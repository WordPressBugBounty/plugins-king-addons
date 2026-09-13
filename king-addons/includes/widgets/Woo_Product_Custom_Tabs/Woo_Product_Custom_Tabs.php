<?php
/**
 * Woo Product Custom Tabs widget.
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Plugin;
use Elementor\Repeater;
use Elementor\Group_Control_Typography;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Allows adding custom tabs from text or ACF/meta fields.
 */
class Woo_Product_Custom_Tabs extends Abstract_Single_Widget
{
    /**
     * Get widget scripts.
     *
     * @return array<string>
     */
    public function get_script_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-product-custom-tabs-script'];
    }

    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'woo_product_custom_tabs';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Product Custom Tabs', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-woo-product-custom-tabs';
    }

    /**
     * Widget categories.
     *
     * @return array<int, string>
     */
    public function get_categories(): array
    {
        return ['king-addons-woo-builder'];
    }

    /**
     * Widget styles.
     *
     * @return array<string>
     */
    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-product-custom-tabs-style'];
    }

    /**
     * Register controls.
     *
     * @return void
     */
    protected function register_controls(): void
    {
        $this->start_controls_section(
            'section_content',
            [
                'label' => esc_html__('Tabs', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $repeater = new Repeater();
        $repeater->add_control(
            'tab_title',
            [
                'label' => esc_html__('Tab Title', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Custom Tab', 'king-addons'),
            ]
        );
        $repeater->add_control(
            'tab_source',
            [
                'label' => esc_html__('Content Source', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'text' => esc_html__('Custom text', 'king-addons'),
                    'acf' => sprintf(__('ACF field %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                    'meta' => sprintf(__('Meta field %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                ],
                'default' => 'text',
            ]
        );
        $repeater->add_control(
            'tab_slug',
            [
                'label' => esc_html__('Tab ID / slug', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => esc_html__('custom-tab', 'king-addons'),
            ]
        );
        $repeater->add_control(
            'tab_text',
            [
                'label' => esc_html__('Content', 'king-addons'),
                'type' => Controls_Manager::WYSIWYG,
                'default' => esc_html__('Your custom content here.', 'king-addons'),
                'condition' => [
                    'tab_source' => 'text',
                ],
            ]
        );
        $repeater->add_control(
            'tab_field_key',
            [
                'label' => esc_html__('Field key/name (Pro)', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'condition' => [
                    'tab_source' => ['acf', 'meta'],
                ],
            ]
        );
        $repeater->add_control(
            'tab_field_fallback',
            [
                'label' => esc_html__('Fallback text (Pro)', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'condition' => [
                    'tab_source' => ['acf', 'meta'],
                ],
            ]
        );
        $repeater->add_control(
            'tab_show_if_empty',
            [
                'label' => sprintf(__('Show if empty %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
            ]
        );
        $repeater->add_control(
            'tab_priority',
            [
                'label' => esc_html__('Priority (merge into WC tabs, Pro)', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'default' => 80,
            ]
        );

        $this->add_control(
            'tabs',
            [
                'label' => esc_html__('Tabs', 'king-addons'),
                'type' => Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'default' => [
                    [
                        'tab_title' => esc_html__('Custom Tab', 'king-addons'),
                        'tab_source' => 'text',
                        'tab_text' => esc_html__('Your custom content here.', 'king-addons'),
                    ],
                ],
                'title_field' => '{{ tab_title }}',
            ]
        );

        $this->add_control(
            'layout',
            [
                'label' => esc_html__('Layout', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'list' => esc_html__('Stacked list', 'king-addons'),
                    'tabs' => sprintf(__('Tabs %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                    'accordion' => sprintf(__('Accordion %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                ],
                'default' => 'list',
            ]
        );

        $this->add_control(
            'integration_mode',
            [
                'label' => sprintf(__('Positioning %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'standalone' => esc_html__('Standalone widget', 'king-addons'),
                    'merge_wc_tabs' => esc_html__('Merge into Woo tabs', 'king-addons'),
                ],
                'default' => 'standalone',
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style',
            [
                'label' => esc_html__('Style', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'title_typo',
                'selector' => '{{WRAPPER}} .ka-woo-custom-tabs__title',
            ]
        );

        $this->add_control(
            'title_color',
            [
                'label' => esc_html__('Title Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-custom-tabs__title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'content_typo',
                'selector' => '{{WRAPPER}} .ka-woo-custom-tabs__content',
            ]
        );

        $this->add_control(
            'content_color',
            [
                'label' => esc_html__('Content Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-custom-tabs__content' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'item_gap',
            [
                'label' => esc_html__('Item Gap', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => ['min' => 0, 'max' => 40],
                ],
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-custom-tabs__item' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Build tab payloads from widget settings for the given product.
     *
     * @param array<string, mixed> $settings Widget settings.
     * @param \WC_Product          $product  Product.
     * @return array<int, array{title: string, slug: string, content: string, priority: int}>
     */
    public static function prepare_tabs_for_product(array $settings, $product): array
    {
        $can_pro = function_exists('king_addons_can_use_pro') && king_addons_can_use_pro();
        $tabs = $settings['tabs'] ?? [];
        if (empty($tabs) || !is_array($tabs)) {
            return [];
        }
        if (!$can_pro) {
            $tabs = [reset($tabs)];
        }

        $prepared = [];
        foreach ($tabs as $tab) {
            $title = $tab['tab_title'] ?? '';
            $slug = !empty($tab['tab_slug']) ? sanitize_title($tab['tab_slug']) : sanitize_title($title);
            $source = $tab['tab_source'] ?? 'text';
            $content = '';

            if ('text' === $source) {
                $content = $tab['tab_text'] ?? '';
            } elseif (in_array($source, ['acf', 'meta'], true) && $can_pro) {
                $field = $tab['tab_field_key'] ?? '';
                if ($field) {
                    if ('acf' === $source && function_exists('get_field')) {
                        $content = get_field($field, $product->get_id());
                    } else {
                        $content = get_post_meta($product->get_id(), $field, true);
                    }
                }
                if (empty($content) && !empty($tab['tab_field_fallback'])) {
                    $content = $tab['tab_field_fallback'];
                }
            }

            if ((empty($content) || (is_string($content) && '' === trim((string) $content))) && empty($tab['tab_show_if_empty'])) {
                continue;
            }

            $prepared[] = [
                'title' => $title,
                'slug' => $slug ?: 'custom-tab-' . count($prepared),
                'content' => is_string($content) ? $content : '',
                'priority' => isset($tab['tab_priority']) ? (int) $tab['tab_priority'] : 80,
            ];
        }

        return $prepared;
    }

    /**
     * Merge Custom Tabs widgets into Woo's tab list before Product Tabs renders.
     *
     * Widget render() is too late when this widget sits below Product Tabs.
     *
     * @param array<string, array<string, mixed>> $wc_tabs Existing tabs.
     * @return array<string, array<string, mixed>>
     */
    public static function inject_merged_tabs(array $wc_tabs): array
    {
        if (!function_exists('king_addons_can_use_pro') || !king_addons_can_use_pro()) {
            return $wc_tabs;
        }
        $product = (isset($GLOBALS['product']) && $GLOBALS['product'] instanceof \WC_Product)
            ? $GLOBALS['product']
            : (function_exists('wc_get_product') ? wc_get_product(get_the_ID()) : null);
        if (!$product) {
            return $wc_tabs;
        }

        $template_ids = [];
        $current = (int) apply_filters('king_addons/woo_builder/current_template_id', 0, 'single_product');
        if ($current > 0) {
            $template_ids[] = $current;
        } else {
            $found = get_posts(
                [
                    'post_type' => 'elementor_library',
                    'post_status' => 'publish',
                    'posts_per_page' => 20,
                    'fields' => 'ids',
                    'meta_key' => 'ka_woo_template_type',
                    'meta_value' => 'single_product',
                    'orderby' => 'date',
                    'order' => 'DESC',
                    'no_found_rows' => true,
                    'suppress_filters' => true,
                ]
            );
            foreach ($found as $id) {
                $template_ids[] = (int) $id;
            }
        }

        $settings_list = [];
        $walk = static function ($els) use (&$walk, &$settings_list) {
            foreach ($els as $el) {
                if (($el['elType'] ?? '') === 'widget' && ($el['widgetType'] ?? '') === 'woo_product_custom_tabs') {
                    $settings_list[] = $el['settings'] ?? [];
                }
                if (!empty($el['elements'])) {
                    $walk($el['elements']);
                }
            }
        };
        foreach (array_unique($template_ids) as $template_id) {
            $data = json_decode((string) get_post_meta($template_id, '_elementor_data', true), true);
            if (is_array($data)) {
                $walk($data);
            }
        }

        foreach ($settings_list as $settings) {
            if (($settings['integration_mode'] ?? 'standalone') !== 'merge_wc_tabs') {
                continue;
            }
            foreach (self::prepare_tabs_for_product($settings, $product) as $tab) {
                $key = $tab['slug'];
                $wc_tabs[$key] = [
                    'title' => $tab['title'],
                    'priority' => $tab['priority'],
                    'callback' => static function () use ($tab): void {
                        echo '<div class="ka-woo-custom-tabs__content">' . wp_kses_post($tab['content']) . '</div>';
                    },
                ];
            }
        }

        return $wc_tabs;
    }

    /**
     * Render widget output.
     *
     * @return void
     */
    protected function render(): void
    {
        $product = $this->get_product();
        if (!$product) {
            $this->render_missing_product_notice();
            return;
        }

        $settings = $this->get_settings_for_display();
        $can_pro = king_addons_can_use_pro();

        // Both values end up in class names and data attributes.
        $layout = (string) ($settings['layout'] ?? 'list');
        if (!in_array($layout, ['list', 'tabs', 'accordion'], true)) {
            $layout = 'list';
        }
        if (!$can_pro && in_array($layout, ['tabs', 'accordion'], true)) {
            $layout = 'list';
        }

        $integration_mode = (string) ($settings['integration_mode'] ?? 'standalone');
        if (!in_array($integration_mode, ['standalone', 'merge_wc_tabs'], true)) {
            $integration_mode = 'standalone';
        }
        if (!$can_pro && 'merge_wc_tabs' === $integration_mode) {
            $integration_mode = 'standalone';
        }

        $prepared = self::prepare_tabs_for_product($settings, $product);
        if (empty($prepared)) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode() && empty($settings['tabs'])) {
                echo '<div class="king-addons-woo-builder-notice">'
                    . esc_html__('Add at least one tab.', 'king-addons')
                    . '</div>';
            }
            return;
        }

        if ('merge_wc_tabs' === $integration_mode && $can_pro) {
            $is_editor = class_exists(Plugin::class) && Plugin::instance()->editor->is_edit_mode();
            if (!$is_editor) {
                return;
            }
        }

        if ('tabs' === $layout) {
            echo '<div class="ka-woo-custom-tabs ka-woo-custom-tabs--tabs">';
            echo '<div class="ka-woo-custom-tabs__nav">';
            $first = $prepared[0]['slug'];
            foreach ($prepared as $tab) {
                $active = $tab['slug'] === $first ? ' is-active' : '';
                echo '<button type="button" class="ka-woo-custom-tabs__tab' . esc_attr($active) . '" data-tab="' . esc_attr($tab['slug']) . '">' . esc_html($tab['title']) . '</button>';
            }
            echo '</div>';
            echo '<div class="ka-woo-custom-tabs__panels">';
            foreach ($prepared as $tab) {
                $active = $tab['slug'] === $first ? ' is-active' : '';
                echo '<div class="ka-woo-custom-tabs__panel' . esc_attr($active) . '" data-tab="' . esc_attr($tab['slug']) . '">';
                echo '<div class="ka-woo-custom-tabs__content">' . wp_kses_post($tab['content']) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo '</div>';
            }
            echo '</div>';
            echo '</div>';
            return;
        }

        if ('accordion' === $layout) {
            echo '<div class="ka-woo-custom-tabs ka-woo-custom-tabs--accordion">';
            foreach ($prepared as $tab) {
                echo '<div class="ka-woo-custom-tabs__item">';
                echo '<button type="button" class="ka-woo-custom-tabs__accordion-toggle" data-tab="' . esc_attr($tab['slug']) . '">' . esc_html($tab['title']) . '</button>';
                echo '<div class="ka-woo-custom-tabs__accordion-body">';
                echo '<div class="ka-woo-custom-tabs__content">' . wp_kses_post($tab['content']) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo '</div>';
                echo '</div>';
            }
            echo '</div>';
            return;
        }

        // Fallback stacked list.
        $output = '';
        foreach ($prepared as $tab) {
            $output .= '<div class="ka-woo-custom-tabs__item">';
            $output .= '<h4 class="ka-woo-custom-tabs__title">' . esc_html($tab['title']) . '</h4>';
            $output .= '<div class="ka-woo-custom-tabs__content">' . wp_kses_post($tab['content']) . '</div>';
            $output .= '</div>';
        }

        echo '<div class="ka-woo-custom-tabs">' . $output . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}

add_filter('woocommerce_product_tabs', [Woo_Product_Custom_Tabs::class, 'inject_merged_tabs'], 50);








