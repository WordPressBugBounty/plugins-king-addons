<?php
/**
 * Smart Links service layer.
 *
 * @package King_Addons
 */

namespace King_Addons\Smart_Links;

use WP_Post;
use WP_Query;

if (!defined('ABSPATH')) {
    exit;
}

class Smart_Links_Service
{
    public function get_link(int $link_id): ?WP_Post
    {
        $post = get_post($link_id);
        if (!$post || $post->post_type !== Smart_Links::POST_TYPE) {
            return null;
        }

        return $post;
    }

    public function get_link_by_slug(string $slug): ?WP_Post
    {
        $slug = $this->sanitize_slug($slug);
        if ($slug === '') {
            return null;
        }

        $query = new WP_Query([
            'post_type' => Smart_Links::POST_TYPE,
            'post_status' => 'any',
            'posts_per_page' => 1,
            'meta_key' => Smart_Links::META_SLUG,
            'meta_value' => $slug,
        ]);

        if (empty($query->posts)) {
            return null;
        }

        return $query->posts[0];
    }

    public function slug_exists(string $slug, int $exclude_id = 0): bool
    {
        $slug = $this->sanitize_slug($slug);
        if ($slug === '') {
            return false;
        }

        $args = [
            'post_type' => Smart_Links::POST_TYPE,
            'post_status' => 'any',
            'fields' => 'ids',
            'posts_per_page' => 1,
            'meta_key' => Smart_Links::META_SLUG,
            'meta_value' => $slug,
        ];

        if ($exclude_id > 0) {
            $args['post__not_in'] = [$exclude_id];
        }

        $query = new WP_Query($args);
        return !empty($query->posts);
    }

    public function sanitize_slug(string $slug): string
    {
        $slug = strtolower($slug);
        $slug = preg_replace('/[^a-z0-9\-_]+/', '-', $slug);
        $slug = trim((string) $slug, '-_');
        return $slug;
    }

    public function generate_slug(int $length = 6): string
    {
        $length = max(4, min(20, $length));
        $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $slug = '';

        for ($i = 0; $i < $length; $i++) {
            $slug .= $chars[wp_rand(0, strlen($chars) - 1)];
        }

        return $slug;
    }

    public function ensure_unique_slug(string $slug, int $exclude_id = 0): string
    {
        $slug = $this->sanitize_slug($slug);
        if ($slug === '') {
            $slug = $this->generate_slug();
        }

        if (!$this->slug_exists($slug, $exclude_id)) {
            return $slug;
        }

        $base = $slug;
        $suffix = 1;

        while ($suffix < 1000) {
            $candidate = $base . '-' . $suffix;
            if (!$this->slug_exists($candidate, $exclude_id)) {
                return $candidate;
            }
            $suffix++;
        }

        return $this->generate_slug();
    }

    public function build_short_url(string $slug): string
    {
        $base = trim(Smart_Links_Settings::get_base_path(), '/');
        $path = $base !== '' ? $base . '/' . $slug : $slug;
        return home_url(user_trailingslashit($path));
    }

    public function sanitize_destination_url(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        $validated = wp_http_validate_url($url);
        if (!$validated) {
            return '';
        }

        $parsed = wp_parse_url($validated);
        $scheme = $parsed['scheme'] ?? '';
        if (!in_array($scheme, ['http', 'https'], true)) {
            return '';
        }

        return $validated;
    }

    public function apply_utm(string $url, array $utm): string
    {
        if (empty($utm['enabled'])) {
            return $url;
        }

        $args = [];
        foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'] as $key) {
            if (!empty($utm[$key])) {
                $args[$key] = $utm[$key];
            }
        }

        if (empty($args)) {
            return $url;
        }

        return add_query_arg($args, $url);
    }

    public function merge_query_params(string $url, array $incoming, array $settings): string
    {
        if (empty($incoming)) {
            return $url;
        }

        $filtered = [];
        foreach ($incoming as $key => $value) {
            if (!is_scalar($value)) {
                continue;
            }
            if (strpos($key, 'kng_') === 0) {
                continue;
            }
            $filtered[$key] = sanitize_text_field((string) $value);
        }

        if (empty($filtered)) {
            return $url;
        }

        $whitelist = $this->parse_csv($settings['whitelist_query_params'] ?? '');
        $blacklist = $this->parse_csv($settings['blacklist_query_params'] ?? '');

        if (!empty($whitelist)) {
            $filtered = array_intersect_key($filtered, array_flip($whitelist));
        }

        if (!empty($blacklist)) {
            foreach ($blacklist as $blocked) {
                unset($filtered[$blocked]);
            }
        }

        if (empty($filtered)) {
            return $url;
        }

        return add_query_arg($filtered, $url);
    }

    public function parse_tags(string $tags): array
    {
        $parts = array_filter(array_map('trim', explode(',', $tags)));
        $clean = [];
        foreach ($parts as $tag) {
            $tag = sanitize_text_field($tag);
            if ($tag !== '') {
                $clean[] = $tag;
            }
        }

        return array_values(array_unique($clean));
    }

    public function parse_csv(string $value): array
    {
        $parts = array_filter(array_map('trim', explode(',', $value)));
        $clean = [];
        foreach ($parts as $part) {
            $part = sanitize_key($part);
            if ($part !== '') {
                $clean[] = $part;
            }
        }

        return array_values(array_unique($clean));
    }

    public function get_totals(int $days = 7): array
    {
        global $wpdb;

        $daily_table = Smart_Links_DB::get_daily_table();
        $date_from = wp_date('Y-m-d', current_time('timestamp') - (max(0, $days - 1) * DAY_IN_SECONDS));

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT SUM(clicks) AS clicks, SUM(unique_clicks) AS unique_clicks FROM {$daily_table} WHERE date >= %s",
                $date_from
            ),
            ARRAY_A
        );

        return [
            'clicks' => isset($row['clicks']) ? (int) $row['clicks'] : 0,
            'unique_clicks' => isset($row['unique_clicks']) ? (int) $row['unique_clicks'] : 0,
        ];
    }

    public function get_top_link(int $days = 7): ?array
    {
        global $wpdb;

        $daily_table = Smart_Links_DB::get_daily_table();
        $date_from = wp_date('Y-m-d', current_time('timestamp') - (max(0, $days - 1) * DAY_IN_SECONDS));

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT link_id, SUM(clicks) AS total FROM {$daily_table} WHERE date >= %s GROUP BY link_id ORDER BY total DESC LIMIT 1",
                $date_from
            ),
            ARRAY_A
        );

        if (!$row) {
            return null;
        }

        $link = $this->get_link((int) $row['link_id']);
        if (!$link) {
            return null;
        }

        return [
            'link' => $link,
            'clicks' => (int) $row['total'],
        ];
    }

    public function get_daily_stats(int $link_id = 0, string $date_from = '', string $date_to = ''): array
    {
        global $wpdb;

        $daily_table = Smart_Links_DB::get_daily_table();
        $where = [];
        $params = [];

        if ($link_id > 0) {
            $where[] = 'link_id = %d';
            $params[] = $link_id;
        }

        if ($date_from !== '') {
            $where[] = 'date >= %s';
            $params[] = $date_from;
        }

        if ($date_to !== '') {
            $where[] = 'date <= %s';
            $params[] = $date_to;
        }

        $where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $select = $link_id > 0
            ? 'date, clicks, unique_clicks'
            : 'date, SUM(clicks) AS clicks, SUM(unique_clicks) AS unique_clicks';
        $group = $link_id > 0 ? '' : 'GROUP BY date';

        $sql = "SELECT {$select} FROM {$daily_table} {$where_sql} {$group} ORDER BY date DESC";

        if (!empty($params)) {
            $prepared = $wpdb->prepare($sql, $params);
        } else {
            $prepared = $sql;
        }

        return $wpdb->get_results($prepared, ARRAY_A);
    }

    public function get_total_stats(int $link_id = 0, string $date_from = '', string $date_to = ''): array
    {
        global $wpdb;

        $daily_table = Smart_Links_DB::get_daily_table();
        $where = [];
        $params = [];

        if ($link_id > 0) {
            $where[] = 'link_id = %d';
            $params[] = $link_id;
        }

        if ($date_from !== '') {
            $where[] = 'date >= %s';
            $params[] = $date_from;
        }

        if ($date_to !== '') {
            $where[] = 'date <= %s';
            $params[] = $date_to;
        }

        $where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "SELECT SUM(clicks) AS clicks, SUM(unique_clicks) AS unique_clicks FROM {$daily_table} {$where_sql}";

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        $row = $wpdb->get_row($sql, ARRAY_A);

        return [
            'clicks' => isset($row['clicks']) ? (int) $row['clicks'] : 0,
            'unique_clicks' => isset($row['unique_clicks']) ? (int) $row['unique_clicks'] : 0,
        ];
    }

    public function get_top_links(int $limit = 10, string $date_from = '', string $date_to = ''): array
    {
        global $wpdb;

        $daily_table = Smart_Links_DB::get_daily_table();
        $where = [];
        $params = [];

        if ($date_from !== '') {
            $where[] = 'date >= %s';
            $params[] = $date_from;
        }

        if ($date_to !== '') {
            $where[] = 'date <= %s';
            $params[] = $date_to;
        }

        $where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "SELECT link_id, SUM(clicks) AS clicks, SUM(unique_clicks) AS unique_clicks FROM {$daily_table} {$where_sql} GROUP BY link_id ORDER BY clicks DESC LIMIT %d";
        $params[] = $limit;

        $prepared = $wpdb->prepare($sql, $params);
        $rows = $wpdb->get_results($prepared, ARRAY_A);

        $results = [];
        foreach ($rows as $row) {
            $link = $this->get_link((int) $row['link_id']);
            if (!$link) {
                continue;
            }
            $results[] = [
                'link' => $link,
                'clicks' => (int) $row['clicks'],
                'unique_clicks' => (int) $row['unique_clicks'],
            ];
        }

        return $results;
    }
}
