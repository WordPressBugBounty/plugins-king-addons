<?php

namespace King_Addons\Wishlist;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Provides typed access to wishlist options and defaults.
 */
class Wishlist_Settings
{
    private const OPTION_KEY = 'king_addons_wishlist_settings';

    /**
     * Get merged wishlist settings with defaults.
     *
     * @return array<string, mixed> Settings.
     */
    public static function get_settings(): array
    {
        $saved = get_option(self::OPTION_KEY, []);
        return wp_parse_args($saved, self::defaults());
    }

    /**
     * Get a specific setting value.
     *
     * @param string $key Setting key.
     * @param mixed $default Default fallback.
     * @return mixed Setting value.
     */
    public static function get(string $key, $default = null)
    {
        $settings = self::get_settings();
        return $settings[$key] ?? $default;
    }

    /**
     * Persist wishlist settings.
     *
     * @param array<string, mixed> $settings Settings to store.
     * @return void
     */
    public static function save(array $settings): void
    {
        update_option(self::OPTION_KEY, wp_parse_args($settings, self::defaults()));
    }

    /**
     * Determine if wishlist is enabled.
     *
     * @return bool Whether wishlist is enabled.
     */
    public static function is_enabled(): bool
    {
        return (bool) self::get('enabled', true);
    }

    /**
     * Check if guests can use wishlist.
     *
     * @return bool Whether guests are allowed.
     */
    public static function guests_allowed(): bool
    {
        return (bool) self::get('allow_guests', true);
    }

    /**
     * Get selected wishlist page id.
     *
     * @return int|null Page id or null.
     */
    public static function wishlist_page_id(): ?int
    {
        $page_id = absint(self::get('wishlist_page_id', 0));
        return $page_id > 0 ? $page_id : null;
    }

    /**
     * Get default settings.
     *
     * @return array<string, mixed> Default settings.
     */
    public static function defaults(): array
    {
        return [
            'enabled' => true,
            'wishlist_page_id' => 0,
            'allow_guests' => true,
            'guest_block_text' => esc_html__('Please sign in to use wishlist.', 'king-addons'),
            'button_add_text' => esc_html__('Add to wishlist', 'king-addons'),
            'button_added_text' => esc_html__('In wishlist', 'king-addons'),
            'button_display_mode' => 'icon_text', // icon, icon_text
            'button_position' => 'after_add_to_cart', // before_add_to_cart|after_add_to_cart
            'show_in_archives' => true,
            'wishlist_columns' => ['image', 'title', 'price', 'stock', 'notes', 'add_to_cart', 'remove'],
            'cache_enabled' => false,
            'cache_ttl' => 0,
            'icon_choice' => 'eicon-heart',
        ];
    }

    /**
     * Get available column options.
     *
     * @return array<string, string> Column key => label pairs.
     */
    public static function get_column_options(): array
    {
        return [
            'image' => esc_html__('Image', 'king-addons'),
            'title' => esc_html__('Title', 'king-addons'),
            'price' => esc_html__('Price', 'king-addons'),
            'stock' => esc_html__('Stock', 'king-addons'),
            'notes' => esc_html__('Notes (Pro)', 'king-addons'),
            'add_to_cart' => esc_html__('Add to cart', 'king-addons'),
            'remove' => esc_html__('Remove', 'king-addons'),
        ];
    }
}



