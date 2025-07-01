<?php
/**
 * Core initialization and configuration
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AIOHM_KB_Core_Init {
    
    /**
     * Initialize core functionality
     */
    public static function init() {
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'admin_enqueue_scripts'));
        add_action('wp_ajax_aiohm_chat_request', array(__CLASS__, 'handle_chat_ajax'));
        add_action('wp_ajax_nopriv_aiohm_chat_request', array(__CLASS__, 'handle_chat_ajax'));
        add_action('wp_ajax_aiohm_scan_content', array(__CLASS__, 'handle_scan_ajax'));
        add_action('wp_ajax_aiohm_delete_entry', array(__CLASS__, 'handle_delete_entry'));
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public static function enqueue_scripts() {
        wp_enqueue_script(
            'aiohm-chat',
            AIOHM_KB_ASSETS_URL . 'js/aiohm-chat.js',
            array('jquery'),
            AIOHM_KB_VERSION,
            true
        );
        
        wp_enqueue_style(
            'aiohm-chat',
            AIOHM_KB_ASSETS_URL . 'css/aiohm-chat.css',
            array(),
            AIOHM_KB_VERSION
        );
        
        // Localize script for AJAX
        wp_localize_script('aiohm-chat', 'aiohm_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aiohm_chat_nonce'),
            'strings' => array(
                'error' => __('An error occurred. Please try again.', 'aiohm-kb-assistant'),
                'thinking' => __('Thinking...', 'aiohm-kb-assistant'),
                'placeholder' => __('Ask me anything...', 'aiohm-kb-assistant'),
                'send' => __('Send', 'aiohm-kb-assistant')
            )
        ));
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public static function admin_enqueue_scripts($hook) {
        // Only load on our admin pages
        if (strpos($hook, 'aiohm') === false) {
            return;
        }
        
        wp_enqueue_script(
            'aiohm-admin',
            AIOHM_KB_ASSETS_URL . 'js/aiohm-chat.js',
            array('jquery'),
            AIOHM_KB_VERSION,
            true
        );
        
        wp_enqueue_style(
            'aiohm-admin',
            AIOHM_KB_ASSETS_URL . 'css/aiohm-chat.css',
            array(),
            AIOHM_KB_VERSION
        );
        
        wp_localize_script('aiohm-admin', 'aiohm_admin_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aiohm_admin_nonce'),
            'strings' => array(
                'scanning' => __('Scanning...', 'aiohm-kb-assistant'),
                'complete' => __('Scan complete', 'aiohm-kb-assistant'),
                'error' => __('Error occurred', 'aiohm-kb-assistant'),
                'confirm_delete' => __('Are you sure you want to delete this entry?', 'aiohm-kb-assistant')
            )
        ));
    }
    
    /**
     * Handle chat AJAX requests
     */
    public static function handle_chat_ajax() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'aiohm_chat_nonce')) {
            wp_die('Security check failed');
        }
        
        $message = sanitize_textarea_field($_POST['message']);
        
        if (empty($message)) {
            wp_send_json_error('Message is required');
        }
        
        try {
            // Get AI response
            $ai_client = new AIOHM_KB_AI_GPT_Client();
            $rag_engine = new AIOHM_KB_RAG_Engine();
            
            // Find relevant context
            $context = $rag_engine->find_relevant_context($message);
            
            // Generate response
            $response = $ai_client->generate_response($message, $context);
            
            wp_send_json_success(array(
                'response' => $response,
                'timestamp' => current_time('mysql')
            ));
            
        } catch (Exception $e) {
            error_log('AIOHM Chat Error: ' . $e->getMessage());
            wp_send_json_error('Failed to generate response: ' . $e->getMessage());
        }
    }
    
    /**
     * Handle content scan AJAX requests
     */
    public static function handle_scan_ajax() {
        // Verify nonce and permissions
        if (!wp_verify_nonce($_POST['nonce'], 'aiohm_admin_nonce') || !current_user_can('manage_options')) {
            wp_die('Security check failed');
        }
        
        $scan_type = sanitize_text_field($_POST['scan_type']);
        
        try {
            if ($scan_type === 'website') {
                $crawler = new AIOHM_KB_Site_Crawler();
                $results = $crawler->scan_website();
            } else if ($scan_type === 'uploads') {
                $crawler = new AIOHM_KB_Uploads_Crawler();
                $results = $crawler->scan_uploads();
            } else {
                throw new Exception('Invalid scan type');
            }
            
            wp_send_json_success($results);
            
        } catch (Exception $e) {
            error_log('AIOHM Scan Error: ' . $e->getMessage());
            wp_send_json_error('Scan failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Handle delete entry AJAX requests
     */
    public static function handle_delete_entry() {
        // Verify nonce and permissions
        if (!wp_verify_nonce($_POST['nonce'], 'aiohm_admin_nonce') || !current_user_can('manage_options')) {
            wp_die('Security check failed');
        }
        
        $entry_id = sanitize_text_field($_POST['entry_id']);
        
        try {
            $rag_engine = new AIOHM_KB_RAG_Engine();
            $result = $rag_engine->delete_entry($entry_id);
            
            if ($result) {
                wp_send_json_success('Entry deleted successfully');
            } else {
                wp_send_json_error('Failed to delete entry');
            }
            
        } catch (Exception $e) {
            error_log('AIOHM Delete Error: ' . $e->getMessage());
            wp_send_json_error('Delete failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Get plugin settings
     */
    public static function get_settings() {
        $default_settings = array(
            'openai_api_key' => '',
            'claude_api_key' => '',
            'default_model' => 'openai',
            'chat_enabled' => true,
            'auto_scan' => false,
            'max_tokens' => 150,
            'temperature' => 0.7,
            'chunk_size' => 1000,
            'chunk_overlap' => 200
        );
        
        $settings = get_option('aiohm_kb_settings', $default_settings);
        return wp_parse_args($settings, $default_settings);
    }
    
    /**
     * Update plugin settings
     */
    public static function update_settings($new_settings) {
        $current_settings = self::get_settings();
        $updated_settings = wp_parse_args($new_settings, $current_settings);
        
        return update_option('aiohm_kb_settings', $updated_settings);
    }
    
    /**
     * Log debug information
     */
    public static function log($message, $level = 'info') {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("AIOHM KB [{$level}]: " . $message);
        }
    }
}
