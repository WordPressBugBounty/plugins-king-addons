<?php
/**
 * Smart Links analytics view.
 *
 * @package King_Addons
 */

if (!defined('ABSPATH')) {
    exit;
}

$link_id = isset($_GET['link_id']) ? absint($_GET['link_id']) : 0;
$date_from = isset($_GET['date_from']) ? sanitize_text_field(wp_unslash($_GET['date_from'])) : '';
$date_to = isset($_GET['date_to']) ? sanitize_text_field(wp_unslash($_GET['date_to'])) : '';

if ($date_from && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)) {
    $date_from = '';
}
if ($date_to && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to)) {
    $date_to = '';
}

if ($date_from === '' && $date_to === '') {
    $date_from = wp_date('Y-m-d', current_time('timestamp') - (13 * DAY_IN_SECONDS));
    $date_to = wp_date('Y-m-d', current_time('timestamp'));
}

$links = get_posts([
    'post_type' => \King_Addons\Smart_Links\Smart_Links::POST_TYPE,
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC',
]);

$totals = $service->get_total_stats($link_id, $date_from, $date_to);
$daily = $service->get_daily_stats($link_id, $date_from, $date_to);
$top_links = $service->get_top_links(10, $date_from, $date_to);

$link_label = '';
if ($link_id) {
    $selected_link = $service->get_link($link_id);
    $link_label = $selected_link ? $selected_link->post_title : '';
}

$base_url = admin_url('admin.php?page=king-addons-smart-links&view=analytics');
?>

<div class="ka-filter-bar">
    <form method="get" action="<?php echo esc_url($base_url); ?>" style="display: contents;">
        <input type="hidden" name="page" value="king-addons-smart-links">
        <input type="hidden" name="view" value="analytics">
        <label>
            <?php esc_html_e('Link', 'king-addons'); ?>
            <select name="link_id">
                <option value="0"><?php esc_html_e('All Links', 'king-addons'); ?></option>
                <?php foreach ($links as $link) : ?>
                    <option value="<?php echo esc_attr($link->ID); ?>" <?php selected($link_id, $link->ID); ?>>
                        <?php echo esc_html($link->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <?php esc_html_e('From', 'king-addons'); ?>
            <input type="date" name="date_from" value="<?php echo esc_attr($date_from); ?>">
        </label>
        <label>
            <?php esc_html_e('To', 'king-addons'); ?>
            <input type="date" name="date_to" value="<?php echo esc_attr($date_to); ?>">
        </label>
        <button type="submit" class="button"><?php esc_html_e('Filter', 'king-addons'); ?></button>
    </form>
</div>

<div class="ka-stats-grid">
    <div class="ka-stat-card">
        <div class="ka-stat-label"><?php esc_html_e('Clicks', 'king-addons'); ?></div>
        <div class="ka-stat-value"><?php echo esc_html(number_format_i18n($totals['clicks'])); ?></div>
    </div>
    <div class="ka-stat-card">
        <div class="ka-stat-label"><?php esc_html_e('Unique Clicks', 'king-addons'); ?></div>
        <div class="ka-stat-value"><?php echo esc_html(number_format_i18n($totals['unique_clicks'])); ?></div>
    </div>
    <div class="ka-stat-card">
        <div class="ka-stat-label"><?php esc_html_e('Range', 'king-addons'); ?></div>
        <div class="ka-stat-value" style="font-size:16px;line-height:1.4">
            <?php echo esc_html($date_from); ?> → <?php echo esc_html($date_to); ?>
        </div>
    </div>
    <div class="ka-stat-card">
        <div class="ka-stat-label"><?php esc_html_e('Scope', 'king-addons'); ?></div>
        <div class="ka-stat-value" style="font-size:16px;line-height:1.4">
            <?php echo $link_label ? esc_html($link_label) : esc_html__('All Links', 'king-addons'); ?>
        </div>
    </div>
</div>

<div class="ka-card">
    <div class="ka-card-header">
        <span class="dashicons dashicons-chart-line green"></span>
        <h2><?php esc_html_e('Daily Performance', 'king-addons'); ?></h2>
    </div>
    <div class="ka-card-body" style="padding:0;">
        <?php if (empty($daily)) : ?>
            <div class="ka-empty">
                <span class="dashicons dashicons-chart-line"></span>
                <p><?php esc_html_e('No analytics data yet.', 'king-addons'); ?></p>
            </div>
        <?php else : ?>
            <table class="ka-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Date', 'king-addons'); ?></th>
                        <th class="text-center"><?php esc_html_e('Clicks', 'king-addons'); ?></th>
                        <th class="text-center"><?php esc_html_e('Unique', 'king-addons'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($daily as $row) : ?>
                        <tr>
                            <td><?php echo esc_html($row['date']); ?></td>
                            <td class="text-center"><?php echo esc_html(number_format_i18n($row['clicks'])); ?></td>
                            <td class="text-center"><?php echo esc_html(number_format_i18n($row['unique_clicks'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<div class="ka-card">
    <div class="ka-card-header">
        <span class="dashicons dashicons-star-filled green"></span>
        <h2><?php esc_html_e('Top Links', 'king-addons'); ?></h2>
    </div>
    <div class="ka-card-body" style="padding:0;">
        <?php if (empty($top_links)) : ?>
            <div class="ka-empty">
                <span class="dashicons dashicons-admin-links"></span>
                <p><?php esc_html_e('No top links yet.', 'king-addons'); ?></p>
            </div>
        <?php else : ?>
            <table class="ka-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Link', 'king-addons'); ?></th>
                        <th class="text-center"><?php esc_html_e('Clicks', 'king-addons'); ?></th>
                        <th class="text-center"><?php esc_html_e('Unique', 'king-addons'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_links as $row) :
                        $slug = (string) get_post_meta($row['link']->ID, \King_Addons\Smart_Links\Smart_Links::META_SLUG, true);
                        $short_url = $service->build_short_url($slug);
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($row['link']->post_title); ?></strong>
                                <div><a href="<?php echo esc_url($short_url); ?>" target="_blank" rel="noopener"><?php echo esc_html($short_url); ?></a></div>
                            </td>
                            <td class="text-center"><?php echo esc_html(number_format_i18n($row['clicks'])); ?></td>
                            <td class="text-center"><?php echo esc_html(number_format_i18n($row['unique_clicks'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<div class="ka-pro-notice">
    <h2><?php esc_html_e('Unlock Pro Analytics', 'king-addons'); ?></h2>
    <p><?php esc_html_e('Get geo maps, referrer domains, device breakdowns, and advanced attribution.', 'king-addons'); ?></p>
    <a href="https://kingaddons.com/pricing/" target="_blank" class="ka-btn ka-btn-pink">
        <?php esc_html_e('Upgrade to Pro', 'king-addons'); ?>
    </a>
</div>
