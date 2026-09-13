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
 * A URL on this site.
 */
class Site_URL extends Url_Tag
{
    public function get_name()
    {
        return 'king-addons-site-url';
    }

    public function get_title()
    {
        return esc_html__('Site URL', 'king-addons');
    }

    protected function register_controls()
    {
        $this->add_control(
            'url_type',
            [
                'label' => esc_html__('URL', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'home' => esc_html__('Home', 'king-addons'),
                    'blog' => esc_html__('Posts page', 'king-addons'),
                    'login' => esc_html__('Log in', 'king-addons'),
                    'logout' => esc_html__('Log out', 'king-addons'),
                    'register' => esc_html__('Register', 'king-addons'),
                    'search' => esc_html__('Search results', 'king-addons'),
                    'custom' => esc_html__('Custom path', 'king-addons'),
                ],
                'default' => 'home',
            ]
        );

        $this->add_control(
            'custom_path',
            [
                'label' => esc_html__('Path', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => '/contact/',
                'condition' => ['url_type' => 'custom'],
            ]
        );

        $this->add_control(
            'redirect_back',
            [
                'label' => esc_html__('Return to current page', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'condition' => ['url_type' => ['login', 'logout']],
            ]
        );

        $this->add_control(
            'search_term',
            [
                'label' => esc_html__('Search for', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'condition' => ['url_type' => 'search'],
            ]
        );
    }

    public function get_value(array $options = [])
    {
        $type = (string) $this->get_settings('url_type');

        $redirect = '';
        if ('yes' === $this->get_settings('redirect_back')) {
            $request = isset($_SERVER['REQUEST_URI'])
                ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']))
                : '';
            // home_url() keeps the redirect on this site whatever the request
            // path happens to contain.
            $redirect = home_url($request);
        }

        switch ($type) {
            case 'blog':
                $page_for_posts = (int) get_option('page_for_posts');
                return $page_for_posts ? (string) get_permalink($page_for_posts) : home_url('/');
            case 'login':
                return wp_login_url($redirect);
            case 'logout':
                return wp_logout_url($redirect);
            case 'register':
                return wp_registration_url();
            case 'search':
                $term = (string) $this->get_settings('search_term');
                return '' === trim($term) ? home_url('/?s=') : (string) get_search_link($term);
            case 'custom':
                return home_url((string) $this->get_settings('custom_path'));
            case 'home':
            default:
                return home_url('/');
        }
    }
}
