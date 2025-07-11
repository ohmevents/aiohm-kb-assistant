jQuery(document).ready(function($) {
    const chatInput = $('#chat-input');
    const sendBtn = $('#send-btn');
    const conversationPanel = $('#conversation-panel');

    sendBtn.on('click', function() {
        const message = chatInput.val().trim();
        if (message) {
            // Display user message
            conversationPanel.append('<div class="message user">' + message + '</div>');
            chatInput.val('');

            // Scroll to the bottom
            conversationPanel.scrollTop(conversationPanel[0].scrollHeight);

            // Send to server
            $.post(aiohm_private_chat_params.ajax_url, {
                _ajax_nonce: aiohm_private_chat_params.nonce,
                action: 'aiohm_private_chat',
                message: message
            }, function(response) {
                if (response.success) {
                    // Display AI response
                    conversationPanel.append('<div class="message ai">' + response.data.reply + '</div>');
                    // Scroll to the bottom
                    conversationPanel.scrollTop(conversationPanel[0].scrollHeight);
                }
            });
        }
    });

    // Optional: Send on Enter key press
    chatInput.on('keypress', function(e) {
        if (e.which === 13 && !e.shiftKey) {
            e.preventDefault();
            sendBtn.click();
        }
    });
});