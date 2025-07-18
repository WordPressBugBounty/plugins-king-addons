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
        
        // Initialize existing functionality
        var $refreshButton = $('#king-addons-ai-refresh-models-button');
        var $spinner = $('#king-addons-ai-refresh-models-spinner');
        var $statusSpan = $('#king-addons-ai-refresh-models-status');
        var $modelSelect = $('select[name="king_addons_ai_options[openai_model]"]');

        $refreshButton.on('click', function() {
            if ($refreshButton.prop('disabled')) {
                return;
            }

            // Disable button and show spinner
            $refreshButton.prop('disabled', true);
            $spinner.css({ visibility: 'visible', display: 'inline-block' }).addClass('is-active');
            $statusSpan.text(KingAddonsAiSettings.refreshing_text).css('color', '');
            $modelSelect.prop('disabled', true);

            // AJAX request to refresh models
            $.ajax({
                url: KingAddonsAiSettings.ajax_url,
                type: 'POST',
                data: {
                    action: 'king_addons_ai_refresh_models',
                    nonce: KingAddonsAiSettings.nonce
                },
                dataType: 'json'
            }).done(function(response) {
                if (response.success && response.data.models) {
                    var currentValue = $modelSelect.val();
                    $modelSelect.empty();

                    $.each(response.data.models, function(modelId, modelLabel) {
                        var $option = $('<option></option>').val(modelId).text(modelLabel);
                        if (modelId === currentValue) {
                            $option.prop('selected', true);
                        }
                        $modelSelect.append($option);
                    });

                    $statusSpan.text(KingAddonsAiSettings.refreshed_text).css('color', 'green');
                    setTimeout(function() {
                        $statusSpan.text('');
                    }, 3000);
                } else {
                    var message = response.data && response.data.message ? response.data.message : KingAddonsAiSettings.error_text;
                    $statusSpan.text(message).css('color', 'red');
                }
            }).fail(function(jqXHR) {
                var message = KingAddonsAiSettings.error_text;
                try {
                    var errorResponse = JSON.parse(jqXHR.responseText);
                    if (errorResponse.data && errorResponse.data.message) {
                        message = errorResponse.data.message;
                    }
                } catch (e) {
                    // ignore JSON parse errors
                }
                $statusSpan.text(message).css('color', 'red');
            }).always(function() {
                // Re-enable button and hide spinner
                $spinner.css({ visibility: 'hidden', display: 'none' }).removeClass('is-active');
                $refreshButton.prop('disabled', false);
                $modelSelect.prop('disabled', false);
            });
        });
        
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