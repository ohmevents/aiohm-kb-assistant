/**
 * AIOHM Knowledge Assistant - Admin Mode Scripts
 *
 * This file handles the dynamic functionality for the Mirror Mode and Muse Mode
 * admin pages, including live previews, AJAX saving, and test chat features.
 * It relies on a localized data object `aiohm_admin_modes_data` passed from PHP.
 */

jQuery(document).ready(function($) {
    // Exit if the required configuration object is not present
    if (typeof aiohm_admin_modes_data === 'undefined') {
        return;
    }

    const config = aiohm_admin_modes_data;
    let noticeTimer;

    /**
     * Displays a dismissible admin notice at the top of the page.
     * @param {string} message - The message to display. Can contain HTML.
     * @param {string} type - The notice type ('success', 'error', 'warning').
     */
    function showAdminNotice(message, type = 'success') {
        clearTimeout(noticeTimer);
        const $notice = $('#aiohm-admin-notice');
        $notice.removeClass('notice-success notice-error notice-warning').addClass('notice-' + type).addClass('is-dismissible');
        $notice.find('p').html(message);
        $notice.fadeIn();

        // Focus on the notice for accessibility and scroll to top
        setTimeout(() => {
            $notice.focus();
            $('html, body').animate({
                scrollTop: $notice.offset().top - 100
            }, 300);
        }, 100);

        // If the notice doesn't contain a button, auto-hide it.
        if (!message.includes('<button')) {
            noticeTimer = setTimeout(() => $notice.fadeOut(), 5000);
        }
    }

    /**
     * Determines if a hex color is "dark" to decide text color.
     * @param {string} hex - The hex color string (e.g., '#RRGGBB').
     * @returns {boolean} - True if the color is dark.
     */
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

    /**
     * Updates the live preview elements based on form input changes.
     */
    function updateLivePreview() {
        // Update colors and text for the test chat preview
        const primaryColor = $('#primary_color').val() || '#1f5014';
        const textColor = $('#text_color').val() || '#ffffff';
        // Use business_name for Mirror Mode, assistant_name for Muse Mode
        const assistantName = config.mode === 'mirror' ? 
            ($('#business_name').val() || 'Live Preview') : 
            ($('#assistant_name').val() || 'Muse');
        const botAvatarUrl = $('#ai_avatar').val();
        const defaultAvatarUrl = config.pluginUrl + 'assets/images/OHM-logo.png';

        $('#aiohm-test-chat .aiohm-chat-header').css({ 'background-color': primaryColor, 'color': textColor });
        $('#aiohm-test-chat .aiohm-message-user .aiohm-message-bubble').css('background-color', primaryColor);
        $('#aiohm-test-chat .aiohm-chat-title-preview').text(assistantName);

        // Use default avatar if no custom avatar is set
        const avatarToUse = botAvatarUrl || defaultAvatarUrl;
        $('.aiohm-avatar-preview').attr('src', avatarToUse).show();
        
        // Update footer preview for Mirror Mode
        if (config.mode === 'mirror') {
            const footerPreview = $('.aiohm-chat-footer-preview');
            const meetingUrl = $('#meeting_button_url').val().trim();
            const brandingHtml = `<div class="aiohm-chat-footer-branding"><span>Powered by <strong>AIOHM</strong></span></div>`;
            const buttonHtml = `<a href="#" class="aiohm-chat-footer-button" style="background-color: ${primaryColor}; color: ${textColor};" onclick="event.preventDefault();">Book a Meeting</a>`;

            footerPreview.html(meetingUrl ? buttonHtml : brandingHtml);
        }
    }

    /**
     * Test Chat Functionality
     */
    const testChat = {
        $container: $('#aiohm-test-chat'),
        $messages: $('#aiohm-test-chat .aiohm-chat-messages'),
        $input: $('#aiohm-test-chat .aiohm-chat-input'),
        $sendBtn: $('#aiohm-test-chat .aiohm-chat-send-btn'),
        isTyping: false,
        currentRequest: null,
        
        init: function() {
            this.bindEvents();
        },
        
        bindEvents: function() {
            this.$input.on('keydown', e => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); this.sendMessage(); }});
            this.$sendBtn.on('click', () => this.sendMessage());
            this.$input.on('input', e => this.$sendBtn.prop('disabled', $(e.target).val().trim().length === 0));
        },
        
        sendMessage: function() {
            const message = this.$input.val().trim();
            if (!message || this.isTyping) return;
            
            this.addMessage(message, 'user');
            this.$input.val('').trigger('input');
            this.showTypingIndicator();

            if (this.currentRequest) this.currentRequest.abort();

            const settingsPayload = {
                system_prompt: $('#' + (config.promptTextareaId || 'qa_system_message')).val(),
                temperature: $('#temperature, #qa_temperature').val(),
                assistant_name: config.mode === 'mirror' ? $('#business_name').val() : $('#assistant_name').val(),
                ai_model: $('#ai_model_selector').val(),
            };
            
            this.currentRequest = $.post(config.ajax_url, {
                action: config.testChatAction,
                [config.nonceFieldId]: $('#' + config.nonceFieldId).val(),
                message: message,
                settings: settingsPayload
            }).done(response => {
                const answer = response.success ? response.data.answer : "Sorry, an error occurred.";
                this.addMessage(answer, 'bot');
            }).fail(() => {
                this.addMessage("Server error. Please try again.", 'bot', true);
            }).always(() => {
                this.hideTypingIndicator();
                this.currentRequest = null;
            });
        },
        
        addMessage: function(content, type, isError = false) {
            const sanitizedContent = $('<div/>').text(content).html().replace(/\n/g, '<br>');
            const errorIcon = isError ? '⚠️ ' : '';
            const botAvatarUrl = $('#ai_avatar').val();
            let avatarHtml = (type === 'bot' && botAvatarUrl) ? `<img src="${botAvatarUrl}" alt="AI Avatar" class="aiohm-avatar-preview">` : '';

            const messageHtml = `
                <div class="aiohm-message aiohm-message-${type}">
                    ${avatarHtml ? `<div class="aiohm-message-avatar">${avatarHtml}</div>` : ''}
                    <div class="aiohm-message-bubble"><div class="aiohm-message-content">${errorIcon}${sanitizedContent}</div></div>
                </div>`;
            this.$messages.append(messageHtml).scrollTop(this.$messages[0].scrollHeight);
        },
        
        showTypingIndicator: function() {
            this.isTyping = true;
            const typingHtml = `
                <div class="aiohm-message aiohm-message-bot aiohm-typing-indicator">
                    <div class="aiohm-message-bubble"><div class="aiohm-typing-dots"><span></span><span></span><span></span></div></div>
                </div>`;
            this.$messages.append(typingHtml).scrollTop(this.$messages[0].scrollHeight);
        },
        
        hideTypingIndicator: function() {
            this.isTyping = false;
            this.$messages.find('.aiohm-typing-indicator').remove();
        }
    };
    testChat.init();

    // --- General Event Handlers ---
    $('#' + config.formId).on('input change', 'input, select, textarea', updateLivePreview);
    $('#qa_temperature, #temperature').on('input', function() { $('.temp-value').text($(this).val()); });

    // --- Page-Specific Logic ---
    if (config.mode === 'mirror') {
        // Media uploader for Mirror Mode avatar
        wp.media && $('#upload_ai_avatar_button').on('click', function(e) {
            e.preventDefault();
            const mediaUploader = wp.media({ title: 'Choose AI Avatar', button: { text: 'Choose Avatar' }, multiple: false });
            mediaUploader.on('select', () => {
                const attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#ai_avatar').val(attachment.url).trigger('input');
            });
            mediaUploader.open();
        });
        
        // Q&A Generator for Mirror Mode
        $('#generate-q-and-a').on('click', function() {
            const $btn = $(this);
            const $results = $('#q-and-a-results');
            $btn.prop('disabled', true).text('Generating...');
            $results.html('<span class="spinner is-active"></span>');
            
            $.post(config.ajax_url, { action: 'aiohm_generate_mirror_mode_qa', nonce: $('#' + config.nonceFieldId).val() })
             .done(response => $results.html(response.success ? `<p><strong class="q-title">Q:</strong> ${response.data.qa_pair.question}</p><p><strong class="q-title">A:</strong> ${response.data.qa_pair.answer}</p>` : `<p style="color:red;">${response.data.message || 'Failed.'}</p>`))
             .fail(() => $results.html('<p style="color:red;">Server error.</p>'))
             .always(() => $btn.prop('disabled', false).text('Generate Sample Q&A'));
        });
        
        // Text formatting removed - was causing corruption
    }
    
    if (config.mode === 'muse') {
        // Archetype change handler for Muse Mode
        $('#brand_archetype').on('change', function() {
            const selected = $(this).val();
            const promptText = selected && config.archetypePrompts[selected] ? config.archetypePrompts[selected] : config.defaultPrompt;
            $('#' + config.promptTextareaId).val(promptText);
            
            // Trigger input event to ensure proper formatting is applied
            $('#' + config.promptTextareaId).trigger('input');
        });
        
        // Text formatting removed - was causing corruption
    }

    // --- Shared Handlers for Buttons ---
    $('#reset-prompt-btn').on('click', function(e) {
        e.preventDefault();
        showAdminNotice(
            'Are you sure you want to reset the prompt? <button id="confirm-prompt-reset" class="button button-small" style="margin-left: 10px;">Confirm</button>',
            'warning'
        );
    });

    $(document).on('click', '#confirm-prompt-reset', function() {
        const promptText = (config.mode === 'muse' && config.archetypePrompts && config.archetypePrompts[$('#brand_archetype').val()])
            ? config.archetypePrompts[$('#brand_archetype').val()]
            : config.defaultPrompt;
        $('#' + (config.promptTextareaId || 'qa_system_message')).val(promptText);
        $('#aiohm-admin-notice').fadeOut();
        showAdminNotice('Prompt has been reset to default.', 'success');
    });

    $('#' + config.saveButtonId).on('click', function(e) {
        e.preventDefault();
        const $btn = $(this);
        const originalText = $btn.text();
        $btn.prop('disabled', true).text('Saving...');
        
        const formData = $('#' + config.formId).serialize();
        
        
        $.post(config.ajax_url, {
            action: config.saveAction,
            form_data: formData
        }).done(response => {
            showAdminNotice(response.success ? response.data.message : 'Error: ' + (response.data.message || 'Could not save.'), response.success ? 'success' : 'error');
        }).fail((xhr, status, error) => {
            showAdminNotice('A server error occurred.', 'error');
        }).always(() => $btn.prop('disabled', false).text(originalText));
    });

    // KB Search Handler
    $('.aiohm-search-btn').on('click', function() {
        const query = $('.aiohm-search-input').val();
        const filter = $('#aiohm-test-search-filter').val() || '';
        const $results = $('.aiohm-search-results');
        if (!query) return;

        $results.html('<span class="spinner is-active"></span>');
        
        $.post(config.ajax_url, {
            action: 'aiohm_admin_search_knowledge',
            nonce: $('#' + config.nonceFieldId).val(),
            query: query,
            content_type_filter: filter
        }).done(function(response) {
            $results.empty();
            if (response.success && response.data.results.length > 0) {
                response.data.results.forEach(item => {
                    $results.append(`<div class="aiohm-search-result-item"><h4><a href="${item.url}" target="_blank">${item.title}</a></h4><p>${item.excerpt}</p><div class="result-meta">Type: ${item.content_type} | Similarity: ${item.similarity}%</div></div>`);
                });
            } else {
                 $results.html('<div class="aiohm-search-result-item"><p>No results found.</p></div>');
            }
        }).fail(() => $results.html('<div class="aiohm-search-result-item"><p>Search request failed.</p></div>'));
    });

    // --- Initial State ---
    updateLivePreview();
});