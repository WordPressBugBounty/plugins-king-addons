<?php
/**
 * Facet Meta/ACF filter widget (placeholder).
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use King_Addons\Woo_Builder\Context as Woo_Context;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders meta/ACF filter controls for faceted filters.
 */
class Facet_Meta extends Widget_Base
{
    public function get_name(): string
    {
        return 'facet_meta';
    }

    public function get_title(): string
    {
        return esc_html__('Custom Field Filter', 'king-addons');
    }

    /**
     * Keywords.
     *
     * @return array<int, string>
     */
    public function get_keywords(): array
    {
        return ['shop', 'product', 'filter', 'filters', 'ajax', 'woocommerce', 'acf', 'meta', 'sku', 'faceted', 'smart filters'];
    }

    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-facet-meta';
    }

    public function get_categories(): array
    {
        return ['king-addons'];
    }

    public function get_script_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-facet-meta-script'];
    }

    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-facet-meta-style'];
    }

        public function get_custom_help_url()
        {
            return 'mailto:bug@kingaddons.com?subject=Bug Report - King Addons&body=Please describe the issue';
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

        Core::renderUpgradeProSection($this, Controls_Manager::RAW_HTML, 'facet-meta', 'facet_meta_pro_notice');

        $this->add_control(
            'query_id',
            [
                'label' => esc_html__('Shop Filters ID', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'description' => esc_html__('Must match the Shop Filters ID on the product grid.', 'king-addons'),
            ]
        );

        $this->add_control(
            'meta_key',
            [
                'label' => esc_html__('Meta/ACF key', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => 'custom_field',
            ]
        );

        $this->add_control(
            'control_type',
            [
                'label' => esc_html__('Control type', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'text' => esc_html__('Text', 'king-addons'),
                    'select' => esc_html__('Select', 'king-addons'),
                    'range' => esc_html__('Range', 'king-addons'),
                ],
                'default' => 'text',
            ]
        );

        $this->add_control(
            'placeholder',
            [
                'label' => esc_html__('Placeholder', 'king-addons'),
                'type' => Controls_Manager::TEXT,
            ]
        );

        $this->end_controls_section();

        require_once KING_ADDONS_PATH . 'includes/helpers/Faceted/Style_Controls.php';
        Facet_Style_Controls::fields($this, '{{WRAPPER}} .ka-facet-meta__input, {{WRAPPER}} .ka-facet-meta__select, {{WRAPPER}} .ka-facet-meta__range input');
    }

    protected function render(): void
    {
        if (!function_exists('king_addons_can_use_pro') || !king_addons_can_use_pro()) {
            if (class_exists(Woo_Context::class) && Woo_Context::is_editor()) {
                echo '<div class="king-addons-woo-builder-notice">' . esc_html__('Custom Field Filter is available in Pro.', 'king-addons') . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }
            return;
        }

        $settings = $this->get_settings_for_display();
        $query_id = sanitize_title($settings['query_id'] ?? '');
        $meta_key = sanitize_key($settings['meta_key'] ?? '');
        if (empty($query_id) || empty($meta_key)) {
            if (class_exists(Woo_Context::class) && Woo_Context::is_editor()) {
                echo '<div class="king-addons-woo-builder-notice">' . esc_html__('Set Query ID and Meta key.', 'king-addons') . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }
            return;
        }

        $type = $settings['control_type'] ?? 'text';
        $placeholder = $settings['placeholder'] ?? '';
        if ('_sku' === $meta_key) {
            $meta_label = __('SKU', 'king-addons');
        } else {
            $meta_label = ucwords(str_replace(['_', '-'], ' ', (string) preg_replace('/^ka_/', '', $meta_key)));
        }
        $shared = ' data-ka-filters-query-id="' . esc_attr($query_id) . '" data-ka-filter-type="meta" data-ka-meta-key="' . esc_attr($meta_key) . '" data-ka-meta-label="' . esc_attr($meta_label) . '"';

        echo '<div class="ka-facet-meta">';
        if ('text' === $type) {
            echo '<input type="text" class="ka-facet-meta__input"' . $shared . ' data-ka-meta-role="equals" placeholder="' . esc_attr($placeholder) . '" />';
        } elseif ('select' === $type) {
            $ph = $placeholder ?: __('Any', 'king-addons');
            echo '<select class="ka-facet-meta__select"' . $shared . ' data-ka-meta-role="equals" data-placeholder="' . esc_attr($ph) . '"><option value="">' . esc_html($ph) . '</option></select>';
        } elseif ('range' === $type) {
            echo '<div class="ka-facet-meta__range">';
            echo '<input type="number" class="ka-facet-meta__range-min"' . $shared . ' data-ka-meta-role="min" placeholder="' . esc_attr__('Min', 'king-addons') . '" />';
            echo '<input type="number" class="ka-facet-meta__range-max"' . $shared . ' data-ka-meta-role="max" placeholder="' . esc_attr__('Max', 'king-addons') . '" />';
            echo '</div>';
        }
        echo '</div>';
    }
}





