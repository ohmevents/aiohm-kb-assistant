<?php
/**
 * Core initialization and configuration.
 * v1.1.14 - Fixes a fatal error in the private chat AJAX handler.
 */
if (!defined('ABSPATH')) exit;

class AIOHM_KB_Core_Init {

    public static function init() {
        // --- Admin & Scanning Actions ---
        add_action('wp_ajax_aiohm_progressive_scan', array(__CLASS__, 'handle_progressive_scan_ajax'));
        add_action('wp_ajax_aiohm_check_api_key', array(__CLASS__, 'handle_check_api_key_ajax'));
        
        // --- Knowledge Base Management Actions ---
        add_action('wp_ajax_aiohm_export_kb', array(__CLASS__, 'handle_export_kb_ajax'));
        add_action('wp_ajax_aiohm_reset_kb', array(__CLASS__, 'handle_reset_kb_ajax'));
        add_action('wp_ajax_aiohm_toggle_kb_scope', array(__CLASS__, 'handle_toggle_kb_scope_ajax'));
        add_action('wp_ajax_aiohm_restore_kb', array(__CLASS__, 'handle_restore_kb_ajax'));
        add_action('wp_ajax_aiohm_delete_kb_entry', array(__CLASS__, 'handle_delete_kb_entry_ajax'));

        // --- Brand Soul Actions ---
        add_action('wp_ajax_aiohm_save_brand_soul', array(__CLASS__, 'handle_save_brand_soul_ajax'));
        add_action('wp_ajax_aiohm_add_brand_soul_to_kb', array(__CLASS__, 'handle_add_brand_soul_to_kb_ajax'));
        add_action('admin_init', array(__CLASS__, 'handle_pdf_download'));

        // --- Mirror Mode (Public Chat) Actions ---
        add_action('wp_ajax_aiohm_save_mirror_mode_settings', array(__CLASS__, 'handle_save_mirror_mode_settings_ajax'));
        add_action('wp_ajax_aiohm_generate_mirror_mode_qa', array(__CLASS__, 'handle_generate_mirror_mode_qa_ajax'));
        add_action('wp_ajax_aiohm_test_mirror_mode_chat', array(__CLASS__, 'handle_test_mirror_mode_chat_ajax'));

        // --- Muse Mode (Private Assistant) Actions ---
        add_action('wp_ajax_aiohm_save_muse_mode_settings', array(__CLASS__, 'handle_save_muse_mode_settings_ajax'));
        
        add_action('wp_ajax_aiohm_private_chat', array(__CLASS__, 'handle_private_assistant_chat_ajax'));

        add_action('wp_ajax_aiohm_test_muse_mode_chat', array(__CLASS__, 'handle_test_muse_mode_chat_ajax'));
        add_action('wp_ajax_aiohm_load_history', array(__CLASS__, 'handle_load_history_ajax'));
        add_action('wp_ajax_aiohm_load_conversation', array(__CLASS__, 'handle_get_conversation_history_ajax'));
        
        // --- Project & Notes Actions ---
        add_action('wp_ajax_aiohm_get_projects', array(__CLASS__, 'handle_get_projects_ajax'));
        add_action('wp_ajax_aiohm_create_project', array(__CLASS__, 'handle_create_project_ajax'));
        add_action('wp_ajax_aiohm_save_project_notes', array(__CLASS__, 'handle_save_project_notes_ajax'));
        add_action('wp_ajax_aiohm_load_project_notes', array(__CLASS__, 'handle_load_project_notes_ajax'));
        add_action('wp_ajax_aiohm_delete_project', array(__CLASS__, 'handle_delete_project_ajax'));
        add_action('wp_ajax_aiohm_delete_conversation', array(__CLASS__, 'handle_delete_conversation_ajax'));
        
        // --- (Legacy or alternative method) Live URL Scanning ---
        add_action('wp_ajax_aiohm_scan_url_live', array(__CLASS__, 'handle_scan_url_live_ajax'));

        // --- Frontend Actions (Shortcodes) ---
        add_action('wp_ajax_nopriv_aiohm_frontend_chat', array(__CLASS__, 'handle_frontend_chat_ajax'));
        add_action('wp_ajax_aiohm_frontend_chat', array(__CLASS__, 'handle_frontend_chat_ajax'));
        add_action('wp_ajax_nopriv_aiohm_search_knowledge', array(__CLASS__, 'handle_search_knowledge_ajax'));
        add_action('wp_ajax_aiohm_search_knowledge', array(__CLASS__, 'handle_search_knowledge_ajax'));

        // --- Admin-Specific Actions ---
        add_action('wp_ajax_aiohm_admin_search_knowledge', array(__CLASS__, 'handle_admin_search_knowledge_ajax'));
    }

    /**
     * Handles the main private chat AJAX requests, now including research logic.
     */
    public static function handle_private_assistant_chat_ajax() {
        check_ajax_referer('aiohm_private_chat_nonce', 'nonce');
        if (!current_user_can('read')) {
            wp_send_json_error(['message' => 'Permission denied']);
            wp_die();
        }
    
        $user_id = get_current_user_id();
        $message = isset($_POST['message']) ? sanitize_textarea_field(stripslashes($_POST['message'])) : '';
        $project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
        $conversation_id = isset($_POST['conversation_id']) ? intval($_POST['conversation_id']) : null;
    
        if (empty($message) || empty($project_id)) {
            wp_send_json_error(['message' => 'Missing message or project context.']);
            wp_die();
        }
    
        try {
            $rag_engine = new AIOHM_KB_RAG_Engine();
            $response_text = $rag_engine->query($message, 'private', $user_id);
    
            // --- Start of Fix ---
            // The user functions are called directly, not as part of a class.
            // This reverts the code to its original, correct state.
            if (is_null($conversation_id)) {
                $conversation_title = mb_strimwidth($message, 0, 100, '...');
                $conversation_id = create_conversation($user_id, $project_id, $conversation_title);
            }
            
            add_message_to_conversation($conversation_id, 'user', $message);
            add_message_to_conversation($conversation_id, 'ai', $response_text);
            // --- End of Fix ---
    
            wp_send_json_success(['reply' => $response_text, 'conversation_id' => $conversation_id]);
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
        wp_die();
    }
    
    /**
     * Handles the AJAX request to scan a live URL from the modal (legacy or alternative method).
     */
    public static function handle_scan_url_live_ajax() {
        check_ajax_referer('aiohm_private_chat_nonce', 'nonce');
        if (!current_user_can('read')) {
            wp_send_json_error(['message' => 'Permission denied.']);
            wp_die();
        }

        $url_to_scan = isset($_POST['url_to_scan']) ? esc_url_raw($_POST['url_to_scan']) : '';
        $user_id = get_current_user_id();

        if (empty($url_to_scan)) {
            wp_send_json_error(['message' => 'No URL was provided.']);
            wp_die();
        }

        $rag_engine = new AIOHM_KB_RAG_Engine();
        $result = $rag_engine->research_and_add_url($url_to_scan, $user_id);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        } else {
            wp_send_json_success(['message' => 'The website content has been successfully added to your private knowledge base.']);
        }
        wp_die();
    }
    
    // --- All other original functions are preserved below ---

    public static function handle_progressive_scan_ajax() {
        check_ajax_referer('aiohm_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied.']);
            wp_die();
        }
    
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        $limit = 10;
        
        $urls_to_scan = get_option('aiohm_urls_to_scan', []);
        $total_urls = count($urls_to_scan);
        
        $urls_for_this_batch = array_slice($urls_to_scan, $offset, $limit);
        
        if (empty($urls_for_this_batch)) {
            wp_send_json_success(['done' => true, 'message' => 'Scan complete.']);
            wp_die();
        }
        
        try {
            $crawler = new AIOHM_KB_Crawler_Site();
            foreach ($urls_for_this_batch as $url) {
                $crawler->crawl_and_store($url);
            }
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
            wp_die();
        }
        
        $new_offset = $offset + $limit;
        $progress = min(100, intval(($new_offset / $total_urls) * 100));
    
        if ($new_offset >= $total_urls) {
            wp_send_json_success(['done' => true, 'progress' => 100, 'message' => 'Website scan completed successfully.']);
        } else {
            wp_send_json_success(['done' => false, 'progress' => $progress, 'offset' => $new_offset, 'message' => "Scanned " . count($urls_for_this_batch) . " more URLs..."]);
        }
        wp_die();
    }

    public static function handle_check_api_key_ajax() {
        check_ajax_referer('aiohm_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied.']);
            wp_die();
        }
        $provider = isset($_POST['provider']) ? sanitize_text_field($_POST['provider']) : '';
        $settings = AIOHM_KB_Assistant::get_settings();
        $api_key = '';

        switch ($provider) {
            case 'openai':
                $api_key = $settings['openai_api_key'];
                break;
            case 'gemini':
                $api_key = $settings['gemini_api_key'];
                break;
            case 'claude':
                $api_key = $settings['claude_api_key'];
                break;
        }

        if (empty($api_key)) {
            wp_send_json_error(['message' => 'API key is not set.']);
            wp_die();
        }

        try {
            $client = new AIOHM_KB_AI_GPT_Client($api_key, $provider);
            $result = $client->test_connection();
            if ($result) {
                wp_send_json_success(['message' => ucfirst($provider) . ' API key is valid.']);
            } else {
                wp_send_json_error(['message' => 'Could not validate ' . ucfirst($provider) . ' API key.']);
            }
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'Error: ' . $e->getMessage()]);
        }
        wp_die();
    }

    public static function handle_export_kb_ajax() {
        check_ajax_referer('aiohm_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied.']);
            wp_die();
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'aiohm_vector_entries';
        $results = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);

        if ($results === null) {
            wp_send_json_error(['message' => 'Failed to retrieve data from the database.']);
            wp_die();
        }

        $json_data = json_encode($results, JSON_PRETTY_PRINT);
        $file_path = wp_upload_dir()['path'] . '/aiohm_kb_export.json';
        file_put_contents($file_path, $json_data);

        wp_send_json_success(['file_url' => wp_upload_dir()['url'] . '/aiohm_kb_export.json']);
        wp_die();
    }

    public static function handle_reset_kb_ajax() {
        check_ajax_referer('aiohm_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied.']);
            wp_die();
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'aiohm_vector_entries';
        $wpdb->query("TRUNCATE TABLE $table_name");
        
        wp_send_json_success(['message' => 'Knowledge Base has been reset.']);
        wp_die();
    }

    public static function handle_toggle_kb_scope_ajax() {
        check_ajax_referer('aiohm_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied.']);
            wp_die();
        }
        
        $entry_id = isset($_POST['entry_id']) ? intval($_POST['entry_id']) : 0;
        $new_scope = isset($_POST['new_scope']) ? intval($_POST['new_scope']) : 0;

        if (!$entry_id) {
            wp_send_json_error(['message' => 'Invalid Entry ID.']);
            wp_die();
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'aiohm_vector_entries';

        $result = $wpdb->update(
            $table_name,
            ['user_id' => $new_scope],
            ['id' => $entry_id],
            ['%d'],
            ['%d']
        );

        if ($result === false) {
            wp_send_json_error(['message' => 'Failed to update entry scope.']);
        } else {
            wp_send_json_success(['message' => 'Entry scope updated successfully.']);
        }
        wp_die();
    }

    public static function handle_restore_kb_ajax() {
        check_ajax_referer('aiohm_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied.']);
            wp_die();
        }

        if (empty($_FILES['file']['tmp_name'])) {
            wp_send_json_error(['message' => 'No file uploaded.']);
            wp_die();
        }

        $json_data = file_get_contents($_FILES['file']['tmp_name']);
        $data = json_decode($json_data, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(['message' => 'Invalid JSON file.']);
            wp_die();
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'aiohm_vector_entries';
        $wpdb->query("TRUNCATE TABLE $table_name");

        foreach ($data as $row) {
            $wpdb->insert($table_name, $row);
        }

        wp_send_json_success(['message' => 'Knowledge Base restored successfully.']);
        wp_die();
    }
    
    public static function handle_delete_kb_entry_ajax() {
        check_ajax_referer('aiohm_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied.']);
            wp_die();
        }

        $entry_id = isset($_POST['entry_id']) ? intval($_POST['entry_id']) : 0;

        if (!$entry_id) {
            wp_send_json_error(['message' => 'Invalid entry ID.']);
            wp_die();
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'aiohm_vector_entries';
        $result = $wpdb->delete($table_name, ['id' => $entry_id], ['%d']);

        if ($result === false) {
            wp_send_json_error(['message' => 'Failed to delete the entry.']);
        } else {
            wp_send_json_success(['message' => 'Entry deleted successfully.']);
        }
        wp_die();
    }

    public static function handle_save_brand_soul_ajax() {
        check_ajax_referer('aiohm_brand_soul_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied.']);
            wp_die();
        }

        $questions_answers = isset($_POST['questions']) ? $_POST['questions'] : [];
        $sanitized_data = [];

        foreach ($questions_answers as $qa) {
            $sanitized_data[] = [
                'question' => sanitize_text_field(stripslashes($qa['question'])),
                'answer'   => sanitize_textarea_field(stripslashes($qa['answer']))
            ];
        }

        update_option('aiohm_brand_soul_data', $sanitized_data);
        wp_send_json_success(['message' => 'Brand Soul saved successfully.']);
        wp_die();
    }

    public static function handle_add_brand_soul_to_kb_ajax() {
        check_ajax_referer('aiohm_brand_soul_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'No Brand Soul data to add.']);
            wp_die();
        }

        $brand_soul_data = get_option('aiohm_brand_soul_data', []);
        if (empty($brand_soul_data)) {
            wp_send_json_error(['message' => 'No Brand Soul data to add.']);
            wp_die();
        }
        
        $rag_engine = new AIOHM_KB_RAG_Engine();
        $user_id = get_current_user_id();
        $content = '';

        foreach ($brand_soul_data as $qa) {
            $content .= "Q: " . $qa['question'] . "\nA: " . $qa['answer'] . "\n\n";
        }
        
        $rag_engine->add_entry($content, 'brand_soul', 'Brand Soul Answers', [], $user_id);

        wp_send_json_success(['message' => 'Brand Soul data successfully added to your private knowledge base.']);
        wp_die();
    }

    public static function handle_pdf_download() {
        if (isset($_GET['action'], $_GET['nonce']) && $_GET['action'] === 'download_brand_soul_pdf' && wp_verify_nonce($_GET['nonce'], 'download_brand_soul_pdf')) {
            if (!current_user_can('manage_options')) {
                wp_die('Permission Denied.');
            }
        }
    }
    
    public static function handle_save_mirror_mode_settings_ajax() {
        check_ajax_referer('aiohm_mirror_mode_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied.']);
            wp_die();
        }

        $settings = AIOHM_KB_Assistant::get_settings();
        $settings['mirror_mode']['qa_system_message'] = sanitize_textarea_field(stripslashes($_POST['qa_system_message']));
        
        $temperature = isset($_POST['qa_temperature']) ? floatval($_POST['qa_temperature']) : 0.7;
        $settings['mirror_mode']['qa_temperature'] = max(0.0, min(1.0, $temperature));

        $settings['mirror_mode']['ai_model'] = sanitize_text_field($_POST['ai_model']);
        
        update_option('aiohm_kb_settings', $settings);
        wp_send_json_success(['message' => 'Mirror Mode settings saved.']);
        wp_die();
    }
    
    public static function handle_generate_mirror_mode_qa_ajax() {
        check_ajax_referer('aiohm_mirror_mode_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied.']);
            wp_die();
        }
        wp_send_json_success(['message' => 'QA generation started.']);
        wp_die();
    }
    
    public static function handle_test_mirror_mode_chat_ajax() {
        check_ajax_referer('aiohm_mirror_mode_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied.']);
            wp_die();
        }

        $message = sanitize_text_field($_POST['message']);
        
        try {
            $rag_engine = new AIOHM_KB_RAG_Engine();
            $response = $rag_engine->query($message, 'site', 0);
            wp_send_json_success(['reply' => $response]);
        } catch(Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
        wp_die();
    }

    public static function handle_save_muse_mode_settings_ajax() {
        check_ajax_referer('aiohm_muse_mode_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied.']);
            wp_die();
        }

        $settings = AIOHM_KB_Assistant::get_settings();
        $settings['muse_mode']['system_prompt'] = sanitize_textarea_field(stripslashes($_POST['system_prompt']));
        
        $temperature = isset($_POST['temperature']) ? floatval($_POST['temperature']) : 0.7;
        $settings['muse_mode']['temperature'] = max(0.0, min(1.0, $temperature));

        $settings['muse_mode']['assistant_name'] = sanitize_text_field($_POST['assistant_name']);
        $settings['muse_mode']['ai_model'] = sanitize_text_field($_POST['ai_model']);
        
        update_option('aiohm_kb_settings', $settings);
        wp_send_json_success(['message' => 'Muse Mode settings saved.']);
        wp_die();
    }

    public static function handle_save_project_notes_ajax() {
        check_ajax_referer('aiohm_private_chat_nonce', 'nonce');
        if (!current_user_can('read')) {
            wp_send_json_error(['message' => 'Permission denied.']);
            wp_die();
        }

        $project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
        $note_content = isset($_POST['note_content']) ? wp_kses_post(stripslashes($_POST['note_content'])) : '';
        $user_id = get_current_user_id();

        if (empty($project_id)) {
            wp_send_json_error(['message' => 'Invalid Project ID.']);
            wp_die();
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'aiohm_projects';

        $wpdb->update(
            $table_name,
            ['notes' => $note_content],
            ['id' => $project_id, 'user_id' => $user_id],
            ['%s'],
            ['%d', '%d']
        );

        wp_send_json_success(['message' => 'Notes saved.']);
        wp_die();
    }

    public static function handle_load_project_notes_ajax() {
        check_ajax_referer('aiohm_private_chat_nonce', 'nonce');
        if (!current_user_can('read')) {
            wp_send_json_error(['message' => 'Permission denied.']);
            wp_die();
        }

        $project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
        $user_id = get_current_user_id();

        if (empty($project_id)) {
            wp_send_json_error(['message' => 'Invalid Project ID.']);
            wp_die();
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'aiohm_projects';

        $note_content = $wpdb->get_var($wpdb->prepare(
            "SELECT notes FROM $table_name WHERE id = %d AND user_id = %d",
            $project_id,
            $user_id
        ));

        wp_send_json_success(['note_content' => $note_content]);
        wp_die();
    }
    
    public static function handle_delete_project_ajax() {
        check_ajax_referer('aiohm_private_chat_nonce', 'nonce');
        if (!current_user_can('read')) {
            wp_send_json_error(['message' => 'Permission denied']);
            wp_die();
        }

        $project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
        $user_id = get_current_user_id();

        if (empty($project_id)) {
            wp_send_json_error(['message' => 'Invalid project ID.']);
            wp_die();
        }

        global $wpdb;
        $projects_table = $wpdb->prefix . 'aiohm_projects';
        $conversations_table = $wpdb->prefix . 'aiohm_conversations';
        $messages_table = $wpdb->prefix . 'aiohm_messages';
        
        $conversation_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT id FROM $conversations_table WHERE project_id = %d AND user_id = %d",
            $project_id, $user_id
        ));

        $wpdb->query('START TRANSACTION');

        try {
            if (!empty($conversation_ids)) {
                $wpdb->query($wpdb->prepare(
                    "DELETE FROM $messages_table WHERE conversation_id IN (" . implode(',', array_fill(0, count($conversation_ids), '%d')) . ")",
                    $conversation_ids
                ));
                $wpdb->delete($conversations_table, ['project_id' => $project_id], ['%d']);
            }

            $deleted_project = $wpdb->delete($projects_table, ['id' => $project_id, 'user_id' => $user_id], ['%d', '%d']);

            if ($deleted_project === false) {
                throw new Exception("Failed to delete the project.");
            }
            
            $wpdb->query('COMMIT');
            wp_send_json_success(['message' => 'Project and all its data deleted successfully.']);

        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            wp_send_json_error(['message' => 'Failed to delete project: ' . $e->getMessage()]);
        }
        wp_die();
    }

    public static function handle_delete_conversation_ajax() {
        check_ajax_referer('aiohm_private_chat_nonce', 'nonce');
        if (!current_user_can('read')) {
            wp_send_json_error(['message' => 'Permission denied']);
            wp_die();
        }

        $conversation_id = isset($_POST['conversation_id']) ? intval($_POST['conversation_id']) : 0;
        $user_id = get_current_user_id();
        
        if (empty($conversation_id)) {
            wp_send_json_error(['message' => 'Invalid conversation ID.']);
            wp_die();
        }

        global $wpdb;
        $conversations_table = $wpdb->prefix . 'aiohm_conversations';
        $messages_table = $wpdb->prefix . 'aiohm_messages';

        $wpdb->query('START TRANSACTION');
        try {
            $wpdb->delete($messages_table, ['conversation_id' => $conversation_id], ['%d']);
            $deleted_conversation = $wpdb->delete($conversations_table, ['id' => $conversation_id, 'user_id' => $user_id], ['%d', '%d']);

            if ($deleted_conversation === false) {
                throw new Exception("Failed to delete the conversation.");
            }

            $wpdb->query('COMMIT');
            wp_send_json_success(['message' => 'Conversation deleted successfully.']);
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            wp_send_json_error(['message' => 'Failed to delete conversation: ' . $e->getMessage()]);
        }
        wp_die();
    }
    
    public static function handle_test_muse_mode_chat_ajax() {
        check_ajax_referer('aiohm_muse_mode_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied.']);
            wp_die();
        }
        $message = sanitize_text_field($_POST['message']);
        $user_id = get_current_user_id();
        try {
            $rag_engine = new AIOHM_KB_RAG_Engine();
            $response = $rag_engine->query($message, 'private', $user_id);
            wp_send_json_success(['reply' => $response]);
        } catch(Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
        wp_die();
    }

    public static function handle_frontend_chat_ajax() {
        check_ajax_referer('aiohm_chat_nonce', 'nonce');
        $message = sanitize_text_field($_POST['message']);
        try {
            $rag_engine = new AIOHM_KB_RAG_Engine();
            $response = $rag_engine->query($message, 'site', 0);
            wp_send_json_success(['reply' => $response]);
        } catch(Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
        wp_die();
    }

    public static function handle_search_knowledge_ajax() {
        check_ajax_referer('aiohm_search_nonce', 'nonce');
        $query = sanitize_text_field($_POST['query']);
        try {
            $rag_engine = new AIOHM_KB_RAG_Engine();
            $results = $rag_engine->search($query, 'site', 0);
            wp_send_json_success(['results' => $results]);
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
        wp_die();
    }

    public static function handle_admin_search_knowledge_ajax() {
        check_ajax_referer('aiohm_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied.']);
            wp_die();
        }
        $query = sanitize_text_field($_POST['query']);
        try {
            $rag_engine = new AIOHM_KB_RAG_Engine();
            $results = $rag_engine->search($query, 'site', 0);
            wp_send_json_success(['results' => $results]);
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
        wp_die();
    }

    public static function handle_get_projects_ajax() {
        check_ajax_referer('aiohm_private_chat_nonce', 'nonce');
        if (!current_user_can('read')) {
            wp_send_json_error(['message' => 'Permission denied']);
            wp_die();
        }
        global $wpdb;
        $user_id = get_current_user_id();
        $results = $wpdb->get_results($wpdb->prepare("SELECT id, project_name, notes FROM {$wpdb->prefix}aiohm_projects WHERE user_id = %d", $user_id));
        wp_send_json_success($results);
        wp_die();
    }

    public static function handle_create_project_ajax() {
        check_ajax_referer('aiohm_private_chat_nonce', 'nonce');
        if (!current_user_can('read')) {
            wp_send_json_error(['message' => 'Permission denied.']);
            wp_die();
        }

        $project_name = isset($_POST['name']) ? sanitize_text_field(stripslashes($_POST['name'])) : '';

        if (empty($project_name)) {
            wp_send_json_error(['message' => 'Project name cannot be empty.']);
            wp_die();
        }

        global $wpdb;
        $user_id = get_current_user_id();
        $table_name = $wpdb->prefix . 'aiohm_projects';

        $result = $wpdb->insert(
            $table_name,
            ['user_id' => $user_id, 'project_name' => $project_name],
            ['%d', '%s']
        );
        
        if ($result === false) {
             wp_send_json_error(['message' => 'Could not save the project to the database.']);
        } else {
            $project_id = $wpdb->insert_id;
            wp_send_json_success(['id' => $project_id, 'project_name' => $project_name, 'new_project_id' => $project_id]);
        }
        wp_die();
    }
    
    public static function handle_get_conversation_history_ajax() {
        check_ajax_referer('aiohm_private_chat_nonce', 'nonce');
        if (!current_user_can('read')) {
            wp_send_json_error(['message' => 'Permission denied']);
            wp_die();
        }
        $conversation_id = isset($_POST['conversation_id']) ? intval($_POST['conversation_id']) : 0;
        if(empty($conversation_id)){
            wp_send_json_error(['message' => 'Invalid conversation ID.']);
            wp_die();
        }

        global $wpdb;
        $project_id = $wpdb->get_var($wpdb->prepare("SELECT project_id FROM {$wpdb->prefix}aiohm_conversations WHERE id = %d", $conversation_id));
        $project_name = $wpdb->get_var($wpdb->prepare("SELECT project_name FROM {$wpdb->prefix}aiohm_projects WHERE id = %d", $project_id));
        
        $messages = $wpdb->get_results($wpdb->prepare("SELECT sender, content as message_content FROM {$wpdb->prefix}aiohm_messages WHERE conversation_id = %d ORDER BY created_at ASC", $conversation_id));
        
        wp_send_json_success(['messages' => $messages, 'project_id' => $project_id, 'project_name' => $project_name]);
        wp_die();
    }
    
    public static function handle_load_history_ajax() {
        check_ajax_referer('aiohm_private_chat_nonce', 'nonce');
        if (!current_user_can('read')) {
            wp_send_json_error(['message' => 'Permission denied']);
            wp_die();
        }
        global $wpdb;
        $user_id = get_current_user_id();
        $projects = $wpdb->get_results($wpdb->prepare("SELECT id, project_name as name FROM {$wpdb->prefix}aiohm_projects WHERE user_id = %d ORDER BY creation_date DESC", $user_id));
        $conversations = $wpdb->get_results($wpdb->prepare("SELECT id, title FROM {$wpdb->prefix}aiohm_conversations WHERE user_id = %d ORDER BY updated_at DESC LIMIT 100", $user_id));
        wp_send_json_success(['projects' => $projects, 'conversations' => $conversations]);
        wp_die();
    }
}