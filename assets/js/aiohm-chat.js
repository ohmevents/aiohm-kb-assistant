/**
 * AIOHM Chat Frontend JavaScript
 */

(function($) {
    'use strict';
    
    // Global AIOHM Chat object
    window.AIOHM_Chat = {
        instances: {},
        config: {},
        
        /**
         * Initialize chat instance
         */
        init: function(chatId, config) {
            this.instances[chatId] = new ChatInstance(chatId, config);
            this.instances[chatId].init();
        },
        
        /**
         * Get chat instance
         */
        getInstance: function(chatId) {
            return this.instances[chatId] || null;
        }
    };
    
    // Global AIOHM Search object
    window.AIOHM_Search = {
        instances: {},
        
        /**
         * Initialize search instance
         */
        init: function(searchId, config) {
            this.instances[searchId] = new SearchInstance(searchId, config);
            this.instances[searchId].init();
        },
        
        /**
         * Get search instance
         */
        getInstance: function(searchId) {
            return this.instances[searchId] || null;
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
        
        // Load conversation from localStorage if persistence is enabled
        if (this.config.settings && this.config.settings.persist_chat) {
            this.loadConversation();
        }
    }
    
    ChatInstance.prototype = {
        /**
         * Initialize chat instance
         */
        init: function() {
            this.bindEvents();
            this.initializeFeatures();
            this.setStatus('ready');
            
            // Show welcome message if no conversation history
            if (this.conversationHistory.length === 0) {
                this.showWelcomeState();
            } else {
                this.restoreConversation();
            }
        },
        
        /**
         * Bind event listeners
         */
        bindEvents: function() {
            var self = this;
            
            // Input events
            this.$input.on('input', function() {
                self.handleInputChange();
            });
            
            this.$input.on('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    self.sendMessage();
                }
            });
            
            // Send button
            this.$sendBtn.on('click', function() {
                self.sendMessage();
            });
            
            // Quick reply buttons
            this.$container.on('click', '.aiohm-quick-reply-btn', function() {
                var reply = $(this).data('reply');
                self.$input.val(reply);
                self.sendMessage();
            });
            
            // Suggestion buttons
            this.$container.on('click', '.aiohm-suggestion-btn', function() {
                var question = $(this).data('question');
                self.$input.val(question);
                self.sendMessage();
            });
            
            // Sources toggle
            this.$container.on('click', '.aiohm-sources-toggle', function() {
                $(this).siblings('.aiohm-sources-list').slideToggle();
            });
            
            // Copy message buttons
            this.$container.on('click', '.aiohm-copy-btn', function() {
                var content = $(this).closest('.aiohm-message').find('.aiohm-message-content').text();
                self.copyToClipboard(content);
            });
            
            // Auto-resize textarea
            if (this.$input.hasClass('aiohm-auto-resize')) {
                this.$input.on('input', function() {
                    self.autoResizeTextarea(this);
                });
            }
        },
        
        /**
         * Initialize additional features
         */
        initializeFeatures: function() {
            // Add copy buttons to messages if enabled
            if (this.config.features && this.config.features.copy_messages) {
                this.addCopyButtons();
            }
            
            // Initialize auto-scroll
            if (this.config.settings && this.config.settings.auto_scroll) {
                this.initAutoScroll();
            }
        },
        
        /**
         * Handle input change
         */
        handleInputChange: function() {
            var hasText = this.$input.val().trim().length > 0;
            this.$sendBtn.prop('disabled', !hasText);
            
            // Update character counter if enabled
            var $counter = this.$container.find('.aiohm-char-count');
            if ($counter.length) {
                $counter.text(this.$input.val().length);
            }
        },
        
        /**
         * Send message
         */
        sendMessage: function() {
            var message = this.$input.val().trim();
            
            if (!message || this.isTyping) {
                return;
            }
            
            // Add user message to chat
            this.addMessage(message, 'user');
            
            // Clear input
            this.$input.val('');
            this.handleInputChange();
            
            // Show typing indicator
            this.showTypingIndicator();
            
            // Send to server
            this.sendToServer(message);
        },
        
        /**
         * Add message to chat
         */
        addMessage: function(content, type, metadata) {
            var timestamp = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            var messageHtml = this.createMessageHtml(content, type, timestamp, metadata);
            
            this.$messages.append(messageHtml);
            
            // Store in conversation history
            this.conversationHistory.push({
                content: content,
                type: type,
                timestamp: timestamp,
                metadata: metadata || {}
            });
            
            // Save conversation if persistence is enabled
            if (this.config.settings && this.config.settings.persist_chat) {
                this.saveConversation();
            }
            
            // Auto-scroll
            this.scrollToBottom();
            
            // Play sound if enabled
            if (this.config.features && this.config.features.enable_sound && type === 'bot') {
                this.playNotificationSound();
            }
        },
        
        /**
         * Create message HTML
         */
        createMessageHtml: function(content, type, timestamp, metadata) {
            var classes = ['aiohm-message', 'aiohm-message-' + type];
            
            if (metadata && metadata.error) {
                classes.push('aiohm-message-error');
            }
            
            var html = '<div class="' + classes.join(' ') + '">';
            
            // Avatar
            html += '<div class="aiohm-message-avatar">';
            if (type === 'user') {
                html += this.getUserAvatar();
            } else {
                html += this.getBotAvatar();
            }
            html += '</div>';
            
            // Message bubble
            html += '<div class="aiohm-message-bubble">';
            html += '<div class="aiohm-message-content">';
            
            if (metadata && metadata.error) {
                html += '<div class="aiohm-error-icon">⚠️</div>';
            }
            
            html += this.formatMessageContent(content);
            html += '</div>';
            
            // Message metadata
            html += '<div class="aiohm-message-meta">';
            
            if (this.config.settings && this.config.settings.show_timestamps) {
                html += '<span class="aiohm-message-time">' + timestamp + '</span>';
            }
            
            // Copy button
            if (this.config.features && this.config.features.copy_messages) {
                html += '<button type="button" class="aiohm-copy-btn" title="' + this.config.strings.copy + '">';
                html += '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">';
                html += '<rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>';
                html += '<path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>';
                html += '</svg>';
                html += '</button>';
            }
            
            // Sources
            if (metadata && metadata.sources && metadata.sources.length > 0) {
                html += '<div class="aiohm-message-sources">';
                html += '<button type="button" class="aiohm-sources-toggle">';
                html += this.config.strings.sources + ' (' + metadata.sources.length + ')';
                html += '</button>';
                html += '<div class="aiohm-sources-list" style="display: none;">';
                
                metadata.sources.forEach(function(source) {
                    html += '<div class="aiohm-source-item">';
                    html += '<div class="aiohm-source-title">' + source.title + '</div>';
                    html += '<div class="aiohm-source-type">' + source.content_type.charAt(0).toUpperCase() + source.content_type.slice(1) + '</div>';
                    if (source.metadata && source.metadata.url) {
                        html += '<a href="' + source.metadata.url + '" target="_blank" class="aiohm-source-link">View Source</a>';
                    }
                    html += '</div>';
                });
                
                html += '</div>';
                html += '</div>';
            }
            
            html += '</div>';
            html += '</div>';
            html += '</div>';
            
            return html;
        },
        
        /**
         * Format message content
         */
        formatMessageContent: function(content) {
            if (!this.config.features || !this.config.features.markdown_support) {
                return $('<div>').text(content).html(); // Escape HTML
            }
            
            // Basic markdown formatting
            content = $('<div>').text(content).html(); // Escape HTML first
            
            // Bold text
            content = content.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            
            // Italic text
            content = content.replace(/\*(.*?)\*/g, '<em>$1</em>');
            
            // Code blocks
            content = content.replace(/`([^`]+)`/g, '<code>$1</code>');
            
            // Line breaks
            content = content.replace(/\n/g, '<br>');
            
            // Links
            content = content.replace(/(https?:\/\/[^\s<>"]+)/g, '<a href="$1" target="_blank" rel="noopener">$1</a>');
            
            return content;
        },
        
        /**
         * Show typing indicator
         */
        showTypingIndicator: function() {
            this.isTyping = true;
            this.setStatus('typing');
            
            var typingHtml = '<div class="aiohm-message aiohm-message-bot aiohm-typing-indicator">';
            typingHtml += '<div class="aiohm-message-avatar">' + this.getBotAvatar() + '</div>';
            typingHtml += '<div class="aiohm-message-bubble">';
            typingHtml += '<div class="aiohm-typing-dots">';
            typingHtml += '<span></span><span></span><span></span>';
            typingHtml += '</div>';
            typingHtml += '</div>';
            typingHtml += '</div>';
            
            this.$messages.append(typingHtml);
            this.scrollToBottom();
        },
        
        /**
         * Hide typing indicator
         */
        hideTypingIndicator: function() {
            this.isTyping = false;
            this.setStatus('ready');
            this.$messages.find('.aiohm-typing-indicator').remove();
        },
        
        /**
         * Send message to server
         */
        sendToServer: function(message) {
            var self = this;
            
            // Cancel any existing request
            if (this.currentRequest) {
                this.currentRequest.abort();
            }
            
            this.currentRequest = $.ajax({
                url: this.config.ajax_url,
                type: 'POST',
                data: {
                    action: 'aiohm_chat_request',
                    message: message,
                    nonce: this.config.nonce
                },
                timeout: 30000,
                success: function(response) {
                    self.handleServerResponse(response);
                },
                error: function(xhr, status, error) {
                    self.handleServerError(status, error);
                },
                complete: function() {
                    self.currentRequest = null;
                    self.hideTypingIndicator();
                }
            });
        },
        
        /**
         * Handle server response
         */
        handleServerResponse: function(response) {
            if (response.success && response.data && response.data.response) {
                this.addMessage(response.data.response, 'bot', {
                    sources: response.data.sources || []
                });
            } else {
                this.addMessage(this.config.strings.error, 'bot', {
                    error: true
                });
            }
        },
        
        /**
         * Handle server error
         */
        handleServerError: function(status, error) {
            var errorMessage = this.config.strings.error;
            
            if (status === 'timeout') {
                errorMessage = 'Request timed out. Please try again.';
            } else if (status === 'abort') {
                return; // Don't show error for aborted requests
            }
            
            this.addMessage(errorMessage, 'bot', {
                error: true
            });
        },
        
        /**
         * Set chat status
         */
        setStatus: function(status) {
            var statusText = this.config.strings[status] || status;
            this.$status.text(statusText);
            this.$statusIndicator.attr('data-status', status);
        },
        
        /**
         * Show welcome state
         */
        showWelcomeState: function() {
            var welcomeHtml = '<div class="aiohm-empty-chat-state">';
            welcomeHtml += '<div class="aiohm-welcome-avatar">' + this.getBotAvatar() + '</div>';
            welcomeHtml += '<div class="aiohm-welcome-message">Hello! How can I help you today?</div>';
            welcomeHtml += this.getSuggestedQuestions();
            welcomeHtml += '</div>';
            
            this.$messages.html(welcomeHtml);
        },
        
        /**
         * Get suggested questions
         */
        getSuggestedQuestions: function() {
            var suggestions = [
                'What services do you offer?',
                'How can I contact support?',
                'What are your business hours?',
                'Do you have a FAQ section?'
            ];
            
            var html = '<div class="aiohm-suggested-questions">';
            html += '<div class="aiohm-suggestions-title">Suggested questions:</div>';
            
            suggestions.forEach(function(suggestion) {
                html += '<button type="button" class="aiohm-suggestion-btn" data-question="' + suggestion + '">';
                html += suggestion;
                html += '</button>';
            });
            
            html += '</div>';
            
            return html;
        },
        
        /**
         * Auto-scroll to bottom
         */
        scrollToBottom: function() {
            if (this.config.settings && this.config.settings.auto_scroll) {
                this.$messages.scrollTop(this.$messages[0].scrollHeight);
            }
        },
        
        /**
         * Auto-resize textarea
         */
        autoResizeTextarea: function(textarea) {
            var $textarea = $(textarea);
            var maxRows = parseInt($textarea.data('max-rows')) || 4;
            var lineHeight = parseInt($textarea.css('line-height'));
            
            $textarea.css('height', 'auto');
            var scrollHeight = textarea.scrollHeight;
            var maxHeight = lineHeight * maxRows;
            
            $textarea.css('height', Math.min(scrollHeight, maxHeight) + 'px');
        },
        
        /**
         * Copy text to clipboard
         */
        copyToClipboard: function(text) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(function() {
                    // Show copied feedback
                });
            } else {
                // Fallback for older browsers
                var textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
            }
        },
        
        /**
         * Play notification sound
         */
        playNotificationSound: function() {
            if (typeof Audio !== 'undefined') {
                var audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IAAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmAcBjuD0/DAcSECKG/E7d6SQAoWWK/k5KhYFAhZk+jwzHoA');
                audio.volume = 0.1;
                audio.play().catch(function() {
                    // Ignore errors
                });
            }
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
            return '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M12 1v6m0 6v6"></path><path d="m9 9 3 3 3-3"></path></svg>';
        },
        
        /**
         * Save conversation to localStorage
         */
        saveConversation: function() {
            try {
                localStorage.setItem('aiohm_chat_' + this.chatId, JSON.stringify(this.conversationHistory));
            } catch (e) {
                // Ignore storage errors
            }
        },
        
        /**
         * Load conversation from localStorage
         */
        loadConversation: function() {
            try {
                var stored = localStorage.getItem('aiohm_chat_' + this.chatId);
                if (stored) {
                    this.conversationHistory = JSON.parse(stored);
                }
            } catch (e) {
                this.conversationHistory = [];
            }
        },
        
        /**
         * Restore conversation in chat
         */
        restoreConversation: function() {
            var self = this;
            this.conversationHistory.forEach(function(message) {
                var messageHtml = self.createMessageHtml(message.content, message.type, message.timestamp, message.metadata);
                self.$messages.append(messageHtml);
            });
            this.scrollToBottom();
        }
    };
    
    /**
     * Search Instance Class
     */
    function SearchInstance(searchId, config) {
        this.searchId = searchId;
        this.config = config;
        this.$container = $('#' + searchId);
        this.$input = this.$container.find('.aiohm-search-input');
        this.$searchBtn = this.$container.find('.aiohm-search-btn');
        this.$results = this.$container.find('.aiohm-search-results');
        this.$status = this.$container.find('.aiohm-search-status');
        this.$typeFilter = this.$container.find('.aiohm-content-type-filter');
        
        this.searchTimeout = null;
        this.currentRequest = null;
    }
    
    SearchInstance.prototype = {
        /**
         * Initialize search instance
         */
        init: function() {
            this.bindEvents();
        },
        
        /**
         * Bind event listeners
         */
        bindEvents: function() {
            var self = this;
            
            // Search input
            this.$input.on('input', function() {
                if (self.config.settings.enable_instant_search) {
                    self.handleInstantSearch();
                }
            });
            
            this.$input.on('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    self.performSearch();
                }
            });
            
            // Search button
            this.$searchBtn.on('click', function() {
                self.performSearch();
            });
            
            // Type filter
            this.$typeFilter.on('change', function() {
                if (self.$input.val().trim()) {
                    self.performSearch();
                }
            });
        },
        
        /**
         * Handle instant search with debouncing
         */
        handleInstantSearch: function() {
            var self = this;
            
            clearTimeout(this.searchTimeout);
            
            this.searchTimeout = setTimeout(function() {
                var query = self.$input.val().trim();
                if (query.length >= self.config.settings.min_chars) {
                    self.performSearch();
                } else if (query.length === 0) {
                    self.showPlaceholder();
                }
            }, 300);
        },
        
        /**
         * Perform search
         */
        performSearch: function() {
            var query = this.$input.val().trim();
            
            if (!query) {
                this.showPlaceholder();
                return;
            }
            
            if (query.length < this.config.settings.min_chars) {
                return;
            }
            
            this.showLoading();
            
            // Cancel any existing request
            if (this.currentRequest) {
                this.currentRequest.abort();
            }
            
            var self = this;
            
            this.currentRequest = $.ajax({
                url: this.config.ajax_url,
                type: 'POST',
                data: {
                    action: 'aiohm_search_knowledge',
                    query: query,
                    content_type: this.$typeFilter.val(),
                    max_results: this.config.settings.max_results,
                    excerpt_length: this.config.settings.excerpt_length,
                    nonce: this.config.nonce
                },
                success: function(response) {
                    self.handleSearchResults(response);
                },
                error: function(xhr, status, error) {
                    self.handleSearchError(status, error);
                },
                complete: function() {
                    self.currentRequest = null;
                    self.hideLoading();
                }
            });
        },
        
        /**
         * Handle search results
         */
        handleSearchResults: function(response) {
            if (response.success && response.data) {
                this.displayResults(response.data.results, response.data.total_count);
            } else {
                this.displayError(response.data || this.config.strings.error);
            }
        },
        
        /**
         * Handle search error
         */
        handleSearchError: function(status, error) {
            if (status !== 'abort') {
                this.displayError(this.config.strings.error);
            }
        },
        
        /**
         * Display search results
         */
        displayResults: function(results, totalCount) {
            var html = '';
            
            if (results.length === 0) {
                html = '<div class="aiohm-no-results">';
                html += '<div class="aiohm-no-results-icon">🔍</div>';
                html += '<p>' + this.config.strings.no_results + '</p>';
                html += '</div>';
            } else {
                if (this.config.settings.show_results_count) {
                    html += '<div class="aiohm-results-count">';
                    html += this.config.strings.results_count.replace('%d', totalCount);
                    html += '</div>';
                }
                
                html += '<div class="aiohm-results-list">';
                
                results.forEach(function(result) {
                    html += '<div class="aiohm-search-result">';
                    html += '<div class="aiohm-result-header">';
                    
                    if (result.url) {
                        html += '<h3 class="aiohm-result-title"><a href="' + result.url + '" target="_blank">' + result.title + '</a></h3>';
                    } else {
                        html += '<h3 class="aiohm-result-title">' + result.title + '</h3>';
                    }
                    
                    html += '<span class="aiohm-content-type-badge aiohm-type-' + result.content_type + '">';
                    html += result.content_type.charAt(0).toUpperCase() + result.content_type.slice(1);
                    html += '</span>';
                    
                    html += '<div class="aiohm-similarity-score">';
                    html += '<span class="aiohm-score-label">Relevance:</span>';
                    html += '<span class="aiohm-score-value">' + result.similarity + '%</span>';
                    html += '</div>';
                    
                    html += '</div>';
                    html += '<div class="aiohm-result-content">';
                    html += '<p>' + result.excerpt + '</p>';
                    html += '</div>';
                    html += '</div>';
                });
                
                html += '</div>';
            }
            
            this.$results.html(html);
        },
        
        /**
         * Display error message
         */
        displayError: function(message) {
            var html = '<div class="aiohm-search-error">';
            html += '<div class="aiohm-error-icon">⚠️</div>';
            html += '<p>' + message + '</p>';
            html += '</div>';
            
            this.$results.html(html);
        },
        
        /**
         * Show placeholder state
         */
        showPlaceholder: function() {
            var html = '<div class="aiohm-search-placeholder">';
            html += '<div class="aiohm-search-icon">';
            html += '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">';
            html += '<circle cx="11" cy="11" r="8"></circle>';
            html += '<path d="m21 21-4.35-4.35"></path>';
            html += '</svg>';
            html += '</div>';
            html += '<p>Enter your search query to find relevant content from our knowledge base.</p>';
            html += '</div>';
            
            this.$results.html(html);
        },
        
        /**
         * Show loading state
         */
        showLoading: function() {
            this.$status.show().find('.aiohm-search-loading').text(this.config.strings.searching);
        },
        
        /**
         * Hide loading state
         */
        hideLoading: function() {
            this.$status.hide();
        }
    };
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        // Auto-initialize if global config is available
        if (typeof aiohm_config !== 'undefined') {
            window.AIOHM_Chat.config = aiohm_config;
        }
    });
    
})(jQuery);
