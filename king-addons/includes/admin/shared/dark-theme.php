<?php
/**
 * Shared Dark Theme Support for King Addons Admin Pages
 * 
 * Include this file at the top of admin pages to add dark theme support.
 * Usage: include KING_ADDONS_PATH . 'includes/admin/shared/dark-theme.php';
 * 
 * @package King_Addons
 */

if (!defined('ABSPATH')) {
    exit;
}

// Ensure V3 segmented control styles are available wherever this helper is used.
wp_enqueue_style(
    'king-addons-admin-v3',
    KING_ADDONS_URL . 'includes/admin/layouts/shared/admin-v3-styles.css',
    [],
    KING_ADDONS_VERSION
);

// Theme mode is per-user. Must be global so functions can access it.
global $ka_theme_mode;
$ka_theme_mode = get_user_meta(get_current_user_id(), 'king_addons_theme_mode', true);
$ka_allowed_theme_modes = ['dark', 'light', 'auto'];
if (!in_array($ka_theme_mode, $ka_allowed_theme_modes, true)) {
    $ka_theme_mode = 'dark';
}

/**
 * Output the dark theme toggle HTML
 */
function ka_render_dark_theme_toggle() {
    global $ka_theme_mode;
    ?>
    <div class="ka-v3-segmented" id="ka-v3-theme-segment" role="radiogroup" aria-label="<?php echo esc_attr(esc_html__('Theme', 'king-addons')); ?>" data-active="<?php echo esc_attr($ka_theme_mode); ?>">
        <span class="ka-v3-segmented-indicator" aria-hidden="true"></span>
        <button type="button" class="ka-v3-segmented-btn" data-theme="light" aria-pressed="<?php echo $ka_theme_mode === 'light' ? 'true' : 'false'; ?>">
            <span class="ka-v3-segmented-icon" aria-hidden="true">☀︎</span>
            <?php esc_html_e('Light', 'king-addons'); ?>
        </button>
        <button type="button" class="ka-v3-segmented-btn" data-theme="dark" aria-pressed="<?php echo $ka_theme_mode === 'dark' ? 'true' : 'false'; ?>">
            <span class="ka-v3-segmented-icon" aria-hidden="true">☾</span>
            <?php esc_html_e('Dark', 'king-addons'); ?>
        </button>
        <button type="button" class="ka-v3-segmented-btn" data-theme="auto" aria-pressed="<?php echo $ka_theme_mode === 'auto' ? 'true' : 'false'; ?>">
            <span class="ka-v3-segmented-icon" aria-hidden="true">◐</span>
            <?php esc_html_e('Auto', 'king-addons'); ?>
        </button>
    </div>
    <?php
}

/**
 * Output the dark theme CSS
 */
function ka_render_dark_theme_styles() {
    ?>
    <style>
    /* Theme Switch Button */
    .ka-theme-switch {
        display: flex;
        align-items: center;
    }
    .ka-theme-toggle {
        background: none;
        border: none;
        padding: 0;
        cursor: pointer;
        display: flex;
        align-items: center;
    }
    .ka-theme-switch-track {
        width: 52px;
        height: 28px;
        background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
        border-radius: 14px;
        position: relative;
        transition: background 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 6px;
    }
    .ka-theme-switch-track::before,
    .ka-theme-switch-track::after {
        font-size: 12px;
        opacity: 0.7;
    }
    .ka-theme-switch-thumb {
        position: absolute;
        top: 2px;
        left: 2px;
        width: 24px;
        height: 24px;
        background: #fff;
        border-radius: 50%;
        transition: transform 0.3s ease, background 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    .ka-theme-switch-thumb::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }
    .ka-theme-switch-thumb .ka-sun-icon {
        color: #f59e0b;
        display: flex;
        transition: opacity 0.2s ease;
    }
    .ka-theme-switch-thumb .ka-moon-icon {
        color: #6366f1;
        display: none;
        transition: opacity 0.2s ease;
    }
    .ka-theme-switch-track:hover {
        box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2);
    }

    /* Dark theme switch state */
    .ka-dark-theme .ka-theme-switch-track {
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
    }
    .ka-dark-theme .ka-theme-switch-track::before,
    .ka-dark-theme .ka-theme-switch-track::after {
        color: #fff;
    }
    .ka-dark-theme .ka-theme-switch-thumb {
        transform: translateX(24px);
        background: #1e1b4b;
    }
    .ka-dark-theme .ka-theme-switch-thumb::before {
        color: #c7d2fe;
    }
    .ka-dark-theme .ka-theme-switch-thumb .ka-sun-icon {
        display: none;
    }
    .ka-dark-theme .ka-theme-switch-thumb .ka-moon-icon {
        display: flex;
        color: #c7d2fe;
    }
    .ka-dark-theme .ka-theme-switch-track:hover {
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.3);
    }

    /* Instant theme switch (no transition) */
    .ka-no-transition,
    .ka-no-transition * {
        transition: none !important;
    }

    /* ========================================
       DARK THEME STYLES
       ======================================== */
    
    /* WordPress admin background */
    body.ka-dark-theme #wpcontent,
    body.ka-dark-theme #wpbody,
    body.ka-dark-theme #wpbody-content {
        background: #0f172a;
    }

    /* Main wrappers */
    .ka-dark-theme .ka-settings-wrap,
    .ka-dark-theme .ka-cookie-wrap,
    .ka-dark-theme .ka-agegate-wrap,
    .ka-dark-theme .ka-ai-wrap,
    .ka-dark-theme .ka-wishlist-wrap,
    .ka-dark-theme .ka-analytics-wrap,
    .ka-dark-theme .ka-security-wrap,
    .ka-dark-theme .ka-woo-wrap {
        background: #0f172a;
    }

    /* Headers - keep gradients but adjust */
    .ka-dark-theme .ka-settings-header,
    .ka-dark-theme .ka-cookie-header,
    .ka-dark-theme .ka-agegate-header,
    .ka-dark-theme .ka-ai-header,
    .ka-dark-theme .ka-wishlist-header,
    .ka-dark-theme .ka-analytics-header,
    .ka-dark-theme .ka-security-header,
    .ka-dark-theme .ka-woo-header {
        box-shadow: 0 4px 20px rgba(0,0,0,0.3);
    }

    /* Cards */
    .ka-dark-theme .ka-card,
    .ka-dark-theme .ka-section,
    .ka-dark-theme .ka-stat-card,
    .ka-dark-theme .ka-stats-card {
        background: #1e293b;
        border-color: #334155;
        box-shadow: 0 1px 3px rgba(0,0,0,0.3);
    }
    .ka-dark-theme .ka-card-header,
    .ka-dark-theme .ka-section-header {
        background: #1e293b;
        border-bottom-color: #334155;
    }
    .ka-dark-theme .ka-card-header h2,
    .ka-dark-theme .ka-section-header h2,
    .ka-dark-theme .ka-card-header h3,
    .ka-dark-theme .ka-section-title {
        color: #f1f5f9;
    }
    .ka-dark-theme .ka-card-body,
    .ka-dark-theme .ka-section-body {
        background: #1e293b;
    }

    /* Form elements */
    .ka-dark-theme .ka-row {
        border-bottom-color: #334155;
    }
    .ka-dark-theme .ka-row-label,
    .ka-dark-theme label {
        color: #cbd5e1;
    }
    .ka-dark-theme .ka-row-desc,
    .ka-dark-theme .ka-field-desc,
    .ka-dark-theme .description {
        color: #94a3b8;
    }
    .ka-dark-theme .ka-row-field input[type="text"],
    .ka-dark-theme .ka-row-field input[type="number"],
    .ka-dark-theme .ka-row-field input[type="password"],
    .ka-dark-theme .ka-row-field input[type="email"],
    .ka-dark-theme .ka-row-field input[type="url"],
    .ka-dark-theme .ka-row-field textarea,
    .ka-dark-theme .ka-row-field select,
    .ka-dark-theme input[type="text"],
    .ka-dark-theme input[type="number"],
    .ka-dark-theme input[type="password"],
    .ka-dark-theme input[type="email"],
    .ka-dark-theme input[type="url"],
    .ka-dark-theme textarea,
    .ka-dark-theme select {
        background: #0f172a;
        border-color: #475569;
        color: #f1f5f9;
    }
    .ka-dark-theme .ka-row-field input:focus,
    .ka-dark-theme input:focus,
    .ka-dark-theme textarea:focus,
    .ka-dark-theme select:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
    }
    .ka-dark-theme input::placeholder,
    .ka-dark-theme textarea::placeholder {
        color: #64748b;
    }

    /* Toggles */
    .ka-dark-theme .ka-toggle-slider {
        background: #475569;
    }
    .ka-dark-theme .ka-toggle input:checked + .ka-toggle-slider {
        background: #6366f1;
    }

    /* Tabs */
    .ka-dark-theme .ka-tabs {
        background: #1e293b;
    }
    .ka-dark-theme .ka-tab {
        color: #94a3b8;
        background: transparent;
    }
    .ka-dark-theme .ka-tab:hover {
        background: #334155;
        color: #f1f5f9;
    }
    .ka-dark-theme .ka-tab.active {
        background: #6366f1;
        color: #fff;
    }

    /* Tables */
    .ka-dark-theme table {
        background: #1e293b;
        border-color: #334155;
    }
    .ka-dark-theme th {
        background: #0f172a;
        color: #f1f5f9;
        border-color: #334155;
    }
    .ka-dark-theme td {
        color: #cbd5e1;
        border-color: #334155;
    }
    .ka-dark-theme tr:hover td {
        background: #334155;
    }

    /* Buttons */
    .ka-dark-theme .button {
        background: #334155;
        border-color: #475569;
        color: #f1f5f9;
        transition: all 0.2s;
    }
    .ka-dark-theme .button:hover {
        background: #475569;
        border-color: #6366f1;
        color: #fff;
    }
    .ka-dark-theme .button-primary,
    .ka-dark-theme .ka-btn-primary {
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        border: none;
        color: #fff;
        transition: transform 0.2s, box-shadow 0.2s, background 0.2s;
    }
    .ka-dark-theme .button-primary:hover,
    .ka-dark-theme .ka-btn-primary:hover {
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        color: #fff;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
    }

    /* Submit area */
    .ka-dark-theme .ka-submit,
    .ka-dark-theme .ka-card-footer {
        background: #0f172a;
        border-top-color: #334155;
    }

    /* Status badges */
    .ka-dark-theme .ka-status-enabled {
        background: rgba(34, 197, 94, 0.2);
        color: #4ade80;
    }
    .ka-dark-theme .ka-status-disabled {
        background: rgba(239, 68, 68, 0.2);
        color: #f87171;
    }

    /* Text colors */
    .ka-dark-theme h1, .ka-dark-theme h2, .ka-dark-theme h3, .ka-dark-theme h4 {
        color: #f1f5f9;
    }
    .ka-dark-theme p, .ka-dark-theme span, .ka-dark-theme div {
        color: #cbd5e1;
    }
    .ka-dark-theme a {
        color: #818cf8;
    }
    .ka-dark-theme a:hover {
        color: #a5b4fc;
    }

    .ka-dark-theme a.ka-hf-btn-primary {
        color: #fff;
    } 

    .ka-dark-theme a.ka-wb-dropdown-item{
        color: var(--ka-wb-text);
    }

    .ka-dark-theme a.ka-wb-dropdown-item.is-danger {
    color: #ff3b30;
}

    /* Stat cards */
    .ka-dark-theme .ka-stat-value,
    .ka-dark-theme .ka-stat-number {
        color: #f1f5f9;
    }
    .ka-dark-theme .ka-stat-label {
        color: #94a3b8;
    }
    .ka-dark-theme .ka-stat-icon {
        background: rgba(99, 102, 241, 0.2);
    }

    /* Code/Pre */
    .ka-dark-theme code,
    .ka-dark-theme pre {
        background: #0f172a;
        color: #e2e8f0;
        border-color: #334155;
    }

    /* Notices */
    .ka-dark-theme .ka-notice,
    .ka-dark-theme .notice {
        background: #1e293b;
        border-color: #475569;
        color: #cbd5e1;
    }

    /* Specific page adjustments */
    .ka-dark-theme .ka-log-entry {
        background: #0f172a;
        border-color: #334155;
    }

    /* WooCommerce Builder specific */
    .ka-dark-theme .ka-woo-grid .ka-woo-card {
        background: #1e293b;
        border-color: #334155;
    }
    .ka-dark-theme .ka-woo-card:hover {
        border-color: #6366f1;
    }
    .ka-dark-theme .ka-woo-card-title {
        color: #f1f5f9;
    }
    .ka-dark-theme .ka-woo-card-desc {
        color: #94a3b8;
    }

    /* Filter bar */
    .ka-dark-theme .ka-filter-bar {
        background: #1e293b;
        border-color: #334155;
    }

    /* Scrollbar */
    .ka-dark-theme ::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }
    .ka-dark-theme ::-webkit-scrollbar-track {
        background: #1e293b;
    }
    .ka-dark-theme ::-webkit-scrollbar-thumb {
        background: #475569;
        border-radius: 4px;
    }
    .ka-dark-theme ::-webkit-scrollbar-thumb:hover {
        background: #64748b;
    }
    </style>
    <?php
}

/**
 * Output the dark theme JavaScript
 */
function ka_render_dark_theme_script() {
    ?>
    <script>
    (function($) {
        'use strict';

        // AJAX settings
        const ajaxUrl = '<?php echo esc_url(admin_url('admin-ajax.php')); ?>';
        const nonce = '<?php echo esc_js(wp_create_nonce('king_addons_dashboard_ui')); ?>';

        // Save UI setting via AJAX
        function saveUISetting(key, value) {
            $.post(ajaxUrl, {
                action: 'king_addons_save_dashboard_ui',
                nonce: nonce,
                key: key,
                value: value
            });
        }

        const themeMql = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
        let themeMode = ($('#ka-v3-theme-segment').attr('data-active') || 'dark').toString();
        let themeMqlHandler = null;

        function applyThemeClasses(isDark) {
            if (isDark) {
                $('body').addClass('ka-dark-theme');
            } else {
                $('body').removeClass('ka-dark-theme');
            }

            $('body').toggleClass('ka-v3-dark', isDark);
            document.documentElement.classList.toggle('ka-v3-dark', isDark);
        }

        function updateSegment(mode) {
            var $segment = $('#ka-v3-theme-segment');
            if (!$segment.length) {
                return;
            }
            $segment.attr('data-active', mode);
            $segment.find('.ka-v3-segmented-btn').each(function() {
                var theme = $(this).data('theme');
                $(this).attr('aria-pressed', theme === mode ? 'true' : 'false');
            });
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
                applyThemeClasses(!!(themeMql && themeMql.matches));
                themeMqlHandler = function(e) {
                    if (themeMode !== 'auto') {
                        return;
                    }
                    applyThemeClasses(!!e.matches);
                };
                if (themeMql) {
                    if (themeMql.addEventListener) {
                        themeMql.addEventListener('change', themeMqlHandler);
                    } else if (themeMql.addListener) {
                        themeMql.addListener(themeMqlHandler);
                    }
                }
            } else {
                applyThemeClasses(mode === 'dark');
            }

            if (save) {
                saveUISetting('theme_mode', mode);
            }
        }

        // Theme segmented control click
        $(document).on('click', '#ka-v3-theme-segment .ka-v3-segmented-btn', function(e) {
            e.preventDefault();
            setThemeMode(($(this).data('theme') || 'dark').toString(), true);
        });

        // Apply current mode (incl. Auto listener)
        setThemeMode(themeMode, false);
    })(jQuery);
    </script>
    <?php
}

/**
 * Output inline script to apply dark theme immediately (before page renders)
 */
function ka_render_dark_theme_init() {
    global $ka_theme_mode;
    $mode = esc_js($ka_theme_mode);
    echo '<script>(function(){var mode="' . $mode . '";var mql=window.matchMedia?window.matchMedia("(prefers-color-scheme: dark)"):null;function apply(isDark){document.documentElement.classList.toggle("ka-v3-dark",!!isDark);if(document.body){document.body.classList.toggle("ka-v3-dark",!!isDark);document.body.classList.toggle("ka-dark-theme",!!isDark);}}function ensureBody(fn){if(document.body){fn();return;}document.addEventListener("DOMContentLoaded",fn,{once:true});}if(mode==="auto"){var isDark=!!(mql&&mql.matches);apply(isDark);ensureBody(function(){apply(isDark);});}else{var dark=(mode==="dark");apply(dark);ensureBody(function(){apply(dark);});}})();</script>';
}
