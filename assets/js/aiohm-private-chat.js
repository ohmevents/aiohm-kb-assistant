/**
 * AIOHM Private Assistant Frontend Script
 * v1.4.2 - FINAL FIX: Replaces conversational UI with a dedicated view for project creation to guarantee success.
 */
jQuery(document).ready(function($) {
    'use strict';

    // ====================================================================
    // 1. STATE & DOM
    // ====================================================================
    let currentProjectId = null;
    let currentConversationId = null;
    let originalPlaceholder = 'Type your message...';

    const appContainer = $('#aiohm-app-container');
    const chatInput = $('#chat-input');
    const sendBtn = $('#send-btn');
    const conversationPanel = $('#conversation-panel');
    const projectList = $('.aiohm-pa-project-list');
    const conversationList = $('.aiohm-pa-conversation-list');
    const projectTitle = $('#project-title');
    const loadingIndicator = $('#aiohm-chat-loading');
    const notificationBar = $('#aiohm-pa-notification');
    const welcomeInstructions = $('#welcome-instructions');
    const assistantName = aiohm_private_chat_params.assistantName || 'Assistant';

    const newProjectBtn = $('#new-project-btn');
    const newChatBtn = $('#new-chat-btn');
    const addToKbBtn = $('#add-to-kb-btn');
    const sidebarToggleBtn = $('#sidebar-toggle');
    const notesToggleBtn = $('#toggle-notes-btn');
    const closeNotesBtn = $('#close-notes-btn');
    const fullscreenBtn = $('#fullscreen-toggle-btn');


    // ====================================================================
    // 2. HELPER & UI FUNCTIONS
    // ====================================================================

    function showNotification(message, type = 'success') {
        const notificationMessage = notificationBar.find('p');
        notificationMessage.text(message);
        notificationBar.removeClass('success error').addClass(type);
        notificationBar.fadeIn().delay(4000).fadeOut();
    }

    function appendMessage(sender, text) {
        const messageClass = sender.toLowerCase();
        const senderName = sender.toLowerCase() === 'user' ? 'You' : assistantName;
        
        const messageHTML = `
            <div class="message ${messageClass}">
                <p><strong>${senderName}:</strong> ${text}</p>
            </div>`;
        conversationPanel.append(messageHTML);
        conversationPanel.scrollTop(conversationPanel[0].scrollHeight);
    }

    function updateChatUIState() {
        const isProjectSelected = !!currentProjectId;
        const isConversationActive = !!currentConversationId;
        chatInput.prop('disabled', !isProjectSelected);
        sendBtn.prop('disabled', !isProjectSelected);
        chatInput.attr('placeholder', isProjectSelected ? originalPlaceholder : 'Select a project to begin...');
        addToKbBtn.prop('disabled', !isConversationActive);
        if (!isProjectSelected) {
            projectTitle.text('Select a Project');
        }
    }

    function setFullscreen(force = null) {
        const shouldBeFullscreen = force !== null ? force : !appContainer.hasClass('fullscreen-mode');
        appContainer.toggleClass('fullscreen-mode', shouldBeFullscreen);
        $('body').toggleClass('aiohm-fullscreen-body-no-scroll', shouldBeFullscreen);
        const icon = fullscreenBtn.find('.dashicons');
        if (shouldBeFullscreen) {
            fullscreenBtn.attr('title', 'Exit Fullscreen');
            icon.removeClass('dashicons-fullscreen-alt').addClass('dashicons-fullscreen-exit-alt');
        } else {
            fullscreenBtn.attr('title', 'Toggle Fullscreen');
            icon.removeClass('dashicons-fullscreen-exit-alt').addClass('dashicons-fullscreen-alt');
        }
    }


    // ====================================================================
    // 3. CORE & AJAX FUNCTIONALITY
    // ====================================================================

    function performAjaxRequest(action, data) {
        loadingIndicator.show();
        return $.ajax({
            url: aiohm_private_chat_params.ajax_url,
            type: 'POST',
            data: {
                action: action,
                nonce: aiohm_private_chat_params.nonce,
                ...data
            }
        }).always(function() {
            loadingIndicator.hide();
        });
    }

    function loadHistory() {
        return performAjaxRequest('aiohm_load_history', {}).done(function(response) {
            if (response.success) {
                projectList.empty();
                if (response.data.projects && response.data.projects.length > 0) {
                    response.data.projects.forEach(proj => projectList.append(`<a href="#" class="aiohm-pa-list-item" data-id="${proj.id}">${proj.name}</a>`));
                } else {
                    projectList.append('<p class="aiohm-no-items">No projects yet.</p>');
                }

                conversationList.empty();
                if (response.data.conversations && response.data.conversations.length > 0) {
                    response.data.conversations.forEach(convo => conversationList.append(`<a href="#" class="aiohm-pa-list-item" data-id="${convo.id}">${convo.title}</a>`));
                } else {
                    conversationList.append('<p class="aiohm-no-items">No conversations yet.</p>');
                }
            }
        });
    }

    function sendMessage() {
        const message = chatInput.val().trim();
        if (!message) return;
        if (!currentProjectId) {
            showNotification('Please select a project first!', 'error');
            return;
        }
        welcomeInstructions.hide();
        appendMessage('user', message);
        chatInput.val('');
        performAjaxRequest('aiohm_send_message', {
            message: message,
            project_id: currentProjectId,
            conversation_id: currentConversationId
        }).done(function(response) {
            if (response.success) {
                appendMessage(assistantName, response.data.reply);
                if (response.data.conversation_id && !currentConversationId) {
                    currentConversationId = response.data.conversation_id;
                    loadHistory();
                }
            } else {
                appendMessage(assistantName, 'Error: ' + (response.data.message || 'Could not get a response.'));
            }
        }).always(updateChatUIState);
    }
    
    // ====================================================================
    // 4. EVENT LISTENERS & NEW PROJECT VIEW
    // ====================================================================

    /**
     * Replaces the chat panel with the "Create Project" form.
     */
    function displayProjectCreationView() {
        // Disable main chat input while this view is active
        chatInput.prop('disabled', true);
        sendBtn.prop('disabled', true);

        const formHTML = `
            <div id="create-project-view" style="padding: 40px; text-align: center;">
                <h3>Create a New Project</h3>
                <p style="color: var(--pa-text-secondary); margin-top: 5px;">Enter a name below to organize your chats.</p>
                <input type="text" id="new-project-input" placeholder="My Awesome Project" style="width: 100%; max-width: 400px; padding: 10px; margin-top: 20px; margin-bottom: 15px; background-color: var(--pa-bg-darkest); border: 1px solid var(--pa-border-color); color: #fff; border-radius: 5px;">
                <br>
                <button id="create-project-submit" class="aiohm-pa-action-btn" style="padding: 12px 30px;">Create Project</button>
            </div>
        `;
        conversationPanel.html(formHTML);
        $('#new-project-input').focus();
    }

    /**
     * Restores the normal chat view.
     */
    function restoreChatView() {
        conversationPanel.html(''); // Clear the form
        conversationPanel.append(welcomeInstructions); // Put welcome message back
        welcomeInstructions.show();
        updateChatUIState();
    }
    
    // --- Event Listeners ---
    $('#private-chat-form').on('submit', e => { e.preventDefault(); sendMessage(); });
    
    newProjectBtn.on('click', displayProjectCreationView);
    
    // Use event delegation on the static parent for the dynamic button
    conversationPanel.on('click', '#create-project-submit', function() {
        const projectName = $('#new-project-input').val().trim();

        if (!projectName) {
            showNotification('Project name cannot be empty.', 'error');
            return;
        }

        // Show loading indicator inside the button
        $(this).text('Creating...').prop('disabled', true);

        performAjaxRequest('aiohm_create_project', { name: projectName }).done(response => {
            if (response.success && response.data.new_project_id) {
                showNotification(`Project "${projectName}" created!`, 'success');
                restoreChatView();
                // After restoring view, load history and click the new project
                loadHistory().done(function() {
                    const newProjectLink = projectList.find(`.aiohm-pa-list-item[data-id="${response.data.new_project_id}"]`);
                    if (newProjectLink.length) {
                        newProjectLink.trigger('click');
                    }
                });
            } else {
                showNotification('Error: ' + (response.data.message || 'Could not create project.'), 'error');
                $(this).text('Create Project').prop('disabled', false); // Re-enable button on failure
            }
        }).fail(function() {
            showNotification('An unexpected network error occurred.', 'error');
            $(this).text('Create Project').prop('disabled', false); // Re-enable button on failure
        });
    });


    projectList.on('click', '.aiohm-pa-list-item', function(e) {
        e.preventDefault();
        restoreChatView(); // Ensure we're in chat mode when a project is clicked
        $('.aiohm-pa-list-item').removeClass('active');
        $(this).addClass('active');
        currentProjectId = $(this).data('id');
        currentConversationId = null; 
        projectTitle.text($(this).text());
        conversationPanel.html(`<div class="message system"><p>New chat started in project: <strong>${$(this).text()}</strong></p></div>`);
        welcomeInstructions.hide();
        updateChatUIState();
    });

    conversationList.on('click', '.aiohm-pa-list-item', function(e) {
        e.preventDefault();
        restoreChatView();
        $('.aiohm-pa-list-item').removeClass('active');
        $(this).addClass('active');
        currentConversationId = $(this).data('id');
        performAjaxRequest('aiohm_load_conversation', { conversation_id: currentConversationId }).done(response => {
            if (response.success && response.data.messages) {
                conversationPanel.empty();
                welcomeInstructions.hide();
                response.data.messages.forEach(msg => appendMessage(msg.sender, msg.message_content));
                currentProjectId = response.data.project_id;
                projectTitle.text(response.data.project_name || 'Conversation');
            }
        }).always(updateChatUIState);
    });

    newChatBtn.on('click', function() {
        if (!currentProjectId) {
            showNotification('Please select a project first.', 'error');
            return;
        }
        restoreChatView();
        currentConversationId = null;
        conversationList.find('.aiohm-pa-list-item').removeClass('active');
        conversationPanel.html(`<div class="message system"><p>New chat started in current project.</p></div>`);
        welcomeInstructions.hide();
        updateChatUIState();
    });

    sidebarToggleBtn.on('click', () => appContainer.toggleClass('sidebar-open'));
    notesToggleBtn.on('click', () => appContainer.toggleClass('notes-open'));
    closeNotesBtn.on('click', () => appContainer.removeClass('notes-open'));
    fullscreenBtn.on('click', () => setFullscreen());
    notificationBar.on('click', '.close-btn', () => notificationBar.fadeOut());


    // ====================================================================
    // 5. INITIALIZATION
    // ====================================================================
    function initialize() {
        appContainer.addClass('sidebar-open');
        loadHistory();
        updateChatUIState();
        if (aiohm_private_chat_params.startFullscreen) {
            setFullscreen(true);
        }
    }

    initialize();
});