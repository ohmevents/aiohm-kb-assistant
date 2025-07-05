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
        if (!self::should_load_assets()) {
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
        
        // ** THE FIX IS HERE: Calling the correct central settings function **
        $settings = AIOHM_KB_Assistant::get_settings();
        
        wp_localize_script('aiohm-chat', 'aiohm_config', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aiohm_chat_nonce'),
            'chat_enabled' => $settings['chat_enabled'] ?? true,
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
        if ($post && has_shortcode($post->post_content, 'aiohm_chat')) {
            return true;
        }
        
        // ** THE FIX IS HERE: Calling the correct central settings function **
        $settings = AIOHM_KB_Assistant::get_settings();
        if (!empty($settings['show_floating_chat'])) {
            return true;
        }
        
        return false;
    }
}