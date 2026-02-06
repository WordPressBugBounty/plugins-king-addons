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

        $wishlist_id = $args['wishlist_id'] ? sanitize_title($args['wishlist_id']) : $this->service->get_active_wishlist_id();

        $in_list = $this->service->has_item($product_id, $variation_id, $wishlist_id);
        $label_default = $args['label_default'] ?? Wishlist_Settings::get('button_add_text');
        $label_added = $args['label_added'] ?? Wishlist_Settings::get('button_added_text');
        $label = $in_list ? $label_added : $label_default;
        $state_class = $in_list ? 'king-addons-wishlist-button--added' : 'king-addons-wishlist-button--default';

        $display_mode = $args['display_mode'] ?? Wishlist_Settings::get('button_display_mode', 'icon_text');
        $show_icon = in_array($display_mode, ['icon', 'icon_text'], true);
        $show_label = $display_mode === 'icon_text';
        $icon_class = $args['icon_class'] ?? Wishlist_Settings::get('icon_choice', 'eicon-heart');

        $classes = [
            'king-addons-wishlist-button',
            $state_class,
            sanitize_html_class($args['class']),
        ];

        $icon_html = $show_icon ? '<span class="king-addons-wishlist-button__icon" aria-hidden="true"><i class="' . esc_attr($icon_class) . '"></i></span>' : '';
        $label_html = $show_label ? '<span class="king-addons-wishlist-button__label">' . esc_html($label) . '</span>' : '';

        return '<button type="button" class="' . esc_attr(implode(' ', array_filter($classes))) . '" data-product-id="' . esc_attr($product_id) . '" data-variation-id="' . esc_attr($variation_id) . '" data-wishlist-id="' . esc_attr($wishlist_id) . '" data-state="' . esc_attr($in_list ? 'added' : 'default') . '" aria-pressed="' . ($in_list ? 'true' : 'false') . '">' . $icon_html . $label_html . '</button>';
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

        $wishlist_id = $args['wishlist_id'] ? sanitize_title($args['wishlist_id']) : $this->service->get_active_wishlist_id();
        $count = $this->service->get_count($wishlist_id);

        $classes = [
            'king-addons-wishlist-counter',
            isset($args['class']) ? sanitize_html_class($args['class']) : '',
        ];

        return '<span class="' . esc_attr(implode(' ', array_filter($classes))) . '" data-wishlist-id="' . esc_attr($wishlist_id) . '" data-count="' . esc_attr($count) . '">' . esc_html($count) . '</span>';
    }
}



