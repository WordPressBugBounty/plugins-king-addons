<?php
/**
 * Fomo Notifications - Wizard Template (5-Step)
 *
 * @package King_Addons
 */

defined('ABSPATH') || exit;

$is_pro = king_addons_freemius()->can_use_premium_code();
$edit_id = isset($_GET['edit']) ? absint($_GET['edit']) : 0;

// Notification types
$notification_types = [
    'notification_bar' => [
        'label' => __('Notification Bar', 'king-addons'),
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line></svg>',
        'desc' => __('Sticky header/footer bar', 'king-addons'),
        'free' => true
    ],
    'woocommerce_sales' => [
        'label' => __('WooCommerce Sales', 'king-addons'),
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>',
        'desc' => __('Real-time sales popups', 'king-addons'),
        'free' => true
    ],
    'wordpress_comments' => [
        'label' => __('WordPress Comments', 'king-addons'),
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>',
        'desc' => __('Recent blog comments', 'king-addons'),
        'free' => true
    ],
    'wporg_downloads' => [
        'label' => __('WordPress.org Downloads', 'king-addons'),
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>',
        'desc' => __('Plugin/theme downloads', 'king-addons'),
        'free' => true
    ],
    'reviews' => [
        'label' => __('Reviews', 'king-addons'),
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>',
        'desc' => __('Product reviews', 'king-addons'),
        'free' => false
    ],
    'email_subscription' => [
        'label' => __('Email Subscriptions', 'king-addons'),
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>',
        'desc' => __('Newsletter signups', 'king-addons'),
        'free' => false
    ],
    'donations' => [
        'label' => __('Donations', 'king-addons'),
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>',
        'desc' => __('Recent donations', 'king-addons'),
        'free' => false
    ],
    'flashing_tab' => [
        'label' => __('Flashing Tab', 'king-addons'),
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>',
        'desc' => __('Browser tab attention', 'king-addons'),
        'free' => false
    ],
    'custom_csv' => [
        'label' => __('Custom CSV', 'king-addons'),
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>',
        'desc' => __('Import from CSV', 'king-addons'),
        'free' => false
    ]
];

// Design templates
$design_templates = [
    'default' => [
        'label' => __('Default', 'king-addons'),
        'preview' => 'default-preview.png'
    ],
    'modern' => [
        'label' => __('Modern', 'king-addons'),
        'preview' => 'modern-preview.png'
    ],
    'minimal' => [
        'label' => __('Minimal', 'king-addons'),
        'preview' => 'minimal-preview.png'
    ],
    'rounded' => [
        'label' => __('Rounded', 'king-addons'),
        'preview' => 'rounded-preview.png'
    ]
];

// Positions
$positions = [
    'bottom-left' => __('Bottom Left', 'king-addons'),
    'bottom-right' => __('Bottom Right', 'king-addons'),
    'top-left' => __('Top Left', 'king-addons'),
    'top-right' => __('Top Right', 'king-addons'),
    'bottom-center' => __('Bottom Center', 'king-addons'),
    'top-center' => __('Top Center', 'king-addons')
];

// Animations
$animations = [
    'slide' => __('Slide', 'king-addons'),
    'fade' => __('Fade', 'king-addons'),
    'bounce' => __('Bounce', 'king-addons'),
    'scale' => __('Scale', 'king-addons')
];
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
        <a href="<?php echo esc_url(admin_url('admin.php?page=king-addons-fomo&view=wizard')); ?>" class="kng-fomo-nav-item is-active">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>
            <?php echo $edit_id ? esc_html__('Edit Notification', 'king-addons') : esc_html__('Add New', 'king-addons'); ?>
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

    <!-- Wizard -->
    <div class="kng-fomo-wizard">
        
        <!-- Header -->
        <div class="kng-fomo-wizard-header">
            <h1 class="kng-fomo-wizard-title">
                <?php echo $edit_id ? esc_html__('Edit Notification', 'king-addons') : esc_html__('Create Notification', 'king-addons'); ?>
            </h1>
            <p class="kng-fomo-wizard-subtitle"><?php esc_html_e('Follow the steps to configure your notification', 'king-addons'); ?></p>
        </div>

        <!-- Steps Indicator -->
        <div class="kng-fomo-wizard-steps">
            <div class="kng-fomo-wizard-step is-active" data-step="1">
                <span class="kng-fomo-wizard-step-num">1</span>
                <span><?php esc_html_e('Source', 'king-addons'); ?></span>
            </div>
            <div class="kng-fomo-wizard-step" data-step="2">
                <span class="kng-fomo-wizard-step-num">2</span>
                <span><?php esc_html_e('Design', 'king-addons'); ?></span>
            </div>
            <div class="kng-fomo-wizard-step" data-step="3">
                <span class="kng-fomo-wizard-step-num">3</span>
                <span><?php esc_html_e('Content', 'king-addons'); ?></span>
            </div>
            <div class="kng-fomo-wizard-step" data-step="4">
                <span class="kng-fomo-wizard-step-num">4</span>
                <span><?php esc_html_e('Display', 'king-addons'); ?></span>
            </div>
            <div class="kng-fomo-wizard-step" data-step="5">
                <span class="kng-fomo-wizard-step-num">5</span>
                <span><?php esc_html_e('Customize', 'king-addons'); ?></span>
            </div>
        </div>

        <!-- Step 1: Source -->
        <div class="kng-fomo-wizard-panel is-active" data-step="1">
            <div class="kng-fomo-wizard-content">
                <div class="kng-fomo-wizard-section">
                    <h3 class="kng-fomo-wizard-section-title"><?php esc_html_e('Notification Name', 'king-addons'); ?></h3>
                    <div class="kng-fomo-field">
                        <input type="text" class="kng-fomo-input" data-field="title" data-section="content" placeholder="<?php esc_attr_e('e.g., Recent Sales Popup', 'king-addons'); ?>">
                    </div>
                </div>

                <div class="kng-fomo-wizard-section">
                    <h3 class="kng-fomo-wizard-section-title"><?php esc_html_e('Choose Notification Type', 'king-addons'); ?></h3>
                    <div class="kng-fomo-radio-cards">
                        <?php foreach ($notification_types as $type_key => $type): 
                            $is_locked = !$type['free'] && !$is_pro;
                        ?>
                        <label class="kng-fomo-radio-card <?php echo $is_locked ? 'kng-fomo-radio-card--locked' : ''; ?>">
                            <input type="radio" name="notification_type" value="<?php echo esc_attr($type_key); ?>" <?php echo $is_locked ? 'disabled' : ''; ?>>
                            <div class="kng-fomo-radio-card-content">
                                <span class="kng-fomo-radio-card-icon"><?php echo $type['icon']; ?></span>
                                <span class="kng-fomo-radio-card-label"><?php echo esc_html($type['label']); ?></span>
                            </div>
                            <?php if ($is_locked): ?>
                            <span class="kng-fomo-card-badge kng-fomo-card-badge--pro"><?php esc_html_e('Pro', 'king-addons'); ?></span>
                            <?php endif; ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Source Config Panels (shown based on type) -->
                <div class="kng-fomo-source-config">
                    <!-- WooCommerce Config -->
                    <div class="kng-fomo-source-config-panel" data-source="woocommerce_sales" style="display: none;">
                        <h3 class="kng-fomo-wizard-section-title"><?php esc_html_e('WooCommerce Settings', 'king-addons'); ?></h3>
                        <div class="kng-fomo-field-row">
                            <div class="kng-fomo-field">
                                <label class="kng-fomo-label"><?php esc_html_e('Order Status', 'king-addons'); ?></label>
                                <select class="kng-fomo-select" data-field="order_status" data-section="source_config">
                                    <option value="completed"><?php esc_html_e('Completed', 'king-addons'); ?></option>
                                    <option value="processing"><?php esc_html_e('Processing', 'king-addons'); ?></option>
                                    <option value="any"><?php esc_html_e('Any', 'king-addons'); ?></option>
                                </select>
                            </div>
                            <div class="kng-fomo-field">
                                <label class="kng-fomo-label"><?php esc_html_e('Time Range', 'king-addons'); ?></label>
                                <select class="kng-fomo-select" data-field="time_range" data-section="source_config">
                                    <option value="24h"><?php esc_html_e('Last 24 hours', 'king-addons'); ?></option>
                                    <option value="7d"><?php esc_html_e('Last 7 days', 'king-addons'); ?></option>
                                    <option value="30d"><?php esc_html_e('Last 30 days', 'king-addons'); ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="kng-fomo-field">
                            <label class="kng-fomo-label"><?php esc_html_e('Product Categories', 'king-addons'); ?> <span class="kng-fomo-label-hint"><?php esc_html_e('(optional)', 'king-addons'); ?></span></label>
                            <select class="kng-fomo-select" data-field="categories" data-section="source_config" multiple>
                                <?php
                                $categories = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);
                                if (!is_wp_error($categories)) {
                                    foreach ($categories as $cat) {
                                        echo '<option value="' . esc_attr($cat->term_id) . '">' . esc_html($cat->name) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <!-- WordPress.org Config -->
                    <div class="kng-fomo-source-config-panel" data-source="wporg_downloads" style="display: none;">
                        <h3 class="kng-fomo-wizard-section-title"><?php esc_html_e('WordPress.org Settings', 'king-addons'); ?></h3>
                        <div class="kng-fomo-field">
                            <label class="kng-fomo-label"><?php esc_html_e('Plugin/Theme Slug', 'king-addons'); ?></label>
                            <input type="text" class="kng-fomo-input" data-field="wporg_slug" data-section="source_config" placeholder="<?php esc_attr_e('e.g., king-addons', 'king-addons'); ?>">
                            <p class="kng-fomo-field-help"><?php esc_html_e('Enter the slug from wordpress.org URL (e.g., wordpress.org/plugins/king-addons)', 'king-addons'); ?></p>
                        </div>
                        <div class="kng-fomo-field">
                            <label class="kng-fomo-label"><?php esc_html_e('Type', 'king-addons'); ?></label>
                            <select class="kng-fomo-select" data-field="wporg_type" data-section="source_config">
                                <option value="plugin"><?php esc_html_e('Plugin', 'king-addons'); ?></option>
                                <option value="theme"><?php esc_html_e('Theme', 'king-addons'); ?></option>
                            </select>
                        </div>
                    </div>

                    <!-- Comments Config -->
                    <div class="kng-fomo-source-config-panel" data-source="wordpress_comments" style="display: none;">
                        <h3 class="kng-fomo-wizard-section-title"><?php esc_html_e('Comments Settings', 'king-addons'); ?></h3>
                        <div class="kng-fomo-field">
                            <label class="kng-fomo-label"><?php esc_html_e('Post Types', 'king-addons'); ?></label>
                            <select class="kng-fomo-select" data-field="post_types" data-section="source_config" multiple>
                                <option value="post"><?php esc_html_e('Posts', 'king-addons'); ?></option>
                                <option value="page"><?php esc_html_e('Pages', 'king-addons'); ?></option>
                                <option value="product"><?php esc_html_e('Products', 'king-addons'); ?></option>
                            </select>
                        </div>
                        <div class="kng-fomo-field">
                            <label class="kng-fomo-label"><?php esc_html_e('Number of Comments', 'king-addons'); ?></label>
                            <input type="number" class="kng-fomo-input" data-field="comments_count" data-section="source_config" value="10" min="1" max="50">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 2: Design -->
        <div class="kng-fomo-wizard-panel" data-step="2">
            <div class="kng-fomo-wizard-content">
                <div class="kng-fomo-wizard-section">
                    <h3 class="kng-fomo-wizard-section-title"><?php esc_html_e('Choose Template', 'king-addons'); ?></h3>
                    <div class="kng-fomo-template-grid">
                        <?php foreach ($design_templates as $tpl_key => $tpl): ?>
                        <div class="kng-fomo-template-card <?php echo $tpl_key === 'default' ? 'is-selected' : ''; ?>" data-template="<?php echo esc_attr($tpl_key); ?>">
                            <div class="kng-fomo-template-preview kng-fomo-template-preview--<?php echo esc_attr($tpl_key); ?>">
                                <div class="kng-fomo-template-mock">
                                    <div class="kng-fomo-template-mock-img"></div>
                                    <div class="kng-fomo-template-mock-content">
                                        <div class="kng-fomo-template-mock-title"></div>
                                        <div class="kng-fomo-template-mock-text"></div>
                                        <div class="kng-fomo-template-mock-time"></div>
                                    </div>
                                </div>
                            </div>
                            <span class="kng-fomo-template-name"><?php echo esc_html($tpl['label']); ?></span>
                            <span class="kng-fomo-template-check">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="kng-fomo-wizard-section">
                    <h3 class="kng-fomo-wizard-section-title"><?php esc_html_e('Position', 'king-addons'); ?></h3>
                    <div class="kng-fomo-position-selector">
                        <div class="kng-fomo-position-preview">
                            <div class="kng-fomo-position-dot" data-pos="top-left"></div>
                            <div class="kng-fomo-position-dot" data-pos="top-center"></div>
                            <div class="kng-fomo-position-dot" data-pos="top-right"></div>
                            <div class="kng-fomo-position-dot is-active" data-pos="bottom-left"></div>
                            <div class="kng-fomo-position-dot" data-pos="bottom-center"></div>
                            <div class="kng-fomo-position-dot" data-pos="bottom-right"></div>
                        </div>
                        <div class="kng-fomo-position-buttons">
                            <?php foreach ($positions as $pos_key => $pos_label): ?>
                            <button type="button" class="kng-fomo-position-btn <?php echo $pos_key === 'bottom-left' ? 'is-active' : ''; ?>" data-position="<?php echo esc_attr($pos_key); ?>">
                                <?php echo esc_html($pos_label); ?>
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="kng-fomo-wizard-section">
                    <h3 class="kng-fomo-wizard-section-title"><?php esc_html_e('Colors', 'king-addons'); ?></h3>
                    <div class="kng-fomo-field-row kng-fomo-field-row--3">
                        <div class="kng-fomo-field">
                            <label class="kng-fomo-label"><?php esc_html_e('Background', 'king-addons'); ?></label>
                            <div class="kng-fomo-color-field">
                                <input type="color" class="kng-fomo-color-picker" data-field="bg_color" value="#ffffff">
                                <input type="text" class="kng-fomo-input kng-fomo-color-input" data-field="bg_color" data-section="design" value="#ffffff">
                            </div>
                        </div>
                        <div class="kng-fomo-field">
                            <label class="kng-fomo-label"><?php esc_html_e('Text', 'king-addons'); ?></label>
                            <div class="kng-fomo-color-field">
                                <input type="color" class="kng-fomo-color-picker" data-field="text_color" value="#1d1d1f">
                                <input type="text" class="kng-fomo-input kng-fomo-color-input" data-field="text_color" data-section="design" value="#1d1d1f">
                            </div>
                        </div>
                        <div class="kng-fomo-field">
                            <label class="kng-fomo-label"><?php esc_html_e('Accent', 'king-addons'); ?></label>
                            <div class="kng-fomo-color-field">
                                <input type="color" class="kng-fomo-color-picker" data-field="accent_color" value="#0071e3">
                                <input type="text" class="kng-fomo-input kng-fomo-color-input" data-field="accent_color" data-section="design" value="#0071e3">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="kng-fomo-wizard-section">
                    <h3 class="kng-fomo-wizard-section-title"><?php esc_html_e('Style Options', 'king-addons'); ?></h3>
                    <div class="kng-fomo-field-row">
                        <div class="kng-fomo-field">
                            <label class="kng-fomo-label"><?php esc_html_e('Border Radius', 'king-addons'); ?></label>
                            <input type="range" class="kng-fomo-input" data-field="border_radius" data-section="design" min="0" max="32" value="16">
                        </div>
                        <div class="kng-fomo-field">
                            <label class="kng-fomo-label"><?php esc_html_e('Animation', 'king-addons'); ?></label>
                            <select class="kng-fomo-select" data-field="animation" data-section="design">
                                <?php foreach ($animations as $anim_key => $anim_label): ?>
                                <option value="<?php echo esc_attr($anim_key); ?>"><?php echo esc_html($anim_label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="kng-fomo-field">
                        <label style="display: flex; align-items: center; gap: 12px;">
                            <input type="checkbox" data-field="shadow" data-section="design" checked>
                            <span class="kng-fomo-label" style="margin: 0;"><?php esc_html_e('Show Shadow', 'king-addons'); ?></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 3: Content -->
        <div class="kng-fomo-wizard-panel" data-step="3">
            <div class="kng-fomo-wizard-content">
                <div class="kng-fomo-wizard-section">
                    <h3 class="kng-fomo-wizard-section-title"><?php esc_html_e('Notification Content', 'king-addons'); ?></h3>
                    <div class="kng-fomo-field">
                        <label class="kng-fomo-label"><?php esc_html_e('Title', 'king-addons'); ?></label>
                        <input type="text" class="kng-fomo-input" data-field="title" data-section="content" placeholder="<?php esc_attr_e('{{name}} just purchased', 'king-addons'); ?>">
                        <p class="kng-fomo-field-help"><?php esc_html_e('Available placeholders: {{name}}, {{product}}, {{location}}, {{time}}', 'king-addons'); ?></p>
                    </div>
                    <div class="kng-fomo-field">
                        <label class="kng-fomo-label"><?php esc_html_e('Message', 'king-addons'); ?></label>
                        <textarea class="kng-fomo-textarea" data-field="message" data-section="content" placeholder="<?php esc_attr_e('{{product}} from {{location}}', 'king-addons'); ?>"></textarea>
                    </div>
                </div>

                <div class="kng-fomo-wizard-section">
                    <h3 class="kng-fomo-wizard-section-title"><?php esc_html_e('Image', 'king-addons'); ?></h3>
                    <div class="kng-fomo-field">
                        <label class="kng-fomo-label"><?php esc_html_e('Image Source', 'king-addons'); ?></label>
                        <select class="kng-fomo-select" data-field="image_type" data-section="content">
                            <option value="product"><?php esc_html_e('Product Image', 'king-addons'); ?></option>
                            <option value="avatar"><?php esc_html_e('User Avatar', 'king-addons'); ?></option>
                            <option value="custom"><?php esc_html_e('Custom Image', 'king-addons'); ?></option>
                            <option value="none"><?php esc_html_e('No Image', 'king-addons'); ?></option>
                        </select>
                    </div>
                </div>

                <div class="kng-fomo-wizard-section">
                    <h3 class="kng-fomo-wizard-section-title"><?php esc_html_e('Call to Action', 'king-addons'); ?></h3>
                    <div class="kng-fomo-field-row">
                        <div class="kng-fomo-field">
                            <label class="kng-fomo-label"><?php esc_html_e('CTA Text', 'king-addons'); ?> <span class="kng-fomo-label-hint"><?php esc_html_e('(optional)', 'king-addons'); ?></span></label>
                            <input type="text" class="kng-fomo-input" data-field="cta_text" data-section="content" placeholder="<?php esc_attr_e('View Product', 'king-addons'); ?>">
                        </div>
                        <div class="kng-fomo-field">
                            <label class="kng-fomo-label"><?php esc_html_e('CTA Link', 'king-addons'); ?></label>
                            <input type="url" class="kng-fomo-input" data-field="cta_url" data-section="content" placeholder="<?php esc_attr_e('https://...', 'king-addons'); ?>">
                        </div>
                    </div>
                </div>

                <div class="kng-fomo-wizard-section">
                    <h3 class="kng-fomo-wizard-section-title"><?php esc_html_e('Time Display', 'king-addons'); ?></h3>
                    <div class="kng-fomo-field">
                        <label style="display: flex; align-items: center; gap: 12px;">
                            <input type="checkbox" data-field="show_time" data-section="content" checked>
                            <span class="kng-fomo-label" style="margin: 0;"><?php esc_html_e('Show time ago', 'king-addons'); ?></span>
                        </label>
                    </div>
                    <div class="kng-fomo-field">
                        <label class="kng-fomo-label"><?php esc_html_e('Time Format', 'king-addons'); ?></label>
                        <select class="kng-fomo-select" data-field="time_format" data-section="content">
                            <option value="relative"><?php esc_html_e('Relative (5 mins ago)', 'king-addons'); ?></option>
                            <option value="absolute"><?php esc_html_e('Absolute (Jan 5, 2024)', 'king-addons'); ?></option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 4: Display -->
        <div class="kng-fomo-wizard-panel" data-step="4">
            <div class="kng-fomo-wizard-content">
                <div class="kng-fomo-wizard-section">
                    <h3 class="kng-fomo-wizard-section-title"><?php esc_html_e('Timing', 'king-addons'); ?></h3>
                    <div class="kng-fomo-field-row kng-fomo-field-row--3">
                        <div class="kng-fomo-field">
                            <label class="kng-fomo-label"><?php esc_html_e('Initial Delay', 'king-addons'); ?></label>
                            <input type="number" class="kng-fomo-input" data-field="delay" data-section="display" value="3" min="0" max="60">
                            <p class="kng-fomo-field-help"><?php esc_html_e('Seconds', 'king-addons'); ?></p>
                        </div>
                        <div class="kng-fomo-field">
                            <label class="kng-fomo-label"><?php esc_html_e('Display Duration', 'king-addons'); ?></label>
                            <input type="number" class="kng-fomo-input" data-field="duration" data-section="display" value="5" min="1" max="30">
                            <p class="kng-fomo-field-help"><?php esc_html_e('Seconds', 'king-addons'); ?></p>
                        </div>
                        <div class="kng-fomo-field">
                            <label class="kng-fomo-label"><?php esc_html_e('Interval Between', 'king-addons'); ?></label>
                            <input type="number" class="kng-fomo-input" data-field="interval" data-section="display" value="10" min="1" max="120">
                            <p class="kng-fomo-field-help"><?php esc_html_e('Seconds', 'king-addons'); ?></p>
                        </div>
                    </div>
                    <div class="kng-fomo-field">
                        <label class="kng-fomo-label"><?php esc_html_e('Max Per Session', 'king-addons'); ?></label>
                        <input type="number" class="kng-fomo-input" data-field="max_per_session" data-section="display" value="5" min="1" max="50">
                        <p class="kng-fomo-field-help"><?php esc_html_e('Maximum notifications to show per visitor session', 'king-addons'); ?></p>
                    </div>
                </div>

                <div class="kng-fomo-wizard-section">
                    <h3 class="kng-fomo-wizard-section-title"><?php esc_html_e('Devices', 'king-addons'); ?></h3>
                    <div style="display: flex; gap: 24px;">
                        <label style="display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" class="kng-fomo-device-check" value="desktop" checked>
                            <span><?php esc_html_e('Desktop', 'king-addons'); ?></span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" class="kng-fomo-device-check" value="tablet" checked>
                            <span><?php esc_html_e('Tablet', 'king-addons'); ?></span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" class="kng-fomo-device-check" value="mobile" checked>
                            <span><?php esc_html_e('Mobile', 'king-addons'); ?></span>
                        </label>
                    </div>
                </div>

                <div class="kng-fomo-wizard-section">
                    <h3 class="kng-fomo-wizard-section-title"><?php esc_html_e('Page Rules', 'king-addons'); ?></h3>
                    <div class="kng-fomo-field">
                        <label class="kng-fomo-label"><?php esc_html_e('Display On', 'king-addons'); ?></label>
                        <select class="kng-fomo-select" data-field="pages" data-section="display">
                            <option value="all"><?php esc_html_e('All Pages', 'king-addons'); ?></option>
                            <option value="specific"><?php esc_html_e('Specific Pages', 'king-addons'); ?></option>
                        </select>
                    </div>
                    <div class="kng-fomo-page-rules" style="margin-top: 16px;"></div>
                    <button type="button" class="kng-fomo-btn kng-fomo-btn--ghost kng-fomo-btn--sm kng-fomo-add-rule" style="margin-top: 12px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>
                        <?php esc_html_e('Add Rule', 'king-addons'); ?>
                    </button>
                </div>

                <div class="kng-fomo-wizard-section">
                    <h3 class="kng-fomo-wizard-section-title"><?php esc_html_e('Audience', 'king-addons'); ?></h3>
                    <div class="kng-fomo-field">
                        <label class="kng-fomo-label"><?php esc_html_e('Show To', 'king-addons'); ?></label>
                        <select class="kng-fomo-select" data-field="audience" data-section="display">
                            <option value="all"><?php esc_html_e('Everyone', 'king-addons'); ?></option>
                            <option value="logged_in"><?php esc_html_e('Logged In Users Only', 'king-addons'); ?></option>
                            <option value="logged_out"><?php esc_html_e('Guests Only', 'king-addons'); ?></option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 5: Customize -->
        <div class="kng-fomo-wizard-panel" data-step="5">
            <div class="kng-fomo-wizard-content">
                <div class="kng-fomo-wizard-section">
                    <h3 class="kng-fomo-wizard-section-title"><?php esc_html_e('Advanced Options', 'king-addons'); ?></h3>
                    <div class="kng-fomo-field">
                        <label class="kng-fomo-label"><?php esc_html_e('Z-Index', 'king-addons'); ?></label>
                        <input type="number" class="kng-fomo-input" data-field="z_index" data-section="customize" value="99999">
                        <p class="kng-fomo-field-help"><?php esc_html_e('Higher values appear on top of other elements', 'king-addons'); ?></p>
                    </div>
                    <div class="kng-fomo-field">
                        <label style="display: flex; align-items: center; gap: 12px;">
                            <input type="checkbox" data-field="close_button" data-section="customize" checked>
                            <span class="kng-fomo-label" style="margin: 0;"><?php esc_html_e('Show Close Button', 'king-addons'); ?></span>
                        </label>
                    </div>
                    <div class="kng-fomo-field">
                        <label class="kng-fomo-label"><?php esc_html_e('Click Action', 'king-addons'); ?></label>
                        <select class="kng-fomo-select" data-field="click_action" data-section="customize">
                            <option value="link"><?php esc_html_e('Open Link', 'king-addons'); ?></option>
                            <option value="dismiss"><?php esc_html_e('Dismiss Notification', 'king-addons'); ?></option>
                            <option value="nothing"><?php esc_html_e('Do Nothing', 'king-addons'); ?></option>
                        </select>
                    </div>
                </div>

                <div class="kng-fomo-wizard-section">
                    <h3 class="kng-fomo-wizard-section-title"><?php esc_html_e('Sound & Analytics', 'king-addons'); ?></h3>
                    <div class="kng-fomo-field">
                        <label style="display: flex; align-items: center; gap: 12px;">
                            <input type="checkbox" data-field="sound" data-section="customize">
                            <span class="kng-fomo-label" style="margin: 0;"><?php esc_html_e('Play Sound', 'king-addons'); ?></span>
                        </label>
                        <p class="kng-fomo-field-help"><?php esc_html_e('Play a subtle notification sound', 'king-addons'); ?></p>
                    </div>
                    <div class="kng-fomo-field">
                        <label style="display: flex; align-items: center; gap: 12px;">
                            <input type="checkbox" data-field="analytics" data-section="customize" checked>
                            <span class="kng-fomo-label" style="margin: 0;"><?php esc_html_e('Enable Analytics', 'king-addons'); ?></span>
                        </label>
                        <p class="kng-fomo-field-help"><?php esc_html_e('Track views and clicks for this notification', 'king-addons'); ?></p>
                    </div>
                </div>

                <!-- Live Preview -->
                <div class="kng-fomo-wizard-section">
                    <h3 class="kng-fomo-wizard-section-title"><?php esc_html_e('Preview', 'king-addons'); ?></h3>
                    <div class="kng-fomo-preview-wrap" style="background: var(--kng-fomo-bg-secondary); border-radius: 16px; padding: 40px; position: relative; min-height: 200px;">
                        <div class="kng-fomo-preview-notification pos-bottom-left has-shadow" style="position: absolute;">
                            <div style="display: flex; gap: 12px; padding: 16px; background: var(--bg-color, #fff); border-radius: var(--border-radius, 16px); box-shadow: 0 4px 20px rgba(0,0,0,0.1); max-width: 340px;">
                                <div style="width: 56px; height: 56px; background: var(--kng-fomo-bg-secondary); border-radius: 10px; flex-shrink: 0;"></div>
                                <div style="flex: 1;">
                                    <div class="kng-fomo-preview-title" style="font-size: 14px; font-weight: 600; color: var(--text-color, #1d1d1f); margin-bottom: 4px;"><?php esc_html_e('John Doe just purchased', 'king-addons'); ?></div>
                                    <div class="kng-fomo-preview-message" style="font-size: 13px; color: var(--text-color, #86868b); opacity: 0.8;"><?php esc_html_e('Premium Product from New York', 'king-addons'); ?></div>
                                    <div style="font-size: 11px; color: var(--accent-color, #0071e3); margin-top: 8px;"><?php esc_html_e('5 mins ago', 'king-addons'); ?></div>
                                </div>
                                <button class="kng-fomo-preview-close" style="position: absolute; top: 8px; right: 8px; width: 24px; height: 24px; background: var(--kng-fomo-bg-secondary); border: none; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="kng-fomo-wizard-footer">
            <button type="button" class="kng-fomo-btn kng-fomo-btn--ghost kng-fomo-wizard-prev" style="display: none;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                <?php esc_html_e('Previous', 'king-addons'); ?>
            </button>
            <div style="flex: 1;"></div>
            <button type="button" class="kng-fomo-btn kng-fomo-btn--primary kng-fomo-wizard-next">
                <?php esc_html_e('Next', 'king-addons'); ?>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </button>
            <button type="button" class="kng-fomo-btn kng-fomo-btn--primary kng-fomo-save-notification" style="display: none;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                <?php esc_html_e('Save Notification', 'king-addons'); ?>
            </button>
        </div>
    </div>

</div>

<style>
.kng-fomo-position-item.is-active {
    border-color: var(--kng-fomo-primary) !important;
    background: var(--kng-fomo-primary-light) !important;
}

.kng-fomo-preview-notification.pos-bottom-left { bottom: 20px; left: 20px; }
.kng-fomo-preview-notification.pos-bottom-right { bottom: 20px; right: 20px; left: auto; }
.kng-fomo-preview-notification.pos-top-left { top: 20px; left: 20px; bottom: auto; }
.kng-fomo-preview-notification.pos-top-right { top: 20px; right: 20px; bottom: auto; left: auto; }
.kng-fomo-preview-notification.pos-bottom-center { bottom: 20px; left: 50%; transform: translateX(-50%); }
.kng-fomo-preview-notification.pos-top-center { top: 20px; left: 50%; transform: translateX(-50%); bottom: auto; }

.kng-fomo-page-rule {
    display: flex;
    gap: 8px;
    margin-bottom: 8px;
}

.kng-fomo-page-rule .kng-fomo-input--sm {
    flex: 1;
}
</style>
