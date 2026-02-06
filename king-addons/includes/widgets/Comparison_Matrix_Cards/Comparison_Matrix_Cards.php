<?php
/**
 * Comparison Matrix Cards Widget (Free).
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Plugin;
use Elementor\Repeater;
use Elementor\Widget_Base;
use WC_Product;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders a comparison matrix in card layout.
 */
class Comparison_Matrix_Cards extends Widget_Base
{

    /**
     * Widget slug.
     */
    public function get_name(): string
    {
        return 'king-addons-comparison-matrix-cards';
    }

    /**
     * Widget title.
     */
    public function get_title(): string
    {
        return esc_html__('Comparison Matrix Cards', 'king-addons');
    }

    /**
     * Widget icon.
     */
    public function get_icon(): string
    {
        return 'eicon-price-table';
    }

    /**
     * Style dependencies.
     *
     * @return array<int, string>
     */
    public function get_style_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-comparison-matrix-cards-style',
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
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-comparison-matrix-cards-script',
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
        return ['comparison', 'matrix', 'cards', 'pricing', 'plans'];
    }

        public function get_custom_help_url()
        {
            return 'mailto:bug@kingaddons.com?subject=Bug Report - King Addons&body=Please describe the issue';
        }

        /**
     * Register widget controls.
     */
    public function register_controls(): void
    {
        $this->register_data_source_controls();
        $this->register_plan_controls();
        $this->register_feature_controls();
        $this->register_layout_controls();
        $this->register_highlight_controls();
        $this->register_sticky_controls();
        $this->register_search_controls();
        $this->register_style_card_controls();
        $this->register_style_header_controls();
        $this->register_style_feature_controls();
        $this->register_style_button_controls();
        $this->register_style_highlight_controls();
        $this->register_style_sticky_controls();
        $this->register_style_search_controls();
        $this->register_pro_notice_controls();
    }

    /**
     * Render widget output.
     */
    public function render(): void
    {
        $settings = $this->get_settings_for_display();
        $data = $this->get_render_data($settings);
        if (!empty($data['pro_locked'])) {
            if ($this->is_editor_mode()) {
                echo '<div class="king-addons-pro-notice">' . esc_html__('Upgrade to Pro to use ACF/WooCommerce data sources and advanced comparison tools.', 'king-addons') . '</div>';
            }
            return;
        }

        $plans = $data['plans'] ?? [];
        $features = $data['features'] ?? [];

        if (count($plans) < 2) {
            return;
        }

        $context = $this->get_render_context($settings, count($plans));
        $wrapper_classes = $this->get_wrapper_classes($settings, $context);
        $wrapper_attributes = $this->get_wrapper_attributes($settings, $context);

        ?>
        <div class="<?php echo esc_attr(implode(' ', $wrapper_classes)); ?>"<?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
            <?php $this->render_controls_bar($context); ?>
            <div class="king-addons-comparison-matrix-cards__grid">
                <?php foreach ($plans as $index => $plan) : ?>
                    <?php $this->render_card($plan, $features, $index); ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Register data source controls.
     */
    protected function register_data_source_controls(): void
    {
        $this->start_controls_section(
            'kng_data_source_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Data Source', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_data_source',
            [
                'label' => esc_html__('Source', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'manual',
                'options' => [
                    'manual' => esc_html__('Manual', 'king-addons'),
                    'acf' => $this->get_pro_label(esc_html__('ACF', 'king-addons')),
                    'woo' => $this->get_pro_label(esc_html__('WooCommerce', 'king-addons')),
                ],
            ]
        );

        $this->add_control(
            'kng_max_plans',
            [
                'label' => $this->get_pro_label(esc_html__('Max Plans', 'king-addons')),
                'type' => Controls_Manager::NUMBER,
                'default' => 4,
                'min' => 2,
                'max' => 8,
                'step' => 1,
                'classes' => $this->get_pro_control_class(),
                'condition' => [
                    'kng_data_source!' => 'manual',
                ],
                'description' => esc_html__('Applies to ACF/Woo data sources.', 'king-addons'),
            ]
        );

        $this->end_controls_section();

        $this->register_acf_controls();
        $this->register_woo_controls();
    }

    /**
     * Register ACF data controls.
     */
    protected function register_acf_controls(): void
    {
        $this->start_controls_section(
            'kng_acf_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('ACF Mapping', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
                'condition' => [
                    'kng_data_source' => 'acf',
                ],
            ]
        );

        if (!function_exists('get_field')) {
            $this->add_control(
                'kng_acf_missing_notice',
                [
                    'type' => Controls_Manager::RAW_HTML,
                    'raw' => esc_html__('ACF plugin is not active. Enable ACF to use this data source.', 'king-addons'),
                    'content_classes' => 'king-addons-pro-notice',
                ]
            );
        }

        $this->add_control(
            'kng_acf_post_source',
            [
                'label' => $this->get_pro_label(esc_html__('Post ID Source', 'king-addons')),
                'type' => Controls_Manager::SELECT,
                'default' => 'current',
                'options' => [
                    'current' => esc_html__('Current Post', 'king-addons'),
                    'custom' => esc_html__('Custom Post ID', 'king-addons'),
                ],
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->add_control(
            'kng_acf_post_id',
            [
                'label' => $this->get_pro_label(esc_html__('Custom Post ID', 'king-addons')),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'classes' => $this->get_pro_control_class(),
                'condition' => [
                    'kng_acf_post_source' => 'custom',
                ],
            ]
        );

        $this->add_control(
            'kng_acf_plans_repeater',
            [
                'label' => $this->get_pro_label(esc_html__('Plans Repeater Field', 'king-addons')),
                'type' => Controls_Manager::TEXT,
                'placeholder' => 'plans',
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->add_control(
            'kng_acf_plan_name_field',
            [
                'label' => $this->get_pro_label(esc_html__('Plan Name Field', 'king-addons')),
                'type' => Controls_Manager::TEXT,
                'placeholder' => 'plan_name',
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->add_control(
            'kng_acf_plan_badge_field',
            [
                'label' => $this->get_pro_label(esc_html__('Badge Field', 'king-addons')),
                'type' => Controls_Manager::TEXT,
                'placeholder' => 'plan_badge',
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->add_control(
            'kng_acf_plan_description_field',
            [
                'label' => $this->get_pro_label(esc_html__('Description Field', 'king-addons')),
                'type' => Controls_Manager::TEXT,
                'placeholder' => 'plan_description',
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->add_control(
            'kng_acf_plan_price_field',
            [
                'label' => $this->get_pro_label(esc_html__('Price Field', 'king-addons')),
                'type' => Controls_Manager::TEXT,
                'placeholder' => 'plan_price',
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->add_control(
            'kng_acf_plan_subtitle_field',
            [
                'label' => $this->get_pro_label(esc_html__('Subtitle Field', 'king-addons')),
                'type' => Controls_Manager::TEXT,
                'placeholder' => 'plan_subtitle',
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->add_control(
            'kng_acf_plan_cta_label_field',
            [
                'label' => $this->get_pro_label(esc_html__('CTA Label Field', 'king-addons')),
                'type' => Controls_Manager::TEXT,
                'placeholder' => 'cta_label',
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->add_control(
            'kng_acf_plan_cta_link_field',
            [
                'label' => $this->get_pro_label(esc_html__('CTA Link Field', 'king-addons')),
                'type' => Controls_Manager::TEXT,
                'placeholder' => 'cta_link',
                'classes' => $this->get_pro_control_class(),
                'description' => esc_html__('Accepts ACF Link field or URL string.', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_acf_plan_footer_field',
            [
                'label' => $this->get_pro_label(esc_html__('Footer Note Field', 'king-addons')),
                'type' => Controls_Manager::TEXT,
                'placeholder' => 'plan_footer',
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->add_control(
            'kng_acf_plan_features_repeater',
            [
                'label' => $this->get_pro_label(esc_html__('Plan Features Repeater', 'king-addons')),
                'type' => Controls_Manager::TEXT,
                'placeholder' => 'features',
                'classes' => $this->get_pro_control_class(),
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'kng_acf_feature_label_field',
            [
                'label' => $this->get_pro_label(esc_html__('Feature Label Field', 'king-addons')),
                'type' => Controls_Manager::TEXT,
                'placeholder' => 'feature_label',
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->add_control(
            'kng_acf_feature_value_field',
            [
                'label' => $this->get_pro_label(esc_html__('Feature Value Field', 'king-addons')),
                'type' => Controls_Manager::TEXT,
                'placeholder' => 'feature_value',
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->add_control(
            'kng_acf_feature_value_type_field',
            [
                'label' => $this->get_pro_label(esc_html__('Feature Value Type Field', 'king-addons')),
                'type' => Controls_Manager::TEXT,
                'placeholder' => 'feature_type',
                'classes' => $this->get_pro_control_class(),
                'description' => esc_html__('Optional field with values: included, excluded, limited, text.', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_acf_feature_tooltip_field',
            [
                'label' => $this->get_pro_label(esc_html__('Tooltip Field', 'king-addons')),
                'type' => Controls_Manager::TEXT,
                'placeholder' => 'feature_tooltip',
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->add_control(
            'kng_acf_feature_group_field',
            [
                'label' => $this->get_pro_label(esc_html__('Group Field', 'king-addons')),
                'type' => Controls_Manager::TEXT,
                'placeholder' => 'feature_group',
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register WooCommerce data controls.
     */
    protected function register_woo_controls(): void
    {
        $this->start_controls_section(
            'kng_woo_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('WooCommerce Mapping', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
                'condition' => [
                    'kng_data_source' => 'woo',
                ],
            ]
        );

        if (!class_exists('WooCommerce')) {
            $this->add_control(
                'kng_woo_missing_notice',
                [
                    'type' => Controls_Manager::RAW_HTML,
                    'raw' => esc_html__('WooCommerce plugin is not active. Enable WooCommerce to use this data source.', 'king-addons'),
                    'content_classes' => 'king-addons-pro-notice',
                ]
            );
        }

        $this->add_control(
            'kng_woo_products_source',
            [
                'label' => $this->get_pro_label(esc_html__('Products Source', 'king-addons')),
                'type' => Controls_Manager::SELECT,
                'default' => 'manual',
                'options' => [
                    'manual' => esc_html__('Manual Select', 'king-addons'),
                    'related' => esc_html__('Related Products', 'king-addons'),
                    'upsell' => esc_html__('Upsells', 'king-addons'),
                    'cross_sell' => esc_html__('Cross-sells', 'king-addons'),
                ],
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->add_control(
            'kng_woo_products',
            [
                'label' => $this->get_pro_label(esc_html__('Select Products', 'king-addons')),
                'type' => 'king-addons-ajax-select2',
                'options' => 'ajaxselect2/getPostsByPostType',
                'query_slug' => 'product',
                'multiple' => true,
                'label_block' => true,
                'classes' => $this->get_pro_control_class(),
                'condition' => [
                    'kng_woo_products_source' => 'manual',
                ],
            ]
        );

        $this->add_control(
            'kng_woo_products_limit',
            [
                'label' => $this->get_pro_label(esc_html__('Max Products', 'king-addons')),
                'type' => Controls_Manager::NUMBER,
                'default' => 4,
                'min' => 2,
                'max' => 8,
                'step' => 1,
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->add_control(
            'kng_woo_compare_variations',
            [
                'label' => $this->get_pro_label(esc_html__('Compare Variations', 'king-addons')),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
                'classes' => $this->get_pro_control_class(),
                'condition' => [
                    'kng_woo_products_source' => 'manual',
                ],
                'description' => esc_html__('Compare variations of a single variable product.', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_woo_variation_title_mode',
            [
                'label' => $this->get_pro_label(esc_html__('Variation Title', 'king-addons')),
                'type' => Controls_Manager::SELECT,
                'default' => 'full',
                'options' => [
                    'full' => esc_html__('Full Name', 'king-addons'),
                    'attributes' => esc_html__('Attributes Only', 'king-addons'),
                    'parent' => esc_html__('Parent Name', 'king-addons'),
                ],
                'classes' => $this->get_pro_control_class(),
                'condition' => [
                    'kng_woo_compare_variations' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_woo_variation_subtitle_mode',
            [
                'label' => $this->get_pro_label(esc_html__('Variation Subtitle', 'king-addons')),
                'type' => Controls_Manager::SELECT,
                'default' => 'none',
                'options' => [
                    'none' => esc_html__('None', 'king-addons'),
                    'attributes' => esc_html__('Attributes', 'king-addons'),
                ],
                'classes' => $this->get_pro_control_class(),
                'condition' => [
                    'kng_woo_compare_variations' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_woo_variation_notice',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => esc_html__('When enabled, only the first selected product is used to load variations.', 'king-addons'),
                'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
                'condition' => [
                    'kng_woo_compare_variations' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_woo_show_price',
            [
                'label' => $this->get_pro_label(esc_html__('Show Price', 'king-addons')),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->add_control(
            'kng_woo_cta_mode',
            [
                'label' => $this->get_pro_label(esc_html__('CTA Type', 'king-addons')),
                'type' => Controls_Manager::SELECT,
                'default' => 'product',
                'options' => [
                    'product' => esc_html__('Product Page', 'king-addons'),
                    'add_to_cart' => esc_html__('Add to Cart', 'king-addons'),
                ],
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->add_control(
            'kng_woo_cta_label',
            [
                'label' => $this->get_pro_label(esc_html__('CTA Label', 'king-addons')),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('View Product', 'king-addons'),
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $attributes = new Repeater();
        $attributes->add_control(
            'kng_woo_attr_slug',
            [
                'label' => esc_html__('Attribute Slug', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => 'pa_color',
                'label_block' => true,
            ]
        );
        $attributes->add_control(
            'kng_woo_attr_label',
            [
                'label' => esc_html__('Label Override', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => esc_html__('Color', 'king-addons'),
            ]
        );
        $attributes->add_control(
            'kng_woo_attr_group',
            [
                'label' => esc_html__('Group', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => esc_html__('Performance', 'king-addons'),
            ]
        );
        $attributes->add_control(
            'kng_woo_attr_tooltip',
            [
                'label' => esc_html__('Tooltip', 'king-addons'),
                'type' => Controls_Manager::TEXTAREA,
                'rows' => 2,
            ]
        );
        $attributes->add_control(
            'kng_woo_attr_fallback',
            [
                'label' => esc_html__('Fallback Meta Key', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => 'custom_feature',
            ]
        );

        $this->add_control(
            'kng_woo_attributes',
            [
                'label' => $this->get_pro_label(esc_html__('Compare Attributes', 'king-addons')),
                'type' => Controls_Manager::REPEATER,
                'fields' => $attributes->get_controls(),
                'title_field' => '{{{ kng_woo_attr_slug }}}',
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register manual plan controls.
     */
    protected function register_plan_controls(): void
    {
        $this->start_controls_section(
            'kng_plans_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Plans', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
                'condition' => [
                    'kng_data_source' => 'manual',
                ],
            ]
        );

        $plan_options = [
            '2' => esc_html__('2 Plans', 'king-addons'),
            '3' => esc_html__('3 Plans', 'king-addons'),
        ];

        if ($this->can_use_pro()) {
            $plan_options['4'] = esc_html__('4 Plans', 'king-addons');
            $plan_options['5'] = esc_html__('5 Plans', 'king-addons');
            $plan_options['6'] = esc_html__('6 Plans', 'king-addons');
        } else {
            $plan_options['4'] = $this->get_pro_label(esc_html__('4 Plans', 'king-addons'));
            $plan_options['5'] = $this->get_pro_label(esc_html__('5 Plans', 'king-addons'));
            $plan_options['6'] = $this->get_pro_label(esc_html__('6 Plans', 'king-addons'));
        }

        $this->add_control(
            'kng_plans_count',
            [
                'label' => esc_html__('Plans Count', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => '3',
                'options' => $plan_options,
                'description' => esc_html__('Free version supports 2-3 plans.', 'king-addons'),
            ]
        );

        $repeater = new Repeater();

        $repeater->add_control(
            'kng_plan_name',
            [
                'label' => esc_html__('Name', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Plan name', 'king-addons'),
                'label_block' => true,
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $repeater->add_control(
            'kng_plan_badge',
            [
                'label' => esc_html__('Badge', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'placeholder' => esc_html__('Most Popular', 'king-addons'),
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $repeater->add_control(
            'kng_plan_description',
            [
                'label' => esc_html__('Short Description', 'king-addons'),
                'type' => Controls_Manager::TEXTAREA,
                'rows' => 2,
                'default' => '',
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $repeater->add_control(
            'kng_plan_price',
            [
                'label' => esc_html__('Price', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => '$29',
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $repeater->add_control(
            'kng_plan_subtitle',
            [
                'label' => esc_html__('Subtitle', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('per month', 'king-addons'),
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $repeater->add_control(
            'kng_plan_cta_label',
            [
                'label' => esc_html__('CTA Label', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Get started', 'king-addons'),
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $repeater->add_control(
            'kng_plan_cta_link',
            [
                'label' => esc_html__('CTA Link', 'king-addons'),
                'type' => Controls_Manager::URL,
                'placeholder' => esc_html__('https://your-link.com', 'king-addons'),
                'default' => [
                    'url' => '',
                    'is_external' => false,
                    'nofollow' => false,
                ],
            ]
        );

        $repeater->add_control(
            'kng_plan_footer_note',
            [
                'label' => esc_html__('Footer Note', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $this->add_control(
            'kng_plans',
            [
                'label' => esc_html__('Plans', 'king-addons'),
                'type' => Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'title_field' => '{{{ kng_plan_name }}}',
                'default' => [
                    [
                        'kng_plan_name' => esc_html__('Starter', 'king-addons'),
                        'kng_plan_badge' => '',
                        'kng_plan_price' => '$19',
                        'kng_plan_subtitle' => esc_html__('per month', 'king-addons'),
                        'kng_plan_cta_label' => esc_html__('Choose Starter', 'king-addons'),
                    ],
                    [
                        'kng_plan_name' => esc_html__('Pro', 'king-addons'),
                        'kng_plan_badge' => esc_html__('Most Popular', 'king-addons'),
                        'kng_plan_price' => '$39',
                        'kng_plan_subtitle' => esc_html__('per month', 'king-addons'),
                        'kng_plan_cta_label' => esc_html__('Choose Pro', 'king-addons'),
                    ],
                    [
                        'kng_plan_name' => esc_html__('Business', 'king-addons'),
                        'kng_plan_badge' => '',
                        'kng_plan_price' => '$79',
                        'kng_plan_subtitle' => esc_html__('per month', 'king-addons'),
                        'kng_plan_cta_label' => esc_html__('Choose Business', 'king-addons'),
                    ],
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register feature controls (manual mode).
     */
    protected function register_feature_controls(): void
    {
        $this->start_controls_section(
            'kng_features_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Features', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
                'condition' => [
                    'kng_data_source' => 'manual',
                ],
            ]
        );

        $repeater = new Repeater();

        $repeater->add_control(
            'kng_feature_label',
            [
                'label' => esc_html__('Feature Label', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Feature name', 'king-addons'),
                'label_block' => true,
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $repeater->add_control(
            'kng_feature_tooltip',
            [
                'label' => esc_html__('Tooltip', 'king-addons'),
                'type' => Controls_Manager::TEXTAREA,
                'rows' => 2,
                'default' => '',
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $repeater->add_control(
            'kng_feature_group',
            [
                'label' => esc_html__('Group', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'placeholder' => esc_html__('Security', 'king-addons'),
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $plan_conditions = [
            3 => ['kng_plans_count' => ['3', '4', '5', '6']],
            4 => ['kng_plans_count' => ['4', '5', '6']],
            5 => ['kng_plans_count' => ['5', '6']],
            6 => ['kng_plans_count' => ['6']],
        ];

        $this->add_feature_value_controls($repeater, 1, esc_html__('Plan 1', 'king-addons'));
        $this->add_feature_value_controls($repeater, 2, esc_html__('Plan 2', 'king-addons'));
        $this->add_feature_value_controls($repeater, 3, esc_html__('Plan 3', 'king-addons'), $plan_conditions[3]);
        $this->add_feature_value_controls($repeater, 4, $this->get_pro_label(esc_html__('Plan 4', 'king-addons')), $plan_conditions[4], $this->get_pro_control_class());
        $this->add_feature_value_controls($repeater, 5, $this->get_pro_label(esc_html__('Plan 5', 'king-addons')), $plan_conditions[5], $this->get_pro_control_class());
        $this->add_feature_value_controls($repeater, 6, $this->get_pro_label(esc_html__('Plan 6', 'king-addons')), $plan_conditions[6], $this->get_pro_control_class());

        $this->add_control(
            'kng_features',
            [
                'label' => esc_html__('Feature Rows', 'king-addons'),
                'type' => Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'title_field' => '{{{ kng_feature_label }}}',
                'default' => [
                    [
                        'kng_feature_label' => esc_html__('Unlimited projects', 'king-addons'),
                        'kng_feature_value_1_type' => 'limited',
                        'kng_feature_value_2_type' => 'included',
                        'kng_feature_value_3_type' => 'included',
                    ],
                    [
                        'kng_feature_label' => esc_html__('Team seats', 'king-addons'),
                        'kng_feature_value_1_type' => 'text',
                        'kng_feature_value_1_text' => '5',
                        'kng_feature_value_2_type' => 'text',
                        'kng_feature_value_2_text' => '20',
                        'kng_feature_value_3_type' => 'text',
                        'kng_feature_value_3_text' => 'Unlimited',
                    ],
                    [
                        'kng_feature_label' => esc_html__('Priority support', 'king-addons'),
                        'kng_feature_value_1_type' => 'excluded',
                        'kng_feature_value_2_type' => 'included',
                        'kng_feature_value_3_type' => 'included',
                    ],
                    [
                        'kng_feature_label' => esc_html__('SLA guarantee', 'king-addons'),
                        'kng_feature_value_1_type' => 'excluded',
                        'kng_feature_value_2_type' => 'limited',
                        'kng_feature_value_3_type' => 'included',
                    ],
                ],
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
            'kng_preset',
            [
                'label' => esc_html__('Preset', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'minimal',
                'options' => [
                    'minimal' => esc_html__('Minimal', 'king-addons'),
                    'bordered' => esc_html__('Bordered', 'king-addons'),
                    'soft' => esc_html__('Soft Shadow', 'king-addons'),
                ],
            ]
        );

        $column_options = [
            '2' => esc_html__('2 Columns', 'king-addons'),
            '3' => esc_html__('3 Columns', 'king-addons'),
        ];
        if ($this->can_use_pro()) {
            $column_options['4'] = esc_html__('4 Columns', 'king-addons');
            $column_options['5'] = esc_html__('5 Columns', 'king-addons');
            $column_options['6'] = esc_html__('6 Columns', 'king-addons');
        } else {
            $column_options['4'] = $this->get_pro_label(esc_html__('4 Columns', 'king-addons'));
            $column_options['5'] = $this->get_pro_label(esc_html__('5 Columns', 'king-addons'));
            $column_options['6'] = $this->get_pro_label(esc_html__('6 Columns', 'king-addons'));
        }

        $this->add_control(
            'kng_columns',
            [
                'label' => esc_html__('Columns', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => '3',
                'options' => $column_options,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-comparison-matrix-cards' => '--kng-cmc-columns: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_grid_gap',
            [
                'label' => esc_html__('Gap', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 80,
                    ],
                    'em' => [
                        'min' => 0,
                        'max' => 6,
                    ],
                ],
                'default' => [
                    'size' => 24,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-comparison-matrix-cards' => '--kng-cmc-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_equal_height',
            [
                'label' => esc_html__('Equal Height Cards', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_mobile_layout',
            [
                'label' => esc_html__('Mobile Layout', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'stack',
                'options' => [
                    'stack' => esc_html__('Stack', 'king-addons'),
                    'scroll' => esc_html__('Horizontal Scroll', 'king-addons'),
                ],
            ]
        );

        $this->add_control(
            'kng_hide_scrollbar',
            [
                'label' => esc_html__('Hide Mobile Scrollbar', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
                'condition' => [
                    'kng_mobile_layout' => 'scroll',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_mobile_card_min_width',
            [
                'label' => esc_html__('Card Min Width (Mobile)', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 200,
                        'max' => 420,
                    ],
                ],
                'default' => [
                    'size' => 260,
                    'unit' => 'px',
                ],
                'condition' => [
                    'kng_mobile_layout' => 'scroll',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-comparison-matrix-cards' => '--kng-cmc-card-min-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register highlight differences controls (Pro).
     */
    protected function register_highlight_controls(): void
    {
        $this->start_controls_section(
            'kng_highlight_section',
            [
                'label' => $this->get_pro_label(esc_html__('Highlight Differences', 'king-addons')),
                'tab' => Controls_Manager::TAB_CONTENT,
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->add_control(
            'kng_highlight_enable',
            [
                'label' => $this->get_pro_label(esc_html__('Enable Highlight', 'king-addons')),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->add_control(
            'kng_highlight_mode',
            [
                'label' => $this->get_pro_label(esc_html__('Mode', 'king-addons')),
                'type' => Controls_Manager::SELECT,
                'default' => 'dim',
                'options' => [
                    'dim' => esc_html__('Dim Identical', 'king-addons'),
                    'hide' => esc_html__('Hide Identical', 'king-addons'),
                    'unique' => esc_html__('Highlight Unique', 'king-addons'),
                ],
                'classes' => $this->get_pro_control_class(),
                'condition' => [
                    'kng_highlight_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_highlight_style',
            [
                'label' => $this->get_pro_label(esc_html__('Visual Style', 'king-addons')),
                'type' => Controls_Manager::SELECT,
                'default' => 'background',
                'options' => [
                    'background' => esc_html__('Background', 'king-addons'),
                    'border' => esc_html__('Border', 'king-addons'),
                    'icon' => esc_html__('Icon Emphasis', 'king-addons'),
                ],
                'classes' => $this->get_pro_control_class(),
                'condition' => [
                    'kng_highlight_enable' => 'yes',
                    'kng_highlight_mode' => 'unique',
                ],
            ]
        );

        $this->add_control(
            'kng_highlight_compare',
            [
                'label' => $this->get_pro_label(esc_html__('Compare Mode', 'king-addons')),
                'type' => Controls_Manager::SELECT,
                'default' => 'normalized',
                'options' => [
                    'strict' => esc_html__('Strict Match', 'king-addons'),
                    'normalized' => esc_html__('Normalized', 'king-addons'),
                ],
                'classes' => $this->get_pro_control_class(),
                'condition' => [
                    'kng_highlight_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_highlight_toggle',
            [
                'label' => $this->get_pro_label(esc_html__('Show Toggle', 'king-addons')),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
                'classes' => $this->get_pro_control_class(),
                'condition' => [
                    'kng_highlight_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_highlight_toggle_label',
            [
                'label' => $this->get_pro_label(esc_html__('Toggle Label', 'king-addons')),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Highlight differences', 'king-addons'),
                'classes' => $this->get_pro_control_class(),
                'condition' => [
                    'kng_highlight_enable' => 'yes',
                    'kng_highlight_toggle' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_highlight_default',
            [
                'label' => $this->get_pro_label(esc_html__('Default State', 'king-addons')),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
                'classes' => $this->get_pro_control_class(),
                'condition' => [
                    'kng_highlight_enable' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register sticky header controls (Pro).
     */
    protected function register_sticky_controls(): void
    {
        $this->start_controls_section(
            'kng_sticky_section',
            [
                'label' => $this->get_pro_label(esc_html__('Sticky Header', 'king-addons')),
                'tab' => Controls_Manager::TAB_CONTENT,
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->add_control(
            'kng_sticky_enable',
            [
                'label' => $this->get_pro_label(esc_html__('Enable Sticky Header', 'king-addons')),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->add_control(
            'kng_sticky_offset',
            [
                'label' => $this->get_pro_label(esc_html__('Top Offset', 'king-addons')),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 120,
                    ],
                ],
                'default' => [
                    'size' => 0,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-comparison-matrix-cards' => '--kng-cmc-sticky-offset: {{SIZE}}{{UNIT}};',
                ],
                'classes' => $this->get_pro_control_class(),
                'condition' => [
                    'kng_sticky_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_sticky_shadow',
            [
                'label' => $this->get_pro_label(esc_html__('Shadow on Sticky', 'king-addons')),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
                'classes' => $this->get_pro_control_class(),
                'condition' => [
                    'kng_sticky_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_sticky_mobile_mode',
            [
                'label' => $this->get_pro_label(esc_html__('Mobile Mode', 'king-addons')),
                'type' => Controls_Manager::SELECT,
                'default' => 'compact',
                'options' => [
                    'off' => esc_html__('Off', 'king-addons'),
                    'compact' => esc_html__('Compact', 'king-addons'),
                    'full' => esc_html__('Full', 'king-addons'),
                ],
                'classes' => $this->get_pro_control_class(),
                'condition' => [
                    'kng_sticky_enable' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register search/filter controls (Pro).
     */
    protected function register_search_controls(): void
    {
        $this->start_controls_section(
            'kng_search_section',
            [
                'label' => $this->get_pro_label(esc_html__('Feature Search', 'king-addons')),
                'tab' => Controls_Manager::TAB_CONTENT,
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->add_control(
            'kng_search_enable',
            [
                'label' => $this->get_pro_label(esc_html__('Enable Search', 'king-addons')),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->add_control(
            'kng_search_placeholder',
            [
                'label' => $this->get_pro_label(esc_html__('Placeholder', 'king-addons')),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Search features', 'king-addons'),
                'classes' => $this->get_pro_control_class(),
                'condition' => [
                    'kng_search_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_search_includes_tooltip',
            [
                'label' => $this->get_pro_label(esc_html__('Search Tooltips', 'king-addons')),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
                'classes' => $this->get_pro_control_class(),
                'condition' => [
                    'kng_search_enable' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register card style controls.
     */
    protected function register_style_card_controls(): void
    {
        $this->start_controls_section(
            'kng_style_card_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Card', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name' => 'kng_card_background',
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .king-addons-comparison-matrix-cards__card',
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_card_border',
                'selector' => '{{WRAPPER}} .king-addons-comparison-matrix-cards__card',
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
                    '{{WRAPPER}} .king-addons-comparison-matrix-cards__card' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_card_shadow',
                'selector' => '{{WRAPPER}} .king-addons-comparison-matrix-cards__card',
            ]
        );

        $this->add_responsive_control(
            'kng_card_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-comparison-matrix-cards__card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_card_hover_heading',
            [
                'label' => esc_html__('Hover', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'kng_card_hover_background',
            [
                'label' => esc_html__('Hover Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-comparison-matrix-cards__card:hover' => 'background: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_card_hover_border',
            [
                'label' => esc_html__('Hover Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-comparison-matrix-cards__card:hover' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_card_hover_shadow',
                'selector' => '{{WRAPPER}} .king-addons-comparison-matrix-cards__card:hover',
            ]
        );

        $this->add_control(
            'kng_card_hover_translate',
            [
                'label' => esc_html__('Hover Lift', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => -30,
                        'max' => 30,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-comparison-matrix-cards' => '--kng-cmc-card-hover-translate: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register header style controls.
     */
    protected function register_style_header_controls(): void
    {
        $this->start_controls_section(
            'kng_style_header_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Header', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'kng_title_color',
            [
                'label' => esc_html__('Title Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-comparison-matrix-cards__title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_title_typography',
                'selector' => '{{WRAPPER}} .king-addons-comparison-matrix-cards__title',
            ]
        );

        $this->add_control(
            'kng_price_color',
            [
                'label' => esc_html__('Price Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-comparison-matrix-cards__price' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_price_typography',
                'selector' => '{{WRAPPER}} .king-addons-comparison-matrix-cards__price',
            ]
        );

        $this->add_control(
            'kng_subtitle_color',
            [
                'label' => esc_html__('Subtitle Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-comparison-matrix-cards__subtitle' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-comparison-matrix-cards__description' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_subtitle_typography',
                'selector' => '{{WRAPPER}} .king-addons-comparison-matrix-cards__subtitle, {{WRAPPER}} .king-addons-comparison-matrix-cards__description',
            ]
        );

        $this->add_control(
            'kng_header_gap',
            [
                'label' => esc_html__('Header Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 40,
                    ],
                    'em' => [
                        'min' => 0,
                        'max' => 3,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-comparison-matrix-cards' => '--kng-cmc-header-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_badge_color',
            [
                'label' => esc_html__('Badge Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-comparison-matrix-cards__badge' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_badge_background',
            [
                'label' => esc_html__('Badge Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-comparison-matrix-cards__badge' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register feature style controls.
     */
    protected function register_style_feature_controls(): void
    {
        $this->start_controls_section(
            'kng_style_features_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Features', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'kng_feature_label_color',
            [
                'label' => esc_html__('Label Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-comparison-matrix-cards__feature-label' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_feature_label_typography',
                'selector' => '{{WRAPPER}} .king-addons-comparison-matrix-cards__feature-label',
            ]
        );

        $this->add_control(
            'kng_feature_value_color',
            [
                'label' => esc_html__('Value Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-comparison-matrix-cards__feature-value' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_feature_value_typography',
                'selector' => '{{WRAPPER}} .king-addons-comparison-matrix-cards__feature-value',
            ]
        );

        $this->add_control(
            'kng_feature_icon_size',
            [
                'label' => esc_html__('Icon Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 12,
                        'max' => 32,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-comparison-matrix-cards__icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_feature_icon_included',
            [
                'label' => esc_html__('Included Icon Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-comparison-matrix-cards__icon.is-included' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_feature_icon_excluded',
            [
                'label' => esc_html__('Excluded Icon Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-comparison-matrix-cards__icon.is-excluded' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_feature_icon_limited',
            [
                'label' => esc_html__('Limited Icon Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-comparison-matrix-cards__icon.is-limited' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_feature_spacing',
            [
                'label' => esc_html__('Item Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 4,
                        'max' => 32,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-comparison-matrix-cards__feature' => 'gap: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .king-addons-comparison-matrix-cards__feature:not(:last-child)' => 'padding-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_feature_divider_color',
            [
                'label' => esc_html__('Divider Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-comparison-matrix-cards' => '--kng-cmc-divider-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_feature_divider_style',
            [
                'label' => esc_html__('Divider Style', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'dashed',
                'options' => [
                    'solid' => esc_html__('Solid', 'king-addons'),
                    'dashed' => esc_html__('Dashed', 'king-addons'),
                    'dotted' => esc_html__('Dotted', 'king-addons'),
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-comparison-matrix-cards' => '--kng-cmc-divider-style: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_feature_divider_width',
            [
                'label' => esc_html__('Divider Width', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 4,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-comparison-matrix-cards' => '--kng-cmc-divider-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_feature_group_color',
            [
                'label' => esc_html__('Group Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-comparison-matrix-cards__group' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_feature_group_typography',
                'selector' => '{{WRAPPER}} .king-addons-comparison-matrix-cards__group',
            ]
        );

        $this->add_control(
            'kng_feature_group_background',
            [
                'label' => esc_html__('Group Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-comparison-matrix-cards' => '--kng-cmc-group-bg: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_feature_group_padding',
            [
                'label' => esc_html__('Group Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-comparison-matrix-cards' => '--kng-cmc-group-padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_feature_group_radius',
            [
                'label' => esc_html__('Group Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 40],
                    '%' => ['min' => 0, 'max' => 100],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-comparison-matrix-cards' => '--kng-cmc-group-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_tooltip_background',
            [
                'label' => esc_html__('Tooltip Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-comparison-matrix-cards' => '--kng-cmc-tooltip-bg: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_tooltip_text',
            [
                'label' => esc_html__('Tooltip Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-comparison-matrix-cards' => '--kng-cmc-tooltip-text: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_tooltip_typography',
                'selector' => '{{WRAPPER}} .king-addons-comparison-matrix-cards__tooltip-text',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register button style controls.
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
                'type' => Controls_Manager::SELECT,
                'default' => 'flex-start',
                'options' => [
                    'flex-start' => esc_html__('Left', 'king-addons'),
                    'center' => esc_html__('Center', 'king-addons'),
                    'flex-end' => esc_html__('Right', 'king-addons'),
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-comparison-matrix-cards__button' => 'align-self: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_button_typography',
                'selector' => '{{WRAPPER}} .king-addons-comparison-matrix-cards__button',
            ]
        );

        $this->add_control(
            'kng_button_text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-comparison-matrix-cards__button' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_background',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-comparison-matrix-cards__button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_hover_text_color',
            [
                'label' => esc_html__('Hover Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-comparison-matrix-cards__button:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_hover_background',
            [
                'label' => esc_html__('Hover Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-comparison-matrix-cards__button:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_button_border',
                'selector' => '{{WRAPPER}} .king-addons-comparison-matrix-cards__button',
            ]
        );

        $this->add_control(
            'kng_button_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 60],
                    '%' => ['min' => 0, 'max' => 100],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-comparison-matrix-cards__button' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_button_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-comparison-matrix-cards__button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register highlight style controls (Pro).
     */
    protected function register_style_highlight_controls(): void
    {
        $this->start_controls_section(
            'kng_style_highlight_section',
            [
                'label' => $this->get_pro_label(esc_html__('Highlight Styles', 'king-addons')),
                'tab' => Controls_Manager::TAB_STYLE,
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->add_control(
            'kng_highlight_dim_opacity',
            [
                'label' => $this->get_pro_label(esc_html__('Dim Opacity', 'king-addons')),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['%'],
                'range' => [
                    '%' => [
                        'min' => 10,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'size' => 45,
                    'unit' => '%',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-comparison-matrix-cards' => '--kng-cmc-dim-opacity: calc({{SIZE}} / 100);',
                ],
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->add_control(
            'kng_highlight_unique_background',
            [
                'label' => $this->get_pro_label(esc_html__('Unique Background', 'king-addons')),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-comparison-matrix-cards' => '--kng-cmc-unique-bg: {{VALUE}};',
                ],
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->add_control(
            'kng_highlight_unique_border',
            [
                'label' => $this->get_pro_label(esc_html__('Unique Border', 'king-addons')),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-comparison-matrix-cards' => '--kng-cmc-unique-border: {{VALUE}};',
                ],
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->add_control(
            'kng_highlight_unique_text',
            [
                'label' => $this->get_pro_label(esc_html__('Unique Text', 'king-addons')),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-comparison-matrix-cards' => '--kng-cmc-unique-text: {{VALUE}};',
                ],
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register sticky style controls (Pro).
     */
    protected function register_style_sticky_controls(): void
    {
        $this->start_controls_section(
            'kng_style_sticky_section',
            [
                'label' => $this->get_pro_label(esc_html__('Sticky Styles', 'king-addons')),
                'tab' => Controls_Manager::TAB_STYLE,
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->add_control(
            'kng_sticky_background',
            [
                'label' => $this->get_pro_label(esc_html__('Sticky Background', 'king-addons')),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-comparison-matrix-cards' => '--kng-cmc-sticky-bg: {{VALUE}};',
                ],
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->add_control(
            'kng_sticky_divider',
            [
                'label' => $this->get_pro_label(esc_html__('Sticky Divider', 'king-addons')),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-comparison-matrix-cards' => '--kng-cmc-sticky-divider: {{VALUE}};',
                ],
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->add_control(
            'kng_sticky_shadow_color',
            [
                'label' => $this->get_pro_label(esc_html__('Sticky Shadow', 'king-addons')),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-comparison-matrix-cards' => '--kng-cmc-sticky-shadow: {{VALUE}};',
                ],
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register search style controls (Pro).
     */
    protected function register_style_search_controls(): void
    {
        $this->start_controls_section(
            'kng_style_search_section',
            [
                'label' => $this->get_pro_label(esc_html__('Search Styles', 'king-addons')),
                'tab' => Controls_Manager::TAB_STYLE,
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->add_control(
            'kng_search_background',
            [
                'label' => $this->get_pro_label(esc_html__('Background', 'king-addons')),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-comparison-matrix-cards' => '--kng-cmc-search-bg: {{VALUE}};',
                ],
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->add_control(
            'kng_search_border',
            [
                'label' => $this->get_pro_label(esc_html__('Border Color', 'king-addons')),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-comparison-matrix-cards' => '--kng-cmc-search-border: {{VALUE}};',
                ],
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->add_control(
            'kng_search_text_color',
            [
                'label' => $this->get_pro_label(esc_html__('Text Color', 'king-addons')),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-comparison-matrix-cards' => '--kng-cmc-search-text: {{VALUE}};',
                ],
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_search_typography',
                'selector' => '{{WRAPPER}} .king-addons-comparison-matrix-cards__search-input',
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->add_control(
            'kng_search_radius',
            [
                'label' => $this->get_pro_label(esc_html__('Border Radius', 'king-addons')),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 40],
                    '%' => ['min' => 0, 'max' => 100],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-comparison-matrix-cards__search-input' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->add_responsive_control(
            'kng_search_padding',
            [
                'label' => $this->get_pro_label(esc_html__('Padding', 'king-addons')),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-comparison-matrix-cards__search-input' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render premium upgrade notice.
     */
    public function register_pro_notice_controls(): void
    {
        if (!$this->can_use_pro()) {
            Core::renderProFeaturesSection($this, '', Controls_Manager::RAW_HTML, 'comparison-matrix-cards', [
                'Highlight differences modes and visitor toggle',
                'Sticky header inside widget',
                'Dynamic data sources (ACF, WooCommerce)',
                'Feature search and filtering',
            ]);
        }
    }

    /**
     * Add per-plan feature value controls.
     *
     * @param Repeater $repeater  Repeater instance.
     * @param int      $index     Plan index.
     * @param string   $label     Label.
     * @param array    $condition Optional condition.
     * @param string   $classes   Optional classes.
     */
    protected function add_feature_value_controls(Repeater $repeater, int $index, string $label, array $condition = [], string $classes = ''): void
    {
        $type_key = 'kng_feature_value_' . $index . '_type';
        $text_key = 'kng_feature_value_' . $index . '_text';

        $type_control = [
            'label' => $label . ' ' . esc_html__('Value Type', 'king-addons'),
            'type' => Controls_Manager::SELECT,
            'default' => 'included',
            'options' => [
                'included' => esc_html__('Included', 'king-addons'),
                'excluded' => esc_html__('Excluded', 'king-addons'),
                'limited' => esc_html__('Limited', 'king-addons'),
                'text' => esc_html__('Text', 'king-addons'),
            ],
            'separator' => 'before',
        ];

        if (!empty($condition)) {
            $type_control['condition'] = $condition;
        }

        if ($classes !== '') {
            $type_control['classes'] = $classes;
        }

        $repeater->add_control($type_key, $type_control);

        $text_condition = [
            $type_key => 'text',
        ];

        if (!empty($condition)) {
            $text_condition = array_merge($text_condition, $condition);
        }

        $text_control = [
            'label' => $label . ' ' . esc_html__('Text', 'king-addons'),
            'type' => Controls_Manager::TEXT,
            'default' => '',
            'condition' => $text_condition,
        ];

        if ($classes !== '') {
            $text_control['classes'] = $classes;
        }

        $repeater->add_control($text_key, $text_control);
    }

    /**
     * Build render context.
     *
     * @param array<string, mixed> $settings Settings.
     * @param int                  $plan_count Plan count.
     *
     * @return array<string, mixed>
     */
    protected function get_render_context(array $settings, int $plan_count): array
    {
        $can_pro = $this->can_use_pro();

        $highlight_enabled = $can_pro && (($settings['kng_highlight_enable'] ?? '') === 'yes');
        $search_enabled = $can_pro && (($settings['kng_search_enable'] ?? '') === 'yes');
        $sticky_enabled = $can_pro && (($settings['kng_sticky_enable'] ?? '') === 'yes');

        return [
            'plan_count' => $plan_count,
            'preset' => $settings['kng_preset'] ?? 'minimal',
            'mobile_layout' => $settings['kng_mobile_layout'] ?? 'stack',
            'equal_height' => ($settings['kng_equal_height'] ?? '') === 'yes',
            'hide_scrollbar' => ($settings['kng_hide_scrollbar'] ?? '') === 'yes',
            'highlight_enabled' => $highlight_enabled,
            'highlight_mode' => $settings['kng_highlight_mode'] ?? 'dim',
            'highlight_compare' => $settings['kng_highlight_compare'] ?? 'normalized',
            'highlight_style' => $settings['kng_highlight_style'] ?? 'background',
            'highlight_default' => ($settings['kng_highlight_default'] ?? '') === 'yes',
            'highlight_toggle' => $highlight_enabled && (($settings['kng_highlight_toggle'] ?? '') === 'yes'),
            'highlight_label' => $settings['kng_highlight_toggle_label'] ?? esc_html__('Highlight differences', 'king-addons'),
            'search_enabled' => $search_enabled,
            'search_placeholder' => $settings['kng_search_placeholder'] ?? esc_html__('Search features', 'king-addons'),
            'search_tooltip' => ($settings['kng_search_includes_tooltip'] ?? '') === 'yes',
            'sticky_enabled' => $sticky_enabled,
            'sticky_shadow' => ($settings['kng_sticky_shadow'] ?? '') === 'yes',
            'sticky_mobile_mode' => $settings['kng_sticky_mobile_mode'] ?? 'compact',
        ];
    }

    /**
     * Build wrapper classes.
     *
     * @param array<string, mixed> $settings Settings.
     * @param array<string, mixed> $context  Context.
     *
     * @return array<int, string>
     */
    protected function get_wrapper_classes(array $settings, array $context): array
    {
        $classes = [
            'king-addons-comparison-matrix-cards',
            'king-addons-comparison-matrix-cards--preset-' . ($context['preset'] ?? 'minimal'),
            'king-addons-comparison-matrix-cards--mobile-' . ($context['mobile_layout'] ?? 'stack'),
        ];

        if (!empty($context['equal_height'])) {
            $classes[] = 'king-addons-comparison-matrix-cards--equal-height';
        }

        if (!empty($context['hide_scrollbar'])) {
            $classes[] = 'king-addons-comparison-matrix-cards--hide-scrollbar';
        }

        if (!empty($context['highlight_enabled'])) {
            $classes[] = 'is-highlight-enabled';
            $classes[] = 'is-highlight-mode-' . ($context['highlight_mode'] ?? 'dim');
            $classes[] = 'is-highlight-style-' . ($context['highlight_style'] ?? 'background');
        }

        if (!empty($context['search_enabled'])) {
            $classes[] = 'has-search';
        }

        if (!empty($context['sticky_enabled'])) {
            $classes[] = 'is-sticky-enabled';
            if (!empty($context['sticky_shadow'])) {
                $classes[] = 'has-sticky-shadow';
            }
            $mobile_mode = $context['sticky_mobile_mode'] ?? 'compact';
            if ($mobile_mode === 'off') {
                $classes[] = 'is-sticky-mobile-off';
            } elseif ($mobile_mode === 'compact') {
                $classes[] = 'is-sticky-compact';
            }
        }

        unset($settings);
        return $classes;
    }

    /**
     * Build wrapper attributes.
     *
     * @param array<string, mixed> $settings Settings.
     * @param array<string, mixed> $context  Context.
     *
     * @return string
     */
    protected function get_wrapper_attributes(array $settings, array $context): string
    {
        $columns_setting = isset($settings['kng_columns']) ? (int) $settings['kng_columns'] : 3;
        $columns_setting = max(1, min($columns_setting, $this->can_use_pro() ? 6 : 3));
        $plan_count = (int) ($context['plan_count'] ?? 0);
        $columns = $plan_count > 0 ? min($columns_setting, $plan_count) : $columns_setting;

        $attributes = [
            'data-widget-id' => $this->get_id(),
            'data-plan-count' => (string) ($context['plan_count'] ?? 0),
            'data-mobile-layout' => (string) ($context['mobile_layout'] ?? 'stack'),
            'data-highlight-enable' => !empty($context['highlight_enabled']) ? 'yes' : 'no',
            'data-highlight-mode' => (string) ($context['highlight_mode'] ?? 'dim'),
            'data-highlight-compare' => (string) ($context['highlight_compare'] ?? 'normalized'),
            'data-highlight-style' => (string) ($context['highlight_style'] ?? 'background'),
            'data-highlight-default' => !empty($context['highlight_default']) ? 'yes' : 'no',
            'data-highlight-toggle' => !empty($context['highlight_toggle']) ? 'yes' : 'no',
            'data-search-enable' => !empty($context['search_enabled']) ? 'yes' : 'no',
            'data-search-tooltip' => !empty($context['search_tooltip']) ? 'yes' : 'no',
            'data-sticky-enable' => !empty($context['sticky_enabled']) ? 'yes' : 'no',
            'data-sticky-shadow' => !empty($context['sticky_shadow']) ? 'yes' : 'no',
            'style' => '--kng-cmc-columns: ' . $columns . ';',
        ];

        $output = [];
        foreach ($attributes as $key => $value) {
            $output[] = esc_attr($key) . '="' . esc_attr($value) . '"';
        }

        if (!empty($settings['kng_sticky_offset']['size'])) {
            $output[] = 'data-sticky-offset="' . esc_attr((string) $settings['kng_sticky_offset']['size']) . '"';
        }

        return $output ? ' ' . implode(' ', $output) : '';
    }

    /**
     * Render controls bar (search + toggle).
     *
     * @param array<string, mixed> $context Context.
     */
    protected function render_controls_bar(array $context): void
    {
        $has_search = !empty($context['search_enabled']);
        $has_toggle = !empty($context['highlight_toggle']);

        if (!$has_search && !$has_toggle) {
            return;
        }

        ?>
        <div class="king-addons-comparison-matrix-cards__controls">
            <?php if ($has_search) : ?>
                <label class="king-addons-comparison-matrix-cards__search">
                    <span class="screen-reader-text"><?php echo esc_html__('Search features', 'king-addons'); ?></span>
                    <input
                        class="king-addons-comparison-matrix-cards__search-input"
                        type="search"
                        placeholder="<?php echo esc_attr($context['search_placeholder'] ?? esc_html__('Search features', 'king-addons')); ?>"
                    />
                </label>
            <?php endif; ?>
            <?php if ($has_toggle) : ?>
                <label class="king-addons-comparison-matrix-cards__highlight-toggle">
                    <input class="king-addons-comparison-matrix-cards__highlight-input" type="checkbox" />
                    <span class="king-addons-comparison-matrix-cards__toggle-indicator" aria-hidden="true"></span>
                    <span class="king-addons-comparison-matrix-cards__toggle-label">
                        <?php echo esc_html($context['highlight_label'] ?? esc_html__('Highlight differences', 'king-addons')); ?>
                    </span>
                </label>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render a single plan card.
     *
     * @param array<string, mixed>          $plan     Plan data.
     * @param array<int, array<string, mixed>> $features Feature list.
     * @param int                           $index    Plan index starting at 0.
     */
    protected function render_card(array $plan, array $features, int $index): void
    {
        $badge = trim((string) ($plan['badge'] ?? ''));
        $title = trim((string) ($plan['name'] ?? ''));
        $description = trim((string) ($plan['description'] ?? ''));
        $price = $plan['price'] ?? '';
        $subtitle = trim((string) ($plan['subtitle'] ?? ''));
        $footer_note = trim((string) ($plan['footer_note'] ?? ''));

        $button_label = trim((string) ($plan['cta_label'] ?? ''));
        $button_link = $plan['cta_link'] ?? [];
        $button_attributes = $this->get_link_attributes($button_link);

        ?>
        <div class="king-addons-comparison-matrix-cards__card">
            <div class="king-addons-comparison-matrix-cards__header">
                <?php if ($badge !== '') : ?>
                    <span class="king-addons-comparison-matrix-cards__badge">
                        <?php echo esc_html($badge); ?>
                    </span>
                <?php endif; ?>
                <?php if ($title !== '') : ?>
                    <h3 class="king-addons-comparison-matrix-cards__title">
                        <?php echo esc_html($title); ?>
                    </h3>
                <?php endif; ?>
                <?php if ($description !== '') : ?>
                    <p class="king-addons-comparison-matrix-cards__description">
                        <?php echo esc_html($description); ?>
                    </p>
                <?php endif; ?>
                <?php if (!empty($price)) : ?>
                    <div class="king-addons-comparison-matrix-cards__price">
                        <?php echo wp_kses_post($price); ?>
                    </div>
                <?php endif; ?>
                <?php if ($subtitle !== '') : ?>
                    <div class="king-addons-comparison-matrix-cards__subtitle">
                        <?php echo esc_html($subtitle); ?>
                    </div>
                <?php endif; ?>
                <?php if ($button_label !== '' && !empty($button_link['url'])) : ?>
                    <a class="king-addons-comparison-matrix-cards__button" <?php echo $button_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
                        <?php echo esc_html($button_label); ?>
                    </a>
                <?php endif; ?>
            </div>
            <?php if (!empty($features)) : ?>
                <div class="king-addons-comparison-matrix-cards__features">
                    <ul class="king-addons-comparison-matrix-cards__feature-list">
                        <?php $this->render_feature_rows($features, $index); ?>
                    </ul>
                </div>
            <?php endif; ?>
            <?php if ($footer_note !== '') : ?>
                <div class="king-addons-comparison-matrix-cards__footer">
                    <?php echo esc_html($footer_note); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render all feature rows for a card.
     *
     * @param array<int, array<string, mixed>> $features Feature list.
     * @param int                              $index    Plan index.
     */
    protected function render_feature_rows(array $features, int $index): void
    {
        $previous_group = '';

        foreach ($features as $feature) {
            $label = trim((string) ($feature['label'] ?? ''));
            $tooltip = trim((string) ($feature['tooltip'] ?? ''));
            $group = trim((string) ($feature['group'] ?? ''));
            $feature_id = (string) ($feature['id'] ?? $label);

            if ($group !== '' && $group !== $previous_group) {
                $previous_group = $group;
                ?>
                <li class="king-addons-comparison-matrix-cards__feature-group" data-feature-group="<?php echo esc_attr($group); ?>" role="presentation">
                    <span class="king-addons-comparison-matrix-cards__group">
                        <?php echo esc_html($group); ?>
                    </span>
                </li>
                <?php
            }

            if ($label === '') {
                continue;
            }

            $values = $feature['values'] ?? [];
            $value = $values[$index] ?? [
                'type' => 'excluded',
                'text' => '',
            ];

            $value_type = $value['type'] ?? 'excluded';
            $value_text = $value['text'] ?? '';

            $item_attributes = $this->render_attributes([
                'data-feature-id' => $feature_id,
                'data-feature-label' => $label,
                'data-feature-tooltip' => $tooltip,
                'data-feature-group' => $group,
                'data-value-type' => $value_type,
                'data-value-text' => $value_text,
            ]);
            ?>
            <li class="king-addons-comparison-matrix-cards__feature" <?php echo $item_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
                <div class="king-addons-comparison-matrix-cards__feature-label">
                    <span><?php echo esc_html($label); ?></span>
                    <?php if ($tooltip !== '') : ?>
                        <?php $tooltip_id = $this->get_tooltip_id($feature_id); ?>
                        <span class="king-addons-comparison-matrix-cards__tooltip" tabindex="0" aria-describedby="<?php echo esc_attr($tooltip_id); ?>">
                            <?php $this->render_tooltip_icon(); ?>
                            <span class="king-addons-comparison-matrix-cards__tooltip-text" role="tooltip" id="<?php echo esc_attr($tooltip_id); ?>">
                                <?php echo esc_html($tooltip); ?>
                            </span>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="king-addons-comparison-matrix-cards__feature-value">
                    <?php if ($value_type === 'text') : ?>
                        <?php echo esc_html($value_text !== '' ? $value_text : '-'); ?>
                    <?php else : ?>
                        <?php $this->render_feature_icon($value_type); ?>
                    <?php endif; ?>
                </div>
            </li>
            <?php
        }
    }

    /**
     * Render feature icon by type.
     *
     * @param string $type Feature type.
     */
    protected function render_feature_icon(string $type): void
    {
        $label = 'Included';
        $class = 'is-included';
        $path = 'M4 10l4 4 8-8';

        if ($type === 'excluded') {
            $label = 'Excluded';
            $class = 'is-excluded';
            $path = 'M4 4l8 8M12 4l-8 8';
        } elseif ($type === 'limited') {
            $label = 'Limited';
            $class = 'is-limited';
            $path = 'M4 8h8';
        }

        ?>
        <span class="king-addons-comparison-matrix-cards__icon <?php echo esc_attr($class); ?>" role="img" aria-label="<?php echo esc_attr($label); ?>">
            <svg viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="<?php echo esc_attr($path); ?>" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </span>
        <?php
    }

    /**
     * Render tooltip icon.
     */
    protected function render_tooltip_icon(): void
    {
        ?>
        <span class="king-addons-comparison-matrix-cards__icon king-addons-comparison-matrix-cards__icon--tooltip" aria-hidden="true">
            <svg viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="8" cy="8" r="7" stroke="currentColor" stroke-width="1.5" />
                <path d="M8 7.2v3.2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                <circle cx="8" cy="4.6" r="0.8" fill="currentColor" />
            </svg>
        </span>
        <?php
    }

    /**
     * Generate tooltip id.
     *
     * @param string $feature_id Feature id.
     *
     * @return string
     */
    protected function get_tooltip_id(string $feature_id): string
    {
        return $this->get_id() . '-tooltip-' . $feature_id;
    }

    /**
     * Build link attributes string.
     *
     * @param array<string, mixed> $link Link data.
     *
     * @return string
     */
    protected function get_link_attributes(array $link): string
    {
        if (empty($link['url'])) {
            return '';
        }

        $attributes = [
            'href' => esc_url($link['url']),
            'target' => !empty($link['is_external']) ? '_blank' : '_self',
        ];

        $rel = [];
        if (!empty($link['nofollow'])) {
            $rel[] = 'nofollow';
        }
        if (!empty($link['is_external'])) {
            $rel[] = 'noopener';
        }
        if ($rel) {
            $attributes['rel'] = implode(' ', array_unique($rel));
        }

        return $this->render_attributes($attributes);
    }

    /**
     * Render attributes array to HTML string.
     *
     * @param array<string, string> $attributes Attributes.
     *
     * @return string
     */
    protected function render_attributes(array $attributes): string
    {
        $output = [];

        foreach ($attributes as $key => $value) {
            if ($value === '') {
                continue;
            }
            $output[] = esc_attr($key) . '="' . esc_attr($value) . '"';
        }

        return implode(' ', $output);
    }

    /**
     * Build render data based on source.
     *
     * @param array<string, mixed> $settings Settings.
     *
     * @return array<string, mixed>
     */
    protected function get_render_data(array $settings): array
    {
        $source = $settings['kng_data_source'] ?? 'manual';
        $can_pro = $this->can_use_pro();

        if ($source !== 'manual' && !$can_pro) {
            return [
                'plans' => [],
                'features' => [],
                'pro_locked' => true,
            ];
        }

        if ($source === 'acf') {
            return $this->get_acf_data($settings);
        }

        if ($source === 'woo') {
            return $this->get_woo_data($settings);
        }

        return $this->get_manual_data($settings);
    }

    /**
     * Get manual data.
     *
     * @param array<string, mixed> $settings Settings.
     *
     * @return array<string, mixed>
     */
    protected function get_manual_data(array $settings): array
    {
        $plans = $this->get_manual_plans($settings);
        $features = [];

        $feature_rows = $settings['kng_features'] ?? [];
        if (!is_array($feature_rows)) {
            $feature_rows = [];
        }

        foreach ($feature_rows as $feature_row) {
            $label = trim((string) ($feature_row['kng_feature_label'] ?? ''));
            if ($label === '') {
                continue;
            }

            $values = [];
            for ($i = 1; $i <= count($plans); $i++) {
                $values[] = $this->get_feature_value($feature_row, $i);
            }

            $features[] = [
                'id' => (string) ($feature_row['_id'] ?? sanitize_title($label)),
                'label' => $label,
                'tooltip' => trim((string) ($feature_row['kng_feature_tooltip'] ?? '')),
                'group' => trim((string) ($feature_row['kng_feature_group'] ?? '')),
                'values' => $values,
            ];
        }

        return [
            'plans' => $plans,
            'features' => $features,
        ];
    }

    /**
     * Get manual plans list.
     *
     * @param array<string, mixed> $settings Settings.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function get_manual_plans(array $settings): array
    {
        $plans = $settings['kng_plans'] ?? [];
        if (!is_array($plans)) {
            return [];
        }

        $limit = isset($settings['kng_plans_count']) ? (int) $settings['kng_plans_count'] : 3;
        $limit = max(2, min(6, $limit));
        if (!$this->can_use_pro()) {
            $limit = min(3, $limit);
        }

        $plans = array_slice($plans, 0, $limit);
        $normalized = [];

        foreach ($plans as $plan) {
            $link = $plan['kng_plan_cta_link'] ?? [];
            $normalized[] = [
                'name' => $plan['kng_plan_name'] ?? '',
                'badge' => $plan['kng_plan_badge'] ?? '',
                'description' => $plan['kng_plan_description'] ?? '',
                'price' => $plan['kng_plan_price'] ?? '',
                'subtitle' => $plan['kng_plan_subtitle'] ?? '',
                'cta_label' => $plan['kng_plan_cta_label'] ?? '',
                'cta_link' => $this->normalize_link($link, $plan['kng_plan_cta_label'] ?? ''),
                'footer_note' => $plan['kng_plan_footer_note'] ?? '',
            ];
        }

        return $normalized;
    }

    /**
     * Get ACF data.
     *
     * @param array<string, mixed> $settings Settings.
     *
     * @return array<string, mixed>
     */
    protected function get_acf_data(array $settings): array
    {
        if (!function_exists('get_field')) {
            return [
                'plans' => [],
                'features' => [],
            ];
        }

        $post_id = $this->resolve_acf_post_id($settings);
        $plans_key = trim((string) ($settings['kng_acf_plans_repeater'] ?? ''));
        if ($plans_key === '') {
            return [
                'plans' => [],
                'features' => [],
            ];
        }

        $rows = get_field($plans_key, $post_id);
        if (!is_array($rows)) {
            return [
                'plans' => [],
                'features' => [],
            ];
        }

        $limit = $this->get_max_plans_limit($settings);
        $rows = array_slice($rows, 0, $limit);

        $plans = [];
        $features_map = [];
        $feature_order = [];

        $feature_repeater = trim((string) ($settings['kng_acf_plan_features_repeater'] ?? ''));
        $label_field = trim((string) ($settings['kng_acf_feature_label_field'] ?? ''));
        $value_field = trim((string) ($settings['kng_acf_feature_value_field'] ?? ''));
        $type_field = trim((string) ($settings['kng_acf_feature_value_type_field'] ?? ''));
        $tooltip_field = trim((string) ($settings['kng_acf_feature_tooltip_field'] ?? ''));
        $group_field = trim((string) ($settings['kng_acf_feature_group_field'] ?? ''));

        foreach ($rows as $plan_index => $row) {
            if (!is_array($row)) {
                continue;
            }

            $plans[] = $this->map_acf_plan($row, $settings);

            if ($feature_repeater === '' || $label_field === '' || $value_field === '') {
                continue;
            }

            $feature_rows = $row[$feature_repeater] ?? [];
            if (!is_array($feature_rows)) {
                continue;
            }

            foreach ($feature_rows as $feature_row) {
                if (!is_array($feature_row)) {
                    continue;
                }

                $label = trim((string) ($feature_row[$label_field] ?? ''));
                if ($label === '') {
                    continue;
                }

                $group_value = $group_field ? (string) ($feature_row[$group_field] ?? '') : '';
                $key_base = $group_value !== '' ? $group_value . '-' . $label : $label;
                $key = sanitize_title($key_base);
                if ($key === '') {
                    $key = 'feature-' . md5($key_base);
                }
                if (!isset($features_map[$key])) {
                    $features_map[$key] = [
                        'id' => $key,
                        'label' => $label,
                        'tooltip' => $tooltip_field ? (string) ($feature_row[$tooltip_field] ?? '') : '',
                        'group' => $group_value,
                        'values' => [],
                    ];
                    $feature_order[] = $key;
                }

                $type_override = $type_field ? (string) ($feature_row[$type_field] ?? '') : '';
                $value = $this->normalize_feature_value($feature_row[$value_field] ?? '', $type_override);
                $features_map[$key]['values'][$plan_index] = $value;
            }
        }

        $features = [];
        foreach ($feature_order as $key) {
            $feature = $features_map[$key];
            $feature['values'] = $this->fill_feature_values($feature['values'], count($plans));
            $features[] = $feature;
        }

        return [
            'plans' => $plans,
            'features' => $features,
        ];
    }

    /**
     * Resolve ACF post ID.
     *
     * @param array<string, mixed> $settings Settings.
     *
     * @return int
     */
    protected function resolve_acf_post_id(array $settings): int
    {
        if (($settings['kng_acf_post_source'] ?? 'current') === 'custom') {
            return max(1, (int) ($settings['kng_acf_post_id'] ?? 0));
        }

        return (int) get_the_ID();
    }

    /**
     * Map ACF plan row to plan data.
     *
     * @param array<string, mixed> $row      Plan row.
     * @param array<string, mixed> $settings Settings.
     *
     * @return array<string, mixed>
     */
    protected function map_acf_plan(array $row, array $settings): array
    {
        $name_field = trim((string) ($settings['kng_acf_plan_name_field'] ?? ''));
        $badge_field = trim((string) ($settings['kng_acf_plan_badge_field'] ?? ''));
        $desc_field = trim((string) ($settings['kng_acf_plan_description_field'] ?? ''));
        $price_field = trim((string) ($settings['kng_acf_plan_price_field'] ?? ''));
        $subtitle_field = trim((string) ($settings['kng_acf_plan_subtitle_field'] ?? ''));
        $cta_label_field = trim((string) ($settings['kng_acf_plan_cta_label_field'] ?? ''));
        $cta_link_field = trim((string) ($settings['kng_acf_plan_cta_link_field'] ?? ''));
        $footer_field = trim((string) ($settings['kng_acf_plan_footer_field'] ?? ''));

        $cta_label = $cta_label_field ? (string) ($row[$cta_label_field] ?? '') : '';
        $cta_link = $cta_link_field ? ($row[$cta_link_field] ?? []) : [];

        $link = $this->normalize_link($cta_link, $cta_label);

        return [
            'name' => $name_field ? (string) ($row[$name_field] ?? '') : '',
            'badge' => $badge_field ? (string) ($row[$badge_field] ?? '') : '',
            'description' => $desc_field ? (string) ($row[$desc_field] ?? '') : '',
            'price' => $price_field ? (string) ($row[$price_field] ?? '') : '',
            'subtitle' => $subtitle_field ? (string) ($row[$subtitle_field] ?? '') : '',
            'cta_label' => $cta_label !== '' ? $cta_label : ($link['title'] ?? ''),
            'cta_link' => $link,
            'footer_note' => $footer_field ? (string) ($row[$footer_field] ?? '') : '',
        ];
    }

    /**
     * Get WooCommerce data.
     *
     * @param array<string, mixed> $settings Settings.
     *
     * @return array<string, mixed>
     */
    protected function get_woo_data(array $settings): array
    {
        if (!class_exists('WooCommerce') || !function_exists('wc_get_product')) {
            return [
                'plans' => [],
                'features' => [],
            ];
        }

        if (($settings['kng_woo_compare_variations'] ?? '') === 'yes') {
            $variation_data = $this->get_woo_variations_data($settings);
            if (!empty($variation_data['plans'])) {
                return $variation_data;
            }
        }

        $products = $this->get_woo_products($settings);
        if (count($products) < 2) {
            return [
                'plans' => [],
                'features' => [],
            ];
        }

        $plans = [];
        foreach ($products as $product) {
            $plans[] = $this->map_woo_plan($product, $settings);
        }

        $features = $this->get_woo_features($products, $settings);

        return [
            'plans' => $plans,
            'features' => $features,
        ];
    }

    /**
     * Get WooCommerce products.
     *
     * @param array<string, mixed> $settings Settings.
     *
     * @return array<int, WC_Product>
     */
    protected function get_woo_products(array $settings): array
    {
        $source = $settings['kng_woo_products_source'] ?? 'manual';
        $limit = $this->get_max_plans_limit($settings, 'kng_woo_products_limit');

        $products = [];
        if ($source === 'manual') {
            $ids = $settings['kng_woo_products'] ?? [];
            if (!is_array($ids)) {
                $ids = [];
            }
            foreach ($ids as $id) {
                $product = wc_get_product((int) $id);
                if ($product) {
                    $products[] = $product;
                }
            }
        } else {
            $current_id = (int) get_the_ID();
            $current_product = $current_id ? wc_get_product($current_id) : null;
            $ids = [];
            if ($current_product) {
                if ($source === 'related') {
                    $ids = wc_get_related_products($current_id, $limit);
                } elseif ($source === 'upsell') {
                    $ids = $current_product->get_upsell_ids();
                } elseif ($source === 'cross_sell') {
                    $ids = $current_product->get_cross_sell_ids();
                }
            }

            $ids = array_slice(array_filter(array_map('intval', $ids)), 0, $limit);
            foreach ($ids as $id) {
                $product = wc_get_product($id);
                if ($product) {
                    $products[] = $product;
                }
            }
        }

        return array_slice($products, 0, $limit);
    }

    /**
     * Get WooCommerce variations data for a single variable product.
     *
     * @param array<string, mixed> $settings Settings.
     *
     * @return array<string, mixed>
     */
    protected function get_woo_variations_data(array $settings): array
    {
        $ids = $settings['kng_woo_products'] ?? [];
        if (!is_array($ids) || empty($ids)) {
            return [
                'plans' => [],
                'features' => [],
            ];
        }

        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (empty($ids)) {
            return [
                'plans' => [],
                'features' => [],
            ];
        }

        $product_id = (int) $ids[0];
        $product = $product_id ? wc_get_product($product_id) : null;
        if (!$product || !$product->is_type('variable')) {
            return [
                'plans' => [],
                'features' => [],
            ];
        }

        $limit = $this->get_max_plans_limit($settings, 'kng_woo_products_limit');
        $variation_ids = array_slice($product->get_children(), 0, $limit);
        $variations = [];

        foreach ($variation_ids as $variation_id) {
            $variation = wc_get_product((int) $variation_id);
            if ($variation) {
                $variations[] = $variation;
            }
        }

        if (count($variations) < 2) {
            return [
                'plans' => [],
                'features' => [],
            ];
        }

        $plans = [];
        foreach ($variations as $variation) {
            $plans[] = $this->map_woo_variation_plan($variation, $product, $settings);
        }

        return [
            'plans' => $plans,
            'features' => $this->get_woo_features($variations, $settings),
        ];
    }

    /**
     * Map WooCommerce product to plan data.
     *
     * @param WC_Product           $product  Product.
     * @param array<string, mixed> $settings Settings.
     *
     * @return array<string, mixed>
     */
    protected function map_woo_plan(WC_Product $product, array $settings): array
    {
        $show_price = ($settings['kng_woo_show_price'] ?? '') === 'yes';
        $cta_mode = $settings['kng_woo_cta_mode'] ?? 'product';
        $cta_label = $settings['kng_woo_cta_label'] ?? esc_html__('View Product', 'king-addons');

        $link = [
            'url' => '',
            'is_external' => false,
            'nofollow' => false,
            'title' => '',
        ];

        if ($cta_mode === 'add_to_cart') {
            $link['url'] = $product->add_to_cart_url();
        } else {
            $link['url'] = get_permalink($product->get_id());
        }

        return [
            'name' => $product->get_name(),
            'badge' => '',
            'description' => '',
            'price' => $show_price ? $product->get_price_html() : '',
            'subtitle' => '',
            'cta_label' => $cta_label,
            'cta_link' => $link,
            'footer_note' => '',
        ];
    }

    /**
     * Map WooCommerce variation to plan data.
     *
     * @param WC_Product           $variation Variation product.
     * @param WC_Product           $parent    Parent product.
     * @param array<string, mixed> $settings  Settings.
     *
     * @return array<string, mixed>
     */
    protected function map_woo_variation_plan(WC_Product $variation, WC_Product $parent, array $settings): array
    {
        $show_price = ($settings['kng_woo_show_price'] ?? '') === 'yes';
        $cta_mode = $settings['kng_woo_cta_mode'] ?? 'product';
        $cta_label = $settings['kng_woo_cta_label'] ?? esc_html__('View Product', 'king-addons');
        $name_mode = $settings['kng_woo_variation_title_mode'] ?? 'full';
        $subtitle_mode = $settings['kng_woo_variation_subtitle_mode'] ?? 'none';

        $link = [
            'url' => '',
            'is_external' => false,
            'nofollow' => false,
            'title' => '',
        ];

        if ($cta_mode === 'add_to_cart') {
            $link['url'] = $variation->add_to_cart_url();
        } else {
            $link['url'] = $this->get_variation_permalink($variation, $parent);
        }

        $parent_name = $parent->get_name();
        $variation_name = $variation->get_formatted_name();
        if ($variation_name === '') {
            $variation_name = $parent_name;
        }

        $variation_attributes = $this->format_variation_attributes($variation);
        $name = $variation_name;

        if ($name_mode === 'attributes') {
            $name = $variation_attributes !== '' ? $variation_attributes : $parent_name;
        } elseif ($name_mode === 'parent') {
            $name = $parent_name;
        }

        $subtitle = '';
        if ($subtitle_mode === 'attributes') {
            $subtitle = $variation_attributes;
        }

        if ($subtitle !== '' && $subtitle === $name) {
            $subtitle = '';
        }

        return [
            'name' => $name,
            'badge' => '',
            'description' => '',
            'price' => $show_price ? $variation->get_price_html() : '',
            'subtitle' => $subtitle,
            'cta_label' => $cta_label,
            'cta_link' => $link,
            'footer_note' => '',
        ];
    }

    /**
     * Build variation permalink with preselected attributes.
     *
     * @param WC_Product $variation Variation product.
     * @param WC_Product $parent    Parent product.
     *
     * @return string
     */
    protected function get_variation_permalink(WC_Product $variation, WC_Product $parent): string
    {
        $permalink = get_permalink($parent->get_id());
        if (!$permalink) {
            return '';
        }

        if (!method_exists($variation, 'get_variation_attributes')) {
            return $permalink;
        }

        $attributes = $variation->get_variation_attributes();
        if (!is_array($attributes) || empty($attributes)) {
            return $permalink;
        }

        $query_args = [];
        foreach ($attributes as $key => $value) {
            $value = is_array($value) ? implode(', ', $value) : (string) $value;
            if ($value === '') {
                continue;
            }
            $query_args[(string) $key] = $value;
        }

        if (empty($query_args)) {
            return $permalink;
        }

        $query_args['variation_id'] = (string) $variation->get_id();

        return add_query_arg($query_args, $permalink);
    }

    /**
     * Format variation attributes for labels or subtitles.
     *
     * @param WC_Product $variation Variation product.
     *
     * @return string
     */
    protected function format_variation_attributes(WC_Product $variation): string
    {
        if (!method_exists($variation, 'get_variation_attributes')) {
            return '';
        }

        $attributes = $variation->get_variation_attributes();
        if (!is_array($attributes) || empty($attributes)) {
            return '';
        }

        $parts = [];
        foreach ($attributes as $key => $value) {
            $value = is_array($value) ? implode(', ', $value) : (string) $value;
            if ($value === '') {
                continue;
            }

            $taxonomy = str_replace('attribute_', '', (string) $key);
            $label = '';
            if ($taxonomy !== '') {
                if (function_exists('wc_attribute_label')) {
                    $label = wc_attribute_label($taxonomy);
                }
                if ($label === '') {
                    $label = ucwords(str_replace(['pa_', '-', '_'], ' ', $taxonomy));
                }
            }

            $value_label = $value;
            if ($taxonomy !== '' && taxonomy_exists($taxonomy)) {
                $term = get_term_by('slug', $value, $taxonomy);
                if ($term && !is_wp_error($term)) {
                    $value_label = $term->name;
                }
            }

            if ($label !== '') {
                $parts[] = $label . ': ' . $value_label;
            } else {
                $parts[] = $value_label;
            }
        }

        return implode(', ', $parts);
    }

    /**
     * Build feature list from WooCommerce attributes.
     *
     * @param array<int, WC_Product> $products Products.
     * @param array<string, mixed>   $settings Settings.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function get_woo_features(array $products, array $settings): array
    {
        $attributes = $settings['kng_woo_attributes'] ?? [];
        if (!is_array($attributes)) {
            return [];
        }

        $features = [];
        foreach ($attributes as $attribute) {
            $slug = trim((string) ($attribute['kng_woo_attr_slug'] ?? ''));
            if ($slug === '') {
                continue;
            }

            $label_override = trim((string) ($attribute['kng_woo_attr_label'] ?? ''));
            $label = $label_override;
            if ($label === '') {
                if (function_exists('wc_attribute_label')) {
                    $label = wc_attribute_label($slug);
                } else {
                    $label = $slug;
                }
            }

            $group = trim((string) ($attribute['kng_woo_attr_group'] ?? ''));
            $tooltip = trim((string) ($attribute['kng_woo_attr_tooltip'] ?? ''));
            $fallback = trim((string) ($attribute['kng_woo_attr_fallback'] ?? ''));
            $id_base = $group !== '' ? $group . '-' . $slug : $slug;
            $feature_id = sanitize_title($id_base);
            if ($feature_id === '') {
                $feature_id = 'feature-' . md5($id_base);
            }

            $values = [];
            foreach ($products as $product) {
                $raw = $this->get_product_attribute_value($product, $slug, $fallback);
                $values[] = $this->normalize_feature_value($raw);
            }

            $features[] = [
                'id' => $feature_id,
                'label' => $label,
                'tooltip' => $tooltip,
                'group' => $group,
                'values' => $values,
            ];
        }

        return $features;
    }

    /**
     * Get product attribute value with fallback.
     *
     * @param WC_Product $product  Product.
     * @param string     $slug     Attribute slug.
     * @param string     $fallback Fallback meta key.
     *
     * @return mixed
     */
    protected function get_product_attribute_value(WC_Product $product, string $slug, string $fallback)
    {
        $value = '';

        if (taxonomy_exists($slug) && !$product->is_type('variation')) {
            $terms = wc_get_product_terms($product->get_id(), $slug, ['fields' => 'names']);
            if (!empty($terms) && is_array($terms)) {
                $value = implode(', ', $terms);
            }
        } else {
            $value = $product->get_attribute($slug);
        }

        if ($value === '' && $product->is_type('variation')) {
            $parent = wc_get_product($product->get_parent_id());
            if ($parent) {
                if (taxonomy_exists($slug)) {
                    $terms = wc_get_product_terms($parent->get_id(), $slug, ['fields' => 'names']);
                    if (!empty($terms) && is_array($terms)) {
                        $value = implode(', ', $terms);
                    }
                } else {
                    $value = $parent->get_attribute($slug);
                }
            }
        }

        if ($value === '' && $fallback !== '') {
            $meta_value = get_post_meta($product->get_id(), $fallback, true);
            if ($meta_value !== '') {
                $value = $meta_value;
            }
        }

        return $value;
    }

    /**
     * Normalize feature value for rendering.
     *
     * @param mixed  $value        Raw value.
     * @param string $type_override Optional type.
     *
     * @return array{type: string, text: string}
     */
    protected function normalize_feature_value($value, string $type_override = ''): array
    {
        $type_override = strtolower(trim($type_override));
        if ($type_override !== '') {
            $type_override = $this->sanitize_feature_type($type_override);
        }

        if ($type_override !== '') {
            return [
                'type' => $type_override,
                'text' => $type_override === 'text' ? (string) $value : '',
            ];
        }

        if (is_bool($value)) {
            return [
                'type' => $value ? 'included' : 'excluded',
                'text' => '',
            ];
        }

        if (is_array($value)) {
            $value = implode(', ', array_map('strval', $value));
        }

        $value = trim((string) $value);
        if ($value === '') {
            return [
                'type' => 'excluded',
                'text' => '',
            ];
        }

        $normalized = strtolower($value);
        $included_values = ['yes', 'true', 'included', 'available', 'check'];
        $excluded_values = ['no', 'false', 'excluded', 'not available', 'x', '-'];
        $limited_values = ['limited', 'partial', 'some'];

        if (in_array($normalized, $included_values, true)) {
            return [
                'type' => 'included',
                'text' => '',
            ];
        }

        if (in_array($normalized, $excluded_values, true)) {
            return [
                'type' => 'excluded',
                'text' => '',
            ];
        }

        if (in_array($normalized, $limited_values, true)) {
            return [
                'type' => 'limited',
                'text' => '',
            ];
        }

        return [
            'type' => 'text',
            'text' => $value,
        ];
    }

    /**
     * Sanitize feature type.
     *
     * @param string $type Type.
     *
     * @return string
     */
    protected function sanitize_feature_type(string $type): string
    {
        $allowed = ['included', 'excluded', 'limited', 'text'];
        return in_array($type, $allowed, true) ? $type : '';
    }

    /**
     * Get manual feature value for plan index.
     *
     * @param array<string, mixed> $feature Feature data.
     * @param int                  $index   Plan index (1-based).
     *
     * @return array{type: string, text: string}
     */
    protected function get_feature_value(array $feature, int $index): array
    {
        $type_key = 'kng_feature_value_' . $index . '_type';
        $text_key = 'kng_feature_value_' . $index . '_text';

        $type = (string) ($feature[$type_key] ?? 'excluded');
        $type = in_array($type, ['included', 'excluded', 'limited', 'text'], true) ? $type : 'excluded';
        $text = trim((string) ($feature[$text_key] ?? ''));

        return [
            'type' => $type,
            'text' => $text,
        ];
    }

    /**
     * Fill missing feature values with defaults.
     *
     * @param array<int, array{type: string, text: string}> $values Values.
     * @param int                                            $count  Plan count.
     *
     * @return array<int, array{type: string, text: string}>
     */
    protected function fill_feature_values(array $values, int $count): array
    {
        for ($i = 0; $i < $count; $i++) {
            if (!isset($values[$i])) {
                $values[$i] = [
                    'type' => 'excluded',
                    'text' => '',
                ];
            }
        }

        ksort($values);

        return array_values($values);
    }

    /**
     * Normalize link data.
     *
     * @param mixed  $link        Link data.
     * @param string $label_fallback Label fallback.
     *
     * @return array<string, mixed>
     */
    protected function normalize_link($link, string $label_fallback = ''): array
    {
        if (is_string($link)) {
            $link = [
                'url' => $link,
            ];
        }

        if (!is_array($link)) {
            $link = [];
        }

        return [
            'url' => $link['url'] ?? '',
            'is_external' => !empty($link['target']) || !empty($link['is_external']),
            'nofollow' => !empty($link['nofollow']),
            'title' => $link['title'] ?? $label_fallback,
        ];
    }

    /**
     * Get max plan limit based on settings.
     *
     * @param array<string, mixed> $settings Settings.
     * @param string               $key      Optional key.
     *
     * @return int
     */
    protected function get_max_plans_limit(array $settings, string $key = 'kng_max_plans'): int
    {
        $limit = isset($settings[$key]) ? (int) $settings[$key] : 4;
        $limit = max(2, min(8, $limit));
        if (!$this->can_use_pro()) {
            $limit = min(3, $limit);
        }
        return $limit;
    }

    /**
     * Check if we are in Elementor editor mode.
     */
    protected function is_editor_mode(): bool
    {
        return class_exists(Plugin::class) && Plugin::$instance->editor->is_edit_mode();
    }

    /**
     * Check if Pro is available.
     */
    protected function can_use_pro(): bool
    {
        return function_exists('king_addons_can_use_pro') && king_addons_can_use_pro();
    }

    /**
     * Get Pro label helper.
     *
     * @param string $label Label.
     *
     * @return string
     */
    protected function get_pro_label(string $label): string
    {
        if ($this->can_use_pro()) {
            return $label;
        }

        return $label . ' <i class="eicon-pro-icon"></i>';
    }

    /**
     * Get pro control class helper.
     */
    protected function get_pro_control_class(): string
    {
        return $this->can_use_pro() ? '' : 'king-addons-pro-control';
    }
}
