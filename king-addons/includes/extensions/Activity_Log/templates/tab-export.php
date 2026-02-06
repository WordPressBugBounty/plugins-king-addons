<?php
/**
 * Activity Log - Export Tab.
 *
 * @package King_Addons
 */

if (!defined('ABSPATH')) {
    exit;
}

$activity_log = \King_Addons\Activity_Log::instance();
$is_pro = $activity_log->is_pro();
?>

<div class="ka-al-card">
    <div class="ka-al-card-header">
        <h3><?php esc_html_e('Export Logs', 'king-addons'); ?></h3>
    </div>
    <div class="ka-al-card-body">
        <div class="ka-al-export-options">
            <div class="ka-al-export-format">
                <label class="ka-al-radio">
                    <input type="radio" name="export_format" value="csv" checked>
                    <span class="ka-al-radio-label">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <?php esc_html_e('CSV Format', 'king-addons'); ?>
                    </span>
                </label>

                <label class="ka-al-radio <?php echo !$is_pro ? 'ka-al-radio--disabled' : ''; ?>">
                    <input type="radio" name="export_format" value="json" <?php disabled(!$is_pro); ?>>
                    <span class="ka-al-radio-label">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                        </svg>
                        <?php esc_html_e('JSON Format', 'king-addons'); ?>
                        <?php if (!$is_pro): ?>
                            <span class="ka-al-pro-badge"><?php esc_html_e('Pro', 'king-addons'); ?></span>
                        <?php endif; ?>
                    </span>
                </label>
            </div>

            <?php if (!$is_pro): ?>
                <div class="ka-al-export-notice">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span><?php esc_html_e('Free version exports last 14 days only. Upgrade to Pro for full history and scheduled exports.', 'king-addons'); ?></span>
                </div>
            <?php endif; ?>

            <button type="button" id="ka-al-export-btn" class="ka-al-btn ka-al-btn-primary"
                style="padding: 16px 40px; font-size: 16px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                <?php esc_html_e('Export Now', 'king-addons'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Scheduled Exports (Pro) -->
<?php if (!$is_pro): ?>
    <div class="ka-al-card">
        <div class="ka-al-card-header">
            <h3><?php esc_html_e('Scheduled Exports', 'king-addons'); ?> <span
                    class="ka-al-pro-badge"><?php esc_html_e('Pro', 'king-addons'); ?></span></h3>
        </div>
        <div class="ka-al-card-body">
            <div class="ka-al-pro-feature">
                <div class="ka-al-pro-feature-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <h4><?php esc_html_e('Automate Your Exports', 'king-addons'); ?></h4>
                <p><?php esc_html_e('Schedule daily, weekly, or monthly exports delivered to your email or stored in uploads folder.', 'king-addons'); ?>
                </p>
                <a href="https://kingaddons.com/pricing/?utm_source=kng-activity-log-export&utm_medium=plugin&utm_campaign=kng"
                    class="ka-al-pro-btn" target="_blank">
                    <?php esc_html_e('Upgrade to Pro', 'king-addons'); ?>
                </a>
            </div>
        </div>
    </div>
<?php endif; ?>