<?php
/**
 * My Account endpoints manager.
 *
 * @package King_Addons
 */

namespace King_Addons\Woo_Builder;

use Elementor\Plugin as Elementor_Plugin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles endpoint registration and template routing for My Account.
 */
class My_Account_Manager
{
    /**
     * Option name for endpoints config.
     */
    private const OPTION_NAME = 'ka_woo_account_endpoints';

    /**
     * Cached endpoints.
     *
     * @var array<string,array<string,mixed>>
     */
    private array $endpoints = [];

    /**
     * Guards against re-entering get_endpoints() from our own menu filter.
     *
     * @var bool
     */
    private bool $resolving_endpoints = false;

    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action('init', [$this, 'register_custom_endpoints']);
        add_filter('woocommerce_account_menu_items', [$this, 'filter_menu_items'], 99);
        add_filter('woocommerce_get_endpoint_url', [$this, 'filter_logout_url'], 10, 4);
        add_action('template_redirect', [$this, 'maybe_render_endpoint_template'], 1);
        // Use priority 15 to ensure the parent menu exists before adding this submenu
        add_action('admin_menu', [$this, 'register_admin_page'], 15);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_post_king_addons_myaccount_endpoints_save', [$this, 'handle_admin_save']);
    }

    /**
     * Register custom endpoints (rewrite).
     *
     * @return void
     */
    public function register_custom_endpoints(): void
    {
        foreach ($this->get_endpoints() as $slug => $data) {
            if (!empty($data['is_custom']) && !empty($data['enabled'])) {
                add_rewrite_endpoint($slug, EP_ROOT | EP_PAGES);
            }
        }
    }

    /**
     * Filter My Account menu items based on config.
     *
     * @param array<string,string> $items Default menu.
     *
     * @return array<string,string>
     */
    public function filter_menu_items(array $items): array
    {
        $endpoints = $this->get_endpoints();

        // get_endpoints() is what triggered this filter in the first place, so
        // on the way in it has nothing to offer yet. Returning its empty list
        // here would hand WooCommerce an empty account menu.
        if (empty($endpoints)) {
            return $items;
        }

        $output = [];

        foreach ($endpoints as $slug => $data) {
            if (empty($data['enabled'])) {
                continue;
            }

            if (!$this->is_allowed_for_user($data)) {
                continue;
            }

            $label = $data['label'] ?? ($items[$slug] ?? ucfirst(str_replace('-', ' ', $slug)));
            $position = isset($data['position']) ? (int) $data['position'] : 20;

            $output[sprintf('%05d:%s', $position, $slug)] = [$slug, $label];
        }

        ksort($output, SORT_NATURAL);

        $sorted = [];
        foreach ($output as $item) {
            [$slug, $label] = $item;
            $sorted[$slug] = $label;
        }

        return $sorted;
    }

    /**
     * Render endpoint template if mapped; otherwise fall back.
     *
     * @return void
     */
    public function maybe_render_endpoint_template(): void
    {
        if (!function_exists('is_account_page') || !is_account_page()) {
            return;
        }
        if (!class_exists(Elementor_Plugin::class)) {
            return;
        }

        if (isset($_GET['ka_logout_confirm'])) {
            $this->render_logout_confirm();
            return;
        }

        $endpoints = $this->get_endpoints();
        $current = $this->detect_current_endpoint(array_keys($endpoints));
        if (!$current) {
            return;
        }

        $config = $endpoints[$current] ?? [];
        $template_id = isset($config['template_id']) ? (int) $config['template_id'] : 0;

        if (!$this->is_allowed_for_user($config)) {
            return;
        }

        if (!$template_id || !king_addons_can_use_pro()) {
            return;
        }

        status_header(200);
        nocache_headers();
        echo '<div class="king-addons-woo-builder king-addons-woo-builder--my-account king-addons-woo-builder--endpoint-' . esc_attr($current) . '">';
        echo Elementor_Plugin::$instance->frontend->get_builder_content_for_display($template_id); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '</div>';
        exit;
    }

    /**
     * Add confirm flag to logout URL if configured.
     *
     * @param string $url      URL.
     * @param string $endpoint Endpoint.
     * @param string $value    Value.
     * @param string $permalink Permalink.
     *
     * @return string
     */
    public function filter_logout_url(string $url, string $endpoint, string $value, string $permalink): string
    {
        $endpoints = $this->get_endpoints();
        if ('customer-logout' === $endpoint && !empty($endpoints['customer-logout']['confirm'])) {
            $url = add_query_arg('ka_logout_confirm', '1', $url);
        }
        return $url;
    }

    /**
     * Get endpoints config with defaults and filters.
     *
     * @return array<string,array<string,mixed>>
     */
    public function get_endpoints(): array
    {
        if (!empty($this->endpoints)) {
            return $this->endpoints;
        }

        // wc_get_account_menu_items() applies woocommerce_account_menu_items,
        // and filter_menu_items() below asks for the endpoints again - without
        // this guard the pair recurse until PHP runs out of memory.
        if ($this->resolving_endpoints) {
            return [];
        }
        $this->resolving_endpoints = true;

        $defaults = wc_get_account_menu_items();
        $config = get_option(self::OPTION_NAME, []);

        $endpoints = [];
        $order = 10;
        foreach ($defaults as $slug => $label) {
            $endpoints[$slug] = [
                'label' => $label,
                'enabled' => true,
                // Was a fixed 20 for every item, so sorting fell back to the
                // slug and rearranged WooCommerce's menu alphabetically.
                'position' => $order,
                'template_id' => 0,
                'is_custom' => false,
                'confirm' => false,
            ];
            $order += 10;
        }

        // Merge saved config.
        if (is_array($config)) {
            foreach ($config as $slug => $data) {
                $endpoints[$slug] = array_merge($endpoints[$slug] ?? [], $data);
            }
        }

        /**
         * Filter endpoints (add custom, adjust existing).
         *
         * @param array<string,array<string,mixed>> $endpoints Endpoints config.
         */
        $endpoints = apply_filters('king_addons/my_account/endpoints', $endpoints);

        $this->endpoints = $endpoints;
        $this->resolving_endpoints = false;

        return $this->endpoints;
    }

    /**
     * Detect current endpoint slug.
     *
     * @param array<int,string> $endpoints Endpoint slugs.
     *
     * @return string|null
     */
    private function detect_current_endpoint(array $endpoints): ?string
    {
        global $wp;
        foreach ($endpoints as $endpoint) {
            if (isset($wp->query_vars[$endpoint])) {
                return $endpoint;
            }
        }
        return null;
    }

    /**
     * Register admin settings page.
     *
     * @return void
     */
    public function register_admin_page(): void
    {
        add_submenu_page(
            king_addons_woo_admin_parent_slug(),
            esc_html__('My Account Endpoints', 'king-addons'),
            esc_html__('My Account Endpoints', 'king-addons'),
            'manage_options',
            'king-addons-myaccount-endpoints',
            [$this, 'render_admin_page']
        );
    }

    /**
     * Register settings.
     *
     * @return void
     */
    public function register_settings(): void
    {
        register_setting(
            'king_addons_myaccount_endpoints',
            self::OPTION_NAME,
            [
                'type' => 'array',
                'description' => 'King Addons My Account endpoints config',
                'sanitize_callback' => [$this, 'sanitize_endpoints_option'],
                'default' => [],
            ]
        );
    }

    /**
     * Sanitize endpoints option (expects JSON string or array).
     *
     * @param mixed $value Raw value.
     *
     * @return array<string,mixed>
     */
    public function sanitize_endpoints_option($value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return $decoded;
            }
            return [];
        }
        return is_array($value) ? $value : [];
    }

    /**
     * Render admin page.
     *
     * @return void
     */
    public function render_admin_page(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $endpoints = $this->get_endpoints();
        $is_pro = function_exists('king_addons_can_use_pro') && king_addons_can_use_pro();
        $templates = $this->get_account_templates();
        $roles = $this->get_editable_roles();

        require KING_ADDONS_PATH . 'includes/helpers/Woo_Builder/templates/endpoints-admin-page.php';
    }

    /**
     * Elementor templates that can stand in for an endpoint's content.
     *
     * @return array<int,string>
     */
    private function get_account_templates(): array
    {
        $templates = [];

        $posts = get_posts([
            'post_type' => 'elementor_library',
            'post_status' => 'publish',
            'posts_per_page' => 100,
            'orderby' => 'title',
            'order' => 'ASC',
            'suppress_filters' => false,
        ]);

        foreach ($posts as $post) {
            $templates[(int) $post->ID] = $post->post_title !== ''
                ? $post->post_title
                : sprintf('#%d', (int) $post->ID);
        }

        return $templates;
    }

    /**
     * Roles an endpoint can be limited to.
     *
     * @return array<string,string>
     */
    private function get_editable_roles(): array
    {
        if (!function_exists('get_editable_roles')) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
        }

        $roles = [];

        foreach (get_editable_roles() as $slug => $role) {
            $roles[(string) $slug] = translate_user_role($role['name'] ?? $slug);
        }

        return $roles;
    }

    /**
     * Save the endpoints screen.
     *
     * @return void
     */
    public function handle_admin_save(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to do this.', 'king-addons'));
        }

        check_admin_referer('king_addons_myaccount_endpoints_save');

        $raw = isset($_POST['ka_endpoint']) && is_array($_POST['ka_endpoint'])
            ? wp_unslash($_POST['ka_endpoint']) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            : [];

        $is_pro = function_exists('king_addons_can_use_pro') && king_addons_can_use_pro();
        $known_roles = array_keys($this->get_editable_roles());
        $existing = get_option(self::OPTION_NAME, []);
        $existing = is_array($existing) ? $existing : [];

        $clean = [];

        foreach ($raw as $slug => $data) {
            $slug = sanitize_key((string) $slug);
            if ('' === $slug || !is_array($data)) {
                continue;
            }

            $was_custom = !empty($existing[$slug]['is_custom']);

            $clean[$slug] = [
                'label' => sanitize_text_field((string) ($data['label'] ?? '')),
                'enabled' => !empty($data['enabled']),
                'position' => max(0, min(9999, (int) ($data['position'] ?? 10))),
                'template_id' => $is_pro ? absint($data['template_id'] ?? 0) : 0,
                'is_custom' => $was_custom,
                'confirm' => !empty($data['confirm']),
                'roles' => array_values(array_intersect(
                    array_map('sanitize_key', (array) ($data['roles'] ?? [])),
                    $known_roles
                )),
            ];
        }

        // A new custom endpoint, if one was filled in.
        $new_slug = isset($_POST['ka_new_endpoint_slug'])
            ? sanitize_title(wp_unslash($_POST['ka_new_endpoint_slug'])) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            : '';
        $new_label = isset($_POST['ka_new_endpoint_label'])
            ? sanitize_text_field(wp_unslash($_POST['ka_new_endpoint_label'])) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            : '';

        $notice = 'saved';

        if ('' !== $new_slug) {
            if (!$is_pro) {
                $notice = 'pro';
            } elseif (isset($clean[$new_slug])) {
                $notice = 'exists';
            } else {
                $clean[$new_slug] = [
                    'label' => '' !== $new_label ? $new_label : ucfirst(str_replace('-', ' ', $new_slug)),
                    'enabled' => true,
                    'position' => 100,
                    'template_id' => 0,
                    'is_custom' => true,
                    'confirm' => false,
                    'roles' => [],
                ];
                $notice = 'added';
            }
        }

        // Removing a custom endpoint. Built-in ones are switched off, not deleted.
        $remove = isset($_POST['ka_remove_endpoint'])
            ? sanitize_key(wp_unslash($_POST['ka_remove_endpoint'])) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            : '';

        if ('' !== $remove && !empty($clean[$remove]['is_custom'])) {
            unset($clean[$remove]);
            $notice = 'removed';
        }

        update_option(self::OPTION_NAME, $clean);

        // Custom endpoints add rewrite rules, which only take effect once the
        // rules are rebuilt.
        flush_rewrite_rules(false);

        wp_safe_redirect(add_query_arg(
            ['page' => 'king-addons-myaccount-endpoints', 'ka-notice' => $notice],
            admin_url('admin.php')
        ));
        exit;
    }

    /**
     * Render logout confirmation page.
     *
     * @return void
     */
    private function render_logout_confirm(): void
    {
        if (!is_user_logged_in()) {
            return;
        }

        $logout_url = wp_logout_url(wc_get_page_permalink('myaccount'));
        $cancel_url = remove_query_arg('ka_logout_confirm');

        status_header(200);
        nocache_headers();

        echo '<style>';
        echo '.ka-logout-confirm{min-height:100vh;display:flex;align-items:center;justify-content:center;background:#f8fafc;padding:24px;box-sizing:border-box;}';
        echo '.ka-logout-confirm__card{max-width:420px;width:100%;background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 10px 30px rgba(15,23,42,0.08);padding:28px;text-align:center;}';
        echo '.ka-logout-confirm__title{margin:0 0 12px;font-size:24px;font-weight:700;color:#0f172a;}';
        echo '.ka-logout-confirm__desc{margin:0 0 20px;font-size:15px;color:#334155;}';
        echo '.ka-logout-confirm__actions{display:flex;gap:12px;justify-content:center;flex-wrap:wrap;}';
        echo '.ka-logout-confirm__btn{display:inline-flex;align-items:center;justify-content:center;padding:10px 18px;border-radius:8px;border:1px solid transparent;font-weight:600;text-decoration:none;transition:all .15s ease;min-width:120px;}';
        echo '.ka-logout-confirm__btn--primary{background:#2563eb;border-color:#2563eb;color:#fff;}';
        echo '.ka-logout-confirm__btn--primary:hover{background:#1d4ed8;border-color:#1d4ed8;}';
        echo '.ka-logout-confirm__btn--ghost{background:#fff;border-color:#cbd5e1;color:#0f172a;}';
        echo '.ka-logout-confirm__btn--ghost:hover{border-color:#94a3b8;}';
        echo '</style>';

        echo '<div class="ka-logout-confirm">';
        echo '<div class="ka-logout-confirm__card">';
        echo '<h2 class="ka-logout-confirm__title">' . esc_html__('Confirm logout', 'king-addons') . '</h2>';
        echo '<p class="ka-logout-confirm__desc">' . esc_html__('Are you sure you want to log out?', 'king-addons') . '</p>';
        echo '<div class="ka-logout-confirm__actions">';
        echo '<a class="ka-logout-confirm__btn ka-logout-confirm__btn--primary" href="' . esc_url($logout_url) . '">' . esc_html__('Yes, log out', 'king-addons') . '</a>';
        echo '<a class="ka-logout-confirm__btn ka-logout-confirm__btn--ghost" href="' . esc_url($cancel_url) . '">' . esc_html__('Cancel', 'king-addons') . '</a>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        exit;
    }

    /**
     * Check role-based access.
     *
     * @param array<string,mixed> $data Endpoint data.
     *
     * @return bool
     */
    private function is_allowed_for_user(array $data): bool
    {
        if (empty($data['roles']) || !is_array($data['roles'])) {
            return true;
        }
        $user = wp_get_current_user();
        if (!$user || empty($user->roles)) {
            return false;
        }
        return (bool) array_intersect($user->roles, $data['roles']);
    }
}





