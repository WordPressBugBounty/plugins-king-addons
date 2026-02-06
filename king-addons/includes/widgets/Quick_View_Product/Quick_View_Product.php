<?php
/**
 * Quick View Product Widget (Free).
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

require_once __DIR__ . '/ajax-handler.php';

/**
 * Renders the Quick View Product widget.
 */
class Quick_View_Product extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-quick-view-product';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Quick View Product', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-quick-view-product';
    }

    /**
     * Script dependencies.
     *
     * @return array<int, string>
     */
    public function get_script_depends(): array
    {
        return [
            'jquery',
            'wc-add-to-cart-variation',
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-quick-view-product-script',
        ];
    }

    /**
     * Style dependencies.
     *
     * @return array<int, string>
     */
    public function get_style_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-quick-view-product-style',
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
        return ['woocommerce', 'product', 'quick view', 'modal'];
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
        $this->register_style_button_controls();
        $this->register_style_modal_controls();
        $this->register_pro_notice_controls();
    }

    /**
     * Render widget output.
     *
     * @return void
     */
    public function render(): void
    {
        if (!class_exists('\WooCommerce')) {
            return;
        }

        $settings = $this->get_settings_for_display();
        $product_id = $this->resolve_product_id($settings);

        if (!$product_id) {
            return;
        }

        $button_text = $settings['kng_button_text'] ?? esc_html__('Quick View', 'king-addons');
        $wrapper_classes = ['king-addons-quick-view'];
        $nonce = wp_create_nonce('king_addons_qv');
        $settings_payload = wp_json_encode([
            'show_image' => $settings['kng_show_image'] ?? 'yes',
            'show_price' => $settings['kng_show_price'] ?? 'yes',
            'show_rating' => $settings['kng_show_rating'] ?? 'yes',
            'show_excerpt' => $settings['kng_show_excerpt'] ?? 'yes',
            'show_button' => $settings['kng_show_button'] ?? 'yes',
            'pro_gallery' => 'no',
            'pro_variations' => 'no',
            'pro_sticky_cta' => 'no',
        ]);

        ?>
        <div class="<?php echo esc_attr(implode(' ', $wrapper_classes)); ?>"
             data-product-id="<?php echo esc_attr((string) $product_id); ?>"
             data-nonce="<?php echo esc_attr($nonce); ?>"
             data-qv-settings="<?php echo esc_attr($settings_payload); ?>">
            <button type="button" class="king-addons-quick-view__trigger">
                <span class="king-addons-quick-view__label"><?php echo esc_html($button_text); ?></span>
                <span class="king-addons-quick-view__spinner" aria-hidden="true"></span>
            </button>
            <div class="king-addons-quick-view__modal" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr__('Product quick view', 'king-addons'); ?>">
                <div class="king-addons-quick-view__overlay"></div>
                <div class="king-addons-quick-view__content">
                    <button type="button" class="king-addons-quick-view__close" aria-label="<?php echo esc_attr__('Close', 'king-addons'); ?>">×</button>
                    <div class="king-addons-quick-view__body">
                        <div class="king-addons-quick-view__loader"></div>
                        <div class="king-addons-quick-view__product"></div>
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
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Product', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_use_current_product',
            [
                'label' => esc_html__('Use Current Product (Single)', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_product_id',
            [
                'label' => esc_html__('Product ID (fallback)', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'step' => 1,
                'condition' => [
                    'kng_use_current_product!' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_show_image',
            [
                'label' => esc_html__('Show Image', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_show_price',
            [
                'label' => esc_html__('Show Price', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_show_rating',
            [
                'label' => esc_html__('Show Rating', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_show_excerpt',
            [
                'label' => esc_html__('Show Short Description', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_show_button',
            [
                'label' => esc_html__('Show Add to Cart', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_button_text',
            [
                'label' => esc_html__('Button Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Quick View', 'king-addons'),
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Button styles.
     *
     * @return void
     */
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
                'label' => esc_html__('Alignment', 'king-addons'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'flex-start' => [
                        'title' => esc_html__('Left', 'king-addons'),
                        'icon' => 'eicon-h-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'king-addons'),
                        'icon' => 'eicon-h-align-center',
                    ],
                    'flex-end' => [
                        'title' => esc_html__('Right', 'king-addons'),
                        'icon' => 'eicon-h-align-right',
                    ],
                ],
                'default' => 'flex-start',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-view' => 'justify-content: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_button_typography',
                'selector' => '{{WRAPPER}} .king-addons-quick-view__trigger',
            ]
        );

        $this->add_control(
            'kng_button_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-view__trigger' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_bg',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-view__trigger' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_color_hover',
            [
                'label' => esc_html__('Hover Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-view__trigger:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_bg_hover',
            [
                'label' => esc_html__('Hover Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-view__trigger:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_button_border',
                'selector' => '{{WRAPPER}} .king-addons-quick-view__trigger',
            ]
        );

        $this->add_control(
            'kng_button_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 50],
                    '%' => ['min' => 0, 'max' => 100],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-view__trigger' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Modal styles.
     *
     * @return void
     */
    protected function register_style_modal_controls(): void
    {
        $this->start_controls_section(
            'kng_style_modal_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Modal', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'kng_overlay_color',
            [
                'label' => esc_html__('Overlay Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-view__overlay' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_modal_bg',
            [
                'label' => esc_html__('Modal Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-view__content' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_modal_shadow',
                'selector' => '{{WRAPPER}} .king-addons-quick-view__content',
            ]
        );

        $this->add_control(
            'kng_modal_radius',
            [
                'label' => esc_html__('Modal Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 40],
                    '%' => ['min' => 0, 'max' => 100],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-view__content' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Resolve product ID.
     *
     * @param array<string, mixed> $settings Settings.
     *
     * @return int
     */
    protected function resolve_product_id(array $settings): int
    {
        $use_current = ($settings['kng_use_current_product'] ?? 'yes') === 'yes';
        if ($use_current && is_singular('product')) {
            $product_id = get_the_ID();
            if ($product_id) {
                return (int) $product_id;
            }
        }

        if (!empty($settings['kng_product_id'])) {
            return (int) $settings['kng_product_id'];
        }

        return 0;
    }

    /**
     * Pro notice section.
     *
     * @return void
     */
    public function register_pro_notice_controls(): void
    {
        if (!king_addons_freemius()->can_use_premium_code__premium_only()) {
            Core::renderProFeaturesSection($this, '', Controls_Manager::RAW_HTML, 'quick-view-product', [
                'Gallery thumbnails and zoom',
                'Variation picker inside modal',
                'Sticky CTA and related products',
                'Custom triggers and badges',
            ]);
        }
    }
}







