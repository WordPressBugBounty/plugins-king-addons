<?php
/**
 * Site Preloader Extension.
 *
 * Provides a customizable preloader/loading animation displayed during page load.
 * Features include preset animations, custom styling, display rules, and Pro features
 * like custom templates, advanced conditions, and AJAX navigation support.
 *
 * @package King_Addons
 * @since 1.0.0
 */

namespace King_Addons;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Site Preloader main class.
 *
 * Handles admin settings UI, frontend preloader rendering, and all related functionality.
 */
final class Site_Preloader
{
    /**
     * Option key for storing preloader settings.
     *
     * @var string
     */
    public const OPTION_KEY = 'king_addons_site_preloader_settings';

    /**
     * Option key for storing preloader rules.
     *
     * @var string
     */
    public const RULES_OPTION_KEY = 'king_addons_site_preloader_rules';

    /**
     * Cookie name for tracking first visit.
     *
     * @var string
     */
    public const COOKIE_NAME = 'kng_preloader_shown';

    /**
     * Session storage key.
     *
     * @var string
     */
    public const SESSION_KEY = 'kng_preloader_session';

    /**
     * Singleton instance.
     *
     * @var Site_Preloader|null
     */
    private static ?Site_Preloader $instance = null;

    /**
     * Cached settings array.
     *
     * @var array<string, mixed>
     */
    private array $settings = [];

    /**
     * Available presets.
     *
     * @var array<string, array<string, mixed>>
     */
    private array $presets = [];

    /**
     * Returns the singleton instance.
     *
     * @return Site_Preloader
     */
    public static function instance(): Site_Preloader
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor. Initializes hooks and loads settings.
     */
    public function __construct()
    {
        $this->settings = $this->get_settings();
        $this->presets = $this->get_presets();

        // Admin hooks
        add_action('admin_menu', [$this, 'register_admin_menu'], 15);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('admin_post_king_addons_site_preloader_save', [$this, 'handle_save_settings']);
        add_action('admin_post_king_addons_site_preloader_export', [$this, 'handle_export']);
        add_action('admin_post_king_addons_site_preloader_import', [$this, 'handle_import']);
        add_action('admin_post_king_addons_site_preloader_reset', [$this, 'handle_reset']);

        // Frontend hooks
        add_action('wp_enqueue_scripts', [$this, 'maybe_enqueue_frontend_assets']);
        add_action('wp_body_open', [$this, 'render_preloader_markup'], 1);
        add_action('wp_footer', [$this, 'render_preloader_script'], 999);

        // AJAX hooks
        add_action('wp_ajax_king_addons_preloader_preview', [$this, 'ajax_preview']);
        add_action('wp_ajax_king_addons_preloader_save_rule', [$this, 'ajax_save_rule']);
        add_action('wp_ajax_king_addons_preloader_delete_rule', [$this, 'ajax_delete_rule']);
    }

    /**
     * Registers the admin submenu page.
     *
     * @return void
     */
    public function register_admin_menu(): void
    {
        add_submenu_page(
            'king-addons',
            esc_html__('Site Preloader', 'king-addons'),
            esc_html__('Site Preloader', 'king-addons'),
            'manage_options',
            'king-addons-site-preloader',
            [$this, 'render_admin_page']
        );
    }

    /**
     * Enqueues admin assets for the settings page.
     *
     * @param string $hook Current admin page hook.
     * @return void
     */
    public function enqueue_admin_assets(string $hook): void
    {
        if ($hook !== 'king-addons_page_king-addons-site-preloader') {
            return;
        }

        // Shared V3 admin styles
        wp_enqueue_style(
            'king-addons-admin-v3',
            KING_ADDONS_URL . 'includes/admin/layouts/shared/admin-v3-styles.css',
            [],
            KING_ADDONS_VERSION
        );

        // Frontend preloader animations (needed for admin preview)
        wp_enqueue_style(
            'king-addons-site-preloader-frontend',
            KING_ADDONS_URL . 'includes/extensions/Site_Preloader/assets/style.css',
            [],
            KING_ADDONS_VERSION
        );

        // Site Preloader specific admin styles
        wp_enqueue_style(
            'king-addons-site-preloader-admin',
            KING_ADDONS_URL . 'includes/extensions/Site_Preloader/assets/admin.css',
            ['king-addons-admin-v3', 'king-addons-site-preloader-frontend'],
            KING_ADDONS_VERSION
        );

        // Color picker
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');

        // Alpha-enabled WP Color Picker (same library used in Settings -> Lightbox Colors)
        wp_enqueue_script(
            'king-addons-wpcolorpicker-alpha',
            KING_ADDONS_URL . 'includes/assets/libraries/wpcolorpicker/wpcolorpicker.js',
            ['wp-color-picker'],
            KING_ADDONS_VERSION,
            true
        );

        // Media uploader
        wp_enqueue_media();

        // Admin JS
        wp_enqueue_script(
            'king-addons-site-preloader-admin',
            KING_ADDONS_URL . 'includes/extensions/Site_Preloader/assets/admin.js',
            ['jquery', 'wp-color-picker', 'king-addons-wpcolorpicker-alpha'],
            KING_ADDONS_VERSION,
            true
        );

        wp_localize_script('king-addons-site-preloader-admin', 'kngPreloaderAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('king_addons_site_preloader'),
            'presets' => $this->presets,
            'settings' => $this->settings,
            'isPro' => $this->is_pro(),
            'strings' => [
                'selectImage' => esc_html__('Select Image', 'king-addons'),
                'useImage' => esc_html__('Use This Image', 'king-addons'),
                'saved' => esc_html__('Settings saved successfully!', 'king-addons'),
                'error' => esc_html__('An error occurred. Please try again.', 'king-addons'),
                'confirmReset' => esc_html__('Are you sure you want to reset all settings to defaults?', 'king-addons'),
                'confirmDelete' => esc_html__('Are you sure you want to delete this rule?', 'king-addons'),
            ],
        ]);
    }

    /**
     * Conditionally enqueues frontend assets.
     *
     * @return void
     */
    public function maybe_enqueue_frontend_assets(): void
    {
        if (!$this->should_show_preloader()) {
            return;
        }

        wp_enqueue_style(
            'king-addons-site-preloader',
            KING_ADDONS_URL . 'includes/extensions/Site_Preloader/assets/style.css',
            [],
            KING_ADDONS_VERSION
        );

        wp_enqueue_script(
            'king-addons-site-preloader',
            KING_ADDONS_URL . 'includes/extensions/Site_Preloader/assets/script.js',
            [],
            KING_ADDONS_VERSION,
            false // Load in header for immediate availability
        );
    }

    /**
     * Renders the admin settings page.
     *
     * @return void
     */
    public function render_admin_page(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $this->settings = $this->get_settings();
        $settings = $this->settings; // For templates
        $is_pro = $this->is_pro();
        $presets = $this->presets;
        $rules = $this->get_rules();
        $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'dashboard';

        include __DIR__ . '/templates/admin-page.php';
    }

    /**
     * Handles saving settings from the admin form.
     *
     * @return void
     */
    public function handle_save_settings(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized access', 'king-addons'));
        }

        check_admin_referer('king_addons_site_preloader_save');

        $sanitized = $this->sanitize_settings($_POST);
        update_option(self::OPTION_KEY, $sanitized);
        $this->settings = $sanitized;

        wp_safe_redirect(add_query_arg([
            'page' => 'king-addons-site-preloader',
            'tab' => isset($_POST['current_tab']) ? sanitize_key($_POST['current_tab']) : 'settings',
            'updated' => 'true',
        ], admin_url('admin.php')));
        exit;
    }

    /**
     * Handles exporting settings as JSON.
     *
     * @return void
     */
    public function handle_export(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized access', 'king-addons'));
        }

        if (!$this->is_pro()) {
            wp_die(esc_html__('Export is a Pro feature', 'king-addons'));
        }

        check_admin_referer('king_addons_site_preloader_export');

        $export_data = [
            'version' => KING_ADDONS_VERSION,
            'settings' => $this->settings,
            'rules' => $this->get_rules(),
        ];

        $json = wp_json_encode($export_data, JSON_PRETTY_PRINT);

        nocache_headers();
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename=site-preloader-settings-' . gmdate('Y-m-d') . '.json');
        echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        exit;
    }

    /**
     * Handles importing settings from JSON.
     *
     * @return void
     */
    public function handle_import(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized access', 'king-addons'));
        }

        if (!$this->is_pro()) {
            wp_die(esc_html__('Import is a Pro feature', 'king-addons'));
        }

        check_admin_referer('king_addons_site_preloader_import');

        if (empty($_FILES['import_file']['tmp_name'])) {
            wp_safe_redirect(add_query_arg([
                'page' => 'king-addons-site-preloader',
                'tab' => 'import-export',
                'error' => 'no-file',
            ], admin_url('admin.php')));
            exit;
        }

        $file_content = file_get_contents($_FILES['import_file']['tmp_name']); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        $import_data = json_decode($file_content, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($import_data)) {
            wp_safe_redirect(add_query_arg([
                'page' => 'king-addons-site-preloader',
                'tab' => 'import-export',
                'error' => 'invalid-json',
            ], admin_url('admin.php')));
            exit;
        }

        // Import settings
        if (!empty($import_data['settings'])) {
            $sanitized = $this->sanitize_settings($import_data['settings']);
            update_option(self::OPTION_KEY, $sanitized);
        }

        // Import rules
        if (!empty($import_data['rules']) && is_array($import_data['rules'])) {
            update_option(self::RULES_OPTION_KEY, $import_data['rules']);
        }

        wp_safe_redirect(add_query_arg([
            'page' => 'king-addons-site-preloader',
            'tab' => 'import-export',
            'imported' => 'true',
        ], admin_url('admin.php')));
        exit;
    }

    /**
     * Handles resetting settings to defaults.
     *
     * @return void
     */
    public function handle_reset(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized access', 'king-addons'));
        }

        check_admin_referer('king_addons_site_preloader_reset');

        delete_option(self::OPTION_KEY);
        delete_option(self::RULES_OPTION_KEY);

        wp_safe_redirect(add_query_arg([
            'page' => 'king-addons-site-preloader',
            'tab' => 'import-export',
            'reset' => 'true',
        ], admin_url('admin.php')));
        exit;
    }

    /**
     * AJAX handler for live preview.
     *
     * @return void
     */
    public function ajax_preview(): void
    {
        check_ajax_referer('king_addons_site_preloader', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $preset_id = isset($_POST['preset']) ? sanitize_key($_POST['preset']) : 'spinner-circle';
        $settings = isset($_POST['settings']) ? $this->sanitize_settings($_POST['settings']) : [];

        ob_start();
        $this->render_preset_html($preset_id, $settings);
        $html = ob_get_clean();

        wp_send_json_success([
            'html' => $html,
            'css' => $this->generate_inline_css($settings),
        ]);
    }

    /**
     * AJAX handler for saving a display rule.
     *
     * @return void
     */
    public function ajax_save_rule(): void
    {
        check_ajax_referer('king_addons_site_preloader', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $rules = $this->get_rules();
        $rule_id = isset($_POST['rule_id']) ? sanitize_text_field($_POST['rule_id']) : '';
        
        // Generate new ID if empty or starts with 'new_'
        $is_new_rule = empty($rule_id) || strpos($rule_id, 'new_') === 0;
        $final_rule_id = $is_new_rule ? 'rule_' . wp_generate_uuid4() : $rule_id;
        
        // Parse pages array
        $pages = [];
        if (isset($_POST['pages'])) {
            if (is_array($_POST['pages'])) {
                $pages = array_map('absint', $_POST['pages']);
            } else {
                $pages = array_map('absint', explode(',', sanitize_text_field($_POST['pages'])));
            }
            $pages = array_filter($pages);
        }
        
        // Get action from 'rule_action' parameter (since 'action' is reserved for AJAX action name)
        $rule_action = isset($_POST['rule_action']) ? sanitize_key($_POST['rule_action']) : 'show';
        
        $rule = [
            'id' => $final_rule_id,
            'enabled' => isset($_POST['enabled']) && $_POST['enabled'] === '1',
            'action' => $rule_action, // show / hide / override
            'condition' => isset($_POST['condition']) ? sanitize_key($_POST['condition']) : 'specific_pages',
            'condition_value' => isset($_POST['condition_value']) ? sanitize_text_field($_POST['condition_value']) : '',
            'pages' => $pages,
            'priority' => isset($_POST['priority']) ? absint($_POST['priority']) : 10,
            'template' => isset($_POST['template']) ? sanitize_key($_POST['template']) : '',
            'override_colors' => isset($_POST['override_colors']) && $_POST['override_colors'] === '1',
            'bg_color' => $this->sanitize_color_value($_POST['bg_color'] ?? '', '#ffffff'),
            'accent_color' => $this->sanitize_color_value($_POST['accent_color'] ?? '', '#0071e3'),
        ];

        if (!$is_new_rule) {
            // Update existing rule
            $found = false;
            foreach ($rules as $key => $existing_rule) {
                if ($existing_rule['id'] === $rule_id) {
                    $rules[$key] = $rule;
                    $found = true;
                    break;
                }
            }
            // If not found, add as new
            if (!$found) {
                $rules[] = $rule;
            }
        } else {
            // Add new rule
            $rules[] = $rule;
        }

        update_option(self::RULES_OPTION_KEY, $rules);

        wp_send_json_success(['rule' => $rule, 'rules' => $rules]);
    }

    /**
     * AJAX handler for deleting a display rule.
     *
     * @return void
     */
    public function ajax_delete_rule(): void
    {
        check_ajax_referer('king_addons_site_preloader', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $rule_id = isset($_POST['rule_id']) ? sanitize_key($_POST['rule_id']) : '';
        if (empty($rule_id)) {
            wp_send_json_error(['message' => 'No rule ID provided']);
        }

        $rules = $this->get_rules();
        $rules = array_filter($rules, function ($rule) use ($rule_id) {
            return $rule['id'] !== $rule_id;
        });
        $rules = array_values($rules);

        update_option(self::RULES_OPTION_KEY, $rules);

        wp_send_json_success(['rules' => $rules]);
    }

    /**
     * Renders the preloader HTML markup in wp_body_open.
     *
     * @return void
     */
    public function render_preloader_markup(): void
    {
        if (!$this->should_show_preloader()) {
            return;
        }

        $settings = $this->settings;
        
        // Check if any rule overrides the settings
        $rule_result = $this->get_rule_action();
        $matched_rule = $rule_result['rule'];
        $action = $rule_result['action'];
        
        // Apply overrides only for "override" action
        if ($action === 'override' && $matched_rule) {
            // Override template if rule has one
            if (!empty($matched_rule['template'])) {
                $settings['template'] = $matched_rule['template'];
            }
            // Override colors if rule specifies
            if (!empty($matched_rule['override_colors'])) {
                if (!empty($matched_rule['bg_color'])) {
                    $settings['bg_color'] = $matched_rule['bg_color'];
                }
                if (!empty($matched_rule['accent_color'])) {
                    $settings['accent_color'] = $matched_rule['accent_color'];
                }
            }
        }
        
        $preset_id = $settings['template'] ?? 'spinner-circle';
        
        $trigger_type = $settings['trigger_type'] ?? 'always';
        $cookie_name = self::COOKIE_NAME . '_' . sanitize_key($trigger_type);

        $data_attrs = [
            'data-preset' => $preset_id,
            'data-trigger-type' => $trigger_type,
            'data-cookie-name' => $cookie_name,
            'data-hide-strategy' => $settings['hide_strategy'] ?? 'window_load',
            'data-min-display-time' => absint($settings['min_display_time'] ?? 500),
            'data-max-display-time' => absint($settings['max_display_time'] ?? 5000),
            'data-hide-animation' => $settings['hide_animation'] ?? 'fade',
            'data-animation-duration' => absint($settings['transition_duration'] ?? 400),
            'data-allow-skip' => !empty($settings['allow_skip']) ? 'true' : 'false',
            'data-skip-method' => $settings['skip_method'] ?? 'click',
            'data-lock-scroll' => !empty($settings['lock_scroll']) ? 'true' : 'false',
        ];
        
        $data_string = '';
        foreach ($data_attrs as $attr => $value) {
            $data_string .= ' ' . esc_attr($attr) . '="' . esc_attr($value) . '"';
        }
        
        // Debug: output matched rule info as HTML comment
        $debug_info = '';
        if (defined('WP_DEBUG') && WP_DEBUG && $matched_rule) {
            $debug_info = sprintf(
                '<!-- KNG Preloader: Matched rule ID=%s, action=%s, template=%s, condition=%s -->',
                esc_html($matched_rule['id'] ?? 'unknown'),
                esc_html($action),
                esc_html($matched_rule['template'] ?? 'default'),
                esc_html($matched_rule['condition'] ?? '')
            );
        }
        echo $debug_info;
        ?>
        <div id="kng-site-preloader" class="kng-site-preloader"<?php echo $data_string; ?> style="<?php echo esc_attr($this->get_inline_style($settings)); ?>"><?php /* Using template: <?php echo esc_html($preset_id); ?> */ ?>
            <div class="kng-site-preloader__overlay"></div>
            <div class="kng-site-preloader__content">
                <?php $this->render_preset_html($preset_id, $settings); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Gets the first matching rule for current page.
     * Rules are already sorted by priority (lower = higher priority).
     *
     * @return array|null The matching rule or null.
     */
    private function get_matching_rule(): ?array
    {
        $rules = $this->get_rules();
        
        // Sort by priority (lower number = higher priority)
        usort($rules, function($a, $b) {
            $priority_a = intval($a['priority'] ?? 10);
            $priority_b = intval($b['priority'] ?? 10);
            return $priority_a - $priority_b;
        });
        
        foreach ($rules as $rule) {
            // Skip disabled rules
            if (empty($rule['enabled'])) {
                continue;
            }
            
            // Check if rule matches current page
            if ($this->evaluate_rule_condition($rule)) {
                return $rule;
            }
        }
        
        return null;
    }

    /**
     * Get the action from a matching rule (show/hide/override).
     *
     * @return array{action: string, rule: array|null} Action and rule data.
     */
    private function get_rule_action(): array
    {
        $rule = $this->get_matching_rule();
        
        if ($rule === null) {
            return ['action' => 'default', 'rule' => null];
        }
        
        $action = $rule['action'] ?? 'show';
        
        return ['action' => $action, 'rule' => $rule];
    }

    /**
     * Renders the preloader initialization script.
     *
     * @return void
     */
    public function render_preloader_script(): void
    {
        // Script auto-initializes, no need for footer script
        // Config is passed via data attributes on the preloader element
    }

    /**
     * Generates inline CSS for preview styling.
     *
     * @param array<string,mixed> $settings Current settings.
     * @return string Inline CSS styles.
     */
    public function get_preview_styles(array $settings): string
    {
        $bg_color = $settings['bg_color'] ?? '#ffffff';
        $accent_color = $settings['accent_color'] ?? '#0071e3';
        $text_color = $settings['text_color'] ?? '#1d1d1f';
        $animation_size = absint($settings['spinner_size'] ?? 48);

        return sprintf(
            '--kng-preloader-bg: %s; --kng-preloader-accent: %s; --kng-preloader-text: %s; --kng-preloader-size: %dpx; background-color: %s;',
            esc_attr($bg_color),
            esc_attr($accent_color),
            esc_attr($text_color),
            $animation_size,
            esc_attr($bg_color)
        );
    }

    /**
     * Renders the HTML for a specific preset.
     *
     * @param string              $preset_id The preset identifier.
     * @param array<string,mixed> $settings  Current settings.
     * @return void
     */
    public function render_preset_html(string $preset_id, array $settings): void
    {
        $logo_enabled = !empty($settings['logo_enabled']);
        $logo_url = $settings['logo_url'] ?? '';
        $text_enabled = !empty($settings['text_enabled']);
        $text_content = $settings['text_content'] ?? '';

        // Logo
        if ($logo_enabled && !empty($logo_url)) {
            echo '<div class="kng-site-preloader__logo">';
            echo '<img src="' . esc_url($logo_url) . '" alt="' . esc_attr__('Loading', 'king-addons') . '" />';
            echo '</div>';
        }

        // Preset animation
        echo '<div class="kng-site-preloader__animation kng-site-preloader__animation--' . esc_attr($preset_id) . '">';
        
        switch ($preset_id) {
            case 'spinner-circle':
                echo '<div class="kng-preloader-spinner-circle"></div>';
                break;

            case 'dual-ring':
                echo '<div class="kng-preloader-dual-ring"></div>';
                break;

            case 'dots-bounce':
                echo '<div class="kng-preloader-dots-bounce">';
                echo '<span></span><span></span><span></span>';
                echo '</div>';
                break;

            case 'bar-loader':
                echo '<div class="kng-preloader-bar-loader">';
                echo '<div class="kng-preloader-bar-loader__bar"></div>';
                echo '</div>';
                break;

            case 'pulse-logo':
                echo '<div class="kng-preloader-pulse-logo">';
                if ($logo_enabled && !empty($logo_url)) {
                    echo '<img src="' . esc_url($logo_url) . '" alt="" />';
                } else {
                    echo '<div class="kng-preloader-pulse-logo__circle"></div>';
                }
                echo '</div>';
                break;

            case 'minimal-line':
                echo '<div class="kng-preloader-minimal-line">';
                echo '<div class="kng-preloader-minimal-line__track">';
                echo '<div class="kng-preloader-minimal-line__bar"></div>';
                echo '</div>';
                echo '</div>';
                break;

            case 'gradient-spinner':
                echo '<div class="kng-preloader-gradient-spinner"></div>';
                break;

            case 'fade-text':
                echo '<div class="kng-preloader-fade-text">';
                $loading_text = !empty($text_content) ? $text_content : __('Loading', 'king-addons');
                if (function_exists('mb_str_split')) {
                    $chars = mb_str_split($loading_text);
                } else {
                    $chars = preg_split('//u', $loading_text, -1, PREG_SPLIT_NO_EMPTY);
                }
                if (!is_array($chars)) {
                    $chars = str_split($loading_text);
                }
                foreach ($chars as $index => $char) {
                    echo '<span style="animation-delay: ' . (0.1 * $index) . 's">' . esc_html($char) . '</span>';
                }
                echo '</div>';
                break;

            case 'cube-grid':
                echo '<div class="kng-preloader-cube-grid">';
                for ($i = 1; $i <= 9; $i++) {
                    echo '<div class="kng-preloader-cube-grid__cube"></div>';
                }
                echo '</div>';
                break;

            case 'wave-bars':
                echo '<div class="kng-preloader-wave-bars">';
                for ($i = 1; $i <= 5; $i++) {
                    echo '<div class="kng-preloader-wave-bars__bar"></div>';
                }
                echo '</div>';
                break;

            case 'rotating-squares':
                echo '<div class="kng-preloader-rotating-squares">';
                echo '<div class="kng-preloader-rotating-squares__square"></div>';
                echo '<div class="kng-preloader-rotating-squares__square"></div>';
                echo '</div>';
                break;

            case 'morphing-circle':
                echo '<div class="kng-preloader-morphing-circle"></div>';
                break;

            default:
                echo '<div class="kng-preloader-spinner-circle"></div>';
        }

        echo '</div>';

        // Text
        if ($text_enabled && !empty($text_content) && $preset_id !== 'fade-text') {
            echo '<div class="kng-site-preloader__text">' . esc_html($text_content) . '</div>';
        }
    }

    /**
     * Generates inline CSS based on settings.
     *
     * @param array<string,mixed> $settings Current settings.
     * @return string
     */
    private function generate_inline_css(array $settings): string
    {
        $css = '';
        
        // Background
        $bg_type = $settings['bg_type'] ?? 'solid';
        $bg_color = $settings['bg_color'] ?? 'rgba(0,0,0,0)';
        $bg_gradient_start = $settings['bg_gradient_start'] ?? '#ffffff';
        $bg_gradient_end = $settings['bg_gradient_end'] ?? '#f5f5f7';
        $bg_image = $settings['bg_image'] ?? '';
        $overlay_opacity = isset($settings['overlay_opacity']) ? floatval($settings['overlay_opacity']) : 1;

        $css .= '.kng-site-preloader__overlay {';
        
        if ($bg_type === 'gradient') {
            $css .= 'background: linear-gradient(135deg, ' . $bg_gradient_start . ' 0%, ' . $bg_gradient_end . ' 100%);';
        } elseif ($bg_type === 'image' && !empty($bg_image)) {
            $css .= 'background-image: url(' . esc_url($bg_image) . ');';
            $css .= 'background-size: cover;';
            $css .= 'background-position: center;';
        } else {
            $css .= 'background-color: ' . $bg_color . ';';
        }
        
        $css .= 'opacity: ' . $overlay_opacity . ';';
        $css .= '}';

        // Accent color
        $accent_color = $settings['accent_color'] ?? '#0071e3';
        $css .= '.kng-site-preloader { --kng-preloader-accent: ' . $accent_color . '; }';

        // Spinner size
        $spinner_size = isset($settings['spinner_size']) ? absint($settings['spinner_size']) : 48;
        $css .= '.kng-site-preloader { --kng-preloader-size: ' . $spinner_size . 'px; }';

        // Text styles
        if (!empty($settings['text_enabled'])) {
            $text_color = $settings['text_color'] ?? '#1d1d1f';
            $text_size = isset($settings['text_size']) ? absint($settings['text_size']) : 16;
            $text_weight = $settings['text_weight'] ?? '400';
            
            $css .= '.kng-site-preloader__text {';
            $css .= 'color: ' . $text_color . ';';
            $css .= 'font-size: ' . $text_size . 'px;';
            $css .= 'font-weight: ' . $text_weight . ';';
            $css .= '}';
        }

        // Logo styles
        if (!empty($settings['logo_enabled']) && !empty($settings['logo_max_width'])) {
            $logo_max_width = absint($settings['logo_max_width']);
            $css .= '.kng-site-preloader__logo img { max-width: ' . $logo_max_width . 'px; }';
        }

        return $css;
    }

    /**
     * Gets inline style attribute value.
     *
     * @param array|null $settings Settings to use (optional, uses class settings if not provided).
     * @return string
     */
    private function get_inline_style(?array $settings = null): string
    {
        $settings = $settings ?? $this->settings;
        $z_index = isset($settings['z_index']) ? absint($settings['z_index']) : 999999;
        $bg_color = $settings['bg_color'] ?? '#ffffff';
        $accent_color = $settings['accent_color'] ?? '#0071e3';
        $text_color = $settings['text_color'] ?? '#1d1d1f';
        $spinner_size = absint($settings['spinner_size'] ?? 48);
        
        return sprintf(
            'z-index: %d; --kng-preloader-bg: %s; --kng-preloader-accent: %s; --kng-preloader-text: %s; --kng-preloader-size: %dpx;',
            $z_index,
            esc_attr($bg_color),
            esc_attr($accent_color),
            esc_attr($text_color),
            $spinner_size
        );
    }

    /**
     * Determines if the preloader should be displayed.
     *
     * @return bool
     */
    private function should_show_preloader(): bool
    {
        $settings = $this->settings;

        // Check if enabled
        if (empty($settings['enabled'])) {
            return false;
        }

        // Don't show in admin
        if (is_admin()) {
            return false;
        }

        // Don't show on Elementor editor/preview
        if (
            (isset($_GET['elementor-preview']) && $_GET['elementor-preview']) ||
            (isset($_GET['preview']) && $_GET['preview'] === 'true') ||
            (class_exists('\Elementor\Plugin') && \Elementor\Plugin::$instance->preview->is_preview_mode())
        ) {
            return false;
        }

        // Don't show on login page
        if (strpos($_SERVER['REQUEST_URI'], 'wp-login.php') !== false) {
            return false;
        }

        // Don't show on admin-ajax
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return false;
        }

        // Don't show on REST API
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return false;
        }

        // Check trigger type (cookie-based; avoids PHP session dependency)
        $trigger_type = $settings['trigger_type'] ?? 'always';
        $cookie_name = self::COOKIE_NAME . '_' . sanitize_key($trigger_type);

        if ($trigger_type === 'first_visit') {
            if (isset($_COOKIE[$cookie_name])) {
                return false;
            }
        } elseif ($trigger_type === 'once_per_session') {
            // Session cookie is handled on the frontend; we only need to check presence.
            if (isset($_COOKIE[$cookie_name])) {
                return false;
            }
        } elseif ($trigger_type === 'once_per_day') {
            // Cookie value must be a unix timestamp (seconds).
            $cookie_time = isset($_COOKIE[$cookie_name]) ? intval($_COOKIE[$cookie_name]) : 0;
            if ($cookie_time && (time() - $cookie_time) < DAY_IN_SECONDS) {
                return false;
            }
        }

        // Check device visibility
        $device_desktop = $settings['device_desktop'] ?? true;
        $device_tablet = $settings['device_tablet'] ?? true;
        $device_mobile = $settings['device_mobile'] ?? true;

        if (!$device_desktop && !$device_tablet && !$device_mobile) {
            return false;
        }

        // Check user visibility
        $show_for = $settings['show_for'] ?? 'everyone';
        
        if ($show_for === 'guests' && is_user_logged_in()) {
            return false;
        } elseif ($show_for === 'logged_in' && !is_user_logged_in()) {
            return false;
        }

        // Check display rules
        $show_on = $settings['show_on'] ?? 'all';
        
        if ($show_on === 'selected') {
            $selected_pages = $settings['selected_pages'] ?? [];
            $current_page_id = get_queried_object_id();
            
            if (!in_array($current_page_id, $selected_pages, true)) {
                return false;
            }
        } elseif ($show_on === 'exclude') {
            $excluded_pages = $settings['excluded_pages'] ?? [];
            $current_page_id = get_queried_object_id();
            
            if (in_array($current_page_id, $excluded_pages, true)) {
                return false;
            }
        }

        // Check custom rules with new action system
        $rule_result = $this->get_rule_action();
        $action = $rule_result['action'];
        
        // If a "hide" rule matches, don't show preloader
        if ($action === 'hide') {
            return false;
        }
        
        // "show" and "override" actions allow the preloader to show
        // "default" means no matching rule, use global settings

        return true;
    }

    /**
     * Evaluates a rule condition.
     *
     * @param array<string,mixed> $rule The rule to evaluate.
     * @return bool
     */
    private function evaluate_rule_condition(array $rule): bool
    {
        $condition = $rule['condition'] ?? '';
        $value = $rule['condition_value'] ?? '';
        $pages = $rule['pages'] ?? [];
        $current_url = $_SERVER['REQUEST_URI'] ?? '';

        switch ($condition) {
            // Specific pages by ID
            case 'specific_pages':
                if (empty($pages)) {
                    return false;
                }
                $current_id = get_queried_object_id();
                return in_array($current_id, array_map('intval', $pages));

            // All pages (not posts, not archives)
            case 'all_pages':
                return is_page();

            // Front page
            case 'front_page':
                return is_front_page();

            // Blog page (posts listing)
            case 'blog_page':
                return is_home();

            // All single posts
            case 'all_posts':
                return is_single() && get_post_type() === 'post';

            // Specific post type
            case 'post_type':
                return is_singular($value);

            // Archive pages
            case 'archive':
                return is_archive();

            // Search results
            case 'search':
                return is_search();

            // 404 page
            case '404':
                return is_404();

            // URL contains string
            case 'url_contains':
                return !empty($value) && strpos($current_url, $value) !== false;

            // URL equals exactly
            case 'url_equals':
                return $current_url === $value || parse_url($current_url, PHP_URL_PATH) === $value;

            default:
                return false;
        }
    }

    /**
     * Gets the available presets.
     *
     * @return array<string, array<string, mixed>>
     */
    private function get_presets(): array
    {
        return [
            'spinner-circle' => [
                'id' => 'spinner-circle',
                'title' => __('Spinner Circle', 'king-addons'),
                'description' => __('Classic circular spinner animation', 'king-addons'),
                'thumbnail' => KING_ADDONS_URL . 'includes/extensions/Site_Preloader/assets/images/preset-spinner-circle.svg',
                'pro' => false,
            ],
            'dual-ring' => [
                'id' => 'dual-ring',
                'title' => __('Dual Ring', 'king-addons'),
                'description' => __('Two concentric rotating rings', 'king-addons'),
                'thumbnail' => KING_ADDONS_URL . 'includes/extensions/Site_Preloader/assets/images/preset-dual-ring.svg',
                'pro' => false,
            ],
            'dots-bounce' => [
                'id' => 'dots-bounce',
                'title' => __('Dots Bounce', 'king-addons'),
                'description' => __('Three bouncing dots animation', 'king-addons'),
                'thumbnail' => KING_ADDONS_URL . 'includes/extensions/Site_Preloader/assets/images/preset-dots-bounce.svg',
                'pro' => false,
            ],
            'bar-loader' => [
                'id' => 'bar-loader',
                'title' => __('Bar Loader', 'king-addons'),
                'description' => __('Horizontal progress bar animation', 'king-addons'),
                'thumbnail' => KING_ADDONS_URL . 'includes/extensions/Site_Preloader/assets/images/preset-bar-loader.svg',
                'pro' => false,
            ],
            'pulse-logo' => [
                'id' => 'pulse-logo',
                'title' => __('Pulse Logo', 'king-addons'),
                'description' => __('Pulsating logo or circle effect', 'king-addons'),
                'thumbnail' => KING_ADDONS_URL . 'includes/extensions/Site_Preloader/assets/images/preset-pulse-logo.svg',
                'pro' => false,
            ],
            'minimal-line' => [
                'id' => 'minimal-line',
                'title' => __('Minimal Line', 'king-addons'),
                'description' => __('Sleek minimal line animation', 'king-addons'),
                'thumbnail' => KING_ADDONS_URL . 'includes/extensions/Site_Preloader/assets/images/preset-minimal-line.svg',
                'pro' => false,
            ],
            'gradient-spinner' => [
                'id' => 'gradient-spinner',
                'title' => __('Gradient Spinner', 'king-addons'),
                'description' => __('Spinner with gradient color effect', 'king-addons'),
                'thumbnail' => KING_ADDONS_URL . 'includes/extensions/Site_Preloader/assets/images/preset-gradient-spinner.svg',
                'pro' => false,
            ],
            'fade-text' => [
                'id' => 'fade-text',
                'title' => __('Fade Text', 'king-addons'),
                'description' => __('Loading text with letter fade animation', 'king-addons'),
                'thumbnail' => KING_ADDONS_URL . 'includes/extensions/Site_Preloader/assets/images/preset-fade-text.svg',
                'pro' => false,
            ],
            'cube-grid' => [
                'id' => 'cube-grid',
                'title' => __('Cube Grid', 'king-addons'),
                'description' => __('3x3 grid of animated cubes', 'king-addons'),
                'thumbnail' => KING_ADDONS_URL . 'includes/extensions/Site_Preloader/assets/images/preset-cube-grid.svg',
                'pro' => false,
            ],
            'wave-bars' => [
                'id' => 'wave-bars',
                'title' => __('Wave Bars', 'king-addons'),
                'description' => __('Vertical bars with wave animation', 'king-addons'),
                'thumbnail' => KING_ADDONS_URL . 'includes/extensions/Site_Preloader/assets/images/preset-wave-bars.svg',
                'pro' => false,
            ],
            'rotating-squares' => [
                'id' => 'rotating-squares',
                'title' => __('Rotating Squares', 'king-addons'),
                'description' => __('Two squares rotating in sync', 'king-addons'),
                'thumbnail' => KING_ADDONS_URL . 'includes/extensions/Site_Preloader/assets/images/preset-rotating-squares.svg',
                'pro' => false,
            ],
            'morphing-circle' => [
                'id' => 'morphing-circle',
                'title' => __('Morphing Circle', 'king-addons'),
                'description' => __('Shape-shifting circle animation', 'king-addons'),
                'thumbnail' => KING_ADDONS_URL . 'includes/extensions/Site_Preloader/assets/images/preset-morphing-circle.svg',
                'pro' => false,
            ],
        ];
    }

    /**
     * Gets the default settings.
     *
     * @return array<string, mixed>
     */
    private function get_default_settings(): array
    {
        return [
            // General
            'enabled' => false,
            'template' => 'spinner-circle',
            'show_on' => 'all',
            'selected_pages' => [],
            'excluded_pages' => [],
            'show_for' => 'everyone',
            'device_desktop' => true,
            'device_tablet' => true,
            'device_mobile' => true,

            // Appearance - Background
            'bg_type' => 'solid',
            'bg_color' => 'rgba(0,0,0,0)',
            'bg_gradient_start' => '#ffffff',
            'bg_gradient_end' => '#f5f5f7',
            'bg_image' => '',
            'overlay_opacity' => 1,

            // Appearance - Logo
            'logo_enabled' => false,
            'logo_url' => '',
            'logo_max_width' => 120,

            // Appearance - Text
            'text_enabled' => false,
            'text_content' => '',
            'text_size' => 16,
            'text_weight' => '400',
            'text_color' => '#1d1d1f',

            // Appearance - Animation
            'accent_color' => '#0071e3',
            'spinner_size' => 48,

            // Behavior
            'trigger_type' => 'always',
            'hide_strategy' => 'window_load',
            'min_display_time' => 500,
            'max_display_time' => 5000,
            'allow_skip' => false,
            'skip_method' => 'click',
            'lock_scroll' => true,

            // Transitions
            'show_animation' => 'fade',
            'hide_animation' => 'fade',
            'transition_duration' => 400,
            'easing' => 'ease-out',

            // Advanced
            'z_index' => 999999,
            'custom_css' => '',
        ];
    }

    /**
     * Gets current settings with defaults merged.
     *
     * @return array<string, mixed>
     */
    private function get_settings(): array
    {
        $saved = get_option(self::OPTION_KEY, []);
        return wp_parse_args($saved, $this->get_default_settings());
    }

    /**
     * Gets saved display rules.
     *
     * @return array<int, array<string, mixed>>
     */
    private function get_rules(): array
    {
        return get_option(self::RULES_OPTION_KEY, []);
    }

    /**
     * Sanitizes settings input.
     *
     * @param array<string,mixed> $input Raw input data.
     * @return array<string,mixed>
     */
    private function sanitize_settings(array $input): array
    {
        $sanitized = [];

        // General
        $sanitized['enabled'] = !empty($input['enabled']);
        $sanitized['template'] = isset($input['template']) ? sanitize_key($input['template']) : 'spinner-circle';
        $sanitized['show_on'] = isset($input['show_on']) ? sanitize_key($input['show_on']) : 'all';
        $sanitized['selected_pages'] = isset($input['selected_pages']) && is_array($input['selected_pages']) 
            ? array_map('absint', $input['selected_pages']) 
            : [];
        $sanitized['excluded_pages'] = isset($input['excluded_pages']) && is_array($input['excluded_pages']) 
            ? array_map('absint', $input['excluded_pages']) 
            : [];
        $sanitized['show_for'] = isset($input['show_for']) ? sanitize_key($input['show_for']) : 'everyone';
        $sanitized['device_desktop'] = !empty($input['device_desktop']);
        $sanitized['device_tablet'] = !empty($input['device_tablet']);
        $sanitized['device_mobile'] = !empty($input['device_mobile']);

        // Background
        $sanitized['bg_type'] = isset($input['bg_type']) ? sanitize_key($input['bg_type']) : 'solid';
        $sanitized['bg_color'] = $this->sanitize_color_value($input['bg_color'] ?? '', 'rgba(0,0,0,0)');
        $sanitized['bg_gradient_start'] = $this->sanitize_color_value($input['bg_gradient_start'] ?? '', '#ffffff');
        $sanitized['bg_gradient_end'] = $this->sanitize_color_value($input['bg_gradient_end'] ?? '', '#f5f5f7');
        $sanitized['bg_image'] = isset($input['bg_image']) ? esc_url_raw($input['bg_image']) : '';
        $sanitized['overlay_opacity'] = isset($input['overlay_opacity']) ? max(0, min(1, floatval($input['overlay_opacity']))) : 1;

        // Logo
        $sanitized['logo_enabled'] = !empty($input['logo_enabled']);
        $sanitized['logo_url'] = isset($input['logo_url']) ? esc_url_raw($input['logo_url']) : '';
        $sanitized['logo_max_width'] = isset($input['logo_max_width']) ? absint($input['logo_max_width']) : 120;

        // Text
        $sanitized['text_enabled'] = !empty($input['text_enabled']);
        $sanitized['text_content'] = isset($input['text_content']) ? sanitize_text_field($input['text_content']) : '';
        $sanitized['text_size'] = isset($input['text_size']) ? absint($input['text_size']) : 16;
        $sanitized['text_weight'] = isset($input['text_weight']) ? sanitize_key($input['text_weight']) : '400';
        $sanitized['text_color'] = $this->sanitize_color_value($input['text_color'] ?? '', '#1d1d1f');

        // Animation
        $sanitized['accent_color'] = $this->sanitize_color_value($input['accent_color'] ?? '', '#0071e3');
        $sanitized['spinner_size'] = isset($input['spinner_size']) ? absint($input['spinner_size']) : 48;

        // Behavior
        $sanitized['trigger_type'] = isset($input['trigger_type']) ? sanitize_key($input['trigger_type']) : 'always';
        $sanitized['hide_strategy'] = isset($input['hide_strategy']) ? sanitize_key($input['hide_strategy']) : 'window_load';
        $sanitized['min_display_time'] = isset($input['min_display_time']) ? absint($input['min_display_time']) : 500;
        $sanitized['max_display_time'] = isset($input['max_display_time']) ? absint($input['max_display_time']) : 5000;
        $sanitized['allow_skip'] = !empty($input['allow_skip']);
        $sanitized['skip_method'] = isset($input['skip_method']) ? sanitize_key($input['skip_method']) : 'click';
        $sanitized['lock_scroll'] = !empty($input['lock_scroll']);

        // Transitions
        $sanitized['show_animation'] = isset($input['show_animation']) ? sanitize_key($input['show_animation']) : 'fade';
        $sanitized['hide_animation'] = isset($input['hide_animation']) ? sanitize_key($input['hide_animation']) : 'fade';
        $sanitized['transition_duration'] = isset($input['transition_duration']) ? absint($input['transition_duration']) : 400;
        $sanitized['easing'] = isset($input['easing']) ? sanitize_key($input['easing']) : 'ease-out';

        // Advanced
        $sanitized['z_index'] = isset($input['z_index']) ? absint($input['z_index']) : 999999;
        
        // Custom CSS (Pro only)
        if ($this->is_pro() && isset($input['custom_css'])) {
            $sanitized['custom_css'] = wp_strip_all_tags($input['custom_css']);
        }

        return $sanitized;
    }

    /**
     * Sanitize a CSS color value coming from admin inputs.
     * Allows hex, 8-digit hex, rgb/rgba, and 'transparent'.
     *
     * @param mixed  $value
     * @param string $fallback
     * @return string
     */
    private function sanitize_color_value($value, string $fallback): string
    {
        if (!is_string($value)) {
            return $fallback;
        }

        $value = trim($value);
        if ($value === '') {
            return $fallback;
        }

        if (strcasecmp($value, 'transparent') === 0) {
            return 'transparent';
        }

        $hex = sanitize_hex_color($value);
        if (!empty($hex)) {
            return $hex;
        }

        if (preg_match('/^#([0-9a-fA-F]{8})$/', $value, $m)) {
            return '#' . strtolower($m[1]);
        }

        if (preg_match('/^rgba?\((.+)\)$/i', $value, $m)) {
            $parts = array_map('trim', explode(',', $m[1]));
            if (count($parts) < 3 || count($parts) > 4) {
                return $fallback;
            }

            $r = is_numeric($parts[0]) ? (int) $parts[0] : -1;
            $g = is_numeric($parts[1]) ? (int) $parts[1] : -1;
            $b = is_numeric($parts[2]) ? (int) $parts[2] : -1;
            if ($r < 0 || $r > 255 || $g < 0 || $g > 255 || $b < 0 || $b > 255) {
                return $fallback;
            }

            if (count($parts) === 3) {
                return 'rgb(' . $r . ',' . $g . ',' . $b . ')';
            }

            $aRaw = $parts[3];
            if (!is_numeric($aRaw)) {
                return $fallback;
            }
            $a = (float) $aRaw;
            if ($a < 0) {
                $a = 0;
            } elseif ($a > 1) {
                $a = 1;
            }

            $aStr = rtrim(rtrim(sprintf('%.3F', $a), '0'), '.');
            return 'rgba(' . $r . ',' . $g . ',' . $b . ',' . $aStr . ')';
        }

        return $fallback;
    }

    /**
     * Checks if Pro version is active.
     *
     * @return bool
     */
    private function is_pro(): bool
    {
        return function_exists('king_addons_freemius') && king_addons_freemius()->can_use_premium_code__premium_only();
    }
}
