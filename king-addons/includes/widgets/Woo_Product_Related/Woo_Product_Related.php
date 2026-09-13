<?php
/**
 * Woo Product Related widget.
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Displays related products grid.
 */
class Woo_Product_Related extends Abstract_Single_Widget
{
    public function get_name(): string
    {
        return 'woo_product_related';
    }

    /**
     * Style dependencies.
     *
     * @return array<int, string>
     */
    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-loop-style'];
    }

    public function get_title(): string
    {
        return esc_html__('Related Products', 'king-addons');
    }

    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-woo-product-related';
    }

    public function get_categories(): array
    {
        return ['king-addons-woo-builder'];
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
            'products_limit',
            [
                'label' => esc_html__('Products limit', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'default' => 4,
                'min' => 1,
            ]
        );

        $this->add_control(
            'columns',
            [
                'label' => esc_html__('Columns', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'default' => 4,
                'min' => 1,
                'max' => 6,
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

        $settings = $this->get_settings_for_display();
        // ?: not ??: a cleared number field is '' rather than null, and
        // (int) '' is 0 - both settings silently collapsed to one.
        $limit = max(1, (int) (($settings['products_limit'] ?? null) ?: 4));
        $columns = max(1, min(6, (int) (($settings['columns'] ?? null) ?: 4)));

        $related_ids = wc_get_related_products($product->get_id(), $limit);
        if (empty($related_ids)) {
            // Related products depend on shared categories or tags; an empty
            // result in the editor should say so rather than draw nothing.
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="king-addons-woo-builder-notice">'
                    . esc_html__('No related products found - they come from products sharing a category or tag.', 'king-addons')
                    . '</div>';
            }
            return;
        }

        $shortcode = sprintf('[products ids="%s" columns="%d"]', implode(',', $related_ids), $columns);
        echo '<div class="ka-woo-related">';
        echo do_shortcode($shortcode); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '</div>';
    }
}







