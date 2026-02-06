<?php
/**
 * Fomo Notifications - Settings Template
 *
 * @package King_Addons
 */

defined('ABSPATH') || exit;

$is_pro = king_addons_freemius()->can_use_premium_code();
$settings = get_option('kng_fomo_settings', []);

// Default settings
$defaults = [
    'enabled' => true,
    'cache_ttl' => 300,
    'anonymize_names' => false,
    'sound_volume' => 50,
    'modules' => [
        'notification_bar' => true,
        'woocommerce_sales' => true,
        'wordpress_comments' => true,
        'wporg_downloads' => true,
        'reviews' => true,
        'email_subscription' => true,
        'donations' => true,
        'flashing_tab' => true,
        'custom_csv' => true
    ]
];

$settings = wp_parse_args($settings, $defaults);
?>

<div class="kng-fomo-admin">
    
    <!-- Navigation -->
    <nav class="kng-fomo-nav">
        <a href="<?php echo esc_url(admin_url('admin.php?page=king-addons-fomo')); ?>" class="kng-fomo-nav-item">
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
        <a href="<?php echo esc_url(admin_url('admin.php?page=king-addons-fomo&view=settings')); ?>" class="kng-fomo-nav-item is-active">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
            <?php esc_html_e('Settings', 'king-addons'); ?>
        </a>
    </nav>

    <!-- Page Header -->
    <div class="kng-fomo-section">
        <div class="kng-fomo-section-header">
            <div>
                <h1 class="kng-fomo-section-title"><?php esc_html_e('Settings', 'king-addons'); ?></h1>
                <p class="kng-fomo-section-subtitle"><?php esc_html_e('Configure global settings for Fomo Notifications', 'king-addons'); ?></p>
            </div>
        </div>
    </div>

    <form class="kng-fomo-settings-form">
        
        <!-- Tabs -->
        <div class="kng-fomo-tabs">
            <button type="button" class="kng-fomo-tab is-active" data-tab="tab-general"><?php esc_html_e('General', 'king-addons'); ?></button>
            <button type="button" class="kng-fomo-tab" data-tab="tab-modules"><?php esc_html_e('Modules', 'king-addons'); ?></button>
            <button type="button" class="kng-fomo-tab" data-tab="tab-import-export"><?php esc_html_e('Import / Export', 'king-addons'); ?></button>
        </div>

        <!-- General Tab -->
        <div id="tab-general" class="kng-fomo-tab-content is-active">
            <div class="kng-fomo-card">
                <div class="kng-fomo-field">
                    <label style="display: flex; align-items: center; gap: 12px;">
                        <label class="kng-fomo-toggle">
                            <input type="checkbox" name="enabled" <?php checked($settings['enabled']); ?>>
                            <span class="kng-fomo-toggle-slider"></span>
                        </label>
                        <div>
                            <span class="kng-fomo-label" style="margin: 0;"><?php esc_html_e('Enable Fomo Notifications', 'king-addons'); ?></span>
                            <p class="kng-fomo-field-help" style="margin: 4px 0 0;"><?php esc_html_e('Master switch to enable or disable all notifications', 'king-addons'); ?></p>
                        </div>
                    </label>
                </div>

                <hr style="border: none; border-top: 1px solid var(--kng-fomo-border); margin: 24px 0;">

                <div class="kng-fomo-field">
                    <label class="kng-fomo-label"><?php esc_html_e('Cache Duration', 'king-addons'); ?></label>
                    <select class="kng-fomo-select" name="cache_ttl">
                        <option value="0" <?php selected($settings['cache_ttl'], 0); ?>><?php esc_html_e('No Cache', 'king-addons'); ?></option>
                        <option value="60" <?php selected($settings['cache_ttl'], 60); ?>><?php esc_html_e('1 minute', 'king-addons'); ?></option>
                        <option value="300" <?php selected($settings['cache_ttl'], 300); ?>><?php esc_html_e('5 minutes', 'king-addons'); ?></option>
                        <option value="900" <?php selected($settings['cache_ttl'], 900); ?>><?php esc_html_e('15 minutes', 'king-addons'); ?></option>
                        <option value="3600" <?php selected($settings['cache_ttl'], 3600); ?>><?php esc_html_e('1 hour', 'king-addons'); ?></option>
                    </select>
                    <p class="kng-fomo-field-help"><?php esc_html_e('How long to cache notification data (improves performance)', 'king-addons'); ?></p>
                </div>

                <div class="kng-fomo-field">
                    <label style="display: flex; align-items: center; gap: 12px;">
                        <input type="checkbox" name="anonymize_names" <?php checked($settings['anonymize_names']); ?>>
                        <div>
                            <span class="kng-fomo-label" style="margin: 0;"><?php esc_html_e('Anonymize Names', 'king-addons'); ?></span>
                            <p class="kng-fomo-field-help" style="margin: 4px 0 0;"><?php esc_html_e('Show partial names for privacy (e.g., "John D." instead of "John Doe")', 'king-addons'); ?></p>
                        </div>
                    </label>
                </div>

                <div class="kng-fomo-field">
                    <label class="kng-fomo-label"><?php esc_html_e('Sound Volume', 'king-addons'); ?></label>
                    <input type="range" class="kng-fomo-input" name="sound_volume" min="0" max="100" value="<?php echo esc_attr($settings['sound_volume']); ?>">
                    <p class="kng-fomo-field-help"><?php esc_html_e('Volume level for notification sounds (when enabled)', 'king-addons'); ?></p>
                </div>
            </div>
        </div>

        <!-- Modules Tab -->
        <div id="tab-modules" class="kng-fomo-tab-content">
            <div class="kng-fomo-card">
                <p style="color: var(--kng-fomo-text-secondary); margin: 0 0 24px;"><?php esc_html_e('Enable or disable notification types globally', 'king-addons'); ?></p>
                
                <div class="kng-fomo-modules">
                    <!-- Free Modules -->
                    <div class="kng-fomo-module">
                        <div class="kng-fomo-module-info">
                            <div class="kng-fomo-module-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line></svg>
                            </div>
                            <span class="kng-fomo-module-name"><?php esc_html_e('Notification Bar', 'king-addons'); ?></span>
                        </div>
                        <label class="kng-fomo-toggle">
                            <input type="checkbox" class="kng-fomo-module-toggle" data-module="notification_bar" <?php checked($settings['modules']['notification_bar'] ?? true); ?>>
                            <span class="kng-fomo-toggle-slider"></span>
                        </label>
                    </div>

                    <div class="kng-fomo-module">
                        <div class="kng-fomo-module-info">
                            <div class="kng-fomo-module-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                            </div>
                            <span class="kng-fomo-module-name"><?php esc_html_e('WooCommerce Sales', 'king-addons'); ?></span>
                        </div>
                        <label class="kng-fomo-toggle">
                            <input type="checkbox" class="kng-fomo-module-toggle" data-module="woocommerce_sales" <?php checked($settings['modules']['woocommerce_sales'] ?? true); ?>>
                            <span class="kng-fomo-toggle-slider"></span>
                        </label>
                    </div>

                    <div class="kng-fomo-module">
                        <div class="kng-fomo-module-info">
                            <div class="kng-fomo-module-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                            </div>
                            <span class="kng-fomo-module-name"><?php esc_html_e('WordPress Comments', 'king-addons'); ?></span>
                        </div>
                        <label class="kng-fomo-toggle">
                            <input type="checkbox" class="kng-fomo-module-toggle" data-module="wordpress_comments" <?php checked($settings['modules']['wordpress_comments'] ?? true); ?>>
                            <span class="kng-fomo-toggle-slider"></span>
                        </label>
                    </div>

                    <div class="kng-fomo-module">
                        <div class="kng-fomo-module-info">
                            <div class="kng-fomo-module-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                            </div>
                            <span class="kng-fomo-module-name"><?php esc_html_e('WordPress.org Downloads', 'king-addons'); ?></span>
                        </div>
                        <label class="kng-fomo-toggle">
                            <input type="checkbox" class="kng-fomo-module-toggle" data-module="wporg_downloads" <?php checked($settings['modules']['wporg_downloads'] ?? true); ?>>
                            <span class="kng-fomo-toggle-slider"></span>
                        </label>
                    </div>

                    <!-- Pro Modules -->
                    <div class="kng-fomo-module <?php echo !$is_pro ? 'kng-fomo-module--locked' : ''; ?>">
                        <div class="kng-fomo-module-info">
                            <div class="kng-fomo-module-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                            </div>
                            <span class="kng-fomo-module-name">
                                <?php esc_html_e('Reviews', 'king-addons'); ?>
                                <?php if (!$is_pro): ?>
                                <span class="kng-fomo-card-badge kng-fomo-card-badge--pro"><?php esc_html_e('Pro', 'king-addons'); ?></span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <label class="kng-fomo-toggle">
                            <input type="checkbox" class="kng-fomo-module-toggle" data-module="reviews" <?php checked($settings['modules']['reviews'] ?? true); ?> <?php echo !$is_pro ? 'disabled' : ''; ?>>
                            <span class="kng-fomo-toggle-slider"></span>
                        </label>
                    </div>

                    <div class="kng-fomo-module <?php echo !$is_pro ? 'kng-fomo-module--locked' : ''; ?>">
                        <div class="kng-fomo-module-info">
                            <div class="kng-fomo-module-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                            </div>
                            <span class="kng-fomo-module-name">
                                <?php esc_html_e('Email Subscriptions', 'king-addons'); ?>
                                <?php if (!$is_pro): ?>
                                <span class="kng-fomo-card-badge kng-fomo-card-badge--pro"><?php esc_html_e('Pro', 'king-addons'); ?></span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <label class="kng-fomo-toggle">
                            <input type="checkbox" class="kng-fomo-module-toggle" data-module="email_subscription" <?php checked($settings['modules']['email_subscription'] ?? true); ?> <?php echo !$is_pro ? 'disabled' : ''; ?>>
                            <span class="kng-fomo-toggle-slider"></span>
                        </label>
                    </div>

                    <div class="kng-fomo-module <?php echo !$is_pro ? 'kng-fomo-module--locked' : ''; ?>">
                        <div class="kng-fomo-module-info">
                            <div class="kng-fomo-module-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                            </div>
                            <span class="kng-fomo-module-name">
                                <?php esc_html_e('Donations', 'king-addons'); ?>
                                <?php if (!$is_pro): ?>
                                <span class="kng-fomo-card-badge kng-fomo-card-badge--pro"><?php esc_html_e('Pro', 'king-addons'); ?></span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <label class="kng-fomo-toggle">
                            <input type="checkbox" class="kng-fomo-module-toggle" data-module="donations" <?php checked($settings['modules']['donations'] ?? true); ?> <?php echo !$is_pro ? 'disabled' : ''; ?>>
                            <span class="kng-fomo-toggle-slider"></span>
                        </label>
                    </div>

                    <div class="kng-fomo-module <?php echo !$is_pro ? 'kng-fomo-module--locked' : ''; ?>">
                        <div class="kng-fomo-module-info">
                            <div class="kng-fomo-module-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                            </div>
                            <span class="kng-fomo-module-name">
                                <?php esc_html_e('Flashing Tab', 'king-addons'); ?>
                                <?php if (!$is_pro): ?>
                                <span class="kng-fomo-card-badge kng-fomo-card-badge--pro"><?php esc_html_e('Pro', 'king-addons'); ?></span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <label class="kng-fomo-toggle">
                            <input type="checkbox" class="kng-fomo-module-toggle" data-module="flashing_tab" <?php checked($settings['modules']['flashing_tab'] ?? true); ?> <?php echo !$is_pro ? 'disabled' : ''; ?>>
                            <span class="kng-fomo-toggle-slider"></span>
                        </label>
                    </div>

                    <div class="kng-fomo-module <?php echo !$is_pro ? 'kng-fomo-module--locked' : ''; ?>">
                        <div class="kng-fomo-module-info">
                            <div class="kng-fomo-module-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                            </div>
                            <span class="kng-fomo-module-name">
                                <?php esc_html_e('Custom CSV', 'king-addons'); ?>
                                <?php if (!$is_pro): ?>
                                <span class="kng-fomo-card-badge kng-fomo-card-badge--pro"><?php esc_html_e('Pro', 'king-addons'); ?></span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <label class="kng-fomo-toggle">
                            <input type="checkbox" class="kng-fomo-module-toggle" data-module="custom_csv" <?php checked($settings['modules']['custom_csv'] ?? true); ?> <?php echo !$is_pro ? 'disabled' : ''; ?>>
                            <span class="kng-fomo-toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Import/Export Tab -->
        <div id="tab-import-export" class="kng-fomo-tab-content">
            <div class="kng-fomo-grid kng-fomo-grid--2">
                <div class="kng-fomo-card">
                    <div class="kng-fomo-card-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                    </div>
                    <h3 class="kng-fomo-card-title"><?php esc_html_e('Export Notifications', 'king-addons'); ?></h3>
                    <p class="kng-fomo-card-desc"><?php esc_html_e('Download all notifications as JSON file for backup or migration.', 'king-addons'); ?></p>
                    <button type="button" class="kng-fomo-btn kng-fomo-btn--secondary kng-fomo-btn--sm kng-fomo-export" style="margin-top: 16px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                        <?php esc_html_e('Export', 'king-addons'); ?>
                    </button>
                </div>

                <div class="kng-fomo-card">
                    <div class="kng-fomo-card-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    </div>
                    <h3 class="kng-fomo-card-title"><?php esc_html_e('Import Notifications', 'king-addons'); ?></h3>
                    <p class="kng-fomo-card-desc"><?php esc_html_e('Import notifications from a previously exported JSON file.', 'king-addons'); ?></p>
                    <button type="button" class="kng-fomo-btn kng-fomo-btn--secondary kng-fomo-btn--sm kng-fomo-import" style="margin-top: 16px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                        <?php esc_html_e('Import', 'king-addons'); ?>
                    </button>
                </div>
            </div>

            <div class="kng-fomo-card" style="margin-top: 24px;">
                <div class="kng-fomo-card-icon" style="background: var(--kng-fomo-danger-light); color: var(--kng-fomo-danger);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                </div>
                <h3 class="kng-fomo-card-title"><?php esc_html_e('Clear Analytics Data', 'king-addons'); ?></h3>
                <p class="kng-fomo-card-desc"><?php esc_html_e('Permanently delete all analytics data (views, clicks). This action cannot be undone.', 'king-addons'); ?></p>
                <button type="button" class="kng-fomo-btn kng-fomo-btn--danger kng-fomo-btn--sm kng-fomo-purge-analytics" style="margin-top: 16px;">
                    <?php esc_html_e('Clear Analytics', 'king-addons'); ?>
                </button>
            </div>
        </div>

        <!-- Save Button -->
        <div style="margin-top: 32px; display: flex; justify-content: flex-end;">
            <button type="button" class="kng-fomo-btn kng-fomo-btn--primary kng-fomo-save-settings">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                <?php esc_html_e('Save Settings', 'king-addons'); ?>
            </button>
        </div>

    </form>

</div>
