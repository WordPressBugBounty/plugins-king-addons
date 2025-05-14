<?php

namespace King_Addons;

use King_Addons\Core;

if (!defined('ABSPATH')) {
    exit;
}

class Send_Email
{

    public function __construct()
    {
        add_action('wp_ajax_king_addons_form_builder_email', [$this, 'send_email']);
        add_action('wp_ajax_nopriv_king_addons_form_builder_email', [$this, 'send_email']);
    }

    public function send_email()
    {

        $nonce = $_POST['nonce'];

        if (!wp_verify_nonce($nonce, 'king-addons-js')) {
            return;
        }

        $message_body = [];

        foreach ($_POST['form_content'] as $field) {
            if ($field[0] === 'email') {
                if (!is_email($field[1])) {


                    wp_send_json_error(array(
                        'action' => 'king_addons_form_builder_email',
                        'message' => esc_html__('Email provided is invalid', 'king-addons'),
                        'status' => 'error'
                    ));
                }
            }
        }


        $content_type = get_option('king_addons_email_content_type_' . $_POST['king_addons_form_id']);

        $line_break = 'html' === $content_type ? '<br>' : "\n";

        $email_fields = trim(get_option('king_addons_email_fields_' . $_POST['king_addons_form_id']));

        if ($email_fields === '[all-fields]' || str_contains($email_fields, '[all-fields]')) {


            $replace_shortcode_with_value = function ($matches) {
                $field_id = $matches[1];
                foreach ($_POST['form_content'] as $key => $value) {
                    $key_parts = explode('-', $key);
                    $last_part = end($key_parts);
                    if ($last_part === $field_id) {
                        return is_array($value[1]) ? implode("\n", $value[1]) : $value[1];
                    }
                }
                return '';
            };


            $all_fields_content = [];

            foreach ($_POST['form_content'] as $key => $value) {
                $all_fields_content[] = is_array($value[1]) ? trim($value[2]) . ': ' . implode("\n", $value[1]) : trim($value[2]) . ': ' . $value[1];
            }
            $all_fields_content = implode("\n", $all_fields_content);


            $processed_message = str_replace('[all-fields]', $all_fields_content, $email_fields);

            $processed_message = preg_replace_callback(
                '/\[id="([^"]+)"\]/',
                $replace_shortcode_with_value,
                $processed_message
            );
        } else {


            $replace_shortcode_with_value = function ($matches) {
                $field_id = $matches[1];
                foreach ($_POST['form_content'] as $key => $value) {
                    $key_parts = explode('-', $key);
                    $last_part = end($key_parts);
                    if ($last_part === $field_id) {
                        return is_array($value[1]) ? trim($value[2]) . ': ' . implode("\n", $value[1]) : trim($value[2]) . ': ' . $value[1];
                    }
                }
                return '';
            };


            $processed_message = preg_replace_callback(
                '/\[id="([^"]+)"\]/',
                $replace_shortcode_with_value,
                $email_fields
            );
        }

        $meta_keys = get_option('king_addons_meta_keys_' . $_POST['king_addons_form_id']);
        $meta_fields = [];

        foreach ($meta_keys as $metadata_type) {
            switch ($metadata_type) {
                case 'date':
                    $meta_fields['date'] = [
                        'title' => esc_html__('Date', 'king-addons'),
                        'value' => date_i18n(get_option('date_format')),
                    ];
                    break;

                case 'time':
                    $meta_fields['time'] = [
                        'title' => esc_html__('Time', 'king-addons'),
                        'value' => date_i18n(get_option('time_format')),
                    ];
                    break;

                case 'page_url':
                    $meta_fields['page_url'] = [
                        'title' => esc_html__('Page URL', 'king-addons'),

                        'value' => get_option('king_addons_referrer_' . $_POST['king_addons_form_id']) ? get_option('king_addons_referrer_' . $_POST['king_addons_form_id']) : '',
                    ];
                    break;

                case 'page_title':
                    $meta_fields['page_title'] = [
                        'title' => esc_html__('Page Title', 'king-addons'),

                        'value' => get_option('king_addons_referrer_title_' . $_POST['king_addons_form_id']) ? get_option('king_addons_referrer_title_' . $_POST['king_addons_form_id']) : '',
                    ];
                    break;

                case 'user_agent':
                    $meta_fields['user_agent'] = [
                        'title' => esc_html__('User Agent', 'king-addons'),
                        'value' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_textarea_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '',
                    ];
                    break;

                case 'remote_ip':
                    $meta_fields['remote_ip'] = [
                        'title' => esc_html__('Remote IP', 'king-addons'),
                        'value' => Core::getClientIP(),
                    ];
                    break;

                case 'credit':
                    $meta_fields['credit'] = [
                        'title' => esc_html__('Powered by', 'king-addons'),
                        'value' => esc_html__('King Addons', 'king-addons'),
                    ];
                    break;
            }
        }

        $email_meta = [];

        foreach ($meta_fields as $key => $value) {
            $email_meta[] = $value['title'] . ': ' . $value['value'];
        }

        $to = get_option('king_addons_email_to_' . $_POST['king_addons_form_id']);

        $to = preg_replace_callback(
            '/\[id="(\w+)"\]/',
            function ($matches) {
                return $this->get_field_value($matches[1]);
            },
            $to
        );

        $subject = get_option('king_addons_email_subject_' . $_POST['king_addons_form_id']);

        $subject = preg_replace_callback(
            '/\[id="(\w+)"\]/',
            function ($matches) {
                return $this->get_field_value($matches[1]);
            },
            $subject
        );

        if ($processed_message) {
            $message_body[] = $processed_message;
        }


        if ($content_type === 'html') {

            foreach ($message_body as &$item) {
                $item = nl2br($item);
            }
            unset($item);
        }

        $body = implode($line_break, $message_body) . $line_break . '-----' . $line_break . implode($line_break, $email_meta);

        $cc_header = '';
        if (!empty(get_option('king_addons_cc_header_' . $_POST['king_addons_form_id']))) {
            $cc_header = 'Cc: ' . get_option('king_addons_cc_header_' . $_POST['king_addons_form_id']);

            $cc_header = preg_replace_callback(
                '/\[id="(\w+)"\]/',
                function ($matches) {
                    return $this->get_field_value($matches[1]);
                },
                $cc_header
            );
        }

        $bcc_header = '';
        if (!empty(get_option('king_addons_bcc_header_' . $_POST['king_addons_form_id']))) {
            $bcc_header = 'Bcc: ' . get_option('king_addons_bcc_header_' . $_POST['king_addons_form_id']);

            $bcc_header = preg_replace_callback(
                '/\[id="([^\"]+)"\]/',
                function ($matches) {
                    return $this->get_field_value($matches[1]);
                },
                $bcc_header
            );
        }

        // Initialize reply-to and email-from variables to avoid undefined variable warnings
        $reply_to_address = '';
        $email_from_name  = '';
        $email_from_mail  = '';
        $reply_to         = '';

        if (!empty(get_option('king_addons_reply_to_' . $_POST['king_addons_form_id'])) && !empty(get_option('king_addons_email_from_name_' . $_POST['king_addons_form_id'])) && !empty(get_option('king_addons_email_from_' . $_POST['king_addons_form_id']))) {

            preg_match_all('/id="([^"]+)"/', get_option('king_addons_reply_to_' . $_POST['king_addons_form_id']), $matche);
            $reply_to_field_id = $matche[1];

            preg_match_all('/id="([^"]+)"/', get_option('king_addons_email_from_name_' . $_POST['king_addons_form_id']), $matche);
            $email_from_name_field_id = $matche[1];

            preg_match_all('/id="([^"]+)"/', get_option('king_addons_email_from_' . $_POST['king_addons_form_id']), $matche);
            $email_from_field_id = $matche[1];

            foreach ($_POST['form_content'] as $key => $value) {
                $key_parts = explode('-', $key);
                $last_part = end($key_parts);

                if (in_array($last_part, $reply_to_field_id)) {
                    $reply_to_address = $value[1];
                }

                if (in_array($last_part, $email_from_name_field_id)) {
                    $email_from_name = $value[1];
                }

                if (in_array($last_part, $email_from_field_id)) {
                    $email_from_mail = $value[1];
                }
            }

            if (!$reply_to_address) {
                $reply_to_address = get_option('king_addons_reply_to_' . $_POST['king_addons_form_id']);
            }

            if (!$email_from_name) {
                $email_from_name = get_option('king_addons_email_from_name_' . $_POST['king_addons_form_id']);
            }

            if (!$email_from_mail) {
                $email_from_mail = get_option('king_addons_email_from_' . $_POST['king_addons_form_id']);
            }

            $reply_to = 'Reply-To: ' . $reply_to_address;
        }

        $email_from = sprintf('From: %s <%s>' . "\r\n", $email_from_name, $email_from_mail);

        $headers = array('Content-Type: text/' . $content_type . '; charset=UTF-8', $email_from, $cc_header, $bcc_header, $reply_to);


        $sent = wp_mail($to, $subject, $body, $headers);

        if ($sent) {
            wp_send_json_success(array(
                'action' => 'king_addons_form_builder_email',
                'message' => esc_html__('Message sent successfully', 'king-addons'),
                'status' => 'success',
                'details' => json_encode($message_body)
            ));
        } else {
            wp_send_json_error(array(
                'action' => 'king_addons_form_builder_email',
                'message' => esc_html__('Message could not be sent', 'king-addons'),
                'status' => 'error',
                'details' => json_encode($message_body)
            ));
        }
    }

    public function get_field_value($field_id)
    {
        foreach ($_POST['form_content'] as $key => $field) {
            $key_parts = explode('-', $key);
            $last_part = end($key_parts);

            if ($last_part === $field_id) {
                return $field[1];
            }
        }
        return '';
    }
}

new Send_Email();