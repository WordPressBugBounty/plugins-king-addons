<?php
/**
 * WooCommerce Builder Admin Page
 * Clean, Premium style inspired design
 *
 * @package King_Addons
 */

if (!defined('ABSPATH')) {
    exit;
}

// Include shared dark theme support
include KING_ADDONS_PATH . 'includes/admin/shared/dark-theme.php';

$can_use_pro = king_addons_can_use_pro();

// Get WooCommerce categories and tags for conditions
$product_categories = get_terms([
    'taxonomy' => 'product_cat',
    'hide_empty' => false,
]);
if (is_wp_error($product_categories)) {
    $product_categories = [];
}

$product_tags = get_terms([
    'taxonomy' => 'product_tag',
    'hide_empty' => false,
]);
if (is_wp_error($product_tags)) {
    $product_tags = [];
}

// Get all products for specific product conditions
$products = get_posts([
    'post_type' => 'product',
    'posts_per_page' => 100,
    'post_status' => 'publish',
    'orderby' => 'title',
    'order' => 'ASC',
]);

/**
 * Helper function to deactivate conflicting templates.
 * Only deactivates templates with EXACTLY the same condition.
 *
 * @param int    $current_template_id The template being activated.
 * @param string $template_type       The template type.
 * @param array  $new_conditions      The new conditions being set.
 */
function ka_woo_deactivate_conflicting_templates(int $current_template_id, string $template_type, array $new_conditions): void {
    $new_condition_type = $new_conditions['rules'][0]['type'] ?? 'all';
    $new_condition_values = $new_conditions['rules'][0]['values'] ?? [];
    
    // Get all templates of the same type
    $query = new WP_Query([
        'post_type' => 'elementor_library',
        'posts_per_page' => -1,
        'post_status' => ['publish', 'draft'],
        'post__not_in' => [$current_template_id],
        'meta_query' => [
            [
                'key' => 'ka_woo_template_type',
                'value' => $template_type,
            ],
        ],
    ]);
    
    foreach ($query->posts as $post) {
        $conditions = get_post_meta($post->ID, 'ka_woo_conditions', true);
        if (empty($conditions['enabled'])) {
            continue;
        }
        
        $existing_type = $conditions['rules'][0]['type'] ?? 'all';
        $existing_values = $conditions['rules'][0]['values'] ?? [];
        
        // Check if conditions conflict (same type and overlapping values)
        $should_deactivate = false;
        
        $new_is_global = in_array($new_condition_type, ['all', 'always'], true);
        $existing_is_global = in_array($existing_type, ['all', 'always'], true);

        if ($new_is_global && $existing_is_global) {
            // Both are "all" - conflict
            $should_deactivate = true;
        } elseif ($new_condition_type === $existing_type && !empty($new_condition_values) && !empty($existing_values)) {
            // Same type with specific values - check for overlap
            $overlap = array_intersect($new_condition_values, $existing_values);
            if (!empty($overlap)) {
                $should_deactivate = true;
            }
        }
        
        if ($should_deactivate) {
            $conditions['enabled'] = false;
            update_post_meta($post->ID, 'ka_woo_conditions', $conditions);
        }
    }
}

// Handle create template action - create post and redirect to Elementor editor
if (!empty($_GET['ka_woo_create']) && !empty($_GET['ka_woo_template_type'])) {
    $template_type = sanitize_key($_GET['ka_woo_template_type']);
    $valid_types = ['single_product', 'product_archive', 'cart', 'checkout', 'my_account'];
    
    if (in_array($template_type, $valid_types, true) && current_user_can('manage_options') && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'] ?? '')), 'ka_woo_create_' . $template_type)) {
        
        // Check Pro restriction
        if (!$can_use_pro && in_array($template_type, ['cart', 'checkout', 'my_account'], true)) {
            wp_safe_redirect(remove_query_arg(['ka_woo_create', 'ka_woo_template_type', '_wpnonce']));
            exit;
        }
        
        // Get type label for title
        $type_labels = [
            'single_product' => __('Single Product', 'king-addons'),
            'product_archive' => __('Shop & Category', 'king-addons'),
            'cart' => __('Cart', 'king-addons'),
            'checkout' => __('Checkout', 'king-addons'),
            'my_account' => __('My Account', 'king-addons'),
        ];
        
        // Create new post
        $post_id = wp_insert_post([
            'post_title' => $type_labels[$template_type] ?? __('WooCommerce Template', 'king-addons'),
            'post_type' => 'elementor_library',
            'post_status' => 'draft',
        ]);
        
        if ($post_id && !is_wp_error($post_id)) {
            // Set template type meta
            update_post_meta($post_id, 'ka_woo_template_type', $template_type);
            
            // Use 'page' as Elementor template type - this ensures Elementor editor works
            // Our custom document type may not be properly supported
            update_post_meta($post_id, '_elementor_template_type', 'page');
            
            // Set default conditions (enabled by default)
            $default_rule_type = in_array($template_type, ['cart', 'checkout', 'my_account'], true) ? 'always' : 'all';
            $default_conditions = [
                'enabled' => true,
                'priority' => 10,
                'rules' => [
                    [
                        'type' => $default_rule_type,
                        'values' => [],
                    ],
                ],
            ];
            update_post_meta($post_id, 'ka_woo_conditions', $default_conditions);
            
            // Set _elementor_edit_mode so Elementor knows to use builder
            update_post_meta($post_id, '_elementor_edit_mode', 'builder');
            
            // Initialize empty Elementor data
            update_post_meta($post_id, '_elementor_data', '[]');
            
            // Redirect to Elementor editor
            $elementor_url = add_query_arg([
                'post' => $post_id,
                'action' => 'elementor',
            ], admin_url('post.php'));
            
            wp_safe_redirect($elementor_url);
            exit;
        }
    }
    wp_safe_redirect(remove_query_arg(['ka_woo_create', 'ka_woo_template_type', '_wpnonce']));
    exit;
}

// Handle toggle action
if (!empty($_GET['ka_woo_toggle']) && !empty($_GET['template_id'])) {
    $template_id = (int) $_GET['template_id'];
    if (current_user_can('manage_options') && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'] ?? '')), 'ka_woo_toggle_' . $template_id)) {
        $conditions = get_post_meta($template_id, 'ka_woo_conditions', true);
        if (!is_array($conditions)) {
            $conditions = ['enabled' => false, 'rules' => [['type' => 'all', 'values' => []]]];
        }
        
        $will_enable = empty($conditions['enabled']);
        $conditions['enabled'] = $will_enable;
        update_post_meta($template_id, 'ka_woo_conditions', $conditions);
        
        // If enabling, deactivate conflicting templates
        if ($will_enable) {
            $template_type = get_post_meta($template_id, 'ka_woo_template_type', true);
            ka_woo_deactivate_conflicting_templates($template_id, $template_type, $conditions);
        }
    }
    wp_safe_redirect(remove_query_arg(['ka_woo_toggle', 'template_id', '_wpnonce']));
    exit;
}

// Handle conditions update action (Pro only)
if (!empty($_POST['ka_woo_update_conditions']) && !empty($_POST['template_id'])) {
    $template_id = (int) $_POST['template_id'];
    if (current_user_can('manage_options') && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'] ?? '')), 'ka_woo_conditions')) {
        
        // Only allow in Pro
        if ($can_use_pro) {
            $condition_type = sanitize_key($_POST['condition_type'] ?? 'all');
            $condition_values = [];
            
            if (!empty($_POST['condition_values']) && is_array($_POST['condition_values'])) {
                $condition_values = array_map('intval', $_POST['condition_values']);
            }

            $template_type = (string) get_post_meta($template_id, 'ka_woo_template_type', true);
            $allowed_types = [];
            if ('single_product' === $template_type) {
                $allowed_types = ['all', 'product_cat', 'product_tag', 'specific_product'];
            } elseif ('product_archive' === $template_type) {
                $allowed_types = ['all', 'shop', 'product_cat', 'product_tag'];
            } elseif (in_array($template_type, ['cart', 'checkout', 'my_account'], true)) {
                $allowed_types = ['always'];
            }

            if (!in_array($condition_type, $allowed_types, true)) {
                $condition_type = $allowed_types[0] ?? 'all';
            }

            // If a "specific" selector is chosen but nothing selected, treat it as global.
            if (in_array($condition_type, ['product_cat', 'product_tag', 'specific_product'], true) && empty($condition_values)) {
                $condition_type = 'all';
            }
            
            $conditions = get_post_meta($template_id, 'ka_woo_conditions', true);
            if (!is_array($conditions)) {
                $conditions = ['enabled' => false];
            }
            
            $conditions['rules'] = [
                [
                    'type' => $condition_type,
                    'values' => $condition_values,
                ],
            ];
            
            update_post_meta($template_id, 'ka_woo_conditions', $conditions);
            
            // If enabled, deactivate conflicting templates
            if (!empty($conditions['enabled'])) {
                $template_type = get_post_meta($template_id, 'ka_woo_template_type', true);
                ka_woo_deactivate_conflicting_templates($template_id, $template_type, $conditions);
            }
        }
    }
    wp_safe_redirect(add_query_arg('page', 'king-addons-woo-builder', admin_url('admin.php')));
    exit;
}

// Handle duplicate action
if (!empty($_GET['ka_woo_duplicate']) && !empty($_GET['template_id'])) {
    $template_id = (int) $_GET['template_id'];
    if (current_user_can('manage_options') && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'] ?? '')), 'ka_woo_duplicate_' . $template_id)) {
        $original_post = get_post($template_id);
        if ($original_post && 'elementor_library' === $original_post->post_type) {
            // Create duplicate
            $new_post_id = wp_insert_post([
                'post_title' => $original_post->post_title . ' ' . __('(Copy)', 'king-addons'),
                'post_type' => 'elementor_library',
                'post_status' => 'draft',
                'post_content' => $original_post->post_content,
            ]);
            
            if ($new_post_id && !is_wp_error($new_post_id)) {
                // Copy all meta
                $meta = get_post_meta($template_id);
                foreach ($meta as $key => $values) {
                    foreach ($values as $value) {
                        add_post_meta($new_post_id, $key, maybe_unserialize($value));
                    }
                }
                // Set as inactive by default
                $conditions = get_post_meta($new_post_id, 'ka_woo_conditions', true);
                if (!is_array($conditions)) {
                    $conditions = [];
                }
                $conditions['enabled'] = false;
                update_post_meta($new_post_id, 'ka_woo_conditions', $conditions);
            }
        }
    }
    wp_safe_redirect(remove_query_arg(['ka_woo_duplicate', 'template_id', '_wpnonce']));
    exit;
}

// Handle rename action (AJAX)
if (!empty($_POST['ka_woo_rename']) && !empty($_POST['template_id']) && !empty($_POST['new_title'])) {
    if (current_user_can('manage_options') && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'] ?? '')), 'ka_woo_rename')) {
        $template_id = (int) $_POST['template_id'];
        $new_title = sanitize_text_field(wp_unslash($_POST['new_title']));
        wp_update_post([
            'ID' => $template_id,
            'post_title' => $new_title,
        ]);
    }
    wp_safe_redirect(remove_query_arg(['ka_woo_rename', 'template_id', 'new_title', '_wpnonce']));
    exit;
}

// Handle change type action
if (!empty($_GET['ka_woo_change_type']) && !empty($_GET['template_id']) && !empty($_GET['new_type'])) {
    $template_id = (int) $_GET['template_id'];
    $new_type = sanitize_key($_GET['new_type']);
    $valid_types = ['single_product', 'product_archive', 'cart', 'checkout', 'my_account'];
    
    if (current_user_can('manage_options') && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'] ?? '')), 'ka_woo_change_type_' . $template_id)) {
        if (in_array($new_type, $valid_types, true)) {
            // Check Pro restriction for target type
            if ($can_use_pro || !in_array($new_type, ['cart', 'checkout', 'my_account'], true)) {
                update_post_meta($template_id, 'ka_woo_template_type', $new_type);
            }
        }
    }
    wp_safe_redirect(remove_query_arg(['ka_woo_change_type', 'template_id', 'new_type', '_wpnonce']));
    exit;
}

// Handle delete action
if (!empty($_GET['ka_woo_delete']) && !empty($_GET['template_id'])) {
    $template_id = (int) $_GET['template_id'];
    if (current_user_can('manage_options') && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'] ?? '')), 'ka_woo_delete_' . $template_id)) {
        wp_trash_post($template_id);
    }
    wp_safe_redirect(remove_query_arg(['ka_woo_delete', 'template_id', '_wpnonce']));
    exit;
}

// Template types
$types = [
    'single_product' => [
        'label' => esc_html__('Single Product', 'king-addons'),
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>',
        'desc' => esc_html__('Customize product pages', 'king-addons'),
    ],
    'product_archive' => [
        'label' => esc_html__('Shop & Category', 'king-addons'),
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>',
        'desc' => esc_html__('Design shop & category pages', 'king-addons'),
    ],
    'cart' => [
        'label' => esc_html__('Cart', 'king-addons'),
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>',
        'desc' => esc_html__('Custom cart layout', 'king-addons'),
        'pro' => true,
    ],
    'checkout' => [
        'label' => esc_html__('Checkout', 'king-addons'),
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>',
        'desc' => esc_html__('Streamlined checkout', 'king-addons'),
        'pro' => true,
    ],
    'my_account' => [
        'label' => esc_html__('My Account', 'king-addons'),
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
        'desc' => esc_html__('Account dashboard', 'king-addons'),
        'pro' => true,
    ],
];

// Create links for each type - now uses our custom handler that creates the post and redirects to Elementor
$create_links = [];
foreach ($types as $slug => $data) {
    $create_links[$slug] = wp_nonce_url(
        add_query_arg(
            [
                'page' => 'king-addons-woo-builder',
                'ka_woo_create' => '1',
                'ka_woo_template_type' => $slug,
            ],
            admin_url('admin.php')
        ),
        'ka_woo_create_' . $slug
    );
}

// Get existing templates
$query = new WP_Query([
    'post_type' => 'elementor_library',
    'posts_per_page' => -1,
    'post_status' => ['publish', 'draft'],
    'meta_query' => [
        [
            'key' => 'ka_woo_template_type',
            'compare' => 'EXISTS',
        ],
    ],
]);

$templates = [];
foreach ($query->posts as $post) {
    $type = get_post_meta($post->ID, 'ka_woo_template_type', true);
    if (empty($type)) continue;

    $conditions = get_post_meta($post->ID, 'ka_woo_conditions', true);
    if (!is_array($conditions)) {
        $conditions = ['enabled' => false, 'rules' => [['type' => 'all', 'values' => []]]];
    }
    $enabled = !empty($conditions['enabled']);
    $pro_locked = (!$can_use_pro && in_array($type, ['cart', 'checkout', 'my_account'], true));
    
    // Parse condition for display
    $condition_type = $conditions['rules'][0]['type'] ?? 'all';
    $condition_values = $conditions['rules'][0]['values'] ?? [];
    $condition_label = __('All', 'king-addons');

    if (in_array($condition_type, ['always'], true)) {
        $condition_label = __('Always', 'king-addons');
    } elseif (in_array($condition_type, ['shop', 'is_shop'], true)) {
        $condition_label = __('Shop Page Only', 'king-addons');
    } elseif ('all' === $condition_type) {
        if ('single_product' === $type) {
            $condition_label = __('All Products', 'king-addons');
        } elseif ('product_archive' === $type) {
            $condition_label = __('All Categories', 'king-addons');
        } else {
            $condition_label = __('Always', 'king-addons');
        }
    } elseif (!empty($condition_values)) {
        switch ($condition_type) {
            case 'product_cat':
                $cat_names = [];
                foreach ($condition_values as $cat_id) {
                    $term = get_term($cat_id, 'product_cat');
                    if ($term && !is_wp_error($term)) {
                        $cat_names[] = $term->name;
                    }
                }
                $condition_label = !empty($cat_names) ? implode(', ', $cat_names) : __('Categories', 'king-addons');
                break;
            case 'product_tag':
                $tag_names = [];
                foreach ($condition_values as $tag_id) {
                    $term = get_term($tag_id, 'product_tag');
                    if ($term && !is_wp_error($term)) {
                        $tag_names[] = $term->name;
                    }
                }
                $condition_label = !empty($tag_names) ? implode(', ', $tag_names) : __('Tags', 'king-addons');
                break;
            case 'specific_product':
                $product_titles = [];
                foreach ($condition_values as $product_id) {
                    $product_titles[] = get_the_title($product_id);
                }
                $condition_label = !empty($product_titles) ? implode(', ', $product_titles) : __('Specific Products', 'king-addons');
                break;
        }
    }

    $templates[] = [
        'id' => $post->ID,
        'title' => get_the_title($post),
        'type' => $type,
        'enabled' => $enabled,
        'status' => $post->post_status,
        'date' => get_the_modified_date('M j, Y', $post),
        'pro_locked' => $pro_locked,
        'condition_type' => $condition_type,
        'condition_values' => $condition_values,
        'condition_label' => $condition_label,
    ];
}

// Condition options based on template type
$condition_options = [
    'single_product' => [
        'all' => __('All Products', 'king-addons'),
        'product_cat' => __('Specific Categories', 'king-addons'),
        'product_tag' => __('Specific Tags', 'king-addons'),
        'specific_product' => __('Specific Products', 'king-addons'),
    ],
    'product_archive' => [
        'all' => __('All Categories', 'king-addons'),
        'shop' => __('Shop Page Only', 'king-addons'),
        'product_cat' => __('Specific Categories (or Archives)', 'king-addons'),
        'product_tag' => __('Specific Tags', 'king-addons'),
    ],
    'cart' => [
        'always' => __('Always', 'king-addons'),
    ],
    'checkout' => [
        'always' => __('Always', 'king-addons'),
    ],
    'my_account' => [
        'always' => __('Always', 'king-addons'),
    ],
];

// Get Elementor edit URL
if (!function_exists('ka_woo_get_elementor_edit_url')) {
    function ka_woo_get_elementor_edit_url(int $post_id): string {
        return add_query_arg(['post' => $post_id, 'action' => 'elementor'], admin_url('post.php'));
    }
}
?>
<style>
/* ================================================
   WooCommerce Builder - Pro Design
   ================================================ */

:root {
    --ka-wb-font: -apple-system, BlinkMacSystemFont, "SF Pro Display", "SF Pro Text", system-ui, sans-serif;
    --ka-wb-bg: #f5f5f7;
    --ka-wb-surface: #ffffff;
    --ka-wb-text: #1d1d1f;
    --ka-wb-text-secondary: #86868b;
    --ka-wb-border: rgba(0, 0, 0, 0.06);
    --ka-wb-accent: #0071e3;
    --ka-wb-accent-hover: #0077ed;
    --ka-wb-success: #34c759;
    --ka-wb-warning: #ff9500;
    --ka-wb-radius: 20px;
    --ka-wb-radius-sm: 12px;
    --ka-wb-shadow: 0 4px 24px rgba(0, 0, 0, 0.06);
    --ka-wb-shadow-hover: 0 8px 32px rgba(0, 0, 0, 0.1);
    --ka-wb-transition: all 0.3s cubic-bezier(0.25, 1, 0.5, 1);
}

body.ka-v3-dark {
    --ka-wb-bg: #000000;
    --ka-wb-surface: #1c1c1e;
    --ka-wb-text: #f5f5f7;
    --ka-wb-text-secondary: #98989d;
    --ka-wb-border: rgba(255, 255, 255, 0.1);
    --ka-wb-shadow: 0 4px 24px rgba(0, 0, 0, 0.3);
    --ka-wb-shadow-hover: 0 12px 40px rgba(0, 0, 0, 0.4);
}

body.king-addons_page_king-addons-woo-builder #wpcontent,
body.king-addons_page_king-addons-woo-builder #wpbody,
body.king-addons_page_king-addons-woo-builder #wpbody-content {
    background: var(--ka-wb-bg) !important;
    padding: 0 !important;
}

.ka-wb {
    font-family: var(--ka-wb-font);
    max-width: 1100px;
    margin: 0 auto;
    padding: 48px 40px 80px;
    color: var(--ka-wb-text);
    -webkit-font-smoothing: antialiased;
}

.ka-wb * { box-sizing: border-box; }

/* Header */
.ka-wb-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 56px;
}

.ka-wb-header-content {
    display: flex;
    align-items: flex-start;
    gap: 16px;
}

.ka-wb-header-titles {
    display: flex;
    flex-direction: column;
    gap: 8px;
    min-width: 0;
}

.ka-wb-header-titles h1 {
    font-size: 56px;
    font-weight: 700;
    letter-spacing: -0.025em;
    margin: 0;
    line-height: 1;
}

.ka-wb-header-titles p {
    font-size: 21px;
    color: var(--ka-wb-text-secondary);
    margin: 0;
    font-weight: 400;
}

.ka-wb-title-icon {
    width: 76px;
    height: 76px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 22px;
    background: rgba(0, 113, 227, 0.12);
    color: var(--ka-wb-accent);
    flex: 0 0 auto;
}
body.ka-v3-dark .ka-wb-title-icon {
    background: rgba(10, 132, 255, 0.18);
    color: #0a84ff;
}
.ka-wb-title-icon svg { width: 36px; height: 36px; }

.ka-wb-title-text {
    background: linear-gradient(135deg, var(--ka-wb-text) 0%, var(--ka-wb-text-secondary) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.ka-wb-header-content p {
    font-size: 21px;
    color: var(--ka-wb-text-secondary);
    margin: 0;
    font-weight: 400;
}

.ka-wb-header-actions {
    display: flex;
    align-items: center;
    gap: 12px;
}

/* Template Types Grid */
.ka-wb-types {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 64px;
}

.ka-wb-type {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 32px 20px;
    background: var(--ka-wb-surface);
    border: 1px solid var(--ka-wb-border);
    border-radius: var(--ka-wb-radius);
    text-decoration: none;
    color: var(--ka-wb-text);
    transition: var(--ka-wb-transition);
    cursor: pointer;
    overflow: hidden;
}

.ka-wb-type:hover {
    transform: translateY(-4px);
    box-shadow: var(--ka-wb-shadow-hover);
    border-color: var(--ka-wb-accent);
}

.ka-wb-type:hover .ka-wb-type-icon {
    transform: scale(1.1);
    color: var(--ka-wb-accent);
}

.ka-wb-type.is-locked {
    opacity: 0.6;
    cursor: not-allowed;
}

.ka-wb-type.is-locked:hover {
    transform: none;
    box-shadow: none;
    border-color: var(--ka-wb-border);
}

.ka-wb-type.is-locked .ka-wb-type-icon {
    transform: none;
    color: var(--ka-wb-text-secondary);
}

.ka-wb-type-icon {
    width: 48px;
    height: 48px;
    margin-bottom: 16px;
    color: var(--ka-wb-text-secondary);
    transition: var(--ka-wb-transition);
}

.ka-wb-type-icon svg {
    width: 100%;
    height: 100%;
}

.ka-wb-type-label {
    font-size: 17px;
    font-weight: 600;
    margin-bottom: 4px;
    text-align: center;
}

.ka-wb-type-desc {
    font-size: 13px;
    color: var(--ka-wb-text-secondary);
    text-align: center;
}

.ka-wb-type-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    padding: 4px 10px;
    font-size: 11px;
    font-weight: 600;
    background: linear-gradient(135deg, #0071e3, #5856d6);
    color: #fff;
    border-radius: 20px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Section */
.ka-wb-section {
    margin-bottom: 48px;
}

.ka-wb-section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.ka-wb-section-title {
    font-size: 28px;
    font-weight: 600;
    letter-spacing: -0.01em;
    margin: 0;
}

.ka-wb-section-count {
    font-size: 15px;
    color: var(--ka-wb-text-secondary);
    background: var(--ka-wb-surface);
    padding: 6px 14px;
    border-radius: 20px;
    border: 1px solid var(--ka-wb-border);
}

/* Templates List */
.ka-wb-templates {
    background: var(--ka-wb-surface);
    border-radius: var(--ka-wb-radius);
    border: 1px solid var(--ka-wb-border);
}

.ka-wb-template {
    display: grid;
    grid-template-columns: auto 1fr auto auto;
    align-items: center;
    gap: 20px;
    padding: 20px 24px;
    border-bottom: 1px solid var(--ka-wb-border);
    transition: var(--ka-wb-transition);
}

.ka-wb-template:last-child {
    border-bottom: none;
}

.ka-wb-template:hover {
    background: rgba(0, 113, 227, 0.03);
}

body.ka-v3-dark .ka-wb-template:hover {
    background: rgba(255, 255, 255, 0.03);
}

.ka-wb-template-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 0;
}

.ka-wb-template-title {
    font-size: 16px;
    font-weight: 500;
    color: var(--ka-wb-text);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-width: 0;
    transition: var(--ka-wb-transition);
}

.ka-wb-template-title-icon {
    width: 18px;
    height: 18px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: var(--ka-wb-text-secondary);
    flex: 0 0 auto;
}
.ka-wb-template-title-icon svg { width: 18px; height: 18px; }
.ka-wb-template-title-text {
    min-width: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.ka-wb-template-title:hover {
    color: var(--ka-wb-accent);
}

.ka-wb-template-meta {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 13px;
    color: var(--ka-wb-text-secondary);
}

.ka-wb-template-type {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.ka-wb-template-type::before {
    content: '';
    width: 6px;
    height: 6px;
    background: var(--ka-wb-accent);
    border-radius: 50%;
}

.ka-wb-template-status {
    display: inline-flex;
    align-items: center;
    padding: 6px 14px;
    font-size: 13px;
    font-weight: 600;
    border-radius: 8px;
    white-space: nowrap;
    min-width: 80px;
    justify-content: center;
    justify-self: start;
}

.ka-wb-template-status.is-enabled {
    background: rgba(52, 199, 89, 0.15);
    color: #30d158;
}

.ka-wb-template-status.is-disabled {
    background: rgba(255, 149, 0, 0.15);
    color: #ff9f0a;
}

.ka-wb-template-status.is-draft {
    background: var(--ka-wb-border);
    color: var(--ka-wb-text-secondary);
}

/* Condition Badge */
.ka-wb-template-condition {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    min-height: 40px;
    font-size: 14px;
    font-weight: 500;
    border-radius: 980px;
    background: rgba(0, 113, 227, 0.1);
    color: var(--ka-wb-accent);
    max-width: 240px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    border: none;
    cursor: pointer;
    transition: var(--ka-wb-transition);
}

.ka-wb-template-condition:hover {
    background: rgba(0, 113, 227, 0.18);
}

.ka-wb-template-condition svg {
    width: 18px;
    height: 18px;
    flex-shrink: 0;
    opacity: 0.7;
}

.ka-wb-template-condition.is-pro-locked {
    opacity: 0.5;
    cursor: not-allowed;
}

.ka-wb-template-condition-btn {
    background: rgba(0, 113, 227, 0.06);
    border: 1px solid rgba(0, 113, 227, 0.18);
    color: var(--ka-wb-accent);
}

.ka-wb-template-condition-btn:hover {
    border-color: rgba(0, 113, 227, 0.32);
    color: var(--ka-wb-accent);
    background: rgba(0, 113, 227, 0.10);
}

.ka-wb-template-actions {
    display: flex;
    align-items: center;
    gap: 12px;
}

.ka-wb-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 10px 18px;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    border-radius: 980px;
    border: none;
    cursor: pointer;
    transition: var(--ka-wb-transition);
    white-space: nowrap;
    font-family: inherit;
}

.ka-wb-btn-primary {
    background: var(--ka-wb-accent);
    color: #fff;
}

.ka-wb-btn-primary:visited {
    color: #fff;
}

.ka-wb-btn-primary:hover {
    background: var(--ka-wb-accent-hover);
    color: #fff;
    transform: scale(1.02);
}

.ka-wb-btn-secondary {
    background: rgba(0, 0, 0, 0.04);
    color: var(--ka-wb-text);
}

body.ka-v3-dark .ka-wb-btn-secondary {
    background: rgba(255, 255, 255, 0.08);
}

.ka-wb-btn-secondary:hover {
    background: rgba(0, 0, 0, 0.08);
    color: var(--ka-wb-text);
}

body.ka-v3-dark .ka-wb-btn-secondary:hover {
    background: rgba(255, 255, 255, 0.12);
}

.ka-wb-btn-ghost {
    background: transparent;
    color: var(--ka-wb-accent);
    padding: 10px 12px;
}

.ka-wb-btn-ghost:hover {
    background: rgba(0, 113, 227, 0.08);
}

.ka-wb-btn-danger {
    color: #ff3b30;
}

.ka-wb-btn-danger:hover {
    background: rgba(255, 59, 48, 0.08);
}

.ka-wb-btn svg {
    width: 16px;
    height: 16px;
}

/* Dropdown Menu */
.ka-wb-dropdown {
    position: relative;
}

.ka-wb-dropdown-trigger {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: transparent;
    border: 1px solid transparent;
    cursor: pointer;
    transition: var(--ka-wb-transition);
    color: var(--ka-wb-text-secondary);
}

.ka-wb-dropdown-trigger:hover {
    background: var(--ka-wb-surface);
    border-color: var(--ka-wb-border);
    color: var(--ka-wb-text);
}

.ka-wb-dropdown-trigger svg {
    width: 20px;
    height: 20px;
}

.ka-wb-dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    z-index: 100;
    min-width: 200px;
    padding: 8px 0;
    background: var(--ka-wb-surface);
    border: 1px solid var(--ka-wb-border);
    border-radius: var(--ka-wb-radius-sm);
    box-shadow: var(--ka-wb-shadow-hover);
    opacity: 0;
    visibility: hidden;
    transform: translateY(-8px);
    transition: var(--ka-wb-transition);
}

.ka-wb-dropdown.is-open .ka-wb-dropdown-menu {
    opacity: 1;
    visibility: visible;
    transform: translateY(4px);
}

.ka-wb-dropdown-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 16px;
    font-size: 14px;
    color: var(--ka-wb-text);
    text-decoration: none;
    cursor: pointer;
    transition: var(--ka-wb-transition);
    border: none;
    background: none;
    width: 100%;
    text-align: left;
    font-family: inherit;
}

.ka-wb-dropdown-item:hover {
    background: var(--ka-wb-border);
}

.ka-wb-dropdown-item svg {
    width: 16px;
    height: 16px;
    color: var(--ka-wb-text-secondary);
    flex-shrink: 0;
}

.ka-wb-dropdown-item.is-danger {
    color: #ff3b30;
}

.ka-wb-dropdown-item.is-danger svg {
    color: #ff3b30;
}

.ka-wb-dropdown-divider {
    height: 1px;
    margin: 8px 0;
    background: var(--ka-wb-border);
}

.ka-wb-dropdown-label {
    padding: 8px 16px 4px;
    font-size: 11px;
    font-weight: 600;
    color: var(--ka-wb-text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Modal */
.ka-wb-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 100000;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    visibility: hidden;
    transition: var(--ka-wb-transition);
    padding: 20px;
}

.ka-wb-modal-overlay.is-open {
    opacity: 1;
    visibility: visible;
}

.ka-wb-modal {
    width: 100%;
    max-width: 420px;
    padding: 36px;
    background: var(--ka-wb-surface);
    border-radius: var(--ka-wb-radius);
    box-shadow: 0 24px 80px rgba(0, 0, 0, 0.35);
    transform: scale(0.95) translateY(10px);
    transition: var(--ka-wb-transition);
}

body.ka-v3-dark .ka-wb-modal {
    border: 1px solid var(--ka-wb-border);
}

.ka-wb-modal-overlay.is-open .ka-wb-modal {
    transform: scale(1) translateY(0);
}

.ka-wb-modal h3 {
    font-size: 24px;
    font-weight: 600;
    margin: 0 0 8px;
    color: var(--ka-wb-text);
    letter-spacing: -0.02em;
}

.ka-wb-modal-input {
    width: 100%;
    padding: 14px 18px;
    font-size: 16px;
    font-family: inherit;
    border: 1px solid var(--ka-wb-border);
    border-radius: var(--ka-wb-radius-sm);
    background: var(--ka-wb-surface);
    color: var(--ka-wb-text);
    transition: var(--ka-wb-transition);
}

body.ka-v3-dark .ka-wb-modal-input {
    background: rgba(255, 255, 255, 0.06);
}

.ka-wb-modal-input:focus {
    outline: none;
    border-color: var(--ka-wb-accent);
    box-shadow: 0 0 0 3px rgba(0, 113, 227, 0.15);
}
.ka-wb-modal-actions {
    display: flex;
    gap: 12px;
    margin-top: 28px;
    justify-content: flex-end;
}

/* Conditions Modal */
.ka-wb-modal.ka-wb-modal-conditions {
    max-width: 480px;
}

.ka-wb-modal-desc {
    font-size: 15px;
    color: var(--ka-wb-text-secondary);
    margin: -8px 0 28px;
    line-height: 1.5;
}

.ka-wb-form-group {
    margin-bottom: 24px;
}

.ka-wb-form-group:last-of-type {
    margin-bottom: 0;
}

.ka-wb-form-label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: var(--ka-wb-text-secondary);
    margin-bottom: 10px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.ka-wb-form-select {
    width: 100%;
    padding: 14px 18px;
    font-size: 16px;
    font-family: inherit;
    border: 1px solid var(--ka-wb-border);
    border-radius: var(--ka-wb-radius-sm);
    background: var(--ka-wb-surface);
    color: var(--ka-wb-text);
    transition: var(--ka-wb-transition);
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2386868b' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 16px center;
}

body.ka-v3-dark .ka-wb-form-select {
    background-color: rgba(255, 255, 255, 0.06);
}

.ka-wb-form-select:focus {
    outline: none;
    border-color: var(--ka-wb-accent);
    box-shadow: 0 0 0 3px rgba(0, 113, 227, 0.12);
}

.ka-wb-checkbox-list {
    max-height: 220px;
    overflow-y: auto;
    border: 1px solid var(--ka-wb-border);
    border-radius: var(--ka-wb-radius-sm);
    padding: 8px;
    background: var(--ka-wb-surface);
}

body.ka-v3-dark .ka-wb-checkbox-list {
    background: rgba(255, 255, 255, 0.04);
}

.ka-wb-checkbox-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    border-radius: 8px;
    cursor: pointer;
    transition: var(--ka-wb-transition);
}

.ka-wb-checkbox-item:hover {
    background: rgba(0, 113, 227, 0.06);
}

.ka-wb-checkbox-item input[type="checkbox"] {
    width: 20px;
    height: 20px;
    accent-color: var(--ka-wb-accent);
    cursor: pointer;
    border-radius: 4px;
}

.ka-wb-checkbox-item span {
    font-size: 15px;
    color: var(--ka-wb-text);
}

.ka-wb-pro-badge-inline {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    font-size: 10px;
    font-weight: 600;
    background: linear-gradient(135deg, #0071e3, #5856d6);
    color: #fff;
    border-radius: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-left: 8px;
}

.ka-wb-modal-locked {
    position: static;
}

.ka-wb-modal-locked::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(var(--ka-wb-surface), 0.8);
    backdrop-filter: blur(3px);
    z-index: 1;
}

.ka-wb-modal-locked-overlay {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 2;
    text-align: center;
    padding: 24px;
}

.ka-wb-modal-locked-overlay svg {
    width: 48px;
    height: 48px;
    color: var(--ka-wb-accent);
    margin-bottom: 12px;
}

.ka-wb-modal-locked-overlay h4 {
    font-size: 18px;
    font-weight: 600;
    margin: 0 0 8px;
    color: var(--ka-wb-text);
}

.ka-wb-modal-locked-overlay p {
    font-size: 14px;
    color: var(--ka-wb-text-secondary);
    margin: 0 0 16px;
}

/* Empty State */
.ka-wb-empty {
    text-align: center;
    padding: 64px 40px;
}

.ka-wb-empty-icon {
    width: 64px;
    height: 64px;
    margin: 0 auto 20px;
    color: var(--ka-wb-text-secondary);
    opacity: 0.4;
}

.ka-wb-empty-icon svg {
    width: 100%;
    height: 100%;
}

.ka-wb-empty h3 {
    font-size: 20px;
    font-weight: 600;
    margin: 0 0 8px;
    color: var(--ka-wb-text);
}

.ka-wb-empty p {
    font-size: 15px;
    color: var(--ka-wb-text-secondary);
    margin: 0;
}

/* Quick Start */
.ka-wb-quickstart {
    padding: 24px 28px;
    background: var(--ka-wb-surface);
    border: 1px solid var(--ka-wb-border);
    border-radius: var(--ka-wb-radius);
    box-shadow: var(--ka-wb-shadow);
    margin-bottom: 32px;
}

.ka-wb-quickstart-head {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 18px;
}

.ka-wb-quickstart-head h3 {
    font-size: 16px;
    font-weight: 600;
    margin: 0;
    color: var(--ka-wb-text);
}

.ka-wb-quickstart-head p {
    font-size: 13px;
    margin: 0;
    color: var(--ka-wb-text-secondary);
    white-space: nowrap;
}

.ka-wb-quickstart-steps {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
}

.ka-wb-quickstart-step {
    display: flex;
    gap: 12px;
    padding: 14px 16px;
    border-radius: var(--ka-wb-radius-sm);
    background: rgba(0, 0, 0, 0.02);
    border: 1px solid var(--ka-wb-border);
}

body.ka-v3-dark .ka-wb-quickstart-step {
    background: rgba(255, 255, 255, 0.04);
}

.ka-wb-quickstart-step-number {
    width: 28px;
    height: 28px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: 700;
    color: #fff;
    background: linear-gradient(135deg, var(--ka-wb-accent), var(--ka-wb-accent-hover));
    flex-shrink: 0;
}

.ka-wb-quickstart-step-title {
    font-size: 14px;
    font-weight: 600;
    margin: 0 0 3px;
    color: var(--ka-wb-text);
}

.ka-wb-quickstart-step-desc {
    font-size: 13px;
    margin: 0;
    color: var(--ka-wb-text-secondary);
    line-height: 1.35;
}

/* Pro Notice */
.ka-wb-pro-notice {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
    padding: 24px 28px;
    background: linear-gradient(135deg, rgba(0, 113, 227, 0.08), rgba(88, 86, 214, 0.08));
    border: 1px solid rgba(0, 113, 227, 0.15);
    border-radius: var(--ka-wb-radius);
    margin-bottom: 32px;
}

body.ka-v3-dark .ka-wb-pro-notice {
    background: linear-gradient(135deg, rgba(0, 113, 227, 0.12), rgba(88, 86, 214, 0.12));
    border-color: rgba(0, 113, 227, 0.2);
}

.ka-wb-pro-notice-content {
    display: flex;
    align-items: center;
    gap: 16px;
}

.ka-wb-pro-notice-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #0071e3, #5856d6);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    flex-shrink: 0;
}

.ka-wb-pro-notice-icon svg {
    width: 22px;
    height: 22px;
}

.ka-wb-pro-notice-text h4 {
    font-size: 15px;
    font-weight: 600;
    margin: 0 0 2px;
    color: var(--ka-wb-text);
}

.ka-wb-pro-notice-text p {
    font-size: 14px;
    color: var(--ka-wb-text-secondary);
    margin: 0;
}

/* Responsive */
@media (max-width: 768px) {
    .ka-wb {
        padding: 32px 20px 60px;
    }
    
    .ka-wb-header {
        flex-direction: column;
        gap: 24px;
    }
    
    .ka-wb-header-titles h1 {
        font-size: 36px;
    }

    .ka-wb-title-icon {
        width: 64px;
        height: 64px;
        border-radius: 18px;
    }

    .ka-wb-title-icon svg {
        width: 30px;
        height: 30px;
    }
    
    .ka-wb-types {
        grid-template-columns: 1fr 1fr;
    }
    
    .ka-wb-template {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    
    .ka-wb-template-actions {
        justify-content: flex-start;
    }
    
    .ka-wb-pro-notice {
        flex-direction: column;
        text-align: center;
    }
    
    .ka-wb-pro-notice-content {
        flex-direction: column;
    }

    .ka-wb-quickstart {
        padding: 20px 20px;
    }

    .ka-wb-quickstart-head {
        flex-direction: column;
        align-items: flex-start;
        gap: 6px;
    }

    .ka-wb-quickstart-steps {
        grid-template-columns: 1fr;
    }
}
</style>

<?php 
ka_render_dark_theme_styles();
ka_render_dark_theme_init();
?>

<script>
if (document.body) {
    document.body.classList.add('ka-admin-v3');
} else {
    document.addEventListener('DOMContentLoaded', function() {
        document.body.classList.add('ka-admin-v3');
    });
}
</script>

<div class="ka-wb">
    <!-- Header -->
    <header class="ka-wb-header">
        <div class="ka-wb-header-content">
            <span class="ka-wb-title-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 6h15l-1.5 9h-12z" />
                    <path d="M6 6l-2-3H1" />
                    <circle cx="9" cy="20" r="1" />
                    <circle cx="18" cy="20" r="1" />
                </svg>
            </span>
            <div class="ka-wb-header-titles">
                <h1><span class="ka-wb-title-text"><?php esc_html_e('WooCommerce Builder', 'king-addons'); ?></span></h1>
                <p><?php esc_html_e('Design beautiful store pages with Elementor', 'king-addons'); ?></p>
            </div>
        </div>
        <div class="ka-wb-header-actions">
            <?php ka_render_dark_theme_toggle(); ?>
        </div>
    </header>

    <?php if (!$can_use_pro): ?>
    <!-- Pro Notice -->
    <div class="ka-wb-pro-notice">
        <div class="ka-wb-pro-notice-content">
            <div class="ka-wb-pro-notice-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                </svg>
            </div>
            <div class="ka-wb-pro-notice-text">
                <h4><?php esc_html_e('Unlock All Templates', 'king-addons'); ?></h4>
                <p><?php esc_html_e('Cart, Checkout, and My Account templates require Pro', 'king-addons'); ?></p>
            </div>
        </div>
        <a href="https://kingaddons.com/pricing/" target="_blank" class="ka-wb-btn ka-wb-btn-primary">
            <?php esc_html_e('Upgrade to Pro', 'king-addons'); ?>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M5 12h14M12 5l7 7-7 7"/>
            </svg>
        </a>
    </div>
    <?php endif; ?>

    <!-- Template Types -->
    <div class="ka-wb-types">
        <?php foreach ($types as $slug => $data): ?>
            <?php 
            $is_locked = (!$can_use_pro && !empty($data['pro']));
            $tag = $is_locked ? 'div' : 'a';
            $href = $is_locked ? '' : ' href="' . esc_url($create_links[$slug]) . '"';
            ?>
            <<?php echo $tag; ?> class="ka-wb-type <?php echo $is_locked ? 'is-locked' : ''; ?>"<?php echo $href; ?>>
                <?php if ($is_locked && !empty($data['pro'])): ?>
                    <span class="ka-wb-type-badge">Pro</span>
                <?php endif; ?>
                <div class="ka-wb-type-icon"><?php echo $data['icon']; ?></div>
                <div class="ka-wb-type-label"><?php echo esc_html($data['label']); ?></div>
                <div class="ka-wb-type-desc"><?php echo esc_html($data['desc']); ?></div>
            </<?php echo $tag; ?>>
        <?php endforeach; ?>
    </div>

    <!-- Templates Section -->
    <section class="ka-wb-section">
        <div class="ka-wb-section-header">
            <h2 class="ka-wb-section-title"><?php esc_html_e('Your Templates', 'king-addons'); ?></h2>
            <span class="ka-wb-section-count"><?php echo count($templates); ?> <?php esc_html_e('templates', 'king-addons'); ?></span>
        </div>

        <div class="ka-wb-templates">
            <?php if (empty($templates)): ?>
                <div class="ka-wb-empty">
                    <div class="ka-wb-empty-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                            <line x1="12" y1="8" x2="12" y2="16"/>
                            <line x1="8" y1="12" x2="16" y2="12"/>
                        </svg>
                    </div>
                    <h3><?php esc_html_e('No templates yet', 'king-addons'); ?></h3>
                    <p><?php esc_html_e('Create your first template by selecting a type above', 'king-addons'); ?></p>
                </div>
            <?php else: ?>
                <?php foreach ($templates as $template): ?>
                    <?php 
                    $elementor_url = ka_woo_get_elementor_edit_url((int) $template['id']);
                    $title_icon = $types[$template['type']]['icon'] ?? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>';
                    $duplicate_url = wp_nonce_url(add_query_arg([
                        'page' => 'king-addons-woo-builder',
                        'template_id' => $template['id'],
                        'ka_woo_duplicate' => 1,
                    ], admin_url('admin.php')), 'ka_woo_duplicate_' . $template['id']);
                    $delete_url = wp_nonce_url(add_query_arg([
                        'page' => 'king-addons-woo-builder',
                        'template_id' => $template['id'],
                        'ka_woo_delete' => 1,
                    ], admin_url('admin.php')), 'ka_woo_delete_' . $template['id']);
                    ?>
                    <div class="ka-wb-template" data-template-id="<?php echo esc_attr($template['id']); ?>">
                        <?php if ($template['status'] === 'draft'): ?>
                            <span class="ka-wb-template-status is-draft"><?php esc_html_e('Draft', 'king-addons'); ?></span>
                        <?php elseif ($template['enabled']): ?>
                            <span class="ka-wb-template-status is-enabled"><?php esc_html_e('Active', 'king-addons'); ?></span>
                        <?php else: ?>
                            <span class="ka-wb-template-status is-disabled"><?php esc_html_e('Inactive', 'king-addons'); ?></span>
                        <?php endif; ?>

                        <div class="ka-wb-template-info">
                            <a href="<?php echo esc_url($elementor_url); ?>" class="ka-wb-template-title">
                                <span class="ka-wb-template-title-icon" aria-hidden="true">
                                    <?php echo $title_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                </span>
                                <span class="ka-wb-template-title-text"><?php echo esc_html($template['title']); ?></span>
                            </a>
                            <div class="ka-wb-template-meta">
                                <span class="ka-wb-template-type">
                                    <?php echo esc_html($types[$template['type']]['label'] ?? $template['type']); ?>
                                </span>
                                <span><?php echo esc_html($template['date']); ?></span>
                            </div>
                        </div>

                        <!-- Condition Badge -->
                        <?php 
                        $has_advanced_conditions = $template['condition_type'] !== 'all';
                        $template_options = $condition_options[$template['type']] ?? ['all' => __('All', 'king-addons')];
                        $show_conditions_button = count($template_options) > 1; // Show only if template type supports conditions
                        ?>
                        <?php if ($show_conditions_button): ?>
                            <button type="button" 
                                class="ka-wb-template-condition <?php echo $has_advanced_conditions ? '' : 'ka-wb-template-condition-btn'; ?>" 
                                onclick="kaWbOpenConditions(<?php echo (int) $template['id']; ?>, '<?php echo esc_js($template['type']); ?>', '<?php echo esc_js($template['condition_type']); ?>', <?php echo esc_attr(wp_json_encode($template['condition_values'])); ?>)"
                                title="<?php echo esc_attr($template['condition_label']); ?>">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="3"/>
                                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                                </svg>
                                <?php echo esc_html($template['condition_label']); ?>
                                <?php if (!$can_use_pro && $has_advanced_conditions): ?>
                                    <span class="ka-wb-pro-badge-inline">Pro</span>
                                <?php endif; ?>
                            </button>
                        <?php else: ?>
                            <span class="ka-wb-template-condition">
                                <?php echo esc_html($template['condition_label']); ?>
                            </span>
                        <?php endif; ?>

                        <div class="ka-wb-template-actions">
                            <a href="<?php echo esc_url($elementor_url); ?>" class="ka-wb-btn ka-wb-btn-primary">
                                <?php esc_html_e('Edit', 'king-addons'); ?>
                            </a>
                            
                            <!-- Dropdown Menu -->
                            <div class="ka-wb-dropdown">
                                <button type="button" class="ka-wb-dropdown-trigger" aria-label="<?php esc_attr_e('More actions', 'king-addons'); ?>">
                                    <svg viewBox="0 0 24 24" fill="currentColor">
                                        <circle cx="12" cy="5" r="2"/>
                                        <circle cx="12" cy="12" r="2"/>
                                        <circle cx="12" cy="19" r="2"/>
                                    </svg>
                                </button>
                                <div class="ka-wb-dropdown-menu">
                                    <!-- Rename -->
                                    <button type="button" class="ka-wb-dropdown-item" onclick="kaWbRename(<?php echo (int) $template['id']; ?>, '<?php echo esc_js($template['title']); ?>')">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/>
                                        </svg>
                                        <?php esc_html_e('Rename', 'king-addons'); ?>
                                    </button>
                                    
                                    <!-- Duplicate -->
                                    <a href="<?php echo esc_url($duplicate_url); ?>" class="ka-wb-dropdown-item">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                                        </svg>
                                        <?php esc_html_e('Duplicate', 'king-addons'); ?>
                                    </a>
                                    
                                    <?php if (!$template['pro_locked']): ?>
                                    <!-- Toggle Active -->
                                    <a href="<?php echo esc_url(wp_nonce_url(add_query_arg([
                                        'page' => 'king-addons-woo-builder',
                                        'template_id' => $template['id'],
                                        'ka_woo_toggle' => 1,
                                    ], admin_url('admin.php')), 'ka_woo_toggle_' . $template['id'])); ?>" class="ka-wb-dropdown-item">
                                        <?php if ($template['enabled']): ?>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="1" y="5" width="22" height="14" rx="7" ry="7"/>
                                            <circle cx="16" cy="12" r="3"/>
                                        </svg>
                                        <?php esc_html_e('Deactivate', 'king-addons'); ?>
                                        <?php else: ?>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="1" y="5" width="22" height="14" rx="7" ry="7"/>
                                            <circle cx="8" cy="12" r="3"/>
                                        </svg>
                                        <?php esc_html_e('Activate', 'king-addons'); ?>
                                        <?php endif; ?>
                                    </a>
                                    <?php endif; ?>
                                    
                                    <div class="ka-wb-dropdown-divider"></div>
                                    
                                    <!-- Change Type -->
                                    <div class="ka-wb-dropdown-label"><?php esc_html_e('Change Type', 'king-addons'); ?></div>
                                    <?php foreach ($types as $type_slug => $type_data): ?>
                                        <?php 
                                        if ($type_slug === $template['type']) continue;
                                        $is_type_locked = (!$can_use_pro && !empty($type_data['pro']));
                                        if ($is_type_locked) continue;
                                        $change_type_url = wp_nonce_url(add_query_arg([
                                            'page' => 'king-addons-woo-builder',
                                            'template_id' => $template['id'],
                                            'ka_woo_change_type' => 1,
                                            'new_type' => $type_slug,
                                        ], admin_url('admin.php')), 'ka_woo_change_type_' . $template['id']);
                                        ?>
                                        <a href="<?php echo esc_url($change_type_url); ?>" class="ka-wb-dropdown-item">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="9 18 15 12 9 6"/>
                                            </svg>
                                            <?php echo esc_html($type_data['label']); ?>
                                        </a>
                                    <?php endforeach; ?>
                                    
                                    <div class="ka-wb-dropdown-divider"></div>
                                    
                                    <!-- Delete -->
                                    <a href="<?php echo esc_url($delete_url); ?>" class="ka-wb-dropdown-item is-danger" onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this template?', 'king-addons'); ?>')">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="3 6 5 6 21 6"/>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                        </svg>
                                        <?php esc_html_e('Delete', 'king-addons'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- Quick Setup -->
    <div class="ka-wb-quickstart">
        <div class="ka-wb-quickstart-head">
            <h3><?php esc_html_e('Quick setup', 'king-addons'); ?></h3>
            <p><?php esc_html_e('3 steps to get started', 'king-addons'); ?></p>
        </div>
        <div class="ka-wb-quickstart-steps">
            <div class="ka-wb-quickstart-step">
                <span class="ka-wb-quickstart-step-number" aria-hidden="true">1</span>
                <div>
                    <p class="ka-wb-quickstart-step-title"><?php esc_html_e('Pick a template type', 'king-addons'); ?></p>
                    <p class="ka-wb-quickstart-step-desc"><?php esc_html_e('Choose what you want to customize: Single Product, Shop & Category, Cart, Checkout, or My Account.', 'king-addons'); ?></p>
                </div>
            </div>
            <div class="ka-wb-quickstart-step">
                <span class="ka-wb-quickstart-step-number" aria-hidden="true">2</span>
                <div>
                    <p class="ka-wb-quickstart-step-title"><?php esc_html_e('Design in Elementor', 'king-addons'); ?></p>
                    <p class="ka-wb-quickstart-step-desc"><?php esc_html_e('You’ll be taken straight into the Elementor editor. Build your layout visually using widgets from "King Addons Woo Builder" category or WooCommerce related widgets provided by other compatible plugins, then save.', 'king-addons'); ?></p>
                </div>
            </div>
            <div class="ka-wb-quickstart-step">
                <span class="ka-wb-quickstart-step-number" aria-hidden="true">3</span>
                <div>
                    <p class="ka-wb-quickstart-step-title"><?php esc_html_e('Manage & activate templates', 'king-addons'); ?></p>
                    <p class="ka-wb-quickstart-step-desc"><?php esc_html_e('Set display conditions in Pro. Activate or deactivate templates, and use the menu to rename, duplicate, or delete them.', 'king-addons'); ?></p>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Rename Modal -->
<div class="ka-wb-modal-overlay" id="kaWbRenameModal">
    <div class="ka-wb-modal">
        <h3><?php esc_html_e('Rename Template', 'king-addons'); ?></h3>
        <form method="post" action="">
            <?php wp_nonce_field('ka_woo_rename', '_wpnonce'); ?>
            <input type="hidden" name="ka_woo_rename" value="1">
            <input type="hidden" name="template_id" id="kaWbRenameId" value="">
            <input type="text" name="new_title" id="kaWbRenameInput" class="ka-wb-modal-input" placeholder="<?php esc_attr_e('Template name', 'king-addons'); ?>">
            <div class="ka-wb-modal-actions">
                <button type="button" class="ka-wb-btn ka-wb-btn-secondary" onclick="kaWbCloseRename()"><?php esc_html_e('Cancel', 'king-addons'); ?></button>
                <button type="submit" class="ka-wb-btn ka-wb-btn-primary"><?php esc_html_e('Save', 'king-addons'); ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Conditions Modal -->
<div class="ka-wb-modal-overlay" id="kaWbConditionsModal">
    <div class="ka-wb-modal ka-wb-modal-conditions">
        <h3><?php esc_html_e('Display Conditions', 'king-addons'); ?></h3>
        <p class="ka-wb-modal-desc"><?php esc_html_e('Choose where this template should be displayed', 'king-addons'); ?></p>
        
        <?php if (!$can_use_pro): ?>
        <!-- Pro Lock Overlay -->
        <div class="ka-wb-modal-locked">
            <div class="ka-wb-modal-locked-overlay">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
                <h4><?php esc_html_e('Pro Feature', 'king-addons'); ?></h4>
                <p><?php esc_html_e('Advanced display conditions are available in Pro', 'king-addons'); ?></p>
                <a href="https://kingaddons.com/pricing/" target="_blank" class="ka-wb-btn ka-wb-btn-primary">
                    <?php esc_html_e('Upgrade to Pro', 'king-addons'); ?>
                </a>
            </div>
        <?php endif; ?>
        
        <form method="post" action="" id="kaWbConditionsForm">
            <?php wp_nonce_field('ka_woo_conditions', '_wpnonce'); ?>
            <input type="hidden" name="ka_woo_update_conditions" value="1">
            <input type="hidden" name="template_id" id="kaWbConditionsId" value="">
            
            <div class="ka-wb-form-group">
                <label class="ka-wb-form-label"><?php esc_html_e('Show template on', 'king-addons'); ?></label>
                <select name="condition_type" id="kaWbConditionType" class="ka-wb-form-select" onchange="kaWbUpdateConditionValues()">
                    <!-- Options filled by JS -->
                </select>
            </div>
            
            <div class="ka-wb-form-group" id="kaWbConditionValuesGroup" style="display: none;">
                <label class="ka-wb-form-label"><?php esc_html_e('Select items', 'king-addons'); ?></label>
                <div class="ka-wb-checkbox-list" id="kaWbConditionValues">
                    <!-- Checkboxes filled by JS -->
                </div>
            </div>
            
            <div class="ka-wb-modal-actions">
                <button type="button" class="ka-wb-btn ka-wb-btn-secondary" onclick="kaWbCloseConditions()"><?php esc_html_e('Cancel', 'king-addons'); ?></button>
                <button type="submit" class="ka-wb-btn ka-wb-btn-primary" <?php echo !$can_use_pro ? 'disabled' : ''; ?>><?php esc_html_e('Save', 'king-addons'); ?></button>
            </div>
        </form>
        
        <?php if (!$can_use_pro): ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Condition options data
const kaWbConditionOptions = <?php echo wp_json_encode($condition_options); ?>;
const kaWbCategories = <?php echo wp_json_encode(array_map(function($cat) {
    return ['id' => $cat->term_id, 'name' => $cat->name];
}, $product_categories)); ?>;
const kaWbTags = <?php echo wp_json_encode(array_map(function($tag) {
    return ['id' => $tag->term_id, 'name' => $tag->name];
}, $product_tags)); ?>;
const kaWbProducts = <?php echo wp_json_encode(array_map(function($product) {
    return ['id' => $product->ID, 'name' => $product->post_title];
}, $products)); ?>;
const kaWbCanUsePro = <?php echo $can_use_pro ? 'true' : 'false'; ?>;

let kaWbCurrentConditionValues = [];

// Dropdown toggle
document.addEventListener('click', function(e) {
    const trigger = e.target.closest('.ka-wb-dropdown-trigger');
    const dropdown = e.target.closest('.ka-wb-dropdown');
    
    // Close all dropdowns first
    document.querySelectorAll('.ka-wb-dropdown.is-open').forEach(function(d) {
        if (d !== dropdown) d.classList.remove('is-open');
    });
    
    // Toggle clicked dropdown
    if (trigger) {
        e.preventDefault();
        e.stopPropagation();
        dropdown.classList.toggle('is-open');
    } else if (!dropdown) {
        document.querySelectorAll('.ka-wb-dropdown.is-open').forEach(function(d) {
            d.classList.remove('is-open');
        });
    }
});

// Rename modal
function kaWbRename(id, title) {
    document.getElementById('kaWbRenameId').value = id;
    document.getElementById('kaWbRenameInput').value = title;
    document.getElementById('kaWbRenameModal').classList.add('is-open');
    setTimeout(function() {
        document.getElementById('kaWbRenameInput').focus();
        document.getElementById('kaWbRenameInput').select();
    }, 100);
}

function kaWbCloseRename() {
    document.getElementById('kaWbRenameModal').classList.remove('is-open');
}

// Conditions modal
function kaWbOpenConditions(templateId, templateType, conditionType, conditionValues) {
    document.getElementById('kaWbConditionsId').value = templateId;
    
    // Build select options
    const select = document.getElementById('kaWbConditionType');
    select.innerHTML = '';
    const options = kaWbConditionOptions[templateType] || {'all': 'All'};
    
    for (const [value, label] of Object.entries(options)) {
        const option = document.createElement('option');
        option.value = value;
        option.textContent = label;
        if (value === conditionType) option.selected = true;
        select.appendChild(option);
    }
    
    // Store current values
    kaWbCurrentConditionValues = conditionValues || [];
    
    // Update values list
    kaWbUpdateConditionValues();
    
    document.getElementById('kaWbConditionsModal').classList.add('is-open');
}

function kaWbUpdateConditionValues() {
    const select = document.getElementById('kaWbConditionType');
    const conditionType = select.value;
    const valuesGroup = document.getElementById('kaWbConditionValuesGroup');
    const valuesContainer = document.getElementById('kaWbConditionValues');
    
    let items = [];
    
    switch (conditionType) {
        case 'product_cat':
            items = kaWbCategories;
            break;
        case 'product_tag':
            items = kaWbTags;
            break;
        case 'specific_product':
            items = kaWbProducts;
            break;
        default:
            valuesGroup.style.display = 'none';
            return;
    }
    
    if (items.length === 0) {
        valuesGroup.style.display = 'none';
        return;
    }
    
    valuesGroup.style.display = 'block';
    valuesContainer.innerHTML = '';
    
    items.forEach(function(item) {
        const isChecked = kaWbCurrentConditionValues.includes(item.id);
        const label = document.createElement('label');
        label.className = 'ka-wb-checkbox-item';
        label.innerHTML = `
            <input type="checkbox" name="condition_values[]" value="${item.id}" ${isChecked ? 'checked' : ''}>
            <span>${item.name}</span>
        `;
        valuesContainer.appendChild(label);
    });
}

function kaWbCloseConditions() {
    document.getElementById('kaWbConditionsModal').classList.remove('is-open');
}

// Form submission for conditions
document.getElementById('kaWbConditionsForm').addEventListener('submit', function(e) {
    if (!kaWbCanUsePro) {
        e.preventDefault();
        return false;
    }
    
    // Update nonce with actual template ID
    const templateId = document.getElementById('kaWbConditionsId').value;
    const nonceField = this.querySelector('input[name="_wpnonce"]');
    // Nonce is already set, let form submit
});

// Close modal on overlay click
document.getElementById('kaWbRenameModal').addEventListener('click', function(e) {
    if (e.target === this) kaWbCloseRename();
});

document.getElementById('kaWbConditionsModal').addEventListener('click', function(e) {
    if (e.target === this) kaWbCloseConditions();
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        kaWbCloseRename();
        kaWbCloseConditions();
    }
});
</script>

<?php ka_render_dark_theme_script(); ?>
