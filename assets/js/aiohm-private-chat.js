document.addEventListener('DOMContentLoaded', function() {

    // --- Element References ---
    const appContainer = document.getElementById('aiohm-app-container');
    const sidebarToggleBtn = document.getElementById('sidebar-toggle');
    const menuHeaders = document.querySelectorAll('.aiohm-pa-menu-header');
    
    const chatHistory = document.getElementById('conversation-panel');
    const chatInput = document.getElementById('chat-input');
    const chatForm = document.getElementById('private-chat-form');
    const sendBtn = document.getElementById('send-btn');
    const loadingIndicator = document.getElementById('aiohm-chat-loading');

    // New button references
    const downloadPdfBtn = document.getElementById('download-pdf-btn');
    const researchBtn = document.getElementById('research-online-prompt-btn');
    const activateAudioBtn = document.getElementById('activate-audio-btn');

    // --- START: Restored Sidebar & Menu Logic ---
    if (sidebarToggleBtn && appContainer) {
        sidebarToggleBtn.addEventListener('click', function() {
            appContainer.classList.toggle('sidebar-open');
        });
    }

    menuHeaders.forEach(header => {
        header.addEventListener('click', function() {
            this.classList.toggle('active');
            const content = document.getElementById(this.dataset.target);
            if (content) {
                // Simple slide toggle effect with jQuery for reliability
                jQuery(content).slideToggle(200);
            }
        });
    });
    // --- END: Restored Sidebar & Menu Logic ---

    // --- Feature 1: Download Chat as PDF ---
    if (downloadPdfBtn) {
        downloadPdfBtn.addEventListener('click', function() {
            if (typeof window.jspdf === 'undefined') {
                alert('Error: The PDF generation library (jsPDF) is not loaded.');
                return;
            }
            if (!chatHistory) return;

            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            const chatContent = chatHistory.innerText;
            const lines = doc.splitTextToSize(chatContent, 180);

            doc.text(lines, 10, 10);
            doc.save('aiohm-chat-history.pdf');
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
        } else {
            const recognition = new SpeechRecognition();
            recognition.continuous = false;
            recognition.lang = 'en-US';

            activateAudioBtn.addEventListener('click', () => {
                recognition.start();
                activateAudioBtn.classList.add('is-listening');
            });

            recognition.onresult = (event) => {
                chatInput.value = event.results[0][0].transcript;
            };

            recognition.onend = () => {
                activateAudioBtn.classList.remove('is-listening');
            };

            recognition.onerror = (event) => {
                alert('Speech recognition error: ' + event.error);
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
        dataPayload.nonce = aiohm_private_chat_params.nonce;

        loadingIndicator.style.display = 'block';
        sendBtn.disabled = true;

        jQuery.ajax({
            type: 'POST',
            url: aiohm_private_chat_params.ajax_url,
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
                sendBtn.disabled = false;
            }
        });
    }

    function appendMessageToChat(sender, message) {
        if (!chatHistory) return;
        const messageElement = document.createElement('div');
        messageElement.classList.add('message', sender.toLowerCase());
        
        messageElement.textContent = message; 
        
        chatHistory.appendChild(messageElement);
        chatHistory.scrollTop = chatHistory.scrollHeight;
    }
});