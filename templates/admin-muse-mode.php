<?php
/**
 * Admin Muse Mode Settings page template for Club members.
 * Final, complete, and stable version with all features, styles, and full scripts.
 */

if (!defined('ABSPATH')) exit;

// Fetch all settings and then get the specific part for Muse Mode
$all_settings = AIOHM_KB_Assistant::get_settings();
$settings = $all_settings['muse_mode'] ?? [];
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
    <h1><?php _e('Muse Mode Customization', 'aiohm-kb-assistant'); ?></h1>
    <p class="page-description"><?php _e('Fine-tune your AI\'s personality and appearance on the left, and test your changes in real-time on the right.', 'aiohm-kb-assistant'); ?></p>

    <div id="aiohm-admin-notice" class="notice" style="display:none; margin-top: 10px;"><p></p></div>

    <div class="aiohm-muse-mode-layout">
        
        <div class="aiohm-settings-form-wrapper">
            <form id="mirror-mode-settings-form">
                <?php wp_nonce_field('aiohm_mirror_mode_nonce', 'aiohm_mirror_mode_nonce_field'); ?>
                
                <div class="aiohm-setting-block">
                    <label for="business_name">Assistant name</label>
                    <input type="text" id="business_name" name="aiohm_kb_settings[mirror_mode][business_name]" value="<?php echo esc_attr($settings['business_name'] ?? get_bloginfo('name')); ?>">
                    <p class="description">This name will appear in the chat header.</p>
                </div>

                <div class="aiohm-setting-block">
                    <div class="aiohm-setting-header">
                        <label for="qa_system_message"><?php _e('Soul Signature for Muse Assistant', 'aiohm-kb-assistant'); ?></label>
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

<style>
    :root {
        --ohm-primary: #457d58;
        --ohm-dark: #272727;
        --ohm-light-accent: #cbddd1;
        --ohm-light-bg: #EBEBEB;
        --ohm-dark-accent: #1f5014;
        --ohm-font-primary: 'Montserrat', sans-serif;
        --ohm-font-secondary: 'PT Sans', sans-serif;
    }
    .aiohm-settings-page h1, .aiohm-settings-page h2, .aiohm-settings-page h3 { font-family: var(--ohm-font-primary); color: var(--ohm-dark-accent); }
    .aiohm-settings-page .page-description { font-size: 1.1em; padding-bottom: 1em; border-bottom: 1px solid var(--ohm-light-bg); }
    .aiohm-mirror-mode-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 20px; }
    .aiohm-settings-form-wrapper, .aiohm-test-column { background: #fff; padding: 20px 30px; border: 1px solid var(--ohm-light-bg); border-radius: 8px; }
    .aiohm-setting-block { margin-bottom: 20px; }
    .aiohm-setting-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
    .aiohm-setting-header label { margin-bottom: 0; }
    .aiohm-setting-block label { display: block; font-family: var(--ohm-font-primary); font-weight: bold; font-size: 1.1em; color: var(--ohm-dark-accent); margin-bottom: 8px; }
    .aiohm-setting-block input[type="text"], .aiohm-setting-block input[type="url"], .aiohm-setting-block textarea, .aiohm-setting-block select { width: 100%; padding: 10px; font-family: var(--ohm-font-secondary); font-size: 1em; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
    .aiohm-setting-block p.description { font-family: var(--ohm-font-secondary); color: #666; font-size: 13px; margin-top: 5px; }
    .aiohm-avatar-uploader { display: flex; gap: 10px; align-items: center; }
    .aiohm-avatar-uploader input[type="text"] { flex-grow: 1; }
    .aiohm-avatar-uploader .button-secondary { flex-shrink: 0; height: 44px; padding: 0 20px; box-sizing: border-box; }
    .aiohm-color-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    .aiohm-color-grid .aiohm-setting-block { margin-bottom: 0; }
    .aiohm-color-grid .aiohm-setting-block label { font-size: 1em; }
    .aiohm-color-grid input[type="color"] { width: 100%; height: 44px; border: 1px solid #ddd; padding: 2px; cursor: pointer; border-radius: 4px; box-sizing: border-box; }
    .temp-value { color: var(--ohm-primary); font-weight: bold; }
    .form-actions { margin-top: 30px; }
    
    input[type="range"] { -webkit-appearance: none; appearance: none; width: 100%; height: 8px; background: var(--ohm-light-bg); border-radius: 5px; outline: none; margin-top: 10px; }
    input[type="range"]::-webkit-slider-thumb { -webkit-appearance: none; appearance: none; width: 20px; height: 20px; background: var(--ohm-primary); cursor: pointer; border-radius: 50%; border: 2px solid #fff; box-shadow: 0 0 5px rgba(0,0,0,0.2); }
    input[type="range"]::-moz-range-thumb { width: 20px; height: 20px; background: var(--ohm-primary); cursor: pointer; border-radius: 50%; border: 2px solid #fff; }

    .aiohm-test-column .aiohm-chat-container { border: 1px solid var(--ohm-light-bg); border-radius: 8px; overflow: hidden; background: var(--aiohm-background-color, #f0f4f8); display: flex; flex-direction: column; min-height: 500px; }
    .aiohm-test-column .aiohm-chat-header { background: var(--aiohm-primary-color, #1f5014); color: var(--aiohm-text-color, #ffffff); padding: 10px 15px; display: flex; justify-content: space-between; align-items: center; }
    .aiohm-test-column .aiohm-chat-status { display: flex; align-items: center; gap: 8px; }
    .aiohm-test-column .aiohm-status-indicator { width: 8px; height: 8px; border-radius: 50%; background-color: #28a745; animation: pulse 2s infinite; }
    @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }
    .aiohm-test-column .aiohm-chat-messages { flex-grow: 1; padding: 10px; overflow-y: auto; }
    .aiohm-test-column .aiohm-message { display: flex; gap: 10px; max-width: 85%; margin-bottom: 10px; }
    .aiohm-test-column .aiohm-message-bot { align-self: flex-start; }
    .aiohm-test-column .aiohm-message-user { align-self: flex-end; flex-direction: row-reverse; }
    .aiohm-test-column .aiohm-message-avatar { width: 30px; height: 30px; border-radius: 50%; flex-shrink: 0; }
    .aiohm-test-column .aiohm-message-avatar img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; }
    
    .aiohm-test-column .aiohm-message-bubble { padding: 10px 15px; border-radius: 12px; line-height: 1.6; }
    .aiohm-test-column .aiohm-message-bot .aiohm-message-bubble { background-color: #fff; border: 1px solid var(--ohm-light-bg); color: var(--ohm-dark); border-bottom-left-radius: 4px; }
    .aiohm-test-column .aiohm-message-user .aiohm-message-bubble { background-color: var(--aiohm-primary-color, #457d58); border-bottom-right-radius: 4px; }
    .aiohm-message-bubble.text-light { color: #ffffff; }
    .aiohm-message-bubble.text-dark { color: #272727; }

    .aiohm-test-column .aiohm-chat-input-container { padding: 10px; background-color: #fff; border-top: 1px solid var(--ohm-light-bg); }
    .aiohm-test-column .aiohm-chat-input-wrapper { display: flex; align-items: center; border: 1px solid var(--ohm-light-bg); border-radius: 8px; padding: 5px; transition: border-color 0.2s, box-shadow 0.2s; }
    .aiohm-test-column .aiohm-chat-input-wrapper:focus-within { border-color: var(--aiohm-primary-color, #457d58); box-shadow: 0 0 0 2px var(--ohm-light-accent); }
    .aiohm-test-column .aiohm-chat-input { flex-grow: 1; border: none; padding: 8px; background: transparent; outline: none; box-shadow: none; resize: none; font-family: var(--ohm-font-secondary); }
    .aiohm-test-column .aiohm-chat-input::placeholder { color: var(--ohm-muted-accent, #7d9b76); }
    .aiohm-test-column .aiohm-chat-send-btn { background: var(--aiohm-primary-color, #1f5014); border: none; border-radius: 5px; color: #fff; width: 38px; height: 38px; cursor: pointer; }
    .aiohm-test-column .aiohm-chat-send-btn:disabled { background: var(--ohm-muted-accent); }
    
    .aiohm-chat-footer-preview { text-align: center; }
    .aiohm-chat-footer-branding, .aiohm-chat-footer-button { transition: background-color 0.2s; }
    .aiohm-chat-footer-branding { padding: 8px 15px; background-color: #EBEBEB; font-size: 12px; color: #6c757d; }
    .aiohm-chat-footer-branding strong { color: var(--ohm-dark); }
    .aiohm-chat-footer-button { display: block; text-decoration: none; font-weight: bold; padding: 12px 15px; color: var(--aiohm-text-color, #ffffff); background-color: var(--aiohm-primary-color, #457d58); }
    .aiohm-chat-footer-button:hover { color: var(--aiohm-text-color, #ffffff); filter: brightness(90%); }
    
    .aiohm-search-container-wrapper, .q-and-a-generator { margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--ohm-light-bg); }
    .aiohm-search-controls { display: flex; gap: 15px; align-items: center; margin-bottom: 15px; }
    .aiohm-search-form { flex-grow: 1; }
    .aiohm-search-input-wrapper { display: flex; border: 2px solid var(--ohm-light-bg); border-radius: 8px; overflow: hidden; height: 44px; }
    .aiohm-search-input { flex-grow: 1; border: none; padding: 10px 15px; outline: none; }
    .aiohm-search-btn { background: var(--ohm-primary); border: none; color: white; padding: 0 15px; cursor: pointer; }
    .aiohm-search-filters select { height: 44px; padding: 0 10px; border-radius: 4px; border: 1px solid #ddd; }

    .aiohm-search-results { max-height: 250px; overflow-y: auto; padding-right: 10px; }
    .aiohm-search-result-item { background: #fdfdfd; border: 1px solid var(--ohm-light-bg); padding: 10px 15px; margin-bottom: 10px; border-radius: 4px; }
    .aiohm-search-result-item h4 { margin: 0 0 5px 0; font-size: 1.1em; }
    .aiohm-search-result-item p { margin: 0; font-size: 0.9em; color: #555; }
    .aiohm-search-result-item .result-meta { font-size: 0.8em; color: #777; margin-top: 5px; }
    
    .q-and-a-generator .button-secondary { width: 100%; margin-top: 10px; }
    .q-and-a-container { margin-top: 15px; background: #f8f9fa; padding: 15px; border-radius: 4px; border: 1px solid var(--ohm-light-bg); text-align: left; }
    .q-and-a-container .q-title { font-weight: bold; color: var(--ohm-dark-accent); }
</style>

<script>
    jQuery(document).ready(function($) {
        const defaultPrompt = <?php echo json_encode($default_prompt); ?>;

        function isColorDark(hex) {
            if (!hex) return false;
            const color = (hex.charAt(0) === '#') ? hex.substring(1, 7) : hex;
            if (color.length !== 6) return false;
            const r = parseInt(color.substring(0, 2), 16);
            const g = parseInt(color.substring(2, 4), 16);
            const b = parseInt(color.substring(4, 6), 16);
            const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
            return luminance < 0.5;
        }

        const testChat = {
            $container: $('#aiohm-test-chat'),
            $messages: $('#aiohm-test-chat .aiohm-chat-messages'),
            $input: $('#aiohm-test-chat .aiohm-chat-input'),
            $sendBtn: $('#aiohm-test-chat .aiohm-chat-send-btn'),
            isTyping: false,
            
            init: function() {
                this.$input.on('keydown', (e) => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); this.sendMessage(); } });
                this.$sendBtn.on('click', () => this.sendMessage());
                this.$input.on('input', (e) => this.handleInput(e));
            },

            handleInput: function(e) {
                this.$sendBtn.prop('disabled', $(e.target).val().trim().length === 0);
            },

            sendMessage: function() {
                const message = this.$input.val().trim();
                if (!message || this.isTyping) return;
                
                this.addMessage(message, 'user');
                this.$input.val('').trigger('input');
                this.showTypingIndicator();

                $.post(ajaxurl, {
                    action: 'aiohm_test_mirror_mode_chat',
                    nonce: $('#aiohm_mirror_mode_nonce_field').val(),
                    message: message,
                    settings: { 
                        qa_system_message: $('#qa_system_message').val(), 
                        qa_temperature: $('#qa_temperature').val(), 
                        business_name: $('#business_name').val(),
                        ai_model: $('#ai_model_selector').val()
                    }
                }).done(response => {
                    const answer = response.success ? response.data.answer : "Sorry, an error occurred.";
                    this.hideTypingIndicator();
                    this.addMessage(answer, 'bot');
                }).fail(() => {
                    this.hideTypingIndicator();
                    this.addMessage("Server error. Please try again.", 'bot', true);
                });
            },

            addMessage: function(content, type, isError = false) {
                const botAvatarUrl = $('#ai_avatar').val();
                let botAvatarHtml = botAvatarUrl ? `<img src="${botAvatarUrl}" alt="AI Avatar" class="aiohm-avatar-preview">` : `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M12 1v6m0 6v6m-3-9 3 3 3-3"></path></svg>`;
                const userAvatarHtml = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>`;
                
                const avatar = type === 'user' ? userAvatarHtml : botAvatarHtml;
                const sanitizedContent = $('<div/>').text(content).html().replace(/\n/g, '<br>');
                const textColorClass = (type === 'user' && isColorDark($('#primary_color').val())) ? 'text-light' : 'text-dark';

                const messageHtml = `<div class="aiohm-message aiohm-message-${type}"><div class="aiohm-message-avatar">${avatar}</div><div class="aiohm-message-bubble ${textColorClass}"><div class="aiohm-message-content">${isError ? '⚠️ ' : ''}${sanitizedContent}</div></div></div>`;
                
                this.$messages.append(messageHtml);
                this.$messages.scrollTop(this.$messages[0].scrollHeight);
            },

            showTypingIndicator: function() {
                this.isTyping = true;
                const botAvatarUrl = $('#ai_avatar').val();
                let botAvatarHtml = botAvatarUrl ? `<img src="${botAvatarUrl}" alt="AI Avatar" class="aiohm-avatar-preview">` : `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M12 1v6m0 6v6m-3-9 3 3 3-3"></path></svg>`;
                this.$messages.append(`<div class="aiohm-message aiohm-message-bot aiohm-typing-indicator"><div class="aiohm-message-avatar">${botAvatarHtml}</div><div class="aiohm-message-bubble"><div class="aiohm-typing-dots"><span></span><span></span><span></span></div></div></div>`);
                this.$messages.scrollTop(this.$messages[0].scrollHeight);
            },

            hideTypingIndicator: function() {
                this.isTyping = false;
                this.$messages.find('.aiohm-typing-indicator').remove();
            }
        };
        testChat.init();

        function updateLivePreview() {
            $('#aiohm-test-chat').css('--aiohm-primary-color', $('#primary_color').val());
            $('#aiohm-test-chat').css('--aiohm-text-color', $('#text_color').val());
            $('#aiohm-test-chat').css('--aiohm-background-color', $('#background_color').val());
            $('#aiohm-test-chat .aiohm-chat-title-preview').text($('#business_name').val());
            $('.aiohm-avatar-preview').attr('src', $('#ai_avatar').val());

            const footerPreview = $('.aiohm-chat-footer-preview');
            const meetingUrl = $('#meeting_button_url').val().trim();
            const brandingHtml = `<div class="aiohm-chat-footer-branding"><span>Powered by <strong>AIOHM</strong></span></div>`;
            const buttonHtml = `<a href="#" class="aiohm-chat-footer-button" onclick="event.preventDefault(); window.open('${meetingUrl}', '_blank');">Book a Meeting</a>`;

            if (meetingUrl) {
                footerPreview.html(buttonHtml);
            } else {
                footerPreview.html(brandingHtml);
            }
        }
        
        $('#mirror-mode-settings-form input, #mirror-mode-settings-form select, #mirror-mode-settings-form textarea').on('input change', updateLivePreview);
        
        $('#qa_temperature').on('input', function() {
            $('.temp-value').text($(this).val());
        });

        $('#reset-prompt-btn').on('click', function(e) {
            e.preventDefault();
            showAdminNotice(
                'Are you sure you want to reset the prompt to its default? <button id="confirm-prompt-reset" class="button button-small" style="margin-left: 10px;">Confirm Reset</button>',
                'warning'
            );
        });

        $(document).on('click', '#confirm-prompt-reset', function() {
            $('#qa_system_message').val(defaultPrompt);
            $('#aiohm-admin-notice').fadeOut();
            showAdminNotice('Prompt has been reset to default.', 'success');
        });
        
        $('#upload_ai_avatar_button').on('click', function(e) {
            e.preventDefault();
            var mediaUploader = wp.media({
                title: 'Choose AI Avatar',
                button: { text: 'Choose Avatar' },
                multiple: false
            });
            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#ai_avatar').val(attachment.url).trigger('input');
            });
            mediaUploader.open();
        });

        $('#save-mirror-mode-settings').on('click', function(e) {
            e.preventDefault();
            const $btn = $(this);
            $btn.prop('disabled', true).text('Saving...');

            $.post(ajaxurl, {
                action: 'aiohm_save_mirror_mode_settings',
                nonce: $('#aiohm_mirror_mode_nonce_field').val(),
                form_data: $('#mirror-mode-settings-form').serialize()
            }).done(function(response) {
                showAdminNotice(response.success ? response.data.message : 'Error: ' + (response.data.message || 'Could not save.'), response.success ? 'success' : 'error');
            }).fail(function() {
                showAdminNotice('A server error occurred.', 'error');
            }).always(function() {
                $btn.prop('disabled', false).text('Save Mirror Mode Settings');
            });
        });

        function showAdminNotice(message, type = 'success') {
            const $notice = $('#aiohm-admin-notice');
            $notice.removeClass('notice-success notice-error notice-warning').addClass('notice-' + type).addClass('is-dismissible');
            $notice.find('p').html(message);
            $notice.fadeIn();
            if (!message.includes('<button')) {
                setTimeout(function() {
                    $notice.fadeOut();
                }, 5000);
            }
        }
        
        $('#generate-q-and-a').on('click', function(e) {
            e.preventDefault();
            const $btn = $(this);
            const $resultsContainer = $('#q-and-a-results');
            $btn.prop('disabled', true).text('Generating...');
            $resultsContainer.html('<span class="spinner is-active"></span>');

            $.post(ajaxurl, {
                action: 'aiohm_generate_mirror_mode_qa',
                nonce: $('#aiohm_mirror_mode_nonce_field').val(),
            }).done(function(response) {
                if (response.success) {
                    const qa = response.data.qa_pair;
                    $resultsContainer.html(`<p><strong class="q-title">Question:</strong><br>${qa.question}</p><p><strong class="q-title">Answer:</strong><br>${qa.answer}</p>`);
                } else {
                    $resultsContainer.html(`<p style="color:red;">${response.data.message || 'Failed to generate.'}</p>`);
                }
            }).fail(function() {
                $resultsContainer.html('<p style="color:red;">A server error occurred.</p>');
            }).always(function() {
                $btn.prop('disabled', false).text('Generate Sample Q&A');
            });
        });

        $('.aiohm-search-btn').on('click', function() {
            const query = $('.aiohm-search-input').val();
            const filter = $('#aiohm-test-search-filter').val();
            const $resultsContainer = $('.aiohm-search-results');
            $resultsContainer.html('<span class="spinner is-active"></span>');
            
            $.post(ajaxurl, {
                action: 'aiohm_admin_search_knowledge',
                nonce: $('#aiohm_mirror_mode_nonce_field').val(),
                query: query,
                content_type_filter: filter
            }).done(function(response) {
                $resultsContainer.empty();
                if (response.success && response.data.results.length > 0) {
                    response.data.results.forEach(function(item) {
                        $resultsContainer.append(`<div class="aiohm-search-result-item"><h4><a href="${item.url}" target="_blank">${item.title}</a></h4><p>${item.excerpt}</p><div class="result-meta">Type: ${item.content_type} | Similarity: ${item.similarity}%</div></div>`);
                    });
                } else {
                     $resultsContainer.html('<div class="aiohm-search-result-item"><p>No results found.</p></div>');
                }
            }).fail(function() {
                 $resultsContainer.html('<div class="aiohm-search-result-item"><p>Search request failed.</p></div>');
            });
        });
        
        updateLivePreview();
    });
</script>