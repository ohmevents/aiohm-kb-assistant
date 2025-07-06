<?php
/**
 * Media Library (Uploads) Crawler.
 * Final version with PDF metadata scanning.
 */
if (!defined('ABSPATH')) exit;

class AIOHM_KB_Uploads_Crawler {
    
    private $rag_engine;
    private $readable_extensions = ['json', 'txt', 'csv', 'pdf'];
    
    public function __construct() {
        $this->rag_engine = new AIOHM_KB_RAG_Engine();
    }

    public function get_stats() { /* ... function content ... */ }

    public function find_pending_attachments() {
        $pending_items = [];
        $attachments = get_posts([
            'post_type'      => 'attachment',
            'posts_per_page' => -1,
            'post_status'    => 'inherit',
            'meta_query'     => [['key' => '_aiohm_indexed', 'compare' => 'NOT EXISTS']]
        ]);
        foreach ($attachments as $attachment) {
            $file_path = get_attached_file($attachment->ID);
            if ($file_path && file_exists($file_path)) {
                $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
                if (in_array($ext, $this->readable_extensions)) {
                    $pending_items[] = [
                        'id'    => $attachment->ID,
                        'title' => $attachment->post_title ?: basename($file_path),
                        'link'  => wp_get_attachment_url($attachment->ID),
                        'type'  => $attachment->post_mime_type,
                    ];
                }
            }
        }
        return $pending_items;
    }

    public function add_attachments_to_kb(array $attachment_ids) {
        if (empty($attachment_ids)) return [];
        $processed = [];
        foreach ($attachment_ids as $attachment_id) {
            $file_path = get_attached_file($attachment_id);
            $file_title = get_the_title($attachment_id) ?: basename($file_path);
            try {
                if (!$file_path || !file_exists($file_path)) {
                    throw new Exception('File path not found.');
                }
                $file_data = $this->process_file($file_path, $attachment_id);
                if ($file_data && !empty(trim($file_data['content']))) {
                    $this->rag_engine->add_entry($file_data['content'], $file_data['type'], $file_data['title'], $file_data['metadata']);
                    update_post_meta($attachment_id, '_aiohm_indexed', time());
                    $processed[] = ['id' => $attachment_id, 'title' => $file_title, 'status' => 'success'];
                } else {
                     throw new Exception('File type not readable or content is empty.');
                }
            } catch (Exception $e) {
                AIOHM_KB_Assistant::log('Upload scan error processing file ID ' . $attachment_id . ': ' . $e->getMessage(), 'error');
                $processed[] = ['id' => $attachment_id, 'title' => $file_title, 'status' => 'error', 'error' => $e->getMessage()];
            }
        }
        return $processed;
    }
    
    private function get_supported_attachments($get_all_for_stats = false) { /* ... function content ... */ }

    private function process_file($file_path, $attachment_id) {
        if (!file_exists($file_path) || !is_readable($file_path)) {
            return null;
        }
        $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        $mime_type = mime_content_type($file_path);
        $content = '';
        if (in_array($ext, ['json', 'txt', 'csv'])) {
            $content = file_get_contents($file_path);
        } elseif ($ext === 'pdf') {
            $attachment_post = get_post($attachment_id);
            $content_parts = [];
            if ($attachment_post) {
                $content_parts[] = $attachment_post->post_title;
                $content_parts[] = $attachment_post->post_excerpt;
                $content_parts[] = $attachment_post->post_content;
            }
            $content = implode("\n\n", array_filter($content_parts));
        } else {
            return null;
        }
        return [
            'content' => $content, 'type' => $mime_type, 'title' => basename($file_path),
            'metadata' => ['size' => filesize($file_path), 'path' => $file_path, 'attachment_id' => $attachment_id]
        ];
    }
}