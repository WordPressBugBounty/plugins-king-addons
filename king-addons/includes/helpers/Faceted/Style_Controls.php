<?php
/**
 * Shared Elementor style controls for Shop Filter widgets.
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Style tabs for taxonomy lists, fields, price ranges, reset, and chips.
 */
class Facet_Style_Controls
{
    /**
     * Taxonomy checkbox / swatch list.
     *
     * @param Widget_Base $widget Widget instance.
     * @return void
     */
    public static function list(Widget_Base $widget): void
    {
        $widget->start_controls_section(
            'ka_facet_list_style',
            [
                'label' => esc_html__('List', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'ka_facet_list_typography',
                'selector' => '{{WRAPPER}} .king-addons-facet__text',
            ]
        );

        $widget->add_control(
            'ka_facet_list_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-facet__text' => 'color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_control(
            'ka_facet_count_color',
            [
                'label' => esc_html__('Count Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-facet__count' => 'color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_responsive_control(
            'ka_facet_item_gap',
            [
                'label' => esc_html__('Space Between Rows', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 24],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-facet__list' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $widget->add_responsive_control(
            'ka_facet_label_gap',
            [
                'label' => esc_html__('Checkbox Gap', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 24],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-facet__label' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $widget->add_responsive_control(
            'ka_facet_row_padding',
            [
                'label' => esc_html__('Row Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-facet__label' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $widget->add_control(
            'ka_facet_hover_bg',
            [
                'label' => esc_html__('Hover Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-facet__label:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_control(
            'ka_facet_active_bg',
            [
                'label' => esc_html__('Selected Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-facet__label:has(.king-addons-facet__input:checked)' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_control(
            'ka_facet_active_color',
            [
                'label' => esc_html__('Selected Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-facet__label:has(.king-addons-facet__input:checked) .king-addons-facet__text' => 'color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_control(
            'ka_facet_checkbox_heading',
            [
                'label' => esc_html__('Checkbox', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $widget->add_responsive_control(
            'ka_facet_checkbox_size',
            [
                'label' => esc_html__('Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 12, 'max' => 28],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-facet__box' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $widget->add_responsive_control(
            'ka_facet_checkbox_radius',
            [
                'label' => esc_html__('Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 14],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-facet__box' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $widget->add_control(
            'ka_facet_checkbox_border',
            [
                'label' => esc_html__('Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-facet__box' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_control(
            'ka_facet_checkbox_bg',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-facet__box' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_control(
            'ka_facet_accent',
            [
                'label' => esc_html__('Checked Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-facet__input:checked + .king-addons-facet__box' => 'background-color: {{VALUE}}; border-color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_control(
            'ka_facet_check_color',
            [
                'label' => esc_html__('Check Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-facet__input:checked + .king-addons-facet__box::after' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_control(
            'ka_facet_swatch_heading',
            [
                'label' => esc_html__('Swatch', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $widget->add_responsive_control(
            'ka_facet_swatch_size',
            [
                'label' => esc_html__('Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 12, 'max' => 40],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-facet__swatch' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $widget->add_responsive_control(
            'ka_facet_swatch_radius',
            [
                'label' => esc_html__('Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 40],
                    '%' => ['min' => 0, 'max' => 50],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-facet__swatch' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $widget->add_control(
            'ka_facet_swatch_border',
            [
                'label' => esc_html__('Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-facet__swatch' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_control(
            'ka_facet_swatch_ring',
            [
                'label' => esc_html__('Selected Ring', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-facet__input:checked + .king-addons-facet__swatch' => 'box-shadow: 0 0 0 2px #fff, 0 0 0 3.5px {{VALUE}};',
                ],
            ]
        );

        $widget->end_controls_section();
    }

    /**
     * Text, number, and select fields.
     *
     * @param Widget_Base $widget Widget instance.
     * @param string      $selector CSS selector for the fields.
     * @return void
     */
    public static function fields(Widget_Base $widget, string $selector): void
    {
        $widget->start_controls_section(
            'ka_facet_field_style',
            [
                'label' => esc_html__('Fields', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'ka_facet_field_typography',
                'selector' => $selector,
            ]
        );

        $widget->add_control(
            'ka_facet_field_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    $selector => 'color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_control(
            'ka_facet_field_placeholder',
            [
                'label' => esc_html__('Placeholder Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    $selector . '::placeholder' => 'color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_control(
            'ka_facet_field_bg',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    $selector => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_responsive_control(
            'ka_facet_field_height',
            [
                'label' => esc_html__('Height', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 32, 'max' => 64],
                ],
                'selectors' => [
                    $selector => 'height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $widget->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'ka_facet_field_border',
                'selector' => $selector,
            ]
        );

        $widget->add_control(
            'ka_facet_field_focus',
            [
                'label' => esc_html__('Focus Border', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    $selector . ':focus' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_responsive_control(
            'ka_facet_field_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    $selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $widget->add_responsive_control(
            'ka_facet_field_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    $selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $widget->end_controls_section();
    }

    /**
     * Price range buttons.
     *
     * @param Widget_Base $widget Widget instance.
     * @return void
     */
    public static function buckets(Widget_Base $widget): void
    {
        $widget->start_controls_section(
            'ka_facet_bucket_style',
            [
                'label' => esc_html__('Price Ranges', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'ka_facet_bucket_typography',
                'selector' => '{{WRAPPER}} .king-addons-facet__bucket-btn',
            ]
        );

        $widget->start_controls_tabs('ka_facet_bucket_tabs');

        $widget->start_controls_tab(
            'ka_facet_bucket_normal',
            ['label' => esc_html__('Normal', 'king-addons')]
        );

        $widget->add_control(
            'ka_facet_bucket_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-facet__bucket-btn' => 'color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_control(
            'ka_facet_bucket_bg',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-facet__bucket-btn' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_control(
            'ka_facet_bucket_border',
            [
                'label' => esc_html__('Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-facet__bucket-btn' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $widget->end_controls_tab();

        $widget->start_controls_tab(
            'ka_facet_bucket_active',
            ['label' => esc_html__('Active', 'king-addons')]
        );

        $widget->add_control(
            'ka_facet_bucket_color_active',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-facet__bucket-btn.is-active' => 'color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_control(
            'ka_facet_bucket_bg_active',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-facet__bucket-btn.is-active' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_control(
            'ka_facet_bucket_border_active',
            [
                'label' => esc_html__('Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-facet__bucket-btn.is-active' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $widget->end_controls_tab();
        $widget->end_controls_tabs();

        $widget->add_control(
            'ka_facet_bucket_count',
            [
                'label' => esc_html__('Count Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'separator' => 'before',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-facet__bucket-count' => 'color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_responsive_control(
            'ka_facet_bucket_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 24],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-facet__bucket-btn' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $widget->add_responsive_control(
            'ka_facet_bucket_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-facet__bucket-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $widget->add_responsive_control(
            'ka_facet_bucket_gap',
            [
                'label' => esc_html__('Space Between', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 24],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-facet__buckets' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $widget->end_controls_section();
    }

    /**
     * Reset button.
     *
     * @param Widget_Base $widget Widget instance.
     * @return void
     */
    public static function button(Widget_Base $widget): void
    {
        $widget->start_controls_section(
            'ka_facet_button_style',
            [
                'label' => esc_html__('Button', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'ka_facet_button_typography',
                'selector' => '{{WRAPPER}} .king-addons-facet__button',
            ]
        );

        $widget->start_controls_tabs('ka_facet_button_tabs');

        $widget->start_controls_tab(
            'ka_facet_button_normal',
            ['label' => esc_html__('Normal', 'king-addons')]
        );

        $widget->add_control(
            'ka_facet_button_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-facet__button' => 'color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_control(
            'ka_facet_button_bg',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-facet__button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_control(
            'ka_facet_button_border_color',
            [
                'label' => esc_html__('Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-facet__button' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $widget->end_controls_tab();

        $widget->start_controls_tab(
            'ka_facet_button_hover',
            ['label' => esc_html__('Hover', 'king-addons')]
        );

        $widget->add_control(
            'ka_facet_button_color_hover',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-facet__button:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_control(
            'ka_facet_button_bg_hover',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-facet__button:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_control(
            'ka_facet_button_border_hover',
            [
                'label' => esc_html__('Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-facet__button:hover' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $widget->end_controls_tab();
        $widget->end_controls_tabs();

        $widget->add_responsive_control(
            'ka_facet_button_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'separator' => 'before',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-facet__button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $widget->add_responsive_control(
            'ka_facet_button_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-facet__button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $widget->end_controls_section();
    }

    /**
     * Active filter chips.
     *
     * @param Widget_Base $widget Widget instance.
     * @return void
     */
    public static function chips(Widget_Base $widget): void
    {
        $widget->start_controls_section(
            'ka_facet_chip_style',
            [
                'label' => esc_html__('Chips', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'ka_facet_chip_title_typography',
                'label' => esc_html__('Title Typography', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-active-filters__title',
            ]
        );

        $widget->add_control(
            'ka_facet_chip_title_color',
            [
                'label' => esc_html__('Title Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-active-filters__title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_control(
            'ka_facet_chip_heading',
            [
                'label' => esc_html__('Chip', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'ka_facet_chip_typography',
                'selector' => '{{WRAPPER}} .king-addons-active-filters__item',
            ]
        );

        $widget->start_controls_tabs('ka_facet_chip_tabs');

        $widget->start_controls_tab(
            'ka_facet_chip_normal',
            ['label' => esc_html__('Normal', 'king-addons')]
        );

        $widget->add_control(
            'ka_facet_chip_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-active-filters__item' => 'color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_control(
            'ka_facet_chip_bg',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-active-filters__item' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_control(
            'ka_facet_chip_border',
            [
                'label' => esc_html__('Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-active-filters__item' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $widget->end_controls_tab();

        $widget->start_controls_tab(
            'ka_facet_chip_hover',
            ['label' => esc_html__('Hover', 'king-addons')]
        );

        $widget->add_control(
            'ka_facet_chip_color_hover',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-active-filters__item:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_control(
            'ka_facet_chip_bg_hover',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-active-filters__item:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $widget->end_controls_tab();
        $widget->end_controls_tabs();

        $widget->add_responsive_control(
            'ka_facet_chip_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 40],
                ],
                'separator' => 'before',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-active-filters__item' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $widget->add_responsive_control(
            'ka_facet_chip_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-active-filters__item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $widget->add_responsive_control(
            'ka_facet_chip_gap',
            [
                'label' => esc_html__('Gap', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 24],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-active-filters__list' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $widget->end_controls_section();
    }
}
