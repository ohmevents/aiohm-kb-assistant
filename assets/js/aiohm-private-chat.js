/**
 * AIOHM Private Assistant Scripts - v1.1.10
 *
 * This file handles all client-side functionality for the modern private assistant,
 * including the hybrid embedded/fullscreen mode, conversation management, AJAX chat,
 * and all other UI interactions.
 */
jQuery(document).ready(function($) {
    // Exit if the main container for our modern chat doesn't exist.
    if ($('.aiohm-private-assistant-container.modern').length === 0) {
        return;
    }

    // --- Element Selectors ---
    const container = $('#aiohm-app-container');
    const chatInput = $('#chat-input');
    const sendBtn = $('#send-btn');
    const conversationPanel = $('#conversation-panel');
    const newProjectBtn = $('.aiohm-pa-new-project-btn');
    const conversationList = $('.aiohm-pa-conversation-list');
    const sidebarToggle = $('.aiohm-pa-sidebar-toggle');
    const fullscreenToggle = $('.aiohm-pa-fullscreen-toggle');
    const menuHeaders = $('.aiohm-pa-menu-header');
    const addToKbBtn = $('#add-to-kb-btn');

    // --- State Management ---
    let activeConversationId = null;
    let isLoading = false;

    // --- Core Functions ---

    /**
     * Sends the user's message to the backend via AJAX.
     */
    function sendMessage() {
        const messageText = chatInput.val().trim();
        if (!messageText || isLoading) return;

        isLoading = true;

        if (!activeConversationId) {
            conversationPanel.empty();
        }

        appendMessage(messageText, 'user');
        chatInput.val('').trigger('input');
        sendBtn.prop('disabled', true);
        
        appendMessage('...', 'ai', true);

        $.post(aiohm_private_chat_params.ajax_url, {
            _ajax_nonce: aiohm_private_chat_params.nonce,
            action: 'aiohm_private_assistant_chat',
            message: messageText,
            conversation_id: activeConversationId
        })
        .done(function(response) {
            $('.message.typing').remove();
            if (response.success) {
                appendMessage(response.data.reply, 'ai');
                if (!activeConversationId) {
                    activeConversationId = response.data.conversation_id;
                    loadConversations(true);
                }
            } else {
                appendMessage(response.data.message || aiohm_private_chat_params.strings.error, 'error');
            }
        })
        .fail(function() {
             $('.message.typing').remove();
             appendMessage(aiohm_private_chat_params.strings.error, 'error');
        })
        .always(function() {
            isLoading = false;
            updateAddToKbButtonState();
        });
    }

    /**
     * Appends a new message to the chat panel.
     */
    function appendMessage(content, type, isTyping = false) {
        let messageClass = isTyping ? 'message ai typing' : `message ${type}`;
        if (type === 'error') {
            messageClass = 'message system';
            content = `⚠️ ${content}`;
        }
        
        const sanitizedContent = $('<div/>').text(content).html().replace(/\n/g, '<br>');
        const messageHtml = `<div class="${messageClass}" data-sender="${type}">${isTyping ? '<div class="aiohm-typing-dots"><span></span><span></span><span></span></div>' : sanitizedContent}</div>`;
        conversationPanel.prepend(messageHtml);
    }

    /**
     * Resets the UI for a new conversation.
     */
    function startNewConversation() {
        activeConversationId = null;
        conversationPanel.empty();
        chatInput.val('').css('height', 'auto');
        sendBtn.prop('disabled', true);
        conversationList.find('.conversation-item').removeClass('active');
        updateAddToKbButtonState();
    }
    
    /**
     * Fetches and renders the list of past conversations.
     */
    function loadConversations(activateLatest = false) {
        if (isLoading) return;
        isLoading = true;
        conversationList.html('<span class="spinner is-active" style="margin: 20px auto; display: block;"></span>');

        $.post(aiohm_private_chat_params.ajax_url, {
            action: 'aiohm_get_conversations',
            nonce: aiohm_private_chat_params.nonce,
        }).done(function(response) {
            conversationList.empty();
            if (response.success && response.data.length > 0) {
                response.data.forEach(chat => {
                    const item = $(`<button class="conversation-item" data-id="${chat.id}">${esc_html(chat.title)}</button>`);
                    conversationList.append(item);
                });
                if (activateLatest && activeConversationId) {
                    conversationList.find(`.conversation-item[data-id="${activeConversationId}"]`).addClass('active');
                }
            } else {
                conversationList.html('<p class="aiohm-pa-coming-soon">No dialogue history.</p>');
            }
        }).always(() => { isLoading = false; });
    }

    /**
     * Fetches and displays the messages for a specific conversation.
     */
    function loadConversationHistory(id) {
        if (isLoading) return;
        isLoading = true;
        activeConversationId = id;
        conversationPanel.html('<span class="spinner is-active" style="margin: auto;"></span>');
        
        conversationList.find('.conversation-item').removeClass('active');
        conversationList.find(`.conversation-item[data-id="${id}"]`).addClass('active');

        $.post(aiohm_private_chat_params.ajax_url, {
            action: 'aiohm_get_conversation_history',
            nonce: aiohm_private_chat_params.nonce,
            conversation_id: id
        }).done(function(response) {
            conversationPanel.empty();
            if (response.success && response.data.length > 0) {
                response.data.forEach(msg => {
                    appendMessage(msg.content, msg.sender);
                });
            } else if (!response.success) {
                appendMessage(response.data.message || 'Could not load history.', 'error');
            }
        }).always(() => {
            isLoading = false;
            updateAddToKbButtonState();
        });
    }

    /**
     * Handles the "Add Chat to KB" functionality.
     */
    function addChatToKb() {
        if (!activeConversationId || isLoading) return;

        const messages = [];
        // Since we prepend, we need to get messages and reverse them for chronological order
        conversationPanel.find('.message:not(.typing)').each(function() {
            messages.push({
                sender: $(this).data('sender'),
                content: $(this).text()
            });
        });

        if (messages.length === 0) {
            alert('There is no conversation to save.');
            return;
        }

        const btn = $('#add-to-kb-btn');
        const originalHtml = btn.html();
        btn.prop('disabled', true).html('<span class="spinner is-active"></span> Saving...');
        
        $.post(aiohm_private_chat_params.ajax_url, {
            action: 'aiohm_add_chat_to_kb',
            nonce: aiohm_private_chat_params.nonce,
            conversation_id: activeConversationId,
            messages: messages.reverse() // Reverse to get user -> ai order
        }).done(function(response) {
            alert(response.success ? response.data.message : 'Error: ' + response.data.message);
        }).fail(function() {
            alert('A server error occurred.');
        }).always(function() {
            btn.prop('disabled', false).html(originalHtml);
        });
    }

    function updateAddToKbButtonState() {
        addToKbBtn.prop('disabled', !activeConversationId);
    }
    
    function esc_html(str) {
        return $('<div />').text(str).html();
    }

    // --- Event Handlers ---
    sendBtn.on('click', sendMessage);
    newProjectBtn.on('click', startNewConversation);
    sidebarToggle.on('click', () => container.toggleClass('sidebar-open'));
    fullscreenToggle.on('click', function() {
        container.toggleClass('is-fullscreen');
        $('body').toggleClass('aiohm-assistant-fullscreen-active');
        const icon = $(this).find('.dashicons');
        if (container.hasClass('is-fullscreen')) {
            icon.removeClass('dashicons-editor-expand').addClass('dashicons-editor-contract');
            $(this).attr('title', 'Exit Fullscreen');
        } else {
            icon.removeClass('dashicons-editor-contract').addClass('dashicons-editor-expand');
            $(this).attr('title', 'Toggle Fullscreen');
        }
    });

    chatInput.on('keypress', function(e) {
        if (e.which === 13 && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    }).on('input', function() {
        sendBtn.prop('disabled', $(this).val().trim().length === 0);
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });

    conversationList.on('click', '.conversation-item', function() {
        const id = $(this).data('id');
        if (id !== activeConversationId) {
            loadConversationHistory(id);
        }
    });
    
    menuHeaders.on('click', function() {
        $(this).toggleClass('active');
        $(this).next('.aiohm-pa-menu-content').slideToggle(200);
    });

    addToKbBtn.on('click', addChatToKb);

    // --- Initial Load ---
    startNewConversation();
    loadConversations();
});