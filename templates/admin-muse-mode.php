<?php
/**
 * Admin "Muse Mode" Private Assistant Page
 */

if (!defined('ABSPATH')) {
    exit;
}

// You can add logic here to load existing conversations later
$conversations = []; // Placeholder

?>

<div id="aiohm-private-assistant" class="aiohm-private-assistant-container">
    <div class="aiohm-chat-header">
        <div class="header-text">
            <h2>Muse: Your Private Brand Assistant</h2>
            <p>Creative strategy, emotional clarity, voice-aligned expression.</p>
        </div>
        <div class="header-actions">
            <button id="new-project-btn" class="button">New Project</button>
            <button id="delete-all-btn" class="button-link button-link-delete">Delete All Conversations</button>
        </div>
    </div>
    <div class="aiohm-chat-body">
        <div id="conversation-panel" class="conversation-panel">
            <div class="message ai">Hello! I'm your private brand assistant, Muse. How can we bring your vision to life today?</div>
            </div>
        <div class="input-area">
            <?php wp_nonce_field('aiohm-private-chat-nonce', 'aiohm_private_chat_nonce_field'); ?>
            <textarea id="chat-input" placeholder="Ask Muse anything..."></textarea>
            <button id="send-btn" class="button button-primary" title="Send Message">
                <span class="dashicons dashicons-arrow-right-alt"></span>
            </button>
        </div>
    </div>
</div>

<style>
    /* AIOHM Private Assistant Styles */
    :root {
        --ohm-primary: #457d58;
        --ohm-dark: #272727;
        --ohm-light-accent: #cbddd1;
        --ohm-muted-accent: #7d9b76;
        --ohm-light-bg: #EBEBEB;
        --ohm-dark-accent: #1f5014;
        --ohm-font-primary: 'Montserrat', sans-serif;
        --ohm-font-secondary: 'PT Sans', sans-serif;
    }

    .aiohm-private-assistant-container {
        margin: 2em auto;
        border: 1px solid var(--ohm-light-bg);
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        font-family: var(--ohm-font-secondary);
        background: #fff;
    }

    .aiohm-chat-header {
        background-color: #f8f9fa;
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--ohm-light-bg);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .aiohm-chat-header h2 {
        font-family: var(--ohm-font-primary);
        color: var(--ohm-dark-accent);
        font-size: 1.25rem;
        font-weight: 600;
        margin: 0;
    }

    .aiohm-chat-header p {
        color: var(--ohm-dark);
        font-size: 0.9rem;
        margin: 0.25rem 0 0 0;
    }
    .header-actions {
        display: flex;
        gap: 0.75rem;
    }

    .aiohm-chat-body {
        padding: 1.5rem;
    }

    .conversation-panel {
        height: 50vh;
        max-height: 500px;
        overflow-y: auto;
        margin-bottom: 1rem;
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
        padding-right: 10px; /* For scrollbar */
    }

    .message {
        padding: 0.75rem 1.25rem;
        border-radius: 12px;
        max-width: 80%;
        line-height: 1.6;
        width: fit-content;
    }

    .message.ai {
        background-color: #f1f3f5;
        color: var(--ohm-dark);
        align-self: flex-start;
        border-bottom-left-radius: 4px;
    }

    .message.user {
        background-color: var(--ohm-primary);
        color: #fff;
        align-self: flex-end;
        border-bottom-right-radius: 4px;
    }

    .input-area {
        display: flex;
        gap: 0.5rem;
        border: 1px solid var(--ohm-light-bg);
        border-radius: 12px;
        padding: 8px;
        transition: border-color 0.2s;
    }
    .input-area:focus-within {
        border-color: var(--ohm-primary);
    }

    .input-area textarea {
        flex-grow: 1;
        border: none;
        padding: 0.5rem 0.5rem;
        resize: none;
        background: transparent;
        outline: none;
        box-shadow: none;
        font-size: 1em;
        height: 50px;
        max-height: 150px;
    }

    .input-area .button {
        border-radius: 8px;
        height: 50px;
        width: 50px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
</style>

<script>
    jQuery(document).ready(function($) {
        const chatInput = $('#chat-input');
        const sendBtn = $('#send-btn');
        const conversationPanel = $('#conversation-panel');
        const newProjectBtn = $('#new-project-btn');
        const deleteAllBtn = $('#delete-all-btn');

        const addMessage = (text, type) => {
            const messageClass = type === 'user' ? 'user' : 'ai';
            // Sanitize text before appending
            const sanitizedText = $('<div/>').text(text).html();
            const messageHtml = `<div class="message ${messageClass}">${sanitizedText.replace(/\n/g, '<br>')}</div>`;
            conversationPanel.append(messageHtml);
            conversationPanel.scrollTop(conversationPanel[0].scrollHeight);
        };
        
        const sendMessage = () => {
            const message = chatInput.val().trim();
            if (message) {
                addMessage(message, 'user');
                chatInput.val('').css('height', 'auto'); // Reset height after sending

                addMessage("Thinking...", 'ai'); // Typing indicator

                $.post(ajaxurl, {
                    _ajax_nonce: $('#aiohm_private_chat_nonce_field').val(),
                    action: 'aiohm_private_chat',
                    message: message
                }, function(response) {
                    conversationPanel.find('.message.ai:last-child').remove(); // Remove "Thinking..."
                    if (response.success) {
                        addMessage(response.data.reply, 'ai');
                    } else {
                        addMessage('Sorry, an error occurred. ' + response.data.message, 'ai');
                    }
                }).fail(function() {
                    conversationPanel.find('.message.ai:last-child').remove();
                    addMessage('A server error occurred. Please try again.', 'ai');
                });
            }
        };

        sendBtn.on('click', sendMessage);

        chatInput.on('keypress', function(e) {
            if (e.which === 13 && !e.shiftKey) {
                e.preventDefault();
                sendBtn.click();
            }
        }).on('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });

        newProjectBtn.on('click', function() {
            conversationPanel.html('<div class="message ai">New project started. What vision shall we explore?</div>');
            // Add logic here to clear the current thread from history if you implement storage
        });

        deleteAllBtn.on('click', function() {
            if (confirm('Are you sure you want to delete all conversation history? This cannot be undone.')) {
                // Add AJAX call here to delete history from the database
                conversationPanel.html('<div class="message ai">All conversations have been deleted.</div>');
            }
        });
    });
</script>