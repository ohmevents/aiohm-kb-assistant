<?php
/**
 * Core initialization and configuration.
 * This is the complete and final version of this file.
 */
if (!defined('ABSPATH')) exit;

class AIOHM_KB_Core_Init {
    
    /**
     * Initialize core functionality by adding all necessary hooks.
     */
    public static function init() {
        add_action('wp_ajax_aiohm_progressive_scan', array(__CLASS__, 'handle_progressive_scan_ajax'));
        add_action('wp_ajax_aiohm_check_api_key', array(__CLASS__, 'handle_check_api_key_ajax'));
        add_action('wp_ajax_aiohm_save_personal_kb', array(__CLASS__, 'handle_save_personal_kb_ajax'));
        add_action('wp_ajax_aiohm_export_kb', array(__CLASS__, 'handle_export_kb_ajax'));
        add_action('wp_ajax_aiohm_reset_kb', array(__CLASS__, 'handle_reset_kb_ajax'));
    }

    /**
     * Handles all progressive scan AJAX requests for finding and adding content.
     */
    public static function handle_progressive_scan_ajax() {
        if (!wp_verify_nonce($_POST['nonce'], 'aiohm_admin_nonce') || !current_user_can('manage_options')) {
            wp_die('Security check failed');
        }

        try {
            $scan_type = sanitize_text_field($_POST['scan_type']);
            $user_id = get_current_user_id();

            switch ($scan_type) {
                case 'website_find':
                    $transient_key = 'aiohm_pending_items_website_' . $user_id;
                    delete_transient($transient_key);
                    $crawler = new AIOHM_KB_Site_Crawler();
                    $all_items = $crawler->find_all_content();
                    set_transient($transient_key, $all_items, 12 * HOUR_IN_SECONDS);
                    wp_send_json_success(['items' => $all_items]);
                    break;

                case 'website_add':
                    $transient_key = 'aiohm_pending_items_website_' . $user_id;
                    $item_ids = isset($_POST['item_ids']) ? array_map('intval', $_POST['item_ids']) : [];
                    if (empty($item_ids)) throw new Exception('No item IDs provided.');
                    
                    $crawler = new AIOHM_KB_Site_Crawler();
                    $results = $crawler->add_items_to_kb($item_ids);
                    
                    $new_stats = $crawler->get_scan_stats(); 

                    $current_items = get_transient($transient_key);
                    if ($current_items) {
                        $successful_ids = wp_list_pluck(array_filter($results, fn($r) => $r['status'] === 'success'), 'id');
                        if (!empty($successful_ids)) {
                            foreach ($current_items as &$item) {
                                if (in_array($item['id'], $successful_ids)) {
                                    $item['status'] = 'In Knowledge Base';
                                }
                            }
                            set_transient($transient_key, $current_items, 12 * HOUR_IN_SECONDS);
                        }
                    }

                    wp_send_json_success([
                        'processed_items' => $results,
                        'new_stats'       => $new_stats
                    ]);
                    break;
                
                case 'uploads':
                    $crawler = new AIOHM_KB_Uploads_Crawler();
                    $batch_size = intval($_POST['batch_size'] ?? 5);
                    $current_offset = intval($_POST['current_offset'] ?? 0);
                    $results = $crawler->scan_uploads_with_progress($batch_size, $current_offset);
                    wp_send_json_success($results);
                    break;

                default:
                    throw new Exception('Invalid scan type specified.');
            }
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'Scan failed: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Handles the AJAX request to check the OpenAI API key.
     */
    public static function handle_check_api_key_ajax() {
        if (!wp_verify_nonce($_POST['nonce'], 'aiohm_admin_nonce') || !current_user_can('manage_options')) { wp_send_json_error(['message' => 'Security check failed.']); }
        $api_key = sanitize_text_field($_POST['api_key']);
        $ai_client = new AIOHM_KB_AI_GPT_Client(['openai_api_key' => $api_key]);
        $result = $ai_client->test_api_connection();
        if ($result['success']) { wp_send_json_success(['message' => 'Connection successful!']); } 
        else { wp_send_json_error(['message' => 'Connection failed: ' . ($result['error'] ?? 'Unknown error.')]); }
    }
    
    /**
     * Handles saving the Brand Soul answers to the user's personal KB.
     */
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

    /**
     * Handles the AJAX request to export the knowledge base.
     */
    public static function handle_export_kb_ajax() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'aiohm_admin_nonce')) { wp_send_json_error(['message' => 'Permission denied.']); }
        $rag_engine = new AIOHM_KB_RAG_Engine();
        $json_data = $rag_engine->export_knowledge_base();
        wp_send_json_success(['filename' => 'aiohm-knowledge-base-' . date('Y-m-d') . '.json', 'data' => $json_data]);
    }
    
    /**
     * Handles the AJAX request to reset the entire knowledge base.
     */
    public static function handle_reset_kb_ajax() {
        if (!wp_verify_nonce($_POST['nonce'], 'aiohm_reset_kb_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Security check failed.']);
        }
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_aiohm_indexed'");
        $table_name = $wpdb->prefix . 'aiohm_vector_entries';
        $wpdb->query("TRUNCATE TABLE {$table_name}");
        delete_transient('aiohm_pending_items_website_' . get_current_user_id());
        delete_transient('aiohm_pending_items_uploads_' . get_current_user_id());
        wp_send_json_success(['message' => 'The knowledge base has been successfully reset.']);
    }
}