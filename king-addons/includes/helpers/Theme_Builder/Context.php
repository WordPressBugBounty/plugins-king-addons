<?php
/**
 * Theme Builder request context utilities.
 *
 * @package King_Addons
 */

namespace King_Addons\Theme_Builder;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Builds normalized context information for template resolution.
 */
class Context
{
    /**
     * Build context data for the current request.
     *
     * @return array<string,mixed>
     */
    public static function from_request(): array
    {
        $post_id = get_queried_object_id() ? (int) get_queried_object_id() : null;
        $post_type = $post_id ? get_post_type($post_id) : null;
        $author_id = is_author() ? (int) get_query_var('author') : null;
        $is_front_page = is_front_page();
        // is_home() returns true for the blog posts page (whether it's also the front or not)
        $is_blog_page = is_home();
        $term_id = null;
        $taxonomy = null;
        $location = 'unknown';
        $type = 'single';

        if (is_category() || is_tag() || is_tax()) {
            $term = get_queried_object();
            if ($term && isset($term->term_id, $term->taxonomy)) {
                $term_id = (int) $term->term_id;
                $taxonomy = $term->taxonomy;
            }
        }

        if (is_404()) {
            $type = 'not_found';
            $location = 'not_found';
        } elseif (is_search()) {
            $type = 'search';
            $location = 'search_results';
        } elseif (is_author()) {
            $type = 'author';
            $location = 'author_all';
        } elseif ($is_blog_page && !is_singular()) {
            // Blog posts page (archive of posts) - must check before is_singular
            // because is_home() can be true on static front page setups
            $type = 'archive';
            $location = 'archive_blog';
        } elseif (is_singular()) {
            $type = 'single';
            if ('post' === $post_type) {
                $location = 'single_post';
            } elseif ('page' === $post_type) {
                $location = 'single_page';
            } elseif (!empty($post_type)) {
                $location = 'single_' . $post_type;
            } else {
                $location = 'single';
            }
        } elseif (is_archive()) {
            $type = 'archive';
            if (is_category()) {
                $location = 'archive_category';
            } elseif (is_tag()) {
                $location = 'archive_tag';
            } elseif (is_tax() && $taxonomy) {
                $location = 'archive_' . $taxonomy;
            } else {
                $location = 'archive';
            }
        }

        return [
            'type' => $type,
            'location' => $location,
            'post_id' => $post_id,
            'post_type' => $post_type,
            'author_id' => $author_id,
            'term_id' => $term_id,
            'taxonomy' => $taxonomy,
            'is_front_page' => $is_front_page,
            'is_blog_page' => $is_blog_page,
        ];
    }
}




