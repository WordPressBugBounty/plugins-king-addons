<?php

namespace King_Addons\Widgets\Login_Register_Form;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Email Handler for Login Register Form widget
 */
class Email_Handler
{
    /**
     * Send registration emails
     */
    public static function send_registration_emails($user_id, $settings)
    {
        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }

        // Prepare placeholders
        $placeholders = self::get_email_placeholders($user);

        // Send user email
        if ($settings['enable_user_email'] === 'yes') {
            self::send_user_email($user, $settings, $placeholders);
        }

        // Send admin email
        if ($settings['enable_admin_email'] === 'yes') {
            self::send_admin_email($user, $settings, $placeholders);
        }
    }

    /**
     * Send welcome email to user
     */
    private static function send_user_email($user, $settings, $placeholders)
    {
        $subject = self::replace_placeholders($settings['user_email_subject'], $placeholders);
        $content = self::replace_placeholders($settings['user_email_content'], $placeholders);
        
        // Convert line breaks to HTML
        $content = nl2br($content);
        
        // Set content type to HTML
        add_filter('wp_mail_content_type', [__CLASS__, 'set_html_content_type']);

        $headers = array(
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
        );

        $sent = wp_mail($user->user_email, $subject, $content, $headers);
        
        // Reset content type
        remove_filter('wp_mail_content_type', [__CLASS__, 'set_html_content_type']);

        return $sent;
    }

    /**
     * Send notification email to admin
     */
    private static function send_admin_email($user, $settings, $placeholders)
    {
        $admin_email = !empty($settings['admin_email_address']) ? $settings['admin_email_address'] : get_option('admin_email');
        
        if (empty($admin_email)) {
            return false;
        }

        $subject = self::replace_placeholders($settings['admin_email_subject'], $placeholders);
        $content = self::replace_placeholders($settings['admin_email_content'], $placeholders);
        
        // Convert line breaks to HTML
        $content = nl2br($content);
        
        // Set content type to HTML
        add_filter('wp_mail_content_type', [__CLASS__, 'set_html_content_type']);

        $headers = array(
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
        );

        $sent = wp_mail($admin_email, $subject, $content, $headers);
        
        // Reset content type
        remove_filter('wp_mail_content_type', [__CLASS__, 'set_html_content_type']);

        return $sent;
    }

    /**
     * Get email placeholders
     */
    private static function get_email_placeholders($user)
    {
        $user_roles = $user->roles;
        $user_role_name = '';
        
        if (!empty($user_roles)) {
            global $wp_roles;
            if (!isset($wp_roles)) {
                $wp_roles = new \WP_Roles();
            }
            $role_key = $user_roles[0];
            $user_role_name = isset($wp_roles->roles[$role_key]) ? $wp_roles->roles[$role_key]['name'] : $role_key;
        }

        $user_name = trim($user->first_name . ' ' . $user->last_name);
        if (empty($user_name)) {
            $user_name = $user->display_name;
        }

        return [
            '{site_name}' => get_bloginfo('name'),
            '{user_name}' => $user_name,
            '{username}' => $user->user_login,
            '{user_email}' => $user->user_email,
            '{user_role}' => $user_role_name,
            '{registration_date}' => date_i18n(get_option('date_format') . ' ' . get_option('time_format')),
        ];
    }

    /**
     * Replace placeholders in text
     */
    private static function replace_placeholders($text, $placeholders)
    {
        return str_replace(array_keys($placeholders), array_values($placeholders), $text);
    }

    /**
     * Set HTML content type for emails
     */
    public static function set_html_content_type()
    {
        return 'text/html';
    }
} 