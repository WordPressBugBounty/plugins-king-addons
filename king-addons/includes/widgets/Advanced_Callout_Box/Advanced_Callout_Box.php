<?php
/**
 * Advanced Callout Box Widget (Free).
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Callout box widget for notes, tips, and warnings.
 */
class Advanced_Callout_Box extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-advanced-callout-box';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Advanced Callout Box', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-advanced-callout-box';
    }

    /**
     * Style dependencies.
     *
     * @return array<int, string>
     */
    public function get_style_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-advanced-callout-box-style',
            'elementor-icons-fa-solid',
            'elementor-icons-fa-regular',
            'elementor-icons-fa-brands',
        ];
    }

    /**
     * Script dependencies.
     *
     * @return array<int, string>
     */
    public function get_script_depends(): array
    {
        return [];
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
        return ['callout', 'notice', 'alert', 'note', 'tip'];
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
        $this->register_content_controls();
        $this->register_cta_controls();
        $this->register_style_controls();
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
        $type = $this->sanitize_type($settings['kng_type'] ?? 'note');
        $title = $settings['kng_title'] ?? '';
        $content = $settings['kng_content'] ?? '';
        $show_icon = ($settings['kng_show_icon'] ?? 'yes') === 'yes';
        $icon_position = $this->sanitize_icon_position($settings['kng_icon_position'] ?? 'left');
        $accent_enabled = ($settings['kng_accent_enable'] ?? 'yes') === 'yes';
        $align = $this->sanitize_align($settings['kng_content_alignment'] ?? 'left');

        if (!$show_icon) {
            $icon_position = 'none';
        }

        $title_id = 'king-addons-callout-title-' . $this->get_id();
        $content_id = 'king-addons-callout-content-' . $this->get_id();

        $wrapper_attrs = $this->get_wrapper_attributes([
            'type' => $type,
            'icon_position' => $icon_position,
            'accent' => $accent_enabled ? 'yes' : 'no',
            'align' => $align,
            'title_id' => !empty($title) ? $title_id : '',
            'content_id' => !empty($content) ? $content_id : '',
        ]);

        $icon = null;
        if ('none' !== $icon_position) {
            $override_icon = ($settings['kng_icon_override'] ?? '') === 'yes';
            if ($override_icon && !empty($settings['kng_custom_icon']['value'])) {
                $icon = $settings['kng_custom_icon'];
            } else {
                $icon = $this->get_default_icon($type);
            }
        }

        $button_enabled = ($settings['kng_button_enable'] ?? '') === 'yes';
        $button_text = $settings['kng_button_text'] ?? '';
        $button_link = $settings['kng_button_link'] ?? [];

        $href = is_array($button_link) ? ($button_link['url'] ?? '') : '';
        $is_external = is_array($button_link) && !empty($button_link['is_external']);
        $nofollow = is_array($button_link) && !empty($button_link['nofollow']);
        $rels = [];
        if ($is_external) {
            $rels[] = 'noopener';
            $rels[] = 'noreferrer';
        }
        if ($nofollow) {
            $rels[] = 'nofollow';
        }
        $rel_attr = !empty($rels) ? ' rel="' . esc_attr(implode(' ', array_unique($rels))) . '"' : '';
        $target_attr = $is_external ? ' target="_blank"' : '';

        $title_tag = $this->sanitize_title_tag($settings['kng_title_tag'] ?? 'h4');
        ?>
        <div class="king-addons-advanced-callout" <?php echo $wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
            <div class="king-addons-advanced-callout__inner">
                <?php if ($icon && 'none' !== $icon_position) : ?>
                    <span class="king-addons-advanced-callout__icon" aria-hidden="true">
                        <?php Icons_Manager::render_icon($icon, ['aria-hidden' => 'true']); ?>
                    </span>
                <?php endif; ?>

                <div class="king-addons-advanced-callout__body">
                    <?php if (!empty($title)) : ?>
                        <<?php echo esc_attr($title_tag); ?> id="<?php echo esc_attr($title_id); ?>" class="king-addons-advanced-callout__title">
                            <?php echo esc_html($title); ?>
                        </<?php echo esc_attr($title_tag); ?>>
                    <?php endif; ?>

                    <?php if (!empty($content)) : ?>
                        <div id="<?php echo esc_attr($content_id); ?>" class="king-addons-advanced-callout__content">
                            <?php echo wp_kses_post($content); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($button_enabled && !empty($button_text)) : ?>
                        <?php if (!empty($href)) : ?>
                            <a class="king-addons-advanced-callout__button" href="<?php echo esc_url($href); ?>"<?php echo $target_attr . $rel_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
                                <?php echo esc_html($button_text); ?>
                            </a>
                        <?php else : ?>
                            <span class="king-addons-advanced-callout__button" aria-disabled="true">
                                <?php echo esc_html($button_text); ?>
                            </span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Content controls.
     *
     * @return void
     */
    protected function register_content_controls(): void
    {
        $this->start_controls_section(
            'kng_content_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Content', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_type',
            [
                'label' => esc_html__('Type', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'note',
                'options' => [
                    'note' => esc_html__('Note', 'king-addons'),
                    'tip' => esc_html__('Tip', 'king-addons'),
                    'warning' => esc_html__('Warning', 'king-addons'),
                ],
            ]
        );

        $this->add_control(
            'kng_title',
            [
                'label' => esc_html__('Title', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'dynamic' => [
                    'active' => true,
                ],
                'default' => esc_html__('Note', 'king-addons'),
                'label_block' => true,
            ]
        );

        $this->add_control(
            'kng_title_tag',
            [
                'label' => esc_html__('Title HTML Tag', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'h4',
                'options' => [
                    'h1' => 'H1',
                    'h2' => 'H2',
                    'h3' => 'H3',
                    'h4' => 'H4',
                    'h5' => 'H5',
                    'h6' => 'H6',
                    'div' => 'DIV',
                    'p' => 'P',
                ],
            ]
        );

        $this->add_control(
            'kng_content',
            [
                'label' => esc_html__('Content', 'king-addons'),
                'type' => Controls_Manager::WYSIWYG,
                'dynamic' => [
                    'active' => true,
                ],
                'default' => esc_html__('Add your callout content here.', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_show_icon',
            [
                'label' => esc_html__('Show Icon', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'kng_icon_position',
            [
                'label' => esc_html__('Icon Position', 'king-addons'),
                'type' => Controls_Manager::CHOOSE,
                'toggle' => true,
                'default' => 'left',
                'options' => [
                    'left' => [
                        'title' => esc_html__('Left', 'king-addons'),
                        'icon' => 'eicon-h-align-left',
                    ],
                    'top' => [
                        'title' => esc_html__('Top', 'king-addons'),
                        'icon' => 'eicon-v-align-top',
                    ],
                    'none' => [
                        'title' => esc_html__('None', 'king-addons'),
                        'icon' => 'eicon-close',
                    ],
                ],
                'condition' => [
                    'kng_show_icon' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_icon_override',
            [
                'label' => esc_html__('Custom Icon', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
                'condition' => [
                    'kng_show_icon' => 'yes',
                    'kng_icon_position!' => 'none',
                ],
            ]
        );

        $this->add_control(
            'kng_custom_icon',
            [
                'label' => esc_html__('Select Icon', 'king-addons'),
                'type' => Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fas fa-info-circle',
                    'library' => 'fa-solid',
                ],
                'condition' => [
                    'kng_show_icon' => 'yes',
                    'kng_icon_position!' => 'none',
                    'kng_icon_override' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * CTA controls.
     *
     * @return void
     */
    protected function register_cta_controls(): void
    {
        $this->start_controls_section(
            'kng_cta_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('CTA', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_button_enable',
            [
                'label' => esc_html__('Button', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
            ]
        );

        $this->add_control(
            'kng_button_text',
            [
                'label' => esc_html__('Button Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'dynamic' => [
                    'active' => true,
                ],
                'default' => esc_html__('Learn more', 'king-addons'),
                'condition' => [
                    'kng_button_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_button_link',
            [
                'label' => esc_html__('Button Link', 'king-addons'),
                'type' => Controls_Manager::URL,
                'dynamic' => [
                    'active' => true,
                ],
                'placeholder' => esc_html__('https://', 'king-addons'),
                'condition' => [
                    'kng_button_enable' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Style controls.
     *
     * @return void
     */
    protected function register_style_controls(): void
    {
        $this->start_controls_section(
            'kng_style_box_section',
            [
                'label' => esc_html__('Box', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'kng_background_color',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-advanced-callout' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_border',
                'selector' => '{{WRAPPER}} .king-addons-advanced-callout',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_box_shadow',
                'selector' => '{{WRAPPER}} .king-addons-advanced-callout',
            ]
        );

        $this->add_responsive_control(
            'kng_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 80,
                    ],
                    '%' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-advanced-callout' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-advanced-callout__inner' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_content_alignment',
            [
                'label' => esc_html__('Content Alignment', 'king-addons'),
                'type' => Controls_Manager::CHOOSE,
                'toggle' => true,
                'default' => 'left',
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
                    '{{WRAPPER}} .king-addons-advanced-callout__body' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_icon_gap',
            [
                'label' => esc_html__('Icon Gap', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 60,
                    ],
                    'em' => [
                        'min' => 0,
                        'max' => 6,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-advanced-callout' => '--ka-callout-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_margin_bottom',
            [
                'label' => esc_html__('Margin Bottom', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', '%'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 200,
                    ],
                    'em' => [
                        'min' => 0,
                        'max' => 12,
                    ],
                    '%' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-advanced-callout' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_accent_enable',
            [
                'label' => esc_html__('Left Accent Bar', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'kng_accent_color',
            [
                'label' => esc_html__('Accent Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-advanced-callout' => '--ka-callout-accent-color: {{VALUE}}; --ka-callout-button-bg: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_accent_width',
            [
                'label' => esc_html__('Accent Bar Width', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 1,
                        'max' => 16,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-advanced-callout' => '--ka-callout-accent-width: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'kng_accent_enable' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'kng_style_icon_section',
            [
                'label' => esc_html__('Icon', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'kng_show_icon' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_icon_color',
            [
                'label' => esc_html__('Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-advanced-callout' => '--ka-callout-icon: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_icon_size',
            [
                'label' => esc_html__('Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range' => [
                    'px' => [
                        'min' => 12,
                        'max' => 80,
                    ],
                    'em' => [
                        'min' => 0.5,
                        'max' => 6,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-advanced-callout' => '--ka-callout-icon-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'kng_style_title_section',
            [
                'label' => esc_html__('Title', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_title_typography',
                'selector' => '{{WRAPPER}} .king-addons-advanced-callout__title',
            ]
        );

        $this->add_control(
            'kng_title_color',
            [
                'label' => esc_html__('Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-advanced-callout__title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'kng_style_content_section',
            [
                'label' => esc_html__('Content', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_content_typography',
                'selector' => '{{WRAPPER}} .king-addons-advanced-callout__content',
            ]
        );

        $this->add_control(
            'kng_content_color',
            [
                'label' => esc_html__('Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-advanced-callout__content' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'kng_style_button_section',
            [
                'label' => esc_html__('Button', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'kng_button_enable' => 'yes',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_button_typography',
                'selector' => '{{WRAPPER}} .king-addons-advanced-callout__button',
            ]
        );

        $this->add_responsive_control(
            'kng_button_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-advanced-callout__button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_button_spacing',
            [
                'label' => esc_html__('Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 60,
                    ],
                    'em' => [
                        'min' => 0,
                        'max' => 6,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-advanced-callout' => '--ka-callout-button-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_button_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 80,
                    ],
                    '%' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-advanced-callout__button' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_button_border_width',
            [
                'label' => esc_html__('Border Width', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 8,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-advanced-callout' => '--ka-callout-button-border-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_border_style',
            [
                'label' => esc_html__('Border Style', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'solid',
                'options' => [
                    'none' => esc_html__('None', 'king-addons'),
                    'solid' => esc_html__('Solid', 'king-addons'),
                    'dashed' => esc_html__('Dashed', 'king-addons'),
                    'dotted' => esc_html__('Dotted', 'king-addons'),
                    'double' => esc_html__('Double', 'king-addons'),
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-advanced-callout' => '--ka-callout-button-border-style: {{VALUE}};',
                ],
            ]
        );

        $this->start_controls_tabs('kng_button_style_tabs');

        $this->start_controls_tab(
            'kng_button_style_tab_normal',
            [
                'label' => esc_html__('Normal', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_button_background',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-advanced-callout' => '--ka-callout-button-bg: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-advanced-callout' => '--ka-callout-button-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_border_color',
            [
                'label' => esc_html__('Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-advanced-callout' => '--ka-callout-button-border: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'kng_button_style_tab_hover',
            [
                'label' => esc_html__('Hover', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_button_background_hover',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-advanced-callout' => '--ka-callout-button-bg-hover: {{VALUE}}; --ka-callout-button-hover-filter: none;',
                ],
            ]
        );

        $this->add_control(
            'kng_button_color_hover',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-advanced-callout' => '--ka-callout-button-color-hover: {{VALUE}}; --ka-callout-button-hover-filter: none;',
                ],
            ]
        );

        $this->add_control(
            'kng_button_border_color_hover',
            [
                'label' => esc_html__('Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-advanced-callout' => '--ka-callout-button-border-hover: {{VALUE}}; --ka-callout-button-hover-filter: none;',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->end_controls_section();
    }

    /**
     * Register Pro notice controls.
     *
     * @return void
     */
    public function register_pro_notice_controls(): void
    {
        if (!king_addons_can_use_pro()) {
            Core::renderProFeaturesSection($this, '', Controls_Manager::RAW_HTML, 'advanced-callout-box', [
                'Promo, Info, Success, and Custom types',
                'Collapsible mode with accessible toggle',
                'Copy-to-clipboard button with feedback',
                'Dynamic content via ACF with fallbacks',
                'Display conditions and preset library',
            ]);
        }
    }

    /**
     * Sanitize callout type.
     *
     * @param string $type Type.
     *
     * @return string
     */
    protected function sanitize_type(string $type): string
    {
        $allowed = ['note', 'tip', 'warning', 'promo', 'info', 'success', 'custom'];
        return in_array($type, $allowed, true) ? $type : 'note';
    }

    /**
     * Sanitize icon position.
     *
     * @param string $position Position.
     *
     * @return string
     */
    protected function sanitize_icon_position(string $position): string
    {
        $allowed = ['left', 'top', 'none'];
        return in_array($position, $allowed, true) ? $position : 'left';
    }

    /**
     * Sanitize content alignment.
     *
     * @param string $align Alignment.
     *
     * @return string
     */
    protected function sanitize_align(string $align): string
    {
        $allowed = ['left', 'center', 'right'];
        return in_array($align, $allowed, true) ? $align : 'left';
    }

    /**
     * Sanitize title tag.
     *
     * @param string $tag Tag.
     *
     * @return string
     */
    protected function sanitize_title_tag(string $tag): string
    {
        $allowed = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'p', 'span'];
        return in_array($tag, $allowed, true) ? $tag : 'h4';
    }

    /**
     * Default icon map per type.
     *
     * @param string $type Type.
     *
     * @return array<string, string>
     */
    protected function get_default_icon(string $type): array
    {
        $map = [
            'note' => [
                'value' => 'far fa-sticky-note',
                'library' => 'fa-regular',
            ],
            'tip' => [
                'value' => 'far fa-lightbulb',
                'library' => 'fa-regular',
            ],
            'warning' => [
                'value' => 'fas fa-exclamation-triangle',
                'library' => 'fa-solid',
            ],
            'promo' => [
                'value' => 'fas fa-bullhorn',
                'library' => 'fa-solid',
            ],
            'info' => [
                'value' => 'fas fa-info-circle',
                'library' => 'fa-solid',
            ],
            'success' => [
                'value' => 'fas fa-check-circle',
                'library' => 'fa-solid',
            ],
            'custom' => [
                'value' => 'fas fa-star',
                'library' => 'fa-solid',
            ],
        ];

        return $map[$type] ?? $map['note'];
    }

    /**
     * Build wrapper attributes.
     *
     * @param array<string, string> $settings Settings.
     *
     * @return string
     */
    protected function get_wrapper_attributes(array $settings): string
    {
        $attrs = [
            'data-type' => $settings['type'] ?? 'note',
            'data-icon-position' => $settings['icon_position'] ?? 'left',
            'data-accent' => $settings['accent'] ?? 'yes',
            'data-align' => $settings['align'] ?? 'left',
            'data-collapsible' => 'no',
            'data-copy' => 'no',
            'role' => 'note',
        ];

        if (!empty($settings['title_id'])) {
            $attrs['aria-labelledby'] = $settings['title_id'];
        }

        if (!empty($settings['content_id'])) {
            $attrs['aria-describedby'] = $settings['content_id'];
        }

        $compiled = [];
        foreach ($attrs as $key => $value) {
            $compiled[] = esc_attr($key) . '="' . esc_attr($value) . '"';
        }

        return implode(' ', $compiled);
    }
}
