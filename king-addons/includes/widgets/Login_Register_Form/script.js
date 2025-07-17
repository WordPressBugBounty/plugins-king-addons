(function($) {
    'use strict';

    // Initialize when Elementor frontend is ready
    $(window).on('elementor/frontend/init', function() {
        elementorFrontend.hooks.addAction('frontend/element_ready/king-addons-login-register-form.default', function($scope) {
            initLoginRegisterForm($scope);
        });
    });

    // Initialize for non-Elementor pages
    $(document).ready(function() {
        if (typeof elementorFrontend === 'undefined') {
            $('.king-addons-login-register-form-wrapper').each(function() {
                initLoginRegisterForm($(this));
            });
        }
    });

    function initLoginRegisterForm($scope) {
        const $wrapper = $scope.find('.king-addons-login-register-form-wrapper');
        if (!$wrapper.length) return;

        const $loginForm = $wrapper.find('.king-addons-login-form');
        const $registerForm = $wrapper.find('.king-addons-register-form');
        const widgetId = $wrapper.data('widget-id');
        const isAjaxEnabled = $wrapper.data('ajax') === true;

        // Get widget settings from data attributes
        const widgetSettings = {
            recaptcha_secret_key: $wrapper.data('recaptcha-secret-key') || '',
            recaptcha_score_threshold: $wrapper.data('recaptcha-threshold') || '0.5',
            redirect_after_login: $wrapper.data('redirect-login') || '',
            redirect_after_register: $wrapper.data('redirect-register') || '',
            terms_required: $wrapper.data('terms-required') || 'no',
            enable_user_email: $wrapper.data('enable-user-email') || 'yes',
            user_email_subject: $wrapper.data('user-email-subject') || '',
            user_email_content: $wrapper.data('user-email-content') || '',
            enable_admin_email: $wrapper.data('enable-admin-email') || 'no',
            admin_email_address: $wrapper.data('admin-email-address') || '',
            admin_email_subject: $wrapper.data('admin-email-subject') || '',
            admin_email_content: $wrapper.data('admin-email-content') || '',
            enable_mailchimp_integration: $wrapper.data('enable-mailchimp') || 'no',
            mailchimp_api_key: $wrapper.data('mailchimp-api-key') || '',
            mailchimp_list_id: $wrapper.data('mailchimp-list-id') || '',
            mailchimp_double_optin: $wrapper.data('mailchimp-double-optin') || 'no',
            auto_login_after_register: $wrapper.data('auto-login') || 'yes'
        };

        // Form toggle functionality
        initFormToggle($wrapper, $loginForm, $registerForm);

        // AJAX form submission
        if (isAjaxEnabled) {
            const $lostPasswordForm = $wrapper.find('.king-addons-lost-password-form');
            initAjaxForms($wrapper, $loginForm, $registerForm, $lostPasswordForm, widgetId, widgetSettings);
        }

        // Form validation
        initFormValidation($loginForm, $registerForm);

        // Password visibility toggle
        initPasswordToggle($wrapper);

        // Social login
        initSocialLogin($wrapper);
        
        // reCAPTCHA initialization
        initRecaptcha($wrapper);
    }

    function initFormToggle($wrapper, $loginForm, $registerForm) {
        $wrapper.on('click', '.king-addons-form-toggle', function(e) {
            e.preventDefault();
            
            const toggleType = $(this).data('toggle');
            const $lostPasswordForm = $wrapper.find('.king-addons-lost-password-form');
            
            let $targetForm;
            if (toggleType === 'login') {
                $targetForm = $loginForm;
            } else if (toggleType === 'register') {
                $targetForm = $registerForm;
            } else if (toggleType === 'lostpassword') {
                $targetForm = $lostPasswordForm;
            }

            if (!$targetForm) return;

            // Clear any existing messages
            clearMessages($wrapper);

            // Hide all forms
            $loginForm.hide();
            $registerForm.hide();
            $lostPasswordForm.hide();
            
            // Show target form
            $targetForm.show();
        });
    }

    function initAjaxForms($wrapper, $loginForm, $registerForm, $lostPasswordForm, widgetId, widgetSettings) {
        // Login form submission
        $loginForm.find('form').on('submit', function(e) {
            e.preventDefault();
            handleLoginSubmission($(this), $wrapper, widgetId, widgetSettings);
        });

        // Register form submission
        $registerForm.find('form').on('submit', function(e) {
            e.preventDefault();
            handleRegisterSubmission($(this), $wrapper, widgetId, widgetSettings);
        });

        // Lost password form submission
        if ($lostPasswordForm.length) {
            $lostPasswordForm.find('form').on('submit', function(e) {
                e.preventDefault();
                handleLostPasswordSubmission($(this), $wrapper, widgetId);
            });
        }
    }

    function handleLoginSubmission($form, $wrapper, widgetId, widgetSettings) {
        const $messageContainer = $form.closest('.king-addons-login-register-form').find('.king-addons-form-message');
        const $submitButton = $form.find('.king-addons-login-button');
        
        // Clear previous messages
        clearMessages($wrapper);
        
        // Check if variables are defined
        if (typeof king_addons_login_register_vars === 'undefined') {
            console.error('King Addons Login Register: AJAX variables not loaded');
            showMessage($messageContainer, 'Configuration error. Please refresh the page.', 'error');
            return;
        }
        
        // Get form data
        const formData = {
            action: 'king_addons_user_login',
            nonce: king_addons_login_register_vars.login_nonce,
            username: $form.find('[name="username"]').val(),
            password: $form.find('[name="password"]').val(),
            remember: $form.find('[name="remember"]').is(':checked') ? 1 : 0,
            widget_id: widgetId,
            recaptcha_secret_key: widgetSettings.recaptcha_secret_key,
            recaptcha_score_threshold: widgetSettings.recaptcha_score_threshold,
            redirect_after_login: widgetSettings.redirect_after_login
        };
        
        // Add reCAPTCHA response if present
        const recaptchaResponse = $form.find('[name="g-recaptcha-response"]').val();
        if (recaptchaResponse) {
            formData['g-recaptcha-response'] = recaptchaResponse;
        }

        console.log('Submitting login form with data:', formData);

        // Validate required fields
        if (!formData.username || !formData.password) {
            showMessage($messageContainer, 'Please fill in all required fields.', 'error');
            return;
        }

        // Show loading state
        setLoadingState($form, true);
        $submitButton.prop('disabled', true);

        // Submit via AJAX
        $.ajax({
            url: king_addons_login_register_vars.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showMessage($messageContainer, response.data.message, 'success');
                    
                    // Redirect if URL provided
                    if (response.data.redirect) {
                        setTimeout(function() {
                            window.location.href = response.data.redirect;
                        }, 1500);
                    } else {
                        // Reload page
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    }
                } else {
                    showMessage($messageContainer, response.data.message || 'An error occurred. Please try again.', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Login AJAX Error:', error);
                console.error('Login XHR response:', xhr.responseText);
                console.error('Login XHR status code:', xhr.status);
                
                let errorMessage = 'Network error. Please check your connection and try again.';
                
                // Try to parse error response
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.data && response.data.message) {
                        errorMessage = response.data.message;
                    }
                } catch (e) {
                    // Use default error message
                }
                
                showMessage($messageContainer, errorMessage, 'error');
            },
            complete: function() {
                setLoadingState($form, false);
                $submitButton.prop('disabled', false);
            }
        });
    }

    function handleRegisterSubmission($form, $wrapper, widgetId, widgetSettings) {
        const $messageContainer = $form.closest('.king-addons-login-register-form').find('.king-addons-form-message');
        const $submitButton = $form.find('.king-addons-register-button');
        
        // Clear previous messages
        clearMessages($wrapper);
        
        // Check if variables are defined
        if (typeof king_addons_login_register_vars === 'undefined') {
            console.error('King Addons Login Register: AJAX variables not loaded');
            showMessage($messageContainer, 'Configuration error. Please refresh the page.', 'error');
            return;
        }
        
        // Get form data
        const formData = {
            action: 'king_addons_user_register',
            nonce: king_addons_login_register_vars.register_nonce,
            email: $form.find('[name="email"]').val(),
            username: $form.find('[name="username"]').val(),
            password: $form.find('[name="password"]').val(),
            confirm_password: $form.find('[name="confirm_password"]').val(),
            widget_id: widgetId,
            recaptcha_secret_key: widgetSettings.recaptcha_secret_key,
            recaptcha_score_threshold: widgetSettings.recaptcha_score_threshold,
            redirect_after_register: widgetSettings.redirect_after_register,
            terms_required: widgetSettings.terms_required,
            enable_user_email: widgetSettings.enable_user_email,
            user_email_subject: widgetSettings.user_email_subject,
            user_email_content: widgetSettings.user_email_content,
            enable_admin_email: widgetSettings.enable_admin_email,
            admin_email_address: widgetSettings.admin_email_address,
            admin_email_subject: widgetSettings.admin_email_subject,
            admin_email_content: widgetSettings.admin_email_content,
            enable_mailchimp_integration: widgetSettings.enable_mailchimp_integration,
            mailchimp_api_key: widgetSettings.mailchimp_api_key,
            mailchimp_list_id: widgetSettings.mailchimp_list_id,
            mailchimp_double_optin: widgetSettings.mailchimp_double_optin,
            auto_login_after_register: widgetSettings.auto_login_after_register
        };

        // Add additional fields if they exist
        const firstName = $form.find('[name="first_name"]').val();
        const lastName = $form.find('[name="last_name"]').val();
        const website = $form.find('[name="website"]').val();
        const phone = $form.find('[name="phone"]').val();
        const userRole = $form.find('[name="user_role"]').val();
        const termsConditions = $form.find('[name="terms_conditions"]').is(':checked');
        
        if (firstName) formData.first_name = firstName;
        if (lastName) formData.last_name = lastName;
        if (website) formData.website = website;
        if (phone) formData.phone = phone;
        if (userRole) formData.user_role = userRole;
        if (termsConditions) formData.terms_conditions = 1;

        // Collect custom fields from repeater
        const customFields = {};
        const customFieldLabels = {};
        
        $form.find('[name^="custom_field_"]').each(function() {
            const fieldName = $(this).attr('name');
            const fieldType = $(this).attr('type');
            const fieldLabel = $(this).closest('.king-addons-form-field').data('field-label') || fieldName.replace('custom_field_', 'Field ');
            let fieldValue;
            
            if (fieldType === 'checkbox') {
                // For checkboxes, collect all checked values
                const checkedBoxes = $form.find('[name="' + fieldName + '"]:checked');
                if (checkedBoxes.length > 0) {
                    const values = [];
                    checkedBoxes.each(function() {
                        values.push($(this).val());
                    });
                    fieldValue = values.length === 1 ? values[0] : values;
                }
            } else if (fieldType === 'radio') {
                // For radio buttons, get the checked value
                fieldValue = $form.find('[name="' + fieldName + '"]:checked').val();
            } else {
                // For other input types (text, email, etc.)
                fieldValue = $(this).val();
            }
            
            if (fieldValue && fieldValue !== '') {
                customFields[fieldName] = fieldValue;
                customFieldLabels[fieldName] = fieldLabel;
            }
        });
        
        // Also handle textarea and select fields
        $form.find('textarea[name^="custom_field_"], select[name^="custom_field_"]').each(function() {
            const fieldName = $(this).attr('name');
            const fieldValue = $(this).val();
            const fieldLabel = $(this).closest('.king-addons-form-field').data('field-label') || fieldName.replace('custom_field_', 'Field ');
            
            if (fieldValue && fieldValue !== '') {
                customFields[fieldName] = fieldValue;
                customFieldLabels[fieldName] = fieldLabel;
            }
        });
        
        // Add custom fields to form data if any exist
        if (Object.keys(customFields).length > 0) {
            formData.custom_fields = customFields;
            formData.custom_field_labels = customFieldLabels;
            console.log('Found custom fields:', customFields);
            console.log('Field labels:', customFieldLabels);
        } else {
            console.log('No custom fields found');
        }
        
        // Add reCAPTCHA response if present
        const recaptchaResponse = $form.find('[name="g-recaptcha-response"]').val();
        if (recaptchaResponse) {
            formData['g-recaptcha-response'] = recaptchaResponse;
        }

        console.log('Submitting registration form with data:', formData);

        // Client-side validation
        const validationResult = validateRegistrationForm(formData);
        if (!validationResult.valid) {
            showMessage($messageContainer, validationResult.message, 'error');
            return;
        }

        // Show loading state
        setLoadingState($form, true);
        $submitButton.prop('disabled', true);

        // Prepare FormData for file uploads
        let ajaxData = formData;
        let ajaxOptions = {
            url: king_addons_login_register_vars.ajax_url,
            type: 'POST',
            data: ajaxData,
        };
        
        // Check if there are file inputs
        const $fileInputs = $form.find('input[type="file"]');
        if ($fileInputs.length > 0) {
            // Use FormData for file uploads
            const formDataObj = new FormData();
            
            // Add all form data
            for (const key in formData) {
                if (formData.hasOwnProperty(key)) {
                    if (typeof formData[key] === 'object' && formData[key] !== null) {
                        formDataObj.append(key, JSON.stringify(formData[key]));
                    } else {
                        formDataObj.append(key, formData[key]);
                    }
                }
            }
            
            // Add files
            $fileInputs.each(function() {
                const $input = $(this);
                const files = $input[0].files;
                if (files.length > 0) {
                    for (let i = 0; i < files.length; i++) {
                        formDataObj.append($input.attr('name'), files[i]);
                    }
                }
            });
            
            ajaxOptions.data = formDataObj;
            ajaxOptions.processData = false;
            ajaxOptions.contentType = false;
        }

        // Submit via AJAX
        $.ajax(ajaxOptions).done(function(response) {
            if (response.success) {
                showMessage($messageContainer, response.data.message, 'success');
                
                // Redirect if URL provided
                if (response.data.redirect) {
                    setTimeout(function() {
                        window.location.href = response.data.redirect;
                    }, 1500);
                } else {
                    // Reload page or switch to login form
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                }
            } else {
                showMessage($messageContainer, response.data.message || 'Registration failed. Please try again.', 'error');
            }
        }).fail(function(xhr, status, error) {
            console.error('Registration AJAX Error:', error);
            console.error('Registration XHR response:', xhr.responseText);
            console.error('Registration XHR status code:', xhr.status);
            
            let errorMessage = 'Network error. Please check your connection and try again.';
            
            // Try to parse error response
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.data && response.data.message) {
                    errorMessage = response.data.message;
                }
            } catch (e) {
                // Use default error message
            }
            
            showMessage($messageContainer, errorMessage, 'error');
        }).always(function() {
            setLoadingState($form, false);
            $submitButton.prop('disabled', false);
        });
    }

    function handleLostPasswordSubmission($form, $wrapper, widgetId) {
        const $messageContainer = $form.closest('.king-addons-lost-password-form').find('.king-addons-form-message');
        const $submitButton = $form.find('.king-addons-lostpassword-button');
        
        // Clear previous messages
        clearMessages($wrapper);
        
        // Check if variables are defined
        if (typeof king_addons_login_register_vars === 'undefined') {
            console.error('King Addons Login Register: AJAX variables not loaded');
            showMessage($messageContainer, 'Configuration error. Please refresh the page.', 'error');
            return;
        }
        
        // Get form data
        const formData = {
            action: 'king_addons_user_lostpassword',
            nonce: king_addons_login_register_vars.lostpassword_nonce,
            user_login: $form.find('[name="user_login"]').val(),
            widget_id: widgetId
        };

        // Validate required fields
        if (!formData.user_login) {
            showMessage($messageContainer, 'Please enter your email address.', 'error');
            return;
        }

        // Show loading state
        setLoadingState($form, true);
        $submitButton.prop('disabled', true);

        // Submit via AJAX
        $.ajax({
            url: king_addons_login_register_vars.ajax_url,
            type: 'POST',
            data: formData,
            timeout: 30000,
            success: function(response) {
                if (response.success) {
                    showMessage($messageContainer, response.data.message, 'success');
                    $form[0].reset(); // Clear the form
                } else {
                    showMessage($messageContainer, response.data.message || 'Lost password request failed. Please try again.', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Lost Password AJAX Error:', error);
                console.error('Lost Password XHR response:', xhr.responseText);
                console.error('Lost Password XHR status code:', xhr.status);
                
                let errorMessage = 'Network error. Please check your connection and try again.';
                
                // Try to parse error response
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.data && response.data.message) {
                        errorMessage = response.data.message;
                    }
                } catch (e) {
                    // Use default error message
                }
                
                showMessage($messageContainer, errorMessage, 'error');
            },
            complete: function() {
                setLoadingState($form, false);
                $submitButton.prop('disabled', false);
            }
        });
    }

    function validateRegistrationForm(data) {
        // Check required fields
        if (!data.email || !data.username || !data.password || !data.confirm_password) {
            return {
                valid: false,
                message: 'Please fill in all required fields.'
            };
        }

        // Validate email format
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(data.email)) {
            return {
                valid: false,
                message: 'Please enter a valid email address.'
            };
        }

        // Validate username (basic validation)
        if (data.username.length < 3) {
            return {
                valid: false,
                message: 'Username must be at least 3 characters long.'
            };
        }

        // Check username characters
        const usernameRegex = /^[a-zA-Z0-9._-]+$/;
        if (!usernameRegex.test(data.username)) {
            return {
                valid: false,
                message: 'Username can only contain letters, numbers, periods, hyphens, and underscores.'
            };
        }

        // Validate password length
        if (data.password.length < 6) {
            return {
                valid: false,
                message: 'Password must be at least 6 characters long.'
            };
        }

        // Enhanced password strength validation (updated for security)
        if (data.password.length < 8) {
            return {
                valid: false,
                message: 'Password must be at least 8 characters long.'
            };
        }

        // Check for basic password strength
        const hasLower = /[a-z]/.test(data.password);
        const hasUpper = /[A-Z]/.test(data.password);
        const hasNumbers = /\d/.test(data.password);
        const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(data.password);
        
        let strengthScore = 0;
        if (hasLower) strengthScore++;
        if (hasUpper) strengthScore++;
        if (hasNumbers) strengthScore++;
        if (hasSpecial) strengthScore++;
        
        if (strengthScore < 3) {
            return {
                valid: false,
                message: 'Password must contain uppercase, lowercase, numbers, and special characters for security.'
            };
        }

        // Check against common passwords
        const commonPasswords = ['password', '123456', '123456789', 'qwerty', 'abc123', 'password123'];
        if (commonPasswords.includes(data.password.toLowerCase())) {
            return {
                valid: false,
                message: 'Please choose a more unique password. This password is too common.'
            };
        }

        // Check password confirmation
        if (data.password !== data.confirm_password) {
            return {
                valid: false,
                message: 'Passwords do not match.'
            };
        }

        // Check terms and conditions if required
        const $termsCheckbox = $('.king-addons-register-form [name="terms_conditions"]');
        if ($termsCheckbox.length && $termsCheckbox.prop('required') && !$termsCheckbox.is(':checked')) {
            return {
                valid: false,
                message: 'Please accept the Terms & Conditions.'
            };
        }

        return { valid: true };
    }

    function initFormValidation($loginForm, $registerForm) {
        // Real-time validation for registration form
        $registerForm.find('[name="confirm_password"]').on('blur keyup', function() {
            const password = $registerForm.find('[name="password"]').val();
            const confirmPassword = $(this).val();
            const $field = $(this).closest('.king-addons-form-field');
            
            // Remove existing validation classes
            $field.removeClass('field-error field-success');
            
            if (confirmPassword && password && password !== confirmPassword) {
                $field.addClass('field-error');
                showFieldError($field, 'Passwords do not match.');
            } else if (confirmPassword && password && password === confirmPassword) {
                $field.addClass('field-success');
                hideFieldError($field);
            } else {
                hideFieldError($field);
            }
        });

        // Email validation
        $registerForm.find('[name="email"]').on('blur', function() {
            const email = $(this).val();
            const $field = $(this).closest('.king-addons-form-field');
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            $field.removeClass('field-error field-success');
            
            if (email && !emailRegex.test(email)) {
                $field.addClass('field-error');
                showFieldError($field, 'Please enter a valid email address.');
            } else if (email) {
                $field.addClass('field-success');
                hideFieldError($field);
            } else {
                hideFieldError($field);
            }
        });

        // Username validation
        $registerForm.find('[name="username"]').on('blur', function() {
            const username = $(this).val();
            const $field = $(this).closest('.king-addons-form-field');
            const usernameRegex = /^[a-zA-Z0-9._-]+$/;
            
            $field.removeClass('field-error field-success');
            
            if (username && (username.length < 3 || !usernameRegex.test(username))) {
                $field.addClass('field-error');
                const message = username.length < 3 
                    ? 'Username must be at least 3 characters long.'
                    : 'Username can only contain letters, numbers, periods, hyphens, and underscores.';
                showFieldError($field, message);
            } else if (username) {
                $field.addClass('field-success');
                hideFieldError($field);
            } else {
                hideFieldError($field);
            }
        });
    }

    function showFieldError($field, message) {
        hideFieldError($field);
        $field.append('<span class="field-error-message" style="color: #f44336; font-size: 12px; margin-top: 5px; display: block;">' + message + '</span>');
    }

    function hideFieldError($field) {
        $field.find('.field-error-message').remove();
    }

    function initPasswordToggle($wrapper) {
        $wrapper.on('click', '.king-addons-password-toggle', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const $input = $button.siblings('input[type="password"], input[type="text"]');
            const $icon = $button.find('.king-addons-password-toggle-icon');
            
            if ($input.attr('type') === 'password') {
                $input.attr('type', 'text');
                $icon.text('🙈');
                $button.attr('aria-label', 'Hide password');
            } else {
                $input.attr('type', 'password');
                $icon.text('👁');
                $button.attr('aria-label', 'Show password');
            }
        });
    }

    function showMessage($container, message, type) {
        $container.removeClass('success error').addClass(type + ' show').text(message);
        
        // Auto-hide success messages after 5 seconds
        if (type === 'success') {
            setTimeout(function() {
                $container.removeClass('show');
            }, 5000);
        }
    }

    function clearMessages($wrapper) {
        $wrapper.find('.king-addons-form-message').removeClass('success error show').text('');
    }

    function setLoadingState($form, loading) {
        if (loading) {
            $form.addClass('king-addons-form-loading');
        } else {
            $form.removeClass('king-addons-form-loading');
        }
    }

    function initSocialLogin($wrapper) {
        // Google login
        $wrapper.find('.king-addons-google-login').on('click', function(e) {
            e.preventDefault();
            const $button = $(this);
            const clientId = $button.data('client-id');
            
            if (!clientId) {
                showSocialLoginError('Google Client ID not configured. Please check widget settings.');
                return;
            }
            
            // Check if Google API is loaded
            if (typeof google === 'undefined' || !google.accounts) {
                showSocialLoginError('Google API not loaded. Please check your internet connection.');
                return;
            }
            
            // Initialize Google One Tap
            google.accounts.id.initialize({
                client_id: clientId,
                callback: handleGoogleResponse,
                auto_select: false,
                cancel_on_tap_outside: true
            });
            
            // Show Google login prompt
            google.accounts.id.prompt();
        });

        // Facebook login
        $wrapper.find('.king-addons-facebook-login').on('click', function(e) {
            e.preventDefault();
            const $button = $(this);
            const appId = $button.data('app-id');
            
            if (!appId) {
                showSocialLoginError('Facebook App ID not configured. Please check widget settings.');
                return;
            }
            
            // Check if Facebook SDK is loaded
            if (typeof FB === 'undefined') {
                showSocialLoginError('Facebook SDK not loaded. Please check your internet connection.');
                return;
            }
            
            // Facebook login
            FB.login(function(response) {
                if (response.authResponse) {
                    handleFacebookResponse(response.authResponse);
                } else {
                    showSocialLoginError('Facebook login was cancelled or failed.');
                }
            }, {scope: 'email,public_profile'});
        });
        
        function handleGoogleResponse(response) {
            const $wrapper = $('.king-addons-login-register-form-wrapper');
            const widgetId = $wrapper.data('widget-id');
            const $messageContainer = $wrapper.find('.king-addons-form-message');
            
            // Show loading
            showMessage($messageContainer, 'Authenticating with Google...', 'info');
            
            $.ajax({
                url: king_addons_login_register_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'king_addons_google_login',
                    nonce: king_addons_login_register_vars.social_login_nonce,
                    google_token: response.credential,
                    widget_id: widgetId,
                    google_client_id: $wrapper.data('google-client-id')
                },
                success: function(ajaxResponse) {
                    if (ajaxResponse.success) {
                        showMessage($messageContainer, ajaxResponse.data.message, 'success');
                        if (ajaxResponse.data.redirect) {
                            setTimeout(() => {
                                window.location.href = ajaxResponse.data.redirect;
                            }, 1500);
                        } else {
                            setTimeout(() => {
                                location.reload();
                            }, 1500);
                        }
                    } else {
                        showMessage($messageContainer, ajaxResponse.data.message, 'error');
                    }
                },
                error: function() {
                    showMessage($messageContainer, 'Google login failed. Please try again.', 'error');
                }
            });
        }
        
        function handleFacebookResponse(authResponse) {
            const $wrapper = $('.king-addons-login-register-form-wrapper');
            const widgetId = $wrapper.data('widget-id');
            const $messageContainer = $wrapper.find('.king-addons-form-message');
            
            // Show loading
            showMessage($messageContainer, 'Authenticating with Facebook...', 'info');
            
            $.ajax({
                url: king_addons_login_register_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'king_addons_facebook_login',
                    nonce: king_addons_login_register_vars.social_login_nonce,
                    facebook_token: authResponse.accessToken,
                    widget_id: widgetId,
                    facebook_app_id: $wrapper.data('facebook-app-id'),
                    facebook_app_secret: $wrapper.data('facebook-app-secret')
                },
                success: function(ajaxResponse) {
                    if (ajaxResponse.success) {
                        showMessage($messageContainer, ajaxResponse.data.message, 'success');
                        if (ajaxResponse.data.redirect) {
                            setTimeout(() => {
                                window.location.href = ajaxResponse.data.redirect;
                            }, 1500);
                        } else {
                            setTimeout(() => {
                                location.reload();
                            }, 1500);
                        }
                    } else {
                        showMessage($messageContainer, ajaxResponse.data.message, 'error');
                    }
                },
                error: function() {
                    showMessage($messageContainer, 'Facebook login failed. Please try again.', 'error');
                }
            });
        }
        
        function showSocialLoginError(message) {
            const $wrapper = $('.king-addons-login-register-form-wrapper');
            const $messageContainer = $wrapper.find('.king-addons-form-message');
            showMessage($messageContainer, message, 'error');
        }
    }

    function initRecaptcha($wrapper) {
        // Initialize reCAPTCHA v2
        $wrapper.find('.king-addons-recaptcha-v2').each(function() {
            const $recaptcha = $(this);
            const sitekey = $recaptcha.data('sitekey');
            const theme = $recaptcha.data('theme') || 'light';
            const size = $recaptcha.data('size') || 'normal';

            if (typeof grecaptcha !== 'undefined' && sitekey) {
                grecaptcha.ready(function() {
                    grecaptcha.render($recaptcha[0], {
                        'sitekey': sitekey,
                        'theme': theme,
                        'size': size
                    });
                });
            }
        });

        // For reCAPTCHA v3, we'll handle it in form submission
        $wrapper.find('.king-addons-recaptcha-v3').each(function() {
            const $recaptcha = $(this);
            const sitekey = $recaptcha.data('sitekey');
            const action = $recaptcha.data('action');

            if (typeof grecaptcha !== 'undefined' && sitekey) {
                // v3 tokens are generated on form submission
                $recaptcha.closest('form').on('submit', function(e) {
                    const $form = $(this);
                    
                    if ($recaptcha.val()) {
                        // Token already exists, proceed
                        return;
                    }
                    
                    e.preventDefault();
                    
                    grecaptcha.ready(function() {
                        grecaptcha.execute(sitekey, {action: action}).then(function(token) {
                            $recaptcha.val(token);
                            $form.trigger('submit');
                        });
                    });
                });
            }
        });
    }

    // Expose functions globally for potential external use
    window.KingAddonsLoginRegister = {
        initForm: initLoginRegisterForm,
        showMessage: showMessage,
        clearMessages: clearMessages
    };

})(jQuery); 