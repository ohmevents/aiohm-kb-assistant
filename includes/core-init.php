<?php
/**
 * Core initialization and configuration.
 * Final version with all original functions preserved and fixes for saving and loading conversations.
 */
if (!defined('ABSPATH')) exit;

// Prevent class redeclaration errors
if (!class_exists('AIOHM_KB_Core_Init')) {

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

    /**
     * Generate AI-powered conversation title
     */
    private static function generate_conversation_title($user_message, $ai_client, $model) {
        try {
            // Simplified and more reliable title generation
            $title_prompt = "Create a short title (3-5 words) for this conversation. Remove quotes. Examples: Website Design Project, Marketing Strategy Planning, Brand Development Discussion. Message: " . substr($user_message, 0, 150);
            
            $title = $ai_client->get_chat_completion(
                "Create concise conversation titles without quotes or special characters.",
                $title_prompt,
                0.2, // Even lower temperature for consistency
                $model
            );
            
            // Clean and validate the title
            $title = trim(strip_tags($title));
            $title = str_replace(['"', "'", '`', '«', '»'], '', $title); // Remove all quote types
            $title = preg_replace('/[^\w\s-]/', '', $title); // Remove special chars except hyphens
            $title = preg_replace('/\s+/', ' ', $title); // Normalize whitespace
            $title = trim($title);
            
            // Ensure reasonable length
            if (strlen($title) > 50) {
                $title = mb_strimwidth($title, 0, 47, '...');
            }
            
            // More robust fallback check
            if (strlen($title) < 3 || empty($title) || $title === '...' || strtolower($title) === 'new chat') {
                $title = self::create_fallback_title($user_message);
            }
            
            // Log the generated title for debugging
            AIOHM_KB_Assistant::log('Generated conversation title: "' . $title . '" from message: "' . substr($user_message, 0, 50) . '"', 'info');
            
            return $title;
            
        } catch (Exception $e) {
            // Fallback to smart title generation
            AIOHM_KB_Assistant::log('Title generation error: ' . $e->getMessage(), 'warning');
            return self::create_fallback_title($user_message);
        }
    }

    /**
     * Create a smart fallback title from user message
     */
    private static function create_fallback_title($user_message) {
        // Extract key words and create a meaningful title
        $message = strtolower(trim($user_message));
        
        // Common patterns for smart titles
        if (strpos($message, 'help') !== false && strpos($message, 'with') !== false) {
            return 'Help Request';
        } elseif (strpos($message, 'create') !== false || strpos($message, 'make') !== false) {
            return 'Creation Project';
        } elseif (strpos($message, 'plan') !== false || strpos($message, 'strategy') !== false) {
            return 'Planning Session';
        } elseif (strpos($message, 'write') !== false || strpos($message, 'content') !== false) {
            return 'Content Writing';
        } elseif (strpos($message, 'design') !== false) {
            return 'Design Discussion';
        } elseif (strpos($message, 'market') !== false) {
            return 'Marketing Discussion';
        } elseif (strpos($message, 'brand') !== false) {
            return 'Brand Development';
        } elseif (strpos($message, 'website') !== false || strpos($message, 'web') !== false) {
            return 'Web Development';
        } elseif (strpos($message, 'question') !== false) {
            return 'General Questions';
        } else {
            // Use first few words as title
            $words = explode(' ', $message);
            $title_words = array_slice($words, 0, 3);
            $title = ucwords(implode(' ', $title_words));
            return strlen($title) > 3 ? $title : 'General Discussion';
        }
    }

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
        add_action('wp_ajax_nopriv_aiohm_save_mirror_mode_settings', array(__CLASS__, 'handle_save_mirror_mode_settings_ajax'));
        
        // Add hook to monitor settings changes
        add_action('update_option_aiohm_kb_settings', array(__CLASS__, 'monitor_settings_changes'), 10, 2);
        add_action('delete_option_aiohm_kb_settings', array(__CLASS__, 'monitor_settings_deletion'), 10, 1);
        add_action('wp_ajax_aiohm_generate_mirror_mode_qa', array(__CLASS__, 'handle_generate_mirror_mode_qa_ajax'));
        add_action('wp_ajax_aiohm_test_mirror_mode_chat', array(__CLASS__, 'handle_test_mirror_mode_chat_ajax'));
        add_action('wp_ajax_aiohm_save_muse_mode_settings', array(__CLASS__, 'handle_save_muse_mode_settings_ajax'));
        add_action('wp_ajax_aiohm_private_assistant_chat', array(__CLASS__, 'handle_private_assistant_chat_ajax'));
        add_action('wp_ajax_aiohm_private_chat', array(__CLASS__, 'handle_private_assistant_chat_ajax'));
        add_action('wp_ajax_aiohm_test_muse_mode_chat', array(__CLASS__, 'handle_test_muse_mode_chat_ajax'));
        add_action('wp_ajax_nopriv_aiohm_frontend_chat', array(__CLASS__, 'handle_frontend_chat_ajax'));
        add_action('wp_ajax_aiohm_frontend_chat', array(__CLASS__, 'handle_frontend_chat_ajax'));
        add_action('wp_ajax_nopriv_aiohm_search_knowledge', array(__CLASS__, 'handle_search_knowledge_ajax'));
        add_action('wp_ajax_aiohm_search_knowledge', array(__CLASS__, 'handle_search_knowledge_ajax'));
        add_action('wp_ajax_aiohm_admin_search_knowledge', array(__CLASS__, 'handle_admin_search_knowledge_ajax'));

        // --- Existing FIX for project and conversations ---
        add_action('wp_ajax_aiohm_get_project_conversations', array(__CLASS__, 'handle_get_project_conversations_ajax'));
        add_action('wp_ajax_aiohm_create_project', array(__CLASS__, 'handle_create_project_ajax'));
        
        // --- NEW FIX: Add the missing action handler for loading all projects and conversations ---
        add_action('wp_ajax_aiohm_load_history', array(__CLASS__, 'handle_load_history_ajax'));
        add_action('wp_ajax_aiohm_load_conversation', array(__CLASS__, 'handle_load_conversation_ajax'));
        add_action('wp_ajax_aiohm_save_project_notes', array(__CLASS__, 'handle_save_project_notes_ajax'));
        add_action('wp_ajax_aiohm_load_project_notes', array(__CLASS__, 'handle_load_project_notes_ajax'));
        add_action('wp_ajax_aiohm_delete_project', array(__CLASS__, 'handle_delete_project_ajax'));
        add_action('wp_ajax_aiohm_delete_conversation', array(__CLASS__, 'handle_delete_conversation_ajax'));
        add_action('wp_ajax_aiohm_create_conversation', array(__CLASS__, 'handle_create_conversation_ajax'));
        add_action('wp_ajax_aiohm_upload_project_files', array(__CLASS__, 'handle_upload_project_files_ajax'));
        add_action('wp_ajax_aiohm_get_brand_soul_content', array(__CLASS__, 'handle_get_brand_soul_content_ajax'));
        add_action('wp_ajax_aiohm_get_content_for_view', array(__CLASS__, 'handle_get_content_for_view_ajax'));
        add_action('wp_ajax_aiohm_get_usage_stats', array(__CLASS__, 'handle_get_usage_stats_ajax'));
        add_action('wp_ajax_aiohm_download_conversation_pdf', array(__CLASS__, 'handle_download_conversation_pdf_ajax'));
        add_action('wp_ajax_aiohm_add_conversation_to_kb', array(__CLASS__, 'handle_add_conversation_to_kb_ajax'));
    }
    
    /**
     * Handles the AJAX request to load all projects and recent conversations for the current user.
     * This directly supports the `loadHistory()` function in JavaScript.
     */
    public static function handle_load_history_ajax() {
        check_ajax_referer('aiohm_private_chat_nonce', 'nonce');
        if (!current_user_can('read')) {
            wp_send_json_error(['message' => 'Permission Denied']);
            wp_die();
        }

        global $wpdb;
        $user_id = get_current_user_id();

        // Fetch projects
        $projects_table = $wpdb->prefix . 'aiohm_projects';
        $projects = $wpdb->get_results($wpdb->prepare(
            "SELECT id, project_name as name FROM {$projects_table} WHERE user_id = %d ORDER BY creation_date DESC",
            $user_id
        ), ARRAY_A);

        // Fetch recent conversations (e.g., last 10, or all if not too many)
        // Adjust LIMIT as needed for performance if users have many conversations
        $conversations_table = $wpdb->prefix . 'aiohm_conversations';
        $conversations = $wpdb->get_results($wpdb->prepare(
            "SELECT id, title, project_id FROM {$conversations_table} WHERE user_id = %d ORDER BY updated_at DESC LIMIT 50",
            $user_id
        ), ARRAY_A);

        wp_send_json_success(['projects' => $projects, 'conversations' => $conversations]);
        wp_die();
    }

    /**
     * Handles the AJAX request to load a specific conversation's messages.
     */
    public static function handle_load_conversation_ajax() {
        check_ajax_referer('aiohm_private_chat_nonce', 'nonce');
        if (!current_user_can('read')) {
            wp_send_json_error(['message' => 'Permission Denied']);
            wp_die();
        }
    
        $conversation_id = isset($_POST['conversation_id']) ? intval($_POST['conversation_id']) : 0;
        if (empty($conversation_id)) {
            wp_send_json_error(['message' => 'Invalid Conversation ID.']);
            wp_die();
        }
    
        global $wpdb;
        $user_id = get_current_user_id();
        $messages_table = $wpdb->prefix . 'aiohm_messages';
        $conversations_table = $wpdb->prefix . 'aiohm_conversations';
        $projects_table = $wpdb->prefix . 'aiohm_projects';
    
        // Verify conversation belongs to the user and get its project ID
        $conversation_info = $wpdb->get_row($wpdb->prepare(
            "SELECT c.id, c.title, c.project_id, p.project_name FROM {$conversations_table} c JOIN {$projects_table} p ON c.project_id = p.id WHERE c.id = %d AND c.user_id = %d",
            $conversation_id,
            $user_id
        ), ARRAY_A);
    
        if (!$conversation_info) {
            wp_send_json_error(['message' => 'Conversation not found or not accessible.']);
            wp_die();
        }
    
        // Fetch messages for the conversation
        $messages = $wpdb->get_results($wpdb->prepare(
            "SELECT sender, content as message_content FROM {$messages_table} WHERE conversation_id = %d ORDER BY created_at ASC",
            $conversation_id
        ), ARRAY_A);
    
        wp_send_json_success([
            'messages' => $messages,
            'project_id' => $conversation_info['project_id'],
            'project_name' => $conversation_info['project_name'],
            'conversation_title' => $conversation_info['title']
        ]);
        wp_die();
    }

    /**
     * Handles AJAX request to save project notes.
     */
    public static function handle_save_project_notes_ajax() {
        check_ajax_referer('aiohm_private_chat_nonce', 'nonce');
        if (!current_user_can('read')) {
            wp_send_json_error(['message' => 'Permission Denied']);
            wp_die();
        }
    
        $project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
        $note_content = isset($_POST['note_content']) ? sanitize_textarea_field(stripslashes($_POST['note_content'])) : '';
    
        if (empty($project_id)) {
            wp_send_json_error(['message' => 'Invalid Project ID.']);
            wp_die();
        }
    
        global $wpdb;
        $table_name = $wpdb->prefix . 'aiohm_projects';
        $user_id = get_current_user_id();
    
        // Ensure the project belongs to the current user
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(id) FROM {$table_name} WHERE id = %d AND user_id = %d",
            $project_id,
            $user_id
        ));
    
        if (!$exists) {
            wp_send_json_error(['message' => 'Project not found or not owned by user.']);
            wp_die();
        }
    
        $updated = $wpdb->update(
            $table_name,
            ['notes' => $note_content],
            ['id' => $project_id],
            ['%s'],
            ['%d']
        );
    
        if ($updated !== false) {
            wp_send_json_success(['message' => 'Notes saved.']);
        } else {
            wp_send_json_error(['message' => 'Failed to save notes.']);
        }
        wp_die();
    }

    /**
     * Handles AJAX request to load project notes.
     */
    public static function handle_load_project_notes_ajax() {
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
        $table_name = $wpdb->prefix . 'aiohm_projects';
        $user_id = get_current_user_id();
    
        $note_content = $wpdb->get_var($wpdb->prepare(
            "SELECT notes FROM {$table_name} WHERE id = %d AND user_id = %d",
            $project_id,
            $user_id
        ));
    
        if ($note_content !== null) {
            wp_send_json_success(['note_content' => $note_content]);
        } else {
            wp_send_json_error(['message' => 'Project notes not found or not accessible.']);
        }
        wp_die();
    }

    /**
     * Handles AJAX request to delete a project.
     */
    public static function handle_delete_project_ajax() {
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
    
        // Delete associated conversations first
        $conversations_table = $wpdb->prefix . 'aiohm_conversations';
        $wpdb->delete($conversations_table, ['project_id' => $project_id, 'user_id' => $user_id], ['%d', '%d']);
    
        // Delete the project
        $projects_table = $wpdb->prefix . 'aiohm_projects';
        $deleted = $wpdb->delete($projects_table, ['id' => $project_id, 'user_id' => $user_id], ['%d', '%d']);
    
        if ($deleted) {
            wp_send_json_success(['message' => 'Project and its conversations deleted.']);
        } else {
            wp_send_json_error(['message' => 'Failed to delete project or project not found.']);
        }
        wp_die();
    }
    
    /**
     * Handles AJAX request to delete a conversation.
     */
    public static function handle_delete_conversation_ajax() {
        check_ajax_referer('aiohm_private_chat_nonce', 'nonce');
        if (!current_user_can('read')) {
            wp_send_json_error(['message' => 'Permission Denied']);
            wp_die();
        }
    
        $conversation_id = isset($_POST['conversation_id']) ? intval($_POST['conversation_id']) : 0;
    
        if (empty($conversation_id)) {
            wp_send_json_error(['message' => 'Invalid Conversation ID.']);
            wp_die();
        }
    
        global $wpdb;
        $user_id = get_current_user_id();
    
        // Delete associated messages first
        $messages_table = $wpdb->prefix . 'aiohm_messages';
        $wpdb->delete($messages_table, ['conversation_id' => $conversation_id], ['%d']);
    
        // Delete the conversation
        $conversations_table = $wpdb->prefix . 'aiohm_conversations';
        $deleted = $wpdb->delete($conversations_table, ['id' => $conversation_id, 'user_id' => $user_id], ['%d', '%d']);
    
        if ($deleted) {
            wp_send_json_success(['message' => 'Conversation and its messages deleted.']);
        } else {
            wp_send_json_error(['message' => 'Failed to delete conversation or conversation not found.']);
        }
        wp_die();
    }

    /**
     * Handles AJAX request to create a new conversation.
     */
    public static function handle_create_conversation_ajax() {
        check_ajax_referer('aiohm_private_chat_nonce', 'nonce');
        if (!current_user_can('read')) {
            wp_send_json_error(['message' => 'Permission Denied']);
            wp_die();
        }
    
        $project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
        $title = isset($_POST['title']) ? sanitize_text_field(stripslashes($_POST['title'])) : 'New Chat';
    
        if (empty($project_id)) {
            wp_send_json_error(['message' => 'Invalid Project ID.']);
            wp_die();
        }
    
        global $wpdb;
        $user_id = get_current_user_id();
        $projects_table = $wpdb->prefix . 'aiohm_projects';
    
        // Verify project belongs to the current user
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(id) FROM {$projects_table} WHERE id = %d AND user_id = %d",
            $project_id,
            $user_id
        ));
    
        if (!$exists) {
            wp_send_json_error(['message' => 'Project not found or not owned by user.']);
            wp_die();
        }
    
        $conversation_id = self::create_conversation_internal($user_id, $project_id, $title);
    
        if ($conversation_id) {
            wp_send_json_success(['conversation_id' => $conversation_id, 'title' => $title]);
        } else {
            wp_send_json_error(['message' => 'Failed to create conversation.']);
        }
        wp_die();
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
        
        $project_name = isset($_POST['name']) ? sanitize_text_field(stripslashes($_POST['name'])) : '';
        if (empty($project_name)) { wp_send_json_error(['message' => 'Project name cannot be empty.']); wp_die(); }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'aiohm_projects';
        $user_id = get_current_user_id();
        
        $result = $wpdb->insert($table_name, ['user_id' => $user_id, 'project_name' => $project_name], ['%d', '%s']);
        
        if ($result === false) {
             wp_send_json_error(['message' => 'Could not save the project to the database.']);
        } else {
            $project_id = $wpdb->insert_id;
            wp_send_json_success(['new_project_id' => $project_id, 'name' => $project_name]);
        }
        wp_die();
    }

    public static function handle_private_assistant_chat_ajax() {
        // FIX: Changed 'answer' to 'message' for security check error.
        if (!check_ajax_referer('aiohm_private_chat_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Security check failed.']);
        }
    
        // FIX: Changed 'answer' to 'message' for permission error.
        if (!current_user_can('administrator') && !current_user_can('ohm_brand_collaborator')) {
            wp_send_json_error(['message' => 'You do not have permission to use this feature.']);
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
            
            // Enhanced formatting instructions for better readability
            $formatting_instructions = "\n\n--- FORMATTING INSTRUCTIONS ---\n" .
                "Format your responses with clear structure using:\n" .
                "- **Bold headings** for main topics\n" .
                "- Bullet points for lists\n" .
                "- Numbered lists for step-by-step instructions\n" .
                "- Tables when presenting comparative data\n" .
                "- Use line breaks for better readability\n" .
                "- Keep paragraphs concise and focused\n\n";
            
            $final_system_message = $system_prompt . $formatting_instructions . "--- CONTEXT ---\n" . $context_string;
    
            $answer = $ai_client->get_chat_completion($final_system_message, $user_message, $temperature, $model);

            if (is_null($conversation_id) || empty($conversation_id)) {
                // Generate AI-powered conversation title
                $conversation_title = self::generate_conversation_title($user_message, $ai_client, $model);
                $conversation_id = self::create_conversation_internal($user_id, $project_id, $conversation_title);
                if (!$conversation_id) {
                    AIOHM_KB_Assistant::log('Failed to create conversation record.', 'error');
                }
            }
            
            if ($conversation_id) {
                self::add_message_to_conversation_internal($conversation_id, 'user', $user_message);
                self::add_message_to_conversation_internal($conversation_id, 'ai', $answer);
            }
    
            // This was already fixed in the last step to use 'reply'
            wp_send_json_success(['reply' => $answer, 'conversation_id' => $conversation_id]);
    
        } catch (Exception $e) {
            AIOHM_KB_Assistant::log('Private Assistant Error: ' . $e->getMessage(), 'error');
            // FIX: Changed 'answer' to 'message' for the catch block error.
            wp_send_json_error(['message' => 'An error occurred while processing your request: ' . $e->getMessage()]); // Include exception message for clarity
        }
    }

    public static function handle_frontend_chat_ajax() {
        if (!check_ajax_referer('aiohm_chat_nonce', 'nonce', false)) {
            wp_send_json_error(['reply' => 'Security check failed.']);
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
            $temperature = floatval($mirror_settings['qa_temperature'] ?? 0.8);
            $model = $mirror_settings['ai_model'] ?? 'gpt-3.5-turbo';
            
            // Apply variable replacements to system message
            $replacements = [
                '{context}'        => $context_string,
                '%site_name%'      => get_bloginfo('name'),
                '%site_tagline%'   => get_bloginfo('description'),
                '%business_name%'  => $mirror_settings['business_name'] ?? get_bloginfo('name'),
                '%day_of_week%'    => wp_date('l'),
                '%current_date%'   => wp_date(get_option('date_format')),
                '%current_time%'   => wp_date(get_option('time_format')),
            ];
            $final_system_message = str_replace(array_keys($replacements), array_values($replacements), $system_message);

            $answer = $ai_client->get_chat_completion($final_system_message, $user_message, $temperature, $model);

            wp_send_json_success(['reply' => $answer]);

        } catch (Exception $e) {
            AIOHM_KB_Assistant::log('Frontend Chat Error: ' . $e->getMessage(), 'error');
            wp_send_json_error(['reply' => 'An error occurred while processing your request. Please try again.']);
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
        if (!check_ajax_referer('aiohm_mirror_mode_nonce', 'aiohm_mirror_mode_nonce_field', false) || !current_user_can('read')) {
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
        if (!check_ajax_referer('aiohm_muse_mode_nonce', 'aiohm_muse_mode_nonce_field', false) || !current_user_can('read')) {
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
                    
                    // Check prerequisites
                    $settings = AIOHM_KB_Assistant::get_settings();
                    $api_key_exists = !empty($settings['openai_api_key']);
                    
                    if (!$api_key_exists) {
                        throw new Exception('OpenAI API key is not configured. Please add your API key in settings.');
                    }
                    
                    $crawler = new AIOHM_KB_Site_Crawler();
                    $results = $crawler->add_items_to_kb($item_ids);
                    
                    // Categorize results
                    $errors = array_filter($results, function($item) { return $item['status'] === 'error'; });
                    $successes = array_filter($results, function($item) { return $item['status'] === 'success'; });
                    $skipped = array_filter($results, function($item) { return $item['status'] === 'skipped'; });
                    
                    if (!empty($errors) && empty($successes)) {
                        // All items failed
                        $error_messages = array_column($errors, 'error_message');
                        throw new Exception('All items failed to add: ' . implode(', ', $error_messages));
                    } else if (!empty($errors)) {
                        // Some items failed - return partial success with error details
                        $all_items = $crawler->find_all_content();
                        wp_send_json(['success' => false, 'data' => [
                            'message' => 'Some items failed to add to knowledge base',
                            'processed_items' => $results, 
                            'all_items' => $all_items,
                            'errors' => $errors,
                            'successes' => $successes
                        ]]);
                    } else if (!empty($skipped) && empty($successes)) {
                        // All items were skipped
                        $skip_reasons = array_column($skipped, 'reason');
                        $message = 'All items were skipped: ' . implode(', ', $skip_reasons);
                        $all_items = $crawler->find_all_content();
                        wp_send_json(['success' => false, 'data' => [
                            'message' => $message,
                            'processed_items' => $results, 
                            'all_items' => $all_items,
                            'skipped' => $skipped
                        ]]);
                    } else if (!empty($skipped)) {
                        // Some items were skipped
                        $all_items = $crawler->find_all_content();
                        $skip_count = count($skipped);
                        $success_count = count($successes);
                        wp_send_json(['success' => true, 'data' => [
                            'message' => "Processing complete: {$success_count} added, {$skip_count} skipped",
                            'processed_items' => $results, 
                            'all_items' => $all_items,
                            'skipped' => $skipped,
                            'successes' => $successes
                        ]]);
                    } else {
                        // All items succeeded
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
                    
                    // Check for any errors in the results
                    $errors = array_filter($results, function($item) { return $item['status'] === 'error'; });
                    $successes = array_filter($results, function($item) { return $item['status'] === 'success'; });
                    
                    if (!empty($errors) && empty($successes)) {
                        // All items failed
                        $error_messages = array_column($errors, 'error');
                        throw new Exception('All items failed to add: ' . implode(', ', $error_messages));
                    } else if (!empty($errors)) {
                        // Some items failed - return partial success with error details
                        $updated_files_list = $crawler->find_all_supported_attachments();
                        wp_send_json(['success' => false, 'data' => [
                            'message' => 'Some items failed to add to knowledge base',
                            'processed_items' => $results, 
                            'items' => $updated_files_list,
                            'errors' => $errors,
                            'successes' => $successes
                        ]]);
                    } else {
                        // All items succeeded
                        $updated_files_list = $crawler->find_all_supported_attachments();
                        wp_send_json_success(['processed_items' => $results, 'items' => $updated_files_list]);
                    }
                    break;
                default:
                    throw new Exception('Invalid scan type specified.');
            }
        } catch (Exception $e) {
            error_log('AIOHM KB Error: ' . $e->getMessage());
            wp_send_json_error(['message' => 'Scan failed: ' . $e->getMessage()]);
        } catch (Error $e) {
            error_log('AIOHM KB Fatal Error: ' . $e->getMessage());
            wp_send_json_error(['message' => 'Fatal error: ' . $e->getMessage()]);
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
                case 'claude':
                    $ai_client = new AIOHM_KB_AI_GPT_Client(['claude_api_key' => $api_key]);
                    $result = $ai_client->test_claude_api_connection();
                    if ($result['success']) {
                        wp_send_json_success(['message' => 'Claude connection successful!']);
                    } else {
                        wp_send_json_error(['message' => 'Claude connection failed: ' . ($result['error'] ?? 'Unknown error.')]);
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
        // Force log this immediately
        error_log('=== MIRROR MODE SAVE HANDLER CALLED AT ' . date('Y-m-d H:i:s') . ' ===');
        error_log('POST data: ' . print_r($_POST, true));
        error_log('Current user ID: ' . get_current_user_id());
        error_log('User capabilities: ' . print_r(wp_get_current_user()->allcaps, true));
        error_log('Action hook: ' . ($_POST['action'] ?? 'NO ACTION'));
        error_log('Nonce field: ' . ($_POST['aiohm_mirror_mode_nonce_field'] ?? 'NO NONCE'));
        
        if (!check_ajax_referer('aiohm_mirror_mode_nonce', 'aiohm_mirror_mode_nonce_field', false)) {
            error_log('NONCE CHECK FAILED');
            wp_send_json_error(['message' => 'Nonce verification failed.']);
        }
        
        if (!current_user_can('edit_posts')) {
            error_log('USER CAPABILITY CHECK FAILED');
            wp_send_json_error(['message' => 'Insufficient permissions.']);
        }
        
        error_log('Security checks passed');
        
        parse_str($_POST['form_data'], $form_data);
        error_log('Parsed form data: ' . print_r($form_data, true));
        
        $settings_input = $form_data['aiohm_kb_settings']['mirror_mode'] ?? [];
        error_log('Settings input: ' . print_r($settings_input, true));
        
        if (empty($settings_input)) {
            error_log('SETTINGS INPUT IS EMPTY');
            wp_send_json_error(['message' => 'No settings data received.']);
        }
        
        // Get current settings from database (not the merged defaults)
        $settings = get_option('aiohm_kb_settings', []);
        
        // Ensure mirror_mode structure exists
        if (!isset($settings['mirror_mode'])) {
            $settings['mirror_mode'] = [];
        }
        
        $settings['mirror_mode']['business_name'] = sanitize_text_field($settings_input['business_name'] ?? '');
        $settings['mirror_mode']['qa_system_message'] = sanitize_textarea_field($settings_input['qa_system_message'] ?? '');
        $settings['mirror_mode']['qa_temperature'] = floatval($settings_input['qa_temperature'] ?? 0.7);
        $settings['mirror_mode']['primary_color'] = sanitize_hex_color($settings_input['primary_color'] ?? '#1f5014');
        $settings['mirror_mode']['background_color'] = sanitize_hex_color($settings_input['background_color'] ?? '#f0f4f8');
        $settings['mirror_mode']['text_color'] = sanitize_hex_color($settings_input['text_color'] ?? '#ffffff');
        $settings['mirror_mode']['ai_avatar'] = esc_url_raw($settings_input['ai_avatar'] ?? '');
        $settings['mirror_mode']['meeting_button_url'] = esc_url_raw($settings_input['meeting_button_url'] ?? '');
        $settings['mirror_mode']['ai_model'] = sanitize_text_field($settings_input['ai_model'] ?? 'gpt-3.5-turbo');

        error_log('About to save settings: ' . print_r($settings['mirror_mode'], true));
        
        // Get current settings to compare
        $current_settings = get_option('aiohm_kb_settings', []);
        error_log('Current settings before save: ' . print_r($current_settings['mirror_mode'] ?? 'NOT FOUND', true));
        
        // Debug the current state before saving
        error_log('==== BEFORE SAVE ====');
        error_log('Current DB settings: ' . print_r(get_option('aiohm_kb_settings', []), true));
        error_log('Settings to save: ' . print_r($settings, true));
        
        // Save the settings using WordPress API - don't use delete/add method
        error_log('SAVING SETTINGS WITH update_option');
        error_log('Settings array before save: ' . print_r($settings, true));
        
        // Force update even if the value hasn't changed
        $result = update_option('aiohm_kb_settings', $settings, true);
        error_log('update_option result: ' . ($result ? 'TRUE' : 'FALSE'));
        
        // If update_option returns false, try direct database update
        if (!$result) {
            error_log('update_option failed, trying direct database update');
            global $wpdb;
            $option_name = 'aiohm_kb_settings';
            $option_value = serialize($settings);
            $autoload = 'yes';
            
            $result = $wpdb->replace(
                $wpdb->options,
                array(
                    'option_name' => $option_name,
                    'option_value' => $option_value,
                    'autoload' => $autoload
                ),
                array('%s', '%s', '%s')
            );
            error_log('Direct database update result: ' . ($result ? 'SUCCESS' : 'FAILED'));
        }
        
        // Force clear all caches
        wp_cache_delete('aiohm_kb_settings', 'options');
        wp_cache_flush();
        
        // Verify the settings were actually saved
        $verify_settings = get_option('aiohm_kb_settings', []);
        error_log('Settings after save verification: ' . print_r($verify_settings, true));
        
        if (isset($verify_settings['mirror_mode']) && 
            isset($verify_settings['mirror_mode']['business_name']) &&
            $verify_settings['mirror_mode']['business_name'] === $settings['mirror_mode']['business_name']) {
            error_log('VERIFICATION SUCCESSFUL: Settings were saved correctly');
        } else {
            error_log('VERIFICATION FAILED: Settings may not have been saved');
            error_log('Expected business_name: ' . $settings['mirror_mode']['business_name']);
            error_log('Actual business_name: ' . ($verify_settings['mirror_mode']['business_name'] ?? 'NOT FOUND'));
        }
        
        // Check what actually got saved using the plugin's method
        error_log('==== AFTER SAVE ====');
        $saved_settings = AIOHM_KB_Assistant::get_settings();
        error_log('Saved DB settings: ' . print_r($saved_settings['mirror_mode'], true));
        
        // Verify the specific field we changed
        if (isset($saved_settings['mirror_mode']['business_name'])) {
            error_log('SUCCESS: Business name saved as: ' . $saved_settings['mirror_mode']['business_name']);
        } else {
            error_log('ERROR: mirror_mode[business_name] not found in saved settings');
        }
        
        wp_send_json_success(['message' => 'Mirror Mode settings saved successfully.']);
    }
    
    public static function monitor_settings_changes($old_value, $new_value) {
        error_log('=== SETTINGS CHANGE DETECTED ===');
        error_log('Old value has mirror_mode: ' . (isset($old_value['mirror_mode']) ? 'YES' : 'NO'));
        error_log('New value has mirror_mode: ' . (isset($new_value['mirror_mode']) ? 'YES' : 'NO'));
        error_log('Caller: ' . wp_debug_backtrace_summary());
        
        if (isset($old_value['mirror_mode']) && !isset($new_value['mirror_mode'])) {
            error_log('WARNING: Mirror mode settings were REMOVED!');
            error_log('Full backtrace: ' . wp_debug_backtrace_summary());
        }
        
        if (isset($old_value['muse_mode']) && !isset($new_value['muse_mode'])) {
            error_log('WARNING: Muse mode settings were REMOVED!');
            error_log('Full backtrace: ' . wp_debug_backtrace_summary());
        }
    }
    
    public static function monitor_settings_deletion($option_name) {
        error_log('=== SETTINGS DELETION DETECTED ===');
        error_log('Option name: ' . $option_name);
        error_log('Caller: ' . wp_debug_backtrace_summary());
        error_log('This might be causing the settings to disappear!');
    }

    public static function handle_save_muse_mode_settings_ajax() {
        error_log('=== MUSE MODE SAVE HANDLER CALLED ===');
        error_log('POST data: ' . print_r($_POST, true));
        error_log('Current user ID: ' . get_current_user_id());
        error_log('User capabilities: ' . print_r(wp_get_current_user()->allcaps, true));
        
        if (!check_ajax_referer('aiohm_muse_mode_nonce', 'aiohm_muse_mode_nonce_field', false)) {
            error_log('MUSE NONCE CHECK FAILED');
            wp_send_json_error(['message' => 'Nonce verification failed.']);
        }
        
        if (!current_user_can('edit_posts')) {
            error_log('MUSE USER CAPABILITY CHECK FAILED');
            wp_send_json_error(['message' => 'Insufficient permissions.']);
        }
        
        error_log('Muse security checks passed');

        parse_str($_POST['form_data'], $form_data);
        error_log('Muse parsed form data: ' . print_r($form_data, true));
        
        $muse_input = $form_data['aiohm_kb_settings']['muse_mode'] ?? [];
        error_log('Muse settings input: ' . print_r($muse_input, true));

        if (empty($muse_input)) {
            error_log('MUSE SETTINGS INPUT IS EMPTY');
            wp_send_json_error(['message' => 'No settings data received.']);
        }

        // Get current settings from database (not the merged defaults)
        $settings = get_option('aiohm_kb_settings', []);
        
        // Ensure muse_mode structure exists
        if (!isset($settings['muse_mode'])) {
            $settings['muse_mode'] = [];
        }
        
        $settings['muse_mode']['assistant_name'] = sanitize_text_field($muse_input['assistant_name'] ?? 'Muse');
        $settings['muse_mode']['system_prompt'] = sanitize_textarea_field($muse_input['system_prompt'] ?? '');
        $settings['muse_mode']['ai_model'] = sanitize_text_field($muse_input['ai_model'] ?? 'gpt-4');
        $settings['muse_mode']['temperature'] = floatval($muse_input['temperature'] ?? 0.7);
        $settings['muse_mode']['start_fullscreen'] = isset($muse_input['start_fullscreen']) ? 1 : 0;
        $settings['muse_mode']['brand_archetype'] = sanitize_text_field($muse_input['brand_archetype'] ?? '');

        error_log('About to save Muse settings: ' . print_r($settings['muse_mode'], true));
        
        // Get current settings to compare
        $current_settings = get_option('aiohm_kb_settings', []);
        error_log('Current Muse settings before save: ' . print_r($current_settings['muse_mode'] ?? 'NOT FOUND', true));
        
        // Debug the current state before saving
        error_log('==== MUSE BEFORE SAVE ====');
        error_log('Current DB settings: ' . print_r(get_option('aiohm_kb_settings', []), true));
        error_log('Settings to save: ' . print_r($settings, true));
        
        // Save the settings using WordPress API - don't use delete/add method
        error_log('MUSE SAVING SETTINGS WITH update_option');
        error_log('Muse settings array before save: ' . print_r($settings, true));
        
        // Force update even if the value hasn't changed
        $result = update_option('aiohm_kb_settings', $settings, true);
        error_log('Muse update_option result: ' . ($result ? 'TRUE' : 'FALSE'));
        
        // If update_option returns false, try direct database update
        if (!$result) {
            error_log('Muse update_option failed, trying direct database update');
            global $wpdb;
            $option_name = 'aiohm_kb_settings';
            $option_value = serialize($settings);
            $autoload = 'yes';
            
            $result = $wpdb->replace(
                $wpdb->options,
                array(
                    'option_name' => $option_name,
                    'option_value' => $option_value,
                    'autoload' => $autoload
                ),
                array('%s', '%s', '%s')
            );
            error_log('Muse direct database update result: ' . ($result ? 'SUCCESS' : 'FAILED'));
        }
        
        // Force clear all caches
        wp_cache_delete('aiohm_kb_settings', 'options');
        wp_cache_flush();
        
        // Verify the settings were actually saved
        $verify_settings = get_option('aiohm_kb_settings', []);
        error_log('Muse settings after save verification: ' . print_r($verify_settings, true));
        
        if (isset($verify_settings['muse_mode']) && 
            isset($verify_settings['muse_mode']['assistant_name']) &&
            $verify_settings['muse_mode']['assistant_name'] === $settings['muse_mode']['assistant_name']) {
            error_log('MUSE VERIFICATION SUCCESSFUL: Settings were saved correctly');
        } else {
            error_log('MUSE VERIFICATION FAILED: Settings may not have been saved');
            error_log('Expected assistant_name: ' . $settings['muse_mode']['assistant_name']);
            error_log('Actual assistant_name: ' . ($verify_settings['muse_mode']['assistant_name'] ?? 'NOT FOUND'));
        }
        
        // Check what actually got saved using the plugin's method
        error_log('==== MUSE AFTER SAVE ====');
        $saved_settings = AIOHM_KB_Assistant::get_settings();
        error_log('Muse saved DB settings: ' . print_r($saved_settings['muse_mode'], true));
        
        // Verify the specific field we changed
        if (isset($saved_settings['muse_mode']['assistant_name'])) {
            error_log('SUCCESS: Assistant name saved as: ' . $saved_settings['muse_mode']['assistant_name']);
        } else {
            error_log('ERROR: muse_mode[assistant_name] not found in saved settings');
        }
        
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

    /**
     * Handle file uploads for projects
     */
    public static function handle_upload_project_files_ajax() {
        // Security checks
        if (!check_ajax_referer('aiohm_private_chat_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Security check failed.']);
        }

        if (!current_user_can('read')) {
            wp_send_json_error(['message' => 'Insufficient permissions.']);
        }

        // Check if files were uploaded
        if (empty($_FILES['files'])) {
            wp_send_json_error(['message' => 'No files uploaded.']);
        }

        // Get project ID
        $project_id = intval($_POST['project_id'] ?? 0);
        if (!$project_id) {
            wp_send_json_error(['message' => 'Invalid project ID.']);
        }

        // Verify user owns the project
        global $wpdb;
        $user_id = get_current_user_id();
        $project_table = $wpdb->prefix . 'aiohm_projects';
        $project = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$project_table} WHERE id = %d AND user_id = %d",
            $project_id, $user_id
        ));

        if (!$project) {
            wp_send_json_error(['message' => 'Project not found or access denied.']);
        }

        // Define upload directory
        $upload_base_dir = wp_upload_dir();
        $project_upload_dir = $upload_base_dir['basedir'] . '/aiohm_project_files/project_' . $project_id;
        $project_upload_url = $upload_base_dir['baseurl'] . '/aiohm_project_files/project_' . $project_id;

        // Create directory if it doesn't exist
        if (!file_exists($project_upload_dir)) {
            wp_mkdir_p($project_upload_dir);
        }

        // Define allowed file types
        $allowed_types = [
            'txt' => 'text/plain',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'm4a' => 'audio/mp4',
            'ogg' => 'audio/ogg'
        ];

        $uploaded_files = [];
        $errors = [];

        // Handle multiple files
        $files = $_FILES['files'];
        $file_count = count($files['name']);

        for ($i = 0; $i < $file_count; $i++) {
            $file = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i]
            ];

            // Check for upload errors
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errors[] = "Error uploading {$file['name']}: Upload error code {$file['error']}";
                continue;
            }

            // Check file size (limit to 50MB)
            $max_size = 50 * 1024 * 1024;
            if ($file['size'] > $max_size) {
                $errors[] = "File {$file['name']} is too large (max 50MB)";
                continue;
            }

            // Check file type
            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!array_key_exists($file_ext, $allowed_types)) {
                $errors[] = "File type not allowed for {$file['name']}";
                continue;
            }

            // Verify MIME type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mime_type, $allowed_types)) {
                $errors[] = "Invalid file type for {$file['name']}";
                continue;
            }

            // Generate safe filename
            $safe_filename = sanitize_file_name($file['name']);
            $unique_filename = wp_unique_filename($project_upload_dir, $safe_filename);
            $file_path = $project_upload_dir . '/' . $unique_filename;

            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                $uploaded_files[] = [
                    'name' => $unique_filename,
                    'original_name' => $file['name'],
                    'path' => $file_path,
                    'url' => $project_upload_url . '/' . $unique_filename,
                    'type' => $file_ext,
                    'size' => $file['size'],
                    'mime_type' => $mime_type
                ];

                // Add to knowledge base
                self::add_file_to_knowledge_base($file_path, $file['name'], $project_id, $user_id, $file_ext, $mime_type);
            } else {
                $errors[] = "Failed to save {$file['name']}";
            }
        }

        // Return response
        if (!empty($uploaded_files)) {
            wp_send_json_success([
                'message' => count($uploaded_files) . ' file(s) uploaded successfully',
                'files' => $uploaded_files,
                'errors' => $errors
            ]);
        } else {
            wp_send_json_error([
                'message' => 'No files were uploaded successfully',
                'errors' => $errors
            ]);
        }
    }

    /**
     * Add uploaded file to knowledge base
     */
    private static function add_file_to_knowledge_base($file_path, $original_name, $project_id, $user_id, $file_ext, $mime_type) {
        global $wpdb;
        
        try {
            $content = '';
            $content_type = 'file';
            
            // Extract content based on file type
            if ($file_ext === 'txt') {
                $content = file_get_contents($file_path);
                $content_type = 'text';
            } elseif ($file_ext === 'pdf') {
                $content = self::extract_pdf_content($file_path, $original_name);
                $content_type = 'pdf';
            } elseif (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                $content = "Image file uploaded: {$original_name}. This image has been added to the project and can be referenced in conversations. The user can ask questions about this image or request analysis of its contents.";
                $content_type = 'image';
            } elseif (in_array($file_ext, ['mp3', 'wav', 'm4a', 'ogg'])) {
                $content = "Audio file uploaded: {$original_name}. This audio file has been added to the project and can be referenced in conversations. The user may ask for transcription or analysis of the audio content.";
                $content_type = 'audio';
            } elseif (in_array($file_ext, ['doc', 'docx'])) {
                $content = self::extract_document_content($file_path, $original_name, $file_ext);
                $content_type = 'document';
            }

            // Insert into knowledge base
            $table_name = $wpdb->prefix . 'aiohm_vector_entries';
            $result = $wpdb->insert(
                $table_name,
                [
                    'user_id' => $user_id,
                    'content_id' => 'project_file_' . $project_id . '_' . time(),
                    'content_type' => $content_type,
                    'title' => $original_name,
                    'content' => $content,
                    'metadata' => json_encode([
                        'project_id' => $project_id,
                        'file_path' => $file_path,
                        'file_type' => $file_ext,
                        'mime_type' => $mime_type,
                        'upload_date' => current_time('mysql')
                    ])
                ],
                [
                    '%d', '%s', '%s', '%s', '%s', '%s'
                ]
            );

            if ($result === false) {
                error_log('Failed to add file to knowledge base: ' . $wpdb->last_error);
            }

        } catch (Exception $e) {
            error_log('Error adding file to knowledge base: ' . $e->getMessage());
        }
    }

    /**
     * Extract text content from PDF files
     */
    private static function extract_pdf_content($file_path, $original_name) {
        try {
            // Method 1: Try using pdftotext command line tool (if available)
            if (function_exists('exec')) {
                $output = [];
                $return_code = 0;
                exec("pdftotext '$file_path' - 2>/dev/null", $output, $return_code);
                
                if ($return_code === 0 && !empty($output)) {
                    $content = implode("\n", $output);
                    if (strlen(trim($content)) > 50) { // Ensure we got meaningful content
                        return "PDF Document: {$original_name}\n\nContent:\n" . trim($content);
                    }
                }
            }

            // Method 2: Try using PHP PDF parsing libraries if available
            if (class_exists('Smalot\PdfParser\Parser')) {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($file_path);
                $text = $pdf->getText();
                
                if (strlen(trim($text)) > 50) {
                    return "PDF Document: {$original_name}\n\nContent:\n" . trim($text);
                }
            }

            // Method 3: Fallback - create a descriptive entry that tells the AI about the PDF
            return "PDF Document uploaded: {$original_name}. This is a PDF file that has been uploaded to the project. The user has indicated they want to analyze or discuss the contents of this PDF. While I cannot directly read PDF files in this conversation, I can help the user by asking them to paste relevant text excerpts from the PDF that they'd like to discuss, or I can provide guidance on how to extract and work with PDF content.";

        } catch (Exception $e) {
            error_log('Error extracting PDF content: ' . $e->getMessage());
            return "PDF Document: {$original_name} - Content extraction failed, but file is available for reference.";
        }
    }

    /**
     * Extract text content from Word documents
     */
    private static function extract_document_content($file_path, $original_name, $file_ext) {
        try {
            // Method 1: Try using antiword for .doc files
            if ($file_ext === 'doc' && function_exists('exec')) {
                $output = [];
                $return_code = 0;
                exec("antiword '$file_path' 2>/dev/null", $output, $return_code);
                
                if ($return_code === 0 && !empty($output)) {
                    $content = implode("\n", $output);
                    if (strlen(trim($content)) > 50) {
                        return "Word Document: {$original_name}\n\nContent:\n" . trim($content);
                    }
                }
            }

            // Method 2: Try using docx2txt for .docx files
            if ($file_ext === 'docx' && function_exists('exec')) {
                $output = [];
                $return_code = 0;
                exec("docx2txt '$file_path' - 2>/dev/null", $output, $return_code);
                
                if ($return_code === 0 && !empty($output)) {
                    $content = implode("\n", $output);
                    if (strlen(trim($content)) > 50) {
                        return "Word Document: {$original_name}\n\nContent:\n" . trim($content);
                    }
                }
            }

            // Method 3: Try ZIP extraction for .docx (it's essentially a ZIP file)
            if ($file_ext === 'docx' && class_exists('ZipArchive')) {
                $zip = new ZipArchive();
                if ($zip->open($file_path) === TRUE) {
                    $xml_content = $zip->getFromName('word/document.xml');
                    $zip->close();
                    
                    if ($xml_content) {
                        // Parse XML and extract text
                        $dom = new DOMDocument();
                        if (@$dom->loadXML($xml_content)) {
                            $text_nodes = $dom->getElementsByTagName('t');
                            $content = '';
                            foreach ($text_nodes as $node) {
                                $content .= $node->nodeValue . ' ';
                            }
                            
                            if (strlen(trim($content)) > 50) {
                                return "Word Document: {$original_name}\n\nContent:\n" . trim($content);
                            }
                        }
                    }
                }
            }

            // Fallback - create a descriptive entry
            return "Document uploaded: {$original_name}. This is a Word document that has been uploaded to the project. The user has indicated they want to analyze or discuss the contents of this document. While I cannot directly read Word documents in this conversation, I can help the user by asking them to paste relevant text excerpts from the document that they'd like to discuss, or I can provide guidance on how to extract and work with document content.";

        } catch (Exception $e) {
            error_log('Error extracting document content: ' . $e->getMessage());
            return "Document: {$original_name} - Content extraction failed, but file is available for reference.";
        }
    }

    /**
     * Handle AJAX request to get Brand Soul content for viewing
     */
    public static function handle_get_brand_soul_content_ajax() {
        if (!check_ajax_referer('aiohm_admin_nonce', 'nonce', false) || !current_user_can('read')) {
            wp_send_json_error(['message' => 'Security check failed.']);
            return;
        }

        $content_id = sanitize_text_field($_POST['content_id'] ?? '');
        if (empty($content_id)) {
            wp_send_json_error(['message' => 'Content ID is required.']);
            return;
        }

        try {
            global $wpdb;
            $rag_engine = new AIOHM_KB_RAG_Engine();
            $table_name = $rag_engine->get_table_name();

            // Get the brand soul content by content_id
            $entry = $wpdb->get_row($wpdb->prepare(
                "SELECT content, title FROM {$table_name} WHERE content_id = %s AND content_type IN ('brand-soul', 'brand_soul') LIMIT 1",
                $content_id
            ), ARRAY_A);

            if (!$entry) {
                wp_send_json_error(['message' => 'Brand Soul content not found.']);
                return;
            }

            wp_send_json_success([
                'content' => $entry['content'],
                'title' => $entry['title']
            ]);

        } catch (Exception $e) {
            error_log('Error retrieving Brand Soul content: ' . $e->getMessage());
            wp_send_json_error(['message' => 'Error retrieving Brand Soul content.']);
        }
    }

    /**
     * Handle AJAX request to get content for viewing (supports all content types)
     */
    public static function handle_get_content_for_view_ajax() {
        if (!check_ajax_referer('aiohm_admin_nonce', 'nonce', false) || !current_user_can('read')) {
            wp_send_json_error(['message' => 'Security check failed.']);
            return;
        }

        $content_id = sanitize_text_field($_POST['content_id'] ?? '');
        $content_type = sanitize_text_field($_POST['content_type'] ?? '');
        
        if (empty($content_id)) {
            wp_send_json_error(['message' => 'Content ID is required.']);
            return;
        }

        try {
            global $wpdb;
            $rag_engine = new AIOHM_KB_RAG_Engine();
            $table_name = $rag_engine->get_table_name();

            // Get the content by content_id
            $entry = $wpdb->get_row($wpdb->prepare(
                "SELECT content, title, content_type FROM {$table_name} WHERE content_id = %s LIMIT 1",
                $content_id
            ), ARRAY_A);

            if (!$entry) {
                wp_send_json_error(['message' => 'Content not found.']);
                return;
            }

            wp_send_json_success([
                'content' => $entry['content'],
                'title' => $entry['title'],
                'content_type' => $entry['content_type']
            ]);

        } catch (Exception $e) {
            error_log('Error retrieving content for view: ' . $e->getMessage());
            wp_send_json_error(['message' => 'Error retrieving content.']);
        }
    }

    /**
     * Handles the AJAX request to get AI usage statistics
     */
    public static function handle_get_usage_stats_ajax() {
        if (!check_ajax_referer('aiohm_admin_nonce', 'nonce', false) || !current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Security check failed.']);
            return;
        }

        try {
            global $wpdb;
            
            // Create usage stats table if it doesn't exist
            $table_name = $wpdb->prefix . 'aiohm_usage_stats';
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE IF NOT EXISTS $table_name (
                id int(11) NOT NULL AUTO_INCREMENT,
                provider varchar(20) NOT NULL,
                tokens_used int(11) NOT NULL DEFAULT 0,
                requests_count int(11) NOT NULL DEFAULT 1,
                cost_estimate decimal(10,6) NOT NULL DEFAULT 0.000000,
                usage_date date NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY provider_date (provider, usage_date)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);

            // Get current date and 30 days ago
            $today = date('Y-m-d');
            $thirty_days_ago = date('Y-m-d', strtotime('-30 days'));

            // Calculate total tokens for last 30 days
            $total_tokens_30d = $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(tokens_used) FROM $table_name WHERE usage_date >= %s",
                $thirty_days_ago
            )) ?: 0;

            // Calculate today's tokens
            $tokens_today = $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(tokens_used) FROM $table_name WHERE usage_date = %s",
                $today
            )) ?: 0;

            // Calculate estimated cost for last 30 days
            $estimated_cost = $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(cost_estimate) FROM $table_name WHERE usage_date >= %s",
                $thirty_days_ago
            )) ?: 0;

            // Get breakdown by provider
            $provider_stats = $wpdb->get_results($wpdb->prepare(
                "SELECT 
                    provider,
                    SUM(tokens_used) as tokens,
                    SUM(requests_count) as requests,
                    SUM(cost_estimate) as cost
                FROM $table_name 
                WHERE usage_date >= %s 
                GROUP BY provider",
                $thirty_days_ago
            ), ARRAY_A);

            // Format provider data
            $providers = [
                'openai' => ['tokens' => 0, 'requests' => 0, 'cost' => '0.00'],
                'gemini' => ['tokens' => 0, 'requests' => 0, 'cost' => '0.00'],
                'claude' => ['tokens' => 0, 'requests' => 0, 'cost' => '0.00']
            ];

            foreach ($provider_stats as $stat) {
                if (isset($providers[$stat['provider']])) {
                    $providers[$stat['provider']] = [
                        'tokens' => (int) $stat['tokens'],
                        'requests' => (int) $stat['requests'],
                        'cost' => number_format((float) $stat['cost'], 2)
                    ];
                }
            }

            wp_send_json_success([
                'total_tokens_30d' => (int) $total_tokens_30d,
                'tokens_today' => (int) $tokens_today,
                'estimated_cost' => number_format((float) $estimated_cost, 2),
                'providers' => $providers
            ]);

        } catch (Exception $e) {
            error_log('Error retrieving usage stats: ' . $e->getMessage());
            wp_send_json_error(['message' => 'Error retrieving usage statistics.']);
        }
    }

    /**
     * Helper function to log AI usage (call this whenever AI APIs are used)
     */
    public static function log_ai_usage($provider, $tokens_used, $cost_estimate = 0) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'aiohm_usage_stats';
        $today = date('Y-m-d');
        
        // Check if we already have an entry for today and this provider
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id, tokens_used, requests_count, cost_estimate FROM $table_name 
             WHERE provider = %s AND usage_date = %s",
            $provider, $today
        ));
        
        if ($existing) {
            // Update existing record
            $wpdb->update(
                $table_name,
                [
                    'tokens_used' => $existing->tokens_used + $tokens_used,
                    'requests_count' => $existing->requests_count + 1,
                    'cost_estimate' => $existing->cost_estimate + $cost_estimate
                ],
                ['id' => $existing->id]
            );
        } else {
            // Create new record
            $wpdb->insert(
                $table_name,
                [
                    'provider' => $provider,
                    'tokens_used' => $tokens_used,
                    'requests_count' => 1,
                    'cost_estimate' => $cost_estimate,
                    'usage_date' => $today
                ]
            );
        }
    }

    /**
     * Handle download conversation as PDF
     */
    public static function handle_download_conversation_pdf_ajax() {
        if (!check_ajax_referer('aiohm_private_chat_nonce', 'nonce', false)) {
            wp_die('Security check failed.');
        }
        
        if (!current_user_can('read')) {
            wp_die('You do not have permission to access this feature.');
        }
        
        try {
            $conversation_id = isset($_POST['conversation_id']) ? intval($_POST['conversation_id']) : 0;
            if (!$conversation_id) {
                wp_die('Invalid conversation ID.');
            }
            
            global $wpdb;
            $user_id = get_current_user_id();
            
            // Get conversation details
            $conversation = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}aiohm_conversations WHERE id = %d AND user_id = %d",
                $conversation_id, $user_id
            ));
            
            if (!$conversation) {
                wp_die('Conversation not found or access denied.');
            }
            
            // Get all messages for this conversation
            $messages = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}aiohm_messages WHERE conversation_id = %d ORDER BY created_at ASC",
                $conversation_id
            ));
            
            // Include the FPDF library and enhanced PDF class
            require_once AIOHM_KB_PLUGIN_DIR . 'includes/lib/fpdf/fpdf.php';
            require_once AIOHM_KB_PLUGIN_DIR . 'includes/class-enhanced-pdf.php';
            
            // Create enhanced PDF instance
            $pdf = new AIOHM_Enhanced_PDF();
            $pdf->AddPage();
            
            // Conversation details
            $pdf->ChapterTitle('Conversation: ' . $conversation->title);
            
            $pdf->SetFont('Arial', '', 10);
            $pdf->SetTextColor(100);
            $pdf->Cell(0, 6, 'Created: ' . date('F j, Y g:i A', strtotime($conversation->created_at)), 0, 1);
            $pdf->Cell(0, 6, 'Project ID: ' . $conversation->project_id, 0, 1);
            $pdf->Cell(0, 6, 'Generated: ' . date('F j, Y g:i A'), 0, 1);
            $pdf->Ln(10);
            
            // Messages
            foreach ($messages as $message) {
                $pdf->MessageBlock($message->sender, $message->content, $message->created_at);
            }
            
            // Output PDF
            $filename = 'conversation-' . $conversation_id . '-' . date('Y-m-d') . '.pdf';
            $pdf->Output('D', $filename);
            exit;
            
        } catch (Exception $e) {
            AIOHM_KB_Assistant::log('PDF Download Error: ' . $e->getMessage(), 'error');
            wp_die('Error generating PDF: ' . $e->getMessage());
        }
    }

    /**
     * Handle adding conversation to knowledge base
     */
    public static function handle_add_conversation_to_kb_ajax() {
        if (!check_ajax_referer('aiohm_private_chat_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Security check failed.']);
        }
        
        if (!current_user_can('read')) {
            wp_send_json_error(['message' => 'You do not have permission to use this feature.']);
        }
        
        try {
            $conversation_id = isset($_POST['conversation_id']) ? intval($_POST['conversation_id']) : 0;
            if (!$conversation_id) {
                wp_send_json_error(['message' => 'Invalid conversation ID.']);
            }
            
            global $wpdb;
            $user_id = get_current_user_id();
            
            // Get conversation details
            $conversation = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}aiohm_conversations WHERE id = %d AND user_id = %d",
                $conversation_id, $user_id
            ));
            
            if (!$conversation) {
                wp_send_json_error(['message' => 'Conversation not found or access denied.']);
            }
            
            // Get all messages for this conversation
            $messages = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}aiohm_messages WHERE conversation_id = %d ORDER BY created_at ASC",
                $conversation_id
            ));
            
            // Compile conversation content
            $content = "Conversation: " . $conversation->title . "\n";
            $content .= "Date: " . $conversation->created_at . "\n\n";
            
            foreach ($messages as $message) {
                $sender = ($message->sender === 'user') ? 'User' : 'Assistant';
                $content .= $sender . ": " . strip_tags($message->content) . "\n\n";
            }
            
            // Add to knowledge base
            $vectorizer = new AIOHM_KB_Vectorizer();
            $result = $vectorizer->add_entry([
                'title' => 'Conversation: ' . $conversation->title,
                'content' => $content,
                'scope' => 'private',
                'user_id' => $user_id,
                'source_type' => 'conversation',
                'source_id' => $conversation_id
            ]);
            
            if ($result) {
                wp_send_json_success(['message' => 'Conversation added to knowledge base successfully.']);
            } else {
                wp_send_json_error(['message' => 'Failed to add conversation to knowledge base.']);
            }
            
        } catch (Exception $e) {
            AIOHM_KB_Assistant::log('Add to KB Error: ' . $e->getMessage(), 'error');
            wp_send_json_error(['message' => 'Error adding to KB: ' . $e->getMessage()]);
        }
    }
}

} // End class_exists check