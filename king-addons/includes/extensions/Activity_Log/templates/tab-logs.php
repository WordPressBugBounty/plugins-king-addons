<?php
/**
 * Activity Log - Logs Tab.
 *
 * @package King_Addons
 */

if (!defined('ABSPATH')) {
    exit;
}

$activity_log = \King_Addons\Activity_Log::instance();
$event_keys = $activity_log->get_event_keys();
$is_pro = $activity_log->is_pro();

// Get all users for filter
$users = get_users(['fields' => ['ID', 'user_login']]);
?>

<!-- Controls Bar -->
<div class="ka-al-controls">
    <div class="ka-al-search">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24"
            stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input type="text" id="ka-al-search" placeholder="<?php esc_attr_e('Search events...', 'king-addons'); ?>">
    </div>

    <div class="ka-al-filters">
        <select id="ka-al-filter-event" class="ka-al-select">
            <option value=""><?php esc_html_e('All Events', 'king-addons'); ?></option>
            <?php foreach ($event_keys as $key): ?>
                <option value="<?php echo esc_attr($key); ?>">
                    <?php echo esc_html(\King_Addons\Activity_Log_Event_Types::get_label($key)); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select id="ka-al-filter-severity" class="ka-al-select">
            <option value=""><?php esc_html_e('All Severities', 'king-addons'); ?></option>
            <?php foreach (\King_Addons\Activity_Log_Event_Types::get_severity_labels() as $sev => $label): ?>
                <option value="<?php echo esc_attr($sev); ?>"><?php echo esc_html($label); ?></option>
            <?php endforeach; ?>
        </select>

        <select id="ka-al-filter-user" class="ka-al-select">
            <option value=""><?php esc_html_e('All Users', 'king-addons'); ?></option>
            <?php foreach ($users as $user): ?>
                <option value="<?php echo esc_attr($user->ID); ?>">
                    <?php echo esc_html($user->user_login); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <input type="date" id="ka-al-filter-date-from" class="ka-al-date"
            placeholder="<?php esc_attr_e('From', 'king-addons'); ?>">
        <input type="date" id="ka-al-filter-date-to" class="ka-al-date"
            placeholder="<?php esc_attr_e('To', 'king-addons'); ?>">

        <button type="button" id="ka-al-filter-apply" class="ka-al-btn ka-al-btn-primary">
            <?php esc_html_e('Apply', 'king-addons'); ?>
        </button>

        <button type="button" id="ka-al-filter-reset" class="ka-al-btn ka-al-btn-secondary">
            <?php esc_html_e('Reset', 'king-addons'); ?>
        </button>
    </div>
</div>

<!-- Logs Table -->
<div class="ka-al-table-wrap">
    <table class="ka-al-table" id="ka-al-table">
        <thead>
            <tr>
                <th><?php esc_html_e('Time', 'king-addons'); ?></th>
                <th><?php esc_html_e('Event', 'king-addons'); ?></th>
                <th><?php esc_html_e('Severity', 'king-addons'); ?></th>
                <th><?php esc_html_e('User', 'king-addons'); ?></th>
                <th><?php esc_html_e('Object', 'king-addons'); ?></th>
                <th><?php esc_html_e('IP', 'king-addons'); ?></th>
                <th><?php esc_html_e('Actions', 'king-addons'); ?></th>
            </tr>
        </thead>
        <tbody id="ka-al-table-body">
            <tr class="ka-al-loading">
                <td colspan="7">
                    <div class="ka-al-spinner"></div>
                    <?php esc_html_e('Loading events...', 'king-addons'); ?>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<div class="ka-al-pagination" id="ka-al-pagination">
    <span class="ka-al-pagination-info"></span>
    <div class="ka-al-pagination-controls">
        <button type="button" class="ka-al-page-btn" data-page="prev" disabled>
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
        </button>
        <span class="ka-al-page-numbers"></span>
        <button type="button" class="ka-al-page-btn" data-page="next" disabled>
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
        </button>
    </div>
</div>