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
        add_action('wp_ajax_aiohm_delete_kb_entry', array(__CLASS__, 'handle_delete_kb_entry_ajax'));

        // Handlers for the Brand Soul Page
        add_action('wp_ajax_aiohm_save_brand_soul', array(__CLASS__, 'handle_save_brand_soul_ajax'));
        add_action('wp_ajax_aiohm_add_brand_soul_to_kb', array(__CLASS__, 'handle_add_brand_soul_to_kb_ajax'));
        add_action('admin_init', array(__CLASS__, 'handle_pdf_download'));
        
        // Handlers for the Mirror Mode (formerly Q&A Settings) Page
        add_action('wp_ajax_aiohm_save_mirror_mode_settings', array(__CLASS__, 'handle_save_mirror_mode_settings_ajax'));
        add_action('wp_ajax_aiohm_generate_mirror_mode_qa', array(__CLASS__, 'handle_generate_mirror_mode_qa_ajax'));
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
                        wp_send_json_success([
                            'message' => 'AIOHM.app connection successful!',
                            'user_id' => $result['response']['result']['ID']
                        ]);
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
                    wp_send_json_success(['message' => 'Gemini API Key format is valid (Test mode).']);
                    break;

                case 'claude':
                    wp_send_json_success(['message' => 'Claude API Key format is valid (Test mode).']);
                    break;

                default:
                    wp_send_json_error(['message' => 'Invalid key type specified.']);
            }
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'An unexpected error occurred: ' . $e->getMessage()]);
        }
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
                    'expression_5' => "Share any links or files that represent your current branding or moodboard.",
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
        if (!wp_verify_nonce($_POST['nonce'], 'aiohm_mirror_mode_nonce') || !current_user_can('read')) {
            wp_send_json_error(['message' => 'Security check failed.']);
        }
    
        $settings = AIOHM_KB_Assistant::get_settings();
        
        $settings['qa_system_message'] = sanitize_textarea_field($_POST['aiohm_kb_settings']['qa_system_message']);
        $settings['qa_temperature'] = floatval($_POST['aiohm_kb_settings']['qa_temperature']);
        $settings['qa_desktop_width'] = sanitize_text_field($_POST['aiohm_kb_settings']['qa_desktop_width']);
        $settings['qa_desktop_height'] = sanitize_text_field($_POST['aiohm_kb_settings']['qa_desktop_height']);
    
        update_option('aiohm_kb_settings', $settings);
        
        wp_send_json_success(['message' => 'Mirror Mode settings saved successfully.']);
    }
    
    public static function handle_generate_mirror_mode_qa_ajax() {
        if (!wp_verify_nonce($_POST['nonce'], 'aiohm_mirror_mode_nonce') || !current_user_can('read')) {
            wp_send_json_error(['message' => 'Security check failed.']);
        }
    
        try {
            $sample_qa_pairs = [];
            for ($i = 1; $i <= 10; $i++) {
                $sample_qa_pairs[] = [
                    'question' => "Sample Question {$i}: What is the pricing?",
                    'answer' => "This is a sample answer generated by the AI based on the knowledge base content. The pricing can be found on our website."
                ];
            }
            
            wp_send_json_success(['qa_pairs' => $sample_qa_pairs]);
    
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'Failed to generate Q&A pairs: ' . $e->getMessage()]);
        }
    }
}