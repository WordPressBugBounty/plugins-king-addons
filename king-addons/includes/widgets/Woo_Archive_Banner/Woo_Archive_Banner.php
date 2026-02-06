<?php
/**
 * Woo Archive Banner widget.
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Typography;
use Elementor\Utils;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Displays archive banner.
 */
class Woo_Archive_Banner extends Abstract_Archive_Widget
{
    public function get_name(): string
    {
        return 'woo_archive_banner';
    }

    public function get_title(): string
    {
        return esc_html__('Archive Banner', 'king-addons');
    }

    public function get_icon(): string
    {
        return 'eicon-banner';
    }

    public function get_categories(): array
    {
        return ['king-addons-woo-builder'];
    }

    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-archive-banner-style'];
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
            'image',
            [
                'label' => esc_html__('Image', 'king-addons'),
                'type' => Controls_Manager::MEDIA,
                'default' => [
                    'url' => Utils::get_placeholder_image_src(),
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Image_Size::get_type(),
            [
                'name' => 'image_size',
                'default' => 'large',
            ]
        );

        $this->add_control(
            'title',
            [
                'label' => esc_html__('Title', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Archive Banner', 'king-addons'),
            ]
        );

        $this->add_control(
            'description',
            [
                'label' => esc_html__('Description', 'king-addons'),
                'type' => Controls_Manager::TEXTAREA,
                'default' => esc_html__('Highlight your category or shop with a banner.', 'king-addons'),
            ]
        );

        $this->add_control(
            'button_text',
            [
                'label' => esc_html__('Button Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
            ]
        );

        $this->add_control(
            'button_url',
            [
                'label' => esc_html__('Button URL', 'king-addons'),
                'type' => Controls_Manager::URL,
                'placeholder' => 'https://',
            ]
        );

        $this->add_control(
            'use_term_meta',
            [
                'label' => sprintf(__('Use term meta (Pro) %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'title_format',
            [
                'label' => sprintf(__('Title format %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::TEXT,
                'description' => esc_html__('Use {title} and {count}.', 'king-addons'),
            ]
        );

        $this->add_control(
            'desc_format',
            [
                'label' => sprintf(__('Description format %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::TEXTAREA,
                'description' => esc_html__('Use {description}, {title}, {count}.', 'king-addons'),
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

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'title_typo',
                'selector' => '{{WRAPPER}} .ka-woo-archive-banner__title',
            ]
        );

        $this->add_control(
            'title_color',
            [
                'label' => esc_html__('Title Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-archive-banner__title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'desc_typo',
                'selector' => '{{WRAPPER}} .ka-woo-archive-banner__desc',
            ]
        );

        $this->add_control(
            'desc_color',
            [
                'label' => esc_html__('Description Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-archive-banner__desc' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-archive-banner__inner' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render(): void
    {
        if (!class_exists('WooCommerce') || !function_exists('is_shop') || !function_exists('is_product_taxonomy')) {
            return;
        }

        if (!$this->should_render()) {
            $this->render_missing_archive_notice();
            return;
        }

        $settings = $this->get_settings_for_display();
        $can_pro = king_addons_can_use_pro();

        $title = $settings['title'] ?? '';
        $desc = $settings['description'] ?? '';
        $img_html = '';

        if (!empty($settings['use_term_meta']) && $can_pro && is_product_taxonomy()) {
            $term = get_queried_object();
            $term_img_id = get_term_meta($term->term_id, 'banner_image', true);
            $term_title = get_term_meta($term->term_id, 'banner_title', true);
            $term_desc = get_term_meta($term->term_id, 'banner_desc', true);
            if ($term_img_id) {
                $img_html = Group_Control_Image_Size::get_attachment_image_html($settings, 'image_size', $term_img_id);
            }
            if ($term_title) {
                $title = $term_title;
            }
            if ($term_desc) {
                $desc = $term_desc;
            }
        }

        if ($can_pro) {
            global $wp_query;
            $count = isset($wp_query->found_posts) ? (int) $wp_query->found_posts : 0;
            if (!empty($settings['title_format'])) {
                $title = str_replace(
                    ['{title}', '{count}'],
                    [$title, number_format_i18n($count)],
                    $settings['title_format']
                );
            }
            if (!empty($settings['desc_format'])) {
                $desc = str_replace(
                    ['{description}', '{title}', '{count}'],
                    [$desc, $title, number_format_i18n($count)],
                    $settings['desc_format']
                );
            }
        }

        if (empty($img_html) && !empty($settings['image']['id'])) {
            $img_html = Group_Control_Image_Size::get_attachment_image_html($settings, 'image_size', $settings['image']['id']);
        }

        echo '<div class="ka-woo-archive-banner">';
        if ($img_html) {
            echo '<div class="ka-woo-archive-banner__image">' . $img_html . '</div>';
        }
        echo '<div class="ka-woo-archive-banner__inner">';
        if ($title) {
            echo '<h3 class="ka-woo-archive-banner__title">' . esc_html($title) . '</h3>';
        }
        if ($desc) {
            echo '<div class="ka-woo-archive-banner__desc">' . wp_kses_post($desc) . '</div>';
        }
        if (!empty($settings['button_text']) && !empty($settings['button_url']['url'])) {
            $target = $settings['button_url']['is_external'] ? ' target="_blank" rel="noopener noreferrer"' : '';
            echo '<a class="ka-woo-archive-banner__btn" href="' . esc_url($settings['button_url']['url']) . '"' . $target . '>' . esc_html($settings['button_text']) . '</a>';
        }
        echo '</div>';
        echo '</div>';
    }
}






