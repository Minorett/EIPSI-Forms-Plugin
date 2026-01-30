/**
 * EIPSI Forms - Waves Manager Modal
 * Handles Close & Anonymize Study modal logic
 */

/* global eipsiWavesManagerData, ajaxurl */

( function ( $ ) {
	'use strict';

	// ===========================
	// STATE
	// ===========================

	let currentStep = 1;
	const totalSteps = 3;
	let modalNonce = '';
	let surveyId = 0;

	// ===========================
	// INITIALIZATION
	// ===========================

	$( document ).ready( function () {
		initAnonymizeModal();
	} );

	function initAnonymizeModal() {
		// Get nonce from localized data (WordPress global)
		modalNonce =
			typeof eipsiWavesManagerData !== 'undefined'
				? eipsiWavesManagerData.anonymizeNonce
				: '';

		// Event: Open modal
		$( document ).on( 'click', '#eipsi-open-anonymize-modal', function () {
			surveyId = $( this ).data( 'survey-id' );
			currentStep = 1;
			resetModal();
			$( '#eipsi-anonymize-modal' ).fadeIn( 200 );
			updateModalView();
		} );

		// Event: Close modal (X button)
		$( document ).on( 'click', '#eipsi-close-modal', function () {
			closeModal();
		} );

		// Event: Close modal (Cancel button)
		$( document ).on( 'click', '#eipsi-modal-cancel', function () {
			closeModal();
		} );

		// Event: Close modal on overlay click
		$( document ).on(
			'click',
			'#eipsi-anonymize-modal .eipsi-modal-overlay',
			function () {
				closeModal();
			}
		);

		// Event: Next button
		$( document ).on( 'click', '#eipsi-modal-next', function () {
			if ( validateStep( currentStep ) ) {
				if ( currentStep === totalSteps ) {
					submitAnonymization();
				} else {
					currentStep++;
					updateModalView();
				}
			}
		} );

		// Event: Previous button
		$( document ).on( 'click', '#eipsi-modal-prev', function () {
			if ( currentStep > 1 ) {
				currentStep--;
				updateModalView();
			}
		} );

		// Event: Real-time validation for step 3 (text input)
		$( document ).on( 'keyup', '#eipsi-confirm-text', function () {
			validateStep3();
		} );
	}

	// ===========================
	// MODAL VIEW UPDATE
	// ===========================

	function updateModalView() {
		// Hide all steps
		$( '.eipsi-modal-step' ).hide();

		// Show current step
		$( '#step-' + currentStep ).show();

		// Update title
		const title =
			'Cerrar & Anonimizar Estudio - Paso ' +
			currentStep +
			'/' +
			totalSteps;
		$( '#eipsi-modal-title' ).text( title );

		// Update "Next" button
		const nextText =
			currentStep === totalSteps ? '✅ Anonimizar Ahora' : 'Siguiente →';
		const nextBtn = $( '#eipsi-modal-next' );
		nextBtn.text( nextText );

		// Disable next button on step 3 if validation fails
		if ( currentStep === totalSteps ) {
			nextBtn.prop( 'disabled', ! isStep3Valid() );
			validateStep3(); // Show feedback
		} else {
			nextBtn.prop( 'disabled', false );
		}

		// Toggle previous button visibility
		$( '#eipsi-modal-prev' ).toggle( currentStep > 1 );
	}

	// ===========================
	// VALIDATIONS
	// ===========================

	function validateStep( step ) {
		if ( step === 1 ) {
			return validateStep1();
		} else if ( step === 2 ) {
			return validateStep2();
		} else if ( step === 3 ) {
			return isStep3Valid();
		}
		return false;
	}

	function validateStep1() {
		// All 6 checkboxes must be checked
		const allChecked =
			$( '#eipsi-confirm-1' ).is( ':checked' ) &&
			$( '#eipsi-confirm-2' ).is( ':checked' ) &&
			$( '#eipsi-confirm-3' ).is( ':checked' ) &&
			$( '#eipsi-confirm-4' ).is( ':checked' ) &&
			$( '#eipsi-confirm-5' ).is( ':checked' ) &&
			$( '#eipsi-confirm-6' ).is( ':checked' );

		if ( ! allChecked ) {
			showErrorMessage(
				'Por favor, confirma que entiendes todas las consecuencias de anonimizar.'
			);
			return false;
		}
		return true;
	}

	function validateStep2() {
		// Close reason is required
		const reason = $( '#eipsi-close-reason' ).val();
		if ( ! reason || reason.trim() === '' ) {
			showErrorMessage(
				'Por favor, selecciona una razón para cerrar el estudio.'
			);
			return false;
		}
		return true;
	}

	function validateStep3() {
		const confirmText = $( '#eipsi-confirm-text' )
			.val()
			.toUpperCase()
			.trim();
		const isValid = confirmText === 'ANONIMIZAR';

		// Update button state
		$( '#eipsi-modal-next' ).prop( 'disabled', ! isValid );

		// Show feedback message
		const msgDiv = $( '#eipsi-step3-message' );
		if ( confirmText.length > 0 && ! isValid ) {
			msgDiv
				.show()
				.attr(
					'style',
					'background: #ffe6e6; color: #d63031; display: block;'
				)
				.text(
					'❌ El texto no coincide. Escribe exactamente: ANONIMIZAR'
				);
		} else if ( isValid ) {
			msgDiv
				.show()
				.attr(
					'style',
					'background: #e6ffe6; color: #27ae60; display: block;'
				)
				.text(
					'✅ Correcto. Haz clic en "Anonimizar Ahora" para confirmar.'
				);
		} else {
			msgDiv.hide();
		}
	}

	function isStep3Valid() {
		const confirmText = $( '#eipsi-confirm-text' )
			.val()
			.toUpperCase()
			.trim();
		return confirmText === 'ANONIMIZAR';
	}

	// ===========================
	// SUBMISSION
	// ===========================

	function submitAnonymization() {
		const button = $( '#eipsi-modal-next' );
		button.prop( 'disabled', true );
		button.text( '⏳ Procesando...' );

		const closeReason = $( '#eipsi-close-reason' ).val();
		const closeNotes = $( '#eipsi-close-notes' ).val();

		// Make AJAX request
		$.ajax( {
			url:
				typeof ajaxurl !== 'undefined'
					? ajaxurl
					: '/wp-admin/admin-ajax.php',
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'eipsi_anonymize_survey',
				survey_id: surveyId,
				nonce: modalNonce,
				close_reason: closeReason,
				close_notes: closeNotes,
			},
			success( response ) {
				if ( response.success ) {
					showSuccess( response.data );
				} else {
					showErrorMessage(
						response.data.message || 'Error desconocido'
					);
					button.prop( 'disabled', false );
					button.text( '✅ Anonimizar Ahora' );
				}
			},
			error( xhr, status, error ) {
				showErrorMessage( 'Error en la solicitud: ' + error );
				button.prop( 'disabled', false );
				button.text( '✅ Anonimizar Ahora' );
			},
		} );
	}

	function showSuccess( data ) {
		// Hide all steps
		$( '.eipsi-modal-step' ).hide();
		$( '#step-success' ).show();

		// Update title
		$( '#eipsi-modal-title' ).text( '✅ Anonimización Completada' );

		// Update success message
		const successMsg =
			'<strong>' +
			escapeHtml( data.survey_title ) +
			'</strong><br>' +
			data.anonymized_count +
			' participante(s) anonimizado(s) exitosamente';
		$( '#eipsi-success-message' ).html( successMsg );

		// Show details
		$( '#eipsi-success-details' ).html(
			'<strong>Acciones realizadas:</strong><br>' +
				'✅ Emails eliminados<br>' +
				'✅ Contraseñas eliminadas<br>' +
				'✅ Nombres eliminados<br>' +
				'✅ Magic links invalidados<br>' +
				'✅ Audit log registrado<br><br>' +
				'<strong>Próximos pasos:</strong><br>' +
				'1. La página se recargará en 3 segundos<br>' +
				'2. Descarga un backup de la encuesta si es necesario<br>' +
				'3. Notifica a los participantes si aplica'
		);

		// Update footer (replace buttons)
		$( '.eipsi-modal-footer' ).html(
			'<button type="button" class="button button-primary" id="eipsi-modal-reload">Recargar Página</button>'
		);

		// Event: Reload button
		$( document ).on( 'click', '#eipsi-modal-reload', function () {
			window.location.reload();
		} );

		// Auto-reload after 3 seconds
		setTimeout( function () {
			window.location.reload();
		}, 3000 );
	}

	function showErrorMessage( message ) {
		// Create a custom modal for errors instead of using alert
		const errorModal = $(
			'<div class="notice notice-error" style="padding: 10px; margin: 10px 0; border-left: 4px solid #dc3232;"><p>❌ Error: ' +
				escapeHtml( message ) +
				'</p></div>'
		);
		$( '.eipsi-modal-content' ).prepend( errorModal );

		// Auto-remove after 5 seconds
		setTimeout( function () {
			errorModal.fadeOut( 500, function () {
				errorModal.remove();
			} );
		}, 5000 );
	}

	// ===========================
	// MODAL MANAGEMENT
	// ===========================

	function resetModal() {
		// Reset all inputs
		$(
			'#eipsi-confirm-1, #eipsi-confirm-2, #eipsi-confirm-3, #eipsi-confirm-4, #eipsi-confirm-5, #eipsi-confirm-6'
		).prop( 'checked', false );
		$( '#eipsi-close-reason' ).val( '' );
		$( '#eipsi-close-notes' ).val( '' );
		$( '#eipsi-confirm-text' ).val( '' );
		$( '#eipsi-step3-message' ).hide();

		// Reset modal view
		currentStep = 1;
		$( '.eipsi-modal-step' ).show();
		$( '#step-success' ).hide();
		$( '.eipsi-modal-footer' ).show();

		// Reset buttons
		$( '#eipsi-modal-next' )
			.text( 'Siguiente →' )
			.prop( 'disabled', false );
		$( '#eipsi-modal-prev' ).show();

		// Remove any error messages
		$( '.notice-error' ).remove();
	}

	function closeModal() {
		resetModal();
		$( '#eipsi-anonymize-modal' ).fadeOut( 200 );
	}

	// ===========================
	// UTILITIES
	// ===========================

	/**
	 * Escape HTML to prevent XSS
	 * @param {string} unsafe Unsafe string
	 * @return {string} Escaped string
	 */
	function escapeHtml( unsafe ) {
		return unsafe
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			.replace( /"/g, '&quot;' )
			.replace( /'/g, '&#039;' );
	}
} )( window.jQuery );
