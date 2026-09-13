<?php
/**
 * King Addons dynamic tag.
 *
 * @package King_Addons
 */

namespace King_Addons\Dynamic_Tags;

use Elementor\Controls_Manager;
use Elementor\Modules\DynamicTags\Module;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * A field of the current WooCommerce product.
 *
 * Only registered while WooCommerce is active - see
 * King_Addons\Dynamic_Tags::has_woo().
 */
class Product_Field extends Text_Tag
{
    public function get_name()
    {
        return 'king-addons-product-field';
    }

    public function get_title()
    {
        return $this->ka_title_with_pro(esc_html__('Product Field', 'king-addons'));
    }

    public function get_categories()
    {
        return [Module::TEXT_CATEGORY, Module::NUMBER_CATEGORY];
    }

    protected function register_controls()
    {
        $this->ka_register_pro_notice();

        $this->add_control(
            'field',
            [
                'label' => esc_html__('Field', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'price_html' => esc_html__('Price (formatted)', 'king-addons'),
                    'price' => esc_html__('Price (raw)', 'king-addons'),
                    'regular_price' => esc_html__('Regular price', 'king-addons'),
                    'sale_price' => esc_html__('Sale price', 'king-addons'),
                    'sku' => esc_html__('SKU', 'king-addons'),
                    'stock_status' => esc_html__('Stock status', 'king-addons'),
                    'stock_quantity' => esc_html__('Stock quantity', 'king-addons'),
                    'weight' => esc_html__('Weight', 'king-addons'),
                    'dimensions' => esc_html__('Dimensions', 'king-addons'),
                    'short_description' => esc_html__('Short description', 'king-addons'),
                    'average_rating' => esc_html__('Average rating', 'king-addons'),
                    'review_count' => esc_html__('Review count', 'king-addons'),
                    'categories' => esc_html__('Categories', 'king-addons'),
                    'tags' => esc_html__('Tags', 'king-addons'),
                    'attribute' => esc_html__('Attribute', 'king-addons'),
                ],
                'default' => 'price_html',
            ]
        );

        $this->add_control(
            'attribute_slug',
            [
                'label' => esc_html__('Attribute', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => 'color',
                'description' => esc_html__('Name or slug of the attribute, with or without the "pa_" prefix.', 'king-addons'),
                'condition' => ['field' => 'attribute'],
            ]
        );

        $this->add_control(
            'separator_glue',
            [
                'label' => esc_html__('Separator', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => ', ',
                'condition' => ['field' => ['categories', 'tags', 'attribute']],
            ]
        );
    }

    public function render()
    {
        if ($this->ka_pro_locked()) {
            return;
        }

        if (!function_exists('wc_get_product')) {
            $this->ka_echo('');
            return;
        }

        $product = wc_get_product($this->ka_get_post_id());
        if (!$product instanceof \WC_Product) {
            $this->ka_echo('');
            return;
        }

        $field = (string) $this->get_settings('field');
        $glue = (string) $this->get_settings('separator_glue');
        if ('' === $glue) {
            $glue = ', ';
        }

        $value = '';

        switch ($field) {
            case 'price':
                $value = (string) $product->get_price();
                break;
            case 'regular_price':
                $value = (string) $product->get_regular_price();
                break;
            case 'sale_price':
                $value = (string) $product->get_sale_price();
                break;
            case 'sku':
                $value = (string) $product->get_sku();
                break;
            case 'stock_status':
                $statuses = function_exists('wc_get_product_stock_status_options')
                    ? wc_get_product_stock_status_options()
                    : [];
                $status = (string) $product->get_stock_status();
                $value = isset($statuses[$status]) ? (string) $statuses[$status] : $status;
                break;
            case 'stock_quantity':
                $qty = $product->get_stock_quantity();
                $value = null === $qty ? '' : (string) $qty;
                break;
            case 'weight':
                $weight = (string) $product->get_weight();
                $value = '' === $weight ? '' : $weight . ' ' . get_option('woocommerce_weight_unit');
                break;
            case 'dimensions':
                // wc_format_dimensions() returns the localised "N/A" string when
                // the product has no dimensions at all.
                $dimensions = wc_format_dimensions($product->get_dimensions(false));
                $value = ($dimensions === __('N/A', 'woocommerce')) ? '' : (string) $dimensions;
                break;
            case 'short_description':
                $value = (string) $product->get_short_description();
                break;
            case 'average_rating':
                $rating = (float) $product->get_average_rating();
                $value = $rating > 0 ? (string) $rating : '';
                break;
            case 'review_count':
                $value = (string) (int) $product->get_review_count();
                break;
            case 'categories':
            case 'tags':
                $taxonomy = 'categories' === $field ? 'product_cat' : 'product_tag';
                $names = wp_get_post_terms($product->get_id(), $taxonomy, ['fields' => 'names']);
                $value = is_wp_error($names) ? '' : implode($glue, $names);
                break;
            case 'attribute':
                $slug = sanitize_title((string) $this->get_settings('attribute_slug'));
                if ('' === $slug) {
                    break;
                }

                $attributes = $product->get_attributes();
                // Global attributes are stored with the "pa_" prefix. Custom
                // product attributes are not, so "pa_size" still has to match
                // a local "size" key.
                $object = $attributes[$slug]
                    ?? ($attributes['pa_' . $slug] ?? null);
                if (!$object instanceof \WC_Product_Attribute && str_starts_with($slug, 'pa_')) {
                    $object = $attributes[substr($slug, 3)] ?? null;
                }
                if (!$object instanceof \WC_Product_Attribute) {
                    break;
                }

                // Reading the options directly, rather than parsing
                // get_attribute()'s string, is what makes the separator work:
                // WooCommerce joins taxonomy terms with ", " but custom
                // attribute values with " | ".
                $values = $object->is_taxonomy()
                    ? wc_get_product_terms($product->get_id(), $object->get_name(), ['fields' => 'names'])
                    : $object->get_options();

                if (!is_array($values)) {
                    $values = [];
                }

                $value = implode($glue, array_map('trim', array_map('strval', $values)));
                break;
            case 'price_html':
            default:
                $value = (string) $product->get_price_html();
                break;
        }

        $this->ka_echo($value);
    }
}
