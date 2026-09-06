(function($) {
    'use strict';

    $(function() {
        // Initialize modern animations and interactions
        initModernAnimations();
        
        // Auto-save feedback for AI settings form specifically
        $('.king-addons-settings-form').on('submit', function(e) {
            // Ensure the form is not prevented from submitting
            showSaveAnimation();
            
            // Explicitly allow form submission
            return true;
        });
        
        // Input focus animations
        $('input, select, textarea').on('focus', function() {
            $(this).closest('tr').addClass('focused');
        }).on('blur', function() {
            $(this).closest('tr').removeClass('focused');
        });
        
        // Checkbox animations
        $('input[type="checkbox"]').on('change', function() {
            if ($(this).is(':checked')) {
                $(this).addClass('checked-animation');
                setTimeout(() => $(this).removeClass('checked-animation'), 300);
            }
        });
        
        // ---- Provider switching -------------------------------------------
        var $provider = $('#king-addons-ai-provider');

        function activeProvider() {
            return $provider.length ? $provider.val() : 'openai';
        }

        // Show only the rows belonging to the selected provider. The rows are
        // hidden by a class rather than an inline style so this stays in step
        // with the server-rendered initial state.
        function applyProviderVisibility() {
            var provider = activeProvider();
            $('.ka-ai-provider-row').each(function() {
                var $row = $(this);
                $row.toggleClass('ka-ai-row-hidden', !$row.hasClass('ka-ai-provider-' + provider));
            });
        }

        function apiKeyField() {
            return activeProvider() === 'openrouter'
                ? $('#king-addons-openrouter-api-key')
                : $('input[name="king_addons_ai_options[openai_api_key]"]');
        }

        // ---- Shared helpers -----------------------------------------------
        var $refreshButton = $('#king-addons-ai-refresh-models-button');
        var $spinner = $('#king-addons-ai-refresh-models-spinner');
        var $statusSpan = $('#king-addons-ai-refresh-models-status');
        var $testButton = $('#king-addons-ai-test-connection-button');
        var $testSpinner = $('#king-addons-ai-test-connection-spinner');
        var $testStatus = $('#king-addons-ai-test-connection-status');

        function showSpinner($el, visible) {
            if (visible) {
                $el.css({ visibility: 'visible', display: 'inline-block' }).addClass('is-active');
            } else {
                $el.css({ visibility: 'hidden', display: 'none' }).removeClass('is-active');
            }
        }

        function errorMessage(jqXHR, fallback) {
            try {
                var parsed = JSON.parse(jqXHR.responseText);
                if (parsed.data && parsed.data.message) {
                    return parsed.data.message;
                }
            } catch (e) {
                // ignore JSON parse errors
            }
            return fallback;
        }

        // Rebuilds one model <select>, keeping the current selection when the
        // model is still offered, and grouping free models above paid ones.
        function fillModelSelect($select, models) {
            if (!$select.length || !Array.isArray(models)) {
                return;
            }

            var currentValue = $select.val();
            var free = [];
            var paid = [];

            models.forEach(function(model) {
                (model.free ? free : paid).push(model);
            });

            function optionsFor(list, $target) {
                list.forEach(function(model) {
                    var $option = $('<option></option>').val(model.id).text(model.label);
                    if (model.id === currentValue) {
                        $option.prop('selected', true);
                    }
                    $target.append($option);
                });
            }

            $select.empty().prop('disabled', false);

            // Keep a saved-but-no-longer-offered model selectable.
            var stillOffered = models.some(function(model) {
                return model.id === currentValue;
            });
            if (currentValue && !stillOffered) {
                $select.append($('<option></option>').val(currentValue).text(currentValue).prop('selected', true));
            }

            if (free.length && paid.length) {
                var $freeGroup = $('<optgroup></optgroup>').attr('label', KingAddonsAiSettings.free_models_label);
                var $paidGroup = $('<optgroup></optgroup>').attr('label', KingAddonsAiSettings.paid_models_label);
                optionsFor(free, $freeGroup);
                optionsFor(paid, $paidGroup);
                $select.append($freeGroup, $paidGroup);
            } else {
                optionsFor(models, $select);
            }
        }

        // ---- Refresh model list -------------------------------------------
        $refreshButton.on('click', function() {
            if ($refreshButton.prop('disabled')) {
                return;
            }

            var provider = activeProvider();
            var $selects = $('.ka-ai-provider-' + provider + ' .ka-ai-model-select');
            if (!$selects.length) {
                $selects = $('.ka-ai-model-select');
            }

            $refreshButton.prop('disabled', true);
            showSpinner($spinner, true);
            $statusSpan.text(KingAddonsAiSettings.refreshing_text).css('color', '');
            $selects.prop('disabled', true);

            $.ajax({
                url: KingAddonsAiSettings.ajax_url,
                type: 'POST',
                data: {
                    action: 'king_addons_ai_refresh_models',
                    nonce: KingAddonsAiSettings.nonce,
                    provider: provider,
                    api_key: apiKeyField().val() || ''
                },
                dataType: 'json'
            }).done(function(response) {
                if (response.success && response.data && response.data.models) {
                    $selects.each(function() {
                        var $select = $(this);
                        var type = $select.data('ka-model-type') || 'text';
                        fillModelSelect($select, response.data.models[type]);
                    });

                    $statusSpan.text(KingAddonsAiSettings.refreshed_text).css('color', 'green');
                    setTimeout(function() {
                        $statusSpan.text('');
                    }, 3000);
                } else {
                    var message = (response.data && response.data.message) ? response.data.message : KingAddonsAiSettings.error_text;
                    $statusSpan.text(message).css('color', 'red');
                }
            }).fail(function(jqXHR) {
                $statusSpan.text(errorMessage(jqXHR, KingAddonsAiSettings.error_text)).css('color', 'red');
            }).always(function() {
                showSpinner($spinner, false);
                $refreshButton.prop('disabled', false);
                $selects.prop('disabled', false);
            });
        });

        // ---- Test connection ----------------------------------------------
        $testButton.on('click', function() {
            if ($testButton.prop('disabled')) {
                return;
            }

            $testButton.prop('disabled', true);
            showSpinner($testSpinner, true);
            $testStatus.text(KingAddonsAiSettings.testing_text).css('color', '');

            $.ajax({
                url: KingAddonsAiSettings.ajax_url,
                type: 'POST',
                data: {
                    action: 'king_addons_ai_test_connection',
                    nonce: KingAddonsAiSettings.nonce,
                    provider: activeProvider(),
                    api_key: apiKeyField().val() || ''
                },
                dataType: 'json'
            }).done(function(response) {
                if (response.success && response.data && response.data.message) {
                    $testStatus.text(response.data.message).css('color', 'green');
                } else {
                    var message = (response.data && response.data.message) ? response.data.message : KingAddonsAiSettings.test_failed_text;
                    $testStatus.text(message).css('color', 'red');
                }
            }).fail(function(jqXHR) {
                $testStatus.text(errorMessage(jqXHR, KingAddonsAiSettings.test_failed_text)).css('color', 'red');
            }).always(function() {
                showSpinner($testSpinner, false);
                $testButton.prop('disabled', false);
            });
        });

        $provider.on('change', function() {
            applyProviderVisibility();
            $statusSpan.text('');
            $testStatus.text('');
        });

        applyProviderVisibility();

        // Modern animation functions
        function initModernAnimations() {
            // Stagger animation for form sections
            $('.king-addons-ai-settings h2').each(function(index) {
                $(this).css({
                    'animation-delay': (index * 0.1) + 's',
                    'animation-fill-mode': 'forwards'
                }).addClass('slide-in-from-left');
            });
            
            // Stagger animation for form rows
            $('.king-addons-ai-settings .form-table tr').each(function(index) {
                $(this).css({
                    'animation-delay': (index * 0.05) + 's',
                    'animation-fill-mode': 'forwards'
                }).addClass('fade-in-up');
            });
            
            // Header icon rotation on hover
            $('.king-addons-settings-header-icon').on('mouseenter', function() {
                $(this).css('transform', 'rotate(10deg) scale(1.05)');
            }).on('mouseleave', function() {
                $(this).css('transform', 'rotate(0deg) scale(1)');
            });
        }
        
        function showSaveAnimation() {
            var $saveButton = $('.king-addons-save-button');
            
            // Only show animation if button exists and is not already disabled
            if ($saveButton.length && !$saveButton.prop('disabled')) {
                // Show saving state immediately
                $saveButton.text('Saving...')
                          .prop('disabled', true)
                          .css({
                              'opacity': '0.8',
                              'transform': 'scale(0.98)'
                          });
            }
            
            // No need for timeouts since page will reload on successful save
        }
    });
})(jQuery); 