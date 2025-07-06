<?php
/**
 * Core initialization and configuration.
 * This version adds AJAX handling for the uploads scanner.
 */
if (!defined('ABSPATH')) exit;

class AIOHM_KB_Core_Init {
    
    public static function init() {
        add_action('wp_ajax_aiohm_progressive_scan', array(__CLASS__, 'handle_progressive_scan_ajax'));
        // ... (keep all other existing add_action calls)
    }

    public static function handle_progressive_scan_ajax() {
        if (!wp_verify_nonce($_POST['nonce'], 'aiohm_admin_nonce') || !current_user_can('manage_options')) {
            wp_die('Security check failed');
        }

        try {
            $scan_type = sanitize_text_field($_POST['scan_type']);
            $user_id = get_current_user_id();

            switch ($scan_type) {
                case 'website_find':
                    // ... (this case remains the same)
                    break;

                case 'website_add':
                    // ... (this case remains the same)
                    break;
                
                // ** NEW: Case for finding pending file uploads **
                case 'uploads_find':
                    $crawler = new AIOHM_KB_Uploads_Crawler();
                    $pending_files = $crawler->find_pending_attachments();
                    wp_send_json_success(['items' => $pending_files]);
                    break;

                // ** NEW: Case for adding selected file uploads to the KB **
                case 'uploads_add':
                    $item_ids = isset($_POST['item_ids']) ? array_map('intval', $_POST['item_ids']) : [];
                    if (empty($item_ids)) throw new Exception('No item IDs provided.');

                    $crawler = new AIOHM_KB_Uploads_Crawler();
                    $results = $crawler->add_attachments_to_kb($item_ids);
                    
                    // You might want to return new upload stats here in the future
                    wp_send_json_success(['processed_items' => $results]);
                    break;

                default:
                    throw new Exception('Invalid scan type specified.');
            }
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'Scan failed: ' . $e->getMessage()]);
        }
    }
    
    // ... (keep all other existing functions) ...
}