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
        return esc_html__('Facet Meta/ACF', 'king-addons');
    }

    public function get_icon(): string
    {
        return 'eicon-filter';
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

        $this->add_control(
            'query_id',
            [
                'label' => esc_html__('Query ID', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'description' => esc_html__('Must match the grid Query ID.', 'king-addons'),
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
    }

    protected function render(): void
    {
        $settings = $this->get_settings_for_display();
        $query_id = $settings['query_id'] ?? '';
        $meta_key = $settings['meta_key'] ?? '';
        if (empty($query_id) || empty($meta_key)) {
            if (class_exists(Woo_Context::class) && Woo_Context::is_editor()) {
                echo '<div class="king-addons-woo-builder-notice">' . esc_html__('Set Query ID and Meta key.', 'king-addons') . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }
            return;
        }

        $type = $settings['control_type'] ?? 'text';
        $placeholder = $settings['placeholder'] ?? '';
        $data = ' data-ka-filters-query-id="' . esc_attr($query_id) . '" data-ka-filter-type="meta" data-ka-meta-key="' . esc_attr($meta_key) . '"';

        echo '<div class="ka-facet-meta" ' . $data . '>';
        if ('text' === $type) {
            echo '<input type="text" class="ka-facet-meta__input" placeholder="' . esc_attr($placeholder) . '" />';
        } elseif ('select' === $type) {
            $ph = $placeholder ?: __('Any', 'king-addons');
            echo '<select class="ka-facet-meta__select" data-placeholder="' . esc_attr($ph) . '" data-ka-meta-key="' . esc_attr($meta_key) . '" data-ka-filters-query-id="' . esc_attr($query_id) . '"><option value="">' . esc_html($ph) . '</option></select>';
        } elseif ('range' === $type) {
            echo '<div class="ka-facet-meta__range">';
            echo '<input type="number" class="ka-facet-meta__range-min" placeholder="' . esc_attr__('Min', 'king-addons') . '" />';
            echo '<input type="number" class="ka-facet-meta__range-max" placeholder="' . esc_attr__('Max', 'king-addons') . '" />';
            echo '</div>';
        }
        echo '</div>';
    }
}





