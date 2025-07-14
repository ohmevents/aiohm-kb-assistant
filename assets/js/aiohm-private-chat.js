/**
 * AIOHM Private Assistant Frontend Script
 * v1.4.5 - Implements "Research Online" prompt injection.
 */
jQuery(document).ready(function($) {
    'use strict';

    // ====================================================================
    // 1. STATE & DOM
    // ====================================================================
    let currentProjectId = null;
    let currentConversationId = null;
    let originalPlaceholder = 'Type your message...';
    let noteSaveTimer = null;

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

    const notesInput = $('#aiohm-pa-notes-textarea');
    const addToKbBtnNotes = $('#add-note-to-kb-btn');
    const notesStatus = $('#aiohm-notes-status');

    const newProjectBtn = $('#new-project-btn');
    const newChatBtn = $('#new-chat-btn');
    const addToKbBtnChat = $('#add-to-kb-btn');
    const sidebarToggleBtn = $('#sidebar-toggle');
    const notesToggleBtn = $('#toggle-notes-btn');
    const closeNotesBtn = $('#close-notes-btn');
    const fullscreenBtn = $('#fullscreen-toggle-btn');
    
    const researchBtn = $('#research-online-prompt-btn');
    // The modal elements are no longer needed for the new prompt-injection workflow.
    // const researchModal = $('#research-url-modal');
    // const researchUrlInput = $('#research-url-input');
    // const researchUrlSubmit = $('#research-url-submit');
    // const researchUrlStatus = $('#research-url-status');
    // const researchModalClose = $('#close-research-modal');


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
        const messageClass = sender.toLowerCase() === 'user' ? 'user' : 'assistant';
        const senderName = sender.toLowerCase() === 'user' ? 'You' : assistantName;
        
        const sanitizedText = $('<div/>').text(text).html().replace(/\n/g, '<br>');

        const messageHTML = `
            <div class="message ${messageClass}">
                <p><strong>${senderName}:</strong> ${sanitizedText}</p>
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
        addToKbBtnChat.prop('disabled', !isConversationActive);
        notesInput.prop('disabled', !isProjectSelected);
        addToKbBtnNotes.prop('disabled', !isProjectSelected);

        if (!isProjectSelected) {
            projectTitle.text('Select a Project');
            notesInput.val('');
            notesStatus.text('');
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

        performAjaxRequest('aiohm_private_chat', {
            message: message,
            project_id: currentProjectId,
            conversation_id: currentConversationId
        }).done(function(response) {
            if (response.success) {
                appendMessage(assistantName, response.data.reply);
                if (response.data.conversation_id) {
                    currentConversationId = response.data.conversation_id;
                    // Only reload history if it's a new conversation
                    if (!conversationList.find(`.aiohm-pa-list-item[data-id="${currentConversationId}"]`).length) {
                        loadHistory();
                    }
                }
            } else {
                appendMessage(assistantName, 'Error: ' + (response.data.message || 'Could not get a response.'));
            }
        }).always(updateChatUIState);
    }

    // ====================================================================
    // 4. NOTES & KB FUNCTIONALITY
    // ====================================================================
    
    function saveNotes() {
        if (!currentProjectId) return;
        const noteContent = notesInput.val();
        notesStatus.text('Saving...').show();

        performAjaxRequest('aiohm_save_project_notes', {
            project_id: currentProjectId,
            note_content: noteContent
        }, false).done(function(response) {
             if(response.success) {
                notesStatus.text('Saved');
             } else {
                notesStatus.text('Error');
             }
        });
    }

    function loadNotes(projectId) {
        performAjaxRequest('aiohm_load_project_notes', { project_id: projectId }).done(function(response) {
            if (response.success) {
                notesInput.val(response.data.note_content || '');
                notesStatus.text('');
            }
        });
    }

    function addNoteToKb() {
        const noteContent = notesInput.val().trim();
        if(!noteContent) {
            showNotification('Note is empty.', 'error');
            return;
        }

        performAjaxRequest('aiohm_add_brand_soul_to_kb', {
            questions: [{ 
                question: 'Note from Project: ' + projectTitle.text(), 
                answer: noteContent 
            }]
        }).done(function(response) {
            if(response.success) {
                showNotification('Note added to your private knowledge base.', 'success');
            } else {
                showNotification('Error: ' + (response.data.message || 'Could not add note.'), 'error');
            }
        });
    }


    // ====================================================================
    // 5. EVENT LISTENERS & VIEWS
    // ====================================================================

    function displayProjectCreationView() {
        chatInput.prop('disabled', true);
        sendBtn.prop('disabled', true);

        const formHTML = `
            <div id="create-project-view">
                <h3>Create a New Project</h3>
                <p>Enter a name below to organize your chats.</p>
                <input type="text" id="new-project-input" placeholder="My Awesome Project">
                <br>
                <button id="create-project-submit" class="aiohm-pa-action-btn aiohm-ohm-green-btn">Create Project</button>
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
    
    chatInput.on('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

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
        if (currentProjectId) {
            saveNotes();
        }
        restoreChatView();
        welcomeInstructions.hide();
        $('.aiohm-pa-list-item').removeClass('active');
        $(this).addClass('active');
        currentProjectId = $(this).data('id');
        currentConversationId = null; 
        projectTitle.text($(this).text());
        conversationPanel.html(`<div class="message assistant"><p>New chat started in project: <strong>${$(this).text()}</strong></p></div>`);
        loadNotes(currentProjectId);
        updateChatUIState();
    });

    conversationList.on('click', '.aiohm-pa-list-item', function(e) {
        e.preventDefault();
        restoreChatView();
        welcomeInstructions.hide();
        $('.aiohm-pa-list-item').removeClass('active');
        $(this).addClass('active');
        currentConversationId = $(this).data('id');
        performAjaxRequest('aiohm_load_conversation', { conversation_id: currentConversationId }).done(response => {
            if (response.success && response.data.messages) {
                conversationPanel.empty();
                response.data.messages.forEach(msg => appendMessage(msg.sender, msg.message_content));
                currentProjectId = response.data.project_id;
                projectTitle.text(response.data.project_name || 'Conversation');
                projectList.find('.aiohm-pa-list-item').removeClass('active');
                projectList.find(`.aiohm-pa-list-item[data-id="${currentProjectId}"]`).addClass('active');
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
        welcomeInstructions.hide();
        currentConversationId = null;
        conversationList.find('.aiohm-pa-list-item').removeClass('active');
        conversationPanel.html(`<div class="message assistant"><p>New chat started in current project.</p></div>`);
        updateChatUIState();
    });

    notesInput.on('keyup', function() {
        clearTimeout(noteSaveTimer);
        notesStatus.text('Typing...');
        if (currentProjectId) {
            noteSaveTimer = setTimeout(saveNotes, 1500);
        }
    });

    addToKbBtnNotes.on('click', function() {
        addNoteToKb();
    });

    projectList.on('click', '.delete-project', function(e) {
        e.stopPropagation();
        const projectId = $(this).data('id');
        if (confirm('Are you sure you want to delete this project and all its conversations? This cannot be undone.')) {
            performAjaxRequest('aiohm_delete_project', { project_id: projectId }).done(function(response) {
                if (response.success) {
                    showNotification('Project deleted.', 'success');
                    if(currentProjectId === projectId) {
                        currentProjectId = null;
                        currentConversationId = null;
                        restoreChatView();
                    }
                    loadHistory();
                } else {
                    showNotification('Error: ' + (response.data.message || 'Could not delete project.'), 'error');
                }
            }).always(updateChatUIState);
        }
    });

    conversationList.on('click', '.delete-conversation', function(e) {
        e.stopPropagation();
        const conversationId = $(this).data('id');
        if (confirm('Are you sure you want to delete this conversation? This cannot be undone.')) {
            performAjaxRequest('aiohm_delete_conversation', { conversation_id: conversationId }).done(function(response) {
                if (response.success) {
                    showNotification('Conversation deleted.', 'success');
                     if(currentConversationId === conversationId) {
                        currentConversationId = null;
                        restoreChatView();
                    }
                    loadHistory();
                } else {
                    showNotification('Error: ' + (response.data.message || 'Could not delete conversation.'), 'error');
                }
            }).always(updateChatUIState);
        }
    });

    sidebarToggleBtn.on('click', () => appContainer.toggleClass('sidebar-open'));
    notesToggleBtn.on('click', () => appContainer.toggleClass('notes-open'));
    closeNotesBtn.on('click', () => appContainer.removeClass('notes-open'));
    fullscreenBtn.on('click', () => setFullscreen());
    notificationBar.on('click', '.close-btn', () => notificationBar.fadeOut());
    
    // --- Start of Changes ---
    // This is the new, improved functionality for the Research Online button.
    researchBtn.on('click', function() {
        if (!currentProjectId) {
            showNotification('Please select a project first to start your research.', 'error');
            return;
        }

        // Define the prompt that will be injected into the chat input.
        const researchPrompt = "Please research the following URL and provide a summary of its key points: [PASTE URL HERE]";
        
        // Set the value of the chat input and focus on it for the user.
        chatInput.val(researchPrompt);
        chatInput.focus();
        
        // Optional: Move the cursor to the position where the user should paste the URL.
        const promptLength = chatInput.val().length;
        chatInput[0].setSelectionRange(promptLength - 16, promptLength - 1);
    });
    // --- End of Changes ---

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