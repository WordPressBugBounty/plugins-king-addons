<?php
/**
 * Site Preloader Dashboard Tab.
 *
 * @package King_Addons
 * @since 1.0.0
 *
 * @var array $settings Current settings.
 * @var bool  $is_pro   Whether Pro version is active.
 * @var array $presets  Available presets.
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_preset = $settings['template'] ?? 'spinner-circle';
$preset_info = $presets[$current_preset] ?? $presets['spinner-circle'];
?>

<div class="ka-preloader-dashboard">
    <!-- Quick Stats -->
    <div class="ka-stats-grid ka-preloader-stats">
        <div class="ka-stat-card">
            <div class="ka-stat-label"><?php esc_html_e('Status', 'king-addons'); ?></div>
            <div class="ka-stat-value <?php echo !empty($settings['enabled']) ? 'good' : ''; ?>">
                <?php echo !empty($settings['enabled']) ? esc_html__('Active', 'king-addons') : esc_html__('Inactive', 'king-addons'); ?>
            </div>
        </div>
        <div class="ka-stat-card">
            <div class="ka-stat-label"><?php esc_html_e('Current Template', 'king-addons'); ?></div>
            <div class="ka-stat-value ka-stat-value--small"><?php echo esc_html($preset_info['title']); ?></div>
        </div>
        <div class="ka-stat-card">
            <div class="ka-stat-label"><?php esc_html_e('Display Mode', 'king-addons'); ?></div>
            <div class="ka-stat-value ka-stat-value--small">
                <?php
                $trigger_labels = [
                    'always' => __('Every Visit', 'king-addons'),
                    'first_visit' => __('First Visit', 'king-addons'),
                    'once_per_session' => __('Once Per Session', 'king-addons'),
                    'once_per_day' => __('Once Per Day', 'king-addons'),
                ];
                echo esc_html($trigger_labels[$settings['trigger_type'] ?? 'always']);
                ?>
            </div>
        </div>
        <div class="ka-stat-card">
            <div class="ka-stat-label"><?php esc_html_e('Hide After', 'king-addons'); ?></div>
            <div class="ka-stat-value ka-stat-value--small">
                <?php
                $hide_labels = [
                    'window_load' => __('Page Load', 'king-addons'),
                    'dom_ready' => __('DOM Ready', 'king-addons'),
                    'timeout' => __('Timeout', 'king-addons'),
                ];
                echo esc_html($hide_labels[$settings['hide_strategy'] ?? 'window_load']);
                ?>
            </div>
        </div>
    </div>

    <div class="ka-preloader-dashboard__grid">
        <!-- Quick Enable Card -->
        <div class="ka-card ka-preloader-quick-enable">
            <div class="ka-card-body">
                <div class="ka-preloader-quick-enable__content">
                    <div class="ka-preloader-quick-enable__icon">
                        <span class="dashicons dashicons-<?php echo !empty($settings['enabled']) ? 'yes-alt' : 'controls-play'; ?>"></span>
                    </div>
                    <div class="ka-preloader-quick-enable__text">
                        <h3><?php echo !empty($settings['enabled']) ? esc_html__('Preloader is Active', 'king-addons') : esc_html__('Enable Preloader', 'king-addons'); ?></h3>
                        <p><?php echo !empty($settings['enabled']) 
                            ? esc_html__('Your preloader is currently showing on your website.', 'king-addons') 
                            : esc_html__('Activate the preloader to display it on your website.', 'king-addons'); 
                        ?></p>
                    </div>
                    <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" class="ka-preloader-quick-enable__form">
                        <?php wp_nonce_field('king_addons_site_preloader_save'); ?>
                        <input type="hidden" name="action" value="king_addons_site_preloader_save" />
                        <input type="hidden" name="current_tab" value="dashboard" />
                        <?php foreach ($settings as $key => $value): ?>
                            <?php if ($key !== 'enabled'): ?>
                                <?php if (is_array($value)): ?>
                                    <?php foreach ($value as $arr_val): ?>
                                        <input type="hidden" name="<?php echo esc_attr($key); ?>[]" value="<?php echo esc_attr($arr_val); ?>" />
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <input type="hidden" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($value); ?>" />
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <input type="hidden" name="enabled" value="<?php echo empty($settings['enabled']) ? '1' : '0'; ?>" />
                        <button type="submit" class="ka-btn <?php echo !empty($settings['enabled']) ? 'ka-btn-secondary' : 'ka-btn-primary'; ?>">
                            <?php echo !empty($settings['enabled']) ? esc_html__('Disable', 'king-addons') : esc_html__('Enable', 'king-addons'); ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Current Preview -->
        <div class="ka-card ka-preloader-preview-card">
            <div class="ka-card-header">
                <span class="dashicons dashicons-visibility"></span>
                <h2><?php esc_html_e('Current Preview', 'king-addons'); ?></h2>
            </div>
            <div class="ka-preloader-preview-area" id="ka-dashboard-preview">
                <div class="ka-preloader-preview-wrapper" style="<?php echo esc_attr($this->get_preview_styles($settings)); ?>">
                    <?php $this->render_preset_html($current_preset, $settings); ?>
                </div>
            </div>
            <div class="ka-card-footer">
                <button type="button" class="ka-btn ka-btn-secondary" onclick="kaPreloaderOpenPreview()">
                    <span class="dashicons dashicons-fullscreen-alt"></span>
                    <?php esc_html_e('Full Preview', 'king-addons'); ?>
                </button>
                <a href="<?php echo esc_url(add_query_arg('tab', 'templates', admin_url('admin.php?page=king-addons-site-preloader'))); ?>" class="ka-btn ka-btn-primary">
                    <span class="dashicons dashicons-edit"></span>
                    <?php esc_html_e('Change Template', 'king-addons'); ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="ka-card">
        <div class="ka-card-header">
            <span class="dashicons dashicons-admin-tools"></span>
            <h2><?php esc_html_e('Quick Actions', 'king-addons'); ?></h2>
        </div>
        <div class="ka-card-body">
            <div class="ka-preloader-actions">
                <a href="<?php echo esc_url(add_query_arg('tab', 'settings', admin_url('admin.php?page=king-addons-site-preloader'))); ?>" class="ka-preloader-action-card">
                    <span class="dashicons dashicons-admin-generic"></span>
                    <span class="ka-preloader-action-card__title"><?php esc_html_e('General Settings', 'king-addons'); ?></span>
                    <span class="ka-preloader-action-card__desc"><?php esc_html_e('Configure display rules and behavior', 'king-addons'); ?></span>
                </a>
                <a href="<?php echo esc_url(add_query_arg('tab', 'templates', admin_url('admin.php?page=king-addons-site-preloader'))); ?>" class="ka-preloader-action-card">
                    <span class="dashicons dashicons-layout"></span>
                    <span class="ka-preloader-action-card__title"><?php esc_html_e('Choose Template', 'king-addons'); ?></span>
                    <span class="ka-preloader-action-card__desc"><?php esc_html_e('Select from 12 animation presets', 'king-addons'); ?></span>
                </a>
                <a href="<?php echo esc_url(add_query_arg('tab', 'rules', admin_url('admin.php?page=king-addons-site-preloader'))); ?>" class="ka-preloader-action-card">
                    <span class="dashicons dashicons-filter"></span>
                    <span class="ka-preloader-action-card__title"><?php esc_html_e('Display Rules', 'king-addons'); ?></span>
                    <span class="ka-preloader-action-card__desc"><?php esc_html_e('Set conditions for different pages', 'king-addons'); ?></span>
                </a>
                <a href="<?php echo esc_url(add_query_arg('tab', 'advanced', admin_url('admin.php?page=king-addons-site-preloader'))); ?>" class="ka-preloader-action-card">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <span class="ka-preloader-action-card__title"><?php esc_html_e('Advanced Options', 'king-addons'); ?></span>
                    <span class="ka-preloader-action-card__desc"><?php esc_html_e('Fine-tune timing and CSS', 'king-addons'); ?></span>
                </a>
            </div>
        </div>
    </div>

    <!-- Pro Features Promo -->
    <?php if (!$is_pro): ?>
    <div class="ka-upgrade-card">
        <h3><?php esc_html_e('Unlock Pro Features', 'king-addons'); ?></h3>
        <p><?php esc_html_e('Get access to advanced preloader customization options', 'king-addons'); ?></p>
        <ul>
            <li><?php esc_html_e('Custom Preloader Builder with layers', 'king-addons'); ?></li>
            <li><?php esc_html_e('Lottie, SVG, and GIF animations', 'king-addons'); ?></li>
            <li><?php esc_html_e('Advanced display conditions by role, device, referrer', 'king-addons'); ?></li>
            <li><?php esc_html_e('Different preloaders for different pages', 'king-addons'); ?></li>
            <li><?php esc_html_e('AJAX navigation support', 'king-addons'); ?></li>
            <li><?php esc_html_e('Custom CSS and JS hooks', 'king-addons'); ?></li>
            <li><?php esc_html_e('Import/Export settings', 'king-addons'); ?></li>
        </ul>
        <a href="https://kingaddons.com/pricing/?utm_source=kng-preloader-upgrade&utm_medium=plugin&utm_campaign=kng" target="_blank" class="ka-btn ka-btn-pink">
            <span class="dashicons dashicons-star-filled"></span>
            <?php esc_html_e('Upgrade to Pro', 'king-addons'); ?>
        </a>
    </div>
    <?php endif; ?>
</div>

<?php
/**
 * Helper method to get preview styles.
 */
function get_preview_styles_from_settings($settings) {
    $styles = [];
    
    $bg_type = $settings['bg_type'] ?? 'solid';
    $bg_color = $settings['bg_color'] ?? '#ffffff';
    
    if ($bg_type === 'gradient') {
        $start = $settings['bg_gradient_start'] ?? '#ffffff';
        $end = $settings['bg_gradient_end'] ?? '#f5f5f7';
        $styles[] = "background: linear-gradient(135deg, {$start} 0%, {$end} 100%)";
    } else {
        $styles[] = "background-color: {$bg_color}";
    }
    
    $accent = $settings['accent_color'] ?? '#0071e3';
    $styles[] = "--kng-preloader-accent: {$accent}";
    
    $size = $settings['spinner_size'] ?? 48;
    $styles[] = "--kng-preloader-size: {$size}px";
    
    return implode('; ', $styles);
}
?>
