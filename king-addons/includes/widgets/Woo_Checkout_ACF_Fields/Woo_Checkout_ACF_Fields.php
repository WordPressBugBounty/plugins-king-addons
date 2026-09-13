<?php
/**
 * Woo Checkout ACF Extra Fields widget.
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use King_Addons\Core;
use King_Addons\Woo_Builder\ACF_Fields;
use King_Addons\Woo_Builder\Context as Woo_Context;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Placeholder ACF fields for checkout (Pro-only rendering).
 */
class Woo_Checkout_ACF_Fields extends Widget_Base
{
    /**
     * Register Pro-only controls (placeholder).
     *
     * Pro overrides this method to add premium controls without using parent::register_controls().
     *
     * @return void
     */
    public function register_pro_controls(): void
    {
        // Intentionally empty in Free.
    }

    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'woo_checkout_acf_fields';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Checkout ACF Extra Fields', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-woo-checkout-acf-fields';
    }

    /**
     * Styles.
     *
     * @return array<int,string>
     */
    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-acf-fields-style'];
    }

    /**
     * Scripts: moves the fields into the checkout form when it rendered first.
     *
     * @return array<int,string>
     */
    public function get_script_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-checkout-acf-fields-script'];
    }

    /**
     * Widget categories.
     *
     * @return array<int,string>
     */
    public function get_categories(): array
    {
        return ['king-addons-woo-builder'];
    }

    /**
     * Register controls.
     *
     * @return void
     */
    public function get_custom_help_url()
    {
        return 'mailto:bug@kingaddons.com?subject=Bug Report - King Addons&body=Please describe the issue';
    }

    protected function register_controls(): void
    {
        $this->start_controls_section(
            'section_content',
            [
                'label' => esc_html__('Content', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'heading',
            [
                'label' => esc_html__('Heading', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'dynamic' => ['active' => true],
                'default' => esc_html__('Extra Information', 'king-addons'),
            ]
        );

        $this->add_control(
            'field_keys',
            [
                'label' => esc_html__('ACF Field Keys (Pro)', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => 'field_123abc, my_field',
            ]
        );

        $this->add_control(
            'placement',
            [
                'label' => esc_html__('Placement', 'king-addons'),
                'description' => esc_html__('Where the fields appear inside the checkout form. They have to be inside it to be saved with the order.', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'before_billing' => esc_html__('Before billing', 'king-addons'),
                    'after_billing' => esc_html__('After billing', 'king-addons'),
                    'before_shipping' => esc_html__('Before shipping', 'king-addons'),
                    'after_shipping' => esc_html__('After shipping', 'king-addons'),
                    'before_order' => esc_html__('Before order notes', 'king-addons'),
                    'after_order' => esc_html__('After order notes', 'king-addons'),
                ],
                'default' => 'after_order',
            ]
        );

        $this->add_control(
            'required_notice',
            [
                'label' => sprintf(__('Show required mark %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                // ACF always printed the mark; the switch now controls it.
                'default' => 'yes',
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style',
            [
                'label' => esc_html__('Style', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        // The fields are printed inside the checkout form, outside this widget's
        // wrapper, so {{WRAPPER}} cannot reach them there. The block carries a
        // class with the widget id instead.
        $this->add_control(
            'gap',
            [
                'label' => esc_html__('Fields gap', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => ['min' => 0, 'max' => 40],
                ],
                'selectors' => [
                    '.ka-acf-block.ka-acf-block-{{ID}}' => '--ka-acf-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'label_color',
            [
                'label' => esc_html__('Label color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '.ka-acf-block.ka-acf-block-{{ID}} .acf-field .acf-label label, .ka-acf-block.ka-acf-block-{{ID}} .acf-field .acf-input label' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'required_color',
            [
                'label' => esc_html__('Required mark color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '.ka-acf-block.ka-acf-block-{{ID}} .acf-required' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Allow Pro to add additional controls without using parent::register_controls().
        $this->register_pro_controls();
    }

    /**
     * Render widget output (free notice).
     *
     * @return void
     */
    protected function render(): void
    {
        if (!Woo_Context::maybe_render_context_notice('checkout')) {
            return;
        }

        $in_builder = class_exists('King_Addons\\Woo_Builder\\Context') && Woo_Context::is_editing_template_type('checkout');
        if (!function_exists('is_checkout') || (!is_checkout() && !$in_builder) || (function_exists('is_order_received_page') && is_order_received_page())) {
            return;
        }

        if (!king_addons_can_use_pro()) {
            if (Woo_Context::is_editor()) {
                echo '<div class="king-addons-woo-builder-notice">';
                echo esc_html__('ACF extra fields for checkout are available in Pro.', 'king-addons');
                echo '</div>';
            }
            return;
        }

        if (!class_exists('King_Addons\\Woo_Builder\\ACF_Fields')) {
            require_once KING_ADDONS_PATH . 'includes/helpers/Woo_Builder/ACF_Fields.php';
        }

        // Printing, validation and saving to the order live in the helper: the
        // order is created by a separate request in which no widget renders.
        ACF_Fields::render_checkout($this->get_settings_for_display(), (string) $this->get_id());
    }
}






