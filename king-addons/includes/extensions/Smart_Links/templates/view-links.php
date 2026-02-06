<?php
/**
 * Smart Links list view.
 *
 * @package King_Addons
 */

if (!defined('ABSPATH')) {
    exit;
}

$status_filter = isset($_GET['status']) ? sanitize_key($_GET['status']) : '';
$tag_filter = isset($_GET['tag']) ? sanitize_text_field(wp_unslash($_GET['tag'])) : '';
$search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
$date_from = isset($_GET['date_from']) ? sanitize_text_field(wp_unslash($_GET['date_from'])) : '';
$date_to = isset($_GET['date_to']) ? sanitize_text_field(wp_unslash($_GET['date_to'])) : '';

if ($date_from && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)) {
    $date_from = '';
}
if ($date_to && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to)) {
    $date_to = '';
}

$args = [
    'post_type' => \King_Addons\Smart_Links\Smart_Links::POST_TYPE,
    'post_status' => 'any',
    'posts_per_page' => 50,
    'orderby' => 'date',
    'order' => 'DESC',
];

$meta_query = [];

if ($status_filter !== '') {
    $meta_query[] = [
        'key' => \King_Addons\Smart_Links\Smart_Links::META_STATUS,
        'value' => $status_filter,
    ];
}

if ($tag_filter !== '') {
    $meta_query[] = [
        'key' => \King_Addons\Smart_Links\Smart_Links::META_TAGS,
        'value' => '"' . $tag_filter . '"',
        'compare' => 'LIKE',
    ];
}

if ($search !== '') {
    $meta_query[] = [
        'relation' => 'OR',
        [
            'key' => \King_Addons\Smart_Links\Smart_Links::META_SLUG,
            'value' => $search,
            'compare' => 'LIKE',
        ],
        [
            'key' => \King_Addons\Smart_Links\Smart_Links::META_DESTINATION,
            'value' => $search,
            'compare' => 'LIKE',
        ],
    ];
}

if (!empty($meta_query)) {
    $args['meta_query'] = $meta_query;
}

if ($date_from || $date_to) {
    $args['date_query'] = [
        'inclusive' => true,
    ];
    if ($date_from) {
        $args['date_query']['after'] = $date_from;
    }
    if ($date_to) {
        $args['date_query']['before'] = $date_to;
    }
}

$query = new WP_Query($args);
$links = $query->posts;

$base_url = admin_url('admin.php?page=king-addons-smart-links&view=links');
?>

<div class="ka-filter-bar">
    <form method="get" action="<?php echo esc_url($base_url); ?>" style="display: contents;">
        <input type="hidden" name="page" value="king-addons-smart-links">
        <input type="hidden" name="view" value="links">
        <label>
            <?php esc_html_e('Status', 'king-addons'); ?>
            <select name="status">
                <option value=""><?php esc_html_e('All', 'king-addons'); ?></option>
                <option value="active" <?php selected($status_filter, 'active'); ?>><?php esc_html_e('Active', 'king-addons'); ?></option>
                <option value="disabled" <?php selected($status_filter, 'disabled'); ?>><?php esc_html_e('Disabled', 'king-addons'); ?></option>
            </select>
        </label>
        <label>
            <?php esc_html_e('Tag', 'king-addons'); ?>
            <input type="text" name="tag" value="<?php echo esc_attr($tag_filter); ?>" placeholder="<?php esc_attr_e('campaign', 'king-addons'); ?>">
        </label>
        <label>
            <?php esc_html_e('From', 'king-addons'); ?>
            <input type="date" name="date_from" value="<?php echo esc_attr($date_from); ?>">
        </label>
        <label>
            <?php esc_html_e('To', 'king-addons'); ?>
            <input type="date" name="date_to" value="<?php echo esc_attr($date_to); ?>">
        </label>
        <label>
            <?php esc_html_e('Search', 'king-addons'); ?>
            <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="slug or url">
        </label>
        <button type="submit" class="button"><?php esc_html_e('Filter', 'king-addons'); ?></button>
        <?php if ($status_filter || $tag_filter || $date_from || $date_to || $search) : ?>
            <a href="<?php echo esc_url($base_url); ?>" class="button"><?php esc_html_e('Reset', 'king-addons'); ?></a>
        <?php endif; ?>
    </form>
</div>

<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <input type="hidden" name="action" value="kng_smart_links_bulk">
    <?php wp_nonce_field('kng_smart_links_bulk'); ?>

    <div class="ka-card">
        <div class="ka-card-header">
            <span class="dashicons dashicons-admin-links green"></span>
            <h2><?php esc_html_e('All Links', 'king-addons'); ?></h2>
        </div>
        <div class="ka-card-body" style="padding: 18px 20px;">
            <div class="ka-bulk-actions">
                <label>
                    <input type="checkbox" id="kng-select-all" />
                    <?php esc_html_e('Select all', 'king-addons'); ?>
                </label>
                <select name="bulk_action">
                    <option value=""><?php esc_html_e('Bulk actions', 'king-addons'); ?></option>
                    <option value="enable"><?php esc_html_e('Enable', 'king-addons'); ?></option>
                    <option value="disable"><?php esc_html_e('Disable', 'king-addons'); ?></option>
                    <option value="add_tag"><?php esc_html_e('Add tag', 'king-addons'); ?></option>
                    <option value="export"><?php esc_html_e('Export CSV', 'king-addons'); ?></option>
                    <option value="delete"><?php esc_html_e('Delete', 'king-addons'); ?></option>
                </select>
                <input type="text" name="bulk_tag" placeholder="<?php esc_attr_e('Tag name', 'king-addons'); ?>">
                <button type="submit" class="ka-btn ka-btn-secondary ka-btn-sm"><?php esc_html_e('Apply', 'king-addons'); ?></button>
            </div>
        </div>
        <div class="ka-card-body" style="padding: 0;">
            <?php if (empty($links)) : ?>
                <div class="ka-empty">
                    <span class="dashicons dashicons-admin-links"></span>
                    <p><?php esc_html_e('No smart links found.', 'king-addons'); ?></p>
                </div>
            <?php else : ?>
                <table class="ka-table">
                    <thead>
                        <tr>
                            <th></th>
                            <th><?php esc_html_e('Title', 'king-addons'); ?></th>
                            <th><?php esc_html_e('Short URL', 'king-addons'); ?></th>
                            <th><?php esc_html_e('Destination', 'king-addons'); ?></th>
                            <th class="text-center"><?php esc_html_e('Clicks', 'king-addons'); ?></th>
                            <th class="text-center"><?php esc_html_e('Unique', 'king-addons'); ?></th>
                            <th><?php esc_html_e('Tags', 'king-addons'); ?></th>
                            <th><?php esc_html_e('Status', 'king-addons'); ?></th>
                            <th><?php esc_html_e('Created', 'king-addons'); ?></th>
                            <th><?php esc_html_e('Actions', 'king-addons'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($links as $link) :
                            $slug = (string) get_post_meta($link->ID, \King_Addons\Smart_Links\Smart_Links::META_SLUG, true);
                            $short_url = $service->build_short_url($slug);
                            $destination = (string) get_post_meta($link->ID, \King_Addons\Smart_Links\Smart_Links::META_DESTINATION, true);
                            $status = (string) get_post_meta($link->ID, \King_Addons\Smart_Links\Smart_Links::META_STATUS, true);
                            $tags = (array) get_post_meta($link->ID, \King_Addons\Smart_Links\Smart_Links::META_TAGS, true);
                            $clicks = (int) get_post_meta($link->ID, \King_Addons\Smart_Links\Smart_Links::META_CLICKS, true);
                            $unique = (int) get_post_meta($link->ID, \King_Addons\Smart_Links\Smart_Links::META_UNIQUE_CLICKS, true);

                            $edit_url = add_query_arg([
                                'page' => 'king-addons-smart-links',
                                'view' => 'add',
                                'link_id' => $link->ID,
                            ], admin_url('admin.php'));

                            $analytics_url = add_query_arg([
                                'page' => 'king-addons-smart-links',
                                'view' => 'analytics',
                                'link_id' => $link->ID,
                            ], admin_url('admin.php'));

                            $duplicate_url = wp_nonce_url(
                                add_query_arg([
                                    'action' => 'kng_smart_links_duplicate',
                                    'link_id' => $link->ID,
                                ], admin_url('admin-post.php')),
                                'kng_smart_links_duplicate_' . $link->ID
                            );

                            $delete_url = wp_nonce_url(
                                add_query_arg([
                                    'action' => 'kng_smart_links_delete',
                                    'link_id' => $link->ID,
                                ], admin_url('admin-post.php')),
                                'kng_smart_links_delete_' . $link->ID
                            );

                            $toggle_status = $status === 'disabled' ? 'active' : 'disabled';
                            $toggle_url = wp_nonce_url(
                                add_query_arg([
                                    'action' => 'kng_smart_links_toggle',
                                    'link_id' => $link->ID,
                                    'status' => $toggle_status,
                                ], admin_url('admin-post.php')),
                                'kng_smart_links_toggle_' . $link->ID
                            );
                            ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="kng-link-checkbox" name="link_ids[]" value="<?php echo esc_attr($link->ID); ?>">
                                </td>
                                <td class="ka-link-list-title">
                                    <strong><?php echo esc_html($link->post_title); ?></strong>
                                    <small><?php echo esc_html($slug); ?></small>
                                </td>
                                <td>
                                    <div class="ka-short-url">
                                        <a href="<?php echo esc_url($short_url); ?>" target="_blank" rel="noopener" class="ka-short-url-text" data-copy="<?php echo esc_attr($short_url); ?>">
                                            <?php echo esc_html($short_url); ?>
                                        </a>
                                        <button type="button" class="ka-copy-btn" data-copy="<?php echo esc_attr($short_url); ?>">
                                            <span class="dashicons dashicons-admin-page"></span>
                                            <span><?php esc_html_e('Copy', 'king-addons'); ?></span>
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    <a href="<?php echo esc_url($destination); ?>" target="_blank" rel="noopener">
                                        <?php echo esc_html(wp_trim_words($destination, 6, '…')); ?>
                                    </a>
                                </td>
                                <td class="text-center"><?php echo esc_html(number_format_i18n($clicks)); ?></td>
                                <td class="text-center"><?php echo esc_html(number_format_i18n($unique)); ?></td>
                                <td>
                                    <div class="ka-tags">
                                        <?php if (empty($tags)) : ?>
                                            <span class="ka-tag-chip">—</span>
                                        <?php else : ?>
                                            <?php foreach ($tags as $tag) : ?>
                                                <span class="ka-tag-chip"><?php echo esc_html($tag); ?></span>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="ka-status <?php echo $status === 'disabled' ? 'ka-status-disabled' : 'ka-status-enabled'; ?>">
                                        <span class="ka-status-dot"></span>
                                        <?php echo $status === 'disabled' ? esc_html__('Disabled', 'king-addons') : esc_html__('Active', 'king-addons'); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html(mysql2date('Y-m-d', $link->post_date)); ?></td>
                                <td>
                                    <div class="ka-smart-links-actions">
                                        <a href="<?php echo esc_url($edit_url); ?>"><?php esc_html_e('Edit', 'king-addons'); ?></a>
                                        <a href="<?php echo esc_url($analytics_url); ?>"><?php esc_html_e('Analytics', 'king-addons'); ?></a>
                                        <a href="<?php echo esc_url($toggle_url); ?>"><?php echo $status === 'disabled' ? esc_html__('Enable', 'king-addons') : esc_html__('Disable', 'king-addons'); ?></a>
                                        <a href="<?php echo esc_url($duplicate_url); ?>"><?php esc_html_e('Duplicate', 'king-addons'); ?></a>
                                        <a href="<?php echo esc_url($delete_url); ?>" onclick="return confirm('<?php echo esc_js(__('Delete this link?', 'king-addons')); ?>');"><?php esc_html_e('Delete', 'king-addons'); ?></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</form>
