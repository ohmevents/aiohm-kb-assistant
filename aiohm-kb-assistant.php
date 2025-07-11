<?php
/**
 * Plugin Name: AIOHM Knowledge Assistant
 * Plugin URI:  https://aiohm.app
 * Description: Bring your wisdom to life. The AIOHM Knowledge Assistant listens, learns, and speaks in your brand's voice, offering real-time answers, soulful brand support, and intuitive guidance for your visitors.
 * Version:     1.1.10
 * Author:      OHM Events Agency
 * Author URI:  https://aiohm.app
 * Text Domain: aiohm-kb-assistant
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.8
 * Tested up to: 6.5
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) exit;

// Define plugin constants
define('AIOHM_KB_VERSION', '1.1.10');
define('AIOHM_KB_PLUGIN_FILE', __FILE__);
define('AIOHM_KB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AIOHM_KB_INCLUDES_DIR', AIOHM_KB_PLUGIN_DIR . 'includes/');
define('AIOHM_KB_PLUGIN_URL', plugin_dir_url(__FILE__));

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
            'shortcode-private-assistant.php', 
            'frontend-widget.php', 
            'chat-box.php', 
            'user-functions.php', 
            'pmpro-integration.php'
        ];
        foreach ($files as $file) {
            if (file_exists(AIOHM_KB_INCLUDES_DIR . $file)) { 
                require_once AIOHM_KB_INCLUDES_DIR . $file; 
            }
        }
    }
    
    public function init_plugin() {
        AIOHM_KB_Core_Init::init();
        AIOHM_KB_Settings_Page::init();
        AIOHM_KB_Shortcode_Chat::init();
        AIOHM_KB_Shortcode_Search::init();
        AIOHM_KB_Shortcode_Private_Assistant::init();
        AIOHM_KB_Frontend_Widget::init();
        AIOHM_KB_PMP_Integration::init();
    }
    
    public function activate() {
        require_once AIOHM_KB_INCLUDES_DIR . 'rag-engine.php';
        $this->create_tables();
        $this->create_conversation_tables();
        $this->create_project_tables(); // <-- Added this line
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
            'default_ai_provider' => 'openai',
            'chat_enabled'     => true,
            'show_floating_chat' => false,
            'scan_schedule'    => 'none',
            'chunk_size'       => 1000,
            'chunk_overlap'    => 200,
            'mirror_mode' => [
                'qa_system_message' => "You are the official AI Knowledge Assistant for \"%site_name%\".\n\nYour core mission is to embody our brand's tagline: \"%site_tagline%\".\n\nYou are to act as a thoughtful and emotionally intelligent guide for all website visitors, reflecting the unique voice of the brand. You should be aware that today is %day_of_week%, %current_date%.\n\n---\n\n**Core Instructions:**\n\n1.  **Primary Directive:** Your primary goal is to answer the user's question by grounding your response in the **context provided below**. This context is your main source of truth.\n\n2.  **Tone & Personality:**\n    * Speak with emotional clarity, not robotic formality.\n    * Sound like a thoughtful assistant, not a sales rep.\n    * Be concise, but not curt — useful, but never cold.\n    * Your purpose is to express with presence, not persuasion.\n\n3.  **Formatting Rules:**\n    * Use only basic HTML tags for clarity (like <strong> or <em> if needed). Do not use Markdown.\n    * Never end your response with a question like “Do you need help with anything else?”\n\n4.  **Fallback Response (Crucial):**\n    * If the provided context does not contain enough information to answer the user's question, you MUST respond with this exact phrase: \"Hmm… I don’t want to guess here. This might need a human’s wisdom. You can connect with the person behind this site on the contact page. They’ll know exactly how to help.\"\n\n---\n\n**Primary Context for Answering the User's Question:**\n{context}",
                'qa_temperature' => '0.8',
                'business_name' => get_bloginfo('name'),
                'ai_model' => 'gpt-3.5-turbo',
            ],
            'muse_mode' => [
                'system_prompt' => "You are Muse, a private brand assistant. Your role is to help the user develop their brand by using the provided context, which includes public information and the user's private 'Brand Soul' answers. Synthesize this information to provide creative ideas, answer strategic questions, and help draft content. Always prioritize the private 'Brand Soul' context when available.",
                'temperature' => '0.7',
                'assistant_name' => 'Muse',
                'ai_model' => 'gpt-4',
            ]
        ];
        $saved_settings = get_option('aiohm_kb_settings', []);
        
        $settings = wp_parse_args($saved_settings, $default_settings);
        $settings['mirror_mode'] = wp_parse_args($settings['mirror_mode'] ?? [], $default_settings['mirror_mode']);
        $settings['muse_mode'] = wp_parse_args($settings['muse_mode'] ?? [], $default_settings['muse_mode']);

        return $settings;
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
    
    private function create_conversation_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Table for conversations
        $table_conversations = $wpdb->prefix . 'aiohm_conversations';
        $sql_conversations = "CREATE TABLE $table_conversations (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) NOT NULL,
            title VARCHAR(255) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id)
        ) $charset_collate;";
        dbDelta($sql_conversations);

        // Table for individual messages
        $table_messages = $wpdb->prefix . 'aiohm_messages';
        $sql_messages = "CREATE TABLE $table_messages (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            conversation_id BIGINT(20) NOT NULL,
            sender ENUM('user', 'ai', 'system') NOT NULL,
            content LONGTEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY conversation_id (conversation_id)
        ) $charset_collate;";
        dbDelta($sql_messages);
    }
    
    /**
     * ---- NEW FUNCTION ----
     * Creates the projects table and updates the conversations table.
     */
    private function create_project_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
        // Table for projects
        $table_name_projects = $wpdb->prefix . 'aiohm_projects';
        $sql_projects = "CREATE TABLE $table_name_projects (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            project_name varchar(255) NOT NULL,
            creation_date datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id)
        ) $charset_collate;";
        dbDelta($sql_projects);
    
        // Add project_id to the conversations table
        $table_name_conversations = $wpdb->prefix . 'aiohm_conversations';
        if($wpdb->get_var("SHOW TABLES LIKE '$table_name_conversations'") == $table_name_conversations) {
            if ($wpdb->get_var("SHOW COLUMNS FROM `{$table_name_conversations}` LIKE 'project_id'") != 'project_id') {
                $wpdb->query("ALTER TABLE `{$table_name_conversations}` ADD `project_id` mediumint(9) NOT NULL DEFAULT 0;");
            }
        }
    }


    private function set_default_options() {
        if (get_option('aiohm_kb_settings') === false) {
            add_option('aiohm_kb_settings', self::get_settings());
        }
    }

    public function add_settings_link($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=aiohm-settings') . '">' . __('Settings', 'aiohm-kb-assistant') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    public static function log($message, $level = 'info') {
        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG === true) {
            error_log('[AIOHM_KB_Assistant] ' . strtoupper($level) . ': ' . print_r($message, true));
        }
    }

    public function add_custom_cron_intervals($schedules) {
        $schedules['weekly'] = array('interval' => WEEK_IN_SECONDS, 'display'  => __('Once Weekly', 'aiohm-kb-assistant'));
        $schedules['monthly'] = array('interval' => MONTH_IN_SECONDS, 'display'  => __('Once Monthly', 'aiohm-kb-assistant'));
        return $schedules;
    }

    public function run_scheduled_scan() {
        // Implementation for scheduled scan can go here.
    }
    public function handle_scan_schedule_change($old_value, $new_value) {
        // Implementation for handling schedule changes can go here.
    }
    private function schedule_scan_event($schedule) {
        // Implementation for scheduling the cron event can go here.
    }
}

AIOHM_KB_Assistant::get_instance();