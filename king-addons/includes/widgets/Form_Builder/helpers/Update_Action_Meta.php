<?php

namespace King_Addons;

if (!defined('ABSPATH')) {
    exit;
}

class Update_Action_Meta
{

    public function __construct()
    {
        add_action('wp_ajax_king_addons_update_form_action_meta', [$this, 'king_addons_update_form_action_meta']);
        add_action('wp_ajax_nopriv_king_addons_update_form_action_meta', [$this, 'king_addons_update_form_action_meta']);
    }


    public function king_addons_update_form_action_meta()
    {
        $nonce = $_POST['nonce'];

        // Security fix: Generate nonce server-side instead of relying on client-provided nonce
        $server_nonce = wp_create_nonce('king-addons-js');
        if (!wp_verify_nonce($nonce, 'king-addons-js')) {
            return;
        }


        $custom_token = $_POST['custom_token'];

        if (is_user_logged_in()) {

            $user_id = get_current_user_id();
            $stored_token = get_transient('king_addons_custom_token_' . $user_id);
        } else {

            if (isset($_COOKIE['king_addons_guest_token'])) {
                $guest_id = sanitize_text_field($_COOKIE['king_addons_guest_token']);
                $stored_token = get_transient('king_addons_custom_guest_token_' . $guest_id);
            } else {
                wp_send_json_error('Invalid token.');
                return;
            }
        }

        if (!$stored_token || $custom_token !== $stored_token) {
            wp_send_json_error('Invalid token.');
            return;
        }

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $action_name = isset($_POST['action_name']) ? sanitize_text_field($_POST['action_name']) : '';
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        $message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';

        $meta_value = [
            'status' => $status,
            'message' => $message
        ];

        $actions_whitelist = [
            'king_addons_form_builder_email',
            'king_addons_form_builder_submissions',
            'king_addons_form_builder_mailchimp',
            'king_addons_form_builder_webhook'
        ];

        if ($post_id && $action_name && $status && in_array($action_name, $actions_whitelist)) {
            update_post_meta($post_id, '_action_' . $action_name, $meta_value);
            wp_send_json_success('Post meta updated successfully');
        } else {
            wp_send_json_error('Invalid data provided');
        }
    }
}

new Update_Action_Meta();