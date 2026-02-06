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

        if (!empty($this->filter_state['filters']['meta']) && is_array($this->filter_state['filters']['meta'])) {
            foreach ($this->filter_state['filters']['meta'] as $meta_key => $meta_value) {
                if (is_array($meta_value) && array_key_exists('min', $meta_value) && array_key_exists('max', $meta_value)) {
                    $args['meta_query'][] = [
                        'key' => sanitize_key((string) $meta_key),
                        'value' => [
                            (float) $meta_value['min'],
                            (float) $meta_value['max'],
                        ],
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
                $min = isset($price['min']) ? (float) $price['min'] : 0;
                $max = isset($price['max']) ? (float) $price['max'] : 999999;
                $args['meta_query'][] = [
                    'key' => '_price',
                    'value' => [$min, $max],
                    'type' => 'NUMERIC',
                    'compare' => 'BETWEEN',
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
            $terms = wp_get_object_terms($ids, $taxonomy);
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

        $prices = [];
        foreach ($ids as $id) {
            $price = get_post_meta($id, '_price', true);
            if ('' === $price) {
                continue;
            }
            $prices[] = (float) $price;
        }

        $results = [];
        foreach ($buckets as $bucket) {
            $min = isset($bucket['min']) ? (float) $bucket['min'] : 0;
            $max = isset($bucket['max']) ? (float) $bucket['max'] : 999999;
            $key = $min . '-' . $max;
            $results[$key] = 0;
            foreach ($prices as $price) {
                if ($price >= $min && $price <= $max) {
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
}






