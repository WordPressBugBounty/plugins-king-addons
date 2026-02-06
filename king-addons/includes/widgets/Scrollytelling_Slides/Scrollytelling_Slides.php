<?php
/**
 * Scrollytelling Slides Widget (Free).
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Embed;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Typography;
use Elementor\Plugin;
use Elementor\Repeater;
use Elementor\Utils;
use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Scrollytelling slides widget.
 */
class Scrollytelling_Slides extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-scrollytelling-slides';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Scrollytelling Slides', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-scrollytelling-slides';
    }

    /**
     * Style dependencies.
     *
     * @return array<int, string>
     */
    public function get_style_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-scrollytelling-slides-style',
        ];
    }

    /**
     * Script dependencies.
     *
     * @return array<int, string>
     */
    public function get_script_depends(): array
    {
        $deps = [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-scrollytelling-slides-script',
        ];

        if (king_addons_can_use_pro()) {
            $deps[] = KING_ADDONS_ASSETS_UNIQUE_KEY . '-lottie-lottie';
        }

        return $deps;
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
        return ['scrollytelling', 'slides', 'story', 'scroll', 'progress'];
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
        $this->register_behavior_controls();
        $this->register_style_controls();
        $this->register_pro_notice_controls();
    }

    /**
     * Render widget output.
     *
     * @return void
     */
    public function render(): void
    {
        $settings = $this->get_settings_for_display();
        $slides = $settings['kng_slides'] ?? [];

        if (!is_array($slides) || empty($slides)) {
            echo '<div class="king-addons-scrollytelling__notice">' . esc_html__('Please add at least one slide.', 'king-addons') . '</div>';
            return;
        }

        $can_pro = king_addons_can_use_pro();
        $max_slides = $can_pro ? 20 : 5;
        $slides = array_values(array_slice($slides, 0, $max_slides));

        $is_editor = class_exists(Plugin::class) && Plugin::instance()->editor->is_edit_mode();
        if (!$can_pro && count($slides) < 3) {
            if ($is_editor) {
                echo '<div class="king-addons-scrollytelling__notice">' . esc_html__('Add at least 3 slides for the Free version.', 'king-addons') . '</div>';
            }
            return;
        }

        $progress_style = $settings['kng_progress_style'] ?? 'dots';
        $markers_enabled = ($settings['kng_progress_markers'] ?? '') === 'yes';
        $label_mode = $can_pro ? ($settings['kng_progress_label_mode'] ?? '') : '';
        $show_numbers = ($settings['kng_progress_numbers'] ?? '') === 'yes';
        $show_labels = false;
        $reverse_columns = ($settings['kng_reverse_columns'] ?? '') === 'yes';
        $sections_enabled = ($settings['kng_sections_enable'] ?? '') === 'yes';
        $section_separators = $can_pro && $sections_enabled && (($settings['kng_section_separators'] ?? '') === 'yes');
        $mobile_media_position = $settings['kng_mobile_media_position'] ?? 'above';
        $sticky_media = $can_pro && (($settings['kng_media_sticky'] ?? '') === 'yes');
        $transition_type = $this->sanitize_transition_type($settings['kng_transition_type'] ?? 'fade');
        $transition_duration = 420;
        if (!empty($settings['kng_transition_duration']['size'])) {
            $transition_duration = (int) $settings['kng_transition_duration']['size'];
        }
        $transition_duration = min(max($transition_duration, 150), 1200);
        $transition_easing = $this->sanitize_transition_easing($settings['kng_transition_easing'] ?? 'ease');

        $snap_mode = $can_pro ? $this->sanitize_snap_mode($settings['kng_snap_mode'] ?? 'off') : 'off';
        $snap_duration = 420;
        if (!empty($settings['kng_snap_duration']['size'])) {
            $snap_duration = (int) $settings['kng_snap_duration']['size'];
        }
        $snap_duration = min(max($snap_duration, 150), 2000);
        $lottie_active_only = $can_pro && (($settings['kng_lottie_active_only'] ?? 'yes') === 'yes');

        $ratio_custom = '';
        if (($settings['kng_media_ratio'] ?? '') === 'custom') {
            $ratio_custom = $this->sanitize_ratio($settings['kng_media_ratio_custom'] ?? '');
        }

        $wrapper_classes = [
            'king-addons-scrollytelling',
            'king-addons-scrollytelling--progress-' . sanitize_html_class($progress_style),
        ];

        if ($reverse_columns) {
            $wrapper_classes[] = 'king-addons-scrollytelling--reverse';
        }

        if ($can_pro && $label_mode !== '') {
            $show_numbers = ('numbers' === $label_mode);
            $show_labels = ('labels' === $label_mode);
        }

        if ($show_numbers) {
            $wrapper_classes[] = 'king-addons-scrollytelling--numbers';
        }

        if ($show_labels) {
            $wrapper_classes[] = 'king-addons-scrollytelling--labels';
        }

        if ('line' === $progress_style && !$markers_enabled) {
            $wrapper_classes[] = 'king-addons-scrollytelling--markers-hidden';
        }

        if ('below' === $mobile_media_position) {
            $wrapper_classes[] = 'king-addons-scrollytelling--mobile-media-below';
        }

        if ($sticky_media) {
            $wrapper_classes[] = 'king-addons-scrollytelling--sticky';
        }

        if ($section_separators) {
            $wrapper_classes[] = 'king-addons-scrollytelling--section-separators';
        }

        if ($can_pro && !empty($transition_type)) {
            $wrapper_classes[] = 'king-addons-scrollytelling--transition-' . $transition_type;
        }

        $activation_offset = 40;
        if (!empty($settings['kng_activation_offset']['size'])) {
            $activation_offset = (int) $settings['kng_activation_offset']['size'];
        }

        $markers_visible = !('line' === $progress_style && !$markers_enabled);

        $options = [
            'offset' => $activation_offset,
            'clickableDots' => $can_pro && (($settings['kng_dots_clickable'] ?? '') === 'yes') && $markers_visible,
            'updateHash' => $can_pro && (($settings['kng_deep_link'] ?? '') === 'yes'),
            'readHash' => $can_pro && (($settings['kng_deep_link'] ?? '') === 'yes'),
            'sticky' => $sticky_media,
            'snap' => $snap_mode,
            'snapDuration' => $snap_duration,
            'lottieActiveOnly' => $lottie_active_only,
        ];

        $this->add_render_attribute('wrapper', [
            'class' => $wrapper_classes,
            'data-options' => wp_json_encode($options),
        ]);

        if (!empty($ratio_custom)) {
            $this->add_render_attribute('wrapper', 'style', '--kng-sly-media-ratio: ' . $ratio_custom . ';');
        }

        if ($sticky_media) {
            $sticky_offset = isset($settings['kng_media_sticky_offset']) ? (int) $settings['kng_media_sticky_offset'] : 0;
            $this->add_render_attribute('wrapper', 'style', '--kng-sly-sticky-offset: ' . $sticky_offset . 'px;');
        }

        if ($can_pro) {
            $this->add_render_attribute(
                'wrapper',
                'style',
                '--kng-sly-transition-duration: ' . $transition_duration . 'ms; --kng-sly-transition-ease: ' . $transition_easing . ';'
            );
        }

        $clickable_dots = !empty($options['clickableDots']);

        echo '<div ' . $this->get_render_attribute_string('wrapper') . '>';
        echo '<div class="king-addons-scrollytelling__inner">';

        echo '<div class="king-addons-scrollytelling__slides">';

        foreach ($slides as $index => $slide) {
            if ($sections_enabled && ($slide['kng_slide_new_section'] ?? '') === 'yes') {
                $section_heading = trim((string) ($slide['kng_slide_section_heading'] ?? ''));
                if (!empty($section_heading)) {
                    echo '<div class="king-addons-scrollytelling__section">' . esc_html($section_heading) . '</div>';
                }
            }

            $slide_classes = ['king-addons-scrollytelling__slide'];
            if (0 === $index) {
                $slide_classes[] = 'is-active';
            }

            $slide_key = 'slide_' . $index;
            $this->add_render_attribute($slide_key, [
                'class' => $slide_classes,
                'data-index' => (string) $index,
            ]);

            $anchor = $this->sanitize_anchor($slide['kng_slide_anchor'] ?? '');
            if (!empty($anchor)) {
                $this->add_render_attribute($slide_key, 'data-anchor', $anchor);
            }

            echo '<article ' . $this->get_render_attribute_string($slide_key) . '>';
            echo '<div class="king-addons-scrollytelling__progress-cell">';

            $dot_label = $this->get_dot_label($slide, $index);
            $dot_display = $this->get_dot_display_label($slide, $index);
            $dot_attrs = [
                'type' => 'button',
                'class' => 'king-addons-scrollytelling__dot',
                'data-index' => (string) $index,
                'aria-label' => $dot_label,
            ];

            if ($index === 0) {
                $dot_attrs['aria-current'] = 'step';
            }

            if (!$clickable_dots) {
                $dot_attrs['disabled'] = 'disabled';
                $dot_attrs['tabindex'] = '-1';
            }

            echo '<button ' . $this->compile_attributes($dot_attrs) . '>';
            echo '<span class="king-addons-scrollytelling__dot-core">';
            echo '<span class="king-addons-scrollytelling__dot-number" aria-hidden="true">' . esc_html((string) ($index + 1)) . '</span>';
            echo '</span>';
            echo '<span class="king-addons-scrollytelling__dot-label">' . esc_html($dot_display) . '</span>';
            echo '</button>';
            echo '</div>';

            echo '<div class="king-addons-scrollytelling__text">';
            echo '<div class="king-addons-scrollytelling__card">';

            $title = $slide['kng_slide_title'] ?? '';
            $subtitle = $slide['kng_slide_subtitle'] ?? '';
            $description = $slide['kng_slide_description'] ?? '';
            $bullets = $this->parse_bullets($slide['kng_slide_bullets'] ?? '');

            if (!empty($title)) {
                echo '<h3 class="king-addons-scrollytelling__title">' . esc_html($title) . '</h3>';
            }

            if (!empty($subtitle)) {
                echo '<p class="king-addons-scrollytelling__subtitle">' . esc_html($subtitle) . '</p>';
            }

            if (!empty($description)) {
                echo '<div class="king-addons-scrollytelling__description">' . wp_kses_post($description) . '</div>';
            }

            if (!empty($bullets)) {
                echo '<ul class="king-addons-scrollytelling__bullets">';
                foreach ($bullets as $bullet) {
                    echo '<li>' . esc_html($bullet) . '</li>';
                }
                echo '</ul>';
            }

            $button_text = $slide['kng_slide_button_text'] ?? '';
            $button_link = $slide['kng_slide_button_link'] ?? [];
            $button_attrs = $this->get_link_attributes($button_link);
            if (!empty($button_text) && !empty($button_attrs['href'])) {
                $button_attrs['class'] = 'king-addons-scrollytelling__button';
                echo '<a ' . $this->compile_attributes($button_attrs) . '>' . esc_html($button_text) . '</a>';
            }

            echo '</div>';
            echo '</div>';

            echo '<div class="king-addons-scrollytelling__media">';
            echo $this->render_media($slide, $settings, $can_pro); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo '</div>';

            echo '</article>';
        }

        echo '</div>';

        if ($sticky_media) {
            echo '<div class="king-addons-scrollytelling__media-panel">';
            echo '<div class="king-addons-scrollytelling__media-sticky" data-index="0">';
            echo $this->render_media($slides[0], $settings, $can_pro); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo '</div>';
            echo '</div>';
        }

        echo '</div>';
        echo '</div>';
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
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Slides', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $repeater = new Repeater();

        $repeater->add_control(
            'kng_slide_label',
            [
                'label' => esc_html__('Slide Label', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $repeater->add_control(
            'kng_slide_title',
            [
                'label' => esc_html__('Title', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'dynamic' => [
                    'active' => true,
                ],
                'default' => esc_html__('Slide Title', 'king-addons'),
                'label_block' => true,
            ]
        );

        $repeater->add_control(
            'kng_slide_subtitle',
            [
                'label' => esc_html__('Subtitle', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'dynamic' => [
                    'active' => true,
                ],
                'label_block' => true,
            ]
        );

        $repeater->add_control(
            'kng_slide_description',
            [
                'label' => esc_html__('Description', 'king-addons'),
                'type' => Controls_Manager::WYSIWYG,
                'dynamic' => [
                    'active' => true,
                ],
                'default' => esc_html__('Use this slide to tell a compelling product story.', 'king-addons'),
            ]
        );

        $repeater->add_control(
            'kng_slide_bullets',
            [
                'label' => esc_html__('Bullets', 'king-addons'),
                'type' => Controls_Manager::TEXTAREA,
                'rows' => 4,
                'description' => esc_html__('Add one bullet per line.', 'king-addons'),
            ]
        );

        $repeater->add_control(
            'kng_slide_button_text',
            [
                'label' => esc_html__('CTA Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $repeater->add_control(
            'kng_slide_button_link',
            [
                'label' => esc_html__('CTA Link', 'king-addons'),
                'type' => Controls_Manager::URL,
                'dynamic' => [
                    'active' => true,
                ],
                'placeholder' => esc_html__('https://', 'king-addons'),
            ]
        );

        $repeater->add_control(
            'kng_slide_media_type',
            [
                'label' => esc_html__('Media Type', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'image',
                'options' => [
                    'image' => esc_html__('Image', 'king-addons'),
                    'video' => esc_html__('Video', 'king-addons'),
                    'lottie' => esc_html__('Lottie (Pro)', 'king-addons'),
                ],
            ]
        );

        $repeater->add_control(
            'kng_slide_image',
            [
                'label' => esc_html__('Image', 'king-addons'),
                'type' => Controls_Manager::MEDIA,
                'default' => [
                    'url' => Utils::get_placeholder_image_src(),
                ],
                'condition' => [
                    'kng_slide_media_type' => 'image',
                ],
            ]
        );

        $repeater->add_control(
            'kng_slide_video_url',
            [
                'label' => esc_html__('Video URL', 'king-addons'),
                'type' => Controls_Manager::URL,
                'dynamic' => [
                    'active' => true,
                ],
                'placeholder' => esc_html__('https://', 'king-addons'),
                'condition' => [
                    'kng_slide_media_type' => 'video',
                ],
            ]
        );

        $repeater->add_control(
            'kng_slide_video_autoplay',
            [
                'label' => esc_html__('Autoplay', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'condition' => [
                    'kng_slide_media_type' => 'video',
                ],
            ]
        );

        $repeater->add_control(
            'kng_slide_video_muted',
            [
                'label' => esc_html__('Muted', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
                'condition' => [
                    'kng_slide_media_type' => 'video',
                ],
            ]
        );

        $repeater->add_control(
            'kng_slide_video_loop',
            [
                'label' => esc_html__('Loop', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'condition' => [
                    'kng_slide_media_type' => 'video',
                ],
            ]
        );

        $repeater->add_control(
            'kng_slide_video_controls',
            [
                'label' => esc_html__('Show Controls', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
                'condition' => [
                    'kng_slide_media_type' => 'video',
                ],
            ]
        );

        $repeater->add_control(
            'kng_slide_video_poster',
            [
                'label' => esc_html__('Video Poster', 'king-addons'),
                'type' => Controls_Manager::MEDIA,
                'condition' => [
                    'kng_slide_media_type' => 'video',
                ],
            ]
        );

        $repeater->add_control(
            'kng_slide_lottie_url',
            [
                'label' => esc_html__('Lottie URL (Pro)', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'dynamic' => [
                    'active' => true,
                ],
                'condition' => [
                    'kng_slide_media_type' => 'lottie',
                ],
                'classes' => 'king-addons-pro-control',
            ]
        );

        $repeater->add_control(
            'kng_slide_lottie_loop',
            [
                'label' => esc_html__('Lottie Loop (Pro)', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
                'condition' => [
                    'kng_slide_media_type' => 'lottie',
                ],
                'classes' => 'king-addons-pro-control',
            ]
        );

        $repeater->add_control(
            'kng_slide_lottie_speed',
            [
                'label' => esc_html__('Lottie Speed (Pro)', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'default' => 1,
                'min' => 0.1,
                'step' => 0.1,
                'condition' => [
                    'kng_slide_media_type' => 'lottie',
                ],
                'classes' => 'king-addons-pro-control',
            ]
        );

        $repeater->add_control(
            'kng_slide_lottie_segment_start',
            [
                'label' => esc_html__('Lottie Segment Start (Pro)', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 0,
                'condition' => [
                    'kng_slide_media_type' => 'lottie',
                ],
                'classes' => 'king-addons-pro-control',
            ]
        );

        $repeater->add_control(
            'kng_slide_lottie_segment_end',
            [
                'label' => esc_html__('Lottie Segment End (Pro)', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 0,
                'condition' => [
                    'kng_slide_media_type' => 'lottie',
                ],
                'classes' => 'king-addons-pro-control',
            ]
        );

        $repeater->add_control(
            'kng_slide_anchor',
            [
                'label' => esc_html__('Slide ID / Anchor (Pro)', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'description' => esc_html__('Used for deep linking.', 'king-addons'),
                'classes' => 'king-addons-pro-control',
            ]
        );

        $repeater->add_control(
            'kng_slide_new_section',
            [
                'label' => esc_html__('Start New Section', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'separator' => 'before',
            ]
        );

        $repeater->add_control(
            'kng_slide_section_heading',
            [
                'label' => esc_html__('Section Heading', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'condition' => [
                    'kng_slide_new_section' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_slides',
            [
                'label' => esc_html__('Slides', 'king-addons'),
                'type' => Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'default' => $this->get_default_slides(),
                'title_field' => '{{{ kng_slide_title }}}',
                'description' => esc_html__('Free version is limited to 5 slides.', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_sections_enable',
            [
                'label' => esc_html__('Enable Sections', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'separator' => 'before',
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'kng_behavior_pro_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON_PRO . esc_html__('Behavior (Pro)', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_media_sticky',
            [
                'label' => esc_html__('Sticky Media Panel (Pro)', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
            ]
        );

        Core::renderUpgradeProNotice($this, Controls_Manager::RAW_HTML, 'scrollytelling-slides', 'kng_media_sticky', ['yes']);

        $this->add_control(
            'kng_media_sticky_offset',
            [
                'label' => esc_html__('Sticky Offset (px) (Pro)', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'default' => 0,
                'condition' => [
                    'kng_media_sticky' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_transition_type',
            [
                'label' => esc_html__('Transition Type (Pro)', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'fade',
                'options' => [
                    'fade' => esc_html__('Fade', 'king-addons'),
                    'slide' => esc_html__('Slide', 'king-addons'),
                    'scale' => esc_html__('Scale', 'king-addons'),
                    'crossfade' => esc_html__('Crossfade', 'king-addons'),
                ],
                'separator' => 'before',
            ]
        );

        Core::renderUpgradeProNotice($this, Controls_Manager::RAW_HTML, 'scrollytelling-slides', 'kng_transition_type', ['fade', 'slide', 'scale', 'crossfade']);

        $this->add_control(
            'kng_transition_duration',
            [
                'label' => esc_html__('Transition Duration (ms) (Pro)', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['ms'],
                'range' => [
                    'ms' => [
                        'min' => 150,
                        'max' => 1200,
                    ],
                ],
                'default' => [
                    'size' => 420,
                    'unit' => 'ms',
                ],
            ]
        );

        $this->add_control(
            'kng_transition_easing',
            [
                'label' => esc_html__('Transition Easing (Pro)', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'ease',
                'options' => [
                    'ease' => esc_html__('Ease', 'king-addons'),
                    'ease-in' => esc_html__('Ease In', 'king-addons'),
                    'ease-out' => esc_html__('Ease Out', 'king-addons'),
                    'ease-in-out' => esc_html__('Ease In Out', 'king-addons'),
                    'linear' => esc_html__('Linear', 'king-addons'),
                ],
            ]
        );

        Core::renderUpgradeProNotice($this, Controls_Manager::RAW_HTML, 'scrollytelling-slides', 'kng_transition_easing', ['ease', 'ease-in', 'ease-out', 'ease-in-out', 'linear']);

        $this->add_control(
            'kng_dots_clickable',
            [
                'label' => esc_html__('Clickable Dots (Pro)', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'separator' => 'before',
            ]
        );

        Core::renderUpgradeProNotice($this, Controls_Manager::RAW_HTML, 'scrollytelling-slides', 'kng_dots_clickable', ['yes']);

        $this->add_control(
            'kng_progress_label_mode',
            [
                'label' => esc_html__('Dot Labels (Pro)', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => '',
                'options' => [
                    '' => esc_html__('Default', 'king-addons'),
                    'none' => esc_html__('None', 'king-addons'),
                    'numbers' => esc_html__('Numbers', 'king-addons'),
                    'labels' => esc_html__('Slide Label', 'king-addons'),
                ],
            ]
        );

        Core::renderUpgradeProNotice($this, Controls_Manager::RAW_HTML, 'scrollytelling-slides', 'kng_progress_label_mode', ['none', 'numbers', 'labels']);

        $this->add_control(
            'kng_snap_mode',
            [
                'label' => esc_html__('Snap To Slides (Pro)', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'off',
                'options' => [
                    'off' => esc_html__('Off', 'king-addons'),
                    'soft' => esc_html__('Soft', 'king-addons'),
                    'strict' => esc_html__('Strict', 'king-addons'),
                ],
                'separator' => 'before',
            ]
        );

        Core::renderUpgradeProNotice($this, Controls_Manager::RAW_HTML, 'scrollytelling-slides', 'kng_snap_mode', ['off', 'soft', 'strict']);

        $this->add_control(
            'kng_snap_duration',
            [
                'label' => esc_html__('Snap Duration (ms) (Pro)', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['ms'],
                'range' => [
                    'ms' => [
                        'min' => 150,
                        'max' => 2000,
                    ],
                ],
                'default' => [
                    'size' => 420,
                    'unit' => 'ms',
                ],
                'condition' => [
                    'kng_snap_mode!' => 'off',
                ],
            ]
        );

        $this->add_control(
            'kng_deep_link',
            [
                'label' => esc_html__('Deep Linking (Pro)', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
            ]
        );

        Core::renderUpgradeProNotice($this, Controls_Manager::RAW_HTML, 'scrollytelling-slides', 'kng_deep_link', ['yes']);

        $this->add_control(
            'kng_lottie_active_only',
            [
                'label' => esc_html__('Play Lottie On Active (Pro)', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
                'separator' => 'before',
            ]
        );

        Core::renderUpgradeProNotice($this, Controls_Manager::RAW_HTML, 'scrollytelling-slides', 'kng_lottie_active_only', ['yes']);

        $this->add_control(
            'kng_section_separators',
            [
                'label' => esc_html__('Section Separators (Pro)', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'condition' => [
                    'kng_sections_enable' => 'yes',
                ],
            ]
        );

        Core::renderUpgradeProNotice($this, Controls_Manager::RAW_HTML, 'scrollytelling-slides', 'kng_section_separators', ['yes']);

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

        $this->add_responsive_control(
            'kng_columns_ratio',
            [
                'label' => esc_html__('Columns Ratio', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => '50-50',
                'options' => [
                    '40-60' => esc_html__('40 / 60', 'king-addons'),
                    '50-50' => esc_html__('50 / 50', 'king-addons'),
                    '60-40' => esc_html__('60 / 40', 'king-addons'),
                ],
                'selectors_dictionary' => [
                    '40-60' => '--kng-sly-text-fr: 0.4fr; --kng-sly-media-fr: 0.6fr;',
                    '50-50' => '--kng-sly-text-fr: 1fr; --kng-sly-media-fr: 1fr;',
                    '60-40' => '--kng-sly-text-fr: 0.6fr; --kng-sly-media-fr: 0.4fr;',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scrollytelling' => '{{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'kng_reverse_columns',
            [
                'label' => esc_html__('Reverse Columns', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'kng_content_alignment',
            [
                'label' => esc_html__('Content Alignment', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'top',
                'options' => [
                    'top' => esc_html__('Top', 'king-addons'),
                    'center' => esc_html__('Center', 'king-addons'),
                ],
                'selectors_dictionary' => [
                    'top' => '--kng-sly-align: flex-start;',
                    'center' => '--kng-sly-align: center;',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scrollytelling' => '{{VALUE}}',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_column_gap',
            [
                'label' => esc_html__('Column Gap', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 120,
                    ],
                    'em' => [
                        'min' => 0,
                        'max' => 10,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scrollytelling' => '--kng-sly-column-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_row_gap',
            [
                'label' => esc_html__('Slide Gap', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 160,
                    ],
                    'em' => [
                        'min' => 0,
                        'max' => 12,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scrollytelling' => '--kng-sly-row-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_slide_min_height',
            [
                'label' => esc_html__('Slide Min Height', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'auto',
                'options' => [
                    'auto' => esc_html__('Auto', 'king-addons'),
                    '100vh' => esc_html__('100vh', 'king-addons'),
                    'custom' => esc_html__('Custom (vh)', 'king-addons'),
                ],
                'selectors_dictionary' => [
                    'auto' => '--kng-sly-slide-min-height: auto;',
                    '100vh' => '--kng-sly-slide-min-height: 100vh;',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scrollytelling' => '{{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'kng_slide_min_height_custom',
            [
                'label' => esc_html__('Custom Min Height', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['vh'],
                'range' => [
                    'vh' => [
                        'min' => 120,
                        'max' => 400,
                    ],
                ],
                'default' => [
                    'size' => 120,
                    'unit' => 'vh',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scrollytelling' => '--kng-sly-slide-min-height: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'kng_slide_min_height' => 'custom',
                ],
            ]
        );

        $this->add_control(
            'kng_media_ratio',
            [
                'label' => esc_html__('Media Aspect Ratio', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => '16-9',
                'options' => [
                    '16-9' => esc_html__('16:9', 'king-addons'),
                    '4-3' => esc_html__('4:3', 'king-addons'),
                    '1-1' => esc_html__('1:1', 'king-addons'),
                    '9-16' => esc_html__('9:16', 'king-addons'),
                    'custom' => esc_html__('Custom', 'king-addons'),
                ],
                'selectors_dictionary' => [
                    '16-9' => '--kng-sly-media-ratio: 16 / 9;',
                    '4-3' => '--kng-sly-media-ratio: 4 / 3;',
                    '1-1' => '--kng-sly-media-ratio: 1 / 1;',
                    '9-16' => '--kng-sly-media-ratio: 9 / 16;',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scrollytelling' => '{{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'kng_media_ratio_custom',
            [
                'label' => esc_html__('Custom Ratio', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => '3/2',
                'condition' => [
                    'kng_media_ratio' => 'custom',
                ],
            ]
        );

        $this->add_control(
            'kng_media_fit',
            [
                'label' => esc_html__('Media Fit', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'cover',
                'options' => [
                    'cover' => esc_html__('Cover', 'king-addons'),
                    'contain' => esc_html__('Contain', 'king-addons'),
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scrollytelling' => '--kng-sly-media-fit: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_mobile_media_position',
            [
                'label' => esc_html__('Mobile Media Position', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'above',
                'options' => [
                    'above' => esc_html__('Above Text', 'king-addons'),
                    'below' => esc_html__('Below Text', 'king-addons'),
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Image_Size::get_type(),
            [
                'name' => 'kng_image_size',
                'default' => 'large',
                'separator' => 'before',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Behavior controls.
     *
     * @return void
     */
    protected function register_behavior_controls(): void
    {
        $this->start_controls_section(
            'kng_behavior_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Behavior', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_activation_offset',
            [
                'label' => esc_html__('Activation Offset (%)', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['%'],
                'range' => [
                    '%' => [
                        'min' => 10,
                        'max' => 80,
                    ],
                ],
                'default' => [
                    'size' => 40,
                    'unit' => '%',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_scroll_margin',
            [
                'label' => esc_html__('Scroll Margin Top', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'vh'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 200,
                    ],
                    'vh' => [
                        'min' => 0,
                        'max' => 40,
                    ],
                ],
                'default' => [
                    'size' => 12,
                    'unit' => 'vh',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scrollytelling' => '--kng-sly-scroll-margin: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_progress_style',
            [
                'label' => esc_html__('Progress Style', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'dots',
                'options' => [
                    'dots' => esc_html__('Dots', 'king-addons'),
                    'line' => esc_html__('Line', 'king-addons'),
                ],
            ]
        );

        $this->add_control(
            'kng_progress_markers',
            [
                'label' => esc_html__('Show Markers', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
                'condition' => [
                    'kng_progress_style' => 'line',
                ],
            ]
        );

        $this->add_control(
            'kng_progress_numbers',
            [
                'label' => esc_html__('Show Numbers', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
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
            'kng_container_style',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Container', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name' => 'kng_container_background',
                'selector' => '{{WRAPPER}} .king-addons-scrollytelling',
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_container_border',
                'selector' => '{{WRAPPER}} .king-addons-scrollytelling',
            ]
        );

        $this->add_responsive_control(
            'kng_container_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scrollytelling' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_container_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scrollytelling__inner' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_container_max_width',
            [
                'label' => esc_html__('Max Width', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => [
                        'min' => 320,
                        'max' => 1600,
                    ],
                    '%' => [
                        'min' => 60,
                        'max' => 100,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scrollytelling__inner' => 'max-width: {{SIZE}}{{UNIT}}; margin-left: auto; margin-right: auto;',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'kng_typography_style',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Typography', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'kng_title_color',
            [
                'label' => esc_html__('Title Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scrollytelling__title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_title_typography',
                'selector' => '{{WRAPPER}} .king-addons-scrollytelling__title',
            ]
        );

        $this->add_control(
            'kng_subtitle_color',
            [
                'label' => esc_html__('Subtitle Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scrollytelling__subtitle' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_subtitle_typography',
                'selector' => '{{WRAPPER}} .king-addons-scrollytelling__subtitle',
            ]
        );

        $this->add_control(
            'kng_description_color',
            [
                'label' => esc_html__('Body Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scrollytelling__description' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_description_typography',
                'selector' => '{{WRAPPER}} .king-addons-scrollytelling__description',
            ]
        );

        $this->add_control(
            'kng_bullets_color',
            [
                'label' => esc_html__('Bullets Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scrollytelling__bullets' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_bullets_typography',
                'selector' => '{{WRAPPER}} .king-addons-scrollytelling__bullets',
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'kng_section_heading_style',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Section Heading', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'kng_sections_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_section_heading_color',
            [
                'label' => esc_html__('Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scrollytelling__section' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_section_heading_typography',
                'selector' => '{{WRAPPER}} .king-addons-scrollytelling__section',
            ]
        );

        $this->add_responsive_control(
            'kng_section_heading_spacing',
            [
                'label' => esc_html__('Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 80,
                    ],
                    'em' => [
                        'min' => 0,
                        'max' => 6,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scrollytelling' => '--kng-sly-section-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'kng_card_style',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Slide Card', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name' => 'kng_card_background',
                'selector' => '{{WRAPPER}} .king-addons-scrollytelling__card',
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_card_border',
                'selector' => '{{WRAPPER}} .king-addons-scrollytelling__card',
            ]
        );

        $this->add_responsive_control(
            'kng_card_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scrollytelling__card' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_card_shadow',
                'selector' => '{{WRAPPER}} .king-addons-scrollytelling__card',
            ]
        );

        $this->add_responsive_control(
            'kng_card_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scrollytelling__card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'kng_media_style',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Media', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'kng_media_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scrollytelling__media-frame' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_media_shadow',
                'selector' => '{{WRAPPER}} .king-addons-scrollytelling__media-frame',
            ]
        );

        $this->add_control(
            'kng_media_opacity_inactive',
            [
                'label' => esc_html__('Inactive Opacity', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['custom'],
                'range' => [
                    'custom' => [
                        'min' => 0,
                        'max' => 1,
                        'step' => 0.05,
                    ],
                ],
                'default' => [
                    'size' => 0.8,
                    'unit' => 'custom',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scrollytelling' => '--kng-sly-media-opacity: {{SIZE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_media_opacity_active',
            [
                'label' => esc_html__('Active Opacity', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['custom'],
                'range' => [
                    'custom' => [
                        'min' => 0,
                        'max' => 1,
                        'step' => 0.05,
                    ],
                ],
                'default' => [
                    'size' => 1,
                    'unit' => 'custom',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scrollytelling' => '--kng-sly-media-opacity-active: {{SIZE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name' => 'kng_media_overlay',
                'types' => ['gradient'],
                'selector' => '{{WRAPPER}} .king-addons-scrollytelling__media-frame::after',
            ]
        );

        $this->add_control(
            'kng_media_overlay_opacity',
            [
                'label' => esc_html__('Overlay Opacity', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['custom'],
                'range' => [
                    'custom' => [
                        'min' => 0,
                        'max' => 1,
                        'step' => 0.05,
                    ],
                ],
                'default' => [
                    'size' => 1,
                    'unit' => 'custom',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scrollytelling' => '--kng-sly-media-overlay-opacity: {{SIZE}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'kng_progress_style_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Progress', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'kng_progress_column_width',
            [
                'label' => esc_html__('Column Width', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 24,
                        'max' => 120,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scrollytelling' => '--kng-sly-progress-cell: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_progress_alignment',
            [
                'label' => esc_html__('Dot Alignment', 'king-addons'),
                'type' => Controls_Manager::CHOOSE,
                'toggle' => true,
                'default' => 'center',
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
                'selectors_dictionary' => [
                    'left' => '--kng-sly-progress-justify: flex-start; --kng-sly-progress-line-position: calc(var(--kng-sly-progress-size) / 2);',
                    'center' => '--kng-sly-progress-justify: center; --kng-sly-progress-line-position: calc(var(--kng-sly-progress-cell) / 2);',
                    'right' => '--kng-sly-progress-justify: flex-end; --kng-sly-progress-line-position: calc(var(--kng-sly-progress-cell) - (var(--kng-sly-progress-size) / 2));',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scrollytelling' => '{{VALUE}}',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_progress_dot_size',
            [
                'label' => esc_html__('Dot Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 6,
                        'max' => 24,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scrollytelling' => '--kng-sly-progress-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_progress_dot_inactive',
            [
                'label' => esc_html__('Dot Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scrollytelling' => '--kng-sly-progress-inactive: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_progress_dot_active',
            [
                'label' => esc_html__('Active Dot Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scrollytelling' => '--kng-sly-progress-active: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_progress_dot_completed',
            [
                'label' => esc_html__('Completed Dot Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scrollytelling' => '--kng-sly-progress-complete: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_progress_label_color',
            [
                'label' => esc_html__('Label Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scrollytelling' => '--kng-sly-progress-label-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_progress_label_typography',
                'selector' => '{{WRAPPER}} .king-addons-scrollytelling__dot-label',
            ]
        );

        $this->add_control(
            'kng_progress_line_color',
            [
                'label' => esc_html__('Line Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scrollytelling' => '--kng-sly-progress-line-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_progress_line_thickness',
            [
                'label' => esc_html__('Line Thickness', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 1,
                        'max' => 6,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scrollytelling' => '--kng-sly-progress-line: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'kng_button_style',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('CTA Button', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_button_typography',
                'selector' => '{{WRAPPER}} .king-addons-scrollytelling__button',
            ]
        );

        $this->add_responsive_control(
            'kng_button_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scrollytelling__button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_button_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scrollytelling__button' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
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
            'kng_button_bg',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scrollytelling__button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scrollytelling__button' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_border_color',
            [
                'label' => esc_html__('Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scrollytelling__button' => 'border-color: {{VALUE}};',
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
            'kng_button_bg_hover',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scrollytelling__button:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_color_hover',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scrollytelling__button:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_border_color_hover',
            [
                'label' => esc_html__('Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scrollytelling__button:hover' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->end_controls_section();
    }

    /**
     * Register Pro notice controls.
     *
     * @return void
     */
    public function register_pro_notice_controls(): void
    {
        if (!king_addons_can_use_pro()) {
            Core::renderProFeaturesSection($this, '', Controls_Manager::RAW_HTML, 'scrollytelling-slides', [
                'Sticky media panel with configurable offset',
                'Animated transitions (fade/slide/scale) with easing presets',
                'Per-slide Lottie animations with play-on-active control',
                'Clickable dots with optional label modes',
                'Section separators in the progress rail',
                'Snap-to-slide scrolling with duration control',
                'Deep linking to slides with URL hash sync',
            ]);
        }
    }

    /**
     * Render slide media.
     *
     * @param array<string, mixed> $slide Slide data.
     * @param array<string, mixed> $settings Widget settings.
     * @param bool                $can_pro Whether Pro is available.
     *
     * @return string
     */
    protected function render_media(array $slide, array $settings, bool $can_pro): string
    {
        $media_type = $slide['kng_slide_media_type'] ?? 'image';
        $media_html = '';

        if ('video' === $media_type) {
            $media_html = $this->get_video_markup($slide);
        } elseif ('lottie' === $media_type) {
            if ($can_pro) {
                $lottie_url = trim((string) ($slide['kng_slide_lottie_url'] ?? ''));
                if ($lottie_url !== '') {
                    $speed = isset($slide['kng_slide_lottie_speed']) ? (float) $slide['kng_slide_lottie_speed'] : 1.0;
                    if ($speed <= 0) {
                        $speed = 1.0;
                    }

                    $settings = [
                        'loop' => ($slide['kng_slide_lottie_loop'] ?? '') === 'yes',
                        'speed' => $speed,
                        'segmentStart' => $slide['kng_slide_lottie_segment_start'] ?? '',
                        'segmentEnd' => $slide['kng_slide_lottie_segment_end'] ?? '',
                    ];

                    $media_html = sprintf(
                        '<div class="king-addons-scrollytelling__lottie" data-json-url="%s" data-settings="%s"></div>',
                        esc_url($lottie_url),
                        esc_attr(wp_json_encode($settings))
                    );
                } else {
                    $media_html = '<div class="king-addons-scrollytelling__media-placeholder">' . esc_html__('Add a Lottie URL for this slide.', 'king-addons') . '</div>';
                }
            } else {
                $media_html = '<div class="king-addons-scrollytelling__media-placeholder">' . esc_html__('Lottie animation is available in Pro.', 'king-addons') . '</div>';
            }
        } else {
            $image = $slide['kng_slide_image'] ?? [];
            $image_id = is_array($image) ? (int) ($image['id'] ?? 0) : 0;

            if ($image_id) {
                $media_html = Group_Control_Image_Size::get_attachment_image_html($settings, 'kng_image_size', $image_id);
            }

            if (empty($media_html)) {
                $placeholder = Utils::get_placeholder_image_src();
                $media_html = '<img src="' . esc_url($placeholder) . '" alt="" loading="lazy" />';
            }
        }

        if (empty($media_html)) {
            $media_html = '<div class="king-addons-scrollytelling__media-placeholder">' . esc_html__('Add media for this slide.', 'king-addons') . '</div>';
        }

        return '<div class="king-addons-scrollytelling__media-frame">' . $media_html . '</div>';
    }

    /**
     * Build video markup.
     *
     * @param array<string, mixed> $slide Slide data.
     *
     * @return string
     */
    protected function get_video_markup(array $slide): string
    {
        $video_url = '';
        if (!empty($slide['kng_slide_video_url'])) {
            $video_url = is_array($slide['kng_slide_video_url'])
                ? (string) ($slide['kng_slide_video_url']['url'] ?? '')
                : (string) $slide['kng_slide_video_url'];
        }

        if (empty($video_url)) {
            return '';
        }

        $autoplay = ($slide['kng_slide_video_autoplay'] ?? '') === 'yes';
        $muted = ($slide['kng_slide_video_muted'] ?? 'yes') === 'yes';
        $loop = ($slide['kng_slide_video_loop'] ?? '') === 'yes';
        $controls = ($slide['kng_slide_video_controls'] ?? 'yes') === 'yes';
        $poster = '';

        if (!empty($slide['kng_slide_video_poster']['url'])) {
            $poster = (string) $slide['kng_slide_video_poster']['url'];
        }

        $video = Embed::get_video_properties($video_url);

        if (!empty($video['provider']) && !empty($video['video_id'])) {
            if ('youtube' === $video['provider']) {
                $params = [
                    'rel' => '0',
                    'playsinline' => '1',
                    'autoplay' => $autoplay ? '1' : '0',
                    'mute' => $muted ? '1' : '0',
                    'controls' => $controls ? '1' : '0',
                ];

                if ($loop) {
                    $params['loop'] = '1';
                    $params['playlist'] = (string) $video['video_id'];
                }

                $src = 'https://www.youtube.com/embed/' . rawurlencode((string) $video['video_id']) . '?' . http_build_query($params);

                $attrs = [
                    'class' => 'king-addons-scrollytelling__iframe',
                    'src' => 'about:blank',
                    'data-src' => esc_url($src),
                    'loading' => 'lazy',
                    'allow' => 'autoplay; encrypted-media; picture-in-picture',
                    'allowfullscreen' => 'allowfullscreen',
                    'title' => esc_attr__('Slide video', 'king-addons'),
                ];

                return '<iframe ' . $this->compile_attributes($attrs) . '></iframe>';
            }

            if ('vimeo' === $video['provider']) {
                $params = [
                    'autoplay' => $autoplay ? '1' : '0',
                    'muted' => $muted ? '1' : '0',
                    'loop' => $loop ? '1' : '0',
                    'title' => '0',
                    'byline' => '0',
                    'portrait' => '0',
                    'controls' => $controls ? '1' : '0',
                ];

                $src = 'https://player.vimeo.com/video/' . rawurlencode((string) $video['video_id']);
                $data_src = $src . '?' . http_build_query($params);

                $attrs = [
                    'class' => 'king-addons-scrollytelling__iframe',
                    'src' => 'about:blank',
                    'data-src' => esc_url($data_src),
                    'loading' => 'lazy',
                    'allow' => 'autoplay; fullscreen; picture-in-picture',
                    'allowfullscreen' => 'allowfullscreen',
                    'title' => esc_attr__('Slide video', 'king-addons'),
                ];

                return '<iframe ' . $this->compile_attributes($attrs) . '></iframe>';
            }
        }

        $attrs = [
            'playsinline',
            'preload="metadata"',
        ];

        if ($autoplay) {
            $attrs[] = 'autoplay';
        }

        if ($muted) {
            $attrs[] = 'muted';
        }

        if ($loop) {
            $attrs[] = 'loop';
        }

        if ($controls) {
            $attrs[] = 'controls';
        }

        $attr_string = implode(' ', $attrs);
        $poster_attr = !empty($poster) ? ' poster="' . esc_url($poster) . '"' : '';

        return '<video src="' . esc_url($video_url) . '" ' . $attr_string . $poster_attr . '></video>';
    }

    /**
     * Build an accessible label for dots.
     *
     * @param array<string, mixed> $slide Slide data.
     * @param int                 $index Slide index.
     *
     * @return string
     */
    protected function get_dot_label(array $slide, int $index): string
    {
        $label = trim((string) ($slide['kng_slide_label'] ?? ''));
        if (!empty($label)) {
            return sprintf(esc_html__('Go to %s', 'king-addons'), $label);
        }

        return sprintf(esc_html__('Go to slide %d', 'king-addons'), $index + 1);
    }

    /**
     * Build a visible label for dot UI.
     *
     * @param array<string, mixed> $slide Slide data.
     * @param int                 $index Slide index.
     *
     * @return string
     */
    protected function get_dot_display_label(array $slide, int $index): string
    {
        $label = trim((string) ($slide['kng_slide_label'] ?? ''));
        if (!empty($label)) {
            return $label;
        }

        $title = trim((string) ($slide['kng_slide_title'] ?? ''));
        if (!empty($title)) {
            return $title;
        }

        return sprintf(esc_html__('Slide %d', 'king-addons'), $index + 1);
    }

    /**
     * Parse bullet list string into items.
     *
     * @param string $value Bullets text.
     *
     * @return array<int, string>
     */
    protected function parse_bullets(string $value): array
    {
        $value = trim($value);
        if ('' === $value) {
            return [];
        }

        $items = preg_split('/\r\n|\r|\n/', $value);
        if (!$items) {
            return [];
        }

        $items = array_map('trim', $items);
        return array_values(array_filter($items, static fn($item) => $item !== ''));
    }

    /**
     * Build link attributes.
     *
     * @param array<string, mixed> $link Link data.
     *
     * @return array<string, string>
     */
    protected function get_link_attributes(array $link): array
    {
        $href = $link['url'] ?? '';
        if (empty($href)) {
            return [];
        }

        $attributes = [
            'href' => esc_url($href),
        ];

        $rels = [];

        if (!empty($link['is_external'])) {
            $attributes['target'] = '_blank';
            $rels[] = 'noopener';
            $rels[] = 'noreferrer';
        }

        if (!empty($link['nofollow'])) {
            $rels[] = 'nofollow';
        }

        if (!empty($rels)) {
            $attributes['rel'] = implode(' ', array_unique($rels));
        }

        return $attributes;
    }

    /**
     * Compile attributes for output.
     *
     * @param array<string, string> $attrs Attributes.
     *
     * @return string
     */
    protected function compile_attributes(array $attrs): string
    {
        $compiled = [];

        foreach ($attrs as $key => $value) {
            if ($value === '') {
                continue;
            }

            $compiled[] = esc_attr($key) . '="' . esc_attr($value) . '"';
        }

        return implode(' ', $compiled);
    }

    /**
     * Sanitize anchor.
     *
     * @param string $value Anchor value.
     *
     * @return string
     */
    protected function sanitize_anchor(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        return sanitize_title($value);
    }

    /**
     * Sanitize custom ratio input.
     *
     * @param string $ratio Ratio string.
     *
     * @return string
     */
    protected function sanitize_ratio(string $ratio): string
    {
        $ratio = trim($ratio);
        if ($ratio === '') {
            return '';
        }

        if (preg_match('/^(\d+(?:\.\d+)?)\s*[:\/]\s*(\d+(?:\.\d+)?)$/', $ratio, $matches)) {
            $width = (float) $matches[1];
            $height = (float) $matches[2];
            if ($width > 0 && $height > 0) {
                return $width . ' / ' . $height;
            }
        }

        return '';
    }

    /**
     * Sanitize transition type.
     *
     * @param string $value Transition type.
     *
     * @return string
     */
    protected function sanitize_transition_type(string $value): string
    {
        $allowed = ['fade', 'slide', 'scale', 'crossfade'];
        return in_array($value, $allowed, true) ? $value : 'fade';
    }

    /**
     * Sanitize transition easing.
     *
     * @param string $value Easing preset.
     *
     * @return string
     */
    protected function sanitize_transition_easing(string $value): string
    {
        $allowed = ['ease', 'ease-in', 'ease-out', 'ease-in-out', 'linear'];
        return in_array($value, $allowed, true) ? $value : 'ease';
    }

    /**
     * Sanitize snap mode.
     *
     * @param string $value Snap mode.
     *
     * @return string
     */
    protected function sanitize_snap_mode(string $value): string
    {
        $allowed = ['off', 'soft', 'strict'];
        return in_array($value, $allowed, true) ? $value : 'off';
    }

    /**
     * Default slides.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function get_default_slides(): array
    {
        return [
            [
                'kng_slide_label' => esc_html__('Intro', 'king-addons'),
                'kng_slide_title' => esc_html__('Tell the big idea', 'king-addons'),
                'kng_slide_subtitle' => esc_html__('Set the stage', 'king-addons'),
                'kng_slide_description' => esc_html__('Introduce the challenge and the promise in one focused slide.', 'king-addons'),
                'kng_slide_bullets' => "Quick context\nClear value\nStrong visual",
                'kng_slide_button_text' => esc_html__('Learn more', 'king-addons'),
                'kng_slide_button_link' => [
                    'url' => '#',
                ],
                'kng_slide_media_type' => 'image',
                'kng_slide_image' => [
                    'url' => Utils::get_placeholder_image_src(),
                ],
            ],
            [
                'kng_slide_label' => esc_html__('Feature', 'king-addons'),
                'kng_slide_title' => esc_html__('Highlight the feature', 'king-addons'),
                'kng_slide_subtitle' => esc_html__('Explain the impact', 'king-addons'),
                'kng_slide_description' => esc_html__('Use bullets to summarize outcomes and benefits.', 'king-addons'),
                'kng_slide_bullets' => "Faster workflows\nCleaner outputs\nEasy adoption",
                'kng_slide_media_type' => 'image',
                'kng_slide_image' => [
                    'url' => Utils::get_placeholder_image_src(),
                ],
            ],
            [
                'kng_slide_label' => esc_html__('Next step', 'king-addons'),
                'kng_slide_title' => esc_html__('Move to action', 'king-addons'),
                'kng_slide_subtitle' => esc_html__('Invite the user', 'king-addons'),
                'kng_slide_description' => esc_html__('Close with a clear action and supporting visual.', 'king-addons'),
                'kng_slide_button_text' => esc_html__('Get started', 'king-addons'),
                'kng_slide_button_link' => [
                    'url' => '#',
                ],
                'kng_slide_media_type' => 'image',
                'kng_slide_image' => [
                    'url' => Utils::get_placeholder_image_src(),
                ],
            ],
        ];
    }
}
