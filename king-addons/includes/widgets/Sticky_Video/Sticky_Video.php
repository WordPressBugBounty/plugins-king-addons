<?php
/**
 * Sticky Video Widget (Free).
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
 * Renders a floating sticky video player.
 */
class Sticky_Video extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-sticky-video';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Sticky Video', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-video';
    }

    /**
     * Style dependencies.
     *
     * @return array<int, string>
     */
    public function get_style_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-sticky-video-style',
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
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-sticky-video-script',
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
        return ['video', 'sticky', 'floating', 'youtube', 'vimeo'];
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
     * Render widget output.
     *
     * @return void
     */
    public function render(): void
    {
        $settings = $this->get_settings_for_display();
        $position = $settings['kng_position'] ?? 'bottom-right';
        $allowed_positions = ['bottom-right', 'bottom-left'];

        if (!in_array($position, $allowed_positions, true)) {
            $position = 'bottom-right';
        }

        $width_value = isset($settings['kng_width']['size']) ? max(160.0, (float) $settings['kng_width']['size']) : 320.0;
        $offset_x = isset($settings['kng_offset_x']['size']) ? max(0.0, (float) $settings['kng_offset_x']['size']) : 24.0;
        $offset_y = isset($settings['kng_offset_y']['size']) ? max(0.0, (float) $settings['kng_offset_y']['size']) : 24.0;
        $aspect_ratio = $this->normalize_ratio($settings['kng_aspect_ratio'] ?? '16-9');

        $classes = [
            'king-addons-sticky-video',
            'king-addons-sticky-video--' . sanitize_html_class($position),
            'is-visible',
        ];

        $attrs = [
            'class' => implode(' ', $classes),
            'data-position' => $position,
            'data-offset-x' => (string) $offset_x,
            'data-offset-y' => (string) $offset_y,
            'data-trigger-scroll' => '0',
            'data-delay-ms' => '0',
            'data-persist-close' => 'no',
            'data-persist-hours' => '0',
            'data-device' => 'all',
            'data-unique' => $this->get_id(),
            'data-show-close' => (($settings['kng_show_close'] ?? 'yes') === 'yes') ? 'yes' : 'no',
            'style' => '--kng-sticky-width:' . $width_value . 'px;'
                . '--kng-sticky-aspect:' . $aspect_ratio . ';'
                . '--kng-sticky-offset-x:' . $offset_x . 'px;'
                . '--kng-sticky-offset-y:' . $offset_y . 'px;',
        ];

        $video_markup = $this->get_video_markup($settings);

        if ($video_markup === '') {
            echo '<div class="king-addons-sticky-video__notice">' . esc_html__('Please provide a video source.', 'king-addons') . '</div>';
            return;
        }

        ?>
        <div <?php echo $this->compile_attributes($attrs); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
            <div class="king-addons-sticky-video__frame">
                <?php if (($settings['kng_show_close'] ?? 'yes') === 'yes') : ?>
                    <button class="king-addons-sticky-video__close" type="button" aria-label="<?php echo esc_attr__('Close sticky video', 'king-addons'); ?>">
                        &times;
                    </button>
                <?php endif; ?>
                <div class="king-addons-sticky-video__media">
                    <?php echo $video_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
            </div>
            <?php if (!empty($settings['kng_label'])) : ?>
                <div class="king-addons-sticky-video__label">
                    <?php echo esc_html($settings['kng_label']); ?>
                </div>
            <?php endif; ?>
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
            'kng_video_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Video', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_video_source',
            [
                'label' => esc_html__('Source', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'youtube',
                'options' => [
                    'youtube' => esc_html__('YouTube (ID)', 'king-addons'),
                    'vimeo' => esc_html__('Vimeo (ID)', 'king-addons'),
                    'self_hosted' => esc_html__('Self-Hosted', 'king-addons'),
                ],
            ]
        );

        $this->add_control(
            'kng_youtube_id',
            [
                'label' => esc_html__('YouTube Video ID', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => esc_html__('e.g. dQw4w9WgXcQ', 'king-addons'),
                'description' => esc_html__('Use the ID from the YouTube URL (after v=).', 'king-addons'),
                'condition' => [
                    'kng_video_source' => 'youtube',
                ],
            ]
        );

        $this->add_control(
            'kng_vimeo_id',
            [
                'label' => esc_html__('Vimeo Video ID', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => esc_html__('e.g. 76979871', 'king-addons'),
                'description' => esc_html__('Use the numeric ID from the Vimeo URL.', 'king-addons'),
                'condition' => [
                    'kng_video_source' => 'vimeo',
                ],
            ]
        );

        $this->add_control(
            'kng_self_hosted',
            [
                'label' => esc_html__('Self-Hosted Video', 'king-addons'),
                'type' => Controls_Manager::MEDIA,
                'media_types' => ['video'],
                'condition' => [
                    'kng_video_source' => 'self_hosted',
                ],
            ]
        );

        $this->add_control(
            'kng_start_time',
            [
                'label' => esc_html__('Start Time (seconds)', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 0,
                'max' => 3600,
                'default' => 0,
            ]
        );

        $this->add_control(
            'kng_autoplay',
            [
                'label' => esc_html__('Autoplay', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_muted',
            [
                'label' => esc_html__('Mute', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_loop',
            [
                'label' => esc_html__('Loop', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
            ]
        );

        $this->add_control(
            'kng_show_controls',
            [
                'label' => esc_html__('Show Controls', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_label',
            [
                'label' => esc_html__('Label (optional)', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => esc_html__('Now playing', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_show_close',
            [
                'label' => esc_html__('Show Close Button', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
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

        $this->add_control(
            'kng_position',
            [
                'label' => esc_html__('Position', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'bottom-right',
                'options' => [
                    'bottom-right' => esc_html__('Bottom Right', 'king-addons'),
                    'bottom-left' => esc_html__('Bottom Left', 'king-addons'),
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_width',
            [
                'label' => esc_html__('Width', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 200, 'max' => 640],
                ],
                'default' => [
                    'size' => 320,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-sticky-video' => 'max-width: 90vw;',
                ],
            ]
        );

        $this->add_control(
            'kng_offset_x',
            [
                'label' => esc_html__('Horizontal Offset', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 200],
                ],
                'default' => [
                    'size' => 24,
                    'unit' => 'px',
                ],
            ]
        );

        $this->add_control(
            'kng_offset_y',
            [
                'label' => esc_html__('Vertical Offset', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 200],
                ],
                'default' => [
                    'size' => 24,
                    'unit' => 'px',
                ],
            ]
        );

        $this->add_control(
            'kng_aspect_ratio',
            [
                'label' => esc_html__('Aspect Ratio', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => '16-9',
                'options' => [
                    '16-9' => esc_html__('16:9', 'king-addons'),
                    '4-3' => esc_html__('4:3', 'king-addons'),
                    '1-1' => esc_html__('1:1', 'king-addons'),
                    '9-16' => esc_html__('9:16', 'king-addons'),
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
            'kng_container_style',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Container', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'kng_container_bg',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-sticky-video' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_container_border',
                'selector' => '{{WRAPPER}} .king-addons-sticky-video',
            ]
        );

        $this->add_control(
            'kng_container_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 40],
                    '%' => ['min' => 0, 'max' => 50],
                ],
                'default' => [
                    'size' => 12,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-sticky-video' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_container_shadow',
                'selector' => '{{WRAPPER}} .king-addons-sticky-video',
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'kng_label_style',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Label', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'kng_label!' => '',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_label_typography',
                'selector' => '{{WRAPPER}} .king-addons-sticky-video__label',
            ]
        );

        $this->add_control(
            'kng_label_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-sticky-video__label' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'kng_close_style',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Close Button', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'kng_show_close' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_close_color',
            [
                'label' => esc_html__('Icon Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-sticky-video__close' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_close_bg',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-sticky-video__close' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_close_radius',
            [
                'label' => esc_html__('Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 20],
                    '%' => ['min' => 0, 'max' => 50],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-sticky-video__close' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render pro upgrade section.
     *
     * @return void
     */
    public function register_pro_notice_controls(): void
    {
        if (!king_addons_freemius()->can_use_premium_code__premium_only()) {
            Core::renderProFeaturesSection($this, '', Controls_Manager::RAW_HTML, 'sticky-video', [
                'Top corner placements with per-axis offsets',
                'Delayed or scroll-triggered reveal',
                'Device targeting and close persistence',
                'CTA button beneath the player',
            ]);
        }
    }

    /**
     * Build video markup.
     *
     * @param array<string, mixed> $settings Settings.
     *
     * @return string
     */
    protected function get_video_markup(array $settings): string
    {
        $source = $settings['kng_video_source'] ?? 'youtube';
        $autoplay = (($settings['kng_autoplay'] ?? 'yes') === 'yes');
        $muted = (($settings['kng_muted'] ?? 'yes') === 'yes');
        $loop = (($settings['kng_loop'] ?? '') === 'yes');
        $controls = (($settings['kng_show_controls'] ?? 'yes') === 'yes');
        $start = max(0, (int) ($settings['kng_start_time'] ?? 0));

        if ($source === 'youtube' && !empty($settings['kng_youtube_id'])) {
            $video_id = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $settings['kng_youtube_id']);
            $params = [
                'rel' => '0',
                'playsinline' => '1',
                'autoplay' => $autoplay ? '1' : '0',
                'mute' => $muted ? '1' : '0',
                'controls' => $controls ? '1' : '0',
            ];

            if ($start > 0) {
                $params['start'] = (string) $start;
            }

            if ($loop) {
                $params['loop'] = '1';
                $params['playlist'] = $video_id;
            }

            $src = 'https://www.youtube.com/embed/' . rawurlencode($video_id) . '?' . http_build_query($params);

            return '<iframe src="' . esc_url($src) . '" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen title="' . esc_attr__('Sticky video', 'king-addons') . '"></iframe>';
        }

        if ($source === 'vimeo' && !empty($settings['kng_vimeo_id'])) {
            $video_id = preg_replace('/[^0-9]/', '', (string) $settings['kng_vimeo_id']);
            $params = [
                'autoplay' => $autoplay ? '1' : '0',
                'muted' => $muted ? '1' : '0',
                'loop' => $loop ? '1' : '0',
                'title' => '0',
                'byline' => '0',
                'portrait' => '0',
                'controls' => $controls ? '1' : '0',
            ];

            $src = 'https://player.vimeo.com/video/' . rawurlencode($video_id);
            $query = http_build_query($params);
            $fragment = $start > 0 ? '#t=' . $start . 's' : '';

            return '<iframe src="' . esc_url($src . '?' . $query . $fragment) . '" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen title="' . esc_attr__('Sticky video', 'king-addons') . '"></iframe>';
        }

        if ($source === 'self_hosted' && !empty($settings['kng_self_hosted']['url'])) {
            $video_url = esc_url($settings['kng_self_hosted']['url']);
            $attrs = [
                'playsinline',
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

            return '<video src="' . $video_url . '" ' . esc_attr($attr_string) . '></video>';
        }

        return '';
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

            $compiled[] = esc_attr($key) . '="' . esc_attr((string) $value) . '"';
        }

        return implode(' ', $compiled);
    }

    /**
     * Normalize ratio label to percentage padding.
     *
     * @param string $ratio Ratio label.
     *
     * @return string
     */
    protected function normalize_ratio(string $ratio): string
    {
        $map = [
            '16-9' => '56.25%',
            '4-3' => '75%',
            '1-1' => '100%',
            '9-16' => '177.78%',
        ];

        return $map[$ratio] ?? '56.25%';
    }
}



