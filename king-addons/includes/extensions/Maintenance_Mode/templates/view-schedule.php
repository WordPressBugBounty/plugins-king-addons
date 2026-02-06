<?php
/**
 * Maintenance Mode schedule view.
 *
 * @package King_Addons
 */

if (!defined('ABSPATH')) {
    exit;
}

$schedule_start = $settings['schedule_start'] ? get_date_from_gmt($settings['schedule_start'], 'Y-m-d\\TH:i') : '';
$schedule_end = $settings['schedule_end'] ? get_date_from_gmt($settings['schedule_end'], 'Y-m-d\\TH:i') : '';

$schedule_windows = $settings['schedule_windows'] ?? [];
if (!is_array($schedule_windows)) {
    $schedule_windows = [];
}

if ($schedule_windows === [] && ($schedule_start !== '' || $schedule_end !== '')) {
    $schedule_windows[] = [
        'start' => $settings['schedule_start'] ?? '',
        'end' => $settings['schedule_end'] ?? '',
        'timezone' => 'site',
    ];
}

$recurring_enabled = !empty($settings['recurring_enabled']);
$recurring_rules = $settings['recurring_rules'] ?? [];
if (!is_array($recurring_rules)) {
    $recurring_rules = [];
}

$site_timezone = function_exists('wp_timezone_string') ? wp_timezone_string() : 'UTC';
$timezone_list = function_exists('timezone_identifiers_list') ? timezone_identifiers_list() : [];

function kng_mm_dt_local($gmt)
{
    if (!$gmt) {
        return '';
    }

    return get_date_from_gmt($gmt, 'Y-m-d\\TH:i');
}
?>

<form method="post" action="options.php">
    <?php settings_fields('kng_maintenance_settings_group'); ?>

    <div class="ka-card">
        <div class="ka-card-header">
            <span class="dashicons dashicons-clock purple"></span>
            <h2><?php esc_html_e('Schedule', 'king-addons'); ?></h2>
        </div>
        <div class="ka-card-body">
            <input type="hidden" name="kng_maintenance_settings[schedule_enabled]" value="0">
            <label class="ka-toggle">
                <input type="checkbox" name="kng_maintenance_settings[schedule_enabled]" value="1" <?php checked(!empty($settings['schedule_enabled'])); ?> />
                <span class="ka-toggle-slider"></span>
                <span class="ka-toggle-label"><?php esc_html_e('Enable schedule window', 'king-addons'); ?></span>
            </label>

            <div class="kng-maintenance-schedule-grid">
                <div class="kng-field">
                    <label><?php esc_html_e('Start', 'king-addons'); ?></label>
                    <input type="datetime-local" name="kng_maintenance_settings[schedule_start]" value="<?php echo esc_attr($schedule_start); ?>">
                </div>
                <div class="kng-field">
                    <label><?php esc_html_e('End', 'king-addons'); ?></label>
                    <input type="datetime-local" name="kng_maintenance_settings[schedule_end]" value="<?php echo esc_attr($schedule_end); ?>">
                </div>
            </div>
            <p class="kng-maintenance-note"><?php esc_html_e('Scheduling uses your site timezone. Leave empty to ignore start or end.', 'king-addons'); ?></p>
        </div>
    </div>

    <div class="ka-card">
        <div class="ka-card-header">
            <span class="dashicons dashicons-lock pink"></span>
            <h2><?php esc_html_e('Multiple Schedule Windows', 'king-addons'); ?></h2>
        </div>
        <div class="ka-card-body">
            <p class="kng-maintenance-note">
                <?php
                echo esc_html(sprintf(
                    /* translators: %s: timezone string */
                    __('Each window can override timezone (default: site timezone %s). Windows are evaluated in UTC internally.', 'king-addons'),
                    $site_timezone
                ));
                ?>
            </p>

            <div id="kng-maintenance-windows" class="kng-maintenance-repeat">
                <?php foreach ($schedule_windows as $index => $window) :
                    $tz = isset($window['timezone']) ? (string) $window['timezone'] : 'site';
                    $startLocal = kng_mm_dt_local($window['start'] ?? '');
                    $endLocal = kng_mm_dt_local($window['end'] ?? '');
                    ?>
                    <div class="kng-repeat-row" data-index="<?php echo esc_attr($index); ?>">
                        <div class="kng-maintenance-schedule-grid">
                            <div class="kng-field">
                                <label><?php esc_html_e('Start', 'king-addons'); ?></label>
                                <input type="datetime-local" name="kng_maintenance_settings[schedule_windows][<?php echo esc_attr($index); ?>][start]" value="<?php echo esc_attr($startLocal); ?>">
                            </div>
                            <div class="kng-field">
                                <label><?php esc_html_e('End', 'king-addons'); ?></label>
                                <input type="datetime-local" name="kng_maintenance_settings[schedule_windows][<?php echo esc_attr($index); ?>][end]" value="<?php echo esc_attr($endLocal); ?>">
                            </div>
                            <div class="kng-field">
                                <label><?php esc_html_e('Timezone', 'king-addons'); ?></label>
                                <select name="kng_maintenance_settings[schedule_windows][<?php echo esc_attr($index); ?>][timezone]">
                                    <option value="site" <?php selected($tz, 'site'); ?>><?php echo esc_html(sprintf(__('Site (%s)', 'king-addons'), $site_timezone)); ?></option>
                                    <?php foreach ($timezone_list as $tzItem) : ?>
                                        <option value="<?php echo esc_attr($tzItem); ?>" <?php selected($tz, $tzItem); ?>><?php echo esc_html($tzItem); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="kng-repeat-actions">
                            <button type="button" class="ka-btn ka-btn-secondary ka-btn-sm kng-remove-row"><?php esc_html_e('Remove', 'king-addons'); ?></button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="kng-maintenance-actions">
                <button type="button" class="ka-btn ka-btn-secondary" id="kng-add-window"><?php esc_html_e('Add window', 'king-addons'); ?></button>
            </div>
        </div>
    </div>

    <div class="ka-card">
        <div class="ka-card-header">
            <span class="dashicons dashicons-update purple"></span>
            <h2><?php esc_html_e('Recurring Rules', 'king-addons'); ?></h2>
        </div>
        <div class="ka-card-body">
            <input type="hidden" name="kng_maintenance_settings[recurring_enabled]" value="0">
            <label class="ka-toggle">
                <input type="checkbox" name="kng_maintenance_settings[recurring_enabled]" value="1" <?php checked($recurring_enabled); ?> />
                <span class="ka-toggle-slider"></span>
                <span class="ka-toggle-label"><?php esc_html_e('Enable recurring rules', 'king-addons'); ?></span>
            </label>

            <div id="kng-maintenance-recurring" class="kng-maintenance-repeat" style="margin-top: 16px;">
                <?php foreach ($recurring_rules as $index => $rule) :
                    $freq = isset($rule['frequency']) ? (string) $rule['frequency'] : 'daily';
                    $tz = isset($rule['timezone']) ? (string) $rule['timezone'] : 'site';
                    $startTime = isset($rule['start_time']) ? (string) $rule['start_time'] : '01:00';
                    $endTime = isset($rule['end_time']) ? (string) $rule['end_time'] : '02:00';
                    $dow = isset($rule['days_of_week']) && is_array($rule['days_of_week']) ? array_map('intval', $rule['days_of_week']) : [];
                    $dom = isset($rule['days_of_month']) && is_array($rule['days_of_month']) ? implode(',', array_map('intval', $rule['days_of_month'])) : '';
                    ?>
                    <div class="kng-repeat-row" data-index="<?php echo esc_attr($index); ?>">
                        <div class="kng-maintenance-content-grid kng-maintenance-content-grid-sm">
                            <div class="kng-field">
                                <label><?php esc_html_e('Frequency', 'king-addons'); ?></label>
                                <select class="kng-recurring-frequency" name="kng_maintenance_settings[recurring_rules][<?php echo esc_attr($index); ?>][frequency]">
                                    <option value="daily" <?php selected($freq, 'daily'); ?>><?php esc_html_e('Daily', 'king-addons'); ?></option>
                                    <option value="weekly" <?php selected($freq, 'weekly'); ?>><?php esc_html_e('Weekly', 'king-addons'); ?></option>
                                    <option value="monthly" <?php selected($freq, 'monthly'); ?>><?php esc_html_e('Monthly', 'king-addons'); ?></option>
                                </select>
                            </div>
                            <div class="kng-field">
                                <label><?php esc_html_e('Timezone', 'king-addons'); ?></label>
                                <select name="kng_maintenance_settings[recurring_rules][<?php echo esc_attr($index); ?>][timezone]">
                                    <option value="site" <?php selected($tz, 'site'); ?>><?php echo esc_html(sprintf(__('Site (%s)', 'king-addons'), $site_timezone)); ?></option>
                                    <?php foreach ($timezone_list as $tzItem) : ?>
                                        <option value="<?php echo esc_attr($tzItem); ?>" <?php selected($tz, $tzItem); ?>><?php echo esc_html($tzItem); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="kng-field">
                                <label><?php esc_html_e('Start time', 'king-addons'); ?></label>
                                <input type="time" name="kng_maintenance_settings[recurring_rules][<?php echo esc_attr($index); ?>][start_time]" value="<?php echo esc_attr($startTime); ?>">
                            </div>
                            <div class="kng-field">
                                <label><?php esc_html_e('End time', 'king-addons'); ?></label>
                                <input type="time" name="kng_maintenance_settings[recurring_rules][<?php echo esc_attr($index); ?>][end_time]" value="<?php echo esc_attr($endTime); ?>">
                            </div>
                        </div>

                        <div class="kng-recurring-weekly" <?php echo $freq === 'weekly' ? '' : 'style="display:none;"'; ?>>
                            <div class="kng-field">
                                <label><?php esc_html_e('Days of week', 'king-addons'); ?></label>
                                <div class="kng-weekdays">
                                    <?php
                                    $labels = [
                                        1 => __('Mon', 'king-addons'),
                                        2 => __('Tue', 'king-addons'),
                                        3 => __('Wed', 'king-addons'),
                                        4 => __('Thu', 'king-addons'),
                                        5 => __('Fri', 'king-addons'),
                                        6 => __('Sat', 'king-addons'),
                                        7 => __('Sun', 'king-addons'),
                                    ];
                                    foreach ($labels as $dayNum => $label) :
                                        ?>
                                        <label style="margin-right:10px; display:inline-flex; align-items:center; gap:6px;">
                                            <input type="checkbox" name="kng_maintenance_settings[recurring_rules][<?php echo esc_attr($index); ?>][days_of_week][]" value="<?php echo esc_attr($dayNum); ?>" <?php checked(in_array($dayNum, $dow, true)); ?>>
                                            <span><?php echo esc_html($label); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <p class="kng-maintenance-note"><?php esc_html_e('Weekly rules run only on the selected days.', 'king-addons'); ?></p>
                            </div>
                        </div>

                        <div class="kng-recurring-monthly" <?php echo $freq === 'monthly' ? '' : 'style="display:none;"'; ?>>
                            <div class="kng-field">
                                <label><?php esc_html_e('Days of month', 'king-addons'); ?></label>
                                <input type="text" placeholder="1,15,28" name="kng_maintenance_settings[recurring_rules][<?php echo esc_attr($index); ?>][days_of_month]" value="<?php echo esc_attr($dom); ?>">
                                <p class="kng-maintenance-note"><?php esc_html_e('Comma-separated list (1..31).', 'king-addons'); ?></p>
                            </div>
                        </div>

                        <div class="kng-repeat-actions">
                            <button type="button" class="ka-btn ka-btn-secondary ka-btn-sm kng-remove-row"><?php esc_html_e('Remove', 'king-addons'); ?></button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="kng-maintenance-actions">
                <button type="button" class="ka-btn ka-btn-secondary" id="kng-add-recurring"><?php esc_html_e('Add recurring rule', 'king-addons'); ?></button>
            </div>
        </div>
    </div>

    <template id="kng-maintenance-window-template">
        <div class="kng-repeat-row" data-index="__INDEX__">
            <div class="kng-maintenance-schedule-grid">
                <div class="kng-field">
                    <label><?php esc_html_e('Start', 'king-addons'); ?></label>
                    <input type="datetime-local" name="kng_maintenance_settings[schedule_windows][__INDEX__][start]" value="">
                </div>
                <div class="kng-field">
                    <label><?php esc_html_e('End', 'king-addons'); ?></label>
                    <input type="datetime-local" name="kng_maintenance_settings[schedule_windows][__INDEX__][end]" value="">
                </div>
                <div class="kng-field">
                    <label><?php esc_html_e('Timezone', 'king-addons'); ?></label>
                    <select name="kng_maintenance_settings[schedule_windows][__INDEX__][timezone]">
                        <option value="site"><?php echo esc_html(sprintf(__('Site (%s)', 'king-addons'), $site_timezone)); ?></option>
                        <?php foreach ($timezone_list as $tzItem) : ?>
                            <option value="<?php echo esc_attr($tzItem); ?>"><?php echo esc_html($tzItem); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="kng-repeat-actions">
                <button type="button" class="ka-btn ka-btn-secondary ka-btn-sm kng-remove-row"><?php esc_html_e('Remove', 'king-addons'); ?></button>
            </div>
        </div>
    </template>

    <template id="kng-maintenance-recurring-template">
        <div class="kng-repeat-row" data-index="__INDEX__">
            <div class="kng-maintenance-content-grid kng-maintenance-content-grid-sm">
                <div class="kng-field">
                    <label><?php esc_html_e('Frequency', 'king-addons'); ?></label>
                    <select class="kng-recurring-frequency" name="kng_maintenance_settings[recurring_rules][__INDEX__][frequency]">
                        <option value="daily"><?php esc_html_e('Daily', 'king-addons'); ?></option>
                        <option value="weekly"><?php esc_html_e('Weekly', 'king-addons'); ?></option>
                        <option value="monthly"><?php esc_html_e('Monthly', 'king-addons'); ?></option>
                    </select>
                </div>
                <div class="kng-field">
                    <label><?php esc_html_e('Timezone', 'king-addons'); ?></label>
                    <select name="kng_maintenance_settings[recurring_rules][__INDEX__][timezone]">
                        <option value="site"><?php echo esc_html(sprintf(__('Site (%s)', 'king-addons'), $site_timezone)); ?></option>
                        <?php foreach ($timezone_list as $tzItem) : ?>
                            <option value="<?php echo esc_attr($tzItem); ?>"><?php echo esc_html($tzItem); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="kng-field">
                    <label><?php esc_html_e('Start time', 'king-addons'); ?></label>
                    <input type="time" name="kng_maintenance_settings[recurring_rules][__INDEX__][start_time]" value="01:00">
                </div>
                <div class="kng-field">
                    <label><?php esc_html_e('End time', 'king-addons'); ?></label>
                    <input type="time" name="kng_maintenance_settings[recurring_rules][__INDEX__][end_time]" value="02:00">
                </div>
            </div>

            <div class="kng-recurring-weekly" style="display:none;">
                <div class="kng-field">
                    <label><?php esc_html_e('Days of week', 'king-addons'); ?></label>
                    <div class="kng-weekdays">
                        <?php foreach ([1 => __('Mon', 'king-addons'), 2 => __('Tue', 'king-addons'), 3 => __('Wed', 'king-addons'), 4 => __('Thu', 'king-addons'), 5 => __('Fri', 'king-addons'), 6 => __('Sat', 'king-addons'), 7 => __('Sun', 'king-addons')] as $dayNum => $label) : ?>
                            <label style="margin-right:10px; display:inline-flex; align-items:center; gap:6px;">
                                <input type="checkbox" name="kng_maintenance_settings[recurring_rules][__INDEX__][days_of_week][]" value="<?php echo esc_attr($dayNum); ?>">
                                <span><?php echo esc_html($label); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <p class="kng-maintenance-note"><?php esc_html_e('Weekly rules run only on the selected days.', 'king-addons'); ?></p>
                </div>
            </div>

            <div class="kng-recurring-monthly" style="display:none;">
                <div class="kng-field">
                    <label><?php esc_html_e('Days of month', 'king-addons'); ?></label>
                    <input type="text" placeholder="1,15,28" name="kng_maintenance_settings[recurring_rules][__INDEX__][days_of_month]" value="">
                    <p class="kng-maintenance-note"><?php esc_html_e('Comma-separated list (1..31).', 'king-addons'); ?></p>
                </div>
            </div>

            <div class="kng-repeat-actions">
                <button type="button" class="ka-btn ka-btn-secondary ka-btn-sm kng-remove-row"><?php esc_html_e('Remove', 'king-addons'); ?></button>
            </div>
        </div>
    </template>

    <div class="ka-card">
        <div class="ka-submit">
            <button type="submit" class="ka-btn ka-btn-primary">
                <?php esc_html_e('Save Schedule', 'king-addons'); ?>
            </button>
        </div>
    </div>
</form>
