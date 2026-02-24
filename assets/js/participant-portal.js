/**
 * EIPSI Forms - Participant Portal Authentication
 * 
 * Handles login, registration, magic link, and logout for participants
 * 
 * @package EIPSI_Forms
 * @since 2.0.0
 */

(function($) {
    'use strict';
    
    // Auth state
    var authState = {
        isLoading: false,
        lastAction: null
    };
    
    /**
     * Initialize auth handlers
     */
    function init() {
        // Tab switching
        $(document).on('click', '.eipsi-survey-login-tab', handleTabSwitch);
        $(document).on('click', '.switch-to-login, .switch-to-register', handleSwitchTab);
        $(document).on('click', '.forgot-password-link', handleForgotPassword);
        
        // Form submissions
        $(document).on('submit', '#eipsi-participant-login-form', handleLoginSubmit);
        $(document).on('submit', '#eipsi-participant-register-form', handleRegisterSubmit);
        $(document).on('submit', '#eipsi-magic-link-form', handleMagicLinkSubmit);
        
        // Logout
        $(document).on('click', '#eipsi-logout-button', handleLogout);
        
        // Password visibility toggle
        $(document).on('click', '.toggle-password, .toggle-password-checkbox', handlePasswordToggle);
        
        // Password strength meter
        $(document).on('input', '#register-password', handlePasswordStrength);
        
        // Form validation
        initFormValidation();
        
        // Check session on page load
        checkSessionStatus();
    }
    
    /**
     * Handle tab switching
     */
    function handleTabSwitch(e) {
        e.preventDefault();
        
        var $tab = $(this);
        var targetTab = $tab.data('tab');
        
        // Update tab states
        $('.eipsi-survey-login-tab').removeClass('active');
        $tab.addClass('active');
        
        // Update pane visibility
        $('.eipsi-survey-login-pane').removeClass('active');
        var paneId = '#eipsi-' + targetTab + '-pane';
        $(paneId).addClass('active');
        
        // Clear any existing messages
        clearMessages();
    }
    
    /**
     * Handle switch to login/register links
     */
    function handleSwitchTab(e) {
        e.preventDefault();
        
        var targetTab = $(this).hasClass('switch-to-login') ? 'login' : 'register';
        $('.eipsi-survey-login-tab[data-tab="' + targetTab + '"]').trigger('click');
    }
    
    /**
     * Handle forgot password link
     */
    function handleForgotPassword(e) {
        e.preventDefault();
        $('.eipsi-survey-login-tab[data-tab="magic"]').trigger('click');
    }
    
    /**
     * Handle login form submission
     */
    function handleLoginSubmit(e) {
        e.preventDefault();
        
        if (authState.isLoading) return;
        
        var $form = $(this);
        var $submitBtn = $form.find('button[type="submit"]');
        
        // Get form data
        var formData = {
            action: 'eipsi_participant_login',
            nonce: eipsiAuth.nonce,
            email: $form.find('input[name="email"]').val(),
            password: $form.find('input[name="password"]').val(),
            survey_id: $form.find('input[name="survey_id"]').val(),
            remember: $form.find('input[name="remember"]').is(':checked'),
            redirect_url: $form.closest('.eipsi-survey-login-container').data('redirect') || ''
        };
        
        // Validate
        if (!validateEmail(formData.email)) {
            showFieldError($form.find('input[name="email"]'), eipsiAuth.strings.invalid_email);
            return;
        }
        
        if (formData.password.length < 8) {
            showFieldError($form.find('input[name="password"]'), eipsiAuth.strings.short_password);
            return;
        }
        
        // Set loading state
        setLoadingState($submitBtn, true);
        authState.isLoading = true;
        authState.lastAction = 'login';
        
        // Send request
        $.ajax({
            url: eipsiAuth.ajaxUrl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showSuccess(response.data.message);
                    
                    // Redirect after short delay
                    setTimeout(function() {
                        window.location.href = response.data.redirect_url;
                    }, 1000);
                } else {
                    showError(response.data.message);
                    
                    // Handle rate limiting
                    if (response.data.code === 'rate_limited') {
                        showRateLimitWarning(response.data.retry_after);
                    }
                    
                    // Handle email exists - show login prompt
                    if (response.data.show_login_link) {
                        showLoginPrompt();
                    }
                }
            },
            error: function() {
                showError(eipsiAuth.strings.network_error);
            },
            complete: function() {
                setLoadingState($submitBtn, false);
                authState.isLoading = false;
            }
        });
    }
    
    /**
     * Handle registration form submission
     */
    function handleRegisterSubmit(e) {
        e.preventDefault();
        
        if (authState.isLoading) return;
        
        var $form = $(this);
        var $submitBtn = $form.find('button[type="submit"]');
        
        // Get form data
        var formData = {
            action: 'eipsi_participant_register',
            nonce: eipsiAuth.nonce,
            email: $form.find('input[name="email"]').val(),
            password: $form.find('input[name="password"]').val(),
            confirm_password: $form.find('input[name="confirm_password"]').val(),
            first_name: $form.find('input[name="first_name"]').val(),
            last_name: $form.find('input[name="last_name"]').val(),
            study_code: $form.find('input[name="study_code"]').val(),
            survey_id: $form.find('input[name="survey_id"]').val(),
            accept_terms: $form.find('input[name="accept_terms"]').is(':checked'),
            redirect_url: $form.closest('.eipsi-survey-login-container').data('redirect') || ''
        };
        
        // Validate
        if (!validateEmail(formData.email)) {
            showFieldError($form.find('input[name="email"]'), eipsiAuth.strings.invalid_email);
            return;
        }
        
        if (formData.password.length < 8) {
            showFieldError($form.find('input[name="password"]'), eipsiAuth.strings.short_password);
            return;
        }
        
        if (formData.password !== formData.confirm_password) {
            showFieldError($form.find('input[name="confirm_password"]'), eipsiAuth.strings.password_mismatch);
            return;
        }
        
        if (!formData.first_name || !formData.last_name) {
            showError(eipsiAuth.strings.name_required);
            return;
        }
        
        if (!formData.accept_terms) {
            showError(eipsiAuth.strings.terms_required);
            return;
        }
        
        // Set loading state
        setLoadingState($submitBtn, true);
        authState.isLoading = true;
        authState.lastAction = 'register';
        
        // Send request
        $.ajax({
            url: eipsiAuth.ajaxUrl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showSuccess(response.data.message);
                    
                    if (response.data.requires_login) {
                        // Switch to login tab
                        setTimeout(function() {
                            $('.eipsi-survey-login-tab[data-tab="login"]').trigger('click');
                        }, 2000);
                    } else if (response.data.redirect_url) {
                        // Redirect after short delay
                        setTimeout(function() {
                            window.location.href = response.data.redirect_url;
                        }, 1000);
                    }
                } else {
                    showError(response.data.message);
                    
                    // Handle email exists - show login prompt
                    if (response.data.show_login_link) {
                        showLoginPrompt();
                    }
                }
            },
            error: function() {
                showError(eipsiAuth.strings.network_error);
            },
            complete: function() {
                setLoadingState($submitBtn, false);
                authState.isLoading = false;
            }
        });
    }
    
    /**
     * Handle magic link form submission
     */
    function handleMagicLinkSubmit(e) {
        e.preventDefault();
        
        if (authState.isLoading) return;
        
        var $form = $(this);
        var $submitBtn = $form.find('button[type="submit"]');
        
        // Get form data
        var formData = {
            action: 'eipsi_participant_magic_link',
            nonce: eipsiAuth.nonce,
            email: $form.find('input[name="email"]').val(),
            study_code: $form.find('input[name="study_code"]').val(),
            survey_id: $form.find('input[name="survey_id"]').val()
        };
        
        // Validate
        if (!validateEmail(formData.email)) {
            showFieldError($form.find('input[name="email"]'), eipsiAuth.strings.invalid_email);
            return;
        }
        
        // Set loading state
        setLoadingState($submitBtn, true);
        authState.isLoading = true;
        authState.lastAction = 'magic_link';
        
        // Send request
        $.ajax({
            url: eipsiAuth.ajaxUrl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showSuccess(response.data.message);
                    $form.find('input[name="email"]').val('');
                } else {
                    showError(response.data.message);
                }
            },
            error: function() {
                showError(eipsiAuth.strings.network_error);
            },
            complete: function() {
                setLoadingState($submitBtn, false);
                authState.isLoading = false;
            }
        });
    }
    
    /**
     * Handle logout
     */
    function handleLogout(e) {
        e.preventDefault();
        
        if (!confirm(eipsiAuth.strings.confirm_logout)) {
            return;
        }
        
        var $btn = $(this);
        var nonce = $btn.data('nonce');
        
        setLoadingState($btn, true);
        
        $.ajax({
            url: eipsiAuth.ajaxUrl,
            type: 'POST',
            data: {
                action: 'eipsi_participant_logout',
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = response.data.redirect_url;
                } else {
                    showError(response.data.message);
                    setLoadingState($btn, false);
                }
            },
            error: function() {
                showError(eipsiAuth.strings.network_error);
                setLoadingState($btn, false);
            }
        });
    }
    
    /**
     * Handle password visibility toggle
     */
    function handlePasswordToggle(e) {
        var $input = $(this).closest('.eipsi-input-wrapper').find('input[type="password"], input[type="text"]');
        
        if ($input.attr('type') === 'password') {
            $input.attr('type', 'text');
            $(this).find('.dashicons').removeClass('dashicons-visibility').addClass('dashicons-hidden');
        } else {
            $input.attr('type', 'password');
            $(this).find('.dashicons').removeClass('dashicons-hidden').addClass('dashicons-visibility');
        }
    }
    
    /**
     * Handle password strength meter
     */
    function handlePasswordStrength(e) {
        var password = $(this).val();
        var strength = calculatePasswordStrength(password);
        
        var $meter = $(this).closest('.eipsi-form-group').find('.password-strength-meter');
        var $bar = $meter.find('.strength-bar');
        var $text = $meter.find('.strength-text');
        
        // Update bar
        $bar.css('width', strength.percentage + '%');
        $bar.css('background-color', strength.color);
        
        // Update text
        $text.text(strength.label);
        $text.css('color', strength.color);
    }
    
    /**
     * Calculate password strength
     */
    function calculatePasswordStrength(password) {
        var score = 0;
        
        if (password.length >= 8) score += 20;
        if (password.length >= 12) score += 10;
        if (/[a-z]/.test(password)) score += 10;
        if (/[A-Z]/.test(password)) score += 15;
        if (/[0-9]/.test(password)) score += 15;
        if (/[^a-zA-Z0-9]/.test(password)) score += 20;
        
        if (score < 30) {
            return { percentage: score, color: '#dc3545', label: eipsiAuth.strings.strength_weak };
        } else if (score < 50) {
            return { percentage: score, color: '#ffc107', label: eipsiAuth.strings.strength_fair };
        } else if (score < 70) {
            return { percentage: score, color: '#17a2b8', label: eipsiAuth.strings.strength_good };
        } else {
            return { percentage: score, color: '#28a745', label: eipsiAuth.strings.strength_strong };
        }
    }
    
    /**
     * Check session status
     */
    function checkSessionStatus() {
        var $container = $('.eipsi-survey-login-container');
        if ($container.length === 0) return;
        
        $.ajax({
            url: eipsiAuth.ajaxUrl,
            type: 'POST',
            data: {
                action: 'eipsi_participant_check_session',
                nonce: eipsiAuth.nonce
            },
            success: function(response) {
                if (response.success) {
                    // User is logged in - redirect to study
                    var redirectUrl = $container.data('redirect') || '';
                    if (redirectUrl) {
                        window.location.href = redirectUrl;
                    }
                }
            }
        });
    }
    
    /**
     * Initialize form validation
     */
    function initFormValidation() {
        // Real-time email validation
        $(document).on('blur', 'input[type="email"]', function() {
            var email = $(this).val();
            var $wrapper = $(this).closest('.eipsi-input-wrapper');
            
            if (email && !validateEmail(email)) {
                $wrapper.addClass('has-error');
                $wrapper.find('.eipsi-valid-icon').hide();
            } else if (email) {
                $wrapper.removeClass('has-error');
                $wrapper.addClass('has-success');
                $wrapper.find('.eipsi-valid-icon').show();
            }
        });
        
        // Real-time password confirmation
        $(document).on('blur', '#register-confirm-password', function() {
            var password = $('#register-password').val();
            var confirm = $(this).val();
            
            if (confirm && password !== confirm) {
                showFieldError($(this), eipsiAuth.strings.password_mismatch);
            } else {
                clearFieldError($(this));
            }
        });
    }
    
    /**
     * Validate email format
     */
    function validateEmail(email) {
        var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    /**
     * Show error message
     */
    function showError(message) {
        var $container = $('.eipsi-survey-login-content');
        clearMessages();
        
        var $error = $('<div class="eipsi-auth-message eipsi-auth-error">' +
            '<span class="message-icon">❌</span>' +
            '<span class="message-text">' + message + '</span>' +
            '</div>');
        
        $container.prepend($error);
        
        // Scroll to message
        $('html, body').animate({
            scrollTop: $error.offset().top - 100
        }, 300);
    }
    
    /**
     * Show success message
     */
    function showSuccess(message) {
        var $container = $('.eipsi-survey-login-content');
        clearMessages();
        
        var $success = $('<div class="eipsi-auth-message eipsi-auth-success">' +
            '<span class="message-icon">✅</span>' +
            '<span class="message-text">' + message + '</span>' +
            '</div>');
        
        $container.prepend($success);
    }
    
    /**
     * Show field-specific error
     */
    function showFieldError($field, message) {
        var $formGroup = $field.closest('.eipsi-form-group');
        $formGroup.addClass('has-error');
        
        // Remove existing error message
        $formGroup.find('.field-error').remove();
        
        // Add error message
        $formGroup.append('<div class="field-error">' + message + '</div>');
        
        // Focus field
        $field.focus();
    }
    
    /**
     * Clear field-specific error
     */
    function clearFieldError($field) {
        var $formGroup = $field.closest('.eipsi-form-group');
        $formGroup.removeClass('has-error');
        $formGroup.find('.field-error').remove();
    }
    
    /**
     * Clear all messages
     */
    function clearMessages() {
        $('.eipsi-auth-message').remove();
        $('.field-error').remove();
        $('.has-error').removeClass('has-error');
    }
    
    /**
     * Show rate limit warning
     */
    function showRateLimitWarning(retryAfter) {
        var minutes = Math.ceil(retryAfter / 60);
        var message = eipsiAuth.strings.rate_limited.replace('%d', minutes);
        showError(message);
    }
    
    /**
     * Show login prompt for existing email
     */
    function showLoginPrompt() {
        var $container = $('.eipsi-survey-login-content');
        
        // Remove existing prompt
        $('.eipsi-login-prompt').remove();
        
        var $prompt = $('<div class="eipsi-login-prompt">' +
            '<p>' + eipsiAuth.strings.email_exists + '</p>' +
            '<a href="#" class="switch-to-login">' + eipsiAuth.strings.login_here + '</a>' +
            '</div>');
        
        $container.prepend($prompt);
    }
    
    /**
     * Set loading state on button
     */
    function setLoadingState($btn, isLoading) {
        if (isLoading) {
            $btn.prop('disabled', true);
            $btn.find('.button-text').hide();
            $btn.find('.eipsi-spinner').show();
        } else {
            $btn.prop('disabled', false);
            $btn.find('.button-text').show();
            $btn.find('.eipsi-spinner').hide();
        }
    }
    
    // Initialize on document ready
    $(document).ready(init);
    
})(jQuery);
