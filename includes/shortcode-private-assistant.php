<?php
/**
 * Private Assistant shortcode implementation - [aiohm_private_assistant]
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIOHM_KB_Shortcode_Private_Assistant {

    public static function init() {
        add_shortcode('aiohm_private_assistant', array(__CLASS__, 'render_shortcode'));
    }

    public static function render_shortcode($atts) {
        // Access Control
        if (!current_user_can('administrator') && !current_user_can('ohm_brand_collaborator')) {
            return '<div class="aiohm-chat-disabled"><p>This space is reserved for private brand dialogue. Please log in with appropriate permissions.</p></div>';
        }

        $settings = AIOHM_KB_Assistant::get_settings();
        $muse_settings = $settings['muse_mode'] ?? [];

        $chat_atts = shortcode_atts(array(
            'title' => $muse_settings['assistant_name'] ?? 'Muse: Private Assistant',
            'placeholder' => 'Ask Muse anything...',
            'height' => '500',
            'width' => '100%',
            'welcome_message' => 'Hello! I am your private brand assistant. How can I help you create today?'
        ), $atts, 'aiohm_private_assistant');
        
        static $chat_counter = 0;
        $chat_counter++;
        $chat_id = 'aiohm-muse-chat-' . $chat_counter;
        
        wp_enqueue_script('aiohm-chat');
        wp_enqueue_style('aiohm-chat');
        
        $output = '<div class="aiohm-chat-wrapper">';
        $output .= '<div class="aiohm-chat-container" id="' . esc_attr($chat_id) . '">';
        
        $output .= '<div class="aiohm-chat-header">';
        $output .= '<div class="aiohm-chat-title">' . esc_html($chat_atts['title']) . '</div>';
        $output .= '</div>';
        
        $output .= '<div class="aiohm-chat-messages" style="height: ' . esc_attr($chat_atts['height']) . 'px;">';
        $output .= '<div class="aiohm-message aiohm-message-bot"><div class="aiohm-message-bubble"><div class="aiohm-message-content">' . esc_html($chat_atts['welcome_message']) . '</div></div></div>';
        $output .= '</div>';
        
        $output .= '<div class="aiohm-chat-input-container">';
        $output .= '<div class="aiohm-chat-input-wrapper">';
        $output .= '<textarea class="aiohm-chat-input" placeholder="' . esc_attr($chat_atts['placeholder']) . '" rows="1"></textarea>';
        $output .= '<button type="button" class="aiohm-chat-send-btn" disabled><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg></button>';
        $output .= '</div>';
        $output .= '</div>';
        
        $output .= '</div>';
        $output .= '</div>';

        $chat_config = array(
            'chat_id' => $chat_id,
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aiohm_private_chat_nonce'),
            'chat_action' => 'aiohm_private_assistant_chat', // Use a new action
            'strings' => array(
                'error' => 'Sorry, something went wrong.',
            )
        );
        
        wp_add_inline_script('aiohm-chat', 'if (typeof window.aiohm_chat_configs === "undefined") window.aiohm_chat_configs = {}; window.aiohm_chat_configs["' . $chat_id . '"] = ' . json_encode($chat_config) . ';', 'before');

        return $output;
    }
}