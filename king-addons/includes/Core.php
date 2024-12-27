<?php
/**
 * Core class do all things at the start of the plugin
 */

namespace King_Addons;

use Elementor\Widgets_Manager;
use Elementor\Controls_Manager;

/** @noinspection SpellCheckingInspection */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

final class Core
{
    /**
     * Instance
     *
     * @var Core|null The single instance of the class.
     */
    private static ?Core $_instance = null;

    /**
     * Instance
     *
     * Ensures only one instance of the class is loaded or can be loaded.
     *
     * @return Core An instance of the class.
     * @since 1.0.0
     */
    public static function instance(): Core
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor
     *
     * Perform some compatibility checks to make sure basic requirements are meet.
     * If all compatibility checks pass, initialize the functionality.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        require_once(KING_ADDONS_PATH . 'includes/ModulesMap.php');
        require_once(KING_ADDONS_PATH . 'includes/LibrariesMap.php');

        if ($this->hasElementorCompatibility()) {

            require_once(KING_ADDONS_PATH . 'includes/elementor-constants.php');

            // Initial requirements check
            require_once(KING_ADDONS_PATH . 'includes/RequirementsCheck.php');

            // Templates Catalog
            if (KING_ADDONS_EXT_TEMPLATES_CATALOG) {
                require_once(KING_ADDONS_PATH . 'includes/TemplatesMap.php');
                require_once(KING_ADDONS_PATH . 'includes/Templates.php');
            }

            // Header & Footer Builder
            if (KING_ADDONS_EXT_HEADER_FOOTER_BUILDER) {
                require_once(KING_ADDONS_PATH . 'includes/extensions/Header_Footer_Builder/Header_Footer_Builder.php');
                Header_Footer_Builder::instance();
            }

            // Popup Builder
            if (KING_ADDONS_EXT_POPUP_BUILDER) {
                require_once(KING_ADDONS_PATH . 'includes/extensions/Popup_Builder/Popup_Builder.php');
                Popup_Builder::instance();
            }

            // Admin
            require_once(KING_ADDONS_PATH . 'includes/Admin.php');

            // Additional
            require_once(KING_ADDONS_PATH . 'includes/controls/Ajax_Select2/Ajax_Select2.php');
            require_once(KING_ADDONS_PATH . 'includes/controls/Ajax_Select2/Ajax_Select2_API.php');
            require_once(KING_ADDONS_PATH . 'includes/controls/Animations/Animations.php');
            require_once(KING_ADDONS_PATH . 'includes/controls/Animations/Button_Animations.php');
            require_once(KING_ADDONS_PATH . 'includes/widgets/Search/Search_Ajax.php');

            self::enableWidgetsByDefault();

            add_action('elementor/init', [$this, 'initElementor']);
            add_action('elementor/controls/controls_registered', [$this, 'registerControls']);

            self::enableFeatures();

            new Admin();
        }
    }

    function hasElementorCompatibility(): bool
    {
        // Check if Elementor installed and activated
        if (!did_action('elementor/loaded')) {
            add_action('admin_notices', [$this, 'showAdminNotice_ElementorRequired']);
            return false;
        }

        // Check for required Elementor version
        if (!version_compare(ELEMENTOR_VERSION, KING_ADDONS_MINIMUM_ELEMENTOR_VERSION, '>=')) {
            add_action('admin_notices', [$this, 'showAdminNotice_ElementorMinimumVersion']);
            return false;
        }

        return true;
    }

    function showAdminNotice_ElementorRequired(): void
    {
        $screen = get_current_screen();
        if (isset($screen->parent_file) && 'plugins.php' === $screen->parent_file && 'update' === $screen->id) {
            return;
        }

        if (isset(get_plugins()['elementor/elementor.php'])) {
            if (!current_user_can('activate_plugins') || is_plugin_active('elementor/elementor.php')) {
                return;
            }
            $plugin = 'elementor/elementor.php';
            $activation_url = wp_nonce_url('plugins.php?action=activate&amp;plugin=' . $plugin . '&amp;plugin_status=all&amp;paged=1&amp;s', 'activate-plugin_' . $plugin);
            $message = '<div class="error"><p>' . esc_html__('King Addons plugin is not working because you need to activate the Elementor plugin.', 'king-addons') . '</p>';
            $message .= '<p>' . sprintf('<a href="%s" class="button-primary">%s</a>', $activation_url, esc_html__('Activate Elementor now', 'king-addons')) . '</p></div>';
        } else {
            if (!current_user_can('install_plugins')) {
                return;
            }
            $install_url = wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=elementor'), 'install-plugin_elementor');
            $message = '<div class="error"><p>' . esc_html__('King Addons plugin is not working because you need to install the Elementor plugin.', 'king-addons') . '</p>';
            $message .= '<p>' . sprintf('<a href="%s" class="button-primary">%s</a>', $install_url, esc_html__('Install Elementor now', 'king-addons')) . '</p></div>';
        }
        echo $message;
    }

    function showAdminNotice_ElementorMinimumVersion(): void
    {
        $message = sprintf(
        /* translators: 1: Plugin name 2: Elementor 3: Required Elementor version */
            esc_html__('%1$s plugin requires %2$s plugin version %3$s or greater.', 'king-addons'),
            esc_html__('King Addons', 'king-addons'),
            esc_html__('Elementor', 'king-addons'),
            KING_ADDONS_MINIMUM_ELEMENTOR_VERSION
        );
        echo '<div class="notice notice-error"><p>' . esc_html($message) . '</p></div>';
    }

    public function initElementor(): void
    {
        add_action('elementor/elements/categories_registered', [$this, 'addWidgetCategory']);
        add_action('elementor/widgets/register', [$this, 'registerWidgets']);
        add_action('elementor/editor/after_enqueue_styles', [$this, 'enqueueEditorStyles']);
    }

    function addWidgetCategory($elements_manager): void
    {
        $elements_manager->add_category(
            'king-addons',
            [
                'title' => esc_html__('King Addons', 'king-addons'),
                'icon' => 'fa fa-plug'
            ]
        );
    }

    /**
     * Register Widgets
     *
     * Load widgets files and register new Elementor widgets.
     *
     * Fired by `elementor/widgets/register` action hook.
     *
     * @param Widgets_Manager $widgets_manager Elementor widgets manager.
     */
    function registerWidgets(Widgets_Manager $widgets_manager): void
    {
        $options = get_option('king_addons_options');

        foreach (ModulesMap::getModulesMapArray()['widgets'] as $widget_id => $widget) {

            if ($options[$widget_id] === 'enabled') {
                $widget_class = $widget['php-class'];
                $path_widget_class = "King_Addons\\" . $widget_class;

                // Include the base widget class
                require_once(KING_ADDONS_PATH . 'includes/widgets/' . $widget_class . '/' . $widget_class . '.php');

                if (king_addons_freemius()->can_use_premium_code__premium_only() && defined('KING_ADDONS_PRO_PATH')) {
                    if (isset($widget['has-pro'])) {
                        $pro_file_path = KING_ADDONS_PRO_PATH . 'includes/widgets/' . $widget_class . '_Pro/' . $widget_class . '_Pro.php';

                        if (file_exists($pro_file_path)) {
                            // Include the Pro version class if the file exists
                            require_once($pro_file_path);

                            $path_widget_class_pro = "King_Addons\\" . $widget_class . '_Pro';
                            $widgets_manager->register(new $path_widget_class_pro);

                        } else {
                            // error_log("Pro file not found: $pro_file_path. Registering base widget.");
                            $widgets_manager->register(new $path_widget_class);
                        }
                    } else {
                        // Register base widget if no 'has-pro' key
                        $widgets_manager->register(new $path_widget_class);
                    }
                } else {
                    // Register base widget if Freemius premium code not available
                    $widgets_manager->register(new $path_widget_class);
                }
            }
        }
    }


    function enableWidgetsByDefault(): void
    {
        $options = get_option('king_addons_options');

        foreach (ModulesMap::getModulesMapArray()['widgets'] as $widget_id => $widget) {

            if (!($options[$widget_id] ?? null)) {
                $options[$widget_id] = 'enabled';
                update_option('king_addons_options', $options);
            }
        }
    }

    function enableFeatures(): void
    {
        $options = get_option('king_addons_options');

        foreach (ModulesMap::getModulesMapArray()['features'] as $feature_id => $feature) {

            if (!($options[$feature_id] ?? null)) {
                $options[$feature_id] = 'enabled';
                update_option('king_addons_options', $options);
            }

            if ($options[$feature_id] === 'enabled') {
                $feature_class = $feature['php-class'];
                $path_feature_class = "King_Addons\\" . $feature_class;
                require_once(KING_ADDONS_PATH . 'includes/features/' . $feature_class . '/' . $feature_class . '.php');
                new $path_feature_class;
            }
        }
    }

    public function registerControls(Controls_Manager $controls_manager): void
    {
        $controls_manager->register(new AJAX_Select2\Ajax_Select2());
        $controls_manager->register(new Animations\Animations());
        $controls_manager->register(new Animations\Animations_Alternative());
        $controls_manager->register(new Button_Animations\Button_Animations());
    }

    function enqueueEditorStyles(): void
    {
        wp_enqueue_style(KING_ADDONS_ASSETS_UNIQUE_KEY . '-elementor-editor', KING_ADDONS_URL . 'includes/admin/css/elementor-editor.css', '', KING_ADDONS_VERSION);
    }

    public static function renderProFeaturesSection($module, $section, $type, $widget_name, $features): void
    {
        if (king_addons_freemius()->can_use_premium_code__premium_only()) {
            return;
        }

        $module->start_controls_section(
            'king_addons_pro_features_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON_PRO . '<span class="king-addons-pro-features-heading">' . esc_html__('Pro Features', 'king-addons') . '</span>',
                'tab' => $section ?: null,
            ]
        );

        $list_html = '<ul>' . implode('', array_map(fn($feature) => "<li>$feature</li>", $features)) . '</ul>';

        $module->add_control(
            'king_addons_pro_features_list',
            [
                'type' => $type,
                'raw' => $list_html . '<a class="king-addons-pro-features-cta-btn" href="https://kingaddons.com/pricing/?ref=kng-module-' . $widget_name . '-upgrade-pro" target="_blank">' . esc_html__('Learn More About Pro', 'king-addons') . '</a>',
                'content_classes' => 'king-addons-pro-features-list',
            ]
        );

        $module->end_controls_section();
    }

    public static function renderUpgradeProNotice($module, $controls_manager, $widget_name, $option, $condition = []): void
    {
        if (king_addons_freemius()->can_use_premium_code__premium_only()) {
            return;
        }

        $module->add_control(
            $option . '_pro_notice',
            [
                'raw' => 'This option is available<br> in the <strong><a href="https://kingaddons.com/pricing/?ref=kng-module-' . $widget_name . '-settings-upgrade-pro" target="_blank">Pro version</a></strong>',
                'type' => $controls_manager,
                'content_classes' => 'king-addons-pro-notice',
                'condition' => [
                    $option => $condition,
                ]
            ]
        );
    }

    public static function getCustomTypes($query, $exclude_defaults = true): array
    {
        $custom_types = $query === 'tax'
            ? get_taxonomies(['show_in_nav_menus' => true], 'objects')
            : get_post_types(['show_in_nav_menus' => true], 'objects');

        return array_filter(
            array_map(fn($type) => $type->label, $custom_types),
            fn($label, $key) => !$exclude_defaults || !in_array($key, ['post', 'page', 'category', 'post_tag']),
            ARRAY_FILTER_USE_BOTH
        );
    }
}

Core::instance();