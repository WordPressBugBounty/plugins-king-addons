<?php

namespace King_Addons;

if (!defined('ABSPATH')) {
    exit;
}
require_once __DIR__ . '/Display_Access_Plan.php';

/** Keeps conditional elements dynamic without disabling other element caches. */
class Conditional_Content_Cache
{
    private static array $setting_keys = [];

    public static function register(string $setting_key): void
    {
        if (empty(self::$setting_keys)) {
            add_filter('elementor/frontend/builder_content_data', [self::class, 'prepare_document'], 10, 2);
            add_filter('elementor/element/is_dynamic_content', [self::class, 'is_dynamic'], 10, 2);
        }
        self::$setting_keys[$setting_key] = true;
    }

    public static function is_dynamic(bool $dynamic, array $data): bool
    {
        if ($dynamic || self::has_condition($data['settings'] ?? [])) {
            return true;
        }
        foreach ($data['elements'] ?? [] as $child) {
            if (is_array($child) && self::is_dynamic(false, $child)) {
                return true;
            }
        }
        return false;
    }

    public static function prepare_document(array $data, int $post_id): array
    {
        $has_conditions = false;
        foreach ($data as &$element) {
            if (is_array($element) && self::mark_dynamic($element)) {
                $has_conditions = true;
            }
        }
        unset($element);

        if ($has_conditions && $post_id > 0) {
            $keys = array_keys(self::$setting_keys);
            sort($keys);
            $revision = '5:' . (Display_Access_Plan::has_pro() ? 'pro:' : 'free:') . implode(',', $keys);
            // Replace legacy cached markup once, only for affected documents.
            if (get_post_meta($post_id, '_king_addons_display_cache_revision', true) !== $revision) {
                delete_post_meta($post_id, '_elementor_element_cache');
                update_post_meta($post_id, '_king_addons_display_cache_revision', $revision);
            }
        }
        return $data;
    }

    private static function mark_dynamic(array &$element): bool
    {
        $dynamic = self::has_condition($element['settings'] ?? []);
        if (!empty($element['elements']) && is_array($element['elements'])) {
            foreach ($element['elements'] as &$child) {
                if (is_array($child) && self::mark_dynamic($child)) {
                    $dynamic = true;
                }
            }
            unset($child);
        }
        if ($dynamic) {
            // Runtime data only: saved Cache Settings and static siblings stay intact.
            $element['settings']['_element_cache'] = 'yes';
        }
        return $dynamic;
    }

    private static function has_condition(array $settings): bool
    {
        if (isset(self::$setting_keys['cd_enable']) && ($settings['cd_hide_empty'] ?? '') === 'yes') { return true; }
        foreach (self::$setting_keys as $key => $unused) {
            if (($settings[$key] ?? '') === 'yes') {
                return true;
            }
        }
        return false;
    }
}
