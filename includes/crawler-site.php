<?php
/**
 * Website content crawler for posts and pages.
 * This is the final version with the cache-clearing fix and updated status text.
 */
if (!defined('ABSPATH')) exit;

class AIOHM_KB_Site_Crawler {
    
    private $rag_engine;
    
    public function __construct() {
        $this->rag_engine = new AIOHM_KB_RAG_Engine();
    }

    public function find_all_content() {
        $all_items = [];
        $post_types = ['post', 'page'];

        foreach ($post_types as $post_type) {
            $args = [
                'post_type' => $post_type,
                'post_status' => 'publish',
                'numberposts' => -1,
            ];
            $items = get_posts($args);

            foreach ($items as $item) {
                $is_indexed = get_post_meta($item->ID, '_aiohm_indexed', true);
                $all_items[] = [
                    'id'     => $item->ID,
                    'title'  => $item->post_title,
                    'link'   => get_permalink($item->ID),
                    'type'   => $item->post_type,
                    'status' => $is_indexed ? 'Knowledge Base' : 'Ready to Add',
                ];
            }
        }
        return $all_items;
    }

    public function get_scan_stats() {
        $total_posts = wp_count_posts('post')->publish;
        $total_pages = wp_count_posts('page')->publish;
        
        $indexed_posts = (new WP_Query(['post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => -1, 'meta_key' => '_aiohm_indexed', 'fields' => 'ids']))->post_count;
        $indexed_pages = (new WP_Query(['post_type' => 'page', 'post_status' => 'publish', 'posts_per_page' => -1, 'meta_key' => '_aiohm_indexed', 'fields' => 'ids']))->post_count;

        return [
            'posts' => ['total' => $total_posts, 'indexed' => $indexed_posts, 'pending' => $total_posts - $indexed_posts],
            'pages' => ['total' => $total_pages, 'indexed' => $indexed_pages, 'pending' => $total_pages - $indexed_pages]
        ];
    }

    public function add_items_to_kb(array $item_ids) {
        if (empty($item_ids)) return [];
        $posts = get_posts(['post__in' => $item_ids, 'post_type' => 'any', 'numberposts' => count($item_ids), 'post_status' => 'publish']);
        return $this->process_posts($posts);
    }

    private function process_posts($posts) {
        $processed = [];
        foreach ($posts as $post) {
            try {
                $content_data = $this->extract_post_content($post);
                
                if (empty(trim($content_data['content']))) {
                    $processed[] = ['id' => $post->ID, 'title' => $post->post_title, 'status' => 'skipped', 'reason' => 'empty_content'];
                    continue;
                }
                
                $result = $this->rag_engine->add_entry($content_data['content'], $post->post_type, $content_data['title'], $content_data['metadata']);
                
                // Check if the knowledge base addition was successful
                if (is_wp_error($result)) {
                    throw new Exception('Failed to add to knowledge base: ' . $result->get_error_message());
                }
                
                // Only update meta if KB addition was successful
                update_post_meta($post->ID, '_aiohm_indexed', time());
                
                // Clear the cache for this specific post to ensure status updates immediately.
                clean_post_cache($post->ID);

                $processed[] = ['id' => $post->ID, 'title' => $content_data['title'], 'status' => 'success'];
            } catch (Exception $e) {
                AIOHM_KB_Assistant::log('Error processing post ' . $post->ID . ': ' . $e->getMessage(), 'error');
                $processed[] = ['id' => $post->ID, 'title' => $post->post_title, 'status' => 'error', 'error_message' => $e->getMessage()];
            }
        }
        return $processed;
    }

    private function extract_post_content($post) {
        $original_content = $post->post_content;
        
        // First try: Standard content extraction (remove shortcodes and HTML)
        $content = strip_shortcodes($original_content);
        $content = wp_strip_all_tags($content);
        $content = preg_replace('/\s+/', ' ', $content);
        $content = trim($content);
        
        // If content is empty or too short, try alternative approaches
        if (strlen($content) < 10) {
            // For pages that are mostly shortcodes, include descriptive content
            $fallback_content = $this->generate_fallback_content($post, $original_content);
            if (!empty($fallback_content)) {
                $content = $fallback_content;
            }
        }
        
        return [
            'title' => $post->post_title,
            'content' => $content,
            'metadata' => ['post_id' => $post->ID, 'url' => get_permalink($post->ID)]
        ];
    }
    
    private function generate_fallback_content($post, $original_content) {
        $fallback_parts = [];
        
        // Add post title as context
        if (!empty($post->post_title)) {
            $fallback_parts[] = "Page: " . $post->post_title;
        }
        
        // Add post excerpt if available
        if (!empty($post->post_excerpt)) {
            $fallback_parts[] = $post->post_excerpt;
        }
        
        // Detect and describe common shortcodes
        if (strpos($original_content, '[') !== false) {
            $shortcode_descriptions = [];
            
            // Common WordPress/plugin shortcodes with descriptions
            $known_shortcodes = [
                'wp_login' => 'WordPress login form',
                'login' => 'User login functionality', 
                'user_profile' => 'User profile information and editing',
                'profile' => 'User profile page',
                'account' => 'User account management',
                'pmpro_account' => 'Paid Memberships Pro account page',
                'contact' => 'Contact form or information',
                'membership' => 'Membership information',
                'dashboard' => 'User dashboard'
            ];
            
            foreach ($known_shortcodes as $shortcode => $description) {
                if (strpos($original_content, "[$shortcode") !== false) {
                    $shortcode_descriptions[] = $description;
                }
            }
            
            if (!empty($shortcode_descriptions)) {
                $fallback_parts[] = "This page contains: " . implode(', ', $shortcode_descriptions);
            } else {
                $fallback_parts[] = "This page contains interactive elements and functionality";
            }
        }
        
        // Add page type context
        if ($post->post_type === 'page') {
            $fallback_parts[] = "This is a WordPress page that provides specific functionality for users.";
        }
        
        return implode('. ', $fallback_parts);
    }
}