<?php
/**
 * Data Table Builder admin page.
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
    'tables' => [
        'label' => esc_html__('All Tables', 'king-addons'),
        'icon' => 'dashicons-grid-view',
    ],
    'add' => [
        'label' => esc_html__('Add New', 'king-addons'),
        'icon' => 'dashicons-plus',
    ],
    'templates' => [
        'label' => esc_html__('Templates', 'king-addons'),
        'icon' => 'dashicons-layout',
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

$base_url = admin_url('admin.php?page=king-addons-table-builder');
$current_view = array_key_exists($view, $tabs) ? $view : 'dashboard';

$message_code = isset($_GET['message']) ? sanitize_key($_GET['message']) : '';
$messages = [
    'saved' => esc_html__('Table saved successfully.', 'king-addons'),
    'deleted' => esc_html__('Table deleted.', 'king-addons'),
    'duplicated' => esc_html__('Table duplicated.', 'king-addons'),
    'imported' => esc_html__('Table imported successfully.', 'king-addons'),
    'error_save' => esc_html__('Unable to save table.', 'king-addons'),
    'error_not_found' => esc_html__('Table not found.', 'king-addons'),
    'error_import' => esc_html__('Import failed. Check your CSV file.', 'king-addons'),
];
$has_message = $message_code !== '' && isset($messages[$message_code]);
$is_error_message = strpos($message_code, 'error_') === 0;
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

<div class="ka-admin-wrap kng-table-admin">
    <div class="ka-admin-header">
        <div class="ka-admin-header-left">
            <div class="ka-admin-header-icon purple">
                <span class="dashicons dashicons-grid-view"></span>
            </div>
            <div>
                <h1 class="ka-admin-title"><?php esc_html_e('Table Builder', 'king-addons'); ?></h1>
                <p class="ka-admin-subtitle"><?php esc_html_e('Create interactive data tables with Premium style inspired styling.', 'king-addons'); ?></p>
            </div>
        </div>
        <div class="ka-admin-header-actions">
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

    <div class="ka-tabs kng-table-tabs">
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
        case 'tables':
            include __DIR__ . '/view-list.php';
            break;
        case 'add':
            include __DIR__ . '/view-editor.php';
            break;
        case 'templates':
            include __DIR__ . '/view-templates.php';
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
