<?php
/**
 * Parallax Depth Cards Widget (Free).
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
use Elementor\Repeater;
use Elementor\Utils;
use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Parallax depth cards widget.
 */
class Parallax_Depth_Cards extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-parallax-depth-cards';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Parallax Depth Cards', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-parallax-depth-cards';
    }

    /**
     * Style dependencies.
     *
     * @return array<int, string>
     */
    public function get_style_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-parallax-depth-cards-style',
            'elementor-icons-fa-solid',
            'elementor-icons-fa-regular',
            'elementor-icons-fa-brands',
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
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-parallax-depth-cards-script',
        ];
    }

    /**
     * Widget categories.
     *
     * @return array<int, string>
     */
    public function get_categories(): array
    {
        return ['king-addons'];
    }

    /**
     * Widget keywords.
     *
     * @return array<int, string>
     */
    public function get_keywords(): array
    {
        return ['parallax', 'depth', 'cards', 'tilt', 'hover', '3d', 'interactive'];
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
        $this->register_layout_controls();
        $this->register_cards_controls();
        $this->register_single_card_controls();
        $this->register_interaction_controls();
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
        $mode = $settings['kng_pdc_mode'] ?? 'cards';

        if ($mode === 'single') {
            $cards = [$this->get_single_card_data($settings)];
        } else {
            $cards = $settings['kng_pdc_cards'] ?? [];
        }

        if (empty($cards)) {
            return;
        }

        $align = $settings['kng_pdc_content_align'] ?? 'left';
        $valign = $settings['kng_pdc_content_v_align'] ?? 'top';
        $height_mode = $settings['kng_pdc_card_height_mode'] ?? 'auto';

        $wrapper_classes = [
            'king-addons-parallax-depth-cards',
            'king-addons-parallax-depth-cards--mode-' . sanitize_html_class($mode),
            'king-addons-parallax-depth-cards--align-' . sanitize_html_class($align),
            'king-addons-parallax-depth-cards--valign-' . sanitize_html_class($valign),
            'king-addons-parallax-depth-cards--height-' . sanitize_html_class($height_mode),
        ];

        $enable_tilt = ($settings['kng_pdc_enable_tilt'] ?? 'yes') === 'yes';
        $enable_parallax = ($settings['kng_pdc_enable_parallax'] ?? 'yes') === 'yes';
        $trigger = sanitize_key($settings['kng_pdc_trigger'] ?? 'hover');
        $reduce_motion = ($settings['kng_pdc_reduce_motion'] ?? 'yes') === 'yes';
        $disable_touch = ($settings['kng_pdc_disable_touch'] ?? 'yes') === 'yes';

        $intensity = $this->get_control_number($settings['kng_pdc_intensity'] ?? 60, 60);
        $max_tilt = $this->get_control_number($settings['kng_pdc_max_tilt'] ?? 12, 12);
        $depth_strength = $this->get_control_number($settings['kng_pdc_depth_strength'] ?? 18, 18);
        $smoothing = $this->get_control_number($settings['kng_pdc_smoothing'] ?? 0.12, 0.12);
        $reset_duration = $this->get_control_number($settings['kng_pdc_reset_duration'] ?? 350, 350);

        $depths = [
            'bg' => $this->get_control_number($settings['kng_pdc_depth_bg'] ?? 0.2, 0.2),
            'media' => $this->get_control_number($settings['kng_pdc_depth_media'] ?? 0.35, 0.35),
            'content' => $this->get_control_number($settings['kng_pdc_depth_content'] ?? 0.5, 0.5),
        ];

        $wrapper_attributes = [
            'class' => implode(' ', $wrapper_classes),
            'data-tilt' => $enable_tilt ? 'yes' : 'no',
            'data-parallax' => $enable_parallax ? 'yes' : 'no',
            'data-intensity' => (string) $intensity,
            'data-max-tilt' => (string) $max_tilt,
            'data-depth-strength' => (string) $depth_strength,
            'data-smoothing' => (string) $smoothing,
            'data-reset-duration' => (string) $reset_duration,
            'data-trigger' => $trigger,
            'data-reduce-motion' => $reduce_motion ? 'yes' : 'no',
            'data-disable-touch' => $disable_touch ? 'yes' : 'no',
        ];

        ?>
        <div <?php echo $this->render_attribute_string($wrapper_attributes); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
            <div class="king-addons-parallax-depth-cards__grid">
                <?php foreach ($cards as $card) : ?>
                    <?php $this->render_card($settings, $card, $depths); ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Register layout controls.
     *
     * @return void
     */
    protected function register_layout_controls(): void
    {
        $this->start_controls_section(
            'kng_pdc_layout_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Layout', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_pdc_mode',
            [
                'label' => esc_html__('Mode', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'cards' => esc_html__('Cards (Repeater)', 'king-addons'),
                    'single' => esc_html__('Single Card', 'king-addons'),
                ],
                'default' => 'cards',
            ]
        );

        $this->add_responsive_control(
            'kng_pdc_columns',
            [
                'label' => esc_html__('Columns', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 6,
                'step' => 1,
                'desktop_default' => 3,
                'tablet_default' => 2,
                'mobile_default' => 1,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-parallax-depth-cards' => '--ka-pdc-cols: {{SIZE}};',
                ],
                'condition' => [
                    'kng_pdc_mode' => 'cards',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_pdc_column_gap',
            [
                'label' => esc_html__('Column Gap', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 80],
                    'em' => ['min' => 0, 'max' => 4],
                    'rem' => ['min' => 0, 'max' => 4],
                ],
                'default' => [
                    'size' => 24,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-parallax-depth-cards' => '--ka-pdc-col-gap: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'kng_pdc_mode' => 'cards',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_pdc_row_gap',
            [
                'label' => esc_html__('Row Gap', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 80],
                    'em' => ['min' => 0, 'max' => 4],
                    'rem' => ['min' => 0, 'max' => 4],
                ],
                'default' => [
                    'size' => 24,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-parallax-depth-cards' => '--ka-pdc-row-gap: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'kng_pdc_mode' => 'cards',
                ],
            ]
        );

        $this->add_control(
            'kng_pdc_card_height_mode',
            [
                'label' => esc_html__('Card Height', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'auto' => esc_html__('Auto', 'king-addons'),
                    'min' => esc_html__('Min Height', 'king-addons'),
                ],
                'default' => 'auto',
            ]
        );

        $this->add_responsive_control(
            'kng_pdc_card_min_height',
            [
                'label' => esc_html__('Card Min Height', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => ['min' => 160, 'max' => 600],
                    'em' => ['min' => 10, 'max' => 40],
                    'rem' => ['min' => 10, 'max' => 40],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-parallax-depth-cards' => '--ka-pdc-card-min-height: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'kng_pdc_card_height_mode' => 'min',
                ],
            ]
        );

        $this->add_control(
            'kng_pdc_content_align',
            [
                'label' => esc_html__('Content Alignment', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'left' => esc_html__('Left', 'king-addons'),
                    'center' => esc_html__('Center', 'king-addons'),
                    'right' => esc_html__('Right', 'king-addons'),
                ],
                'default' => 'left',
            ]
        );

        $this->add_control(
            'kng_pdc_content_v_align',
            [
                'label' => esc_html__('Vertical Alignment', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'top' => esc_html__('Top', 'king-addons'),
                    'center' => esc_html__('Center', 'king-addons'),
                    'bottom' => esc_html__('Bottom', 'king-addons'),
                ],
                'default' => 'top',
            ]
        );

        $this->add_control(
            'kng_pdc_title_tag',
            [
                'label' => esc_html__('Title HTML Tag', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'h1' => 'H1',
                    'h2' => 'H2',
                    'h3' => 'H3',
                    'h4' => 'H4',
                    'h5' => 'H5',
                    'h6' => 'H6',
                    'div' => 'DIV',
                    'span' => 'SPAN',
                    'p' => 'P',
                ],
                'default' => 'h3',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register cards repeater controls.
     *
     * @return void
     */
    protected function register_cards_controls(): void
    {
        $this->start_controls_section(
            'kng_pdc_cards_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Cards', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
                'condition' => [
                    'kng_pdc_mode' => 'cards',
                ],
            ]
        );

        $repeater = new Repeater();

        $repeater->add_control(
            'kng_pdc_media_type',
            [
                'label' => esc_html__('Media Type', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'none' => esc_html__('None', 'king-addons'),
                    'image' => esc_html__('Image', 'king-addons'),
                    'icon' => esc_html__('Icon', 'king-addons'),
                ],
                'default' => 'image',
            ]
        );

        $repeater->add_control(
            'kng_pdc_media_image',
            [
                'label' => esc_html__('Image', 'king-addons'),
                'type' => Controls_Manager::MEDIA,
                'default' => [
                    'url' => Utils::get_placeholder_image_src(),
                ],
                'condition' => [
                    'kng_pdc_media_type' => 'image',
                ],
            ]
        );

        $repeater->add_control(
            'kng_pdc_media_icon',
            [
                'label' => esc_html__('Icon', 'king-addons'),
                'type' => Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fas fa-layer-group',
                    'library' => 'solid',
                ],
                'condition' => [
                    'kng_pdc_media_type' => 'icon',
                ],
            ]
        );

        $repeater->add_responsive_control(
            'kng_pdc_media_width',
            [
                'label' => esc_html__('Media Width', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => ['min' => 40, 'max' => 320],
                    'em' => ['min' => 2, 'max' => 20],
                    'rem' => ['min' => 2, 'max' => 20],
                ],
                'default' => [
                    'size' => 120,
                    'unit' => 'px',
                ],
                'condition' => [
                    'kng_pdc_media_type!' => 'none',
                ],
            ]
        );

        $repeater->add_responsive_control(
            'kng_pdc_icon_size',
            [
                'label' => esc_html__('Icon Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => ['min' => 16, 'max' => 120],
                    'em' => ['min' => 1, 'max' => 8],
                    'rem' => ['min' => 1, 'max' => 8],
                ],
                'default' => [
                    'size' => 36,
                    'unit' => 'px',
                ],
                'condition' => [
                    'kng_pdc_media_type' => 'icon',
                ],
            ]
        );

        $repeater->add_control(
            'kng_pdc_media_position',
            [
                'label' => esc_html__('Media Alignment', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'left' => esc_html__('Left', 'king-addons'),
                    'center' => esc_html__('Center', 'king-addons'),
                    'right' => esc_html__('Right', 'king-addons'),
                ],
                'default' => 'left',
                'condition' => [
                    'kng_pdc_media_type!' => 'none',
                ],
            ]
        );

        $repeater->add_control(
            'kng_pdc_title',
            [
                'label' => esc_html__('Title', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Depth Focused', 'king-addons'),
                'label_block' => true,
            ]
        );

        $repeater->add_control(
            'kng_pdc_description',
            [
                'label' => esc_html__('Description', 'king-addons'),
                'type' => Controls_Manager::TEXTAREA,
                'default' => esc_html__('Layered motion elevates this card with subtle depth.', 'king-addons'),
                'rows' => 4,
            ]
        );

        $repeater->add_control(
            'kng_pdc_button_text',
            [
                'label' => esc_html__('Button Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Learn more', 'king-addons'),
                'label_block' => true,
            ]
        );

        $repeater->add_control(
            'kng_pdc_button_link',
            [
                'label' => esc_html__('Button Link', 'king-addons'),
                'type' => Controls_Manager::URL,
                'placeholder' => 'https://',
                'show_external' => true,
                'default' => [
                    'url' => '',
                    'is_external' => true,
                ],
                'label_block' => true,
            ]
        );

        $repeater->add_control(
            'kng_pdc_badge_text',
            [
                'label' => esc_html__('Badge Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Featured', 'king-addons'),
                'label_block' => true,
            ]
        );

        $repeater->add_control(
            'kng_pdc_badge_position',
            [
                'label' => esc_html__('Badge Position', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'left' => esc_html__('Top Left', 'king-addons'),
                    'right' => esc_html__('Top Right', 'king-addons'),
                ],
                'default' => 'left',
            ]
        );

        $this->add_control(
            'kng_pdc_cards',
            [
                'label' => esc_html__('Cards', 'king-addons'),
                'type' => Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'default' => [
                    [
                        'kng_pdc_title' => esc_html__('Premium Motion', 'king-addons'),
                        'kng_pdc_description' => esc_html__('Responsive depth and tilt for high-impact highlights.', 'king-addons'),
                        'kng_pdc_badge_text' => esc_html__('New', 'king-addons'),
                    ],
                    [
                        'kng_pdc_title' => esc_html__('Layered Clarity', 'king-addons'),
                        'kng_pdc_description' => esc_html__('Give key features visual separation with depth.', 'king-addons'),
                        'kng_pdc_badge_text' => esc_html__('Focus', 'king-addons'),
                    ],
                    [
                        'kng_pdc_title' => esc_html__('Smooth Response', 'king-addons'),
                        'kng_pdc_description' => esc_html__('Balanced motion keeps everything calm and premium.', 'king-addons'),
                        'kng_pdc_badge_text' => esc_html__('Pro', 'king-addons'),
                    ],
                ],
                'title_field' => '{{{ kng_pdc_title }}}',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register single card controls.
     *
     * @return void
     */
    protected function register_single_card_controls(): void
    {
        $this->start_controls_section(
            'kng_pdc_single_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Single Card', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
                'condition' => [
                    'kng_pdc_mode' => 'single',
                ],
            ]
        );

        $this->add_control(
            'kng_pdc_single_media_type',
            [
                'label' => esc_html__('Media Type', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'none' => esc_html__('None', 'king-addons'),
                    'image' => esc_html__('Image', 'king-addons'),
                    'icon' => esc_html__('Icon', 'king-addons'),
                ],
                'default' => 'image',
            ]
        );

        $this->add_control(
            'kng_pdc_single_media_image',
            [
                'label' => esc_html__('Image', 'king-addons'),
                'type' => Controls_Manager::MEDIA,
                'default' => [
                    'url' => Utils::get_placeholder_image_src(),
                ],
                'condition' => [
                    'kng_pdc_single_media_type' => 'image',
                ],
            ]
        );

        $this->add_control(
            'kng_pdc_single_media_icon',
            [
                'label' => esc_html__('Icon', 'king-addons'),
                'type' => Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fas fa-layer-group',
                    'library' => 'solid',
                ],
                'condition' => [
                    'kng_pdc_single_media_type' => 'icon',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_pdc_single_media_width',
            [
                'label' => esc_html__('Media Width', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => ['min' => 40, 'max' => 320],
                    'em' => ['min' => 2, 'max' => 20],
                    'rem' => ['min' => 2, 'max' => 20],
                ],
                'default' => [
                    'size' => 120,
                    'unit' => 'px',
                ],
                'condition' => [
                    'kng_pdc_single_media_type!' => 'none',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_pdc_single_icon_size',
            [
                'label' => esc_html__('Icon Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => ['min' => 16, 'max' => 120],
                    'em' => ['min' => 1, 'max' => 8],
                    'rem' => ['min' => 1, 'max' => 8],
                ],
                'default' => [
                    'size' => 36,
                    'unit' => 'px',
                ],
                'condition' => [
                    'kng_pdc_single_media_type' => 'icon',
                ],
            ]
        );

        $this->add_control(
            'kng_pdc_single_media_position',
            [
                'label' => esc_html__('Media Alignment', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'left' => esc_html__('Left', 'king-addons'),
                    'center' => esc_html__('Center', 'king-addons'),
                    'right' => esc_html__('Right', 'king-addons'),
                ],
                'default' => 'left',
                'condition' => [
                    'kng_pdc_single_media_type!' => 'none',
                ],
            ]
        );

        $this->add_control(
            'kng_pdc_single_title',
            [
                'label' => esc_html__('Title', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Depth Focused', 'king-addons'),
                'label_block' => true,
            ]
        );

        $this->add_control(
            'kng_pdc_single_description',
            [
                'label' => esc_html__('Description', 'king-addons'),
                'type' => Controls_Manager::TEXTAREA,
                'default' => esc_html__('Layered motion elevates this card with subtle depth.', 'king-addons'),
                'rows' => 4,
            ]
        );

        $this->add_control(
            'kng_pdc_single_button_text',
            [
                'label' => esc_html__('Button Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Learn more', 'king-addons'),
                'label_block' => true,
            ]
        );

        $this->add_control(
            'kng_pdc_single_button_link',
            [
                'label' => esc_html__('Button Link', 'king-addons'),
                'type' => Controls_Manager::URL,
                'placeholder' => 'https://',
                'show_external' => true,
                'default' => [
                    'url' => '',
                    'is_external' => true,
                ],
                'label_block' => true,
            ]
        );

        $this->add_control(
            'kng_pdc_single_badge_text',
            [
                'label' => esc_html__('Badge Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Featured', 'king-addons'),
                'label_block' => true,
            ]
        );

        $this->add_control(
            'kng_pdc_single_badge_position',
            [
                'label' => esc_html__('Badge Position', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'left' => esc_html__('Top Left', 'king-addons'),
                    'right' => esc_html__('Top Right', 'king-addons'),
                ],
                'default' => 'left',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register interaction controls.
     *
     * @return void
     */
    protected function register_interaction_controls(): void
    {
        $this->start_controls_section(
            'kng_pdc_interaction_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Interaction', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_pdc_enable_tilt',
            [
                'label' => esc_html__('Enable Tilt', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_pdc_enable_parallax',
            [
                'label' => esc_html__('Enable Parallax', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_pdc_trigger',
            [
                'label' => esc_html__('Trigger', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'hover' => esc_html__('On Hover', 'king-addons'),
                    'always' => esc_html__('Always Active', 'king-addons'),
                ],
                'default' => 'hover',
            ]
        );

        $this->add_control(
            'kng_pdc_intensity',
            [
                'label' => esc_html__('Intensity', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => [],
                'range' => [
                    '' => ['min' => 0, 'max' => 100],
                ],
                'default' => [
                    'size' => 60,
                ],
            ]
        );

        $this->add_control(
            'kng_pdc_perspective',
            [
                'label' => esc_html__('Perspective (px)', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 200, 'max' => 2000],
                ],
                'default' => [
                    'size' => 900,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-parallax-depth-cards' => '--ka-pdc-perspective: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_pdc_max_tilt',
            [
                'label' => esc_html__('Max Tilt (deg)', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => [],
                'range' => [
                    '' => ['min' => 0, 'max' => 30],
                ],
                'default' => [
                    'size' => 12,
                ],
            ]
        );

        $this->add_control(
            'kng_pdc_depth_strength',
            [
                'label' => esc_html__('Parallax Depth (px)', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => [],
                'range' => [
                    '' => ['min' => 0, 'max' => 60],
                ],
                'default' => [
                    'size' => 18,
                ],
            ]
        );

        $this->add_control(
            'kng_pdc_depth_bg',
            [
                'label' => esc_html__('Background Layer Depth', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => [],
                'range' => [
                    '' => ['min' => -1, 'max' => 1, 'step' => 0.05],
                ],
                'default' => [
                    'size' => 0.2,
                ],
            ]
        );

        $this->add_control(
            'kng_pdc_depth_media',
            [
                'label' => esc_html__('Media Layer Depth', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => [],
                'range' => [
                    '' => ['min' => -1, 'max' => 1, 'step' => 0.05],
                ],
                'default' => [
                    'size' => 0.35,
                ],
            ]
        );

        $this->add_control(
            'kng_pdc_depth_content',
            [
                'label' => esc_html__('Content Layer Depth', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => [],
                'range' => [
                    '' => ['min' => -1, 'max' => 1, 'step' => 0.05],
                ],
                'default' => [
                    'size' => 0.5,
                ],
            ]
        );

        $this->add_control(
            'kng_pdc_smoothing',
            [
                'label' => esc_html__('Smoothing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => [],
                'range' => [
                    '' => ['min' => 0.02, 'max' => 0.4, 'step' => 0.01],
                ],
                'default' => [
                    'size' => 0.12,
                ],
            ]
        );

        $this->add_control(
            'kng_pdc_reset_duration',
            [
                'label' => esc_html__('Hover Out Duration (ms)', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => [],
                'range' => [
                    '' => ['min' => 100, 'max' => 1200, 'step' => 50],
                ],
                'default' => [
                    'size' => 350,
                ],
            ]
        );

        $this->add_control(
            'kng_pdc_reduce_motion',
            [
                'label' => esc_html__('Respect Reduced Motion', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_pdc_disable_touch',
            [
                'label' => esc_html__('Disable on Touch Devices', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register style controls.
     *
     * @return void
     */
    protected function register_style_controls(): void
    {
        $this->register_style_card_controls();
        $this->register_style_typography_controls();
        $this->register_style_badge_controls();
        $this->register_style_button_controls();
        $this->register_style_media_controls();
        $this->register_style_layer_controls();
    }

    /**
     * Card base style controls.
     *
     * @return void
     */
    protected function register_style_card_controls(): void
    {
        $this->start_controls_section(
            'kng_pdc_style_card',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Card', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name' => 'kng_pdc_card_background',
                'selector' => '{{WRAPPER}} .king-addons-parallax-depth-cards__card',
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_pdc_card_border',
                'selector' => '{{WRAPPER}} .king-addons-parallax-depth-cards__card',
            ]
        );

        $this->add_responsive_control(
            'kng_pdc_card_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-parallax-depth-cards__card' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_pdc_card_shadow',
                'selector' => '{{WRAPPER}} .king-addons-parallax-depth-cards__card',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_pdc_card_shadow_hover',
                'selector' => '{{WRAPPER}} .king-addons-parallax-depth-cards__card:hover, {{WRAPPER}} .king-addons-parallax-depth-cards__card:focus-within',
            ]
        );

        $this->add_control(
            'kng_pdc_card_hover_bg',
            [
                'label' => esc_html__('Hover Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-parallax-depth-cards__card:hover' => 'background-color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-parallax-depth-cards__card:focus-within' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_pdc_card_hover_border',
            [
                'label' => esc_html__('Hover Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-parallax-depth-cards__card:hover' => 'border-color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-parallax-depth-cards__card:focus-within' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_pdc_card_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', 'rem'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-parallax-depth-cards__card-inner' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_pdc_hover_transform_heading',
            [
                'label' => esc_html__('Hover Transform', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'kng_pdc_hover_scale',
            [
                'label' => esc_html__('Hover Scale', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => ['min' => 0.9, 'max' => 1.2, 'step' => 0.01],
                ],
                'default' => [
                    'size' => 1,
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-parallax-depth-cards__card:hover' => '--ka-pdc-hover-scale: {{SIZE}};',
                    '{{WRAPPER}} .king-addons-parallax-depth-cards__card:focus-within' => '--ka-pdc-hover-scale: {{SIZE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_pdc_hover_lift',
            [
                'label' => esc_html__('Hover Lift (Y)', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => -20, 'max' => 0],
                ],
                'default' => [
                    'size' => 0,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-parallax-depth-cards__card:hover' => '--ka-pdc-hover-lift: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .king-addons-parallax-depth-cards__card:focus-within' => '--ka-pdc-hover-lift: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Typography style controls.
     *
     * @return void
     */
    protected function register_style_typography_controls(): void
    {
        $this->start_controls_section(
            'kng_pdc_style_typography',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Typography', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_pdc_title_typography',
                'selector' => '{{WRAPPER}} .king-addons-parallax-depth-cards__title',
            ]
        );

        $this->add_control(
            'kng_pdc_title_color',
            [
                'label' => esc_html__('Title Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-parallax-depth-cards__title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_pdc_description_typography',
                'selector' => '{{WRAPPER}} .king-addons-parallax-depth-cards__description',
            ]
        );

        $this->add_control(
            'kng_pdc_description_color',
            [
                'label' => esc_html__('Description Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-parallax-depth-cards__description' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_pdc_content_gap',
            [
                'label' => esc_html__('Content Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 40],
                    'em' => ['min' => 0, 'max' => 3],
                    'rem' => ['min' => 0, 'max' => 3],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-parallax-depth-cards' => '--ka-pdc-content-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_pdc_media_spacing',
            [
                'label' => esc_html__('Media Bottom Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 60],
                    'em' => ['min' => 0, 'max' => 4],
                    'rem' => ['min' => 0, 'max' => 4],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-parallax-depth-cards__layer--media' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Badge style controls.
     *
     * @return void
     */
    protected function register_style_badge_controls(): void
    {
        $this->start_controls_section(
            'kng_pdc_style_badge',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Badge', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_pdc_badge_typography',
                'selector' => '{{WRAPPER}} .king-addons-parallax-depth-cards__badge',
            ]
        );

        $this->add_control(
            'kng_pdc_badge_text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-parallax-depth-cards' => '--ka-pdc-badge-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_pdc_badge_bg',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-parallax-depth-cards' => '--ka-pdc-badge-bg: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_pdc_badge_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-parallax-depth-cards__badge' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_pdc_badge_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', 'rem'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-parallax-depth-cards__badge' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Button style controls.
     *
     * @return void
     */
    protected function register_style_button_controls(): void
    {
        $this->start_controls_section(
            'kng_pdc_style_button',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Button', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_pdc_button_typography',
                'selector' => '{{WRAPPER}} .king-addons-parallax-depth-cards__button',
            ]
        );

        $this->start_controls_tabs('kng_pdc_button_tabs');

        $this->start_controls_tab(
            'kng_pdc_button_tab_normal',
            [
                'label' => esc_html__('Normal', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_pdc_button_text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-parallax-depth-cards' => '--ka-pdc-button-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_pdc_button_bg',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-parallax-depth-cards' => '--ka-pdc-button-bg: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_pdc_button_border',
            [
                'label' => esc_html__('Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-parallax-depth-cards' => '--ka-pdc-button-border: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'kng_pdc_button_tab_hover',
            [
                'label' => esc_html__('Hover', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_pdc_button_text_color_hover',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-parallax-depth-cards' => '--ka-pdc-button-color-hover: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_pdc_button_bg_hover',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-parallax-depth-cards' => '--ka-pdc-button-bg-hover: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_pdc_button_border_hover',
            [
                'label' => esc_html__('Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-parallax-depth-cards' => '--ka-pdc-button-border-hover: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->add_responsive_control(
            'kng_pdc_button_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-parallax-depth-cards__button' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_pdc_button_border_width',
            [
                'label' => esc_html__('Border Width', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 5],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-parallax-depth-cards__button' => 'border-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_pdc_button_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', 'rem'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-parallax-depth-cards__button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Media style controls.
     *
     * @return void
     */
    protected function register_style_media_controls(): void
    {
        $this->start_controls_section(
            'kng_pdc_style_media',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Media', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Image_Size::get_type(),
            [
                'name' => 'kng_pdc_media_image_size',
                'default' => 'medium',
            ]
        );

        $this->add_control(
            'kng_pdc_icon_color',
            [
                'label' => esc_html__('Icon Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-parallax-depth-cards' => '--ka-pdc-icon-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_pdc_icon_bg',
            [
                'label' => esc_html__('Icon Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-parallax-depth-cards__icon' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_pdc_icon_padding',
            [
                'label' => esc_html__('Icon Padding', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 40],
                    'em' => ['min' => 0, 'max' => 3],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-parallax-depth-cards__icon' => 'padding: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_pdc_icon_border_radius',
            [
                'label' => esc_html__('Icon Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-parallax-depth-cards__icon' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_pdc_media_radius',
            [
                'label' => esc_html__('Image Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-parallax-depth-cards' => '--ka-pdc-media-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Background layer style controls.
     *
     * @return void
     */
    protected function register_style_layer_controls(): void
    {
        $this->start_controls_section(
            'kng_pdc_style_layer',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Background Layer', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name' => 'kng_pdc_layer_background',
                'selector' => '{{WRAPPER}} .king-addons-parallax-depth-cards__layer--bg',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render a card.
     *
     * @param array<string, mixed> $settings Widget settings.
     * @param array<string, mixed> $card     Card data.
     * @param array<string, float> $depths   Depth settings.
     *
     * @return void
     */
    protected function render_card(array $settings, array $card, array $depths): void
    {
        $title = trim((string) ($card['kng_pdc_title'] ?? ''));
        $description = trim((string) ($card['kng_pdc_description'] ?? ''));
        $badge_text = trim((string) ($card['kng_pdc_badge_text'] ?? ''));
        $badge_position = $card['kng_pdc_badge_position'] ?? 'left';
        $title_tag = $this->sanitize_html_tag($settings['kng_pdc_title_tag'] ?? 'h3');

        $button_text = trim((string) ($card['kng_pdc_button_text'] ?? ''));
        $button_link = is_array($card['kng_pdc_button_link'] ?? null) ? $card['kng_pdc_button_link'] : [];
        $button_url = $button_link['url'] ?? '';

        $media_type = $card['kng_pdc_media_type'] ?? 'none';
        $media_position = $card['kng_pdc_media_position'] ?? 'left';

        $media_width_value = $this->get_slider_css_value($card['kng_pdc_media_width'] ?? null, 'px');
        $icon_size_value = $this->get_slider_css_value($card['kng_pdc_icon_size'] ?? null, 'px');

        $media_styles = [];
        if ($media_width_value !== '') {
            $media_styles[] = '--ka-pdc-media-width: ' . $media_width_value . ';';
        }
        if ($icon_size_value !== '') {
            $media_styles[] = '--ka-pdc-icon-size: ' . $icon_size_value . ';';
        }
        $media_style_attr = !empty($media_styles) ? ' style="' . esc_attr(implode(' ', $media_styles)) . '"' : '';

        $card_classes = ['king-addons-parallax-depth-cards__card'];
        if (!empty($card['_id'])) {
            $card_classes[] = 'elementor-repeater-item-' . $card['_id'];
        }

        $badge_class = 'king-addons-parallax-depth-cards__badge is-' . sanitize_html_class($badge_position);
        $media_class = 'king-addons-parallax-depth-cards__media is-align-' . sanitize_html_class($media_position);

        ?>
        <article class="<?php echo esc_attr(implode(' ', $card_classes)); ?>">
            <div class="king-addons-parallax-depth-cards__card-inner">
                <div class="king-addons-parallax-depth-cards__layer king-addons-parallax-depth-cards__layer--bg" data-depth="<?php echo esc_attr((string) $depths['bg']); ?>"></div>

                <?php if ($media_type !== 'none') : ?>
                    <div class="king-addons-parallax-depth-cards__layer king-addons-parallax-depth-cards__layer--media" data-depth="<?php echo esc_attr((string) $depths['media']); ?>">
                        <div class="<?php echo esc_attr($media_class); ?>"<?php echo $media_style_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
                            <?php if ($media_type === 'image' && !empty($card['kng_pdc_media_image']['url'])) : ?>
                                <?php
                                $image_settings = $card;
                                if (isset($settings['kng_pdc_media_image_size_size'])) {
                                    $image_settings['kng_pdc_media_image_size_size'] = $settings['kng_pdc_media_image_size_size'];
                                }
                                if (isset($settings['kng_pdc_media_image_size_custom_dimension'])) {
                                    $image_settings['kng_pdc_media_image_size_custom_dimension'] = $settings['kng_pdc_media_image_size_custom_dimension'];
                                }
                                $image_html = Group_Control_Image_Size::get_attachment_image_html($image_settings, 'kng_pdc_media_image_size', 'kng_pdc_media_image');
                                echo wp_kses_post($image_html);
                                ?>
                            <?php elseif ($media_type === 'icon' && !empty($card['kng_pdc_media_icon']['value'])) : ?>
                                <span class="king-addons-parallax-depth-cards__icon" aria-hidden="true">
                                    <?php Icons_Manager::render_icon($card['kng_pdc_media_icon'], ['aria-hidden' => 'true']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="king-addons-parallax-depth-cards__layer king-addons-parallax-depth-cards__layer--content" data-depth="<?php echo esc_attr((string) $depths['content']); ?>">
                    <div class="king-addons-parallax-depth-cards__content">
                        <?php if ($badge_text !== '') : ?>
                            <span class="<?php echo esc_attr($badge_class); ?>"><?php echo esc_html($badge_text); ?></span>
                        <?php endif; ?>

                        <?php if ($title !== '') : ?>
                            <<?php echo esc_html($title_tag); ?> class="king-addons-parallax-depth-cards__title"><?php echo esc_html($title); ?></<?php echo esc_html($title_tag); ?>>
                        <?php endif; ?>

                        <?php if ($description !== '') : ?>
                            <p class="king-addons-parallax-depth-cards__description"><?php echo esc_html($description); ?></p>
                        <?php endif; ?>

                        <?php if ($button_text !== '') : ?>
                            <?php if ($button_url !== '') : ?>
                                <a class="king-addons-parallax-depth-cards__button"<?php echo $this->build_button_attributes($button_link); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
                                    <?php echo esc_html($button_text); ?>
                                </a>
                            <?php else : ?>
                                <button class="king-addons-parallax-depth-cards__button" type="button">
                                    <?php echo esc_html($button_text); ?>
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </article>
        <?php
    }

    /**
     * Map single card settings to card data.
     *
     * @param array<string, mixed> $settings Widget settings.
     *
     * @return array<string, mixed>
     */
    protected function get_single_card_data(array $settings): array
    {
        return [
            'kng_pdc_media_type' => $settings['kng_pdc_single_media_type'] ?? 'none',
            'kng_pdc_media_image' => $settings['kng_pdc_single_media_image'] ?? [],
            'kng_pdc_media_icon' => $settings['kng_pdc_single_media_icon'] ?? [],
            'kng_pdc_media_width' => $settings['kng_pdc_single_media_width'] ?? [],
            'kng_pdc_icon_size' => $settings['kng_pdc_single_icon_size'] ?? [],
            'kng_pdc_media_position' => $settings['kng_pdc_single_media_position'] ?? 'left',
            'kng_pdc_title' => $settings['kng_pdc_single_title'] ?? '',
            'kng_pdc_description' => $settings['kng_pdc_single_description'] ?? '',
            'kng_pdc_button_text' => $settings['kng_pdc_single_button_text'] ?? '',
            'kng_pdc_button_link' => $settings['kng_pdc_single_button_link'] ?? [],
            'kng_pdc_badge_text' => $settings['kng_pdc_single_badge_text'] ?? '',
            'kng_pdc_badge_position' => $settings['kng_pdc_single_badge_position'] ?? 'left',
        ];
    }

    /**
     * Build button attributes.
     *
     * @param array<string, mixed> $link Button link data.
     *
     * @return string
     */
    protected function build_button_attributes(array $link): string
    {
        if (empty($link['url'])) {
            return '';
        }

        $attributes = ' href="' . esc_url($link['url']) . '"';
        $rels = [];

        if (!empty($link['is_external'])) {
            $attributes .= ' target="_blank"';
            $rels[] = 'noopener';
            $rels[] = 'noreferrer';
        }

        if (!empty($link['nofollow'])) {
            $rels[] = 'nofollow';
        }

        if (!empty($rels)) {
            $attributes .= ' rel="' . esc_attr(implode(' ', array_unique($rels))) . '"';
        }

        return $attributes;
    }

    /**
     * Normalize slider values to numbers.
     *
     * @param mixed $value   Slider value.
     * @param float $default Default value.
     *
     * @return float
     */
    protected function get_control_number($value, float $default): float
    {
        if (is_array($value) && isset($value['size'])) {
            return is_numeric($value['size']) ? (float) $value['size'] : $default;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        return $default;
    }

    /**
     * Build a slider value with unit for inline CSS.
     *
     * @param mixed  $value       Slider value.
     * @param string $default_unit Fallback unit.
     *
     * @return string
     */
    protected function get_slider_css_value($value, string $default_unit = 'px'): string
    {
        if (is_array($value) && isset($value['size']) && $value['size'] !== '') {
            $unit = isset($value['unit']) && $value['unit'] !== '' ? $value['unit'] : $default_unit;
            if (is_numeric($value['size'])) {
                return $value['size'] . $unit;
            }
        }

        if (is_numeric($value)) {
            return $value . $default_unit;
        }

        return '';
    }

    /**
     * Render HTML attributes from array.
     *
     * @param array<string, string> $attributes Attributes list.
     *
     * @return string
     */
    protected function render_attribute_string(array $attributes): string
    {
        $pairs = [];
        foreach ($attributes as $key => $value) {
            if ($value === '' || $value === null) {
                continue;
            }
            $pairs[] = sprintf('%s="%s"', $key, esc_attr($value));
        }

        return implode(' ', $pairs);
    }

    /**
     * Sanitize HTML tag.
     *
     * @param string $tag HTML tag name.
     *
     * @return string
     */
    protected function sanitize_html_tag(string $tag): string
    {
        $allowed = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'span', 'p'];
        $tag = strtolower(trim($tag));
        return in_array($tag, $allowed, true) ? $tag : 'h3';
    }

    /**
     * Register Pro notice controls.
     *
     * @return void
     */
    protected function register_pro_notice_controls(): void
    {
        if (!king_addons_can_use_pro()) {
            Core::renderProFeaturesSection($this, '', Controls_Manager::RAW_HTML, 'parallax-depth-cards', [
                'Advanced layer mapping with per-layer controls',
                'Dynamic shadow and focus edge highlights',
                'Adaptive performance and device toggles',
            ]);
        }
    }
}
