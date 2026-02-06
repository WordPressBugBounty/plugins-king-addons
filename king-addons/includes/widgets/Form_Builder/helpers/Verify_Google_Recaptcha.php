<?php

namespace King_Addons;

if (!defined('ABSPATH')) {
    exit;
}

class Verify_Google_Recaptcha
{
    public function __construct()
    {
        add_action('wp_ajax_king_addons_verify_recaptcha', [$this, 'king_addons_verify_recaptcha']);
        add_action('wp_ajax_nopriv_king_addons_verify_recaptcha', [$this, 'king_addons_verify_recaptcha']);
    }

    public function king_addons_verify_recaptcha()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            wp_send_json_error(['message' => 'Invalid request method.']);
        }

        if (!isset($_POST['nonce']) || !check_ajax_referer('king-addons-js', 'nonce', false)) {
            wp_send_json_error(['message' => 'Invalid nonce.']);
        }

        $recaptcha_response = sanitize_text_field($_POST['g-recaptcha-response'] ?? '');
        if (empty($recaptcha_response)) {
            wp_send_json_error(['message' => 'Missing reCAPTCHA response.']);
        }

        $is_valid_recaptcha = $this->check_recaptcha($recaptcha_response);

        if ($is_valid_recaptcha[0] && $is_valid_recaptcha[1] >= get_option('king_addons_recaptcha_v3_score_threshold')) {
            wp_send_json_success(array(
                'message' => 'Recaptcha Success',
                'score' => $is_valid_recaptcha[1]
            ));
        } else {
            wp_send_json_error(array(
                'message' => 'Recaptcha Error',
                'score' => $is_valid_recaptcha[1],
                'results' => [
                    $is_valid_recaptcha[0],
                    $is_valid_recaptcha[1] >= get_option('king_addons_recaptcha_v3_score_threshold')
                ]
            ));
        }
    }

    public function check_recaptcha($recaptcha_response)
    {
        $secret_key = get_option('king_addons_recaptcha_v3_secret_key');
        $remote_ip = $_SERVER['REMOTE_ADDR'];

        $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', array(
            'body' => array(
                'secret' => $secret_key,
                'response' => $recaptcha_response,
                'remoteip' => $remote_ip
            )
        ));

        if (is_wp_error($response)) {
            return [false, 0];
        }

        $decoded_response = json_decode(wp_remote_retrieve_body($response), true);

        if (!is_array($decoded_response)) {
            return [false, 0];
        }

        $score = isset($decoded_response['score']) ? (float) $decoded_response['score'] : 0.0;

        if (!empty($decoded_response['success'])) {
            return [true, $score];
        } else {
            return [false, $score];
        }
    }
}

new Verify_Google_Recaptcha();