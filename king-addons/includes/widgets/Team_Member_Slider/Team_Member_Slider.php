<?php
/**
 * Team Member Slider Widget
 *
 * @package King_Addons
 */
namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Css_Filter;
use Elementor\Repeater;
use Elementor\Utils;
use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}



class Team_Member_Slider extends Widget_Base {
    

    public function get_name(): string {
        return 'king-addons-team-member-slider';
    }

    public function get_title(): string {
        return esc_html__('Team Member Slider', 'king-addons');
    }

    public function get_icon(): string {
        return 'king-addons-icon king-addons-team-member-slider';
    }

    public function get_script_depends(): array {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-swiper-swiper',
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-team-member-slider-script',
        ];
    }

    public function get_style_depends(): array {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-swiper-swiper',
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-team-member-slider-style',
        ];
    }

    public function get_categories(): array {
        return ['king-addons'];
    }

    public function get_keywords(): array {
        return ['team', 'members', 'slider', 'carousel', 'staff', 'profile'];
    }

    protected function register_controls(): void {
        // Slider behavior settings
        $this->start_controls_section(
            'section_slider_settings',
            [
                'label' => esc_html__('Slider Settings', 'king-addons'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );
        $this->add_control(
            'slides_per_view',
            [
                'label' => esc_html__('Slides Per View', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 6,
                'step' => 1,
                'default' => 4,
            ]
        );
        $this->add_control(
            'slides_per_view_tablet',
            [
                'label' => esc_html__('Slides Per View (Tablet)', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 6,
                'step' => 1,
                'default' => 3,
            ]
        );
        $this->add_control(
            'slides_per_view_mobile',
            [
                'label' => esc_html__('Slides Per View (Mobile)', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 6,
                'step' => 1,
                'default' => 1,
            ]
        );
        $this->add_control(
            'space_between',
            [
                'label' => esc_html__('Space Between (px)', 'king-addons'),
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
            ]
        );
        $this->add_control(
            'loop',
            [
                'label' => esc_html__('Loop', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => '',
            ]
        );
        $this->add_control(
            'autoplay',
            [
                'label' => esc_html__('Autoplay', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => '',
            ]
        );
        $this->add_control(
            'autoplay_delay',
            [
                'label' => esc_html__('Autoplay Delay (ms)', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 0,
                'step' => 100,
                'default' => 2000,
                'condition' => [
                    'autoplay' => 'yes',
                ],
            ]
        );
        $this->add_control(
            'autoplay_reverse',
            [
                'label' => esc_html__('Reverse Autoplay Direction', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => '',
                'condition' => [
                    'autoplay' => 'yes',
                ],
            ]
        );
        $this->add_control(
            'speed',
            [
                'label' => esc_html__('Transition Speed (ms)', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 0,
                'step' => 100,
                'default' => 600,
            ]
        );
        $this->add_control(
            'pagination',
            [
                'label' => esc_html__('Pagination', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );
        $this->add_control(
            'navigation',
            [
                'label' => esc_html__('Navigation Arrows', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );
        $this->end_controls_section();

        // Style: Slide
        $this->start_controls_section(
            'section_style_slide',
            [
                'label' => esc_html__('Slide', 'king-addons'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );
        $this->add_responsive_control(
            'slide_padding',
            [
                'label'      => esc_html__('Padding', 'king-addons'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .king-addons-tms-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        $this->add_control(
            'slide_bg_color',
            [
                'label'     => esc_html__('Background Color', 'king-addons'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tms-item' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'slide_border',
                'selector' => '{{WRAPPER}} .king-addons-tms-item',
            ]
        );
        $this->add_control(
            'slide_border_radius',
            [
                'label'      => esc_html__('Border Radius', 'king-addons'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range'      => [
                    'px' => ['min' => 0, 'max' => 200],
                    '%'  => ['min' => 0, 'max' => 100],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .king-addons-tms-item' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );
        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'slide_box_shadow',
                'selector' => '{{WRAPPER}} .king-addons-tms-item',
            ]
        );
        $this->end_controls_section();

        // Style: Photo
        $this->start_controls_section(
            'section_style_photo',
            [
                'label' => esc_html__('Photo', 'king-addons'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );
        $this->add_group_control(
            Group_Control_Image_Size::get_type(),
            [
                'name'      => 'photo_size',
                'default'   => 'thumbnail',
                'separator' => 'none',
            ]
        );
        $this->add_responsive_control(
            'photo_width',
            [
                'label'      => esc_html__('Width', 'king-addons'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'em'],
                'range'      => [
                    'px' => ['min' => 0, 'max' => 1000],
                    '%'  => ['min' => 0, 'max' => 100],
                    'em' => ['min' => 0, 'max' => 10],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .king-addons-tms-photo img' => 'width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );
        $this->add_responsive_control(
            'photo_height',
            [
                'label'      => esc_html__('Height', 'king-addons'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'em'],
                'range'      => [
                    'px' => ['min' => 0, 'max' => 1000],
                    '%'  => ['min' => 0, 'max' => 100],
                    'em' => ['min' => 0, 'max' => 10],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .king-addons-tms-photo img' => 'height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );
        $this->add_control(
            'photo_fit',
            [
                'label'   => esc_html__('Image Fit', 'king-addons'),
                'type'    => Controls_Manager::SELECT,
                'options' => [
                    'cover'   => esc_html__('Cover', 'king-addons'),
                    'contain' => esc_html__('Contain', 'king-addons'),
                    'fill'    => esc_html__('Fill', 'king-addons'),
                    'none'    => esc_html__('None', 'king-addons'),
                ],
                'default'   => 'cover',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tms-photo img' => 'object-fit: {{VALUE}};',
                ],
            ]
        );
        $this->add_control(
            'photo_border_radius',
            [
                'label'      => esc_html__('Border Radius', 'king-addons'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range'      => [
                    'px' => ['min' => 0, 'max' => 200],
                    '%'  => ['min' => 0, 'max' => 100],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .king-addons-tms-photo img' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );
        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'photo_box_shadow',
                'selector' => '{{WRAPPER}} .king-addons-tms-photo img',
            ]
        );

        $this->add_group_control(
            Group_Control_Css_Filter::get_type(),
            [
                'name' => 'kng_photo_css_filters',
                'label' => esc_html__('CSS Filters', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-tms-photo img',
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            Group_Control_Css_Filter::get_type(),
            [
                'name' => 'kng_photo_css_filters_hover',
                'label' => esc_html__('CSS Filters on Hover', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-tms-photo:hover img',
            ]
        );

        $this->end_controls_section();

        // Style: Name
        $this->start_controls_section(
            'section_style_name',
            [
                'label' => esc_html__('Name', 'king-addons'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'name_typography',
                'selector' => '{{WRAPPER}} .king-addons-tms-name',
            ]
        );
        $this->add_control(
            'name_color',
            [
                'label'     => esc_html__('Color', 'king-addons'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tms-name' => 'color: {{VALUE}};',
                ],
            ]
        );
        $this->end_controls_section();

        // Style: Role
        $this->start_controls_section(
            'section_style_role',
            [
                'label' => esc_html__('Role', 'king-addons'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'role_typography',
                'selector' => '{{WRAPPER}} .king-addons-tms-role',
            ]
        );
        $this->add_control(
            'role_color',
            [
                'label'     => esc_html__('Color', 'king-addons'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tms-role' => 'color: {{VALUE}};',
                ],
            ]
        );
        $this->end_controls_section();

        // Style: Bio
        $this->start_controls_section(
            'section_style_bio',
            [
                'label' => esc_html__('Bio', 'king-addons'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'bio_typography',
                'selector' => '{{WRAPPER}} .king-addons-tms-bio',
            ]
        );
        $this->add_control(
            'bio_color',
            [
                'label'     => esc_html__('Color', 'king-addons'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tms-bio' => 'color: {{VALUE}};',
                ],
            ]
        );
        $this->end_controls_section();

        // Style: Pagination
        $this->start_controls_section(
            'section_style_pagination',
            [
                'label' => esc_html__('Pagination', 'king-addons'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );
        $this->add_control(
            'pagination_bullet_size',
            [
                'label' => esc_html__('Bullet Size (px)', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => [ 'min' => 4, 'max' => 20 ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .swiper-pagination-bullet' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );
        $this->add_control(
            'pagination_bullet_color',
            [
                'label'     => esc_html__('Bullet Color', 'king-addons'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .swiper-pagination-bullet' => 'background: {{VALUE}};',
                ],
            ]
        );
        $this->add_control(
            'pagination_bullet_active_color',
            [
                'label'     => esc_html__('Bullet Active Color', 'king-addons'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .swiper-pagination-bullet-active' => 'background: {{VALUE}};',
                ],
            ]
        );
        // Pagination position (inside/outside)
        $this->add_control(
            'pagination_position',
            [
                'label'   => esc_html__('Pagination Position', 'king-addons'),
                'type'    => Controls_Manager::SELECT,
                'options' => [
                    'inside'  => esc_html__('Inside', 'king-addons'),
                    'outside' => esc_html__('Outside', 'king-addons'),
                ],
                'default' => 'inside',
            ]
        );
        $this->end_controls_section();

        // Style: Navigation
        $this->start_controls_section(
            'section_style_navigation',
            [
                'label' => esc_html__('Navigation', 'king-addons'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );
        $this->add_responsive_control(
            'nav_arrow_size',
            [
                'label' => esc_html__('Arrow Size (px)', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => [ 'min' => 10, 'max' => 60 ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .swiper-button-next, {{WRAPPER}} .swiper-button-prev' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}; background-size: {{SIZE}}{{UNIT}} {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .swiper-button-next::after, {{WRAPPER}} .swiper-button-prev::after' => 'font-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );
        $this->add_control(
            'nav_arrow_color',
            [
                'label'     => esc_html__('Arrow Color', 'king-addons'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .swiper-button-next, {{WRAPPER}} .swiper-button-prev' => 'color: {{VALUE}};',
                ],
            ]
        );
        // Navigation position (inside/outside)
        $this->add_control(
            'navigation_position',
            [
                'label'   => esc_html__('Arrows Position', 'king-addons'),
                'type'    => Controls_Manager::SELECT,
                'options' => [
                    'inside'  => esc_html__('Inside', 'king-addons'),
                    'outside' => esc_html__('Outside', 'king-addons'),
                ],
                'default' => 'inside',
            ]
        );
        $this->end_controls_section();
        
        // Team members list
        $this->start_controls_section(
            'section_members',
            [
                'label' => esc_html__('Team Members', 'king-addons'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $repeater = new Repeater();
        $repeater->add_control(
            'name',
            [
                'label'       => esc_html__('Name', 'king-addons'),
                'type'        => Controls_Manager::TEXT,
                'default'     => esc_html__('John Doe', 'king-addons'),
                'label_block' => true,
            ]
        );
        $repeater->add_control(
            'role',
            [
                'label'       => esc_html__('Role', 'king-addons'),
                'type'        => Controls_Manager::TEXT,
                'default'     => esc_html__('CEO', 'king-addons'),
                'label_block' => true,
            ]
        );
        $repeater->add_control(
            'photo',
            [
                'label'   => esc_html__('Photo', 'king-addons'),
                'type'    => Controls_Manager::MEDIA,
                'default' => [
                    'url' => Utils::get_placeholder_image_src(),
                ],
            ]
        );
        $repeater->add_control(
            'bio',
            [
                'label'   => esc_html__('Bio', 'king-addons'),
                'type'    => Controls_Manager::TEXTAREA,
                'default' => esc_html__('Brief bio goes here.', 'king-addons'),
            ]
        );

        $this->add_control(
            'members',
            [
                'label'       => esc_html__('Members', 'king-addons'),
                'type'        => Controls_Manager::REPEATER,
                'fields'      => $repeater->get_controls(),
                'default'     => [
                    ['name' => esc_html__('John Doe', 'king-addons'), 'role' => esc_html__('CEO', 'king-addons')],
                ],
                'title_field' => '{{{ name }}}',
            ]
        );
        
        

$this->end_controls_section();
    
        
    }

    protected function render(): void {
        $settings = $this->get_settings_for_display();
        if (empty($settings['members'])) {
            return;
        }
        ?>
        <?php
        // Prepare carousel settings attributes
        $attrs = [];
        $attrs[] = 'data-slides-per-view="' . esc_attr($settings['slides_per_view']) . '"';
        $attrs[] = 'data-slides-per-view-tablet="' . esc_attr($settings['slides_per_view_tablet']) . '"';
        $attrs[] = 'data-slides-per-view-mobile="' . esc_attr($settings['slides_per_view_mobile']) . '"';
        $attrs[] = 'data-space-between="' . esc_attr($settings['space_between']['size']) . '"';
        $attrs[] = 'data-loop="' . esc_attr($settings['loop']) . '"';
        $attrs[] = 'data-autoplay="' . esc_attr($settings['autoplay']) . '"';
        $attrs[] = 'data-autoplay-delay="' . esc_attr($settings['autoplay_delay']) . '"';
        $attrs[] = 'data-autoplay-reverse="' . esc_attr($settings['autoplay_reverse']) . '"';
        $attrs[] = 'data-speed="' . esc_attr($settings['speed']) . '"';
        $attrs[] = 'data-pagination="' . esc_attr($settings['pagination']) . '"';
        $attrs[] = 'data-navigation="' . esc_attr($settings['navigation']) . '"';
        ?>
        <?php
        // Build container classes
        $classes = [
            'king-addons-team-member-slider',
            'swiper-container',
            'swiper',
        ];
        if (!empty($settings['navigation_position']) && 'outside' === $settings['navigation_position']) {
            $classes[] = 'arrows-outside';
        }
        if (!empty($settings['pagination_position']) && 'outside' === $settings['pagination_position']) {
            $classes[] = 'pagination-outside';
        }
        ?>
        <div class="<?php echo esc_attr(implode(' ', $classes)); ?>" <?php echo implode(' ', $attrs); ?>>
            <div class="swiper-wrapper">
                <?php foreach ($settings['members'] as $member) : ?>
                    <div class="swiper-slide">
                        <div class="king-addons-tms-item">
                            <?php if (!empty($member['photo']['url'])) : ?>
                                <div class="king-addons-tms-photo">
                                    <img src="<?php echo esc_url($member['photo']['url']); ?>" alt="<?php echo esc_attr($member['name']); ?>" />
                                </div>
                            <?php endif; ?>
                            <div class="king-addons-tms-info">
                                <h4 class="king-addons-tms-name"><?php echo esc_html($member['name']); ?></h4>
                                <span class="king-addons-tms-role"><?php echo esc_html($member['role']); ?></span>
                                <?php if (!empty($member['bio'])) : ?>
                                    <p class="king-addons-tms-bio"><?php echo esc_html($member['bio']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="swiper-pagination"></div>
            <div class="swiper-button-prev"></div>
            <div class="swiper-button-next"></div>
        </div>
        <?php
    }
}