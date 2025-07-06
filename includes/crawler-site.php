<?php
/**
 * Website content crawler for posts and pages.
 * This version adds a cache-clearing mechanism to fix the refresh issue.
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
                    'status' => $is_indexed ? 'In Knowledge Base' : 'Ready to Add',
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
                    throw new Exception('Content is empty after processing.');
                }
                $this->rag_engine->add_entry($content_data['content'], $post->post_type, $content_data['title'], $content_data['metadata']);
                update_post_meta($post->ID, '_aiohm_indexed', time());

                // ** THE FIX IS HERE: Clear the cache for this specific post. **
                // This ensures that when we re-fetch the list, we get the new "In Knowledge Base" status.
                clean_post_cache($post->ID);

                $processed[] = ['id' => $post->ID, 'title' => $content_data['title'], 'status' => 'success'];
            } catch (Exception $e) {
                AIOHM_KB_Assistant::log('Error processing post ' . $post->ID . ': ' . $e->getMessage(), 'error');
                $processed[] = ['id' => $post->ID, 'title' => $post->post_title, 'status' => 'error', 'error' => $e->getMessage()];
            }
        }
        return $processed;
    }

    private function extract_post_content($post) {
        $content = strip_shortcodes($post->post_content);
        $content = wp_strip_all_tags($content);
        $content = preg_replace('/\s+/', ' ', $content);
        return [
            'title' => $post->post_title,
            'content' => trim($content),
            'metadata' => ['post_id' => $post->ID, 'url' => get_permalink($post->ID)]
        ];
    }
}