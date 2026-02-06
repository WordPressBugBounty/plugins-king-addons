<?php
/**
 * Fomo Notifications - Analytics Template
 *
 * @package King_Addons
 */

defined('ABSPATH') || exit;

$is_pro = king_addons_freemius()->can_use_premium_code();
$stats = King_Addons\Fomo_Notifications::instance()->get_total_stats();

// Get top performing notifications
$top_notifications = get_posts([
    'post_type' => 'kng_fomo_notif',
    'posts_per_page' => 10,
    'meta_key' => '_kng_fomo_clicks',
    'orderby' => 'meta_value_num',
    'order' => 'DESC'
]);
?>

<div class="kng-fomo-admin">
    
    <!-- Navigation -->
    <nav class="kng-fomo-nav">
        <a href="<?php echo esc_url(admin_url('admin.php?page=king-addons-fomo')); ?>" class="kng-fomo-nav-item">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"></rect><rect x="14" y="3" width="7" height="5"></rect><rect x="14" y="12" width="7" height="9"></rect><rect x="3" y="16" width="7" height="5"></rect></svg>
            <?php esc_html_e('Dashboard', 'king-addons'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=king-addons-fomo&view=list')); ?>" class="kng-fomo-nav-item">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
            <?php esc_html_e('Notifications', 'king-addons'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=king-addons-fomo&view=wizard')); ?>" class="kng-fomo-nav-item">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>
            <?php esc_html_e('Add New', 'king-addons'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=king-addons-fomo&view=analytics')); ?>" class="kng-fomo-nav-item is-active">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
            <?php esc_html_e('Analytics', 'king-addons'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=king-addons-fomo&view=settings')); ?>" class="kng-fomo-nav-item">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
            <?php esc_html_e('Settings', 'king-addons'); ?>
        </a>
    </nav>

    <!-- Page Header -->
    <div class="kng-fomo-section">
        <div class="kng-fomo-section-header">
            <div>
                <h1 class="kng-fomo-section-title"><?php esc_html_e('Analytics', 'king-addons'); ?></h1>
                <p class="kng-fomo-section-subtitle"><?php esc_html_e('Track the performance of your notifications', 'king-addons'); ?></p>
            </div>
            <div class="kng-fomo-chart-filters">
                <select class="kng-fomo-select kng-fomo-input--sm kng-fomo-date-range" style="width: auto;">
                    <option value="7days"><?php esc_html_e('Last 7 Days', 'king-addons'); ?></option>
                    <option value="30days"><?php esc_html_e('Last 30 Days', 'king-addons'); ?></option>
                    <option value="90days"><?php esc_html_e('Last 90 Days', 'king-addons'); ?></option>
                    <option value="year"><?php esc_html_e('This Year', 'king-addons'); ?></option>
                </select>
            </div>
        </div>
    </div>

    <!-- KPIs -->
    <div class="kng-fomo-kpis">
        <div class="kng-fomo-kpi">
            <div class="kng-fomo-kpi-icon kng-fomo-kpi-icon--views">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
            </div>
            <div class="kng-fomo-kpi-value" data-kpi="views"><?php echo esc_html(number_format_i18n($stats['views'])); ?></div>
            <div class="kng-fomo-kpi-label"><?php esc_html_e('Total Views', 'king-addons'); ?></div>
            <?php if ($stats['views_change'] != 0): ?>
            <span class="kng-fomo-kpi-change kng-fomo-kpi-change--<?php echo $stats['views_change'] > 0 ? 'up' : 'down'; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="<?php echo $stats['views_change'] > 0 ? '18 15 12 9 6 15' : '6 9 12 15 18 9'; ?>"></polyline></svg>
                <?php echo esc_html(abs($stats['views_change']) . '%'); ?>
            </span>
            <?php endif; ?>
        </div>

        <div class="kng-fomo-kpi">
            <div class="kng-fomo-kpi-icon kng-fomo-kpi-icon--clicks">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"></path></svg>
            </div>
            <div class="kng-fomo-kpi-value" data-kpi="clicks"><?php echo esc_html(number_format_i18n($stats['clicks'])); ?></div>
            <div class="kng-fomo-kpi-label"><?php esc_html_e('Total Clicks', 'king-addons'); ?></div>
            <?php if ($stats['clicks_change'] != 0): ?>
            <span class="kng-fomo-kpi-change kng-fomo-kpi-change--<?php echo $stats['clicks_change'] > 0 ? 'up' : 'down'; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="<?php echo $stats['clicks_change'] > 0 ? '18 15 12 9 6 15' : '6 9 12 15 18 9'; ?>"></polyline></svg>
                <?php echo esc_html(abs($stats['clicks_change']) . '%'); ?>
            </span>
            <?php endif; ?>
        </div>

        <div class="kng-fomo-kpi">
            <div class="kng-fomo-kpi-icon kng-fomo-kpi-icon--ctr">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
            </div>
            <div class="kng-fomo-kpi-value" data-kpi="ctr"><?php echo esc_html($stats['ctr'] . '%'); ?></div>
            <div class="kng-fomo-kpi-label"><?php esc_html_e('Click-Through Rate', 'king-addons'); ?></div>
        </div>
    </div>

    <!-- Chart -->
    <div class="kng-fomo-section">
        <div class="kng-fomo-chart-wrap">
            <div class="kng-fomo-chart-header">
                <h3 class="kng-fomo-chart-title"><?php esc_html_e('Performance Over Time', 'king-addons'); ?></h3>
            </div>
            <div class="kng-fomo-chart-canvas">
                <canvas id="kng-fomo-chart"></canvas>
            </div>
        </div>
    </div>

    <!-- Top Performing -->
    <?php if (!empty($top_notifications)): ?>
    <div class="kng-fomo-section">
        <div class="kng-fomo-section-header">
            <div>
                <h2 class="kng-fomo-section-title"><?php esc_html_e('Top Performing Notifications', 'king-addons'); ?></h2>
                <p class="kng-fomo-section-subtitle"><?php esc_html_e('Notifications with the most engagement', 'king-addons'); ?></p>
            </div>
        </div>

        <div class="kng-fomo-table-wrap">
            <table class="kng-fomo-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Notification', 'king-addons'); ?></th>
                        <th><?php esc_html_e('Type', 'king-addons'); ?></th>
                        <th><?php esc_html_e('Views', 'king-addons'); ?></th>
                        <th><?php esc_html_e('Clicks', 'king-addons'); ?></th>
                        <th><?php esc_html_e('CTR', 'king-addons'); ?></th>
                        <th><?php esc_html_e('Status', 'king-addons'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_notifications as $notification):
                        $type = get_post_meta($notification->ID, '_kng_fomo_type', true);
                        $status = get_post_meta($notification->ID, '_kng_fomo_status', true) ?: 'disabled';
                        $views = (int) get_post_meta($notification->ID, '_kng_fomo_views', true);
                        $clicks = (int) get_post_meta($notification->ID, '_kng_fomo_clicks', true);
                        $ctr = $views > 0 ? round(($clicks / $views) * 100, 1) : 0;
                    ?>
                    <tr>
                        <td>
                            <div class="kng-fomo-table-title">
                                <span class="kng-fomo-table-preview">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                                </span>
                                <?php echo esc_html($notification->post_title); ?>
                            </div>
                        </td>
                        <td><?php echo esc_html(ucfirst(str_replace('_', ' ', $type))); ?></td>
                        <td><?php echo esc_html(number_format_i18n($views)); ?></td>
                        <td><?php echo esc_html(number_format_i18n($clicks)); ?></td>
                        <td>
                            <span style="color: <?php echo $ctr >= 5 ? 'var(--kng-fomo-success)' : ($ctr >= 2 ? 'var(--kng-fomo-warning)' : 'var(--kng-fomo-text-secondary)'); ?>; font-weight: 600;">
                                <?php echo esc_html($ctr . '%'); ?>
                            </span>
                        </td>
                        <td>
                            <span class="kng-fomo-status kng-fomo-status--<?php echo esc_attr($status); ?>">
                                <span class="kng-fomo-status-dot"></span>
                                <?php echo $status === 'enabled' ? esc_html__('Active', 'king-addons') : esc_html__('Inactive', 'king-addons'); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Pro Upsell (for advanced analytics) -->
    <?php if (!$is_pro): ?>
    <div class="kng-fomo-upsell">
        <div class="kng-fomo-upsell-content">
            <span class="kng-fomo-upsell-badge">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                <?php esc_html_e('Pro', 'king-addons'); ?>
            </span>
            <h3 class="kng-fomo-upsell-title"><?php esc_html_e('Advanced Analytics', 'king-addons'); ?></h3>
            <p class="kng-fomo-upsell-desc"><?php esc_html_e('Get detailed analytics with conversion tracking, A/B testing results, geographic data, device breakdown, and more.', 'king-addons'); ?></p>
            <div class="kng-fomo-upsell-features">
                <span class="kng-fomo-upsell-feature">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <?php esc_html_e('Conversion Tracking', 'king-addons'); ?>
                </span>
                <span class="kng-fomo-upsell-feature">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <?php esc_html_e('Device Breakdown', 'king-addons'); ?>
                </span>
                <span class="kng-fomo-upsell-feature">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <?php esc_html_e('Export Reports', 'king-addons'); ?>
                </span>
            </div>
            <a href="<?php echo esc_url(king_addons_freemius()->get_upgrade_url()); ?>" class="kng-fomo-btn kng-fomo-btn--primary">
                <?php esc_html_e('Upgrade to Pro', 'king-addons'); ?>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </a>
        </div>
    </div>
    <?php endif; ?>

</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
