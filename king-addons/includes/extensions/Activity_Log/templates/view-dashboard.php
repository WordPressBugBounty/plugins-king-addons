<?php
/**
 * Activity Log dashboard view.
 *
 * @package King_Addons
 */

if (!defined('ABSPATH')) {
    exit;
}

$stats = $this->get_dashboard_stats();
$series = $this->get_events_over_time(14);
$top_events = $this->get_top_events();
$top_users = $this->get_top_users();
$max_count = 0;
foreach ($series as $point) {
    $max_count = max($max_count, (int) $point['count']);
}
?>

<div class="ka-stats-grid">
    <div class="ka-stat-card">
        <div class="ka-stat-label"><?php esc_html_e('Events (24h)', 'king-addons'); ?></div>
        <div class="ka-stat-value"><?php echo esc_html(number_format_i18n($stats['total_24h'])); ?></div>
        <div class="ka-stat-note"><?php esc_html_e('Total activity logged', 'king-addons'); ?></div>
    </div>
    <div class="ka-stat-card">
        <div class="ka-stat-label"><?php esc_html_e('Failed Logins (24h)', 'king-addons'); ?></div>
        <div class="ka-stat-value"><?php echo esc_html(number_format_i18n($stats['failed_24h'])); ?></div>
        <div class="ka-stat-note"><?php esc_html_e('Auth warnings', 'king-addons'); ?></div>
    </div>
    <div class="ka-stat-card">
        <div class="ka-stat-label"><?php esc_html_e('Critical Events (7d)', 'king-addons'); ?></div>
        <div class="ka-stat-value"><?php echo esc_html(number_format_i18n($stats['critical_7d'])); ?></div>
        <div class="ka-stat-note"><?php esc_html_e('High severity changes', 'king-addons'); ?></div>
    </div>
    <div class="ka-stat-card">
        <div class="ka-stat-label"><?php esc_html_e('Unique Users (7d)', 'king-addons'); ?></div>
        <div class="ka-stat-value"><?php echo esc_html(number_format_i18n($stats['unique_users_7d'])); ?></div>
        <div class="ka-stat-note"><?php esc_html_e('Distinct actors', 'king-addons'); ?></div>
    </div>
</div>

<div class="kng-activity-dashboard-grid">
    <div class="ka-card">
        <div class="ka-card-header">
            <span class="dashicons dashicons-chart-bar purple"></span>
            <h2><?php esc_html_e('Events over 14 days', 'king-addons'); ?></h2>
        </div>
        <div class="ka-card-body">
            <div class="kng-activity-chart">
                <?php foreach ($series as $point) :
                    $height = $max_count > 0 ? (int) round(($point['count'] / $max_count) * 100) : 0;
                    $label = wp_date('M j', strtotime($point['date'] . ' UTC'));
                    ?>
                    <div class="kng-activity-bar" style="--kng-bar-height: <?php echo esc_attr($height); ?>%;">
                        <span class="kng-activity-bar-count"><?php echo esc_html(number_format_i18n($point['count'])); ?></span>
                        <small class="kng-activity-bar-label"><?php echo esc_html($label); ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="ka-card">
        <div class="ka-card-header">
            <span class="dashicons dashicons-analytics purple"></span>
            <h2><?php esc_html_e('Top Event Types', 'king-addons'); ?></h2>
        </div>
        <div class="ka-card-body">
            <?php if (empty($top_events)) : ?>
                <div class="ka-empty">
                    <span class="dashicons dashicons-clipboard"></span>
                    <p><?php esc_html_e('No events yet.', 'king-addons'); ?></p>
                </div>
            <?php else : ?>
                <ul class="kng-activity-list">
                    <?php foreach ($top_events as $row) : ?>
                        <li>
                            <span><?php echo esc_html($row->event_key); ?></span>
                            <strong><?php echo esc_html(number_format_i18n($row->total)); ?></strong>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <div class="ka-card">
        <div class="ka-card-header">
            <span class="dashicons dashicons-admin-users purple"></span>
            <h2><?php esc_html_e('Top Users', 'king-addons'); ?></h2>
        </div>
        <div class="ka-card-body">
            <?php if (empty($top_users)) : ?>
                <div class="ka-empty">
                    <span class="dashicons dashicons-admin-users"></span>
                    <p><?php esc_html_e('No user activity yet.', 'king-addons'); ?></p>
                </div>
            <?php else : ?>
                <ul class="kng-activity-list">
                    <?php foreach ($top_users as $row) : ?>
                        <li>
                            <span><?php echo esc_html($row->user_login); ?></span>
                            <strong><?php echo esc_html(number_format_i18n($row->total)); ?></strong>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!$is_pro) : ?>
    <div class="ka-upgrade-card">
        <h2><?php esc_html_e('Upgrade to Activity Log Pro', 'king-addons'); ?></h2>
        <p><?php esc_html_e('Unlock diff viewer, tamper detection, alerts, and advanced filters.', 'king-addons'); ?></p>
        <ul>
            <li><?php esc_html_e('Option changes, WooCommerce, and King Addons events', 'king-addons'); ?></li>
            <li><?php esc_html_e('Real-time alerts via email and webhooks', 'king-addons'); ?></li>
            <li><?php esc_html_e('Extended retention and export automation', 'king-addons'); ?></li>
        </ul>
        <a href="https://kingaddons.com/pricing/" target="_blank" class="ka-btn ka-btn-pink">
            <?php esc_html_e('Upgrade to Pro', 'king-addons'); ?>
        </a>
    </div>
<?php endif; ?>
