<?php
/**
 * Woo Products Sorting widget.
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Repeater;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Displays WooCommerce sorting dropdown.
 */
class Woo_Products_Sorting extends Abstract_Archive_Widget
{
    public function get_name(): string
    {
        return 'woo_products_sorting';
    }

    public function get_title(): string
    {
        return esc_html__('Products Sorting', 'king-addons');
    }

    public function get_icon(): string
    {
        return 'eicon-filter';
    }

    public function get_categories(): array
    {
        return ['king-addons-woo-builder'];
    }

    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-products-sorting-style'];
    }

    /**
     * Script dependencies.
     *
     * @return array<int,string>
     */
    public function get_script_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-products-sorting-script'];
    }

    protected function register_controls(): void
    {
        $this->start_controls_section(
            'section_content',
            [
                'label' => esc_html__('Content', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'query_id',
            [
                'label' => sprintf(__('Query ID %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::TEXT,
                'description' => esc_html__('Link with Products Grid / Filters having same ID.', 'king-addons'),
            ]
        );

        $repeater = new Repeater();
        $repeater->add_control(
            'key',
            [
                'label' => esc_html__('Option key', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'menu_order' => esc_html__('Default', 'king-addons'),
                    'popularity' => esc_html__('Popularity', 'king-addons'),
                    'rating' => esc_html__('Rating', 'king-addons'),
                    'date' => esc_html__('Latest', 'king-addons'),
                    'price' => esc_html__('Price: low to high', 'king-addons'),
                    'price-desc' => esc_html__('Price: high to low', 'king-addons'),
                    'rand' => esc_html__('Random', 'king-addons'),
                ],
                'default' => 'menu_order',
            ]
        );

        $repeater->add_control(
            'label',
            [
                'label' => esc_html__('Label', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Default sorting', 'king-addons'),
            ]
        );

        $this->add_control(
            'options',
            [
                'label' => sprintf(__('Options %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'default' => [
                    ['key' => 'menu_order', 'label' => esc_html__('Default sorting', 'king-addons')],
                    ['key' => 'popularity', 'label' => esc_html__('Sort by popularity', 'king-addons')],
                    ['key' => 'rating', 'label' => esc_html__('Sort by rating', 'king-addons')],
                    ['key' => 'date', 'label' => esc_html__('Sort by latest', 'king-addons')],
                    ['key' => 'price', 'label' => esc_html__('Sort by price: low to high', 'king-addons')],
                    ['key' => 'price-desc', 'label' => esc_html__('Sort by price: high to low', 'king-addons')],
                ],
                'title_field' => '{{{ label }}}',
            ]
        );

        $this->add_control(
            'default_option',
            [
                'label' => sprintf(__('Default option %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'menu_order' => esc_html__('Default', 'king-addons'),
                    'popularity' => esc_html__('Popularity', 'king-addons'),
                    'rating' => esc_html__('Rating', 'king-addons'),
                    'date' => esc_html__('Latest', 'king-addons'),
                    'price' => esc_html__('Price: low to high', 'king-addons'),
                    'price-desc' => esc_html__('Price: high to low', 'king-addons'),
                    'rand' => esc_html__('Random', 'king-addons'),
                ],
                'default' => 'menu_order',
            ]
        );

        $this->add_control(
            'layout',
            [
                'label' => sprintf(__('Layout %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'dropdown' => esc_html__('Dropdown', 'king-addons'),
                    'inline' => esc_html__('Inline (Pro)', 'king-addons'),
                ],
                'default' => 'dropdown',
            ]
        );

        $this->end_controls_section();
    }

    protected function render(): void
    {
        if (!class_exists('WooCommerce') || !function_exists('is_shop') || !function_exists('is_product_taxonomy')) {
            return;
        }

        if (!$this->should_render()) {
            $this->render_missing_archive_notice();
            return;
        }

        $settings = $this->get_settings_for_display();
        $layout = $settings['layout'] ?? 'dropdown';
        $can_pro = king_addons_can_use_pro();
        if ('inline' === $layout && !$can_pro) {
            $layout = 'dropdown';
        }

        $options = $settings['options'] ?? [];
        $default = $settings['default_option'] ?? 'menu_order';
        $query_id = $settings['query_id'] ?? '';

        if (!$can_pro) {
            $options = [
                ['key' => 'menu_order', 'label' => esc_html__('Default sorting', 'king-addons')],
                ['key' => 'date', 'label' => esc_html__('Sort by latest', 'king-addons')],
                ['key' => 'price', 'label' => esc_html__('Sort by price: low to high', 'king-addons')],
                ['key' => 'price-desc', 'label' => esc_html__('Sort by price: high to low', 'king-addons')],
            ];
            $query_id = '';
        }

        $options = array_values(array_filter($options, static function ($opt) {
            return !empty($opt['key']);
        }));

        if (empty($options)) {
            $options = [
                ['key' => 'menu_order', 'label' => esc_html__('Default sorting', 'king-addons')],
            ];
        }

        echo '<div class="ka-woo-sorting ka-woo-sorting--' . esc_attr($layout) . '" data-query-id="' . esc_attr($query_id) . '">';

        if ('inline' === $layout) {
            echo '<div class="ka-woo-sorting__inline" role="group" aria-label="' . esc_attr__('Sort products', 'king-addons') . '">';
            foreach ($options as $opt) {
                $key = sanitize_key($opt['key']);
                $label = !empty($opt['label']) ? $opt['label'] : $key;
                $active = ($default === $key) ? ' is-active' : '';
                echo '<button type="button" class="ka-woo-sorting__btn' . esc_attr($active) . '" data-sort="' . esc_attr($key) . '">' . esc_html($label) . '</button>';
            }
            echo '</div>';
        } else {
            echo '<select class="ka-woo-sorting__select" aria-label="' . esc_attr__('Sort products', 'king-addons') . '">';
            foreach ($options as $opt) {
                $key = sanitize_key($opt['key']);
                $label = !empty($opt['label']) ? $opt['label'] : $key;
                $selected = selected($key, $default, false);
                echo '<option value="' . esc_attr($key) . '" ' . $selected . '>' . esc_html($label) . '</option>';
            }
            echo '</select>';
        }

        echo '</div>';
    }
}






