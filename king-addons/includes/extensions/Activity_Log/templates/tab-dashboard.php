<?php
/**
 * Activity Log - Dashboard Tab.
 *
 * @package King_Addons
 */

if (!defined('ABSPATH')) {
    exit;
}

$activity_log = \King_Addons\Activity_Log::instance();
$kpis = $activity_log->get_kpis();
$top_events = $activity_log->get_top_events();
$top_users = $activity_log->get_top_users();
$is_pro = $activity_log->is_pro();
?>

<!-- Bento KPI Cards -->
<div class="ka-al-bento">
    <div class="ka-al-bento-card">
        <div class="ka-al-bento-icon ka-al-bento-icon--blue">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
        </div>
        <div>
            <span class="ka-al-bento-value"><?php echo esc_html(number_format_i18n($kpis['events_24h'])); ?></span>
            <p class="ka-al-bento-desc"><?php esc_html_e('Events (24h)', 'king-addons'); ?></p>
        </div>
    </div>

    <div class="ka-al-bento-card">
        <div class="ka-al-bento-icon ka-al-bento-icon--orange">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>
        <div>
            <span class="ka-al-bento-value"><?php echo esc_html(number_format_i18n($kpis['failed_logins'])); ?></span>
            <p class="ka-al-bento-desc"><?php esc_html_e('Failed Logins (24h)', 'king-addons'); ?></p>
        </div>
    </div>

    <div class="ka-al-bento-card">
        <div class="ka-al-bento-icon ka-al-bento-icon--red">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <div>
            <span class="ka-al-bento-value"><?php echo esc_html(number_format_i18n($kpis['critical_7d'])); ?></span>
            <p class="ka-al-bento-desc"><?php esc_html_e('Critical Events (7d)', 'king-addons'); ?></p>
        </div>
    </div>

    <div class="ka-al-bento-card">
        <div class="ka-al-bento-icon ka-al-bento-icon--green">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
        </div>
        <div>
            <span class="ka-al-bento-value"><?php echo esc_html(number_format_i18n($kpis['unique_users'])); ?></span>
            <p class="ka-al-bento-desc"><?php esc_html_e('Active Users (7d)', 'king-addons'); ?></p>
        </div>
    </div>
</div>

<!-- Charts Section (Pro) -->
<?php if (!$is_pro): ?>
    <div class="ka-al-pro-section">
        <div class="ka-al-pro-card">
            <div class="ka-al-pro-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
            </div>
            <h3><?php esc_html_e('Activity Charts & Analytics', 'king-addons'); ?></h3>
            <p><?php esc_html_e('Visualize activity trends, suspicious patterns, and audit insights with interactive charts.', 'king-addons'); ?>
            </p>
            <a href="https://kingaddons.com/pricing/?utm_source=kng-activity-log-charts&utm_medium=plugin&utm_campaign=kng"
                class="ka-al-pro-btn" target="_blank">
                <?php esc_html_e('Upgrade to Pro', 'king-addons'); ?>
            </a>
        </div>
    </div>
<?php endif; ?>

<!-- Top Events & Users -->
<div class="ka-al-dashboard-grid">
    <div class="ka-al-card">
        <div class="ka-al-card-header">
            <h3><?php esc_html_e('Top Events (7 days)', 'king-addons'); ?></h3>
        </div>
        <div class="ka-al-card-body">
            <?php if (empty($top_events)): ?>
                <p class="ka-al-empty"><?php esc_html_e('No events recorded yet.', 'king-addons'); ?></p>
            <?php else: ?>
                <table class="ka-al-mini-table">
                    <tbody>
                        <?php foreach ($top_events as $event): ?>
                            <tr>
                                <td><?php echo esc_html(\King_Addons\Activity_Log_Event_Types::get_label($event->event_key)); ?>
                                </td>
                                <td class="ka-al-count"><?php echo esc_html(number_format_i18n($event->count)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="ka-al-card">
        <div class="ka-al-card-header">
            <h3><?php esc_html_e('Top Users (7 days)', 'king-addons'); ?></h3>
        </div>
        <div class="ka-al-card-body">
            <?php if (empty($top_users)): ?>
                <p class="ka-al-empty"><?php esc_html_e('No user activity recorded yet.', 'king-addons'); ?></p>
            <?php else: ?>
                <table class="ka-al-mini-table">
                    <tbody>
                        <?php foreach ($top_users as $user): ?>
                            <tr>
                                <td>
                                    <?php echo get_avatar($user->user_id, 28, '', '', ['class' => 'ka-al-avatar']); ?>
                                    <?php echo esc_html($user->user_login); ?>
                                </td>
                                <td class="ka-al-count"><?php echo esc_html(number_format_i18n($user->count)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>