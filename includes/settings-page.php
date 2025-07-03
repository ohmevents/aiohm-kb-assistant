<?php
/**
 * Settings Page for AIOHM Knowledge Assistant
 *
 * @package AIOHM_KB_Assistant
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AIOHM_KB_Settings_Page {

    private static $instance = null;

    /**
     * Get a single instance of the class
     */
    public static function init() {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        // Register admin menu
        add_action('admin_menu', array(self::$instance, 'register_settings_page'));
    }

    /**
     * Register the settings page in the WordPress admin menu
     */
    public function register_settings_page() {
        add_menu_page(
            __('AIOHM Knowledge Assistant Settings', 'aiohm-kb-assistant'),
            __('AIOHM Settings', 'aiohm-kb-assistant'),
            'manage_options',
            'aiohm-settings',
            array($this, 'render_settings_page'),
            'dashicons-admin-settings',
            60
        );

        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Render the settings page
     */
    public function render_settings_page() {
        $settings = get_option('aiohm_kb_settings', array());
        include AIOHM_KB_PLUGIN_DIR . 'templates/admin-settings.php';
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting('aiohm_kb_settings', 'aiohm_kb_settings', array($this, 'sanitize_settings'));

        // Sections and fields
        add_settings_section(
            'aiohm_api_settings',
            __('API Configuration', 'aiohm-kb-assistant'),
            '__return_false',
            'aiohm-settings'
        );

        add_settings_field(
            'openai_api_key',
            __('OpenAI API Key', 'aiohm-kb-assistant'),
            array($this, 'render_text_field'),
            'aiohm-settings',
            'aiohm_api_settings',
            array('id' => 'openai_api_key', 'description' => __('Your OpenAI API key for GPT models.', 'aiohm-kb-assistant'))
        );

        add_settings_field(
            'claude_api_key',
            __('Claude API Key', 'aiohm-kb-assistant'),
            array($this, 'render_text_field'),
            'aiohm-settings',
            'aiohm_api_settings',
            array('id' => 'claude_api_key', 'description' => __('Your Anthropic Claude API key.', 'aiohm-kb-assistant'))
        );

        add_settings_field(
            'default_model',
            __('Default Model', 'aiohm-kb-assistant'),
            array($this, 'render_select_field'),
            'aiohm-settings',
            'aiohm_api_settings',
            array(
                'id' => 'default_model',
                'options' => array(
                    'openai' => __('OpenAI GPT', 'aiohm-kb-assistant'),
                    'claude' => __('Anthropic Claude', 'aiohm-kb-assistant')
                )
            )
        );

        add_settings_section(
            'aiohm_chat_settings',
            __('Chat Configuration', 'aiohm-kb-assistant'),
            '__return_false',
            'aiohm-settings'
        );

        add_settings_field(
            'chat_enabled',
            __('Enable Chat', 'aiohm-kb-assistant'),
            array($this, 'render_checkbox_field'),
            'aiohm-settings',
            'aiohm_chat_settings',
            array('id' => 'chat_enabled', 'description' => __('Enable chat functionality on frontend', 'aiohm-kb-assistant'))
        );

        add_settings_field(
            'max_tokens',
            __('Max Tokens', 'aiohm-kb-assistant'),
            array($this, 'render_number_field'),
            'aiohm-settings',
            'aiohm_chat_settings',
            array('id' => 'max_tokens', 'min' => 50, 'max' => 2000, 'description' => __('Maximum tokens for AI responses (50-2000).', 'aiohm-kb-assistant'))
        );

        add_settings_field(
            'temperature',
            __('Temperature', 'aiohm-kb-assistant'),
            array($this, 'render_number_field'),
            'aiohm-settings',
            'aiohm_chat_settings',
            array('id' => 'temperature', 'min' => 0, 'max' => 2, 'step' => 0.1, 'description' => __('Response creativity (0.0 = focused, 2.0 = creative).', 'aiohm-kb-assistant'))
        );

        add_settings_section(
            'aiohm_processing_settings',
            __('Processing Configuration', 'aiohm-kb-assistant'),
            '__return_false',
            'aiohm-settings'
        );

        add_settings_field(
            'chunk_size',
            __('Chunk Size', 'aiohm-kb-assistant'),
            array($this, 'render_number_field'),
            'aiohm-settings',
            'aiohm_processing_settings',
            array('id' => 'chunk_size', 'min' => 500, 'max' => 3000, 'description' => __('Size of content chunks for processing (500-3000 characters).', 'aiohm-kb-assistant'))
        );

        add_settings_field(
            'chunk_overlap',
            __('Chunk Overlap', 'aiohm-kb-assistant'),
            array($this, 'render_number_field'),
            'aiohm-settings',
            'aiohm_processing_settings',
            array('id' => 'chunk_overlap', 'min' => 0, 'max' => 500, 'description' => __('Overlap between chunks to maintain context (0-500 characters).', 'aiohm-kb-assistant'))
        );
    }

    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();

        if (isset($input['openai_api_key'])) {
            $sanitized['openai_api_key'] = sanitize_text_field($input['openai_api_key']);
        }

        if (isset($input['claude_api_key'])) {
            $sanitized['claude_api_key'] = sanitize_text_field($input['claude_api_key']);
        }

        if (isset($input['default_model']) && in_array($input['default_model'], array('openai', 'claude'), true)) {
            $sanitized['default_model'] = sanitize_text_field($input['default_model']);
        }

        $sanitized['chat_enabled'] = isset($input['chat_enabled']) && $input['chat_enabled'] === '1';

        if (isset($input['max_tokens']) && is_numeric($input['max_tokens'])) {
            $sanitized['max_tokens'] = intval($input['max_tokens']);
        }

        if (isset($input['temperature']) && is_numeric($input['temperature'])) {
            $sanitized['temperature'] = floatval($input['temperature']);
        }

        if (isset($input['chunk_size']) && is_numeric($input['chunk_size'])) {
            $sanitized['chunk_size'] = intval($input['chunk_size']);
        }

        if (isset($input['chunk_overlap']) && is_numeric($input['chunk_overlap'])) {
            $sanitized['chunk_overlap'] = intval($input['chunk_overlap']);
        }

        return $sanitized;
    }

    /**
     * Render text field
     */
    public function render_text_field($args) {
        $settings = get_option('aiohm_kb_settings', array());
        $value = isset($settings[$args['id']]) ? $settings[$args['id']] : '';
        printf(
            '<input type="text" id="%s" name="aiohm_kb_settings[%s]" value="%s" class="regular-text">',
            esc_attr($args['id']),
            esc_attr($args['id']),
            esc_attr($value)
        );
        if (isset($args['description'])) {
            printf('<p class="description">%s</p>', esc_html($args['description']));
        }
    }

    /**
     * Render checkbox field
     */
    public function render_checkbox_field($args) {
        $settings = get_option('aiohm_kb_settings', array());
        $value = isset($settings[$args['id']]) ? $settings[$args['id']] : false;
        printf(
            '<label><input type="checkbox" id="%s" name="aiohm_kb_settings[%s]" value="1" %s> %s</label>',
            esc_attr($args['id']),
            esc_attr($args['id']),
            checked($value, true, false),
            esc_html($args['description'])
        );
    }

    /**
     * Render number field
     */
    public function render_number_field($args) {
        $settings = get_option('aiohm_kb_settings', array());
        $value = isset($settings[$args['id']]) ? $settings[$args['id']] : '';
        printf(
            '<input type="number" id="%s" name="aiohm_kb_settings[%s]" value="%s" min="%s" max="%s" step="%s">',
            esc_attr($args['id']),
            esc_attr($args['id']),
            esc_attr($value),
            isset($args['min']) ? esc_attr($args['min']) : '',
            isset($args['max']) ? esc_attr($args['max']) : '',
            isset($args['step']) ? esc_attr($args['step']) : '1'
        );
        if (isset($args['description'])) {
            printf('<p class="description">%s</p>', esc_html($args['description']));
        }
    }

    /**
     * Render select field
     */
    public function render_select_field($args) {
        $settings = get_option('aiohm_kb_settings', array());
        $value = isset($settings[$args['id']]) ? $settings[$args['id']] : '';
        printf('<select id="%s" name="aiohm_kb_settings[%s]">', esc_attr($args['id']), esc_attr($args['id']));
        foreach ($args['options'] as $key => $label) {
            printf('<option value="%s" %s>%s</option>', esc_attr($key), selected($value, $key, false), esc_html($label));
        }
        printf('</select>');
    }
}

// Initialize the settings page
AIOHM_KB_Settings_Page::init();