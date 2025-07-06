<?php
/**
 * Settings Page controller for AIOHM Knowledge Assistant.
 * This version fetches fresh data for the scan page on every load.
 */
if (!defined('ABSPATH')) exit;

class AIOHM_KB_Settings_Page {
    private static $instance = null;

    public static function init() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        add_action('admin_menu', array(self::$instance, 'register_admin_pages'));
    }

    public function register_admin_pages() {
        add_menu_page('AIOHM Assistant', 'AIOHM', 'manage_options', 'aiohm-dashboard', array($this, 'render_dashboard_page'), 'dashicons-admin-generic', 60);
        add_submenu_page('aiohm-dashboard', 'Dashboard', 'Dashboard', 'manage_options', 'aiohm-dashboard', array($this, 'render_dashboard_page'));
        add_submenu_page('aiohm-dashboard', 'Scan Content', 'Scan Content', 'manage_options', 'aiohm-scan-content', array($this, 'render_scan_page'));
        add_submenu_page('aiohm-dashboard', 'Manage Knowledge Base', 'Manage KB', 'manage_options', 'aiohm-manage-kb', array($this, 'render_manage_kb_page'));
        add_submenu_page('aiohm-dashboard', 'AIOHM Settings', 'Settings', 'manage_options', 'aiohm-settings', array($this, 'render_form_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function render_dashboard_page() {
        include AIOHM_KB_PLUGIN_DIR . 'templates/admin-dashboard.php';
    }

    public function render_form_settings_page() {
        $settings = AIOHM_KB_Assistant::get_settings();
        include AIOHM_KB_PLUGIN_DIR . 'templates/admin-settings.php';
    }
    
    public function render_scan_page() {
        $site_crawler = new AIOHM_KB_Site_Crawler();
        $uploads_crawler = new AIOHM_KB_Uploads_Crawler();
        
        $site_stats = $site_crawler->get_scan_stats();
        $uploads_stats = $uploads_crawler->get_stats();

        $pending_website_items = $site_crawler->find_all_content();
        $pending_upload_items = $uploads_crawler->find_pending_attachments();

        include AIOHM_KB_PLUGIN_DIR . 'templates/scan-website.php';
    }
    
    public function render_manage_kb_page() {
        $manager = new AIOHM_KB_Manager();
        $manager->display_page();
    }

    public function register_settings() {
        register_setting('aiohm_kb_settings', 'aiohm_kb_settings', array($this, 'sanitize_settings'));
    }

    public function sanitize_settings($input) {
        $sanitized = [];
        if (isset($input['personal_api_key'])) { $sanitized['personal_api_key'] = sanitize_text_field(trim($input['personal_api_key'])); }
        if (isset($input['openai_api_key'])) { $sanitized['openai_api_key'] = sanitize_text_field(trim($input['openai_api_key'])); }
        // Removed 'system_prompt'
        if (isset($input['scan_schedule'])) { $sanitized['scan_schedule'] = sanitize_key($input['scan_schedule']); }
        $sanitized['chat_enabled'] = isset($input['chat_enabled']) ? (bool) $input['chat_enabled'] : false; // Added chat_enabled
        $sanitized['show_floating_chat'] = isset($input['show_floating_chat']) ? (bool) $input['show_floating_chat'] : false; // Added show_floating_chat
        return $sanitized;
    }
}