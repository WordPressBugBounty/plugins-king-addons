<?php
/**
 * Fomo Notifications - Dashboard Template
 *
 * @package King_Addons
 */

defined('ABSPATH') || exit;

$is_pro = king_addons_freemius()->can_use_premium_code();
$notifications = get_posts([
    'post_type' => 'kng_fomo_notif',
    'posts_per_page' => 5,
    'orderby' => 'date',
    'order' => 'DESC'
]);

$total_notifications = wp_count_posts('kng_fomo_notif')->publish;
$stats = King_Addons\Fomo_Notifications::instance()->get_total_stats();
?>

<div class="kng-fomo-admin">
    
    <!-- Navigation -->
    <nav class="kng-fomo-nav">
        <a href="<?php echo esc_url(admin_url('admin.php?page=king-addons-fomo')); ?>" class="kng-fomo-nav-item is-active">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"></rect><rect x="14" y="3" width="7" height="5"></rect><rect x="14" y="12" width="7" height="9"></rect><rect x="3" y="16" width="7" height="5"></rect></svg>
            <?php esc_html_e('Dashboard', 'king-addons'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=king-addons-fomo&view=list')); ?>" class="kng-fomo-nav-item">
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

    <!-- Hero Section -->
    <div class="kng-fomo-hero">
        <span class="kng-fomo-hero-badge">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
            <?php esc_html_e('Social Proof & FOMO', 'king-addons'); ?>
        </span>
        <h1 class="kng-fomo-hero-title"><?php esc_html_e('Fomo Notifications', 'king-addons'); ?></h1>
        <p class="kng-fomo-hero-subtitle"><?php esc_html_e('Build trust and urgency with real-time social proof notifications. Show sales, reviews, signups and more.', 'king-addons'); ?></p>
        <div class="kng-fomo-hero-actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=king-addons-fomo&view=wizard')); ?>" class="kng-fomo-btn kng-fomo-btn--primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>
                <?php esc_html_e('Create Notification', 'king-addons'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=king-addons-fomo&view=list')); ?>" class="kng-fomo-btn kng-fomo-btn--secondary">
                <?php esc_html_e('View All', 'king-addons'); ?>
            </a>
        </div>
    </div>

    <!-- KPIs -->
    <div class="kng-fomo-kpis">
        <div class="kng-fomo-kpi">
            <div class="kng-fomo-kpi-icon kng-fomo-kpi-icon--views">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
            </div>
            <div class="kng-fomo-kpi-value" data-kpi="views"><?php echo esc_html(number_format_i18n($stats['views'])); ?></div>
            <div class="kng-fomo-kpi-label"><?php esc_html_e('Total Views', 'king-addons'); ?></div>
            <?php if ($stats['views_change'] != 0): ?>
            <span class="kng-fomo-kpi-change kng-fomo-kpi-change--<?php echo $stats['views_change'] > 0 ? 'up' : 'down'; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="<?php echo $stats['views_change'] > 0 ? '18 15 12 9 6 15' : '6 9 12 15 18 9'; ?>"></polyline></svg>
                <?php echo esc_html(abs($stats['views_change']) . '%'); ?>
            </span>
            <?php endif; ?>
        </div>

        <div class="kng-fomo-kpi">
            <div class="kng-fomo-kpi-icon kng-fomo-kpi-icon--clicks">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"></path></svg>
            </div>
            <div class="kng-fomo-kpi-value" data-kpi="clicks"><?php echo esc_html(number_format_i18n($stats['clicks'])); ?></div>
            <div class="kng-fomo-kpi-label"><?php esc_html_e('Total Clicks', 'king-addons'); ?></div>
            <?php if ($stats['clicks_change'] != 0): ?>
            <span class="kng-fomo-kpi-change kng-fomo-kpi-change--<?php echo $stats['clicks_change'] > 0 ? 'up' : 'down'; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="<?php echo $stats['clicks_change'] > 0 ? '18 15 12 9 6 15' : '6 9 12 15 18 9'; ?>"></polyline></svg>
                <?php echo esc_html(abs($stats['clicks_change']) . '%'); ?>
            </span>
            <?php endif; ?>
        </div>

        <div class="kng-fomo-kpi">
            <div class="kng-fomo-kpi-icon kng-fomo-kpi-icon--ctr">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
            </div>
            <div class="kng-fomo-kpi-value" data-kpi="ctr"><?php echo esc_html($stats['ctr'] . '%'); ?></div>
            <div class="kng-fomo-kpi-label"><?php esc_html_e('Click-Through Rate', 'king-addons'); ?></div>
        </div>

        <div class="kng-fomo-kpi">
            <div class="kng-fomo-kpi-icon kng-fomo-kpi-icon--views">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
            </div>
            <div class="kng-fomo-kpi-value"><?php echo esc_html($total_notifications); ?></div>
            <div class="kng-fomo-kpi-label"><?php esc_html_e('Active Notifications', 'king-addons'); ?></div>
        </div>
    </div>

    <!-- Integrations Grid -->
    <div class="kng-fomo-section">
        <div class="kng-fomo-section-header">
            <div>
                <h2 class="kng-fomo-section-title"><?php esc_html_e('Notification Types', 'king-addons'); ?></h2>
                <p class="kng-fomo-section-subtitle"><?php esc_html_e('Choose from multiple notification sources', 'king-addons'); ?></p>
            </div>
        </div>

        <div class="kng-fomo-grid kng-fomo-grid--4">
            <!-- Notification Bar - Free -->
            <div class="kng-fomo-card kng-fomo-card--clickable">
                <div class="kng-fomo-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line></svg>
                </div>
                <h3 class="kng-fomo-card-title"><?php esc_html_e('Notification Bar', 'king-addons'); ?></h3>
                <p class="kng-fomo-card-desc"><?php esc_html_e('Sticky header or footer announcements', 'king-addons'); ?></p>
            </div>

            <!-- WooCommerce Sales - Free -->
            <div class="kng-fomo-card kng-fomo-card--clickable">
                <div class="kng-fomo-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                </div>
                <h3 class="kng-fomo-card-title"><?php esc_html_e('WooCommerce Sales', 'king-addons'); ?></h3>
                <p class="kng-fomo-card-desc"><?php esc_html_e('Real-time sales notifications', 'king-addons'); ?></p>
            </div>

            <!-- WordPress Comments - Free -->
            <div class="kng-fomo-card kng-fomo-card--clickable">
                <div class="kng-fomo-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                </div>
                <h3 class="kng-fomo-card-title"><?php esc_html_e('WordPress Comments', 'king-addons'); ?></h3>
                <p class="kng-fomo-card-desc"><?php esc_html_e('Show recent blog comments', 'king-addons'); ?></p>
            </div>

            <!-- WordPress.org Downloads - Free -->
            <div class="kng-fomo-card kng-fomo-card--clickable">
                <div class="kng-fomo-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                </div>
                <h3 class="kng-fomo-card-title"><?php esc_html_e('WordPress.org Downloads', 'king-addons'); ?></h3>
                <p class="kng-fomo-card-desc"><?php esc_html_e('Plugin/theme download counter', 'king-addons'); ?></p>
            </div>

            <!-- Reviews - Pro -->
            <div class="kng-fomo-card kng-fomo-card--clickable <?php echo !$is_pro ? 'kng-fomo-card--locked' : ''; ?>">
                <div class="kng-fomo-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                </div>
                <h3 class="kng-fomo-card-title">
                    <?php esc_html_e('Reviews', 'king-addons'); ?>
                    <?php if (!$is_pro): ?>
                    <span class="kng-fomo-card-badge kng-fomo-card-badge--pro"><?php esc_html_e('Pro', 'king-addons'); ?></span>
                    <?php endif; ?>
                </h3>
                <p class="kng-fomo-card-desc"><?php esc_html_e('Display product reviews', 'king-addons'); ?></p>
            </div>

            <!-- Email Subscriptions - Pro -->
            <div class="kng-fomo-card kng-fomo-card--clickable <?php echo !$is_pro ? 'kng-fomo-card--locked' : ''; ?>">
                <div class="kng-fomo-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                </div>
                <h3 class="kng-fomo-card-title">
                    <?php esc_html_e('Email Subscriptions', 'king-addons'); ?>
                    <?php if (!$is_pro): ?>
                    <span class="kng-fomo-card-badge kng-fomo-card-badge--pro"><?php esc_html_e('Pro', 'king-addons'); ?></span>
                    <?php endif; ?>
                </h3>
                <p class="kng-fomo-card-desc"><?php esc_html_e('Newsletter signup counter', 'king-addons'); ?></p>
            </div>

            <!-- Donations - Pro -->
            <div class="kng-fomo-card kng-fomo-card--clickable <?php echo !$is_pro ? 'kng-fomo-card--locked' : ''; ?>">
                <div class="kng-fomo-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                </div>
                <h3 class="kng-fomo-card-title">
                    <?php esc_html_e('Donations', 'king-addons'); ?>
                    <?php if (!$is_pro): ?>
                    <span class="kng-fomo-card-badge kng-fomo-card-badge--pro"><?php esc_html_e('Pro', 'king-addons'); ?></span>
                    <?php endif; ?>
                </h3>
                <p class="kng-fomo-card-desc"><?php esc_html_e('Show recent donations', 'king-addons'); ?></p>
            </div>

            <!-- Flashing Tab - Pro -->
            <div class="kng-fomo-card kng-fomo-card--clickable <?php echo !$is_pro ? 'kng-fomo-card--locked' : ''; ?>">
                <div class="kng-fomo-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                </div>
                <h3 class="kng-fomo-card-title">
                    <?php esc_html_e('Flashing Tab', 'king-addons'); ?>
                    <?php if (!$is_pro): ?>
                    <span class="kng-fomo-card-badge kng-fomo-card-badge--pro"><?php esc_html_e('Pro', 'king-addons'); ?></span>
                    <?php endif; ?>
                </h3>
                <p class="kng-fomo-card-desc"><?php esc_html_e('Browser tab attention grabber', 'king-addons'); ?></p>
            </div>
        </div>
    </div>

    <!-- Recent Notifications -->
    <?php if (!empty($notifications)): ?>
    <div class="kng-fomo-section">
        <div class="kng-fomo-section-header">
            <div>
                <h2 class="kng-fomo-section-title"><?php esc_html_e('Recent Notifications', 'king-addons'); ?></h2>
                <p class="kng-fomo-section-subtitle"><?php esc_html_e('Your latest notification campaigns', 'king-addons'); ?></p>
            </div>
            <a href="<?php echo esc_url(admin_url('admin.php?page=king-addons-fomo&view=list')); ?>" class="kng-fomo-btn kng-fomo-btn--ghost kng-fomo-btn--sm">
                <?php esc_html_e('View All', 'king-addons'); ?>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </a>
        </div>

        <div class="kng-fomo-table-wrap">
            <table class="kng-fomo-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Notification', 'king-addons'); ?></th>
                        <th><?php esc_html_e('Type', 'king-addons'); ?></th>
                        <th><?php esc_html_e('Views', 'king-addons'); ?></th>
                        <th><?php esc_html_e('Clicks', 'king-addons'); ?></th>
                        <th><?php esc_html_e('Status', 'king-addons'); ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($notifications as $notification):
                        $type = get_post_meta($notification->ID, '_kng_fomo_type', true);
                        $status = get_post_meta($notification->ID, '_kng_fomo_status', true) ?: 'disabled';
                        $views = get_post_meta($notification->ID, '_kng_fomo_views', true) ?: 0;
                        $clicks = get_post_meta($notification->ID, '_kng_fomo_clicks', true) ?: 0;
                    ?>
                    <tr data-id="<?php echo esc_attr($notification->ID); ?>">
                        <td>
                            <div class="kng-fomo-table-title">
                                <span class="kng-fomo-table-preview">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                                </span>
                                <?php echo esc_html($notification->post_title); ?>
                            </div>
                        </td>
                        <td><?php echo esc_html(ucfirst(str_replace('_', ' ', $type))); ?></td>
                        <td><?php echo esc_html(number_format_i18n($views)); ?></td>
                        <td><?php echo esc_html(number_format_i18n($clicks)); ?></td>
                        <td>
                            <span class="kng-fomo-status kng-fomo-status--<?php echo esc_attr($status); ?>">
                                <span class="kng-fomo-status-dot"></span>
                                <?php echo $status === 'enabled' ? esc_html__('Active', 'king-addons') : esc_html__('Inactive', 'king-addons'); ?>
                            </span>
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
    </div>
    <?php else: ?>
    <!-- Empty State -->
    <div class="kng-fomo-empty">
        <div class="kng-fomo-empty-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
        </div>
        <h3 class="kng-fomo-empty-title"><?php esc_html_e('No notifications yet', 'king-addons'); ?></h3>
        <p class="kng-fomo-empty-desc"><?php esc_html_e('Create your first notification to start building social proof and urgency.', 'king-addons'); ?></p>
        <a href="<?php echo esc_url(admin_url('admin.php?page=king-addons-fomo&view=wizard')); ?>" class="kng-fomo-btn kng-fomo-btn--primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>
            <?php esc_html_e('Create Your First Notification', 'king-addons'); ?>
        </a>
    </div>
    <?php endif; ?>

    <!-- Pro Upsell -->
    <?php if (!$is_pro): ?>
    <div class="kng-fomo-upsell">
        <div class="kng-fomo-upsell-content">
            <span class="kng-fomo-upsell-badge">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                <?php esc_html_e('Pro', 'king-addons'); ?>
            </span>
            <h3 class="kng-fomo-upsell-title"><?php esc_html_e('Unlock All Features', 'king-addons'); ?></h3>
            <p class="kng-fomo-upsell-desc"><?php esc_html_e('Get unlimited notifications, advanced analytics, reviews, donations, flashing tabs, and more with King Addons Pro.', 'king-addons'); ?></p>
            <div class="kng-fomo-upsell-features">
                <span class="kng-fomo-upsell-feature">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <?php esc_html_e('Unlimited Notifications', 'king-addons'); ?>
                </span>
                <span class="kng-fomo-upsell-feature">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <?php esc_html_e('Reviews & Ratings', 'king-addons'); ?>
                </span>
                <span class="kng-fomo-upsell-feature">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <?php esc_html_e('Custom CSV Import', 'king-addons'); ?>
                </span>
                <span class="kng-fomo-upsell-feature">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <?php esc_html_e('Advanced Analytics', 'king-addons'); ?>
                </span>
            </div>
            <a href="<?php echo esc_url(king_addons_freemius()->get_upgrade_url()); ?>" class="kng-fomo-btn kng-fomo-btn--primary">
                <?php esc_html_e('Upgrade to Pro', 'king-addons'); ?>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </a>
        </div>
    </div>
    <?php endif; ?>

</div>
