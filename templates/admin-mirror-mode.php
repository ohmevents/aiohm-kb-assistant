<?php
/**
 * Admin Mirror Mode Settings page template for Club members.
 * Final, complete, and stable version with all features, styles, and full scripts.
 */

if (!defined('ABSPATH')) exit;

// Fetch all settings and then get the specific part for Mirror Mode
$all_settings = AIOHM_KB_Assistant::get_settings();
$settings = $all_settings['mirror_mode'] ?? [];
$global_settings = $all_settings; // for API keys

// Helper function for color contrast
function aiohm_is_color_dark($hex) {
    if (empty($hex)) return false;
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) == 3) {
        $hex = str_repeat(substr($hex,0,1), 2).str_repeat(substr($hex,1,1), 2).str_repeat(substr($hex,2,1), 2);
    }
    if (strlen($hex) != 6) return false;
    $r = hexdec(substr($hex,0,2));
    $g = hexdec(substr($hex,2,2));
    $b = hexdec(substr($hex,4,2));
    $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
    return $luminance < 0.5;
}

$default_prompt = "You are the official AI Knowledge Assistant for \"%site_name%\".\n\nYour core mission is to embody our brand's tagline: \"%site_tagline%\".\n\nYou are to act as a thoughtful and emotionally intelligent guide for all website visitors, reflecting the unique voice of the brand. You should be aware that today is %day_of_week%, %current_date%.\n\n---\n\n**Core Instructions:**\n\n1.  **Primary Directive:** Your primary goal is to answer the user's question by grounding your response in the **context provided below**. This context is your main source of truth.\n\n2.  **Tone & Personality:**\n    * Speak with emotional clarity, not robotic formality.\n    * Sound like a thoughtful assistant, not a sales rep.\n    * Be concise, but not curt — useful, but never cold.\n    * Your purpose is to express with presence, not persuasion.\n\n3.  **Formatting Rules:**\n    * Use only basic HTML tags for clarity (like <strong> or <em> if needed). Do not use Markdown.\n    * Never end your response with a question like “Do you need help with anything else?”\n\n4.  **Fallback Response (Crucial):**\n    * If the provided context does not contain enough information to answer the user's question, you MUST respond with this exact phrase: \"Hmm… I don’t want to guess here. This might need a human’s wisdom. You can connect with the person behind this site on the contact page. They’ll know exactly how to help.\"\n\n---\n\n**Primary Context for Answering the User's Question:**\n{context}";
$qa_system_message = !empty($settings['qa_system_message']) ? $settings['qa_system_message'] : $default_prompt;

?>

<div class="wrap aiohm-settings-page aiohm-mirror-mode-page">
    <h1><?php _e('Mirror Mode Customization', 'aiohm-kb-assistant'); ?></h1>
    <p class="page-description"><?php _e('Fine-tune your AI\'s personality and appearance on the left, and test your changes in real-time on the right.', 'aiohm-kb-assistant'); ?></p>

    <div id="aiohm-admin-notice" class="notice" style="display:none; margin-top: 10px;"><p></p></div>

    <div class="aiohm-mirror-mode-layout">
        
        <div class="aiohm-settings-form-wrapper">
            <form id="mirror-mode-settings-form">
                <?php wp_nonce_field('aiohm_mirror_mode_nonce', 'aiohm_mirror_mode_nonce_field'); ?>
                
                <div class="aiohm-setting-block">
                    <label for="business_name">Business Name</label>
                    <input type="text" id="business_name" name="aiohm_kb_settings[mirror_mode][business_name]" value="<?php echo esc_attr($settings['business_name'] ?? get_bloginfo('name')); ?>">
                    <p class="description">This name will appear in the chat header.</p>
                </div>

                <div class="aiohm-setting-block">
                    <div class="aiohm-setting-header">
                        <label for="qa_system_message"><?php _e('Soul Signature for Q&A Assistant', 'aiohm-kb-assistant'); ?></label>
                        <button type="button" id="reset-prompt-btn" class="button-link"><?php _e('Reset to Default', 'aiohm-kb-assistant'); ?></button>
                    </div>
                    <textarea id="qa_system_message" name="aiohm_kb_settings[mirror_mode][qa_system_message]" rows="15"><?php echo esc_textarea($qa_system_message); ?></textarea>
                    <p class="description">This is the core instruction set for your AI.</p>
                </div>

                <div class="aiohm-setting-block">
                    <label for="ai_model_selector">AI Model</label>
                    <select id="ai_model_selector" name="aiohm_kb_settings[mirror_mode][ai_model]">
                        <?php if (!empty($global_settings['openai_api_key'])): ?>
                            <option value="gpt-3.5-turbo" <?php selected($settings['ai_model'] ?? 'gpt-3.5-turbo', 'gpt-3.5-turbo'); ?>>OpenAI: GPT-3.5 Turbo</option>
                            <option value="gpt-4" <?php selected($settings['ai_model'] ?? '', 'gpt-4'); ?>>OpenAI: GPT-4</option>
                        <?php endif; ?>
                        <?php if (!empty($global_settings['gemini_api_key'])): ?>
                            <option value="gemini-pro" <?php selected($settings['ai_model'] ?? '', 'gemini-pro'); ?>>Google: Gemini Pro</option>
                        <?php endif; ?>
                        <?php if (!empty($global_settings['claude_api_key'])): ?>
                            <option value="claude-3-sonnet" <?php selected($settings['ai_model'] ?? '', 'claude-3-sonnet'); ?>>Anthropic: Claude 3 Sonnet</option>
                        <?php endif; ?>
                    </select>
                     <p class="description">Select the model to power the chat. Models are available based on the API keys you've provided in the main settings.</p>
                </div>

                <div class="aiohm-setting-block">
                    <label for="qa_temperature">Temperature: <span class="temp-value"><?php echo esc_attr($settings['qa_temperature'] ?? '0.8'); ?></span></label>
                    <input type="range" id="qa_temperature" name="aiohm_kb_settings[mirror_mode][qa_temperature]" value="<?php echo esc_attr($settings['qa_temperature'] ?? '0.8'); ?>" min="0" max="1" step="0.1">
                    <p class="description">Lower is more predictable; higher is more creative.</p>
                </div>
                
                <div class="aiohm-color-grid">
                    <div class="aiohm-setting-block">
                        <label for="primary_color">Primary</label>
                        <input type="color" id="primary_color" name="aiohm_kb_settings[mirror_mode][primary_color]" value="<?php echo esc_attr($settings['primary_color'] ?? '#1f5014'); ?>">
                    </div>
                    <div class="aiohm-setting-block">
                        <label for="background_color">Background</label>
                        <input type="color" id="background_color" name="aiohm_kb_settings[mirror_mode][background_color]" value="<?php echo esc_attr($settings['background_color'] ?? '#f0f4f8'); ?>">
                    </div>
                    <div class="aiohm-setting-block">
                        <label for="text_color">Header Text</label>
                        <input type="color" id="text_color" name="aiohm_kb_settings[mirror_mode][text_color]" value="<?php echo esc_attr($settings['text_color'] ?? '#ffffff'); ?>">
                    </div>
                </div>

                <div class="aiohm-setting-block">
                    <label for="ai_avatar">AI Avatar</label>
                    <div class="aiohm-avatar-uploader">
                        <input type="text" id="ai_avatar" name="aiohm_kb_settings[mirror_mode][ai_avatar]" value="<?php echo esc_attr($settings['ai_avatar'] ?? ''); ?>" placeholder="Enter image URL">
                        <button type="button" class="button button-secondary" id="upload_ai_avatar_button">Upload</button>
                    </div>
                     <p class="description">Upload or enter the URL for the AI's avatar.</p>
                </div>

                <div class="aiohm-setting-block">
                    <label for="meeting_button_url">"Book a Meeting" URL</label>
                    <input type="url" id="meeting_button_url" name="aiohm_kb_settings[mirror_mode][meeting_button_url]" value="<?php echo esc_attr($settings['meeting_button_url'] ?? ''); ?>" placeholder="https://your-booking-link.com">
                     <p class="description">Replaces the "Powered by" text with a booking button.</p>
                </div>
                
                <div class="form-actions">
                    <button type="button" id="save-mirror-mode-settings" class="button button-primary"><?php _e('Save Mirror Mode Settings', 'aiohm-kb-assistant'); ?></button>
                </div>
            </form>
        </div>
        
        <div class="aiohm-test-column">
            <div id="aiohm-test-chat" class="aiohm-chat-container">
                <div class="aiohm-chat-header">
                    <div class="aiohm-chat-title-preview"><?php echo esc_html($settings['business_name'] ?? 'Live Preview'); ?></div>
                    <div class="aiohm-chat-status">
                        <span class="aiohm-status-indicator" data-status="ready"></span>
                        <span class="aiohm-status-text">Ready</span>
                    </div>
                </div>
                <div class="aiohm-chat-messages">
                    <div class="aiohm-message aiohm-message-bot">
                        <div class="aiohm-message-avatar">
                            <img src="<?php echo esc_url($settings['ai_avatar'] ?? ''); ?>" alt="AI Avatar" class="aiohm-avatar-preview">
                        </div>
                        <div class="aiohm-message-bubble"><div class="aiohm-message-content">Ask a question to test the settings from the left. Your changes are applied instantly here without saving.</div></div>
                    </div>
                </div>
                <div class="aiohm-chat-input-container">
                    <div class="aiohm-chat-input-wrapper">
                        <textarea class="aiohm-chat-input" placeholder="Ask your question here..." rows="1"></textarea>
                        <button type="button" class="aiohm-chat-send-btn" disabled><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg></button>
                    </div>
                </div>
                <div class="aiohm-chat-footer-preview">
                </div>
            </div>

            <div class="aiohm-search-container-wrapper">
                <div class="aiohm-search-controls">
                    <div class="aiohm-search-form">
                        <div class="aiohm-search-input-wrapper">
                            <input type="text" class="aiohm-search-input" placeholder="Search knowledge base...">
                            <button type="button" class="aiohm-search-btn">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.35-4.35"></path></svg>
                            </button>
                        </div>
                    </div>
                    <div class="aiohm-search-filters">
                         <select id="aiohm-test-search-filter" name="content_type">
                            <option value="">All Types</option>
                            <option value="post">Posts</option>
                            <option value="page">Pages</option>
                            <option value="application/pdf">PDF</option>
                            <option value="text/plain">TXT</option>
                        </select>
                    </div>
                </div>
                <div class="aiohm-search-results"></div>
            </div>

            <div class="q-and-a-generator">
                <h3><?php _e('Generate Sample Q&A', 'aiohm-kb-assistant'); ?></h3>
                <p class="description">Generate a random question and answer from your knowledge base to test the AI's understanding.</p>
                <button type="button" id="generate-q-and-a" class="button button-secondary"><?php _e('Generate Sample Q&A', 'aiohm-kb-assistant'); ?></button>
                <div id="q-and-a-results" class="q-and-a-container"></div>
            </div>

        </div>
    </div>
</div>