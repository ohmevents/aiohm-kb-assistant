jQuery(document).ready(function($) {
    'use strict';

    // --- State Management ---
    let currentProjectId = null;
    let currentConversationId = null;

    // --- DOM Elements ---
    const chatInput = $('#chat-input');
    const sendBtn = $('#send-btn');
    const conversationPanel = $('#conversation-panel');
    const projectList = $('.aiohm-pa-project-list');
    const conversationList = $('.aiohm-pa-conversation-list');
    const projectTitle = $('#project-title');
    const loadingIndicator = $('#aiohm-chat-loading');

    // --- Helper Functions ---
    /**
     * Appends a message to the chat panel.
     * @param {string} sender - 'user' or the assistant's name.
     * @param {string} text - The message content.
     */
    function appendMessage(sender, text) {
        const messageClass = sender.toLowerCase() === 'user' ? 'user' : 'assistant';
        const senderName = sender.toLowerCase() === 'user' ? 'You' : sender;
        const messageHTML = `
            <div class="message ${messageClass}">
                <p><strong>${senderName}:</strong> ${text}</p>
            </div>`;
        conversationPanel.append(messageHTML);
        conversationPanel.scrollTop(conversationPanel[0].scrollHeight); // Auto-scroll
    }

    /**
     * Handles AJAX requests.
     * @param {string} action - The WordPress AJAX action hook.
     * @param {object} data - The data to send.
     * @returns {Promise}
     */
    function performAjaxRequest(action, data) {
        return $.ajax({
            url: aiohm_private_chat_params.ajax_url,
            type: 'POST',
            data: {
                action: action,
                nonce: aiohm_private_chat_params.nonce,
                ...data
            },
            beforeSend: function() {
                loadingIndicator.show();
            },
            complete: function() {
                loadingIndicator.hide();
            }
        });
    }

    // --- Core Functionality ---

    /**
     * Loads all projects and conversations for the user.
     */
    function loadHistory() {
        performAjaxRequest('aiohm_load_history', {}).done(function(response) {
            if (response.success) {
                // Populate Projects
                projectList.empty();
                response.data.projects.forEach(proj => {
                    projectList.append(`<a href="#" class="aiohm-pa-list-item" data-id="${proj.id}">${proj.name}</a>`);
                });

                // Populate Conversations
                conversationList.empty();
                response.data.conversations.forEach(convo => {
                    conversationList.append(`<a href="#" class="aiohm-pa-list-item" data-id="${convo.id}">${convo.title}</a>`);
                });
            }
        });
    }

    /**
     * Handles sending a chat message.
     */
    function sendMessage() {
        const message = chatInput.val().trim();
        if (!message || !currentProjectId) {
            return;
        }

        appendMessage('user', message);
        chatInput.val('');

        performAjaxRequest('aiohm_send_message', {
            message: message,
            project_id: currentProjectId,
            conversation_id: currentConversationId
        }).done(function(response) {
            if (response.success) {
                appendMessage('Assistant', response.data.reply);
                if (response.data.conversation_id && !currentConversationId) {
                    currentConversationId = response.data.conversation_id;
                    loadHistory(); // Refresh lists
                }
            } else {
                appendMessage('System', 'Error: Could not get a response.');
            }
        }).fail(function() {
            appendMessage('System', 'Error: AJAX request failed.');
        });
    }

    // --- Event Listeners ---

    // Send message on form submit
    $('#private-chat-form').on('submit', function(e) {
        e.preventDefault();
        sendMessage();
    });

    // Create New Project
    $('#new-project-btn').on('click', function() {
        const projectName = prompt('Enter a name for your new project:', 'New Project');
        if (projectName && projectName.trim() !== '') {
            performAjaxRequest('aiohm_create_project', { name: projectName.trim() }).done(function(response) {
                if (response.success) {
                    alert('Project created!');
                    loadHistory();
                } else {
                    alert('Error: ' + response.data.message);
                }
            });
        }
    });

    // Select a Project
    projectList.on('click', '.aiohm-pa-list-item', function(e) {
        e.preventDefault();
        currentProjectId = $(this).data('id');
        currentConversationId = null; // Start a new conversation
        projectTitle.text($(this).text());
        conversationPanel.html('<div class="message system"><p>New chat started in project: ' + $(this).text() + '</p></div>');
        chatInput.prop('disabled', false);
        sendBtn.prop('disabled', false);
    });

    // --- Research Modal Logic ---
    const researchModal = $('#research-prompt-modal');
    const researchPromptList = $('#research-prompt-list');
    const customResearchPrompt = $('#custom-research-prompt');

    $('#research-online-prompt-btn').on('click', function() {
        if(currentProjectId) {
             researchModal.show();
        } else {
            alert("Please select a project first.");
        }
    });

    $('.aiohm-modal-close').on('click', function() {
        researchModal.hide();
    });

    researchPromptList.on('click', 'li', function() {
        const promptTemplate = $(this).data('prompt');
        chatInput.val(promptTemplate);
        researchModal.hide();
    });

    $('#start-research-btn').on('click', function() {
        const customPrompt = customResearchPrompt.val().trim();
        if (customPrompt) {
            chatInput.val(customPrompt);
            researchModal.hide();
        }
    });

    // --- Initial Load ---
    loadHistory();
});