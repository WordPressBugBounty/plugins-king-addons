/**
 * Custom Code Manager - Admin JavaScript
 */

(function($) {
    'use strict';

    // Global state
    let config = {};
    let editor = null;
    let currentType = 'css';
    let rules = [];
    let ruleIndex = 0;
    let importData = null;

    /**
     * Initialize
     */
    function init() {
        // Load initial config if available
        if (typeof window.kngCCInitialConfig !== 'undefined') {
            config = window.kngCCInitialConfig;
            rules = config.rules || [];
            currentType = config.type || 'css';
        }

        // Initialize components based on page
        initListPage();
        initEditorPage();
        initSettingsPage();
        initImportExportPage();
    }

    // =========================================================================
    // List Page
    // =========================================================================

    function initListPage() {
        const $list = $('#kng-cc-snippets-list');
        if (!$list.length) return;

        // Search filter
        $('#kng-cc-search').on('input', debounce(filterSnippets, 300));

        // Dropdown filters
        $('.kng-cc-filter').on('change', filterSnippets);

        // Select all checkbox
        $('.kng-cc-select-all').on('change', function() {
            const checked = $(this).prop('checked');
            $('.kng-cc-row-check:visible').prop('checked', checked);
        });

        // Bulk actions
        $('#kng-cc-bulk-apply').on('click', handleBulkAction);

        // Status toggle
        $(document).on('click', '.kng-cc-status-toggle', handleStatusToggle);

        // Duplicate button
        $(document).on('click', '.kng-cc-duplicate-btn', handleDuplicate);

        // Export single button
        $(document).on('click', '.kng-cc-export-btn', handleExportSingle);

        // Delete button
        $(document).on('click', '.kng-cc-delete-btn', handleDelete);
    }

    function filterSnippets() {
        const search = $('#kng-cc-search').val().toLowerCase();
        const typeFilter = $('[data-filter="type"]').val();
        const statusFilter = $('[data-filter="status"]').val();
        const locationFilter = $('[data-filter="location"]').val();

        $('.kng-cc-row').each(function() {
            const $row = $(this);
            const name = $row.find('.kng-cc-name-link').text().toLowerCase();
            const type = $row.data('type');
            const status = $row.data('status');
            const location = $row.data('location');

            let visible = true;

            if (search && !name.includes(search)) {
                visible = false;
            }
            if (typeFilter && type !== typeFilter) {
                visible = false;
            }
            if (statusFilter && status !== statusFilter) {
                visible = false;
            }
            if (locationFilter && location !== locationFilter) {
                visible = false;
            }

            $row.toggle(visible);
        });
    }

    function handleBulkAction() {
        const action = $('#kng-cc-bulk-action').val();
        if (!action) return;

        const ids = [];
        $('.kng-cc-row-check:checked').each(function() {
            ids.push($(this).val());
        });

        if (!ids.length) {
            showNotice(kngCCAdmin.strings.selectSnippets, 'warning');
            return;
        }

        if (action === 'delete' && !confirm(kngCCAdmin.strings.confirmBulkDelete)) {
            return;
        }

        $.ajax({
            url: kngCCAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'kng_cc_bulk_action',
                nonce: kngCCAdmin.nonce,
                bulk_action: action,
                ids: ids
            },
            success: function(response) {
                if (response.success) {
                    showNotice(response.data.message, 'success');
                    location.reload();
                } else {
                    showNotice(response.data.message || kngCCAdmin.strings.error, 'error');
                }
            },
            error: function() {
                showNotice(kngCCAdmin.strings.error, 'error');
            }
        });
    }

    function handleStatusToggle(e) {
        e.preventDefault();
        const $toggle = $(this);
        const id = $toggle.data('id');

        $.ajax({
            url: kngCCAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'kng_cc_toggle_snippet',
                nonce: kngCCAdmin.nonce,
                id: id
            },
            success: function(response) {
                if (response.success) {
                    const $row = $toggle.closest('.kng-cc-row');
                    $row.data('status', response.data.status);
                    $toggle.prop('checked', response.data.status === 'enabled');
                } else {
                    showNotice(response.data.message || kngCCAdmin.strings.error, 'error');
                    $toggle.prop('checked', !$toggle.prop('checked'));
                }
            },
            error: function() {
                showNotice(kngCCAdmin.strings.error, 'error');
                $toggle.prop('checked', !$toggle.prop('checked'));
            }
        });
    }

    function handleDuplicate(e) {
        e.preventDefault();
        const id = $(this).data('id');

        $.ajax({
            url: kngCCAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'kng_cc_duplicate_snippet',
                nonce: kngCCAdmin.nonce,
                id: id
            },
            success: function(response) {
                if (response.success) {
                    showNotice(kngCCAdmin.strings.duplicated, 'success');
                    location.reload();
                } else {
                    showNotice(response.data.message || kngCCAdmin.strings.error, 'error');
                }
            },
            error: function() {
                showNotice(kngCCAdmin.strings.error, 'error');
            }
        });
    }

    function handleExportSingle(e) {
        e.preventDefault();
        const id = $(this).data('id');

        $.ajax({
            url: kngCCAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'kng_cc_export_snippet',
                nonce: kngCCAdmin.nonce,
                id: id
            },
            success: function(response) {
                if (response.success) {
                    downloadJSON(response.data.export, 'snippet-' + id + '.json');
                    showNotice(kngCCAdmin.strings.exportReady, 'success');
                } else {
                    showNotice(response.data.message || kngCCAdmin.strings.error, 'error');
                }
            },
            error: function() {
                showNotice(kngCCAdmin.strings.error, 'error');
            }
        });
    }

    function handleDelete(e) {
        e.preventDefault();
        
        if (!confirm(kngCCAdmin.strings.confirmDelete)) {
            return;
        }

        const id = $(this).data('id');

        $.ajax({
            url: kngCCAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'kng_cc_delete_snippet',
                nonce: kngCCAdmin.nonce,
                id: id
            },
            success: function(response) {
                if (response.success) {
                    $('[data-id="' + id + '"]').closest('.kng-cc-row').fadeOut(300, function() {
                        $(this).remove();
                    });
                } else {
                    showNotice(response.data.message || kngCCAdmin.strings.error, 'error');
                }
            },
            error: function() {
                showNotice(kngCCAdmin.strings.error, 'error');
            }
        });
    }

    // =========================================================================
    // Editor Page
    // =========================================================================

    function initEditorPage() {
        const $form = $('#kng-cc-editor-form');
        if (!$form.length) return;

        // Initialize CodeMirror
        initCodeMirror();

        // Type tabs
        $('.kng-cc-type-tab').on('click', handleTypeChange);

        // Status toggle label
        $('#kng-cc-status').on('change', updateStatusLabel);

        // Location change
        $('#kng-cc-location').on('change', handleLocationChange);

        // Scope mode change
        $('input[name="scope_mode"]').on('change', handleScopeModeChange);

        // Rules builder
        initRulesBuilder();

        // Form submit
        $form.on('submit', handleFormSubmit);

        // Fullscreen toggle
        $('#kng-cc-fullscreen').on('click', toggleFullscreen);

        // Collapsible sections
        $('[data-collapsible]').on('click', function() {
            $(this).toggleClass('is-collapsed');
            $(this).next('.kng-cc-section-content').slideToggle(200);
        });

        // Initialize state
        updateStatusLabel();
        handleLocationChange();
        handleScopeModeChange();
        renderRules();
    }

    function initCodeMirror() {
        const $textarea = $('#kng-cc-code');
        if (!$textarea.length) return;

        const modeMap = {
            'css': 'text/css',
            'js': 'application/javascript',
            'html': 'text/html'
        };

        const settings = wp.codeEditor.defaultSettings ? _.clone(wp.codeEditor.defaultSettings) : {};
        settings.codemirror = _.extend({}, settings.codemirror, {
            mode: modeMap[currentType] || 'text/css',
            lineNumbers: true,
            lineWrapping: true,
            indentUnit: 4,
            tabSize: 4,
            indentWithTabs: false,
            autoCloseBrackets: true,
            autoCloseTags: true,
            matchBrackets: true,
            matchTags: {bothTags: true},
            highlightSelectionMatches: {
                showToken: /\w/,
                annotateScrollbar: true
            },
            foldGutter: true,
            styleActiveLine: true,
            gutters: ['CodeMirror-linenumbers', 'CodeMirror-foldgutter'],
            extraKeys: {
                'Ctrl-S': function() { saveSnippet(); },
                'Cmd-S': function() { saveSnippet(); },
                'Ctrl-Space': 'autocomplete',
                'F11': function(cm) {
                    cm.setOption('fullScreen', !cm.getOption('fullScreen'));
                },
                'Esc': function(cm) {
                    if (cm.getOption('fullScreen')) cm.setOption('fullScreen', false);
                }
            }
        });

        // Add autocomplete for CSS properties
        if (currentType === 'css' && window.CodeMirror && CodeMirror.hint && CodeMirror.hint.css) {
            settings.codemirror.hintOptions = {
                completeSingle: false
            };
        }

        editor = wp.codeEditor.initialize($textarea, settings);

        // Enhance editor instance
        if (editor && editor.codemirror) {
            const cm = editor.codemirror;
            
            // Auto-format on paste
            cm.on('paste', function() {
                setTimeout(function() {
                    if (currentType === 'css' || currentType === 'js') {
                        // Simple auto-indent
                        const totalLines = cm.lineCount();
                        cm.operation(function() {
                            for (let i = 0; i < totalLines; i++) {
                                cm.indentLine(i);
                            }
                        });
                    }
                }, 10);
            });
            
            // Show autocomplete on input for CSS
            if (currentType === 'css') {
                cm.on('inputRead', function(cm, change) {
                    if (change.text[0].match(/[a-z]/i)) {
                        CodeMirror.commands.autocomplete(cm, null, {completeSingle: false});
                    }
                });
            }
        }

        // Refresh on tab switch
        setTimeout(function() {
            if (editor && editor.codemirror) {
                editor.codemirror.refresh();
            }
        }, 100);
    }

    function handleTypeChange(e) {
        e.preventDefault();
        
        const $tab = $(this);
        if ($tab.is(':disabled') || $tab.hasClass('is-pro')) {
            if (!kngCCAdmin.hasPro) {
                showNotice(kngCCAdmin.strings.proRequired, 'warning');
            }
            return;
        }

        const type = $tab.data('type');
        currentType = type;

        // Update tabs
        $('.kng-cc-type-tab').removeClass('is-active').attr('aria-selected', 'false');
        $tab.addClass('is-active').attr('aria-selected', 'true');

        // Update hidden input
        $('#kng-cc-type').val(type);

        // Update CodeMirror mode
        if (editor && editor.codemirror) {
            const modeMap = {
                'css': 'text/css',
                'js': 'application/javascript',
                'html': 'text/html'
            };
            editor.codemirror.setOption('mode', modeMap[type] || 'text/css');
        }

        // Show/hide JS options
        $('#kng-cc-js-options').toggle(type === 'js');
    }

    function updateStatusLabel() {
        const enabled = $('#kng-cc-status').prop('checked');
        $('#kng-cc-status-label').text(enabled ? 'Enabled' : 'Disabled');
    }

    function handleLocationChange() {
        const location = $('#kng-cc-location').val();
        $('#kng-cc-custom-hook-wrap').toggle(location === 'custom_hook');
    }

    function handleScopeModeChange() {
        const mode = $('input[name="scope_mode"]:checked').val();
        
        // Update radio card visual
        $('.kng-cc-radio-card').removeClass('is-selected');
        $('input[name="scope_mode"]:checked').closest('.kng-cc-radio-card').addClass('is-selected');
        
        // Show/hide rules builder
        $('#kng-cc-rules-builder').toggle(mode !== 'global');
    }

    function handleFormSubmit(e) {
        e.preventDefault();
        saveSnippet();
    }

    function saveSnippet() {
        const $btn = $('#kng-cc-save-btn');
        const $saveIcon = $btn.find('.kng-cc-save-icon');
        const $spinner = $btn.find('.kng-cc-spinner');
        const $text = $btn.find('.kng-cc-save-text');

        // Collect form data
        collectRulesFromForm();
        
        const snippet = {
            id: parseInt($('#kng-cc-id').val()) || 0,
            title: $('#kng-cc-title').val(),
            code: editor && editor.codemirror ? editor.codemirror.getValue() : $('#kng-cc-code').val(),
            type: $('#kng-cc-type').val(),
            status: $('#kng-cc-status').prop('checked') ? 'enabled' : 'disabled',
            location: $('#kng-cc-location').val(),
            custom_hook: $('#kng-cc-custom-hook').val(),
            priority: parseInt($('#kng-cc-priority').val()) || 10,
            js_dom_ready: $('#kng-cc-js-dom-ready').prop('checked'),
            js_defer: $('#kng-cc-js-defer').prop('checked'),
            js_async: $('#kng-cc-js-async').prop('checked'),
            js_module: $('#kng-cc-js-module').prop('checked'),
            scope_mode: $('input[name="scope_mode"]:checked').val(),
            rules: rules,
            match_mode: $('#kng-cc-match-mode').val() || 'any',
            notes: $('#kng-cc-notes').val()
        };

        // Validate
        if (!snippet.title.trim()) {
            showNotice('Please enter a snippet name', 'error');
            $('#kng-cc-title').focus();
            return;
        }

        // Show loading
        $saveIcon.hide();
        $spinner.show();
        $text.text(kngCCAdmin.strings.saving);
        $btn.prop('disabled', true);

        $.ajax({
            url: kngCCAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'kng_cc_save_snippet',
                nonce: kngCCAdmin.nonce,
                snippet: JSON.stringify(snippet)
            },
            success: function(response) {
                if (response.success) {
                    $text.text(kngCCAdmin.strings.saved);
                    
                    // Update ID if new snippet
                    if (!snippet.id && response.data.id) {
                        $('#kng-cc-id').val(response.data.id);
                        
                        // Update URL without reload
                        const newUrl = window.location.href.replace('view=new', 'view=edit&id=' + response.data.id);
                        window.history.replaceState({}, '', newUrl);
                    }

                    setTimeout(function() {
                        $spinner.hide();
                        $saveIcon.show();
                        $text.text('Save Snippet');
                        $btn.prop('disabled', false);
                    }, 1500);
                } else {
                    showNotice(response.data.message || kngCCAdmin.strings.error, 'error');
                    resetSaveButton();
                }
            },
            error: function() {
                showNotice(kngCCAdmin.strings.error, 'error');
                resetSaveButton();
            }
        });

        function resetSaveButton() {
            $spinner.hide();
            $saveIcon.show();
            $text.text('Save Snippet');
            $btn.prop('disabled', false);
        }
    }

    function toggleFullscreen() {
        const $section = $('.kng-cc-code-section');
        $section.toggleClass('is-fullscreen');
        
        if (editor && editor.codemirror) {
            setTimeout(function() {
                editor.codemirror.refresh();
            }, 100);
        }
    }

    // =========================================================================
    // Rules Builder
    // =========================================================================

    function initRulesBuilder() {
        // Add rule button
        $('#kng-cc-add-rule').on('click', addRule);

        // Rule type change
        $(document).on('change', '.kng-cc-rule-type', handleRuleTypeChange);

        // Remove rule
        $(document).on('click', '.kng-cc-rule-remove', removeRule);

        // Set initial rule index
        ruleIndex = rules.length;
    }

    function renderRules() {
        const $list = $('#kng-cc-rules-list');
        $list.empty();

        rules.forEach(function(rule, index) {
            const html = createRuleHTML(index, rule);
            $list.append(html);
            renderRuleValue(index, rule);
        });
    }

    function createRuleHTML(index, rule) {
        const template = $('#kng-cc-rule-template').html();
        let html = template.replace(/\{\{index\}\}/g, index);
        
        const $rule = $(html);
        $rule.find('.kng-cc-rule-type').val(rule.type || 'page');
        
        return $rule;
    }

    function addRule() {
        const index = ruleIndex++;
        const rule = { type: 'page', value: '' };
        rules.push(rule);
        
        const html = createRuleHTML(index, rule);
        $('#kng-cc-rules-list').append(html);
        renderRuleValue(index, rule);
    }

    function removeRule() {
        const $rule = $(this).closest('.kng-cc-rule');
        const index = $rule.data('index');
        
        // Find and remove from rules array
        const ruleIndex = rules.findIndex((r, i) => {
            return $('[data-index="' + i + '"]').is($rule);
        });
        
        if (ruleIndex > -1) {
            rules.splice(ruleIndex, 1);
        }
        
        $rule.fadeOut(200, function() {
            $(this).remove();
        });
    }

    function handleRuleTypeChange() {
        const $rule = $(this).closest('.kng-cc-rule');
        const index = $rule.data('index');
        const type = $(this).val();
        
        // Update rule type
        const ruleData = getRuleByElement($rule);
        if (ruleData) {
            ruleData.type = type;
            ruleData.value = '';
        }
        
        renderRuleValue(index, { type: type, value: '' });
    }

    function getRuleByElement($rule) {
        const index = $rule.data('index');
        // Simple approach: use DOM order
        const domIndex = $rule.index();
        return rules[domIndex];
    }

    function renderRuleValue(index, rule) {
        const $rule = $('[data-index="' + index + '"]');
        const $valueContainer = $rule.find('.kng-cc-rule-value');
        $valueContainer.empty();

        let html = '';
        const type = rule.type || 'page';

        switch (type) {
            case 'page':
            case 'post':
                html = '<div class="kng-cc-rule-search-wrap">' +
                    '<input type="text" class="kng-v3-input kng-cc-rule-search" placeholder="Search ' + type + 's..." data-type="' + type + '" />' +
                    '<input type="hidden" class="kng-cc-rule-value-input" name="rules[' + index + '][value]" value="' + (rule.value || '') + '" />' +
                    '<div class="kng-cc-rule-search-results"></div>' +
                    '</div>';
                break;

            case 'post_type':
                html = '<select class="kng-v3-select kng-cc-rule-value-input" name="rules[' + index + '][value]">' +
                    '<option value="post">Posts</option>' +
                    '<option value="page">Pages</option>' +
                    '<option value="product">Products</option>' +
                    '</select>';
                break;

            case 'url_contains':
            case 'url_starts':
            case 'url_ends':
            case 'url_regex':
                html = '<input type="text" class="kng-v3-input kng-cc-rule-value-input" name="rules[' + index + '][value]" value="' + escapeHtml(rule.value || '') + '" placeholder="' + getPlaceholder(type) + '" />';
                break;

            case 'user_logged_in':
                html = '<select class="kng-v3-select kng-cc-rule-value-input" name="rules[' + index + '][value]">' +
                    '<option value="yes"' + (rule.value === 'yes' ? ' selected' : '') + '>Logged In</option>' +
                    '<option value="no"' + (rule.value === 'no' ? ' selected' : '') + '>Logged Out</option>' +
                    '</select>';
                break;

            case 'user_role':
                html = '<select class="kng-v3-select kng-cc-rule-value-input" name="rules[' + index + '][value]">' +
                    '<option value="administrator">Administrator</option>' +
                    '<option value="editor">Editor</option>' +
                    '<option value="author">Author</option>' +
                    '<option value="contributor">Contributor</option>' +
                    '<option value="subscriber">Subscriber</option>' +
                    '</select>';
                break;

            case 'device':
                html = '<select class="kng-v3-select kng-cc-rule-value-input" name="rules[' + index + '][value]">' +
                    '<option value="desktop"' + (rule.value === 'desktop' ? ' selected' : '') + '>Desktop</option>' +
                    '<option value="tablet"' + (rule.value === 'tablet' ? ' selected' : '') + '>Tablet</option>' +
                    '<option value="mobile"' + (rule.value === 'mobile' ? ' selected' : '') + '>Mobile</option>' +
                    '</select>';
                break;

            case 'front_page':
            case 'blog_page':
            case 'archive':
            case 'search':
            case '404':
                // No value needed
                html = '<div class="kng-cc-rule-no-value">No additional configuration needed</div>';
                break;

            default:
                html = '<input type="text" class="kng-v3-input kng-cc-rule-value-input" name="rules[' + index + '][value]" value="' + escapeHtml(rule.value || '') + '" />';
        }

        $valueContainer.html(html);

        // Set initial value for selects
        if (rule.value) {
            $valueContainer.find('select.kng-cc-rule-value-input').val(rule.value);
        }

        // Initialize search if needed
        if (type === 'page' || type === 'post') {
            initRuleSearch($rule.find('.kng-cc-rule-search'));
        }
    }

    function getPlaceholder(type) {
        const placeholders = {
            'url_contains': '/shop/',
            'url_starts': '/products',
            'url_ends': '/checkout/',
            'url_regex': '/\\/product\\/[0-9]+/'
        };
        return placeholders[type] || '';
    }

    function initRuleSearch($input) {
        let searchTimeout;
        
        $input.on('input', function() {
            const $wrap = $(this).closest('.kng-cc-rule-search-wrap');
            const $results = $wrap.find('.kng-cc-rule-search-results');
            const search = $(this).val();
            const type = $(this).data('type');

            clearTimeout(searchTimeout);

            if (search.length < 2) {
                $results.empty().hide();
                return;
            }

            searchTimeout = setTimeout(function() {
                $.ajax({
                    url: kngCCAdmin.ajaxUrl,
                    method: 'POST',
                    data: {
                        action: 'kng_cc_search_content',
                        nonce: kngCCAdmin.nonce,
                        search: search,
                        content_type: type
                    },
                    success: function(response) {
                        if (response.success && response.data.length) {
                            let html = '';
                            response.data.forEach(function(item) {
                                html += '<div class="kng-cc-search-result" data-id="' + item.id + '" data-title="' + escapeHtml(item.title) + '">' +
                                    escapeHtml(item.title) +
                                    '</div>';
                            });
                            $results.html(html).show();
                        } else {
                            $results.html('<div class="kng-cc-search-no-results">' + kngCCAdmin.strings.noResults + '</div>').show();
                        }
                    }
                });
            }, 300);
        });

        // Select result
        $(document).on('click', '.kng-cc-search-result', function() {
            const $wrap = $(this).closest('.kng-cc-rule-search-wrap');
            const id = $(this).data('id');
            const title = $(this).data('title');
            
            $wrap.find('.kng-cc-rule-search').val(title);
            $wrap.find('.kng-cc-rule-value-input').val(id);
            $wrap.find('.kng-cc-rule-search-results').empty().hide();
        });

        // Hide results on outside click
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.kng-cc-rule-search-wrap').length) {
                $('.kng-cc-rule-search-results').empty().hide();
            }
        });
    }

    function collectRulesFromForm() {
        rules = [];
        
        $('.kng-cc-rule').each(function() {
            const $rule = $(this);
            const type = $rule.find('.kng-cc-rule-type').val();
            const value = $rule.find('.kng-cc-rule-value-input').val() || '';
            
            rules.push({
                type: type,
                value: value
            });
        });
    }

    // =========================================================================
    // Settings Page
    // =========================================================================

    function initSettingsPage() {
        const $form = $('#kng-cc-settings-form');
        if (!$form.length) return;

        $form.on('submit', function(e) {
            e.preventDefault();
            
            const settings = {
                enabled: $form.find('[name="enabled"]').prop('checked'),
                default_location_css: $form.find('[name="default_location_css"]').val(),
                default_location_js: $form.find('[name="default_location_js"]').val(),
                default_priority: parseInt($form.find('[name="default_priority"]').val()) || 10,
                debug_mode: $form.find('[name="debug_mode"]').prop('checked')
            };

            $.ajax({
                url: kngCCAdmin.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'kng_cc_save_settings',
                    nonce: kngCCAdmin.nonce,
                    settings: JSON.stringify(settings)
                },
                success: function(response) {
                    if (response.success) {
                        showNotice(kngCCAdmin.strings.saved, 'success');
                    } else {
                        showNotice(response.data.message || kngCCAdmin.strings.error, 'error');
                    }
                },
                error: function() {
                    showNotice(kngCCAdmin.strings.error, 'error');
                }
            });
        });
    }

    // =========================================================================
    // Import/Export Page
    // =========================================================================

    function initImportExportPage() {
        const $exportBtn = $('#kng-cc-export-btn');
        const $importBtn = $('#kng-cc-import-btn');
        
        if (!$exportBtn.length && !$importBtn.length) return;

        // Export all
        $exportBtn.on('click', handleExportAll);

        // Import dropzone
        const $dropzone = $('#kng-cc-import-dropzone');
        const $fileInput = $('#kng-cc-import-file');

        $dropzone.on('click', function() {
            $fileInput.click();
        });

        $dropzone.on('dragover dragenter', function(e) {
            e.preventDefault();
            $(this).addClass('is-dragover');
        });

        $dropzone.on('dragleave dragend drop', function(e) {
            e.preventDefault();
            $(this).removeClass('is-dragover');
        });

        $dropzone.on('drop', function(e) {
            const files = e.originalEvent.dataTransfer.files;
            if (files.length) {
                handleFileSelect(files[0]);
            }
        });

        $fileInput.on('change', function() {
            if (this.files.length) {
                handleFileSelect(this.files[0]);
            }
        });

        // Remove file
        $(document).on('click', '.kng-cc-file-remove', function(e) {
            e.stopPropagation();
            importData = null;
            $dropzone.find('.kng-cc-dropzone-content').show();
            $dropzone.find('.kng-cc-dropzone-file').hide();
            $importBtn.prop('disabled', true);
            $fileInput.val('');
        });

        // Import button
        $importBtn.on('click', handleImport);
    }

    function handleExportAll() {
        $.ajax({
            url: kngCCAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'kng_cc_export_all',
                nonce: kngCCAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    const filename = 'king-addons-snippets-' + new Date().toISOString().slice(0, 10) + '.json';
                    downloadJSON(response.data.export, filename);
                    showNotice(kngCCAdmin.strings.exportReady, 'success');
                } else {
                    showNotice(response.data.message || kngCCAdmin.strings.error, 'error');
                }
            },
            error: function() {
                showNotice(kngCCAdmin.strings.error, 'error');
            }
        });
    }

    function handleFileSelect(file) {
        if (!file.name.endsWith('.json')) {
            showNotice(kngCCAdmin.strings.invalidFile, 'error');
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            try {
                importData = JSON.parse(e.target.result);
                
                if (!importData.snippets || !Array.isArray(importData.snippets)) {
                    throw new Error('Invalid format');
                }

                const $dropzone = $('#kng-cc-import-dropzone');
                $dropzone.find('.kng-cc-dropzone-content').hide();
                $dropzone.find('.kng-cc-dropzone-file').show();
                $dropzone.find('.kng-cc-file-name').text(file.name + ' (' + importData.snippets.length + ' snippets)');
                
                $('#kng-cc-import-btn').prop('disabled', false);
            } catch (err) {
                showNotice(kngCCAdmin.strings.invalidFile, 'error');
                importData = null;
            }
        };
        reader.readAsText(file);
    }

    function handleImport() {
        if (!importData) return;

        const mode = $('#kng-cc-import-mode').val();
        const $btn = $('#kng-cc-import-btn');
        
        $btn.prop('disabled', true).text('Importing...');

        $.ajax({
            url: kngCCAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'kng_cc_import',
                nonce: kngCCAdmin.nonce,
                import: JSON.stringify(importData),
                mode: mode
            },
            success: function(response) {
                if (response.success) {
                    $('#kng-cc-imported-count').text(response.data.imported);
                    $('#kng-cc-skipped-count').text(response.data.skipped);
                    $('#kng-cc-errors-count').text(response.data.errors);
                    $('#kng-cc-import-results').show();
                    
                    showNotice(kngCCAdmin.strings.importSuccess, 'success');
                } else {
                    showNotice(response.data.message || kngCCAdmin.strings.error, 'error');
                }
                
                $btn.prop('disabled', false).html(
                    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">' +
                    '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>' +
                    '<polyline points="17 8 12 3 7 8"/>' +
                    '<line x1="12" y1="3" x2="12" y2="15"/>' +
                    '</svg> Import Snippets'
                );
            },
            error: function() {
                showNotice(kngCCAdmin.strings.error, 'error');
                $btn.prop('disabled', false);
            }
        });
    }

    // =========================================================================
    // Utilities
    // =========================================================================

    function showNotice(message, type) {
        // Remove existing notices
        $('.kng-cc-notice').remove();

        const typeClass = type === 'success' ? 'kng-cc-notice--success' : 
                         type === 'warning' ? 'kng-cc-notice--warning' : 
                         'kng-cc-notice--error';

        const $notice = $('<div class="kng-cc-notice ' + typeClass + '">' +
            '<span>' + escapeHtml(message) + '</span>' +
            '<button type="button" class="kng-cc-notice-close">&times;</button>' +
            '</div>');

        $('body').append($notice);
        
        setTimeout(function() {
            $notice.addClass('is-visible');
        }, 10);

        // Auto hide after 5 seconds
        setTimeout(function() {
            $notice.removeClass('is-visible');
            setTimeout(function() {
                $notice.remove();
            }, 300);
        }, 5000);

        // Close on click
        $notice.find('.kng-cc-notice-close').on('click', function() {
            $notice.removeClass('is-visible');
            setTimeout(function() {
                $notice.remove();
            }, 300);
        });
    }

    function downloadJSON(data, filename) {
        const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    // Initialize when DOM is ready
    $(document).ready(init);

})(jQuery);

// Add notice styles dynamically
(function() {
    const style = document.createElement('style');
    style.textContent = `
        .kng-cc-notice {
            position: fixed;
            top: 50px;
            right: 20px;
            padding: 14px 20px;
            background: #1d1d1f;
            color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
            z-index: 999999;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            font-family: -apple-system, BlinkMacSystemFont, sans-serif;
            transform: translateX(120%);
            transition: transform 0.3s ease;
        }
        .kng-cc-notice.is-visible {
            transform: translateX(0);
        }
        .kng-cc-notice--success {
            background: #30d158;
        }
        .kng-cc-notice--warning {
            background: #ff9f0a;
            color: #1d1d1f;
        }
        .kng-cc-notice--error {
            background: #ff453a;
        }
        .kng-cc-notice-close {
            background: transparent;
            border: none;
            color: inherit;
            font-size: 20px;
            cursor: pointer;
            opacity: 0.7;
            line-height: 1;
        }
        .kng-cc-notice-close:hover {
            opacity: 1;
        }
        .kng-cc-rule-search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #ffffff;
            border: 1px solid #d2d2d7;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            z-index: 100;
            max-height: 200px;
            overflow-y: auto;
            display: none;
        }
        .kng-cc-search-result {
            padding: 10px 14px;
            cursor: pointer;
            border-bottom: 1px solid #e5e5e7;
        }
        .kng-cc-search-result:last-child {
            border-bottom: none;
        }
        .kng-cc-search-result:hover {
            background: #f5f5f7;
        }
        .kng-cc-search-no-results {
            padding: 12px 14px;
            color: #86868b;
            text-align: center;
        }
        .kng-cc-rule-search-wrap {
            position: relative;
        }
        .kng-cc-rule-no-value {
            font-size: 12px;
            color: #86868b;
            font-style: italic;
        }
    `;
    document.head.appendChild(style);
})();

// =========================================================================
// Theme Switcher
// =========================================================================

(function initThemeSwitcher() {
    'use strict';
    
    const THEME_KEY = 'kng_cc_theme';
    const $admin = $('.kng-cc-admin');
    
    if (!$admin.length) return;
    
    // Get saved theme or default to auto
    let currentTheme = localStorage.getItem(THEME_KEY) || 'auto';
    
    // Apply saved theme
    applyTheme(currentTheme);
    
    // Create theme switcher if in header
    const $headerRight = $('.kng-cc-header-right');
    if ($headerRight.length && !$('.kng-cc-theme-switcher').length) {
        const switcher = `
            <div class="kng-cc-theme-switcher">
                <button type="button" class="kng-cc-theme-btn" data-theme="light" title="Light Theme">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="5"/>
                        <line x1="12" y1="1" x2="12" y2="3"/>
                        <line x1="12" y1="21" x2="12" y2="23"/>
                        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
                        <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                        <line x1="1" y1="12" x2="3" y2="12"/>
                        <line x1="21" y1="12" x2="23" y2="12"/>
                        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
                        <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                    </svg>
                </button>
                <button type="button" class="kng-cc-theme-btn" data-theme="auto" title="Auto Theme">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                        <line x1="8" y1="21" x2="16" y2="21"/>
                        <line x1="12" y1="17" x2="12" y2="21"/>
                    </svg>
                </button>
                <button type="button" class="kng-cc-theme-btn" data-theme="dark" title="Dark Theme">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                    </svg>
                </button>
            </div>
        `;
        $headerRight.prepend(switcher);
        
        // Add event listeners
        $('.kng-cc-theme-btn').on('click', function() {
            const theme = $(this).data('theme');
            setTheme(theme);
        });
    }
    
    function applyTheme(theme) {
        currentTheme = theme;
        
        // Remove all theme classes
        $admin.removeClass('kng-theme-light kng-theme-dark').removeAttr('data-theme');
        
        // Update active button
        $('.kng-cc-theme-btn').removeClass('is-active');
        $(`.kng-cc-theme-btn[data-theme="${theme}"]`).addClass('is-active');
        
        if (theme === 'light') {
            $admin.attr('data-theme', 'light');
        } else if (theme === 'dark') {
            $admin.attr('data-theme', 'dark').addClass('kng-theme-dark');
        }
        // 'auto' - no class, uses prefers-color-scheme
    }
    
    function setTheme(theme) {
        applyTheme(theme);
        localStorage.setItem(THEME_KEY, theme);
        
        // Show subtle feedback
        showThemeNotice(theme);
    }
    
    function showThemeNotice(theme) {
        const themeNames = {
            light: 'Light Theme',
            dark: 'Dark Theme',
            auto: 'Auto Theme'
        };
        
        const $notice = $(`
            <div class="kng-cc-theme-notice">
                ${themeNames[theme]} activated
            </div>
        `);
        
        $('body').append($notice);
        
        setTimeout(() => {
            $notice.addClass('is-visible');
        }, 10);
        
        setTimeout(() => {
            $notice.removeClass('is-visible');
            setTimeout(() => $notice.remove(), 300);
        }, 2000);
    }
    
    // Add notice styles
    const style = document.createElement('style');
    style.textContent = `
        .kng-cc-theme-notice {
            position: fixed;
            bottom: 32px;
            right: 32px;
            background: var(--kng-v3-card-bg);
            color: var(--kng-v3-text);
            padding: 12px 20px;
            border-radius: 100px;
            font-size: 13px;
            font-weight: 500;
            box-shadow: var(--kng-v3-shadow-lg);
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 10000;
            border: 1px solid var(--kng-v3-border);
        }
        .kng-cc-theme-notice.is-visible {
            opacity: 1;
            transform: translateY(0);
        }
    `;
    document.head.appendChild(style);
})();
