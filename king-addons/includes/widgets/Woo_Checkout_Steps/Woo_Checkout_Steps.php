<?php
/**
 * Woo Checkout Steps widget (multi-step).
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use King_Addons\Woo_Builder\Context as Woo_Context;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Placeholder multi-step checkout (Free shows notice, Pro overrides).
 */
class Woo_Checkout_Steps extends Widget_Base
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
        return 'woo_checkout_steps';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Checkout Steps (Multi-step)', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'eicon-editor-list-ol';
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
     * Styles.
     *
     * @return array<int, string>
     */
    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-checkout-steps-style'];
    }

    /**
     * Scripts.
     *
     * @return array<int, string>
     */
    public function get_script_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-checkout-steps-script'];
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
            'steps',
            [
                'label' => esc_html__('Steps', 'king-addons'),
                'type' => Controls_Manager::REPEATER,
                'fields' => [
                    [
                        'name' => 'label',
                        'label' => esc_html__('Label', 'king-addons'),
                        'type' => Controls_Manager::TEXT,
                        'default' => esc_html__('Step', 'king-addons'),
                    ],
                    [
                        'name' => 'target',
                        'label' => sprintf(__('Target selector (Pro) %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                        'type' => Controls_Manager::TEXT,
                        'placeholder' => '#customer_details',
                    ],
                ],
                'default' => [
                    ['label' => esc_html__('Billing', 'king-addons')],
                    ['label' => esc_html__('Shipping', 'king-addons')],
                    ['label' => esc_html__('Payment', 'king-addons')],
                    ['label' => esc_html__('Review', 'king-addons')],
                ],
            ]
        );

        $this->add_control(
            'scroll_to_section',
            [
                'label' => sprintf(__('Scroll to section (Pro) %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'show_nav',
            [
                'label' => sprintf(__('Show Next/Previous (Pro) %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'auto_scroll',
            [
                'label' => esc_html__('Scroll on step change (Pro)', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'prev_text',
            [
                'label' => esc_html__('Previous text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Previous', 'king-addons'),
                'condition' => [
                    'show_nav' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'next_text',
            [
                'label' => esc_html__('Next text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Next', 'king-addons'),
                'condition' => [
                    'show_nav' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        // Allow Pro to add additional controls without using parent::register_controls().
        $this->register_pro_controls();
    }

    /**
     * Render widget output (free shows upgrade notice).
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
                echo esc_html__('Multi-step checkout is available in Pro.', 'king-addons');
                echo '</div>';
            }
            return;
        }

        $settings = $this->get_settings_for_display();
        $steps = $settings['steps'] ?? [];
        if (empty($steps)) {
            return;
        }

        $scroll = (!empty($settings['scroll_to_section']) && 'yes' === $settings['scroll_to_section']) ? 'yes' : 'no';
        $auto_scroll = (!empty($settings['auto_scroll']) && 'yes' === $settings['auto_scroll']) ? 'yes' : 'no';
        $show_nav = (!empty($settings['show_nav']) && 'yes' === $settings['show_nav']);
        $prev_text = $settings['prev_text'] ?? esc_html__('Previous', 'king-addons');
        $next_text = $settings['next_text'] ?? esc_html__('Next', 'king-addons');

        echo '<div class="ka-woo-checkout-steps-wrapper" data-enable-nav="' . ($show_nav ? 'true' : 'false') . '" data-auto-scroll="' . esc_attr($auto_scroll) . '">';
        echo '<ol class="ka-woo-checkout-steps ka-woo-checkout-steps--pro" role="list" data-scroll-top="' . esc_attr($scroll) . '" data-prev-text="' . esc_attr($prev_text) . '" data-next-text="' . esc_attr($next_text) . '">';
        foreach ($steps as $index => $step) {
            $label = $step['label'] ?? '';
            $target = $step['target'] ?? '';
            echo '<li class="ka-woo-checkout-step" data-step="' . esc_attr((string) ($index + 1)) . '" data-target="' . esc_attr($target) . '">';
            echo '<span class="ka-woo-checkout-step__number">' . esc_html((string) ($index + 1)) . '</span>';
            echo '<span class="ka-woo-checkout-step__label">' . esc_html($label) . '</span>';
            echo '</li>';
        }
        echo '</ol>';
        if ($show_nav) {
            echo '<div class="ka-woo-checkout-steps__nav">';
            echo '<button type="button" class="button ka-woo-checkout-steps__prev" data-nav="prev">' . esc_html($prev_text) . '</button>';
            echo '<button type="button" class="button button-primary ka-woo-checkout-steps__next" data-nav="next">' . esc_html($next_text) . '</button>';
            echo '</div>';
        }
        echo '</div>';
    }
}






