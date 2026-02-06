<?php
/**
 * Custom Code Manager - Settings Page Template
 *
 * @package King_Addons
 */

defined('ABSPATH') || exit;

use King_Addons\Custom_Code_Manager;

/** @var array $settings */
/** @var bool $has_pro */
?>
<div class="kng-cc-admin">
    <!-- Header -->
    <header class="kng-cc-header">
        <div class="kng-cc-header-left">
            <a href="<?php echo esc_url(admin_url('admin.php?page=king-addons-custom-code')); ?>" class="kng-cc-back-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                    <line x1="19" y1="12" x2="5" y2="12"/>
                    <polyline points="12 19 5 12 12 5"/>
                </svg>
                <?php esc_html_e('Back to snippets', 'king-addons'); ?>
            </a>
            <h1 class="kng-cc-title"><?php esc_html_e('Settings', 'king-addons'); ?></h1>
            <p class="kng-cc-subtitle"><?php esc_html_e('Configure Custom Code Manager behavior', 'king-addons'); ?></p>
        </div>
    </header>

    <div class="kng-cc-settings-content">
        <form id="kng-cc-settings-form" class="kng-cc-settings-form">
            <!-- General Settings -->
            <div class="kng-cc-settings-card">
                <div class="kng-cc-settings-card-header">
                    <h3>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
                            <circle cx="12" cy="12" r="3"/>
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                        </svg>
                        <?php esc_html_e('General', 'king-addons'); ?>
                    </h3>
                </div>
                <div class="kng-cc-settings-card-content">
                    <div class="kng-v3-field">
                        <label class="kng-v3-toggle-wrap kng-v3-toggle-row">
                            <input type="checkbox" name="enabled" <?php checked($settings['enabled']); ?> />
                            <span class="kng-v3-toggle-slider"></span>
                            <span class="kng-v3-toggle-text"><?php esc_html_e('Enable Custom Code Manager', 'king-addons'); ?></span>
                        </label>
                        <p class="kng-v3-field-help"><?php esc_html_e('When disabled, no custom code will be injected on the frontend.', 'king-addons'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Default Settings -->
            <div class="kng-cc-settings-card">
                <div class="kng-cc-settings-card-header">
                    <h3>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
                            <path d="M12 3v18M3 12h18"/>
                        </svg>
                        <?php esc_html_e('Defaults', 'king-addons'); ?>
                    </h3>
                </div>
                <div class="kng-cc-settings-card-content">
                    <div class="kng-cc-settings-row">
                        <div class="kng-v3-field">
                            <label class="kng-v3-label"><?php esc_html_e('Default CSS Location', 'king-addons'); ?></label>
                            <select name="default_location_css" class="kng-v3-select">
                                <option value="head" <?php selected($settings['default_location_css'], 'head'); ?>><?php esc_html_e('Head', 'king-addons'); ?></option>
                                <option value="footer" <?php selected($settings['default_location_css'], 'footer'); ?>><?php esc_html_e('Footer', 'king-addons'); ?></option>
                            </select>
                        </div>
                        <div class="kng-v3-field">
                            <label class="kng-v3-label"><?php esc_html_e('Default JS Location', 'king-addons'); ?></label>
                            <select name="default_location_js" class="kng-v3-select">
                                <option value="head" <?php selected($settings['default_location_js'], 'head'); ?>><?php esc_html_e('Head', 'king-addons'); ?></option>
                                <option value="footer" <?php selected($settings['default_location_js'], 'footer'); ?>><?php esc_html_e('Footer', 'king-addons'); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="kng-v3-field">
                        <label class="kng-v3-label"><?php esc_html_e('Default Priority', 'king-addons'); ?></label>
                        <input type="number" name="default_priority" class="kng-v3-input kng-v3-input--small" value="<?php echo esc_attr($settings['default_priority']); ?>" min="1" max="9999" />
                    </div>
                </div>
            </div>

            <!-- Debug Settings -->
            <div class="kng-cc-settings-card">
                <div class="kng-cc-settings-card-header">
                    <h3>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        </svg>
                        <?php esc_html_e('Debug', 'king-addons'); ?>
                    </h3>
                </div>
                <div class="kng-cc-settings-card-content">
                    <div class="kng-v3-field">
                        <label class="kng-v3-toggle-wrap kng-v3-toggle-row">
                            <input type="checkbox" name="debug_mode" <?php checked($settings['debug_mode']); ?> />
                            <span class="kng-v3-toggle-slider"></span>
                            <span class="kng-v3-toggle-text"><?php esc_html_e('Enable Debug Mode', 'king-addons'); ?></span>
                        </label>
                        <p class="kng-v3-field-help"><?php esc_html_e('When enabled, logs which snippets are loaded on each page to browser console (visible to admins only).', 'king-addons'); ?></p>
                    </div>
                </div>
            </div>

            <div class="kng-cc-settings-actions">
                <button type="submit" class="kng-v3-btn kng-v3-btn--primary" id="kng-cc-save-settings">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                        <polyline points="17 21 17 13 7 13 7 21"/>
                        <polyline points="7 3 7 8 15 8"/>
                    </svg>
                    <?php esc_html_e('Save Settings', 'king-addons'); ?>
                </button>
            </div>
        </form>
    </div>
</div>
