<?php
/**
 * Theme Builder feature bootstrap.
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Plugin as Elementor_Plugin;
use King_Addons\Theme_Builder\Admin_List_Table;
use King_Addons\Theme_Builder\Conditions;
use King_Addons\Theme_Builder\Context;
use King_Addons\Theme_Builder\Meta_Keys;
use King_Addons\Theme_Builder\Repository;
use King_Addons\Theme_Builder\Resolver;
use King_Addons\Woo_Builder\Context as Woo_Context;
use WP_Post;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registers Theme Builder feature and integrates with WordPress and Elementor.
 */
class Theme_Builder
{
    /**
     * Admin menu slug.
     *
     * @var string
     */
    private string $menu_slug = 'king-addons-theme-builder';

    /**
     * Repository instance.
     *
     * @var Repository
     */
    private Repository $repository;

    /**
     * Resolver instance.
     *
     * @var Resolver
     */
    private Resolver $resolver;

    /**
     * Resolved template ID for current request.
     *
     * @var int|null
     */
    private ?int $current_template_id = null;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->require_helpers();

        $this->repository = new Repository();
        $this->resolver = new Resolver($this->repository);

        // Use priority 15 to ensure the parent menu exists before adding this submenu
        add_action('admin_menu', [$this, 'register_admin_menu'], 15);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('add_meta_boxes', [$this, 'register_meta_boxes']);
        add_action('save_post', [$this, 'handle_save_post'], 10, 2);
        add_action('delete_post', [$this, 'handle_delete_post']);
        add_action('transition_post_status', [$this, 'handle_status_transition'], 10, 3);
        add_action('admin_post_ka_theme_builder_create', [$this, 'handle_create_template']);
        add_action('admin_post_ka_theme_builder_quick_update', [$this, 'handle_quick_update']);
        add_action('admin_post_ka_theme_builder_duplicate', [$this, 'handle_duplicate_template']);
        add_action('elementor/preview/enqueue_scripts', [$this, 'enqueue_preview_handler']);
        add_action('elementor/documents/register_controls', [$this, 'register_document_controls']);
        add_action('elementor/document/after_save', [$this, 'handle_document_after_save'], 10, 2);
        add_filter('template_include', [$this, 'handle_template_include'], 99);
        add_filter('king_addons/theme_builder/current_template_id', [$this, 'filter_current_template_id']);
        add_filter('body_class', [$this, 'filter_body_class']);
    }

    /**
     * Require helper files for Theme Builder.
     *
     * @return void
     */
    private function require_helpers(): void
    {
        require_once KING_ADDONS_PATH . 'includes/helpers/Theme_Builder/Meta_Keys.php';
        require_once KING_ADDONS_PATH . 'includes/helpers/Theme_Builder/Context.php';
        require_once KING_ADDONS_PATH . 'includes/helpers/Theme_Builder/Conditions.php';
        require_once KING_ADDONS_PATH . 'includes/helpers/Theme_Builder/Repository.php';
        require_once KING_ADDONS_PATH . 'includes/helpers/Theme_Builder/Resolver.php';
        require_once KING_ADDONS_PATH . 'includes/helpers/Theme_Builder/Admin_List_Table.php';
    }

    /**
     * Register admin menu entry.
     *
     * @return void
     */
    public function register_admin_menu(): void
    {
        add_menu_page(
            esc_html__('Theme Builder', 'king-addons'),
            esc_html__('Theme Builder', 'king-addons'),
            'manage_options',
            $this->menu_slug,
            [$this, 'render_admin_page'],
            'dashicons-layout',
            54.6
        );
    }

    /**
     * Enqueue admin assets for Theme Builder pages.
     *
     * @param string $hook Current admin page hook.
     *
     * @return void
     */
    public function enqueue_admin_assets(string $hook): void
    {
        if (false === strpos($hook, $this->menu_slug)) {
            return;
        }

        wp_enqueue_style(
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-theme-builder-admin',
            KING_ADDONS_URL . 'includes/extensions/Theme_Builder/style.css',
            [],
            KING_ADDONS_VERSION
        );

        wp_enqueue_script(
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-theme-builder-admin',
            KING_ADDONS_URL . 'includes/extensions/Theme_Builder/script.js',
            ['jquery'],
            KING_ADDONS_VERSION,
            true
        );
    }

    /**
     * Register meta boxes for Elementor templates.
     *
     * @return void
     */
    public function register_meta_boxes(): void
    {
        add_meta_box(
            'king-addons-theme-builder',
            esc_html__('Theme Builder', 'king-addons'),
            [$this, 'render_meta_box'],
            'elementor_library',
            'side',
            'default'
        );
    }

    /**
     * Render Theme Builder meta box.
     *
     * @param WP_Post $post Current post.
     *
     * @return void
     */
    public function render_meta_box(WP_Post $post): void
    {
        wp_nonce_field('ka_theme_builder_meta_box', 'ka_theme_builder_meta_box_nonce');

        $location = get_post_meta($post->ID, Meta_Keys::LOCATION, true);
        $sub_location = get_post_meta($post->ID, Meta_Keys::SUB_LOCATION, true);
        $priority = (int) get_post_meta($post->ID, Meta_Keys::PRIORITY, true);
        $enabled = '1' === (string) get_post_meta($post->ID, Meta_Keys::ENABLED, true);

        echo '<p><strong>' . esc_html__('Location', 'king-addons') . ':</strong> ' . esc_html($sub_location ?: '-') . '</p>';
        echo '<p><strong>' . esc_html__('Type', 'king-addons') . ':</strong> ' . esc_html($location ?: '-') . '</p>';
        echo '<p><strong>' . esc_html__('Priority', 'king-addons') . ':</strong> ' . esc_html((string) ($priority ?: 10)) . '</p>';
        echo '<p><strong>' . esc_html__('Status', 'king-addons') . ':</strong> ' . ($enabled ? esc_html__('Enabled', 'king-addons') : esc_html__('Disabled', 'king-addons')) . '</p>';
        echo '<p><a href="' . esc_url(admin_url('admin.php?page=' . $this->menu_slug)) . '">' . esc_html__('Manage in Theme Builder', 'king-addons') . '</a></p>';
    }

    /**
     * Handle meta save for Elementor Library posts.
     *
     * @param int     $post_id Post ID.
     * @param WP_Post $post    Post object.
     *
     * @return void
     */
    public function handle_save_post(int $post_id, WP_Post $post): void
    {
        if ('elementor_library' !== $post->post_type) {
            return;
        }

        if (!isset($_POST['ka_theme_builder_meta_box_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ka_theme_builder_meta_box_nonce'])), 'ka_theme_builder_meta_box')) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            return;
        }

        // Keep cache fresh if any relevant meta changes.
        $this->repository->clear_cache();
    }

    /**
     * Clear cache on delete.
     *
     * @return void
     */
    public function handle_delete_post(): void
    {
        $this->repository->clear_cache();
    }

    /**
     * Clear cache on status transition.
     *
     * @param string  $new_status New status.
     * @param string  $old_status Old status.
     * @param WP_Post $post       Post object.
     *
     * @return void
     */
    public function handle_status_transition(string $new_status, string $old_status, WP_Post $post): void
    {
        if ('elementor_library' !== $post->post_type || $new_status === $old_status) {
            return;
        }

        $this->repository->clear_cache();
    }

    /**
     * Render admin page for Theme Builder.
     *
     * @return void
     */
    public function render_admin_page(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Keep cache fresh.
        $this->repository->clear_cache();

        // Shared dark theme support (used by Woo Builder admin page).
        include KING_ADDONS_PATH . 'includes/admin/shared/dark-theme.php';

        $this->handle_inline_actions();
        $templates = $this->prepare_admin_templates();

        $base_url = admin_url('admin.php?page=' . $this->menu_slug);
        $status_filter = isset($_GET['status']) ? sanitize_key(wp_unslash($_GET['status'])) : 'all'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (!in_array($status_filter, ['all', 'enabled', 'disabled'], true)) {
            $status_filter = 'all';
        }
        $filtered_templates = array_values(
            array_filter(
                $templates,
                static function (array $template) use ($status_filter): bool {
                    if ('enabled' === $status_filter) {
                        return !empty($template['enabled']);
                    }
                    if ('disabled' === $status_filter) {
                        return empty($template['enabled']);
                    }
                    return true;
                }
            )
        );

        $type_cards = [
            'single' => [
                'label' => esc_html__('Single', 'king-addons'),
                'desc' => esc_html__('Posts, pages & custom post types', 'king-addons'),
                'default_location' => 'single_post',
            ],
            'archive' => [
                'label' => esc_html__('Archive', 'king-addons'),
                'desc' => esc_html__('Blog, categories, tags & taxonomies', 'king-addons'),
                'default_location' => 'archive_blog',
            ],
            'author' => [
                'label' => esc_html__('Author', 'king-addons'),
                'desc' => esc_html__('Author pages', 'king-addons'),
                'default_location' => 'author_all',
            ],
            'search' => [
                'label' => esc_html__('Search', 'king-addons'),
                'desc' => esc_html__('Search results page', 'king-addons'),
                'default_location' => 'search_results',
            ],
            'not_found' => [
                'label' => esc_html__('404', 'king-addons'),
                'desc' => esc_html__('Not found page', 'king-addons'),
                'default_location' => 'not_found',
            ],
        ];

        $this->render_modern_styles();

        ka_render_dark_theme_styles();
        ka_render_dark_theme_init();
        ?>
        <script>
        if (document.body) {
            document.body.classList.add('ka-admin-v3');
        } else {
            document.addEventListener('DOMContentLoaded', function() {
                document.body.classList.add('ka-admin-v3');
            });
        }
        </script>

        <div class="ka-tb">
            <header class="ka-tb-header">
                <div class="ka-tb-header-content">
                    <span class="ka-tb-title-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16v16H4z" />
                            <path d="M8 8h8M8 12h8M8 16h5" />
                        </svg>
                    </span>
                    <div class="ka-tb-header-titles">
                        <h1><span class="ka-tb-title-text"><?php esc_html_e('Theme Builder', 'king-addons'); ?></span></h1>
                        <p><?php esc_html_e('Create custom templates for your site', 'king-addons'); ?></p>
                    </div>
                </div>
                <div class="ka-tb-header-actions">
                    <button type="button" id="ka-theme-builder-add-new" class="ka-tb-btn ka-tb-btn-primary">
                        <span class="ka-tb-btn-icon" aria-hidden="true">＋</span>
                        <?php esc_html_e('Add New Template', 'king-addons'); ?>
                    </button>
                    <?php ka_render_dark_theme_toggle(); ?>
                </div>
            </header>

            <div class="ka-tb-types" role="list">
                <?php foreach ($type_cards as $type_slug => $data) : ?>
                    <?php $type_icon_svg = $this->get_theme_builder_type_icon_svg((string) $type_slug); ?>
                    <button
                        type="button"
                        class="ka-tb-type"
                        role="listitem"
                        data-ka-tb-type="<?php echo esc_attr($type_slug); ?>"
                        data-ka-tb-location="<?php echo esc_attr($data['default_location']); ?>"
                    >
                        <div class="ka-tb-type-icon" aria-hidden="true">
                            <?php echo $type_icon_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </div>
                        <div class="ka-tb-type-label"><?php echo esc_html($data['label']); ?></div>
                        <div class="ka-tb-type-desc"><?php echo esc_html($data['desc']); ?></div>
                    </button>
                <?php endforeach; ?>
            </div>

            <section class="ka-tb-section">
                <div class="ka-tb-section-header">
                    <h2 class="ka-tb-section-title"><?php esc_html_e('Templates', 'king-addons'); ?></h2>
                    <div class="ka-tb-section-actions">
                        <div class="ka-tb-filters" role="navigation" aria-label="<?php echo esc_attr(esc_html__('Template filters', 'king-addons')); ?>">
                            <?php
                            $base = remove_query_arg(['status', 'paged'], $base_url);
                            $filters = [
                                'all' => esc_html__('All', 'king-addons'),
                                'enabled' => esc_html__('Enabled', 'king-addons'),
                                'disabled' => esc_html__('Disabled', 'king-addons'),
                            ];
                            foreach ($filters as $key => $label) {
                                $url = ('all' === $key) ? $base : add_query_arg('status', $key, $base);
                                $is_active = $status_filter === $key;
                                echo '<a class="ka-tb-filter' . ($is_active ? ' is-active' : '') . '" href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
                            }
                            ?>
                        </div>
                        <div class="ka-tb-section-count"><?php echo esc_html(count($filtered_templates) . ' ' . _n('item', 'items', count($filtered_templates), 'king-addons')); ?></div>
                    </div>
                </div>

                <div class="ka-tb-templates" role="list">
                    <?php if (empty($filtered_templates)) : ?>
                        <div class="ka-tb-empty">
                            <h3 class="ka-tb-empty-title"><?php esc_html_e('No templates yet', 'king-addons'); ?></h3>
                            <p class="ka-tb-empty-desc"><?php esc_html_e('Create your first template to start customizing your site.', 'king-addons'); ?></p>
                            <button type="button" class="ka-tb-btn ka-tb-btn-primary" id="ka-theme-builder-add-new-empty">
                                <span class="ka-tb-btn-icon" aria-hidden="true">＋</span>
                                <?php esc_html_e('Add New Template', 'king-addons'); ?>
                            </button>
                        </div>
                    <?php else : ?>
                        <?php foreach ($filtered_templates as $template) : ?>
                            <?php
                            $template_id = (int) ($template['id'] ?? 0);
                            if (!$template_id) {
                                continue;
                            }

                            $title = !empty($template['title']) ? (string) $template['title'] : sprintf(
                                /* translators: %d: template ID */
                                esc_html__('Template #%d', 'king-addons'),
                                $template_id
                            );

                            $edit_url = admin_url('post.php?post=' . $template_id . '&action=elementor');
                            $enabled = !empty($template['enabled']);
                            $toggle_action = $enabled ? 'disable_template' : 'enable_template';
                            $toggle_url = wp_nonce_url(add_query_arg(['action' => $toggle_action, 'template_id' => $template_id], $base_url), 'ka_theme_builder_toggle_' . $template_id);
                            $delete_url = wp_nonce_url(add_query_arg(['action' => 'delete_template', 'template_id' => $template_id], $base_url), 'ka_theme_builder_delete_' . $template_id);
                            $conditions_text = isset($template['conditions']) && is_array($template['conditions'])
                                ? $this->summarize_admin_conditions($template['conditions'])
                                : esc_html__('All', 'king-addons');
                            $type_label = !empty($template['type']) ? (string) $template['type'] : '';
                            $location_label = !empty($template['sub_location']) ? (string) $template['sub_location'] : '';
                            $priority = isset($template['priority']) ? (int) $template['priority'] : 10;
                            $title_icon_svg = $this->get_theme_builder_type_icon_svg((string) $type_label);
                            ?>
                            <div class="ka-tb-template" role="listitem">
                                <div class="ka-tb-template-status <?php echo $enabled ? 'is-enabled' : 'is-disabled'; ?>">
                                    <?php echo $enabled ? esc_html__('Enabled', 'king-addons') : esc_html__('Disabled', 'king-addons'); ?>
                                </div>
                                <div class="ka-tb-template-info">
                                    <a class="ka-tb-template-title" href="<?php echo esc_url($edit_url); ?>">
                                        <span class="ka-tb-template-title-icon" aria-hidden="true">
                                            <?php echo $title_icon_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                        </span>
                                        <span class="ka-tb-template-title-text"><?php echo esc_html($title); ?></span>
                                    </a>
                                    <div class="ka-tb-template-meta">
                                        <?php if ($type_label) : ?>
                                            <span class="ka-tb-template-type"><?php echo esc_html($type_label); ?></span>
                                        <?php endif; ?>
                                        <?php if ($location_label) : ?>
                                            <span><?php echo esc_html($location_label); ?></span>
                                        <?php endif; ?>
                                        <span><?php echo esc_html(sprintf(esc_html__('Priority %d', 'king-addons'), $priority)); ?></span>
                                    </div>
                                </div>
                                <button type="button" class="ka-tb-template-condition" title="<?php echo esc_attr($conditions_text); ?>">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path d="M4 6h16M4 12h16M4 18h10" />
                                    </svg>
                                    <?php echo esc_html($conditions_text); ?>
                                </button>
                                <div class="ka-tb-template-actions">
                                    <a class="ka-tb-btn ka-tb-btn-secondary" href="<?php echo esc_url($edit_url); ?>"><?php esc_html_e('Edit', 'king-addons'); ?></a>
                                    <button type="button" class="ka-tb-btn ka-tb-btn-secondary ka-tb-quick-edit" data-template-id="<?php echo esc_attr((string) $template_id); ?>" data-priority="<?php echo esc_attr((string) $priority); ?>" data-enabled="<?php echo $enabled ? '1' : '0'; ?>"><?php esc_html_e('Quick Edit', 'king-addons'); ?></button>
                                    <div class="ka-tb-dropdown" data-ka-dropdown>
                                        <button type="button" class="ka-tb-dropdown-trigger" aria-label="<?php echo esc_attr(esc_html__('More actions', 'king-addons')); ?>">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                <circle cx="12" cy="5" r="1" />
                                                <circle cx="12" cy="12" r="1" />
                                                <circle cx="12" cy="19" r="1" />
                                            </svg>
                                        </button>
                                        <div class="ka-tb-dropdown-menu" role="menu">
                                            <a class="ka-tb-dropdown-item" role="menuitem" href="<?php echo esc_url($toggle_url); ?>">
                                                <?php echo $enabled ? esc_html__('Disable', 'king-addons') : esc_html__('Enable', 'king-addons'); ?>
                                            </a>
                                            <div class="ka-tb-dropdown-divider" aria-hidden="true"></div>
                                            <a class="ka-tb-dropdown-item is-danger" role="menuitem" href="<?php echo esc_url($delete_url); ?>" onclick="return confirm('<?php echo esc_js(esc_html__('Move template to trash?', 'king-addons')); ?>');">
                                                <?php esc_html_e('Delete', 'king-addons'); ?>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <?php $this->render_add_new_modal(); ?>
            <?php $this->render_quick_edit_modal(); ?>
        </div>
        <?php ka_render_dark_theme_script(); ?>
        <?php
    }

    /**
     * Summarize conditions to a short label for admin list.
     *
     * @param array<string,mixed> $conditions Conditions payload.
     *
     * @return string
     */
    private function summarize_admin_conditions(array $conditions): string
    {
        $groups = $conditions['groups'] ?? [];
        if (empty($groups)) {
            return (string) esc_html__('All', 'king-addons');
        }

        $rules_count = 0;
        foreach ($groups as $group) {
            $rules = $group['rules'] ?? [];
            $rules_count += is_array($rules) ? count($rules) : 0;
        }

        return (string) sprintf(
            /* translators: %d: number of rules */
            esc_html__('%d rules', 'king-addons'),
            $rules_count
        );
    }

    /**
     * SVG icon for Theme Builder type.
     *
     * @param string $type_slug Theme Builder type slug.
     *
     * @return string
     */
    private function get_theme_builder_type_icon_svg(string $type_slug): string
    {
        $type_slug = sanitize_key($type_slug);
        switch ($type_slug) {
            case 'single':
                return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h6"/></svg>';
            case 'archive':
                return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="6" rx="2"/><path d="M7 10v10h10V10"/><path d="M10 14h4"/></svg>';
            case 'author':
                return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 21a8 8 0 1 0-16 0"/><circle cx="12" cy="7" r="4"/></svg>';
            case 'search':
                return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>';
            case 'not_found':
                return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>';
            default:
                return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h16v16H4z"/><path d="M8 8h8M8 12h8M8 16h5"/></svg>';
        }
    }

    /**
     * Render modern CSS styles for Theme Builder admin page.
     *
     * @return void
     */
    private function render_modern_styles(): void
    {
        ?>
        <style>
        /* ================================================
           Theme Builder - Match Woo Builder Admin Design
           ================================================ */

        :root {
            --ka-tb-font: -apple-system, BlinkMacSystemFont, "SF Pro Display", "SF Pro Text", system-ui, sans-serif;
            --ka-tb-bg: #f5f5f7;
            --ka-tb-surface: #ffffff;
            --ka-tb-text: #1d1d1f;
            --ka-tb-text-secondary: #86868b;
            --ka-tb-border: rgba(0, 0, 0, 0.06);
            --ka-tb-accent: #0071e3;
            --ka-tb-accent-hover: #0077ed;
            --ka-tb-radius: 20px;
            --ka-tb-radius-sm: 12px;
            --ka-tb-shadow: 0 4px 24px rgba(0, 0, 0, 0.06);
            --ka-tb-shadow-hover: 0 8px 32px rgba(0, 0, 0, 0.10);
            --ka-tb-transition: all 0.3s cubic-bezier(0.25, 1, 0.5, 1);
        }

        body.ka-v3-dark {
            --ka-tb-bg: #000000;
            --ka-tb-surface: #1c1c1e;
            --ka-tb-text: #f5f5f7;
            --ka-tb-text-secondary: #98989d;
            --ka-tb-border: rgba(255, 255, 255, 0.10);
            --ka-tb-shadow: 0 4px 24px rgba(0, 0, 0, 0.30);
            --ka-tb-shadow-hover: 0 12px 40px rgba(0, 0, 0, 0.40);
        }

        body.wp-admin #wpcontent,
        body.wp-admin #wpbody,
        body.wp-admin #wpbody-content {
            background: var(--ka-tb-bg) !important;
            padding: 0 !important;
        }

        .ka-tb {
            font-family: var(--ka-tb-font);
            max-width: 1100px;
            margin: 0 auto;
            padding: 48px 40px 80px;
            color: var(--ka-tb-text);
            -webkit-font-smoothing: antialiased;
        }
        .ka-tb * { box-sizing: border-box; }

        /* Header */
        .ka-tb-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 40px;
            gap: 20px;
        }
        .ka-tb-header-content {
            display: flex;
            align-items: flex-start;
            gap: 16px;
        }

        .ka-tb-header-titles {
            display: flex;
            flex-direction: column;
            gap: 8px;
            min-width: 0;
        }

        .ka-tb-header-titles h1 {
            font-size: 56px;
            font-weight: 700;
            letter-spacing: -0.025em;
            margin: 0;
            line-height: 1;
        }
        .ka-tb-header-titles p {
            font-size: 21px;
            color: var(--ka-tb-text-secondary);
            margin: 0;
            font-weight: 400;
        }
        .ka-tb-title-icon {
            width: 76px;
            height: 76px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 22px;
            background: rgba(0, 113, 227, 0.12);
            color: var(--ka-tb-accent);
            flex: 0 0 auto;
        }
        body.ka-v3-dark .ka-tb-title-icon {
            background: rgba(10, 132, 255, 0.18);
            color: #0a84ff;
        }
        .ka-tb-title-icon svg { width: 36px; height: 36px; }
        .ka-tb-title-text {
            background: linear-gradient(135deg, var(--ka-tb-text) 0%, var(--ka-tb-text-secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        /* subtitle moved under .ka-tb-header-titles */
        .ka-tb-header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        /* Buttons */
        .ka-tb-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 18px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            border-radius: 980px;
            border: none;
            cursor: pointer;
            transition: var(--ka-tb-transition);
            white-space: nowrap;
            font-family: inherit;
        }
        .ka-tb-btn-icon {
            font-size: 16px;
            line-height: 1;
        }
        .ka-tb-btn-primary {
            background: var(--ka-tb-accent);
            color: #fff;
        }
        .ka-tb-btn-primary:hover {
            background: var(--ka-tb-accent-hover);
            color: #fff;
            transform: scale(1.02);
        }
        .ka-tb-btn-secondary {
            background: rgba(0, 0, 0, 0.04);
            color: var(--ka-tb-text);
        }
        body.ka-v3-dark .ka-tb-btn-secondary {
            background: rgba(255, 255, 255, 0.08);
        }
        .ka-tb-btn-secondary:hover {
            background: rgba(0, 0, 0, 0.08);
            color: var(--ka-tb-text);
        }
        body.ka-v3-dark .ka-tb-btn-secondary:hover {
            background: rgba(255, 255, 255, 0.12);
        }

        /* Template Types */
        .ka-tb-types {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 44px;
        }
        .ka-tb-type {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 32px 20px;
            background: var(--ka-tb-surface);
            border: 1px solid var(--ka-tb-border);
            border-radius: var(--ka-tb-radius);
            color: var(--ka-tb-text);
            transition: var(--ka-tb-transition);
            cursor: pointer;
            overflow: hidden;
        }
        .ka-tb-type:hover {
            transform: translateY(-4px);
            box-shadow: var(--ka-tb-shadow-hover);
            border-color: var(--ka-tb-accent);
        }
        .ka-tb-type-icon {
            width: 48px;
            height: 48px;
            margin-bottom: 16px;
            color: var(--ka-tb-text-secondary);
            transition: var(--ka-tb-transition);
        }
        .ka-tb-type:hover .ka-tb-type-icon {
            transform: scale(1.1);
            color: var(--ka-tb-accent);
        }
        .ka-tb-type-icon svg { width: 100%; height: 100%; }
        .ka-tb-type-label {
            font-size: 17px;
            font-weight: 600;
            margin-bottom: 4px;
            text-align: center;
        }
        .ka-tb-type-desc {
            font-size: 13px;
            color: var(--ka-tb-text-secondary);
            text-align: center;
        }

        /* Section */
        .ka-tb-section { margin-bottom: 48px; }
        .ka-tb-section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
            gap: 12px;
        }
        .ka-tb-section-title {
            font-size: 28px;
            font-weight: 600;
            letter-spacing: -0.01em;
            margin: 0;
        }
        .ka-tb-section-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .ka-tb-section-count {
            font-size: 15px;
            color: var(--ka-tb-text-secondary);
            background: var(--ka-tb-surface);
            padding: 6px 14px;
            border-radius: 20px;
            border: 1px solid var(--ka-tb-border);
            white-space: nowrap;
        }

        /* Filters */
        .ka-tb-filters {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px;
            border-radius: 980px;
            border: 1px solid var(--ka-tb-border);
            background: var(--ka-tb-surface);
        }
        .ka-tb-filter {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 12px;
            border-radius: 980px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            color: var(--ka-tb-text-secondary);
            transition: var(--ka-tb-transition);
        }
        .ka-tb-filter.is-active {
            background: rgba(0, 113, 227, 0.12);
            color: var(--ka-tb-accent);
        }
        .ka-tb-filter:hover {
            background: rgba(0, 0, 0, 0.04);
            color: var(--ka-tb-text);
        }
        body.ka-v3-dark .ka-tb-filter:hover {
            background: rgba(255, 255, 255, 0.06);
        }

        /* Templates List */
        .ka-tb-templates {
            background: var(--ka-tb-surface);
            border-radius: var(--ka-tb-radius);
            border: 1px solid var(--ka-tb-border);
            overflow: visible;
        }
        .ka-tb-template {
            display: grid;
            grid-template-columns: auto 1fr auto auto;
            align-items: center;
            gap: 20px;
            padding: 20px 24px;
            border-bottom: 1px solid var(--ka-tb-border);
            transition: var(--ka-tb-transition);
        }
        .ka-tb-template:last-child { border-bottom: none; }
        .ka-tb-template:hover { background: rgba(0, 113, 227, 0.03); }
        body.ka-v3-dark .ka-tb-template:hover { background: rgba(255, 255, 255, 0.03); }

        .ka-tb-template-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
            min-width: 0;
        }
        .ka-tb-template-title {
            font-size: 16px;
            font-weight: 500;
            color: var(--ka-tb-text);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
            transition: var(--ka-tb-transition);
        }
        .ka-tb-template-title-icon {
            width: 18px;
            height: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--ka-tb-text-secondary);
            flex: 0 0 auto;
        }
        .ka-tb-template-title-icon svg { width: 18px; height: 18px; }
        .ka-tb-template-title-text {
            min-width: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .ka-tb-template-title:hover { color: var(--ka-tb-accent); }
        .ka-tb-template-meta {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 13px;
            color: var(--ka-tb-text-secondary);
            flex-wrap: wrap;
        }
        .ka-tb-template-type {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .ka-tb-template-type::before {
            content: '';
            width: 6px;
            height: 6px;
            background: var(--ka-tb-accent);
            border-radius: 50%;
        }

        .ka-tb-template-status {
            display: inline-flex;
            align-items: center;
            padding: 6px 14px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 8px;
            white-space: nowrap;
            min-width: 90px;
            justify-content: center;
            justify-self: start;
        }
        .ka-tb-template-status.is-enabled {
            background: rgba(52, 199, 89, 0.15);
            color: #30d158;
        }
        .ka-tb-template-status.is-disabled {
            background: rgba(255, 149, 0, 0.15);
            color: #ff9f0a;
        }

        .ka-tb-template-condition {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            min-height: 40px;
            font-size: 14px;
            font-weight: 500;
            border-radius: 980px;
            background: rgba(0, 113, 227, 0.10);
            color: var(--ka-tb-accent);
            max-width: 240px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            border: none;
            cursor: default;
        }
        .ka-tb-template-condition svg {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
            opacity: 0.7;
        }

        .ka-tb-template-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: nowrap;
        }

        /* Dropdown */
        .ka-tb-dropdown { position: relative; }
        .ka-tb-dropdown-trigger {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: transparent;
            border: 1px solid transparent;
            cursor: pointer;
            transition: var(--ka-tb-transition);
            color: var(--ka-tb-text-secondary);
        }
        .ka-tb-dropdown-trigger:hover {
            background: var(--ka-tb-surface);
            border-color: var(--ka-tb-border);
            color: var(--ka-tb-text);
        }
        .ka-tb-dropdown-trigger svg { width: 20px; height: 20px; }
        .ka-tb-dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            z-index: 100;
            min-width: 180px;
            padding: 8px 0;
            background: var(--ka-tb-surface);
            border: 1px solid var(--ka-tb-border);
            border-radius: var(--ka-tb-radius-sm);
            box-shadow: var(--ka-tb-shadow-hover);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-8px);
            transition: var(--ka-tb-transition);
        }
        .ka-tb-dropdown.is-open .ka-tb-dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(4px);
        }
        .ka-tb-dropdown-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            font-size: 14px;
            color: var(--ka-tb-text);
            text-decoration: none;
            cursor: pointer;
            transition: var(--ka-tb-transition);
        }
        .ka-tb-dropdown-item:hover { background: var(--ka-tb-border); }
        .ka-tb-dropdown-item.is-danger { color: #ff3b30; }
        .ka-tb-dropdown-divider { height: 1px; margin: 8px 0; background: var(--ka-tb-border); }

        /* Empty state */
        .ka-tb-empty {
            padding: 42px 24px;
            text-align: center;
        }
        .ka-tb-empty-title {
            margin: 0 0 8px;
            font-size: 20px;
            font-weight: 600;
        }
        .ka-tb-empty-desc {
            margin: 0 0 18px;
            color: var(--ka-tb-text-secondary);
            font-size: 15px;
        }

        /* Modal - Premium Design */
        .ka-theme-builder-modal { display: none; }
        .ka-tb-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 100000;
            background: rgba(0, 0, 0, 0.55);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.25s ease, visibility 0.25s ease;
            padding: 20px;
        }
        .ka-tb-modal-overlay.is-open {
            opacity: 1;
            visibility: visible;
        }
        .ka-tb-modal {
            width: 100%;
            max-width: 480px;
            padding: 40px 44px 36px;
            background: var(--ka-tb-surface);
            border-radius: 24px;
            box-shadow: 0 32px 100px rgba(0, 0, 0, 0.35), 0 0 0 1px rgba(255, 255, 255, 0.08) inset;
            transform: scale(0.92) translateY(20px);
            transition: transform 0.35s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.25s ease;
            opacity: 0;
        }
        body.ka-v3-dark .ka-tb-modal {
            background: rgba(28, 28, 30, 0.92);
            border: 1px solid rgba(255, 255, 255, 0.12);
            box-shadow: 0 32px 100px rgba(0, 0, 0, 0.55), 0 0 0 1px rgba(255, 255, 255, 0.06) inset;
        }
        .ka-tb-modal-overlay.is-open .ka-tb-modal {
            transform: scale(1) translateY(0);
            opacity: 1;
        }
        .ka-tb-modal h3 {
            font-size: 28px;
            font-weight: 700;
            margin: 0 0 6px;
            color: var(--ka-tb-text);
            letter-spacing: -0.03em;
            line-height: 1.15;
        }
        .ka-tb-modal-desc {
            font-size: 16px;
            color: var(--ka-tb-text-secondary);
            margin: 0 0 28px;
            line-height: 1.5;
        }
        .ka-tb-form-group {
            margin-bottom: 20px;
        }
        .ka-tb-form-label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--ka-tb-text-secondary);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }
        .ka-tb-modal input[type="text"].ka-tb-modal-input,
        .ka-tb-modal-input {
            width: 100% !important;
            padding: 16px 20px !important;
            font-size: 17px !important;
            font-family: inherit !important;
            font-weight: 400 !important;
            border: 2px solid rgba(84, 84, 88, 0.36) !important;
            border-radius: 14px !important;
            background: #2c2c2e !important;
            color: #f5f5f7 !important;
            transition: all 0.2s ease !important;
            box-shadow: none !important;
            -webkit-appearance: none !important;
            appearance: none !important;
            line-height: 1.4 !important;
            height: auto !important;
            margin: 0 !important;
        }
        body:not(.ka-v3-dark) .ka-tb-modal input[type="text"].ka-tb-modal-input,
        body:not(.ka-v3-dark) .ka-tb-modal-input {
            background: #f5f5f7 !important;
            border-color: rgba(0, 0, 0, 0.12) !important;
            color: #1d1d1f !important;
        }
        .ka-tb-modal-input::placeholder {
            color: #98989d !important;
            opacity: 1 !important;
        }
        .ka-tb-modal-input:hover {
            border-color: rgba(10, 132, 255, 0.5) !important;
        }
        body:not(.ka-v3-dark) .ka-tb-modal-input:hover {
            border-color: rgba(0, 113, 227, 0.5) !important;
        }
        .ka-tb-modal-input:focus {
            outline: none !important;
            border-color: #0a84ff !important;
            background: #3a3a3c !important;
            box-shadow: 0 0 0 4px rgba(10, 132, 255, 0.25) !important;
        }
        body:not(.ka-v3-dark) .ka-tb-modal-input:focus {
            background: #ffffff !important;
            border-color: #0071e3 !important;
            box-shadow: 0 0 0 4px rgba(0, 113, 227, 0.15) !important;
        }
        .ka-tb-modal select.ka-tb-form-select,
        .ka-tb-form-select {
            width: 100% !important;
            padding: 16px 52px 16px 20px !important;
            font-size: 17px !important;
            font-family: inherit !important;
            font-weight: 400 !important;
            border: 2px solid rgba(84, 84, 88, 0.36) !important;
            border-radius: 14px !important;
            background-color: #2c2c2e !important;
            color: #f5f5f7 !important;
            transition: all 0.2s ease !important;
            cursor: pointer !important;
            appearance: none !important;
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='%23f5f5f7' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E") !important;
            background-repeat: no-repeat !important;
            background-position: right 18px center !important;
            background-size: 20px 20px !important;
            line-height: 1.4 !important;
            height: auto !important;
            margin: 0 !important;
            box-shadow: none !important;
        }
        body:not(.ka-v3-dark) .ka-tb-modal select.ka-tb-form-select,
        body:not(.ka-v3-dark) .ka-tb-form-select {
            background-color: #f5f5f7 !important;
            border-color: rgba(0, 0, 0, 0.12) !important;
            color: #1d1d1f !important;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='%231d1d1f' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E") !important;
        }
        .ka-tb-form-select:hover {
            border-color: rgba(10, 132, 255, 0.5) !important;
        }
        body:not(.ka-v3-dark) .ka-tb-form-select:hover {
            border-color: rgba(0, 113, 227, 0.5) !important;
        }
        .ka-tb-form-select:focus {
            outline: none !important;
            border-color: #0a84ff !important;
            background-color: #3a3a3c !important;
            box-shadow: 0 0 0 4px rgba(10, 132, 255, 0.25) !important;
        }
        body:not(.ka-v3-dark) .ka-tb-form-select:focus {
            background-color: #ffffff !important;
            border-color: #0071e3 !important;
            box-shadow: 0 0 0 4px rgba(0, 113, 227, 0.15) !important;
        }
        .ka-tb-form-select option {
            background: #2c2c2e !important;
            color: #f5f5f7 !important;
            padding: 12px !important;
        }
        body:not(.ka-v3-dark) .ka-tb-form-select option {
            background: #ffffff !important;
            color: #1d1d1f !important;
        }
        .ka-tb-modal-actions {
            display: flex;
            gap: 12px;
            margin-top: 28px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }
        .ka-tb-modal-actions .ka-tb-btn {
            padding: 14px 24px;
            font-size: 15px;
            font-weight: 500;
        }
        .ka-tb-modal-actions .ka-tb-btn-primary {
            min-width: 180px;
        }

        /* Responsive */
        @media (max-width: 782px) {
            .ka-tb { padding: 36px 20px 70px; }
            .ka-tb-header { flex-direction: column; align-items: flex-start; }
            .ka-tb-header-titles h1 { font-size: 44px; }
            .ka-tb-title-icon { width: 64px; height: 64px; border-radius: 18px; }
            .ka-tb-title-icon svg { width: 30px; height: 30px; }
            .ka-tb-template { grid-template-columns: 1fr; gap: 12px; }
            .ka-tb-template-actions { justify-content: flex-start; flex-wrap: wrap; }
        }
        /* (Old table/modal styles removed; Theme Builder now mirrors Woo Builder UI) */
        </style>
        <?php
    }

    /**
     * Enqueue front-end assets placeholder.
     *
     * @return void
     */
    public function enqueue_front_assets(): void
    {
        // Reserved for future front-end assets; keep file reference valid.
    }

    /**
     * Enqueue preview handler for Elementor editor.
     *
     * @return void
     */
    public function enqueue_preview_handler(): void
    {
        $post_id = get_the_ID() ? (int) get_the_ID() : get_queried_object_id();

        if (!$post_id || 'elementor_library' !== get_post_type($post_id)) {
            return;
        }

        if (!get_post_meta($post_id, Meta_Keys::LOCATION, true)) {
            return;
        }

        wp_enqueue_script(
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-theme-builder-preview',
                KING_ADDONS_URL . 'includes/extensions/Theme_Builder/preview-handler.js',
            ['jquery'],
            KING_ADDONS_VERSION,
            true
        );
    }

    /**
     * Register Elementor document controls for Theme Builder templates.
     *
     * @param mixed $document Document instance.
     *
     * @return void
     */
    public function register_document_controls($document): void
    {
        if (!is_object($document) || !method_exists($document, 'get_main_id')) {
            return;
        }

        $post_id = (int) $document->get_main_id();
        if ('elementor_library' !== get_post_type($post_id)) {
            return;
        }
        $location = get_post_meta($post_id, Meta_Keys::LOCATION, true);

        if (!$location) {
            return;
        }

        $sub_location = get_post_meta($post_id, Meta_Keys::SUB_LOCATION, true);
        $preview_post = (int) get_post_meta($post_id, Meta_Keys::PREVIEW_POST_ID, true);
        $preview_term = (int) get_post_meta($post_id, Meta_Keys::PREVIEW_TERM_ID, true);
        $preview_author = (int) get_post_meta($post_id, Meta_Keys::PREVIEW_AUTHOR_ID, true);
        $preview_query = get_post_meta($post_id, Meta_Keys::PREVIEW_QUERY, true);

        $document->start_controls_section(
            'ka_theme_builder_settings',
            [
                'label' => esc_html__('Theme Builder', 'king-addons'),
                'tab' => Controls_Manager::TAB_SETTINGS,
            ]
        );

        $document->add_control(
            'ka_tb_readonly_type',
            [
                'label' => esc_html__('Template Type', 'king-addons'),
                'type' => Controls_Manager::RAW_HTML,
                'raw' => '<strong>' . esc_html($location) . '</strong>',
                'content_classes' => 'ka-theme-builder-readonly',
            ]
        );

        $document->add_control(
            'ka_tb_readonly_location',
            [
                'label' => esc_html__('Location', 'king-addons'),
                'type' => Controls_Manager::RAW_HTML,
                'raw' => '<strong>' . esc_html($sub_location) . '</strong>',
                'content_classes' => 'ka-theme-builder-readonly',
            ]
        );

        $document->add_control(
            'ka_tb_preview_post',
            [
                'label' => esc_html__('Preview Post ID', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'default' => $preview_post,
            ]
        );

        $document->add_control(
            'ka_tb_preview_term',
            [
                'label' => esc_html__('Preview Term ID', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'default' => $preview_term,
            ]
        );

        $document->add_control(
            'ka_tb_preview_author',
            [
                'label' => esc_html__('Preview Author ID', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'default' => $preview_author,
            ]
        );

        $document->add_control(
            'ka_tb_preview_query',
            [
                'label' => esc_html__('Preview Search Query', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => $preview_query,
            ]
        );

        if ($this->is_pro_location($sub_location)) {
            $document->add_control(
                'ka_tb_pro_notice',
                [
                    'label' => esc_html__('Pro', 'king-addons'),
                    'type' => Controls_Manager::RAW_HTML,
                    'raw' => esc_html__('Advanced conditions require Pro.', 'king-addons'),
                    'content_classes' => 'ka-theme-builder-readonly',
                ]
            );
        }

        $document->end_controls_section();
    }

    /**
     * Persist preview meta from Elementor document settings.
     *
     * @param \Elementor\Core\Base\Document $document Elementor document.
     * @param array<string,mixed>           $data     Saved data.
     *
     * @return void
     */
    public function handle_document_after_save($document, array $data): void
    {
        if (!is_object($document) || !method_exists($document, 'get_main_id')) {
            return;
        }

        $post_id = (int) $document->get_main_id();
        if ('elementor_library' !== get_post_type($post_id)) {
            return;
        }

        if (!get_post_meta($post_id, Meta_Keys::LOCATION, true)) {
            return;
        }

        $preview_post = (int) $document->get_settings('ka_tb_preview_post');
        $preview_term = (int) $document->get_settings('ka_tb_preview_term');
        $preview_author = (int) $document->get_settings('ka_tb_preview_author');
        $preview_query = sanitize_text_field((string) $document->get_settings('ka_tb_preview_query'));

        update_post_meta($post_id, Meta_Keys::PREVIEW_POST_ID, $preview_post);
        update_post_meta($post_id, Meta_Keys::PREVIEW_TERM_ID, $preview_term);
        update_post_meta($post_id, Meta_Keys::PREVIEW_AUTHOR_ID, $preview_author);
        update_post_meta($post_id, Meta_Keys::PREVIEW_QUERY, $preview_query);

        $this->repository->clear_cache();
    }

    /**
     * Handle "Add New" submission.
     *
     * @return void
     */
    public function handle_create_template(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to perform this action.', 'king-addons'));
        }

        if (!isset($_POST['ka_theme_builder_create_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ka_theme_builder_create_nonce'])), 'ka_theme_builder_create')) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            wp_die(esc_html__('Invalid nonce.', 'king-addons'));
        }

        $title = isset($_POST['ka_tb_title']) ? sanitize_text_field(wp_unslash($_POST['ka_tb_title'])) : esc_html__('New Theme Template', 'king-addons');
        $type = isset($_POST['ka_tb_type']) ? sanitize_text_field(wp_unslash($_POST['ka_tb_type'])) : 'single';
        $sub_location = isset($_POST['ka_tb_location']) ? sanitize_text_field(wp_unslash($_POST['ka_tb_location'])) : '';
        $type = $this->normalize_type($type, $sub_location);

        // Check Free tier limit: 1 active template per primary type
        if (!$this->can_use_pro()) {
            $primary_type = $this->normalize_type($type, $sub_location);
            if ($this->count_enabled_templates_by_type($primary_type) >= 1) {
                wp_die(
                    sprintf(
                        /* translators: %s: template type name */
                        esc_html__('Free version allows only 1 active %s template. Disable an existing template or upgrade to Pro for unlimited templates.', 'king-addons'),
                        esc_html($primary_type)
                    )
                );
            }
        }

        $post_id = wp_insert_post([
            'post_type' => 'elementor_library',
            'post_status' => 'publish',
            'post_title' => $title,
        ]);

        if (is_wp_error($post_id)) {
            wp_die(esc_html__('Unable to create template.', 'king-addons'));
        }

        $conditions = $this->build_default_conditions($sub_location);

        $is_pro = Conditions::is_pro_only($conditions) || $this->is_pro_location($sub_location);
        $enabled = $is_pro && !$this->can_use_pro() ? '0' : '1';

        update_post_meta($post_id, Meta_Keys::ENABLED, $enabled);
        update_post_meta($post_id, Meta_Keys::LOCATION, $type);
        update_post_meta($post_id, Meta_Keys::SUB_LOCATION, $sub_location);
        update_post_meta($post_id, Meta_Keys::PRIORITY, 10);
        update_post_meta($post_id, Meta_Keys::IS_PRO_ONLY, $is_pro ? '1' : '0');
        update_post_meta($post_id, Meta_Keys::CONDITIONS, wp_json_encode($conditions));

        $this->repository->clear_cache();

        wp_safe_redirect(admin_url('post.php?post=' . $post_id . '&action=elementor'));
        exit;
    }

    /**
     * Handle quick update submission.
     *
     * @return void
     */
    public function handle_quick_update(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to perform this action.', 'king-addons'));
        }

        if (!isset($_POST['ka_theme_builder_quick_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ka_theme_builder_quick_nonce'])), 'ka_theme_builder_quick')) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            wp_die(esc_html__('Invalid nonce.', 'king-addons'));
        }

        $template_id = isset($_POST['template_id']) ? (int) $_POST['template_id'] : 0;
        $priority = isset($_POST['priority']) ? (int) $_POST['priority'] : 10;
        $enabled = isset($_POST['enabled']) && '1' === $_POST['enabled']; // phpcs:ignore WordPress.Security.NonceVerification.Missing

        if ($template_id > 0) {
            update_post_meta($template_id, Meta_Keys::PRIORITY, $priority);
            update_post_meta($template_id, Meta_Keys::ENABLED, $enabled ? '1' : '0');
            $this->repository->clear_cache();
        }

        wp_safe_redirect(admin_url('admin.php?page=' . $this->menu_slug));
        exit;
    }

    /**
     * Handle template duplication (Pro feature).
     *
     * @return void
     */
    public function handle_duplicate_template(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to perform this action.', 'king-addons'));
        }

        if (!$this->can_use_pro()) {
            wp_die(esc_html__('Template duplication requires Pro.', 'king-addons'));
        }

        $template_id = isset($_GET['template_id']) ? (int) $_GET['template_id'] : 0;

        if (!$template_id || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'] ?? '')), 'ka_theme_builder_duplicate_' . $template_id)) {
            wp_die(esc_html__('Invalid request.', 'king-addons'));
        }

        $original = get_post($template_id);
        if (!$original || 'elementor_library' !== $original->post_type) {
            wp_die(esc_html__('Template not found.', 'king-addons'));
        }

        // Clone the post
        $new_post_id = wp_insert_post([
            'post_type' => 'elementor_library',
            'post_status' => 'draft',
            'post_title' => $original->post_title . ' ' . esc_html__('(Copy)', 'king-addons'),
            'post_content' => $original->post_content,
        ]);

        if (is_wp_error($new_post_id)) {
            wp_die(esc_html__('Failed to duplicate template.', 'king-addons'));
        }

        // Copy all meta data
        $meta_keys = [
            Meta_Keys::ENABLED,
            Meta_Keys::LOCATION,
            Meta_Keys::SUB_LOCATION,
            Meta_Keys::CONDITIONS,
            Meta_Keys::PRIORITY,
            Meta_Keys::IS_PRO_ONLY,
            Meta_Keys::PREVIEW_POST_ID,
            Meta_Keys::PREVIEW_TERM_ID,
            Meta_Keys::PREVIEW_AUTHOR_ID,
            Meta_Keys::PREVIEW_QUERY,
        ];

        foreach ($meta_keys as $key) {
            $value = get_post_meta($template_id, $key, true);
            if ('' !== $value) {
                update_post_meta($new_post_id, $key, $value);
            }
        }

        // Disable the duplicate by default to avoid conflicts
        update_post_meta($new_post_id, Meta_Keys::ENABLED, '0');

        // Copy Elementor data
        $elementor_data = get_post_meta($template_id, '_elementor_data', true);
        if ($elementor_data) {
            update_post_meta($new_post_id, '_elementor_data', $elementor_data);
        }

        $elementor_edit_mode = get_post_meta($template_id, '_elementor_edit_mode', true);
        if ($elementor_edit_mode) {
            update_post_meta($new_post_id, '_elementor_edit_mode', $elementor_edit_mode);
        }

        $this->repository->clear_cache();

        wp_safe_redirect(admin_url('admin.php?page=' . $this->menu_slug . '&duplicated=1'));
        exit;
    }

    /**
     * Handle template include override.
     *
     * @param string $template Current template path.
     *
     * @return string
     */
    public function handle_template_include(string $template): string
    {
        if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
            return $template;
        }

        if (!did_action('elementor/loaded')) {
            return $template;
        }

        if (is_singular('elementor_library')) {
            return $template;
        }

        if ($this->is_woo_context()) {
            return $template;
        }

        $context = Context::from_request();
        $resolved = $this->resolver->resolve($context);

        if (!$resolved) {
            return $template;
        }

        $this->current_template_id = $resolved;

        return KING_ADDONS_PATH . 'includes/extensions/Theme_Builder/templates/theme-builder.php';
    }

    /**
     * Provide current template ID to filters.
     *
     * @param int $template_id Existing template id.
     *
     * @return int
     */
    public function filter_current_template_id(int $template_id): int
    {
        if ($this->current_template_id) {
            return (int) $this->current_template_id;
        }

        return (int) $template_id;
    }

    /**
     * Append body class for resolved template.
     *
     * @param array<int,string> $classes Body classes.
     *
     * @return array<int,string>
     */
    public function filter_body_class(array $classes): array
    {
        if ($this->current_template_id) {
            $classes[] = 'king-addons-theme-builder-active';
        }

        return $classes;
    }

    /**
     * Render add-new modal markup.
     *
     * @return void
     */
    private function render_add_new_modal(): void
    {
        ?>
        <div id="ka-theme-builder-modal" class="ka-tb-modal-overlay" aria-hidden="true">
            <div class="ka-tb-modal" role="dialog" aria-modal="true" aria-labelledby="ka-tb-create-title">
                <h3 id="ka-tb-create-title"><?php echo esc_html__('Create Theme Template', 'king-addons'); ?></h3>
                <p class="ka-tb-modal-desc"><?php echo esc_html__('Choose the template type and where it should apply.', 'king-addons'); ?></p>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="ka_theme_builder_create" />
                    <?php wp_nonce_field('ka_theme_builder_create', 'ka_theme_builder_create_nonce'); ?>

                    <div class="ka-tb-form-group">
                        <label class="ka-tb-form-label" for="ka-tb-title"><?php echo esc_html__('Template Name', 'king-addons'); ?></label>
                        <input type="text" id="ka-tb-title" name="ka_tb_title" class="ka-tb-modal-input" value="<?php echo esc_attr__('Theme Template', 'king-addons'); ?>" />
                    </div>

                    <div class="ka-tb-form-group">
                        <label class="ka-tb-form-label" for="ka-tb-type"><?php echo esc_html__('Template Type', 'king-addons'); ?></label>
                        <select id="ka-tb-type" name="ka_tb_type" class="ka-tb-form-select">
                            <option value="single"><?php echo esc_html__('Single', 'king-addons'); ?></option>
                            <option value="archive"><?php echo esc_html__('Archive', 'king-addons'); ?></option>
                            <option value="author"><?php echo esc_html__('Author', 'king-addons'); ?></option>
                            <option value="search"><?php echo esc_html__('Search', 'king-addons'); ?></option>
                            <option value="not_found"><?php echo esc_html__('404', 'king-addons'); ?></option>
                        </select>
                    </div>

                    <div class="ka-tb-form-group">
                        <label class="ka-tb-form-label" for="ka-tb-location"><?php echo esc_html__('Location', 'king-addons'); ?></label>
                        <select id="ka-tb-location" name="ka_tb_location" class="ka-tb-form-select">
                            <option value="single_post"><?php echo esc_html__('All Posts', 'king-addons'); ?></option>
                            <option value="single_page"><?php echo esc_html__('All Pages', 'king-addons'); ?></option>
                            <option value="single_cpt"><?php echo esc_html__('Custom Post Types (Pro)', 'king-addons'); ?></option>
                            <option value="archive_blog"><?php echo esc_html__('Blog / Posts Page', 'king-addons'); ?></option>
                            <option value="archive_category"><?php echo esc_html__('All Categories', 'king-addons'); ?></option>
                            <option value="archive_tag"><?php echo esc_html__('All Tags', 'king-addons'); ?></option>
                            <option value="archive_tax"><?php echo esc_html__('Custom Taxonomy (Pro)', 'king-addons'); ?></option>
                            <option value="author_all"><?php echo esc_html__('All Authors (Pro)', 'king-addons'); ?></option>
                            <option value="author_specific"><?php echo esc_html__('Specific Authors (Pro)', 'king-addons'); ?></option>
                            <option value="search_results"><?php echo esc_html__('Search Results', 'king-addons'); ?></option>
                            <option value="not_found"><?php echo esc_html__('404 Page', 'king-addons'); ?></option>
                        </select>
                    </div>

                    <div class="ka-tb-modal-actions">
                        <button type="button" class="ka-tb-btn ka-tb-btn-secondary ka-tb-modal-close"><?php echo esc_html__('Cancel', 'king-addons'); ?></button>
                        <button type="submit" class="ka-tb-btn ka-tb-btn-primary"><?php echo esc_html__('Create and Edit with Elementor', 'king-addons'); ?></button>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Render quick edit modal markup.
     *
     * @return void
     */
    private function render_quick_edit_modal(): void
    {
        ?>
        <div id="ka-theme-builder-quick-modal" class="ka-tb-modal-overlay" aria-hidden="true">
            <div class="ka-tb-modal" role="dialog" aria-modal="true" aria-labelledby="ka-tb-quick-title">
                <h3 id="ka-tb-quick-title"><?php echo esc_html__('Quick Edit Template', 'king-addons'); ?></h3>
                <p class="ka-tb-modal-desc"><?php echo esc_html__('Change priority and enabled status.', 'king-addons'); ?></p>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="ka_theme_builder_quick_update" />
                    <?php wp_nonce_field('ka_theme_builder_quick', 'ka_theme_builder_quick_nonce'); ?>
                    <input type="hidden" id="ka-tb-quick-template-id" name="template_id" value="" />

                    <div class="ka-tb-form-group">
                        <label class="ka-tb-form-label" for="ka-tb-quick-priority"><?php echo esc_html__('Priority', 'king-addons'); ?></label>
                        <input type="number" id="ka-tb-quick-priority" name="priority" min="0" step="1" value="10" class="ka-tb-modal-input" />
                    </div>

                    <div class="ka-tb-form-group">
                        <label class="ka-tb-form-label" for="ka-tb-quick-enabled"><?php echo esc_html__('Status', 'king-addons'); ?></label>
                        <select id="ka-tb-quick-enabled" name="enabled" class="ka-tb-form-select">
                            <option value="1"><?php echo esc_html__('Enabled', 'king-addons'); ?></option>
                            <option value="0"><?php echo esc_html__('Disabled', 'king-addons'); ?></option>
                        </select>
                    </div>

                    <div class="ka-tb-modal-actions">
                        <button type="button" class="ka-tb-btn ka-tb-btn-secondary ka-tb-modal-close"><?php echo esc_html__('Cancel', 'king-addons'); ?></button>
                        <button type="submit" class="ka-tb-btn ka-tb-btn-primary"><?php echo esc_html__('Save', 'king-addons'); ?></button>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Build default conditions for a selected location.
     *
     * @param string $sub_location Sub-location value.
     *
     * @return array<string,mixed>
     */
    private function build_default_conditions(string $sub_location): array
    {
        $groups = [];

        switch ($sub_location) {
            case 'single_post':
                $groups[] = [
                    'relation' => 'AND',
                    'rules' => [
                        [
                            'type' => 'include',
                            'target' => 'post_type',
                            'operator' => 'in',
                            'value' => ['post'],
                        ],
                    ],
                ];
                break;
            case 'single_page':
                $groups[] = [
                    'relation' => 'AND',
                    'rules' => [
                        [
                            'type' => 'include',
                            'target' => 'post_type',
                            'operator' => 'in',
                            'value' => ['page'],
                        ],
                    ],
                ];
                break;
            case 'search_results':
                $groups[] = [
                    'relation' => 'AND',
                    'rules' => [
                        [
                            'type' => 'include',
                            'target' => 'search',
                            'operator' => 'in',
                            'value' => ['search'],
                        ],
                    ],
                ];
                break;
            case 'not_found':
                $groups[] = [
                    'relation' => 'AND',
                    'rules' => [
                        [
                            'type' => 'include',
                            'target' => '404',
                            'operator' => 'in',
                            'value' => ['404'],
                        ],
                    ],
                ];
                break;
            default:
                $groups[] = [
                    'relation' => 'AND',
                    'rules' => [],
                ];
                break;
        }

        return ['groups' => $groups];
    }

    /**
     * Normalize template type based on selected sub-location.
     *
     * @param string $type         Base type from UI.
     * @param string $sub_location Selected sub-location.
     *
     * @return string
     */
    private function normalize_type(string $type, string $sub_location): string
    {
        if (strpos($sub_location, 'archive') === 0) {
            return 'archive';
        }

        if ('search_results' === $sub_location) {
            return 'search';
        }

        if ('not_found' === $sub_location) {
            return 'not_found';
        }

        if (strpos($sub_location, 'author') === 0) {
            return 'author';
        }

        return $type ?: 'single';
    }

    /**
     * Determine if sub-location is Pro-only.
     *
     * @param string $sub_location Sub-location identifier.
     *
     * @return bool
     */
    private function is_pro_location(string $sub_location): bool
    {
        if (in_array($sub_location, ['single_cpt', 'archive_tax', 'author_all', 'author_specific'], true)) {
            return true;
        }

        if (strpos($sub_location, 'single_') === 0 && !in_array($sub_location, ['single_post', 'single_page'], true)) {
            return true;
        }

        if (strpos($sub_location, 'archive_') === 0 && !in_array($sub_location, ['archive_blog', 'archive_category', 'archive_tag'], true)) {
            return true;
        }

        return false;
    }

    /**
     * Check whether Pro license is available.
     *
     * @return bool
     */
    private function can_use_pro(): bool
    {
        return function_exists('king_addons_freemius')
            && king_addons_freemius()->can_use_premium_code__premium_only();
    }

    /**
     * Count enabled templates of a given primary type.
     *
     * @param string $type Primary type (single, archive, search, not_found, author).
     *
     * @return int
     */
    private function count_enabled_templates_by_type(string $type): int
    {
        $templates = $this->repository->get_active_templates();
        $count = 0;

        foreach ($templates as $template) {
            if (empty($template['enabled'])) {
                continue;
            }

            $template_location = $template['location'] ?? '';

            if ($template_location === $type) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Prepare data for admin list table.
     *
     * @return array<int,array<string,mixed>>
     */
    private function prepare_admin_templates(): array
    {
        $templates = $this->repository->get_active_templates();
        $prepared = [];

        foreach ($templates as $template) {
            $post = get_post($template['id']);
            if (!$post instanceof WP_Post) {
                continue;
            }

            $prepared[] = [
                'id' => $template['id'],
                'title' => get_the_title($template['id']),
                'type' => $template['location'] ?? '',
                'sub_location' => $template['sub_location'] ?? '',
                'conditions' => $template['conditions'] ?? [],
                'priority' => $template['priority'] ?? 10,
                'enabled' => !empty($template['enabled']),
                'is_pro_only' => !empty($template['is_pro_only']),
            ];
        }

        return $prepared;
    }

    /**
     * Handle inline toggle/delete actions.
     *
     * @return void
     */
    private function handle_inline_actions(): void
    {
        $action = isset($_GET['action']) ? sanitize_text_field(wp_unslash($_GET['action'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $template_id = isset($_GET['template_id']) ? (int) $_GET['template_id'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

        if (!$template_id || empty($action)) {
            return;
        }

        if ('delete_template' === $action) {
            if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'] ?? '')), 'ka_theme_builder_delete_' . $template_id)) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                return;
            }
            wp_trash_post($template_id);
            $this->repository->clear_cache();
        }

        if ('disable_template' === $action || 'enable_template' === $action) {
            if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'] ?? '')), 'ka_theme_builder_toggle_' . $template_id)) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                return;
            }
            update_post_meta($template_id, Meta_Keys::ENABLED, 'enable_template' === $action ? '1' : '0');
            $this->repository->clear_cache();
        }
    }

    /**
     * Check if Woo Builder should own the context.
     *
     * @return bool
     */
    private function is_woo_context(): bool
    {
        if (!class_exists(Woo_Context::class)) {
            return false;
        }

        $context = Woo_Context::detect_context();

        return in_array($context, ['single_product', 'product_archive', 'cart', 'checkout', 'my_account'], true);
    }
}




