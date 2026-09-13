<?php

namespace King_Addons;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shared Form Builder checks for public AJAX requests.
 */
class Form_Builder_Security
{
    /**
     * Checks whether a published document contains the Form Builder widget.
     *
     * @param mixed $page_id Page or post ID from the request.
     * @return bool True when the document is published and includes Form Builder.
     */
    public static function page_has_form_builder($page_id): bool
    {
        $page_id = absint($page_id);
        if ($page_id <= 0) {
            return false;
        }

        $post = get_post($page_id);
        if (!$post || 'publish' !== $post->post_status) {
            return false;
        }

        $elementor_data = get_post_meta($page_id, '_elementor_data', true);
        if (empty($elementor_data)) {
            return false;
        }

        if (!is_string($elementor_data)) {
            $elementor_data = wp_json_encode($elementor_data);
        }

        return false !== strpos($elementor_data, 'king-addons-form-builder');
    }

    /**
     * Stops a submission that failed the form's spam challenge.
     *
     * Every submit action is its own public AJAX endpoint, so each one calls
     * this: checking the challenge only in the browser would leave them open.
     *
     * @return void Sends a JSON error and exits when the check fails.
     */
    public static function guard_spam(): void
    {
        if (!class_exists('King_Addons\\Form_Spam_Protection')) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- the caller checked the nonce.
        $form_id = isset($_POST['king_addons_form_id'])
            ? sanitize_text_field(wp_unslash($_POST['king_addons_form_id']))
            : '';

        if ('' === $form_id) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- the caller checked the nonce.
        $result = Form_Spam_Protection::verify($form_id, $_POST);

        if (true === $result) {
            return;
        }

        wp_send_json_error([
            'message' => esc_html__('Your submission was rejected by the spam check.', 'king-addons'),
            'status' => 'error',
            'reason' => $result,
        ]);
    }

    /**
     * Validates a page ID for public form submissions.
     *
     * Accepts a published page that contains Form Builder. Also accepts a
     * published source page or template when the widget is injected by Theme
     * Builder, Header/Footer, or a popup and is therefore not stored on the
     * queried page.
     *
     * @param mixed $page_id Page or post ID from the request.
     * @return bool True when the page ID is safe to store with a submission.
     */
    public static function is_valid_submission_page($page_id): bool
    {
        if (self::page_has_form_builder($page_id)) {
            return true;
        }

        $page_id = absint($page_id);
        if ($page_id <= 0) {
            return false;
        }

        $post = get_post($page_id);
        if (!$post || 'publish' !== $post->post_status) {
            return false;
        }

        if (is_post_publicly_viewable($post)) {
            return true;
        }

        return in_array(
            $post->post_type,
            ['elementor_library', 'king-addons-el-hf', 'king_addons_popup'],
            true
        );
    }
}
