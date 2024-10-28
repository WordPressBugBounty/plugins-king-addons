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

        if ($this->hasElementorCompatibility()) {

            // Initial requirements check
            require_once(KING_ADDONS_PATH . 'includes/RequirementsCheck.php');

            // Freemius connect
            require_once(KING_ADDONS_PATH . 'includes/FreemiusInit.php');

            // Templates
            require_once(KING_ADDONS_PATH . 'includes/TemplatesMap.php');
            require_once(KING_ADDONS_PATH . 'includes/Templates.php');

            // Extensions
            require_once(KING_ADDONS_PATH . 'includes/extensions/Header_Footer_Builder/Header_Footer_Builder.php');
            Header_Footer_Builder::instance();

            // Admin
            require_once(KING_ADDONS_PATH . 'includes/Admin.php');

            // Additional
            require_once(KING_ADDONS_PATH . 'includes/controls/Ajax_Select2/Ajax_Select2.php');
            require_once(KING_ADDONS_PATH . 'includes/controls/Ajax_Select2/Ajax_Select2_API.php');

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
        $message = sprintf(
        /* translators: 1: Plugin name 2: Elementor */
            esc_html__('%1$s plugin requires %2$s plugin to be installed and activated.', 'king-addons'),
            esc_html__('King Addons', 'king-addons'),
            esc_html__('Elementor', 'king-addons')
        );
        echo '<div class="notice notice-error"><p>' . esc_html($message) . '</p></div>';
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
                require_once(KING_ADDONS_PATH . 'includes/widgets/' . $widget_class . '/' . $widget_class . '.php');
                $widgets_manager->register(new $path_widget_class);
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
    }

    function enqueueEditorStyles(): void
    {
        wp_enqueue_style(KING_ADDONS_ASSETS_UNIQUE_KEY . '-elementor-editor', KING_ADDONS_URL . 'includes/admin/css/elementor-editor.css', '', KING_ADDONS_VERSION);
    }

}

Core::instance();