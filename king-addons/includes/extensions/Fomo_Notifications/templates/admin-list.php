<?php
/**
 * Fomo Notifications - List Template
 *
 * @package King_Addons
 */

defined('ABSPATH') || exit;

$is_pro = king_addons_freemius()->can_use_premium_code();
$free_limit = 3;

// Get all notifications
$paged = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$type_filter = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';

$args = [
    'post_type' => 'kng_fomo_notif',
    'posts_per_page' => 20,
    'paged' => $paged,
    'orderby' => 'date',
    'order' => 'DESC'
];

if ($search) {
    $args['s'] = $search;
}

if ($status_filter) {
    $args['meta_query'][] = [
        'key' => '_kng_fomo_status',
        'value' => $status_filter
    ];
}

if ($type_filter) {
    $args['meta_query'][] = [
        'key' => '_kng_fomo_type',
        'value' => $type_filter
    ];
}

$query = new WP_Query($args);
$notifications = $query->posts;
$total = $query->found_posts;
$total_pages = $query->max_num_pages;

// Count stats
$published_count = wp_count_posts('kng_fomo_notif')->publish;
$can_create = $is_pro || $published_count < $free_limit;
?>

<div class="kng-fomo-admin">
    
    <!-- Navigation -->
    <nav class="kng-fomo-nav">
        <a href="<?php echo esc_url(admin_url('admin.php?page=king-addons-fomo')); ?>" class="kng-fomo-nav-item">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"></rect><rect x="14" y="3" width="7" height="5"></rect><rect x="14" y="12" width="7" height="9"></rect><rect x="3" y="16" width="7" height="5"></rect></svg>
            <?php esc_html_e('Dashboard', 'king-addons'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=king-addons-fomo&view=list')); ?>" class="kng-fomo-nav-item is-active">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
            <?php esc_html_e('Notifications', 'king-addons'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=king-addons-fomo&view=wizard')); ?>" class="kng-fomo-nav-item">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>
            <?php esc_html_e('Add New', 'king-addons'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=king-addons-fomo&view=analytics')); ?>" class="kng-fomo-nav-item">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
            <?php esc_html_e('Analytics', 'king-addons'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=king-addons-fomo&view=settings')); ?>" class="kng-fomo-nav-item">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
            <?php esc_html_e('Settings', 'king-addons'); ?>
        </a>
    </nav>

    <!-- Page Header -->
    <div class="kng-fomo-section">
        <div class="kng-fomo-section-header">
            <div>
                <h1 class="kng-fomo-section-title"><?php esc_html_e('All Notifications', 'king-addons'); ?></h1>
                <p class="kng-fomo-section-subtitle">
                    <?php 
                    if (!$is_pro) {
                        printf(
                            /* translators: %1$d: current count, %2$d: max limit */
                            esc_html__('%1$d of %2$d notifications used (Free)', 'king-addons'),
                            $published_count,
                            $free_limit
                        );
                    } else {
                        printf(
                            /* translators: %d: total count */
                            esc_html__('%d notifications', 'king-addons'),
                            $total
                        );
                    }
                    ?>
                </p>
            </div>
            <?php if ($can_create): ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=king-addons-fomo&view=wizard')); ?>" class="kng-fomo-btn kng-fomo-btn--primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>
                <?php esc_html_e('Create Notification', 'king-addons'); ?>
            </a>
            <?php else: ?>
            <a href="<?php echo esc_url(king_addons_freemius()->get_upgrade_url()); ?>" class="kng-fomo-btn kng-fomo-btn--primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                <?php esc_html_e('Upgrade for More', 'king-addons'); ?>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filters -->
    <div class="kng-fomo-card" style="margin-bottom: 24px; padding: 16px 24px;">
        <form method="get" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
            <input type="hidden" name="page" value="king-addons-fomo">
            <input type="hidden" name="view" value="list">
            
            <input type="text" name="s" class="kng-fomo-input kng-fomo-input--sm" placeholder="<?php esc_attr_e('Search notifications...', 'king-addons'); ?>" value="<?php echo esc_attr($search); ?>" style="width: 250px;">
            
            <select name="status" class="kng-fomo-select kng-fomo-input--sm" style="width: auto;">
                <option value=""><?php esc_html_e('All Statuses', 'king-addons'); ?></option>
                <option value="enabled" <?php selected($status_filter, 'enabled'); ?>><?php esc_html_e('Active', 'king-addons'); ?></option>
                <option value="disabled" <?php selected($status_filter, 'disabled'); ?>><?php esc_html_e('Inactive', 'king-addons'); ?></option>
            </select>
            
            <select name="type" class="kng-fomo-select kng-fomo-input--sm" style="width: auto;">
                <option value=""><?php esc_html_e('All Types', 'king-addons'); ?></option>
                <option value="notification_bar" <?php selected($type_filter, 'notification_bar'); ?>><?php esc_html_e('Notification Bar', 'king-addons'); ?></option>
                <option value="woocommerce_sales" <?php selected($type_filter, 'woocommerce_sales'); ?>><?php esc_html_e('WooCommerce Sales', 'king-addons'); ?></option>
                <option value="wordpress_comments" <?php selected($type_filter, 'wordpress_comments'); ?>><?php esc_html_e('WordPress Comments', 'king-addons'); ?></option>
                <option value="wporg_downloads" <?php selected($type_filter, 'wporg_downloads'); ?>><?php esc_html_e('WordPress.org Downloads', 'king-addons'); ?></option>
            </select>
            
            <button type="submit" class="kng-fomo-btn kng-fomo-btn--secondary kng-fomo-btn--sm">
                <?php esc_html_e('Filter', 'king-addons'); ?>
            </button>
            
            <?php if ($search || $status_filter || $type_filter): ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=king-addons-fomo&view=list')); ?>" class="kng-fomo-btn kng-fomo-btn--ghost kng-fomo-btn--sm">
                <?php esc_html_e('Clear', 'king-addons'); ?>
            </a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (!empty($notifications)): ?>
    <!-- Table -->
    <div class="kng-fomo-table-wrap">
        <table class="kng-fomo-table">
            <thead>
                <tr>
                    <th style="width: 40px;">
                        <input type="checkbox" id="kng-fomo-select-all">
                    </th>
                    <th><?php esc_html_e('Notification', 'king-addons'); ?></th>
                    <th><?php esc_html_e('Type', 'king-addons'); ?></th>
                    <th><?php esc_html_e('Views', 'king-addons'); ?></th>
                    <th><?php esc_html_e('Clicks', 'king-addons'); ?></th>
                    <th><?php esc_html_e('CTR', 'king-addons'); ?></th>
                    <th><?php esc_html_e('Status', 'king-addons'); ?></th>
                    <th><?php esc_html_e('Created', 'king-addons'); ?></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($notifications as $notification):
                    $type = get_post_meta($notification->ID, '_kng_fomo_type', true);
                    $status = get_post_meta($notification->ID, '_kng_fomo_status', true) ?: 'disabled';
                    $views = (int) get_post_meta($notification->ID, '_kng_fomo_views', true);
                    $clicks = (int) get_post_meta($notification->ID, '_kng_fomo_clicks', true);
                    $ctr = $views > 0 ? round(($clicks / $views) * 100, 1) : 0;
                ?>
                <tr data-id="<?php echo esc_attr($notification->ID); ?>">
                    <td>
                        <input type="checkbox" class="kng-fomo-select-item" value="<?php echo esc_attr($notification->ID); ?>">
                    </td>
                    <td>
                        <div class="kng-fomo-table-title">
                            <span class="kng-fomo-table-preview">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                            </span>
                            <div>
                                <strong><?php echo esc_html($notification->post_title); ?></strong>
                            </div>
                        </div>
                    </td>
                    <td><?php echo esc_html(ucfirst(str_replace('_', ' ', $type))); ?></td>
                    <td><?php echo esc_html(number_format_i18n($views)); ?></td>
                    <td><?php echo esc_html(number_format_i18n($clicks)); ?></td>
                    <td>
                        <span style="color: <?php echo $ctr >= 5 ? 'var(--kng-fomo-success)' : ($ctr >= 2 ? 'var(--kng-fomo-warning)' : 'var(--kng-fomo-text-secondary)'); ?>; font-weight: 600;">
                            <?php echo esc_html($ctr . '%'); ?>
                        </span>
                    </td>
                    <td>
                        <label class="kng-fomo-toggle">
                            <input type="checkbox" data-id="<?php echo esc_attr($notification->ID); ?>" <?php checked($status, 'enabled'); ?>>
                            <span class="kng-fomo-toggle-slider"></span>
                        </label>
                    </td>
                    <td style="color: var(--kng-fomo-text-secondary);">
                        <?php echo esc_html(human_time_diff(strtotime($notification->post_date), current_time('timestamp')) . ' ' . __('ago', 'king-addons')); ?>
                    </td>
                    <td>
                        <div class="kng-fomo-table-actions">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=king-addons-fomo&view=wizard&edit=' . $notification->ID)); ?>" class="kng-fomo-table-action kng-fomo-edit" title="<?php esc_attr_e('Edit', 'king-addons'); ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                            </a>
                            <button type="button" class="kng-fomo-table-action kng-fomo-duplicate" title="<?php esc_attr_e('Duplicate', 'king-addons'); ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                            </button>
                            <button type="button" class="kng-fomo-table-action kng-fomo-table-action--danger kng-fomo-delete" title="<?php esc_attr_e('Delete', 'king-addons'); ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div style="margin-top: 24px; display: flex; justify-content: center; gap: 8px;">
        <?php
        $base_url = admin_url('admin.php?page=king-addons-fomo&view=list');
        if ($search) $base_url .= '&s=' . urlencode($search);
        if ($status_filter) $base_url .= '&status=' . urlencode($status_filter);
        if ($type_filter) $base_url .= '&type=' . urlencode($type_filter);
        
        for ($i = 1; $i <= $total_pages; $i++):
        ?>
        <a href="<?php echo esc_url($base_url . '&paged=' . $i); ?>" class="kng-fomo-btn kng-fomo-btn--sm <?php echo $paged === $i ? 'kng-fomo-btn--primary' : 'kng-fomo-btn--ghost'; ?>">
            <?php echo esc_html($i); ?>
        </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <!-- Empty State -->
    <div class="kng-fomo-empty">
        <div class="kng-fomo-empty-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
        </div>
        <h3 class="kng-fomo-empty-title">
            <?php echo $search || $status_filter || $type_filter ? esc_html__('No notifications found', 'king-addons') : esc_html__('No notifications yet', 'king-addons'); ?>
        </h3>
        <p class="kng-fomo-empty-desc">
            <?php echo $search || $status_filter || $type_filter ? esc_html__('Try adjusting your filters or search terms.', 'king-addons') : esc_html__('Create your first notification to start building social proof and urgency.', 'king-addons'); ?>
        </p>
        <?php if (!$search && !$status_filter && !$type_filter && $can_create): ?>
        <a href="<?php echo esc_url(admin_url('admin.php?page=king-addons-fomo&view=wizard')); ?>" class="kng-fomo-btn kng-fomo-btn--primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>
            <?php esc_html_e('Create Your First Notification', 'king-addons'); ?>
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</div>

<script>
// Bulk selection
document.getElementById('kng-fomo-select-all')?.addEventListener('change', function() {
    document.querySelectorAll('.kng-fomo-select-item').forEach(cb => {
        cb.checked = this.checked;
    });
});
</script>
