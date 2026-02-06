<?php
/**
 * Floating Tags Marquee Widget.
 *
 * Animated infinite marquee of tags/chips with direction and speed controls.
 * Pro version adds drag, pause on hover, multi-rows, fade edges, and reduced motion.
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Plugin;
use Elementor\Repeater;
use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Floating_Tags_Marquee
 */
class Floating_Tags_Marquee extends Widget_Base
{
    /**
     * Get widget name.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-floating-tags-marquee';
    }

    /**
     * Get widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Floating Tags Marquee', 'king-addons');
    }

    /**
     * Get widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'eicon-tags';
    }

    /**
     * Get widget categories.
     *
     * @return array
     */
    public function get_categories(): array
    {
        return ['king-addons'];
    }

    /**
     * Get widget keywords.
     *
     * @return array
     */
    public function get_keywords(): array
    {
        return ['tags', 'marquee', 'floating', 'chips', 'badges', 'scroll', 'animation', 'loop', 'king-addons'];
    }

    /**
     * Get style dependencies.
     *
     * @return array
     */
    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-floating-tags-marquee-style'];
    }

    /**
     * Get script dependencies.
     *
     * @return array
     */
    public function get_script_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-floating-tags-marquee-script'];
    }

        public function get_custom_help_url()
        {
            return 'mailto:bug@kingaddons.com?subject=Bug Report - King Addons&body=Please describe the issue';
        }

        /**
     * Register widget controls.
     *
     * @return void
     */
    protected function register_controls(): void
    {
        $this->register_tags_controls();
        $this->register_marquee_controls();
        $this->register_separator_controls();
        $this->register_style_chip_controls();
        $this->register_style_separator_controls();
        $this->register_style_layout_controls();
        $this->register_pro_notice_controls();
    }

    /**
     * Register tags repeater controls.
     *
     * @return void
     */
    protected function register_tags_controls(): void
    {
        $this->start_controls_section(
            'kng_ftm_tags_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Tags', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $repeater = new Repeater();

        $repeater->add_control(
            'tag_text',
            [
                'label' => esc_html__('Tag Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Tag', 'king-addons'),
                'label_block' => true,
                'dynamic' => ['active' => true],
            ]
        );

        $repeater->add_control(
            'tag_link',
            [
                'label' => esc_html__('Link', 'king-addons'),
                'type' => Controls_Manager::URL,
                'placeholder' => esc_html__('https://your-link.com', 'king-addons'),
                'dynamic' => ['active' => true],
            ]
        );

        $repeater->add_control(
            'tag_highlight',
            [
                'label' => esc_html__('Highlight', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'description' => esc_html__('Make this tag stand out with highlight styles.', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_ftm_tags',
            [
                'label' => esc_html__('Tags', 'king-addons'),
                'type' => Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'default' => $this->get_default_tags(),
                'title_field' => '{{{ tag_text }}}',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register marquee settings controls.
     *
     * @return void
     */
    protected function register_marquee_controls(): void
    {
        $this->start_controls_section(
            'kng_ftm_marquee_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Marquee Settings', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_ftm_direction',
            [
                'label' => esc_html__('Direction', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'left',
                'options' => [
                    'left' => esc_html__('Left', 'king-addons'),
                    'right' => esc_html__('Right', 'king-addons'),
                ],
            ]
        );

        $this->add_control(
            'kng_ftm_speed',
            [
                'label' => esc_html__('Speed', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => ['min' => 1, 'max' => 100, 'step' => 1],
                ],
                'default' => [
                    'size' => 30,
                ],
                'description' => esc_html__('Animation speed (higher = faster).', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_ftm_duplicate_count',
            [
                'label' => esc_html__('Track Copies', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'auto',
                'options' => [
                    'auto' => esc_html__('Auto', 'king-addons'),
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                ],
                'description' => esc_html__('Number of track copies for seamless loop.', 'king-addons'),
            ]
        );
        $this->add_control(
            'kng_ftm_timing_function',
            [
                'label' => esc_html__('Animation Timing', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'linear',
                'options' => [
                    'linear' => esc_html__('Linear', 'king-addons'),
                    'ease' => esc_html__('Ease', 'king-addons'),
                    'ease-in' => esc_html__('Ease In', 'king-addons'),
                    'ease-out' => esc_html__('Ease Out', 'king-addons'),
                    'ease-in-out' => esc_html__('Ease In-Out', 'king-addons'),
                ],
                'selectors' => [
                    '{{WRAPPER}} .kng-ftm__track' => 'animation-timing-function: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_ftm_initial_delay',
            [
                'label' => esc_html__('Initial Delay (s)', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => ['min' => 0, 'max' => 5, 'step' => 0.1],
                ],
                'default' => ['size' => 0],
                'selectors' => [
                    '{{WRAPPER}} .kng-ftm__track' => 'animation-delay: {{SIZE}}s;',
                ],
            ]
        );
        $this->end_controls_section();
    }

    /**
     * Register separator controls.
     *
     * @return void
     */
    protected function register_separator_controls(): void
    {
        $this->start_controls_section(
            'kng_ftm_separator_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Separator', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_ftm_separator_type',
            [
                'label' => esc_html__('Separator Type', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'none',
                'options' => [
                    'none' => esc_html__('None', 'king-addons'),
                    'dot' => esc_html__('Dot', 'king-addons'),
                    'line' => esc_html__('Line', 'king-addons'),
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_ftm_separator_spacing',
            [
                'label' => esc_html__('Separator Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 60],
                ],
                'default' => [
                    'size' => 16,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .kng-ftm__sep' => 'margin-left: {{SIZE}}{{UNIT}}; margin-right: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'kng_ftm_separator_type!' => 'none',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register chip style controls.
     *
     * @return void
     */
    protected function register_style_chip_controls(): void
    {
        $this->start_controls_section(
            'kng_ftm_style_chip_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Chip', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_ftm_chip_typography',
                'selector' => '{{WRAPPER}} .kng-ftm__item',
            ]
        );

        $this->start_controls_tabs('kng_ftm_chip_tabs');

        // Normal tab
        $this->start_controls_tab(
            'kng_ftm_chip_tab_normal',
            ['label' => esc_html__('Normal', 'king-addons')]
        );

        $this->add_control(
            'kng_ftm_chip_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .kng-ftm__item' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name' => 'kng_ftm_chip_bg',
                'selector' => '{{WRAPPER}} .kng-ftm__item',
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_ftm_chip_border',
                'selector' => '{{WRAPPER}} .kng-ftm__item',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_ftm_chip_shadow',
                'selector' => '{{WRAPPER}} .kng-ftm__item',
            ]
        );

        $this->end_controls_tab();

        // Hover tab
        $this->start_controls_tab(
            'kng_ftm_chip_tab_hover',
            ['label' => esc_html__('Hover', 'king-addons')]
        );

        $this->add_control(
            'kng_ftm_chip_hover_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .kng-ftm__item:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name' => 'kng_ftm_chip_hover_bg',
                'selector' => '{{WRAPPER}} .kng-ftm__item:hover',
            ]
        );

        $this->add_control(
            'kng_ftm_chip_hover_border_color',
            [
                'label' => esc_html__('Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .kng-ftm__item:hover' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_ftm_chip_hover_shadow',
                'selector' => '{{WRAPPER}} .kng-ftm__item:hover',
            ]
        );

        $this->add_control(
            'kng_ftm_chip_hover_transform',
            [
                'label' => esc_html__('Hover Transform', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'none',
                'options' => [
                    'none' => esc_html__('None', 'king-addons'),
                    'lift' => esc_html__('Lift Up', 'king-addons'),
                    'scale' => esc_html__('Scale Up', 'king-addons'),
                    'lift-scale' => esc_html__('Lift + Scale', 'king-addons'),
                ],
            ]
        );

        $this->end_controls_tab();

        // Highlight tab
        $this->start_controls_tab(
            'kng_ftm_chip_tab_highlight',
            ['label' => esc_html__('Highlight', 'king-addons')]
        );

        $this->add_control(
            'kng_ftm_chip_highlight_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .kng-ftm__item--highlight' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name' => 'kng_ftm_chip_highlight_bg',
                'selector' => '{{WRAPPER}} .kng-ftm__item--highlight',
            ]
        );

        $this->add_control(
            'kng_ftm_chip_highlight_border_color',
            [
                'label' => esc_html__('Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .kng-ftm__item--highlight' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_responsive_control(
            'kng_ftm_chip_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'separator' => 'before',
                'selectors' => [
                    '{{WRAPPER}} .kng-ftm__item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_ftm_chip_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .kng-ftm__item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register separator style controls.
     *
     * @return void
     */
    protected function register_style_separator_controls(): void
    {
        $this->start_controls_section(
            'kng_ftm_style_separator_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Separator', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'kng_ftm_separator_type!' => 'none',
                ],
            ]
        );

        $this->add_control(
            'kng_ftm_sep_color',
            [
                'label' => esc_html__('Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#94a3b8',
                'selectors' => [
                    '{{WRAPPER}} .kng-ftm__sep' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_ftm_sep_opacity',
            [
                'label' => esc_html__('Opacity', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => ['min' => 0, 'max' => 1, 'step' => 0.05],
                ],
                'default' => ['size' => 0.5],
                'selectors' => [
                    '{{WRAPPER}} .kng-ftm__sep' => 'opacity: {{SIZE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_ftm_sep_dot_size',
            [
                'label' => esc_html__('Dot Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 2, 'max' => 20],
                ],
                'default' => ['size' => 6, 'unit' => 'px'],
                'selectors' => [
                    '{{WRAPPER}} .kng-ftm__sep--dot' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'kng_ftm_separator_type' => 'dot',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_ftm_sep_line_width',
            [
                'label' => esc_html__('Line Width', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 1, 'max' => 4],
                ],
                'default' => ['size' => 1, 'unit' => 'px'],
                'selectors' => [
                    '{{WRAPPER}} .kng-ftm__sep--line' => 'width: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'kng_ftm_separator_type' => 'line',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_ftm_sep_line_height',
            [
                'label' => esc_html__('Line Height', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => ['min' => 8, 'max' => 60],
                    '%' => ['min' => 20, 'max' => 100],
                ],
                'default' => ['size' => 20, 'unit' => 'px'],
                'selectors' => [
                    '{{WRAPPER}} .kng-ftm__sep--line' => 'height: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'kng_ftm_separator_type' => 'line',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register layout style controls.
     *
     * @return void
     */
    protected function register_style_layout_controls(): void
    {
        $this->start_controls_section(
            'kng_ftm_style_layout_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Layout', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'kng_ftm_track_height',
            [
                'label' => esc_html__('Track Height', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 30, 'max' => 150],
                ],
                'selectors' => [
                    '{{WRAPPER}} .kng-ftm__track' => 'height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_ftm_gap',
            [
                'label' => esc_html__('Gap Between Chips', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 60],
                ],
                'default' => [
                    'size' => 12,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .kng-ftm' => '--kng-ftm-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_ftm_vertical_align',
            [
                'label' => esc_html__('Vertical Alignment', 'king-addons'),
                'type' => Controls_Manager::CHOOSE,
                'default' => 'center',
                'options' => [
                    'flex-start' => [
                        'title' => esc_html__('Top', 'king-addons'),
                        'icon' => 'eicon-v-align-top',
                    ],
                    'center' => [
                        'title' => esc_html__('Middle', 'king-addons'),
                        'icon' => 'eicon-v-align-middle',
                    ],
                    'flex-end' => [
                        'title' => esc_html__('Bottom', 'king-addons'),
                        'icon' => 'eicon-v-align-bottom',
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .kng-ftm__track' => 'align-items: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name' => 'kng_ftm_wrapper_bg',
                'label' => esc_html__('Background', 'king-addons'),
                'types' => ['classic', 'gradient'],
                'separator' => 'before',
                'selector' => '{{WRAPPER}} .kng-ftm',
            ]
        );

        $this->add_responsive_control(
            'kng_ftm_wrapper_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .kng-ftm' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_ftm_wrapper_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .kng-ftm' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register Pro features notice (Free version).
     *
     * @return void
     */
    protected function register_pro_notice_controls(): void
    {
        if (!$this->is_pro_enabled()) {
            Core::renderProFeaturesSection($this, '', Controls_Manager::RAW_HTML, 'floating-tags-marquee', [
                'Interactive drag (mouse & touch)',
                'Pause animation on hover',
                'Multi-rows (2–5 rows)',
                'Fade edges (gradient mask)',
                'Reduced motion support',
                'Icons in tags',
            ]);
        }
    }

    /**
     * Get default tags data.
     *
     * @return array
     */
    protected function get_default_tags(): array
    {
        return [
            ['tag_text' => esc_html__('Design', 'king-addons')],
            ['tag_text' => esc_html__('Development', 'king-addons')],
            ['tag_text' => esc_html__('Marketing', 'king-addons'), 'tag_highlight' => 'yes'],
            ['tag_text' => esc_html__('Analytics', 'king-addons')],
            ['tag_text' => esc_html__('Automation', 'king-addons')],
            ['tag_text' => esc_html__('Integration', 'king-addons')],
            ['tag_text' => esc_html__('Security', 'king-addons')],
            ['tag_text' => esc_html__('Performance', 'king-addons')],
        ];
    }

    /**
     * Render widget output.
     *
     * @return void
     */
    protected function render(): void
    {
        $settings = $this->get_settings_for_display();
        $tags = $settings['kng_ftm_tags'] ?? [];

        if (empty($tags)) {
            if ($this->is_editor()) {
                echo '<div class="kng-ftm-empty">' . esc_html__('Add tags to get started.', 'king-addons') . '</div>';
            }
            return;
        }

        $wrapper_classes = $this->get_wrapper_classes($settings);
        $data_settings = $this->get_data_settings($settings);

        $this->add_render_attribute('wrapper', [
            'class' => implode(' ', $wrapper_classes),
            'data-settings' => wp_json_encode($data_settings),
        ]);

        echo '<div ' . $this->get_render_attribute_string('wrapper') . '>';
        $this->render_row($settings, $tags, 0);
        echo '</div>';
    }

    /**
     * Render single row (track).
     *
     * @param array $settings Widget settings.
     * @param array $tags Tags data.
     * @param int $row_index Row index.
     * @return void
     */
    protected function render_row(array $settings, array $tags, int $row_index): void
    {
        $direction = $settings['kng_ftm_direction'] ?? 'left';

        echo '<div class="kng-ftm__row" data-row="' . esc_attr((string) $row_index) . '">';
        echo '<div class="kng-ftm__track kng-ftm__track--' . esc_attr($direction) . '">';

        // Render track content (will be duplicated by JS for seamless loop)
        echo '<div class="kng-ftm__content">';
        $this->render_tags($settings, $tags);
        echo '</div>';

        echo '</div>';
        echo '</div>';
    }

    /**
     * Render tags list.
     *
     * @param array $settings Widget settings.
     * @param array $tags Tags data.
     * @return void
     */
    protected function render_tags(array $settings, array $tags): void
    {
        $separator_type = $settings['kng_ftm_separator_type'] ?? 'none';
        $total_tags = count($tags);

        foreach ($tags as $index => $tag) {
            $this->render_tag($tag);

            // Separator (except after last tag)
            if ('none' !== $separator_type && $index < $total_tags - 1) {
                $this->render_separator($separator_type);
            }
        }

        // Add separator after last tag for seamless loop
        if ('none' !== $separator_type) {
            $this->render_separator($separator_type);
        }
    }

    /**
     * Render single tag.
     *
     * @param array $tag Tag data.
     * @return void
     */
    protected function render_tag(array $tag): void
    {
        $text = esc_html(trim((string) ($tag['tag_text'] ?? '')));
        $link = $tag['tag_link'] ?? [];
        $is_highlight = 'yes' === ($tag['tag_highlight'] ?? '');

        if ('' === $text) {
            return;
        }

        $classes = ['kng-ftm__item'];
        if ($is_highlight) {
            $classes[] = 'kng-ftm__item--highlight';
        }

        $has_link = !empty($link['url']);

        if ($has_link) {
            $link_attrs = $this->get_link_attributes($link);
            echo '<a ' . $link_attrs . ' class="' . esc_attr(implode(' ', $classes)) . '">';
            echo '<span class="kng-ftm__text">' . $text . '</span>';
            echo '</a>';
        } else {
            echo '<span class="' . esc_attr(implode(' ', $classes)) . '">';
            echo '<span class="kng-ftm__text">' . $text . '</span>';
            echo '</span>';
        }
    }

    /**
     * Render separator element.
     *
     * @param string $type Separator type.
     * @return void
     */
    protected function render_separator(string $type): void
    {
        $classes = ['kng-ftm__sep', 'kng-ftm__sep--' . esc_attr($type)];
        echo '<span class="' . esc_attr(implode(' ', $classes)) . '" aria-hidden="true"></span>';
    }

    /**
     * Get wrapper CSS classes.
     *
     * @param array $settings Widget settings.
     * @return array
     */
    protected function get_wrapper_classes(array $settings): array
    {
        $classes = ['kng-ftm'];

        $direction = $settings['kng_ftm_direction'] ?? 'left';
        $classes[] = 'kng-ftm--direction-' . $direction;

        // Hover transform
        $hover_transform = $settings['kng_ftm_chip_hover_transform'] ?? 'none';
        if ('none' !== $hover_transform) {
            $classes[] = 'kng-ftm--hover-' . $hover_transform;
        }

        if ($this->is_pro_enabled()) {
            $classes[] = 'kng-ftm--pro';
        }

        return $classes;
    }

    /**
     * Get data settings for JS.
     *
     * @param array $settings Widget settings.
     * @return array
     */
    protected function get_data_settings(array $settings): array
    {
        $speed = absint($settings['kng_ftm_speed']['size'] ?? 30);
        $duplicate = $settings['kng_ftm_duplicate_count'] ?? 'auto';

        return [
            'direction' => $settings['kng_ftm_direction'] ?? 'left',
            'speed' => $speed,
            'duplicateCount' => $duplicate,
        ];
    }

    /**
     * Get link attributes string.
     *
     * @param array $link Link data.
     * @return string
     */
    protected function get_link_attributes(array $link): string
    {
        if (empty($link['url'])) {
            return '';
        }

        $attributes = [
            'href' => esc_url($link['url']),
        ];

        if (!empty($link['is_external'])) {
            $attributes['target'] = '_blank';
            $attributes['rel'] = 'noopener noreferrer';
        }

        if (!empty($link['nofollow'])) {
            $attributes['rel'] = isset($attributes['rel']) ? $attributes['rel'] . ' nofollow' : 'nofollow';
        }

        $output = [];
        foreach ($attributes as $key => $value) {
            $output[] = esc_attr($key) . '="' . esc_attr((string) $value) . '"';
        }

        return implode(' ', $output);
    }

    /**
     * Check if Pro features are enabled.
     *
     * @return bool
     */
    protected function is_pro_enabled(): bool
    {
        return function_exists('king_addons_freemius') && king_addons_freemius()->can_use_premium_code__premium_only();
    }

    /**
     * Check if in editor mode.
     *
     * @return bool
     */
    protected function is_editor(): bool
    {
        return class_exists(Plugin::class) && Plugin::$instance->editor->is_edit_mode();
    }
}
