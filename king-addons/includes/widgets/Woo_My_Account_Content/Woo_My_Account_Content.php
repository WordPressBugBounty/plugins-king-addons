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
    private static bool $hooks_added = false;

    /**
     * Constructor.
     *
     * @param array<mixed> $data  Data.
     * @param array<mixed> $args  Args.
     */
    public function __construct($data = [], $args = null)
    {
        parent::__construct($data, $args);

        if (!self::$hooks_added) {
            add_action('init', [self::class, 'register_endpoints']);
            add_filter('woocommerce_account_menu_items', [self::class, 'filter_menu_items'], 20);
            self::$hooks_added = true;
        }
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
        return 'eicon-library-download';
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
                'title_field' => '{{{ label }}} ({{{ slug }}})',
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
            foreach ($settings['endpoints'] as $ep) {
                $slug = sanitize_title($ep['slug'] ?? '');
                if (empty($slug)) {
                    continue;
                }
                self::$custom_endpoints[$slug] = [
                    'label' => $ep['label'] ?? $slug,
                    'content' => $ep['content'] ?? '',
                    'position' => isset($ep['position']) ? (int) $ep['position'] : 90,
                ];
            }
        }

        if ($this->maybe_render_login_form()) {
            return;
        }

        echo '<div class="ka-woo-my-account-content">';
        woocommerce_output_all_notices();
        woocommerce_account_content();
        echo '</div>';
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
        $ordered = [];
        foreach ($items as $slug => $label) {
            $ordered[ sprintf('%05d:%s', 50, $slug) ] = [$slug, $label];
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






