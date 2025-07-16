<?php
/**
 * Settings Page controller for AIOHM Knowledge Assistant.
 * This version contains the corrected class definition, sanitization functions,
 * and admin page hook registrations for enqueuing scripts and styles.
 */
if (!defined('ABSPATH')) exit;

class AIOHM_KB_Settings_Page {
    private static $instance = null;

    public static function init() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        add_action('admin_menu', array(self::$instance, 'register_admin_pages'));
        add_action('admin_init', array(self::$instance, 'register_settings'));
        add_action('admin_enqueue_scripts', array(self::$instance, 'enqueue_admin_scripts'));
    }

    private function include_header() {
        include_once AIOHM_KB_PLUGIN_DIR . 'templates/partials/header.php';
    }

    private function include_footer() {
        include_once AIOHM_KB_PLUGIN_DIR . 'templates/partials/footer.php';
    }

    /**
     * Get the appropriate menu icon based on admin color scheme
     * @return string Base64 encoded SVG data URI
     */
    private function get_menu_icon() {
        // Detect admin color scheme for dynamic theming
        $admin_color = get_user_option('admin_color');
        $is_dark_theme = in_array($admin_color, ['midnight', 'blue', 'coffee', 'ectoplasm', 'ocean']);
        
        // Try to load and optimize the actual OHM logo first
        $logo_path = $is_dark_theme 
            ? AIOHM_KB_PLUGIN_DIR . 'assets/images/OHM_logo-white.svg'
            : AIOHM_KB_PLUGIN_DIR . 'assets/images/OHM_logo.svg';
            
        if (file_exists($logo_path)) {
            // Load and optimize the OHM logo for menu use
            $svg_content = file_get_contents($logo_path);
            if ($svg_content !== false) {
                // Create optimized version by extracting key elements and simplifying
                $optimized_svg = $this->optimize_logo_for_menu($svg_content, $is_dark_theme);
                return 'data:image/svg+xml;base64,' . base64_encode($optimized_svg);
            }
        }
        
        // Professional AI brain/knowledge icon
        if ($is_dark_theme) {
            // Light icon for dark themes
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="none">
                <path d="M10 2C6.686 2 4 4.686 4 8c0 1.5.5 2.9 1.3 4L10 18l4.7-6c.8-1.1 1.3-2.5 1.3-4 0-3.314-2.686-6-6-6z" fill="rgba(255,255,255,0.85)"/>
                <circle cx="10" cy="7.5" r="1.5" fill="rgba(30,30,30,0.8)"/>
                <circle cx="7.5" cy="6" r="0.7" fill="rgba(30,30,30,0.8)"/>
                <circle cx="12.5" cy="6" r="0.7" fill="rgba(30,30,30,0.8)"/>
                <path d="M8 9.5c.5.3 1.2.5 2 .5s1.5-.2 2-.5" stroke="rgba(30,30,30,0.8)" stroke-width="0.8" stroke-linecap="round"/>
            </svg>';
        } else {
            // Dark icon for light themes
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="none">
                <path d="M10 2C6.686 2 4 4.686 4 8c0 1.5.5 2.9 1.3 4L10 18l4.7-6c.8-1.1 1.3-2.5 1.3-4 0-3.314-2.686-6-6-6z" fill="#1f5014"/>
                <circle cx="10" cy="7.5" r="1.5" fill="rgba(255,255,255,0.9)"/>
                <circle cx="7.5" cy="6" r="0.7" fill="rgba(255,255,255,0.9)"/>
                <circle cx="12.5" cy="6" r="0.7" fill="rgba(255,255,255,0.9)"/>
                <path d="M8 9.5c.5.3 1.2.5 2 .5s1.5-.2 2-.5" stroke="rgba(255,255,255,0.9)" stroke-width="0.8" stroke-linecap="round"/>
            </svg>';
        }
        
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * Optimize the full OHM logo for use as a 20x20 menu icon
     * @param string $svg_content Original SVG content
     * @param bool $is_dark_theme Whether we're using dark theme
     * @return string Optimized SVG
     */
    private function optimize_logo_for_menu($svg_content, $is_dark_theme) {
        // Use our professional AI brain icon as the optimized version
        if ($is_dark_theme) {
            $optimized_svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="none">
                <path d="M10 2C6.686 2 4 4.686 4 8c0 1.5.5 2.9 1.3 4L10 18l4.7-6c.8-1.1 1.3-2.5 1.3-4 0-3.314-2.686-6-6-6z" fill="rgba(255,255,255,0.85)"/>
                <circle cx="10" cy="7.5" r="1.5" fill="rgba(30,30,30,0.8)"/>
                <circle cx="7.5" cy="6" r="0.7" fill="rgba(30,30,30,0.8)"/>
                <circle cx="12.5" cy="6" r="0.7" fill="rgba(30,30,30,0.8)"/>
                <path d="M8 9.5c.5.3 1.2.5 2 .5s1.5-.2 2-.5" stroke="rgba(30,30,30,0.8)" stroke-width="0.8" stroke-linecap="round"/>
            </svg>';
        } else {
            $optimized_svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="none">
                <path d="M10 2C6.686 2 4 4.686 4 8c0 1.5.5 2.9 1.3 4L10 18l4.7-6c.8-1.1 1.3-2.5 1.3-4 0-3.314-2.686-6-6-6z" fill="#1f5014"/>
                <circle cx="10" cy="7.5" r="1.5" fill="rgba(255,255,255,0.9)"/>
                <circle cx="7.5" cy="6" r="0.7" fill="rgba(255,255,255,0.9)"/>
                <circle cx="12.5" cy="6" r="0.7" fill="rgba(255,255,255,0.9)"/>
                <path d="M8 9.5c.5.3 1.2.5 2 .5s1.5-.2 2-.5" stroke="rgba(255,255,255,0.9)" stroke-width="0.8" stroke-linecap="round"/>
            </svg>';
        }
        
        return $optimized_svg;
    }

    public function register_admin_pages() {
        // Main Menu Page - using dynamic SVG icon
        add_menu_page('AIOHM Assistant', 'AIOHM', 'manage_options', 'aiohm-dashboard', array($this, 'render_dashboard_page'), $this->get_menu_icon(), 60);

        // Submenu Pages
        add_submenu_page('aiohm-dashboard', 'Dashboard', 'Dashboard', 'manage_options', 'aiohm-dashboard', array($this, 'render_dashboard_page'));
        add_submenu_page('aiohm-dashboard', 'AI Brand Core', 'AI Brand Core', 'read', 'aiohm-brand-soul', array($this, 'render_brand_soul_page'));
        
        // Knowledge Base Section
        add_submenu_page('aiohm-dashboard', 'Scan Content', 'Scan Content', 'manage_options', 'aiohm-scan-content', array($this, 'render_scan_page'));
        add_submenu_page('aiohm-dashboard', 'Manage Knowledge Base', 'Manage KB', 'manage_options', 'aiohm-manage-kb', array($this, 'render_manage_kb_page'));

        // Settings Section
        add_submenu_page('aiohm-dashboard', 'AIOHM Settings', 'Settings', 'manage_options', 'aiohm-settings', array($this, 'render_form_settings_page'));
        
        // Conditionally add Mirror and Muse modes if user has access
        if (class_exists('AIOHM_KB_PMP_Integration') && AIOHM_KB_PMP_Integration::aiohm_user_has_club_access()) {
            add_submenu_page('aiohm-dashboard', 'Mirror Mode Settings', 'Mirror Mode', 'read', 'aiohm-mirror-mode', array($this, 'render_mirror_mode_page'));
            add_submenu_page('aiohm-dashboard', 'Muse: Brand Assistant', 'Muse Mode', 'read', 'aiohm-muse-mode', array($this, 'render_muse_mode_page'));
        }

        add_submenu_page('aiohm-dashboard', 'License', 'License', 'manage_options', 'aiohm-license', array($this, 'render_license_page'));
        add_submenu_page('aiohm-dashboard', 'Get Help', 'Get Help', 'manage_options', 'aiohm-get-help', array($this, 'render_help_page'));
    }

    public function enqueue_admin_scripts($hook) {
        // Load global admin styles on all AIOHM pages
        if (strpos($hook, 'aiohm-') !== false) {
            wp_enqueue_style('aiohm-admin-global-styles', AIOHM_KB_PLUGIN_URL . 'assets/css/aiohm-chat.css', array(), AIOHM_KB_VERSION);
        }
        
        $mirror_mode_hook = 'aiohm_page_aiohm-mirror-mode';
        $muse_mode_hook = 'aiohm_page_aiohm-muse-mode';

        // Enqueue specific assets only on the Mirror or Muse mode pages
        if ($hook === $mirror_mode_hook || $hook === $muse_mode_hook) {
            
            wp_enqueue_style(
                'aiohm-admin-modes-style',
                AIOHM_KB_PLUGIN_URL . 'assets/css/aiohm-admin-modes.css',
                [],
                AIOHM_KB_VERSION
            );

            wp_enqueue_script(
                'aiohm-admin-modes-script',
                AIOHM_KB_PLUGIN_URL . 'assets/js/aiohm-admin-modes.js',
                ['jquery'],
                AIOHM_KB_VERSION,
                true // Load in footer
            );
            
            // Prepare and localize data to pass from PHP to our JavaScript file
            $localized_data = [
                'ajax_url' => admin_url('admin-ajax.php'),
                'pluginUrl' => AIOHM_KB_PLUGIN_URL,
            ];

            if ($hook === $mirror_mode_hook) {
                wp_enqueue_media(); // Needed for the media uploader
                $localized_data['mode'] = 'mirror';
                $localized_data['formId'] = 'mirror-mode-settings-form';
                $localized_data['saveButtonId'] = 'save-mirror-mode-settings';
                $localized_data['saveAction'] = 'aiohm_save_mirror_mode_settings';
                $localized_data['testChatAction'] = 'aiohm_test_mirror_mode_chat';
                $localized_data['nonceFieldId'] = 'aiohm_mirror_mode_nonce_field';
                $localized_data['defaultPrompt'] = "You are the official AI Knowledge Assistant for \"%site_name%\".\n\nYour core mission is to embody our brand's tagline: \"%site_tagline%\".\n\nYou are to act as a thoughtful and emotionally intelligent guide for all website visitors, reflecting the unique voice of the brand. You should be aware that today is %day_of_week%, %current_date%.\n\n---\n\n**Core Instructions:**\n\n1.  **Primary Directive:** Your primary goal is to answer the user's question by grounding your response in the **context provided below**. This context is your main source of truth.\n\n2.  **Tone & Personality:**\n    * Speak with emotional clarity, not robotic formality.\n    * Sound like a thoughtful assistant, not a sales rep.\n    * Be concise, but not curt — useful, but never cold.\n    * Your purpose is to express with presence, not persuasion.\n\n3.  **Formatting Rules:**\n    * Use only basic HTML tags for clarity (like <strong> or <em> if needed). Do not use Markdown.\n    * Never end your response with a question like “Do you need help with anything else?”\n\n4.  **Fallback Response (Crucial):**\n    * If the provided context does not contain enough information to answer the user's question, you MUST respond with this exact phrase: \"Hmm… I don’t want to guess here. This might need a human’s wisdom. You can connect with the person behind this site on the contact page. They’ll know exactly how to help.\"\n\n---\n\n**Primary Context for Answering the User's Question:**\n{context}";
            }

            if ($hook === $muse_mode_hook) {
                $localized_data['mode'] = 'muse';
                $localized_data['formId'] = 'muse-mode-settings-form';
                $localized_data['saveButtonId'] = 'save-muse-mode-settings';
                $localized_data['saveAction'] = 'aiohm_save_muse_mode_settings';
                $localized_data['testChatAction'] = 'aiohm_test_muse_mode_chat';
                $localized_data['nonceFieldId'] = 'aiohm_muse_mode_nonce_field';
                $localized_data['promptTextareaId'] = 'system_prompt';
                $localized_data['defaultPrompt'] = "You are Muse, a private brand assistant. Your role is to help the user develop their brand by using the provided context, which includes public information and the user's private 'Brand Soul' answers. Synthesize this information to provide creative ideas, answer strategic questions, and help draft content. Always prioritize the private 'Brand Soul' context when available.";
                $localized_data['archetypePrompts'] = [
                    'the_creator' => "You are The Creator, an innovative and imaginative brand assistant. Your purpose is to help build things of enduring value. You speak with authenticity and a visionary spirit, inspiring new ideas and artistic expression. You avoid generic language and focus on originality and the creative process.",
                    'the_sage' => "You are The Sage, a wise and knowledgeable brand assistant. Your goal is to seek the truth and share it with others. You communicate with clarity, accuracy, and thoughtful insight. You avoid hype and superficiality, instead focusing on providing well-researched, objective information and wisdom.",
                    'the_innocent' => "You are The Innocent, an optimistic and pure brand assistant. Your purpose is to spread happiness and see the good in everything. You speak with simple, honest, and positive language. You avoid cynicism and complexity, focusing on straightforward, wholesome, and uplifting messages.",
                    'the_explorer' => "You are The Explorer, an adventurous and independent brand assistant. Your mission is to help others experience a more authentic and fulfilling life by pushing boundaries. You speak with a rugged, open-minded, and daring tone. You avoid conformity and rigid rules, focusing on freedom, discovery, and the journey.",
                    'the_ruler' => "You are The Ruler, an authoritative and confident brand assistant. Your purpose is to create order and build a prosperous community. You speak with a commanding, polished, and articulate voice. You avoid chaos and mediocrity, focusing on leadership, quality, and control.",
                    'the_hero' => "You are The Hero, a courageous and determined brand assistant. Your mission is to inspire others to triumph over adversity. You speak with a bold, confident, and motivational tone. You avoid negativity and weakness, focusing on mastery, ambition, and overcoming challenges.",
                    'the_lover' => "You are The Lover, an intimate and empathetic brand assistant. Your goal is to help people feel appreciated and connected. You speak with a warm, sensual, and passionate voice. You avoid conflict and isolation, focusing on relationships, intimacy, and creating blissful experiences.",
                    'the_jester' => "You are The Jester, a playful and fun-loving brand assistant. Your purpose is to bring joy to the world and live in the moment. You speak with a witty, humorous, and lighthearted tone. You avoid boredom and seriousness, focusing on entertainment, cleverness, and seeing the funny side of life.",
                    'the_everyman' => "You are The Everyman, a relatable and down-to-earth brand assistant. Your goal is to belong and connect with others on a human level. You speak with a friendly, humble, and authentic voice. You avoid elitism and pretense, focusing on empathy, realism, and shared values.",
                    'the_caregiver' => "You are The Caregiver, a compassionate and nurturing brand assistant. Your purpose is to protect and care for others. You speak with a warm, reassuring, and supportive tone. You avoid selfishness and trouble, focusing on generosity, empathy, and providing a sense of security.",
                    'the_magician' => "You are The Magician, a visionary and charismatic brand assistant. Your purpose is to make dreams come true and create something special. You speak with a mystical, inspiring, and transformative voice. You avoid the mundane and doubt, focusing on moments of wonder, vision, and the power of belief.",
                    'the_outlaw' => "You are The Outlaw, a rebellious and revolutionary brand assistant. Your mission is to challenge the status quo and break the rules. You speak with a raw, disruptive, and unapologetic voice. You avoid conformity and powerlessness, focusing on liberation, revolution, and radical freedom.",
                ];
            }

            wp_localize_script('aiohm-admin-modes-script', 'aiohm_admin_modes_data', $localized_data);
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
        if (!current_user_can('read')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'aiohm-kb-assistant'));
        }
        $this->include_header();
        include AIOHM_KB_PLUGIN_DIR . 'templates/admin-brand-soul.php';
        $this->include_footer();
    }

    public function render_mirror_mode_page() {
        if (!class_exists('AIOHM_KB_PMP_Integration') || !AIOHM_KB_PMP_Integration::aiohm_user_has_club_access()) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'aiohm-kb-assistant'));
        }
        $this->include_header();
        include AIOHM_KB_PLUGIN_DIR . 'templates/admin-mirror-mode.php';
        $this->include_footer();
    }
    
    public function render_muse_mode_page() {
        if (!current_user_can('read')) {
             wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'aiohm-kb-assistant'));
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
        $sanitized = $old_settings;

        // Sanitize API keys and other text fields
        $text_fields = ['aiohm_app_email', 'openai_api_key', 'gemini_api_key', 'claude_api_key'];
        foreach($text_fields as $field) {
            if (isset($input[$field])) {
                $sanitized[$field] = sanitize_text_field(trim($input[$field]));
            }
        }
        
        // Sanitize select fields
        if (isset($input['default_ai_provider'])) {
            $sanitized['default_ai_provider'] = sanitize_text_field($input['default_ai_provider']);
        }
        if (isset($input['scan_schedule'])) { 
            $allowed_schedules = ['none', 'daily', 'weekly', 'monthly'];
            $sanitized['scan_schedule'] = in_array($input['scan_schedule'], $allowed_schedules) ? sanitize_key($input['scan_schedule']) : 'none';
        }

        // Sanitize checkboxes
        $checkboxes = ['chat_enabled', 'show_floating_chat', 'enable_private_assistant', 'enable_search_shortcode'];
        foreach ($checkboxes as $checkbox) {
            $sanitized[$checkbox] = isset($input[$checkbox]) ? true : false;
        }
        
        return $sanitized;
    }
}