<?php

namespace King_Addons\Wishlist;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles HTML rendering for wishlist components.
 */
class Wishlist_Renderer
{
    private Wishlist_Service $service;

    /**
     * Setup renderer with service dependency.
     *
     * @param Wishlist_Service $service Wishlist service instance.
     */
    public function __construct(Wishlist_Service $service)
    {
        $this->service = $service;
    }

    /**
     * Render wishlist button markup.
     *
     * @param array<string, mixed> $args Rendering arguments.
     * @return string Rendered HTML.
     */
    public function render_button(array $args = []): string
    {
        if (!Wishlist_Settings::is_enabled()) {
            return '';
        }

        if (!Wishlist_Settings::guests_allowed() && !is_user_logged_in()) {
            $message = Wishlist_Settings::get('guest_block_text');
            return '<div class="king-addons-wishlist__notice">' . esc_html($message) . '</div>';
        }

        $defaults = [
            'product_id' => get_the_ID(),
            'variation_id' => 0,
            'wishlist_id' => null,
            'class' => '',
        ];
        $args = wp_parse_args($args, $defaults);

        $product_id = absint($args['product_id']);
        $variation_id = absint($args['variation_id']);
        if ($product_id <= 0) {
            return '';
        }

        if (function_exists('wc_get_product') && !wc_get_product($product_id)) {
            return '';
        }

        $wishlist_id = !empty($args['wishlist_id']) ? sanitize_title((string) $args['wishlist_id']) : $this->service->get_active_wishlist_id();

        $in_list = $this->service->has_item($product_id, $variation_id, $wishlist_id);
        $label_default = $args['label_default'] ?? Wishlist_Settings::get('button_add_text');
        $label_added = $args['label_added'] ?? Wishlist_Settings::get('button_added_text');
        $label = $in_list ? $label_added : $label_default;
        $state_class = $in_list ? 'king-addons-wishlist-button--added' : 'king-addons-wishlist-button--default';

        $display_mode = $args['display_mode'] ?? Wishlist_Settings::get('button_display_mode', 'icon_text');
        $show_icon = in_array($display_mode, ['icon', 'icon_text'], true);
        $show_label = $display_mode === 'icon_text';
        $icon_class = (string) ($args['icon_class'] ?? Wishlist_Settings::get('icon_choice', 'fas fa-heart'));

        $classes = [
            'king-addons-wishlist-button',
            $state_class,
            sanitize_html_class((string) $args['class']),
        ];

        $icon_html = $show_icon ? $this->render_icon_html($icon_class) : '';
        $label_html = $show_label ? '<span class="king-addons-wishlist-button__label">' . esc_html($label) . '</span>' : '';

        return '<button type="button" class="' . esc_attr(implode(' ', array_filter($classes))) . '" data-product-id="' . esc_attr((string) $product_id) . '" data-variation-id="' . esc_attr((string) $variation_id) . '" data-wishlist-id="' . esc_attr((string) $wishlist_id) . '" data-state="' . esc_attr($in_list ? 'added' : 'default') . '" data-display-mode="' . esc_attr((string) $display_mode) . '" data-label-default="' . esc_attr((string) $label_default) . '" data-label-added="' . esc_attr((string) $label_added) . '" aria-pressed="' . ($in_list ? 'true' : 'false') . '" aria-label="' . esc_attr((string) $label) . '">' . $icon_html . $label_html . '</button>';
    }

    /**
     * Built-in heart SVG used by the button and floating icon.
     *
     * @param string $class Extra SVG class.
     * @return string SVG markup.
     */
    public static function heart_svg(string $class = 'king-addons-wishlist-button__heart'): string
    {
        return '<svg class="' . esc_attr($class) . '" viewBox="0 0 24 24" width="16" height="16" focusable="false" aria-hidden="true">'
            . '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78z"/>'
            . '</svg>';
    }

    /**
     * Render the button icon: built-in SVG heart, or a custom font class.
     *
     * @param string $icon_class Icon class from settings/widget.
     * @return string Icon markup.
     */
    private function render_icon_html(string $icon_class): string
    {
        if ($this->uses_builtin_heart($icon_class)) {
            return '<span class="king-addons-wishlist-button__icon" aria-hidden="true">' . self::heart_svg() . '</span>';
        }

        $classes = preg_split('/\s+/', trim($icon_class), -1, PREG_SPLIT_NO_EMPTY);
        $safe = implode(' ', array_map('sanitize_html_class', is_array($classes) ? $classes : []));
        if ($safe === '') {
            return $this->render_icon_html('fas fa-heart');
        }

        return '<span class="king-addons-wishlist-button__icon" aria-hidden="true"><i class="' . esc_attr($safe) . '"></i></span>';
    }

    /**
     * Default heart classes use the built-in SVG instead of a webfont.
     *
     * @param string $icon_class Icon class from settings/widget.
     * @return bool Whether to render the SVG heart.
     */
    private function uses_builtin_heart(string $icon_class): bool
    {
        $normalized = strtolower(trim(preg_replace('/\s+/', ' ', $icon_class) ?? $icon_class));

        return in_array($normalized, [
            '',
            'fas fa-heart',
            'fa fa-heart',
            'far fa-heart',
            'fa-solid fa-heart',
            'fa-regular fa-heart',
            'eicon-heart',
            'eicon-heart-o',
        ], true);
    }

    /**
     * Sanitize renderer HTML for echo contexts that still want kses.
     *
     * @param string $html Button or similar markup.
     * @return string Sanitized HTML.
     */
    public static function kses(string $html): string
    {
        $allowed = [
            'button' => [
                'type' => true,
                'class' => true,
                'disabled' => true,
                'aria-pressed' => true,
                'aria-label' => true,
            ],
            'span' => [
                'class' => true,
                'aria-hidden' => true,
            ],
            'i' => [
                'class' => true,
                'aria-hidden' => true,
            ],
            'svg' => [
                'class' => true,
                'viewbox' => true,
                'width' => true,
                'height' => true,
                'focusable' => true,
                'aria-hidden' => true,
            ],
            'path' => [
                'd' => true,
            ],
        ];

        foreach ($allowed as $tag => $attrs) {
            $allowed[$tag] = array_merge($attrs, [
                'class' => true,
                'aria-hidden' => true,
                'data-*' => true,
            ]);
        }

        return wp_kses($html, $allowed);
    }

    /**
     * Render wishlist counter element.
     *
     * @param array<string, mixed> $args Rendering arguments.
     * @return string Rendered HTML.
     */
    public function render_counter(array $args = []): string
    {
        if (!Wishlist_Settings::is_enabled()) {
            return '';
        }

        $wishlist_id = !empty($args['wishlist_id']) ? sanitize_title((string) $args['wishlist_id']) : $this->service->get_active_wishlist_id();
        $count = $this->service->get_count($wishlist_id);

        $classes = [
            'king-addons-wishlist-counter',
            isset($args['class']) ? sanitize_html_class($args['class']) : '',
        ];

        return '<span class="' . esc_attr(implode(' ', array_filter($classes))) . '" data-wishlist-id="' . esc_attr($wishlist_id) . '" data-count="' . esc_attr($count) . '">' . esc_html($count) . '</span>';
    }
}



