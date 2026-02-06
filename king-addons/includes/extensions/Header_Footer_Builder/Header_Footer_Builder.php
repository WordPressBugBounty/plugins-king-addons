<?php /** @noinspection PhpMissingFieldTypeInspection, DuplicatedCode */

namespace King_Addons;

use Elementor;
use WP_Query;

if (!defined('ABSPATH')) {
    exit;
}

final class Header_Footer_Builder
{
    private static ?Header_Footer_Builder $instance = null;
    private static ?string $current_page_type = null;
    private static array $current_page_data = array();
    private static $location_selection;
    private static $user_selection;
    private static $elementor_instance;
    
    /**
     * Admin menu slug for the new page.
     *
     * @var string
     */
    private string $menu_slug = 'king-addons-el-hf';

    public static function instance(): ?Header_Footer_Builder
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        add_action('init', [$this, 'addPostType']);
        add_action('add_meta_boxes', [$this, 'registerMetabox']);
        add_action('save_post', [$this, 'saveMetaboxData']);
        add_action('template_redirect', [$this, 'checkUserCanEdit']);
        add_filter('screen_options_show_screen', [$this, 'disableScreenOptions'], 10, 2);

        require_once(KING_ADDONS_PATH . 'includes/extensions/Header_Footer_Builder/ELHF_Render_On_Canvas.php');
        add_filter('single_template', [$this, 'loadElementorCanvasTemplate']);
        add_filter('template_include', [$this, 'forceElementorCanvasTemplate'], 99);

        self::setCompatibility();
        add_action('admin_enqueue_scripts', array($this, 'enqueueScripts'));
        add_action('admin_action_edit', array($this, 'initialize_options'));
        add_action('wp_ajax_king_addons_el_hf_get_posts_by_query', array($this, 'king_addons_el_hf_get_posts_by_query'));
        add_action('pre_get_posts', [$this, 'forcePreviewQuery']);
        
        // Handle template creation and actions
        add_action('admin_post_ka_hf_builder_create', [$this, 'handleCreateTemplate']);
        add_action('admin_post_ka_hf_builder_quick_update', [$this, 'handleQuickUpdate']);
        
        // AJAX handler for conditions popup
        add_action('wp_ajax_ka_hf_save_conditions', [$this, 'handleAjaxSaveConditions']);
        
        // AJAX handlers for rename and toggle status
        add_action('wp_ajax_ka_hf_rename_template', [$this, 'handleAjaxRenameTemplate']);
        add_action('wp_ajax_ka_hf_toggle_template_status', [$this, 'handleAjaxToggleTemplateStatus']);

        if (is_admin()) {
            add_action('manage_king-addons-el-hf_posts_custom_column', [$this, 'columnContent'], 10, 2);
            add_filter('manage_king-addons-el-hf_posts_columns', [$this, 'columnHeadings']);
        }
    }
    
    /**
     * Register admin menu entry as top-level menu.
     *
     * @return void
     */
    public function registerAdminMenu(): void
    {
        global $menu;
        $menu['54.6'] = array('', 'read', 'separator-king-addons-hf', '', 'wp-menu-separator');
        
        add_menu_page(
            esc_html__('Header & Footer Builder', 'king-addons'),
            esc_html__('Header & Footer', 'king-addons'),
            'manage_options',
            $this->menu_slug,
            [$this, 'renderAdminPage'],
            'dashicons-align-full-width',
            54.7
        );
    }
    
    /**
     * Render modern admin page for Header & Footer Builder.
     *
     * @return void
     */
    public function renderAdminPage(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Include shared dark theme support
        include KING_ADDONS_PATH . 'includes/admin/shared/dark-theme.php';
        
        // Handle tab navigation
        $current_tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'templates'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (!in_array($current_tab, ['templates', 'settings'], true)) {
            $current_tab = 'templates';
        }

        $this->handleInlineActions();
        $templates = $this->prepareAdminTemplates();

        $base_url = admin_url('admin.php?page=' . $this->menu_slug);
        $status_filter = 'all';
        $filtered_templates = $templates;

        $type_cards = [
            'header' => [
                'label' => esc_html__('Header', 'king-addons'),
                'desc' => esc_html__('Site header templates', 'king-addons'),
                'value' => 'king_addons_el_hf_type_header',
            ],
            'footer' => [
                'label' => esc_html__('Footer', 'king-addons'),
                'desc' => esc_html__('Site footer templates', 'king-addons'),
                'value' => 'king_addons_el_hf_type_footer',
            ],
        ];

        $this->renderModernStyles();
        
        // Render dark theme styles and init
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
        
        <div class="ka-hf">
            <header class="ka-hf-header">
                <div class="ka-hf-header-content">
                    <span class="ka-hf-title-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 3h18v6H3zM3 15h18v6H3z" />
                        </svg>
                    </span>
                    <div class="ka-hf-header-titles">
                        <h1><span class="ka-hf-title-text"><?php esc_html_e('Header & Footer Builder', 'king-addons'); ?></span></h1>
                        <p><?php esc_html_e('Create custom headers and footers with display conditions', 'king-addons'); ?></p>
                    </div>
                </div>
                <div class="ka-hf-header-actions">
                    <?php if ('templates' === $current_tab) : ?>
                    <button type="button" id="ka-hf-add-new" class="ka-hf-btn ka-hf-btn-primary">
                        <span class="ka-hf-btn-icon" aria-hidden="true">＋</span>
                        <?php esc_html_e('Add New Template', 'king-addons'); ?>
                    </button>
                    <?php endif; ?>
                    <?php ka_render_dark_theme_toggle(); ?>
                </div>
            </header>

            <!-- Navigation Tabs -->
            <nav class="ka-hf-nav-tabs">
                <a href="<?php echo esc_url($base_url); ?>" class="ka-hf-nav-tab<?php echo 'templates' === $current_tab ? ' is-active' : ''; ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                        <path d="M3 3h18v6H3zM3 15h18v6H3z" />
                    </svg>
                    <?php esc_html_e('Templates', 'king-addons'); ?>
                </a>
                <a href="<?php echo esc_url(add_query_arg('tab', 'settings', $base_url)); ?>" class="ka-hf-nav-tab<?php echo 'settings' === $current_tab ? ' is-active' : ''; ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/>
                    </svg>
                    <?php esc_html_e('Display Settings', 'king-addons'); ?>
                </a>
            </nav>

            <?php if ('templates' === $current_tab) : ?>

            <div class="ka-hf-types" role="list">
                <?php foreach ($type_cards as $type_slug => $data) : ?>
                    <?php $type_icon_svg = $this->getTypeIconSvg($type_slug); ?>
                    <button
                        type="button"
                        class="ka-hf-type"
                        role="listitem"
                        data-ka-hf-type="<?php echo esc_attr($data['value']); ?>"
                    >
                        <div class="ka-hf-type-icon" aria-hidden="true">
                            <?php echo $type_icon_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </div>
                        <div class="ka-hf-type-label"><?php echo esc_html($data['label']); ?></div>
                        <div class="ka-hf-type-desc"><?php echo esc_html($data['desc']); ?></div>
                    </button>
                <?php endforeach; ?>
            </div>

            <section class="ka-hf-section">
                <div class="ka-hf-section-header">
                    <h2 class="ka-hf-section-title"><?php esc_html_e('Templates', 'king-addons'); ?></h2>
                    <div class="ka-hf-section-actions">
                        <div class="ka-hf-filters" role="navigation">
                            <button type="button" class="ka-hf-filter is-active" data-filter="all"><?php esc_html_e('All', 'king-addons'); ?></button>
                            <button type="button" class="ka-hf-filter" data-filter="header"><?php esc_html_e('Headers', 'king-addons'); ?></button>
                            <button type="button" class="ka-hf-filter" data-filter="footer"><?php esc_html_e('Footers', 'king-addons'); ?></button>
                        </div>
                        <div class="ka-hf-section-count"><?php echo esc_html(count($filtered_templates) . ' ' . _n('item', 'items', count($filtered_templates), 'king-addons')); ?></div>
                    </div>
                </div>

                <div class="ka-hf-templates" role="list">
                    <?php if (empty($filtered_templates)) : ?>
                        <div class="ka-hf-empty">
                            <h3 class="ka-hf-empty-title"><?php esc_html_e('No templates yet', 'king-addons'); ?></h3>
                            <p class="ka-hf-empty-desc"><?php esc_html_e('Create your first header or footer template to get started.', 'king-addons'); ?></p>
                            <button type="button" class="ka-hf-btn ka-hf-btn-primary" id="ka-hf-add-new-empty">
                                <span class="ka-hf-btn-icon" aria-hidden="true">＋</span>
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
                                esc_html__('Template #%d', 'king-addons'),
                                $template_id
                            );

                            $edit_elementor_url = admin_url('post.php?post=' . $template_id . '&action=elementor');
                            $edit_settings_url = admin_url('post.php?post=' . $template_id . '&action=edit');
                            $type_value = $template['type'] ?? '';
                            $type_label = 'king_addons_el_hf_type_header' === $type_value ? esc_html__('Header', 'king-addons') : ('king_addons_el_hf_type_footer' === $type_value ? esc_html__('Footer', 'king-addons') : esc_html__('Not Set', 'king-addons'));
                            $type_slug = 'king_addons_el_hf_type_header' === $type_value ? 'header' : ('king_addons_el_hf_type_footer' === $type_value ? 'footer' : 'unset');
                            $delete_url = wp_nonce_url(add_query_arg(['action' => 'delete_template', 'template_id' => $template_id], $base_url), 'ka_hf_delete_' . $template_id);
                            $conditions_text = $this->summarizeConditions($template);
                            $title_icon_svg = $this->getTypeIconSvg($type_slug);
                            $post_status = get_post_status($template_id);
                            $is_disabled = 'publish' !== $post_status;
                            $status_label = $is_disabled ? esc_html__('Disabled', 'king-addons') : $type_label;
                            ?>
                            <div class="ka-hf-template" role="listitem" data-template-type="<?php echo esc_attr($type_slug); ?>" data-template-id="<?php echo esc_attr($template_id); ?>" data-status="<?php echo esc_attr($post_status); ?>" data-type-label="<?php echo esc_attr($type_label); ?>">
                                <div class="ka-hf-template-status <?php echo $is_disabled ? 'is-disabled' : 'is-enabled'; ?>">
                                    <?php echo esc_html($status_label); ?>
                                </div>
                                <div class="ka-hf-template-info">
                                    <a class="ka-hf-template-title" href="<?php echo esc_url($edit_elementor_url); ?>">
                                        <span class="ka-hf-template-title-icon" aria-hidden="true">
                                            <?php echo $title_icon_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                        </span>
                                        <span class="ka-hf-template-title-text"><?php echo esc_html($title); ?></span>
                                    </a>
                                    <div class="ka-hf-template-meta">
                                        <span class="ka-hf-template-type"><?php echo esc_html($type_label); ?></span>
                                    </div>
                                </div>
                                <?php
                                // Prepare conditions data for the popup
                                $include_locs = $template['include_locations'] ?? [];
                                $exclude_locs = $template['exclude_locations'] ?? [];
                                $user_roles_arr = $template['user_roles'] ?? [];
                                $template_data = [
                                    'id' => $template_id,
                                    'title' => $title,
                                    'type' => $type_value,
                                    'include' => $include_locs,
                                    'exclude' => $exclude_locs,
                                    'userRoles' => $user_roles_arr,
                                ];
                                ?>
                                <button type="button" class="ka-hf-template-condition ka-hf-open-conditions" title="<?php echo esc_attr__('Edit Display Conditions', 'king-addons'); ?>" data-template='<?php echo esc_attr(wp_json_encode($template_data)); ?>'>
                                    <svg class="ka-hf-condition-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <circle cx="12" cy="12" r="3"/>
                                        <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/>
                                    </svg>
                                    <?php echo esc_html($conditions_text); ?>
                                </button>
                                <div class="ka-hf-template-actions">
                                    <a class="ka-hf-btn ka-hf-btn-primary" href="<?php echo esc_url($edit_elementor_url); ?>"><?php esc_html_e('Edit with Elementor', 'king-addons'); ?></a>
                                    <div class="ka-hf-dropdown" data-ka-dropdown>
                                        <button type="button" class="ka-hf-dropdown-trigger" aria-label="<?php echo esc_attr(esc_html__('More actions', 'king-addons')); ?>">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                <circle cx="12" cy="5" r="1" />
                                                <circle cx="12" cy="12" r="1" />
                                                <circle cx="12" cy="19" r="1" />
                                            </svg>
                                        </button>
                                        <div class="ka-hf-dropdown-menu" role="menu">
                                            <button type="button" class="ka-hf-dropdown-item ka-hf-rename-btn" role="menuitem" data-id="<?php echo esc_attr($template_id); ?>" data-title="<?php echo esc_attr($title); ?>">
                                                <?php esc_html_e('Rename', 'king-addons'); ?>
                                            </button>
                                            <a class="ka-hf-dropdown-item" role="menuitem" href="<?php echo esc_url($edit_settings_url); ?>">
                                                <?php esc_html_e('WP Edit', 'king-addons'); ?>
                                            </a>
                                            <button type="button" class="ka-hf-dropdown-item ka-hf-toggle-status-btn" role="menuitem" data-id="<?php echo esc_attr($template_id); ?>" data-status="<?php echo esc_attr(get_post_status($template_id)); ?>">
                                                <?php echo get_post_status($template_id) === 'publish' ? esc_html__('Disable', 'king-addons') : esc_html__('Enable', 'king-addons'); ?>
                                            </button>
                                            <a class="ka-hf-dropdown-item is-danger" role="menuitem" href="<?php echo esc_url($delete_url); ?>" onclick="return confirm('<?php echo esc_js(esc_html__('Move template to trash?', 'king-addons')); ?>');">
                                                <?php esc_html_e('Delete', 'king-addons'); ?>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="ka-hf-filter-empty" style="display: none;">
                    <h3 class="ka-hf-empty-title"><?php esc_html_e('No templates found', 'king-addons'); ?></h3>
                    <p class="ka-hf-empty-desc"><?php esc_html_e('Try a different filter or create a new template.', 'king-addons'); ?></p>
                    <button type="button" class="ka-hf-btn ka-hf-btn-primary" id="ka-hf-add-new-filter-empty">
                        <span class="ka-hf-btn-icon" aria-hidden="true">＋</span>
                        <?php esc_html_e('Add New Template', 'king-addons'); ?>
                    </button>
                </div>
            </section>

            <?php $this->renderAddNewModal(); ?>
            <?php $this->renderRenameModal(); ?>
            <?php $this->renderConditionsPopup(); ?>
            
            <?php else : // settings tab ?>
            
            <?php $this->renderDisplaySettingsTab(); ?>
            
            <?php endif; ?>
        </div>
        <?php
        
        // Render dark theme script at the end
        ka_render_dark_theme_script();
    }
    
    /**
     * Render the Display Settings tab content.
     *
     * @return void
     */
    private function renderDisplaySettingsTab(): void
    {
        $chosen_option = get_option('king_addons_el_hf_compatibility_option', '3');
        ?>
        <section class="ka-hf-section ka-hf-settings-section">
            <div class="ka-hf-section-header">
                <h2 class="ka-hf-section-title"><?php esc_html_e('Display Settings', 'king-addons'); ?></h2>
            </div>
            
            <form method="post" action="options.php" class="ka-hf-settings-form">
                <?php settings_fields('king-addons-el-hf-ext-options'); ?>
                
                <div class="ka-hf-settings-card">
                    <h3 class="ka-hf-settings-card-title"><?php esc_html_e('Compatibility Mode', 'king-addons'); ?></h3>
                    <p class="ka-hf-settings-card-desc"><?php esc_html_e('To ensure compatibility with the current theme, three methods are available:', 'king-addons'); ?></p>
                    
                    <div class="ka-hf-settings-options">
                        
                        <label class="ka-hf-settings-option">
                            <input type="radio" name="king_addons_el_hf_compatibility_option" value="1" <?php checked($chosen_option, '1'); ?>>
                            <div class="ka-hf-settings-option-content">
                                <span class="ka-hf-settings-option-title"><?php esc_html_e('Method 1 - Replace Theme Templates', 'king-addons'); ?></span>
                                <span class="ka-hf-settings-option-desc"><?php esc_html_e('This method replaces the theme header (header.php) and footer (footer.php) templates with custom templates. Works well with classic themes that use standard WordPress template structure.', 'king-addons'); ?></span>
                            </div>
                        </label>
                        
                        <label class="ka-hf-settings-option">
                            <input type="radio" name="king_addons_el_hf_compatibility_option" value="2" <?php checked($chosen_option, '2'); ?>>
                            <div class="ka-hf-settings-option-content">
                                <span class="ka-hf-settings-option-title"><?php esc_html_e('Method 2 - CSS Hide + Inject', 'king-addons'); ?></span>
                                <span class="ka-hf-settings-option-desc"><?php esc_html_e('This method hides the theme header and footer using CSS (display: none;) and injects custom templates via wp_body_open and wp_footer hooks.', 'king-addons'); ?></span>
                            </div>
                        </label>

                        <label class="ka-hf-settings-option">
                            <input type="radio" name="king_addons_el_hf_compatibility_option" value="3" <?php checked($chosen_option, '3'); ?>>
                            <div class="ka-hf-settings-option-content">
                                <span class="ka-hf-settings-option-title"><?php esc_html_e('Method 3 - Universal (Recommended)', 'king-addons'); ?></span>
                                <span class="ka-hf-settings-option-desc"><?php esc_html_e('This method combines multiple approaches for maximum theme compatibility. It uses hooks, output buffering, CSS hiding of native theme headers/footers, and JavaScript fallback to ensure headers and footers display correctly on all themes including Block Themes (FSE).', 'king-addons'); ?></span>
                            </div>
                        </label>

                    </div>
                    
                    <div class="ka-hf-settings-actions">
                        <button type="submit" class="ka-hf-btn ka-hf-btn-primary"><?php esc_html_e('Save Settings', 'king-addons'); ?></button>
                    </div>
                </div>
            </form>
        </section>
        <?php
    }
    
    /**
     * Render the Add New Template modal.
     *
     * @return void
     */
    private function renderAddNewModal(): void
    {
        self::$location_selection = self::getLocationSelections();
        self::$user_selection = self::get_user_selections();
        ?>
        <div id="ka-hf-modal" class="ka-hf-modal-overlay" aria-hidden="true">
            <div class="ka-hf-modal" role="dialog" aria-modal="true" aria-labelledby="ka-hf-create-title">
                <h3 id="ka-hf-create-title"><?php echo esc_html__('Create Header / Footer Template', 'king-addons'); ?></h3>
                <p class="ka-hf-modal-desc"><?php echo esc_html__('Choose the template type and configure display conditions.', 'king-addons'); ?></p>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="ka_hf_builder_create" />
                    <?php wp_nonce_field('ka_hf_builder_create', 'ka_hf_builder_create_nonce'); ?>

                    <div class="ka-hf-form-group">
                        <label class="ka-hf-form-label" for="ka-hf-title"><?php echo esc_html__('Template Name', 'king-addons'); ?></label>
                        <input type="text" id="ka-hf-title" name="ka_hf_title" class="ka-hf-modal-input" value="<?php echo esc_attr__('My Template', 'king-addons'); ?>" />
                    </div>

                    <div class="ka-hf-form-group">
                        <label class="ka-hf-form-label" for="ka-hf-type"><?php echo esc_html__('Template Type', 'king-addons'); ?></label>
                        <select id="ka-hf-type" name="ka_hf_type" class="ka-hf-form-select">
                            <option value="king_addons_el_hf_type_header"><?php echo esc_html__('Header', 'king-addons'); ?></option>
                            <option value="king_addons_el_hf_type_footer"><?php echo esc_html__('Footer', 'king-addons'); ?></option>
                        </select>
                    </div>
                    
                    <div class="ka-hf-form-group">
                        <label class="ka-hf-form-label"><?php echo esc_html__('Display On', 'king-addons'); ?></label>
                        <p class="ka-hf-form-desc"><?php echo esc_html__('Add locations where this template should appear', 'king-addons'); ?></p>
                        <div class="ka-hf-conditions-wrap">
                            <div class="ka-hf-create-rule-row">
                                <select id="ka-hf-display-rule" name="ka_hf_display_rule" class="ka-hf-form-select">
                                    <?php foreach (self::$location_selection as $group_data) : ?>
                                        <optgroup label="<?php echo esc_attr($group_data['label']); ?>">
                                            <?php foreach ($group_data['value'] as $opt_key => $opt_value) : ?>
                                                <option value="<?php echo esc_attr($opt_key); ?>" <?php selected($opt_key, 'basic-global'); ?>><?php echo esc_html($opt_value); ?></option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </select>
                                <input type="text" id="ka-hf-display-specific" name="ka_hf_display_specific" class="ka-hf-modal-input ka-hf-specific-input" placeholder="<?php echo esc_attr__('Enter page/post IDs (comma separated)', 'king-addons'); ?>" style="display: none;" />
                            </div>
                        </div>
                    </div>
                    
                    <div class="ka-hf-form-group">
                        <label class="ka-hf-form-label"><?php echo esc_html__('User Roles (Optional)', 'king-addons'); ?></label>
                        <p class="ka-hf-form-desc"><?php echo esc_html__('Display template for specific user roles.', 'king-addons'); ?></p>
                        <div id="ka-hf-create-user-roles" class="ka-hf-rules-container">
                            <!-- Role rows will be added dynamically via JS -->
                        </div>
                        <button type="button" class="ka-hf-add-rule-btn" id="ka-hf-create-add-user-role">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M12 5v14M5 12h14"/></svg>
                            <?php echo esc_html__('Add User Role', 'king-addons'); ?>
                        </button>
                    </div>
                    
                    <div class="ka-hf-form-group">
                        <label class="ka-hf-form-label">
                            <input type="checkbox" id="ka-hf-display-canvas" name="ka_hf_display_canvas" value="1" />
                            <?php echo esc_html__('Enable for Elementor Canvas Template', 'king-addons'); ?>
                        </label>
                        <p class="ka-hf-form-desc"><?php echo esc_html__('Show this template on pages using Elementor Canvas Template', 'king-addons'); ?></p>
                    </div>

                    <div class="ka-hf-modal-actions">
                        <button type="button" class="ka-hf-btn ka-hf-btn-secondary ka-hf-modal-close"><?php echo esc_html__('Cancel', 'king-addons'); ?></button>
                        <button type="submit" class="ka-hf-btn ka-hf-btn-primary"><?php echo esc_html__('Create and Edit with Elementor', 'king-addons'); ?></button>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Render the Rename Template modal.
     *
     * @return void
     */
    private function renderRenameModal(): void
    {
        ?>
        <div id="ka-hf-rename-modal" class="ka-hf-modal-overlay" aria-hidden="true">
            <div class="ka-hf-modal" role="dialog" aria-modal="true" aria-labelledby="ka-hf-rename-title">
                <h3 id="ka-hf-rename-title"><?php echo esc_html__('Rename Template', 'king-addons'); ?></h3>
                <p class="ka-hf-modal-desc"><?php echo esc_html__('Enter a new name for this template.', 'king-addons'); ?></p>

                <input type="hidden" id="ka-hf-rename-id" value="" />
                <div class="ka-hf-form-group">
                    <label class="ka-hf-form-label" for="ka-hf-rename-title-input"><?php echo esc_html__('Template Name', 'king-addons'); ?></label>
                    <input type="text" id="ka-hf-rename-title-input" class="ka-hf-modal-input" value="" />
                </div>

                <div class="ka-hf-modal-actions">
                    <button type="button" class="ka-hf-btn ka-hf-btn-secondary ka-hf-rename-close"><?php echo esc_html__('Cancel', 'king-addons'); ?></button>
                    <button type="button" class="ka-hf-btn ka-hf-btn-primary" id="ka-hf-rename-save"><?php echo esc_html__('Save', 'king-addons'); ?></button>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render the Conditions Popup for editing existing templates.
     *
     * @return void
     */
    private function renderConditionsPopup(): void
    {
        self::$location_selection = self::getLocationSelections();
        self::$user_selection = self::get_user_selections();
        
        // Prepare location options JSON for JS
        $location_options = [];
        foreach (self::$location_selection as $group_key => $group_data) {
            foreach ($group_data['value'] as $opt_key => $opt_value) {
                $location_options[$opt_key] = $opt_value;
            }
        }
        
        $user_options = [];
        foreach (self::$user_selection as $group_data) {
            foreach ($group_data['value'] as $opt_key => $opt_value) {
                $user_options[$opt_key] = $opt_value;
            }
        }
        ?>
        <div id="ka-hf-conditions-modal" class="ka-hf-modal-overlay" aria-hidden="true">
            <div class="ka-hf-modal ka-hf-modal-conditions" role="dialog" aria-modal="true" aria-labelledby="ka-hf-conditions-title">
                <h3 id="ka-hf-conditions-title"><?php echo esc_html__('Template Settings', 'king-addons'); ?></h3>
                <p class="ka-hf-modal-desc"><?php echo esc_html__('Configure template type and display conditions.', 'king-addons'); ?></p>
                
                <input type="hidden" id="ka-hf-cond-template-id" value="" />
                
                <!-- Template Type Section -->
                <div class="ka-hf-form-group">
                    <label class="ka-hf-form-label"><?php echo esc_html__('Template Type', 'king-addons'); ?></label>
                    <select id="ka-hf-cond-template-type" class="ka-hf-form-select">
                        <option value="king_addons_el_hf_type_header"><?php echo esc_html__('Header', 'king-addons'); ?></option>
                        <option value="king_addons_el_hf_type_footer"><?php echo esc_html__('Footer', 'king-addons'); ?></option>
                    </select>
                </div>
                
                <!-- Include Rules Section -->
                <div class="ka-hf-form-group">
                    <label class="ka-hf-form-label"><?php echo esc_html__('Display On', 'king-addons'); ?></label>
                    <div id="ka-hf-include-rules" class="ka-hf-rules-container">
                        <!-- Rules will be added dynamically via JS -->
                    </div>
                    <button type="button" class="ka-hf-add-rule-btn" data-rule-type="include">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M12 5v14M5 12h14"/></svg>
                        <?php echo esc_html__('Add Display Rule', 'king-addons'); ?>
                    </button>
                </div>
                
                <!-- Exclude Rules Section -->
                <div class="ka-hf-form-group">
                    <label class="ka-hf-form-label"><?php echo esc_html__('Do Not Display On', 'king-addons'); ?></label>
                    <div id="ka-hf-exclude-rules" class="ka-hf-rules-container">
                        <!-- Exclusion rules will be added dynamically via JS -->
                    </div>
                    <button type="button" class="ka-hf-add-rule-btn" data-rule-type="exclude">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M12 5v14M5 12h14"/></svg>
                        <?php echo esc_html__('Add Exclusion Rule', 'king-addons'); ?>
                    </button>
                </div>
                
                <!-- User Roles Section -->
                <div class="ka-hf-form-group">
                    <label class="ka-hf-form-label"><?php echo esc_html__('User Roles (Optional)', 'king-addons'); ?></label>
                    <div id="ka-hf-user-roles" class="ka-hf-rules-container">
                        <!-- User role rows will be added dynamically via JS -->
                    </div>
                    <button type="button" class="ka-hf-add-rule-btn" id="ka-hf-add-user-role">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M12 5v14M5 12h14"/></svg>
                        <?php echo esc_html__('Add User Role', 'king-addons'); ?>
                    </button>
                </div>

                <div class="ka-hf-modal-actions">
                    <button type="button" class="ka-hf-btn ka-hf-btn-secondary ka-hf-conditions-close"><?php echo esc_html__('Cancel', 'king-addons'); ?></button>
                    <button type="button" id="ka-hf-save-conditions" class="ka-hf-btn ka-hf-btn-primary"><?php echo esc_html__('Save Conditions', 'king-addons'); ?></button>
                </div>
                
                <div id="ka-hf-conditions-saving" class="ka-hf-saving-overlay" style="display: none;">
                    <span class="ka-hf-spinner"></span>
                    <?php echo esc_html__('Saving...', 'king-addons'); ?>
                </div>
            </div>
        </div>
        
        <!-- Rule template for JS cloning -->
        <template id="ka-hf-rule-template">
            <div class="ka-hf-rule-row">
                <select class="ka-hf-form-select ka-hf-rule-select">
                    <?php foreach (self::$location_selection as $group_data) : ?>
                        <optgroup label="<?php echo esc_attr($group_data['label']); ?>">
                            <?php foreach ($group_data['value'] as $opt_key => $opt_value) : ?>
                                <option value="<?php echo esc_attr($opt_key); ?>"><?php echo esc_html($opt_value); ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
                <input type="text" class="ka-hf-modal-input ka-hf-specific-input" placeholder="<?php echo esc_attr__('Specific IDs (comma separated)', 'king-addons'); ?>" style="display: none;" />
                <button type="button" class="ka-hf-remove-rule-btn" title="<?php echo esc_attr__('Remove rule', 'king-addons'); ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </div>
        </template>

        <!-- User role template for JS cloning -->
        <template id="ka-hf-user-role-template">
            <div class="ka-hf-rule-row ka-hf-user-role-row">
                <select class="ka-hf-form-select ka-hf-user-role-select">
                    <?php foreach (self::$user_selection as $group_data) : ?>
                        <optgroup label="<?php echo esc_attr($group_data['label']); ?>">
                            <?php foreach ($group_data['value'] as $opt_key => $opt_value) : ?>
                                <option value="<?php echo esc_attr($opt_key); ?>"><?php echo esc_html($opt_value); ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="ka-hf-remove-rule-btn" title="<?php echo esc_attr__('Remove role', 'king-addons'); ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </div>
        </template>
        
        <script>
        var kaHfLocationOptions = <?php echo wp_json_encode($location_options); ?>;
        var kaHfUserOptions = <?php echo wp_json_encode($user_options); ?>;
        var kaHfAjaxUrl = <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>;
        var kaHfNonce = <?php echo wp_json_encode(wp_create_nonce('ka_hf_save_conditions')); ?>;
        </script>
        <?php
    }
    
    /**
     * AJAX handler to save conditions for a template.
     *
     * @return void
     */
    public function handleAjaxSaveConditions(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => esc_html__('Permission denied.', 'king-addons')]);
        }
        
        if (!check_ajax_referer('ka_hf_save_conditions', 'nonce', false)) {
            wp_send_json_error(['message' => esc_html__('Invalid nonce.', 'king-addons')]);
        }
        
        $template_id = isset($_POST['template_id']) ? (int) $_POST['template_id'] : 0;
        if (!$template_id || 'king-addons-el-hf' !== get_post_type($template_id)) {
            wp_send_json_error(['message' => esc_html__('Invalid template.', 'king-addons')]);
        }
        
        // Parse include rules
        $include_rules = [];
        $include_specific = [];
        if (!empty($_POST['include_rules']) && is_array($_POST['include_rules'])) {
            foreach ($_POST['include_rules'] as $rule) {
                $rule_val = sanitize_text_field(wp_unslash($rule['rule'] ?? ''));
                if ($rule_val) {
                    $include_rules[] = $rule_val;
                    if ('specifics' === $rule_val && !empty($rule['specific'])) {
                        $specific_ids = array_map('intval', array_filter(explode(',', sanitize_text_field(wp_unslash($rule['specific'])))));
                        $include_specific = array_merge($include_specific, $specific_ids);
                    }
                }
            }
        }
        
        // Parse exclude rules
        $exclude_rules = [];
        $exclude_specific = [];
        if (!empty($_POST['exclude_rules']) && is_array($_POST['exclude_rules'])) {
            foreach ($_POST['exclude_rules'] as $rule) {
                $rule_val = sanitize_text_field(wp_unslash($rule['rule'] ?? ''));
                if ($rule_val) {
                    $exclude_rules[] = $rule_val;
                    if ('specifics' === $rule_val && !empty($rule['specific'])) {
                        $specific_ids = array_map('intval', array_filter(explode(',', sanitize_text_field(wp_unslash($rule['specific'])))));
                        $exclude_specific = array_merge($exclude_specific, $specific_ids);
                    }
                }
            }
        }
        
        // Parse user roles (multiple)
        $user_roles = [];
        if (!empty($_POST['user_roles']) && is_array($_POST['user_roles'])) {
            foreach ($_POST['user_roles'] as $role) {
                $role_val = sanitize_text_field(wp_unslash($role));
                if ($role_val) {
                    $user_roles[] = $role_val;
                }
            }
        }
        if (empty($user_roles)) {
            $user_roles = ['all'];
        }
        $user_roles = array_values(array_unique($user_roles));
        if (in_array('all', $user_roles, true)) {
            $user_roles = ['all'];
        }
        
        // Parse template type
        $template_type = isset($_POST['template_type']) ? sanitize_text_field(wp_unslash($_POST['template_type'])) : '';
        if (!empty($template_type) && in_array($template_type, ['king_addons_el_hf_type_header', 'king_addons_el_hf_type_footer'], true)) {
            update_post_meta($template_id, 'king_addons_el_hf_template_type', $template_type);
        }
        
        // Save meta
        $include_locations = [
            'rule' => $include_rules,
            'specific' => $include_specific,
        ];
        $exclude_locations = [
            'rule' => $exclude_rules,
            'specific' => $exclude_specific,
        ];
        
        update_post_meta($template_id, 'king_addons_el_hf_target_include_locations', $include_locations);
        update_post_meta($template_id, 'king_addons_el_hf_target_exclude_locations', $exclude_locations);
        update_post_meta($template_id, 'king_addons_el_hf_target_user_roles', $user_roles);
        
        wp_send_json_success(['message' => esc_html__('Settings saved successfully.', 'king-addons')]);
    }
    
    /**
     * AJAX handler to rename a template.
     *
     * @return void
     */
    public function handleAjaxRenameTemplate(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => esc_html__('Permission denied.', 'king-addons')]);
        }
        
        if (!check_ajax_referer('ka_hf_save_conditions', 'nonce', false)) {
            wp_send_json_error(['message' => esc_html__('Invalid nonce.', 'king-addons')]);
        }
        
        $template_id = isset($_POST['template_id']) ? (int) $_POST['template_id'] : 0;
        $new_title = isset($_POST['new_title']) ? sanitize_text_field(wp_unslash($_POST['new_title'])) : '';
        
        if (!$template_id || 'king-addons-el-hf' !== get_post_type($template_id)) {
            wp_send_json_error(['message' => esc_html__('Invalid template.', 'king-addons')]);
        }
        
        if (empty($new_title)) {
            wp_send_json_error(['message' => esc_html__('Title cannot be empty.', 'king-addons')]);
        }
        
        $result = wp_update_post([
            'ID' => $template_id,
            'post_title' => $new_title,
        ]);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => esc_html__('Failed to rename template.', 'king-addons')]);
        }
        
        wp_send_json_success(['message' => esc_html__('Template renamed successfully.', 'king-addons')]);
    }
    
    /**
     * AJAX handler to toggle template status (publish/draft).
     *
     * @return void
     */
    public function handleAjaxToggleTemplateStatus(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => esc_html__('Permission denied.', 'king-addons')]);
        }
        
        if (!check_ajax_referer('ka_hf_save_conditions', 'nonce', false)) {
            wp_send_json_error(['message' => esc_html__('Invalid nonce.', 'king-addons')]);
        }
        
        $template_id = isset($_POST['template_id']) ? (int) $_POST['template_id'] : 0;
        $new_status = isset($_POST['new_status']) ? sanitize_text_field(wp_unslash($_POST['new_status'])) : '';
        
        if (!$template_id || 'king-addons-el-hf' !== get_post_type($template_id)) {
            wp_send_json_error(['message' => esc_html__('Invalid template.', 'king-addons')]);
        }
        
        if (!in_array($new_status, ['publish', 'draft'], true)) {
            wp_send_json_error(['message' => esc_html__('Invalid status.', 'king-addons')]);
        }
        
        $result = wp_update_post([
            'ID' => $template_id,
            'post_status' => $new_status,
        ]);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => esc_html__('Failed to update template status.', 'king-addons')]);
        }
        
        wp_send_json_success(['message' => esc_html__('Template status updated.', 'king-addons')]);
    }
    
    /**
     * Handle "Add New" template submission.
     *
     * @return void
     */
    public function handleCreateTemplate(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to perform this action.', 'king-addons'));
        }

        if (!isset($_POST['ka_hf_builder_create_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ka_hf_builder_create_nonce'])), 'ka_hf_builder_create')) {
            wp_die(esc_html__('Invalid nonce.', 'king-addons'));
        }

        $title = isset($_POST['ka_hf_title']) ? sanitize_text_field(wp_unslash($_POST['ka_hf_title'])) : esc_html__('My Template', 'king-addons');
        $type = isset($_POST['ka_hf_type']) ? sanitize_text_field(wp_unslash($_POST['ka_hf_type'])) : 'king_addons_el_hf_type_header';
        $display_rule = isset($_POST['ka_hf_display_rule']) ? sanitize_text_field(wp_unslash($_POST['ka_hf_display_rule'])) : 'basic-global';
        $display_specific = isset($_POST['ka_hf_display_specific']) ? sanitize_text_field(wp_unslash($_POST['ka_hf_display_specific'])) : '';
        $display_canvas = isset($_POST['ka_hf_display_canvas']) ? '1' : '';
        
        // Parse user roles (multiple selection)
        $user_roles = [];
        if (!empty($_POST['ka_hf_user_role']) && is_array($_POST['ka_hf_user_role'])) {
            foreach ($_POST['ka_hf_user_role'] as $role) {
                $role_val = sanitize_text_field(wp_unslash($role));
                if ($role_val) {
                    $user_roles[] = $role_val;
                }
            }
        }
        if (empty($user_roles)) {
            $user_roles = ['all'];
        }
        $user_roles = array_values(array_unique($user_roles));
        if (in_array('all', $user_roles, true)) {
            $user_roles = ['all'];
        }

        // Validate type
        if (!in_array($type, ['king_addons_el_hf_type_header', 'king_addons_el_hf_type_footer'], true)) {
            $type = 'king_addons_el_hf_type_header';
        }

        // Create the post
        $post_id = wp_insert_post([
            'post_type' => 'king-addons-el-hf',
            'post_status' => 'publish',
            'post_title' => $title,
        ]);

        if (is_wp_error($post_id)) {
            wp_die(esc_html__('Unable to create template.', 'king-addons'));
        }

        // Save template type immediately
        update_post_meta($post_id, 'king_addons_el_hf_template_type', $type);

        // Save display conditions
        $specific_ids = [];
        if ('specifics' === $display_rule && !empty($display_specific)) {
            $specific_ids = array_map('intval', array_filter(explode(',', $display_specific)));
        }
        $target_locations = [
            'rule' => [$display_rule],
            'specific' => $specific_ids,
        ];
        update_post_meta($post_id, 'king_addons_el_hf_target_include_locations', $target_locations);
        update_post_meta($post_id, 'king_addons_el_hf_target_exclude_locations', []);
        
        // Save user roles (multiple)
        update_post_meta($post_id, 'king_addons_el_hf_target_user_roles', $user_roles);
        
        // Save canvas display option
        if ($display_canvas) {
            update_post_meta($post_id, 'king-addons-el-hf-display-on-canvas', '1');
        }

        // Redirect to Elementor editor
        wp_safe_redirect(admin_url('post.php?post=' . $post_id . '&action=elementor'));
        exit;
    }
    
    /**
     * Handle quick update submission.
     *
     * @return void
     */
    public function handleQuickUpdate(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to perform this action.', 'king-addons'));
        }

        if (!isset($_POST['ka_hf_quick_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ka_hf_quick_nonce'])), 'ka_hf_quick')) {
            wp_die(esc_html__('Invalid nonce.', 'king-addons'));
        }

        $template_id = isset($_POST['template_id']) ? (int) $_POST['template_id'] : 0;
        $type = isset($_POST['type']) ? sanitize_text_field(wp_unslash($_POST['type'])) : '';

        if ($template_id > 0 && !empty($type)) {
            update_post_meta($template_id, 'king_addons_el_hf_template_type', $type);
        }

        wp_safe_redirect(admin_url('admin.php?page=' . $this->menu_slug));
        exit;
    }
    
    /**
     * Handle inline toggle/delete actions.
     *
     * @return void
     */
    private function handleInlineActions(): void
    {
        $action = isset($_GET['action']) ? sanitize_text_field(wp_unslash($_GET['action'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $template_id = isset($_GET['template_id']) ? (int) $_GET['template_id'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

        if (!$template_id || empty($action)) {
            return;
        }

        if ('delete_template' === $action) {
            if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'] ?? '')), 'ka_hf_delete_' . $template_id)) {
                return;
            }
            wp_trash_post($template_id);
        }
    }
    
    /**
     * Prepare data for admin list table.
     *
     * @return array
     */
    private function prepareAdminTemplates(): array
    {
        $args = [
            'post_type' => 'king-addons-el-hf',
            'post_status' => ['publish', 'draft'],
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        $query = new WP_Query($args);
        $templates = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                $templates[] = [
                    'id' => $post_id,
                    'title' => get_the_title(),
                    'type' => get_post_meta($post_id, 'king_addons_el_hf_template_type', true),
                    'include_locations' => get_post_meta($post_id, 'king_addons_el_hf_target_include_locations', true),
                    'exclude_locations' => get_post_meta($post_id, 'king_addons_el_hf_target_exclude_locations', true),
                    'user_roles' => get_post_meta($post_id, 'king_addons_el_hf_target_user_roles', true),
                ];
            }
        }
        wp_reset_postdata();

        return $templates;
    }
    
    /**
     * Summarize conditions to a short label for admin list.
     *
     * @param array $template Template data.
     *
     * @return string
     */
    private function summarizeConditions(array $template): string
    {
        $locations = $template['include_locations'] ?? [];
        
        if (empty($locations) || empty($locations['rule'])) {
            return esc_html__('All', 'king-addons');
        }

        $rules = $locations['rule'];
        $count = is_array($rules) ? count($rules) : 0;

        if ($count === 0) {
            return esc_html__('All', 'king-addons');
        }

        // Get the first rule label
        $first_rule = $rules[0] ?? '';
        $label = self::getLocation($first_rule);

        if ($count > 1) {
            return sprintf(
                esc_html__('%s + %d more', 'king-addons'),
                $label,
                $count - 1
            );
        }

        return $label ?: esc_html__('All', 'king-addons');
    }
    
    /**
     * SVG icon for template type.
     *
     * @param string $type_slug Template type slug.
     *
     * @return string
     */
    private function getTypeIconSvg(string $type_slug): string
    {
        switch ($type_slug) {
            case 'header':
                return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="6" rx="1"/><path d="M3 13h18M3 17h10"/></svg>';
            case 'footer':
                return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="15" width="18" height="6" rx="1"/><path d="M3 7h18M3 11h10"/></svg>';
            default:
                return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 3h18v6H3zM3 15h18v6H3z"/></svg>';
        }
    }
    
    /**
     * Render modern CSS styles for admin page.
     *
     * @return void
     */
    private function renderModernStyles(): void
    {
        ?>
        <style>
        /* ================================================
           Header & Footer Builder - Premium Admin Design
           ================================================ */

        :root {
            --ka-hf-font: -apple-system, BlinkMacSystemFont, "SF Pro Display", "SF Pro Text", system-ui, sans-serif;
            --ka-hf-bg: #f5f5f7;
            --ka-hf-surface: #ffffff;
            --ka-hf-text: #1d1d1f;
            --ka-hf-text-secondary: #86868b;
            --ka-hf-border: rgba(0, 0, 0, 0.06);
            --ka-hf-accent: #0071e3;
            --ka-hf-accent-hover: #0077ed;
            --ka-hf-radius: 20px;
            --ka-hf-radius-sm: 12px;
            --ka-hf-shadow: 0 4px 24px rgba(0, 0, 0, 0.06);
            --ka-hf-shadow-hover: 0 8px 32px rgba(0, 0, 0, 0.10);
            --ka-hf-transition: all 0.3s cubic-bezier(0.25, 1, 0.5, 1);
        }
        
        /* Dark theme support */
        body.ka-v3-dark {
            --ka-hf-bg: #1c1c1e;
            --ka-hf-surface: #2c2c2e;
            --ka-hf-text: #f5f5f7;
            --ka-hf-text-secondary: #98989d;
            --ka-hf-border: rgba(255, 255, 255, 0.1);
            --ka-hf-shadow: 0 4px 24px rgba(0, 0, 0, 0.3);
            --ka-hf-shadow-hover: 0 8px 32px rgba(0, 0, 0, 0.4);
        }

        body.wp-admin #wpcontent,
        body.wp-admin #wpbody,
        body.wp-admin #wpbody-content {
            background: var(--ka-hf-bg) !important;
            padding: 0 !important;
        }

        .ka-hf {
            font-family: var(--ka-hf-font);
            max-width: 1100px;
            margin: 0 auto;
            padding: 48px 40px 80px;
            color: var(--ka-hf-text);
            -webkit-font-smoothing: antialiased;
        }
        .ka-hf * { box-sizing: border-box; }

        /* Header */
        .ka-hf-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 40px;
            gap: 20px;
        }
        .ka-hf-header-content {
            display: flex;
            align-items: flex-start;
            gap: 16px;
        }

        .ka-hf-header-titles {
            display: flex;
            flex-direction: column;
            gap: 8px;
            min-width: 0;
        }

        .ka-hf-header-titles h1 {
            font-size: 48px;
            font-weight: 700;
            letter-spacing: -0.025em;
            margin: 0;
            line-height: 1;
        }
        .ka-hf-header-titles p {
            font-size: 21px;
            color: var(--ka-hf-text-secondary);
            margin: 0;
            font-weight: 400;
        }
        .ka-hf-title-icon {
            width: 76px;
            height: 76px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 22px;
            background: rgba(10, 132, 255, 0.18) !important;
            color: #0a84ff !important;
            flex: 0 0 auto;
        }
        .ka-hf-title-icon svg { width: 36px; height: 36px; }
        .ka-hf-title-text {
            background: linear-gradient(135deg, var(--ka-hf-text) 0%, var(--ka-hf-text-secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .ka-hf-header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        /* Buttons */
        .ka-hf-btn {
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
            transition: var(--ka-hf-transition);
            white-space: nowrap;
            font-family: inherit;
        }
        .ka-hf-btn-icon {
            font-size: 16px;
            line-height: 1;
        }
        .ka-hf-btn-primary {
            background: var(--ka-hf-accent);
            color: #fff;
        }
        .ka-hf-btn-primary:hover {
            background: var(--ka-hf-accent-hover);
            color: #fff;
            transform: scale(1.02);
        }
        .ka-hf-btn-secondary {
            background: rgba(0, 0, 0, 0.04);
            color: var(--ka-hf-text);
        }
        .ka-hf-btn-secondary:hover {
            background: rgba(0, 0, 0, 0.08);
            color: var(--ka-hf-text);
        }

        /* Template Types - Compact Cards */
        .ka-hf-types {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 36px;
            max-width: 480px;
        }
        .ka-hf-type {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px 16px;
            background: var(--ka-hf-surface);
            border: 1px solid var(--ka-hf-border);
            border-radius: var(--ka-hf-radius-sm);
            color: var(--ka-hf-text);
            transition: var(--ka-hf-transition);
            cursor: pointer;
            overflow: hidden;
        }
        .ka-hf-type:hover {
            transform: translateY(-2px);
            box-shadow: var(--ka-hf-shadow-hover);
            border-color: var(--ka-hf-accent);
        }
        .ka-hf-type-icon {
            width: 36px;
            height: 36px;
            margin-bottom: 10px;
            color: var(--ka-hf-text-secondary);
            transition: var(--ka-hf-transition);
        }
        .ka-hf-type:hover .ka-hf-type-icon {
            transform: scale(1.1);
            color: var(--ka-hf-accent);
        }
        .ka-hf-type-icon svg { width: 100%; height: 100%; }
        .ka-hf-type-label {
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 2px;
            text-align: center;
        }
        .ka-hf-type-desc {
            font-size: 12px;
            color: var(--ka-hf-text-secondary);
            text-align: center;
        }

        /* Section */
        .ka-hf-section { margin-bottom: 48px; }
        .ka-hf-section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
            gap: 12px;
        }
        .ka-hf-section-title {
            font-size: 28px;
            font-weight: 600;
            letter-spacing: -0.01em;
            margin: 0;
        }
        .ka-hf-section-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .ka-hf-section-count {
            font-size: 15px;
            color: var(--ka-hf-text-secondary);
            background: var(--ka-hf-surface);
            padding: 6px 14px;
            border-radius: 20px;
            border: 1px solid var(--ka-hf-border);
            white-space: nowrap;
        }

        /* Filters */
        .ka-hf-filters {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px;
            border-radius: 980px;
            border: 1px solid var(--ka-hf-border);
            background: var(--ka-hf-surface);
        }
        .ka-hf-filter {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 12px;
            border-radius: 980px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            color: var(--ka-hf-text-secondary);
            transition: var(--ka-hf-transition);
            background: transparent;
            border: none;
            cursor: pointer;
            font-family: inherit;
        }
        .ka-hf-filter.is-active {
            background: rgba(0, 113, 227, 0.12);
            color: var(--ka-hf-accent);
        }
        .ka-hf-filter:hover {
            background: rgba(0, 0, 0, 0.04);
            color: var(--ka-hf-text);
        }

        /* Templates List */
        .ka-hf-templates {
            background: var(--ka-hf-surface);
            border-radius: var(--ka-hf-radius);
            border: 1px solid var(--ka-hf-border);
            overflow: visible;
        }
        .ka-hf-template {
            display: grid;
            grid-template-columns: auto 1fr auto auto;
            align-items: center;
            gap: 20px;
            padding: 20px 24px;
            border-bottom: 1px solid var(--ka-hf-border);
            transition: var(--ka-hf-transition);
        }
        .ka-hf-template:last-child { border-bottom: none; }
        .ka-hf-template:hover { background: rgba(0, 113, 227, 0.03); }

        .ka-hf-template-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
            min-width: 0;
        }
        .ka-hf-template-title {
            font-size: 16px;
            font-weight: 500;
            color: var(--ka-hf-text);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
            transition: var(--ka-hf-transition);
        }
        .ka-hf-template-title-icon {
            width: 18px;
            height: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--ka-hf-text-secondary);
            flex: 0 0 auto;
        }
        .ka-hf-template-title-icon svg { width: 18px; height: 18px; }
        .ka-hf-template-title-text {
            min-width: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .ka-hf-template-title:hover { color: var(--ka-hf-accent); }
        .ka-hf-template-meta {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 13px;
            color: var(--ka-hf-text-secondary);
            flex-wrap: wrap;
        }
        .ka-hf-template-type {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .ka-hf-template-type::before {
            content: '';
            width: 6px;
            height: 6px;
            background: var(--ka-hf-accent);
            border-radius: 50%;
        }

        .ka-hf-template-status {
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
        .ka-hf-template-status.is-enabled {
            background: rgba(52, 199, 89, 0.15);
            color: #30d158;
        }
        .ka-hf-template-status.is-disabled {
            background: rgba(255, 149, 0, 0.15);
            color: #ff9f0a;
        }

        .ka-hf-template-condition {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            min-height: 40px;
            font-size: 14px;
            font-weight: 500;
            border-radius: 980px;
            background: rgba(0, 113, 227, 0.10);
            color: var(--ka-hf-accent);
            max-width: 240px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            border: none;
            cursor: default;
        }
        .ka-hf-template-condition svg {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
            opacity: 0.7;
        }

        .ka-hf-template-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: nowrap;
        }

        /* Dropdown */
        .ka-hf-dropdown { position: relative; }
        .ka-hf-dropdown-trigger {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: transparent;
            border: 1px solid transparent;
            cursor: pointer;
            transition: var(--ka-hf-transition);
            color: var(--ka-hf-text-secondary);
        }
        .ka-hf-dropdown-trigger:hover {
            background: var(--ka-hf-surface);
            border-color: var(--ka-hf-border);
            color: var(--ka-hf-text);
        }
        .ka-hf-dropdown-trigger svg { width: 20px; height: 20px; }
        .ka-hf-dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            z-index: 100;
            min-width: 180px;
            padding: 8px 0;
            background: var(--ka-hf-surface);
            border: 1px solid var(--ka-hf-border);
            border-radius: var(--ka-hf-radius-sm);
            box-shadow: var(--ka-hf-shadow-hover);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-8px);
            transition: var(--ka-hf-transition);
        }
        .ka-hf-dropdown.is-open .ka-hf-dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(4px);
        }
        .ka-hf-dropdown-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            font-size: 14px;
            color: var(--ka-hf-text);
            text-decoration: none;
            cursor: pointer;
            transition: var(--ka-hf-transition);
            background: transparent;
            border: none;
            width: 100%;
            text-align: left;
            font-family: inherit;
        }
        .ka-hf-dropdown-item:hover { background: var(--ka-hf-border); }
        .ka-hf-dropdown-item.is-danger { color: #ff3b30; }

        /* Empty state */
        .ka-hf-empty {
            padding: 60px 24px;
            text-align: center;
        }
        .ka-hf-filter-empty {
            margin-top: 12px;
            padding: 48px 24px;
            text-align: center;
            background: var(--ka-hf-surface);
            border-radius: var(--ka-hf-radius);
            border: 1px solid var(--ka-hf-border);
        }
        .ka-hf-empty-title {
            margin: 0 0 8px;
            font-size: 20px;
            font-weight: 600;
        }
        .ka-hf-empty-desc {
            margin: 0 0 18px;
            color: var(--ka-hf-text-secondary);
            font-size: 15px;
        }

        /* Modal */
        .ka-hf-modal-overlay {
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
        .ka-hf-modal-overlay.is-open {
            opacity: 1;
            visibility: visible;
        }
        .ka-hf-modal {
            width: 100%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            padding: 32px 36px 28px;
            background: var(--ka-hf-surface);
            border-radius: 20px;
            box-shadow: 0 32px 100px rgba(0, 0, 0, 0.35);
            transform: scale(0.92) translateY(20px);
            transition: transform 0.35s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.25s ease;
            opacity: 0;
        }
        .ka-hf-modal-overlay.is-open .ka-hf-modal {
            transform: scale(1) translateY(0);
            opacity: 1;
        }
        .ka-hf-modal h3 {
            font-size: 24px;
            font-weight: 700;
            margin: 0 0 4px;
            color: var(--ka-hf-text);
            letter-spacing: -0.02em;
            line-height: 1.2;
        }
        .ka-hf-modal-desc {
            font-size: 14px;
            color: var(--ka-hf-text-secondary);
            margin: 0 0 28px;
            line-height: 1.5;
        }
        .ka-hf-form-group {
            margin-bottom: 20px;
        }
        .ka-hf-form-label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--ka-hf-text-secondary);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }
        .ka-hf-form-desc {
            font-size: 13px;
            color: var(--ka-hf-text-secondary);
            margin: 4px 0 8px;
        }
        .ka-hf-modal-input {
            width: 100% !important;
            padding: 16px 20px !important;
            font-size: 17px !important;
            font-family: inherit !important;
            font-weight: 400 !important;
            border: 2px solid rgba(0, 0, 0, 0.12) !important;
            border-radius: 14px !important;
            background: #f5f5f7 !important;
            color: #1d1d1f !important;
            transition: all 0.2s ease !important;
            box-shadow: none !important;
            -webkit-appearance: none !important;
            appearance: none !important;
            line-height: 1.4 !important;
            height: auto !important;
            margin: 0 !important;
        }
        .ka-hf-modal-input:focus {
            outline: none !important;
            border-color: #0071e3 !important;
            background: #ffffff !important;
            box-shadow: 0 0 0 4px rgba(0, 113, 227, 0.15) !important;
        }
        .ka-hf-form-select {
            width: 100% !important;
            padding: 16px 52px 16px 20px !important;
            font-size: 17px !important;
            font-family: inherit !important;
            font-weight: 400 !important;
            border: 2px solid rgba(0, 0, 0, 0.12) !important;
            border-radius: 14px !important;
            background-color: #f5f5f7 !important;
            color: #1d1d1f !important;
            transition: all 0.2s ease !important;
            cursor: pointer !important;
            appearance: none !important;
            -webkit-appearance: none !important;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='%231d1d1f' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E") !important;
            background-repeat: no-repeat !important;
            background-position: right 18px center !important;
            background-size: 20px !important;
            line-height: 1.4 !important;
            height: auto !important;
            margin: 0 !important;
            box-shadow: none !important;
        }
        .ka-hf-form-select:focus {
            outline: none !important;
            border-color: #0071e3 !important;
            background-color: #ffffff !important;
            box-shadow: 0 0 0 4px rgba(0, 113, 227, 0.15) !important;
        }
        /* Multi-select styles */
        .ka-hf-form-select.ka-hf-multi-select {
            background-image: none !important;
            padding-right: 20px !important;
            min-height: 120px !important;
        }
        .ka-hf-form-select.ka-hf-multi-select option {
            padding: 8px 12px !important;
            border-radius: 6px !important;
            margin: 2px 4px !important;
        }
        .ka-hf-form-select.ka-hf-multi-select option:checked {
            background: linear-gradient(0deg, rgba(0, 113, 227, 0.15) 0%, rgba(0, 113, 227, 0.15) 100%) !important;
            color: #0071e3 !important;
        }
        .ka-hf-form-select.ka-hf-multi-select optgroup {
            font-weight: 600 !important;
            font-style: normal !important;
            padding: 8px 4px 4px !important;
            color: var(--ka-hf-text-secondary) !important;
        }
        .ka-hf-modal-actions {
            display: flex;
            gap: 10px;
            margin-top: 24px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }
        .ka-hf-modal-actions .ka-hf-btn {
            padding: 12px 20px;
            font-size: 14px;
            font-weight: 500;
        }
        .ka-hf-modal-actions .ka-hf-btn-primary {
            min-width: 180px;
        }
        
        .ka-hf-conditions-wrap {
            margin-bottom: 8px;
        }
        
        /* Create modal rule row */
        .ka-hf-create-rule-row {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .ka-hf-create-rule-row .ka-hf-form-select {
            flex: 1;
        }
        .ka-hf-create-rule-row .ka-hf-specific-input {
            margin-top: 0;
        }
        
        /* Form spacing improvements */
        .ka-hf-form-group {
            margin-bottom: 18px;
        }
        .ka-hf-form-group:last-of-type {
            margin-bottom: 8px;
        }
        .ka-hf-modal-desc {
            margin-bottom: 24px !important;
        }
        .ka-hf-form-desc {
            margin: 2px 0 6px;
            font-size: 12px;
            opacity: 0.8;
        }
        
        /* Smaller inputs */
        .ka-hf-modal-input,
        .ka-hf-form-select {
            padding: 12px 16px !important;
            font-size: 15px !important;
            border-radius: 12px !important;
        }
        .ka-hf-form-select {
            padding-right: 44px !important;
            background-position: right 14px center !important;
        }
        
        /* Specific input styling */
        .ka-hf-specific-input {
            flex: 1;
        }
        .ka-hf-specific-input[style*="display: block"] {
            margin-top: 8px;
        }
        
        /* Clickable condition button */
        .ka-hf-template-condition.ka-hf-open-conditions {
            cursor: pointer;
            transition: var(--ka-hf-transition);
        }
        .ka-hf-template-condition.ka-hf-open-conditions:hover {
            background: rgba(0, 113, 227, 0.18);
            transform: scale(1.02);
        }
        
        /* Conditions Modal - Larger width */
        .ka-hf-modal.ka-hf-modal-conditions {
            max-width: 600px;
            position: relative;
        }
        
        /* Rules container */
        .ka-hf-rules-container {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 10px;
        }
        
        .ka-hf-rule-row {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        
        .ka-hf-rule-row .ka-hf-rule-select {
            flex: 1;
            min-width: 180px;
        }
        
        .ka-hf-rule-row .ka-hf-specific-input {
            flex: 0 0 180px;
            max-width: 180px;
        }
        
        .ka-hf-remove-rule-btn {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 59, 48, 0.1);
            border: none;
            border-radius: 10px;
            color: #ff3b30;
            cursor: pointer;
            transition: var(--ka-hf-transition);
            flex-shrink: 0;
        }
        .ka-hf-remove-rule-btn:hover {
            background: rgba(255, 59, 48, 0.2);
        }
        
        .ka-hf-add-rule-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 8px 14px;
            font-size: 12px;
            font-weight: 500;
            color: var(--ka-hf-accent);
            background: rgba(0, 113, 227, 0.06);
            border: 1px dashed rgba(0, 113, 227, 0.25);
            border-radius: 8px;
            cursor: pointer;
            transition: var(--ka-hf-transition);
            font-family: inherit;
        }
        .ka-hf-add-rule-btn:hover {
            background: rgba(0, 113, 227, 0.12);
            border-color: var(--ka-hf-accent);
        }
        .ka-hf-add-rule-btn svg {
            width: 14px;
            height: 14px;
        }
        
        /* Saving overlay */
        .ka-hf-saving-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 12px;
            border-radius: 24px;
            font-size: 15px;
            font-weight: 500;
            color: var(--ka-hf-text);
            z-index: 10;
        }
        body.ka-v3-dark .ka-hf-saving-overlay {
            background: rgba(44, 44, 46, 0.95);
        }
        
        .ka-hf-spinner {
            width: 32px;
            height: 32px;
            border: 3px solid var(--ka-hf-border);
            border-top-color: var(--ka-hf-accent);
            border-radius: 50%;
            animation: ka-hf-spin 0.8s linear infinite;
        }
        
        @keyframes ka-hf-spin {
            to { transform: rotate(360deg); }
        }
        
        /* Dark theme overrides for secondary button */
        body.ka-v3-dark .ka-hf-btn-secondary {
            background: rgba(255, 255, 255, 0.08);
            color: var(--ka-hf-text);
        }
        body.ka-v3-dark .ka-hf-btn-secondary:hover {
            background: rgba(255, 255, 255, 0.14);
        }
        
        /* Dark theme modal inputs */
        body.ka-v3-dark .ka-hf-modal-input,
        body.ka-v3-dark .ka-hf-form-select {
            background-color: #3a3a3c !important;
            border-color: rgba(255, 255, 255, 0.15) !important;
            color: #f5f5f7 !important;
        }
        body.ka-v3-dark .ka-hf-modal-input:focus,
        body.ka-v3-dark .ka-hf-form-select:focus {
            background-color: #48484a !important;
            border-color: #0071e3 !important;
        }
        body.ka-v3-dark .ka-hf-form-select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='%23f5f5f7' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E") !important;
        }
        
        /* Dark theme modal */
        body.ka-v3-dark .ka-hf-modal {
            background: var(--ka-hf-surface);
        }

        /* Responsive */
        @media (max-width: 782px) {
            .ka-hf { padding: 36px 20px 70px; }
            .ka-hf-header { flex-direction: column; align-items: flex-start; }
            .ka-hf-header-titles h1 { font-size: 36px; }
            .ka-hf-title-icon { width: 64px; height: 64px; border-radius: 18px; }
            .ka-hf-title-icon svg { width: 30px; height: 30px; }
            .ka-hf-template { grid-template-columns: 1fr; gap: 12px; }
            .ka-hf-template-actions { justify-content: flex-start; flex-wrap: wrap; }
            .ka-hf-nav-tabs { flex-wrap: wrap; }
        }
        
        /* Navigation Tabs */
        .ka-hf-nav-tabs {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px;
            background: var(--ka-hf-surface);
            border: 1px solid var(--ka-hf-border);
            border-radius: 14px;
            margin-bottom: 32px;
        }
        .ka-hf-nav-tab {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            font-size: 14px;
            font-weight: 500;
            color: var(--ka-hf-text-secondary);
            text-decoration: none;
            border-radius: 10px;
            transition: var(--ka-hf-transition);
        }
        .ka-hf-nav-tab svg {
            opacity: 0.7;
            transition: var(--ka-hf-transition);
        }
        .ka-hf-nav-tab:hover {
            color: var(--ka-hf-text);
            background: rgba(0, 0, 0, 0.03);
        }
        .ka-hf-nav-tab:hover svg {
            opacity: 1;
        }
        .ka-hf-nav-tab.is-active {
            background: var(--ka-hf-accent);
            color: #fff;
        }
        .ka-hf-nav-tab.is-active svg {
            opacity: 1;
        }
        body.ka-v3-dark .ka-hf-nav-tab:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        
        /* Settings Tab Styles */
        .ka-hf-settings-section {
            max-width: 700px;
        }
        .ka-hf-settings-form {
            margin: 0;
        }
        .ka-hf-settings-card {
            background: var(--ka-hf-surface);
            border: 1px solid var(--ka-hf-border);
            border-radius: var(--ka-hf-radius);
            padding: 32px;
        }
        .ka-hf-settings-card-title {
            font-size: 20px;
            font-weight: 600;
            margin: 0 0 8px 0;
            color: var(--ka-hf-text);
        }
        .ka-hf-settings-card-desc {
            font-size: 14px;
            color: var(--ka-hf-text-secondary);
            margin: 0 0 24px 0;
            line-height: 1.5;
        }
        .ka-hf-settings-options {
            display: flex;
            flex-direction: column;
            gap: 16px;
            margin-bottom: 28px;
        }
        .ka-hf-settings-option {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            padding: 20px;
            background: rgba(0, 0, 0, 0.02);
            border: 1px solid var(--ka-hf-border);
            border-radius: var(--ka-hf-radius-sm);
            cursor: pointer;
            transition: var(--ka-hf-transition);
        }
        .ka-hf-settings-option:hover {
            border-color: var(--ka-hf-accent);
            background: rgba(0, 113, 227, 0.02);
        }
        .ka-hf-settings-option:has(input:checked) {
            border-color: var(--ka-hf-accent);
            background: rgba(0, 113, 227, 0.05);
        }
        .ka-hf-settings-option input[type="radio"] {
            margin: 3px 0 0 0;
            flex-shrink: 0;
            accent-color: var(--ka-hf-accent);
            width: 18px;
            height: 18px;
        }
        .ka-hf-settings-option-content {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .ka-hf-settings-option-title {
            font-size: 15px;
            font-weight: 600;
            color: var(--ka-hf-text);
        }
        .ka-hf-settings-option-desc {
            font-size: 13px;
            color: var(--ka-hf-text-secondary);
            line-height: 1.5;
        }
        .ka-hf-settings-actions {
            padding-top: 4px;
        }
        body.ka-v3-dark .ka-hf-settings-option {
            background: rgba(255, 255, 255, 0.02);
        }
        body.ka-v3-dark .ka-hf-settings-option:hover {
            background: rgba(0, 113, 227, 0.08);
        }
        body.ka-v3-dark .ka-hf-settings-option:has(input:checked) {
            background: rgba(0, 113, 227, 0.12);
        }
        </style>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var addNewButton = document.getElementById('ka-hf-add-new');
            var addNewEmptyButton = document.getElementById('ka-hf-add-new-empty');
            var addNewFilterEmptyButton = document.getElementById('ka-hf-add-new-filter-empty');
            var modal = document.getElementById('ka-hf-modal');
            var closeButtons = document.querySelectorAll('.ka-hf-modal-close');
            var typeSelect = document.getElementById('ka-hf-type');
            var createUserRolesContainer = document.getElementById('ka-hf-create-user-roles');
            var createAddUserRoleBtn = document.getElementById('ka-hf-create-add-user-role');

            var renameModal = document.getElementById('ka-hf-rename-modal');
            var renameCloseButtons = document.querySelectorAll('.ka-hf-rename-close');
            var renameSaveBtn = document.getElementById('ka-hf-rename-save');
            var renameIdInput = document.getElementById('ka-hf-rename-id');
            var renameTitleInput = document.getElementById('ka-hf-rename-title-input');
            
            var openModal = function() {
                if (modal) {
                    modal.classList.add('is-open');
                    modal.setAttribute('aria-hidden', 'false');
                }

                // Ensure at least one user role row exists in create modal
                if (createUserRolesContainer && typeof addUserRoleRow === 'function') {
                    if (createUserRolesContainer.children.length === 0) {
                        addUserRoleRow(createUserRolesContainer, 'all', true);
                    }
                }
            };
            
            var closeModal = function() {
                if (modal) {
                    modal.classList.remove('is-open');
                    modal.setAttribute('aria-hidden', 'true');
                }
            };
            
            if (addNewButton) {
                addNewButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    openModal();
                });
            }
            
            if (addNewEmptyButton) {
                addNewEmptyButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    openModal();
                });
            }

            if (addNewFilterEmptyButton) {
                addNewFilterEmptyButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    openModal();
                });
            }
            
            // Type cards: preselect type and open create modal
            var typeButtons = document.querySelectorAll('.ka-hf-type');
            typeButtons.forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    var type = btn.getAttribute('data-ka-hf-type');
                    if (typeSelect && type) {
                        typeSelect.value = type;
                    }
                    openModal();
                });
            });
            
            closeButtons.forEach(function(button) {
                button.addEventListener('click', closeModal);
            });
            
            // Backdrop click closes
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        closeModal();
                    }
                });
            }
            
            // ESC closes
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeModal();
                }
            });
            
            // Dropdown toggles
            var dropdowns = document.querySelectorAll('[data-ka-dropdown]');
            dropdowns.forEach(function(dropdown) {
                var trigger = dropdown.querySelector('.ka-hf-dropdown-trigger');
                if (trigger) {
                    trigger.addEventListener('click', function(e) {
                        e.stopPropagation();
                        dropdown.classList.toggle('is-open');
                    });
                }
            });
            
            document.addEventListener('click', function() {
                dropdowns.forEach(function(dropdown) {
                    dropdown.classList.remove('is-open');
                });
            });
            
            // ==========================================
            // JS Filtering (All/Headers/Footers)
            // ==========================================
            var filterButtons = document.querySelectorAll('.ka-hf-filter[data-filter]');
            var templateItems = document.querySelectorAll('.ka-hf-template[data-template-type]');
            var templateCount = document.querySelector('.ka-hf-section-count');
            var filterEmptyState = document.querySelector('.ka-hf-filter-empty');
            var templatesList = document.querySelector('.ka-hf-templates');
            
            var updateCount = function(count) {
                if (templateCount) {
                    templateCount.textContent = count + ' ' + (count === 1 ? '<?php echo esc_js(__('item', 'king-addons')); ?>' : '<?php echo esc_js(__('items', 'king-addons')); ?>');
                }
            };
            
            filterButtons.forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    var filter = btn.getAttribute('data-filter');
                    
                    // Update active state
                    filterButtons.forEach(function(b) { b.classList.remove('is-active'); });
                    btn.classList.add('is-active');
                    
                    // Filter templates
                    var visibleCount = 0;
                    templateItems.forEach(function(item) {
                        var type = item.getAttribute('data-template-type');
                        if (filter === 'all' || type === filter) {
                            item.style.display = '';
                            visibleCount++;
                        } else {
                            item.style.display = 'none';
                        }
                    });
                    
                    updateCount(visibleCount);

                    if (filterEmptyState && templatesList) {
                        if (visibleCount === 0) {
                            filterEmptyState.style.display = 'block';
                            templatesList.style.display = 'none';
                        } else {
                            filterEmptyState.style.display = 'none';
                            templatesList.style.display = '';
                        }
                    }
                });
            });
            
            // ==========================================
            // Rename Template
            // ==========================================
            var renameBtns = document.querySelectorAll('.ka-hf-rename-btn');
            renameBtns.forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var templateId = btn.getAttribute('data-id');
                    var currentTitle = btn.getAttribute('data-title');
                    if (renameModal && renameIdInput && renameTitleInput) {
                        renameIdInput.value = templateId || '';
                        renameTitleInput.value = currentTitle || '';
                        renameModal.classList.add('is-open');
                        renameModal.setAttribute('aria-hidden', 'false');
                        renameTitleInput.focus();
                        renameTitleInput.select();
                    }
                });
            });

            if (renameCloseButtons.length) {
                renameCloseButtons.forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        if (renameModal) {
                            renameModal.classList.remove('is-open');
                            renameModal.setAttribute('aria-hidden', 'true');
                        }
                    });
                });
            }

            if (renameModal) {
                renameModal.addEventListener('click', function(e) {
                    if (e.target === renameModal) {
                        renameModal.classList.remove('is-open');
                        renameModal.setAttribute('aria-hidden', 'true');
                    }
                });
            }

            if (renameSaveBtn) {
                renameSaveBtn.addEventListener('click', function() {
                    if (!renameIdInput || !renameTitleInput) return;
                    var templateId = renameIdInput.value;
                    var newTitle = (renameTitleInput.value || '').trim();
                    if (!templateId || !newTitle) return;

                    var formData = new FormData();
                    formData.append('action', 'ka_hf_rename_template');
                    formData.append('nonce', kaHfNonce);
                    formData.append('template_id', templateId);
                    formData.append('new_title', newTitle);

                    fetch(kaHfAjaxUrl, {
                        method: 'POST',
                        body: formData
                    })
                    .then(function(response) { return response.json(); })
                    .then(function(data) {
                        if (data.success) {
                            var templateEl = document.querySelector('.ka-hf-template[data-template-id="' + templateId + '"]');
                            if (templateEl) {
                                var titleText = templateEl.querySelector('.ka-hf-template-title-text');
                                if (titleText) titleText.textContent = newTitle;
                                var renameBtn = templateEl.querySelector('.ka-hf-rename-btn');
                                if (renameBtn) renameBtn.setAttribute('data-title', newTitle);
                            }
                            if (renameModal) {
                                renameModal.classList.remove('is-open');
                                renameModal.setAttribute('aria-hidden', 'true');
                            }
                        } else {
                            alert(data.data && data.data.message ? data.data.message : '<?php echo esc_js(__('Error renaming template', 'king-addons')); ?>');
                        }
                    })
                    .catch(function(err) {
                        alert('<?php echo esc_js(__('Error renaming template', 'king-addons')); ?>');
                        console.error(err);
                    });
                });
            }
            
            // ==========================================
            // Enable/Disable Template
            // ==========================================
            var toggleStatusBtns = document.querySelectorAll('.ka-hf-toggle-status-btn');
            toggleStatusBtns.forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var templateId = btn.getAttribute('data-id');
                    var currentStatus = btn.getAttribute('data-status');
                    var newStatus = (currentStatus === 'publish') ? 'draft' : 'publish';
                    
                    var formData = new FormData();
                    formData.append('action', 'ka_hf_toggle_template_status');
                    formData.append('nonce', kaHfNonce);
                    formData.append('template_id', templateId);
                    formData.append('new_status', newStatus);
                    
                    fetch(kaHfAjaxUrl, {
                        method: 'POST',
                        body: formData
                    })
                    .then(function(response) { return response.json(); })
                    .then(function(data) {
                        if (data.success) {
                            // Update button text and data attribute
                            btn.setAttribute('data-status', newStatus);
                            btn.textContent = (newStatus === 'publish') ? '<?php echo esc_js(__('Disable', 'king-addons')); ?>' : '<?php echo esc_js(__('Enable', 'king-addons')); ?>';
                            
                            // Update status badge
                            var templateEl = document.querySelector('.ka-hf-template[data-template-id="' + templateId + '"]');
                            if (templateEl) {
                                templateEl.setAttribute('data-status', newStatus);
                                var statusBadge = templateEl.querySelector('.ka-hf-template-status');
                                if (statusBadge) {
                                    if (newStatus === 'publish') {
                                        statusBadge.classList.remove('is-disabled');
                                        statusBadge.classList.add('is-enabled');
                                        var typeLabel = templateEl.getAttribute('data-type-label') || '';
                                        statusBadge.textContent = typeLabel || '<?php echo esc_js(__('Enabled', 'king-addons')); ?>';
                                    } else {
                                        statusBadge.classList.remove('is-enabled');
                                        statusBadge.classList.add('is-disabled');
                                        statusBadge.textContent = '<?php echo esc_js(__('Disabled', 'king-addons')); ?>';
                                    }
                                }
                            }
                        } else {
                            alert(data.data && data.data.message ? data.data.message : '<?php echo esc_js(__('Error updating template status', 'king-addons')); ?>');
                        }
                    })
                    .catch(function(err) {
                        alert('<?php echo esc_js(__('Error updating template status', 'king-addons')); ?>');
                        console.error(err);
                    });
                });
            });
            
            // ==========================================
            // Create Modal: Show/hide specific input
            // ==========================================
            var displayRuleSelect = document.getElementById('ka-hf-display-rule');
            var displaySpecificInput = document.getElementById('ka-hf-display-specific');
            
            if (displayRuleSelect && displaySpecificInput) {
                displayRuleSelect.addEventListener('change', function() {
                    if (this.value === 'specifics') {
                        displaySpecificInput.style.display = 'block';
                    } else {
                        displaySpecificInput.style.display = 'none';
                        displaySpecificInput.value = '';
                    }
                });
            }

            if (createAddUserRoleBtn) {
                createAddUserRoleBtn.addEventListener('click', function() {
                    if (createUserRolesContainer && typeof addUserRoleRow === 'function') {
                        addUserRoleRow(createUserRolesContainer, 'all', true);
                    }
                });
            }
            
            // ==========================================
            // Conditions Popup Functionality
            // ==========================================
            var conditionsModal = document.getElementById('ka-hf-conditions-modal');
            var conditionsCloseButtons = document.querySelectorAll('.ka-hf-conditions-close');
            var conditionButtons = document.querySelectorAll('.ka-hf-open-conditions');
            var saveConditionsBtn = document.getElementById('ka-hf-save-conditions');
            var ruleTemplate = document.getElementById('ka-hf-rule-template');
            var userRoleTemplate = document.getElementById('ka-hf-user-role-template');
            var userRolesContainer = document.getElementById('ka-hf-user-roles');
            var addUserRoleBtn = document.getElementById('ka-hf-add-user-role');
            
            var openConditionsModal = function() {
                if (conditionsModal) {
                    conditionsModal.classList.add('is-open');
                    conditionsModal.setAttribute('aria-hidden', 'false');
                }
            };
            
            var closeConditionsModal = function() {
                if (conditionsModal) {
                    conditionsModal.classList.remove('is-open');
                    conditionsModal.setAttribute('aria-hidden', 'true');
                }
            };
            
            // Add rule row
            var addRuleRow = function(container, ruleValue, specificValue) {
                var templateContent = ruleTemplate.content.cloneNode(true);
                var row = templateContent.querySelector('.ka-hf-rule-row');
                var select = row.querySelector('.ka-hf-rule-select');
                var specificInput = row.querySelector('.ka-hf-specific-input');
                var removeBtn = row.querySelector('.ka-hf-remove-rule-btn');
                
                if (ruleValue) {
                    select.value = ruleValue;
                }
                
                // Show specific input if needed
                if (ruleValue === 'specifics') {
                    specificInput.style.display = 'block';
                    if (specificValue) {
                        specificInput.value = specificValue;
                    }
                }
                
                // Handle select change
                select.addEventListener('change', function() {
                    if (this.value === 'specifics') {
                        specificInput.style.display = 'block';
                    } else {
                        specificInput.style.display = 'none';
                        specificInput.value = '';
                    }
                });
                
                // Handle remove
                removeBtn.addEventListener('click', function() {
                    row.remove();
                });
                
                container.appendChild(row);
            };

            // Add user role row
            var addUserRoleRow = function(container, roleValue, includeNameAttr) {
                if (!container || !userRoleTemplate) return;
                var templateContent = userRoleTemplate.content.cloneNode(true);
                var row = templateContent.querySelector('.ka-hf-user-role-row');
                var select = row.querySelector('.ka-hf-user-role-select');
                var removeBtn = row.querySelector('.ka-hf-remove-rule-btn');

                if (includeNameAttr) {
                    select.setAttribute('name', 'ka_hf_user_role[]');
                }
                if (roleValue) {
                    select.value = roleValue;
                }

                removeBtn.addEventListener('click', function() {
                    row.remove();
                });

                container.appendChild(row);
            };

            // Create modal default row (after helper is defined)
            if (createUserRolesContainer && createUserRolesContainer.children.length === 0) {
                addUserRoleRow(createUserRolesContainer, 'all', true);
            }
            
            // Load template data into popup
            var loadConditionsData = function(templateData) {
                var includeContainer = document.getElementById('ka-hf-include-rules');
                var excludeContainer = document.getElementById('ka-hf-exclude-rules');
                var templateIdInput = document.getElementById('ka-hf-cond-template-id');
                var templateTypeSelect = document.getElementById('ka-hf-cond-template-type');
                
                // Clear existing rules
                includeContainer.innerHTML = '';
                excludeContainer.innerHTML = '';
                if (userRolesContainer) {
                    userRolesContainer.innerHTML = '';
                }
                
                // Set template ID
                templateIdInput.value = templateData.id;
                
                // Set template type
                if (templateTypeSelect && templateData.type) {
                    templateTypeSelect.value = templateData.type;
                }
                
                // Load include rules
                var includeRules = templateData.include && templateData.include.rule ? templateData.include.rule : [];
                var includeSpecific = templateData.include && templateData.include.specific ? templateData.include.specific : [];
                
                if (includeRules.length === 0) {
                    addRuleRow(includeContainer, 'basic-global', '');
                } else {
                    includeRules.forEach(function(rule, idx) {
                        var specificVal = (rule === 'specifics' && includeSpecific.length) ? includeSpecific.join(',') : '';
                        addRuleRow(includeContainer, rule, specificVal);
                    });
                }
                
                // Load exclude rules
                var excludeRules = templateData.exclude && templateData.exclude.rule ? templateData.exclude.rule : [];
                var excludeSpecific = templateData.exclude && templateData.exclude.specific ? templateData.exclude.specific : [];
                
                excludeRules.forEach(function(rule, idx) {
                    var specificVal = (rule === 'specifics' && excludeSpecific.length) ? excludeSpecific.join(',') : '';
                    addRuleRow(excludeContainer, rule, specificVal);
                });

                // Load user roles (rows)
                var userRoles = templateData.userRoles || [];
                if (!Array.isArray(userRoles) || userRoles.length === 0) {
                    userRoles = ['all'];
                }
                userRoles.forEach(function(role) {
                    addUserRoleRow(userRolesContainer, role, false);
                });
            };
            
            // Condition button click handlers
            conditionButtons.forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var templateData = JSON.parse(btn.getAttribute('data-template'));
                    loadConditionsData(templateData);
                    openConditionsModal();
                });
            });
            
            // Add rule buttons
            document.querySelectorAll('.ka-hf-add-rule-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var ruleType = btn.getAttribute('data-rule-type');
                    var container = document.getElementById('ka-hf-' + ruleType + '-rules');
                    addRuleRow(container, 'basic-global', '');
                });
            });

            if (addUserRoleBtn) {
                addUserRoleBtn.addEventListener('click', function() {
                    addUserRoleRow(userRolesContainer, 'all', false);
                });
            }
            
            // Close buttons
            conditionsCloseButtons.forEach(function(btn) {
                btn.addEventListener('click', closeConditionsModal);
            });
            
            // Backdrop click
            if (conditionsModal) {
                conditionsModal.addEventListener('click', function(e) {
                    if (e.target === conditionsModal) {
                        closeConditionsModal();
                    }
                });
            }
            
            // Save conditions via AJAX
            if (saveConditionsBtn) {
                saveConditionsBtn.addEventListener('click', function() {
                    var savingOverlay = document.getElementById('ka-hf-conditions-saving');
                    savingOverlay.style.display = 'flex';
                    
                    var templateId = document.getElementById('ka-hf-cond-template-id').value;
                    var templateType = document.getElementById('ka-hf-cond-template-type').value;

                    // Collect user roles
                    var selectedRoles = [];
                    document.querySelectorAll('#ka-hf-user-roles .ka-hf-user-role-select').forEach(function(sel) {
                        if (sel && sel.value) {
                            selectedRoles.push(sel.value);
                        }
                    });
                    // De-dupe and default
                    selectedRoles = Array.from(new Set(selectedRoles));
                    if (selectedRoles.length === 0) {
                        selectedRoles = ['all'];
                    }
                    if (selectedRoles.indexOf('all') !== -1) {
                        selectedRoles = ['all'];
                    }
                    
                    // Collect include rules
                    var includeRules = [];
                    document.querySelectorAll('#ka-hf-include-rules .ka-hf-rule-row').forEach(function(row) {
                        var rule = row.querySelector('.ka-hf-rule-select').value;
                        var specific = row.querySelector('.ka-hf-specific-input').value;
                        includeRules.push({ rule: rule, specific: specific });
                    });
                    
                    // Collect exclude rules
                    var excludeRules = [];
                    document.querySelectorAll('#ka-hf-exclude-rules .ka-hf-rule-row').forEach(function(row) {
                        var rule = row.querySelector('.ka-hf-rule-select').value;
                        var specific = row.querySelector('.ka-hf-specific-input').value;
                        excludeRules.push({ rule: rule, specific: specific });
                    });
                    
                    // Build form data
                    var formData = new FormData();
                    formData.append('action', 'ka_hf_save_conditions');
                    formData.append('nonce', kaHfNonce);
                    formData.append('template_id', templateId);
                    formData.append('template_type', templateType);
                    
                    // Append multiple user roles
                    selectedRoles.forEach(function(role, idx) {
                        formData.append('user_roles[' + idx + ']', role);
                    });
                    
                    includeRules.forEach(function(rule, idx) {
                        formData.append('include_rules[' + idx + '][rule]', rule.rule);
                        formData.append('include_rules[' + idx + '][specific]', rule.specific);
                    });
                    
                    excludeRules.forEach(function(rule, idx) {
                        formData.append('exclude_rules[' + idx + '][rule]', rule.rule);
                        formData.append('exclude_rules[' + idx + '][specific]', rule.specific);
                    });
                    
                    fetch(kaHfAjaxUrl, {
                        method: 'POST',
                        body: formData
                    })
                    .then(function(response) { return response.json(); })
                    .then(function(data) {
                        savingOverlay.style.display = 'none';
                        if (data.success) {
                            closeConditionsModal();
                            // Reload page to show updated conditions
                            window.location.reload();
                        } else {
                            alert(data.data && data.data.message ? data.data.message : 'Error saving conditions');
                        }
                    })
                    .catch(function(err) {
                        savingOverlay.style.display = 'none';
                        alert('Error saving conditions');
                        console.error(err);
                    });
                });
            }
        });
        </script>
        <?php
    }

    public function disableScreenOptions($show_screen, $screen)
    {
        if ($screen->id === 'edit-king-addons-el-hf') {
            return false;
        }
        return $show_screen;
    }

    function king_addons_el_hf_get_posts_by_query()
    {
        // Security fix: Add authorization check
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }

        check_ajax_referer('king-addons-el-hf-get-posts-by-query', 'nonce');

        $search_string = isset($_POST['q']) ? sanitize_text_field($_POST['q']) : '';
        $result = array();

        $args = array(
            'public' => true,
            '_builtin' => false,
        );

        $output = 'names';
        $operator = 'and';
        $post_types = get_post_types($args, $output, $operator);

        unset($post_types['elementor-hf']);

        $post_types['Posts'] = 'post';
        $post_types['Pages'] = 'page';

        foreach ($post_types as $key => $post_type) {
            $data = array();

            add_filter('posts_search', array($this, 'search_only_titles'), 10, 2);

            $query = new WP_Query(
                array(
                    's' => $search_string,
                    'post_type' => $post_type,
                    'posts_per_page' => -1,
                )
            );

            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $title = get_the_title();
                    $title .= (0 != $query->post->post_parent) ? ' (' . get_the_title($query->post->post_parent) . ')' : '';
                    $id = get_the_id();
                    $data[] = array(
                        'id' => 'post-' . $id,
                        'text' => $title,
                    );
                }
            }

            if (is_array($data) && !empty($data)) {
                $result[] = array(
                    'text' => $key,
                    'children' => $data,
                );
            }
        }

        wp_reset_postdata();

        $args = array(
            'public' => true,
        );

        $output = 'objects';
        $taxonomies = get_taxonomies($args, $output, $operator);

        foreach ($taxonomies as $taxonomy) {
            $terms = get_terms(
                $taxonomy->name,
                array(
                    'orderby' => 'count',
                    'hide_empty' => 0,
                    'name__like' => $search_string,
                )
            );

            $data = array();

            $label = ucwords($taxonomy->label);

            if (!empty($terms)) {
                foreach ($terms as $term) {

                    $data[] = array(
                        'id' => 'tax-' . $term->term_id,
                        'text' => $term->name . ' archive page',
                    );

                    $data[] = array(
                        'id' => 'tax-' . $term->term_id . '-single-' . $taxonomy->name,
                        'text' => 'All singulars from ' . $term->name,
                    );
                }
            }

            if (is_array($data) && !empty($data)) {
                $result[] = array(
                    'text' => $label,
                    'children' => $data,
                );
            }
        }

        wp_send_json($result);
    }

    public function initialize_options()
    {
        self::$user_selection = self::get_user_selections();
        self::$location_selection = self::getLocationSelections();
    }

    public function renderAdminCustomHeader()
    {
        $current_screen = get_current_screen()->id;
        if ($current_screen !== 'edit-king-addons-el-hf'
            && $current_screen !== 'header-footer_page_king-addons-el-hf-settings') {
            return;
        }

        ?>
        <div class="king-addons-pb-settings-page-header">
            <h1><?php esc_html_e('Elementor Header & Footer Builder', 'king-addons'); ?></h1>
            <p>
                <?php esc_html_e('Create fully customizable headers and footers with display conditions to control where they appear', 'king-addons'); ?>
            </p>
            <div class="king-addons-pb-preview-buttons">
                <a href="<?php echo admin_url('post-new.php?post_type=king-addons-el-hf'); ?>">
                    <div class="king-addons-pb-user-template">
                        <span><?php esc_html_e('Create New', 'king-addons'); ?></span>
                        <span class="plus-icon">+</span>
                    </div>
                </a>
                <?php if (!king_addons_freemius()->can_use_premium_code__premium_only()): ?>
                    <div class="kng-promo-btn-wrap">
                        <a href="https://kingaddons.com/pricing/?utm_source=king-addons-hf-builder" target="_blank">
                            <div class="kng-promo-btn-txt">
                                <?php esc_html_e('Unlock Premium Features & 650+ Templates Today!', 'king-addons'); ?>
                            </div>
                            <img width="16px"
                                 src="<?php echo esc_url(KING_ADDONS_URL) . 'includes/admin/img/share-v2.svg'; ?>"
                                 alt="<?php echo esc_html__('Open link in the new tab', 'king-addons'); ?>">
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php

        $counts = wp_count_posts('king-addons-el-hf');
        $total = (int)$counts->publish + (int)$counts->draft;

        if (0 === $total) {
            echo '<div class="notice notice-info">';
            echo '<p>';
            echo esc_html__("Create the first header or footer by clicking the 'Create New' button above.", 'king addons');
            echo '</p>';
            echo '</div>';
        }

    }

    function addPostType(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $labels = [
            'name' => esc_html__('Elementor Header & Footer Builder', 'king-addons'),
            'singular_name' => esc_html__('Elementor Header & Footer Builder', 'king-addons'),
            'menu_name' => esc_html__('Elementor Header & Footer Builder', 'king-addons'),
            'name_admin_bar' => esc_html__('Elementor Header & Footer Builder', 'king-addons'),
            'add_new' => esc_html__('Add New', 'king-addons'),
            'add_new_item' => esc_html__('Add New', 'king-addons'),
            'new_item' => esc_html__('New Template', 'king-addons'),
            'edit_item' => esc_html__('Edit Template', 'king-addons'),
            'view_item' => esc_html__('View Template', 'king-addons'),
            'all_items' => esc_html__('All Templates', 'king-addons'),
            'search_items' => esc_html__('Search Templates', 'king-addons'),
            'parent_item_colon' => esc_html__('Parent Templates:', 'king-addons'),
            'not_found' => esc_html__('No Templates found.', 'king-addons'),
            'not_found_in_trash' => esc_html__('No Templates found in Trash.', 'king-addons'),
        ];

        $args = [
            'labels' => $labels,
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => false,
            'show_in_nav_menus' => false,
            'exclude_from_search' => true,
            'capability_type' => 'post',
            'hierarchical' => false,
            'menu_icon' => 'dashicons-editor-kitchensink',
            'supports' => ['title', 'editor', 'thumbnail', 'elementor'],
            'show_in_rest' => true,
        ];

        register_post_type('king-addons-el-hf', $args);

        if (false === get_option('king_addons_HFB_flushed_rewrite_rules')) {
            add_option('king_addons_HFB_flushed_rewrite_rules', true);
            flush_rewrite_rules();
        }
    }

    function registerMetabox()
    {
        add_meta_box(
            'king-addons-el-hf-meta-box',
            esc_html__('Elementor Header & Footer Builder Options', 'king-addons'),
            [$this, 'renderMetabox'],
            'king-addons-el-hf',
            'normal',
            'high'
        );
    }

    function renderMetabox($post)
    {
        $values = get_post_custom($post->ID);
        $template_type = isset($values['king_addons_el_hf_template_type']) ? esc_attr(sanitize_text_field($values['king_addons_el_hf_template_type'][0])) : '';
        $display_on_canvas = isset($values['king-addons-el-hf-display-on-canvas']);

        wp_nonce_field('king_addons_el_hf_meta_nounce', 'king_addons_el_hf_meta_nounce');
        ?>
        <table class="king-addons-el-hf-options-table widefat">
            <tbody>
            <tr class="king-addons-el-hf-options-row type-of-template">
                <td class="king-addons-el-hf-options-row-heading">
                    <label for="king_addons_el_hf_template_type"><strong><?php esc_html_e('Type of Template', 'king-addons'); ?></strong></label>
                </td>
                <td class="king-addons-el-hf-options-row-content">
                    <select name="king_addons_el_hf_template_type" id="king_addons_el_hf_template_type">
                        <option value="king_addons_el_hf_not_selected" <?php selected($template_type, ''); ?>><?php esc_html_e('Select Option', 'king-addons'); ?></option>
                        <option value="king_addons_el_hf_type_header" <?php selected($template_type, 'king_addons_el_hf_type_header'); ?>><?php esc_html_e('Header', 'king-addons'); ?></option>
                        <option value="king_addons_el_hf_type_footer" <?php selected($template_type, 'king_addons_el_hf_type_footer'); ?>><?php esc_html_e('Footer', 'king-addons'); ?></option>
                    </select>
                </td>
            </tr>
            <?php
            $this->display_rules_tab();

            ?>
            <tr class="king-addons-el-hf-options-row enable-for-canvas">
                <td class="king-addons-el-hf-options-row-heading">
                    <label for="king-addons-el-hf-display-on-canvas">
                        <strong><?php esc_html_e('Enable Layout for Elementor Canvas Template?', 'king-addons'); ?></strong>
                    </label>
                    <p><?php esc_html_e('Enabling this option will display this layout on pages using Elementor Canvas Template', 'king-addons'); ?></p>
                </td>
                <td class="king-addons-el-hf-options-row-content">
                    <input type="checkbox" id="king-addons-el-hf-display-on-canvas"
                           name="king-addons-el-hf-display-on-canvas"
                           value="1" <?php checked($display_on_canvas); ?> />
                </td>
            </tr>
            </tbody>
        </table>
        <?php
    }


    public function admin_styles()
    {
        wp_enqueue_script('king-addons-el-hf-select2', KING_ADDONS_URL . 'includes/extensions/Header_Footer_Builder/select2.js', array('jquery'), KING_ADDONS_VERSION, true);

        wp_register_script(
            'king-addons-el-hf-target-rule',
            KING_ADDONS_URL . 'includes/extensions/Header_Footer_Builder/conditions-target.js',
            array(
                'jquery',
                'king-addons-el-hf-select2',
            ),
            KING_ADDONS_VERSION,
            true
        );

        wp_enqueue_script('king-addons-el-hf-target-rule');

        wp_register_script(
            'king-addons-el-hf-user-role',
            KING_ADDONS_URL . 'includes/extensions/Header_Footer_Builder/conditions-user.js',
            array(
                'jquery',
            ),
            KING_ADDONS_VERSION,
            true
        );

        wp_enqueue_script('king-addons-el-hf-user-role');

        wp_register_style('king-addons-el-hf-select2', KING_ADDONS_URL . 'includes/extensions/Header_Footer_Builder/select2.css', '', KING_ADDONS_VERSION);
        wp_enqueue_style('king-addons-el-hf-select2');
        wp_register_style('king-addons-el-hf-target-rule', KING_ADDONS_URL . 'includes/extensions/Header_Footer_Builder/conditions.css', '', KING_ADDONS_VERSION);
        wp_enqueue_style('king-addons-el-hf-target-rule');
        wp_enqueue_script('king-addons-el-hf-script', KING_ADDONS_URL . 'includes/extensions/Header_Footer_Builder/admin.js', array('jquery'), KING_ADDONS_VERSION);

        $localize_vars = array(
            'please_enter' => __('Please enter', 'king-addons'),
            'please_delete' => __('Please delete', 'king-addons'),
            'more_char' => __('or more characters', 'king-addons'),
            'character' => __('character', 'king-addons'),
            'loading' => __('Loading more results…', 'king-addons'),
            'only_select' => __('You can only select', 'king-addons'),
            'item' => __('item', 'king-addons'),
            'char_s' => __('s', 'king-addons'),
            'no_result' => __('No results found', 'king-addons'),
            'searching' => __('Searching…', 'king-addons'),
            'not_loader' => __('The results could not be loaded.', 'king-addons'),
            'search' => __('Search pages / post / categories', 'king-addons'),
            'ajax_nonce' => wp_create_nonce('king-addons-el-hf-get-posts-by-query'),
        );
        wp_localize_script('king-addons-el-hf-select2', 'kngRules', $localize_vars);

    }

    public function display_rules_tab()
    {
        $this->admin_styles();
        $include_locations = get_post_meta(get_the_id(), 'king_addons_el_hf_target_include_locations', true);
        $exclude_locations = get_post_meta(get_the_id(), 'king_addons_el_hf_target_exclude_locations', true);
        $users = get_post_meta(get_the_id(), 'king_addons_el_hf_target_user_roles', true);
        ?>
        <tr class="king-addons-el-hf-target-rules-row king-addons-el-hf-options-row">
            <td class="king-addons-el-hf-target-rules-row-heading king-addons-el-hf-options-row-heading">
                <label><strong><?php esc_html_e('Display On', 'king-addons'); ?></strong></label>
                <p><?php esc_html_e('Add locations for where this template should appear', 'king-addons'); ?></p>
            </td>
            <td class="king-addons-el-hf-target-rules-row-content king-addons-el-hf-options-row-content">
                <?php
                self::target_rule_settings_field(
                    'king-addons-el-hf-target-rules-location',
                    [
                        'title' => __('Display Rules', 'king-addons'),
                        'value' => '[{"type":"basic-global","specific":null}]',
                        'tags' => 'site,enable,target,pages',
                        'rule_type' => 'display',
                        'add_rule_label' => __('Add Display Rule', 'king-addons'),
                    ],
                    $include_locations
                );
                ?>
            </td>
        </tr>
        <tr class="king-addons-el-hf-target-rules-row king-addons-el-hf-options-row">
            <td class="king-addons-el-hf-target-rules-row-heading king-addons-el-hf-options-row-heading">
                <label><strong><?php esc_html_e('Do Not Display On', 'king-addons'); ?></strong></label>
                <p><?php esc_html_e('Add locations for where this template should not appear', 'king-addons'); ?></p>
            </td>
            <td class="king-addons-el-hf-target-rules-row-content king-addons-el-hf-options-row-content">
                <?php
                self::target_rule_settings_field(
                    'king-addons-el-hf-target-rules-exclusion',
                    [
                        'title' => __('Exclude On', 'king-addons'),
                        'value' => '[]',
                        'tags' => 'site,enable,target,pages',
                        'add_rule_label' => __('Add Exclusion Rule', 'king-addons'),
                        'rule_type' => 'exclude',
                    ],
                    $exclude_locations
                );
                ?>
            </td>
        </tr>
        <tr class="king-addons-el-hf-target-rules-row king-addons-el-hf-options-row">
            <td class="king-addons-el-hf-target-rules-row-heading king-addons-el-hf-options-row-heading">
                <label><strong><?php esc_html_e('User Roles', 'king-addons'); ?></strong></label>
                <p><?php esc_html_e('Display custom template based on user role', 'king-addons'); ?></p>
            </td>
            <td class="king-addons-el-hf-target-rules-row-content king-addons-el-hf-options-row-content">
                <?php
                self::target_user_role_settings_field(
                    'king-addons-el-hf-target-rules-users',
                    [
                        'title' => __('Users', 'king-addons'),
                        'value' => '[]',
                        'tags' => 'site,enable,target,pages',
                        'add_rule_label' => __('Add User Rule', 'king-addons'),
                    ],
                    $users
                );
                ?>
            </td>
        </tr>
        <?php
    }

    public static function get_user_selections()
    {
        $selection_options = array(
            'basic' => array(
                'label' => __('Basic', 'king-addons'),
                'value' => array(
                    'all' => __('All', 'king-addons'),
                    'logged-in' => __('Logged In', 'king-addons'),
                    'logged-out' => __('Logged Out', 'king-addons'),
                ),
            ),

            'advanced' => array(
                'label' => __('Advanced', 'king-addons'),
                'value' => array(),
            ),
        );

        /* User roles */
        $roles = get_editable_roles();

        foreach ($roles as $slug => $data) {
            $selection_options['advanced']['value'][$slug] = $data['name'];
        }

        /**
         * Filter options displayed in the user select field of Display conditions.
         *
         * @since 1.5.0
         */
        return apply_filters('king-addons-el-hf_user_roles_list', $selection_options);
    }

    public static function target_user_role_settings_field($name, $settings, $value)
    {
        $input_name = $name;
        $add_rule_label = $settings['add_rule_label'] ?? __('Add Rule', 'king-addons');
        $saved_values = $value;
        $output = '';

        if (!isset(self::$user_selection) || empty(self::$user_selection)) {
            self::$user_selection = self::get_user_selections();
        }
        $selection_options = self::$user_selection;

        $output .= '<script type="text/html" id="tmpl-king-addons-el-hf-user-role-condition">';
        $output .= '<div class="king-addons-el-hf-user-role-condition king-addons-el-hf-user-role-{{data.id}}" data-rule="{{data.id}}" >';
        $output .= '<span class="user_role-condition-delete dashicons dashicons-dismiss"></span>';

        $output .= '<div class="user_role-condition-wrap" >';
        $output .= '<select name="' . esc_attr($input_name) . '[{{data.id}}]" class="user_role-condition form-control king-addons-el-hf-input">';
        $output .= '<option value="">' . __('Select', 'king-addons') . '</option>';

        foreach ($selection_options as $group_data) {
            $output .= '<optgroup label="' . $group_data['label'] . '">';
            foreach ($group_data['value'] as $opt_key => $opt_value) {
                $output .= '<option value="' . $opt_key . '">' . $opt_value . '</option>';
            }
            $output .= '</optgroup>';
        }
        $output .= '</select>';
        $output .= '</div>';
        $output .= '</div> <!-- king-addons-el-hf-user-role-condition -->';
        $output .= '</script>';

        /** @noinspection PhpConditionAlreadyCheckedInspection */
        if (!is_array($saved_values) || (is_array($saved_values) && empty($saved_values))) {
            $saved_values = array();
            $saved_values[0] = '';
        }

        $index = 0;

        $output .= '<div class="king-addons-el-hf-user-role-wrapper king-addons-el-hf-user-role-display-on-wrap" data-type="display">';
        $output .= '<div class="king-addons-el-hf-user-role-selector-wrapper king-addons-el-hf-user-role-display-on">';
        $output .= '<div class="user_role-builder-wrap">';
        foreach ($saved_values as $index => $data) {
            $output .= '<div class="king-addons-el-hf-user-role-condition king-addons-el-hf-user-role-' . $index . '" data-rule="' . $index . '" >';
            $output .= '<span class="user_role-condition-delete dashicons dashicons-dismiss"></span>';
            /* Condition Selection */
            $output .= '<div class="user_role-condition-wrap" >';
            $output .= '<select name="' . esc_attr($input_name) . '[' . $index . ']" class="user_role-condition form-control king-addons-el-hf-input">';
            $output .= '<option value="">' . __('Select', 'king-addons') . '</option>';

            foreach ($selection_options as $group_data) {
                $output .= '<optgroup label="' . $group_data['label'] . '">';
                foreach ($group_data['value'] as $opt_key => $opt_value) {
                    $output .= '<option value="' . $opt_key . '" ' . selected($data, $opt_key, false) . '>' . $opt_value . '</option>';
                }
                $output .= '</optgroup>';
            }
            $output .= '</select>';
            $output .= '</div>';
            $output .= '</div> <!-- king-addons-el-hf-user-role-condition -->';
        }
        $output .= '</div>';
        /* Add new rule */
        $output .= '<div class="user_role-add-rule-wrap">';
        $output .= '<a href="#" class="button" data-rule-id="' . absint($index) . '">' . $add_rule_label . '</a>';
        $output .= '</div>';
        $output .= '</div>';
        $output .= '</div>';

        echo $output;
    }

    public static function target_rule_settings_field($name, $settings, $value)
    {
        $input_name = $name;
        $rule_type = $settings['rule_type'] ?? 'target_rule';
        $add_rule_label = $settings['add_rule_label'] ?? __('Add Rule', 'king-addons');
        $saved_values = $value;
        $output = '';

        if (isset(self::$location_selection) || empty(self::$location_selection)) {
            self::$location_selection = self::getLocationSelections();
        }
        $selection_options = self::$location_selection;

        $output .= '<script type="text/html" id="tmpl-king-addons-el-hf-target-rule-' . $rule_type . '-condition">';

        $output .= '<div class="king-addons-el-hf-target-rule-condition king-addons-el-hf-target-rule-{{data.id}}" data-rule="{{data.id}}" >';
        $output .= '<span class="target_rule-condition-delete dashicons dashicons-dismiss"></span>';
        $output .= '<div class="target_rule-condition-wrap" >';

        $output .= '<select name="' . esc_attr($input_name) . '[rule][{{data.id}}]" class="target_rule-condition form-control king-addons-el-hf-input">';
        $output .= '<option value="">' . __('Select', 'king-addons') . '</option>';

        foreach ($selection_options as $group_data) {
            $output .= '<optgroup label="' . $group_data['label'] . '">';
            foreach ($group_data['value'] as $opt_key => $opt_value) {
                $output .= '<option value="' . $opt_key . '">' . $opt_value . '</option>';
            }
            $output .= '</optgroup>';
        }
        $output .= '</select>';

        $output .= '</div>';
        $output .= '</div> <!-- king-addons-el-hf-target-rule-condition -->';

        $output .= '<div class="target_rule-specific-page-wrap" style="display:none">';
        $output .= '<select name="' . esc_attr($input_name) . '[specific][]" class="target-rule-select2 target_rule-specific-page form-control king-addons-el-hf-input " multiple="multiple">';
        $output .= '</select>';
        $output .= '</div>';

        $output .= '</script>';

        $output .= '<div class="king-addons-el-hf-target-rule-wrapper king-addons-el-hf-target-rule-' . $rule_type . '-on-wrap" data-type="' . $rule_type . '">';
        $output .= '<div class="king-addons-el-hf-target-rule-selector-wrapper king-addons-el-hf-target-rule-' . $rule_type . '-on">';
        $output .= self::generate_target_rule_selector($rule_type, $selection_options, $input_name, $saved_values, $add_rule_label);
        $output .= '</div>';
        $output .= '</div>';

        echo $output;
    }

    public static function generate_target_rule_selector($type, $selection_options, $input_name, $saved_values, $add_rule_label)
    {
        $output = '<div class="target_rule-builder-wrap">';

        /** @noinspection PhpConditionAlreadyCheckedInspection */
        if (!is_array($saved_values) || (is_array($saved_values) && empty($saved_values))) {
            $saved_values = array();
            $saved_values['rule'][0] = '';
            $saved_values['specific'][0] = '';
        }

        $index = 0;
        if (is_array($saved_values) && is_array($saved_values['rule'])) {
            foreach ($saved_values['rule'] as $index => $data) {
                $output .= '<div class="king-addons-el-hf-target-rule-condition king-addons-el-hf-target-rule-' . $index . '" data-rule="' . $index . '" >';

                $output .= '<span class="target_rule-condition-delete dashicons dashicons-dismiss"></span>';
                $output .= '<div class="target_rule-condition-wrap" >';
                $output .= '<select name="' . esc_attr($input_name) . '[rule][' . $index . ']" class="target_rule-condition form-control king-addons-el-hf-input">';
                $output .= '<option value="">' . __('Select', 'king-addons') . '</option>';

                foreach ($selection_options as $group_data) {
                    $output .= '<optgroup label="' . $group_data['label'] . '">';
                    foreach ($group_data['value'] as $opt_key => $opt_value) {

                        $selected = '';

                        if ($data == $opt_key) {
                            $selected = 'selected="selected"';
                        }

                        $output .= '<option value="' . $opt_key . '" ' . $selected . '>' . $opt_value . '</option>';
                    }
                    $output .= '</optgroup>';
                }
                $output .= '</select>';
                $output .= '</div>';

                $output .= '</div>';

                $output .= '<div class="target_rule-specific-page-wrap" style="display:none">';
                $output .= '<select name="' . esc_attr($input_name) . '[specific][]" class="target-rule-select2 target_rule-specific-page form-control king-addons-el-hf-input " multiple="multiple">';

                if ('specifics' == $data && isset($saved_values['specific']) && null != $saved_values['specific'] && is_array($saved_values['specific'])) {
                    foreach ($saved_values['specific'] as $sel_value) {

                        if (strpos($sel_value, 'post-') !== false) {
                            $post_id = (int)str_replace('post-', '', $sel_value);
                            $post_title = get_the_title($post_id);
                            $output .= '<option value="post-' . $post_id . '" selected="selected" >' . $post_title . '</option>';
                        }

                        if (strpos($sel_value, 'tax-') !== false) {
                            $tax_data = explode('-', $sel_value);

                            $tax_id = (int)str_replace('tax-', '', $sel_value);
                            $term = get_term($tax_id);
                            $term_name = '';

                            if (!is_wp_error($term)) {
                                $term_taxonomy = ucfirst(str_replace('_', ' ', $term->taxonomy));

                                if (isset($tax_data[2]) && 'single' === $tax_data[2]) {
                                    $term_name = 'All singulars from ' . $term->name;
                                } else {
                                    $term_name = $term->name . ' - ' . $term_taxonomy;
                                }
                            }

                            $output .= '<option value="' . $sel_value . '" selected="selected" >' . $term_name . '</option>';
                        }
                    }
                }
                $output .= '</select>';
                $output .= '</div>';
            }
        }

        $output .= '</div>';

        $output .= '<div class="target_rule-add-rule-wrap">';
        $output .= '<a href="#" class="button" data-rule-id="' . absint($index) . '" data-rule-type="' . $type . '">' . $add_rule_label . '</a>';
        $output .= '</div>';

        if ('display' == $type) {
            $output .= '<div class="target_rule-add-exclusion-rule">';
            $output .= '<a href="#" class="button">' . __('Add Exclusion Rule', 'king-addons') . '</a>';
            $output .= '</div>';
        }

        return $output;
    }

    function saveMetaboxData($post_id)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!isset($_POST['king_addons_el_hf_meta_nounce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['king_addons_el_hf_meta_nounce'])), 'king_addons_el_hf_meta_nounce')) {
            return;
        }

        if (!current_user_can('edit_posts')) {
            return;
        }

        if (!isset($_POST['king-addons-el-hf-target-rules-location'])) {
            $target_locations = array(
                'rule' => array('basic-global'),
                'specific' => array(),
            );
        } else {
            $target_locations = self::getFormatRuleValue($_POST, 'king-addons-el-hf-target-rules-location');
            if (empty($target_locations)) {
                $target_locations = array(
                    'rule' => array('basic-global'),
                    'specific' => array(),
                );
            }
        }

        $target_exclusion = self::getFormatRuleValue($_POST, 'king-addons-el-hf-target-rules-exclusion');
        $target_users = [];

        if (isset($_POST['king-addons-el-hf-target-rules-users'])) {
            $target_users = array_map('sanitize_text_field', wp_unslash($_POST['king-addons-el-hf-target-rules-users']));
        }

        update_post_meta($post_id, 'king_addons_el_hf_target_include_locations', $target_locations);
        update_post_meta($post_id, 'king_addons_el_hf_target_exclude_locations', $target_exclusion);
        update_post_meta($post_id, 'king_addons_el_hf_target_user_roles', $target_users);

        if (isset($_POST['king_addons_el_hf_template_type'])) {
            update_post_meta($post_id, 'king_addons_el_hf_template_type', sanitize_text_field(wp_unslash($_POST['king_addons_el_hf_template_type'])));
        }

        if (isset($_POST['king-addons-el-hf-display-on-canvas'])) {
            update_post_meta($post_id, 'king-addons-el-hf-display-on-canvas', sanitize_text_field(wp_unslash($_POST['king-addons-el-hf-display-on-canvas'])));
        } else {
            delete_post_meta($post_id, 'king-addons-el-hf-display-on-canvas');
        }
    }

    function setCompatibility()
    {
        $template = get_template();
        $is_elementor_callable = defined('ELEMENTOR_VERSION') && is_callable('Elementor\Plugin::instance');

        if ($is_elementor_callable) {
            self::$elementor_instance = Elementor\Plugin::instance();

            // TODO: Add popular themes
            switch ($template) {
                case 'hello-elementor':
                    require_once(KING_ADDONS_PATH . 'includes/extensions/Header_Footer_Builder/themes/hello-elementor/ELHF_Hello_Elementor.php');
                    break;
                default:
                    add_action('init', [$this, 'setupSettingsPage']);
                    add_filter('king_addons_el_hf_settings_tabs', [$this, 'setupUnsupportedTheme']);
                    add_action('init', [$this, 'setupFallbackSupport']);
                    break;
            }
        }
    }

    public function setupUnsupportedTheme($settings_tabs = [])
    {
        if (!current_theme_supports('king-addons-elementor-header-footer')) {
            $settings_tabs['king_addons_el_hf_settings'] = [
                'name' => esc_html__('Display Settings', 'king-addons'),
                'url' => admin_url('edit.php?post_type=king-addons-el-hf&page=king-addons-el-hf-settings'),
            ];
        }
        return $settings_tabs;
    }

    public function setupFallbackSupport()
    {
        if (!current_theme_supports('king-addons-elementor-header-footer')) {
            // Default to Method 3 (Universal) for best compatibility
            $compatibility_option = get_option('king_addons_el_hf_compatibility_option', '3');

            if ('1' === $compatibility_option) {
                if (!class_exists('ELHF_Default_Method_1')) {
                    require_once(KING_ADDONS_PATH . 'includes/extensions/Header_Footer_Builder/themes/default/ELHF_Default_Method_1.php');
                }
            } elseif ('2' === $compatibility_option) {
                if (!class_exists('ELHF_Default_Method_2')) {
                    require_once(KING_ADDONS_PATH . 'includes/extensions/Header_Footer_Builder/themes/default/ELHF_Default_Method_2.php');
                }
            } else {
                // Method 3: Universal (combines all approaches for maximum compatibility)
                if (!class_exists('ELHF_Default_Method_3')) {
                    require_once(KING_ADDONS_PATH . 'includes/extensions/Header_Footer_Builder/themes/default/ELHF_Default_Method_3.php');
                }
            }
        }
    }

    function setupSettingsPage()
    {
        require_once(KING_ADDONS_PATH . 'includes/extensions/Header_Footer_Builder/ELHF_Settings_Page.php');
    }

    public static function renderHeader()
    {
        /** @noinspection SpellCheckingInspection */
        echo '<header id="masthead" class="king-addons-el-hf-header" itemscope="itemscope" itemtype="https://schema.org/WPHeader">';
        ?><p class="main-title" style="display: none;" itemprop="headline"><a
                href="<?php echo esc_url(get_bloginfo('url')); ?>"
                title="<?php echo esc_attr(get_bloginfo('name', 'display')); ?>"
                rel="home"><?php echo esc_html(get_bloginfo('name')); ?></a></p><?php
        self::getHeaderContent();
        echo '</header>';
    }

    public static function renderFooter()
    {
        /** @noinspection SpellCheckingInspection */
        echo '<footer id="colophon" class="king-addons-el-hf-footer" itemscope="itemscope" itemtype="https://schema.org/WPFooter" role="contentinfo">';
        self::getFooterContent();
        echo '</footer>';
    }

    public static function getHeaderContent()
    {
        $header_id = self::getHeaderID();
        if ($header_id) {
            // Ensure Elementor-generated CSS is included on non-Elementor pages.
            $with_css = true;
            echo self::$elementor_instance->frontend->get_builder_content_for_display($header_id, $with_css); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
    }

    public static function getFooterContent()
    {
        $footer_id = self::getFooterID();
        if ($footer_id) {
            echo '<div style="width: 100%;">';
            // Ensure Elementor-generated CSS is included on non-Elementor pages.
            $with_css = true;
            echo self::$elementor_instance->frontend->get_builder_content_for_display($footer_id, $with_css); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo '</div>';
        }
    }

    public static function getHeaderID()
    {
        $header_id = self::getSettings('king_addons_el_hf_type_header');

        if ('' === $header_id) {
            $header_id = false;
        }

        return apply_filters('king_addons_el_hf_get_header_id', $header_id);
    }

    public static function isHeaderEnabled()
    {
        $header_id = self::getSettings('king_addons_el_hf_type_header');
        $status = false;

        if ('' !== $header_id) {
            $status = true;
        }

        return apply_filters('king_addons_el_hf_header_enabled', $status);
    }

    public static function isFooterEnabled()
    {
        $footer_id = self::getSettings('king_addons_el_hf_type_footer');
        $status = false;

        if ('' !== $footer_id) {
            $status = true;
        }

        return apply_filters('king_addons_el_hf_footer_enabled', $status);
    }

    public static function getFooterID()
    {
        $footer_id = self::getSettings('king_addons_el_hf_type_footer');

        if ('' === $footer_id) {
            $footer_id = false;
        }

        return apply_filters('king_addons_el_hf_get_footer_id', $footer_id);
    }


    public static function getSettings($setting = '')
    {
        if ('king_addons_el_hf_type_header' == $setting || 'king_addons_el_hf_type_footer' == $setting) {
            $templates = self::getTemplateID($setting);
            $template = !is_array($templates) ? $templates : $templates[0];
            return apply_filters("king_addons_el_hf_get_settings_$setting", $template);
        }

        return null;
    }

    public static function getTemplateID($type)
    {
        $option = [
            'location' => 'king_addons_el_hf_target_include_locations',
            'exclusion' => 'king_addons_el_hf_target_exclude_locations',
            'users' => 'king_addons_el_hf_target_user_roles',
        ];

        $templates = self::getPostsByConditions('king-addons-el-hf', $option);

        // Prime meta cache to avoid N+1 queries when calling get_post_meta in loops below.
        // getPostsByConditions() may already have populated template IDs; caching is safe and cheap.
        if (!empty($templates)) {
            $template_ids = array_map(static function ($template) {
                return isset($template['id']) ? absint($template['id']) : 0;
            }, $templates);
            $template_ids = array_values(array_filter($template_ids));
            if (!empty($template_ids)) {
                update_meta_cache('post', $template_ids);
            }
        }

        foreach ($templates as $template) {
            if (get_post_meta(absint($template['id']), 'king_addons_el_hf_template_type', true) === $type) {
                // Polylang check - https://polylang.pro/doc/function-reference/
                if (function_exists('pll_current_language')) {
                    if (pll_current_language('slug') == pll_get_post_language($template['id'], 'slug')) {
                        return $template['id'];
                    }
                } else {
                    return $template['id'];
                }
            }
        }

        return '';
    }

    public static function getPostsByConditions($post_type, $option)
    {
        global $wpdb;
        global $post;

        // Security fix: Validate and sanitize post_type
        // Ensure $post is valid before accessing its properties
        if ($post_type) {
            $post_type = sanitize_key($post_type);
        } elseif ($post instanceof \WP_Post && !empty($post->post_type)) {
            $post_type = sanitize_key($post->post_type);
        } else {
            return [];
        }
        
        if (empty($post_type)) {
            return [];
        }

        if (is_array(self::$current_page_data) && isset(self::$current_page_data[$post_type])) {
            return apply_filters('king_addons_el_hf_get_display_posts_by_conditions', self::$current_page_data[$post_type], $post_type);
        }

        $current_page_type = self::getCurrentPageType();

        self::$current_page_data[$post_type] = array();

        $option['current_post_id'] = self::$current_page_data['ID'];
        $meta_header = self::getMetaOptionPost($post_type, $option);

        if (false === $meta_header) {
            $current_post_type = sanitize_key(get_post_type());
            $current_post_id = false;
            $q_obj = get_queried_object();

            $current_id = absint(get_the_id());

            // Check if WPML is active. Find WPML Object ID for current page.
            /** @noinspection SpellCheckingInspection */
            if (defined('ICL_SITEPRESS_VERSION')) {
                $default_lang = apply_filters('wpml_default_language', '');
                $current_lang = apply_filters('wpml_current_language', '');

                if ($default_lang !== $current_lang) {
                    $current_post_type = get_post_type($current_id);
                    $current_id = apply_filters('wpml_object_id', $current_id, $current_post_type, true, $default_lang);
                }
            }

            // Security fix: Sanitize location parameter
            $location = isset($option['location']) ? sanitize_key($option['location']) : '';
            if (empty($location)) {
                return [];
            }

            // Security fix: Use prepared statement to prevent SQL injection
            $query = $wpdb->prepare(
                "SELECT p.ID, pm.meta_value FROM {$wpdb->postmeta} as pm
                INNER JOIN {$wpdb->posts} as p ON pm.post_id = p.ID
                WHERE pm.meta_key = %s
                AND p.post_type = %s
                AND p.post_status = 'publish'",
                $location,
                $post_type
            );

            $orderby = ' ORDER BY p.post_date DESC';

            // Security fix: Build meta_args using safe placeholders and prepared statements
            $meta_conditions = ["pm.meta_value LIKE %s"];
            $meta_values = ['%"basic-global"%'];

            switch ($current_page_type) {
                case 'is_404':
                    $meta_conditions[] = "pm.meta_value LIKE %s";
                    $meta_values[] = '%"special-404"%';
                    break;
                case 'is_search':
                    $meta_conditions[] = "pm.meta_value LIKE %s";
                    $meta_values[] = '%"special-search"%';
                    break;
                case 'is_archive':
                case 'is_tax':
                case 'is_date':
                case 'is_author':
                    $meta_conditions[] = "pm.meta_value LIKE %s";
                    $meta_values[] = '%"basic-archives"%';
                    $meta_conditions[] = "pm.meta_value LIKE %s";
                    $meta_values[] = '%"' . sanitize_key($current_post_type) . '|all|archive"%';
                    
                    if ('is_tax' == $current_page_type && (is_category() || is_tag() || is_tax())) {
                        if (is_object($q_obj) && isset($q_obj->taxonomy) && isset($q_obj->term_id)) {
                            $meta_conditions[] = "pm.meta_value LIKE %s";
                            $meta_values[] = '%"' . sanitize_key($current_post_type) . '|all|taxarchive|' . sanitize_key($q_obj->taxonomy) . '"%';
                            $meta_conditions[] = "pm.meta_value LIKE %s";
                            $meta_values[] = '%"tax-' . absint($q_obj->term_id) . '"%';
                        }
                    } elseif ('is_date' == $current_page_type) {
                        $meta_conditions[] = "pm.meta_value LIKE %s";
                        $meta_values[] = '%"special-date"%';
                    } elseif ('is_author' == $current_page_type) {
                        $meta_conditions[] = "pm.meta_value LIKE %s";
                        $meta_values[] = '%"special-author"%';
                    }
                    break;
                case 'is_home':
                    $meta_conditions[] = "pm.meta_value LIKE %s";
                    $meta_values[] = '%"special-blog"%';
                    break;
                case 'is_front_page':
                    $current_post_id = $current_id;
                    $meta_conditions[] = "pm.meta_value LIKE %s";
                    $meta_values[] = '%"special-front"%';
                    $meta_conditions[] = "pm.meta_value LIKE %s";
                    $meta_values[] = '%"' . sanitize_key($current_post_type) . '|all"%';
                    $meta_conditions[] = "pm.meta_value LIKE %s";
                    $meta_values[] = '%"post-' . absint($current_id) . '"%';
                    break;
                case 'is_singular':
                    $current_post_id = $current_id;
                    $meta_conditions[] = "pm.meta_value LIKE %s";
                    $meta_values[] = '%"basic-singulars"%';
                    $meta_conditions[] = "pm.meta_value LIKE %s";
                    $meta_values[] = '%"' . sanitize_key($current_post_type) . '|all"%';
                    $meta_conditions[] = "pm.meta_value LIKE %s";
                    $meta_values[] = '%"post-' . absint($current_id) . '"%';
                    
                    if (is_object($q_obj) && isset($q_obj->post_type) && isset($q_obj->ID)) {
                        $taxonomies = get_object_taxonomies($q_obj->post_type);
                        $terms = wp_get_post_terms($q_obj->ID, $taxonomies);
                        foreach ($terms as $term) {
                            if (isset($term->term_id) && isset($term->taxonomy)) {
                                $meta_conditions[] = "pm.meta_value LIKE %s";
                                $meta_values[] = '%"tax-' . absint($term->term_id) . '-single-' . sanitize_key($term->taxonomy) . '"%';
                            }
                        }
                    }
                    break;
                case 'is_woo_shop_page':
                    if (function_exists('is_shop')) {
                        $meta_conditions[] = "pm.meta_value LIKE %s";
                        $meta_values[] = '%"special-woocommerce-shop"%';
                    }
                    break;
                case '':
                    $current_post_id = $current_id;
                    break;
            }
            
            // Build the final meta_args string using prepare
            $meta_args = '(' . implode(' OR ', $meta_conditions) . ')';

            // Security fix: Use prepared statement for the complete query
            $full_query = $wpdb->prepare(
                $query . ' AND ' . $meta_args . $orderby,
                ...$meta_values
            );
            
            $posts = $wpdb->get_results($full_query);

            foreach ($posts as $local_post) {
                $unserialized_location = maybe_unserialize($local_post->meta_value);
                if ($unserialized_location !== false) {
                    self::$current_page_data[$post_type][$local_post->ID] = array(
                        'id' => $local_post->ID,
                        'location' => $unserialized_location,
                    );
                }
            }

            // Prime meta cache for all candidate templates to avoid repeated get_post_meta queries
            // in removeExclusionRulePosts/removeUserRulePosts and getTemplateID.
            if (!empty(self::$current_page_data[$post_type])) {
                $candidate_ids = array_map('absint', array_keys(self::$current_page_data[$post_type]));
                $candidate_ids = array_values(array_filter($candidate_ids));
                if (!empty($candidate_ids)) {
                    update_meta_cache('post', $candidate_ids);
                }
            }

            $option['current_post_id'] = $current_post_id;

            self::removeExclusionRulePosts($post_type, $option);
            self::removeUserRulePosts($post_type, $option);
        }

        return apply_filters('king_addons_el_hf_get_display_posts_by_conditions', self::$current_page_data[$post_type], $post_type);
    }

    public static function getCurrentPageType(): ?string
    {
        if (null === self::$current_page_type) {
            $page_type = '';
            $current_id = false;

            if (is_404()) {
                $page_type = 'is_404';
            } elseif (is_search()) {
                $page_type = 'is_search';
            } elseif (is_archive()) {
                $page_type = 'is_archive';
                if (is_category() || is_tag() || is_tax()) {
                    $page_type = 'is_tax';
                } elseif (is_date()) {
                    $page_type = 'is_date';
                } elseif (is_author()) {
                    $page_type = 'is_author';
                } elseif (function_exists('is_shop')) {
                    /** @noinspection PhpUndefinedFunctionInspection */
                    if (is_shop()) {
                        $page_type = 'is_woo_shop_page';
                    }
                }
            } elseif (is_home()) {
                $page_type = 'is_home';
            } elseif (is_front_page()) {
                $page_type = 'is_front_page';
                $current_id = get_the_id();
            } elseif (is_singular()) {
                $page_type = 'is_singular';
                $current_id = get_the_id();
            } else {
                $current_id = get_the_id();
            }

            self::$current_page_data['ID'] = $current_id;
            self::$current_page_type = $page_type;
        }

        return self::$current_page_type;
    }

    public static function getMetaOptionPost($post_type, $option)
    {
        $page_meta = (isset($option['page_meta']) && '' != $option['page_meta']) ? $option['page_meta'] : false;

        if (false !== $page_meta) {
            $current_post_id = $option['current_post_id'] ?? false;
            $meta_id = get_post_meta($current_post_id, $option['page_meta'], true);

            if (false !== $meta_id && '' != $meta_id) {
                self::$current_page_data[$post_type][$meta_id] = array(
                    'id' => $meta_id,
                    'location' => '',
                );

                return self::$current_page_data[$post_type];
            }
        }

        return false;
    }

    public static function removeExclusionRulePosts($post_type, $option)
    {
        $exclusion = $option['exclusion'] ?? '';
        $current_post_id = $option['current_post_id'] ?? false;
        foreach (self::$current_page_data[$post_type] as $c_post_id => $c_data) {
            $exclusion_rules = get_post_meta($c_post_id, $exclusion, true);
            $is_exclude = self::parseLayoutDisplayCondition($current_post_id, $exclusion_rules);
            if ($is_exclude) {
                unset(self::$current_page_data[$post_type][$c_post_id]);
            }
        }
    }

    public static function removeUserRulePosts($post_type, $option)
    {
        $users = $option['users'] ?? '';

        foreach (self::$current_page_data[$post_type] as $c_post_id => $c_data) {
            $user_rules = get_post_meta($c_post_id, $users, true);
            $is_user = self::parseUserRoleCondition($user_rules);

            if (!$is_user) {
                unset(self::$current_page_data[$post_type][$c_post_id]);
            }
        }
    }

    public static function parseLayoutDisplayCondition($post_id, $rules): bool
    {
        $display = false;

        /** @noinspection PhpConditionCheckedByNextConditionInspection */
        if (isset($rules['rule']) && is_array($rules['rule']) && !empty($rules['rule'])) {
            foreach ($rules['rule'] as $rule) {
                if (strrpos($rule, 'all') !== false) {
                    $rule_case = 'all';
                } else {
                    $rule_case = $rule;
                }

                switch ($rule_case) {
                    case 'basic-global':
                        $display = true;
                        break;

                    case 'basic-singulars':
                        if (is_singular()) {
                            $display = true;
                        }
                        break;

                    case 'basic-archives':
                        if (is_archive()) {
                            $display = true;
                        }
                        break;

                    case 'special-404':
                        if (is_404()) {
                            $display = true;
                        }
                        break;

                    case 'special-search':
                        if (is_search()) {
                            $display = true;
                        }
                        break;

                    case 'special-blog':
                        if (is_home()) {
                            $display = true;
                        }
                        break;

                    case 'special-front':
                        if (is_front_page()) {
                            $display = true;
                        }
                        break;

                    case 'special-date':
                        if (is_date()) {
                            $display = true;
                        }
                        break;

                    case 'special-author':
                        if (is_author()) {
                            $display = true;
                        }
                        break;

                    case 'special-woocommerce-shop':
                        if (function_exists('is_shop')) {
                            if (is_shop()) {
                                $display = true;
                            }
                        }
                        break;

                    case 'all':
                        $rule_data = explode('|', $rule);

                        $post_type = $rule_data[0] ?? false;
                        $archive_type = $rule_data[2] ?? false;
                        $taxonomy = $rule_data[3] ?? false;
                        if (false === $archive_type) {
                            $current_post_type = get_post_type($post_id);
                            if (false !== $post_id && $current_post_type == $post_type) {
                                $display = true;
                            }
                        } else {
                            if (is_archive()) {
                                $current_post_type = get_post_type();
                                if ($current_post_type == $post_type) {
                                    if ('archive' == $archive_type) {
                                        $display = true;
                                    } elseif ('taxarchive' == $archive_type) {
                                        $obj = get_queried_object();
                                        $current_taxonomy = '';
                                        if ('' !== $obj && null !== $obj) {
                                            $current_taxonomy = $obj->taxonomy;
                                        }

                                        if ($current_taxonomy == $taxonomy) {
                                            $display = true;
                                        }
                                    }
                                }
                            }
                        }
                        break;

                    case 'specifics':
                        if (isset($rules['specific']) && is_array($rules['specific'])) {
                            foreach ($rules['specific'] as $specific_page) {
                                $specific_data = explode('-', $specific_page);
                                $specific_post_type = $specific_data[0] ?? false;
                                $specific_post_id = $specific_data[1] ?? false;
                                if ('post' == $specific_post_type) {
                                    if ($specific_post_id == $post_id) {
                                        $display = true;
                                    }
                                } elseif (isset($specific_data[2]) && ('single' == $specific_data[2]) && 'tax' == $specific_post_type) {
                                    if (is_singular()) {
                                        $term_details = get_term($specific_post_id);

                                        if (isset($term_details->taxonomy)) {
                                            $has_term = has_term((int)$specific_post_id, $term_details->taxonomy, $post_id);

                                            if ($has_term) {
                                                $display = true;
                                            }
                                        }
                                    }
                                } elseif ('tax' == $specific_post_type) {
                                    $tax_id = get_queried_object_id();
                                    if ($specific_post_id == $tax_id) {
                                        $display = true;
                                    }
                                }
                            }
                        }
                        break;

                    default:
                        break;
                }

                if ($display) {
                    break;
                }
            }
        }

        return $display;
    }

    public static function parseUserRoleCondition($rules): bool
    {
        $show_popup = true;

        if (is_array($rules) && !empty($rules)) {
            $show_popup = false;

            foreach ($rules as $rule) {
                switch ($rule) {
                    case '':
                    case 'all':
                        $show_popup = true;
                        break;

                    case 'logged-in':
                        if (is_user_logged_in()) {
                            $show_popup = true;
                        }
                        break;

                    case 'logged-out':
                        if (!is_user_logged_in()) {
                            $show_popup = true;
                        }
                        break;

                    default:
                        if (is_user_logged_in()) {
                            $current_user = wp_get_current_user();

                            if (isset($current_user->roles)
                                && is_array($current_user->roles)
                                && in_array($rule, $current_user->roles)
                            ) {
                                $show_popup = true;
                            }
                        }
                        break;
                }

                if ($show_popup) {
                    break;
                }
            }
        }

        return $show_popup;
    }

    public static function checkUserCanEdit()
    {
        if (is_singular('king-addons-el-hf') && !current_user_can('edit_posts')) {
            wp_redirect(site_url(), 301);
            die;
        }
    }

    public static function loadElementorCanvasTemplate($single_template)
    {
        global $post;

        // Safety check: ensure $post is a valid WP_Post object before accessing properties
        if (!$post instanceof \WP_Post) {
            return $single_template;
        }

        if ('king-addons-el-hf' === $post->post_type) {
            if (defined('ELEMENTOR_VERSION')) {
                $elementor_2_0_canvas = ELEMENTOR_PATH . '/modules/page-templates/templates/canvas.php';

                if (file_exists($elementor_2_0_canvas)) {
                    return $elementor_2_0_canvas;
                } else {
                    return ELEMENTOR_PATH . '/includes/page-templates/canvas.php';
                }
            }
        }

        return $single_template;
    }

    public function forceElementorCanvasTemplate($template)
    {
        if (!defined('ELEMENTOR_VERSION')) {
            return $template;
        }

        $post_id = 0;

        if (is_singular('king-addons-el-hf')) {
            $post_id = (int) get_queried_object_id();
        } elseif (isset($_GET['elementor-preview'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $post_id = (int) $_GET['elementor-preview'];
        }

        if ($post_id && 'king-addons-el-hf' === get_post_type($post_id)) {
            $elementor_2_0_canvas = ELEMENTOR_PATH . '/modules/page-templates/templates/canvas.php';
            if (file_exists($elementor_2_0_canvas)) {
                return $elementor_2_0_canvas;
            }
            return ELEMENTOR_PATH . '/includes/page-templates/canvas.php';
        }

        return $template;
    }

    public function forcePreviewQuery($query): void
    {
        if (is_admin() || !$query->is_main_query()) {
            return;
        }

        if (!isset($_GET['elementor-preview'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return;
        }

        $preview_id = (int) $_GET['elementor-preview']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (!$preview_id || 'king-addons-el-hf' !== get_post_type($preview_id)) {
            return;
        }

        $query->set('p', $preview_id);
        $query->set('post_type', 'king-addons-el-hf');
        $query->set('post_status', ['publish', 'draft', 'pending', 'private']);
    }

    public static function enqueueScripts(): void
    {
        $screen = get_current_screen();
        if ($screen->id === 'edit-king-addons-el-hf') {
            // todo - styles
//            wp_enqueue_style('king-addons-el-hf-style', KING_ADDONS_URL . 'includes/extensions/Header_Footer_Builder/header-footer-builder.css', '', KING_ADDONS_VERSION);
            wp_enqueue_style('king-addons-el-hf-style', KING_ADDONS_URL . 'includes/extensions/Header_Footer_Builder/admin.css', '', KING_ADDONS_VERSION);
        }
    }

    public static function columnHeadings($columns)
    {
        unset($columns['date']);
        $columns['king_addons_el_hf_edit_template'] = esc_html__('Edit Template', 'king-addons');
        $columns['king_addons_el_hf_type_of_template'] = esc_html__('Type of Template', 'king-addons');
        $columns['king_addons_el_hf_display_rules'] = esc_html__('Display Rules', 'king-addons');
        $columns['date'] = esc_html__('Date', 'king-addons');
        return $columns;
    }

    public static function columnContent($column, $post_id)
    {
        // Edit Template
        if ('king_addons_el_hf_edit_template' === $column) {
            echo '<a class="king-addons-el-hf-edit-template-btn" href="';
            echo './post.php?post=' . esc_attr($post_id) . '&action=edit';
            echo '">' . esc_html__('Edit Template', 'king-addons') . '</a>';
        }

        // Display Rules
        if ('king_addons_el_hf_display_rules' === $column) {

            $locations = get_post_meta($post_id, 'king_addons_el_hf_target_include_locations', true);
            if (!empty($locations)) {
                echo '<div style="margin-bottom: 5px;">';
                echo '<strong>';
                echo esc_html__('Display: ', 'king-addons');
                echo '</strong>';
                self::columnDisplayLocation($locations);
                echo '</div>';
            }

            $locations = get_post_meta($post_id, 'king_addons_el_hf_target_exclude_locations', true);
            if (!empty($locations)) {
                echo '<div style="margin-bottom: 5px;">';
                echo '<strong>';
                echo esc_html__('Exclusion: ', 'king-addons');
                echo '</strong>';
                self::columnDisplayLocation($locations);
                echo '</div>';
            }

            $users = get_post_meta($post_id, 'king_addons_el_hf_target_user_roles', true);
            if (isset($users) && is_array($users)) {
                if (!empty($users[0])) {
                    $user_label = [];
                    foreach ($users as $user) {
                        $user_label[] = self::get_user_by_key($user);
                    }
                    echo '<div>';
                    echo '<strong>Users: </strong>';
                    echo esc_html(join(', ', $user_label));
                    echo '</div>';
                }
            }

        }

        // Type of Template
        if ('king_addons_el_hf_type_of_template' === $column) {
            $template_type = get_post_meta($post_id, 'king_addons_el_hf_template_type', true);
            if (!empty($template_type)) {
                echo '<div style="margin-bottom: 5px;">';
                echo '<strong>';
                switch ($template_type) {
                    case 'king_addons_el_hf_type_header':
                        echo esc_html__('Header', 'king-addons');
                        break;
                    case 'king_addons_el_hf_type_footer':
                        echo esc_html__('Footer', 'king-addons');
                        break;
                    default:
                        echo esc_html__('Not selected', 'king-addons');
                        break;
                }
                echo '</strong>';
                echo '</div>';
            }
        }
    }

    public static function get_user_by_key($key)
    {
        if (!isset(self::$user_selection) || empty(self::$user_selection)) {
            self::$user_selection = self::get_user_selections();
        }
        $user_selection = self::$user_selection;

        if (isset($user_selection['basic']['value'][$key])) {
            return $user_selection['basic']['value'][$key];
        } elseif ($user_selection['advanced']['value'][$key]) {
            return $user_selection['advanced']['value'][$key];
        }
        return $key;
    }

    public static function columnDisplayLocation($locations)
    {
        $location_label = [];
        /** @noinspection PhpConditionAlreadyCheckedInspection */
        if (is_array($locations) && is_array($locations['rule']) && isset($locations['rule'])) {
            /** @noinspection PhpArraySearchInBooleanContextInspection */
            $index = array_search('specifics', $locations['rule']);
            /** @noinspection PhpConditionCheckedByNextConditionInspection */
            if (false !== $index && !empty($index)) {
                unset($locations['rule'][$index]);
            }
        }

        if (isset($locations['rule']) && is_array($locations['rule'])) {
            foreach ($locations['rule'] as $location) {
                $location_label[] = self::getLocation($location);
            }
        }

        if (isset($locations['specific']) && is_array($locations['specific'])) {
            foreach ($locations['specific'] as $location) {
                $location_label[] = self::getLocation($location);
            }
        }

        echo esc_html(join(', ', $location_label));
    }

    public static function getLocation($key)
    {
        if (!isset(self::$location_selection) || empty(self::$location_selection)) {
            self::$location_selection = self::getLocationSelections();
        }

        $location_selection = self::$location_selection;

        foreach ($location_selection as $location_grp) {
            if (isset($location_grp['value'][$key])) {
                return $location_grp['value'][$key];
            }
        }

        if (strpos($key, 'post-') !== false) {
            $post_id = (int)str_replace('post-', '', $key);
            return get_the_title($post_id);
        }

        if (strpos($key, 'tax-') !== false) {
            $tax_id = (int)str_replace('tax-', '', $key);
            $term = get_term($tax_id);

            if (!is_wp_error($term)) {
                $term_taxonomy = ucfirst(str_replace('_', ' ', $term->taxonomy));
                return $term->name . ' - ' . $term_taxonomy;
            } else {
                return '';
            }
        }

        return $key;
    }

    public static function getLocationSelections()
    {
        $args = array(
            'public' => true,
            '_builtin' => true,
        );

        $post_types = get_post_types($args, 'objects');
        unset($post_types['attachment']);

        $args['_builtin'] = false;
        $custom_post_type = get_post_types($args, 'objects');

        $post_types = apply_filters('king_addons_el_hf_location_rule_post_types', array_merge($post_types, $custom_post_type));

        if (!king_addons_freemius()->can_use_premium_code__premium_only()) {
            $special_pages = array(
                'special-404-none' => esc_html__('404 Page (Available in PRO)', 'king-addons'),
                'special-search' => esc_html__('Search Page', 'king-addons'),
                'special-blog-none' => esc_html__('Blog / Posts Page (Available in PRO)', 'king-addons'),
                'special-front' => esc_html__('Front Page', 'king-addons'),
                'special-date' => esc_html__('Date Archive', 'king-addons'),
                'special-author' => esc_html__('Author Archive', 'king-addons'),
            );
        } else {
            $special_pages = array(
                'special-404' => esc_html__('404 Page', 'king-addons'),
                'special-search' => esc_html__('Search Page', 'king-addons'),
                'special-blog' => esc_html__('Blog / Posts Page', 'king-addons'),
                'special-front' => esc_html__('Front Page', 'king-addons'),
                'special-date' => esc_html__('Date Archive', 'king-addons'),
                'special-author' => esc_html__('Author Archive', 'king-addons'),
            );
        }

        if (class_exists('WooCommerce')) {
            if (!king_addons_freemius()->can_use_premium_code__premium_only()) {
                $special_pages['special-woocommerce-shop-none'] = esc_html__('WooCommerce Shop Page (Available in PRO)', 'king-addons');
            } else {
                $special_pages['special-woocommerce-shop'] = esc_html__('WooCommerce Shop Page', 'king-addons');
            }
        }

        $selection_options = array(
            'basic' => array(
                'label' => esc_html__('Basic', 'king-addons'),
                'value' => array(
                    'basic-global' => esc_html__('Entire Website', 'king-addons'),
                    'basic-singulars' => esc_html__('All Singulars', 'king-addons'),
                    'basic-archives' => esc_html__('All Archives', 'king-addons'),
                ),
            ),

            'special-pages' => array(
                'label' => esc_html__('Special Pages', 'king-addons'),
                'value' => $special_pages,
            ),
        );

        if (!king_addons_freemius()->can_use_premium_code__premium_only()) {
            $selection_options['specific-target'] = array(
                'label' => esc_html__('Specific Target', 'king-addons'),
                'value' => array(
                    'specifics-none' => esc_html__('Specific Pages / Posts / Taxonomies, etc. (Available in PRO)', 'king-addons'),
                ),
            );
        } else {
            $selection_options['specific-target'] = array(
                'label' => esc_html__('Specific Target', 'king-addons'),
                'value' => array(
                    'specifics' => esc_html__('Specific Pages / Posts / Taxonomies, etc.', 'king-addons'),
                ),
            );
        }

        $args = array(
            'public' => true,
        );

        $taxonomies = get_taxonomies($args, 'objects');

        if (!empty($taxonomies)) {
            foreach ($taxonomies as $taxonomy) {

                if ('post_format' == $taxonomy->name) {
                    continue;
                }

                foreach ($post_types as $post_type) {
                    $post_opt = self::getPostTargetRuleOptions($post_type, $taxonomy);

                    if (isset($selection_options[$post_opt['post_key']])) {
                        if (!empty($post_opt['value']) && is_array($post_opt['value'])) {
                            foreach ($post_opt['value'] as $key => $value) {
                                if (!in_array($value, $selection_options[$post_opt['post_key']]['value'])) {
                                    $selection_options[$post_opt['post_key']]['value'][$key] = $value;
                                }
                            }
                        }
                    } else {
                        $selection_options[$post_opt['post_key']] = array(
                            'label' => $post_opt['label'],
                            'value' => $post_opt['value'],
                        );
                    }
                }
            }
        }

        return apply_filters('king_addons_el_hf_display_on_list', $selection_options);
    }

    public static function getPostTargetRuleOptions($post_type, $taxonomy): array
    {
        $post_key = str_replace(' ', '-', strtolower($post_type->label));
        $post_label = ucwords($post_type->label);
        $post_name = $post_type->name;
        $post_option = array();

        /* translators: %s is post label */
        $all_posts = sprintf(esc_html__('All %s', 'king-addons'), $post_label);
        $post_option[$post_name . '|all'] = $all_posts;

        if ('pages' != $post_key) {
            /* translators: %s is post label */
            $all_archive = sprintf(esc_html__('All %s Archive', 'king-addons'), $post_label);
            $post_option[$post_name . '|all|archive'] = $all_archive;
        }

        if (in_array($post_type->name, $taxonomy->object_type)) {
            $tax_label = ucwords($taxonomy->label);
            $tax_name = $taxonomy->name;

            /* translators: %s is taxonomy label */
            $tax_archive = sprintf(esc_html__('All %s Archive', 'king-addons'), $tax_label);

            $post_option[$post_name . '|all|taxarchive|' . $tax_name] = $tax_archive;
        }

        $post_output['post_key'] = $post_key;
        $post_output['label'] = $post_label;
        $post_output['value'] = $post_option;

        return $post_output;
    }

    public static function getFormatRuleValue($save_data, $key): array
    {
        $meta_value = array();

        if (isset($save_data[$key]['rule'])) {
            $save_data[$key]['rule'] = array_unique($save_data[$key]['rule']);
            if (isset($save_data[$key]['specific'])) {
                $save_data[$key]['specific'] = array_unique($save_data[$key]['specific']);
            }

            $index = array_search('', $save_data[$key]['rule']);
            if (false !== $index) {
                unset($save_data[$key]['rule'][$index]);
            }
            $index = array_search('specifics', $save_data[$key]['rule']);
            if (false !== $index) {
                unset($save_data[$key]['rule'][$index]);

                if (isset($save_data[$key]['specific']) && is_array($save_data[$key]['specific'])) {
                    $save_data[$key]['rule'][] = 'specifics';
                }
            }

            foreach ($save_data[$key] as $meta_key => $value) {
                if (!empty($value)) {
                    $meta_value[$meta_key] = array_map('esc_attr', $value);
                }
            }
            if (!isset($meta_value['rule']) || !in_array('specifics', $meta_value['rule'])) {
                $meta_value['specific'] = array();
            }

            if (empty($meta_value['rule'])) {
                $meta_value = array();
            }
        }

        return $meta_value;
    }
}