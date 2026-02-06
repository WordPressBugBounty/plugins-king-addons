<?php
/**
 * Data Table Builder dashboard view.
 *
 * @package King_Addons
 */

if (!defined('ABSPATH')) {
    exit;
}

$count = wp_count_posts(\King_Addons\Data_Table_Builder::POST_TYPE);
$total_tables = $count && isset($count->publish) ? (int) $count->publish : 0;

$tables = get_posts([
    'post_type' => \King_Addons\Data_Table_Builder::POST_TYPE,
    'post_status' => 'publish',
    'posts_per_page' => 50,
]);

$total_rows = 0;
$top_table = null;
$top_rows = 0;

foreach ($tables as $table) {
    $data = get_post_meta($table->ID, \King_Addons\Data_Table_Builder::META_DATA, true);
    $rows = is_array($data) && isset($data['rows']) && is_array($data['rows']) ? count($data['rows']) : 0;
    $total_rows += $rows;
    if ($rows > $top_rows) {
        $top_rows = $rows;
        $top_table = $table;
    }
}

$recent_tables = get_posts([
    'post_type' => \King_Addons\Data_Table_Builder::POST_TYPE,
    'post_status' => 'publish',
    'posts_per_page' => 5,
    'orderby' => 'date',
    'order' => 'DESC',
]);
?>

<div class="ka-stats-grid">
    <div class="ka-stat-card">
        <div class="ka-stat-label"><?php esc_html_e('Total Tables', 'king-addons'); ?></div>
        <div class="ka-stat-value"><?php echo esc_html(number_format_i18n($total_tables)); ?></div>
    </div>
    <div class="ka-stat-card">
        <div class="ka-stat-label"><?php esc_html_e('Total Rows', 'king-addons'); ?></div>
        <div class="ka-stat-value"><?php echo esc_html(number_format_i18n($total_rows)); ?></div>
    </div>
    <div class="ka-stat-card">
        <div class="ka-stat-label"><?php esc_html_e('Top Table', 'king-addons'); ?></div>
        <?php if ($top_table) : ?>
            <div class="ka-stat-value" style="font-size:16px;line-height:1.3">
                <div><?php echo esc_html($top_table->post_title); ?></div>
                <small><?php echo esc_html(number_format_i18n($top_rows)); ?> <?php esc_html_e('rows', 'king-addons'); ?></small>
            </div>
        <?php else : ?>
            <div class="ka-stat-value" style="font-size:16px;line-height:1.3">
                <?php esc_html_e('No data yet', 'king-addons'); ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="ka-stat-card">
        <div class="ka-stat-label"><?php esc_html_e('Presets', 'king-addons'); ?></div>
        <div class="ka-stat-value">10</div>
    </div>
</div>

<div class="ka-card">
    <div class="ka-card-header">
        <span class="dashicons dashicons-clock purple"></span>
        <h2><?php esc_html_e('Recent Tables', 'king-addons'); ?></h2>
    </div>
    <div class="ka-card-body" style="padding:0;">
        <?php if (empty($recent_tables)) : ?>
            <div class="ka-empty">
                <span class="dashicons dashicons-grid-view"></span>
                <p><?php esc_html_e('Create your first table to start building.', 'king-addons'); ?></p>
            </div>
        <?php else : ?>
            <table class="ka-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Table', 'king-addons'); ?></th>
                        <th class="text-center"><?php esc_html_e('Rows', 'king-addons'); ?></th>
                        <th class="text-center"><?php esc_html_e('Columns', 'king-addons'); ?></th>
                        <th><?php esc_html_e('Updated', 'king-addons'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_tables as $table) :
                        $data = get_post_meta($table->ID, \King_Addons\Data_Table_Builder::META_DATA, true);
                        $rows = is_array($data) && isset($data['rows']) && is_array($data['rows']) ? count($data['rows']) : 0;
                        $cols = is_array($data) && isset($data['columns']) && is_array($data['columns']) ? count($data['columns']) : 0;
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($table->post_title); ?></strong>
                                <small><?php echo esc_html('[kng_table id="' . $table->ID . '"]'); ?></small>
                            </td>
                            <td class="text-center"><?php echo esc_html(number_format_i18n($rows)); ?></td>
                            <td class="text-center"><?php echo esc_html(number_format_i18n($cols)); ?></td>
                            <td><?php echo esc_html(mysql2date('Y-m-d', $table->post_modified)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<div class="ka-upgrade-card">
    <h2><?php esc_html_e('Unlock Pro Table Studio', 'king-addons'); ?></h2>
    <p><?php esc_html_e('Enable conditional formatting, advanced filters, and rich tooltips.', 'king-addons'); ?></p>
    <ul>
        <li><?php esc_html_e('Advanced filters and column types', 'king-addons'); ?></li>
        <li><?php esc_html_e('Row details, inline charts, and formulas', 'king-addons'); ?></li>
        <li><?php esc_html_e('Google Sheets sync and JSON/PDF export', 'king-addons'); ?></li>
    </ul>
    <a href="https://kingaddons.com/pricing/" target="_blank" class="ka-btn ka-btn-pink">
        <?php esc_html_e('Upgrade to Pro', 'king-addons'); ?>
    </a>
</div>
