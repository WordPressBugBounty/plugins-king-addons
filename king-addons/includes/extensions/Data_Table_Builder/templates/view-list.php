<?php
/**
 * Data Table Builder list view.
 *
 * @package King_Addons
 */

if (!defined('ABSPATH')) {
    exit;
}

$tables = get_posts([
    'post_type' => \King_Addons\Data_Table_Builder::POST_TYPE,
    'post_status' => 'publish',
    'posts_per_page' => 50,
    'orderby' => 'date',
    'order' => 'DESC',
]);
?>

<div class="ka-card">
    <div class="ka-card-header">
        <span class="dashicons dashicons-grid-view purple"></span>
        <h2><?php esc_html_e('All Tables', 'king-addons'); ?></h2>
    </div>
    <div class="ka-card-body" style="padding:0;">
        <?php if (empty($tables)) : ?>
            <div class="ka-empty">
                <span class="dashicons dashicons-grid-view"></span>
                <p><?php esc_html_e('No tables found.', 'king-addons'); ?></p>
            </div>
        <?php else : ?>
            <table class="ka-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Table', 'king-addons'); ?></th>
                        <th><?php esc_html_e('Shortcode', 'king-addons'); ?></th>
                        <th class="text-center"><?php esc_html_e('Rows', 'king-addons'); ?></th>
                        <th class="text-center"><?php esc_html_e('Columns', 'king-addons'); ?></th>
                        <th><?php esc_html_e('Updated', 'king-addons'); ?></th>
                        <th><?php esc_html_e('Actions', 'king-addons'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tables as $table) :
                        $data = get_post_meta($table->ID, \King_Addons\Data_Table_Builder::META_DATA, true);
                        $rows = is_array($data) && isset($data['rows']) && is_array($data['rows']) ? count($data['rows']) : 0;
                        $cols = is_array($data) && isset($data['columns']) && is_array($data['columns']) ? count($data['columns']) : 0;

                        $edit_url = add_query_arg([
                            'page' => 'king-addons-table-builder',
                            'view' => 'add',
                            'table_id' => $table->ID,
                        ], admin_url('admin.php'));

                        $duplicate_url = wp_nonce_url(
                            add_query_arg([
                                'action' => 'kng_table_duplicate',
                                'table_id' => $table->ID,
                            ], admin_url('admin-post.php')),
                            'kng_table_duplicate_' . $table->ID
                        );

                        $delete_url = wp_nonce_url(
                            add_query_arg([
                                'action' => 'kng_table_delete',
                                'table_id' => $table->ID,
                            ], admin_url('admin-post.php')),
                            'kng_table_delete_' . $table->ID
                        );

                        $export_url = wp_nonce_url(
                            add_query_arg([
                                'action' => 'kng_table_export',
                                'table_id' => $table->ID,
                            ], admin_url('admin-post.php')),
                            'kng_table_export_' . $table->ID
                        );
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($table->post_title); ?></strong>
                            </td>
                            <td>
                                <code>[kng_table id="<?php echo esc_attr($table->ID); ?>"]</code>
                            </td>
                            <td class="text-center"><?php echo esc_html(number_format_i18n($rows)); ?></td>
                            <td class="text-center"><?php echo esc_html(number_format_i18n($cols)); ?></td>
                            <td><?php echo esc_html(mysql2date('Y-m-d', $table->post_modified)); ?></td>
                            <td>
                                <div class="kng-table-actions">
                                    <a href="<?php echo esc_url($edit_url); ?>"><?php esc_html_e('Edit', 'king-addons'); ?></a>
                                    <a href="<?php echo esc_url($duplicate_url); ?>"><?php esc_html_e('Duplicate', 'king-addons'); ?></a>
                                    <a href="<?php echo esc_url($export_url); ?>"><?php esc_html_e('Export CSV', 'king-addons'); ?></a>
                                    <a href="<?php echo esc_url($delete_url); ?>" onclick="return confirm('<?php echo esc_js(__('Delete this table?', 'king-addons')); ?>');">
                                        <?php esc_html_e('Delete', 'king-addons'); ?>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
