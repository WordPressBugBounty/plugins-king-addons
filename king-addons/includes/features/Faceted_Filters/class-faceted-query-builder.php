<?php
/**
 * Faceted Query Builder helper.
 *
 * @package King_Addons
 */

namespace King_Addons;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Translates filter state into WP_Query arguments.
 */
class Faceted_Query_Builder
{
    /**
     * Baseline query arguments from the widget.
     *
     * @var array<string, mixed>
     */
    protected array $base_args;

    /**
     * Widget query baseline settings.
     *
     * @var array<string, mixed>
     */
    protected array $grid_settings;

    /**
     * Filter state from frontend.
     *
     * @var array<string, mixed>
     */
    protected array $filter_state;

    /**
     * Constructor.
     *
     * @param array<string, mixed> $grid_settings Widget settings.
     * @param array<string, mixed> $filter_state  Filter state.
     * @param array<string, mixed> $base_args     Base query arguments.
     */
    public function __construct(array $grid_settings, array $filter_state, array $base_args = [])
    {
        $this->grid_settings = $grid_settings;
        $this->filter_state = $filter_state;
        $this->base_args = $base_args;
        add_filter('posts_clauses', [$this, 'apply_price_clauses'], 20, 2);
    }

    /**
     * Build WP_Query arguments from state.
     *
     * @return array<string, mixed>
     */
    public function build_query_args(): array
    {
        $args = $this->base_args;

        if (!empty($this->grid_settings['kng_post_type'])) {
            $args['post_type'] = $this->grid_settings['kng_post_type'];
        }

        if (empty($args['post_type']) && !empty($this->filter_state['filters']['post_type'])) {
            $args['post_type'] = sanitize_key((string) $this->filter_state['filters']['post_type']);
        }

        if (!empty($this->filter_state['page'])) {
            $args['paged'] = max(1, (int) $this->filter_state['page']);
        }

        if (!empty($this->filter_state['filters']['search'])) {
            $args['s'] = sanitize_text_field((string) $this->filter_state['filters']['search']);
        }

        if (!empty($this->filter_state['filters']['taxonomy']) && is_array($this->filter_state['filters']['taxonomy'])) {
            $tax_query = $args['tax_query'] ?? [];
            foreach ($this->filter_state['filters']['taxonomy'] as $taxonomy => $terms) {
                if (empty($terms) || !is_array($terms)) {
                    continue;
                }

                $tax_query[] = [
                    'taxonomy' => sanitize_key((string) $taxonomy),
                    'field' => 'slug',
                    'terms' => array_map('sanitize_title', $terms),
                    'operator' => 'IN',
                ];
            }

            if (!empty($tax_query)) {
                $args['tax_query'] = $tax_query;
            }
        }

        $args['meta_query'] = $args['meta_query'] ?? [];

        if (
            !empty($this->filter_state['filters']['meta'])
            && is_array($this->filter_state['filters']['meta'])
            && function_exists('king_addons_can_use_pro')
            && king_addons_can_use_pro()
        ) {
            foreach ($this->filter_state['filters']['meta'] as $meta_key => $meta_value) {
                if (is_array($meta_value) && (array_key_exists('min', $meta_value) || array_key_exists('max', $meta_value))) {
                    $min = (isset($meta_value['min']) && $meta_value['min'] !== '') ? (float) $meta_value['min'] : 0;
                    $max = (isset($meta_value['max']) && $meta_value['max'] !== '') ? (float) $meta_value['max'] : 999999;
                    $args['meta_query'][] = [
                        'key' => sanitize_key((string) $meta_key),
                        'value' => [$min, $max],
                        'type' => 'NUMERIC',
                        'compare' => 'BETWEEN',
                    ];
                    continue;
                }

                if (empty($meta_value)) {
                    continue;
                }

                $args['meta_query'][] = [
                    'key' => sanitize_key((string) $meta_key),
                    'value' => array_map('sanitize_text_field', (array) $meta_value),
                    'compare' => 'IN',
                ];
            }
        }

        if (!empty($this->filter_state['filters']['price']) && is_array($this->filter_state['filters']['price'])) {
            $price = $this->filter_state['filters']['price'];
            if (isset($price['min']) || isset($price['max'])) {
                $args['ka_ff_price'] = [
                    'min' => isset($price['min']) && $price['min'] !== '' ? (float) $price['min'] : 0,
                    'max' => isset($price['max']) && $price['max'] !== '' ? (float) $price['max'] : 999999,
                    'mode' => $this->variable_price_mode(),
                ];
            }
        }

        if (!empty($this->filter_state['filters']['orderby'])) {
            $args['orderby'] = sanitize_key((string) $this->filter_state['filters']['orderby']);
        }

        if (!empty($this->filter_state['filters']['order'])) {
            $args['order'] = 'ASC' === strtoupper((string) $this->filter_state['filters']['order']) ? 'ASC' : 'DESC';
        }

        if (isset($args['meta_query']) && empty($args['meta_query'])) {
            unset($args['meta_query']);
        }

        if (isset($args['tax_query']) && is_array($args['tax_query']) && count($args['tax_query']) > 1) {
            $args['tax_query']['relation'] = 'AND';
        }
        if (isset($args['meta_query']) && is_array($args['meta_query']) && count($args['meta_query']) > 1) {
            $args['meta_query']['relation'] = 'AND';
        }

        return $args;
    }

    /**
     * Build taxonomy counts for the current query result set.
     *
     * @param array<string, string> $taxonomies Map of taxonomy keys.
     * @param array<string, mixed>  $query_args Query arguments to inspect.
     *
     * @return array<string, array<string, int>>
     */
    public function build_taxonomy_counts(array $taxonomies, array $query_args): array
    {
        $ids = $this->get_object_ids($query_args);
        if (empty($ids)) {
            return [];
        }

        $counts = [];
        foreach ($taxonomies as $taxonomy) {
            $terms = wp_get_object_terms($ids, $taxonomy, ['fields' => 'all_with_object_id']);
            if (is_wp_error($terms) || empty($terms)) {
                continue;
            }
            foreach ($terms as $term) {
                $slug = sanitize_title($term->slug);
                if (!isset($counts[$taxonomy][$slug])) {
                    $counts[$taxonomy][$slug] = 0;
                }
                $counts[$taxonomy][$slug]++;
            }
        }

        return $counts;
    }

    /**
     * Build price bucket counts based on matched products.
     *
     * @param array<int, array<string, float|int>> $buckets   Buckets with min/max keys.
     * @param array<string, mixed>                 $query_args Query arguments.
     *
     * @return array<string, int>
     */
    public function build_price_counts(array $buckets, array $query_args): array
    {
        $ids = $this->get_object_ids($query_args);
        if (empty($ids)) {
            return [];
        }

        $ranges = $this->get_price_ranges($ids);

        $results = [];
        foreach ($buckets as $bucket) {
            $min = isset($bucket['min']) ? (float) $bucket['min'] : 0;
            $max = isset($bucket['max']) ? (float) $bucket['max'] : 999999;
            $key = $min . '-' . $max;
            $results[$key] = 0;
            $mode = $this->variable_price_mode();
            foreach ($ranges as $range) {
                if ('displayed' === $mode) {
                    if ($range['min'] >= $min && $range['min'] <= $max) {
                        $results[$key]++;
                    }
                } elseif ($range['min'] <= $max && $range['max'] >= $min) {
                    $results[$key]++;
                }
            }
        }

        return $results;
    }

    /**
     * Build meta value counts for provided meta keys.
     *
     * @param array<int, string>    $meta_keys  Meta keys.
     * @param array<string, mixed>  $query_args Query arguments.
     *
     * @return array<string, array<string, int>>
     */
    public function build_meta_counts(array $meta_keys, array $query_args): array
    {
        $ids = $this->get_object_ids($query_args);
        if (empty($ids) || empty($meta_keys)) {
            return [];
        }

        $counts = [];
        foreach ($meta_keys as $meta_key) {
            $meta_key = sanitize_key($meta_key);
            foreach ($ids as $id) {
                $values = get_post_meta($id, $meta_key, false);
                if (empty($values)) {
                    continue;
                }
                foreach ($values as $val) {
                    $val = is_scalar($val) ? (string) $val : '';
                    if ('' === $val) {
                        continue;
                    }
                    if (!isset($counts[$meta_key][$val])) {
                        $counts[$meta_key][$val] = 0;
                    }
                    $counts[$meta_key][$val]++;
                }
            }
        }

        return $counts;
    }

    /**
     * Get all object IDs for the given query args (ignoring pagination).
     *
     * @param array<string, mixed> $query_args Query arguments.
     *
     * @return array<int>
     */
    private function get_object_ids(array $query_args): array
    {
        $args = $query_args;
        $args['posts_per_page'] = -1;
        $args['paged'] = 1;
        $args['fields'] = 'ids';
        $args['no_found_rows'] = true;

        $query = new \WP_Query($args);
        if (empty($query->posts)) {
            return [];
        }

        return array_map('intval', $query->posts);
    }

    /**
     * Restrict product queries to the selected price range, including variable max/min.
     *
     * @param array<string, string> $clauses Query clauses.
     * @param \WP_Query             $query   Query.
     *
     * @return array<string, string>
     */
    public function apply_price_clauses(array $clauses, \WP_Query $query): array
    {
        $price = $query->get('ka_ff_price');
        if (!is_array($price) || (!isset($price['min']) && !isset($price['max']))) {
            return $clauses;
        }

        global $wpdb;
        $min = isset($price['min']) ? (float) $price['min'] : 0;
        $max = isset($price['max']) ? (float) $price['max'] : 999999;
        $table = $this->product_lookup_table();

        if (!$this->product_lookup_table_exists()) {
            $clauses['where'] .= $wpdb->prepare(
                " AND EXISTS (
                    SELECT 1 FROM {$wpdb->postmeta} AS ka_ff_price_meta
                    WHERE ka_ff_price_meta.post_id = {$wpdb->posts}.ID
                    AND ka_ff_price_meta.meta_key = '_price'
                    AND CAST(ka_ff_price_meta.meta_value AS DECIMAL(20,6)) BETWEEN %f AND %f
                ) ",
                $min,
                $max
            );

            return $clauses;
        }

        if (false === strpos((string) $clauses['join'], 'ka_ff_price_lookup')) {
            $clauses['join'] .= " INNER JOIN {$table} AS ka_ff_price_lookup ON {$wpdb->posts}.ID = ka_ff_price_lookup.product_id ";
        }

        if (($price['mode'] ?? 'any') === 'displayed') {
            $clauses['where'] .= $wpdb->prepare(
                ' AND ka_ff_price_lookup.min_price >= %f AND ka_ff_price_lookup.min_price <= %f ',
                $min,
                $max
            );
        } else {
            $clauses['where'] .= $wpdb->prepare(
                ' AND ka_ff_price_lookup.min_price <= %f AND ka_ff_price_lookup.max_price >= %f ',
                $max,
                $min
            );
        }

        return $clauses;
    }

    /**
     * How variable products match a price filter.
     *
     * @return string any|displayed
     */
    private function variable_price_mode(): string
    {
        $raw = $this->filter_state['variable_price'] ?? '';
        if (!is_string($raw) || '' === $raw) {
            $price = $this->filter_state['filters']['price'] ?? [];
            $raw = is_array($price) ? ($price['variable_price'] ?? 'any') : 'any';
        }

        return 'displayed' === $raw ? 'displayed' : 'any';
    }

    /**
     * WooCommerce product lookup table name.
     *
     * @return string
     */
    private function product_lookup_table(): string
    {
        global $wpdb;
        if (isset($wpdb->wc_product_meta_lookup) && is_string($wpdb->wc_product_meta_lookup) && $wpdb->wc_product_meta_lookup !== '') {
            return $wpdb->wc_product_meta_lookup;
        }

        return $wpdb->prefix . 'wc_product_meta_lookup';
    }

    /**
     * Whether the WooCommerce product lookup table exists.
     *
     * @return bool
     */
    private function product_lookup_table_exists(): bool
    {
        global $wpdb;
        static $exists = null;
        if (null !== $exists) {
            return $exists;
        }

        $table = $this->product_lookup_table();
        $found = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table)));
        $exists = ($found === $table);

        return $exists;
    }

    /**
     * Min/max prices for product IDs (variable products use lookup max/min).
     *
     * @param array<int, int> $ids Product ids.
     *
     * @return array<int, array{min: float, max: float}>
     */
    private function get_price_ranges(array $ids): array
    {
        global $wpdb;

        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (empty($ids)) {
            return [];
        }

        $table = $this->product_lookup_table();
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $sql = "SELECT product_id, min_price, max_price FROM {$table} WHERE product_id IN ({$placeholders})";
        $prepared = $wpdb->prepare($sql, ...$ids);
        $rows = $prepared ? $wpdb->get_results($prepared) : [];

        $ranges = [];
        if (is_array($rows)) {
            foreach ($rows as $row) {
                $ranges[(int) $row->product_id] = [
                    'min' => (float) $row->min_price,
                    'max' => (float) $row->max_price,
                ];
            }
        }

        foreach ($ids as $id) {
            if (isset($ranges[$id])) {
                continue;
            }
            $price = get_post_meta($id, '_price', true);
            if ('' === $price) {
                continue;
            }
            $ranges[$id] = [
                'min' => (float) $price,
                'max' => (float) $price,
            ];
        }

        return $ranges;
    }
}






