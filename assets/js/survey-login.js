/**
 * EIPSI Forms - Survey Login & Registration Frontend JS
 * @package EIPSI_Forms
 * @since 1.4.0
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        initTabs();
        initPasswordToggle();
        initValidation();
        initSwitchLinks();
    });

    /**
     * Initialize tab switching logic
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
            } else {
                $container.find('#eipsi-register-pane').addClass('active');
            }
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
            $btn.find('.dashicons').toggleClass('dashicons-visibility dashicons-visibility-faint');
            
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
            $form.find('.toggle-password .dashicons').toggleClass('dashicons-visibility', !isChecked).toggleClass('dashicons-visibility-faint', isChecked);
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
            $form.find('.error-message, .eipsi-error-message').fadeOut();
        });
    }

    /**
     * Initialize switch links (e.g. "Crear una nueva")
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
    }

})(jQuery);
