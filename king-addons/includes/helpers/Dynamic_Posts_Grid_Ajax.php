<?php

/** @noinspection SpellCheckingInspection, DuplicatedCode, PhpUnused */

namespace King_Addons;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Dynamic Posts Grid AJAX Helper
 * 
 * Handles AJAX requests for filtering, searching, and load more functionality
 * for the Dynamic Posts Grid widget.
 */
class Dynamic_Posts_Grid_Ajax
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->init_hooks();
    }

    /**
     * Initialize hooks.
     */
    private function init_hooks(): void
    {
        // AJAX actions for both logged in and non-logged in users
        add_action('wp_ajax_king_addons_dynamic_posts_grid_filter', [$this, 'handle_filter_request']);
        add_action('wp_ajax_nopriv_king_addons_dynamic_posts_grid_filter', [$this, 'handle_filter_request']);
        
        add_action('wp_ajax_king_addons_dynamic_posts_grid_load_more', [$this, 'handle_load_more_request']);
        add_action('wp_ajax_nopriv_king_addons_dynamic_posts_grid_load_more', [$this, 'handle_load_more_request']);

        // Enqueue AJAX variables
        add_action('wp_enqueue_scripts', [$this, 'enqueue_ajax_variables']);
    }

    /**
     * Enqueue AJAX variables for frontend.
     */
    public function enqueue_ajax_variables(): void
    {
        wp_localize_script('jquery', 'KingAddonsDynamicPostsGrid', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('king_addons_dynamic_posts_grid_nonce'),
        ]);
    }

    /**
     * Handle filter AJAX request.
     */
    public function handle_filter_request(): void
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'king_addons_dynamic_posts_grid_nonce')) {
            wp_send_json_error(['message' => esc_html__('Security check failed.', 'king-addons')]);
        }

        // Get and sanitize POST data
        $widget_id = sanitize_text_field($_POST['widget_id'] ?? '');
        $posts_per_page = absint($_POST['posts_per_page'] ?? 12);
        $post_types = $this->sanitize_array($_POST['post_types'] ?? ['post']);
        $orderby = sanitize_text_field($_POST['orderby'] ?? 'date');
        $order = sanitize_text_field($_POST['order'] ?? 'DESC');
        $filter_taxonomy = sanitize_text_field($_POST['filter_taxonomy'] ?? 'category');
        $filter_term = sanitize_text_field($_POST['filter_term'] ?? '*');
        $search_query = sanitize_text_field($_POST['search_query'] ?? '');
        $page = absint($_POST['page'] ?? 1);
        $show_excerpt = ($_POST['show_excerpt'] ?? '1') === '1';

        // Build query arguments
        $query_args = $this->build_query_args([
            'posts_per_page' => $posts_per_page,
            'post_types' => $post_types,
            'orderby' => $orderby,
            'order' => $order,
            'filter_taxonomy' => $filter_taxonomy,
            'filter_term' => $filter_term,
            'search_query' => $search_query,
            'page' => $page,
        ]);

        // Execute query
        $posts_query = new \WP_Query($query_args);

        // Generate posts HTML
        $posts_html = $this->generate_posts_html($posts_query, $show_excerpt);

        // Prepare response data
        $response_data = [
            'posts_html' => $posts_html,
            'current_page' => $page,
            'max_pages' => $posts_query->max_num_pages,
            'total_posts' => $posts_query->found_posts,
            'current_count' => $posts_query->post_count,
        ];

        wp_send_json_success($response_data);
    }

    /**
     * Handle load more AJAX request.
     */
    public function handle_load_more_request(): void
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'king_addons_dynamic_posts_grid_nonce')) {
            wp_send_json_error(['message' => esc_html__('Security check failed.', 'king-addons')]);
        }

        // Get and sanitize POST data
        $widget_id = sanitize_text_field($_POST['widget_id'] ?? '');
        $posts_per_page = absint($_POST['posts_per_page'] ?? 12);
        $post_types = $this->sanitize_array($_POST['post_types'] ?? ['post']);
        $orderby = sanitize_text_field($_POST['orderby'] ?? 'date');
        $order = sanitize_text_field($_POST['order'] ?? 'DESC');
        $filter_taxonomy = sanitize_text_field($_POST['filter_taxonomy'] ?? 'category');
        $filter_term = sanitize_text_field($_POST['filter_term'] ?? '*');
        $search_query = sanitize_text_field($_POST['search_query'] ?? '');
        $page = absint($_POST['page'] ?? 1);
        $show_excerpt = ($_POST['show_excerpt'] ?? '1') === '1';

        // Build query arguments for load more (accumulative)
        $query_args = $this->build_query_args([
            'posts_per_page' => $posts_per_page,
            'post_types' => $post_types,
            'orderby' => $orderby,
            'order' => $order,
            'filter_taxonomy' => $filter_taxonomy,
            'filter_term' => $filter_term,
            'search_query' => $search_query,
            'page' => $page,
        ]);

        // Execute query
        $posts_query = new \WP_Query($query_args);

        // Generate posts HTML
        $posts_html = $this->generate_posts_html($posts_query, $show_excerpt);

        // Calculate total shown posts (previous pages + current page)
        $total_shown = (($page - 1) * $posts_per_page) + $posts_query->post_count;

        // Prepare response data
        $response_data = [
            'posts_html' => $posts_html,
            'current_page' => $page,
            'max_pages' => $posts_query->max_num_pages,
            'total_posts' => $posts_query->found_posts,
            'current_count' => $total_shown,
        ];

        wp_send_json_success($response_data);
    }

    /**
     * Build WP_Query arguments.
     *
     * @param array $params Query parameters.
     * @return array WP_Query arguments.
     */
    private function build_query_args(array $params): array
    {
        $query_args = [
            'post_type' => $params['post_types'],
            'posts_per_page' => $params['posts_per_page'],
            'paged' => $params['page'],
            'orderby' => $params['orderby'],
            'order' => $params['order'],
            'post_status' => 'publish',
            'ignore_sticky_posts' => true,
        ];

        // Add search query
        if (!empty($params['search_query'])) {
            $query_args['s'] = $params['search_query'];
        }

        // Add taxonomy filter
        if (!empty($params['filter_term']) && $params['filter_term'] !== '*') {
            $query_args['tax_query'] = [
                [
                    'taxonomy' => $params['filter_taxonomy'],
                    'field' => 'slug',
                    'terms' => $params['filter_term'],
                ]
            ];
        }

        // Handle random order
        if ($params['orderby'] === 'rand') {
            $query_args['orderby'] = 'rand';
            unset($query_args['order']); // Order is not applicable for random
        }

        return $query_args;
    }

    /**
     * Generate posts HTML.
     *
     * @param \WP_Query $posts_query WP_Query object.
     * @param bool $show_excerpt Whether to show excerpt.
     * @return string Generated HTML.
     */
    private function generate_posts_html(\WP_Query $posts_query, bool $show_excerpt = true): string
    {
        if (!$posts_query->have_posts()) {
            return '<div class="king-addons-dpg-no-posts">' . esc_html__('No posts found.', 'king-addons') . '</div>';
        }

        ob_start();

        while ($posts_query->have_posts()): 
            $posts_query->the_post();
            $post = get_post();
            $post_type_display = $this->get_post_type_display($post);
            $category_classes = $this->get_post_category_color_classes($post);
            $cta_text = $this->get_cta_text($post);
            $post_icon = $this->get_post_type_icon($post);
            ?>

            <div class="king-addons-dpg-card king-addons-dpg-item <?php echo esc_attr($category_classes); ?>" data-post-id="<?php echo esc_attr($post->ID); ?>">
                
                <!-- Post Type Header -->
                <div class="king-addons-dpg-header">
                    <div class="king-addons-dpg-icon">
                        <?php echo $post_icon; ?>
                    </div>
                    <div class="king-addons-dpg-label">
                        <?php echo esc_html($post_type_display); ?>
                    </div>
                </div>

                                 <!-- Post Content -->
                 <div class="king-addons-dpg-content">
                     <h3 class="king-addons-dpg-title">
                         <a href="<?php echo esc_url(get_permalink()); ?>">
                             <?php echo esc_html(get_the_title()); ?>
                         </a>
                     </h3>
                     
                     <?php if ($show_excerpt): ?>
                     <div class="king-addons-dpg-excerpt">
                         <?php echo esc_html(wp_trim_words(get_the_excerpt(), 20, '...')); ?>
                     </div>
                     <?php endif; ?>
                 </div>

                <!-- CTA Button -->
                <div class="king-addons-dpg-cta">
                    <a href="<?php echo esc_url(get_permalink()); ?>" class="king-addons-dpg-button">
                        <?php echo esc_html($cta_text); ?>
                    </a>
                </div>

            </div>

            <?php
        endwhile;

        wp_reset_postdata();

        return ob_get_clean();
    }

    /**
     * Get post category for display.
     *
     * @param object $post Post object.
     * @return string Post category display name.
     */
    private function get_post_type_display($post): string
    {
        // Get post categories
        $taxonomy = 'category';
        $terms = get_the_terms($post->ID, $taxonomy);
        
        if ($terms && !is_wp_error($terms)) {
            // Use first category name
            $term = $terms[0];
            return strtoupper($term->name);
        }
        
        // Fallback to post format/type based display
        $format = get_post_format($post->ID);
        
        if ($format === 'video') {
            return 'VIDEO';
        } elseif ($format === 'audio') {
            return 'PUBLICATION';
        } elseif ($post->post_type === 'page') {
            return 'WEBSITE';
        } else {
            return 'POST';
        }
    }

    /**
     * Get CTA button text (fallback for AJAX context).
     *
     * @param object $post Post object.
     * @return string CTA button text.
     */
    private function get_cta_text($post): string
    {
        // Simple fallback for AJAX - smart detection only
        $taxonomy = 'category';
        $terms = get_the_terms($post->ID, $taxonomy);
        
        if ($terms && !is_wp_error($terms)) {
            $term = $terms[0];
            $slug = strtolower($term->slug);
            
            // Basic smart mapping for AJAX
            $smart_cta_map = [
                'video' => 'WATCH NOW',
                'videos' => 'WATCH NOW',
                'website' => 'LEARN MORE',
                'websites' => 'LEARN MORE',
            ];
            
            if (isset($smart_cta_map[$slug])) {
                return $smart_cta_map[$slug];
            }
        }
        
        return 'READ MORE';
    }

    /**
     * Get post category color classes.
     *
     * @param object $post Post object.
     * @return string Color class names.
     */
    private function get_post_category_color_classes($post): string
    {
        $classes = [];
        $taxonomy = 'category'; // Default to category for AJAX requests
        
        // Get post categories/terms
        $terms = get_the_terms($post->ID, $taxonomy);
        
        if ($terms && !is_wp_error($terms)) {
            foreach ($terms as $term) {
                $classes[] = 'filter-' . $term->slug;
                $classes[] = 'king-addons-dpg-category-' . $term->slug;
            }
        }
        
        // Add default class if no terms
        if (empty($classes)) {
            $classes[] = 'king-addons-dpg-category-default';
        }
        
        return implode(' ', $classes);
    }

    /**
     * Get post type icon (fallback icons for AJAX context).
     *
     * @param object $post Post object.
     * @return string Icon HTML.
     */
    private function get_post_type_icon($post): string
    {
        // Get post categories/terms
        $taxonomy = 'category';
        $terms = get_the_terms($post->ID, $taxonomy);
        
        if ($terms && !is_wp_error($terms)) {
            // Use first category to determine icon type
            $term = $terms[0];
            
            // Map common category names to icons
            $category_icon_map = [
                'report' => '<i class="far fa-file-alt"></i>',
                'reports' => '<i class="far fa-file-alt"></i>',
                'publication' => '<i class="far fa-newspaper"></i>',
                'publications' => '<i class="far fa-newspaper"></i>',
                'video' => '<i class="far fa-play-circle"></i>',
                'videos' => '<i class="far fa-play-circle"></i>',
                'website' => '<i class="fas fa-globe"></i>',
                'websites' => '<i class="fas fa-globe"></i>',
                'news' => '<i class="far fa-newspaper"></i>',
                'blog' => '<i class="far fa-newspaper"></i>',
                'article' => '<i class="far fa-newspaper"></i>',
                'articles' => '<i class="far fa-newspaper"></i>',
            ];
            
            $slug = strtolower($term->slug);
            if (isset($category_icon_map[$slug])) {
                return $category_icon_map[$slug];
            }
        }
        
        // Fallback based on post format
        $format = get_post_format($post->ID);
        
        if ($format === 'video') {
            return '<i class="far fa-play-circle"></i>';
        } elseif ($format === 'audio') {
            return '<i class="far fa-newspaper"></i>';
        } elseif ($post->post_type === 'page') {
            return '<i class="fas fa-globe"></i>';
        } else {
            return '<i class="far fa-file-alt"></i>';
        }
    }

    /**
     * Sanitize array values.
     *
     * @param array $array Array to sanitize.
     * @return array Sanitized array.
     */
    private function sanitize_array(array $array): array
    {
        return array_map('sanitize_text_field', $array);
    }
} 