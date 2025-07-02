<?php
/**
 * Core initialization and configuration with ARMember integration
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AIOHM_KB_Core_Init {
    
    // Static property to store settings
    private static $settings = null;
    
    /**
     * Initialize core functionality
     */
    public static function init() {
        // Use static methods instead of $this
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'admin_enqueue_scripts'));
        add_action('wp_ajax_aiohm_chat_request', array(__CLASS__, 'handle_chat_ajax'));
        add_action('wp_ajax_nopriv_aiohm_chat_request', array(__CLASS__, 'handle_chat_ajax'));
        add_action('wp_ajax_aiohm_scan_content', array(__CLASS__, 'handle_scan_ajax'));
        add_action('wp_ajax_aiohm_delete_entry', array(__CLASS__, 'handle_delete_entry'));
        add_action('wp_ajax_aiohm_update_user_preferences', array(__CLASS__, 'handle_user_preferences'));
        add_action('wp_ajax_aiohm_get_chat_history', array(__CLASS__, 'handle_get_chat_history'));
        add_action('wp_ajax_aiohm_clear_chat_history', array(__CLASS__, 'handle_clear_chat_history'));
        add_action('wp_ajax_aiohm_rate_response', array(__CLASS__, 'handle_rate_response'));
        add_action('wp_ajax_aiohm_export_user_data', array(__CLASS__, 'handle_export_user_data'));
        add_action('wp_ajax_aiohm_progressive_scan', array(__CLASS__, 'handle_progressive_scan_ajax'));
        
        // ARMember integration hooks
        if (class_exists('ARMemberLite')) {
            add_action('wp_ajax_aiohm_sync_single_user', array(__CLASS__, 'handle_sync_single_user'));
            add_action('wp_ajax_aiohm_check_user_access', array(__CLASS__, 'handle_check_user_access'));
        }
        
        // Scheduled events
        add_action('aiohm_daily_cleanup', array(__CLASS__, 'daily_cleanup_task'));
        add_action('aiohm_sync_armember_users', array(__CLASS__, 'scheduled_armember_sync'));
        
        // Initialize scheduled events
        self::setup_scheduled_events();
    }
    
    /**
     * Setup scheduled events
     */
    private static function setup_scheduled_events() {
        if (!wp_next_scheduled('aiohm_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'aiohm_daily_cleanup');
        }
        
        // Schedule ARMember sync if enabled
        $settings = self::get_settings();
        if (!empty($settings['auto_sync_armember']) && class_exists('ARMemberLite')) {
            if (!wp_next_scheduled('aiohm_sync_armember_users')) {
                wp_schedule_event(time(), 'hourly', 'aiohm_sync_armember_users');
            }
        }
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
        
        // Enhanced localization with user context
        $user_id = get_current_user_id();
        $user_context = self::get_user_context($user_id);
        
        wp_localize_script('aiohm-chat', 'aiohm_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aiohm_chat_nonce'),
            'user_id' => $user_id,
            'user_context' => $user_context,
            'settings' => array(
                'max_message_length' => 2000,
                'typing_delay' => 1000,
                'auto_save_chat' => true,
                'show_timestamps' => true,
                'enable_markdown' => true,
                'rate_responses' => true
            ),
            'strings' => array(
                'error' => __('An error occurred. Please try again.', 'aiohm-kb-assistant'),
                'thinking' => __('Thinking...', 'aiohm-kb-assistant'),
                'placeholder' => __('Ask me anything...', 'aiohm-kb-assistant'),
                'send' => __('Send', 'aiohm-kb-assistant'),
                'retry' => __('Retry', 'aiohm-kb-assistant'),
                'copy' => __('Copy', 'aiohm-kb-assistant'),
                'copied' => __('Copied!', 'aiohm-kb-assistant'),
                'rate_helpful' => __('Was this helpful?', 'aiohm-kb-assistant'),
                'rate_yes' => __('Yes', 'aiohm-kb-assistant'),
                'rate_no' => __('No', 'aiohm-kb-assistant'),
                'clear_chat' => __('Clear chat', 'aiohm-kb-assistant'),
                'confirm_clear' => __('Are you sure you want to clear the chat history?', 'aiohm-kb-assistant'),
                'access_denied' => __('You need a premium membership to access this content.', 'aiohm-kb-assistant'),
                'login_required' => __('Please log in to access advanced features.', 'aiohm-kb-assistant')
            )
        ));
    }
    
    /**
     * Get user context for frontend
     */
    private static function get_user_context($user_id) {
        if (!$user_id) {
            return array(
                'access_level' => 'guest',
                'is_logged_in' => false,
                'can_save_chat' => false
            );
        }
        
        $user_profile = get_user_meta($user_id, 'aiohm_knowledge_profile', true);
        $access_level = $user_profile ? $user_profile['access_level'] : 'basic';
        
        return array(
            'access_level' => $access_level,
            'is_logged_in' => true,
            'can_save_chat' => true,
            'preferences' => isset($user_profile['preferences']) ? $user_profile['preferences'] : array(),
            'membership_plans' => isset($user_profile['plans']) ? $user_profile['plans'] : array()
        );
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
            AIOHM_KB_ASSETS_URL . 'js/aiohm-admin.js',
            array('jquery', 'jquery-ui-progressbar'),
            AIOHM_KB_VERSION,
            true
        );
        
        wp_enqueue_style(
            'aiohm-admin',
            AIOHM_KB_ASSETS_URL . 'css/aiohm-admin.css',
            array('jquery-ui-theme'),
            AIOHM_KB_VERSION
        );
        
        wp_localize_script('aiohm-admin', 'aiohm_admin_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aiohm_admin_nonce'),
            'armember_active' => class_exists('ARMemberLite'),
            'strings' => array(
                'scanning' => __('Scanning...', 'aiohm-kb-assistant'),
                'complete' => __('Scan complete', 'aiohm-kb-assistant'),
                'error' => __('Error occurred', 'aiohm-kb-assistant'),
                'confirm_delete' => __('Are you sure you want to delete this entry?', 'aiohm-kb-assistant'),
                'syncing_users' => __('Syncing users...', 'aiohm-kb-assistant'),
                'sync_complete' => __('Sync complete', 'aiohm-kb-assistant'),
                'processing' => __('Processing...', 'aiohm-kb-assistant')
            )
        ));
    }
    
    /**
     * Handle progressive scan AJAX
     */
    public static function handle_progressive_scan_ajax() {
        // Verify nonce and permissions
        if (!wp_verify_nonce($_POST['nonce'], 'aiohm_admin_nonce') || !current_user_can('manage_options')) {
            wp_die('Security check failed');
        }
        
        $scan_type = sanitize_text_field($_POST['scan_type']);
        $batch_size = intval($_POST['batch_size'] ?? 5);
        $current_offset = intval($_POST['current_offset'] ?? 0);
        
        try {
            if ($scan_type === 'website') {
                $crawler = new AIOHM_KB_Site_Crawler();
                $results = $crawler->scan_website_with_progress($batch_size, $current_offset);
            } elseif ($scan_type === 'uploads') {
                $crawler = new AIOHM_KB_Uploads_Crawler();
                $results = $crawler->scan_uploads_with_progress($batch_size, $current_offset);
            } else {
                throw new Exception('Invalid scan type');
            }
            
            wp_send_json_success($results);
            
        } catch (Exception $e) {
            self::log('Scan Error: ' . $e->getMessage(), 'error');
            wp_send_json_error('Scan failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Get plugin settings with defaults
     */
    public static function get_settings() {
        if (self::$settings !== null) {
            return self::$settings;
        }
        
        $default_settings = array(
            'openai_api_key' => '',
            'claude_api_key' => '',
            'default_model' => 'openai',
            'chat_enabled' => true,
            'auto_scan' => false,
            'max_tokens' => 150,
            'temperature' => 0.7,
            'chunk_size' => 1000,
            'chunk_overlap' => 200,
            'rate_limit_requests' => 60,
            'rate_limit_window' => 3600,
            'auto_sync_armember' => false,
            'enable_chat_history' => true,
            'enable_response_rating' => true,
            'show_upgrade_prompts' => true,
            'max_chat_history' => 100,
            'cleanup_old_chats' => true,
            'chat_retention_days' => 30
        );
        
        $settings = get_option('aiohm_kb_settings', $default_settings);
        self::$settings = wp_parse_args($settings, $default_settings);
        
        return self::$settings;
    }
    
    /**
     * Update plugin settings
     */
    public static function update_settings($new_settings) {
        $current_settings = self::get_settings();
        $updated_settings = wp_parse_args($new_settings, $current_settings);
        
        // Update cached settings
        self::$settings = $updated_settings;
        
        return update_option('aiohm_kb_settings', $updated_settings);
    }
    
    /**
     * Enhanced logging with levels and rotation
     */
    public static function log($message, $level = 'info') {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $timestamp = current_time('Y-m-d H:i:s');
            $log_entry = "[{$timestamp}] AIOHM KB [{$level}]: {$message}";
            
            error_log($log_entry);
            
            // Also save to custom log table for admin review
            self::save_to_custom_log($level, $message);
        }
    }
    
    /**
     * Save to custom log table
     */
    private static function save_to_custom_log($level, $message) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'aiohm_logs';
        
        $wpdb->insert(
            $table_name,
            array(
                'level' => $level,
                'message' => $message,
                'timestamp' => current_time('mysql'),
                'user_id' => get_current_user_id(),
                'ip_address' => self::get_client_ip()
            ),
            array('%s', '%s', '%s', '%d', '%s')
        );
    }
    
    /**
     * Get client IP address
     */
    private static function get_client_ip() {
        $ip_keys = array('HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    
    // Add other methods as needed...
    
    /**
     * Handle chat AJAX request
     */
    public static function handle_chat_ajax() {
        // Implementation...
    }
    
    /**
     * Handle scan AJAX request
     */
    public static function handle_scan_ajax() {
        // Implementation...
    }
    
    /**
     * Handle delete entry
     */
    public static function handle_delete_entry() {
        // Implementation...
    }
    
    /**
     * Handle user preferences
     */
    public static function handle_user_preferences() {
        // Implementation...
    }
    
    /**
     * Handle get chat history
     */
    public static function handle_get_chat_history() {
        // Implementation...
    }
    
    /**
     * Handle clear chat history
     */
    public static function handle_clear_chat_history() {
        // Implementation...
    }
    
    /**
     * Handle rate response
     */
    public static function handle_rate_response() {
        // Implementation...
    }
    
    /**
     * Handle export user data
     */
    public static function handle_export_user_data() {
        // Implementation...
    }
    
    /**
     * Handle sync single user
     */
    public static function handle_sync_single_user() {
        // Implementation...
    }
    
    /**
     * Handle check user access
     */
    public static function handle_check_user_access() {
        // Implementation...
    }
    
    /**
     * Daily cleanup task
     */
    public static function daily_cleanup_task() {
        // Implementation...
    }
    
    /**
     * Scheduled ARMember sync
     */
    public static function scheduled_armember_sync() {
        // Implementation...
    }
}
