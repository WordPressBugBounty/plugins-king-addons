<?php
/**
 * Form Builder integrations.
 *
 * One submit action that forwards a submission to whichever services the form
 * has switched on: Slack, Telegram, Brevo, ConvertKit, ActiveCampaign, HubSpot,
 * Klaviyo and Google Sheets.
 *
 * @package King_Addons
 */

namespace King_Addons;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Sends a submission on to third-party services.
 */
class Form_Integrations
{
    /**
     * Option prefix for the per-form configuration.
     */
    private const OPTION_PREFIX = 'king_addons_integrations_';

    /**
     * Services this action can talk to.
     *
     * @return array<string,string>
     */
    public static function services(): array
    {
        return [
            'slack' => esc_html__('Slack', 'king-addons'),
            'telegram' => esc_html__('Telegram', 'king-addons'),
            'brevo' => esc_html__('Brevo', 'king-addons'),
            'convertkit' => esc_html__('ConvertKit / Kit', 'king-addons'),
            'activecampaign' => esc_html__('ActiveCampaign', 'king-addons'),
            'hubspot' => esc_html__('HubSpot', 'king-addons'),
            'klaviyo' => esc_html__('Klaviyo', 'king-addons'),
            'google_sheets' => esc_html__('Google Sheets', 'king-addons'),
        ];
    }

    /**
     * Register the AJAX endpoint.
     *
     * @return void
     */
    public function __construct()
    {
        add_action('wp_ajax_king_addons_form_builder_integrations', [self::class, 'handle']);
        add_action('wp_ajax_nopriv_king_addons_form_builder_integrations', [self::class, 'handle']);
    }

    /**
     * Store a form's integration settings.
     *
     * The browser never sends these: like the email settings, they are written
     * when the form renders and read back when a submission arrives, so an
     * endpoint cannot be talked into posting somewhere else.
     *
     * @param string              $form_id  Form element id.
     * @param array<string,mixed> $settings Widget settings.
     *
     * @return void
     */
    public static function save_settings(string $form_id, array $settings): void
    {
        $enabled = isset($settings['integration_services']) && is_array($settings['integration_services'])
            ? array_values(array_intersect($settings['integration_services'], array_keys(self::services())))
            : [];

        $config = [
            'enabled' => $enabled,
            'slack_webhook' => (string) ($settings['slack_webhook_url'] ?? ''),
            'slack_title' => (string) ($settings['slack_title'] ?? ''),
            'telegram_token' => (string) ($settings['telegram_bot_token'] ?? ''),
            'telegram_chat' => (string) ($settings['telegram_chat_id'] ?? ''),
            'brevo_key' => (string) ($settings['brevo_api_key'] ?? ''),
            'brevo_list' => (string) ($settings['brevo_list_id'] ?? ''),
            'convertkit_key' => (string) ($settings['convertkit_api_key'] ?? ''),
            'convertkit_form' => (string) ($settings['convertkit_form_id'] ?? ''),
            'ac_url' => (string) ($settings['activecampaign_url'] ?? ''),
            'ac_key' => (string) ($settings['activecampaign_api_key'] ?? ''),
            'ac_list' => (string) ($settings['activecampaign_list_id'] ?? ''),
            'hubspot_token' => (string) ($settings['hubspot_token'] ?? ''),
            'klaviyo_key' => (string) ($settings['klaviyo_api_key'] ?? ''),
            'klaviyo_list' => (string) ($settings['klaviyo_list_id'] ?? ''),
            'sheets_url' => (string) ($settings['google_sheets_url'] ?? ''),
            'sheets_secret' => (string) ($settings['google_sheets_secret'] ?? ''),
            'email_field' => (string) ($settings['integration_email_field'] ?? ''),
            'name_field' => (string) ($settings['integration_name_field'] ?? ''),
        ];

        update_option(self::OPTION_PREFIX . $form_id, $config, false);
    }

    /**
     * The stored configuration for a form.
     *
     * @param string $form_id Form element id.
     *
     * @return array<string,mixed>
     */
    public static function get_settings(string $form_id): array
    {
        $config = get_option(self::OPTION_PREFIX . $form_id, []);

        return is_array($config) ? $config : [];
    }

    /**
     * Handle a submission.
     *
     * @return void
     */
    public static function handle(): void
    {
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'king-addons-js')) {
            wp_send_json_error(['message' => 'invalid-nonce', 'status' => 'error']);
        }

        if (class_exists('King_Addons\\Form_Builder_Security')) {
            Form_Builder_Security::guard_spam();
        }

        $form_id = isset($_POST['king_addons_form_id'])
            ? sanitize_text_field(wp_unslash($_POST['king_addons_form_id']))
            : '';

        if ('' === $form_id) {
            wp_send_json_error(['message' => 'missing-form-id', 'status' => 'error']);
        }

        $config = self::get_settings($form_id);
        if (empty($config['enabled'])) {
            wp_send_json_success([
                'action' => 'king_addons_form_builder_integrations',
                'message' => esc_html__('No services are switched on for this form.', 'king-addons'),
                'status' => 'success',
                'results' => [],
            ]);
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- checked above.
        $raw = isset($_POST['form_content']) && is_array($_POST['form_content']) ? wp_unslash($_POST['form_content']) : [];
        $fields = self::normalise_fields($raw);

        $results = [];
        foreach ((array) $config['enabled'] as $service) {
            $results[$service] = self::dispatch((string) $service, $config, $fields);
        }

        $failed = array_keys(array_filter($results, static function ($ok) {
            return true !== $ok;
        }));

        // The widget's front-end script decides success or failure from
        // "status", and reports "message" next to the submission.
        wp_send_json_success([
            'action' => 'king_addons_form_builder_integrations',
            'status' => empty($failed) ? 'success' : 'error',
            'message' => empty($failed)
                ? esc_html__('Sent to every connected service.', 'king-addons')
                : sprintf(
                    /* translators: %s: comma separated list of services. */
                    esc_html__('These services did not accept the submission: %s', 'king-addons'),
                    implode(', ', $failed)
                ),
            'results' => $results,
            'failed' => $failed,
        ]);
    }

    /**
     * Flatten what the browser sent into label => value pairs.
     *
     * @param array<mixed> $raw Raw form content.
     *
     * @return array<string,array{label:string,value:string,id:string}>
     */
    private static function normalise_fields(array $raw): array
    {
        $fields = [];

        foreach ($raw as $key => $entry) {
            if (!is_array($entry) || count($entry) < 2) {
                continue;
            }

            $value = $entry[1];

            if (is_array($value)) {
                // Radio and checkbox groups arrive as [value, checked, name, id].
                $picked = [];
                foreach ($value as $option) {
                    if (is_array($option) && !empty($option[1]) && isset($option[0])) {
                        $picked[] = (string) $option[0];
                    } elseif (is_scalar($option)) {
                        $picked[] = (string) $option;
                    }
                }
                $value = implode(', ', $picked);
            }

            $id = str_replace('form_field-', '', sanitize_text_field((string) $key));

            $fields[$id] = [
                'id' => $id,
                'label' => isset($entry[2]) && is_scalar($entry[2]) ? sanitize_text_field((string) $entry[2]) : $id,
                'value' => sanitize_textarea_field((string) $value),
            ];
        }

        return $fields;
    }

    /**
     * Send to one service.
     *
     * @param string                                                  $service Service key.
     * @param array<string,mixed>                                     $config  Stored config.
     * @param array<string,array{label:string,value:string,id:string}> $fields Submitted fields.
     *
     * @return true|string True on success, otherwise a short reason.
     */
    private static function dispatch(string $service, array $config, array $fields)
    {
        switch ($service) {
            case 'slack':
                return self::send_slack($config, $fields);
            case 'telegram':
                return self::send_telegram($config, $fields);
            case 'brevo':
                return self::send_brevo($config, $fields);
            case 'convertkit':
                return self::send_convertkit($config, $fields);
            case 'activecampaign':
                return self::send_activecampaign($config, $fields);
            case 'hubspot':
                return self::send_hubspot($config, $fields);
            case 'klaviyo':
                return self::send_klaviyo($config, $fields);
            case 'google_sheets':
                return self::send_google_sheets($config, $fields);
        }

        return 'unknown-service';
    }

    /**
     * The endpoint a service posts to.
     *
     * Filterable so a site can route through its own proxy, and so the
     * integrations can be exercised against a stub.
     *
     * @param string $service Service key.
     * @param string $url     Default URL.
     *
     * @return string
     */
    private static function endpoint(string $service, string $url): string
    {
        /**
         * Filters the endpoint one Form Builder integration posts to.
         *
         * @param string $url     Endpoint URL.
         * @param string $service Service key.
         */
        return (string) apply_filters('king_addons/form_builder/integration_endpoint', $url, $service);
    }

    /**
     * The submission as plain "Label: value" lines.
     *
     * @param array<string,array{label:string,value:string,id:string}> $fields Fields.
     *
     * @return string
     */
    private static function as_text(array $fields): string
    {
        $lines = [];

        foreach ($fields as $field) {
            $label = '' !== $field['label'] ? $field['label'] : $field['id'];
            $lines[] = $label . ': ' . $field['value'];
        }

        return implode("\n", $lines);
    }

    /**
     * The value of a field the author nominated, by field ID.
     *
     * @param array<string,array{label:string,value:string,id:string}> $fields   Fields.
     * @param string                                                  $field_id Field ID.
     *
     * @return string
     */
    private static function value_of(array $fields, string $field_id): string
    {
        $field_id = trim($field_id);

        if ('' !== $field_id && isset($fields[$field_id])) {
            return $fields[$field_id]['value'];
        }

        foreach ($fields as $field) {
            if (!is_array($field)) {
                continue;
            }
            if ($field_id === (string) ($field['id'] ?? '')) {
                return $field['value'];
            }
        }

        return '';
    }

    /**
     * The submitted email address.
     *
     * @param array<string,mixed>                                     $config Config.
     * @param array<string,array{label:string,value:string,id:string}> $fields Fields.
     *
     * @return string
     */
    private static function email_of(array $config, array $fields): string
    {
        $email = self::value_of($fields, (string) ($config['email_field'] ?? ''));

        if ('' === $email) {
            // Fall back to the first value that looks like an address.
            foreach ($fields as $field) {
                if (is_email($field['value'])) {
                    $email = $field['value'];
                    break;
                }
            }
        }

        return is_email($email) ? $email : '';
    }

    /**
     * Post JSON and report whether the service accepted it.
     *
     * @param string               $url     Endpoint.
     * @param array<string,mixed>  $body    Payload.
     * @param array<string,string> $headers Headers.
     *
     * @return true|string
     */
    private static function post_json(string $url, array $body, array $headers = [])
    {
        if ('' === $url || !wp_http_validate_url($url)) {
            return 'bad-url';
        }

        $response = wp_remote_post($url, [
            'timeout' => 15,
            'headers' => array_merge(['Content-Type' => 'application/json'], $headers),
            'body' => wp_json_encode($body),
        ]);

        if (is_wp_error($response)) {
            return 'request-failed';
        }

        $code = (int) wp_remote_retrieve_response_code($response);

        return ($code >= 200 && $code < 300) ? true : 'http-' . $code;
    }

    /**
     * Slack incoming webhook.
     *
     * @param array<string,mixed> $config Config.
     * @param array<string,mixed> $fields Fields.
     *
     * @return true|string
     */
    private static function send_slack(array $config, array $fields)
    {
        $title = '' !== (string) ($config['slack_title'] ?? '')
            ? (string) $config['slack_title']
            : esc_html__('New form submission', 'king-addons');

        return self::post_json(
            self::endpoint('slack', (string) ($config['slack_webhook'] ?? '')),
            ['text' => $title . "\n" . self::as_text($fields)]
        );
    }

    /**
     * Telegram bot message.
     *
     * @param array<string,mixed> $config Config.
     * @param array<string,mixed> $fields Fields.
     *
     * @return true|string
     */
    private static function send_telegram(array $config, array $fields)
    {
        $token = trim((string) ($config['telegram_token'] ?? ''));
        $chat = trim((string) ($config['telegram_chat'] ?? ''));

        if ('' === $token || '' === $chat) {
            return 'not-configured';
        }

        return self::post_json(
            self::endpoint('telegram', 'https://api.telegram.org/bot' . rawurlencode($token) . '/sendMessage'),
            ['chat_id' => $chat, 'text' => self::as_text($fields)]
        );
    }

    /**
     * Brevo contact.
     *
     * @param array<string,mixed> $config Config.
     * @param array<string,mixed> $fields Fields.
     *
     * @return true|string
     */
    private static function send_brevo(array $config, array $fields)
    {
        $key = trim((string) ($config['brevo_key'] ?? ''));
        $email = self::email_of($config, $fields);

        if ('' === $key || '' === $email) {
            return 'not-configured';
        }

        $body = ['email' => $email, 'updateEnabled' => true];

        $list = trim((string) ($config['brevo_list'] ?? ''));
        if ('' !== $list) {
            $body['listIds'] = [(int) $list];
        }

        $name = self::value_of($fields, (string) ($config['name_field'] ?? ''));
        if ('' !== $name) {
            $body['attributes'] = ['FIRSTNAME' => $name];
        }

        return self::post_json(
            self::endpoint('brevo', 'https://api.brevo.com/v3/contacts'),
            $body,
            ['api-key' => $key, 'accept' => 'application/json']
        );
    }

    /**
     * ConvertKit (Kit) subscriber.
     *
     * @param array<string,mixed> $config Config.
     * @param array<string,mixed> $fields Fields.
     *
     * @return true|string
     */
    private static function send_convertkit(array $config, array $fields)
    {
        $key = trim((string) ($config['convertkit_key'] ?? ''));
        $form = trim((string) ($config['convertkit_form'] ?? ''));
        $email = self::email_of($config, $fields);

        if ('' === $key || '' === $form || '' === $email) {
            return 'not-configured';
        }

        $body = ['api_key' => $key, 'email' => $email];

        $name = self::value_of($fields, (string) ($config['name_field'] ?? ''));
        if ('' !== $name) {
            $body['first_name'] = $name;
        }

        return self::post_json(
            self::endpoint('convertkit', 'https://api.convertkit.com/v3/forms/' . rawurlencode($form) . '/subscribe'),
            $body
        );
    }

    /**
     * ActiveCampaign contact.
     *
     * @param array<string,mixed> $config Config.
     * @param array<string,mixed> $fields Fields.
     *
     * @return true|string
     */
    private static function send_activecampaign(array $config, array $fields)
    {
        $base = rtrim(trim((string) ($config['ac_url'] ?? '')), '/');
        $key = trim((string) ($config['ac_key'] ?? ''));
        $email = self::email_of($config, $fields);

        if ('' === $base || '' === $key || '' === $email) {
            return 'not-configured';
        }

        $body = ['contact' => ['email' => $email]];

        $name = self::value_of($fields, (string) ($config['name_field'] ?? ''));
        if ('' !== $name) {
            $body['contact']['firstName'] = $name;
        }

        $created = self::post_json(
            self::endpoint('activecampaign', $base . '/api/3/contacts'),
            $body,
            ['Api-Token' => $key]
        );

        $list = trim((string) ($config['ac_list'] ?? ''));
        if (true !== $created || '' === $list) {
            return $created;
        }

        // ActiveCampaign keeps list membership separate from the contact, so
        // the list ID needs its own call.
        return self::post_json(
            self::endpoint('activecampaign_list', $base . '/api/3/contactLists'),
            [
                'contactList' => [
                    'list' => (int) $list,
                    'email' => $email,
                    'status' => 1,
                ],
            ],
            ['Api-Token' => $key]
        );
    }

    /**
     * HubSpot contact.
     *
     * @param array<string,mixed> $config Config.
     * @param array<string,mixed> $fields Fields.
     *
     * @return true|string
     */
    private static function send_hubspot(array $config, array $fields)
    {
        $token = trim((string) ($config['hubspot_token'] ?? ''));
        $email = self::email_of($config, $fields);

        if ('' === $token || '' === $email) {
            return 'not-configured';
        }

        $properties = ['email' => $email];

        $name = self::value_of($fields, (string) ($config['name_field'] ?? ''));
        if ('' !== $name) {
            $properties['firstname'] = $name;
        }

        return self::post_json(
            self::endpoint('hubspot', 'https://api.hubapi.com/crm/v3/objects/contacts'),
            ['properties' => $properties],
            ['Authorization' => 'Bearer ' . $token]
        );
    }

    /**
     * Klaviyo profile.
     *
     * @param array<string,mixed> $config Config.
     * @param array<string,mixed> $fields Fields.
     *
     * @return true|string
     */
    private static function send_klaviyo(array $config, array $fields)
    {
        $key = trim((string) ($config['klaviyo_key'] ?? ''));
        $email = self::email_of($config, $fields);

        if ('' === $key || '' === $email) {
            return 'not-configured';
        }

        $attributes = ['email' => $email];

        $name = self::value_of($fields, (string) ($config['name_field'] ?? ''));
        if ('' !== $name) {
            $attributes['first_name'] = $name;
        }

        $body = ['data' => ['type' => 'profile', 'attributes' => $attributes]];

        $list = trim((string) ($config['klaviyo_list'] ?? ''));
        if ('' !== $list) {
            $body['data']['relationships'] = [
                'lists' => ['data' => [['type' => 'list', 'id' => $list]]],
            ];
        }

        return self::post_json(
            self::endpoint('klaviyo', 'https://a.klaviyo.com/api/profiles/'),
            $body,
            [
                'Authorization' => 'Klaviyo-API-Key ' . $key,
                'revision' => '2024-10-15',
            ]
        );
    }

    /**
     * Google Sheets, through an Apps Script web app.
     *
     * Google's own API needs OAuth, which a form widget cannot do on its own;
     * a published Apps Script bound to the sheet is the usual way round it.
     *
     * @param array<string,mixed> $config Config.
     * @param array<string,mixed> $fields Fields.
     *
     * @return true|string
     */
    private static function send_google_sheets(array $config, array $fields)
    {
        $url = trim((string) ($config['sheets_url'] ?? ''));

        if ('' === $url) {
            return 'not-configured';
        }

        $row = [];
        foreach ($fields as $field) {
            $row['' !== $field['label'] ? $field['label'] : $field['id']] = $field['value'];
        }

        $body = [
            'secret' => (string) ($config['sheets_secret'] ?? ''),
            'submitted_at' => current_time('mysql'),
            'row' => $row,
        ];

        return self::post_json(self::endpoint('google_sheets', $url), $body);
    }
}
