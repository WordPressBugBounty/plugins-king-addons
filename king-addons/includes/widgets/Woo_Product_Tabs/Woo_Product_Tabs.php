<?php
/**
 * Woo Product Tabs widget.
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders WooCommerce product tabs.
 */
class Woo_Product_Tabs extends Abstract_Single_Widget
{
    /**
     * Whether AJAX handlers are already registered.
     *
     * @var bool
     */
    private static $ajax_actions_registered = false;

    /**
     * Constructor.
     *
     * @param array<mixed> $data Widget data.
     * @param array|null   $args Widget args.
     */
    public function __construct($data = [], $args = null)
    {
        parent::__construct($data, $args);

        if (!self::$ajax_actions_registered) {
            add_action('wp_ajax_king_addons_render_product_tab', [self::class, 'ajax_render_tab']);
            add_action('wp_ajax_nopriv_king_addons_render_product_tab', [self::class, 'ajax_render_tab']);
            self::$ajax_actions_registered = true;
        }
    }

    public function get_name(): string
    {
        return 'woo_product_tabs';
    }

    public function get_title(): string
    {
        return esc_html__('Product Tabs', 'king-addons');
    }

    public function get_icon(): string
    {
        return 'eicon-tabs';
    }

    public function get_categories(): array
    {
        return ['king-addons-woo-builder'];
    }

    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-product-tabs-style'];
    }

    public function get_script_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-product-tabs-script'];
    }

    protected function register_controls(): void
    {
        $this->start_controls_section(
            'section_content',
            [
                'label' => esc_html__('Content', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'layout',
            [
                'label' => esc_html__('Layout', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'horizontal' => esc_html__('Horizontal', 'king-addons'),
                    'vertical' => sprintf(__('Vertical %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                    'accordion' => sprintf(__('Accordion %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                ],
                'default' => 'horizontal',
            ]
        );

        $this->add_control(
            'tabs_selection',
            [
                'label' => sprintf(__('Tabs to show %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SELECT2,
                'multiple' => true,
                'label_block' => true,
                'options' => [
                    'description' => esc_html__('Description', 'king-addons'),
                    'additional_information' => esc_html__('Additional Information', 'king-addons'),
                    'reviews' => esc_html__('Reviews', 'king-addons'),
                ],
                'default' => ['description', 'additional_information', 'reviews'],
            ]
        );

        $repeater = new Repeater();
        $repeater->add_control(
            'tab_key',
            [
                'label' => esc_html__('Tab', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'description' => esc_html__('Description', 'king-addons'),
                    'additional_information' => esc_html__('Additional Information', 'king-addons'),
                    'reviews' => esc_html__('Reviews', 'king-addons'),
                ],
                'default' => 'description',
            ]
        );

        $repeater->add_control(
            'custom_label',
            [
                'label' => esc_html__('Custom label', 'king-addons'),
                'type' => Controls_Manager::TEXT,
            ]
        );

        $repeater->add_control(
            'enabled',
            [
                'label' => esc_html__('Show tab', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'tabs_manager',
            [
                'label' => sprintf(__('Tabs manager %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'default' => [
                    ['tab_key' => 'description', 'enabled' => 'yes'],
                    ['tab_key' => 'additional_information', 'enabled' => 'yes'],
                    ['tab_key' => 'reviews', 'enabled' => 'yes'],
                ],
                'title_field' => '{{{ tab_key }}}',
            ]
        );

        $this->add_control(
            'active_tab',
            [
                'label' => esc_html__('Default Active Tab', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'description' => esc_html__('Description', 'king-addons'),
                    'additional_information' => esc_html__('Additional Information', 'king-addons'),
                    'reviews' => esc_html__('Reviews', 'king-addons'),
                ],
                'default' => 'description',
            ]
        );

        $this->add_control(
            'ajax_load',
            [
                'label' => sprintf(__('AJAX load content %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style_tabs',
            [
                'label' => esc_html__('Tabs', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'tabs_typo',
                'selector' => '{{WRAPPER}} .ka-woo-tabs__nav button',
            ]
        );

        $this->add_control(
            'tabs_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-tabs__nav button' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'tabs_color_active',
            [
                'label' => esc_html__('Active Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-tabs__nav button.is-active' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'tabs_border_active',
            [
                'label' => esc_html__('Active Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-tabs__nav button.is-active' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'tabs_gap',
            [
                'label' => esc_html__('Tabs Gap', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => ['min' => 0, 'max' => 30],
                ],
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-tabs__nav' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style_content',
            [
                'label' => esc_html__('Content', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'content_typo',
                'selector' => '{{WRAPPER}} .ka-woo-tabs__panel',
            ]
        );

        $this->add_control(
            'content_color',
            [
                'label' => esc_html__('Content Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-tabs__panel' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'content_padding',
            [
                'label' => esc_html__('Content Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-tabs__panel' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render(): void
    {
        $product = $this->get_product();
        if (!$product) {
            $this->render_missing_product_notice();
            return;
        }

        global $product;
        $settings = $this->get_settings_for_display();
        $can_pro = king_addons_can_use_pro();

        $layout = $settings['layout'] ?? 'horizontal';
        if (in_array($layout, ['vertical', 'accordion'], true) && !$can_pro) {
            $layout = 'horizontal';
        }

        $manager_rows = $settings['tabs_manager'] ?? [];
        $allowed_tabs = $settings['tabs_selection'] ?? ['description', 'additional_information', 'reviews'];
        if (!$can_pro || empty($allowed_tabs)) {
            $allowed_tabs = ['description', 'additional_information', 'reviews'];
        }

        $active_tab = $settings['active_tab'] ?? 'description';
        $ajax = !empty($settings['ajax_load']) && $can_pro;

        $tabs = apply_filters('woocommerce_product_tabs', []);
        if (empty($tabs)) {
            return;
        }

        // Use manager order if enabled and Pro, otherwise fallback to selection list.
        if ($can_pro && !empty($manager_rows)) {
            $ordered_tabs = [];
            foreach ($manager_rows as $row) {
                if (empty($row['tab_key'])) {
                    continue;
                }
                $key = sanitize_key($row['tab_key']);
                $enabled = !isset($row['enabled']) || 'yes' === $row['enabled'];
                if (!$enabled || !isset($tabs[$key])) {
                    continue;
                }
                if (!empty($row['custom_label'])) {
                    $tabs[$key]['title'] = esc_html($row['custom_label']);
                }
                $ordered_tabs[$key] = $tabs[$key];
            }
            // If manager removed everything, keep original.
            if (!empty($ordered_tabs)) {
                $tabs = $ordered_tabs;
            }
        } else {
            $ordered_tabs = [];
            foreach ($allowed_tabs as $key) {
                if (isset($tabs[$key])) {
                    $ordered_tabs[$key] = $tabs[$key];
                }
            }
            // Append any remaining tabs to avoid losing 3rd-party ones.
            foreach ($tabs as $key => $tab) {
                if (!isset($ordered_tabs[$key])) {
                    $ordered_tabs[$key] = $tab;
                }
            }
            $tabs = $ordered_tabs;
        }

        if (!isset($tabs[$active_tab])) {
            $active_tab = array_key_first($tabs);
        }

        $wrapper_classes = ['ka-woo-tabs', 'ka-woo-tabs--' . $layout];
        echo '<div class="' . esc_attr(implode(' ', $wrapper_classes)) . '" data-active="' . esc_attr($active_tab) . '" data-ajax="' . ($ajax ? 'yes' : 'no') . '" data-ajax-url="' . esc_url(admin_url('admin-ajax.php')) . '" data-nonce="' . esc_attr(wp_create_nonce('king_addons_woo_tabs')) . '" data-product-id="' . esc_attr($product->get_id()) . '">';

        echo '<div class="ka-woo-tabs__nav">';
        foreach ($tabs as $key => $tab) {
            $is_active = $key === $active_tab ? ' is-active' : '';
            echo '<button type="button" class="ka-woo-tabs__tab' . $is_active . '" data-tab="' . esc_attr($key) . '">' . wp_kses_post($tab['title']) . '</button>';
        }
        echo '</div>';

        echo '<div class="ka-woo-tabs__panels">';
        foreach ($tabs as $key => $tab) {
            $is_active = $key === $active_tab ? ' is-active' : '';
            echo '<div class="ka-woo-tabs__panel' . $is_active . '" data-tab="' . esc_attr($key) . '">';
            if ('accordion' === $layout) {
                echo '<button type="button" class="ka-woo-tabs__accordion-toggle" data-tab="' . esc_attr($key) . '">' . wp_kses_post($tab['title']) . '</button>';
                echo '<div class="ka-woo-tabs__accordion-body">';
            }
            if ($ajax && !$is_active) {
                echo '<div class="ka-woo-tabs__placeholder"></div>';
            } else {
                if (isset($tab['callback'])) {
                    call_user_func($tab['callback'], $key, $tab);
                }
            }
            if ('accordion' === $layout) {
                echo '</div>';
            }
            echo '</div>';
        }
        echo '</div>';

        echo '</div>';
    }

    /**
     * AJAX render a single tab.
     */
    public static function ajax_render_tab(): void
    {
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'king_addons_woo_tabs')) {
            wp_send_json_error(['message' => esc_html__('Invalid nonce.', 'king-addons')], 400);
        }

        if (!class_exists('WooCommerce') || !function_exists('wc_get_product')) {
            wp_send_json_error(['message' => esc_html__('WooCommerce is not available.', 'king-addons')], 400);
        }

        $product_id = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
        $tab_key = isset($_POST['tab_key']) ? sanitize_key(wp_unslash($_POST['tab_key'])) : '';

        if ($product_id <= 0 || empty($tab_key)) {
            wp_send_json_error(['message' => esc_html__('Invalid request.', 'king-addons')], 400);
        }

        $product = wc_get_product($product_id);
        if (!$product) {
            wp_send_json_error(['message' => esc_html__('Product not found.', 'king-addons')], 404);
        }

        $previous_product = $GLOBALS['product'] ?? null;
        $GLOBALS['product'] = $product;

        $tabs = apply_filters('woocommerce_product_tabs', []);
        if (!isset($tabs[$tab_key]) || empty($tabs[$tab_key]['callback'])) {
            $GLOBALS['product'] = $previous_product;
            wp_send_json_error(['message' => esc_html__('Tab not found.', 'king-addons')], 404);
        }

        ob_start();
        call_user_func($tabs[$tab_key]['callback'], $tab_key, $tabs[$tab_key]);
        $html = ob_get_clean();

        $GLOBALS['product'] = $previous_product;

        wp_send_json_success(
            [
                'html' => $html,
            ]
        );
    }
}







