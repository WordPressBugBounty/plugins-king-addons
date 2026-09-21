<?php
/**
 * Facet Price widget.
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Price range filter widget.
 */
class Facet_Price extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-facet-price';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Price Filter', 'king-addons');
    }

    /**
     * Keywords.
     *
     * @return array<int, string>
     */
    public function get_keywords(): array
    {
        return ['shop', 'product', 'filter', 'filters', 'ajax', 'woocommerce', 'price', 'range', 'faceted', 'smart filters'];
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-facet-price';
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
     * Style dependencies.
     *
     * @return array<int, string>
     */
    public function get_style_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-facet-price-style',
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
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-facet-price-script',
        ];
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
        $this->start_controls_section(
            'kng_facet_price_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Price Filter', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_filters_query_id',
            [
                'label' => esc_html__('Shop Filters ID', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => esc_html__('shop_grid_1', 'king-addons'),
                'description' => esc_html__('Must match the Shop Filters ID on the product grid.', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_min_value',
            [
                'label' => esc_html__('Minimum Price', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'default' => 0,
            ]
        );

        $this->add_control(
            'kng_max_value',
            [
                'label' => esc_html__('Maximum Price', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'default' => 100,
            ]
        );

        $this->add_control(
            'kng_step',
            [
                'label' => esc_html__('Step', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'default' => 1,
                'min' => 0.1,
            ]
        );

        $this->add_control(
            'kng_variable_price',
            [
                'label' => esc_html__('Variable products', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'any',
                'options' => [
                    'any' => esc_html__('Match if any variation is in range', 'king-addons'),
                    'displayed' => esc_html__('Match by the price shown on the card', 'king-addons'),
                ],
                'description' => esc_html__('A variable product may show “from €150” while other variations cost more. “Any variation” keeps it in the results when a shopper filters from €250 and a €260 option exists. “Card price” uses only the number on the card, usually the minimum.', 'king-addons'),
            ]
        );

        $this->end_controls_section();

        require_once KING_ADDONS_PATH . 'includes/helpers/Faceted/Style_Controls.php';
        Facet_Style_Controls::fields($this, '{{WRAPPER}} .king-addons-facet--price .king-addons-facet__input');
        Facet_Style_Controls::buckets($this);
    }

    /**
     * Render widget output.
     *
     * @return void
     */
    public function render(): void
    {
        $settings = $this->get_settings_for_display();
        $query_id = sanitize_title($settings['kng_filters_query_id'] ?? '');

        if ('' === $query_id) {
            return;
        }

        $min = isset($settings['kng_min_value']) ? (float) $settings['kng_min_value'] : 0;
        $max = isset($settings['kng_max_value']) ? (float) $settings['kng_max_value'] : 0;
        $step = isset($settings['kng_step']) ? (float) $settings['kng_step'] : 1;
        $variable_price = (($settings['kng_variable_price'] ?? 'any') === 'displayed') ? 'displayed' : 'any';
        $show_buckets = true;

        ?>
        <div class="king-addons-facet king-addons-facet--price" data-ka-filters-query-id="<?php echo esc_attr($query_id); ?>" data-ka-variable-price="<?php echo esc_attr($variable_price); ?>">
            <div class="king-addons-facet__row">
                <label class="king-addons-facet__label">
                    <?php echo esc_html__('Min', 'king-addons'); ?>
                    <input
                        type="number"
                        class="king-addons-facet__input"
                        data-ka-filter-type="price"
                        data-ka-price-role="min"
                        data-ka-filters-query-id="<?php echo esc_attr($query_id); ?>"
                        step="<?php echo esc_attr($step); ?>"
                        value="<?php echo esc_attr($min); ?>"
                    />
                </label>
                <label class="king-addons-facet__label">
                    <?php echo esc_html__('Max', 'king-addons'); ?>
                    <input
                        type="number"
                        class="king-addons-facet__input"
                        data-ka-filter-type="price"
                        data-ka-price-role="max"
                        data-ka-filters-query-id="<?php echo esc_attr($query_id); ?>"
                        step="<?php echo esc_attr($step); ?>"
                        value="<?php echo esc_attr($max); ?>"
                    />
                </label>
            </div>
            <?php if ($show_buckets) : ?>
                <ul class="king-addons-facet__buckets" data-ka-filters-query-id="<?php echo esc_attr($query_id); ?>">
                    <?php
                    $buckets = apply_filters(
                        'king_addons/faceted_filters/price_buckets',
                        [
                            ['min' => 0, 'max' => 25],
                            ['min' => 25, 'max' => 50],
                            ['min' => 50, 'max' => 100],
                            ['min' => 100, 'max' => 250],
                            ['min' => 250, 'max' => 999999],
                        ],
                        'woo_products_grid'
                    );
                    foreach ($buckets as $bucket) :
                        $bucket_min = isset($bucket['min']) ? (float) $bucket['min'] : 0;
                        $bucket_max = isset($bucket['max']) ? (float) $bucket['max'] : 999999;
                        $bucket_key = $bucket_min . '-' . $bucket_max;
                        if ($bucket_max >= 999999) {
                            $label = sprintf('%s+', wc_price($bucket_min));
                        } else {
                            $label = sprintf('%s - %s', wc_price($bucket_min), wc_price($bucket_max));
                        }
                        ?>
                        <li class="king-addons-facet__bucket">
                            <button
                                type="button"
                                class="king-addons-facet__bucket-btn"
                                data-ka-filter-type="price-bucket"
                                data-ka-filters-query-id="<?php echo esc_attr($query_id); ?>"
                                data-ka-price-bucket="<?php echo esc_attr($bucket_key); ?>"
                                data-ka-price-min="<?php echo esc_attr((string) $bucket_min); ?>"
                                data-ka-price-max="<?php echo esc_attr((string) $bucket_max); ?>"
                                data-ka-price-label="<?php echo esc_attr(wp_strip_all_tags($label)); ?>"
                            >
                                <span class="king-addons-facet__bucket-label"><?php echo wp_kses_post($label); ?></span>
                                <span class="king-addons-facet__bucket-count" data-ka-price-bucket="<?php echo esc_attr($bucket_key); ?>"></span>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <?php
    }
}






