<?php
/**
 * WooCommerce Floating Cart Icon Widget (Free).
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Displays a floating cart icon with item count and quick summary.
 */
class WooCommerce_Floating_Cart_Icon extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-woocommerce-floating-cart-icon';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('WooCommerce Floating Cart Icon', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'eicon-cart-medium';
    }

    /**
     * Style dependencies.
     *
     * @return array<int, string>
     */
    public function get_style_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-woocommerce-floating-cart-icon-style',
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
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-woocommerce-floating-cart-icon-script',
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
        return ['woocommerce', 'cart', 'floating', 'badge', 'checkout'];
    }

    /**
     * Register Elementor controls.
     *
     * @return void
     */
    public function get_custom_help_url()
    {
        return 'mailto:bug@kingaddons.com?subject=Bug Report - King Addons&body=Please describe the issue';
    }

    public function register_controls(): void
    {
        $this->register_content_controls();
        $this->register_layout_controls();
        $this->register_panel_controls();
        $this->register_style_controls();
        $this->register_pro_notice_controls();
    }

    /**
     * Render widget output.
     *
     * @return void
     */
    public function render(): void
    {
        $cart = $this->get_cart();
        if ($cart === null) {
            return;
        }

        $settings = $this->get_settings_for_display();

        $count = (int) $cart->get_cart_contents_count();
        $subtotal = $cart->get_subtotal();
        $cart_url = wc_get_cart_url();
        $checkout_url = wc_get_checkout_url();

        $position = $settings['kng_position'] ?? 'right';
        $show_badge = ($settings['kng_show_badge'] ?? 'yes') === 'yes';
        $show_label = ($settings['kng_show_label'] ?? 'yes') === 'yes';
        $show_subtotal = ($settings['kng_show_subtotal'] ?? 'yes') === 'yes';

        $icon = $settings['kng_icon'] ?? [
            'value' => 'eicon-cart-medium',
            'library' => 'elementor',
        ];

        $offset_x = isset($settings['kng_offset_x']['size'], $settings['kng_offset_x']['unit'])
            ? $settings['kng_offset_x']['size'] . $settings['kng_offset_x']['unit']
            : '18px';

        $offset_y = isset($settings['kng_offset_y']['size'], $settings['kng_offset_y']['unit'])
            ? $settings['kng_offset_y']['size'] . $settings['kng_offset_y']['unit']
            : '18px';

        $wrapper_classes = ['king-addons-floating-cart'];
        $wrapper_classes[] = $position === 'left' ? 'king-addons-floating-cart--left' : 'king-addons-floating-cart--right';

        $button_classes = ['king-addons-floating-cart__button'];
        if ($count === 0) {
            $button_classes[] = 'is-empty';
        }

        $style_attr = $position === 'left'
            ? 'left:' . esc_attr($offset_x) . ';bottom:' . esc_attr($offset_y) . ';'
            : 'right:' . esc_attr($offset_x) . ';bottom:' . esc_attr($offset_y) . ';';

        $items = array_slice($cart->get_cart(), 0, 2, true);
        $remaining = max(0, $count - count($items));

        ?>
        <div class="<?php echo esc_attr(implode(' ', $wrapper_classes)); ?>" style="<?php echo esc_attr($style_attr); ?>">
            <button type="button"
                class="<?php echo esc_attr(implode(' ', $button_classes)); ?>"
                aria-expanded="false"
                aria-label="<?php echo esc_attr__('Open cart preview', 'king-addons'); ?>"
                data-trigger="click"
                data-auto-open="no">
                <span class="king-addons-floating-cart__icon" aria-hidden="true">
                    <?php Icons_Manager::render_icon($icon, ['aria-hidden' => 'true']); ?>
                </span>
                <?php if ($show_label) : ?>
                    <span class="king-addons-floating-cart__label"><?php echo esc_html($settings['kng_label'] ?? esc_html__('Cart', 'king-addons')); ?></span>
                <?php endif; ?>
                <?php if ($show_badge) : ?>
                    <span class="king-addons-floating-cart__badge" aria-label="<?php echo esc_attr(sprintf(_n('%d item in cart', '%d items in cart', $count, 'king-addons'), $count)); ?>">
                        <?php echo esc_html($count); ?>
                    </span>
                <?php endif; ?>
            </button>
            <div class="king-addons-floating-cart__panel" hidden aria-hidden="true">
                <div class="king-addons-floating-cart__panel-head">
                    <span class="king-addons-floating-cart__panel-title"><?php echo esc_html__('Cart summary', 'king-addons'); ?></span>
                    <span class="king-addons-floating-cart__count">
                        <?php echo esc_html(sprintf(_n('%d item', '%d items', $count, 'king-addons'), $count)); ?>
                    </span>
                </div>
                <div class="king-addons-floating-cart__panel-body">
                    <?php if ($count === 0) : ?>
                        <p class="king-addons-floating-cart__empty"><?php echo esc_html__('Your cart is empty.', 'king-addons'); ?></p>
                    <?php else : ?>
                        <ul class="king-addons-floating-cart__items">
                            <?php foreach ($items as $cart_item_key => $cart_item) : ?>
                                <?php
                                $product = $cart_item['data'] ?? null;
                                if (!$product) {
                                    continue;
                                }
                                ?>
                                <li class="king-addons-floating-cart__item">
                                    <span class="king-addons-floating-cart__item-name"><?php echo esc_html($product->get_name()); ?></span>
                                    <span class="king-addons-floating-cart__item-qty"><?php echo esc_html('x ' . ($cart_item['quantity'] ?? 1)); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if ($remaining > 0) : ?>
                            <p class="king-addons-floating-cart__more">
                                <?php echo esc_html(sprintf(_n('and %d more item', 'and %d more items', $remaining, 'king-addons'), $remaining)); ?>
                            </p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <div class="king-addons-floating-cart__panel-footer">
                    <?php if ($show_subtotal) : ?>
                        <div class="king-addons-floating-cart__subtotal">
                            <span class="king-addons-floating-cart__subtotal-label"><?php echo esc_html__('Subtotal', 'king-addons'); ?></span>
                            <span class="king-addons-floating-cart__subtotal-value"><?php echo wp_kses_post(wc_price((float) $subtotal)); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="king-addons-floating-cart__actions">
                        <a class="king-addons-floating-cart__link king-addons-floating-cart__link--cart" href="<?php echo esc_url($cart_url); ?>">
                            <?php echo esc_html__('View cart', 'king-addons'); ?>
                        </a>
                        <a class="king-addons-floating-cart__link king-addons-floating-cart__link--checkout" href="<?php echo esc_url($checkout_url); ?>">
                            <?php echo esc_html__('Checkout', 'king-addons'); ?>
                        </a>
                    </div>
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
            'kng_icon',
            [
                'label' => esc_html__('Icon', 'king-addons'),
                'type' => Controls_Manager::ICONS,
                'default' => [
                    'value' => 'eicon-cart-medium',
                    'library' => 'elementor',
                ],
            ]
        );

        $this->add_control(
            'kng_label',
            [
                'label' => esc_html__('Label', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Cart', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_show_label',
            [
                'label' => esc_html__('Show Label', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Show', 'king-addons'),
                'label_off' => esc_html__('Hide', 'king-addons'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_show_badge',
            [
                'label' => esc_html__('Show Count Badge', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Show', 'king-addons'),
                'label_off' => esc_html__('Hide', 'king-addons'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Layout controls for floating placement.
     *
     * @return void
     */
    protected function register_layout_controls(): void
    {
        $this->start_controls_section(
            'kng_layout_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Floating Button', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_position',
            [
                'label' => esc_html__('Side', 'king-addons'),
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
                'default' => 'right',
            ]
        );

        $this->add_control(
            'kng_offset_x',
            [
                'label' => esc_html__('Horizontal Offset', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 120,
                    ],
                ],
                'default' => [
                    'size' => 18,
                    'unit' => 'px',
                ],
            ]
        );

        $this->add_control(
            'kng_offset_y',
            [
                'label' => esc_html__('Bottom Offset', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 200,
                    ],
                ],
                'default' => [
                    'size' => 18,
                    'unit' => 'px',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Quick view panel controls.
     *
     * @return void
     */
    protected function register_panel_controls(): void
    {
        $this->start_controls_section(
            'kng_panel_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Quick View', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_show_subtotal',
            [
                'label' => esc_html__('Show Subtotal', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_upgrade_hover',
            [
                'label' => sprintf(__('Hover to open %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'classes' => 'king-addons-pro-control no-distance',
            ]
        );

        $this->add_control(
            'kng_upgrade_drawer',
            [
                'label' => sprintf(__('Drawer layout %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'classes' => 'king-addons-pro-control no-distance',
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
            'kng_style_button',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Button', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_button_typography',
                'selector' => '{{WRAPPER}} .king-addons-floating-cart__button',
            ]
        );

        $this->add_control(
            'kng_button_text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-floating-cart__button' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_bg',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-floating-cart__button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_bg_hover',
            [
                'label' => esc_html__('Hover Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-floating-cart__button:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_button_border',
                'selector' => '{{WRAPPER}} .king-addons-floating-cart__button',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_button_shadow',
                'selector' => '{{WRAPPER}} .king-addons-floating-cart__button',
            ]
        );

        $this->add_responsive_control(
            'kng_button_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-floating-cart__button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'kng_style_badge',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Badge', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'kng_badge_bg',
            [
                'label' => esc_html__('Badge Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-floating-cart__badge' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_badge_color',
            [
                'label' => esc_html__('Badge Text', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-floating-cart__badge' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_badge_shadow',
                'selector' => '{{WRAPPER}} .king-addons-floating-cart__badge',
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'kng_style_panel',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Panel', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_panel_typography',
                'selector' => '{{WRAPPER}} .king-addons-floating-cart__panel',
            ]
        );

        $this->add_control(
            'kng_panel_bg',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-floating-cart__panel' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_panel_text',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-floating-cart__panel' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_panel_border',
                'selector' => '{{WRAPPER}} .king-addons-floating-cart__panel',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_panel_shadow',
                'selector' => '{{WRAPPER}} .king-addons-floating-cart__panel',
            ]
        );

        $this->add_responsive_control(
            'kng_panel_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-floating-cart__panel' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render pro upgrade notice.
     *
     * @return void
     */
    public function register_pro_notice_controls(): void
    {
        if (!king_addons_freemius()->can_use_premium_code__premium_only()) {
            Core::renderProFeaturesSection(
                $this,
                '',
                Controls_Manager::RAW_HTML,
                'woocommerce-floating-cart-icon',
                [
                    'Hover trigger and auto-open after add to cart',
                    'Drawer layout with slide animation',
                    'Show thumbnails, subtotals, and remove actions',
                    'Hide button on empty cart or specific devices',
                    'Custom checkout label and quick actions',
                ]
            );
        }
    }

    /**
     * Get WooCommerce cart instance when available.
     *
     * @return \WC_Cart|null
     */
    protected function get_cart(): ?\WC_Cart
    {
        if (!function_exists('WC')) {
            return null;
        }

        $wc = WC();
        if (!is_object($wc) || !method_exists($wc, 'cart')) {
            return null;
        }

        return $wc->cart ?: null;
    }
}




