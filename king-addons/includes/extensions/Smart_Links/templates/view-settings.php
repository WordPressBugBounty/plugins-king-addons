<?php
/**
 * Smart Links settings view.
 *
 * @package King_Addons
 */

if (!defined('ABSPATH')) {
    exit;
}

$is_pro_enabled = $is_pro;
?>

<form method="post" action="options.php">
    <?php settings_fields('king_addons_smart_links'); ?>

    <div class="ka-tabs kng-settings-tabs">
        <button type="button" class="ka-tab kng-settings-tab active" data-tab="general"><?php esc_html_e('General', 'king-addons'); ?></button>
        <button type="button" class="ka-tab kng-settings-tab" data-tab="tracking"><?php esc_html_e('Tracking', 'king-addons'); ?></button>
        <button type="button" class="ka-tab kng-settings-tab" data-tab="redirect"><?php esc_html_e('Redirect', 'king-addons'); ?></button>
        <button type="button" class="ka-tab kng-settings-tab" data-tab="cache"><?php esc_html_e('Cache', 'king-addons'); ?></button>
        <button type="button" class="ka-tab kng-settings-tab" data-tab="misc"><?php esc_html_e('Misc', 'king-addons'); ?></button>
    </div>

    <div class="ka-tab-content kng-settings-content active" data-tab="general">
        <div class="ka-card">
            <div class="ka-card-header">
                <span class="dashicons dashicons-admin-generic green"></span>
                <h2><?php esc_html_e('General', 'king-addons'); ?></h2>
            </div>
            <div class="ka-card-body">
                <div class="ka-row">
                    <div class="ka-row-label"><?php esc_html_e('Base Path', 'king-addons'); ?></div>
                    <div class="ka-row-field">
                        <input type="text" name="king_addons_smart_links_settings[base_path]" value="<?php echo esc_attr($settings['base_path']); ?>" placeholder="go">
                        <p class="ka-row-desc"><?php esc_html_e('Short URL base path, e.g. /go/slug.', 'king-addons'); ?></p>
                    </div>
                </div>
                <div class="ka-row">
                    <div class="ka-row-label"><?php esc_html_e('Default Slug Length', 'king-addons'); ?></div>
                    <div class="ka-row-field">
                        <input type="number" name="king_addons_smart_links_settings[default_slug_length]" value="<?php echo esc_attr($settings['default_slug_length']); ?>" min="4" max="20">
                    </div>
                </div>
                <div class="ka-row">
                    <div class="ka-row-label"><?php esc_html_e('Allow Manual Slug', 'king-addons'); ?></div>
                    <div class="ka-row-field">
                        <label class="ka-toggle">
                            <input type="checkbox" name="king_addons_smart_links_settings[allow_manual_slug]" value="1" <?php checked(!empty($settings['allow_manual_slug'])); ?> />
                            <span class="ka-toggle-slider"></span>
                            <span class="ka-toggle-label"><?php esc_html_e('Enable manual slugs', 'king-addons'); ?></span>
                        </label>
                    </div>
                </div>
                <div class="ka-row">
                    <div class="ka-row-label"><?php esc_html_e('Default Redirect', 'king-addons'); ?></div>
                    <div class="ka-row-field">
                        <select name="king_addons_smart_links_settings[default_redirect_type]">
                            <option value="301" <?php selected($settings['default_redirect_type'], '301'); ?>><?php esc_html_e('301 Permanent', 'king-addons'); ?></option>
                            <option value="302" <?php selected($settings['default_redirect_type'], '302'); ?>><?php esc_html_e('302 Temporary', 'king-addons'); ?></option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="ka-tab-content kng-settings-content" data-tab="tracking">
        <div class="ka-card">
            <div class="ka-card-header">
                <span class="dashicons dashicons-chart-bar green"></span>
                <h2><?php esc_html_e('Tracking', 'king-addons'); ?></h2>
            </div>
            <div class="ka-card-body">
                <div class="ka-row">
                    <div class="ka-row-label"><?php esc_html_e('Enable Tracking', 'king-addons'); ?></div>
                    <div class="ka-row-field">
                        <label class="ka-toggle">
                            <input type="checkbox" name="king_addons_smart_links_settings[tracking_enabled]" value="1" <?php checked(!empty($settings['tracking_enabled'])); ?> />
                            <span class="ka-toggle-slider"></span>
                            <span class="ka-toggle-label"><?php esc_html_e('Record click events', 'king-addons'); ?></span>
                        </label>
                    </div>
                </div>
                <div class="ka-row">
                    <div class="ka-row-label"><?php esc_html_e('Exclude Bots', 'king-addons'); ?></div>
                    <div class="ka-row-field">
                        <label class="ka-toggle">
                            <input type="checkbox" name="king_addons_smart_links_settings[exclude_bots]" value="1" <?php checked(!empty($settings['exclude_bots'])); ?> />
                            <span class="ka-toggle-slider"></span>
                            <span class="ka-toggle-label"><?php esc_html_e('Ignore known bots', 'king-addons'); ?></span>
                        </label>
                    </div>
                </div>
                <div class="ka-row">
                    <div class="ka-row-label"><?php esc_html_e('Unique Window', 'king-addons'); ?></div>
                    <div class="ka-row-field">
                        <select name="king_addons_smart_links_settings[unique_click_window]">
                            <option value="daily" <?php selected($settings['unique_click_window'], 'daily'); ?>><?php esc_html_e('Per day', 'king-addons'); ?></option>
                            <option value="rolling" <?php selected($settings['unique_click_window'], 'rolling'); ?>><?php esc_html_e('Rolling 24h', 'king-addons'); ?></option>
                        </select>
                    </div>
                </div>
                <div class="ka-row">
                    <div class="ka-row-label"><?php esc_html_e('Retention Days', 'king-addons'); ?></div>
                    <div class="ka-row-field">
                        <input type="number" name="king_addons_smart_links_settings[retention_days]" value="<?php echo esc_attr($settings['retention_days']); ?>" min="0" <?php disabled(!$is_pro_enabled); ?> />
                        <?php if (!$is_pro_enabled) : ?><span class="ka-pro-badge">PRO</span><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="ka-tab-content kng-settings-content" data-tab="redirect">
        <div class="ka-card">
            <div class="ka-card-header">
                <span class="dashicons dashicons-randomize green"></span>
                <h2><?php esc_html_e('Redirect', 'king-addons'); ?></h2>
            </div>
            <div class="ka-card-body">
                <div class="ka-row">
                    <div class="ka-row-label"><?php esc_html_e('Query Passthrough', 'king-addons'); ?></div>
                    <div class="ka-row-field">
                        <label class="ka-toggle">
                            <input type="checkbox" name="king_addons_smart_links_settings[pass_query_params]" value="1" <?php checked(!empty($settings['pass_query_params'])); ?> />
                            <span class="ka-toggle-slider"></span>
                            <span class="ka-toggle-label"><?php esc_html_e('Forward query parameters', 'king-addons'); ?></span>
                        </label>
                    </div>
                </div>
                <div class="ka-row">
                    <div class="ka-row-label"><?php esc_html_e('Whitelist Params', 'king-addons'); ?></div>
                    <div class="ka-row-field">
                        <input type="text" name="king_addons_smart_links_settings[whitelist_query_params]" value="<?php echo esc_attr($settings['whitelist_query_params']); ?>" placeholder="utm_source, utm_medium">
                        <p class="ka-row-desc"><?php esc_html_e('Comma-separated list of allowed parameters.', 'king-addons'); ?></p>
                    </div>
                </div>
                <div class="ka-row">
                    <div class="ka-row-label"><?php esc_html_e('Blacklist Params', 'king-addons'); ?></div>
                    <div class="ka-row-field">
                        <input type="text" name="king_addons_smart_links_settings[blacklist_query_params]" value="<?php echo esc_attr($settings['blacklist_query_params']); ?>" placeholder="fbclid, gclid">
                    </div>
                </div>
                <div class="ka-row">
                    <div class="ka-row-label"><?php esc_html_e('Global UTM Defaults', 'king-addons'); ?></div>
                    <div class="ka-row-field">
                        <input type="text" placeholder="utm_source=brand" disabled>
                        <?php if (!$is_pro_enabled) : ?><span class="ka-pro-badge">PRO</span><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="ka-tab-content kng-settings-content" data-tab="cache">
        <div class="ka-card">
            <div class="ka-card-header">
                <span class="dashicons dashicons-update green"></span>
                <h2><?php esc_html_e('Cache', 'king-addons'); ?></h2>
            </div>
            <div class="ka-card-body">
                <div class="ka-row">
                    <div class="ka-row-label"><?php esc_html_e('Cache TTL (seconds)', 'king-addons'); ?></div>
                    <div class="ka-row-field">
                        <input type="number" name="king_addons_smart_links_settings[cache_ttl]" value="<?php echo esc_attr($settings['cache_ttl']); ?>" min="0">
                        <p class="ka-row-desc"><?php esc_html_e('Cache link lookups for faster redirects.', 'king-addons'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="ka-tab-content kng-settings-content" data-tab="misc">
        <div class="ka-card">
            <div class="ka-card-header">
                <span class="dashicons dashicons-shield green"></span>
                <h2><?php esc_html_e('Misc', 'king-addons'); ?></h2>
            </div>
            <div class="ka-card-body">
                <div class="ka-row">
                    <div class="ka-row-label"><?php esc_html_e('Role Access', 'king-addons'); ?></div>
                    <div class="ka-row-field">
                        <select disabled>
                            <option><?php esc_html_e('Administrator only', 'king-addons'); ?></option>
                        </select>
                        <?php if (!$is_pro_enabled) : ?><span class="ka-pro-badge">PRO</span><?php endif; ?>
                    </div>
                </div>
                <div class="ka-row">
                    <div class="ka-row-label"><?php esc_html_e('Reset Data', 'king-addons'); ?></div>
                    <div class="ka-row-field">
                        <button type="button" class="ka-btn ka-btn-secondary ka-btn-sm" disabled>
                            <?php esc_html_e('Reset Smart Links', 'king-addons'); ?>
                        </button>
                        <?php if (!$is_pro_enabled) : ?><span class="ka-pro-badge">PRO</span><?php endif; ?>
                    </div>
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
