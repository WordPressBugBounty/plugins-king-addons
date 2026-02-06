<?php
/**
 * Activity Log logs view.
 *
 * @package King_Addons
 */

if (!defined('ABSPATH')) {
    exit;
}

$filters = $this->get_filters_from_request();
$page = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
$per_page = (int) ($settings['rows_per_page'] ?? 20);

$current_page = isset($_GET['page']) ? sanitize_key($_GET['page']) : 'king-addons-activity-log';
$can_manage = current_user_can('manage_options');

$logs = $this->get_logs($filters, $page, $per_page);
$total = $this->get_logs_count($filters);
$total_pages = $per_page > 0 ? (int) ceil($total / $per_page) : 1;

$event_labels = $this->get_event_labels();
$users = $this->get_recent_users_for_filter();

$filter_args = [
    'page' => $current_page,
    'view' => 'logs',
];

if (!empty($filters['search'])) {
    $filter_args['s'] = sanitize_text_field(wp_unslash($_GET['s'] ?? ''));
}
if (!empty($filters['event_key'])) {
    $filter_args['event_key'] = sanitize_text_field(wp_unslash($_GET['event_key'] ?? ''));
}
if (!empty($filters['severity'])) {
    $filter_args['severity'] = sanitize_text_field(wp_unslash($_GET['severity'] ?? ''));
}
if (!empty($filters['user_id'])) {
    $filter_args['user_id'] = absint($_GET['user_id'] ?? 0);
}
if (!empty($_GET['date_from'])) {
    $filter_args['date_from'] = sanitize_text_field(wp_unslash($_GET['date_from']));
}
if (!empty($_GET['date_to'])) {
    $filter_args['date_to'] = sanitize_text_field(wp_unslash($_GET['date_to']));
}
if (!empty($_GET['ip']) && $is_pro) {
    $filter_args['ip'] = sanitize_text_field(wp_unslash($_GET['ip']));
}

$reset_url = add_query_arg([
    'page' => $current_page,
    'view' => 'logs',
], admin_url('admin.php'));

$export_args = array_merge($filter_args, [
    'action' => 'kng_activity_log_export',
]);
$export_url = wp_nonce_url(add_query_arg($export_args, admin_url('admin-post.php')), 'kng_activity_log_export');

$date_from_value = isset($_GET['date_from']) ? sanitize_text_field(wp_unslash($_GET['date_from'])) : '';
$date_to_value = isset($_GET['date_to']) ? sanitize_text_field(wp_unslash($_GET['date_to'])) : '';
?>

<div class="ka-card">
    <div class="ka-card-header">
        <span class="dashicons dashicons-filter purple"></span>
        <h2><?php esc_html_e('Filters', 'king-addons'); ?></h2>
    </div>
    <div class="ka-card-body">
        <form method="get" class="kng-activity-filters">
            <input type="hidden" name="page" value="king-addons-activity-log">
            <input type="hidden" name="view" value="logs">

            <input type="search" name="s" value="<?php echo esc_attr($filters['search']); ?>" placeholder="<?php esc_attr_e('Search events, users, objects...', 'king-addons'); ?>">

            <select name="event_key">
                <option value=""><?php esc_html_e('All events', 'king-addons'); ?></option>
                <?php foreach ($event_labels as $key => $label) : ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php selected($filters['event_key'], $key); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="severity">
                <option value=""><?php esc_html_e('All severity', 'king-addons'); ?></option>
                <option value="info" <?php selected($filters['severity'], 'info'); ?>><?php esc_html_e('Info', 'king-addons'); ?></option>
                <option value="notice" <?php selected($filters['severity'], 'notice'); ?>><?php esc_html_e('Notice', 'king-addons'); ?></option>
                <option value="warning" <?php selected($filters['severity'], 'warning'); ?>><?php esc_html_e('Warning', 'king-addons'); ?></option>
                <option value="critical" <?php selected($filters['severity'], 'critical'); ?>><?php esc_html_e('Critical', 'king-addons'); ?></option>
            </select>

            <select name="user_id">
                <option value=""><?php esc_html_e('All users', 'king-addons'); ?></option>
                <?php foreach ($users as $user_id => $user_login) : ?>
                    <option value="<?php echo esc_attr($user_id); ?>" <?php selected($filters['user_id'], $user_id); ?>>
                        <?php echo esc_html($user_login); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <input type="date" name="date_from" value="<?php echo esc_attr($date_from_value); ?>">
            <input type="date" name="date_to" value="<?php echo esc_attr($date_to_value); ?>">

            <?php if ($is_pro) : ?>
                <input type="text" name="ip" value="<?php echo esc_attr($filters['ip']); ?>" placeholder="<?php esc_attr_e('IP address', 'king-addons'); ?>">
            <?php else : ?>
                <div class="kng-pro-input">
                    <input type="text" placeholder="<?php esc_attr_e('IP address (Pro)', 'king-addons'); ?>" disabled>
                    <span class="ka-pro-badge">PRO</span>
                </div>
            <?php endif; ?>

            <div class="kng-activity-filter-actions">
                <button type="submit" class="ka-btn ka-btn-primary"><?php esc_html_e('Apply Filters', 'king-addons'); ?></button>
                <a class="ka-btn ka-btn-secondary" href="<?php echo esc_url($reset_url); ?>"><?php esc_html_e('Reset', 'king-addons'); ?></a>
                <?php if ($can_manage) : ?>
                    <a class="ka-btn ka-btn-secondary" href="<?php echo esc_url($export_url); ?>"><?php esc_html_e('Export CSV', 'king-addons'); ?></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="ka-card">
    <div class="ka-card-header">
        <span class="dashicons dashicons-clipboard purple"></span>
        <h2><?php esc_html_e('Activity Logs', 'king-addons'); ?></h2>
    </div>
    <div class="ka-card-body" style="padding:0;">
        <?php if (empty($logs)) : ?>
            <div class="ka-empty">
                <span class="dashicons dashicons-shield-alt"></span>
                <p><?php esc_html_e('No log entries yet.', 'king-addons'); ?></p>
            </div>
        <?php else : ?>
            <table class="ka-table kng-activity-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Time', 'king-addons'); ?></th>
                        <th><?php esc_html_e('User', 'king-addons'); ?></th>
                        <th><?php esc_html_e('Role', 'king-addons'); ?></th>
                        <th><?php esc_html_e('Event', 'king-addons'); ?></th>
                        <th><?php esc_html_e('Object', 'king-addons'); ?></th>
                        <th><?php esc_html_e('Severity', 'king-addons'); ?></th>
                        <th><?php esc_html_e('IP', 'king-addons'); ?></th>
                        <th><?php esc_html_e('Source', 'king-addons'); ?></th>
                        <th><?php esc_html_e('Actions', 'king-addons'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log) :
                        $event_label = $event_labels[$log->event_key] ?? $log->event_key;
                        $object_label = $log->object_title ?: $log->object_id;
                        $object_label = $object_label ? $log->object_type . ': ' . $object_label : '-';
                        $object_url = '';

                        if (!empty($log->object_type) && !empty($log->object_id)) {
                            if ($log->object_type === 'user') {
                                $object_url = get_edit_user_link((int) $log->object_id);
                            } elseif (post_type_exists($log->object_type)) {
                                $object_url = get_edit_post_link((int) $log->object_id);
                            }
                        }

                        $payload = [
                            'event' => $event_label,
                            'event_key' => $log->event_key,
                            'time' => $this->format_time($log->created_at),
                            'severity' => $log->severity,
                            'user' => $log->user_login ?: __('Guest', 'king-addons'),
                            'role' => $log->user_role ?: '',
                            'ip' => $log->ip ?: '',
                            'user_agent' => $log->user_agent ?: '',
                            'object_label' => $object_label,
                            'object_url' => $object_url,
                            'context' => $log->context,
                            'source' => $log->source,
                            'message' => $log->message,
                            'data' => json_decode($log->data ?? '', true),
                        ];
                        ?>
                        <tr>
                            <td><?php echo esc_html($this->format_time($log->created_at)); ?></td>
                            <td><?php echo esc_html($log->user_login ?: __('Guest', 'king-addons')); ?></td>
                            <td><?php echo esc_html($log->user_role ?: '-'); ?></td>
                            <td>
                                <strong><?php echo esc_html($event_label); ?></strong>
                                <small class="kng-activity-muted"><?php echo esc_html($log->event_key); ?></small>
                            </td>
                            <td>
                                <?php if ($object_url) : ?>
                                    <a href="<?php echo esc_url($object_url); ?>" target="_blank" rel="noopener">
                                        <?php echo esc_html($object_label); ?>
                                    </a>
                                <?php else : ?>
                                    <?php echo esc_html($object_label); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="kng-severity-badge kng-severity-<?php echo esc_attr($log->severity); ?>">
                                    <?php echo esc_html(ucfirst($log->severity)); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($log->ip ?: '-'); ?></td>
                            <td><?php echo esc_html($log->source ?: '-'); ?></td>
                            <td>
                                <button type="button" class="ka-btn ka-btn-secondary ka-btn-sm kng-log-view" data-log="<?php echo esc_attr(wp_json_encode($payload)); ?>">
                                    <?php esc_html_e('View', 'king-addons'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php if ($total_pages > 1) : ?>
    <div class="kng-activity-pagination">
        <?php for ($i = 1; $i <= $total_pages; $i++) :
            $page_url = add_query_arg(array_merge($filter_args, ['paged' => $i]), admin_url('admin.php'));
            ?>
            <a class="<?php echo $i === $page ? 'active' : ''; ?>" href="<?php echo esc_url($page_url); ?>">
                <?php echo esc_html($i); ?>
            </a>
        <?php endfor; ?>
    </div>
<?php endif; ?>

<div class="kng-activity-drawer" aria-hidden="true">
    <div class="kng-activity-drawer-overlay"></div>
    <div class="kng-activity-drawer-panel">
        <div class="kng-activity-drawer-header">
            <div>
                <span class="kng-activity-drawer-label"><?php esc_html_e('Event Details', 'king-addons'); ?></span>
                <h3 data-field="event"><?php esc_html_e('Event', 'king-addons'); ?></h3>
            </div>
            <button type="button" class="kng-activity-drawer-close">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <div class="kng-activity-drawer-body">
            <div class="kng-activity-drawer-grid">
                <div>
                    <span class="kng-activity-muted"><?php esc_html_e('Time', 'king-addons'); ?></span>
                    <div data-field="time"></div>
                </div>
                <div>
                    <span class="kng-activity-muted"><?php esc_html_e('Severity', 'king-addons'); ?></span>
                    <div class="kng-severity-badge" data-field="severity"></div>
                </div>
                <div>
                    <span class="kng-activity-muted"><?php esc_html_e('User', 'king-addons'); ?></span>
                    <div data-field="user"></div>
                </div>
                <div>
                    <span class="kng-activity-muted"><?php esc_html_e('Role', 'king-addons'); ?></span>
                    <div data-field="role"></div>
                </div>
                <div>
                    <span class="kng-activity-muted"><?php esc_html_e('IP Address', 'king-addons'); ?></span>
                    <div data-field="ip"></div>
                </div>
                <div>
                    <span class="kng-activity-muted"><?php esc_html_e('Source', 'king-addons'); ?></span>
                    <div data-field="source"></div>
                </div>
                <div>
                    <span class="kng-activity-muted"><?php esc_html_e('Context', 'king-addons'); ?></span>
                    <div data-field="context"></div>
                </div>
                <div>
                    <span class="kng-activity-muted"><?php esc_html_e('Object', 'king-addons'); ?></span>
                    <div data-field="object"></div>
                </div>
            </div>

            <div class="kng-activity-section">
                <span class="kng-activity-muted"><?php esc_html_e('Summary', 'king-addons'); ?></span>
                <p data-field="message"></p>
            </div>

            <div class="kng-activity-section">
                <span class="kng-activity-muted"><?php esc_html_e('User Agent', 'king-addons'); ?></span>
                <p data-field="user_agent"></p>
            </div>

            <div class="kng-activity-section">
                <span class="kng-activity-muted"><?php esc_html_e('Event Data', 'king-addons'); ?></span>
                <pre class="kng-activity-json" data-field="data"></pre>
            </div>
        </div>
    </div>
</div>
