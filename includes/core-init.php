<?php
/**
 * Core initialization and configuration.
 * This version includes more robust AJAX handling for the scan page.
 */
if (!defined('ABSPATH')) exit;

class AIOHM_KB_Core_Init {
    
    public static function init() {
        // ... (all other add_action calls remain the same) ...
        add_action('wp_ajax_aiohm_progressive_scan', array(__CLASS__, 'handle_progressive_scan_ajax'));
        add_action('wp_ajax_aiohm_check_api_key', array(__CLASS__, 'handle_check_api_key_ajax'));
        add_action('wp_ajax_aiohm_save_personal_kb', array(__CLASS__, 'handle_save_personal_kb_ajax'));
        add_action('wp_ajax_aiohm_export_kb', array(__CLASS__, 'handle_export_kb_ajax'));
        add_action('wp_ajax_aiohm_reset_kb', array(__CLASS__, 'handle_reset_kb_ajax'));
        add_action('wp_ajax_aiohm_brand_assistant_chat', array(__CLASS__, 'handle_brand_assistant_ajax'));
        add_action('wp_ajax_aiohm_toggle_kb_scope', array(__CLASS__, 'handle_toggle_kb_scope_ajax'));
        add_action('wp_ajax_aiohm_restore_kb', array(__CLASS__, 'handle_restore_kb_ajax'));
    }

    public static function handle_progressive_scan_ajax() {
        if (!wp_verify_nonce($_POST['nonce'], 'aiohm_admin_nonce') || !current_user_can('manage_options')) {
            wp_die('Security check failed');
        }

        try {
            $scan_type = sanitize_text_field($_POST['scan_type']);

            switch ($scan_type) {
                case 'website_find':
                    $crawler = new AIOHM_KB_Site_Crawler();
                    $all_items = $crawler->find_all_content();
                    wp_send_json_success(['items' => $all_items]);
                    break;

                case 'website_add':
                    $item_ids = isset($_POST['item_ids']) ? array_map('intval', $_POST['item_ids']) : [];
                    if (empty($item_ids)) throw new Exception('No item IDs provided.');
                    
                    $crawler = new AIOHM_KB_Site_Crawler();
                    $results = $crawler->add_items_to_kb($item_ids);
                    
                    // **THE FIX:** After adding, re-fetch the complete list to get updated statuses.
                    $all_items = $crawler->find_all_content();
                    wp_send_json_success(['processed_items' => $results, 'all_items' => $all_items]);
                    break;
                
                case 'uploads_find':
                    $crawler = new AIOHM_KB_Uploads_Crawler();
                    $pending_files = $crawler->find_pending_attachments();
                    wp_send_json_success(['items' => $pending_files]);
                    break;

                case 'uploads_add':
                    $item_ids = isset($_POST['item_ids']) ? array_map('intval', $_POST['item_ids']) : [];
                    if (empty($item_ids)) throw new Exception('No item IDs provided.');

                    $crawler = new AIOHM_KB_Uploads_Crawler();
                    $results = $crawler->add_attachments_to_kb($item_ids);
                    
                    // **THE FIX:** After adding, re-fetch the list of pending uploads.
                    $pending_files = $crawler->find_pending_attachments();
                    wp_send_json_success(['processed_items' => $results, 'items' => $pending_files]);
                    break;

                default:
                    throw new Exception('Invalid scan type specified.');
            }
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'Scan failed: ' . $e->getMessage()]);
        }
    }
    
    // ... (all other handle_..._ajax functions remain the same) ...
}