<?php /** @noinspection SpellCheckingInspection, PhpUnused */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Element_Base;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class Floating_Animation
{
    private static ?Floating_Animation $_instance = null;

    public static function instance(): Floating_Animation
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /** @noinspection DuplicatedCode */
    public function __construct()
    {
        add_action('elementor/element/container/section_layout/after_section_end', [__CLASS__, 'addControls'], 1);
        add_action('elementor/element/column/section_advanced/after_section_end', [__CLASS__, 'addControls'], 1);
        add_action('elementor/element/section/section_advanced/after_section_end', [__CLASS__, 'addControls'], 1);
        add_action('elementor/element/common/_section_style/after_section_end', [__CLASS__, 'addControls'], 1);
        add_action('elementor/preview/enqueue_scripts', [__CLASS__, 'enqueueScripts'], 1);
        add_action('elementor/frontend/before_render', [__CLASS__, 'renderAnimation'], 1);
    }

    public static function enqueueScripts(): void
    {
        if (!wp_script_is(KING_ADDONS_ASSETS_UNIQUE_KEY . '-' . 'floating-animation' . '-' . 'preview-handler')) {
            wp_enqueue_script(KING_ADDONS_ASSETS_UNIQUE_KEY . '-' . 'floating-animation' . '-' . 'preview-handler', '', array('jquery'), KING_ADDONS_VERSION);
        }
    }

    public static function addControls(Element_Base $element): void
    {
        $element->start_controls_section(
            'kng_floating_animation_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Floating Animation', 'king-addons'),
                'tab' => Controls_Manager::TAB_ADVANCED
            ]
        );

        $element->add_control(
            'kng_floating_animation_switch',
            [
                'label' => esc_html__('Enable Floating Animation', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'prefix_class' => 'kng-floating-animation-',
                'frontend_available' => true
            ]
        );

        $element->add_control(
            'kng_floating_animation_value_X',
            [
                'label' => esc_html__('Offset X', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'frontend_available' => true,
                'step' => 1,
                'default' => 0,
                'condition' => [
                    'kng_floating_animation_switch!' => ''
                ]
            ]
        );

        $element->add_control(
            'kng_floating_animation_value',
            [
                'label' => esc_html__('Offset Y', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'frontend_available' => true,
                'step' => 1,
                'default' => -20,
                'condition' => [
                    'kng_floating_animation_switch!' => ''
                ]
            ]
        );

        $element->add_control(
            'kng_floating_animation_duration',
            [
                'label' => esc_html__('Duration', 'king-addons') . ' (ms)',
                'type' => Controls_Manager::NUMBER,
                'frontend_available' => true,
                'min' => 0,
                'step' => 1,
                'default' => 6000,
                'condition' => [
                    'kng_floating_animation_switch!' => ''
                ]
            ]
        );

        $element->add_control(
            'kng_floating_animation_delay',
            [
                'label' => esc_html__('Animation delay', 'king-addons') . ' (ms)',
                'type' => Controls_Manager::NUMBER,
                'frontend_available' => true,
                'min' => 0,
                'step' => 1,
                'default' => 0,
                'condition' => [
                    'kng_floating_animation_switch!' => ''
                ]
            ]
        );

        $element->end_controls_section();
    }

    /**
     * Renders the inline CSS for the floating animation if enabled.
     * This function is hooked into 'elementor/frontend/before_render'.
     * It prints a <style> tag directly into the HTML output.
     *
     * @param Element_Base $element The current Elementor element object.
     */
    public static function renderAnimation(Element_Base $element): void
    {
        $settings = $element->get_settings_for_display();
        $element_id = $element->get_id(); // Get element ID once for efficiency

        // 1. CHECK THE SWITCHER CONTROL
        // Elementor's switcher control typically uses 'yes' for the enabled state.
        if (empty($settings['kng_floating_animation_switch']) || 'yes' !== $settings['kng_floating_animation_switch']) {
            return; // Animation is disabled for this element, do nothing.
        }

        // 2. GET ANIMATION VALUES FROM SETTINGS
        // Use null coalescing operator (??) to provide default values if settings are not set.
        $value_x = $settings['kng_floating_animation_value_X'] ?? 0;
        $value_y = $settings['kng_floating_animation_value'] ?? 0; // Using your specific setting key name for Y
        $duration = $settings['kng_floating_animation_duration'] ?? null; // Get duration, check validity later
        $delay = $settings['kng_floating_animation_delay'] ?? 0;

        // 3. VALIDATE IF ANIMATION IS MEANINGFUL
        // Animation requires a duration greater than 0ms to be visible.
        if (empty($duration) || intval($duration) <= 0) {
            // If duration is not set or is zero/negative, don't output the style tag.
            return;
        }

        // 4. SANITIZE NUMERIC VALUES
        // Use intval for values that can be negative (translate offsets).
        // Use absint for values that must be non-negative (duration, delay).
        // This prevents invalid CSS and potential security issues if settings were compromised.
        $value_x_sanitized = intval($value_x);
        $value_y_sanitized = intval($value_y);
        $duration_sanitized = absint($duration);
        $delay_sanitized = absint($delay);

        // 5. GENERATE THE CSS STRING
        // Use the element ID directly in animation names and selectors (Elementor IDs are safe).
        // Use sprintf for cleaner code when building strings with variables.
        $animation_name = 'floating-animation-' . $element_id;
        $selector = '.elementor-element-' . $element_id;

        $css = sprintf(
        // Use a unique ID for the style tag itself, based on the element ID.
        // Use esc_attr() for sanitizing the ID attribute value.
            '<style id="floating-anim-%1$s">
                @keyframes %2$s {
                    0%% { transform: translate(0, 0); }
                    50%% { transform: translate(%3$dpx, %4$dpx); }
                    100%% { transform: translate(0, 0); }
                }
                %5$s {
                    animation-name: %2$s;
                    animation-duration: %6$dms;
                    animation-timing-function: ease-in-out;
                    animation-iteration-count: infinite;
                    animation-delay: %7$dms;
                }
            </style>',
            esc_attr($element_id),  // %1$s: Sanitized ID for the <style> tag attribute
            $animation_name,        // %2$s: Animation name (uses safe element ID)
            $value_x_sanitized,     // %3$s: Sanitized X offset (integer)
            $value_y_sanitized,     // %4$s: Sanitized Y offset (integer)
            $selector,              // %5$s: Element selector (uses safe element ID)
            $duration_sanitized,    // %6$s: Sanitized duration (non-negative integer)
            $delay_sanitized        // %7$s: Sanitized delay (non-negative integer)
        );

        // 6. OUTPUT THE GENERATED CSS
        // Since this function is called within the 'elementor/frontend/before_render' hook,
        // using print or echo will directly insert the <style> tag into the page's HTML
        // just before the element's own HTML is rendered.
        print $css;
    }
}