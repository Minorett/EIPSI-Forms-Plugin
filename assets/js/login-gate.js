/**
 * Login Gate Interactivity
 * Maneja clicks en "Ingresar" y "Crear cuenta" del login gate
 */
(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Click en botones de login gate → inyectar form + cambiar tab
        $(document).on('click', '.survey-login-tab-trigger', function(e) {
            e.preventDefault();
            
            var tab = $(this).data('tab'); // 'login' o 'register'
            var surveyId = $(this).data('survey-id');
            
            // Inyectar form aquí si no existe
            var $container = $('#eipsi-login-form-container');
            if ($container.length && $container.html().trim() === '') {
                // Inyectar shortcode [eipsi_survey_login]
                $container.html(
                    '[eipsi_survey_login survey_id="' + surveyId + '"]'
                );
                // Re-procesar blocks
                if (typeof wp !== 'undefined' && wp.blocks && wp.blocks.doAction) {
                    wp.blocks.doAction('blocks.setAction', 'render');
                }
            }
            
            // Switchear tab
            $('.survey-login-tab').removeClass('active');
            $('[data-tab="' + tab + '"]').addClass('active');
            $('[data-tab-content="' + tab + '"]').show().siblings('.survey-login-tab-content').hide();
        });
    });
})(jQuery);