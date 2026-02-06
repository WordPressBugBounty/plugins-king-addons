<?php
/**
 * Content Toggle Widget (Free)
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Plugin;
use Elementor\Utils;
use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders the Content Toggle widget for the free version.
 */
class Content_Toggle extends Widget_Base
{
    /**
     * Get widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-content-toggle';
    }

    /**
     * Get widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Content Toggle', 'king-addons');
    }

    /**
     * Get widget icon CSS classes.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'eicon-toggle';
    }

    /**
     * Get script dependencies.
     *
     * @return array<string>
     */
    public function get_script_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-content-toggle-script',
        ];
    }

    /**
     * Get style dependencies.
     *
     * @return array<string>
     */
    public function get_style_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-content-toggle-style',
        ];
    }

    /**
     * Get widget categories.
     *
     * @return array<string>
     */
    public function get_categories(): array
    {
        return ['king-addons'];
    }

    /**
     * Get widget keywords.
     *
     * @return array<string>
     */
    public function get_keywords(): array
    {
        return ['toggle', 'switch', 'content', 'tabs', 'king-addons'];
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
        $this->register_toggle_controls();
        $this->register_primary_content_controls();
        $this->register_secondary_content_controls();
        $this->register_external_target_controls();
        $this->register_style_toggle_controls();
        $this->register_style_content_controls();
        $this->register_pro_features_notice();
    }

    /**
     * Register Pro-only controls placeholder.
     *
     * The Pro version overrides this method to add additional controls without
     * overriding the full `register_controls()` flow.
     *
     * @return void
     */
    public function register_external_target_controls(): void
    {
        // Intentionally empty in Free.
    }

    /**
     * Register general toggle controls.
     *
     * @return void
     */
    public function register_toggle_controls(): void
    {
        $this->start_controls_section(
            'kng_toggle_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Toggle', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_toggle_label_primary',
            [
                'label' => esc_html__('Primary Label', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Primary', 'king-addons'),
                'placeholder' => esc_html__('Primary', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_toggle_label_secondary',
            [
                'label' => esc_html__('Secondary Label', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Secondary', 'king-addons'),
                'placeholder' => esc_html__('Secondary', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_default_state',
            [
                'label' => esc_html__('Default State', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'primary',
                'options' => [
                    'primary' => esc_html__('Primary', 'king-addons'),
                    'secondary' => esc_html__('Secondary', 'king-addons'),
                ],
                'description' => esc_html__('Choose which content is visible on load.', 'king-addons'),
            ]
        );

        $this->add_responsive_control(
            'kng_toggle_alignment',
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
                'default' => 'flex-start',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-content-toggle__header' => 'justify-content: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_animation_style',
            [
                'label' => esc_html__('Animation', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'fade',
                'options' => [
                    'fade' => esc_html__('Fade', 'king-addons'),
                ],
                'description' => esc_html__('Slide animation is available in the Pro version.', 'king-addons'),
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register primary content controls.
     *
     * @return void
     */
    public function register_primary_content_controls(): void
    {
        $this->start_controls_section(
            'kng_primary_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Primary Content', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_primary_source',
            [
                'label' => esc_html__('Source', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'text',
                'options' => [
                    'text' => esc_html__('Text', 'king-addons'),
                    'image' => esc_html__('Image', 'king-addons'),
                ],
            ]
        );

        $this->add_control(
            'kng_primary_content',
            [
                'label' => esc_html__('Content', 'king-addons'),
                'type' => Controls_Manager::WYSIWYG,
                'default' => esc_html__('Share your primary content here to highlight the most important information first.', 'king-addons'),
                'condition' => [
                    'kng_primary_source' => 'text',
                ],
            ]
        );

        $this->add_control(
            'kng_primary_image',
            [
                'label' => esc_html__('Image', 'king-addons'),
                'type' => Controls_Manager::MEDIA,
                'default' => [
                    'url' => Utils::get_placeholder_image_src(),
                ],
                'condition' => [
                    'kng_primary_source' => 'image',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Image_Size::get_type(),
            [
                'name' => 'kng_primary_image',
                'default' => 'large',
                'condition' => [
                    'kng_primary_source' => 'image',
                ],
            ]
        );

        $this->add_control(
            'kng_primary_template_notice',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => esc_html__('Loading Elementor templates is available in the Pro version.', 'king-addons'),
                'content_classes' => 'king-addons-pro-notice',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register secondary content controls.
     *
     * @return void
     */
    public function register_secondary_content_controls(): void
    {
        $this->start_controls_section(
            'kng_secondary_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Secondary Content', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_secondary_source',
            [
                'label' => esc_html__('Source', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'text',
                'options' => [
                    'text' => esc_html__('Text', 'king-addons'),
                    'image' => esc_html__('Image', 'king-addons'),
                ],
            ]
        );

        $this->add_control(
            'kng_secondary_content',
            [
                'label' => esc_html__('Content', 'king-addons'),
                'type' => Controls_Manager::WYSIWYG,
                'default' => esc_html__('Use the secondary area for comparison, alternate offers, or supporting details.', 'king-addons'),
                'condition' => [
                    'kng_secondary_source' => 'text',
                ],
            ]
        );

        $this->add_control(
            'kng_secondary_image',
            [
                'label' => esc_html__('Image', 'king-addons'),
                'type' => Controls_Manager::MEDIA,
                'default' => [
                    'url' => Utils::get_placeholder_image_src(),
                ],
                'condition' => [
                    'kng_secondary_source' => 'image',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Image_Size::get_type(),
            [
                'name' => 'kng_secondary_image',
                'default' => 'large',
                'condition' => [
                    'kng_secondary_source' => 'image',
                ],
            ]
        );

        $this->add_control(
            'kng_secondary_template_notice',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => esc_html__('Loading Elementor templates is available in the Pro version.', 'king-addons'),
                'content_classes' => 'king-addons-pro-notice',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register toggle style controls.
     *
     * @return void
     */
    public function register_style_toggle_controls(): void
    {
        $this->start_controls_section(
            'kng_toggle_style_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Toggle', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'kng_toggle_gap',
            [
                'label' => esc_html__('Toggle Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 64,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-content-toggle__header' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_toggle_width',
            [
                'label' => esc_html__('Switch Width', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 80,
                        'max' => 200,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-content-toggle' => '--kng-toggle-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_toggle_height',
            [
                'label' => esc_html__('Switch Height', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 28,
                        'max' => 80,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-content-toggle' => '--kng-toggle-height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_track_color',
            [
                'label' => esc_html__('Track', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-content-toggle' => '--kng-toggle-track: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_track_active_color',
            [
                'label' => esc_html__('Track Active', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-content-toggle' => '--kng-toggle-track-active: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_thumb_color',
            [
                'label' => esc_html__('Thumb', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-content-toggle' => '--kng-toggle-thumb: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_switch_border',
                'selector' => '{{WRAPPER}} .king-addons-content-toggle__switch',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_switch_shadow',
                'selector' => '{{WRAPPER}} .king-addons-content-toggle__switch',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_labels_typography',
                'selector' => '{{WRAPPER}} .king-addons-content-toggle__label',
            ]
        );

        $this->add_control(
            'kng_label_color',
            [
                'label' => esc_html__('Label', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-content-toggle__label' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_label_active_color',
            [
                'label' => esc_html__('Label Active', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-content-toggle__label.is-active' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register content style controls.
     *
     * @return void
     */
    public function register_style_content_controls(): void
    {
        $this->start_controls_section(
            'kng_content_style_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Content Area', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name' => 'kng_pane_background',
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .king-addons-content-toggle__pane',
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_pane_border',
                'selector' => '{{WRAPPER}} .king-addons-content-toggle__pane',
            ]
        );

        $this->add_responsive_control(
            'kng_pane_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-content-toggle__pane' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_pane_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-content-toggle__pane' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_pane_gap',
            [
                'label' => esc_html__('Content Gap', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 48,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-content-toggle__panes' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_pane_shadow',
                'selector' => '{{WRAPPER}} .king-addons-content-toggle__pane',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_content_typography',
                'selector' => '{{WRAPPER}} .king-addons-content-toggle__pane',
            ]
        );

        $this->add_control(
            'kng_content_color',
            [
                'label' => esc_html__('Text', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-content-toggle__pane' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render the Pro feature promo section.
     *
     * @return void
     */
    public function register_pro_features_notice(): void
    {
        Core::renderProFeaturesSection(
            $this,
            '',
            Controls_Manager::RAW_HTML,
            'content-toggle',
            [
                'Use Elementor templates for primary and secondary areas',
                'Add icons to primary and secondary labels',
                'Control external CSS selectors to show or hide per state',
                'Choose between fade and slide animations',
                'Custom active/hidden classes for synced selectors',
            ]
        );
    }

    /**
     * Render widget output on the frontend.
     *
     * @return void
     */
    public function render(): void
    {
        $settings = $this->get_settings_for_display();
        $this->render_output($settings);
    }

    /**
     * Render widget output using provided settings.
     *
     * @param array<string, mixed> $settings Widget settings.
     *
     * @return void
     */
    public function render_output(array $settings): void
    {
        $active_state = $this->get_default_state($settings);
        $primary_targets = isset($settings['kng_primary_target_selectors']) ? trim((string) $settings['kng_primary_target_selectors']) : '';
        $secondary_targets = isset($settings['kng_secondary_target_selectors']) ? trim((string) $settings['kng_secondary_target_selectors']) : '';
        $primary_hide = isset($settings['kng_primary_hide_selectors']) ? trim((string) $settings['kng_primary_hide_selectors']) : '';
        $secondary_hide = isset($settings['kng_secondary_hide_selectors']) ? trim((string) $settings['kng_secondary_hide_selectors']) : '';
        $active_class = isset($settings['kng_active_class_name']) && '' !== $settings['kng_active_class_name']
            ? sanitize_text_field((string) $settings['kng_active_class_name'])
            : 'is-visible';
        $hidden_class = isset($settings['kng_hidden_class_name']) && '' !== $settings['kng_hidden_class_name']
            ? sanitize_text_field((string) $settings['kng_hidden_class_name'])
            : 'is-hidden';

        $wrapper_classes = [
            'king-addons-content-toggle',
            $active_state === 'secondary'
                ? 'king-addons-content-toggle--secondary-active'
                : 'king-addons-content-toggle--primary-active',
        ];

        $primary_pane_id = 'king-addons-content-toggle-primary-' . $this->get_id();
        $secondary_pane_id = 'king-addons-content-toggle-secondary-' . $this->get_id();

        $this->add_render_attribute('wrapper', 'class', $wrapper_classes);
        $this->add_render_attribute('wrapper', 'data-default-state', $active_state);
        $this->add_render_attribute('wrapper', 'data-animation', $settings['kng_animation_style'] ?? 'fade');
        $this->add_render_attribute('wrapper', 'data-primary-targets', $primary_targets);
        $this->add_render_attribute('wrapper', 'data-secondary-targets', $secondary_targets);
        $this->add_render_attribute('wrapper', 'data-primary-hide', $primary_hide);
        $this->add_render_attribute('wrapper', 'data-secondary-hide', $secondary_hide);
        $this->add_render_attribute('wrapper', 'data-active-class', $active_class);
        $this->add_render_attribute('wrapper', 'data-hidden-class', $hidden_class);

        ?>
        <div <?php echo $this->get_render_attribute_string('wrapper'); ?>>
            <?php $this->render_toggle_header($settings, $active_state, $primary_pane_id, $secondary_pane_id); ?>
            <?php $this->render_panes($settings, $active_state, $primary_pane_id, $secondary_pane_id); ?>
        </div>
        <?php
    }

    /**
     * Render the toggle header.
     *
     * @param array<string, mixed> $settings Widget settings.
     * @param string               $active_state Active pane identifier.
     * @param string               $primary_pane_id Primary pane HTML id.
     * @param string               $secondary_pane_id Secondary pane HTML id.
     *
     * @return void
     */
    public function render_toggle_header(array $settings, string $active_state, string $primary_pane_id, string $secondary_pane_id): void
    {
        $primary_label = $settings['kng_toggle_label_primary'] ?? esc_html__('Primary', 'king-addons');
        $secondary_label = $settings['kng_toggle_label_secondary'] ?? esc_html__('Secondary', 'king-addons');

        ?>
        <div class="king-addons-content-toggle__header" role="group" aria-label="<?php echo esc_attr__('Content toggle', 'king-addons'); ?>">
            <button
                type="button"
                class="king-addons-content-toggle__label king-addons-content-toggle__label--primary <?php echo $active_state === 'primary' ? 'is-active' : ''; ?>"
                data-target="primary"
                aria-pressed="<?php echo $active_state === 'primary' ? 'true' : 'false'; ?>"
                aria-controls="<?php echo esc_attr($primary_pane_id); ?>"
            >
                <?php echo $this->render_label_icon($settings, 'primary'); ?>
                <span class="king-addons-content-toggle__label-text"><?php echo esc_html($primary_label); ?></span>
            </button>

            <button
                type="button"
                class="king-addons-content-toggle__switch"
                role="switch"
                aria-checked="<?php echo $active_state === 'secondary' ? 'true' : 'false'; ?>"
                aria-label="<?php echo esc_attr__('Toggle content', 'king-addons'); ?>"
                data-target="<?php echo $active_state === 'primary' ? 'secondary' : 'primary'; ?>"
            >
                <span class="king-addons-content-toggle__thumb" aria-hidden="true"></span>
            </button>

            <button
                type="button"
                class="king-addons-content-toggle__label king-addons-content-toggle__label--secondary <?php echo $active_state === 'secondary' ? 'is-active' : ''; ?>"
                data-target="secondary"
                aria-pressed="<?php echo $active_state === 'secondary' ? 'true' : 'false'; ?>"
                aria-controls="<?php echo esc_attr($secondary_pane_id); ?>"
            >
                <?php echo $this->render_label_icon($settings, 'secondary'); ?>
                <span class="king-addons-content-toggle__label-text"><?php echo esc_html($secondary_label); ?></span>
            </button>
        </div>
        <?php
    }

    /**
     * Render the content panes.
     *
     * @param array<string, mixed> $settings Widget settings.
     * @param string               $active_state Active pane identifier.
     * @param string               $primary_pane_id Primary pane HTML id.
     * @param string               $secondary_pane_id Secondary pane HTML id.
     *
     * @return void
     */
    public function render_panes(array $settings, string $active_state, string $primary_pane_id, string $secondary_pane_id): void
    {
        ?>
        <div class="king-addons-content-toggle__panes">
            <?php $this->render_single_pane($settings, 'primary', $primary_pane_id, $active_state === 'primary'); ?>
            <?php $this->render_single_pane($settings, 'secondary', $secondary_pane_id, $active_state === 'secondary'); ?>
        </div>
        <?php
    }

    /**
     * Render a single content pane.
     *
     * @param array<string, mixed> $settings Widget settings.
     * @param string               $context Pane context.
     * @param string               $pane_id Pane HTML id.
     * @param bool                 $is_active Whether pane is active.
     *
     * @return void
     */
    public function render_single_pane(array $settings, string $context, string $pane_id, bool $is_active): void
    {
        $content_html = $this->get_content_html($settings, $context);

        if ('' === $content_html) {
            return;
        }

        ?>
        <div
            id="<?php echo esc_attr($pane_id); ?>"
            class="king-addons-content-toggle__pane king-addons-content-toggle__pane--<?php echo esc_attr($context); ?> <?php echo $is_active ? 'is-active' : ''; ?>"
            role="region"
            aria-live="polite"
            <?php echo $is_active ? '' : 'hidden'; ?>
        >
            <?php echo $content_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>
        <?php
    }

    /**
     * Get default state from settings.
     *
     * @param array<string, mixed> $settings Widget settings.
     *
     * @return string
     */
    public function get_default_state(array $settings): string
    {
        $state = $settings['kng_default_state'] ?? 'primary';
        return 'secondary' === $state ? 'secondary' : 'primary';
    }

    /**
     * Get content type for a context.
     *
     * @param array<string, mixed> $settings Widget settings.
     * @param string               $context Context identifier.
     *
     * @return string
     */
    public function get_content_type(array $settings, string $context): string
    {
        $key = 'kng_' . $context . '_source';
        $value = $settings[$key] ?? 'text';

        if (!in_array($value, ['text', 'image'], true)) {
            return 'text';
        }

        return $value;
    }

    /**
     * Get content HTML for a context.
     *
     * @param array<string, mixed> $settings Widget settings.
     * @param string               $context Context identifier.
     *
     * @return string
     */
    public function get_content_html(array $settings, string $context): string
    {
        return $this->get_content_html_base($settings, $context);
    }

    /**
     * Get base content HTML for a context.
     *
     * This method exists to allow the Pro version to extend content rendering
     * without calling `parent::` methods.
     *
     * @param array<string, mixed> $settings Widget settings.
     * @param string               $context Context identifier.
     *
     * @return string
     */
    public function get_content_html_base(array $settings, string $context): string
    {
        $type = $this->get_content_type($settings, $context);

        if ('image' === $type) {
            return $this->get_image_html($settings, $context);
        }

        $key = 'kng_' . $context . '_content';
        $content = $settings[$key] ?? '';

        return wp_kses_post($content);
    }

    /**
     * Get image HTML for a context.
     *
     * @param array<string, mixed> $settings Widget settings.
     * @param string               $context Context identifier.
     *
     * @return string
     */
    public function get_image_html(array $settings, string $context): string
    {
        $image_key = 'kng_' . $context . '_image';
        $image = $settings[$image_key] ?? null;

        if (empty($image['id']) && empty($image['url'])) {
            return '';
        }

        $image_html = Group_Control_Image_Size::get_attachment_image_html(
            $settings,
            $image_key,
            $image_key
        );

        if (empty($image_html) && !empty($image['url'])) {
            $alt = !empty($image['alt']) ? $image['alt'] : $this->get_title();
            $image_html = '<img src="' . esc_url($image['url']) . '" alt="' . esc_attr($alt) . '" />';
        }

        return $image_html;
    }

    /**
     * Render label icon if provided.
     *
     * @param array<string, mixed> $settings Widget settings.
     * @param string               $context Context identifier.
     *
     * @return string
     */
    public function render_label_icon(array $settings, string $context): string
    {
        $icon_key = 'kng_' . $context . '_icon';
        $icon = $settings[$icon_key] ?? null;

        if (empty($icon)) {
            return '';
        }

        ob_start();
        Icons_Manager::render_icon($icon, ['aria-hidden' => 'true']);

        return '<span class="king-addons-content-toggle__label-icon">' . ob_get_clean() . '</span>';
    }

    /**
     * Get Elementor template content.
     *
     * @param int  $template_id Template id.
     * @param bool $include_css Whether to include template CSS.
     *
     * @return string
     */
    public function get_template_content(int $template_id, bool $include_css = true): string
    {
        if (empty($template_id)) {
            return '';
        }

        $has_css = $include_css && 'internal' === get_option('elementor_css_print_method');

        return Plugin::instance()->frontend->get_builder_content_for_display($template_id, $has_css);
    }

    /**
     * Get Elementor templates options.
     *
     * @return array<string, string>
     */
    public function get_elementor_templates_options(): array
    {
        $options = [];
        $templates = Plugin::$instance->templates_manager->get_source('local')->get_items();

        if (!empty($templates)) {
            foreach ($templates as $template) {
                $options[$template['template_id']] = $template['title'];
            }
        }

        return $options;
    }
}




