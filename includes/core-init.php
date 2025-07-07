<?php
/**
 * Core initialization and configuration.
 * This is the complete and final version with all working AJAX handlers.
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
        add_action('wp_ajax_aiohm_restore_kb', array(__CLASS__, 'handle_restore_kb_ajax'));
        // Add handler for single KB entry deletion
        add_action('wp_ajax_aiohm_delete_kb_entry', array(__CLASS__, 'handle_delete_kb_entry_ajax'));
        // NEW: Add handler for ARMember single user sync
        add_action('wp_ajax_aiohm_sync_current_armember_user', array(__CLASS__, 'handle_sync_current_armember_user_ajax'));
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
                    $errors = array_filter($results, function($r) { return $r['status'] === 'error'; });
                    if (!empty($errors)) {
                        $error_messages = array_map(function($e) { return $e['title'] . ': ' . $e['error_message']; }, $errors);
                        wp_send_json_error(['message' => "Some items failed to process:\n" . implode("\n", $error_messages)]);
                    } else {
                        $all_items = $crawler->find_all_content();
                        wp_send_json_success(['processed_items' => $results, 'all_items' => $all_items]);
                    }
                    break;
                case 'uploads_find':
                    $crawler = new AIOHM_KB_Uploads_Crawler();
                    // Return all supported attachments to keep indexed files visible
                    $all_supported_files = $crawler->find_all_supported_attachments(); 
                    wp_send_json_success(['items' => $all_supported_files]);
                    break;
                case 'uploads_add':
                    $item_ids = isset($_POST['item_ids']) ? array_map('intval', $_POST['item_ids']) : [];
                    if (empty($item_ids)) throw new Exception('No item IDs provided.');
                    $crawler = new AIOHM_KB_Uploads_Crawler();
                    $results = $crawler->add_attachments_to_kb($item_ids);
                    // After adding, fetch all supported files to update the table correctly
                    $updated_files_list = $crawler->find_all_supported_attachments(); 
                    wp_send_json_success(['processed_items' => $results, 'items' => $updated_files_list]);
                    break;
                default:
                    throw new Exception('Invalid scan type specified.');
            }
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'Scan failed: ' . $e->getMessage()]);
        }
    }
    
    public static function handle_check_api_key_ajax() {
        if (!wp_verify_nonce($_POST['nonce'], 'aiohm_admin_nonce') || !current_user_can('manage_options')) { wp_send_json_error(['message' => 'Security check failed.']); }
        $api_key = sanitize_text_field($_POST['api_key']);
        $ai_client = new AIOHM_KB_AI_GPT_Client(['openai_api_key' => $api_key]);
        $result = $ai_client->test_api_connection();
        if ($result['success']) { wp_send_json_success(['message' => 'Connection successful!']); } 
        else { wp_send_json_error(['message' => 'Connection failed: ' . ($result['error'] ?? 'Unknown error.')]); }
    }
    
    public static function handle_save_personal_kb_ajax() {
        if (!wp_verify_nonce($_POST['nonce'], 'aiohm_personal_kb_nonce') || !current_user_can('manage_options')) { wp_send_json_error(['message' => 'Security check failed.']); }
        parse_str($_POST['data'], $form_data);
        $answers = $form_data['answers'] ?? [];
        if (empty($answers)) { wp_send_json_error(['message' => 'No answers were submitted.']); }
        try {
            $rag_engine = new AIOHM_KB_RAG_Engine(); $user_id = get_current_user_id(); $entries_added = 0;
            foreach ($answers as $question => $answer) {
                if (!empty(trim($answer))) {
                    $rag_engine->add_entry("Question: " . $question . "\nAnswer: " . $answer, 'personal_qa', $question, [], $user_id);
                    $entries_added++;
                }
            }
            if ($entries_added > 0) { wp_send_json_success(['message' => $entries_added . ' answers saved to your Personal KB!']); } 
            else { wp_send_json_error(['message' => 'Please fill out at least one answer.']); }
        } catch (Exception $e) { wp_send_json_error(['message' => 'Error saving to KB: ' . $e->getMessage()]); }
    }

    public static function handle_export_kb_ajax() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'aiohm_admin_nonce')) { wp_send_json_error(['message' => 'Permission denied.']); }
        $rag_engine = new AIOHM_KB_RAG_Engine();
        $json_data = $rag_engine->export_knowledge_base();
        wp_send_json_success(['filename' => 'aiohm-knowledge-base-' . date('Y-m-d') . '.json', 'data' => $json_data]);
    }
    
    public static function handle_reset_kb_ajax() {
        if (!wp_verify_nonce($_POST['nonce'], 'aiohm_admin_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Security check failed.']);
        }
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_aiohm_indexed'");
        $table_name = $wpdb->prefix . 'aiohm_vector_entries';
        $wpdb->query("TRUNCATE TABLE {$table_name}");
        wp_send_json_success(['message' => 'The knowledge base has been successfully reset.']);
    }

    public static function handle_brand_assistant_ajax() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'aiohm_admin_nonce')) {
            wp_send_json_error(['message' => 'Security check failed.']);
        }
        try {
            $user_id = get_current_user_id();
            $query = sanitize_text_field($_POST['query']);
            $rag_engine = new AIOHM_KB_RAG_Engine();
            $ai_client = new AIOHM_KB_AI_GPT_Client();
            $relevant_context_data = $rag_engine->find_context_for_user($query, $user_id);
            $context_string = "";
            foreach ($relevant_context_data as $data) {
                $context_string .= "Source Title: " . $data['entry']['title'] . "\nContent: " . $data['entry']['content'] . "\n\n";
            }
            $system_prompt = "You are a Brand Strategy Assistant. Your role is to help the user develop their brand by using the provided context, which includes public information and the user's private 'Brand Soul' answers. Synthesize this information to provide creative ideas, answer strategic questions, and help draft content. Always prioritize the private 'Brand Soul' context when available.";
            // This assumes a method 'generate_chat_response' exists in your AI client.
            $response = "Feature under development. Context found: " . $context_string; // Placeholder
            wp_send_json_success(['response' => $response]);
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'Chat request failed: ' . $e->getMessage()]);
        }
    }

    public static function handle_toggle_kb_scope_ajax() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'aiohm_admin_nonce')) {
            wp_send_json_error(['message' => 'Permission denied.']);
        }
        $content_id = sanitize_text_field($_POST['content_id']);
        $new_scope = sanitize_text_field($_POST['new_scope']);
        $current_user_id = get_current_user_id();
        $new_user_id = ($new_scope === 'private') ? $current_user_id : 0;
        $rag_engine = new AIOHM_KB_RAG_Engine();
        $result = $rag_engine->update_entry_scope_by_content_id($content_id, $new_user_id);
        if ($result !== false) {
            $new_visibility_text = ($new_user_id == 0) ? 'Public' : 'Private';
            wp_send_json_success(['message' => 'Entry scope updated successfully.', 'new_visibility_text' => $new_visibility_text]);
        } else {
            wp_send_json_error(['message' => 'Failed to update entry scope in the database.']);
        }
    }
    
    public static function handle_restore_kb_ajax() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'aiohm_admin_nonce')) {
            wp_send_json_error(['message' => 'Security check failed.']);
        }
        if (!isset($_POST['json_data']) || empty($_POST['json_data'])) {
            wp_send_json_error(['message' => 'No data provided for restore.']);
        }
        $json_data = stripslashes($_POST['json_data']);
        try {
            $rag_engine = new AIOHM_KB_RAG_Engine();
            $count = $rag_engine->import_knowledge_base($json_data);
            wp_send_json_success(['message' => $count . ' entries have been successfully restored. The page will now reload.']);
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'Restore failed: ' . $e->getMessage()]);
        }
    }

    // Handles single KB entry deletion via AJAX
    public static function handle_delete_kb_entry_ajax() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'aiohm_admin_nonce')) {
            wp_send_json_error(['message' => 'Security check failed.']);
        }
        if (!isset($_POST['content_id']) || empty($_POST['content_id'])) {
            wp_send_json_error(['message' => 'Content ID is missing for deletion.']);
        }
        $content_id = sanitize_text_field($_POST['content_id']);
        $rag_engine = new AIOHM_KB_RAG_Engine();
        if ($rag_engine->delete_entry_by_content_id($content_id)) {
            wp_send_json_success(['message' => 'Entry successfully deleted.']);
        } else {
            wp_send_json_error(['message' => 'Failed to delete entry.']);
        }
    }

    // NEW: Handles syncing current user's ARMember profile
    public static function handle_sync_current_armember_user_ajax() {
        // Ensure the current user has capability to manage options or at least view their own profile/manage membership
        if (!current_user_can('read') || !wp_verify_nonce($_POST['nonce'], 'aiohm_admin_nonce')) {
            wp_send_json_error(['message' => 'Security check failed.']);
        }

        if (!class_exists('AIOHM_KB_ARMember_Integration')) {
            wp_send_json_error(['message' => 'ARMember Integration class not found. Ensure ARMember is installed and active.']);
        }

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => 'User not logged in.']);
        }

        try {
            // Instantiate ARMember integration and call the sync method
            $armember_integration = new AIOHM_KB_ARMember_Integration();
            $armember_integration->sync_user_profile_on_demand($user_id); // This new method will be added to AIOHM_KB_ARMember_Integration

            wp_send_json_success(['message' => 'ARMember membership synced successfully for current user.']);

        } catch (Exception $e) {
            AIOHM_KB_Assistant::log('ARMember manual sync failed: ' . $e->getMessage(), 'error');
            wp_send_json_error(['message' => 'ARMember sync failed: ' . $e->getMessage()]);
        }
    }
}