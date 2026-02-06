<?php
/**
 * Testimonials Wall Widget (Free).
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
use Elementor\Repeater;
use Elementor\Utils;
use Elementor\Widget_Base;
use Elementor\Plugin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Displays testimonials in a masonry/grid wall with filters and load more.
 */
class Testimonials_Wall extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-testimonials-wall';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Testimonials Wall', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-testimonials-wall';
    }

    /**
     * Style dependencies.
     *
     * @return array<int, string>
     */
    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-testimonials-wall-style'];
    }

    /**
     * Script dependencies.
     *
     * @return array<int, string>
     */
    public function get_script_depends(): array
    {
        $handle = KING_ADDONS_ASSETS_UNIQUE_KEY . '-testimonials-wall-script';

        // Elementor may call enqueue_scripts before the widget settings are available.
        // Therefore, get_script_depends() must not access settings.
        // The script itself may "do nothing" if the feature is disabled via settings.
        return [$handle];
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
        return [
            'testimonials',
            'reviews',
            'wall',
            'masonry',
            'grid',
            'ratings',
            'filters',
            'load more',
            'king',
            'addons',
            'kingaddons',
            'king-addons',
        ];
    }

    /**
     * Widget help URL.
     *
     * @return string
     */
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
        $this->register_content_controls();
        $this->register_display_controls();
        $this->register_layout_controls();
        $this->register_filter_controls();
        $this->register_load_more_controls();
        $this->register_empty_state_controls();
        $this->register_editor_controls();
        $this->register_style_controls();
        $this->register_pro_notice_controls();
    }

    /**
     * Register content controls.
     *
     * @return void
     */
    protected function register_content_controls(): void
    {
        $this->start_controls_section(
            'kng_tw_content_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Testimonials', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $repeater = new Repeater();

        $repeater->add_control(
            'kng_tw_name',
            [
                'label' => esc_html__('Author Name', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Alex Johnson', 'king-addons'),
                'label_block' => true,
            ]
        );

        $repeater->add_control(
            'kng_tw_title',
            [
                'label' => esc_html__('Author Title', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Product Lead', 'king-addons'),
                'label_block' => true,
            ]
        );

        $repeater->add_control(
            'kng_tw_company',
            [
                'label' => esc_html__('Company', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Flux Labs', 'king-addons'),
                'label_block' => true,
            ]
        );

        $repeater->add_control(
            'kng_tw_avatar',
            [
                'label' => esc_html__('Avatar', 'king-addons'),
                'type' => Controls_Manager::MEDIA,
                'default' => [
                    'url' => Utils::get_placeholder_image_src(),
                ],
            ]
        );

        $repeater->add_control(
            'kng_tw_rating',
            [
                'label' => esc_html__('Rating (1-5)', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 0,
                'max' => 5,
                'step' => 1,
                'default' => 5,
            ]
        );

        $repeater->add_control(
            'kng_tw_date',
            [
                'label' => esc_html__('Date', 'king-addons'),
                'type' => Controls_Manager::DATE_TIME,
                'label_block' => true,
            ]
        );

        $repeater->add_control(
            'kng_tw_text',
            [
                'label' => esc_html__('Review Text', 'king-addons'),
                'type' => Controls_Manager::TEXTAREA,
                'rows' => 6,
                'default' => esc_html__('Fast onboarding, great support, and the wall layout makes our social proof feel alive.', 'king-addons'),
            ]
        );

        $repeater->add_control(
            'kng_tw_source',
            [
                'label' => esc_html__('Source Label', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Google', 'king-addons'),
            ]
        );

        $repeater->add_control(
            'kng_tw_categories',
            [
                'label' => esc_html__('Categories (comma-separated)', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('SaaS, Support', 'king-addons'),
                'label_block' => true,
            ]
        );

        $repeater->add_control(
            'kng_tw_featured',
            [
                'label' => sprintf(__('Featured %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
                'separator' => 'before',
                'classes' => 'king-addons-pro-control no-distance',
            ]
        );

        $default_items = [
            [
                'kng_tw_name' => esc_html__('Alex Johnson', 'king-addons'),
                'kng_tw_title' => esc_html__('Product Lead', 'king-addons'),
                'kng_tw_company' => esc_html__('Flux Labs', 'king-addons'),
                'kng_tw_rating' => 5,
                'kng_tw_text' => esc_html__('Fast onboarding, great support, and the wall layout makes our social proof feel alive.', 'king-addons'),
                'kng_tw_source' => esc_html__('Google', 'king-addons'),
                'kng_tw_categories' => esc_html__('SaaS, Support', 'king-addons'),
            ],
            [
                'kng_tw_name' => esc_html__('Maya Lee', 'king-addons'),
                'kng_tw_title' => esc_html__('Founder', 'king-addons'),
                'kng_tw_company' => esc_html__('Studio North', 'king-addons'),
                'kng_tw_rating' => 5,
                'kng_tw_text' => esc_html__('We replaced the old carousel with the wall and conversion improved instantly.', 'king-addons'),
                'kng_tw_source' => esc_html__('Upwork', 'king-addons'),
                'kng_tw_categories' => esc_html__('Agency, Conversion', 'king-addons'),
            ],
            [
                'kng_tw_name' => esc_html__('Jonas Weber', 'king-addons'),
                'kng_tw_title' => esc_html__('CX Manager', 'king-addons'),
                'kng_tw_company' => esc_html__('Cloudline', 'king-addons'),
                'kng_tw_rating' => 4,
                'kng_tw_text' => esc_html__('Smooth filters and the masonry vibe make it feel modern without the heavy JS.', 'king-addons'),
                'kng_tw_source' => esc_html__('Trustpilot', 'king-addons'),
                'kng_tw_categories' => esc_html__('SaaS, UI', 'king-addons'),
            ],
            [
                'kng_tw_name' => esc_html__('Sophia Grant', 'king-addons'),
                'kng_tw_title' => esc_html__('Marketing Lead', 'king-addons'),
                'kng_tw_company' => esc_html__('Orbit AI', 'king-addons'),
                'kng_tw_rating' => 5,
                'kng_tw_text' => esc_html__('Search + rating filters help visitors quickly find relevant stories.', 'king-addons'),
                'kng_tw_source' => esc_html__('G2', 'king-addons'),
                'kng_tw_categories' => esc_html__('B2B, Growth', 'king-addons'),
            ],
            [
                'kng_tw_name' => esc_html__('Liam Perez', 'king-addons'),
                'kng_tw_title' => esc_html__('Operations', 'king-addons'),
                'kng_tw_company' => esc_html__('BrightPath', 'king-addons'),
                'kng_tw_rating' => 4,
                'kng_tw_text' => esc_html__('The load more feel keeps the page light but still rich in proof.', 'king-addons'),
                'kng_tw_source' => esc_html__('LinkedIn', 'king-addons'),
                'kng_tw_categories' => esc_html__('Services, Operations', 'king-addons'),
            ],
            [
                'kng_tw_name' => esc_html__('Ivy Chen', 'king-addons'),
                'kng_tw_title' => esc_html__('Designer', 'king-addons'),
                'kng_tw_company' => esc_html__('Layer Studio', 'king-addons'),
                'kng_tw_rating' => 5,
                'kng_tw_text' => esc_html__('Love the dense layout and the subtle hover lift.', 'king-addons'),
                'kng_tw_source' => esc_html__('Behance', 'king-addons'),
                'kng_tw_categories' => esc_html__('Design, Studio', 'king-addons'),
            ],
        ];

        foreach ($default_items as &$item) {
            $item['kng_tw_avatar'] = [
                'url' => Utils::get_placeholder_image_src(),
            ];
        }
        unset($item);

        $this->add_control(
            'kng_tw_items',
            [
                'type' => Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'default' => $default_items,
                'title_field' => '{{kng_tw_name}}',
            ]
        );

        $this->add_group_control(
            Group_Control_Image_Size::get_type(),
            [
                'name' => 'kng_tw_avatar_size',
                'default' => 'thumbnail',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register display toggles.
     *
     * @return void
     */
    protected function register_display_controls(): void
    {
        $this->start_controls_section(
            'kng_tw_display_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Display', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_tw_show_avatar',
            [
                'label' => esc_html__('Show Avatar', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_tw_show_meta',
            [
                'label' => esc_html__('Show Author Meta', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_tw_show_rating',
            [
                'label' => esc_html__('Show Rating', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_tw_show_date',
            [
                'label' => esc_html__('Show Date', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
            ]
        );

        $this->add_control(
            'kng_tw_show_source',
            [
                'label' => esc_html__('Show Source Label', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_tw_enable_read_more',
            [
                'label' => esc_html__('Enable Read More', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
            ]
        );

        $this->add_control(
            'kng_tw_read_more_lines',
            [
                'label' => esc_html__('Collapsed Lines', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 2,
                'max' => 12,
                'step' => 1,
                'default' => 4,
                'condition' => [
                    'kng_tw_enable_read_more' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_tw_read_more_text',
            [
                'label' => esc_html__('Read More Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Read more', 'king-addons'),
                'condition' => [
                    'kng_tw_enable_read_more' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_tw_read_less_text',
            [
                'label' => esc_html__('Read Less Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Read less', 'king-addons'),
                'condition' => [
                    'kng_tw_enable_read_more' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register layout controls.
     *
     * @return void
     */
    protected function register_layout_controls(): void
    {
        $this->start_controls_section(
            'kng_tw_layout_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Layout', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_tw_layout',
            [
                'label' => esc_html__('Layout Mode', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'masonry' => esc_html__('Masonry', 'king-addons'),
                    'grid' => esc_html__('Grid', 'king-addons'),
                ],
                'default' => 'masonry',
            ]
        );

        $this->add_responsive_control(
            'kng_tw_columns',
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
                    '{{WRAPPER}} .king-addons-testimonials-wall' => '--ka-tw-cols: {{SIZE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_tw_gap',
            [
                'label' => esc_html__('Gap', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 60],
                    'em' => ['min' => 0, 'max' => 3],
                    'rem' => ['min' => 0, 'max' => 3],
                ],
                'default' => [
                    'size' => 24,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall' => '--ka-tw-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_tw_item_min_width',
            [
                'label' => esc_html__('Item Min Width', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => ['min' => 120, 'max' => 480],
                    'em' => ['min' => 8, 'max' => 30],
                    'rem' => ['min' => 8, 'max' => 30],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall' => '--ka-tw-item-min: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_tw_masonry_row_height',
            [
                'label' => esc_html__('Masonry Row Height', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 4, 'max' => 20],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall' => '--ka-tw-row: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'kng_tw_layout' => 'masonry',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register filters controls.
     *
     * @return void
     */
    protected function register_filter_controls(): void
    {
        $this->start_controls_section(
            'kng_tw_filters_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Filters', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_tw_enable_filters',
            [
                'label' => esc_html__('Enable Filters', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_tw_filters_position',
            [
                'label' => esc_html__('Filter Bar Position', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'top' => esc_html__('Top', 'king-addons'),
                    'left' => esc_html__('Left', 'king-addons'),
                ],
                'default' => 'top',
                'condition' => [
                    'kng_tw_enable_filters' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_tw_enable_category_filter',
            [
                'label' => esc_html__('Category Filter', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
                'condition' => [
                    'kng_tw_enable_filters' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_tw_all_label',
            [
                'label' => esc_html__('All Label', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('All', 'king-addons'),
                'condition' => [
                    'kng_tw_enable_filters' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_tw_enable_rating_filter',
            [
                'label' => esc_html__('Rating Filter', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
                'condition' => [
                    'kng_tw_enable_filters' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_tw_rating_ui',
            [
                'label' => esc_html__('Rating UI', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'chips' => esc_html__('Chips', 'king-addons'),
                    'dropdown' => esc_html__('Dropdown', 'king-addons'),
                ],
                'default' => 'chips',
                'condition' => [
                    'kng_tw_enable_filters' => 'yes',
                    'kng_tw_enable_rating_filter' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_tw_enable_search',
            [
                'label' => esc_html__('Search Field', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
                'condition' => [
                    'kng_tw_enable_filters' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_tw_search_placeholder',
            [
                'label' => esc_html__('Search Placeholder', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Search testimonials', 'king-addons'),
                'condition' => [
                    'kng_tw_enable_filters' => 'yes',
                    'kng_tw_enable_search' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_tw_filters_sticky',
            [
                'label' => sprintf(__('Sticky Filters %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
                'condition' => [
                    'kng_tw_enable_filters' => 'yes',
                ],
                'classes' => 'king-addons-pro-control no-distance',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register load more controls.
     *
     * @return void
     */
    protected function register_load_more_controls(): void
    {
        $this->start_controls_section(
            'kng_tw_load_more_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Load More', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_tw_enable_load_more',
            [
                'label' => esc_html__('Enable Load More', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_tw_initial_items',
            [
                'label' => esc_html__('Initial Items', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 6,
                'max' => 24,
                'step' => 1,
                'default' => 9,
                'condition' => [
                    'kng_tw_enable_load_more' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_tw_items_per_load',
            [
                'label' => esc_html__('Items per Load', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 4,
                'max' => 20,
                'step' => 1,
                'default' => 6,
                'condition' => [
                    'kng_tw_enable_load_more' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_tw_load_more_text',
            [
                'label' => esc_html__('Load More Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Load more', 'king-addons'),
                'condition' => [
                    'kng_tw_enable_load_more' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_tw_loading_text',
            [
                'label' => esc_html__('Loading Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Loading...', 'king-addons'),
                'condition' => [
                    'kng_tw_enable_load_more' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_tw_enable_skeleton',
            [
                'label' => esc_html__('Skeleton Loader', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
                'condition' => [
                    'kng_tw_enable_load_more' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_tw_skeleton_count',
            [
                'label' => esc_html__('Skeleton Count', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 12,
                'step' => 1,
                'default' => 3,
                'condition' => [
                    'kng_tw_enable_load_more' => 'yes',
                    'kng_tw_enable_skeleton' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_tw_infinite_scroll',
            [
                'label' => sprintf(__('Infinite Scroll %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
                'condition' => [
                    'kng_tw_enable_load_more' => 'yes',
                ],
                'classes' => 'king-addons-pro-control no-distance',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register empty state controls.
     *
     * @return void
     */
    protected function register_empty_state_controls(): void
    {
        $this->start_controls_section(
            'kng_tw_empty_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Empty State', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_tw_enable_empty_state',
            [
                'label' => esc_html__('Show Empty Message', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_tw_empty_text',
            [
                'label' => esc_html__('Empty Message', 'king-addons'),
                'type' => Controls_Manager::TEXTAREA,
                'default' => esc_html__('No testimonials match your filters.', 'king-addons'),
                'condition' => [
                    'kng_tw_enable_empty_state' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register editor preview controls.
     *
     * @return void
     */
    protected function register_editor_controls(): void
    {
        $this->start_controls_section(
            'kng_tw_editor_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Editor Preview', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_tw_editor_notice',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => '<div style="background: #f8f9ff; border: 1px solid #d6d9ff; border-radius: 6px; padding: 12px; margin-bottom: 10px;">
                    <strong style="display: block; margin-bottom: 6px;">' . esc_html__('Editor note', 'king-addons') . '</strong>
                    <span style="font-size: 12px; line-height: 1.4;">' . esc_html__('Live filtering and load more are optimized for the frontend. Use preview toggles if needed while editing.', 'king-addons') . '</span>
                </div>',
            ]
        );

        $this->add_control(
            'kng_tw_editor_masonry_preview',
            [
                'label' => esc_html__('Enable Masonry Preview', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
            ]
        );

        $this->add_control(
            'kng_tw_editor_load_more_preview',
            [
                'label' => esc_html__('Enable Load More Preview', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
            ]
        );

        $this->add_control(
            'kng_tw_editor_max_items',
            [
                'label' => esc_html__('Max Items in Editor', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 4,
                'max' => 20,
                'step' => 1,
                'default' => 8,
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
        $this->register_style_wall_controls();
        $this->register_style_card_controls();
        $this->register_style_typography_controls();
        $this->register_style_avatar_controls();
        $this->register_style_rating_controls();
        $this->register_style_filter_controls();
        $this->register_style_read_more_controls();
        $this->register_style_load_more_controls();
        $this->register_style_empty_state_controls();
        $this->register_style_skeleton_controls();
    }

    /**
     * Wall style controls.
     *
     * @return void
     */
    protected function register_style_wall_controls(): void
    {
        $this->start_controls_section(
            'kng_tw_style_wall',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Wall', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name' => 'kng_tw_wall_bg',
                'selector' => '{{WRAPPER}} .king-addons-testimonials-wall',
            ]
        );

        $this->add_responsive_control(
            'kng_tw_wall_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', 'rem'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_tw_wall_max_width',
            [
                'label' => esc_html__('Content Width', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => ['min' => 320, 'max' => 1600],
                    '%' => ['min' => 50, 'max' => 100],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall' => '--ka-tw-max-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_tw_text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall' => '--ka-tw-text-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_tw_muted_color',
            [
                'label' => esc_html__('Muted Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall' => '--ka-tw-muted-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_tw_border_color',
            [
                'label' => esc_html__('Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall' => '--ka-tw-border-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Card style controls.
     *
     * @return void
     */
    protected function register_style_card_controls(): void
    {
        $this->start_controls_section(
            'kng_tw_style_card',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Card', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'kng_tw_card_bg',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall__card' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_tw_card_border',
                'selector' => '{{WRAPPER}} .king-addons-testimonials-wall__card',
            ]
        );

        $this->add_responsive_control(
            'kng_tw_card_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall__card' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_tw_card_shadow',
                'selector' => '{{WRAPPER}} .king-addons-testimonials-wall__card',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_tw_card_shadow_hover',
                'selector' => '{{WRAPPER}} .king-addons-testimonials-wall__card:hover',
            ]
        );

        $this->add_responsive_control(
            'kng_tw_card_hover_translate',
            [
                'label' => esc_html__('Hover Lift', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 20],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall' => '--ka-tw-card-hover-translate: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_tw_card_hover_duration',
            [
                'label' => esc_html__('Hover Transition (s)', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 0,
                'max' => 2,
                'step' => 0.05,
                'default' => 0.2,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall' => '--ka-tw-card-hover-duration: {{VALUE}}s;',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_tw_card_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', 'rem'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall__card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
            'kng_tw_style_typography',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Typography', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_tw_name_typography',
                'selector' => '{{WRAPPER}} .king-addons-testimonials-wall__name',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_tw_meta_typography',
                'selector' => '{{WRAPPER}} .king-addons-testimonials-wall__meta',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_tw_text_typography',
                'selector' => '{{WRAPPER}} .king-addons-testimonials-wall__text',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_tw_source_typography',
                'selector' => '{{WRAPPER}} .king-addons-testimonials-wall__source',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_tw_date_typography',
                'selector' => '{{WRAPPER}} .king-addons-testimonials-wall__date',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Avatar style controls.
     *
     * @return void
     */
    protected function register_style_avatar_controls(): void
    {
        $this->start_controls_section(
            'kng_tw_style_avatar',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Avatar', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'kng_tw_avatar_box_size',
            [
                'label' => esc_html__('Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => ['min' => 24, 'max' => 120],
                    'em' => ['min' => 2, 'max' => 8],
                    'rem' => ['min' => 2, 'max' => 8],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall__avatar' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_tw_avatar_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall__avatar' => 'border-radius: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .king-addons-testimonials-wall__avatar img' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_tw_avatar_border',
                'selector' => '{{WRAPPER}} .king-addons-testimonials-wall__avatar',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Rating style controls.
     *
     * @return void
     */
    protected function register_style_rating_controls(): void
    {
        $this->start_controls_section(
            'kng_tw_style_rating',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Rating', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'kng_tw_star_size',
            [
                'label' => esc_html__('Star Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall' => '--ka-tw-star-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_tw_star_color',
            [
                'label' => esc_html__('Star Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall' => '--ka-tw-star-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_tw_star_empty_color',
            [
                'label' => esc_html__('Empty Star Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall' => '--ka-tw-star-muted: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_tw_star_gap',
            [
                'label' => esc_html__('Star Gap', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall' => '--ka-tw-star-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Filters style controls.
     *
     * @return void
     */
    protected function register_style_filter_controls(): void
    {
        $this->start_controls_section(
            'kng_tw_style_filters',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Filters', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'kng_tw_chip_style',
            [
                'label' => esc_html__('Chip Style', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'filled' => esc_html__('Filled', 'king-addons'),
                    'outline' => esc_html__('Outline', 'king-addons'),
                ],
                'default' => 'filled',
            ]
        );

        $this->add_control(
            'kng_tw_filters_alignment',
            [
                'label' => esc_html__('Filters Alignment', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'flex-start' => esc_html__('Left', 'king-addons'),
                    'center' => esc_html__('Center', 'king-addons'),
                    'flex-end' => esc_html__('Right', 'king-addons'),
                    'space-between' => esc_html__('Space Between', 'king-addons'),
                ],
                'default' => 'flex-start',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall__filters' => 'justify-content: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_tw_filters_gap',
            [
                'label' => esc_html__('Filter Bar Gap', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 40],
                    'em' => ['min' => 0, 'max' => 3],
                    'rem' => ['min' => 0, 'max' => 3],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall' => '--ka-tw-filters-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_tw_filter_group_gap',
            [
                'label' => esc_html__('Filter Group Gap', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 30],
                    'em' => ['min' => 0, 'max' => 2],
                    'rem' => ['min' => 0, 'max' => 2],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall' => '--ka-tw-filter-group-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_tw_chip_typography',
                'selector' => '{{WRAPPER}} .king-addons-testimonials-wall__filter-chip',
            ]
        );

        $this->add_control(
            'kng_tw_chip_text_color',
            [
                'label' => esc_html__('Chip Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall' => '--ka-tw-chip-text: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_tw_chip_bg_color',
            [
                'label' => esc_html__('Chip Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall' => '--ka-tw-chip-bg: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_tw_chip_border_color',
            [
                'label' => esc_html__('Chip Border', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall' => '--ka-tw-chip-border: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_tw_chip_active_text_color',
            [
                'label' => esc_html__('Active Text', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall' => '--ka-tw-chip-active-text: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_tw_chip_active_bg_color',
            [
                'label' => esc_html__('Active Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall' => '--ka-tw-chip-active-bg: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_tw_chip_active_border_color',
            [
                'label' => esc_html__('Active Border', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall' => '--ka-tw-chip-active-border: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_tw_chip_radius',
            [
                'label' => esc_html__('Chip Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall__filter-chip' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_tw_chip_padding',
            [
                'label' => esc_html__('Chip Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', 'rem'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall__filter-chip' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_tw_search_typography',
                'selector' => '{{WRAPPER}} .king-addons-testimonials-wall__search-input',
            ]
        );

        $this->add_responsive_control(
            'kng_tw_search_padding',
            [
                'label' => esc_html__('Search Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', 'rem'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall__search-input' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_tw_search_bg',
            [
                'label' => esc_html__('Search Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall__search-input' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_tw_search_text',
            [
                'label' => esc_html__('Search Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall__search-input' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_tw_search_border',
            [
                'label' => esc_html__('Search Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall__search-input' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_tw_search_radius',
            [
                'label' => esc_html__('Search Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall__search-input' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_tw_dropdown_typography',
                'selector' => '{{WRAPPER}} .king-addons-testimonials-wall__rating-select',
            ]
        );

        $this->add_responsive_control(
            'kng_tw_dropdown_padding',
            [
                'label' => esc_html__('Dropdown Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', 'rem'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall__rating-select' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_tw_dropdown_bg',
            [
                'label' => esc_html__('Dropdown Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall__rating-select' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_tw_dropdown_text',
            [
                'label' => esc_html__('Dropdown Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall__rating-select' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_tw_dropdown_border',
            [
                'label' => esc_html__('Dropdown Border', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall__rating-select' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_tw_dropdown_radius',
            [
                'label' => esc_html__('Dropdown Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall__rating-select' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Read more style controls.
     *
     * @return void
     */
    protected function register_style_read_more_controls(): void
    {
        $this->start_controls_section(
            'kng_tw_style_read_more',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Read More', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_tw_read_more_typography',
                'selector' => '{{WRAPPER}} .king-addons-testimonials-wall__read-more',
            ]
        );

        $this->start_controls_tabs('kng_tw_read_more_tabs');

        $this->start_controls_tab(
            'kng_tw_read_more_tab_normal',
            [
                'label' => esc_html__('Normal', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_tw_read_more_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall__read-more' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'kng_tw_read_more_tab_hover',
            [
                'label' => esc_html__('Hover', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_tw_read_more_color_hover',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall__read-more:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->add_responsive_control(
            'kng_tw_read_more_spacing',
            [
                'label' => esc_html__('Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 30],
                    'em' => ['min' => 0, 'max' => 2],
                    'rem' => ['min' => 0, 'max' => 2],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall__read-more' => 'margin-top: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Load more button style controls.
     *
     * @return void
     */
    protected function register_style_load_more_controls(): void
    {
        $this->start_controls_section(
            'kng_tw_style_load_more',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Load More Button', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_tw_load_more_typography',
                'selector' => '{{WRAPPER}} .king-addons-testimonials-wall__load-more-btn',
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_tw_load_more_border',
                'selector' => '{{WRAPPER}} .king-addons-testimonials-wall__load-more-btn',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_tw_load_more_shadow',
                'selector' => '{{WRAPPER}} .king-addons-testimonials-wall__load-more-btn',
            ]
        );

        $this->start_controls_tabs('kng_tw_load_more_tabs');

        $this->start_controls_tab(
            'kng_tw_load_more_tab_normal',
            [
                'label' => esc_html__('Normal', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_tw_load_more_text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall__load-more-btn' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_tw_load_more_bg',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall__load-more-btn' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'kng_tw_load_more_tab_hover',
            [
                'label' => esc_html__('Hover', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_tw_load_more_text_color_hover',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall__load-more-btn:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_tw_load_more_bg_hover',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall__load-more-btn:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_tw_load_more_border_hover',
            [
                'label' => esc_html__('Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall__load-more-btn:hover' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_tw_load_more_shadow_hover',
                'selector' => '{{WRAPPER}} .king-addons-testimonials-wall__load-more-btn:hover',
            ]
        );

        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->add_responsive_control(
            'kng_tw_load_more_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', 'rem'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall__load-more-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_tw_load_more_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall__load-more-btn' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_tw_load_more_alignment',
            [
                'label' => esc_html__('Alignment', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'flex-start' => esc_html__('Left', 'king-addons'),
                    'center' => esc_html__('Center', 'king-addons'),
                    'flex-end' => esc_html__('Right', 'king-addons'),
                ],
                'default' => 'center',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall__load-more' => 'justify-content: {{VALUE}};',
                ],
                'separator' => 'before',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Empty state style controls.
     *
     * @return void
     */
    protected function register_style_empty_state_controls(): void
    {
        $this->start_controls_section(
            'kng_tw_style_empty',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Empty State', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_tw_empty_typography',
                'selector' => '{{WRAPPER}} .king-addons-testimonials-wall__empty',
            ]
        );

        $this->add_control(
            'kng_tw_empty_text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall__empty' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_tw_empty_bg',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall__empty' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_tw_empty_border',
                'selector' => '{{WRAPPER}} .king-addons-testimonials-wall__empty',
            ]
        );

        $this->add_responsive_control(
            'kng_tw_empty_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall__empty' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_tw_empty_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', 'rem'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall__empty' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_tw_empty_align',
            [
                'label' => esc_html__('Text Alignment', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'left' => esc_html__('Left', 'king-addons'),
                    'center' => esc_html__('Center', 'king-addons'),
                    'right' => esc_html__('Right', 'king-addons'),
                ],
                'default' => 'center',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall__empty' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Skeleton style controls.
     *
     * @return void
     */
    protected function register_style_skeleton_controls(): void
    {
        $this->start_controls_section(
            'kng_tw_style_skeleton',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Skeleton Loader', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'kng_tw_skeleton_base',
            [
                'label' => esc_html__('Base Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall' => '--ka-tw-skeleton-base: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_tw_skeleton_shine',
            [
                'label' => esc_html__('Shimmer Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall' => '--ka-tw-skeleton-shine: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_tw_skeleton_radius',
            [
                'label' => esc_html__('Skeleton Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-testimonials-wall__skeleton-card' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render widget output.
     *
     * @return void
     */
    public function render(): void
    {
        $settings = $this->get_settings_for_display();
        $items = $settings['kng_tw_items'] ?? [];

        if (empty($items)) {
            return;
        }

        $is_editor = $this->is_elementor_editor_context();
        $max_editor_items = isset($settings['kng_tw_editor_max_items']) ? (int) $settings['kng_tw_editor_max_items'] : 8;
        if ($is_editor && $max_editor_items > 0) {
            $items = array_slice($items, 0, $max_editor_items);
        }

        $layout = $settings['kng_tw_layout'] ?? 'masonry';
        $enable_filters = ($settings['kng_tw_enable_filters'] ?? 'yes') === 'yes';
        $filters_position = $settings['kng_tw_filters_position'] ?? 'top';
        $enable_category_filter = ($settings['kng_tw_enable_category_filter'] ?? 'yes') === 'yes';
        $enable_rating_filter = ($settings['kng_tw_enable_rating_filter'] ?? 'yes') === 'yes';
        $rating_ui = $settings['kng_tw_rating_ui'] ?? 'chips';
        $enable_search = ($settings['kng_tw_enable_search'] ?? 'yes') === 'yes';
        $search_placeholder = $settings['kng_tw_search_placeholder'] ?? esc_html__('Search testimonials', 'king-addons');

        $enable_load_more = ($settings['kng_tw_enable_load_more'] ?? 'yes') === 'yes';
        $editor_load_more_preview = ($settings['kng_tw_editor_load_more_preview'] ?? '') === 'yes';
        $render_load_more = $enable_load_more && (!$is_editor || $editor_load_more_preview);

        $initial_items = isset($settings['kng_tw_initial_items']) ? (int) $settings['kng_tw_initial_items'] : 9;
        $items_per_load = isset($settings['kng_tw_items_per_load']) ? (int) $settings['kng_tw_items_per_load'] : 6;
        $initial_items = max(1, $initial_items);
        $items_per_load = max(1, $items_per_load);

        if (!$render_load_more) {
            $initial_items = count($items);
        }

        $load_more_text = $settings['kng_tw_load_more_text'] ?? esc_html__('Load more', 'king-addons');
        $loading_text = $settings['kng_tw_loading_text'] ?? esc_html__('Loading...', 'king-addons');

        $enable_skeleton = ($settings['kng_tw_enable_skeleton'] ?? 'yes') === 'yes';
        $skeleton_count = isset($settings['kng_tw_skeleton_count']) ? (int) $settings['kng_tw_skeleton_count'] : 3;
        $skeleton_count = max(1, $skeleton_count);

        $enable_read_more = ($settings['kng_tw_enable_read_more'] ?? '') === 'yes';
        $read_more_lines = isset($settings['kng_tw_read_more_lines']) ? (int) $settings['kng_tw_read_more_lines'] : 4;
        $read_more_text = $settings['kng_tw_read_more_text'] ?? esc_html__('Read more', 'king-addons');
        $read_less_text = $settings['kng_tw_read_less_text'] ?? esc_html__('Read less', 'king-addons');
        $enable_empty_state = ($settings['kng_tw_enable_empty_state'] ?? 'yes') === 'yes';
        $empty_text = $settings['kng_tw_empty_text'] ?? esc_html__('No testimonials match your filters.', 'king-addons');

        $show_avatar = ($settings['kng_tw_show_avatar'] ?? 'yes') === 'yes';
        $show_meta = ($settings['kng_tw_show_meta'] ?? 'yes') === 'yes';
        $show_rating = ($settings['kng_tw_show_rating'] ?? 'yes') === 'yes';
        $show_date = ($settings['kng_tw_show_date'] ?? '') === 'yes';
        $show_source = ($settings['kng_tw_show_source'] ?? 'yes') === 'yes';

        $category_map = [];
        $item_categories = [];
        $has_rating = false;

        foreach ($items as $index => $item) {
            $raw_categories = is_string($item['kng_tw_categories'] ?? '') ? $item['kng_tw_categories'] : '';
            $normalized = $this->normalize_categories($raw_categories);
            $item_categories[$index] = array_keys($normalized);

            foreach ($normalized as $slug => $label) {
                if (!isset($category_map[$slug])) {
                    $category_map[$slug] = $label;
                }
            }

            $rating_value = $this->sanitize_rating($item['kng_tw_rating'] ?? 0);
            if ($rating_value > 0) {
                $has_rating = true;
            }
        }

        if (!$has_rating) {
            $enable_rating_filter = false;
        }

        $chip_style = $settings['kng_tw_chip_style'] ?? 'filled';
        $editor_masonry_preview = ($settings['kng_tw_editor_masonry_preview'] ?? '') === 'yes';

        $wrapper_classes = [
            'king-addons-testimonials-wall',
            'king-addons-testimonials-wall--layout-' . sanitize_html_class((string) $layout),
        ];

        if ($filters_position === 'left') {
            $wrapper_classes[] = 'king-addons-testimonials-wall--filters-left';
        }

        if ($enable_read_more) {
            $wrapper_classes[] = 'king-addons-testimonials-wall--read-more';
        }

        $wrapper_attributes = [
            'class' => implode(' ', $wrapper_classes),
            'data-layout' => $layout,
            'data-filters' => $enable_filters ? 'yes' : 'no',
            'data-filter-position' => $filters_position,
            'data-enable-category' => $enable_category_filter ? 'yes' : 'no',
            'data-enable-rating' => $enable_rating_filter ? 'yes' : 'no',
            'data-rating-ui' => $rating_ui,
            'data-enable-search' => $enable_search ? 'yes' : 'no',
            'data-enable-loadmore' => $render_load_more ? 'yes' : 'no',
            'data-initial' => (string) $initial_items,
            'data-per-load' => (string) $items_per_load,
            'data-read-more' => $enable_read_more ? 'yes' : 'no',
            'data-read-more-lines' => (string) max(2, $read_more_lines),
            'data-read-more-text' => $read_more_text,
            'data-read-less-text' => $read_less_text,
            'data-chip-style' => $chip_style,
            'data-editor-masonry' => $editor_masonry_preview ? 'yes' : 'no',
            'data-editor-loadmore' => $editor_load_more_preview ? 'yes' : 'no',
            'data-skeleton' => ($render_load_more && $enable_skeleton) ? 'yes' : 'no',
            'data-skeleton-count' => (string) $skeleton_count,
        ];

        $auto_cols = isset($settings['kng_tw_item_min_width']['size']) && (float) $settings['kng_tw_item_min_width']['size'] > 0;
        if ($auto_cols) {
            $wrapper_attributes['data-auto-cols'] = 'yes';
        }

        $all_label = $settings['kng_tw_all_label'] ?? esc_html__('All', 'king-addons');
        $date_format = get_option('date_format');

        $filters_enabled = $enable_filters && ($enable_category_filter || $enable_rating_filter || $enable_search);
        ?>
        <div <?php echo $this->render_attribute_string($wrapper_attributes); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
            <div class="king-addons-testimonials-wall__inner">
                <div class="king-addons-testimonials-wall__layout">
                    <?php if ($filters_enabled) : ?>
                        <div class="king-addons-testimonials-wall__filters">
                            <?php if ($enable_category_filter && !empty($category_map)) : ?>
                                <div class="king-addons-testimonials-wall__filter-group king-addons-testimonials-wall__filter-group--categories" role="group" aria-label="<?php echo esc_attr__('Filter by category', 'king-addons'); ?>">
                                    <button type="button" class="king-addons-testimonials-wall__filter-chip is-active" data-filter="*" aria-pressed="true">
                                        <?php echo esc_html($all_label); ?>
                                    </button>
                                    <?php foreach ($category_map as $slug => $label) : ?>
                                        <button type="button" class="king-addons-testimonials-wall__filter-chip" data-filter="<?php echo esc_attr($slug); ?>" aria-pressed="false">
                                            <?php echo esc_html($label); ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($enable_rating_filter) : ?>
                                <div class="king-addons-testimonials-wall__filter-group king-addons-testimonials-wall__filter-group--rating" role="group" aria-label="<?php echo esc_attr__('Filter by rating', 'king-addons'); ?>">
                                    <?php if ($rating_ui === 'dropdown') : ?>
                                        <label class="screen-reader-text" for="king-addons-testimonials-wall-rating-<?php echo esc_attr($this->get_id()); ?>">
                                            <?php echo esc_html__('Rating filter', 'king-addons'); ?>
                                        </label>
                                        <select id="king-addons-testimonials-wall-rating-<?php echo esc_attr($this->get_id()); ?>" class="king-addons-testimonials-wall__rating-select">
                                            <option value="all"><?php echo esc_html__('All ratings', 'king-addons'); ?></option>
                                            <option value="5"><?php echo esc_html__('5', 'king-addons'); ?></option>
                                            <option value="4"><?php echo esc_html__('4+', 'king-addons'); ?></option>
                                            <option value="3"><?php echo esc_html__('3+', 'king-addons'); ?></option>
                                        </select>
                                    <?php else : ?>
                                        <button type="button" class="king-addons-testimonials-wall__filter-chip is-active" data-rating="all" aria-pressed="true">
                                            <?php echo esc_html($all_label); ?>
                                        </button>
                                        <button type="button" class="king-addons-testimonials-wall__filter-chip" data-rating="5" aria-pressed="false">
                                            <?php echo esc_html__('5', 'king-addons'); ?>
                                        </button>
                                        <button type="button" class="king-addons-testimonials-wall__filter-chip" data-rating="4" aria-pressed="false">
                                            <?php echo esc_html__('4+', 'king-addons'); ?>
                                        </button>
                                        <button type="button" class="king-addons-testimonials-wall__filter-chip" data-rating="3" aria-pressed="false">
                                            <?php echo esc_html__('3+', 'king-addons'); ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($enable_search) : ?>
                                <div class="king-addons-testimonials-wall__filter-group king-addons-testimonials-wall__filter-group--search">
                                    <input type="search" class="king-addons-testimonials-wall__search-input" placeholder="<?php echo esc_attr($search_placeholder); ?>" aria-label="<?php echo esc_attr($search_placeholder); ?>">
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="king-addons-testimonials-wall__grid" role="list">
                        <?php foreach ($items as $index => $item) : ?>
                            <?php
                            $name = $item['kng_tw_name'] ?? '';
                            $title = $item['kng_tw_title'] ?? '';
                            $company = $item['kng_tw_company'] ?? '';
                            $text = $item['kng_tw_text'] ?? '';
                            $source = $item['kng_tw_source'] ?? '';
                            $rating_value = $this->sanitize_rating($item['kng_tw_rating'] ?? 0);
                            $date = $item['kng_tw_date'] ?? '';
                            $meta_parts = array_filter([trim((string) $title), trim((string) $company)]);
                            $meta = implode(' - ', $meta_parts);

                            $item_classes = [
                                'king-addons-testimonials-wall__item',
                            ];

                            if (!empty($item['_id'])) {
                                $item_classes[] = 'elementor-repeater-item-' . sanitize_html_class((string) $item['_id']);
                            }

                            if ($index < $initial_items) {
                                $item_classes[] = 'is-visible';
                            } else {
                                $item_classes[] = 'is-hidden';
                            }

                            $categories_list = $item_categories[$index] ?? [];
                            $data_categories = !empty($categories_list) ? implode(' ', array_map('sanitize_html_class', $categories_list)) : '';

                            $search_blob = implode(' ', array_filter([
                                $name,
                                $title,
                                $company,
                                wp_strip_all_tags((string) $text),
                                $source,
                                implode(' ', $categories_list),
                            ]));

                            $item_attributes = [
                                'class' => implode(' ', $item_classes),
                                'data-rating' => (string) $rating_value,
                                'data-categories' => $data_categories,
                                'data-search' => sanitize_text_field($search_blob),
                                'role' => 'listitem',
                            ];
                            ?>
                            <article <?php echo $this->render_attribute_string($item_attributes); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
                                <div class="king-addons-testimonials-wall__card">
                                    <?php if ($show_avatar || !empty($name) || !empty($meta)) : ?>
                                        <div class="king-addons-testimonials-wall__header">
                                            <?php if ($show_avatar && !empty($item['kng_tw_avatar']['url'])) : ?>
                                                <div class="king-addons-testimonials-wall__avatar">
                                                    <?php
                                                    $image_settings = $item;
                                                    if (isset($settings['kng_tw_avatar_size_size'])) {
                                                        $image_settings['kng_tw_avatar_size_size'] = $settings['kng_tw_avatar_size_size'];
                                                    }
                                                    if (isset($settings['kng_tw_avatar_size_custom_dimension'])) {
                                                        $image_settings['kng_tw_avatar_size_custom_dimension'] = $settings['kng_tw_avatar_size_custom_dimension'];
                                                    }
                                                    $avatar_html = Group_Control_Image_Size::get_attachment_image_html($image_settings, 'kng_tw_avatar_size', 'kng_tw_avatar');
                                                    echo wp_kses_post($avatar_html);
                                                    ?>
                                                </div>
                                            <?php endif; ?>

                                            <div class="king-addons-testimonials-wall__author">
                                                <?php if (!empty($name)) : ?>
                                                    <div class="king-addons-testimonials-wall__name"><?php echo esc_html($name); ?></div>
                                                <?php endif; ?>
                                                <?php if ($show_meta && !empty($meta)) : ?>
                                                    <div class="king-addons-testimonials-wall__meta"><?php echo esc_html($meta); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($show_rating && $rating_value > 0) : ?>
                                        <div class="king-addons-testimonials-wall__rating" role="img" aria-label="<?php echo esc_attr(sprintf(esc_html__('Rated %1$s out of 5', 'king-addons'), $rating_value)); ?>">
                                            <?php $this->render_rating($rating_value); ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="king-addons-testimonials-wall__text king-addons-testimonials-wall__searchable">
                                        <?php echo wp_kses_post($text); ?>
                                    </div>

                                    <?php if ($enable_read_more) : ?>
                                        <button type="button" class="king-addons-testimonials-wall__read-more" aria-expanded="false">
                                            <?php echo esc_html($read_more_text); ?>
                                        </button>
                                    <?php endif; ?>

                                    <?php if ($show_source || ($show_date && !empty($date))) : ?>
                                        <div class="king-addons-testimonials-wall__footer">
                                            <?php if ($show_source && !empty($source)) : ?>
                                                <span class="king-addons-testimonials-wall__source"><?php echo esc_html($source); ?></span>
                                            <?php endif; ?>
                                            <?php if ($show_date && !empty($date)) : ?>
                                                <span class="king-addons-testimonials-wall__date">
                                                    <?php echo esc_html(date_i18n($date_format, strtotime($date))); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($enable_empty_state && !empty($empty_text)) : ?>
                        <div class="king-addons-testimonials-wall__empty" role="status" aria-live="polite">
                            <?php echo esc_html($empty_text); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($render_load_more) : ?>
                        <div class="king-addons-testimonials-wall__load-more">
                            <button type="button" class="king-addons-testimonials-wall__load-more-btn" data-load-text="<?php echo esc_attr($load_more_text); ?>" data-loading-text="<?php echo esc_attr($loading_text); ?>" aria-label="<?php echo esc_attr($load_more_text); ?>">
                                <?php echo esc_html($load_more_text); ?>
                            </button>
                        </div>
                    <?php endif; ?>

                    <?php if ($render_load_more && $enable_skeleton) : ?>
                        <div class="king-addons-testimonials-wall__skeletons" aria-hidden="true">
                            <?php for ($i = 0; $i < $skeleton_count; $i++) : ?>
                                <div class="king-addons-testimonials-wall__skeleton-card"></div>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render rating stars.
     *
     * @param int $rating Rating value.
     *
     * @return void
     */
    protected function render_rating(int $rating): void
    {
        $rating = max(0, min(5, $rating));
        ?>
        <div class="king-addons-testimonials-wall__stars" aria-hidden="true">
            <?php for ($i = 1; $i <= 5; $i++) : ?>
                <span class="king-addons-testimonials-wall__star<?php echo ($i <= $rating) ? ' is-filled' : ''; ?>">
                    <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
                        <path d="M12 2l2.9 6.1 6.7.9-4.9 4.8 1.2 6.7L12 17.8l-5.9 3.1 1.2-6.7-4.9-4.8 6.7-.9L12 2z"/>
                    </svg>
                </span>
            <?php endfor; ?>
        </div>
        <?php
    }

    /**
     * Register Pro notice controls.
     *
     * @return void
     */
    protected function register_pro_notice_controls(): void
    {
        if (!king_addons_can_use_pro()) {
            Core::renderProFeaturesSection($this, '', Controls_Manager::RAW_HTML, 'testimonials-wall', [
                'CPT and WooCommerce review sources with ACF mapping',
                'Sorting: newest, highest rating, featured first',
                'Featured highlight with badge and 2x masonry cards',
                'Schema.org Review & AggregateRating JSON-LD',
                'AJAX filtering, infinite scroll, and caching layer',
            ]);
        }
    }

    /**
     * Normalize category list into slug => label pairs.
     *
     * @param string $raw Raw category string.
     *
     * @return array<string, string>
     */
    protected function normalize_categories(string $raw): array
    {
        $labels = preg_split('/[,;]+/', $raw) ?: [];
        $normalized = [];

        foreach ($labels as $label) {
            $label = trim($label);
            if ($label === '') {
                continue;
            }
            $slug = sanitize_title($label);
            if ($slug === '') {
                continue;
            }
            if (!isset($normalized[$slug])) {
                $normalized[$slug] = $label;
            }
        }

        return $normalized;
    }

    /**
     * Sanitize rating value.
     *
     * @param mixed $value Raw rating value.
     *
     * @return int
     */
    protected function sanitize_rating($value): int
    {
        $rating = is_numeric($value) ? (int) $value : 0;
        return max(0, min(5, $rating));
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
     * Detect if Elementor editor/preview is active.
     *
     * @return bool
     */
    protected function is_elementor_editor_context(): bool
    {
        if (isset($_GET['elementor-preview'])) {
            return true;
        }

        if (!defined('ELEMENTOR_VERSION') || !class_exists(Plugin::class)) {
            return false;
        }

        $plugin = Plugin::$instance;

        if (isset($plugin->editor) && is_object($plugin->editor) && method_exists($plugin->editor, 'is_edit_mode')) {
            if ($plugin->editor->is_edit_mode()) {
                return true;
            }
        }

        if (isset($plugin->preview) && is_object($plugin->preview) && method_exists($plugin->preview, 'is_preview_mode')) {
            if ($plugin->preview->is_preview_mode()) {
                return true;
            }
        }

        return false;
    }
}
