<?php
/**
 * Settings admin page - V3 Premium style inspired design.
 *
 * @package King_Addons
 */

if (!defined('ABSPATH')) {
    exit;
}

// Theme mode is per-user
$theme_mode = get_user_meta(get_current_user_id(), 'king_addons_theme_mode', true);
$allowed_theme_modes = ['dark', 'light', 'auto'];
if (!in_array($theme_mode, $allowed_theme_modes, true)) {
    $theme_mode = 'dark';
}

// Handle form submission
if (isset($_POST['king_addons_settings_submit_settings'])) {
    if (
        !isset($_POST['king_addons_settings_nonce_field']) ||
        !wp_verify_nonce($_POST['king_addons_settings_nonce_field'], 'king_addons_settings_save_settings')
    ) {
        wp_die('Security check failed.');
    }

    update_option('king_addons_google_map_api_key', sanitize_text_field($_POST['king_addons_google_map_api_key']));
    update_option('king_addons_mailchimp_api_key', sanitize_text_field($_POST['king_addons_mailchimp_api_key']));
    update_option('king_addons_recaptcha_v3_site_key', sanitize_text_field($_POST['king_addons_recaptcha_v3_site_key']));
    update_option('king_addons_recaptcha_v3_secret_key', sanitize_text_field($_POST['king_addons_recaptcha_v3_secret_key']));
    update_option('king_addons_recaptcha_v3_score_threshold', floatval($_POST['king_addons_recaptcha_v3_score_threshold']));

    // Lightbox colors
    update_option('king_addons_lightbox_bg_color', sanitize_text_field($_POST['king_addons_lightbox_bg_color']));
    update_option('king_addons_lightbox_toolbar_color', sanitize_text_field($_POST['king_addons_lightbox_toolbar_color']));
    update_option('king_addons_lightbox_caption_color', sanitize_text_field($_POST['king_addons_lightbox_caption_color']));
    update_option('king_addons_lightbox_gallery_color', sanitize_text_field($_POST['king_addons_lightbox_gallery_color']));
    update_option('king_addons_lightbox_pb_color', sanitize_text_field($_POST['king_addons_lightbox_pb_color']));
    update_option('king_addons_lightbox_ui_color', sanitize_text_field($_POST['king_addons_lightbox_ui_color']));
    update_option('king_addons_lightbox_ui_hover_color', sanitize_text_field($_POST['king_addons_lightbox_ui_hover_color']));
    update_option('king_addons_lightbox_text_color', sanitize_text_field($_POST['king_addons_lightbox_text_color']));

    // Lightbox numbers
    update_option('king_addons_lightbox_icon_size', intval($_POST['king_addons_lightbox_icon_size']));
    update_option('king_addons_lightbox_text_size', intval($_POST['king_addons_lightbox_text_size']));
    update_option('king_addons_lightbox_arrow_size', intval($_POST['king_addons_lightbox_arrow_size']));

    // Import Performance
    $improve_import = isset($_POST['king_addons_improve_import_performance']) ? '1' : '0';
    update_option('king_addons_improve_import_performance', $improve_import);

    // Template Catalog Button
    if (function_exists('king_addons_freemius') && king_addons_freemius()->can_use_premium_code()) {
        $disable_template_catalog = isset($_POST['king_addons_disable_template_catalog_button']) ? '1' : '0';
        update_option('king_addons_disable_template_catalog_button', $disable_template_catalog);
    }

    add_settings_error('king_addons_messages', 'king_addons_message', esc_html__('Settings Saved', 'king-addons'), 'updated');
}

// Get existing values
$google_map_key = get_option('king_addons_google_map_api_key', '');
$mailchimp_key = get_option('king_addons_mailchimp_api_key', '');
$recaptcha_site_key = get_option('king_addons_recaptcha_v3_site_key', '');
$recaptcha_secret_key = get_option('king_addons_recaptcha_v3_secret_key', '');
$recaptcha_score_threshold = get_option('king_addons_recaptcha_v3_score_threshold', 0.5);
$improve_import_performance = get_option('king_addons_improve_import_performance', '1');
$disable_template_catalog_button = get_option('king_addons_disable_template_catalog_button', '0');

// Enqueue the shared CSS
$css_url = KING_ADDONS_URL . 'includes/admin/layouts/shared/admin-v3-styles.css';
$css_path = KING_ADDONS_PATH . 'includes/admin/layouts/shared/admin-v3-styles.css';
$css_version = file_exists($css_path) ? filemtime($css_path) : KING_ADDONS_VERSION;
?>

<link rel="stylesheet" href="<?php echo esc_url($css_url); ?>?v=<?php echo esc_attr($css_version); ?>">

<script>
document.body.classList.add('ka-admin-v3');
(function() {
    const mode = '<?php echo esc_js($theme_mode); ?>';
    const mql = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
    const isDark = mode === 'auto' ? !!(mql && mql.matches) : mode === 'dark';
    document.body.classList.toggle('ka-v3-dark', isDark);
    document.documentElement.classList.toggle('ka-v3-dark', isDark);
})();
</script>

<div class="ka-admin-wrap">
    <?php settings_errors('king_addons_messages'); ?>
    <!-- Header -->
    <div class="ka-admin-header">
        <div class="ka-admin-header-left">
            <div class="ka-admin-header-icon purple">
                <span class="dashicons dashicons-admin-generic"></span>
            </div>
            <div>
                <h1 class="ka-admin-title"><?php esc_html_e('Settings', 'king-addons'); ?></h1>
                <p class="ka-admin-subtitle"><?php esc_html_e('Configure API keys and global options for King Addons.', 'king-addons'); ?></p>
            </div>
        </div>
        <div class="ka-admin-header-actions">
            <div class="ka-v3-segmented" id="ka-v3-theme-segment" role="radiogroup" aria-label="<?php echo esc_attr(esc_html__('Theme', 'king-addons')); ?>" data-active="<?php echo esc_attr($theme_mode); ?>">
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
            <a href="https://www.youtube.com/@kingaddons" target="_blank" class="ka-btn ka-btn-secondary">
                <span class="dashicons dashicons-book"></span>
                <?php esc_html_e('Guides', 'king-addons'); ?>
            </a>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="ka-tabs">
        <button type="button" class="ka-tab active" data-tab="integrations"><?php esc_html_e('Integrations', 'king-addons'); ?></button>
        <button type="button" class="ka-tab" data-tab="lightbox"><?php esc_html_e('Lightbox', 'king-addons'); ?></button>
        <button type="button" class="ka-tab" data-tab="performance"><?php esc_html_e('Performance', 'king-addons'); ?></button>
    </div>

    <form method="post" action="">
        <?php wp_nonce_field('king_addons_settings_save_settings', 'king_addons_settings_nonce_field'); ?>

        <!-- Integrations Tab -->
        <div class="ka-tab-content active" data-tab="integrations">
            <div class="ka-card">
                <div class="ka-card-header">
                    <span class="dashicons dashicons-admin-plugins"></span>
                    <h2><?php esc_html_e('API Keys', 'king-addons'); ?></h2>
                </div>
                <div class="ka-card-body">
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Google Map API Key', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <input type="text" name="king_addons_google_map_api_key" value="<?php echo esc_attr($google_map_key); ?>" placeholder="Abc_42..." />
                            <p class="ka-row-desc">
                                <?php esc_html_e('Enter your Google Map API key from Google Cloud Platform.', 'king-addons'); ?>
                                <a href="https://www.youtube.com/watch?v=O5cUoVpVUjU" target="_blank"><?php esc_html_e('Learn how →', 'king-addons'); ?></a>
                            </p>
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('MailChimp API Key', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <input type="text" name="king_addons_mailchimp_api_key" value="<?php echo esc_attr($mailchimp_key); ?>" />
                            <p class="ka-row-desc">
                                <?php esc_html_e('Insert your MailChimp API key for mailing features.', 'king-addons'); ?>
                                <a href="https://mailchimp.com/help/about-api-keys/" target="_blank"><?php esc_html_e('Learn how →', 'king-addons'); ?></a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ka-card">
                <div class="ka-card-header">
                    <span class="dashicons dashicons-shield"></span>
                    <h2><?php esc_html_e('reCAPTCHA v3', 'king-addons'); ?></h2>
                </div>
                <div class="ka-card-body">
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Site Key', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <input type="text" name="king_addons_recaptcha_v3_site_key" value="<?php echo esc_attr($recaptcha_site_key); ?>" />
                            <p class="ka-row-desc">
                                <?php esc_html_e('Enter your reCAPTCHA Site Key for Form Builder.', 'king-addons'); ?>
                                <a href="https://www.google.com/recaptcha/about/" target="_blank"><?php esc_html_e('Get keys →', 'king-addons'); ?></a>
                            </p>
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Secret Key', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <input type="password" name="king_addons_recaptcha_v3_secret_key" value="<?php echo esc_attr($recaptcha_secret_key); ?>" />
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Score Threshold', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <input type="number" step="0.1" min="0" max="1" name="king_addons_recaptcha_v3_score_threshold" value="<?php echo esc_attr($recaptcha_score_threshold); ?>" style="max-width:100px" />
                            <p class="ka-row-desc"><?php esc_html_e('Score threshold 0.0 to 1.0 (default 0.5). Higher values are more strict.', 'king-addons'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lightbox Tab -->
        <div class="ka-tab-content" data-tab="lightbox">
            <div class="ka-card">
                <div class="ka-card-header">
                    <span class="dashicons dashicons-format-gallery"></span>
                    <h2><?php esc_html_e('Lightbox Colors', 'king-addons'); ?></h2>
                </div>
                <div class="ka-card-body">
                    <div class="ka-grid-2">
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Text Color', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <div class="ka-color-wrap">
                                    <input type="text" name="king_addons_lightbox_text_color" data-alpha-enabled="true" data-default-color="#efefef" value="<?php echo esc_attr(get_option('king_addons_lightbox_text_color', '#efefef')); ?>" class="color-picker" />
                                </div>
                            </div>
                        </div>
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Background', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <div class="ka-color-wrap">
                                    <input type="text" name="king_addons_lightbox_bg_color" data-alpha-enabled="true" data-default-color="rgba(0,0,0,0.6)" value="<?php echo esc_attr(get_option('king_addons_lightbox_bg_color', 'rgba(0,0,0,0.6)')); ?>" class="color-picker" />
                                </div>
                            </div>
                        </div>
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Toolbar BG', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <div class="ka-color-wrap">
                                    <input type="text" name="king_addons_lightbox_toolbar_color" data-alpha-enabled="true" data-default-color="rgba(0,0,0,0.8)" value="<?php echo esc_attr(get_option('king_addons_lightbox_toolbar_color', 'rgba(0,0,0,0.8)')); ?>" class="color-picker" />
                                </div>
                            </div>
                        </div>
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Caption BG', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <div class="ka-color-wrap">
                                    <input type="text" name="king_addons_lightbox_caption_color" data-alpha-enabled="true" data-default-color="rgba(0,0,0,0.8)" value="<?php echo esc_attr(get_option('king_addons_lightbox_caption_color', 'rgba(0,0,0,0.8)')); ?>" class="color-picker" />
                                </div>
                            </div>
                        </div>
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Gallery BG', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <div class="ka-color-wrap">
                                    <input type="text" name="king_addons_lightbox_gallery_color" data-alpha-enabled="true" data-default-color="#444444" value="<?php echo esc_attr(get_option('king_addons_lightbox_gallery_color', '#444444')); ?>" class="color-picker" />
                                </div>
                            </div>
                        </div>
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Progress Bar', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <div class="ka-color-wrap">
                                    <input type="text" name="king_addons_lightbox_pb_color" data-alpha-enabled="true" data-default-color="#8a8a8a" value="<?php echo esc_attr(get_option('king_addons_lightbox_pb_color', '#8a8a8a')); ?>" class="color-picker" />
                                </div>
                            </div>
                        </div>
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('UI Color', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <div class="ka-color-wrap">
                                    <input type="text" name="king_addons_lightbox_ui_color" data-alpha-enabled="true" data-default-color="#efefef" value="<?php echo esc_attr(get_option('king_addons_lightbox_ui_color', '#efefef')); ?>" class="color-picker" />
                                </div>
                            </div>
                        </div>
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('UI Hover', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <div class="ka-color-wrap">
                                    <input type="text" name="king_addons_lightbox_ui_hover_color" data-alpha-enabled="true" data-default-color="#ffffff" value="<?php echo esc_attr(get_option('king_addons_lightbox_ui_hover_color', '#ffffff')); ?>" class="color-picker" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ka-card">
                <div class="ka-card-header">
                    <span class="dashicons dashicons-editor-expand"></span>
                    <h2><?php esc_html_e('Lightbox Sizes', 'king-addons'); ?></h2>
                </div>
                <div class="ka-card-body">
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Icon Size', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <input type="number" name="king_addons_lightbox_icon_size" value="<?php echo esc_attr(get_option('king_addons_lightbox_icon_size', '20')); ?>" style="max-width:100px" />
                            <span style="color:#86868b;margin-left:8px">px</span>
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Arrow Size', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <input type="number" name="king_addons_lightbox_arrow_size" value="<?php echo esc_attr(get_option('king_addons_lightbox_arrow_size', '35')); ?>" style="max-width:100px" />
                            <span style="color:#86868b;margin-left:8px">px</span>
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Text Size', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <input type="number" name="king_addons_lightbox_text_size" value="<?php echo esc_attr(get_option('king_addons_lightbox_text_size', '14')); ?>" style="max-width:100px" />
                            <span style="color:#86868b;margin-left:8px">px</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Tab -->
        <div class="ka-tab-content" data-tab="performance">
            <div class="ka-card">
                <div class="ka-card-header">
                    <span class="dashicons dashicons-download"></span>
                    <h2><?php esc_html_e('Import Templates', 'king-addons'); ?></h2>
                </div>
                <div class="ka-card-body">
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Performance Mode', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <label class="ka-toggle">
                                <input type="checkbox" name="king_addons_improve_import_performance" value="1" <?php checked($improve_import_performance, '1'); ?> />
                                <span class="ka-toggle-slider"></span>
                                <span class="ka-toggle-label"><?php esc_html_e('Improve import on low-performance servers', 'king-addons'); ?></span>
                            </label>
                            <p class="ka-row-desc"><?php esc_html_e('Applies optimizations like increased PHP limits during import. Enabled by default.', 'king-addons'); ?></p>
                        </div>
                    </div>
                    <?php if (function_exists('king_addons_freemius') && king_addons_freemius()->can_use_premium_code()) : ?>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Template Button', 'king-addons'); ?><span class="ka-pro-badge">PRO</span></div>
                        <div class="ka-row-field">
                            <label class="ka-toggle">
                                <input type="checkbox" name="king_addons_disable_template_catalog_button" value="1" <?php checked($disable_template_catalog_button, '1'); ?> />
                                <span class="ka-toggle-slider"></span>
                                <span class="ka-toggle-label"><?php esc_html_e('Hide "Start with a Template" button in editor', 'king-addons'); ?></span>
                            </label>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="ka-card">
            <div class="ka-submit">
                <button type="submit" name="king_addons_settings_submit_settings" class="ka-btn ka-btn-primary">
                    <?php esc_html_e('Save Settings', 'king-addons'); ?>
                </button>
            </div>
        </div>
    </form>
</div>

<?php
// Enqueue color picker
wp_enqueue_style('wp-color-picker');
wp_enqueue_script('wp-color-picker');
?>
<script>
(function($) {
    'use strict';
    
    $(document).ready(function() {
        const ajaxUrl = '<?php echo esc_url(admin_url('admin-ajax.php')); ?>';
        const nonce = '<?php echo esc_js(wp_create_nonce('king_addons_dashboard_ui')); ?>';

        // Theme segmented control (dashboard-style)
        const $themeSegment = $('#ka-v3-theme-segment');
        const $themeSegmentButtons = $themeSegment.find('.ka-v3-segmented-btn');
        const themeMql = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
        let themeMode = ($themeSegment.attr('data-active') || 'dark').toString();
        let themeMqlHandler = null;

        function saveUISetting(key, value) {
            $.post(ajaxUrl, {
                action: 'king_addons_save_dashboard_ui',
                nonce: nonce,
                key: key,
                value: value
            });
        }

        function updateSegment(mode) {
            $themeSegment.attr('data-active', mode);
            $themeSegmentButtons.each(function() {
                const theme = $(this).data('theme');
                $(this).attr('aria-pressed', theme === mode ? 'true' : 'false');
            });
        }

        function applyThemeClass(isDark) {
            $('body').toggleClass('ka-v3-dark', isDark);
            document.documentElement.classList.toggle('ka-v3-dark', isDark);
        }

        function setThemeMode(mode, save) {
            themeMode = mode;
            updateSegment(mode);

            if (themeMqlHandler && themeMql) {
                if (themeMql.removeEventListener) {
                    themeMql.removeEventListener('change', themeMqlHandler);
                } else if (themeMql.removeListener) {
                    themeMql.removeListener(themeMqlHandler);
                }
                themeMqlHandler = null;
            }

            if (mode === 'auto') {
                applyThemeClass(!!(themeMql && themeMql.matches));
                themeMqlHandler = function(e) {
                    if (themeMode !== 'auto') {
                        return;
                    }
                    applyThemeClass(!!e.matches);
                };
                if (themeMql) {
                    if (themeMql.addEventListener) {
                        themeMql.addEventListener('change', themeMqlHandler);
                    } else if (themeMql.addListener) {
                        themeMql.addListener(themeMqlHandler);
                    }
                }
            } else {
                applyThemeClass(mode === 'dark');
            }

            if (save) {
                saveUISetting('theme_mode', mode);
            }
        }

        $themeSegment.on('click', '.ka-v3-segmented-btn', function(e) {
            e.preventDefault();
            const mode = ($(this).data('theme') || 'dark').toString();
            setThemeMode(mode, true);
        });

        // Ensure mode applies for Auto (listener + system state)
        setThemeMode(themeMode, false);

        // Tab navigation
        $('.ka-tab').on('click', function() {
            const target = $(this).data('tab');
            $('.ka-tab').removeClass('active');
            $(this).addClass('active');
            $('.ka-tab-content').removeClass('active');
            $(`.ka-tab-content[data-tab="${target}"]`).addClass('active');
        });

        // Initialize color pickers
        if ($.fn.wpColorPicker) {
            $('.color-picker').each(function() {
                var $input = $(this);
                $input.wpColorPicker({
                    defaultColor: $input.data('default-color') || ''
                });
            });
        }
    });
})(jQuery);
</script>
