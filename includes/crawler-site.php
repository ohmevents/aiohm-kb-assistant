<?php
/**
 * Website content crawler for posts, pages, and menus with ARMember integration
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
     * Process website content in batches with progress tracking
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
        
        // Calculate which content type to process based on offset
        if ($current_offset < $total_posts) {
            // Process posts
            $posts = $this->get_posts_batch($batch_size, $current_offset);
            $results['posts'] = $this->process_posts($posts);
            $results['progress']['currently_scanning'] = 'Posts';
            $results['total_processed'] = count($results['posts']);
        } else {
            // Process pages
            $pages_offset = $current_offset - $total_posts;
            $pages = $this->get_pages_batch($batch_size, $pages_offset);
            $results['pages'] = $this->process_posts($pages);
            $results['progress']['currently_scanning'] = 'Pages';
            $results['total_processed'] = count($results['pages']);
        }
        
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
        
        return $results;
    }
    
    /**
     * Scan website content in batches
     */
    public function scan_website_batch($batch_size = 10, $offset = 0) {
        $results = array(
            'posts' => $this->scan_posts_batch($batch_size, $offset),
            'pages' => $this->scan_pages_batch($batch_size, $offset),
            'menus' => array(),
            'total_processed' => 0
        );
        
        // Only scan menus on first batch
        if ($offset === 0) {
            $results['menus'] = $this->scan_menus();
        }
        
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
        
        return $this->process_posts($posts);
    }
    
    /**
     * Scan posts in batches
     */
    public function scan_posts_batch($batch_size = 10, $offset = 0) {
        $posts = get_posts(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'numberposts' => $batch_size,
            'offset' => $offset,
            'meta_query' => array(
                array(
                    'key' => '_aiohm_indexed',
                    'compare' => 'NOT EXISTS'
                )
            )
        ));
        
        return $this->process_posts($posts);
    }
    
    /**
     * Get posts batch
     */
    private function get_posts_batch($batch_size, $offset) {
        return get_posts(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'numberposts' => $batch_size,
            'offset' => $offset,
            'meta_query' => array(
                array(
                    'key' => '_aiohm_indexed',
                    'compare' => 'NOT EXISTS'
                )
            )
        ));
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
        
        return $this->process_posts($pages);
    }
    
    /**
     * Scan pages in batches
     */
    public function scan_pages_batch($batch_size = 10, $offset = 0) {
        $pages = get_posts(array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'numberposts' => $batch_size,
            'offset' => $offset,
            'meta_query' => array(
                array(
                    'key' => '_aiohm_indexed',
                    'compare' => 'NOT EXISTS'
                )
            )
        ));
        
        return $this->process_posts($pages);
    }
    
    /**
     * Get pages batch
     */
    private function get_pages_batch($batch_size, $offset) {
        return get_posts(array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'numberposts' => $batch_size,
            'offset' => $offset,
            'meta_query' => array(
                array(
                    'key' => '_aiohm_indexed',
                    'compare' => 'NOT EXISTS'
                )
            )
        ));
    }
    
    /**
     * Process posts/pages array
     */
    private function process_posts($posts) {
        $processed = array();
        
        foreach ($posts as $post) {
            try {
                $content_data = $this->extract_post_content($post);
                
                if (!empty($content_data['content'])) {
                    // Determine access level for ARMember integration
                    $access_level = $this->determine_content_access_level($post);
                    
                    // Add to vector database with access level
                    $entry_id = $this->rag_engine->add_entry(
                        $content_data['content'],
                        $post->post_type,
                        $content_data['title'],
                        array_merge($content_data['metadata'], array(
                            'access_level' => $access_level
                        ))
                    );
                    
                    // Mark as indexed
                    update_post_meta($post->ID, '_aiohm_indexed', time());
                    update_post_meta($post->ID, '_aiohm_access_level', $access_level);
                    
                    $processed[] = array(
                        'id' => $post->ID,
                        'title' => $content_data['title'],
                        'url' => get_permalink($post->ID),
                        'content_length' => strlen($content_data['content']),
                        'access_level' => $access_level,
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
                                'items' => $menu_content['items'],
                                'access_level' => 'basic' // Menus are usually public
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
        // Get the content and clean it
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
        
        // Get custom fields that might be relevant
        $custom_fields = get_post_meta($post->ID);
        $relevant_meta = array();
        
        // Filter out system meta fields
        foreach ($custom_fields as $key => $value) {
            if (!str_starts_with($key, '_') && !empty($value[0])) {
                $relevant_meta[$key] = $value[0];
            }
        }
        
        return array(
            'title' => $post->post_title,
            'content' => $content,
            'excerpt' => $excerpt,
            'categories' => $categories,
            'tags' => $tags,
            'metadata' => array(
                'post_id' => $post->ID,
                'url' => get_permalink($post->ID),
                'excerpt' => $excerpt,
                'categories' => $categories,
                'tags' => $tags,
                'post_type' => $post->post_type,
                'post_date' => $post->post_date,
                'post_modified' => $post->post_modified,
                'author_id' => $post->post_author,
                'author_name' => get_the_author_meta('display_name', $post->post_author),
                'custom_fields' => $relevant_meta,
                'word_count' => str_word_count($content),
                'template' => $post->post_type === 'page' ? get_page_template_slug($post->ID) : ''
            )
        );
    }
    
    /**
     * Determine content access level based on ARMember restrictions and other factors
     */
    private function determine_content_access_level($post) {
        // Check ARMember access restrictions first
        if (class_exists('ARMemberLite')) {
            $arm_access_rules = get_post_meta($post->ID, 'arm_access_plan', true);
            
            if (!empty($arm_access_rules)) {
                // Post has ARMember restrictions
                if (is_array($arm_access_rules)) {
                    // Multiple plans - determine highest level
                    return $this->determine_highest_access_level_from_plans($arm_access_rules);
                } else {
                    // Single plan restriction
                    return $this->map_armember_plan_to_access_level($arm_access_rules);
                }
            }
        }
        
        // Check categories for access hints
        $categories = get_the_category($post->ID);
        foreach ($categories as $category) {
            $cat_name = strtolower($category->name);
            
            if (strpos($cat_name, 'premium') !== false || strpos($cat_name, 'paid') !== false) {
                return 'premium';
            }
            
            if (strpos($cat_name, 'vip') !== false || strpos($cat_name, 'exclusive') !== false) {
                return 'premium_plus';
            }
            
            if (strpos($cat_name, 'members') !== false || strpos($cat_name, 'private') !== false) {
                return 'premium';
            }
        }
        
        // Check tags for access hints
        $tags = get_the_tags($post->ID);
        if ($tags) {
            foreach ($tags as $tag) {
                $tag_name = strtolower($tag->name);
                
                if (strpos($tag_name, 'premium') !== false) {
                    return 'premium';
                }
                
                if (strpos($tag_name, 'vip') !== false) {
                    return 'premium_plus';
                }
            }
        }
        
        // Check custom fields for access level
        $custom_access = get_post_meta($post->ID, 'access_level', true);
        if (!empty($custom_access) && in_array($custom_access, array('basic', 'premium', 'premium_plus'))) {
            return $custom_access;
        }
        
        // Check if post is password protected
        if (!empty($post->post_password)) {
            return 'premium';
        }
        
        // Default to basic access
        return 'basic';
    }
    
    /**
     * Map ARMember plan to access level
     */
    private function map_armember_plan_to_access_level($plan_id) {
        global $wpdb;
        
        $plan_data = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}arm_subscription_plans WHERE arm_subscription_plan_id = %d",
                $plan_id
            ),
            ARRAY_A
        );
        
        if (!$plan_data) {
            return 'basic';
        }
        
        // Map plan types to access levels
        $access_mapping = array(
            'free_plan' => 'basic',
            'paid_finite' => 'premium',
            'paid_infinite' => 'premium_plus',
            'recurring' => 'premium_plus'
        );
        
        $plan_type = $plan_data['arm_subscription_plan_type'];
        return isset($access_mapping[$plan_type]) ? $access_mapping[$plan_type] : 'basic';
    }
    
    /**
     * Determine highest access level from multiple plans
     */
    private function determine_highest_access_level_from_plans($plan_ids) {
        $access_hierarchy = array('basic' => 1, 'premium' => 2, 'premium_plus' => 3);
        $highest_level = 'basic';
        
        foreach ($plan_ids as $plan_id) {
            $level = $this->map_armember_plan_to_access_level($plan_id);
            if ($access_hierarchy[$level] > $access_hierarchy[$highest_level]) {
                $highest_level = $level;
            }
        }
        
        return $highest_level;
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
                'description' => $item->description,
                'parent_id' => $item->menu_item_parent
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
                delete_post_meta($content_id, '_aiohm_access_level');
                
                // Remove from vector database
                $rag_engine = new AIOHM_KB_RAG_Engine();
                $entry_id = $content_type . '_' . md5($post->post_title . $post->post_content);
                $rag_engine->delete_entry($entry_id);
                
                // Rescan
                return $this->process_posts(array($post));
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
    
    /**
     * Count total items for progress calculation
     */
    public function count_total_items() {
        $total_posts = wp_count_posts('post')->publish;
        $total_pages = wp_count_posts('page')->publish;
        
        return $total_posts + $total_pages;
    }
    
    /**
     * Format time remaining in human-readable format
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
    
    /**
     * Get content by access level for admin review
     */
    public function get_content_by_access_level($access_level = 'all') {
        $args = array(
            'post_type' => array('post', 'page'),
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_key' => '_aiohm_indexed'
        );
        
        if ($access_level !== 'all') {
            $args['meta_query'] = array(
                array(
                    'key' => '_aiohm_access_level',
                    'value' => $access_level,
                    'compare' => '='
                )
            );
        }
        
        $posts = get_posts($args);
        $results = array();
        
        foreach ($posts as $post) {
            $access_level_meta = get_post_meta($post->ID, '_aiohm_access_level', true);
            
            $results[] = array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'type' => $post->post_type,
                'url' => get_permalink($post->ID),
                'access_level' => $access_level_meta ?: 'basic',
                'indexed_date' => get_post_meta($post->ID, '_aiohm_indexed', true)
            );
        }
        
        return $results;
    }
}