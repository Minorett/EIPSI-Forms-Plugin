/**
 * EIPSI Forms - Privacy Dashboard JavaScript
 * Smart Save Button: Detecta cambios y habilita/deshabilita botones
 *
 * @param {Object} $ jQuery object
 */

/* global ajaxurl, jQuery */

( function ( $ ) {
	'use strict';

	$( document ).ready( function () {
		initSmartSaveButtons();
	} );

	/**
	 * Inicializa la detección de cambios en ambos formularios
	 */
	function initSmartSaveButtons() {
		// Global Privacy Form
		const $globalForm = $( '#eipsi-global-privacy-form' );
		if ( $globalForm.length ) {
			setupFormChangeDetection( $globalForm, 'global' );
		}

		// Per-Form Privacy Config
		const $perFormForm = $( '#eipsi-privacy-form' );
		if ( $perFormForm.length ) {
			setupFormChangeDetection( $perFormForm, 'form' );
		}
	}

	/**
	 * Configura la detección de cambios para un formulario específico
	 *
	 * @param {jQuery} $form    El formulario a monitorear
	 * @param {string} formType 'global' o 'form'
	 */
	function setupFormChangeDetection( $form, formType ) {
		const $submitButton = $form.find( 'button[type="submit"]' );
		if ( ! $submitButton.length ) {
			return;
		}

		// Capturar estado inicial del formulario
		const initialState = captureFormState( $form );

		// Inicializar botón desactivado
		disableSaveButton( $submitButton );

		// Escuchar cambios en todos los checkboxes
		const $inputs = $form.find( 'input[type="checkbox"]' );
		$inputs.on( 'change', function () {
			const currentState = captureFormState( $form );
			const hasChanges = statesAreDifferent( initialState, currentState );

			if ( hasChanges ) {
				enableSaveButton( $submitButton );
			} else {
				disableSaveButton( $submitButton );
			}
		} );

		// Manejar envío del formulario
		$form.on( 'submit', function ( e ) {
			e.preventDefault();

			// Si no hay cambios, no enviar
			const currentState = captureFormState( $form );
			if ( ! statesAreDifferent( initialState, currentState ) ) {
				showMessage( $form, 'info', 'No hay cambios para guardar.' );
				return;
			}

			const originalText = $submitButton.text();

			// Desactivar botón y mostrar estado de carga
			$submitButton.prop( 'disabled', true ).text( '💾 Guardando...' );

			// Remover mensajes existentes
			$( '.eipsi-message' ).remove();

			// Determinar la acción AJAX correcta
			const action =
				formType === 'global'
					? 'eipsi_save_global_privacy_config'
					: 'eipsi_save_privacy_config';

			// Enviar datos por AJAX
			$.ajax( {
				url: ajaxurl,
				type: 'POST',
				data: $form.serialize() + '&action=' + action,
				success( response ) {
					if ( response.success ) {
						// Actualizar estado inicial al estado actual
						Object.assign( initialState, currentState );

						// Desactivar botón nuevamente
						disableSaveButton( $submitButton );

						// Mostrar mensaje de éxito
						showMessage(
							$form,
							'success',
							response.data.message ||
								'✓ Configuración guardada correctamente'
						);
					} else {
						// Habilitar botón para reintentar
						enableSaveButton( $submitButton );

						// Mostrar mensaje de error
						showMessage(
							$form,
							'error',
							response.data.message ||
								'Error al guardar la configuración.'
						);
					}
				},
				error() {
					// Habilitar botón para reintentar
					enableSaveButton( $submitButton );

					// Mostrar mensaje de error
					showMessage(
						$form,
						'error',
						'Error al guardar la configuración. Por favor, inténtelo de nuevo.'
					);
				},
				complete() {
					// Restaurar texto original del botón
					$submitButton.text( originalText );
				},
			} );
		} );
	}

	/**
	 * Captura el estado actual de los checkboxes de un formulario
	 *
	 * @param {jQuery} $form El formulario
	 * @return {Object} Estado actual de los checkboxes
	 */
	function captureFormState( $form ) {
		const state = {};
		const $inputs = $form.find( 'input[type="checkbox"]' );

		$inputs.each( function () {
			state[ this.name ] = this.checked;
		} );

		return state;
	}

	/**
	 * Compara dos estados para detectar diferencias
	 *
	 * @param {Object} state1 Primer estado
	 * @param {Object} state2 Segundo estado
	 * @return {boolean} true si hay diferencias, false si son iguales
	 */
	function statesAreDifferent( state1, state2 ) {
		// Obtener todas las claves de ambos estados
		const keys = Object.keys( state1 ).concat( Object.keys( state2 ) );
		const uniqueKeys = [ ...new Set( keys ) ];

		// Comparar cada clave
		for ( const key of uniqueKeys ) {
			if ( state1[ key ] !== state2[ key ] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Desactiva el botón de guardar
	 *
	 * @param {jQuery} $button El botón a desactivar
	 */
	function disableSaveButton( $button ) {
		$button
			.prop( 'disabled', true )
			.addClass( 'is-disabled' )
			.attr( 'title', 'No hay cambios para guardar' );
	}

	/**
	 * Activa el botón de guardar
	 *
	 * @param {jQuery} $button El botón a activar
	 */
	function enableSaveButton( $button ) {
		$button
			.prop( 'disabled', false )
			.removeClass( 'is-disabled' )
			.attr( 'title', 'Guardar cambios' );
	}

	/**
	 * Muestra un mensaje de éxito, error o información
	 *
	 * @param {jQuery} $form   El formulario relacionado
	 * @param {string} type    Tipo de mensaje: 'success', 'error', 'info'
	 * @param {string} message El mensaje a mostrar
	 */
	function showMessage( $form, type, message ) {
		// Eliminar mensajes existentes
		$( '.eipsi-message' ).remove();

		let noticeClass;
		if ( type === 'success' ) {
			noticeClass = 'notice-success';
		} else if ( type === 'error' ) {
			noticeClass = 'notice-error';
		} else {
			noticeClass = 'notice-info';
		}

		const $message = $( '<div>' )
			.addClass( 'eipsi-message notice is-dismissible' )
			.addClass( noticeClass )
			.html( '<p>' + message + '</p>' );

		$form.before( $message );

		// Auto-ocultar después de 3 segundos
		setTimeout( function () {
			$message.fadeOut( function () {
				$( this ).remove();
			} );
		}, 3000 );
	}
} )( jQuery );
