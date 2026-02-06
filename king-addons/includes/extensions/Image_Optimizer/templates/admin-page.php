<?php
/**
 * Image Optimizer admin page template.
 * Premium Apple-inspired design with dark/light theme support.
 *
 * @package King_Addons
 */

if (!defined('ABSPATH')) {
    exit;
}

use King_Addons\Image_Optimizer\Image_Optimizer;
use King_Addons\Image_Optimizer\Image_Optimizer_DB;

$optimizer = Image_Optimizer::instance();
$settings = $optimizer->get_settings();
$stats = $optimizer->get_global_stats();
$is_pro = $optimizer->is_pro();
$quota = !$is_pro ? $optimizer->get_free_quota_state() : null;
$upgrade_url = $optimizer->get_upgrade_url('kng-img-optimizer');
$savings = Image_Optimizer_DB::get_total_savings();
$format_counts = Image_Optimizer_DB::count_images_by_format();

// Theme mode (per-user)
$theme_mode = get_user_meta(get_current_user_id(), 'king_addons_theme_mode', true);
if (empty($theme_mode)) {
    $theme_mode = get_option('king_addons_theme_mode', '');
}
$allowed_theme_modes = ['dark', 'light', 'auto'];
if (!in_array($theme_mode, $allowed_theme_modes, true)) {
    $theme_mode = 'dark';
}

// Enqueue shared V3 styles
wp_enqueue_style(
    'king-addons-admin-v3',
    KING_ADDONS_URL . 'includes/admin/layouts/shared/admin-v3-styles.css',
    [],
    KING_ADDONS_VERSION
);
?>
<script>
(function() {
    document.body.classList.add('ka-admin-v3');
    const mode = '<?php echo esc_js($theme_mode); ?>';
    const mql = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
    const isDark = mode === 'auto' ? !!(mql && mql.matches) : mode === 'dark';
    document.documentElement.classList.toggle('ka-v3-dark', isDark);
    document.body.classList.toggle('ka-v3-dark', isDark);
})();
</script>

<div class="ka-admin-wrap ka-img-opt-wrap">
    <!-- Header -->
    <div class="ka-admin-header">
        <div class="ka-admin-header-left">
            <div class="ka-admin-header-icon" style="background: linear-gradient(135deg, #5e5ce6 0%, #bf5af2 100%);">
                <span class="dashicons dashicons-format-image"></span>
            </div>
            <div>
                <h1 class="ka-admin-title"><?php esc_html_e('Image Optimizer', 'king-addons'); ?></h1>
                <p class="ka-admin-subtitle"><?php esc_html_e('Optimize your images for faster page loads and better SEO.', 'king-addons'); ?></p>
            </div>
        </div>
        <div class="ka-admin-header-actions">
            <!-- Theme Toggle -->
            <div class="ka-v3-segmented" id="ka-v3-theme-segment" role="radiogroup" aria-label="<?php echo esc_attr__('Theme', 'king-addons'); ?>" data-active="<?php echo esc_attr($theme_mode); ?>">
                <span class="ka-v3-segmented-indicator" aria-hidden="true"></span>
                <button type="button" class="ka-v3-segmented-btn" data-theme="light" aria-pressed="<?php echo $theme_mode === 'light' ? 'true' : 'false'; ?>">
                    <span class="ka-v3-segmented-icon" aria-hidden="true">☀︎</span>
                    <?php esc_html_e('Light', 'king-addons'); ?>
                </button>
                <button type="button" class="ka-v3-segmented-btn" data-theme="dark" aria-pressed="<?php echo $theme_mode === 'dark' ? 'true' : 'false'; ?>">
                    <span class="ka-v3-segmented-icon" aria-hidden="true">☾</span>
                    <?php esc_html_e('Dark', 'king-addons'); ?>
                </button>
                <button type="button" class="ka-v3-segmented-btn" data-theme="auto" aria-pressed="<?php echo $theme_mode === 'auto' ? 'true' : 'false'; ?>">
                    <span class="ka-v3-segmented-icon" aria-hidden="true">◐</span>
                    <?php esc_html_e('Auto', 'king-addons'); ?>
                </button>
            </div>

            <?php if (!$is_pro && is_array($quota)) : ?>
                <div class="ka-img-opt-quota" id="ka-img-opt-quota" aria-label="<?php echo esc_attr__('Free plan quota', 'king-addons'); ?>">
                    <div class="ka-img-opt-quota-text">
                        <div class="ka-img-opt-quota-title"><?php esc_html_e('Free Plan', 'king-addons'); ?></div>
                        <div class="ka-img-opt-quota-sub">
                            <span id="ka-img-opt-quota-remaining"><?php echo esc_html((string) ($quota['remaining'] ?? '0')); ?></span>
                            <?php echo esc_html__(' / ', 'king-addons'); ?>
                            <span id="ka-img-opt-quota-limit"><?php echo esc_html((string) ($quota['limit'] ?? '200')); ?></span>
                            <?php esc_html_e('left this month', 'king-addons'); ?>
                        </div>
                    </div>
                    <a class="ka-btn ka-btn-primary" id="ka-img-opt-upgrade" href="<?php echo esc_url($upgrade_url); ?>" target="_blank" rel="noopener noreferrer">
                        <?php esc_html_e('Upgrade to Unlimited', 'king-addons'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="ka-img-opt-tabs">
        <button type="button" class="ka-img-opt-tab active" data-tab="bulk"><?php esc_html_e('Bulk Optimizer', 'king-addons'); ?></button>
        <button type="button" class="ka-img-opt-tab" data-tab="settings"><?php esc_html_e('Settings', 'king-addons'); ?></button>
    </div>

    <!-- Statistics Cards -->
    <div class="ka-img-opt-stats">
        <div class="ka-img-opt-stat-card">
            <div class="ka-img-opt-stat-icon purple">
                <span class="dashicons dashicons-format-gallery"></span>
            </div>
            <div class="ka-img-opt-stat-content">
                <span class="ka-img-opt-stat-value" id="stat-total"><?php echo esc_html(number_format_i18n($stats['total_images'])); ?></span>
                <span class="ka-img-opt-stat-label"><?php esc_html_e('Total Images', 'king-addons'); ?></span>
            </div>
        </div>
        <div class="ka-img-opt-stat-card">
            <div class="ka-img-opt-stat-icon orange">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <div class="ka-img-opt-stat-content">
                <span class="ka-img-opt-stat-value" id="stat-pending"><?php echo esc_html(number_format_i18n($stats['pending_images'] ?? max(0, $stats['total_images'] - $stats['optimized_images']))); ?></span>
                <span class="ka-img-opt-stat-label"><?php esc_html_e('Pending', 'king-addons'); ?></span>
            </div>
        </div>
        <div class="ka-img-opt-stat-card">
            <div class="ka-img-opt-stat-icon grey">
                <span class="dashicons dashicons-warning"></span>
            </div>
            <div class="ka-img-opt-stat-content">
                <span class="ka-img-opt-stat-value" id="stat-failed"><?php echo esc_html(number_format_i18n($stats['failed_images'] ?? 0)); ?></span>
                <span class="ka-img-opt-stat-label"><?php esc_html_e('Failed', 'king-addons'); ?></span>
            </div>
        </div>
        <div class="ka-img-opt-stat-card large-card">
            <div class="ka-img-opt-stat-icon blue">
                <span class="dashicons dashicons-performance"></span>
            </div>
            <div class="ka-img-opt-stat-content">
                <span class="ka-img-opt-stat-value" id="stat-saved"><?php echo esc_html(Image_Optimizer::format_bytes($savings['total_saved'])); ?></span>
                <span class="ka-img-opt-stat-label"><?php esc_html_e('Space Saved', 'king-addons'); ?></span>
            </div>
        </div>
        <div class="ka-img-opt-stat-card">
            <div class="ka-img-opt-stat-icon green">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="ka-img-opt-stat-content">
                <span class="ka-img-opt-stat-value" id="stat-optimized"><?php echo esc_html(number_format_i18n($stats['optimized_images'])); ?></span>
                <span class="ka-img-opt-stat-label"><?php esc_html_e('Optimized', 'king-addons'); ?></span>
            </div>
        </div>
    </div>

    <!-- Tab Content: Bulk Optimizer -->
    <div class="ka-img-opt-tab-content active" id="tab-bulk">
        
        <!-- Resume Banner (hidden by default) -->
        <div class="ka-img-opt-resume-banner" id="resume-banner" style="display: none;">
            <button type="button" class="ka-img-opt-resume-close" id="resume-close">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
            <div class="ka-img-opt-resume-body">
                <div class="ka-img-opt-resume-icon">
                    <span class="dashicons dashicons-backup"></span>
                </div>
                <div class="ka-img-opt-resume-content">
                    <h3><?php esc_html_e('Resume Optimization', 'king-addons'); ?></h3>
                    <p><?php esc_html_e('Continue your interrupted optimization session.', 'king-addons'); ?></p>
                    <div class="ka-img-opt-resume-progress">
                        <span id="resume-count">0 of 0</span> <?php esc_html_e('images processed', 'king-addons'); ?>
                    </div>
                </div>
            </div>
            <div class="ka-img-opt-resume-actions">
                <button type="button" id="resume-btn" class="ka-btn ka-btn-primary"><?php esc_html_e('Resume', 'king-addons'); ?></button>
                <button type="button" id="discard-btn" class="ka-btn ka-btn-secondary"><?php esc_html_e('Start Over', 'king-addons'); ?></button>
            </div>
        </div>

        <!-- Optimization Options -->
        <div class="ka-card ka-img-opt-options" id="optimization-options">
            <div class="ka-card-header">
                <span class="dashicons dashicons-admin-settings"></span>
                <h2><?php esc_html_e('Optimization Settings', 'king-addons'); ?></h2>
            </div>
            <div class="ka-card-body">
                <div class="ka-img-opt-option-group">
                    <label class="ka-img-opt-option-label"><?php esc_html_e('Mode', 'king-addons'); ?></label>
                    <p class="ka-row-desc" style="margin: 0;"><?php esc_html_e('Bulk convert PNG and JPEG images to modern WebP format with compression and transparency support.', 'king-addons'); ?></p>
                </div>

                <!-- Quality Slider -->
                <div class="ka-img-opt-option-group">
                    <label class="ka-img-opt-option-label"><?php esc_html_e('Quality', 'king-addons'); ?></label>
                    <div class="ka-img-opt-quality-presets">
                        <button type="button" class="ka-img-opt-preset-btn" data-quality="95"><?php esc_html_e('Maximum', 'king-addons'); ?></button>
                        <button type="button" class="ka-img-opt-preset-btn active" data-quality="82"><?php esc_html_e('Balanced', 'king-addons'); ?></button>
                        <button type="button" class="ka-img-opt-preset-btn" data-quality="65"><?php esc_html_e('Web Optimized', 'king-addons'); ?></button>
                        <button type="button" class="ka-img-opt-preset-btn" data-quality="45"><?php esc_html_e('Max Compression', 'king-addons'); ?></button>
                    </div>
                    <div class="ka-img-opt-slider-wrap">
                        <input type="range" id="quality-slider" min="1" max="100" value="<?php echo esc_attr($settings['quality']); ?>" class="ka-img-opt-slider">
                        <output id="quality-output"><?php echo esc_html($settings['quality']); ?>%</output>
                    </div>
                </div>

                <!-- Advanced Settings Toggle -->
                <div class="ka-img-opt-advanced-toggle" id="advanced-toggle">
                    <span class="dashicons dashicons-admin-generic"></span>
                    <?php esc_html_e('Advanced Settings', 'king-addons'); ?>
                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                </div>

                <!-- Advanced Settings Content -->
                <div class="ka-img-opt-advanced-content" id="advanced-content" style="display: none;">
                    <!-- Skip Small Images -->
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Skip Small Images', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <label class="ka-toggle">
                                <input type="checkbox" id="skip-small" <?php checked($settings['skip_small']); ?>>
                                <span class="ka-toggle-slider"></span>
                            </label>
                            <p class="ka-row-desc"><?php esc_html_e('Skip images smaller than 10KB (already optimized).', 'king-addons'); ?></p>
                        </div>
                    </div>

                    <!-- Auto Replace URLs -->
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Auto Replace URLs', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <label class="ka-toggle">
                                <input type="checkbox" id="auto-replace-urls" <?php checked($settings['auto_replace_urls']); ?>>
                                <span class="ka-toggle-slider"></span>
                            </label>
                            <p class="ka-row-desc"><?php esc_html_e('Automatically update image URLs in content after optimization.', 'king-addons'); ?></p>
                        </div>
                    </div>

                    <!-- Resize Large Images -->
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Resize Large Images', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <label class="ka-toggle">
                                <input type="checkbox" id="resize-enabled" <?php checked($settings['resize_enabled']); ?>>
                                <span class="ka-toggle-slider"></span>
                            </label>
                            <div class="ka-img-opt-resize-options" style="<?php echo $settings['resize_enabled'] ? '' : 'display:none;'; ?>">
                                <label><?php esc_html_e('Max width:', 'king-addons'); ?>
                                    <input type="number" id="max-width" value="<?php echo esc_attr($settings['max_width']); ?>" min="100" max="5000"> px
                                </label>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Start Button -->
                <div class="ka-img-opt-actions">
                    <button type="button" id="start-optimization" class="ka-btn ka-btn-primary ka-btn-lg">
                        <span class="dashicons dashicons-controls-play"></span>
                        <?php esc_html_e('Start Optimization', 'king-addons'); ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Progress Section (hidden by default) -->
        <div class="ka-card ka-img-opt-progress" id="progress-section" style="display: none;">
            <div class="ka-card-header">
                <span class="spinner ka-img-opt-title-spinner" id="ka-opt-progress-spinner" aria-hidden="true"></span>
                <span class="dashicons dashicons-yes-alt ka-img-opt-title-check" id="ka-opt-progress-check" aria-hidden="true" style="display:none;"></span>
                <h2><?php esc_html_e('Optimization Progress', 'king-addons'); ?></h2>
            </div>
            <div class="ka-card-body">
                <!-- Progress Bar -->
                <div class="ka-img-opt-progress-bar">
                    <div class="ka-img-opt-progress-fill" id="progress-fill" style="width: 0%;"></div>
                </div>
                <div class="ka-img-opt-progress-info">
                    <span id="progress-percent">0%</span>
                    <span id="progress-count">0 / 0</span>
                </div>

                <!-- Current File -->
                <div class="ka-img-opt-current-file" id="current-file">
                    <span class="dashicons dashicons-format-image"></span>
                    <span id="current-filename"><?php esc_html_e('Preparing...', 'king-addons'); ?></span>
                </div>

                <!-- Live Stats -->
                <div class="ka-img-opt-live-stats">
                    <div class="ka-img-opt-live-stat">
                        <span class="ka-img-opt-live-stat-value" id="live-success">0</span>
                        <span class="ka-img-opt-live-stat-label"><?php esc_html_e('Optimized', 'king-addons'); ?></span>
                    </div>
                    <div class="ka-img-opt-live-stat">
                        <span class="ka-img-opt-live-stat-value" id="live-skipped">0</span>
                        <span class="ka-img-opt-live-stat-label"><?php esc_html_e('Skipped', 'king-addons'); ?></span>
                    </div>
                    <div class="ka-img-opt-live-stat">
                        <span class="ka-img-opt-live-stat-value" id="live-errors">0</span>
                        <span class="ka-img-opt-live-stat-label"><?php esc_html_e('Errors', 'king-addons'); ?></span>
                    </div>
                    <div class="ka-img-opt-live-stat">
                        <span class="ka-img-opt-live-stat-value" id="live-saved">0 KB</span>
                        <span class="ka-img-opt-live-stat-label"><?php esc_html_e('Saved', 'king-addons'); ?></span>
                    </div>
                </div>

                <!-- Live List (Processed/Remaining) -->
                <div class="ka-img-opt-live-list-card" id="ka-img-opt-live-list-card">
                    <div class="ka-img-opt-live-list-head">
                        <div class="ka-img-opt-live-tabs" role="tablist" aria-label="<?php esc_attr_e('Live processing list', 'king-addons'); ?>">
                            <button type="button" class="ka-img-opt-live-tab active" data-view="processed" role="tab" aria-selected="true">
                                <?php esc_html_e('Processed', 'king-addons'); ?>
                                <span class="ka-img-opt-live-tab-count" id="ka-live-processed-count">0</span>
                            </button>
                            <button type="button" class="ka-img-opt-live-tab" data-view="remaining" role="tab" aria-selected="false">
                                <?php esc_html_e('Remaining', 'king-addons'); ?>
                                <span class="ka-img-opt-live-tab-count" id="ka-live-remaining-count">0</span>
                            </button>
                        </div>

                        <div class="ka-img-opt-live-filters" aria-label="<?php esc_attr_e('Filter processed items', 'king-addons'); ?>">
                            <button type="button" class="ka-img-opt-live-filter active" data-filter="all"><?php esc_html_e('All', 'king-addons'); ?></button>
                            <button type="button" class="ka-img-opt-live-filter" data-filter="success"><?php esc_html_e('Optimized', 'king-addons'); ?></button>
                            <button type="button" class="ka-img-opt-live-filter" data-filter="skipped"><?php esc_html_e('Skipped', 'king-addons'); ?></button>
                            <button type="button" class="ka-img-opt-live-filter" data-filter="error"><?php esc_html_e('Failed', 'king-addons'); ?></button>
                        </div>
                    </div>

                    <div class="ka-img-opt-live-list" id="ka-live-list" aria-live="polite"></div>

                    <div class="ka-img-opt-live-pagination">
                        <button type="button" class="ka-btn ka-btn-secondary" id="ka-live-prev"><?php esc_html_e('Prev', 'king-addons'); ?></button>
                        <span class="ka-img-opt-live-page" id="ka-live-page">1 / 1</span>
                        <button type="button" class="ka-btn ka-btn-secondary" id="ka-live-next"><?php esc_html_e('Next', 'king-addons'); ?></button>
                    </div>
                </div>

                <!-- Control Buttons -->
                <div class="ka-img-opt-controls">
                    <button type="button" id="pause-btn" class="ka-btn ka-btn-secondary">
                        <span class="dashicons dashicons-controls-pause"></span>
                        <?php esc_html_e('Pause', 'king-addons'); ?>
                    </button>
                    <button type="button" id="stop-btn" class="ka-btn ka-btn-secondary">
                        <span class="dashicons dashicons-no"></span>
                        <?php esc_html_e('Stop', 'king-addons'); ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Results Section (hidden by default) -->
        <div class="ka-card ka-img-opt-results" id="results-section" style="display: none;">
            <div class="ka-card-header">
                <span class="dashicons dashicons-yes-alt"></span>
                <h2><?php esc_html_e('Optimization Complete', 'king-addons'); ?></h2>
            </div>
            <div class="ka-card-body">
                <!-- Summary -->
                <div class="ka-img-opt-results-summary">
                    <div class="ka-img-opt-result-item success">
                        <span class="ka-img-opt-result-icon dashicons dashicons-yes-alt" aria-hidden="true"></span>
                        <span class="ka-img-opt-result-value" id="result-success">0</span>
                        <span class="ka-img-opt-result-label"><?php esc_html_e('Images Optimized', 'king-addons'); ?></span>
                    </div>
                    <div class="ka-img-opt-result-item saved">
                        <span class="ka-img-opt-result-icon dashicons dashicons-database" aria-hidden="true"></span>
                        <span class="ka-img-opt-result-value" id="result-saved">0 MB</span>
                        <span class="ka-img-opt-result-label"><?php esc_html_e('Space Saved', 'king-addons'); ?></span>
                    </div>
                    <div class="ka-img-opt-result-item percent">
                        <span class="ka-img-opt-result-icon dashicons dashicons-chart-bar" aria-hidden="true"></span>
                        <span class="ka-img-opt-result-value" id="result-percent">0%</span>
                        <span class="ka-img-opt-result-label"><?php esc_html_e('Average Reduction', 'king-addons'); ?></span>
                    </div>
                </div>

                <!-- Actions -->
                <div class="ka-img-opt-actions">
                    <button type="button" id="optimize-more" class="ka-btn ka-btn-primary">
                        <span class="dashicons dashicons-update"></span>
                        <?php esc_html_e('Optimize More', 'king-addons'); ?>
                    </button>
                    <a href="<?php echo esc_url(admin_url('upload.php')); ?>" class="ka-btn ka-btn-secondary">
                        <span class="dashicons dashicons-format-gallery"></span>
                        <?php esc_html_e('View Media Library', 'king-addons'); ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- Image Format Breakdown -->
        <div class="ka-card ka-img-opt-formats">
            <div class="ka-card-header">
                <span class="dashicons dashicons-chart-pie"></span>
                <h2><?php esc_html_e('Image Library Breakdown', 'king-addons'); ?></h2>
            </div>
            <div class="ka-card-body">
                <div class="ka-img-opt-format-list" id="ka-img-opt-format-list">
                    <?php foreach ($format_counts as $format => $count): ?>
                        <div class="ka-img-opt-format-item">
                            <div class="ka-img-opt-format-info">
                                <span class="ka-img-opt-format-name"><?php echo esc_html(strtoupper($format)); ?></span>
                                <span class="ka-img-opt-format-count"><?php echo esc_html(number_format_i18n($count)); ?></span>
                            </div>
                            <div class="ka-img-opt-format-bar">
                                <div class="ka-img-opt-format-bar-fill" style="width: <?php echo esc_attr(min(100, ($count / max($stats['total_images'], 1)) * 100)); ?>%;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab Content: Settings -->
    <div class="ka-img-opt-tab-content" id="tab-settings">
        <div class="ka-card">
            <div class="ka-card-header">
                <span class="dashicons dashicons-admin-generic"></span>
                <h2><?php esc_html_e('Global Settings', 'king-addons'); ?></h2>
            </div>
            <div class="ka-card-body">
                <!-- Default Quality -->
                <div class="ka-row">
                    <div class="ka-row-label"><?php esc_html_e('Default Quality', 'king-addons'); ?></div>
                    <div class="ka-row-field">
                        <div class="ka-img-opt-slider-wrap">
                            <input type="range" id="settings-quality" min="1" max="100" value="<?php echo esc_attr($settings['quality']); ?>" class="ka-img-opt-slider">
                            <output id="settings-quality-output"><?php echo esc_html($settings['quality']); ?>%</output>
                        </div>
                        <p class="ka-row-desc"><?php esc_html_e('Default compression quality for new optimizations.', 'king-addons'); ?></p>
                    </div>
                </div>

                <!-- Skip Small Files -->
                <div class="ka-row">
                    <div class="ka-row-label"><?php esc_html_e('Skip Small Files', 'king-addons'); ?></div>
                    <div class="ka-row-field">
                        <label class="ka-toggle">
                            <input type="checkbox" id="settings-skip-small" <?php checked($settings['skip_small']); ?>>
                            <span class="ka-toggle-slider"></span>
                        </label>
                        <p class="ka-row-desc"><?php esc_html_e('Skip images smaller than 10KB to avoid over-optimization.', 'king-addons'); ?></p>
                    </div>
                </div>

                <!-- Auto Replace URLs -->
                <div class="ka-row">
                    <div class="ka-row-label"><?php esc_html_e('Auto Replace URLs', 'king-addons'); ?></div>
                    <div class="ka-row-field">
                        <label class="ka-toggle">
                            <input type="checkbox" id="settings-auto-replace" <?php checked($settings['auto_replace_urls']); ?>>
                            <span class="ka-toggle-slider"></span>
                        </label>
                        <p class="ka-row-desc"><?php esc_html_e('Automatically update image URLs in database after optimization.', 'king-addons'); ?></p>
                    </div>
                </div>

                <!-- Auto Optimize Uploads (Browser) -->
                <div class="ka-row <?php echo !$is_pro ? 'ka-pro-locked' : ''; ?>">
                    <div class="ka-row-label">
                        <?php esc_html_e('Auto Optimize Uploads', 'king-addons'); ?>
                        <?php if (!$is_pro) : ?>
                            <span class="ka-pro-badge" aria-label="<?php echo esc_attr__('Pro feature', 'king-addons'); ?>">PRO</span>
                        <?php endif; ?>
                    </div>
                    <div class="ka-row-field">
                        <label class="ka-toggle">
                            <input type="checkbox" id="settings-auto-optimize-uploads" <?php checked($is_pro && !empty($settings['auto_optimize_uploads'])); ?> <?php disabled(!$is_pro); ?>>
                            <span class="ka-toggle-slider"></span>
                        </label>
                        <p class="ka-row-desc">
                            <?php esc_html_e('Automatically optimize newly uploaded images in Media Library.', 'king-addons'); ?>
                            <?php if (!$is_pro) : ?>
                                <a href="<?php echo esc_url($upgrade_url); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Upgrade to enable.', 'king-addons'); ?></a>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

                <!-- Resize Large Images -->
                <div class="ka-row">
                    <div class="ka-row-label"><?php esc_html_e('Resize Large Images', 'king-addons'); ?></div>
                    <div class="ka-row-field">
                        <label class="ka-toggle">
                            <input type="checkbox" id="settings-resize-enabled" <?php checked(!empty($settings['resize_enabled'])); ?>>
                            <span class="ka-toggle-slider"></span>
                        </label>
                        <div class="ka-img-opt-resize-options" style="<?php echo !empty($settings['resize_enabled']) ? '' : 'display:none;'; ?>">
                            <label><?php esc_html_e('Max width:', 'king-addons'); ?>
                                <input type="number" id="settings-max-width" value="<?php echo esc_attr($settings['max_width']); ?>" min="100" max="5000"> px
                            </label>
                        </div>
                        <p class="ka-row-desc"><?php esc_html_e('If enabled, images wider than Max width will be resized in the browser before converting to WebP.', 'king-addons'); ?></p>
                    </div>
                </div>

                <!-- Save Button -->
                <div class="ka-img-opt-actions">
                    <button type="button" id="save-settings" class="ka-btn ka-btn-primary">
                        <span class="dashicons dashicons-saved"></span>
                        <?php esc_html_e('Save Settings', 'king-addons'); ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Restore Section -->
        <div class="ka-card">
            <div class="ka-card-header">
                <span class="dashicons dashicons-backup" style="color: #ff9500;"></span>
                <h2><?php esc_html_e('Restore & Maintenance', 'king-addons'); ?></h2>
            </div>
            <div class="ka-card-body">
                <div class="ka-img-opt-restore-section">
                    <h3><?php esc_html_e('Restore All Original Images', 'king-addons'); ?></h3>
                    <p><?php esc_html_e('Restore all images to their original versions and delete optimized WebP files.', 'king-addons'); ?></p>
                    <button type="button" id="restore-all" class="ka-btn ka-btn-secondary danger">
                        <span class="dashicons dashicons-undo"></span>
                        <?php esc_html_e('Restore All Originals', 'king-addons'); ?>
                    </button>

                    <div id="restore-all-progress" style="display:none; margin-top: 14px;">
                        <div class="ka-img-opt-progress-bar">
                            <div class="ka-img-opt-progress-fill" id="restore-progress-fill" style="width: 0%;"></div>
                        </div>
                        <div class="ka-img-opt-progress-info">
                            <span id="restore-progress-percent">0%</span>
                            <span id="restore-progress-count">0 / 0</span>
                        </div>
                        <div class="ka-img-opt-current-file">
                            <span class="dashicons dashicons-format-image"></span>
                            <span id="restore-current-filename"><?php esc_html_e('Preparing...', 'king-addons'); ?></span>
                        </div>
                    </div>
                </div>

                <div class="ka-img-opt-restore-section">
                    <h3><?php esc_html_e('Sync Media Library Metadata', 'king-addons'); ?></h3>
                    <p><?php esc_html_e('If WebP files exist in uploads but Media Library still shows old file sizes or paths, run a sync to update attachment metadata.', 'king-addons'); ?></p>
                    <button type="button" id="sync-media-library" class="ka-btn ka-btn-secondary">
                        <span class="dashicons dashicons-update"></span>
                        <?php esc_html_e('Sync Media Library', 'king-addons'); ?>
                    </button>
                    <button type="button" id="sync-media-library-stop" class="ka-btn ka-btn-secondary" style="display:none; margin-left: 8px;">
                        <span class="dashicons dashicons-no"></span>
                        <?php esc_html_e('Stop', 'king-addons'); ?>
                    </button>

                    <div id="sync-media-library-progress" style="display:none; margin-top: 14px;">
                        <div class="ka-img-opt-progress-bar">
                            <div class="ka-img-opt-progress-fill" id="sync-progress-fill" style="width: 0%;"></div>
                        </div>
                        <div class="ka-img-opt-progress-info">
                            <span id="sync-progress-percent">0%</span>
                            <span id="sync-progress-count">0 / 0</span>
                        </div>
                        <div class="ka-img-opt-current-file">
                            <span class="dashicons dashicons-format-image"></span>
                            <span id="sync-current-filename"><?php esc_html_e('Preparing...', 'king-addons'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- PRO / Free limits modal -->
<div class="ka-img-opt-modal" id="ka-img-opt-pro-modal" style="display:none;" aria-hidden="true">
    <div class="ka-img-opt-modal__overlay" data-ka-modal-close="1"></div>
    <div class="ka-img-opt-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="ka-img-opt-pro-modal-title">
        <div class="ka-img-opt-modal__head">
            <div class="ka-img-opt-modal__icon" aria-hidden="true">
                <span class="dashicons dashicons-star-filled"></span>
            </div>
            <div class="ka-img-opt-modal__titles">
                <div class="ka-img-opt-modal__title" id="ka-img-opt-pro-modal-title"><?php esc_html_e('Free Plan Limit Reached', 'king-addons'); ?></div>
                <div class="ka-img-opt-modal__sub" id="ka-img-opt-pro-modal-sub">
                    <?php esc_html_e('You can optimize up to 200 images per month in the Free plan.', 'king-addons'); ?>
                </div>
            </div>
        </div>

        <div class="ka-img-opt-modal__body">
            <div class="ka-img-opt-modal__quota">
                <span class="ka-img-opt-modal__quota-k"><?php esc_html_e('Remaining this month:', 'king-addons'); ?></span>
                <span class="ka-img-opt-modal__quota-v"><span id="ka-img-opt-modal-remaining">0</span> / <span id="ka-img-opt-modal-limit">200</span></span>
            </div>
            <ul class="ka-img-opt-modal__list">
                <li><?php esc_html_e('200 optimizations per month', 'king-addons'); ?></li>
                <li><?php esc_html_e('Auto Optimize Uploads is a PRO feature', 'king-addons'); ?></li>
                <li><?php esc_html_e('Upgrade to Unlimited to remove limits', 'king-addons'); ?></li>
            </ul>
        </div>

        <div class="ka-img-opt-modal__actions">
            <a class="ka-btn ka-btn-primary" id="ka-img-opt-modal-upgrade" href="<?php echo esc_url($upgrade_url); ?>" target="_blank" rel="noopener noreferrer">
                <?php esc_html_e('Upgrade to Unlimited', 'king-addons'); ?>
            </a>
            <button type="button" class="ka-btn ka-btn-secondary" data-ka-modal-close="1"><?php esc_html_e('Not now', 'king-addons'); ?></button>
        </div>
    </div>
</div>

<script>
// Theme toggle handler
document.addEventListener('DOMContentLoaded', function() {
    const segment = document.getElementById('ka-v3-theme-segment');
    if (segment) {
        const buttons = segment.querySelectorAll('.ka-v3-segmented-btn');

        function setPressedState(activeMode) {
            segment.setAttribute('data-active', activeMode);
            buttons.forEach((btn) => {
                const theme = btn.getAttribute('data-theme');
                btn.setAttribute('aria-pressed', theme === activeMode ? 'true' : 'false');
            });
        }

        function applyThemeMode(mode) {
            const mql = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
            const isDark = mode === 'auto' ? !!(mql && mql.matches) : mode === 'dark';
            document.documentElement.classList.toggle('ka-v3-dark', isDark);
            document.body.classList.toggle('ka-v3-dark', isDark);
        }

        buttons.forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const mode = (this.getAttribute('data-theme') || 'dark').toString();

                setPressedState(mode);
                applyThemeMode(mode);

                // Save preference (same handler as Dashboard V3)
                try {
                    const body = new URLSearchParams();
                    body.set('action', 'king_addons_save_dashboard_ui');
                    body.set('nonce', '<?php echo esc_js(wp_create_nonce('king_addons_dashboard_ui')); ?>');
                    body.set('key', 'theme_mode');
                    body.set('value', mode);

                    fetch(ajaxurl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                        body: body.toString(),
                        credentials: 'same-origin'
                    });
                } catch (e) {}
            });
        });

        // Ensure correct state on load
        const initial = (segment.getAttribute('data-active') || 'dark').toString();
        setPressedState(initial);
        applyThemeMode(initial);
    }

    // Tab switching
    document.querySelectorAll('.ka-img-opt-tab').forEach(function(tab) {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.ka-img-opt-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.ka-img-opt-tab-content').forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            document.getElementById('tab-' + this.dataset.tab).classList.add('active');
        });
    });
});
</script>
