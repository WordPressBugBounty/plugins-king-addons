<?php
/**
 * Phone Call Button Widget (Free).
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders a phone call button.
 */
class Phone_Call_Button extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-phone-call-button';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Phone Call Button', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-phone-call-button';
    }

    /**
     * Style dependencies.
     *
     * @return array<int, string>
     */
    public function get_style_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-phone-call-button-style',
        ];
    }

    /**
     * Script dependencies.
     *
     * @return array<int, string>
     */
    public function get_script_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-phone-call-button-script',
        ];
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
        return ['phone', 'call', 'button', 'contact'];
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
     * Render widget.
     *
     * @return void
     */
    public function render(): void
    {
        $settings = $this->get_settings_for_display();
        $phone = trim((string) ($settings['kng_phone'] ?? ''));
        if ($phone === '') {
            return;
        }

        $button_text = $settings['kng_button_text'] ?? '';
        $alignment = $settings['kng_align'] ?? 'left';
        $icon_position = (string) ($settings['kng_icon_position'] ?? 'left');
        $icon = $settings['kng_icon'] ?? [
            'value' => 'fas fa-phone',
            'library' => 'fa-solid',
        ];

        $effect = (string) ($settings['kng_attention_animation'] ?? $settings['kng_effect'] ?? 'none');
        $can_use_pro = (bool) king_addons_freemius()->can_use_premium_code__premium_only();
        $pro_effects = ['bounce', 'ring'];
        if (in_array($effect, $pro_effects, true) && !$can_use_pro) {
            $effect = 'none';
        }

        $wrapper_classes = ['king-addons-phone-call'];
        $wrapper_classes[] = 'align-' . $alignment;
        $button_classes = ['king-addons-phone-call__button'];
        if ($icon_position === 'right') {
            $button_classes[] = 'is-icon-right';
        }
        if ($effect !== 'none') {
            $button_classes[] = 'is-anim-' . $effect;
        }

        ?>
        <div class="<?php echo esc_attr(implode(' ', $wrapper_classes)); ?>">
            <a class="<?php echo esc_attr(implode(' ', $button_classes)); ?>" href="tel:<?php echo esc_attr($phone); ?>" data-device="all" data-animation="<?php echo esc_attr($effect); ?>" aria-label="<?php echo esc_attr($button_text ?: $phone); ?>">
                <?php if (!empty($icon['value'])) : ?>
                    <span class="king-addons-phone-call__icon" aria-hidden="true">
                        <?php Icons_Manager::render_icon($icon, ['aria-hidden' => 'true']); ?>
                    </span>
                <?php endif; ?>
                <?php if (!empty($button_text)) : ?>
                    <span class="king-addons-phone-call__text"><?php echo esc_html($button_text); ?></span>
                <?php else : ?>
                    <span class="king-addons-phone-call__text"><?php echo esc_html($phone); ?></span>
                <?php endif; ?>
            </a>
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
            'kng_phone',
            [
                'label' => esc_html__('Phone Number', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => '12345',
                'placeholder' => esc_html__('+1 555 000 1234', 'king-addons'),
                'description' => esc_html__('Will be used in tel: link. Keep digits and plus.', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_button_text',
            [
                'label' => esc_html__('Button Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Call now', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_icon',
            [
                'label' => esc_html__('Icon', 'king-addons'),
                'type' => Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fas fa-phone',
                    'library' => 'fa-solid',
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
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        // Clean up any legacy/duplicate controls added elsewhere (e.g., Pro attention animation).
        $this->remove_control('kng_effect');
        $this->remove_control('kng_attention_animation');

        $this->add_responsive_control(
            'kng_align',
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
                'default' => 'left',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-phone-call' => 'justify-content: {{VALUE}};',
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
                    'right' => esc_html__('Right', 'king-addons'),
                ],
            ]
        );

        $this->add_control(
            'kng_attention_animation',
            [
                'label' => esc_html__('Attention Animation', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'pulse',
                'options' => [
                    'none' => esc_html__('None', 'king-addons'),
                    'pulse' => esc_html__('Pulse (Free)', 'king-addons'),
                    'waves' => esc_html__('Waves (Free)', 'king-addons'),
                    'bounce' => esc_html__('Bounce (Pro)', 'king-addons'),
                    'ring' => esc_html__('Ring (Pro)', 'king-addons'),
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
            'kng_style_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Button', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_text_typography',
                'selector' => '{{WRAPPER}} .king-addons-phone-call__text',
            ]
        );

        $this->start_controls_tabs('kng_button_tabs');

        $this->start_controls_tab(
            'kng_button_tab_normal',
            [
                'label' => esc_html__('Normal', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-phone-call__button' => 'color: {{VALUE}};',
                ],
            ]
        );

            $this->add_control(
                'kng_hover_scale',
                [
                    'label' => esc_html__('Hover Scale', 'king-addons'),
                    'type' => Controls_Manager::SLIDER,
                    'size_units' => ['custom'],
                    'range' => [
                        'custom' => ['min' => 1, 'max' => 1.5, 'step' => 0.01],
                    ],
                    'default' => [
                        'size' => 1.05,
                    ],
                    'selectors' => [
                        '{{WRAPPER}} .king-addons-phone-call__button' => '--kng-phone-hover-scale: {{SIZE}};',
                    ],
                ]
            );

        $this->add_control(
            'kng_bg_color',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-phone-call__button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_border',
                'selector' => '{{WRAPPER}} .king-addons-phone-call__button',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_shadow',
                'selector' => '{{WRAPPER}} .king-addons-phone-call__button',
                'condition' => [
                    'kng_disable_shadow!' => 'yes',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'kng_button_tab_hover',
            [
                'label' => esc_html__('Hover', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_text_color_hover',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-phone-call__button:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_bg_color_hover',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-phone-call__button:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_border_color_hover',
            [
                'label' => esc_html__('Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-phone-call__button:hover' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_border_hover',
                'selector' => '{{WRAPPER}} .king-addons-phone-call__button:hover',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_shadow_hover',
                'selector' => '{{WRAPPER}} .king-addons-phone-call__button:hover',
                'condition' => [
                    'kng_disable_shadow!' => 'yes',
                ],
            ]
        );

        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->add_control(
            'kng_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 40],
                    '%' => ['min' => 0, 'max' => 100],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-phone-call__button' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_disable_shadow',
            [
                'label' => esc_html__('Disable Box Shadow', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-phone-call__button' => 'box-shadow: none;',
                    '{{WRAPPER}} .king-addons-phone-call__button:hover' => 'box-shadow: none;',
                ],
            ]
        );

        $this->add_control(
            'kng_effect_heading',
            [
                'label' => esc_html__('Effect Styling', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'kng_effect_color',
            [
                'label' => esc_html__('Effect Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#5B03FF',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-phone-call__button' => '--kng-phone-effect-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_effect_color_hover',
            [
                'label' => esc_html__('Hover Effect Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#7A2CFF',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-phone-call__button:hover' => '--kng-phone-effect-color-hover: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_effect_thickness',
            [
                'label' => esc_html__('Effect Thickness', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 8],
                ],
                'default' => [
                    'size' => 2,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-phone-call__button' => '--kng-phone-effect-thickness: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_effect_spread',
            [
                'label' => esc_html__('Effect Spread', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 30],
                ],
                'default' => [
                    'size' => 10,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-phone-call__button' => '--kng-phone-effect-inset: calc(-1 * {{SIZE}}{{UNIT}});',
                ],
            ]
        );

        $this->add_control(
            'kng_effect_duration',
            [
                'label' => esc_html__('Effect Duration (s)', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['s'],
                'range' => [
                    's' => ['min' => 0.5, 'max' => 3, 'step' => 0.1],
                ],
                'default' => [
                    'size' => 1.6,
                    'unit' => 's',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-phone-call__button' => '--kng-phone-effect-duration: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_effect_opacity',
            [
                'label' => esc_html__('Effect Opacity', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['custom'],
                'range' => [
                    'custom' => ['min' => 0, 'max' => 1, 'step' => 0.05],
                ],
                'default' => [
                    'size' => 0.35,
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-phone-call__button' => '--kng-phone-effect-opacity: {{SIZE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_shadow_footer_placeholder', // kept to avoid breaking existing references; hidden by tabs usage.
                'selector' => '{{WRAPPER}} .king-addons-phone-call__button',
                'condition' => ['_never_render_' => 'true'],
            ]
        );

        $this->add_responsive_control(
            'kng_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-phone-call__button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render pro notice.
     *
     * @return void
     */
    public function register_pro_notice_controls(): void
    {
        if (!king_addons_freemius()->can_use_premium_code__premium_only()) {
            Core::renderProFeaturesSection($this, '', Controls_Manager::RAW_HTML, 'phone-call-button', [
                'Floating button (left/right, mobile-only)',
                'More attention animations: Bounce, Ring',
                'Device targeting and schedule',
                'Custom SVG icon and tooltip',
            ]);
        }
    }
}







