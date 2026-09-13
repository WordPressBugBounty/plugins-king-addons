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
 * The title WordPress would print for the current archive.
 */
class Archive_Title extends Text_Tag
{
    public function get_name()
    {
        return 'king-addons-archive-title';
    }

    public function get_title()
    {
        return esc_html__('Archive Title', 'king-addons');
    }

    protected function register_controls()
    {
        $this->add_control(
            'strip_prefix',
            [
                'label' => esc_html__('Remove prefix', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
                'description' => esc_html__('Drops "Category:", "Tag:" and the like.', 'king-addons'),
            ]
        );
    }

    public function render()
    {
        $strip = 'yes' === $this->get_settings('strip_prefix');

        // WordPress only wraps the title in a <span> when a prefix survives
        // this filter, so emptying it removes both prefix and wrapper.
        $filter = static function () {
            return '';
        };

        if ($strip) {
            add_filter('get_the_archive_title_prefix', $filter);
        }

        // Off an archive, get_the_archive_title() answers a flat "Archives",
        // which is worse than nothing: leaving it empty lets the tag's Fallback
        // take over on single posts and pages.
        if (!is_archive() && !is_search() && !is_404() && !is_home()) {
            $this->ka_echo('');
            return;
        }

        // get_the_archive_title() has no answer for search results or 404s,
        // and both have their own Theme Builder template.
        if (is_search()) {
            $title = sprintf(
                /* translators: %s: search term. */
                esc_html__('Search results for: %s', 'king-addons'),
                esc_html(get_search_query())
            );
        } elseif (is_404()) {
            $title = esc_html__('Page not found', 'king-addons');
        } elseif (is_home() && !is_front_page()) {
            $title = (string) get_the_title((int) get_option('page_for_posts'));
        } else {
            $title = (string) get_the_archive_title();
        }

        if ($strip) {
            remove_filter('get_the_archive_title_prefix', $filter);
        }

        $this->ka_echo($title);
    }
}
