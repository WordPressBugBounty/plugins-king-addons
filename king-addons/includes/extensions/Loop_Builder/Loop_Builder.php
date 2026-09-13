<?php
/**
 * Loop Builder.
 *
 * Lets a card be designed once in Elementor and repeated for every post a query
 * returns, which is what the Loop Grid widget does with it.
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Plugin as Elementor_Plugin;
use King_Addons\Loop_Builder\Document as Loop_Document;
use King_Addons\Loop_Builder\Renderer as Loop_Renderer;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registers the loop item document type and everything around it.
 */
class Loop_Builder
{
    /**
     * Admin page slug.
     */
    public const PAGE = 'king-addons-loop-builder';

    /**
     * Constructor.
     */
    public function __construct()
    {
        if (!did_action('elementor/loaded')) {
            return;
        }

        require_once KING_ADDONS_PATH . 'includes/helpers/Loop_Builder/Document.php';
        require_once KING_ADDONS_PATH . 'includes/helpers/Loop_Builder/Renderer.php';

        add_action('elementor/documents/register', [$this, 'register_document_type']);

        // The grid's load-more request never builds the page, so its handler
        // cannot live in the widget file: admin-ajax.php does not load Elementor
        // widgets.
        add_action('wp_ajax_ka_loop_grid', [$this, 'ajax_loop_grid']);
        add_action('wp_ajax_nopriv_ka_loop_grid', [$this, 'ajax_loop_grid']);

        add_action('admin_menu', [$this, 'register_admin_page'], 11);
        add_action('admin_post_king_addons_create_loop_template', [$this, 'handle_create_template']);

        // A loop item is edited on its own, with no page around it, so the
        // editor needs to know which post to preview it against.
        add_action('elementor/documents/register_controls', [$this, 'register_document_controls']);
        // Two hooks, because the editor renders a template two different ways:
        // the whole document on the first preview load, and one widget at a
        // time over AJAX afterwards.
        add_action('elementor/frontend/before_get_builder_content', [$this, 'maybe_setup_preview_post']);
        add_action('elementor/widget/before_render_content', [$this, 'maybe_setup_preview_post']);
    }

    /**
     * Register the loop item document type with Elementor.
     *
     * @param \Elementor\Core\Documents_Manager $documents_manager Manager.
     *
     * @return void
     */
    public function register_document_type($documents_manager): void
    {
        if (!class_exists(Loop_Document::class)) {
            return;
        }

        $documents_manager->register_document_type(Loop_Document::get_type(), Loop_Document::class);
    }

    /**
     * Post types a loop template can be built for.
     *
     * @return array<string,string>
     */
    public static function get_source_post_types(): array
    {
        $types = [];

        $skip = [
            'attachment',
            'elementor_library',
            'e-floating-buttons',
            'king-addons-el-hf',
            'king_addons_ext_pb',
            'king-addons-fb-sub',
        ];

        foreach (get_post_types(['public' => true], 'objects') as $slug => $type) {
            if (in_array($slug, $skip, true)) {
                continue;
            }

            $types[$slug] = $type->labels->name ?? $slug;
        }

        return $types;
    }

    /**
     * Add the Loop Builder page under the King Addons menu.
     *
     * @return void
     */
    public function register_admin_page(): void
    {
        add_submenu_page(
            'king-addons',
            esc_html__('Loop Builder', 'king-addons'),
            esc_html__('Loop Builder', 'king-addons'),
            'edit_pages',
            self::PAGE,
            [$this, 'render_admin_page']
        );
    }

    /**
     * The Loop Builder screen: existing templates plus a form to add one.
     *
     * @return void
     */
    public function render_admin_page(): void
    {
        if (!current_user_can('edit_pages')) {
            wp_die(esc_html__('You are not allowed to manage loop templates.', 'king-addons'));
        }

        require KING_ADDONS_PATH . 'includes/extensions/Loop_Builder/admin-page.php';
    }

    /**
     * Create a loop item template and send the user straight to the editor.
     *
     * @return void
     */
    public function handle_create_template(): void
    {
        if (!current_user_can('edit_pages')) {
            wp_die(esc_html__('You are not allowed to create loop templates.', 'king-addons'));
        }

        check_admin_referer('king_addons_create_loop_template');

        $title = isset($_POST['loop_title']) ? sanitize_text_field(wp_unslash($_POST['loop_title'])) : '';
        if ('' === $title) {
            $title = esc_html__('Loop item', 'king-addons');
        }

        $source = isset($_POST['loop_source']) ? sanitize_key(wp_unslash($_POST['loop_source'])) : 'post';
        $sources = self::get_source_post_types();
        if (!isset($sources[$source])) {
            $source = 'post';
        }

        $template_id = wp_insert_post([
            'post_title' => $title,
            'post_type' => 'elementor_library',
            'post_status' => 'publish',
        ]);

        if (is_wp_error($template_id) || !$template_id) {
            wp_safe_redirect(add_query_arg(['page' => self::PAGE, 'ka_error' => 'create'], admin_url('admin.php')));
            exit;
        }

        update_post_meta($template_id, '_elementor_template_type', Loop_Document::get_type());
        update_post_meta($template_id, '_elementor_edit_mode', 'builder');
        update_post_meta($template_id, Loop_Document::META_SOURCE, $source);

        wp_safe_redirect(add_query_arg(
            ['post' => $template_id, 'action' => 'elementor'],
            admin_url('post.php')
        ));
        exit;
    }

    /**
     * Add the preview-post picker to the loop item document settings.
     *
     * @param \Elementor\Core\Base\Document $document Document being edited.
     *
     * @return void
     */
    public function register_document_controls($document): void
    {
        if (!$document instanceof \Elementor\Core\Base\Document) {
            return;
        }

        if (Loop_Document::get_type() !== $document->get_name()) {
            return;
        }

        $source = (string) get_post_meta($document->get_main_id(), Loop_Document::META_SOURCE, true);
        if ('' === $source) {
            $source = 'post';
        }

        $options = [0 => esc_html__('- Latest -', 'king-addons')];
        foreach (get_posts([
            'post_type' => $source,
            'posts_per_page' => 25,
            'post_status' => 'publish',
        ]) as $post) {
            $options[(int) $post->ID] = $post->post_title !== '' ? $post->post_title : '#' . $post->ID;
        }

        $document->start_controls_section(
            'ka_loop_preview_section',
            [
                'label' => esc_html__('Loop Preview', 'king-addons'),
                'tab' => \Elementor\Controls_Manager::TAB_SETTINGS,
            ]
        );

        $document->add_control(
            'ka_loop_preview_id',
            [
                'label' => esc_html__('Preview with', 'king-addons'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $options,
                'default' => 0,
                'description' => esc_html__('Which entry the editor shows while you design the card.', 'king-addons'),
            ]
        );

        $document->end_controls_section();
    }

    /**
     * In the editor, put a real post behind the loop item being designed.
     *
     * Without this the card is built against the template post itself, so every
     * dynamic value reads "Loop item" instead of showing real content.
     *
     * @return void
     */
    public function maybe_setup_preview_post(): void
    {
        static $done = false;

        if ($done) {
            return;
        }

        $template_id = 0;

        // The id comes from the preview flag rather than get_the_ID(): by the
        // time an element renders the global post has been through the theme's
        // loop, and comparing against it made this check miss.
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Elementor's own preview flag.
        if (isset($_GET['elementor-preview'])) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $template_id = absint(wp_unslash($_GET['elementor-preview']));
        } elseif (Elementor_Plugin::$instance->editor->is_edit_mode()) {
            // A single widget re-rendered over AJAX: Elementor has already
            // switched to the document being edited.
            $document = Elementor_Plugin::$instance->documents->get_current();
            if ($document) {
                $template_id = (int) $document->get_main_id();
            }
        }

        if (!$template_id || Loop_Document::get_type() !== get_post_meta($template_id, '_elementor_template_type', true)) {
            return;
        }

        if (!current_user_can('edit_post', $template_id)) {
            return;
        }

        $done = true;

        $preview_id = (int) $this->get_preview_post_id($template_id);
        if (!$preview_id) {
            return;
        }

        $post = get_post($preview_id);
        if (!$post) {
            return;
        }

        $GLOBALS['post'] = $post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
        setup_postdata($post);

        if (function_exists('wc_get_product') && 'product' === $post->post_type) {
            $product = wc_get_product($preview_id);
            $GLOBALS['product'] = $product instanceof \WC_Product ? $product : null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
        }
    }

    /**
     * The post the editor should preview a template with.
     *
     * @param int $template_id Template id.
     *
     * @return int
     */
    private function get_preview_post_id(int $template_id): int
    {
        $document = Elementor_Plugin::$instance->documents->get($template_id);
        $chosen = 0;

        if ($document) {
            $chosen = (int) $document->get_settings('ka_loop_preview_id');
        }

        if ($chosen > 0 && get_post($chosen)) {
            return $chosen;
        }

        $source = (string) get_post_meta($template_id, Loop_Document::META_SOURCE, true);
        if ('' === $source) {
            $source = 'post';
        }

        $latest = get_posts([
            'post_type' => $source,
            'posts_per_page' => 1,
            'post_status' => 'publish',
            'fields' => 'ids',
        ]);

        return $latest ? (int) $latest[0] : 0;
    }

    /**
     * Load-more / paging for the Loop Grid widget.
     *
     * @return void
     */
    public function ajax_loop_grid(): void
    {
        require_once KING_ADDONS_PATH . 'includes/widgets/Loop_Grid/Loop_Grid.php';

        if (!class_exists('King_Addons\\Loop_Grid')) {
            wp_send_json_error(['message' => 'unavailable'], 500);
        }

        Loop_Grid::handle_ajax();
    }
}
