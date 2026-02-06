<?php
/**
 * Pros and Cons Box Widget (Free).
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Plugin;
use Elementor\Repeater;
use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders a pros/cons comparison box.
 */
class Pros_Cons_Box extends Widget_Base
{
    /**
     * Current settings for render helpers.
     *
     * @var array<string, mixed>
     */
    protected array $current_settings = [];
    /**
     * Widget slug.
     */
    public function get_name(): string
    {
        return 'king-addons-pros-cons-box';
    }

    /**
     * Widget title.
     */
    public function get_title(): string
    {
        return esc_html__('Pros and Cons Box', 'king-addons');
    }

    /**
     * Widget icon.
     */
    public function get_icon(): string
    {
        return 'eicon-editor-list-ul';
    }

    /**
     * Styles used.
     *
     * @return array<int, string>
     */
    public function get_style_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-pros-cons-box-style',
        ];
    }

    /**
     * Scripts used.
     *
     * @return array<int, string>
     */
    public function get_script_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-pros-cons-box-script',
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
        return ['pros', 'cons', 'comparison', 'review', 'box'];
    }

        public function get_custom_help_url()
        {
            return 'mailto:bug@kingaddons.com?subject=Bug Report - King Addons&body=Please describe the issue';
        }

        /**
     * Register controls.
     */
    public function register_controls(): void
    {
        $this->register_general_controls();
        $this->register_layout_controls();
        $this->register_list_controls();
        $this->register_pros_controls();
        $this->register_cons_controls();
        $this->register_style_general_controls();
        $this->register_style_section_controls();
        $this->register_style_pros_controls();
        $this->register_style_cons_controls();
        $this->register_pro_notice_controls();
    }

    /**
     * Render widget output.
     */
    public function render(): void
    {
        $settings = $this->get_settings_for_display();
        $this->render_output($settings);
    }

    /**
     * Render output.
     *
     * @param array<string, mixed> $settings Settings.
     */
    protected function render_output(array $settings): void
    {
        $this->current_settings = $settings;
        $pros_items = $this->prepare_items($settings['kng_pros_items'] ?? []);
        $cons_items = $this->prepare_items($settings['kng_cons_items'] ?? []);

        $pros_count = count($pros_items);
        $cons_count = count($cons_items);

        $is_editor = class_exists(Plugin::class) && Plugin::$instance->editor->is_edit_mode();

        $pros_payload = $this->build_section_payload('pros', $settings, $pros_items, $pros_count, $is_editor);
        $cons_payload = $this->build_section_payload('cons', $settings, $cons_items, $cons_count, $is_editor);

        if (!$pros_payload['show'] && !$cons_payload['show']) {
            return;
        }

        $context = [
            'show_pros' => $pros_payload['show'],
            'show_cons' => $cons_payload['show'],
            'pros_count' => $pros_count,
            'cons_count' => $cons_count,
        ];

        $wrapper_classes = $this->get_wrapper_classes($settings, $context);
        $data_attributes = $this->get_data_attributes($settings);
        ?>
        <div class="<?php echo esc_attr(implode(' ', $wrapper_classes)); ?>"<?php echo $data_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
            <?php $this->render_header($settings, $context); ?>
            <div class="king-addons-pros-cons__columns">
                <?php if ($pros_payload['show']) : ?>
                    <?php $this->render_section('pros', $settings, $pros_payload); ?>
                <?php endif; ?>
                <?php if ($cons_payload['show']) : ?>
                    <?php $this->render_section('cons', $settings, $cons_payload); ?>
                <?php endif; ?>
            </div>
            <?php $this->render_schema($settings, $context); ?>
        </div>
        <?php
    }

    /**
     * Register general content controls.
     */
    protected function register_general_controls(): void
    {
        $this->start_controls_section(
            'kng_general_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('General', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_main_title',
            [
                'label' => esc_html__('Main Title', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Pros and Cons', 'king-addons'),
                'label_block' => true,
            ]
        );

        $this->add_control(
            'kng_description',
            [
                'label' => esc_html__('Description', 'king-addons'),
                'type' => Controls_Manager::TEXTAREA,
                'rows' => 3,
                'default' => '',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register layout controls.
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
            'kng_layout',
            [
                'label' => esc_html__('Layout', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'side',
                'options' => [
                    'side' => esc_html__('Side by Side', 'king-addons'),
                    'stacked' => esc_html__('Stacked', 'king-addons'),
                ],
            ]
        );

        $this->add_control(
            'kng_stack_on_mobile',
            [
                'label' => esc_html__('Stack on Mobile', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_responsive_control(
            'kng_columns_gap',
            [
                'label' => esc_html__('Columns Gap', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 80,
                    ],
                ],
                'default' => [
                    'size' => 24,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pros-cons' => '--kng-pros-cons-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_split_ratio',
            [
                'label' => esc_html__('Pros Width (%)', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['%'],
                'range' => [
                    '%' => [
                        'min' => 30,
                        'max' => 70,
                    ],
                ],
                'default' => [
                    'size' => 50,
                    'unit' => '%',
                ],
                'condition' => [
                    'kng_layout' => 'side',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pros-cons' => '--kng-pros-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_box_padding',
            [
                'label' => esc_html__('Box Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pros-cons__section' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_preset',
            [
                'label' => esc_html__('Style Preset', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'clean',
                'options' => $this->get_preset_options(),
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register list controls.
     */
    protected function register_list_controls(): void
    {
        $this->start_controls_section(
            'kng_list_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('List Settings', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_show_count',
            [
                'label' => esc_html__('Show Count', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
            ]
        );

        $this->add_control(
            'kng_show_item_icons',
            [
                'label' => esc_html__('Show Item Icons', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_item_icon_position',
            [
                'label' => esc_html__('Icon Position', 'king-addons'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => esc_html__('Left', 'king-addons'),
                        'icon' => 'eicon-h-align-left',
                    ],
                    'right' => [
                        'title' => esc_html__('Right', 'king-addons'),
                        'icon' => 'eicon-h-align-right',
                    ],
                ],
                'toggle' => false,
                'default' => 'left',
            ]
        );

        $this->add_control(
            'kng_pros_item_icon',
            [
                'label' => esc_html__('Pros Default Icon', 'king-addons'),
                'type' => Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fas fa-check',
                    'library' => 'fa-solid',
                ],
                'condition' => [
                    'kng_show_item_icons' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_cons_item_icon',
            [
                'label' => esc_html__('Cons Default Icon', 'king-addons'),
                'type' => Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fas fa-times',
                    'library' => 'fa-solid',
                ],
                'condition' => [
                    'kng_show_item_icons' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register pros controls.
     */
    protected function register_pros_controls(): void
    {
        $this->start_controls_section(
            'kng_pros_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Pros', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_pros_title',
            [
                'label' => esc_html__('Pros Title', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Pros', 'king-addons'),
                'label_block' => true,
            ]
        );

        $this->add_control(
            'kng_pros_subtitle',
            [
                'label' => esc_html__('Pros Subtitle', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'label_block' => true,
            ]
        );

        $this->add_control(
            'kng_pros_title_icon',
            [
                'label' => esc_html__('Pros Title Icon', 'king-addons'),
                'type' => Controls_Manager::ICONS,
            ]
        );

        $repeater = new Repeater();

        $repeater->add_control(
            'kng_item_text',
            [
                'label' => esc_html__('Text', 'king-addons'),
                'type' => Controls_Manager::TEXTAREA,
                'rows' => 2,
                'default' => esc_html__('Clear advantage with a short explanation.', 'king-addons'),
            ]
        );

        $repeater->add_control(
            'kng_item_icon',
            [
                'label' => esc_html__('Icon Override', 'king-addons'),
                'type' => Controls_Manager::ICONS,
                'condition' => [
                    'kng_show_item_icons' => 'yes',
                ],
            ]
        );

        $repeater->add_control(
            'kng_item_highlight',
            [
                'label' => esc_html__('Highlight', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
            ]
        );

        $repeater->add_control(
            'kng_item_tooltip',
            [
                'label' => esc_html__('Tooltip (Pro)', 'king-addons'),
                'type' => Controls_Manager::TEXTAREA,
                'rows' => 2,
                'condition' => [
                    'kng_enable_tooltips' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_pros_items',
            [
                'label' => esc_html__('Pros Items', 'king-addons'),
                'type' => Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'default' => [],
                'title_field' => '{{{ kng_item_text }}}',
            ]
        );

        $this->add_control(
            'kng_pros_empty_state',
            [
                'label' => esc_html__('Empty State', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'hide',
                'options' => [
                    'hide' => esc_html__('Hide Section', 'king-addons'),
                    'placeholder' => esc_html__('Show Placeholder', 'king-addons'),
                ],
            ]
        );

        $this->add_control(
            'kng_pros_empty_text',
            [
                'label' => esc_html__('Placeholder Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Add pros items to show this section.', 'king-addons'),
                'condition' => [
                    'kng_pros_empty_state' => 'placeholder',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register cons controls.
     */
    protected function register_cons_controls(): void
    {
        $this->start_controls_section(
            'kng_cons_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Cons', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_cons_title',
            [
                'label' => esc_html__('Cons Title', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Cons', 'king-addons'),
                'label_block' => true,
            ]
        );

        $this->add_control(
            'kng_cons_subtitle',
            [
                'label' => esc_html__('Cons Subtitle', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'label_block' => true,
            ]
        );

        $this->add_control(
            'kng_cons_title_icon',
            [
                'label' => esc_html__('Cons Title Icon', 'king-addons'),
                'type' => Controls_Manager::ICONS,
            ]
        );

        $repeater = new Repeater();

        $repeater->add_control(
            'kng_item_text',
            [
                'label' => esc_html__('Text', 'king-addons'),
                'type' => Controls_Manager::TEXTAREA,
                'rows' => 2,
                'default' => esc_html__('Note a limitation or tradeoff to be aware of.', 'king-addons'),
            ]
        );

        $repeater->add_control(
            'kng_item_icon',
            [
                'label' => esc_html__('Icon Override', 'king-addons'),
                'type' => Controls_Manager::ICONS,
                'condition' => [
                    'kng_show_item_icons' => 'yes',
                ],
            ]
        );

        $repeater->add_control(
            'kng_item_highlight',
            [
                'label' => esc_html__('Highlight', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
            ]
        );

        $repeater->add_control(
            'kng_item_tooltip',
            [
                'label' => esc_html__('Tooltip (Pro)', 'king-addons'),
                'type' => Controls_Manager::TEXTAREA,
                'rows' => 2,
                'condition' => [
                    'kng_enable_tooltips' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_cons_items',
            [
                'label' => esc_html__('Cons Items', 'king-addons'),
                'type' => Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'default' => [],
                'title_field' => '{{{ kng_item_text }}}',
            ]
        );

        $this->add_control(
            'kng_cons_empty_state',
            [
                'label' => esc_html__('Empty State', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'hide',
                'options' => [
                    'hide' => esc_html__('Hide Section', 'king-addons'),
                    'placeholder' => esc_html__('Show Placeholder', 'king-addons'),
                ],
            ]
        );

        $this->add_control(
            'kng_cons_empty_text',
            [
                'label' => esc_html__('Placeholder Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Add cons items to show this section.', 'king-addons'),
                'condition' => [
                    'kng_cons_empty_state' => 'placeholder',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register general style controls.
     */
    protected function register_style_general_controls(): void
    {
        $this->start_controls_section(
            'kng_style_general_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('General', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name' => 'kng_box_background',
                'selector' => '{{WRAPPER}} .king-addons-pros-cons',
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_box_border',
                'selector' => '{{WRAPPER}} .king-addons-pros-cons',
            ]
        );

        $this->add_control(
            'kng_box_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 60],
                    '%' => ['min' => 0, 'max' => 100],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pros-cons' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_box_shadow',
                'selector' => '{{WRAPPER}} .king-addons-pros-cons',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_title_typography',
                'selector' => '{{WRAPPER}} .king-addons-pros-cons__title',
            ]
        );

        $this->add_control(
            'kng_title_color',
            [
                'label' => esc_html__('Title Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pros-cons__title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_description_typography',
                'selector' => '{{WRAPPER}} .king-addons-pros-cons__description',
            ]
        );

        $this->add_control(
            'kng_description_color',
            [
                'label' => esc_html__('Description Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pros-cons__description' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_header_spacing',
            [
                'label' => esc_html__('Header Bottom Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 80],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pros-cons__header' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register shared section style controls.
     */
    protected function register_style_section_controls(): void
    {
        $this->start_controls_section(
            'kng_style_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Section', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_section_title_typography',
                'selector' => '{{WRAPPER}} .king-addons-pros-cons__section-title',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_section_subtitle_typography',
                'selector' => '{{WRAPPER}} .king-addons-pros-cons__section-subtitle',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_item_typography',
                'selector' => '{{WRAPPER}} .king-addons-pros-cons__item-text',
            ]
        );

        $this->add_responsive_control(
            'kng_section_spacing',
            [
                'label' => esc_html__('Title Bottom Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 60],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pros-cons__section-header' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_item_spacing',
            [
                'label' => esc_html__('Item Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 40],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pros-cons' => '--kng-pros-cons-item-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_highlight_text_color',
            [
                'label' => esc_html__('Highlight Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pros-cons__item.is-highlight .king-addons-pros-cons__item-text' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_highlight_radius',
            [
                'label' => esc_html__('Highlight Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 40],
                    '%' => ['min' => 0, 'max' => 100],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pros-cons__item.is-highlight' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_highlight_padding',
            [
                'label' => esc_html__('Highlight Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pros-cons__item.is-highlight' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_item_icon_size',
            [
                'label' => esc_html__('Item Icon Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 10, 'max' => 36],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pros-cons' => '--kng-pros-cons-icon-size: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'kng_show_item_icons' => 'yes',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_item_icon_gap',
            [
                'label' => esc_html__('Item Icon Gap', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 30],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pros-cons' => '--kng-pros-cons-icon-gap: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'kng_show_item_icons' => 'yes',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_title_icon_size',
            [
                'label' => esc_html__('Title Icon Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 10, 'max' => 32],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pros-cons' => '--kng-pros-cons-title-icon-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_title_icon_gap',
            [
                'label' => esc_html__('Title Icon Gap', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 40],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pros-cons' => '--kng-pros-cons-title-icon-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_title_icon_position',
            [
                'label' => esc_html__('Title Icon Position', 'king-addons'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => esc_html__('Left', 'king-addons'),
                        'icon' => 'eicon-h-align-left',
                    ],
                    'right' => [
                        'title' => esc_html__('Right', 'king-addons'),
                        'icon' => 'eicon-h-align-right',
                    ],
                ],
                'toggle' => false,
                'default' => 'left',
                'prefix_class' => 'king-addons-pros-cons-title-icon-',
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name' => 'kng_section_header_background',
                'selector' => '{{WRAPPER}} .king-addons-pros-cons__section-header',
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_section_header_border',
                'selector' => '{{WRAPPER}} .king-addons-pros-cons__section-header',
            ]
        );

        $this->add_responsive_control(
            'kng_section_header_radius',
            [
                'label' => esc_html__('Header Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 60],
                    '%' => ['min' => 0, 'max' => 100],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pros-cons__section-header' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_section_header_padding',
            [
                'label' => esc_html__('Header Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pros-cons__section-header' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register pros style controls.
     */
    protected function register_style_pros_controls(): void
    {
        $this->start_controls_section(
            'kng_style_pros_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Pros Styles', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'kng_pros_title_color',
            [
                'label' => esc_html__('Title Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pros-cons__section--pros .king-addons-pros-cons__section-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_pros_subtitle_color',
            [
                'label' => esc_html__('Subtitle Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pros-cons__section--pros .king-addons-pros-cons__section-subtitle' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_pros_item_text_color',
            [
                'label' => esc_html__('Item Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pros-cons__section--pros .king-addons-pros-cons__item-text' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_pros_highlight_text_color',
            [
                'label' => esc_html__('Highlight Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pros-cons__section--pros .king-addons-pros-cons__item.is-highlight .king-addons-pros-cons__item-text' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_pros_accent_color',
            [
                'label' => esc_html__('Accent Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pros-cons' => '--kng-pros-accent: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_pros_title_icon_color',
            [
                'label' => esc_html__('Title Icon Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pros-cons__section--pros .king-addons-pros-cons__section-title-icon' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-pros-cons__section--pros .king-addons-pros-cons__section-title-icon svg' => 'fill: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_pros_header_heading',
            [
                'label' => esc_html__('Header', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name' => 'kng_pros_header_background',
                'selector' => '{{WRAPPER}} .king-addons-pros-cons__section--pros .king-addons-pros-cons__section-header',
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_pros_header_border',
                'selector' => '{{WRAPPER}} .king-addons-pros-cons__section--pros .king-addons-pros-cons__section-header',
            ]
        );

        $this->add_responsive_control(
            'kng_pros_header_radius',
            [
                'label' => esc_html__('Header Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 60],
                    '%' => ['min' => 0, 'max' => 100],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pros-cons__section--pros .king-addons-pros-cons__section-header' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_pros_header_padding',
            [
                'label' => esc_html__('Header Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pros-cons__section--pros .king-addons-pros-cons__section-header' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_pros_header_shadow',
                'selector' => '{{WRAPPER}} .king-addons-pros-cons__section--pros .king-addons-pros-cons__section-header',
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name' => 'kng_pros_section_background',
                'selector' => '{{WRAPPER}} .king-addons-pros-cons__section--pros',
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_pros_section_border',
                'selector' => '{{WRAPPER}} .king-addons-pros-cons__section--pros',
            ]
        );

        $this->add_responsive_control(
            'kng_pros_section_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 60],
                    '%' => ['min' => 0, 'max' => 100],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pros-cons__section--pros' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_pros_section_shadow',
                'selector' => '{{WRAPPER}} .king-addons-pros-cons__section--pros',
            ]
        );

        $this->add_control(
            'kng_pros_icon_color',
            [
                'label' => esc_html__('Item Icon Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pros-cons__section--pros .king-addons-pros-cons__item-icon' => 'color: {{VALUE}};',
                ],
                'condition' => [
                    'kng_show_item_icons' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_pros_highlight_background',
            [
                'label' => esc_html__('Highlight Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pros-cons' => '--kng-pros-highlight: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_pros_accent_border_width',
            [
                'label' => esc_html__('Accent Border Width', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 10],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pros-cons' => '--kng-pros-border-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register cons style controls.
     */
    protected function register_style_cons_controls(): void
    {
        $this->start_controls_section(
            'kng_style_cons_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Cons Styles', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'kng_cons_title_color',
            [
                'label' => esc_html__('Title Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pros-cons__section--cons .king-addons-pros-cons__section-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_cons_subtitle_color',
            [
                'label' => esc_html__('Subtitle Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pros-cons__section--cons .king-addons-pros-cons__section-subtitle' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_cons_item_text_color',
            [
                'label' => esc_html__('Item Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pros-cons__section--cons .king-addons-pros-cons__item-text' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_cons_highlight_text_color',
            [
                'label' => esc_html__('Highlight Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pros-cons__section--cons .king-addons-pros-cons__item.is-highlight .king-addons-pros-cons__item-text' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_cons_accent_color',
            [
                'label' => esc_html__('Accent Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pros-cons' => '--kng-cons-accent: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_cons_title_icon_color',
            [
                'label' => esc_html__('Title Icon Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pros-cons__section--cons .king-addons-pros-cons__section-title-icon' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-pros-cons__section--cons .king-addons-pros-cons__section-title-icon svg' => 'fill: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_cons_header_heading',
            [
                'label' => esc_html__('Header', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name' => 'kng_cons_header_background',
                'selector' => '{{WRAPPER}} .king-addons-pros-cons__section--cons .king-addons-pros-cons__section-header',
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_cons_header_border',
                'selector' => '{{WRAPPER}} .king-addons-pros-cons__section--cons .king-addons-pros-cons__section-header',
            ]
        );

        $this->add_responsive_control(
            'kng_cons_header_radius',
            [
                'label' => esc_html__('Header Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 60],
                    '%' => ['min' => 0, 'max' => 100],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pros-cons__section--cons .king-addons-pros-cons__section-header' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_cons_header_padding',
            [
                'label' => esc_html__('Header Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pros-cons__section--cons .king-addons-pros-cons__section-header' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_cons_header_shadow',
                'selector' => '{{WRAPPER}} .king-addons-pros-cons__section--cons .king-addons-pros-cons__section-header',
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name' => 'kng_cons_section_background',
                'selector' => '{{WRAPPER}} .king-addons-pros-cons__section--cons',
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_cons_section_border',
                'selector' => '{{WRAPPER}} .king-addons-pros-cons__section--cons',
            ]
        );

        $this->add_responsive_control(
            'kng_cons_section_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 60],
                    '%' => ['min' => 0, 'max' => 100],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pros-cons__section--cons' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_cons_section_shadow',
                'selector' => '{{WRAPPER}} .king-addons-pros-cons__section--cons',
            ]
        );

        $this->add_control(
            'kng_cons_icon_color',
            [
                'label' => esc_html__('Item Icon Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pros-cons__section--cons .king-addons-pros-cons__item-icon' => 'color: {{VALUE}};',
                ],
                'condition' => [
                    'kng_show_item_icons' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_cons_highlight_background',
            [
                'label' => esc_html__('Highlight Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pros-cons' => '--kng-cons-highlight: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_cons_accent_border_width',
            [
                'label' => esc_html__('Accent Border Width', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 10],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pros-cons' => '--kng-cons-border-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Pro notice section.
     */
    protected function register_pro_notice_controls(): void
    {
        if (!king_addons_freemius()->can_use_premium_code__premium_only()) {
            Core::renderProFeaturesSection($this, '', Controls_Manager::RAW_HTML, 'pros-cons-box', [
                'Advanced presets and rating header',
                'Section-level collapse with memory',
                'Item tooltips and search-friendly output',
                'Review schema output and WooCommerce mapping',
                'Custom count formats and difference indicator',
            ]);
        }
    }

    /**
     * Build section payload.
     *
     * @param string $type Section type.
     * @param array<string, mixed> $settings Settings.
     * @param array<int, array<string, mixed>> $items Items.
     * @param int $count Count.
     * @param bool $is_editor Editor state.
     *
     * @return array<string, mixed>
     */
    protected function build_section_payload(string $type, array $settings, array $items, int $count, bool $is_editor): array
    {
        $empty_state = $settings['kng_' . $type . '_empty_state'] ?? 'hide';
        $show = !empty($items) || $empty_state === 'placeholder';
        $is_placeholder = false;

        if (!$show) {
            return [
                'show' => false,
                'items' => [],
                'count' => $count,
                'is_placeholder' => false,
            ];
        }

        if (empty($items)) {
            $placeholder = trim((string) ($settings['kng_' . $type . '_empty_text'] ?? ''));
            if ($placeholder === '') {
                $placeholder = $type === 'pros'
                    ? esc_html__('Add pros items to show this section.', 'king-addons')
                    : esc_html__('Add cons items to show this section.', 'king-addons');
            }

            $items = [
                [
                    'text' => esc_html($placeholder),
                    'icon' => [],
                    'highlight' => false,
                    'tooltip' => '',
                    'id' => 'placeholder',
                ],
            ];
            $is_placeholder = true;
        }

        return [
            'show' => true,
            'items' => $items,
            'count' => $count,
            'is_placeholder' => $is_placeholder,
        ];
    }

    /**
     * Prepare repeater items.
     *
     * @param array<int, array<string, mixed>> $items Items.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function prepare_items(array $items): array
    {
        $prepared = [];
        foreach ($items as $item) {
            $text = $this->sanitize_item_text((string) ($item['kng_item_text'] ?? ''));
            if ($text === '') {
                continue;
            }

            $prepared[] = [
                'text' => $text,
                'icon' => $item['kng_item_icon'] ?? [],
                'highlight' => ($item['kng_item_highlight'] ?? '') === 'yes',
                'tooltip' => (string) ($item['kng_item_tooltip'] ?? ''),
                'id' => $item['_id'] ?? uniqid('kng', true),
            ];
        }

        return array_slice($prepared, 0, 50);
    }

    /**
     * Sanitize item text with minimal markup.
     *
     * @param string $text Text.
     * @return string
     */
    protected function sanitize_item_text(string $text): string
    {
        $allowed = [
            'strong' => [],
            'em' => [],
            'br' => [],
        ];

        return wp_kses($text, $allowed);
    }

    /**
     * Render header.
     *
     * @param array<string, mixed> $settings Settings.
     * @param array<string, mixed> $context Context.
     */
    protected function render_header(array $settings, array $context): void
    {
        $title = trim((string) ($settings['kng_main_title'] ?? ''));
        $description = trim((string) ($settings['kng_description'] ?? ''));

        if ($title === '' && $description === '' && !$this->has_header_extra($settings, $context)) {
            return;
        }
        ?>
        <div class="king-addons-pros-cons__header">
            <?php if ($title !== '') : ?>
                <h3 class="king-addons-pros-cons__title"><?php echo esc_html($title); ?></h3>
            <?php endif; ?>
            <?php $this->render_header_extra($settings, $context); ?>
            <?php if ($description !== '') : ?>
                <div class="king-addons-pros-cons__description"><?php echo esc_html($description); ?></div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render extra header elements (Pro).
     *
     * @param array<string, mixed> $settings Settings.
     * @param array<string, mixed> $context Context.
     */
    protected function render_header_extra(array $settings, array $context): void
    {
        // Reserved for Pro.
    }

    /**
     * Check if extra header elements should render.
     *
     * @param array<string, mixed> $settings Settings.
     * @param array<string, mixed> $context Context.
     */
    protected function has_header_extra(array $settings, array $context): bool
    {
        return false;
    }

    /**
     * Render section.
     *
     * @param string $type Section type.
     * @param array<string, mixed> $settings Settings.
     * @param array<string, mixed> $payload Payload.
     */
    protected function render_section(string $type, array $settings, array $payload): void
    {
        $title = trim((string) ($settings['kng_' . $type . '_title'] ?? ''));
        $subtitle = trim((string) ($settings['kng_' . $type . '_subtitle'] ?? ''));
        $title_icon = $settings['kng_' . $type . '_title_icon'] ?? [];
        $list_id = $this->get_section_list_id($type);
        $toggle_html = $this->render_section_toggle($type, $settings, $list_id);
        $section_title = $this->format_section_title($type, $title, (int) $payload['count'], $settings);

        $section_classes = [
            'king-addons-pros-cons__section',
            'king-addons-pros-cons__section--' . $type,
        ];

        if ($payload['is_placeholder']) {
            $section_classes[] = 'is-placeholder';
        }

        if ($this->is_section_collapsible($type, $settings)) {
            $section_classes[] = 'is-collapsible';
        }

        ?>
        <section class="<?php echo esc_attr(implode(' ', $section_classes)); ?>" data-section="<?php echo esc_attr($type); ?>"<?php echo $this->get_section_data_attributes($type, $settings); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
            <div class="king-addons-pros-cons__section-header">
                <div class="king-addons-pros-cons__section-heading">
                    <?php if (!empty($title_icon['value'])) : ?>
                        <span class="king-addons-pros-cons__section-title-icon" aria-hidden="true">
                            <?php Icons_Manager::render_icon($title_icon, ['aria-hidden' => 'true']); ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($section_title !== '') : ?>
                        <h4 class="king-addons-pros-cons__section-title"><?php echo esc_html($section_title); ?></h4>
                    <?php endif; ?>
                </div>
                <?php if ($toggle_html !== '') : ?>
                    <?php echo $toggle_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <?php endif; ?>
            </div>
            <?php if ($subtitle !== '') : ?>
                <div class="king-addons-pros-cons__section-subtitle"><?php echo esc_html($subtitle); ?></div>
            <?php endif; ?>
            <ul id="<?php echo esc_attr($list_id); ?>" class="king-addons-pros-cons__list" aria-hidden="false">
                <?php foreach ($payload['items'] as $item) : ?>
                    <?php $this->render_item($type, $settings, $item); ?>
                <?php endforeach; ?>
            </ul>
        </section>
        <?php
    }

    /**
     * Render item.
     *
     * @param string $type Section type.
     * @param array<string, mixed> $settings Settings.
     * @param array<string, mixed> $item Item.
     */
    protected function render_item(string $type, array $settings, array $item): void
    {
        $show_icons = ($settings['kng_show_item_icons'] ?? 'yes') === 'yes';
        $default_icon = $settings['kng_' . $type . '_item_icon'] ?? [];
        $icon = !empty($item['icon']['value']) ? $item['icon'] : $default_icon;
        $tooltip = trim((string) ($item['tooltip'] ?? ''));
        $tooltip_html = $this->render_item_tooltip($tooltip, (string) ($item['id'] ?? ''));

        $item_classes = ['king-addons-pros-cons__item'];
        if (!empty($item['highlight'])) {
            $item_classes[] = 'is-highlight';
        }
        if ($tooltip_html !== '') {
            $item_classes[] = 'has-tooltip';
        }
        ?>
        <li class="<?php echo esc_attr(implode(' ', $item_classes)); ?>">
            <?php if ($show_icons && !empty($icon['value'])) : ?>
                <span class="king-addons-pros-cons__item-icon" aria-hidden="true">
                    <?php Icons_Manager::render_icon($icon, ['aria-hidden' => 'true']); ?>
                </span>
            <?php endif; ?>
            <span class="king-addons-pros-cons__item-text"><?php echo $item['text']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
            <?php if ($tooltip_html !== '') : ?>
                <?php echo $tooltip_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php endif; ?>
        </li>
        <?php
    }

    /**
     * Render item tooltip (Pro).
     *
     * @param string $tooltip Tooltip text.
     * @param string $id Item id.
     * @return string
     */
    protected function render_item_tooltip(string $tooltip, string $id): string
    {
        if ($tooltip === '' || !($this->is_tooltip_enabled())) {
            return '';
        }

        $tooltip_id = 'kng-pros-cons-tooltip-' . $this->get_id() . '-' . sanitize_key($id);
        $tooltip_text = esc_html($tooltip);

        return sprintf(
            '<button type="button" class="king-addons-pros-cons__tooltip-trigger" aria-describedby="%1$s" aria-label="%2$s">i</button><span id="%1$s" class="king-addons-pros-cons__tooltip king-addons-pros-cons__tooltip--%3$s" role="tooltip">%4$s</span>',
            esc_attr($tooltip_id),
            esc_attr__('More info', 'king-addons'),
            esc_attr($this->get_tooltip_position()),
            $tooltip_text
        );
    }

    /**
     * Tooltip enabled flag (Pro).
     */
    protected function is_tooltip_enabled(): bool
    {
        return false;
    }

    /**
     * Tooltip position (Pro).
     */
    protected function get_tooltip_position(): string
    {
        return 'top';
    }

    /**
     * Render section toggle (Pro).
     *
     * @param string $type Section type.
     * @param array<string, mixed> $settings Settings.
     * @param string $list_id List id.
     * @return string
     */
    protected function render_section_toggle(string $type, array $settings, string $list_id): string
    {
        if (!$this->is_section_collapsible($type, $settings)) {
            return '';
        }

        $label = $type === 'pros'
            ? esc_html__('Toggle pros', 'king-addons')
            : esc_html__('Toggle cons', 'king-addons');

        return sprintf(
            '<button type="button" class="king-addons-pros-cons__toggle" aria-expanded="true" aria-controls="%1$s" aria-label="%2$s"><span class="king-addons-pros-cons__toggle-icon" aria-hidden="true"></span></button>',
            esc_attr($list_id),
            esc_attr($label)
        );
    }

    /**
     * Check if section is collapsible (Pro).
     *
     * @param string $type Section type.
     * @param array<string, mixed> $settings Settings.
     * @return bool
     */
    protected function is_section_collapsible(string $type, array $settings): bool
    {
        return false;
    }

    /**
     * Section data attributes (Pro).
     *
     * @param string $type Section type.
     * @param array<string, mixed> $settings Settings.
     * @return string
     */
    protected function get_section_data_attributes(string $type, array $settings): string
    {
        return '';
    }

    /**
     * Format section title with count.
     *
     * @param string $type Section type.
     * @param string $title Title.
     * @param int $count Count.
     * @param array<string, mixed> $settings Settings.
     * @return string
     */
    protected function format_section_title(string $type, string $title, int $count, array $settings): string
    {
        $show_count = ($settings['kng_show_count'] ?? '') === 'yes';
        if (!$show_count) {
            return $title;
        }

        return sprintf('%s (%d)', $title, $count);
    }

    /**
     * Generate section list id.
     *
     * @param string $type Section type.
     * @return string
     */
    protected function get_section_list_id(string $type): string
    {
        return 'kng-pros-cons-' . $this->get_id() . '-' . $type . '-list';
    }

    /**
     * Wrapper classes.
     *
     * @param array<string, mixed> $settings Settings.
     * @param array<string, mixed> $context Context.
     * @return array<int, string>
     */
    protected function get_wrapper_classes(array $settings, array $context): array
    {
        $layout = $settings['kng_layout'] ?? 'side';
        $preset = $this->get_preset($settings);
        $icon_position = $settings['kng_item_icon_position'] ?? 'left';
        $show_icons = ($settings['kng_show_item_icons'] ?? 'yes') === 'yes';
        $stack_mobile = ($settings['kng_stack_on_mobile'] ?? 'yes') === 'yes';

        $classes = [
            'king-addons-pros-cons',
            'king-addons-pros-cons--layout-' . sanitize_html_class((string) $layout),
            'king-addons-pros-cons--preset-' . sanitize_html_class($preset),
            'king-addons-pros-cons--icon-' . sanitize_html_class((string) $icon_position),
        ];

        if ($stack_mobile) {
            $classes[] = 'king-addons-pros-cons--stacked-mobile';
        }

        if (!$show_icons) {
            $classes[] = 'king-addons-pros-cons--no-icons';
        }

        if (!($context['show_pros'] ?? false) || !($context['show_cons'] ?? false)) {
            $classes[] = 'king-addons-pros-cons--single';
        }

        return array_filter($classes);
    }

    /**
     * Build data attributes for the wrapper.
     *
     * @param array<string, mixed> $settings Settings.
     * @return string
     */
    protected function get_data_attributes(array $settings): string
    {
        $attributes = [
            'data-widget-id' => $this->get_id(),
            'data-tooltip' => $this->is_tooltip_enabled() ? 'yes' : 'no',
        ];

        $output = [];
        foreach ($attributes as $key => $value) {
            $output[] = esc_attr($key) . '="' . esc_attr((string) $value) . '"';
        }

        return ' ' . implode(' ', $output);
    }

    /**
     * Preset options.
     *
     * @return array<string, string>
     */
    protected function get_preset_options(): array
    {
        return [
            'clean' => esc_html__('Clean', 'king-addons'),
            'card' => esc_html__('Card', 'king-addons'),
        ];
    }

    /**
     * Sanitize preset.
     *
     * @param array<string, mixed> $settings Settings.
     * @return string
     */
    protected function get_preset(array $settings): string
    {
        $preset = $settings['kng_preset'] ?? 'clean';
        $options = $this->get_preset_options();

        if (!isset($options[$preset])) {
            return 'clean';
        }

        return (string) $preset;
    }

    /**
     * Render schema output (Pro).
     *
     * @param array<string, mixed> $settings Settings.
     * @param array<string, mixed> $context Context.
     */
    protected function render_schema(array $settings, array $context): void
    {
        // Reserved for Pro.
    }
}
