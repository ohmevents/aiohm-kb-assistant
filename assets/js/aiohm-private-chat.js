/**
 * AIOHM Private Assistant Scripts - v1.2.0 (Projects Update)
 *
 * This file handles all client-side functionality for the modern private assistant,
 * including project and conversation management, AJAX chat, and all UI interactions.
 */
jQuery(document).ready(function($) {
    if ($('.aiohm-private-assistant-container.modern').length === 0) {
        return;
    }

    // --- Element Selectors ---
    const container = $('#aiohm-app-container');
    const chatInput = $('#chat-input');
    const sendBtn = $('#send-btn');
    const conversationPanel = $('#conversation-panel');
    const projectList = $('.aiohm-pa-project-list');
    const conversationList = $('.aiohm-pa-conversation-list');
    const newProjectBtn = $('#new-project-btn');
    const newChatBtn = $('#new-chat-btn');
    const addToKbBtn = $('#add-to-kb-btn');
    const sidebarToggle = $('#sidebar-toggle');
    const projectTitle = $('#project-title');
    const menuHeaders = $('.aiohm-pa-menu-header');

    // --- State Management ---
    let activeProjectId = null;
    let activeConversationId = null;
    let isLoading = false;

    // --- Core Functions ---

    /**
     * Sends the user's message to the backend.
     */
    function sendMessage() {
        const messageText = chatInput.val().trim();
        if (!messageText || isLoading || !activeProjectId) {
            if (!activeProjectId) {
                alert("Please select a project before starting a chat.");
            }
            return;
        }

        isLoading = true;

        if (!activeConversationId) {
            conversationPanel.empty(); // Clear "Select a conversation" message
        }

        appendMessage(messageText, 'user');
        chatInput.val('').trigger('input');
        sendBtn.prop('disabled', true);
        appendMessage('...', 'ai', true); // Typing indicator

        $.post(aiohm_private_chat_params.ajax_url, {
            _ajax_nonce: aiohm_private_chat_params.nonce,
            action: 'aiohm_private_assistant_chat',
            message: messageText,
            project_id: activeProjectId,
            conversation_id: activeConversationId
        })
        .done(function(response) {
            $('.message.typing').remove();
            if (response.success) {
                appendMessage(response.data.reply, 'ai');
                if (!activeConversationId) {
                    activeConversationId = response.data.conversation_id;
                    loadConversations(activeProjectId, true); // Refresh list and activate new chat
                }
            } else {
                appendMessage(response.data.answer || 'An unknown error occurred.', 'error');
            }
        })
        .fail(function() {
             $('.message.typing').remove();
             appendMessage('A server error occurred. Please try again.', 'error');
        })
        .always(function() {
            isLoading = false;
            updateAddToKbButtonState();
        });
    }

    /**
     * Appends a message to the chat panel.
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
     * Fetches and renders the list of projects.
     */
    function loadProjects(activateLatest = false) {
        isLoading = true;
        projectList.html('<span class="spinner is-active" style="margin: 20px auto; display: block;"></span>');
        
        $.post(aiohm_private_chat_params.ajax_url, {
            action: 'aiohm_get_projects',
            nonce: aiohm_private_chat_params.nonce,
        }).done(function(response) {
            projectList.empty();
            if (response.success && response.data.length > 0) {
                response.data.forEach(proj => {
                    const item = $(`<button class="conversation-item project-item" data-id="${proj.id}">${esc_html(proj.project_name)}</button>`);
                    projectList.append(item);
                });
                if (activateLatest && response.data.length > 0) {
                    // Activate the first project in the list
                    setActiveProject(response.data[0].id, response.data[0].project_name);
                }
            } else {
                projectList.html('<p class="aiohm-pa-coming-soon">No projects yet.</p>');
            }
        }).always(() => { isLoading = false; });
    }
    
    /**
     * Creates a new project.
     */
    function createNewProject() {
        const projectName = prompt("Enter the name for your new project:");
        if (!projectName || projectName.trim() === '') return;
        
        isLoading = true;
        $.post(aiohm_private_chat_params.ajax_url, {
            action: 'aiohm_create_project',
            nonce: aiohm_private_chat_params.nonce,
            project_name: projectName.trim()
        }).done(function(response) {
            if (response.success) {
                loadProjects(false); // Reload project list
                setActiveProject(response.data.id, response.data.project_name); // Activate the new project
            } else {
                alert('Error: ' + (response.data.message || 'Could not create project.'));
            }
        }).always(() => { isLoading = false; });
    }

    /**
     * Sets the currently active project and loads its conversations.
     */
    function setActiveProject(projectId, projectName) {
        activeProjectId = projectId;
        activeConversationId = null; // Reset conversation
        
        projectTitle.text(projectName);
        projectList.find('.project-item').removeClass('active');
        projectList.find(`.project-item[data-id="${projectId}"]`).addClass('active');
        
        loadConversations(projectId);
        conversationPanel.html('<p class="message system">Select a conversation or start a new one.</p>');
        updateAddToKbButtonState();
        chatInput.prop('disabled', false); // Enable input now that a project is selected
    }

    /**
     * Fetches conversations for a given project.
     */
    function loadConversations(projectId, activateLatest = false) {
        if (!projectId) {
            conversationList.html('<p class="aiohm-pa-coming-soon">Select a project.</p>');
            return;
        }
        isLoading = true;
        conversationList.html('<span class="spinner is-active" style="margin: 20px auto; display: block;"></span>');

        $.post(aiohm_private_chat_params.ajax_url, {
            action: 'aiohm_get_conversations',
            nonce: aiohm_private_chat_params.nonce,
            project_id: projectId
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
                conversationList.html('<p class="aiohm-pa-coming-soon">No chats in this project.</p>');
            }
        }).always(() => { isLoading = false; });
    }

    /**
     * Fetches and displays messages for a specific conversation.
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
                    // Note: We use .append() now to maintain chronological order from the server
                    conversationPanel.append(`<div class="message ${msg.sender}" data-sender="${msg.sender}">${esc_html(msg.content).replace(/\n/g, '<br>')}</div>`);
                });
                conversationPanel.scrollTop(conversationPanel[0].scrollHeight); // Scroll to bottom
            } else if (!response.success) {
                appendMessage(response.data.message || 'Could not load history.', 'error');
            }
        }).always(() => {
            isLoading = false;
            updateAddToKbButtonState();
        });
    }
    
    /**
     * Resets the UI for a new chat within the current project.
     */
    function startNewChat() {
        if (!activeProjectId) {
            alert("Please select a project first.");
            return;
        }
        activeConversationId = null;
        conversationPanel.html('<p class="message system">Start a new conversation.</p>');
        chatInput.val('').css('height', 'auto');
        sendBtn.prop('disabled', true);
        conversationList.find('.conversation-item').removeClass('active');
        updateAddToKbButtonState();
    }


    /**
     * Handles adding the current chat to the knowledge base.
     */
    function addChatToKb() {
        if (!activeConversationId || isLoading) return;

        const messages = [];
        conversationPanel.find('.message:not(.typing)').each(function() {
            messages.push({
                sender: $(this).data('sender'),
                content: $(this).text()
            });
        });

        if (messages.length === 0) return;

        const btn = $('#add-to-kb-btn');
        const originalHtml = btn.html();
        btn.prop('disabled', true).html('<span class="spinner is-active"></span> Saving...');
        
        $.post(aiohm_private_chat_params.ajax_url, {
            action: 'aiohm_add_chat_to_kb',
            nonce: aiohm_private_chat_params.nonce,
            conversation_id: activeConversationId,
            messages: messages // No longer need to reverse, backend handles it
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
    newProjectBtn.on('click', createNewProject);
    newChatBtn.on('click', startNewChat);
    sidebarToggle.on('click', () => container.toggleClass('sidebar-open'));
    addToKbBtn.on('click', addChatToKb);

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

    projectList.on('click', '.project-item', function() {
        const id = $(this).data('id');
        const name = $(this).text();
        if (id !== activeProjectId) {
            setActiveProject(id, name);
        }
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

    // --- Initial Load ---
    function initializeChat() {
        projectTitle.text('Select a Project');
        conversationPanel.html('<p class="message system">Please select a project to begin.</p>');
        chatInput.prop('disabled', true);
        sendBtn.prop('disabled', true);
        loadProjects(true); // Load projects and activate the first one automatically
    }

    initializeChat();
});