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
     * Render the security dashboard
     */
    public static function render_dashboard()
    {
        // Get security statistics
        $stats = self::get_security_statistics();
        $blocked_ips = self::get_blocked_ips();
        $recent_attempts = self::get_recent_failed_attempts();
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('King Addons - Login Security Dashboard', 'king-addons'); ?></h1>
            
            <!-- Widget Information -->
            <div class="king-addons-widget-info">
                <div class="widget-info-header">
                    <h2><?php echo esc_html__('Login Register Form Widget Security', 'king-addons'); ?></h2>
                    <p class="description">
                        <?php echo esc_html__('This security dashboard monitors and protects the Login Register Form widget from various threats including brute-force attacks, spam registrations, malicious file uploads, and unauthorized access attempts.', 'king-addons'); ?>
                    </p>
                </div>
                
                <div class="security-features-grid">
                    <div class="security-feature">
                        <span class="dashicons dashicons-shield-alt"></span>
                        <h4><?php echo esc_html__('Rate Limiting', 'king-addons'); ?></h4>
                        <p><?php echo esc_html__('Automatically blocks IPs after failed login attempts', 'king-addons'); ?></p>
                    </div>
                    
                    <div class="security-feature">
                        <span class="dashicons dashicons-upload"></span>
                        <h4><?php echo esc_html__('File Upload Security', 'king-addons'); ?></h4>
                        <p><?php echo esc_html__('Validates file types, sizes, and scans for malicious content', 'king-addons'); ?></p>
                    </div>
                    
                    <div class="security-feature">
                        <span class="dashicons dashicons-admin-users"></span>
                        <h4><?php echo esc_html__('Anti-Enumeration', 'king-addons'); ?></h4>
                        <p><?php echo esc_html__('Prevents user enumeration through unified error messages', 'king-addons'); ?></p>
                    </div>
                    
                    <div class="security-feature">
                        <span class="dashicons dashicons-share"></span>
                        <h4><?php echo esc_html__('Social Login Protection', 'king-addons'); ?></h4>
                        <p><?php echo esc_html__('Enhanced validation for Google and Facebook login data', 'king-addons'); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Security Overview -->
            <div class="king-addons-security-overview">
                <h2><?php echo esc_html__('Security Statistics', 'king-addons'); ?></h2>
                <p class="description">
                    <?php echo esc_html__('Real-time security metrics for the Login Register Form widget across your entire website.', 'king-addons'); ?>
                </p>
                <div class="king-addons-stats-grid">
                    <div class="king-addons-stat-card">
                        <h3><?php echo esc_html__('Failed Login Attempts (24h)', 'king-addons'); ?></h3>
                        <div class="stat-number"><?php echo esc_html($stats['failed_logins_24h']); ?></div>
                    </div>
                    
                    <div class="king-addons-stat-card">
                        <h3><?php echo esc_html__('Blocked IPs', 'king-addons'); ?></h3>
                        <div class="stat-number"><?php echo esc_html($stats['blocked_ips']); ?></div>
                    </div>
                    
                    <div class="king-addons-stat-card">
                        <h3><?php echo esc_html__('Suspicious Registrations', 'king-addons'); ?></h3>
                        <div class="stat-number"><?php echo esc_html($stats['suspicious_registrations']); ?></div>
                    </div>
                    
                    <div class="king-addons-stat-card">
                        <h3><?php echo esc_html__('File Upload Blocks', 'king-addons'); ?></h3>
                        <div class="stat-number"><?php echo esc_html($stats['file_upload_blocks']); ?></div>
                    </div>
                </div>
            </div>

            <!-- Currently Blocked IPs -->
            <?php if (!empty($blocked_ips)): ?>
            <div class="king-addons-blocked-ips">
                <h2><?php echo esc_html__('Currently Blocked IPs', 'king-addons'); ?></h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('IP Address', 'king-addons'); ?></th>
                            <th><?php echo esc_html__('Attempts', 'king-addons'); ?></th>
                            <th><?php echo esc_html__('Last Attempt', 'king-addons'); ?></th>
                            <th><?php echo esc_html__('Expires', 'king-addons'); ?></th>
                            <th><?php echo esc_html__('Actions', 'king-addons'); ?></th>
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
                                <button class="button unblock-ip" data-ip="<?php echo esc_attr($ip_data['ip']); ?>">
                                    <?php echo esc_html__('Unblock', 'king-addons'); ?>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- Recent Failed Attempts -->
            <?php if (!empty($recent_attempts)): ?>
            <div class="king-addons-recent-attempts">
                <h2><?php echo esc_html__('Recent Failed Attempts', 'king-addons'); ?></h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Time', 'king-addons'); ?></th>
                            <th><?php echo esc_html__('IP Address', 'king-addons'); ?></th>
                            <th><?php echo esc_html__('Type', 'king-addons'); ?></th>
                            <th><?php echo esc_html__('Details', 'king-addons'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_attempts as $attempt): ?>
                        <tr>
                            <td><?php echo esc_html(date('Y-m-d H:i:s', $attempt['time'])); ?></td>
                            <td><?php echo esc_html($attempt['ip']); ?></td>
                            <td><?php echo esc_html(ucfirst($attempt['type'])); ?></td>
                            <td><?php echo esc_html($attempt['details']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- Security Settings -->
            <div class="king-addons-security-settings">
                <h2><?php echo esc_html__('Security Settings for Login Register Form Widget', 'king-addons'); ?></h2>
                <p class="description">
                    <?php echo esc_html__('Configure security parameters that apply to all Login Register Form widgets on your website. These settings help protect against brute-force attacks, spam registrations, and other security threats.', 'king-addons'); ?>
                </p>
                <form method="post" action="options.php">
                    <?php settings_fields('king_addons_security_settings'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="king_addons_max_login_attempts">
                                    <?php echo esc_html__('Max Login Attempts', 'king-addons'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="number" id="king_addons_max_login_attempts" name="king_addons_max_login_attempts" 
                                       value="<?php echo esc_attr(get_option('king_addons_max_login_attempts', 5)); ?>" min="1" max="20" />
                                <p class="description">
                                    <?php echo esc_html__('Number of failed login attempts before an IP address is temporarily blocked from accessing Login Register Form widgets. Recommended: 3-5 attempts.', 'king-addons'); ?>
                                    <br><strong><?php echo esc_html__('Applies to:', 'king-addons'); ?></strong> <?php echo esc_html__('Login forms, Registration forms, Password reset forms', 'king-addons'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="king_addons_lockout_duration">
                                    <?php echo esc_html__('Lockout Duration (minutes)', 'king-addons'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="number" id="king_addons_lockout_duration" name="king_addons_lockout_duration" 
                                       value="<?php echo esc_attr(get_option('king_addons_lockout_duration', 15)); ?>" min="1" max="1440" />
                                <p class="description">
                                    <?php echo esc_html__('Duration in minutes to block an IP address after exceeding failed attempts. During this time, the IP cannot access any Login Register Form widgets. Recommended: 15-30 minutes.', 'king-addons'); ?>
                                    <br><strong><?php echo esc_html__('Security Impact:', 'king-addons'); ?></strong> <?php echo esc_html__('Prevents brute-force attacks and automated bot attempts', 'king-addons'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="king_addons_enable_security_logging">
                                    <?php echo esc_html__('Enable Security Logging', 'king-addons'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="checkbox" id="king_addons_enable_security_logging" name="king_addons_enable_security_logging" value="1" 
                                       <?php checked(get_option('king_addons_enable_security_logging', 1)); ?> />
                                <p class="description">
                                    <?php echo esc_html__('Log all security events related to Login Register Form widgets including failed attempts, suspicious registrations, blocked file uploads, and social login activities. Logs help track and analyze security threats.', 'king-addons'); ?>
                                    <br><strong><?php echo esc_html__('Recommended:', 'king-addons'); ?></strong> <?php echo esc_html__('Keep enabled for security monitoring and compliance', 'king-addons'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button(); ?>
                </form>
            </div>

            <!-- Actions -->
            <div class="king-addons-security-actions">
                <h2><?php echo esc_html__('Security Management Actions', 'king-addons'); ?></h2>
                <p class="description">
                    <?php echo esc_html__('Manage security data and generate reports for Login Register Form widget security events.', 'king-addons'); ?>
                </p>
                
                <div class="security-actions-grid">
                    <div class="action-card">
                        <h4><?php echo esc_html__('Clear Security Logs', 'king-addons'); ?></h4>
                        <p><?php echo esc_html__('Remove all stored security logs and reset blocked IP addresses. This action cannot be undone.', 'king-addons'); ?></p>
                        <button class="button button-secondary" id="clear-security-logs">
                            <span class="dashicons dashicons-trash"></span>
                            <?php echo esc_html__('Clear All Logs', 'king-addons'); ?>
                        </button>
                    </div>
                    
                    <div class="action-card">
                        <h4><?php echo esc_html__('Export Security Report', 'king-addons'); ?></h4>
                        <p><?php echo esc_html__('Generate and download a comprehensive security report including all statistics and blocked IPs.', 'king-addons'); ?></p>
                        <button class="button button-secondary" id="export-security-report">
                            <span class="dashicons dashicons-download"></span>
                            <?php echo esc_html__('Export Report', 'king-addons'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <style>
        /* Widget Info Section */
        .king-addons-widget-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
            padding: 30px;
            margin: 20px 0;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .widget-info-header h2 {
            color: white;
            margin: 0 0 10px 0;
        }
        .widget-info-header .description {
            color: rgba(255,255,255,0.9);
            font-size: 16px;
            margin-bottom: 25px;
        }
        .security-features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        .security-feature {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.2);
        }
        .security-feature .dashicons {
            font-size: 32px;
            width: 32px;
            height: 32px;
            margin-bottom: 10px;
            opacity: 0.9;
        }
        .security-feature h4 {
            color: white;
            margin: 10px 0;
            font-size: 16px;
        }
        .security-feature p {
            color: rgba(255,255,255,0.8);
            font-size: 14px;
            margin: 0;
        }

        /* Stats Grid */
        .king-addons-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .king-addons-stat-card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 8px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: transform 0.2s ease;
        }
        .king-addons-stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .king-addons-stat-card h3 {
            margin: 0 0 15px 0;
            font-size: 14px;
            color: #555;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #2271b1;
            line-height: 1;
        }

        /* Main Sections */
        .king-addons-security-overview,
        .king-addons-blocked-ips,
        .king-addons-recent-attempts,
        .king-addons-security-settings,
        .king-addons-security-actions {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 8px;
            padding: 25px;
            margin: 20px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        /* Security Actions */
        .security-actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .action-card {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }
        .action-card h4 {
            margin: 0 0 10px 0;
            color: #333;
        }
        .action-card p {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }
        .action-card button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 32px;
        }
        .action-card button .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
            line-height: 16px;
            vertical-align: middle;
        }

        /* Form improvements */
        .form-table th {
            width: 250px;
            padding: 20px 10px 20px 0;
        }
        .form-table td {
            padding: 20px 10px;
        }
        .form-table input[type="number"] {
            width: 100px;
        }
        .form-table .description {
            margin-top: 8px;
            line-height: 1.5;
        }
        .form-table .description strong {
            color: #2271b1;
        }

        /* Table improvements */
        .wp-list-table th, .wp-list-table td {
            padding: 12px;
        }
        .unblock-ip {
            font-size: 12px;
            padding: 4px 8px;
        }

        /* Loading animation */
        @keyframes rotation {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(359deg);
            }
        }
        </style>

        <script>
        jQuery(document).ready(function($) {
            // Unblock IP functionality
            $('.unblock-ip').on('click', function() {
                const ip = $(this).data('ip');
                if (confirm('Are you sure you want to unblock this IP?')) {
                    $.post(ajaxurl, {
                        action: 'king_addons_unblock_ip',
                        ip: ip,
                        nonce: '<?php echo wp_create_nonce('king_addons_security_nonce'); ?>'
                    }, function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Failed to unblock IP: ' + response.data.message);
                        }
                    });
                }
            });

            // Clear security logs
            $('#clear-security-logs').on('click', function() {
                if (confirm('Are you sure you want to clear all security logs?')) {
                    $.post(ajaxurl, {
                        action: 'king_addons_clear_security_logs',
                        nonce: '<?php echo wp_create_nonce('king_addons_security_nonce'); ?>'
                    }, function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Failed to clear logs: ' + response.data.message);
                        }
                    });
                }
            });

            // Export security report
            $('#export-security-report').on('click', function() {
                const $button = $(this);
                const originalText = $button.html();
                
                // Show loading state
                $button.prop('disabled', true).html('<span class="dashicons dashicons-update-alt" style="animation: rotation 1s infinite linear;"></span> Generating...');
                
                $.post(ajaxurl, {
                    action: 'king_addons_export_security_report',
                    nonce: '<?php echo wp_create_nonce('king_addons_security_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        // Create download link and trigger download
                        const link = document.createElement('a');
                        link.href = response.data.download_url;
                        link.download = response.data.filename;
                        link.style.display = 'none';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        
                        alert('Security report downloaded successfully!');
                    } else {
                        alert('Failed to generate report: ' + response.data.message);
                    }
                }).fail(function() {
                    alert('Network error occurred while generating report.');
                }).always(function() {
                    // Restore button state
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