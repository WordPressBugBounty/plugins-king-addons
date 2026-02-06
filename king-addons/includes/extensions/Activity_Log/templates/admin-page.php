<?php
/**
 * Activity Log admin page.
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
        'icon' => 'dashicons-chart-area',
    ],
    'logs' => [
        'label' => esc_html__('Logs', 'king-addons'),
        'icon' => 'dashicons-clipboard',
    ],
    'alerts' => [
        'label' => esc_html__('Alerts', 'king-addons'),
        'icon' => 'dashicons-warning',
    ],
    'settings' => [
        'label' => esc_html__('Settings', 'king-addons'),
        'icon' => 'dashicons-admin-generic',
    ],
    'export' => [
        'label' => esc_html__('Export', 'king-addons'),
        'icon' => 'dashicons-download',
    ],
    'tools' => [
        'label' => esc_html__('Tools', 'king-addons'),
        'icon' => 'dashicons-admin-tools',
    ],
];

$current_page = isset($_GET['page']) ? sanitize_key($_GET['page']) : 'king-addons-activity-log';
$base_url = admin_url('admin.php?page=' . $current_page);
$current_view = array_key_exists($view, $tabs) ? $view : 'dashboard';

$can_manage = current_user_can('manage_options');
if (!$can_manage) {
    $tabs = [
        'logs' => [
            'label' => esc_html__('Logs', 'king-addons'),
            'icon' => 'dashicons-clipboard',
        ],
    ];
    $current_view = 'logs';
}

$message_code = isset($_GET['message']) ? sanitize_key($_GET['message']) : '';
$messages = [
    'saved' => esc_html__('Settings saved.', 'king-addons'),
    'alerts_saved' => esc_html__('Alert settings saved.', 'king-addons'),
    'purged' => esc_html__('Old logs purged.', 'king-addons'),
    'error_export' => esc_html__('Export failed.', 'king-addons'),
];
$has_message = $message_code !== '' && isset($messages[$message_code]);
$is_error_message = strpos($message_code, 'error_') === 0;

$logging_enabled = !empty($settings['enabled']);
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

<div class="ka-admin-wrap kng-activity-admin">
    <div class="ka-admin-header">
        <div class="ka-admin-header-left">
            <div class="ka-admin-header-icon blue">
                <span class="dashicons dashicons-shield-alt"></span>
            </div>
            <div>
                <h1 class="ka-admin-title"><?php esc_html_e('Activity Log', 'king-addons'); ?></h1>
                <p class="ka-admin-subtitle"><?php esc_html_e('Audit critical actions across your WordPress site in Premium style inspired clarity.', 'king-addons'); ?></p>
            </div>
        </div>
        <div class="ka-admin-header-actions">
            <span class="ka-status <?php echo $logging_enabled ? 'ka-status-enabled' : 'ka-status-disabled'; ?>">
                <span class="ka-status-dot"></span>
                <?php echo $logging_enabled ? esc_html__('Enabled', 'king-addons') : esc_html__('Disabled', 'king-addons'); ?>
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

    <div class="ka-tabs kng-activity-tabs">
        <?php foreach ($tabs as $key => $tab) : ?>
            <a class="ka-tab <?php echo $current_view === $key ? 'active' : ''; ?>" href="<?php echo esc_url(add_query_arg(['view' => $key], $base_url)); ?>">
                <span class="dashicons <?php echo esc_attr($tab['icon']); ?>"></span>
                <?php echo esc_html($tab['label']); ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if ($has_message) : ?>
        <div class="ka-alert <?php echo $is_error_message ? 'ka-alert-error' : ''; ?>">
            <span class="dashicons <?php echo $is_error_message ? 'dashicons-warning' : 'dashicons-yes'; ?>"></span>
            <?php echo esc_html($messages[$message_code]); ?>
        </div>
    <?php endif; ?>

    <?php
    switch ($current_view) {
        case 'logs':
            include __DIR__ . '/view-logs.php';
            break;
        case 'alerts':
            include __DIR__ . '/view-alerts.php';
            break;
        case 'settings':
            include __DIR__ . '/view-settings.php';
            break;
        case 'export':
            include __DIR__ . '/view-export.php';
            break;
        case 'tools':
            include __DIR__ . '/view-tools.php';
            break;
        default:
            include __DIR__ . '/view-dashboard.php';
            break;
    }
    ?>
</div>
