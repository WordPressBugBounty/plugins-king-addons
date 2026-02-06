<?php
/**
 * Wishlist Analytics page - V3 Premium style inspired design.
 *
 * @package King_Addons
 */

use King_Addons\Wishlist\Wishlist_Service;

if (!defined('ABSPATH')) {
    exit;
}

// Theme mode is per-user
$theme_mode = get_user_meta(get_current_user_id(), 'king_addons_theme_mode', true);
$allowed_theme_modes = ['dark', 'light', 'auto'];
if (!in_array($theme_mode, $allowed_theme_modes, true)) {
    $theme_mode = 'dark';
}

$is_pro = function_exists('king_addons_freemius') && king_addons_freemius()->can_use_premium_code__premium_only();

// Get date filter values
$date_from = isset($_GET['date_from']) ? sanitize_text_field(wp_unslash($_GET['date_from'])) : '';
$date_to = isset($_GET['date_to']) ? sanitize_text_field(wp_unslash($_GET['date_to'])) : '';

// Validate dates
if ($date_from && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)) {
    $date_from = '';
}
if ($date_to && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to)) {
    $date_to = '';
}

// Handle CSV export
if ($is_pro && isset($_GET['action']) && 'export_csv' === $_GET['action']) {
    if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'] ?? '')), 'king_addons_wishlist_export')) {
        wp_die(esc_html__('Invalid request.', 'king-addons'));
    }

    $service = new Wishlist_Service();
    $stats = $service->get_product_stats($date_from ?: null, $date_to ?: null);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=wishlist-analytics-' . gmdate('Y-m-d') . '.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, [
        esc_html__('Product ID', 'king-addons'),
        esc_html__('Product Name', 'king-addons'),
        esc_html__('Wishlist Adds', 'king-addons'),
        esc_html__('Conversions', 'king-addons'),
        esc_html__('Revenue', 'king-addons'),
        esc_html__('Conversion Rate', 'king-addons'),
        esc_html__('Price', 'king-addons'),
        esc_html__('Stock Status', 'king-addons'),
    ]);

    foreach ($stats as $row) {
        $product = function_exists('wc_get_product') ? wc_get_product($row['product_id']) : null;
        $conv_rate = $row['adds'] > 0 ? round(($row['conversions'] / $row['adds']) * 100, 1) : 0;
        fputcsv($output, [
            $row['product_id'],
            $product ? $product->get_name() : sprintf(esc_html__('Product #%d', 'king-addons'), $row['product_id']),
            $row['adds'],
            $row['conversions'],
            $row['revenue'],
            $conv_rate . '%',
            $product ? $product->get_price() : '',
            $product ? ($product->is_in_stock() ? 'In Stock' : 'Out of Stock') : '',
        ]);
    }

    fclose($output);
    exit;
}

// Enqueue the shared CSS
$css_url = KING_ADDONS_URL . 'includes/admin/layouts/shared/admin-v3-styles.css';
$css_path = KING_ADDONS_PATH . 'includes/admin/layouts/shared/admin-v3-styles.css';
$css_version = file_exists($css_path) ? filemtime($css_path) : KING_ADDONS_VERSION;
?>

<link rel="stylesheet" href="<?php echo esc_url($css_url); ?>?v=<?php echo esc_attr($css_version); ?>">

<script>
document.body.classList.add('ka-admin-v3');
(function() {
    const mode = '<?php echo esc_js($theme_mode); ?>';
    const mql = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
    const isDark = mode === 'auto' ? !!(mql && mql.matches) : mode === 'dark';
    document.body.classList.toggle('ka-v3-dark', isDark);
    document.documentElement.classList.toggle('ka-v3-dark', isDark);
})();
</script>

<div class="ka-admin-wrap">
    <!-- Header -->
    <div class="ka-admin-header">
        <div class="ka-admin-header-left">
            <div class="ka-admin-header-icon pink">
                <span class="dashicons dashicons-chart-bar"></span>
            </div>
            <div>
                <h1 class="ka-admin-title"><?php esc_html_e('Wishlist Analytics', 'king-addons'); ?></h1>
                <p class="ka-admin-subtitle"><?php esc_html_e('Track wishlist performance and conversions.', 'king-addons'); ?></p>
            </div>
        </div>
        <div class="ka-admin-header-actions">
            <div class="ka-v3-segmented" id="ka-v3-theme-segment" role="radiogroup" aria-label="<?php echo esc_attr(esc_html__('Theme', 'king-addons')); ?>" data-active="<?php echo esc_attr($theme_mode); ?>">
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
            <a href="<?php echo esc_url(admin_url('admin.php?page=king-addons-wishlist')); ?>" class="ka-btn ka-btn-secondary">
                <span class="dashicons dashicons-arrow-left-alt"></span>
                <?php esc_html_e('Wishlist Settings', 'king-addons'); ?>
            </a>
        </div>
    </div>

    <?php if (!$is_pro): ?>
        <div class="ka-pro-notice">
            <h2><?php esc_html_e('Unlock Wishlist Analytics', 'king-addons'); ?></h2>
            <p><?php esc_html_e('Get detailed insights into wishlist performance with conversion tracking, date filters, and CSV exports.', 'king-addons'); ?></p>
            <a href="https://kingaddons.com/pricing/" target="_blank" class="ka-btn ka-btn-pink">
                <?php esc_html_e('Upgrade to Pro', 'king-addons'); ?>
            </a>
        </div>
    <?php return; endif; ?>

    <?php
    $service = new Wishlist_Service();
    $stats = $service->get_product_stats($date_from ?: null, $date_to ?: null);
    $summary = $service->get_stats_summary($date_from ?: null, $date_to ?: null);

    $base_url = admin_url('admin.php?page=king-addons-wishlist-analytics');
    $export_url = wp_nonce_url(
        add_query_arg([
            'action' => 'export_csv',
            'date_from' => $date_from,
            'date_to' => $date_to,
        ], $base_url),
        'king_addons_wishlist_export'
    );
    ?>

    <!-- Stats Grid -->
    <div class="ka-stats-grid">
        <div class="ka-stat-card">
            <div class="ka-stat-label"><?php esc_html_e('Total Adds', 'king-addons'); ?></div>
            <div class="ka-stat-value"><?php echo esc_html(number_format_i18n($summary['total_adds'])); ?></div>
        </div>
        <div class="ka-stat-card">
            <div class="ka-stat-label"><?php esc_html_e('Conversions', 'king-addons'); ?></div>
            <div class="ka-stat-value"><?php echo esc_html(number_format_i18n($summary['total_conversions'])); ?></div>
        </div>
        <div class="ka-stat-card">
            <div class="ka-stat-label"><?php esc_html_e('Revenue', 'king-addons'); ?></div>
            <div class="ka-stat-value"><?php echo function_exists('wc_price') ? wp_kses_post(wc_price($summary['total_revenue'])) : esc_html('$' . number_format($summary['total_revenue'], 2)); ?></div>
        </div>
        <div class="ka-stat-card">
            <div class="ka-stat-label"><?php esc_html_e('Conversion Rate', 'king-addons'); ?></div>
            <div class="ka-stat-value <?php echo $summary['conversion_rate'] >= 5 ? 'good' : ''; ?>"><?php echo esc_html($summary['conversion_rate']); ?>%</div>
        </div>
        <div class="ka-stat-card">
            <div class="ka-stat-label"><?php esc_html_e('Unique Users', 'king-addons'); ?></div>
            <div class="ka-stat-value"><?php echo esc_html(number_format_i18n($summary['unique_users'])); ?></div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="ka-filter-bar">
        <form method="get" action="<?php echo esc_url($base_url); ?>" style="display: contents;">
            <input type="hidden" name="page" value="king-addons-wishlist-analytics">
            <label>
                <?php esc_html_e('From', 'king-addons'); ?>
                <input type="date" name="date_from" value="<?php echo esc_attr($date_from); ?>">
            </label>
            <label>
                <?php esc_html_e('To', 'king-addons'); ?>
                <input type="date" name="date_to" value="<?php echo esc_attr($date_to); ?>">
            </label>
            <button type="submit" class="button"><?php esc_html_e('Filter', 'king-addons'); ?></button>
            <?php if ($date_from || $date_to): ?>
                <a href="<?php echo esc_url($base_url); ?>" class="button"><?php esc_html_e('Reset', 'king-addons'); ?></a>
            <?php endif; ?>
        </form>
        <a href="<?php echo esc_url($export_url); ?>" class="ka-btn ka-btn-pink ka-export-btn">
            <span class="dashicons dashicons-download"></span>
            <?php esc_html_e('Export CSV', 'king-addons'); ?>
        </a>
    </div>

    <!-- Products Table -->
    <div class="ka-card">
        <div class="ka-card-header">
            <span class="dashicons dashicons-products pink"></span>
            <h2><?php esc_html_e('Product Performance', 'king-addons'); ?></h2>
        </div>
        <div class="ka-card-body" style="padding: 0;">
            <?php if (empty($stats)): ?>
                <div class="ka-empty">
                    <span class="dashicons dashicons-heart"></span>
                    <p><?php esc_html_e('No wishlist activity yet.', 'king-addons'); ?></p>
                </div>
            <?php else: ?>
                <table class="ka-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Product', 'king-addons'); ?></th>
                            <th class="text-center"><?php esc_html_e('Adds', 'king-addons'); ?></th>
                            <th class="text-center"><?php esc_html_e('Conversions', 'king-addons'); ?></th>
                            <th class="text-center"><?php esc_html_e('Revenue', 'king-addons'); ?></th>
                            <th class="text-center"><?php esc_html_e('Conv. Rate', 'king-addons'); ?></th>
                            <th><?php esc_html_e('Price', 'king-addons'); ?></th>
                            <th><?php esc_html_e('Stock', 'king-addons'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats as $row):
                            $product = function_exists('wc_get_product') ? wc_get_product($row['product_id']) : null;
                            $title = $product ? $product->get_name() : sprintf(esc_html__('Product #%d', 'king-addons'), $row['product_id']);
                            $link = $product ? get_edit_post_link($product->get_id()) : '';
                            $conv_rate = $row['adds'] > 0 ? round(($row['conversions'] / $row['adds']) * 100, 1) : 0;
                        ?>
                            <tr>
                                <td>
                                    <div class="ka-product-cell">
                                        <?php if ($product): ?>
                                            <?php echo $product->get_image([40, 40]); ?>
                                        <?php endif; ?>
                                        <?php if ($link): ?>
                                            <a href="<?php echo esc_url($link); ?>"><?php echo esc_html($title); ?></a>
                                        <?php else: ?>
                                            <span><?php echo esc_html($title); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="text-center font-bold"><?php echo esc_html($row['adds']); ?></td>
                                <td class="text-center"><?php echo esc_html($row['conversions']); ?></td>
                                <td class="text-center"><?php echo function_exists('wc_price') ? wp_kses_post(wc_price($row['revenue'])) : esc_html('$' . number_format($row['revenue'], 2)); ?></td>
                                <td class="text-center">
                                    <span class="ka-badge <?php echo $conv_rate >= 5 ? 'ka-badge-success' : ($conv_rate > 0 ? 'ka-badge-warning' : 'ka-badge-neutral'); ?>">
                                        <?php echo esc_html($conv_rate); ?>%
                                    </span>
                                </td>
                                <td><?php echo $product ? wp_kses_post($product->get_price_html()) : '-'; ?></td>
                                <td>
                                    <?php if ($product): ?>
                                        <?php if ($product->is_in_stock()): ?>
                                            <span class="ka-stock ka-stock-in">
                                                <span class="dashicons dashicons-yes-alt"></span>
                                                <?php esc_html_e('In Stock', 'king-addons'); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="ka-stock ka-stock-out">
                                                <span class="dashicons dashicons-dismiss"></span>
                                                <?php esc_html_e('Out of Stock', 'king-addons'); ?>
                                            </span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
(function($) {
    'use strict';
    
    $(document).ready(function() {
        const ajaxUrl = '<?php echo esc_url(admin_url('admin-ajax.php')); ?>';
        const nonce = '<?php echo esc_js(wp_create_nonce('king_addons_dashboard_ui')); ?>';

        // Theme segmented control (dashboard-style)
        const $themeSegment = $('#ka-v3-theme-segment');
        const $themeSegmentButtons = $themeSegment.find('.ka-v3-segmented-btn');
        const themeMql = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
        let themeMode = ($themeSegment.attr('data-active') || 'dark').toString();
        let themeMqlHandler = null;

        function saveUISetting(key, value) {
            $.post(ajaxUrl, {
                action: 'king_addons_save_dashboard_ui',
                nonce: nonce,
                key: key,
                value: value
            });
        }

        function updateSegment(mode) {
            $themeSegment.attr('data-active', mode);
            $themeSegmentButtons.each(function() {
                const theme = $(this).data('theme');
                $(this).attr('aria-pressed', theme === mode ? 'true' : 'false');
            });
        }

        function applyThemeClass(isDark) {
            $('body').toggleClass('ka-v3-dark', isDark);
            document.documentElement.classList.toggle('ka-v3-dark', isDark);
        }

        function setThemeMode(mode, save) {
            themeMode = mode;
            updateSegment(mode);

            if (themeMqlHandler && themeMql) {
                if (themeMql.removeEventListener) {
                    themeMql.removeEventListener('change', themeMqlHandler);
                } else if (themeMql.removeListener) {
                    themeMql.removeListener(themeMqlHandler);
                }
                themeMqlHandler = null;
            }

            if (mode === 'auto') {
                applyThemeClass(!!(themeMql && themeMql.matches));
                themeMqlHandler = function(e) {
                    if (themeMode !== 'auto') {
                        return;
                    }
                    applyThemeClass(!!e.matches);
                };
                if (themeMql) {
                    if (themeMql.addEventListener) {
                        themeMql.addEventListener('change', themeMqlHandler);
                    } else if (themeMql.addListener) {
                        themeMql.addListener(themeMqlHandler);
                    }
                }
            } else {
                applyThemeClass(mode === 'dark');
            }

            if (save) {
                saveUISetting('theme_mode', mode);
            }
        }

        $themeSegment.on('click', '.ka-v3-segmented-btn', function(e) {
            e.preventDefault();
            const mode = ($(this).data('theme') || 'dark').toString();
            setThemeMode(mode, true);
        });

        // Ensure mode applies for Auto (listener + system state)
        setThemeMode(themeMode, false);
    });
})(jQuery);
</script>
