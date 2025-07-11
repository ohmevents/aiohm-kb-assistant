<?php
/**
 * Settings Page controller for AIOHM Knowledge Assistant.
 * This version uses header and footer partials and includes the new Mirror Mode page.
 */
if (!defined('ABSPATH')) exit;

class AIOHM_KB_Settings_Page {
    private static $instance = null;

    public static function init() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        add_action('admin_menu', array(self::$instance, 'register_admin_pages'));
        add_action('admin_enqueue_scripts', array(self::$instance, 'enqueue_admin_scripts'));
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

        if (class_exists('AIOHM_KB_PMP_Integration') && AIOHM_KB_PMP_Integration::aiohm_user_has_club_access()) {
            add_submenu_page('aiohm-dashboard', 'Mirror Mode Settings', 'Mirror Mode', 'read', 'aiohm-mirror-mode', array($this, 'render_mirror_mode_page'));
            add_submenu_page('aiohm-dashboard', 'Muse: Brand Assistant', 'Muse Mode', 'read', 'aiohm-muse-mode', array($this, 'render_muse_mode_page'));
        }
        
        add_submenu_page('aiohm-dashboard', 'Scan Content', 'Scan Content', 'manage_options', 'aiohm-scan-content', array($this, 'render_scan_page'));
        add_submenu_page('aiohm-dashboard', 'Manage Knowledge Base', 'Manage KB', 'manage_options', 'aiohm-manage-kb', array($this, 'render_manage_kb_page'));
        add_submenu_page('aiohm-dashboard', __('AI Brand Core', 'aiohm-kb-assistant'), __('AI Brand Core', 'aiohm-kb-assistant'), 'read', 'aiohm-brand-soul', array($this, 'render_brand_soul_page'));
        add_submenu_page('aiohm-dashboard', __('Get Help', 'aiohm-kb-assistant'), __('Get Help', 'aiohm-kb-assistant'), 'manage_options', 'aiohm-get-help', array($this, 'render_help_page'));
        add_submenu_page('aiohm-dashboard', __('AIOHM License', 'aiohm-kb-assistant'), __('License', 'aiohm-kb-assistant'), 'manage_options', 'aiohm-license', array($this, 'render_license_page'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'aiohm-') !== false || strpos($hook, '_page_aiohm-') !== false) {
            wp_enqueue_style( 'aiohm-admin-styles', AIOHM_KB_PLUGIN_URL . 'assets/css/aiohm-chat.css', array(), AIOHM_KB_VERSION );
            
            // --- THIS IS THE KEY CHANGE ---
            // Enqueue WordPress media scripts only on the mirror mode page
            if ($hook === 'aiohm_page_aiohm-mirror-mode') {
                wp_enqueue_media();
            }
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

    public function render_brand_soul_page() {
        if (!current_user_can('manage_options') && !in_array('minim_tribe', (array) wp_get_current_user()->roles)) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        $this->include_header();
        include AIOHM_KB_PLUGIN_DIR . 'templates/admin-brand-soul.php';
        $this->include_footer();
    }

    public function render_mirror_mode_page() {
        if (!class_exists('AIOHM_KB_PMP_Integration') || !AIOHM_KB_PMP_Integration::aiohm_user_has_club_access()) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'aiohm-kb-assistant'));
        }
        $this->include_header();
        // The new file will be named admin-mirror-mode.php
        include AIOHM_KB_PLUGIN_DIR . 'templates/admin-mirror-mode.php';
        $this->include_footer();
    }
    
    public function render_muse_mode_page() {
        // Access control is handled here before rendering
        if (!current_user_can('administrator') && !current_user_can('ohm_brand_collaborator')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'aiohm-kb-assistant'));
        }
        $this->include_header();
        include AIOHM_KB_PLUGIN_DIR . 'templates/admin-muse-mode.php';
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
        $old_settings = get_option('aiohm_kb_settings', []);
        $sanitized = [];

        // Text fields
        $text_fields = ['aiohm_app_arm_user_id', 'aiohm_app_email', 'openai_api_key', 'gemini_api_key', 'claude_api_key', 'qa_desktop_width', 'qa_desktop_height', 'business_name'];
        foreach ($text_fields as $field) {
            if (isset($input[$field])) {
                $sanitized[$field] = sanitize_text_field(trim($input[$field]));
            } elseif (isset($old_settings[$field])) {
                $sanitized[$field] = $old_settings[$field];
            }
        }
        
        // Textarea
        if (isset($input['qa_system_message'])) {
            $sanitized['qa_system_message'] = sanitize_textarea_field($input['qa_system_message']);
        } elseif (isset($old_settings['qa_system_message'])) {
            $sanitized['qa_system_message'] = $old_settings['qa_system_message'];
        }

        // Select field
        if (isset($input['scan_schedule'])) { 
            $allowed_schedules = ['none', 'daily', 'weekly', 'monthly'];
            $sanitized['scan_schedule'] = in_array($input['scan_schedule'], $allowed_schedules) ? sanitize_key($input['scan_schedule']) : 'none';
        } elseif (isset($old_settings['scan_schedule'])) {
             $sanitized['scan_schedule'] = $old_settings['scan_schedule'];
        }

        // Checkboxes
        $checkboxes = ['chat_enabled', 'show_floating_chat', 'enable_private_assistant'];
        foreach ($checkboxes as $checkbox) {
            $sanitized[$checkbox] = isset($input[$checkbox]) ? (bool) $input[$checkbox] : false;
        }

        // Float
        if (isset($input['qa_temperature'])) {
            $sanitized['qa_temperature'] = floatval($input['qa_temperature']);
        } elseif (isset($old_settings['qa_temperature'])) {
            $sanitized['qa_temperature'] = $old_settings['qa_temperature'];
        }
        
        return $sanitized;
    }
}