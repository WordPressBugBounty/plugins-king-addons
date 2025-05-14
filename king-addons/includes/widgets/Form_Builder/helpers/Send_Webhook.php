<?php

namespace King_Addons;

if (!defined('ABSPATH')) {
    exit;
}

class Send_Webhook
{

    public function __construct()
    {
        add_action('wp_ajax_king_addons_form_builder_webhook', [$this, 'send_webhook']);
        add_action('wp_ajax_nopriv_king_addons_form_builder_webhook', [$this, 'send_webhook']);
    }

    public function send_webhook()
    {
        $nonce = $_POST['nonce'];

        if (!wp_verify_nonce($nonce, 'king-addons-js')) {
            return;
        }

        $message_body = [];

        foreach ($_POST['form_content'] as $key => $value) {
            if (is_array($value[1])) {
                if (empty($value[2])) {
                    $message_body[trim($key)] = implode("\n", $value[1]);
                } else {
                    $message_body[trim($value[2])] = implode("\n", $value[1]);
                }
            } else {
                if (empty($value2)) {
                    $message_body[trim($key)] = $value[1];
                } else {
                    $message_body[trim($value[2])] = $value[1];
                }
            }
        }

        $message_body['form_id'] = $_POST['king_addons_form_id'];
        $message_body['form_name'] = $_POST['form_name'];

        $args = [
            'body' => $message_body,
        ];

        $response = wp_remote_post(trim(get_option('king_addons_webhook_url_' . $_POST['king_addons_form_id'])), $args);

        if (200 !== (int)wp_remote_retrieve_response_code($response)) {
            wp_send_json_error(array(
                'action' => 'king_addons_form_builder_webhook',
                'message' => esc_html__('Webhook error', 'king-addons'),
                'status' => 'error',
                'details' => json_encode($message_body)
            ));
        } else {
            wp_send_json_success(array(
                'action' => 'king_addons_form_builder_webhook',
                'message' => esc_html__('Webhook success', 'king-addons'),
                'status' => 'success',
                'details' => json_encode($message_body)
            ));
        }
    }
}

new Send_Webhook();