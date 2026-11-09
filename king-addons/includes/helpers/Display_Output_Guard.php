<?php
namespace King_Addons;

use Elementor\Element_Base;

if (!defined('ABSPATH')) { exit; }

/** Discard denied markup, including child elements, while retaining its fallback. */
class Display_Output_Guard
{
    private static array $buffers = [];
    private static bool $registered = false;

    public static function suppress(Element_Base $element): void
    {
        if (!self::$registered) {
            add_action('elementor/frontend/after_render', [self::class, 'finish'], PHP_INT_MAX);
            self::$registered = true;
        }
        $id = spl_object_id($element);
        if (isset(self::$buffers[$id])) { return; }
        ob_start(static function ($markup) { return ''; });
        self::$buffers[$id] = ob_get_level();
    }

    public static function finish(Element_Base $element): void
    {
        $id = spl_object_id($element);
        if (!isset(self::$buffers[$id])) { return; }
        $level = self::$buffers[$id];
        unset(self::$buffers[$id]);
        while (ob_get_level() >= $level) { ob_end_clean(); }
    }
}
