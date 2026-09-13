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
 * A value taken from the request query string.
 */
class Request_Parameter extends Text_Tag
{
    public function get_name()
    {
        return 'king-addons-request-parameter';
    }

    public function get_title()
    {
        return $this->ka_title_with_pro(esc_html__('Request Parameter', 'king-addons'));
    }

    protected function register_controls()
    {
        $this->ka_register_pro_notice();

        $this->add_control(
            'param_type',
            [
                'label' => esc_html__('Source', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'get' => esc_html__('Query string (GET)', 'king-addons'),
                    'query_var' => esc_html__('Query variable', 'king-addons'),
                ],
                'default' => 'get',
            ]
        );

        $this->add_control(
            'param_name',
            [
                'label' => esc_html__('Parameter', 'king-addons'),
                'type' => Controls_Manager::TEXT,
            ]
        );
    }

    public function render()
    {
        if ($this->ka_pro_locked()) {
            return;
        }

        $name = (string) $this->get_settings('param_name');
        $name = preg_replace('/[^A-Za-z0-9_\-]/', '', $name);
        if ('' === $name) {
            $this->ka_echo('');
            return;
        }

        $raw = '';
        if ('query_var' === $this->get_settings('param_type')) {
            $raw = get_query_var($name, '');
        } elseif (isset($_GET[$name])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display value.
            $raw = wp_unslash($_GET[$name]); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        }

        if (is_array($raw)) {
            $raw = implode(', ', array_map('strval', $raw));
        }

        // This value comes straight from the visitor's URL. It is printed on a
        // page, so it is stripped to plain text before anything else - never
        // pass it through as markup.
        $this->ka_echo(sanitize_text_field((string) $raw));
    }
}
