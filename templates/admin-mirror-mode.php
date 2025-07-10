<?php
/**
 * Admin Mirror Mode Settings page template for Club members.
 * Final version with a two-column layout: settings on the left, live test chat and Q&A generator on the right.
 */

if (!defined('ABSPATH')) {
    exit;
}

// --- Data Fetching ---
$settings = AIOHM_KB_Assistant::get_settings();
?>

<div class="wrap aiohm-settings-page aiohm-mirror-mode-page">
    <h1><?php _e('Mirror Mode Customization', 'aiohm-kb-assistant'); ?></h1>
    <p class="page-description"><?php _e('Fine-tune your AI\'s personality and appearance on the left, and test your changes in real-time on the right.', 'aiohm-kb-assistant'); ?></p>

    <div id="aiohm-admin-notice" class="notice" style="display:none; margin-top: 10px;"><p></p></div>

    <div class="aiohm-mirror-mode-layout">
        
        <div class="aiohm-settings-form-wrapper">
            <form id="mirror-mode-settings-form">
                <?php wp_nonce_field('aiohm_mirror_mode_nonce'); ?>

                <div class="aiohm-setting-block">
                    <label for="business_name">Business Name</label>
                    <input type="text" id="business_name" name="settings[business_name]" value="<?php echo esc_attr($settings['business_name'] ?? get_bloginfo('name')); ?>">
                    <p class="description">Required for all AI modes. The bot will use this name in its responses.</p>
                </div>

                <div class="aiohm-setting-block">
                    <label for="qa_system_message">AI System Message (Personality)</label>
                    <textarea id="qa_system_message" name="settings[qa_system_message]" rows="15"><?php echo esc_textarea($settings['qa_system_message']); ?></textarea>
                    <p class="description">This is the core personality prompt for the AI. Keep tags like <code>{context}</code> intact.</p>
                </div>

                <div class="aiohm-setting-block">
                    <label for="qa_temperature">Temperature: <span class="temp-value"><?php echo esc_attr($settings['qa_temperature']); ?></span></label>
                    <input type="range" id="qa_temperature" name="settings[qa_temperature]" value="<?php echo esc_attr($settings['qa_temperature']); ?>" min="0" max="1" step="0.1">
                    <p class="description">Lower values are more predictable; higher values are more creative.</p>
                </div>
                
                <div class="aiohm-setting-block">
                    <label>Inline Bot Dimensions</label>
                    <div class="dimension-inputs">
                        <div class="dimension-input">
                            <input type="text" id="qa_desktop_width" name="settings[qa_desktop_width]" value="<?php echo esc_attr($settings['qa_desktop_width']); ?>" placeholder="100%">
                            <p class="description">Desktop Width</p>
                        </div>
                        <div class="dimension-input">
                            <input type="text" id="qa_desktop_height" name="settings[qa_desktop_height]" value="<?php echo esc_attr($settings['qa_desktop_height']); ?>" placeholder="500px">
                            <p class="description">Desktop Height</p>
                        </div>
                    </div>
                     <p class="description">Applies to the `[aiohm_chat]` shortcode. Use 'px' or '%'.</p>
                </div>

                <div class="form-actions">
                    <button type="button" id="save-mirror-mode-settings" class="button button-primary"><?php _e('Save Chat Settings', 'aiohm-kb-assistant'); ?></button>
                </div>
            </form>
        </div>
        
        <div class="aiohm-test-chat-wrapper">
            <h2><?php _e('Test Your Chatbot', 'aiohm-kb-assistant'); ?></h2>
            <div class="aiohm-chat-container" id="aiohm-test-chat">
                <div class="aiohm-chat-header">
                    <div class="aiohm-chat-title">Live Preview</div>
                    <div class="aiohm-chat-status"><span class="aiohm-status-indicator" data-status="ready"></span> <span class="aiohm-status-text">Ready</span></div>
                </div>
                <div class="aiohm-chat-messages">
                    <div class="aiohm-message aiohm-message-bot">
                        <div class="aiohm-message-avatar"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M12 1v6m0 6v6m-3-9 3 3 3-3"></path></svg></div>
                        <div class="aiohm-message-bubble"><div class="aiohm-message-content">Ask a question to test the settings from the left. Your changes are applied instantly here without saving.</div></div>
                    </div>
                </div>
                <div class="aiohm-chat-input-container">
                    <div class="aiohm-chat-input-wrapper">
                        <textarea class="aiohm-chat-input" placeholder="Type your test question..." rows="1"></textarea>
                        <button type="button" class="aiohm-chat-send-btn" disabled><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22,2 15,22 11,13 2,9"></polygon></svg></button>
                    </div>
                </div>
            </div>
            
            <div class="q-and-a-generator">
                <button type="button" id="generate-q-and-a" class="button button-secondary"><?php _e('Generate Sample Q&A', 'aiohm-kb-assistant'); ?></button>
                <div id="q-and-a-results" class="q-and-a-container">
                     </div>
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

    .aiohm-settings-page h1, .aiohm-settings-page h2 { font-family: var(--ohm-font-primary); color: var(--ohm-dark-accent); }
    .aiohm-settings-page .page-description { font-size: 1.1em; padding-bottom: 1em; border-bottom: 1px solid var(--ohm-light-bg); }
    
    .aiohm-mirror-mode-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 20px; }
    .aiohm-settings-form-wrapper, .aiohm-test-chat-wrapper { background: #fff; padding: 20px 30px; border: 1px solid var(--ohm-light-bg); border-radius: 8px; }
    
    .aiohm-setting-block { margin-bottom: 30px; }
    .aiohm-setting-block label { display: block; font-family: var(--ohm-font-primary); font-weight: bold; font-size: 1.1em; color: var(--ohm-dark-accent); margin-bottom: 8px; }
    .aiohm-setting-block input[type="text"], .aiohm-setting-block textarea { width: 100%; padding: 10px; font-family: var(--ohm-font-secondary); font-size: 1em; border: 1px solid #ddd; border-radius: 4px; }
    .aiohm-setting-block p.description { font-family: var(--ohm-font-secondary); color: #666; font-size: 13px; margin-top: 8px; }

    input[type="range"] { -webkit-appearance: none; appearance: none; width: 100%; height: 8px; background: var(--ohm-light-bg); border-radius: 5px; outline: none; }
    input[type="range"]::-webkit-slider-thumb { -webkit-appearance: none; appearance: none; width: 20px; height: 20px; background: var(--ohm-primary); cursor: pointer; border-radius: 50%; }
    .temp-value { color: var(--ohm-primary); font-weight: bold; }

    .dimension-inputs { display: flex; gap: 20px; }
    .dimension-input { flex: 1; }
    .dimension-input p.description { margin-top: 4px; font-weight: bold; color: var(--ohm-dark); }
    
    .aiohm-settings-page .button-primary { font-size: 1.1em; padding: 8px 24px; height: auto; background-color: var(--ohm-primary); border-color: var(--ohm-dark-accent); }
    .aiohm-settings-page .button-secondary { font-size: 1.1em; padding: 8px 24px; height: auto; }

    /* Test Chat Styles */
    .aiohm-test-chat-wrapper .aiohm-chat-container { height: 500px; display: flex; flex-direction: column; border: 1px solid var(--ohm-light-bg); border-radius: 8px; overflow: hidden; background: #fdfdfd; }
    .aiohm-test-chat-wrapper .aiohm-chat-header { background: var(--ohm-dark-accent); color: white; padding: 10px 15px; }
    .aiohm-test-chat-wrapper .aiohm-chat-messages { flex-grow: 1; padding: 10px; overflow-y: auto; }
    .aiohm-test-chat-wrapper .aiohm-message { display: flex; margin-bottom: 12px; max-width: 85%; }
    .aiohm-test-chat-wrapper .aiohm-message-user { margin-left: auto; flex-direction: row-reverse; }
    .aiohm-test-chat-wrapper .aiohm-message-avatar { flex-shrink: 0; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 8px; }
    .aiohm-test-chat-wrapper .aiohm-message-user .aiohm-message-avatar { background: var(--ohm-muted-accent); color: white; }
    .aiohm-test-chat-wrapper .aiohm-message-bot .aiohm-message-avatar { background: var(--ohm-primary); color: white; }
    .aiohm-test-chat-wrapper .aiohm-message-bubble { padding: 8px 12px; border-radius: 12px; }
    .aiohm-test-chat-wrapper .aiohm-message-user .aiohm-message-bubble { background: var(--ohm-light-accent); }
    .aiohm-test-chat-wrapper .aiohm-message-bot .aiohm-message-bubble { background: #fff; border: 1px solid var(--ohm-light-bg); }
    .aiohm-test-chat-wrapper .aiohm-chat-input-container { padding: 10px; border-top: 1px solid var(--ohm-light-bg); }
    .aiohm-test-chat-wrapper .aiohm-chat-input-wrapper { display: flex; align-items: center; gap: 10px; }
    .aiohm-test-chat-wrapper .aiohm-chat-input { flex-grow: 1; }
    .aiohm-test-chat-wrapper .aiohm-chat-send-btn { background: var(--ohm-primary); color: white; border-radius: 50%; width: 36px; height: 36px; border: none; cursor: pointer; }
    .aiohm-test-chat-wrapper .aiohm-chat-send-btn:disabled { background: var(--ohm-muted-accent); cursor: not-allowed; }

    /* Q&A Generator */
    .q-and-a-generator { margin-top: 20px; }
    .q-and-a-container { margin-top: 15px; padding-top: 15px; border-top: 1px solid var(--ohm-light-bg); min-height: 50px; }
    .q-and-a-pair { margin-bottom: 20px; background: #fdfdfd; padding: 15px; border-left: 3px solid var(--ohm-light-accent); }
    .q-and-a-pair strong { display: block; margin-bottom: 5px; color: var(--ohm-dark-accent); }

    @media (max-width: 1200px) { .aiohm-mirror-mode-layout { grid-template-columns: 1fr; } }
</style>


<script>
jQuery(document).ready(function($) {
    const testChat = {
        $container: $('#aiohm-test-chat'),
        $messages: $('#aiohm-test-chat .aiohm-chat-messages'),
        $input: $('#aiohm-test-chat .aiohm-chat-input'),
        $sendBtn: $('#aiohm-test-chat .aiohm-chat-send-btn'),
        $statusText: $('#aiohm-test-chat .aiohm-status-text'),
        $statusIndicator: $('#aiohm-test-chat .aiohm-status-indicator'),
        isTyping: false,

        init: function() {
            this.$input.on('input keydown', this.handleInput.bind(this));
            this.$sendBtn.on('click', this.sendMessage.bind(this));
        },

        handleInput: function(e) {
            this.$sendBtn.prop('disabled', this.$input.val().trim() === '');
            if (e.type === 'keydown' && e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        },

        sendMessage: function() {
            const message = this.$input.val().trim();
            if (!message || this.isTyping) return;
            
            this.addMessage(message, 'user');
            this.$input.val('');
            this.$sendBtn.prop('disabled', true);
            this.showTypingIndicator();

            // AJAX call to test the chat
            $.post(ajaxurl, {
                action: 'aiohm_test_mirror_mode_chat', // This is a new AJAX action
                nonce: $('#_wpnonce').val(),
                message: message,
                settings: { // Send current form settings for real-time preview
                    qa_system_message: $('#qa_system_message').val(),
                    qa_temperature: $('#qa_temperature').val(),
                    business_name: $('#business_name').val()
                }
            })
            .done(response => {
                const answer = response.success ? response.data.answer : "Sorry, I couldn't get a response.";
                this.addMessage(answer, 'bot', !response.success);
            })
            .fail(() => this.addMessage("An unexpected server error occurred.", 'bot', true))
            .always(() => this.hideTypingIndicator());
        },

        addMessage: function(content, type, isError = false) {
            const avatar = type === 'user' 
                ? '<svg width="20" height="20" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>'
                : '<svg width="20" height="20" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"></circle><path d="M12 1v6m0 6v6"></path><path d="m9 9 3 3 3-3"></path></svg>';
            
            const errorClass = isError ? 'aiohm-message-error' : '';
            const messageHtml = `
                <div class="aiohm-message aiohm-message-${type} ${errorClass}">
                    <div class="aiohm-message-avatar">${avatar}</div>
                    <div class="aiohm-message-bubble"><div class="aiohm-message-content">${content.replace(/\n/g, '<br>')}</div></div>
                </div>`;
            this.$messages.append(messageHtml);
            this.$messages.scrollTop(this.$messages[0].scrollHeight);
        },
        
        showTypingIndicator: function() {
            this.isTyping = true;
            this.setStatus('typing');
            const typingHtml = `<div class="aiohm-message aiohm-message-bot aiohm-typing-indicator"><div class="aiohm-message-avatar">${this.getBotAvatar()}</div><div class="aiohm-message-bubble"><div class="aiohm-typing-dots"><span></span><span></span><span></span></div></div></div>`;
            this.$messages.append(typingHtml);
            this.$messages.scrollTop(this.$messages[0].scrollHeight);
        },

        hideTypingIndicator: function() {
            this.isTyping = false;
            this.setStatus('ready');
            this.$messages.find('.aiohm-typing-indicator').remove();
        },
        
        setStatus: function(status) {
            this.$statusText.text(status.charAt(0).toUpperCase() + status.slice(1));
            this.$statusIndicator.attr('data-status', status);
        },

        getBotAvatar: function() {
            return '<svg width="20" height="20" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"></circle><path d="M12 1v6m0 6v6"></path><path d="m9 9 3 3 3-3"></path></svg>';
        }
    };

    testChat.init();

    // --- Event Handler for Temperature Slider ---
    $('#qa_temperature').on('input', function() {
        $('.temp-value').text($(this).val());
    });

    // --- Event Handler for Save Button ---
    $('#save-mirror-mode-settings').on('click', function(e) {
        e.preventDefault();
        const $btn = $(this);
        const originalText = $btn.text();
        $btn.prop('disabled', true).text('Saving...');

        $.post(ajaxurl, {
            action: 'aiohm_save_mirror_mode_settings',
            nonce: $('#_wpnonce').val(),
            'aiohm_kb_settings[qa_system_message]': $('#qa_system_message').val(),
            'aiohm_kb_settings[qa_temperature]': $('#qa_temperature').val(),
            'aiohm_kb_settings[qa_desktop_width]': $('#qa_desktop_width').val(),
            'aiohm_kb_settings[qa_desktop_height]': $('#qa_desktop_height').val(),
            'aiohm_kb_settings[business_name]': $('#business_name').val()
        })
        .done(response => {
            showAdminNotice(response.success ? 'Settings saved successfully.' : 'Error: Could not save settings.', response.success ? 'success' : 'error');
        })
        .fail(() => showAdminNotice('An unexpected server error occurred.', 'error'))
        .always(() => $btn.prop('disabled', false).text(originalText));
    });
    
    function showAdminNotice(message, type = 'success') {
        const $notice = $('#aiohm-admin-notice');
        $notice.removeClass('notice-success notice-error notice-warning notice-info').addClass('notice-' + type).addClass('is-dismissible');
        $notice.find('p').html(message);
        $notice.fadeIn().delay(5000).fadeOut();
    }
});
</script>