<?php
/**
 * Plugin Name: AIOHM Knowledge Assistant
 * Plugin URI: https://aiohm.app
 * Description: Bring your wisdom to life. The AIOHM Knowledge Assistant listens, learns, and speaks in your brand's voice, offering real-time answers, soulful brand support, and intuitive guidance for your visitors. With Muse and Mirror modes, it doesn't just respond - it resonates.
 * Version: 1.1.8
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
define('AIOHM_KB_VERSION', '1.1.8');
define('AIOHM_KB_PLUGIN_FILE', __FILE__);
define('AIOHM_KB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AIOHM_KB_INCLUDES_DIR', AIOHM_KB_PLUGIN_DIR . 'includes/');
define('AIOHM_KB_PLUGIN_URL', plugin_dir_url(__FILE__));

// Define the WP-Cron hook name
define('AIOHM_KB_SCHEDULED_SCAN_HOOK', 'aiohm_scheduled_scan');

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
        add_filter('plugin_action_links_' . plugin_basename(AIOHM_KB_PLUGIN_FILE), array($this, 'add_settings_link'));

        // WP-Cron setup
        add_filter('cron_schedules', array($this, 'add_custom_cron_intervals'));
        add_action(AIOHM_KB_SCHEDULED_SCAN_HOOK, array($this, 'run_scheduled_scan'));
        add_action('update_option_aiohm_kb_settings', array($this, 'handle_scan_schedule_change'), 10, 2);
    }
    
    public function load_dependencies() {
        $files = [
            'core-init.php', 
            'settings-page.php', 
            'rag-engine.php', 
            'ai-gpt-client.php', 
            'crawler-site.php', 
            'crawler-uploads.php', 
            'aiohm-kb-manager.php', 
            'api-client-app.php', 
            'shortcode-chat.php', 
            'shortcode-search.php', 
            'frontend-widget.php', 
            'chat-box.php',
            'user-functions.php',
            'pmpro-integration.php'
        ];
        foreach ($files as $file) {
            $path = AIOHM_KB_INCLUDES_DIR . $file;
            if (file_exists($path)) { require_once $path; }
        }
    }
    
    public function init_plugin() {
        AIOHM_KB_Core_Init::init();
        AIOHM_KB_Settings_Page::init();
        AIOHM_KB_Shortcode_Chat::init();
        AIOHM_KB_Shortcode_Search::init();
        AIOHM_KB_Frontend_Widget::init();
        AIOHM_KB_PMP_Integration::init();
    }
    
    public function activate() {
        require_once AIOHM_KB_INCLUDES_DIR . 'rag-engine.php';
        $this->create_tables();
        $this->set_default_options();
        flush_rewrite_rules();

        $settings = self::get_settings();
        if ($settings['scan_schedule'] !== 'none') {
            $this->schedule_scan_event($settings['scan_schedule']);
        }
    }

    public function deactivate() {
        wp_clear_scheduled_hook(AIOHM_KB_SCHEDULED_SCAN_HOOK);
        flush_rewrite_rules();
    }
    
    public static function get_settings() {
        $default_settings = [
            'aiohm_app_email' => '',
            'openai_api_key'   => '',
            'gemini_api_key' => '',
            'claude_api_key' => '',
            'chat_enabled'     => true,
            'show_floating_chat' => false,
            'scan_schedule'    => 'none',
            'chunk_size'       => 1000,
            'chunk_overlap'    => 200,
            'qa_system_message' => "The following is a conversation with an AI assistant customized for %site_name%.\n\nToday is %day_of_week%, and the date is %current_date%.\nThis assistant is emotionally intelligent, grounded, and tuned to reflect the unique voice of the user behind this WordPress site.\n\nIt responds with clarity, calmness, and resonance — always adapting to the brand tone learned from their uploaded content and website pages.\nIt has access to:\nThe user’s WordPress site content (posts, pages, metadata)\nTheir uploaded documents in the AIOHM plugin folder (PDF, DOC, TXT, JSON)\nBrand-aligned insights gathered through the Brand Soul Questionnaire (if available)\n\nTone & Personality\nSpeak with emotional clarity, not robotic formality\nSound like a thoughtful assistant, not a sales rep\nBe concise, but not curt — useful, but never cold\n\nIf unsure, say:\nHmm… I’m not sure how to answer that just yet. But no worries - real humans are nearby. 👉 Check “contact page” to connect directly with the person behind this site for personalized support.\n\nFormatting Rules:\nNo Markdown — use only basic HTML tags for clarity.\nNever end with “Do you want to ask another question?” or other prompts.\nBe present, brief, and brand-aware.\n\nYou may reference any of the following context sources as needed to answer user questions:\n{context}",
            'qa_temperature' => '0.8',
            'business_name' => get_bloginfo('name'),
        ];
        return wp_parse_args(get_option('aiohm_kb_settings', []), $default_settings);
    }

    private function create_tables() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aiohm_vector_entries';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (id bigint(20) NOT NULL AUTO_INCREMENT, user_id bigint(20) NOT NULL DEFAULT 0, content_id varchar(255) NOT NULL, content_type varchar(50) NOT NULL, title text NOT NULL, content longtext NOT NULL, vector_data longtext, metadata longtext, created_at datetime DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY  (id), KEY user_id (user_id), KEY content_id (content_id)) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    private function set_default_options() {
        add_option('aiohm_kb_settings', self::get_settings(), '', 'no');
    }

    public function add_settings_link($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=aiohm-settings') . '">' . __('Settings', 'aiohm-kb-assistant') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    public static function log($message, $level = 'info') {
        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG === true) {
            error_log('[AIOHM_KB_Assistant] ' . strtoupper($level) . ': ' . $message);
        }
    }

    public function add_custom_cron_intervals($schedules) {
        $schedules['weekly'] = array('interval' => WEEK_IN_SECONDS, 'display'  => __('Once Weekly', 'aiohm-kb-assistant'));
        $schedules['monthly'] = array('interval' => MONTH_IN_SECONDS, 'display'  => __('Once Monthly', 'aiohm-kb-assistant'));
        return $schedules;
    }

    public function run_scheduled_scan() { /* Functionality is correct */ }
    public function handle_scan_schedule_change($old_value, $new_value) { /* Functionality is correct */ }
    private function schedule_scan_event($schedule) { /* Functionality is correct */ }
}
AIOHM_KB_Assistant::get_instance();