<?php
/**
 * Renders a Loop Builder item template once per post.
 *
 * @package King_Addons
 */

namespace King_Addons\Loop_Builder;

use Elementor\Plugin as Elementor_Plugin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Puts one post into context and renders the chosen item template for it.
 */
class Renderer
{
    /**
     * Templates whose CSS has already been printed on this request.
     *
     * @var array<int,bool>
     */
    private static $css_printed = [];

    /**
     * How many renders deep the element cache has been suspended.
     *
     * A card can hold a grid of a second template, so this has to be a depth
     * count: a plain flag would let the inner render switch the cache back on
     * while the outer one is still going.
     *
     * @var int
     */
    private static $cache_depth = 0;

    /**
     * Templates currently being rendered, to stop a loop inside itself.
     *
     * @var array<int,bool>
     */
    private static $rendering = [];

    /**
     * All published loop item templates, as id => title.
     *
     * @return array<int,string>
     */
    public static function get_templates(): array
    {
        $posts = get_posts([
            'post_type' => 'elementor_library',
            'post_status' => 'publish',
            'posts_per_page' => 100,
            'orderby' => 'title',
            'order' => 'ASC',
            'meta_key' => '_elementor_template_type',
            'meta_value' => Document::get_type(),
            'suppress_filters' => false,
        ]);

        $templates = [];
        foreach ($posts as $post) {
            $templates[(int) $post->ID] = $post->post_title !== ''
                ? $post->post_title
                /* translators: %d: template id. */
                : sprintf(esc_html__('Loop item #%d', 'king-addons'), (int) $post->ID);
        }

        return $templates;
    }

    /**
     * Whether the given id is a usable loop item template.
     *
     * @param int $template_id Template post id.
     *
     * @return bool
     */
    public static function is_template(int $template_id): bool
    {
        if ($template_id < 1) {
            return false;
        }

        $post = get_post($template_id);
        if (!$post || 'elementor_library' !== $post->post_type) {
            return false;
        }

        return Document::get_type() === get_post_meta($template_id, '_elementor_template_type', true);
    }

    /**
     * Render one template for one post.
     *
     * @param int $template_id Loop item template id.
     * @param int $post_id     Post to render it for.
     *
     * @return string
     */
    public static function render(int $template_id, int $post_id): string
    {
        if (!class_exists('Elementor\\Plugin') || !self::is_template($template_id)) {
            return '';
        }

        $post = get_post($post_id);
        if (!$post) {
            return '';
        }

        // A card holding a Loop Grid that points back at its own template would
        // recurse until the request runs out of memory.
        if (!empty(self::$rendering[$template_id])) {
            return '';
        }

        self::$rendering[$template_id] = true;

        self::suspend_element_cache();

        // Elementor's frontend uses the global post; WooCommerce widgets read
        // the global $product. Both are restored below.
        global $wp_query;

        $previous_post = $GLOBALS['post'] ?? null;
        $previous_product = $GLOBALS['product'] ?? null;
        $previous_in_the_loop = isset($wp_query) ? $wp_query->in_the_loop : null;

        $GLOBALS['post'] = $post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
        setup_postdata($post);

        if (isset($wp_query)) {
            // Widgets and themes gate "current post" behaviour on this flag.
            $wp_query->in_the_loop = true;
        }

        if (function_exists('wc_get_product') && 'product' === $post->post_type) {
            $product = wc_get_product($post_id);
            $GLOBALS['product'] = $product instanceof \WC_Product ? $product : null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
        }

        // The template's CSS is the same for every card, so it is printed with
        // the first one only.
        $with_css = empty(self::$css_printed[$template_id]);
        self::$css_printed[$template_id] = true;

        $html = Elementor_Plugin::$instance->frontend->get_builder_content_for_display($template_id, $with_css);

        if (isset($wp_query) && null !== $previous_in_the_loop) {
            $wp_query->in_the_loop = $previous_in_the_loop;
        }

        wp_reset_postdata();
        $GLOBALS['post'] = $previous_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
        $GLOBALS['product'] = $previous_product; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

        self::resume_element_cache();

        unset(self::$rendering[$template_id]);

        return (string) $html;
    }

    /**
     * Turn Elementor's element cache off for the duration of a loop.
     *
     * Without this the first card's HTML is stored against the template and
     * replayed for every following post, so a ten-post grid shows the same post
     * ten times.
     *
     * @return void
     */
    private static function suspend_element_cache(): void
    {
        self::$cache_depth++;

        if (self::$cache_depth > 1) {
            return;
        }

        add_filter('pre_option_elementor_element_cache_ttl', [self::class, 'return_disable']);
    }

    /**
     * Restore the element cache.
     *
     * @return void
     */
    private static function resume_element_cache(): void
    {
        if (self::$cache_depth < 1) {
            return;
        }

        self::$cache_depth--;

        if (0 === self::$cache_depth) {
            remove_filter('pre_option_elementor_element_cache_ttl', [self::class, 'return_disable']);
        }
    }

    /**
     * Filter callback: report the element cache as switched off.
     *
     * @return string
     */
    public static function return_disable(): string
    {
        return 'disable';
    }

    /**
     * Forget which templates printed their CSS.
     *
     * Used by the AJAX handlers, where each request is a fresh page as far as
     * the browser is concerned and the styles have to come along again.
     *
     * @return void
     */
    public static function reset_css_state(): void
    {
        self::$css_printed = [];
    }
}
