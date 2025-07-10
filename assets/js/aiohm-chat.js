/**
 * AIOHM Chat Frontend JavaScript
 */

(function($) {
    'use strict';
    
    // Global AIOHM Chat object
    window.AIOHM_Chat = {
        instances: {},
        
        /**
         * Initialize chat instance
         */
        init: function() {
            if (typeof window.aiohm_chat_configs !== 'undefined') {
                for (const chatId in window.aiohm_chat_configs) {
                    if (window.aiohm_chat_configs.hasOwnProperty(chatId)) {
                        this.instances[chatId] = new ChatInstance(chatId, window.aiohm_chat_configs[chatId]);
                        this.instances[chatId].init();
                    }
                }
            }
        },
        
        /**
         * Get chat instance
         */
        getInstance: function(chatId) {
            return this.instances[chatId] || null;
        }
    };
    
    /**
     * Chat Instance Class
     */
    function ChatInstance(chatId, config) {
        this.chatId = chatId;
        this.config = config;
        this.$container = $('#' + chatId);
        this.$messages = this.$container.find('.aiohm-chat-messages');
        this.$input = this.$container.find('.aiohm-chat-input');
        this.$sendBtn = this.$container.find('.aiohm-chat-send-btn');
        this.$status = this.$container.find('.aiohm-status-text');
        this.$statusIndicator = this.$container.find('.aiohm-status-indicator');
        
        this.isTyping = false;
        this.conversationHistory = [];
        this.currentRequest = null;
    }
    
    ChatInstance.prototype = {
        /**
         * Initialize chat instance
         */
        init: function() {
            this.bindEvents();
            this.setStatus('ready');
        },
        
        /**
         * Bind event listeners
         */
        bindEvents: function() {
            var self = this;
            
            this.$input.on('input', function() {
                self.handleInputChange();
            });
            
            this.$input.on('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    self.sendMessage();
                }
            });
            
            this.$sendBtn.on('click', function() {
                self.sendMessage();
            });
        },
        
        /**
         * Handle input change
         */
        handleInputChange: function() {
            var hasText = this.$input.val().trim().length > 0;
            this.$sendBtn.prop('disabled', !hasText);
        },
        
        /**
         * Send message
         */
        sendMessage: function() {
            var message = this.$input.val().trim();
            
            if (!message || this.isTyping) {
                return;
            }
            
            this.addMessage(message, 'user');
            this.$input.val('');
            this.handleInputChange();
            this.showTypingIndicator();
            this.sendToServer(message);
        },
        
        /**
         * Add message to chat
         */
        addMessage: function(content, type) {
            const avatarUrl = type === 'bot' ? this.config.settings.ai_avatar : '';
            const avatar = avatarUrl ? `<img src="${avatarUrl}" alt="AI Avatar" style="width:100%; height:100%; border-radius:50%; object-fit: cover;">` : (type === 'bot' ? this.getBotAvatar() : this.getUserAvatar());
            const messageHtml = `
                <div class="aiohm-message aiohm-message-${type}">
                    <div class="aiohm-message-avatar">${avatar}</div>
                    <div class="aiohm-message-bubble"><div class="aiohm-message-content">${content}</div></div>
                </div>`;
            this.$messages.append(messageHtml).scrollTop(this.$messages[0].scrollHeight);
        },
        
        /**
         * Show typing indicator
         */
        showTypingIndicator: function() {
            this.isTyping = true;
            this.setStatus('typing');
            this.addMessage('<div class="aiohm-typing-dots"><span></span><span></span><span></span></div>', 'bot');
        },
        
        /**
         * Hide typing indicator
         */
        hideTypingIndicator: function() {
            this.isTyping = false;
            this.setStatus('ready');
            this.$messages.find('.aiohm-typing-indicator').last().parent().remove();
        },
        
        /**
         * Send message to server
         */
        sendToServer: function(message) {
            var self = this;
            
            if (this.currentRequest) {
                this.currentRequest.abort();
            }
            
            this.currentRequest = $.ajax({
                url: this.config.ajax_url,
                type: 'POST',
                data: {
                    action: 'aiohm_test_mirror_mode_chat', // Use the test chat action
                    nonce: this.config.nonce,
                    message: message,
                    settings: this.config.settings
                },
                success: function(response) {
                    const answer = response.success ? response.data.answer : (response.data.message || self.config.strings.error);
                    self.addMessage(answer, 'bot');
                },
                error: function() {
                    self.addMessage(self.config.strings.error, 'bot');
                },
                complete: function() {
                    self.currentRequest = null;
                    self.hideTypingIndicator();
                }
            });
        },
        
        /**
         * Set chat status
         */
        setStatus: function(status) {
            this.$status.text(status.charAt(0).toUpperCase() + status.slice(1));
            this.$statusIndicator.attr('data-status', status);
        },
        
        /**
         * Get user avatar SVG
         */
        getUserAvatar: function() {
            return '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>';
        },
        
        /**
         * Get bot avatar SVG
         */
        getBotAvatar: function() {
            return '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M12 1v6m0 6v6m-3-9 3 3 3-3"></path></svg>';
        }
    };
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        window.AIOHM_Chat.init();
    });
    
})(jQuery);