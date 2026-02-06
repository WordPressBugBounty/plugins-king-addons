<?php
/**
 * Event Logger - WordPress hook listeners for Activity Log.
 *
 * @package King_Addons
 */

namespace King_Addons;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Captures WordPress events and logs them to the database.
 */
final class Activity_Log_Event_Logger
{
    /**
     * Singleton instance.
     *
     * @var Activity_Log_Event_Logger|null
     */
    private static ?Activity_Log_Event_Logger $instance = null;

    /**
     * Extension settings.
     *
     * @var array<string, mixed>
     */
    private array $settings;

    /**
     * Get singleton instance.
     *
     * @param array<string, mixed> $settings Extension settings.
     * @return Activity_Log_Event_Logger
     */
    public static function instance(array $settings = []): Activity_Log_Event_Logger
    {
        if (self::$instance === null) {
            self::$instance = new self($settings);
        }
        return self::$instance;
    }

    /**
     * Constructor - registers WordPress hooks.
     *
     * @param array<string, mixed> $settings Extension settings.
     */
    private function __construct(array $settings)
    {
        $this->settings = $settings;

        // Check if logging is enabled
        if (empty($this->settings['enabled'])) {
            return;
        }

        // Register hooks based on enabled modules
        $this->register_auth_hooks();
        $this->register_user_hooks();
        $this->register_content_hooks();
        $this->register_plugin_hooks();
        $this->register_theme_hooks();

        // Custom event action for other extensions
        add_action('kng_activity_log/event', [$this, 'log_custom_event'], 10, 1);
    }

    // =========================================================================
    // Hook Registration
    // =========================================================================

    /**
     * Register authentication hooks.
     *
     * @return void
     */
    private function register_auth_hooks(): void
    {
        if (!$this->is_module_enabled('auth')) {
            return;
        }

        add_action('wp_login', [$this, 'on_login'], 10, 2);
        add_action('wp_logout', [$this, 'on_logout'], 10, 1);
        add_action('wp_login_failed', [$this, 'on_login_failed'], 10, 2);
    }

    /**
     * Register user hooks.
     *
     * @return void
     */
    private function register_user_hooks(): void
    {
        if (!$this->is_module_enabled('users')) {
            return;
        }

        add_action('user_register', [$this, 'on_user_created'], 10, 2);
        add_action('profile_update', [$this, 'on_user_updated'], 10, 3);
        add_action('delete_user', [$this, 'on_user_deleted'], 10, 3);
        add_action('set_user_role', [$this, 'on_user_role_changed'], 10, 3);
    }

    /**
     * Register content hooks.
     *
     * @return void
     */
    private function register_content_hooks(): void
    {
        if (!$this->is_module_enabled('content')) {
            return;
        }

        add_action('transition_post_status', [$this, 'on_post_status_change'], 10, 3);
        add_action('before_delete_post', [$this, 'on_post_deleted'], 10, 2);
    }

    /**
     * Register plugin hooks.
     *
     * @return void
     */
    private function register_plugin_hooks(): void
    {
        if (!$this->is_module_enabled('plugins')) {
            return;
        }

        add_action('activated_plugin', [$this, 'on_plugin_activated'], 10, 2);
        add_action('deactivated_plugin', [$this, 'on_plugin_deactivated'], 10, 2);
    }

    /**
     * Register theme hooks.
     *
     * @return void
     */
    private function register_theme_hooks(): void
    {
        if (!$this->is_module_enabled('themes')) {
            return;
        }

        add_action('switch_theme', [$this, 'on_theme_switched'], 10, 3);
    }

    // =========================================================================
    // Auth Event Handlers
    // =========================================================================

    /**
     * Handle successful login.
     *
     * @param string   $user_login User login name.
     * @param \WP_User $user       User object.
     * @return void
     */
    public function on_login(string $user_login, \WP_User $user): void
    {
        if ($this->is_excluded_user($user)) {
            return;
        }

        $this->log([
            'event_key' => Activity_Log_Event_Types::AUTH_LOGIN_SUCCESS,
            'user_id' => $user->ID,
            'user_login' => $user_login,
            'user_role' => $this->get_primary_role($user),
            'object_type' => 'user',
            'object_id' => (string) $user->ID,
            'object_title' => $user->display_name,
            'message' => sprintf(
                /* translators: %s: user login name */
                __('User %s logged in', 'king-addons'),
                $user_login
            ),
        ]);
    }

    /**
     * Handle logout.
     *
     * @param int $user_id User ID.
     * @return void
     */
    public function on_logout(int $user_id): void
    {
        $user = get_userdata($user_id);
        if (!$user || $this->is_excluded_user($user)) {
            return;
        }

        $this->log([
            'event_key' => Activity_Log_Event_Types::AUTH_LOGOUT,
            'user_id' => $user_id,
            'user_login' => $user->user_login,
            'user_role' => $this->get_primary_role($user),
            'object_type' => 'user',
            'object_id' => (string) $user_id,
            'object_title' => $user->display_name,
            'message' => sprintf(
                /* translators: %s: user login name */
                __('User %s logged out', 'king-addons'),
                $user->user_login
            ),
        ]);
    }

    /**
     * Handle failed login.
     *
     * @param string    $username Username attempted.
     * @param \WP_Error $error    Error object.
     * @return void
     */
    public function on_login_failed(string $username, \WP_Error $error): void
    {
        $error_code = $error->get_error_code();

        $this->log([
            'event_key' => Activity_Log_Event_Types::AUTH_LOGIN_FAILED,
            'severity' => Activity_Log_Event_Types::SEVERITY_WARNING,
            'object_type' => 'user',
            'object_title' => $username,
            'message' => sprintf(
                /* translators: 1: username, 2: error code */
                __('Failed login attempt for "%1$s" (%2$s)', 'king-addons'),
                $username,
                $error_code
            ),
            'data' => [
                'error_code' => $error_code,
                'error_message' => $error->get_error_message(),
            ],
        ]);
    }

    // =========================================================================
    // User Event Handlers
    // =========================================================================

    /**
     * Handle user creation.
     *
     * @param int   $user_id  User ID.
     * @param array $userdata User data array.
     * @return void
     */
    public function on_user_created(int $user_id, array $userdata = []): void
    {
        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }

        $current_user = wp_get_current_user();

        $this->log([
            'event_key' => Activity_Log_Event_Types::USER_CREATED,
            'user_id' => $current_user->ID ?: null,
            'user_login' => $current_user->user_login ?: null,
            'user_role' => $current_user->ID ? $this->get_primary_role($current_user) : null,
            'object_type' => 'user',
            'object_id' => (string) $user_id,
            'object_title' => $user->display_name,
            'message' => sprintf(
                /* translators: %s: new user login name */
                __('User %s created', 'king-addons'),
                $user->user_login
            ),
            'data' => [
                'new_user_role' => $this->get_primary_role($user),
                'new_user_email' => $user->user_email,
            ],
        ]);
    }

    /**
     * Handle user update.
     *
     * @param int      $user_id       User ID.
     * @param \WP_User $old_user_data Old user data.
     * @param array    $userdata      New user data.
     * @return void
     */
    public function on_user_updated(int $user_id, \WP_User $old_user_data, array $userdata = []): void
    {
        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }

        $current_user = wp_get_current_user();
        if ($this->is_excluded_user($current_user)) {
            return;
        }

        $this->log([
            'event_key' => Activity_Log_Event_Types::USER_UPDATED,
            'user_id' => $current_user->ID,
            'user_login' => $current_user->user_login,
            'user_role' => $this->get_primary_role($current_user),
            'object_type' => 'user',
            'object_id' => (string) $user_id,
            'object_title' => $user->display_name,
            'message' => sprintf(
                /* translators: %s: user login name */
                __('User %s updated', 'king-addons'),
                $user->user_login
            ),
        ]);
    }

    /**
     * Handle user deletion.
     *
     * @param int      $user_id  User ID being deleted.
     * @param int|null $reassign User ID to reassign posts to.
     * @param \WP_User $user     User object being deleted.
     * @return void
     */
    public function on_user_deleted(int $user_id, ?int $reassign, \WP_User $user): void
    {
        $current_user = wp_get_current_user();

        $this->log([
            'event_key' => Activity_Log_Event_Types::USER_DELETED,
            'severity' => Activity_Log_Event_Types::SEVERITY_CRITICAL,
            'user_id' => $current_user->ID,
            'user_login' => $current_user->user_login,
            'user_role' => $this->get_primary_role($current_user),
            'object_type' => 'user',
            'object_id' => (string) $user_id,
            'object_title' => $user->display_name,
            'message' => sprintf(
                /* translators: %s: user login name */
                __('User %s deleted', 'king-addons'),
                $user->user_login
            ),
            'data' => [
                'deleted_user_email' => $user->user_email,
                'deleted_user_role' => $this->get_primary_role($user),
                'reassign_to' => $reassign,
            ],
        ]);
    }

    /**
     * Handle user role change.
     *
     * @param int    $user_id   User ID.
     * @param string $new_role  New role.
     * @param array  $old_roles Old roles.
     * @return void
     */
    public function on_user_role_changed(int $user_id, string $new_role, array $old_roles): void
    {
        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }

        $current_user = wp_get_current_user();
        $old_role = !empty($old_roles) ? $old_roles[0] : '';

        // Determine severity - admin grant is critical
        $severity = ($new_role === 'administrator')
            ? Activity_Log_Event_Types::SEVERITY_CRITICAL
            : Activity_Log_Event_Types::SEVERITY_NOTICE;

        $this->log([
            'event_key' => Activity_Log_Event_Types::USER_ROLE_CHANGED,
            'severity' => $severity,
            'user_id' => $current_user->ID,
            'user_login' => $current_user->user_login,
            'user_role' => $this->get_primary_role($current_user),
            'object_type' => 'user',
            'object_id' => (string) $user_id,
            'object_title' => $user->display_name,
            'message' => sprintf(
                /* translators: 1: user login, 2: old role, 3: new role */
                __('User %1$s role changed from %2$s to %3$s', 'king-addons'),
                $user->user_login,
                $old_role,
                $new_role
            ),
            'data' => [
                'old_role' => $old_role,
                'new_role' => $new_role,
            ],
        ]);
    }

    // =========================================================================
    // Content Event Handlers
    // =========================================================================

    /**
     * Handle post status transition.
     *
     * @param string   $new_status New post status.
     * @param string   $old_status Old post status.
     * @param \WP_Post $post       Post object.
     * @return void
     */
    public function on_post_status_change(string $new_status, string $old_status, \WP_Post $post): void
    {
        // Skip revisions and auto-drafts
        if (wp_is_post_revision($post->ID) || wp_is_post_autosave($post->ID)) {
            return;
        }

        if ($post->post_status === 'auto-draft') {
            return;
        }

        // Skip if statuses are the same (no actual change)
        if ($new_status === $old_status) {
            return;
        }

        $current_user = wp_get_current_user();
        if ($this->is_excluded_user($current_user)) {
            return;
        }

        // Determine event type
        $event_key = null;

        if ($old_status === 'new' || $old_status === 'auto-draft') {
            $event_key = Activity_Log_Event_Types::CONTENT_CREATED;
        } elseif ($new_status === 'trash') {
            $event_key = Activity_Log_Event_Types::CONTENT_TRASHED;
        } elseif ($old_status === 'trash') {
            $event_key = Activity_Log_Event_Types::CONTENT_RESTORED;
        } else {
            $event_key = Activity_Log_Event_Types::CONTENT_UPDATED;
        }

        $this->log([
            'event_key' => $event_key,
            'user_id' => $current_user->ID,
            'user_login' => $current_user->user_login,
            'user_role' => $this->get_primary_role($current_user),
            'object_type' => $post->post_type,
            'object_id' => (string) $post->ID,
            'object_title' => $post->post_title,
            'message' => sprintf(
                /* translators: 1: post type, 2: post title, 3: old status, 4: new status */
                __('%1$s "%2$s" status changed from %3$s to %4$s', 'king-addons'),
                ucfirst($post->post_type),
                $post->post_title,
                $old_status,
                $new_status
            ),
            'data' => [
                'old_status' => $old_status,
                'new_status' => $new_status,
            ],
        ]);
    }

    /**
     * Handle permanent post deletion.
     *
     * @param int      $post_id Post ID.
     * @param \WP_Post $post    Post object.
     * @return void
     */
    public function on_post_deleted(int $post_id, \WP_Post $post): void
    {
        if (wp_is_post_revision($post_id)) {
            return;
        }

        $current_user = wp_get_current_user();
        if ($this->is_excluded_user($current_user)) {
            return;
        }

        $this->log([
            'event_key' => Activity_Log_Event_Types::CONTENT_DELETED,
            'severity' => Activity_Log_Event_Types::SEVERITY_CRITICAL,
            'user_id' => $current_user->ID,
            'user_login' => $current_user->user_login,
            'user_role' => $this->get_primary_role($current_user),
            'object_type' => $post->post_type,
            'object_id' => (string) $post_id,
            'object_title' => $post->post_title,
            'message' => sprintf(
                /* translators: 1: post type, 2: post title */
                __('%1$s "%2$s" permanently deleted', 'king-addons'),
                ucfirst($post->post_type),
                $post->post_title
            ),
        ]);
    }

    // =========================================================================
    // Plugin Event Handlers
    // =========================================================================

    /**
     * Handle plugin activation.
     *
     * @param string $plugin       Plugin path.
     * @param bool   $network_wide Network-wide activation.
     * @return void
     */
    public function on_plugin_activated(string $plugin, bool $network_wide): void
    {
        $current_user = wp_get_current_user();
        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
        $plugin_name = $plugin_data['Name'] ?? $plugin;

        $this->log([
            'event_key' => Activity_Log_Event_Types::PLUGIN_ACTIVATED,
            'severity' => Activity_Log_Event_Types::SEVERITY_WARNING,
            'user_id' => $current_user->ID,
            'user_login' => $current_user->user_login,
            'user_role' => $this->get_primary_role($current_user),
            'object_type' => 'plugin',
            'object_id' => $plugin,
            'object_title' => $plugin_name,
            'message' => sprintf(
                /* translators: %s: plugin name */
                __('Plugin "%s" activated', 'king-addons'),
                $plugin_name
            ),
            'data' => [
                'network_wide' => $network_wide,
                'version' => $plugin_data['Version'] ?? '',
            ],
        ]);
    }

    /**
     * Handle plugin deactivation.
     *
     * @param string $plugin       Plugin path.
     * @param bool   $network_wide Network-wide deactivation.
     * @return void
     */
    public function on_plugin_deactivated(string $plugin, bool $network_wide): void
    {
        $current_user = wp_get_current_user();
        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
        $plugin_name = $plugin_data['Name'] ?? $plugin;

        $this->log([
            'event_key' => Activity_Log_Event_Types::PLUGIN_DEACTIVATED,
            'severity' => Activity_Log_Event_Types::SEVERITY_WARNING,
            'user_id' => $current_user->ID,
            'user_login' => $current_user->user_login,
            'user_role' => $this->get_primary_role($current_user),
            'object_type' => 'plugin',
            'object_id' => $plugin,
            'object_title' => $plugin_name,
            'message' => sprintf(
                /* translators: %s: plugin name */
                __('Plugin "%s" deactivated', 'king-addons'),
                $plugin_name
            ),
            'data' => [
                'network_wide' => $network_wide,
            ],
        ]);
    }

    // =========================================================================
    // Theme Event Handlers
    // =========================================================================

    /**
     * Handle theme switch.
     *
     * @param string    $new_name  New theme name.
     * @param \WP_Theme $new_theme New theme object.
     * @param \WP_Theme $old_theme Old theme object.
     * @return void
     */
    public function on_theme_switched(string $new_name, \WP_Theme $new_theme, \WP_Theme $old_theme): void
    {
        $current_user = wp_get_current_user();

        $this->log([
            'event_key' => Activity_Log_Event_Types::THEME_SWITCHED,
            'severity' => Activity_Log_Event_Types::SEVERITY_WARNING,
            'user_id' => $current_user->ID,
            'user_login' => $current_user->user_login,
            'user_role' => $this->get_primary_role($current_user),
            'object_type' => 'theme',
            'object_id' => $new_theme->get_stylesheet(),
            'object_title' => $new_name,
            'message' => sprintf(
                /* translators: 1: old theme name, 2: new theme name */
                __('Theme switched from "%1$s" to "%2$s"', 'king-addons'),
                $old_theme->get('Name'),
                $new_name
            ),
            'data' => [
                'old_theme' => $old_theme->get_stylesheet(),
                'old_theme_name' => $old_theme->get('Name'),
            ],
        ]);
    }

    // =========================================================================
    // Custom Event Handler
    // =========================================================================

    /**
     * Log custom event from other extensions.
     *
     * @param array<string, mixed> $event Event data.
     * @return void
     */
    public function log_custom_event(array $event): void
    {
        $this->log($event);
    }

    // =========================================================================
    // Core Logging Method
    // =========================================================================

    /**
     * Log an event to the database.
     *
     * @param array<string, mixed> $data Event data.
     * @return void
     */
    private function log(array $data): void
    {
        // Add context
        $data['context'] = $this->get_context();
        $data['source'] = $data['source'] ?? 'core';

        // Add IP and user agent
        $data['ip'] = $this->get_client_ip();
        $data['user_agent'] = $this->get_user_agent();

        // Set default severity if not specified
        if (empty($data['severity'])) {
            $data['severity'] = Activity_Log_Event_Types::get_default_severity($data['event_key'] ?? '');
        }

        // Insert into database
        Activity_Log_DB::insert($data);
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Check if a module is enabled.
     *
     * @param string $module Module name.
     * @return bool
     */
    private function is_module_enabled(string $module): bool
    {
        $modules = $this->settings['modules'] ?? [];
        return !isset($modules[$module]) || $modules[$module] === true;
    }

    /**
     * Check if user is excluded from logging.
     *
     * @param \WP_User $user User object.
     * @return bool
     */
    private function is_excluded_user(\WP_User $user): bool
    {
        if (empty($user->ID)) {
            return false;
        }

        // Check excluded roles
        $excluded_roles = $this->settings['excluded_roles'] ?? [];
        if (!empty($excluded_roles)) {
            foreach ($user->roles as $role) {
                if (in_array($role, $excluded_roles, true)) {
                    return true;
                }
            }
        }

        // Check excluded user IDs
        $excluded_users = $this->settings['excluded_users'] ?? [];
        if (in_array($user->ID, $excluded_users, true)) {
            return true;
        }

        return false;
    }

    /**
     * Get user's primary role.
     *
     * @param \WP_User $user User object.
     * @return string
     */
    private function get_primary_role(\WP_User $user): string
    {
        return !empty($user->roles) ? $user->roles[0] : '';
    }

    /**
     * Get current context.
     *
     * @return string
     */
    private function get_context(): string
    {
        if (defined('WP_CLI') && WP_CLI) {
            return 'cli';
        }

        if (defined('REST_REQUEST') && REST_REQUEST) {
            return 'rest';
        }

        if (defined('DOING_CRON') && DOING_CRON) {
            return 'cron';
        }

        if (is_admin()) {
            return 'admin';
        }

        return 'frontend';
    }

    /**
     * Get client IP address.
     *
     * @return string|null
     */
    private function get_client_ip(): ?string
    {
        $ip_storage = $this->settings['ip_storage'] ?? 'full';

        if ($ip_storage === 'none') {
            return null;
        }

        $ip = '';

        // Check for proxy headers if enabled
        if (!empty($this->settings['trust_proxy_headers'])) {
            $headers = [
                'HTTP_X_FORWARDED_FOR',
                'HTTP_X_REAL_IP',
                'HTTP_CLIENT_IP',
            ];

            foreach ($headers as $header) {
                if (!empty($_SERVER[$header])) {
                    $ips = explode(',', sanitize_text_field(wp_unslash($_SERVER[$header])));
                    $ip = trim($ips[0]);
                    break;
                }
            }
        }

        if (empty($ip) && !empty($_SERVER['REMOTE_ADDR'])) {
            $ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
        }

        if (empty($ip)) {
            return null;
        }

        // Apply masking if configured
        if ($ip_storage === 'masked') {
            return $this->mask_ip($ip);
        }

        if ($ip_storage === 'hashed') {
            return wp_hash($ip);
        }

        return $ip;
    }

    /**
     * Mask IP address (last octet for IPv4, last 80 bits for IPv6).
     *
     * @param string $ip IP address.
     * @return string
     */
    private function mask_ip(string $ip): string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return preg_replace('/\.\d+$/', '.xxx', $ip) ?? $ip;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return preg_replace('/:[^:]+:[^:]+:[^:]+:[^:]+:[^:]+$/', ':xxxx:xxxx:xxxx:xxxx:xxxx', $ip) ?? $ip;
        }

        return $ip;
    }

    /**
     * Get user agent string.
     *
     * @return string|null
     */
    private function get_user_agent(): ?string
    {
        if (empty($this->settings['store_user_agent'])) {
            return null;
        }

        if (!empty($_SERVER['HTTP_USER_AGENT'])) {
            return sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT']));
        }

        return null;
    }
}
