<?php
/**
 * Theme Builder Featured Image widget (Free).
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Displays the current post featured image.
 */
class TB_Featured_Image extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-tb-featured-image';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('TB - Featured Image', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'eicon-post';
    }

    /**
     * Style dependencies.
     *
     * @return array<int, string>
     */
    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-tb-featured-image-style'];
    }

    /**
     * Script dependencies.
     *
     * @return array<int, string>
     */
    public function get_script_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-tb-featured-image-script'];
    }

    /**
     * Categories.
     *
     * @return array<int, string>
     */
    public function get_categories(): array
    {
        return ['king-addons'];
    }

    /**
     * Keywords.
     *
     * @return array<int, string>
     */
    public function get_keywords(): array
    {
        return ['featured image', 'post image', 'thumbnail', 'theme builder', 'king-addons'];
    }

        public function get_custom_help_url()
        {
            return 'mailto:bug@kingaddons.com?subject=Bug Report - King Addons&body=Please describe the issue';
        }

        /**
     * Register controls.
     *
     * @return void
     */
    public function register_controls(): void
    {
        $this->register_content_controls(false);
        $this->register_style_controls(false);
        $this->register_pro_notice_controls();
    }

    /**
     * Render widget output.
     *
     * @return void
     */
    public function render(): void
    {
        $settings = $this->get_settings_for_display();
        $this->render_output($settings, false);
    }

    /**
     * Content controls.
     *
     * @param bool $is_pro Whether Pro controls are enabled.
     *
     * @return void
     */
    protected function register_content_controls(bool $is_pro): void
    {
        $this->start_controls_section(
            'kng_content_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Content', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_group_control(
            Group_Control_Image_Size::get_type(),
            [
                'name' => 'kng_image_size',
                'default' => 'large',
            ]
        );

        $this->add_control(
            'kng_link_to',
            [
                'label' => esc_html__('Link', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'none',
                'options' => [
                    'none' => esc_html__('None', 'king-addons'),
                    'post' => esc_html__('Post', 'king-addons'),
                    'media' => esc_html__('Media File', 'king-addons'),
                ],
            ]
        );

        $this->add_control(
            'kng_fallback_image',
            [
                'label' => $is_pro ?
                    esc_html__('Fallback Image', 'king-addons') :
                    sprintf(__('Fallback Image %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::MEDIA,
                'default' => [],
                'classes' => $is_pro ? '' : 'king-addons-pro-control',
            ]
        );

        $this->add_control(
            'kng_aspect_ratio',
            [
                'label' => $is_pro ?
                    esc_html__('Aspect Ratio', 'king-addons') :
                    sprintf(__('Aspect Ratio %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SELECT,
                'default' => '',
                'options' => [
                    '' => esc_html__('Natural', 'king-addons'),
                    '16-9' => '16:9',
                    '4-3' => '4:3',
                    '1-1' => '1:1',
                    '3-4' => '3:4',
                ],
                'classes' => $is_pro ? '' : 'king-addons-pro-control',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Style controls.
     *
     * @param bool $is_pro Whether Pro controls are enabled.
     *
     * @return void
     */
    protected function register_style_controls(bool $is_pro): void
    {
        $this->start_controls_section(
            'kng_style_section',
            [
                'label' => esc_html__('Style', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'kng_alignment',
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
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-featured-image' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-featured-image img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_border',
                'selector' => '{{WRAPPER}} .king-addons-tb-featured-image img',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_shadow',
                'selector' => '{{WRAPPER}} .king-addons-tb-featured-image img',
            ]
        );

        $this->add_responsive_control(
            'kng_margin',
            [
                'label' => esc_html__('Margin', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-featured-image' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        if ($is_pro) {
            $this->add_control(
                'kng_hover_opacity',
                [
                    'label' => esc_html__('Hover Opacity', 'king-addons'),
                    'type' => Controls_Manager::SLIDER,
                    'size_units' => ['px'],
                    'range' => [
                        'px' => ['min' => 0.3, 'max' => 1, 'step' => 0.01],
                    ],
                    'selectors' => [
                        '{{WRAPPER}} .king-addons-tb-featured-image img:hover' => 'opacity: {{SIZE}};',
                    ],
                ]
            );
        }

        $this->end_controls_section();
    }

    /**
     * Pro upsell section.
     *
     * @return void
     */
    protected function register_pro_notice_controls(): void
    {
        if (king_addons_freemius()->can_use_premium_code__premium_only()) {
            return;
        }

        Core::renderProFeaturesSection(
            $this,
            '',
            Controls_Manager::RAW_HTML,
            'tb-featured-image',
            [
                'Fallback image when no thumbnail set',
                'Aspect ratio and object-fit options',
                'Advanced hover overlays and opacity',
            ]
        );
    }

    /**
     * Render output helper.
     *
     * @param array<string, mixed> $settings Widget settings.
     * @param bool                 $is_pro   Whether Pro is enabled.
     *
     * @return void
     */
    protected function render_output(array $settings, bool $is_pro): void
    {
        $post = get_post();
        if (!$post instanceof \WP_Post) {
            return;
        }

        $image_id = get_post_thumbnail_id($post);

        if (!$image_id && $is_pro && !empty($settings['kng_fallback_image']['id'])) {
            $image_id = (int) $settings['kng_fallback_image']['id'];
        }

        if (!$image_id) {
            return;
        }

        $image_html = Group_Control_Image_Size::get_attachment_image_html($settings, 'kng_image_size', $image_id);
        if (!$image_html) {
            return;
        }

        $link_to = $settings['kng_link_to'] ?? 'none';
        $link_open = '';
        $link_close = '';

        if ('post' === $link_to) {
            $permalink = get_permalink($post);
            if ($permalink) {
                $link_open = '<a class="king-addons-tb-featured-image__link" href="' . esc_url($permalink) . '">';
                $link_close = '</a>';
            }
        } elseif ('media' === $link_to) {
            $url = wp_get_attachment_url($image_id);
            if ($url) {
                $link_open = '<a class="king-addons-tb-featured-image__link" href="' . esc_url($url) . '">';
                $link_close = '</a>';
            }
        }

        $wrapper_classes = ['king-addons-tb-featured-image'];
        $ratio_class = $settings['kng_aspect_ratio'] ?? '';
        if ($is_pro && $ratio_class) {
            $wrapper_classes[] = 'king-addons-tb-featured-image--ratio';
            $wrapper_classes[] = 'king-addons-tb-featured-image--ratio-' . $ratio_class;
        }

        echo '<div class="' . esc_attr(implode(' ', $wrapper_classes)) . '">';
        echo $link_open; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $image_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $link_close; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '</div>';
    }
}
