/**
 * AIOHM Private Assistant Frontend Script
 * v1.2.7
 *
 * - Handles all frontend logic for the private assistant, including AJAX communication,
 * UI updates, and state management.
 * - Replaces browser popups with integrated UI elements for a better user experience.
 * - Manages application state to intelligently enable/disable UI controls.
 */
jQuery(document).ready(function($) {
    'use strict';

    // ====================================================================
    // 1. STATE MANAGEMENT
    // ====================================================================
    let currentProjectId = null;
    let currentConversationId = null;

    // ====================================================================
    // 2. DOM ELEMENT CACHING
    // ====================================================================
    const appContainer = $('#aiohm-app-container');
    const chatInput = $('#chat-input');
    const sendBtn = $('#send-btn');
    const conversationPanel = $('#conversation-panel');
    const projectList = $('.aiohm-pa-project-list');
    const conversationList = $('.aiohm-pa-conversation-list');
    const projectTitle = $('#project-title');
    const loadingIndicator = $('#aiohm-chat-loading');
    const notificationBar = $('#aiohm-pa-notification');
    const notificationMessage = $('#aiohm-pa-notification p');
    const welcomeInstructions = $('#welcome-instructions');

    // --- Buttons ---
    const newProjectBtn = $('#new-project-btn');
    const newChatBtn = $('#new-chat-btn');
    const addToKbBtn = $('#add-to-kb-btn');
    const fullscreenBtn = $('#fullscreen-toggle-btn');
    const projectActionsContainer = $('.aiohm-pa-actions');


    // ====================================================================
    // 3. HELPER FUNCTIONS
    // ====================================================================

    /**
     * Displays a slide-in notification message.
     * @param {string} message - The message to display.
     * @param {string} type - 'success' or 'error'.
     */
    function showNotification(message, type = 'success') {
        notificationMessage.text(message);
        notificationBar.removeClass('success error').addClass(type);
        notificationBar.fadeIn().delay(4000).fadeOut(); // Show for 4 seconds
    }

    /**
     * Appends a message to the chat panel.
     * @param {string} sender - 'user', 'assistant', or 'system'.
     * @param {string} text - The message content (HTML is allowed).
     */
    function appendMessage(sender, text) {
        const messageClass = sender.toLowerCase();
        const senderName = sender.toLowerCase() === 'user' ? 'You' : (sender.charAt(0).toUpperCase() + sender.slice(1));
        const messageHTML = `
            <div class="message ${messageClass}">
                <p><strong>${senderName}:</strong> ${text}</p>
            </div>`;
        conversationPanel.append(messageHTML);
        conversationPanel.scrollTop(conversationPanel[0].scrollHeight); // Auto-scroll
    }

    /**
     * Performs a standardized AJAX request.
     * @param {string} action - The WordPress AJAX action.
     * @param {object} data - The data to send.
     * @returns {Promise}
     */
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

    /**
     * Updates the state of UI controls based on the current context.
     * This is crucial for enabling/disabling buttons correctly.
     */
    function updateChatUIState() {
        const isProjectSelected = !!currentProjectId;
        const isConversationActive = !!currentConversationId;

        // Enable chat input and send button only when a project is selected
        chatInput.prop('disabled', !isProjectSelected);
        sendBtn.prop('disabled', !isProjectSelected);

        // Enable "Add to KB" button only when a conversation has been started/loaded
        addToKbBtn.prop('disabled', !isConversationActive);

        if (!isProjectSelected) {
            projectTitle.text('Select a Project');
        }
    }


    // ====================================================================
    // 4. CORE FUNCTIONALITY
    // ====================================================================

    /**
     * Loads the initial project and conversation history.
     */
    function loadHistory() {
        performAjaxRequest('aiohm_load_history', {}).done(function(response) {
            if (response.success) {
                // Populate Projects
                projectList.empty();
                if (response.data.projects && response.data.projects.length > 0) {
                    response.data.projects.forEach(proj => {
                        projectList.append(`<a href="#" class="aiohm-pa-list-item" data-id="${proj.id}">${proj.name}</a>`);
                    });
                } else {
                    projectList.append('<p class="aiohm-no-items">No projects yet.</p>');
                }

                // Populate Conversations
                conversationList.empty();
                if (response.data.conversations && response.data.conversations.length > 0) {
                    response.data.conversations.forEach(convo => {
                        conversationList.append(`<a href="#" class="aiohm-pa-list-item" data-id="${convo.id}">${convo.title}</a>`);
                    });
                } else {
                    conversationList.append('<p class="aiohm-no-items">No conversations yet.</p>');
                }
            } else {
                showNotification('Failed to load history: ' + (response.data.message || 'Unknown error.'), 'error');
            }
        }).fail(function() {
            showNotification('Error loading history. Please try again.', 'error');
        });
    }

    /**
     * Handles sending a chat message from the user.
     */
    function sendMessage() {
        const message = chatInput.val().trim();
        if (!message || !currentProjectId) {
            if (!currentProjectId) {
                showNotification('Please select a project first!', 'error');
            }
            return;
        }

        welcomeInstructions.hide(); // Hide instructions on first message
        appendMessage('user', message);
        chatInput.val('');

        performAjaxRequest('aiohm_send_message', {
            message: message,
            project_id: currentProjectId,
            conversation_id: currentConversationId
        }).done(function(response) {
            if (response.success) {
                appendMessage('assistant', response.data.reply);
                // If this was the first message, a new conversation was created.
                if (response.data.conversation_id && !currentConversationId) {
                    currentConversationId = response.data.conversation_id;
                    loadHistory(); // Refresh lists to show the new conversation.
                }
            } else {
                appendMessage('System', 'Error: ' + (response.data.message || 'Could not get a response.'));
            }
        }).fail(function() {
            appendMessage('System', 'Error: The request to the server failed. Please check your connection.');
        }).always(function() {
            updateChatUIState(); // Re-check button states
        });
    }

    /**
     * Displays an inline form for creating a new project.
     * REPLACES the old `prompt()` popup.
     */
    function showNewProjectForm() {
        // Hide the main buttons
        newProjectBtn.hide();
        newChatBtn.hide();

        // Show the form
        const formHTML = `
            <div id="new-project-form" class="aiohm-pa-inline-form">
                <input type="text" id="new-project-name" placeholder="Enter project name..." />
                <button id="create-project-confirm" class="aiohm-pa-form-btn-confirm">Create</button>
                <button id="create-project-cancel" class="aiohm-pa-form-btn-cancel">Cancel</button>
            </div>
        `;
        projectActionsContainer.append(formHTML);
        $('#new-project-name').focus();
    }

    /**
     * Hides the inline new project form and restores the buttons.
     */
    function hideNewProjectForm() {
        $('#new-project-form').remove();
        newProjectBtn.show();
        newChatBtn.show();
    }


    // ====================================================================
    // 5. EVENT LISTENERS
    // ====================================================================

    // --- Main Chat Form ---
    $('#private-chat-form').on('submit', function(e) {
        e.preventDefault();
        sendMessage();
    });

    // --- New Project Creation (No Popup) ---
    newProjectBtn.on('click', showNewProjectForm);

    projectActionsContainer.on('click', '#create-project-cancel', hideNewProjectForm);

    projectActionsContainer.on('click', '#create-project-confirm', function() {
        const projectName = $('#new-project-name').val().trim();
        if (!projectName) {
            showNotification('Project name cannot be empty.', 'error');
            return;
        }

        performAjaxRequest('aiohm_create_project', { name: projectName }).done(function(response) {
            if (response.success) {
                showNotification(`Project "${projectName}" created successfully!`, 'success');
                loadHistory(); // Refresh the project list
            } else {
                showNotification('Error: ' + (response.data.message || 'Could not create project.'), 'error');
            }
        }).fail(function() {
            showNotification('An unexpected error occurred while creating the project.', 'error');
        }).always(function() {
            hideNewProjectForm();
        });
    });

    // --- Sidebar Item Selection ---
    projectList.on('click', '.aiohm-pa-list-item', function(e) {
        e.preventDefault();
        $('.aiohm-pa-list-item').removeClass('active');
        $(this).addClass('active');

        currentProjectId = $(this).data('id');
        currentConversationId = null; // Always start a new chat when a project is clicked
        projectTitle.text($(this).text());
        conversationPanel.html('<div class="message system"><p>New chat started in project: <strong>' + $(this).text() + '</strong></p></div>');
        welcomeInstructions.hide();
        updateChatUIState();
    });

    conversationList.on('click', '.aiohm-pa-list-item', function(e) {
        e.preventDefault();
        $('.aiohm-pa-list-item').removeClass('active');
        $(this).addClass('active');

        currentConversationId = $(this).data('id');

        performAjaxRequest('aiohm_load_conversation', { conversation_id: currentConversationId }).done(function(response) {
            if (response.success && response.data.messages) {
                conversationPanel.empty();
                welcomeInstructions.hide();
                response.data.messages.forEach(msg => {
                    appendMessage(msg.sender, msg.message_content);
                });
                currentProjectId = response.data.project_id; // Important: update the project context
                projectTitle.text(response.data.project_name || 'Conversation'); // Update title
            } else {
                showNotification('Error loading conversation: ' + (response.data.message || 'Unknown error.'), 'error');
            }
        }).fail(function() {
            showNotification('Failed to load conversation history.', 'error');
        }).always(function() {
            updateChatUIState();
        });
    });

    // --- Header Buttons ---

    // ** New Chat Button **
    newChatBtn.on('click', function() {
        if (!currentProjectId) {
            showNotification('Please select a project before starting a new chat.', 'error');
            return;
        }
        currentConversationId = null;
        conversationPanel.html('<div class="message system"><p>New chat started.</p></div>');
        welcomeInstructions.hide();
        updateChatUIState();
    });

    // ** Fullscreen Toggle Button **
    fullscreenBtn.on('click', function() {
        appContainer.toggleClass('fullscreen-mode');
        // This class on the body is what allows the CSS to hide the admin bar
        $('body').toggleClass('aiohm-fullscreen-body-no-scroll');

        // Update tooltip/icon if desired
        if (appContainer.hasClass('fullscreen-mode')) {
            $(this).attr('title', 'Exit Fullscreen');
            $(this).find('.dashicons').removeClass('dashicons-fullscreen-alt').addClass('dashicons-fullscreen-exit-alt');
        } else {
            $(this).attr('title', 'Toggle Fullscreen');
            $(this).find('.dashicons').removeClass('dashicons-fullscreen-exit-alt').addClass('dashicons-fullscreen-alt');
        }
    });

    // --- Notification Close Button ---
    notificationBar.on('click', '.close-btn', function() {
        notificationBar.fadeOut();
    });


    // ====================================================================
    // 6. INITIALIZATION
    // ====================================================================
    loadHistory();
    updateChatUIState(); // Set initial button states
});