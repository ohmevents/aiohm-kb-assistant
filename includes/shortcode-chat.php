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
        $settings = AIOHM_KB_Assistant::get_settings();
        $has_club_access = class_exists('AIOHM_KB_PMP_Integration') && AIOHM_KB_PMP_Integration::aiohm_user_has_club_access();
        
        // Check if chat is enabled from settings OR if user has Club access
        if (!($settings['chat_enabled'] ?? false) || !$has_club_access) {
            // Display message indicating why chat is disabled
            $message = __('Chat is currently disabled.', 'aiohm-kb-assistant');
            if (!$has_club_access) {
                $message = __('This chat feature requires an AIOHM Club membership.', 'aiohm-kb-assistant');
                $message .= ' <a href="' . esc_url(admin_url('admin.php?page=aiohm-license&tab=club')) . '" target="_blank">' . __('Join Club', 'aiohm-kb-assistant') . '</a>';
            }
            return '<div class="aiohm-chat-disabled"><p>' . $message . '</p></div>';
        }
        
        // Parse shortcode attributes
        $atts = shortcode_atts(array(
            'title' => $settings['business_name'] ?? __('Ask me anything', 'aiohm-kb-assistant'),
            'placeholder' => __('Type your question here...', 'aiohm-kb-assistant'),
            'height' => '400',
            'width' => '100%',
            'theme' => 'ohm-green', // Set the new theme as default
            'position' => 'inline',
            'show_branding' => 'true',
            'welcome_message' => $settings['welcome_message'] ?? 'Hey there, beautiful soul - welcome to OHM Events Agency! I’m your AI Assistant, here to help you grow your event, boost your visibility, and bring your vision to life - with strategy, clarity, and heart. Ask me anything!',
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
        
        // Add inline styles for customization
        $styles = array();
        if ($atts['width'] !== '100%') {
            $styles[] = 'width: ' . esc_attr($atts['width']);
        }
        if (!empty($settings['background_color'])) {
            $styles[] = '--aiohm-secondary-color: ' . esc_attr($settings['background_color']);
        }
        if (!empty($settings['primary_color'])) {
            $styles[] = '--aiohm-primary-color: ' . esc_attr($settings['primary_color']);
        }
        if (!empty($settings['text_color'])) {
            $styles[] = '--aiohm-text-color: ' . esc_attr($settings['text_color']);
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
            if (!empty($settings['ai_avatar'])) {
                $output .= '<div class="aiohm-message-avatar"><img src="' . esc_url($settings['ai_avatar']) . '" alt="AI Avatar" style="width:100%; height:100%; border-radius:50%; object-fit: cover;"></div>';
            }
            $output .= '<div class="aiohm-message-bubble"><div class="aiohm-message-content">' . esc_html($atts['welcome_message']) . '</div></div>';
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
        
        // Footer: Conditional "Book a Meeting" button or Branding
        if (!empty($settings['meeting_button_url'])) {
            $output .= '<a href="' . esc_url($settings['meeting_button_url']) . '" class="aiohm-chat-footer-button" target="_blank">' . __('Book a Meeting', 'aiohm-kb-assistant') . '</a>';
        } elseif ($atts['show_branding'] === 'true') {
            $output .= '<div class="aiohm-chat-branding">';
            $output .= '<span>' . __('Powered by', 'aiohm-kb-assistant') . ' <strong>AIOHM</strong></span>';
            $output .= '</div>';
        }
        
        $output .= '</div>';
        
// inside render_chat_shortcode() in includes/shortcode-chat.php
$chat_config = array(
    'chat_id' => $chat_id,
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('aiohm_chat_nonce'),
    'chat_action' => 'aiohm_frontend_chat', // Add this line
    'strings' => array(
        'error' => __('Sorry, something went wrong. Please try again.', 'aiohm-kb-assistant'),
    ),
    'settings' => $settings // Pass all settings to JS
);
        
        $output .= '<script type="text/javascript">';
        $output .= 'if (typeof window.aiohm_chat_configs === "undefined") window.aiohm_chat_configs = {};';
        $output .= 'window.aiohm_chat_configs["' . $chat_id . '"] = ' . json_encode($chat_config) . ';';
        $output .= '</script>';
        
        return $output;
    }
}