<?php
/**
 * Quick Post Grid Widget (Free)
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Typography;
use Elementor\Utils;
use Elementor\Widget_Base;
use WP_Query;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders the Quick Post Grid widget.
 */
class Quick_Post_Grid extends Widget_Base
{
    public function get_name(): string
    {
        return 'king-addons-quick-post-grid';
    }

    public function get_title(): string
    {
        return esc_html__('Quick Post Grid', 'king-addons');
    }

    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-simple-post-grid';
    }

    public function get_script_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-quick-post-grid-script',
        ];
    }

    public function get_style_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-quick-post-grid-style',
        ];
    }

    public function get_categories(): array
    {
        return ['king-addons'];
    }

    public function get_keywords(): array
    {
        return ['posts', 'grid', 'blog', 'king-addons'];
    }

        public function get_custom_help_url()
        {
            return 'mailto:bug@kingaddons.com?subject=Bug Report - King Addons&body=Please describe the issue';
        }

        public function register_controls(): void
    {
        $this->register_query_controls();
        $this->register_layout_controls();
        $this->register_style_card_controls();
        $this->register_style_text_controls();
        $this->register_style_meta_controls();
        $this->register_style_button_controls();
        $this->register_pro_notice_controls();
    }

    public function render(): void
    {
        $settings = $this->get_settings_for_display();
        $this->render_output($settings);
    }

    public function render_output(array $settings): void
    {
        $query = $this->build_query($settings);
        if (!$query->have_posts()) {
            wp_reset_postdata();
            return;
        }

        $wrapper_classes = $this->get_wrapper_classes($settings);
        $wrapper_style = $this->get_wrapper_style($settings);
        $card_linkable = ($settings['kng_card_linkable'] ?? '') === 'yes';

        ?>
        <div class="<?php echo esc_attr(implode(' ', $wrapper_classes)); ?>"<?php echo $wrapper_style; ?>>
            <div class="king-addons-post-grid__grid">
                <?php while ($query->have_posts()) : $query->the_post(); ?>
                    <?php $this->render_card($settings, $card_linkable); ?>
                <?php endwhile; ?>
            </div>
        </div>
        <?php
        wp_reset_postdata();
    }

    protected function register_query_controls(): void
    {
        $this->start_controls_section(
            'kng_query_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Query', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_posts_per_page',
            [
                'label' => esc_html__('Posts Number', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 6,
                'step' => 1,
                'default' => 6,
                'description' => esc_html__('Free version is limited to 6 posts.', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_orderby',
            [
                'label' => esc_html__('Order By', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'date',
                'options' => [
                    'date' => esc_html__('Date', 'king-addons'),
                    'title' => esc_html__('Title', 'king-addons'),
                    'rand' => esc_html__('Random', 'king-addons'),
                    'menu_order' => esc_html__('Menu Order', 'king-addons'),
                ],
            ]
        );

        $this->add_control(
            'kng_order',
            [
                'label' => esc_html__('Order', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'DESC',
                'options' => [
                    'DESC' => esc_html__('Descending', 'king-addons'),
                    'ASC' => esc_html__('Ascending', 'king-addons'),
                ],
            ]
        );

        $this->add_control_categories();

        $this->add_control(
            'kng_exclude_sticky',
            [
                'label' => esc_html__('Ignore Sticky Posts', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_show_image',
            [
                'label' => esc_html__('Show Featured Image', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_group_control(
            Group_Control_Image_Size::get_type(),
            [
                'name' => 'kng_image_size',
                'default' => 'medium',
                'condition' => [
                    'kng_show_image' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_image_ratio',
            [
                'label' => esc_html__('Image Ratio', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'original',
                'options' => [
                    'original' => esc_html__('Original', 'king-addons'),
                    'square' => esc_html__('1:1', 'king-addons'),
                    '4-3' => esc_html__('4:3', 'king-addons'),
                    '3-2' => esc_html__('3:2', 'king-addons'),
                    '16-9' => esc_html__('16:9', 'king-addons'),
                ],
                'condition' => [
                    'kng_show_image' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_image_fit',
            [
                'label' => esc_html__('Image Fit', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'cover',
                'options' => [
                    'cover' => esc_html__('Cover', 'king-addons'),
                    'contain' => esc_html__('Contain', 'king-addons'),
                ],
                'condition' => [
                    'kng_show_image' => 'yes',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-grid' => '--kng-post-grid-image-fit: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_image_radius',
            [
                'label' => esc_html__('Image Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 60],
                    '%' => ['min' => 0, 'max' => 100],
                ],
                'condition' => [
                    'kng_show_image' => 'yes',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-grid__media' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_image_spacing',
            [
                'label' => esc_html__('Image Bottom Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 80],
                ],
                'condition' => [
                    'kng_show_image' => 'yes',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-grid__media' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_show_meta',
            [
                'label' => esc_html__('Show Meta', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_show_author',
            [
                'label' => esc_html__('Show Author', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
                'condition' => [
                    'kng_show_meta' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_show_date',
            [
                'label' => esc_html__('Show Date', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
                'condition' => [
                    'kng_show_meta' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_meta_separator',
            [
                'label' => esc_html__('Meta Separator', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => '•',
                'condition' => [
                    'kng_show_meta' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_show_excerpt',
            [
                'label' => esc_html__('Show Excerpt', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_excerpt_length',
            [
                'label' => esc_html__('Excerpt Length (words)', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 5,
                'max' => 80,
                'step' => 1,
                'default' => 18,
                'condition' => [
                    'kng_show_excerpt' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_show_read_more',
            [
                'label' => esc_html__('Show Read More', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_read_more_text',
            [
                'label' => esc_html__('Read More Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Read more', 'king-addons'),
                'condition' => [
                    'kng_show_read_more' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_card_linkable',
            [
                'label' => esc_html__('Make Card Clickable', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => '',
                'description' => esc_html__('Card click is disabled inside the Elementor editor to avoid interfering with editing.', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_card_link_new_tab',
            [
                'label' => esc_html__('Open Card in New Tab', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
                'condition' => [
                    'kng_card_linkable' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function add_control_categories(): void
    {
        $this->add_control(
            'kng_categories',
            [
                'label' => sprintf(__('Categories %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::TEXT,
                'description' => esc_html__('Enter category slugs separated by commas.', 'king-addons'),
                'placeholder' => esc_html__('news,features', 'king-addons'),
                'classes' => 'king-addons-pro-control no-distance',
            ]
        );
    }

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
            'kng_columns',
            [
                'label' => esc_html__('Columns', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 6,
                'step' => 1,
                'default' => 3,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-grid__grid' => 'grid-template-columns: repeat({{VALUE}}, minmax(0, 1fr));',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_grid_gap',
            [
                'label' => esc_html__('Gap', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 80,
                    ],
                ],
                'default' => [
                    'size' => 20,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-grid__grid' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function register_style_card_controls(): void
    {
        $this->start_controls_section(
            'kng_style_card_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Card', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'kng_card_background',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-grid__card' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_card_background_hover',
            [
                'label' => esc_html__('Background Hover', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-grid__card:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_card_border',
                'selector' => '{{WRAPPER}} .king-addons-post-grid__card',
            ]
        );

        $this->add_control(
            'kng_card_border_color_hover',
            [
                'label' => esc_html__('Border Color Hover', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-grid__card:hover' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_card_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 60],
                    '%' => ['min' => 0, 'max' => 100],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-grid__card' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_card_shadow',
                'selector' => '{{WRAPPER}} .king-addons-post-grid__card',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_card_shadow_hover',
                'selector' => '{{WRAPPER}} .king-addons-post-grid__card:hover',
            ]
        );

        $this->add_control(
            'kng_card_shadow_disable',
            [
                'label' => esc_html__('Disable Box Shadow', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-grid__card' => 'box-shadow: none !important;',
                ],
            ]
        );

        $this->add_control(
            'kng_card_shadow_hover_disable',
            [
                'label' => esc_html__('Disable Box Shadow on Hover', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-grid__card:hover' => 'box-shadow: none !important;',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_card_padding',
            [
                'label' => esc_html__('Card Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-grid__card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function register_style_text_controls(): void
    {
        $this->start_controls_section(
            'kng_style_text_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Text', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_title_typography',
                'selector' => '{{WRAPPER}} .king-addons-post-grid__title',
            ]
        );

        $this->add_control(
            'kng_title_color',
            [
                'label' => esc_html__('Title Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-grid__title a' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_title_spacing',
            [
                'label' => esc_html__('Title Bottom Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 60],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-grid__title' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_excerpt_typography',
                'selector' => '{{WRAPPER}} .king-addons-post-grid__excerpt',
            ]
        );

        $this->add_control(
            'kng_excerpt_color',
            [
                'label' => esc_html__('Excerpt Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-grid__excerpt' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_excerpt_spacing',
            [
                'label' => esc_html__('Excerpt Bottom Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 80],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-grid__excerpt' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_text_alignment',
            [
                'label' => esc_html__('Text Alignment', 'king-addons'),
                'type' => Controls_Manager::CHOOSE,
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
                'toggle' => false,
                'default' => 'left',
                'prefix_class' => 'king-addons-post-grid--text-align-',
            ]
        );

        $this->end_controls_section();
    }

    protected function register_style_meta_controls(): void
    {
        $this->start_controls_section(
            'kng_style_meta_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Meta', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'kng_show_meta' => 'yes',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_meta_typography',
                'selector' => '{{WRAPPER}} .king-addons-post-grid__meta',
            ]
        );

        $this->add_control(
            'kng_meta_color',
            [
                'label' => esc_html__('Meta Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-grid__meta' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_meta_spacing',
            [
                'label' => esc_html__('Meta Bottom Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 60],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-grid__meta' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function register_style_button_controls(): void
    {
        $this->start_controls_section(
            'kng_style_button_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Button', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'kng_show_read_more' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_button_alignment',
            [
                'label' => esc_html__('Button Alignment', 'king-addons'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => esc_html__('Left', 'king-addons'),
                        'icon' => 'eicon-h-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'king-addons'),
                        'icon' => 'eicon-h-align-center',
                    ],
                    'right' => [
                        'title' => esc_html__('Right', 'king-addons'),
                        'icon' => 'eicon-h-align-right',
                    ],
                ],
                'toggle' => false,
                'default' => 'left',
                'prefix_class' => 'king-addons-post-grid--button-align-',
            ]
        );

        $this->add_control(
            'kng_button_text_alignment',
            [
                'label' => esc_html__('Button Text Alignment', 'king-addons'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => esc_html__('Left', 'king-addons'),
                        'icon' => 'eicon-h-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'king-addons'),
                        'icon' => 'eicon-h-align-center',
                    ],
                    'right' => [
                        'title' => esc_html__('Right', 'king-addons'),
                        'icon' => 'eicon-h-align-right',
                    ],
                ],
                'toggle' => false,
                'default' => 'center',
                'prefix_class' => 'king-addons-post-grid--button-text-align-',
            ]
        );

        $this->add_control(
            'kng_button_full_width',
            [
                'label' => esc_html__('Full Width', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-grid__button' => 'width: 100%;',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_button_typography',
                'selector' => '{{WRAPPER}} .king-addons-post-grid__button',
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
            'kng_button_text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-grid__button' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_background',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-grid__button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_button_border',
                'selector' => '{{WRAPPER}} .king-addons-post-grid__button',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_button_shadow',
                'selector' => '{{WRAPPER}} .king-addons-post-grid__button',
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
            'kng_button_text_color_hover',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-grid__button:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_background_hover',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-grid__button:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_border_color_hover',
            [
                'label' => esc_html__('Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-grid__button:hover' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_button_shadow_hover',
                'selector' => '{{WRAPPER}} .king-addons-post-grid__button:hover',
            ]
        );

        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->add_responsive_control(
            'kng_button_spacing',
            [
                'label' => esc_html__('Button Top Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 80],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-grid__button' => 'margin-top: {{SIZE}}{{UNIT}};',
                ],
                'separator' => 'before',
            ]
        );

        $this->add_responsive_control(
            'kng_button_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-grid__button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'kng_button_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 80],
                    '%' => ['min' => 0, 'max' => 100],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-grid__button' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_shadow_disable',
            [
                'label' => esc_html__('Disable Box Shadow', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-grid__button' => 'box-shadow: none !important;',
                ],
            ]
        );

        $this->add_control(
            'kng_button_shadow_hover_disable',
            [
                'label' => esc_html__('Disable Box Shadow on Hover', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-grid__button:hover' => 'box-shadow: none !important;',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function build_query(array $settings): WP_Query
    {
        $per_page = !empty($settings['kng_posts_per_page']) ? (int) $settings['kng_posts_per_page'] : 6;
        if (!king_addons_freemius()->can_use_premium_code__premium_only()) {
            $per_page = min($per_page, 6);
        }

        $order = strtoupper((string) ($settings['kng_order'] ?? 'DESC'));
        $order = in_array($order, ['ASC', 'DESC'], true) ? $order : 'DESC';

        $allowed_orderby = ['date', 'title', 'rand', 'menu_order'];
        $orderby = (string) ($settings['kng_orderby'] ?? 'date');
        $orderby = in_array($orderby, $allowed_orderby, true) ? $orderby : 'date';

        $args = [
            'post_type' => 'post',
            'posts_per_page' => $per_page,
            'order' => $order,
            'orderby' => $orderby,
        ];

        if (!empty($settings['kng_categories']) && king_addons_freemius()->can_use_premium_code__premium_only()) {
            $slugs = array_filter(array_map('sanitize_title', array_map('trim', explode(',', (string) $settings['kng_categories']))));
            if (!empty($slugs)) {
                $args['category_name'] = implode(',', $slugs);
            }
        }

        if (!empty($settings['kng_exclude_sticky'])) {
            $args['ignore_sticky_posts'] = true;
        }

        return new WP_Query($args);
    }

    protected function render_card(array $settings, bool $card_linkable): void
    {
        $show_image = $settings['kng_show_image'] === 'yes';
        $show_meta = ($settings['kng_show_meta'] ?? '') === 'yes';
        $show_author = ($settings['kng_show_author'] ?? 'yes') === 'yes';
        $show_date = ($settings['kng_show_date'] ?? 'yes') === 'yes';
        $show_excerpt = $settings['kng_show_excerpt'] === 'yes';
        $show_read_more = $settings['kng_show_read_more'] === 'yes';

        $image_html = '';
        if ($show_image) {
            if (has_post_thumbnail()) {
                $image_html = wp_get_attachment_image(
                    get_post_thumbnail_id(),
                    $settings['kng_image_size_size'] ?? 'medium',
                    false,
                    ['alt' => esc_attr(get_the_title())]
                );
            }
            if (empty($image_html)) {
                $image_html = '<img src="' . esc_url(Utils::get_placeholder_image_src()) . '" alt="' . esc_attr(get_the_title()) . '"/>';
            }
        }

        $excerpt_length = !empty($settings['kng_excerpt_length']) ? (int) $settings['kng_excerpt_length'] : 18;
        $excerpt_text = $show_excerpt ? wp_trim_words(get_the_excerpt(), $excerpt_length) : '';

        $meta_parts = [];
        if ($show_meta) {
            if ($show_date) {
                $meta_parts[] = get_the_date();
            }
            if ($show_author) {
                $meta_parts[] = get_the_author();
            }
        }

        $meta_separator = isset($settings['kng_meta_separator']) ? trim((string) $settings['kng_meta_separator']) : '•';
        $meta_glue = $meta_separator === '' ? ' ' : ' ' . $meta_separator . ' ';

        $card_attributes = [];
        if ($card_linkable) {
            $card_attributes[] = 'data-card-link="' . esc_url(get_permalink()) . '"';
            if (($settings['kng_card_link_new_tab'] ?? '') === 'yes') {
                $card_attributes[] = 'data-card-link-target="_blank"';
            }
            $card_attributes[] = 'tabindex="0"';
            $card_attributes[] = 'role="link"';
            $card_attributes[] = 'aria-label="' . esc_attr(sprintf(__('Open %s', 'king-addons'), get_the_title())) . '"';
        }

        ?>
        <div class="king-addons-post-grid__item">
            <article class="king-addons-post-grid__card"<?php echo !empty($card_attributes) ? ' ' . implode(' ', $card_attributes) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
                <?php if (!empty($image_html)) : ?>
                    <div class="king-addons-post-grid__media">
                        <a href="<?php the_permalink(); ?>" class="king-addons-post-grid__media-link">
                            <?php echo $image_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </a>
                    </div>
                <?php endif; ?>

                <div class="king-addons-post-grid__body">
                    <h3 class="king-addons-post-grid__title">
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                    </h3>

                    <?php if (!empty($meta_parts)) : ?>
                        <div class="king-addons-post-grid__meta">
                            <?php echo esc_html(implode($meta_glue, $meta_parts)); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($excerpt_text)) : ?>
                        <div class="king-addons-post-grid__excerpt"><?php echo esc_html($excerpt_text); ?></div>
                    <?php endif; ?>

                    <?php if ($show_read_more) : ?>
                        <a class="king-addons-post-grid__button" href="<?php the_permalink(); ?>">
                            <?php echo esc_html($settings['kng_read_more_text'] ?? esc_html__('Read more', 'king-addons')); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </article>
        </div>
        <?php
    }

    protected function get_wrapper_classes(array $settings): array
    {
        $classes = ['king-addons-post-grid'];

        $image_ratio = $settings['kng_image_ratio'] ?? 'original';
        if ($image_ratio !== 'original') {
            $classes[] = 'king-addons-post-grid--image-ratio-' . sanitize_html_class((string) $image_ratio);
        }

        $text_align = $settings['kng_text_alignment'] ?? 'left';
        $classes[] = 'king-addons-post-grid--text-align-' . sanitize_html_class((string) $text_align);

        $button_align = $settings['kng_button_alignment'] ?? 'left';
        $classes[] = 'king-addons-post-grid--button-align-' . sanitize_html_class((string) $button_align);

        $button_text_align = $settings['kng_button_text_alignment'] ?? 'center';
        $classes[] = 'king-addons-post-grid--button-text-align-' . sanitize_html_class((string) $button_text_align);

        return array_filter($classes);
    }

    protected function get_wrapper_style(array $settings): string
    {
        $style_parts = [];
        if (isset($settings['kng_grid_gap']['size'])) {
            $style_parts[] = '--kng-post-grid-gap:' . (float) $settings['kng_grid_gap']['size'] . $settings['kng_grid_gap']['unit'] . ';';
        }

        if (!empty($style_parts)) {
            return ' style="' . esc_attr(implode(' ', $style_parts)) . '"';
        }

        return '';
    }

    protected function register_pro_notice_controls(): void
    {
        if (!king_addons_freemius()->can_use_premium_code__premium_only()) {
            Core::renderProFeaturesSection($this, '', Controls_Manager::RAW_HTML, 'quick-post-grid', [
                'Advanced query (categories) and higher post limits',
                'Hover animations and extended styling',
                'Navigation/pagination skins (if enabled in grid variant)',
            ]);
        }
    }
}
