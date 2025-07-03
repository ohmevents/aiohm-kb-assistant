<?php
/**
 * Website content crawler for posts, pages, and menus
 * 
 * @package AIOHM_KB_Assistant
 * @author ohmevents
 * @version 1.0.0
 * @created 2025-07-02 12:34:11
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AIOHM_KB_Site_Crawler {
    
    private $rag_engine;
    private $chunk_size;
    private $chunk_overlap;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->rag_engine = new AIOHM_KB_RAG_Engine();
        $settings = AIOHM_KB_Core_Init::get_settings();
        $this->chunk_size = isset($settings['chunk_size']) ? $settings['chunk_size'] : 1000;
        $this->chunk_overlap = isset($settings['chunk_overlap']) ? $settings['chunk_overlap'] : 200;
    }

    /**
     * Get scan statistics
     */
    public function get_scan_stats() {
        $total_posts = wp_count_posts('post')->publish;
        $total_pages = wp_count_posts('page')->publish;
        $total_menus = count(wp_get_nav_menus());
        
        // Get indexed posts count
        $indexed_posts = get_posts(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_query' => array(
                array(
                    'key' => '_aiohm_indexed',
                    'compare' => 'EXISTS'
                )
            ),
            'fields' => 'ids'
        ));
        
        // Get indexed pages count
        $indexed_pages = get_posts(array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_query' => array(
                array(
                    'key' => '_aiohm_indexed',
                    'compare' => 'EXISTS'
                )
            ),
            'fields' => 'ids'
        ));
        
        return array(
            'posts' => array(
                'total' => $total_posts,
                'indexed' => count($indexed_posts),
                'pending' => $total_posts - count($indexed_posts)
            ),
            'pages' => array(
                'total' => $total_pages,
                'indexed' => count($indexed_pages),
                'pending' => $total_pages - count($indexed_pages)
            ),
            'menus' => array(
                'total' => $total_menus,
                'indexed' => $total_menus,
                'pending' => 0
            ),
            'last_scan' => get_option('aiohm_last_scan_time', false)
        );
    }

    /**
     * Scan website with progress tracking
     */
    public function scan_website_with_progress($batch_size = 5, $current_offset = 0) {
        $start_time = microtime(true);
        
        $total_posts = wp_count_posts('post')->publish;
        $total_pages = wp_count_posts('page')->publish;
        $total_items = $total_posts + $total_pages;
        
        $results = array(
            'posts' => array(),
            'pages' => array(),
            'total_processed' => 0,
            'progress' => array(
                'current_offset' => $current_offset,
                'total_items' => $total_items,
                'percentage' => 0,
                'estimated_time_remaining' => 0,
                'currently_scanning' => '',
                'items_per_minute' => 0,
                'is_complete' => false
            )
        );
        
        try {
            // Process batch
            if ($current_offset < $total_posts) {
                // Process posts
                $results['posts'] = $this->process_posts_batch($batch_size, $current_offset);
                $results['progress']['currently_scanning'] = 'Posts';
            } else {
                // Process pages
                $pages_offset = $current_offset - $total_posts;
                $results['pages'] = $this->process_pages_batch($batch_size, $pages_offset);
                $results['progress']['currently_scanning'] = 'Pages';
            }
            
            $results['total_processed'] = count($results['posts']) + count($results['pages']);
            
            // Calculate progress
            $new_offset = $current_offset + $results['total_processed'];
            $results['progress']['current_offset'] = $new_offset;
            $results['progress']['percentage'] = ($total_items > 0) ? round(($new_offset / $total_items) * 100, 1) : 100;
            
            // Calculate timing metrics
            $elapsed_time = microtime(true) - $start_time;
            if ($elapsed_time > 0 && $results['total_processed'] > 0) {
                $items_per_second = $results['total_processed'] / $elapsed_time;
                $remaining_items = $total_items - $new_offset;
                
                if ($items_per_second > 0) {
                    $estimated_seconds = $remaining_items / $items_per_second;
                    $results['progress']['estimated_time_remaining'] = $this->format_time_remaining($estimated_seconds);
                    $results['progress']['items_per_minute'] = round($items_per_second * 60);
                }
            }
            
            // Check if complete
            $results['progress']['is_complete'] = ($new_offset >= $total_items);
            
            if ($results['progress']['is_complete']) {
                update_option('aiohm_last_scan_time', current_time('mysql'));
            }
            
        } catch (Exception $e) {
            AIOHM_KB_Core_Init::log('Scan error: ' . $e->getMessage(), 'error');
            throw $e;
        }
        
        return $results;
    }

    /**
     * Process posts batch
     */
    private function process_posts_batch($batch_size, $offset) {
        $posts = get_posts(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'numberposts' => $batch_size,
            'offset' => $offset,
            'orderby' => 'ID',
            'order' => 'ASC'
        ));
        
        return $this->process_posts($posts);
    }

    /**
     * Process pages batch
     */
    private function process_pages_batch($batch_size, $offset) {
        $pages = get_posts(array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'numberposts' => $batch_size,
            'offset' => $offset,
            'orderby' => 'ID',
            'order' => 'ASC'
        ));
        
        return $this->process_posts($pages);
    }

    /**
     * Process posts/pages
     */
    private function process_posts($posts) {
        $processed = array();
        
        foreach ($posts as $post) {
            try {
                $content_data = $this->extract_post_content($post);
                
                if (!empty($content_data['content'])) {
                    // Add to vector database
                    $entry_id = $this->rag_engine->add_entry(
                        $content_data['content'],
                        $post->post_type,
                        $content_data['title'],
                        $content_data['metadata']
                    );
                    
                    // Mark as indexed
                    update_post_meta($post->ID, '_aiohm_indexed', time());
                    
                    $processed[] = array(
                        'id' => $post->ID,
                        'title' => $content_data['title'],
                        'url' => get_permalink($post->ID),
                        'content_length' => strlen($content_data['content']),
                        'entry_id' => $entry_id,
                        'status' => 'success'
                    );
                }
            } catch (Exception $e) {
                AIOHM_KB_Core_Init::log('Error processing post ' . $post->ID . ': ' . $e->getMessage(), 'error');
                
                $processed[] = array(
                    'id' => $post->ID,
                    'title' => $post->post_title,
                    'status' => 'error',
                    'error' => $e->getMessage()
                );
            }
        }
        
        return $processed;
    }

    /**
     * Extract content from post/page
     */
    private function extract_post_content($post) {
        $content = wp_strip_all_tags($post->post_content);
        $content = preg_replace('/\s+/', ' ', $content);
        $content = trim($content);
        
        $categories = array();
        $tags = array();
        
        if ($post->post_type === 'post') {
            $post_categories = get_the_category($post->ID);
            $post_tags = get_the_tags($post->ID);
            
            if ($post_categories) {
                $categories = wp_list_pluck($post_categories, 'name');
            }
            
            if ($post_tags) {
                $tags = wp_list_pluck($post_tags, 'name');
            }
        }
        
        $excerpt = $post->post_excerpt;
        if (empty($excerpt)) {
            $excerpt = wp_trim_words($content, 55);
        }
        
        return array(
            'title' => $post->post_title,
            'content' => $content,
            'metadata' => array(
                'post_id' => $post->ID,
                'url' => get_permalink($post->ID),
                'excerpt' => $excerpt,
                'categories' => $categories,
                'tags' => $tags,
                'post_type' => $post->post_type,
                'post_date' => $post->post_date,
                'author_id' => $post->post_author,
                'author_name' => get_the_author_meta('display_name', $post->post_author)
            )
        );
    }

    /**
     * Format time remaining
     */
    private function format_time_remaining($seconds) {
        if ($seconds < 60) {
            return round($seconds) . ' seconds';
        } elseif ($seconds < 3600) {
            return round($seconds / 60, 1) . ' minutes';
        } else {
            $hours = floor($seconds / 3600);
            $minutes = round(($seconds % 3600) / 60);
            return $hours . ' hours ' . $minutes . ' minutes';
        }
    }
}