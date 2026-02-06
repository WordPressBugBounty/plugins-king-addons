<?php
/**
 * Quick Card Grid Widget (Free)
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
 * Renders the Quick Card Grid widget.
 */
class Quick_Card_Grid extends Widget_Base
{
    /**
     * Get widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-quick-card-grid';
    }

    /**
     * Get widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Quick Card Grid', 'king-addons');
    }

    /**
     * Get widget icon class.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-simple-card-grid';
    }

    /**
     * Enqueue script dependencies.
     *
     * @return array<int, string>
     */
    public function get_script_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-quick-card-grid-script',
        ];
    }

    /**
     * Enqueue style dependencies.
     *
     * @return array<int, string>
     */
    public function get_style_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-quick-card-grid-style',
        ];
    }

    /**
     * Get widget categories.
     *
     * @return array<int, string>
     */
    public function get_categories(): array
    {
        return ['king-addons'];
    }

    /**
     * Get widget keywords.
     *
     * @return array<int, string>
     */
    public function get_keywords(): array
    {
        return ['grid', 'card', 'content', 'king-addons'];
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
        $this->register_content_controls();
        $this->register_layout_controls();
        $this->register_style_card_controls();
        $this->register_style_text_controls();
        $this->register_style_button_controls();
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
        $this->render_output($settings);
    }

    /**
     * Render grid output for the frontend.
     *
     * @param array<string, mixed> $settings Widget settings.
     *
     * @return void
     */
    public function render_output(array $settings): void
    {
        $cards = $settings['kng_cards'] ?? [];
        if (empty($cards)) {
            return;
        }

        $wrapper_classes = $this->get_wrapper_classes($settings);
        $wrapper_style = $this->get_wrapper_style($settings);

        ?>
        <div class="<?php echo esc_attr(implode(' ', $wrapper_classes)); ?>"<?php echo $wrapper_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
            <div class="king-addons-card-grid__grid">
                <?php foreach ($cards as $card) : ?>
                    <?php $this->render_card($settings, $card); ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    protected function register_content_controls(): void
    {
        $this->start_controls_section(
            'kng_content_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Cards', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $repeater = new Repeater();

        $repeater->add_control(
            'kng_card_image',
            [
                'label' => esc_html__('Image', 'king-addons'),
                'type' => Controls_Manager::MEDIA,
                'default' => [
                    'url' => Utils::get_placeholder_image_src(),
                ],
            ]
        );

        $repeater->add_control(
            'kng_card_title',
            [
                'label' => esc_html__('Title', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Card title', 'king-addons'),
                'label_block' => true,
            ]
        );

        $repeater->add_control(
            'kng_card_description',
            [
                'label' => esc_html__('Description', 'king-addons'),
                'type' => Controls_Manager::TEXTAREA,
                'rows' => 3,
                'default' => esc_html__('Card description goes here.', 'king-addons'),
            ]
        );

        $repeater->add_control(
            'kng_card_button_text',
            [
                'label' => esc_html__('Button Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Learn more', 'king-addons'),
                'label_block' => true,
            ]
        );

        $repeater->add_control(
            'kng_card_button_link',
            [
                'label' => esc_html__('Button Link', 'king-addons'),
                'type' => Controls_Manager::URL,
                'placeholder' => esc_html__('https://your-link.com', 'king-addons'),
            ]
        );

        $repeater->add_control(
            'kng_card_link_enable',
            [
                'label' => esc_html__('Make Whole Card Clickable', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'description' => esc_html__('Editor safety: the card link is disabled in the Elementor editor to prevent accidental navigation.', 'king-addons'),
            ]
        );

        $repeater->add_control(
            'kng_card_link',
            [
                'label' => esc_html__('Card Link', 'king-addons'),
                'type' => Controls_Manager::URL,
                'placeholder' => esc_html__('https://your-link.com', 'king-addons'),
                'condition' => [
                    'kng_card_link_enable' => 'yes',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Image_Size::get_type(),
            [
                'name' => 'kng_card_image',
                'default' => 'medium',
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'kng_cards',
            [
                'label' => esc_html__('Cards', 'king-addons'),
                'type' => Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'default' => [
                    [
                        'kng_card_title' => esc_html__('Modern layout', 'king-addons'),
                        'kng_card_description' => esc_html__('Clean card grid with safe spacing.', 'king-addons'),
                        'kng_card_button_text' => esc_html__('Read more', 'king-addons'),
                        'kng_card_image' => ['url' => Utils::get_placeholder_image_src()],
                    ],
                    [
                        'kng_card_title' => esc_html__('Responsive by default', 'king-addons'),
                        'kng_card_description' => esc_html__('Columns adapt to devices.', 'king-addons'),
                        'kng_card_button_text' => esc_html__('Details', 'king-addons'),
                        'kng_card_image' => ['url' => Utils::get_placeholder_image_src()],
                    ],
                    [
                        'kng_card_title' => esc_html__('Ready to use', 'king-addons'),
                        'kng_card_description' => esc_html__('Balanced paddings and typography.', 'king-addons'),
                        'kng_card_button_text' => esc_html__('Explore', 'king-addons'),
                        'kng_card_image' => ['url' => Utils::get_placeholder_image_src()],
                    ],
                ],
                'title_field' => '{{{ kng_card_title }}}',
            ]
        );

        $this->end_controls_section();
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
                'tablet_default' => 2,
                'mobile_default' => 1,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-card-grid__grid' => 'grid-template-columns: repeat({{VALUE}}, minmax(0, 1fr));',
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
                    '{{WRAPPER}} .king-addons-card-grid__grid' => 'gap: {{SIZE}}{{UNIT}};',
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
                    '{{WRAPPER}} .king-addons-card-grid__card' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_card_border',
                'selector' => '{{WRAPPER}} .king-addons-card-grid__card',
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
                    '{{WRAPPER}} .king-addons-card-grid__card' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_card_shadow',
                'selector' => '{{WRAPPER}} .king-addons-card-grid__card',
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
                    '{{WRAPPER}} .king-addons-card-grid__card' => 'box-shadow: none !important;',
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
                    '{{WRAPPER}} .king-addons-card-grid__card:hover' => 'box-shadow: none !important;',
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
                    '{{WRAPPER}} .king-addons-card-grid__card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
                'selector' => '{{WRAPPER}} .king-addons-card-grid__title',
            ]
        );

        $this->add_control(
            'kng_title_color',
            [
                'label' => esc_html__('Title Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-card-grid__title' => 'color: {{VALUE}};',
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
                    '{{WRAPPER}} .king-addons-card-grid__title' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_description_typography',
                'selector' => '{{WRAPPER}} .king-addons-card-grid__description',
            ]
        );

        $this->add_control(
            'kng_description_color',
            [
                'label' => esc_html__('Description Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-card-grid__description' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_description_spacing',
            [
                'label' => esc_html__('Description Bottom Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 80],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-card-grid__description' => 'margin-bottom: {{SIZE}}{{UNIT}};',
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
                'prefix_class' => 'king-addons-card-grid--text-align-',
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
                'prefix_class' => 'king-addons-card-grid--button-align-',
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
                'prefix_class' => 'king-addons-card-grid--button-text-align-',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_button_typography',
                'selector' => '{{WRAPPER}} .king-addons-card-grid__button',
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
                    '{{WRAPPER}} .king-addons-card-grid__button' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_background',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-card-grid__button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_button_border',
                'selector' => '{{WRAPPER}} .king-addons-card-grid__button',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_button_shadow',
                'selector' => '{{WRAPPER}} .king-addons-card-grid__button',
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
                    '{{WRAPPER}} .king-addons-card-grid__button:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_background_hover',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-card-grid__button:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_border_color_hover',
            [
                'label' => esc_html__('Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-card-grid__button:hover' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_button_shadow_hover',
                'selector' => '{{WRAPPER}} .king-addons-card-grid__button:hover',
            ]
        );

        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->add_responsive_control(
            'kng_button_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-card-grid__button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
                    '{{WRAPPER}} .king-addons-card-grid__button' => 'border-radius: {{SIZE}}{{UNIT}};',
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
                    '{{WRAPPER}} .king-addons-card-grid__button' => 'box-shadow: none !important;',
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
                    '{{WRAPPER}} .king-addons-card-grid__button:hover' => 'box-shadow: none !important;',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render a single card item.
     *
     * @param array<string, mixed> $settings Widget settings.
     * @param array<string, mixed> $card     Card data.
     *
     * @return void
     */
    protected function render_card(array $settings, array $card): void
    {
        $card_classes = ['king-addons-card-grid__card'];
        $card_classes = array_merge($card_classes, $this->get_additional_card_classes($settings, $card));

        $card_link_enabled = ($card['kng_card_link_enable'] ?? '') === 'yes';
        $card_link = $card['kng_card_link'] ?? [];
        $card_link_attributes = $this->get_card_link_attributes($card_link_enabled, $card_link);
        $card_attribute_string = $card_link_attributes ? ' ' . $card_link_attributes : '';

        $image_html = $this->get_card_image($card);

        $title = $card['kng_card_title'] ?? '';
        $description = $card['kng_card_description'] ?? '';
        $button_text = $card['kng_card_button_text'] ?? '';
        $button_link = $card['kng_card_button_link'] ?? [];

        ?>
        <div class="king-addons-card-grid__item">
            <article class="<?php echo esc_attr(implode(' ', array_filter($card_classes))); ?>"<?php echo $card_attribute_string; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
                <?php if (!empty($image_html)) : ?>
                    <div class="king-addons-card-grid__media">
                        <?php echo $image_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </div>
                <?php endif; ?>

                <div class="king-addons-card-grid__body">
                    <?php if (!empty($title)) : ?>
                        <h3 class="king-addons-card-grid__title"><?php echo esc_html($title); ?></h3>
                    <?php endif; ?>
                    <?php if (!empty($description)) : ?>
                        <div class="king-addons-card-grid__description"><?php echo esc_html($description); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($button_text)) : ?>
                        <?php $this->render_button($button_text, $button_link); ?>
                    <?php endif; ?>
                </div>
            </article>
        </div>
        <?php
    }

    /**
     * Build wrapper classes.
     *
     * @param array<string, mixed> $settings Widget settings.
     *
     * @return array<int, string>
     */
    protected function get_wrapper_classes(array $settings): array
    {
        $classes = ['king-addons-card-grid'];

        $text_align = $settings['kng_text_alignment'] ?? 'left';
        $classes[] = 'king-addons-card-grid--text-align-' . sanitize_html_class((string) $text_align);

        $button_align = $settings['kng_button_alignment'] ?? 'left';
        $classes[] = 'king-addons-card-grid--button-align-' . sanitize_html_class((string) $button_align);

        $button_text_align = $settings['kng_button_text_alignment'] ?? 'center';
        $classes[] = 'king-addons-card-grid--button-text-align-' . sanitize_html_class((string) $button_text_align);

        $classes = array_merge($classes, $this->get_additional_wrapper_classes($settings));

        return array_filter($classes);
    }

    /**
     * Build wrapper inline styles.
     *
     * @param array<string, mixed> $settings Widget settings.
     *
     * @return string
     */
    protected function get_wrapper_style(array $settings): string
    {
        $style_parts = [];

        if (isset($settings['kng_grid_gap']['size'])) {
            $style_parts[] = '--kng-card-grid-gap:' . (float) $settings['kng_grid_gap']['size'] . ($settings['kng_grid_gap']['unit'] ?? 'px') . ';';
        }

        if (!empty($style_parts)) {
            return ' style="' . esc_attr(implode(' ', $style_parts)) . '"';
        }

        return '';
    }

    /**
     * Get card image HTML.
     *
     * @param array<string, mixed> $card Card data.
     *
     * @return string
     */
    protected function get_card_image(array $card): string
    {
        if (empty($card['kng_card_image']['id']) && empty($card['kng_card_image']['url'])) {
            return '';
        }

        $image_html = Group_Control_Image_Size::get_attachment_image_html($card, 'kng_card_image');

        if (!empty($image_html)) {
            return $image_html;
        }

        return '<img src="' . esc_url(Utils::get_placeholder_image_src()) . '" alt="' . esc_attr($card['kng_card_title'] ?? '') . '"/>';
    }

    /**
     * Render button element.
     *
     * @param string               $text Button text.
     * @param array<string, mixed> $link Link data.
     *
     * @return void
     */
    protected function render_button(string $text, array $link): void
    {
        $url = $link['url'] ?? '';
        $rel = [];

        if (!empty($link['nofollow'])) {
            $rel[] = 'nofollow';
        }

        if (!empty($link['is_external'])) {
            $rel[] = 'noopener';
            $rel[] = 'noreferrer';
        }

        $attributes = '';

        if (!empty($url)) {
            $attributes .= ' href="' . esc_url($url) . '"';
        }

        if (!empty($link['is_external'])) {
            $attributes .= ' target="_blank"';
        }

        if (!empty($rel)) {
            $attributes .= ' rel="' . esc_attr(implode(' ', array_unique($rel))) . '"';
        }

        ?>
        <a class="king-addons-card-grid__button"<?php echo $attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
            <?php echo esc_html($text); ?>
        </a>
        <?php
    }

    /**
     * Build card-level link data attributes.
     *
     * @param bool                 $enabled Whether link is enabled.
     * @param array<string, mixed> $link    Link data.
     *
     * @return string
     */
    protected function get_card_link_attributes(bool $enabled, array $link): string
    {
        if (!$enabled || empty($link['url'])) {
            return '';
        }

        $attributes = [
            'data-card-link' => esc_url_raw($link['url']),
        ];

        if (!empty($link['is_external'])) {
            $attributes['data-card-link-target'] = '_blank';
        }

        if (!empty($link['nofollow'])) {
            $attributes['data-card-link-rel'] = 'nofollow';
        }

        $output = [];
        foreach ($attributes as $key => $value) {
            $output[] = esc_attr($key) . '="' . esc_attr((string) $value) . '"';
        }

        return implode(' ', $output);
    }

    /**
     * Placeholder for pro wrapper classes.
     *
     * @param array<string, mixed> $settings Widget settings.
     *
     * @return array<int, string>
     */
    protected function get_additional_wrapper_classes(array $settings): array
    {
        unset($settings);
        return [];
    }

    /**
     * Placeholder for pro card classes.
     *
     * @param array<string, mixed> $settings Widget settings.
     * @param array<string, mixed> $card     Card data.
     *
     * @return array<int, string>
     */
    protected function get_additional_card_classes(array $settings, array $card): array
    {
        unset($settings, $card);
        return [];
    }

    /**
     * Render premium upgrade notice.
     *
     * @return void
     */
    public function register_pro_notice_controls(): void
    {
        if (!king_addons_freemius()->can_use_premium_code__premium_only()) {
            Core::renderProFeaturesSection($this, '', Controls_Manager::RAW_HTML, 'quick-card-grid', [
                'Advanced hover animations for cards',
                'Extended styling controls and presets',
                'Additional layout polish for cards and buttons',
            ]);
        }
    }
}







