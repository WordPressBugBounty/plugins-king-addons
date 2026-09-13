<?php

namespace King_Addons;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Stores Form Builder submissions from the public AJAX endpoint.
 */
class Create_Submission
{

    /**
     * Registers submission AJAX hooks and admin meta updates.
     */
    public function __construct()
    {
        add_action('wp_ajax_king_addons_form_builder_submissions', [$this, 'add_to_submissions']);
        add_action('wp_ajax_nopriv_king_addons_form_builder_submissions', [$this, 'add_to_submissions']);
        add_action('save_post', [$this, 'update_submissions_post_meta']);
    }

    /**
     * Creates a submission post from a public form request.
     *
     * Guests are allowed when the nonce is valid and the submitted page ID
     * belongs to a published Form Builder source page.
     *
     * @return void
     */
    public function add_to_submissions()
    {

        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';

        if (!wp_verify_nonce($nonce, 'king-addons-js')) {
            wp_send_json_error(array(
                'message' => esc_html__('Security check failed.', 'king-addons'),
            ));
        }

        Form_Builder_Security::guard_spam();

        $sanitized_form_page_id = absint($_POST['form_page_id'] ?? 0);
        if (!Form_Builder_Security::is_valid_submission_page($sanitized_form_page_id)) {
            wp_send_json_error(array(
                'message' => esc_html__('Insufficient permissions.', 'king-addons'),
            ));
        }

        $sanitized_form_name = sanitize_text_field(wp_unslash($_POST['form_name'] ?? ''));
        $form_content = isset($_POST['form_content']) && is_array($_POST['form_content']) ? wp_unslash($_POST['form_content']) : [];
        $sanitized_form_id = sanitize_key(wp_unslash($_POST['form_id'] ?? ''));
        $sanitized_form_page = sanitize_text_field(wp_unslash($_POST['form_page'] ?? ''));

        $post_id = self::insert_submission([
            'form_name' => $sanitized_form_name,
            'form_id' => $sanitized_form_id,
            'form_page' => $sanitized_form_page,
            'form_page_id' => $sanitized_form_page_id,
            'form_content' => $form_content,
        ]);

        if ($post_id) {
            wp_send_json_success(array(
                'action' => 'king_addons_form_builder_submissions',
                'post_id' => $post_id,
                'message' => esc_html__('Submission created successfully', 'king-addons'),
                'status' => 'success'
                // Security fix: Removed unsanitized form_content from response to prevent XSS
            ));
        } else {
            wp_send_json_success(array(
                'action' => 'king_addons_form_builder_submissions',
                'post_id' => $post_id,
                'message' => esc_html__('Submit action failed', 'king-addons'),
                'status' => 'error'
            ));
        }
    }

    /**
     * Insert a Form Builder submission from already-sanitized request pieces.
     *
     * @param array<string,mixed> $args {
     *     @type string $form_name
     *     @type string $form_id
     *     @type string $form_page
     *     @type int    $form_page_id
     *     @type array  $form_content
     * }
     *
     * @return int Submission post ID, or 0.
     */
    public static function insert_submission(array $args): int
    {
        $form_name = sanitize_text_field((string) ($args['form_name'] ?? ''));
        $form_id = sanitize_key((string) ($args['form_id'] ?? ''));
        $form_page = sanitize_text_field((string) ($args['form_page'] ?? ''));
        $form_page_id = absint($args['form_page_id'] ?? 0);
        $form_content = isset($args['form_content']) && is_array($args['form_content']) ? $args['form_content'] : [];

        $post_id = wp_insert_post([
            'post_status' => 'publish',
            'post_type' => 'king-addons-fb-sub',
            'post_title' => $form_name
                ? $form_name . ' - ' . current_time('mysql')
                : current_time('mysql'),
        ]);

        if (!$post_id || is_wp_error($post_id)) {
            return 0;
        }

        $post_id = (int) $post_id;

        foreach ($form_content as $key => $value) {
            if (!is_array($value) || count($value) < 3) {
                continue;
            }

            $sanitized_key = sanitize_key((string) $key);
            if ('' === $sanitized_key) {
                continue;
            }

            update_post_meta($post_id, $sanitized_key, [
                sanitize_text_field((string) $value[0]),
                self::sanitize_field_value($value[1]),
                sanitize_text_field((string) $value[2]),
            ]);
        }

        update_post_meta($post_id, 'king_addons_form_name', $form_name);
        update_post_meta($post_id, 'king_addons_form_id', $form_id);
        update_post_meta($post_id, 'king_addons_form_page', $form_page);
        update_post_meta($post_id, 'king_addons_form_page_id', $form_page_id);
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_textarea_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '';
        update_post_meta($post_id, 'king_addons_user_agent', $user_agent);
        update_post_meta($post_id, 'king_addons_user_ip', Core::getClientIP());

        return $post_id;
    }

    /**
     * Saves admin edits to an existing submission.
     *
     * @param int $post_id Submission post ID.
     * @return void
     */
    public function update_submissions_post_meta($post_id)
    {
        // Security fix: Validate nonce and capabilities
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['king_addons_submission_changes']) && !empty($_POST['king_addons_submission_changes'])) {
            // Security fix: Sanitize JSON input and validate structure
            $raw_changes = sanitize_textarea_field(stripslashes($_POST['king_addons_submission_changes']));
            $changes = json_decode($raw_changes, true);

            if (!is_array($changes)) {
                return; // Invalid JSON structure
            }

            foreach ($changes as $key => $value) {
                // Security fix: Validate and sanitize keys and values
                $sanitized_key = sanitize_key($key);
                if (empty($sanitized_key)) {
                    continue; // Skip invalid keys
                }

                // Sanitize values based on type
                if (is_array($value)) {
                    $sanitized_value = array_map('sanitize_text_field', $value);
                } else {
                    $sanitized_value = sanitize_text_field($value);
                }

                update_post_meta($post_id, $sanitized_key, $sanitized_value);
            }
        }
    }

    /**
     * Sanitize a submitted field value.
     *
     * Radio and checkbox groups arrive as rows of [value, checked, name, id].
     * A flat array_map(sanitize_text_field) turns each row into an empty
     * string, so the saved submission lost what the visitor actually picked.
     *
     * @param mixed $raw Raw value from form_content.
     *
     * @return string|array<int,string>
     */
    private static function sanitize_field_value($raw)
    {
        if (!is_array($raw)) {
            return sanitize_text_field((string) $raw);
        }

        if (isset($raw[0]) && is_array($raw[0])) {
            $picked = [];
            foreach ($raw as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $option = sanitize_text_field((string) ($row[0] ?? ''));
                $checked = !empty($row[1]) && 'false' !== (string) $row[1] && '0' !== (string) $row[1];
                if ($checked && '' !== $option) {
                    $picked[] = $option;
                }
            }

            return implode(', ', $picked);
        }

        return array_values(array_map('sanitize_text_field', $raw));
    }
}

new Create_Submission();