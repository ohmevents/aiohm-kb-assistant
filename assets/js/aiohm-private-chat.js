document.addEventListener('DOMContentLoaded', function() {

    // --- Element References ---
    const chatHistory = document.getElementById('private-chat-history');
    const chatInput = document.getElementById('private-chat-input');
    const chatForm = document.getElementById('private-chat-form');
    const loadingIndicator = document.getElementById('aiohm-chat-loading');

    const downloadPdfBtn = document.getElementById('download-pdf-btn');
    const researchBtn = document.getElementById('research-online-prompt-btn');
    const activateAudioBtn = document.getElementById('activate-audio-btn');

    // --- Feature 1: Download Chat as PDF ---
    if (downloadPdfBtn) {
        downloadPdfBtn.addEventListener('click', function() {
            if (typeof window.jspdf === 'undefined') {
                alert('Error: The PDF generation library (jsPDF) is not loaded. Please ensure it is enqueued.');
                return;
            }
            if (!chatHistory) {
                alert('Error: Could not find the chat history container.');
                return;
            }

            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            const chatContent = chatHistory.innerText; // Grabs all text content
            const lines = doc.splitTextToSize(chatContent, 180); // 180mm max width for A4

            doc.text(lines, 10, 10);
            doc.save('aiohm-muse-chat-history.pdf');
        });
    }

    // --- Feature 2: Research Online ---
    if (researchBtn && chatInput) {
        researchBtn.addEventListener('click', function() {
            chatInput.value = "Website: ";
            chatInput.focus();
        });
    }

    // --- Feature 3: Activate Audio (Voice-to-Text) ---
    if (activateAudioBtn) {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (!SpeechRecognition) {
            activateAudioBtn.style.display = 'none';
            console.warn('Speech Recognition is not supported in this browser.');
        } else {
            const recognition = new SpeechRecognition();
            recognition.continuous = false;
            recognition.lang = 'en-US';

            activateAudioBtn.addEventListener('click', () => {
                recognition.start();
                activateAudioBtn.classList.add('is-listening');
                activateAudioBtn.disabled = true;
            });

            recognition.onresult = (event) => {
                chatInput.value = event.results[0][0].transcript;
            };

            recognition.onend = () => {
                activateAudioBtn.classList.remove('is-listening');
                activateAudioBtn.disabled = false;
            };

            recognition.onerror = (event) => {
                alert('Speech recognition error: ' + event.error);
                console.error('Speech recognition error:', event.error);
            };
        }
    }

    // --- Core Chat Logic ---
    if (chatForm) {
        chatForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const message = chatInput.value.trim();
            if (message) {
                handleSendMessage(message);
                chatInput.value = '';
            }
        });
    }

    function handleSendMessage(message) {
        let ajaxAction;
        let dataPayload;

        if (message.toLowerCase().startsWith('website:')) {
            const urlToResearch = message.substring(message.indexOf(':') + 1).trim();
            if (!urlToResearch) {
                appendMessageToChat('System', 'Error: Please provide a URL after "Website:".');
                return;
            }
            ajaxAction = 'aiohm_live_research';
            dataPayload = { research_url: urlToResearch };
            appendMessageToChat('User', message);
        } else {
            ajaxAction = 'aiohm_private_chat';
            dataPayload = { prompt: message };
            appendMessageToChat('User', message);
        }

        dataPayload.action = ajaxAction;
        // Ensure you localize this nonce value from PHP
        dataPayload.nonce = aiohm_private_chat_params.nonce; 

        loadingIndicator.style.display = 'block';

        jQuery.ajax({
            type: 'POST',
            url: aiohm_private_chat_params.ajax_url, // Localized ajax url
            data: dataPayload,
            success: function(response) {
                if (response.success) {
                    appendMessageToChat('Assistant', response.data.message);
                } else {
                    appendMessageToChat('System', 'Error: ' + (response.data.message || 'The request failed.'));
                }
            },
            error: function() {
                appendMessageToChat('System', 'An unexpected network error occurred.');
            },
            complete: function() {
                loadingIndicator.style.display = 'none';
            }
        });
    }

    function appendMessageToChat(sender, message) {
        if (!chatHistory) return;
        const messageElement = document.createElement('div');
        messageElement.classList.add('chat-message', `message-from-${sender.toLowerCase()}`);
        
        const senderTag = document.createElement('strong');
        senderTag.textContent = `${sender}: `;
        messageElement.appendChild(senderTag);

        const messageContent = document.createElement('p');
        messageContent.textContent = message;
        messageElement.appendChild(messageContent);
        
        chatHistory.appendChild(messageElement);
        chatHistory.scrollTop = chatHistory.scrollHeight; // Auto-scroll to the bottom
    }
});