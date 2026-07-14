<?php

namespace King_Addons;

use King_Addons\Animations\Animations;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Sanitizes untrusted grid settings received through AJAX handlers.
 */
class Grid_Ajax_Security
{
    /**
     * Returns allowed animation size values.
     *
     * @return array<int, string> Allowed size slugs.
     */
    public static function get_allowed_animation_sizes(): array
    {
        return ['small', 'medium', 'large'];
    }

    /**
     * Returns allowed image effect slugs.
     *
     * @return array<int, string> Allowed effect slugs.
     */
    public static function get_allowed_image_effects(): array
    {
        return ['none', 'pro-zi', 'pro-zo', 'grayscale-in', 'pro-go', 'blur-in', 'pro-bo', 'slide'];
    }

    /**
     * Returns allowed image effect direction values.
     *
     * @return array<int, string> Allowed direction slugs.
     */
    public static function get_allowed_image_effect_directions(): array
    {
        return ['top', 'right', 'bottom', 'left'];
    }

    /**
     * Sanitizes an animation slug against the widget allowlist.
     *
     * @param mixed $value Raw animation value.
     * @return string Sanitized animation slug.
     */
    public static function sanitize_animation($value): string
    {
        $value = sanitize_key((string) $value);

        return in_array($value, Animations::get_animation_slugs(), true) ? $value : 'none';
    }

    /**
     * Sanitizes an animation size slug.
     *
     * @param mixed $value Raw animation size value.
     * @return string Sanitized animation size slug.
     */
    public static function sanitize_animation_size($value): string
    {
        $value = sanitize_key((string) $value);

        return in_array($value, self::get_allowed_animation_sizes(), true) ? $value : 'large';
    }

    /**
     * Sanitizes an animation timing slug.
     *
     * @param mixed $value Raw animation timing value.
     * @return string Sanitized animation timing slug.
     */
    public static function sanitize_animation_timing($value): string
    {
        $value = sanitize_key((string) $value);
        $allowed = array_keys(Core::getAnimationTimings());

        return in_array($value, $allowed, true) ? $value : 'ease-default';
    }

    /**
     * Sanitizes an image effect slug.
     *
     * @param mixed $value Raw image effect value.
     * @return string Sanitized image effect slug.
     */
    public static function sanitize_image_effect($value): string
    {
        $value = sanitize_key((string) $value);

        if (!in_array($value, self::get_allowed_image_effects(), true)) {
            return 'none';
        }

        if (
            !king_addons_freemius()->can_use_premium_code__premium_only()
            && in_array($value, ['pro-zi', 'pro-zo', 'pro-go', 'pro-bo'], true)
        ) {
            return 'none';
        }

        return $value;
    }

    /**
     * Sanitizes an image effect size slug.
     *
     * @param mixed $value Raw image effect size value.
     * @return string Sanitized image effect size slug.
     */
    public static function sanitize_image_effect_size($value): string
    {
        $value = sanitize_key((string) $value);

        return in_array($value, self::get_allowed_animation_sizes(), true) ? $value : 'medium';
    }

    /**
     * Sanitizes an image effect direction slug.
     *
     * @param mixed $value Raw image effect direction value.
     * @return string Sanitized image effect direction slug.
     */
    public static function sanitize_image_effect_direction($value): string
    {
        $value = sanitize_key((string) $value);

        return in_array($value, self::get_allowed_image_effect_directions(), true) ? $value : 'bottom';
    }

    /**
     * Sanitizes a yes/no switcher value.
     *
     * @param mixed $value Raw switcher value.
     * @return string Either "yes" or an empty string.
     */
    public static function sanitize_yes_no_switcher($value): string
    {
        return 'yes' === $value ? 'yes' : '';
    }
}
