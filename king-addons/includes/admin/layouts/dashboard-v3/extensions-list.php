<?php
/**
 * Extensions list for Dashboard V3.
 *
 * @package King_Addons
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get the list of available extensions.
 *
 * @return array
 */
function king_addons_get_extensions_list(): array {
    $extensions = [
        'templates-catalog' => [
            'title' => esc_html__('Templates Catalog', 'king-addons'),
            'description' => esc_html__('Access to 4,000+ pre-designed Elementor templates and sections that can be imported with one click.', 'king-addons'),
            'icon' => 'dashicons-layout',
            'constant' => 'KING_ADDONS_EXT_TEMPLATES_CATALOG',
            'link' => admin_url('admin.php?page=king-addons-templates'),
        ],
        'header-footer-builder' => [
            'title' => esc_html__('Header & Footer Builder', 'king-addons'),
            'description' => esc_html__('Create custom headers and footers with Elementor and display them anywhere on your site.', 'king-addons'),
            'icon' => 'dashicons-welcome-widgets-menus',
            'constant' => 'KING_ADDONS_EXT_HEADER_FOOTER_BUILDER',
            'link' => admin_url('edit.php?post_type=king-addons-el-hf'),
        ],
        'popup-builder' => [
            'title' => esc_html__('Popup Builder', 'king-addons'),
            'description' => esc_html__('Design and display beautiful popups with triggers, animations, and display conditions.', 'king-addons'),
            'icon' => 'dashicons-external',
            'constant' => 'KING_ADDONS_EXT_POPUP_BUILDER',
            'link' => admin_url('admin.php?page=king-addons-popup-builder'),
        ],
        'woo-builder' => [
            'title' => esc_html__('WooCommerce Builder', 'king-addons'),
            'description' => esc_html__('Customize WooCommerce product pages, shop pages, cart, and checkout with Elementor.', 'king-addons'),
            'icon' => 'dashicons-cart',
            'constant' => 'KING_ADDONS_EXT_WOO_BUILDER',
            'link' => admin_url('admin.php?page=king-addons-woo-builder'),
            'requires' => [
                'woocommerce' => [
                    'name' => esc_html__('WooCommerce', 'king-addons'),
                    'install_url' => admin_url('plugin-install.php?s=woocommerce&tab=search&type=term'),
                    'message' => esc_html__('Requires WooCommerce to be installed and activated.', 'king-addons'),
                ],
            ],
        ],
        'cookie-consent' => [
            'title' => esc_html__('Cookie / Consent Bar', 'king-addons'),
            'description' => esc_html__('GDPR-compliant cookie consent bar with customizable appearance and behavior.', 'king-addons'),
            'icon' => 'dashicons-shield',
            'constant' => 'KING_ADDONS_EXT_COOKIE_CONSENT',
            'link' => admin_url('admin.php?page=king-addons-cookie-consent'),
        ],
        'age-gate' => [
            'title' => esc_html__('Age Gate', 'king-addons'),
            'description' => esc_html__('Age verification popup for restricted content with customizable design.', 'king-addons'),
            'icon' => 'dashicons-id-alt',
            'constant' => 'KING_ADDONS_EXT_AGE_GATE',
            'link' => admin_url('admin.php?page=king-addons-age-gate'),
        ],
        'live-chat' => [
            'title' => esc_html__('Live Chat', 'king-addons'),
            'description' => esc_html__('Real-time chat support widget with admin inbox for customer conversations and email notifications.', 'king-addons'),
            'icon' => 'dashicons-format-chat',
            'constant' => 'KING_ADDONS_EXT_LIVE_CHAT',
            'link' => admin_url('admin.php?page=king-addons-live-chat'),
        ],
        'sticky-contact-bar' => [
            'title' => esc_html__('Sticky Contact Bar', 'king-addons'),
            'description' => esc_html__('Displays a fixed contact bar with messaging, call, and custom action buttons on the edge of the screen.', 'king-addons'),
            'icon' => 'dashicons-phone',
            'constant' => 'KING_ADDONS_EXT_STICKY_CONTACT_BAR',
            'link' => admin_url('admin.php?page=king-addons-sticky-contact-bar'),
        ],
        'custom-cursor' => [
            'title' => esc_html__('Custom Cursor', 'king-addons'),
            'description' => esc_html__('Replace the default cursor with presets, hover states, Elementor-aware interactions, and Pro-only magnetic and image options.', 'king-addons'),
            'icon' => 'dashicons-move',
            'constant' => 'KING_ADDONS_EXT_CUSTOM_CURSOR',
            'link' => admin_url('admin.php?page=king-addons-custom-cursor'),
        ],
        'theme-builder' => [
            'title' => esc_html__('Theme Builder', 'king-addons'),
            'description' => esc_html__('Create custom Elementor templates for single posts, pages, custom post types, archives, search and 404 pages with flexible display conditions.', 'king-addons'),
            'icon' => 'dashicons-admin-appearance',
            'constant' => 'KING_ADDONS_EXT_THEME_BUILDER',
            'link' => admin_url('admin.php?page=king-addons-theme-builder'),
        ],
        'wishlist' => [
            'title' => esc_html__('Wishlist', 'king-addons'),
            'description' => esc_html__('Complete wishlist system for WooCommerce with buttons, counters, pages, and advanced features for managing customer wishlists.', 'king-addons'),
            'icon' => 'dashicons-heart',
            'constant' => 'KING_ADDONS_EXT_WISHLIST',
            'link' => admin_url('admin.php?page=king-addons-wishlist'),
        ],
        'smart-links' => [
            'title' => esc_html__('Smart Links', 'king-addons'),
            'description' => esc_html__('Create short links with click tracking, UTM builder, tags, and Premium style inspired analytics.', 'king-addons'),
            'icon' => 'dashicons-admin-links',
            'constant' => 'KING_ADDONS_EXT_SMART_LINKS',
            'link' => admin_url('admin.php?page=king-addons-smart-links'),
        ],
        'activity-log' => [
            'title' => esc_html__('Activity Log', 'king-addons'),
            'description' => esc_html__('Track admin activity, content changes, and security events with clear audit trails.', 'king-addons'),
            'icon' => 'dashicons-shield-alt',
            'constant' => 'KING_ADDONS_EXT_ACTIVITY_LOG',
            'link' => admin_url('admin.php?page=king-addons-activity-log'),
        ],
        'maintenance-mode' => [
            'title' => esc_html__('Maintenance Mode', 'king-addons'),
            'description' => esc_html__('Launch coming soon or maintenance pages with scheduling and access rules.', 'king-addons'),
            'icon' => 'dashicons-hammer',
            'constant' => 'KING_ADDONS_EXT_MAINTENANCE_MODE',
            'link' => admin_url('admin.php?page=king-addons-maintenance-mode'),
        ],
        'table-builder' => [
            'title' => esc_html__('Table Builder', 'king-addons'),
            'description' => esc_html__('Build interactive tables with sorting, search, pagination, and Premium style inspired presets.', 'king-addons'),
            'icon' => 'dashicons-grid-view',
            'constant' => 'KING_ADDONS_EXT_TABLE_BUILDER',
            'link' => admin_url('admin.php?page=king-addons-table-builder'),
        ],
        'docs-kb' => [
            'title' => esc_html__('Docs & Knowledge Base', 'king-addons'),
            'description' => esc_html__('Create beautiful documentation with categories, search, table of contents, and Premium style inspired layouts.', 'king-addons'),
            'icon' => 'dashicons-book-alt',
            'constant' => 'KING_ADDONS_EXT_DOCS_KB',
            'link' => admin_url('admin.php?page=king-addons-docs-kb'),
        ],
        'pricing-table-builder' => [
            'title' => esc_html__('Pricing Table Builder', 'king-addons'),
            'description' => esc_html__('Create stunning pricing tables with billing toggle, multiple presets, and Premium style inspired design.', 'king-addons'),
            'icon' => 'dashicons-editor-table',
            'constant' => 'KING_ADDONS_EXT_PRICING_TABLE_BUILDER',
            'link' => admin_url('admin.php?page=king-addons-pricing-tables'),
        ],
        'custom-code-manager' => [
            'title' => esc_html__('Custom Code Manager', 'king-addons'),
            'description' => esc_html__('Add custom CSS, JavaScript, and HTML snippets with advanced targeting rules, locations, and code highlighting.', 'king-addons'),
            'icon' => 'dashicons-editor-code',
            'constant' => 'KING_ADDONS_EXT_CUSTOM_CODE_MANAGER',
            'link' => admin_url('admin.php?page=king-addons-custom-code'),
        ],
        'fomo-notifications' => [
            'title' => esc_html__('Fomo Notifications', 'king-addons'),
            'description' => esc_html__('Social proof notifications with WooCommerce sales, reviews, comments, and custom data sources. Boost conversions with real-time FOMO.', 'king-addons'),
            'icon' => 'dashicons-megaphone',
            'constant' => 'KING_ADDONS_EXT_FOMO_NOTIFICATIONS',
            'link' => admin_url('admin.php?page=king-addons-fomo'),
        ],
        'site-preloader' => [
            'title' => esc_html__('Site Preloader Animation', 'king-addons'),
            'description' => esc_html__('Beautiful page loading animations with 12+ presets, custom colors, display rules, and Premium style inspired design.', 'king-addons'),
            'icon' => 'dashicons-update',
            'constant' => 'KING_ADDONS_EXT_SITE_PRELOADER',
            'link' => admin_url('admin.php?page=king-addons-site-preloader'),
        ],

        'image-optimizer' => [
            'title' => esc_html__('Image Optimizer', 'king-addons'),
            'description' => esc_html__('Optimize images in your Media Library to reduce file sizes, improve website performance, and boost SEO.', 'king-addons'),
            'icon' => 'dashicons-format-image',
            'constant' => 'KING_ADDONS_EXT_IMAGE_OPTIMIZER',
            'link' => admin_url('admin.php?page=king-addons-image-optimizer'),
        ],

    ];

    // If a constant is defined and false, hide the extension entirely.
    // This allows developers to keep in-progress extensions out of the UI.
    return array_filter(
        $extensions,
        static function (array $ext): bool {
            $constant_name = $ext['constant'] ?? '';
            if ($constant_name === '') {
                return true;
            }
            if (!defined($constant_name)) {
                return true;
            }
            return (bool) constant($constant_name);
        }
    );
}
