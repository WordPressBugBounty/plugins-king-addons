<?php
/**
 * Docs & Knowledge Base Extension — v2.
 *
 * Full-featured documentation system for WordPress with an Apple-inspired,
 * premium Liquid-Glass design language.
 *
 * @package King_Addons
 */

namespace King_Addons;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main Docs & Knowledge Base class (singleton).
 */
final class Docs_KB
{
    /* ───────── Constants ───────── */

    private const OPTION_NAME = 'king_addons_docs_kb_options';
    public const POST_TYPE = 'kng_doc';
    public const TAXONOMY = 'kng_doc_category';
    public const TAG_TAXONOMY = 'kng_doc_tag';
    public const API_NAMESPACE = 'king-addons/v1';

    /* ───────── Singleton ───────── */

    private static ?Docs_KB $instance = null;

    /** @var array<string,mixed> */
    private array $options = [];

    public static function instance(): Docs_KB
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /* ───────── Bootstrap ───────── */

    public function __construct()
    {
        $this->options = $this->get_options();

        // Activation
        register_activation_hook(KING_ADDONS_PATH . 'king-addons.php', [$this, 'handle_activation']);

        // Init
        add_action('init', [$this, 'register_post_type']);
        add_action('init', [$this, 'register_taxonomy']);
        add_action('init', [$this, 'register_tag_taxonomy']);
        add_action('init', [$this, 'add_rewrite_rules']);

        // Admin
        add_filter('parent_file', [$this, 'fix_admin_parent_file']);
        add_filter('submenu_file', [$this, 'fix_admin_submenu_file']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('admin_post_king_addons_docs_kb_save', [$this, 'handle_save_settings']);
        add_filter('manage_' . self::POST_TYPE . '_posts_columns', [$this, 'add_admin_columns']);
        add_action('manage_' . self::POST_TYPE . '_posts_custom_column', [$this, 'render_admin_columns'], 10, 2);

        // Frontend
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_filter('template_include', [$this, 'template_loader']);

        // REST
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // AJAX – reactions & feedback
        add_action('wp_ajax_king_docs_reaction', [$this, 'ajax_save_reaction']);
        add_action('wp_ajax_nopriv_king_docs_reaction', [$this, 'ajax_save_reaction']);

        // Track views (Pro)
        add_action('wp', [$this, 'track_view']);
    }

    /* ═══════════════════════════════════════════
       ACTIVATION / OPTIONS
       ═══════════════════════════════════════════ */

    public function handle_activation(): void
    {
        if (!get_option(self::OPTION_NAME)) {
            add_option(self::OPTION_NAME, $this->get_default_options());
        }
        $this->register_post_type();
        $this->register_taxonomy();
        $this->register_tag_taxonomy();
        $this->add_rewrite_rules();
        flush_rewrite_rules();
    }

    /** @return array<string,mixed> */
    private function get_default_options(): array
    {
        return [
            // General
            'enabled'           => true,
            'docs_slug'         => 'docs',
            'main_page_id'      => 0,
            'docs_per_page'     => 12,
            'archive_title'     => __('How can we help?', 'king-addons'),
            'archive_subtitle'  => __('Find guides, tutorials and answers to your questions.', 'king-addons'),

            // Layout
            'layout'            => 'glass-card',  // glass-card | glass-list | glass-grid
            'columns'           => 3,
            'show_article_count' => true,
            'show_category_icon' => true,
            'dark_mode'         => 'auto', // light | dark | auto

            // Single article
            'toc_enabled'       => true,
            'toc_sticky'        => true,
            'toc_headings'      => 'h2,h3',
            'sidebar_enabled'   => true,
            'navigation_enabled' => true,
            'print_button'      => true,
            'reading_time'      => true,
            'social_share'      => true,
            'reactions_enabled' => true,

            // Search
            'search_enabled'    => true,
            'search_placeholder' => __('Search documentation…', 'king-addons'),
            'search_min_chars'  => 2,

            // Pro: Multiple KBs
            'multiple_kb_enabled' => false,

            // Pro: Internal docs
            'internal_docs_enabled' => false,
            'internal_docs_roles' => ['administrator'],

            // Pro: Feedback
            'feedback_enabled'  => false,
            'feedback_question' => __('Was this article helpful?', 'king-addons'),
            'feedback_thanks'   => __('Thank you for your feedback!', 'king-addons'),
            'empty_text'        => __('No articles yet', 'king-addons'),

            // Pro: Related
            'related_enabled'   => true,
            'related_count'     => 3,

            // Pro: Analytics
            'analytics_enabled' => false,
            'analytics_email_report' => false,
            'analytics_email'   => get_option('admin_email'),

            // Appearance
            'primary_color'     => '#0071e3',
            'category_icon_color' => '#0071e3',
            'link_color'        => '#0071e3',
        ];
    }

    /** @return array<string,mixed> */
    public function get_options(): array
    {
        $saved = get_option(self::OPTION_NAME, []);
        return wp_parse_args($saved, $this->get_default_options());
    }

    public function is_premium(): bool
    {
        return function_exists('king_addons_freemius')
            && king_addons_freemius()->can_use_premium_code__premium_only();
    }

    /* ═══════════════════════════════════════════
       POST TYPE / TAXONOMIES
       ═══════════════════════════════════════════ */

    public function register_post_type(): void
    {
        $slug = sanitize_title($this->options['docs_slug'] ?? 'docs');

        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name'               => __('Docs', 'king-addons'),
                'singular_name'      => __('Doc', 'king-addons'),
                'add_new'            => __('Add New Doc', 'king-addons'),
                'add_new_item'       => __('Add New Doc', 'king-addons'),
                'edit_item'          => __('Edit Doc', 'king-addons'),
                'new_item'           => __('New Doc', 'king-addons'),
                'view_item'          => __('View Doc', 'king-addons'),
                'search_items'       => __('Search Docs', 'king-addons'),
                'not_found'          => __('No docs found', 'king-addons'),
                'not_found_in_trash' => __('No docs found in Trash', 'king-addons'),
                'menu_name'          => __('Docs', 'king-addons'),
            ],
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => false,
            'show_in_rest'       => true,
            'query_var'          => true,
            'rewrite'            => ['slug' => $slug, 'with_front' => false],
            'capability_type'    => 'post',
            'has_archive'        => $slug,
            'hierarchical'       => false,
            'menu_position'      => 25,
            'menu_icon'          => 'dashicons-book-alt',
            'supports'           => ['title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'custom-fields', 'page-attributes'],
        ]);
    }

    public function register_taxonomy(): void
    {
        $slug = sanitize_title($this->options['docs_slug'] ?? 'docs');

        register_taxonomy(self::TAXONOMY, self::POST_TYPE, [
            'labels' => [
                'name'          => __('Doc Categories', 'king-addons'),
                'singular_name' => __('Doc Category', 'king-addons'),
                'search_items'  => __('Search Categories', 'king-addons'),
                'all_items'     => __('All Categories', 'king-addons'),
                'parent_item'   => __('Parent Category', 'king-addons'),
                'edit_item'     => __('Edit Category', 'king-addons'),
                'update_item'   => __('Update Category', 'king-addons'),
                'add_new_item'  => __('Add New Category', 'king-addons'),
                'menu_name'     => __('Categories', 'king-addons'),
            ],
            'hierarchical'   => true,
            'public'         => true,
            'show_ui'        => true,
            'show_admin_column' => true,
            'show_in_rest'   => true,
            'query_var'      => true,
            'rewrite'        => ['slug' => $slug . '/category', 'with_front' => false, 'hierarchical' => true],
        ]);

        // Custom meta fields
        add_action(self::TAXONOMY . '_add_form_fields', [$this, 'add_category_fields']);
        add_action(self::TAXONOMY . '_edit_form_fields', [$this, 'edit_category_fields'], 10, 2);
        add_action('created_' . self::TAXONOMY, [$this, 'save_category_fields']);
        add_action('edited_' . self::TAXONOMY, [$this, 'save_category_fields']);
    }

    public function register_tag_taxonomy(): void
    {
        $slug = sanitize_title($this->options['docs_slug'] ?? 'docs');

        register_taxonomy(self::TAG_TAXONOMY, self::POST_TYPE, [
            'labels' => [
                'name'          => __('Doc Tags', 'king-addons'),
                'singular_name' => __('Doc Tag', 'king-addons'),
                'search_items'  => __('Search Tags', 'king-addons'),
                'all_items'     => __('All Tags', 'king-addons'),
                'edit_item'     => __('Edit Tag', 'king-addons'),
                'update_item'   => __('Update Tag', 'king-addons'),
                'add_new_item'  => __('Add New Tag', 'king-addons'),
                'menu_name'     => __('Tags', 'king-addons'),
            ],
            'hierarchical' => false,
            'public'       => true,
            'show_ui'      => true,
            'show_admin_column' => true,
            'show_in_rest' => true,
            'rewrite'      => ['slug' => $slug . '/tag', 'with_front' => false],
        ]);
    }

    /* ── Category form fields ── */

    public function add_category_fields(): void
    {
        $icons = self::get_available_icons();
        ?>
        <div class="form-field">
            <label for="kng_doc_cat_icon"><?php esc_html_e('Category Icon', 'king-addons'); ?></label>
            <select name="kng_doc_cat_icon" id="kng_doc_cat_icon">
                <?php foreach ($icons as $value => $label): ?>
                    <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-field">
            <label for="kng_doc_cat_order"><?php esc_html_e('Order', 'king-addons'); ?></label>
            <input type="number" name="kng_doc_cat_order" id="kng_doc_cat_order" value="0" min="0">
        </div>
        <?php
    }

    public function edit_category_fields(\WP_Term $term): void
    {
        $icon  = get_term_meta($term->term_id, 'kng_doc_cat_icon', true) ?: 'book';
        $order = get_term_meta($term->term_id, 'kng_doc_cat_order', true) ?: 0;
        $icons = self::get_available_icons();
        ?>
        <tr class="form-field">
            <th><label for="kng_doc_cat_icon"><?php esc_html_e('Category Icon', 'king-addons'); ?></label></th>
            <td>
                <select name="kng_doc_cat_icon" id="kng_doc_cat_icon">
                    <?php foreach ($icons as $value => $label): ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($icon, $value); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr class="form-field">
            <th><label for="kng_doc_cat_order"><?php esc_html_e('Order', 'king-addons'); ?></label></th>
            <td><input type="number" name="kng_doc_cat_order" id="kng_doc_cat_order" value="<?php echo esc_attr($order); ?>" min="0"></td>
        </tr>
        <?php
    }

    public function save_category_fields(int $term_id): void
    {
        if (isset($_POST['kng_doc_cat_icon'])) {
            update_term_meta($term_id, 'kng_doc_cat_icon', sanitize_text_field($_POST['kng_doc_cat_icon']));
        }
        if (isset($_POST['kng_doc_cat_order'])) {
            update_term_meta($term_id, 'kng_doc_cat_order', intval($_POST['kng_doc_cat_order']));
        }
    }

    /* ═══════════════════════════════════════════
       REWRITE RULES
       ═══════════════════════════════════════════ */

    public function add_rewrite_rules(): void
    {
        $slug = sanitize_title($this->options['docs_slug'] ?? 'docs');

        add_rewrite_rule('^' . $slug . '/?$', 'index.php?post_type=' . self::POST_TYPE, 'top');
        add_rewrite_rule('^' . $slug . '/category/([^/]+)/?$', 'index.php?' . self::TAXONOMY . '=$matches[1]', 'top');
        add_rewrite_rule('^' . $slug . '/([^/]+)/?$', 'index.php?' . self::POST_TYPE . '=$matches[1]', 'top');

        if (get_option('king_addons_docs_kb_rewrite_flushed') !== $slug) {
            flush_rewrite_rules();
            update_option('king_addons_docs_kb_rewrite_flushed', $slug);
        }
    }

    /* ═══════════════════════════════════════════
       ADMIN COLUMNS
       ═══════════════════════════════════════════ */

    public function add_admin_columns(array $columns): array
    {
        $new = [];
        foreach ($columns as $k => $v) {
            $new[$k] = $v;
            if ($k === 'title') {
                $new['doc_category'] = __('Category', 'king-addons');
            }
        }
        $new['doc_views']    = __('Views', 'king-addons');
        $new['doc_reactions'] = __('Reactions', 'king-addons');
        return $new;
    }

    public function render_admin_columns(string $column, int $post_id): void
    {
        switch ($column) {
            case 'doc_category':
                $terms = get_the_terms($post_id, self::TAXONOMY);
                echo ($terms && !is_wp_error($terms))
                    ? esc_html(implode(', ', wp_list_pluck($terms, 'name')))
                    : '—';
                break;

            case 'doc_views':
                echo esc_html(number_format_i18n(get_post_meta($post_id, '_kng_doc_views', true) ?: 0));
                break;

            case 'doc_reactions':
                $happy   = get_post_meta($post_id, '_kng_doc_react_happy', true) ?: 0;
                $neutral = get_post_meta($post_id, '_kng_doc_react_neutral', true) ?: 0;
                $sad     = get_post_meta($post_id, '_kng_doc_react_sad', true) ?: 0;
                echo '<span style="color:#34c759">😊 ' . esc_html($happy) . '</span> ';
                echo '<span style="color:#ff9f0a">😐 ' . esc_html($neutral) . '</span> ';
                echo '<span style="color:#ff3b30">😞 ' . esc_html($sad) . '</span>';
                break;
        }
    }

    /* ═══════════════════════════════════════════
       ASSETS
       ═══════════════════════════════════════════ */

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
    }

    public function enqueue_frontend_assets(): void
    {
        if (!$this->options['enabled']) {
            return;
        }

        if (
            !is_post_type_archive(self::POST_TYPE)
            && !is_singular(self::POST_TYPE)
            && !is_tax(self::TAXONOMY)
            && !is_tax(self::TAG_TAXONOMY)
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

        wp_localize_script('king-addons-docs-kb', 'kingDocsKB', [
            'restUrl'       => rest_url(self::API_NAMESPACE . '/docs/'),
            'ajaxUrl'       => admin_url('admin-ajax.php'),
            'nonce'         => wp_create_nonce('wp_rest'),
            'reactionNonce' => wp_create_nonce('king_docs_reaction'),
            'searchEnabled' => !empty($this->options['search_enabled']),
            'searchMinChars' => intval($this->options['search_min_chars'] ?? 2),
            'tocEnabled'    => !empty($this->options['toc_enabled']),
            'tocSticky'     => !empty($this->options['toc_sticky']),
            'tocHeadings'   => $this->options['toc_headings'] ?? 'h2,h3',
            'reactionsEnabled' => !empty($this->options['reactions_enabled']),
            'darkMode'      => $this->options['dark_mode'] ?? 'auto',
            'i18n' => [
                'searchPlaceholder' => $this->options['search_placeholder'] ?? __('Search documentation…', 'king-addons'),
                'noResults'         => __('No results found', 'king-addons'),
                'searching'         => __('Searching…', 'king-addons'),
                'tocTitle'          => __('On this page', 'king-addons'),
                'reactionThanks'    => __('Thanks for your feedback!', 'king-addons'),
                'copied'            => __('Link copied!', 'king-addons'),
            ],
        ]);

        // Inline custom-property overrides
        wp_add_inline_style('king-addons-docs-kb', $this->get_custom_css());
    }

    private function get_custom_css(): string
    {
        $o = $this->options;
        $primary = sanitize_hex_color($o['primary_color'] ?? '#0071e3');
        $icon    = sanitize_hex_color($o['category_icon_color'] ?? '#0071e3');
        $link    = sanitize_hex_color($o['link_color'] ?? '#0071e3');

        return ":root{--kng-docs-primary:{$primary};--kng-docs-icon-color:{$icon};--kng-docs-link-color:{$link};}";
    }

    private function is_docs_main_page(): bool
    {
        $id = intval($this->options['main_page_id'] ?? 0);
        return $id > 0 && is_page($id);
    }

    /* ═══════════════════════════════════════════
       TEMPLATE LOADER
       ═══════════════════════════════════════════ */

    public function template_loader(string $template): string
    {
        if (!$this->options['enabled']) {
            return $template;
        }

        $base = __DIR__ . '/templates/';

        if ($this->is_docs_main_page() || is_post_type_archive(self::POST_TYPE)) {
            $t = $base . 'archive-docs.php';
            return file_exists($t) ? $t : $template;
        }

        if (is_tax(self::TAXONOMY) || is_tax(self::TAG_TAXONOMY)) {
            $t = $base . 'taxonomy-docs.php';
            return file_exists($t) ? $t : $template;
        }

        if (is_singular(self::POST_TYPE)) {
            $t = $base . 'single-doc.php';
            return file_exists($t) ? $t : $template;
        }

        return $template;
    }

    /* ═══════════════════════════════════════════
       REST API
       ═══════════════════════════════════════════ */

    public function register_rest_routes(): void
    {
        register_rest_route(self::API_NAMESPACE, '/docs/search', [
            'methods'  => 'GET',
            'callback' => [$this, 'rest_search'],
            'permission_callback' => '__return_true',
            'args' => [
                'q'  => ['required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
                'cat' => ['required' => false, 'type' => 'integer', 'default' => 0],
            ],
        ]);

        register_rest_route(self::API_NAMESPACE, '/docs/categories', [
            'methods'  => 'GET',
            'callback' => [$this, 'rest_get_categories'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route(self::API_NAMESPACE, '/docs/category/(?P<id>\d+)', [
            'methods'  => 'GET',
            'callback' => [$this, 'rest_get_category_articles'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function rest_search(\WP_REST_Request $request): \WP_REST_Response
    {
        $q   = $request->get_param('q');
        $cat = $request->get_param('cat');

        if (strlen($q) < intval($this->options['search_min_chars'] ?? 2)) {
            return new \WP_REST_Response(['results' => []], 200);
        }

        $args = [
            'post_type'      => self::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => 10,
            's'              => $q,
            'orderby'        => 'relevance',
        ];

        if ($cat) {
            $args['tax_query'] = [['taxonomy' => self::TAXONOMY, 'field' => 'term_id', 'terms' => $cat]];
        }

        // Pro: hide internal docs from non-logged-in
        if ($this->is_premium() && !empty($this->options['internal_docs_enabled']) && !is_user_logged_in()) {
            $args['meta_query'] = [['key' => '_kng_doc_visibility', 'value' => 'public']];
        }

        $wp = new \WP_Query($args);
        $results = [];

        foreach ($wp->posts as $post) {
            $cats = get_the_terms($post->ID, self::TAXONOMY);
            $cat_name = ($cats && !is_wp_error($cats)) ? $cats[0]->name : '';
            $excerpt = $post->post_excerpt ?: wp_trim_words($post->post_content, 20, '…');

            $results[] = [
                'id'       => $post->ID,
                'title'    => $post->post_title,
                'h_title'  => $this->highlight($post->post_title, $q),
                'url'      => get_permalink($post->ID),
                'excerpt'  => $excerpt,
                'h_excerpt' => $this->highlight($excerpt, $q),
                'category' => $cat_name,
                'date'     => get_the_date('', $post),
                'reading_time'  => $this->estimate_reading_time($post->post_content),
            ];
        }

        // Pro: analytics log
        if ($this->is_premium() && !empty($this->options['analytics_enabled'])) {
            $this->log_search($q, count($results));
        }

        return new \WP_REST_Response(['results' => $results], 200);
    }

    public function rest_get_categories(): \WP_REST_Response
    {
        $cats = get_terms([
            'taxonomy'   => self::TAXONOMY,
            'hide_empty' => true,
            'orderby'    => 'meta_value_num',
            'meta_key'   => 'kng_doc_cat_order',
            'order'      => 'ASC',
        ]);

        $out = [];
        foreach ($cats as $c) {
            if (is_wp_error($c)) continue;
            $out[] = [
                'id'    => $c->term_id,
                'name'  => $c->name,
                'slug'  => $c->slug,
                'desc'  => $c->description,
                'count' => $c->count,
                'icon'  => get_term_meta($c->term_id, 'kng_doc_cat_icon', true) ?: 'book',
                'url'   => get_term_link($c),
            ];
        }

        return new \WP_REST_Response($out, 200);
    }

    public function rest_get_category_articles(\WP_REST_Request $request): \WP_REST_Response
    {
        $cat_id = $request->get_param('id');

        $wp = new \WP_Query([
            'post_type'      => self::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'tax_query'      => [['taxonomy' => self::TAXONOMY, 'field' => 'term_id', 'terms' => $cat_id]],
            'orderby'        => 'menu_order title',
            'order'          => 'ASC',
        ]);

        $out = [];
        foreach ($wp->posts as $p) {
            $out[] = [
                'id'      => $p->ID,
                'title'   => $p->post_title,
                'url'     => get_permalink($p->ID),
                'excerpt' => $p->post_excerpt ?: wp_trim_words($p->post_content, 15, '…'),
            ];
        }

        return new \WP_REST_Response($out, 200);
    }

    /* ═══════════════════════════════════════════
       REACTIONS (AJAX)
       ═══════════════════════════════════════════ */

    public function ajax_save_reaction(): void
    {
        check_ajax_referer('king_docs_reaction', 'nonce');

        $post_id  = intval($_POST['post_id'] ?? 0);
        $reaction = sanitize_text_field($_POST['reaction'] ?? '');

        if (!$post_id || !in_array($reaction, ['happy', 'neutral', 'sad'], true)) {
            wp_send_json_error('Invalid');
        }

        $cookie = 'kng_docs_reaction_' . $post_id;
        if (isset($_COOKIE[$cookie])) {
            wp_send_json_error('Already voted');
        }

        $key = '_kng_doc_react_' . $reaction;
        $count = get_post_meta($post_id, $key, true) ?: 0;
        update_post_meta($post_id, $key, $count + 1);

        setcookie($cookie, $reaction, time() + YEAR_IN_SECONDS, '/');

        wp_send_json_success([
            'happy'   => get_post_meta($post_id, '_kng_doc_react_happy', true) ?: 0,
            'neutral' => get_post_meta($post_id, '_kng_doc_react_neutral', true) ?: 0,
            'sad'     => get_post_meta($post_id, '_kng_doc_react_sad', true) ?: 0,
        ]);
    }

    /* ═══════════════════════════════════════════
       VIEW TRACKING (Pro)
       ═══════════════════════════════════════════ */

    public function track_view(): void
    {
        if (!is_singular(self::POST_TYPE)) return;
        if (empty($this->options['analytics_enabled']) || !$this->is_premium()) return;

        $id = get_the_ID();
        update_post_meta($id, '_kng_doc_views', (get_post_meta($id, '_kng_doc_views', true) ?: 0) + 1);

        $ck = 'kng_doc_viewed_' . $id;
        if (!isset($_COOKIE[$ck])) {
            update_post_meta($id, '_kng_doc_unique_views', (get_post_meta($id, '_kng_doc_unique_views', true) ?: 0) + 1);
            setcookie($ck, '1', time() + DAY_IN_SECONDS, '/');
        }
    }

    /* ═══════════════════════════════════════════
       HELPERS
       ═══════════════════════════════════════════ */

    private function highlight(string $text, string $q): string
    {
        if (!$q) return $text;
        foreach (explode(' ', $q) as $w) {
            if (strlen($w) >= 2) {
                $text = preg_replace('/(' . preg_quote($w, '/') . ')/iu', '<mark>$1</mark>', $text);
            }
        }
        return $text;
    }

    private function log_search(string $q, int $cnt): void
    {
        $logs = get_option('king_addons_docs_search_logs', []);
        $logs[] = ['query' => $q, 'results' => $cnt, 'ts' => current_time('mysql')];
        if (count($logs) > 1000) $logs = array_slice($logs, -1000);
        update_option('king_addons_docs_search_logs', $logs);
    }

    public static function estimate_reading_time(string $content): string
    {
        $min = max(1, (int)ceil(str_word_count(strip_tags($content)) / 200));
        return sprintf(_n('%d min read', '%d min read', $min, 'king-addons'), $min);
    }

    /**
     * Get all docs grouped by category — used by archive template.
     *
     * @return array
     */
    public function get_categories_with_docs(int $limit = 5): array
    {
        $cats = get_terms([
            'taxonomy'   => self::TAXONOMY,
            'hide_empty' => true,
            'parent'     => 0,
            'orderby'    => 'meta_value_num',
            'meta_key'   => 'kng_doc_cat_order',
            'order'      => 'ASC',
        ]);

        $out = [];
        foreach ($cats as $c) {
            if (is_wp_error($c)) continue;

            $docs = get_posts([
                'post_type'      => self::POST_TYPE,
                'post_status'    => 'publish',
                'posts_per_page' => $limit,
                'tax_query'      => [['taxonomy' => self::TAXONOMY, 'field' => 'term_id', 'terms' => $c->term_id]],
                'orderby'        => 'menu_order title',
                'order'          => 'ASC',
            ]);

            $doc_items = [];
            foreach ($docs as $d) {
                $doc_items[] = ['id' => $d->ID, 'title' => $d->post_title, 'url' => get_permalink($d->ID)];
            }

            $subcats = get_terms([
                'taxonomy'   => self::TAXONOMY,
                'hide_empty' => true,
                'parent'     => $c->term_id,
                'orderby'    => 'meta_value_num',
                'meta_key'   => 'kng_doc_cat_order',
                'order'      => 'ASC',
            ]);

            $sub_items = [];
            foreach ($subcats as $sc) {
                if (is_wp_error($sc)) continue;
                $sub_items[] = [
                    'id' => $sc->term_id, 'name' => $sc->name, 'count' => $sc->count, 'url' => get_term_link($sc),
                ];
            }

            $out[] = [
                'id'          => $c->term_id,
                'name'        => $c->name,
                'slug'        => $c->slug,
                'description' => $c->description,
                'count'       => $c->count,
                'icon'        => get_term_meta($c->term_id, 'kng_doc_cat_icon', true) ?: 'book',
                'url'         => get_term_link($c),
                'docs'        => $doc_items,
                'subcategories' => $sub_items,
            ];
        }

        return $out;
    }

    public function get_related_docs(int $post_id, int $count = 3): array
    {
        $cats = wp_get_post_terms($post_id, self::TAXONOMY, ['fields' => 'ids']);
        if (empty($cats) || is_wp_error($cats)) return [];

        $q = new \WP_Query([
            'post_type'      => self::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => $count,
            'post__not_in'   => [$post_id],
            'tax_query'      => [['taxonomy' => self::TAXONOMY, 'field' => 'term_id', 'terms' => $cats]],
            'orderby'        => 'rand',
        ]);

        $out = [];
        foreach ($q->posts as $p) {
            $out[] = [
                'id'      => $p->ID,
                'title'   => $p->post_title,
                'url'     => get_permalink($p->ID),
                'excerpt' => $p->post_excerpt ?: wp_trim_words($p->post_content, 15, '…'),
            ];
        }
        return $out;
    }

    public function get_article_navigation(int $post_id): array
    {
        $cats = wp_get_post_terms($post_id, self::TAXONOMY, ['fields' => 'ids']);
        if (empty($cats) || is_wp_error($cats)) return ['prev' => null, 'next' => null];

        $all = get_posts([
            'post_type'      => self::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'tax_query'      => [['taxonomy' => self::TAXONOMY, 'field' => 'term_id', 'terms' => $cats[0]]],
            'orderby'        => 'menu_order title',
            'order'          => 'ASC',
            'fields'         => 'ids',
        ]);

        $idx = array_search($post_id, $all);
        $prev = $next = null;

        if ($idx !== false) {
            if ($idx > 0) {
                $pid = $all[$idx - 1];
                $prev = ['id' => $pid, 'title' => get_the_title($pid), 'url' => get_permalink($pid)];
            }
            if ($idx < count($all) - 1) {
                $nid = $all[$idx + 1];
                $next = ['id' => $nid, 'title' => get_the_title($nid), 'url' => get_permalink($nid)];
            }
        }

        return ['prev' => $prev, 'next' => $next];
    }

    /* ═══════════════════════════════════════════
       ADMIN PAGE
       ═══════════════════════════════════════════ */

    /**
     * Highlight King Addons parent menu when editing docs.
     */
    public function fix_admin_parent_file(string $parent_file): string
    {
        $screen = get_current_screen();
        if ($screen && ($screen->post_type === self::POST_TYPE || in_array($screen->taxonomy, [self::TAXONOMY, self::TAG_TAXONOMY], true))) {
            return 'king-addons';
        }
        return $parent_file;
    }

    /**
     * Highlight correct submenu item when editing docs.
     */
    public function fix_admin_submenu_file(?string $submenu_file): ?string
    {
        $screen = get_current_screen();
        if (!$screen) return $submenu_file;

        // Keep the single “Builder” page highlighted for any Docs KB admin screens.
        if (
            ($screen->post_type === self::POST_TYPE)
            || (!empty($screen->taxonomy) && in_array($screen->taxonomy, [self::TAXONOMY, self::TAG_TAXONOMY], true))
        ) {
            return 'king-addons-docs-kb';
        }
        return $submenu_file;
    }

    public function render_admin_page(): void
    {
        if (!current_user_can('manage_options')) return;

        $this->options = $this->get_options();
        $options     = $this->options;
        $is_premium  = $this->is_premium();

        include __DIR__ . '/templates/admin-page.php';
    }

    public function handle_save_settings(): void
    {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        check_admin_referer('king_addons_docs_kb_save', 'king_docs_kb_nonce');

        $old_slug = $this->options['docs_slug'] ?? 'docs';
        $o = [];

        // General
        $o['enabled']       = !empty($_POST['enabled']);
        $o['docs_slug']     = sanitize_title($_POST['docs_slug'] ?? 'docs');
        $o['main_page_id']  = intval($_POST['main_page_id'] ?? 0);
        $o['docs_per_page'] = max(1, intval($_POST['docs_per_page'] ?? 12));
        $o['archive_title']    = sanitize_text_field($_POST['archive_title'] ?? '');
        $o['archive_subtitle'] = sanitize_text_field($_POST['archive_subtitle'] ?? '');

        // Layout
        $valid_layouts = ['glass-card', 'glass-list', 'glass-grid'];
        $o['layout']   = in_array($_POST['layout'] ?? 'glass-card', $valid_layouts) ? sanitize_text_field($_POST['layout']) : 'glass-card';
        $o['columns']  = max(1, min(4, intval($_POST['columns'] ?? 3)));
        $o['show_article_count']  = !empty($_POST['show_article_count']);
        $o['show_category_icon']  = !empty($_POST['show_category_icon']);
        $o['dark_mode'] = in_array($_POST['dark_mode'] ?? 'auto', ['light', 'dark', 'auto']) ? sanitize_text_field($_POST['dark_mode']) : 'auto';

        // Single
        $o['toc_enabled']       = !empty($_POST['toc_enabled']);
        $o['toc_sticky']        = !empty($_POST['toc_sticky']);
        $o['toc_headings']      = sanitize_text_field($_POST['toc_headings'] ?? 'h2,h3');
        $o['sidebar_enabled']   = !empty($_POST['sidebar_enabled']);
        $o['navigation_enabled'] = !empty($_POST['navigation_enabled']);
        $o['print_button']      = !empty($_POST['print_button']);
        $o['reading_time']      = !empty($_POST['reading_time']);
        $o['social_share']      = !empty($_POST['social_share']);
        $o['reactions_enabled'] = !empty($_POST['reactions_enabled']);

        // Search
        $o['search_enabled']     = !empty($_POST['search_enabled']);
        $o['search_placeholder'] = sanitize_text_field($_POST['search_placeholder'] ?? '');
        $o['search_min_chars']   = max(1, intval($_POST['search_min_chars'] ?? 2));

        // Pro
        $o['multiple_kb_enabled']   = !empty($_POST['multiple_kb_enabled']);
        $o['internal_docs_enabled'] = !empty($_POST['internal_docs_enabled']);
        $o['internal_docs_roles']   = isset($_POST['internal_docs_roles']) ? array_map('sanitize_text_field', (array)$_POST['internal_docs_roles']) : ['administrator'];
        $o['feedback_enabled']      = !empty($_POST['feedback_enabled']);
        $o['feedback_question']     = sanitize_text_field($_POST['feedback_question'] ?? '');
        $o['feedback_thanks']       = sanitize_text_field($_POST['feedback_thanks'] ?? '');
        $o['empty_text']            = sanitize_text_field($_POST['empty_text'] ?? '');
        $o['related_enabled']       = !empty($_POST['related_enabled']);
        $o['related_count']         = max(1, intval($_POST['related_count'] ?? 3));
        $o['analytics_enabled']     = !empty($_POST['analytics_enabled']);
        $o['analytics_email_report'] = !empty($_POST['analytics_email_report']);
        $o['analytics_email']       = sanitize_email($_POST['analytics_email'] ?? '');

        // Colors
        $o['primary_color']       = sanitize_hex_color($_POST['primary_color'] ?? '#0071e3');
        $o['category_icon_color'] = sanitize_hex_color($_POST['category_icon_color'] ?? '#0071e3');
        $o['link_color']          = sanitize_hex_color($_POST['link_color'] ?? '#0071e3');

        update_option(self::OPTION_NAME, $o);

        if ($old_slug !== $o['docs_slug']) {
            delete_option('king_addons_docs_kb_rewrite_flushed');
        }

        wp_redirect(admin_url('admin.php?page=king-addons-docs-kb&saved=1'));
        exit;
    }

    /* ═══════════════════════════════════════════
       ICONS
       ═══════════════════════════════════════════ */

    /** @return array<string,string> */
    public static function get_available_icons(): array
    {
        return [
            'book'      => 'Book',
            'lightbulb' => 'Lightbulb',
            'gear'      => 'Gear',
            'rocket'    => 'Rocket',
            'star'      => 'Star',
            'code'      => 'Code',
            'help'      => 'Help',
            'video'     => 'Video',
            'layers'    => 'Layers',
            'shield'    => 'Shield',
            'palette'   => 'Palette',
            'globe'     => 'Globe',
        ];
    }

    public static function get_icon_svg(string $name): string
    {
        $icons = [
            'book'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>',
            'book-open'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>',
            'home'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
            'calendar'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
            'clock'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
            'eye'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>',
            'list'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>',
            'file-text'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M16 13H8"/><path d="M16 17H8"/><path d="M10 9H8"/></svg>',
            'printer'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>',
            'lightbulb'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18h6"/><path d="M10 22h4"/><path d="M15.09 14c.18-.98.65-1.74 1.41-2.5A4.65 4.65 0 0 0 18 8 6 6 0 0 0 6 8c0 1 .23 2.23 1.5 3.5A4.61 4.61 0 0 1 8.91 14"/></svg>',
            'gear'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M12 1v2m0 18v2M4.22 4.22l1.42 1.42m12.72 12.72 1.42 1.42M1 12h2m18 0h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>',
            'rocket'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/><path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/><path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0"/><path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"/></svg>',
            'star'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
            'code'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>',
            'help'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
            'video'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>',
            'layers'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>',
            'shield'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
            'palette'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="13.5" cy="6.5" r="0.5" fill="currentColor"/><circle cx="17.5" cy="10.5" r="0.5" fill="currentColor"/><circle cx="8.5" cy="7.5" r="0.5" fill="currentColor"/><circle cx="6.5" cy="12.5" r="0.5" fill="currentColor"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"/></svg>',
            'globe'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>',
            'search'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>',
            'folder'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>',
            'arrow-right' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>',
            'arrow-left'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>',
            'print'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>',
            'share'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>',
            'link'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>',
        ];

        return $icons[$name] ?? $icons['book'];
    }
}
