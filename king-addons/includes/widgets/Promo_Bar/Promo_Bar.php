<?php
/**
 * Announcement / Promo Bar Widget (Free).
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders a sticky announcement bar.
 */
class Promo_Bar extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-promo-bar';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Announcement / Promo Bar', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-promo-bar';
    }

    /**
     * Style dependencies.
     *
     * @return array<int, string>
     */
    public function get_style_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-promo-bar-style',
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
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-promo-bar-script',
        ];
    }

    /**
     * Categories.
     *
     * @return array<int, string>
     */
    public function get_categories(): array
    {
        return ['king-addons'];
    }

    /**
     * Keywords.
     *
     * @return array<int, string>
     */
    public function get_keywords(): array
    {
        return ['announcement', 'promo', 'bar', 'notification'];
    }

        public function get_custom_help_url()
        {
            return 'mailto:bug@kingaddons.com?subject=Bug Report - King Addons&body=Please describe the issue';
        }

        /**
     * Register controls.
     *
     * @return void
     */
    public function register_controls(): void
    {
        $this->register_content_controls();
        $this->register_layout_controls();
        $this->register_style_controls();
        $this->register_pro_notice_controls();
    }

    /**
     * Render widget.
     *
     * @return void
     */
    public function render(): void
    {
        $settings = $this->get_settings_for_display();
        $attrs = $this->get_wrapper_attributes($settings);

        $button_url = $settings['kng_button_url'] ?? [];
        $href = is_array($button_url) ? ($button_url['url'] ?? '') : '';
        $is_external = is_array($button_url) && !empty($button_url['is_external']);
        $nofollow = is_array($button_url) && !empty($button_url['nofollow']);
        $rels = [];
        if ($is_external) {
            $rels[] = 'noopener';
            $rels[] = 'noreferrer';
        }
        if ($nofollow) {
            $rels[] = 'nofollow';
        }
        $rel_attr = !empty($rels) ? ' rel="' . esc_attr(implode(' ', array_unique($rels))) . '"' : '';
        $target_attr = $is_external ? ' target="_blank"' : '';

        ?>
        <div class="king-addons-promo-bar" <?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
            <div class="king-addons-promo-bar__inner">
                <div class="king-addons-promo-bar__content">
                    <?php if (!empty($settings['kng_badge_text'])) : ?>
                        <span class="king-addons-promo-bar__badge">
                            <?php echo esc_html($settings['kng_badge_text']); ?>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($settings['kng_title'])) : ?>
                        <span class="king-addons-promo-bar__title"><?php echo esc_html($settings['kng_title']); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($settings['kng_description'])) : ?>
                        <span class="king-addons-promo-bar__description"><?php echo esc_html($settings['kng_description']); ?></span>
                    <?php endif; ?>
                </div>

                <div class="king-addons-promo-bar__actions">
                    <?php if (!empty($settings['kng_button_text']) && !empty($href)) : ?>
                        <a class="king-addons-promo-bar__button" href="<?php echo esc_url($href); ?>"<?php echo $target_attr . $rel_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
                            <?php echo esc_html($settings['kng_button_text']); ?>
                        </a>
                    <?php endif; ?>
                    <?php if (($settings['kng_show_close'] ?? 'yes') === 'yes') : ?>
                        <button class="king-addons-promo-bar__close" type="button" aria-label="<?php echo esc_attr__('Close', 'king-addons'); ?>">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Content controls.
     *
     * @return void
     */
    protected function register_content_controls(): void
    {
        $this->start_controls_section(
            'kng_content_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Content', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_badge_text',
            [
                'label' => esc_html__('Badge Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => esc_html__('New', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_title',
            [
                'label' => esc_html__('Title', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Limited-time offer', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_description',
            [
                'label' => esc_html__('Description', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Save 20% today only.', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_button_text',
            [
                'label' => esc_html__('Button Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Shop now', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_button_url',
            [
                'label' => esc_html__('Button Link', 'king-addons'),
                'type' => Controls_Manager::URL,
                'placeholder' => esc_html__('https://', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_show_close',
            [
                'label' => esc_html__('Show Close Button', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Layout controls.
     *
     * @return void
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
            'kng_position',
            [
                'label' => esc_html__('Position', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'top',
                'options' => [
                    'top' => esc_html__('Top', 'king-addons'),
                    'bottom' => esc_html__('Bottom', 'king-addons'),
                ],
            ]
        );

        $this->add_control(
            'kng_sticky',
            [
                'label' => esc_html__('Sticky on Scroll', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_responsive_control(
            'kng_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-promo-bar__inner' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Style controls.
     *
     * @return void
     */
    protected function register_style_controls(): void
    {
        $this->start_controls_section(
            'kng_style_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Styles', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'kng_bg_color',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-promo-bar' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_border',
                'selector' => '{{WRAPPER}} .king-addons-promo-bar',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_shadow',
                'selector' => '{{WRAPPER}} .king-addons-promo-bar',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_text_typography',
                'selector' => '{{WRAPPER}} .king-addons-promo-bar__content',
            ]
        );

        $this->add_control(
            'kng_text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-promo-bar__title' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-promo-bar__description' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_badge_typography',
                'selector' => '{{WRAPPER}} .king-addons-promo-bar__badge',
            ]
        );

        $this->add_control(
            'kng_badge_color',
            [
                'label' => esc_html__('Badge Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-promo-bar__badge' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_badge_bg',
            [
                'label' => esc_html__('Badge Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-promo-bar__badge' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_button_typography',
                'selector' => '{{WRAPPER}} .king-addons-promo-bar__button',
            ]
        );

        $this->add_control(
            'kng_button_color',
            [
                'label' => esc_html__('Button Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-promo-bar__button' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_bg',
            [
                'label' => esc_html__('Button Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-promo-bar__button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_radius',
            [
                'label' => esc_html__('Button Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 40],
                    '%' => ['min' => 0, 'max' => 100],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-promo-bar__button' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render pro notice.
     *
     * @return void
     */
    public function register_pro_notice_controls(): void
    {
        if (!king_addons_can_use_pro()) {
            Core::renderProFeaturesSection($this, '', Controls_Manager::RAW_HTML, 'promo-bar', [
                'Display conditions (page, UTM, schedule)',
                'Persistent close and countdown timer',
                'Device targeting and button icons',
                'Bottom sheet style and animations',
            ]);
        }
    }

    /**
     * Build wrapper attributes.
     *
     * @param array<string, mixed> $settings Settings.
     *
     * @return string
     */
    protected function get_wrapper_attributes(array $settings): string
    {
        $position = $settings['kng_position'] ?? 'top';
        $position = in_array($position, ['top', 'bottom'], true) ? $position : 'top';

        $attrs = [
            'data-position' => $position,
            'data-sticky' => (($settings['kng_sticky'] ?? 'yes') === 'yes') ? 'yes' : 'no',
            'data-close-persist' => 'no',
            'data-conditions' => '',
        ];

        $compiled = [];
        foreach ($attrs as $key => $value) {
            $compiled[] = esc_attr($key) . '="' . esc_attr($value) . '"';
        }

        return implode(' ', $compiled);
    }
}







