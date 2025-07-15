/**
 * King Addons Media Library Alt Text Generation Script
 */
(function($, _, wp) {
    'use strict';

    $(document).ready(function() {

        // --- Media Library List View Button Handler --- //
        $('body').on('click', '.king-addons-generate-alt-button', function(e) {
            e.preventDefault();

            var $button = $(this);
            var $container = $button.closest('.king-addons-alt-text-status');
            var $spinner = $container.find('.spinner');
            var $resultSpan = $container.find('.king-addons-alt-text-result');
            var attachmentId = $container.data('attachment-id');

            if ($button.is('.disabled')) {
                return; // Prevent multiple clicks
            }

            if (!attachmentId) {
                console.error('King Addons: Missing attachment ID (List View).');
                $resultSpan.text(kingAddonsMediaAltText.error_text + ': Missing ID').css('color', 'red').show();
                return;
            }

            $button.addClass('disabled').prop('disabled', true);
            $spinner.css('display', 'inline-block');
            $resultSpan.hide().text('');

            $.ajax({
                url: kingAddonsMediaAltText.ajax_url,
                type: 'POST',
                data: {
                    action: 'king_addons_generate_single_alt',
                    nonce: kingAddonsMediaAltText.nonce,
                    attachment_id: attachmentId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $container.empty().append('<span>' + escapeHtml(response.data.alt_text) + '</span>');
                    } else {
                        var errorMessage = response.data && response.data.message ? response.data.message : kingAddonsMediaAltText.error_text;
                        $resultSpan.text(escapeHtml(errorMessage)).css('color', 'red').show();
                        $button.removeClass('disabled').prop('disabled', false);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('King Addons AJAX Error (List View):', textStatus, errorThrown, jqXHR.responseText);
                    var specificMessage = kingAddonsMediaAltText.error_text + ': ' + textStatus;
                    if (jqXHR.responseText) {
                        try {
                            var errorResponse = JSON.parse(jqXHR.responseText);
                            if (errorResponse && errorResponse.data && errorResponse.data.message) {
                                specificMessage = escapeHtml(errorResponse.data.message);
                            }
                        } catch (e) { /* Ignore */ }
                    }
                    $resultSpan.text(specificMessage).css('color', 'red').show();
                    $button.removeClass('disabled').prop('disabled', false);
                },
                complete: function() {
                    $spinner.hide();
                }
            });
        });
        // --- End Media Library List View Button Handler --- //

        // --- Media Modal Button Injection using wp.media API --- //
        if (typeof wp !== 'undefined' && wp.media) {

            // Define our custom button view for generation
            const KingAddonsGenerateButton = wp.media.view.Button.extend({
                className: 'button button-primary button-big king-addons-generate-modal-button ',
                template: _.template('<span class="spinner king-addons-modal-spinner" style="float: none; vertical-align: middle; margin-left: 5px; display: none;"></span><span class="king-addons-button-text"></span>'),

                initialize: function() {
                    wp.media.view.Button.prototype.initialize.apply(this, arguments);
                    this.model = this.options.attachmentModel;
                    this.$altInput = this.options.$altInput;
                    this.bindHandlers();
                },

                render: function() {
                    wp.media.view.Button.prototype.render.apply(this, arguments);
                    this.$el.html(this.template());
                    this.$spinner = this.$el.find('.king-addons-modal-spinner');
                    this.$buttonTextSpan = this.$el.find('.king-addons-button-text');
                    this.$buttonTextSpan.text(kingAddonsMediaAltText.generating_text ? kingAddonsMediaAltText.generating_text.replace('...', ' with AI') : 'Generate with AI');
                    return this;
                },

                bindHandlers: function() {
                    // Check if alt text exists initially and hide button if it does
                    if (this.model && this.model.get('alt')) {
                        this.$el.hide();
                    }
                    // Listen for changes on the alt text model property
                    if (this.model) {
                        this.listenTo(this.model, 'change:alt', this.toggleVisibility);
                    }
                    // Also listen to changes directly on the input field
                    if (this.$altInput) {
                        this.$altInput.on('input change', _.debounce(this.toggleVisibilityBasedOnInput.bind(this), 300));
                    }
                },

                // Hide button if alt text is added
                toggleVisibility: function() {
                    if (this.model && this.model.get('alt')) {
                        this.$el.fadeOut();
                    } else {
                        this.$el.fadeIn();
                    }
                },

                toggleVisibilityBasedOnInput: function() {
                    if (this.$altInput && this.$altInput.val()) {
                        this.$el.fadeOut();
                    } else if (!this.model || !this.model.get('alt')) {
                        this.$el.fadeIn();
                    }
                },

                // Handle the button click: Perform AJAX request
                click: function(e) {
                    e.preventDefault();
                    var attachmentId = this.model.id;

                    if (!attachmentId || !this.$altInput || this.$el.is('.disabled')) {
                        console.error('King Addons: Missing data for modal generation.', { id: attachmentId, input: this.$altInput });
                        return;
                    }

                    // Find the status span, which is now external
                    this.$statusSpan = this.$el.next('.king-addons-modal-status');
                    if (!this.$statusSpan.length) {
                        console.error("King Addons: Could not find external status span next to button.");
                        return;
                    }

                    // Show loading state
                    this.$el.addClass('disabled').prop('disabled', true);
                    this.$spinner.css('display', 'inline-block').addClass('is-active');
                    this.$buttonTextSpan.css('display', 'none');
                    this.$statusSpan.text('').css('color', '');

                    var self = this;

                    $.ajax({
                        url: kingAddonsMediaAltText.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'king_addons_generate_single_alt',
                            nonce: kingAddonsMediaAltText.nonce,
                            attachment_id: attachmentId
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success && response.data.alt_text) {
                                // Update the input value
                                self.$altInput.val(response.data.alt_text).trigger('change');
                                // Update the model as well
                                self.model.set('alt', response.data.alt_text);

                                if (self.$statusSpan) self.$statusSpan.text('Generated!').css('color', 'green');
                                setTimeout(function() {
                                    if (self.$statusSpan) self.$statusSpan.text('');
                                }, 3000);
                            } else {
                                var errorMessage = response.data && response.data.message ? response.data.message : kingAddonsMediaAltText.error_text;
                                if (self.$statusSpan) self.$statusSpan.text(escapeHtml(errorMessage)).css('color', 'red');
                                if (self.$el) self.$el.removeClass('disabled').prop('disabled', false);
                                if (self.$spinner) self.$spinner.css('display', 'none').removeClass('is-active');
                                if (self.$buttonTextSpan) self.$buttonTextSpan.css('display', 'inline');
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            console.error('King Addons Modal Generate AJAX Error:', textStatus, errorThrown, jqXHR.responseText);
                            var specificMessage = kingAddonsMediaAltText.error_text;
                            if (jqXHR.responseText) {
                                try {
                                    var errorResponse = JSON.parse(jqXHR.responseText);
                                    if (errorResponse && errorResponse.data && errorResponse.data.message) {
                                        specificMessage = escapeHtml(errorResponse.data.message);
                                    }
                                } catch (e) { /* Ignore */ }
                            }
                            if (self.$statusSpan) self.$statusSpan.text(specificMessage + ' (' + textStatus + ')').css('color', 'red');
                            if (self.$el) self.$el.removeClass('disabled').prop('disabled', false);
                            if (self.$spinner) self.$spinner.css('display', 'none').removeClass('is-active');
                            if (self.$buttonTextSpan) self.$buttonTextSpan.css('display', 'inline');
                        },
                        complete: function() {
                            if (self.$spinner && self.$spinner.is(':visible')) {
                                self.$spinner.css('display', 'none').removeClass('is-active');
                            }
                        }
                    });
                }
            });

            // Define our custom settings link view for when API key is missing
            const KingAddonsSettingsLink = wp.media.view.Button.extend({
                className: 'button button-primary button-big king-addons-settings-modal-link',
                tagName: 'a',

                initialize: function() {
                    wp.media.view.Button.prototype.initialize.apply(this, arguments);
                    this.model = this.options.attachmentModel;
                    this.$altInput = this.options.$altInput;
                    this.bindHandlers();
                },

                render: function() {
                    wp.media.view.Button.prototype.render.apply(this, arguments);
                    this.$el.text('Generate with AI').attr({
                        'href': kingAddonsMediaAltText.settings_url,
                        'target': '_blank',
                        'rel': 'noopener noreferrer'
                    });
                    return this;
                },

                bindHandlers: function() {
                    // Check if alt text exists initially and hide button if it does
                    if (this.model && this.model.get('alt')) {
                        this.$el.hide();
                    }
                    // Listen for changes on the alt text model property
                    if (this.model) {
                        this.listenTo(this.model, 'change:alt', this.toggleVisibility);
                    }
                    // Also listen to changes directly on the input field
                    if (this.$altInput) {
                        this.$altInput.on('input change', _.debounce(this.toggleVisibilityBasedOnInput.bind(this), 300));
                    }
                },

                // Hide button if alt text is added
                toggleVisibility: function() {
                    if (this.model && this.model.get('alt')) {
                        this.$el.fadeOut();
                    } else {
                        this.$el.fadeIn();
                    }
                },

                toggleVisibilityBasedOnInput: function() {
                    if (this.$altInput && this.$altInput.val()) {
                        this.$el.fadeOut();
                    } else if (!this.model || !this.model.get('alt')) {
                        this.$el.fadeIn();
                    }
                },

                // Handle clicks (default link behavior)
                click: function(e) {
                    // Let the default link behavior handle this
                    // The link will open the settings page in a new tab
                }
            });

            // Hook into the media frame rendering
            wp.media.events.on('editor:render', function(editor) {
                attachButtonToSidebar(editor);
            });

            // Need to handle different ways the sidebar might render/update
            var originalSidebar = wp.media.view.Attachment.Details.TwoColumn;
            if (originalSidebar) {
                wp.media.view.Attachment.Details.TwoColumn = originalSidebar.extend({
                    render: function() {
                        originalSidebar.prototype.render.apply(this, arguments);
                        attachButtonLogic(this);
                    }
                });
            }
            var originalDetails = wp.media.view.Attachment.Details;
            if (originalDetails) {
                wp.media.view.Attachment.Details = originalDetails.extend({
                    render: function() {
                        originalDetails.prototype.render.apply(this, arguments);
                        attachButtonLogic(this);
                    }
                });
            }

            // Function to find the alt text field and inject the button
            function attachButtonLogic(viewInstance) {
                var attachmentModel = viewInstance.model;
                if (!attachmentModel) return;

                var $altSetting = viewInstance.$el.find('.setting[data-setting="alt"]');
                if (!$altSetting.length) return;

                var $altDesc = viewInstance.$el.find('#alt-text-description');
                if (!$altDesc.length) return;

                var $altTextarea = $altSetting.find('textarea');
                if (!$altTextarea.length) return;

                // Prevent duplicate injection for this specific view instance
                if ($altSetting.data('king-addons-button-added')) return;
                $altSetting.data('king-addons-button-added', true);

                // Create status span separately (only needed for generation)
                var $statusSpan = $('<span class="king-addons-modal-status" style="margin-left: 10px; vertical-align: baseline;"></span>');

                // Choose which button to show based on API key availability
                var buttonInstance;
                if (kingAddonsMediaAltText.has_api_key) {
                    // Instantiate and render the generation button
                    buttonInstance = new KingAddonsGenerateButton({
                        controller: viewInstance.controller,
                        model: viewInstance.model,
                        attachmentModel: attachmentModel,
                        $altInput: $altTextarea
                    }).render();
                } else {
                    // Instantiate and render the settings link
                    buttonInstance = new KingAddonsSettingsLink({
                        controller: viewInstance.controller,
                        model: viewInstance.model,
                        attachmentModel: attachmentModel,
                        $altInput: $altTextarea
                    }).render();
                }

                // Wrap button and status span in a container div with a unique class
                const $containerDiv = $('<div class="king-addons-modal-alt-gen-container"></div>');
                $containerDiv.append(buttonInstance.$el);
                
                // Only add status span for generation button
                if (kingAddonsMediaAltText.has_api_key) {
                    $containerDiv.append($statusSpan);
                }
                
                // Insert the container after the textarea
                $altDesc.prepend($containerDiv);
            }

            // Helper for editor:render event
            function attachButtonToSidebar(sidebarView) {
                if (sidebarView && sidebarView.details) {
                    attachButtonLogic(sidebarView.details);
                }
            }

        } else {
            console.error("King Addons: wp.media object not found.");
        }
        // --- End Media Modal Button Injection --- //

    });

    // Basic HTML escaping function
    function escapeHtml(unsafe) {
        if (!unsafe) return '';
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

})(jQuery, _, wp);
