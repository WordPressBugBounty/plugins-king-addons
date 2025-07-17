<?php

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;
use Elementor\Utils;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Login Register Form Widget for King Addons
 * 
 * @since 1.0.0
 */
class Login_Register_Form extends Widget_Base
{
    public function __construct($data = [], $args = null)
    {
        parent::__construct($data, $args);
    }

    public function get_name()
    {
        return 'king-addons-login-register-form';
    }

    public function get_title()
    {
        return esc_html__('Login | Register Form', 'king-addons');
    }

    public function get_icon()
    {
        return 'king-addons-icon king-addons-login-register-form';
    }

    public function get_categories()
    {
        return ['king-addons'];
    }

    public function get_keywords()
    {
        return ['login', 'register', 'form', 'user', 'authentication', 'sign in', 'sign up', 'king addons'];
    }

    public function get_script_depends()
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-login-register-form-script'];
    }

    public function get_style_depends()
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-login-register-form-style'];
    }

    public function get_custom_help_url()
    {
        return 'https://kingaddons.com/';
    }

    /**
     * Get user roles for select control
     */
    protected function get_user_roles()
    {
        global $wp_roles;
        
        if (!isset($wp_roles)) {
            $wp_roles = new \WP_Roles();
        }
        
        $roles = [];
        foreach ($wp_roles->roles as $role_key => $role) {
            $roles[$role_key] = $role['name'];
        }
        
        return $roles;
    }

    /**
     * Render form header with logo and illustration
     */
    protected function render_form_header($settings)
    {
        if ($settings['show_form_header'] !== 'yes') {
            return;
        }

        $has_logo = !empty($settings['form_logo']['url']);
        $has_illustration = !empty($settings['form_illustration']['url']);
        
        if (!$has_logo && !$has_illustration) {
            return;
        }

        $illustration_position = $settings['form_illustration_position'];
        $wrapper_class = 'king-addons-form-header';
        
        if ($has_illustration) {
            $wrapper_class .= ' king-addons-form-header-' . $illustration_position;
        }
        ?>
        <div class="<?php echo esc_attr($wrapper_class); ?>">
            <?php if ($has_illustration && in_array($illustration_position, ['left', 'top'])): ?>
                <div class="king-addons-form-illustration">
                    <img src="<?php echo esc_url($settings['form_illustration']['url']); ?>" 
                         alt="<?php echo esc_attr(get_post_meta($settings['form_illustration']['id'], '_wp_attachment_image_alt', true)); ?>">
                </div>
            <?php endif; ?>
            
            <div class="king-addons-form-header-content">
                <?php if ($has_logo): ?>
                    <div class="king-addons-form-header-logo">
                        <img src="<?php echo esc_url($settings['form_logo']['url']); ?>" 
                             alt="<?php echo esc_attr(get_post_meta($settings['form_logo']['id'], '_wp_attachment_image_alt', true)); ?>">
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($has_illustration && in_array($illustration_position, ['right', 'bottom'])): ?>
                <div class="king-addons-form-illustration">
                    <img src="<?php echo esc_url($settings['form_illustration']['url']); ?>" 
                         alt="<?php echo esc_attr(get_post_meta($settings['form_illustration']['id'], '_wp_attachment_image_alt', true)); ?>">
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render social login buttons
     */
    protected function render_social_login($settings, $context = 'login')
    {
        // Only render social login for Pro users
        if (!king_addons_freemius()->can_use_premium_code__premium_only()) {
            return;
        }
        
        if ($settings['enable_social_login'] !== 'yes') {
            return;
        }

        $show_google = $settings['enable_google_login'] === 'yes' && !empty($settings['google_client_id']);
        $show_facebook = $settings['enable_facebook_login'] === 'yes' && !empty($settings['facebook_app_id']);

        if (!$show_google && !$show_facebook) {
            return;
        }
        ?>
        <div class="king-addons-social-login">
            <?php if ($settings['social_login_separator'] === 'yes'): ?>
                <div class="king-addons-social-separator">
                    <span><?php echo esc_html($settings['social_login_heading'] ?? 'Or login with:'); ?></span>
                </div>
            <?php endif; ?>

            <div class="king-addons-social-buttons">
                <?php if ($show_google): ?>
                    <button type="button" class="king-addons-social-button king-addons-google-login"
                            data-client-id="<?php echo esc_attr($settings['google_client_id']); ?>"
                            data-context="<?php echo esc_attr($context); ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        <?php echo esc_html__('Continue with Google', 'king-addons'); ?>
                    </button>
                <?php endif; ?>

                <?php if ($show_facebook): ?>
                    <button type="button" class="king-addons-social-button king-addons-facebook-login"
                            data-app-id="<?php echo esc_attr($settings['facebook_app_id']); ?>"
                            data-app-secret="<?php echo esc_attr($settings['facebook_app_secret']); ?>"
                            data-context="<?php echo esc_attr($context); ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24">
                            <path fill="#1877F2" d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                        <?php echo esc_html__('Continue with Facebook', 'king-addons'); ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render custom fields
     */
    protected function render_custom_fields($settings, $show_labels = true)
    {
        // Only render custom fields for Pro users
        if (!king_addons_freemius()->can_use_premium_code__premium_only()) {
            return;
        }
        
        if (empty($settings['custom_fields'])) {
            return;
        }

        foreach ($settings['custom_fields'] as $index => $field) {
            $field_id = 'custom_field_' . $index;
            $field_name = 'custom_field_' . $index;
            $field_class = 'king-addons-form-field';
            $field_label = !empty($field['field_label']) ? $field['field_label'] : 'Field ' . ($index + 1);
            
            if ($field['field_width'] === 'half') {
                $field_class .= ' king-addons-field-half';
            }
            
            $required = $field['field_required'] === 'yes' ? 'required' : '';
            ?>
            <div class="<?php echo esc_attr($field_class); ?>" data-field-label="<?php echo esc_attr($field_label); ?>">
                <?php if ($show_labels && !empty($field['field_label'])): ?>
                    <label for="<?php echo esc_attr($field_id); ?>">
                        <?php echo esc_html($field['field_label']); ?>
                        <?php if ($required): ?><span class="required">*</span><?php endif; ?>
                    </label>
                <?php endif; ?>
                
                <?php
                switch ($field['field_type']) {
                    case 'textarea':
                        ?>
                        <textarea id="<?php echo esc_attr($field_id); ?>" 
                                  name="<?php echo esc_attr($field_name); ?>" 
                                  placeholder="<?php echo esc_attr($field['field_placeholder']); ?>" 
                                  <?php echo $required; ?>
                                  rows="4"></textarea>
                        <?php
                        break;
                        
                    case 'select':
                        ?>
                        <select id="<?php echo esc_attr($field_id); ?>" 
                                name="<?php echo esc_attr($field_name); ?>" 
                                <?php echo $required; ?>>
                            <option value=""><?php echo esc_html__('Select an option', 'king-addons'); ?></option>
                            <?php
                            $options = explode("\n", $field['field_options']);
                            foreach ($options as $option) {
                                $option = trim($option);
                                if (empty($option)) continue;
                                
                                $parts = explode('|', $option);
                                $value = trim($parts[0]);
                                $label = isset($parts[1]) ? trim($parts[1]) : $value;
                                ?>
                                <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                                <?php
                            }
                            ?>
                        </select>
                        <?php
                        break;
                        
                    case 'file':
                        ?>
                        <input type="file" 
                               id="<?php echo esc_attr($field_id); ?>" 
                               name="<?php echo esc_attr($field_name); ?>" 
                               <?php echo $required; ?>>
                        <?php
                        break;
                        
                    case 'checkbox':
                        ?>
                        <div class="king-addons-checkbox-group">
                            <?php
                            $options = explode("\n", $field['field_options']);
                            foreach ($options as $option_index => $option) {
                                $option = trim($option);
                                if (empty($option)) continue;
                                
                                $parts = explode('|', $option);
                                $value = trim($parts[0]);
                                $label = isset($parts[1]) ? trim($parts[1]) : $value;
                                $option_id = $field_id . '_' . $option_index;
                                ?>
                                <div class="king-addons-checkbox-option">
                                    <input type="checkbox" 
                                           id="<?php echo esc_attr($option_id); ?>" 
                                           name="<?php echo esc_attr($field_name); ?>" 
                                           value="<?php echo esc_attr($value); ?>"
                                           <?php echo $required; ?>>
                                    <label for="<?php echo esc_attr($option_id); ?>"><?php echo esc_html($label); ?></label>
                                </div>
                                <?php
                            }
                            ?>
                        </div>
                        <?php
                        break;
                        
                    case 'radio':
                        ?>
                        <div class="king-addons-radio-group">
                            <?php
                            $options = explode("\n", $field['field_options']);
                            foreach ($options as $option_index => $option) {
                                $option = trim($option);
                                if (empty($option)) continue;
                                
                                $parts = explode('|', $option);
                                $value = trim($parts[0]);
                                $label = isset($parts[1]) ? trim($parts[1]) : $value;
                                $option_id = $field_id . '_' . $option_index;
                                ?>
                                <div class="king-addons-radio-option">
                                    <input type="radio" 
                                           id="<?php echo esc_attr($option_id); ?>" 
                                           name="<?php echo esc_attr($field_name); ?>" 
                                           value="<?php echo esc_attr($value); ?>"
                                           <?php echo $required; ?>>
                                    <label for="<?php echo esc_attr($option_id); ?>"><?php echo esc_html($label); ?></label>
                                </div>
                                <?php
                            }
                            ?>
                        </div>
                        <?php
                        break;
                        
                    default:
                        ?>
                        <input type="<?php echo esc_attr($field['field_type']); ?>" 
                               id="<?php echo esc_attr($field_id); ?>" 
                               name="<?php echo esc_attr($field_name); ?>" 
                               placeholder="<?php echo esc_attr($field['field_placeholder']); ?>" 
                               <?php echo $required; ?>>
                        <?php
                        break;
                }
                ?>
            </div>
            <?php
        }
    }

    /**
     * Render reCAPTCHA element
     */
    protected function render_recaptcha($settings, $form_type)
    {
        if ($settings['enable_recaptcha'] !== 'yes' || empty($settings['recaptcha_site_key'])) {
            return;
        }

        if (!in_array($form_type, $settings['recaptcha_apply_on'])) {
            return;
        }

        $widget_id = $this->get_id();
        $recaptcha_id = 'recaptcha-' . $form_type . '-' . $widget_id;
        
        if ($settings['recaptcha_version'] === 'v2') {
            ?>
            <div class="king-addons-recaptcha-field">
                <div id="<?php echo esc_attr($recaptcha_id); ?>" 
                     class="king-addons-recaptcha-v2"
                     data-sitekey="<?php echo esc_attr($settings['recaptcha_site_key']); ?>"
                     data-theme="<?php echo esc_attr($settings['recaptcha_theme']); ?>"
                     data-size="<?php echo esc_attr($settings['recaptcha_size']); ?>">
                </div>
            </div>
            <?php
        } else {
            // reCAPTCHA v3 is handled via JavaScript
            ?>
            <input type="hidden" 
                   name="g-recaptcha-response" 
                   class="king-addons-recaptcha-v3"
                   data-sitekey="<?php echo esc_attr($settings['recaptcha_site_key']); ?>"
                   data-action="<?php echo esc_attr($form_type); ?>"
                   data-threshold="<?php echo esc_attr($settings['recaptcha_score_threshold']['size']); ?>">
            <?php
        }
    }

    protected function register_controls()
    {
        // General Settings
        $this->start_controls_section(
            'section_general',
            [
                'label' => esc_html__('General', 'king-addons'),
            ]
        );

        $this->add_control(
            'default_form_type',
            [
                'label' => esc_html__('Default Form Type', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'login' => esc_html__('Login', 'king-addons'),
                    'register' => esc_html__('Register', 'king-addons'),
                ],
                'default' => 'login',
            ]
        );

        $this->add_control(
            'show_labels',
            [
                'label' => esc_html__('Show Labels', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'enable_ajax',
            [
                'label' => esc_html__('Enable AJAX', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->end_controls_section();

        // Form Header Section
        $this->start_controls_section(
            'section_form_header',
            [
                'label' => esc_html__('Form Header', 'king-addons'),
            ]
        );

        $this->add_control(
            'show_form_header',
            [
                'label' => esc_html__('Show Form Header', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => '',
            ]
        );

        $this->add_control(
            'form_logo',
            [
                'label' => esc_html__('Logo', 'king-addons'),
                'type' => Controls_Manager::MEDIA,
                'condition' => [
                    'show_form_header' => 'yes',
                ],
            ]
        );

        $this->add_responsive_control(
            'form_logo_width',
            [
                'label' => esc_html__('Logo Width', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 500,
                    ],
                    '%' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 150,
                ],
                'condition' => [
                    'show_form_header' => 'yes',
                    'form_logo[url]!' => '',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-form-header-logo img' => 'width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'form_illustration',
            [
                'label' => esc_html__('Illustration', 'king-addons'),
                'type' => Controls_Manager::MEDIA,
                'condition' => [
                    'show_form_header' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'form_illustration_position',
            [
                'label' => esc_html__('Illustration Position', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'left' => esc_html__('Left', 'king-addons'),
                    'right' => esc_html__('Right', 'king-addons'),
                    'top' => esc_html__('Top', 'king-addons'),
                    'bottom' => esc_html__('Bottom', 'king-addons'),
                ],
                'default' => 'left',
                'condition' => [
                    'show_form_header' => 'yes',
                    'form_illustration[url]!' => '',
                ],
            ]
        );

        $this->add_responsive_control(
            'form_illustration_width',
            [
                'label' => esc_html__('Illustration Width', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 800,
                    ],
                    '%' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'unit' => '%',
                    'size' => 50,
                ],
                'condition' => [
                    'show_form_header' => 'yes',
                    'form_illustration[url]!' => '',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-form-illustration' => 'width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Login Form Section
        $this->start_controls_section(
            'section_login_form',
            [
                'label' => esc_html__('Login Form', 'king-addons'),
            ]
        );

        $this->add_control(
            'login_form_title',
            [
                'label' => esc_html__('Form Title', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Login to Your Account', 'king-addons'),
                'placeholder' => esc_html__('Enter title', 'king-addons'),
                'dynamic' => ['active' => true],
                'ai' => ['active' => false],
            ]
        );

        $this->add_control(
            'login_form_subtitle',
            [
                'label' => esc_html__('Form Subtitle', 'king-addons'),
                'type' => Controls_Manager::TEXTAREA,
                'default' => esc_html__('Please enter your username and password to log in.', 'king-addons'),
                'placeholder' => esc_html__('Enter subtitle', 'king-addons'),
                'dynamic' => ['active' => true],
            ]
        );

        $this->add_control(
            'login_username_label',
            [
                'label' => esc_html__('Username/Email Label', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Username or Email', 'king-addons'),
                'dynamic' => ['active' => true],
                'ai' => ['active' => false],
            ]
        );

        $this->add_control(
            'login_username_placeholder',
            [
                'label' => esc_html__('Username/Email Placeholder', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Enter your username or email', 'king-addons'),
                'dynamic' => ['active' => true],
                'ai' => ['active' => false],
            ]
        );

        $this->add_control(
            'login_password_label',
            [
                'label' => esc_html__('Password Label', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Password', 'king-addons'),
                'dynamic' => ['active' => true],
                'ai' => ['active' => false],
            ]
        );

        $this->add_control(
            'login_password_placeholder',
            [
                'label' => esc_html__('Password Placeholder', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Enter your password', 'king-addons'),
                'dynamic' => ['active' => true],
                'ai' => ['active' => false],
            ]
        );

        $this->add_control(
            'login_button_text',
            [
                'label' => esc_html__('Login Button Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Login', 'king-addons'),
                'dynamic' => ['active' => true],
                'ai' => ['active' => false],
            ]
        );

        $this->add_control(
            'show_password_visibility_login',
            [
                'label' => esc_html__('Show Password Visibility Toggle', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_remember_me',
            [
                'label' => esc_html__('Show Remember Me', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'remember_me_text',
            [
                'label' => esc_html__('Remember Me Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Remember Me', 'king-addons'),
                'condition' => [
                    'show_remember_me' => 'yes',
                ],
                'dynamic' => ['active' => true],
                'ai' => ['active' => false],
            ]
        );

        $this->add_control(
            'show_lost_password',
            [
                'label' => esc_html__('Show Lost Password Link', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'lost_password_text',
            [
                'label' => esc_html__('Lost Password Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Forgot Password?', 'king-addons'),
                'condition' => [
                    'show_lost_password' => 'yes',
                ],
                'dynamic' => ['active' => true],
                'ai' => ['active' => false],
            ]
        );

        $this->add_control(
            'show_register_link',
            [
                'label' => esc_html__('Show Register Link', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'register_link_text',
            [
                'label' => esc_html__('Register Link Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__("Don't have an account? Register", 'king-addons'),
                'condition' => [
                    'show_register_link' => 'yes',
                ],
                'dynamic' => ['active' => true],
                'ai' => ['active' => false],
            ]
        );

        $this->add_control(
            'show_lost_password_link',
            [
                'label' => esc_html__('Show Lost Password Link', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'lost_password_link_text',
            [
                'label' => esc_html__('Lost Password Link Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Forgot your password?', 'king-addons'),
                'condition' => [
                    'show_lost_password_link' => 'yes',
                ],
                'dynamic' => ['active' => true],
                'ai' => ['active' => false],
                            ]
            );



        $this->end_controls_section();

        // Email Notifications Section
        $this->start_controls_section(
            'section_email_notifications',
            [
                'label' => esc_html__('Email Notifications', 'king-addons'),
            ]
        );

        $this->add_control(
            'enable_user_email',
            [
                'label' => esc_html__('Send Email to User', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => 'yes',
                'description' => esc_html__('Send a welcome email to the user after successful registration.', 'king-addons'),
            ]
        );

        $this->add_control(
            'user_email_subject',
            [
                'label' => esc_html__('User Email Subject', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Welcome to {site_name}!', 'king-addons'),
                'condition' => [
                    'enable_user_email' => 'yes',
                ],
                'dynamic' => ['active' => true],
                'ai' => ['active' => false],
            ]
        );

        if (!king_addons_freemius()->can_use_premium_code__premium_only()) {
            \King_Addons\Core::renderUpgradeProNotice($this, Controls_Manager::RAW_HTML, 'login-register-form', 'user_email_subject', ['']);
        }

        $this->add_control(
            'user_email_content',
            [
                'label' => esc_html__('User Email Content', 'king-addons'),
                'type' => Controls_Manager::TEXTAREA,
                'rows' => 10,
                'default' => "Hello {user_name},\n\nWelcome to {site_name}!\n\nYour account has been successfully created.\n\nUsername: {username}\nEmail: {user_email}\n\nThank you for joining us!\n\nBest regards,\n{site_name} Team",
                'condition' => [
                    'enable_user_email' => 'yes',
                ],
                'dynamic' => ['active' => true],
                'ai' => ['active' => false],
            ]
        );

        if (!king_addons_freemius()->can_use_premium_code__premium_only()) {
            \King_Addons\Core::renderUpgradeProNotice($this, Controls_Manager::RAW_HTML, 'login-register-form', 'user_email_content', ['']);
        }

        $this->add_control(
            'enable_admin_email',
            [
                'label' => sprintf(__('Send Email to Admin %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => '',
                'description' => esc_html__('Send a notification email to the admin when a user registers.', 'king-addons'),
                'classes' => 'king-addons-pro-control',
            ]
        );

        if (!king_addons_freemius()->can_use_premium_code__premium_only()) {
            \King_Addons\Core::renderUpgradeProNotice($this, Controls_Manager::RAW_HTML, 'login-register-form', 'enable_admin_email', ['yes']);
        } else {
            $this->add_control(
                'admin_email_address',
                [
                    'label' => esc_html__('Admin Email Address', 'king-addons'),
                    'type' => Controls_Manager::TEXT,
                    'default' => get_option('admin_email'),
                    'condition' => [
                        'enable_admin_email' => 'yes',
                    ],
                    'dynamic' => ['active' => true],
                    'ai' => ['active' => false],
                ]
            );

            $this->add_control(
                'admin_email_subject',
                [
                    'label' => esc_html__('Admin Email Subject', 'king-addons'),
                    'type' => Controls_Manager::TEXT,
                    'default' => esc_html__('New User Registration on {site_name}', 'king-addons'),
                    'condition' => [
                        'enable_admin_email' => 'yes',
                    ],
                    'dynamic' => ['active' => true],
                    'ai' => ['active' => false],
                ]
            );

            $this->add_control(
                'admin_email_content',
                [
                    'label' => esc_html__('Admin Email Content', 'king-addons'),
                    'type' => Controls_Manager::TEXTAREA,
                    'rows' => 10,
                    'default' => "Hello Admin,\n\nA new user has registered on {site_name}.\n\nUser Details:\nName: {user_name}\nUsername: {username}\nEmail: {user_email}\nRole: {user_role}\nRegistration Date: {registration_date}\n\nYou can view the user profile in the admin dashboard.\n\nBest regards,\n{site_name}",
                    'condition' => [
                        'enable_admin_email' => 'yes',
                    ],
                    'dynamic' => ['active' => true],
                    'ai' => ['active' => false],
                ]
            );
        }

        $this->add_control(
            'email_placeholders_info',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => esc_html__('Available placeholders: {site_name}, {user_name}, {username}, {user_email}, {user_role}, {registration_date}', 'king-addons'),
                'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
            ]
        );

        $this->end_controls_section();

        // Mailchimp Integration Section
        $this->start_controls_section(
            'section_mailchimp_integration',
            [
                'label' => esc_html__('Mailchimp Integration', 'king-addons'),
            ]
        );

        $this->add_control(
            'enable_mailchimp_integration',
            [
                'label' => sprintf(__('Enable Mailchimp Integration %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => '',
                'description' => esc_html__('Automatically subscribe users to your Mailchimp list upon registration.', 'king-addons'),
                'classes' => 'king-addons-pro-control',
            ]
        );

        if (!king_addons_freemius()->can_use_premium_code__premium_only()) {
            \King_Addons\Core::renderUpgradeProNotice($this, Controls_Manager::RAW_HTML, 'login-register-form', 'enable_mailchimp_integration', ['yes']);
        } else {
            $this->add_control(
                'mailchimp_api_key',
                [
                    'label' => esc_html__('Mailchimp API Key', 'king-addons'),
                    'type' => Controls_Manager::TEXT,
                    'placeholder' => esc_html__('Enter your Mailchimp API Key', 'king-addons'),
                    'condition' => [
                        'enable_mailchimp_integration' => 'yes',
                    ],
                    'dynamic' => ['active' => true],
                    'ai' => ['active' => false],
                ]
            );

            $this->add_control(
                'mailchimp_list_id',
                [
                    'label' => esc_html__('Mailchimp List ID', 'king-addons'),
                    'type' => Controls_Manager::TEXT,
                    'placeholder' => esc_html__('Enter your Mailchimp List ID', 'king-addons'),
                    'condition' => [
                        'enable_mailchimp_integration' => 'yes',
                    ],
                    'dynamic' => ['active' => true],
                    'ai' => ['active' => false],
                ]
            );

            $this->add_control(
                'mailchimp_double_optin',
                [
                    'label' => esc_html__('Double Opt-in', 'king-addons'),
                    'type' => Controls_Manager::SWITCHER,
                    'label_on' => esc_html__('Yes', 'king-addons'),
                    'label_off' => esc_html__('No', 'king-addons'),
                    'return_value' => 'yes',
                    'default' => '',
                    'description' => esc_html__('Send confirmation email to subscribers.', 'king-addons'),
                    'condition' => [
                        'enable_mailchimp_integration' => 'yes',
                    ],
                ]
            );

            $this->add_control(
                'mailchimp_setup_info',
                [
                    'type' => Controls_Manager::RAW_HTML,
                    'raw' => sprintf(
                        esc_html__('To get your API key and List ID, go to your %s and navigate to Account > Extras > API keys and Audience > Manage Audience > Settings > Audience name and defaults.', 'king-addons'),
                        '<a href="https://mailchimp.com/" target="_blank">Mailchimp account</a>'
                    ),
                    'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
                    'condition' => [
                        'enable_mailchimp_integration' => 'yes',
                    ],
                ]
            );
        }

        $this->end_controls_section();

        // Register Actions Section
        $this->start_controls_section(
            'section_register_actions',
            [
                'label' => esc_html__('Register Actions', 'king-addons'),
            ]
        );

        $this->add_control(
            'auto_login_after_register',
            [
                'label' => esc_html__('Auto Login After Registration', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => 'yes',
                'description' => esc_html__('Automatically log in the user after successful registration.', 'king-addons'),
            ]
        );

        $this->add_control(
            'redirect_after_register',
            [
                'label' => esc_html__('Redirect After Registration', 'king-addons'),
                'type' => Controls_Manager::URL,
                'placeholder' => esc_html__('https://yoursite.com/welcome', 'king-addons'),
                'dynamic' => ['active' => true],
                'description' => esc_html__('Leave empty to stay on the current page.', 'king-addons'),
            ]
        );

        $this->add_control(
            'redirect_after_login',
            [
                'label' => esc_html__('Redirect After Login', 'king-addons'),
                'type' => Controls_Manager::URL,
                'placeholder' => esc_html__('https://yoursite.com/dashboard', 'king-addons'),
                'dynamic' => ['active' => true],
                'description' => esc_html__('Leave empty to stay on the current page.', 'king-addons'),
            ]
        );

        $this->add_control(
            'redirect_logout_user',
            [
                'label' => esc_html__('Redirect Already Logged Users', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => '',
                'description' => esc_html__('Redirect users who are already logged in.', 'king-addons'),
            ]
        );

        $this->add_control(
            'redirect_logout_url',
            [
                'label' => esc_html__('Redirect URL for Logged Users', 'king-addons'),
                'type' => Controls_Manager::URL,
                'placeholder' => esc_html__('https://yoursite.com/dashboard', 'king-addons'),
                'condition' => [
                    'redirect_logout_user' => 'yes',
                ],
                'dynamic' => ['active' => true],
            ]
        );

        $this->end_controls_section();

        // Lost Password Form Section
        $this->start_controls_section(
            'section_lost_password_form',
            [
                'label' => esc_html__('Lost Password Form', 'king-addons'),
                'condition' => [
                    'show_lost_password_link' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'lostpassword_form_title',
            [
                'label' => esc_html__('Form Title', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Lost Your Password?', 'king-addons'),
                'dynamic' => ['active' => true],
                'ai' => ['active' => false],
            ]
        );

        $this->add_control(
            'lostpassword_form_subtitle',
            [
                'label' => esc_html__('Form Subtitle', 'king-addons'),
                'type' => Controls_Manager::TEXTAREA,
                'default' => esc_html__('Enter your email address and we will send you a link to reset your password.', 'king-addons'),
                'dynamic' => ['active' => true],
                'ai' => ['active' => false],
            ]
        );

        $this->add_control(
            'lostpassword_email_label',
            [
                'label' => esc_html__('Email Label', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Email', 'king-addons'),
                'dynamic' => ['active' => true],
                'ai' => ['active' => false],
            ]
        );

        $this->add_control(
            'lostpassword_email_placeholder',
            [
                'label' => esc_html__('Email Placeholder', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Enter your email address', 'king-addons'),
                'dynamic' => ['active' => true],
                'ai' => ['active' => false],
            ]
        );

        $this->add_control(
            'lostpassword_button_text',
            [
                'label' => esc_html__('Button Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Reset Password', 'king-addons'),
                'dynamic' => ['active' => true],
                'ai' => ['active' => false],
            ]
        );

        $this->add_control(
            'show_login_link_from_lostpassword',
            [
                'label' => esc_html__('Show Login Link', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'login_link_text_from_lostpassword',
            [
                'label' => esc_html__('Login Link Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Back to Login', 'king-addons'),
                'condition' => [
                    'show_login_link_from_lostpassword' => 'yes',
                ],
                'dynamic' => ['active' => true],
                'ai' => ['active' => false],
            ]
        );

        $this->end_controls_section();

        // Social Login Section
        $this->start_controls_section(
            'section_social_login',
            [
                'label' => esc_html__('Social Login', 'king-addons'),
            ]
        );

        $this->add_control(
            'enable_social_login',
            [
                'label' => sprintf(__('Enable Social Login %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => '',
                'classes' => 'king-addons-pro-control',
            ]
        );

        if (!king_addons_freemius()->can_use_premium_code__premium_only()) {
            \King_Addons\Core::renderUpgradeProNotice($this, Controls_Manager::RAW_HTML, 'login-register-form', 'enable_social_login', ['yes']);
        } else {
            $this->add_control(
                'social_login_separator',
                [
                    'label' => esc_html__('Show Separator', 'king-addons'),
                    'type' => Controls_Manager::SWITCHER,
                    'label_on' => esc_html__('Yes', 'king-addons'),
                    'label_off' => esc_html__('No', 'king-addons'),
                    'return_value' => 'yes',
                    'default' => 'yes',
                    'condition' => [
                        'enable_social_login' => 'yes',
                    ],
                ]
            );

            $this->add_control(
                'social_login_setup_note',
                [
                    'type' => Controls_Manager::RAW_HTML,
                    'raw' => esc_html__('Note: Social login requires proper API setup. Google and Facebook apps need to be configured with valid credentials.', 'king-addons'),
                    'content_classes' => 'elementor-panel-alert elementor-panel-alert-warning',
                    'condition' => [
                        'enable_social_login' => 'yes',
                    ],
                ]
            );

            // Google Login Settings
            $this->add_control(
                'google_login_heading',
                [
                    'label' => esc_html__('Google Login', 'king-addons'),
                    'type' => Controls_Manager::HEADING,
                    'separator' => 'before',
                    'condition' => [
                        'enable_social_login' => 'yes',
                    ],
                ]
            );

            $this->add_control(
                'enable_google_login',
                [
                    'label' => esc_html__('Enable Google Login', 'king-addons'),
                    'type' => Controls_Manager::SWITCHER,
                    'label_on' => esc_html__('Yes', 'king-addons'),
                    'label_off' => esc_html__('No', 'king-addons'),
                    'return_value' => 'yes',
                    'default' => '',
                    'condition' => [
                        'enable_social_login' => 'yes',
                    ],
                ]
            );

            $this->add_control(
                'google_client_id',
                [
                    'label' => esc_html__('Google Client ID', 'king-addons'),
                    'type' => Controls_Manager::TEXT,
                    'placeholder' => esc_html__('Enter your Google Client ID', 'king-addons'),
                    'condition' => [
                        'enable_social_login' => 'yes',
                        'enable_google_login' => 'yes',
                    ],
                    'dynamic' => ['active' => true],
                    'ai' => ['active' => false],
                ]
            );

            $this->add_control(
                'google_client_secret',
                [
                    'label' => esc_html__('Google Client Secret', 'king-addons'),
                    'type' => Controls_Manager::TEXT,
                    'placeholder' => esc_html__('Enter your Google Client Secret', 'king-addons'),
                    'condition' => [
                        'enable_social_login' => 'yes',
                        'enable_google_login' => 'yes',
                    ],
                    'dynamic' => ['active' => true],
                    'ai' => ['active' => false],
                ]
            );

            $this->add_control(
                'google_setup_instructions',
                [
                    'type' => Controls_Manager::RAW_HTML,
                    'raw' => sprintf(
                        '<div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;">
                            <h4 style="margin: 0 0 10px 0;">🔧 Google OAuth Setup Instructions:</h4>
                            <ol style="margin: 0; padding-left: 20px;">
                                <li>Go to <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a></li>
                                <li>Create a new project or select existing one</li>
                                <li>Enable "Google+ API" in APIs & Services</li>
                                <li>Go to "Credentials" → "Create Credentials" → "OAuth 2.0 Client IDs"</li>
                                <li>Set Application type to "Web application"</li>
                                <li>Add your domain to "Authorized JavaScript origins"</li>
                                <li>Add redirect URI: <code>%s</code></li>
                                <li>Copy Client ID and Client Secret to fields above</li>
                            </ol>
                        </div>',
                        home_url('/wp-admin/admin-ajax.php?action=king_addons_google_callback')
                    ),
                    'condition' => [
                        'enable_social_login' => 'yes',
                        'enable_google_login' => 'yes',
                    ],
                ]
            );

            // Facebook Login Settings
            $this->add_control(
                'facebook_login_heading',
                [
                    'label' => esc_html__('Facebook Login', 'king-addons'),
                    'type' => Controls_Manager::HEADING,
                    'separator' => 'before',
                    'condition' => [
                        'enable_social_login' => 'yes',
                    ],
                ]
            );

            $this->add_control(
                'enable_facebook_login',
                [
                    'label' => esc_html__('Enable Facebook Login', 'king-addons'),
                    'type' => Controls_Manager::SWITCHER,
                    'label_on' => esc_html__('Yes', 'king-addons'),
                    'label_off' => esc_html__('No', 'king-addons'),
                    'return_value' => 'yes',
                    'default' => '',
                    'condition' => [
                        'enable_social_login' => 'yes',
                    ],
                ]
            );

            $this->add_control(
                'facebook_app_id',
                [
                    'label' => esc_html__('Facebook App ID', 'king-addons'),
                    'type' => Controls_Manager::TEXT,
                    'placeholder' => esc_html__('Enter your Facebook App ID', 'king-addons'),
                    'condition' => [
                        'enable_social_login' => 'yes',
                        'enable_facebook_login' => 'yes',
                    ],
                    'dynamic' => ['active' => true],
                    'ai' => ['active' => false],
                ]
            );

            $this->add_control(
                'facebook_app_secret',
                [
                    'label' => esc_html__('Facebook App Secret', 'king-addons'),
                    'type' => Controls_Manager::TEXT,
                    'placeholder' => esc_html__('Enter your Facebook App Secret', 'king-addons'),
                    'condition' => [
                        'enable_social_login' => 'yes',
                        'enable_facebook_login' => 'yes',
                    ],
                    'dynamic' => ['active' => true],
                    'ai' => ['active' => false],
                ]
            );

            $this->add_control(
                'facebook_setup_instructions',
                [
                    'type' => Controls_Manager::RAW_HTML,
                    'raw' => sprintf(
                        '<div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;">
                            <h4 style="margin: 0 0 10px 0;">🔧 Facebook OAuth Setup Instructions:</h4>
                            <ol style="margin: 0; padding-left: 20px;">
                                <li>Go to <a href="https://developers.facebook.com/" target="_blank">Facebook Developers</a></li>
                                <li>Create a new app or select existing one</li>
                                <li>Add "Facebook Login" product to your app</li>
                                <li>Go to Facebook Login → Settings</li>
                                <li>Add Valid OAuth Redirect URI: <code>%s</code></li>
                                <li>Get App ID and App Secret from App Settings → Basic</li>
                                <li>Copy App ID and App Secret to fields above</li>
                                <li>Make sure app is live (not in development mode)</li>
                            </ol>
                        </div>',
                        home_url('/wp-admin/admin-ajax.php?action=king_addons_facebook_callback')
                    ),
                    'condition' => [
                        'enable_social_login' => 'yes',
                        'enable_facebook_login' => 'yes',
                    ],
                ]
            );
        }

        $this->end_controls_section();

        // reCAPTCHA Section
        $this->start_controls_section(
            'section_recaptcha',
            [
                'label' => esc_html__('reCAPTCHA', 'king-addons'),
            ]
        );

        $this->add_control(
            'enable_recaptcha',
            [
                'label' => esc_html__('Enable reCAPTCHA', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => '',
            ]
        );

        $this->add_control(
            'recaptcha_version',
            [
                'label' => esc_html__('reCAPTCHA Version', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'v2' => esc_html__('reCAPTCHA v2', 'king-addons'),
                    'v3' => esc_html__('reCAPTCHA v3', 'king-addons'),
                ],
                'default' => 'v2',
                'condition' => [
                    'enable_recaptcha' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'recaptcha_site_key',
            [
                'label' => esc_html__('Site Key', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => esc_html__('Enter your reCAPTCHA Site Key', 'king-addons'),
                'condition' => [
                    'enable_recaptcha' => 'yes',
                ],
                'dynamic' => ['active' => true],
                'ai' => ['active' => false],
            ]
        );

        $this->add_control(
            'recaptcha_secret_key',
            [
                'label' => esc_html__('Secret Key', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => esc_html__('Enter your reCAPTCHA Secret Key', 'king-addons'),
                'condition' => [
                    'enable_recaptcha' => 'yes',
                ],
                'dynamic' => ['active' => true],
                'ai' => ['active' => false],
            ]
        );

        $this->add_control(
            'recaptcha_theme',
            [
                'label' => esc_html__('Theme', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'light' => esc_html__('Light', 'king-addons'),
                    'dark' => esc_html__('Dark', 'king-addons'),
                ],
                'default' => 'light',
                'condition' => [
                    'enable_recaptcha' => 'yes',
                    'recaptcha_version' => 'v2',
                ],
            ]
        );

        $this->add_control(
            'recaptcha_size',
            [
                'label' => esc_html__('Size', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'normal' => esc_html__('Normal', 'king-addons'),
                    'compact' => esc_html__('Compact', 'king-addons'),
                ],
                'default' => 'normal',
                'condition' => [
                    'enable_recaptcha' => 'yes',
                    'recaptcha_version' => 'v2',
                ],
            ]
        );

        $this->add_control(
            'recaptcha_score_threshold',
            [
                'label' => esc_html__('Score Threshold', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    '' => [
                        'min' => 0,
                        'max' => 1,
                        'step' => 0.1,
                    ],
                ],
                'default' => [
                    'size' => 0.5,
                ],
                'condition' => [
                    'enable_recaptcha' => 'yes',
                    'recaptcha_version' => 'v3',
                ],
                'description' => esc_html__('reCAPTCHA v3 returns a score (1.0 is very likely a good interaction, 0.0 is very likely a bot). Based on the score, you can take variable action in the context of your site.', 'king-addons'),
            ]
        );

        $this->add_control(
            'recaptcha_hide_badge',
            [
                'label' => esc_html__('Hide reCAPTCHA Badge', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => '',
                'condition' => [
                    'enable_recaptcha' => 'yes',
                    'recaptcha_version' => 'v3',
                ],
                'description' => esc_html__('Hide the reCAPTCHA badge. Note: You must include the reCAPTCHA branding visibly in the user flow.', 'king-addons'),
            ]
        );

        $this->add_control(
            'recaptcha_apply_on',
            [
                'label' => esc_html__('Apply reCAPTCHA On', 'king-addons'),
                'type' => Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => [
                    'login' => esc_html__('Login Form', 'king-addons'),
                    'register' => esc_html__('Register Form', 'king-addons'),
                    'lostpassword' => esc_html__('Lost Password Form', 'king-addons'),
                ],
                'default' => ['login', 'register'],
                'condition' => [
                    'enable_recaptcha' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'recaptcha_setup_note',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => sprintf(
                    esc_html__('To use reCAPTCHA, you need to get API keys from %s. For more details, check the documentation.', 'king-addons'),
                    '<a href="https://www.google.com/recaptcha/" target="_blank">Google reCAPTCHA</a>'
                ),
                'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
                'condition' => [
                    'enable_recaptcha' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        // Register Form Section
        $this->start_controls_section(
            'section_register_form',
            [
                'label' => esc_html__('Register Form', 'king-addons'),
            ]
        );

        $this->add_control(
            'register_form_title',
            [
                'label' => esc_html__('Form Title', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Create New Account', 'king-addons'),
                'placeholder' => esc_html__('Enter title', 'king-addons'),
                'dynamic' => ['active' => true],
                'ai' => ['active' => false],
            ]
        );

        $this->add_control(
            'register_form_subtitle',
            [
                'label' => esc_html__('Form Subtitle', 'king-addons'),
                'type' => Controls_Manager::TEXTAREA,
                'default' => esc_html__('Create an account to enjoy awesome features.', 'king-addons'),
                'placeholder' => esc_html__('Enter subtitle', 'king-addons'),
                'dynamic' => ['active' => true],
            ]
        );

        $this->add_control(
            'register_email_label',
            [
                'label' => esc_html__('Email Label', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Email', 'king-addons'),
                'dynamic' => ['active' => true],
                'ai' => ['active' => false],
            ]
        );

        $this->add_control(
            'register_email_placeholder',
            [
                'label' => esc_html__('Email Placeholder', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Enter your email', 'king-addons'),
                'dynamic' => ['active' => true],
                'ai' => ['active' => false],
            ]
        );

        $this->add_control(
            'register_username_label',
            [
                'label' => esc_html__('Username Label', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Username', 'king-addons'),
                'dynamic' => ['active' => true],
                'ai' => ['active' => false],
            ]
        );

        $this->add_control(
            'register_username_placeholder',
            [
                'label' => esc_html__('Username Placeholder', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Enter your username', 'king-addons'),
                'dynamic' => ['active' => true],
                'ai' => ['active' => false],
            ]
        );

        $this->add_control(
            'register_password_label',
            [
                'label' => esc_html__('Password Label', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Password', 'king-addons'),
                'dynamic' => ['active' => true],
                'ai' => ['active' => false],
            ]
        );

        $this->add_control(
            'register_password_placeholder',
            [
                'label' => esc_html__('Password Placeholder', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Enter your password', 'king-addons'),
                'dynamic' => ['active' => true],
                'ai' => ['active' => false],
            ]
        );

        $this->add_control(
            'register_confirm_password_label',
            [
                'label' => esc_html__('Confirm Password Label', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Confirm Password', 'king-addons'),
                'dynamic' => ['active' => true],
                'ai' => ['active' => false],
            ]
        );

        $this->add_control(
            'register_confirm_password_placeholder',
            [
                'label' => esc_html__('Confirm Password Placeholder', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Confirm your password', 'king-addons'),
                'dynamic' => ['active' => true],
                'ai' => ['active' => false],
            ]
        );

        $this->add_control(
            'register_additional_fields_heading',
            [
                'label' => esc_html__('Additional Fields', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'show_first_name',
            [
                'label' => esc_html__('Show First Name', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => '',
            ]
        );

        $this->add_control(
            'register_first_name_label',
            [
                'label' => esc_html__('First Name Label', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('First Name', 'king-addons'),
                'condition' => [
                    'show_first_name' => 'yes',
                ],
                'dynamic' => ['active' => true],
                'ai' => ['active' => false],
            ]
        );

        $this->add_control(
            'register_first_name_placeholder',
            [
                'label' => esc_html__('First Name Placeholder', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('First Name', 'king-addons'),
                'condition' => [
                    'show_first_name' => 'yes',
                ],
                'dynamic' => ['active' => true],
                'ai' => ['active' => false],
            ]
        );

        $this->add_control(
            'show_last_name',
            [
                'label' => esc_html__('Show Last Name', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => '',
            ]
        );

        $this->add_control(
            'register_last_name_label',
            [
                'label' => esc_html__('Last Name Label', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Last Name', 'king-addons'),
                'condition' => [
                    'show_last_name' => 'yes',
                ],
                'dynamic' => ['active' => true],
                'ai' => ['active' => false],
            ]
        );

        $this->add_control(
            'register_last_name_placeholder',
            [
                'label' => esc_html__('Last Name Placeholder', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Last Name', 'king-addons'),
                'condition' => [
                    'show_last_name' => 'yes',
                ],
                'dynamic' => ['active' => true],
                'ai' => ['active' => false],
            ]
        );

        $this->add_control(
            'show_website',
            [
                'label' => esc_html__('Show Website', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => '',
            ]
        );

        $this->add_control(
            'register_website_label',
            [
                'label' => esc_html__('Website Label', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Website', 'king-addons'),
                'condition' => [
                    'show_website' => 'yes',
                ],
                'dynamic' => ['active' => true],
                'ai' => ['active' => false],
            ]
        );

        $this->add_control(
            'register_website_placeholder',
            [
                'label' => esc_html__('Website Placeholder', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('https://yourwebsite.com', 'king-addons'),
                'condition' => [
                    'show_website' => 'yes',
                ],
                'dynamic' => ['active' => true],
                'ai' => ['active' => false],
            ]
        );

        $this->add_control(
            'show_phone',
            [
                'label' => esc_html__('Show Phone', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => '',
            ]
        );

        $this->add_control(
            'register_phone_label',
            [
                'label' => esc_html__('Phone Label', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Phone', 'king-addons'),
                'condition' => [
                    'show_phone' => 'yes',
                ],
                'dynamic' => ['active' => true],
                'ai' => ['active' => false],
            ]
        );

        $this->add_control(
            'register_phone_placeholder',
            [
                'label' => esc_html__('Phone Placeholder', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Phone Number', 'king-addons'),
                'condition' => [
                    'show_phone' => 'yes',
                ],
                'dynamic' => ['active' => true],
                'ai' => ['active' => false],
            ]
        );

        $this->add_control(
            'register_button_text',
            [
                'label' => esc_html__('Register Button Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Register', 'king-addons'),
                'dynamic' => ['active' => true],
                'ai' => ['active' => false],
            ]
        );

        $this->add_control(
            'show_password_visibility_register',
            [
                'label' => esc_html__('Show Password Visibility Toggle', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_login_link',
            [
                'label' => esc_html__('Show Login Link', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'login_link_text',
            [
                'label' => esc_html__('Login Link Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Already have an account? Login', 'king-addons'),
                'condition' => [
                    'show_login_link' => 'yes',
                ],
                'dynamic' => ['active' => true],
                'ai' => ['active' => false],
            ]
        );

        // Custom Fields for Registration
        $this->add_control(
            'custom_fields_heading',
            [
                'label' => sprintf(__('Custom Fields %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        if (!king_addons_freemius()->can_use_premium_code__premium_only()) {
            $this->add_control(
                'custom_fields_pro_notice',
                [
                    'type' => Controls_Manager::RAW_HTML,
                    'raw' => 'Custom Registration Fields are available<br> in the <strong><a href="https://kingaddons.com/pricing/?utm_source=kng-module-login-register-form-settings-upgrade-pro&utm_medium=plugin&utm_campaign=kng" target="_blank">Pro version</a></strong>',
                    'content_classes' => 'king-addons-pro-notice',
                ]
            );
        } else {
            $repeater = new \Elementor\Repeater();

            $repeater->add_control(
                'field_type',
                [
                    'label' => esc_html__('Field Type', 'king-addons'),
                    'type' => Controls_Manager::SELECT,
                    'options' => [
                        'text' => esc_html__('Text', 'king-addons'),
                        'email' => esc_html__('Email', 'king-addons'),
                        'number' => esc_html__('Number', 'king-addons'),
                        'tel' => esc_html__('Phone', 'king-addons'),
                        'url' => esc_html__('URL', 'king-addons'),
                        'textarea' => esc_html__('Textarea', 'king-addons'),
                        'select' => esc_html__('Select', 'king-addons'),
                        'checkbox' => esc_html__('Checkbox', 'king-addons'),
                        'radio' => esc_html__('Radio', 'king-addons'),
                        'date' => esc_html__('Date', 'king-addons'),
                        'file' => esc_html__('File Upload', 'king-addons'),
                    ],
                    'default' => 'text',
                ]
            );

            $repeater->add_control(
                'field_label',
                [
                    'label' => esc_html__('Field Label', 'king-addons'),
                    'type' => Controls_Manager::TEXT,
                    'default' => esc_html__('Custom Field', 'king-addons'),
                    'dynamic' => ['active' => true],
                ]
            );

            $repeater->add_control(
                'field_placeholder',
                [
                    'label' => esc_html__('Placeholder', 'king-addons'),
                    'type' => Controls_Manager::TEXT,
                    'dynamic' => ['active' => true],
                    'condition' => [
                        'field_type!' => ['checkbox', 'radio', 'file'],
                    ],
                ]
            );

            $repeater->add_control(
                'field_required',
                [
                    'label' => esc_html__('Required', 'king-addons'),
                    'type' => Controls_Manager::SWITCHER,
                    'label_on' => esc_html__('Yes', 'king-addons'),
                    'label_off' => esc_html__('No', 'king-addons'),
                    'return_value' => 'yes',
                    'default' => '',
                ]
            );

            $repeater->add_control(
                'field_options',
                [
                    'label' => esc_html__('Options', 'king-addons'),
                    'type' => Controls_Manager::TEXTAREA,
                    'description' => esc_html__('Enter each option on a new line. For radio/checkbox use format: value|label', 'king-addons'),
                    'condition' => [
                        'field_type' => ['select', 'checkbox', 'radio'],
                    ],
                    'default' => "option1|Option 1\noption2|Option 2\noption3|Option 3",
                ]
            );

            $repeater->add_control(
                'field_width',
                [
                    'label' => esc_html__('Field Width', 'king-addons'),
                    'type' => Controls_Manager::SELECT,
                    'options' => [
                        'full' => esc_html__('Full Width', 'king-addons'),
                        'half' => esc_html__('Half Width', 'king-addons'),
                    ],
                    'default' => 'full',
                ]
            );

            $this->add_control(
                'custom_fields',
                [
                    'label' => esc_html__('Additional Fields', 'king-addons'),
                    'type' => Controls_Manager::REPEATER,
                    'fields' => $repeater->get_controls(),
                    'title_field' => '{{{ field_label }}}',
                    'default' => [],
                ]
            );
        }

        // User Role Selection
        $this->add_control(
            'user_role_heading',
            [
                'label' => esc_html__('User Role', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'show_user_role_selection',
            [
                'label' => esc_html__('Show User Role Selection', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => '',
            ]
        );

        $this->add_control(
            'user_role_label',
            [
                'label' => esc_html__('User Role Label', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Select Role', 'king-addons'),
                'condition' => [
                    'show_user_role_selection' => 'yes',
                ],
                'dynamic' => ['active' => true],
                'ai' => ['active' => false],
            ]
        );

        $this->add_control(
            'allowed_user_roles',
            [
                'label' => esc_html__('Allowed User Roles', 'king-addons'),
                'type' => Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => $this->get_user_roles(),
                'default' => ['subscriber'],
                'condition' => [
                    'show_user_role_selection' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'default_user_role',
            [
                'label' => esc_html__('Default User Role', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => $this->get_user_roles(),
                'default' => 'subscriber',
                'condition' => [
                    'show_user_role_selection' => 'yes',
                ],
            ]
        );

        // Terms & Conditions
        $this->add_control(
            'terms_conditions_heading',
            [
                'label' => esc_html__('Terms & Conditions', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'show_terms_conditions',
            [
                'label' => esc_html__('Show Terms & Conditions', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => '',
            ]
        );

        $this->add_control(
            'terms_conditions_text',
            [
                'label' => esc_html__('Terms & Conditions Text', 'king-addons'),
                'type' => Controls_Manager::TEXTAREA,
                'default' => esc_html__('I agree to the Terms & Conditions', 'king-addons'),
                'condition' => [
                    'show_terms_conditions' => 'yes',
                ],
                'dynamic' => ['active' => true],
                'ai' => ['active' => false],
            ]
        );

        $this->add_control(
            'terms_conditions_link',
            [
                'label' => esc_html__('Terms & Conditions Link', 'king-addons'),
                'type' => Controls_Manager::URL,
                'placeholder' => esc_html__('https://yoursite.com/terms', 'king-addons'),
                'condition' => [
                    'show_terms_conditions' => 'yes',
                ],
                'dynamic' => ['active' => true],
            ]
        );

        $this->add_control(
            'terms_required',
            [
                'label' => esc_html__('Required', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => 'yes',
                'condition' => [
                    'show_terms_conditions' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        // Redirect Settings (Legacy - for backward compatibility)
        $this->start_controls_section(
            'section_redirect',
            [
                'label' => esc_html__('Redirect Settings', 'king-addons'),
            ]
        );

        $this->add_control(
            'redirect_info_notice',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => esc_html__('Note: Redirect settings have been moved to the "Register Actions" section above for better organization.', 'king-addons'),
                'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
            ]
        );

        $this->end_controls_section();

        // Pro Features Section
        \King_Addons\Core::renderProFeaturesSection($this, '', Controls_Manager::RAW_HTML, 'login-register-form', [
            'Custom Registration Fields (Text, Email, Phone, File Upload, Select, Checkbox, Radio)',
            'Advanced Social Login (Google, Facebook with OAuth Setup)',
            'Mailchimp Integration with Double Opt-in Support',
            'Advanced Email Notifications (Custom Templates, Placeholders)',
            'Admin Email Notifications for New Registrations',
            'Email Template Customization with Placeholders',
            'Enhanced Security Features (Rate Limiting, File Validation)',
            'Advanced Redirect Options (Role-based Redirects)',
            'User Role Assignment & Selection',
            'Advanced Form Validation (Client & Server Side)',
            'File Upload Security & Validation',
            'reCAPTCHA v2/v3 Integration',
            'Custom Fields User Meta Storage',
            'Priority Support & Updates'
        ]);

        // Style Controls Start Here
        $this->register_style_controls();
    }

    protected function register_style_controls()
    {
        // Form Container Style
        $this->start_controls_section(
            'section_form_container_style',
            [
                'label' => esc_html__('Form Container', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'form_container_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'default' => [
                    'top' => '30',
                    'right' => '30',
                    'bottom' => '30',
                    'left' => '30',
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-login-register-form' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'form_container_background',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-login-register-form' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'form_container_border',
                'label' => esc_html__('Border', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-login-register-form',
                'fields_options' => [
                    'border' => [
                        'default' => 'solid',
                    ],
                    'width' => [
                        'default' => [
                            'top' => '1',
                            'right' => '1',
                            'bottom' => '1',
                            'left' => '1',
                            'unit' => 'px',
                        ],
                    ],
                    'color' => [
                        'default' => '#e1e5e9',
                    ],
                ],
            ]
        );

        $this->add_control(
            'form_container_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'default' => [
                    'top' => '8',
                    'right' => '8',
                    'bottom' => '8',
                    'left' => '8',
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-login-register-form' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'form_container_box_shadow',
                'label' => esc_html__('Box Shadow', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-login-register-form',
                'fields_options' => [
                    'box_shadow_type' => [
                        'default' => 'yes',
                    ],
                    'box_shadow' => [
                        'default' => [
                            'horizontal' => 0,
                            'vertical' => 4,
                            'blur' => 20,
                            'spread' => 0,
                            'color' => 'rgba(0,0,0,0.1)',
                        ],
                    ],
                ],
            ]
        );

        $this->end_controls_section();

        // Form Title Style
        $this->start_controls_section(
            'section_form_title_style',
            [
                'label' => esc_html__('Form Title', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'form_title_typography',
                'label' => esc_html__('Typography', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-form-title',
            ]
        );

        $this->add_control(
            'form_title_color',
            [
                'label' => esc_html__('Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#333333',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-form-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'form_title_margin',
            [
                'label' => esc_html__('Margin', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'default' => [
                    'top' => '0',
                    'right' => '0',
                    'bottom' => '10',
                    'left' => '0',
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-form-title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Form Subtitle Style
        $this->start_controls_section(
            'section_form_subtitle_style',
            [
                'label' => esc_html__('Form Subtitle', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'form_subtitle_typography',
                'label' => esc_html__('Typography', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-form-subtitle',
            ]
        );

        $this->add_control(
            'form_subtitle_color',
            [
                'label' => esc_html__('Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#666666',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-form-subtitle' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'form_subtitle_margin',
            [
                'label' => esc_html__('Margin', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'default' => [
                    'top' => '0',
                    'right' => '0',
                    'bottom' => '20',
                    'left' => '0',
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-form-subtitle' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Input Fields Style
        $this->start_controls_section(
            'section_input_fields_style',
            [
                'label' => esc_html__('Input Fields', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'input_fields_typography',
                'label' => esc_html__('Typography', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-form-field input',
            ]
        );

        $this->add_control(
            'input_fields_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#333333',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-form-field input' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'input_fields_background',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-form-field input' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'input_fields_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'default' => [
                    'top' => '12',
                    'right' => '15',
                    'bottom' => '12',
                    'left' => '15',
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-form-field input' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'input_fields_margin',
            [
                'label' => esc_html__('Margin', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'default' => [
                    'top' => '0',
                    'right' => '0',
                    'bottom' => '15',
                    'left' => '0',
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-form-field' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'input_fields_border',
                'label' => esc_html__('Border', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-form-field input',
            ]
        );

        $this->add_control(
            'input_fields_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'default' => [
                    'top' => '3',
                    'right' => '3',
                    'bottom' => '3',
                    'left' => '3',
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-form-field input' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'input_fields_focus_color',
            [
                'label' => esc_html__('Focus Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#007cba',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-form-field input:focus' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Labels Style
        $this->start_controls_section(
            'section_labels_style',
            [
                'label' => esc_html__('Labels', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_labels' => 'yes',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'labels_typography',
                'label' => esc_html__('Typography', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-form-field label',
            ]
        );

        $this->add_control(
            'labels_color',
            [
                'label' => esc_html__('Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#333333',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-form-field label' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'labels_margin',
            [
                'label' => esc_html__('Margin', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'default' => [
                    'top' => '0',
                    'right' => '0',
                    'bottom' => '5',
                    'left' => '0',
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-form-field label' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Button Style
        $this->start_controls_section(
            'section_button_style',
            [
                'label' => esc_html__('Buttons', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'button_typography',
                'label' => esc_html__('Typography', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-form-button',
            ]
        );

        $this->start_controls_tabs('button_style_tabs');

        $this->start_controls_tab(
            'button_normal_tab',
            [
                'label' => esc_html__('Normal', 'king-addons'),
            ]
        );

        $this->add_control(
            'button_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-form-button' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_background',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#007cba',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-form-button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'button_hover_tab',
            [
                'label' => esc_html__('Hover', 'king-addons'),
            ]
        );

        $this->add_control(
            'button_hover_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-form-button:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_hover_background',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#005a87',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-form-button:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_responsive_control(
            'button_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'default' => [
                    'top' => '12',
                    'right' => '30',
                    'bottom' => '12',
                    'left' => '30',
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-form-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'separator' => 'before',
            ]
        );

        $this->add_responsive_control(
            'button_margin',
            [
                'label' => esc_html__('Margin', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'default' => [
                    'top' => '10',
                    'right' => '0',
                    'bottom' => '15',
                    'left' => '0',
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-form-button' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'button_border',
                'label' => esc_html__('Border', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-form-button',
            ]
        );

        $this->add_control(
            'button_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'default' => [
                    'top' => '3',
                    'right' => '3',
                    'bottom' => '3',
                    'left' => '3',
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-form-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'button_width',
            [
                'label' => esc_html__('Width', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 1000,
                    ],
                    '%' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'unit' => '%',
                    'size' => 100,
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-form-button' => 'width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Links Style
        $this->start_controls_section(
            'section_links_style',
            [
                'label' => esc_html__('Links', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'links_typography',
                'label' => esc_html__('Typography', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-form-link, {{WRAPPER}} .king-addons-form-toggle',
            ]
        );

        $this->start_controls_tabs('links_style_tabs');

        $this->start_controls_tab(
            'links_normal_tab',
            [
                'label' => esc_html__('Normal', 'king-addons'),
            ]
        );

        $this->add_control(
            'links_color',
            [
                'label' => esc_html__('Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#007cba',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-form-link, {{WRAPPER}} .king-addons-form-toggle' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'links_hover_tab',
            [
                'label' => esc_html__('Hover', 'king-addons'),
            ]
        );

        $this->add_control(
            'links_hover_color',
            [
                'label' => esc_html__('Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#005a87',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-form-link:hover, {{WRAPPER}} .king-addons-form-toggle:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_responsive_control(
            'links_margin',
            [
                'label' => esc_html__('Margin', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'default' => [
                    'top' => '10',
                    'right' => '0',
                    'bottom' => '0',
                    'left' => '0',
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-form-link, {{WRAPPER}} .king-addons-form-toggle' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'separator' => 'before',
            ]
        );

        $this->end_controls_section();

        // Messages Style
        $this->start_controls_section(
            'section_messages_style',
            [
                'label' => esc_html__('Messages', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'messages_typography',
                'label' => esc_html__('Typography', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-form-message',
            ]
        );

        $this->add_control(
            'success_message_color',
            [
                'label' => esc_html__('Success Message Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#4caf50',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-form-message.success' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'error_message_color',
            [
                'label' => esc_html__('Error Message Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#f44336',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-form-message.error' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'messages_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'default' => [
                    'top' => '10',
                    'right' => '15',
                    'bottom' => '10',
                    'left' => '15',
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-form-message' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'messages_margin',
            [
                'label' => esc_html__('Margin', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'default' => [
                    'top' => '0',
                    'right' => '0',
                    'bottom' => '15',
                    'left' => '0',
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-form-message' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'messages_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'default' => [
                    'top' => '3',
                    'right' => '3',
                    'bottom' => '3',
                    'left' => '3',
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-form-message' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render()
    {
        $settings = $this->get_settings_for_display();
        
        if (king_addons_freemius()->can_use_premium_code__premium_only()) {
            $this->render_pro($settings);
        } else {
            $this->render_free($settings);
        }
    }

    /**
     * Render Pro version with all features
     */
    private function render_pro($settings)
    {
        // Full implementation with all features
        $this->render_full_form($settings);
    }

    /**
     * Render Free version with limited features
     */
    private function render_free($settings)
    {
        // Disable premium features for free users
        $settings['custom_fields'] = []; // No custom fields in free
        $settings['enable_social_login'] = 'no'; // No social login in free
        $settings['enable_mailchimp_integration'] = 'no'; // No Mailchimp in free
        $settings['enable_admin_email'] = 'no'; // No admin emails in free
        
        // Render with limited settings
        $this->render_full_form($settings);
        
        // Show upgrade notice if premium features are attempted
        if ($this->get_settings('enable_social_login') === 'yes' || 
            $this->get_settings('enable_mailchimp_integration') === 'yes' || 
            !empty($this->get_settings('custom_fields'))) {
            ?>
            <div class="king-addons-pro-notice-widget" style="text-align: center; padding: 20px; background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; margin: 10px 0;">
                <h4 style="margin: 0 0 10px 0; color: #495057;">🔒 Premium Features Detected</h4>
                <p style="margin: 0 0 15px 0; color: #6c757d;">You have configured premium features that require King Addons Pro.</p>
                <a href="https://kingaddons.com/pricing/?utm_source=kng-widget-login-register-form-frontend-upgrade-pro&utm_medium=widget&utm_campaign=kng" 
                   target="_blank" 
                   style="display: inline-block; padding: 10px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 5px; font-weight: 500;">
                   Upgrade to Pro
                </a>
            </div>
            <?php
        }
    }

    /**
     * Full form rendering (used by both free and pro)
     */
    private function render_full_form($settings)
    {
        $widget_id = $this->get_id();
        $default_form = $settings['default_form_type'];
        $enable_ajax = $settings['enable_ajax'] === 'yes';
        $show_labels = $settings['show_labels'] === 'yes';

        // Check if user registration is enabled
        $user_can_register = get_option('users_can_register');

        // Check if user is already logged in and should be redirected
        if (is_user_logged_in() && $settings['redirect_logout_user'] === 'yes' && !empty($settings['redirect_logout_url']['url'])) {
            $redirect_url = $settings['redirect_logout_url']['url'];
            ?>
            <script>
                window.location.href = '<?php echo esc_js($redirect_url); ?>';
            </script>
            <?php
            return;
        }

        // Enqueue reCAPTCHA API if enabled
        if ($settings['enable_recaptcha'] === 'yes' && !empty($settings['recaptcha_site_key'])) {
            $recaptcha_url = 'https://www.google.com/recaptcha/api.js';
            if ($settings['recaptcha_version'] === 'v3') {
                $recaptcha_url .= '?render=' . $settings['recaptcha_site_key'];
            }
            wp_enqueue_script('google-recaptcha', $recaptcha_url, [], null, true);
        }

        // Enqueue Social Login APIs if enabled
        if ($settings['enable_social_login'] === 'yes') {
            // Google Sign-In API
            if ($settings['enable_google_login'] === 'yes' && !empty($settings['google_client_id'])) {
                wp_enqueue_script('google-signin-api', 'https://accounts.google.com/gsi/client', [], null, true);
            }
            
            // Facebook SDK
            if ($settings['enable_facebook_login'] === 'yes' && !empty($settings['facebook_app_id'])) {
                $fb_app_id = $settings['facebook_app_id'];
                $fb_script = "
                window.fbAsyncInit = function() {
                    FB.init({
                        appId: '{$fb_app_id}',
                        cookie: true,
                        xfbml: true,
                        version: 'v18.0'
                    });
                };
                (function(d, s, id){
                    var js, fjs = d.getElementsByTagName(s)[0];
                    if (d.getElementById(id)) {return;}
                    js = d.createElement(s); js.id = id;
                    js.src = 'https://connect.facebook.net/en_US/sdk.js';
                    fjs.parentNode.insertBefore(js, fjs);
                }(document, 'script', 'facebook-jssdk'));
                ";
                wp_add_inline_script('king-addons-login-register-form-script', $fb_script);
            }
        }

        // AJAX handlers are now registered in constructor

        ?>
        <div class="king-addons-login-register-form-wrapper" 
             data-widget-id="<?php echo esc_attr($widget_id); ?>" 
             data-ajax="<?php echo $enable_ajax ? 'true' : 'false'; ?>"
             data-recaptcha-secret-key="<?php echo esc_attr($settings['recaptcha_secret_key'] ?? ''); ?>"
             data-recaptcha-threshold="<?php echo esc_attr($settings['recaptcha_score_threshold']['size'] ?? '0.5'); ?>"
             data-redirect-login="<?php echo esc_attr($settings['redirect_after_login']['url'] ?? ''); ?>"
             data-redirect-register="<?php echo esc_attr($settings['redirect_after_register']['url'] ?? ''); ?>"
             data-terms-required="<?php echo esc_attr($settings['terms_required'] ?? 'no'); ?>"
             data-enable-user-email="<?php echo esc_attr($settings['enable_user_email'] ?? 'yes'); ?>"
             data-user-email-subject="<?php echo esc_attr($settings['user_email_subject'] ?? ''); ?>"
             data-user-email-content="<?php echo esc_attr($settings['user_email_content'] ?? ''); ?>"
             data-enable-admin-email="<?php echo esc_attr($settings['enable_admin_email'] ?? 'no'); ?>"
             data-admin-email-address="<?php echo esc_attr($settings['admin_email_address'] ?? ''); ?>"
             data-admin-email-subject="<?php echo esc_attr($settings['admin_email_subject'] ?? ''); ?>"
             data-admin-email-content="<?php echo esc_attr($settings['admin_email_content'] ?? ''); ?>"
             data-enable-mailchimp="<?php echo esc_attr($settings['enable_mailchimp_integration'] ?? 'no'); ?>"
             data-mailchimp-api-key="<?php echo esc_attr($settings['mailchimp_api_key'] ?? ''); ?>"
             data-mailchimp-list-id="<?php echo esc_attr($settings['mailchimp_list_id'] ?? ''); ?>"
             data-mailchimp-double-optin="<?php echo esc_attr($settings['mailchimp_double_optin'] ?? 'no'); ?>"
             data-enable-social-login="<?php echo esc_attr($settings['enable_social_login'] ?? 'no'); ?>"
             data-google-client-id="<?php echo esc_attr($settings['google_client_id'] ?? ''); ?>"
             data-google-client-secret="<?php echo esc_attr($settings['google_client_secret'] ?? ''); ?>"
             data-facebook-app-id="<?php echo esc_attr($settings['facebook_app_id'] ?? ''); ?>"
             data-facebook-app-secret="<?php echo esc_attr($settings['facebook_app_secret'] ?? ''); ?>"
             data-auto-login="<?php echo esc_attr($settings['auto_login_after_register'] ?? 'yes'); ?>">
             
             <!-- Form Messages Container -->
             <div class="king-addons-form-message" style="display: none;"></div>
             
             <?php $this->render_form_header($settings); ?>
             
             <!-- Login Form -->
             <div class="king-addons-login-register-form king-addons-login-form <?php echo $default_form === 'register' ? 'king-addons-form-hidden' : ''; ?>" 
                  id="king-addons-login-form-<?php echo esc_attr($widget_id); ?>">
                 
                 <?php if (!empty($settings['login_form_title'])): ?>
                     <h3 class="king-addons-form-title"><?php echo esc_html($settings['login_form_title']); ?></h3>
                 <?php endif; ?>
                 
                 <?php if (!empty($settings['login_form_subtitle'])): ?>
                     <p class="king-addons-form-subtitle"><?php echo esc_html($settings['login_form_subtitle']); ?></p>
                 <?php endif; ?>

                 <div class="king-addons-form-message"></div>

                 <form class="king-addons-form" method="post">
                     <?php wp_nonce_field('king_addons_login_action', 'king_addons_login_nonce'); ?>
                     
                     <div class="king-addons-form-field">
                         <?php if ($show_labels && !empty($settings['login_username_label'])): ?>
                             <label for="king-addons-login-username-<?php echo esc_attr($widget_id); ?>">
                                 <?php echo esc_html($settings['login_username_label']); ?>
                             </label>
                         <?php endif; ?>
                         <input type="text" 
                                id="king-addons-login-username-<?php echo esc_attr($widget_id); ?>"
                                name="username" 
                                placeholder="<?php echo esc_attr($settings['login_username_placeholder']); ?>" 
                                required>
                     </div>

                     <div class="king-addons-form-field <?php echo $settings['show_password_visibility_login'] === 'yes' ? 'king-addons-password-field' : ''; ?>">
                         <?php if ($show_labels && !empty($settings['login_password_label'])): ?>
                             <label for="king-addons-login-password-<?php echo esc_attr($widget_id); ?>">
                                 <?php echo esc_html($settings['login_password_label']); ?>
                             </label>
                         <?php endif; ?>
                         <div class="king-addons-password-input-wrapper">
                             <input type="password" 
                                    id="king-addons-login-password-<?php echo esc_attr($widget_id); ?>"
                                    name="password" 
                                    placeholder="<?php echo esc_attr($settings['login_password_placeholder']); ?>" 
                                    required>
                             <?php if ($settings['show_password_visibility_login'] === 'yes'): ?>
                                 <button type="button" class="king-addons-password-toggle" aria-label="Toggle password visibility">
                                     <span class="king-addons-password-toggle-icon">👁</span>
                                 </button>
                             <?php endif; ?>
                         </div>
                     </div>

                     <?php if ($settings['show_remember_me'] === 'yes'): ?>
                         <div class="king-addons-form-field king-addons-checkbox-field">
                             <label>
                                 <input type="checkbox" name="remember" value="1">
                                 <?php echo esc_html($settings['remember_me_text']); ?>
                             </label>
                         </div>
                     <?php endif; ?>

                     <?php $this->render_recaptcha($settings, 'login'); ?>

                     <div class="king-addons-form-field">
                         <button type="submit" class="king-addons-form-button king-addons-login-button">
                             <?php echo esc_html($settings['login_button_text']); ?>
                         </button>
                     </div>

                                             <div class="king-addons-form-links">
                             <?php if ($settings['show_lost_password_link'] === 'yes'): ?>
                                 <span class="king-addons-form-toggle" data-toggle="lostpassword">
                                     <?php echo esc_html($settings['lost_password_link_text']); ?>
                                 </span>
                             <?php endif; ?>

                             <?php if ($user_can_register && $settings['show_register_link'] === 'yes'): ?>
                                 <span class="king-addons-form-toggle" data-toggle="register">
                                     <?php echo esc_html($settings['register_link_text']); ?>
                                 </span>
                             <?php endif; ?>
                         </div>
                     </form>

                     <?php $this->render_social_login($settings, 'login'); ?>
                 </div>

             <!-- Register Form -->
             <?php if ($user_can_register): ?>
                 <div class="king-addons-login-register-form king-addons-register-form <?php echo $default_form === 'login' ? 'king-addons-form-hidden' : ''; ?>" 
                      id="king-addons-register-form-<?php echo esc_attr($widget_id); ?>">
                     
                     <?php if (!empty($settings['register_form_title'])): ?>
                         <h3 class="king-addons-form-title"><?php echo esc_html($settings['register_form_title']); ?></h3>
                     <?php endif; ?>
                     
                     <?php if (!empty($settings['register_form_subtitle'])): ?>
                         <p class="king-addons-form-subtitle"><?php echo esc_html($settings['register_form_subtitle']); ?></p>
                     <?php endif; ?>

                     <div class="king-addons-form-message"></div>

                     <form class="king-addons-form" method="post">
                         <?php wp_nonce_field('king_addons_register_action', 'king_addons_register_nonce'); ?>
                         
                         <?php if ($settings['show_first_name'] === 'yes'): ?>
                         <div class="king-addons-form-field">
                             <?php if ($show_labels && !empty($settings['register_first_name_label'])): ?>
                                 <label for="king-addons-register-first-name-<?php echo esc_attr($widget_id); ?>">
                                     <?php echo esc_html($settings['register_first_name_label']); ?>
                                 </label>
                             <?php endif; ?>
                             <input type="text" 
                                    id="king-addons-register-first-name-<?php echo esc_attr($widget_id); ?>"
                                    name="first_name" 
                                    placeholder="<?php echo esc_attr($settings['register_first_name_placeholder']); ?>">
                         </div>
                         <?php endif; ?>

                         <?php if ($settings['show_last_name'] === 'yes'): ?>
                         <div class="king-addons-form-field">
                             <?php if ($show_labels && !empty($settings['register_last_name_label'])): ?>
                                 <label for="king-addons-register-last-name-<?php echo esc_attr($widget_id); ?>">
                                     <?php echo esc_html($settings['register_last_name_label']); ?>
                                 </label>
                             <?php endif; ?>
                             <input type="text" 
                                    id="king-addons-register-last-name-<?php echo esc_attr($widget_id); ?>"
                                    name="last_name" 
                                    placeholder="<?php echo esc_attr($settings['register_last_name_placeholder']); ?>">
                         </div>
                         <?php endif; ?>

                         <div class="king-addons-form-field">
                             <?php if ($show_labels && !empty($settings['register_email_label'])): ?>
                                 <label for="king-addons-register-email-<?php echo esc_attr($widget_id); ?>">
                                     <?php echo esc_html($settings['register_email_label']); ?>
                                 </label>
                             <?php endif; ?>
                             <input type="email" 
                                    id="king-addons-register-email-<?php echo esc_attr($widget_id); ?>"
                                    name="email" 
                                    placeholder="<?php echo esc_attr($settings['register_email_placeholder']); ?>" 
                                    required>
                         </div>

                         <div class="king-addons-form-field">
                             <?php if ($show_labels && !empty($settings['register_username_label'])): ?>
                                 <label for="king-addons-register-username-<?php echo esc_attr($widget_id); ?>">
                                     <?php echo esc_html($settings['register_username_label']); ?>
                                 </label>
                             <?php endif; ?>
                             <input type="text" 
                                    id="king-addons-register-username-<?php echo esc_attr($widget_id); ?>"
                                    name="username" 
                                    placeholder="<?php echo esc_attr($settings['register_username_placeholder']); ?>" 
                                    required>
                         </div>

                         <?php if ($settings['show_website'] === 'yes'): ?>
                         <div class="king-addons-form-field">
                             <?php if ($show_labels && !empty($settings['register_website_label'])): ?>
                                 <label for="king-addons-register-website-<?php echo esc_attr($widget_id); ?>">
                                     <?php echo esc_html($settings['register_website_label']); ?>
                                 </label>
                             <?php endif; ?>
                             <input type="url" 
                                    id="king-addons-register-website-<?php echo esc_attr($widget_id); ?>"
                                    name="website" 
                                    placeholder="<?php echo esc_attr($settings['register_website_placeholder']); ?>">
                         </div>
                         <?php endif; ?>

                         <?php if ($settings['show_phone'] === 'yes'): ?>
                         <div class="king-addons-form-field">
                             <?php if ($show_labels && !empty($settings['register_phone_label'])): ?>
                                 <label for="king-addons-register-phone-<?php echo esc_attr($widget_id); ?>">
                                     <?php echo esc_html($settings['register_phone_label']); ?>
                                 </label>
                             <?php endif; ?>
                             <input type="tel" 
                                    id="king-addons-register-phone-<?php echo esc_attr($widget_id); ?>"
                                    name="phone" 
                                    placeholder="<?php echo esc_attr($settings['register_phone_placeholder']); ?>">
                         </div>
                         <?php endif; ?>

                         <div class="king-addons-form-field <?php echo $settings['show_password_visibility_register'] === 'yes' ? 'king-addons-password-field' : ''; ?>">
                             <?php if ($show_labels && !empty($settings['register_password_label'])): ?>
                                 <label for="king-addons-register-password-<?php echo esc_attr($widget_id); ?>">
                                     <?php echo esc_html($settings['register_password_label']); ?>
                                 </label>
                             <?php endif; ?>
                             <div class="king-addons-password-input-wrapper">
                                 <input type="password" 
                                        id="king-addons-register-password-<?php echo esc_attr($widget_id); ?>"
                                        name="password" 
                                        placeholder="<?php echo esc_attr($settings['register_password_placeholder']); ?>" 
                                        required>
                                 <?php if ($settings['show_password_visibility_register'] === 'yes'): ?>
                                     <button type="button" class="king-addons-password-toggle" aria-label="Toggle password visibility">
                                         <span class="king-addons-password-toggle-icon">👁</span>
                                     </button>
                                 <?php endif; ?>
                             </div>
                         </div>

                         <div class="king-addons-form-field <?php echo $settings['show_password_visibility_register'] === 'yes' ? 'king-addons-password-field' : ''; ?>">
                             <?php if ($show_labels && !empty($settings['register_confirm_password_label'])): ?>
                                 <label for="king-addons-register-confirm-password-<?php echo esc_attr($widget_id); ?>">
                                     <?php echo esc_html($settings['register_confirm_password_label']); ?>
                                 </label>
                             <?php endif; ?>
                             <div class="king-addons-password-input-wrapper">
                                 <input type="password" 
                                        id="king-addons-register-confirm-password-<?php echo esc_attr($widget_id); ?>"
                                        name="confirm_password" 
                                        placeholder="<?php echo esc_attr($settings['register_confirm_password_placeholder']); ?>" 
                                        required>
                                 <?php if ($settings['show_password_visibility_register'] === 'yes'): ?>
                                     <button type="button" class="king-addons-password-toggle" aria-label="Toggle password visibility">
                                         <span class="king-addons-password-toggle-icon">👁</span>
                                     </button>
                                 <?php endif; ?>
                             </div>
                         </div>

                         <?php $this->render_custom_fields($settings, $show_labels); ?>

                         <?php if ($settings['show_user_role_selection'] === 'yes' && !empty($settings['allowed_user_roles'])): ?>
                         <div class="king-addons-form-field">
                             <?php if ($show_labels && !empty($settings['user_role_label'])): ?>
                                 <label for="king-addons-register-user-role-<?php echo esc_attr($widget_id); ?>">
                                     <?php echo esc_html($settings['user_role_label']); ?>
                                 </label>
                             <?php endif; ?>
                             <select id="king-addons-register-user-role-<?php echo esc_attr($widget_id); ?>" name="user_role">
                                 <?php 
                                 $user_roles = $this->get_user_roles();
                                 foreach ($settings['allowed_user_roles'] as $role_key): 
                                     if (isset($user_roles[$role_key])):
                                 ?>
                                     <option value="<?php echo esc_attr($role_key); ?>" 
                                             <?php selected($settings['default_user_role'], $role_key); ?>>
                                         <?php echo esc_html($user_roles[$role_key]); ?>
                                     </option>
                                 <?php 
                                     endif;
                                 endforeach; 
                                 ?>
                             </select>
                         </div>
                         <?php endif; ?>

                         <?php if ($settings['show_terms_conditions'] === 'yes'): ?>
                         <div class="king-addons-form-field king-addons-checkbox-field">
                             <label>
                                 <input type="checkbox" name="terms_conditions" value="1" 
                                        <?php echo $settings['terms_required'] === 'yes' ? 'required' : ''; ?>>
                                 <?php 
                                 if (!empty($settings['terms_conditions_link']['url'])):
                                     $link_target = $settings['terms_conditions_link']['is_external'] ? '_blank' : '_self';
                                     $link_nofollow = $settings['terms_conditions_link']['nofollow'] ? 'rel="nofollow"' : '';
                                 ?>
                                     <a href="<?php echo esc_url($settings['terms_conditions_link']['url']); ?>" 
                                        target="<?php echo esc_attr($link_target); ?>" 
                                        <?php echo $link_nofollow; ?>>
                                         <?php echo esc_html($settings['terms_conditions_text']); ?>
                                     </a>
                                 <?php else: ?>
                                     <?php echo esc_html($settings['terms_conditions_text']); ?>
                                 <?php endif; ?>
                             </label>
                         </div>
                         <?php endif; ?>

                         <?php $this->render_recaptcha($settings, 'register'); ?>

                         <div class="king-addons-form-field">
                             <button type="submit" class="king-addons-form-button king-addons-register-button">
                                 <?php echo esc_html($settings['register_button_text']); ?>
                             </button>
                         </div>

                         <div class="king-addons-form-links">
                             <?php if ($settings['show_login_link'] === 'yes'): ?>
                                 <span class="king-addons-form-toggle" data-toggle="login">
                                     <?php echo esc_html($settings['login_link_text']); ?>
                                 </span>
                             <?php endif; ?>
                         </div>
                     </form>

                     <?php $this->render_social_login($settings, 'register'); ?>
                 </div>
             <?php endif; ?>

             <?php if ($settings['show_lost_password_link'] === 'yes'): ?>
                 <div class="king-addons-lost-password-form" id="lost-password-form-<?php echo esc_attr($widget_id); ?>" style="display: none;">
                     
                     <?php if (!empty($settings['lostpassword_form_title'])): ?>
                         <h3 class="king-addons-form-title"><?php echo esc_html($settings['lostpassword_form_title']); ?></h3>
                     <?php endif; ?>
                     
                     <?php if (!empty($settings['lostpassword_form_subtitle'])): ?>
                         <p class="king-addons-form-subtitle"><?php echo esc_html($settings['lostpassword_form_subtitle']); ?></p>
                     <?php endif; ?>

                     <div class="king-addons-form-message"></div>

                     <form class="king-addons-form" method="post">
                         <?php wp_nonce_field('king_addons_lostpassword_action', 'king_addons_lostpassword_nonce'); ?>
                         
                         <div class="king-addons-form-field">
                             <?php if ($show_labels && !empty($settings['lostpassword_email_label'])): ?>
                                 <label for="king-addons-lostpassword-email-<?php echo esc_attr($widget_id); ?>">
                                     <?php echo esc_html($settings['lostpassword_email_label']); ?>
                                 </label>
                             <?php endif; ?>
                             <input type="email" 
                                    id="king-addons-lostpassword-email-<?php echo esc_attr($widget_id); ?>"
                                    name="user_login" 
                                    placeholder="<?php echo esc_attr($settings['lostpassword_email_placeholder']); ?>" 
                                    required>
                         </div>

                         <div class="king-addons-form-field">
                             <button type="submit" class="king-addons-form-button king-addons-lostpassword-button">
                                 <?php echo esc_html($settings['lostpassword_button_text']); ?>
                             </button>
                         </div>

                         <div class="king-addons-form-links">
                             <?php if ($settings['show_login_link_from_lostpassword'] === 'yes'): ?>
                                 <span class="king-addons-form-toggle" data-toggle="login">
                                     <?php echo esc_html($settings['login_link_text_from_lostpassword']); ?>
                                 </span>
                             <?php endif; ?>
                         </div>
                     </form>
                 </div>
             <?php endif; ?>
         </div>
         <?php
    }




} 