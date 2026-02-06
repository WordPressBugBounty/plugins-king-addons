<?php
/**
 * Image Marquee Widget (Free).
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Utils;
use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders the Image Marquee widget.
 */
class Image_Marquee extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-image-marquee';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Image Marquee', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-image-marquee';
    }

    /**
     * Script dependencies.
     *
     * @return array<int, string>
     */
    public function get_script_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-image-marquee-script',
        ];
    }

    /**
     * Style dependencies.
     *
     * @return array<int, string>
     */
    public function get_style_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-image-marquee-style',
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
        return ['marquee', 'logo', 'slider', 'scroll', 'image'];
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
        $this->register_content_images_controls();
        $this->register_marquee_settings_controls();
        $this->register_style_item_controls();
        $this->register_style_image_controls();
        $this->register_style_overlay_controls();
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
        $items = $settings['kng_items'] ?? [];

        if (empty($items)) {
            return;
        }

        $this->render_output($settings, $items);
    }

    /**
     * Register images repeater controls.
     *
     * @return void
     */
    protected function register_content_images_controls(): void
    {
        $this->start_controls_section(
            'kng_images_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Images', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $repeater = new Repeater();

        $repeater->add_control(
            'item_image',
            [
                'label' => esc_html__('Image', 'king-addons'),
                'type' => Controls_Manager::MEDIA,
                'default' => [
                    'url' => Utils::get_placeholder_image_src(),
                ],
            ]
        );

        $repeater->add_control(
            'item_title',
            [
                'label' => esc_html__('Title', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'label_block' => true,
                'default' => esc_html__('Brand Name', 'king-addons'),
            ]
        );

        $repeater->add_control(
            'item_alt',
            [
                'label' => esc_html__('Alt Text (optional)', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'label_block' => true,
            ]
        );

        $repeater->add_control(
            'item_hover_label',
            [
                'label' => esc_html__('Hover Label', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'label_block' => true,
            ]
        );

        $repeater->add_control(
            'item_link',
            [
                'label' => esc_html__('Link', 'king-addons'),
                'type' => Controls_Manager::URL,
                'placeholder' => esc_html__('https://example.com', 'king-addons'),
                'label_block' => true,
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Image_Size::get_type(),
            [
                'name' => 'item_image',
                'default' => 'medium',
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'kng_items',
            [
                'label' => esc_html__('Items', 'king-addons'),
                'type' => Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'default' => $this->get_default_items(),
                'title_field' => '{{{ item_title }}}',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register marquee behavior controls.
     *
     * @return void
     */
    protected function register_marquee_settings_controls(): void
    {
        $this->start_controls_section(
            'kng_marquee_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Marquee Settings', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_direction',
            [
                'label' => esc_html__('Direction', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'right_to_left',
                'options' => [
                    'left_to_right' => esc_html__('Left to Right', 'king-addons'),
                    'right_to_left' => esc_html__('Right to Left', 'king-addons'),
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_speed',
            [
                'label' => esc_html__('Speed', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 1,
                        'max' => 200,
                    ],
                ],
                'default' => [
                    'size' => 50,
                    'unit' => 'px',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_gap',
            [
                'label' => esc_html__('Gap Between Items', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'size' => 30,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-image-marquee' => '--kng-image-marquee-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_pause_on_hover',
            [
                'label' => esc_html__('Pause on Hover', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_pause_on_touch',
            [
                'label' => esc_html__('Pause on Touch (Mobile)', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_duplicate_items',
            [
                'label' => esc_html__('Duplicate Items for Smooth Loop', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register style controls for items.
     *
     * @return void
     */
    protected function register_style_item_controls(): void
    {
        $this->start_controls_section(
            'kng_style_items_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Items', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'kng_container_height',
            [
                'label' => esc_html__('Container Height', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 600,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-image-marquee' => 'min-height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_item_padding',
            [
                'label' => esc_html__('Item Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-image-marquee__item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_item_border',
                'selector' => '{{WRAPPER}} .king-addons-image-marquee__item',
            ]
        );

        $this->add_control(
            'kng_item_radius',
            [
                'label' => esc_html__('Item Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-image-marquee__item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_item_shadow',
                'selector' => '{{WRAPPER}} .king-addons-image-marquee__item',
            ]
        );

        $this->add_control(
            'kng_container_background',
            [
                'label' => esc_html__('Container Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-image-marquee' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register style controls for images.
     *
     * @return void
     */
    protected function register_style_image_controls(): void
    {
        $this->start_controls_section(
            'kng_style_image_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Images', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'kng_image_width',
            [
                'label' => esc_html__('Image Width', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => ['min' => 20, 'max' => 600],
                    '%' => ['min' => 5, 'max' => 100],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-image-marquee__img' => 'width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_image_height',
            [
                'label' => esc_html__('Image Height', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 20, 'max' => 400],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-image-marquee__img' => 'height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_image_object_fit',
            [
                'label' => esc_html__('Image Fit', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'cover',
                'options' => [
                    'cover' => esc_html__('Cover', 'king-addons'),
                    'contain' => esc_html__('Contain', 'king-addons'),
                    'fill' => esc_html__('Fill', 'king-addons'),
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-image-marquee__img' => 'object-fit: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_image_radius',
            [
                'label' => esc_html__('Image Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-image-marquee__img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'separator' => 'before',
            ]
        );

        $this->start_controls_tabs('kng_image_tabs');

        $this->start_controls_tab(
            'kng_image_tab_normal',
            [
                'label' => esc_html__('Normal', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_image_opacity',
            [
                'label' => esc_html__('Opacity', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['%'],
                'range' => [
                    '%' => ['min' => 10, 'max' => 100],
                ],
                'default' => [
                    'size' => 100,
                    'unit' => '%',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-image-marquee__img' => 'opacity: {{SIZE}}%;',
                ],
            ]
        );

        $this->add_control(
            'kng_image_filter_normal',
            [
                'label' => esc_html__('Filter', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'none',
                'options' => [
                    'none' => esc_html__('None', 'king-addons'),
                    'grayscale(100%)' => esc_html__('Grayscale', 'king-addons'),
                    'blur(2px)' => esc_html__('Blur', 'king-addons'),
                    'brightness(1.1)' => esc_html__('Brightness', 'king-addons'),
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-image-marquee__img' => 'filter: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'kng_image_tab_hover',
            [
                'label' => esc_html__('Hover', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_image_opacity_hover',
            [
                'label' => esc_html__('Opacity', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['%'],
                'range' => [
                    '%' => ['min' => 10, 'max' => 100],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-image-marquee__item:hover .king-addons-image-marquee__img' => 'opacity: {{SIZE}}%;',
                ],
            ]
        );

        $this->add_control(
            'kng_image_filter_hover',
            [
                'label' => esc_html__('Filter', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'none',
                'options' => [
                    'none' => esc_html__('None', 'king-addons'),
                    'grayscale(100%)' => esc_html__('Grayscale', 'king-addons'),
                    'blur(2px)' => esc_html__('Blur', 'king-addons'),
                    'brightness(1.1)' => esc_html__('Brightness', 'king-addons'),
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-image-marquee__item:hover .king-addons-image-marquee__img' => 'filter: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->end_controls_section();
    }

    /**
     * Register overlay and hover label controls.
     *
     * @return void
     */
    protected function register_style_overlay_controls(): void
    {
        $this->start_controls_section(
            'kng_style_overlay_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Overlay & Hover', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->start_controls_tabs('kng_overlay_tabs');

        $this->start_controls_tab(
            'kng_overlay_tab_normal',
            [
                'label' => esc_html__('Normal', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_overlay_color',
            [
                'label' => esc_html__('Overlay Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-image-marquee__overlay' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'kng_overlay_tab_hover',
            [
                'label' => esc_html__('Hover', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_overlay_color_hover',
            [
                'label' => esc_html__('Overlay Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-image-marquee__item:hover .king-addons-image-marquee__overlay' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_hover_scale',
            [
                'label' => esc_html__('Hover Scale', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 1,
                        'max' => 1.2,
                        'step' => 0.01,
                    ],
                ],
                'default' => [
                    'size' => 1.02,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-image-marquee__item:hover .king-addons-image-marquee__img' => 'transform: scale({{SIZE}});',
                ],
            ]
        );

        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_hover_label_typography',
                'selector' => '{{WRAPPER}} .king-addons-image-marquee__hover-label',
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'kng_hover_label_color',
            [
                'label' => esc_html__('Hover Label Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-image-marquee__hover-label' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render marquee output.
     *
     * @param array<string, mixed>   $settings Widget settings.
     * @param array<int, array<mixed>> $items    Repeater items.
     *
     * @return void
     */
    protected function render_output(array $settings, array $items): void
    {
        $duplicate = ($settings['kng_duplicate_items'] ?? 'yes') === 'yes';
        $wrapper_classes = ['king-addons-image-marquee'];
        $track_attributes = $this->get_data_attributes($settings);

        ?>
        <div class="<?php echo esc_attr(implode(' ', $wrapper_classes)); ?>">
            <?php $this->render_track($items, $track_attributes, $duplicate); ?>
        </div>
        <?php
    }

    /**
     * Render track markup with items.
     *
     * @param array<int, array<string, mixed>> $items        Items.
     * @param string                           $attributes   Track data attributes.
     * @param bool                             $duplicate    Whether to duplicate list.
     *
     * @return void
     */
    protected function render_track(array $items, string $attributes, bool $duplicate): void
    {
        ?>
        <div class="king-addons-image-marquee__track" <?php echo $attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
            <div class="king-addons-image-marquee__list">
                <?php foreach ($items as $item) : ?>
                    <?php $this->render_item($item); ?>
                <?php endforeach; ?>
            </div>
            <?php if ($duplicate) : ?>
                <div class="king-addons-image-marquee__list is-duplicate">
                    <?php foreach ($items as $item) : ?>
                        <?php $this->render_item($item); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render single item.
     *
     * @param array<string, mixed> $item Repeater item.
     *
     * @return void
     */
    protected function render_item(array $item): void
    {
        $image_html = $this->get_item_image_html($item);
        if (empty($image_html)) {
            return;
        }

        $hover_label = $item['item_hover_label'] ?? '';
        $link = $item['item_link'] ?? [];
        $link_attrs = $this->get_link_attributes($link);

        $item_open = '<div class="king-addons-image-marquee__item">';
        $item_close = '</div>';

        if (!empty($link_attrs)) {
            $item_open = '<a class="king-addons-image-marquee__item" ' . $link_attrs . '>';
            $item_close = '</a>';
        }

        echo $item_open; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        ?>
            <span class="king-addons-image-marquee__media">
                <?php echo $image_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <span class="king-addons-image-marquee__overlay"></span>
                <?php if (!empty($hover_label)) : ?>
                    <span class="king-addons-image-marquee__hover-label"><?php echo esc_html($hover_label); ?></span>
                <?php endif; ?>
            </span>
        <?php
        echo $item_close; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    /**
     * Build data attributes for JS (applied to track).
     *
     * @param array<string, mixed> $settings Widget settings.
     *
     * @return string
     */
    protected function get_data_attributes(array $settings): string
    {
        return $this->get_data_attributes_base($settings);
    }

    /**
     * Base data attributes builder for JS.
     *
     * This method exists to allow the Pro version to extend behavior without
     * calling `parent::` methods.
     *
     * @param array<string, mixed> $settings Widget settings.
     *
     * @return string
     */
    protected function get_data_attributes_base(array $settings): string
    {
        $speed = $settings['kng_speed']['size'] ?? 50;
        $direction = $settings['kng_direction'] ?? 'right_to_left';
        $pause_hover = $settings['kng_pause_on_hover'] ?? 'yes';
        $pause_touch = $settings['kng_pause_on_touch'] ?? 'yes';
        $duplicate = $settings['kng_duplicate_items'] ?? 'yes';
        $orientation = $settings['kng_orientation'] ?? 'horizontal';
        $reverse_hover = $settings['kng_reverse_on_hover'] ?? 'no';

        $attributes = [
            'data-direction' => esc_attr($direction),
            'data-speed' => esc_attr((string) $speed),
            'data-pause-hover' => esc_attr($pause_hover),
            'data-pause-touch' => esc_attr($pause_touch),
            'data-duplicate' => esc_attr($duplicate),
            'data-orientation' => esc_attr($orientation),
            'data-reverse-hover' => esc_attr($reverse_hover),
        ];

        $prepared = [];
        foreach ($attributes as $key => $value) {
            $prepared[] = $key . '="' . $value . '"';
        }

        return implode(' ', $prepared);
    }

    /**
     * Render image HTML with alt/title fallbacks.
     *
     * @param array<string, mixed> $item Repeater item.
     *
     * @return string
     */
    protected function get_item_image_html(array $item): string
    {
        $image = $item['item_image'] ?? [];
        $alt = $this->get_item_alt($item);
        $title = $item['item_title'] ?? '';

        if (!empty($image['id'])) {
            $image_src = Group_Control_Image_Size::get_attachment_image_src($image['id'], 'item_image', $item);

            if (!empty($image_src)) {
                return '<img class="king-addons-image-marquee__img" src="' . esc_url($image_src) . '" alt="' . esc_attr($alt) . '" title="' . esc_attr($title) . '" />';
            }
        }

        if (!empty($image['url'])) {
            return '<img class="king-addons-image-marquee__img" src="' . esc_url($image['url']) . '" alt="' . esc_attr($alt) . '" title="' . esc_attr($title) . '" />';
        }

        return '';
    }

    /**
     * Get computed alt text.
     *
     * @param array<string, mixed> $item Repeater item.
     *
     * @return string
     */
    protected function get_item_alt(array $item): string
    {
        $alt = $item['item_alt'] ?? '';
        if (!empty($alt)) {
            return $alt;
        }

        $title = $item['item_title'] ?? '';
        if (!empty($title)) {
            return $title;
        }

        $image = $item['item_image']['url'] ?? '';
        if (!empty($image)) {
            return wp_basename((string) $image);
        }

        return '';
    }

    /**
     * Build link attributes.
     *
     * @param array<string, mixed> $link Link settings.
     *
     * @return string
     */
    protected function get_link_attributes(array $link): string
    {
        if (empty($link['url'])) {
            return '';
        }

        $attributes = [
            'href' => esc_url($link['url']),
            'class' => 'king-addons-image-marquee__link',
        ];

        if (!empty($link['is_external'])) {
            $attributes['target'] = '_blank';
            $attributes['rel'] = 'noopener noreferrer';
        }

        if (!empty($link['nofollow'])) {
            $attributes['rel'] = isset($attributes['rel']) ? $attributes['rel'] . ' nofollow' : 'nofollow';
        }

        $output = [];
        foreach ($attributes as $key => $value) {
            $output[] = esc_attr($key) . '="' . esc_attr((string) $value) . '"';
        }

        return implode(' ', $output);
    }

    /**
     * Default items for the repeater.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function get_default_items(): array
    {
        $defaults = [];
        $placeholder = Utils::get_placeholder_image_src();

        $labels = [
            esc_html__('Modern Brand', 'king-addons'),
            esc_html__('Creative Studio', 'king-addons'),
            esc_html__('Product Line', 'king-addons'),
            esc_html__('Trusted Partner', 'king-addons'),
            esc_html__('Design Agency', 'king-addons'),
        ];

        foreach ($labels as $label) {
            $defaults[] = [
                'item_title' => $label,
                'item_image' => ['url' => $placeholder],
                'item_hover_label' => esc_html__('View', 'king-addons'),
            ];
        }

        return $defaults;
    }

    /**
     * Render pro notice.
     *
     * @return void
     */
    public function register_pro_notice_controls(): void
    {
        if (!king_addons_freemius()->can_use_premium_code__premium_only()) {
            Core::renderProFeaturesSection($this, '', Controls_Manager::RAW_HTML, 'image-marquee', [
                'Vertical marquee directions',
                'Multiple rows with alternating directions',
                'Advanced hover overlays and masks',
                'Per-row speed and reverse-on-hover',
            ]);
        }
    }
}







