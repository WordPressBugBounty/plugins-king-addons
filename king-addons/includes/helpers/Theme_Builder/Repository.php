<?php
/**
 * Theme Builder template repository.
 *
 * @package King_Addons
 */

namespace King_Addons\Theme_Builder;

use WP_Query;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Retrieves and caches Theme Builder templates.
 */
class Repository
{
    /**
     * Cache key for transient.
     *
     * @var string
     */
    private string $cache_key = 'king_addons_theme_builder_templates';

    /**
     * Retrieve active templates.
     *
     * @return array<int,array<string,mixed>>
     */
    public function get_active_templates(): array
    {
        $cached = get_transient($this->cache_key);
        if (is_array($cached)) {
            return $cached;
        }

        // Query for templates that have EITHER ENABLED OR LOCATION meta key.
        // This ensures templates created via Theme Builder are shown,
        // even if they were created before the ENABLED key was set or if they
        // have only the LOCATION key set.
        $query = new WP_Query([
            'post_type' => 'elementor_library',
            'post_status' => ['publish', 'draft', 'pending', 'future'],
            'posts_per_page' => -1,
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => Meta_Keys::ENABLED,
                    'compare' => 'EXISTS',
                ],
                [
                    'key' => Meta_Keys::LOCATION,
                    'compare' => 'EXISTS',
                ],
            ],
            'fields' => 'ids',
            'no_found_rows' => true,
        ]);

        $templates = [];

        foreach ($query->posts as $post_id) {
            $location = get_post_meta($post_id, Meta_Keys::LOCATION, true) ?: '';
            
            // Skip templates without location - they're not Theme Builder templates
            if (empty($location)) {
                continue;
            }
            
            $sub_location = get_post_meta($post_id, Meta_Keys::SUB_LOCATION, true) ?: '';
            $conditions_meta = get_post_meta($post_id, Meta_Keys::CONDITIONS, true);
            $conditions = Conditions::normalize($conditions_meta);
            $priority = (int) get_post_meta($post_id, Meta_Keys::PRIORITY, true);
            $is_pro_only = (bool) get_post_meta($post_id, Meta_Keys::IS_PRO_ONLY, true);
            
            // Check if ENABLED meta exists
            $enabled_meta = get_post_meta($post_id, Meta_Keys::ENABLED, true);
            // If ENABLED meta doesn't exist but LOCATION does, default to enabled
            // If ENABLED meta exists, use its value
            $enabled = ($enabled_meta === '') ? true : ((string) $enabled_meta === '1');

            $templates[] = [
                'id' => (int) $post_id,
                'location' => $location,
                'sub_location' => $sub_location,
                'conditions' => $conditions,
                'priority' => $priority ?: 10,
                'is_pro_only' => $is_pro_only || Conditions::is_pro_only($conditions),
                'enabled' => $enabled,
            ];
        }

        set_transient($this->cache_key, $templates, HOUR_IN_SECONDS);

        return $templates;
    }

    /**
     * Delete cached templates.
     *
     * @return void
     */
    public function clear_cache(): void
    {
        delete_transient($this->cache_key);
    }

    /**
     * Get templates that match request location.
     *
     * @param array<string,mixed> $context Request context.
     *
     * @return array<int,array<string,mixed>>
     */
    public function get_location_candidates(array $context): array
    {
        $templates = $this->get_active_templates();
        $candidates = [];
        foreach ($templates as $template) {
            if (empty($template['enabled'])) {
                continue;
            }
            if ($this->matches_location($template, $context)) {
                $candidates[] = $template;
            }
        }

        return $candidates;
    }

    /**
     * Check if template location matches context.
     *
     * @param array<string,mixed> $template Template data.
     * @param array<string,mixed> $context  Request context.
     *
     * @return bool
     */
    private function matches_location(array $template, array $context): bool
    {
        $location = $template['location'] ?? '';
        $sub_location = $template['sub_location'] ?? '';
        $type = $context['type'] ?? '';

        if ('not_found' === $type) {
            return 'not_found' === $sub_location || 'not_found' === $location;
        }

        if ('search' === $type) {
            return 'search_results' === $sub_location || 'search' === $location;
        }

        if ('author' === $type) {
            return in_array($sub_location, ['author_all', 'author_specific'], true);
        }

        if ('single' === $type) {
            $post_type = $context['post_type'] ?? '';
            if ('single' === $location) {
                if ('single_post' === $sub_location && 'post' === $post_type) {
                    return true;
                }
                if ('single_page' === $sub_location && 'page' === $post_type) {
                    return true;
                }
                if ('single_cpt' === $sub_location && !in_array($post_type, ['post', 'page'], true)) {
                    return true;
                }
                if (!empty($post_type) && ('single_' . $post_type) === $sub_location) {
                    return true;
                }
                return empty($sub_location);
            }
        }

        if ('archive' === $type) {
            $taxonomy = $context['taxonomy'] ?? '';
            $is_blog = !empty($context['is_blog_page']);

            if ('archive' === $location) {
                if ($is_blog && 'archive_blog' === $sub_location) {
                    return true;
                }
                if ('archive_category' === $sub_location && 'category' === $taxonomy) {
                    return true;
                }
                if ('archive_tag' === $sub_location && 'post_tag' === $taxonomy) {
                    return true;
                }
                if ('archive_tax' === $sub_location && !empty($taxonomy)) {
                    return true;
                }
                if (!empty($taxonomy) && ('archive_' . $taxonomy) === $sub_location) {
                    return true;
                }
                return empty($sub_location);
            }
        }

        return false;
    }
}




