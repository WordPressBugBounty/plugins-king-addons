<?php
/**
 * Maintenance Mode settings view.
 *
 * @package King_Addons
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<form method="post" action="options.php">
    <?php settings_fields('kng_maintenance_settings_group'); ?>

    <div class="ka-card">
        <div class="ka-card-header">
            <span class="dashicons dashicons-admin-generic purple"></span>
            <h2><?php esc_html_e('Mode Settings', 'king-addons'); ?></h2>
        </div>
        <div class="ka-card-body">
            <input type="hidden" name="kng_maintenance_settings[enabled]" value="0">
            <div class="ka-row">
                <div class="ka-row-label"><?php esc_html_e('Enable maintenance mode', 'king-addons'); ?></div>
                <div class="ka-row-field">
                    <label class="ka-toggle">
                        <input type="checkbox" name="kng_maintenance_settings[enabled]" value="1" <?php checked(!empty($settings['enabled'])); ?> />
                        <span class="ka-toggle-slider"></span>
                        <span class="ka-toggle-label"><?php esc_html_e('Activate site gate', 'king-addons'); ?></span>
                    </label>
                </div>
            </div>

            <div class="ka-row">
                <div class="ka-row-label"><?php esc_html_e('Mode type', 'king-addons'); ?></div>
                <div class="ka-row-field">
                    <select name="kng_maintenance_settings[mode]">
                        <option value="coming_soon" <?php selected($settings['mode'] ?? 'coming_soon', 'coming_soon'); ?>><?php esc_html_e('Coming Soon (200)', 'king-addons'); ?></option>
                        <option value="maintenance" <?php selected($settings['mode'] ?? 'coming_soon', 'maintenance'); ?>><?php esc_html_e('Maintenance (503)', 'king-addons'); ?></option>
                        <option value="private" disabled><?php esc_html_e('Private Site (Pro)', 'king-addons'); ?></option>
                    </select>
                </div>
            </div>

            <input type="hidden" name="kng_maintenance_settings[noindex]" value="0">
            <div class="ka-row">
                <div class="ka-row-label"><?php esc_html_e('Meta robots', 'king-addons'); ?></div>
                <div class="ka-row-field">
                    <label class="ka-toggle">
                        <input type="checkbox" name="kng_maintenance_settings[noindex]" value="1" <?php checked(!empty($settings['noindex'])); ?> />
                        <span class="ka-toggle-slider"></span>
                        <span class="ka-toggle-label"><?php esc_html_e('Noindex maintenance page', 'king-addons'); ?></span>
                    </label>
                </div>
            </div>

            <div class="ka-row">
                <div class="ka-row-label"><?php esc_html_e('Retry-After header', 'king-addons'); ?></div>
                <div class="ka-row-field">
                    <input type="number" name="kng_maintenance_settings[retry_after]" value="<?php echo esc_attr($settings['retry_after'] ?? 3600); ?>" min="0" step="60">
                    <small><?php esc_html_e('Seconds for maintenance mode only (0 to disable).', 'king-addons'); ?></small>
                </div>
            </div>
        </div>
    </div>

    <div class="ka-card">
        <div class="ka-card-header">
            <span class="dashicons dashicons-lock pink"></span>
            <h2><?php esc_html_e('Private Access', 'king-addons'); ?></h2>
            <?php if (!$is_pro) : ?><span class="ka-pro-badge">PRO</span><?php endif; ?>
        </div>
        <div class="ka-card-body">
            <?php if (!$is_pro) : ?>
                <div class="ka-row">
                    <div class="ka-row-label"><?php esc_html_e('Password protection', 'king-addons'); ?></div>
                    <div class="ka-row-field">
                        <input type="password" disabled placeholder="<?php esc_attr_e('Set a password (Pro)', 'king-addons'); ?>">
                    </div>
                </div>
                <div class="ka-row">
                    <div class="ka-row-label"><?php esc_html_e('Access token', 'king-addons'); ?></div>
                    <div class="ka-row-field">
                        <input type="text" disabled placeholder="<?php esc_attr_e('Generate token link (Pro)', 'king-addons'); ?>">
                    </div>
                </div>
            <?php else : ?>
                <?php
                $password_is_set = !empty($settings['private_password_hash']);
                $token = (string) ($settings['private_token'] ?? '');
                $token_is_set = $token !== '';

                $token_url = $token_is_set
                    ? add_query_arg('kng_maintenance_token', rawurlencode($token), home_url('/'))
                    : '';

                $token_generate_url = wp_nonce_url(
                    add_query_arg([
                        'action' => 'kng_maintenance_generate_token',
                    ], admin_url('admin-post.php')),
                    'kng_maintenance_private_token'
                );

                $token_revoke_url = wp_nonce_url(
                    add_query_arg([
                        'action' => 'kng_maintenance_revoke_token',
                    ], admin_url('admin-post.php')),
                    'kng_maintenance_private_token'
                );

                $password_revoke_url = wp_nonce_url(
                    add_query_arg([
                        'action' => 'kng_maintenance_revoke_password',
                    ], admin_url('admin-post.php')),
                    'kng_maintenance_private_password'
                );
                ?>

                <div class="ka-row">
                    <div class="ka-row-label"><?php esc_html_e('Password protection', 'king-addons'); ?></div>
                    <div class="ka-row-field">
                        <?php
                        $kng_password_notice_allowed = isset($message_code) && $message_code === 'password_revoked';
                        if ($kng_password_notice_allowed && isset($messages[$message_code])) :
                            ?>
                            <div class="ka-alert kng-maintenance-inline-alert">
                                <span class="dashicons dashicons-yes"></span>
                                <?php echo esc_html($messages[$message_code]); ?>
                            </div>
                        <?php endif; ?>

                        <input type="password" name="kng_maintenance_settings[private_password]" value="" placeholder="<?php echo esc_attr($password_is_set ? __('Leave blank to keep current password', 'king-addons') : __('Set a password', 'king-addons')); ?>" autocomplete="new-password">
                        <small>
                            <?php if ($password_is_set) : ?>
                                <?php esc_html_e('A password is set.', 'king-addons'); ?>
                            <?php else : ?>
                                <?php esc_html_e('No password set.', 'king-addons'); ?>
                            <?php endif; ?>
                        </small>

                        <?php if ($password_is_set) : ?>
                            <div class="kng-maintenance-token-actions kng-maintenance-password-actions">
                                <a class="ka-btn ka-btn-secondary ka-btn-sm kng-maintenance-btn-danger" href="<?php echo esc_url($password_revoke_url); ?>" onclick="return confirm('<?php echo esc_js(__('Revoke password protection now? Visitors will no longer be able to use the password to bypass maintenance.', 'king-addons')); ?>');">
                                    <span class="dashicons dashicons-trash" aria-hidden="true"></span>
                                    <?php esc_html_e('Revoke password', 'king-addons'); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="ka-row">
                    <div class="ka-row-label"><?php esc_html_e('Access token', 'king-addons'); ?></div>
                    <div class="ka-row-field">
                        <?php
                        $kng_token_notice_allowed = isset($message_code) && in_array($message_code, ['token_generated', 'token_revoked'], true);
                        if ($kng_token_notice_allowed && isset($messages[$message_code])) :
                            ?>
                            <div class="ka-alert kng-maintenance-inline-alert">
                                <span class="dashicons dashicons-yes"></span>
                                <?php echo esc_html($messages[$message_code]); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($token_is_set) : ?>
                            <div class="kng-maintenance-token-row">
                                <input type="text" readonly value="<?php echo esc_attr($token_url); ?>" class="kng-maintenance-token-input" aria-label="<?php echo esc_attr__('Access token URL', 'king-addons'); ?>">
                                <button type="button" class="ka-btn ka-btn-secondary ka-btn-sm kng-maintenance-copy-token">
                                    <span class="dashicons dashicons-clipboard" aria-hidden="true"></span>
                                    <?php esc_html_e('Copy', 'king-addons'); ?>
                                </button>
                                <span class="kng-maintenance-copy-status" aria-live="polite" role="status"></span>
                            </div>
                            <small><?php esc_html_e('Anyone with this link will bypass maintenance mode.', 'king-addons'); ?></small>
                            <div class="kng-maintenance-token-actions">
                                <a class="ka-btn ka-btn-secondary ka-btn-sm" href="<?php echo esc_url($token_generate_url); ?>">
                                    <span class="dashicons dashicons-update" aria-hidden="true"></span>
                                    <?php esc_html_e('Regenerate', 'king-addons'); ?>
                                </a>
                                <a class="ka-btn ka-btn-secondary ka-btn-sm kng-maintenance-btn-danger" href="<?php echo esc_url($token_revoke_url); ?>" onclick="return confirm('<?php echo esc_js(__('Revoke access token? Anyone with the existing link will lose access.', 'king-addons')); ?>');">
                                    <span class="dashicons dashicons-trash" aria-hidden="true"></span>
                                    <?php esc_html_e('Revoke', 'king-addons'); ?>
                                </a>
                            </div>
                        <?php else : ?>
                            <small><?php esc_html_e('No token generated.', 'king-addons'); ?></small>
                            <div class="kng-maintenance-token-actions">
                                <a class="ka-btn ka-btn-primary ka-btn-sm" href="<?php echo esc_url($token_generate_url); ?>">
                                    <span class="dashicons dashicons-admin-links" aria-hidden="true"></span>
                                    <?php esc_html_e('Generate token', 'king-addons'); ?>
                                </a>
                            </div>
                        <?php endif; ?>

                        <input type="hidden" name="kng_maintenance_settings[private_token]" value="<?php echo esc_attr($token); ?>">
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="ka-card">
        <div class="ka-card-header">
            <span class="dashicons dashicons-admin-tools purple"></span>
            <h2><?php esc_html_e('Advanced Exceptions', 'king-addons'); ?></h2>
        </div>
        <div class="ka-card-body">
            <input type="hidden" name="kng_maintenance_settings[allow_admin_ajax]" value="0">
            <label class="ka-toggle">
                <input type="checkbox" name="kng_maintenance_settings[allow_admin_ajax]" value="1" <?php checked(!empty($settings['allow_admin_ajax'])); ?> />
                <span class="ka-toggle-slider"></span>
                <span class="ka-toggle-label"><?php esc_html_e('Allow admin-ajax requests', 'king-addons'); ?></span>
            </label>
            <input type="hidden" name="kng_maintenance_settings[allow_rest]" value="0">
            <label class="ka-toggle">
                <input type="checkbox" name="kng_maintenance_settings[allow_rest]" value="1" <?php checked(!empty($settings['allow_rest'])); ?> />
                <span class="ka-toggle-slider"></span>
                <span class="ka-toggle-label"><?php esc_html_e('Allow REST API for guests', 'king-addons'); ?></span>
            </label>
            <input type="hidden" name="kng_maintenance_settings[disable_elementor_editor]" value="0">
            <label class="ka-toggle">
                <input type="checkbox" name="kng_maintenance_settings[disable_elementor_editor]" value="1" <?php checked(!empty($settings['disable_elementor_editor'])); ?> />
                <span class="ka-toggle-slider"></span>
                <span class="ka-toggle-label"><?php esc_html_e('Disable maintenance in Elementor editor', 'king-addons'); ?></span>
            </label>
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
