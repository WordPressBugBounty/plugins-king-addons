<?php
/**
 * Custom Code Manager Extension
 *
 * Manage custom CSS, JS, and HTML snippets with advanced targeting rules.
 *
 * @package King_Addons
 */

namespace King_Addons;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Custom Code Manager class.
 */
final class Custom_Code_Manager
{
    /**
     * Instance.
     *
     * @var Custom_Code_Manager|null
     */
    private static ?Custom_Code_Manager $instance = null;

    /**
     * Post type name.
     */
    public const POST_TYPE = 'kng_custom_code';

    /**
     * Meta prefix.
     */
    public const META_PREFIX = '_kng_cc_';

    /**
     * Free snippets limit.
     */
    public const FREE_LIMIT = 10;

    /**
     * Cached snippets for current request.
     *
     * @var array|null
     */
    private ?array $cached_snippets = null;

    /**
     * Rule engine cache.
     *
     * @var array
     */
    private array $rule_cache = [];

    /**
     * Debug log.
     *
     * @var array
     */
    private array $debug_log = [];

    /**
     * Gets instance.
     *
     * @return Custom_Code_Manager
     */
    public static function getInstance(): Custom_Code_Manager
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Check if Pro version is active.
     *
     * @return bool
     */
    public static function hasPro(): bool
    {
        return function_exists('king_addons_freemius') && king_addons_freemius()->can_use_premium_code();
    }

    /**
     * Constructor.
     */
    private function __construct()
    {
        $this->init_hooks();
    }

    /**
     * Initializes hooks.
     *
     * @return void
     */
    private function init_hooks(): void
    {
        // Post type registration
        add_action('init', [$this, 'register_post_type']);

        // Admin hooks
        add_action('admin_menu', [$this, 'add_admin_menu'], 25);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        // AJAX handlers
        add_action('wp_ajax_kng_cc_save_snippet', [$this, 'ajax_save_snippet']);
        add_action('wp_ajax_kng_cc_delete_snippet', [$this, 'ajax_delete_snippet']);
        add_action('wp_ajax_kng_cc_toggle_snippet', [$this, 'ajax_toggle_snippet']);
        add_action('wp_ajax_kng_cc_duplicate_snippet', [$this, 'ajax_duplicate_snippet']);
        add_action('wp_ajax_kng_cc_export_snippet', [$this, 'ajax_export_snippet']);
        add_action('wp_ajax_kng_cc_export_all', [$this, 'ajax_export_all']);
        add_action('wp_ajax_kng_cc_import', [$this, 'ajax_import']);
        add_action('wp_ajax_kng_cc_search_content', [$this, 'ajax_search_content']);
        add_action('wp_ajax_kng_cc_get_snippet', [$this, 'ajax_get_snippet']);
        add_action('wp_ajax_kng_cc_bulk_action', [$this, 'ajax_bulk_action']);

        // Frontend injection
        add_action('wp_head', [$this, 'inject_head'], 1);
        add_action('wp_footer', [$this, 'inject_footer'], 99);
        add_action('wp_body_open', [$this, 'inject_body_open'], 1);

        // Script attributes filter
        add_filter('script_loader_tag', [$this, 'modify_script_tag'], 10, 3);

        // Debug output for admins
        if ($this->is_debug_mode() && current_user_can('manage_options')) {
            add_action('wp_footer', [$this, 'output_debug_log'], 999);
        }
    }

    /**
     * Registers custom post type.
     *
     * @return void
     */
    public function register_post_type(): void
    {
        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name' => __('Code Snippets', 'king-addons'),
                'singular_name' => __('Code Snippet', 'king-addons'),
            ],
            'public' => false,
            'show_ui' => false,
            'show_in_menu' => false,
            'supports' => ['title'],
            'capability_type' => 'post',
            'map_meta_cap' => true,
        ]);
    }

    /**
     * Adds admin menu.
     *
     * @return void
     */
    public function add_admin_menu(): void
    {
        add_menu_page(
            __('Custom Code', 'king-addons'),
            __('Custom Code', 'king-addons'),
            'manage_options',
            'king-addons-custom-code',
            [$this, 'render_admin_page'],
            'dashicons-editor-code',
            54.7
        );
    }

    /**
     * Renders admin page.
     *
     * @return void
     */
    public function render_admin_page(): void
    {
        $view = isset($_GET['view']) ? sanitize_key($_GET['view']) : 'list';
        $snippet_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        switch ($view) {
            case 'edit':
            case 'new':
                $this->render_editor_page($snippet_id);
                break;
            case 'settings':
                $this->render_settings_page();
                break;
            case 'import-export':
                $this->render_import_export_page();
                break;
            default:
                $this->render_list_page();
                break;
        }
    }

    /**
     * Renders list page.
     *
     * @return void
     */
    private function render_list_page(): void
    {
        $snippets = $this->get_all_snippets();
        $snippet_count = count($snippets);
        $has_pro = self::hasPro();
        $at_limit = !$has_pro && $snippet_count >= self::FREE_LIMIT;

        include __DIR__ . '/templates/admin-list.php';
    }

    /**
     * Renders editor page.
     *
     * @param int $snippet_id Snippet ID.
     * @return void
     */
    private function render_editor_page(int $snippet_id = 0): void
    {
        $snippet = null;
        $is_new = $snippet_id === 0;

        if (!$is_new) {
            $snippet = $this->get_snippet($snippet_id);
            if (!$snippet) {
                wp_die(__('Snippet not found.', 'king-addons'));
            }
        }

        $has_pro = self::hasPro();
        $defaults = $this->get_default_snippet();
        $config = $snippet ? array_merge($defaults, $snippet) : $defaults;

        include __DIR__ . '/templates/admin-editor.php';
    }

    /**
     * Renders settings page.
     *
     * @return void
     */
    private function render_settings_page(): void
    {
        $settings = $this->get_settings();
        $has_pro = self::hasPro();

        include __DIR__ . '/templates/admin-settings.php';
    }

    /**
     * Renders import/export page.
     *
     * @return void
     */
    private function render_import_export_page(): void
    {
        $has_pro = self::hasPro();

        include __DIR__ . '/templates/admin-import-export.php';
    }

    /**
     * Enqueues admin assets.
     *
     * @param string $hook Page hook.
     * @return void
     */
    public function enqueue_admin_assets(string $hook): void
    {
        if (strpos($hook, 'king-addons-custom-code') === false) {
            return;
        }

        // CodeMirror
        wp_enqueue_code_editor(['type' => 'text/css']);
        wp_enqueue_script('wp-theme-plugin-editor');
        wp_enqueue_style('wp-codemirror');

        // Admin styles
        wp_enqueue_style(
            'king-addons-cc-admin',
            KING_ADDONS_URL . 'includes/extensions/Custom_Code_Manager/assets/admin.css',
            [],
            KING_ADDONS_VERSION
        );

        // Admin script
        wp_enqueue_script(
            'king-addons-cc-admin',
            KING_ADDONS_URL . 'includes/extensions/Custom_Code_Manager/assets/admin.js',
            ['jquery', 'wp-theme-plugin-editor', 'code-editor'],
            KING_ADDONS_VERSION,
            true
        );

        wp_localize_script('king-addons-cc-admin', 'kngCCAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('kng_cc_admin'),
            'hasPro' => self::hasPro(),
            'freeLimit' => self::FREE_LIMIT,
            'strings' => [
                'confirmDelete' => __('Are you sure you want to delete this snippet?', 'king-addons'),
                'confirmBulkDelete' => __('Are you sure you want to delete the selected snippets?', 'king-addons'),
                'saving' => __('Saving...', 'king-addons'),
                'saved' => __('Saved!', 'king-addons'),
                'error' => __('Error occurred', 'king-addons'),
                'selectSnippets' => __('Please select at least one snippet', 'king-addons'),
                'duplicated' => __('Snippet duplicated successfully', 'king-addons'),
                'proRequired' => __('This feature requires Pro version', 'king-addons'),
                'limitReached' => __('Free version limit reached. Upgrade to Pro for unlimited snippets.', 'king-addons'),
                'copied' => __('Copied!', 'king-addons'),
                'exportReady' => __('Export ready', 'king-addons'),
                'importSuccess' => __('Import successful', 'king-addons'),
                'invalidFile' => __('Invalid file format', 'king-addons'),
                'noResults' => __('No results found', 'king-addons'),
            ],
            'codeMirrorSettings' => [
                'css' => wp_enqueue_code_editor(['type' => 'text/css']),
                'javascript' => wp_enqueue_code_editor(['type' => 'application/javascript']),
                'html' => wp_enqueue_code_editor(['type' => 'text/html']),
            ],
        ]);
    }

    /**
     * Gets default snippet config.
     *
     * @return array
     */
    private function get_default_snippet(): array
    {
        return [
            'id' => 0,
            'title' => '',
            'code' => '',
            'type' => 'css',
            'status' => 'enabled',
            'location' => 'head',
            'custom_hook' => '',
            'priority' => 10,
            'js_load_mode' => 'inline',
            'js_defer' => false,
            'js_async' => false,
            'js_module' => false,
            'js_dom_ready' => false,
            'scope_mode' => 'global',
            'rules' => [],
            'match_mode' => 'any',
            'group_id' => 0,
            'notes' => '',
            'created' => '',
            'modified' => '',
            'author' => 0,
        ];
    }

    /**
     * Gets all snippets.
     *
     * @param array $args Query args.
     * @return array
     */
    public function get_all_snippets(array $args = []): array
    {
        $defaults = [
            'post_type' => self::POST_TYPE,
            'posts_per_page' => -1,
            'post_status' => 'any',
            'orderby' => 'menu_order',
            'order' => 'ASC',
        ];

        $query_args = array_merge($defaults, $args);
        $posts = get_posts($query_args);
        $snippets = [];

        foreach ($posts as $post) {
            $snippets[] = $this->post_to_snippet($post);
        }

        return $snippets;
    }

    /**
     * Gets single snippet.
     *
     * @param int $id Snippet ID.
     * @return array|null
     */
    public function get_snippet(int $id): ?array
    {
        $post = get_post($id);

        if (!$post || $post->post_type !== self::POST_TYPE) {
            return null;
        }

        return $this->post_to_snippet($post);
    }

    /**
     * Converts post to snippet array.
     *
     * @param \WP_Post $post Post object.
     * @return array
     */
    private function post_to_snippet(\WP_Post $post): array
    {
        $meta = get_post_meta($post->ID);

        return [
            'id' => $post->ID,
            'title' => $post->post_title,
            'code' => $post->post_content,
            'type' => $this->get_meta($meta, 'type', 'css'),
            'status' => $post->post_status === 'publish' ? 'enabled' : 'disabled',
            'location' => $this->get_meta($meta, 'location', 'head'),
            'custom_hook' => $this->get_meta($meta, 'custom_hook', ''),
            'priority' => (int)$this->get_meta($meta, 'priority', 10),
            'js_load_mode' => $this->get_meta($meta, 'js_load_mode', 'inline'),
            'js_defer' => (bool)$this->get_meta($meta, 'js_defer', false),
            'js_async' => (bool)$this->get_meta($meta, 'js_async', false),
            'js_module' => (bool)$this->get_meta($meta, 'js_module', false),
            'js_dom_ready' => (bool)$this->get_meta($meta, 'js_dom_ready', false),
            'scope_mode' => $this->get_meta($meta, 'scope_mode', 'global'),
            'rules' => json_decode($this->get_meta($meta, 'rules', '[]'), true) ?: [],
            'match_mode' => $this->get_meta($meta, 'match_mode', 'any'),
            'group_id' => (int)$this->get_meta($meta, 'group_id', 0),
            'notes' => $this->get_meta($meta, 'notes', ''),
            'created' => $post->post_date,
            'modified' => $post->post_modified,
            'author' => $post->post_author,
        ];
    }

    /**
     * Gets meta value with prefix.
     *
     * @param array $meta Meta array.
     * @param string $key Meta key.
     * @param mixed $default Default value.
     * @return mixed
     */
    private function get_meta(array $meta, string $key, $default)
    {
        $full_key = self::META_PREFIX . $key;
        return isset($meta[$full_key][0]) ? $meta[$full_key][0] : $default;
    }

    /**
     * Saves snippet.
     *
     * @param array $data Snippet data.
     * @return int|false Post ID or false on failure.
     */
    public function save_snippet(array $data)
    {
        $has_pro = self::hasPro();
        $is_new = empty($data['id']);

        // Check free limit
        if (!$has_pro && $is_new) {
            $count = count($this->get_all_snippets());
            if ($count >= self::FREE_LIMIT) {
                return false;
            }
        }

        // Validate type (HTML is Pro only)
        if (!$has_pro && $data['type'] === 'html') {
            $data['type'] = 'css';
        }

        // Validate location (body_open, custom_hook are Pro only)
        if (!$has_pro && in_array($data['location'], ['body_open', 'custom_hook'], true)) {
            $data['location'] = 'head';
        }

        // Prepare post data
        $post_data = [
            'post_type' => self::POST_TYPE,
            'post_title' => sanitize_text_field($data['title'] ?? ''),
            'post_content' => $data['code'] ?? '',
            'post_status' => ($data['status'] ?? 'enabled') === 'enabled' ? 'publish' : 'draft',
            'menu_order' => (int)($data['priority'] ?? 10),
        ];

        if (!$is_new) {
            $post_data['ID'] = (int)$data['id'];
            $post_id = wp_update_post($post_data);
        } else {
            $post_id = wp_insert_post($post_data);
        }

        if (!$post_id || is_wp_error($post_id)) {
            return false;
        }

        // Save meta
        $meta_fields = [
            'type' => sanitize_key($data['type'] ?? 'css'),
            'location' => sanitize_key($data['location'] ?? 'head'),
            'custom_hook' => sanitize_text_field($data['custom_hook'] ?? ''),
            'priority' => (int)($data['priority'] ?? 10),
            'js_load_mode' => sanitize_key($data['js_load_mode'] ?? 'inline'),
            'js_defer' => !empty($data['js_defer']),
            'js_async' => !empty($data['js_async']),
            'js_module' => !empty($data['js_module']),
            'js_dom_ready' => !empty($data['js_dom_ready']),
            'scope_mode' => sanitize_key($data['scope_mode'] ?? 'global'),
            'rules' => wp_json_encode($data['rules'] ?? []),
            'match_mode' => sanitize_key($data['match_mode'] ?? 'any'),
            'group_id' => (int)($data['group_id'] ?? 0),
            'notes' => sanitize_textarea_field($data['notes'] ?? ''),
        ];

        // Reset Pro-only meta in free version
        if (!$has_pro) {
            $meta_fields['js_defer'] = false;
            $meta_fields['js_async'] = false;
            $meta_fields['js_module'] = false;
            $meta_fields['match_mode'] = 'any';
        }

        foreach ($meta_fields as $key => $value) {
            update_post_meta($post_id, self::META_PREFIX . $key, $value);
        }

        return $post_id;
    }

    /**
     * Deletes snippet.
     *
     * @param int $id Snippet ID.
     * @return bool
     */
    public function delete_snippet(int $id): bool
    {
        $post = get_post($id);

        if (!$post || $post->post_type !== self::POST_TYPE) {
            return false;
        }

        return wp_delete_post($id, true) !== false;
    }

    /**
     * Duplicates snippet.
     *
     * @param int $id Snippet ID.
     * @return int|false New snippet ID or false.
     */
    public function duplicate_snippet(int $id)
    {
        $snippet = $this->get_snippet($id);

        if (!$snippet) {
            return false;
        }

        $snippet['id'] = 0;
        $snippet['title'] .= ' ' . __('(Copy)', 'king-addons');
        $snippet['status'] = 'disabled';

        return $this->save_snippet($snippet);
    }

    /**
     * Toggles snippet status.
     *
     * @param int $id Snippet ID.
     * @return string|false New status or false.
     */
    public function toggle_snippet(int $id)
    {
        $post = get_post($id);

        if (!$post || $post->post_type !== self::POST_TYPE) {
            return false;
        }

        $new_status = $post->post_status === 'publish' ? 'draft' : 'publish';

        wp_update_post([
            'ID' => $id,
            'post_status' => $new_status,
        ]);

        return $new_status === 'publish' ? 'enabled' : 'disabled';
    }

    // =========================================================================
    // AJAX Handlers
    // =========================================================================

    /**
     * AJAX: Save snippet.
     *
     * @return void
     */
    public function ajax_save_snippet(): void
    {
        check_ajax_referer('kng_cc_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'king-addons')]);
        }

        $data = isset($_POST['snippet']) ? json_decode(wp_unslash($_POST['snippet']), true) : [];

        if (empty($data)) {
            wp_send_json_error(['message' => __('Invalid data', 'king-addons')]);
        }

        $id = $this->save_snippet($data);

        if ($id) {
            wp_send_json_success([
                'id' => $id,
                'message' => __('Snippet saved successfully', 'king-addons'),
            ]);
        } else {
            $has_pro = self::hasPro();
            if (!$has_pro) {
                wp_send_json_error(['message' => __('Free version limit reached', 'king-addons')]);
            }
            wp_send_json_error(['message' => __('Failed to save snippet', 'king-addons')]);
        }
    }

    /**
     * AJAX: Delete snippet.
     *
     * @return void
     */
    public function ajax_delete_snippet(): void
    {
        check_ajax_referer('kng_cc_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'king-addons')]);
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

        if ($this->delete_snippet($id)) {
            wp_send_json_success(['message' => __('Snippet deleted', 'king-addons')]);
        } else {
            wp_send_json_error(['message' => __('Failed to delete snippet', 'king-addons')]);
        }
    }

    /**
     * AJAX: Toggle snippet.
     *
     * @return void
     */
    public function ajax_toggle_snippet(): void
    {
        check_ajax_referer('kng_cc_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'king-addons')]);
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $new_status = $this->toggle_snippet($id);

        if ($new_status) {
            wp_send_json_success([
                'status' => $new_status,
                'message' => __('Status updated', 'king-addons'),
            ]);
        } else {
            wp_send_json_error(['message' => __('Failed to update status', 'king-addons')]);
        }
    }

    /**
     * AJAX: Duplicate snippet.
     *
     * @return void
     */
    public function ajax_duplicate_snippet(): void
    {
        check_ajax_referer('kng_cc_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'king-addons')]);
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $new_id = $this->duplicate_snippet($id);

        if ($new_id) {
            wp_send_json_success([
                'id' => $new_id,
                'snippet' => $this->get_snippet($new_id),
                'message' => __('Snippet duplicated', 'king-addons'),
            ]);
        } else {
            wp_send_json_error(['message' => __('Failed to duplicate snippet', 'king-addons')]);
        }
    }

    /**
     * AJAX: Export single snippet.
     *
     * @return void
     */
    public function ajax_export_snippet(): void
    {
        check_ajax_referer('kng_cc_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'king-addons')]);
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $snippet = $this->get_snippet($id);

        if (!$snippet) {
            wp_send_json_error(['message' => __('Snippet not found', 'king-addons')]);
        }

        // Remove internal fields
        unset($snippet['author'], $snippet['created'], $snippet['modified']);

        wp_send_json_success([
            'export' => [
                'version' => KING_ADDONS_VERSION,
                'type' => 'single',
                'exported' => current_time('mysql'),
                'snippets' => [$snippet],
            ],
        ]);
    }

    /**
     * AJAX: Export all snippets.
     *
     * @return void
     */
    public function ajax_export_all(): void
    {
        check_ajax_referer('kng_cc_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'king-addons')]);
        }

        $snippets = $this->get_all_snippets();

        foreach ($snippets as &$snippet) {
            unset($snippet['author'], $snippet['created'], $snippet['modified']);
        }

        wp_send_json_success([
            'export' => [
                'version' => KING_ADDONS_VERSION,
                'type' => 'all',
                'exported' => current_time('mysql'),
                'snippets' => $snippets,
                'settings' => $this->get_settings(),
            ],
        ]);
    }

    /**
     * AJAX: Import snippets.
     *
     * @return void
     */
    public function ajax_import(): void
    {
        check_ajax_referer('kng_cc_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'king-addons')]);
        }

        $import_data = isset($_POST['import']) ? json_decode(wp_unslash($_POST['import']), true) : [];
        $mode = isset($_POST['mode']) ? sanitize_key($_POST['mode']) : 'merge';

        if (empty($import_data['snippets'])) {
            wp_send_json_error(['message' => __('Invalid import data', 'king-addons')]);
        }

        $has_pro = self::hasPro();
        $imported = 0;
        $skipped = 0;
        $errors = 0;

        // Get existing snippets for duplicate check
        $existing = [];
        if ($mode === 'skip') {
            foreach ($this->get_all_snippets() as $snippet) {
                $existing[$snippet['title']] = true;
            }
        }

        // Replace mode: delete all first
        if ($mode === 'replace') {
            foreach ($this->get_all_snippets() as $snippet) {
                $this->delete_snippet($snippet['id']);
            }
        }

        foreach ($import_data['snippets'] as $snippet) {
            // Check duplicate
            if ($mode === 'skip' && isset($existing[$snippet['title']])) {
                $skipped++;
                continue;
            }

            // Check free limit
            if (!$has_pro) {
                $count = count($this->get_all_snippets());
                if ($count >= self::FREE_LIMIT) {
                    $errors++;
                    continue;
                }
            }

            // Reset ID for new import
            $snippet['id'] = 0;

            if ($this->save_snippet($snippet)) {
                $imported++;
            } else {
                $errors++;
            }
        }

        wp_send_json_success([
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
            'message' => sprintf(
                __('Import complete: %d imported, %d skipped, %d errors', 'king-addons'),
                $imported,
                $skipped,
                $errors
            ),
        ]);
    }

    /**
     * AJAX: Search content (pages, posts, etc.)
     *
     * @return void
     */
    public function ajax_search_content(): void
    {
        check_ajax_referer('kng_cc_admin', 'nonce');

        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $content_type = isset($_POST['content_type']) ? sanitize_key($_POST['content_type']) : 'page';

        $args = [
            'post_type' => $content_type,
            'posts_per_page' => 20,
            'post_status' => 'publish',
            's' => $search,
        ];

        $posts = get_posts($args);
        $results = [];

        foreach ($posts as $post) {
            $results[] = [
                'id' => $post->ID,
                'title' => $post->post_title,
                'type' => $post->post_type,
            ];
        }

        wp_send_json_success($results);
    }

    /**
     * AJAX: Get single snippet.
     *
     * @return void
     */
    public function ajax_get_snippet(): void
    {
        check_ajax_referer('kng_cc_admin', 'nonce');

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $snippet = $this->get_snippet($id);

        if ($snippet) {
            wp_send_json_success($snippet);
        } else {
            wp_send_json_error(['message' => __('Snippet not found', 'king-addons')]);
        }
    }

    /**
     * AJAX: Bulk action.
     *
     * @return void
     */
    public function ajax_bulk_action(): void
    {
        check_ajax_referer('kng_cc_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'king-addons')]);
        }

        $action = isset($_POST['bulk_action']) ? sanitize_key($_POST['bulk_action']) : '';
        $ids = isset($_POST['ids']) ? array_map('intval', (array)$_POST['ids']) : [];

        if (empty($ids)) {
            wp_send_json_error(['message' => __('No items selected', 'king-addons')]);
        }

        $processed = 0;

        foreach ($ids as $id) {
            switch ($action) {
                case 'enable':
                    $post = get_post($id);
                    if ($post && $post->post_status !== 'publish') {
                        wp_update_post(['ID' => $id, 'post_status' => 'publish']);
                        $processed++;
                    }
                    break;

                case 'disable':
                    $post = get_post($id);
                    if ($post && $post->post_status === 'publish') {
                        wp_update_post(['ID' => $id, 'post_status' => 'draft']);
                        $processed++;
                    }
                    break;

                case 'delete':
                    if ($this->delete_snippet($id)) {
                        $processed++;
                    }
                    break;
            }
        }

        wp_send_json_success([
            'processed' => $processed,
            'message' => sprintf(__('%d items processed', 'king-addons'), $processed),
        ]);
    }

    // =========================================================================
    // Frontend Injection
    // =========================================================================

    /**
     * Gets snippets for current page.
     *
     * @param string $location Location filter.
     * @return array
     */
    private function get_snippets_for_current_page(string $location): array
    {
        if ($this->cached_snippets === null) {
            $this->cached_snippets = $this->get_all_snippets([
                'post_status' => 'publish',
            ]);
        }

        $matching = [];

        foreach ($this->cached_snippets as $snippet) {
            // Check location
            if ($snippet['location'] !== $location) {
                continue;
            }

            // Check rules
            if ($this->should_load_snippet($snippet)) {
                $matching[] = $snippet;
            }
        }

        // Sort by priority
        usort($matching, function ($a, $b) {
            $priority_diff = $a['priority'] - $b['priority'];
            if ($priority_diff !== 0) {
                return $priority_diff;
            }
            return $a['id'] - $b['id'];
        });

        return $matching;
    }

    /**
     * Checks if snippet should load on current page.
     *
     * @param array $snippet Snippet config.
     * @return bool
     */
    private function should_load_snippet(array $snippet): bool
    {
        $cache_key = 'snippet_' . $snippet['id'];

        if (isset($this->rule_cache[$cache_key])) {
            return $this->rule_cache[$cache_key];
        }

        $result = $this->evaluate_rules($snippet);
        $this->rule_cache[$cache_key] = $result;

        if ($this->is_debug_mode()) {
            $this->debug_log[] = [
                'snippet' => $snippet['title'],
                'id' => $snippet['id'],
                'result' => $result,
                'scope_mode' => $snippet['scope_mode'],
                'rules' => $snippet['rules'],
            ];
        }

        return $result;
    }

    /**
     * Evaluates snippet rules.
     *
     * @param array $snippet Snippet config.
     * @return bool
     */
    private function evaluate_rules(array $snippet): bool
    {
        $scope_mode = $snippet['scope_mode'];
        $rules = $snippet['rules'];
        $match_mode = $snippet['match_mode'];
        $has_pro = self::hasPro();

        // Global scope: always load
        if ($scope_mode === 'global') {
            return true;
        }

        if (empty($rules)) {
            return $scope_mode === 'include' ? false : true;
        }

        // Separate include and exclude rules
        $include_rules = [];
        $exclude_rules = [];

        foreach ($rules as $rule) {
            if (isset($rule['exclude']) && $rule['exclude']) {
                $exclude_rules[] = $rule;
            } else {
                $include_rules[] = $rule;
            }
        }

        // Check exclude rules first (Pro)
        if ($has_pro && !empty($exclude_rules)) {
            foreach ($exclude_rules as $rule) {
                if ($this->evaluate_single_rule($rule)) {
                    return false;
                }
            }
        }

        // Check include rules
        if ($scope_mode === 'include') {
            if (empty($include_rules)) {
                return false;
            }

            // Match mode (Pro): ALL or ANY
            if ($has_pro && $match_mode === 'all') {
                foreach ($include_rules as $rule) {
                    if (!$this->evaluate_single_rule($rule)) {
                        return false;
                    }
                }
                return true;
            }

            // Default: ANY match
            foreach ($include_rules as $rule) {
                if ($this->evaluate_single_rule($rule)) {
                    return true;
                }
            }
            return false;
        }

        // Exclude scope mode
        if ($scope_mode === 'exclude') {
            foreach ($include_rules as $rule) {
                if ($this->evaluate_single_rule($rule)) {
                    return false;
                }
            }
            return true;
        }

        return true;
    }

    /**
     * Evaluates a single rule.
     *
     * @param array $rule Rule config.
     * @return bool
     */
    private function evaluate_single_rule(array $rule): bool
    {
        $type = $rule['type'] ?? '';
        $value = $rule['value'] ?? '';
        $has_pro = self::hasPro();

        switch ($type) {
            case 'page':
            case 'post':
                if (is_singular()) {
                    return get_the_ID() === (int)$value;
                }
                return false;

            case 'post_type':
                return is_singular($value) || is_post_type_archive($value);

            case 'url_contains':
                $current_url = $_SERVER['REQUEST_URI'] ?? '';
                return strpos($current_url, $value) !== false;

            case 'url_starts':
                if (!$has_pro) return false;
                $current_url = $_SERVER['REQUEST_URI'] ?? '';
                return strpos($current_url, $value) === 0;

            case 'url_ends':
                if (!$has_pro) return false;
                $current_url = $_SERVER['REQUEST_URI'] ?? '';
                return substr($current_url, -strlen($value)) === $value;

            case 'url_regex':
                if (!$has_pro) return false;
                $current_url = $_SERVER['REQUEST_URI'] ?? '';
                try {
                    return (bool)preg_match($value, $current_url);
                } catch (\Exception $e) {
                    return false;
                }

            case 'taxonomy':
                if (!$has_pro) return false;
                $tax = $rule['taxonomy'] ?? 'category';
                $term_id = (int)$value;
                if (is_singular()) {
                    return has_term($term_id, $tax);
                }
                if (is_tax($tax, $term_id) || is_category($term_id) || is_tag($term_id)) {
                    return true;
                }
                return false;

            case 'user_logged_in':
                if (!$has_pro) return false;
                return is_user_logged_in() === ($value === 'yes');

            case 'user_role':
                if (!$has_pro) return false;
                if (!is_user_logged_in()) return false;
                $user = wp_get_current_user();
                return in_array($value, $user->roles, true);

            case 'device':
                if (!$has_pro) return false;
                return $this->detect_device() === $value;

            case 'front_page':
                return is_front_page();

            case 'blog_page':
                return is_home();

            case 'archive':
                return is_archive();

            case 'search':
                return is_search();

            case '404':
                return is_404();

            default:
                return false;
        }
    }

    /**
     * Detects current device type.
     *
     * @return string
     */
    private function detect_device(): string
    {
        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            return 'desktop';
        }

        $ua = strtolower($_SERVER['HTTP_USER_AGENT']);

        if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobile))/i', $ua)) {
            return 'tablet';
        }

        if (preg_match('/(mobile|android|iphone|ipod|blackberry|windows phone)/i', $ua)) {
            return 'mobile';
        }

        return 'desktop';
    }

    /**
     * Injects code into wp_head.
     *
     * @return void
     */
    public function inject_head(): void
    {
        $snippets = $this->get_snippets_for_current_page('head');
        $this->output_snippets($snippets);
    }

    /**
     * Injects code into wp_footer.
     *
     * @return void
     */
    public function inject_footer(): void
    {
        $snippets = $this->get_snippets_for_current_page('footer');
        $this->output_snippets($snippets);
    }

    /**
     * Injects code into wp_body_open.
     *
     * @return void
     */
    public function inject_body_open(): void
    {
        if (!self::hasPro()) {
            return;
        }

        $snippets = $this->get_snippets_for_current_page('body_open');
        $this->output_snippets($snippets);
    }

    /**
     * Outputs snippets.
     *
     * @param array $snippets Snippets to output.
     * @return void
     */
    private function output_snippets(array $snippets): void
    {
        foreach ($snippets as $snippet) {
            $code = $snippet['code'];

            if (empty(trim($code))) {
                continue;
            }

            switch ($snippet['type']) {
                case 'css':
                    echo "\n<!-- King Addons Custom CSS: " . esc_html($snippet['title']) . " -->\n";
                    echo "<style id=\"kng-cc-" . esc_attr($snippet['id']) . "\">\n";
                    echo $code;
                    echo "\n</style>\n";
                    break;

                case 'js':
                    echo "\n<!-- King Addons Custom JS: " . esc_html($snippet['title']) . " -->\n";
                    $this->output_js_snippet($snippet);
                    break;

                case 'html':
                    if (self::hasPro()) {
                        echo "\n<!-- King Addons Custom HTML: " . esc_html($snippet['title']) . " -->\n";
                        echo $code;
                        echo "\n";
                    }
                    break;
            }
        }
    }

    /**
     * Outputs JS snippet with proper attributes.
     *
     * @param array $snippet Snippet config.
     * @return void
     */
    private function output_js_snippet(array $snippet): void
    {
        $code = $snippet['code'];
        $has_pro = self::hasPro();

        // Wrap in DOMContentLoaded if needed
        if ($snippet['js_dom_ready']) {
            $code = "document.addEventListener('DOMContentLoaded', function() {\n" . $code . "\n});";
        }

        $attrs = ['id="kng-cc-' . esc_attr($snippet['id']) . '"'];

        if ($has_pro) {
            if ($snippet['js_module']) {
                $attrs[] = 'type="module"';
            }
            if ($snippet['js_defer']) {
                $attrs[] = 'defer';
            }
            if ($snippet['js_async']) {
                $attrs[] = 'async';
            }
        }

        echo '<script ' . implode(' ', $attrs) . ">\n";
        echo $code;
        echo "\n</script>\n";
    }

    /**
     * Modifies script tag for enqueued scripts.
     *
     * @param string $tag Script tag.
     * @param string $handle Script handle.
     * @param string $src Script source.
     * @return string
     */
    public function modify_script_tag(string $tag, string $handle, string $src): string
    {
        // For future use with external scripts
        return $tag;
    }

    // =========================================================================
    // Settings
    // =========================================================================

    /**
     * Gets settings.
     *
     * @return array
     */
    public function get_settings(): array
    {
        $defaults = [
            'enabled' => true,
            'default_location_css' => 'head',
            'default_location_js' => 'footer',
            'default_priority' => 10,
            'debug_mode' => false,
        ];

        $saved = get_option('kng_cc_settings', []);

        return array_merge($defaults, $saved);
    }

    /**
     * Saves settings.
     *
     * @param array $settings Settings to save.
     * @return bool
     */
    public function save_settings(array $settings): bool
    {
        return update_option('kng_cc_settings', $settings);
    }

    /**
     * Checks if debug mode is enabled.
     *
     * @return bool
     */
    private function is_debug_mode(): bool
    {
        $settings = $this->get_settings();
        return !empty($settings['debug_mode']);
    }

    /**
     * Outputs debug log.
     *
     * @return void
     */
    public function output_debug_log(): void
    {
        if (empty($this->debug_log)) {
            return;
        }

        echo "\n<!-- King Addons Custom Code Debug Log -->\n";
        echo "<script>\n";
        echo "console.group('King Addons Custom Code Manager');\n";
        foreach ($this->debug_log as $entry) {
            $status = $entry['result'] ? '✓' : '✗';
            echo "console.log('" . $status . " " . esc_js($entry['snippet']) . " (ID: " . $entry['id'] . ")');\n";
        }
        echo "console.groupEnd();\n";
        echo "</script>\n";
    }
}

// Initialize
Custom_Code_Manager::getInstance();
