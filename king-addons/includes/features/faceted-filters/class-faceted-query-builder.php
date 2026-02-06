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
     * Base query args from widget settings.
     *
     * @var array<string, mixed>
     */
    protected array $base_args;

    /**
     * Cached post IDs under current filters.
     *
     * @var array<int>
     */
    private array $cached_ids = [];

    /**
     * Constructor.
     *
     * @param array<string, mixed> $grid_settings Widget settings.
     * @param array<string, mixed> $filter_state  Filter state.
     * @param array<string, mixed> $base_args     Baseline query args.
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
            $tax_query = [];
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

        if (!empty($this->filter_state['filters']['meta']) && is_array($this->filter_state['filters']['meta'])) {
            $meta_query = [];
            foreach ($this->filter_state['filters']['meta'] as $meta_key => $meta_value) {
                if (is_array($meta_value) && array_key_exists('min', $meta_value) && array_key_exists('max', $meta_value)) {
                    $meta_query[] = [
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

                $meta_query[] = [
                    'key' => sanitize_key((string) $meta_key),
                    'value' => array_map('sanitize_text_field', (array) $meta_value),
                    'compare' => 'IN',
                ];
            }

            if (!empty($meta_query)) {
                $args['meta_query'] = $meta_query;
            }
        }

        if (!isset($args['meta_query'])) {
            $args['meta_query'] = [];
        }

        if (!empty($this->filter_state['filters']['price']) && is_array($this->filter_state['filters']['price'])) {
            $price = $this->filter_state['filters']['price'];
            if (isset($price['min'], $price['max'])) {
                $args['meta_query'][] = [
                    'key' => '_price',
                    'value' => [
                        (float) $price['min'],
                        (float) $price['max'],
                    ],
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

        return $args;
    }

    /**
     * Build taxonomy counts constrained by current filters.
     *
     * @param array<string,string> $taxonomies Taxonomies to count.
     *
     * @return array<string,array<string,int>>
     */
    public function build_taxonomy_counts(array $taxonomies): array
    {
        $counts = [];
        $base_args = $this->build_query_args();

        // Remove pagination for counts
        unset($base_args['paged']);
        unset($base_args['offset']);
        $base_args['posts_per_page'] = -1;
        $base_args['fields'] = 'ids';

        foreach ($taxonomies as $taxonomy => $label) {
            $query_args = $base_args;
            // Remove existing tax filter on the same taxonomy to get full distribution under other filters
            if (!empty($query_args['tax_query']) && is_array($query_args['tax_query'])) {
                $query_args['tax_query'] = array_values(
                    array_filter(
                        $query_args['tax_query'],
                        static function ($t) use ($taxonomy) {
                            return ($t['taxonomy'] ?? '') !== $taxonomy;
                        }
                    )
                );
                if (empty($query_args['tax_query'])) {
                    unset($query_args['tax_query']);
                }
            }

            $post_ids = get_posts($query_args);
            if (empty($post_ids)) {
                $counts[$taxonomy] = [];
                continue;
            }

            $terms = wp_get_object_terms($post_ids, $taxonomy, ['fields' => 'all']);
            $tax_counts = [];
            if (!is_wp_error($terms)) {
                foreach ($terms as $term) {
                    $tax_counts[$term->slug] = ($tax_counts[$term->slug] ?? 0) + (int) $term->count;
                }
            }
            $counts[$taxonomy] = $tax_counts;
        }

        return $counts;
    }

    /**
     * Build price range counts (bucketed).
     *
     * @param array<int,array<string,float|int>> $buckets Buckets with min/max.
     *
     * @return array<int,int>
     */
    public function build_price_counts(array $buckets): array
    {
        if (empty($buckets)) {
            return [];
        }
        $ids = $this->get_filtered_ids();
        if (empty($ids)) {
            return [];
        }

        $counts = array_fill(0, count($buckets), 0);
        foreach ($ids as $pid) {
            $price = (float) get_post_meta($pid, '_price', true);
            foreach ($buckets as $idx => $bucket) {
                $min = isset($bucket['min']) ? (float) $bucket['min'] : 0.0;
                $max = isset($bucket['max']) ? (float) $bucket['max'] : PHP_FLOAT_MAX;
                if ($price >= $min && $price <= $max) {
                    $counts[$idx] += 1;
                    break;
                }
            }
        }
        return $counts;
    }

    /**
     * Build meta value counts (e.g., ACF/select).
     *
     * @param array<string,int> $meta_keys Meta keys with optional value limit.
     *
     * @return array<string,array<string,int>>
     */
    public function build_meta_counts(array $meta_keys): array
    {
        global $wpdb;
        if (empty($meta_keys)) {
            return [];
        }
        $ids = $this->get_filtered_ids();
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $out = [];

        foreach ($meta_keys as $meta_key => $limit) {
            $limit = $limit ? (int) $limit : 30;
            $sql = $wpdb->prepare(
                "SELECT meta_value, COUNT(*) AS cnt
                FROM {$wpdb->postmeta}
                WHERE meta_key = %s
                AND post_id IN ($placeholders)
                AND meta_value != ''
                GROUP BY meta_value
                ORDER BY cnt DESC
                LIMIT %d",
                array_merge([$meta_key], $ids, [$limit])
            );
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $rows = $wpdb->get_results($sql, ARRAY_A);
            $counts = [];
            if (!empty($rows)) {
                foreach ($rows as $row) {
                    $val = is_array($row['meta_value']) ? wp_json_encode($row['meta_value']) : (string) $row['meta_value'];
                    $counts[$val] = (int) $row['cnt'];
                }
            }
            $out[$meta_key] = $counts;
        }

        return $out;
    }

    /**
     * Get filtered post IDs with current filters (cached).
     *
     * @return array<int>
     */
    private function get_filtered_ids(): array
    {
        if (!empty($this->cached_ids)) {
            return $this->cached_ids;
        }
        $args = $this->build_query_args();
        $args['fields'] = 'ids';
        $args['posts_per_page'] = -1;
        unset($args['paged'], $args['offset']);

        $ids = get_posts($args);
        $this->cached_ids = array_map('absint', $ids);
        return $this->cached_ids;
    }
}






