<?php
/**
 * Shortcode for displaying the private assistant interface.
 * v1.2.7 - Improved UX with dynamic welcome and smarter button states.
 */
if (!defined('ABSPATH')) exit;

class AIOHM_KB_Shortcode_Private_Assistant {

    /**
     * Initializes the shortcode.
     */
    public static function init() {
        add_shortcode('aiohm_private_assistant', array(__CLASS__, 'render_private_assistant'));
    }

    /**
     * Renders the private assistant interface.
     *
     * @param array $atts Shortcode attributes.
     * @return string The HTML for the private assistant.
     */
    public static function render_private_assistant($atts = []) {
        // Ensure the user is logged in before rendering the assistant.
        if (!is_user_logged_in()) {
            return '<p class="aiohm-auth-notice">Please <a href="' . esc_url(wp_login_url(get_permalink())) . '">log in</a> to access your private assistant.</p>';
        }

        // --- Process Shortcode Attributes ---
        // This makes the welcome message customizable.
        // Example: [aiohm_private_assistant welcome_title="Hello there!" welcome_message="Let's get started."]
        $atts = shortcode_atts([
            'welcome_title' => 'Welcome! Here’s a quick guide to the buttons:',
            'welcome_message' => 'Select a project from the sidebar to begin.',
        ], $atts, 'aiohm_private_assistant');


        // --- Fetch Settings ---
        $all_settings = AIOHM_KB_Assistant::get_settings();
        $muse_settings = $all_settings['muse_mode'] ?? [];
        $assistant_name = !empty($muse_settings['assistant_name']) ? esc_html($muse_settings['assistant_name']) : 'Muse';
        $settings_page_url = admin_url('admin.php?page=aiohm-muse-mode');

        // --- Enqueue Necessary Scripts and Styles ---
        wp_enqueue_style('aiohm-private-chat-style', AIOHM_KB_PLUGIN_URL . 'assets/css/aiohm-private-chat.css', [], AIOHM_KB_VERSION);
        wp_enqueue_script('aiohm-private-chat-js', AIOHM_KB_PLUGIN_URL . 'assets/js/aiohm-private-chat.js', ['jquery'], AIOHM_KB_VERSION, true);
        
        // Pass data from PHP to our JavaScript file.
        wp_localize_script('aiohm-private-chat-js', 'aiohm_private_chat_params', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aiohm_private_chat_nonce'),
            'user_name' => wp_get_current_user()->display_name,
        ]);

        // Start output buffering to capture the HTML.
        ob_start();
        ?>
        <div id="aiohm-app-container" class="aiohm-private-assistant-container modern sidebar-open">
            
            <aside class="aiohm-pa-sidebar">
                <div class="aiohm-pa-sidebar-header">
                    <h3 style="color: white; margin-top: 10px;"><?php echo $assistant_name; ?></h3>
                </div>

                <div class="aiohm-pa-actions">
                    <button class="aiohm-pa-action-btn" id="new-project-btn" title="Start a new project folder">
                        <span class="dashicons dashicons-plus"></span>
                        New Project
                    </button>
                    <button class="aiohm-pa-action-btn" id="new-chat-btn" title="Start a fresh conversation">
                        <span class="dashicons dashicons-format-chat"></span>
                        New Chat
                    </button>
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
                    <a href="<?php echo esc_url($settings_page_url); ?>" class="aiohm-footer-settings-link" title="Muse Mode Settings">Settings</a>
                    <span class="aiohm-footer-version">AIOHM KB v<?php echo AIOHM_KB_VERSION; ?></span>
                </div>
            </aside>

            <main class="aiohm-pa-content-wrapper">
                <header class="aiohm-pa-header">
                    <button class="aiohm-pa-header-btn" id="sidebar-toggle" title="Toggle Sidebar">
                        <span class="dashicons dashicons-menu-alt"></span>
                    </button>
                    <h2 class="aiohm-pa-header-title" id="project-title">Select a Project</h2>
                    
                    <div class="aiohm-pa-window-controls">
                        <button class="aiohm-pa-header-btn" id="research-online-prompt-btn" title="Research a live website">
                            <span class="dashicons dashicons-search"></span>
                        </button>
                        <button class="aiohm-pa-header-btn" id="download-pdf-btn" title="Download chat as PDF">
                            <span class="dashicons dashicons-download"></span>
                        </button>
                        <button class="aiohm-pa-header-btn" id="add-to-kb-btn" title="Add Chat to Knowledge Base" disabled>
                            <span class="dashicons dashicons-database-add"></span>
                        </button>
                        <button class="aiohm-pa-header-btn" id="toggle-notes-btn" title="Open Notes">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                        <button class="aiohm-pa-header-btn" id="fullscreen-toggle-btn" title="Toggle Fullscreen">
                            <span class="dashicons dashicons-fullscreen-alt"></span>
                        </button>
                    </div>
                </header>
                
                <div id="aiohm-pa-notification" class="aiohm-pa-notification-bar" style="display: none;">
                    <p></p>
                    <span class="close-btn dashicons dashicons-no-alt"></span>
                </div>

                <div class="conversation-panel" id="conversation-panel">
                    <div class="message system" id="welcome-instructions">
                        <h4><?php echo esc_html($atts['welcome_title']); ?></h4>
                        <ul class="aiohm-instructions-list">
                            <li><span class="dashicons dashicons-search"></span> <div><strong>Research Online</strong><p>Fetch real-time information from a website.</p></div></li>
                            <li><span class="dashicons dashicons-download"></span> <div><strong>Download Chat</strong><p>Save your current conversation as a PDF.</p></div></li>
                            <li><span class="dashicons dashicons-edit"></span> <div><strong>Toggle Notes</strong><p>Open a sidebar to jot down ideas.</p></div></li>
                            <li><span class="dashicons dashicons-fullscreen-alt"></span> <div><strong>Go Fullscreen</strong><p>Expand the interface to fill the screen.</p></div></li>
                        </ul>
                        <p><?php echo esc_html($atts['welcome_message']); ?></p>
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

            <aside class="aiohm-pa-notes-sidebar">
                <div class="aiohm-pa-notes-header">
                    <h3>Notes</h3>
                    <button class="aiohm-pa-header-btn" id="close-notes-btn" title="Close Notes">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
                <div class="aiohm-pa-notes-content">
                    <p class="description">Jot down ideas or important points here. Add them to your Knowledge Base for future reference.</p>
                    <textarea id="muse-notes-input" placeholder="Write your notes here..." rows="10"></textarea>
                    <button type="button" id="add-note-to-kb-btn" class="button button-primary">Add Note to KB</button>
                </div>
            </aside>
        </div>
        <?php
        // Return the captured HTML.
        return ob_get_clean();
    }
}