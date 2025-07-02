<?php
/**
 * Upload folder crawler for PDFs and images with OCR support
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
        $this->upload_dir = $upload_info['basedir'];
    }
    
    /**
     * Scan upload folder for supported files
     */
    public function scan_uploads() {
        $files = $this->get_supported_files();
        $processed = array();
        
        AIOHM_KB_Core_Init::log('Found ' . count($files) . ' files to process');
        
        foreach ($files as $file_path) {
            try {
                $file_data = $this->process_file($file_path);
                
                if ($file_data && !empty($file_data['content'])) {
                    // Add to vector database
                    $entry_id = $this->rag_engine->add_entry(
                        $file_data['content'],
                        $file_data['type'],
                        $file_data['title'],
                        $file_data['metadata']
                    );
                    
                    $processed[] = array(
                        'file_path' => $file_path,
                        'file_name' => basename($file_path),
                        'title' => $file_data['title'],
                        'type' => $file_data['type'],
                        'content_length' => strlen($file_data['content']),
                        'entry_id' => $entry_id,
                        'status' => 'success'
                    );
                } else {
                    $processed[] = array(
                        'file_path' => $file_path,
                        'file_name' => basename($file_path),
                        'status' => 'skipped',
                        'reason' => 'No extractable content'
                    );
                }
                
            } catch (Exception $e) {
                AIOHM_KB_Core_Init::log('Error processing file ' . $file_path . ': ' . $e->getMessage(), 'error');
                
                $processed[] = array(
                    'file_path' => $file_path,
                    'file_name' => basename($file_path),
                    'status' => 'error',
                    'error' => $e->getMessage()
                );
            }
        }
        
        return $processed;
    }
    
    /**
     * Scan upload folder in batches with progress tracking
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
                'currently_scanning' => 'User uploads',
                'items_per_minute' => 0,
                'is_complete' => false
            )
        );
        
        // Get batch of files to process
        $files_to_process = array_slice($all_files, $current_offset, $batch_size);
        
        // Process files
        foreach ($files_to_process as $file_path) {
            try {
                $file_data = $this->process_file($file_path);
                
                if ($file_data && !empty($file_data['content'])) {
                    // Add to vector database
                    $entry_id = $this->rag_engine->add_entry(
                        $file_data['content'],
                        $file_data['type'],
                        $file_data['title'],
                        $file_data['metadata']
                    );
                    
                    $results['files'][] = array(
                        'file_path' => $file_path,
                        'file_name' => basename($file_path),
                        'title' => $file_data['title'],
                        'type' => $file_data['type'],
                        'content_length' => strlen($file_data['content']),
                        'entry_id' => $entry_id,
                        'status' => 'success'
                    );
                } else {
                    $results['files'][] = array(
                        'file_path' => $file_path,
                        'file_name' => basename($file_path),
                        'status' => 'skipped',
                        'reason' => 'No extractable content'
                    );
                }
            } catch (Exception $e) {
                AIOHM_KB_Core_Init::log('Error processing file ' . $file_path . ': ' . $e->getMessage(), 'error');
                
                $results['files'][] = array(
                    'file_path' => $file_path,
                    'file_name' => basename($file_path),
                    'status' => 'error',
                    'error' => $e->getMessage()
                );
            }
        }
        
        $results['total_processed'] = count($results['files']);
        
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
     * Get all supported files from upload directory
     */
    private function get_supported_files() {
        $files = array();
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->upload_dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        // List of directories to exclude (plugin directories)
        $excluded_dirs = array(
            'plugins',
            'cache',
            'upgrade',
            'woocommerce_uploads',
            'elementor',
            'backups',
            'wp-content/uploads/cache'
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $extension = strtolower($file->getExtension());
                $path = $file->getPathname();
                
                // Skip files in excluded directories
                $skip = false;
                foreach ($excluded_dirs as $dir) {
                    if (strpos($path, '/' . $dir . '/') !== false) {
                        $skip = true;
                        break;
                    }
                }
                
                if (!$skip && in_array($extension, $this->supported_extensions)) {
                    $files[] = $path;
                }
            }
        }
        
        return $files;
    }
    
    /**
     * Count total number of supported files
     */
    public function count_supported_files() {
        return count($this->get_supported_files());
    }
    
    /**
     * Process individual file
     */
    private function process_file($file_path) {
        $file_info = pathinfo($file_path);
        $extension = strtolower($file_info['extension']);
        $file_name = $file_info['filename'];
        
        $base_metadata = array(
            'file_path' => $file_path,
            'file_name' => basename($file_path),
            'file_size' => filesize($file_path),
            'file_modified' => filemtime($file_path),
            'mime_type' => mime_content_type($file_path)
        );
        
        switch ($extension) {
            case 'pdf':
                return $this->process_pdf($file_path, $file_name, $base_metadata);
            
            case 'doc':
            case 'docx':
                return $this->process_doc($file_path, $file_name, $base_metadata);
                
            case 'txt':
                return $this->process_txt($file_path, $file_name, $base_metadata);
                
            case 'csv':
                return $this->process_csv($file_path, $file_name, $base_metadata);
            
            case 'jpg':
            case 'jpeg':
            case 'png':
            case 'gif':
                return $this->process_image($file_path, $file_name, $base_metadata);
            
            case 'json':
                return $this->process_json($file_path, $file_name, $base_metadata);
            
            default:
                return null;
        }
    }
    
    /**
     * Process PDF files
     */
    private function process_pdf($file_path, $file_name, $metadata) {
        try {
            // Try to extract text using basic PHP methods first
            $content = $this->extract_pdf_text_basic($file_path);
            
            // If basic extraction fails, try alternative methods
            if (empty($content)) {
                $content = $this->extract_pdf_text_alternative($file_path);
            }
            
            if (!empty($content)) {
                // Clean and normalize content
                $content = $this->clean_text($content);
                
                return array(
                    'content' => $content,
                    'type' => 'pdf',
                    'title' => $file_name,
                    'metadata' => array_merge($metadata, array(
                        'extraction_method' => 'pdf_parser',
                        'page_count' => $this->get_pdf_page_count($file_path)
                    ))
                );
            }
            
        } catch (Exception $e) {
            AIOHM_KB_Core_Init::log('PDF processing error: ' . $e->getMessage(), 'error');
        }
        
        return null;
    }
    
    /**
     * Process Word documents
     */
    private function process_doc($file_path, $file_name, $metadata) {
        try {
            // Simple text extraction for DOC/DOCX
            // This is a placeholder - ideally use a library like PHPWord
            $content = '';
            
            if (class_exists('ZipArchive') && $metadata['mime_type'] === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
                // DOCX is a ZIP file, we can extract document.xml
                $zip = new ZipArchive();
                if ($zip->open($file_path) === TRUE) {
                    // Extract the main content
                    if (($index = $zip->locateName('word/document.xml')) !== false) {
                        $content_xml = $zip->getFromIndex($index);
                        // Remove XML tags to get plain text
                        $content = strip_tags($content_xml);
                    }
                    $zip->close();
                }
            }
            
            // If no content extracted, try exec methods if available
            if (empty($content) && function_exists('exec')) {
                // Try using external tools like catdoc or antiword
                exec('catdoc "' . escapeshellarg($file_path) . '" 2>/dev/null', $output);
                if (!empty($output)) {
                    $content = implode("\n", $output);
                }
            }
            
            if (!empty($content)) {
                // Clean and normalize content
                $content = $this->clean_text($content);
                
                return array(
                    'content' => $content,
                    'type' => 'document',
                    'title' => $file_name,
                    'metadata' => $metadata
                );
            }
            
        } catch (Exception $e) {
            AIOHM_KB_Core_Init::log('DOC processing error: ' . $e->getMessage(), 'error');
        }
        
        return null;
    }
    
    /**
     * Process TXT files
     */
    private function process_txt($file_path, $file_name, $metadata) {
        try {
            $content = file_get_contents($file_path);
            
            if (!empty($content)) {
                // Clean and normalize content
                $content = $this->clean_text($content);
                
                return array(
                    'content' => $content,
                    'type' => 'text',
                    'title' => $file_name,
                    'metadata' => $metadata
                );
            }
            
        } catch (Exception $e) {
            AIOHM_KB_Core_Init::log('TXT processing error: ' . $e->getMessage(), 'error');
        }
        
        return null;
    }
    
    /**
     * Process CSV files
     */
    private function process_csv($file_path, $file_name, $metadata) {
        try {
            // Read CSV file
            $csv_data = array();
            if (($handle = fopen($file_path, "r")) !== FALSE) {
                // Get headers
                $headers = fgetcsv($handle);
                
                // Read data
                while (($data = fgetcsv($handle)) !== FALSE) {
                    $row = array();
                    foreach ($data as $key => $value) {
                        $header = isset($headers[$key]) ? $headers[$key] : "Column " . ($key + 1);
                        $row[$header] = $value;
                    }
                    $csv_data[] = $row;
                }
                fclose($handle);
            }
            
            // Convert CSV data to text
            $content = '';
            if (!empty($headers)) {
                $content .= "CSV Headers: " . implode(", ", $headers) . "\n\n";
            }
            
            foreach ($csv_data as $row_index => $row) {
                $content .= "Row " . ($row_index + 1) . ":\n";
                foreach ($row as $header => $value) {
                    $content .= $header . ": " . $value . "\n";
                }
                $content .= "\n";
            }
            
            if (!empty($content)) {
                // Clean and normalize content
                $content = $this->clean_text($content);
                
                return array(
                    'content' => $content,
                    'type' => 'csv',
                    'title' => $file_name,
                    'metadata' => array_merge($metadata, array(
                        'row_count' => count($csv_data),
                        'column_count' => count($headers),
                        'headers' => $headers
                    ))
                );
            }
            
        } catch (Exception $e) {
            AIOHM_KB_Core_Init::log('CSV processing error: ' . $e->getMessage(), 'error');
        }
        
        return null;
    }
    
    /**
     * Basic PDF text extraction
     */
    private function extract_pdf_text_basic($file_path) {
        // Simple PDF text extraction for basic PDFs
        $content = file_get_contents($file_path);
        
        // Extract text between stream objects (very basic)
        if (preg_match_all('/stream\s*\n(.*?)\nendstream/s', $content, $matches)) {
            $text_parts = array();
            
            foreach ($matches[1] as $stream) {
                // Try to decode if it's not compressed
                if (strpos($stream, '/Filter') === false) {
                    // Extract readable text
                    $decoded = preg_replace('/[^\x20-\x7E\n\r\t]/', '', $stream);
                    if (!empty(trim($decoded))) {
                        $text_parts[] = $decoded;
                    }
                }
            }
            
            return implode("\n", $text_parts);
        }
        
        return '';
    }
    
    /**
     * Alternative PDF text extraction using exec if available
     */
    private function extract_pdf_text_alternative($file_path) {
        // Try pdftotext if available
        if (function_exists('exec') && !in_array('exec', explode(',', ini_get('disable_functions')))) {
            $output = array();
            $return_var = 0;
            
            // Try pdftotext command
            exec('pdftotext "' . escapeshellarg($file_path) . '" -', $output, $return_var);
            
            if ($return_var === 0 && !empty($output)) {
                return implode("\n", $output);
            }
        }
        
        return '';
    }
    
    /**
     * Get PDF page count
     */
    private function get_pdf_page_count($file_path) {
        $content = file_get_contents($file_path);
        if (preg_match('/\/Count\s+(\d+)/', $content, $matches)) {
            return intval($matches[1]);
        }
        return 1;
    }
    
    /**
     * Process image files with basic OCR
     */
    private function process_image($file_path, $file_name, $metadata) {
        try {
            // Basic image processing - extract EXIF data and attempt simple OCR
            $image_info = getimagesize($file_path);
            $exif_data = array();
            
            if (function_exists('exif_read_data') && in_array($image_info['mime'], array('image/jpeg', 'image/tiff'))) {
                $exif_data = @exif_read_data($file_path);
            }
            
            // Try basic OCR if Tesseract is available
            $ocr_text = $this->extract_text_from_image($file_path);
            
            // Create description based on available data
            $description_parts = array();
            $description_parts[] = "Image: " . $file_name;
            
            if ($image_info) {
                $description_parts[] = "Dimensions: " . $image_info[0] . "x" . $image_info[1];
                $description_parts[] = "Type: " . $image_info['mime'];
            }
            
            if (!empty($exif_data['ImageDescription'])) {
                $description_parts[] = "Description: " . $exif_data['ImageDescription'];
            }
            
            if (!empty($ocr_text)) {
                $description_parts[] = "Extracted Text: " . $ocr_text;
            }
            
            $content = implode("\n", $description_parts);
            
            return array(
                'content' => $content,
                'type' => 'image',
                'title' => $file_name,
                'metadata' => array_merge($metadata, array(
                    'image_info' => $image_info,
                    'exif_data' => $exif_data,
                    'ocr_text' => $ocr_text,
                    'has_text' => !empty($ocr_text)
                ))
            );
            
        } catch (Exception $e) {
            AIOHM_KB_Core_Init::log('Image processing error: ' . $e->getMessage(), 'error');
        }
        
        return null;
    }
    
    /**
     * Extract text from image using OCR
     */
    private function extract_text_from_image($file_path) {
        // Try Tesseract OCR if available
        if (function_exists('exec') && !in_array('exec', explode(',', ini_get('disable_functions')))) {
            $output = array();
            $return_var = 0;
            
            // Try tesseract command
            exec('tesseract "' . escapeshellarg($file_path) . '" stdout 2>/dev/null', $output, $return_var);
            
            if ($return_var === 0 && !empty($output)) {
                return implode("\n", $output);
            }
        }
        
        return '';
    }
    
    /**
     * Process JSON files
     */
    private function process_json($file_path, $file_name, $metadata) {
        try {
            $json_content = file_get_contents($file_path);
            $json_data = json_decode($json_content, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                // Convert JSON to readable text
                $content = $this->json_to_text($json_data, $file_name);
                
                return array(
                    'content' => $content,
                    'type' => 'json',
                    'title' => $file_name,
                    'metadata' => array_merge($metadata, array(
                        'json_structure' => $this->analyze_json_structure($json_data),
                        'key_count' => $this->count_json_keys($json_data)
                    ))
                );
            }
            
        } catch (Exception $e) {
            AIOHM_KB_Core_Init::log('JSON processing error: ' . $e->getMessage(), 'error');
        }
        
        return null;
    }
    
    /**
     * Convert JSON data to readable text
     */
    private function json_to_text($data, $file_name, $prefix = '') {
        $text_parts = array();
        
        if (empty($prefix)) {
            $text_parts[] = "JSON File: " . $file_name;
        }
        
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $current_prefix = empty($prefix) ? $key : $prefix . '.' . $key;
                
                if (is_array($value) || is_object($value)) {
                    $text_parts[] = $current_prefix . ":";
                    $text_parts[] = $this->json_to_text($value, $file_name, $current_prefix);
                } else {
                    $text_parts[] = $current_prefix . ": " . $value;
                }
            }
        } elseif (is_object($data)) {
            foreach ($data as $key => $value) {
                $current_prefix = empty($prefix) ? $key : $prefix . '.' . $key;
                
                if (is_array($value) || is_object($value)) {
                    $text_parts[] = $current_prefix . ":";
                    $text_parts[] = $this->json_to_text($value, $file_name, $current_prefix);
                } else {
                    $text_parts[] = $current_prefix . ": " . $value;
                }
            }
        }
        
        return implode("\n", $text_parts);
    }
    
    /**
     * Analyze JSON structure
     */
    private function analyze_json_structure($data) {
        if (is_array($data)) {
            return array(
                'type' => 'array',
                'length' => count($data),
                'keys' => array_keys($data)
            );
        } elseif (is_object($data)) {
            return array(
                'type' => 'object',
                'properties' => array_keys((array)$data)
            );
        }
        
        return array('type' => gettype($data));
    }
    
    /**
     * Count JSON keys recursively
     */
    private function count_json_keys($data) {
        $count = 0;
        
        if (is_array($data) || is_object($data)) {
            foreach ($data as $key => $value) {
                $count++;
                if (is_array($value) || is_object($value)) {
                    $count += $this->count_json_keys($value);
                }
            }
        }
        
        return $count;
    }
    
    /**
     * Clean and normalize text
     */
    private function clean_text($text) {
        // Remove excessive whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Remove non-printable characters except newlines and tabs
        $text = preg_replace('/[^\x20-\x7E\n\r\t]/', '', $text);
        
        // Normalize line endings
        $text = str_replace(array("\r\n", "\r"), "\n", $text);
        
        return trim($text);
    }
    
    /**
     * Get upload scan statistics
     */
    public function get_scan_stats() {
        $files = $this->get_supported_files();
        
        $stats = array(
            'total_files' => count($files),
            'by_type' => array(),
            'total_size' => 0
        );
        
        foreach ($files as $file_path) {
            $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
            
            if (!isset($stats['by_type'][$extension])) {
                $stats['by_type'][$extension] = array(
                    'count' => 0,
                    'size' => 0
                );
            }
            
            $file_size = filesize($file_path);
            $stats['by_type'][$extension]['count']++;
            $stats['by_type'][$extension]['size'] += $file_size;
            $stats['total_size'] += $file_size;
        }
        
        return $stats;
    }
}
