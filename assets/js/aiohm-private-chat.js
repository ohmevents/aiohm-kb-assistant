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
        const messageClass = sender.toLowerCase() === 'user' ? 'user' : 'assistant';
        const senderName = sender.toLowerCase() === 'user' ? 'You' : assistantName;
        
        // Enhanced formatting for AI responses
        let formattedText = text;
        if (messageClass === 'assistant') {
            // Convert markdown-like formatting to HTML
            formattedText = text
                // Bold text: **text** -> <strong>text</strong>
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                // Bullet points: - item -> <li>item</li>
                .replace(/^- (.+)$/gm, '<li>$1</li>')
                // Numbered lists: 1. item -> <ol><li>item</li></ol>
                .replace(/^\d+\. (.+)$/gm, '<li>$1</li>')
                // Line breaks for better readability
                .replace(/\n\n/g, '</p><p>')
                // Tables: | cell | cell | -> proper table HTML (basic)
                .replace(/\|(.+)\|/g, function(match, content) {
                    const cells = content.split('|').map(cell => `<td>${cell.trim()}</td>`).join('');
                    return `<tr>${cells}</tr>`;
                });
            
            // Wrap lists in proper HTML
            if (formattedText.includes('<li>')) {
                // Handle bullet points
                formattedText = formattedText.replace(/(<li>(?:(?!<li>).)*<\/li>)/gs, function(match) {
                    return '<ul>' + match + '</ul>';
                });
                // Handle numbered lists (need to check for numbered pattern)
                if (/^\d+\./.test(text)) {
                    formattedText = formattedText.replace(/<ul>/g, '<ol>').replace(/<\/ul>/g, '</ol>');
                }
            }
            
            // Wrap tables
            if (formattedText.includes('<tr>')) {
                formattedText = '<table class="aiohm-response-table">' + formattedText + '</table>';
            }
        }
        
        const messageHTML = `
            <div class="message ${messageClass}">
                <div class="message-content">
                    <strong>${senderName}:</strong> 
                    <div class="message-text">${formattedText}</div>
                </div>
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
        notesInput.prop('disabled', !isProjectSelected);
        saveNoteBtn.prop('disabled', !isProjectSelected);

        if (!isProjectSelected) {
            projectTitle.text('Select a Project');
            notesInput.val('');
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

    /**
     * MY MISTAKE WAS HERE. This function is now fixed.
     * It correctly uses the 'action' and 'nonce' parameters passed to it,
     * so that all AJAX calls work, not just the chat.
     */
    function performAjaxRequest(action, data, showLoading = true) {
        if (showLoading) {
            loadingIndicator.show();
        }
        return $.ajax({
            url: aiohm_private_chat_params.ajax_url,
            type: 'POST',
            data: {
                action: action, // Use the action passed into the function
                nonce: aiohm_private_chat_params.nonce, // The key must be 'nonce'
                ...data
            }
        }).always(function() {
            if (showLoading) {
                loadingIndicator.hide();
            }
        });
    }

    function loadHistory() {
        // This function will now work correctly because performAjaxRequest is fixed.
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

        // The action is 'aiohm_private_chat' to match our fix in core-init.php
        performAjaxRequest('aiohm_private_chat', {
            message: message,
            project_id: currentProjectId,
            conversation_id: currentConversationId
        }).done(function(response) {
            if (response.success) {
                // The PHP backend sends 'reply', so we use that here.
                appendMessage(assistantName, response.data.reply);
                if (response.data.conversation_id) {
                    // This correctly saves the conversation ID for the next message.
                    currentConversationId = response.data.conversation_id;
                    // We also refresh the history to show the new chat entry
                    loadHistory();
                }
            } else {
                appendMessage(assistantName, 'Error: ' + (response.data.message || 'Could not get a response.'));
            }
        }).always(updateChatUIState);
    }

    // ====================================================================
    // 4. NEW FEATURE FUNCTIONS (NOTES & DELETION)
    // ====================================================================

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

    function loadNotes(projectId) {
        performAjaxRequest('aiohm_load_project_notes', { project_id: projectId }).done(function(response) {
            if (response.success) {
                notesInput.val(response.data.note_content || '');
            } else {
                notesInput.val('');
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
    
    // Handle Enter key to send message (Shift+Enter for new line)
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
        
        // This will now work correctly because performAjaxRequest is fixed.
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
        loadNotes(currentProjectId);
        updateChatUIState();
    });

    conversationList.on('click', '.aiohm-pa-list-item', function(e) {
        e.preventDefault();
        
        // Auto-save current project notes before switching conversations
        if (currentProjectId) {
            saveNotes(currentProjectId);
        }
        
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
        
        // Create a new conversation in the database
        performAjaxRequest('aiohm_create_conversation', {
            project_id: currentProjectId,
            title: 'New Chat'
        }).done(function(response) {
            if (response.success) {
                restoreChatView();
                currentConversationId = response.data.conversation_id;
                conversationList.find('.aiohm-pa-list-item').removeClass('active');
                conversationPanel.html(`<div class="message system"><p>New chat started in current project.</p></div>`);
                welcomeInstructions.hide();
                updateChatUIState();
                // Refresh the conversation list to show the new conversation
                loadHistory();
            } else {
                showNotification('Error creating new chat: ' + (response.data.message || 'Unknown error'), 'error');
            }
        }).fail(function() {
            showNotification('Failed to create new chat. Please try again.', 'error');
        });
    });

    notesInput.on('keyup', function() {
        clearTimeout(noteSaveTimer);
        if (currentProjectId) {
            noteSaveTimer = setTimeout(() => saveNotes(currentProjectId), 1500);
        }
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
                        updateChatUIState();
                    }
                    loadHistory();
                } else {
                    showNotification('Error: ' + (response.data.message || 'Could not delete project.'), 'error');
                }
            });
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

    // File upload button handler
    $('#upload-file-btn').on('click', function() {
        if (!currentProjectId) {
            showNotification('Please select a project first.', 'error');
            return;
        }
        $('#file-upload-input').click();
    });

    // File input change handler
    $('#file-upload-input').on('change', function() {
        const files = this.files;
        if (files.length > 0) {
            uploadFiles(files);
        }
    });

    // Research online button handler
    $('#research-online-prompt-btn').on('click', function() {
        if (!currentProjectId) {
            showNotification('Please select a project first.', 'error');
            return;
        }
        insertResearchPrompt();
    });

    // Download PDF button handler
    $('#download-pdf-btn').on('click', function() {
        if (!currentConversationId) {
            showNotification('Please start a conversation before downloading PDF.', 'error');
            return;
        }
        
        const $btn = $(this);
        const originalTitle = $btn.attr('title');
        $btn.prop('disabled', true).attr('title', 'Generating PDF...');
        
        // Create a form and submit it to trigger PDF download
        const form = $('<form>', {
            method: 'POST',
            action: aiohm_private_chat_params.ajax_url,
            target: '_blank'
        });
        
        form.append($('<input>', {type: 'hidden', name: 'action', value: 'aiohm_download_conversation_pdf'}));
        form.append($('<input>', {type: 'hidden', name: 'nonce', value: aiohm_private_chat_params.nonce}));
        form.append($('<input>', {type: 'hidden', name: 'conversation_id', value: currentConversationId}));
        
        $('body').append(form);
        form.submit();
        form.remove();
        
        setTimeout(() => {
            $btn.prop('disabled', false).attr('title', originalTitle);
        }, 3000);
    });

    // Add to KB button handler
    $('#add-to-kb-btn').on('click', function() {
        if (!currentConversationId) {
            showNotification('Please start a conversation before adding to knowledge base.', 'error');
            return;
        }
        
        const $btn = $(this);
        const originalTitle = $btn.attr('title');
        $btn.prop('disabled', true).attr('title', 'Adding to KB...');
        
        performAjaxRequest('aiohm_add_conversation_to_kb', {
            conversation_id: currentConversationId
        }).done(function(response) {
            if (response.success) {
                showNotification('Conversation added to knowledge base successfully!', 'success');
            } else {
                showNotification('Error adding to KB: ' + (response.data.message || 'Unknown error'), 'error');
            }
        }).fail(function() {
            showNotification('Error adding conversation to knowledge base.', 'error');
        }).always(function() {
            $btn.prop('disabled', false).attr('title', originalTitle);
        });
    });

    // Speech-to-text microphone button handler
    $('#activate-audio-btn').on('click', function() {
        if (!currentProjectId) {
            showNotification('Please select a project first.', 'error');
            return;
        }
        
        // Check if browser supports speech recognition
        if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
            showNotification('Speech recognition is not supported in your browser.', 'error');
            return;
        }
        
        const $btn = $(this);
        const $icon = $btn.find('.dashicons');
        
        // Initialize speech recognition
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        const recognition = new SpeechRecognition();
        
        recognition.continuous = false;
        recognition.interimResults = false;
        recognition.lang = 'en-US';
        
        // Start recording
        $btn.prop('disabled', true).attr('title', 'Listening...');
        $icon.removeClass('dashicons-microphone').addClass('dashicons-controls-pause');
        $btn.css('background-color', '#dc3545'); // Red color when recording
        
        showNotification('Listening... Speak now!', 'success');
        
        recognition.onresult = function(event) {
            const transcript = event.results[0][0].transcript;
            if (transcript.trim()) {
                // Insert the transcribed text into the chat input
                const currentText = chatInput.val();
                const newText = currentText ? currentText + ' ' + transcript : transcript;
                chatInput.val(newText);
                chatInput.focus();
                showNotification('Speech captured successfully!', 'success');
            } else {
                showNotification('No speech detected. Please try again.', 'error');
            }
        };
        
        recognition.onerror = function(event) {
            let errorMessage = 'Speech recognition error: ';
            switch(event.error) {
                case 'no-speech':
                    errorMessage += 'No speech detected.';
                    break;
                case 'audio-capture':
                    errorMessage += 'No microphone found.';
                    break;
                case 'not-allowed':
                    errorMessage += 'Microphone access denied.';
                    break;
                default:
                    errorMessage += event.error;
            }
            showNotification(errorMessage, 'error');
        };
        
        recognition.onend = function() {
            // Reset button state
            $btn.prop('disabled', false).attr('title', 'Activate voice-to-text');
            $icon.removeClass('dashicons-controls-pause').addClass('dashicons-microphone');
            $btn.css('background-color', ''); // Reset color
        };
        
        try {
            recognition.start();
        } catch (error) {
            showNotification('Could not start speech recognition: ' + error.message, 'error');
            recognition.onend(); // Reset button state
        }
    });

    // ====================================================================
    // 5. FILE UPLOAD & RESEARCH FUNCTIONS
    // ====================================================================
    
    function insertResearchPrompt() {
        const researchPrompt = `Please act as a research analyst. I want you to meticulously analyze the content from the URL I provide below and then generate a structured report that answers the following questions:

**1. Who:** Who are the key people, companies, or groups mentioned?

**2. What:** What is the main topic, event, or product being discussed?

**3. When:** When did these events happen, or when is the content relevant?

**4. Where:** Where is this happening or where is the focus of the content?

**5. Why:** Why is this information important? What is the main argument or purpose?

**6. How:** How did this happen or how does this work, based on the text?

**7. Summary:** Finally, provide a concise, three-sentence summary of the entire article.

Here is the URL: [PASTE URL HERE]`;

        // Insert the prompt into the chat input
        chatInput.val(researchPrompt);
        
        // Focus on the chat input and scroll to the URL placeholder
        chatInput.focus();
        
        // Select the [PASTE URL HERE] text for easy replacement
        const textArea = chatInput[0];
        const urlPlaceholder = '[PASTE URL HERE]';
        const promptText = textArea.value;
        const startIndex = promptText.indexOf(urlPlaceholder);
        
        if (startIndex !== -1) {
            textArea.setSelectionRange(startIndex, startIndex + urlPlaceholder.length);
        }
        
        // Enable send button
        updateChatUIState();
        
        showNotification('Research prompt inserted! Replace [PASTE URL HERE] with the website URL you want to analyze.', 'success');
    }
    
    function uploadFiles(files) {
        if (!currentProjectId) {
            showNotification('Please select a project first.', 'error');
            return;
        }

        // Create FormData object
        const formData = new FormData();
        
        // Add files to FormData
        for (let i = 0; i < files.length; i++) {
            formData.append('files[]', files[i]);
        }
        
        // Add other required data
        formData.append('action', 'aiohm_upload_project_files');
        formData.append('project_id', currentProjectId);
        formData.append('nonce', aiohm_private_chat_params.nonce);

        // Show upload progress
        showNotification(`Uploading ${files.length} file(s)...`, 'success');

        // Perform AJAX upload
        $.ajax({
            url: aiohm_private_chat_params.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    let message = response.data.message;
                    if (response.data.errors && response.data.errors.length > 0) {
                        message += '. Some files had errors: ' + response.data.errors.join(', ');
                    }
                    showNotification(message, 'success');
                    
                    // Display uploaded files in chat
                    if (response.data.files && response.data.files.length > 0) {
                        displayUploadedFiles(response.data.files);
                    }
                } else {
                    let errorMessage = response.data.message || 'Upload failed';
                    if (response.data.errors && response.data.errors.length > 0) {
                        errorMessage += ': ' + response.data.errors.join(', ');
                    }
                    showNotification(errorMessage, 'error');
                }
                
                // Clear the file input
                $('#file-upload-input').val('');
            },
            error: function(xhr, status, error) {
                showNotification('Upload failed: ' + error, 'error');
                $('#file-upload-input').val('');
            }
        });
    }

    function displayUploadedFiles(files) {
        // Create a message showing the uploaded files
        let fileList = files.map(file => {
            const fileSize = (file.size / 1024).toFixed(1) + ' KB';
            const fileIcon = getFileIcon(file.type);
            return `${fileIcon} ${file.original_name} (${fileSize})`;
        }).join('<br>');

        const fileMessage = `
            <div class="message system">
                <div class="message-content">
                    <strong>📁 Files uploaded to project:</strong><br>
                    ${fileList}
                </div>
            </div>
        `;

        conversationPanel.append(fileMessage);
        conversationPanel.scrollTop(conversationPanel[0].scrollHeight);
    }

    function getFileIcon(fileType) {
        const iconMap = {
            'txt': '📄',
            'pdf': '📋',
            'doc': '📝',
            'docx': '📝',
            'jpg': '🖼️',
            'jpeg': '🖼️',
            'png': '🖼️',
            'gif': '🖼️',
            'mp3': '🎵',
            'wav': '🎵',
            'm4a': '🎵',
            'ogg': '🎵'
        };
        return iconMap[fileType] || '📎';
    }


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