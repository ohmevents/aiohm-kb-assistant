<?php
/**
 * Plugin Name: AIOHM Knowledge Assistant
 * Plugin URI: https://aiohm.app
 * Description: Bring your wisdom to life. The AIOHM Knowledge Assistant listens, learns, and speaks in your brand's voice, offering real-time answers, soulful brand support, and intuitive guidance for your visitors. With Muse and Mirror modes, it doesn't just respond - it resonates.
 * Version: 1.1.5
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
define('AIOHM_KB_VERSION', '1.1.5');
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
        // Corrected: Removed the problematic require_once for admin-license.php
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
            'chat-box.php'
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
            'personal_api_key' => '',
            'openai_api_key'   => '',
            'chat_enabled'     => true,
            'show_floating_chat' => false,
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
            'chat_enabled' => true,
            'show_floating_chat' => false,
            'scan_schedule' => 'none',
        ], '', 'no');
    }

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
        // Corrected the typo from WP_DEBUG_log to WP_DEBUG_LOG
        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG === true) {
            $log_prefix = '[AIOHM_KB_Assistant] ' . strtoupper($level) . ': ';
            error_log($log_prefix . $message);
        }
    }

    /**
     * Adds custom cron intervals for weekly and monthly.
     * @param array $schedules Existing WP-Cron schedules.
     * @return array Modified schedules.
     */
    public function add_custom_cron_intervals($schedules) {
        $schedules['weekly'] = array(
            'interval' => WEEK_IN_SECONDS,
            'display'  => __('Once Weekly', 'aiohm-kb-assistant'),
        );
        $schedules['monthly'] = array(
            'interval' => MONTH_IN_SECONDS,
            'display'  => __('Once Monthly', 'aiohm-kb-assistant'),
        );
        return $schedules;
    }

    /**
     * The callback function for the scheduled scan event.
     */
    public function run_scheduled_scan() {
        self::log('Running scheduled content scan.', 'info');

        $settings = self::get_settings();
        if (empty($settings['openai_api_key'])) {
            self::log('Scheduled scan skipped: OpenAI API key is not configured.', 'warning');
            return;
        }

        try {
            $site_crawler = new AIOHM_KB_Site_Crawler();
            $all_website_items = $site_crawler->find_all_content();
            $website_item_ids_to_add = array_map(function($item) {
                return $item['id'];
            }, array_filter($all_website_items, function($item) {
                return $item['status'] === 'Ready to Add';
            }));

            if (!empty($website_item_ids_to_add)) {
                $site_crawler->add_items_to_kb($website_item_ids_to_add);
                self::log('Processed ' . count($website_item_ids_to_add) . ' pending website items during scheduled scan.', 'info');
            } else {
                self::log('No pending website items to process during scheduled scan.', 'info');
            }
        } catch (Exception $e) {
            self::log('Error during scheduled website content scan: ' . $e->getMessage(), 'error');
        }

        try {
            $uploads_crawler = new AIOHM_KB_Uploads_Crawler();
            $pending_uploads = $uploads_crawler->find_pending_attachments();
            $upload_item_ids_to_add = array_map(function($item) {
                return $item['id'];
            }, $pending_uploads);

            if (!empty($upload_item_ids_to_add)) {
                $uploads_crawler->add_attachments_to_kb($upload_item_ids_to_add);
                self::log('Processed ' . count($upload_item_ids_to_add) . ' pending upload items during scheduled scan.', 'info');
            } else {
                self::log('No pending upload items to process during scheduled scan.', 'info');
            }
        } catch (Exception $e) {
            self::log('Error during scheduled uploads scan: ' . $e->getMessage(), 'error');
        }

        self::log('Scheduled content scan finished.', 'info');
    }

    /**
     * Handles updating the WP-Cron schedule when the plugin settings are saved.
     */
    public function handle_scan_schedule_change($old_value, $new_value) {
        $old_schedule = $old_value['scan_schedule'] ?? 'none';
        $new_schedule = $new_value['scan_schedule'] ?? 'none';

        wp_clear_scheduled_hook(AIOHM_KB_SCHEDULED_SCAN_HOOK);

        if ($new_schedule !== 'none') {
            $this->schedule_scan_event($new_schedule);
            self::log('Scan schedule updated to: ' . $new_schedule, 'info');
        } else {
            self::log('Scheduled scan cleared.', 'info');
        }
    }

    /**
     * Schedules the WP-Cron event for content scanning.
     */
    private function schedule_scan_event($schedule) {
        if (!wp_next_scheduled(AIOHM_KB_SCHEDULED_SCAN_HOOK) && $schedule !== 'none') {
            wp_schedule_event(time(), $schedule, AIOHM_KB_SCHEDULED_SCAN_HOOK);
        }
    }
}
AIOHM_KB_Assistant::get_instance();