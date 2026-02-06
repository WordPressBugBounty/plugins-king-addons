<?php
/**
 * Pull Quotes & Callouts Builder Widget (Free).
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
use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Pull Quotes & Callouts Builder widget.
 */
class Pull_Quotes_Callouts_Builder extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-pull-quotes-callouts-builder';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Pull Quotes & Callouts Builder', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-pull-quotes-callouts-builder';
    }

    /**
     * Style dependencies.
     *
     * @return array<int, string>
     */
    public function get_style_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-pull-quotes-callouts-builder-style',
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
        return ['quote', 'pull quote', 'callout', 'editorial', 'highlight', 'testimonial', 'tip'];
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
        $this->register_layout_controls();
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

        $block_type = $this->sanitize_block_type($settings['kng_block_type'] ?? 'pull-quote');
        $preset = $this->sanitize_preset($settings['kng_preset'] ?? 'classic-accent');
        $mark_style = $this->sanitize_mark_style($settings['kng_quote_mark_style'] ?? 'classic');
        $mark_mode = $this->sanitize_mark_mode($settings['kng_quote_mark_mode'] ?? 'corner');
        $icon_position = $this->sanitize_icon_position($settings['kng_icon_position'] ?? 'left');
        $accent_side = $this->sanitize_accent_side($settings['kng_accent_side'] ?? 'left');

        $show_mark = $block_type === 'pull-quote' && ($settings['kng_quote_mark_show'] ?? 'yes') === 'yes';
        $show_icon = $block_type === 'callout' && ($settings['kng_icon_enable'] ?? 'yes') === 'yes';
        $show_label = $block_type === 'callout' && ($settings['kng_label_show'] ?? '') === 'yes';

        $wrapper_classes = [
            'king-addons-pull-quotes-callouts',
            'king-addons-pull-quotes-callouts--type-' . $block_type,
            'king-addons-pull-quotes-callouts--preset-' . $preset,
            'king-addons-pull-quotes-callouts--accent-' . $accent_side,
            'king-addons-pull-quotes-callouts--mark-' . $mark_style,
            'king-addons-pull-quotes-callouts--mark-mode-' . $mark_mode,
        ];

        if ($show_icon) {
            $wrapper_classes[] = 'king-addons-pull-quotes-callouts--icon-' . $icon_position;
        } else {
            $wrapper_classes[] = 'king-addons-pull-quotes-callouts--icon-none';
        }

        if (!$show_mark) {
            $wrapper_classes[] = 'king-addons-pull-quotes-callouts--mark-hidden';
        }

        if ($show_label) {
            $wrapper_classes[] = 'king-addons-pull-quotes-callouts--label-enabled';
        }

        $this->add_render_attribute('wrapper', [
            'class' => $wrapper_classes,
        ]);

        $text = $settings['kng_text'] ?? '';
        $author = $settings['kng_author_name'] ?? '';
        $source = $settings['kng_author_source'] ?? '';
        $label = $settings['kng_label_text'] ?? '';

        $link = $settings['kng_link'] ?? [];
        $href = is_array($link) ? ($link['url'] ?? '') : '';
        $is_external = is_array($link) && !empty($link['is_external']);
        $nofollow = is_array($link) && !empty($link['nofollow']);
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
        $link_text = $settings['kng_link_text'] ?? '';

        $wrapper_tag = $block_type === 'pull-quote' ? 'figure' : 'aside';
        $role_attr = $block_type === 'callout' ? ' role="note"' : '';

        echo '<' . esc_html($wrapper_tag) . ' ' . $this->get_render_attribute_string('wrapper') . $role_attr . '>';
        echo '<div class="king-addons-pull-quotes-callouts__inner">';

        if ($show_icon && !empty($settings['kng_icon']['value'])) {
            echo '<span class="king-addons-pull-quotes-callouts__icon" aria-hidden="true">';
            Icons_Manager::render_icon($settings['kng_icon'], ['aria-hidden' => 'true']);
            echo '</span>';
        }

        echo '<div class="king-addons-pull-quotes-callouts__body">';

        if ($show_label && !empty($label)) {
            echo '<span class="king-addons-pull-quotes-callouts__label">' . esc_html($label) . '</span>';
        }

        if ($show_mark) {
            echo '<span class="king-addons-pull-quotes-callouts__mark" aria-hidden="true">&#8220;</span>';
        }

        if (!empty($text)) {
            if ($block_type === 'pull-quote') {
                echo '<blockquote class="king-addons-pull-quotes-callouts__text">' . wp_kses_post($text) . '</blockquote>';
            } else {
                echo '<div class="king-addons-pull-quotes-callouts__text">' . wp_kses_post($text) . '</div>';
            }
        }

        if (!empty($href) && !empty($link_text)) {
            echo '<a class="king-addons-pull-quotes-callouts__link" href="' . esc_url($href) . '"' . $target_attr . $rel_attr . '>' . esc_html($link_text) . '</a>';
        }

        echo '</div>';
        echo '</div>';

        if (!empty($author) || !empty($source)) {
            $footer_tag = $block_type === 'pull-quote' ? 'figcaption' : 'div';
            $separator = $settings['kng_author_source_separator'] ?? '';
            echo '<' . esc_html($footer_tag) . ' class="king-addons-pull-quotes-callouts__footer">';
            if (!empty($author)) {
                echo '<span class="king-addons-pull-quotes-callouts__author">' . esc_html($author) . '</span>';
            }
            if (!empty($separator) && !empty($author) && !empty($source)) {
                echo '<span class="king-addons-pull-quotes-callouts__separator" aria-hidden="true">' . esc_html($separator) . '</span>';
            }
            if (!empty($source)) {
                echo '<span class="king-addons-pull-quotes-callouts__source">' . esc_html($source) . '</span>';
            }
            echo '</' . esc_html($footer_tag) . '>';
        }

        echo '</' . esc_html($wrapper_tag) . '>';
    }

    /**
     * Content controls.
     *
     * @return void
     */
    protected function register_content_controls(): void
    {
        $can_pro = king_addons_can_use_pro();

        $this->start_controls_section(
            'kng_content_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Content', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_block_type',
            [
                'label' => esc_html__('Block Type', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'pull-quote',
                'options' => [
                    'pull-quote' => esc_html__('Pull Quote', 'king-addons'),
                    'callout' => esc_html__('Callout', 'king-addons'),
                ],
            ]
        );

        $this->add_control(
            'kng_preset',
            [
                'label' => esc_html__('Preset', 'king-addons'),
                'type' => Controls_Manager::VISUAL_CHOICE,
                'label_block' => true,
                'columns' => 4,
                'default' => 'classic-accent',
                'options' => $this->get_preset_options(),
                'render_type' => 'template',
            ]
        );

        $this->add_control(
            'kng_text',
            [
                'label' => esc_html__('Main Text', 'king-addons'),
                'type' => Controls_Manager::WYSIWYG,
                'default' => '<p>' . esc_html__('Add your quote or callout text here.', 'king-addons') . '</p>',
                'dynamic' => [
                    'active' => $can_pro,
                ],
            ]
        );

        $this->add_control(
            'kng_author_name',
            [
                'label' => esc_html__('Author', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'dynamic' => [
                    'active' => $can_pro,
                ],
            ]
        );

        $this->add_control(
            'kng_author_source',
            [
                'label' => esc_html__('Source / Role', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'dynamic' => [
                    'active' => $can_pro,
                ],
            ]
        );

        $this->add_control(
            'kng_label_show',
            [
                'label' => esc_html__('Show Label', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
                'condition' => [
                    'kng_block_type' => 'callout',
                ],
            ]
        );

        $this->add_control(
            'kng_label_text',
            [
                'label' => esc_html__('Label Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Callout', 'king-addons'),
                'dynamic' => [
                    'active' => $can_pro,
                ],
                'condition' => [
                    'kng_block_type' => 'callout',
                    'kng_label_show' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_quote_mark_show',
            [
                'label' => esc_html__('Show Quote Mark', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
                'condition' => [
                    'kng_block_type' => 'pull-quote',
                ],
            ]
        );

        $this->add_control(
            'kng_quote_mark_style',
            [
                'label' => esc_html__('Quote Mark Style', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'classic',
                'options' => [
                    'classic' => esc_html__('Classic', 'king-addons'),
                    'modern' => esc_html__('Modern', 'king-addons'),
                    'minimal' => esc_html__('Minimal', 'king-addons'),
                ],
                'condition' => [
                    'kng_block_type' => 'pull-quote',
                    'kng_quote_mark_show' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_quote_mark_mode',
            [
                'label' => esc_html__('Quote Mark Mode', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'corner',
                'options' => [
                    'corner' => esc_html__('Corner', 'king-addons'),
                    'inline' => esc_html__('Inline', 'king-addons'),
                    'watermark' => esc_html__('Watermark', 'king-addons'),
                ],
                'condition' => [
                    'kng_block_type' => 'pull-quote',
                    'kng_quote_mark_show' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_icon_enable',
            [
                'label' => esc_html__('Show Icon', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
                'condition' => [
                    'kng_block_type' => 'callout',
                ],
            ]
        );

        $this->add_control(
            'kng_icon',
            [
                'label' => esc_html__('Icon', 'king-addons'),
                'type' => Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fas fa-quote-left',
                    'library' => 'fa-solid',
                ],
                'condition' => [
                    'kng_block_type' => 'callout',
                    'kng_icon_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_icon_position',
            [
                'label' => esc_html__('Icon Position', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'left',
                'options' => [
                    'left' => esc_html__('Left', 'king-addons'),
                    'top' => esc_html__('Top', 'king-addons'),
                ],
                'condition' => [
                    'kng_block_type' => 'callout',
                    'kng_icon_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_link',
            [
                'label' => esc_html__('Link', 'king-addons'),
                'type' => Controls_Manager::URL,
                'dynamic' => [
                    'active' => $can_pro,
                ],
                'show_external' => true,
            ]
        );

        $this->add_control(
            'kng_link_text',
            [
                'label' => esc_html__('Link Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Read more', 'king-addons'),
                'condition' => [
                    'kng_link[url]!' => '',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Layout controls.
     *
     * @return void
     */
    protected function register_layout_controls(): void
    {
        $this->start_controls_section(
            'kng_layout_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Layout', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'kng_alignment',
            [
                'label' => esc_html__('Alignment', 'king-addons'),
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
                'prefix_class' => 'king-addons-pqcb-align%s-',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pull-quotes-callouts' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_gap',
            [
                'label' => esc_html__('Gap', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 60,
                    ],
                ],
                'default' => [
                    'size' => 14,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pull-quotes-callouts' => '--ka-pqcb-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em', 'rem'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pull-quotes-callouts' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_margin',
            [
                'label' => esc_html__('Margin', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em', 'rem'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pull-quotes-callouts' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name' => 'kng_background',
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .king-addons-pull-quotes-callouts',
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_border',
                'selector' => '{{WRAPPER}} .king-addons-pull-quotes-callouts',
            ]
        );

        $this->add_control(
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
                    '{{WRAPPER}} .king-addons-pull-quotes-callouts' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_shadow',
                'selector' => '{{WRAPPER}} .king-addons-pull-quotes-callouts',
            ]
        );

        $this->add_control(
            'kng_accent_heading',
            [
                'label' => esc_html__('Accent Line', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'kng_accent_side',
            [
                'label' => esc_html__('Accent Side', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'left',
                'options' => [
                    'left' => esc_html__('Left', 'king-addons'),
                    'right' => esc_html__('Right', 'king-addons'),
                    'top' => esc_html__('Top', 'king-addons'),
                    'bottom' => esc_html__('Bottom', 'king-addons'),
                ],
                'render_type' => 'template',
            ]
        );

        $this->add_control(
            'kng_accent_size',
            [
                'label' => esc_html__('Accent Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 20,
                    ],
                ],
                'default' => [
                    'size' => 0,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pull-quotes-callouts' => '--ka-pqcb-accent-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_accent_color',
            [
                'label' => esc_html__('Accent Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pull-quotes-callouts' => '--ka-pqcb-accent-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'kng_style_typography_section',
            [
                'label' => esc_html__('Typography', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'kng_text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pull-quotes-callouts' => '--ka-pqcb-text-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_text_typography',
                'selector' => '{{WRAPPER}} .king-addons-pull-quotes-callouts__text',
            ]
        );

        $this->add_control(
            'kng_author_color',
            [
                'label' => esc_html__('Author Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pull-quotes-callouts' => '--ka-pqcb-author-color: {{VALUE}};',
                ],
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_author_typography',
                'selector' => '{{WRAPPER}} .king-addons-pull-quotes-callouts__author',
            ]
        );

        $this->add_control(
            'kng_source_color',
            [
                'label' => esc_html__('Source Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pull-quotes-callouts' => '--ka-pqcb-source-color: {{VALUE}};',
                ],
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_source_typography',
                'selector' => '{{WRAPPER}} .king-addons-pull-quotes-callouts__source',
            ]
        );

        $this->add_control(
            'kng_label_color',
            [
                'label' => esc_html__('Label Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pull-quotes-callouts' => '--ka-pqcb-label-color: {{VALUE}};',
                ],
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'kng_label_background',
            [
                'label' => esc_html__('Label Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pull-quotes-callouts' => '--ka-pqcb-label-bg: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_label_typography',
                'selector' => '{{WRAPPER}} .king-addons-pull-quotes-callouts__label',
            ]
        );

        $this->add_responsive_control(
            'kng_label_padding',
            [
                'label' => esc_html__('Label Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', 'rem'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pull-quotes-callouts__label' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'condition' => [
                    'kng_block_type' => 'callout',
                    'kng_label_show' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_label_radius',
            [
                'label' => esc_html__('Label Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                    '%' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pull-quotes-callouts__label' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'kng_block_type' => 'callout',
                    'kng_label_show' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_label_transform',
            [
                'label' => esc_html__('Label Transform', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'uppercase',
                'options' => [
                    'uppercase' => esc_html__('Uppercase', 'king-addons'),
                    'capitalize' => esc_html__('Capitalize', 'king-addons'),
                    'lowercase' => esc_html__('Lowercase', 'king-addons'),
                    'none' => esc_html__('None', 'king-addons'),
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pull-quotes-callouts__label' => 'text-transform: {{VALUE}};',
                ],
                'condition' => [
                    'kng_block_type' => 'callout',
                    'kng_label_show' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_label_letter_spacing',
            [
                'label' => esc_html__('Label Letter Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 6,
                    ],
                    'em' => [
                        'min' => 0,
                        'max' => 0.4,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pull-quotes-callouts__label' => 'letter-spacing: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'kng_block_type' => 'callout',
                    'kng_label_show' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_link_color',
            [
                'label' => esc_html__('Link Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pull-quotes-callouts' => '--ka-pqcb-link-color: {{VALUE}};',
                ],
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_link_typography',
                'selector' => '{{WRAPPER}} .king-addons-pull-quotes-callouts__link',
            ]
        );

        $this->add_control(
            'kng_link_hover_color',
            [
                'label' => esc_html__('Link Hover Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pull-quotes-callouts__link:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'kng_style_footer_section',
            [
                'label' => esc_html__('Footer', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'kng_footer_spacing',
            [
                'label' => esc_html__('Top Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 60,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pull-quotes-callouts__footer' => 'margin-top: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_footer_gap',
            [
                'label' => esc_html__('Gap', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 40,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pull-quotes-callouts__footer' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_footer_alignment',
            [
                'label' => esc_html__('Alignment', 'king-addons'),
                'type' => Controls_Manager::CHOOSE,
                'toggle' => true,
                'options' => [
                    'flex-start' => [
                        'title' => esc_html__('Left', 'king-addons'),
                        'icon' => 'eicon-h-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'king-addons'),
                        'icon' => 'eicon-h-align-center',
                    ],
                    'flex-end' => [
                        'title' => esc_html__('Right', 'king-addons'),
                        'icon' => 'eicon-h-align-right',
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pull-quotes-callouts__footer' => 'justify-content: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_footer_direction',
            [
                'label' => esc_html__('Direction', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'row',
                'options' => [
                    'row' => esc_html__('Horizontal', 'king-addons'),
                    'column' => esc_html__('Vertical', 'king-addons'),
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pull-quotes-callouts__footer' => 'flex-direction: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_author_source_separator',
            [
                'label' => esc_html__('Separator', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'placeholder' => '—',
                'description' => esc_html__('Character between author and source (e.g. — or |)', 'king-addons'),
                'render_type' => 'template',
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'kng_style_hover_section',
            [
                'label' => esc_html__('Hover', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_hover_shadow',
                'label' => esc_html__('Box Shadow', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-pull-quotes-callouts:hover',
            ]
        );

        $this->add_control(
            'kng_hover_border_color',
            [
                'label' => esc_html__('Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pull-quotes-callouts:hover' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_hover_transform',
            [
                'label' => esc_html__('Transform', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'none',
                'options' => [
                    'none' => esc_html__('None', 'king-addons'),
                    'translateY(-4px)' => esc_html__('Lift Up', 'king-addons'),
                    'scale(1.02)' => esc_html__('Scale Up', 'king-addons'),
                    'translateY(-4px) scale(1.02)' => esc_html__('Lift + Scale', 'king-addons'),
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pull-quotes-callouts:hover' => 'transform: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_hover_transition',
            [
                'label' => esc_html__('Transition Duration (ms)', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 0,
                'max' => 1000,
                'step' => 50,
                'default' => 300,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pull-quotes-callouts' => 'transition: transform {{VALUE}}ms ease, box-shadow {{VALUE}}ms ease, border-color {{VALUE}}ms ease;',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'kng_style_mark_section',
            [
                'label' => esc_html__('Quote Mark', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'kng_block_type' => 'pull-quote',
                ],
            ]
        );

        $this->add_control(
            'kng_mark_size',
            [
                'label' => esc_html__('Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 12,
                        'max' => 200,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pull-quotes-callouts' => '--ka-pqcb-mark-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_mark_color',
            [
                'label' => esc_html__('Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pull-quotes-callouts' => '--ka-pqcb-mark-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_mark_opacity',
            [
                'label' => esc_html__('Opacity', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 0,
                'max' => 1,
                'step' => 0.05,
                'default' => 0.2,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pull-quotes-callouts' => '--ka-pqcb-mark-opacity: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_mark_scale',
            [
                'label' => esc_html__('Scale', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 0.5,
                'max' => 2,
                'step' => 0.05,
                'default' => 1,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pull-quotes-callouts' => '--ka-pqcb-mark-scale: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_mark_offset_x',
            [
                'label' => esc_html__('Offset X', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => -80,
                        'max' => 80,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pull-quotes-callouts' => '--ka-pqcb-mark-offset-x: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_mark_offset_y',
            [
                'label' => esc_html__('Offset Y', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => -80,
                        'max' => 80,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pull-quotes-callouts' => '--ka-pqcb-mark-offset-y: {{SIZE}}{{UNIT}};',
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
                    'kng_block_type' => 'callout',
                ],
            ]
        );

        $this->add_control(
            'kng_icon_size',
            [
                'label' => esc_html__('Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 12,
                        'max' => 120,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pull-quotes-callouts' => '--ka-pqcb-icon-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_icon_color',
            [
                'label' => esc_html__('Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pull-quotes-callouts' => '--ka-pqcb-icon-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_icon_offset_x',
            [
                'label' => esc_html__('Offset X', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => -60,
                        'max' => 60,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pull-quotes-callouts' => '--ka-pqcb-icon-offset-x: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_icon_offset_y',
            [
                'label' => esc_html__('Offset Y', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => -60,
                        'max' => 60,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pull-quotes-callouts' => '--ka-pqcb-icon-offset-y: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_icon_bg_heading',
            [
                'label' => esc_html__('Icon Box', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'kng_icon_background',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pull-quotes-callouts__icon' => 'background: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_icon_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 40,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pull-quotes-callouts__icon' => 'padding: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_icon_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                    '%' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pull-quotes-callouts__icon' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

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
            Core::renderProFeaturesSection($this, '', Controls_Manager::RAW_HTML, 'pull-quotes-callouts-builder', [
                'Editorial float layout with gutters and wide pull quotes',
                'Copy, share, and deep-link actions with button styles',
                'Decorative effects: gradient accents, noise, blur, and watermark controls',
                'Reveal-on-scroll animations with staggered elements',
                'Expanded dynamic tags for quote, author, label, and link',
            ]);
        }
    }

    /**
     * Preset options list.
     *
     * @return array<string, array<string, string>>
     */
    protected function get_preset_options(): array
    {
        $base_url = KING_ADDONS_URL . 'includes/widgets/Pull_Quotes_Callouts_Builder/assets/';

        return [
            'classic-accent' => [
                'title' => esc_html__('Classic Left Accent', 'king-addons'),
                'image' => $base_url . 'preset-classic-accent.svg',
            ],
            'centered-mark' => [
                'title' => esc_html__('Centered Quote Mark', 'king-addons'),
                'image' => $base_url . 'preset-centered-mark.svg',
            ],
            'modern-card' => [
                'title' => esc_html__('Modern Card', 'king-addons'),
                'image' => $base_url . 'preset-modern-card.svg',
            ],
            'minimal-inline' => [
                'title' => esc_html__('Minimal Inline', 'king-addons'),
                'image' => $base_url . 'preset-minimal-inline.svg',
            ],
            'highlight-strip' => [
                'title' => esc_html__('Highlight Strip', 'king-addons'),
                'image' => $base_url . 'preset-highlight-strip.svg',
            ],
            'side-callout' => [
                'title' => esc_html__('Side Callout', 'king-addons'),
                'image' => $base_url . 'preset-side-callout.svg',
            ],
            'badge-callout' => [
                'title' => esc_html__('Badge Callout', 'king-addons'),
                'image' => $base_url . 'preset-badge-callout.svg',
            ],
            'editorial-watermark' => [
                'title' => esc_html__('Editorial Watermark', 'king-addons'),
                'image' => $base_url . 'preset-editorial-watermark.svg',
            ],
        ];
    }

    /**
     * Sanitize block type.
     *
     * @param string $value Block type.
     *
     * @return string
     */
    protected function sanitize_block_type(string $value): string
    {
        $allowed = ['pull-quote', 'callout'];
        return in_array($value, $allowed, true) ? $value : 'pull-quote';
    }

    /**
     * Sanitize preset value.
     *
     * @param string $value Preset value.
     *
     * @return string
     */
    protected function sanitize_preset(string $value): string
    {
        $allowed = array_keys($this->get_preset_options());
        return in_array($value, $allowed, true) ? $value : 'classic-accent';
    }

    /**
     * Sanitize mark style.
     *
     * @param string $value Mark style.
     *
     * @return string
     */
    protected function sanitize_mark_style(string $value): string
    {
        $allowed = ['classic', 'modern', 'minimal'];
        return in_array($value, $allowed, true) ? $value : 'classic';
    }

    /**
     * Sanitize mark mode.
     *
     * @param string $value Mark mode.
     *
     * @return string
     */
    protected function sanitize_mark_mode(string $value): string
    {
        $allowed = ['corner', 'inline', 'watermark'];
        return in_array($value, $allowed, true) ? $value : 'corner';
    }

    /**
     * Sanitize icon position.
     *
     * @param string $value Icon position.
     *
     * @return string
     */
    protected function sanitize_icon_position(string $value): string
    {
        $allowed = ['left', 'top'];
        return in_array($value, $allowed, true) ? $value : 'left';
    }

    /**
     * Sanitize accent side.
     *
     * @param string $value Accent side.
     *
     * @return string
     */
    protected function sanitize_accent_side(string $value): string
    {
        $allowed = ['left', 'right', 'top', 'bottom'];
        return in_array($value, $allowed, true) ? $value : 'left';
    }
}
