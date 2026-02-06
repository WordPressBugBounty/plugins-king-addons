/**
 * Activity Log - Admin JavaScript
 *
 * @package King_Addons
 */

(function ($) {
    'use strict';

    const ActivityLog = {
        state: {
            page: 1,
            perPage: 20,
            filters: {},
            loading: false
        },

        /**
         * Initialize the Activity Log module.
         */
        init: function () {
            this.bindEvents();

            // Load logs if on logs tab
            if ($('#ka-al-table').length) {
                this.loadLogs();
            }
        },

        /**
         * Bind all event handlers.
         */
        bindEvents: function () {
            // Filter events
            $('#ka-al-filter-apply').on('click', this.applyFilters.bind(this));
            $('#ka-al-filter-reset').on('click', this.resetFilters.bind(this));
            $('#ka-al-search').on('keypress', function (e) {
                if (e.which === 13) {
                    ActivityLog.applyFilters();
                }
            });

            // Pagination
            $(document).on('click', '.ka-al-page-btn', this.handlePagination.bind(this));
            $(document).on('click', '.ka-al-page-num', this.handlePageNumber.bind(this));

            // View event details
            $(document).on('click', '.ka-al-view-btn', this.openDrawer.bind(this));

            // Close drawer
            $('.ka-al-drawer-close, .ka-al-drawer-backdrop').on('click', this.closeDrawer.bind(this));
            $(document).on('keydown', function (e) {
                if (e.key === 'Escape') {
                    ActivityLog.closeDrawer();
                }
            });

            // Settings
            $('#ka-al-save-settings').on('click', this.saveSettings.bind(this));
            $('#ka-al-purge-logs').on('click', this.purgeLogs.bind(this));

            // Export
            $('#ka-al-export-btn').on('click', this.exportLogs.bind(this));
        },

        /**
         * Apply filters and reload logs.
         */
        applyFilters: function () {
            this.state.page = 1;
            this.state.filters = {
                search: $('#ka-al-search').val(),
                event_key: $('#ka-al-filter-event').val(),
                severity: $('#ka-al-filter-severity').val(),
                user_id: $('#ka-al-filter-user').val(),
                date_from: $('#ka-al-filter-date-from').val(),
                date_to: $('#ka-al-filter-date-to').val()
            };
            this.loadLogs();
        },

        /**
         * Reset all filters.
         */
        resetFilters: function () {
            $('#ka-al-search').val('');
            $('#ka-al-filter-event').val('');
            $('#ka-al-filter-severity').val('');
            $('#ka-al-filter-user').val('');
            $('#ka-al-filter-date-from').val('');
            $('#ka-al-filter-date-to').val('');
            this.state.filters = {};
            this.state.page = 1;
            this.loadLogs();
        },

        /**
         * Load logs via AJAX.
         */
        loadLogs: function () {
            if (this.state.loading) {
                return;
            }

            this.state.loading = true;
            const $tbody = $('#ka-al-table-body');

            // Show loading state
            $tbody.html(`
                <tr class="ka-al-loading">
                    <td colspan="7">
                        <div class="ka-al-spinner"></div>
                        ${kngActivityLog.i18n.loading}
                    </td>
                </tr>
            `);

            const data = {
                action: 'kng_activity_log_get_logs',
                nonce: kngActivityLog.nonce,
                page: this.state.page,
                per_page: this.state.perPage,
                ...this.state.filters
            };

            $.post(kngActivityLog.ajaxUrl, data, (response) => {
                this.state.loading = false;

                if (!response.success) {
                    $tbody.html(`
                        <tr>
                            <td colspan="7" class="ka-al-empty">${kngActivityLog.i18n.error}</td>
                        </tr>
                    `);
                    return;
                }

                this.renderLogs(response.data);
            }).fail(() => {
                this.state.loading = false;
                $tbody.html(`
                    <tr>
                        <td colspan="7" class="ka-al-empty">${kngActivityLog.i18n.error}</td>
                    </tr>
                `);
            });
        },

        /**
         * Render logs table.
         * 
         * @param {Object} data Response data with items, total, pages.
         */
        renderLogs: function (data) {
            const $tbody = $('#ka-al-table-body');

            if (!data.items || data.items.length === 0) {
                $tbody.html(`
                    <tr>
                        <td colspan="7" class="ka-al-empty">${kngActivityLog.i18n.noResults}</td>
                    </tr>
                `);
                this.renderPagination(0, 0);
                return;
            }

            let html = '';
            data.items.forEach((item) => {
                const date = new Date(item.created_at);
                const formattedDate = date.toLocaleDateString() + ' ' + date.toLocaleTimeString();

                html += `
                    <tr>
                        <td>${this.escapeHtml(formattedDate)}</td>
                        <td>${this.escapeHtml(item.event_label)}</td>
                        <td>
                            <span class="ka-al-severity ka-al-severity--${item.severity_class}">
                                ${this.escapeHtml(item.severity)}
                            </span>
                        </td>
                        <td>${this.escapeHtml(item.user_login || 'System')}</td>
                        <td>${this.escapeHtml(item.object_title || '-')}</td>
                        <td>${this.escapeHtml(item.ip || '-')}</td>
                        <td>
                            <button type="button" class="ka-al-view-btn" data-id="${item.id}">
                                View
                            </button>
                        </td>
                    </tr>
                `;
            });

            $tbody.html(html);
            this.renderPagination(data.total, data.pages);
        },

        /**
         * Render pagination controls.
         * 
         * @param {number} total Total items.
         * @param {number} pages Total pages.
         */
        renderPagination: function (total, pages) {
            const $pagination = $('#ka-al-pagination');
            const $info = $pagination.find('.ka-al-pagination-info');
            const $numbers = $pagination.find('.ka-al-page-numbers');
            const $prevBtn = $pagination.find('[data-page="prev"]');
            const $nextBtn = $pagination.find('[data-page="next"]');

            // Update info
            const start = ((this.state.page - 1) * this.state.perPage) + 1;
            const end = Math.min(this.state.page * this.state.perPage, total);
            $info.text(`Showing ${start}-${end} of ${total} events`);

            // Update prev/next buttons
            $prevBtn.prop('disabled', this.state.page <= 1);
            $nextBtn.prop('disabled', this.state.page >= pages);

            // Render page numbers
            let numbersHtml = '';
            const maxVisible = 5;
            let startPage = Math.max(1, this.state.page - Math.floor(maxVisible / 2));
            let endPage = Math.min(pages, startPage + maxVisible - 1);

            if (endPage - startPage < maxVisible - 1) {
                startPage = Math.max(1, endPage - maxVisible + 1);
            }

            for (let i = startPage; i <= endPage; i++) {
                numbersHtml += `
                    <button type="button" class="ka-al-page-num ${i === this.state.page ? 'active' : ''}" data-page="${i}">
                        ${i}
                    </button>
                `;
            }

            $numbers.html(numbersHtml);
        },

        /**
         * Handle pagination button click.
         * 
         * @param {Event} e Click event.
         */
        handlePagination: function (e) {
            const $btn = $(e.currentTarget);
            const action = $btn.data('page');

            if (action === 'prev' && this.state.page > 1) {
                this.state.page--;
                this.loadLogs();
            } else if (action === 'next') {
                this.state.page++;
                this.loadLogs();
            }
        },

        /**
         * Handle page number click.
         * 
         * @param {Event} e Click event.
         */
        handlePageNumber: function (e) {
            const page = parseInt($(e.currentTarget).data('page'), 10);
            if (page !== this.state.page) {
                this.state.page = page;
                this.loadLogs();
            }
        },

        /**
         * Open event details drawer.
         * 
         * @param {Event} e Click event.
         */
        openDrawer: function (e) {
            const id = $(e.currentTarget).data('id');
            const $drawer = $('#ka-al-drawer');
            const $body = $drawer.find('.ka-al-drawer-body');

            // Show loading
            $body.html(`
                <div style="text-align: center; padding: 56px;">
                    <div class="ka-al-spinner"></div>
                    <div style="margin-top: 16px; color: #8e8e93;">${kngActivityLog.i18n.loading}</div>
                </div>
            `);

            $drawer.addClass('open');
            $('body').css('overflow', 'hidden');

            // Load event details
            $.post(kngActivityLog.ajaxUrl, {
                action: 'kng_activity_log_get_event',
                nonce: kngActivityLog.nonce,
                id: id
            }, (response) => {
                if (!response.success) {
                    $body.html(`<p class="ka-al-empty">${kngActivityLog.i18n.error}</p>`);
                    return;
                }

                this.renderEventDetails(response.data, $body);
            });
        },

        /**
         * Render event details in drawer.
         * 
         * @param {Object} event Event data.
         * @param {jQuery} $container Container element.
         */
        renderEventDetails: function (event, $container) {
            const date = new Date(event.created_at);
            const formattedDate = date.toLocaleDateString() + ' ' + date.toLocaleTimeString();

            let html = `
                <div class="ka-al-detail-row">
                    <span class="ka-al-detail-label">Event</span>
                    <span class="ka-al-detail-value">${this.escapeHtml(event.event_label)}</span>
                </div>
                <div class="ka-al-detail-row">
                    <span class="ka-al-detail-label">Time</span>
                    <span class="ka-al-detail-value">${this.escapeHtml(formattedDate)}</span>
                </div>
                <div class="ka-al-detail-row">
                    <span class="ka-al-detail-label">Severity</span>
                    <span class="ka-al-detail-value">
                        <span class="ka-al-severity ka-al-severity--${event.severity_class}">
                            ${this.escapeHtml(event.severity_label)}
                        </span>
                    </span>
                </div>
                <div class="ka-al-detail-row">
                    <span class="ka-al-detail-label">User</span>
                    <span class="ka-al-detail-value">${this.escapeHtml(event.user_login || 'System')} (${event.user_role || 'N/A'})</span>
                </div>
                <div class="ka-al-detail-row">
                    <span class="ka-al-detail-label">IP Address</span>
                    <span class="ka-al-detail-value">${this.escapeHtml(event.ip || 'N/A')}</span>
                </div>
                <div class="ka-al-detail-row">
                    <span class="ka-al-detail-label">Object Type</span>
                    <span class="ka-al-detail-value">${this.escapeHtml(event.object_type || 'N/A')}</span>
                </div>
                <div class="ka-al-detail-row">
                    <span class="ka-al-detail-label">Object</span>
                    <span class="ka-al-detail-value">${this.escapeHtml(event.object_title || 'N/A')} (ID: ${event.object_id || 'N/A'})</span>
                </div>
                <div class="ka-al-detail-row">
                    <span class="ka-al-detail-label">Context</span>
                    <span class="ka-al-detail-value">${this.escapeHtml(event.context)}</span>
                </div>
                <div class="ka-al-detail-row">
                    <span class="ka-al-detail-label">Source</span>
                    <span class="ka-al-detail-value">${this.escapeHtml(event.source)}</span>
                </div>
                <div class="ka-al-detail-message">
                    ${this.escapeHtml(event.message)}
                </div>
            `;

            // Add user agent if available
            if (event.user_agent) {
                html += `
                    <div class="ka-al-detail-row" style="margin-top: 20px;">
                        <span class="ka-al-detail-label">User Agent</span>
                        <span class="ka-al-detail-value" style="font-size: 12px; opacity: 0.7;">
                            ${this.escapeHtml(event.user_agent)}
                        </span>
                    </div>
                `;
            }

            // Add data JSON if available
            if (event.data && Object.keys(event.data).length > 0) {
                html += `
                    <div class="ka-al-detail-data">
                        <h4>Additional Data</h4>
                        <pre class="ka-al-detail-json">${JSON.stringify(event.data, null, 2)}</pre>
                    </div>
                `;
            }

            $container.html(html);
        },

        /**
         * Close event details drawer.
         */
        closeDrawer: function () {
            $('#ka-al-drawer').removeClass('open');
            $('body').css('overflow', '');
        },

        /**
         * Save settings via AJAX.
         */
        saveSettings: function () {
            const $btn = $('#ka-al-save-settings');
            const $form = $('#ka-al-settings-form');

            // Collect settings
            const settings = {
                enabled: $form.find('[name="enabled"]').is(':checked'),
                retention_days: $form.find('[name="retention_days"]').val() || 14,
                modules: {
                    auth: $form.find('[name="modules[auth]"]').is(':checked'),
                    content: $form.find('[name="modules[content]"]').is(':checked'),
                    users: $form.find('[name="modules[users]"]').is(':checked'),
                    plugins: $form.find('[name="modules[plugins]"]').is(':checked'),
                    themes: $form.find('[name="modules[themes]"]').is(':checked')
                },
                excluded_roles: $form.find('[name="excluded_roles[]"]').val() || [],
                ip_storage: $form.find('[name="ip_storage"]').val(),
                store_user_agent: $form.find('[name="store_user_agent"]').is(':checked')
            };

            $btn.prop('disabled', true).text('Saving...');

            $.post(kngActivityLog.ajaxUrl, {
                action: 'kng_activity_log_save_settings',
                nonce: kngActivityLog.nonce,
                settings: settings
            }, (response) => {
                $btn.prop('disabled', false).text('Save Settings');

                if (response.success) {
                    this.showNotice('success', kngActivityLog.i18n.settingsSaved);
                } else {
                    this.showNotice('error', response.data?.message || kngActivityLog.i18n.error);
                }
            }).fail(() => {
                $btn.prop('disabled', false).text('Save Settings');
                this.showNotice('error', kngActivityLog.i18n.error);
            });
        },

        /**
         * Purge all logs.
         */
        purgeLogs: function () {
            if (!confirm(kngActivityLog.i18n.confirmPurge)) {
                return;
            }

            const $btn = $('#ka-al-purge-logs');
            $btn.prop('disabled', true).text('Purging...');

            $.post(kngActivityLog.ajaxUrl, {
                action: 'kng_activity_log_purge',
                nonce: kngActivityLog.nonce
            }, (response) => {
                $btn.prop('disabled', false).text('Purge All Logs');

                if (response.success) {
                    this.showNotice('success', response.data.message);
                } else {
                    this.showNotice('error', response.data?.message || kngActivityLog.i18n.error);
                }
            }).fail(() => {
                $btn.prop('disabled', false).text('Purge All Logs');
                this.showNotice('error', kngActivityLog.i18n.error);
            });
        },

        /**
         * Export logs to CSV.
         */
        exportLogs: function () {
            const $btn = $('#ka-al-export-btn');
            const format = $('input[name="export_format"]:checked').val() || 'csv';

            $btn.prop('disabled', true);

            $.post(kngActivityLog.ajaxUrl, {
                action: 'kng_activity_log_export',
                nonce: kngActivityLog.nonce,
                ...this.state.filters
            }, (response) => {
                $btn.prop('disabled', false);

                if (!response.success) {
                    this.showNotice('error', response.data?.message || kngActivityLog.i18n.error);
                    return;
                }

                // Create and trigger download
                const blob = new Blob([response.data.content], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement('a');
                const url = URL.createObjectURL(blob);

                link.setAttribute('href', url);
                link.setAttribute('download', response.data.filename);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                this.showNotice('success', kngActivityLog.i18n.exportSuccess);
            }).fail(() => {
                $btn.prop('disabled', false);
                this.showNotice('error', kngActivityLog.i18n.error);
            });
        },

        /**
         * Show a temporary notice.
         * 
         * @param {string} type Notice type (success, error).
         * @param {string} message Message text.
         */
        showNotice: function (type, message) {
            // Remove any existing notices
            $('.ka-al-notice').remove();

            const bgColor = type === 'success' ? 'rgba(48, 209, 88, 0.95)' : 'rgba(255, 69, 58, 0.95)';

            const $notice = $(`
                <div class="ka-al-notice" style="
                    position: fixed;
                    top: 50px;
                    right: 20px;
                    z-index: 100001;
                    max-width: 400px;
                    padding: 16px 24px;
                    background: ${bgColor};
                    color: #fff;
                    border-radius: 14px;
                    font-size: 14px;
                    font-weight: 500;
                    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
                    backdrop-filter: blur(10px);
                ">
                    ${this.escapeHtml(message)}
                </div>
            `);

            $('body').append($notice);

            setTimeout(() => {
                $notice.fadeOut(300, function () {
                    $(this).remove();
                });
            }, 3000);
        },

        /**
         * Escape HTML for safe rendering.
         * 
         * @param {string} str String to escape.
         * @returns {string} Escaped string.
         */
        escapeHtml: function (str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }
    };

    // Initialize on DOM ready
    $(document).ready(function () {
        ActivityLog.init();
    });

})(jQuery);
