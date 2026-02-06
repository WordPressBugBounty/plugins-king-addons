<?php
/**
 * Ajax Add to Cart Widget (Free).
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders the Ajax Add to Cart widget.
 */
class Ajax_Add_To_Cart extends Widget_Base
{
    /**
     * Detect whether the current request is running inside Elementor editor/preview.
     *
     * Elementor loads the page inside an iframe while editing, which is still a frontend request.
     * Some interactive behaviors (like redirects) must be disabled there to avoid breaking editing.
     *
     * @return bool
     */
    protected function is_elementor_editor_context(): bool
    {
        // Fast path: common query arg used by Elementor preview iframe.
        if (isset($_GET['elementor-preview'])) {
            return true;
        }

        if (!defined('ELEMENTOR_VERSION') || !class_exists('\Elementor\Plugin')) {
            return false;
        }

        // Be defensive: these properties/methods exist in Elementor but we guard to avoid fatals.
        $plugin = \Elementor\Plugin::$instance;

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

    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-ajax-add-to-cart';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Ajax Add to Cart', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-ajax-add-to-cart';
    }

    /**
     * Scripts.
     *
     * @return array<int, string>
     */
    public function get_script_depends(): array
    {
        return [
            'jquery',
            'wc-add-to-cart',
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-ajax-add-to-cart-script',
        ];
    }

    /**
     * Styles.
     *
     * @return array<int, string>
     */
    public function get_style_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-ajax-add-to-cart-style',
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
        return ['woocommerce', 'cart', 'ajax', 'button', 'add'];
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
        $this->register_style_notice_controls();
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

        $quantity = !empty($settings['kng_quantity']) ? max(1, (int) $settings['kng_quantity']) : 1;
        $button_text = $settings['kng_button_text'] ?? esc_html__('Add to cart', 'king-addons');
        $success_text = $settings['kng_success_text'] ?? esc_html__('Added to cart', 'king-addons');

        $wrapper_classes = ['king-addons-ajax-atc'];
        ?>
        <div class="<?php echo esc_attr(implode(' ', $wrapper_classes)); ?>"
             data-product-id="<?php echo esc_attr((string) $product_id); ?>"
             data-quantity="<?php echo esc_attr((string) $quantity); ?>"
             data-success-text="<?php echo esc_attr($success_text); ?>">
            <button type="button" class="king-addons-ajax-atc__button">
                <span class="king-addons-ajax-atc__label"><?php echo esc_html($button_text); ?></span>
            </button>
            <a class="added_to_cart wc-forward" href="<?php echo esc_url(wc_get_cart_url()); ?>">
                <?php echo esc_html__('View cart', 'woocommerce'); ?>
            </a>
            <div class="king-addons-ajax-atc__notice" role="status" aria-live="polite"></div>
        </div>
        <?php
    }

    /**
     * Register content controls.
     *
     * @return void
     */
    protected function register_content_controls(): void
    {
        $this->start_controls_section(
            'kng_content_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Settings', 'king-addons'),
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
                'description' => esc_html__('On product pages, automatically use the current product.', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_product_id',
            [
                'label' => esc_html__('Product ID (fallback)', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'step' => 1,
                'description' => esc_html__('Used when not on a single product or when current product is unavailable.', 'king-addons'),
                'condition' => [
                    'kng_use_current_product!' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_quantity',
            [
                'label' => esc_html__('Quantity', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 20,
                'step' => 1,
                'default' => 1,
            ]
        );

        $this->add_control(
            'kng_button_text',
            [
                'label' => esc_html__('Button Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Add to cart', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_success_text',
            [
                'label' => esc_html__('Success Message', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Added to cart', 'king-addons'),
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
            'kng_alignment',
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
                    '{{WRAPPER}} .king-addons-ajax-atc' => 'justify-content: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_button_typography',
                'selector' => '{{WRAPPER}} .king-addons-ajax-atc__button',
            ]
        );

        $this->add_control(
            'kng_button_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-ajax-atc__button' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_bg',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-ajax-atc__button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_color_hover',
            [
                'label' => esc_html__('Hover Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-ajax-atc__button:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_bg_hover',
            [
                'label' => esc_html__('Hover Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-ajax-atc__button:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_button_border',
                'selector' => '{{WRAPPER}} .king-addons-ajax-atc__button',
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
                    '{{WRAPPER}} .king-addons-ajax-atc__button' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Notice styles.
     *
     * @return void
     */
    protected function register_style_notice_controls(): void
    {
        $this->start_controls_section(
            'kng_style_notice_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Notice', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_notice_typography',
                'selector' => '{{WRAPPER}} .king-addons-ajax-atc__notice',
            ]
        );

        $this->add_control(
            'kng_notice_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-ajax-atc__notice' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Resolve product ID based on settings.
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
     * Pro notice.
     *
     * @return void
     */
    public function register_pro_notice_controls(): void
    {
        if (!king_addons_freemius()->can_use_premium_code__premium_only()) {
            Core::renderProFeaturesSection($this, '', Controls_Manager::RAW_HTML, 'ajax-add-to-cart', [
                'Icons and badges on the button',
                'Mini-cart fragment refresh toggle',
                'Success/redirect behaviors and messages',
                'Quantity selector and variable products',
            ]);
        }
    }
}







