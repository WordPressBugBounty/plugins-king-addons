<?php
/**
 * Woo Product Badges widget.
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Displays product badges (Sale/New; Pro: Best seller/Custom).
 */
class Woo_Product_Badges extends Abstract_Single_Widget
{
    public function get_name(): string
    {
        return 'woo_product_badges';
    }

    public function get_title(): string
    {
        return esc_html__('Product Badges', 'king-addons');
    }

    public function get_icon(): string
    {
        return 'eicon-price-badge';
    }

    public function get_categories(): array
    {
        return ['king-addons-woo-builder'];
    }

    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-product-badges-style'];
    }

    protected function register_controls(): void
    {
        $this->start_controls_section(
            'section_content',
            [
                'label' => esc_html__('Badges', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'show_sale',
            [
                'label' => esc_html__('Show Sale', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_new',
            [
                'label' => esc_html__('Show New', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'new_days',
            [
                'label' => esc_html__('New if added within days', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'default' => 14,
            ]
        );

        $this->add_control(
            'show_percent',
            [
                'label' => sprintf(__('Show sale percent %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'show_best',
            [
                'label' => sprintf(__('Show Best Seller %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'custom_badge',
            [
                'label' => sprintf(__('Custom badge text %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
            ]
        );

        $this->add_control(
            'custom_meta_key',
            [
                'label' => sprintf(__('Custom badge meta key %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::TEXT,
                'description' => esc_html__('Read custom badge text from product meta/ACF.', 'king-addons'),
            ]
        );

        $this->add_control(
            'badge_position',
            [
                'label' => sprintf(__('Badge position %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'top-left' => esc_html__('Top Left', 'king-addons'),
                    'top-right' => esc_html__('Top Right', 'king-addons'),
                    'bottom-left' => esc_html__('Bottom Left', 'king-addons'),
                    'bottom-right' => esc_html__('Bottom Right', 'king-addons'),
                ],
                'default' => 'top-left',
            ]
        );

        $this->add_control(
            'badge_order',
            [
                'label' => sprintf(__('Badge priority %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::NUMBER,
                'default' => 10,
                'description' => esc_html__('Lower number shows first; Pro-only applies.', 'king-addons'),
            ]
        );

        $this->end_controls_section();
    }

    protected function render(): void
    {
        $product = $this->get_product();
        if (!$product) {
            $this->render_missing_product_notice();
            return;
        }

        $settings = $this->get_settings_for_display();
        $can_pro = king_addons_can_use_pro();

        $badges = [];
        $position = $settings['badge_position'] ?? 'top-left';

        if (!empty($settings['show_sale']) && $product->is_on_sale()) {
            $label = esc_html__('Sale', 'king-addons');
            if (!empty($settings['show_percent']) && $can_pro) {
                $regular = (float) $product->get_regular_price();
                $sale = (float) $product->get_sale_price();
                if ($regular > 0 && $sale > 0 && $sale < $regular) {
                    $percent = max(0, round((($regular - $sale) / $regular) * 100));
                    $label = '-' . $percent . '%';
                }
            }
            $badges[] = [
                'text' => $label,
                'class' => 'ka-woo-badge--sale',
                'priority' => 5,
            ];
        }

        if (!empty($settings['show_new'])) {
            $days = isset($settings['new_days']) ? (int) $settings['new_days'] : 14;
            $created = strtotime($product->get_date_created()->date('Y-m-d H:i:s'));
            if ($created && (time() - $created) <= $days * DAY_IN_SECONDS) {
                $badges[] = [
                    'text' => esc_html__('New', 'king-addons'),
                    'class' => 'ka-woo-badge--new',
                    'priority' => 8,
                ];
            }
        }

        if (!empty($settings['show_best']) && $can_pro) {
            $sales = (int) $product->get_total_sales();
            if ($sales > 0) {
                $badges[] = [
                    'text' => esc_html__('Best seller', 'king-addons'),
                    'class' => 'ka-woo-badge--best',
                    'priority' => 7,
                ];
            }
        }

        if (!empty($settings['custom_badge']) && $can_pro) {
            $badges[] = [
                'text' => wp_kses_post($settings['custom_badge']),
                'class' => 'ka-woo-badge--custom',
                'priority' => (int) ($settings['badge_order'] ?? 10),
            ];
        }

        if (!empty($settings['custom_meta_key']) && $can_pro) {
            $meta_val = get_post_meta($product->get_id(), sanitize_key($settings['custom_meta_key']), true);
            if (!empty($meta_val)) {
                $badges[] = [
                    'text' => wp_kses_post($meta_val),
                    'class' => 'ka-woo-badge--custom',
                    'priority' => (int) ($settings['badge_order'] ?? 10),
                ];
            }
        }

        if (empty($badges)) {
            return;
        }

        usort($badges, static function ($a, $b) {
            return ($a['priority'] ?? 10) <=> ($b['priority'] ?? 10);
        });

        echo '<div class="ka-woo-badges ka-woo-badges--' . esc_attr($position) . '">';
        foreach ($badges as $badge) {
            echo '<span class="ka-woo-badge ' . esc_attr($badge['class']) . '">' . esc_html($badge['text']) . '</span>';
        }
        echo '</div>';
    }
}







