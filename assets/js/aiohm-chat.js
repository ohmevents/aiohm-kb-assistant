/**
 * AIOHM Chat & Search Frontend JavaScript
 * Version: 1.1.0 (Added Search Functionality)
 */

(function($) {
    'use strict';
    
    // --- Chat Instance Logic ---
    window.AIOHM_Chat = {
        instances: {},
        
        init: function() {
            if (typeof window.aiohm_chat_configs !== 'undefined') {
                for (var chatId in window.aiohm_chat_configs) {
                    if (window.aiohm_chat_configs.hasOwnProperty(chatId)) {
                        this.instances[chatId] = new ChatInstance(chatId, window.aiohm_chat_configs[chatId]);
                        this.instances[chatId].init();
                    }
                }
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
    }
    
    ChatInstance.prototype = {
        init: function() {
            this.bindEvents();
        },
        
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
        
        handleInputChange: function() {
            var hasText = this.$input.val().trim().length > 0;
            this.$sendBtn.prop('disabled', !hasText);
        },
        
        sendMessage: function() {
            var message = this.$input.val().trim();
            if (!message || this.isTyping) { return; }
            
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
            if (this.currentRequest) { this.currentRequest.abort(); }
            
            var requestData = {
                action: this.config.chat_action || 'aiohm_frontend_chat',
                nonce: this.config.nonce,
                message: message,
                page_title: document.title,
                page_url: window.location.href
            };

            this.currentRequest = $.ajax({
                url: this.config.ajax_url,
                type: 'POST',
                data: requestData,
                success: function(response) {
                    var answer = response.success ? response.data.answer : (response.data.message || self.config.strings.error);
                    self.hideTypingIndicator();
                    self.addMessage(answer, 'bot');
                },
                error: function() {
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

    // --- Search Instance Logic ---
    window.AIOHM_Search = {
        instances: {},
        
        init: function() {
            if (typeof window.aiohm_search_configs !== 'undefined') {
                for (var searchId in window.aiohm_search_configs) {
                    if (window.aiohm_search_configs.hasOwnProperty(searchId)) {
                        this.instances[searchId] = new SearchInstance(searchId, window.aiohm_search_configs[searchId]);
                        this.instances[searchId].init();
                    }
                }
            }
        },
    };

    function SearchInstance(searchId, config) {
        this.searchId = searchId;
        this.config = config;
        this.$container = $('#' + searchId);
        this.$input = this.$container.find('.aiohm-search-input');
        this.$btn = this.$container.find('.aiohm-search-btn');
        this.$results = this.$container.find('.aiohm-search-results');
        this.$status = this.$container.find('.aiohm-search-status');
        this.$filter = this.$container.find('.aiohm-content-type-filter');
        this.currentRequest = null;
        this.typingTimeout = null;
    }

    SearchInstance.prototype = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            var self = this;
            this.$btn.on('click', function() { self.triggerSearch(); });
            this.$filter.on('change', function() { self.triggerSearch(); });

            if (this.config.settings.enable_instant_search) {
                this.$input.on('keyup', function() {
                    clearTimeout(self.typingTimeout);
                    self.typingTimeout = setTimeout(function() {
                        if (self.$input.val().length >= self.config.settings.min_chars) {
                            self.triggerSearch();
                        }
                    }, 500);
                });
            } else {
                 this.$input.on('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        self.triggerSearch();
                    }
                });
            }
        },

        triggerSearch: function() {
            var query = this.$input.val().trim();
            if (!query) { return; }

            var self = this;
            this.$status.show().find('.aiohm-search-loading').text(this.config.strings.searching);
            this.$results.empty();
            if (this.currentRequest) { this.currentRequest.abort(); }

            this.currentRequest = $.ajax({
                url: this.config.ajax_url,
                type: 'POST',
                data: {
                    action: 'aiohm_search_knowledge',
                    nonce: this.config.nonce,
                    query: query,
                    content_type_filter: this.$filter.val(),
                    max_results: this.config.settings.max_results,
                    excerpt_length: this.config.settings.excerpt_length,
                },
                success: function(response) {
                    self.$status.hide();
                    if (response.success) {
                        self.renderResults(response.data);
                    } else {
                        self.$results.html('<p>' + (response.data.message || self.config.strings.error) + '</p>');
                    }
                },
                error: function() {
                    self.$status.hide();
                    self.$results.html('<p>' + self.config.strings.error + '</p>');
                }
            });
        },

        renderResults: function(data) {
            if (!data.results || data.results.length === 0) {
                this.$results.html('<p>' + this.config.strings.no_results + '</p>');
                return;
            }

            var resultsHtml = '';
            if (this.config.settings.show_results_count) {
                resultsHtml += '<p class="aiohm-results-count">' + this.config.strings.results_count.replace('%d', data.total_count) + '</p>';
            }

            data.results.forEach(function(item) {
                resultsHtml += '<div class="aiohm-search-result-item">';
                resultsHtml += '<h3 class="aiohm-result-title"><a href="' + item.url + '" target="_blank">' + item.title + '</a></h3>';
                if (this.config.settings.show_content_type) {
                    resultsHtml += '<span class="aiohm-result-type">' + item.content_type + '</span>';
                }
                resultsHtml += '<p class="aiohm-result-excerpt">' + item.excerpt + '</p>';
                resultsHtml += '</div>';
            }, this);

            this.$results.html(resultsHtml);
        }
    };
    
    $(document).ready(function() {
        window.AIOHM_Chat.init();
        window.AIOHM_Search.init();
    });
    
})(jQuery);