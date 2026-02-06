<?php
/**
 * Theme Builder meta key definitions.
 *
 * @package King_Addons
 */

namespace King_Addons\Theme_Builder;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Holds meta key names used by the Theme Builder.
 */
class Meta_Keys
{
    public const ENABLED = '_ka_theme_builder_enabled';
    public const LOCATION = '_ka_theme_builder_location';
    public const SUB_LOCATION = '_ka_theme_builder_sub_location';
    public const CONDITIONS = '_ka_theme_builder_conditions';
    public const PRIORITY = '_ka_theme_builder_priority';
    public const IS_PRO_ONLY = '_ka_theme_builder_is_pro_only';
    public const PREVIEW_POST_ID = '_ka_theme_builder_preview_post_id';
    public const PREVIEW_TERM_ID = '_ka_theme_builder_preview_term_id';
    public const PREVIEW_AUTHOR_ID = '_ka_theme_builder_preview_author_id';
    public const PREVIEW_QUERY = '_ka_theme_builder_preview_query';
}




