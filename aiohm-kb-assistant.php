<?php
/**
 * Plugin Name: AIOHM Knowledge Assistant
 * Plugin URI: https://aiohm.com
 * Description: AI-powered knowledge base with content scanning, vector embedding, and chat functionality
 * Version: 1.0.0
 * Author: AIOHM
 * Author URI: https://aiohm.com
 * Text Domain: aiohm-kb-assistant
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('AIOHM_KB_VERSION', '1.0.0');
define('AIOHM_KB_PLUGIN_FILE', __FILE__);
define('AIOHM_KB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AIOHM_KB_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AIOHM_KB_INCLUDES_DIR', AIOHM_KB_PLUGIN_DIR . 'includes/');
define('AIOHM_KB_ASSETS_URL', AIOHM_KB_PLUGIN_URL . 'assets/');

/**
 * Main AIOHM KB Assistant class
 */
class AIOHM_KB_Assistant {
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * Get single instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor - Only set up hooks, don't load dependencies yet
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        register_uninstall_hook(__FILE__, array('AIOHM_KB_Assistant', 'uninstall'));
        
        // Load dependencies after WordPress is fully loaded
        add_action('plugins_loaded', array($this, 'load_dependencies'), 10);
        
        // Initialize plugin after dependencies are loaded
        add_action('init', array($this, 'init_plugin'), 10);
        
        // Load textdomain
        add_action('init', array($this, 'load_textdomain'), 5);
    }
    
    /**
     * Load plugin dependencies - Called on 'plugins_loaded'
     */
    public function load_dependencies() {
        // Check if files exist before loading
        $required_files = array(
            'core-init.php',
            'rag-engine.php',
            'ai-gpt-client.php'
        );
        
        // Load required files first
        foreach ($required_files as $file) {
            $file_path = AIOHM_KB_INCLUDES_DIR . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            } else {
                add_action('admin_notices', function() use ($file) {
                    echo '<div class="notice notice-error"><p>';
                    echo sprintf(__('AIOHM KB Assistant: Required file %s is missing.', 'aiohm-kb-assistant'), $file);
                    echo '</p></div>';
                });
                return false;
            }
        }
        
        // Load optional files (only if they exist)
        $optional_files = array(
            'settings-page.php',
            'shortcode-chat.php',
            'shortcode-search.php',
            'frontend-widget.php',
            'chat-box.php',
            'crawler-site.php',
            'crawler-uploads.php',
            'aiohm-kb-manager.php'
        );
        
        foreach ($optional_files as $file) {
            $file_path = AIOHM_KB_INCLUDES_DIR . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
        
        // Load ARMember integration if ARMember is active
        if (class_exists('ARMemberLite')) {
            $armember_file = AIOHM_KB_INCLUDES_DIR . 'armember-integration.php';
            if (file_exists($armember_file)) {
                require_once $armember_file;
            }
        }
        
        return true;
    }
    
    /**
     * Initialize plugin - Called on 'init'
     */
    public function init_plugin() {
        // Only initialize if core class exists
        if (!class_exists('AIOHM_KB_Core_Init')) {
            return;
        }
        
        // Initialize core components
        AIOHM_KB_Core_Init::init();
        
        // Initialize other components if they exist
        if (class_exists('AIOHM_KB_Settings_Page')) {
            AIOHM_KB_Settings_Page::init();
        }
        
        if (class_exists('AIOHM_KB_Shortcode_Chat')) {
            AIOHM_KB_Shortcode_Chat::init();
        }
        
        if (class_exists('AIOHM_KB_Shortcode_Search')) {
            AIOHM_KB_Shortcode_Search::init();
        }
        
        if (class_exists('AIOHM_KB_Frontend_Widget')) {
            AIOHM_KB_Frontend_Widget::init();
        }
        
        // Initialize ARMember integration if available
        if (class_exists('ARMemberLite') && class_exists('AIOHM_KB_ARMember_Integration')) {
            AIOHM_KB_ARMember_Integration::init();
        }
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain('aiohm-kb-assistant', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables if needed
        $this->create_tables();
        
        // Set default options
        $this->set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set activation notice
        set_transient('aiohm_kb_activation_notice', true, 30);
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up scheduled events
        wp_clear_scheduled_hook('aiohm_kb_cleanup');
        wp_clear_scheduled_hook('aiohm_sync_armember_users');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin uninstall
     */
    public static function uninstall() {
        // Remove plugin options
        delete_option('aiohm_kb_settings');
        delete_option('aiohm_vector_entries');
        delete_option('aiohm_qa_dataset');
        delete_option('aiohm_kb_version');
        
        // Remove user meta
        delete_metadata('user', 0, 'aiohm_kb_preferences', '', true);
        
        // Drop custom tables
        global $wpdb;
        $tables = array(
            $wpdb->prefix . 'aiohm_vector_entries',
            $wpdb->prefix . 'aiohm_chat_history',
            $wpdb->prefix . 'aiohm_response_ratings',
            $wpdb->prefix . 'aiohm_logs',
            $wpdb->prefix . 'aiohm_error_logs'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$table}");
        }
        
        // Clear scheduled events
        wp_clear_scheduled_hook('aiohm_kb_cleanup');
        wp_clear_scheduled_hook('aiohm_sync_armember_users');
    }
    
    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Vector entries table for better performance
        $table_name = $wpdb->prefix . 'aiohm_vector_entries';
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            content_id varchar(255) NOT NULL,
            content_type varchar(50) NOT NULL,
            title text NOT NULL,
            content longtext NOT NULL,
            vector_data longtext,
            metadata longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY content_id (content_id),
            KEY content_type (content_type)
        ) $charset_collate;";
        
        // Chat history table
        $chat_table = $wpdb->prefix . 'aiohm_chat_history';
        $chat_sql = "CREATE TABLE $chat_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id int(11) NOT NULL,
            conversation_id varchar(255),
            message longtext,
            response longtext,
            context_used longtext,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            ip_address varchar(45),
            user_agent text,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY conversation_id (conversation_id),
            KEY timestamp (timestamp)
        ) $charset_collate;";
        
        // Response ratings table
        $ratings_table = $wpdb->prefix . 'aiohm_response_ratings';
        $ratings_sql = "CREATE TABLE $ratings_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            chat_id varchar(255),
            user_id int(11),
            rating varchar(20),
            feedback text,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            ip_address varchar(45),
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY rating (rating)
        ) $charset_collate;";
        
        // Logs table
        $logs_table = $wpdb->prefix . 'aiohm_logs';
        $logs_sql = "CREATE TABLE $logs_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            level varchar(20),
            message text,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            user_id int(11),
            ip_address varchar(45),
            PRIMARY KEY (id),
            KEY level (level),
            KEY timestamp (timestamp)
        ) $charset_collate;";
        
        // Error logs table
        $error_table = $wpdb->prefix . 'aiohm_error_logs';
        $error_sql = "CREATE TABLE $error_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            type varchar(50),
            message text,
            user_id int(11),
            user_message text,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY type (type),
            KEY timestamp (timestamp)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        dbDelta($chat_sql);
        dbDelta($ratings_sql);
        dbDelta($logs_sql);
        dbDelta($error_sql);
    }
    
    /**
     * Set default plugin options
     */
    private function set_default_options() {
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
        
        if (!get_option('aiohm_kb_settings')) {
            add_option('aiohm_kb_settings', $default_settings);
        }
        
        if (!get_option('aiohm_vector_entries')) {
            add_option('aiohm_vector_entries', array());
        }
        
        if (!get_option('aiohm_qa_dataset')) {
            add_option('aiohm_qa_dataset', array());
        }
        
        add_option('aiohm_kb_version', AIOHM_KB_VERSION);
    }
}

// Initialize the plugin only if WordPress is loaded
if (defined('ABSPATH')) {
    function aiohm_kb_assistant() {
        return AIOHM_KB_Assistant::get_instance();
    }
    
    // Start the plugin
    aiohm_kb_assistant();
}