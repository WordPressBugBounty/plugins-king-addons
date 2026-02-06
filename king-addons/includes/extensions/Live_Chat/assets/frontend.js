/**
 * Live Chat Frontend JavaScript
 *
 * Handles chat widget functionality, messaging, and polling.
 *
 * @package King_Addons
 */

(function() {
    'use strict';

    /**
     * Live Chat Widget Module
     */
    const KingLiveChat = {
        /**
         * Current state
         */
        state: {
            isOpen: false,
            isChatting: false,
            conversationId: null,
            visitorId: null,
            visitorName: '',
            visitorEmail: '',
            messages: [],
            lastMessageId: 0,
            pollInterval: null,
            isLoading: false,
            hasError: false,
            mode: 'live_chat' // live_chat or contact_form
        },

        /**
         * Configuration
         */
        config: {},

        /**
         * Strings for i18n
         */
        strings: {},

        /**
         * DOM elements cache
         */
        elements: {},

        /**
         * Initialize the widget
         */
        init: function() {
            // Get config from global
            if (typeof kingLiveChat === 'undefined') {
                console.error('KingLiveChat: Config not found');
                return;
            }

            this.config = kingLiveChat;
            this.strings = kingLiveChat.strings || {};
            this.state.visitorId = kingLiveChat.visitorId;
            this.state.mode = kingLiveChat.widgetMode || 'live_chat';

            this.cacheElements();
            
            if (!this.elements.container) {
                console.error('KingLiveChat: Widget container not found');
                return;
            }

            this.bindEvents();
            this.initVisitorId();
            
            // Only restore session for live chat mode
            if (this.state.mode === 'live_chat') {
                this.restoreSession();
            }
        },

        /**
         * Cache DOM elements
         */
        cacheElements: function() {
            this.elements = {
                container: document.getElementById('ka-live-chat'),
                button: document.querySelector('.ka-live-chat__button'),
                panel: document.querySelector('.ka-live-chat__panel'),
                closeBtn: document.querySelector('.ka-live-chat__close'),
                prechat: document.querySelector('.ka-live-chat__prechat'),
                nameInput: document.getElementById('ka-chat-name'),
                emailInput: document.getElementById('ka-chat-email'),
                startBtn: document.querySelector('.ka-live-chat__start'),
                messagesContainer: document.querySelector('.ka-live-chat__messages'),
                messagesList: document.querySelector('.ka-live-chat__messages-list'),
                inputArea: document.querySelector('.ka-live-chat__input'),
                textarea: document.querySelector('.ka-live-chat__input textarea'),
                sendBtn: document.querySelector('.ka-live-chat__send'),
                badge: document.querySelector('.ka-live-chat__badge'),
                honeypot: document.querySelector('.ka-live-chat__hp input'),
                // Contact Form elements
                contactForm: document.querySelector('.ka-live-chat__contact-form'),
                subjectInput: document.getElementById('ka-chat-subject'),
                messageTextarea: document.getElementById('ka-chat-message'),
                submitBtn: document.querySelector('.ka-live-chat__submit'),
                successScreen: document.querySelector('.ka-live-chat__success'),
                newMessageBtn: document.querySelector('.ka-live-chat__new-message')
            };
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            const self = this;

            // Toggle chat
            this.elements.button.addEventListener('click', function() {
                self.toggle();
            });

            // Close button
            if (this.elements.closeBtn) {
                this.elements.closeBtn.addEventListener('click', function() {
                    self.close();
                });
            }

            // Start chat (Live Chat mode)
            if (this.elements.startBtn) {
                this.elements.startBtn.addEventListener('click', function() {
                    self.startChat();
                });
            }

            // Send message (Live Chat mode)
            if (this.elements.sendBtn) {
                this.elements.sendBtn.addEventListener('click', function() {
                    self.sendMessage();
                });
            }

            // Enter to send (Live Chat mode)
            if (this.elements.textarea) {
                this.elements.textarea.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        self.sendMessage();
                    }
                });

                // Auto-resize textarea
                this.elements.textarea.addEventListener('input', function() {
                    this.style.height = 'auto';
                    this.style.height = Math.min(this.scrollHeight, 100) + 'px';
                });
            }

            // Pre-chat form validation (Live Chat mode)
            if (this.elements.nameInput) {
                this.elements.nameInput.addEventListener('input', function() {
                    if (self.state.mode === 'live_chat') {
                        self.validatePrechat();
                    } else {
                        self.validateContactForm();
                    }
                });
            }

            if (this.elements.emailInput) {
                this.elements.emailInput.addEventListener('input', function() {
                    if (self.state.mode === 'live_chat') {
                        self.validatePrechat();
                    } else {
                        self.validateContactForm();
                    }
                });
            }

            // Contact Form mode events
            if (this.elements.submitBtn) {
                this.elements.submitBtn.addEventListener('click', function() {
                    self.submitContactForm();
                });
            }

            if (this.elements.messageTextarea) {
                this.elements.messageTextarea.addEventListener('input', function() {
                    self.validateContactForm();
                });
            }

            if (this.elements.newMessageBtn) {
                this.elements.newMessageBtn.addEventListener('click', function() {
                    self.resetContactForm();
                });
            }

            // Close on escape
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && self.state.isOpen) {
                    self.close();
                }
            });
        },

        /**
         * Initialize or get visitor ID from cookie
         */
        initVisitorId: function() {
            const cookieName = 'king_support_vid';
            let visitorId = this.getCookie(cookieName);

            if (!visitorId) {
                visitorId = this.state.visitorId || this.generateUUID();
                this.setCookie(cookieName, visitorId, 365);
            }

            this.state.visitorId = visitorId;
        },

        /**
         * Restore session from previous visit
         */
        restoreSession: function() {
            const self = this;
            const sessionData = localStorage.getItem('ka_chat_session');

            // Avoid background REST calls on every page load.
            // Only attempt to restore if a session already exists.
            if (!sessionData) {
                return;
            }

            if (sessionData) {
                try {
                    const data = JSON.parse(sessionData);
                    this.state.visitorName = data.name || '';
                    this.state.visitorEmail = data.email || '';
                    
                    if (data.conversationId) {
                        this.state.conversationId = data.conversationId;
                    }
                } catch (e) {
                    localStorage.removeItem('ka_chat_session');
                }
            }

            // Try to init conversation
            this.initConversation().then(function(data) {
                if (data.conversation_id) {
                    self.state.conversationId = data.conversation_id;
                    self.state.isChatting = true;
                    self.state.messages = data.messages || [];
                    self.state.lastMessageId = data.messages.length ? data.messages[data.messages.length - 1].id : 0;
                    
                    self.elements.container.classList.add('ka-live-chat--chatting');
                    self.renderMessages();
                    self.updateBadge(data.unread || 0);
                }
            });
        },

        /**
         * Toggle chat panel
         */
        toggle: function() {
            if (this.state.isOpen) {
                this.close();
            } else {
                this.open();
            }
        },

        /**
         * Open chat panel
         */
        open: function() {
            this.state.isOpen = true;
            this.elements.container.classList.add('ka-live-chat--open');

            // Mark messages as read
            if (this.state.conversationId) {
                this.markAsRead();
            }

            // Start polling
            this.startPolling();

            // Focus appropriate element
            if (this.state.isChatting) {
                this.elements.textarea.focus();
                this.scrollToBottom();
            } else if (this.elements.nameInput) {
                this.elements.nameInput.focus();
            }
        },

        /**
         * Close chat panel
         */
        close: function() {
            this.state.isOpen = false;
            this.elements.container.classList.remove('ka-live-chat--open');
            this.stopPolling();
        },

        /**
         * Validate pre-chat form
         */
        validatePrechat: function() {
            const requireName = this.config.options.requireName;
            const requireEmail = this.config.options.requireEmail;
            
            let isValid = true;

            if (requireName && this.elements.nameInput) {
                isValid = isValid && this.elements.nameInput.value.trim().length > 0;
            }

            if (requireEmail && this.elements.emailInput) {
                const email = this.elements.emailInput.value.trim();
                isValid = isValid && this.isValidEmail(email);
            }

            this.elements.startBtn.disabled = !isValid;
        },

        /**
         * Start chat (after pre-chat form)
         */
        startChat: function() {
            const self = this;

            // Get values
            if (this.elements.nameInput) {
                this.state.visitorName = this.elements.nameInput.value.trim();
            }
            if (this.elements.emailInput) {
                this.state.visitorEmail = this.elements.emailInput.value.trim();
            }

            // Save to session
            this.saveSession();

            // Show chat UI
            this.state.isChatting = true;
            this.elements.container.classList.add('ka-live-chat--chatting');

            // Add welcome message
            if (this.strings.welcomeMessage) {
                this.addMessage({
                    type: 'welcome',
                    text: this.strings.welcomeMessage,
                    time: new Date().toISOString()
                });
            }

            // Focus textarea
            this.elements.textarea.focus();
        },

        /**
         * Send message
         */
        sendMessage: function() {
            const self = this;
            const text = this.elements.textarea.value.trim();

            if (!text || this.state.isLoading) {
                return;
            }

            // Check honeypot
            if (this.elements.honeypot && this.elements.honeypot.value) {
                console.warn('KingLiveChat: Spam detected');
                return;
            }

            this.state.isLoading = true;
            this.elements.sendBtn.disabled = true;

            // Optimistically add message
            const tempMsg = {
                id: 'temp-' + Date.now(),
                type: 'visitor',
                text: text,
                time: new Date().toISOString(),
                pending: true
            };
            this.addMessage(tempMsg);
            this.elements.textarea.value = '';
            this.elements.textarea.style.height = 'auto';

            // Send to server
            this.apiRequest('message/send', {
                visitor_id: this.state.visitorId,
                conversation_id: this.state.conversationId,
                message: text,
                name: this.state.visitorName,
                email: this.state.visitorEmail,
                page_url: window.location.href,
                referrer: document.referrer,
                website: this.elements.honeypot ? this.elements.honeypot.value : ''
            }).then(function(data) {
                if (data.success) {
                    // Update conversation ID if new
                    if (!self.state.conversationId) {
                        self.state.conversationId = data.conversation_id;
                        self.saveSession();
                    }

                    // Update temp message
                    const tempEl = self.elements.messagesList.querySelector('[data-id="' + tempMsg.id + '"]');
                    if (tempEl) {
                        tempEl.dataset.id = data.message_id;
                        tempEl.classList.remove('ka-live-chat__message--pending');
                    }

                    self.state.lastMessageId = data.message_id;
                } else if (data.error === 'rate_limit') {
                    self.showError(self.strings.errorRateLimit);
                    // Remove temp message
                    self.removeMessage(tempMsg.id);
                } else {
                    self.showError(self.strings.errorNetwork);
                    self.removeMessage(tempMsg.id);
                }
            }).catch(function() {
                self.showError(self.strings.errorNetwork);
                self.removeMessage(tempMsg.id);
            }).finally(function() {
                self.state.isLoading = false;
                self.elements.sendBtn.disabled = false;
                self.elements.textarea.focus();
            });
        },

        /**
         * Add message to UI
         *
         * @param {Object} msg Message object
         */
        addMessage: function(msg) {
            this.state.messages.push(msg);

            const msgEl = document.createElement('div');
            msgEl.className = 'ka-live-chat__message ka-live-chat__message--' + msg.type;
            if (msg.pending) {
                msgEl.classList.add('ka-live-chat__message--pending');
            }
            msgEl.dataset.id = msg.id;

            const textEl = document.createElement('div');
            textEl.className = 'ka-live-chat__message-text';
            textEl.textContent = msg.text;
            msgEl.appendChild(textEl);

            const timeEl = document.createElement('div');
            timeEl.className = 'ka-live-chat__message-time';
            timeEl.textContent = this.formatTime(msg.time);
            msgEl.appendChild(timeEl);

            this.elements.messagesList.appendChild(msgEl);
            this.scrollToBottom();
        },

        /**
         * Remove message from UI
         *
         * @param {string|number} id Message ID
         */
        removeMessage: function(id) {
            const el = this.elements.messagesList.querySelector('[data-id="' + id + '"]');
            if (el) {
                el.remove();
            }
            this.state.messages = this.state.messages.filter(function(m) {
                return m.id !== id;
            });
        },

        /**
         * Render all messages
         */
        renderMessages: function() {
            const self = this;
            this.elements.messagesList.innerHTML = '';

            // Add welcome message first if chatting
            if (this.state.isChatting && this.strings.welcomeMessage && !this.state.messages.length) {
                this.addMessage({
                    type: 'welcome',
                    text: this.strings.welcomeMessage,
                    time: new Date().toISOString()
                });
            }

            this.state.messages.forEach(function(msg) {
                self.addMessage(msg);
            });
        },

        /**
         * Scroll messages to bottom
         */
        scrollToBottom: function() {
            const container = this.elements.messagesContainer;
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        },

        /**
         * Start polling for new messages
         */
        startPolling: function() {
            if (this.state.pollInterval) {
                return;
            }

            const self = this;
            const interval = this.config.pollInterval || 4000;

            this.state.pollInterval = setInterval(function() {
                if (self.state.isOpen && self.state.conversationId) {
                    self.pollMessages();
                }
            }, interval);
        },

        /**
         * Stop polling
         */
        stopPolling: function() {
            if (this.state.pollInterval) {
                clearInterval(this.state.pollInterval);
                this.state.pollInterval = null;
            }
        },

        /**
         * Poll for new messages
         */
        pollMessages: function() {
            const self = this;

            this.apiRequest('messages/poll', {
                visitor_id: this.state.visitorId,
                conversation_id: this.state.conversationId,
                after_id: this.state.lastMessageId
            }, 'GET').then(function(data) {
                if (data.messages && data.messages.length) {
                    data.messages.forEach(function(msg) {
                        // Don't duplicate
                        const exists = self.state.messages.some(function(m) {
                            return m.id === msg.id;
                        });

                        if (!exists) {
                            self.addMessage(msg);
                            self.state.lastMessageId = msg.id;

                            // Update badge if closed
                            if (!self.state.isOpen && msg.type === 'admin') {
                                self.updateBadge((parseInt(self.elements.badge.textContent, 10) || 0) + 1);
                            }
                        }
                    });
                }
            });
        },

        /**
         * Mark messages as read
         */
        markAsRead: function() {
            this.updateBadge(0);

            this.apiRequest('messages/read', {
                visitor_id: this.state.visitorId,
                conversation_id: this.state.conversationId
            });
        },

        /**
         * Update unread badge
         *
         * @param {number} count Unread count
         */
        updateBadge: function(count) {
            if (this.elements.badge) {
                this.elements.badge.textContent = count;
                this.elements.badge.style.display = count > 0 ? 'flex' : 'none';
            }
        },

        /**
         * Initialize conversation via API
         *
         * @returns {Promise}
         */
        initConversation: function() {
            return this.apiRequest('conversation/init', {
                visitor_id: this.state.visitorId,
                name: this.state.visitorName,
                email: this.state.visitorEmail,
                page_url: window.location.href,
                referrer: document.referrer
            });
        },

        /**
         * Make API request
         *
         * @param {string} endpoint API endpoint
         * @param {Object} data Request data
         * @param {string} method HTTP method
         * @returns {Promise}
         */
        apiRequest: function(endpoint, data, method) {
            const self = this;
            method = method || 'POST';

            let url = this.config.restUrl + '/' + endpoint;

            const options = {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': this.config.nonce
                }
            };

            if (method === 'GET' && data) {
                const params = new URLSearchParams();
                Object.keys(data).forEach(function(key) {
                    if (data[key] !== undefined && data[key] !== null) {
                        params.append(key, data[key]);
                    }
                });
                url += '?' + params.toString();
            } else if (data) {
                options.body = JSON.stringify(data);
            }

            return fetch(url, options)
                .then(function(response) {
                    return response.json();
                })
                .catch(function(error) {
                    console.error('KingLiveChat API Error:', error);
                    throw error;
                });
        },

        /**
         * Show error message
         *
         * @param {string} message Error message
         */
        showError: function(message) {
            // Simple alert for now, could be improved
            console.error('KingLiveChat:', message);
        },

        /**
         * Save session to localStorage
         */
        saveSession: function() {
            const sessionData = {
                name: this.state.visitorName,
                email: this.state.visitorEmail,
                conversationId: this.state.conversationId
            };
            localStorage.setItem('ka_chat_session', JSON.stringify(sessionData));
        },

        /**
         * Format time for display
         *
         * @param {string} dateStr ISO date string
         * @returns {string}
         */
        formatTime: function(dateStr) {
            if (!dateStr) return '';
            
            const date = new Date(dateStr);
            const now = new Date();
            const diff = Math.floor((now - date) / 1000);

            if (diff < 60) {
                return this.strings.justNow || 'Just now';
            }

            if (diff < 3600) {
                return Math.floor(diff / 60) + 'm ago';
            }

            if (diff < 86400 && date.getDate() === now.getDate()) {
                return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            }

            return date.toLocaleDateString([], { month: 'short', day: 'numeric' }) + 
                   ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        },

        /**
         * Validate email format
         *
         * @param {string} email Email address
         * @returns {boolean}
         */
        isValidEmail: function(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        },

        /**
         * Generate UUID v4
         *
         * @returns {string}
         */
        generateUUID: function() {
            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
                const r = Math.random() * 16 | 0;
                const v = c === 'x' ? r : (r & 0x3 | 0x8);
                return v.toString(16);
            });
        },

        /**
         * Get cookie value
         *
         * @param {string} name Cookie name
         * @returns {string|null}
         */
        getCookie: function(name) {
            const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
            return match ? match[2] : null;
        },

        /**
         * Set cookie
         *
         * @param {string} name Cookie name
         * @param {string} value Cookie value
         * @param {number} days Days until expiry
         */
        setCookie: function(name, value, days) {
            const date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            document.cookie = name + '=' + value + ';expires=' + date.toUTCString() + ';path=/;SameSite=Lax';
        },

        /**
         * Validate contact form
         */
        validateContactForm: function() {
            const requireName = this.config.options.requireName;
            const requireEmail = this.config.options.requireEmail;
            
            let isValid = true;

            if (requireName && this.elements.nameInput) {
                isValid = isValid && this.elements.nameInput.value.trim().length > 0;
            }

            if (requireEmail && this.elements.emailInput) {
                const email = this.elements.emailInput.value.trim();
                isValid = isValid && this.isValidEmail(email);
            }

            if (this.elements.messageTextarea) {
                isValid = isValid && this.elements.messageTextarea.value.trim().length > 0;
            }

            if (this.elements.submitBtn) {
                this.elements.submitBtn.disabled = !isValid;
            }
        },

        /**
         * Submit contact form
         */
        submitContactForm: function() {
            const self = this;

            // Check honeypot
            if (this.elements.honeypot && this.elements.honeypot.value) {
                return;
            }

            // Get values
            const name = this.elements.nameInput ? this.elements.nameInput.value.trim() : '';
            const email = this.elements.emailInput ? this.elements.emailInput.value.trim() : '';
            const subject = this.elements.subjectInput ? this.elements.subjectInput.value.trim() : '';
            const message = this.elements.messageTextarea ? this.elements.messageTextarea.value.trim() : '';

            if (!message) {
                return;
            }

            // Disable submit
            if (this.elements.submitBtn) {
                this.elements.submitBtn.disabled = true;
                this.elements.submitBtn.textContent = this.strings.sending || 'Sending...';
            }

            // Send via REST API
            this.apiCall('/support/contact', 'POST', {
                visitor_id: this.state.visitorId,
                name: name,
                email: email,
                subject: subject,
                message: message,
                page_url: window.location.href,
                referrer: document.referrer
            }).then(function(data) {
                if (data.success) {
                    self.showSuccessScreen();
                } else {
                    self.showFormError(data.message || self.strings.errorNetwork);
                }
            }).catch(function(error) {
                self.showFormError(self.strings.errorNetwork);
            }).finally(function() {
                if (self.elements.submitBtn) {
                    self.elements.submitBtn.disabled = false;
                    self.elements.submitBtn.textContent = self.strings.submitButton || 'Send Message';
                }
            });
        },

        /**
         * Show success screen
         */
        showSuccessScreen: function() {
            if (this.elements.contactForm) {
                this.elements.contactForm.style.display = 'none';
            }
            if (this.elements.successScreen) {
                this.elements.successScreen.style.display = 'flex';
            }
        },

        /**
         * Reset contact form
         */
        resetContactForm: function() {
            if (this.elements.nameInput) {
                this.elements.nameInput.value = '';
            }
            if (this.elements.emailInput) {
                this.elements.emailInput.value = '';
            }
            if (this.elements.subjectInput) {
                this.elements.subjectInput.value = '';
            }
            if (this.elements.messageTextarea) {
                this.elements.messageTextarea.value = '';
            }
            
            if (this.elements.successScreen) {
                this.elements.successScreen.style.display = 'none';
            }
            if (this.elements.contactForm) {
                this.elements.contactForm.style.display = 'flex';
            }
            
            this.validateContactForm();
        },

        /**
         * Show form error
         *
         * @param {string} message Error message
         */
        showFormError: function(message) {
            // For now just alert, could be improved
            alert(message);
        }
    };

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            KingLiveChat.init();
        });
    } else {
        KingLiveChat.init();
    }

    // Expose globally for debugging
    window.KingLiveChat = KingLiveChat;

})();
