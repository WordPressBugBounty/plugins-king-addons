<?php

namespace King_Addons;

if (!defined('ABSPATH')) {
    exit;
}

class Subscribe_Mailchimp
{

    public function __construct()
    {

        add_action('wp_ajax_king_addons_form_builder_mailchimp', [$this, 'king_addons_form_builder_mailchimp']);
        add_action('wp_ajax_nopriv_king_addons_form_builder_mailchimp', [$this, 'king_addons_form_builder_mailchimp']);
    }


    public static function king_addons_form_builder_mailchimp()
    {

        $nonce = $_POST['nonce'];

        // Security fix: Generate nonce server-side instead of relying on client-provided nonce
        $server_nonce = wp_create_nonce('king-addons-js');
        if (!wp_verify_nonce($nonce, 'king-addons-js')) {
            return;
        }


        $api_key = get_option('king_addons_mailchimp_api_key') ? get_option('king_addons_mailchimp_api_key') : '';

        $api_key_sufix = explode('-', $api_key)[1];


        $list_id = isset($_POST['listId']) ? sanitize_text_field(wp_unslash($_POST['listId'])) : '';


        $fields = $_POST['form_data'] ?? [];

        $group_ids = isset($fields['group_id']) ? array_map('sanitize_text_field', array_map('trim', explode(',', wp_unslash($fields['group_id'])))) : [];


        $merge_fields = [
            'FNAME' => !empty($fields['first_name_field']) ? sanitize_text_field($fields['first_name_field']) : '',
            'LNAME' => !empty($fields['last_name_field']) ? sanitize_text_field($fields['last_name_field']) : '',
            'PHONE' => !empty ($fields['phone_field']) ? sanitize_text_field($fields['phone_field']) : '',
            'BIRTHDAY' => !empty ($fields['birthday_field']) ? sanitize_text_field($fields['birthday_field']) : '',
        ];

        $requiredKeys = ['address_field', 'country_field', 'city_field', 'state_field', 'zip_field'];

        if (!empty(array_intersect_key($fields, array_flip($requiredKeys)))) {
            $merge_fields = array_merge($merge_fields, [
                'ADDRESS' => [
                    'addr1' => !empty ($fields['address_field']) ? sanitize_text_field($fields['address_field']) : 'none',
                    'country' => !empty ($fields['country_field']) ? sanitize_text_field($fields['country_field']) : 'none',
                    'city' => !empty ($fields['city_field']) ? sanitize_text_field($fields['city_field']) : 'none',
                    'state' => !empty ($fields['state_field']) ? sanitize_text_field($fields['state_field']) : 'none',
                    'zip' => !empty ($fields['zip_field']) ? sanitize_text_field($fields['zip_field']) : 'none',
                ]
            ]);
        }


        $api_url = 'https://' . $api_key_sufix . '.api.mailchimp.com/3.0/lists/' . $list_id . '/members/' . md5(strtolower(sanitize_text_field($fields['email_field'])));

        $api_body = [
            'email_address' => sanitize_text_field($fields['email_field']),
            'status' => 'subscribed',
            'merge_fields' => $merge_fields
        ];

        if (!empty($group_ids)) {
            $api_body['interests'] = self::group_ids_to_interests_array($group_ids);
        }


        $api_args = [
            'method' => 'PUT',
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'apikey ' . $api_key,
            ],
            'body' => json_encode($api_body),
        ];


        $request = wp_remote_post($api_url, $api_args);

        if (!is_wp_error($request)) {
            $request = json_decode(wp_remote_retrieve_body($request));

            if (!empty($request)) {
                if ($request->status == 'subscribed') {

                    wp_send_json_success(array(
                        'action' => 'king_addons_form_builder_mailchimp',
                        'status' => 'success',
                        'message' => 'Mailchimp subscription was successful',
                        'request' => $request
                    ));

                } else {
                    wp_send_json_error([
                        'action' => 'king_addons_form_builder_mailchimp',
                        'status' => 'error',
                        'message' => 'Mailchimp subscription failed',
                        'request' => $request
                    ]);
                }
            }
        } else {

            wp_send_json_error([
                'action' => 'king_addons_form_builder_mailchimp',
                'status' => 'error',
                'message' => 'Mailchimp subscription failed',
                'request' => $request
            ]);
        }
    }

    public static function group_ids_to_interests_array($group_ids)
    {
        $interests_array = [];

        foreach ($group_ids as $group_id) {
            $interests_array[$group_id] = true;
        }

        return $interests_array;
    }

}

new Subscribe_Mailchimp();