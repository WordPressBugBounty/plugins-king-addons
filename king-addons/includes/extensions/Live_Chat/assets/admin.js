/**
 * Live Chat Admin JavaScript
 *
 * Handles inbox functionality and conversation management.
 *
 * @package King_Addons
 */

(function($) {
    'use strict';

    /**
     * Live Chat Admin Module
     */
    const KingLiveChatAdmin = {
        /**
         * Current state
         */
        state: {
            currentFilter: 'all',
            currentSearch: '',
            currentConversation: null,
            conversations: [],
            refreshInterval: null,
            lastMessageId: 0
        },

        /**
         * DOM elements cache
         */
        elements: {},

        /**
         * Initialize the module
         */
        init: function() {
            this.cacheElements();
            this.bindEvents();
            this.loadConversations();
            this.initColorPickers();
            this.startAutoRefresh();
            this.checkUrlParams();
        },

        /**
         * Cache DOM elements
         */
        cacheElements: function() {
            this.elements = {
                conversationsList: $('#ka-inbox-conversations'),
                conversationView: $('#ka-conversation-view'),
                filters: $('.ka-inbox-filter'),
                searchInput: $('.ka-inbox-search')
            };
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            const self = this;

            // Filter clicks
            this.elements.filters.on('click', function() {
                self.elements.filters.removeClass('active');
                $(this).addClass('active');
                self.state.currentFilter = $(this).data('status');
                self.loadConversations();
            });

            // Search input
            let searchTimeout;
            this.elements.searchInput.on('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    self.state.currentSearch = self.elements.searchInput.val();
                    self.loadConversations();
                }, 300);
            });

            // Conversation item click (delegated)
            this.elements.conversationsList.on('click', '.ka-inbox-item', function() {
                const id = $(this).data('id');
                self.loadConversation(id);
            });

            // Reply form submit (delegated)
            $(document).on('submit', '.ka-reply-form', function(e) {
                e.preventDefault();
                self.sendReply();
            });

            // Status toggle (delegated)
            $(document).on('click', '.ka-toggle-status', function() {
                const newStatus = $(this).data('status');
                self.updateStatus(newStatus);
            });

            // Delete conversation (delegated)
            $(document).on('click', '.ka-delete-conversation', function() {
                if (confirm(kingLiveChatAdmin.strings.confirmDelete)) {
                    self.deleteConversation();
                }
            });

            // Textarea enter to send
            $(document).on('keydown', '.ka-reply-textarea', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    self.sendReply();
                }
            });
        },

        /**
         * Check URL parameters for direct conversation link
         */
        checkUrlParams: function() {
            const urlParams = new URLSearchParams(window.location.search);
            const conversationId = urlParams.get('conversation');
            if (conversationId) {
                this.loadConversation(parseInt(conversationId, 10));
            }
        },

        /**
         * Initialize color pickers
         */
        initColorPickers: function() {
            if ($.fn.wpColorPicker) {
                $('.ka-color-picker').wpColorPicker();
            }
        },

        /**
         * Start auto-refresh for inbox
         */
        startAutoRefresh: function() {
            const self = this;
            this.state.refreshInterval = setInterval(function() {
                self.loadConversations(true);
                if (self.state.currentConversation) {
                    self.pollNewMessages();
                }
            }, 10000);
        },

        /**
         * Load conversations list
         *
         * @param {boolean} silent Don't show loading state
         */
        loadConversations: function(silent) {
            const self = this;

            if (!silent) {
                this.elements.conversationsList.html('<div class="ka-loading"><div class="ka-loading-spinner"></div></div>');
            }

            $.ajax({
                url: kingLiveChatAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'king_live_chat_get_conversations',
                    nonce: kingLiveChatAdmin.nonce,
                    status: this.state.currentFilter,
                    search: this.state.currentSearch
                },
                success: function(response) {
                    if (response.success) {
                        self.state.conversations = response.data.conversations;
                        self.renderConversationsList();
                    }
                },
                error: function() {
                    self.elements.conversationsList.html(
                        '<div class="ka-inbox-empty">' + kingLiveChatAdmin.strings.error + '</div>'
                    );
                }
            });
        },

        /**
         * Render conversations list
         */
        renderConversationsList: function() {
            const self = this;
            const conversations = this.state.conversations;

            if (!conversations.length) {
                this.elements.conversationsList.html(
                    '<div class="ka-inbox-empty">No conversations found</div>'
                );
                return;
            }

            let html = '';
            conversations.forEach(function(conv) {
                const initials = self.getInitials(conv.name);
                const isActive = self.state.currentConversation === conv.id;
                const isUnread = conv.unread > 0;
                const timeAgo = self.formatTimeAgo(conv.last_message);

                html += `
                    <div class="ka-inbox-item ${isActive ? 'active' : ''} ${isUnread ? 'unread' : ''}" data-id="${conv.id}">
                        <div class="ka-inbox-avatar">${initials}</div>
                        <div class="ka-inbox-item-content">
                            <div class="ka-inbox-item-header">
                                <span class="ka-inbox-item-name">
                                    ${self.escapeHtml(conv.name)}
                                    ${isUnread ? '<span class="ka-inbox-badge">' + conv.unread + '</span>' : ''}
                                </span>
                                <span class="ka-inbox-item-time">${timeAgo}</span>
                            </div>
                            <div class="ka-inbox-item-preview">${self.escapeHtml(conv.email || 'No email')}</div>
                        </div>
                    </div>
                `;
            });

            this.elements.conversationsList.html(html);
        },

        /**
         * Load single conversation
         *
         * @param {number} id Conversation ID
         */
        loadConversation: function(id) {
            const self = this;
            this.state.currentConversation = id;

            // Update active state in list
            this.elements.conversationsList.find('.ka-inbox-item').removeClass('active');
            this.elements.conversationsList.find('[data-id="' + id + '"]').addClass('active').removeClass('unread');

            this.elements.conversationView.html('<div class="ka-loading"><div class="ka-loading-spinner"></div></div>');

            $.ajax({
                url: kingLiveChatAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'king_live_chat_get_conversation',
                    nonce: kingLiveChatAdmin.nonce,
                    conversation_id: id
                },
                success: function(response) {
                    if (response.success) {
                        self.renderConversation(response.data);
                        // Update last message ID for polling
                        const messages = response.data.messages;
                        if (messages.length) {
                            self.state.lastMessageId = messages[messages.length - 1].id;
                        }
                    } else {
                        self.elements.conversationView.html(
                            '<div class="ka-conversation-empty"><p>' + kingLiveChatAdmin.strings.error + '</p></div>'
                        );
                    }
                }
            });
        },

        /**
         * Render conversation view
         *
         * @param {Object} data Conversation data
         */
        renderConversation: function(data) {
            const self = this;
            const conv = data.conversation;
            const messages = data.messages;
            const initials = this.getInitials(conv.name || 'Anonymous');
            const isOpen = conv.status === 'open';

            let messagesHtml = '';
            messages.forEach(function(msg) {
                const isAdmin = msg.type === 'admin';
                const time = self.formatTime(msg.time);
                messagesHtml += `
                    <div class="ka-message ka-message--${msg.type}">
                        <div class="ka-message-text">${self.escapeHtml(msg.text)}</div>
                        <div class="ka-message-meta">
                            ${isAdmin && msg.admin_name ? msg.admin_name + ' · ' : ''}${time}
                        </div>
                    </div>
                `;
            });

            if (!messages.length) {
                messagesHtml = '<div class="ka-inbox-empty">' + kingLiveChatAdmin.strings.noMessages + '</div>';
            }

            const html = `
                <div class="ka-conversation-header">
                    <div class="ka-conversation-info">
                        <div class="ka-inbox-avatar">${initials}</div>
                        <div class="ka-conversation-details">
                            <h3>${self.escapeHtml(conv.name || 'Anonymous')}</h3>
                            <div class="ka-conversation-email">${self.escapeHtml(conv.email || 'No email')}</div>
                        </div>
                    </div>
                    <div class="ka-conversation-actions">
                        <button type="button" class="ka-conversation-btn ka-toggle-status" data-status="${isOpen ? 'closed' : 'open'}">
                            <span class="dashicons dashicons-${isOpen ? 'no' : 'yes'}"></span>
                            ${isOpen ? 'Close' : 'Reopen'}
                        </button>
                        <button type="button" class="ka-conversation-btn ka-conversation-btn--danger ka-delete-conversation">
                            <span class="dashicons dashicons-trash"></span>
                            Delete
                        </button>
                    </div>
                </div>
                <div class="ka-conversation-messages">
                    ${messagesHtml}
                </div>
                <div class="ka-conversation-reply">
                    <form class="ka-reply-form">
                        <textarea class="ka-reply-textarea" placeholder="Type your reply..." rows="3"></textarea>
                        <button type="submit" class="ka-reply-send">${kingLiveChatAdmin.strings.send}</button>
                    </form>
                </div>
                <div class="ka-visitor-info">
                    <h4>Visitor Info</h4>
                    <div class="ka-visitor-info-item">
                        <span class="ka-visitor-info-label">Status:</span>
                        <span class="ka-visitor-info-value ka-status-${conv.status}">${conv.status}</span>
                    </div>
                    ${conv.page_url ? `
                    <div class="ka-visitor-info-item">
                        <span class="ka-visitor-info-label">Page:</span>
                        <span class="ka-visitor-info-value"><a href="${conv.page_url}" target="_blank">${self.truncateUrl(conv.page_url)}</a></span>
                    </div>
                    ` : ''}
                    ${conv.referrer ? `
                    <div class="ka-visitor-info-item">
                        <span class="ka-visitor-info-label">Referrer:</span>
                        <span class="ka-visitor-info-value">${self.truncateUrl(conv.referrer)}</span>
                    </div>
                    ` : ''}
                    <div class="ka-visitor-info-item">
                        <span class="ka-visitor-info-label">Started:</span>
                        <span class="ka-visitor-info-value">${self.formatTime(conv.created)}</span>
                    </div>
                </div>
            `;

            this.elements.conversationView.html(html);

            // Scroll to bottom
            const messagesEl = this.elements.conversationView.find('.ka-conversation-messages');
            messagesEl.scrollTop(messagesEl[0].scrollHeight);
        },

        /**
         * Send admin reply
         */
        sendReply: function() {
            const self = this;
            const textarea = this.elements.conversationView.find('.ka-reply-textarea');
            const sendBtn = this.elements.conversationView.find('.ka-reply-send');
            const message = textarea.val().trim();

            if (!message || !this.state.currentConversation) {
                return;
            }

            sendBtn.prop('disabled', true).text(kingLiveChatAdmin.strings.sending);

            $.ajax({
                url: kingLiveChatAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'king_live_chat_send_reply',
                    nonce: kingLiveChatAdmin.nonce,
                    conversation_id: this.state.currentConversation,
                    message: message
                },
                success: function(response) {
                    if (response.success) {
                        // Add message to view
                        const msg = response.data.message;
                        const messagesEl = self.elements.conversationView.find('.ka-conversation-messages');
                        
                        const msgHtml = `
                            <div class="ka-message ka-message--admin">
                                <div class="ka-message-text">${self.escapeHtml(msg.text)}</div>
                                <div class="ka-message-meta">
                                    ${msg.admin_name ? msg.admin_name + ' · ' : ''}${self.formatTime(msg.time)}
                                </div>
                            </div>
                        `;
                        
                        messagesEl.find('.ka-inbox-empty').remove();
                        messagesEl.append(msgHtml);
                        messagesEl.scrollTop(messagesEl[0].scrollHeight);
                        
                        textarea.val('');
                        self.state.lastMessageId = msg.id;
                    } else {
                        alert(kingLiveChatAdmin.strings.error);
                    }
                },
                error: function() {
                    alert(kingLiveChatAdmin.strings.error);
                },
                complete: function() {
                    sendBtn.prop('disabled', false).text(kingLiveChatAdmin.strings.send);
                }
            });
        },

        /**
         * Poll for new messages in current conversation
         */
        pollNewMessages: function() {
            const self = this;

            if (!this.state.currentConversation) {
                return;
            }

            $.ajax({
                url: kingLiveChatAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'king_live_chat_get_conversation',
                    nonce: kingLiveChatAdmin.nonce,
                    conversation_id: this.state.currentConversation
                },
                success: function(response) {
                    if (response.success) {
                        const messages = response.data.messages;
                        if (messages.length) {
                            const lastMsg = messages[messages.length - 1];
                            if (lastMsg.id > self.state.lastMessageId) {
                                // New messages, reload view
                                self.renderConversation(response.data);
                                self.state.lastMessageId = lastMsg.id;
                            }
                        }
                    }
                }
            });
        },

        /**
         * Update conversation status
         *
         * @param {string} status New status
         */
        updateStatus: function(status) {
            const self = this;

            $.ajax({
                url: kingLiveChatAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'king_live_chat_update_status',
                    nonce: kingLiveChatAdmin.nonce,
                    conversation_id: this.state.currentConversation,
                    status: status
                },
                success: function(response) {
                    if (response.success) {
                        self.loadConversation(self.state.currentConversation);
                        self.loadConversations(true);
                    }
                }
            });
        },

        /**
         * Delete conversation
         */
        deleteConversation: function() {
            const self = this;

            $.ajax({
                url: kingLiveChatAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'king_live_chat_delete_conversation',
                    nonce: kingLiveChatAdmin.nonce,
                    conversation_id: this.state.currentConversation
                },
                success: function(response) {
                    if (response.success) {
                        self.state.currentConversation = null;
                        self.elements.conversationView.html(`
                            <div class="ka-conversation-empty">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
                                </svg>
                                <p>Select a conversation to view messages</p>
                            </div>
                        `);
                        self.loadConversations();
                    }
                }
            });
        },

        /**
         * Get initials from name
         *
         * @param {string} name Full name
         * @returns {string}
         */
        getInitials: function(name) {
            if (!name) return '?';
            const parts = name.trim().split(' ');
            if (parts.length >= 2) {
                return (parts[0][0] + parts[1][0]).toUpperCase();
            }
            return name.substring(0, 2).toUpperCase();
        },

        /**
         * Format time for display
         *
         * @param {string} dateStr Date string
         * @returns {string}
         */
        formatTime: function(dateStr) {
            if (!dateStr) return '';
            const date = new Date(dateStr.replace(' ', 'T'));
            return date.toLocaleString();
        },

        /**
         * Format time ago
         *
         * @param {string} dateStr Date string
         * @returns {string}
         */
        formatTimeAgo: function(dateStr) {
            if (!dateStr) return '';
            const date = new Date(dateStr.replace(' ', 'T'));
            const now = new Date();
            const diff = Math.floor((now - date) / 1000);

            if (diff < 60) return 'Just now';
            if (diff < 3600) return Math.floor(diff / 60) + 'm';
            if (diff < 86400) return Math.floor(diff / 3600) + 'h';
            if (diff < 604800) return Math.floor(diff / 86400) + 'd';
            return date.toLocaleDateString();
        },

        /**
         * Truncate URL for display
         *
         * @param {string} url Full URL
         * @returns {string}
         */
        truncateUrl: function(url) {
            if (!url) return '';
            try {
                const parsed = new URL(url);
                let path = parsed.pathname;
                if (path.length > 30) {
                    path = path.substring(0, 30) + '...';
                }
                return parsed.host + path;
            } catch (e) {
                return url.substring(0, 40) + (url.length > 40 ? '...' : '');
            }
        },

        /**
         * Escape HTML entities
         *
         * @param {string} str Input string
         * @returns {string}
         */
        escapeHtml: function(str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }
    };

    // Initialize on DOM ready
    $(function() {
        if ($('#ka-inbox-conversations').length) {
            KingLiveChatAdmin.init();
        } else {
            // Settings page - just init color pickers
            if ($.fn.wpColorPicker) {
                $('.ka-color-picker').wpColorPicker();
            }
        }
    });

})(jQuery);
