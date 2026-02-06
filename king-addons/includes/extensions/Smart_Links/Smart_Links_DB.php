<?php
/**
 * Smart Links database schema.
 *
 * @package King_Addons
 */

namespace King_Addons\Smart_Links;

if (!defined('ABSPATH')) {
    exit;
}

class Smart_Links_DB
{
    private const DB_VERSION = '1.0.0';

    public static function get_clicks_table(): string
    {
        global $wpdb;

        return $wpdb->prefix . 'kng_link_clicks';
    }

    public static function get_daily_table(): string
    {
        global $wpdb;

        return $wpdb->prefix . 'kng_link_clicks_daily';
    }

    public static function maybe_create_tables(): void
    {
        $installed_version = get_option('king_addons_smart_links_db_version');
        if ($installed_version === self::DB_VERSION) {
            return;
        }

        self::create_tables();
        update_option('king_addons_smart_links_db_version', self::DB_VERSION);
    }

    private static function create_tables(): void
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();
        $clicks_table = self::get_clicks_table();
        $daily_table = self::get_daily_table();

        $sql_clicks = "CREATE TABLE {$clicks_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            link_id BIGINT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            ip_hash VARCHAR(64) NOT NULL DEFAULT '',
            user_agent_hash VARCHAR(64) NOT NULL DEFAULT '',
            unique_hash VARCHAR(64) NOT NULL DEFAULT '',
            referrer TEXT NULL,
            landing_url TEXT NULL,
            device_type VARCHAR(20) NOT NULL DEFAULT '',
            browser VARCHAR(50) NOT NULL DEFAULT '',
            os VARCHAR(50) NOT NULL DEFAULT '',
            country CHAR(2) NOT NULL DEFAULT '',
            PRIMARY KEY  (id),
            KEY link_id (link_id),
            KEY created_at (created_at),
            KEY unique_hash (unique_hash)
        ) {$charset_collate};";

        $sql_daily = "CREATE TABLE {$daily_table} (
            link_id BIGINT UNSIGNED NOT NULL,
            date DATE NOT NULL,
            clicks BIGINT UNSIGNED NOT NULL DEFAULT 0,
            unique_clicks BIGINT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY  (link_id, date),
            KEY date (date)
        ) {$charset_collate};";

        dbDelta($sql_clicks);
        dbDelta($sql_daily);
    }
}
