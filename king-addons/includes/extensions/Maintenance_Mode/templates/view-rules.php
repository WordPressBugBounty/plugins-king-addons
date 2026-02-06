<?php
/**
 * Maintenance Mode rules view.
 *
 * @package King_Addons
 */

if (!defined('ABSPATH')) {
    exit;
}

$whitelist_ips = !empty($settings['whitelist_ips']) ? implode("\n", $settings['whitelist_ips']) : '';
$whitelist_paths = !empty($settings['whitelist_paths']) ? implode("\n", $settings['whitelist_paths']) : '';
?>

<form method="post" action="options.php">
    <?php settings_fields('kng_maintenance_settings_group'); ?>

    <div class="ka-card">
        <div class="ka-card-header">
            <span class="dashicons dashicons-filter purple"></span>
            <h2><?php esc_html_e('Basic Rules', 'king-addons'); ?></h2>
        </div>
        <div class="ka-card-body">
            <input type="hidden" name="kng_maintenance_settings[exclude_admin]" value="0">
            <label class="ka-toggle">
                <input type="checkbox" name="kng_maintenance_settings[exclude_admin]" value="1" <?php checked(!empty($settings['exclude_admin'])); ?> />
                <span class="ka-toggle-slider"></span>
                <span class="ka-toggle-label"><?php esc_html_e('Always allow administrators', 'king-addons'); ?></span>
            </label>

            <div class="kng-maintenance-rule-grid">
                <div class="kng-field">
                    <label><?php esc_html_e('Whitelist IPs', 'king-addons'); ?></label>
                    <textarea name="kng_maintenance_settings[whitelist_ips]" rows="4" placeholder="203.0.113.10&#10;198.51.100.22"><?php echo esc_textarea($whitelist_ips); ?></textarea>
                    <small><?php esc_html_e('One IP per line. Free plan supports up to 10.', 'king-addons'); ?></small>
                </div>
                <div class="kng-field">
                    <label><?php esc_html_e('Whitelist Paths', 'king-addons'); ?></label>
                    <textarea name="kng_maintenance_settings[whitelist_paths]" rows="4" placeholder="/pricing&#10;/about"><?php echo esc_textarea($whitelist_paths); ?></textarea>
                    <small><?php esc_html_e('One path per line. Prefix matches are allowed.', 'king-addons'); ?></small>
                </div>
            </div>
        </div>
    </div>

    <div class="ka-card">
        <div class="ka-card-header">
            <span class="dashicons dashicons-lock pink"></span>
            <h2><?php esc_html_e('Pro Rules Engine', 'king-addons'); ?></h2>
            <?php if (!$is_pro) : ?><span class="ka-pro-badge">PRO</span><?php endif; ?>
        </div>
        <div class="ka-card-body">
            <div class="kng-maintenance-pro-grid">
                <div>
                    <strong><?php esc_html_e('Role, device, and country rules', 'king-addons'); ?></strong>
                    <p><?php esc_html_e('Create advanced allow/block rules with priority ordering.', 'king-addons'); ?></p>
                </div>
                <div>
                    <strong><?php esc_html_e('Per-rule templates', 'king-addons'); ?></strong>
                    <p><?php esc_html_e('Serve different maintenance pages by segment.', 'king-addons'); ?></p>
                </div>
                <div>
                    <strong><?php esc_html_e('Query and referrer conditions', 'king-addons'); ?></strong>
                    <p><?php esc_html_e('Fine-tune access using URL parameters and referrers.', 'king-addons'); ?></p>
                </div>
            </div>

            <?php if (!$is_pro) : ?>
                <a href="https://kingaddons.com/pricing/" target="_blank" class="ka-btn ka-btn-pink">
                    <?php esc_html_e('Upgrade to Pro', 'king-addons'); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="ka-card">
        <div class="ka-submit">
            <button type="submit" class="ka-btn ka-btn-primary">
                <?php esc_html_e('Save Rules', 'king-addons'); ?>
            </button>
        </div>
    </div>
</form>
