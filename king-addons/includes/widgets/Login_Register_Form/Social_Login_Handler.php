<?php

namespace King_Addons\Widgets\Login_Register_Form;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Include Security Manager
require_once KING_ADDONS_PATH . 'includes/widgets/Login_Register_Form/Security_Manager.php';

/**
 * Social Login Handler for Login Register Form widget
 */
class Social_Login_Handler
{
    /**
     * Initialize social login handlers
     */
    public static function init()
    {
        // Register AJAX handlers for social login callbacks
        add_action('wp_ajax_nopriv_king_addons_google_callback', [__CLASS__, 'handle_google_callback']);
        add_action('wp_ajax_king_addons_google_callback', [__CLASS__, 'handle_google_callback']);
        add_action('wp_ajax_nopriv_king_addons_facebook_callback', [__CLASS__, 'handle_facebook_callback']);
        add_action('wp_ajax_king_addons_facebook_callback', [__CLASS__, 'handle_facebook_callback']);
        
        // Frontend AJAX handlers
        add_action('wp_ajax_nopriv_king_addons_google_login', [__CLASS__, 'handle_google_login']);
        add_action('wp_ajax_king_addons_google_login', [__CLASS__, 'handle_google_login']);
        add_action('wp_ajax_nopriv_king_addons_facebook_login', [__CLASS__, 'handle_facebook_login']);
        add_action('wp_ajax_king_addons_facebook_login', [__CLASS__, 'handle_facebook_login']);
    }

    /**
     * Handle Google OAuth login
     */
    public static function handle_google_login()
    {
        // Only allow social login for Pro users
        if (!king_addons_freemius()->can_use_premium_code__premium_only()) {
            wp_send_json_error(['message' => esc_html__('Social login is only available in King Addons Pro. Please upgrade to use this feature.', 'king-addons')]);
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'king_addons_social_login_action')) {
            wp_send_json_error(['message' => esc_html__('Security check failed.', 'king-addons')]);
        }

        $google_token = sanitize_text_field($_POST['google_token'] ?? '');
        $widget_settings = self::get_widget_settings($_POST['widget_id'] ?? '');

        if (empty($google_token)) {
            wp_send_json_error(['message' => esc_html__('Google token is required.', 'king-addons')]);
        }

        $google_client_id = $widget_settings['google_client_id'] ?? '';
        if (empty($google_client_id)) {
            wp_send_json_error(['message' => esc_html__('Google Client ID not configured.', 'king-addons')]);
        }

        // Verify Google token
        $user_data = self::verify_google_token($google_token, $google_client_id);
        if (!$user_data) {
            wp_send_json_error(['message' => esc_html__('Google authentication failed.', 'king-addons')]);
        }

        // Process social login
        $result = self::process_social_login($user_data, 'google');
        
        if ($result['success']) {
            wp_send_json_success([
                'message' => esc_html__('Google login successful!', 'king-addons'),
                'redirect' => $result['redirect']
            ]);
        } else {
            wp_send_json_error(['message' => $result['message']]);
        }
    }

    /**
     * Handle Facebook OAuth login
     */
    public static function handle_facebook_login()
    {
        // Only allow social login for Pro users
        if (!king_addons_freemius()->can_use_premium_code__premium_only()) {
            wp_send_json_error(['message' => esc_html__('Social login is only available in King Addons Pro. Please upgrade to use this feature.', 'king-addons')]);
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'king_addons_social_login_action')) {
            wp_send_json_error(['message' => esc_html__('Security check failed.', 'king-addons')]);
        }

        $facebook_token = sanitize_text_field($_POST['facebook_token'] ?? '');
        $widget_settings = self::get_widget_settings($_POST['widget_id'] ?? '');

        if (empty($facebook_token)) {
            wp_send_json_error(['message' => esc_html__('Facebook token is required.', 'king-addons')]);
        }

        $facebook_app_id = $widget_settings['facebook_app_id'] ?? '';
        $facebook_app_secret = $widget_settings['facebook_app_secret'] ?? '';
        
        if (empty($facebook_app_id) || empty($facebook_app_secret)) {
            wp_send_json_error(['message' => esc_html__('Facebook App credentials not configured.', 'king-addons')]);
        }

        // Verify Facebook token
        $user_data = self::verify_facebook_token($facebook_token, $facebook_app_id, $facebook_app_secret);
        if (!$user_data) {
            wp_send_json_error(['message' => esc_html__('Facebook authentication failed.', 'king-addons')]);
        }

        // Process social login
        $result = self::process_social_login($user_data, 'facebook');
        
        if ($result['success']) {
            wp_send_json_success([
                'message' => esc_html__('Facebook login successful!', 'king-addons'),
                'redirect' => $result['redirect']
            ]);
        } else {
            wp_send_json_error(['message' => $result['message']]);
        }
    }

    /**
     * Verify Google OAuth token
     */
    private static function verify_google_token($token, $client_id)
    {
        // Security fix: Validate token format
        if (empty($token) || strlen($token) > 2048) {
            return false;
        }
        
        $url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($token);
        
        $response = wp_remote_get($url, [
            'timeout' => 15,
            'user-agent' => 'King Addons Social Login/1.0'
        ]);
        
        if (is_wp_error($response)) {
            error_log('King Addons Social Login: Google token verification failed: ' . $response->get_error_message());
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        // Verify the token is for our app
        if (!isset($data['aud']) || $data['aud'] !== $client_id) {
            return false;
        }

        // Return user data
        return [
            'email' => $data['email'] ?? '',
            'first_name' => $data['given_name'] ?? '',
            'last_name' => $data['family_name'] ?? '',
            'name' => $data['name'] ?? '',
            'picture' => $data['picture'] ?? '',
            'provider_id' => $data['sub'] ?? '',
        ];
    }

    /**
     * Verify Facebook OAuth token
     */
    private static function verify_facebook_token($token, $app_id, $app_secret)
    {
        // Security fix: Validate inputs
        if (empty($token) || empty($app_id) || empty($app_secret) || strlen($token) > 1024) {
            return false;
        }
        
        // First, verify the token
        $verify_url = "https://graph.facebook.com/debug_token?" . http_build_query([
            'input_token' => $token,
            'access_token' => $app_id . '|' . $app_secret
        ]);
        
        $response = wp_remote_get($verify_url, [
            'timeout' => 15,
            'user-agent' => 'King Addons Social Login/1.0'
        ]);
        
        if (is_wp_error($response)) {
            error_log('King Addons Social Login: Facebook token verification failed: ' . $response->get_error_message());
            return false;
        }

        $verify_data = json_decode(wp_remote_retrieve_body($response), true);
        if (!isset($verify_data['data']['is_valid']) || !$verify_data['data']['is_valid']) {
            return false;
        }

        // Get user data
        $user_url = "https://graph.facebook.com/me?" . http_build_query([
            'fields' => 'id,name,email,first_name,last_name,picture',
            'access_token' => $token
        ]);
        
        $user_response = wp_remote_get($user_url, [
            'timeout' => 15,
            'user-agent' => 'King Addons Social Login/1.0'
        ]);
        
        if (is_wp_error($user_response)) {
            error_log('King Addons Social Login: Facebook user data request failed: ' . $user_response->get_error_message());
            return false;
        }

        $user_data = json_decode(wp_remote_retrieve_body($user_response), true);
        
        return [
            'email' => $user_data['email'] ?? '',
            'first_name' => $user_data['first_name'] ?? '',
            'last_name' => $user_data['last_name'] ?? '',
            'name' => $user_data['name'] ?? '',
            'picture' => $user_data['picture']['data']['url'] ?? '',
            'provider_id' => $user_data['id'] ?? '',
        ];
    }

    /**
     * Process social login (create user or login existing)
     */
    private static function process_social_login($user_data, $provider)
    {
        // Sanitize social login data for security
        $sanitized_data = Security_Manager::sanitize_social_data($user_data, $provider);
        
        $email = $sanitized_data['email'];
        if (empty($email)) {
            return [
                'success' => false,
                'message' => esc_html__('Email is required for social login.', 'king-addons')
            ];
        }

        // Additional security checks for social login
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            error_log("King Addons Security: Invalid email from {$provider}: {$email}");
            return [
                'success' => false,
                'message' => esc_html__('Invalid email address from social provider.', 'king-addons')
            ];
        }

        // Check if user exists
        $user = get_user_by('email', $email);
        
        if ($user) {
            // User exists, log them in
            wp_set_current_user($user->ID);
            wp_set_auth_cookie($user->ID);
            
            // Update social provider info with sanitized data
            update_user_meta($user->ID, 'king_addons_social_provider', sanitize_text_field($provider));
            update_user_meta($user->ID, 'king_addons_social_provider_id', sanitize_text_field($sanitized_data['provider_id']));
            
        } else {
            // Create new user with sanitized data
            $username = self::generate_username($sanitized_data['name'] ?: $sanitized_data['email']);
            $password = wp_generate_password(16, true); // Stronger password
            
            $user_id = wp_create_user($username, $password, $email);
            if (is_wp_error($user_id)) {
                return [
                    'success' => false,
                    'message' => esc_html__('Unable to create user account.', 'king-addons')
                ];
            }

            // Update user meta with sanitized data
            if (!empty($sanitized_data['first_name'])) {
                update_user_meta($user_id, 'first_name', $sanitized_data['first_name']);
            }
            if (!empty($sanitized_data['last_name'])) {
                update_user_meta($user_id, 'last_name', $sanitized_data['last_name']);
            }

            // Store social provider info
            update_user_meta($user_id, 'king_addons_social_provider', $provider);
            update_user_meta($user_id, 'king_addons_social_provider_id', $sanitized_data['provider_id']);
            if (!empty($sanitized_data['picture'])) {
                update_user_meta($user_id, 'king_addons_social_picture', $sanitized_data['picture']);
            }

            // Log in the new user
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id);

            // Send welcome email
            wp_new_user_notification($user_id, null, 'user');
        }

        return [
            'success' => true,
            'redirect' => home_url()
        ];
    }

    /**
     * Generate unique username
     */
    private static function generate_username($base_name)
    {
        $username = sanitize_user($base_name);
        $username = preg_replace('/[^a-zA-Z0-9._-]/', '', $username);
        
        if (empty($username)) {
            $username = 'user';
        }

        $original_username = $username;
        $counter = 1;

        while (username_exists($username)) {
            $username = $original_username . $counter;
            $counter++;
        }

        return $username;
    }

    /**
     * Get widget settings from POST data
     */
    private static function get_widget_settings($widget_id)
    {
        // This would be enhanced to get actual widget settings
        // For now, return settings from POST data
        return [
            'google_client_id' => sanitize_text_field($_POST['google_client_id'] ?? ''),
            'google_client_secret' => sanitize_text_field($_POST['google_client_secret'] ?? ''),
            'facebook_app_id' => sanitize_text_field($_POST['facebook_app_id'] ?? ''),
            'facebook_app_secret' => sanitize_text_field($_POST['facebook_app_secret'] ?? ''),
        ];
    }

    /**
     * Handle Google OAuth callback (for future server-side flow)
     */
    public static function handle_google_callback()
    {
        // Placeholder for server-side OAuth flow
        wp_die('Google OAuth callback - not implemented yet');
    }

    /**
     * Handle Facebook OAuth callback (for future server-side flow)
     */
    public static function handle_facebook_callback()
    {
        // Placeholder for server-side OAuth flow
        wp_die('Facebook OAuth callback - not implemented yet');
    }
} 