(function($) {
    'use strict';

    $(function() {
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
    });
})(jQuery); 