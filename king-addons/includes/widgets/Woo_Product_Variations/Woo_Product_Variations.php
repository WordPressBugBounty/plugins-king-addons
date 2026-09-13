<?php
/**
 * Woo Product Variations / Swatches widget.
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use King_Addons\Woo_Builder\Context as Woo_Context;
use WC_Product_Variable;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders variation attributes selector.
 */
class Woo_Product_Variations extends Abstract_Single_Widget
{
        /**
         * Widget slug.
         *
         * @return string
         */
    public function get_name(): string
    {
        return 'woo_product_variations';
    }

        /**
         * Widget title.
         *
         * @return string
         */
    public function get_title(): string
    {
        return esc_html__('Product Variations', 'king-addons');
    }

        /**
         * Widget icon.
         *
         * @return string
         */
    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-woo-product-variations';
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
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-product-variations-style'];
    }

    /**
     * Script dependencies.
     *
     * @return array<int,string>
     */
    public function get_script_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-product-variations-script'];
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
            'swatches',
            [
                'label' => esc_html__('Enable swatches', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'swatch_source',
            [
                'label' => esc_html__('Swatch source', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'auto' => esc_html__('Term meta (color/image) fallback to text', 'king-addons'),
                    'text' => esc_html__('Text only', 'king-addons'),
                ],
                'default' => 'auto',
                'condition' => [
                    'swatches' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'show_selected',
            [
                'label' => esc_html__('Show selected value', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'unavailable_behavior',
            [
                'label' => esc_html__('Unavailable behavior', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'disable' => esc_html__('Disable', 'king-addons'),
                    'hide' => esc_html__('Hide', 'king-addons'),
                ],
                'default' => 'disable',
            ]
        );

        $this->add_control(
            'swatch_shape',
            [
                'label' => esc_html__('Swatch shape', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'square' => esc_html__('Square', 'king-addons'),
                    'round' => esc_html__('Round', 'king-addons'),
                ],
                'default' => 'square',
                'condition' => [
                    'swatches' => 'yes',
                ],
            ]
        );

        $this->add_responsive_control(
            'swatch_size',
            [
                'label' => esc_html__('Swatch size (px)', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => ['min' => 16, 'max' => 80],
                ],
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-variations__swatch' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'swatches' => 'yes',
                ],
            ]
        );

        $this->add_responsive_control(
            'swatch_spacing',
            [
                'label' => esc_html__('Swatch spacing (px)', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => ['min' => 0, 'max' => 24],
                ],
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-variations__swatches' => 'gap: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'swatches' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Find any published variable product, for editor preview only.
     *
     * @return WC_Product_Variable|null
     */
    protected function find_variable_product_for_preview(): ?WC_Product_Variable
    {
        if (!function_exists('wc_get_products')) {
            return null;
        }

        $found = wc_get_products([
            'status' => 'publish',
            'type' => 'variable',
            'limit' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        $candidate = $found[0] ?? null;

        return $candidate instanceof WC_Product_Variable ? $candidate : null;
    }

    protected function render(): void
    {
        if (
            !class_exists('WooCommerce') ||
            !function_exists('wc_attribute_taxonomy_name') ||
            !function_exists('wc_get_product_terms') ||
            !function_exists('wc_attribute_label') ||
            !function_exists('wc_get_template')
        ) {
            return;
        }

        $product = $this->get_product();
        if (!$product) {
            $this->render_missing_product_notice();
            return;
        }

        $is_editor = class_exists(Woo_Context::class) && Woo_Context::is_editor();

        if (!$product instanceof WC_Product_Variable && $is_editor) {
            // The builder picks the preview product on its own and the author
            // cannot choose it, so a store whose newest product is simple would
            // show this widget as broken. Borrow a variable product to preview.
            $preview = $this->find_variable_product_for_preview();
            if ($preview instanceof WC_Product_Variable) {
                $GLOBALS['product'] = $preview;
                $product = $preview;
            }
        }

        if (!$product instanceof WC_Product_Variable) {
            if ($is_editor) {
                echo '<div class="king-addons-woo-builder-notice">' . esc_html__('This widget needs a variable product. Add one to preview it here.', 'king-addons') . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }
            return;
        }

        $settings = $this->get_settings_for_display();

        // Swatches ship in the free tier: both freemium competitors give them away,
        // and it is the first thing compared side by side.
        $use_swatches = !empty($settings['swatches']);
        $unavailable_behavior = $settings['unavailable_behavior'] ?? 'disable';
        $unavailable_behavior = in_array($unavailable_behavior, ['disable', 'hide'], true) ? $unavailable_behavior : 'disable';
        $show_selected = !empty($settings['show_selected']);
        $shape = $settings['swatch_shape'] ?? 'square';

        // Build the swatch map from the product's attributes and terms.
        $swatch_map = [];
        if ($use_swatches) {
            $attributes = $product->get_variation_attributes();
            foreach ($attributes as $attr_name => $options) {
                // get_variation_attributes() hands back the taxonomy name as-is
                // for global attributes (pa_color) and the plain label for custom
                // ones (Size). Only the first kind has terms to read colours from.
                $taxonomy = taxonomy_exists($attr_name) ? $attr_name : '';
                $swatches = [];
                if ('' !== $taxonomy) {
                    $terms = wc_get_product_terms($product->get_id(), $taxonomy, ['fields' => 'all']);
                    foreach ($terms as $term) {
                        $color_raw = get_term_meta($term->term_id, 'color', true);
                        $color = '';
                        if (is_string($color_raw)) {
                            $hex = sanitize_hex_color($color_raw);
                            $color = $hex ? $hex : '';
                        }
                        $thumb_id = get_term_meta($term->term_id, 'thumbnail_id', true);
                        $image = $thumb_id ? esc_url_raw(wp_get_attachment_image_url($thumb_id, 'thumbnail')) : '';
                        $swatches[$term->slug] = [
                            'label' => sanitize_text_field($term->name),
                            'color' => $color,
                            'image' => $image,
                        ];
                    }
                } else {
                    foreach ($options as $opt) {
                        $swatches[$opt] = [
                            // The attribute name already labels the row; repeating
                            // it inside every swatch reads as "Size Small".
                            'label' => sanitize_text_field($opt),
                            'color' => '',
                            'image' => '',
                        ];
                    }
                }
                // Key the map the way WooCommerce names the select it belongs to
                // (wc_dropdown_variation_attribute_options uses
                // "attribute_" . sanitize_title($name)); the script looks the
                // swatches up by that name.
                $swatch_map['attribute_' . sanitize_title($attr_name)] = $swatches;
            }
        }

        $data = [
            'swatches' => $use_swatches ? 'yes' : 'no',
            'unavailable' => $unavailable_behavior,
            'show_selected' => $show_selected ? 'yes' : 'no',
            'shape' => $shape,
            'map' => $swatch_map,
            'source' => in_array(($settings['swatch_source'] ?? 'auto'), ['auto', 'text'], true) ? ($settings['swatch_source'] ?? 'auto') : 'auto',
        ];

        $classes = 'ka-woo-variations';
        if (Woo_Context::template_has_widget('woo_product_add_to_cart', 'single_product')) {
            $classes .= ' ka-woo-variations--atc-external';
        }

        echo '<div class="' . esc_attr($classes) . '" id="ka-satc-form-anchor" data-swatches="' . ($use_swatches ? 'yes' : 'no') . '" data-unavailable="' . esc_attr($unavailable_behavior) . '" data-swatches-map="' . esc_attr(wp_json_encode($data)) . '">';
        wc_get_template('single-product/add-to-cart/variable.php', [
            'available_variations' => $product->get_available_variations(),
            'attributes' => $product->get_variation_attributes(),
            'selected_attributes' => $product->get_default_attributes(),
        ]);
        echo '</div>';
    }
}







