<?php
/**
 * Smart Links settings helpers.
 *
 * @package King_Addons
 */

namespace King_Addons\Smart_Links;

if (!defined('ABSPATH')) {
    exit;
}

class Smart_Links_Settings
{
    private const OPTION_KEY = 'king_addons_smart_links_settings';

    public static function get_settings(): array
    {
        $saved = get_option(self::OPTION_KEY, []);
        return wp_parse_args($saved, self::defaults());
    }

    public static function get(string $key, $default = null)
    {
        $settings = self::get_settings();
        return $settings[$key] ?? $default;
    }

    public static function defaults(): array
    {
        return [
            'base_path' => 'go',
            'default_slug_length' => 6,
            'allow_manual_slug' => true,
            'default_redirect_type' => '302',
            'tracking_enabled' => true,
            'exclude_bots' => false,
            'unique_click_window' => 'daily',
            'retention_days' => 30,
            'pass_query_params' => true,
            'whitelist_query_params' => '',
            'blacklist_query_params' => '',
            'cache_ttl' => 0,
        ];
    }

    public static function sanitize(array $settings): array
    {
        $defaults = self::defaults();
        $current = self::get_settings();

        $base_path = sanitize_title_with_dashes($settings['base_path'] ?? $defaults['base_path']);
        $base_path = trim($base_path, '/');
        if ($base_path === '') {
            $base_path = $defaults['base_path'];
        }

        $redirect_type = ($settings['default_redirect_type'] ?? $defaults['default_redirect_type']);
        $redirect_type = in_array($redirect_type, ['301', '302'], true) ? $redirect_type : $defaults['default_redirect_type'];

        $unique_window = ($settings['unique_click_window'] ?? $defaults['unique_click_window']);
        $unique_window = in_array($unique_window, ['daily', 'rolling'], true) ? $unique_window : $defaults['unique_click_window'];

        $sanitized = [
            'base_path' => $base_path,
            'default_slug_length' => max(4, min(20, absint($settings['default_slug_length'] ?? $defaults['default_slug_length']))),
            'allow_manual_slug' => !empty($settings['allow_manual_slug']),
            'default_redirect_type' => $redirect_type,
            'tracking_enabled' => !empty($settings['tracking_enabled']),
            'exclude_bots' => !empty($settings['exclude_bots']),
            'unique_click_window' => $unique_window,
            'retention_days' => max(0, absint($settings['retention_days'] ?? $defaults['retention_days'])),
            'pass_query_params' => !empty($settings['pass_query_params']),
            'whitelist_query_params' => sanitize_text_field($settings['whitelist_query_params'] ?? ''),
            'blacklist_query_params' => sanitize_text_field($settings['blacklist_query_params'] ?? ''),
            'cache_ttl' => max(0, absint($settings['cache_ttl'] ?? 0)),
        ];

        if ($current['base_path'] !== $sanitized['base_path']) {
            update_option('king_addons_smart_links_flush_rewrite', 1);
        }

        return wp_parse_args($sanitized, $defaults);
    }

    public static function get_base_path(): string
    {
        return (string) self::get('base_path', 'go');
    }

    public static function get_short_base_url(): string
    {
        $base = trim(self::get_base_path(), '/');
        return home_url(user_trailingslashit($base));
    }
}
