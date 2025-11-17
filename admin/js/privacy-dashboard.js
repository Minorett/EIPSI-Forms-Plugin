/**
 * EIPSI Forms - Privacy Dashboard JavaScript
 * Handles AJAX form submission for privacy configuration
 *
 * @param {Object} $ jQuery object
 */

/* global ajaxurl, jQuery */

( function ( $ ) {
	'use strict';

	$( document ).ready( function () {
		const $form = $( '#eipsi-privacy-form' );

		if ( ! $form.length ) {
			return;
		}

		$form.on( 'submit', function ( e ) {
			e.preventDefault();

			const formData = new FormData( this );
			const $submitButton = $form.find( 'button[type="submit"]' );
			const originalText = $submitButton.text();

			// Disable button and show loading state
			$submitButton.prop( 'disabled', true ).text( '💾 Guardando...' );

			// Remove any existing messages
			$( '.eipsi-message' ).remove();

			$.ajax( {
				url: ajaxurl,
				type: 'POST',
				data: formData,
				processData: false,
				contentType: false,
				success( response ) {
					if ( response.success ) {
						showMessage( 'success', response.data.message );
					} else {
						showMessage(
							'error',
							response.data.message ||
								'Error al guardar la configuración.'
						);
					}
				},
				error() {
					showMessage(
						'error',
						'Error al guardar la configuración. Por favor, inténtelo de nuevo.'
					);
				},
				complete() {
					$submitButton
						.prop( 'disabled', false )
						.text( originalText );
				},
			} );
		} );

		function showMessage( type, message ) {
			const $message = $( '<div>' )
				.addClass( 'eipsi-message notice is-dismissible' )
				.addClass(
					type === 'success' ? 'notice-success' : 'notice-error'
				)
				.html( '<p>' + message + '</p>' );

			$form.before( $message );

			// Auto-dismiss after 3 seconds
			setTimeout( function () {
				$message.fadeOut( function () {
					$( this ).remove();
				} );
			}, 3000 );
		}
	} );
} )( jQuery );
