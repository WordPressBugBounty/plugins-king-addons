<?php
/**
 * Site Preloader Admin Page Template.
 *
 * Premium style inspired admin UI for Site Preloader settings.
 *
 * @package King_Addons
 * @since 1.0.0
 *
 * @var array  $settings    Current settings.
 * @var bool   $is_pro      Whether Pro version is active.
 * @var array  $presets     Available presets.
 * @var array  $rules       Current display rules.
 * @var string $current_tab Current active tab.
 */

if (!defined('ABSPATH')) {
    exit;
}

$tabs = [
    'dashboard' => __('Dashboard', 'king-addons'),
    'settings' => __('Settings', 'king-addons'),
    'templates' => __('Templates', 'king-addons'),
    'rules' => __('Rules', 'king-addons'),
    'advanced' => __('Advanced', 'king-addons'),
    'import-export' => __('Import / Export', 'king-addons'),
];

// Show success/error messages
$updated = isset($_GET['updated']) && $_GET['updated'] === 'true';
$imported = isset($_GET['imported']) && $_GET['imported'] === 'true';
$reset = isset($_GET['reset']) && $_GET['reset'] === 'true';
$error = isset($_GET['error']) ? sanitize_key($_GET['error']) : '';

// Theme mode is per-user
$theme_mode = get_user_meta(get_current_user_id(), 'king_addons_theme_mode', true);
$allowed_theme_modes = ['dark', 'light', 'auto'];
if (!in_array($theme_mode, $allowed_theme_modes, true)) {
    $theme_mode = 'dark';
}
?>

<script>
(function() {
    const mode = '<?php echo esc_js($theme_mode); ?>';
    const mql = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
    const isDark = mode === 'auto' ? !!(mql && mql.matches) : mode === 'dark';

    function applyThemeClasses(nextIsDark) {
        document.documentElement.classList.toggle('ka-v3-dark', nextIsDark);
        if (document.body) {
            document.body.classList.add('ka-admin-v3');
            document.body.classList.toggle('ka-v3-dark', nextIsDark);
        }
    }

    function ensureBodyReady(fn) {
        if (document.body) {
            fn();
            return;
        }
        document.addEventListener('DOMContentLoaded', fn, { once: true });
    }

    // Apply to <html> immediately to avoid flash; apply to body when ready.
    document.documentElement.classList.toggle('ka-v3-dark', isDark);
    ensureBodyReady(function() {
        applyThemeClasses(isDark);
    });
})();

// Theme segmented control (dashboard-style)
(function() {
    const ajaxUrl = '<?php echo esc_url(admin_url('admin-ajax.php')); ?>';
    const nonce = '<?php echo esc_js(wp_create_nonce('king_addons_dashboard_ui')); ?>';
    const mql = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
    let mode = 'dark';
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
        const segment = document.getElementById('ka-v3-theme-segment');
        if (!segment) {
            return;
        }
        segment.setAttribute('data-active', activeMode);
        segment.querySelectorAll('.ka-v3-segmented-btn').forEach((btn) => {
            const theme = btn.getAttribute('data-theme');
            btn.setAttribute('aria-pressed', theme === activeMode ? 'true' : 'false');
        });
    }

    function ensureBodyReady(fn) {
        if (document.body) {
            fn();
            return;
        }
        document.addEventListener('DOMContentLoaded', fn, { once: true });
    }

    function applyTheme(isDark) {
        document.documentElement.classList.toggle('ka-v3-dark', isDark);
        if (document.body) {
            document.body.classList.add('ka-admin-v3');
            document.body.classList.toggle('ka-v3-dark', isDark);
        }
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
            ensureBodyReady(function() {
                applyTheme(!!(mql && mql.matches));
            });
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
            ensureBodyReady(function() {
                applyTheme(nextMode === 'dark');
            });
        }

        if (save) {
            saveUISetting('theme_mode', nextMode);
        }
    }

    window.kaV3ToggleDark = function() {
        const isDark = document.body.classList.contains('ka-v3-dark');
        setThemeMode(isDark ? 'light' : 'dark', true);
    };

    function initThemeSegment() {
        const segment = document.getElementById('ka-v3-theme-segment');
        if (!segment) {
            return;
        }

        mode = (segment.getAttribute('data-active') || 'dark').toString();

        // Use event delegation to avoid edge-cases where the element gets re-rendered.
        document.addEventListener('click', function(e) {
            const btn = e.target && e.target.closest ? e.target.closest('#ka-v3-theme-segment .ka-v3-segmented-btn') : null;
            if (!btn) {
                return;
            }
            e.preventDefault();
            setThemeMode((btn.getAttribute('data-theme') || 'dark').toString(), true);
        });

        setThemeMode(mode, false);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initThemeSegment, { once: true });
    } else {
        initThemeSegment();
    }
})();

// Tab switching
function kaPreloaderSwitchTab(tabId) {
    // Update URL
    const url = new URL(window.location.href);
    url.searchParams.set('tab', tabId);
    window.history.replaceState({}, '', url);

    // Switch tabs
    document.querySelectorAll('.ka-tab').forEach(tab => {
        tab.classList.toggle('active', tab.dataset.tab === tabId);
    });
    document.querySelectorAll('.ka-tab-content').forEach(content => {
        content.classList.toggle('active', content.dataset.tab === tabId);
    });
}

// Preview functions
function kaPreloaderOpenPreview() {
    document.getElementById('ka-preloader-preview-modal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
    kaPreloaderUpdatePreview();
}

function kaPreloaderClosePreview() {
    document.getElementById('ka-preloader-preview-modal').style.display = 'none';
    document.body.style.overflow = '';
}

function kaPreloaderUpdatePreview() {
    const container = document.getElementById('ka-preloader-preview-container');
    if (!container) {
        return;
    }

    const form = document.getElementById('ka-preloader-settings-form');

    function getVal(name, fallback) {
        if (!form) {
            return fallback;
        }
        const el = form.querySelector('[name="' + name + '"]');
        if (!el) {
            return fallback;
        }
        if (el.type === 'checkbox') {
            return el.checked ? '1' : '';
        }
        return (el.value ?? fallback);
    }

    // Try to re-use existing preview HTML so animations always match PHP output.
    const liveWrapper = document.getElementById('ka-live-preview-wrapper');
    const dashWrapper = document.querySelector('#ka-dashboard-preview .ka-preloader-preview-wrapper');
    const activeTemplateCard = document.querySelector('.ka-preloader-template-card.active .ka-preloader-template-card__animation');
    const source = liveWrapper || dashWrapper || activeTemplateCard;

    const accent = getVal('accent_color', '#0071e3') || '#0071e3';
    const textColor = getVal('text_color', '#1d1d1f') || '#1d1d1f';
    const spinnerSize = parseInt(getVal('spinner_size', '48'), 10) || 48;
    const bgType = getVal('bg_type', 'solid') || 'solid';
    const bgColor = getVal('bg_color', '#ffffff') || '#ffffff';
    const bgStart = getVal('bg_gradient_start', '#ffffff') || '#ffffff';
    const bgEnd = getVal('bg_gradient_end', '#f5f5f7') || '#f5f5f7';
    const bgImage = getVal('bg_image', '') || '';
    const overlayOpacity = Math.max(0, Math.min(1, parseFloat(getVal('overlay_opacity', '1')) || 1));

    let overlayStyle = '';
    if (bgType === 'gradient') {
        overlayStyle = 'background: linear-gradient(135deg, ' + bgStart + ' 0%, ' + bgEnd + ' 100%);';
    } else if (bgType === 'image' && bgImage) {
        overlayStyle = 'background-image: url(' + bgImage + '); background-size: cover; background-position: center;';
    } else {
        overlayStyle = 'background: ' + bgColor + ';';
    }
    overlayStyle += ' opacity: ' + overlayOpacity + ';';

    const contentHtml = source ? source.innerHTML : '';

    container.innerHTML = '';
    const preloader = document.createElement('div');
    preloader.className = 'kng-site-preloader';
    preloader.setAttribute('aria-label', 'Preloader Preview');
    preloader.style.cssText = '--kng-preloader-accent: ' + accent + '; --kng-preloader-text: ' + textColor + '; --kng-preloader-bg: ' + bgColor + '; --kng-preloader-size: ' + spinnerSize + 'px;';
    preloader.innerHTML = '' +
        '<div class="kng-site-preloader__overlay" style="' + overlayStyle + '"></div>' +
        '<div class="kng-site-preloader__content">' + contentHtml + '</div>';
    container.appendChild(preloader);
}

// Template selection
function kaPreloaderSelectTemplate(templateId) {
    // Update hidden input
    const hiddenInput = document.getElementById('ka-selected-template');
    if (hiddenInput) {
        hiddenInput.value = templateId;
    }
    
    // Update active state
    document.querySelectorAll('.ka-preloader-template-card').forEach(card => {
        card.classList.toggle('active', card.dataset.template === templateId);
    });
}
</script>

<div class="ka-admin-wrap ka-preloader-admin">
    <!-- Header -->
    <div class="ka-admin-header">
        <div class="ka-admin-header-left">
            <div class="ka-admin-header-icon orange">
                <span class="dashicons dashicons-visibility"></span>
            </div>
            <div>
                <h1 class="ka-admin-title"><?php esc_html_e('Site Preloader', 'king-addons'); ?></h1>
                <p class="ka-admin-subtitle"><?php esc_html_e('Create beautiful loading animations for your website', 'king-addons'); ?></p>
            </div>
        </div>
        <div class="ka-admin-header-actions">
            <span class="ka-status <?php echo !empty($settings['enabled']) ? 'ka-status-enabled' : 'ka-status-disabled'; ?>">
                <span class="ka-status-dot"></span>
                <?php echo !empty($settings['enabled']) ? esc_html__('Enabled', 'king-addons') : esc_html__('Disabled', 'king-addons'); ?>
            </span>
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
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if ($updated): ?>
    <div class="ka-notice ka-notice-success">
        <span class="dashicons dashicons-yes-alt"></span>
        <?php esc_html_e('Settings saved successfully!', 'king-addons'); ?>
    </div>
    <?php endif; ?>

    <?php if ($imported): ?>
    <div class="ka-notice ka-notice-success">
        <span class="dashicons dashicons-yes-alt"></span>
        <?php esc_html_e('Settings imported successfully!', 'king-addons'); ?>
    </div>
    <?php endif; ?>

    <?php if ($reset): ?>
    <div class="ka-notice ka-notice-success">
        <span class="dashicons dashicons-yes-alt"></span>
        <?php esc_html_e('Settings reset to defaults!', 'king-addons'); ?>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="ka-notice ka-notice-error">
        <span class="dashicons dashicons-warning"></span>
        <?php
        switch ($error) {
            case 'no-file':
                esc_html_e('Please select a file to import.', 'king-addons');
                break;
            case 'invalid-json':
                esc_html_e('Invalid JSON file. Please check the file and try again.', 'king-addons');
                break;
            default:
                esc_html_e('An error occurred. Please try again.', 'king-addons');
        }
        ?>
    </div>
    <?php endif; ?>

    <!-- Tabs -->
    <div class="ka-tabs">
        <?php foreach ($tabs as $tab_id => $tab_label): ?>
        <button type="button" 
                class="ka-tab <?php echo $current_tab === $tab_id ? 'active' : ''; ?>" 
                data-tab="<?php echo esc_attr($tab_id); ?>"
                onclick="kaPreloaderSwitchTab('<?php echo esc_attr($tab_id); ?>')">
            <?php echo esc_html($tab_label); ?>
        </button>
        <?php endforeach; ?>
    </div>

    <!-- Tab Content: Dashboard -->
    <div class="ka-tab-content <?php echo $current_tab === 'dashboard' ? 'active' : ''; ?>" data-tab="dashboard">
        <?php include __DIR__ . '/partials/tab-dashboard.php'; ?>
    </div>

    <!-- Tab Content: Settings -->
    <div class="ka-tab-content <?php echo $current_tab === 'settings' ? 'active' : ''; ?>" data-tab="settings">
        <?php include __DIR__ . '/partials/tab-settings.php'; ?>
    </div>

    <!-- Tab Content: Templates -->
    <div class="ka-tab-content <?php echo $current_tab === 'templates' ? 'active' : ''; ?>" data-tab="templates">
        <?php include __DIR__ . '/partials/tab-templates.php'; ?>
    </div>

    <!-- Tab Content: Rules -->
    <div class="ka-tab-content <?php echo $current_tab === 'rules' ? 'active' : ''; ?>" data-tab="rules">
        <?php include __DIR__ . '/partials/tab-rules.php'; ?>
    </div>

    <!-- Tab Content: Advanced -->
    <div class="ka-tab-content <?php echo $current_tab === 'advanced' ? 'active' : ''; ?>" data-tab="advanced">
        <?php include __DIR__ . '/partials/tab-advanced.php'; ?>
    </div>

    <!-- Tab Content: Import/Export -->
    <div class="ka-tab-content <?php echo $current_tab === 'import-export' ? 'active' : ''; ?>" data-tab="import-export">
        <?php include __DIR__ . '/partials/tab-import-export.php'; ?>
    </div>
</div>

<!-- Live Preview Modal -->
<div id="ka-preloader-preview-modal" class="ka-preloader-preview-modal" style="display: none;">
    <div class="ka-preloader-preview-modal__backdrop" onclick="kaPreloaderClosePreview()"></div>
    <div class="ka-preloader-preview-modal__content">
        <button type="button" class="ka-preloader-preview-modal__close" onclick="kaPreloaderClosePreview()">
            <span class="dashicons dashicons-no-alt"></span>
        </button>
        <div class="ka-preloader-preview-modal__frame">
            <div id="ka-preloader-preview-container"></div>
        </div>
        <div class="ka-preloader-preview-modal__info">
            <span class="ka-preloader-preview-modal__label"><?php esc_html_e('Preview Mode', 'king-addons'); ?></span>
            <span class="ka-preloader-preview-modal__hint"><?php esc_html_e('Click anywhere to close', 'king-addons'); ?></span>
        </div>
    </div>
</div>
