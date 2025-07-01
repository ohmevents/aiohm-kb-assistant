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
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        register_uninstall_hook(__FILE__, array('AIOHM_KB_Assistant', 'uninstall'));
        
        add_action('plugins_loaded', array($this, 'init'));
        add_action('init', array($this, 'load_textdomain'));
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        require_once AIOHM_KB_INCLUDES_DIR . 'core-init.php';
        require_once AIOHM_KB_INCLUDES_DIR . 'crawler-site.php';
        require_once AIOHM_KB_INCLUDES_DIR . 'crawler-uploads.php';
        require_once AIOHM_KB_INCLUDES_DIR . 'rag-engine.php';
        require_once AIOHM_KB_INCLUDES_DIR . 'ai-gpt-client.php';
        require_once AIOHM_KB_INCLUDES_DIR . 'settings-page.php';
        require_once AIOHM_KB_INCLUDES_DIR . 'aiohm-kb-manager.php';
        require_once AIOHM_KB_INCLUDES_DIR . 'shortcode-chat.php';
        require_once AIOHM_KB_INCLUDES_DIR . 'shortcode-search.php';
        require_once AIOHM_KB_INCLUDES_DIR . 'chat-box.php';
        require_once AIOHM_KB_INCLUDES_DIR . 'frontend-widget.php';
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Initialize core components
        AIOHM_KB_Core_Init::init();
        AIOHM_KB_Settings_Page::init();
        AIOHM_KB_Manager::init();
        AIOHM_KB_Shortcode_Chat::init();
        AIOHM_KB_Shortcode_Search::init();
        AIOHM_KB_Frontend_Widget::init();
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
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up scheduled events
        wp_clear_scheduled_hook('aiohm_kb_cleanup');
        
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
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
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
            'chunk_overlap' => 200
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

// Initialize the plugin
function aiohm_kb_assistant() {
    return AIOHM_KB_Assistant::get_instance();
}

// Start the plugin
aiohm_kb_assistant();
