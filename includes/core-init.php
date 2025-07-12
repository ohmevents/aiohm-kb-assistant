<?php
/**
 * Core initialization and configuration.
 * v1.2.1
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
        add_action('wp_ajax_aiohm_private_assistant_chat', array(__CLASS__, 'handle_private_assistant_chat_ajax'));
        add_action('wp_ajax_aiohm_test_muse_mode_chat', array(__CLASS__, 'handle_test_muse_mode_chat_ajax'));
        add_action('wp_ajax_aiohm_get_conversations', array(__CLASS__, 'handle_get_conversations_ajax'));
        add_action('wp_ajax_aiohm_get_conversation_history', array(__CLASS__, 'handle_get_conversation_history_ajax'));
        add_action('wp_ajax_aiohm_add_chat_to_kb', array(__CLASS__, 'handle_add_chat_to_kb_ajax'));
        
        // --- Project Actions ---
        add_action('wp_ajax_aiohm_get_projects', array(__CLASS__, 'handle_get_projects_ajax'));
        add_action('wp_ajax_aiohm_create_project', array(__CLASS__, 'handle_create_project_ajax'));


        // --- Frontend Actions (Shortcodes) ---
        add_action('wp_ajax_nopriv_aiohm_frontend_chat', array(__CLASS__, 'handle_frontend_chat_ajax'));
        add_action('wp_ajax_aiohm_frontend_chat', array(__CLASS__, 'handle_frontend_chat_ajax'));
        add_action('wp_ajax_nopriv_aiohm_search_knowledge', array(__CLASS__, 'handle_search_knowledge_ajax'));
        add_action('wp_ajax_aiohm_search_knowledge', array(__CLASS__, 'handle_search_knowledge_ajax'));

        // --- Admin-Specific Actions ---
        add_action('wp_ajax_aiohm_admin_search_knowledge', array(__CLASS__, 'handle_admin_search_knowledge_ajax'));
    }

    public static function handle_get_projects_ajax() {
        if (!check_ajax_referer('aiohm_private_chat_nonce', 'nonce', false) || !current_user_can('read')) {
            wp_send_json_error([]);
        }
        global $wpdb;
        $user_id = get_current_user_id();
        $table_name = $wpdb->prefix . 'aiohm_projects';
        $projects = $wpdb->get_results($wpdb->prepare(
            "SELECT id, project_name FROM $table_name WHERE user_id = %d ORDER BY creation_date DESC",
            $user_id
        ));
        wp_send_json_success($projects);
    }

    public static function handle_create_project_ajax() {
        if (!check_ajax_referer('aiohm_private_chat_nonce', 'nonce', false) || !current_user_can('read')) {
            wp_send_json_error(['message' => 'Permission denied.']);
        }
        $project_name = isset($_POST['project_name']) ? sanitize_text_field($_POST['project_name']) : '';
        if (empty($project_name)) {
            wp_send_json_error(['message' => 'Project name cannot be empty.']);
        }
        global $wpdb;
        $user_id = get_current_user_id();
        $table_name = $wpdb->prefix . 'aiohm_projects';
        $wpdb->insert(
            $table_name,
            ['user_id' => $user_id, 'project_name' => $project_name],
            ['%d', '%s']
        );
        $project_id = $wpdb->insert_id;
        wp_send_json_success(['id' => $project_id, 'project_name' => $project_name]);
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
                '{context}' => $context_string,
                '%site_name%' => get_bloginfo('name'),
                '%business_name%' => $mirror_settings['business_name'] ?? get_bloginfo('name'),
                '%day_of_week%'    => wp_date('l'),
                '%current_date%'   => wp_date(get_option('date_format')),
            ];
            $final_system_message = str_replace(array_keys($replacements), array_values($replacements), $system_message);
            $answer = $ai_client->get_chat_completion($final_system_message, $user_message, $temperature, $mirror_settings['ai_model'] ?? 'gpt-3.5-turbo');

            wp_send_json_success(['answer' => $answer]);

        } catch (Exception $e) {
            AIOHM_KB_Assistant::log('Frontend Chat Error: ' . $e->getMessage(), 'error');
            wp_send_json_error(['answer' => 'An error occurred while processing your request. Please try again.']);
        }
    }

    public static function handle_private_assistant_chat_ajax() {
        if (!check_ajax_referer('aiohm_private_chat_nonce', 'nonce', false) || !current_user_can('read')) {
            wp_send_json_error(['answer' => 'Permission denied.']);
        }

        global $wpdb;
        $user_id = get_current_user_id();
        $user_message = sanitize_textarea_field($_POST['message']);
        $conversation_id = isset($_POST['conversation_id']) ? intval($_POST['conversation_id']) : null;
        $project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;

        if (empty($project_id)) {
            wp_send_json_error(['answer' => 'Project ID is missing. Cannot save chat.']);
            return;
        }

        try {
            if (!$conversation_id) {
                $conversation_title = substr($user_message, 0, 50) . (strlen($user_message) > 50 ? '...' : '');
                $wpdb->insert(
                    $wpdb->prefix . 'aiohm_conversations',
                    ['user_id' => $user_id, 'title' => $conversation_title, 'project_id' => $project_id],
                    ['%d', '%s', '%d']
                );
                $conversation_id = $wpdb->insert_id;
            }

            $wpdb->insert(
                $wpdb->prefix . 'aiohm_messages',
                ['conversation_id' => $conversation_id, 'sender' => 'user', 'content' => $user_message],
                ['%d', '%s', '%s']
            );

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
            $final_system_message = $system_prompt . "\n\n--- CONTEXT ---\n" . $context_string;
            $ai_response = $ai_client->get_chat_completion($final_system_message, $user_message, floatval($muse_settings['temperature'] ?? 0.7), $muse_settings['ai_model'] ?? 'gpt-4');

            $wpdb->insert(
                $wpdb->prefix . 'aiohm_messages',
                ['conversation_id' => $conversation_id, 'sender' => 'ai', 'content' => $ai_response],
                ['%d', '%s', '%s']
            );
            
            $wpdb->update($wpdb->prefix . 'aiohm_conversations', ['updated_at' => current_time('mysql')], ['id' => $conversation_id]);

            wp_send_json_success(['reply' => $ai_response, 'conversation_id' => $conversation_id]);

        } catch (Exception $e) {
            AIOHM_KB_Assistant::log('Private Assistant Error: ' . $e->getMessage(), 'error');
            wp_send_json_error(['answer' => 'An error occurred while processing your request.']);
        }
    }

    public static function handle_get_conversations_ajax() {
        if (!check_ajax_referer('aiohm_private_chat_nonce', 'nonce', false) || !current_user_can('read')) {
            wp_send_json_error([]);
        }
        global $wpdb;
        $user_id = get_current_user_id();
        $project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
        $table_name = $wpdb->prefix . 'aiohm_conversations';
        
        $query = $wpdb->prepare(
            "SELECT id, title FROM $table_name WHERE user_id = %d AND project_id = %d ORDER BY updated_at DESC LIMIT 50",
            $user_id, $project_id
        );

        $conversations = $wpdb->get_results($query);
        wp_send_json_success($conversations);
    }

    public static function handle_get_conversation_history_ajax() {
        if (!check_ajax_referer('aiohm_private_chat_nonce', 'nonce', false) || !current_user_can('read')) {
            wp_send_json_error([]);
        }
        global $wpdb;
        $user_id = get_current_user_id();
        $conversation_id = intval($_POST['conversation_id']);
        $table_messages = $wpdb->prefix . 'aiohm_messages';
        $table_conversations = $wpdb->prefix . 'aiohm_conversations';
        
        $owner_id = $wpdb->get_var($wpdb->prepare("SELECT user_id FROM $table_conversations WHERE id = %d", $conversation_id));
        if ($owner_id != $user_id) {
            wp_send_json_error(['message' => 'Access denied.']);
        }

        $messages = $wpdb->get_results($wpdb->prepare(
            "SELECT sender, content FROM $table_messages WHERE conversation_id = %d ORDER BY created_at ASC",
            $conversation_id
        ));
        wp_send_json_success($messages);
    }

    public static function handle_add_chat_to_kb_ajax() {
        if (!check_ajax_referer('aiohm_private_chat_nonce', 'nonce', false) || !current_user_can('read')) {
            wp_send_json_error(['message' => 'Permission denied.']);
        }

        $conversation_id = isset($_POST['conversation_id']) ? intval($_POST['conversation_id']) : 0;
        $messages = isset($_POST['messages']) ? $_POST['messages'] : [];

        if (empty($conversation_id) || empty($messages)) {
            wp_send_json_error(['message' => 'Missing conversation data.']);
        }

        // The bug fix from our first conversation: ensuring messages are in the correct order.
        $messages = array_reverse($messages);

        try {
            if (!class_exists('FPDF')) {
                require_once AIOHM_KB_INCLUDES_DIR . 'lib/fpdf/fpdf.php';
            }
            
            global $wpdb;
            $conversation_title = $wpdb->get_var($wpdb->prepare(
                "SELECT title FROM {$wpdb->prefix}aiohm_conversations WHERE id = %d", 
                $conversation_id
            ));
            $pdf_title = 'AIOHM Chat - ' . ($conversation_title ?: "Conversation #{$conversation_id}");

            $pdf = new FPDF();
            $pdf->AddPage();
            $pdf->SetFont('Arial', 'B', 16);
            $pdf->Cell(0, 10, $pdf_title, 0, 1, 'C');
            $pdf->Ln(10);

            foreach ($messages as $message) {
                $sender = esc_html(ucfirst($message['sender']));
                $content = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $message['content']);
                
                $pdf->SetFont('Arial', 'B', 12);
                $pdf->Cell(0, 10, "{$sender}:", 0, 1);
                $pdf->SetFont('Arial', '', 12);
                $pdf->MultiCell(0, 7, $content);
                $pdf->Ln(5);
            }

            $upload_dir = wp_upload_dir();
            $filename = "aiohm-chat-{$conversation_id}-" . time() . ".pdf";
            $filepath = trailingslashit($upload_dir['path']) . $filename;
            
            $pdf->Output('F', $filepath);

            if (!file_exists($filepath)) {
                throw new Exception('Failed to save the PDF file.');
            }

            $attachment = [
                'guid'           => trailingslashit($upload_dir['url']) . $filename,
                'post_mime_type' => 'application/pdf',
                'post_title'     => preg_replace('/\.[^.]+$/', '', $filename),
                'post_content'   => '',
                'post_status'    => 'inherit'
            ];
            
            $attach_id = wp_insert_attachment($attachment, $filepath);
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attach_data = wp_generate_attachment_metadata($attach_id, $filepath);
            wp_update_attachment_metadata($attach_id, $attach_data);

            $uploads_crawler = new AIOHM_KB_Uploads_Crawler();
            $result = $uploads_crawler->add_attachments_to_kb([$attach_id]);

            if (empty($result) || $result[0]['status'] !== 'success') {
                wp_delete_attachment($attach_id, true);
                throw new Exception('Could not add the generated PDF to the knowledge base.');
            }

            wp_send_json_success(['message' => 'Chat successfully saved to your Knowledge Base as: ' . $filename]);

        } catch (Exception $e) {
            wp_send_json_error(['message' => 'Error: ' . $e->getMessage()]);
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
        if (!check_ajax_referer('aiohm_admin_nonce', 'nonce', false) || !current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied.']); return;
        }
        $rag_engine = new AIOHM_KB_RAG_Engine();
        $json_data = $rag_engine->export_knowledge_base();
        wp_send_json_success(['filename' => 'aiohm-knowledge-base-' . date('Y-m-d') . '.json', 'data' => $json_data]);
    }

    public static function handle_reset_kb_ajax() {
        if (!check_ajax_referer('aiohm_admin_nonce', 'nonce', false) || !current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Security check failed.']); return;
        }
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_aiohm_indexed'");
        $table_name = $wpdb->prefix . 'aiohm_vector_entries';
        $wpdb->query("TRUNCATE TABLE {$table_name}");
        wp_send_json_success(['message' => 'The knowledge base has been successfully reset.']);
    }

    public static function handle_toggle_kb_scope_ajax() {
        if (!check_ajax_referer('aiohm_admin_nonce', 'nonce', false) || !current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied.']); return;
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
        if (!check_ajax_referer('aiohm_admin_nonce', 'nonce', false) || !current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Security check failed.']); return;
        }
        if (!isset($_POST['json_data']) || empty($_POST['json_data'])) {
            wp_send_json_error(['message' => 'No data provided for restore.']);
        }
        $json_data = stripslashes($_POST['json_data']);
        try {
            $rag_engine = new AIOHM_KB_RAG_Engine();
            $count = $rag_engine->import_knowledge_base($json_data);
            wp_send_json_success(['message' => $count . ' entries have been successfully restored.']);
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'Restore failed: ' . $e->getMessage()]);
        }
    }

    public static function handle_delete_kb_entry_ajax() {
        if (!check_ajax_referer('aiohm_admin_nonce', 'nonce', false) || !current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Security check failed.']); return;
        }
        if (!isset($_POST['content_id']) || empty($_POST['content_id'])) {
            wp_send_json_error(['message' => 'Content ID is missing.']);
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
        if (!check_ajax_referer('aiohm_brand_soul_nonce', 'nonce', false) || !current_user_can('read')) {
            wp_send_json_error(['message' => 'Security check failed.']); return;
        }
        parse_str($_POST['data'], $form_data);
        $answers = isset($form_data['answers']) ? array_map('sanitize_textarea_field', $form_data['answers']) : [];
        update_user_meta(get_current_user_id(), 'aiohm_brand_soul_answers', $answers);
        wp_send_json_success();
    }

    public static function handle_add_brand_soul_to_kb_ajax() {
        if (!check_ajax_referer('aiohm_brand_soul_nonce', 'nonce', false) || !current_user_can('read')) {
            wp_send_json_error(['message' => 'Security check failed.']); return;
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
}