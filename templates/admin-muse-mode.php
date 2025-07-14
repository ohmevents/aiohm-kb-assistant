<?php
/**
 * Admin Muse Mode Settings page template for Club members.
 * Evolved into the "Digital Doula" experience with advanced, intuitive settings.
 * Based on the stable Mirror Mode template.
 */

if (!defined('ABSPATH')) exit;

// Fetch all settings and then get the specific part for Muse Mode
$all_settings = AIOHM_KB_Assistant::get_settings();
$settings = $all_settings['muse_mode'] ?? [];
$global_settings = $all_settings; // for API keys

// Default prompt for Muse Mode
$default_prompt = "You are Muse, a private brand assistant. Your role is to help the user develop their brand by using the provided context, which includes public information and the user's private 'Brand Soul' answers. Synthesize this information to provide creative ideas, answer strategic questions, and help draft content. Always prioritize the private 'Brand Soul' context when available.";
$system_prompt = !empty($settings['system_prompt']) ? $settings['system_prompt'] : $default_prompt;

// Archetypes for the dropdown
$brand_archetypes = [
    'the_creator' => 'The Creator',
    'the_sage' => 'The Sage',
    'the_innocent' => 'The Innocent',
    'the_explorer' => 'The Explorer',
    'the_ruler' => 'The Ruler',
    'the_hero' => 'The Hero',
    'the_lover' => 'The Lover',
    'the_jester' => 'The Jester',
    'the_everyman' => 'The Everyman',
    'the_caregiver' => 'The Caregiver',
    'the_magician' => 'The Magician',
    'the_outlaw' => 'The Outlaw',
];

?>

<div class="wrap aiohm-settings-page aiohm-muse-mode-page">
    <h1><?php _e('Muse Mode: Your Digital Doula', 'aiohm-kb-assistant'); ?></h1>
    <p class="page-description"><?php _e('Here, you attune your AI to be a true creative partner. Define its energetic signature and workflow to transform your brand dialogue.', 'aiohm-kb-assistant'); ?></p>

    <div id="aiohm-admin-notice" class="notice" style="display:none; margin-top: 10px;"><p></p></div>

    <div class="aiohm-muse-mode-layout">
        
        <div class="aiohm-settings-form-wrapper">
            <form id="muse-mode-settings-form">
                <?php wp_nonce_field('aiohm_muse_mode_nonce', 'aiohm_muse_mode_nonce_field'); ?>
                
                <div class="aiohm-settings-section">
                    <h2><?php _e('Attuned: Energetic Signature', 'aiohm-kb-assistant'); ?></h2>

                    <div class="aiohm-setting-block">
                        <label for="brand_archetype"><?php _e('Brand Archetype', 'aiohm-kb-assistant'); ?></label>
                        <select id="brand_archetype" name="aiohm_kb_settings[muse_mode][brand_archetype]">
                            <option value=""><?php _e('-- Select an Archetype --', 'aiohm-kb-assistant'); ?></option>
                            <?php foreach ($brand_archetypes as $key => $label) : ?>
                                <option value="<?php echo esc_attr($key); ?>" <?php selected($settings['brand_archetype'] ?? '', $key); ?>><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php _e('Select an archetype to give your Muse a foundational personality.', 'aiohm-kb-assistant'); ?></p>
                    </div>

                    <div class="aiohm-setting-block">
                        <label for="core_values"><?php _e('Core Values', 'aiohm-kb-assistant'); ?></label>
                        <input type="text" id="core_values" name="aiohm_kb_settings[muse_mode][core_values]" value="<?php echo esc_attr($settings['core_values'] ?? ''); ?>" placeholder="e.g., Authenticity, Innovation, Playfulness">
                        <p class="description"><?php _e('Enter 3-5 comma-separated brand values the AI must align with.', 'aiohm-kb-assistant'); ?></p>
                    </div>

                    <div class="aiohm-setting-block">
                        <label for="forbidden_phrases"><?php _e('Forbidden Phrases', 'aiohm-kb-assistant'); ?></label>
                        <textarea id="forbidden_phrases" name="aiohm_kb_settings[muse_mode][forbidden_phrases]" rows="3" placeholder="e.g., buy now, limited time offer, customers"><?php echo esc_textarea($settings['forbidden_phrases'] ?? ''); ?></textarea>
                        <p class="description"><?php _e('Enter comma-separated words or phrases the AI must never use.', 'aiohm-kb-assistant'); ?></p>
                    </div>
                </div>
                <div class="aiohm-settings-section">
                     <h2><?php _e('Intuitive: Creative Workflow', 'aiohm-kb-assistant'); ?></h2>

                    <div class="aiohm-setting-block">
                        <label for="assistant_name"><?php _e('Assistant Name', 'aiohm-kb-assistant'); ?></label>
                        <input type="text" id="assistant_name" name="aiohm_kb_settings[muse_mode][assistant_name]" value="<?php echo esc_attr($settings['assistant_name'] ?? 'Muse'); ?>">
                    </div>

                    <div class="aiohm-setting-block">
                        <div class="aiohm-setting-header">
                            <label for="system_prompt"><?php _e('Soul Signature (System Prompt)', 'aiohm-kb-assistant'); ?></label>
                            <button type="button" id="reset-prompt-btn" class="button-link"><?php _e('Reset to Default', 'aiohm-kb-assistant'); ?></button>
                        </div>
                        <textarea id="system_prompt" name="aiohm_kb_settings[muse_mode][system_prompt]" rows="10"><?php echo esc_textarea($system_prompt); ?></textarea>
                        <p class="description"><?php _e('This is the core instruction set for your AI. The Archetype, Values, and other settings will be added to this.', 'aiohm-kb-assistant'); ?></p>
                    </div>

                    <div class="aiohm-setting-block">
                        <label for="ai_model_selector"><?php _e('AI Model', 'aiohm-kb-assistant'); ?></label>
                        <select id="ai_model_selector" name="aiohm_kb_settings[muse_mode][ai_model]">
                            <?php if (!empty($global_settings['openai_api_key'])): ?>
                                <option value="gpt-4" <?php selected($settings['ai_model'] ?? 'gpt-4', 'gpt-4'); ?>>OpenAI: GPT-4</option>
                                <option value="gpt-3.5-turbo" <?php selected($settings['ai_model'] ?? '', 'gpt-3.5-turbo'); ?>>OpenAI: GPT-3.5 Turbo</option>
                            <?php endif; ?>
                            <?php if (!empty($global_settings['gemini_api_key'])): ?>
                                <option value="gemini-pro" <?php selected($settings['ai_model'] ?? '', 'gemini-pro'); ?>>Google: Gemini Pro</option>
                            <?php endif; ?>
                            <?php if (!empty($global_settings['claude_api_key'])): ?>
                                <option value="claude-3-sonnet" <?php selected($settings['ai_model'] ?? '', 'claude-3-sonnet'); ?>>Anthropic: Claude 3 Sonnet</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="aiohm-setting-block">
                        <label for="temperature"><?php _e('Temperature:', 'aiohm-kb-assistant'); ?> <span class="temp-value"><?php echo esc_attr($settings['temperature'] ?? '0.7'); ?></span></label>
                        <input type="range" id="temperature" name="aiohm_kb_settings[muse_mode][temperature]" value="<?php echo esc_attr($settings['temperature'] ?? '0.7'); ?>" min="0" max="1" step="0.1">
                        <p class="description"><?php _e('Lower is more predictable; higher is more creative.', 'aiohm-kb-assistant'); ?></p>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" id="save-muse-mode-settings" class="button button-primary"><?php _e('Save Muse Settings', 'aiohm-kb-assistant'); ?></button>
                </div>
            </form>
        </div>
        
        <div class="aiohm-test-column">
            <div id="aiohm-test-chat" class="aiohm-chat-container">
                <div class="aiohm-chat-header">
                    <div class="aiohm-chat-title-preview"><?php echo esc_html($settings['assistant_name'] ?? 'Muse'); ?></div>
                    <div class="aiohm-chat-status">
                        <span class="aiohm-status-indicator" data-status="ready"></span>
                        <span class="aiohm-status-text">Ready</span>
                    </div>
                </div>
                <div class="aiohm-chat-messages">
                    <div class="aiohm-message aiohm-message-bot">
                        <div class="aiohm-message-bubble"><div class="aiohm-message-content">I'm your private brand assistant. Ask me to help you create content or brainstorm ideas. Your settings are applied here in real-time.</div></div>
                    </div>
                </div>
                <div class="aiohm-chat-input-container">
                    <div class="aiohm-chat-input-wrapper">
                        <textarea class="aiohm-chat-input" placeholder="Ask your question here..." rows="1"></textarea>
                        <button type="button" class="aiohm-chat-send-btn" disabled><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg></button>
                    </div>
                </div>
            </div>

             <div class="aiohm-search-container-wrapper">
                <h3><?php _e('Test Knowledge Base Context', 'aiohm-kb-assistant'); ?></h3>
                <p class="description">Check what information the AI can find in your knowledge base for a given query.</p>
                <div class="aiohm-search-controls">
                    <div class="aiohm-search-form">
                        <div class="aiohm-search-input-wrapper">
                            <input type="text" class="aiohm-search-input" placeholder="Search knowledge base...">
                            <button type="button" class="aiohm-search-btn">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.35-4.35"></path></svg>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="aiohm-search-results"></div>
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
    .aiohm-muse-mode-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 20px; }
    .aiohm-settings-form-wrapper, .aiohm-test-column { background: #fff; padding: 20px 30px; border: 1px solid var(--ohm-light-bg); border-radius: 8px; }
    .aiohm-settings-section {
        background: #fdfdfd;
        border: 1px solid var(--ohm-light-bg);
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 25px;
    }
    .aiohm-settings-section h2 {
        margin-top: 0;
        padding-bottom: 15px;
        border-bottom: 1px solid var(--ohm-light-bg);
        margin-bottom: 20px;
    }
    .aiohm-setting-block { margin-bottom: 20px; }
    .aiohm-setting-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
    .aiohm-setting-header label { margin-bottom: 0; }
    .aiohm-setting-block label { display: block; font-family: var(--ohm-font-primary); font-weight: bold; font-size: 1.1em; color: var(--ohm-dark-accent); margin-bottom: 8px; }
    .aiohm-setting-block input[type="text"], .aiohm-setting-block input[type="url"], .aiohm-setting-block textarea, .aiohm-setting-block select { width: 100%; padding: 10px; font-family: var(--ohm-font-secondary); font-size: 1em; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
    .aiohm-setting-block p.description { font-family: var(--ohm-font-secondary); color: #666; font-size: 13px; margin-top: 5px; }
    
    .temp-value { color: var(--ohm-primary); font-weight: bold; }
    .form-actions { margin-top: 30px; }
    
    input[type="range"] { -webkit-appearance: none; appearance: none; width: 100%; height: 8px; background: var(--ohm-light-bg); border-radius: 5px; outline: none; margin-top: 10px; }
    input[type="range"]::-webkit-slider-thumb { -webkit-appearance: none; appearance: none; width: 20px; height: 20px; background: var(--ohm-primary); cursor: pointer; border-radius: 50%; border: 2px solid #fff; box-shadow: 0 0 5px rgba(0,0,0,0.2); }
    input[type="range"]::-moz-range-thumb { width: 20px; height: 20px; background: var(--ohm-primary); cursor: pointer; border-radius: 50%; border: 2px solid #fff; }

    .aiohm-test-column .aiohm-chat-container { border: 1px solid var(--ohm-light-bg); border-radius: 8px; overflow: hidden; background: #f9f9f9; display: flex; flex-direction: column; min-height: 500px; }
    .aiohm-test-column .aiohm-chat-header { background: var(--ohm-dark); color: #fff; padding: 10px 15px; display: flex; justify-content: space-between; align-items: center; }
    .aiohm-test-column .aiohm-chat-status { display: flex; align-items: center; gap: 8px; }
    .aiohm-test-column .aiohm-status-indicator { width: 8px; height: 8px; border-radius: 50%; background-color: #28a745; animation: pulse 2s infinite; }
    @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }
    .aiohm-test-column .aiohm-chat-messages { flex-grow: 1; padding: 10px; overflow-y: auto; background-color: #fff; }
    .aiohm-test-column .aiohm-message { display: flex; gap: 10px; max-width: 85%; margin-bottom: 10px; }
    .aiohm-test-column .aiohm-message-bot { align-self: flex-start; }
    .aiohm-test-column .aiohm-message-user { align-self: flex-end; flex-direction: row-reverse; }
    
    .aiohm-test-column .aiohm-message-bubble { padding: 10px 15px; border-radius: 12px; line-height: 1.6; }
    .aiohm-test-column .aiohm-message-bot .aiohm-message-bubble { background-color: var(--ohm-light-bg); color: var(--ohm-dark); border-bottom-left-radius: 4px; }
    .aiohm-test-column .aiohm-message-user .aiohm-message-bubble { background-color: var(--ohm-dark); color: #fff; border-bottom-right-radius: 4px; }

    .aiohm-test-column .aiohm-chat-input-container { padding: 10px; background-color: #f1f1f1; border-top: 1px solid var(--ohm-light-bg); }
    .aiohm-test-column .aiohm-chat-input-wrapper { display: flex; align-items: center; border: 1px solid var(--ohm-light-bg); border-radius: 8px; padding: 5px; transition: border-color 0.2s, box-shadow 0.2s; background: #fff; }
    .aiohm-test-column .aiohm-chat-input-wrapper:focus-within { border-color: var(--ohm-primary); box-shadow: 0 0 0 2px var(--ohm-light-accent); }
    .aiohm-test-column .aiohm-chat-input { flex-grow: 1; border: none; padding: 8px; background: transparent; outline: none; box-shadow: none; resize: none; font-family: var(--ohm-font-secondary); }
    .aiohm-test-column .aiohm-chat-send-btn { background: var(--ohm-primary); border: none; border-radius: 5px; color: #fff; width: 38px; height: 38px; cursor: pointer; }
    .aiohm-test-column .aiohm-chat-send-btn:disabled { background: var(--ohm-muted-accent); }
    
    .aiohm-search-container-wrapper { margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--ohm-light-bg); }
    .aiohm-search-controls { display: flex; gap: 15px; align-items: center; margin-bottom: 15px; }
    .aiohm-search-form { flex-grow: 1; }
    .aiohm-search-input-wrapper { display: flex; border: 2px solid var(--ohm-light-bg); border-radius: 8px; overflow: hidden; height: 44px; }
    .aiohm-search-input { flex-grow: 1; border: none; padding: 10px 15px; outline: none; }
    .aiohm-search-btn { background: var(--ohm-primary); border: none; color: white; padding: 0 15px; cursor: pointer; }

    .aiohm-search-results { max-height: 200px; overflow-y: auto; padding-right: 10px; }
    .aiohm-search-result-item { background: #fdfdfd; border: 1px solid var(--ohm-light-bg); padding: 10px 15px; margin-bottom: 10px; border-radius: 4px; }
    .aiohm-search-result-item h4 { margin: 0 0 5px 0; font-size: 1.1em; }
    .aiohm-search-result-item p { margin: 0; font-size: 0.9em; color: #555; }
    .aiohm-search-result-item .result-meta { font-size: 0.8em; color: #777; margin-top: 5px; }
    
</style>

<script>
    jQuery(document).ready(function($) {
        const defaultPrompt = <?php echo json_encode($default_prompt); ?>;

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
                    action: 'aiohm_test_muse_mode_chat',
                    nonce: $('#aiohm_muse_mode_nonce_field').val(),
                    message: message,
                    settings: { 
                        system_prompt: $('#system_prompt').val(), 
                        temperature: $('#temperature').val(), 
                        assistant_name: $('#assistant_name').val(),
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
                const sanitizedContent = $('<div/>').text(content).html().replace(/\n/g, '<br>');
                const messageHtml = `<div class="aiohm-message aiohm-message-${type}"><div class="aiohm-message-bubble"><div class="aiohm-message-content">${isError ? '⚠️ ' : ''}${sanitizedContent}</div></div></div>`;
                
                this.$messages.append(messageHtml);
                this.$messages.scrollTop(this.$messages[0].scrollHeight);
            },

            showTypingIndicator: function() {
                this.isTyping = true;
                this.$messages.append(`<div class="aiohm-message aiohm-message-bot aiohm-typing-indicator"><div class="aiohm-message-bubble"><div class="aiohm-typing-dots"><span></span><span></span><span></span></div></div></div>`);
                this.$messages.scrollTop(this.$messages[0].scrollHeight);
            },

            hideTypingIndicator: function() {
                this.isTyping = false;
                this.$messages.find('.aiohm-typing-indicator').remove();
            }
        };
        testChat.init();

        function updateLivePreview() {
            $('#aiohm-test-chat .aiohm-chat-title-preview').text($('#assistant_name').val());
        }
        
        $('#muse-mode-settings-form input, #muse-mode-settings-form select, #muse-mode-settings-form textarea').on('input change', updateLivePreview);
        
        $('#temperature').on('input', function() {
            $('.temp-value').text($(this).val());
        });

        $('#reset-prompt-btn').on('click', function(e) {
            e.preventDefault();
            $('#system_prompt').val(defaultPrompt);
            showAdminNotice('Prompt has been reset to the default.', 'success');
        });
        
        $('#save-muse-mode-settings').on('click', function(e) {
            e.preventDefault();
            const $btn = $(this);
            $btn.prop('disabled', true).text('Saving...');

            $.post(ajaxurl, {
                action: 'aiohm_save_muse_mode_settings',
                nonce: $('#aiohm_muse_mode_nonce_field').val(),
                form_data: $('#muse-mode-settings-form').serialize()
            }).done(function(response) {
                showAdminNotice(response.success ? response.data.message : 'Error: ' + (response.data.message || 'Could not save.'), response.success ? 'success' : 'error');
            }).fail(function() {
                showAdminNotice('A server error occurred.', 'error');
            }).always(function() {
                $btn.prop('disabled', false).text('Save Muse Settings');
            });
        });

        function showAdminNotice(message, type = 'success') {
            const $notice = $('#aiohm-admin-notice');
            $notice.removeClass('notice-success notice-error notice-warning').addClass('notice-' + type).addClass('is-dismissible');
            $notice.find('p').html(message);
            $notice.fadeIn();
            setTimeout(function() { $notice.fadeOut(); }, 5000);
        }
        
        $('.aiohm-search-btn').on('click', function() {
            const query = $('.aiohm-search-input').val();
            const $resultsContainer = $('.aiohm-search-results');
            $resultsContainer.html('<span class="spinner is-active"></span>');
            
            $.post(ajaxurl, {
                action: 'aiohm_admin_search_knowledge', // Re-using this action from mirror-mode for context testing
                nonce: $('#aiohm_muse_mode_nonce_field').val(), // Use the correct nonce
                query: query,
                content_type_filter: '' // No filter for this test
            }).done(function(response) {
                $resultsContainer.empty();
                if (response.success && response.data.results.length > 0) {
                    response.data.results.forEach(function(item) {
                        $resultsContainer.append(`<div class="aiohm-search-result-item"><h4>${item.title}</h4><p>${item.excerpt}</p><div class="result-meta">Type: ${item.content_type} | Similarity: ${item.similarity}%</div></div>`);
                    });
                } else {
                     $resultsContainer.html('<div class="aiohm-search-result-item"><p>No results found in the knowledge base for this query.</p></div>');
                }
            }).fail(function() {
                 $resultsContainer.html('<div class="aiohm-search-result-item"><p>Search request failed.</p></div>');
            });
        });
        
        updateLivePreview();
    });
</script>