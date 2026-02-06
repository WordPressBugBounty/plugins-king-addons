<?php
/**
 * Activity Log database schema.
 *
 * @package King_Addons
 */

namespace King_Addons\Activity_Log;

if (!defined('ABSPATH')) {
    exit;
}

class Activity_Log_DB
{
    private const DB_VERSION = '1.0.0';

    public static function get_table(): string
    {
        global $wpdb;

        return $wpdb->prefix . 'kng_activity_log';
    }

    public static function maybe_create_table(): void
    {
        $installed = get_option('king_addons_activity_log_db_version');
        if ($installed === self::DB_VERSION) {
            return;
        }

        self::create_table();
        update_option('king_addons_activity_log_db_version', self::DB_VERSION);
    }

    private static function create_table(): void
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $table = self::get_table();
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            created_at DATETIME NOT NULL,
            event_key VARCHAR(191) NOT NULL,
            severity VARCHAR(20) NOT NULL DEFAULT 'info',
            user_id BIGINT UNSIGNED NULL,
            user_login VARCHAR(60) NULL,
            user_role VARCHAR(191) NULL,
            ip VARCHAR(191) NULL,
            user_agent TEXT NULL,
            object_type VARCHAR(50) NOT NULL DEFAULT '',
            object_id VARCHAR(191) NULL,
            object_title VARCHAR(191) NULL,
            source VARCHAR(191) NOT NULL DEFAULT '',
            context VARCHAR(50) NOT NULL DEFAULT '',
            message TEXT NULL,
            data LONGTEXT NULL,
            checksum VARCHAR(64) NULL,
            chain_prev_checksum VARCHAR(64) NULL,
            PRIMARY KEY  (id),
            KEY created_at (created_at),
            KEY event_key (event_key),
            KEY severity (severity),
            KEY user_id (user_id),
            KEY ip (ip),
            KEY object_type (object_type),
            KEY object_id (object_id),
            KEY source (source)
        ) {$charset};";

        dbDelta($sql);
    }
}
