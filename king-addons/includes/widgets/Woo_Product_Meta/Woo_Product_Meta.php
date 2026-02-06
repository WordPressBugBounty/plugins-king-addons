<?php
/**
 * Woo Product Meta widget.
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Displays product meta info (categories, tags, optional SKU/brand).
 */
class Woo_Product_Meta extends Abstract_Single_Widget
{
    public function get_name(): string
    {
        return 'woo_product_meta';
    }

    public function get_title(): string
    {
        return esc_html__('Product Meta', 'king-addons');
    }

    public function get_icon(): string
    {
        return 'eicon-meta-data';
    }

    public function get_categories(): array
    {
        return ['king-addons-woo-builder'];
    }

    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-product-meta-style'];
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
            'show_categories',
            [
                'label' => esc_html__('Show categories', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_tags',
            [
                'label' => esc_html__('Show tags', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_sku',
            [
                'label' => sprintf(__('Show SKU %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'show_brand',
            [
                'label' => sprintf(__('Show brand taxonomy %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'brand_taxonomy',
            [
                'label' => esc_html__('Brand taxonomy (Pro)', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => 'product_brand',
                'condition' => [
                    'show_brand' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'brand_fallback_taxonomy',
            [
                'label' => esc_html__('Brand fallback taxonomy (Pro)', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => 'pa_brand',
                'condition' => [
                    'show_brand' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'layout',
            [
                'label' => sprintf(__('Layout %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'inline' => esc_html__('Inline', 'king-addons'),
                    'stacked' => esc_html__('Stacked (Pro)', 'king-addons'),
                    'inline_no_label' => esc_html__('Inline, no labels (Pro)', 'king-addons'),
                ],
                'default' => 'inline',
            ]
        );

        $this->add_control(
            'format_preset',
            [
                'label' => sprintf(__('Format preset %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    '' => esc_html__('Default', 'king-addons'),
                    'pipe' => esc_html__('Pipe inline', 'king-addons'),
                    'bullets' => esc_html__('Bullets', 'king-addons'),
                    'badges' => esc_html__('Badges', 'king-addons'),
                ],
                'default' => '',
            ]
        );

        $this->add_control(
            'separator',
            [
                'label' => esc_html__('Separator', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => ', ',
            ]
        );

        $this->add_control(
            'label_categories',
            [
                'label' => esc_html__('Label: Categories', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Category:', 'king-addons'),
            ]
        );

        $this->add_control(
            'label_tags',
            [
                'label' => esc_html__('Label: Tags', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Tags:', 'king-addons'),
            ]
        );

        $this->add_control(
            'label_brand',
            [
                'label' => esc_html__('Label: Brand (Pro)', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Brand:', 'king-addons'),
            ]
        );

        $this->add_control(
            'label_sku',
            [
                'label' => esc_html__('Label: SKU (Pro)', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('SKU:', 'king-addons'),
            ]
        );

        $this->add_control(
            'label_transform',
            [
                'label' => sprintf(__('Label transform %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    '' => esc_html__('None', 'king-addons'),
                    'uppercase' => esc_html__('Uppercase', 'king-addons'),
                    'caps' => esc_html__('Capitalize', 'king-addons'),
                ],
                'default' => '',
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
                'name' => 'label_typo',
                'selector' => '{{WRAPPER}} .ka-woo-product-meta__label',
                'label' => esc_html__('Label Typography', 'king-addons'),
            ]
        );

        $this->add_control(
            'label_color',
            [
                'label' => esc_html__('Label Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-product-meta__label' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'value_typo',
                'selector' => '{{WRAPPER}} .ka-woo-product-meta__value',
                'label' => esc_html__('Value Typography', 'king-addons'),
            ]
        );

        $this->add_control(
            'value_color',
            [
                'label' => esc_html__('Value Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-product-meta__value' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .ka-woo-product-meta__value a' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'item_gap',
            [
                'label' => esc_html__('Items gap', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => ['min' => 0, 'max' => 30],
                ],
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-product-meta__item' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render(): void
    {
        if (!class_exists('WooCommerce') || !function_exists('wc_get_product_category_list') || !function_exists('wc_get_product_tag_list')) {
            return;
        }

        $product = $this->get_product();
        if (!$product) {
            $this->render_missing_product_notice();
            return;
        }

        $settings = $this->get_settings_for_display();
        $can_pro = king_addons_can_use_pro();

        $items = [];
        $sep = sanitize_text_field((string) ($settings['separator'] ?? ', '));

        if (!empty($settings['show_categories'])) {
            $cats = wc_get_product_category_list($product->get_id(), $sep);
            if ($cats) {
                $items[] = [
                    'label' => $settings['label_categories'] ?: esc_html__('Category:', 'king-addons'),
                    'value' => $cats,
                ];
            }
        }

        if (!empty($settings['show_tags'])) {
            $tags = wc_get_product_tag_list($product->get_id(), $sep);
            if ($tags) {
                $items[] = [
                    'label' => $settings['label_tags'] ?: esc_html__('Tags:', 'king-addons'),
                    'value' => $tags,
                ];
            }
        }

        if (!empty($settings['show_sku']) && $can_pro) {
            $sku = $product->get_sku();
            if ($sku) {
                $items[] = [
                    'label' => $settings['label_sku'] ?: esc_html__('SKU:', 'king-addons'),
                    'value' => esc_html($sku),
                ];
            }
        }

        if (!empty($settings['show_brand']) && $can_pro) {
            $taxes = [];
            if (!empty($settings['brand_taxonomy'])) {
                $taxes[] = sanitize_key($settings['brand_taxonomy']);
            }
            if (!empty($settings['brand_fallback_taxonomy'])) {
                $taxes[] = sanitize_key($settings['brand_fallback_taxonomy']);
            }
            $taxes = array_unique(array_filter($taxes));
            foreach ($taxes as $tax) {
                $terms = get_the_terms($product->get_id(), $tax);
                if (!empty($terms) && !is_wp_error($terms)) {
                    $links = [];
                    foreach ($terms as $term) {
                        $links[] = '<a href="' . esc_url(get_term_link($term)) . '">' . esc_html($term->name) . '</a>';
                    }
                    $items[] = [
                        'label' => $settings['label_brand'] ?: esc_html__('Brand:', 'king-addons'),
                        'value' => implode($sep, $links),
                    ];
                    break;
                }
            }
        }

        if (empty($items)) {
            return;
        }

        $layout = $settings['layout'] ?? 'inline';
        if (in_array($layout, ['stacked', 'inline_no_label'], true) && !$can_pro) {
            $layout = 'inline';
        }

        $format_preset = $settings['format_preset'] ?? '';
        if (!empty($format_preset) && !$can_pro) {
            $format_preset = '';
        }

        $wrapper_classes = ['ka-woo-product-meta', 'ka-woo-product-meta--' . $layout];
        if (!empty($format_preset)) {
            $wrapper_classes[] = 'ka-woo-product-meta--format-' . sanitize_html_class($format_preset);
        }
        if (!empty($settings['label_transform']) && $can_pro) {
            $wrapper_classes[] = 'ka-woo-product-meta--label-' . sanitize_html_class($settings['label_transform']);
        }

        echo '<div class="' . esc_attr(implode(' ', $wrapper_classes)) . '">';
        foreach ($items as $item) {
            echo '<div class="ka-woo-product-meta__item">';
            if ('inline_no_label' !== $layout) {
                echo '<span class="ka-woo-product-meta__label">' . esc_html($item['label']) . '</span> ';
            }
            echo '<span class="ka-woo-product-meta__value">' . wp_kses_post($item['value']) . '</span>';
            echo '</div>';
        }
        echo '</div>';
    }
}







