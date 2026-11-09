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
    private static ?Dynamic_Posts_Grid_Ajax $_instance = null;
    private static bool $initialized = false;

    /**
     * Get singleton instance.
     */
    public static function get_instance(): Dynamic_Posts_Grid_Ajax
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor.
     */
    public function __construct()
    {
        // Prevent double initialization
        if (self::$initialized) {
            return;
        }
        self::$initialized = true;

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
     * Create a server-authenticated allowlist from the rendered widget settings.
     * A public nonce is CSRF protection, not permission to choose meta keys.
     *
     * @param array  $settings Elementor settings resolved on the server.
     * @param string $widget_id Widget identifier.
     * @return string Signed request configuration.
     */
    public static function create_request_config(array $settings, string $widget_id): string
    {
        $is_cpt = ($settings['kng_dynamic_posts_widget_mode'] ?? 'default') === 'custom_cpt';
        $types = $is_cpt
            ? ($settings['kng_dynamic_posts_custom_post_types'] ?? [])
            : ($settings['kng_dynamic_posts_post_types'] ?? ['post']);
        $types = is_array($types) ? array_values(array_filter($types, 'is_string')) : [];
        $actions = [];
        if ($is_cpt) {
            foreach ($types as $type) {
                $actions[$type] = [
                    'url_field' => $settings['kng_dynamic_posts_cpt_' . $type . '_url_field'] ?? '',
                    'action_type' => $settings['kng_dynamic_posts_cpt_' . $type . '_action_type'] ?? 'new_tab',
                    'cta_text' => $settings['kng_dynamic_posts_cpt_' . $type . '_cta_text'] ?? ($settings['kng_dynamic_posts_cpt_default_cta'] ?? 'VIEW'),
                ];
            }
        }
        return Grid_Ajax_Security::sign_settings([
            'widget_id' => $widget_id,
            'post_types' => $types,
            'posts_per_page' => max(1, min(100, (int) ($settings['kng_dynamic_posts_per_page'] ?? 12))),
            'orderby' => $settings['kng_dynamic_posts_orderby'] ?? 'date',
            'order' => $settings['kng_dynamic_posts_order'] ?? 'DESC',
            'filter_taxonomy' => $is_cpt ? 'post_type' : ($settings['kng_dynamic_posts_filter_taxonomy'] ?? 'category'),
            'enable_filters' => ($settings['kng_dynamic_posts_enable_filters'] ?? 'yes') === 'yes',
            'enable_search' => ($settings['kng_dynamic_posts_enable_search'] ?? 'yes') === 'yes',
            'show_excerpt' => ($settings['kng_dynamic_posts_show_excerpt'] ?? 'yes') === 'yes',
            'is_cpt_mode' => $is_cpt,
            'cpt_actions' => $actions,
        ], 'dynamic-posts-grid');
    }

    /** Handle an authorized widget filter request. */
    public function handle_filter_request(): void
    {
        $this->handle_request(false);
    }

    /** Handle an authorized widget pagination request. */
    public function handle_load_more_request(): void
    {
        $this->handle_request(true);
    }

    /**
     * Both public endpoints use only authenticated widget settings. Visitors
     * may change the search, filter term and page, never the meta-field map,
     * query source, taxonomy, or rendering options.
     *
     * @param bool $load_more Whether to return an accumulated count.
     * @return void
     */
    private function handle_request(bool $load_more): void
    {
        $nonce = self::request_string('nonce');
        if (!wp_verify_nonce($nonce, 'king_addons_dynamic_posts_grid_nonce')) {
            wp_send_json_error(['message' => esc_html__('Security check failed.', 'king-addons')], 403);
        }
        $config = Grid_Ajax_Security::verify_settings(self::request_string('request_config'), 'dynamic-posts-grid');
        if (null === $config || ($config['widget_id'] ?? '') !== self::request_string('widget_id')) {
            wp_send_json_error(['message' => esc_html__('Invalid grid configuration. Please reload the page.', 'king-addons')], 403);
        }
        $page = max(1, min(10000, absint(self::request_string('page', '1'))));
        $config['page'] = $page;
        $config['filter_term'] = !empty($config['enable_filters'])
            ? sanitize_text_field(self::request_string('filter_term', '*')) : '*';
        $config['search_query'] = !empty($config['enable_search'])
            ? sanitize_text_field(self::request_string('search_query')) : '';
        $query = new \WP_Query($this->build_query_args($config));
        $actions = $this->sanitize_cpt_actions(wp_json_encode($config['cpt_actions'] ?? []));
        $html = $this->generate_posts_html($query, !empty($config['show_excerpt']), !empty($config['is_cpt_mode']), $actions);
        wp_send_json_success([
            'posts_html' => $html,
            'current_page' => $page,
            'max_pages' => $query->max_num_pages,
            'total_posts' => $query->found_posts,
            'current_count' => $load_more ? (($page - 1) * $config['posts_per_page']) + $query->post_count : $query->post_count,
        ]);
    }

    /** Read a scalar POST value; reject array-shaped input without PHP errors. */
    private static function request_string(string $key, string $default = ''): string
    {
        // The caller reads the nonce through this scalar reader, then verifies
        // it in handle_request() before using any other request parameters.
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        return isset($_POST[$key]) && is_string($_POST[$key]) ? sanitize_text_field(wp_unslash($_POST[$key])) : $default;
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
            'post_type' => array_values(array_filter($params['post_types'], static function ($type) {
                $object = get_post_type_object($type);
                return $object && is_post_type_viewable($object);
            })),
            'posts_per_page' => $params['posts_per_page'],
            'paged' => $params['page'],
            'orderby' => $params['orderby'],
            'order' => $params['order'],
            'post_status' => 'publish',
            'has_password' => false,
            'ignore_sticky_posts' => true,
        ];

        // An empty post_type means all types to WP_Query; fail closed instead.
        if (!$query_args['post_type']) {
            $query_args['post__in'] = [0];
        }

        // Add search query
        if (!empty($params['search_query'])) {
            $query_args['s'] = $params['search_query'];
        }

        // Add taxonomy filter
        if (!empty($params['filter_term']) && $params['filter_term'] !== '*') {
            if ($params['filter_taxonomy'] === 'post_type') {
                if (in_array($params['filter_term'], $query_args['post_type'], true)) {
                    $query_args['post_type'] = [$params['filter_term']];
                } else {
                    $query_args['post__in'] = [0];
                }
            } elseif (taxonomy_exists($params['filter_taxonomy'])) {
                $query_args['tax_query'] = [
                    [
                        'taxonomy' => $params['filter_taxonomy'],
                        'field' => 'slug',
                        'terms' => $params['filter_term'],
                    ],
                ];
            }
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
    private function generate_posts_html(\WP_Query $posts_query, bool $show_excerpt = true, bool $is_cpt_mode = false, array $cpt_actions = []): string
    {
        if (!$posts_query->have_posts()) {
            return '<div class="king-addons-dpg-no-posts">' . esc_html__('No posts found.', 'king-addons') . '</div>';
        }

        ob_start();

        while ($posts_query->have_posts()): 
            $posts_query->the_post();
            $post = get_post();
            $cta_text = $this->get_cta_text($post);
            
            if ($is_cpt_mode) {
                $post_type = $post->post_type;
                $card_classes = 'king-addons-dpg-cpt-' . $post_type;
                $post_type_obj = get_post_type_object($post_type);
                $post_type_display = $post_type_obj ? strtoupper($post_type_obj->label) : strtoupper($post_type);
                $post_icon = '<i class="far fa-file-alt"></i>';
            } else {
                $post_type_display = $this->get_post_type_display($post);
                $card_classes = $this->get_post_category_color_classes($post);
                $post_icon = $this->get_post_type_icon($post);
            }
            ?>

            <div class="king-addons-dpg-card king-addons-dpg-item <?php echo esc_attr($card_classes); ?>" data-post-id="<?php echo esc_attr($post->ID); ?>"<?php echo $is_cpt_mode ? ' data-post-type="' . esc_attr($post->post_type) . '"' : ''; ?>>
                
                <!-- Post Type Header -->
                <div class="king-addons-dpg-header">
                    <div class="king-addons-dpg-icon">
                        <?php echo wp_kses_post($post_icon); ?>
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
                    <?php if ($is_cpt_mode) : ?>
                        <?php 
                        $pt = $post->post_type;
                        $action_conf = $cpt_actions[$pt] ?? [];
                        $url_field = $action_conf['url_field'] ?? '';
                        $action_type = $action_conf['action_type'] ?? '';
                        $cta_override = $action_conf['cta_text'] ?? '';
                        $meta_url = $url_field ? get_post_meta($post->ID, $url_field, true) : '';
                        ?>
                        <?php 
                        // Security fix: Block dangerous protocols like javascript:, data:, vbscript:
                        $is_safe_url = is_string($meta_url) && (bool) esc_url($meta_url, ['http', 'https']);
                        ?>
                        <?php if (!empty($meta_url) && !empty($action_type) && $is_safe_url) : ?>
                            <button class="king-addons-dpg-button king-addons-dpg-action-btn" 
                                    data-action="<?php echo esc_attr($action_type); ?>" 
                                    data-url="<?php echo esc_url($meta_url, ['http', 'https']); ?>"
                                    data-title="<?php echo esc_attr(get_the_title()); ?>">
                                <?php echo esc_html(strtoupper($cta_override ?: 'VIEW')); ?>
                            </button>
                        <?php else: ?>
                            <a href="<?php echo esc_url(get_permalink()); ?>" class="king-addons-dpg-button">
                                <?php echo esc_html($cta_text); ?>
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="<?php echo esc_url(get_permalink()); ?>" class="king-addons-dpg-button">
                            <?php echo esc_html($cta_text); ?>
                        </a>
                    <?php endif; ?>
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
     * Sanitize CPT actions configuration JSON.
     *
     * @param string $raw_json Raw JSON from request.
     * @return array Sanitized CPT actions config.
     */
    private function sanitize_cpt_actions(string $raw_json): array
    {
        if (empty($raw_json)) {
            return [];
        }
        $decoded = json_decode($raw_json, true);
        if (!is_array($decoded)) {
            return [];
        }
        $sanitized = [];
        foreach ($decoded as $pt => $conf) {
            $pt_key = sanitize_key($pt);
            if (!$pt_key || !is_array($conf)) {
                continue;
            }
            $sanitized[$pt_key] = [
                'url_field' => isset($conf['url_field']) ? sanitize_key($conf['url_field']) : '',
                'action_type' => in_array($conf['action_type'] ?? '', ['new_tab', 'lightbox_image', 'lightbox_video'], true) ? $conf['action_type'] : '',
                'cta_text' => isset($conf['cta_text']) ? sanitize_text_field($conf['cta_text']) : '',
            ];
        }
        return $sanitized;
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
