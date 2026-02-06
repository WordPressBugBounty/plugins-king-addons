<?php
/**
 * Theme Builder Back to Home widget (Free).
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
 * Renders a CTA button linking to the homepage.
 */
class TB_Back_To_Home extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-tb-back-to-home';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('TB - Back to Home', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'eicon-button';
    }

    /**
     * Style dependencies.
     *
     * @return array<int, string>
     */
    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-tb-back-to-home-style'];
    }

    /**
     * Script dependencies.
     *
     * @return array<int, string>
     */
    public function get_script_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-tb-back-to-home-script'];
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
        return ['404', 'button', 'home', 'cta', 'king-addons'];
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
     * Render output.
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

        $this->add_control(
            'kng_label',
            [
                'label' => esc_html__('Label', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Back to Home', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_destination',
            [
                'label' => esc_html__('Destination', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'home',
                'options' => [
                    'home' => esc_html__('Home URL', 'king-addons'),
                    'custom' => $is_pro ?
                        esc_html__('Custom URL', 'king-addons') :
                        sprintf(__('Custom URL %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                ],
            ]
        );

        $this->add_control(
            'kng_custom_url',
            [
                'label' => esc_html__('Custom URL', 'king-addons'),
                'type' => Controls_Manager::URL,
                'placeholder' => 'https://example.com',
                'condition' => [
                    'kng_destination' => 'custom',
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

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_typography',
                'selector' => '{{WRAPPER}} .king-addons-tb-back-to-home__button',
            ]
        );

        $this->add_control(
            'kng_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-back-to-home__button' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_bg',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-back-to-home__button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_border',
                'selector' => '{{WRAPPER}} .king-addons-tb-back-to-home__button',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_shadow',
                'selector' => '{{WRAPPER}} .king-addons-tb-back-to-home__button',
            ]
        );

        $this->add_responsive_control(
            'kng_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-back-to-home__button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Pro upsell.
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
            'tb-back-to-home',
            [
                'Custom URL destination',
                'Icons and advanced hover styles',
            ]
        );
    }

    /**
     * Render output helper.
     *
     * @param array<string, mixed> $settings Settings.
     * @param bool                 $is_pro   Pro flag.
     *
     * @return void
     */
    protected function render_output(array $settings, bool $is_pro): void
    {
        $label = $settings['kng_label'] ?? esc_html__('Back to Home', 'king-addons');
        $url = home_url('/');

        if ('custom' === ($settings['kng_destination'] ?? 'home') && $is_pro && !empty($settings['kng_custom_url']['url'])) {
            $url = $settings['kng_custom_url']['url'];
        }

        echo '<div class="king-addons-tb-back-to-home">';
        echo '<a class="king-addons-tb-back-to-home__button" href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
        echo '</div>';
    }
}
