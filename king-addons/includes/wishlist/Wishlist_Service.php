<?php

namespace King_Addons\Wishlist;

use DateTime;
use DateTimeZone;
use WP_Error;
use wpdb;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Provides CRUD operations for wishlist items and lists.
 */
class Wishlist_Service
{
    private const DEFAULT_WISHLIST_ID = 'default';
    private const CACHE_TTL = 600;

    private Wishlist_Session $session;
    private string $session_key;
    private int $user_id;
    private string $active_wishlist_id;

    /**
     * Set up wishlist service with current user and session.
     *
     * @param int|null $user_id Optional user identifier.
     * @param string|null $session_key Optional session key for guests.
     */
    public function __construct(?int $user_id = null, ?string $session_key = null)
    {
        $this->session = new Wishlist_Session();
        $this->user_id = $user_id ?? get_current_user_id();
        $this->session_key = $session_key ?: $this->session->get_session_key();
        $this->active_wishlist_id = $this->resolve_active_wishlist_id();
    }

    /**
     * Get current active wishlist identifier.
     *
     * @return string Active wishlist id.
     */
    public function get_active_wishlist_id(): string
    {
        return $this->active_wishlist_id;
    }

    /**
     * Set current active wishlist identifier.
     *
     * @param string $wishlist_id Wishlist identifier.
     * @return void
     */
    public function set_active_wishlist_id(string $wishlist_id): void
    {
        $this->active_wishlist_id = $this->normalize_wishlist_id($wishlist_id);
        if ($this->user_id > 0) {
            update_user_meta($this->user_id, 'king_addons_active_wishlist_id', $this->active_wishlist_id);
        }
    }

    /**
     * Add product to wishlist.
     *
     * @param int $product_id Product identifier.
     * @param int $variation_id Variation identifier.
     * @param int $qty Quantity to store.
     * @param string|null $wishlist_id Wishlist identifier.
     * @return array|WP_Error Operation result.
     */
    public function add_item(int $product_id, int $variation_id = 0, int $qty = 1, ?string $wishlist_id = null)
    {
        $validation = $this->validate_product($product_id, $variation_id);
        if (is_wp_error($validation)) {
            return $validation;
        }

        $wishlist_id = $this->normalize_wishlist_id($wishlist_id);
        $qty = max(1, $qty);
        $now = $this->now();

        global $wpdb;
        $table = Wishlist_DB::get_items_table();

        $existing = $this->get_item_row($wishlist_id, $product_id, $variation_id);

        if ($existing) {
            $wpdb->update(
                $table,
                [
                    'qty' => $qty,
                    'updated_at' => $now,
                ],
                [
                    'id' => intval($existing->id),
                ],
                ['%d', '%s'],
                ['%d']
            );
        } else {
            $wpdb->insert(
                $table,
                [
                    'user_id' => $this->user_id,
                    'session_key' => $this->session_key,
                    'wishlist_id' => $wishlist_id,
                    'product_id' => $product_id,
                    'variation_id' => $variation_id,
                    'qty' => $qty,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'meta' => null,
                ],
                ['%d', '%s', '%s', '%d', '%d', '%d', '%s', '%s', '%s']
            );
        }

        $this->ensure_default_list($wishlist_id);
        $this->invalidate_cache($wishlist_id);

        return [
            'success' => true,
            'wishlist_id' => $wishlist_id,
            'count' => $this->get_count($wishlist_id, true),
        ];
    }

    /**
     * Remove a product from wishlist.
     *
     * @param int $product_id Product identifier.
     * @param int $variation_id Variation identifier.
     * @param string|null $wishlist_id Wishlist identifier.
     * @return array Operation result.
     */
    public function remove_item(int $product_id, int $variation_id = 0, ?string $wishlist_id = null): array
    {
        $wishlist_id = $this->normalize_wishlist_id($wishlist_id);
        $row = $this->get_item_row($wishlist_id, $product_id, $variation_id);

        if (!$row) {
            return [
                'success' => false,
                'message' => esc_html__('Item not found in wishlist.', 'king-addons'),
                'count' => $this->get_count($wishlist_id, true),
            ];
        }

        global $wpdb;
        $wpdb->delete(
            Wishlist_DB::get_items_table(),
            ['id' => intval($row->id)],
            ['%d']
        );

        $this->invalidate_cache($wishlist_id);

        return [
            'success' => true,
            'wishlist_id' => $wishlist_id,
            'count' => $this->get_count($wishlist_id, true),
        ];
    }

    /**
     * Toggle wishlist state for a product.
     *
     * @param int $product_id Product identifier.
     * @param int $variation_id Variation identifier.
     * @param int $qty Quantity to store.
     * @param string|null $wishlist_id Wishlist identifier.
     * @return array|WP_Error Operation result.
     */
    public function toggle_item(int $product_id, int $variation_id = 0, int $qty = 1, ?string $wishlist_id = null)
    {
        $wishlist_id = $this->normalize_wishlist_id($wishlist_id);
        $existing = $this->get_item_row($wishlist_id, $product_id, $variation_id);

        if ($existing) {
            return $this->remove_item($product_id, $variation_id, $wishlist_id);
        }

        return $this->add_item($product_id, $variation_id, $qty, $wishlist_id);
    }

    /**
     * Retrieve wishlist items for current user or session.
     *
     * @param string|null $wishlist_id Wishlist identifier.
     * @return array<int, object> List of wishlist rows.
     */
    public function get_items(?string $wishlist_id = null): array
    {
        $wishlist_id = $this->normalize_wishlist_id($wishlist_id);
        global $wpdb;

        $where = $this->get_scope_where($wishlist_id);
        $table = Wishlist_DB::get_items_table();
        $query = "SELECT * FROM {$table} WHERE {$where['sql']} ORDER BY created_at DESC";

        /** @var array<int, object> $items */
        $items = $wpdb->get_results($wpdb->prepare($query, $where['params']));

        return $items ?: [];
    }

    /**
     * Determine if wishlist already contains a product.
     *
     * @param int $product_id Product identifier.
     * @param int $variation_id Variation identifier.
     * @param string|null $wishlist_id Wishlist identifier.
     * @return bool Whether the item is present.
     */
    public function has_item(int $product_id, int $variation_id = 0, ?string $wishlist_id = null): bool
    {
        $wishlist_id = $this->normalize_wishlist_id($wishlist_id);
        return (bool) $this->get_item_row($wishlist_id, $product_id, $variation_id);
    }

    /**
     * Get available wishlists for current scope.
     *
     * @return array<int, object> Lists rows.
     */
    public function get_lists(): array
    {
        global $wpdb;

        $lists_table = Wishlist_DB::get_lists_table();
        $where = $this->user_id > 0
            ? $wpdb->prepare('user_id = %d', $this->user_id)
            : $wpdb->prepare('session_key = %s', $this->session_key);

        $lists = $wpdb->get_results("SELECT * FROM {$lists_table} WHERE {$where} ORDER BY created_at DESC");

        if (empty($lists)) {
            $this->ensure_default_list(self::DEFAULT_WISHLIST_ID);
            $lists = $wpdb->get_results("SELECT * FROM {$lists_table} WHERE {$where} ORDER BY created_at DESC");
        }

        return $lists ?: [];
    }

    /**
     * Create a new wishlist record.
     *
     * @param string $title Wishlist title.
     * @param string $visibility Visibility mode.
     * @return array<string, mixed>|WP_Error Created list data or error.
     */
    public function create_list(string $title, string $visibility = 'private')
    {
        global $wpdb;

        if (empty($title)) {
            return new WP_Error('wishlist_title_missing', esc_html__('List title is required.', 'king-addons'));
        }

        $slug_base = sanitize_title($title);
        $slug = $slug_base ?: 'list-' . wp_generate_uuid4();
        $lists_table = Wishlist_DB::get_lists_table();
        $counter = 1;

        while ($wpdb->get_var($wpdb->prepare("SELECT id FROM {$lists_table} WHERE slug = %s", $slug))) {
            $slug = $slug_base . '-' . $counter;
            ++$counter;
        }

        $now = $this->now();
        $wpdb->insert(
            $lists_table,
            [
                'user_id' => $this->user_id,
                'session_key' => $this->user_id > 0 ? '' : $this->session_key,
                'title' => $title,
                'slug' => $slug,
                'visibility' => in_array($visibility, ['private', 'shared', 'public'], true) ? $visibility : 'private',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        return [
            'id' => $wpdb->insert_id,
            'slug' => $slug,
            'title' => $title,
            'visibility' => $visibility,
        ];
    }

    /**
     * Update item note stored in meta JSON.
     *
     * @param int $product_id Product identifier.
     * @param int $variation_id Variation identifier.
     * @param string $note Note content.
     * @param string|null $wishlist_id Wishlist identifier.
     * @return bool|WP_Error Whether update succeeded.
     */
    public function update_item_note(int $product_id, int $variation_id, string $note, ?string $wishlist_id = null)
    {
        $wishlist_id = $this->normalize_wishlist_id($wishlist_id);
        $row = $this->get_item_row($wishlist_id, $product_id, $variation_id);

        if (!$row) {
            return new WP_Error('wishlist_note_missing_item', esc_html__('Item not found for note update.', 'king-addons'));
        }

        $meta = [];
        if (!empty($row->meta)) {
            $decoded = json_decode($row->meta, true);
            if (is_array($decoded)) {
                $meta = $decoded;
            }
        }

        $meta['note'] = wp_strip_all_tags(wp_trim_words($note, 100));

        global $wpdb;
        $wpdb->update(
            Wishlist_DB::get_items_table(),
            [
                'meta' => wp_json_encode($meta),
                'updated_at' => $this->now(),
            ],
            ['id' => intval($row->id)],
            ['%s', '%s'],
            ['%d']
        );

        return true;
    }

    /**
     * Get aggregated wishlist stats by product with optional date filtering.
     *
     * @param string|null $date_from Start date (Y-m-d format).
     * @param string|null $date_to End date (Y-m-d format).
     * @return array<int, array<string, mixed>> Stats per product.
     */
    public function get_product_stats(?string $date_from = null, ?string $date_to = null): array
    {
        global $wpdb;
        $items_table = Wishlist_DB::get_items_table();
        $conversions_table = Wishlist_DB::get_conversions_table();

        $where_clauses = [];
        $params = [];

        if ($date_from) {
            $where_clauses[] = 'i.created_at >= %s';
            $params[] = $date_from . ' 00:00:00';
        }

        if ($date_to) {
            $where_clauses[] = 'i.created_at <= %s';
            $params[] = $date_to . ' 23:59:59';
        }

        $where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

        // Build the query with conversion stats
        $query = "
            SELECT 
                i.product_id,
                COUNT(DISTINCT i.id) as adds,
                COALESCE(c.conversions, 0) as conversions,
                COALESCE(c.revenue, 0) as revenue
            FROM {$items_table} i
            LEFT JOIN (
                SELECT 
                    product_id,
                    COUNT(DISTINCT order_id) as conversions,
                    SUM(order_item_total) as revenue
                FROM {$conversions_table}
                GROUP BY product_id
            ) c ON i.product_id = c.product_id
            {$where_sql}
            GROUP BY i.product_id
            ORDER BY adds DESC
            LIMIT 100
        ";

        if (!empty($params)) {
            $results = $wpdb->get_results($wpdb->prepare($query, $params), ARRAY_A);
        } else {
            $results = $wpdb->get_results($query, ARRAY_A);
        }

        return $results ?: [];
    }

    /**
     * Get total wishlist statistics summary.
     *
     * @param string|null $date_from Start date (Y-m-d format).
     * @param string|null $date_to End date (Y-m-d format).
     * @return array<string, mixed> Summary stats.
     */
    public function get_stats_summary(?string $date_from = null, ?string $date_to = null): array
    {
        global $wpdb;
        $items_table = Wishlist_DB::get_items_table();
        $conversions_table = Wishlist_DB::get_conversions_table();

        $where_items = '';
        $where_conv = '';
        $params_items = [];
        $params_conv = [];

        if ($date_from) {
            $where_items .= ($where_items ? ' AND ' : 'WHERE ') . 'created_at >= %s';
            $params_items[] = $date_from . ' 00:00:00';
            $where_conv .= ($where_conv ? ' AND ' : 'WHERE ') . 'converted_at >= %s';
            $params_conv[] = $date_from . ' 00:00:00';
        }

        if ($date_to) {
            $where_items .= ($where_items ? ' AND ' : 'WHERE ') . 'created_at <= %s';
            $params_items[] = $date_to . ' 23:59:59';
            $where_conv .= ($where_conv ? ' AND ' : 'WHERE ') . 'converted_at <= %s';
            $params_conv[] = $date_to . ' 23:59:59';
        }

        // Total adds
        $adds_query = "SELECT COUNT(*) FROM {$items_table} {$where_items}";
        $total_adds = !empty($params_items)
            ? (int) $wpdb->get_var($wpdb->prepare($adds_query, $params_items))
            : (int) $wpdb->get_var($adds_query);

        // Total conversions and revenue
        $conv_query = "SELECT COUNT(DISTINCT order_id) as conversions, COALESCE(SUM(order_item_total), 0) as revenue FROM {$conversions_table} {$where_conv}";
        $conv_row = !empty($params_conv)
            ? $wpdb->get_row($wpdb->prepare($conv_query, $params_conv), ARRAY_A)
            : $wpdb->get_row($conv_query, ARRAY_A);

        $total_conversions = (int) ($conv_row['conversions'] ?? 0);
        $total_revenue = (float) ($conv_row['revenue'] ?? 0);

        // Unique users
        $users_query = "SELECT COUNT(DISTINCT user_id) FROM {$items_table} WHERE user_id > 0 {$where_items}";
        // Fix the WHERE clause for users query
        if ($where_items) {
            $users_query = str_replace('WHERE user_id > 0 WHERE', 'WHERE user_id > 0 AND', $users_query);
        }
        $unique_users = !empty($params_items)
            ? (int) $wpdb->get_var($wpdb->prepare($users_query, $params_items))
            : (int) $wpdb->get_var($users_query);

        return [
            'total_adds' => $total_adds,
            'total_conversions' => $total_conversions,
            'total_revenue' => $total_revenue,
            'unique_users' => $unique_users,
            'conversion_rate' => $total_adds > 0 ? round(($total_conversions / $total_adds) * 100, 2) : 0,
        ];
    }

    /**
     * Get wishlist items count.
     *
     * @param string|null $wishlist_id Wishlist identifier.
     * @param bool $force_refresh Skip cache.
     * @return int Count of items.
     */
    public function get_count(?string $wishlist_id = null, bool $force_refresh = false): int
    {
        $wishlist_id = $this->normalize_wishlist_id($wishlist_id);
        $cache_key = $this->get_cache_key($wishlist_id);
        $cache_enabled = Wishlist_Settings::get('cache_enabled', false);
        $cache_ttl = max(0, intval(Wishlist_Settings::get('cache_ttl', self::CACHE_TTL)));

        if (!$force_refresh && $cache_enabled) {
            $cached = get_transient($cache_key);
            if (false !== $cached) {
                return intval($cached);
            }
        }

        global $wpdb;
        $where = $this->get_scope_where($wishlist_id);
        $table = Wishlist_DB::get_items_table();
        $query = "SELECT COUNT(id) FROM {$table} WHERE {$where['sql']}";
        $count = intval($wpdb->get_var($wpdb->prepare($query, $where['params'])));

        if ($cache_enabled) {
            set_transient($cache_key, $count, $cache_ttl ?: self::CACHE_TTL);
        }

        return $count;
    }

    /**
     * Merge guest wishlist items into a user account on login.
     *
     * @param int $user_id User identifier.
     * @param string $session_key Guest session key.
     * @return void
     */
    public function merge_guest_items(int $user_id, string $session_key): void
    {
        if ($user_id <= 0 || empty($session_key)) {
            return;
        }

        global $wpdb;
        $items_table = Wishlist_DB::get_items_table();
        $lists_table = Wishlist_DB::get_lists_table();

        $guest_items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$items_table} WHERE session_key = %s",
                $session_key
            )
        );

        foreach ($guest_items as $item) {
            $wishlist_id = $this->normalize_wishlist_id($item->wishlist_id);
            $existing = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM {$items_table} WHERE user_id = %d AND wishlist_id = %s AND product_id = %d AND variation_id = %d",
                    $user_id,
                    $wishlist_id,
                    $item->product_id,
                    $item->variation_id
                )
            );

            if ($existing) {
                $wpdb->update(
                    $items_table,
                    [
                        'qty' => max(intval($item->qty), 1),
                        'updated_at' => $this->now(),
                    ],
                    ['id' => intval($existing)],
                    ['%d', '%s'],
                    ['%d']
                );
            } else {
                $wpdb->insert(
                    $items_table,
                    [
                        'user_id' => $user_id,
                        'session_key' => '',
                        'wishlist_id' => $wishlist_id,
                        'product_id' => intval($item->product_id),
                        'variation_id' => intval($item->variation_id),
                        'qty' => max(intval($item->qty), 1),
                        'created_at' => $this->now(),
                        'updated_at' => $this->now(),
                        'meta' => $item->meta,
                    ],
                    ['%d', '%s', '%s', '%d', '%d', '%d', '%s', '%s', '%s']
                );
            }
        }

        // Transfer guest lists ownership for Pro multi-list support.
        $wpdb->update(
            $lists_table,
            [
                'user_id' => $user_id,
                'session_key' => '',
                'updated_at' => $this->now(),
            ],
            [
                'session_key' => $session_key,
            ],
            ['%d', '%s', '%s'],
            ['%s']
        );

        // Remove guest rows to avoid duplication.
        $wpdb->delete(
            $items_table,
            ['session_key' => $session_key],
            ['%s']
        );

        $this->session->clear();
        $this->invalidate_cache(self::DEFAULT_WISHLIST_ID);
    }

    /**
     * Remove cached counts for the wishlist.
     *
     * @param string $wishlist_id Wishlist identifier.
     * @return void
     */
    public function invalidate_cache(string $wishlist_id): void
    {
        delete_transient($this->get_cache_key($wishlist_id));
    }

    /**
     * Ensure a default list row exists.
     *
     * @param string $wishlist_id Wishlist identifier.
     * @return void
     */
    public function ensure_default_list(string $wishlist_id): void
    {
        global $wpdb;

        $lists_table = Wishlist_DB::get_lists_table();

        $existing_row = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$lists_table} WHERE (user_id = %d OR session_key = %s) AND slug = %s LIMIT 1",
                $this->user_id,
                $this->session_key,
                $wishlist_id
            )
        );

        if ($existing_row) {
            return;
        }

        $wpdb->insert(
            $lists_table,
            [
                'user_id' => $this->user_id,
                'session_key' => $this->user_id > 0 ? '' : $this->session_key,
                'title' => esc_html__('My Wishlist', 'king-addons'),
                'slug' => $wishlist_id,
                'visibility' => 'private',
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s']
        );
    }

    /**
     * Get a single wishlist item row.
     *
     * @param string $wishlist_id Wishlist identifier.
     * @param int $product_id Product identifier.
     * @param int $variation_id Variation identifier.
     * @return object|null Wishlist row.
     */
    private function get_item_row(string $wishlist_id, int $product_id, int $variation_id): ?object
    {
        global $wpdb;
        $table = Wishlist_DB::get_items_table();
        $where = $this->get_scope_where($wishlist_id);
        $where['sql'] .= ' AND product_id = %d AND variation_id = %d';
        $where['params'][] = $product_id;
        $where['params'][] = $variation_id;

        $query = "SELECT * FROM {$table} WHERE {$where['sql']} LIMIT 1";

        return $wpdb->get_row($wpdb->prepare($query, $where['params']));
    }

    /**
     * Validate product and variation existence.
     *
     * @param int $product_id Product identifier.
     * @param int $variation_id Variation identifier.
     * @return bool|WP_Error Validation result.
     */
    private function validate_product(int $product_id, int $variation_id)
    {
        if (!function_exists('wc_get_product')) {
            return new WP_Error('wishlist_no_wc', esc_html__('WooCommerce is required for wishlist.', 'king-addons'));
        }

        $product = wc_get_product($variation_id > 0 ? $variation_id : $product_id);
        if (!$product) {
            return new WP_Error('wishlist_invalid_product', esc_html__('Product not found.', 'king-addons'));
        }

        return true;
    }

    /**
     * Build scope-aware WHERE clause for queries.
     *
     * @param string $wishlist_id Wishlist identifier.
     * @return array{sql:string,params:array<int, mixed>} Query fragment.
     */
    private function get_scope_where(string $wishlist_id): array
    {
        if ($this->user_id > 0) {
            return [
                'sql' => 'user_id = %d AND wishlist_id = %s',
                'params' => [$this->user_id, $wishlist_id],
            ];
        }

        return [
            'sql' => 'session_key = %s AND wishlist_id = %s',
            'params' => [$this->session_key, $wishlist_id],
        ];
    }

    /**
     * Normalize wishlist identifier.
     *
     * @param string|null $wishlist_id Wishlist identifier.
     * @return string Normalized wishlist id.
     */
    private function normalize_wishlist_id(?string $wishlist_id): string
    {
        $resolved = $wishlist_id ?: $this->active_wishlist_id ?: self::DEFAULT_WISHLIST_ID;
        $resolved = sanitize_title($resolved);

        if (empty($resolved)) {
            $resolved = self::DEFAULT_WISHLIST_ID;
        }

        return $resolved;
    }

    /**
     * Resolve the active wishlist id from user meta or default.
     *
     * @return string Active wishlist id.
     */
    private function resolve_active_wishlist_id(): string
    {
        if ($this->user_id > 0) {
            $saved = get_user_meta($this->user_id, 'king_addons_active_wishlist_id', true);
            if (!empty($saved)) {
                return $this->normalize_wishlist_id($saved);
            }
        }

        return self::DEFAULT_WISHLIST_ID;
    }

    /**
     * Build cache key per scope and wishlist.
     *
     * @param string $wishlist_id Wishlist identifier.
     * @return string Cache key.
     */
    private function get_cache_key(string $wishlist_id): string
    {
        $scope = $this->user_id > 0 ? 'user-' . $this->user_id : 'sess-' . $this->session_key;
        return 'king_addons_wishlist_count_' . $scope . '_' . $wishlist_id;
    }

    /**
     * Current UTC datetime string for DB writes.
     *
     * @return string Datetime in mysql format.
     */
    private function now(): string
    {
        $dt = new DateTime('now', new DateTimeZone('UTC'));
        return $dt->format('Y-m-d H:i:s');
    }
}



