<?php
/**
 * Woo Archive Description widget.
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
 * Displays archive description.
 */
class Woo_Archive_Description extends Abstract_Archive_Widget
{
    public function get_name(): string
    {
        return 'woo_archive_description';
    }

    public function get_title(): string
    {
        return esc_html__('Archive Description', 'king-addons');
    }

    public function get_icon(): string
    {
        return 'eicon-editor-paragraph';
    }

    public function get_categories(): array
    {
        return ['king-addons-woo-builder'];
    }

    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-archive-description-style'];
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
            'trim_words',
            [
                'label' => sprintf(__('Trim words %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::NUMBER,
                'min' => 10,
            ]
        );

        $this->add_control(
            'read_more_text',
            [
                'label' => esc_html__('Read more text (Pro)', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Read more', 'king-addons'),
            ]
        );

        $this->add_control(
            'format',
            [
                'label' => sprintf(__('Custom format %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::TEXTAREA,
                'description' => esc_html__('Use {description}, {title}, {count}.', 'king-addons'),
                'rows' => 3,
            ]
        );

        $this->add_control(
            'source',
            [
                'label' => sprintf(__('Source %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'auto' => esc_html__('Auto (term/shop description)', 'king-addons'),
                    'custom' => esc_html__('Custom text', 'king-addons'),
                ],
                'default' => 'auto',
            ]
        );

        $this->add_control(
            'custom_text',
            [
                'label' => esc_html__('Custom text', 'king-addons'),
                'type' => Controls_Manager::TEXTAREA,
                'rows' => 4,
                'condition' => [
                    'source' => 'custom',
                ],
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
                'selector' => '{{WRAPPER}} .ka-woo-archive-description',
            ]
        );

        $this->add_control(
            'color',
            [
                'label' => esc_html__('Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-archive-description' => 'color: {{VALUE}};',
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
                    '{{WRAPPER}} .ka-woo-archive-description' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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

        $desc = '';
        $title = woocommerce_page_title(false);
        if ('custom' === ($settings['source'] ?? 'auto')) {
            $desc = $settings['custom_text'] ?? '';
        } else {
            $desc = term_description();
            if (is_shop()) {
                $shop_id = wc_get_page_id('shop');
                $desc = get_post_field('post_content', $shop_id);
            }
        }

        if (empty($desc)) {
            return;
        }

        $trim = !empty($settings['trim_words']) && $can_pro ? (int) $settings['trim_words'] : 0;
        $trimmed = false;
        if ($trim > 0) {
            $trimmed = true;
            $desc = wp_trim_words(wp_strip_all_tags($desc), $trim, '');
            $desc = wpautop($desc);
        }

        if (!empty($settings['format']) && $can_pro) {
            global $wp_query;
            $count = isset($wp_query->found_posts) ? (int) $wp_query->found_posts : 0;
            $desc = str_replace(
                ['{description}', '{title}', '{count}'],
                [$desc, $title, number_format_i18n($count)],
                $settings['format']
            );
        }

        echo '<div class="ka-woo-archive-description">';
        echo wp_kses_post($desc);
        if ($trimmed && !empty($settings['read_more_text']) && $can_pro) {
            echo '<a class="ka-woo-archive-description__readmore" href="#">' . esc_html($settings['read_more_text']) . '</a>';
        }
        echo '</div>';
    }
}






