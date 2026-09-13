<?php
/**
 * Woo Products Pagination widget.
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Displays WooCommerce pagination.
 */
class Woo_Products_Pagination extends Abstract_Archive_Widget
{
    public function get_name(): string
    {
        return 'woo_products_pagination';
    }

    public function get_title(): string
    {
        return esc_html__('Products Pagination', 'king-addons');
    }

    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-woo-products-pagination';
    }

    public function get_categories(): array
    {
        return ['king-addons-woo-builder'];
    }

    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-products-pagination-style'];
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
            'query_id',
            [
                'label' => sprintf(__('Query ID %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::TEXT,
                'description' => esc_html__('Match a Products Grid Query ID. Leave empty to paginate the first Products Grid on this archive, or the main shop query if there is none.', 'king-addons'),
            ]
        );

        $this->add_control(
            'alignment',
            [
                'label' => esc_html__('Alignment', 'king-addons'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => esc_html__('Left', 'king-addons'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'king-addons'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'right' => [
                        'title' => esc_html__('Right', 'king-addons'),
                        'icon' => 'eicon-text-align-right',
                    ],
                ],
                'default' => 'center',
                'selectors' => [
                    // WooCommerce's own stylesheet centres nav.woocommerce-pagination,
                    // which sits inside the wrapper - aligning only the wrapper
                    // left the list centred whatever the control said.
                    '{{WRAPPER}} .ka-woo-pagination' => 'text-align: {{VALUE}};',
                    '{{WRAPPER}} .ka-woo-pagination .woocommerce-pagination' => 'text-align: {{VALUE}};',
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
        $query_id = $can_pro ? sanitize_title((string) ($settings['query_id'] ?? '')) : '';
        $stats = class_exists(__NAMESPACE__ . '\\Woo_Products_Grid') ? Woo_Products_Grid::query_stats($query_id) : null;

        if ($stats && (int) ($stats['max_num_pages'] ?? 1) > 1 && !empty($stats['page_arg'])) {
            $output = paginate_links([
                'base' => add_query_arg($stats['page_arg'], '%#%'),
                'format' => '',
                'total' => (int) $stats['max_num_pages'],
                'current' => max(1, (int) $stats['paged']),
                'prev_text' => '&laquo;',
                'next_text' => '&raquo;',
            ]);
            $output = is_string($output) ? trim($output) : '';
            if ('' !== $output) {
                echo '<div class="ka-woo-pagination">' . $output . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                return;
            }
        }

        // woocommerce_pagination() prints nothing when there is only one page -
        // including in the editor, where the widget then looks broken rather
        // than simply having nothing to page through.
        ob_start();
        woocommerce_pagination();
        $output = trim((string) ob_get_clean());

        if ('' === $output) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="king-addons-woo-builder-notice">'
                    . esc_html__('Pagination appears once the archive has more than one page.', 'king-addons')
                    . '</div>';
            }
            return;
        }

        // WooCommerce's own markup, not user input.
        echo '<div class="ka-woo-pagination">' . $output . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}






