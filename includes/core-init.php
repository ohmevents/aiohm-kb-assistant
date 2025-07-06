<?php
/**
 * Core initialization and configuration.
 * This version adds the restore AJAX handler and fixes the reset nonce.
 */
if (!defined('ABSPATH')) exit;

class AIOHM_KB_Core_Init {
    
    public static function init() {
        add_action('wp_ajax_aiohm_progressive_scan', array(__CLASS__, 'handle_progressive_scan_ajax'));
        add_action('wp_ajax_aiohm_check_api_key', array(__CLASS__, 'handle_check_api_key_ajax'));
        add_action('wp_ajax_aiohm_save_personal_kb', array(__CLASS__, 'handle_save_personal_kb_ajax'));
        add_action('wp_ajax_aiohm_export_kb', array(__CLASS__, 'handle_export_kb_ajax'));
        add_action('wp_ajax_aiohm_reset_kb', array(__CLASS__, 'handle_reset_kb_ajax'));
        add_action('wp_ajax_aiohm_brand_assistant_chat', array(__CLASS__, 'handle_brand_assistant_ajax'));
        add_action('wp_ajax_aiohm_toggle_kb_scope', array(__CLASS__, 'handle_toggle_kb_scope_ajax'));
        add_action('wp_ajax_aiohm_restore_kb', array(__CLASS__, 'handle_restore_kb_ajax')); // ** NEW **
    }

    // ... (keep handle_progressive_scan_ajax, handle_check_api_key_ajax, etc.) ...
    public static function handle_progressive_scan_ajax() { /* ... */ }
    public static function handle_check_api_key_ajax() { /* ... */ }
    public static function handle_save_personal_kb_ajax() { /* ... */ }
    public static function handle_export_kb_ajax() { /* ... */ }
    public static function handle_brand_assistant_ajax() { /* ... */ }
    public static function handle_toggle_kb_scope_ajax() { /* ... */ }

    
    /**
     * Handles the AJAX request to reset the entire knowledge base.
     */
    public static function handle_reset_kb_ajax() {
        // ** FIXED: Standardized the nonce for this page **
        if (!wp_verify_nonce($_POST['nonce'], 'aiohm_admin_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Security check failed.']);
        }
        global $wpdb;
        // ... (rest of the function is the same) ...
        $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_aiohm_indexed'");
        $table_name = $wpdb->prefix . 'aiohm_vector_entries';
        $wpdb->query("TRUNCATE TABLE {$table_name}");
        delete_transient('aiohm_pending_items_website_' . get_current_user_id());
        delete_transient('aiohm_pending_items_uploads_' . get_current_user_id());
        wp_send_json_success(['message' => 'The knowledge base has been successfully reset.']);
    }

    /**
     * Handles the AJAX request to restore the knowledge base from a JSON file.
     */
    public static function handle_restore_kb_ajax() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'aiohm_admin_nonce')) {
            wp_send_json_error(['message' => 'Security check failed.']);
        }

        if (!isset($_POST['json_data']) || empty($_POST['json_data'])) {
            wp_send_json_error(['message' => 'No data provided for restore.']);
        }

        // We need to use stripslashes because wp_remote_post or jQuery might add them
        $json_data = stripslashes($_POST['json_data']);

        try {
            $rag_engine = new AIOHM_KB_RAG_Engine();
            $count = $rag_engine->import_knowledge_base($json_data);
            wp_send_json_success(['message' => $count . ' entries have been successfully restored. The page will now reload.']);
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'Restore failed: ' . $e->getMessage()]);
        }
    }
}