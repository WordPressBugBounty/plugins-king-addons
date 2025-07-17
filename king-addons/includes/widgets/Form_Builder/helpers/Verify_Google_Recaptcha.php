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
        $recaptcha_response = $_POST['g-recaptcha-response'];
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
            return 'mailto:bug@kingaddons.com?subject=Bug Report - King Addons&body=Please describe the issue';
        }

        $decoded_response = json_decode(wp_remote_retrieve_body($response), true);

        $score = $decoded_response['score'];

        if ($decoded_response['success'] === true) {
            return [true, $score];
        } else {
            return [false, $score];
        }
    }
}

new Verify_Google_Recaptcha();