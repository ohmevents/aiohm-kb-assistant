<?php
/**
 * Media Library (Uploads) Crawler.
 * This version fixes the logic for stats calculation to correctly identify all supported files.
 */
if (!defined('ABSPATH')) exit;
// Using 'use' statement assuming the class is autoloaded or manually included correctly.
use Smalot\PdfParser\Parser;

class AIOHM_KB_Uploads_Crawler {

    private $rag_engine;
    private $readable_extensions = ['json', 'txt', 'csv', 'pdf'];

    public function __construct() {
        $this->rag_engine = new AIOHM_KB_RAG_Engine();
    }

    public function get_stats() {
        AIOHM_KB_Assistant::log('Starting get_stats in Uploads_Crawler.');
        $stats = ['total_files' => 0, 'indexed_files' => 0, 'pending_files' => 0, 'by_type' => []];
        $all_files_with_status = $this->find_all_supported_attachments(); // Use the new method

        $stats['total_files'] = count($all_files_with_status);
        AIOHM_KB_Assistant::log('Total supported files found: ' . $stats['total_files']);

        foreach ($all_files_with_status as $file_info) {
            $ext = strtolower(pathinfo($file_info['path'], PATHINFO_EXTENSION));
            if (!isset($stats['by_type'][$ext])) {
                $stats['by_type'][$ext] = ['count' => 0, 'indexed' => 0, 'pending' => 0, 'size' => 0];
            }

            $stats['by_type'][$ext]['count']++;
            $stats['by_type'][$ext]['size'] += filesize($file_info['path']);

            if ($file_info['status'] === 'Knowledge Base') {
                $stats['indexed_files']++;
                $stats['by_type'][$ext]['indexed']++;
            } else {
                $stats['pending_files']++;
                $stats['by_type'][$ext]['pending']++;
            }
        }
        AIOHM_KB_Assistant::log('Finished get_stats.');
        return $stats;
    }

    public function find_all_supported_attachments() {
        AIOHM_KB_Assistant::log('Starting find_all_supported_attachments in Uploads_Crawler.');
        $all_items = [];
        $attachments = get_posts([
            'post_type'      => 'attachment',
            'posts_per_page' => -1,
            'post_status'    => 'inherit',
            'cache_results'  => false, // Prevent caching of the post query itself
            'no_found_rows'  => true,  // Optimization
            'update_post_meta_cache' => false, // Prevent populating meta cache during this query
            'update_post_term_cache' => false  // Prevent populating term cache during this query
        ]);
        AIOHM_KB_Assistant::log('Found ' . count($attachments) . ' attachments in WordPress. Iterating through them.');

        foreach ($attachments as $attachment) {
            // IMPORTANT: Clear the object cache for the specific post/attachment before getting its meta.
            // This is a strong measure against persistent caching issues.
            clean_post_cache($attachment->ID);

            $file_path = get_attached_file($attachment->ID);
            if ($file_path && file_exists($file_path)) {
                $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
                AIOHM_KB_Assistant::log("Processing attachment ID: {$attachment->ID}, Title: {$attachment->post_title}, Path: {$file_path}, Ext: {$ext}");
                if (in_array($ext, $this->readable_extensions)) {
                    $is_indexed = get_post_meta($attachment->ID, '_aiohm_indexed', true);
                    $item_status = $is_indexed ? 'Knowledge Base' : 'Ready to Add';
                    AIOHM_KB_Assistant::log("Attachment ID {$attachment->ID} ({$attachment->post_title}) is readable ({$ext}). Indexed Status: " . ($is_indexed ? 'True' : 'False') . ". Final Item Status: {$item_status}");
                    $all_items[] = [
                        'id'     => $attachment->ID,
                        'title'  => $attachment->post_title ?: basename($file_path),
                        'link'   => wp_get_attachment_url($attachment->ID),
                        'type'   => wp_check_filetype($file_path)['type'], // Use wp_check_filetype for MIME type
                        'status' => $item_status,
                        'path'   => $file_path // Added path for stats calculation
                    ];
                } else {
                    AIOHM_KB_Assistant::log("Attachment ID {$attachment->ID} ({$attachment->post_title}) is NOT a readable extension ({$ext}). Skipping.");
                }
            } else {
                AIOHM_KB_Assistant::log("Attachment ID {$attachment->ID} ({$attachment->post_title}) has no file path or file does not exist: {$file_path}");
            }
        }
        AIOHM_KB_Assistant::log('Finished find_all_supported_attachments. Returning ' . count($all_items) . ' supported items.');
        return $all_items;
    }

    public function find_pending_attachments() {
        AIOHM_KB_Assistant::log('Starting find_pending_attachments in Uploads_Crawler.');
        $all_supported = $this->find_all_supported_attachments();
        $pending = array_filter($all_supported, function($item) {
            return $item['status'] === 'Ready to Add';
        });
        AIOHM_KB_Assistant::log('Finished find_pending_attachments. Returning ' . count($pending) . ' pending items.');
        return $pending;
    }

    public function add_attachments_to_kb(array $attachment_ids) {
        AIOHM_KB_Assistant::log('Starting add_attachments_to_kb in Uploads_Crawler for IDs: ' . implode(', ', $attachment_ids));
        if (empty($attachment_ids)) return [];
        $processed = [];
        foreach ($attachment_ids as $attachment_id) {
            $file_path = get_attached_file($attachment_id);
            $file_title = get_the_title($attachment_id) ?: basename($file_path);
            try {
                if (!$file_path || !file_exists($file_path)) {
                    throw new Exception('File path not found.');
                }
                AIOHM_KB_Assistant::log("Attempting to process file ID {$attachment_id} (Title: {$file_title}) for KB.");
                $file_data = $this->process_file($file_path, $attachment_id);
                if ($file_data && !empty(trim($file_data['content']))) {
                    $result = $this->rag_engine->add_entry($file_data['content'], $file_data['type'], $file_data['title'], $file_data['metadata']);
                    
                    // Check if the knowledge base addition was successful
                    if (is_wp_error($result)) {
                        throw new Exception('Failed to add to knowledge base: ' . $result->get_error_message());
                    }
                    
                    // Only update meta if KB addition was successful
                    update_post_meta($attachment_id, '_aiohm_indexed', time());
                    clean_post_cache($attachment_id); // Clear cache for this specific post. This should also clear its post_meta cache.
                    AIOHM_KB_Assistant::log("Successfully processed and indexed attachment ID {$attachment_id}.");
                    $processed[] = ['id' => $attachment_id, 'title' => $file_title, 'status' => 'success'];
                } else {
                     throw new Exception('File type not readable or content is empty after processing.');
                }
            } catch (Exception $e) {
                AIOHM_KB_Assistant::log('Upload scan error processing file ID ' . $attachment_id . ': ' . $e->getMessage(), 'error');
                $processed[] = ['id' => $attachment_id, 'title' => $file_title, 'status' => 'error', 'error' => $e->getMessage()];
            }
        }
        AIOHM_KB_Assistant::log('Finished add_attachments_to_kb.');
        return $processed;
    }

    private function get_supported_attachments($get_all_for_stats = false) {
        // This method's logic is largely superseded by find_all_supported_attachments()
        // It should ideally be refactored or removed if no longer directly used.
        // For now, it will simply return all supported files without status.
        $attachments = get_posts(['post_type' => 'attachment', 'posts_per_page' => -1, 'post_status' => 'inherit']);
        $file_infos = [];

        foreach ($attachments as $attachment) {
            $path = get_attached_file($attachment->ID);
            if ($path && file_exists($path)) {
                 $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                 if (in_array($ext, $this->readable_extensions)) {
                     $file_infos[] = ['path' => $path, 'id' => $attachment->ID];
                 }
            }
        }
        return $file_infos;
    }

    private function process_file($file_path, $attachment_id) {
        AIOHM_KB_Assistant::log("Starting process_file for path: {$file_path}, ID: {$attachment_id}");
        if (!file_exists($file_path) || !is_readable($file_path)) {
            AIOHM_KB_Assistant::log("File not found or not readable: {$file_path}", 'error');
            return null;
        }
        $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        $mime_type = wp_check_filetype($file_path)['type']; // Use wp_check_filetype for MIME type
        $content = '';
        if (in_array($ext, ['json', 'txt', 'csv'])) {
            $content = file_get_contents($file_path);
            AIOHM_KB_Assistant::log("Processed {$ext} file using file_get_contents.");
        } elseif ($ext === 'pdf') {
            AIOHM_KB_Assistant::log("Attempting PDF text extraction for: {$file_path}");
            try {
                if (!class_exists('Smalot\PdfParser\Parser')) {
                    AIOHM_KB_Assistant::log('Error: Smalot\PdfParser\Parser class not found. Ensure library is installed and autoloaded/included correctly.', 'error');
                    throw new Exception('PDF Parser library not found.');
                }
                $parser = new Parser();
                $pdf = $parser->parseFile($file_path);
                $content = $pdf->getText();
                AIOHM_KB_Assistant::log("PDF text extraction successful. Content length: " . strlen($content));

                // If PDF text extraction results in empty content, fall back to metadata
                if (empty(trim($content))) {
                    AIOHM_KB_Assistant::log('PDF text extraction resulted in empty content, falling back to metadata.', 'warning');
                    $attachment_post = get_post($attachment_id);
                    $content_parts = [];
                    if ($attachment_post) {
                        $content_parts[] = $attachment_post->post_title;
                        $content_parts[] = $attachment_post->post_excerpt; // Caption
                        $content_parts[] = $attachment_post->post_content; // Description
                    }
                    $content = implode("\n\n", array_filter($content_parts));
                }

            } catch (Exception $e) {
                // Log any errors during PDF parsing and fall back to metadata
                AIOHM_KB_Assistant::log('Error parsing PDF ' . basename($file_path) . ': ' . $e->getMessage() . '. Falling back to metadata.', 'error');
                $attachment_post = get_post($attachment_id);
                $content_parts = [];
                if ($attachment_post) {
                    $content_parts[] = $attachment_post->post_title;
                    $content_parts[] = $attachment_post->post_excerpt; // Caption
                    $content_parts[] = $attachment_post->post_content; // Description
                }
                $content = implode("\n\n", array_filter($content_parts));
            }
        } else {
            AIOHM_KB_Assistant::log("Unsupported file extension encountered: {$ext}", 'warning');
            return null;
        }
        return [
            'content' => $content, 'type' => $mime_type, 'title' => basename($file_path),
            'metadata' => ['size' => filesize($file_path), 'path' => $file_path, 'attachment_id' => $attachment_id]
        ];
    }
}