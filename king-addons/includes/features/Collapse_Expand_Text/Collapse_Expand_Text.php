<?php /** @noinspection SpellCheckingInspection, PhpUnused */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Element_Base;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class Collapse_Expand_Text
{
    private static ?Collapse_Expand_Text $_instance = null;

    public static function instance(): Collapse_Expand_Text
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /** @noinspection DuplicatedCode */
    public function __construct()
    {
        // Add controls to default text widget
        add_action('elementor/element/text-editor/section_style/after_section_end', [__CLASS__, 'addControls'], 1);
        // Add controls to styled text builder widget
        add_action('elementor/element/king-addons-styled-text-builder/kng_styled_txt_style_section_commmon_styles/after_section_end', [__CLASS__, 'addControls'], 1);
        // Add controls to other text-related widgets
        add_action('elementor/element/heading/section_style/after_section_end', [__CLASS__, 'addControls'], 1);
        // Add controls to common elements as well for flexibility
        add_action('elementor/element/common/_section_style/after_section_end', [__CLASS__, 'addControls'], 1);
        
        // Enqueue scripts for editor preview
        add_action('elementor/preview/enqueue_scripts', [__CLASS__, 'enqueueScripts'], 1);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueueScripts'], 1);
        
        // Add CSS and data attributes to output
        add_action('elementor/frontend/before_render', [__CLASS__, 'renderCSS'], 1);
        add_action('elementor/frontend/before_render', [__CLASS__, 'addDataAttributes'], 1);
    }

    public static function enqueueScripts(): void
    {
        $script_key = KING_ADDONS_ASSETS_UNIQUE_KEY . '-collapse-expand-text-script';
        $preview_key = KING_ADDONS_ASSETS_UNIQUE_KEY . '-collapse-expand-text-preview-handler';
        $style_key = KING_ADDONS_ASSETS_UNIQUE_KEY . '-collapse-expand-text-style';
        
        if (!wp_script_is($script_key)) {
            wp_enqueue_script(
                $script_key, 
                KING_ADDONS_URL . 'includes/features/Collapse_Expand_Text/script.js', 
                array('jquery'), 
                KING_ADDONS_VERSION,
                true
            );
        }
        
        // Enqueue preview handler for editor mode
        if (!wp_script_is($preview_key)) {
            wp_enqueue_script(
                $preview_key, 
                KING_ADDONS_URL . 'includes/features/Collapse_Expand_Text/preview-handler.js', 
                array('jquery'), 
                KING_ADDONS_VERSION,
                true
            );
        }
        
        if (!wp_style_is($style_key)) {
            wp_enqueue_style(
                $style_key, 
                KING_ADDONS_URL . 'includes/features/Collapse_Expand_Text/style.css', 
                array(), 
                KING_ADDONS_VERSION
            );
        }
    }

    public static function addControls(Element_Base $element): void
    {
        $element->start_controls_section(
            'kng_collapse_expand_text_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Collapse & Expand Text', 'king-addons'),
                'tab' => Controls_Manager::TAB_ADVANCED
            ]
        );

        $element->add_control(
            'kng_collapse_expand_text_enable',
            [
                'label' => esc_html__('Enable Collapse & Expand', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => '',
                'prefix_class' => 'kng-collapse-expand-',
                'frontend_available' => true
            ]
        );

        $element->add_control(
            'kng_collapse_expand_text_height',
            [
                'label' => esc_html__('Collapsed Height', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 20,
                        'max' => 500,
                        'step' => 5,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 80,
                ],
                'description' => esc_html__('Height to show when collapsed', 'king-addons'),
                'frontend_available' => true,
                'condition' => [
                    'kng_collapse_expand_text_enable' => 'yes'
                ]
            ]
        );

        $element->add_control(
            'kng_collapse_expand_text_show_more_text',
            [
                'label' => esc_html__('Show More Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Read more', 'king-addons'),
                'placeholder' => esc_html__('Read more', 'king-addons'),
                'frontend_available' => true,
                'condition' => [
                    'kng_collapse_expand_text_enable' => 'yes'
                ]
            ]
        );

        $element->add_control(
            'kng_collapse_expand_text_show_less_text',
            [
                'label' => esc_html__('Show Less Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Read less', 'king-addons'),
                'placeholder' => esc_html__('Read less', 'king-addons'),
                'frontend_available' => true,
                'condition' => [
                    'kng_collapse_expand_text_enable' => 'yes'
                ]
            ]
        );

        $element->add_control(
            'kng_collapse_expand_text_animation_duration',
            [
                'label' => esc_html__('Animation Duration', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['ms'],
                'range' => [
                    'ms' => [
                        'min' => 100,
                        'max' => 2000,
                        'step' => 50,
                    ],
                ],
                'default' => [
                    'unit' => 'ms',
                    'size' => 300,
                ],
                'frontend_available' => true,
                'condition' => [
                    'kng_collapse_expand_text_enable' => 'yes'
                ]
            ]
        );

        $element->add_control(
            'kng_collapse_expand_text_divider_style',
            [
                'type' => Controls_Manager::DIVIDER,
                'condition' => [
                    'kng_collapse_expand_text_enable' => 'yes'
                ]
            ]
        );

        $element->add_control(
            'kng_collapse_expand_text_button_position',
            [
                'label' => esc_html__('Button Position', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'right',
                'options' => [
                    'left' => esc_html__('Left', 'king-addons'),
                    'center' => esc_html__('Center', 'king-addons'),
                    'right' => esc_html__('Right', 'king-addons'),
                ],
                'frontend_available' => true,
                'condition' => [
                    'kng_collapse_expand_text_enable' => 'yes'
                ]
            ]
        );

        $element->add_control(
            'kng_collapse_expand_text_button_color',
            [
                'label' => esc_html__('Button Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#007cba',
                'selectors' => [
                    '{{WRAPPER}} .kng-collapse-expand-button' => 'color: {{VALUE}};',
                ],
                'condition' => [
                    'kng_collapse_expand_text_enable' => 'yes'
                ]
            ]
        );

        $element->add_control(
            'kng_collapse_expand_text_button_hover_color',
            [
                'label' => esc_html__('Button Hover Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#005a87',
                'selectors' => [
                    '{{WRAPPER}} .kng-collapse-expand-button:hover' => 'color: {{VALUE}};',
                ],
                'condition' => [
                    'kng_collapse_expand_text_enable' => 'yes'
                ]
            ]
        );

        $element->add_control(
            'kng_collapse_expand_text_button_font_size',
            [
                'label' => esc_html__('Button Font Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => [
                        'min' => 10,
                        'max' => 50,
                        'step' => 1,
                    ],
                    'em' => [
                        'min' => 0.5,
                        'max' => 3,
                        'step' => 0.1,
                    ],
                    'rem' => [
                        'min' => 0.5,
                        'max' => 3,
                        'step' => 0.1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 14,
                ],
                'selectors' => [
                    '{{WRAPPER}} .kng-collapse-expand-button' => 'font-size: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'kng_collapse_expand_text_enable' => 'yes'
                ]
            ]
        );

        // $element->add_control(
        //     'kng_collapse_expand_text_fade_effect',
        //     [
        //         'label' => esc_html__('Fade Effect', 'king-addons'),
        //         'type' => Controls_Manager::SWITCHER,
        //         'label_on' => esc_html__('Yes', 'king-addons'),
        //         'label_off' => esc_html__('No', 'king-addons'),
        //         'return_value' => 'yes',
        //         'default' => 'yes',
        //         'description' => esc_html__('Add a fade effect to the collapsed text bottom', 'king-addons'),
        //         'frontend_available' => true,
        //         'condition' => [
        //             'kng_collapse_expand_text_enable' => 'yes'
        //         ]
        //     ]
        // );

        $element->end_controls_section();
    }

    /**
     * Render inline CSS for the collapse/expand functionality
     */
    public static function renderCSS(Element_Base $element): void
    {
        $settings = $element->get_settings_for_display();
        $element_id = $element->get_id();

        // Only render CSS if the feature is enabled
        if (empty($settings['kng_collapse_expand_text_enable']) || 'yes' !== $settings['kng_collapse_expand_text_enable']) {
            return;
        }

        $collapsed_height = intval($settings['kng_collapse_expand_text_height']['size'] ?? 80);
        $animation_duration = intval($settings['kng_collapse_expand_text_animation_duration']['size'] ?? 300);
        $button_position = $settings['kng_collapse_expand_text_button_position'] ?? 'right';
        $fade_effect = $settings['kng_collapse_expand_text_fade_effect'] ?? 'yes';

        $css = sprintf(
            '<style id="kng-collapse-expand-%1$s">
                .elementor-element-%1$s.kng-collapse-expand-yes {
                    position: relative;
                }
                .elementor-element-%1$s .kng-collapse-expand-content {
                    transition: max-height %2$dms ease;
                    overflow: hidden;
                    position: relative;
                }
                .elementor-element-%1$s .kng-collapse-expand-content.collapsed {
                    max-height: %3$dpx;
                }
                .elementor-element-%1$s .kng-collapse-expand-content.expanded {
                    max-height: none;
                }
                .elementor-element-%1$s .kng-collapse-expand-button-wrapper {
                    text-align: %4$s;
                    margin-top: 10px;
                }
                .elementor-element-%1$s .kng-collapse-expand-button {
                    background: none;
                    border: none;
                    cursor: pointer;
                    text-decoration: underline;
                    font-family: inherit;
                    padding: 5px 0;
                    transition: color 0.3s ease;
                }
                .elementor-element-%1$s .kng-collapse-expand-button:focus {
                    outline: 2px solid #007cba;
                    outline-offset: 2px;
                }',
            esc_attr($element_id),
            $animation_duration,
            $collapsed_height,
            esc_attr($button_position)
        );

        // if ('yes' === $fade_effect) {
        //     $css .= sprintf(
        //         '.elementor-element-%1$s .kng-collapse-expand-content.collapsed::after {
        //             content: "";
        //             position: absolute;
        //             bottom: 0;
        //             left: 0;
        //             right: 0;
        //             height: 1.6em;
        //             background: linear-gradient(transparent, var(--e-global-color-background, #ffffff));
        //             pointer-events: none;
        //         }',
        //         esc_attr($element_id)
        //     );
        // }

        $css .= '</style>';

        echo $css;
    }

    /**
     * Add data attributes to elements for JavaScript
     */
    public static function addDataAttributes(Element_Base $element): void
    {
        $settings = $element->get_settings_for_display();

        // Only add attributes if the feature is enabled
        if (empty($settings['kng_collapse_expand_text_enable']) || 'yes' !== $settings['kng_collapse_expand_text_enable']) {
            return;
        }

        $collapsed_height = intval($settings['kng_collapse_expand_text_height']['size'] ?? 80);
        $show_more_text = esc_attr($settings['kng_collapse_expand_text_show_more_text'] ?? 'Read more');
        $show_less_text = esc_attr($settings['kng_collapse_expand_text_show_less_text'] ?? 'Read less');
        $animation_duration = intval($settings['kng_collapse_expand_text_animation_duration']['size'] ?? 300);
        $button_position = esc_attr($settings['kng_collapse_expand_text_button_position'] ?? 'right');
        $fade_effect = $settings['kng_collapse_expand_text_fade_effect'] ?? 'yes';

        // Add data attributes to the element
        $element->add_render_attribute('_wrapper', [
            'data-kng-collapse-height' => $collapsed_height,
            'data-kng-collapse-show-more' => $show_more_text,
            'data-kng-collapse-show-less' => $show_less_text,
            'data-kng-collapse-duration' => $animation_duration,
            'data-kng-collapse-position' => $button_position,
            'data-kng-collapse-fade' => $fade_effect,
        ]);
    }
}
