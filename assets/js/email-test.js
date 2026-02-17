/**
 * EIPSI Forms - Email System Test JavaScript
 * Handles test email functionality without SMTP
 * @since 1.5.4
 */

/* global jQuery, ajaxurl */
( function ( $ ) {
    'use strict';

    // DOM ready
    $( document ).ready( function () {
        // Test SMTP button
        $( '#eipsi-test-smtp' ).on( 'click', function ( e ) {
            e.preventDefault();
            testSmtp();
        } );

        // Test default email button (no SMTP needed)
        $( '#eipsi-test-default-email' ).on( 'click', function ( e ) {
            e.preventDefault();
            testDefaultEmail();
        } );

        // Get email diagnostic
        $( '#eipsi-get-email-diagnostic' ).on( 'click', function ( e ) {
            e.preventDefault();
            getEmailDiagnostic();
        } );
    } );

    /**
     * Test SMTP configuration
     */
    function testSmtp() {
        const $button = $( '#eipsi-test-smtp' );
        const $messageContainer = $( '#eipsi-smtp-message-container' );

        // Get test email if provided
        const testEmail = $( '#test-email-address' ).val() || '';

        // Show loading
        $button.prop( 'disabled', true ).text( 'Probando...' );
        hideMessage( $messageContainer );

        $.ajax( {
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'eipsi_test_smtp',
                nonce: $( '#eipsi_smtp_config_nonce' ).val(),
                test_email: testEmail
            },
            success( response ) {
                if ( response.success ) {
                    // Show success message
                    showMessage( 'success', response.data.message, $messageContainer );
                } else {
                    // Show error
                    showMessage( 'error', response.data.message, $messageContainer );
                }
            },
            error() {
                showMessage( 'error', 'Error de conexión al probar SMTP.', $messageContainer );
            },
            complete() {
                $button.prop( 'disabled', false ).text( 'Probar SMTP' );
            }
        } );
    }

    /**
     * Test default email system (wp_mail)
     */
    function testDefaultEmail() {
        const $button = $( '#eipsi-test-default-email' );
        const $results = $( '#eipsi-email-test-results' );
        const $diagnostic = $( '#eipsi-email-diagnostic' );
        
        // Get test email if provided
        const testEmail = $( '#test-email-address' ).val() || '';

        // Show loading
        $button.prop( 'disabled', true ).text( 'Probando...' );
        $results.hide();
        $diagnostic.hide();

        $.ajax( {
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'eipsi_test_default_email',
                nonce: $( '#eipsi_smtp_config_nonce' ).val(),
                test_email: testEmail
            },
            success( response ) {
                if ( response.success ) {
                    // Show success message
                    showMessage( 'success', response.data.message, $results );
                    
                    // Show details
                    $results.find( '.details' ).html( '<strong>Detalles:</strong> ' + response.data.details );
                    
                    // Show diagnostic
                    showDiagnostic( response.data.diagnostic, $diagnostic );
                    
                    $results.show();
                } else {
                    // Show error
                    showMessage( 'error', response.data.message, $results );
                    
                    // Show details
                    $results.find( '.details' ).html( '<strong>Error:</strong> ' + response.data.details );
                    
                    // Show diagnostic
                    showDiagnostic( response.data.diagnostic, $diagnostic );
                    
                    $results.show();
                }
            },
            error() {
                showMessage( 'error', 'Error de conexión al probar el email.', $results );
                $results.show();
            },
            complete() {
                $button.prop( 'disabled', false ).text( 'Probar Email Default' );
            }
        } );
    }

    /**
     * Get email system diagnostic
     */
    function getEmailDiagnostic() {
        const $button = $( '#eipsi-get-email-diagnostic' );
        const $results = $( '#eipsi-email-diagnostic' );

        // Show loading
        $button.prop( 'disabled', true ).text( 'Analizando...' );

        $.ajax( {
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'eipsi_get_email_diagnostic',
                nonce: $( '#eipsi_smtp_config_nonce' ).val()
            },
            success( response ) {
                if ( response.success ) {
                    showDiagnostic( response.data.diagnostic, $results );
                    showStats( response.data.stats, $results );
                    $results.show();
                } else {
                    showMessage( 'error', 'Error al obtener diagnóstico.', $results );
                    $results.show();
                }
            },
            error() {
                showMessage( 'error', 'Error de conexión al obtener diagnóstico.', $results );
            },
            complete() {
                $button.prop( 'disabled', false ).text( 'Ver Diagnóstico' );
            }
        } );
    }

    /**
     * Show diagnostic information
     */
    function showDiagnostic( diagnostic, $container ) {
        if ( ! diagnostic ) return;

        let html = '<div class="email-diagnostic">';
        
        // Status
        const statusClass = diagnostic.status === 'okay' ? 'success' : 'warning';
        html += '<p class="status-' + statusClass + '">';
        html += '<strong>Estado:</strong> ' + ( diagnostic.status === 'okay' ? '✅ OK' : '⚠️ Advertencias' );
        html += '</p>';

        // SMTP Status
        html += '<p><strong>SMTP Configurado:</strong> ' + ( diagnostic.smtp_configured ? '✅ Sí' : '❌ No' ) + '</p>';
        
        // Emails
        if ( diagnostic.investigator_email ) {
            html += '<p><strong>Email Investigador:</strong> ' + diagnostic.investigator_email + '</p>';
        }
        
        if ( diagnostic.admin_email ) {
            html += '<p><strong>Email Admin:</strong> ' + diagnostic.admin_email + '</p>';
        }

        // Issues
        if ( diagnostic.issues && diagnostic.issues.length > 0 ) {
            html += '<p><strong>Problemas:</strong></p><ul>';
            diagnostic.issues.forEach( function ( issue ) {
                html += '<li>⚠️ ' + issue + '</li>';
            } );
            html += '</ul>';
        }

        // Recommendations
        if ( diagnostic.recommendations && diagnostic.recommendations.length > 0 ) {
            html += '<p><strong>Recomendaciones:</strong></p><ul>';
            diagnostic.recommendations.forEach( function ( rec ) {
                html += '<li>💡 ' + rec + '</li>';
            } );
            html += '</ul>';
        }

        html += '</div>';
        
        $container.find( '.diagnostic-content' ).html( html );
    }

    /**
     * Show email stats
     */
    function showStats( stats, $container ) {
        if ( ! stats ) return;

        let html = '<div class="email-stats">';
        html += '<p><strong>Emails Enviados:</strong> ' + stats.sent + '</p>';
        html += '<p><strong>Emails Fallidos:</strong> ' + stats.failed + '</p>';
        html += '<p><strong>Tasa de Éxito:</strong> ' + stats.success_rate + '%</p>';
        html += '</div>';
        
        $container.find( '.stats-content' ).html( html );
    }

    /**
     * Show message in results container
     */
    function showMessage( type, message, $container ) {
        const className = type === 'success' ? 'success' : 'error';
        const icon = type === 'success' ? '✅' : '❌';

        if ( $container.find( '.message' ).length ) {
            $container.find( '.message' )
                .removeClass( 'success error' )
                .addClass( className )
                .html( icon + ' ' + message );
        } else {
            $container.html( '<p class="message ' + className + '">' + icon + ' ' + message + '</p>' );
        }
        $container.show();
    }

    /**
     * Hide message container
     */
    function hideMessage( $container ) {
        $container.hide();
    }

} )( jQuery );