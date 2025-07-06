<?php
/**
 * Plugin Name: AIOHM Knowledge Assistant
 * Plugin URI: https://aiohm.app
 * Description: A soul-aligned AI assistant for WordPress — integrates your knowledge base, voice, and strategy into a deeply intelligent and customizable chat experience.
 * Version: 1.1.3
 * Author: OHM Events Agency
 * Author URI: https://aiohm.app
 * Text Domain: aiohm-knowledge-assistant
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.8
 * Tested up to: 6.5
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) exit;

// Define plugin constants
define('AIOHM_KB_VERSION', '1.1.3');
define('AIOHM_KB_PLUGIN_FILE', __FILE__);
define('AIOHM_KB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AIOHM_KB_INCLUDES_DIR', AIOHM_KB_PLUGIN_DIR . 'includes/');
define('AIOHM_KB_PLUGIN_URL', plugin_dir_url(__FILE__));

class AIOHM_KB_Assistant {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        register_activation_hook(AIOHM_KB_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(AIOHM_KB_PLUGIN_FILE, array($this, 'deactivate'));
        add_action('plugins_loaded', array($this, 'load_dependencies'));
        add_action('init', array($this, 'init_plugin'));
        // Add this line to call the method that adds the settings link
        add_filter('plugin_action_links_' . plugin_basename(AIOHM_KB_PLUGIN_FILE), array($this, 'add_settings_link'));
    }
    
    public function load_dependencies() {
        $files = [
            'core-init.php', 'settings-page.php', 'rag-engine.php', 
            'ai-gpt-client.php', 'crawler-site.php', 'crawler-uploads.php', 
            'aiohm-kb-manager.php', 'api-client-app.php', 'shortcode-chat.php', 
            'shortcode-search.php', 'frontend-widget.php', 'chat-box.php'
        ];
        foreach ($files as $file) {
            $path = AIOHM_KB_INCLUDES_DIR . $file;
            if (file_exists($path)) { require_once $path; }
        }
    }
    
    public function init_plugin() {
        AIOHM_KB_Core_Init::init();
        AIOHM_KB_Settings_Page::init();
        AIOHM_KB_Shortcode_Chat::init(); // Ensure chat shortcode is initialized
        AIOHM_KB_Shortcode_Search::init(); // Ensure search shortcode is initialized
        AIOHM_KB_Frontend_Widget::init(); // Ensure frontend widget is initialized
    }
    
    public function activate() {
        require_once AIOHM_KB_INCLUDES_DIR . 'rag-engine.php';
        $this->create_tables();
        $this->set_default_options();
        flush_rewrite_rules();
    }

    public function deactivate() {
        wp_clear_scheduled_hook('aiohm_scheduled_scan');
        flush_rewrite_rules();
    }
    
    public static function get_settings() {
        $default_settings = [
            'personal_api_key' => '',
            'openai_api_key'   => '',
            'chat_enabled'     => true, // Added and defaulted to true
            'show_floating_chat' => false, // Added and defaulted to false
            'scan_schedule'    => 'none',
            'chunk_size'       => 1000,
            'chunk_overlap'    => 200,
        ];
        return wp_parse_args(get_option('aiohm_kb_settings', []), $default_settings);
    }

    private function create_tables() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aiohm_vector_entries';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL DEFAULT 0,
            content_id varchar(255) NOT NULL,
            content_type varchar(50) NOT NULL,
            title text NOT NULL,
            content longtext NOT NULL,
            vector_data longtext,
            metadata longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY content_id (content_id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    private function set_default_options() {
        add_option('aiohm_kb_settings', [
            'personal_api_key' => '',
            'openai_api_key' => '',
            'chat_enabled' => true, // Set default to true for activation
            'show_floating_chat' => false, // Set default to false for activation
            'scan_schedule' => 'none',
        ], '', 'no');
    }

    /**
     * Add a settings link to the plugin actions on the plugins page.
     * @param array $links Array of plugin action links.
     * @return array Modified array of links.
     */
    public function add_settings_link($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=aiohm-settings') . '">' . __('Settings', 'aiohm-kb-assistant') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Custom logging function for the plugin.
     * Logs messages to the debug log if WP_DEBUG_LOG is enabled.
     *
     * @param string $message The message to log.
     * @param string $level The log level (e.g., 'info', 'warning', 'error').
     */
    public static function log($message, $level = 'info') {
        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG === true) {
            $log_prefix = '[AIOHM_KB_Assistant] ' . strtoupper($level) . ': ';
            error_log($log_prefix . $message);
        }
    }
}
AIOHM_KB_Assistant::get_instance();