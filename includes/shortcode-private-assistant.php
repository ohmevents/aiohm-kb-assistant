<?php
/**
 * Shortcode for displaying the private assistant interface.
 * v1.2.9 - Moved action buttons to header and clarified instructions with a table.
 */
if (!defined('ABSPATH')) exit;

class AIOHM_KB_Shortcode_Private_Assistant {

    public static function init() {
        add_shortcode('aiohm_private_assistant', array(__CLASS__, 'render_private_assistant'));
    }

    public static function render_private_assistant() {
        if (!is_user_logged_in()) {
            return '<p class="aiohm-auth-notice">Please <a href="' . esc_url(wp_login_url(get_permalink())) . '">log in</a> to access your private assistant.</p>';
        }

        // --- Fetch settings ---
        $all_settings = AIOHM_KB_Assistant::get_settings();
        $muse_settings = $all_settings['muse_mode'] ?? [];
        $assistant_name = !empty($muse_settings['assistant_name']) ? $muse_settings['assistant_name'] : 'Muse';
        $settings_page_url = admin_url('admin.php?page=aiohm-muse-mode');

        // Enqueue scripts and styles
        wp_enqueue_style('aiohm-private-chat-style', AIOHM_KB_PLUGIN_URL . 'assets/css/aiohm-private-chat.css', [], AIOHM_KB_VERSION);
        wp_enqueue_script('aiohm-private-chat-js', AIOHM_KB_PLUGIN_URL . 'assets/js/aiohm-private-chat.js', ['jquery'], AIOHM_KB_VERSION, true);
        
        // Localize script parameters
        wp_localize_script('aiohm-private-chat-js', 'aiohm_private_chat_params', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aiohm_private_chat_nonce'),
            'user_name' => wp_get_current_user()->display_name,
        ]);

        ob_start();
        ?>
        <div id="aiohm-app-container" class="aiohm-private-assistant-container modern sidebar-open">
            <aside class="aiohm-pa-sidebar">
                <div class="aiohm-pa-sidebar-header">
                    <h3 style="color: white; margin-top: 10px;"><?php echo esc_html($assistant_name); ?></h3>
                </div>

                <nav class="aiohm-pa-menu">
                    <div class="aiohm-pa-menu-item">
                        <button class="aiohm-pa-menu-header active" data-target="projects-content">
                            <span>Projects</span>
                            <span class="dashicons dashicons-arrow-down-alt2"></span>
                        </button>
                        <div class="aiohm-pa-menu-content" id="projects-content" style="display: block;">
                            <div class="aiohm-pa-project-list"></div>
                        </div>
                    </div>
                    <div class="aiohm-pa-menu-item">
                        <button class="aiohm-pa-menu-header" data-target="conversations-content">
                            <span>Conversations</span>
                            <span class="dashicons dashicons-arrow-down-alt2"></span>
                        </button>
                        <div class="aiohm-pa-menu-content" id="conversations-content">
                            <div class="aiohm-pa-conversation-list"></div>
                        </div>
                    </div>
                </nav>

                <div class="aiohm-pa-sidebar-footer">
                    <div>
                        <a href="<?php echo esc_url($settings_page_url); ?>" class="aiohm-footer-settings-link" title="Muse Mode Settings">
                             Settings
                        </a>
                    </div>
                    <div>
                        <span class="aiohm-footer-version">AIOHM KB Assistant v<?php echo AIOHM_KB_VERSION; ?></span>
                    </div>
                </div>

            </aside>

            <main class="aiohm-pa-content-wrapper">
                <header class="aiohm-pa-header">
                    <button class="aiohm-pa-header-btn" id="sidebar-toggle">
                        <span class="dashicons dashicons-menu-alt"></span>
                    </button>
                    
                    <div class="aiohm-pa-actions">
                        <button class="aiohm-pa-action-btn" id="new-project-btn">
                            <span class="dashicons dashicons-plus"></span>
                            New Project
                        </button>
                        <button class="aiohm-pa-action-btn" id="new-chat-btn">
                            <span class="dashicons dashicons-format-chat"></span>
                            New Chat
                        </button>
                    </div>
                    <h2 class="aiohm-pa-header-title" id="project-title">Select a Project</h2>
                    
                    <div class="aiohm-pa-window-controls">
                        <button class="aiohm-pa-header-btn" id="research-online-prompt-btn" title="Research Online">
                            <span class="dashicons dashicons-admin-globe"></span>
                        </button>
                        <button class="aiohm-pa-header-btn" id="download-pdf-btn" title="Download chat as PDF">
                            <span class="dashicons dashicons-download"></span>
                        </button>
                        <button class="aiohm-pa-header-btn" id="add-to-kb-btn" title="Add Chat to Knowledge Base" disabled>
                            <span class="dashicons dashicons-database-add"></span>
                        </button>
                    </div>

                </header>

                <div class="conversation-panel" id="conversation-panel">
                    <div class="message system" id="welcome-instructions">
                        <h4>Welcome to Muse!</h4>
                        <p>Here’s a quick guide to get started:</p>
                        <table class="aiohm-instructions-table">
                            <tbody>
                                <tr>
                                    <td class="icon-cell"><span class="dashicons dashicons-plus"></span></td>
                                    <td>
                                        <strong>New Project</strong>
                                        <p>Start here to create a new project and keep your chats organized.</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="icon-cell"><span class="dashicons dashicons-admin-globe"></span></td>
                                    <td>
                                        <strong>Research Online</strong>
                                        <p>Use this to get real-time information from any website for your project.</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="icon-cell"><span class="dashicons dashicons-microphone"></span></td>
                                    <td>
                                        <strong>Voice-to-Text</strong>
                                        <p>Click the microphone in the input bar to dictate your messages.</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <p style="margin-top: 20px; font-weight: bold;">Select a project or create a new one to begin!</p>
                    </div>
                    </div>
                
                <div id="aiohm-chat-loading" style="display: none; text-align: center; padding: 10px;">
                    Thinking...
                </div>

                <div class="aiohm-pa-input-area-wrapper">
                    <form id="private-chat-form">
                        <div class="aiohm-pa-input-area">
                            <textarea id="chat-input" placeholder="Type your message..." rows="1" disabled></textarea>
                            <button id="send-btn" type="submit" disabled>
                                <span class="dashicons dashicons-arrow-right-alt2"></span>
                            </button>
                            <button class="aiohm-pa-header-btn" id="activate-audio-btn" type="button" title="Activate voice-to-text">
                                <span class="dashicons dashicons-microphone"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </main>

            <div id="research-prompt-modal" class="aiohm-modal" style="display:none;">
                <div class="aiohm-modal-content">
                    <span class="aiohm-modal-close">&times;</span>
                    <h3>Research Online</h3>
                    <p>Select a prompt to start your research. Replace the text in [brackets] with your topic.</p>
                    <ul id="research-prompt-list">
                        <li data-prompt="Go online and give me a summary of the latest news about [topic].">Summary of latest news about [topic].</li>
                        <li data-prompt="Research the best practices for [topic] in 2025.">Best practices for [topic].</li>
                        <li data-prompt="Find tutorials or guides on how to [do something].">Find tutorials on how to [do something].</li>
                        <li data-prompt="Extract key statistics and data about [industry or subject].">Extract key statistics about [industry].</li>
                    </ul>
                    <textarea id="custom-research-prompt" placeholder="Or type your own research prompt here..."></textarea>
                    <button id="start-research-btn" class="aiohm-pa-action-btn">Start Research</button>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}