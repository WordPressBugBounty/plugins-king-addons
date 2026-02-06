<?php
/**
 * Woo Products Result Count widget.
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
 * Shows WooCommerce result count.
 */
class Woo_Products_Result_Count extends Abstract_Archive_Widget
{
    public function get_name(): string
    {
        return 'woo_products_result_count';
    }

    public function get_title(): string
    {
        return esc_html__('Products Result Count', 'king-addons');
    }

    public function get_icon(): string
    {
        return 'eicon-editor-info';
    }

    public function get_categories(): array
    {
        return ['king-addons-woo-builder'];
    }

    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-products-result-count-style'];
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
            'custom_format',
            [
                'label' => sprintf(__('Custom format %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::TEXT,
                'description' => esc_html__('Use {first}, {last}, {total} placeholders.', 'king-addons'),
                'default' => esc_html__('Showing {first}–{last} of {total} results', 'king-addons'),
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
                'name' => 'typography',
                'selector' => '{{WRAPPER}} .ka-woo-result-count',
            ]
        );

        $this->add_control(
            'color',
            [
                'label' => esc_html__('Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-result-count' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render(): void
    {
        if (!class_exists('WooCommerce') || !function_exists('is_shop') || !function_exists('is_product_taxonomy')) {
            return;
        }

        if (!$this->should_render()) {
            $this->render_missing_archive_notice();
            return;
        }

        $settings = $this->get_settings_for_display();
        $can_pro = king_addons_can_use_pro();

        $format = $settings['custom_format'] ?? '';
        if (!$can_pro || empty($format)) {
            echo '<div class="ka-woo-result-count">';
            woocommerce_result_count();
            echo '</div>';
            return;
        }

        global $wp_query;
        $total = (int) ($wp_query->found_posts ?? 0);
        $per_page = (int) get_query_var('posts_per_page', wc_get_default_products_per_row());
        $paged = (int) max(1, get_query_var('paged', 1));

        $first = 0;
        $last = 0;
        if ($total > 0) {
            $first = (($paged - 1) * $per_page) + 1;
            $last = min($total, $paged * $per_page);
        }

        $output = str_replace(
            ['{first}', '{last}', '{total}'],
            [number_format_i18n($first), number_format_i18n($last), number_format_i18n($total)],
            $format
        );

        echo '<div class="ka-woo-result-count">' . esc_html($output) . '</div>';
    }
}






