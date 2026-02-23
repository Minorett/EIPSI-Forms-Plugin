/**
 * EIPSI Forms - Survey Login & Registration Frontend JS (Enhanced v1.6.0)
 * 
 * Mejoras:
 * - Magic link support
 * - Password strength meter
 * - Progress indicators
 * - Better validation
 * - Loading states
 * 
 * @package EIPSI_Forms
 * @since 1.6.0
 */

(function($) {
    'use strict';

    // Configuration
    const config = {
        ajaxUrl: window.eipsiAuth?.ajaxUrl || '/wp-admin/admin-ajax.php',
        nonce: window.eipsiAuth?.nonce || '',
        strings: window.eipsiAuth?.strings || {
            registering: 'Registrando...',
            logging_in: 'Ingresando...',
            loading: 'Cargando...',
            confirm_logout: '¿Estás seguro de que quieres cerrar sesión?'
        }
    };

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        initTabs();
        initPasswordToggle();
        initValidation();
        initSwitchLinks();
        initPasswordStrength();
        initMagicLink();
        initForms();
    });

    /**
     * Initialize tab switching
     */
    function initTabs() {
        $(document).on('click', '.eipsi-survey-login-tab', function() {
            const $btn = $(this);
            const tabId = $btn.data('tab');
            const $container = $btn.closest('.eipsi-survey-login-container');

            // Update tabs
            $container.find('.eipsi-survey-login-tab').removeClass('active');
            $btn.addClass('active');

            // Update panes
            $container.find('.eipsi-survey-login-pane').removeClass('active');
            
            if (tabId === 'login') {
                $container.find('#eipsi-login-pane').addClass('active');
            } else if (tabId === 'register') {
                $container.find('#eipsi-register-pane').addClass('active');
            } else if (tabId === 'magic') {
                $container.find('#eipsi-magic-pane').addClass('active');
            }
            
            // Trigger animation
            $container.find('.eipsi-survey-login-pane.active').hide().fadeIn(200);
        });
    }

    /**
     * Initialize password visibility toggle
     */
    function initPasswordToggle() {
        // Button toggle
        $(document).on('click', '.toggle-password', function() {
            const $btn = $(this);
            const $input = $btn.siblings('input');
            const type = $input.attr('type') === 'password' ? 'text' : 'password';
            
            $input.attr('type', type);
            $btn.find('.dashicons').toggleClass('dashicons-visibility dashicons-hidden');
            
            // Sync with checkbox if exists in the same form
            const $form = $btn.closest('form');
            $form.find('.toggle-password-checkbox').prop('checked', type === 'text');
        });

        // Checkbox toggle
        $(document).on('change', '.toggle-password-checkbox', function() {
            const isChecked = $(this).is(':checked');
            const $form = $(this).closest('form');
            const type = isChecked ? 'text' : 'password';
            
            $form.find('input[type="password"], input[type="text"]').filter('[name*="password"]').attr('type', type);
            $form.find('.toggle-password .dashicons').toggleClass('dashicons-visibility', !isChecked).toggleClass('dashicons-hidden', isChecked);
        });
    }

    /**
     * Initialize real-time validation
     */
    function initValidation() {
        // Email validation
        $(document).on('input blur', 'input[type="email"]', function() {
            const $input = $(this);
            const value = $input.val();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (value === '') {
                $input.removeClass('valid invalid');
            } else if (emailRegex.test(value)) {
                $input.addClass('valid').removeClass('invalid');
            } else {
                $input.addClass('invalid').removeClass('valid');
            }
        });

        // Register Password validation
        $(document).on('input blur', '#register-password', function() {
            const $input = $(this);
            const value = $input.val();
            
            if (value === '') {
                $input.removeClass('valid invalid');
            } else if (value.length >= 8) {
                $input.addClass('valid').removeClass('invalid');
            } else {
                $input.addClass('invalid').removeClass('valid');
            }
            
            // Trigger confirmation check if it has value
            const $confirm = $('#register-confirm-password');
            if ($confirm.val() !== '') {
                $confirm.trigger('input');
            }
            
            // Update password strength
            updatePasswordStrength(value);
        });

        // Password confirmation
        $(document).on('input blur', '#register-confirm-password', function() {
            const $input = $(this);
            const $password = $('#register-password');
            
            if ($input.val() === '') {
                $input.removeClass('valid invalid');
            } else if ($input.val() === $password.val() && $password.val().length >= 8) {
                $input.addClass('valid').removeClass('invalid');
            } else {
                $input.addClass('invalid').removeClass('valid');
            }
        });

        // Required text fields
        $(document).on('input blur', '#register-first-name, #register-last-name', function() {
            const $input = $(this);
            if ($input.val().trim() !== '') {
                $input.addClass('valid').removeClass('invalid');
            } else if ($input.is(':blur')) {
                $input.addClass('invalid').removeClass('valid');
            }
        });

        // Clear error messages on input
        $(document).on('input', '.eipsi-survey-login-form input', function() {
            const $form = $(this).closest('form');
            $form.find('.error-message, .eipsi-error-message').fadeOut(function() {
                $(this).remove();
            });
        });
    }

    /**
     * Initialize password strength meter
     */
    function initPasswordStrength() {
        // Initial state
        $('.strength-bar').removeClass('weak fair good strong');
        $('.strength-text').text('');
    }

    /**
     * Update password strength indicator
     */
    function updatePasswordStrength(password) {
        const $bar = $('.strength-bar');
        const $text = $('.strength-text');
        
        if (!password) {
            $bar.removeClass('weak fair good strong');
            $text.text('');
            return;
        }
        
        let strength = 0;
        
        // Length check
        if (password.length >= 8) strength++;
        if (password.length >= 12) strength++;
        
        // Complexity checks
        if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^A-Za-z0-9]/.test(password)) strength++;
        
        // Update UI
        $bar.removeClass('weak fair good strong');
        
        if (strength <= 2) {
            $bar.addClass('weak');
            $text.text('Débil').css('color', '#d63638');
        } else if (strength === 3) {
            $bar.addClass('fair');
            $text.text('Regular').css('color', '#f0ad4e');
        } else if (strength === 4) {
            $bar.addClass('good');
            $text.text('Buena').css('color', '#5bc0de');
        } else {
            $bar.addClass('strong');
            $text.text('Fuerte').css('color', '#00a32a');
        }
    }

    /**
     * Initialize switch links
     */
    function initSwitchLinks() {
        $(document).on('click', '.switch-to-register', function(e) {
            e.preventDefault();
            $(this).closest('.eipsi-survey-login-container').find('.eipsi-survey-login-tab[data-tab="register"]').click();
        });

        $(document).on('click', '.switch-to-login', function(e) {
            e.preventDefault();
            $(this).closest('.eipsi-survey-login-container').find('.eipsi-survey-login-tab[data-tab="login"]').click();
        });
        
        $(document).on('click', '.forgot-password-link', function(e) {
            e.preventDefault();
            $(this).closest('.eipsi-survey-login-container').find('.eipsi-survey-login-tab[data-tab="magic"]').click();
        });
    }

    /**
     * Initialize Magic Link form
     */
    function initMagicLink() {
        $(document).on('submit', '#eipsi-magic-link-form', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $submitBtn = $form.find('button[type="submit"]');
            const $btnText = $submitBtn.find('.button-text');
            const $spinner = $submitBtn.find('.eipsi-spinner');
            const originalText = $btnText.text();
            
            // Show loading state
            $btnText.text('Enviando...');
            $spinner.show();
            $submitBtn.prop('disabled', true);
            $form.find('.error-message, .success-message').remove();
            
            const formData = {
                action: 'eipsi_request_magic_link',
                nonce: config.nonce,
                survey_id: $form.find('[name="survey_id"]').val(),
                email: $form.find('[name="email"]').val()
            };
            
            $.ajax({
                url: config.ajaxUrl,
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showMessage($form, response.data.message, 'success');
                        $form[0].reset();
                    } else {
                        showMessage($form, response.data.message || 'Error al enviar el link', 'error');
                    }
                },
                error: function() {
                    showMessage($form, 'Error de conexión. Intenta nuevamente.', 'error');
                },
                complete: function() {
                    $btnText.text(originalText);
                    $spinner.hide();
                    $submitBtn.prop('disabled', false);
                }
            });
        });
    }

    /**
     * Initialize Login and Register forms
     */
    function initForms() {
        // Login form
        $(document).on('submit', '#eipsi-participant-login-form', function(e) {
            e.preventDefault();
            handleFormSubmit($(this), 'eipsi_participant_login', config.strings.logging_in);
        });
        
        // Register form
        $(document).on('submit', '#eipsi-participant-register-form', function(e) {
            e.preventDefault();
            
            // Validate password match
            const $form = $(this);
            const password = $form.find('#register-password').val();
            const confirmPassword = $form.find('#register-confirm-password').val();
            
            if (password !== confirmPassword) {
                showMessage($form, 'Las contraseñas no coinciden.', 'error');
                return;
            }
            
            handleFormSubmit($form, 'eipsi_participant_register', config.strings.registering);
        });
    }

    /**
     * Handle form submission with loading states
     */
    function handleFormSubmit($form, action, loadingText) {
        const $submitBtn = $form.find('button[type="submit"]');
        const $btnText = $submitBtn.find('.button-text');
        const $spinner = $submitBtn.find('.eipsi-spinner');
        const originalText = $btnText.text();
        
        // Show loading state
        $btnText.text(loadingText);
        $spinner.show();
        $submitBtn.prop('disabled', true);
        $form.find('.error-message, .success-message').remove();
        
        // Collect form data
        const formData = $form.serialize();
        const data = formData + '&action=' + action + '&nonce=' + config.nonce;
        
        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showMessage($form, response.data.message, 'success');
                    
                    // Update step indicator
                    updateStepIndicator(2);
                    
                    // Redirect if provided
                    if (response.data.redirect) {
                        setTimeout(function() {
                            window.location.href = response.data.redirect;
                        }, 1000);
                    } else {
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    }
                } else {
                    showMessage($form, response.data.message || 'Error en la operación', 'error');
                }
            },
            error: function() {
                showMessage($form, 'Error de conexión. Intenta nuevamente.', 'error');
            },
            complete: function() {
                $btnText.text(originalText);
                $spinner.hide();
                $submitBtn.prop('disabled', false);
            }
        });
    }

    /**
     * Show message in form
     */
    function showMessage($form, message, type) {
        const $message = $('<div class="' + type + '-message" role="' + (type === 'error' ? 'alert' : 'status') + '">' + message + '</div>');
        $form.prepend($message);
        
        // Auto-remove success messages
        if (type === 'success') {
            setTimeout(function() {
                $message.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }
    }

    /**
     * Update step indicator
     */
    function updateStepIndicator(step) {
        $('.eipsi-step').removeClass('active completed');
        
        $('.eipsi-step').each(function(index) {
            const stepNum = index + 1;
            if (stepNum < step) {
                $(this).addClass('completed');
                $(this).find('.eipsi-step-number').text('✓');
            } else if (stepNum === step) {
                $(this).addClass('active');
            }
        });
    }

})(jQuery);
