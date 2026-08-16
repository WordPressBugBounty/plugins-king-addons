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

    /**
     * Returns allowed HTML tags for grid titles.
     *
     * @return array<int, string> Allowed tag names.
     */
    public static function get_allowed_html_tags(): array
    {
        return ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'span', 'p'];
    }

    /**
     * Sanitizes an HTML tag name against the grid allowlist.
     *
     * @param mixed $value Raw tag name.
     * @return string Allowed tag name.
     */
    public static function sanitize_html_tag($value): string
    {
        $value = strtolower(sanitize_key((string) $value));

        return in_array($value, self::get_allowed_html_tags(), true) ? $value : 'h2';
    }

    /**
     * Sanitizes a CSS class token.
     *
     * @param mixed $value Raw class token.
     * @return string Safe class token.
     */
    public static function sanitize_class_token($value): string
    {
        return sanitize_html_class((string) $value);
    }

    /**
     * Sanitizes a query source / post type slug.
     *
     * @param mixed $value Raw query source.
     * @return string Allowed query source.
     */
    public static function sanitize_query_source($value): string
    {
        $value = sanitize_key((string) $value);
        $allowed = array_merge(
            ['post', 'page', 'product', 'attachment', 'current', 'related', 'manual', 'dynamic'],
            array_keys(get_post_types(['public' => true], 'names'))
        );

        return in_array($value, $allowed, true) ? $value : 'post';
    }

    /**
     * Returns sanitized grid settings from the current AJAX request.
     *
     * @return array<string, mixed> Sanitized settings array.
     */
    public static function get_posted_grid_settings(): array
    {
        $settings = isset($_POST['grid_settings']) ? wp_unslash($_POST['grid_settings']) : [];

        if (!is_array($settings)) {
            return [];
        }

        return self::sanitize_grid_settings($settings);
    }

    /**
     * Sanitizes grid settings that may be reflected into HTML.
     *
     * @param array<string, mixed> $settings Raw grid settings.
     * @return array<string, mixed> Sanitized grid settings.
     */
    public static function sanitize_grid_settings(array $settings): array
    {
        $class_keys = [
            'overlay_animation',
            'overlay_animation_size',
            'overlay_animation_timing',
            'image_effects',
            'image_effects_size',
            'image_effects_direction',
            'image_effects_animation_timing',
            'title_pointer',
            'title_pointer_animation',
            'tax1_pointer',
            'tax1_pointer_animation',
            'tax2_pointer',
            'tax2_pointer_animation',
            'read_more_animation',
            'layout_select',
            'pagination_type',
            'element_separator_style',
            'element_tax_style',
            'element_custom_field_style',
            'overlay_post_link',
        ];

        foreach ($class_keys as $key) {
            if (isset($settings[$key]) && is_scalar($settings[$key])) {
                $settings[$key] = self::sanitize_class_token($settings[$key]);
            }
        }

        if (isset($settings['overlay_animation'])) {
            $settings['overlay_animation'] = self::sanitize_animation($settings['overlay_animation']);
        }
        if (isset($settings['overlay_animation_size'])) {
            $settings['overlay_animation_size'] = self::sanitize_animation_size($settings['overlay_animation_size']);
        }
        if (isset($settings['overlay_animation_timing'])) {
            $settings['overlay_animation_timing'] = self::sanitize_animation_timing($settings['overlay_animation_timing']);
        }
        if (isset($settings['overlay_animation_tr'])) {
            $settings['overlay_animation_tr'] = self::sanitize_yes_no_switcher($settings['overlay_animation_tr']);
        }
        if (isset($settings['image_effects'])) {
            $settings['image_effects'] = self::sanitize_image_effect($settings['image_effects']);
        }
        if (isset($settings['image_effects_size'])) {
            $settings['image_effects_size'] = self::sanitize_image_effect_size($settings['image_effects_size']);
        }
        if (isset($settings['image_effects_direction'])) {
            $settings['image_effects_direction'] = self::sanitize_image_effect_direction($settings['image_effects_direction']);
        }
        if (isset($settings['image_effects_animation_timing'])) {
            $settings['image_effects_animation_timing'] = self::sanitize_animation_timing($settings['image_effects_animation_timing']);
        }
        if (isset($settings['element_title_tag'])) {
            $settings['element_title_tag'] = self::sanitize_html_tag($settings['element_title_tag']);
        }
        if (isset($settings['query_source'])) {
            $settings['query_source'] = self::sanitize_query_source($settings['query_source']);
        }
        if (isset($settings['query_tax_selection'])) {
            $settings['query_tax_selection'] = sanitize_key((string) $settings['query_tax_selection']);
        }
        if (isset($settings['order_posts'])) {
            $settings['order_posts'] = sanitize_key((string) $settings['order_posts']);
        }
        if (isset($settings['order_direction'])) {
            $settings['order_direction'] = in_array(strtoupper((string) $settings['order_direction']), ['ASC', 'DESC'], true)
                ? strtoupper((string) $settings['order_direction'])
                : 'DESC';
        }
        if (isset($settings['query_randomize'])) {
            $settings['query_randomize'] = sanitize_key((string) $settings['query_randomize']);
        }
        foreach (['query_offset', 'query_posts_per_page', 'query_slides_to_show'] as $int_key) {
            if (isset($settings[$int_key])) {
                $settings[$int_key] = absint($settings[$int_key]);
            }
        }
        if (isset($settings['query_author']) && is_array($settings['query_author'])) {
            $settings['query_author'] = array_map('absint', $settings['query_author']);
        }
        foreach ($settings as $key => $value) {
            if (!is_string($key) || !is_array($value)) {
                continue;
            }
            if (0 === strpos($key, 'query_taxonomy_') || 0 === strpos($key, 'query_exclude_')) {
                $settings[$key] = array_map('absint', $value);
            }
        }
        if (isset($settings['tax1_custom_color_switcher'])) {
            $settings['tax1_custom_color_switcher'] = self::sanitize_yes_no_switcher($settings['tax1_custom_color_switcher']);
        }
        if (isset($settings['tax1_custom_color_field_text'])) {
            $settings['tax1_custom_color_field_text'] = sanitize_key((string) $settings['tax1_custom_color_field_text']);
        }
        if (isset($settings['tax1_custom_color_field_bg'])) {
            $settings['tax1_custom_color_field_bg'] = sanitize_key((string) $settings['tax1_custom_color_field_bg']);
        }
        if (isset($settings['open_links_in_new_tab'])) {
            $settings['open_links_in_new_tab'] = self::sanitize_yes_no_switcher($settings['open_links_in_new_tab']);
        }
        if (isset($settings['secondary_img_on_hover'])) {
            $settings['secondary_img_on_hover'] = self::sanitize_yes_no_switcher($settings['secondary_img_on_hover']);
        }
        if (isset($settings['grid_lazy_loading'])) {
            $settings['grid_lazy_loading'] = self::sanitize_yes_no_switcher($settings['grid_lazy_loading']);
        }

        if (!empty($settings['overlay_image']) && is_array($settings['overlay_image'])) {
            $settings['overlay_image']['url'] = esc_url_raw($settings['overlay_image']['url'] ?? '');
            $settings['overlay_image']['alt'] = sanitize_text_field($settings['overlay_image']['alt'] ?? '');
        }

        if (!empty($settings['grid_elements']) && is_array($settings['grid_elements'])) {
            foreach ($settings['grid_elements'] as $index => $element) {
                if (!is_array($element)) {
                    unset($settings['grid_elements'][$index]);
                    continue;
                }
                $settings['grid_elements'][$index] = self::sanitize_grid_element($element);
            }
        }

        return $settings;
    }

    /**
     * Sanitizes one grid element repeater row.
     *
     * @param array<string, mixed> $element Raw element settings.
     * @return array<string, mixed> Sanitized element settings.
     */
    public static function sanitize_grid_element(array $element): array
    {
        foreach (['element_select', '_id', 'element_display', 'element_align_hr', 'element_align_vr', 'element_location'] as $key) {
            if (isset($element[$key]) && is_scalar($element[$key])) {
                $element[$key] = self::sanitize_class_token($element[$key]);
            }
        }

        if (isset($element['element_title_tag'])) {
            $element['element_title_tag'] = self::sanitize_html_tag($element['element_title_tag']);
        }
        if (isset($element['element_animation'])) {
            $element['element_animation'] = self::sanitize_animation($element['element_animation']);
        }
        if (isset($element['element_animation_size'])) {
            $element['element_animation_size'] = self::sanitize_animation_size($element['element_animation_size']);
        }
        if (isset($element['element_animation_timing'])) {
            $element['element_animation_timing'] = self::sanitize_animation_timing($element['element_animation_timing']);
        }
        if (isset($element['element_animation_tr'])) {
            $element['element_animation_tr'] = self::sanitize_yes_no_switcher($element['element_animation_tr']);
        }
        if (isset($element['element_separator_style'])) {
            $element['element_separator_style'] = self::sanitize_class_token($element['element_separator_style']);
        }
        if (isset($element['element_tax_style'])) {
            $element['element_tax_style'] = self::sanitize_class_token($element['element_tax_style']);
        }
        if (isset($element['element_read_more_text'])) {
            $element['element_read_more_text'] = sanitize_text_field((string) $element['element_read_more_text']);
        }
        if (isset($element['element_extra_text'])) {
            $element['element_extra_text'] = sanitize_text_field((string) $element['element_extra_text']);
        }
        if (isset($element['element_tax_sep'])) {
            $element['element_tax_sep'] = sanitize_text_field((string) $element['element_tax_sep']);
        }

        return $element;
    }

    /**
     * Sanitizes a CSS color value used in generated style tags.
     *
     * @param mixed $value Raw color value.
     * @return string Safe color string or an empty string.
     */
    public static function sanitize_css_color($value): string
    {
        $value = trim((string) $value);
        if ('' === $value) {
            return '';
        }

        if (preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6}|[A-Fa-f0-9]{8})$/', $value)) {
            return $value;
        }

        if (preg_match('/^(rgb|rgba|hsl|hsla)\(\s*[0-9.%,\s\/]+\s*\)$/i', $value)) {
            return $value;
        }

        if (preg_match('/^[a-zA-Z]+$/', $value)) {
            return sanitize_key($value);
        }

        return '';
    }
}
