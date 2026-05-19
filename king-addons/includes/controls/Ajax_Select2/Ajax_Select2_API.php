<?php /** @noinspection PhpUnused, SpellCheckingInspection, DuplicatedCode */

namespace King_Addons\AJAX_Select2;

use King_Addons\Core;
use WP_Query;
use WP_User_Query;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class Ajax_Select2_API
{
    private const ALLOWED_ACTIONS = [
        'getElementorTemplates',
        'getPostsByPostType',
        'getPostTypeTaxonomies',
        'getCustomMetaKeys',
        'getUsers',
        'getTaxonomies',
        'getCustomMetaKeysProduct',
    ];

    public function __construct()
    {
        $this->init();
    }

    public function init(): void
    {
        add_action('rest_api_init', function () {
            register_rest_route(
                'kingaddons/v1/ajaxselect2',
                '/(?P<action>\w+)/',
                [
                    'methods' => 'GET',
                    'callback' => [$this, 'callback'],
                    'permission_callback' => [$this, 'canAccess'],
                ]
            );
        });
    }

    public function canAccess($request): bool
    {
        $action = sanitize_key((string)($request['action'] ?? ''));
        $allowed_actions = array_map('sanitize_key', self::ALLOWED_ACTIONS);

        return current_user_can('edit_posts') && in_array($action, $allowed_actions, true);
    }

    public function callback($request)
    {
        $action = (string)($request['action'] ?? '');

        if (!in_array($action, self::ALLOWED_ACTIONS, true) || !is_callable([$this, $action])) {
            return new \WP_Error('king_addons_invalid_ajaxselect2_action', esc_html__('Invalid request.', 'king-addons'), ['status' => 400]);
        }

        return $this->{$action}($request);
    }

    public function getElementorTemplates($request): ?array
    {
        if (!current_user_can('edit_posts')) return null;

        $args = [
            'post_type' => 'elementor_library',
            'post_status' => 'publish',
            'meta_key' => '_elementor_template_type',
            'meta_value' => ['page', 'section', 'container'],
            'numberposts' => 10
        ];

        // Load specific templates by IDs (for pre-populating selected values)
        if (isset($request['ids']) && !empty($request['ids'])) {
            $ids = array_filter(array_map('intval', explode(',', $request['ids'])));
            if (!empty($ids)) {
                $args['post__in'] = $ids;
                $args['numberposts'] = -1;
                unset($args['meta_key'], $args['meta_value']); // Allow any template type when loading by ID
            }
        }

        if (isset($request['s'])) {
            $args['s'] = sanitize_text_field((string)$request['s']);
        }

        $options = [];
        $the_query = new WP_Query($args);

        if ($the_query->have_posts()) {
            while ($the_query->have_posts()) {
                $the_query->the_post();
                $options[] = [
                    'id' => get_the_ID(),
                    'text' => wp_strip_all_tags(html_entity_decode(get_the_title())),
                ];
            }
        }

        wp_reset_postdata();

        return ['results' => $options];
    }

    public function getPostsByPostType($request): ?array
    {
        if (!current_user_can('edit_posts')) return null;

        $post_type = sanitize_key((string)($request['query_slug'] ?? ''));

        $args = [
            'post_type' => $post_type,
            'post_status' => $post_type === 'attachment' ? 'any' : 'publish',
            'posts_per_page' => 15,
        ];

        if (isset($request['ids'])) {
            $args['post__in'] = array_filter(array_map('intval', explode(',', (string)$request['ids'])));
        }

        if (isset($request['s'])) {
            $args['s'] = sanitize_text_field((string)$request['s']);
        }

        $query = new WP_Query($args);
        $options = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $options[] = [
                    'id' => get_the_ID(),
                    'text' => wp_strip_all_tags(html_entity_decode(get_the_title())),
                ];
            }
        }

        wp_reset_postdata();
        return ['results' => $options];
    }

    public function getPostTypeTaxonomies($request): ?array
    {
        if (!current_user_can('edit_posts')) return null;

        $post_type = sanitize_key((string)($request['query_slug'] ?? ''));

        $taxonomies = get_object_taxonomies($post_type, 'objects');
        $options = [];

        if ($taxonomies) {
            foreach ($taxonomies as $taxonomy) {

                if (isset($request['s']) && stripos($taxonomy->label, sanitize_text_field((string)$request['s'])) === false) {
                    continue;
                }

                if (isset($request['ids'])) {
                    $ids = array_map('sanitize_key', explode(',', (string)($request['ids'] ?: '99999999')));
                    if (!in_array($taxonomy->name, $ids)) {
                        continue;
                    }
                }

                $options[] = [
                    'id' => $taxonomy->name,
                    'text' => wp_strip_all_tags($taxonomy->label),
                ];
            }
        }

        return ['results' => $options];
    }

    public function getCustomMetaKeys($request)
    {
        if (!current_user_can('edit_posts')) return null;

        $post_types = Core::getCustomTypes('post', false);
        $data = [];

        foreach ($post_types as $slug => $name) {
            $posts = get_posts(['post_type' => $slug, 'posts_per_page' => -1]);
            $metaKeys = [];

            foreach ($posts as $post) {
                $keys = get_post_custom_keys($post->ID) ?: [];
                $keys = array_filter($keys, fn($k) => $k[0] !== '_');
                $metaKeys = array_merge($metaKeys, $keys);
            }

            $data[$slug] = array_unique($metaKeys);
        }

        $mergedKeys = array_values(
            array_unique(
                array_merge([], ...array_values($data))
            )
        );

        $filtered = array_filter($mergedKeys, function ($key) use ($request) {
            return !isset($request['s']) || strpos($key, sanitize_text_field((string)$request['s'])) !== false;
        });

        $options = array_map(fn($k) => ['id' => $k, 'text' => wp_strip_all_tags($k)], $filtered);

        return ['results' => $options];
    }

    public function getUsers($request)
    {
        if (!current_user_can('edit_posts')) return null;

        $args = [
            'number' => 15,
            'blog_id' => 0,
        ];

        if (!empty($request['ids'])) {
            $args['include'] = array_map('intval', explode(',', $request['ids']));
        }

        if (!empty($request['s'])) {
            $args['search'] = '*' . sanitize_text_field((string)$request['s']) . '*';
        }

        $results = (new WP_User_Query($args))->get_results();

        $options = array_map(
            fn($user) => ['id' => $user->ID, 'text' => wp_strip_all_tags($user->display_name)],
            $results ?: []
        );

        wp_reset_postdata();

        return ['results' => $options];
    }

    public function getTaxonomies($request)
    {
        if (!current_user_can('edit_posts')) return null;

        $tax = sanitize_key((string)($request['query_slug'] ?? ''));
        $args = [
            'orderby' => 'name',
            'order' => 'DESC',
            'hide_empty' => true,
            'number' => 10,
        ];

        if (isset($request['ids'])) {
            $args['include'] = array_filter(array_map('intval', explode(',', (string)($request['ids'] ?: '99999999'))));
        }

        if (!empty($request['s'])) {
            $args['name__like'] = sanitize_text_field((string)$request['s']);
        }

        $terms = get_terms($tax, $args);
        if (is_wp_error($terms)) {
            return ['results' => []];
        }

        $options = array_map(function ($term) {
            return [
                'id' => $term->term_id,
                'text' => wp_strip_all_tags($term->name),
            ];
        }, $terms);

        wp_reset_postdata();

        return ['results' => $options];
    }

    public function getCustomMetaKeysProduct($request)
    {
        if (!current_user_can('edit_posts')) return null;

        $options = [];
        $merged_meta_keys = [];
        $post_types = Core::getCustomTypes('post', false);

        foreach ($post_types as $slug => $name) {
            $posts = get_posts(['post_type' => $slug, 'posts_per_page' => -1]);
            foreach ($posts as $post) {
                $meta_keys = get_post_custom_keys($post->ID);
                if ($meta_keys) {
                    foreach ($meta_keys as $key) {
                        if ('_' !== substr($key, 0, 1)) {
                            $merged_meta_keys[] = $key;
                        }
                    }
                }
            }
        }

        $merged_meta_keys = array_values(array_unique($merged_meta_keys));
        foreach ($merged_meta_keys as $key) {
            if (empty($request['s']) || false !== strpos($key, sanitize_text_field((string)$request['s']))) {
                $options[] = [
                    'id' => $key,
                    'text' => wp_strip_all_tags($key),
                ];
            }
        }

        $product_attributes = [];
        $products_query = new WP_Query([
            'post_type' => 'product',
            'posts_per_page' => -1,
        ]);

        if ($products_query->have_posts()) {
            while ($products_query->have_posts()) {
                $products_query->the_post();

                if (class_exists('WooCommerce')) {
                    if (function_exists('wc_get_product')) {
                        /** @noinspection PhpUndefinedFunctionInspection */

                        $product = wc_get_product(get_the_ID());

                        foreach ($product->get_attributes() as $attribute) {
                            $product_attributes[$attribute->get_name()] = true;
                        }

                    }
                }

            }
            wp_reset_postdata();
        }

        foreach (array_keys($product_attributes) as $attribute_name) {
            $options[] = [
                'id' => $attribute_name,
                'text' => wp_strip_all_tags($attribute_name),
            ];
        }

        return [
            'results' => $options,
        ];
    }

}

new Ajax_Select2_API();