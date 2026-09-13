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
        return 'king-addons-icon king-addons-woo-products-result-count';
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
            'query_id',
            [
                'label' => sprintf(__('Query ID %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::TEXT,
                'description' => esc_html__('Match a Products Grid Query ID. Leave empty to use the first Products Grid on this archive, or the main shop query if there is none.', 'king-addons'),
            ]
        );

        $this->add_control(
            'custom_format',
            [
                'label' => sprintf(__('Custom format %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::TEXT,
                'description' => esc_html__('Use {first}/{from}, {last}/{to}, and {total} placeholders.', 'king-addons'),
                'default' => __('Showing {first}–{last} of {total} results', 'king-addons'),
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
        $query_id = $can_pro ? sanitize_title((string) ($settings['query_id'] ?? '')) : '';
        $stats = self::resolve_grid_stats($query_id);

        $format = $settings['custom_format'] ?? '';
        if (!$can_pro || empty($format)) {
            if ($stats) {
                echo '<div class="ka-woo-result-count">' . esc_html(self::format_woo_count($stats)) . '</div>';
                return;
            }
            // woocommerce_result_count() prints nothing when the loop has no
            // total, which is the case in the editor - and a widget that draws
            // nothing there looks broken rather than context-dependent.
            ob_start();
            woocommerce_result_count();
            $woo_output = trim((string) ob_get_clean());

            if ('' === $woo_output) {
                if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                    echo '<div class="king-addons-woo-builder-notice">'
                        . esc_html__('The result count appears on a product archive with results to count.', 'king-addons')
                        . '</div>';
                }
                return;
            }

            // WooCommerce's own markup, not user input - running it through
            // wp_kses_post() only stripped the aria-relevant it sets.
            echo '<div class="ka-woo-result-count">' . $woo_output . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            return;
        }

        global $wp_query;
        $total = (int) ($wp_query->found_posts ?? 0);
        $per_page = (int) get_query_var('posts_per_page');
        if ($per_page < 1 && function_exists('wc_get_loop_prop')) {
            $per_page = (int) wc_get_loop_prop('per_page', 0);
        }
        if ($per_page < 1) {
            $per_page = (int) get_option('posts_per_page', 10);
        }
        $per_page = max(1, $per_page);
        $paged = (int) max(1, get_query_var('paged', 1));

        if ($stats) {
            $total = (int) $stats['found_posts'];
            $per_page = max(1, (int) $stats['per_page']);
            $paged = max(1, (int) $stats['paged']);
        }

        $first = 0;
        $last = 0;
        if ($total > 0) {
            $first = (($paged - 1) * $per_page) + 1;
            $last = min($total, $paged * $per_page);
        }

        $output = str_replace(
            ['{first}', '{from}', '{last}', '{to}', '{total}'],
            [
                number_format_i18n($first),
                number_format_i18n($first),
                number_format_i18n($last),
                number_format_i18n($last),
                number_format_i18n($total),
            ],
            $format
        );

        echo '<div class="ka-woo-result-count">' . esc_html($output) . '</div>';
    }

    /**
     * Stats from a Products Grid on this archive, if any.
     *
     * @param string $query_id Shared Query ID, or '' to auto-pick.
     *
     * @return array<string,mixed>|null
     */
    private static function resolve_grid_stats(string $query_id): ?array
    {
        if (!class_exists(__NAMESPACE__ . '\\Woo_Products_Grid')) {
            return null;
        }
        return Woo_Products_Grid::query_stats($query_id);
    }

    /**
     * WooCommerce-style count sentence from grid stats.
     *
     * @param array<string,mixed> $stats Grid query stats.
     *
     * @return string
     */
    private static function format_woo_count(array $stats): string
    {
        $total = (int) ($stats['found_posts'] ?? 0);
        $per_page = max(1, (int) ($stats['per_page'] ?? 1));
        $paged = max(1, (int) ($stats['paged'] ?? 1));
        if ($total < 1) {
            return '';
        }
        $first = (($paged - 1) * $per_page) + 1;
        $last = min($total, $paged * $per_page);
        /* translators: 1: first item, 2: last item, 3: total items */
        return sprintf(
            esc_html__('Showing %1$d–%2$d of %3$d results', 'king-addons'),
            $first,
            $last,
            $total
        );
    }
}






