<?php
/**
 * Activity Log - Main Admin Layout.
 *
 * @package King_Addons
 */

if (!defined('ABSPATH')) {
    exit;
}

$activity_log = \King_Addons\Activity_Log::instance();
$options = $activity_log->get_options();
$kpis = $activity_log->get_kpis();
$is_pro = $activity_log->is_pro();

// Get current tab
$current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'dashboard';
$tabs = [
    'dashboard' => __('Dashboard', 'king-addons'),
    'logs' => __('Logs', 'king-addons'),
    'settings' => __('Settings', 'king-addons'),
    'export' => __('Export', 'king-addons'),
];

// Get saved theme (default to dark)
$saved_theme = get_user_meta(get_current_user_id(), 'ka_activity_log_theme', true);
$is_light = ($saved_theme === 'light');
?>
<div class="ka-al-wrap">
    <!-- Header -->
    <div class="ka-al-header">
        <div class="ka-al-header-left">
            <h1 class="ka-al-title"><?php esc_html_e('Activity Log', 'king-addons'); ?></h1>
            <p class="ka-al-subtitle"><?php esc_html_e('Monitor and audit all site activities', 'king-addons'); ?></p>
        </div>
        <div class="ka-al-header-actions">
            <button type="button" class="ka-al-theme-toggle" id="ka-al-theme-toggle"
                title="<?php esc_attr_e('Toggle theme', 'king-addons'); ?>">
                <svg class="ka-al-icon-sun" xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <svg class="ka-al-icon-moon" xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="display:none;">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                </svg>
            </button>
            <?php if (!$is_pro): ?>
                <a href="https://kingaddons.com/pricing/?utm_source=kng-activity-log&utm_medium=plugin&utm_campaign=kng"
                    class="ka-al-btn ka-al-btn-primary" target="_blank">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <?php esc_html_e('Upgrade to Pro', 'king-addons'); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <nav class="ka-al-nav">
        <?php foreach ($tabs as $tab_id => $tab_label): ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=king-addons-activity-log&tab=' . $tab_id)); ?>"
                class="ka-al-nav-item <?php echo $current_tab === $tab_id ? 'active' : ''; ?>">
                <?php echo esc_html($tab_label); ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <!-- Tab Content -->
    <div class="ka-al-content">
        <?php
        switch ($current_tab) {
            case 'logs':
                require_once __DIR__ . '/tab-logs.php';
                break;
            case 'settings':
                require_once __DIR__ . '/tab-settings.php';
                break;
            case 'export':
                require_once __DIR__ . '/tab-export.php';
                break;
            default:
                require_once __DIR__ . '/tab-dashboard.php';
                break;
        }
        ?>
    </div>

    <!-- Event Drawer (for details) -->
    <div class="ka-al-drawer" id="ka-al-drawer">
        <div class="ka-al-drawer-backdrop"></div>
        <div class="ka-al-drawer-panel">
            <div class="ka-al-drawer-header">
                <h3><?php esc_html_e('Event Details', 'king-addons'); ?></h3>
                <button type="button" class="ka-al-drawer-close"
                    aria-label="<?php esc_attr_e('Close', 'king-addons'); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="ka-al-drawer-body">
                <!-- Content loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        // Theme toggle functionality
        var body = document.body;
        var toggle = document.getElementById('ka-al-theme-toggle');
        var sunIcon = toggle.querySelector('.ka-al-icon-sun');
        var moonIcon = toggle.querySelector('.ka-al-icon-moon');
        var savedTheme = '<?php echo esc_js($saved_theme); ?>';

        // Apply saved theme on load
        if (savedTheme === 'light') {
            body.classList.add('ka-al-light');
            sunIcon.style.display = 'none';
            moonIcon.style.display = 'block';
        }

        toggle.addEventListener('click', function () {
            body.classList.toggle('ka-al-light');
            var isLight = body.classList.contains('ka-al-light');

            sunIcon.style.display = isLight ? 'none' : 'block';
            moonIcon.style.display = isLight ? 'block' : 'none';

            // Save preference via AJAX
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo esc_url(admin_url('admin-ajax.php')); ?>', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.send('action=ka_al_save_theme&theme=' + (isLight ? 'light' : 'dark') + '&nonce=<?php echo wp_create_nonce('ka_al_theme'); ?>');
        });
    })();
</script>