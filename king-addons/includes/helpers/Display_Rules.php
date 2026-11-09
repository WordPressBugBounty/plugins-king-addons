<?php
namespace King_Addons;

if (!defined('ABSPATH')) { exit; }

/** Shared, side-effect-free rule evaluation for display and editor explanations. */
class Display_Rules
{
    public static function evaluate(array $rules, array $context = []): array
    {
        $checks = [];
        $groups = [];
        $group = true;
        $now = $context['now'] ?? current_datetime();
        $types = self::type_options();
        foreach (array_slice($rules, 0, 50) as $index => $rule) {
            if (!is_array($rule)) { continue; }
            [$pass, $detail] = self::check($rule, $context, $now);
            if ($index > 0 && ($rule['join'] ?? 'and') === 'or') {
                $groups[] = $group;
                $group = true;
            }
            $group = $group && $pass;
            $checks[] = ['pass' => $pass, 'label' => ($rule['label'] ?? '') ?: ($types[$rule['type'] ?? ''] ?? __('Rule', 'king-addons')), 'detail' => $detail];
        }
        $groups[] = $group;
        return ['pass' => in_array(true, $groups, true), 'checks' => $checks,
            'clock' => $now->format('Y-m-d H:i') . ' · ' . wp_timezone_string(),
            'empty' => empty($checks)];
    }

    public static function type_options(): array
    {
        return ['login'=>__('Login status', 'king-addons'), 'role'=>__('User role', 'king-addons'), 'device'=>__('Device', 'king-addons'), 'query'=>__('URL parameter', 'king-addons'), 'meta'=>__('Post field', 'king-addons'), 'acf'=>__('ACF field', 'king-addons'), 'date'=>__('Date window', 'king-addons'), 'time'=>__('Daily time window', 'king-addons'), 'annual'=>__('Yearly date window', 'king-addons'), 'browser'=>__('Browser', 'king-addons'), 'referrer'=>__('Referral source', 'king-addons'), 'cookie'=>__('Cookie value', 'king-addons'), 'language'=>__('Site language', 'king-addons'), 'woo'=>__('WooCommerce', 'king-addons')];
    }

    private static function check(array $r, array $c, \DateTimeInterface $now): array
    {
        $value = (string) ($r['value'] ?? '');
        switch ($r['type'] ?? '') {
            case 'login':
                $logged = $c['logged_in'] ?? is_user_logged_in();
                return [($r['login_mode'] ?? $value) === 'guest' ? !$logged : $logged, $logged ? __('Visitor is logged in.', 'king-addons') : __('Visitor is a guest.', 'king-addons')];
            case 'role':
                $value = (string) ($r['role'] ?? $value);
                $roles = $c['roles'] ?? wp_get_current_user()->roles;
                return [$value !== '' && in_array($value, $roles, true), __('Required role:', 'king-addons') . ' ' . $value];
            case 'device':
                $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
                $device = preg_match('/ipad|tablet/', $ua) ? 'tablet' : (preg_match('/mobile|iphone|android/', $ua) ? 'mobile' : 'desktop');
                return [$device === ($r['device'] ?? $value), __('Detected device:', 'king-addons') . ' ' . $device];
            case 'query':
                $key = (string) ($r['key'] ?? '');
                $query = $c['query'] ?? $_GET;
                $actual = $query[$key] ?? null;
                return [$key !== '' && self::compare($actual, $value, $r['operator'] ?? 'equals'), __('URL parameter:', 'king-addons') . ' ' . $key];
            case 'browser':
                $browser = $c['browser'] ?? self::browser();
                return [$browser === ($r['browser'] ?? ''), __('Detected browser:', 'king-addons') . ' ' . $browser];
            case 'referrer':
                $referrer = $c['referrer'] ?? ($_SERVER['HTTP_REFERER'] ?? '');
                return [self::compare($referrer, $value, $r['operator'] ?? 'contains'), __('Referral source:', 'king-addons') . ' ' . ($referrer ?: __('None', 'king-addons'))];
            case 'cookie':
                $key = (string) ($r['key'] ?? ''); $cookies = $c['cookies'] ?? $_COOKIE;
                return [$key !== '' && self::compare($cookies[$key] ?? null, $value, $r['operator'] ?? 'equals'), __('Cookie:', 'king-addons') . ' ' . $key];
            case 'language':
                $language = $c['language'] ?? (defined('ICL_LANGUAGE_CODE') && ICL_LANGUAGE_CODE ? ICL_LANGUAGE_CODE : (function_exists('pll_current_language') ? (pll_current_language('slug') ?: get_locale()) : get_locale()));
                $expected = (string) ($r['language'] ?? '');
                return [$expected !== '' && stripos(str_replace('-', '_', $language), str_replace('-', '_', $expected)) === 0, __('Current language:', 'king-addons') . ' ' . $language];
            case 'woo':
                return self::woocommerce($r);
            case 'meta':
            case 'acf':
                $key = (string) (!empty($r['acf_field']) ? $r['acf_field'] : ($r['key'] ?? ''));
                $id = ($r['source'] ?? 'item') === 'page' ? ($c['page_id'] ?? get_queried_object_id()) : ($c['post_id'] ?? get_the_ID());
                if (!$id || $key === '') { return [false, __('Choose a field and a content source.', 'king-addons')]; }
                if (($r['type'] ?? '') === 'acf' && !function_exists('get_field')) { return [false, __('ACF is not active.', 'king-addons')]; }
                $actual = ($r['type'] ?? '') === 'acf' ? get_field($key, $id, false) : get_post_meta($id, $key, true);
                return [self::compare($actual, $value, $r['operator'] ?? 'not_empty'), sprintf(__('Field %1$s · source #%2$d', 'king-addons'), $key, $id)];
            case 'date':
                $from = (string) ($r['from'] ?? ''); $to = (string) ($r['to'] ?? '');
                if ($from === '' && $to === '') { return [false, __('Set a start or end date.', 'king-addons')]; }
                $current = $now->format('Y-m-d H:i');
                return [($from === '' || $current >= str_replace('T', ' ', $from)) && ($to === '' || $current <= str_replace('T', ' ', $to)), __('Date window uses the site timezone.', 'king-addons')];
            case 'time':
                $from = (string) ($r['time_from'] ?? ''); $to = (string) ($r['time_to'] ?? '');
                if (($from !== '' && !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $from)) || ($to !== '' && !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $to)) || ($from === '' && $to === '')) {
                    return [false, __('Use HH:MM, for example 09:00 or 22:30.', 'king-addons')];
                }
                $time = $now->format('H:i');
                $pass = $from !== '' && $to !== '' && $from > $to ? ($time >= $from || $time <= $to) : (($from === '' || $time >= $from) && ($to === '' || $time <= $to));
                return [$pass, __('Time window:', 'king-addons') . ' ' . $from . '–' . $to];
            case 'annual':
                $from = (string) ($r['annual_from'] ?? ''); $to = (string) ($r['annual_to'] ?? '');
                foreach ([$from, $to] as $date) {
                    if (!preg_match('/^(\d{2})-(\d{2})$/', $date, $m) || !checkdate((int)$m[1], (int)$m[2], 2000)) {
                        return [false, __('Use MM-DD for both dates, for example 12-20 and 01-10.', 'king-addons')];
                    }
                }
                $date = $now->format('m-d');
                return [$from <= $to ? ($date >= $from && $date <= $to) : ($date >= $from || $date <= $to), __('Repeats every year:', 'king-addons') . ' ' . $from . '–' . $to];
        }
        return [false, __('Choose a rule type.', 'king-addons')];
    }

    public static function language_options(): array
    {
        $options = ['en'=>'English', 'es'=>'Español', 'fr'=>'Français', 'de'=>'Deutsch', 'it'=>'Italiano', 'pt'=>'Português', 'ru'=>'Русский', 'zh'=>'中文', 'ja'=>'日本語', 'ko'=>'한국어', 'ar'=>'العربية', 'hi'=>'हिन्दी', 'nl'=>'Nederlands', 'pl'=>'Polski', 'tr'=>'Türkçe', 'uk'=>'Українська'];
        foreach (get_available_languages() as $locale) { $options[$locale] = $locale; }
        $locale = get_locale(); if (!isset($options[substr($locale, 0, 2)])) { $options[$locale] = $locale; }
        foreach ((array) apply_filters('wpml_active_languages', null, ['skip_missing'=>0]) as $language) {
            if (is_array($language) && !empty($language['code'])) { $options[$language['code']] = $language['native_name'] ?? $language['code']; }
        }
        if (function_exists('pll_languages_list')) { foreach ((array) pll_languages_list(['fields'=>'slug']) as $slug) { $options[$slug] = $options[$slug] ?? $slug; } }
        return $options;
    }

    private static function browser(): string
    {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        foreach (['edge'=>'/Edg(e|A|iOS)?/i', 'opera'=>'/OPR|Opera/i', 'chrome'=>'/Chrome|CriOS/i', 'firefox'=>'/Firefox|FxiOS/i', 'safari'=>'/Safari/i', 'ie'=>'/MSIE|Trident/i'] as $browser=>$pattern) { if (preg_match($pattern, $ua)) { return $browser; } }
        return 'unknown';
    }

    private static function woocommerce(array $r): array
    {
        if (!function_exists('WC') || !class_exists('WooCommerce')) { return [false, __('WooCommerce is not active.', 'king-addons')]; }
        $check = $r['woo_check'] ?? 'cart_total';
        if ($check === 'cart_total') {
            $total = WC()->cart ? (float) WC()->cart->get_total('edit') : 0;
            return [$total >= max(0, (float) ($r['min_total'] ?? 0)), __('Cart total:', 'king-addons') . ' ' . $total];
        }
        $ids = array_values(array_unique(array_filter(array_map('absint', preg_split('/[\s,]+/', trim((string) ($r['product_ids'] ?? '')))))));
        if (!$ids) { return [false, __('Enter at least one product ID.', 'king-addons')]; }
        $matches = [];
        if ($check === 'in_cart') {
            $in_cart = [];
            foreach (WC()->cart ? WC()->cart->get_cart() : [] as $item) { $in_cart[] = (int) ($item['product_id'] ?? 0); $in_cart[] = (int) ($item['variation_id'] ?? 0); }
            foreach ($ids as $id) { $matches[] = in_array($id, $in_cart, true); }
        } elseif ($check === 'purchased') {
            if (!is_user_logged_in() || !function_exists('wc_customer_bought_product')) { return [false, __('A logged-in customer is required.', 'king-addons')]; }
            $user = wp_get_current_user();
            foreach ($ids as $id) { $matches[] = (bool) wc_customer_bought_product($user->user_email, $user->ID, $id); }
        } else { return [false, __('Choose a WooCommerce condition.', 'king-addons')]; }
        $pass = ($r['product_match'] ?? 'any') === 'all' ? !in_array(false, $matches, true) : in_array(true, $matches, true);
        return [$pass, __('Product IDs:', 'king-addons') . ' ' . implode(', ', $ids)];
    }

    public static function compare($actual, string $expected, string $operator): bool
    {
        $empty = $actual === null || $actual === false || $actual === '' || $actual === [];
        if ($operator === 'empty') { return $empty; }
        if ($operator === 'not_empty') { return !$empty; }
        if (!is_scalar($actual) && !is_array($actual)) { return false; }
        if (is_array($actual)) {
            $actual = array_filter($actual, 'is_scalar');
            if ($operator === 'contains') { return in_array($expected, array_map('strval', $actual), true); }
            $actual = implode(', ', $actual);
        }
        $actual = (string) $actual;
        if ($operator === 'equals') { return $actual === $expected; }
        if ($operator === 'not_equals') { return $actual !== $expected; }
        return $operator === 'contains' && $expected !== '' && strpos($actual, $expected) !== false;
    }
}
