<?php
/**
 * Frontend widget functionality - enqueue scripts and styles.
 * This version is complete and uses the central settings function.
 */
if (!defined('ABSPATH')) exit;

class AIOHM_KB_Frontend_Widget {
    
    public static function init() {
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_frontend_assets'));
    }
    
    /**
     * Enqueue frontend assets
     */
    public static function enqueue_frontend_assets() {
        // Load settings to check if chat is enabled
        $settings = AIOHM_KB_Assistant::get_settings();
        
        // Only load assets if chat is enabled or if the search shortcode is present
        // (Assuming search shortcode also uses aiohm-chat.js and aiohm-chat.css)
        global $post;
        $has_chat_shortcode = $post && has_shortcode($post->post_content, 'aiohm_chat');
        $has_search_shortcode = $post && has_shortcode($post->post_content, 'aiohm_search');

        if (!self::should_load_assets() && !$has_chat_shortcode && !$has_search_shortcode) {
            return;
        }
        
        wp_enqueue_script(
            'aiohm-chat',
            AIOHM_KB_PLUGIN_URL . 'assets/js/aiohm-chat.js',
            array('jquery'),
            AIOHM_KB_VERSION,
            true
        );
        
        wp_enqueue_style(
            'aiohm-chat',
            AIOHM_KB_PLUGIN_URL . 'assets/css/aiohm-chat.css',
            array(),
            AIOHM_KB_VERSION
        );
        
        // Pass chat_enabled setting to frontend JavaScript
        wp_localize_script('aiohm-chat', 'aiohm_config', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aiohm_chat_nonce'),
            'chat_enabled' => $settings['chat_enabled'] ?? true, // Ensure it's passed
            'show_floating_chat' => $settings['show_floating_chat'] ?? false, // Ensure it's passed
        ));
    }
    
    /**
     * Check if assets should be loaded on current page
     */
    private static function should_load_assets() {
        if (is_admin()) {
            return false;
        }
        
        global $post;
        // Check for chat shortcode and chat enable setting
        $settings = AIOHM_KB_Assistant::get_settings();
        if (($post && has_shortcode($post->post_content, 'aiohm_chat')) && ($settings['chat_enabled'] ?? true)) {
            return true;
        }
        
        // Check for floating chat and chat enable setting
        if (!empty($settings['show_floating_chat']) && ($settings['chat_enabled'] ?? true)) {
            return true;
        }
        
        // Check for search shortcode (always load assets for search shortcode)
        if ($post && has_shortcode($post->post_content, 'aiohm_search')) {
            return true;
        }
        
        return false;
    }
}