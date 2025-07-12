<?php
/**
 * Private Assistant shortcode implementation - [aiohm_private_assistant]
 * v1.1.10 - Renders a self-contained chat application with a functional fullscreen mode.
 * Sidebar is now open by default.
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIOHM_KB_Shortcode_Private_Assistant {

    public static function init() {
        add_shortcode('aiohm_private_assistant', array(__CLASS__, 'render_shortcode'));
    }

    public static function render_shortcode($atts) {
        // --- Access Control ---
        if (!is_user_logged_in() && post_password_required()) {
            return get_the_password_form();
        }
        if (!current_user_can('administrator') && !current_user_can('ohm_brand_collaborator')) {
            // A non-logged-in user with the password will pass the above check but fail this one.
            // We need to check if the post has a password and if it's been entered.
            if (get_post() && get_post()->post_password && !post_password_required()) {
                // This means the user entered the password, so we can allow them.
            } else {
                return '<div class="aiohm-chat-disabled"><p>This tool is available for authorized users only.</p></div>';
            }
        }


        // --- Settings & Asset Loading ---
        $settings = AIOHM_KB_Assistant::get_settings();
        $muse_settings = $settings['muse_mode'] ?? [];
        $mirror_settings = $settings['mirror_mode'] ?? [];
        
        $assistant_name = esc_html($muse_settings['assistant_name'] ?? 'Muse');
        
        $logo_url = !empty($mirror_settings['ai_avatar']) 
            ? esc_url($mirror_settings['ai_avatar']) 
            : esc_url(AIOHM_KB_PLUGIN_URL . 'assets/images/AIOHM-logo.png');

        wp_enqueue_style('aiohm-private-chat-style', AIOHM_KB_PLUGIN_URL . 'assets/css/aiohm-private-chat.css', [], '1.1.10');
        wp_enqueue_script('aiohm-private-chat-script', AIOHM_KB_PLUGIN_URL . 'assets/js/aiohm-private-chat.js', ['jquery'], '1.1.10', true);
        
        wp_localize_script('aiohm-private-chat-script', 'aiohm_private_chat_params', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('aiohm_private_chat_nonce'),
            'strings'  => [ 'error' => __('Sorry, something went wrong.', 'aiohm-kb-assistant') ]
        ]);

        // --- HTML Structure ---
        ob_start();
        ?>
        <div id="aiohm-app-container" class="aiohm-private-assistant-container modern sidebar-open">
            <div class="aiohm-pa-sidebar">
                <div class="aiohm-pa-sidebar-header">
                    <img src="<?php echo $logo_url; ?>" alt="Brand Logo" class="aiohm-pa-logo">
                </div>
                <div class="aiohm-pa-actions">
                    <button class="aiohm-pa-action-btn aiohm-pa-new-project-btn">
                        <span class="dashicons dashicons-plus-alt"></span> <?php esc_html_e('New Project', 'aiohm-kb-assistant'); ?>
                    </button>
                    <button id="add-to-kb-btn" class="aiohm-pa-action-btn" disabled>
                        <span class="dashicons dashicons-download"></span> <?php esc_html_e('Add Chat to KB', 'aiohm-kb-assistant'); ?>
                    </button>
                </div>
                
                <div class="aiohm-pa-menu">
                    <div class="aiohm-pa-menu-item">
                        <button class="aiohm-pa-menu-header active" data-menu="history">
                            <span class="dashicons dashicons-backup"></span> History <span class="dashicons dashicons-arrow-down-alt2"></span>
                        </button>
                        <div class="aiohm-pa-menu-content active" data-content="history">
                            <div class="aiohm-pa-conversation-list"></div>
                        </div>
                    </div>
                    <div class="aiohm-pa-menu-item">
                        <button class="aiohm-pa-menu-header" data-menu="projects">
                            <span class="dashicons dashicons-archive"></span> Projects <span class="dashicons dashicons-arrow-down-alt2"></span>
                        </button>
                        <div class="aiohm-pa-menu-content" data-content="projects">
                            <p class="aiohm-pa-coming-soon">Project folders are coming soon.</p>
                        </div>
                    </div>
                </div>

                <div class="aiohm-pa-sidebar-footer">
                    Powered by AIOHM
                </div>
            </div>

            <div class="aiohm-pa-content-wrapper">
                <div class="aiohm-pa-header">
                    <button class="aiohm-pa-header-btn aiohm-pa-sidebar-toggle" title="Toggle Sidebar"><span class="dashicons dashicons-menu-alt"></span></button>
                    <div class="aiohm-pa-header-title"><?php echo $assistant_name; ?></div>
                    <div class="aiohm-pa-window-controls">
                        <button class="aiohm-pa-header-btn aiohm-pa-fullscreen-toggle" title="Toggle Fullscreen">
                            <span class="dashicons dashicons-editor-expand"></span>
                        </button>
                    </div>
                </div>
                <div id="conversation-panel" class="conversation-panel"></div>
                <div class="aiohm-pa-input-area-wrapper">
                    <div class="aiohm-pa-input-area">
                        <textarea id="chat-input" placeholder="Start a dialogue with your Muse..." rows="1"></textarea>
                        <button id="send-btn" title="Send Message" disabled><span class="dashicons dashicons-arrow-up-alt2"></span></button>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}