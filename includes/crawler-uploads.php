<?php
/**
 * Media Library (Uploads) Crawler.
 * This version fixes the logic for stats calculation to correctly identify all supported files.
 */
if (!defined('ABSPATH')) exit;

// IMPORTANT: This plugin assumes Smalot/PdfParser library is available for PDF text extraction.
// If using Composer, ensure 'composer require smalot/pdfparser' has been run.
// If not using Composer, you will need to manually include the library files.
// For example: require_once AIOHM_KB_PLUGIN_DIR . 'path/to/Smalot/PdfParser/Parser.php';
// For this example, we assume the class is accessible.
use Smalot\PdfParser\Parser;

class AIOHM_KB_Uploads_Crawler {
    
    private $rag_engine;
    private $readable_extensions = ['json', 'txt', 'csv', 'pdf'];
    
    public function __construct() {
        $this->rag_engine = new AIOHM_KB_RAG_Engine();
    }

    public function get_stats() {
        $stats = ['total_files' => 0, 'indexed_files' => 0, 'pending_files' => 0, 'by_type' => []];
        $all_files_with_status = $this->find_all_supported_attachments(); // Use the new method

        $stats['total_files'] = count($all_files_with_status);

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
        return $stats;
    }

    public function find_all_supported_attachments() {
        $all_items = [];
        $attachments = get_posts([
            'post_type'      => 'attachment',
            'posts_per_page' => -1,
            'post_status'    => 'inherit',
        ]);

        foreach ($attachments as $attachment) {
            $file_path = get_attached_file($attachment->ID);
            if ($file_path && file_exists($file_path)) {
                $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
                if (in_array($ext, $this->readable_extensions)) {
                    $is_indexed = get_post_meta($attachment->ID, '_aiohm_indexed', true);
                    $all_items[] = [
                        'id'     => $attachment->ID,
                        'title'  => $attachment->post_title ?: basename($file_path),
                        'link'   => wp_get_attachment_url($attachment->ID),
                        'type'   => wp_check_filetype($file_path)['type'], // Use wp_check_filetype for MIME type
                        'status' => $is_indexed ? 'Knowledge Base' : 'Ready to Add',
                        'path'   => $file_path // Added path for stats calculation
                    ];
                }
            }
        }
        return $all_items;
    }

    public function find_pending_attachments() {
        // This function will now simply filter the comprehensive list
        $all_supported = $this->find_all_supported_attachments();
        return array_filter($all_supported, function($item) {
            return $item['status'] === 'Ready to Add';
        });
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
                    clean_post_cache($attachment_id); // Clear cache
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
    
    // This private helper function is no longer strictly needed but kept for completeness
    // as its logic is now primarily handled by find_all_supported_attachments() for stats
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
        if (!file_exists($file_path) || !is_readable($file_path)) {
            return null;
        }
        $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        $mime_type = wp_check_filetype($file_path)['type']; // Use wp_check_filetype for MIME type
        $content = '';
        if (in_array($ext, ['json', 'txt', 'csv'])) {
            $content = file_get_contents($file_path);
        } elseif ($ext === 'pdf') {
            try {
                // Instantiate the PDF Parser
                $parser = new Parser();
                // Parse the PDF file
                $pdf = $parser->parseFile($file_path);
                // Extract all text from the PDF
                $content = $pdf->getText();

                // If PDF text extraction results in empty content, fall back to metadata
                if (empty(trim($content))) {
                    $attachment_post = get_post($attachment_id);
                    $content_parts = [];
                    if ($attachment_post) {
                        $content_parts[] = $attachment_post->post_title;
                        $content_parts[] = $attachment_post->post_excerpt; // Caption
                        $content_parts[] = $attachment_post->post_content; // Description
                    }
                    $content = implode("\n\n", array_filter($content_parts));
                    AIOHM_KB_Assistant::log('PDF text extraction failed for ' . basename($file_path) . ', falling back to metadata.', 'warning');
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
            return null;
        }
        return [
            'content' => $content, 'type' => $mime_type, 'title' => basename($file_path),
            'metadata' => ['size' => filesize($file_path), 'path' => $file_path, 'attachment_id' => $attachment_id]
        ];
    }
}