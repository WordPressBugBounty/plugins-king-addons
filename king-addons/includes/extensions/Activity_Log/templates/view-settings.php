<?php
/**
 * Activity Log settings view.
 *
 * @package King_Addons
 */

if (!defined('ABSPATH')) {
    exit;
}

$exclude_roles = !empty($settings['exclude_roles']) ? implode(', ', $settings['exclude_roles']) : '';
$exclude_users = !empty($settings['exclude_user_ids']) ? implode(', ', $settings['exclude_user_ids']) : '';
$exclude_events = !empty($settings['exclude_event_keys']) ? implode(', ', $settings['exclude_event_keys']) : '';
?>

<form method="post" action="options.php">
    <?php settings_fields('king_addons_activity_log'); ?>

    <div class="ka-card">
        <div class="ka-card-header">
            <span class="dashicons dashicons-admin-settings purple"></span>
            <h2><?php esc_html_e('General', 'king-addons'); ?></h2>
        </div>
        <div class="ka-card-body">
            <div class="ka-row">
                <div class="ka-row-label"><?php esc_html_e('Enable logging', 'king-addons'); ?></div>
                <div class="ka-row-field">
                    <label class="ka-toggle">
                        <input type="checkbox" name="king_addons_activity_log_settings[enabled]" value="1" <?php checked(!empty($settings['enabled'])); ?> />
                        <span class="ka-toggle-slider"></span>
                        <span class="ka-toggle-label"><?php esc_html_e('Capture activity events', 'king-addons'); ?></span>
                    </label>
                </div>
            </div>
            <div class="ka-row">
                <div class="ka-row-label"><?php esc_html_e('Timezone display', 'king-addons'); ?></div>
                <div class="ka-row-field">
                    <select name="king_addons_activity_log_settings[timezone]">
                        <option value="site" <?php selected($settings['timezone'] ?? 'site', 'site'); ?>><?php esc_html_e('Site timezone', 'king-addons'); ?></option>
                        <option value="utc" <?php selected($settings['timezone'] ?? 'site', 'utc'); ?>><?php esc_html_e('UTC', 'king-addons'); ?></option>
                    </select>
                </div>
            </div>
            <div class="ka-row">
                <div class="ka-row-label"><?php esc_html_e('Rows per page', 'king-addons'); ?></div>
                <div class="ka-row-field">
                    <input type="number" name="king_addons_activity_log_settings[rows_per_page]" value="<?php echo esc_attr($settings['rows_per_page'] ?? 20); ?>" min="10" max="200">
                </div>
            </div>
        </div>
    </div>

    <div class="ka-card">
        <div class="ka-card-header">
            <span class="dashicons dashicons-filter purple"></span>
            <h2><?php esc_html_e('Logging Modules', 'king-addons'); ?></h2>
        </div>
        <div class="ka-card-body">
            <div class="kng-activity-module-grid">
                <label class="ka-toggle">
                    <input type="checkbox" name="king_addons_activity_log_settings[modules][auth]" value="1" <?php checked(!empty($settings['modules']['auth'])); ?> />
                    <span class="ka-toggle-slider"></span>
                    <span class="ka-toggle-label"><?php esc_html_e('Authentication', 'king-addons'); ?></span>
                </label>
                <label class="ka-toggle">
                    <input type="checkbox" name="king_addons_activity_log_settings[modules][content]" value="1" <?php checked(!empty($settings['modules']['content'])); ?> />
                    <span class="ka-toggle-slider"></span>
                    <span class="ka-toggle-label"><?php esc_html_e('Content', 'king-addons'); ?></span>
                </label>
                <label class="ka-toggle">
                    <input type="checkbox" name="king_addons_activity_log_settings[modules][users]" value="1" <?php checked(!empty($settings['modules']['users'])); ?> />
                    <span class="ka-toggle-slider"></span>
                    <span class="ka-toggle-label"><?php esc_html_e('Users', 'king-addons'); ?></span>
                </label>
                <label class="ka-toggle">
                    <input type="checkbox" name="king_addons_activity_log_settings[modules][plugins_themes]" value="1" <?php checked(!empty($settings['modules']['plugins_themes'])); ?> />
                    <span class="ka-toggle-slider"></span>
                    <span class="ka-toggle-label"><?php esc_html_e('Plugins & Themes', 'king-addons'); ?></span>
                </label>

                <?php if ($is_pro) : ?>
                    <label class="ka-toggle">
                        <input type="checkbox" name="king_addons_activity_log_settings[modules][settings]" value="1" <?php checked(!empty($settings['modules']['settings'])); ?> />
                        <span class="ka-toggle-slider"></span>
                        <span class="ka-toggle-label"><?php esc_html_e('Settings Changes', 'king-addons'); ?></span>
                    </label>
                    <label class="ka-toggle">
                        <input type="checkbox" name="king_addons_activity_log_settings[modules][woocommerce]" value="1" <?php checked(!empty($settings['modules']['woocommerce'])); ?> />
                        <span class="ka-toggle-slider"></span>
                        <span class="ka-toggle-label"><?php esc_html_e('WooCommerce', 'king-addons'); ?></span>
                    </label>
                    <label class="ka-toggle">
                        <input type="checkbox" name="king_addons_activity_log_settings[modules][king_addons]" value="1" <?php checked(!empty($settings['modules']['king_addons'])); ?> />
                        <span class="ka-toggle-slider"></span>
                        <span class="ka-toggle-label"><?php esc_html_e('King Addons Events', 'king-addons'); ?></span>
                    </label>
                <?php else : ?>
                    <label class="ka-toggle kng-toggle-pro">
                        <input type="checkbox" disabled />
                        <span class="ka-toggle-slider"></span>
                        <span class="ka-toggle-label"><?php esc_html_e('Settings Changes', 'king-addons'); ?></span>
                        <span class="ka-pro-badge">PRO</span>
                    </label>
                    <label class="ka-toggle kng-toggle-pro">
                        <input type="checkbox" disabled />
                        <span class="ka-toggle-slider"></span>
                        <span class="ka-toggle-label"><?php esc_html_e('WooCommerce', 'king-addons'); ?></span>
                        <span class="ka-pro-badge">PRO</span>
                    </label>
                    <label class="ka-toggle kng-toggle-pro">
                        <input type="checkbox" disabled />
                        <span class="ka-toggle-slider"></span>
                        <span class="ka-toggle-label"><?php esc_html_e('King Addons Events', 'king-addons'); ?></span>
                        <span class="ka-pro-badge">PRO</span>
                    </label>
                <?php endif; ?>
            </div>

            <div class="kng-activity-exclusions">
                <div class="kng-field">
                    <label><?php esc_html_e('Exclude roles', 'king-addons'); ?></label>
                    <input type="text" name="king_addons_activity_log_settings[exclude_roles]" value="<?php echo esc_attr($exclude_roles); ?>" placeholder="subscriber, editor">
                    <small><?php esc_html_e('Comma-separated role slugs.', 'king-addons'); ?></small>
                </div>
                <div class="kng-field">
                    <label><?php esc_html_e('Exclude user IDs', 'king-addons'); ?></label>
                    <input type="text" name="king_addons_activity_log_settings[exclude_user_ids]" value="<?php echo esc_attr($exclude_users); ?>" placeholder="12, 54, 78">
                </div>
                <div class="kng-field">
                    <label><?php esc_html_e('Exclude event keys', 'king-addons'); ?></label>
                    <input type="text" name="king_addons_activity_log_settings[exclude_event_keys]" value="<?php echo esc_attr($exclude_events); ?>" placeholder="auth.login.success, plugin.updated">
                </div>
            </div>
        </div>
    </div>

    <div class="ka-card">
        <div class="ka-card-header">
            <span class="dashicons dashicons-shield purple"></span>
            <h2><?php esc_html_e('Privacy', 'king-addons'); ?></h2>
        </div>
        <div class="ka-card-body">
            <div class="ka-row">
                <div class="ka-row-label"><?php esc_html_e('IP storage', 'king-addons'); ?></div>
                <div class="ka-row-field">
                    <select name="king_addons_activity_log_settings[ip_storage]">
                        <option value="full" <?php selected($settings['ip_storage'] ?? 'full', 'full'); ?>><?php esc_html_e('Full IP', 'king-addons'); ?></option>
                        <option value="masked" <?php selected($settings['ip_storage'] ?? 'full', 'masked'); ?>><?php esc_html_e('Masked IP', 'king-addons'); ?></option>
                        <option value="hashed" <?php selected($settings['ip_storage'] ?? 'full', 'hashed'); ?>><?php esc_html_e('Hashed only', 'king-addons'); ?></option>
                    </select>
                </div>
            </div>
            <div class="ka-row">
                <div class="ka-row-label"><?php esc_html_e('Store user agent', 'king-addons'); ?></div>
                <div class="ka-row-field">
                    <label class="ka-toggle">
                        <input type="checkbox" name="king_addons_activity_log_settings[store_user_agent]" value="1" <?php checked(!empty($settings['store_user_agent'])); ?> />
                        <span class="ka-toggle-slider"></span>
                        <span class="ka-toggle-label"><?php esc_html_e('Save browser and device data', 'king-addons'); ?></span>
                    </label>
                </div>
            </div>
            <div class="ka-row">
                <div class="ka-row-label"><?php esc_html_e('Trust proxy headers', 'king-addons'); ?></div>
                <div class="ka-row-field">
                    <label class="ka-toggle">
                        <input type="checkbox" name="king_addons_activity_log_settings[trust_proxy_headers]" value="1" <?php checked(!empty($settings['trust_proxy_headers'])); ?> />
                        <span class="ka-toggle-slider"></span>
                        <span class="ka-toggle-label"><?php esc_html_e('Use X-Forwarded-For for IP detection', 'king-addons'); ?></span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="ka-card">
        <div class="ka-card-header">
            <span class="dashicons dashicons-clock purple"></span>
            <h2><?php esc_html_e('Retention', 'king-addons'); ?></h2>
            <?php if (!$is_pro) : ?><span class="ka-pro-badge">PRO</span><?php endif; ?>
        </div>
        <div class="ka-card-body">
            <div class="ka-row">
                <div class="ka-row-label"><?php esc_html_e('Retention days', 'king-addons'); ?></div>
                <div class="ka-row-field">
                    <input type="number" name="king_addons_activity_log_settings[retention_days]" value="<?php echo esc_attr($settings['retention_days'] ?? 14); ?>" min="1" max="<?php echo $is_pro ? '365' : '14'; ?>" <?php disabled(!$is_pro); ?>>
                    <?php if (!$is_pro) : ?>
                        <small><?php esc_html_e('Free plan stores logs for 14 days.', 'king-addons'); ?></small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="ka-card">
        <div class="ka-card-header">
            <span class="dashicons dashicons-lock pink"></span>
            <h2><?php esc_html_e('Access', 'king-addons'); ?></h2>
            <?php if (!$is_pro) : ?><span class="ka-pro-badge">PRO</span><?php endif; ?>
        </div>
        <div class="ka-card-body">
            <div class="ka-row">
                <div class="ka-row-label"><?php esc_html_e('Who can view logs', 'king-addons'); ?></div>
                <div class="ka-row-field">
                    <?php if ($is_pro) :
                        $view_logs_cap = $settings['view_logs_capability'] ?? 'manage_options';
                    ?>
                        <select name="king_addons_activity_log_settings[view_logs_capability]">
                            <option value="manage_options" <?php selected($view_logs_cap, 'manage_options'); ?>><?php esc_html_e('Administrators only', 'king-addons'); ?></option>
                            <option value="edit_pages" <?php selected($view_logs_cap, 'edit_pages'); ?>><?php esc_html_e('Editors and Administrators', 'king-addons'); ?></option>
                            <option value="edit_posts" <?php selected($view_logs_cap, 'edit_posts'); ?>><?php esc_html_e('Authors, Editors and Administrators', 'king-addons'); ?></option>
                            <option value="read" <?php selected($view_logs_cap, 'read'); ?>><?php esc_html_e('Any logged-in user (wp-admin access)', 'king-addons'); ?></option>
                        </select>
                        <small><?php esc_html_e('Pro: adds a logs-only menu entry for the selected role level.', 'king-addons'); ?></small>
                    <?php else : ?>
                        <select disabled>
                            <option><?php esc_html_e('Administrators only', 'king-addons'); ?></option>
                        </select>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="ka-card">
        <div class="ka-submit">
            <button type="submit" class="ka-btn ka-btn-primary">
                <?php esc_html_e('Save Settings', 'king-addons'); ?>
            </button>
        </div>
    </div>
</form>
