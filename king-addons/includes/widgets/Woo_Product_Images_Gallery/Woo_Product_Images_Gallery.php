<?php
/**
 * Woo Product Images Gallery widget.
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Image_Size;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Displays product main image and gallery.
 */
class Woo_Product_Images_Gallery extends Abstract_Single_Widget
{
    public function get_name(): string
    {
        return 'woo_product_images_gallery';
    }

    public function get_title(): string
    {
        return esc_html__('Product Images Gallery', 'king-addons');
    }

    public function get_icon(): string
    {
        return 'eicon-gallery-grid';
    }

    public function get_categories(): array
    {
        return ['king-addons-woo-builder'];
    }

    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-product-images-gallery-style'];
    }

    public function get_script_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-product-images-gallery-script'];
    }

    protected function register_controls(): void
    {
        $this->start_controls_section(
            'section_layout',
            [
                'label' => esc_html__('Layout', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'layout',
            [
                'label' => esc_html__('Layout', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'slider' => esc_html__('Slider', 'king-addons'),
                    'grid' => esc_html__('Grid', 'king-addons'),
                    'thumbs_left' => sprintf(__('Slider + thumbs left %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                    'thumbs_right' => sprintf(__('Slider + thumbs right %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                    'masonry' => sprintf(__('Masonry %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                ],
                'default' => 'slider',
            ]
        );

        $this->add_control(
            'show_arrows',
            [
                'label' => esc_html__('Show arrows', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_dots',
            [
                'label' => esc_html__('Show dots', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'loop',
            [
                'label' => sprintf(__('Loop slides %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'autoplay',
            [
                'label' => sprintf(__('Autoplay %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'autoplay_speed',
            [
                'label' => sprintf(__('Autoplay speed (ms) %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::NUMBER,
                'default' => 4000,
                'condition' => [
                    'autoplay' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'nav_skin',
            [
                'label' => sprintf(__('Navigation skin %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'dark' => esc_html__('Dark', 'king-addons'),
                    'light' => esc_html__('Light', 'king-addons'),
                ],
                'default' => 'dark',
            ]
        );

        $this->add_control(
            'lightbox_skin',
            [
                'label' => esc_html__('Lightbox Skin', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'dark' => esc_html__('Dark', 'king-addons'),
                    'light' => esc_html__('Light', 'king-addons'),
                ],
                'default' => 'dark',
                'condition' => [
                    'lightbox' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'lightbox_counter',
            [
                'label' => sprintf(__('Lightbox Counter %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'condition' => [
                    'lightbox' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'lightbox_thumbs',
            [
                'label' => sprintf(__('Lightbox Thumbnails %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'condition' => [
                    'lightbox' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'dots_skin',
            [
                'label' => sprintf(__('Dots skin %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'dark' => esc_html__('Dark', 'king-addons'),
                    'light' => esc_html__('Light', 'king-addons'),
                ],
                'default' => 'dark',
                'condition' => [
                    'show_dots' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'lightbox',
            [
                'label' => sprintf(__('Enable lightbox %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'zoom_on_hover',
            [
                'label' => sprintf(__('Zoom on hover %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'masonry_gap',
            [
                'label' => esc_html__('Masonry Gap (px)', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'default' => 8,
                'condition' => [
                    'layout' => 'masonry',
                ],
            ]
        );

        $this->add_control(
            'masonry_row_height',
            [
                'label' => esc_html__('Masonry Row Height (px)', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'default' => 8,
                'condition' => [
                    'layout' => 'masonry',
                ],
            ]
        );

        $this->add_control(
            'show_captions',
            [
                'label' => sprintf(__('Show captions %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'aspect_ratio',
            [
                'label' => sprintf(__('Aspect Ratio (Pro) %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    '' => esc_html__('Auto', 'king-addons'),
                    '1:1' => esc_html__('1:1 Square', 'king-addons'),
                    '4:3' => esc_html__('4:3', 'king-addons'),
                    '3:4' => esc_html__('3:4', 'king-addons'),
                    '16:9' => esc_html__('16:9', 'king-addons'),
                    '9:16' => esc_html__('9:16', 'king-addons'),
                ],
                'default' => '',
            ]
        );

        $this->add_control(
            'mobile_layout',
            [
                'label' => sprintf(__('Mobile Layout (Pro) %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    '' => esc_html__('Inherit', 'king-addons'),
                    'slider' => esc_html__('Slider', 'king-addons'),
                    'grid' => esc_html__('Grid', 'king-addons'),
                ],
                'default' => '',
            ]
        );

        $this->add_control(
            'thumbs_per_row',
            [
                'label' => sprintf(__('Thumbs per row %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::NUMBER,
                'default' => 4,
                'condition' => [
                    'layout' => 'grid',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style_main',
            [
                'label' => esc_html__('Main Image', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Image_Size::get_type(),
            [
                'name' => 'main_image_size',
                'default' => 'large',
            ]
        );

        $this->add_control(
            'main_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-gallery__main img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'main_border',
                'selector' => '{{WRAPPER}} .ka-woo-gallery__main img',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'main_shadow',
                'selector' => '{{WRAPPER}} .ka-woo-gallery__main img',
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style_thumbs',
            [
                'label' => esc_html__('Thumbnails', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'thumb_gap',
            [
                'label' => esc_html__('Gap', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => ['min' => 0, 'max' => 30],
                ],
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-gallery__thumbs' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'thumb_active_border',
            [
                'label' => esc_html__('Active Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-gallery__thumb.is-active img' => 'box-shadow: 0 0 0 2px {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render(): void
    {
        $product = $this->get_product();
        if (!$product) {
            $this->render_missing_product_notice();
            return;
        }

        $settings = $this->get_settings_for_display();
        $can_pro = king_addons_can_use_pro();

        $layout = $settings['layout'] ?? 'slider';
        if (in_array($layout, ['thumbs_left', 'thumbs_right', 'masonry'], true) && !$can_pro) {
            $layout = 'slider';
        }
        $is_grid_layout = in_array($layout, ['grid', 'masonry'], true);

        $gallery = $product->get_gallery_image_ids();
        $featured_id = $product->get_image_id();
        if ($featured_id) {
            array_unshift($gallery, $featured_id);
        }
        $gallery = array_values(array_unique(array_filter($gallery)));

        $slides = [];
        foreach ($gallery as $image_id) {
            $img_html = Group_Control_Image_Size::get_attachment_image_html($settings, 'main_image_size', $image_id);
            if (!$img_html) {
                $img_html = wp_get_attachment_image($image_id, 'full');
            }
            if (!$img_html) {
                continue;
            }
            $caption = wp_get_attachment_caption($image_id);
            $alt = get_post_meta($image_id, '_wp_attachment_image_alt', true);
            $slides[] = [
                'html' => $img_html,
                'full' => wp_get_attachment_image_url($image_id, 'full') ?: '',
                'caption' => $caption ?: $alt,
            ];
        }

        if (empty($slides)) {
            $placeholder_html = '';
            $placeholder_full = '';
            if (function_exists('wc_placeholder_img')) {
                $size = $settings['main_image_size_size'] ?? 'full';
                if ('custom' === $size) {
                    $size = 'full';
                }
                $placeholder_html = wc_placeholder_img($size);
            }
            if (function_exists('wc_placeholder_img_src')) {
                $placeholder_full = wc_placeholder_img_src('full');
            }

            if ($placeholder_html) {
                $slides[] = [
                    'html' => $placeholder_html,
                    'full' => $placeholder_full ?: '',
                    'caption' => '',
                ];
            } else {
                if (class_exists('King_Addons\\Woo_Builder\\Context') && \King_Addons\Woo_Builder\Context::is_editor()) {
                    echo '<div class="king-addons-woo-builder-notice">' . esc_html__('No product images found. Add a featured image or gallery images to preview this widget.', 'king-addons') . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                }
                return;
            }
        }

        $wrapper_classes = ['ka-woo-gallery', 'ka-woo-gallery--' . esc_attr($layout)];
        if (!empty($settings['lightbox']) && $can_pro) {
            $wrapper_classes[] = 'ka-woo-gallery--lightbox';
        }
        if (!empty($settings['zoom_on_hover']) && $can_pro) {
            $wrapper_classes[] = 'ka-woo-gallery--zoom';
        }
        $mobile_layout = $settings['mobile_layout'] ?? '';
        if (!empty($mobile_layout) && $can_pro) {
            $wrapper_classes[] = 'ka-woo-gallery--mobile-' . $mobile_layout;
        }
        $nav_skin = $settings['nav_skin'] ?? 'dark';
        $dots_skin = $settings['dots_skin'] ?? 'dark';
        $wrapper_classes[] = 'ka-woo-gallery--nav-' . esc_attr($nav_skin);
        $wrapper_classes[] = 'ka-woo-gallery--dots-' . esc_attr($dots_skin);
        $lightbox_skin = $settings['lightbox_skin'] ?? 'dark';
        if (!empty($settings['lightbox']) && $can_pro) {
            $wrapper_classes[] = 'ka-woo-gallery--lightbox-' . esc_attr($lightbox_skin);
        }

        $aspect = ($settings['aspect_ratio'] ?? '');
        $aspect_attr = $aspect && $can_pro ? ' data-aspect="' . esc_attr($aspect) . '"' : '';

        $data_attrs = [];
        $show_arrows = !empty($settings['show_arrows']) && !$is_grid_layout;
        $show_dots = !empty($settings['show_dots']) && !$is_grid_layout;
        $data_attrs[] = 'data-arrows="' . ($show_arrows ? 'yes' : 'no') . '"';
        $data_attrs[] = 'data-dots="' . ($show_dots ? 'yes' : 'no') . '"';
        $data_attrs[] = 'data-layout="' . esc_attr($layout) . '"';
        $data_attrs[] = 'data-mobile-layout="' . esc_attr($mobile_layout) . '"';
        $data_attrs[] = 'data-loop="' . ((!empty($settings['loop']) && $can_pro && !$is_grid_layout) ? 'yes' : 'no') . '"';
        $data_attrs[] = 'data-autoplay="' . ((!empty($settings['autoplay']) && $can_pro && !$is_grid_layout) ? 'yes' : 'no') . '"';
        $data_attrs[] = 'data-speed="' . (int) ($settings['autoplay_speed'] ?? 4000) . '"';
        $data_attrs[] = 'data-captions="' . ((!empty($settings['show_captions']) && $can_pro) ? 'yes' : 'no') . '"';
        $data_attrs[] = 'data-lightbox="' . ((!empty($settings['lightbox']) && $can_pro) ? 'yes' : 'no') . '"';
        $data_attrs[] = 'data-lightbox-skin="' . esc_attr($lightbox_skin) . '"';
        $data_attrs[] = 'data-lightbox-counter="' . ((!empty($settings['lightbox_counter']) && $can_pro) ? 'yes' : 'no') . '"';
        $data_attrs[] = 'data-lightbox-thumbs="' . ((!empty($settings['lightbox_thumbs']) && $can_pro) ? 'yes' : 'no') . '"';

        $style_vars = [];
        $grid_cols = max(1, (int) ($settings['thumbs_per_row'] ?? 4));
        if ($is_grid_layout) {
            $style_vars[] = '--ka-gallery-grid-cols:' . $grid_cols;
        }
        if ('masonry' === $layout && $can_pro) {
            if (isset($settings['masonry_gap'])) {
                $style_vars[] = '--ka-gallery-gap:' . (int) $settings['masonry_gap'] . 'px';
            }
            if (isset($settings['masonry_row_height'])) {
                $style_vars[] = '--ka-gallery-row:' . (int) $settings['masonry_row_height'] . 'px';
            }
        }

        $style_attr = empty($style_vars) ? '' : ' style="' . esc_attr(implode(';', $style_vars)) . '"';

        $thumbs_html = '';
        if (!$is_grid_layout && count($slides) > 1) {
            ob_start();
            echo '<div class="ka-woo-gallery__thumbs">';
            foreach ($slides as $index => $slide) {
                $active = 0 === $index ? ' is-active' : '';
                echo '<div class="ka-woo-gallery__thumb' . $active . '" data-index="' . esc_attr((string) $index) . '">' . $slide['html'] . '</div>';
            }
            echo '</div>';
            $thumbs_html = ob_get_clean();
        }

        $main_html = '';
        ob_start();
        echo '<div class="ka-woo-gallery__main"' . $aspect_attr . '>';
        echo '<div class="ka-woo-gallery__slider">';
        foreach ($slides as $slide) {
            $caption_text = $slide['caption'] ? wp_strip_all_tags((string) $slide['caption']) : '';
            echo '<div class="ka-woo-gallery__slide" data-full="' . esc_url($slide['full'] ?: '') . '" data-caption="' . esc_attr($caption_text) . '">' . $slide['html'] . '</div>';
        }
        echo '</div>';
        if (count($slides) > 1 && $show_arrows) {
            echo '<button type="button" class="ka-woo-gallery__arrow ka-woo-gallery__arrow--prev" aria-label="' . esc_attr__('Previous', 'king-addons') . '">&#10094;</button>';
            echo '<button type="button" class="ka-woo-gallery__arrow ka-woo-gallery__arrow--next" aria-label="' . esc_attr__('Next', 'king-addons') . '">&#10095;</button>';
        }
        if (count($slides) > 1 && $show_dots) {
            echo '<div class="ka-woo-gallery__dots">';
            foreach ($slides as $index => $_) {
                $active = 0 === $index ? ' is-active' : '';
                echo '<button type="button" class="ka-woo-gallery__dot' . $active . '" data-index="' . esc_attr((string) $index) . '"></button>';
            }
            echo '</div>';
        }
        echo '</div>';
        $main_html = ob_get_clean();

        echo '<div class="' . esc_attr(implode(' ', $wrapper_classes)) . '" ' . implode(' ', $data_attrs) . $style_attr . '>';
        if ('thumbs_left' === $layout) {
            echo $thumbs_html . $main_html;
        } elseif ('thumbs_right' === $layout) {
            echo $main_html . $thumbs_html;
        } else {
            echo $main_html . $thumbs_html;
        }
        echo '</div>';
    }
}


