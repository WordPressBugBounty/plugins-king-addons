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
const KING_ADDONS_EXT_LIVE_CHAT = false; // contact form getting messages needs fixes
const KING_ADDONS_EXT_STICKY_CONTACT_BAR = false; // ok, but needs some fixes and QA
const KING_ADDONS_EXT_DOCS_KB = false; // maybe needs some fixes and QA, but the core is good to go

// Extensions - Upper segment
const KING_ADDONS_EXT_TABLE_BUILDER = false; // needs fixes
const KING_ADDONS_EXT_PRICING_TABLE_BUILDER = false; // needs fixes
const KING_ADDONS_EXT_FOMO_NOTIFICATIONS = false; // create the new extension for this

// Extensions - Complex and the most important
const KING_ADDONS_EXT_DYNAMIC_TAGS = true; /** IN PROGRESS OF QA */
const KING_ADDONS_EXT_LOOP_BUILDER = true; /** IN PROGRESS OF QA */
const KING_ADDONS_EXT_THEME_BUILDER = true; /** IN PROGRESS OF QA */
const KING_ADDONS_EXT_WOO_BUILDER = true; /** IN PROGRESS OF QA */

// Extensions - Advanced segment
const KING_ADDONS_EXT_WISHLIST = true; /** IN PROGRESS OF QA */
const KING_ADDONS_EXT_FREE_SHIPPING_BAR = true;
const KING_ADDONS_EXT_STICKY_ADD_TO_CART = true;
const KING_ADDONS_EXT_IMAGE_OPTIMIZER = true; /** DONE */
const KING_ADDONS_EXT_AI_SEO_TOOLS = true;


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
const KING_ADDONS_WGT_STEPS_PROCESS_TIMELINE = true; /** DONE */ // Good
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
const KING_ADDONS_WGT_MAGNETIC_BUTTONS = true; /** DONE */ // Great, needs QA
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
const KING_ADDONS_WGT_WISHLIST_BUTTON = true; /** IN PROGRESS OF QA */
const KING_ADDONS_WGT_WISHLIST_COUNTER = true; /** IN PROGRESS OF QA */
const KING_ADDONS_WGT_WISHLIST_ICON = true; /** IN PROGRESS OF QA */
const KING_ADDONS_WGT_WISHLIST_MINI_LIST = true; /** IN PROGRESS OF QA */
const KING_ADDONS_WGT_WISHLIST_MULTIPLE_LISTS_SWITCHER = true; /** IN PROGRESS OF QA */
const KING_ADDONS_WGT_WISHLIST_PAGE = true; /** IN PROGRESS OF QA */
const KING_ADDONS_WGT_WISHLIST_SHARE_BUTTONS = true; /** IN PROGRESS OF QA */
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
const KING_ADDONS_WGT_TB_404_DESCRIPTION = true;
const KING_ADDONS_WGT_TB_404_SEARCH_FORM = true;
const KING_ADDONS_WGT_TB_SEARCH_RESULTS = true;
const KING_ADDONS_WGT_TB_AUTHOR_INFO = true;
const KING_ADDONS_WGT_TB_404_TITLE = true;
const KING_ADDONS_WGT_TB_ARCHIVE_DESCRIPTION = true;
const KING_ADDONS_WGT_TB_ARCHIVE_PAGINATION = true;
const KING_ADDONS_WGT_TB_ARCHIVE_POSTS = true;
const KING_ADDONS_WGT_TB_ARCHIVE_RESULT_COUNT = true;
const KING_ADDONS_WGT_TB_ARCHIVE_TITLE = true;
const KING_ADDONS_WGT_TB_AUTHOR_BOX = true;
const KING_ADDONS_WGT_TB_BACK_TO_HOME = true;
const KING_ADDONS_WGT_TB_FEATURED_IMAGE = true;
const KING_ADDONS_WGT_TB_POST_COMMENTS = true;
const KING_ADDONS_WGT_TB_POST_CONTENT = true;
const KING_ADDONS_WGT_TB_POST_EXCERPT = true;
const KING_ADDONS_WGT_TB_POST_META = true;
const KING_ADDONS_WGT_TB_POST_NAVIGATION = true;
const KING_ADDONS_WGT_TB_POST_TAXONOMIES = true;
const KING_ADDONS_WGT_TB_POST_TITLE = true;
const KING_ADDONS_WGT_TB_RELATED_POSTS = true;


// Widgets - WooCommerce Builder (NEW) - phased rollout plan (52 widgets)

// WooCommerce Builder Widgets - MVP All Pages + dev order
// Phase 1 (MVP) must cover ALL pages: Single Product, Shop & Category, Cart, Checkout, My Account
// Below is the exact build order (1..N) for Phase 1, then Phase 2..4 in recommended order.

// ================================
// Phase 1 (MVP) - Build Order (ALL pages working)
// ================================

// --- Core infra (required for everything) ---
/* P1-01 */ const KING_ADDONS_WGT_WOO_PRODUCTS_GRID = true;               // Page: Archive | QA passed
/* P1-02 */ const KING_ADDONS_WGT_WOO_PRODUCT_IMAGES_GALLERY = true;      // Page: Single Product | QA passed
/* P1-03 */ const KING_ADDONS_WGT_WOO_PRODUCT_TITLE = true;              // Page: Single Product | QA passed
/* P1-04 */ const KING_ADDONS_WGT_WOO_PRODUCT_PRICE = true;             // Page: Single Product | QA passed
/* P1-05 */ const KING_ADDONS_WGT_WOO_PRODUCT_SHORT_DESCRIPTION = true; // Page: Single Product | QA passed
/* P1-06 */ const KING_ADDONS_WGT_WOO_PRODUCT_VARIATIONS = true;        // Page: Single Product | QA passed
/* P1-07 */ const KING_ADDONS_WGT_WOO_PRODUCT_ADD_TO_CART = true;       // Page: Single Product | QA passed
/* P1-08 */ const KING_ADDONS_WGT_WOO_PRODUCT_STOCK = true;             // Page: Single Product | QA passed
/* P1-09 */ const KING_ADDONS_WGT_WOO_PRODUCT_TABS = true;              // Page: Single Product | QA passed

// --- Shop & Category completion ---
/* P1-10 */ const KING_ADDONS_WGT_WOO_PRODUCTS_SORTING = true;            // Page: Archive | QA passed
/* P1-11 */ const KING_ADDONS_WGT_WOO_PRODUCTS_RESULT_COUNT = true;       // Page: Archive | QA passed
/* P1-12 */ const KING_ADDONS_WGT_WOO_PRODUCTS_PAGINATION = true;         // Page: Archive | QA passed
/* P1-13 */ const KING_ADDONS_WGT_WOO_ARCHIVE_TITLE = true;               // Page: Archive | QA passed

// --- Cart completion ---
/* P1-14 */ const KING_ADDONS_WGT_WOO_CART_TABLE = true;                  // Page: Cart | QA passed
/* P1-15 */ const KING_ADDONS_WGT_WOO_CART_TOTALS = true;                 // Page: Cart | QA passed
/* P1-16 */ const KING_ADDONS_WGT_WOO_CART_EMPTY = true;                  // Page: Cart | QA passed

// --- Checkout completion ---
/* P1-17 */ const KING_ADDONS_WGT_WOO_CHECKOUT_FORM = true;              // Page: Checkout | QA passed
/* P1-18 */ const KING_ADDONS_WGT_WOO_CHECKOUT_ORDER_SUMMARY = true;     // Page: Checkout | QA passed
/* P1-19 */ const KING_ADDONS_WGT_WOO_CHECKOUT_PAYMENT = true;           // Page: Checkout | QA passed
/* P1-20 */ const KING_ADDONS_WGT_WOO_CHECKOUT_PLACE_ORDER = true;       // Page: Checkout | QA passed

// --- My Account completion ---
/* P1-21 */ const KING_ADDONS_WGT_WOO_MY_ACCOUNT_NAVIGATION = true;      // Page: My Account | QA passed
/* P1-22 */ const KING_ADDONS_WGT_WOO_MY_ACCOUNT_CONTENT = true;         // Page: My Account | QA passed
/* P1-23 */ const KING_ADDONS_WGT_WOO_MY_ACCOUNT_DASHBOARD = true;       // Page: My Account | QA passed
/* P1-24 */ const KING_ADDONS_WGT_WOO_MY_ACCOUNT_LOGOUT = true;          // Page: My Account | QA passed

// --- MVP polish (optional inside Phase 1, but recommended if time allows) ---
/* P1-25 */ const KING_ADDONS_WGT_WOO_PRODUCT_RELATED = true;             // Page: Single Product | QA passed


// ================================
// Phase 2 (MVP+) - Reduce friction + match common Woo expectations
// Recommended order inside Phase 2
// ================================

// Cart
/* P2-01 */ const KING_ADDONS_WGT_WOO_CART_COUPON_FORM = true;           // Page: Cart | QA passed
/* P2-02 */ const KING_ADDONS_WGT_WOO_CART_CROSS_SELLS = true;           // Page: Cart | QA passed

// Checkout
/* P2-03 */ const KING_ADDONS_WGT_WOO_CHECKOUT_LOGIN = true;             // Page: Checkout | QA passed
/* P2-04 */ const KING_ADDONS_WGT_WOO_CHECKOUT_COUPON = true;            // Page: Checkout | QA passed

// Shop & Category
/* P2-05 */ const KING_ADDONS_WGT_WOO_ARCHIVE_DESCRIPTION = true;        // Page: Shop & Category | QA passed
/* P2-06 */ const KING_ADDONS_WGT_WOO_ARCHIVE_BANNER = true;             // Page: Shop & Category | QA passed

// Single Product
/* P2-07 */ const KING_ADDONS_WGT_WOO_PRODUCT_BREADCRUMBS = true;         // Page: Single Product | QA passed
/* P2-08 */ const KING_ADDONS_WGT_WOO_PRODUCT_RATING = true;              // Page: Single Product | QA passed
/* P2-09 */ const KING_ADDONS_WGT_WOO_PRODUCT_SKU = true;                 // Page: Single Product | QA passed
/* P2-10 */ const KING_ADDONS_WGT_WOO_PRODUCT_META = true;                // Page: Single Product | QA passed
/* P2-11 */ const KING_ADDONS_WGT_WOO_PRODUCT_FULL_DESCRIPTION = true;    // Page: Single Product | QA passed
/* P2-12 */ const KING_ADDONS_WGT_WOO_PRODUCT_UPSELL = true;              // Page: Single Product | QA passed
/* P2-13 */ const KING_ADDONS_WGT_WOO_PRODUCT_CROSS_SELL = true;          // Page: Single Product | QA passed

// My Account
/* P2-14 */ const KING_ADDONS_WGT_WOO_MY_ACCOUNT_ORDERS = true;      // Page: My Account | QA passed
/* P2-15 */ const KING_ADDONS_WGT_WOO_MY_ACCOUNT_ORDER_DETAILS = true;      // Page: My Account | QA passed
/* P2-16 */ const KING_ADDONS_WGT_WOO_MY_ACCOUNT_ADDRESS = true;      // Page: My Account | QA passed


// ================================
// Phase 3 - Advanced layout blocks (premium-feel UX)
// Recommended order inside Phase 3
// ================================

// Checkout
/* P3-01 */ const KING_ADDONS_WGT_WOO_CHECKOUT_STEPS = true;      // Page: Checkout | QA passed
/* P3-02 */ const KING_ADDONS_WGT_WOO_CHECKOUT_PROGRESS = true;      // Page: Checkout | QA passed
/* P3-03 */ const KING_ADDONS_WGT_WOO_CHECKOUT_STICKY_SIDEBAR = true;      // Page: Checkout | QA passed

// Single Product
/* P3-04 */ const KING_ADDONS_WGT_WOO_PRODUCT_BADGES = true;      // Page: Single Product | QA passed
/* P3-05 */ const KING_ADDONS_WGT_WOO_PRODUCT_COUNTDOWN = true;      // Page: Single Product | QA passed
/* P3-06 */ const KING_ADDONS_WGT_WOO_PRODUCT_CUSTOM_TABS = true;      // Page: Single Product | QA passed

// My Account
/* P3-07 */ const KING_ADDONS_WGT_WOO_MY_ACCOUNT_DETAILS = true;      // Page: My Account | QA passed
/* P3-08 */ const KING_ADDONS_WGT_WOO_MY_ACCOUNT_DOWNLOADS = true;      // Page: My Account | QA passed


// ================================
// Phase 4 - Integrations / power fields (ACF-driven)
// Recommended order inside Phase 4
// ================================

/* P4-01 */ const KING_ADDONS_WGT_WOO_PRODUCT_ACF_FIELD = true;      // Page: see phase notes | QA passed
/* P4-02 */ const KING_ADDONS_WGT_WOO_CHECKOUT_ACF_FIELDS = true;      // Page: see phase notes | QA passed
/* P4-03 */ const KING_ADDONS_WGT_WOO_MY_ACCOUNT_ACF_FIELDS = true;      // Page: see phase notes | QA passed


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

// Loop Builder
const KING_ADDONS_WGT_LOOP_GRID = true;                        // Page: any | Repeats a Loop Builder item template
