<?php
/**
 * Docs & Knowledge Base Extension.
 *
 * Full-featured documentation system for WordPress.
 *
 * @package King_Addons
 */

namespace King_Addons;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main Docs & Knowledge Base class.
 */
final class Docs_KB
{
    /**
     * Option name for settings.
     */
    private const OPTION_NAME = 'king_addons_docs_kb_options';

    /**
     * Post type name.
     */
    public const POST_TYPE = 'kng_doc';

    /**
     * Taxonomy name.
     */
    public const TAXONOMY = 'kng_doc_category';

    /**
     * REST API namespace.
     */
    public const API_NAMESPACE = 'king-addons/v1';

    /**
     * Singleton instance.
     *
     * @var Docs_KB|null
     */
    private static ?Docs_KB $instance = null;

    /**
     * Cached options.
     *
     * @var array<string, mixed>
     */
    private array $options = [];

    /**
     * Gets singleton instance.
     *
     * @return Docs_KB
     */
    public static function instance(): Docs_KB
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->options = $this->get_options();

        // Activation
        register_activation_hook(KING_ADDONS_PATH . 'king-addons.php', [$this, 'handle_activation']);

        // Init
        add_action('init', [$this, 'register_post_type']);
        add_action('init', [$this, 'register_taxonomy']);
        add_action('init', [$this, 'add_rewrite_rules']);

        // Admin
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('admin_post_king_addons_docs_kb_save', [$this, 'handle_save_settings']);
        add_filter('manage_' . self::POST_TYPE . '_posts_columns', [$this, 'add_admin_columns']);
        add_action('manage_' . self::POST_TYPE . '_posts_custom_column', [$this, 'render_admin_columns'], 10, 2);

        // Frontend
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_filter('template_include', [$this, 'template_loader']);
        add_filter('the_content', [$this, 'maybe_add_toc']);

        // REST API
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // AJAX feedback (Pro)
        add_action('wp_ajax_king_docs_feedback', [$this, 'ajax_save_feedback']);
        add_action('wp_ajax_nopriv_king_docs_feedback', [$this, 'ajax_save_feedback']);

        // Track views (Pro)
        add_action('wp', [$this, 'track_view']);
    }

    /**
     * Handles plugin activation.
     *
     * @return void
     */
    public function handle_activation(): void
    {
        if (!get_option(self::OPTION_NAME)) {
            add_option(self::OPTION_NAME, $this->get_default_options());
        }

        $this->register_post_type();
        $this->register_taxonomy();
        $this->add_rewrite_rules();
        flush_rewrite_rules();
    }

    /**
     * Gets default options.
     *
     * @return array<string, mixed>
     */
    private function get_default_options(): array
    {
        return [
            // General
            'enabled' => false,
            'docs_slug' => 'docs',
            'main_page_id' => 0,
            'docs_per_page' => 10,

            // Layout
            'layout' => 'card', // box, card, modern
            'columns' => 3,
            'show_article_count' => true,
            'show_category_icon' => true,

            // Single Article
            'toc_enabled' => true,
            'toc_sticky' => true,
            'toc_headings' => 'h2,h3',
            'sidebar_enabled' => true,
            'navigation_enabled' => true,
            'print_button' => true,

            // Search
            'search_enabled' => true,
            'search_placeholder' => __('Search documentation...', 'king-addons'),
            'search_min_chars' => 2,

            // Pro: Multiple KBs
            'multiple_kb_enabled' => false,

            // Pro: Internal docs
            'internal_docs_enabled' => false,
            'internal_docs_roles' => ['administrator'],

            // Pro: Feedback
            'feedback_enabled' => false,
            'feedback_question' => __('Was this article helpful?', 'king-addons'),

            // Pro: Related
            'related_enabled' => false,
            'related_count' => 3,

            // Pro: Analytics
            'analytics_enabled' => false,
            'analytics_email_report' => false,
            'analytics_email' => get_option('admin_email'),

            // Colors
            'primary_color' => '#0066ff',
            'category_icon_color' => '#0066ff',
            'link_color' => '#0066ff',
        ];
    }

    /**
     * Gets options.
     *
     * @return array<string, mixed>
     */
    public function get_options(): array
    {
        $saved = get_option(self::OPTION_NAME, []);
        return wp_parse_args($saved, $this->get_default_options());
    }

    /**
     * Checks if premium.
     *
     * @return bool
     */
    public function is_premium(): bool
    {
        return function_exists('king_addons_freemius')
            && king_addons_freemius()->can_use_premium_code__premium_only();
    }

    /**
     * Registers the Doc post type.
     *
     * @return void
     */
    public function register_post_type(): void
    {
        $slug = sanitize_title($this->options['docs_slug'] ?? 'docs');

        $labels = [
            'name' => __('Docs', 'king-addons'),
            'singular_name' => __('Doc', 'king-addons'),
            'add_new' => __('Add New Doc', 'king-addons'),
            'add_new_item' => __('Add New Doc', 'king-addons'),
            'edit_item' => __('Edit Doc', 'king-addons'),
            'new_item' => __('New Doc', 'king-addons'),
            'view_item' => __('View Doc', 'king-addons'),
            'search_items' => __('Search Docs', 'king-addons'),
            'not_found' => __('No docs found', 'king-addons'),
            'not_found_in_trash' => __('No docs found in Trash', 'king-addons'),
            'menu_name' => __('Docs', 'king-addons'),
        ];

        $args = [
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => false,
            'show_in_rest' => true,
            'query_var' => true,
            'rewrite' => [
                'slug' => $slug,
                'with_front' => false,
            ],
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 25,
            'menu_icon' => 'dashicons-book-alt',
            'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'custom-fields'],
        ];

        register_post_type(self::POST_TYPE, $args);
    }

    /**
     * Registers the Doc Category taxonomy.
     *
     * @return void
     */
    public function register_taxonomy(): void
    {
        $slug = sanitize_title($this->options['docs_slug'] ?? 'docs');

        $labels = [
            'name' => __('Doc Categories', 'king-addons'),
            'singular_name' => __('Doc Category', 'king-addons'),
            'search_items' => __('Search Categories', 'king-addons'),
            'all_items' => __('All Categories', 'king-addons'),
            'parent_item' => __('Parent Category', 'king-addons'),
            'parent_item_colon' => __('Parent Category:', 'king-addons'),
            'edit_item' => __('Edit Category', 'king-addons'),
            'update_item' => __('Update Category', 'king-addons'),
            'add_new_item' => __('Add New Category', 'king-addons'),
            'new_item_name' => __('New Category Name', 'king-addons'),
            'menu_name' => __('Categories', 'king-addons'),
        ];

        $args = [
            'labels' => $labels,
            'hierarchical' => true,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_rest' => true,
            'query_var' => true,
            'rewrite' => [
                'slug' => $slug . '/category',
                'with_front' => false,
                'hierarchical' => true,
            ],
        ];

        register_taxonomy(self::TAXONOMY, self::POST_TYPE, $args);

        // Add custom meta fields to taxonomy
        add_action(self::TAXONOMY . '_add_form_fields', [$this, 'add_category_fields']);
        add_action(self::TAXONOMY . '_edit_form_fields', [$this, 'edit_category_fields'], 10, 2);
        add_action('created_' . self::TAXONOMY, [$this, 'save_category_fields']);
        add_action('edited_' . self::TAXONOMY, [$this, 'save_category_fields']);
    }

    /**
     * Adds category custom fields on add form.
     *
     * @return void
     */
    public function add_category_fields(): void
    {
        ?>
        <div class="form-field">
            <label for="kng_doc_cat_icon"><?php esc_html_e('Category Icon', 'king-addons'); ?></label>
            <select name="kng_doc_cat_icon" id="kng_doc_cat_icon">
                <option value="book"><?php esc_html_e('Book', 'king-addons'); ?></option>
                <option value="lightbulb"><?php esc_html_e('Lightbulb', 'king-addons'); ?></option>
                <option value="gear"><?php esc_html_e('Gear', 'king-addons'); ?></option>
                <option value="rocket"><?php esc_html_e('Rocket', 'king-addons'); ?></option>
                <option value="star"><?php esc_html_e('Star', 'king-addons'); ?></option>
                <option value="code"><?php esc_html_e('Code', 'king-addons'); ?></option>
                <option value="help"><?php esc_html_e('Help', 'king-addons'); ?></option>
                <option value="video"><?php esc_html_e('Video', 'king-addons'); ?></option>
            </select>
            <p class="description"><?php esc_html_e('Select an icon for this category.', 'king-addons'); ?></p>
        </div>
        <div class="form-field">
            <label for="kng_doc_cat_order"><?php esc_html_e('Order', 'king-addons'); ?></label>
            <input type="number" name="kng_doc_cat_order" id="kng_doc_cat_order" value="0" min="0">
            <p class="description"><?php esc_html_e('Custom order for sorting categories.', 'king-addons'); ?></p>
        </div>
        <?php
    }

    /**
     * Adds category custom fields on edit form.
     *
     * @param \WP_Term $term Term object.
     * @return void
     */
    public function edit_category_fields(\WP_Term $term): void
    {
        $icon = get_term_meta($term->term_id, 'kng_doc_cat_icon', true) ?: 'book';
        $order = get_term_meta($term->term_id, 'kng_doc_cat_order', true) ?: 0;
        ?>
        <tr class="form-field">
            <th scope="row"><label for="kng_doc_cat_icon"><?php esc_html_e('Category Icon', 'king-addons'); ?></label></th>
            <td>
                <select name="kng_doc_cat_icon" id="kng_doc_cat_icon">
                    <option value="book" <?php selected($icon, 'book'); ?>><?php esc_html_e('Book', 'king-addons'); ?></option>
                    <option value="lightbulb" <?php selected($icon, 'lightbulb'); ?>><?php esc_html_e('Lightbulb', 'king-addons'); ?></option>
                    <option value="gear" <?php selected($icon, 'gear'); ?>><?php esc_html_e('Gear', 'king-addons'); ?></option>
                    <option value="rocket" <?php selected($icon, 'rocket'); ?>><?php esc_html_e('Rocket', 'king-addons'); ?></option>
                    <option value="star" <?php selected($icon, 'star'); ?>><?php esc_html_e('Star', 'king-addons'); ?></option>
                    <option value="code" <?php selected($icon, 'code'); ?>><?php esc_html_e('Code', 'king-addons'); ?></option>
                    <option value="help" <?php selected($icon, 'help'); ?>><?php esc_html_e('Help', 'king-addons'); ?></option>
                    <option value="video" <?php selected($icon, 'video'); ?>><?php esc_html_e('Video', 'king-addons'); ?></option>
                </select>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label for="kng_doc_cat_order"><?php esc_html_e('Order', 'king-addons'); ?></label></th>
            <td>
                <input type="number" name="kng_doc_cat_order" id="kng_doc_cat_order" value="<?php echo esc_attr($order); ?>" min="0">
            </td>
        </tr>
        <?php
    }

    /**
     * Saves category custom fields.
     *
     * @param int $term_id Term ID.
     * @return void
     */
    public function save_category_fields(int $term_id): void
    {
        if (isset($_POST['kng_doc_cat_icon'])) {
            update_term_meta($term_id, 'kng_doc_cat_icon', sanitize_text_field($_POST['kng_doc_cat_icon']));
        }
        if (isset($_POST['kng_doc_cat_order'])) {
            update_term_meta($term_id, 'kng_doc_cat_order', intval($_POST['kng_doc_cat_order']));
        }
    }

    /**
     * Adds rewrite rules.
     *
     * @return void
     */
    public function add_rewrite_rules(): void
    {
        $slug = sanitize_title($this->options['docs_slug'] ?? 'docs');

        // Archive page
        add_rewrite_rule(
            '^' . $slug . '/?$',
            'index.php?post_type=' . self::POST_TYPE,
            'top'
        );

        // Category page
        add_rewrite_rule(
            '^' . $slug . '/category/([^/]+)/?$',
            'index.php?' . self::TAXONOMY . '=$matches[1]',
            'top'
        );

        // Single doc
        add_rewrite_rule(
            '^' . $slug . '/([^/]+)/?$',
            'index.php?' . self::POST_TYPE . '=$matches[1]',
            'top'
        );

        // Flush rules only once after settings change
        if (get_option('king_addons_docs_kb_rewrite_flushed') !== $slug) {
            flush_rewrite_rules();
            update_option('king_addons_docs_kb_rewrite_flushed', $slug);
        }
    }

    /**
     * Adds admin columns.
     *
     * @param array $columns Existing columns.
     * @return array
     */
    public function add_admin_columns(array $columns): array
    {
        $new_columns = [];
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'title') {
                $new_columns['doc_category'] = __('Category', 'king-addons');
            }
        }
        $new_columns['doc_views'] = __('Views', 'king-addons');
        $new_columns['doc_feedback'] = __('Feedback', 'king-addons');
        return $new_columns;
    }

    /**
     * Renders admin columns.
     *
     * @param string $column Column name.
     * @param int $post_id Post ID.
     * @return void
     */
    public function render_admin_columns(string $column, int $post_id): void
    {
        switch ($column) {
            case 'doc_category':
                $terms = get_the_terms($post_id, self::TAXONOMY);
                if ($terms && !is_wp_error($terms)) {
                    $term_names = wp_list_pluck($terms, 'name');
                    echo esc_html(implode(', ', $term_names));
                } else {
                    echo '—';
                }
                break;

            case 'doc_views':
                $views = get_post_meta($post_id, '_kng_doc_views', true) ?: 0;
                echo esc_html(number_format_i18n($views));
                break;

            case 'doc_feedback':
                $helpful = get_post_meta($post_id, '_kng_doc_helpful', true) ?: 0;
                $not_helpful = get_post_meta($post_id, '_kng_doc_not_helpful', true) ?: 0;
                echo '<span style="color:#34c759">👍 ' . esc_html($helpful) . '</span> / ';
                echo '<span style="color:#ff3b30">👎 ' . esc_html($not_helpful) . '</span>';
                break;
        }
    }

    /**
     * Enqueues admin assets.
     *
     * @param string $hook Current admin page.
     * @return void
     */
    public function enqueue_admin_assets(string $hook): void
    {
        if ($hook !== 'king-addons_page_king-addons-docs-kb') {
            return;
        }

        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');

        wp_enqueue_style(
            'king-addons-v3-styles',
            KING_ADDONS_URL . 'includes/admin/layouts/shared/admin-v3-styles.css',
            [],
            KING_ADDONS_VERSION
        );

        wp_enqueue_style(
            'king-addons-docs-kb-admin',
            KING_ADDONS_URL . 'includes/extensions/Docs_KB/assets/admin.css',
            ['king-addons-v3-styles'],
            KING_ADDONS_VERSION
        );

        wp_enqueue_script(
            'king-addons-docs-kb-admin',
            KING_ADDONS_URL . 'includes/extensions/Docs_KB/assets/admin.js',
            ['jquery', 'wp-color-picker'],
            KING_ADDONS_VERSION,
            true
        );
    }

    /**
     * Enqueues frontend assets.
     *
     * @return void
     */
    public function enqueue_frontend_assets(): void
    {
        if (!$this->options['enabled']) {
            return;
        }

        // Only on docs pages
        if (!is_post_type_archive(self::POST_TYPE) 
            && !is_singular(self::POST_TYPE) 
            && !is_tax(self::TAXONOMY)
            && !$this->is_docs_main_page()
        ) {
            return;
        }

        wp_enqueue_style(
            'king-addons-docs-kb',
            KING_ADDONS_URL . 'includes/extensions/Docs_KB/assets/frontend.css',
            [],
            KING_ADDONS_VERSION
        );

        wp_enqueue_script(
            'king-addons-docs-kb',
            KING_ADDONS_URL . 'includes/extensions/Docs_KB/assets/frontend.js',
            [],
            KING_ADDONS_VERSION,
            true
        );

        $options = $this->options;

        wp_localize_script('king-addons-docs-kb', 'kingDocsKB', [
            'restUrl' => rest_url(self::API_NAMESPACE . '/docs'),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_rest'),
            'feedbackNonce' => wp_create_nonce('king_docs_feedback'),
            'searchEnabled' => !empty($options['search_enabled']),
            'searchMinChars' => intval($options['search_min_chars'] ?? 2),
            'tocEnabled' => !empty($options['toc_enabled']),
            'tocSticky' => !empty($options['toc_sticky']),
            'tocHeadings' => $options['toc_headings'] ?? 'h2,h3',
            'feedbackEnabled' => !empty($options['feedback_enabled']) && $this->is_premium(),
            'strings' => [
                'searchPlaceholder' => $options['search_placeholder'] ?? __('Search documentation...', 'king-addons'),
                'noResults' => __('No results found', 'king-addons'),
                'searching' => __('Searching...', 'king-addons'),
                'feedbackQuestion' => $options['feedback_question'] ?? __('Was this article helpful?', 'king-addons'),
                'feedbackThanks' => __('Thanks for your feedback!', 'king-addons'),
                'tocTitle' => __('On this page', 'king-addons'),
            ],
        ]);

        // Add inline CSS for custom colors
        $custom_css = $this->get_custom_css();
        wp_add_inline_style('king-addons-docs-kb', $custom_css);
    }

    /**
     * Gets custom CSS based on settings.
     *
     * @return string
     */
    private function get_custom_css(): string
    {
        $options = $this->options;
        $primary = sanitize_hex_color($options['primary_color'] ?? '#0066ff');
        $icon_color = sanitize_hex_color($options['category_icon_color'] ?? '#0066ff');
        $link_color = sanitize_hex_color($options['link_color'] ?? '#0066ff');

        return "
            :root {
                --kng-docs-primary: {$primary};
                --kng-docs-icon-color: {$icon_color};
                --kng-docs-link-color: {$link_color};
            }
        ";
    }

    /**
     * Checks if current page is docs main page.
     *
     * @return bool
     */
    private function is_docs_main_page(): bool
    {
        $main_page_id = intval($this->options['main_page_id'] ?? 0);
        return $main_page_id > 0 && is_page($main_page_id);
    }

    /**
     * Loads custom templates.
     *
     * @param string $template Current template.
     * @return string
     */
    public function template_loader(string $template): string
    {
        if (!$this->options['enabled']) {
            return $template;
        }

        // Main docs page
        if ($this->is_docs_main_page()) {
            $custom_template = __DIR__ . '/templates/archive-docs.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }

        // Archive
        if (is_post_type_archive(self::POST_TYPE)) {
            $custom_template = __DIR__ . '/templates/archive-docs.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }

        // Taxonomy
        if (is_tax(self::TAXONOMY)) {
            $custom_template = __DIR__ . '/templates/taxonomy-docs.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }

        // Single doc
        if (is_singular(self::POST_TYPE)) {
            $custom_template = __DIR__ . '/templates/single-doc.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }

        return $template;
    }

    /**
     * Maybe adds TOC to content.
     *
     * @param string $content Post content.
     * @return string
     */
    public function maybe_add_toc(string $content): string
    {
        if (!is_singular(self::POST_TYPE) || !$this->options['toc_enabled']) {
            return $content;
        }

        // TOC is added via JavaScript for better control
        return $content;
    }

    /**
     * Registers REST API routes.
     *
     * @return void
     */
    public function register_rest_routes(): void
    {
        // Search endpoint
        register_rest_route(self::API_NAMESPACE, '/docs/search', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_search'],
            'permission_callback' => '__return_true',
            'args' => [
                'q' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'kb' => [
                    'required' => false,
                    'type' => 'integer',
                    'default' => 0,
                ],
            ],
        ]);

        // Get categories endpoint
        register_rest_route(self::API_NAMESPACE, '/docs/categories', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_categories'],
            'permission_callback' => '__return_true',
        ]);

        // Get articles by category endpoint
        register_rest_route(self::API_NAMESPACE, '/docs/category/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_category_articles'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * REST: Search docs.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function rest_search(\WP_REST_Request $request): \WP_REST_Response
    {
        $query = $request->get_param('q');
        $kb = $request->get_param('kb');

        if (strlen($query) < ($this->options['search_min_chars'] ?? 2)) {
            return new \WP_REST_Response(['results' => []], 200);
        }

        $args = [
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => 10,
            's' => $query,
            'orderby' => 'relevance',
        ];

        // Pro: Internal docs visibility check
        if ($this->is_premium() && !empty($this->options['internal_docs_enabled'])) {
            if (!is_user_logged_in()) {
                $args['meta_query'] = [
                    [
                        'key' => '_kng_doc_visibility',
                        'value' => 'public',
                        'compare' => '=',
                    ],
                ];
            }
        }

        $search_query = new \WP_Query($args);
        $results = [];

        foreach ($search_query->posts as $post) {
            $categories = get_the_terms($post->ID, self::TAXONOMY);
            $category_name = $categories && !is_wp_error($categories) 
                ? $categories[0]->name 
                : '';

            $excerpt = $post->post_excerpt ?: wp_trim_words($post->post_content, 20, '...');

            // Highlight matches in title and excerpt
            $highlighted_title = $this->highlight_matches($post->post_title, $query);
            $highlighted_excerpt = $this->highlight_matches($excerpt, $query);

            $results[] = [
                'id' => $post->ID,
                'title' => $post->post_title,
                'highlighted_title' => $highlighted_title,
                'url' => get_permalink($post->ID),
                'excerpt' => $excerpt,
                'highlighted_excerpt' => $highlighted_excerpt,
                'category' => $category_name,
                'date' => get_the_date('', $post),
            ];
        }

        // Pro: Log search query for analytics
        if ($this->is_premium() && !empty($this->options['analytics_enabled'])) {
            $this->log_search_query($query, count($results));
        }

        return new \WP_REST_Response(['results' => $results], 200);
    }

    /**
     * Highlights search matches in text.
     *
     * @param string $text Text to highlight.
     * @param string $query Search query.
     * @return string
     */
    private function highlight_matches(string $text, string $query): string
    {
        if (empty($query)) {
            return $text;
        }

        $words = explode(' ', $query);
        foreach ($words as $word) {
            if (strlen($word) >= 2) {
                $text = preg_replace(
                    '/(' . preg_quote($word, '/') . ')/iu',
                    '<mark>$1</mark>',
                    $text
                );
            }
        }

        return $text;
    }

    /**
     * Logs search query for analytics.
     *
     * @param string $query Search query.
     * @param int $results_count Number of results.
     * @return void
     */
    private function log_search_query(string $query, int $results_count): void
    {
        $logs = get_option('king_addons_docs_search_logs', []);
        $logs[] = [
            'query' => $query,
            'results' => $results_count,
            'timestamp' => current_time('mysql'),
        ];

        // Keep only last 1000 entries
        if (count($logs) > 1000) {
            $logs = array_slice($logs, -1000);
        }

        update_option('king_addons_docs_search_logs', $logs);
    }

    /**
     * REST: Get categories.
     *
     * @return \WP_REST_Response
     */
    public function rest_get_categories(): \WP_REST_Response
    {
        $categories = get_terms([
            'taxonomy' => self::TAXONOMY,
            'hide_empty' => true,
            'orderby' => 'meta_value_num',
            'meta_key' => 'kng_doc_cat_order',
            'order' => 'ASC',
        ]);

        $results = [];
        foreach ($categories as $cat) {
            if (is_wp_error($cat)) {
                continue;
            }

            $results[] = [
                'id' => $cat->term_id,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'description' => $cat->description,
                'count' => $cat->count,
                'icon' => get_term_meta($cat->term_id, 'kng_doc_cat_icon', true) ?: 'book',
                'url' => get_term_link($cat),
            ];
        }

        return new \WP_REST_Response($results, 200);
    }

    /**
     * REST: Get articles by category.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function rest_get_category_articles(\WP_REST_Request $request): \WP_REST_Response
    {
        $category_id = $request->get_param('id');

        $args = [
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'tax_query' => [
                [
                    'taxonomy' => self::TAXONOMY,
                    'field' => 'term_id',
                    'terms' => $category_id,
                ],
            ],
            'orderby' => 'menu_order title',
            'order' => 'ASC',
        ];

        $query = new \WP_Query($args);
        $results = [];

        foreach ($query->posts as $post) {
            $results[] = [
                'id' => $post->ID,
                'title' => $post->post_title,
                'url' => get_permalink($post->ID),
                'excerpt' => $post->post_excerpt ?: wp_trim_words($post->post_content, 15, '...'),
            ];
        }

        return new \WP_REST_Response($results, 200);
    }

    /**
     * Tracks article view.
     *
     * @return void
     */
    public function track_view(): void
    {
        if (!is_singular(self::POST_TYPE) || !$this->options['analytics_enabled'] || !$this->is_premium()) {
            return;
        }

        $post_id = get_the_ID();
        $views = get_post_meta($post_id, '_kng_doc_views', true) ?: 0;
        update_post_meta($post_id, '_kng_doc_views', $views + 1);

        // Track unique views via cookie
        $cookie_name = 'kng_doc_viewed_' . $post_id;
        if (!isset($_COOKIE[$cookie_name])) {
            $unique_views = get_post_meta($post_id, '_kng_doc_unique_views', true) ?: 0;
            update_post_meta($post_id, '_kng_doc_unique_views', $unique_views + 1);
            setcookie($cookie_name, '1', time() + DAY_IN_SECONDS, '/');
        }
    }

    /**
     * AJAX: Save article feedback.
     *
     * @return void
     */
    public function ajax_save_feedback(): void
    {
        check_ajax_referer('king_docs_feedback', 'nonce');

        $post_id = intval($_POST['post_id'] ?? 0);
        $helpful = sanitize_text_field($_POST['helpful'] ?? '');

        if (!$post_id || !in_array($helpful, ['yes', 'no'])) {
            wp_send_json_error('Invalid data');
        }

        // Check if already voted via cookie
        $cookie_name = 'kng_doc_feedback_' . $post_id;
        if (isset($_COOKIE[$cookie_name])) {
            wp_send_json_error('Already voted');
        }

        $meta_key = $helpful === 'yes' ? '_kng_doc_helpful' : '_kng_doc_not_helpful';
        $count = get_post_meta($post_id, $meta_key, true) ?: 0;
        update_post_meta($post_id, $meta_key, $count + 1);

        // Set cookie to prevent duplicate votes
        setcookie($cookie_name, $helpful, time() + YEAR_IN_SECONDS, '/');

        wp_send_json_success([
            'helpful' => get_post_meta($post_id, '_kng_doc_helpful', true) ?: 0,
            'not_helpful' => get_post_meta($post_id, '_kng_doc_not_helpful', true) ?: 0,
        ]);
    }

    /**
     * Gets docs by category for display.
     *
     * @param int $limit Limit per category.
     * @return array
     */
    public function get_categories_with_docs(int $limit = 5): array
    {
        $categories = get_terms([
            'taxonomy' => self::TAXONOMY,
            'hide_empty' => true,
            'parent' => 0,
            'orderby' => 'meta_value_num',
            'meta_key' => 'kng_doc_cat_order',
            'order' => 'ASC',
        ]);

        $results = [];

        foreach ($categories as $cat) {
            if (is_wp_error($cat)) {
                continue;
            }

            $docs = get_posts([
                'post_type' => self::POST_TYPE,
                'post_status' => 'publish',
                'posts_per_page' => $limit,
                'tax_query' => [
                    [
                        'taxonomy' => self::TAXONOMY,
                        'field' => 'term_id',
                        'terms' => $cat->term_id,
                    ],
                ],
                'orderby' => 'menu_order title',
                'order' => 'ASC',
            ]);

            $doc_items = [];
            foreach ($docs as $doc) {
                $doc_items[] = [
                    'id' => $doc->ID,
                    'title' => $doc->post_title,
                    'url' => get_permalink($doc->ID),
                ];
            }

            // Get subcategories
            $subcats = get_terms([
                'taxonomy' => self::TAXONOMY,
                'hide_empty' => true,
                'parent' => $cat->term_id,
                'orderby' => 'meta_value_num',
                'meta_key' => 'kng_doc_cat_order',
                'order' => 'ASC',
            ]);

            $subcat_items = [];
            foreach ($subcats as $subcat) {
                if (is_wp_error($subcat)) {
                    continue;
                }
                $subcat_items[] = [
                    'id' => $subcat->term_id,
                    'name' => $subcat->name,
                    'count' => $subcat->count,
                    'url' => get_term_link($subcat),
                ];
            }

            $results[] = [
                'id' => $cat->term_id,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'description' => $cat->description,
                'count' => $cat->count,
                'icon' => get_term_meta($cat->term_id, 'kng_doc_cat_icon', true) ?: 'book',
                'url' => get_term_link($cat),
                'docs' => $doc_items,
                'subcategories' => $subcat_items,
            ];
        }

        return $results;
    }

    /**
     * Gets related docs for an article.
     *
     * @param int $post_id Post ID.
     * @param int $count Number of related docs.
     * @return array
     */
    public function get_related_docs(int $post_id, int $count = 3): array
    {
        $categories = wp_get_post_terms($post_id, self::TAXONOMY, ['fields' => 'ids']);

        if (empty($categories) || is_wp_error($categories)) {
            return [];
        }

        $args = [
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => $count,
            'post__not_in' => [$post_id],
            'tax_query' => [
                [
                    'taxonomy' => self::TAXONOMY,
                    'field' => 'term_id',
                    'terms' => $categories,
                ],
            ],
            'orderby' => 'rand',
        ];

        $query = new \WP_Query($args);
        $results = [];

        foreach ($query->posts as $post) {
            $results[] = [
                'id' => $post->ID,
                'title' => $post->post_title,
                'url' => get_permalink($post->ID),
                'excerpt' => $post->post_excerpt ?: wp_trim_words($post->post_content, 15, '...'),
            ];
        }

        return $results;
    }

    /**
     * Gets prev/next navigation for an article.
     *
     * @param int $post_id Post ID.
     * @return array
     */
    public function get_article_navigation(int $post_id): array
    {
        $categories = wp_get_post_terms($post_id, self::TAXONOMY, ['fields' => 'ids']);

        if (empty($categories) || is_wp_error($categories)) {
            return ['prev' => null, 'next' => null];
        }

        $all_docs = get_posts([
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'tax_query' => [
                [
                    'taxonomy' => self::TAXONOMY,
                    'field' => 'term_id',
                    'terms' => $categories[0],
                ],
            ],
            'orderby' => 'menu_order title',
            'order' => 'ASC',
            'fields' => 'ids',
        ]);

        $current_index = array_search($post_id, $all_docs);
        $prev = null;
        $next = null;

        if ($current_index !== false) {
            if ($current_index > 0) {
                $prev_id = $all_docs[$current_index - 1];
                $prev = [
                    'id' => $prev_id,
                    'title' => get_the_title($prev_id),
                    'url' => get_permalink($prev_id),
                ];
            }
            if ($current_index < count($all_docs) - 1) {
                $next_id = $all_docs[$current_index + 1];
                $next = [
                    'id' => $next_id,
                    'title' => get_the_title($next_id),
                    'url' => get_permalink($next_id),
                ];
            }
        }

        return ['prev' => $prev, 'next' => $next];
    }

    /**
     * Renders admin page.
     *
     * @return void
     */
    public function render_admin_page(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $this->options = $this->get_options();
        $options = $this->options;
        $is_premium = $this->is_premium();

        include __DIR__ . '/templates/admin-page.php';
    }

    /**
     * Handles save settings.
     *
     * @return void
     */
    public function handle_save_settings(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        check_admin_referer('king_addons_docs_kb_save', 'king_docs_kb_nonce');

        $old_slug = $this->options['docs_slug'] ?? 'docs';
        $options = [];

        // General
        $options['enabled'] = !empty($_POST['enabled']);
        $options['docs_slug'] = sanitize_title($_POST['docs_slug'] ?? 'docs');
        $options['main_page_id'] = intval($_POST['main_page_id'] ?? 0);
        $options['docs_per_page'] = max(1, intval($_POST['docs_per_page'] ?? 10));

        // Layout
        $options['layout'] = in_array($_POST['layout'] ?? 'card', ['box', 'card', 'modern']) 
            ? sanitize_text_field($_POST['layout']) 
            : 'card';
        $options['columns'] = max(1, min(4, intval($_POST['columns'] ?? 3)));
        $options['show_article_count'] = !empty($_POST['show_article_count']);
        $options['show_category_icon'] = !empty($_POST['show_category_icon']);

        // Single Article
        $options['toc_enabled'] = !empty($_POST['toc_enabled']);
        $options['toc_sticky'] = !empty($_POST['toc_sticky']);
        $options['toc_headings'] = sanitize_text_field($_POST['toc_headings'] ?? 'h2,h3');
        $options['sidebar_enabled'] = !empty($_POST['sidebar_enabled']);
        $options['navigation_enabled'] = !empty($_POST['navigation_enabled']);
        $options['print_button'] = !empty($_POST['print_button']);

        // Search
        $options['search_enabled'] = !empty($_POST['search_enabled']);
        $options['search_placeholder'] = sanitize_text_field($_POST['search_placeholder'] ?? '');
        $options['search_min_chars'] = max(1, intval($_POST['search_min_chars'] ?? 2));

        // Pro: Multiple KBs
        $options['multiple_kb_enabled'] = !empty($_POST['multiple_kb_enabled']);

        // Pro: Internal docs
        $options['internal_docs_enabled'] = !empty($_POST['internal_docs_enabled']);
        $options['internal_docs_roles'] = isset($_POST['internal_docs_roles']) 
            ? array_map('sanitize_text_field', (array) $_POST['internal_docs_roles']) 
            : ['administrator'];

        // Pro: Feedback
        $options['feedback_enabled'] = !empty($_POST['feedback_enabled']);
        $options['feedback_question'] = sanitize_text_field($_POST['feedback_question'] ?? '');

        // Pro: Related
        $options['related_enabled'] = !empty($_POST['related_enabled']);
        $options['related_count'] = max(1, intval($_POST['related_count'] ?? 3));

        // Pro: Analytics
        $options['analytics_enabled'] = !empty($_POST['analytics_enabled']);
        $options['analytics_email_report'] = !empty($_POST['analytics_email_report']);
        $options['analytics_email'] = sanitize_email($_POST['analytics_email'] ?? '');

        // Colors
        $options['primary_color'] = sanitize_hex_color($_POST['primary_color'] ?? '#0066ff');
        $options['category_icon_color'] = sanitize_hex_color($_POST['category_icon_color'] ?? '#0066ff');
        $options['link_color'] = sanitize_hex_color($_POST['link_color'] ?? '#0066ff');

        update_option(self::OPTION_NAME, $options);

        // Flush rewrite rules if slug changed
        if ($old_slug !== $options['docs_slug']) {
            delete_option('king_addons_docs_kb_rewrite_flushed');
        }

        wp_redirect(admin_url('admin.php?page=king-addons-docs-kb&saved=1'));
        exit;
    }

    /**
     * Gets icon SVG by name.
     *
     * @param string $name Icon name.
     * @return string
     */
    public static function get_icon_svg(string $name): string
    {
        $icons = [
            'book' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>',
            'lightbulb' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18h6"/><path d="M10 22h4"/><path d="M15.09 14c.18-.98.65-1.74 1.41-2.5A4.65 4.65 0 0 0 18 8 6 6 0 0 0 6 8c0 1 .23 2.23 1.5 3.5A4.61 4.61 0 0 1 8.91 14"/></svg>',
            'gear' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
            'rocket' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/><path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/><path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0"/><path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"/></svg>',
            'star' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
            'code' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>',
            'help' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
            'video' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>',
            'search' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>',
            'folder' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>',
            'arrow-right' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>',
            'arrow-left' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>',
            'print' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>',
        ];

        return $icons[$name] ?? $icons['book'];
    }
}
