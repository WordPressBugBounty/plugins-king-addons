<?php

/** @noinspection SpellCheckingInspection */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/** ELEMENTOR ICONS  - Icon for Elementor editor with inline styles included */
const KING_ADDONS_ELEMENTOR_ICON = '<img src="' . KING_ADDONS_URL . 'includes/admin/img/icon-for-elementor.svg" alt="King Addons" style="width: 13px; margin-right: 5px; vertical-align: top;">';
const KING_ADDONS_ELEMENTOR_ICON_PRO = '<img src="' . KING_ADDONS_URL . 'includes/admin/img/icon-for-elementor-v2.svg" alt="King Addons" style="width: 15px; margin-right: 5px; vertical-align: top;">';

/** EXTENSIONS - Enable/Disable */
const KING_ADDONS_EXT_TEMPLATES_CATALOG = true;
const KING_ADDONS_EXT_HEADER_FOOTER_BUILDER = true;
const KING_ADDONS_EXT_POPUP_BUILDER = true;

/** WIDGETS - Enable/Disable */
const KING_ADDONS_WGT_FORM_BUILDER = true;
const KING_ADDONS_WGT_MEGA_MENU = true;

/** ------------------------------------------------------- */
/** New constants for QA rollout after 20 December 2025 */

/** EXTENSIONS - Enable/Disable (NEW: disabled by default for QA rollout) */

// Extensions - Easy segment
const KING_ADDONS_EXT_AGE_GATE = true; /** DONE */
const KING_ADDONS_EXT_MAINTENANCE_MODE = true; /** DONE */
const KING_ADDONS_EXT_SITE_PRELOADER = true; /** DONE */
const KING_ADDONS_EXT_CUSTOM_CURSOR = true; /** DONE */

//Extensions - Middle segment
const KING_ADDONS_EXT_COOKIE_CONSENT = true; /** DONE */
const KING_ADDONS_EXT_SMART_LINKS = false;
const KING_ADDONS_EXT_ACTIVITY_LOG = true; /** DONE */
const KING_ADDONS_EXT_CUSTOM_CODE_MANAGER = true; /** DONE */
const KING_ADDONS_EXT_LIVE_CHAT = false;
const KING_ADDONS_EXT_STICKY_CONTACT_BAR = false;
const KING_ADDONS_EXT_DOCS_KB = false;

// Extensions - Upper segment
const KING_ADDONS_EXT_TABLE_BUILDER = false;
const KING_ADDONS_EXT_PRICING_TABLE_BUILDER = false;
const KING_ADDONS_EXT_FOMO_NOTIFICATIONS = false;

// Extensions - Complex and the most important
const KING_ADDONS_EXT_THEME_BUILDER = true; /** IN PROGRESS OF QA */
const KING_ADDONS_EXT_WOO_BUILDER = true; /** IN PROGRESS OF QA */

// Extensions - Advanced segment
const KING_ADDONS_EXT_WISHLIST = false;
const KING_ADDONS_EXT_IMAGE_OPTIMIZER = true; /** DONE */


/** FEATURES - Enable/Disable (NEW: disabled by default for QA rollout) */
const KING_ADDONS_FEAT_CONDITIONAL_DISPLAY = false;
const KING_ADDONS_FEAT_PROTECTED_CONTENT = false;
const KING_ADDONS_FEAT_ANIMATED_GRADIENT_MESH_BACKGROUND = false; // Need fixes

// Features - Others
const KING_ADDONS_FEAT_COOKIE_PREFERENCES_BUTTON = false;
const KING_ADDONS_FEAT_FACETED_FILTERS = false;
const KING_ADDONS_FEAT_PRICING_TABLE_EXT = false;
const KING_ADDONS_FEAT_STICKY_VIDEO = false;
const KING_ADDONS_FEAT_WISHLIST_BUTTON = false;
const KING_ADDONS_FEAT_WISHLIST_COUNTER = false;
const KING_ADDONS_FEAT_WISHLIST_ICON = false;
const KING_ADDONS_FEAT_WISHLIST_MINI_LIST = false;
const KING_ADDONS_FEAT_WISHLIST_MULTIPLE_LISTS_SWITCHER = false;
const KING_ADDONS_FEAT_WISHLIST_PAGE = false;
const KING_ADDONS_FEAT_WISHLIST_SHARE_BUTTONS = false;
const KING_ADDONS_FEAT_WOOCOMMERCE_FLOATING_CART_ICON = false;

/** WIDGETS - Enable/Disable (NEW: disabled by default for QA rollout) */
const KING_ADDONS_WGT_BREADCRUMBS = true; /** DONE */ // 2 =====
const KING_ADDONS_WGT_QUICK_CARD_SLIDER = true; /** DONE */ // 1 - QA passed
const KING_ADDONS_WGT_CUSTOM_POST_TYPES_GRID = false;
const KING_ADDONS_WGT_CUSTOM_POST_TYPES_SLIDER = false;
const KING_ADDONS_WGT_EVENT_CALENDAR = false;
const KING_ADDONS_WGT_FAQ_SCHEMA = false; // Need fixes
const KING_ADDONS_WGT_IMAGE_MARQUEE = false; // 3 =====
const KING_ADDONS_WGT_PRODUCT_360_VIEWER = false; // Moved from ModulesMap features -> widgets (disabled for QA rollout)
const KING_ADDONS_WGT_PHONE_CALL_BUTTON = true; /** DONE */ // 4 - QA passed 
const KING_ADDONS_WGT_QUICK_PRODUCT_SLIDER = true; /** DONE */ // 14 =====
const KING_ADDONS_WGT_QUICK_PRODUCT_GRID = true; /** DONE */ // 15 =====
const KING_ADDONS_WGT_QUICK_VIEW_PRODUCT = false;
const KING_ADDONS_WGT_REVIEW_SCHEMA = false; // 5
const KING_ADDONS_WGT_PROS_CONS_BOX = false; // Good but Need fixes
const KING_ADDONS_WGT_ROTATING_IMAGE_TILES = false; // 11 ---
const KING_ADDONS_WGT_QUICK_CARD_GRID = true; /** DONE */ // 7 - QA passed
const KING_ADDONS_WGT_QUICK_POST_GRID = true; /** DONE */ // 8 ====
const KING_ADDONS_WGT_QUICK_POST_SLIDER = true; /** DONE */ // 9 ====
const KING_ADDONS_WGT_SINGLE_PRODUCT = false; // 16 =====
const KING_ADDONS_WGT_STEPS_PROCESS_TIMELINE = true; // Good
const KING_ADDONS_WGT_LIQUID_GLASS_CARDS = false; // Need fixes
const KING_ADDONS_WGT_SCROLL_STORY_SECTIONS = false; // Good but Need fixes
const KING_ADDONS_WGT_UNFOLD = false; // Bad, maybe remove or redo
const KING_ADDONS_WGT_CONTENT_TOGGLE = false; // Bad, maybe remove or redo

// Widgets - Other, not sure where to categorize them - the numbers of new widgets added: 
const KING_ADDONS_WGT_PROMO_BAR = false;
const KING_ADDONS_WGT_ADVANCED_CALLOUT_BOX = true; /** DONE */ // QA passed
const KING_ADDONS_WGT_TESTIMONIALS_WALL = false; // Good but Need fixes and QA
const KING_ADDONS_WGT_PARALLAX_DEPTH_CARDS = false; // Good but Need fixes
const KING_ADDONS_WGT_KPI_TILES_MICROCHARTS = true;  /** DONE */ // Great, needs QA
const KING_ADDONS_WGT_MAGNETIC_BUTTONS = true; // Great, needs QA
const KING_ADDONS_WGT_KINETIC_TEXT_HOVER = false; // Need fixes
const KING_ADDONS_WGT_PULL_QUOTES_CALLOUTS_BUILDER = false; // Good but Need fixes
const KING_ADDONS_WGT_INTERACTIVE_GRADIENT_MESH = true; /** DONE */ // Good, needs small fixes
const KING_ADDONS_WGT_HOLOGRAPHIC_CARD = true; /** DONE */ // Great, but Need small fixes and QA
const KING_ADDONS_WGT_INTERACTIVE_STEPS_PROGRESS = false; // Bad
const KING_ADDONS_WGT_REVEAL_SWIPE_CARDS = false; // Need fixes
const KING_ADDONS_WGT_FLOATING_TAGS_MARQUEE = false; // Need fixes
const KING_ADDONS_WGT_INTERACTIVE_IMAGE_SHEEN = false; // Good
const KING_ADDONS_WGT_SCROLLYTELLING_SLIDES = false; // Good
const KING_ADDONS_WGT_STICKY_CONTACT_BAR = false; // Moved from ModulesMap features -> widgets (disabled for QA rollout)
const KING_ADDONS_WGT_SPOTLIGHT_REVEAL = true; /** DONE */ // Great, but Need small fixes and QA
const KING_ADDONS_WGT_AJAX_ADD_TO_CART = true; /** DONE */ // woo builder but supports custom id product
const KING_ADDONS_WGT_COMPARE_TABLE = true; /** DONE */ // 13 - QA passed
const KING_ADDONS_WGT_COMPARISON_MATRIX_CARDS = false; // Good, needs QA

// Widgets - Parts of extensions (NEW) - the numbers of new widgets added: 
const KING_ADDONS_WGT_TABLE_BUILDER = false;
const KING_ADDONS_WGT_MAINTENANCE_PAGE = false;
const KING_ADDONS_WGT_COOKIE_PREFERENCES_BUTTON = false; // Moved from ModulesMap features -> widgets (disabled for QA rollout)
const KING_ADDONS_WGT_STICKY_VIDEO = false; // Moved from ModulesMap features -> widgets (disabled for QA rollout)
const KING_ADDONS_WGT_WISHLIST_BUTTON = false; // Moved from ModulesMap features -> widgets (disabled for QA rollout)
const KING_ADDONS_WGT_WISHLIST_COUNTER = false; // Moved from ModulesMap features -> widgets (disabled for QA rollout)
const KING_ADDONS_WGT_WISHLIST_ICON = false; // Moved from ModulesMap features -> widgets (disabled for QA rollout)
const KING_ADDONS_WGT_WISHLIST_MINI_LIST = false; // Moved from ModulesMap features -> widgets (disabled for QA rollout)
const KING_ADDONS_WGT_WISHLIST_MULTIPLE_LISTS_SWITCHER = false; // Moved from ModulesMap features -> widgets (disabled for QA rollout)
const KING_ADDONS_WGT_WISHLIST_PAGE = false; // Moved from ModulesMap features -> widgets (disabled for QA rollout)
const KING_ADDONS_WGT_WISHLIST_SHARE_BUTTONS = false; // Moved from ModulesMap features -> widgets (disabled for QA rollout)
const KING_ADDONS_WGT_WOOCOMMERCE_FLOATING_CART_ICON = false; // Moved from ModulesMap features -> widgets (disabled for QA rollout)
const KING_ADDONS_WGT_PRICING_TABLE_EXT = false; // Moved from ModulesMap features -> widgets (disabled for QA rollout)

// Widgets - Faceted Filters (NEW) - the numbers of new widgets added: 6
const KING_ADDONS_WGT_FACET_ACTIVE_FILTERS = false;
const KING_ADDONS_WGT_FACET_META = false;
const KING_ADDONS_WGT_FACET_PRICE = false;
const KING_ADDONS_WGT_FACET_RESET = false;
const KING_ADDONS_WGT_FACET_SEARCH = false;
const KING_ADDONS_WGT_FACET_TAXONOMY = false;

// Widgets - Theme Builder (NEW) - the numbers of new widgets added: 19
const KING_ADDONS_WGT_TB_404_DESCRIPTION = false;
const KING_ADDONS_WGT_TB_404_SEARCH_FORM = false;
const KING_ADDONS_WGT_TB_404_TITLE = false;
const KING_ADDONS_WGT_TB_ARCHIVE_DESCRIPTION = false;
const KING_ADDONS_WGT_TB_ARCHIVE_PAGINATION = false;
const KING_ADDONS_WGT_TB_ARCHIVE_POSTS = false;
const KING_ADDONS_WGT_TB_ARCHIVE_RESULT_COUNT = false;
const KING_ADDONS_WGT_TB_ARCHIVE_TITLE = false;
const KING_ADDONS_WGT_TB_AUTHOR_BOX = false;
const KING_ADDONS_WGT_TB_BACK_TO_HOME = false;
const KING_ADDONS_WGT_TB_FEATURED_IMAGE = false;
const KING_ADDONS_WGT_TB_POST_COMMENTS = false;
const KING_ADDONS_WGT_TB_POST_CONTENT = false;
const KING_ADDONS_WGT_TB_POST_EXCERPT = false;
const KING_ADDONS_WGT_TB_POST_META = false;
const KING_ADDONS_WGT_TB_POST_NAVIGATION = false;
const KING_ADDONS_WGT_TB_POST_TAXONOMIES = false;
const KING_ADDONS_WGT_TB_POST_TITLE = false;
const KING_ADDONS_WGT_TB_RELATED_POSTS = false;


// Widgets - WooCommerce Builder (NEW) - phased rollout plan (52 widgets)

// WooCommerce Builder Widgets - MVP All Pages + dev order
// Phase 1 (MVP) must cover ALL pages: Single Product, Shop & Category, Cart, Checkout, My Account
// Below is the exact build order (1..N) for Phase 1, then Phase 2..4 in recommended order.

// ================================
// Phase 1 (MVP) - Build Order (ALL pages working)
// ================================

// --- Core infra (required for everything) ---
/* P1-01 */ const KING_ADDONS_WGT_WOO_PRODUCTS_GRID = false;            // Page: Shop & Category | Core listing output
/* P1-02 */ const KING_ADDONS_WGT_WOO_PRODUCT_IMAGES_GALLERY = false;   // Page: Single Product | Visual anchor for PDP
/* P1-03 */ const KING_ADDONS_WGT_WOO_PRODUCT_TITLE = false;             // Page: Single Product | qa test
/* P1-04 */ const KING_ADDONS_WGT_WOO_PRODUCT_PRICE = false;            // Page: Single Product | Core commercial signal
/* P1-05 */ const KING_ADDONS_WGT_WOO_PRODUCT_SHORT_DESCRIPTION = false;// Page: Single Product | Key pitch copy
/* P1-06 */ const KING_ADDONS_WGT_WOO_PRODUCT_VARIATIONS = false;       // Page: Single Product | Must-have for variable products
/* P1-07 */ const KING_ADDONS_WGT_WOO_PRODUCT_ADD_TO_CART = false;      // Page: Single Product | Purchase action
/* P1-08 */ const KING_ADDONS_WGT_WOO_PRODUCT_STOCK = false;            // Page: Single Product | Availability signal
/* P1-09 */ const KING_ADDONS_WGT_WOO_PRODUCT_TABS = false;             // Page: Single Product | Description + reviews baseline

// --- Shop & Category completion ---
/* P1-10 */ const KING_ADDONS_WGT_WOO_PRODUCTS_SORTING = false;         // Page: Shop & Category | Sort dropdown
/* P1-11 */ const KING_ADDONS_WGT_WOO_PRODUCTS_RESULT_COUNT = false;    // Page: Shop & Category | Result count text
/* P1-12 */ const KING_ADDONS_WGT_WOO_PRODUCTS_PAGINATION = false;      // Page: Shop & Category | Paging navigation
/* P1-13 */ const KING_ADDONS_WGT_WOO_ARCHIVE_TITLE = false;            // Page: Shop & Category | Category/shop title

// --- Cart completion ---
/* P1-14 */ const KING_ADDONS_WGT_WOO_CART_TABLE = false;               // Page: Cart | Line items table
/* P1-15 */ const KING_ADDONS_WGT_WOO_CART_TOTALS = false;              // Page: Cart | Totals + proceed to checkout
/* P1-16 */ const KING_ADDONS_WGT_WOO_CART_EMPTY = false;               // Page: Cart | Empty state handling

// --- Checkout completion ---
/* P1-17 */ const KING_ADDONS_WGT_WOO_CHECKOUT_FORM = false;            // Page: Checkout | Billing/shipping fields + notices
/* P1-18 */ const KING_ADDONS_WGT_WOO_CHECKOUT_ORDER_SUMMARY = false;   // Page: Checkout | Order review table
/* P1-19 */ const KING_ADDONS_WGT_WOO_CHECKOUT_PAYMENT = false;         // Page: Checkout | Payment methods + payment box
/* P1-20 */ const KING_ADDONS_WGT_WOO_CHECKOUT_PLACE_ORDER = false;     // Page: Checkout | Place order button + terms

// --- My Account completion ---
/* P1-21 */ const KING_ADDONS_WGT_WOO_MY_ACCOUNT_NAVIGATION = false;    // Page: My Account | Menu/tabs
/* P1-22 */ const KING_ADDONS_WGT_WOO_MY_ACCOUNT_CONTENT = false;       // Page: My Account | Endpoint content renderer/router container
/* P1-23 */ const KING_ADDONS_WGT_WOO_MY_ACCOUNT_DASHBOARD = false;     // Page: My Account | Default landing content
/* P1-24 */ const KING_ADDONS_WGT_WOO_MY_ACCOUNT_LOGOUT = false;        // Page: My Account | Logout link/button

// --- MVP polish (optional inside Phase 1, but recommended if time allows) ---
/* P1-25 */ const KING_ADDONS_WGT_WOO_PRODUCT_RELATED = false;          // Page: Single Product | Common expectation, improves UX


// ================================
// Phase 2 (MVP+) - Reduce friction + match common Woo expectations
// Recommended order inside Phase 2
// ================================

// Cart
/* P2-01 */ const KING_ADDONS_WGT_WOO_CART_COUPON_FORM = false;         // Page: Cart | Coupon input
/* P2-02 */ const KING_ADDONS_WGT_WOO_CART_CROSS_SELLS = false;         // Page: Cart | Cross-sells block

// Checkout
/* P2-03 */ const KING_ADDONS_WGT_WOO_CHECKOUT_LOGIN = false;           // Page: Checkout | Returning customer login
/* P2-04 */ const KING_ADDONS_WGT_WOO_CHECKOUT_COUPON = false;          // Page: Checkout | Coupon on checkout

// Shop & Category
/* P2-05 */ const KING_ADDONS_WGT_WOO_ARCHIVE_DESCRIPTION = false;      // Page: Shop & Category | Term description
/* P2-06 */ const KING_ADDONS_WGT_WOO_ARCHIVE_BANNER = false;           // Page: Shop & Category | Hero/banner area

// Single Product
/* P2-07 */ const KING_ADDONS_WGT_WOO_PRODUCT_BREADCRUMBS = false;      // Page: Single Product | Navigation clarity
/* P2-08 */ const KING_ADDONS_WGT_WOO_PRODUCT_RATING = false;           // Page: Single Product | Social proof
/* P2-09 */ const KING_ADDONS_WGT_WOO_PRODUCT_SKU = false;              // Page: Single Product | Merchant ops
/* P2-10 */ const KING_ADDONS_WGT_WOO_PRODUCT_META = false;             // Page: Single Product | Categories/tags meta
/* P2-11 */ const KING_ADDONS_WGT_WOO_PRODUCT_FULL_DESCRIPTION = false; // Page: Single Product | If separate from tabs needed
/* P2-12 */ const KING_ADDONS_WGT_WOO_PRODUCT_UPSELL = false;           // Page: Single Product | Upsells
/* P2-13 */ const KING_ADDONS_WGT_WOO_PRODUCT_CROSS_SELL = false;       // Page: Single Product | Cross-sells (PDP)

// My Account
/* P2-14 */ const KING_ADDONS_WGT_WOO_MY_ACCOUNT_ORDERS = false;        // Page: My Account | Orders list
/* P2-15 */ const KING_ADDONS_WGT_WOO_MY_ACCOUNT_ORDER_DETAILS = false; // Page: My Account | Single order details
/* P2-16 */ const KING_ADDONS_WGT_WOO_MY_ACCOUNT_ADDRESS = false;       // Page: My Account | Addresses endpoint


// ================================
// Phase 3 - Advanced layout blocks (premium-feel UX)
// Recommended order inside Phase 3
// ================================

// Checkout
/* P3-01 */ const KING_ADDONS_WGT_WOO_CHECKOUT_STEPS = false;           // Page: Checkout | Stepper UI
/* P3-02 */ const KING_ADDONS_WGT_WOO_CHECKOUT_PROGRESS = false;        // Page: Checkout | Progress indicator
/* P3-03 */ const KING_ADDONS_WGT_WOO_CHECKOUT_STICKY_SIDEBAR = false;  // Page: Checkout | Sticky summary/sidebar

// Single Product
/* P3-04 */ const KING_ADDONS_WGT_WOO_PRODUCT_BADGES = false;           // Page: Single Product | Sale/new/out-of-stock badges
/* P3-05 */ const KING_ADDONS_WGT_WOO_PRODUCT_COUNTDOWN = false;        // Page: Single Product | Promo countdown
/* P3-06 */ const KING_ADDONS_WGT_WOO_PRODUCT_CUSTOM_TABS = false;      // Page: Single Product | Extra tabs

// My Account
/* P3-07 */ const KING_ADDONS_WGT_WOO_MY_ACCOUNT_DETAILS = false;       // Page: My Account | Account details form
/* P3-08 */ const KING_ADDONS_WGT_WOO_MY_ACCOUNT_DOWNLOADS = false;     // Page: My Account | Downloads endpoint


// ================================
// Phase 4 - Integrations / power fields (ACF-driven)
// Recommended order inside Phase 4
// ================================

/* P4-01 */ const KING_ADDONS_WGT_WOO_PRODUCT_ACF_FIELD = false;        // Page: Single Product | ACF output field
/* P4-02 */ const KING_ADDONS_WGT_WOO_CHECKOUT_ACF_FIELDS = false;      // Page: Checkout | ACF fields block
/* P4-03 */ const KING_ADDONS_WGT_WOO_MY_ACCOUNT_ACF_FIELDS = false;    // Page: My Account | ACF fields block


// ================================
// WooCommerce Shortcode Wrapper Widgets (Quick Functional Widgets)
// These widgets render standard WooCommerce shortcodes with default styles.
// Useful for quick page building while custom widgets are in development.
// ================================

const KING_ADDONS_WGT_WOO_SHORTCODE_CART = true;               // [woocommerce_cart] shortcode wrapper
const KING_ADDONS_WGT_WOO_SHORTCODE_CHECKOUT = true;           // [woocommerce_checkout] shortcode wrapper
const KING_ADDONS_WGT_WOO_SHORTCODE_MY_ACCOUNT = true;         // [woocommerce_my_account] shortcode wrapper
const KING_ADDONS_WGT_WOO_SHORTCODE_PRODUCTS = true;           // [products] shortcode wrapper with query options
const KING_ADDONS_WGT_WOO_SHORTCODE_ORDER_TRACKING = true;     // [woocommerce_order_tracking] shortcode wrapper
const KING_ADDONS_WGT_WOO_SHORTCODE_PRODUCT_PAGE = true;       // [product_page] single product page by ID/SKU
const KING_ADDONS_WGT_WOO_SHORTCODE_PRODUCT_CATEGORY = true;   // [product_category] products from category
const KING_ADDONS_WGT_WOO_SHORTCODE_PRODUCT_CATEGORIES = true; // [product_categories] product categories grid
const KING_ADDONS_WGT_WOO_SHORTCODE_ADD_TO_CART = true;        // [add_to_cart] button for specific product
const KING_ADDONS_WGT_WOO_SHORTCODE_SHOP_MESSAGES = true;      // [shop_messages] store notices/messages


/** ------------------------------------------------------- **/
// SHORT LIST OF ALL WOO WIDGET CONSTANTS FOR EASY ACCESS

// const KING_ADDONS_WGT_WOO_ARCHIVE_BANNER = false;
// const KING_ADDONS_WGT_WOO_ARCHIVE_DESCRIPTION = false;
// const KING_ADDONS_WGT_WOO_ARCHIVE_TITLE = false;
// const KING_ADDONS_WGT_WOO_CART_COUPON_FORM = false;
// const KING_ADDONS_WGT_WOO_CART_CROSS_SELLS = false;
// const KING_ADDONS_WGT_WOO_CART_EMPTY = false;
// const KING_ADDONS_WGT_WOO_CART_TABLE = false;
// const KING_ADDONS_WGT_WOO_CART_TOTALS = false;
// const KING_ADDONS_WGT_WOO_CHECKOUT_ACF_FIELDS = false;
// const KING_ADDONS_WGT_WOO_CHECKOUT_COUPON = false;
// const KING_ADDONS_WGT_WOO_CHECKOUT_FORM = false;
// const KING_ADDONS_WGT_WOO_CHECKOUT_LOGIN = false;
// const KING_ADDONS_WGT_WOO_CHECKOUT_ORDER_SUMMARY = false;
// const KING_ADDONS_WGT_WOO_CHECKOUT_PAYMENT = false;
// const KING_ADDONS_WGT_WOO_CHECKOUT_PLACE_ORDER = false;
// const KING_ADDONS_WGT_WOO_CHECKOUT_PROGRESS = false;
// const KING_ADDONS_WGT_WOO_CHECKOUT_STEPS = false;
// const KING_ADDONS_WGT_WOO_CHECKOUT_STICKY_SIDEBAR = false;
// const KING_ADDONS_WGT_WOO_MY_ACCOUNT_ACF_FIELDS = false;
// const KING_ADDONS_WGT_WOO_MY_ACCOUNT_ADDRESS = false;
// const KING_ADDONS_WGT_WOO_MY_ACCOUNT_CONTENT = false;
// const KING_ADDONS_WGT_WOO_MY_ACCOUNT_DASHBOARD = false;
// const KING_ADDONS_WGT_WOO_MY_ACCOUNT_DETAILS = false;
// const KING_ADDONS_WGT_WOO_MY_ACCOUNT_DOWNLOADS = false;
// const KING_ADDONS_WGT_WOO_MY_ACCOUNT_LOGOUT = false;
// const KING_ADDONS_WGT_WOO_MY_ACCOUNT_NAVIGATION = false;
// const KING_ADDONS_WGT_WOO_MY_ACCOUNT_ORDER_DETAILS = false;
// const KING_ADDONS_WGT_WOO_MY_ACCOUNT_ORDERS = false;
// const KING_ADDONS_WGT_WOO_PRODUCT_ACF_FIELD = false;
// const KING_ADDONS_WGT_WOO_PRODUCT_ADD_TO_CART = false;
// const KING_ADDONS_WGT_WOO_PRODUCT_BADGES = false;
// const KING_ADDONS_WGT_WOO_PRODUCT_BREADCRUMBS = false;
// const KING_ADDONS_WGT_WOO_PRODUCT_COUNTDOWN = false;
// const KING_ADDONS_WGT_WOO_PRODUCT_CROSS_SELL = false;
// const KING_ADDONS_WGT_WOO_PRODUCT_CUSTOM_TABS = false;
// const KING_ADDONS_WGT_WOO_PRODUCT_FULL_DESCRIPTION = false;
// const KING_ADDONS_WGT_WOO_PRODUCT_IMAGES_GALLERY = false;
// const KING_ADDONS_WGT_WOO_PRODUCT_META = false;
// const KING_ADDONS_WGT_WOO_PRODUCT_PRICE = false;
// const KING_ADDONS_WGT_WOO_PRODUCT_RATING = false;
// const KING_ADDONS_WGT_WOO_PRODUCT_RELATED = false;
// const KING_ADDONS_WGT_WOO_PRODUCT_SHORT_DESCRIPTION = false;
// const KING_ADDONS_WGT_WOO_PRODUCT_SKU = false;
// const KING_ADDONS_WGT_WOO_PRODUCT_STOCK = false;
// const KING_ADDONS_WGT_WOO_PRODUCT_TABS = false;
// const KING_ADDONS_WGT_WOO_PRODUCT_TITLE = true; // qa test
// const KING_ADDONS_WGT_WOO_PRODUCT_UPSELL = false;
// const KING_ADDONS_WGT_WOO_PRODUCT_VARIATIONS = false;
// const KING_ADDONS_WGT_WOO_PRODUCTS_GRID = false;
// const KING_ADDONS_WGT_WOO_PRODUCTS_PAGINATION = false;
// const KING_ADDONS_WGT_WOO_PRODUCTS_RESULT_COUNT = false;
// const KING_ADDONS_WGT_WOO_PRODUCTS_SORTING = false;
