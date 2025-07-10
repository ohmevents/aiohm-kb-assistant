/**
 * AIOHM Chat Frontend JavaScript
 * Version: 1.0.3 (Debugging & Robustness Fix)
 */

(function($) {
    'use strict';
    
    // Global AIOHM Chat object
    window.AIOHM_Chat = {
        instances: {},
        
        init: function() {
            console.log('AIOHM Chat: Initializing...');
            if (typeof window.aiohm_chat_configs !== 'undefined') {
                console.log('AIOHM Chat: Configs found.', window.aiohm_chat_configs);
                for (var chatId in window.aiohm_chat_configs) {
                    if (window.aiohm_chat_configs.hasOwnProperty(chatId)) {
                        this.instances[chatId] = new ChatInstance(chatId, window.aiohm_chat_configs[chatId]);
                        this.instances[chatId].init();
                    }
                }
            } else {
                console.error('AIOHM Chat: ERROR - window.aiohm_chat_configs not found. The chat cannot start.');
            }
        },
        
        getInstance: function(chatId) {
            return this.instances[chatId] || null;
        }
    };
    
    function ChatInstance(chatId, config) {
        this.chatId = chatId;
        this.config = config;
        this.$container = $('#' + chatId);
        this.$messages = this.$container.find('.aiohm-chat-messages');
        this.$input = this.$container.find('.aiohm-chat-input');
        this.$sendBtn = this.$container.find('.aiohm-chat-send-btn');
        this.isTyping = false;
        this.currentRequest = null;
        
        console.log('AIOHM Chat (' + chatId + '): New instance created.');
        if (!this.$container.length) {
            console.error('AIOHM Chat (' + chatId + '): ERROR - Container element #' + chatId + ' not found.');
        }
        if (!this.$input.length) {
            console.error('AIOHM Chat (' + chatId + '): ERROR - Input element .aiohm-chat-input not found.');
        }
        if (!this.$sendBtn.length) {
            console.error('AIOHM Chat (' + chatId + '): ERROR - Send button .aiohm-chat-send-btn not found.');
        }
    }
    
    ChatInstance.prototype = {
        init: function() {
            console.log('AIOHM Chat (' + this.chatId + '): Binding events.');
            this.bindEvents();
        },
        
        bindEvents: function() {
            var self = this;
            
            this.$input.on('input', function() {
                console.log('AIOHM Chat (' + self.chatId + '): Input event fired.');
                self.handleInputChange();
            });
            
            this.$input.on('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    self.sendMessage();
                }
            });
            
            this.$sendBtn.on('click', function() {
                console.log('AIOHM Chat (' + self.chatId + '): Send button clicked.');
                self.sendMessage();
            });
        },
        
        handleInputChange: function() {
            var hasText = this.$input.val().trim().length > 0;
            this.$sendBtn.prop('disabled', !hasText);
            console.log('AIOHM Chat (' + this.chatId + '): Send button disabled state is now: ' + !hasText);
        },
        
        sendMessage: function() {
            var message = this.$input.val().trim();
            
            if (!message || this.isTyping) {
                console.warn('AIOHM Chat (' + this.chatId + '): Send message aborted. Message empty or already typing.');
                return;
            }
            
            console.log('AIOHM Chat (' + this.chatId + '): Sending message: "' + message + '"');
            
            this.addMessage(message, 'user');
            this.$input.val('');
            this.handleInputChange();
            this.showTypingIndicator();
            this.sendToServer(message);
        },
        
        addMessage: function(content, type) {
            var avatar = type === 'bot' ? this.getBotAvatar() : this.getUserAvatar();
            var messageHtml =
                '<div class="aiohm-message aiohm-message-' + type + '">' +
                    '<div class="aiohm-message-avatar">' + avatar + '</div>' +
                    '<div class="aiohm-message-bubble"><div class="aiohm-message-content">' + content + '</div></div>' +
                '</div>';
            this.$messages.append(messageHtml).scrollTop(this.$messages[0].scrollHeight);
        },
        
        showTypingIndicator: function() {
            this.isTyping = true;
            var typingIndicatorHtml =
                '<div class="aiohm-message aiohm-message-bot aiohm-typing-indicator">' +
                    '<div class="aiohm-message-avatar">' + this.getBotAvatar() + '</div>' +
                    '<div class="aiohm-message-bubble">' +
                        '<div class="aiohm-typing-dots"><span></span><span></span><span></span></div>' +
                    '</div>' +
                '</div>';
            this.$messages.append(typingIndicatorHtml).scrollTop(this.$messages[0].scrollHeight);
        },
        
        hideTypingIndicator: function() {
            this.isTyping = false;
            this.$messages.find('.aiohm-typing-indicator').remove();
        },
        
        sendToServer: function(message) {
            var self = this;
            
            if (this.currentRequest) {
                this.currentRequest.abort();
            }
            
            this.currentRequest = $.ajax({
                url: this.config.ajax_url,
                type: 'POST',
                data: {
                    action: this.config.chat_action || 'aiohm_test_mirror_mode_chat',
                    nonce: this.config.nonce,
                    message: message,
                    settings: this.config.settings
                },
                success: function(response) {
                    console.log('AIOHM Chat (' + self.chatId + '): AJAX success response:', response);
                    var answer = response.success ? response.data.answer : (response.data.message || self.config.strings.error);
                    self.hideTypingIndicator();
                    self.addMessage(answer, 'bot');
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('AIOHM Chat (' + self.chatId + '): AJAX error:', textStatus, errorThrown);
                    self.hideTypingIndicator();
                    self.addMessage(self.config.strings.error, 'bot');
                },
                complete: function() {
                    self.currentRequest = null;
                }
            });
        },
        
        getUserAvatar: function() {
            return '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>';
        },
        
        getBotAvatar: function() {
            return '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M12 1v6m0 6v6m-3-9 3 3 3-3"></path></svg>';
        }
    };
    
    $(document).ready(function() {
        window.AIOHM_Chat.init();
    });
    
})(jQuery);