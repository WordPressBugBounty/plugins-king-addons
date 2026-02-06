<?php
/**
 * Event Types and severity constants for Activity Log.
 *
 * @package King_Addons
 */

namespace King_Addons;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Event type definitions, severity levels, and helper methods.
 */
final class Activity_Log_Event_Types
{
    // =========================================================================
    // Severity Levels
    // =========================================================================
    public const SEVERITY_INFO = 'info';
    public const SEVERITY_NOTICE = 'notice';
    public const SEVERITY_WARNING = 'warning';
    public const SEVERITY_CRITICAL = 'critical';

    // =========================================================================
    // Auth Events
    // =========================================================================
    public const AUTH_LOGIN_SUCCESS = 'auth.login.success';
    public const AUTH_LOGIN_FAILED = 'auth.login.failed';
    public const AUTH_LOGOUT = 'auth.logout';

    // =========================================================================
    // User Events
    // =========================================================================
    public const USER_CREATED = 'user.created';
    public const USER_UPDATED = 'user.updated';
    public const USER_DELETED = 'user.deleted';
    public const USER_ROLE_CHANGED = 'user.role_changed';

    // =========================================================================
    // Content Events
    // =========================================================================
    public const CONTENT_CREATED = 'content.created';
    public const CONTENT_UPDATED = 'content.updated';
    public const CONTENT_TRASHED = 'content.trashed';
    public const CONTENT_RESTORED = 'content.restored';
    public const CONTENT_DELETED = 'content.deleted';

    // =========================================================================
    // Plugin Events
    // =========================================================================
    public const PLUGIN_ACTIVATED = 'plugin.activated';
    public const PLUGIN_DEACTIVATED = 'plugin.deactivated';
    public const PLUGIN_UPDATED = 'plugin.updated';

    // =========================================================================
    // Theme Events
    // =========================================================================
    public const THEME_SWITCHED = 'theme.switched';

    // =========================================================================
    // Pro-only Events (placeholders)
    // =========================================================================
    public const SETTINGS_UPDATED = 'settings.updated';
    public const MEDIA_UPLOADED = 'media.uploaded';
    public const MEDIA_DELETED = 'media.deleted';
    public const WOO_ORDER_CREATED = 'woocommerce.order.created';
    public const WOO_ORDER_UPDATED = 'woocommerce.order.updated';
    public const KNG_MODULE_ENABLED = 'kng.module.enabled';
    public const KNG_MODULE_DISABLED = 'kng.module.disabled';

    /**
     * Get all Free event types.
     *
     * @return array<string>
     */
    public static function get_free_events(): array
    {
        return [
            self::AUTH_LOGIN_SUCCESS,
            self::AUTH_LOGIN_FAILED,
            self::AUTH_LOGOUT,
            self::USER_CREATED,
            self::USER_UPDATED,
            self::USER_DELETED,
            self::USER_ROLE_CHANGED,
            self::CONTENT_CREATED,
            self::CONTENT_UPDATED,
            self::CONTENT_TRASHED,
            self::CONTENT_RESTORED,
            self::CONTENT_DELETED,
            self::PLUGIN_ACTIVATED,
            self::PLUGIN_DEACTIVATED,
            self::PLUGIN_UPDATED,
            self::THEME_SWITCHED,
        ];
    }

    /**
     * Get human-readable labels for event types.
     *
     * @return array<string, string>
     */
    public static function get_labels(): array
    {
        return [
            self::AUTH_LOGIN_SUCCESS => __('Login Success', 'king-addons'),
            self::AUTH_LOGIN_FAILED => __('Login Failed', 'king-addons'),
            self::AUTH_LOGOUT => __('Logout', 'king-addons'),
            self::USER_CREATED => __('User Created', 'king-addons'),
            self::USER_UPDATED => __('User Updated', 'king-addons'),
            self::USER_DELETED => __('User Deleted', 'king-addons'),
            self::USER_ROLE_CHANGED => __('User Role Changed', 'king-addons'),
            self::CONTENT_CREATED => __('Content Created', 'king-addons'),
            self::CONTENT_UPDATED => __('Content Updated', 'king-addons'),
            self::CONTENT_TRASHED => __('Content Trashed', 'king-addons'),
            self::CONTENT_RESTORED => __('Content Restored', 'king-addons'),
            self::CONTENT_DELETED => __('Content Deleted', 'king-addons'),
            self::PLUGIN_ACTIVATED => __('Plugin Activated', 'king-addons'),
            self::PLUGIN_DEACTIVATED => __('Plugin Deactivated', 'king-addons'),
            self::PLUGIN_UPDATED => __('Plugin Updated', 'king-addons'),
            self::THEME_SWITCHED => __('Theme Switched', 'king-addons'),
            self::SETTINGS_UPDATED => __('Settings Updated', 'king-addons'),
            self::MEDIA_UPLOADED => __('Media Uploaded', 'king-addons'),
            self::MEDIA_DELETED => __('Media Deleted', 'king-addons'),
            self::WOO_ORDER_CREATED => __('Order Created', 'king-addons'),
            self::WOO_ORDER_UPDATED => __('Order Updated', 'king-addons'),
            self::KNG_MODULE_ENABLED => __('Module Enabled', 'king-addons'),
            self::KNG_MODULE_DISABLED => __('Module Disabled', 'king-addons'),
        ];
    }

    /**
     * Get label for a single event type.
     *
     * @param string $event_key Event key.
     * @return string
     */
    public static function get_label(string $event_key): string
    {
        $labels = self::get_labels();
        return $labels[$event_key] ?? $event_key;
    }

    /**
     * Get default severity for an event type.
     *
     * @param string $event_key Event key.
     * @return string
     */
    public static function get_default_severity(string $event_key): string
    {
        $critical = [
            self::AUTH_LOGIN_FAILED,
            self::USER_ROLE_CHANGED,
            self::USER_DELETED,
            self::CONTENT_DELETED,
        ];

        $warning = [
            self::PLUGIN_ACTIVATED,
            self::PLUGIN_DEACTIVATED,
            self::THEME_SWITCHED,
        ];

        $notice = [
            self::PLUGIN_UPDATED,
            self::CONTENT_TRASHED,
            self::SETTINGS_UPDATED,
        ];

        if (in_array($event_key, $critical, true)) {
            return self::SEVERITY_CRITICAL;
        }

        if (in_array($event_key, $warning, true)) {
            return self::SEVERITY_WARNING;
        }

        if (in_array($event_key, $notice, true)) {
            return self::SEVERITY_NOTICE;
        }

        return self::SEVERITY_INFO;
    }

    /**
     * Get severity badge color class.
     *
     * @param string $severity Severity level.
     * @return string CSS class suffix.
     */
    public static function get_severity_class(string $severity): string
    {
        $map = [
            self::SEVERITY_INFO => 'info',
            self::SEVERITY_NOTICE => 'notice',
            self::SEVERITY_WARNING => 'warning',
            self::SEVERITY_CRITICAL => 'critical',
        ];

        return $map[$severity] ?? 'info';
    }

    /**
     * Get severity labels for filter dropdown.
     *
     * @return array<string, string>
     */
    public static function get_severity_labels(): array
    {
        return [
            self::SEVERITY_INFO => __('Info', 'king-addons'),
            self::SEVERITY_NOTICE => __('Notice', 'king-addons'),
            self::SEVERITY_WARNING => __('Warning', 'king-addons'),
            self::SEVERITY_CRITICAL => __('Critical', 'king-addons'),
        ];
    }

    /**
     * Get event category from event key.
     *
     * @param string $event_key Event key.
     * @return string Category name.
     */
    public static function get_category(string $event_key): string
    {
        $parts = explode('.', $event_key);
        return $parts[0] ?? 'other';
    }

    /**
     * Get category labels for grouping.
     *
     * @return array<string, string>
     */
    public static function get_category_labels(): array
    {
        return [
            'auth' => __('Authentication', 'king-addons'),
            'user' => __('Users', 'king-addons'),
            'content' => __('Content', 'king-addons'),
            'plugin' => __('Plugins', 'king-addons'),
            'theme' => __('Themes', 'king-addons'),
            'settings' => __('Settings', 'king-addons'),
            'media' => __('Media', 'king-addons'),
            'woocommerce' => __('WooCommerce', 'king-addons'),
            'kng' => __('King Addons', 'king-addons'),
        ];
    }
}
