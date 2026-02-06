<?php

namespace King_Addons\Widgets\Login_Register_Form;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class Widget_Settings_Resolver
{
    /**
     * Resolve Elementor widget settings for a given post + widget (element) id.
     *
     * @param int $post_id
     * @param string $widget_id
     * @return array<string,mixed>
     */
    public static function resolve($post_id, $widget_id)
    {
        $post_id = absint($post_id);
        $widget_id = sanitize_text_field($widget_id);

        if ($post_id <= 0 || $widget_id === '') {
            return [];
        }

        if (!class_exists('\\Elementor\\Plugin')) {
            return [];
        }

        try {
            $document = \Elementor\Plugin::$instance->documents->get($post_id);
            if (!$document) {
                return [];
            }

            $elements = $document->get_elements_data();
            if (!is_array($elements)) {
                return [];
            }

            $settings = self::find_settings_by_element_id($elements, $widget_id);
            return is_array($settings) ? $settings : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * @param array $elements
     * @param string $widget_id
     * @return array<string,mixed>|null
     */
    private static function find_settings_by_element_id($elements, $widget_id)
    {
        foreach ((array) $elements as $element) {
            if (!is_array($element)) {
                continue;
            }

            if (!empty($element['id']) && $element['id'] === $widget_id) {
                return isset($element['settings']) && is_array($element['settings']) ? $element['settings'] : [];
            }

            if (!empty($element['elements']) && is_array($element['elements'])) {
                $found = self::find_settings_by_element_id($element['elements'], $widget_id);
                if (is_array($found)) {
                    return $found;
                }
            }
        }

        return null;
    }
}
