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

            $output[$position . ':' . $slug] = [$slug, $label];
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

        $defaults = wc_get_account_menu_items();
        $config = get_option(self::OPTION_NAME, []);

        $endpoints = [];
        foreach ($defaults as $slug => $label) {
            $endpoints[$slug] = [
                'label' => $label,
                'enabled' => true,
                'position' => 20,
                'template_id' => 0,
                'is_custom' => false,
                'confirm' => false,
            ];
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
            'king-addons',
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
        $option = get_option(self::OPTION_NAME, []);
        $json = wp_json_encode($option, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('My Account Endpoints', 'king-addons'); ?></h1>
            <p><?php esc_html_e('Define endpoints as JSON: slug => {label, enabled, position, template_id, is_custom, confirm, roles:[...]}. Custom endpoints require Pro.', 'king-addons'); ?></p>
            <form method="post" action="options.php">
                <?php settings_fields('king_addons_myaccount_endpoints'); ?>
                <textarea name="<?php echo esc_attr(self::OPTION_NAME); ?>" rows="16" style="width:100%;font-family:monospace;"><?php echo esc_textarea($json ?: ''); ?></textarea>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
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





