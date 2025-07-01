<?php
/**
 * Website content crawler for posts, pages, and menus
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AIOHM_KB_Site_Crawler {
    
    private $rag_engine;
    
    public function __construct() {
        $this->rag_engine = new AIOHM_KB_RAG_Engine();
    }
    
    /**
     * Scan entire website content
     */
    public function scan_website() {
        $results = array(
            'posts' => $this->scan_posts(),
            'pages' => $this->scan_pages(),
            'menus' => $this->scan_menus(),
            'total_processed' => 0
        );
        
        $results['total_processed'] = count($results['posts']) + count($results['pages']) + count($results['menus']);
        
        return $results;
    }
    
    /**
     * Scan all published posts
     */
    public function scan_posts() {
        $posts = get_posts(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_query' => array(
                array(
                    'key' => '_aiohm_indexed',
                    'compare' => 'NOT EXISTS'
                )
            )
        ));
        
        $processed = array();
        
        foreach ($posts as $post) {
            try {
                $content_data = $this->extract_post_content($post);
                
                if (!empty($content_data['content'])) {
                    // Add to vector database
                    $entry_id = $this->rag_engine->add_entry(
                        $content_data['content'],
                        'post',
                        $content_data['title'],
                        array(
                            'post_id' => $post->ID,
                            'url' => get_permalink($post->ID),
                            'excerpt' => $content_data['excerpt'],
                            'categories' => $content_data['categories'],
                            'tags' => $content_data['tags']
                        )
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
                    'url' => get_permalink($post->ID),
                    'status' => 'error',
                    'error' => $e->getMessage()
                );
            }
        }
        
        return $processed;
    }
    
    /**
     * Scan all published pages
     */
    public function scan_pages() {
        $pages = get_posts(array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_query' => array(
                array(
                    'key' => '_aiohm_indexed',
                    'compare' => 'NOT EXISTS'
                )
            )
        ));
        
        $processed = array();
        
        foreach ($pages as $page) {
            try {
                $content_data = $this->extract_post_content($page);
                
                if (!empty($content_data['content'])) {
                    // Add to vector database
                    $entry_id = $this->rag_engine->add_entry(
                        $content_data['content'],
                        'page',
                        $content_data['title'],
                        array(
                            'post_id' => $page->ID,
                            'url' => get_permalink($page->ID),
                            'excerpt' => $content_data['excerpt'],
                            'template' => get_page_template_slug($page->ID)
                        )
                    );
                    
                    // Mark as indexed
                    update_post_meta($page->ID, '_aiohm_indexed', time());
                    
                    $processed[] = array(
                        'id' => $page->ID,
                        'title' => $content_data['title'],
                        'url' => get_permalink($page->ID),
                        'content_length' => strlen($content_data['content']),
                        'entry_id' => $entry_id,
                        'status' => 'success'
                    );
                }
            } catch (Exception $e) {
                AIOHM_KB_Core_Init::log('Error processing page ' . $page->ID . ': ' . $e->getMessage(), 'error');
                
                $processed[] = array(
                    'id' => $page->ID,
                    'title' => $page->post_title,
                    'url' => get_permalink($page->ID),
                    'status' => 'error',
                    'error' => $e->getMessage()
                );
            }
        }
        
        return $processed;
    }
    
    /**
     * Scan navigation menus
     */
    public function scan_menus() {
        $menus = wp_get_nav_menus();
        $processed = array();
        
        foreach ($menus as $menu) {
            try {
                $menu_items = wp_get_nav_menu_items($menu->term_id);
                
                if ($menu_items) {
                    $menu_content = $this->extract_menu_content($menu, $menu_items);
                    
                    if (!empty($menu_content['content'])) {
                        // Add to vector database
                        $entry_id = $this->rag_engine->add_entry(
                            $menu_content['content'],
                            'menu',
                            $menu_content['title'],
                            array(
                                'menu_id' => $menu->term_id,
                                'menu_slug' => $menu->slug,
                                'item_count' => count($menu_items),
                                'items' => $menu_content['items']
                            )
                        );
                        
                        $processed[] = array(
                            'id' => $menu->term_id,
                            'title' => $menu_content['title'],
                            'content_length' => strlen($menu_content['content']),
                            'item_count' => count($menu_items),
                            'entry_id' => $entry_id,
                            'status' => 'success'
                        );
                    }
                }
            } catch (Exception $e) {
                AIOHM_KB_Core_Init::log('Error processing menu ' . $menu->term_id . ': ' . $e->getMessage(), 'error');
                
                $processed[] = array(
                    'id' => $menu->term_id,
                    'title' => $menu->name,
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
        $content = preg_replace('/\s+/', ' ', $content); // Normalize whitespace
        $content = trim($content);
        
        // Extract categories and tags for posts
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
        
        // Create excerpt if none exists
        $excerpt = $post->post_excerpt;
        if (empty($excerpt)) {
            $excerpt = wp_trim_words($content, 55);
        }
        
        return array(
            'title' => $post->post_title,
            'content' => $content,
            'excerpt' => $excerpt,
            'categories' => $categories,
            'tags' => $tags
        );
    }
    
    /**
     * Extract content from menu
     */
    private function extract_menu_content($menu, $menu_items) {
        $content_parts = array();
        $items_data = array();
        
        $content_parts[] = "Navigation Menu: " . $menu->name;
        
        foreach ($menu_items as $item) {
            $item_text = "Menu Item: " . $item->title;
            
            if (!empty($item->description)) {
                $item_text .= " - " . $item->description;
            }
            
            if (!empty($item->url)) {
                $item_text .= " (Link: " . $item->url . ")";
            }
            
            $content_parts[] = $item_text;
            
            $items_data[] = array(
                'title' => $item->title,
                'url' => $item->url,
                'description' => $item->description
            );
        }
        
        return array(
            'title' => "Menu: " . $menu->name,
            'content' => implode("\n", $content_parts),
            'items' => $items_data
        );
    }
    
    /**
     * Rescan specific content by ID and type
     */
    public function rescan_content($content_id, $content_type) {
        if ($content_type === 'post' || $content_type === 'page') {
            $post = get_post($content_id);
            if ($post) {
                // Remove existing index
                delete_post_meta($content_id, '_aiohm_indexed');
                
                // Rescan
                if ($content_type === 'post') {
                    return $this->scan_posts();
                } else {
                    return $this->scan_pages();
                }
            }
        } elseif ($content_type === 'menu') {
            return $this->scan_menus();
        }
        
        return false;
    }
    
    /**
     * Get scan statistics
     */
    public function get_scan_stats() {
        $total_posts = wp_count_posts('post')->publish;
        $total_pages = wp_count_posts('page')->publish;
        $total_menus = count(wp_get_nav_menus());
        
        $indexed_posts = get_posts(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_key' => '_aiohm_indexed',
            'fields' => 'ids'
        ));
        
        $indexed_pages = get_posts(array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_key' => '_aiohm_indexed',
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
                'indexed' => $total_menus, // Menus are always reindexed
                'pending' => 0
            )
        );
    }
}
