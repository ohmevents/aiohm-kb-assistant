<?php
/**
 * Chat shortcode implementation - [aiohm_chat]
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AIOHM_KB_Shortcode_Chat {
    
    public static function init() {
        add_shortcode('aiohm_chat', array(__CLASS__, 'render_chat_shortcode'));
    }
    
    /**
     * Render chat shortcode
     */
    public static function render_chat_shortcode($atts) {
        $settings = AIOHM_KB_Core_Init::get_settings();
        
        // Check if chat is enabled
        if (!$settings['chat_enabled']) {
            return '<div class="aiohm-chat-disabled">' . __('Chat is currently disabled.', 'aiohm-kb-assistant') . '</div>';
        }
        
        // Parse shortcode attributes
        $atts = shortcode_atts(array(
            'title' => __('Ask me anything', 'aiohm-kb-assistant'),
            'placeholder' => __('Type your question here...', 'aiohm-kb-assistant'),
            'height' => '400',
            'width' => '100%',
            'theme' => 'default',
            'position' => 'inline',
            'show_branding' => 'true',
            'welcome_message' => '',
            'max_height' => '600'
        ), $atts, 'aiohm_chat');
        
        // Generate unique chat ID
        static $chat_counter = 0;
        $chat_counter++;
        $chat_id = 'aiohm-chat-' . $chat_counter;
        
        // Enqueue chat assets if not already done
        wp_enqueue_script('aiohm-chat');
        wp_enqueue_style('aiohm-chat');
        
        // Build chat container
        $output = '<div class="aiohm-chat-container aiohm-chat-theme-' . esc_attr($atts['theme']) . '" id="' . esc_attr($chat_id) . '"';
        
        // Add inline styles
        $styles = array();
        if ($atts['width'] !== '100%') {
            $styles[] = 'width: ' . esc_attr($atts['width']);
        }
        if (!empty($styles)) {
            $output .= ' style="' . implode('; ', $styles) . '"';
        }
        
        $output .= '>';
        
        // Chat header
        $output .= '<div class="aiohm-chat-header">';
        $output .= '<div class="aiohm-chat-title">' . esc_html($atts['title']) . '</div>';
        $output .= '<div class="aiohm-chat-status">';
        $output .= '<span class="aiohm-status-indicator" data-status="ready"></span>';
        $output .= '<span class="aiohm-status-text">' . __('Ready', 'aiohm-kb-assistant') . '</span>';
        $output .= '</div>';
        $output .= '</div>';
        
        // Chat messages area
        $output .= '<div class="aiohm-chat-messages" style="height: ' . esc_attr($atts['height']) . 'px; max-height: ' . esc_attr($atts['max_height']) . 'px;">';
        
        // Welcome message
        if (!empty($atts['welcome_message'])) {
            $output .= '<div class="aiohm-message aiohm-message-bot">';
            $output .= '<div class="aiohm-message-content">' . esc_html($atts['welcome_message']) . '</div>';
            $output .= '<div class="aiohm-message-time">' . current_time('H:i') . '</div>';
            $output .= '</div>';
        }
        
        $output .= '</div>';
        
        // Chat input area
        $output .= '<div class="aiohm-chat-input-container">';
        $output .= '<div class="aiohm-chat-input-wrapper">';
        $output .= '<textarea class="aiohm-chat-input" placeholder="' . esc_attr($atts['placeholder']) . '" rows="1"></textarea>';
        $output .= '<button type="button" class="aiohm-chat-send-btn" disabled>';
        $output .= '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">';
        $output .= '<line x1="22" y1="2" x2="11" y2="13"></line>';
        $output .= '<polygon points="22,2 15,22 11,13 2,9"></polygon>';
        $output .= '</svg>';
        $output .= '</button>';
        $output .= '</div>';
        $output .= '</div>';
        
        // Branding
        if ($atts['show_branding'] === 'true') {
            $output .= '<div class="aiohm-chat-branding">';
            $output .= '<span>' . __('Powered by', 'aiohm-kb-assistant') . ' <strong>AIOHM</strong></span>';
            $output .= '</div>';
        }
        
        $output .= '</div>';
        
        // Add chat configuration
        $chat_config = array(
            'chat_id' => $chat_id,
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aiohm_chat_nonce'),
            'strings' => array(
                'error' => __('Sorry, something went wrong. Please try again.', 'aiohm-kb-assistant'),
                'thinking' => __('Thinking...', 'aiohm-kb-assistant'),
                'send' => __('Send', 'aiohm-kb-assistant'),
                'ready' => __('Ready', 'aiohm-kb-assistant'),
                'typing' => __('Typing...', 'aiohm-kb-assistant'),
                'connecting' => __('Connecting...', 'aiohm-kb-assistant'),
                'you' => __('You', 'aiohm-kb-assistant'),
                'assistant' => __('Assistant', 'aiohm-kb-assistant')
            ),
            'settings' => array(
                'auto_scroll' => true,
                'show_timestamps' => true,
                'enable_sound' => false,
                'typing_indicator' => true
            )
        );
        
        $output .= '<script type="text/javascript">';
        $output .= 'if (typeof window.aiohm_chat_configs === "undefined") window.aiohm_chat_configs = {};';
        $output .= 'window.aiohm_chat_configs["' . $chat_id . '"] = ' . json_encode($chat_config) . ';';
        $output .= '</script>';
        
        return $output;
    }
    
    /**
     * Render floating chat widget
     */
    public static function render_floating_chat($atts = array()) {
        $atts = array_merge(array(
            'position' => 'bottom-right',
            'title' => __('Chat with us', 'aiohm-kb-assistant'),
            'trigger_text' => __('Need help?', 'aiohm-kb-assistant'),
            'height' => '500',
            'width' => '350'
        ), $atts);
        
        $position_class = 'aiohm-float-' . str_replace('_', '-', $atts['position']);
        
        $output = '<div class="aiohm-floating-chat ' . esc_attr($position_class) . '" id="aiohm-floating-chat">';
        
        // Chat trigger button
        $output .= '<div class="aiohm-chat-trigger" id="aiohm-chat-trigger">';
        $output .= '<div class="aiohm-trigger-content">';
        $output .= '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">';
        $output .= '<path d="m3 21 1.9-5.7a8.5 8.5 0 1 1 3.8 3.8z"></path>';
        $output .= '</svg>';
        $output .= '<span class="aiohm-trigger-text">' . esc_html($atts['trigger_text']) . '</span>';
        $output .= '</div>';
        $output .= '</div>';
        
        // Chat widget (initially hidden)
        $output .= '<div class="aiohm-chat-widget" id="aiohm-chat-widget" style="display: none;">';
        $output .= '<div class="aiohm-widget-header">';
        $output .= '<div class="aiohm-widget-title">' . esc_html($atts['title']) . '</div>';
        $output .= '<button type="button" class="aiohm-widget-close" id="aiohm-widget-close">';
        $output .= '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">';
        $output .= '<line x1="18" y1="6" x2="6" y2="18"></line>';
        $output .= '<line x1="6" y1="6" x2="18" y2="18"></line>';
        $output .= '</svg>';
        $output .= '</button>';
        $output .= '</div>';
        
        // Embed the chat shortcode
        $chat_atts = array(
            'title' => '',
            'height' => $atts['height'],
            'width' => $atts['width'],
            'theme' => 'floating',
            'show_branding' => 'false',
            'welcome_message' => __('Hello! How can I help you today?', 'aiohm-kb-assistant')
        );
        
        $output .= self::render_chat_shortcode($chat_atts);
        $output .= '</div>';
        $output .= '</div>';
        
        // Add floating chat JavaScript
        $output .= '<script type="text/javascript">';
        $output .= 'jQuery(document).ready(function($) {';
        $output .= '$("#aiohm-chat-trigger").click(function() {';
        $output .= '$("#aiohm-chat-widget").slideToggle();';
        $output .= '$(this).toggleClass("active");';
        $output .= '});';
        $output .= '$("#aiohm-widget-close").click(function() {';
        $output .= '$("#aiohm-chat-widget").slideUp();';
        $output .= '$("#aiohm-chat-trigger").removeClass("active");';
        $output .= '});';
        $output .= '});';
        $output .= '</script>';
        
        return $output;
    }
    
    /**
     * Add floating chat to footer
     */
    public static function add_floating_chat() {
        $settings = AIOHM_KB_Core_Init::get_settings();
        
        // Only show if chat is enabled and user is not in admin
        if ($settings['chat_enabled'] && !is_admin() && !wp_is_json_request()) {
            echo self::render_floating_chat();
        }
    }
}

// Add floating chat to footer if enabled in settings
$settings = AIOHM_KB_Core_Init::get_settings();
if (!empty($settings['show_floating_chat'])) {
    add_action('wp_footer', array('AIOHM_KB_Shortcode_Chat', 'add_floating_chat'));
}
