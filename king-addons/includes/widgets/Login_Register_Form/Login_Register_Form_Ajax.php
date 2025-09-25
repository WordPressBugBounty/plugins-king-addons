<?php

namespace King_Addons\Widgets\Login_Register_Form;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Include Security Manager
require_once KING_ADDONS_PATH . 'includes/widgets/Login_Register_Form/Security_Manager.php';

/**
 * AJAX handlers for Login Register Form widget
 */
class Login_Register_Form_Ajax
{
    /**
     * Handle login AJAX request
     */
    public static function handle_login_ajax()
    {
        // Check if it's a POST request
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            wp_send_json_error(['message' => esc_html__('Invalid request method.', 'king-addons')]);
        }
        
        // Check rate limiting first
        if (Security_Manager::is_rate_limited('login')) {
            $remaining_time = Security_Manager::get_remaining_lockout_time('login');
            wp_send_json_error([
                'message' => sprintf(
                    esc_html__('Too many failed login attempts. Please try again in %d minutes.', 'king-addons'),
                    ceil($remaining_time / 60)
                )
            ]);
        }
        
        // Check if required POST data exists
        if (!isset($_POST['nonce'])) {
            wp_send_json_error(['message' => esc_html__('Missing security token.', 'king-addons')]);
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'king_addons_login_action')) {
            wp_send_json_error(['message' => esc_html__('Security check failed.', 'king-addons')]);
        }

        // Check if required fields are present
        if (!isset($_POST['username']) || !isset($_POST['password'])) {
            wp_send_json_error(['message' => esc_html__('Missing required fields.', 'king-addons')]);
        }
        
        $username = sanitize_text_field($_POST['username']);
        $password = $_POST['password'];
        $remember = isset($_POST['remember']) ? true : false;

        if (empty($username) || empty($password)) {
            wp_send_json_error(['message' => esc_html__('Please fill in all required fields.', 'king-addons')]);
        }

        // Validate reCAPTCHA if present
        if (isset($_POST['g-recaptcha-response']) && !empty($_POST['g-recaptcha-response'])) {
            $recaptcha_response = sanitize_text_field($_POST['g-recaptcha-response']);
            $widget_id = isset($_POST['widget_id']) ? sanitize_text_field($_POST['widget_id']) : '';
            
            if (!self::verify_recaptcha($recaptcha_response, $widget_id)) {
                wp_send_json_error(['message' => esc_html__('reCAPTCHA verification failed. Please try again.', 'king-addons')]);
            }
        }

        $credentials = [
            'user_login' => $username,
            'user_password' => $password,
            'remember' => $remember,
        ];

        $user = wp_signon($credentials);

        if (is_wp_error($user)) {
            // Record failed login attempt
            Security_Manager::record_failed_attempt('login');
            wp_send_json_error(['message' => esc_html__('Invalid credentials. Please try again.', 'king-addons')]);
        }

        // Clear failed attempts on successful login
        Security_Manager::clear_failed_attempts('login');

        wp_send_json_success([
            'message' => esc_html__('Login successful! Redirecting...', 'king-addons'),
            'redirect' => self::get_redirect_url('login'),
        ]);
    }

    /**
     * Handle registration AJAX request
     */
    public static function handle_register_ajax()
    {
        // Check if user registration is enabled
        if (!get_option('users_can_register')) {
            wp_send_json_error(['message' => esc_html__('User registration is disabled.', 'king-addons')]);
        }

        // Check premium features in free version
        if (!king_addons_freemius()->can_use_premium_code__premium_only()) {
            // Block custom fields in free version
            if (isset($_POST['custom_fields']) && !empty($_POST['custom_fields'])) {
                wp_send_json_error(['message' => esc_html__('Custom fields are only available in King Addons Pro. Please upgrade to use this feature.', 'king-addons')]);
            }
            
            // Block Mailchimp integration in free version
            if (isset($_POST['enable_mailchimp_integration']) && $_POST['enable_mailchimp_integration'] === 'yes') {
                wp_send_json_error(['message' => esc_html__('Mailchimp integration is only available in King Addons Pro. Please upgrade to use this feature.', 'king-addons')]);
            }
            
            // Block admin emails in free version
            if (isset($_POST['enable_admin_email']) && $_POST['enable_admin_email'] === 'yes') {
                wp_send_json_error(['message' => esc_html__('Admin email notifications are only available in King Addons Pro. Please upgrade to use this feature.', 'king-addons')]);
            }
        }

        // Check rate limiting first
        if (Security_Manager::is_rate_limited('register')) {
            $remaining_time = Security_Manager::get_remaining_lockout_time('register');
            wp_send_json_error([
                'message' => sprintf(
                    esc_html__('Too many registration attempts. Please try again in %d minutes.', 'king-addons'),
                    ceil($remaining_time / 60)
                )
            ]);
        }

        // Check if it's a POST request
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            wp_send_json_error(['message' => esc_html__('Invalid request method.', 'king-addons')]);
        }
        
        // Check if required POST data exists
        if (!isset($_POST['nonce'])) {
            wp_send_json_error(['message' => esc_html__('Missing security token.', 'king-addons')]);
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'king_addons_register_action')) {
            wp_send_json_error(['message' => esc_html__('Security check failed.', 'king-addons')]);
        }

        // Check if required fields are present
        if (!isset($_POST['email']) || !isset($_POST['username']) || !isset($_POST['password']) || !isset($_POST['confirm_password'])) {
            wp_send_json_error(['message' => esc_html__('Missing required fields.', 'king-addons')]);
        }

        $email = sanitize_email($_POST['email']);
        $username = sanitize_text_field($_POST['username']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Additional fields - moved user_role here to fix undefined variable error
        // Security fix: Only allow specific roles to prevent privilege escalation
        $allowed_roles = ['subscriber', 'customer']; // Add more safe roles as needed
        $requested_role = isset($_POST['user_role']) ? sanitize_text_field($_POST['user_role']) : 'subscriber';
        $user_role = in_array($requested_role, $allowed_roles, true) ? $requested_role : 'subscriber';
        $first_name = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
        $last_name = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';
        $website = isset($_POST['website']) ? esc_url_raw($_POST['website']) : '';
        $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
        $terms_conditions = isset($_POST['terms_conditions']) ? (bool) $_POST['terms_conditions'] : false;
        
        // Process custom fields from repeater (Pro only)
        $custom_fields = [];
        
        if (king_addons_freemius()->can_use_premium_code__premium_only()) {
            // Handle JSON data from FormData
            $custom_fields_data = null;
            $custom_field_labels_data = null;
            
            if (isset($_POST['custom_fields'])) {
                if (is_string($_POST['custom_fields'])) {
                    // JSON string from FormData
                    $custom_fields_data = json_decode(stripslashes($_POST['custom_fields']), true);
                } elseif (is_array($_POST['custom_fields'])) {
                    // Regular array
                    $custom_fields_data = $_POST['custom_fields'];
                }
            }
            
            if (isset($_POST['custom_field_labels'])) {
                if (is_string($_POST['custom_field_labels'])) {
                    // JSON string from FormData
                    $custom_field_labels_data = json_decode(stripslashes($_POST['custom_field_labels']), true);
                } elseif (is_array($_POST['custom_field_labels'])) {
                    // Regular array
                    $custom_field_labels_data = $_POST['custom_field_labels'];
                }
            }
            
            if (!empty($custom_fields_data) && is_array($custom_fields_data)) {
                // Get field labels if available
                $field_labels = !empty($custom_field_labels_data) && is_array($custom_field_labels_data) 
                    ? $custom_field_labels_data 
                    : [];
                    
                foreach ($custom_fields_data as $field_name => $field_value) {
                    $sanitized_name = sanitize_key($field_name);
                    $field_label = isset($field_labels[$field_name]) ? sanitize_text_field($field_labels[$field_name]) : '';
                    
                    // Sanitize value based on field type
                    if (is_array($field_value)) {
                        // For checkbox arrays or multi-select
                        $sanitized_value = array_map('sanitize_text_field', $field_value);
                    } else {
                        // Determine sanitization based on field name or value type
                        if (filter_var($field_value, FILTER_VALIDATE_EMAIL)) {
                            $sanitized_value = sanitize_email($field_value);
                        } elseif (filter_var($field_value, FILTER_VALIDATE_URL)) {
                            $sanitized_value = esc_url_raw($field_value);
                        } elseif (strlen($field_value) > 100) {
                            // Long text, treat as textarea
                            $sanitized_value = sanitize_textarea_field($field_value);
                        } else {
                            // Default text field
                            $sanitized_value = sanitize_text_field($field_value);
                        }
                    }
                    
                    if (!empty($sanitized_value)) {
                        $custom_fields[$sanitized_name] = [
                            'value' => $sanitized_value,
                            'label' => $field_label
                        ];
                    }
                }
            }
        }
        
        // Process file uploads from custom fields with enhanced security
        if (isset($_FILES) && !empty($_FILES)) {
            foreach ($_FILES as $file_key => $file_data) {
                if (strpos($file_key, 'custom_field_') === 0 && !empty($file_data['name'])) {
                    // Validate file before upload
                    $file_validation = Security_Manager::validate_file_upload($file_data);
                    if (!$file_validation['valid']) {
                        wp_send_json_error(['message' => $file_validation['error']]);
                    }
                    
                    // Handle secure file upload
                    if (!function_exists('wp_handle_upload')) {
                        require_once(ABSPATH . 'wp-admin/includes/file.php');
                    }
                    
                    $upload_overrides = [
                        'test_form' => false,
                        'unique_filename_callback' => function($dir, $name, $ext) {
                            // Generate unique filename to prevent conflicts and enumeration
                            return wp_generate_uuid4() . $ext;
                        }
                    ];
                    
                    $uploaded_file = wp_handle_upload($file_data, $upload_overrides);
                    
                    if (!isset($uploaded_file['error'])) {
                        $custom_fields[sanitize_key($file_key)] = [
                            'value' => $uploaded_file['url'],
                            'label' => ucwords(str_replace(['custom_field_', '_'], ['', ' '], $file_key))
                        ];
                    } else {
                        wp_send_json_error(['message' => esc_html__('File upload failed. Please try again.', 'king-addons')]);
                    }
                }
            }
        }


        // Validate required fields
        if (empty($email) || empty($username) || empty($password) || empty($confirm_password)) {
            wp_send_json_error(['message' => esc_html__('Please fill in all required fields.', 'king-addons')]);
        }

        // Validate email
        if (!is_email($email)) {
            wp_send_json_error(['message' => esc_html__('Please enter a valid email address.', 'king-addons')]);
        }

        // Check password confirmation
        if ($password !== $confirm_password) {
            wp_send_json_error(['message' => esc_html__('Passwords do not match.', 'king-addons')]);
        }

        // Enhanced password strength validation
        $password_validation = Security_Manager::validate_password_strength($password);
        if (!$password_validation['valid']) {
            wp_send_json_error(['message' => implode(' ', $password_validation['feedback'])]);
        }

        // Check for suspicious registration patterns
        $registration_data = [
            'username' => $username,
            'email' => $email,
            'first_name' => $first_name,
            'last_name' => $last_name
        ];
        
        if (Security_Manager::detect_suspicious_registration($registration_data)) {
            Security_Manager::record_failed_attempt('register');
            wp_send_json_error(['message' => esc_html__('Registration failed. Please try again later.', 'king-addons')]);
        }

        // Validate reCAPTCHA if present
        if (isset($_POST['g-recaptcha-response']) && !empty($_POST['g-recaptcha-response'])) {
            $recaptcha_response = sanitize_text_field($_POST['g-recaptcha-response']);
            $widget_id = isset($_POST['widget_id']) ? sanitize_text_field($_POST['widget_id']) : '';
            
            if (!self::verify_recaptcha($recaptcha_response, $widget_id)) {
                wp_send_json_error(['message' => esc_html__('reCAPTCHA verification failed. Please try again.', 'king-addons')]);
            }
        }

        // Validate Terms & Conditions if required
        if (isset($_POST['terms_required']) && $_POST['terms_required'] === 'yes' && !$terms_conditions) {
            wp_send_json_error(['message' => esc_html__('Please accept the Terms & Conditions to continue.', 'king-addons')]);
        }

        // Check if username or email already exists (unified message to prevent enumeration)
        if (username_exists($username) || email_exists($email)) {
            Security_Manager::record_failed_attempt('register');
            wp_send_json_error(['message' => esc_html__('A user account with this information already exists. Please try different credentials.', 'king-addons')]);
        }

        // Create user with additional data
        $user_data = array(
            'user_login' => $username,
            'user_email' => $email,
            'user_pass' => $password,
        );

        // Add additional standard fields if provided
        if (!empty($first_name)) {
            $user_data['first_name'] = $first_name;
        }
        
        if (!empty($last_name)) {
            $user_data['last_name'] = $last_name;
        }
        
        if (!empty($website)) {
            $user_data['user_url'] = $website;
        }

        // Set user role if provided
        if (!empty($user_role) && $user_role !== 'subscriber') {
            $user_data['role'] = $user_role;
        }

        $user_id = wp_insert_user($user_data);

        if (is_wp_error($user_id)) {
            wp_send_json_error(['message' => $user_id->get_error_message()]);
        }

        // Update custom meta fields
        if (!empty($phone)) {
            update_user_meta($user_id, 'phone', $phone);
        }

        // Save custom fields from repeater to user meta
        if (!empty($custom_fields)) {
            foreach ($custom_fields as $field_name => $field_data) {
                // Store with prefix to avoid conflicts
                $meta_key = 'king_addons_' . $field_name;
                update_user_meta($user_id, $meta_key, $field_data['value']);
                
                // Also save the field label for display purposes
                $label_key = 'king_addons_' . $field_name . '_label';
                update_user_meta($user_id, $label_key, $field_data['label']);
            }
            
            // Log for debugging
            error_log('King Addons: Saved custom fields for user ' . $user_id . ': ' . print_r($custom_fields, true));
        }

        // Try to send email notifications if widget_id is provided
        if (isset($_POST['widget_id'])) {
            $widget_id = sanitize_text_field($_POST['widget_id']);
            self::try_send_registration_emails($user_id, $widget_id);
        }

        // Try Mailchimp integration if enabled
        if (isset($_POST['widget_id'])) {
            self::try_mailchimp_integration($user_id, $email, $first_name, $last_name);
        }

        // Check if we should auto-login after registration
        $auto_login = isset($_POST['auto_login_after_register']) && $_POST['auto_login_after_register'] === 'yes';

        if (!$auto_login) {
            wp_send_json_success([
                'message' => esc_html__('Registration successful! Please log in.', 'king-addons'),
                'redirect' => false,
                'auto_login' => false,
            ]);
            return;
        }
        
        // Auto-login the user
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);

        wp_send_json_success([
            'message' => esc_html__('Registration successful! Welcome!', 'king-addons'),
            'redirect' => self::get_redirect_url('register'),
            'auto_login' => true,
        ]);
    }

    /**
     * Handle lost password AJAX request
     */
    public static function handle_lostpassword_ajax()
    {
        // Check rate limiting first
        if (Security_Manager::is_rate_limited('lostpassword')) {
            $remaining_time = Security_Manager::get_remaining_lockout_time('lostpassword');
            wp_send_json_error([
                'message' => sprintf(
                    esc_html__('Too many password reset attempts. Please try again in %d minutes.', 'king-addons'),
                    ceil($remaining_time / 60)
                )
            ]);
        }

        // Check if it's a POST request
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            wp_send_json_error(['message' => esc_html__('Invalid request method.', 'king-addons')]);
        }
        
        // Check if required POST data exists
        if (!isset($_POST['nonce'])) {
            wp_send_json_error(['message' => esc_html__('Missing security token.', 'king-addons')]);
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'king_addons_lostpassword_action')) {
            wp_send_json_error(['message' => esc_html__('Security check failed.', 'king-addons')]);
        }

        // Check if required fields are present
        if (!isset($_POST['user_login'])) {
            wp_send_json_error(['message' => esc_html__('Missing required fields.', 'king-addons')]);
        }

        $user_login = sanitize_text_field($_POST['user_login']);

        if (empty($user_login)) {
            wp_send_json_error(['message' => esc_html__('Please fill in all required fields.', 'king-addons')]);
        }

        // Check if user exists
        if (strpos($user_login, '@')) {
            $user_data = get_user_by('email', $user_login);
        } else {
            $user_data = get_user_by('login', $user_login);
        }

        if (!$user_data) {
            // Record failed attempt but don't reveal if user exists (prevent enumeration)
            Security_Manager::record_failed_attempt('lostpassword');
            wp_send_json_error(['message' => esc_html__('If an account with this information exists, a password reset email has been sent.', 'king-addons')]);
        }

        // Generate password reset key
        $reset_key = get_password_reset_key($user_data);

        if (is_wp_error($reset_key)) {
            wp_send_json_error(['message' => $reset_key->get_error_message()]);
        }

        // Create reset URL
        $reset_url = network_site_url("wp-login.php?action=rp&key=$reset_key&login=" . rawurlencode($user_data->user_login), 'login');

        // Send email
        $message = esc_html__('Someone has requested a password reset for the following account:', 'king-addons') . "\r\n\r\n";
        $message .= network_home_url('/') . "\r\n\r\n";
        $message .= sprintf(esc_html__('Username: %s', 'king-addons'), $user_data->user_login) . "\r\n\r\n";
        $message .= esc_html__('If this was a mistake, just ignore this email and nothing will happen.', 'king-addons') . "\r\n\r\n";
        $message .= esc_html__('To reset your password, visit the following address:', 'king-addons') . "\r\n\r\n";
        $message .= $reset_url . "\r\n";

        $title = sprintf(esc_html__('[%s] Password Reset', 'king-addons'), wp_specialchars_decode(get_option('blogname'), ENT_QUOTES));

        if (wp_mail($user_data->user_email, $title, $message)) {
            wp_send_json_success([
                'message' => esc_html__('Check your email for the confirmation link.', 'king-addons'),
            ]);
        } else {
            wp_send_json_error(['message' => esc_html__('The email could not be sent. Please contact the site administrator.', 'king-addons')]);
        }
    }

    /**
     * Try to send registration emails
     */
    private static function try_send_registration_emails($user_id, $widget_id)
    {
        try {
            // Prepare settings from POST data (passed from frontend)
            $default_settings = [
                'enable_user_email' => sanitize_text_field($_POST['enable_user_email'] ?? 'yes'),
                'user_email_subject' => sanitize_text_field($_POST['user_email_subject'] ?? 'Welcome to ' . get_bloginfo('name') . '!'),
                'user_email_content' => sanitize_textarea_field($_POST['user_email_content'] ?? "Hello {user_name},\n\nWelcome to {site_name}!\n\nYour account has been successfully created.\n\nUsername: {username}\nEmail: {user_email}\n\nThank you for joining us!\n\nBest regards,\n{site_name} Team"),
                'enable_admin_email' => '', // Default to disabled for free users
                'admin_email_address' => sanitize_email($_POST['admin_email_address'] ?? get_option('admin_email')),
                'admin_email_subject' => sanitize_text_field($_POST['admin_email_subject'] ?? 'New User Registration on ' . get_bloginfo('name')),
                'admin_email_content' => sanitize_textarea_field($_POST['admin_email_content'] ?? "Hello Admin,\n\nA new user has registered on {site_name}.\n\nUser Details:\nName: {user_name}\nUsername: {username}\nEmail: {user_email}\nRole: {user_role}\nRegistration Date: {registration_date}\n\nYou can view the user profile in the admin dashboard.\n\nBest regards,\n{site_name}"),
            ];
            
            // Only enable admin emails for Pro users
            if (king_addons_freemius()->can_use_premium_code__premium_only()) {
                $default_settings['enable_admin_email'] = sanitize_text_field($_POST['enable_admin_email'] ?? '');
            }

            \King_Addons\Widgets\Login_Register_Form\Email_Handler::send_registration_emails($user_id, $default_settings);
        } catch (\Exception $e) {
            // Silently fail - don't break registration process
            error_log('King Addons Registration Email Error: ' . $e->getMessage());
        }
    }

    /**
     * Try Mailchimp integration if enabled
     */
    private static function try_mailchimp_integration($user_id, $email, $first_name, $last_name)
    {
        // Only allow Mailchimp integration for Pro users
        if (!king_addons_freemius()->can_use_premium_code__premium_only()) {
            return false;
        }
        
        // Check if Mailchimp integration is enabled
        if (empty($_POST['enable_mailchimp_integration']) || $_POST['enable_mailchimp_integration'] !== 'yes') {
            return false;
        }

        $api_key = sanitize_text_field($_POST['mailchimp_api_key'] ?? '');
        $list_id = sanitize_text_field($_POST['mailchimp_list_id'] ?? '');

        if (empty($api_key) || empty($list_id)) {
            error_log('King Addons Mailchimp: API Key or List ID not configured');
            return false;
        }

        // Extract datacenter from API key
        $datacenter = substr($api_key, strpos($api_key, '-') + 1);
        $url = "https://{$datacenter}.api.mailchimp.com/3.0/lists/{$list_id}/members";

        // Check if double opt-in is enabled
        $double_optin = isset($_POST['mailchimp_double_optin']) && $_POST['mailchimp_double_optin'] === 'yes';
        $status = $double_optin ? 'pending' : 'subscribed';

        $member_data = [
            'email_address' => $email,
            'status' => $status,
            'merge_fields' => [
                'FNAME' => $first_name,
                'LNAME' => $last_name
            ]
        ];

        $response = wp_remote_post($url, [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode('user:' . $api_key),
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($member_data),
            'timeout' => 15
        ]);

        if (is_wp_error($response)) {
            error_log('King Addons Mailchimp: API request failed - ' . $response->get_error_message());
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code === 200) {
            error_log('King Addons Mailchimp: Successfully subscribed user ' . $user_id . ' to list ' . $list_id);
            return true;
        } else {
            $error_data = json_decode($response_body, true);
            $error_message = isset($error_data['detail']) ? $error_data['detail'] : 'Unknown error';
            error_log('King Addons Mailchimp: Subscription failed - ' . $error_message);
            return false;
        }
    }

    /**
     * Get redirect URL based on form type
     */
    private static function get_redirect_url($form_type)
    {
        // Check for custom redirect in POST data
        if ($form_type === 'login' && !empty($_POST['redirect_after_login'])) {
            return esc_url_raw($_POST['redirect_after_login']);
        }
        
        if ($form_type === 'register' && !empty($_POST['redirect_after_register'])) {
            return esc_url_raw($_POST['redirect_after_register']);
        }
        
        // Check for previous page redirect
        if (!empty($_POST['redirect_to'])) {
            return esc_url_raw($_POST['redirect_to']);
        }
        
        // Default redirects based on form type
        if ($form_type === 'login') {
            // For login, redirect to admin if user has admin capabilities, otherwise home
            if (current_user_can('manage_options')) {
                return admin_url();
            }
            return home_url('/my-account/');  // Common account page
        }
        
        if ($form_type === 'register') {
            // For registration, redirect to welcome/onboarding page or home
            return home_url('/welcome/');
        }
        
        // Fallback to current page or home
        return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : home_url();
    }

    /**
     * Verify reCAPTCHA response
     */
    private static function verify_recaptcha($recaptcha_response, $widget_id = '')
    {
        if (empty($recaptcha_response)) {
            return false;
        }
        
        // Get secret key from POST data
        $secret_key = sanitize_text_field($_POST['recaptcha_secret_key'] ?? '');
        if (empty($secret_key)) {
            // If no secret key configured, skip validation
            return true;
        }
        $verify_url = 'https://www.google.com/recaptcha/api/siteverify';
        
        $response = wp_remote_post($verify_url, [
            'body' => [
                'secret' => $secret_key,
                'response' => $recaptcha_response,
                'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
            ],
            'timeout' => 10
        ]);
        
        if (is_wp_error($response)) {
            error_log('reCAPTCHA verification error: ' . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        
        if (!$result || !isset($result['success'])) {
            return false;
        }
        
        // For reCAPTCHA v3, also check score threshold
        if (isset($result['score']) && isset($_POST['recaptcha_score_threshold'])) {
            $threshold = floatval($_POST['recaptcha_score_threshold'] ?? 0.5);
            return $result['success'] && ($result['score'] >= $threshold);
        }
        
        return $result['success'];
    }
    
    /**
     * Get widget settings for validation
     */
    private static function get_widget_settings($widget_id)
    {
        // Get settings from POST data (passed from frontend)
        $settings = [];
        
        // reCAPTCHA settings
        if (isset($_POST['widget_settings'])) {
            $widget_settings = $_POST['widget_settings'];
            
            if (is_array($widget_settings)) {
                $settings = array_map('sanitize_text_field', $widget_settings);
            }
        }
        
        // Alternative: get individual settings from POST
        $setting_keys = [
            'recaptcha_secret_key',
            'recaptcha_score_threshold',
            'enable_user_email',
            'user_email_subject',
            'user_email_content',
            'enable_admin_email',
            'admin_email_address',
            'admin_email_subject',
            'admin_email_content',
            'enable_mailchimp_integration',
            'mailchimp_api_key',
            'mailchimp_list_id',
            'redirect_after_login',
            'redirect_after_register',
            'terms_required'
        ];
        
        foreach ($setting_keys as $key) {
            if (isset($_POST[$key])) {
                $settings[$key] = sanitize_text_field($_POST[$key]);
            }
        }
        
        return $settings;
    }
} 