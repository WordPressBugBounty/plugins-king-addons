/**
 * Protected Content Password Form Handler.
 *
 * Handles password form submission and cookie management.
 *
 * @package King_Addons
 */
(function ($) {
    'use strict';

    /**
     * Initialize password forms.
     */
    const initPasswordForms = () => {
        $(document).on('submit', '.king-addons-password-form', function (e) {
            e.preventDefault();

            const $form = $(this);
            const $input = $form.find('.king-addons-password-input');
            const $error = $form.find('.king-addons-password-error');
            const $submitText = $form.find('.king-addons-password-submit-text');
            const $submitLoading = $form.find('.king-addons-password-submit-loading');

            const password = $input.val();
            const elementId = $form.data('element-id');
            const postId = $form.data('post-id');
            const scope = $form.data('scope');
            const globalKey = $form.data('global-key');
            const days = $form.data('days') || 7;

            const strings = (window.kingAddonsProtectedContent && window.kingAddonsProtectedContent.strings)
                ? window.kingAddonsProtectedContent.strings
                : {};

            const enterPasswordText = strings.enterPassword || 'Please enter a password';
            const incorrectPasswordText = strings.incorrectPassword || 'Incorrect password';
            const errorOccurredText = strings.errorOccurred || 'An error occurred. Please try again.';

            if (!password) {
                $error.text(enterPasswordText).show();
                return;
            }

            // Show loading state.
            $submitText.hide();
            $submitLoading.show();
            $error.hide();
            $input.prop('disabled', true);

            $.ajax({
                url: kingAddonsProtectedContent.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'king_addons_verify_password',
                    nonce: kingAddonsProtectedContent.nonce,
                    password: password,
                    element_id: elementId,
                    post_id: postId,
                    scope: scope,
                    global_key: globalKey,
                    days: days
                },
                success: function (response) {
                    if (response.success) {
                        // Reload page to show unlocked content.
                        window.location.reload();
                    } else {
                        $error.text(response.data.message || incorrectPasswordText).show();
                        $input.prop('disabled', false).val('').focus();
                        $submitText.show();
                        $submitLoading.hide();
                    }
                },
                error: function () {
                    $error.text(errorOccurredText).show();
                    $input.prop('disabled', false);
                    $submitText.show();
                    $submitLoading.hide();
                }
            });
        });

        // Clear error on input.
        $(document).on('input', '.king-addons-password-input', function () {
            $(this).closest('.king-addons-password-form').find('.king-addons-password-error').hide();
        });
    };

    // Initialize when DOM is ready.
    $(document).ready(initPasswordForms);

}(jQuery));
