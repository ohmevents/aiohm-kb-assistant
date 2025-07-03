<?php
/**
 * Upload folder crawler for PDFs and images with OCR support
 *
 * @package AIOHM_KB_Assistant
 * @author ohmevents
 * @version 1.0.1
 * @created 2025-07-02 13:30:33
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AIOHM_KB_Uploads_Crawler {
    
    private $rag_engine;
    private $upload_dir;
    private $supported_extensions = array('pdf', 'jpg', 'jpeg', 'png', 'gif', 'json', 'doc', 'docx', 'txt', 'csv');
    
    public function __construct() {
        $this->rag_engine = new AIOHM_KB_RAG_Engine();
        $upload_info = wp_upload_dir();
        $this->upload_dir = isset($upload_info['basedir']) ? $upload_info['basedir'] : ABSPATH;
    }

    /**
     * Scan uploads with progress tracking
     *
     * @param int $batch_size Number of files to process per batch
     * @param int $current_offset Current offset in file list
     * @return array Progress information and processed files
     */
    public function scan_uploads_with_progress($batch_size = 5, $current_offset = 0) {
        $start_time = microtime(true);
        
        // Get all supported files
        $all_files = $this->get_supported_files();
        $total_items = count($all_files);
        
        $results = array(
            'files' => array(),
            'total_processed' => 0,
            'progress' => array(
                'current_offset' => $current_offset,
                'total_items' => $total_items,
                'percentage' => 0,
                'estimated_time_remaining' => 0,
                'currently_scanning' => 'Upload files',
                'items_per_minute' => 0,
                'is_complete' => false
            )
        );
        
        try {
            // Get batch of files to process
            $files_to_process = array_slice($all_files, $current_offset, $batch_size);
            
            foreach ($files_to_process as $file_path) {
                $file_data = $this->process_file($file_path);
                if ($file_data && !empty($file_data['content'])) {
                    $entry_id = $this->rag_engine->add_entry(
                        $file_data['content'],
                        $file_data['type'],
                        $file_data['title'],
                        $file_data['metadata']
                    );
                    
                    $results['files'][] = array(
                        'file_path' => $file_path,
                        'file_name' => basename($file_path),
                        'type' => $file_data['type'],
                        'status' => 'success',
                        'entry_id' => $entry_id
                    );
                }
            }
            
            // Update progress information
            $results['total_processed'] = count($results['files']);
            $new_offset = $current_offset + $results['total_processed'];
            $results['progress']['current_offset'] = $new_offset;
            $results['progress']['percentage'] = ($total_items > 0) ? round(($new_offset / $total_items) * 100, 1) : 100;
            $results['progress']['is_complete'] = ($new_offset >= $total_items);
            
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
            
            return $results;
            
        } catch (Exception $e) {
            // Log the error without breaking menu functionality
            error_log('Upload scan error: ' . $e->getMessage());
            return array(
                'error' => true,
                'message' => 'An error occurred while scanning uploads.'
            );
        }
    }

    /**
     * Get supported files from the upload directory
     *
     * @return array List of supported file paths
     */
    private function get_supported_files() {
        $files = array();
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->upload_dir));
        
        foreach ($iterator as $file) {
            if ($file->isFile() && in_array(strtolower($file->getExtension()), $this->supported_extensions)) {
                $files[] = $file->getPathname();
            }
        }
        
        return $files;
    }

    /**
     * Process a single file
     *
     * @param string $file_path Path to the file
     * @return array|null File data or null if processing failed
     */
    private function process_file($file_path) {
        if (!file_exists($file_path)) {
            error_log('File not found: ' . $file_path);
            return null;
        }
        
        // Simulate file processing logic
        return array(
            'content' => file_get_contents($file_path),
            'type' => mime_content_type($file_path),
            'title' => basename($file_path),
            'metadata' => array('size' => filesize($file_path))
        );
    }

    /**
     * Format time remaining into human-readable string
     *
     * @param int $seconds Time in seconds
     * @return string Formatted time
     */
    private function format_time_remaining($seconds) {
        $minutes = floor($seconds / 60);
        $seconds = $seconds % 60;
        return sprintf('%d minutes, %d seconds', $minutes, $seconds);
    }
}