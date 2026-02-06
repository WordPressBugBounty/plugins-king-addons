<?php
/**
 * Maintenance Mode admin page.
 *
 * @package King_Addons
 */

if (!defined('ABSPATH')) {
    exit;
}

$theme_mode = get_user_meta(get_current_user_id(), 'king_addons_theme_mode', true);
$allowed_theme_modes = ['dark', 'light', 'auto'];
if (!in_array($theme_mode, $allowed_theme_modes, true)) {
    $theme_mode = 'dark';
}

$tabs = [
    'dashboard' => [
        'label' => esc_html__('Dashboard', 'king-addons'),
        'icon' => 'dashicons-chart-line',
    ],
    'mode' => [
        'label' => esc_html__('Mode Settings', 'king-addons'),
        'icon' => 'dashicons-admin-generic',
    ],
    'page-builder' => [
        'label' => esc_html__('Page Builder', 'king-addons'),
        'icon' => 'dashicons-welcome-widgets-menus',
    ],
    'rules' => [
        'label' => esc_html__('Rules', 'king-addons'),
        'icon' => 'dashicons-filter',
    ],
    'schedule' => [
        'label' => esc_html__('Schedule', 'king-addons'),
        'icon' => 'dashicons-clock',
    ],
    'analytics' => [
        'label' => esc_html__('Analytics', 'king-addons'),
        'icon' => 'dashicons-chart-area',
    ],
    'import-export' => [
        'label' => esc_html__('Import Export', 'king-addons'),
        'icon' => 'dashicons-upload',
    ],
];

$base_url = admin_url('admin.php?page=king-addons-maintenance-mode');
$current_view = array_key_exists($view, $tabs) ? $view : 'dashboard';

$message_code = isset($_GET['message']) ? sanitize_key($_GET['message']) : '';
$messages = [
    'saved' => esc_html__('Settings saved.', 'king-addons'),
    'imported' => esc_html__('Settings imported.', 'king-addons'),
    'analytics_reset' => esc_html__('Analytics reset.', 'king-addons'),
    'token_generated' => esc_html__('Access token generated.', 'king-addons'),
    'token_revoked' => esc_html__('Access token revoked.', 'king-addons'),
    'password_revoked' => esc_html__('Password protection revoked.', 'king-addons'),
    'error_import' => esc_html__('Import failed. Please check the JSON file.', 'king-addons'),
];
$has_message = $message_code !== '' && isset($messages[$message_code]);
$is_token_message = in_array($message_code, ['token_generated', 'token_revoked'], true);
$is_error_message = strpos($message_code, 'error_') === 0;

// Token/password actions are displayed inline in the Mode view (Private Access card).
if ($current_view === 'mode' && ($is_token_message || $message_code === 'password_revoked')) {
    $has_message = false;
}

$enabled = !empty($settings['enabled']);
$mode = $settings['mode'] ?? 'coming_soon';
?>

<script>
document.body.classList.add('ka-admin-v3');
(function() {
    const mode = '<?php echo esc_js($theme_mode); ?>';
    const mql = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
    const isDark = mode === 'auto' ? !!(mql && mql.matches) : mode === 'dark';
    document.documentElement.classList.toggle('ka-v3-dark', isDark);
    document.body.classList.toggle('ka-v3-dark', isDark);
})();
</script>

<div class="ka-admin-wrap kng-maintenance-admin">
    <div class="ka-admin-header">
        <div class="ka-admin-header-left">
            <div class="ka-admin-header-icon blue">
                <span class="dashicons dashicons-hammer"></span>
            </div>
            <div>
                <h1 class="ka-admin-title"><?php esc_html_e('Maintenance Mode', 'king-addons'); ?></h1>
                <p class="ka-admin-subtitle"><?php esc_html_e('Control access, coming soon pages, and maintenance responses with premium polish.', 'king-addons'); ?></p>
            </div>
        </div>
        <div class="ka-admin-header-actions">
            <span class="ka-status <?php echo $enabled ? 'ka-status-enabled' : 'ka-status-disabled'; ?>">
                <span class="ka-status-dot"></span>
                <?php echo $enabled ? esc_html__('Enabled', 'king-addons') : esc_html__('Disabled', 'king-addons'); ?>
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

    <div class="ka-tabs kng-maintenance-tabs">
        <?php foreach ($tabs as $key => $tab) : ?>
            <a class="ka-tab <?php echo $current_view === $key ? 'active' : ''; ?>" href="<?php echo esc_url(add_query_arg(['view' => $key], $base_url)); ?>">
                <span class="dashicons <?php echo esc_attr($tab['icon']); ?>"></span>
                <?php echo esc_html($tab['label']); ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if ($has_message && !$is_token_message) : ?>
        <div class="ka-alert <?php echo $is_error_message ? 'ka-alert-error' : ''; ?>">
            <span class="dashicons <?php echo $is_error_message ? 'dashicons-warning' : 'dashicons-yes'; ?>"></span>
            <?php echo esc_html($messages[$message_code]); ?>
        </div>
    <?php endif; ?>

    <?php
    switch ($current_view) {
        case 'mode':
            include __DIR__ . '/view-mode.php';
            break;
        case 'page-builder':
            include __DIR__ . '/view-page-builder.php';
            break;
        case 'rules':
            include __DIR__ . '/view-rules.php';
            break;
        case 'schedule':
            include __DIR__ . '/view-schedule.php';
            break;
        case 'analytics':
            include __DIR__ . '/view-analytics.php';
            break;
        case 'import-export':
            include __DIR__ . '/view-import-export.php';
            break;
        default:
            include __DIR__ . '/view-dashboard.php';
            break;
    }
    ?>
</div>
