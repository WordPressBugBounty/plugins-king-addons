<?php
/**
 * Activity Log - Settings Tab.
 *
 * @package King_Addons
 */

if (!defined('ABSPATH')) {
    exit;
}

$activity_log = \King_Addons\Activity_Log::instance();
$options = $activity_log->get_options();
$is_pro = $activity_log->is_pro();

// Get all roles
$roles = wp_roles()->get_names();
?>

<div class="ka-al-settings-form" id="ka-al-settings-form">
    <!-- General Section -->
    <div class="ka-al-card">
        <div class="ka-al-card-header">
            <h3><?php esc_html_e('General', 'king-addons'); ?></h3>
        </div>
        <div class="ka-al-card-body">
            <div class="ka-al-field">
                <label class="ka-al-toggle">
                    <input type="checkbox" name="enabled" value="1" <?php checked($options['enabled']); ?>>
                    <span class="ka-al-toggle-slider"></span>
                    <span
                        class="ka-al-toggle-label"><?php esc_html_e('Enable Activity Logging', 'king-addons'); ?></span>
                </label>
                <p class="ka-al-field-desc">
                    <?php esc_html_e('When enabled, site activities will be recorded to the log.', 'king-addons'); ?>
                </p>
            </div>

            <div class="ka-al-field">
                <label for="retention_days"><?php esc_html_e('Retention Period', 'king-addons'); ?></label>
                <?php if ($is_pro): ?>
                    <select name="retention_days" id="retention_days" class="ka-al-select">
                        <option value="14" <?php selected($options['retention_days'], 14); ?>>
                            <?php esc_html_e('14 days', 'king-addons'); ?></option>
                        <option value="30" <?php selected($options['retention_days'], 30); ?>>
                            <?php esc_html_e('30 days', 'king-addons'); ?></option>
                        <option value="90" <?php selected($options['retention_days'], 90); ?>>
                            <?php esc_html_e('90 days', 'king-addons'); ?></option>
                        <option value="180" <?php selected($options['retention_days'], 180); ?>>
                            <?php esc_html_e('180 days', 'king-addons'); ?></option>
                        <option value="365" <?php selected($options['retention_days'], 365); ?>>
                            <?php esc_html_e('365 days', 'king-addons'); ?></option>
                    </select>
                <?php else: ?>
                    <div class="ka-al-pro-field">
                        <span class="ka-al-field-value"><?php esc_html_e('14 days', 'king-addons'); ?></span>
                        <span class="ka-al-pro-badge"><?php esc_html_e('Pro', 'king-addons'); ?></span>
                    </div>
                    <p class="ka-al-field-desc">
                        <?php esc_html_e('Upgrade to Pro for extended retention up to 365 days.', 'king-addons'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Logging Modules -->
    <div class="ka-al-card">
        <div class="ka-al-card-header">
            <h3><?php esc_html_e('Logging Modules', 'king-addons'); ?></h3>
        </div>
        <div class="ka-al-card-body">
            <p class="ka-al-field-desc" style="margin-bottom: 20px;">
                <?php esc_html_e('Select which types of events to log.', 'king-addons'); ?></p>

            <div class="ka-al-modules-grid">
                <label class="ka-al-toggle">
                    <input type="checkbox" name="modules[auth]" value="1" <?php checked($options['modules']['auth'] ?? true); ?>>
                    <span class="ka-al-toggle-slider"></span>
                    <span class="ka-al-toggle-label"><?php esc_html_e('Authentication', 'king-addons'); ?></span>
                </label>

                <label class="ka-al-toggle">
                    <input type="checkbox" name="modules[content]" value="1" <?php checked($options['modules']['content'] ?? true); ?>>
                    <span class="ka-al-toggle-slider"></span>
                    <span class="ka-al-toggle-label"><?php esc_html_e('Content', 'king-addons'); ?></span>
                </label>

                <label class="ka-al-toggle">
                    <input type="checkbox" name="modules[users]" value="1" <?php checked($options['modules']['users'] ?? true); ?>>
                    <span class="ka-al-toggle-slider"></span>
                    <span class="ka-al-toggle-label"><?php esc_html_e('Users', 'king-addons'); ?></span>
                </label>

                <label class="ka-al-toggle">
                    <input type="checkbox" name="modules[plugins]" value="1" <?php checked($options['modules']['plugins'] ?? true); ?>>
                    <span class="ka-al-toggle-slider"></span>
                    <span class="ka-al-toggle-label"><?php esc_html_e('Plugins', 'king-addons'); ?></span>
                </label>

                <label class="ka-al-toggle">
                    <input type="checkbox" name="modules[themes]" value="1" <?php checked($options['modules']['themes'] ?? true); ?>>
                    <span class="ka-al-toggle-slider"></span>
                    <span class="ka-al-toggle-label"><?php esc_html_e('Themes', 'king-addons'); ?></span>
                </label>
            </div>

            <!-- Pro modules -->
            <div class="ka-al-pro-modules">
                <h4><?php esc_html_e('Pro Modules', 'king-addons'); ?></h4>
                <div class="ka-al-modules-grid">
                    <?php if ($is_pro): ?>
                        <label class="ka-al-toggle">
                            <input type="checkbox" name="modules[settings]" value="1" <?php checked($options['modules']['settings'] ?? false); ?>>
                            <span class="ka-al-toggle-slider"></span>
                            <span class="ka-al-toggle-label"><?php esc_html_e('Settings Changes', 'king-addons'); ?></span>
                        </label>

                        <label class="ka-al-toggle">
                            <input type="checkbox" name="modules[woocommerce]" value="1" <?php checked($options['modules']['woocommerce'] ?? false); ?>>
                            <span class="ka-al-toggle-slider"></span>
                            <span class="ka-al-toggle-label"><?php esc_html_e('WooCommerce', 'king-addons'); ?></span>
                        </label>

                        <label class="ka-al-toggle">
                            <input type="checkbox" name="modules[king_addons]" value="1" <?php checked($options['modules']['king_addons'] ?? false); ?>>
                            <span class="ka-al-toggle-slider"></span>
                            <span class="ka-al-toggle-label"><?php esc_html_e('King Addons Events', 'king-addons'); ?></span>
                        </label>
                    <?php else: ?>
                        <label class="ka-al-toggle ka-al-toggle--disabled">
                            <input type="checkbox" disabled>
                            <span class="ka-al-toggle-slider"></span>
                            <span class="ka-al-toggle-label"><?php esc_html_e('Settings Changes', 'king-addons'); ?></span>
                            <span class="ka-al-pro-badge"><?php esc_html_e('Pro', 'king-addons'); ?></span>
                        </label>

                        <label class="ka-al-toggle ka-al-toggle--disabled">
                            <input type="checkbox" disabled>
                            <span class="ka-al-toggle-slider"></span>
                            <span class="ka-al-toggle-label"><?php esc_html_e('WooCommerce', 'king-addons'); ?></span>
                            <span class="ka-al-pro-badge"><?php esc_html_e('Pro', 'king-addons'); ?></span>
                        </label>

                        <label class="ka-al-toggle ka-al-toggle--disabled">
                            <input type="checkbox" disabled>
                            <span class="ka-al-toggle-slider"></span>
                            <span class="ka-al-toggle-label"><?php esc_html_e('King Addons Events', 'king-addons'); ?></span>
                            <span class="ka-al-pro-badge"><?php esc_html_e('Pro', 'king-addons'); ?></span>
                        </label>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Privacy & Exclusions -->
    <div class="ka-al-card">
        <div class="ka-al-card-header">
            <h3><?php esc_html_e('Privacy & Exclusions', 'king-addons'); ?></h3>
        </div>
        <div class="ka-al-card-body">
            <div class="ka-al-field">
                <label for="excluded_roles"><?php esc_html_e('Exclude Roles', 'king-addons'); ?></label>
                <select name="excluded_roles[]" id="excluded_roles" class="ka-al-select" multiple
                    style="height: auto; min-height: 100px;">
                    <?php foreach ($roles as $role_key => $role_name): ?>
                        <option value="<?php echo esc_attr($role_key); ?>" <?php echo in_array($role_key, $options['excluded_roles'] ?? [], true) ? 'selected' : ''; ?>>
                            <?php echo esc_html($role_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="ka-al-field-desc">
                    <?php esc_html_e('Activities by users with these roles will not be logged.', 'king-addons'); ?></p>
            </div>

            <div class="ka-al-field">
                <label for="ip_storage"><?php esc_html_e('IP Address Storage', 'king-addons'); ?></label>
                <select name="ip_storage" id="ip_storage" class="ka-al-select">
                    <option value="full" <?php selected($options['ip_storage'] ?? 'full', 'full'); ?>>
                        <?php esc_html_e('Full IP', 'king-addons'); ?></option>
                    <option value="masked" <?php selected($options['ip_storage'] ?? 'full', 'masked'); ?>>
                        <?php esc_html_e('Masked (last octet hidden)', 'king-addons'); ?></option>
                    <option value="hashed" <?php selected($options['ip_storage'] ?? 'full', 'hashed'); ?>>
                        <?php esc_html_e('Hashed only', 'king-addons'); ?></option>
                    <option value="none" <?php selected($options['ip_storage'] ?? 'full', 'none'); ?>>
                        <?php esc_html_e('Do not store', 'king-addons'); ?></option>
                </select>
            </div>

            <div class="ka-al-field">
                <label class="ka-al-toggle">
                    <input type="checkbox" name="store_user_agent" value="1" <?php checked($options['store_user_agent'] ?? true); ?>>
                    <span class="ka-al-toggle-slider"></span>
                    <span class="ka-al-toggle-label"><?php esc_html_e('Store User Agent', 'king-addons'); ?></span>
                </label>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="ka-al-settings-actions">
        <button type="button" id="ka-al-save-settings" class="ka-al-btn ka-al-btn-primary">
            <?php esc_html_e('Save Settings', 'king-addons'); ?>
        </button>

        <button type="button" id="ka-al-purge-logs" class="ka-al-btn ka-al-btn-danger">
            <?php esc_html_e('Purge All Logs', 'king-addons'); ?>
        </button>
    </div>
</div>