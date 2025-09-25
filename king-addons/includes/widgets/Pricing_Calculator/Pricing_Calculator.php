<?php

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Repeater;
use Elementor\Group_Control_Border;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}



class Pricing_Calculator extends Widget_Base
{
    

    public function get_name(): string
    {
        return 'king-addons-pricing-calculator';
    }

    public function get_title(): string
    {
        return esc_html__('Pricing Calculator', 'king-addons');
    }

    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-pricing-calculator';
    }

    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-pricing-calculator-style'];
    }

    public function get_script_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-pricing-calculator-script'];
    }

    public function get_categories(): array
    {
        return ['king-addons'];
    }

    public function get_keywords(): array
    {
        return [
            'price',
            'pricing',
            'calculator',
            'pricing calculator',
            'quote',
            'estimate',
            'king addons',
            'king',
            'addons',
            'kingaddons',
            'king-addons'
        ];
    }

    public function get_custom_help_url()
    {
        return 'mailto:bug@kingaddons.com?subject=Bug Report - King Addons&body=Please describe the issue';
    }

    protected function register_controls()
    {
        $this->start_controls_section(
            'section_pricing_calculator',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Pricing Calculator', 'king-addons'),
            ]
        );

        if (!king_addons_freemius()->can_use_premium_code__premium_only()) {
            $this->add_control(
                'pricing_calculator_pro_notice',
                [
                    'type' => Controls_Manager::RAW_HTML,
                    'raw' => 'Advanced calculation formulas, conditional logic, and more options are available in the <strong><a href="https://kingaddons.com/pricing/?utm_source=kng-module-pricing-calculator-settings-upgrade-pro&utm_medium=plugin&utm_campaign=kng" target="_blank">Pro version</a></strong>',
                    'content_classes' => 'king-addons-pro-notice',
                ]
            );
        }

        $this->add_control(
            'calculator_title',
            [
                'label' => esc_html__('Calculator Title', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Pricing Calculator', 'king-addons'),
                'placeholder' => esc_html__('Enter title', 'king-addons'),
                'label_block' => true,
            ]
        );

        $this->add_control(
            'calculator_description',
            [
                'label' => esc_html__('Description', 'king-addons'),
                'type' => Controls_Manager::TEXTAREA,
                'default' => esc_html__('Calculate the cost of your service', 'king-addons'),
                'placeholder' => esc_html__('Enter description', 'king-addons'),
                'rows' => 5,
            ]
        );

        $this->end_controls_section();
        
        // Calculator Fields Section
        $this->start_controls_section(
            'section_calculator_fields',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Calculator Fields', 'king-addons'),
            ]
        );
        
        $repeater = new Repeater();
        
        $repeater->add_control(
            'field_type',
            [
                'label' => esc_html__('Field Type', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'number',
                'options' => [
                    'number' => esc_html__('Number Input', 'king-addons'),
                    'range' => esc_html__('Range Slider', 'king-addons'),
                    'select' => esc_html__('Dropdown Select', 'king-addons'),
                    'radio' => esc_html__('Radio Buttons', 'king-addons'),
                    'checkbox' => esc_html__('Checkbox', 'king-addons'),
                    'switch' => esc_html__('Toggle Switch', 'king-addons')
                ],
            ]
        );
        
        $repeater->add_control(
            'field_label',
            [
                'label' => esc_html__('Field Label', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Field Label', 'king-addons'),
                'label_block' => true,
            ]
        );
        
        $repeater->add_control(
            'field_id',
            [
                'label' => esc_html__('Field ID', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'placeholder' => esc_html__('e.g. quantity, service_type, rush_delivery', 'king-addons'),
                'label_block' => true,
                'description' => esc_html__('Set a unique ID for this field to use in formulas and conditions. Leave empty for auto-generated ID.', 'king-addons'),
            ]
        );
        
        $repeater->add_control(
            'field_description',
            [
                'label' => esc_html__('Description', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'label_block' => true,
            ]
        );
        
        $repeater->add_control(
            'field_unit',
            [
                'label' => esc_html__('Unit Label', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'placeholder' => esc_html__('e.g. hours, sq.ft, items', 'king-addons'),
                'conditions' => [
                    'terms' => [
                        [
                            'name' => 'field_type',
                            'operator' => 'in',
                            'value' => ['number', 'range'],
                        ],
                    ],
                ],
            ]
        );
        
        $repeater->add_control(
            'field_min',
            [
                'label' => esc_html__('Minimum Value', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'default' => 0,
                'conditions' => [
                    'terms' => [
                        [
                            'name' => 'field_type',
                            'operator' => 'in',
                            'value' => ['number', 'range'],
                        ],
                    ],
                ],
            ]
        );
        
        $repeater->add_control(
            'field_max',
            [
                'label' => esc_html__('Maximum Value', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'default' => 100,
                'conditions' => [
                    'terms' => [
                        [
                            'name' => 'field_type',
                            'operator' => 'in',
                            'value' => ['number', 'range'],
                        ],
                    ],
                ],
            ]
        );
        
        $repeater->add_control(
            'field_step',
            [
                'label' => esc_html__('Step', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'default' => 1,
                'min' => 0.01,
                'conditions' => [
                    'terms' => [
                        [
                            'name' => 'field_type',
                            'operator' => 'in',
                            'value' => ['number', 'range'],
                        ],
                    ],
                ],
            ]
        );
        
        $repeater->add_control(
            'field_default',
            [
                'label' => esc_html__('Default Value', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'default' => 0,
                'conditions' => [
                    'terms' => [
                        [
                            'name' => 'field_type',
                            'operator' => 'in',
                            'value' => ['number', 'range'],
                        ],
                    ],
                ],
            ]
        );
        
        $repeater->add_control(
            'field_options',
            [
                'label' => esc_html__('Options (Value|Label|Price)', 'king-addons'),
                'type' => Controls_Manager::TEXTAREA,
                'default' => "option1|Option 1|10\noption2|Option 2|20\noption3|Option 3|30",
                'description' => esc_html__('Enter one option per line in format: value|label|price', 'king-addons'),
                'rows' => 5,
                'conditions' => [
                    'terms' => [
                        [
                            'name' => 'field_type',
                            'operator' => 'in',
                            'value' => ['select', 'radio'],
                        ],
                    ],
                ],
            ]
        );
        
        $repeater->add_control(
            'checkbox_value',
            [
                'label' => esc_html__('Checkbox Value', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'default' => 10,
                'conditions' => [
                    'terms' => [
                        [
                            'name' => 'field_type',
                            'operator' => 'in',
                            'value' => ['checkbox', 'switch'],
                        ],
                    ],
                ],
            ]
        );
        
        $repeater->add_control(
            'field_price_type',
            [
                'label' => esc_html__('Price Calculation', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'multiply',
                'options' => [
                    'add' => esc_html__('Add Value', 'king-addons'),
                    'multiply' => esc_html__('Multiply Value', 'king-addons'),
                    'custom' => esc_html__('Custom Formula (Pro)', 'king-addons')
                ],
                'description' => esc_html__('Custom formula calculation is available in the Pro version. You can reference fields by their Field ID in custom formulas.', 'king-addons'),
            ]
        );
        
        $repeater->add_control(
            'field_price',
            [
                'label' => esc_html__('Price Amount', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'default' => 10,
                'conditions' => [
                    'terms' => [
                        [
                            'name' => 'field_price_type',
                            'operator' => '!==',
                            'value' => 'custom',
                        ],
                        [
                            'name' => 'field_type',
                            'operator' => '!in',
                            'value' => ['select', 'radio'],
                        ],
                    ],
                ],
            ]
        );

        $this->add_control(
            'calculator_fields',
            [
                'label' => esc_html__('Calculator Fields', 'king-addons'),
                'type' => Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'default' => [
                    [
                        'field_type' => 'range',
                        'field_label' => esc_html__('Quantity', 'king-addons'),
                        'field_unit' => esc_html__('items', 'king-addons'),
                        'field_min' => 1,
                        'field_max' => 100,
                        'field_step' => 1,
                        'field_default' => 10,
                        'field_price_type' => 'multiply',
                        'field_price' => 10,
                    ],
                    [
                        'field_type' => 'select',
                        'field_label' => esc_html__('Service Type', 'king-addons'),
                        'field_options' => "basic|Basic Service|50\nstandard|Standard Service|100\npremium|Premium Service|150",
                        'field_price_type' => 'add',
                    ],
                    [
                        'field_type' => 'checkbox',
                        'field_label' => esc_html__('Rush Delivery', 'king-addons'),
                        'checkbox_value' => 25,
                        'field_price_type' => 'add',
                    ],
                ],
                'title_field' => '{{{ field_label }}}',
            ]
        );
        
        $this->end_controls_section();
        
        // Calculation Settings Section
        $this->start_controls_section(
            'section_calculation_settings',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Calculation Settings', 'king-addons'),
            ]
        );
        
        $this->add_control(
            'base_price',
            [
                'label' => esc_html__('Base Price', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'default' => 0,
                'min' => 0,
                'step' => 0.01,
                'description' => esc_html__('Starting price before any calculations', 'king-addons'),
            ]
        );
        
        $this->add_control(
            'price_prefix',
            [
                'label' => esc_html__('Price Prefix', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => '$',
                'placeholder' => esc_html__('Before price', 'king-addons'),
            ]
        );
        
        $this->add_control(
            'price_suffix',
            [
                'label' => esc_html__('Price Suffix', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'placeholder' => esc_html__('After price', 'king-addons'),
            ]
        );
        
        $this->add_control(
            'decimal_places',
            [
                'label' => esc_html__('Decimal Places', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'default' => 2,
                'min' => 0,
                'max' => 10,
                'step' => 1,
                'description' => esc_html__('Number of decimal places to display', 'king-addons'),
            ]
        );
        
        $this->add_control(
            'thousand_separator',
            [
                'label' => esc_html__('Thousand Separator', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => ',',
                'placeholder' => esc_html__(',', 'king-addons'),
            ]
        );
        
        $this->add_control(
            'decimal_separator',
            [
                'label' => esc_html__('Decimal Separator', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => '.',
                'placeholder' => esc_html__('.', 'king-addons'),
            ]
        );
        
        $this->add_control(
            'live_calculation',
            [
                'label' => esc_html__('Live Calculation', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => 'yes',
                'description' => esc_html__('Update price automatically when fields change', 'king-addons'),
            ]
        );
        
        $this->add_control(
            'enable_calculate_button',
            [
                'label' => esc_html__('Enable Calculate Button', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => 'no',
                'condition' => [
                    'live_calculation' => '',
                ],
            ]
        );
        
        $this->add_control(
            'calculate_button_text',
            [
                'label' => esc_html__('Calculate Button Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Calculate', 'king-addons'),
                'condition' => [
                    'enable_calculate_button' => 'yes',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Summary Section
        $this->start_controls_section(
            'section_summary',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Summary & Results', 'king-addons'),
            ]
        );
        
        $this->add_control(
            'show_summary',
            [
                'label' => esc_html__('Show Calculation Summary', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => 'yes',
                'description' => esc_html__('Display breakdown of calculation', 'king-addons'),
            ]
        );
        
        $this->add_control(
            'summary_title',
            [
                'label' => esc_html__('Summary Title', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Summary', 'king-addons'),
                'condition' => [
                    'show_summary' => 'yes',
                ],
            ]
        );
        
        $this->add_control(
            'total_text',
            [
                'label' => esc_html__('Total Label', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Total Price:', 'king-addons'),
            ]
        );
        
        $this->end_controls_section();

        Core::renderProFeaturesSection($this, '', Controls_Manager::RAW_HTML, 'king-addons-pricing-calculator', [
            'Advanced Formula Calculation',
            'Conditional Logic',
        ]);
        
        // Container Style
        $this->start_controls_section(
            'section_container_style',
            [
                'label' => esc_html__('Container', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_responsive_control(
            'container_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-pricing-calculator' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'default' => [
                    'top' => '30',
                    'right' => '30',
                    'bottom' => '30',
                    'left' => '30',
                    'unit' => 'px',
                    'isLinked' => true,
                ],
            ]
        );
        
        $this->add_responsive_control(
            'container_margin',
            [
                'label' => esc_html__('Margin', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-pricing-calculator' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name' => 'container_background',
                'label' => esc_html__('Background', 'king-addons'),
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .king-pricing-calculator',
            ]
        );
        
        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'container_border',
                'label' => esc_html__('Border', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-pricing-calculator',
            ]
        );
        
        $this->add_responsive_control(
            'container_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-pricing-calculator' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'container_box_shadow',
                'label' => esc_html__('Box Shadow', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-pricing-calculator',
            ]
        );
        
        
        

$this->end_controls_section();
        
        
        

        // Title & Description Style
        $this->start_controls_section(
            'section_title_style',
            [
                'label' => esc_html__('Title & Description', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'title_color',
            [
                'label' => esc_html__('Title Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-pricing-calculator__title' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'title_typography',
                'selector' => '{{WRAPPER}} .king-pricing-calculator__title',
            ]
        );
        
        $this->add_responsive_control(
            'title_margin',
            [
                'label' => esc_html__('Title Margin', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-pricing-calculator__title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_control(
            'description_color',
            [
                'label' => esc_html__('Description Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-pricing-calculator__description' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'description_typography',
                'selector' => '{{WRAPPER}} .king-pricing-calculator__description',
            ]
        );
        
        $this->add_responsive_control(
            'description_margin',
            [
                'label' => esc_html__('Description Margin', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-pricing-calculator__description' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Fields Style
        $this->start_controls_section(
            'section_fields_style',
            [
                'label' => esc_html__('Fields', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_responsive_control(
            'fields_spacing',
            [
                'label' => esc_html__('Fields Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                    'em' => [
                        'min' => 0,
                        'max' => 10,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 20,
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-pricing-calculator__field:not(:last-child)' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_control(
            'field_label_heading',
            [
                'label' => esc_html__('Field Labels', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );
        
        $this->add_control(
            'field_label_color',
            [
                'label' => esc_html__('Label Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-pricing-calculator__field-label' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'field_label_typography',
                'selector' => '{{WRAPPER}} .king-pricing-calculator__field-label',
            ]
        );
        
        $this->add_responsive_control(
            'field_label_margin',
            [
                'label' => esc_html__('Label Margin', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-pricing-calculator__field-label' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'default' => [
                    'top' => '0',
                    'right' => '0',
                    'bottom' => '5',
                    'left' => '0',
                    'unit' => 'px',
                    'isLinked' => false,
                ],
            ]
        );
        
        $this->add_control(
            'field_description_color',
            [
                'label' => esc_html__('Description Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-pricing-calculator__field-description' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'field_description_typography',
                'selector' => '{{WRAPPER}} .king-pricing-calculator__field-description',
            ]
        );
        
        $this->add_responsive_control(
            'field_description_margin',
            [
                'label' => esc_html__('Description Margin', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-pricing-calculator__field-description' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_control(
            'input_fields_heading',
            [
                'label' => esc_html__('Input Fields', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );
        
        $this->add_responsive_control(
            'input_padding',
            [
                'label' => esc_html__('Input Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-pricing-calculator__field input[type="number"], 
                     {{WRAPPER}} .king-pricing-calculator__field select' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->start_controls_tabs('input_style_tabs');
        
        $this->start_controls_tab(
            'input_normal_tab',
            [
                'label' => esc_html__('Normal', 'king-addons'),
            ]
        );
        
        $this->add_control(
            'input_text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-pricing-calculator__field input, 
                     {{WRAPPER}} .king-pricing-calculator__field select,
                     {{WRAPPER}} .king-pricing-calculator__field input::placeholder' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'input_background_color',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-pricing-calculator__field input, 
                     {{WRAPPER}} .king-pricing-calculator__field select' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'input_border',
                'label' => esc_html__('Border', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-pricing-calculator__field input, 
                               {{WRAPPER}} .king-pricing-calculator__field select',
            ]
        );
        
        $this->add_responsive_control(
            'input_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-pricing-calculator__field input, 
                     {{WRAPPER}} .king-pricing-calculator__field select' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'input_box_shadow',
                'selector' => '{{WRAPPER}} .king-pricing-calculator__field input, 
                               {{WRAPPER}} .king-pricing-calculator__field select',
            ]
        );
        
        $this->end_controls_tab();
        
        $this->start_controls_tab(
            'input_focus_tab',
            [
                'label' => esc_html__('Focus', 'king-addons'),
            ]
        );
        
        $this->add_control(
            'input_focus_text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-pricing-calculator__field input:focus, 
                     {{WRAPPER}} .king-pricing-calculator__field select:focus' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'input_focus_background_color',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-pricing-calculator__field input:focus, 
                     {{WRAPPER}} .king-pricing-calculator__field select:focus' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'input_focus_border_color',
            [
                'label' => esc_html__('Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-pricing-calculator__field input:focus, 
                     {{WRAPPER}} .king-pricing-calculator__field select:focus' => 'border-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'input_focus_box_shadow',
                'selector' => '{{WRAPPER}} .king-pricing-calculator__field input:focus, 
                               {{WRAPPER}} .king-pricing-calculator__field select:focus',
            ]
        );
        
        $this->end_controls_tab();
        
        $this->end_controls_tabs();
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'input_typography',
                'selector' => '{{WRAPPER}} .king-pricing-calculator__field input, 
                               {{WRAPPER}} .king-pricing-calculator__field select,
                               {{WRAPPER}} .king-pricing-calculator__field-unit',
                'separator' => 'before',
            ]
        );
        
        $this->add_control(
            'range_slider_heading',
            [
                'label' => esc_html__('Range Slider', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );
        
        $this->add_control(
            'range_slider_track_color',
            [
                'label' => esc_html__('Track Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-pricing-calculator__field input[type="range"]' => '--track-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'range_slider_progress_color',
            [
                'label' => esc_html__('Progress Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-pricing-calculator__field input[type="range"]' => '--progress-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'range_slider_thumb_color',
            [
                'label' => esc_html__('Thumb Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-pricing-calculator__field input[type="range"]' => '--thumb-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_responsive_control(
            'range_slider_track_height',
            [
                'label' => esc_html__('Track Height', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 1,
                        'max' => 20,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-pricing-calculator__field input[type="range"]' => '--track-height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_responsive_control(
            'range_slider_thumb_size',
            [
                'label' => esc_html__('Thumb Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 10,
                        'max' => 40,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-pricing-calculator__field input[type="range"]' => '--thumb-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Calculate Button Style
        $this->start_controls_section(
            'section_button_style',
            [
                'label' => esc_html__('Calculate Button', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'enable_calculate_button' => 'yes',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'button_typography',
                'selector' => '{{WRAPPER}} .king-pricing-calculator__calculate-button',
            ]
        );
        
        $this->add_responsive_control(
            'button_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-pricing-calculator__calculate-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_responsive_control(
            'button_margin',
            [
                'label' => esc_html__('Margin', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-pricing-calculator__calculate-button-wrapper' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'default' => [
                    'top' => '20',
                    'right' => '0',
                    'bottom' => '20',
                    'left' => '0',
                    'unit' => 'px',
                    'isLinked' => false,
                ],
            ]
        );
        
        $this->start_controls_tabs('button_style_tabs');
        
        $this->start_controls_tab(
            'button_normal_tab',
            [
                'label' => esc_html__('Normal', 'king-addons'),
            ]
        );
        
        $this->add_control(
            'button_text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-pricing-calculator__calculate-button' => 'color: {{VALUE}};',
                ],
                'default' => '#ffffff',
            ]
        );
        
        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name' => 'button_background',
                'label' => esc_html__('Background', 'king-addons'),
                'types' => ['classic', 'gradient'],
                'exclude' => ['image'],
                'selector' => '{{WRAPPER}} .king-pricing-calculator__calculate-button',
                'fields_options' => [
                    'background' => [
                        'default' => 'classic',
                    ],
                    'color' => [
                        'default' => '#5cb85c',
                    ],
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'button_border',
                'label' => esc_html__('Border', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-pricing-calculator__calculate-button',
            ]
        );
        
        $this->add_responsive_control(
            'button_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-pricing-calculator__calculate-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'button_box_shadow',
                'selector' => '{{WRAPPER}} .king-pricing-calculator__calculate-button',
            ]
        );
        
        $this->end_controls_tab();
        
        $this->start_controls_tab(
            'button_hover_tab',
            [
                'label' => esc_html__('Hover', 'king-addons'),
            ]
        );
        
        $this->add_control(
            'button_hover_text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-pricing-calculator__calculate-button:hover' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name' => 'button_hover_background',
                'label' => esc_html__('Background', 'king-addons'),
                'types' => ['classic', 'gradient'],
                'exclude' => ['image'],
                'selector' => '{{WRAPPER}} .king-pricing-calculator__calculate-button:hover',
                'fields_options' => [
                    'background' => [
                        'default' => 'classic',
                    ],
                    'color' => [
                        'default' => '#449d44',
                    ],
                ],
            ]
        );
        
        $this->add_control(
            'button_hover_border_color',
            [
                'label' => esc_html__('Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-pricing-calculator__calculate-button:hover' => 'border-color: {{VALUE}};',
                ],
                'condition' => [
                    'button_border_border!' => '',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'button_hover_box_shadow',
                'selector' => '{{WRAPPER}} .king-pricing-calculator__calculate-button:hover',
            ]
        );
        
        $this->add_control(
            'button_hover_transition',
            [
                'label' => esc_html__('Transition Duration', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'default' => [
                    'size' => 0.3,
                ],
                'range' => [
                    'px' => [
                        'max' => 3,
                        'step' => 0.1,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-pricing-calculator__calculate-button' => 'transition: all {{SIZE}}s ease;',
                ],
            ]
        );
        
        $this->end_controls_tab();
        
        $this->end_controls_tabs();
        
        $this->end_controls_section();
        
        // Summary Style
        $this->start_controls_section(
            'section_summary_style',
            [
                'label' => esc_html__('Summary & Results', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'summary_title_color',
            [
                'label' => esc_html__('Summary Title Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-pricing-calculator__summary-title' => 'color: {{VALUE}};',
                ],
                'condition' => [
                    'show_summary' => 'yes',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'summary_title_typography',
                'selector' => '{{WRAPPER}} .king-pricing-calculator__summary-title',
                'condition' => [
                    'show_summary' => 'yes',
                ],
            ]
        );
        
        $this->add_responsive_control(
            'summary_title_margin',
            [
                'label' => esc_html__('Summary Title Margin', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-pricing-calculator__summary-title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'condition' => [
                    'show_summary' => 'yes',
                ],
            ]
        );
        
        $this->add_control(
            'summary_container_heading',
            [
                'label' => esc_html__('Summary Container', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'show_summary' => 'yes',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name' => 'summary_background',
                'label' => esc_html__('Background', 'king-addons'),
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .king-pricing-calculator__summary',
                'condition' => [
                    'show_summary' => 'yes',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'summary_border',
                'label' => esc_html__('Border', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-pricing-calculator__summary',
                'condition' => [
                    'show_summary' => 'yes',
                ],
            ]
        );
        
        $this->add_responsive_control(
            'summary_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-pricing-calculator__summary' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'condition' => [
                    'show_summary' => 'yes',
                ],
            ]
        );
        
        $this->add_responsive_control(
            'summary_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-pricing-calculator__summary' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'condition' => [
                    'show_summary' => 'yes',
                ],
                'default' => [
                    'top' => '15',
                    'right' => '15',
                    'bottom' => '15',
                    'left' => '15',
                    'unit' => 'px',
                    'isLinked' => true,
                ],
            ]
        );
        
        $this->add_responsive_control(
            'summary_margin',
            [
                'label' => esc_html__('Margin', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-pricing-calculator__summary' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'condition' => [
                    'show_summary' => 'yes',
                ],
                'default' => [
                    'top' => '20',
                    'right' => '0',
                    'bottom' => '0',
                    'left' => '0',
                    'unit' => 'px',
                    'isLinked' => false,
                ],
            ]
        );
        
        $this->add_control(
            'summary_item_heading',
            [
                'label' => esc_html__('Summary Items', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'show_summary' => 'yes',
                ],
            ]
        );
        
        $this->add_control(
            'summary_item_color',
            [
                'label' => esc_html__('Item Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-pricing-calculator__summary-item' => 'color: {{VALUE}};',
                ],
                'condition' => [
                    'show_summary' => 'yes',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'summary_item_typography',
                'selector' => '{{WRAPPER}} .king-pricing-calculator__summary-item',
                'condition' => [
                    'show_summary' => 'yes',
                ],
            ]
        );
        
        $this->add_responsive_control(
            'summary_item_spacing',
            [
                'label' => esc_html__('Item Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                    'em' => [
                        'min' => 0,
                        'max' => 5,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-pricing-calculator__summary-item:not(:last-child)' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'show_summary' => 'yes',
                ],
            ]
        );
        
        $this->add_control(
            'total_heading',
            [
                'label' => esc_html__('Total Price', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );
        
        $this->add_control(
            'total_label_color',
            [
                'label' => esc_html__('Total Label Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-pricing-calculator__total-label' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'total_label_typography',
                'selector' => '{{WRAPPER}} .king-pricing-calculator__total-label',
            ]
        );
        
        $this->add_control(
            'total_price_color',
            [
                'label' => esc_html__('Total Price Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-pricing-calculator__total-price' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'total_price_typography',
                'selector' => '{{WRAPPER}} .king-pricing-calculator__total-price',
            ]
        );
        
        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name' => 'total_background',
                'label' => esc_html__('Background', 'king-addons'),
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .king-pricing-calculator__total',
            ]
        );
        
        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'total_border',
                'label' => esc_html__('Border', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-pricing-calculator__total',
            ]
        );
        
        $this->add_responsive_control(
            'total_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-pricing-calculator__total' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_responsive_control(
            'total_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-pricing-calculator__total' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'default' => [
                    'top' => '15',
                    'right' => '15',
                    'bottom' => '15',
                    'left' => '15',
                    'unit' => 'px',
                    'isLinked' => true,
                ],
            ]
        );
        
        $this->add_responsive_control(
            'total_margin',
            [
                'label' => esc_html__('Margin', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-pricing-calculator__total' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'default' => [
                    'top' => '20',
                    'right' => '0',
                    'bottom' => '0',
                    'left' => '0',
                    'unit' => 'px',
                    'isLinked' => false,
                ],
            ]
        );
        
        $this->end_controls_section();
    }
    
    /**
     * Format a number with proper separators
     *
     * @param float $number The number to format
     * @param array $settings Widget settings
     * @return string Formatted number
     */
    protected function format_number($number, $settings)
    {
        $decimal_places = intval($settings['decimal_places']);
        // Sanitize separators to prevent XSS
        $thousand_separator = wp_strip_all_tags($settings['thousand_separator']);
        $decimal_separator = wp_strip_all_tags($settings['decimal_separator']);
        
        return number_format($number, $decimal_places, $decimal_separator, $thousand_separator);
    }
    
    /**
     * Render field based on its type
     *
     * @param array $field Field settings
     * @param string $field_id Unique field ID
     * @return string HTML markup for the field
     */
    protected function render_field($field, $field_id)
    {
        $html = '<div class="king-pricing-calculator__field" data-field-id="' . esc_attr($field_id) . '" data-field-type="' . esc_attr($field['field_type']) . '" data-price-type="' . esc_attr($field['field_price_type']) . '"';
        
        switch ($field['field_type']) {
            case 'number':
            case 'range':
                $html .= ' data-price="' . esc_attr($field['field_price']) . '"';
                break;
                
            case 'checkbox':
            case 'switch':
                $html .= ' data-price="' . esc_attr($field['checkbox_value']) . '"';
                break;
        }
        
        $html .= '>';
        
        // Field Label
        if (!empty($field['field_label'])) {
            $html .= '<label class="king-pricing-calculator__field-label" for="' . esc_attr($field_id) . '">' . esc_html($field['field_label']) . '</label>';
        }
        
        // Field Description
        if (!empty($field['field_description'])) {
            $html .= '<div class="king-pricing-calculator__field-description" id="' . esc_attr($field_id) . '-desc">' . esc_html($field['field_description']) . '</div>';
        }
        
        // Field Input
        $html .= '<div class="king-pricing-calculator__field-input">';
        
        switch ($field['field_type']) {
            case 'number':
                $html .= $this->render_number_field($field, $field_id);
                break;
                
            case 'range':
                $html .= $this->render_range_field($field, $field_id);
                break;
                
            case 'select':
                $html .= $this->render_select_field($field, $field_id);
                break;
                
            case 'radio':
                $html .= $this->render_radio_field($field, $field_id);
                break;
                
            case 'checkbox':
                $html .= $this->render_checkbox_field($field, $field_id);
                break;
                
            case 'switch':
                $html .= $this->render_switch_field($field, $field_id);
                break;
        }
        
        $html .= '</div></div>';
        
        return $html;
    }
    
    /**
     * Render a number input field
     * 
     * @param array $field Field settings
     * @param string $field_id Unique field ID
     * @return string HTML markup for number field
     */
    protected function render_number_field($field, $field_id)
    {
        $desc_id = !empty($field['field_description']) ? $field_id . '-desc' : '';
        $aria_desc = !empty($desc_id) ? ' aria-describedby="' . esc_attr($desc_id) . '"' : '';
        
        $html = '<div class="king-pricing-calculator__number-field">';
        $html .= '<input type="number" id="' . esc_attr($field_id) . '" name="' . esc_attr($field_id) . '"';
        $html .= ' min="' . esc_attr($field['field_min']) . '" max="' . esc_attr($field['field_max']) . '" step="' . esc_attr($field['field_step']) . '"';
        $html .= ' value="' . esc_attr($field['field_default']) . '"';
        $html .= ' class="king-pricing-calculator__input"' . $aria_desc . '>';
        
        if (!empty($field['field_unit'])) {
            $html .= '<span class="king-pricing-calculator__field-unit" aria-hidden="true">' . esc_html($field['field_unit']) . '</span>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render a range slider field
     * 
     * @param array $field Field settings
     * @param string $field_id Unique field ID
     * @return string HTML markup for range field
     */
    protected function render_range_field($field, $field_id)
    {
        $desc_id = !empty($field['field_description']) ? $field_id . '-desc' : '';
        $aria_desc = !empty($desc_id) ? ' aria-describedby="' . esc_attr($desc_id) . '"' : '';
        $value_id = $field_id . '-value';
        
        $html = '<div class="king-pricing-calculator__range-field">';
        $html .= '<input type="range" id="' . esc_attr($field_id) . '" name="' . esc_attr($field_id) . '"';
        $html .= ' min="' . esc_attr($field['field_min']) . '" max="' . esc_attr($field['field_max']) . '" step="' . esc_attr($field['field_step']) . '"';
        $html .= ' value="' . esc_attr($field['field_default']) . '"';
        $html .= ' class="king-pricing-calculator__range"' . $aria_desc . ' aria-valuetext="' . esc_attr($field['field_default']) . (!empty($field['field_unit']) ? ' ' . $field['field_unit'] : '') . '">';
        
        $html .= '<div class="king-pricing-calculator__range-value-display" aria-hidden="true">';
        $html .= '<span class="king-pricing-calculator__range-value" id="' . esc_attr($value_id) . '">' . esc_html($field['field_default']) . '</span>';
        
        if (!empty($field['field_unit'])) {
            $html .= ' <span class="king-pricing-calculator__field-unit">' . esc_html($field['field_unit']) . '</span>';
        }
        
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render a select dropdown field
     * 
     * @param array $field Field settings
     * @param string $field_id Unique field ID
     * @return string HTML markup for select field
     */
    protected function render_select_field($field, $field_id)
    {
        $options = $this->parse_options($field['field_options']);
        $desc_id = !empty($field['field_description']) ? $field_id . '-desc' : '';
        $aria_desc = !empty($desc_id) ? ' aria-describedby="' . esc_attr($desc_id) . '"' : '';
        
        $html = '<select id="' . esc_attr($field_id) . '" name="' . esc_attr($field_id) . '" class="king-pricing-calculator__select"' . $aria_desc . '>';
        
        foreach ($options as $option) {
            $html .= '<option value="' . esc_attr($option['value']) . '" data-price="' . esc_attr($option['price']) . '">' . esc_html($option['label']) . '</option>';
        }
        
        $html .= '</select>';
        
        return $html;
    }
    
    /**
     * Render radio button options
     * 
     * @param array $field Field settings
     * @param string $field_id Unique field ID
     * @return string HTML markup for radio buttons
     */
    protected function render_radio_field($field, $field_id)
    {
        $options = $this->parse_options($field['field_options']);
        $desc_id = !empty($field['field_description']) ? $field_id . '-desc' : '';
        $fieldset_id = $field_id . '-group';
        
        $html = '<fieldset id="' . esc_attr($fieldset_id) . '" class="king-pricing-calculator__radio-group">';
        if (!empty($desc_id)) {
            $html .= '<div aria-describedby="' . esc_attr($desc_id) . '" hidden></div>';
        }
        
        foreach ($options as $index => $option) {
            $option_id = $field_id . '_' . $index;
            
            $html .= '<div class="king-pricing-calculator__radio-option">';
            $html .= '<input type="radio" id="' . esc_attr($option_id) . '" name="' . esc_attr($field_id) . '"';
            $html .= ' value="' . esc_attr($option['value']) . '" data-price="' . esc_attr($option['price']) . '"';
            
            if ($index === 0) {
                $html .= ' checked';
            }
            
            $html .= ' class="king-pricing-calculator__radio">';
            $html .= '<label for="' . esc_attr($option_id) . '">' . esc_html($option['label']) . '</label>';
            $html .= '</div>';
        }
        
        $html .= '</fieldset>';
        
        return $html;
    }
    
    /**
     * Render a checkbox field
     * 
     * @param array $field Field settings
     * @param string $field_id Unique field ID
     * @return string HTML markup for checkbox
     */
    protected function render_checkbox_field($field, $field_id)
    {
        $desc_id = !empty($field['field_description']) ? $field_id . '-desc' : '';
        $aria_desc = !empty($desc_id) ? ' aria-describedby="' . esc_attr($desc_id) . '"' : '';
        
        $html = '<div class="king-pricing-calculator__checkbox-field">';
        $html .= '<input type="checkbox" id="' . esc_attr($field_id) . '" name="' . esc_attr($field_id) . '"';
        $html .= ' class="king-pricing-calculator__checkbox"' . $aria_desc . '>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render a toggle switch field
     * 
     * @param array $field Field settings
     * @param string $field_id Unique field ID
     * @return string HTML markup for switch
     */
    protected function render_switch_field($field, $field_id)
    {
        $desc_id = !empty($field['field_description']) ? $field_id . '-desc' : '';
        $aria_desc = !empty($desc_id) ? ' aria-describedby="' . esc_attr($desc_id) . '"' : '';
        
        $html = '<label class="king-pricing-calculator__switch">';
        $html .= '<input type="checkbox" id="' . esc_attr($field_id) . '" name="' . esc_attr($field_id) . '"';
        $html .= ' class="king-pricing-calculator__switch-input"' . $aria_desc . ' role="switch">';
        $html .= '<span class="king-pricing-calculator__switch-slider" aria-hidden="true"></span>';
        $html .= '</label>';
        
        return $html;
    }
    
    /**
     * Parse options string into structured array
     * 
     * @param string $options_string Options in format "value|label|price"
     * @return array Structured options array
     */
    protected function parse_options($options_string)
    {
        $options = [];
        $lines = explode("\n", $options_string);
        
        foreach ($lines as $line) {
            $parts = explode('|', trim($line));
            
            if (count($parts) >= 3) {
                $options[] = [
                    'value' => $parts[0],
                    'label' => $parts[1],
                    'price' => floatval($parts[2]),
                ];
            }
        }
        
        return $options;
    }
    
    /**
     * Render widget output on the frontend
     */
    protected function render()
    {
        $settings = $this->get_settings_for_display();
        $id_int = substr($this->get_id_int(), 0, 3);
        
        $calculator_id = 'king-pricing-calculator-' . $this->get_id();
        $calculator_classes = ['king-pricing-calculator'];
        
        if ($settings['live_calculation'] === 'yes') {
            $calculator_classes[] = 'king-pricing-calculator--live';
        }
        
        if (!king_addons_freemius()->can_use_premium_code__premium_only()) {
        $this->add_render_attribute('calculator', [
            'id' => $calculator_id,
            'class' => $calculator_classes,
            'data-base-price' => floatval($settings['base_price']),
            'data-decimal-places' => intval($settings['decimal_places']),
            'data-thousand-separator' => esc_attr($settings['thousand_separator']),
            'data-decimal-separator' => esc_attr($settings['decimal_separator']),
            'data-price-prefix' => esc_attr($settings['price_prefix']),
            'data-price-suffix' => esc_attr($settings['price_suffix']),
        ]);}

        ?>
        <div <?php $this->print_render_attribute_string('calculator'); ?>>
            <?php if (!empty($settings['calculator_title'])) : ?>
                <h2 class="king-pricing-calculator__title"><?php echo esc_html($settings['calculator_title']); ?></h2>
            <?php endif; ?>
            
            <?php if (!empty($settings['calculator_description'])) : ?>
                <div class="king-pricing-calculator__description"><?php echo esc_html($settings['calculator_description']); ?></div>
            <?php endif; ?>
            
            <div class="king-pricing-calculator__fields">
                <?php
                if (!empty($settings['calculator_fields'])) {
                    foreach ($settings['calculator_fields'] as $index => $field) {
                        // Use custom field ID if provided, otherwise generate an automatic one
                        if (!empty($field['field_id'])) {
                            $field_id = 'king-calc-' . sanitize_key($field['field_id']);
                        } else {
                            $field_id = 'king-calculator-field-' . $id_int . '-' . $index;
                        }
                        echo $this->render_field($field, $field_id);
                    }
                }
                ?>
            </div>
            
            <?php if ($settings['enable_calculate_button'] === 'yes' && $settings['live_calculation'] !== 'yes') : ?>
                <div class="king-pricing-calculator__calculate-button-wrapper">
                    <button type="button" class="king-pricing-calculator__calculate-button">
                        <?php echo esc_html($settings['calculate_button_text']); ?>
                    </button>
                </div>
            <?php endif; ?>
            
            <?php if ($settings['show_summary'] === 'yes') : ?>
                <div class="king-pricing-calculator__summary">
                    <?php if (!empty($settings['summary_title'])) : ?>
                        <h3 class="king-pricing-calculator__summary-title"><?php echo esc_html($settings['summary_title']); ?></h3>
                    <?php endif; ?>
                    
                    <div class="king-pricing-calculator__summary-items"></div>
                </div>
            <?php endif; ?>
            
            <div class="king-pricing-calculator__total">
                <div class="king-pricing-calculator__total-content">
                    <span class="king-pricing-calculator__total-label"><?php echo esc_html($settings['total_text']); ?></span>
                    <span class="king-pricing-calculator__total-price">
                        <?php echo esc_html($settings['price_prefix'] . $this->format_number($settings['base_price'], $settings) . $settings['price_suffix']); ?>
                    </span>
                </div>
            </div>
            
            <?php if (!king_addons_freemius()->can_use_premium_code__premium_only()) : ?>
                <div class="king-pricing-calculator__pro-features">
                    <a href="https://kingaddons.com/pricing/?utm_source=kng-module-pricing-calculator-widget-upgrade-pro&utm_medium=plugin&utm_campaign=kng" target="_blank">
                        <?php echo esc_html__('Notice for Editors: Upgrade to Pro for advanced pricing formulas, conditional logic & more features', 'king-addons'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
} 