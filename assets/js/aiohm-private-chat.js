/**
 * AIOHM Private Assistant Frontend Script
 * v1.5.0 - Adds auto-saving notes and deletion of projects/conversations.
 */
jQuery(document).ready(function($) {
    'use strict';

    // ====================================================================
    // 1. STATE & DOM
    // ====================================================================
    let currentProjectId = null;
    let currentConversationId = null;
    let originalPlaceholder = 'Type your message...';
    let noteSaveTimer = null; // Timer for auto-saving notes

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

    // MODIFICATION: Get Notes DOM elements
    const notesInput = $('#aiohm-pa-notes-textarea');
    const saveNoteBtn = $('#aiohm-pa-save-note-btn');

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

        // MODIFICATION: Enable/disable notes based on project selection
        notesInput.prop('disabled', !isProjectSelected);
        saveNoteBtn.prop('disabled', !isProjectSelected);

        if (!isProjectSelected) {
            projectTitle.text('Select a Project');
            notesInput.val(''); // Clear notes if no project is selected
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

    function performAjaxRequest(action, data, showLoading = true) {
        if (showLoading) {
            loadingIndicator.show();
        }
        return $.ajax({
            url: aiohm_private_chat_params.ajax_url,
            type: 'POST',
            data: {
                action: action,
                nonce: aiohm_private_chat_params.nonce,
                ...data
            }
        }).always(function() {
            if (showLoading) {
                loadingIndicator.hide();
            }
        });
    }

    function loadHistory() {
        return performAjaxRequest('aiohm_load_history', {}).done(function(response) {
            if (response.success) {
                projectList.empty();
                if (response.data.projects && response.data.projects.length > 0) {
                    // MODIFICATION: Add delete icon to each project
                    response.data.projects.forEach(proj => {
                        const projectHTML = `
                            <div class="aiohm-pa-list-item-wrapper">
                                <a href="#" class="aiohm-pa-list-item" data-id="${proj.id}">${proj.name}</a>
                                <span class="delete-icon delete-project" data-id="${proj.id}" title="Delete Project">&times;</span>
                            </div>`;
                        projectList.append(projectHTML);
                    });
                } else {
                    projectList.append('<p class="aiohm-no-items">No projects yet.</p>');
                }

                conversationList.empty();
                if (response.data.conversations && response.data.conversations.length > 0) {
                    // MODIFICATION: Add delete icon to each conversation
                    response.data.conversations.forEach(convo => {
                        const conversationHTML = `
                            <div class="aiohm-pa-list-item-wrapper">
                                <a href="#" class="aiohm-pa-list-item" data-id="${convo.id}">${convo.title}</a>
                                <span class="delete-icon delete-conversation" data-id="${convo.id}" title="Delete Conversation">&times;</span>
                            </div>`;
                        conversationList.append(conversationHTML);
                    });
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
                    loadHistory(); // Refresh to show the new conversation
                }
            } else {
                appendMessage(assistantName, 'Error: ' + (response.data.message || 'Could not get a response.'));
            }
        }).always(updateChatUIState);
    }

    // ====================================================================
    // 4. NEW FEATURE FUNCTIONS (NOTES & DELETION)
    // ====================================================================

    /**
     * MODIFICATION: Saves the current content of the notes textarea for a specific project.
     * @param {number} projectId The ID of the project to save notes for.
     */
    function saveNotes(projectId) {
        const noteContent = notesInput.val();
        performAjaxRequest('aiohm_save_project_notes', {
            project_id: projectId,
            note_content: noteContent
        }, false).done(function(response) {
             if(response.success) {
                console.log('Notes saved for project ' + projectId);
             }
        });
    }

    /**
     * MODIFICATION: Loads and displays the notes for a specific project.
     * @param {number} projectId The ID of the project to load notes for.
     */
    function loadNotes(projectId) {
        performAjaxRequest('aiohm_load_project_notes', { project_id: projectId }).done(function(response) {
            if (response.success) {
                notesInput.val(response.data.note_content || '');
            } else {
                notesInput.val(''); // Clear notes on failure
            }
        });
    }

    
    // ====================================================================
    // 5. EVENT LISTENERS & NEW PROJECT VIEW
    // ====================================================================

    function displayProjectCreationView() {
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

    function restoreChatView() {
        conversationPanel.html('');
        conversationPanel.append(welcomeInstructions);
        welcomeInstructions.show();
        updateChatUIState();
    }
    
    // --- Event Listeners ---
    $('#private-chat-form').on('submit', e => { e.preventDefault(); sendMessage(); });
    
    newProjectBtn.on('click', displayProjectCreationView);
    
    conversationPanel.on('click', '#create-project-submit', function() {
        const projectName = $('#new-project-input').val().trim();

        if (!projectName) {
            showNotification('Project name cannot be empty.', 'error');
            return;
        }

        $(this).text('Creating...').prop('disabled', true);

        performAjaxRequest('aiohm_create_project', { name: projectName }).done(response => {
            if (response.success && response.data.new_project_id) {
                showNotification(`Project "${projectName}" created!`, 'success');
                restoreChatView();
                loadHistory().done(function() {
                    const newProjectLink = projectList.find(`.aiohm-pa-list-item[data-id="${response.data.new_project_id}"]`);
                    if (newProjectLink.length) {
                        newProjectLink.trigger('click');
                    }
                });
            } else {
                showNotification('Error: ' + (response.data.message || 'Could not create project.'), 'error');
                $(this).text('Create Project').prop('disabled', false);
            }
        }).fail(function() {
            showNotification('An unexpected network error occurred.', 'error');
            $(this).text('Create Project').prop('disabled', false);
        });
    });

    projectList.on('click', '.aiohm-pa-list-item', function(e) {
        e.preventDefault();

        // MODIFICATION: Auto-save notes for the previous project before switching
        if (currentProjectId) {
            saveNotes(currentProjectId);
        }

        restoreChatView();
        $('.aiohm-pa-list-item').removeClass('active');
        $(this).addClass('active');
        currentProjectId = $(this).data('id');
        currentConversationId = null; 
        projectTitle.text($(this).text());
        conversationPanel.html(`<div class="message system"><p>New chat started in project: <strong>${$(this).text()}</strong></p></div>`);
        welcomeInstructions.hide();

        // MODIFICATION: Load notes for the newly selected project
        loadNotes(currentProjectId);
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
                currentProjectId = response.data.project_id; // Ensure project context is also loaded
                projectTitle.text(response.data.project_name || 'Conversation');
                
                // Highlight the parent project
                projectList.find('.aiohm-pa-list-item').removeClass('active');
                projectList.find(`.aiohm-pa-list-item[data-id="${currentProjectId}"]`).addClass('active');
                
                // Load notes for the conversation's project
                loadNotes(currentProjectId);
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

    // MODIFICATION: Auto-save notes when the user stops typing
    notesInput.on('keyup', function() {
        clearTimeout(noteSaveTimer);
        if (currentProjectId) {
            noteSaveTimer = setTimeout(() => saveNotes(currentProjectId), 1500); // Auto-save after 1.5s of inactivity
        }
    });

    // MODIFICATION: Handle project deletion
    projectList.on('click', '.delete-project', function(e) {
        e.stopPropagation(); // Prevent the project click event from firing
        const projectId = $(this).data('id');
        if (confirm('Are you sure you want to delete this project and all its conversations? This cannot be undone.')) {
            performAjaxRequest('aiohm_delete_project', { project_id: projectId }).done(function(response) {
                if (response.success) {
                    showNotification('Project deleted.', 'success');
                    if(currentProjectId === projectId) {
                        currentProjectId = null;
                        currentConversationId = null;
                        restoreChatView();
                        updateChatUIState();
                    }
                    loadHistory();
                } else {
                    showNotification('Error: ' + (response.data.message || 'Could not delete project.'), 'error');
                }
            });
        }
    });

    // MODIFICATION: Handle conversation deletion
    conversationList.on('click', '.delete-conversation', function(e) {
        e.stopPropagation(); // Prevent the conversation click event from firing
        const conversationId = $(this).data('id');
        if (confirm('Are you sure you want to delete this conversation? This cannot be undone.')) {
            performAjaxRequest('aiohm_delete_conversation', { conversation_id: conversationId }).done(function(response) {
                if (response.success) {
                    showNotification('Conversation deleted.', 'success');
                     if(currentConversationId === conversationId) {
                        currentConversationId = null;
                        restoreChatView();
                        updateChatUIState();
                    }
                    loadHistory();
                } else {
                    showNotification('Error: ' + (response.data.message || 'Could not delete conversation.'), 'error');
                }
            });
        }
    });

    sidebarToggleBtn.on('click', () => appContainer.toggleClass('sidebar-open'));
    notesToggleBtn.on('click', () => appContainer.toggleClass('notes-open'));
    closeNotesBtn.on('click', () => appContainer.removeClass('notes-open'));
    fullscreenBtn.on('click', () => setFullscreen());
    notificationBar.on('click', '.close-btn', () => notificationBar.fadeOut());


    // ====================================================================
    // 6. INITIALIZATION
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