<?php
/**
 * Sticky Contact Bar feature.
 *
 * @package King_Addons
 */

namespace King_Addons;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders a global sticky contact bar with quick actions.
 */
class Sticky_Contact_Bar
{
    /**
     * Option key for storing settings.
     */
    public const OPTION_KEY = 'king_addons_sticky_contact_bar_settings';

    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
        add_action('wp_footer', [$this, 'render_bar']);

        add_action('admin_init', [$this, 'register_settings']);
        // Use priority 15 to ensure the parent menu exists before adding this submenu
        add_action('admin_menu', [$this, 'register_settings_page'], 15);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * Enqueue admin styles on settings page.
     *
     * All admin styles are now inline in render_settings_page() for the modern design.
     *
     * @param string $hook Current admin page hook.
     *
     * @return void
     */
    public function enqueue_admin_assets(string $hook): void
    {
        // Styles are now inline in render_settings_page()
        // Keeping method for future extensibility
    }

    /**
     * Register frontend assets.
     *
     * @return void
     */
    public function register_assets(): void
    {
        $style_handle = KING_ADDONS_ASSETS_UNIQUE_KEY . '-sticky-contact-bar';
        $script_handle = KING_ADDONS_ASSETS_UNIQUE_KEY . '-sticky-contact-bar';

        wp_register_style(
            $style_handle,
            KING_ADDONS_URL . 'includes/extensions/Sticky_Contact_Bar/style.css',
            [],
            KING_ADDONS_VERSION
        );

        wp_register_script(
            $script_handle,
            KING_ADDONS_URL . 'includes/extensions/Sticky_Contact_Bar/script.js',
            ['jquery'],
            KING_ADDONS_VERSION,
            true
        );
    }

    /**
     * Register settings.
     *
     * @return void
     */
    public function register_settings(): void
    {
        register_setting(
            'king_addons_sticky_contact_bar',
            self::OPTION_KEY,
            [
                'type' => 'array',
                'sanitize_callback' => [$this, 'sanitize_settings'],
                'default' => $this->get_settings_defaults(),
            ]
        );
    }

    /**
     * Register settings page in King Addons menu.
     *
     * @return void
     */
    public function register_settings_page(): void
    {
        add_submenu_page(
            'king-addons',
            esc_html__('Sticky Contact Bar', 'king-addons'),
            esc_html__('Sticky Contact Bar', 'king-addons'),
            'manage_options',
            'king-addons-sticky-contact-bar',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Render admin settings page.
     *
     * @return void
     */
    public function render_settings_page(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $is_pro = $this->can_use_pro();
        $settings = $this->get_settings();

        // Theme mode is per-user
        $theme_mode = get_user_meta(get_current_user_id(), 'king_addons_theme_mode', true);
        $allowed_theme_modes = ['dark', 'light', 'auto'];
        if (!in_array($theme_mode, $allowed_theme_modes, true)) {
            $theme_mode = 'dark';
        }

        // Enqueue shared V3 styles
        wp_enqueue_style(
            'king-addons-admin-v3',
            KING_ADDONS_URL . 'includes/admin/layouts/shared/admin-v3-styles.css',
            [],
            KING_ADDONS_VERSION
        );

        ?>
        <script>
        (function() {
            const mode = '<?php echo esc_js($theme_mode); ?>';
            const mql = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
            const isDark = mode === 'auto' ? !!(mql && mql.matches) : mode === 'dark';
            document.documentElement.classList.toggle('ka-v3-dark', isDark);
            document.body.classList.add('ka-admin-v3');
            document.body.classList.toggle('ka-v3-dark', isDark);
        })();
        </script>
        <style>
        /* Sticky Contact Bar V3 Additional Styles */
        .ka-scb-v3 .ka-grid-2 { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 0 30px; 
        }

        /* Button Items */
        .ka-scb-v3 .ka-scb-item {
            background: rgba(0, 0, 0, 0.02);
            border: 1px solid rgba(0, 0, 0, 0.04);
            border-radius: 16px;
            padding: 20px 24px;
            margin-bottom: 16px;
            transition: all 0.3s;
        }
        body.ka-v3-dark .ka-scb-v3 .ka-scb-item {
            background: rgba(255, 255, 255, 0.04);
            border-color: rgba(255, 255, 255, 0.06);
        }
        .ka-scb-v3 .ka-scb-item:hover { 
            border-color: #22c55e; 
        }
        body.ka-v3-dark .ka-scb-v3 .ka-scb-item:hover {
            border-color: #4ade80;
        }
        .ka-scb-v3 .ka-scb-item-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.04);
        }
        body.ka-v3-dark .ka-scb-v3 .ka-scb-item-header {
            border-bottom-color: rgba(255, 255, 255, 0.04);
        }
        .ka-scb-v3 .ka-scb-item-title { 
            font-weight: 600; 
            color: #1d1d1f; 
            font-size: 15px; 
        }
        body.ka-v3-dark .ka-scb-v3 .ka-scb-item-title {
            color: #f5f5f7;
        }
        .ka-scb-v3 .ka-scb-item-grid { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 16px 24px; 
        }
        .ka-scb-v3 .ka-scb-item-row { 
            display: flex; 
            flex-direction: column; 
            gap: 6px; 
        }
        .ka-scb-v3 .ka-scb-item-label { 
            font-size: 13px; 
            font-weight: 500; 
            color: #86868b; 
        }
        .ka-scb-v3 .ka-scb-item-row input,
        .ka-scb-v3 .ka-scb-item-row select {
            padding: 10px 14px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            font-size: 14px;
            background: #fff;
        }
        body.ka-v3-dark .ka-scb-v3 .ka-scb-item-row input,
        body.ka-v3-dark .ka-scb-v3 .ka-scb-item-row select {
            background: #2c2c2e;
            border-color: rgba(255, 255, 255, 0.1);
            color: #f5f5f7;
        }
        .ka-scb-v3 .ka-scb-remove-btn {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: none;
            border-radius: 980px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 400;
            cursor: pointer;
            transition: all 0.2s;
        }
        .ka-scb-v3 .ka-scb-remove-btn:hover { 
            background: rgba(239, 68, 68, 0.2); 
        }
        .ka-scb-v3 .ka-scb-add-btn {
            background: #22c55e;
            color: #fff;
            border: none;
            border-radius: 980px;
            padding: 12px 24px;
            font-size: 15px;
            font-weight: 400;
            cursor: pointer;
            transition: all 0.3s;
        }
        .ka-scb-v3 .ka-scb-add-btn:hover {
            background: #16a34a;
        }

        /* Checkbox group */
        .ka-scb-v3 .ka-checkbox-group { 
            display: flex; 
            flex-wrap: wrap; 
            gap: 20px; 
        }
        .ka-scb-v3 .ka-checkbox-item { 
            display: flex; 
            align-items: center; 
            gap: 8px; 
            font-size: 14px; 
            color: #1d1d1f; 
        }
        body.ka-v3-dark .ka-scb-v3 .ka-checkbox-item {
            color: #f5f5f7;
        }

        /* Shadow builder */
        .ka-scb-v3 .ka-shadow-builder {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            align-items: flex-end;
            background: rgba(0, 0, 0, 0.02);
            padding: 20px;
            border-radius: 16px;
            border: 1px solid rgba(0, 0, 0, 0.04);
        }
        body.ka-v3-dark .ka-scb-v3 .ka-shadow-builder {
            background: rgba(255, 255, 255, 0.04);
            border-color: rgba(255, 255, 255, 0.06);
        }
        .ka-scb-v3 .ka-shadow-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .ka-scb-v3 .ka-shadow-field label {
            font-size: 12px;
            font-weight: 500;
            color: #86868b;
            text-transform: uppercase;
        }
        .ka-scb-v3 .ka-shadow-field input[type="number"] {
            width: 80px;
            padding: 10px 12px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            font-size: 14px;
            background: #fff;
        }
        body.ka-v3-dark .ka-scb-v3 .ka-shadow-field input[type="number"] {
            background: #2c2c2e;
            border-color: rgba(255, 255, 255, 0.1);
            color: #f5f5f7;
        }
        .ka-scb-v3 .ka-shadow-preview {
            width: 60px;
            height: 40px;
            background: #fff;
            border-radius: 10px;
            margin-left: auto;
        }
        body.ka-v3-dark .ka-scb-v3 .ka-shadow-preview {
            background: #2c2c2e;
        }

        /* Status badge */
        .ka-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 980px;
            font-size: 13px;
            font-weight: 500;
        }
        .ka-status-badge-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }
        .ka-status-badge.enabled {
            background: rgba(34, 197, 94, 0.1);
            color: #22c55e;
        }
        .ka-status-badge.enabled .ka-status-badge-dot {
            background: #22c55e;
        }
        .ka-status-badge.disabled {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }
        .ka-status-badge.disabled .ka-status-badge-dot {
            background: #ef4444;
        }

        /* Color picker overrides */
        .ka-scb-v3 .ka-color-wrap {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .ka-scb-v3 .wp-picker-container .wp-color-result {
            height: 36px;
            border-radius: 8px;
        }

        /* Toggle override for green */
        .ka-scb-v3 .ka-toggle input:checked + .ka-toggle-slider {
            background: #22c55e !important;
        }

        /* Save button */
        .ka-scb-v3 .ka-card-footer {
            padding: 20px 28px;
            background: rgba(34, 197, 94, 0.04);
            border: 1px solid rgba(34, 197, 94, 0.1);
        }
        body.ka-v3-dark .ka-scb-v3 .ka-card-footer {
            background: rgba(34, 197, 94, 0.08);
            border-color: rgba(34, 197, 94, 0.2);
        }
        .ka-scb-v3 .ka-save-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: #22c55e;
            color: #fff;
            border: none;
            padding: 14px 28px;
            border-radius: 980px;
            font-size: 15px;
            font-weight: 400;
            cursor: pointer;
            transition: all 0.3s;
        }
        .ka-scb-v3 .ka-save-btn:hover {
            background: #16a34a;
            transform: scale(1.02);
        }
        .ka-scb-v3 .ka-save-btn .dashicons {
            font-size: 18px;
            width: 18px;
            height: 18px;
        }

        /* Responsive */
        @media (max-width: 900px) {
            .ka-scb-v3 .ka-grid-2, 
            .ka-scb-v3 .ka-scb-item-grid { 
                grid-template-columns: 1fr; 
            }
        }
        </style>

        <div class="ka-admin-wrap ka-scb-v3">
            <!-- Header -->
            <div class="ka-admin-header">
                <div class="ka-admin-header-left">
                    <div class="ka-admin-header-icon green">
                        <span class="dashicons dashicons-phone"></span>
                    </div>
                    <div>
                        <h1 class="ka-admin-title"><?php esc_html_e('Sticky Contact Bar', 'king-addons'); ?></h1>
                        <p class="ka-admin-subtitle"><?php esc_html_e('Configure floating contact buttons', 'king-addons'); ?></p>
                    </div>
                </div>
                <div class="ka-admin-header-actions">
                    <span class="ka-status-badge <?php echo !empty($settings['enabled']) ? 'enabled' : 'disabled'; ?>">
                        <span class="ka-status-badge-dot"></span>
                        <?php echo !empty($settings['enabled']) ? esc_html__('Enabled', 'king-addons') : esc_html__('Disabled', 'king-addons'); ?>
                    </span>
                    <div class="ka-v3-segmented" id="ka-v3-theme-segment" role="radiogroup" aria-label="Theme" data-active="<?php echo esc_attr($theme_mode); ?>">
                        <span class="ka-v3-segmented-indicator" aria-hidden="true"></span>
                        <button type="button" class="ka-v3-segmented-btn" data-theme="light" aria-pressed="<?php echo $theme_mode === 'light' ? 'true' : 'false'; ?>">
                            <span class="ka-v3-segmented-icon" aria-hidden="true">☀︎</span>
                            <?php esc_html_e('Light', 'king-addons'); ?>
                        </button>
                        <button type="button" class="ka-v3-segmented-btn" data-theme="dark" aria-pressed="<?php echo $theme_mode === 'dark' ? 'true' : 'false'; ?>">
                            <span class="ka-v3-segmented-icon" aria-hidden="true">☾</span>
                            <?php esc_html_e('Dark', 'king-addons'); ?>
                        </button>
                        <button type="button" class="ka-v3-segmented-btn" data-theme="auto" aria-pressed="<?php echo $theme_mode === 'auto' ? 'true' : 'false'; ?>">
                            <span class="ka-v3-segmented-icon" aria-hidden="true">◐</span>
                            <?php esc_html_e('Auto', 'king-addons'); ?>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="ka-tabs">
                <button type="button" class="ka-tab active" data-tab="general"><?php esc_html_e('General', 'king-addons'); ?></button>
                <button type="button" class="ka-tab" data-tab="buttons"><?php esc_html_e('Buttons', 'king-addons'); ?></button>
                <button type="button" class="ka-tab" data-tab="advanced">
                    <?php esc_html_e('Advanced', 'king-addons'); ?>
                    <?php if (!$is_pro): ?><span class="ka-pro-badge">Pro</span><?php endif; ?>
                </button>
            </div>

            <form action="<?php echo esc_url(admin_url('options.php')); ?>" method="post">
                <?php settings_fields('king_addons_sticky_contact_bar'); ?>

                <!-- General Tab -->
                <div class="ka-tab-content active" data-tab="general">
                    <!-- General Settings -->
                    <div class="ka-card">
                        <div class="ka-card-header">
                            <span class="dashicons dashicons-admin-settings"></span>
                            <h2><?php esc_html_e('General Settings', 'king-addons'); ?></h2>
                        </div>
                        <div class="ka-card-body">
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Enable', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <label class="ka-toggle">
                                    <input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[enabled]" value="1" <?php checked(!empty($settings['enabled'])); ?> />
                                    <span class="ka-toggle-slider"></span>
                                    <span class="ka-toggle-label"><?php esc_html_e('Show sticky contact bar', 'king-addons'); ?></span>
                                </label>
                            </div>
                        </div>
                        <div class="ka-grid-2">
                            <div class="ka-row">
                                <div class="ka-row-label"><?php esc_html_e('Position', 'king-addons'); ?></div>
                                <div class="ka-row-field">
                                    <select name="<?php echo esc_attr(self::OPTION_KEY); ?>[position]">
                                        <option value="bottom" <?php selected($settings['position'], 'bottom'); ?>><?php esc_html_e('Bottom', 'king-addons'); ?></option>
                                        <option value="left" <?php selected($settings['position'], 'left'); ?> <?php disabled(!$is_pro); ?>><?php esc_html_e('Left (Pro)', 'king-addons'); ?></option>
                                        <option value="right" <?php selected($settings['position'], 'right'); ?> <?php disabled(!$is_pro); ?>><?php esc_html_e('Right (Pro)', 'king-addons'); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="ka-row">
                                <div class="ka-row-label"><?php esc_html_e('Alignment', 'king-addons'); ?></div>
                                <div class="ka-row-field">
                                    <select name="<?php echo esc_attr(self::OPTION_KEY); ?>[alignment]">
                                        <option value="center" <?php selected($settings['alignment'], 'center'); ?>><?php esc_html_e('Center', 'king-addons'); ?></option>
                                        <option value="left" <?php selected($settings['alignment'], 'left'); ?>><?php esc_html_e('Left', 'king-addons'); ?></option>
                                        <option value="right" <?php selected($settings['alignment'], 'right'); ?>><?php esc_html_e('Right', 'king-addons'); ?></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Devices', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <div class="ka-checkbox-group">
                                    <label class="ka-checkbox-item">
                                        <input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[devices][]" value="desktop" <?php checked(in_array('desktop', (array) $settings['devices'], true)); ?> />
                                        <?php esc_html_e('Desktop', 'king-addons'); ?>
                                    </label>
                                    <label class="ka-checkbox-item">
                                        <input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[devices][]" value="mobile" <?php checked(in_array('mobile', (array) $settings['devices'], true)); ?> />
                                        <?php esc_html_e('Mobile', 'king-addons'); ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="ka-grid-2">
                            <div class="ka-row">
                                <div class="ka-row-label"><?php esc_html_e('Trigger', 'king-addons'); ?></div>
                                <div class="ka-row-field">
                                    <select name="<?php echo esc_attr(self::OPTION_KEY); ?>[trigger]">
                                        <option value="always" <?php selected($settings['trigger'], 'always'); ?>><?php esc_html_e('Always visible', 'king-addons'); ?></option>
                                        <option value="scroll" <?php selected($settings['trigger'], 'scroll'); ?>><?php esc_html_e('After scroll', 'king-addons'); ?></option>
                                        <option value="delay" <?php selected($settings['trigger'], 'delay'); ?> <?php disabled(!$is_pro); ?>><?php esc_html_e('After delay (Pro)', 'king-addons'); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="ka-row">
                                <div class="ka-row-label"><?php esc_html_e('Scroll Offset', 'king-addons'); ?></div>
                                <div class="ka-row-field">
                                    <input type="number" min="0" name="<?php echo esc_attr(self::OPTION_KEY); ?>[trigger_scroll]" value="<?php echo esc_attr((int) $settings['trigger_scroll']); ?>" style="max-width:100px" />
                                    <span style="color:#64748b;margin-left:6px">px</span>
                                </div>
                            </div>
                        </div>
                        </div>
                    </div>

                <!-- Appearance -->
                <?php
                // Parse shadow settings
                $shadow_x = isset($settings['shadow_x']) ? (int) $settings['shadow_x'] : 0;
                $shadow_y = isset($settings['shadow_y']) ? (int) $settings['shadow_y'] : 8;
                $shadow_blur = isset($settings['shadow_blur']) ? (int) $settings['shadow_blur'] : 24;
                $shadow_spread = isset($settings['shadow_spread']) ? (int) $settings['shadow_spread'] : 0;
                $shadow_color = isset($settings['shadow_color']) ? $settings['shadow_color'] : 'rgba(0,0,0,0.15)';
                ?>
                <div class="ka-card">
                    <div class="ka-card-header">
                        <span class="dashicons dashicons-art"></span>
                        <h2><?php esc_html_e('Appearance', 'king-addons'); ?></h2>
                    </div>
                    <div class="ka-card-body">
                        <div class="ka-grid-2">
                            <div class="ka-row">
                                <div class="ka-row-label"><?php esc_html_e('Background', 'king-addons'); ?></div>
                                <div class="ka-row-field">
                                    <div class="ka-color-wrap">
                                        <input type="text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[background]" value="<?php echo esc_attr($settings['background']); ?>" data-alpha-enabled="true" data-default-color="#0a0a0a" class="ka-color-picker" />
                                    </div>
                                </div>
                            </div>
                            <div class="ka-row">
                                <div class="ka-row-label"><?php esc_html_e('Border Radius', 'king-addons'); ?></div>
                                <div class="ka-row-field">
                                    <input type="number" min="0" name="<?php echo esc_attr(self::OPTION_KEY); ?>[border_radius]" value="<?php echo esc_attr((int) str_replace('px', '', $settings['border_radius'])); ?>" style="max-width:80px" />
                                    <span style="color:#64748b;margin-left:6px">px</span>
                                </div>
                            </div>
                            <div class="ka-row">
                                <div class="ka-row-label"><?php esc_html_e('Z-index', 'king-addons'); ?></div>
                                <div class="ka-row-field">
                                    <input type="number" name="<?php echo esc_attr(self::OPTION_KEY); ?>[z_index]" value="<?php echo esc_attr((int) $settings['z_index']); ?>" style="max-width:100px" />
                                </div>
                            </div>
                            <div class="ka-row">
                                <div class="ka-row-label"><?php esc_html_e('Item Shape', 'king-addons'); ?></div>
                                <div class="ka-row-field">
                                    <select name="<?php echo esc_attr(self::OPTION_KEY); ?>[item_shape]">
                                        <option value="circle" <?php selected($settings['item_shape'], 'circle'); ?>><?php esc_html_e('Circle', 'king-addons'); ?></option>
                                        <option value="rounded" <?php selected($settings['item_shape'], 'rounded'); ?>><?php esc_html_e('Rounded', 'king-addons'); ?></option>
                                        <option value="square" <?php selected($settings['item_shape'], 'square'); ?>><?php esc_html_e('Square', 'king-addons'); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="ka-row">
                                <div class="ka-row-label"><?php esc_html_e('Item Size', 'king-addons'); ?></div>
                                <div class="ka-row-field">
                                    <input type="number" min="32" name="<?php echo esc_attr(self::OPTION_KEY); ?>[item_size]" value="<?php echo esc_attr((int) $settings['item_size']); ?>" style="max-width:100px" />
                                    <span style="color:#64748b;margin-left:6px">px</span>
                                </div>
                            </div>
                            <div class="ka-row">
                                <div class="ka-row-label"><?php esc_html_e('Item Spacing', 'king-addons'); ?></div>
                                <div class="ka-row-field">
                                    <input type="number" min="0" name="<?php echo esc_attr(self::OPTION_KEY); ?>[item_spacing]" value="<?php echo esc_attr((int) $settings['item_spacing']); ?>" style="max-width:100px" />
                                    <span style="color:#64748b;margin-left:6px">px</span>
                                </div>
                            </div>
                        </div>
                        <!-- Shadow Builder -->
                        <div class="ka-row" style="flex-direction:column;gap:10px">
                            <div class="ka-row-label" style="padding-top:0"><?php esc_html_e('Box Shadow', 'king-addons'); ?></div>
                            <div class="ka-shadow-builder">
                                <div class="ka-shadow-field">
                                    <label><?php esc_html_e('X', 'king-addons'); ?></label>
                                    <input type="number" name="<?php echo esc_attr(self::OPTION_KEY); ?>[shadow_x]" value="<?php echo esc_attr($shadow_x); ?>" id="ka-shadow-x" />
                                </div>
                                <div class="ka-shadow-field">
                                    <label><?php esc_html_e('Y', 'king-addons'); ?></label>
                                    <input type="number" name="<?php echo esc_attr(self::OPTION_KEY); ?>[shadow_y]" value="<?php echo esc_attr($shadow_y); ?>" id="ka-shadow-y" />
                                </div>
                                <div class="ka-shadow-field">
                                    <label><?php esc_html_e('Blur', 'king-addons'); ?></label>
                                    <input type="number" min="0" name="<?php echo esc_attr(self::OPTION_KEY); ?>[shadow_blur]" value="<?php echo esc_attr($shadow_blur); ?>" id="ka-shadow-blur" />
                                </div>
                                <div class="ka-shadow-field">
                                    <label><?php esc_html_e('Spread', 'king-addons'); ?></label>
                                    <input type="number" name="<?php echo esc_attr(self::OPTION_KEY); ?>[shadow_spread]" value="<?php echo esc_attr($shadow_spread); ?>" id="ka-shadow-spread" />
                                </div>
                                <div class="ka-shadow-field">
                                    <label><?php esc_html_e('Color', 'king-addons'); ?></label>
                                    <input type="text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[shadow_color]" value="<?php echo esc_attr($shadow_color); ?>" data-alpha-enabled="true" data-default-color="rgba(0,0,0,0.15)" class="ka-color-picker" id="ka-shadow-color" />
                                </div>
                                <div class="ka-shadow-preview" id="ka-shadow-preview"></div>
                            </div>
                        </div>
                        <!-- Keep legacy shadow field as hidden for backward compatibility -->
                        <input type="hidden" name="<?php echo esc_attr(self::OPTION_KEY); ?>[shadow]" id="ka-shadow-combined" value="<?php echo esc_attr($settings['shadow']); ?>" />
                    </div>
                </div>
                </div><!-- /General Tab -->

                <!-- Buttons Tab -->
                <div class="ka-tab-content" data-tab="buttons">
                <div class="ka-card">
                    <div class="ka-card-header">
                        <span class="dashicons dashicons-admin-links"></span>
                        <h2><?php esc_html_e('Contact Buttons', 'king-addons'); ?></h2>
                    </div>
                    <div class="ka-card-body">
                        <p class="ka-row-desc" style="margin:0 0 16px"><?php esc_html_e('Add contact buttons. Fill only the fields relevant to each button type.', 'king-addons'); ?></p>
                        <div id="ka-scbar-items">
                            <?php
                            $items = is_array($settings['items']) ? $settings['items'] : [];
                            if (empty($items)) {
                                $items[] = [];
                            }
                            foreach ($items as $index => $item) {
                                $this->render_item_row($index, $item, $is_pro);
                            }
                            ?>
                        </div>
                        <button type="button" class="ka-scb-add-btn" id="ka-scbar-add-item">
                            <span class="dashicons dashicons-plus-alt2" style="margin-right:6px"></span>
                            <?php esc_html_e('Add Button', 'king-addons'); ?>
                        </button>
                    </div>
                </div>
                </div><!-- /Buttons Tab -->

                <!-- Advanced Tab -->
                <div class="ka-tab-content" data-tab="advanced">
                <!-- Advanced Settings (Pro) -->
                <div class="ka-card">
                    <div class="ka-card-header">
                        <span class="dashicons dashicons-admin-generic"></span>
                        <h2><?php esc_html_e('Advanced Settings', 'king-addons'); ?></h2>
                        <?php if (!$is_pro): ?><span class="ka-pro-badge">Pro</span><?php endif; ?>
                    </div>
                    <div class="ka-card-body">
                        <div class="ka-grid-2">
                            <div class="ka-row">
                                <div class="ka-row-label"><?php esc_html_e('Delay (ms)', 'king-addons'); ?></div>
                                <div class="ka-row-field">
                                    <input type="number" min="0" name="<?php echo esc_attr(self::OPTION_KEY); ?>[trigger_delay]" value="<?php echo esc_attr((int) ($settings['trigger_delay'] ?? 0)); ?>" style="max-width:100px" <?php disabled(!$is_pro); ?> />
                                    <p class="ka-row-desc"><?php esc_html_e('Show bar after this delay when trigger is set to "After delay".', 'king-addons'); ?></p>
                                </div>
                            </div>
                            <div class="ka-row">
                                <div class="ka-row-label"><?php esc_html_e('Hide on Scroll Down', 'king-addons'); ?></div>
                                <div class="ka-row-field">
                                    <label class="ka-toggle">
                                        <input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[hide_on_scroll_down]" value="1" <?php checked(!empty($settings['hide_on_scroll_down'])); ?> <?php disabled(!$is_pro); ?> />
                                        <span class="ka-toggle-slider"></span>
                                    </label>
                                </div>
                            </div>
                            <div class="ka-row">
                                <div class="ka-row-label"><?php esc_html_e('Show on Scroll Up', 'king-addons'); ?></div>
                                <div class="ka-row-field">
                                    <label class="ka-toggle">
                                        <input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[show_on_scroll_up]" value="1" <?php checked(!empty($settings['show_on_scroll_up'])); ?> <?php disabled(!$is_pro); ?> />
                                        <span class="ka-toggle-slider"></span>
                                    </label>
                                </div>
                            </div>
                            <div class="ka-row">
                                <div class="ka-row-label"><?php esc_html_e('Enable Analytics', 'king-addons'); ?></div>
                                <div class="ka-row-field">
                                    <label class="ka-toggle">
                                        <input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[analytics_enabled]" value="1" <?php checked(!empty($settings['analytics_enabled'])); ?> <?php disabled(!$is_pro); ?> />
                                        <span class="ka-toggle-slider"></span>
                                        <span class="ka-toggle-label"><?php esc_html_e('Track clicks via dataLayer/gtag', 'king-addons'); ?></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Display Conditions (Pro) -->
                <div class="ka-card">
                    <div class="ka-card-header">
                        <span class="dashicons dashicons-visibility"></span>
                        <h2><?php esc_html_e('Display Conditions', 'king-addons'); ?></h2>
                        <?php if (!$is_pro): ?><span class="ka-pro-badge">Pro</span><?php endif; ?>
                    </div>
                    <div class="ka-card-body">
                        <?php if (!$is_pro): ?>
                            <p class="ka-row-desc"><?php esc_html_e('Upgrade to Pro to add display conditions and control where the bar appears.', 'king-addons'); ?></p>
                        <?php else: ?>
                            <p class="ka-row-desc" style="margin:0 0 16px"><?php esc_html_e('Add conditions to control where the bar appears. All conditions must match.', 'king-addons'); ?></p>
                            <div id="ka-scbar-conditions">
                                <?php
                                $conditions = is_array($settings['conditions'] ?? null) ? $settings['conditions'] : [];
                                if (empty($conditions)) {
                                    echo '<p class="ka-row-desc">' . esc_html__('No conditions set. Bar will appear on all pages.', 'king-addons') . '</p>';
                                }
                                foreach ($conditions as $cond_index => $condition) {
                                    $this->render_condition_row($cond_index, $condition);
                                }
                                ?>
                            </div>
                            <button type="button" class="ka-scb-add-btn" id="ka-scbar-add-condition" style="margin-top:12px">
                                <span class="dashicons dashicons-plus-alt2" style="margin-right:6px"></span>
                                <?php esc_html_e('Add Condition', 'king-addons'); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                </div><!-- /Advanced Tab -->

                <!-- Save Button -->
                <div class="ka-card ka-card-footer">
                    <button type="submit" class="ka-save-btn">
                        <span class="dashicons dashicons-saved"></span>
                        <?php esc_html_e('Save Settings', 'king-addons'); ?>
                    </button>
                </div>
            </form>
        </div>

        <script>
        (function() {
            const ajaxUrl = '<?php echo esc_url(admin_url('admin-ajax.php')); ?>';
            const nonce = '<?php echo esc_js(wp_create_nonce('king_addons_dashboard_ui')); ?>';
            const segment = document.getElementById('ka-v3-theme-segment');
            const mql = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
            let mode = (segment && segment.getAttribute('data-active') ? segment.getAttribute('data-active') : 'dark').toString();
            let mqlHandler = null;

            function saveUISetting(key, value) {
                const body = new URLSearchParams();
                body.set('action', 'king_addons_save_dashboard_ui');
                body.set('nonce', nonce);
                body.set('key', key);
                body.set('value', value);
                fetch(ajaxUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                    body: body.toString()
                });
            }

            function updateSegment(activeMode) {
                if (!segment) {
                    return;
                }
                segment.setAttribute('data-active', activeMode);
                segment.querySelectorAll('.ka-v3-segmented-btn').forEach((btn) => {
                    const theme = btn.getAttribute('data-theme');
                    btn.setAttribute('aria-pressed', theme === activeMode ? 'true' : 'false');
                });
            }

            function applyTheme(isDark) {
                document.body.classList.toggle('ka-v3-dark', isDark);
                document.documentElement.classList.toggle('ka-v3-dark', isDark);
            }

            function setThemeMode(nextMode, save) {
                mode = nextMode;
                updateSegment(nextMode);

                if (mqlHandler && mql) {
                    if (mql.removeEventListener) {
                        mql.removeEventListener('change', mqlHandler);
                    } else if (mql.removeListener) {
                        mql.removeListener(mqlHandler);
                    }
                    mqlHandler = null;
                }

                if (nextMode === 'auto') {
                    applyTheme(!!(mql && mql.matches));
                    mqlHandler = (e) => {
                        if (mode !== 'auto') {
                            return;
                        }
                        applyTheme(!!e.matches);
                    };
                    if (mql) {
                        if (mql.addEventListener) {
                            mql.addEventListener('change', mqlHandler);
                        } else if (mql.addListener) {
                            mql.addListener(mqlHandler);
                        }
                    }
                } else {
                    applyTheme(nextMode === 'dark');
                }

                if (save) {
                    saveUISetting('theme_mode', nextMode);
                }
            }

            window.kaV3ToggleDark = function() {
                const isDark = document.body.classList.contains('ka-v3-dark');
                setThemeMode(isDark ? 'light' : 'dark', true);
            };

            if (segment) {
                segment.addEventListener('click', function(e) {
                    const btn = e.target.closest('.ka-v3-segmented-btn');
                    if (!btn) return;
                    e.preventDefault();
                    setThemeMode((btn.getAttribute('data-theme') || 'dark').toString(), true);
                });
            }

            if (segment) {
                setThemeMode(mode, false);
            }
        })();

        // Tab navigation
        (function() {
            const tabs = document.querySelectorAll('.ka-tab');
            const contents = document.querySelectorAll('.ka-tab-content');

            tabs.forEach(tab => {
                tab.addEventListener('click', function(e) {
                    e.preventDefault();
                    const target = this.dataset.tab;
                    tabs.forEach(t => t.classList.remove('active'));
                    contents.forEach(c => c.classList.remove('active'));
                    this.classList.add('active');
                    document.querySelector('.ka-tab-content[data-tab="' + target + '"]').classList.add('active');
                });
            });
        })();
        </script>

        <template id="ka-scbar-item-template">
            <?php $this->render_item_row('__i__', [], $is_pro); ?>
        </template>
        <?php if ($is_pro): ?>
        <template id="ka-scbar-condition-template">
            <?php $this->render_condition_row('__c__', []); ?>
        </template>
        <?php endif; ?>
        <?php
        // Enqueue color picker
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        ?>
        <script>
            (function($) {
                'use strict';

                /**
                 * Initialize color pickers in a given container.
                 *
                 * @param {HTMLElement} container The container to search for color pickers.
                 */
                function initColorPickers(container) {
                    if (!$.fn.wpColorPicker) return;
                    $(container).find('.ka-color-picker').each(function() {
                        if ($(this).closest('.wp-picker-container').length) return;
                        $(this).wpColorPicker({
                            defaultColor: $(this).data('default-color') || '',
                            change: function() {
                                updateShadowPreview();
                            }
                        });
                    });
                }

                /**
                 * Update shadow preview box.
                 */
                function updateShadowPreview() {
                    const x = $('#ka-shadow-x').val() || 0;
                    const y = $('#ka-shadow-y').val() || 0;
                    const blur = $('#ka-shadow-blur').val() || 0;
                    const spread = $('#ka-shadow-spread').val() || 0;
                    const colorInput = $('#ka-shadow-color');
                    let color = colorInput.val() || 'rgba(0,0,0,0.15)';
                    
                    // Get color from wp-color-picker if initialized
                    if (colorInput.closest('.wp-picker-container').length) {
                        color = colorInput.wpColorPicker('color') || color;
                    }
                    
                    const shadow = x + 'px ' + y + 'px ' + blur + 'px ' + spread + 'px ' + color;
                    $('#ka-shadow-preview').css('box-shadow', shadow);
                    $('#ka-shadow-combined').val(shadow);
                }

                $(document).ready(function() {
                    // Initialize color pickers on page load
                    initColorPickers(document);

                    // Shadow builder - update preview on any change
                    $('#ka-shadow-x, #ka-shadow-y, #ka-shadow-blur, #ka-shadow-spread').on('input change', updateShadowPreview);
                    
                    // Initial shadow preview
                    setTimeout(updateShadowPreview, 500);

                    // Button items repeater
                    const container = document.getElementById('ka-scbar-items');
                    const addBtn = document.getElementById('ka-scbar-add-item');
                    const tpl = document.getElementById('ka-scbar-item-template');
                    if (container && addBtn && tpl) {
                        addBtn.addEventListener('click', function() {
                            const idx = container.querySelectorAll('.ka-scb-item').length;
                            const html = tpl.innerHTML.replace(/__i__/g, idx);
                            const wrap = document.createElement('div');
                            wrap.innerHTML = html;
                            const row = wrap.firstElementChild;
                            container.appendChild(row);
                            // Initialize color pickers in new row
                            initColorPickers(row);
                        });

                        container.addEventListener('click', function(e) {
                            const btn = e.target.closest('.ka-scb-remove-btn');
                            if (!btn) return;
                            e.preventDefault();
                            const row = btn.closest('.ka-scb-item');
                            if (row) row.remove();
                        });
                    }

                    // Conditions (Pro)
                    const condContainer = document.getElementById('ka-scbar-conditions');
                    const condAddBtn = document.getElementById('ka-scbar-add-condition');
                    const condTpl = document.getElementById('ka-scbar-condition-template');
                    if (condContainer && condAddBtn && condTpl) {
                        condAddBtn.addEventListener('click', function() {
                            const idx = condContainer.querySelectorAll('.ka-scb-condition').length;
                            const html = condTpl.innerHTML.replace(/__c__/g, idx);
                            const wrap = document.createElement('div');
                            wrap.innerHTML = html;
                            const row = wrap.firstElementChild;
                            condContainer.appendChild(row);
                        });

                        condContainer.addEventListener('click', function(e) {
                            const btn = e.target.closest('.ka-scb-remove-condition');
                            if (!btn) return;
                            e.preventDefault();
                            const row = btn.closest('.ka-scb-condition');
                            if (row) row.remove();
                        });
                    }
                });
            })(jQuery);
        </script>
        <?php
    }

    /**
     * Render single item row for admin repeater.
     *
     * @param int|string $index   Item index.
     * @param array      $item    Item data.
     * @param bool       $is_pro  Whether pro is active.
     *
     * @return void
     */
    protected function render_item_row($index, array $item, bool $is_pro): void
    {
        $prefix = self::OPTION_KEY . '[items][' . $index . ']';
        $type = $item['type'] ?? 'phone';
        $type_labels = [
            'phone' => esc_html__('Phone', 'king-addons'),
            'email' => esc_html__('Email', 'king-addons'),
            'link' => esc_html__('URL', 'king-addons'),
            'whatsapp' => esc_html__('WhatsApp', 'king-addons'),
            'telegram' => esc_html__('Telegram', 'king-addons'),
        ];
        $label_text = $item['label'] ?? ($type_labels[$type] ?? ucfirst($type));
        ?>
        <div class="ka-scb-item">
            <div class="ka-scb-item-header">
                <span class="ka-scb-item-title"><?php echo esc_html($label_text); ?></span>
                <button type="button" class="ka-scb-remove-btn"><?php esc_html_e('Remove', 'king-addons'); ?></button>
            </div>
            <div class="ka-scb-item-grid">
                <div class="ka-scb-item-row">
                    <span class="ka-scb-item-label"><?php esc_html_e('Type', 'king-addons'); ?></span>
                    <select name="<?php echo esc_attr($prefix); ?>[type]">
                        <option value="phone" <?php selected($type, 'phone'); ?>><?php esc_html_e('Phone', 'king-addons'); ?></option>
                        <option value="email" <?php selected($type, 'email'); ?>><?php esc_html_e('Email', 'king-addons'); ?></option>
                        <option value="link" <?php selected($type, 'link'); ?>><?php esc_html_e('Custom URL', 'king-addons'); ?></option>
                        <option value="whatsapp" <?php selected($type, 'whatsapp'); ?>><?php esc_html_e('WhatsApp', 'king-addons'); ?></option>
                        <option value="telegram" <?php selected($type, 'telegram'); ?>><?php esc_html_e('Telegram', 'king-addons'); ?></option>
                        <option value="popup" <?php selected($type, 'popup'); ?> <?php disabled(!$is_pro); ?>><?php esc_html_e('Popup (Pro)', 'king-addons'); ?></option>
                        <option value="messenger" <?php selected($type, 'messenger'); ?> <?php disabled(!$is_pro); ?>><?php esc_html_e('Messenger (Pro)', 'king-addons'); ?></option>
                        <option value="viber" <?php selected($type, 'viber'); ?> <?php disabled(!$is_pro); ?>><?php esc_html_e('Viber (Pro)', 'king-addons'); ?></option>
                    </select>
                </div>
                <div class="ka-scb-item-row">
                    <span class="ka-scb-item-label"><?php esc_html_e('Label', 'king-addons'); ?></span>
                    <input type="text" name="<?php echo esc_attr($prefix); ?>[label]" value="<?php echo esc_attr($item['label'] ?? ''); ?>" placeholder="<?php esc_attr_e('Tooltip text', 'king-addons'); ?>" />
                </div>
                <div class="ka-scb-item-row">
                    <span class="ka-scb-item-label"><?php esc_html_e('Icon Class', 'king-addons'); ?></span>
                    <input type="text" name="<?php echo esc_attr($prefix); ?>[icon]" value="<?php echo esc_attr($item['icon'] ?? ''); ?>" placeholder="fa-brands fa-whatsapp" />
                </div>
                <div class="ka-scb-item-row">
                    <span class="ka-scb-item-label"><?php esc_html_e('Phone', 'king-addons'); ?></span>
                    <input type="text" name="<?php echo esc_attr($prefix); ?>[phone]" value="<?php echo esc_attr($item['phone'] ?? ''); ?>" placeholder="+123456789" />
                </div>
                <div class="ka-scb-item-row">
                    <span class="ka-scb-item-label"><?php esc_html_e('Email', 'king-addons'); ?></span>
                    <input type="email" name="<?php echo esc_attr($prefix); ?>[email]" value="<?php echo esc_attr($item['email'] ?? ''); ?>" />
                </div>
                <div class="ka-scb-item-row">
                    <span class="ka-scb-item-label"><?php esc_html_e('URL', 'king-addons'); ?></span>
                    <input type="text" name="<?php echo esc_attr($prefix); ?>[url]" value="<?php echo esc_attr($item['url'] ?? ''); ?>" placeholder="https://" />
                </div>
                <div class="ka-scb-item-row">
                    <span class="ka-scb-item-label"><?php esc_html_e('Username', 'king-addons'); ?></span>
                    <input type="text" name="<?php echo esc_attr($prefix); ?>[username]" value="<?php echo esc_attr($item['username'] ?? ''); ?>" placeholder="@username" />
                </div>
                <div class="ka-scb-item-row">
                    <span class="ka-scb-item-label"><?php esc_html_e('Background', 'king-addons'); ?></span>
                    <input type="text" name="<?php echo esc_attr($prefix); ?>[color]" value="<?php echo esc_attr($item['color'] ?? ''); ?>" data-alpha-enabled="true" data-default-color="#25D366" class="ka-color-picker" />
                </div>
                <div class="ka-scb-item-row">
                    <span class="ka-scb-item-label"><?php esc_html_e('Hover BG', 'king-addons'); ?></span>
                    <input type="text" name="<?php echo esc_attr($prefix); ?>[color_hover]" value="<?php echo esc_attr($item['color_hover'] ?? ''); ?>" data-alpha-enabled="true" data-default-color="#1da851" class="ka-color-picker" />
                </div>
                <div class="ka-scb-item-row">
                    <span class="ka-scb-item-label"><?php esc_html_e('Icon Color', 'king-addons'); ?></span>
                    <input type="text" name="<?php echo esc_attr($prefix); ?>[icon_color]" value="<?php echo esc_attr($item['icon_color'] ?? ''); ?>" data-alpha-enabled="true" data-default-color="#ffffff" class="ka-color-picker" />
                </div>
                <div class="ka-scb-item-row">
                    <span class="ka-scb-item-label"><?php esc_html_e('Target', 'king-addons'); ?></span>
                    <select name="<?php echo esc_attr($prefix); ?>[target]">
                        <option value="_self" <?php selected($item['target'] ?? '', '_self'); ?>><?php esc_html_e('Same Tab', 'king-addons'); ?></option>
                        <option value="_blank" <?php selected($item['target'] ?? '', '_blank'); ?>><?php esc_html_e('New Tab', 'king-addons'); ?></option>
                    </select>
                </div>
                <div class="ka-scb-item-row">
                    <span class="ka-scb-item-label"><?php esc_html_e('Order', 'king-addons'); ?></span>
                    <input type="number" name="<?php echo esc_attr($prefix); ?>[order]" value="<?php echo esc_attr($item['order'] ?? 0); ?>" style="max-width:80px" />
                </div>
            </div>
            <!-- Hidden fields for full data preservation -->
            <input type="hidden" name="<?php echo esc_attr($prefix); ?>[rel]" value="<?php echo esc_attr($item['rel'] ?? 'noopener noreferrer'); ?>" />
            <input type="hidden" name="<?php echo esc_attr($prefix); ?>[icon_color_hover]" value="<?php echo esc_attr($item['icon_color_hover'] ?? ''); ?>" />
            <input type="hidden" name="<?php echo esc_attr($prefix); ?>[popup_id]" value="<?php echo esc_attr($item['popup_id'] ?? ''); ?>" />
            <input type="hidden" name="<?php echo esc_attr($prefix); ?>[offcanvas_id]" value="<?php echo esc_attr($item['offcanvas_id'] ?? ''); ?>" />
            <input type="hidden" name="<?php echo esc_attr($prefix); ?>[scroll_target]" value="<?php echo esc_attr($item['scroll_target'] ?? ''); ?>" />
            <input type="hidden" name="<?php echo esc_attr($prefix); ?>[show_label]" value="<?php echo !empty($item['show_label']) ? '1' : ''; ?>" />
            <input type="hidden" name="<?php echo esc_attr($prefix); ?>[badge_text]" value="<?php echo esc_attr($item['badge_text'] ?? ''); ?>" />
            <input type="hidden" name="<?php echo esc_attr($prefix); ?>[badge_color]" value="<?php echo esc_attr($item['badge_color'] ?? ''); ?>" />
            <input type="hidden" name="<?php echo esc_attr($prefix); ?>[pulse]" value="<?php echo !empty($item['pulse']) ? '1' : ''; ?>" />
            <input type="hidden" name="<?php echo esc_attr($prefix); ?>[desktop_only]" value="<?php echo !empty($item['desktop_only']) ? '1' : ''; ?>" />
            <input type="hidden" name="<?php echo esc_attr($prefix); ?>[mobile_only]" value="<?php echo !empty($item['mobile_only']) ? '1' : ''; ?>" />
        </div>
        <?php
    }

    /**
     * Render a single condition row for admin.
     *
     * @param int|string          $index     Row index.
     * @param array<string,string> $condition Condition data.
     *
     * @return void
     */
    protected function render_condition_row($index, array $condition): void
    {
        $prefix = self::OPTION_KEY . '[conditions][' . $index . ']';
        $type = $condition['type'] ?? 'page_type';
        $operator = $condition['operator'] ?? 'is';
        $value = $condition['value'] ?? '';
        ?>
        <div class="ka-scb-condition" style="display:flex;gap:10px;align-items:center;margin-bottom:10px;padding:12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;">
            <select name="<?php echo esc_attr($prefix); ?>[type]" style="flex:1;padding:6px 10px;border:1px solid #e2e8f0;border-radius:6px;">
                <option value="page_type" <?php selected($type, 'page_type'); ?>><?php esc_html_e('Page Type', 'king-addons'); ?></option>
                <option value="user_role" <?php selected($type, 'user_role'); ?>><?php esc_html_e('User Role', 'king-addons'); ?></option>
                <option value="url_contains" <?php selected($type, 'url_contains'); ?>><?php esc_html_e('URL Contains', 'king-addons'); ?></option>
                <option value="post_id" <?php selected($type, 'post_id'); ?>><?php esc_html_e('Post ID', 'king-addons'); ?></option>
            </select>
            <select name="<?php echo esc_attr($prefix); ?>[operator]" style="width:100px;padding:6px 10px;border:1px solid #e2e8f0;border-radius:6px;">
                <option value="is" <?php selected($operator, 'is'); ?>><?php esc_html_e('Is', 'king-addons'); ?></option>
                <option value="is_not" <?php selected($operator, 'is_not'); ?>><?php esc_html_e('Is Not', 'king-addons'); ?></option>
            </select>
            <input type="text" name="<?php echo esc_attr($prefix); ?>[value]" value="<?php echo esc_attr($value); ?>" placeholder="<?php esc_attr_e('Value', 'king-addons'); ?>" style="flex:1;padding:6px 10px;border:1px solid #e2e8f0;border-radius:6px;" />
            <button type="button" class="ka-scb-remove-condition" style="background:#fee2e2;color:#dc2626;border:none;border-radius:6px;padding:6px 12px;cursor:pointer;">&times;</button>
        </div>
        <?php
    }

    /**
     * Render the bar on frontend.
     *
     * @return void
     */
    public function render_bar(): void
    {
        $settings = $this->get_settings();

        if (empty($settings['enabled'])) {
            return;
        }

        $settings = $this->apply_license_restrictions($settings);

        if (!$this->check_display_conditions($settings)) {
            return;
        }

        $items = is_array($settings['items']) ? $settings['items'] : [];
        if (empty($items)) {
            return;
        }

        wp_enqueue_style(KING_ADDONS_ASSETS_UNIQUE_KEY . '-sticky-contact-bar');
        wp_enqueue_script(KING_ADDONS_ASSETS_UNIQUE_KEY . '-sticky-contact-bar');

        echo $this->get_bar_html($settings, $items); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    /**
     * Check simple display conditions (devices).
     *
     * @param array<string,mixed> $settings Settings.
     *
     * @return bool
     */
    protected function check_display_conditions(array $settings): bool
    {
        $devices = isset($settings['devices']) && is_array($settings['devices']) ? $settings['devices'] : ['desktop', 'mobile'];
        $is_mobile = wp_is_mobile();

        if (in_array('mobile', $devices, true) && $is_mobile) {
            return true;
        }

        if (in_array('desktop', $devices, true) && !$is_mobile) {
            return true;
        }

        return false;
    }

    /**
     * Build bar HTML.
     *
     * @param array<string,mixed> $settings Settings.
     * @param array<int,array>    $items    Items.
     *
     * @return string
     */
    protected function get_bar_html(array $settings, array $items): string
    {
        $position = $settings['position'] ?? 'bottom';
        $alignment = $settings['alignment'] ?? 'center';

        $classes = [
            'ka-sticky-contact-bar',
            'ka-position-' . esc_attr($position),
            'ka-align-' . esc_attr($alignment),
            'ka-shape-' . esc_attr($settings['item_shape'] ?? 'circle'),
            'is-hidden',
        ];

        $style_parts = [];
        if (!empty($settings['background'])) {
            $style_parts[] = '--ka-scbar-bg:' . $settings['background'];
        }
        if (!empty($settings['border_radius'])) {
            $style_parts[] = '--ka-scbar-radius:' . absint($settings['border_radius']) . 'px';
        }
        if (!empty($settings['shadow'])) {
            $style_parts[] = '--ka-scbar-shadow:' . $settings['shadow'];
        }
        if (!empty($settings['item_size'])) {
            $style_parts[] = '--ka-scbar-size:' . absint($settings['item_size']) . 'px';
        }
        if (isset($settings['item_spacing'])) {
            $style_parts[] = '--ka-scbar-gap:' . absint($settings['item_spacing']) . 'px';
        }
        if (isset($settings['z_index'])) {
            $style_parts[] = '--ka-scbar-z:' . (int) $settings['z_index'];
        }

        $data_attrs = [
            'data-trigger="' . esc_attr($settings['trigger'] ?? 'always') . '"',
            'data-trigger-scroll="' . esc_attr((int) ($settings['trigger_scroll'] ?? 0)) . '"',
            'data-trigger-delay="' . esc_attr((int) ($settings['trigger_delay'] ?? 0)) . '"',
            'data-hide-on-scroll-down="' . (!empty($settings['hide_on_scroll_down']) ? 'yes' : 'no') . '"',
            'data-show-on-scroll-up="' . (!empty($settings['show_on_scroll_up']) ? 'yes' : 'no') . '"',
            'data-analytics-enabled="' . (!empty($settings['analytics_enabled']) ? 'yes' : 'no') . '"',
        ];

        usort(
            $items,
            static function ($a, $b) {
                return (int) ($a['order'] ?? 0) <=> (int) ($b['order'] ?? 0);
            }
        );

        $items_html = '';
        foreach ($items as $item) {
            $item_html = $this->build_item_html($item);
            if ($item_html) {
                $items_html .= $item_html;
            }
        }

        if ('' === $items_html) {
            return '';
        }

        $style_attr = !empty($style_parts) ? ' style="' . esc_attr(implode(';', $style_parts)) . '"' : '';

        return '<div class="' . esc_attr(implode(' ', $classes)) . '" ' . implode(' ', $data_attrs) . $style_attr . '><div class="ka-sticky-contact-bar__inner">' . $items_html . '</div></div>';
    }

    /**
     * Build single item HTML.
     *
     * @param array<string,mixed> $item Item data.
     *
     * @return string
     */
    protected function build_item_html(array $item): string
    {
        $type = $item['type'] ?? 'phone';
        $free_types = ['phone', 'email', 'link', 'whatsapp', 'telegram'];
        $is_pro = $this->can_use_pro();

        if (!$is_pro && !in_array($type, $free_types, true)) {
            return '';
        }

        $href = $this->get_item_href($type, $item);
        if ('' === $href) {
            return '';
        }

        $classes = ['ka-sticky-contact-item', 'ka-type-' . esc_attr($type)];
        if (!empty($item['pulse']) && $is_pro) {
            $classes[] = 'ka-sticky-contact-item--pulse';
        }
        if (!empty($item['show_label']) && $is_pro) {
            $classes[] = 'ka-sticky-contact-item--label-visible';
        }
        if (!empty($item['desktop_only'])) {
            $classes[] = 'ka-desktop-only';
        }
        if (!empty($item['mobile_only'])) {
            $classes[] = 'ka-mobile-only';
        }

        $style_parts = [];
        if (!empty($item['color'])) {
            $style_parts[] = '--ka-scbar-item-bg:' . $item['color'];
        }
        if (!empty($item['color_hover'])) {
            $style_parts[] = '--ka-scbar-item-bg-hover:' . $item['color_hover'];
        }
        if (!empty($item['icon_color'])) {
            $style_parts[] = '--ka-scbar-icon-color:' . $item['icon_color'];
        }
        if (!empty($item['icon_color_hover'])) {
            $style_parts[] = '--ka-scbar-icon-color-hover:' . $item['icon_color_hover'];
        }

        $aria = !empty($item['label']) ? $item['label'] : ucfirst($type);

        $data = [];
        if (in_array($type, ['popup', 'offcanvas', 'scroll'], true)) {
            $data[] = 'data-action="' . esc_attr($type) . '"';
        }
        if ('popup' === $type && !empty($item['popup_id'])) {
            $data[] = 'data-popup-id="' . esc_attr($item['popup_id']) . '"';
        }
        if ('offcanvas' === $type && !empty($item['offcanvas_id'])) {
            $data[] = 'data-offcanvas-id="' . esc_attr($item['offcanvas_id']) . '"';
        }
        if ('scroll' === $type && !empty($item['scroll_target'])) {
            $data[] = 'data-scroll-target="' . esc_attr($item['scroll_target']) . '"';
        }

        $label_html = '';
        if (!empty($item['show_label']) && $is_pro && !empty($item['label'])) {
            $label_html = '<span class="ka-sticky-contact-item__label">' . esc_html($item['label']) . '</span>';
        }

        $badge_html = '';
        if (!empty($item['badge_text']) && $is_pro) {
            $badge_style = '';
            if (!empty($item['badge_color'])) {
                $badge_style = ' style="background:' . esc_attr($item['badge_color']) . ';"';
            }
            $badge_html = '<span class="ka-sticky-contact-item__badge"' . $badge_style . '>' . esc_html($item['badge_text']) . '</span>';
        }

        $icon_html = '';
        if (!empty($item['icon'])) {
            $icon_html = '<span class="ka-sticky-contact-icon"><i class="' . esc_attr($item['icon']) . '" aria-hidden="true"></i></span>';
        } else {
            // Use default SVG icon based on type
            $default_svg = $this->get_default_icon_svg($type);
            if ($default_svg) {
                $icon_html = '<span class="ka-sticky-contact-icon">' . $default_svg . '</span>';
            }
        }

        $style_attr = !empty($style_parts) ? ' style="' . esc_attr(implode(';', $style_parts)) . '"' : '';

        $target = !empty($item['target']) ? $item['target'] : '_self';
        $rel = !empty($item['rel']) ? $item['rel'] : 'noopener noreferrer';

        return '<a class="' . esc_attr(implode(' ', $classes)) . '" href="' . esc_url($href) . '" aria-label="' . esc_attr($aria) . '" target="' . esc_attr($target) . '" rel="' . esc_attr($rel) . '"' . $style_attr . ' ' . implode(' ', $data) . '>' . $icon_html . $label_html . $badge_html . '</a>';
    }

    /**
     * Get href per item type.
     *
     * @param string               $type Item type.
     * @param array<string,mixed>  $item Item data.
     *
     * @return string
     */
    protected function get_item_href(string $type, array $item): string
    {
        switch ($type) {
            case 'phone':
                return !empty($item['phone']) ? 'tel:' . preg_replace('/\s+/', '', $item['phone']) : '';
            case 'email':
                return !empty($item['email']) ? 'mailto:' . sanitize_email($item['email']) : '';
            case 'whatsapp':
                if (!empty($item['url'])) {
                    return esc_url_raw($item['url']);
                }
                if (!empty($item['phone'])) {
                    $num = preg_replace('/\D+/', '', $item['phone']);
                    return $num ? 'https://wa.me/' . $num : '';
                }
                return '';
            case 'telegram':
                if (!empty($item['url'])) {
                    return esc_url_raw($item['url']);
                }
                if (!empty($item['username'])) {
                    return 'https://t.me/' . ltrim($item['username'], '@');
                }
                return '';
            case 'link':
                return !empty($item['url']) ? esc_url_raw($item['url']) : '';
            case 'messenger':
                if (!empty($item['url'])) {
                    return esc_url_raw($item['url']);
                }
                if (!empty($item['username'])) {
                    return 'https://m.me/' . ltrim($item['username'], '@');
                }
                return '';
            case 'viber':
                if (!empty($item['url'])) {
                    return esc_url_raw($item['url']);
                }
                if (!empty($item['phone'])) {
                    $num = preg_replace('/\D+/', '', $item['phone']);
                    return $num ? 'viber://chat?number=' . $num : '';
                }
                return '';
            case 'popup':
            case 'offcanvas':
            case 'scroll':
                return 'javascript:void(0)';
            default:
                return '';
        }
    }

    /**
     * Get default SVG icon for a button type.
     *
     * @param string $type Button type.
     *
     * @return string SVG markup or empty string.
     */
    protected function get_default_icon_svg(string $type): string
    {
        $icons = [
            'phone' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6.62 10.79c1.44 2.83 3.76 5.15 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.24.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>',
            'email' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4-8 5-8-5V6l8 5 8-5v2z"/></svg>',
            'whatsapp' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>',
            'telegram' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M11.944 0A12 12 0 000 12a12 12 0 0012 12 12 12 0 0012-12A12 12 0 0012 0a12 12 0 00-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 01.171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.015 3.333-1.386 4.025-1.627 4.476-1.635z"/></svg>',
            'messenger' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 0C5.373 0 0 4.974 0 11.111c0 3.497 1.745 6.616 4.472 8.652V24l4.086-2.242c1.09.301 2.246.464 3.442.464 6.627 0 12-4.974 12-11.111S18.627 0 12 0zm1.193 14.963l-3.056-3.259-5.963 3.259 6.559-6.963 3.13 3.259 5.889-3.259-6.559 6.963z"/></svg>',
            'viber' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M11.4 0C9.473.028 5.333.344 3.02 2.467 1.302 4.187.712 6.692.622 9.836c-.09 3.144-.198 9.037 5.533 10.618v2.428s-.037.978.609 1.178c.778.243 1.235-.501 1.98-1.302.409-.44.972-1.088 1.397-1.583 3.85.323 6.813-.416 7.15-.527.776-.257 5.17-.815 5.884-6.651.735-6.016-.357-9.818-2.362-11.553C18.986.751 16.13.233 12.021.035 11.827.023 11.613.003 11.4 0zm.077 1.932c.096.002.2.007.313.015 3.758.18 6.282.61 7.952 2.094 1.7 1.468 2.611 4.753 1.974 9.992-.59 4.788-4.01 5.262-4.655 5.475-.279.092-2.905.744-6.212.52 0 0-2.464 2.971-3.233 3.742-.12.121-.268.166-.363.146-.134-.028-.171-.163-.17-.36l.012-4.053c-4.917-1.359-4.832-6.418-4.755-9.083.077-2.664.558-4.79 2.015-6.238 1.902-1.76 5.434-2.29 6.81-2.24.087-.004.19-.006.312-.01z"/><path d="M11.941 4.006c-.287 0-.287.446 0 .449 2.93.022 5.457 2.109 5.479 5.783.002.289.446.287.444 0-.024-3.965-2.723-6.21-5.923-6.232zm.007 2.119c-.285 0-.283.443.003.443 1.564.02 2.888 1.074 2.903 2.955.001.287.444.285.443-.002-.017-2.137-1.5-3.376-3.349-3.396zm.033 2.113c-.287-.001-.288.443 0 .445.663.01 1.244.561 1.252 1.297.001.287.443.284.442-.003-.01-.981-.71-1.728-1.694-1.739zm-4.165.02c.271.036.458.194.533.302.302.428.587.867.862 1.311.226.368.152.742-.167 1.011l-.57.507c-.119.115-.194.283-.082.479.856 1.484 1.811 2.459 3.456 3.474.18.104.36.095.494-.028.478-.435.941-.89 1.392-1.353.21-.217.55-.215.799-.042.484.341.959.694 1.427 1.055.295.227.326.618.103.887-.497.599-.996 1.196-1.49 1.796-.317.385-.767.464-1.214.356-.91-.221-1.773-.577-2.587-1.032-1.82-1.01-3.422-2.291-4.715-3.953-.6-.77-1.101-1.594-1.424-2.52-.17-.487.012-.851.355-1.177.407-.384.823-.759 1.234-1.14.175-.163.376-.25.594-.233z"/></svg>',
            'link' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"/></svg>',
            'popup' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M19 4H5c-1.11 0-2 .9-2 2v12c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 14H5V8h14v10z"/></svg>',
            'offcanvas' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>',
            'scroll' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 8l-6 6 1.41 1.41L12 10.83l4.59 4.58L18 14z"/></svg>',
        ];

        return $icons[$type] ?? '';
    }

    /**
     * Get settings merged with defaults.
     *
     * @return array<string,mixed>
     */
    public function get_settings(): array
    {
        $saved = get_option(self::OPTION_KEY, []);
        if (!is_array($saved)) {
            $saved = [];
        }
        return wp_parse_args($saved, $this->get_settings_defaults());
    }

    /**
     * Default settings.
     *
     * @return array<string,mixed>
     */
    protected function get_settings_defaults(): array
    {
        return [
            'enabled' => false,
            'position' => 'bottom',
            'alignment' => 'center',
            'devices' => ['mobile', 'desktop'],
            'trigger' => 'always',
            'trigger_scroll' => 100,
            'trigger_delay' => 0,
            'z_index' => 9999,
            'background' => 'rgba(20,20,20,0.95)',
            'border_radius' => 14,
            'shadow' => '0px 10px 30px 0px rgba(0,0,0,0.18)',
            'shadow_x' => 0,
            'shadow_y' => 10,
            'shadow_blur' => 30,
            'shadow_spread' => 0,
            'shadow_color' => 'rgba(0,0,0,0.18)',
            'item_size' => 48,
            'item_spacing' => 10,
            'item_shape' => 'circle',
            'hide_on_scroll_down' => false,
            'show_on_scroll_up' => false,
            'analytics_enabled' => false,
            'items' => [],
        ];
    }

    /**
     * Sanitize settings.
     *
     * @param array<string,mixed> $input Raw input.
     *
     * @return array<string,mixed>
     */
    public function sanitize_settings($input): array
    {
        $defaults = $this->get_settings_defaults();

        // Shadow fields - construct combined shadow string from components
        $shadow_x = isset($input['shadow_x']) ? (int) $input['shadow_x'] : $defaults['shadow_x'];
        $shadow_y = isset($input['shadow_y']) ? (int) $input['shadow_y'] : $defaults['shadow_y'];
        $shadow_blur = isset($input['shadow_blur']) ? absint($input['shadow_blur']) : $defaults['shadow_blur'];
        $shadow_spread = isset($input['shadow_spread']) ? (int) $input['shadow_spread'] : $defaults['shadow_spread'];
        $shadow_color = sanitize_text_field($input['shadow_color'] ?? $defaults['shadow_color']);
        $shadow_combined = $shadow_x . 'px ' . $shadow_y . 'px ' . $shadow_blur . 'px ' . $shadow_spread . 'px ' . $shadow_color;

        $output = [
            'enabled' => !empty($input['enabled']),
            'position' => in_array($input['position'] ?? 'bottom', ['bottom', 'left', 'right'], true) ? $input['position'] : 'bottom',
            'alignment' => in_array($input['alignment'] ?? 'center', ['center', 'left', 'right'], true) ? $input['alignment'] : 'center',
            'devices' => [],
            'trigger' => in_array($input['trigger'] ?? 'always', ['always', 'scroll', 'delay'], true) ? $input['trigger'] : 'always',
            'trigger_scroll' => isset($input['trigger_scroll']) ? absint($input['trigger_scroll']) : $defaults['trigger_scroll'],
            'trigger_delay' => isset($input['trigger_delay']) ? absint($input['trigger_delay']) : $defaults['trigger_delay'],
            'z_index' => isset($input['z_index']) ? (int) $input['z_index'] : $defaults['z_index'],
            'background' => sanitize_text_field($input['background'] ?? $defaults['background']),
            'border_radius' => isset($input['border_radius']) ? absint($input['border_radius']) : $defaults['border_radius'],
            'shadow' => $shadow_combined,
            'shadow_x' => $shadow_x,
            'shadow_y' => $shadow_y,
            'shadow_blur' => $shadow_blur,
            'shadow_spread' => $shadow_spread,
            'shadow_color' => $shadow_color,
            'item_size' => isset($input['item_size']) ? absint($input['item_size']) : $defaults['item_size'],
            'item_spacing' => isset($input['item_spacing']) ? absint($input['item_spacing']) : $defaults['item_spacing'],
            'item_shape' => in_array($input['item_shape'] ?? 'circle', ['circle', 'rounded', 'square'], true) ? $input['item_shape'] : 'circle',
            'hide_on_scroll_down' => !empty($input['hide_on_scroll_down']),
            'show_on_scroll_up' => !empty($input['show_on_scroll_up']),
            'analytics_enabled' => !empty($input['analytics_enabled']),
            'items' => [],
        ];

        if (!empty($input['devices']) && is_array($input['devices'])) {
            foreach ($input['devices'] as $device) {
                if (in_array($device, ['desktop', 'mobile'], true)) {
                    $output['devices'][] = $device;
                }
            }
        }
        if (empty($output['devices'])) {
            $output['devices'] = ['mobile', 'desktop'];
        }

        if (!empty($input['items']) && is_array($input['items'])) {
            foreach ($input['items'] as $item) {
                $output['items'][] = [
                    'type' => sanitize_text_field($item['type'] ?? 'phone'),
                    'label' => sanitize_text_field($item['label'] ?? ''),
                    'icon' => sanitize_text_field($item['icon'] ?? ''),
                    'color' => sanitize_text_field($item['color'] ?? ''),
                    'color_hover' => sanitize_text_field($item['color_hover'] ?? ''),
                    'icon_color' => sanitize_text_field($item['icon_color'] ?? ''),
                    'icon_color_hover' => sanitize_text_field($item['icon_color_hover'] ?? ''),
                    'url' => esc_url_raw($item['url'] ?? ''),
                    'phone' => sanitize_text_field($item['phone'] ?? ''),
                    'username' => sanitize_text_field($item['username'] ?? ''),
                    'email' => sanitize_email($item['email'] ?? ''),
                    'popup_id' => sanitize_text_field($item['popup_id'] ?? ''),
                    'offcanvas_id' => sanitize_text_field($item['offcanvas_id'] ?? ''),
                    'scroll_target' => sanitize_text_field($item['scroll_target'] ?? ''),
                    'target' => in_array($item['target'] ?? '_self', ['_self', '_blank'], true) ? $item['target'] : '_self',
                    'rel' => sanitize_text_field($item['rel'] ?? 'noopener noreferrer'),
                    'show_label' => !empty($item['show_label']),
                    'badge_text' => sanitize_text_field($item['badge_text'] ?? ''),
                    'badge_color' => sanitize_text_field($item['badge_color'] ?? ''),
                    'pulse' => !empty($item['pulse']),
                    'desktop_only' => !empty($item['desktop_only']),
                    'mobile_only' => !empty($item['mobile_only']),
                    'order' => isset($item['order']) ? (int) $item['order'] : 0,
                ];
            }
        }

        return $output;
    }

    /**
     * Apply license restrictions to settings (remove Pro-only bits).
     *
     * @param array<string,mixed> $settings Settings.
     *
     * @return array<string,mixed>
     */
    protected function apply_license_restrictions(array $settings): array
    {
        if ($this->can_use_pro()) {
            return $settings;
        }

        // Free limitations.
        $settings['position'] = 'bottom';
        if ('delay' === ($settings['trigger'] ?? 'always')) {
            $settings['trigger'] = 'always';
            $settings['trigger_delay'] = 0;
        }
        $settings['hide_on_scroll_down'] = false;
        $settings['show_on_scroll_up'] = false;

        $settings['items'] = array_values(
            array_filter(
                $settings['items'],
                static function ($item) {
                    return in_array($item['type'] ?? 'phone', ['phone', 'email', 'link', 'whatsapp', 'telegram'], true);
                }
            )
        );

        foreach ($settings['items'] as &$item) {
            $item['pulse'] = false;
            $item['badge_text'] = '';
            $item['badge_color'] = '';
            $item['show_label'] = false;
        }
        unset($item);

        return $settings;
    }

    /**
     * Check if Pro is available.
     *
     * @return bool
     */
    protected function can_use_pro(): bool
    {
        if (!function_exists('king_addons_freemius')) {
            return false;
        }

        $fs = king_addons_freemius();
        if (!is_object($fs) || !method_exists($fs, 'can_use_premium_code')) {
            return false;
        }

        return (bool) $fs->can_use_premium_code();
    }
}







