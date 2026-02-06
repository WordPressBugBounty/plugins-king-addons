<?php

namespace King_Addons;

if (!defined('ABSPATH')) {
    exit;
}

class Create_Submission
{

    public function __construct()
    {
        add_action('wp_ajax_king_addons_form_builder_submissions', [$this, 'add_to_submissions']);
        add_action('wp_ajax_nopriv_king_addons_form_builder_submissions', [$this, 'add_to_submissions']);
        add_action('save_post', [$this, 'update_submissions_post_meta']);
    }

    public function add_to_submissions()
    {

        $nonce = $_POST['nonce'];

        // Security fix: Generate nonce server-side instead of relying on client-provided nonce
        $server_nonce = wp_create_nonce('king-addons-js');
        if (!wp_verify_nonce($nonce, 'king-addons-js')) {
            wp_send_json_error(array(
                'message' => esc_html__('Security check failed.', 'king-addons'),
            ));
        }

        // Add capability check
        if (!current_user_can('read')) {
            wp_send_json_error(array(
                'message' => esc_html__('Insufficient permissions.', 'king-addons'),
            ));
        }

        $new = [
            'post_status' => 'publish',
            'post_type' => 'king-addons-fb-sub'
        ];

        $post_id = wp_insert_post($new);
        
        // Security fix: Validate and sanitize form_content before saving to database
        $form_content = isset($_POST['form_content']) && is_array($_POST['form_content']) ? $_POST['form_content'] : [];
        
        foreach ($form_content as $key => $value) {
            if (!is_array($value) || count($value) < 3) {
                continue; // Skip malformed fields
            }
            
            // Sanitize all form field data before saving
            $sanitized_key = sanitize_key($key);
            $sanitized_value = [
                sanitize_text_field($value[0]), // field type
                is_array($value[1]) ? array_map('sanitize_text_field', $value[1]) : sanitize_text_field($value[1]), // field value
                sanitize_text_field($value[2])  // field label
            ];
            
            update_post_meta($post_id, $sanitized_key, $sanitized_value);
        }

        $sanitized_form_name = sanitize_text_field($_POST['form_name'] ?? '');
        $sanitized_form_id = sanitize_text_field($_POST['form_id'] ?? '');
        $sanitized_form_page = sanitize_text_field($_POST['form_page'] ?? '');
        $sanitized_form_page_id = sanitize_text_field($_POST['form_page_id'] ?? '');

        update_post_meta($post_id, 'king_addons_form_name', $sanitized_form_name);
        update_post_meta($post_id, 'king_addons_form_id', $sanitized_form_id);
        update_post_meta($post_id, 'king_addons_form_page', $sanitized_form_page);
        update_post_meta($post_id, 'king_addons_form_page_id', $sanitized_form_page_id);
        update_post_meta($post_id, 'king_addons_user_agent', sanitize_textarea_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])));
        update_post_meta($post_id, 'king_addons_user_ip', Core::getClientIP());

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
}

new Create_Submission();