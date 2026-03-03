/**
 * Fomo Notifications - Admin JavaScript
 *
 * @package King_Addons
 */

(function($) {
    'use strict';

    /**
     * Main Fomo Notifications Admin Class
     */
    const KngFomoAdmin = {
        /**
         * Current wizard data
         */
        wizardData: {
            step: 1,
            notification_id: 0,
            name: '',
            type: '',
            source: '',
            source_config: {},
            design: {
                template: 'default',
                position: 'bottom-left',
                animation: 'slide',
                bg_color: '#ffffff',
                text_color: '#1d1d1f',
                accent_color: '#0071e3',
                border_radius: 16,
                shadow: true
            },
            content: {
                title: '',
                message: '',
                image_type: 'product',
                custom_image: '',
                show_time: true,
                time_format: 'relative',
                cta_text: '',
                cta_url: ''
            },
            display: {
                delay: 3,
                duration: 5,
                interval: 10,
                max_per_session: 5,
                devices: ['desktop', 'tablet', 'mobile'],
                pages: 'all',
                page_rules: [],
                audience: 'all',
                exclude_logged_in: false
            },
            customize: {
                z_index: 99999,
                close_button: true,
                click_action: 'link',
                sound: false,
                analytics: true
            }
        },

        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.initWizard();
            this.initCharts();
            this.initToggles();
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            const self = this;

            // Navigation
            $(document).on('click', '.kng-fomo-nav-item', function(e) {
                const href = $(this).attr('href');
                if (href && href.indexOf('#') !== 0) {
                    return;
                }
                e.preventDefault();
                const tab = $(this).data('tab');
                self.switchTab(tab);
            });

            // Wizard steps
            $(document).on('click', '.kng-fomo-wizard-step', function() {
                const step = parseInt($(this).data('step'));
                if (step <= self.wizardData.step || self.canGoToStep(step)) {
                    self.goToStep(step);
                }
            });

            // Wizard navigation buttons
            $(document).on('click', '.kng-fomo-wizard-prev', function() {
                self.prevStep();
            });

            $(document).on('click', '.kng-fomo-wizard-next', function() {
                self.nextStep();
            });

            // Notification name (separate from content title)
            $(document).on('change input', '#kng-fomo-notif-name', function() {
                self.wizardData.name = $(this).val();
            });

            // Notification type selection — type also determines the source
            $(document).on('change', 'input[name="notification_type"]', function() {
                var type = $(this).val();
                var isNewType = (self.wizardData.type !== type);
                self.wizardData.type = type;
                self.wizardData.source = type; // type IS the source
                self.updateSourceOptions();
                self.updateSourceConfig();

                // Auto-populate content defaults and source_config for the selected type
                if (isNewType) {
                    self.applyTypeDefaults(type);
                }
            });

            // Design template selection
            $(document).on('click', '.kng-fomo-template-card', function() {
                $('.kng-fomo-template-card').removeClass('is-selected');
                $(this).addClass('is-selected');
                self.wizardData.design.template = $(this).data('template');
                self.updatePreview();
            });

            // Position selection - buttons
            $(document).on('click', '.kng-fomo-position-btn', function() {
                const position = $(this).data('position');
                $('.kng-fomo-position-btn').removeClass('is-active');
                $(this).addClass('is-active');
                $('.kng-fomo-position-dot').removeClass('is-active');
                $('.kng-fomo-position-dot[data-pos="' + position + '"]').addClass('is-active');
                self.wizardData.design.position = position;
                self.updatePreview();
            });

            // Position selection - dots (visual preview)
            $(document).on('click', '.kng-fomo-position-dot', function() {
                const position = $(this).data('pos');
                $('.kng-fomo-position-dot').removeClass('is-active');
                $(this).addClass('is-active');
                $('.kng-fomo-position-btn').removeClass('is-active');
                $('.kng-fomo-position-btn[data-position="' + position + '"]').addClass('is-active');
                self.wizardData.design.position = position;
                self.updatePreview();
            });

            // Form inputs
            $(document).on('change input', '.kng-fomo-wizard [data-field]', function() {
                const field = $(this).data('field');
                const section = $(this).data('section');
                const value = $(this).is(':checkbox') ? $(this).is(':checked') : $(this).val();
                
                if (section && self.wizardData[section]) {
                    self.wizardData[section][field] = value;
                } else {
                    self.wizardData[field] = value;
                }
                
                self.updatePreview();
            });

            // Color pickers
            $(document).on('input', '.kng-fomo-color-picker', function() {
                const field = $(this).data('field');
                const value = $(this).val();
                $(this).siblings('.kng-fomo-color-input').val(value);
                self.wizardData.design[field] = value;
                self.updatePreview();
            });

            $(document).on('input', '.kng-fomo-color-input', function() {
                const field = $(this).data('field');
                const value = $(this).val();
                $(this).siblings('.kng-fomo-color-picker').val(value);
                self.wizardData.design[field] = value;
                self.updatePreview();
            });

            // Save notification
            $(document).on('click', '.kng-fomo-save-notification', function() {
                self.saveNotification();
            });

            // Toggle notification status
            $(document).on('change', '.kng-fomo-toggle input', function() {
                const notificationId = $(this).closest('tr').data('id') || $(this).data('id');
                const status = $(this).is(':checked') ? 'enabled' : 'disabled';
                self.toggleNotification(notificationId, status);
            });

            // Delete notification
            $(document).on('click', '.kng-fomo-delete', function(e) {
                e.preventDefault();
                const notificationId = $(this).closest('tr').data('id') || $(this).data('id');
                self.deleteNotification(notificationId);
            });

            // Duplicate notification
            $(document).on('click', '.kng-fomo-duplicate', function(e) {
                e.preventDefault();
                const notificationId = $(this).closest('tr').data('id') || $(this).data('id');
                self.duplicateNotification(notificationId);
            });

            // Edit notification
            $(document).on('click', '.kng-fomo-edit', function(e) {
                e.preventDefault();
                const notificationId = $(this).closest('tr').data('id') || $(this).data('id');
                self.loadNotification(notificationId);
            });

            // Import/Export
            $(document).on('click', '.kng-fomo-export', function() {
                self.exportNotifications();
            });

            $(document).on('click', '.kng-fomo-import', function() {
                self.importNotifications();
            });

            // Settings save
            $(document).on('click', '.kng-fomo-save-settings', function() {
                self.saveSettings();
            });

            // Modal
            $(document).on('click', '.kng-fomo-modal-close, .kng-fomo-modal-overlay', function(e) {
                if (e.target === this) {
                    self.closeModal();
                }
            });

            // Analytics date range
            $(document).on('change', '.kng-fomo-date-range', function() {
                self.loadAnalytics($(this).val());
            });

            // Tab switching
            $(document).on('click', '.kng-fomo-tab', function() {
                const tabId = $(this).data('tab');
                self.switchContentTab(tabId);
            });

            // Device checkboxes
            $(document).on('change', '.kng-fomo-device-check', function() {
                const device = $(this).val();
                const checked = $(this).is(':checked');
                
                if (checked) {
                    if (!self.wizardData.display.devices.includes(device)) {
                        self.wizardData.display.devices.push(device);
                    }
                } else {
                    self.wizardData.display.devices = self.wizardData.display.devices.filter(d => d !== device);
                }
            });

            // Page rules
            $(document).on('click', '.kng-fomo-add-rule', function() {
                self.addPageRule();
            });

            $(document).on('click', '.kng-fomo-remove-rule', function() {
                $(this).closest('.kng-fomo-page-rule').remove();
                self.collectPageRules();
            });

            $(document).on('change', '.kng-fomo-page-rule select, .kng-fomo-page-rule input', function() {
                self.collectPageRules();
            });
        },

        /**
         * Initialize wizard
         */
        initWizard: function() {
            if (!$('.kng-fomo-wizard').length) {
                return;
            }

            // Check if editing existing notification
            const editId = this.getUrlParam('edit');
            if (editId) {
                this.loadNotification(editId);
            } else {
                this.goToStep(1);
            }
        },

        /**
         * Go to step
         */
        goToStep: function(step) {
            this.wizardData.step = step;

            // Update steps UI
            $('.kng-fomo-wizard-step').each(function() {
                const stepNum = parseInt($(this).data('step'));
                $(this).removeClass('is-active is-completed');
                
                if (stepNum === step) {
                    $(this).addClass('is-active');
                } else if (stepNum < step) {
                    $(this).addClass('is-completed');
                }
            });

            // Show/hide content panels
            $('.kng-fomo-wizard-panel').removeClass('is-active');
            $('.kng-fomo-wizard-panel[data-step="' + step + '"]').addClass('is-active');

            // Update buttons
            if (step === 1) {
                $('.kng-fomo-wizard-prev').hide();
            } else {
                $('.kng-fomo-wizard-prev').show();
            }

            if (step === 5) {
                $('.kng-fomo-wizard-next').hide();
                $('.kng-fomo-save-notification').show();
            } else {
                $('.kng-fomo-wizard-next').show();
                $('.kng-fomo-save-notification').hide();
            }

            // Update preview
            this.updatePreview();

            // Scroll to top
            const wizardEl = $('.kng-fomo-wizard').get(0);
            if (wizardEl) {
                wizardEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        },

        /**
         * Previous step
         */
        prevStep: function() {
            if (this.wizardData.step > 1) {
                this.goToStep(this.wizardData.step - 1);
            }
        },

        /**
         * Next step
         */
        nextStep: function() {
            if (this.validateStep(this.wizardData.step)) {
                if (this.wizardData.step < 5) {
                    this.goToStep(this.wizardData.step + 1);
                }
            }
        },

        /**
         * Validate step
         */
        validateStep: function(step) {
            let isValid = true;
            let message = '';

            switch (step) {
                case 1:
                    if (!this.wizardData.type) {
                        message = kngFomoAdmin.i18n.select_type || 'Please select a notification type.';
                        isValid = false;
                    }
                    break;
                case 2:
                    // Template has default value, so this is always valid
                    break;
                case 3:
                    // Content is optional for some notification types
                    break;
            }

            if (!isValid && message) {
                this.showToast(message, 'error');
            }

            return isValid;
        },

        /**
         * Can go to step
         */
        canGoToStep: function(step) {
            for (let i = 1; i < step; i++) {
                if (!this.validateStep(i)) {
                    return false;
                }
            }
            return true;
        },

        /**
         * Update source options based on type
         */
        updateSourceOptions: function() {
            const type = this.wizardData.type;
            const $sources = $('.kng-fomo-source-options');

            if (!$sources.length) {
                return;
            }

            // Show relevant sources
            $sources.find('.kng-fomo-radio-card').hide();
            $sources.find('.kng-fomo-radio-card[data-type="' + type + '"], .kng-fomo-radio-card[data-type="all"]').show();
        },

        /**
         * Update source configuration
         */
        updateSourceConfig: function() {
            const source = this.wizardData.source;
            const $config = $('.kng-fomo-source-config');

            if (!$config.length) {
                return;
            }

            // Hide all config panels
            $config.find('.kng-fomo-source-config-panel').hide();

            // Show relevant config
            $config.find('.kng-fomo-source-config-panel[data-source="' + source + '"]').fadeIn(200);
        },

        /**
         * Update live preview
         */
        updatePreview: function() {
            const $preview = $('.kng-fomo-preview-notification');

            if (!$preview.length) {
                return;
            }

            const data = this.wizardData;

            // Update position class
            $preview.removeClass('pos-top-left pos-top-right pos-bottom-left pos-bottom-right pos-top-center pos-bottom-center');
            $preview.addClass('pos-' + data.design.position);

            // Update styles
            $preview.css({
                '--bg-color': data.design.bg_color,
                '--text-color': data.design.text_color,
                '--accent-color': data.design.accent_color,
                '--border-radius': data.design.border_radius + 'px'
            });

            // Update content
            if (data.content.title) {
                $preview.find('.kng-fomo-preview-title').text(data.content.title);
            }
            if (data.content.message) {
                $preview.find('.kng-fomo-preview-message').text(data.content.message);
            }

            // Toggle shadow
            if (data.design.shadow) {
                $preview.addClass('has-shadow');
            } else {
                $preview.removeClass('has-shadow');
            }

            // Toggle close button
            if (data.customize.close_button) {
                $preview.find('.kng-fomo-preview-close').show();
            } else {
                $preview.find('.kng-fomo-preview-close').hide();
            }
        },

        /**
         * Save notification
         */
        saveNotification: function() {
            const self = this;
            const $btn = $('.kng-fomo-save-notification');
            const normalizedDisplay = this.buildDisplayPayload(this.wizardData.display || {});

            $btn.prop('disabled', true).addClass('is-loading');

            $.ajax({
                url: kngFomoAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'kng_fomo_save_notification',
                    nonce: kngFomoAdmin.nonce,
                    id: this.wizardData.notification_id,
                    name: this.wizardData.name || this.wizardData.content.title || 'Untitled Notification',
                    title: this.wizardData.content.title || '',
                    status: 'disabled',
                    type: this.wizardData.type,
                    source: this.wizardData.source,
                    source_config: JSON.stringify(this.wizardData.source_config),
                    design: JSON.stringify(this.wizardData.design),
                    content: JSON.stringify(this.wizardData.content),
                    display: JSON.stringify(normalizedDisplay),
                    customize: JSON.stringify(this.wizardData.customize)
                },
                success: function(response) {
                    if (response.success) {
                        self.showToast(kngFomoAdmin.i18n.saved, 'success');
                        
                        // Redirect to list
                        setTimeout(function() {
                            window.location.href = kngFomoAdmin.listUrl;
                        }, 1000);
                    } else {
                        self.showToast(response.data.message || kngFomoAdmin.i18n.error, 'error');
                    }
                },
                error: function() {
                    self.showToast(kngFomoAdmin.i18n.error, 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false).removeClass('is-loading');
                }
            });
        },

        /**
         * Load notification for editing
         */
        loadNotification: function(id) {
            const self = this;

            $.ajax({
                url: kngFomoAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'kng_fomo_get_notification',
                    nonce: kngFomoAdmin.nonce,
                    notification_id: id
                },
                success: function(response) {
                    if (response.success) {
                        if (response.data && response.data.display) {
                            response.data.display = self.normalizeDisplayFromServer(response.data.display);
                        }

                        self.wizardData = $.extend(true, self.wizardData, response.data);
                        self.populateWizardFields();
                        self.goToStep(1);
                    } else {
                        self.showToast(response.data.message || kngFomoAdmin.i18n.error, 'error');
                    }
                },
                error: function() {
                    self.showToast(kngFomoAdmin.i18n.error, 'error');
                }
            });
        },

        /**
         * Populate wizard fields from loaded data
         */
        populateWizardFields: function() {
            const data = this.wizardData;
            data.display = this.normalizeDisplayFromServer(data.display || {});

            // Notification name (separate from content title)
            if (data.name) {
                $('#kng-fomo-notif-name').val(data.name);
            }

            // Type
            if (data.type) {
                $('input[name="notification_type"][value="' + data.type + '"]').prop('checked', true);
                // Source = type (wizard has no separate source selector)
                if (!data.source || data.source === 'manual') {
                    data.source = data.type;
                }
                this.updateSourceOptions();
                this.updateSourceConfig();
            }

            // Source config (populate form fields FROM saved data)
            if (data.source_config && typeof data.source_config === 'object') {
                var sc = data.source_config;
                Object.keys(sc).forEach(function(key) {
                    var $el = $('[data-field="' + key + '"][data-section="source_config"]');
                    if ($el.length) {
                        if ($el.is('select[multiple]') && Array.isArray(sc[key])) {
                            $el.val(sc[key]);
                        } else {
                            $el.val(sc[key]);
                        }
                    }
                });
            }

            // Design - Template
            if (data.design.template) {
                $('.kng-fomo-template-card').removeClass('is-selected');
                $('.kng-fomo-template-card[data-template="' + data.design.template + '"]').addClass('is-selected');
            }

            // Design - Position
            if (data.design.position) {
                $('.kng-fomo-position-btn').removeClass('is-active');
                $('.kng-fomo-position-btn[data-position="' + data.design.position + '"]').addClass('is-active');
                $('.kng-fomo-position-dot').removeClass('is-active');
                $('.kng-fomo-position-dot[data-pos="' + data.design.position + '"]').addClass('is-active');
            }

            // Colors
            $('[data-field="bg_color"]').val(data.design.bg_color);
            $('[data-field="text_color"]').val(data.design.text_color);
            $('[data-field="accent_color"]').val(data.design.accent_color);
            $('[data-field="border_radius"]').val(data.design.border_radius);
            $('[data-field="shadow"]').prop('checked', data.design.shadow);

            // Content (title template, message template)
            $('[data-field="title"][data-section="content"]').val(data.content.title);
            $('[data-field="message"][data-section="content"]').val(data.content.message);
            $('[data-field="cta_text"]').val(data.content.cta_text);
            $('[data-field="cta_url"]').val(data.content.cta_url);

            // Show auto-hint if content has {{}} templates
            if (data.content.title && data.content.title.indexOf('{{') !== -1) {
                $('.kng-fomo-content-auto-hint').show();
            }

            // Display
            $('[data-field="delay"]').val(data.display.delay);
            $('[data-field="duration"]').val(data.display.duration);
            $('[data-field="interval"]').val(data.display.interval);
            $('[data-field="max_per_session"]').val(data.display.max_per_session);
            $('[data-field="pages"]').val(data.display.pages || 'all');
            $('[data-field="audience"]').val(data.display.audience || 'all');
            $('[data-field="loop"]').prop('checked', data.display.loop !== false);
            $('[data-field="random"]').prop('checked', !!data.display.random);

            // Devices
            $('.kng-fomo-device-check').prop('checked', false);
            (data.display.devices || []).forEach(function(device) {
                $('.kng-fomo-device-check[value="' + device + '"]').prop('checked', true);
            });

            // Page rules
            this.renderPageRules(data.display.page_rules || []);

            // Customize
            $('[data-field="z_index"]').val(data.customize.z_index);
            $('[data-field="close_button"]').prop('checked', data.customize.close_button);
            $('[data-field="sound"]').prop('checked', data.customize.sound);
            $('[data-field="analytics"]').prop('checked', data.customize.analytics);

            this.updatePreview();
        },

        /**
         * Apply type-specific defaults when user selects a notification type.
         * Auto-fills content templates, source_config defaults, and shows hint.
         */
        applyTypeDefaults: function(type) {
            var defaults = (window.kngFomoAdmin && kngFomoAdmin.typeDefaults) ? kngFomoAdmin.typeDefaults[type] : null;
            if (!defaults) return;

            // Auto-fill content templates if currently empty or non-template
            var cd = defaults.content_defaults || {};
            var currentTitle = this.wizardData.content.title || '';
            var currentMessage = this.wizardData.content.message || '';

            if (!currentTitle || currentTitle.indexOf('{{') === -1) {
                this.wizardData.content.title = cd.title || '';
                $('[data-field="title"][data-section="content"]').val(this.wizardData.content.title);
            }
            if (!currentMessage || currentMessage.indexOf('{{') === -1) {
                this.wizardData.content.message = cd.message || '';
                $('[data-field="message"][data-section="content"]').val(this.wizardData.content.message);
            }

            // Show auto-hint if we set templates
            if (this.wizardData.content.title && this.wizardData.content.title.indexOf('{{') !== -1) {
                $('.kng-fomo-content-auto-hint').show();
            } else {
                $('.kng-fomo-content-auto-hint').hide();
            }

            // Auto-populate source_config with defaults from server
            var scd = defaults.source_config_defaults || {};
            Object.keys(scd).forEach(function(key) {
                if (this.wizardData.source_config[key] === undefined) {
                    this.wizardData.source_config[key] = scd[key];
                }
                // Also set in the form
                var $el = $('[data-field="' + key + '"][data-section="source_config"]');
                if ($el.length && !$el.val()) {
                    $el.val(scd[key]);
                }
            }.bind(this));

            // Also set image type default for certain types
            if (type === 'wordpress_comments' || type === 'reviews' || type === 'email_subscription') {
                this.wizardData.content.image_type = 'avatar';
                $('[data-field="image_type"]').val('avatar');
            } else if (type === 'woocommerce_sales') {
                this.wizardData.content.image_type = 'product';
                $('[data-field="image_type"]').val('product');
            }

            this.updatePreview();
        },

        /**
         * Normalize server display payload to wizard shape
         */
        normalizeDisplayFromServer: function(display) {
            const out = $.extend(true, {
                delay: 3,
                duration: 5,
                interval: 10,
                max_per_session: 5,
                devices: ['desktop', 'tablet', 'mobile'],
                pages: 'all',
                page_rules: [],
                audience: 'all',
                exclude_logged_in: false,
                loop: true,
                random: false
            }, display || {});

            if (!Array.isArray(out.devices) || !out.devices.length) {
                out.devices = ['desktop', 'tablet', 'mobile'];
            }

            // Audience legacy -> wizard
            if (!out.audience) {
                const displayFor = out.display_for || 'everyone';
                if (displayFor === 'logged_in') out.audience = 'logged_in';
                else if (displayFor === 'logged_out' || displayFor === 'guests') out.audience = 'logged_out';
                else out.audience = 'all';
            }

            // Pages/rules legacy -> wizard
            if (!Array.isArray(out.page_rules)) {
                out.page_rules = [];
            }

            if (!out.pages) {
                out.pages = 'all';
            }

            if (!out.page_rules.length && (out.show_on === 'include' || out.show_on === 'exclude')) {
                const ids = out.show_on === 'include' ? (out.include_pages || []) : (out.exclude_pages || []);
                if (Array.isArray(ids) && ids.length) {
                    out.pages = 'specific';
                    out.page_rules = ids.map(function(id) {
                        return {
                            type: out.show_on,
                            condition: 'page',
                            value: String(id)
                        };
                    });
                }
            }

            if (out.page_rules.length) {
                out.pages = 'specific';
            }

            return out;
        },

        /**
         * Build display payload with legacy-compatible keys
         */
        buildDisplayPayload: function(display) {
            const out = $.extend(true, {}, display || {});

            // Audience wizard -> legacy
            if (out.audience === 'logged_in') out.display_for = 'logged_in';
            else if (out.audience === 'logged_out') out.display_for = 'logged_out';
            else out.display_for = 'everyone';

            // Page rules wizard -> legacy
            const rules = Array.isArray(out.page_rules) ? out.page_rules : [];
            if (out.pages === 'specific' && rules.length) {
                const includeIds = [];
                const excludeIds = [];

                rules.forEach(function(rule) {
                    if (!rule || (rule.condition !== 'page' && rule.condition !== 'post')) return;
                    if (!/^\d+$/.test(String(rule.value || '').trim())) return;
                    const id = parseInt(rule.value, 10);
                    if (rule.type === 'exclude') excludeIds.push(id);
                    else includeIds.push(id);
                });

                if (includeIds.length) {
                    out.show_on = 'include';
                    out.include_pages = includeIds;
                    out.exclude_pages = [];
                } else if (excludeIds.length) {
                    out.show_on = 'exclude';
                    out.exclude_pages = excludeIds;
                    out.include_pages = [];
                } else {
                    out.show_on = 'everywhere';
                    out.include_pages = [];
                    out.exclude_pages = [];
                }
            } else {
                out.show_on = 'everywhere';
                out.include_pages = [];
                out.exclude_pages = [];
            }

            return out;
        },

        /**
         * Toggle notification status
         */
        toggleNotification: function(id, status) {
            const self = this;

            $.ajax({
                url: kngFomoAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'kng_fomo_toggle_status',
                    nonce: kngFomoAdmin.nonce,
                    id: id,
                    status: status
                },
                success: function(response) {
                    if (response.success) {
                        self.showToast(kngFomoAdmin.i18n.saved, 'success');
                    } else {
                        self.showToast(response.data.message || kngFomoAdmin.i18n.error, 'error');
                    }
                },
                error: function() {
                    self.showToast(kngFomoAdmin.i18n.error, 'error');
                }
            });
        },

        /**
         * Delete notification
         */
        deleteNotification: function(id) {
            const self = this;

            if (!confirm(kngFomoAdmin.i18n.confirmDelete)) {
                return;
            }

            $.ajax({
                url: kngFomoAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'kng_fomo_delete_notification',
                    nonce: kngFomoAdmin.nonce,
                    id: id
                },
                success: function(response) {
                    if (response.success) {
                        self.showToast(kngFomoAdmin.i18n.deleted, 'success');
                        $('tr[data-id="' + id + '"]').fadeOut(300, function() {
                            $(this).remove();
                            self.checkEmptyState();
                        });
                    } else {
                        self.showToast(response.data.message || kngFomoAdmin.i18n.error, 'error');
                    }
                },
                error: function() {
                    self.showToast(kngFomoAdmin.i18n.error, 'error');
                }
            });
        },

        /**
         * Duplicate notification
         */
        duplicateNotification: function(id) {
            const self = this;

            $.ajax({
                url: kngFomoAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'kng_fomo_duplicate_notification',
                    nonce: kngFomoAdmin.nonce,
                    id: id
                },
                success: function(response) {
                    if (response.success) {
                        self.showToast(kngFomoAdmin.i18n.duplicated, 'success');
                        setTimeout(function() {
                            window.location.reload();
                        }, 500);
                    } else {
                        self.showToast(response.data.message || kngFomoAdmin.i18n.error, 'error');
                    }
                },
                error: function() {
                    self.showToast(kngFomoAdmin.i18n.error, 'error');
                }
            });
        },

        /**
         * Check empty state
         */
        checkEmptyState: function() {
            if ($('.kng-fomo-table tbody tr').length === 0) {
                $('.kng-fomo-table-wrap').hide();
                $('.kng-fomo-empty').show();
            }
        },

        /**
         * Add page rule
         */
        addPageRule: function() {
            const $container = $('.kng-fomo-page-rules');
            const template = `
                <div class="kng-fomo-page-rule">
                    <select class="kng-fomo-input kng-fomo-input--sm kng-fomo-rule-type">
                        <option value="include">Include</option>
                        <option value="exclude">Exclude</option>
                    </select>
                    <select class="kng-fomo-input kng-fomo-input--sm kng-fomo-rule-condition">
                        <option value="page">Page</option>
                        <option value="post">Post</option>
                        <option value="url_contains">URL Contains</option>
                        <option value="url_is">URL Is</option>
                    </select>
                    <input type="text" class="kng-fomo-input kng-fomo-input--sm kng-fomo-rule-value" placeholder="Value">
                    <button type="button" class="kng-fomo-btn kng-fomo-btn--sm kng-fomo-btn--danger kng-fomo-remove-rule">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>
            `;
            $container.append(template);
        },

        renderPageRules: function(rules) {
            const $container = $('.kng-fomo-page-rules');
            if (!$container.length) return;

            $container.empty();
            if (!Array.isArray(rules) || !rules.length) {
                this.collectPageRules();
                return;
            }

            const escHtml = function(str) {
                return String(str === undefined || str === null ? '' : str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');
            };

            rules.forEach(function(rule) {
                const type = rule.type === 'exclude' ? 'exclude' : 'include';
                const condition = ['page', 'post', 'url_contains', 'url_is'].includes(rule.condition) ? rule.condition : 'url_contains';
                const value = escHtml(rule.value || '');

                const row = `
                    <div class="kng-fomo-page-rule">
                        <select class="kng-fomo-input kng-fomo-input--sm kng-fomo-rule-type">
                            <option value="include" ${type === 'include' ? 'selected' : ''}>Include</option>
                            <option value="exclude" ${type === 'exclude' ? 'selected' : ''}>Exclude</option>
                        </select>
                        <select class="kng-fomo-input kng-fomo-input--sm kng-fomo-rule-condition">
                            <option value="page" ${condition === 'page' ? 'selected' : ''}>Page</option>
                            <option value="post" ${condition === 'post' ? 'selected' : ''}>Post</option>
                            <option value="url_contains" ${condition === 'url_contains' ? 'selected' : ''}>URL Contains</option>
                            <option value="url_is" ${condition === 'url_is' ? 'selected' : ''}>URL Is</option>
                        </select>
                        <input type="text" class="kng-fomo-input kng-fomo-input--sm kng-fomo-rule-value" placeholder="Value" value="${value}">
                        <button type="button" class="kng-fomo-btn kng-fomo-btn--sm kng-fomo-btn--danger kng-fomo-remove-rule">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                        </button>
                    </div>
                `;

                $container.append(row);
            });

            this.collectPageRules();
        },

        /**
         * Collect page rules
         */
        collectPageRules: function() {
            const rules = [];
            
            $('.kng-fomo-page-rule').each(function() {
                const rule = {
                    type: $(this).find('.kng-fomo-rule-type').val(),
                    condition: $(this).find('.kng-fomo-rule-condition').val(),
                    value: $(this).find('.kng-fomo-rule-value').val()
                };
                
                if (rule.value) {
                    rules.push(rule);
                }
            });

            this.wizardData.display.page_rules = rules;
        },

        /**
         * Initialize charts
         */
        initCharts: function() {
            if (!$('#kng-fomo-chart').length) {
                return;
            }

            this.loadAnalytics('7days');
        },

        /**
         * Load analytics data
         */
        loadAnalytics: function(range) {
            const self = this;

            $.ajax({
                url: kngFomoAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'kng_fomo_get_analytics',
                    nonce: kngFomoAdmin.nonce,
                    period: range
                },
                success: function(response) {
                    if (response.success) {
                        self.renderChart(response.data);
                        self.updateKPIs(response.data);
                    }
                }
            });
        },

        /**
         * Render chart
         */
        renderChart: function(data) {
            const ctx = document.getElementById('kng-fomo-chart');
            
            if (!ctx) {
                return;
            }

            // Destroy existing chart
            if (this.chart) {
                this.chart.destroy();
            }

            this.chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.chart.labels,
                    datasets: [
                        {
                            label: 'Views',
                            data: data.chart.views,
                            borderColor: '#0071e3',
                            backgroundColor: 'rgba(0, 113, 227, 0.1)',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'Clicks',
                            data: data.chart.clicks,
                            borderColor: '#34c759',
                            backgroundColor: 'rgba(52, 199, 89, 0.1)',
                            tension: 0.4,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 20,
                                font: {
                                    family: "-apple-system, BlinkMacSystemFont, 'SF Pro Display', sans-serif",
                                    size: 13
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    family: "-apple-system, BlinkMacSystemFont, 'SF Pro Display', sans-serif",
                                    size: 12
                                }
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.04)'
                            },
                            ticks: {
                                font: {
                                    family: "-apple-system, BlinkMacSystemFont, 'SF Pro Display', sans-serif",
                                    size: 12
                                }
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    }
                }
            });
        },

        /**
         * Update KPI cards
         */
        updateKPIs: function(data) {
            $('.kng-fomo-kpi-value[data-kpi="views"]').text(this.formatNumber(data.totals.views));
            $('.kng-fomo-kpi-value[data-kpi="clicks"]').text(this.formatNumber(data.totals.clicks));
            $('.kng-fomo-kpi-value[data-kpi="ctr"]').text(data.totals.ctr + '%');
        },

        /**
         * Format number
         */
        formatNumber: function(num) {
            if (num >= 1000000) {
                return (num / 1000000).toFixed(1) + 'M';
            }
            if (num >= 1000) {
                return (num / 1000).toFixed(1) + 'K';
            }
            return num.toLocaleString();
        },

        /**
         * Initialize toggles
         */
        initToggles: function() {
            // Module toggles
            $(document).on('change', '.kng-fomo-module-toggle', function() {
                const module = $(this).data('module');
                const enabled = $(this).is(':checked');

                // Visual feedback
                $(this).closest('.kng-fomo-module').toggleClass('is-enabled', enabled);
            });
        },

        /**
         * Save settings
         */
        saveSettings: function() {
            const self = this;
            const $btn = $('.kng-fomo-save-settings');
            const settings = {};

            // Collect all settings
            $('.kng-fomo-settings-form [name]').each(function() {
                const name = $(this).attr('name');
                const value = $(this).is(':checkbox') ? $(this).is(':checked') : $(this).val();
                settings[name] = value;
            });

            // Collect modules
            const modules = {};
            $('.kng-fomo-module-toggle').each(function() {
                modules[$(this).data('module')] = $(this).is(':checked');
            });
            settings.modules = modules;

            $btn.prop('disabled', true).addClass('is-loading');

            $.ajax({
                url: kngFomoAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'kng_fomo_save_settings',
                    nonce: kngFomoAdmin.nonce,
                    settings: settings
                },
                success: function(response) {
                    if (response.success) {
                        self.showToast(kngFomoAdmin.i18n.settings_saved, 'success');
                    } else {
                        self.showToast(response.data.message || kngFomoAdmin.i18n.error, 'error');
                    }
                },
                error: function() {
                    self.showToast(kngFomoAdmin.i18n.error, 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false).removeClass('is-loading');
                }
            });
        },

        /**
         * Export notifications
         */
        exportNotifications: function() {
            const self = this;

            $.ajax({
                url: kngFomoAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'kng_fomo_export_notification',
                    nonce: kngFomoAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Download JSON file
                        const blob = new Blob([JSON.stringify(response.data, null, 2)], { type: 'application/json' });
                        const url = URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = 'fomo-notifications-export.json';
                        a.click();
                        URL.revokeObjectURL(url);

                        self.showToast(kngFomoAdmin.i18n.exported, 'success');
                    } else {
                        self.showToast(response.data.message || kngFomoAdmin.i18n.error, 'error');
                    }
                },
                error: function() {
                    self.showToast(kngFomoAdmin.i18n.error, 'error');
                }
            });
        },

        /**
         * Import notifications
         */
        importNotifications: function() {
            const self = this;
            const $input = $('<input type="file" accept=".json">');

            $input.on('change', function(e) {
                const file = e.target.files[0];
                if (!file) {
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    try {
                        const data = JSON.parse(e.target.result);
                        self.processImport(data);
                    } catch (error) {
                        self.showToast(kngFomoAdmin.i18n.invalid_file, 'error');
                    }
                };
                reader.readAsText(file);
            });

            $input.click();
        },

        /**
         * Process import
         */
        processImport: function(data) {
            const self = this;

            $.ajax({
                url: kngFomoAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'kng_fomo_import_notification',
                    nonce: kngFomoAdmin.nonce,
                    data: JSON.stringify(data)
                },
                success: function(response) {
                    if (response.success) {
                        self.showToast(kngFomoAdmin.i18n.imported, 'success');
                        setTimeout(function() {
                            window.location.reload();
                        }, 500);
                    } else {
                        self.showToast(response.data.message || kngFomoAdmin.i18n.error, 'error');
                    }
                },
                error: function() {
                    self.showToast(kngFomoAdmin.i18n.error, 'error');
                }
            });
        },

        /**
         * Switch tab
         */
        switchTab: function(tab) {
            $('.kng-fomo-nav-item').removeClass('is-active');
            $('.kng-fomo-nav-item[data-tab="' + tab + '"]').addClass('is-active');

            // Update URL
            const url = new URL(window.location);
            url.searchParams.set('tab', tab);
            window.history.pushState({}, '', url);
        },

        /**
         * Switch content tab
         */
        switchContentTab: function(tabId) {
            $('.kng-fomo-tab').removeClass('is-active');
            $('.kng-fomo-tab[data-tab="' + tabId + '"]').addClass('is-active');

            $('.kng-fomo-tab-content').removeClass('is-active');
            $('#' + tabId).addClass('is-active');
        },

        /**
         * Open modal
         */
        openModal: function(content) {
            const $overlay = $('.kng-fomo-modal-overlay');

            if (!$overlay.length) {
                $('body').append('<div class="kng-fomo-modal-overlay"><div class="kng-fomo-modal"></div></div>');
            }

            $('.kng-fomo-modal').html(content);
            $('.kng-fomo-modal-overlay').addClass('is-visible');
            $('body').css('overflow', 'hidden');
        },

        /**
         * Close modal
         */
        closeModal: function() {
            $('.kng-fomo-modal-overlay').removeClass('is-visible');
            $('body').css('overflow', '');
        },

        /**
         * Show toast notification
         */
        showToast: function(message, type) {
            type = type || 'success';

            // Remove existing toasts
            $('.kng-fomo-toast').remove();

            const icon = type === 'success'
                ? '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>'
                : '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>';

            const $toast = $(`
                <div class="kng-fomo-toast kng-fomo-toast--${type}">
                    <span class="kng-fomo-toast-icon">${icon}</span>
                    <span class="kng-fomo-toast-message">${message}</span>
                </div>
            `);

            $('body').append($toast);

            // Auto remove
            setTimeout(function() {
                $toast.addClass('is-hiding');
                setTimeout(function() {
                    $toast.remove();
                }, 200);
            }, 3000);
        },

        /**
         * Get URL parameter
         */
        getUrlParam: function(param) {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get(param);
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        KngFomoAdmin.init();
    });

})(jQuery);
