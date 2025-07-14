<?php
/**
 * Core initialization and configuration.
 * Final version with all original functions preserved and fixes for saving and loading conversations.
 */
if (!defined('ABSPATH')) exit;

class AIOHM_KB_Core_Init {

    // ================== START: CONSOLIDATED FIX ==================
    // By placing these functions directly inside the class, we guarantee they are
    // always available when needed, preventing the fatal errors that were causing the crashes.
    
    private static function create_conversation_internal($user_id, $project_id, $title) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aiohm_conversations';
        $result = $wpdb->insert($table_name, ['user_id' => $user_id, 'project_id' => $project_id, 'title' => $title], ['%d', '%d', '%s']);
        return ($result) ? $wpdb->insert_id : false;
    }

    private static function add_message_to_conversation_internal($conversation_id, $sender, $content) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aiohm_messages';
        $result = $wpdb->insert($table_name, ['conversation_id' => $conversation_id, 'sender' => $sender, 'content' => $content], ['%d', '%s', '%s']);
        if ($result) {
            $wpdb->update($wpdb->prefix . 'aiohm_conversations', ['updated_at' => current_time('mysql', 1)], ['id' => $conversation_id]);
        }
        return $result !== false;
    }
    // =================== END: CONSOLIDATED FIX ===================

    public static function init() {
        // --- All original action hooks are preserved ---
        add_action('wp_ajax_aiohm_progressive_scan', array(__CLASS__, 'handle_progressive_scan_ajax'));
        add_action('wp_ajax_aiohm_check_api_key', array(__CLASS__, 'handle_check_api_key_ajax'));
        add_action('wp_ajax_aiohm_export_kb', array(__CLASS__, 'handle_export_kb_ajax'));
        add_action('wp_ajax_aiohm_reset_kb', array(__CLASS__, 'handle_reset_kb_ajax'));
        add_action('wp_ajax_aiohm_toggle_kb_scope', array(__CLASS__, 'handle_toggle_kb_scope_ajax'));
        add_action('wp_ajax_aiohm_restore_kb', array(__CLASS__, 'handle_restore_kb_ajax'));
        add_action('wp_ajax_aiohm_delete_kb_entry', array(__CLASS__, 'handle_delete_kb_entry_ajax'));
        add_action('wp_ajax_aiohm_save_brand_soul', array(__CLASS__, 'handle_save_brand_soul_ajax'));
        add_action('wp_ajax_aiohm_add_brand_soul_to_kb', array(__CLASS__, 'handle_add_brand_soul_to_kb_ajax'));
        add_action('admin_init', array(__CLASS__, 'handle_pdf_download'));
        add_action('wp_ajax_aiohm_save_mirror_mode_settings', array(__CLASS__, 'handle_save_mirror_mode_settings_ajax'));
        add_action('wp_ajax_aiohm_generate_mirror_mode_qa', array(__CLASS__, 'handle_generate_mirror_mode_qa_ajax'));
        add_action('wp_ajax_aiohm_test_mirror_mode_chat', array(__CLASS__, 'handle_test_mirror_mode_chat_ajax'));
        add_action('wp_ajax_aiohm_save_muse_mode_settings', array(__CLASS__, 'handle_save_muse_mode_settings_ajax'));
        add_action('wp_ajax_aiohm_private_assistant_chat', array(__CLASS__, 'handle_private_assistant_chat_ajax'));
        add_action('wp_ajax_aiohm_test_muse_mode_chat', array(__CLASS__, 'handle_test_muse_mode_chat_ajax'));
        add_action('wp_ajax_nopriv_aiohm_frontend_chat', array(__CLASS__, 'handle_frontend_chat_ajax'));
        add_action('wp_ajax_aiohm_frontend_chat', array(__CLASS__, 'handle_frontend_chat_ajax'));
        add_action('wp_ajax_nopriv_aiohm_search_knowledge', array(__CLASS__, 'handle_search_knowledge_ajax'));
        add_action('wp_ajax_aiohm_search_knowledge', array(__CLASS__, 'handle_search_knowledge_ajax'));
        add_action('wp_ajax_aiohm_admin_search_knowledge', array(__CLASS__, 'handle_admin_search_knowledge_ajax'));

        // --- FIX: Add the missing action handler for loading project conversations ---
        add_action('wp_ajax_aiohm_get_project_conversations', array(__CLASS__, 'handle_get_project_conversations_ajax'));
        add_action('wp_ajax_aiohm_create_project', array(__CLASS__, 'handle_create_project_ajax'));
    }
    
    // ================== START: NEW FUNCTION TO FIX PROJECTS ==================
    /**
     * Handles the AJAX request to get all conversations for a specific project.
     * This function was missing, causing projects not to load.
     */
    public static function handle_get_project_conversations_ajax() {
        check_ajax_referer('aiohm_private_chat_nonce', 'nonce');
        if (!current_user_can('read')) {
            wp_send_json_error(['message' => 'Permission Denied']);
            wp_die();
        }

        $project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
        if (empty($project_id)) {
            wp_send_json_error(['message' => 'Invalid Project ID.']);
            wp_die();
        }

        global $wpdb;
        $user_id = get_current_user_id();
        $table_name = $wpdb->prefix . 'aiohm_conversations';

        $conversations = $wpdb->get_results($wpdb->prepare(
            "SELECT id, title FROM {$table_name} WHERE user_id = %d AND project_id = %d ORDER BY updated_at DESC",
            $user_id,
            $project_id
        ));

        wp_send_json_success(['conversations' => $conversations]);
        wp_die();
    }
    // =================== END: NEW FUNCTION TO FIX PROJECTS ===================

    // --- FIX: This function was missing from the older file but is needed by the UI ---
    public static function handle_create_project_ajax() {
        check_ajax_referer('aiohm_private_chat_nonce', 'nonce');
        if (!current_user_can('read')) { wp_send_json_error(['message' => 'Permission denied.']); wp_die(); }
        
        $project_name = isset($_POST['project_name']) ? sanitize_text_field(stripslashes($_POST['project_name'])) : '';
        if (empty($project_name)) { wp_send_json_error(['message' => 'Project name cannot be empty.']); wp_die(); }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'aiohm_projects';
        $user_id = get_current_user_id();
        
        $result = $wpdb->insert($table_name, ['user_id' => $user_id, 'project_name' => $project_name], ['%d', '%s']);
        
        if ($result === false) {
             wp_send_json_error(['message' => 'Could not save the project to the database.']);
        } else {
            $project_id = $wpdb->insert_id;
            wp_send_json_success(['id' => $project_id, 'name' => $project_name]);
        }
        wp_die();
    }

    public static function handle_private_assistant_chat_ajax() {
        if (!check_ajax_referer('aiohm_private_chat_nonce', 'nonce', false)) {
            wp_send_json_error(['answer' => 'Security check failed.']);
        }
    
        if (!current_user_can('administrator') && !current_user_can('ohm_brand_collaborator')) {
            wp_send_json_error(['answer' => 'You do not have permission to use this feature.']);
        }
    
        try {
            $user_message = sanitize_textarea_field($_POST['message']);
            $user_id = get_current_user_id();
            
            $project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
            $conversation_id = isset($_POST['conversation_id']) ? intval($_POST['conversation_id']) : null;

            if (empty($project_id)) {
                throw new Exception('A project must be selected to start a conversation.');
            }

            $settings = AIOHM_KB_Assistant::get_settings();
            $muse_settings = $settings['muse_mode'] ?? [];
    
            $ai_client = new AIOHM_KB_AI_GPT_Client();
            $rag_engine = new AIOHM_KB_RAG_Engine();
            
            $context_data = $rag_engine->find_context_for_user($user_message, $user_id, 10);
            $context_string = "";
            if (!empty($context_data)) {
                foreach ($context_data as $data) {
                    $context_string .= "Source: " . $data['entry']['title'] . "\nContent: " . $data['entry']['content'] . "\n\n";
                }
            }
    
            $system_prompt = $muse_settings['system_prompt'] ?? 'You are a helpful brand assistant.';
            $temperature = floatval($muse_settings['temperature'] ?? 0.7);
            $model = $muse_settings['ai_model'] ?? 'gpt-4';
            
            $final_system_message = $system_prompt . "\n\n--- CONTEXT ---\n" . $context_string;
    
            $answer = $ai_client->get_chat_completion($final_system_message, $user_message, $temperature, $model);

            if (is_null($conversation_id)) {
                $conversation_title = mb_strimwidth($user_message, 0, 100, '...');
                $conversation_id = self::create_conversation_internal($user_id, $project_id, $conversation_title);
                if (!$conversation_id) {
                    AIOHM_KB_Assistant::log('Failed to create conversation record.', 'error');
                }
            }
            
            if ($conversation_id) {
                self::add_message_to_conversation_internal($conversation_id, 'user', $user_message);
                self::add_message_to_conversation_internal($conversation_id, 'ai', $answer);
            }
    
            wp_send_json_success(['answer' => $answer, 'conversation_id' => $conversation_id]);
    
        } catch (Exception $e) {
            AIOHM_KB_Assistant::log('Private Assistant Error: ' . $e->getMessage(), 'error');
            wp_send_json_error(['answer' => 'An error occurred while processing your request.']);
        }
    }

    public static function handle_frontend_chat_ajax() {
        if (!check_ajax_referer('aiohm_chat_nonce', 'nonce', false)) {
            wp_send_json_error(['answer' => 'Security check failed.']);
        }

        try {
            $user_message = sanitize_textarea_field($_POST['message']);
            
            $settings = AIOHM_KB_Assistant::get_settings();
            $mirror_settings = $settings['mirror_mode'] ?? [];

            $ai_client = new AIOHM_KB_AI_GPT_Client();
            $rag_engine = new AIOHM_KB_RAG_Engine();
            
            $context_data = $rag_engine->find_relevant_context($user_message, 5);
            $context_string = "";
            if (!empty($context_data)) {
                foreach ($context_data as $data) {
                    $context_string .= "Source: " . $data['entry']['title'] . "\nContent: " . $data['entry']['content'] . "\n\n";
                }
            } else {
                $context_string = "No relevant context found in the knowledge base.";
            }

            $system_message = $mirror_settings['qa_system_message'] ?? 'You are a helpful assistant.';
            $temperature = floatval($mirror_settings['qa_temperature'] ?? 0.7);
            
            $replacements = [
                '{context}'        => $context_string,
                '%site_name%'      => get_bloginfo('name'),
                '%business_name%'  => $mirror_settings['business_name'] ?? get_bloginfo('name'),
                '%day_of_week%'    => wp_date('l'),
                '%current_date%'   => wp_date(get_option('date_format')),
                '%current_time%'   => wp_date(get_option('time_format')),
            ];
            $final_system_message = str_replace(array_keys($replacements), array_values($replacements), $system_message);

            $answer = $ai_client->get_chat_completion($final_system_message, $user_message, $temperature, $mirror_settings['ai_model'] ?? 'gpt-3.5-turbo');

            wp_send_json_success(['answer' => $answer]);

        } catch (Exception $e) {
            AIOHM_KB_Assistant::log('Frontend Chat Error: ' . $e->getMessage(), 'error');
            wp_send_json_error(['answer' => 'An error occurred while processing your request. Please try again.']);
        }
    }
    
    public static function handle_search_knowledge_ajax() {
        if (!check_ajax_referer('aiohm_search_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Security check failed']);
        }
        
        $query = sanitize_text_field($_POST['query']);
        $content_type_filter = sanitize_text_field($_POST['content_type_filter']);
        $max_results = intval($_POST['max_results']) ?: 10;
        $excerpt_length = intval($_POST['excerpt_length']) ?: 25;
        
        if (empty($query)) {
            wp_send_json_error(['message' => 'Search query is required']);
        }
        
        try {
            $rag_engine = new AIOHM_KB_RAG_Engine();
            $results = $rag_engine->find_relevant_context($query, $max_results);
            
            $filtered_results = [];
            if (!empty($content_type_filter)) {
                 foreach ($results as $result) {
                    if ($result['entry']['content_type'] === $content_type_filter) {
                        $filtered_results[] = $result;
                    }
                }
            } else {
                $filtered_results = $results;
            }
            
            $formatted_results = array();
            foreach ($filtered_results as $result) {
                $entry = $result['entry'];
                $excerpt = wp_trim_words($entry['content'], $excerpt_length, '...');
                $metadata = is_string($entry['metadata']) ? json_decode($entry['metadata'], true) : $entry['metadata'];

                $formatted_results[] = array(
                    'title' => $entry['title'],
                    'excerpt' => $excerpt,
                    'content_type' => $entry['content_type'],
                    'similarity' => round($result['score'] * 100, 1),
                    'url' => $metadata['url'] ?? get_permalink($metadata['post_id'] ?? 0) ?? '#',
                );
            }
            
            wp_send_json_success([
                'results' => $formatted_results,
                'total_count' => count($formatted_results),
            ]);
            
        } catch (Exception $e) {
            AIOHM_KB_Assistant::log('Admin Search Error: ' . $e->getMessage(), 'error');
            wp_send_json_error(['message' => 'Search failed: ' . $e->getMessage()]);
        }
    }
    
    public static function handle_admin_search_knowledge_ajax() {
        if (!check_ajax_referer('aiohm_mirror_mode_nonce', 'nonce', false) || !current_user_can('read')) {
            wp_send_json_error(['message' => 'Security check failed or insufficient permissions.']);
        }
        
        $query = sanitize_text_field($_POST['query']);
        $content_type_filter = sanitize_text_field($_POST['content_type_filter']);
        $max_results = 5;
        $excerpt_length = 20;

        if (empty($query)) {
            wp_send_json_error(['message' => 'Search query is required.']);
        }
        
        try {
            $rag_engine = new AIOHM_KB_RAG_Engine();
            $results = $rag_engine->find_relevant_context($query, $max_results);
            
            $filtered_results = [];
            if (!empty($content_type_filter)) {
                 foreach ($results as $result) {
                    if ($result['entry']['content_type'] === $content_type_filter) {
                        $filtered_results[] = $result;
                    }
                }
            } else {
                $filtered_results = $results;
            }
            
            $formatted_results = array();
            foreach ($filtered_results as $result) {
                $entry = $result['entry'];
                $excerpt = wp_trim_words($entry['content'], $excerpt_length, '...');
                $metadata = is_string($entry['metadata']) ? json_decode($entry['metadata'], true) : $entry['metadata'];

                $formatted_results[] = array(
                    'title' => $entry['title'],
                    'excerpt' => $excerpt,
                    'content_type' => $entry['content_type'],
                    'similarity' => round($result['score'] * 100, 1),
                    'url' => $metadata['url'] ?? get_permalink($metadata['post_id'] ?? 0) ?? '#',
                );
            }
            
            wp_send_json_success([
                'results' => $formatted_results,
            ]);
            
        } catch (Exception $e) {
            AIOHM_KB_Assistant::log('Admin Search Error: ' . $e->getMessage(), 'error');
            wp_send_json_error(['message' => 'Search failed: ' . $e->getMessage()]);
        }
    }

    public static function handle_test_mirror_mode_chat_ajax() {
        if (!check_ajax_referer('aiohm_mirror_mode_nonce', 'nonce', false) || !current_user_can('read')) {
            wp_send_json_error(['message' => 'Security check failed.']);
        }

        try {
            $user_message = sanitize_textarea_field($_POST['message']);
            $posted_settings = isset($_POST['settings']) ? $_POST['settings'] : [];

            $system_message = $posted_settings['qa_system_message'] ?? 'You are a helpful assistant.';
            $temperature = floatval($posted_settings['qa_temperature'] ?? 0.7);
            
            $ai_client = new AIOHM_KB_AI_GPT_Client();
            $rag_engine = new AIOHM_KB_RAG_Engine();
            
            $context_data = $rag_engine->find_relevant_context($user_message, 5);
            $context_string = "";
            foreach ($context_data as $data) {
                $context_string .= "Source: " . $data['entry']['title'] . "\nContent: " . $data['entry']['content'] . "\n\n";
            }
            if (empty($context_string)) {
                $context_string = "No relevant context found.";
            }

            $replacements = [
                '{context}'        => $context_string,
                '%site_name%'      => $posted_settings['business_name'] ?? get_bloginfo('name'),
                '%business_name%'  => $posted_settings['business_name'] ?? get_bloginfo('name'),
                '%day_of_week%'    => wp_date('l'),
                '%current_date%'   => wp_date(get_option('date_format')),
                '%current_time%'   => wp_date(get_option('time_format')),
            ];
            $final_system_message = str_replace(array_keys($replacements), array_values($replacements), $system_message);

            $answer = $ai_client->get_chat_completion($final_system_message, $user_message, $temperature, $posted_settings['ai_model'] ?? 'gpt-3.5-turbo');

            wp_send_json_success(['answer' => $answer]);

        } catch (Exception $e) {
            wp_send_json_error(['message' => 'AI request failed: ' . $e->getMessage()]);
        }
    }
    
    public static function handle_test_muse_mode_chat_ajax() {
        if (!check_ajax_referer('aiohm_muse_mode_nonce', 'nonce', false) || !current_user_can('read')) {
            wp_send_json_error(['message' => 'Security check failed.']);
        }
    
        try {
            $user_message = sanitize_textarea_field($_POST['message']);
            $posted_settings = isset($_POST['settings']) ? $_POST['settings'] : [];
            $user_id = get_current_user_id();
    
            $system_prompt = sanitize_textarea_field($posted_settings['system_prompt'] ?? 'You are a helpful brand assistant.');
            $temperature = floatval($posted_settings['temperature'] ?? 0.7);
            $model = sanitize_text_field($posted_settings['ai_model'] ?? 'gpt-4');
    
            $ai_client = new AIOHM_KB_AI_GPT_Client();
            $rag_engine = new AIOHM_KB_RAG_Engine();
            
            $context_data = $rag_engine->find_context_for_user($user_message, $user_id, 10);
            $context_string = "";
            if (!empty($context_data)) {
                foreach ($context_data as $data) {
                    $context_string .= "Source: " . $data['entry']['title'] . "\nContent: " . $data['entry']['content'] . "\n\n";
                }
            }
            
            $final_system_message = $system_prompt . "\n\n--- CONTEXT ---\n" . $context_string;
    
            $answer = $ai_client->get_chat_completion($final_system_message, $user_message, $temperature, $model);
    
            wp_send_json_success(['answer' => $answer]);
    
        } catch (Exception $e) {
            AIOHM_KB_Assistant::log('Muse Mode Test Chat Error: ' . $e->getMessage(), 'error');
            wp_send_json_error(['message' => 'AI request failed: ' . $e->getMessage()]);
        }
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
                    $all_items = $crawler->find_all_content();
                    wp_send_json_success(['processed_items' => $results, 'all_items' => $all_items]);
                    break;
                case 'uploads_find':
                    $crawler = new AIOHM_KB_Uploads_Crawler();
                    $all_supported_files = $crawler->find_all_supported_attachments();
                    wp_send_json_success(['items' => $all_supported_files]);
                    break;
                case 'uploads_add':
                    $item_ids = isset($_POST['item_ids']) ? array_map('intval', $_POST['item_ids']) : [];
                    if (empty($item_ids)) throw new Exception('No item IDs provided.');
                    $crawler = new AIOHM_KB_Uploads_Crawler();
                    $results = $crawler->add_attachments_to_kb($item_ids);
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
        if (!wp_verify_nonce($_POST['nonce'], 'aiohm_admin_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Security check failed.']);
        }
        $api_key = sanitize_text_field($_POST['api_key']);
        $key_type = sanitize_key($_POST['key_type']);
        if (empty($api_key)) {
            wp_send_json_error(['message' => 'API Key / Email cannot be empty.']);
        }
        try {
            switch ($key_type) {
                case 'aiohm_email':
                    $api_client = new AIOHM_App_API_Client();
                    $result = $api_client->get_member_details_by_email($api_key);
                    if (!is_wp_error($result) && !empty($result['response']['result']['ID'])) {
                        wp_send_json_success(['message' => 'AIOHM.app connection successful!', 'user_id' => $result['response']['result']['ID']]);
                    } else {
                        $error_message = is_wp_error($result) ? $result->get_error_message() : ($result['message'] ?? 'Invalid Email or API error.');
                        wp_send_json_error(['message' => 'AIOHM.app connection failed: ' . $error_message]);
                    }
                    break;
                case 'openai':
                    $ai_client = new AIOHM_KB_AI_GPT_Client(['openai_api_key' => $api_key]);
                    $result = $ai_client->test_api_connection();
                    if ($result['success']) {
                        wp_send_json_success(['message' => 'OpenAI connection successful!']);
                    } else {
                        wp_send_json_error(['message' => 'OpenAI connection failed: ' . ($result['error'] ?? 'Unknown error.')]);
                    }
                    break;
                case 'gemini':
                    $ai_client = new AIOHM_KB_AI_GPT_Client(['gemini_api_key' => $api_key]);
                    $result = $ai_client->test_gemini_api_connection();
                    if ($result['success']) {
                        wp_send_json_success(['message' => 'Gemini connection successful!']);
                    } else {
                        wp_send_json_error(['message' => 'Gemini connection failed: ' . ($result['error'] ?? 'Unknown error.')]);
                    }
                    break;
                default:
                    wp_send_json_error(['message' => 'Invalid key type specified.']);
            }
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'An unexpected error occurred: ' . $e->getMessage()]);
        }
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
        }
        catch (Exception $e) {
            wp_send_json_error(['message' => 'Restore failed: ' . $e->getMessage()]);
        }
    }

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

    public static function handle_save_brand_soul_ajax() {
        if (!wp_verify_nonce($_POST['nonce'], 'aiohm_brand_soul_nonce') || !current_user_can('read')) {
            wp_send_json_error(['message' => 'Security check failed.']);
        }
        parse_str($_POST['data'], $form_data);
        $answers = isset($form_data['answers']) ? array_map('sanitize_textarea_field', $form_data['answers']) : [];
        update_user_meta(get_current_user_id(), 'aiohm_brand_soul_answers', $answers);
        wp_send_json_success();
    }

    public static function handle_add_brand_soul_to_kb_ajax() {
        if (!wp_verify_nonce($_POST['nonce'], 'aiohm_brand_soul_nonce') || !current_user_can('read')) {
            wp_send_json_error(['message' => 'Security check failed.']);
        }
        parse_str($_POST['data'], $form_data);
        $answers = isset($form_data['answers']) ? array_map('sanitize_textarea_field', $form_data['answers']) : [];
        if (empty($answers)) {
            wp_send_json_error(['message' => 'No answers to add.']);
        }
        try {
            $rag_engine = new AIOHM_KB_RAG_Engine();
            $user_id = get_current_user_id();
            $content = json_encode($answers, JSON_PRETTY_PRINT);
            $rag_engine->add_entry($content, 'brand_soul', 'My Brand Soul', [], $user_id);
            wp_send_json_success();
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'Error adding to KB: ' . $e->getMessage()]);
        }
    }

    public static function handle_pdf_download() {
        if (isset($_GET['action']) && $_GET['action'] === 'download_brand_soul_pdf' && isset($_GET['nonce']) && wp_verify_nonce($_GET['nonce'], 'download_brand_soul_pdf')) {
            if (!class_exists('FPDF')) {
                require_once AIOHM_KB_INCLUDES_DIR . 'lib/fpdf/fpdf.php';
            }
            $user_id = get_current_user_id();
            $user_info = get_userdata($user_id);
            $answers = get_user_meta($user_id, 'aiohm_brand_soul_answers', true);
            if (!is_array($answers)) {
                $answers = [];
            }
            $brand_soul_questions = [
                '✨ Foundation' => [
                    'foundation_1' => "What’s the deeper purpose behind your brand — beyond profit?",
                    'foundation_2' => "What life experiences shaped this work you now do?",
                    'foundation_3' => "Who were you before this calling emerged?",
                    'foundation_4' => "If your brand had a soul story, how would you tell it?",
                    'foundation_5' => "What’s one transformation you’ve witnessed that reminds you why you do this?",
                ],
                '🌀 Energy' => [
                    'energy_1' => "What 3 words describe the emotional tone of your brand voice?",
                    'energy_2' => "How do you want your audience to feel after encountering your message?",
                    'energy_3' => "What do you not want to sound like?",
                    'energy_4' => "Do you prefer poetic, punchy, playful, or professional language?",
                    'energy_5' => "Share a quote, phrase, or piece of content that feels like you.",
                ],
                '🎨 Expression' => [
                    'expression_1' => "What are your brand’s primary colors (and any specific hex codes)?",
                    'expression_2' => "What font(s) do you use — or wish to use — for headers and body text?",
                    'expression_3' => "Is there a visual theme (earthy, cosmic, minimalist, ornate) that matches your brand essence?",
                    'expression_4' => "Are there any logos, patterns, or symbols that hold meaning for your brand?",
                    'expression_5' => "What offerings are you currently sharing with the world — and how are they priced or exchanged?",
                ],
                '🚀 Direction' => [
                    'direction_1' => "What’s your current main offer or project you want support with?",
                    'direction_2' => "Who is your dream client? Describe them with emotion and detail.",
                    'direction_3' => "What are 3 key goals you have for the next 6 months?",
                    'direction_4' => "Where do you feel stuck, overwhelmed, or unsure — and where would you love AI support?",
                    'direction_5' => "If this AI assistant could speak your soul fluently, what would you want it to never forget?",
                ],
            ];
            $pdf = new FPDF();
            $pdf->AddPage();
            $pdf->SetFont('Arial', 'B', 16);
            $pdf->Cell(0, 10, 'Your Brand Core Questionnaire', 0, 1, 'C');
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(0, 10, 'User: ' . $user_info->display_name, 0, 1, 'C');
            $pdf->Cell(0, 10, 'Date: ' . date('Y-m-d'), 0, 1, 'C');
            $pdf->Ln(10);
            foreach ($brand_soul_questions as $section_title => $questions) {
                $pdf->SetFont('Arial', 'B', 14);
                $pdf->SetFillColor(235, 235, 235);
                $pdf->Cell(0, 12, mb_convert_encoding($section_title, 'ISO-8859-1', 'UTF-8'), 0, 1, 'L', true);
                $pdf->Ln(4);
                foreach ($questions as $key => $question_text) {
                    $pdf->SetFont('Arial', 'B', 12);
                    $pdf->MultiCell(0, 7, mb_convert_encoding($question_text, 'ISO-8859-1', 'UTF-8'));
                    $pdf->Ln(2);
                    $pdf->SetFont('Arial', '', 12);
                    $answer = isset($answers[$key]) ? $answers[$key] : 'No answer provided.';
                    $pdf->SetTextColor(50, 50, 50);
                    $pdf->MultiCell(0, 7, mb_convert_encoding($answer, 'ISO-8859-1', 'UTF-8'));
                    $pdf->SetTextColor(0, 0, 0);
                    $pdf->Ln(8);
                }
            }
            $brand_name = sanitize_title($user_info->display_name);
            $filename = $brand_name . '-AI-brand-core.pdf';
            $pdf->Output('D', $filename);
            exit;
        }
    }

    public static function handle_save_mirror_mode_settings_ajax() {
        if (!check_ajax_referer('aiohm_mirror_mode_nonce', 'nonce', false) || !current_user_can('read')) {
            wp_send_json_error(['message' => 'Security check failed.']);
        }
        parse_str($_POST['form_data'], $form_data);
        $settings_input = $form_data['aiohm_kb_settings']['mirror_mode'] ?? [];
        if (empty($settings_input)) {
            wp_send_json_error(['message' => 'No settings data received.']);
        }
        
        $settings = AIOHM_KB_Assistant::get_settings();
        
        $settings['mirror_mode']['business_name'] = sanitize_text_field($settings_input['business_name']);
        $settings['mirror_mode']['qa_system_message'] = sanitize_textarea_field($settings_input['qa_system_message']);
        $settings['mirror_mode']['qa_temperature'] = floatval($settings_input['qa_temperature']);
        $settings['mirror_mode']['primary_color'] = sanitize_hex_color($settings_input['primary_color']);
        $settings['mirror_mode']['background_color'] = sanitize_hex_color($settings_input['background_color']);
        $settings['mirror_mode']['text_color'] = sanitize_hex_color($settings_input['text_color']);
        $settings['mirror_mode']['ai_avatar'] = esc_url_raw($settings_input['ai_avatar']);
        $settings['mirror_mode']['meeting_button_url'] = esc_url_raw($settings_input['meeting_button_url']);
        $settings['mirror_mode']['ai_model'] = sanitize_text_field($settings_input['ai_model']);

        update_option('aiohm_kb_settings', $settings);
        wp_send_json_success(['message' => 'Mirror Mode settings saved successfully.']);
    }

    public static function handle_save_muse_mode_settings_ajax() {
        if (!check_ajax_referer('aiohm_muse_mode_nonce', 'nonce', false) || !current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Security check failed.']);
        }

        parse_str($_POST['form_data'], $form_data);
        $muse_input = $form_data['aiohm_kb_settings']['muse_mode'] ?? [];

        if (empty($muse_input)) {
            wp_send_json_error(['message' => 'No settings data received.']);
        }

        $settings = AIOHM_KB_Assistant::get_settings();
        
        $settings['muse_mode']['assistant_name'] = sanitize_text_field($muse_input['assistant_name']);
        $settings['muse_mode']['system_prompt'] = sanitize_textarea_field($muse_input['system_prompt']);
        $settings['muse_mode']['ai_model'] = sanitize_text_field($muse_input['ai_model']);
        $settings['muse_mode']['temperature'] = floatval($muse_input['temperature']);

        update_option('aiohm_kb_settings', $settings);
        wp_send_json_success(['message' => 'Muse Mode settings saved successfully.']);
    }

    public static function handle_generate_mirror_mode_qa_ajax() {
        if (!check_ajax_referer('aiohm_mirror_mode_nonce', 'nonce', false) || !current_user_can('read')) {
            wp_send_json_error(['message' => 'Security check failed.']);
        }
        try {
            $rag_engine = new AIOHM_KB_RAG_Engine();
            $ai_client = new AIOHM_KB_AI_GPT_Client();
            $random_chunk = $rag_engine->get_random_chunk();
            if (!$random_chunk) {
                throw new Exception("Your knowledge base is empty. Please scan some content first.");
            }
            $question_prompt = "Based on the following text, what is a likely user question? Only return the question itself, without any preamble.\n\nCONTEXT:\n" . $random_chunk;
            $question = $ai_client->get_chat_completion($question_prompt, "", 0.7);
            $answer_prompt = "You are a helpful assistant. Answer the following question based on the provided context.\n\nCONTEXT:\n{$random_chunk}\n\nQUESTION:\n{$question}";
            $answer = $ai_client->get_chat_completion($answer_prompt, "", 0.2);
            wp_send_json_success(['qa_pair' => ['question' => trim(str_replace('"', '', $question)), 'answer' => trim($answer)]]);
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'Failed to generate Q&A pair: ' . $e->getMessage()]);
        }
    }
}