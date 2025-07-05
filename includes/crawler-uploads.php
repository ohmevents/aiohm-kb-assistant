<?php
/**
 * Media Library (Uploads) Crawler.
 * This version is complete and supports the two-step "find pending" and "add selected" workflow.
 */
if (!defined('ABSPATH')) exit;

class AIOHM_KB_Uploads_Crawler {
    
    private $rag_engine;
    // Defines extensions the plugin can actually read the content of.
    private $readable_extensions = ['json', 'txt', 'csv'];
    
    public function __construct() {
        $this->rag_engine = new AIOHM_KB_RAG_Engine();
    }

    /**
     * Get statistics about all supported media file types in the Media Library.
     * This function is now present and correct.
     */
    public function get_stats() {
        $stats = ['total_files' => 0, 'by_type' => []];
        // Get all file types for the stats display.
        $all_files = $this->get_supported_attachments(true);
        
        $stats['total_files'] = count($all_files);

        foreach ($all_files as $file_info) {
            if (file_exists($file_info['path'])) {
                $ext = strtolower(pathinfo($file_info['path'], PATHINFO_EXTENSION));
                if (!isset($stats['by_type'][$ext])) {
                    $stats['by_type'][$ext] = ['count' => 0, 'size' => 0];
                }
                $stats['by_type'][$ext]['count']++;
                $stats['by_type'][$ext]['size'] += filesize($file_info['path']);
            }
        }
        return $stats;
    }

    /**
     * Finds all supported and readable attachments that have not yet been indexed.
     */
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

    /**
     * Adds a specific list of attachment IDs to the knowledge base.
     */
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
                
                $file_data = $this->process_file($file_path);
                if ($file_data && !empty($file_data['content'])) {
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

    /**
     * Get a list of supported files from the WordPress Media Library.
     */
    private function get_supported_attachments($get_all_for_stats = false) {
        $attachments = get_posts(['post_type' => 'attachment', 'posts_per_page' => -1, 'post_status' => 'inherit']);
        $file_infos = [];
        $extensions_to_check = $get_all_for_stats 
            ? ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'json', 'doc', 'docx', 'txt', 'csv'] 
            : $this->readable_extensions;

        foreach ($attachments as $attachment) {
            $path = get_attached_file($attachment->ID);
            if ($path && file_exists($path)) {
                 $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                 if (in_array($ext, $extensions_to_check)) {
                     $file_infos[] = ['path' => $path, 'id' => $attachment->ID];
                 }
            }
        }
        return $file_infos;
    }

    /**
     * Process a single file, only reading content from supported text-based files.
     */
    private function process_file($file_path) {
        if (!file_exists($file_path) || !is_readable($file_path)) {
            return null;
        }
        
        $mime_type = mime_content_type($file_path);
        $content = '';
        if (in_array(strtolower(pathinfo($file_path, PATHINFO_EXTENSION)), $this->readable_extensions)) {
            $content = file_get_contents($file_path);
        } else {
            return null;
        }
        return [
            'content' => $content,
            'type' => $mime_type,
            'title' => basename($file_path),
            'metadata' => ['size' => filesize($file_path), 'path' => $file_path]
        ];
    }
}