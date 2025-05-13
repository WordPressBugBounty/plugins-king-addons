(function($, elementor) {
    'use strict';

    // Ensure AI field localization exists
    if (!window.KingAddonsAiImageField) {
        return;
    }
    var data = window.KingAddonsAiImageField;

    /**
     * Injects the Generate Image button and prompt UI into media controls
     * @param {jQuery} $container Elementor panel container
     */
    function injectImageControls($container) {
        $container.find(
            '.elementor-control.elementor-control-type-media, '
          + '.elementor-control.elementor-control-type-image, '
          + '.elementor-control.elementor-control-type-gallery'
        ).each(function() {
            var $ctrl = $(this);
            // Skip if already injected
            if ($ctrl.find('.kng-ai-image-btn-wrapper').length) {
                return;
            }
            // Find the label of the media field (not nested labels like Resolution)
            var $mediaTitle = $ctrl.find('.elementor-control-field.elementor-control-media > .elementor-control-title').first();
            if (!$mediaTitle.length) {
                // fallback to any control title
                $mediaTitle = $ctrl.find('.elementor-control-title').first();
            }
            // Wrap button and prompt in container
            var $outer = $('<div class="kng-ai-image-field-wrapper"></div>');
            // Insert button/prompt wrapper after the input wrapper if available, otherwise after the title
            var $inputWrapper = $ctrl.find('.elementor-control-input-wrapper').first();
            if ($inputWrapper.length) {
                $inputWrapper.after($outer);
            } else {
                $mediaTitle.after($outer);
            }
            var $wrapper = $('<div class="kng-ai-image-btn-wrapper" style="margin-top:8px;"></div>');
            // Button with icon, matching AI text feature
            var $btn = $('<button type="button" class="kng-ai-image-generate-btn elementor-button elementor-size-sm"><img src="' + data.icon_url + '" style="width:16px;height:16px;margin-right:6px;vertical-align:middle;"/><span>Generate Image</span></button>');
            $outer.append($wrapper);
            $wrapper.append($btn);

            $btn.on('click', function(e) {
                e.preventDefault();
                // Remove existing prompt UI
                $outer.find('.kng-ai-image-prompt-container').remove();

                // FIRST_EDIT: Check API key validity before proceeding
                var apiKeyValid = true;
                $.ajax({
                    url: KingAddonsAiImageField.ajax_url,
                    method: 'POST',
                    async: false,
                    dataType: 'json',
                    data: {
                        action: 'king_addons_ai_image_check_limits',
                        nonce: KingAddonsAiImageField.generate_nonce
                    },
                    success: function(resp) {
                        apiKeyValid = resp.success && resp.data.api_key_valid === true;
                    },
                    error: function() {
                        apiKeyValid = true; // on error assume valid
                    }
                });
                if (!apiKeyValid) {
                    var settingsUrl = window.KingAddonsAiImageField && window.KingAddonsAiImageField.settings_url
                        ? window.KingAddonsAiImageField.settings_url
                        : '/wp-admin/admin.php?page=king-addons-ai-settings';
                    var $errorMessage = $('<div class="kng-ai-image-error-message"></div>').css({
                        background: '#e7f3fe',
                        color: '#084d7a',
                        padding: '10px 15px',
                        borderRadius: '4px',
                        border: '1px solid #b6e0fe',
                        margin: '8px 0',
                        fontSize: '13px',
                        fontWeight: '500',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'space-between'
                    });
                    $errorMessage.html(
                        '<span>OpenAI API key is missing or invalid. Please configure your API key in AI Settings.</span>' +
                        '<a href="' + settingsUrl + '" style="color:#0073aa;text-decoration:underline;white-space:nowrap;margin-left:10px;" target="_blank">Settings</a>'
                    );
                    $wrapper.after($errorMessage).hide().fadeIn(200);
                    setTimeout(function() { $errorMessage.fadeOut(200, function() { $(this).remove(); }); }, 5000);
                    return;
                }

                // Proceed to show prompt UI
                // Create prompt UI
                var $promptContainer = $('<div class="kng-ai-image-prompt-container"></div>').css({margin:'8px 0'});
                var $input = $('<input type="text" class="kng-ai-image-prompt-input" placeholder="Enter image prompt..." style="width:100%;padding:6px 10px;border:1px solid #ccc;border-radius:4px;margin-bottom:6px;"/>');
                var $quality = $('<select class="kng-ai-image-quality" style="margin-right:6px;"></select>');
                ['hd','standard'].forEach(function(q) {
                    $quality.append('<option value="'+q+'">'+q.charAt(0).toUpperCase()+q.slice(1)+'</option>');
                });
                var $resolution = $('<select class="kng-ai-image-resolution" style="margin-right:6px;"></select>');
                // Define resolutions with display labels
                var resolutions = [
                    { value: '1024x1024', label: '1024×1024 (square)' },
                    { value: '1024x1792', label: '1024×1792 (portrait)' },
                    { value: '1792x1024', label: '1792×1024 (landscape)' }
                ];
                resolutions.forEach(function(res) {
                    $resolution.append('<option value="' + res.value + '">' + res.label + '</option>');
                });
                // Model selector reflecting saved default
                var $modelSelect = $('<select class="kng-ai-image-model" style="margin-right:6px;"></select>');
                var imageModels = [
                    { value: 'dall-e-3', label: 'DALL·E 3' },
                    { value: 'gpt-image-1', label: 'GPT Image 1' }
                ];
                imageModels.forEach(function(m) {
                    $modelSelect.append('<option value="' + m.value + '">' + m.label + '</option>');
                });
                // Apply default from settings
                if (KingAddonsAiImageField.image_model) {
                    $modelSelect.val(KingAddonsAiImageField.image_model);
                }
                // Define controls per model
                var modelControls = {
                    'dall-e-3': {
                        qualities: [
                            { value: 'hd', label: 'HD (default)' },
                            { value: 'standard', label: 'Standard' }
                        ],
                        sizes: [
                            { value: '1024x1024', label: '1024×1024 (square)' },
                            { value: '1024x1792', label: '1024×1792 (portrait)' },
                            { value: '1792x1024', label: '1792×1024 (landscape)' }
                        ]
                    },
                    'gpt-image-1': {
                        qualities: [
                            { value: 'high', label: 'High (default)' },
                            { value: 'medium', label: 'Medium' },
                            { value: 'low', label: 'Low' },
                            { value: 'auto', label: 'Auto' },
                        ],
                        sizes: [
                            { value: 'auto', label: 'Auto (default)' },
                            { value: '1024x1024', label: '1024×1024 (square)' },
                            { value: '1536x1024', label: '1536×1024 (landscape)' },
                            { value: '1024x1536', label: '1024×1536 (portrait)' }
                        ]
                    }
                };
                // Function to update selects based on chosen model
                function updateControlsByModel() {
                    var m = $modelSelect.val();
                    // update quality options
                    $quality.empty();
                    modelControls[m].qualities.forEach(function(opt) {
                        $quality.append('<option value="'+opt.value+'">'+opt.label+'</option>');
                    });
                    // update size options
                    $resolution.empty();
                    modelControls[m].sizes.forEach(function(opt) {
                        $resolution.append('<option value="'+opt.value+'">'+opt.label+'</option>');
                    });
                }
                // Bind change handler and initialize
                $modelSelect.on('change', updateControlsByModel);
                updateControlsByModel();
                // Create transparent background checkbox (only for GPT Image 1)
                var $bgCheckbox = $('<label class="kng-ai-image-bg-checkbox-label" style="margin-right: 6px;margin-bottom: 8px;margin-top: 8px; display:none;"><input type="checkbox" class="kng-ai-image-bg-checkbox" style="margin-right: 8px; border-color: #ccc;"/>Transparent background</label>');
                // Update show/hide of background checkbox
                function updateBgCheckbox() {
                    if ($modelSelect.val() === 'gpt-image-1') {
                        $bgCheckbox.show();
                    } else {
                        $bgCheckbox.hide().find('input').prop('checked', false);
                    }
                }
                $modelSelect.on('change', updateBgCheckbox);
                updateBgCheckbox();
                // Prompt submit button with icon
                var $submit = $('<button type="button" class="kng-ai-image-prompt-submit elementor-button elementor-button-primary elementor-size-sm"><img src="' + data.icon_url + '" style="width:16px;height:16px;margin-right:6px;vertical-align:middle;"/><span>Generate</span></button>').css({marginRight:'6px'});
                // Prompt cancel button with cross icon
                var $cancel = $('<button type="button" class="kng-ai-image-prompt-cancel elementor-button elementor-button-link elementor-size-sm" title="Cancel"><span class="kng-ai-image-cancel-icon">✕</span></button>');
                // Append input and labelled rows for each control and buttons
                $promptContainer.append(
                    $input,
                    $('<div class="kng-ai-image-field-row"></div>').append(
                        '<label class="kng-ai-image-field-label">Quality</label>',
                        $quality
                    ),
                    $('<div class="kng-ai-image-field-row"></div>').append(
                        '<label class="kng-ai-image-field-label">Size</label>',
                        $resolution
                    ),
                    $('<div class="kng-ai-image-field-row"></div>').append(
                        '<label class="kng-ai-image-field-label">Model</label>',
                        $modelSelect
                    ),
                    $('<div class="kng-ai-image-field-row"></div>').append($bgCheckbox),
                    $('<div class="kng-ai-image-field-row kng-ai-image-field-row--buttons"></div>').append(
                        $submit,
                        $cancel
                    )
                );
                $outer.append($promptContainer);
                // Add user note about processing time
                $promptContainer.append($('<p class="kng-ai-image-prompt-note">Complex prompts may take up to 2 minutes to process.</p>'));
                // Mark the Generate Image button as active (container open)
                $btn.addClass('is-active');

                $submit.on('click', function() {
                    var prompt = $input.val().trim();
                    if (!prompt) { $input.css('border-color','red'); return; }
                    var selectedResolution = $resolution.val();
                    var selectedQuality = $quality.val();
                    var selectedModel = $modelSelect.val();
                    // Disable UI and animate while processing
                    $submit.prop('disabled', true).addClass('is-processing');
                    // Change button text preserving icon
                    $submit.find('span').text('Generating...');
                    // Animate image preview border
                    var $preview = $ctrl.find('.elementor-control-media__preview');
                    if ($preview.length) {
                        $preview.addClass('king-addons-field-pulsing');
                    }
                    // AJAX request
                    $.post(KingAddonsAiImageField.ajax_url, {
                        action: KingAddonsAiImageField.generate_action,
                        nonce: KingAddonsAiImageField.generate_nonce,
                        prompt: prompt,
                        quality: selectedQuality,
                        size: selectedResolution,
                        model: selectedModel,
                        background: $bgCheckbox.find('input').is(':checked') ? 'transparent' : '',
                    }).done(function(response) {
                        if (response.success && response.data.attachment_id) {
                            var attachmentID = response.data.attachment_id;
                            var imageURL = response.data.url;
                            
                            // Get the current element being edited
                            var currentElement = elementor.getPanelView().getCurrentPageView().getOption('editedElementView');
                            if (currentElement) {
                                // Get the control name from the hidden input
                                var controlName = $ctrl.find('input[type="hidden"][data-setting]').data('setting');
                                if (controlName) {
                                    // Create the image data object
                                    var imageData = {
                                        id: attachmentID,
                                        url: imageURL
                                    };
                                    
                                    // Update the element's settings
                                    var settings = {};
                                    settings[controlName] = imageData;
                                    
                                    // Use $e.run to properly update the element
                                    $e.run('document/elements/settings', {
                                        container: currentElement.getContainer(),
                                        settings: settings
                                    });
                                    
                                    // Update the control preview
                                    if ($preview.length) {
                                        $preview.css('background-image', 'url(' + imageURL + ')');
                                    }
                                }
                            }
                        } else {
                            alert('Error generating image. ' + response.responseJSON.data.message);
                        }
                    }).fail(function(response) {
                        alert('Error generating image. ' + response.responseJSON.data.message);
                    }).always(function() {
                        // Clean up animations and UI
                        $submit.prop('disabled', false).removeClass('is-processing').find('span').text('Generate');
                        if (typeof $preview !== 'undefined' && $preview.length) {
                            $preview.removeClass('king-addons-field-pulsing');
                        }
                        // Remove active state on complete
                        $btn.removeClass('is-active');
                        $promptContainer.remove();
                    });
                });

                $cancel.on('click', function() {
                    // Remove active state when closing container
                    $btn.removeClass('is-active');
                    $promptContainer.remove();
                });
            });
        });
    }

    // Global observer: watch for any media control fields added anywhere
    var __kngGlobalObserver = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            Array.prototype.forEach.call(mutation.addedNodes, function(node) {
                if (node.nodeType !== 1) {
                    return;
                }
                var $node = $(node);
                // Check for media field or containing media field
                var $mediaField = $node.is('.elementor-control-field.elementor-control-media') ? $node : $node.find('.elementor-control-field.elementor-control-media');
                if (!$mediaField.length) {
                    return;
                }
                // Find the parent control wrapper and its panel
                var $ctrl = $mediaField.closest('.elementor-control.elementor-control-type-media, .elementor-control.elementor-control-type-image, .elementor-control.elementor-control-type-gallery');
                if (!$ctrl.length) {
                    return;
                }
                var $panel = $ctrl.closest('.elementor-panel');
                if ($panel.length) {
                    injectImageControls($panel);
                }
            });
        });
    });
    __kngGlobalObserver.observe(document.body, { childList: true, subtree: true });

    // On widget panel open, inject image controls and watch for dynamically added controls
    elementor.hooks.addAction('panel/open_editor/widget', function(panel) {
        var $panelEl = panel.$el;
        // Initial injection after panel renders
        setTimeout(function() {
            injectImageControls($panelEl);
        }, 250);
        // Setup a MutationObserver to catch lazy-loaded controls
        var root = $panelEl[0];
        if (!root.__kngAiObserver) {
            var observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    Array.prototype.forEach.call(mutation.addedNodes, function(node) {
                        if (node.nodeType === 1 && (node.matches('.elementor-control.elementor-control-type-media, .elementor-control.elementor-control-type-image, .elementor-control.elementor-control-type-gallery') ||
                            $(node).find('.elementor-control.elementor-control-type-media, .elementor-control.elementor-control-type-image, .elementor-control.elementor-control-type-gallery').length)) {
                            injectImageControls($panelEl);
                        }
                    });
                });
            });
            observer.observe(root, { childList: true, subtree: true });
            root.__kngAiObserver = observer;
        }
        // Extra delayed injections to catch any late-rendered controls
        setTimeout(function() { injectImageControls($panelEl); }, 800);
        setTimeout(function() { injectImageControls($panelEl); }, 1500);
    });

    // Also monitor section changes (e.g., switching tabs)
    elementor.channels.editor.on('section:activated', function(sectionName, editor) {
        var panelView = editor.getOption('editedElementView').getContainer().panel;
        if (panelView && panelView.$el) {
            setTimeout(function() {
                injectImageControls(panelView.$el);
            }, 150);
        }
    });

    // Re-inject image controls whenever a control view is opened
    elementor.hooks.addAction('panel/open_editor/control', function(controlView) {
        var panelEl = controlView.$el.closest('.elementor-panel');
        if (panelEl.length) {
            injectImageControls(panelEl);
        }
    });

    // Listen for Background Type toggles and re-inject Generate Image buttons when sub-controls appear
    $(document).on('change', '.elementor-control.elementor-control-background_background input[type="radio"]', function() {
        var panelEl = $(this).closest('.elementor-panel');
        setTimeout(function() {
            injectImageControls(panelEl);
        }, 100);
    });
})(jQuery, window.elementor); 