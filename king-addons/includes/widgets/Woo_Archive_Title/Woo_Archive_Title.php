<?php
/**
 * Woo Archive Title widget.
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
 * Displays archive title.
 */
class Woo_Archive_Title extends Abstract_Archive_Widget
{
    public function get_name(): string
    {
        return 'woo_archive_title';
    }

    public function get_title(): string
    {
        return esc_html__('Archive Title', 'king-addons');
    }

    public function get_icon(): string
    {
        return 'eicon-heading';
    }

    public function get_categories(): array
    {
        return ['king-addons-woo-builder'];
    }

    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-archive-title-style'];
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
            'html_tag',
            [
                'label' => esc_html__('HTML Tag', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'h1' => 'H1',
                    'h2' => 'H2',
                    'h3' => 'H3',
                    'div' => 'div',
                ],
                'default' => 'h1',
            ]
        );

        $this->add_control(
            'prefix',
            [
                'label' => sprintf(__('Prefix (Pro) %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
            ]
        );

        $this->add_control(
            'custom_format',
            [
                'label' => sprintf(__('Custom format %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::TEXT,
                'description' => esc_html__('Use {title} and {count} placeholders.', 'king-addons'),
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
                'name' => 'typography',
                'selector' => '{{WRAPPER}} .ka-woo-archive-title',
            ]
        );

        $this->add_control(
            'color',
            [
                'label' => esc_html__('Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-archive-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'margin',
            [
                'label' => esc_html__('Margin', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-archive-title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
        $tag = $settings['html_tag'] ?? 'h1';
        $title = woocommerce_page_title(false);
        $can_pro = king_addons_can_use_pro();
        if (!empty($settings['prefix']) && $can_pro) {
            $title = $settings['prefix'] . ' ' . $title;
        }

        if (!empty($settings['custom_format']) && $can_pro) {
            global $wp_query;
            $count = isset($wp_query->found_posts) ? (int) $wp_query->found_posts : 0;
            $formatted = str_replace(
                ['{title}', '{count}'],
                [$title, number_format_i18n($count)],
                $settings['custom_format']
            );
            $title = $formatted;
        }

        echo '<' . esc_html($tag) . ' class="ka-woo-archive-title">' . esc_html($title) . '</' . esc_html($tag) . '>';
    }
}






