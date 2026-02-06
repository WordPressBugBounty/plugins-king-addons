<?php
/**
 * Woo Product Cross Sell widget.
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Displays cross-sell products grid (useful when used on cart builder or single).
 */
class Woo_Product_Cross_Sell extends Abstract_Single_Widget
{
    public function get_name(): string
    {
        return 'woo_product_cross_sell';
    }

    public function get_title(): string
    {
        return esc_html__('Cross-sell Products', 'king-addons');
    }

    public function get_icon(): string
    {
        return 'eicon-products';
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
        $limit = max(1, (int) ($settings['products_limit'] ?? 4));
        $columns = max(1, min(6, (int) ($settings['columns'] ?? 4)));

        $ids = $product->get_cross_sell_ids();
        if (empty($ids)) {
            return;
        }
        $ids = array_slice($ids, 0, $limit);

        $shortcode = sprintf('[products ids="%s" columns="%d"]', implode(',', $ids), $columns);
        echo '<div class="ka-woo-cross-sell">';
        echo do_shortcode($shortcode); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '</div>';
    }
}







