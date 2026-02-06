<?php
/**
 * Smart Links admin page template.
 *
 * @package King_Addons
 */

use King_Addons\Smart_Links\Smart_Links;
use King_Addons\Smart_Links\Smart_Links_Settings;
use King_Addons\Smart_Links\Smart_Links_Service;

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
    'links' => [
        'label' => esc_html__('Links', 'king-addons'),
        'icon' => 'dashicons-admin-links',
    ],
    'add' => [
        'label' => esc_html__('Add New', 'king-addons'),
        'icon' => 'dashicons-plus',
    ],
    'analytics' => [
        'label' => esc_html__('Analytics', 'king-addons'),
        'icon' => 'dashicons-chart-bar',
    ],
    'settings' => [
        'label' => esc_html__('Settings', 'king-addons'),
        'icon' => 'dashicons-admin-generic',
    ],
    'import-export' => [
        'label' => esc_html__('Import Export', 'king-addons'),
        'icon' => 'dashicons-upload',
    ],
];

$base_url = admin_url('admin.php?page=king-addons-smart-links');
$current_view = array_key_exists($view, $tabs) ? $view : 'dashboard';

$message_code = isset($_GET['message']) ? sanitize_key($_GET['message']) : '';
$messages = [
    'created' => esc_html__('Link created successfully.', 'king-addons'),
    'updated' => esc_html__('Link updated successfully.', 'king-addons'),
    'deleted' => esc_html__('Link deleted.', 'king-addons'),
    'duplicated' => esc_html__('Link duplicated.', 'king-addons'),
    'imported' => esc_html__('Links imported successfully.', 'king-addons'),
    'error_invalid_url' => esc_html__('Destination URL is invalid.', 'king-addons'),
    'error_save' => esc_html__('Unable to save the link.', 'king-addons'),
    'error_not_found' => esc_html__('Link not found.', 'king-addons'),
    'error_bulk' => esc_html__('Select at least one link and an action.', 'king-addons'),
    'error_import' => esc_html__('Import failed. Check your CSV file.', 'king-addons'),
];
$has_message = $message_code !== '' && isset($messages[$message_code]);
$is_error_message = strpos($message_code, 'error_') === 0;

$tracking_enabled = !empty($settings['tracking_enabled']);
$tracking_status = $tracking_enabled ? esc_html__('Tracking On', 'king-addons') : esc_html__('Tracking Off', 'king-addons');
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

<div class="ka-admin-wrap">
    <div class="ka-admin-header">
        <div class="ka-admin-header-left">
            <div class="ka-admin-header-icon green">
                <span class="dashicons dashicons-admin-links"></span>
            </div>
            <div>
                <h1 class="ka-admin-title"><?php esc_html_e('Smart Links', 'king-addons'); ?></h1>
                <p class="ka-admin-subtitle"><?php esc_html_e('Shorten, track, and optimize your campaign links.', 'king-addons'); ?></p>
            </div>
        </div>
        <div class="ka-admin-header-actions">
            <span class="ka-status <?php echo $tracking_enabled ? 'ka-status-enabled' : 'ka-status-disabled'; ?>">
                <span class="ka-status-dot"></span>
                <?php echo esc_html($tracking_status); ?>
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
            <a href="<?php echo esc_url(add_query_arg(['view' => 'add'], $base_url)); ?>" class="ka-btn ka-btn-primary">
                <span class="dashicons dashicons-plus"></span>
                <?php esc_html_e('Add New', 'king-addons'); ?>
            </a>
        </div>
    </div>

    <div class="ka-tabs ka-smart-links-tabs">
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
        case 'links':
            include __DIR__ . '/view-links.php';
            break;
        case 'add':
            include __DIR__ . '/view-edit.php';
            break;
        case 'analytics':
            include __DIR__ . '/view-analytics.php';
            break;
        case 'settings':
            include __DIR__ . '/view-settings.php';
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
