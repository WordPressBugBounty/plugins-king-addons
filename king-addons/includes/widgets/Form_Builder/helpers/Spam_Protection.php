<?php
/**
 * Spam protection for Form Builder.
 *
 * Honeypot, Cloudflare Turnstile and hCaptcha, alongside the reCAPTCHA v3 field
 * the widget already had.
 *
 * @package King_Addons
 */

namespace King_Addons;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders the chosen challenge and checks it on the server.
 */
class Form_Spam_Protection
{
    /**
     * Name of the honeypot input.
     *
     * Deliberately plausible: a bot filling in everything it recognises will
     * fill this one too.
     */
    public const HONEYPOT_FIELD = 'king_addons_website_url';

    /**
     * Modes offered in the panel.
     *
     * @return array<string,string>
     */
    public static function modes(): array
    {
        return [
            'none' => esc_html__('None', 'king-addons'),
            'honeypot' => esc_html__('Honeypot (no third party)', 'king-addons'),
            'turnstile' => esc_html__('Cloudflare Turnstile', 'king-addons'),
            'hcaptcha' => esc_html__('hCaptcha', 'king-addons'),
        ];
    }

    /**
     * The mode stored for a form.
     *
     * @param string $form_id Elementor element id of the form.
     *
     * @return string
     */
    public static function get_mode(string $form_id): string
    {
        $mode = (string) get_option('king_addons_spam_protection_' . $form_id, 'none');

        return array_key_exists($mode, self::modes()) ? $mode : 'none';
    }

    /**
     * Store the mode for a form.
     *
     * @param string $form_id Elementor element id.
     * @param string $mode    Chosen mode.
     *
     * @return void
     */
    public static function save_mode(string $form_id, string $mode): void
    {
        if (!array_key_exists($mode, self::modes())) {
            $mode = 'none';
        }

        update_option('king_addons_spam_protection_' . $form_id, $mode);
    }

    /**
     * Markup for the chosen challenge.
     *
     * @param string $mode Mode.
     *
     * @return string
     */
    public static function render(string $mode): string
    {
        switch ($mode) {
            case 'honeypot':
                // Hidden from people with CSS and from screen readers, but a
                // normal input as far as an automated filler is concerned.
                return '<div class="king-addons-form-honeypot" aria-hidden="true">'
                    . '<label>' . esc_html__('Leave this field empty', 'king-addons')
                    . '<input type="text" name="' . esc_attr(self::HONEYPOT_FIELD) . '"'
                    . ' class="king-addons-form-honeypot__input" tabindex="-1" autocomplete="off" value=""></label>'
                    . '</div>';

            case 'turnstile':
                $site_key = (string) get_option('king_addons_turnstile_site_key', '');
                if ('' === $site_key) {
                    return self::missing_keys_notice(esc_html__('Cloudflare Turnstile', 'king-addons'));
                }

                return '<div class="king-addons-form-captcha cf-turnstile" data-ka-captcha="turnstile"'
                    . ' data-sitekey="' . esc_attr($site_key) . '"></div>';

            case 'hcaptcha':
                $site_key = (string) get_option('king_addons_hcaptcha_site_key', '');
                if ('' === $site_key) {
                    return self::missing_keys_notice(esc_html__('hCaptcha', 'king-addons'));
                }

                return '<div class="king-addons-form-captcha h-captcha" data-ka-captcha="hcaptcha"'
                    . ' data-sitekey="' . esc_attr($site_key) . '"></div>';
        }

        return '';
    }

    /**
     * The script a mode needs, if any.
     *
     * @param string $mode Mode.
     *
     * @return array{handle:string,src:string}|null
     */
    public static function script(string $mode): ?array
    {
        if ('turnstile' === $mode) {
            return [
                'handle' => 'king-addons-turnstile',
                'src' => 'https://challenges.cloudflare.com/turnstile/v0/api.js',
            ];
        }

        if ('hcaptcha' === $mode) {
            return [
                'handle' => 'king-addons-hcaptcha',
                'src' => 'https://js.hcaptcha.com/1/api.js',
            ];
        }

        return null;
    }

    /**
     * Check the submitted challenge.
     *
     * Called by every submit action, because each one is its own AJAX request:
     * checking only in the browser would leave the endpoints open.
     *
     * @param string               $form_id Form element id.
     * @param array<string,mixed>  $request Request data ($_POST).
     *
     * @return true|string True when the submission may proceed, otherwise the reason.
     */
    public static function verify(string $form_id, array $request)
    {
        $mode = self::get_mode($form_id);

        if ('none' === $mode) {
            return true;
        }

        if ('honeypot' === $mode) {
            $value = isset($request[self::HONEYPOT_FIELD])
                ? trim((string) wp_unslash($request[self::HONEYPOT_FIELD]))
                : '';

            return '' === $value ? true : 'honeypot';
        }

        $token = isset($request['ka_captcha_token'])
            ? sanitize_text_field(wp_unslash($request['ka_captcha_token']))
            : '';

        if ('' === $token) {
            return 'missing-token';
        }

        if ('turnstile' === $mode) {
            return self::verify_remote(
                'https://challenges.cloudflare.com/turnstile/v0/siteverify',
                (string) get_option('king_addons_turnstile_secret_key', ''),
                $token
            );
        }

        return self::verify_remote(
            'https://hcaptcha.com/siteverify',
            (string) get_option('king_addons_hcaptcha_secret_key', ''),
            $token
        );
    }

    /**
     * Ask the provider whether a token is good.
     *
     * @param string $endpoint Provider endpoint.
     * @param string $secret   Secret key.
     * @param string $token    Token from the browser.
     *
     * @return true|string
     */
    private static function verify_remote(string $endpoint, string $secret, string $token)
    {
        if ('' === $secret) {
            return 'no-secret-key';
        }

        $body = [
            'secret' => $secret,
            'response' => $token,
        ];

        $ip = self::client_ip();
        if ('' !== $ip) {
            $body['remoteip'] = $ip;
        }

        $response = wp_remote_post($endpoint, [
            'timeout' => 10,
            'body' => $body,
        ]);

        if (is_wp_error($response)) {
            return 'verification-failed';
        }

        $data = json_decode((string) wp_remote_retrieve_body($response), true);

        return (is_array($data) && !empty($data['success'])) ? true : 'rejected';
    }

    /**
     * The visitor's address, when the server reports one it is safe to trust.
     *
     * @return string
     */
    private static function client_ip(): string
    {
        // Only REMOTE_ADDR: forwarded headers can be set by the client.
        $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) wp_unslash($_SERVER['REMOTE_ADDR']) : '';

        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
    }

    /**
     * Shown in place of a challenge whose keys are not set up yet.
     *
     * @param string $provider Provider name.
     *
     * @return string
     */
    private static function missing_keys_notice(string $provider): string
    {
        if (!current_user_can('manage_options')) {
            return '';
        }

        return '<p class="king-addons-form-captcha-notice">' . sprintf(
            /* translators: 1: provider name, 2: settings URL. */
            esc_html__('%1$s is selected but its keys are missing. Add them under %2$s.', 'king-addons'),
            esc_html($provider),
            '<a href="' . esc_url(admin_url('admin.php?page=king-addons-settings')) . '">'
                . esc_html__('King Addons settings', 'king-addons') . '</a>'
        ) . '</p>';
    }
}
