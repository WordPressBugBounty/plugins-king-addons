/**
 * Site Preloader Admin Script.
 *
 * Handles admin UI interactions, live preview, color pickers, media uploaders, and AJAX operations.
 * Premium style inspired smooth interactions.
 *
 * @package King_Addons
 * @since 1.0.0
 */

(function ($) {
    'use strict';

    /**
     * Admin Controller
     */
    const KngPreloaderAdmin = {

        /**
         * Cached elements
         */
        elements: {
            form: null,
            preview: null,
            previewFrame: null,
            previewContent: null,
            colorPickers: null,
            mediaButtons: null,
            tabLinks: null,
            rulesList: null
        },

        /**
         * Current settings state
         */
        settings: {},

        /**
         * Initialize admin
         */
        init: function () {
            this.cacheElements();
            this.initColorPickers();
            this.initMediaUploaders();
            this.initTabs();
            this.initPresetSelection();
            this.initLivePreview();
            this.initRulesManager();
            this.initImportExport();
            this.initDarkModeToggle();
            this.initFormSubmit();
            this.initPreviewModal();
        },

        /**
         * Cache DOM elements
         */
        cacheElements: function () {
            this.elements.form = $('#ka-preloader-settings-form');
            if (!this.elements.form.length) {
                this.elements.form = $('#kng-preloader-settings-form');
            }
            this.elements.preview = $('.ka-preloader-live-preview');
            this.elements.previewFrame = $('.ka-preloader-preview-frame');
            this.elements.previewContent = $('.ka-preloader-preview-content');
            this.elements.colorPickers = $('.ka-preloader-color-input, .ka-color-picker, .ka-preloader-wp-color-picker');
            this.elements.mediaButtons = $('.ka-preloader-media-button');
            this.elements.tabLinks = $('.ka-preloader-tab-link');
            this.elements.rulesList = $('.ka-preloader-rules-list');
        },

        /**
         * Initialize color pickers
         */
        initColorPickers: function () {
            // Legacy custom preview inputs (if any exist)
            $('.ka-preloader-color-input').each(function () {
                const $input = $(this);
                const $preview = $input.siblings('.ka-preloader-color-preview');

                $input.off('input.kngPreloader change.kngPreloader');
                $input.on('input.kngPreloader change.kngPreloader', function () {
                    const color = $(this).val();
                    $preview.css('background-color', color);
                    KngPreloaderAdmin.updatePreview();
                });

                $preview.css('background-color', $input.val());
            });

            // Alpha-enabled WordPress color picker
            if ($.fn.wpColorPicker) {
                const $pickers = $('.ka-color-picker, .ka-preloader-wp-color-picker');
                $pickers.each(function () {
                    const $input = $(this);
                    if ($input.hasClass('wp-color-picker')) {
                        return;
                    }

                    $input.wpColorPicker({
                        defaultColor: $input.data('default-color'),
                        change: function () {
                            KngPreloaderAdmin.updatePreview();
                        },
                        clear: function () {
                            KngPreloaderAdmin.updatePreview();
                        }
                    });
                });
            }
        },

        /**
         * Initialize media uploaders
         */
        initMediaUploaders: function () {
            this.elements.mediaButtons.on('click', function (e) {
                e.preventDefault();

                const $button = $(this);
                const $container = $button.closest('.ka-preloader-media-field');
                const $input = $container.find('input[type="hidden"]');
                const $preview = $container.find('.ka-preloader-media-preview');
                const $removeBtn = $container.find('.ka-preloader-media-remove');

                // Create media frame
                const frame = wp.media({
                    title: $button.data('title') || 'Select Image',
                    button: {
                        text: $button.data('button-text') || 'Use Image'
                    },
                    multiple: false
                });

                // Handle selection
                frame.on('select', function () {
                    const attachment = frame.state().get('selection').first().toJSON();
                    
                    $input.val(attachment.id);
                    
                    let imgUrl = attachment.url;
                    if (attachment.sizes && attachment.sizes.thumbnail) {
                        imgUrl = attachment.sizes.thumbnail.url;
                    }

                    $preview.html('<img src="' + imgUrl + '" alt="" />').show();
                    $removeBtn.show();
                    $button.text('Change Image');

                    KngPreloaderAdmin.updatePreview();
                });

                frame.open();
            });

            // Handle remove button
            $('.ka-preloader-media-remove').on('click', function (e) {
                e.preventDefault();

                const $button = $(this);
                const $container = $button.closest('.ka-preloader-media-field');
                const $input = $container.find('input[type="hidden"]');
                const $preview = $container.find('.ka-preloader-media-preview');
                const $uploadBtn = $container.find('.ka-preloader-media-button');

                $input.val('');
                $preview.empty().hide();
                $button.hide();
                $uploadBtn.text('Upload Image');

                KngPreloaderAdmin.updatePreview();
            });
        },

        /**
         * Initialize tabs
         */
        initTabs: function () {
            this.elements.tabLinks.on('click', function (e) {
                e.preventDefault();

                const $link = $(this);
                const tab = $link.data('tab');

                // Update URL without reload
                const url = new URL(window.location);
                url.searchParams.set('tab', tab);
                window.history.replaceState({}, '', url);

                // Update active states
                KngPreloaderAdmin.elements.tabLinks.removeClass('active');
                $link.addClass('active');

                // Show target panel
                $('.ka-preloader-tab-panel').removeClass('active');
                $('#ka-preloader-tab-' + tab).addClass('active');
            });
        },

        /**
         * Initialize preset selection
         */
        initPresetSelection: function () {
            $(document).on('click', '.ka-preloader-preset-card', function () {
                const $card = $(this);
                const presetId = $card.data('preset');

                // Update active state
                $('.ka-preloader-preset-card').removeClass('active');
                $card.addClass('active');

                // Update hidden input
                $('input[name="template"], input[name="preset"]').val(presetId);

                // Update preview
                KngPreloaderAdmin.updatePreview();
            });
        },

        /**
         * Initialize live preview
         */
        initLivePreview: function () {
            // Collect all form inputs
            const $formInputs = this.elements.form.length ? this.elements.form.find('input, select, textarea') : $('input, select, textarea');

            // Update preview on input change
            $formInputs.on('input change', function () {
                KngPreloaderAdmin.debounce(KngPreloaderAdmin.updatePreview, 150)();
            });

            // Initial preview render
            this.updatePreview();
        },

        /**
         * Update live preview
         */
        updatePreview: function () {
            if (!this.elements.previewContent.length) {
                return;
            }

            // Collect current settings
            const settings = this.collectFormSettings();

            const bgColor = settings.bg_color || settings.background_color || 'rgba(0,0,0,0)';
            const presetId = settings.template || settings.preset || 'spinner-circle';
            const sizePx = (settings.spinner_size || settings.animation_size || 48) + 'px';

            // Update preview CSS variables
            const $frame = this.elements.previewFrame;
            $frame.css({
                '--kng-preloader-accent': settings.accent_color || '#0071e3',
                '--kng-preloader-bg': bgColor,
                '--kng-preloader-text': settings.text_color || '#1d1d1f',
                '--kng-preloader-size': sizePx
            });

            // Update background overlay
            const $overlay = this.elements.previewContent.find('.ka-preloader-preview-overlay');
            if ($overlay.length) {
                $overlay.css('background-color', bgColor);
            }

            // Update preset animation
            this.updatePresetAnimation(presetId);

            // Update text
            const $text = this.elements.previewContent.find('.ka-preloader-preview-text');
            if (settings.text_content) {
                $text.text(settings.text_content).show();
            } else {
                $text.hide();
            }
        },

        /**
         * Update preset animation in preview
         */
        updatePresetAnimation: function (presetId) {
            const $animation = this.elements.previewContent.find('.ka-preloader-preview-animation');
            if (!$animation.length) {
                return;
            }

            // Generate preset HTML
            const presetHtml = this.getPresetHtml(presetId);
            $animation.html(presetHtml);
        },

        /**
         * Get preset HTML
         */
        getPresetHtml: function (presetId) {
            const presets = {
                'spinner-circle': '<div class="kng-preloader-spinner-circle"></div>',
                'dual-ring': '<div class="kng-preloader-dual-ring"></div>',
                'dots-bounce': '<div class="kng-preloader-dots-bounce"><span></span><span></span><span></span></div>',
                'bar-loader': '<div class="kng-preloader-bar-loader"><div class="kng-preloader-bar-loader__bar"></div></div>',
                'pulse-logo': '<div class="kng-preloader-pulse-logo"><div class="kng-preloader-pulse-logo__circle"></div></div>',
                'minimal-line': '<div class="kng-preloader-minimal-line"><div class="kng-preloader-minimal-line__track"><div class="kng-preloader-minimal-line__bar"></div></div></div>',
                'gradient-spinner': '<div class="kng-preloader-gradient-spinner"></div>',
                'fade-text': '<div class="kng-preloader-fade-text"><span style="animation-delay: 0s">L</span><span style="animation-delay: 0.1s">o</span><span style="animation-delay: 0.2s">a</span><span style="animation-delay: 0.3s">d</span><span style="animation-delay: 0.4s">i</span><span style="animation-delay: 0.5s">n</span><span style="animation-delay: 0.6s">g</span></div>',
                'cube-grid': '<div class="kng-preloader-cube-grid">' + '<div class="kng-preloader-cube-grid__cube"></div>'.repeat(9) + '</div>',
                'wave-bars': '<div class="kng-preloader-wave-bars">' + '<div class="kng-preloader-wave-bars__bar"></div>'.repeat(5) + '</div>',
                'rotating-squares': '<div class="kng-preloader-rotating-squares"><div class="kng-preloader-rotating-squares__square"></div><div class="kng-preloader-rotating-squares__square"></div></div>',
                'morphing-circle': '<div class="kng-preloader-morphing-circle"></div>'
            };

            return presets[presetId] || presets['spinner-circle'];
        },

        /**
         * Collect form settings
         */
        collectFormSettings: function () {
            const settings = {};
            
            this.elements.form.find('input, select, textarea').each(function () {
                const $field = $(this);
                const name = $field.attr('name');
                
                if (!name) return;

                if ($field.is(':checkbox')) {
                    settings[name] = $field.is(':checked') ? '1' : '0';
                } else if ($field.is(':radio')) {
                    if ($field.is(':checked')) {
                        settings[name] = $field.val();
                    }
                } else {
                    settings[name] = $field.val();
                }
            });

            return settings;
        },

        /**
         * Initialize rules manager
         */
        initRulesManager: function () {
            // Add rule button - support both class and ID
            $(document).on('click', '#ka-add-rule-btn, .ka-preloader-add-rule', function (e) {
                e.preventDefault();
                KngPreloaderAdmin.addNewRule();
            });

            // Remove rule button (both classes) - use deleteRule with AJAX
            $(document).on('click', '.ka-preloader-rule-remove, .ka-rule-delete-btn', function (e) {
                e.preventDefault();
                e.stopPropagation();
                
                if (!confirm(kngPreloaderAdmin.strings.confirmDelete || 'Are you sure you want to delete this rule?')) {
                    return;
                }
                
                const $rule = $(this).closest('.ka-preloader-rule-item');
                KngPreloaderAdmin.deleteRule($rule);
            });

            // Edit rule button - toggle details
            $(document).on('click', '.ka-rule-edit-btn', function (e) {
                e.preventDefault();
                e.stopPropagation();
                const $rule = $(this).closest('.ka-preloader-rule-item');
                const $details = $rule.find('.ka-preloader-rule-item__details');
                $details.slideToggle(200);
            });

            // Save rule button
            $(document).on('click', '.ka-rule-save-btn', function (e) {
                e.preventDefault();
                const $rule = $(this).closest('.ka-preloader-rule-item');
                KngPreloaderAdmin.saveRule($rule);
            });

            // Cancel rule button
            $(document).on('click', '.ka-rule-cancel-btn', function (e) {
                e.preventDefault();
                const $rule = $(this).closest('.ka-preloader-rule-item');
                KngPreloaderAdmin.cancelRuleEdit($rule);
            });

            // Action buttons (Show/Hide/Override) - both old and new class names
            $(document).on('click', '.ka-rule-action-btn, .ka-rule-action-btns .ka-btn', function (e) {
                e.preventDefault();
                const $btn = $(this);
                const $rule = $btn.closest('.ka-preloader-rule-item');
                const action = $btn.data('action');
                
                // Update active state
                $btn.siblings().removeClass('active');
                $btn.addClass('active');
                
                // Store in hidden input if exists
                $rule.find('input.ka-rule-action').val(action);
                
                // Show/hide template row (only for override)
                const $templateRow = $rule.find('.ka-rule-template-row');
                const $colorsToggleRow = $rule.find('.ka-rule-colors-toggle-row');
                const $colorsRow = $rule.find('.ka-rule-colors-row');
                
                if (action === 'override') {
                    $templateRow.slideDown(200);
                    $colorsToggleRow.slideDown(200);
                    if ($rule.find('.ka-rule-override-colors').is(':checked')) {
                        $colorsRow.slideDown(200);
                    }
                } else {
                    $templateRow.slideUp(200);
                    $colorsToggleRow.slideUp(200);
                    $colorsRow.slideUp(200);
                }
                
                // Update header badge
                const actionLabels = { 'show': 'Show', 'hide': 'Hide', 'override': 'Override' };
                const actionIcons = { 'show': 'visibility', 'hide': 'hidden', 'override': 'admin-customizer' };
                $rule.find('.ka-preloader-rule-item__summary .ka-rule-action')
                    .removeClass('ka-rule-action--show ka-rule-action--hide ka-rule-action--override')
                    .addClass('ka-rule-action--' + action)
                    .html('<span class="dashicons dashicons-' + actionIcons[action] + '"></span> ' + actionLabels[action]);
            });

            // Toggle rule expansion (header click)
            $(document).on('click', '.ka-preloader-rule-item__header', function (e) {
                // Don't toggle if clicking buttons
                if ($(e.target).closest('button, .ka-toggle, input').length) {
                    return;
                }
                
                const $rule = $(this).closest('.ka-preloader-rule-item');
                const $details = $rule.find('.ka-preloader-rule-item__details');
                $details.slideToggle(200);
            });

            // Toggle rule expansion (old class for JS-generated rules)
            $(document).on('click', '.ka-preloader-rule-header', function (e) {
                if ($(e.target).closest('button').length) {
                    return;
                }
                
                const $rule = $(this).closest('.ka-preloader-rule-item');
                $rule.toggleClass('expanded');
            });

            // Rule condition change - show/hide appropriate fields
            $(document).on('change', '.ka-rule-condition', function () {
                const $rule = $(this).closest('.ka-preloader-rule-item');
                const condition = $(this).val();
                
                KngPreloaderAdmin.updateConditionFields($rule, condition);
            });
            
            // Also support old class names
            $(document).on('change', '.ka-preloader-rule-condition, .ka-rule-condition-type', function () {
                const $rule = $(this).closest('.ka-preloader-rule-item');
                const condition = $(this).val();
                
                KngPreloaderAdmin.updateRuleConditions($rule, condition);
            });

            // Override colors toggle
            $(document).on('change', '.ka-rule-override-colors', function () {
                const $rule = $(this).closest('.ka-preloader-rule-item');
                const $colorRows = $rule.find('.ka-rule-colors-row, .ka-rule-colors-grid');
                
                if ($(this).is(':checked')) {
                    $colorRows.slideDown(200);
                } else {
                    $colorRows.slideUp(200);
                }
            });

            // Rule type change
            $(document).on('change', '.ka-preloader-rule-type', function () {
                const $rule = $(this).closest('.ka-preloader-rule-item');
                const type = $(this).val();
                
                KngPreloaderAdmin.updateRuleConditions($rule, type);
            });
            
            // Initialize condition fields on page load
            $('.ka-preloader-rule-item').each(function() {
                const $rule = $(this);
                const condition = $rule.find('.ka-rule-condition').val();
                if (condition) {
                    KngPreloaderAdmin.updateConditionFields($rule, condition);
                }
            });
        },
        
        /**
         * Update condition fields visibility based on selected condition
         */
        updateConditionFields: function ($rule, condition) {
            const $pagesRow = $rule.find('.ka-rule-pages-row');
            const $posttypeRow = $rule.find('.ka-rule-posttype-row');
            const $urlRow = $rule.find('.ka-rule-url-row');
            
            // Hide all conditional rows first
            $pagesRow.hide();
            $posttypeRow.hide();
            $urlRow.hide();
            
            // Show appropriate row based on condition
            switch (condition) {
                case 'specific_pages':
                    $pagesRow.slideDown(200);
                    break;
                case 'post_type':
                    $posttypeRow.slideDown(200);
                    break;
                case 'url_contains':
                case 'url_equals':
                    $urlRow.slideDown(200);
                    break;
                // Other conditions don't need additional fields
            }
        },

        /**
         * Save rule via AJAX
         */
        saveRule: function ($rule) {
            const $saveBtn = $rule.find('.ka-rule-save-btn');
            const originalText = $saveBtn.text();
            
            // Get action from the active button or hidden input
            let action = 'show';
            const $activeAction = $rule.find('.ka-rule-action-btn.active, .ka-rule-action-btns .ka-btn.active');
            if ($activeAction.length) {
                action = $activeAction.data('action');
            } else {
                const $actionInput = $rule.find('input.ka-rule-action');
                if ($actionInput.length && $actionInput.val()) {
                    action = $actionInput.val();
                }
            }
            
            // Get condition
            const condition = $rule.find('.ka-rule-condition').val() || 'specific_pages';
            
            // Get pages as array
            const pages = [];
            $rule.find('.ka-rule-pages option:selected').each(function() {
                pages.push($(this).val());
            });
            
            // Collect rule data with new structure
            const ruleData = {
                action: 'king_addons_preloader_save_rule',
                nonce: kngPreloaderAdmin.nonce,
                rule_id: $rule.attr('data-rule-id') || '',
                enabled: $rule.find('.ka-rule-enabled').is(':checked') ? '1' : '0',
                priority: $rule.find('.ka-rule-priority').val() || 10,
                rule_action: action, // show / hide / override
                condition: condition,
                condition_value: $rule.find('.ka-rule-condition-value, .ka-rule-url-value, .ka-rule-posttype').val() || '',
                pages: pages,
                template: $rule.find('.ka-rule-template').val() || '',
                override_colors: $rule.find('.ka-rule-override-colors').is(':checked') ? '1' : '0',
                bg_color: $rule.find('.ka-rule-bg-color').val() || '#ffffff',
                accent_color: $rule.find('.ka-rule-accent-color').val() || '#0071e3'
            };

            // Show saving state
            $saveBtn.text('Saving...').prop('disabled', true);

            console.log('Saving rule:', ruleData);

            // Send AJAX request
            $.ajax({
                url: kngPreloaderAdmin.ajaxUrl,
                type: 'POST',
                data: ruleData,
                success: function (response) {
                    console.log('Save response:', response);
                    if (response.success) {
                        // Update rule ID if it was a new rule
                        if (response.data.rule && response.data.rule.id) {
                            $rule.attr('data-rule-id', response.data.rule.id);
                        }

                        // Update header display
                        const actionLabels = { 'show': 'Show', 'hide': 'Hide', 'override': 'Override' };
                        const conditionLabels = {
                            'specific_pages': 'Specific Pages',
                            'all_pages': 'All Pages',
                            'front_page': 'Front Page',
                            'blog_page': 'Blog Page',
                            'all_posts': 'All Posts',
                            'post_type': 'Post Type',
                            'archive': 'Archive',
                            'search': 'Search',
                            '404': '404 Page',
                            'url_contains': 'URL Contains',
                            'url_equals': 'URL Equals'
                        };
                        
                        $rule.find('.ka-preloader-rule-item__action').text(actionLabels[action] || action);
                        $rule.find('.ka-preloader-rule-item__type').text(conditionLabels[condition] || condition);
                        
                        // Show pages count for specific_pages
                        if (condition === 'specific_pages' && pages.length > 0) {
                            $rule.find('.ka-preloader-rule-item__value').text(pages.length + ' page(s)');
                        } else {
                            $rule.find('.ka-preloader-rule-item__value').text(ruleData.condition_value || '');
                        }

                        // Hide details
                        $rule.find('.ka-preloader-rule-item__details').slideUp(200);

                        // Show success
                        $saveBtn.text('Saved!');
                        setTimeout(() => {
                            $saveBtn.text(originalText).prop('disabled', false);
                        }, 1500);
                    } else {
                        alert(response.data?.message || kngPreloaderAdmin.strings.error);
                        $saveBtn.text(originalText).prop('disabled', false);
                    }
                },
                error: function () {
                    alert(kngPreloaderAdmin.strings.error);
                    $saveBtn.text(originalText).prop('disabled', false);
                }
            });
        },

        /**
         * Delete rule via AJAX
         */
        deleteRule: function ($rule) {
            const ruleId = $rule.attr('data-rule-id');
            
            // If it's a new unsaved rule, just remove from DOM
            if (!ruleId || ruleId.startsWith('new_')) {
                $rule.slideUp(200, function () {
                    $(this).remove();
                    KngPreloaderAdmin.checkEmptyRules();
                });
                return;
            }

            // Send AJAX request
            $.ajax({
                url: kngPreloaderAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'king_addons_preloader_delete_rule',
                    nonce: kngPreloaderAdmin.nonce,
                    rule_id: ruleId
                },
                success: function (response) {
                    if (response.success) {
                        $rule.slideUp(200, function () {
                            $(this).remove();
                            KngPreloaderAdmin.checkEmptyRules();
                            KngPreloaderAdmin.updateRuleIndices();
                        });
                    } else {
                        alert(response.data?.message || kngPreloaderAdmin.strings.error);
                    }
                },
                error: function () {
                    alert(kngPreloaderAdmin.strings.error);
                }
            });
        },

        /**
         * Cancel rule edit
         */
        cancelRuleEdit: function ($rule) {
            const ruleId = $rule.attr('data-rule-id');
            
            // If it's a new unsaved rule, remove it
            if (ruleId && ruleId.startsWith('new_')) {
                $rule.slideUp(200, function () {
                    $(this).remove();
                    KngPreloaderAdmin.checkEmptyRules();
                });
            } else {
                // Just hide details for existing rules
                $rule.find('.ka-preloader-rule-item__details').slideUp(200);
            }
        },

        /**
         * Check if rules list is empty and show empty state
         */
        checkEmptyRules: function () {
            const $rulesList = $('#ka-rules-list');
            const rulesCount = $rulesList.find('.ka-preloader-rule-item').length;
            
            if (rulesCount === 0) {
                $('#ka-rules-empty').show();
            }
        },

        /**
         * Add new rule
         */
        addNewRule: function () {
            // Get rules list, try ID first then class
            let $rulesList = $('#ka-rules-list');
            if (!$rulesList.length) {
                $rulesList = this.elements.rulesList;
            }
            
            if (!$rulesList.length) {
                console.error('Rules list container not found');
                return;
            }
            
            // Hide empty state
            $('#ka-rules-empty').hide();
            
            // Try to use template from PHP first
            const $template = $('#ka-rule-template');
            let $newRule;
            
            if ($template.length) {
                // Clone template content
                $newRule = $($template.html()).clone();
                $newRule.attr('data-rule-id', 'new_' + Date.now());
                $newRule.find('.ka-preloader-rule-item__details').show();
            } else {
                // Fallback to JS-generated HTML
                const ruleIndex = $rulesList.find('.ka-preloader-rule-item').length;
                $newRule = $(`
                    <div class="ka-preloader-rule-item expanded" data-index="${ruleIndex}">
                        <div class="ka-preloader-rule-header">
                            <span class="ka-preloader-rule-title">New Rule</span>
                            <div class="ka-preloader-rule-actions">
                                <button type="button" class="ka-preloader-rule-remove">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M18 6L6 18M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="ka-preloader-rule-content">
                            <div class="ka-preloader-rule-field">
                                <label>Rule Name</label>
                                <input type="text" name="rules[${ruleIndex}][name]" value="New Rule" class="ka-preloader-rule-name">
                            </div>
                            <div class="ka-preloader-rule-field">
                                <label>Rule Type</label>
                                <select name="rules[${ruleIndex}][type]" class="ka-preloader-rule-type">
                                    <option value="include">Show On (Include)</option>
                                    <option value="exclude">Hide On (Exclude)</option>
                                </select>
                            </div>
                            <div class="ka-preloader-rule-field">
                                <label>Condition</label>
                                <select name="rules[${ruleIndex}][condition]" class="ka-preloader-rule-condition">
                                    <option value="entire_site">Entire Site</option>
                                    <option value="front_page">Front Page</option>
                                    <option value="blog_page">Blog Page</option>
                                    <option value="posts">All Posts</option>
                                    <option value="pages">All Pages</option>
                                    <option value="archives">Archives</option>
                                    <option value="search">Search Results</option>
                                    <option value="404">404 Page</option>
                                    <option value="specific_page">Specific Page</option>
                                    <option value="specific_post">Specific Post</option>
                                </select>
                            </div>
                            <div class="ka-preloader-rule-field ka-preloader-rule-value-field" style="display: none;">
                                <label>Value</label>
                                <input type="text" name="rules[${ruleIndex}][value]" class="ka-preloader-rule-value" placeholder="Enter ID or slug">
                            </div>
                            <div class="ka-preloader-rule-field">
                                <label>
                                    <input type="checkbox" name="rules[${ruleIndex}][enabled]" value="1" checked>
                                    Enable this rule
                                </label>
                            </div>
                        </div>
                    </div>
                `);
            }

            $rulesList.append($newRule);

            // Initialize WP color picker on newly added rule (alpha-enabled)
            if ($newRule && $newRule.length) {
                const $newPickers = $newRule.find('.ka-color-picker, .ka-preloader-wp-color-picker');
                if ($.fn.wpColorPicker && $newPickers.length) {
                    $newPickers.each(function () {
                        const $input = $(this);
                        if ($input.hasClass('wp-color-picker')) {
                            return;
                        }

                        $input.wpColorPicker({
                            defaultColor: $input.data('default-color'),
                            change: function () {
                                KngPreloaderAdmin.updatePreview();
                            },
                            clear: function () {
                                KngPreloaderAdmin.updatePreview();
                            }
                        });
                    });
                }
            }

            // Scroll to new rule
            if ($newRule && $newRule[0]) {
                $newRule[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        },

        /**
         * Update rule conditions based on type
         */
        updateRuleConditions: function ($rule, condition) {
            // Find value field (support both class naming conventions)
            const $valueField = $rule.find('.ka-preloader-rule-value-field');
            const $conditionValueRow = $rule.find('.ka-row').filter(function() {
                return $(this).find('.ka-rule-condition-value').length > 0;
            });

            // Conditions that need a value input
            const needsValue = ['url_contains', 'url_equals', 'url_regex', 'query_param', 'specific_page', 'specific_post', 'specific_cpt', 'post_type', 'page_template'];
            
            if (needsValue.includes(condition)) {
                $valueField.slideDown(200);
                $conditionValueRow.slideDown(200);
            } else {
                $valueField.slideUp(200);
                $conditionValueRow.slideUp(200);
            }
        },

        /**
         * Update rule indices after deletion
         */
        updateRuleIndices: function () {
            this.elements.rulesList.find('.ka-preloader-rule-item').each(function (index) {
                const $rule = $(this);
                $rule.attr('data-index', index);
                
                // Update all input names
                $rule.find('input, select').each(function () {
                    const $field = $(this);
                    const name = $field.attr('name');
                    if (name) {
                        $field.attr('name', name.replace(/\[\d+\]/, '[' + index + ']'));
                    }
                });
            });
        },

        /**
         * Initialize import/export
         */
        initImportExport: function () {
            // Export button
            $(document).on('click', '.ka-preloader-export-btn', function (e) {
                e.preventDefault();
                KngPreloaderAdmin.exportSettings();
            });

            // Import button
            $(document).on('click', '.ka-preloader-import-btn', function (e) {
                e.preventDefault();
                KngPreloaderAdmin.importSettings();
            });

            // Copy to clipboard
            $(document).on('click', '.ka-preloader-copy-btn', function (e) {
                e.preventDefault();
                const $textarea = $(this).siblings('textarea');
                $textarea.select();
                document.execCommand('copy');
                
                const $btn = $(this);
                const originalText = $btn.text();
                $btn.text('Copied!');
                setTimeout(() => $btn.text(originalText), 2000);
            });
        },

        /**
         * Export settings
         */
        exportSettings: function () {
            const settings = this.collectFormSettings();
            const exportData = JSON.stringify(settings, null, 2);
            
            const $exportArea = $('.ka-preloader-export-area');
            $exportArea.val(exportData).show();
        },

        /**
         * Import settings
         */
        importSettings: function () {
            const $importArea = $('.ka-preloader-import-area');
            const importData = $importArea.val().trim();

            if (!importData) {
                alert('Please paste settings JSON to import.');
                return;
            }

            try {
                const settings = JSON.parse(importData);
                this.applyImportedSettings(settings);
                alert('Settings imported successfully! Please save to apply.');
            } catch (e) {
                alert('Invalid JSON format. Please check your import data.');
            }
        },

        /**
         * Apply imported settings to form
         */
        applyImportedSettings: function (settings) {
            for (const [key, value] of Object.entries(settings)) {
                const $field = this.elements.form.find('[name="' + key + '"]');
                
                if (!$field.length) continue;

                if ($field.is(':checkbox')) {
                    $field.prop('checked', value === '1');
                } else if ($field.is(':radio')) {
                    $field.filter('[value="' + value + '"]').prop('checked', true);
                } else if ($field.is('select')) {
                    $field.val(value);
                } else {
                    $field.val(value);
                }
            }

            // Update preview
            this.updatePreview();

            // Update preset card selection
            const preset = settings.template || settings.preset;
            if (preset) {
                $('.ka-preloader-preset-card').removeClass('active');
                $('.ka-preloader-preset-card[data-preset="' + preset + '"]').addClass('active');
            }
        },

        /**
         * Initialize dark mode toggle
         */
        initDarkModeToggle: function () {
            $(document).on('change', '.ka-preloader-dark-toggle input', function () {
                const isDark = $(this).is(':checked');
                
                if (isDark) {
                    $('.ka-preloader-admin-page').addClass('dark-mode');
                    localStorage.setItem('kng_preloader_dark_mode', '1');
                } else {
                    $('.ka-preloader-admin-page').removeClass('dark-mode');
                    localStorage.setItem('kng_preloader_dark_mode', '0');
                }
            });

            // Restore preference
            const savedDarkMode = localStorage.getItem('kng_preloader_dark_mode');
            if (savedDarkMode === '1') {
                $('.ka-preloader-dark-toggle input').prop('checked', true).trigger('change');
            }
        },

        /**
         * Initialize form submit
         */
        initFormSubmit: function () {
            this.elements.form.on('submit', function (e) {
                // Let the form submit naturally, just show a loading state
                const $submitBtn = $(this).find('button[type="submit"]');
                $submitBtn.prop('disabled', true).text('Saving...');
            });
        },

        /**
         * Initialize preview modal
         */
        initPreviewModal: function () {
            $(document).on('click', '.ka-preloader-full-preview-btn', function (e) {
                e.preventDefault();
                KngPreloaderAdmin.openPreviewModal();
            });

            // Close on Escape key
            $(document).on('keydown', function (e) {
                if (e.key === 'Escape') {
                    KngPreloaderAdmin.closePreviewModal();
                }
            });
        },

        /**
         * Open preview modal
         */
        openPreviewModal: function () {
            // Prefer template-provided modal implementation when available.
            if (typeof window.kaPreloaderOpenPreview === 'function') {
                window.kaPreloaderOpenPreview();
                return;
            }

            const settings = this.collectFormSettings();
            const $modal = $('.ka-preloader-preview-modal');

            if (!$modal.length) {
                return;
            }

            // Build preview HTML
            const bgColor = settings.bg_color || settings.background_color || 'rgba(0,0,0,0)';
            const presetId = settings.template || settings.preset || 'spinner-circle';
            const sizePx = (settings.spinner_size || settings.animation_size || 48) + 'px';

            const presetHtml = this.getPresetHtml(presetId);
            const previewHtml = `
                <div class="kng-site-preloader" style="
                    --kng-preloader-accent: ${settings.accent_color || '#0071e3'};
                    --kng-preloader-bg: ${bgColor};
                    --kng-preloader-text: ${settings.text_color || '#1d1d1f'};
                    --kng-preloader-size: ${sizePx};
                ">
                    <div class="kng-site-preloader__overlay" style="background: ${bgColor}"></div>
                    <div class="kng-site-preloader__content">
                        <div class="kng-site-preloader__animation">${presetHtml}</div>
                        ${settings.text_content ? '<div class="kng-site-preloader__text">' + settings.text_content + '</div>' : ''}
                    </div>
                </div>
            `;

            const $container = $('#ka-preloader-preview-container');
            if ($container.length) {
                $container.html(previewHtml);
                $modal.css('display', 'flex');
                $('body').css('overflow', 'hidden');
            }
        },

        /**
         * Close preview modal
         */
        closePreviewModal: function () {
            // Prefer template-provided modal implementation when available.
            if (typeof window.kaPreloaderClosePreview === 'function') {
                window.kaPreloaderClosePreview();
                return;
            }

            const $modal = $('.ka-preloader-preview-modal');
            if ($modal.length) {
                $modal.css('display', 'none').removeClass('active');
            }
            $('body').css('overflow', '').removeClass('ka-preloader-modal-open');
        },

        /**
         * Debounce helper
         */
        debounce: function (func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func.apply(KngPreloaderAdmin, args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    };

    // Initialize on DOM ready
    $(document).ready(function () {
        KngPreloaderAdmin.init();
    });

    // Expose globally for debugging
    window.KngPreloaderAdmin = KngPreloaderAdmin;

})(jQuery);
