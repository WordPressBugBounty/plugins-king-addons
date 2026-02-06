<?php
/**
 * Theme Builder 404 Search Form widget (Free).
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders a styled search form for 404 pages.
 */
class TB_404_Search_Form extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-tb-404-search-form';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('TB - 404 Search Form', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'eicon-search';
    }

    /**
     * Style dependencies.
     *
     * @return array<int, string>
     */
    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-tb-404-search-form-style'];
    }

    /**
     * Script dependencies.
     *
     * @return array<int, string>
     */
    public function get_script_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-tb-404-search-form-script'];
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
        return ['404', 'search', 'form', 'king-addons'];
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
            'kng_placeholder',
            [
                'label' => esc_html__('Placeholder', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Search…', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_button_label',
            [
                'label' => esc_html__('Button Label', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Search', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_show_icon',
            [
                'label' => $is_pro ?
                    esc_html__('Show Icon', 'king-addons') :
                    sprintf(__('Show Icon %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
                'classes' => $is_pro ? '' : 'king-addons-pro-control',
            ]
        );

        $this->add_control(
            'kng_post_type',
            [
                'label' => $is_pro ?
                    esc_html__('Search Post Type', 'king-addons') :
                    sprintf(__('Search Post Type %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => esc_html__('post, page', 'king-addons'),
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
                'name' => 'kng_input_typography',
                'label' => esc_html__('Input Typography', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-tb-404-search-form input[type=\"search\"]',
            ]
        );

        $this->add_control(
            'kng_input_color',
            [
                'label' => esc_html__('Input Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-404-search-form input[type=\"search\"]' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_button_typography',
                'label' => esc_html__('Button Typography', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-tb-404-search-form button',
            ]
        );

        $this->add_control(
            'kng_button_color',
            [
                'label' => esc_html__('Button Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-404-search-form button' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_bg',
            [
                'label' => esc_html__('Button Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-404-search-form button' => 'background-color: {{VALUE}};',
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
            'tb-404-search-form',
            [
                'Search icon and button presets',
                'Post type filtering for search',
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
        $placeholder = $settings['kng_placeholder'] ?? '';
        $button = $settings['kng_button_label'] ?? esc_html__('Search', 'king-addons');
        $show_icon = $is_pro && ('yes' === ($settings['kng_show_icon'] ?? ''));
        $post_type = $is_pro ? ($settings['kng_post_type'] ?? '') : '';

        $action = esc_url(home_url('/'));

        echo '<form role="search" method="get" class="king-addons-tb-404-search-form" action="' . $action . '">';
        echo '<label class="king-addons-tb-404-search-form__label">';
        echo '<span class="screen-reader-text">' . esc_html__('Search', 'king-addons') . '</span>';
        echo '<input type="search" name="s" value="" placeholder="' . esc_attr($placeholder) . '" />';
        echo '</label>';
        if ($post_type) {
            echo '<input type="hidden" name="post_type" value="' . esc_attr($post_type) . '"/>';
        }
        echo '<button type="submit" class="king-addons-tb-404-search-form__button">';
        if ($show_icon) {
            echo '<span class="king-addons-tb-404-search-form__icon" aria-hidden="true">&#128269;</span>';
        }
        echo esc_html($button);
        echo '</button>';
        echo '</form>';
    }
}
