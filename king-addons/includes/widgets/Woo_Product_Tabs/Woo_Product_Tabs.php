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
        return 'king-addons-icon king-addons-woo-product-tabs';
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
                'title_field' => '{{ tab_key }}',
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

        // WooCommerce assembles the tab list - and runs every tab callback -
        // off the globals: woocommerce_default_product_tabs() reads
        // $post->post_content for the description tab and comments_open() for
        // the reviews tab, and the callbacks themselves call the_content().
        // Point both globals at the product this widget resolved and put them
        // back afterwards. Declaring `global $product` here instead would
        // rebind the variable to the global and throw away the product
        // resolved above, which is null on any page that reaches the widget
        // without the global set; and leaving $post alone left the widget
        // completely blank in the editor, where $post is the template.
        $previous_product = $GLOBALS['product'] ?? null;
        $previous_post = $GLOBALS['post'] ?? null;
        $product_post = get_post($product->get_id());

        $GLOBALS['product'] = $product;
        if ($product_post instanceof \WP_Post) {
            $GLOBALS['post'] = $product_post;
            setup_postdata($product_post);
        }

        try {
            $this->render_tabs($product);
        } finally {
            $GLOBALS['product'] = $previous_product;
            $GLOBALS['post'] = $previous_post;
            if ($previous_post instanceof \WP_Post) {
                setup_postdata($previous_post);
            }
        }
    }

    /**
     * Insert a merged / third-party tab among already-ordered tabs by Woo priority.
     *
     * Product Tabs rebuilds the list from the manager or “Tabs to show”, then
     * used to append Custom Tabs after every native row. That ignored the
     * Custom Tabs Priority control (qa-526).
     *
     * @param array<string, array<string, mixed>> $ordered Already ordered tabs.
     * @param string                              $key     Tab key to insert.
     * @param array<string, mixed>                $tab     Tab payload from woocommerce_product_tabs.
     *
     * @return array<string, array<string, mixed>>
     */
    private static function insert_tab_by_priority(array $ordered, string $key, array $tab): array
    {
        if (isset($ordered[$key])) {
            return $ordered;
        }
        $pri = isset($tab['priority']) ? (int) $tab['priority'] : 80;
        $out = [];
        $inserted = false;
        foreach ($ordered as $existing_key => $existing) {
            $existing_pri = isset($existing['priority']) ? (int) $existing['priority'] : 80;
            if (!$inserted && $pri < $existing_pri) {
                $out[$key] = $tab;
                $inserted = true;
            }
            $out[$existing_key] = $existing;
        }
        if (!$inserted) {
            $out[$key] = $tab;
        }
        return $out;
    }

    /**
     * Render the tab nav and panels for a product.
     *
     * @param \WC_Product $product Product to render tabs for.
     * @return void
     */
    private function render_tabs($product): void
    {
        $settings = $this->get_settings_for_display();
        $can_pro = king_addons_can_use_pro();

        // The value lands in a class name, so anything outside the three known
        // layouts has to be dropped rather than passed through.
        $layout = (string) ($settings['layout'] ?? 'horizontal');
        if (!in_array($layout, ['horizontal', 'vertical', 'accordion'], true)) {
            $layout = 'horizontal';
        }
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
        $all_tabs = $tabs;
        if (empty($tabs)) {
            // Nothing to show, but an editor that renders nothing at all looks
            // like the widget is broken - say why instead.
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="king-addons-woo-builder-notice">'
                    . esc_html__('This product has no description, attributes or reviews, so there are no tabs to show.', 'king-addons')
                    . '</div>';
            }
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
                    // Titles go out through wp_kses_post() below, which is what
                    // makes them safe. Escaping here as well turned a label like
                    // "<b>New</b>" into visible &lt;b&gt; markup on the page.
                    $tabs[$key]['title'] = $row['custom_label'];
                }
                $ordered_tabs[$key] = $tabs[$key];
            }
            // If manager removed everything, keep original.
            if (!empty($ordered_tabs)) {
                $tabs = $ordered_tabs;
            }
            // Manager only lists native Woo tabs. Keep merged / third-party keys
            // (Product Custom Tabs merge_wc_tabs, extra plugins) ordered by
            // their Woo priority so Custom Tabs Priority is not a no-op.
            $native_keys = ['description', 'additional_information', 'reviews'];
            foreach ($all_tabs as $key => $tab) {
                if (isset($tabs[$key]) || in_array($key, $native_keys, true)) {
                    continue;
                }
                $tabs = self::insert_tab_by_priority($tabs, $key, $tab);
            }
        } else {
            $ordered_tabs = [];
            foreach ($allowed_tabs as $key) {
                if (isset($tabs[$key])) {
                    $ordered_tabs[$key] = $tabs[$key];
                }
            }
            // Keep third-party tabs, but honor “Tabs to show” for Woo’s own keys.
            $native_keys = ['description', 'additional_information', 'reviews'];
            foreach ($tabs as $key => $tab) {
                if (isset($ordered_tabs[$key])) {
                    continue;
                }
                if (in_array($key, $native_keys, true)) {
                    continue;
                }
                $ordered_tabs = self::insert_tab_by_priority($ordered_tabs, $key, $tab);
            }
            $tabs = $ordered_tabs;
        }

        if (!isset($tabs[$active_tab])) {
            $active_tab = array_key_first($tabs);
        }

        $wrapper_classes = ['ka-woo-tabs', 'ka-woo-tabs--' . $layout];
        echo '<div class="' . esc_attr(implode(' ', $wrapper_classes)) . '" data-active="' . esc_attr($active_tab) . '" data-ajax="' . ($ajax ? 'yes' : 'no') . '" data-ajax-url="' . esc_url(admin_url('admin-ajax.php')) . '" data-nonce="' . esc_attr(wp_create_nonce(Woo_Product_Tabs_Ajax::NONCE)) . '" data-product-id="' . esc_attr($product->get_id()) . '" data-error-text="' . esc_attr__('Could not load content.', 'king-addons') . '">';

        echo '<div class="ka-woo-tabs__nav" role="tablist">';
        foreach ($tabs as $key => $tab) {
            $is_active = $key === $active_tab ? ' is-active' : '';
            echo '<button type="button" role="tab" class="ka-woo-tabs__tab' . $is_active . '" data-tab="' . esc_attr($key) . '" aria-selected="' . ($is_active ? 'true' : 'false') . '">' . wp_kses_post($tab['title']) . '</button>';
        }
        echo '</div>';

        echo '<div class="ka-woo-tabs__panels">';
        foreach ($tabs as $key => $tab) {
            $is_active = $key === $active_tab ? ' is-active' : '';
            echo '<div class="ka-woo-tabs__panel' . $is_active . '" data-tab="' . esc_attr($key) . '">';
            if ('accordion' === $layout) {
                echo '<button type="button" class="ka-woo-tabs__accordion-toggle" data-tab="' . esc_attr($key) . '" aria-expanded="' . ($is_active ? 'true' : 'false') . '">' . wp_kses_post($tab['title']) . '</button>';
                echo '<div class="ka-woo-tabs__accordion-body">';
            }
            // Native Woo tabs can be deferred. Merged / third-party tabs
            // (Custom Tabs merge_wc_tabs) are not in the AJAX handler's
            // default list unless the builder template is already current,
            // so they must render inline or the panel shows the error string.
            $native_keys = ['description', 'additional_information', 'reviews'];
            $defer = $ajax && !$is_active && in_array($key, $native_keys, true);
            if ($defer) {
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
}
