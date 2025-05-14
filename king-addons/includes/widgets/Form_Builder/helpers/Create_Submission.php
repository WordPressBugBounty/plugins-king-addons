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

        if (!wp_verify_nonce($nonce, 'king-addons-js')) {
            return;
        }

        $new = [
            'post_status' => 'publish',
            'post_type' => 'king-addons-fb-sub'
        ];

        $post_id = wp_insert_post($new);
        foreach ($_POST['form_content'] as $key => $value) {
            update_post_meta($post_id, $key, [$value[0], $value[1], $value[2]]);
        }

        $sanitized_form_name = sanitize_text_field($_POST['form_name']);
        $sanitized_form_id = sanitize_text_field($_POST['form_id']);
        $sanitized_form_page = sanitize_text_field($_POST['form_page']);
        $sanitized_form_page_id = sanitize_text_field($_POST['form_page_id']);

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
                'status' => 'success',
                'content' => $_POST['form_content']
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
        if (isset($_POST['king_addons_submission_changes']) && !empty($_POST['king_addons_submission_changes'])) {
            $changes = json_decode(stripslashes($_POST['king_addons_submission_changes']), true);

            foreach ($changes as $key => $value) {
                update_post_meta($post_id, $key, $value);
            }
        }
    }
}

new Create_Submission();