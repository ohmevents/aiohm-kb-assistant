<?php
/**
 * Private Assistant shortcode implementation - [aiohm_private_assistant]
 * Enhanced with a premium UI, conversation management, and a welcome screen.
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

        // Enqueue assets
        wp_enqueue_style('aiohm-private-chat-style', AIOHM_KB_PLUGIN_URL . 'assets/css/aiohm-private-chat.css', [], AIOHM_KB_VERSION);
        wp_enqueue_script('aiohm-private-chat-script', AIOHM_KB_PLUGIN_URL . 'assets/js/aiohm-private-chat.js', ['jquery'], AIOHM_KB_VERSION, true);
        
        // Localize data for JavaScript
        wp_localize_script('aiohm-private-chat-script', 'aiohm_private_chat_params', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('aiohm_private_chat_nonce'),
            'strings'  => [
                'new_chat' => __('New Chat', 'aiohm-kb-assistant'),
                'error' => __('Sorry, something went wrong.', 'aiohm-kb-assistant'),
            ]
        ]);

        ob_start();
        ?>
        <div class="aiohm-private-assistant-container">
            <div class="aiohm-pa-sidebar">
                <div class="aiohm-pa-sidebar-header">
                    <img src="<?php echo esc_url(AIOHM_KB_PLUGIN_URL . 'assets/images/AIOHM-logo.png'); ?>" alt="AIOHM Logo" class="aiohm-pa-logo">
                    <h3><?php esc_html_e('My Dialogues', 'aiohm-kb-assistant'); ?></h3>
                </div>
                <div class="aiohm-pa-actions">
                    <button class="aiohm-pa-new-chat-btn">
                        <span class="dashicons dashicons-plus"></span>
                        <?php esc_html_e('New Dialogue', 'aiohm-kb-assistant'); ?>
                    </button>
                </div>
                <div class="aiohm-pa-conversation-list">
                    <div class="aiohm-pa-loader"></div>
                </div>
                <div class="aiohm-pa-sidebar-footer">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=aiohm-muse-mode')); ?>" target="_blank">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <?php esc_html_e('Customize Muse', 'aiohm-kb-assistant'); ?>
                    </a>
                </div>
            </div>

            <div class="aiohm-pa-main">
                <div class="aiohm-pa-chat-area">
                    <div class="aiohm-pa-welcome-screen">
                        <h2><?php echo esc_html($muse_settings['assistant_name'] ?? 'Muse'); ?>: <?php esc_html_e('Your Private Brand Assistant', 'aiohm-kb-assistant'); ?></h2>
                        <p><?php esc_html_e("I'm here to help you brainstorm, draft content, and think through ideas in your own brand voice.", 'aiohm-kb-assistant'); ?></p>
                        <div class="aiohm-pa-prompt-suggestions">
                            <div class="suggestion-card" data-prompt="Draft three social media posts about my main offer.">
                                <strong><?php esc_html_e('Draft Social Posts', 'aiohm-kb-assistant'); ?></strong>
                                <small><?php esc_html_e('Based on your brand direction.', 'aiohm-kb-assistant'); ?></small>
                            </div>
                            <div class="suggestion-card" data-prompt="Write a short email to my audience about my deeper purpose.">
                                <strong><?php esc_html_e('Write an Email', 'aiohm-kb-assistant'); ?></strong>
                                <small><?php esc_html_e('Using your Brand Soul answers.', 'aiohm-kb-assistant'); ?></small>
                            </div>
                            <div class="suggestion-card" data-prompt="Help me brainstorm blog post titles based on my brand's energy.">
                                <strong><?php esc_html_e('Brainstorm Ideas', 'aiohm-kb-assistant'); ?></strong>
                                <small><?php esc_html_e('Connecting to your brand energy.', 'aiohm-kb-assistant'); ?></small>
                            </div>
                            <div class="suggestion-card" data-prompt="Summarize my brand expression in one paragraph.">
                                <strong><?php esc_html_e('Summarize My Brand', 'aiohm-kb-assistant'); ?></strong>
                                <small><?php esc_html_e('Using your brand expression answers.', 'aiohm-kb-assistant'); ?></small>
                            </div>
                        </div>
                    </div>
                    <div id="conversation-panel" class="conversation-panel" style="display:none;">
                        </div>
                </div>
                <div class="aiohm-pa-input-area-wrapper">
                    <div id="context-display" class="context-display" style="display:none;"></div>
                    <div class="input-area">
                        <textarea id="chat-input" placeholder="<?php esc_attr_e('Start a dialogue with your Muse...', 'aiohm-kb-assistant'); ?>" rows="1"></textarea>
                        <button id="send-btn" disabled>
                            <svg width="20" height="20" viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z" fill="currentColor"></path></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}