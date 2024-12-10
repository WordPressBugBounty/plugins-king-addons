<?php /** @noinspection PhpUnused, SpellCheckingInspection, DuplicatedCode */

namespace King_Addons\AJAX_Select2;

use WP_Query;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class Ajax_Select2_API
{
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
                    'permission_callback' => '__return_true'
                ]
            );
        });
    }

    public function callback($request)
    {
        return $this->{$request['action']}($request);
    }

    public function getElementorTemplates($request): ?array
    {
        if (!current_user_can('edit_posts')) {
            return null;
        }

        $args = [
            'post_type' => 'elementor_library',
            'post_status' => 'publish',
            'meta_key' => '_elementor_template_type',
            'meta_value' => ['page', 'section', 'container'],
            'numberposts' => 10
        ];

        if (isset($request['s'])) {
            $args['s'] = $request['s'];
        }

        $options = [];
        $the_query = new WP_Query($args);

        if ($the_query->have_posts()) {
            while ($the_query->have_posts()) {
                $the_query->the_post();
                $options[] = [
                    'id' => get_the_ID(),
                    'text' => html_entity_decode(get_the_title()),
                ];
            }
        }

        wp_reset_postdata();

        return ['results' => $options];
    }

    public function getPostsByPostType($request): ?array
    {
        if (!current_user_can('edit_posts')) return null;

        $post_type = $request['query_slug'] ?? '';

        $args = [
            'post_type' => $post_type,
            'post_status' => $post_type === 'attachment' ? 'any' : 'publish',
            'posts_per_page' => 15,
        ];

        if (isset($request['ids'])) {
            $args['post__in'] = explode(',', $request['ids']);
        }

        if (isset($request['s'])) {
            $args['s'] = $request['s'];
        }

        $query = new WP_Query($args);
        $options = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $options[] = [
                    'id' => get_the_ID(),
                    'text' => html_entity_decode(get_the_title()),
                ];
            }
        }

        wp_reset_postdata();
        return ['results' => $options];
    }

    public function getPostTypeTaxonomies($request): ?array
    {
        if (!current_user_can('edit_posts')) return null;

        $post_type = $request['query_slug'] ?? '';

        $taxonomies = get_object_taxonomies($post_type, 'objects');
        $options = [];

        if ($taxonomies) {
            foreach ($taxonomies as $taxonomy) {
                // Optionally filter by search term if provided.
                if (isset($request['s']) && stripos($taxonomy->label, $request['s']) === false) {
                    continue;
                }

                // Optionally include specific taxonomy IDs if provided.
                if (isset($request['ids'])) {
                    $ids = explode(',', $request['ids'] ?: '99999999');
                    if (!in_array($taxonomy->name, $ids)) {
                        continue;
                    }
                }

                $options[] = [
                    'id' => $taxonomy->name,
                    'text' => $taxonomy->label,
                ];
            }
        }

        return ['results' => $options];
    }
}

new Ajax_Select2_API();