jQuery(document).ready(function($) {
    // Check if the main container exists before running any code
    if ($('.aiohm-private-assistant-container').length === 0) {
        return;
    }

    const chatInput = $('#chat-input');
    const sendBtn = $('#send-btn');
    const conversationPanel = $('#conversation-panel');
    const welcomeScreen = $('.aiohm-pa-welcome-screen');
    const newChatBtn = $('.aiohm-pa-new-chat-btn');
    const conversationList = $('.aiohm-pa-conversation-list');
    
    let activeConversationId = null;
    let conversationCounter = 0; // Simple counter for demo purposes

    /**
     * Sends a message to the server via AJAX.
     */
    function sendMessage() {
        const messageText = chatInput.val().trim();
        if (!messageText) return;

        // Hide welcome screen and show chat panel if this is the first message
        if (welcomeScreen.is(':visible')) {
            welcomeScreen.hide();
            conversationPanel.show();
        }

        appendMessage(messageText, 'user');
        chatInput.val('');
        sendBtn.prop('disabled', true);
        
        // Show typing indicator while waiting for response
        appendMessage('...', 'ai', true);

        // AJAX request to the backend
        $.post(aiohm_private_chat_params.ajax_url, {
            _ajax_nonce: aiohm_private_chat_params.nonce,
            action: 'aiohm_private_assistant_chat',
            message: messageText,
            conversation_id: activeConversationId
        })
        .done(function(response) {
            $('.message.typing').remove(); // Remove typing indicator
            if (response.success) {
                appendMessage(response.data.reply, 'ai');
                
                // If this was a new chat, update the UI
                if (!activeConversationId) {
                    activeConversationId = response.data.conversation_id;
                    loadConversations(); // Refresh list to show the new one
                } else {
                    const currentItem = conversationList.find('.conversation-item[data-id="' + activeConversationId + '"]');
                    if (currentItem.text().includes(aiohm_private_chat_params.strings.new_chat)) {
                        loadConversations(); // Refresh to get the new title from the first message
                    }
                }
            } else {
                appendMessage(response.data.message || aiohm_private_chat_params.strings.error, 'error');
            }
        })
        .fail(function() {
             $('.message.typing').remove();
             appendMessage(aiohm_private_chat_params.strings.error, 'error');
        });
    }

    /**
     * Appends a message or typing indicator to the chat panel.
     */
    function appendMessage(content, type, isTyping = false) {
        let avatarIconClass = type === 'user' ? 'dashicons-admin-users' : 'dashicons-format-chat';
        let messageClass = isTyping ? 'message typing' : `message ${type}`;
        if (type === 'error') {
            avatarIconClass = 'dashicons-warning';
            messageClass = 'message ai error'; // Show errors as if from the assistant
        }

        const sanitizedContent = $('<div/>').text(content).html().replace(/\n/g, '<br>');

        const messageHtml = `
            <div class="${messageClass}">
                <div class="message-avatar"><span class="dashicons ${avatarIconClass}"></span></div>
                <div class="message-bubble">${isTyping ? '<div class="aiohm-typing-dots"><span></span><span></span><span></span></div>' : sanitizedContent}</div>
            </div>`;
        conversationPanel.append(messageHtml);
        conversationPanel.scrollTop(conversationPanel[0].scrollHeight);
    }
    
    /**
     * Resets the UI to start a new conversation.
     */
    function startNewConversation() {
        activeConversationId = null;
        conversationPanel.empty().hide();
        welcomeScreen.show();
        chatInput.val('').css('height', 'auto');
        sendBtn.prop('disabled', true);
        conversationList.find('.conversation-item').removeClass('active');
    }

    /**
     * Loads the list of conversations.
     * NOTE: This is a placeholder. A real implementation would fetch this from the server.
     */
    function loadConversations() {
        // Clear the list before reloading
        // conversationList.empty(); 
        
        // Placeholder logic:
        if (activeConversationId && conversationList.find('.conversation-item[data-id="' + activeConversationId + '"]').length === 0) {
            conversationCounter++;
            const newChatItem = $(`<button class="conversation-item" data-id="${activeConversationId}">${aiohm_private_chat_params.strings.new_chat} ${conversationCounter}</button>`);
            conversationList.prepend(newChatItem);
            switchConversation(activeConversationId);
        }
    }
    
    /**
     * Switches the view to a selected conversation.
     * NOTE: This is a placeholder. A real implementation would fetch the chat history.
     */
    function switchConversation(id) {
        activeConversationId = id;
        conversationList.find('.conversation-item').removeClass('active');
        conversationList.find('.conversation-item[data-id="' + id + '"]').addClass('active');
        
        // In a real app, you would make an AJAX call to get the chat history for this ID.
        // For now, we'll just clear the panel and show it.
        conversationPanel.empty().show();
        welcomeScreen.hide();
        appendMessage('Switched to conversation. History would load here.', 'system'); // System message example
    }


    // --- Event Handlers ---

    sendBtn.on('click', sendMessage);
    newChatBtn.on('click', startNewConversation);

    chatInput.on('keypress', function(e) {
        if (e.which === 13 && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    }).on('input', function() {
        sendBtn.prop('disabled', $(this).val().trim().length === 0);
        // Auto-resize textarea
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });

    // Handle suggestion card clicks
    $('.suggestion-card').on('click', function() {
        const prompt = $(this).data('prompt');
        chatInput.val(prompt).focus();
        sendMessage();
    });
    
    // Handle switching conversations (delegated event)
    conversationList.on('click', '.conversation-item', function() {
        const conversationId = $(this).data('id');
        if (conversationId !== activeConversationId) {
            switchConversation(conversationId);
        }
    });

});