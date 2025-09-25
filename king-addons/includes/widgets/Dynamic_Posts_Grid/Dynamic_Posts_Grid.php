<?php

/** @noinspection SpellCheckingInspection, DuplicatedCode, PhpUnused */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Dynamic Posts Grid Widget
 * 
 * Creates a dynamic grid of posts with AJAX filtering, search, and load more functionality.
 * Features different post types, color coding, and responsive design.
 */
class Dynamic_Posts_Grid extends Widget_Base
{

    /**
     * Get widget name.
     *
     * @return string Widget name.
     */
    public function get_name(): string
    {
        return 'king-addons-dynamic-posts-grid';
    }

    /**
     * Get widget title.
     *
     * @return string Widget title.
     */
    public function get_title(): string
    {
        return esc_html__('Dynamic Posts Grid', 'king-addons');
    }

    /**
     * Get widget icon.
     *
     * @return string Widget icon.
     */
    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-dynamic-posts-grid';
    }

    /**
     * Get widget style dependencies.
     *
     * @return array Style dependencies.
     */
    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-dynamic-posts-grid-style'];
    }

    /**
     * Get widget script dependencies.
     *
     * @return array Script dependencies.
     */
    public function get_script_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-dynamic-posts-grid-script'];
    }

    /**
     * Get widget categories.
     *
     * @return array Widget categories.
     */
    public function get_categories(): array
    {
        return ['king-addons'];
    }

    /**
     * Get widget keywords.
     *
     * @return array Widget keywords.
     */
    public function get_keywords(): array
    {
        return [
            'posts',
            'grid',
            'dynamic',
            'ajax',
            'filter',
            'search',
            'load more',
            'pagination',
            'isotope',
            'masonry',
            'blog',
            'news',
            'articles',
            'content',
            'king',
            'addons',
            'kingaddons',
            'king-addons'
        ];
    }

    /**
     * Get widget help URL.
     *
     * @return string Help URL.
     */
    public function get_custom_help_url()
    {
        return 'mailto:bug@kingaddons.com?subject=Bug Report - King Addons&body=Please describe the issue';
    }

    /**
     * Register widget controls.
     */
    protected function register_controls(): void
    {
        // START TAB: CONTENT
        $this->register_content_controls();

        // START TAB: STYLE
        $this->register_style_controls();
    }

    /**
     * Register content controls.
     */
    private function register_content_controls(): void
    {
        // Content Section
        $this->start_controls_section(
            'kng_dynamic_posts_grid_content_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Content', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        // Editor notice
        $this->add_control(
            'kng_dynamic_posts_editor_notice',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => '<div style="background: #e3f2fd; border: 1px solid #2196f3; border-radius: 4px; padding: 12px; margin-bottom: 15px; color: #1565c0;">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                <i class="eicon-info-circle" style="color: #2196f3;"></i>
                <strong>' . esc_html__('Editor Notice', 'king-addons') . '</strong>
            </div>
            <p style="margin: 0; font-size: 13px; line-height: 1.4;">' .
                    esc_html__('Dynamic features (filtering, search, load more) work only on the live site and will not function in the Elementor editor preview.', 'king-addons') .
                    '</p>
        </div>',
            ]
        );

        // PRO: Widget Mode Control
        $this->add_control_widget_mode();

        // Post Types (shown only in Default mode)
        $this->add_control(
            'kng_dynamic_posts_post_types',
            [
                'label' => esc_html__('Post Types', 'king-addons'),
                'type' => Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => $this->get_post_types(),
                'default' => ['post'],
                'description' => esc_html__('Select post types to display', 'king-addons'),
                'condition' => [
                    'kng_dynamic_posts_widget_mode' => ['', 'default'],
                ],
            ]
        );

        // PRO: Custom Post Types Control
        $this->add_control_custom_post_types();

        // Posts per page
        $this->add_control(
            'kng_dynamic_posts_per_page',
            [
                'label' => esc_html__('Posts Per Page', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 100,
                'step' => 1,
                'default' => 12,
                'description' => esc_html__('Number of posts to display initially', 'king-addons'),
            ]
        );

        // Order by
        $this->add_control(
            'kng_dynamic_posts_orderby',
            [
                'label' => esc_html__('Order By', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'date' => esc_html__('Date', 'king-addons'),
                    'title' => esc_html__('Title', 'king-addons'),
                    'menu_order' => esc_html__('Menu Order', 'king-addons'),
                    'rand' => esc_html__('Random', 'king-addons'),
                    'comment_count' => esc_html__('Comment Count', 'king-addons'),
                ],
                'default' => 'date',
            ]
        );

        // Order
        $this->add_control(
            'kng_dynamic_posts_order',
            [
                'label' => esc_html__('Order', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'DESC' => esc_html__('Descending', 'king-addons'),
                    'ASC' => esc_html__('Ascending', 'king-addons'),
                ],
                'default' => 'DESC',
                'condition' => [
                    'kng_dynamic_posts_orderby!' => 'rand',
                ],
            ]
        );

        $this->end_controls_section();

        // Filtering Section
        $this->start_controls_section(
            'kng_dynamic_posts_filtering_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Filtering & Search', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        // Enable filters
        $this->add_control(
            'kng_dynamic_posts_enable_filters',
            [
                'label' => esc_html__('Enable Filters', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        // Filter title text
        $this->add_control(
            'kng_dynamic_posts_filter_title_text',
            [
                'label' => esc_html__('Filter Title Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Filter', 'king-addons'),
                'placeholder' => esc_html__('Enter filter title', 'king-addons'),
                'condition' => [
                    'kng_dynamic_posts_enable_filters' => 'yes',
                ],
            ]
        );

        // Filter taxonomy (shown only in Default mode)
        $this->add_control(
            'kng_dynamic_posts_filter_taxonomy',
            [
                'label' => esc_html__('Filter Taxonomy', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => $this->get_taxonomies(),
                'default' => 'category',
                'condition' => [
                    'kng_dynamic_posts_enable_filters' => 'yes',
                    'kng_dynamic_posts_widget_mode' => ['', 'default'],
                ],
            ]
        );

        // Enable search
        $this->add_control(
            'kng_dynamic_posts_enable_search',
            [
                'label' => esc_html__('Enable Search', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        // Search placeholder
        $this->add_control(
            'kng_dynamic_posts_search_placeholder',
            [
                'label' => esc_html__('Search Placeholder', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Search by keyword', 'king-addons'),
                'condition' => [
                    'kng_dynamic_posts_enable_search' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        // Grid Layout Section
        $this->start_controls_section(
            'kng_dynamic_posts_layout_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Grid Layout', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        // Columns
        $this->add_responsive_control(
            'kng_dynamic_posts_columns',
            [
                'label' => esc_html__('Columns', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => '3',
                'tablet_default' => '2',
                'mobile_default' => '1',
                'options' => [
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr);',
                ],
            ]
        );

        // Grid gap
        $this->add_responsive_control(
            'kng_dynamic_posts_grid_gap',
            [
                'label' => esc_html__('Grid Gap', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'size' => 20,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-grid' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Grid Spacing
        $this->start_controls_section(
            'kng_dynamic_posts_grid_spacing_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Grid Spacing', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        // Grid margin bottom
        $this->add_responsive_control(
            'kng_dynamic_posts_grid_margin_bottom',
            [
                'label' => esc_html__('Grid Bottom Margin', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'size' => 50,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-grid' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Load More Section
        $this->start_controls_section(
            'kng_dynamic_posts_load_more_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Load More', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        // Enable load more
        $this->add_control(
            'kng_dynamic_posts_enable_load_more',
            [
                'label' => esc_html__('Enable Load More', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        // Load more text
        $this->add_control(
            'kng_dynamic_posts_load_more_text',
            [
                'label' => esc_html__('Load More Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('LOAD MORE', 'king-addons'),
                'condition' => [
                    'kng_dynamic_posts_enable_load_more' => 'yes',
                ],
            ]
        );

        // Loading text
        $this->add_control(
            'kng_dynamic_posts_loading_text',
            [
                'label' => esc_html__('Loading Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Loading...', 'king-addons'),
                'condition' => [
                    'kng_dynamic_posts_enable_load_more' => 'yes',
                ],
            ]
        );

        // PRO: Pagination Type Control
        $this->add_control_pagination_type();

        // PRO: Scroll Threshold Control
        $this->add_control_scroll_threshold();

        $this->end_controls_section();

        // PRO: CPT Actions Section  
        $this->start_controls_section(
            'kng_dynamic_posts_cpt_actions_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('CPT Action Types', 'king-addons') . ' ' . esc_html__('(PRO)', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
                'condition' => [
                    'kng_dynamic_posts_widget_mode' => 'custom_cpt',
                ],
            ]
        );

        // PRO: CPT Actions Control
        $this->add_control_cpt_actions();

        $this->end_controls_section();

        // PRO: Meta Fields Section
        $this->start_controls_section(
            'kng_dynamic_posts_meta_fields_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Meta Fields', 'king-addons') . ' ' . esc_html__('(PRO)', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        // PRO: Show Meta Control
        $this->add_control_show_meta();

        // PRO: Meta Fields Control
        $this->add_control_meta_fields();

        $this->end_controls_section();

        // Category Colors Section
        $this->start_controls_section(
            'kng_dynamic_posts_category_colors_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Category Colors', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
                'condition' => [
                    'kng_dynamic_posts_widget_mode' => ['', 'default']
                ]
            ]
        );

        // Get categories dynamically
        $this->add_dynamic_category_colors();

        $this->end_controls_section();

        // CPT Colors Section (PRO)
        $this->start_controls_section(
            'kng_dynamic_posts_cpt_colors_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('CPT Colors', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
                'condition' => [
                    'kng_dynamic_posts_widget_mode' => 'custom_cpt'
                ]
            ]
        );

        // PRO: CPT Colors
        $this->add_control_cpt_colors();

        $this->end_controls_section();

        $this->start_controls_section(
            'kng_dynamic_posts_category_button_colors_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Category Button Colors', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
                'condition' => [
                    'kng_dynamic_posts_widget_mode' => ['', 'default']
                ]
            ]
        );

        // Dynamic category button colors
        $this->add_dynamic_category_button_colors();

        $this->end_controls_section();

        // CPT Button Colors Section (PRO)
        $this->start_controls_section(
            'kng_dynamic_posts_cpt_button_colors_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('CPT Button Colors', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
                'condition' => [
                    'kng_dynamic_posts_widget_mode' => 'custom_cpt'
                ]
            ]
        );

        // PRO: CPT Button Colors
        $this->add_control_cpt_button_colors();

        $this->end_controls_section();



        // Category CTAs Section
        $this->start_controls_section(
            'kng_dynamic_posts_ctas_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Category Button Text', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
                'condition' => [
                    'kng_dynamic_posts_widget_mode' => ['', 'default']
                ]
            ]
        );

        // Get categories dynamically
        $this->add_dynamic_category_ctas();

        $this->end_controls_section();

        // CPT Button Text Section (PRO)
        $this->start_controls_section(
            'kng_dynamic_posts_cpt_ctas_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('CPT Button Text', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
                'condition' => [
                    'kng_dynamic_posts_widget_mode' => 'custom_cpt'
                ]
            ]
        );

        // PRO: CPT Button Text
        $this->add_control_cpt_ctas();

        $this->end_controls_section();

        // Category Icons Section
        $this->start_controls_section(
            'kng_dynamic_posts_icons_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Category Icons', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
                'condition' => [
                    'kng_dynamic_posts_widget_mode' => ['', 'default']
                ]
            ]
        );

        // Get categories dynamically
        $this->add_dynamic_category_icons();

        $this->end_controls_section();

        // CPT Icons Section (PRO)
        $this->start_controls_section(
            'kng_dynamic_posts_cpt_icons_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('CPT Icons', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
                'condition' => [
                    'kng_dynamic_posts_widget_mode' => 'custom_cpt'
                ]
            ]
        );

        // PRO: CPT Icons
        $this->add_control_cpt_icons();

        $this->end_controls_section();

        // Pro Features Section
        if (!king_addons_freemius()->can_use_premium_code__premium_only()) {
            Core::renderProFeaturesSection($this, '', Controls_Manager::RAW_HTML, 'dynamic-posts-grid', [
                'Advanced Query Builder',
                'Custom Meta Fields Display',
                'Advanced Filtering Options',
                'Infinite Scroll',
                'Custom Post Type Support',
                'Advanced Search with Meta Fields',
                'Custom Date Range Filters',
                'Author-based Filtering'
            ]);
        }

        // PRO extension point to append content controls without duplicating base controls
        // Pro content controls are added via add_control_* method calls above
    }

    /**
     * Register style controls.
     */
    private function register_style_controls(): void
    {
        // Filter Title Style
        $this->start_controls_section(
            'kng_dynamic_posts_filter_title_style_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Filter Title', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'kng_dynamic_posts_enable_filters' => 'yes',
                ],
            ]
        );

        // Filter title typography
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_dynamic_posts_filter_title_typography',
                'label' => esc_html__('Typography', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-dpg-filter-title',
                'fields_options' => [
                    'typography' => [
                        'default' => 'yes',
                    ],
                    'font_size' => [
                        'default' => [
                            'size' => 36,
                            'unit' => 'px',
                        ],
                    ],
                    'font_weight' => [
                        'default' => '600',
                    ],
                ],
            ]
        );

        // Filter title color
        $this->add_control(
            'kng_dynamic_posts_filter_title_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#2c3e50',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-filter-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        // Filter title spacing
        $this->add_responsive_control(
            'kng_dynamic_posts_filter_title_margin',
            [
                'label' => esc_html__('Margin', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'default' => [
                    'top' => 0,
                    'right' => 0,
                    'bottom' => 20,
                    'left' => 0,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-filter-title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Filter Bar Style
        $this->start_controls_section(
            'kng_dynamic_posts_filter_style_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Filter Bar', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        // Filter bar background
        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name' => 'kng_dynamic_posts_filter_background',
                'label' => esc_html__('Background', 'king-addons'),
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .king-addons-dpg-filter-bar',
            ]
        );

        // Filter bar padding
        $this->add_responsive_control(
            'kng_dynamic_posts_filter_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-filter-bar' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        // Filter bar border
        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_dynamic_posts_filter_border',
                'label' => esc_html__('Border', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-dpg-filter-bar',
            ]
        );

        $this->end_controls_section();

        // Filter Section Spacing
        $this->start_controls_section(
            'kng_dynamic_posts_filter_spacing_style_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Filter Section Spacing', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'kng_dynamic_posts_enable_filters' => 'yes',
                ],
            ]
        );

        // Filter bar margin bottom
        $this->add_responsive_control(
            'kng_dynamic_posts_filter_bar_margin_bottom',
            [
                'label' => esc_html__('Filter Bar Bottom Margin', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'size' => 40,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-filter-bar' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        // Filter header margin bottom
        $this->add_responsive_control(
            'kng_dynamic_posts_filter_header_margin_bottom',
            [
                'label' => esc_html__('Filter Header Bottom Margin', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                ],
                'default' => [
                    'size' => 20,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-filter-header' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        // Filter controls gap
        $this->add_responsive_control(
            'kng_dynamic_posts_filter_controls_gap',
            [
                'label' => esc_html__('Controls Gap', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 60,
                    ],
                ],
                'default' => [
                    'size' => 30,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-filter-controls' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        // Filter controls alignment
        $this->add_responsive_control(
            'kng_dynamic_posts_filter_controls_alignment',
            [
                'label' => esc_html__('Controls Alignment', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'space-between',
                'options' => [
                    'flex-start' => esc_html__('Start', 'king-addons'),
                    'center' => esc_html__('Center', 'king-addons'),
                    'flex-end' => esc_html__('End', 'king-addons'),
                    'space-between' => esc_html__('Space Between', 'king-addons'),
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-filter-controls' => 'justify-content: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Filter Controls Style
        $this->start_controls_section(
            'kng_dynamic_posts_filter_controls_style_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Filter Controls', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'kng_dynamic_posts_enable_filters' => 'yes',
                ],
            ]
        );

        // Filter dropdown styles
        $this->add_control(
            'kng_dynamic_posts_filter_dropdown_heading',
            [
                'label' => esc_html__('Filter Dropdown', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        // Filter dropdown typography
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_dynamic_posts_filter_dropdown_typography',
                'label' => esc_html__('Typography', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-dpg-posts-filter',
                'fields_options' => [
                    'typography' => [
                        'default' => 'yes',
                    ],
                    'font_size' => [
                        'default' => [
                            'size' => 14,
                            'unit' => 'px',
                        ],
                    ],
                    'font_weight' => [
                        'default' => '500',
                    ],
                ],
            ]
        );

        // Filter dropdown colors
        $this->add_control(
            'kng_dynamic_posts_filter_dropdown_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#374151',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-posts-filter' => 'color: {{VALUE}};',
                ],
            ]
        );

        // Filter dropdown background
        $this->add_control(
            'kng_dynamic_posts_filter_dropdown_background',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-posts-filter' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        // Filter dropdown border
        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_dynamic_posts_filter_dropdown_border',
                'label' => esc_html__('Border', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-dpg-posts-filter',
                'fields_options' => [
                    'border' => [
                        'default' => 'solid',
                    ],
                    'width' => [
                        'default' => [
                            'top' => 1,
                            'right' => 1,
                            'bottom' => 1,
                            'left' => 1,
                            'unit' => 'px',
                        ],
                    ],
                    'color' => [
                        'default' => '#E5E7EB',
                    ],
                ],
            ]
        );

        // Filter dropdown border radius
        $this->add_responsive_control(
            'kng_dynamic_posts_filter_dropdown_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'default' => [
                    'top' => 12,
                    'right' => 12,
                    'bottom' => 12,
                    'left' => 12,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-posts-filter' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        // Filter dropdown padding
        $this->add_responsive_control(
            'kng_dynamic_posts_filter_dropdown_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'default' => [
                    'top' => 12,
                    'right' => 40,
                    'bottom' => 12,
                    'left' => 16,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-posts-filter' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        // Filter dropdown box shadow
        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_dynamic_posts_filter_dropdown_box_shadow',
                'label' => esc_html__('Box Shadow', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-dpg-posts-filter',
                'fields_options' => [
                    'box_shadow_type' => [
                        'default' => 'yes',
                    ],
                    'box_shadow' => [
                        'default' => [
                            'horizontal' => 0,
                            'vertical' => 1,
                            'blur' => 2,
                            'spread' => 0,
                            'color' => 'rgba(0, 0, 0, 0.05)',
                        ],
                    ],
                ],
            ]
        );

        // Filter dropdown icon
        $this->add_control(
            'kng_dynamic_posts_filter_dropdown_icon_heading',
            [
                'label' => esc_html__('Dropdown Icon', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        // Use custom dropdown icon/image
        $this->add_control(
            'kng_dynamic_posts_filter_dropdown_use_custom',
            [
                'label' => esc_html__('Use Custom Icon/Image', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => 'no',
                'description' => esc_html__('Replace default dropdown arrow with custom icon or image', 'king-addons'),
            ]
        );

        // Choose dropdown type (icon or image)
        $this->add_control(
            'kng_dynamic_posts_filter_dropdown_type',
            [
                'label' => esc_html__('Type', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'icon' => esc_html__('Icon', 'king-addons'),
                    'image' => esc_html__('Image', 'king-addons'),
                ],
                'default' => 'icon',
                'condition' => [
                    'kng_dynamic_posts_filter_dropdown_use_custom' => 'yes',
                ],
            ]
        );

        // Choose custom dropdown icon
        $this->add_control(
            'kng_dynamic_posts_filter_dropdown_custom_icon',
            [
                'label' => esc_html__('Choose Icon', 'king-addons'),
                'type' => Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fas fa-chevron-down',
                    'library' => 'fa-solid',
                ],
                'condition' => [
                    'kng_dynamic_posts_filter_dropdown_use_custom' => 'yes',
                    'kng_dynamic_posts_filter_dropdown_type' => 'icon',
                ],
            ]
        );

        // Choose custom dropdown image
        $this->add_control(
            'kng_dynamic_posts_filter_dropdown_custom_image',
            [
                'label' => esc_html__('Choose Image', 'king-addons'),
                'type' => Controls_Manager::MEDIA,
                'media_types' => ['image'],
                'default' => [
                    'url' => '',
                ],
                'condition' => [
                    'kng_dynamic_posts_filter_dropdown_use_custom' => 'yes',
                    'kng_dynamic_posts_filter_dropdown_type' => 'image',
                ],
            ]
        );

        // Dropdown icon size
        $this->add_control(
            'kng_dynamic_posts_filter_dropdown_icon_size',
            [
                'label' => esc_html__('Icon Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 8,
                        'max' => 30,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'size' => 14,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-filter-dropdown .dropdown-icon' => 'font-size: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .king-addons-dpg-filter-dropdown .dropdown-icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .king-addons-dpg-filter-dropdown .dropdown-icon i' => 'font-size: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'kng_dynamic_posts_filter_dropdown_use_custom' => 'yes',
                    'kng_dynamic_posts_filter_dropdown_type' => 'icon',
                ],
            ]
        );

        // Dropdown image size
        $this->add_control(
            'kng_dynamic_posts_filter_dropdown_image_size',
            [
                'label' => esc_html__('Image Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', '%'],
                'range' => [
                    'px' => [
                        'min' => 8,
                        'max' => 100,
                        'step' => 1,
                    ],
                    'em' => [
                        'min' => 0.5,
                        'max' => 5,
                        'step' => 0.1,
                    ],
                    '%' => [
                        'min' => 50,
                        'max' => 200,
                        'step' => 5,
                    ],
                ],
                'default' => [
                    'size' => 16,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-filter-dropdown .dropdown-image' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .king-addons-dpg-filter-dropdown .dropdown-image img' => 'width: 100%; height: 100%; object-fit: contain;',
                ],
                'condition' => [
                    'kng_dynamic_posts_filter_dropdown_use_custom' => 'yes',
                    'kng_dynamic_posts_filter_dropdown_type' => 'image',
                ],
            ]
        );

        // Dropdown icon color
        $this->add_control(
            'kng_dynamic_posts_filter_dropdown_icon_color',
            [
                'label' => esc_html__('Icon Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#6B7280',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-filter-dropdown .dropdown-icon' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-dpg-filter-dropdown .dropdown-icon svg' => 'fill: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-dpg-filter-dropdown .dropdown-icon i' => 'color: {{VALUE}};',
                ],
                'condition' => [
                    'kng_dynamic_posts_filter_dropdown_use_custom' => 'yes',
                    'kng_dynamic_posts_filter_dropdown_type' => 'icon',
                ],
            ]
        );

        // Dropdown image opacity
        $this->add_control(
            'kng_dynamic_posts_filter_dropdown_image_opacity',
            [
                'label' => esc_html__('Image Opacity', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'min' => 0.1,
                        'max' => 1,
                        'step' => 0.1,
                    ],
                ],
                'default' => [
                    'size' => 0.8,
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-filter-dropdown .dropdown-image' => 'opacity: {{SIZE}};',
                ],
                'condition' => [
                    'kng_dynamic_posts_filter_dropdown_use_custom' => 'yes',
                    'kng_dynamic_posts_filter_dropdown_type' => 'image',
                ],
            ]
        );

        // Search input styles
        $this->add_control(
            'kng_dynamic_posts_search_input_heading',
            [
                'label' => esc_html__('Search Input', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'kng_dynamic_posts_enable_search' => 'yes',
                ],
            ]
        );

        // Search input typography
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_dynamic_posts_search_input_typography',
                'label' => esc_html__('Typography', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-dpg-posts-search',
                'condition' => [
                    'kng_dynamic_posts_enable_search' => 'yes',
                ],
                'fields_options' => [
                    'typography' => [
                        'default' => 'yes',
                    ],
                    'font_size' => [
                        'default' => [
                            'size' => 14,
                            'unit' => 'px',
                        ],
                    ],
                ],
            ]
        );

        // Search input colors
        $this->add_control(
            'kng_dynamic_posts_search_input_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#374151',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-posts-search' => 'color: {{VALUE}};',
                ],
                'condition' => [
                    'kng_dynamic_posts_enable_search' => 'yes',
                ],
            ]
        );

        // Search input placeholder color
        $this->add_control(
            'kng_dynamic_posts_search_placeholder_color',
            [
                'label' => esc_html__('Placeholder Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#9CA3AF',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-posts-search::placeholder' => 'color: {{VALUE}};',
                ],
                'condition' => [
                    'kng_dynamic_posts_enable_search' => 'yes',
                ],
            ]
        );

        // Search input background
        $this->add_control(
            'kng_dynamic_posts_search_input_background',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-search-input' => 'background-color: {{VALUE}};',
                ],
                'condition' => [
                    'kng_dynamic_posts_enable_search' => 'yes',
                ],
            ]
        );

        // Search input border
        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_dynamic_posts_search_input_border',
                'label' => esc_html__('Border', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-dpg-search-input',
                'condition' => [
                    'kng_dynamic_posts_enable_search' => 'yes',
                ],
                'fields_options' => [
                    'border' => [
                        'default' => 'solid',
                    ],
                    'width' => [
                        'default' => [
                            'top' => 1,
                            'right' => 1,
                            'bottom' => 1,
                            'left' => 1,
                            'unit' => 'px',
                        ],
                    ],
                    'color' => [
                        'default' => '#E5E7EB',
                    ],
                ],
            ]
        );

        // Search input border radius
        $this->add_responsive_control(
            'kng_dynamic_posts_search_input_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'default' => [
                    'top' => 12,
                    'right' => 12,
                    'bottom' => 12,
                    'left' => 12,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-search-input' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'condition' => [
                    'kng_dynamic_posts_enable_search' => 'yes',
                ],
            ]
        );

        // Search input padding
        $this->add_responsive_control(
            'kng_dynamic_posts_search_input_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'default' => [
                    'top' => 12,
                    'right' => 16,
                    'bottom' => 12,
                    'left' => 16,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-posts-search' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'condition' => [
                    'kng_dynamic_posts_enable_search' => 'yes',
                ],
            ]
        );

        // Search input box shadow
        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_dynamic_posts_search_input_box_shadow',
                'label' => esc_html__('Box Shadow', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-dpg-search-input',
                'condition' => [
                    'kng_dynamic_posts_enable_search' => 'yes',
                ],
                'fields_options' => [
                    'box_shadow_type' => [
                        'default' => 'yes',
                    ],
                    'box_shadow' => [
                        'default' => [
                            'horizontal' => 0,
                            'vertical' => 1,
                            'blur' => 2,
                            'spread' => 0,
                            'color' => 'rgba(0, 0, 0, 0.05)',
                        ],
                    ],
                ],
            ]
        );

        // Search button heading
        $this->add_control(
            'kng_dynamic_posts_search_button_heading',
            [
                'label' => esc_html__('Search Button', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'kng_dynamic_posts_enable_search' => 'yes',
                ],
            ]
        );

        // Search button size
        $this->add_responsive_control(
            'kng_dynamic_posts_search_button_size',
            [
                'label' => esc_html__('Button Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 30,
                        'max' => 80,
                    ],
                ],
                'default' => [
                    'size' => 44,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-search-btn' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'kng_dynamic_posts_enable_search' => 'yes',
                ],
            ]
        );

        // Search button color
        $this->add_control(
            'kng_dynamic_posts_search_button_color',
            [
                'label' => esc_html__('Button Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#6B7280',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-search-btn' => 'color: {{VALUE}};',
                ],
                'condition' => [
                    'kng_dynamic_posts_enable_search' => 'yes',
                ],
            ]
        );

        // Search button background
        $this->add_control(
            'kng_dynamic_posts_search_button_background',
            [
                'label' => esc_html__('Button Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => 'transparent',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-search-btn' => 'background-color: {{VALUE}};',
                ],
                'condition' => [
                    'kng_dynamic_posts_enable_search' => 'yes',
                ],
            ]
        );

        // Search button hover color
        $this->add_control(
            'kng_dynamic_posts_search_button_hover_color',
            [
                'label' => esc_html__('Button Hover Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#374151',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-search-btn:hover' => 'color: {{VALUE}};',
                ],
                'condition' => [
                    'kng_dynamic_posts_enable_search' => 'yes',
                ],
            ]
        );

        // Search button hover background
        $this->add_control(
            'kng_dynamic_posts_search_button_hover_background',
            [
                'label' => esc_html__('Button Hover Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => 'rgba(59, 130, 246, 0.05)',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-search-btn:hover' => 'background-color: {{VALUE}};',
                ],
                'condition' => [
                    'kng_dynamic_posts_enable_search' => 'yes',
                ],
            ]
        );

        // Search button border
        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_dynamic_posts_search_button_border',
                'label' => esc_html__('Button Border', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-dpg-search-btn',
                'condition' => [
                    'kng_dynamic_posts_enable_search' => 'yes',
                ],
            ]
        );

        // Search button border radius
        $this->add_responsive_control(
            'kng_dynamic_posts_search_button_border_radius',
            [
                'label' => esc_html__('Button Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-search-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'condition' => [
                    'kng_dynamic_posts_enable_search' => 'yes',
                ],
            ]
        );

        // Search button padding
        $this->add_responsive_control(
            'kng_dynamic_posts_search_button_padding',
            [
                'label' => esc_html__('Button Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'default' => [
                    'top' => 8,
                    'right' => 12,
                    'bottom' => 8,
                    'left' => 12,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-search-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'condition' => [
                    'kng_dynamic_posts_enable_search' => 'yes',
                ],
            ]
        );

        // Search button transition duration
        $this->add_control(
            'kng_dynamic_posts_search_button_transition',
            [
                'label' => esc_html__('Transition Duration (ms)', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'min' => 100,
                        'max' => 1000,
                        'step' => 50,
                    ],
                ],
                'default' => [
                    'size' => 300,
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-search-btn' => 'transition: all {{SIZE}}ms ease;',
                ],
                'condition' => [
                    'kng_dynamic_posts_enable_search' => 'yes',
                ],
            ]
        );

        // Search button icon heading
        $this->add_control(
            'kng_dynamic_posts_search_button_icon_heading',
            [
                'label' => esc_html__('Search Button Icon', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'kng_dynamic_posts_enable_search' => 'yes',
                ],
            ]
        );

        // Use custom icon
        $this->add_control(
            'kng_dynamic_posts_search_use_custom_icon',
            [
                'label' => esc_html__('Use Custom Icon', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => '',
                'condition' => [
                    'kng_dynamic_posts_enable_search' => 'yes',
                ],
            ]
        );

        // Custom icon
        $this->add_control(
            'kng_dynamic_posts_search_custom_icon',
            [
                'label' => esc_html__('Choose Icon', 'king-addons'),
                'type' => Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fas fa-search',
                    'library' => 'fa-solid',
                ],
                'condition' => [
                    'kng_dynamic_posts_enable_search' => 'yes',
                    'kng_dynamic_posts_search_use_custom_icon' => 'yes',
                ],
            ]
        );

        // Icon size
        $this->add_responsive_control(
            'kng_dynamic_posts_search_icon_size',
            [
                'label' => esc_html__('Icon Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 10,
                        'max' => 50,
                    ],
                ],
                'default' => [
                    'size' => 18,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-search-btn svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .king-addons-dpg-search-btn i' => 'font-size: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'kng_dynamic_posts_enable_search' => 'yes',
                ],
            ]
        );

        // Icon color
        $this->add_control(
            'kng_dynamic_posts_search_icon_color',
            [
                'label' => esc_html__('Icon Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-search-btn svg' => 'fill: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-dpg-search-btn i' => 'color: {{VALUE}};',
                ],
                'condition' => [
                    'kng_dynamic_posts_enable_search' => 'yes',
                ],
            ]
        );

        // Icon hover color
        $this->add_control(
            'kng_dynamic_posts_search_icon_hover_color',
            [
                'label' => esc_html__('Icon Hover Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-search-btn:hover svg' => 'fill: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-dpg-search-btn:hover i' => 'color: {{VALUE}};',
                ],
                'condition' => [
                    'kng_dynamic_posts_enable_search' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        // Card Style
        $this->start_controls_section(
            'kng_dynamic_posts_card_style_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Cards', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        // Icon Position
        $this->add_control(
            'kng_dynamic_posts_icon_position',
            [
                'label' => esc_html__('Icon Position', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'left',
                'options' => [
                    'left' => esc_html__('Left', 'king-addons'),
                    'right' => esc_html__('Right', 'king-addons'),
                ],
                'prefix_class' => 'king-addons-icon-',
            ]
        );

        // Card background color
        $this->add_control(
            'kng_dynamic_posts_card_background_color',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#F8F9FA',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-card' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        // Card border
        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_dynamic_posts_card_border',
                'label' => esc_html__('Border', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-dpg-card',
                'fields_options' => [
                    'border' => [
                        'default' => 'solid',
                    ],
                    'width' => [
                        'default' => [
                            'top' => 1,
                            'right' => 1,
                            'bottom' => 1,
                            'left' => 1,
                            'unit' => 'px',
                        ],
                    ],
                    'color' => [
                        'default' => 'rgba(0, 0, 0, 0.05)',
                    ],
                ],
            ]
        );

        // Card border radius
        $this->add_responsive_control(
            'kng_dynamic_posts_card_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'default' => [
                    'top' => 20,
                    'right' => 20,
                    'bottom' => 20,
                    'left' => 20,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        // Card min height
        $this->add_responsive_control(
            'kng_dynamic_posts_card_min_height',
            [
                'label' => esc_html__('Min Height', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'vh'],
                'range' => [
                    'px' => [
                        'min' => 100,
                        'max' => 800,
                        'step' => 10,
                    ],
                    'vh' => [
                        'min' => 10,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'size' => 260,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-card' => 'min-height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        // Card box shadow
        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_dynamic_posts_card_box_shadow',
                'label' => esc_html__('Box Shadow', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-dpg-card',
                'fields_options' => [
                    'box_shadow_type' => [
                        'default' => 'yes',
                    ],
                    'box_shadow' => [
                        'default' => [
                            'horizontal' => 0,
                            'vertical' => 1,
                            'blur' => 3,
                            'spread' => 0,
                            'color' => 'rgba(0, 0, 0, 0.1)',
                        ],
                    ],
                ],
            ]
        );

        // Card typography
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_dynamic_posts_card_typography',
                'label' => esc_html__('Typography', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-dpg-card',
            ]
        );

        $this->end_controls_section();

        // Card Hover Effects
        $this->start_controls_section(
            'kng_dynamic_posts_card_hover_style_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Card Hover Effects', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        // Hover transform
        $this->add_control(
            'kng_dynamic_posts_card_hover_transform',
            [
                'label' => esc_html__('Hover Transform', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'translateY(-4px)',
                'options' => [
                    'none' => esc_html__('None', 'king-addons'),
                    'translateY(-2px)' => esc_html__('Lift Small', 'king-addons'),
                    'translateY(-4px)' => esc_html__('Lift Medium', 'king-addons'),
                    'translateY(-6px)' => esc_html__('Lift Large', 'king-addons'),
                    'scale(1.02)' => esc_html__('Scale Small', 'king-addons'),
                    'scale(1.05)' => esc_html__('Scale Medium', 'king-addons'),
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-card:hover' => 'transform: {{VALUE}};',
                ],
            ]
        );

        // Hover box shadow
        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_dynamic_posts_card_hover_box_shadow',
                'label' => esc_html__('Hover Box Shadow', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-dpg-card:hover',
                'fields_options' => [
                    'box_shadow_type' => [
                        'default' => 'yes',
                    ],
                    'box_shadow' => [
                        'default' => [
                            'horizontal' => 0,
                            'vertical' => 10,
                            'blur' => 25,
                            'spread' => 0,
                            'color' => 'rgba(0, 0, 0, 0.15)',
                        ],
                    ],
                ],
            ]
        );

        // Hover border color
        $this->add_control(
            'kng_dynamic_posts_card_hover_border_color',
            [
                'label' => esc_html__('Hover Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => 'rgba(0, 0, 0, 0.1)',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-card:hover' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        // Transition duration
        $this->add_control(
            'kng_dynamic_posts_card_transition_duration',
            [
                'label' => esc_html__('Transition Duration (ms)', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'min' => 100,
                        'max' => 1000,
                        'step' => 50,
                    ],
                ],
                'default' => [
                    'size' => 300,
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-card' => 'transition: all {{SIZE}}ms cubic-bezier(0.4, 0, 0.2, 1);',
                ],
            ]
        );

        // Card clickable
        $this->add_control(
            'kng_dynamic_posts_card_clickable',
            [
                'label' => esc_html__('Make Card Clickable', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => 'no',
                'description' => esc_html__('Makes the entire card clickable to navigate to the post. Note: This feature works only on the live site.', 'king-addons'),
            ]
        );

        // Card clickable notice for editor
        $this->add_control(
            'kng_dynamic_posts_card_clickable_notice',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => '<div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px; padding: 12px; margin-top: 10px; color: #856404;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                        <i class="eicon-info-circle" style="color: #ffc107;"></i>
                        <strong>' . esc_html__('Card Clickable Notice', 'king-addons') . '</strong>
                    </div>
                    <p style="margin: 0; font-size: 13px; line-height: 1.4;">' .
                        esc_html__('Card clicking functionality is disabled in the Elementor editor to prevent interference with editing. It will work properly on the live site.', 'king-addons') .
                        '</p>
                </div>',
                'condition' => [
                    'kng_dynamic_posts_card_clickable' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        // Card Header Style
        $this->start_controls_section(
            'kng_dynamic_posts_card_header_style_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Card Header', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        // Header padding
        $this->add_responsive_control(
            'kng_dynamic_posts_header_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'default' => [
                    'top' => 24,
                    'right' => 24,
                    'bottom' => 20,
                    'left' => 24,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-header' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        // Icon styles
        $this->add_control(
            'kng_dynamic_posts_icon_heading',
            [
                'label' => esc_html__('Icon', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        // Icon size
        $this->add_responsive_control(
            'kng_dynamic_posts_icon_size',
            [
                'label' => esc_html__('Icon Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 12,
                        'max' => 50,
                    ],
                ],
                'default' => [
                    'size' => 20,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-icon i' => 'font-size: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .king-addons-dpg-icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .king-addons-dpg-icon img' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        // Icon color
        $this->add_control(
            'kng_dynamic_posts_icon_color',
            [
                'label' => esc_html__('Icon Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#6B7280',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-icon i' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-dpg-icon svg' => 'fill: {{VALUE}};',
                ],
            ]
        );

        // Icon background
        $this->add_control(
            'kng_dynamic_posts_icon_background',
            [
                'label' => esc_html__('Icon Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => 'rgba(255, 255, 255, 0.7)',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-icon' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        // Icon border radius
        $this->add_responsive_control(
            'kng_dynamic_posts_icon_border_radius',
            [
                'label' => esc_html__('Icon Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'default' => [
                    'top' => 12,
                    'right' => 12,
                    'bottom' => 12,
                    'left' => 12,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-icon' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        // Icon container size
        $this->add_responsive_control(
            'kng_dynamic_posts_icon_container_size',
            [
                'label' => esc_html__('Icon Container Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 30,
                        'max' => 80,
                    ],
                ],
                'default' => [
                    'size' => 44,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        // Icon box shadow
        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_dynamic_posts_icon_box_shadow',
                'label' => esc_html__('Icon Box Shadow', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-dpg-icon',
                'fields_options' => [
                    'box_shadow_type' => [
                        'default' => 'yes',
                    ],
                    'box_shadow' => [
                        'default' => [
                            'horizontal' => 0,
                            'vertical' => 2,
                            'blur' => 4,
                            'spread' => 0,
                            'color' => 'rgba(0, 0, 0, 0.1)',
                        ],
                    ],
                ],
            ]
        );

        // Label styles
        $this->add_control(
            'kng_dynamic_posts_label_heading',
            [
                'label' => esc_html__('Label', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        // Label typography
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_dynamic_posts_label_typography',
                'label' => esc_html__('Label Typography', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-dpg-label',
                'fields_options' => [
                    'typography' => [
                        'default' => 'yes',
                    ],
                    'font_size' => [
                        'default' => [
                            'size' => 11,
                            'unit' => 'px',
                        ],
                    ],
                    'font_weight' => [
                        'default' => '600',
                    ],
                    'text_transform' => [
                        'default' => 'uppercase',
                    ],
                    'letter_spacing' => [
                        'default' => [
                            'size' => 1,
                            'unit' => 'px',
                        ],
                    ],
                ],
            ]
        );

        // Label color
        $this->add_control(
            'kng_dynamic_posts_label_color',
            [
                'label' => esc_html__('Label Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#6B7280',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-label' => 'color: {{VALUE}};',
                ],
            ]
        );

        // Label padding
        $this->add_responsive_control(
            'kng_dynamic_posts_label_padding',
            [
                'label' => esc_html__('Label Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'default' => [
                    'top' => 4,
                    'right' => 8,
                    'bottom' => 4,
                    'left' => 8,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-label' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        // Label border
        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_dynamic_posts_label_border',
                'label' => esc_html__('Label Border', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-dpg-label',
                'fields_options' => [
                    'border' => [
                        'default' => 'solid',
                    ],
                    'width' => [
                        'default' => [
                            'top' => 1,
                            'right' => 1,
                            'bottom' => 1,
                            'left' => 1,
                            'unit' => 'px',
                        ],
                    ],
                    'color' => [
                        'default' => '#E5E7EB',
                    ],
                ],
            ]
        );

        // Label border radius
        $this->add_responsive_control(
            'kng_dynamic_posts_label_border_radius',
            [
                'label' => esc_html__('Label Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'default' => [
                    'top' => 4,
                    'right' => 4,
                    'bottom' => 4,
                    'left' => 4,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-label' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        // Label background
        $this->add_control(
            'kng_dynamic_posts_label_background',
            [
                'label' => esc_html__('Label Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => 'transparent',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-label' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Card Content Style
        $this->start_controls_section(
            'kng_dynamic_posts_card_content_style_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Card Content', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        // Content padding
        $this->add_responsive_control(
            'kng_dynamic_posts_content_padding',
            [
                'label' => esc_html__('Content Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'default' => [
                    'top' => 0,
                    'right' => 24,
                    'bottom' => 20,
                    'left' => 24,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        // Content vertical alignment
        $this->add_responsive_control(
            'kng_dynamic_posts_content_vertical_alignment',
            [
                'label' => esc_html__('Content Vertical Alignment', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'flex-start',
                'options' => [
                    'flex-start' => esc_html__('Top', 'king-addons'),
                    'center' => esc_html__('Center', 'king-addons'),
                    'flex-end' => esc_html__('Bottom', 'king-addons'),
                    'space-between' => esc_html__('Space Between', 'king-addons'),
                    'space-around' => esc_html__('Space Around', 'king-addons'),
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-content' => 'justify-content: {{VALUE}};',
                ],
            ]
        );

        // Show excerpt
        $this->add_control(
            'kng_dynamic_posts_show_excerpt',
            [
                'label' => esc_html__('Show Excerpt', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Show', 'king-addons'),
                'label_off' => esc_html__('Hide', 'king-addons'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        // Title typography
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_dynamic_posts_title_typography',
                'label' => esc_html__('Title Typography', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-dpg-title',
                'fields_options' => [
                    'typography' => [
                        'default' => 'yes',
                    ],
                    'font_size' => [
                        'default' => [
                            'size' => 16,
                            'unit' => 'px',
                        ],
                    ],
                    'font_weight' => [
                        'default' => '500',
                    ],
                    'line_height' => [
                        'default' => [
                            'size' => 1.5,
                            'unit' => 'em',
                        ],
                    ],
                ],
            ]
        );

        // Title color
        $this->add_control(
            'kng_dynamic_posts_title_color',
            [
                'label' => esc_html__('Title Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#374151',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-title a' => 'color: {{VALUE}};',
                ],
            ]
        );

        // Title hover color
        $this->add_control(
            'kng_dynamic_posts_title_hover_color',
            [
                'label' => esc_html__('Title Hover Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#1F2937',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-title a:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        // Excerpt heading
        $this->add_control(
            'kng_dynamic_posts_excerpt_heading',
            [
                'label' => esc_html__('Excerpt', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'kng_dynamic_posts_show_excerpt' => 'yes',
                ],
            ]
        );

        // Excerpt typography
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_dynamic_posts_excerpt_typography',
                'label' => esc_html__('Excerpt Typography', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-dpg-excerpt',
                'condition' => [
                    'kng_dynamic_posts_show_excerpt' => 'yes',
                ],
                'fields_options' => [
                    'typography' => [
                        'default' => 'yes',
                    ],
                    'font_size' => [
                        'default' => [
                            'size' => 14,
                            'unit' => 'px',
                        ],
                    ],
                    'line_height' => [
                        'default' => [
                            'size' => 1.5,
                            'unit' => 'em',
                        ],
                    ],
                ],
            ]
        );

        // Excerpt color
        $this->add_control(
            'kng_dynamic_posts_excerpt_color',
            [
                'label' => esc_html__('Excerpt Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#6B7280',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-excerpt' => 'color: {{VALUE}};',
                ],
                'condition' => [
                    'kng_dynamic_posts_show_excerpt' => 'yes',
                ],
            ]
        );

        // Excerpt margin
        $this->add_responsive_control(
            'kng_dynamic_posts_excerpt_margin',
            [
                'label' => esc_html__('Excerpt Margin', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'default' => [
                    'top' => 8,
                    'right' => 0,
                    'bottom' => 0,
                    'left' => 0,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-excerpt' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'condition' => [
                    'kng_dynamic_posts_show_excerpt' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        // Card Button Style
        $this->start_controls_section(
            'kng_dynamic_posts_card_button_style_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Card Button', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        // Button container padding
        $this->add_responsive_control(
            'kng_dynamic_posts_button_container_padding',
            [
                'label' => esc_html__('Container Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'default' => [
                    'top' => 0,
                    'right' => 24,
                    'bottom' => 24,
                    'left' => 24,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-cta' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        // Button typography
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_dynamic_posts_button_typography',
                'label' => esc_html__('Button Typography', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-dpg-button',
                'fields_options' => [
                    'typography' => [
                        'default' => 'yes',
                    ],
                    'font_size' => [
                        'default' => [
                            'size' => 11,
                            'unit' => 'px',
                        ],
                    ],
                    'font_weight' => [
                        'default' => '500',
                    ],
                    'text_transform' => [
                        'default' => 'uppercase',
                    ],
                    'letter_spacing' => [
                        'default' => [
                            'size' => 0.5,
                            'unit' => 'px',
                        ],
                    ],
                ],
            ]
        );

        // Button border style
        $this->add_control(
            'kng_dynamic_posts_button_border_style',
            [
                'label' => esc_html__('Border Style', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'none' => esc_html__('None', 'king-addons'),
                    'solid' => esc_html__('Solid', 'king-addons'),
                    'dashed' => esc_html__('Dashed', 'king-addons'),
                    'dotted' => esc_html__('Dotted', 'king-addons'),
                ],
                'default' => 'solid',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-button' => 'border-style: {{VALUE}};',
                ],
            ]
        );

        // Button border width  
        $this->add_responsive_control(
            'kng_dynamic_posts_button_border_width',
            [
                'label' => esc_html__('Border Width', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px'],
                'default' => [
                    'top' => 1,
                    'right' => 1,
                    'bottom' => 1,
                    'left' => 1,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-button' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'condition' => [
                    'kng_dynamic_posts_button_border_style!' => 'none',
                ],
            ]
        );

        // Default button border color
        $this->add_control(
            'kng_dynamic_posts_default_button_border_color',
            [
                'label' => esc_html__('Default Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => 'rgba(0, 0, 0, 0.1)',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-button' => 'border-color: {{VALUE}};',
                ],
                'condition' => [
                    'kng_dynamic_posts_button_border_style!' => 'none',
                ],
            ]
        );

        // Button border radius
        $this->add_responsive_control(
            'kng_dynamic_posts_button_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'default' => [
                    'top' => 20,
                    'right' => 20,
                    'bottom' => 20,
                    'left' => 20,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        // Button padding
        $this->add_responsive_control(
            'kng_dynamic_posts_button_padding',
            [
                'label' => esc_html__('Button Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'default' => [
                    'top' => 10,
                    'right' => 20,
                    'bottom' => 10,
                    'left' => 20,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        // Button box shadow
        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_dynamic_posts_button_box_shadow',
                'label' => esc_html__('Box Shadow', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-dpg-button',
                'fields_options' => [
                    'box_shadow_type' => [
                        'default' => 'yes',
                    ],
                    'box_shadow' => [
                        'default' => [
                            'horizontal' => 0,
                            'vertical' => 1,
                            'blur' => 2,
                            'spread' => 0,
                            'color' => 'rgba(0, 0, 0, 0.05)',
                        ],
                    ],
                ],
            ]
        );

        $this->end_controls_section();

        // Load More Button Style
        $this->start_controls_section(
            'kng_dynamic_posts_load_more_style_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Load More Button', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        // Button typography
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_dynamic_posts_load_more_typography',
                'label' => esc_html__('Typography', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-dpg-load-more-btn',
                'fields_options' => [
                    'typography' => [
                        'default' => 'yes',
                    ],
                    'font_size' => [
                        'default' => [
                            'size' => 14,
                            'unit' => 'px',
                        ],
                    ],
                    'font_weight' => [
                        'default' => '600',
                    ],
                    'text_transform' => [
                        'default' => 'uppercase',
                    ],
                    'letter_spacing' => [
                        'default' => [
                            'size' => 0.5,
                            'unit' => 'px',
                        ],
                    ],
                ],
            ]
        );

        // Button text color
        $this->add_control(
            'kng_dynamic_posts_load_more_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#1F2937',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-load-more-btn' => 'color: {{VALUE}};',
                ],
            ]
        );

        // Button background
        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name' => 'kng_dynamic_posts_load_more_background',
                'label' => esc_html__('Background', 'king-addons'),
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .king-addons-dpg-load-more-btn',
                'fields_options' => [
                    'background' => [
                        'default' => 'classic',
                    ],
                    'color' => [
                        'default' => '#FCD34D',
                    ],
                ],
            ]
        );

        // Button hover color
        $this->add_control(
            'kng_dynamic_posts_load_more_hover_color',
            [
                'label' => esc_html__('Hover Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#1F2937',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-load-more-btn:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        // Button hover background
        $this->add_control(
            'kng_dynamic_posts_load_more_hover_background',
            [
                'label' => esc_html__('Hover Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#F59E0B',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-load-more-btn:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        // Button border
        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_dynamic_posts_load_more_border',
                'label' => esc_html__('Border', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-dpg-load-more-btn',
            ]
        );

        // Button border radius
        $this->add_responsive_control(
            'kng_dynamic_posts_load_more_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'default' => [
                    'top' => 24,
                    'right' => 24,
                    'bottom' => 24,
                    'left' => 24,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-load-more-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        // Button padding
        $this->add_responsive_control(
            'kng_dynamic_posts_load_more_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'default' => [
                    'top' => 16,
                    'right' => 48,
                    'bottom' => 16,
                    'left' => 48,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-load-more-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        // Button box shadow
        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_dynamic_posts_load_more_box_shadow',
                'label' => esc_html__('Box Shadow', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-dpg-load-more-btn',
                'fields_options' => [
                    'box_shadow_type' => [
                        'default' => 'yes',
                    ],
                    'box_shadow' => [
                        'default' => [
                            'horizontal' => 0,
                            'vertical' => 2,
                            'blur' => 4,
                            'spread' => 0,
                            'color' => 'rgba(252, 211, 77, 0.3)',
                        ],
                    ],
                ],
            ]
        );

        $this->end_controls_section();

        // Pagination Style
        $this->start_controls_section(
            'kng_dynamic_posts_pagination_style_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Pagination', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'kng_dynamic_posts_enable_load_more' => 'yes',
                ],
            ]
        );

        // Pagination section margin top
        $this->add_responsive_control(
            'kng_dynamic_posts_pagination_margin_top',
            [
                'label' => esc_html__('Section Top Margin', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'size' => 40,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-pagination' => 'margin-top: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        // Pagination info typography
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_dynamic_posts_pagination_info_typography',
                'label' => esc_html__('Info Typography', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-dpg-pagination-info',
                'fields_options' => [
                    'typography' => [
                        'default' => 'yes',
                    ],
                    'font_size' => [
                        'default' => [
                            'size' => 14,
                            'unit' => 'px',
                        ],
                    ],
                ],
            ]
        );

        // Pagination info color
        $this->add_control(
            'kng_dynamic_posts_pagination_info_color',
            [
                'label' => esc_html__('Info Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#6B7280',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-pagination-info' => 'color: {{VALUE}};',
                ],
            ]
        );

        // Count numbers color
        $this->add_control(
            'kng_dynamic_posts_pagination_count_color',
            [
                'label' => esc_html__('Count Numbers Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#374151',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-current-count, {{WRAPPER}} .king-addons-dpg-total-count' => 'color: {{VALUE}};',
                ],
            ]
        );

        // Pagination info margin
        $this->add_responsive_control(
            'kng_dynamic_posts_pagination_info_margin',
            [
                'label' => esc_html__('Info Bottom Margin', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 60,
                    ],
                ],
                'default' => [
                    'size' => 30,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-pagination-info' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        // Loading text styles
        $this->add_control(
            'kng_dynamic_posts_loading_heading',
            [
                'label' => esc_html__('Loading Text', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        // Loading text color
        $this->add_control(
            'kng_dynamic_posts_loading_color',
            [
                'label' => esc_html__('Loading Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#666',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-pagination-loading' => 'color: {{VALUE}};',
                ],
            ]
        );

        // Finish text styles
        $this->add_control(
            'kng_dynamic_posts_finish_heading',
            [
                'label' => esc_html__('Finish Text', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        // Finish text typography
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_dynamic_posts_finish_typography',
                'label' => esc_html__('Finish Typography', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-dpg-pagination-finish',
                'fields_options' => [
                    'typography' => [
                        'default' => 'yes',
                    ],
                    'font_size' => [
                        'default' => [
                            'size' => 14,
                            'unit' => 'px',
                        ],
                    ],
                ],
            ]
        );

        // Finish text color
        $this->add_control(
            'kng_dynamic_posts_finish_color',
            [
                'label' => esc_html__('Finish Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#6B7280',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-pagination-finish' => 'color: {{VALUE}};',
                ],
            ]
        );

        // Finish text margin
        $this->add_responsive_control(
            'kng_dynamic_posts_finish_margin',
            [
                'label' => esc_html__('Finish Text Margin', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'default' => [
                    'top' => 30,
                    'right' => 0,
                    'bottom' => 20,
                    'left' => 0,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-pagination-finish' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Messages Style
        $this->start_controls_section(
            'kng_dynamic_posts_messages_style_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Messages', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        // No posts message
        $this->add_control(
            'kng_dynamic_posts_no_posts_heading',
            [
                'label' => esc_html__('No Posts Message', 'king-addons'),
                'type' => Controls_Manager::HEADING,
            ]
        );

        // No posts typography
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_dynamic_posts_no_posts_typography',
                'label' => esc_html__('Typography', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-dpg-no-posts',
                'fields_options' => [
                    'typography' => [
                        'default' => 'yes',
                    ],
                    'font_size' => [
                        'default' => [
                            'size' => 16,
                            'unit' => 'px',
                        ],
                    ],
                ],
            ]
        );

        // No posts color
        $this->add_control(
            'kng_dynamic_posts_no_posts_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#666',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-no-posts' => 'color: {{VALUE}};',
                ],
            ]
        );

        // No posts background
        $this->add_control(
            'kng_dynamic_posts_no_posts_background',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#f8f9fa',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-no-posts' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        // No posts border
        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_dynamic_posts_no_posts_border',
                'label' => esc_html__('Border', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-dpg-no-posts',
                'fields_options' => [
                    'border' => [
                        'default' => 'dashed',
                    ],
                    'width' => [
                        'default' => [
                            'top' => 2,
                            'right' => 2,
                            'bottom' => 2,
                            'left' => 2,
                            'unit' => 'px',
                        ],
                    ],
                    'color' => [
                        'default' => '#ddd',
                    ],
                ],
            ]
        );

        // No posts border radius
        $this->add_responsive_control(
            'kng_dynamic_posts_no_posts_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'default' => [
                    'top' => 8,
                    'right' => 8,
                    'bottom' => 8,
                    'left' => 8,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-no-posts' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        // No posts padding
        $this->add_responsive_control(
            'kng_dynamic_posts_no_posts_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'default' => [
                    'top' => 60,
                    'right' => 20,
                    'bottom' => 60,
                    'left' => 20,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-no-posts' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        // Error message
        $this->add_control(
            'kng_dynamic_posts_error_heading',
            [
                'label' => esc_html__('Error Message', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        // Error message typography
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_dynamic_posts_error_typography',
                'label' => esc_html__('Typography', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-dpg-error-message',
                'fields_options' => [
                    'typography' => [
                        'default' => 'yes',
                    ],
                    'font_size' => [
                        'default' => [
                            'size' => 14,
                            'unit' => 'px',
                        ],
                    ],
                ],
            ]
        );

        // Error message colors
        $this->add_control(
            'kng_dynamic_posts_error_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#721c24',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-error-message' => 'color: {{VALUE}};',
                ],
            ]
        );

        // Error message background
        $this->add_control(
            'kng_dynamic_posts_error_background',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#f8d7da',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-error-message' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        // Error message border
        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_dynamic_posts_error_border',
                'label' => esc_html__('Border', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-dpg-error-message',
                'fields_options' => [
                    'border' => [
                        'default' => 'solid',
                    ],
                    'width' => [
                        'default' => [
                            'top' => 1,
                            'right' => 1,
                            'bottom' => 1,
                            'left' => 1,
                            'unit' => 'px',
                        ],
                    ],
                    'color' => [
                        'default' => '#f5c6cb',
                    ],
                ],
            ]
        );

        // Error message border radius
        $this->add_responsive_control(
            'kng_dynamic_posts_error_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'default' => [
                    'top' => 8,
                    'right' => 8,
                    'bottom' => 8,
                    'left' => 8,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-error-message' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        // Error message padding
        $this->add_responsive_control(
            'kng_dynamic_posts_error_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'default' => [
                    'top' => 12,
                    'right' => 16,
                    'bottom' => 12,
                    'left' => 16,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-error-message' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        // Error message margin
        $this->add_responsive_control(
            'kng_dynamic_posts_error_margin_bottom',
            [
                'label' => esc_html__('Bottom Margin', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                ],
                'default' => [
                    'size' => 20,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-error-message' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Get available post types.
     *
     * @return array Post types array.
     */
    protected function get_post_types(): array
    {
        $post_types = get_post_types(['public' => true], 'objects');
        $options = [];

        foreach ($post_types as $post_type) {
            $options[$post_type->name] = $post_type->label;
        }

        return $options;
    }

    /**
     * Get available taxonomies.
     *
     * @return array Taxonomies array.
     */
    protected function get_taxonomies(): array
    {
        $taxonomies = get_taxonomies(['public' => true], 'objects');
        $options = [];

        foreach ($taxonomies as $taxonomy) {
            $options[$taxonomy->name] = $taxonomy->label;
        }

        return $options;
    }

    /**
     * Add dynamic category color controls.
     */
    protected function add_dynamic_category_colors(): void
    {
        // Get taxonomy from filter settings (default to category)
        $taxonomy = 'category';

        // Get terms from the taxonomy
        $terms = get_terms([
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'number' => 20, // Limit to prevent too many controls
        ]);

        if (is_wp_error($terms) || empty($terms)) {
            // Fallback to default colors if no terms found
            $this->add_control(
                'kng_dynamic_posts_default_color',
                [
                    'label' => esc_html__('Default Color', 'king-addons'),
                    'type' => Controls_Manager::COLOR,
                    'default' => '#F8F9FA',
                    'description' => esc_html__('Default color for posts without categories', 'king-addons'),
                    'selectors' => [
                        '{{WRAPPER}} .king-addons-dpg-card' => 'background-color: {{VALUE}};',
                        '{{WRAPPER}} .king-addons-dpg-card.king-addons-dpg-category-default' => 'background-color: {{VALUE}};',
                    ],
                ]
            );
            return;
        }

        // Add instruction
        $this->add_control(
            'kng_dynamic_posts_colors_info',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => esc_html__('Set custom colors for each category. These colors will be used as background colors for post cards.', 'king-addons'),
                'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
            ]
        );

        // Default color for uncategorized posts
        $this->add_control(
            'kng_dynamic_posts_default_color',
            [
                'label' => esc_html__('Default Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#F8F9FA',
                'description' => esc_html__('Color for posts without categories', 'king-addons'),
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-card' => 'background-color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-dpg-card.king-addons-dpg-category-default' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        // Add color control for each term (matching original design)
        $default_colors = [
            '#FEF3C7', // Warm yellow/cream (like REPORT in design)
            '#DBEAFE', // Light blue (like PUBLICATION in design)
            '#FBCFE8', // Light pink (like VIDEO in design)
            '#F3F4F6', // Light gray (like WEBSITE in design)
            '#E0E7FF', // Light indigo
            '#FEE2E2', // Light red
            '#D1FAE5', // Light emerald
            '#FEF3C7', // Light amber
        ];

        $color_index = 0;
        foreach ($terms as $term) {
            $default_color = isset($default_colors[$color_index]) ? $default_colors[$color_index] : '#F8F9FA';

            $this->add_control(
                'kng_dynamic_posts_color_' . $term->slug,
                [
                    'label' => sprintf(esc_html__('%s Color', 'king-addons'), $term->name),
                    'type' => Controls_Manager::COLOR,
                    'default' => $default_color,
                    'selectors' => [
                        '{{WRAPPER}} .king-addons-dpg-card.filter-' . $term->slug => 'background-color: {{VALUE}};',
                        '{{WRAPPER}} .king-addons-dpg-card.king-addons-dpg-category-' . $term->slug => 'background-color: {{VALUE}};',
                    ],
                ]
            );

            $color_index++;
            if ($color_index >= count($default_colors)) {
                $color_index = 0; // Reset to cycle through colors
            }
        }
    }

    /**
     * Add dynamic category icon controls.
     */
    protected function add_dynamic_category_icons(): void
    {
        // Get taxonomy from filter settings (default to category)
        $taxonomy = 'category';

        // Get terms from the taxonomy
        $terms = get_terms([
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'number' => 20, // Limit to prevent too many controls
        ]);

        if (is_wp_error($terms) || empty($terms)) {
            // Fallback to default icon if no terms found
            $this->add_control(
                'kng_dynamic_posts_default_icon',
                [
                    'label' => esc_html__('Default Icon', 'king-addons'),
                    'type' => Controls_Manager::ICONS,
                    'default' => [
                        'value' => 'far fa-file-alt',
                        'library' => 'fa-regular',
                    ],
                    'description' => esc_html__('Default icon for posts without categories', 'king-addons'),
                ]
            );
            return;
        }

        // Add instruction
        $this->add_control(
            'kng_dynamic_posts_icons_info',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => esc_html__('Set custom icons for each category. These icons will be displayed in the header of post cards.', 'king-addons'),
                'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
            ]
        );

        // Default icon type
        $this->add_control(
            'kng_dynamic_posts_default_icon_type',
            [
                'label' => esc_html__('Default Type', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'icon' => esc_html__('Icon', 'king-addons'),
                    'image' => esc_html__('Image', 'king-addons'),
                ],
                'default' => 'icon',
                'description' => esc_html__('Choose type for posts without categories', 'king-addons'),
            ]
        );

        // Default icon for uncategorized posts
        $this->add_control(
            'kng_dynamic_posts_default_icon',
            [
                'label' => esc_html__('Default Icon', 'king-addons'),
                'type' => Controls_Manager::ICONS,
                'default' => [
                    'value' => 'far fa-file-alt',
                    'library' => 'fa-regular',
                ],
                'condition' => [
                    'kng_dynamic_posts_default_icon_type' => 'icon',
                ],
            ]
        );

        // Default image for uncategorized posts
        $this->add_control(
            'kng_dynamic_posts_default_image',
            [
                'label' => esc_html__('Default Image', 'king-addons'),
                'type' => Controls_Manager::MEDIA,
                'media_types' => ['image'],
                'default' => [
                    'url' => '',
                ],
                'condition' => [
                    'kng_dynamic_posts_default_icon_type' => 'image',
                ],
            ]
        );

        // Add icon control for each term
        $default_icons = [
            [
                'value' => 'far fa-file-alt',
                'library' => 'fa-regular',
            ], // Report
            [
                'value' => 'far fa-newspaper',
                'library' => 'fa-regular',
            ], // Publication
            [
                'value' => 'far fa-play-circle',
                'library' => 'fa-regular',
            ], // Video
            [
                'value' => 'fas fa-globe',
                'library' => 'fa-solid',
            ], // Website
            [
                'value' => 'far fa-star',
                'library' => 'fa-regular',
            ],
            [
                'value' => 'far fa-heart',
                'library' => 'fa-regular',
            ],
            [
                'value' => 'far fa-lightbulb',
                'library' => 'fa-regular',
            ],
            [
                'value' => 'far fa-bookmark',
                'library' => 'fa-regular',
            ],
        ];

        $icon_index = 0;
        foreach ($terms as $term) {
            $default_icon = isset($default_icons[$icon_index]) ? $default_icons[$icon_index] : [
                'value' => 'fas fa-file',
                'library' => 'fa-solid',
            ];

            // Category heading
            $this->add_control(
                'kng_dynamic_posts_' . $term->slug . '_heading',
                [
                    'label' => sprintf(esc_html__('%s Settings', 'king-addons'), $term->name),
                    'type' => Controls_Manager::HEADING,
                    'separator' => 'before',
                ]
            );

            // Icon type for this category
            $this->add_control(
                'kng_dynamic_posts_icon_type_' . $term->slug,
                [
                    'label' => sprintf(esc_html__('%s Type', 'king-addons'), $term->name),
                    'type' => Controls_Manager::SELECT,
                    'options' => [
                        'icon' => esc_html__('Icon', 'king-addons'),
                        'image' => esc_html__('Image', 'king-addons'),
                    ],
                    'default' => 'icon',
                ]
            );

            // Icon control
            $this->add_control(
                'kng_dynamic_posts_icon_' . $term->slug,
                [
                    'label' => sprintf(esc_html__('%s Icon', 'king-addons'), $term->name),
                    'type' => Controls_Manager::ICONS,
                    'default' => $default_icon,
                    'condition' => [
                        'kng_dynamic_posts_icon_type_' . $term->slug => 'icon',
                    ],
                ]
            );

            // Image control
            $this->add_control(
                'kng_dynamic_posts_image_' . $term->slug,
                [
                    'label' => sprintf(esc_html__('%s Image', 'king-addons'), $term->name),
                    'type' => Controls_Manager::MEDIA,
                    'media_types' => ['image'],
                    'default' => [
                        'url' => '',
                    ],
                    'condition' => [
                        'kng_dynamic_posts_icon_type_' . $term->slug => 'image',
                    ],
                ]
            );

            $icon_index++;
            if ($icon_index >= count($default_icons)) {
                $icon_index = 0; // Reset to cycle through icons
            }
        }
    }

    /**
     * Add dynamic category CTA controls.
     */
    protected function add_dynamic_category_ctas(): void
    {
        // Get taxonomy from filter settings (default to category)
        $taxonomy = 'category';

        // Get terms from the taxonomy
        $terms = get_terms([
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'number' => 20, // Limit to prevent too many controls
        ]);

        if (is_wp_error($terms) || empty($terms)) {
            // Fallback to default CTA if no terms found
            $this->add_control(
                'kng_dynamic_posts_default_cta',
                [
                    'label' => esc_html__('Default CTA Text', 'king-addons'),
                    'type' => Controls_Manager::TEXT,
                    'default' => 'READ MORE',
                    'description' => esc_html__('Default CTA text for posts without categories', 'king-addons'),
                ]
            );
            return;
        }

        // Add instruction
        $this->add_control(
            'kng_dynamic_posts_ctas_info',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => esc_html__('Set custom CTA button text for each category. Leave empty to use smart defaults.', 'king-addons'),
                'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
            ]
        );

        // Default CTA for uncategorized posts
        $this->add_control(
            'kng_dynamic_posts_default_cta',
            [
                'label' => esc_html__('Default CTA Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => 'READ MORE',
                'description' => esc_html__('CTA text for posts without categories', 'king-addons'),
            ]
        );

        // Add CTA control for each term
        $default_ctas = [
            'READ MORE',
            'LEARN MORE',
            'WATCH NOW',
            'DISCOVER',
            'EXPLORE',
            'VIEW MORE',
            'GET STARTED',
            'DOWNLOAD',
        ];

        $cta_index = 0;
        foreach ($terms as $term) {
            $default_cta = isset($default_ctas[$cta_index]) ? $default_ctas[$cta_index] : 'READ MORE';

            $this->add_control(
                'kng_dynamic_posts_cta_' . $term->slug,
                [
                    'label' => sprintf(esc_html__('%s CTA Text', 'king-addons'), $term->name),
                    'type' => Controls_Manager::TEXT,
                    'default' => $default_cta,
                    'placeholder' => 'READ MORE',
                    'description' => sprintf(esc_html__('Custom CTA text for %s category', 'king-addons'), $term->name),
                ]
            );

            $cta_index++;
            if ($cta_index >= count($default_ctas)) {
                $cta_index = 0; // Reset to cycle through CTAs
            }
        }
    }

    /**
     * Add dynamic category button color controls.
     */
    protected function add_dynamic_category_button_colors(): void
    {
        // Get taxonomy from filter settings (default to category)
        $taxonomy = 'category';

        // Get terms from the taxonomy
        $terms = get_terms([
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'number' => 20, // Limit to prevent too many controls
        ]);

        if (is_wp_error($terms) || empty($terms)) {
            // Fallback to default button colors if no terms found
            $this->add_control(
                'kng_dynamic_posts_button_color',
                [
                    'label' => esc_html__('Button Color', 'king-addons'),
                    'type' => Controls_Manager::COLOR,
                    'default' => '#374151',
                    'selectors' => [
                        '{{WRAPPER}} .king-addons-dpg-button' => 'color: {{VALUE}};',
                    ],
                ]
            );

            $this->add_control(
                'kng_dynamic_posts_button_background',
                [
                    'label' => esc_html__('Button Background', 'king-addons'),
                    'type' => Controls_Manager::COLOR,
                    'default' => 'rgba(255, 255, 255, 0.8)',
                    'selectors' => [
                        '{{WRAPPER}} .king-addons-dpg-button' => 'background-color: {{VALUE}};',
                    ],
                ]
            );

            $this->add_control(
                'kng_dynamic_posts_button_hover_color',
                [
                    'label' => esc_html__('Button Hover Color', 'king-addons'),
                    'type' => Controls_Manager::COLOR,
                    'default' => '#1F2937',
                    'selectors' => [
                        '{{WRAPPER}} .king-addons-dpg-button:hover' => 'color: {{VALUE}};',
                    ],
                ]
            );

            $this->add_control(
                'kng_dynamic_posts_button_hover_background',
                [
                    'label' => esc_html__('Button Hover Background', 'king-addons'),
                    'type' => Controls_Manager::COLOR,
                    'default' => 'rgba(255, 255, 255, 0.95)',
                    'selectors' => [
                        '{{WRAPPER}} .king-addons-dpg-button:hover' => 'background-color: {{VALUE}};',
                    ],
                ]
            );

            $this->add_control(
                'kng_dynamic_posts_button_border_color',
                [
                    'label' => esc_html__('Button Border Color', 'king-addons'),
                    'type' => Controls_Manager::COLOR,
                    'default' => 'rgba(0, 0, 0, 0.1)',
                    'selectors' => [
                        '{{WRAPPER}} .king-addons-dpg-button' => 'border-color: {{VALUE}};',
                    ],
                ]
            );
            return;
        }

        // Add instruction
        $this->add_control(
            'kng_dynamic_posts_button_colors_info',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => esc_html__('Set custom button colors for each category. These colors will override the general button style.', 'king-addons'),
                'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
            ]
        );

        // Default colors for uncategorized posts
        $this->add_control(
            'kng_dynamic_posts_default_button_heading',
            [
                'label' => esc_html__('Default Button Colors', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'kng_dynamic_posts_default_button_color',
            [
                'label' => esc_html__('Default Button Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#374151',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-button' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_dynamic_posts_default_button_background',
            [
                'label' => esc_html__('Default Button Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => 'rgba(255, 255, 255, 0.8)',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_dynamic_posts_default_button_hover_color',
            [
                'label' => esc_html__('Default Button Hover Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#1F2937',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-button:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_dynamic_posts_default_button_hover_background',
            [
                'label' => esc_html__('Default Button Hover Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => 'rgba(255, 255, 255, 0.95)',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-button:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_dynamic_posts_default_button_border_color',
            [
                'label' => esc_html__('Default Button Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => 'rgba(0, 0, 0, 0.1)',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-dpg-button' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        // Category-specific colors
        $default_colors = [
            ['color' => '#FFFFFF', 'bg' => '#EF4444', 'hover_color' => '#FFFFFF', 'hover_bg' => '#DC2626', 'border' => '#EF4444'], // Red
            ['color' => '#FFFFFF', 'bg' => '#3B82F6', 'hover_color' => '#FFFFFF', 'hover_bg' => '#2563EB', 'border' => '#3B82F6'], // Blue  
            ['color' => '#FFFFFF', 'bg' => '#10B981', 'hover_color' => '#FFFFFF', 'hover_bg' => '#059669', 'border' => '#10B981'], // Green
            ['color' => '#FFFFFF', 'bg' => '#F59E0B', 'hover_color' => '#FFFFFF', 'hover_bg' => '#D97706', 'border' => '#F59E0B'], // Orange
            ['color' => '#FFFFFF', 'bg' => '#8B5CF6', 'hover_color' => '#FFFFFF', 'hover_bg' => '#7C3AED', 'border' => '#8B5CF6'], // Purple
            ['color' => '#FFFFFF', 'bg' => '#EC4899', 'hover_color' => '#FFFFFF', 'hover_bg' => '#DB2777', 'border' => '#EC4899'], // Pink
            ['color' => '#FFFFFF', 'bg' => '#06B6D4', 'hover_color' => '#FFFFFF', 'hover_bg' => '#0891B2', 'border' => '#06B6D4'], // Cyan
            ['color' => '#FFFFFF', 'bg' => '#84CC16', 'hover_color' => '#FFFFFF', 'hover_bg' => '#65A30D', 'border' => '#84CC16'], // Lime
        ];

        $color_index = 0;
        foreach ($terms as $term) {
            $default_color_set = isset($default_colors[$color_index]) ? $default_colors[$color_index] : $default_colors[0];

            // Category heading
            $this->add_control(
                'kng_dynamic_posts_' . $term->slug . '_button_heading',
                [
                    'label' => sprintf(esc_html__('%s Button Colors', 'king-addons'), $term->name),
                    'type' => Controls_Manager::HEADING,
                    'separator' => 'before',
                ]
            );

            // Button color
            $this->add_control(
                'kng_dynamic_posts_button_color_' . $term->slug,
                [
                    'label' => sprintf(esc_html__('%s Button Text Color', 'king-addons'), $term->name),
                    'type' => Controls_Manager::COLOR,
                    'default' => $default_color_set['color'],
                    'selectors' => [
                        '{{WRAPPER}} .king-addons-dpg-card.king-addons-dpg-category-' . $term->slug . ' .king-addons-dpg-button' => 'color: {{VALUE}};',
                    ],
                ]
            );

            // Button background
            $this->add_control(
                'kng_dynamic_posts_button_background_' . $term->slug,
                [
                    'label' => sprintf(esc_html__('%s Button Background', 'king-addons'), $term->name),
                    'type' => Controls_Manager::COLOR,
                    'default' => $default_color_set['bg'],
                    'selectors' => [
                        '{{WRAPPER}} .king-addons-dpg-card.king-addons-dpg-category-' . $term->slug . ' .king-addons-dpg-button' => 'background-color: {{VALUE}};',
                    ],
                ]
            );

            // Button hover color
            $this->add_control(
                'kng_dynamic_posts_button_hover_color_' . $term->slug,
                [
                    'label' => sprintf(esc_html__('%s Button Hover Color', 'king-addons'), $term->name),
                    'type' => Controls_Manager::COLOR,
                    'default' => $default_color_set['hover_color'],
                    'selectors' => [
                        '{{WRAPPER}} .king-addons-dpg-card.king-addons-dpg-category-' . $term->slug . ' .king-addons-dpg-button:hover' => 'color: {{VALUE}};',
                    ],
                ]
            );

            // Button hover background
            $this->add_control(
                'kng_dynamic_posts_button_hover_background_' . $term->slug,
                [
                    'label' => sprintf(esc_html__('%s Button Hover Background', 'king-addons'), $term->name),
                    'type' => Controls_Manager::COLOR,
                    'default' => $default_color_set['hover_bg'],
                    'selectors' => [
                        '{{WRAPPER}} .king-addons-dpg-card.king-addons-dpg-category-' . $term->slug . ' .king-addons-dpg-button:hover' => 'background-color: {{VALUE}};',
                    ],
                ]
            );

            // Button border color
            $this->add_control(
                'kng_dynamic_posts_button_border_color_' . $term->slug,
                [
                    'label' => sprintf(esc_html__('%s Button Border Color', 'king-addons'), $term->name),
                    'type' => Controls_Manager::COLOR,
                    'default' => $default_color_set['border'],
                    'selectors' => [
                        '{{WRAPPER}} .king-addons-dpg-card.king-addons-dpg-category-' . $term->slug . ' .king-addons-dpg-button' => 'border-color: {{VALUE}};',
                    ],
                ]
            );

            $color_index++;
            if ($color_index >= count($default_colors)) {
                $color_index = 0; // Reset to cycle through colors
            }
        }
    }

    /**
     * Get post category for display.
     *
     * @param object $post Post object.
     * @return string Post category display name.
     */
    private function get_post_type_display($post): string
    {
        // Get post categories
        $taxonomy = 'category';
        $terms = get_the_terms($post->ID, $taxonomy);

        if ($terms && !is_wp_error($terms)) {
            // Use first category name
            $term = $terms[0];
            return strtoupper($term->name);
        }

        // Fallback to post format/type based display
        $format = get_post_format($post->ID);

        if ($format === 'video') {
            return 'VIDEO';
        } elseif ($format === 'audio') {
            return 'PUBLICATION';
        } elseif ($post->post_type === 'page') {
            return 'WEBSITE';
        } else {
            return 'POST';
        }
    }

    /**
     * Get CTA button text based on category settings.
     *
     * @param object $post Post object.
     * @param array $settings Widget settings.
     * @return string CTA button text.
     */
    private function get_cta_text($post, $settings): string
    {
        // Get post categories for custom CTA text
        $taxonomy = 'category';
        $terms = get_the_terms($post->ID, $taxonomy);

        if ($terms && !is_wp_error($terms)) {
            // Use first category's custom CTA
            $term = $terms[0];
            $cta_setting = $settings['kng_dynamic_posts_cta_' . $term->slug] ?? '';

            if (!empty($cta_setting)) {
                return strtoupper(trim($cta_setting));
            }
        }

        // Fallback to default CTA or smart detection
        $default_cta = $settings['kng_dynamic_posts_default_cta'] ?? '';
        if (!empty($default_cta)) {
            return strtoupper(trim($default_cta));
        }

        // Final fallback - smart detection
        if ($terms && !is_wp_error($terms)) {
            $term = $terms[0];
            $slug = strtolower($term->slug);

            $smart_cta_map = [
                'video' => 'WATCH NOW',
                'videos' => 'WATCH NOW',
                'website' => 'LEARN MORE',
                'websites' => 'LEARN MORE',
            ];

            if (isset($smart_cta_map[$slug])) {
                return $smart_cta_map[$slug];
            }
        }

        return 'READ MORE';
    }

    /**
     * Get post category color classes.
     *
     * @param object $post Post object.
     * @param array $settings Widget settings.
     * @return string Color class names.
     */
    private function get_post_category_color_classes($post, $settings): string
    {
        $classes = [];
        $taxonomy = $settings['kng_dynamic_posts_filter_taxonomy'] ?? 'category';

        // Get post categories/terms
        $terms = get_the_terms($post->ID, $taxonomy);

        if ($terms && !is_wp_error($terms)) {
            foreach ($terms as $term) {
                $classes[] = 'filter-' . $term->slug;
                $classes[] = 'king-addons-dpg-category-' . $term->slug;
            }
        }

        // Add default class if no terms
        if (empty($classes)) {
            $classes[] = 'king-addons-dpg-category-default';
        }

        return implode(' ', $classes);
    }

    /**
     * Get post type icon based on category settings.
     *
     * @param object $post Post object.
     * @param array $settings Widget settings.
     * @return string Icon HTML.
     */
    private function get_post_type_icon($post, $settings): string
    {
        // Get post categories/terms
        $taxonomy = 'category';
        $terms = get_the_terms($post->ID, $taxonomy);

        if ($terms && !is_wp_error($terms)) {
            // Use first category's icon/image
            $term = $terms[0];
            $icon_type = $settings['kng_dynamic_posts_icon_type_' . $term->slug] ?? 'icon';
            
            if ($icon_type === 'image') {
                // Use image
                $image_setting = $settings['kng_dynamic_posts_image_' . $term->slug] ?? null;
                if ($image_setting && !empty($image_setting['url'])) {
                    return '<img src="' . esc_url($image_setting['url']) . '" alt="' . esc_attr($term->name) . '" class="category-image" />';
                }
            } else {
                // Use icon
                $icon_setting = $settings['kng_dynamic_posts_icon_' . $term->slug] ?? null;
                if ($icon_setting && !empty($icon_setting['value'])) {
                    // Return FontAwesome icon or SVG
                    if (isset($icon_setting['library']) && $icon_setting['library'] === 'svg') {
                        return $icon_setting['value']['url'] ? '<img src="' . esc_url($icon_setting['value']['url']) . '" alt="" class="category-icon" />' : '';
                    } else {
                        return '<i class="' . esc_attr($icon_setting['value']) . ' category-icon"></i>';
                    }
                }
            }
        }

        // Fallback to default icon/image
        $default_type = $settings['kng_dynamic_posts_default_icon_type'] ?? 'icon';
        
        if ($default_type === 'image') {
            // Use default image
            $default_image = $settings['kng_dynamic_posts_default_image'] ?? null;
            if ($default_image && !empty($default_image['url'])) {
                return '<img src="' . esc_url($default_image['url']) . '" alt="' . esc_attr__('Default', 'king-addons') . '" class="category-image" />';
            }
        }
        
        // Use default icon
        $default_icon = $settings['kng_dynamic_posts_default_icon'] ?? [
            'value' => 'far fa-file-alt',
            'library' => 'fa-regular',
        ];

        if (isset($default_icon['library']) && $default_icon['library'] === 'svg') {
            return $default_icon['value']['url'] ? '<img src="' . esc_url($default_icon['value']['url']) . '" alt="" class="category-icon" />' : '';
        } else {
            return '<i class="' . esc_attr($default_icon['value']) . ' category-icon"></i>';
        }
    }

    /**
     * Render widget output on the frontend.
     */
    protected function render(): void
    {
        $settings = $this->get_settings_for_display();
        $widget_id = $this->get_id();

        // Query posts
        $query_args = [
            'post_type' => $settings['kng_dynamic_posts_post_types'],
            'posts_per_page' => $settings['kng_dynamic_posts_per_page'],
            'orderby' => $settings['kng_dynamic_posts_orderby'],
            'order' => $settings['kng_dynamic_posts_order'],
            'post_status' => 'publish',
        ];

        $posts_query = new \WP_Query($query_args);

        // Get filter terms if filtering is enabled
        $filter_terms = [];
        if ($settings['kng_dynamic_posts_enable_filters'] === 'yes') {
            $taxonomy = $settings['kng_dynamic_posts_filter_taxonomy'];
            $filter_terms = get_terms([
                'taxonomy' => $taxonomy,
                'hide_empty' => true,
            ]);
        }

        // Wrapper attributes
        $this->add_render_attribute('wrapper', 'class', 'king-addons-dpg-wrapper');
        $this->add_render_attribute('wrapper', 'data-widget-id', $widget_id);
        $this->add_render_attribute('wrapper', 'data-widget-mode', 'default'); // Set default mode for Free version
        $this->add_render_attribute('wrapper', 'data-posts-per-page', $settings['kng_dynamic_posts_per_page']);
        $this->add_render_attribute('wrapper', 'data-post-types', wp_json_encode($settings['kng_dynamic_posts_post_types']));
        $this->add_render_attribute('wrapper', 'data-orderby', $settings['kng_dynamic_posts_orderby']);
        $this->add_render_attribute('wrapper', 'data-order', $settings['kng_dynamic_posts_order']);
        $this->add_render_attribute('wrapper', 'data-show-excerpt', ($settings['kng_dynamic_posts_show_excerpt'] ?? 'yes') === 'yes' ? '1' : '0');
        $this->add_render_attribute('wrapper', 'data-card-clickable', ($settings['kng_dynamic_posts_card_clickable'] ?? 'no') === 'yes' ? '1' : '0');

        if ($settings['kng_dynamic_posts_enable_filters'] === 'yes') {
            $this->add_render_attribute('wrapper', 'data-filter-taxonomy', $settings['kng_dynamic_posts_filter_taxonomy']);
        }

        // CPT-related attributes (empty for Free version, but needed for JS compatibility)
        $this->add_render_attribute('wrapper', 'data-cpt-actions', '');
        $this->add_render_attribute('wrapper', 'data-cpt-icons', '');
?>

        <div <?php echo $this->get_render_attribute_string('wrapper'); ?>>

            <?php if ($settings['kng_dynamic_posts_enable_filters'] === 'yes' || $settings['kng_dynamic_posts_enable_search'] === 'yes'): ?>
                <!-- Filter Bar -->
                <div class="king-addons-dpg-filter-bar">

                    <!-- Filter Header -->
                    <div class="king-addons-dpg-filter-header">
                        <h2 class="king-addons-dpg-filter-title"><?php echo esc_html($settings['kng_dynamic_posts_filter_title_text']); ?></h2>
                    </div>

                    <!-- Filter Controls -->
                    <div class="king-addons-dpg-filter-controls">
                        <?php if ($settings['kng_dynamic_posts_enable_filters'] === 'yes' && !empty($filter_terms)): ?>
                            <!-- Filter Dropdown -->
                            <div class="king-addons-dpg-filter-dropdown<?php 
                                $has_custom = !empty($settings['kng_dynamic_posts_filter_dropdown_use_custom']) && $settings['kng_dynamic_posts_filter_dropdown_use_custom'] === 'yes';
                                $has_icon = $has_custom && $settings['kng_dynamic_posts_filter_dropdown_type'] === 'icon' && !empty($settings['kng_dynamic_posts_filter_dropdown_custom_icon']['value']);
                                $has_image = $has_custom && $settings['kng_dynamic_posts_filter_dropdown_type'] === 'image' && !empty($settings['kng_dynamic_posts_filter_dropdown_custom_image']['url']);
                                echo ($has_custom && ($has_icon || $has_image)) ? ' has-custom-icon' : ''; 
                            ?>">
                                <select class="king-addons-dpg-posts-filter" data-taxonomy="<?php echo esc_attr($settings['kng_dynamic_posts_filter_taxonomy']); ?>">
                                    <option value="*"><?php echo esc_html__('All resources types', 'king-addons'); ?></option>
                                    <?php foreach ($filter_terms as $term): ?>
                                        <option value="<?php echo esc_attr($term->slug); ?>"><?php echo esc_html($term->name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ($has_icon): ?>
                                    <span class="dropdown-icon">
                                        <?php \Elementor\Icons_Manager::render_icon($settings['kng_dynamic_posts_filter_dropdown_custom_icon'], ['class' => 'dropdown-custom-icon']); ?>
                                    </span>
                                <?php elseif ($has_image): ?>
                                    <span class="dropdown-image">
                                        <img src="<?php echo esc_url($settings['kng_dynamic_posts_filter_dropdown_custom_image']['url']); ?>" alt="<?php echo esc_attr__('Dropdown icon', 'king-addons'); ?>" />
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($settings['kng_dynamic_posts_enable_search'] === 'yes'): ?>
                            <!-- Search Input -->
                            <div class="king-addons-dpg-search-input">
                                <input type="text" class="king-addons-dpg-posts-search" placeholder="<?php echo esc_attr($settings['kng_dynamic_posts_search_placeholder']); ?>">
                                <button class="king-addons-dpg-search-btn" type="button">
                                    <?php if (!empty($settings['kng_dynamic_posts_search_use_custom_icon']) && $settings['kng_dynamic_posts_search_use_custom_icon'] === 'yes' && !empty($settings['kng_dynamic_posts_search_custom_icon']['value'])): ?>
                                        <?php \Elementor\Icons_Manager::render_icon($settings['kng_dynamic_posts_search_custom_icon'], ['class' => 'king-addons-dpg-search-custom-icon']); ?>
                                    <?php else: ?>
                                        <svg width="20" height="20" viewBox="0 0 24 24">
                                            <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z" />
                                        </svg>
                                    <?php endif; ?>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            <?php endif; ?>

            <!-- Grid Container -->
            <div class="king-addons-dpg-grid" data-columns="<?php echo esc_attr($settings['kng_dynamic_posts_columns']); ?>">
                <?php $this->render_posts($posts_query, $settings); ?>
            </div>

            <?php if ($settings['kng_dynamic_posts_enable_load_more'] === 'yes'): ?>
                <!-- Load More Section -->
                <div class="king-addons-dpg-pagination">

                    <!-- Posts Count Info -->
                    <div class="king-addons-dpg-pagination-info">
                        <?php echo esc_html__('Showing', 'king-addons'); ?> <span class="king-addons-dpg-current-count"><?php echo $posts_query->post_count; ?></span> <?php echo esc_html__('of', 'king-addons'); ?> <span class="king-addons-dpg-total-count"><?php echo $posts_query->found_posts; ?></span> <?php echo esc_html__('items', 'king-addons'); ?>
                    </div>

                    <div class="king-addons-dpg-pagination-loading" style="display: none;">
                        <?php echo esc_html($settings['kng_dynamic_posts_loading_text']); ?>
                    </div>

                    <?php if ($posts_query->max_num_pages > 1): ?>
                        <button class="king-addons-dpg-load-more-btn" data-page="1" data-max-pages="<?php echo esc_attr($posts_query->max_num_pages); ?>">
                            <?php echo esc_html($settings['kng_dynamic_posts_load_more_text']); ?>
                        </button>
                    <?php endif; ?>

                    <div class="king-addons-dpg-pagination-finish" style="display: none;">
                        <?php echo esc_html__('All items loaded', 'king-addons'); ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>

        <?php
        wp_reset_postdata();
    }

    /**
     * Render posts grid.
     *
     * @param \WP_Query $posts_query Posts query object.
     * @param array $settings Widget settings.
     */
    private function render_posts($posts_query, $settings): void
    {
        if (!$posts_query->have_posts()) {
            echo '<div class="king-addons-dpg-no-posts">' . esc_html__('No posts found.', 'king-addons') . '</div>';
            return;
        }

        while ($posts_query->have_posts()):
            $posts_query->the_post();
            $post = get_post();
            $post_type_display = $this->get_post_type_display($post);
            $category_classes = $this->get_post_category_color_classes($post, $settings);
            $cta_text = $this->get_cta_text($post, $settings);
            $post_icon = $this->get_post_type_icon($post, $settings);
        ?>

            <div class="king-addons-dpg-card king-addons-dpg-item <?php echo esc_attr($category_classes); ?>" data-post-id="<?php echo esc_attr($post->ID); ?>">

                <!-- Post Type Header -->
                <div class="king-addons-dpg-header">
                    <div class="king-addons-dpg-icon">
                        <?php echo $post_icon; ?>
                    </div>
                    <div class="king-addons-dpg-label">
                        <?php echo esc_html($post_type_display); ?>
                    </div>
                </div>

                <!-- Post Content -->
                <div class="king-addons-dpg-content">
                    <h3 class="king-addons-dpg-title">
                        <a href="<?php echo esc_url(get_permalink()); ?>">
                            <?php echo esc_html(get_the_title()); ?>
                        </a>
                    </h3>

                    <?php if (($settings['kng_dynamic_posts_show_excerpt'] ?? 'yes') === 'yes'): ?>
                        <div class="king-addons-dpg-excerpt">
                            <?php echo esc_html(wp_trim_words(get_the_excerpt(), 20, '...')); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- CTA Button -->
                <div class="king-addons-dpg-cta">
                    <?php echo $this->get_cta_button_html($post, $settings, $cta_text); ?>
                </div>

            </div>

<?php
        endwhile;
    }

    /**
     * Build WP_Query args. Pro overrides to support Custom CPT mode.
     *
     * @param array $settings Widget settings.
     * @return array
     */
    protected function build_query_args(array $settings): array
    {
        return [
            'post_type' => $settings['kng_dynamic_posts_post_types'],
            'posts_per_page' => $settings['kng_dynamic_posts_per_page'],
            'orderby' => $settings['kng_dynamic_posts_orderby'],
            'order' => $settings['kng_dynamic_posts_order'],
            'post_status' => 'publish',
        ];
    }

    /**
     * Build filter terms data for filter bar. Pro overrides for CPT/tabs.
     *
     * @param array $settings Widget settings.
     * @return array
     */
    protected function get_filter_terms(array $settings): array
    {
        if (($settings['kng_dynamic_posts_enable_filters'] ?? '') !== 'yes') {
            return [];
        }

        $taxonomy = $settings['kng_dynamic_posts_filter_taxonomy'] ?? 'category';
        $terms = get_terms([
            'taxonomy' => $taxonomy,
            'hide_empty' => true,
        ]);

        return is_wp_error($terms) ? [] : $terms;
    }

    /**
     * Render filter bar wrapper. Pro overrides for advanced UI.
     *
     * @param array $settings Widget settings.
     * @param array $filter_terms Terms list or structured data.
     */
    protected function render_filter_bar(array $settings, array $filter_terms): void
    {
        if (($settings['kng_dynamic_posts_enable_filters'] ?? '') !== 'yes' && ($settings['kng_dynamic_posts_enable_search'] ?? '') !== 'yes') {
            return;
        }
        ?>
        <div class="king-addons-dpg-filter-bar">
            <div class="king-addons-dpg-filter-header">
                <h2 class="king-addons-dpg-filter-title"><?php echo esc_html($settings['kng_dynamic_posts_filter_title_text'] ?? ''); ?></h2>
            </div>

            <div class="king-addons-dpg-filter-controls">
                <?php if (($settings['kng_dynamic_posts_enable_filters'] ?? '') === 'yes' && !empty($filter_terms)): ?>
                    <div class="king-addons-dpg-filter-dropdown<?php 
                        $has_custom = !empty($settings['kng_dynamic_posts_filter_dropdown_use_custom']) && $settings['kng_dynamic_posts_filter_dropdown_use_custom'] === 'yes';
                        $has_icon = $has_custom && ($settings['kng_dynamic_posts_filter_dropdown_type'] ?? 'icon') === 'icon' && !empty($settings['kng_dynamic_posts_filter_dropdown_custom_icon']['value'] ?? '');
                        $has_image = $has_custom && ($settings['kng_dynamic_posts_filter_dropdown_type'] ?? 'icon') === 'image' && !empty($settings['kng_dynamic_posts_filter_dropdown_custom_image']['url'] ?? '');
                        echo ($has_custom && ($has_icon || $has_image)) ? ' has-custom-icon' : '';
                    ?>">
                        <select class="king-addons-dpg-posts-filter" data-taxonomy="<?php echo esc_attr($settings['kng_dynamic_posts_filter_taxonomy'] ?? 'category'); ?>">
                            <option value="*">&nbsp;<?php echo esc_html__('All resources types', 'king-addons'); ?></option>
                            <?php foreach ($filter_terms as $term): ?>
                                <option value="<?php echo esc_attr($term->slug); ?>"><?php echo esc_html($term->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($has_icon): ?>
                            <span class="dropdown-icon">
                                <?php \Elementor\Icons_Manager::render_icon($settings['kng_dynamic_posts_filter_dropdown_custom_icon'], ['class' => 'dropdown-custom-icon']); ?>
                            </span>
                        <?php elseif ($has_image): ?>
                            <span class="dropdown-image">
                                <img src="<?php echo esc_url($settings['kng_dynamic_posts_filter_dropdown_custom_image']['url']); ?>" alt="<?php echo esc_attr__('Dropdown icon', 'king-addons'); ?>" />
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if (($settings['kng_dynamic_posts_enable_search'] ?? '') === 'yes'): ?>
                    <div class="king-addons-dpg-search-input">
                        <input type="text" class="king-addons-dpg-posts-search" placeholder="<?php echo esc_attr($settings['kng_dynamic_posts_search_placeholder'] ?? ''); ?>">
                        <button class="king-addons-dpg-search-btn" type="button">
                            <?php if (!empty($settings['kng_dynamic_posts_search_use_custom_icon']) && $settings['kng_dynamic_posts_search_use_custom_icon'] === 'yes' && !empty($settings['kng_dynamic_posts_search_custom_icon']['value'] ?? '')): ?>
                                <?php \Elementor\Icons_Manager::render_icon($settings['kng_dynamic_posts_search_custom_icon'], ['class' => 'king-addons-dpg-search-custom-icon']); ?>
                            <?php else: ?>
                                <svg width="20" height="20" viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z" /></svg>
                            <?php endif; ?>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render pagination wrapper. Pro overrides for advanced types.
     *
     * @param array     $settings
     * @param \WP_Query $posts_query
     */
    protected function render_pagination(array $settings, \WP_Query $posts_query): void
    {
        if (($settings['kng_dynamic_posts_enable_load_more'] ?? '') !== 'yes') {
            return;
        }
        ?>
        <div class="king-addons-dpg-pagination">
            <div class="king-addons-dpg-pagination-info">
                <?php echo esc_html__('Showing', 'king-addons'); ?> <span class="king-addons-dpg-current-count"><?php echo (int) $posts_query->post_count; ?></span> <?php echo esc_html__('of', 'king-addons'); ?> <span class="king-addons-dpg-total-count"><?php echo (int) $posts_query->found_posts; ?></span> <?php echo esc_html__('items', 'king-addons'); ?>
            </div>
            <div class="king-addons-dpg-pagination-loading" style="display: none;">
                <?php echo esc_html($settings['kng_dynamic_posts_loading_text'] ?? 'Loading...'); ?>
            </div>
            <?php if ($posts_query->max_num_pages > 1): ?>
                <button class="king-addons-dpg-load-more-btn" data-page="1" data-max-pages="<?php echo esc_attr($posts_query->max_num_pages); ?>">
                    <?php echo esc_html($settings['kng_dynamic_posts_load_more_text'] ?? 'LOAD MORE'); ?>
                </button>
            <?php endif; ?>
            <div class="king-addons-dpg-pagination-finish" style="display: none;">
                <?php echo esc_html__('All items loaded', 'king-addons'); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render CTA button HTML. Pro overrides to inject action button behaviors.
     *
     * @param \WP_Post $post
     * @param array    $settings
     * @param string   $cta_text
     * @return string
     */
    protected function get_cta_button_html($post, array $settings, string $cta_text): string
    {
        return '<a href="' . esc_url(get_permalink($post)) . '" class="king-addons-dpg-button">' . esc_html($cta_text) . '</a>';
    }

    /**
     * Extension points for Pro controls (content/style). Intentionally empty in Free.
     */
    // PRO Control Methods (empty in Free, overridden in Pro)
    public function add_control_widget_mode() {}
    public function add_control_custom_post_types() {}
    public function add_control_pagination_type() {}
    public function add_control_scroll_threshold() {}
    public function add_control_show_meta() {}
    public function add_control_meta_fields() {}
    public function add_control_cpt_actions() {}
    public function add_control_cpt_colors() {}
    public function add_control_cpt_button_colors() {}
    public function add_control_cpt_ctas() {}
    public function add_control_cpt_icons() {}
}
