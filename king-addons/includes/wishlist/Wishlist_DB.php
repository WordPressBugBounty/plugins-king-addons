<?php

namespace King_Addons\Wishlist;

use wpdb;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles wishlist database schema creation and upgrades.
 */
class Wishlist_DB
{
    private const DB_VERSION = '1.1.0';

    /**
     * Get wishlist items table name with prefix.
     *
     * @return string Table name.
     */
    public static function get_items_table(): string
    {
        global $wpdb;

        return $wpdb->prefix . 'ka_wishlist_items';
    }

    /**
     * Get wishlists table name with prefix.
     *
     * @return string Table name.
     */
    public static function get_lists_table(): string
    {
        global $wpdb;

        return $wpdb->prefix . 'ka_wishlists';
    }

    /**
     * Get conversions table name with prefix.
     *
     * @return string Table name.
     */
    public static function get_conversions_table(): string
    {
        global $wpdb;

        return $wpdb->prefix . 'ka_wishlist_conversions';
    }

    /**
     * Create or update wishlist tables when needed.
     *
     * @return void
     */
    public static function maybe_create_tables(): void
    {
        $installed_version = get_option('king_addons_wishlist_db_version');
        if ($installed_version === self::DB_VERSION) {
            return;
        }

        self::create_tables();
        update_option('king_addons_wishlist_db_version', self::DB_VERSION);
    }

    /**
     * Run dbDelta for all wishlist tables.
     *
     * @return void
     */
    private static function create_tables(): void
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();
        $items_table = self::get_items_table();
        $lists_table = self::get_lists_table();
        $conversions_table = self::get_conversions_table();

        $sql_items = "CREATE TABLE {$items_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            session_key VARCHAR(64) NOT NULL DEFAULT '',
            wishlist_id VARCHAR(64) NOT NULL DEFAULT '',
            product_id BIGINT UNSIGNED NOT NULL,
            variation_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            qty INT UNSIGNED NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            meta LONGTEXT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY session_key (session_key),
            KEY wishlist_id (wishlist_id),
            KEY product_id (product_id),
            KEY variation_id (variation_id),
            KEY created_at (created_at),
            UNIQUE KEY unique_item (wishlist_id, user_id, session_key, product_id, variation_id)
        ) {$charset_collate};";

        $sql_lists = "CREATE TABLE {$lists_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            session_key VARCHAR(64) NOT NULL DEFAULT '',
            title VARCHAR(200) NOT NULL DEFAULT '',
            slug VARCHAR(200) NOT NULL DEFAULT '',
            visibility VARCHAR(20) NOT NULL DEFAULT 'private',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY session_key (session_key),
            KEY slug (slug)
        ) {$charset_collate};";

        $sql_conversions = "CREATE TABLE {$conversions_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            product_id BIGINT UNSIGNED NOT NULL,
            variation_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            order_id BIGINT UNSIGNED NOT NULL,
            order_item_qty INT UNSIGNED NOT NULL DEFAULT 1,
            order_item_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            converted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY product_id (product_id),
            KEY order_id (order_id),
            KEY converted_at (converted_at),
            UNIQUE KEY unique_conversion (user_id, product_id, variation_id, order_id)
        ) {$charset_collate};";

        dbDelta($sql_items);
        dbDelta($sql_lists);
        dbDelta($sql_conversions);
    }
}



