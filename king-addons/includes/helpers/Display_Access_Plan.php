<?php
namespace King_Addons;

if (!defined('ABSPATH')) { exit; }

/** Shared Free/Pro product rules for both display extensions. */
class Display_Access_Plan
{
    public static function has_pro(): bool
    {
        return function_exists('king_addons_freemius') && \king_addons_freemius()->can_use_premium_code__premium_only();
    }

    public static function upgrade_url(): string
    {
        return 'https://kingaddons.com/pricing/?utm_source=display-access&utm_medium=plugin&utm_campaign=pro';
    }

    public static function pro_label(string $label): string
    {
        return trim(wp_strip_all_tags($label)) . ' (' . __('Pro', 'king-addons') . ')';
    }

    public static function pro_control(string $id): bool
    {
        if (in_array($id, ['cd_rules', 'cd_reveal', 'cd_reveal_label', 'cd_hide_empty', 'cd_fallback_template', 'cd_heading_datetime', 'cd_heading_url', 'cd_heading_pro',
            'protected_content_password', 'protected_content_fallback_template', 'protected_content_fallback_form',
            'protected_content_date_heading', 'protected_content_url_heading', 'protected_content_woo_heading',
            'ka_access_check', 'ka_access_form_report', 'ka_display_visitor', 'ka_display_check_button', 'ka_display_check'], true)) { return true; }
        foreach (['cd_cond_browser_', 'cd_cond_date_', 'cd_cond_time_', 'cd_cond_url_', 'cd_cond_get_',
            'cd_cond_ref_', 'cd_cond_woo_', 'cd_cond_cookie_', 'cd_cond_lang_', 'protected_content_password_',
            'protected_content_locker_', 'protected_content_cond_browser', 'protected_content_cond_date_',
            'protected_content_cond_url_', 'protected_content_cond_woo_'] as $prefix) {
            if (strpos($id, $prefix) === 0) { return true; }
        }
        return $id === 'cd_cond_days_of_week';
    }

    /** Runtime copy only. Never rewrite the author's saved settings on a plan change. */
    public static function runtime_settings(array $settings, string $feature): array
    {
        // Runtime-only flag: never trust a saved value or discard saved restrictions.
        $settings['_ka_plan_blocked'] = !self::has_pro() && self::requires_pro($settings, $feature);
        if (self::has_pro()) { return $settings; }
        if ($feature === 'cd') {
            foreach (['browser', 'date', 'time', 'url', 'ref', 'woo', 'cookie', 'lang'] as $type) {
                $settings['cd_cond_' . $type . '_enable'] = '';
            }
            $settings['cd_reveal'] = '';
            $settings['cd_hide_empty'] = '';
            if (($settings['cd_fallback_type'] ?? '') === 'template') { $settings['cd_fallback_type'] = 'hide'; }
        } else {
            if (in_array($settings['protected_content_fallback_type'] ?? '', ['template', 'form'], true)) {
                $settings['protected_content_fallback_type'] = 'none';
            }
        }
        return $settings;
    }

    /** An unavailable access condition must not turn into public access. */
    private static function requires_pro(array $settings, string $feature): bool
    {
        if ($feature === 'cd') {
            if (($settings['cd_enable'] ?? '') !== 'yes') { return false; }
            if (($settings['cd_rules_mode'] ?? '') === 'rules') { return true; }
            foreach (['browser', 'date', 'time', 'url', 'ref', 'woo', 'cookie', 'lang'] as $type) {
                if (($settings['cd_cond_' . $type . '_enable'] ?? '') === 'yes') { return true; }
            }
            return false;
        }
        if (($settings['protected_content_enable'] ?? '') !== 'yes') { return false; }
        $mode = $settings['protected_content_mode'] ?? 'role';
        if (in_array($mode, ['password', 'locker'], true)) { return true; }
        if ($mode === 'conditions') {
            foreach (['browser', 'date_from', 'date_to', 'url_param', 'woo_in_cart_product_ids', 'woo_min_cart_total', 'woo_customer_bought_ids'] as $condition) {
                $value = $settings['protected_content_cond_' . $condition] ?? '';
                if ($value !== '' && $value !== [] && $value !== null && $value !== false && $value !== 0 && $value !== '0') { return true; }
            }
        }
        return false;
    }
}
