<?php

namespace King_Addons\Widgets\Login_Register_Form;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Include Security Manager
require_once KING_ADDONS_PATH . 'includes/widgets/Login_Register_Form/Security_Manager.php';

/**
 * Security Dashboard for Login Register Form widget
 * Provides administrative interface for monitoring security events
 */
class Security_Dashboard
{
    /**
     * Initialize the security dashboard
     */
    public static function init()
    {
        // Add admin menu
        add_action('admin_menu', [__CLASS__, 'add_admin_menu'], 20);
        
        // Add security logs capability check
        add_action('admin_init', [__CLASS__, 'check_capabilities']);
        
        // Add AJAX handlers for dashboard
        add_action('wp_ajax_king_addons_clear_security_logs', [__CLASS__, 'clear_security_logs']);
        add_action('wp_ajax_king_addons_unblock_ip', [__CLASS__, 'unblock_ip']);
        add_action('wp_ajax_king_addons_export_security_report', [__CLASS__, 'export_security_report']);
    }

    /**
     * Add admin menu for security dashboard
     */
    public static function add_admin_menu()
    {
        add_submenu_page(
            'king-addons',
            esc_html__('Login Security', 'king-addons'),
            esc_html__('Login Security', 'king-addons'),
            'manage_options',
            'king-addons-login-security',
            [__CLASS__, 'render_dashboard']
        );
    }

    /**
     * Check if user has capabilities to view security dashboard
     */
    public static function check_capabilities()
    {
        if (isset($_GET['page']) && $_GET['page'] === 'king-addons-login-security') {
            if (!current_user_can('manage_options')) {
                wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'king-addons'));
            }
        }
    }

    /**
     * Render the security dashboard - V3 Premium style inspired Design
     */
    public static function render_dashboard()
    {
        // Get security statistics
        $stats = self::get_security_statistics();
        $blocked_ips = self::get_blocked_ips();
        $recent_attempts = self::get_recent_failed_attempts();

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
            document.body && document.body.classList.add('ka-admin-v3');
            const mode = '<?php echo esc_js($theme_mode); ?>';
            const mql = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
            const isDark = mode === 'auto' ? !!(mql && mql.matches) : mode === 'dark';
            document.documentElement.classList.toggle('ka-v3-dark', isDark);
            document.body && document.body.classList.toggle('ka-v3-dark', isDark);
        })();
        </script>

        <style>
        /* Security Dashboard V3 - Additional styles */
        .ka-security-v3 .ka-features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .ka-security-v3 .ka-feature-card {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            text-align: center;
            border: 1px solid rgba(0, 0, 0, 0.04);
            transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        
        body.ka-v3-dark .ka-security-v3 .ka-feature-card {
            background: #1c1c1e;
            border-color: rgba(255, 255, 255, 0.06);
        }
        
        .ka-security-v3 .ka-feature-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.08);
        }
        
        body.ka-v3-dark .ka-security-v3 .ka-feature-card:hover {
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3);
        }
        
        .ka-security-v3 .ka-feature-card .dashicons {
            font-size: 32px;
            width: 32px;
            height: 32px;
            color: #ef4444;
            margin-bottom: 12px;
        }
        
        .ka-security-v3 .ka-feature-card h4 {
            margin: 0 0 6px;
            font-size: 15px;
            font-weight: 600;
            color: #1d1d1f;
        }
        
        body.ka-v3-dark .ka-security-v3 .ka-feature-card h4,
        body.ka-v3-dark .ka-security-v3 .ka-stat-card h3 {
            color: #f5f5f7;
        }
        
        .ka-security-v3 .ka-feature-card p {
            margin: 0;
            font-size: 13px;
            color: #86868b;
        }
        
        /* Stats override for security color */
        .ka-security-v3 .ka-stat-card .ka-stat-number {
            color: #ef4444;
        }
        
        /* Actions grid */
        .ka-security-v3 .ka-actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .ka-security-v3 .ka-action-card {
            background: rgba(0, 0, 0, 0.02);
            border: 1px solid rgba(0, 0, 0, 0.04);
            border-radius: 16px;
            padding: 24px;
            text-align: center;
            transition: all 0.3s;
        }
        
        body.ka-v3-dark .ka-security-v3 .ka-action-card {
            background: rgba(255, 255, 255, 0.04);
            border-color: rgba(255, 255, 255, 0.06);
        }
        
        .ka-security-v3 .ka-action-card h4 {
            margin: 0 0 8px;
            font-size: 15px;
            font-weight: 600;
            color: #1d1d1f;
        }
        
        body.ka-v3-dark .ka-security-v3 .ka-action-card h4 {
            color: #f5f5f7;
        }
        
        .ka-security-v3 .ka-action-card p {
            margin: 0 0 16px;
            font-size: 13px;
            color: #86868b;
        }
        
        .ka-security-v3 .ka-action-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #fff;
            border: 1px solid rgba(0, 0, 0, 0.1);
            padding: 10px 20px;
            border-radius: 980px;
            font-size: 14px;
            color: #1d1d1f;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        body.ka-v3-dark .ka-security-v3 .ka-action-btn {
            background: #2c2c2e;
            border-color: rgba(255, 255, 255, 0.1);
            color: #f5f5f7;
        }
        
        .ka-security-v3 .ka-action-btn:hover {
            border-color: #ef4444;
            color: #ef4444;
        }
        
        body.ka-v3-dark .ka-security-v3 .ka-action-btn:hover {
            border-color: #ef4444;
            color: #ef4444;
        }
        
        .ka-security-v3 .ka-action-btn .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
        }
        
        /* Security specific input focus */
        .ka-security-v3 input:focus {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1) !important;
        }
        
        body.ka-v3-dark .ka-security-v3 input:focus {
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.2) !important;
        }
        
        /* Security toggle color */
        .ka-security-v3 .ka-toggle input:checked + .ka-toggle-slider {
            background: #ef4444 !important;
        }
        </style>

        <div class="ka-admin-wrap ka-security-v3">
            <!-- Header -->
            <div class="ka-admin-header">
                <div class="ka-admin-header-left">
                    <div class="ka-admin-header-icon red">
                        <span class="dashicons dashicons-shield"></span>
                    </div>
                    <div>
                        <h1 class="ka-admin-title"><?php esc_html_e('Login Security', 'king-addons'); ?></h1>
                        <p class="ka-admin-subtitle"><?php esc_html_e('Monitor and protect Login Register Form widgets', 'king-addons'); ?></p>
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
                </div>
            </div>

            <!-- Features -->
            <div class="ka-features-grid">
                <div class="ka-feature-card">
                    <span class="dashicons dashicons-shield-alt"></span>
                    <h4><?php esc_html_e('Rate Limiting', 'king-addons'); ?></h4>
                    <p><?php esc_html_e('Auto-blocks IPs after failed attempts', 'king-addons'); ?></p>
                </div>
                <div class="ka-feature-card">
                    <span class="dashicons dashicons-upload"></span>
                    <h4><?php esc_html_e('File Security', 'king-addons'); ?></h4>
                    <p><?php esc_html_e('Validates uploads & scans content', 'king-addons'); ?></p>
                </div>
                <div class="ka-feature-card">
                    <span class="dashicons dashicons-admin-users"></span>
                    <h4><?php esc_html_e('Anti-Enumeration', 'king-addons'); ?></h4>
                    <p><?php esc_html_e('Unified error messages', 'king-addons'); ?></p>
                </div>
                <div class="ka-feature-card">
                    <span class="dashicons dashicons-share"></span>
                    <h4><?php esc_html_e('Social Login', 'king-addons'); ?></h4>
                    <p><?php esc_html_e('Enhanced OAuth validation', 'king-addons'); ?></p>
                </div>
            </div>

            <!-- Stats -->
            <div class="ka-stats-grid">
                <div class="ka-stat-card">
                    <h3 class="ka-stat-title"><?php esc_html_e('Failed Logins (24h)', 'king-addons'); ?></h3>
                    <div class="ka-stat-number"><?php echo esc_html($stats['failed_logins_24h']); ?></div>
                </div>
                <div class="ka-stat-card">
                    <h3 class="ka-stat-title"><?php esc_html_e('Blocked IPs', 'king-addons'); ?></h3>
                    <div class="ka-stat-number"><?php echo esc_html($stats['blocked_ips']); ?></div>
                </div>
                <div class="ka-stat-card">
                    <h3 class="ka-stat-title"><?php esc_html_e('Suspicious Registrations', 'king-addons'); ?></h3>
                    <div class="ka-stat-number"><?php echo esc_html($stats['suspicious_registrations']); ?></div>
                </div>
                <div class="ka-stat-card">
                    <h3 class="ka-stat-title"><?php esc_html_e('Upload Blocks', 'king-addons'); ?></h3>
                    <div class="ka-stat-number"><?php echo esc_html($stats['file_upload_blocks']); ?></div>
                </div>
            </div>

            <!-- Blocked IPs -->
            <?php if (!empty($blocked_ips)): ?>
            <div class="ka-card">
                <div class="ka-card-header">
                    <span class="dashicons dashicons-dismiss" style="color: #ef4444;"></span>
                    <h2><?php esc_html_e('Blocked IPs', 'king-addons'); ?></h2>
                </div>
                <div class="ka-card-body" style="padding:0">
                    <table class="ka-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('IP Address', 'king-addons'); ?></th>
                                <th><?php esc_html_e('Attempts', 'king-addons'); ?></th>
                                <th><?php esc_html_e('Last Attempt', 'king-addons'); ?></th>
                                <th><?php esc_html_e('Expires', 'king-addons'); ?></th>
                                <th><?php esc_html_e('Actions', 'king-addons'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($blocked_ips as $ip_data): ?>
                            <tr>
                                <td><?php echo esc_html($ip_data['ip']); ?></td>
                                <td><?php echo esc_html($ip_data['attempts']); ?></td>
                                <td><?php echo esc_html(human_time_diff($ip_data['last_attempt'], time()) . ' ago'); ?></td>
                                <td><?php echo esc_html(human_time_diff(time(), $ip_data['expires']) . ' remaining'); ?></td>
                                <td>
                                    <button class="ka-action-btn unblock-ip" data-ip="<?php echo esc_attr($ip_data['ip']); ?>">
                                        <?php esc_html_e('Unblock', 'king-addons'); ?>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Settings -->
            <div class="ka-card">
                <div class="ka-card-header">
                    <span class="dashicons dashicons-admin-settings" style="color: #ef4444;"></span>
                    <h2><?php esc_html_e('Security Settings', 'king-addons'); ?></h2>
                </div>
                <div class="ka-card-body">
                    <form method="post" action="options.php">
                        <?php settings_fields('king_addons_security_settings'); ?>
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Max Login Attempts', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <input type="number" name="king_addons_max_login_attempts" 
                                       value="<?php echo esc_attr(get_option('king_addons_max_login_attempts', 5)); ?>" min="1" max="20" />
                                <p class="ka-row-desc"><?php esc_html_e('Failed attempts before IP is blocked. Recommended: 3-5', 'king-addons'); ?></p>
                            </div>
                        </div>
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Lockout Duration', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <input type="number" name="king_addons_lockout_duration" 
                                       value="<?php echo esc_attr(get_option('king_addons_lockout_duration', 15)); ?>" min="1" max="1440" />
                                <span style="color:#86868b;margin-left:6px"><?php esc_html_e('minutes', 'king-addons'); ?></span>
                                <p class="ka-row-desc"><?php esc_html_e('Duration to block an IP after exceeding attempts. Recommended: 15-30', 'king-addons'); ?></p>
                            </div>
                        </div>
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Security Logging', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <label class="ka-toggle">
                                    <input type="checkbox" name="king_addons_enable_security_logging" value="1" 
                                           <?php checked(get_option('king_addons_enable_security_logging', 1)); ?> />
                                    <span class="ka-toggle-slider"></span>
                                    <span class="ka-toggle-label"><?php esc_html_e('Log security events', 'king-addons'); ?></span>
                                </label>
                                <p class="ka-row-desc"><?php esc_html_e('Record failed attempts, blocks, and suspicious activity', 'king-addons'); ?></p>
                            </div>
                        </div>
                        
                        <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid rgba(0,0,0,0.04);">
                            <button type="submit" class="ka-btn ka-btn-primary"><?php esc_html_e('Save Settings', 'king-addons'); ?></button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Actions -->
            <div class="ka-card">
                <div class="ka-card-header">
                    <span class="dashicons dashicons-admin-tools" style="color: #ef4444;"></span>
                    <h2><?php esc_html_e('Management Actions', 'king-addons'); ?></h2>
                </div>
                <div class="ka-card-body">
                    <div class="ka-actions-grid">
                        <div class="ka-action-card">
                            <h4><?php esc_html_e('Clear Security Logs', 'king-addons'); ?></h4>
                            <p><?php esc_html_e('Remove all logs and reset blocked IPs', 'king-addons'); ?></p>
                            <button class="ka-action-btn" id="clear-security-logs">
                                <span class="dashicons dashicons-trash"></span>
                                <?php esc_html_e('Clear Logs', 'king-addons'); ?>
                            </button>
                        </div>
                        <div class="ka-action-card">
                            <h4><?php esc_html_e('Export Report', 'king-addons'); ?></h4>
                            <p><?php esc_html_e('Download security report as JSON', 'king-addons'); ?></p>
                            <button class="ka-action-btn" id="export-security-report">
                                <span class="dashicons dashicons-download"></span>
                                <?php esc_html_e('Export', 'king-addons'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
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

            // Optional global for any legacy handlers
            window.kaV3ToggleDark = function() {
                const isDark = document.body.classList.contains('ka-v3-dark');
                setThemeMode(isDark ? 'light' : 'dark', true);
            };

            segment.addEventListener('click', (e) => {
                const btn = e.target && e.target.closest ? e.target.closest('.ka-v3-segmented-btn') : null;
                if (!btn) {
                    return;
                }
                e.preventDefault();
                const theme = (btn.getAttribute('data-theme') || 'dark').toString();
                setThemeMode(theme, true);
            });

            setThemeMode(mode, false);
        })();
        
        jQuery(document).ready(function($) {
            // Unblock IP functionality
            $('.unblock-ip').on('click', function() {
                const ip = $(this).data('ip');
                if (confirm('<?php echo esc_js(__('Are you sure you want to unblock this IP?', 'king-addons')); ?>')) {
                    $.post(ajaxurl, {
                        action: 'king_addons_unblock_ip',
                        ip: ip,
                        nonce: '<?php echo wp_create_nonce('king_addons_security_nonce'); ?>'
                    }, function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('<?php echo esc_js(__('Failed to unblock IP', 'king-addons')); ?>');
                        }
                    });
                }
            });

            // Clear security logs
            $('#clear-security-logs').on('click', function() {
                if (confirm('<?php echo esc_js(__('Are you sure you want to clear all security logs?', 'king-addons')); ?>')) {
                    $.post(ajaxurl, {
                        action: 'king_addons_clear_security_logs',
                        nonce: '<?php echo wp_create_nonce('king_addons_security_nonce'); ?>'
                    }, function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('<?php echo esc_js(__('Failed to clear logs', 'king-addons')); ?>');
                        }
                    });
                }
            });

            // Export security report
            $('#export-security-report').on('click', function() {
                const $button = $(this);
                const originalText = $button.html();
                $button.prop('disabled', true).text('<?php echo esc_js(__('Exporting...', 'king-addons')); ?>');
                
                $.post(ajaxurl, {
                    action: 'king_addons_export_security_report',
                    nonce: '<?php echo wp_create_nonce('king_addons_security_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        const link = document.createElement('a');
                        link.href = response.data.download_url;
                        link.download = response.data.filename;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    } else {
                        alert('<?php echo esc_js(__('Failed to generate report', 'king-addons')); ?>');
                    }
                }).always(function() {
                    $button.prop('disabled', false).html(originalText);
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Get security statistics
     */
    private static function get_security_statistics()
    {
        global $wpdb;
        
        $stats = [
            'failed_logins_24h' => 0,
            'blocked_ips' => 0,
            'suspicious_registrations' => 0,
            'file_upload_blocks' => 0
        ];

        // Count blocked IPs
        $transients = $wpdb->get_results(
            "SELECT option_name FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_king_addons_%_attempts_%' 
             AND option_value >= 3"
        );
        $stats['blocked_ips'] = count($transients);

        // Get failed attempts from error log (simplified - would need actual log parsing)
        $log_file = ini_get('error_log');
        if ($log_file && file_exists($log_file)) {
            $log_content = file_get_contents($log_file);
            $stats['failed_logins_24h'] = substr_count($log_content, 'King Addons Security: Failed login');
            $stats['suspicious_registrations'] = substr_count($log_content, 'Suspicious registration pattern');
            $stats['file_upload_blocks'] = substr_count($log_content, 'File upload blocked');
        }

        return $stats;
    }

    /**
     * Get currently blocked IPs
     */
    private static function get_blocked_ips()
    {
        global $wpdb;
        
        $blocked_ips = [];
        
        $transients = $wpdb->get_results(
            "SELECT option_name, option_value 
             FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_king_addons_%_attempts_%'"
        );

        foreach ($transients as $transient) {
            $attempts = intval($transient->option_value);
            if ($attempts >= Security_Manager::MAX_LOGIN_ATTEMPTS) {
                // Extract IP from transient name
                preg_match('/_transient_king_addons_\w+_attempts_(.+)/', $transient->option_name, $matches);
                if (isset($matches[1])) {
                    $ip_hash = $matches[1];
                    
                    // Get expiration time
                    $timeout_option = '_transient_timeout_' . str_replace('_transient_', '', $transient->option_name);
                    $expires = get_option($timeout_option, 0);
                    
                    $blocked_ips[] = [
                        'ip' => 'IP Hash: ' . substr($ip_hash, 0, 8) . '...', // Don't expose full IPs
                        'attempts' => $attempts,
                        'last_attempt' => time() - 300, // Approximate
                        'expires' => $expires
                    ];
                }
            }
        }

        return $blocked_ips;
    }

    /**
     * Get recent failed attempts from logs
     */
    private static function get_recent_failed_attempts()
    {
        $attempts = [];
        
        // This would parse actual log files in a real implementation
        // For now, return sample data structure
        
        return $attempts;
    }

    /**
     * AJAX handler to clear security logs
     */
    public static function clear_security_logs()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'king_addons_security_nonce')) {
            wp_send_json_error(['message' => 'Invalid nonce']);
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }

        // Clear all rate limiting transients
        global $wpdb;
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_king_addons_%_attempts_%' 
             OR option_name LIKE '_transient_timeout_king_addons_%_attempts_%'"
        );

        wp_send_json_success(['message' => 'Security logs cleared successfully']);
    }

    /**
     * AJAX handler to unblock IP
     */
    public static function unblock_ip()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'king_addons_security_nonce')) {
            wp_send_json_error(['message' => 'Invalid nonce']);
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }

        $ip = sanitize_text_field($_POST['ip']);
        if (empty($ip)) {
            wp_send_json_error(['message' => 'Invalid IP address']);
        }

        // Clear attempts for this IP (simplified)
        global $wpdb;
        $ip_hash = md5($ip);
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE %s 
             OR option_name LIKE %s",
            '%_king_addons_%_attempts_' . $ip_hash,
            '%_king_addons_%_attempts_' . $ip_hash . '%'
        ));

        wp_send_json_success(['message' => 'IP unblocked successfully']);
    }

    /**
     * AJAX handler to export security report
     */
    public static function export_security_report()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'king_addons_security_nonce')) {
            wp_send_json_error(['message' => 'Invalid nonce']);
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }

        // Generate security report
        $stats = self::get_security_statistics();
        $blocked_ips = self::get_blocked_ips();
        
        $report = [
            'generated_at' => current_time('Y-m-d H:i:s'),
            'site_url' => get_site_url(),
            'plugin_version' => defined('KING_ADDONS_VERSION') ? KING_ADDONS_VERSION : 'Unknown',
            'statistics' => $stats,
            'blocked_ips' => $blocked_ips,
            'security_settings' => [
                'max_login_attempts' => get_option('king_addons_max_login_attempts', 5),
                'lockout_duration' => get_option('king_addons_lockout_duration', 15),
                'security_logging_enabled' => get_option('king_addons_enable_security_logging', 1),
            ]
        ];

        // Convert to JSON
        $json_report = json_encode($report, JSON_PRETTY_PRINT);
        
        // Create filename
        $filename = 'king-addons-security-report-' . date('Y-m-d-H-i-s') . '.json';
        
        // Return download URL
        $upload_dir = wp_upload_dir();
        $report_path = $upload_dir['path'] . '/' . $filename;
        
        // Save file
        if (file_put_contents($report_path, $json_report)) {
            $download_url = $upload_dir['url'] . '/' . $filename;
            wp_send_json_success([
                'message' => 'Security report generated successfully',
                'download_url' => $download_url,
                'filename' => $filename
            ]);
        } else {
            wp_send_json_error(['message' => 'Failed to generate report file']);
        }
    }
} 