<?php
/**
 * Woo Product ACF / Attribute Field widget (Free placeholder).
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use King_Addons\Core;
use King_Addons\Woo_Builder\Context as Woo_Context;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders basic product attribute/meta field (limited in Free).
 */
class Woo_Product_ACF_Field extends Abstract_Single_Widget
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'woo_product_acf_field';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Product ACF / Attribute Field', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'eicon-database';
    }

    /**
     * Widget categories.
     *
     * @return array<int,string>
     */
    public function get_categories(): array
    {
        return ['king-addons-woo-builder'];
    }

    /**
     * Style dependencies.
     *
     * @return array<int,string>
     */
    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-product-acf-field-style'];
    }

    /**
     * Script dependencies.
     *
     * @return array<int,string>
     */
    public function get_script_depends(): array
    {
        return [];
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
                'label' => esc_html__('Content', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'source',
            [
                'label' => esc_html__('Source', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'attribute' => esc_html__('Product Attribute', 'king-addons'),
                    'meta' => sprintf(__('Meta Field (Pro) %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                    'acf' => sprintf(__('ACF Field (Pro) %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                ],
                'default' => 'attribute',
            ]
        );

        $this->add_control(
            'attribute_slug',
            [
                'label' => esc_html__('Attribute Slug', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => 'pa_color',
                'description' => esc_html__('Enter taxonomy slug like pa_color or pa_size.', 'king-addons'),
                'condition' => [
                    'source' => 'attribute',
                ],
            ]
        );

        $this->add_control(
            'show_label',
            [
                'label' => esc_html__('Show Label', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
                'condition' => [
                    'source' => 'attribute',
                ],
            ]
        );

        $this->add_control(
            'custom_label',
            [
                'label' => esc_html__('Label Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => esc_html__('Color', 'king-addons'),
                'condition' => [
                    'source' => 'attribute',
                    'show_label' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'fallback',
            [
                'label' => esc_html__('Fallback Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => esc_html__('Not set', 'king-addons'),
            ]
        );

        $this->add_control(
            'separator',
            [
                'label' => esc_html__('Values Separator', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => ', ',
                'condition' => [
                    'source' => 'attribute',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render output.
     *
     * @return void
     */
    protected function render(): void
    {
        if (!class_exists('WooCommerce') || !function_exists('wc_get_product_terms')) {
            return;
        }

        if (!Woo_Context::maybe_render_context_notice('single_product')) {
            return;
        }

        $settings = $this->get_settings_for_display();
        $product = $this->get_product();
        if (!$product) {
            $this->render_missing_product_notice();
            return;
        }

        $source = $settings['source'] ?? 'attribute';
        $is_pro_only = in_array($source, ['acf', 'meta'], true);
        if ($is_pro_only && !king_addons_can_use_pro()) {
            if (Woo_Context::is_editor()) {
                Core::renderProFeaturesSection(
                    $this,
                    '',
                    Controls_Manager::RAW_HTML,
                    'woo-product-acf-field',
                    [
                        'Display any ACF field for the product',
                        'Render taxonomy or custom attributes with labels',
                        'Before/after text and fallback value',
                        'Works inside Woo Builder Single Product templates',
                    ]
                );
            }
            return;
        }

        if ($is_pro_only && king_addons_can_use_pro()) {
            if (Woo_Context::is_editor()) {
                echo '<div class="king-addons-woo-builder-notice">';
                echo esc_html__('Use the Pro widget to render ACF or meta fields.', 'king-addons');
                echo '</div>';
            }
            return;
        }

        $output = '';
        if ('attribute' === $source) {
            $attribute_slug = sanitize_key($settings['attribute_slug'] ?? '');
            if ($attribute_slug) {
                $terms = wc_get_product_terms($product->get_id(), $attribute_slug, ['fields' => 'names']);
                if (!empty($terms) && is_array($terms)) {
                    $sep = sanitize_text_field((string) ($settings['separator'] ?? ', '));
                    $output = implode($sep, $terms);
                }
            }
        }

        if ('' === $output) {
            $fallback = $settings['fallback'] ?? '';
            if ($fallback === '') {
                return;
            }
            $output = $fallback;
        }

        $label = '';
        if (!empty($settings['show_label'])) {
            $label_text = $settings['custom_label'] ?: ($settings['attribute_slug'] ?? '');
            if ($label_text) {
                $label = '<span class="ka-woo-product-field__label">' . esc_html($label_text) . '</span>';
            }
        }

        echo '<div class="ka-woo-product-field">';
        if ($label) {
            echo wp_kses_post($label);
        }
        echo '<span class="ka-woo-product-field__value">' . esc_html($output) . '</span>';
        echo '</div>';
    }
}





