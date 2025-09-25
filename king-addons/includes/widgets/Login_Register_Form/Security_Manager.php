<?php

namespace King_Addons\Widgets\Login_Register_Form;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Security Manager for Login Register Form widget
 * Handles rate limiting, file validation, and other security measures
 */
class Security_Manager
{
    /**
     * Rate limiting settings
     */
    const MAX_LOGIN_ATTEMPTS = 5;
    const MAX_REGISTER_ATTEMPTS = 3;
    const MAX_LOST_PASSWORD_ATTEMPTS = 3;
    const LOCKOUT_DURATION = 900; // 15 minutes in seconds
    const ALLOWED_FILE_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'text/plain'];
    const MAX_FILE_SIZE = 5242880; // 5MB in bytes

    /**
     * Check if IP is rate limited for specific action
     */
    public static function is_rate_limited($action, $ip_address = null)
    {
        if (!$ip_address) {
            $ip_address = self::get_client_ip();
        }

        $transient_key = "king_addons_{$action}_attempts_" . md5($ip_address);
        $attempts = get_transient($transient_key);

        $max_attempts = self::get_max_attempts($action);
        
        return $attempts !== false && $attempts >= $max_attempts;
    }

    /**
     * Record a failed attempt
     */
    public static function record_failed_attempt($action, $ip_address = null)
    {
        if (!$ip_address) {
            $ip_address = self::get_client_ip();
        }

        $transient_key = "king_addons_{$action}_attempts_" . md5($ip_address);
        $attempts = get_transient($transient_key);
        
        if ($attempts === false) {
            $attempts = 0;
        }
        
        $attempts++;
        set_transient($transient_key, $attempts, self::LOCKOUT_DURATION);

        // Log security event
        error_log("King Addons Security: Failed {$action} attempt #{$attempts} from IP {$ip_address}");
        
        return $attempts;
    }

    /**
     * Clear failed attempts (on successful login/registration)
     */
    public static function clear_failed_attempts($action, $ip_address = null)
    {
        if (!$ip_address) {
            $ip_address = self::get_client_ip();
        }

        $transient_key = "king_addons_{$action}_attempts_" . md5($ip_address);
        delete_transient($transient_key);
    }

    /**
     * Get remaining lockout time
     */
    public static function get_remaining_lockout_time($action, $ip_address = null)
    {
        if (!$ip_address) {
            $ip_address = self::get_client_ip();
        }

        $transient_key = "king_addons_{$action}_attempts_" . md5($ip_address);
        $expiration = get_option('_transient_timeout_' . $transient_key);
        
        if ($expiration === false) {
            return 0;
        }
        
        $remaining = $expiration - time();
        return max(0, $remaining);
    }

    /**
     * Validate uploaded file
     */
    public static function validate_file_upload($file_data)
    {
        // Check if file was uploaded
        if (empty($file_data['name']) || empty($file_data['tmp_name'])) {
            return [
                'valid' => false,
                'error' => esc_html__('No file uploaded.', 'king-addons')
            ];
        }

        // Check file size
        if ($file_data['size'] > self::MAX_FILE_SIZE) {
            return [
                'valid' => false,
                'error' => sprintf(
                    esc_html__('File size exceeds maximum allowed size of %s.', 'king-addons'),
                    size_format(self::MAX_FILE_SIZE)
                )
            ];
        }

        // Check MIME type
        $file_type = wp_check_filetype($file_data['name']);
        if (!$file_type['type'] || !in_array($file_type['type'], self::ALLOWED_FILE_TYPES)) {
            return [
                'valid' => false,
                'error' => esc_html__('File type not allowed. Please upload images (JPG, PNG, GIF), PDF, or text files only.', 'king-addons')
            ];
        }

        // Additional security checks
        $real_mime = mime_content_type($file_data['tmp_name']);
        if ($real_mime && $real_mime !== $file_type['type']) {
            return [
                'valid' => false,
                'error' => esc_html__('File type mismatch detected. Upload rejected for security.', 'king-addons')
            ];
        }

        // Check for malicious content in text files (limit file size to prevent DoS)
        if (in_array($file_type['type'], ['text/plain', 'application/pdf'])) {
            // Security fix: Check file size before reading to prevent DoS
            $file_size = filesize($file_data['tmp_name']);
            if ($file_size > 1024 * 1024) { // 1MB limit for content scanning
                return [
                    'valid' => false,
                    'error' => esc_html__('File too large for content scanning.', 'king-addons')
                ];
            }

            $content = file_get_contents($file_data['tmp_name']);
            if (self::contains_malicious_content($content)) {
                return [
                    'valid' => false,
                    'error' => esc_html__('File contains suspicious content and cannot be uploaded.', 'king-addons')
                ];
            }
        }

        return ['valid' => true];
    }

    /**
     * Sanitize social login data
     */
    public static function sanitize_social_data($data, $provider)
    {
        $sanitized = [];
        
        // Basic required fields
        $sanitized['email'] = isset($data['email']) ? sanitize_email($data['email']) : '';
        $sanitized['name'] = isset($data['name']) ? sanitize_text_field($data['name']) : '';
        $sanitized['provider_id'] = isset($data['id']) ? sanitize_text_field($data['id']) : '';
        
        // Optional fields
        $sanitized['first_name'] = isset($data['given_name']) ? sanitize_text_field($data['given_name']) : '';
        $sanitized['last_name'] = isset($data['family_name']) ? sanitize_text_field($data['family_name']) : '';
        
        // Picture URL with strict validation
        if (isset($data['picture'])) {
            $picture_url = esc_url_raw($data['picture']);
            // Additional validation for picture URL
            if (filter_var($picture_url, FILTER_VALIDATE_URL) && self::is_safe_image_url($picture_url)) {
                $sanitized['picture'] = $picture_url;
            } else {
                $sanitized['picture'] = '';
            }
        } else {
            $sanitized['picture'] = '';
        }

        // Validate email domain for additional security
        if (!empty($sanitized['email']) && !self::is_safe_email_domain($sanitized['email'])) {
            error_log("King Addons Security: Suspicious email domain from {$provider}: {$sanitized['email']}");
        }

        return $sanitized;
    }

    /**
     * Check for suspicious patterns in registration data
     */
    public static function detect_suspicious_registration($data)
    {
        $suspicious_patterns = [
            // Common spam patterns
            '/\b(viagra|cialis|casino|poker|lottery|winner|congratulations)\b/i',
            // Suspicious email patterns
            '/\b\d{10,}@/', // Long numeric sequences in email
            // Bot-like usernames
            '/^(user|test|admin)\d+$/i',
        ];

        $text_to_check = implode(' ', [
            $data['username'] ?? '',
            $data['email'] ?? '',
            $data['first_name'] ?? '',
            $data['last_name'] ?? ''
        ]);

        foreach ($suspicious_patterns as $pattern) {
            if (preg_match($pattern, $text_to_check)) {
                error_log("King Addons Security: Suspicious registration pattern detected: {$pattern}");
                return true;
            }
        }

        return false;
    }

    /**
     * Enhanced password strength validation
     */
    public static function validate_password_strength($password)
    {
        $strength = [
            'score' => 0,
            'feedback' => [],
            'valid' => true
        ];

        // Basic length check
        if (strlen($password) < 8) {
            $strength['valid'] = false;
            $strength['feedback'][] = esc_html__('Password must be at least 8 characters long.', 'king-addons');
            return $strength;
        }

        // Check for character variety
        $patterns = [
            'lowercase' => '/[a-z]/',
            'uppercase' => '/[A-Z]/',
            'numbers' => '/\d/',
            'special' => '/[!@#$%^&*(),.?":{}|<>]/'
        ];

        foreach ($patterns as $type => $pattern) {
            if (preg_match($pattern, $password)) {
                $strength['score']++;
            }
        }

        // Check against common passwords
        if (self::is_common_password($password)) {
            $strength['valid'] = false;
            $strength['feedback'][] = esc_html__('This password is too common. Please choose a more unique password.', 'king-addons');
        }

        // Length bonus
        if (strlen($password) >= 12) {
            $strength['score']++;
        }

        // Determine if password is strong enough
        if ($strength['score'] < 3) {
            $strength['valid'] = false;
            $strength['feedback'][] = esc_html__('Password should contain a mix of uppercase, lowercase, numbers, and special characters.', 'king-addons');
        }

        return $strength;
    }

    /**
     * Private helper methods
     */
    private static function get_client_ip()
    {
        $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = trim($_SERVER[$key]);
                // Handle comma-separated IPs (from load balancers)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    private static function get_max_attempts($action)
    {
        switch ($action) {
            case 'login':
                return self::MAX_LOGIN_ATTEMPTS;
            case 'register':
                return self::MAX_REGISTER_ATTEMPTS;
            case 'lostpassword':
                return self::MAX_LOST_PASSWORD_ATTEMPTS;
            default:
                return 3;
        }
    }

    private static function contains_malicious_content($content)
    {
        $malicious_patterns = [
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
            '/javascript:/i',
            '/data:text\/html/i',
            '/\bon\w+\s*=/i', // Event handlers like onclick
            '/eval\s*\(/i',
            '/exec\s*\(/i'
        ];

        foreach ($malicious_patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    private static function is_safe_image_url($url)
    {
        // Only allow images from trusted domains
        $trusted_domains = [
            'lh3.googleusercontent.com', // Google profile pictures
            'platform-lookaside.fbsbx.com', // Facebook profile pictures
            'graph.facebook.com', // Facebook graph API
            'scontent.xx.fbcdn.net' // Facebook CDN
        ];

        $parsed_url = parse_url($url);
        $domain = $parsed_url['host'] ?? '';

        return in_array($domain, $trusted_domains);
    }

    private static function is_safe_email_domain($email)
    {
        // Check against known suspicious domains
        $suspicious_domains = [
            'guerrillamail.com',
            '10minutemail.com',
            'mailinator.com',
            'tempmail.org'
        ];

        $domain = substr(strrchr($email, "@"), 1);
        return !in_array(strtolower($domain), $suspicious_domains);
    }

    private static function is_common_password($password)
    {
        $common_passwords = [
            'password', '123456', '123456789', 'qwerty', 'abc123',
            'password123', 'admin', 'letmein', 'welcome', 'monkey',
            'dragon', 'master', 'sunshine', 'princess', 'football'
        ];

        return in_array(strtolower($password), $common_passwords);
    }
} 