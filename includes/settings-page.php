<?php
/**
 * Settings Page controller for AIOHM Knowledge Assistant.
 * This version uses header and footer partials.
 */
if (!defined('ABSPATH')) exit;

class AIOHM_KB_Settings_Page {
    private static $instance = null;

    public static function init() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        add_action('admin_menu', array(self::$instance, 'register_admin_pages'));
        add_action('admin_enqueue_scripts', array(self::$instance, 'enqueue_admin_styles'));
    }

    private function include_header() {
        include_once AIOHM_KB_PLUGIN_DIR . 'templates/partials/header.php';
    }

    private function include_footer() {
        include_once AIOHM_KB_PLUGIN_DIR . 'templates/partials/footer.php';
    }

    public function register_admin_pages() {
        add_menu_page('AIOHM Assistant', 'AIOHM', 'manage_options', 'aiohm-dashboard', array($this, 'render_dashboard_page'), 'dashicons-admin-generic', 60);
        add_submenu_page('aiohm-dashboard', 'Dashboard', 'Dashboard', 'manage_options', 'aiohm-dashboard', array($this, 'render_dashboard_page'));
        add_submenu_page('aiohm-dashboard', 'AIOHM Settings', 'Settings', 'manage_options', 'aiohm-settings', array($this, 'render_form_settings_page'));
        add_submenu_page('aiohm-dashboard', 'Scan Content', 'Scan Content', 'manage_options', 'aiohm-scan-content', array($this, 'render_scan_page'));
        add_submenu_page('aiohm-dashboard', 'Manage Knowledge Base', 'Manage KB', 'manage_options', 'aiohm-manage-kb', array($this, 'render_manage_kb_page'));
        add_submenu_page('aiohm-dashboard', __('Get Help', 'aiohm-kb-assistant'), __('Get Help', 'aiohm-kb-assistant'), 'manage_options', 'aiohm-get-help', array($this, 'render_help_page'));
        add_submenu_page('aiohm-dashboard', __('AIOHM License', 'aiohm-kb-assistant'), __('License', 'aiohm-kb-assistant'), 'manage_options', 'aiohm-license', array($this, 'render_license_page'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function enqueue_admin_styles($hook) {
        if (strpos($hook, 'aiohm-') !== false || strpos($hook, '_page_aiohm-') !== false) {
            wp_enqueue_style( 'aiohm-admin-styles', AIOHM_KB_PLUGIN_URL . 'assets/css/aiohm-chat.css', array(), AIOHM_KB_VERSION );
        }
    }

    public function render_dashboard_page() {
        $this->include_header();
        include AIOHM_KB_PLUGIN_DIR . 'templates/admin-dashboard.php';
        $this->include_footer();
    }

    public function render_form_settings_page() {
        $this->include_header();
        include AIOHM_KB_PLUGIN_DIR . 'templates/admin-settings.php';
        $this->include_footer();
    }
    
    public function render_scan_page() {
        $site_crawler = new AIOHM_KB_Site_Crawler();
        $uploads_crawler = new AIOHM_KB_Uploads_Crawler();
        $site_stats = $site_crawler->get_scan_stats();
        $uploads_stats = $uploads_crawler->get_stats();
        $all_upload_items = $uploads_crawler->find_all_supported_attachments(); 
        $pending_website_items = $site_crawler->find_all_content();
        $this->include_header();
        include AIOHM_KB_PLUGIN_DIR . 'templates/scan-website.php';
        $this->include_footer();
    }
    
    public function render_manage_kb_page() {
        $this->include_header();
        $manager = new AIOHM_KB_Manager();
        $manager->display_page();
        $this->include_footer();
    }

    public function render_help_page() {
        $this->include_header();
        include AIOHM_KB_PLUGIN_DIR . 'templates/admin-help.php';
        $this->include_footer();
    }

    public function render_license_page() {
        $this->include_header();
        include AIOHM_KB_PLUGIN_DIR . 'templates/admin-license.php';
        $this->include_footer();
    }

    public function register_settings() {
        register_setting('aiohm_kb_settings', 'aiohm_kb_settings', array($this, 'sanitize_settings'));
    }

public function sanitize_settings($input) {
    // Start with the currently saved settings to preserve any keys not present in the submitted form.
    $sanitized = get_option('aiohm_kb_settings', []);

    // Merge the submitted input over the existing settings. This allows forms to only submit the fields they control.
    $input = wp_parse_args($input, $sanitized);

    // --- Sanitize all possible settings ---

    // Sanitize API Keys & Bot ID
    if (isset($input['aiohm_personal_bot_id'])) {
        $sanitized['aiohm_personal_bot_id'] = sanitize_text_field(trim($input['aiohm_personal_bot_id']));
    }
    if (isset($input['openai_api_key'])) {
        $sanitized['openai_api_key'] = sanitize_text_field(trim($input['openai_api_key']));
    }
    if (isset($input['gemini_api_key'])) {
        $sanitized['gemini_api_key'] = sanitize_text_field(trim($input['gemini_api_key']));
    }
    if (isset($input['claude_api_key'])) {
        $sanitized['claude_api_key'] = sanitize_text_field(trim($input['claude_api_key']));
    }
    
    // Sanitize Scan Schedule
    if (isset($input['scan_schedule'])) { 
        $allowed_schedules = ['none', 'daily', 'weekly', 'monthly'];
        $sanitized['scan_schedule'] = in_array($input['scan_schedule'], $allowed_schedules) ? sanitize_key($input['scan_schedule']) : 'none';
    }

    // Sanitize Checkboxes
    $sanitized['chat_enabled'] = isset($input['chat_enabled']) ? (bool) $input['chat_enabled'] : false;
    $sanitized['show_floating_chat'] = isset($input['show_floating_chat']) ? (bool) $input['show_floating_chat'] : false;
    $sanitized['enable_private_assistant'] = isset($input['enable_private_assistant']) ? (bool) $input['enable_private_assistant'] : false;
    
    return $sanitized;
}
}