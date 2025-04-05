<?php /** @noinspection PhpUnused, SpellCheckingInspection */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Element_Base;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class Rotating_Animation
{
    private static ?Rotating_Animation $_instance = null;

    public static function instance(): Rotating_Animation
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
        if (!wp_script_is(KING_ADDONS_ASSETS_UNIQUE_KEY . '- ' . 'rotating-animation' . '-' . 'preview-handler')) {
            wp_enqueue_script(KING_ADDONS_ASSETS_UNIQUE_KEY . '-' . 'rotating-animation' . '-' . 'preview-handler', '', array('jquery'), KING_ADDONS_VERSION);
        }
    }

    public static function addControls(Element_Base $element): void
    {
        $element->start_controls_section(
            'kng_rotating_animation_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Rotating Animation', 'king-addons'),
                'tab' => Controls_Manager::TAB_ADVANCED
            ]
        );

        $element->add_control(
            'kng_rotating_animation_switch',
            [
                'label' => esc_html__('Enable Rotating Animation', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'prefix_class' => 'kng-rotating-animation-',
                'frontend_available' => true
            ]
        );

        $element->add_control(
            'kng_rotating_animation_duration',
            [
                'label' => esc_html__('Duration of single spin', 'king-addons') . ' (ms)',
                'type' => Controls_Manager::NUMBER,
                'frontend_available' => true,
                'min' => 0,
                'step' => 1,
                'default' => 8000,
                'condition' => [
                    'kng_rotating_animation_switch!' => ''
                ]
            ]
        );

        $element->add_control(
            'kng_rotating_animation_delay',
            [
                'label' => esc_html__('Animation delay', 'king-addons') . ' (ms)',
                'type' => Controls_Manager::NUMBER,
                'frontend_available' => true,
                'min' => 0,
                'step' => 1,
                'default' => 0,
                'condition' => [
                    'kng_rotating_animation_switch!' => ''
                ]
            ]
        );

        $element->end_controls_section();
    }

    /**
     * Renders the inline CSS for the rotating animation if enabled.
     * Hooked into 'elementor/frontend/before_render'.
     * Prints a <style> tag directly into the HTML output.
     *
     * @param Element_Base $element The current Elementor element object.
     */
    public static function renderAnimation(Element_Base $element): void
    {
        $settings = $element->get_settings_for_display();
        $element_id = $element->get_id();

        // 1. CHECK THE SWITCHER CONTROL (use 'yes')
        if (empty($settings['kng_rotating_animation_switch']) || 'yes' !== $settings['kng_rotating_animation_switch']) {
            return; // Animation is disabled
        }

        // 2. GET ANIMATION VALUES
        $duration = $settings['kng_rotating_animation_duration'] ?? null;
        $delay = $settings['kng_rotating_animation_delay'] ?? 0;

        // 3. VALIDATE DURATION
        if (empty($duration) || intval($duration) <= 0) {
            return; // Needs a positive duration
        }

        // 4. SANITIZE NUMERIC VALUES (use absint for non-negative ms)
        $duration_sanitized = absint($duration);
        $delay_sanitized = absint($delay);

        // 5. GENERATE THE CSS STRING using sprintf
        $animation_name = 'rotating-animation-' . $element_id;
        $selector = '.elementor-element-' . $element_id;

        $css = sprintf(
        // Add a unique ID to the style tag
            '<style id="rotating-anim-%1$s">
                @keyframes %2$s {
                    0%% { transform: rotate(0deg); }
                    100%% { transform: rotate(360deg); }
                }
                %3$s {
                    animation-name: %2$s;
                    animation-duration: %4$dms;
                    animation-timing-function: linear; /* Linear is suitable for rotation */
                    animation-iteration-count: infinite;
                    animation-delay: %5$dms;
                }
            </style>',
            esc_attr($element_id),  // %1$s: Sanitized ID for the <style> tag attribute
            $animation_name,        // %2$s: Animation name
            $selector,              // %3$s: Element selector
            $duration_sanitized,    // %4$s: Sanitized duration (non-negative integer)
            $delay_sanitized        // %5$s: Sanitized delay (non-negative integer)
        );

        // 6. OUTPUT THE GENERATED CSS directly into HTML
        print $css;
    }
}