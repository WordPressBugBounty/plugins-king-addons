<?php
/**
 * Cookie Preferences Button widget (Free).
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders a manage-consent button that opens the cookie settings modal.
 */
class Cookie_Preferences_Button extends Widget_Base
{
    /**
     * Get widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-cookie-preferences-button';
    }

    /**
     * Get widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Cookie Preferences Button', 'king-addons');
    }

    /**
     * Get widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-button';
    }

    /**
     * Get widget categories.
     *
     * @return array<int, string>
     */
    public function get_categories(): array
    {
        return ['king-addons'];
    }

    /**
     * Get widget keywords.
     *
     * @return array<int, string>
     */
    public function get_keywords(): array
    {
        return ['cookie', 'consent', 'gdpr', 'ccpa', 'privacy'];
    }

    /**
     * Enqueue style dependencies.
     *
     * @return array<int, string>
     */
    public function get_style_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-cookie-preferences-button-style',
        ];
    }

    /**
     * Enqueue script dependencies.
     *
     * @return array<int, string>
     */
    public function get_script_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-cookie-preferences-button-script',
        ];
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
    public function register_controls(): void
    {
        $this->start_controls_section(
            'kng_content_section',
            [
                'label' => esc_html__('Content', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_label',
            [
                'label' => esc_html__('Button Label', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Cookie settings', 'king-addons'),
            ]
        );

        $this->add_responsive_control(
            'kng_align',
            [
                'label' => esc_html__('Alignment', 'king-addons'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'flex-start' => [
                        'title' => esc_html__('Left', 'king-addons'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'king-addons'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'flex-end' => [
                        'title' => esc_html__('Right', 'king-addons'),
                        'icon' => 'eicon-text-align-right',
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-cookie-button' => 'justify-content: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'kng_style_section',
            [
                'label' => esc_html__('Button', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_typography',
                'selector' => '{{WRAPPER}} .king-addons-cookie-button__trigger',
            ]
        );

        $this->add_control(
            'kng_text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-cookie-button__trigger' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_background',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-cookie-button__trigger' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_border_color',
            [
                'label' => esc_html__('Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-cookie-button__trigger' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_border',
                'selector' => '{{WRAPPER}} .king-addons-cookie-button__trigger',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_box_shadow',
                'selector' => '{{WRAPPER}} .king-addons-cookie-button__trigger',
            ]
        );

        $this->add_control(
            'kng_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-cookie-button__trigger' => 'border-radius: {{SIZE}}{{UNIT}};',
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
                    '{{WRAPPER}} .king-addons-cookie-button__trigger' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render widget output.
     *
     * @return void
     */
    public function render(): void
    {
        $settings = $this->get_settings_for_display();
        $label = !empty($settings['kng_label']) ? $settings['kng_label'] : esc_html__('Cookie settings', 'king-addons');
        ?>
        <div class="king-addons-cookie-button">
            <button type="button" class="king-addons-cookie-button__trigger" data-ka-cookie-manage="true">
                <?php echo esc_html($label); ?>
            </button>
        </div>
        <?php
    }
}



