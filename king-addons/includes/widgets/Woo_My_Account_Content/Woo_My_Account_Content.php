<?php
/**
 * Woo My Account Content widget.
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use King_Addons\Woo_Builder\Context as Woo_Context;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders my account content area.
 */
class Woo_My_Account_Content extends Abstract_My_Account_Widget
{
    /**
     * Cached custom endpoints config.
     *
     * @var array<string,array<string,mixed>>
     */
    private static array $custom_endpoints = [];

    /**
     * Whether hooks were added.
     *
     * @var bool
     */

    /**
     * Constructor.
     *
     * @param array<mixed> $data  Data.
     * @param array<mixed> $args  Args.
     */
    public function __construct($data = [], $args = null)
    {
        parent::__construct($data, $args);

        // No hooks here: the endpoint list is only known once render() runs,
        // long after init, so registering from the constructor could never see
        // it. Woo_Builder boots this widget's endpoints from the stored option
        // instead - see boot().
        // (nothing to do)
    }

    public function get_name(): string
    {
        return 'woo_my_account_content';
    }

    public function get_title(): string
    {
        return esc_html__('My Account Content', 'king-addons');
    }

    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-woo-my-account-content';
    }

    public function get_categories(): array
    {
        return ['king-addons-woo-builder'];
    }

    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-my-account-content-style'];
    }

    /**
     * Register controls.
     *
     * @return void
     */
    protected function register_controls(): void
    {
        $this->start_controls_section(
            'section_content',
            [
                'label' => esc_html__('Custom Endpoints (Pro)', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'endpoints',
            [
                'label' => esc_html__('Endpoints', 'king-addons'),
                'type' => Controls_Manager::REPEATER,
                'fields' => [
                    [
                        'name' => 'slug',
                        'label' => esc_html__('Slug', 'king-addons'),
                        'type' => Controls_Manager::TEXT,
                        'placeholder' => 'my-endpoint',
                    ],
                    [
                        'name' => 'label',
                        'label' => esc_html__('Menu label', 'king-addons'),
                        'type' => Controls_Manager::TEXT,
                        'placeholder' => esc_html__('My Endpoint', 'king-addons'),
                    ],
                    [
                        'name' => 'position',
                        'label' => esc_html__('Menu position', 'king-addons'),
                        'type' => Controls_Manager::NUMBER,
                        'default' => 90,
                    ],
                    [
                        'name' => 'content',
                        'label' => esc_html__('Content', 'king-addons'),
                        'type' => Controls_Manager::WYSIWYG,
                    ],
                ],
                'title_field' => '{{ label }} ({{ slug }})',
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style',
            [
                'label' => esc_html__('Style', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'text_typography',
                'selector' => '{{WRAPPER}} .ka-woo-my-account-content',
            ]
        );

        $this->add_control(
            'text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-my-account-content' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render(): void
    {
        if (!Woo_Context::maybe_render_context_notice('my_account')) {
            return;
        }

        if (!$this->should_render()) {
            $this->render_missing_account_notice();
            return;
        }

        $settings = $this->get_settings_for_display();
        $can_pro = king_addons_can_use_pro();

        if ($can_pro && !empty($settings['endpoints']) && is_array($settings['endpoints'])) {
            $collected = [];
            foreach ($settings['endpoints'] as $ep) {
                $slug = sanitize_title($ep['slug'] ?? '');
                if (empty($slug)) {
                    continue;
                }
                $collected[$slug] = [
                    'label' => (string) ($ep['label'] ?? $slug),
                    'content' => (string) ($ep['content'] ?? ''),
                    'position' => isset($ep['position']) ? (int) $ep['position'] : 90,
                ];
            }
            self::$custom_endpoints = array_merge(self::$custom_endpoints, $collected);
            // The rewrite rule for an endpoint has to exist at init, before any
            // widget renders - store the list so the next request can set it up.
            self::remember_endpoints(self::$custom_endpoints);
        }

        if ($this->maybe_render_login_form()) {
            return;
        }

        $skip_native = false;
        if (!Woo_Context::is_editor() && function_exists('is_wc_endpoint_url')) {
            $dedicated = [
                'orders' => 'woo_my_account_orders',
                'view-order' => 'woo_my_account_order_details',
                'edit-address' => 'woo_my_account_address',
                'edit-account' => 'woo_my_account_details',
                'downloads' => 'woo_my_account_downloads',
            ];
            foreach ($dedicated as $endpoint => $widget_type) {
                if (is_wc_endpoint_url($endpoint) && Woo_Context::template_has_widget($widget_type, 'my_account')) {
                    $skip_native = true;
                    break;
                }
            }
        }

        echo '<div class="ka-woo-my-account-content">';
        woocommerce_output_all_notices();
        if (!$skip_native) {
            woocommerce_account_content();
        }
        echo '</div>';
    }

    /**
     * Option holding the custom endpoints between requests.
     */
    private const ENDPOINTS_OPTION = 'king_addons_account_endpoints';

    /**
     * Option flag asking for a one-off rewrite flush.
     */
    private const FLUSH_OPTION = 'king_addons_account_endpoints_flush';

    /**
     * Persist the endpoint list, and ask for a rewrite flush when it changed.
     *
     * @param array<string,array<string,mixed>> $endpoints Endpoint definitions.
     *
     * @return void
     */
    private static function remember_endpoints(array $endpoints): void
    {
        $stored = get_option(self::ENDPOINTS_OPTION, []);
        if (!is_array($stored)) {
            $stored = [];
        }

        if ($stored === $endpoints) {
            return;
        }

        update_option(self::ENDPOINTS_OPTION, $endpoints, false);

        // Rewrite rules only change when the set of slugs changes.
        if (array_keys($stored) !== array_keys($endpoints)) {
            update_option(self::FLUSH_OPTION, 1, false);
        }
    }

    /**
     * Set up custom endpoints for this request.
     *
     * Called on init by Woo_Builder: rewrite endpoints and the account menu
     * filter both have to be in place before anything renders.
     *
     * @return void
     */
    public static function boot(): void
    {
        $stored = get_option(self::ENDPOINTS_OPTION, []);
        if (!is_array($stored) || empty($stored)) {
            return;
        }

        self::$custom_endpoints = $stored;
        self::register_endpoints();
        add_filter('woocommerce_account_menu_items', [self::class, 'filter_menu_items'], 20);
        add_filter('woocommerce_get_query_vars', [self::class, 'filter_wc_query_vars']);

        if (get_option(self::FLUSH_OPTION)) {
            delete_option(self::FLUSH_OPTION);
            flush_rewrite_rules(false);
        }
    }

    /**
     * Whether this request is a King Addons custom My Account endpoint.
     *
     * Custom slugs are registered with add_rewrite_endpoint() but are not
     * WooCommerce query vars unless filter_wc_query_vars() ran, so
     * is_wc_endpoint_url() can miss them. Dashboard and other widgets use
     * this to avoid stacking on those URLs.
     *
     * @return bool
     */
    public static function is_custom_endpoint_request(): bool
    {
        if (empty(self::$custom_endpoints)) {
            $stored = get_option(self::ENDPOINTS_OPTION, []);
            if (is_array($stored)) {
                self::$custom_endpoints = $stored;
            }
        }

        if (empty(self::$custom_endpoints)) {
            return false;
        }

        global $wp;
        if (!isset($wp->query_vars) || !is_array($wp->query_vars)) {
            return false;
        }

        foreach (array_keys(self::$custom_endpoints) as $slug) {
            if (array_key_exists((string) $slug, $wp->query_vars)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Expose custom account endpoints to WooCommerce's query-var map.
     *
     * @param array<string,string> $vars WooCommerce endpoint query vars.
     *
     * @return array<string,string>
     */
    public static function filter_wc_query_vars(array $vars): array
    {
        foreach (array_keys(self::$custom_endpoints) as $slug) {
            $vars[(string) $slug] = (string) $slug;
        }

        return $vars;
    }

    /**
     * Register rewrite endpoints for custom ones.
     *
     * @return void
     */
    public static function register_endpoints(): void
    {
        if (empty(self::$custom_endpoints)) {
            return;
        }
        foreach (array_keys(self::$custom_endpoints) as $slug) {
            add_rewrite_endpoint($slug, EP_ROOT | EP_PAGES);
            $hook = 'woocommerce_account_' . $slug . '_endpoint';
            add_action(
                $hook,
                static function () use ($slug): void {
                    $data = self::$custom_endpoints[$slug] ?? [];
                    $content = $data['content'] ?? '';
                    if ($content) {
                        echo '<div class="ka-woo-my-account-content__custom">' . do_shortcode(wp_kses_post($content)) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    } else {
                        echo '<div class="ka-woo-my-account-content__custom">' . esc_html__('Content not set.', 'king-addons') . '</div>';
                    }
                }
            );
        }
    }

    /**
     * Inject custom endpoints into account menu.
     *
     * @param array<string,string> $items Menu items.
     *
     * @return array<string,string>
     */
    public static function filter_menu_items(array $items): array
    {
        if (empty(self::$custom_endpoints)) {
            return $items;
        }
        // Every standard item used to get the same position, so the natural
        // sort below fell back to comparing slugs and reordered WooCommerce's
        // menu alphabetically - Log out ended up first. Keep their given order
        // by spacing them out, leaving room for custom endpoints in between.
        $ordered = [];
        $step = 10;
        foreach ($items as $slug => $label) {
            $ordered[ sprintf('%05d:%s', $step, $slug) ] = [$slug, $label];
            $step += 10;
        }
        foreach (self::$custom_endpoints as $slug => $data) {
            $pos = $data['position'] ?? 90;
            $label = $data['label'] ?? $slug;
            $ordered[ sprintf('%05d:%s', (int) $pos, $slug) ] = [$slug, $label];
        }
        ksort($ordered, SORT_NATURAL);
        $result = [];
        foreach ($ordered as $pack) {
            [$slug, $label] = $pack;
            $result[$slug] = $label;
        }
        return $result;
    }
}






