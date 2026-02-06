<?php
/**
 * Custom Cursor feature.
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Element_Base;
use Elementor\Plugin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Provides global custom cursor with Elementor integration.
 */
class Custom_Cursor
{
    /**
     * Option key for storing settings.
     */
    public const OPTION_KEY = 'king_addons_custom_cursor_settings';

    /**
     * Admin screen hook suffix for the settings page.
     *
     * @var string
     */
    private string $settings_page_hook = '';

    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action('admin_init', [$this, 'register_settings']);
        // Use priority 15 to ensure the parent menu exists before adding this submenu
        add_action('admin_menu', [$this, 'register_settings_page'], 15);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_enqueue_scripts', [$this, 'maybe_enqueue_assets']);
        add_action('elementor/preview/enqueue_scripts', [$this, 'enqueue_preview_assets']);
        add_action('wp_footer', [$this, 'render_cursor_markup']);
        add_filter('body_class', [$this, 'filter_body_classes']);

        add_action('elementor/element/common/_section_style/after_section_end', [$this, 'register_elementor_controls'], 10, 2);
        add_action('elementor/element/section/section_advanced/after_section_end', [$this, 'register_elementor_controls'], 10, 2);
        add_action('elementor/element/container/section_layout/after_section_end', [$this, 'register_elementor_controls'], 10, 2);
        add_action('elementor/element/column/section_advanced/after_section_end', [$this, 'register_elementor_controls'], 10, 2);
        add_action('elementor/frontend/widget/before_render', [$this, 'apply_elementor_attributes']);
        add_action('elementor/frontend/section/before_render', [$this, 'apply_elementor_attributes']);
        add_action('elementor/frontend/container/before_render', [$this, 'apply_elementor_attributes']);
        add_action('elementor/frontend/column/before_render', [$this, 'apply_elementor_attributes']);
    }

    /**
     * Register settings in WordPress.
     *
     * @return void
     */
    public function register_settings(): void
    {
        register_setting(
            'king_addons_custom_cursor',
            self::OPTION_KEY,
            [
                'type' => 'array',
                'sanitize_callback' => [$this, 'sanitize_settings'],
                'default' => $this->get_default_settings(),
            ]
        );
    }

    /**
     * Register settings page.
     *
     * @return void
     */
    public function register_settings_page(): void
    {
        $this->settings_page_hook = (string) add_submenu_page(
            'king-addons',
            esc_html__('Custom Cursor', 'king-addons'),
            esc_html__('Custom Cursor', 'king-addons'),
            'manage_options',
            'king-addons-custom-cursor',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Enqueue admin assets for the Custom Cursor settings page.
     *
     * @param string $hook_suffix Current admin page hook suffix.
     *
     * @return void
     */
    public function enqueue_admin_assets(string $hook_suffix): void
    {
        if ('' === $this->settings_page_hook) {
            return;
        }

        if ($hook_suffix !== $this->settings_page_hook) {
            return;
        }

        // Required for wp.media modal.
        wp_enqueue_media();
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

        $settings = $this->get_settings();
        $is_pro = $this->can_use_pro();

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
            /* Custom Cursor V3 Additional Styles */
            .ka-cc-v3 {
                display: flex;
                gap: 30px;
                max-width: 1400px;
            }
            .ka-cc-v3 .ka-cc-main {
                flex: 1;
                min-width: 0;
            }

            /* Color picker */
            .ka-cc-v3 .ka-color-wrap {
                display: flex;
                align-items: center;
                gap: 12px;
            }
            .ka-cc-v3 .ka-color-preview {
                width: 40px;
                height: 40px;
                border-radius: 10px;
                border: 2px solid rgba(0, 0, 0, 0.1);
                cursor: pointer;
                transition: all 0.2s;
            }
            body.ka-v3-dark .ka-cc-v3 .ka-color-preview {
                border-color: rgba(255, 255, 255, 0.1);
            }
            .ka-cc-v3 .ka-color-preview:hover {
                transform: scale(1.05);
            }
            .ka-cc-v3 .ka-color-input {
                max-width: 120px !important;
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
                background: rgba(168, 85, 247, 0.1);
                color: #a855f7;
            }
            .ka-status-badge.enabled .ka-status-badge-dot {
                background: #a855f7;
            }
            .ka-status-badge.disabled {
                background: rgba(239, 68, 68, 0.1);
                color: #ef4444;
            }
            .ka-status-badge.disabled .ka-status-badge-dot {
                background: #ef4444;
            }

            /* Toggle override for purple */
            .ka-cc-v3 .ka-toggle input:checked + .ka-toggle-slider {
                background: #a855f7 !important;
            }

            /* Save button purple */
            .ka-cc-v3 .ka-card-footer {
                padding: 20px 28px;
                background: rgba(168, 85, 247, 0.04);
                border: 1px solid rgba(168, 85, 247, 0.1);
            }
            body.ka-v3-dark .ka-cc-v3 .ka-card-footer {
                background: rgba(168, 85, 247, 0.08);
                border-color: rgba(168, 85, 247, 0.2);
            }
            .ka-cc-v3 .ka-save-btn {
                display: inline-flex;
                align-items: center;
                gap: 10px;
                background: #a855f7;
                color: #fff;
                border: none;
                padding: 14px 28px;
                border-radius: 980px;
                font-size: 15px;
                font-weight: 400;
                cursor: pointer;
                transition: all 0.3s;
            }
            .ka-cc-v3 .ka-save-btn:hover {
                background: #9333ea;
                transform: scale(1.02);
            }
            .ka-cc-v3 .ka-save-btn .dashicons {
                font-size: 18px;
                width: 18px;
                height: 18px;
            }

            /* Select dropdown with arrow */
            .ka-cc-v3 select {
                width: 100%;
                padding: 14px 48px 14px 18px !important;
                font-size: 15px;
                font-family: inherit;
                font-weight: 400;
                border: 1.5px solid rgba(0, 0, 0, 0.1);
                border-radius: 12px;
                background-color: #fff !important;
                color: #1d1d1f;
                transition: all 0.2s ease;
                cursor: pointer;
                -webkit-appearance: none !important;
                -moz-appearance: none !important;
                appearance: none !important;
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='%231d1d1f' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E") !important;
                background-repeat: no-repeat !important;
                background-position: right 16px center !important;
                background-size: 18px 18px !important;
            }
            body.ka-v3-dark .ka-cc-v3 select {
                background-color: #2c2c2e !important;
                border-color: rgba(255, 255, 255, 0.15);
                color: #f5f5f7;
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='%23f5f5f7' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E") !important;
            }
            .ka-cc-v3 select:hover {
                border-color: rgba(168, 85, 247, 0.5);
            }
            .ka-cc-v3 select:focus {
                outline: none;
                border-color: #a855f7;
                box-shadow: 0 0 0 3px rgba(168, 85, 247, 0.15);
            }
            .ka-cc-v3 select option {
                background: #fff;
                color: #1d1d1f;
                padding: 10px;
            }
            body.ka-v3-dark .ka-cc-v3 select option {
                background: #2c2c2e;
                color: #f5f5f7;
            }

            /* Opacity range slider */
            .ka-cc-v3 input[type="range"] {
                -webkit-appearance: none;
                height: 6px;
                background: rgba(0, 0, 0, 0.1);
                border-radius: 3px;
                outline: none;
            }
            body.ka-v3-dark .ka-cc-v3 input[type="range"] {
                background: rgba(255, 255, 255, 0.1);
            }
            .ka-cc-v3 input[type="range"]::-webkit-slider-thumb {
                -webkit-appearance: none;
                width: 18px;
                height: 18px;
                background: #a855f7;
                border-radius: 50%;
                cursor: pointer;
            }

            /* Preview sidebar */
            .ka-cc-v3 .ka-cc-sidebar {
                width: 340px;
                flex-shrink: 0;
            }
            .ka-cc-v3 .ka-preview-card {
                background: #fff;
                border-radius: 20px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
                position: sticky;
                top: 50px;
                overflow: hidden;
            }
            body.ka-v3-dark .ka-cc-v3 .ka-preview-card {
                background: #1c1c1e;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            }
            .ka-cc-v3 .ka-preview-header {
                padding: 18px 22px;
                border-bottom: 1px solid rgba(0, 0, 0, 0.04);
                font-size: 15px;
                font-weight: 600;
                color: #1d1d1f;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            body.ka-v3-dark .ka-cc-v3 .ka-preview-header {
                color: #f5f5f7;
                border-bottom-color: rgba(255, 255, 255, 0.06);
            }
            .ka-cc-v3 .ka-preview-header .dashicons {
                color: #a855f7;
            }
            .ka-cc-v3 .ka-preview-area {
                height: 280px;
                background: linear-gradient(135deg, #f5f5f7 0%, #e8e8ed 100%);
                position: relative;
                overflow: hidden;
                cursor: default;
            }
            body.ka-v3-dark .ka-cc-v3 .ka-preview-area {
                background: linear-gradient(135deg, #48484a 0%, #3a3a3c 100%);
            }
            .ka-cc-v3 .ka-preview-area.ka-cc-hide-original {
                cursor: none;
            }
            .ka-cc-v3 .ka-preview-area::before {
                content: '';
                position: absolute;
                inset: 0;
                background-image: radial-gradient(rgba(0,0,0,0.1) 1px, transparent 1px);
                background-size: 20px 20px;
                opacity: 0.5;
            }
            body.ka-v3-dark .ka-cc-v3 .ka-preview-area::before {
                background-image: radial-gradient(rgba(255,255,255,0.1) 1px, transparent 1px);
            }
            .ka-cc-v3 .ka-preview-cursor {
                position: absolute;
                top: 0;
                left: 0;
                z-index: 1;
                transition: all 0.15s ease;
                pointer-events: none;
            }
            .ka-cc-v3 .ka-preview-cursor-inner {
                border-radius: 50%;
                transition: all 0.15s ease;
            }
            .ka-cc-v3 .ka-preview-cursor-outer {
                position: absolute;
                inset: -4px;
                border-radius: 50%;
                transition: all 0.15s ease;
            }
            .ka-cc-v3 .ka-preview-cursor-outer2 {
                position: absolute;
                border-radius: 50%;
                transition: all 0.15s ease;
                display: none;
            }
            .ka-cc-v3 .ka-preview-crosshair {
                position: absolute;
                pointer-events: none;
                display: none;
            }
            .ka-cc-v3 .ka-preview-crosshair-h,
            .ka-cc-v3 .ka-preview-crosshair-v {
                position: absolute;
                background: currentColor;
            }
            .ka-cc-v3 .ka-preview-crosshair-h {
                width: 100%;
                height: 2px;
                top: 50%;
                left: 0;
                transform: translateY(-50%);
            }
            .ka-cc-v3 .ka-preview-crosshair-v {
                width: 2px;
                height: 100%;
                left: 50%;
                top: 0;
                transform: translateX(-50%);
            }
            .ka-cc-v3 .ka-preview-arrow {
                display: none;
                width: 0;
                height: 0;
                border-style: solid;
            }
            .ka-cc-v3 .ka-preview-info {
                padding: 18px 22px;
                border-top: 1px solid rgba(0, 0, 0, 0.04);
            }
            body.ka-v3-dark .ka-cc-v3 .ka-preview-info {
                border-top-color: rgba(255, 255, 255, 0.06);
            }
            .ka-cc-v3 .ka-preview-info-row {
                display: flex;
                justify-content: space-between;
                font-size: 13px;
                padding: 5px 0;
            }
            .ka-cc-v3 .ka-preview-info-label {
                color: #86868b;
            }
            .ka-cc-v3 .ka-preview-info-value {
                color: #1d1d1f;
                font-weight: 500;
            }
            body.ka-v3-dark .ka-cc-v3 .ka-preview-info-value {
                color: #f5f5f7;
            }

            /* Ripple animation */
            @keyframes ka-cc-ripple {
                0% { transform: scale(0); opacity: 0.5; }
                100% { transform: scale(2.5); opacity: 0; }
            }

            /* Responsive */
            @media (max-width: 1200px) {
                .ka-cc-v3 { flex-direction: column-reverse; }
                .ka-cc-v3 .ka-cc-sidebar { 
                    width: 100%; 
                    order: -1;
                }
                .ka-cc-v3 .ka-preview-card { position: static; }
            }
        </style>

        <div class="ka-admin-wrap ka-cc-v3">
            <div class="ka-cc-main">
                <!-- Header -->
                <div class="ka-admin-header">
                    <div class="ka-admin-header-left">
                        <div class="ka-admin-header-icon purple">
                            <span class="dashicons dashicons-admin-customizer"></span>
                        </div>
                        <div>
                            <h1 class="ka-admin-title"><?php esc_html_e('Custom Cursor', 'king-addons'); ?></h1>
                            <p class="ka-admin-subtitle"><?php esc_html_e('Create stunning custom cursors', 'king-addons'); ?></p>
                        </div>
                    </div>
                    <div class="ka-admin-header-actions">
                        <span class="ka-status-badge <?php echo !empty($settings['enabled']) ? 'enabled' : 'disabled'; ?>">
                            <span class="ka-status-badge-dot"></span>
                            <?php echo !empty($settings['enabled']) ? esc_html__('Enabled', 'king-addons') : esc_html__('Disabled', 'king-addons'); ?>
                        </span>
                        <div class="ka-v3-segmented" id="ka-v3-theme-segment" role="radiogroup" aria-label="Theme" data-active="<?php echo esc_attr($theme_mode); ?>">
                            <span class="ka-v3-segmented-indicator" aria-hidden="true"></span>
                            <button type="button" class="ka-v3-segmented-btn" data-theme="light" aria-pressed="<?php echo $theme_mode === 'light' ? 'true' : 'false'; ?>"><span class="ka-v3-segmented-icon" aria-hidden="true">☀︎</span><?php esc_html_e('Light', 'king-addons'); ?></button>
                            <button type="button" class="ka-v3-segmented-btn" data-theme="dark" aria-pressed="<?php echo $theme_mode === 'dark' ? 'true' : 'false'; ?>"><span class="ka-v3-segmented-icon" aria-hidden="true">☾</span><?php esc_html_e('Dark', 'king-addons'); ?></button>
                            <button type="button" class="ka-v3-segmented-btn" data-theme="auto" aria-pressed="<?php echo $theme_mode === 'auto' ? 'true' : 'false'; ?>"><span class="ka-v3-segmented-icon" aria-hidden="true">◐</span><?php esc_html_e('Auto', 'king-addons'); ?></button>
                        </div>
                    </div>
                </div>

                <form action="<?php echo esc_url(admin_url('options.php')); ?>" method="post" id="ka-cc-form">
                    <?php settings_fields('king_addons_custom_cursor'); ?>

                    <!-- General Settings -->
                    <div class="ka-card">
                        <div class="ka-card-header">
                            <span class="dashicons dashicons-admin-generic"></span>
                            <h2><?php esc_html_e('General Settings', 'king-addons'); ?></h2>
                        </div>
                        <div class="ka-card-body">
                            <div class="ka-row">
                                <div class="ka-row-label"><?php esc_html_e('Enable Cursor', 'king-addons'); ?></div>
                                <div class="ka-row-field">
                                    <label class="ka-toggle">
                                        <input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[enabled]" value="1" <?php checked(!empty($settings['enabled'])); ?> id="ka-cc-enabled" />
                                        <span class="ka-toggle-slider"></span>
                                        <span class="ka-toggle-label"><?php esc_html_e('Show custom cursor on your website', 'king-addons'); ?></span>
                                    </label>
                                </div>
                            </div>
                            <div class="ka-row">
                                <div class="ka-row-label"><?php esc_html_e('Hide on Mobile', 'king-addons'); ?></div>
                                <div class="ka-row-field">
                                    <label class="ka-toggle">
                                        <input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[disable_on_mobile]" value="1" <?php checked(!empty($settings['disable_on_mobile'])); ?> />
                                        <span class="ka-toggle-slider"></span>
                                        <span class="ka-toggle-label"><?php esc_html_e('Disable on touch devices', 'king-addons'); ?></span>
                                    </label>
                                </div>
                            </div>
                            <div class="ka-row">
                                <div class="ka-row-label"><?php esc_html_e('Hide Original Cursor', 'king-addons'); ?></div>
                                <div class="ka-row-field">
                                    <label class="ka-toggle">
                                        <input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[hide_original_cursor]" value="1" <?php checked(!empty($settings['hide_original_cursor'])); ?> />
                                        <span class="ka-toggle-slider"></span>
                                        <span class="ka-toggle-label"><?php esc_html_e('Hide default cursor (custom cursor follows it otherwise)', 'king-addons'); ?></span>
                                    </label>
                                </div>
                            </div>
                            <div class="ka-row">
                                <div class="ka-row-label"><?php echo esc_html__('Rendering Mode', 'king-addons'); ?></div>
                                <div class="ka-row-field">
                                    <select name="<?php echo esc_attr(self::OPTION_KEY); ?>[rendering_mode]" id="ka-cc-mode">
                                        <option value="css" <?php selected($settings['rendering_mode'], 'css'); ?>><?php echo esc_html__('CSS Only (Faster)', 'king-addons'); ?></option>
                                        <option value="enhanced" <?php selected($settings['rendering_mode'], 'enhanced'); ?>><?php echo esc_html__('Enhanced JS (Smoother)', 'king-addons'); ?></option>
                            </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Appearance -->
                    <div class="ka-card">
                        <div class="ka-card-header">
                            <span class="dashicons dashicons-art"></span>
                            <h2><?php echo esc_html__('Appearance', 'king-addons'); ?></h2>
                        </div>
                        <div class="ka-card-body">
                            <div class="ka-row">
                                <div class="ka-row-label"><?php echo esc_html__('Cursor Type', 'king-addons'); ?></div>
                                <div class="ka-row-field">
                                    <select name="<?php echo esc_attr(self::OPTION_KEY); ?>[preset][type]" id="ka-cc-type">
                                        <optgroup label="<?php echo esc_attr__('Basic', 'king-addons'); ?>">
                                <option value="dot" <?php selected($settings['preset']['type'], 'dot'); ?>><?php echo esc_html__('Dot', 'king-addons'); ?></option>
                                <option value="ring" <?php selected($settings['preset']['type'], 'ring'); ?>><?php echo esc_html__('Ring', 'king-addons'); ?></option>
                                <option value="dot-ring" <?php selected($settings['preset']['type'], 'dot-ring'); ?>><?php echo esc_html__('Dot + Ring', 'king-addons'); ?></option>
                                        </optgroup>
                                        <optgroup label="<?php echo esc_attr__('Advanced', 'king-addons'); ?>">
                                            <option value="outline" <?php selected($settings['preset']['type'], 'outline'); ?>><?php echo esc_html__('Outline', 'king-addons'); ?></option>
                                            <option value="crosshair" <?php selected($settings['preset']['type'], 'crosshair'); ?>><?php echo esc_html__('Crosshair', 'king-addons'); ?></option>
                                            <option value="arrow" <?php selected($settings['preset']['type'], 'arrow'); ?>><?php echo esc_html__('Arrow', 'king-addons'); ?></option>
                                        </optgroup>
                                        <optgroup label="<?php echo esc_attr__('Effects', 'king-addons'); ?> <?php echo $is_pro ? '' : '(Pro)'; ?>">
                                            <option value="glow" <?php selected($settings['preset']['type'], 'glow'); ?> <?php disabled(!$is_pro); ?>><?php echo esc_html__('Glow', 'king-addons'); ?></option>
                                            <option value="soft-glow" <?php selected($settings['preset']['type'], 'soft-glow'); ?> <?php disabled(!$is_pro); ?>><?php echo esc_html__('Soft Glow', 'king-addons'); ?></option>
                                            <option value="neon" <?php selected($settings['preset']['type'], 'neon'); ?> <?php disabled(!$is_pro); ?>><?php echo esc_html__('Neon', 'king-addons'); ?></option>
                                            <option value="blend" <?php selected($settings['preset']['type'], 'blend'); ?> <?php disabled(!$is_pro); ?>><?php echo esc_html__('Blend (Invert)', 'king-addons'); ?></option>
                                            <option value="blob" <?php selected($settings['preset']['type'], 'blob'); ?> <?php disabled(!$is_pro); ?>><?php echo esc_html__('Blob', 'king-addons'); ?></option>
                                            <option value="dual" <?php selected($settings['preset']['type'], 'dual'); ?> <?php disabled(!$is_pro); ?>><?php echo esc_html__('Dual Circle', 'king-addons'); ?></option>
                                            <option value="gradient" <?php selected($settings['preset']['type'], 'gradient'); ?> <?php disabled(!$is_pro); ?>><?php echo esc_html__('Gradient', 'king-addons'); ?></option>
                                        </optgroup>
                                        <optgroup label="<?php echo esc_attr__('Custom', 'king-addons'); ?> <?php echo $is_pro ? '' : '(Pro)'; ?>">
                                            <option value="image" <?php selected($settings['preset']['type'], 'image'); ?> <?php disabled(!$is_pro); ?>><?php echo esc_html__('Image', 'king-addons'); ?></option>
                                        </optgroup>
                            </select>
                                </div>
                            </div>
                            <div class="ka-row">
                                <div class="ka-row-label"><?php echo esc_html__('Size', 'king-addons'); ?></div>
                                <div class="ka-row-field">
                                    <input type="number" name="<?php echo esc_attr(self::OPTION_KEY); ?>[preset][size]" value="<?php echo esc_attr((int) $settings['preset']['size']); ?>" min="4" max="200" id="ka-cc-size" /> <span style="color:#64748b">px</span>
                                </div>
                            </div>
                            <div class="ka-row">
                                <div class="ka-row-label"><?php echo esc_html__('Fill Color', 'king-addons'); ?></div>
                                <div class="ka-row-field">
                                    <div class="ka-color-wrap">
                                        <input type="color" class="ka-color-preview" value="<?php echo esc_attr($settings['preset']['fill_color']); ?>" id="ka-cc-fill-picker" />
                                        <input type="text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[preset][fill_color]" value="<?php echo esc_attr($settings['preset']['fill_color']); ?>" class="ka-color-input" id="ka-cc-fill" />
                                    </div>
                                </div>
                            </div>
                            <div class="ka-row">
                                <div class="ka-row-label"><?php echo esc_html__('Border Width', 'king-addons'); ?></div>
                                <div class="ka-row-field">
                                    <input type="number" name="<?php echo esc_attr(self::OPTION_KEY); ?>[preset][border_width]" value="<?php echo esc_attr((float) $settings['preset']['border_width']); ?>" min="0" max="20" step="0.5" id="ka-cc-border-width" /> <span style="color:#64748b">px</span>
                                </div>
                            </div>
                            <div class="ka-row">
                                <div class="ka-row-label"><?php echo esc_html__('Border Color', 'king-addons'); ?></div>
                                <div class="ka-row-field">
                                    <div class="ka-color-wrap">
                                        <input type="color" class="ka-color-preview" value="<?php echo esc_attr($settings['preset']['border_color']); ?>" id="ka-cc-border-picker" />
                                        <input type="text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[preset][border_color]" value="<?php echo esc_attr($settings['preset']['border_color']); ?>" class="ka-color-input" id="ka-cc-border" />
                                    </div>
                                </div>
                            </div>
                            <div class="ka-row">
                                <div class="ka-row-label"><?php echo esc_html__('Opacity', 'king-addons'); ?></div>
                                <div class="ka-row-field">
                                    <input type="range" min="0" max="1" step="0.05" value="<?php echo esc_attr((float) $settings['preset']['opacity']); ?>" id="ka-cc-opacity-range" style="width:150px;vertical-align:middle" />
                                    <input type="number" name="<?php echo esc_attr(self::OPTION_KEY); ?>[preset][opacity]" value="<?php echo esc_attr((float) $settings['preset']['opacity']); ?>" min="0" max="1" step="0.05" id="ka-cc-opacity" style="width:60px;margin-left:10px" />
                                </div>
                            </div>
                            <div class="ka-row">
                                <div class="ka-row-label"><?php echo esc_html__('Blur', 'king-addons'); ?><?php if (!$is_pro): ?><span class="ka-pro-badge">PRO</span><?php endif; ?></div>
                                <div class="ka-row-field">
                                    <input type="number" name="<?php echo esc_attr(self::OPTION_KEY); ?>[preset][blur]" value="<?php echo esc_attr((float) $settings['preset']['blur']); ?>" min="0" max="40" <?php disabled(!$is_pro); ?> id="ka-cc-blur" /> <span style="color:#64748b">px</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Image Cursor Settings (shown when type=image) -->
                    <div class="ka-card" id="ka-cc-image-card" style="display:none">
                        <div class="ka-card-header">
                            <span class="dashicons dashicons-format-image"></span>
                            <h2><?php echo esc_html__('Image Cursor', 'king-addons'); ?></h2>
                        </div>
                        <div class="ka-card-body">
                            <div class="ka-row">
                                <div class="ka-row-label"><?php echo esc_html__('Cursor Image', 'king-addons'); ?><?php if (!$is_pro): ?><span class="ka-pro-badge">PRO</span><?php endif; ?></div>
                                <div class="ka-row-field">
                                    <div class="ka-image-upload-wrap" style="display:flex;align-items:center;gap:12px">
                                        <div class="ka-image-preview" id="ka-cc-image-preview" style="width:64px;height:64px;border:2px dashed #d1d5db;border-radius:8px;display:flex;align-items:center;justify-content:center;background:#f9fafb;overflow:hidden">
                                            <?php if (!empty($settings['image']['url'])): ?>
                                                <img src="<?php echo esc_url($settings['image']['url']); ?>" style="max-width:100%;max-height:100%;object-fit:contain" />
                                            <?php else: ?>
                                                <span class="dashicons dashicons-plus-alt2" style="color:#9ca3af;font-size:24px"></span>
                                            <?php endif; ?>
                                        </div>
                                        <div style="display:flex;flex-direction:column;gap:6px">
                                            <button type="button" class="button" id="ka-cc-image-upload-btn" <?php disabled(!$is_pro); ?>><?php echo esc_html__('Select Image', 'king-addons'); ?></button>
                                            <button type="button" class="button" id="ka-cc-image-remove-btn" style="color:#ef4444" <?php disabled(!$is_pro || empty($settings['image']['url'])); ?>><?php echo esc_html__('Remove', 'king-addons'); ?></button>
                                        </div>
                                        <input type="hidden" name="<?php echo esc_attr(self::OPTION_KEY); ?>[image][url]" id="ka-cc-image-url" value="<?php echo esc_attr($settings['image']['url']); ?>" />
                                    </div>
                                </div>
                            </div>
                            <div class="ka-row">
                                <div class="ka-row-label"><?php echo esc_html__('Image Size', 'king-addons'); ?></div>
                                <div class="ka-row-field">
                                    <input type="number" name="<?php echo esc_attr(self::OPTION_KEY); ?>[image][size]" value="<?php echo esc_attr((int) $settings['image']['size']); ?>" min="8" max="256" id="ka-cc-image-size" <?php disabled(!$is_pro); ?> /> <span style="color:#64748b">px</span>
                                </div>
                            </div>
                            <div class="ka-row">
                                <div class="ka-row-label"><?php echo esc_html__('Hotspot X', 'king-addons'); ?></div>
                                <div class="ka-row-field">
                                    <input type="number" name="<?php echo esc_attr(self::OPTION_KEY); ?>[image][hotspot_x]" value="<?php echo esc_attr((int) $settings['image']['hotspot_x']); ?>" min="0" max="256" id="ka-cc-image-hotspot-x" <?php disabled(!$is_pro); ?> /> <span style="color:#64748b">px</span>
                                </div>
                            </div>
                            <div class="ka-row">
                                <div class="ka-row-label"><?php echo esc_html__('Hotspot Y', 'king-addons'); ?></div>
                                <div class="ka-row-field">
                                    <input type="number" name="<?php echo esc_attr(self::OPTION_KEY); ?>[image][hotspot_y]" value="<?php echo esc_attr((int) $settings['image']['hotspot_y']); ?>" min="0" max="256" id="ka-cc-image-hotspot-y" <?php disabled(!$is_pro); ?> /> <span style="color:#64748b">px</span>
                                </div>
                            </div>
                            <div class="ka-row">
                                <div class="ka-row-label"><?php echo esc_html__('Retina (2x)', 'king-addons'); ?></div>
                                <div class="ka-row-field">
                                    <label class="ka-toggle">
                                        <input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[image][retina]" value="1" <?php checked(!empty($settings['image']['retina'])); ?> <?php disabled(!$is_pro); ?> />
                                        <span class="ka-toggle-slider"></span>
                                        <span class="ka-toggle-label"><?php echo esc_html__('Image is @2x for Retina displays', 'king-addons'); ?></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- States -->
                    <div class="ka-card">
                        <div class="ka-card-header">
                            <span class="dashicons dashicons-superhero"></span>
                            <h2><?php echo esc_html__('Cursor States', 'king-addons'); ?></h2>
                        </div>
                        <div class="ka-card-body">
                            <div class="ka-row">
                                <div class="ka-row-label"><?php echo esc_html__('Normal Scale', 'king-addons'); ?></div>
                                <div class="ka-row-field">
                                    <input type="number" name="<?php echo esc_attr(self::OPTION_KEY); ?>[states][normal][scale]" value="<?php echo esc_attr((float) $settings['states']['normal']['scale']); ?>" min="0.1" max="5" step="0.05" id="ka-cc-scale" />
                                </div>
                            </div>
                            <div class="ka-row">
                                <div class="ka-row-label"><?php echo esc_html__('Normal Opacity', 'king-addons'); ?></div>
                                <div class="ka-row-field">
                                    <input type="number" name="<?php echo esc_attr(self::OPTION_KEY); ?>[states][normal][opacity]" value="<?php echo esc_attr((float) $settings['states']['normal']['opacity']); ?>" min="0" max="1" step="0.05" />
                                </div>
                            </div>
                            <div class="ka-row">
                                <div class="ka-row-label"><?php echo esc_html__('Hover Scale', 'king-addons'); ?></div>
                                <div class="ka-row-field">
                                    <input type="number" name="<?php echo esc_attr(self::OPTION_KEY); ?>[states][hover_link][scale]" value="<?php echo esc_attr((float) $settings['states']['hover_link']['scale']); ?>" min="0.5" max="5" step="0.05" id="ka-cc-hover-scale" />
                                </div>
                            </div>
                            <div class="ka-row">
                                <div class="ka-row-label"><?php echo esc_html__('Hover Fill Color', 'king-addons'); ?></div>
                                <div class="ka-row-field">
                                    <div class="ka-color-wrap">
                                        <input type="color" class="ka-color-preview" value="<?php echo esc_attr($settings['states']['hover_link']['color']); ?>" id="ka-cc-hover-fill-picker" />
                                        <input type="text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[states][hover_link][color]" value="<?php echo esc_attr($settings['states']['hover_link']['color']); ?>" class="ka-color-input" id="ka-cc-hover-fill" />
                                    </div>
                                </div>
                            </div>
                            <div class="ka-row">
                                <div class="ka-row-label"><?php echo esc_html__('Hover Border Color', 'king-addons'); ?></div>
                                <div class="ka-row-field">
                                    <div class="ka-color-wrap">
                                        <input type="color" class="ka-color-preview" value="<?php echo esc_attr($settings['states']['hover_link']['border_color']); ?>" id="ka-cc-hover-border-picker" />
                                        <input type="text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[states][hover_link][border_color]" value="<?php echo esc_attr($settings['states']['hover_link']['border_color']); ?>" class="ka-color-input" id="ka-cc-hover-border" />
                                    </div>
                                </div>
                            </div>
                            <div class="ka-row">
                                <div class="ka-row-label"><?php echo esc_html__('Click Scale', 'king-addons'); ?></div>
                                <div class="ka-row-field">
                                    <input type="number" name="<?php echo esc_attr(self::OPTION_KEY); ?>[states][click][scale]" value="<?php echo esc_attr((float) $settings['states']['click']['scale']); ?>" min="0.5" max="2" step="0.05" />
                                </div>
                            </div>
                            <div class="ka-row">
                                <div class="ka-row-label"><?php echo esc_html__('Hover Label', 'king-addons'); ?><?php if (!$is_pro): ?><span class="ka-pro-badge">PRO</span><?php endif; ?></div>
                                <div class="ka-row-field">
                            <input type="text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[states][hover_link][label]" value="<?php echo esc_attr($settings['states']['hover_link']['label']); ?>" placeholder="<?php echo esc_attr__('View', 'king-addons'); ?>" <?php disabled(!$is_pro); ?> />
                                </div>
                            </div>
                            <div class="ka-row">
                                <div class="ka-row-label"><?php echo esc_html__('Click Ripple', 'king-addons'); ?><?php if (!$is_pro): ?><span class="ka-pro-badge">PRO</span><?php endif; ?></div>
                                <div class="ka-row-field">
                                    <label class="ka-toggle">
                                <input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[states][click][ripple]" value="1" <?php checked(!empty($settings['states']['click']['ripple'])); ?> <?php disabled(!$is_pro); ?> />
                                        <span class="ka-toggle-slider"></span>
                                        <span class="ka-toggle-label"><?php echo esc_html__('Enable ripple animation on click', 'king-addons'); ?></span>
                            </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Targeting -->
                    <div class="ka-card">
                        <div class="ka-card-header">
                            <span class="dashicons dashicons-visibility"></span>
                <h2><?php echo esc_html__('Targeting', 'king-addons'); ?></h2>
                        </div>
                        <div class="ka-card-body">
                            <div class="ka-row">
                                <div class="ka-row-label"><?php echo esc_html__('Hide for Logged-in', 'king-addons'); ?></div>
                                <div class="ka-row-field">
                                    <label class="ka-toggle">
                                <input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[targeting][exclude_logged_in]" value="1" <?php checked(!empty($settings['targeting']['exclude_logged_in'])); ?> />
                                        <span class="ka-toggle-slider"></span>
                                        <span class="ka-toggle-label"><?php echo esc_html__('Hide cursor for logged-in users', 'king-addons'); ?></span>
                            </label>
                                </div>
                            </div>
                            <div class="ka-row">
                                <div class="ka-row-label"><?php echo esc_html__('Include Pages', 'king-addons'); ?><?php if (!$is_pro): ?><span class="ka-pro-badge">PRO</span><?php endif; ?></div>
                                <div class="ka-row-field">
                                    <input type="text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[targeting][include_pages]" value="<?php echo esc_attr(implode(',', $settings['targeting']['include_pages'])); ?>" placeholder="12, 45, 99" style="max-width:300px" <?php disabled(!$is_pro); ?> />
                                </div>
                            </div>
                            <div class="ka-row">
                                <div class="ka-row-label"><?php echo esc_html__('Exclude Selectors', 'king-addons'); ?><?php if (!$is_pro): ?><span class="ka-pro-badge">PRO</span><?php endif; ?></div>
                                <div class="ka-row-field">
                                    <input type="text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[targeting][exclude_selectors]" value="<?php echo esc_attr($settings['targeting']['exclude_selectors']); ?>" placeholder=".no-cursor, .modal" style="max-width:300px" <?php disabled(!$is_pro); ?> />
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Hidden fields for other settings -->
                    <input type="hidden" name="<?php echo esc_attr(self::OPTION_KEY); ?>[preset][blend_mode]" value="<?php echo esc_attr($settings['preset']['blend_mode']); ?>" />
                    <input type="hidden" name="<?php echo esc_attr(self::OPTION_KEY); ?>[targeting][include_post_types]" value="<?php echo esc_attr(implode(',', $settings['targeting']['include_post_types'])); ?>" />
                    <input type="hidden" name="<?php echo esc_attr(self::OPTION_KEY); ?>[targeting][include_urls]" value="<?php echo esc_attr(implode(',', $settings['targeting']['include_urls'])); ?>" />
                    <input type="hidden" name="<?php echo esc_attr(self::OPTION_KEY); ?>[targeting][exclude_pages]" value="<?php echo esc_attr(implode(',', $settings['targeting']['exclude_pages'])); ?>" />
                    <input type="hidden" name="<?php echo esc_attr(self::OPTION_KEY); ?>[targeting][exclude_post_types]" value="<?php echo esc_attr(implode(',', $settings['targeting']['exclude_post_types'])); ?>" />
                    <input type="hidden" name="<?php echo esc_attr(self::OPTION_KEY); ?>[magnetic][enabled]" value="<?php echo !empty($settings['magnetic']['enabled']) ? '1' : ''; ?>" />
                    <input type="hidden" name="<?php echo esc_attr(self::OPTION_KEY); ?>[magnetic][strength]" value="<?php echo esc_attr($settings['magnetic']['strength']); ?>" />
                    <input type="hidden" name="<?php echo esc_attr(self::OPTION_KEY); ?>[magnetic][radius]" value="<?php echo esc_attr($settings['magnetic']['radius']); ?>" />
                    <input type="hidden" name="<?php echo esc_attr(self::OPTION_KEY); ?>[magnetic][selectors]" value="<?php echo esc_attr($settings['magnetic']['selectors']); ?>" />
                    <input type="hidden" name="<?php echo esc_attr(self::OPTION_KEY); ?>[movement][follow_speed]" value="<?php echo esc_attr($settings['movement']['follow_speed']); ?>" />
                    <input type="hidden" name="<?php echo esc_attr(self::OPTION_KEY); ?>[movement][tail][points]" value="<?php echo esc_attr($settings['movement']['tail']['points']); ?>" />
                    <input type="hidden" name="<?php echo esc_attr(self::OPTION_KEY); ?>[movement][tail][decay]" value="<?php echo esc_attr($settings['movement']['tail']['decay']); ?>" />
                    
                    <div class="ka-card ka-card-footer">
                        <button type="submit" class="ka-save-btn">
                            <span class="dashicons dashicons-saved"></span>
                            <?php esc_html_e('Save Changes', 'king-addons'); ?>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Sidebar with Preview -->
            <div class="ka-cc-sidebar">
                <div class="ka-preview-card">
                    <div class="ka-preview-header">
                        <span class="dashicons dashicons-visibility"></span>
                        <?php esc_html_e('Live Preview', 'king-addons'); ?>
                    </div>
                    <div class="ka-preview-area" id="ka-cc-preview-area">
                        <div class="ka-preview-cursor" id="ka-cc-preview-cursor">
                            <div class="ka-preview-cursor-inner" id="ka-cc-preview-inner"></div>
                            <div class="ka-preview-cursor-outer" id="ka-cc-preview-outer"></div>
                            <div class="ka-preview-cursor-outer2" id="ka-cc-preview-outer2"></div>
                            <div class="ka-preview-crosshair" id="ka-cc-preview-crosshair">
                                <div class="ka-preview-crosshair-h"></div>
                                <div class="ka-preview-crosshair-v"></div>
                            </div>
                            <div class="ka-preview-arrow" id="ka-cc-preview-arrow"></div>
                        </div>
                    </div>
                    <div class="ka-preview-info">
                        <div class="ka-preview-info-row">
                            <span class="ka-preview-info-label"><?php esc_html_e('Type', 'king-addons'); ?></span>
                            <span class="ka-preview-info-value" id="ka-cc-info-type"><?php echo esc_html(ucfirst($settings['preset']['type'])); ?></span>
                        </div>
                        <div class="ka-preview-info-row">
                            <span class="ka-preview-info-label"><?php esc_html_e('Size', 'king-addons'); ?></span>
                            <span class="ka-preview-info-value" id="ka-cc-info-size"><?php echo esc_html($settings['preset']['size']); ?>px</span>
                        </div>
                        <div class="ka-preview-info-row">
                            <span class="ka-preview-info-label"><?php esc_html_e('Colors', 'king-addons'); ?></span>
                            <span class="ka-preview-info-value">
                                <span style="display:inline-block;width:14px;height:14px;border-radius:6px;background:<?php echo esc_attr($settings['preset']['fill_color']); ?>;vertical-align:middle;margin-right:4px;border:1px solid rgba(0,0,0,0.1)" id="ka-cc-info-fill"></span>
                                <span style="display:inline-block;width:14px;height:14px;border-radius:6px;background:<?php echo esc_attr($settings['preset']['border_color']); ?>;vertical-align:middle;border:1px solid rgba(0,0,0,0.1)" id="ka-cc-info-border"></span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
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
        </script>

        <script>
        (function() {
            'use strict';

            const inner = document.getElementById('ka-cc-preview-inner');
            const outer = document.getElementById('ka-cc-preview-outer');
            const outer2 = document.getElementById('ka-cc-preview-outer2');
            const crosshair = document.getElementById('ka-cc-preview-crosshair');
            const arrow = document.getElementById('ka-cc-preview-arrow');
            const previewArea = document.getElementById('ka-cc-preview-area');
            const cursor = document.getElementById('ka-cc-preview-cursor');
            
            // Input elements
            const sizeInput = document.getElementById('ka-cc-size');
            const fillInput = document.getElementById('ka-cc-fill');
            const fillPicker = document.getElementById('ka-cc-fill-picker');
            const borderInput = document.getElementById('ka-cc-border');
            const borderPicker = document.getElementById('ka-cc-border-picker');
            const borderWidthInput = document.getElementById('ka-cc-border-width');
            const opacityInput = document.getElementById('ka-cc-opacity');
            const opacityRange = document.getElementById('ka-cc-opacity-range');
            const typeInput = document.getElementById('ka-cc-type');
            const scaleInput = document.getElementById('ka-cc-scale');
            const blurInput = document.getElementById('ka-cc-blur');
            
            // Info elements
            const infoType = document.getElementById('ka-cc-info-type');
            const infoSize = document.getElementById('ka-cc-info-size');
            const infoFill = document.getElementById('ka-cc-info-fill');
            const infoBorder = document.getElementById('ka-cc-info-border');
            
            // Type name mapping for display
            const typeNames = {
                'dot': 'Dot',
                'ring': 'Ring',
                'dot-ring': 'Dot + Ring',
                'outline': 'Outline',
                'crosshair': 'Crosshair',
                'arrow': 'Arrow',
                'glow': 'Glow',
                'soft-glow': 'Soft Glow',
                'neon': 'Neon',
                'blend': 'Blend (Invert)',
                'blob': 'Blob',
                'dual': 'Dual Circle',
                'gradient': 'Gradient',
                'image': 'Image'
            };

            // Used to keep the cursor centered when preview is not hovered
            let previewVisualSize = 14;
            
            function updatePreview() {
                const size = parseInt(sizeInput.value) || 14;
                const fill = fillInput.value || '#111111';
                const border = borderInput.value || '#111111';
                const borderWidth = parseFloat(borderWidthInput.value) || 2;
                const opacity = parseFloat(opacityInput.value) || 1;
                const type = typeInput.value || 'dot';
                const scale = parseFloat(scaleInput.value) || 1;
                const blur = parseFloat(blurInput.value) || 0;
                
                // Reset all elements
                inner.style.display = 'none';
                outer.style.display = 'none';
                outer2.style.display = 'none';
                crosshair.style.display = 'none';
                arrow.style.display = 'none';
                inner.style.boxShadow = 'none';
                inner.style.background = fill;
                inner.style.filter = 'none';
                inner.style.borderRadius = '50%';
                outer.style.background = 'none';
                // Don't override CSS background with inline style - let CSS handle dark mode
                previewArea.style.background = '';
                cursor.style.mixBlendMode = 'normal';
                
                // Base inner size
                inner.style.width = size + 'px';
                inner.style.height = size + 'px';
                inner.style.opacity = opacity;
                inner.style.transform = 'scale(' + scale + ')';
                
                // Base outer ring
                const outerSize = size + (borderWidth * 2) + 8;
                previewVisualSize = Math.max(size, outerSize);
                outer.style.width = outerSize + 'px';
                outer.style.height = outerSize + 'px';
                outer.style.border = borderWidth + 'px solid ' + border;
                outer.style.marginTop = '-' + (outerSize / 2) + 'px';
                outer.style.marginLeft = '-' + (outerSize / 2) + 'px';
                outer.style.top = '50%';
                outer.style.left = '50%';
                outer.style.opacity = '1';
                outer.style.transform = 'none';
                
                // Type-specific rendering
                switch (type) {
                    case 'dot':
                        inner.style.display = 'block';
                        break;
                        
                    case 'ring':
                        outer.style.display = 'block';
                        outer.style.background = 'transparent';
                        break;
                        
                    case 'dot-ring':
                        inner.style.display = 'block';
                        outer.style.display = 'block';
                        break;
                        
                    case 'outline':
                        inner.style.display = 'block';
                        inner.style.background = 'transparent';
                        inner.style.border = borderWidth + 'px solid ' + fill;
                        inner.style.width = (size - borderWidth * 2) + 'px';
                        inner.style.height = (size - borderWidth * 2) + 'px';
                        break;
                        
                    case 'crosshair':
                        crosshair.style.display = 'block';
                        crosshair.style.width = size + 'px';
                        crosshair.style.height = size + 'px';
                        crosshair.style.color = fill;
                        crosshair.style.opacity = opacity;
                        // Small center dot
                        inner.style.display = 'block';
                        inner.style.width = '4px';
                        inner.style.height = '4px';
                        break;
                        
                    case 'arrow':
                        arrow.style.display = 'block';
                        const arrowSize = size * 0.6;
                        arrow.style.borderWidth = arrowSize + 'px ' + (arrowSize * 0.6) + 'px 0 ' + (arrowSize * 0.6) + 'px';
                        arrow.style.borderColor = fill + ' transparent transparent transparent';
                        arrow.style.opacity = opacity;
                        arrow.style.transform = 'rotate(-45deg)';
                        break;
                        
                    case 'glow':
                        inner.style.display = 'block';
                        inner.style.boxShadow = '0 0 ' + (size * 0.8) + 'px ' + (size * 0.3) + 'px ' + fill;
                        break;
                        
                    case 'soft-glow':
                        inner.style.display = 'block';
                        inner.style.filter = 'blur(' + (size * 0.15) + 'px)';
                        inner.style.boxShadow = '0 0 ' + (size * 1.2) + 'px ' + (size * 0.5) + 'px ' + fill;
                        break;
                        
                    case 'neon':
                        inner.style.display = 'block';
                        inner.style.background = '#fff';
                        inner.style.boxShadow = 
                            '0 0 ' + (size * 0.3) + 'px ' + fill + ', ' +
                            '0 0 ' + (size * 0.6) + 'px ' + fill + ', ' +
                            '0 0 ' + (size * 1) + 'px ' + fill + ', ' +
                            '0 0 ' + (size * 1.5) + 'px ' + fill;
                        break;
                        
                    case 'blend':
                        inner.style.display = 'block';
                        inner.style.background = '#ffffff';
                        inner.style.width = (size * 1.5) + 'px';
                        inner.style.height = (size * 1.5) + 'px';
                        cursor.style.mixBlendMode = 'difference';
                        previewArea.style.background = 'linear-gradient(135deg, #1e293b 0%, #334155 50%, #f1f5f9 50%, #e2e8f0 100%)';
                        break;
                        
                    case 'blob':
                        inner.style.display = 'block';
                        inner.style.borderRadius = '60% 40% 30% 70% / 60% 30% 70% 40%';
                        inner.style.width = (size * 1.3) + 'px';
                        inner.style.height = (size * 1.3) + 'px';
                        previewVisualSize = Math.max(previewVisualSize, (size * 1.3));
                        break;
                        
                    case 'dual':
                        inner.style.display = 'block';
                        outer.style.display = 'block';
                        outer2.style.display = 'block';
                        // Outer ring 1
                        outer.style.opacity = '0.6';
                        outer.style.transform = 'scale(1.3)';
                        // Outer ring 2
                        const outer2Size = outerSize * 1.6;
                        outer2.style.width = outer2Size + 'px';
                        outer2.style.height = outer2Size + 'px';
                        outer2.style.border = (borderWidth * 0.5) + 'px solid ' + border;
                        outer2.style.marginTop = '-' + (outer2Size / 2) + 'px';
                        outer2.style.marginLeft = '-' + (outer2Size / 2) + 'px';
                        outer2.style.top = '50%';
                        outer2.style.left = '50%';
                        outer2.style.opacity = '0.3';
                        previewVisualSize = Math.max(previewVisualSize, outer2Size);
                        break;
                        
                    case 'gradient':
                        inner.style.display = 'block';
                        inner.style.background = 'linear-gradient(135deg, ' + fill + ' 0%, ' + border + ' 100%)';
                        inner.style.width = (size * 1.2) + 'px';
                        inner.style.height = (size * 1.2) + 'px';
                        previewVisualSize = Math.max(previewVisualSize, (size * 1.2));
                        break;
                        
                    case 'image':
                        inner.style.display = 'block';
                        inner.style.borderRadius = '4px';
                        inner.style.background = 'transparent';
                        const imgUrl = document.getElementById('ka-cc-image-url');
                        const imgSize = document.getElementById('ka-cc-image-size');
                        const imgSizeVal = parseInt(imgSize?.value) || size;
                        inner.style.width = imgSizeVal + 'px';
                        inner.style.height = imgSizeVal + 'px';
                        if (imgUrl && imgUrl.value) {
                            inner.style.backgroundImage = 'url(' + imgUrl.value + ')';
                            inner.style.backgroundSize = 'contain';
                            inner.style.backgroundRepeat = 'no-repeat';
                            inner.style.backgroundPosition = 'center';
                        } else {
                            inner.style.background = '#ddd';
                            inner.style.border = '2px dashed #999';
                        }
                        previewVisualSize = Math.max(previewVisualSize, imgSizeVal);
                        break;
                        
                    default:
                        inner.style.display = 'block';
                }
                
                // Apply blur for Pro types if set
                if (blur > 0 && !['soft-glow', 'blend'].includes(type)) {
                    inner.style.filter = 'blur(' + blur + 'px)';
                }
                
                // Update info
                infoType.textContent = typeNames[type] || type;
                infoSize.textContent = size + 'px';
                infoFill.style.background = fill;
                infoBorder.style.background = border;
            }

            function centerPreviewCursor() {
                const width = previewArea ? previewArea.clientWidth : 0;
                const height = previewArea ? previewArea.clientHeight : 0;
                const half = (previewVisualSize || 14) / 2;
                cursorX = (width / 2) - half;
                cursorY = (height / 2) - half;
                targetX = cursorX;
                targetY = cursorY;
                cursor.style.transform = 'translate(' + cursorX + 'px, ' + cursorY + 'px)';
            }
            
            // Color picker sync
            fillPicker.addEventListener('input', function() {
                fillInput.value = this.value;
                updatePreview();
            });
            fillInput.addEventListener('input', function() {
                try { fillPicker.value = this.value; } catch(e) {}
                updatePreview();
            });
            borderPicker.addEventListener('input', function() {
                borderInput.value = this.value;
                updatePreview();
            });
            borderInput.addEventListener('input', function() {
                try { borderPicker.value = this.value; } catch(e) {}
                updatePreview();
            });
            
            // Opacity range sync
            opacityRange.addEventListener('input', function() {
                opacityInput.value = this.value;
                updatePreview();
            });
            opacityInput.addEventListener('input', function() {
                opacityRange.value = this.value;
                updatePreview();
            });
            
            // Hover color pickers
            const hoverFillPicker = document.getElementById('ka-cc-hover-fill-picker');
            const hoverFillInput = document.getElementById('ka-cc-hover-fill');
            const hoverBorderPicker = document.getElementById('ka-cc-hover-border-picker');
            const hoverBorderInput = document.getElementById('ka-cc-hover-border');
            
            if (hoverFillPicker && hoverFillInput) {
                hoverFillPicker.addEventListener('input', function() {
                    hoverFillInput.value = this.value;
                });
                hoverFillInput.addEventListener('input', function() {
                    try { hoverFillPicker.value = this.value; } catch(e) {}
                });
            }
            if (hoverBorderPicker && hoverBorderInput) {
                hoverBorderPicker.addEventListener('input', function() {
                    hoverBorderInput.value = this.value;
                });
                hoverBorderInput.addEventListener('input', function() {
                    try { hoverBorderPicker.value = this.value; } catch(e) {}
                });
            }
            
            // Bind all relevant inputs
            [sizeInput, borderWidthInput, typeInput, scaleInput, blurInput].forEach(function(el) {
                if (el) {
                    el.addEventListener('input', updatePreview);
                    el.addEventListener('change', updatePreview);
                }
            });
            
            // Interactive preview - follow mouse
            let isHovering = false;
            let cursorX = 0, cursorY = 0;
            let targetX = 0, targetY = 0;
            let firstMove = true;
            const hideOriginalInput = document.querySelector('input[name="<?php echo esc_attr(self::OPTION_KEY); ?>[hide_original_cursor]"]');
            
            function updatePreviewCursor() {
                if (!hideOriginalInput) {
                    return;
                }
                previewArea.classList.toggle('ka-cc-hide-original', !!hideOriginalInput.checked);
            }
            
            if (hideOriginalInput) {
                hideOriginalInput.addEventListener('change', updatePreviewCursor);
                updatePreviewCursor();
            }
            
            previewArea.addEventListener('mouseenter', function(e) {
                isHovering = true;
                firstMove = true;
                // Immediately position cursor at mouse entry point
                const rect = previewArea.getBoundingClientRect();
                const size = parseInt(sizeInput.value) || 14;
                targetX = e.clientX - rect.left - (size / 2);
                targetY = e.clientY - rect.top - (size / 2);
                // Set cursor position instantly on enter
                cursorX = targetX;
                cursorY = targetY;
                cursor.style.transform = 'translate(' + cursorX + 'px, ' + cursorY + 'px)';
            });
            previewArea.addEventListener('mouseleave', function() {
                isHovering = false;
                centerPreviewCursor();
                firstMove = true;
            });
            previewArea.addEventListener('mousemove', function(e) {
                if (!isHovering) return;
                const rect = previewArea.getBoundingClientRect();
                const size = parseInt(sizeInput.value) || 14;
                targetX = e.clientX - rect.left - (size / 2);
                targetY = e.clientY - rect.top - (size / 2);
                // On first move after enter, snap to position
                if (firstMove) {
                    cursorX = targetX;
                    cursorY = targetY;
                    firstMove = false;
                }
            });
            
            // Click ripple effect in preview
            previewArea.addEventListener('mousedown', function(e) {
                if (!isHovering) return;
                // Create ripple element
                const ripple = document.createElement('div');
                const rect = previewArea.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                ripple.style.cssText = 'position:absolute;border-radius:50%;background:' + (fillInput.value || '#a855f7') + ';opacity:0.4;pointer-events:none;transform:scale(0);animation:ka-cc-ripple 0.6s ease-out forwards;';
                ripple.style.left = (x - 30) + 'px';
                ripple.style.top = (y - 30) + 'px';
                ripple.style.width = '60px';
                ripple.style.height = '60px';
                previewArea.appendChild(ripple);
                setTimeout(function() { ripple.remove(); }, 600);
                // Scale down cursor briefly
                cursor.style.transition = 'transform 0.1s ease';
                setTimeout(function() { cursor.style.transition = 'none'; }, 100);
            });
            
            // Smooth animation
            function animateCursor() {
                if (isHovering) {
                    cursorX += (targetX - cursorX) * 0.18;
                    cursorY += (targetY - cursorY) * 0.18;
                    cursor.style.transform = 'translate(' + cursorX + 'px, ' + cursorY + 'px)';
                }
                requestAnimationFrame(animateCursor);
            }
            animateCursor();
            
            // Initial update
            updatePreview();
            centerPreviewCursor();

            // ====== Image Cursor Card visibility ======
            const imageCard = document.getElementById('ka-cc-image-card');
            function updateImageCardVisibility() {
                if (imageCard && typeInput) {
                    imageCard.style.display = typeInput.value === 'image' ? '' : 'none';
                }
            }
            if (typeInput) {
                typeInput.addEventListener('change', updateImageCardVisibility);
                updateImageCardVisibility();
            }

            // ====== Image Upload via Media Library ======
            const imageUploadBtn = document.getElementById('ka-cc-image-upload-btn');
            const imageRemoveBtn = document.getElementById('ka-cc-image-remove-btn');
            const imageUrlInput = document.getElementById('ka-cc-image-url');
            const imagePreview = document.getElementById('ka-cc-image-preview');

            if (imageUploadBtn) {
                imageUploadBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    if (typeof wp === 'undefined' || !wp.media) {
                        alert('Media library not loaded. Please refresh the page.');
                        return;
                    }
                    
                    const mediaFrame = wp.media({
                        title: '<?php echo esc_js(__('Select Cursor Image', 'king-addons')); ?>',
                        button: { text: '<?php echo esc_js(__('Use this image', 'king-addons')); ?>' },
                        multiple: false,
                        library: { type: 'image' }
                    });
                    mediaFrame.on('select', function() {
                        const attachment = mediaFrame.state().get('selection').first().toJSON();
                        if (imageUrlInput) imageUrlInput.value = attachment.url;
                        if (imagePreview) imagePreview.innerHTML = '<img src="' + attachment.url + '" style="max-width:100%;max-height:100%;object-fit:contain" />';
                        if (imageRemoveBtn) imageRemoveBtn.disabled = false;
                        updatePreview();
                    });
                    mediaFrame.open();
                });
            }
            if (imageRemoveBtn) {
                imageRemoveBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    imageUrlInput.value = '';
                    imagePreview.innerHTML = '<span class="dashicons dashicons-plus-alt2" style="color:#9ca3af;font-size:24px"></span>';
                    imageRemoveBtn.disabled = true;
                    updatePreview();
                });
            }
        })();
        </script>
        <?php
    }

    /**
     * Enqueue frontend assets if enabled.
     *
     * @return void
     */
    public function maybe_enqueue_assets(): void
    {
        $settings = $this->get_settings();
        if (!$this->should_activate(false, $settings)) {
            return;
        }

        $style_handle = KING_ADDONS_ASSETS_UNIQUE_KEY . '-custom-cursor-style';
        $script_handle = KING_ADDONS_ASSETS_UNIQUE_KEY . '-custom-cursor-script';

        wp_enqueue_style(
            $style_handle,
            KING_ADDONS_URL . 'includes/extensions/Custom_Cursor/assets/style.css',
            [],
            KING_ADDONS_VERSION
        );

        // Add inline CSS for cursor customization
        $inline_css = $this->generate_inline_css($settings);
        if ($inline_css) {
            wp_add_inline_style($style_handle, $inline_css);
        }

        wp_enqueue_script(
            $script_handle,
            KING_ADDONS_URL . 'includes/extensions/Custom_Cursor/assets/script.js',
            [],
            KING_ADDONS_VERSION,
            true
        );

        wp_localize_script(
            $script_handle,
            'KingAddonsCustomCursorData',
            $this->build_frontend_settings($settings, false)
        );
    }

    /**
     * Generate inline CSS for cursor customization.
     *
     * @param array<string,mixed> $settings Settings array.
     *
     * @return string CSS string.
     */
    private function generate_inline_css(array $settings): string
    {
        $preset = $settings['preset'];
        $states = $settings['states'];
        
        $css = ':root {';
        $css .= '--ka-cursor-size: ' . esc_attr($preset['size']) . 'px;';
        $css .= '--ka-cursor-border-width: ' . esc_attr($preset['border_width']) . 'px;';
        $css .= '--ka-cursor-fill: ' . esc_attr($preset['fill_color']) . ';';
        $css .= '--ka-cursor-border-color: ' . esc_attr($preset['border_color']) . ';';
        $css .= '--ka-cursor-opacity: ' . esc_attr($states['normal']['opacity']) . ';';
        $css .= '--ka-cursor-scale: ' . esc_attr($states['normal']['scale']) . ';';
        $css .= '--ka-cursor-blur: ' . esc_attr($preset['blur']) . 'px;';
        $css .= '--ka-cursor-mix-blend: ' . esc_attr($preset['blend_mode'] ?: 'normal') . ';';
        $css .= '}';

        return $css;
    }

    /**
     * Enqueue assets in Elementor preview.
     *
     * @return void
     */
    public function enqueue_preview_assets(): void
    {
        $settings = $this->get_settings();

        $style_handle = KING_ADDONS_ASSETS_UNIQUE_KEY . '-custom-cursor-style';
        $script_handle = KING_ADDONS_ASSETS_UNIQUE_KEY . '-custom-cursor-script';
        $preview_handle = KING_ADDONS_ASSETS_UNIQUE_KEY . '-custom-cursor-preview';

        wp_enqueue_style(
            $style_handle,
            KING_ADDONS_URL . 'includes/extensions/Custom_Cursor/assets/style.css',
            [],
            KING_ADDONS_VERSION
        );

        wp_enqueue_script(
            $script_handle,
            KING_ADDONS_URL . 'includes/extensions/Custom_Cursor/assets/script.js',
            [],
            KING_ADDONS_VERSION,
            true
        );

        wp_enqueue_script(
            $preview_handle,
            KING_ADDONS_URL . 'includes/extensions/Custom_Cursor/assets/preview-handler.js',
            ['elementor-frontend', $script_handle],
            KING_ADDONS_VERSION,
            true
        );

        $localized = $this->build_frontend_settings($settings, true);

        wp_localize_script(
            $script_handle,
            'KingAddonsCustomCursorData',
            $localized
        );

        wp_localize_script(
            $preview_handle,
            'KingAddonsCustomCursorPreview',
            [
                'enabled' => $localized['enabled'],
            ]
        );
    }

    /**
     * Render cursor container.
     *
     * @return void
     */
    public function render_cursor_markup(): void
    {
        $settings = $this->get_settings();
        if (!$this->should_activate(false, $settings)) {
            return;
        }

        ?>
        <div id="ka-custom-cursor" class="ka-custom-cursor" aria-hidden="true">
            <div class="ka-custom-cursor__outer"></div>
            <div class="ka-custom-cursor__inner"></div>
            <div class="ka-custom-cursor__label" data-ka-cursor-label></div>
            <div class="ka-custom-cursor__tail" data-ka-cursor-tail></div>
        </div>
        <?php
    }

    /**
     * Add body class when enabled.
     *
     * @param array<int,string> $classes Body classes.
     *
     * @return array<int,string>
     */
    public function filter_body_classes(array $classes): array
    {
        $settings = $this->get_settings();
        if ($this->should_activate(false, $settings)) {
            $classes[] = 'ka-custom-cursor-enabled';
            if (!empty($settings['hide_original_cursor'])) {
                $classes[] = 'ka-cursor-hide-original';
            }
        }
        return $classes;
    }

    /**
     * Register Elementor controls.
     *
     * @param Element_Base $element Elementor element.
     * @param array<mixed> $args    Args.
     *
     * @return void
     */
    public function register_elementor_controls(Element_Base $element, $args): void
    {
        $element->start_controls_section(
            'king_addons_custom_cursor_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Custom Cursor', 'king-addons'),
                'tab' => Controls_Manager::TAB_ADVANCED,
            ]
        );

        $element->add_control(
            'kng_cursor_interaction',
            [
                'label' => esc_html__('Cursor Interaction', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'default' => esc_html__('Default', 'king-addons'),
                    'hover' => esc_html__('Hover', 'king-addons'),
                    'drag' => esc_html__('Drag', 'king-addons'),
                    'zoom' => esc_html__('Zoom', 'king-addons'),
                    'hide' => esc_html__('Hide Cursor', 'king-addons'),
                ],
                'default' => 'default',
                'frontend_available' => true,
            ]
        );

        $element->add_control(
            'kng_cursor_magnetic',
            [
                'label' => esc_html__('Magnetic Behavior', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'none' => esc_html__('None', 'king-addons'),
                    'light' => esc_html__('Light', 'king-addons'),
                    'strong' => sprintf(__('Strong %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                    'follow' => sprintf(__('Follow Center %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                ],
                'default' => 'none',
                'frontend_available' => true,
            ]
        );

        $element->add_control(
            'kng_cursor_color_override',
            [
                'label' => esc_html__('Cursor Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'frontend_available' => true,
                'condition' => [
                    'kng_cursor_interaction!' => 'hide',
                ],
            ]
        );

        $element->add_control(
            'kng_cursor_size_multiplier',
            [
                'label' => esc_html__('Cursor Size Multiplier', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0.5,
                        'max' => 3,
                        'step' => 0.1,
                    ],
                ],
                'default' => [
                    'size' => 1,
                ],
                'frontend_available' => true,
                'condition' => [
                    'kng_cursor_interaction!' => 'hide',
                ],
            ]
        );

        if (!$this->can_use_pro()) {
            $element->add_control(
                'kng_cursor_pro_notice',
                [
                    'type' => Controls_Manager::RAW_HTML,
                    'raw' => wp_kses_post('<div class="king-addons-pro-notice">' . esc_html__('Upgrade to Pro to unlock magnetic strength variations and advanced overrides.', 'king-addons') . '</div>'),
                ]
            );
        }

        $element->end_controls_section();
    }

    /**
     * Apply Elementor attributes for runtime.
     *
     * @param Element_Base $element Elementor element.
     *
     * @return void
     */
    public function apply_elementor_attributes(Element_Base $element): void
    {
        $settings = $element->get_settings_for_display();

        $interaction = $settings['kng_cursor_interaction'] ?? 'default';
        if ($interaction && 'default' !== $interaction) {
            $element->add_render_attribute('_wrapper', 'data-ka-cursor', esc_attr($interaction));
        }

        $magnetic = $settings['kng_cursor_magnetic'] ?? 'none';
        if (!$this->can_use_pro() && in_array($magnetic, ['strong', 'follow'], true)) {
            $magnetic = 'none';
        }
        if ('none' !== $magnetic) {
            $element->add_render_attribute('_wrapper', 'data-ka-magnetic', esc_attr($magnetic));
        }

        if (!empty($settings['kng_cursor_color_override'])) {
            $element->add_render_attribute('_wrapper', 'data-ka-cursor-color', esc_attr($settings['kng_cursor_color_override']));
        }

        if (!empty($settings['kng_cursor_size_multiplier']['size'])) {
            $multiplier = (float) $settings['kng_cursor_size_multiplier']['size'];
            if ($multiplier > 0) {
                $element->add_render_attribute('_wrapper', 'data-ka-cursor-size', esc_attr((string) $multiplier));
            }
        }
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
        $defaults = $this->get_default_settings();
        $output = $defaults;

        $output['enabled'] = !empty($input['enabled']);
        $output['disable_on_mobile'] = !empty($input['disable_on_mobile']);
        $output['hide_original_cursor'] = !empty($input['hide_original_cursor']);
        $output['rendering_mode'] = in_array($input['rendering_mode'] ?? 'css', ['css', 'enhanced'], true) ? $input['rendering_mode'] : 'css';

        $preset = $input['preset'] ?? [];
        $output['preset'] = [
            'type' => sanitize_text_field($preset['type'] ?? $defaults['preset']['type']),
            'size' => $this->sanitize_float($preset['size'] ?? $defaults['preset']['size'], 4, 200, (float) $defaults['preset']['size']),
            'border_width' => $this->sanitize_float($preset['border_width'] ?? $defaults['preset']['border_width'], 0, 20, (float) $defaults['preset']['border_width']),
            'fill_color' => $this->sanitize_color($preset['fill_color'] ?? $defaults['preset']['fill_color'], $defaults['preset']['fill_color']),
            'border_color' => $this->sanitize_color($preset['border_color'] ?? $defaults['preset']['border_color'], $defaults['preset']['border_color']),
            'opacity' => $this->sanitize_float($preset['opacity'] ?? $defaults['preset']['opacity'], 0, 1, (float) $defaults['preset']['opacity']),
            'blur' => $this->sanitize_float($preset['blur'] ?? $defaults['preset']['blur'], 0, 80, (float) $defaults['preset']['blur']),
            'blend_mode' => sanitize_text_field($preset['blend_mode'] ?? $defaults['preset']['blend_mode']),
        ];

        $states = $input['states'] ?? [];
        $output['states'] = [
            'normal' => [
                'scale' => $this->sanitize_float($states['normal']['scale'] ?? $defaults['states']['normal']['scale'], 0.1, 5, (float) $defaults['states']['normal']['scale']),
                'opacity' => $this->sanitize_float($states['normal']['opacity'] ?? $defaults['states']['normal']['opacity'], 0, 1, (float) $defaults['states']['normal']['opacity']),
            ],
            'hover_link' => [
                'scale' => $this->sanitize_float($states['hover_link']['scale'] ?? $defaults['states']['hover_link']['scale'], 0.5, 5, (float) $defaults['states']['hover_link']['scale']),
                'color' => $this->sanitize_color($states['hover_link']['color'] ?? $defaults['states']['hover_link']['color'], $defaults['states']['hover_link']['color']),
                'border_color' => $this->sanitize_color($states['hover_link']['border_color'] ?? $defaults['states']['hover_link']['border_color'], $defaults['states']['hover_link']['border_color']),
                'label' => sanitize_text_field($states['hover_link']['label'] ?? $defaults['states']['hover_link']['label']),
            ],
            'click' => [
                'ripple' => !empty($states['click']['ripple']),
                'scale' => $this->sanitize_float($states['click']['scale'] ?? $defaults['states']['click']['scale'], 0.4, 2, (float) $defaults['states']['click']['scale']),
            ],
        ];

        $targeting = $input['targeting'] ?? [];
        $output['targeting'] = [
            'exclude_logged_in' => !empty($targeting['exclude_logged_in']),
            'include_pages' => $this->normalize_ids($targeting['include_pages'] ?? []),
            'include_post_types' => $this->sanitize_list($targeting['include_post_types'] ?? []),
            'include_urls' => $this->sanitize_list($targeting['include_urls'] ?? []),
            'exclude_pages' => $this->normalize_ids($targeting['exclude_pages'] ?? []),
            'exclude_post_types' => $this->sanitize_list($targeting['exclude_post_types'] ?? []),
            'exclude_selectors' => sanitize_text_field($targeting['exclude_selectors'] ?? $defaults['targeting']['exclude_selectors']),
        ];

        $image = $input['image'] ?? [];
        $output['image'] = [
            'url' => esc_url_raw($image['url'] ?? $defaults['image']['url']),
            'size' => $this->sanitize_float($image['size'] ?? $defaults['image']['size'], 8, 256, (float) $defaults['image']['size']),
            'hotspot_x' => $this->sanitize_float($image['hotspot_x'] ?? $defaults['image']['hotspot_x'], -400, 400, (float) $defaults['image']['hotspot_x']),
            'hotspot_y' => $this->sanitize_float($image['hotspot_y'] ?? $defaults['image']['hotspot_y'], -400, 400, (float) $defaults['image']['hotspot_y']),
            'retina' => !empty($image['retina']),
        ];

        $magnetic = $input['magnetic'] ?? [];
        $output['magnetic'] = [
            'enabled' => !empty($magnetic['enabled']),
            'strength' => $this->sanitize_float($magnetic['strength'] ?? $defaults['magnetic']['strength'], 0, 1, (float) $defaults['magnetic']['strength']),
            'radius' => (int) $this->sanitize_float($magnetic['radius'] ?? $defaults['magnetic']['radius'], 20, 600, (float) $defaults['magnetic']['radius']),
            'selectors' => sanitize_text_field($magnetic['selectors'] ?? $defaults['magnetic']['selectors']),
        ];

        $movement = $input['movement'] ?? [];
        $tail = $movement['tail'] ?? [];
        $output['movement'] = [
            'follow_speed' => $this->sanitize_float($movement['follow_speed'] ?? $defaults['movement']['follow_speed'], 0.01, 1, (float) $defaults['movement']['follow_speed']),
            'tail' => [
                'points' => (int) $this->sanitize_float($tail['points'] ?? $defaults['movement']['tail']['points'], 0, 16, (float) $defaults['movement']['tail']['points']),
                'decay' => $this->sanitize_float($tail['decay'] ?? $defaults['movement']['tail']['decay'], 0.1, 0.99, (float) $defaults['movement']['tail']['decay']),
            ],
        ];

        return $this->apply_license_limitations($output);
    }

    /**
     * Get settings merged with defaults and license limitations.
     *
     * @return array<string,mixed>
     */
    public function get_settings(): array
    {
        $saved = get_option(self::OPTION_KEY, []);
        if (!is_array($saved)) {
            $saved = [];
        }
        $merged = wp_parse_args($saved, $this->get_default_settings());
        return $this->apply_license_limitations($merged);
    }

    /**
     * Build settings passed to frontend.
     *
     * @param array<string,mixed> $settings  Settings.
     * @param bool                $is_preview Preview mode.
     *
     * @return array<string,mixed>
     */
    private function build_frontend_settings(array $settings, bool $is_preview): array
    {
        $enabled = $this->should_activate($is_preview, $settings);

        return [
            'enabled' => $enabled,
            'mode' => $settings['rendering_mode'],
            'hideOriginalCursor' => !empty($settings['hide_original_cursor']),
            'preset' => $settings['preset'],
            'states' => $settings['states'],
            'image' => $settings['image'],
            'magnetic' => $settings['magnetic'],
            'movement' => $settings['movement'],
            'targeting' => [
                'excludeSelectors' => $settings['targeting']['exclude_selectors'],
            ],
            'selectors' => [
                'hover' => 'a,button,.ka-hoverable',
                'attribute' => '[data-ka-cursor]',
                'magnetic' => '[data-ka-magnetic]',
            ],
            'bodyClass' => 'ka-custom-cursor-enabled',
            'isPro' => $this->can_use_pro(),
            'isPreview' => $is_preview,
        ];
    }

    /**
     * Determine if cursor should activate on current request.
     *
     * @param bool                     $is_preview Preview flag.
     * @param array<string,mixed>|null $settings   Settings.
     *
     * @return bool
     */
    private function should_activate(bool $is_preview = false, ?array $settings = null): bool
    {
        $settings = $settings ?? $this->get_settings();

        if (empty($settings['enabled'])) {
            return false;
        }

        if (!$is_preview && is_admin()) {
            return false;
        }

        if (!$is_preview && !empty($settings['disable_on_mobile']) && wp_is_mobile()) {
            return false;
        }

        if (!$is_preview && !empty($settings['targeting']['exclude_logged_in']) && is_user_logged_in()) {
            return false;
        }

        if (!$is_preview && class_exists(Plugin::class) && Plugin::instance()->editor->is_edit_mode()) {
            return false;
        }

        if (!$this->can_use_pro()) {
            return true;
        }

        $page_id = get_queried_object_id();
        if (!empty($settings['targeting']['include_pages']) && !in_array((int) $page_id, $settings['targeting']['include_pages'], true)) {
            return false;
        }

        $post_type = get_post_type($page_id);
        if (!empty($settings['targeting']['include_post_types']) && !in_array((string) $post_type, $settings['targeting']['include_post_types'], true)) {
            return false;
        }

        if (!empty($settings['targeting']['include_urls'])) {
            $current_url = $this->get_current_url();
            $matched = false;
            foreach ($settings['targeting']['include_urls'] as $fragment) {
                if ('' !== $fragment && false !== stripos($current_url, $fragment)) {
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                return false;
            }
        }

        if (!empty($settings['targeting']['exclude_pages']) && in_array((int) $page_id, $settings['targeting']['exclude_pages'], true)) {
            return false;
        }

        if (!empty($settings['targeting']['exclude_post_types']) && in_array((string) $post_type, $settings['targeting']['exclude_post_types'], true)) {
            return false;
        }

        return true;
    }

    /**
     * Get default settings.
     *
     * @return array<string,mixed>
     */
    private function get_default_settings(): array
    {
        return [
            'enabled' => false,
            'disable_on_mobile' => true,
            'hide_original_cursor' => true,
            'rendering_mode' => 'css',
            'preset' => [
                'type' => 'dot',
                'size' => 14,
                'border_width' => 2,
                'fill_color' => '#111111',
                'border_color' => '#111111',
                'opacity' => 0.9,
                'blur' => 0,
                'blend_mode' => 'normal',
            ],
            'states' => [
                'normal' => [
                    'scale' => 1,
                    'opacity' => 1,
                ],
                'hover_link' => [
                    'scale' => 1.25,
                    'color' => '#111111',
                    'border_color' => '#ffffff',
                    'label' => '',
                ],
                'click' => [
                    'ripple' => false,
                    'scale' => 0.9,
                ],
            ],
            'targeting' => [
                'exclude_logged_in' => false,
                'include_pages' => [],
                'include_post_types' => [],
                'include_urls' => [],
                'exclude_pages' => [],
                'exclude_post_types' => [],
                'exclude_selectors' => '.elementor-editor-active,.no-cursor-zone',
            ],
            'image' => [
                'url' => '',
                'size' => 48,
                'hotspot_x' => 0,
                'hotspot_y' => 0,
                'retina' => true,
            ],
            'magnetic' => [
                'enabled' => false,
                'strength' => 0.3,
                'radius' => 140,
                'selectors' => 'button,.ka-magnetic',
            ],
            'movement' => [
                'follow_speed' => 0.18,
                'tail' => [
                    'points' => 0,
                    'decay' => 0.65,
                ],
            ],
        ];
    }

    /**
     * Apply license limitations for free version.
     *
     * @param array<string,mixed> $settings Settings.
     *
     * @return array<string,mixed>
     */
    private function apply_license_limitations(array $settings): array
    {
        if ($this->can_use_pro()) {
            return $settings;
        }

        // Free cursor types
        $allowed_presets = ['dot', 'ring', 'dot-ring', 'outline', 'crosshair', 'arrow'];
        if (!in_array($settings['preset']['type'], $allowed_presets, true)) {
            $settings['preset']['type'] = 'dot';
        }

        $settings['preset']['blur'] = 0;
        $settings['preset']['blend_mode'] = 'normal';

        $settings['states']['hover_link']['label'] = '';
        $settings['states']['click']['ripple'] = false;

        $settings['image'] = $this->get_default_settings()['image'];
        $settings['magnetic']['enabled'] = false;
        $settings['magnetic']['selectors'] = 'button,.ka-magnetic';
        $settings['movement']['follow_speed'] = 0.18;
        $settings['movement']['tail'] = [
            'points' => 0,
            'decay' => 0.65,
        ];

        $settings['targeting']['include_pages'] = [];
        $settings['targeting']['include_post_types'] = [];
        $settings['targeting']['include_urls'] = [];
        $settings['targeting']['exclude_pages'] = [];
        $settings['targeting']['exclude_post_types'] = [];
        $settings['targeting']['exclude_selectors'] = '.elementor-editor-active,.no-cursor-zone';

        return $settings;
    }

    /**
     * Check license status.
     *
     * @return bool
     */
    private function can_use_pro(): bool
    {
        return function_exists('king_addons_freemius') && king_addons_freemius()->can_use_premium_code__premium_only();
    }

    /**
     * Normalize IDs input to integer array.
     *
     * @param mixed $value Raw ids.
     *
     * @return array<int,int>
     */
    private function normalize_ids($value): array
    {
        if (is_string($value)) {
            $value = explode(',', $value);
        }

        if (!is_array($value)) {
            return [];
        }

        $ids = [];
        foreach ($value as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        return array_values(array_unique($ids));
    }

    /**
     * Sanitize color string.
     *
     * @param string $value    Raw value.
     * @param string $fallback Fallback.
     *
     * @return string
     */
    private function sanitize_color(string $value, string $fallback): string
    {
        $value = trim($value);
        if ('' === $value) {
            return $fallback;
        }
        return sanitize_text_field($value);
    }

    /**
     * Sanitize float with bounds.
     *
     * @param mixed  $value    Raw value.
     * @param float  $min      Min value.
     * @param float  $max      Max value.
     * @param float  $fallback Default.
     *
     * @return float
     */
    private function sanitize_float($value, float $min, float $max, float $fallback): float
    {
        $float_val = (float) $value;
        if ($float_val < $min || $float_val > $max) {
            return $fallback;
        }
        return $float_val;
    }

    /**
     * Sanitize comma separated list or array.
     *
     * @param mixed $value Raw value.
     *
     * @return array<int,string>
     */
    private function sanitize_list($value): array
    {
        if (is_string($value)) {
            $value = explode(',', $value);
        }

        if (!is_array($value)) {
            return [];
        }

        $clean = [];
        foreach ($value as $item) {
            $item = sanitize_text_field($item);
            if ('' !== $item) {
                $clean[] = $item;
            }
        }

        return array_values(array_unique($clean));
    }

    /**
     * Get current URL.
     *
     * @return string
     */
    private function get_current_url(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && 'off' !== $_SERVER['HTTPS']) ? 'https' : 'http';
        $host = sanitize_text_field($_SERVER['HTTP_HOST'] ?? '');
        $uri = sanitize_text_field($_SERVER['REQUEST_URI'] ?? '');
        return $scheme . '://' . $host . $uri;
    }
}





