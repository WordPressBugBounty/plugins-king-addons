<?php
/**
 * AI Settings page - V3 Premium style inspired Design.
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
    document.body.classList.add('ka-admin-v3');

    const mode = '<?php echo esc_js($theme_mode); ?>';
    const mql = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
    const isDark = mode === 'auto' ? !!(mql && mql.matches) : mode === 'dark';

    document.documentElement.classList.toggle('ka-v3-dark', isDark);
    document.body.classList.toggle('ka-v3-dark', isDark);
})();
</script>

<style>
/* AI Settings V3 */
.ka-ai-settings .form-table {
    margin: 0;
    width: 100%;
}

.ka-ai-settings .form-table th,
.ka-ai-settings .form-table td {
    padding: 18px 0;
    border-bottom: 1px solid rgba(0, 0, 0, 0.04);
    vertical-align: top;
}

body.ka-v3-dark .ka-ai-settings .form-table th,
body.ka-v3-dark .ka-ai-settings .form-table td {
    border-bottom-color: rgba(255, 255, 255, 0.04);
}

.ka-ai-settings .form-table tr:last-child th,
.ka-ai-settings .form-table tr:last-child td {
    border-bottom: none;
}

.ka-ai-settings .form-table th {
    width: 200px;
    font-size: 15px;
    font-weight: 500;
    color: #1d1d1f;
    padding-right: 20px;
}

body.ka-v3-dark .ka-ai-settings .form-table th {
    color: #f5f5f7;
}

.ka-ai-settings .form-table td {
    color: #1d1d1f;
}

body.ka-v3-dark .ka-ai-settings .form-table td {
    color: #f5f5f7;
}

.ka-ai-settings input[type="text"],
.ka-ai-settings input[type="password"],
.ka-ai-settings input[type="number"],
.ka-ai-settings textarea,
.ka-ai-settings select {
    width: 100%;
    max-width: 400px;
    padding: 12px 16px;
    border: 1px solid rgba(0, 0, 0, 0.1);
    border-radius: 12px;
    font-size: 15px;
    font-family: inherit;
    background: #fff;
    transition: border-color 0.2s, box-shadow 0.2s;
}

body.ka-v3-dark .ka-ai-settings input[type="text"],
body.ka-v3-dark .ka-ai-settings input[type="password"],
body.ka-v3-dark .ka-ai-settings input[type="number"],
body.ka-v3-dark .ka-ai-settings textarea,
body.ka-v3-dark .ka-ai-settings select {
    background: #2c2c2e;
    border-color: rgba(255, 255, 255, 0.1);
    color: #f5f5f7;
}

.ka-ai-settings input:focus,
.ka-ai-settings textarea:focus,
.ka-ai-settings select:focus {
    outline: none;
    border-color: #8b5cf6;
    box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.1);
}

body.ka-v3-dark .ka-ai-settings input:focus,
body.ka-v3-dark .ka-ai-settings textarea:focus,
body.ka-v3-dark .ka-ai-settings select:focus {
    border-color: #a78bfa;
    box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.2);
}

.ka-ai-settings .description {
    font-size: 13px;
    color: #86868b;
    margin-top: 8px;
    line-height: 1.5;
}

.ka-ai-settings select {
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3e%3cpath fill='%2386868b' d='M6 8L0 0h12z'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 16px center;
    background-size: 10px 6px;
    padding-right: 44px;
}

/* Section titles */
.ka-ai-settings .ka-card-body h2 {
    font-size: 17px;
    font-weight: 600;
    color: #1d1d1f;
    margin: 24px 0 8px 0;
    padding: 24px 0 0 0;
    border-top: 1px solid rgba(0, 0, 0, 0.04);
}

body.ka-v3-dark .ka-ai-settings .ka-card-body h2 {
    color: #f5f5f7;
    border-top-color: rgba(255, 255, 255, 0.04);
}

.ka-ai-settings .ka-card-body h2:first-of-type {
    margin-top: 0;
    padding-top: 0;
    border-top: none;
}

.ka-ai-settings .ka-card-body h2 + p {
    color: #86868b;
    font-size: 14px;
    margin: 0 0 16px 0;
}

/* Button styles */
.ka-ai-settings .button {
    padding: 10px 20px;
    border-radius: 980px;
    font-size: 14px;
    font-weight: 400;
    background: rgba(0, 0, 0, 0.06);
    border: none;
    color: #1d1d1f;
    cursor: pointer;
    transition: all 0.2s;
}

body.ka-v3-dark .ka-ai-settings .button {
    background: rgba(255, 255, 255, 0.1);
    color: #f5f5f7;
}

.ka-ai-settings .button:hover {
    background: rgba(0, 0, 0, 0.1);
}

body.ka-v3-dark .ka-ai-settings .button:hover {
    background: rgba(255, 255, 255, 0.15);
}
</style>

<div class="ka-admin-wrap ka-ai-settings">
    <?php settings_errors('king_addons_ai'); ?>
    <!-- Header -->
    <div class="ka-admin-header">
        <div class="ka-admin-header-left">
            <div class="ka-admin-header-icon purple">
                <span class="dashicons dashicons-admin-generic"></span>
            </div>
            <div>
                <h1 class="ka-admin-title"><?php esc_html_e('AI Settings', 'king-addons'); ?></h1>
                <p class="ka-admin-subtitle"><?php esc_html_e('Configure OpenAI integration and AI features for Elementor editor', 'king-addons'); ?></p>
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
            <a href="https://platform.openai.com/usage" target="_blank" class="ka-btn ka-btn-secondary">
                <span class="dashicons dashicons-external"></span>
                <?php esc_html_e('OpenAI Dashboard', 'king-addons'); ?>
            </a>
        </div>
    </div>

    <!-- Info Card -->
    <div class="ka-card">
        <div class="ka-card-header">
            <span class="dashicons dashicons-info" style="color: #0071e3;"></span>
            <h3><?php esc_html_e('How to use AI features', 'king-addons'); ?></h3>
        </div>
        <div class="ka-card-body">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 24px;">
                <div>
                    <h4 style="margin: 0 0 8px; font-size: 15px; font-weight: 600; color: inherit;">1. <?php esc_html_e('Get API Key', 'king-addons'); ?></h4>
                    <p class="ka-row-desc" style="margin: 0;"><?php esc_html_e('Create an account at OpenAI and generate an API key from the dashboard.', 'king-addons'); ?></p>
                </div>
                <div>
                    <h4 style="margin: 0 0 8px; font-size: 15px; font-weight: 600; color: inherit;">2. <?php esc_html_e('Enter Key Below', 'king-addons'); ?></h4>
                    <p class="ka-row-desc" style="margin: 0;"><?php esc_html_e('Paste your API key in the field below and select your preferred model.', 'king-addons'); ?></p>
                </div>
                <div>
                    <h4 style="margin: 0 0 8px; font-size: 15px; font-weight: 600; color: inherit;">3. <?php esc_html_e('Use', 'king-addons'); ?></h4>
                    <p class="ka-row-desc" style="margin: 0;"><?php esc_html_e('AI features will be available in the Elementor editor, widgets, and other supported areas.', 'king-addons'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Settings Form -->
    <div class="ka-card">
        <div class="ka-card-header">
            <span class="dashicons dashicons-admin-settings" style="color: #8b5cf6;"></span>
            <h2><?php esc_html_e('AI Configuration', 'king-addons'); ?></h2>
        </div>
        <div class="ka-card-body">
            <form method="post" action="options.php">
                <?php
                settings_fields('king_addons_ai');
                do_settings_sections('king-addons-ai-settings');
                ?>
                
                <div style="margin-top: 24px; padding-top: 24px; border-top: 1px solid rgba(0,0,0,0.04);">
                    <button type="submit" class="ka-btn ka-btn-primary">
                        <?php esc_html_e('Save Settings', 'king-addons'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
// Dashboard-style segmented theme control
(function() {
    const segment = document.getElementById('ka-v3-theme-segment');
    if (!segment) {
        return;
    }

    const ajaxUrl = '<?php echo esc_url(admin_url('admin-ajax.php')); ?>';
    const nonce = '<?php echo esc_js(wp_create_nonce('king_addons_dashboard_ui')); ?>';
    const buttons = segment.querySelectorAll('.ka-v3-segmented-btn');

    const mql = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
    let mode = (segment.getAttribute('data-active') || 'dark').toString();
    let mqlHandler = null;

    function setPressedState(activeMode) {
        segment.setAttribute('data-active', activeMode);
        buttons.forEach((btn) => {
            const theme = btn.getAttribute('data-theme');
            btn.setAttribute('aria-pressed', theme === activeMode ? 'true' : 'false');
        });
    }

    function saveUISetting(key, value) {
        try {
            const body = new URLSearchParams();
            body.set('action', 'king_addons_save_dashboard_ui');
            body.set('nonce', nonce);
            body.set('key', key);
            body.set('value', value);

            fetch(ajaxUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                body: body.toString(),
                credentials: 'same-origin'
            });
        } catch (e) {}
    }

    function applyTheme(isDark) {
        document.body.classList.toggle('ka-v3-dark', isDark);
        document.documentElement.classList.toggle('ka-v3-dark', isDark);
    }

    function setThemeMode(nextMode, save) {
        mode = nextMode;
        setPressedState(nextMode);

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

    segment.addEventListener('click', (e) => {
        const btn = e.target && e.target.closest ? e.target.closest('.ka-v3-segmented-btn') : null;
        if (!btn) {
            return;
        }
        e.preventDefault();
        const theme = (btn.getAttribute('data-theme') || 'dark').toString();
        setThemeMode(theme, true);
    });

    // Ensure mode applies for Auto (listener + system state)
    setThemeMode(mode, false);
})();
</script>
