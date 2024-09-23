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
}

new Ajax_Select2_API();