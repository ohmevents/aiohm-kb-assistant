<?php
defined('ABSPATH') || exit;

/**
 * Recursively scan uploads folder for supported files + OCR + PDF text
 */
function aiohm_kb_scan_uploads_for_documents() {
    $upload_dir = wp_upload_dir();
    $base_path  = $upload_dir['basedir'];

    $supported_text_ext  = ['pdf', 'docx', 'txt', 'json', 'csv', 'md'];
    $supported_image_ext = ['jpg', 'jpeg', 'png'];
    $results = [];

    $options = get_option(AIOHM_OPTIONS_KEY);
    $enable_ocr = !empty($options['enable_ocr']);
    $has_pdftotext = !empty(shell_exec("which pdftotext"));

    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base_path));

    foreach ($rii as $file) {
        if ($file->isDir()) continue;

        $ext  = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
        $path = $file->getPathname();
        $url  = str_replace($base_path, $upload_dir['baseurl'], $path);
        $data = [
            'filename' => $file->getFilename(),
            'path'     => $path,
            'url'      => $url,
            'type'     => 'unknown',
            'content'  => ''
        ];

        // Text-based document
        if (in_array($ext, $supported_text_ext)) {
            $data['type'] = 'document';

            // PDF text extraction (via CLI)
            if ($ext === 'pdf' && $has_pdftotext) {
                $temp_output = tempnam(sys_get_temp_dir(), 'pdf_');
                $cmd = 'pdftotext ' . escapeshellarg($path) . ' ' . escapeshellarg($temp_output);
                shell_exec($cmd);
                $pdf_text = @file_get_contents($temp_output);
                unlink($temp_output);

                if (!empty($pdf_text)) {
                    $data['content'] = trim($pdf_text);
                }
            }
        }

        // OCR image
        if ($enable_ocr && in_array($ext, $supported_image_ext)) {
            $data['type'] = 'image';

            $ocr_text = shell_exec("tesseract " . escapeshellarg($path) . " stdout 2>/dev/null");
            if (!empty($ocr_text)) {
                $data['content'] = trim($ocr_text);
            }
        }

        // Save only if we have content or it's a valid file
        if (!empty($data['content']) || $data['type'] !== 'unknown') {
            $results[] = $data;
        }
    }

    return $results;
}
