<?php
/**
 * Woo Product Full Description widget.
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
 * Displays product full description.
 */
class Woo_Product_Full_Description extends Abstract_Single_Widget
{
    public function get_name(): string
    {
        return 'woo_product_full_description';
    }

    public function get_title(): string
    {
        return esc_html__('Product Full Description', 'king-addons');
    }

    public function get_icon(): string
    {
        return 'eicon-text-align-left';
    }

    public function get_categories(): array
    {
        return ['king-addons-woo-builder'];
    }

    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-product-full-description-style'];
    }

    /**
     * Script dependencies.
     *
     * @return array<int,string>
     */
    public function get_script_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-product-short-description-script'];
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
            'clean_output',
            [
                'label' => sprintf(__('Disable Woo formatting %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'enable_trim',
            [
                'label' => sprintf(__('Trim text %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'trim_words',
            [
                'label' => esc_html__('Trim words (Pro)', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 10,
                'default' => 80,
            ]
        );

        $this->add_control(
            'trim_type',
            [
                'label' => sprintf(__('Trim type %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'words' => esc_html__('Words', 'king-addons'),
                    'chars' => esc_html__('Characters', 'king-addons'),
                ],
                'default' => 'words',
            ]
        );

        $this->add_control(
            'read_more_text',
            [
                'label' => esc_html__('Read more text (Pro)', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Read full description', 'king-addons'),
            ]
        );

        $this->add_control(
            'read_less_text',
            [
                'label' => sprintf(__('Read less text %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Show less', 'king-addons'),
            ]
        );

        $this->add_control(
            'read_more_behavior',
            [
                'label' => sprintf(__('Read more behavior %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'anchor' => esc_html__('Anchor', 'king-addons'),
                    'toggle' => esc_html__('Expand/Collapse', 'king-addons'),
                ],
                'default' => 'anchor',
            ]
        );

        $this->add_control(
            'read_more_target',
            [
                'label' => sprintf(__('Read more anchor %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::TEXT,
                'default' => '#tab-description',
                'condition' => [
                    'read_more_behavior' => 'anchor',
                ],
            ]
        );

        $this->add_control(
            'line_clamp',
            [
                'label' => sprintf(__('Line clamp %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 12,
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
                'selector' => '{{WRAPPER}} .ka-woo-product-full-description',
            ]
        );

        $this->add_control(
            'color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-product-full-description' => 'color: {{VALUE}};',
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
                    '{{WRAPPER}} .ka-woo-product-full-description' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
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
        $can_pro = king_addons_can_use_pro();

        $content = $product->get_description();

        $clean = !empty($settings['clean_output']) && $can_pro;
        if ($clean) {
            $content = wp_kses_post($content);
        } else {
            $content = apply_filters('woocommerce_product_description', $content);
        }

        $trimmed = false;
        $full_output = $content;

        if (!empty($settings['enable_trim']) && $can_pro && !empty($settings['trim_words'])) {
            $trimmed = true;
            $trim_type = $settings['trim_type'] ?? 'words';
            if ('chars' === $trim_type) {
                $content = wp_html_excerpt(wp_strip_all_tags($content), (int) $settings['trim_words'], '');
                $content = wpautop(esc_html($content));
            } else {
                $content = wp_trim_words(wp_strip_all_tags($content), (int) $settings['trim_words'], '');
                $content = wpautop($content);
            }
        }

        if ('' === trim($content)) {
            return;
        }

        $clamp_lines = (!empty($settings['line_clamp']) && $can_pro) ? (int) $settings['line_clamp'] : 0;
        $behavior = $settings['read_more_behavior'] ?? 'anchor';
        $target = $settings['read_more_target'] ?? '#tab-description';
        $read_more = $settings['read_more_text'] ?? '';
        $read_less = $settings['read_less_text'] ?? '';

        $wrapper_classes = ['ka-woo-product-full-description'];
        if ($clamp_lines > 0) {
            $wrapper_classes[] = 'ka-woo-product-full-description--clamp';
        }

        echo '<div class="' . esc_attr(implode(' ', $wrapper_classes)) . '"';
        if ($clamp_lines > 0) {
            echo ' style="--ka-full-desc-lines:' . esc_attr((string) $clamp_lines) . '"';
        }
        echo '>';
        echo '<div class="ka-woo-product-full-description__content is-trimmed">';
        echo wp_kses_post($content);
        echo '</div>';
        echo '<div class="ka-woo-product-full-description__content is-full" hidden>';
        echo wp_kses_post($full_output);
        echo '</div>';

        if ($trimmed && $can_pro && !empty($read_more)) {
            if ('toggle' === $behavior) {
                $less_label = $read_less ?: esc_html__('Show less', 'king-addons');
                echo '<button type="button" class="ka-woo-product-full-description__toggle" data-less="' . esc_attr($less_label) . '" data-more="' . esc_attr($read_more) . '">' . esc_html($read_more) . '</button>';
            } else {
                echo '<a class="ka-woo-product-full-description__readmore" href="' . esc_url($target) . '">' . esc_html($read_more) . '</a>';
            }
        }
        echo '</div>';
    }
}







