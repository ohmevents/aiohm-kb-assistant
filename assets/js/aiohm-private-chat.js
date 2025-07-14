// Document ready function to ensure the DOM is loaded before executing jQuery code.
jQuery(document).ready(function($) {

    // Initialize a timer for the typing indicator. This is part of the original code.
    var typingTimer;
    var doneTypingInterval = 1000;

    // --- INITIALIZATION ---
    // The first action when the page loads is to fetch the user's projects.
    loadProjects();

    // --- EVENT HANDLERS ---

    // Event handler for the "New Project" button.
    $('#new-project-btn').on('click', function() {
        // Prompt the user for a new project name.
        const projectName = prompt('Enter the name for your new project:');
        // If a name is provided (and it's not just whitespace), call the createProject function.
        if (projectName && projectName.trim() !== '') {
            createProject(projectName.trim());
        }
    });

    // Event handler for the project selector dropdown.
    $('#project-selector').on('change', function() {
        const projectId = $(this).val();
        // If a valid project is selected, load its conversations.
        if (projectId) {
            loadConversations(projectId);
        } else {
            // If the "Select a Project" option is chosen, clear the history.
            $('#chat-history').html('<div>Please select a project to see the conversation.</div>');
        }
    });

    // Event handler for the main chat form submission.
    $('#private-assistant-form').on('submit', function(e) {
        // Prevent the default browser form submission.
        e.preventDefault();

        var userInput = $('#user-input').val();
        var projectId = $('#project-selector').val();

        // Validate that a project has been selected.
        if (!projectId) {
            alert('Please select a project first.');
            return;
        }

        // Validate that the user has typed something.
        if (userInput.trim() === '') {
            return;
        }

        // Immediately display the user's message for a better user experience.
        $('#chat-history').append('<div class="user-message"><strong>You:</strong> ' + escapeHTML(userInput) + '</div>');
        var messageToSend = userInput; // Store the message before clearing the input.
        $('#user-input').val(''); // Clear the input field.

        // Call the function to send the chat message to the server.
        sendChatMessage(messageToSend, projectId);
    });

    // The following keyup/keydown handlers are from your original file,
    // likely for a "user is typing" feature. They are preserved.
    $('#user-input').on('keyup', function() {
        clearTimeout(typingTimer);
        typingTimer = setTimeout(doneTyping, doneTypingInterval);
    });

    $('#user-input').on('keydown', function() {
        clearTimeout(typingTimer);
    });

    // This function is called after the user stops typing. Preserved from original.
    function doneTyping() {
        // This can be used to hide a "typing..." indicator in the future.
    }


    // --- AJAX FUNCTIONS (Corrected) ---

    /**
     * FIX: This function fetches the list of projects from the server.
     * The original file was missing this core functionality.
     */
    function loadProjects() {
        $.ajax({
            url: aiohm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'aiohm_get_projects', // This action must exist in core-init.php
                nonce: aiohm_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    const projectSelector = $('#project-selector');
                    // Start with a clean slate and add the default prompt.
                    projectSelector.html('<option value="">Select a Project</option>');
                    // Populate the dropdown with projects from the server.
                    response.data.forEach(function(project) {
                        projectSelector.append('<option value="' + project.id + '">' + escapeHTML(project.project_name) + '</option>');
                    });
                } else {
                    console.error('Could not load projects:', response.data);
                    $('#chat-history').html('<div style="color: red;">Error: Could not load projects. Check the browser console for details.</div>');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("AJAX Error loading projects:", textStatus, errorThrown);
                alert('A network error occurred while loading projects.');
            }
        });
    }

    /**
     * FIX: This function handles the creation of a new project.
     * It sends the project name to the server and updates the UI on success.
     */
    function createProject(projectName) {
        $.ajax({
            url: aiohm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'aiohm_create_project', // This action must exist in core-init.php
                project_name: projectName,
                nonce: aiohm_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Add the newly created project to the dropdown and select it.
                    $('#project-selector').append('<option value="' + response.data.project_id + '" selected>' + escapeHTML(response.data.project_name) + '</option>');
                    // Clear the chat history to show a clean slate for the new project.
                    $('#chat-history').html('<div>Project created successfully! You can now start your conversation.</div>');
                } else {
                    alert('Error creating project: ' + response.data);
                }
            },
            error: function() {
                alert('A server error occurred while creating the project.');
            }
        });
    }

    /**
     * FIX: This function fetches the conversation history for a selected project.
     */
    function loadConversations(projectId) {
        $.ajax({
            url: aiohm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'aiohm_get_project_conversations', // This action must exist in core-init.php
                project_id: projectId,
                nonce: aiohm_ajax.nonce
            },
            success: function(response) {
                const chatHistory = $('#chat-history');
                chatHistory.html(''); // Clear any previous conversation.

                if (response.success) {
                    if (response.data && response.data.length > 0) {
                        // Loop through each message pair and display it.
                        response.data.forEach(function(item) {
                            try {
                                const conversation = JSON.parse(item.conversation);
                                if (conversation.user) {
                                    chatHistory.append('<div class="user-message"><strong>You:</strong> ' + escapeHTML(conversation.user) + '</div>');
                                }
                                if (conversation.assistant) {
                                    chatHistory.append('<div class="assistant-message"><strong>Assistant:</strong> ' + escapeHTML(conversation.assistant) + '</div>');
                                }
                            } catch (e) {
                                // Fallback for older, non-JSON messages if any exist.
                                console.error("Could not parse conversation:", item.conversation, e);
                                chatHistory.append('<div class="assistant-message">' + escapeHTML(item.conversation) + '</div>');
                            }
                        });
                    } else {
                        // If there are no messages in the project yet.
                        chatHistory.append('<div>No conversations in this project yet. Start by typing a message below.</div>');
                    }
                } else {
                    alert('Error loading conversations: ' + response.data);
                }
            },
            error: function() {
                alert('A server error occurred while fetching conversations.');
            }
        });
    }

    /**
     * FIX: This function sends the user's message to the chat handler.
     * The original file's submit handler logic is moved here.
     */
    function sendChatMessage(message, projectId) {
        $.ajax({
            url: aiohm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'aiohm_private_assistant_chat', // The main chat handler action
                message: message,
                project_id: projectId,
                nonce: aiohm_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Display the assistant's reply.
                    $('#chat-history').append('<div class="assistant-message"><strong>Assistant:</strong> ' + response.data.message + '</div>');
                } else {
                    // Display any errors returned from the AI or server.
                    $('#chat-history').append('<div class="assistant-message" style="color: red;"><strong>Error:</strong> ' + response.data.message + '</div>');
                }
            },
            error: function() {
                alert('A critical error occurred while sending your message.');
            }
        });
    }

    // --- UTILITY FUNCTIONS ---

    /**
     * This is a utility function from your original file.
     * It's a crucial security measure to prevent Cross-Site Scripting (XSS) attacks
     * by converting special characters into their HTML entities.
     * @param {string} str The input string to escape.
     * @returns {string} The sanitized string.
     */
    function escapeHTML(str) {
        // Ensure the input is a string before trying to replace characters.
        if (typeof str !== 'string') {
            return '';
        }
        return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }
});