document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('aiohm-chat-input');
    const sendBtn = document.getElementById('aiohm-chat-send');
    const messages = document.getElementById('aiohm-chat-messages');

    function appendMessage(text, sender = 'user') {
        const msg = document.createElement('div');
        msg.className = 'aiohm-message aiohm-' + sender;
        msg.textContent = text;
        messages.appendChild(msg);
        messages.scrollTop = messages.scrollHeight;
    }

    function sendMessage() {
        const query = input.value.trim();
        if (!query) return;

        appendMessage(query, 'user');
        input.value = '';
        appendMessage('...', 'bot'); // Placeholder loading

        fetch(aiohmChatAjax.ajax_url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'aiohm_chat_send',
                nonce: aiohmChatAjax.nonce,
                query: query
            })
        })
        .then(response => response.json())
        .then(data => {
            // Remove loading dots
            const loading = messages.querySelector('.aiohm-message.aiohm-bot:last-child');
            if (loading && loading.textContent === '...') loading.remove();

            if (data.success && data.data.reply) {
                appendMessage(data.data.reply, 'bot');
            } else {
                appendMessage('Something went wrong. Try again.', 'bot');
            }
        })
        .catch(() => {
            appendMessage('Network error.', 'bot');
        });
    }

    sendBtn.addEventListener('click', sendMessage);
    input.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') sendMessage();
    });
});
