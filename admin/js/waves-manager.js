/**
 * EIPSI Forms - Waves Manager
 * Handles Waves CRUD, Assignments, Reminders, and Close & Anonymize Study modal
 *
 * @package EIPSI_Forms
 * @since 1.4.3
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
	let currentWaveId = 0;
	let currentWaveData = null;

	// ===========================
	// INITIALIZATION
	// ===========================

	$( document ).ready( function () {
		initAnonymizeModal();
		initWavesManager();
	} );

	// ===========================
	// WAVES MANAGER FUNCTIONALITY
	// ===========================

	function initWavesManager() {
		// Open Create Wave Modal
		$( document ).on( 'click', '#eipsi-create-wave-btn', function () {
			resetWaveForm();
			const nextIndex = $( this ).data( 'next-index' );
			$( '#wave_index' ).val( nextIndex );
			$( '#wave-modal-title' ).text( 'Crear Nueva Onda' );
			$( '#wave_id' ).val( '' );
			$( '#eipsi-wave-modal' ).fadeIn( 200 );
		} );

		// Open Edit Wave Modal
		$( document ).on( 'click', '.eipsi-edit-wave-btn', function () {
			const waveId = $( this ).data( 'wave-id' );
			currentWaveId = waveId;
			loadWaveData( waveId );
		} );

		// Close Modal
		$( document ).on(
			'click',
			'.eipsi-close-modal, .eipsi-close-modal-btn',
			function () {
				$( '.eipsi-modal' ).fadeOut( 200 );
			}
		);

		// Close on overlay click
		$( document ).on( 'click', '.eipsi-modal', function ( e ) {
			if ( e.target === this ) {
				$( this ).fadeOut( 200 );
			}
		} );

		// Save Wave Form
		$( document ).on( 'submit', '#eipsi-wave-form', function ( e ) {
			e.preventDefault();
			saveWave();
		} );

		// Delete Wave
		$( document ).on( 'click', '.eipsi-delete-wave-btn', function () {
			const waveId = $( this ).data( 'wave-id' );
			if (
				confirm(
					eipsiWavesManagerData.strings.confirmDelete ||
						'¿Estás seguro de eliminar esta onda?'
				)
			) {
				deleteWave( waveId );
			}
		} );

		// Open Assign Participants Modal
		$( document ).on( 'click', '.eipsi-assign-participants-btn', function () {
			const waveId = $( this ).data( 'wave-id' );
			currentWaveId = waveId;
			$( '#assign-wave-id' ).val( waveId );
			loadAvailableParticipants( waveId );
			$( '#eipsi-assign-modal' ).fadeIn( 200 );
		} );

		// Master checkbox for select all
		$( document ).on( 'change', '#master-participant-check', function () {
			$( '.participant-checkbox' ).prop( 'checked', $( this ).is( ':checked' ) );
		} );

		// Select/Deselect all buttons
		$( document ).on( 'click', '#select-all-participants', function () {
			$( '.participant-checkbox' ).prop( 'checked', true );
		} );

		$( document ).on( 'click', '#deselect-all-participants', function () {
			$( '.participant-checkbox' ).prop( 'checked', false );
		} );

		// Confirm Assign Participants
		$( document ).on( 'click', '#confirm-assign-btn', function () {
			const selectedIds = [];
			$( '.participant-checkbox:checked' ).each( function () {
				selectedIds.push( $( this ).val() );
			} );

			if ( selectedIds.length === 0 ) {
				alert(
					eipsiWavesManagerData.strings.selectParticipants ||
						'Por favor selecciona al menos un participante.'
				);
				return;
			}

			assignParticipants( currentWaveId, selectedIds );
		} );

		// Extend Deadline
		$( document ).on( 'click', '.eipsi-extend-deadline-btn', function () {
			const waveId = $( this ).data( 'wave-id' );
			currentWaveId = waveId;
			const newDeadline = prompt(
				'Ingresa la nueva fecha de vencimiento (YYYY-MM-DD HH:MM):',
				new Date().toISOString().slice( 0, 16 ).replace( 'T', ' ' )
			);
			if ( newDeadline ) {
				extendDeadline( waveId, newDeadline );
			}
		} );

		// Send Reminders
		$( document ).on( 'click', '.eipsi-send-reminder-btn', function () {
			const waveId = $( this ).data( 'wave-id' );
			if (
				confirm(
					'¿Enviar recordatorios a todos los participantes pendientes de esta onda?'
				)
			) {
				sendReminders( waveId );
			}
		} );
	}

	// ===========================
	// WAVE CRUD OPERATIONS
	// ===========================

	function loadWaveData( waveId ) {
		const $btn = $( '.eipsi-edit-wave-btn[data-wave-id="' + waveId + '"]');
		const originalText = $btn.text();
		$btn.text( 'Cargando...' ).prop( 'disabled', true );

		$.ajax( {
			url:
				eipsiWavesManagerData.ajaxUrl ||
				typeof ajaxurl !== 'undefined' ?
					ajaxurl :
					'/wp-admin/admin-ajax.php',
			type: 'GET',
			data: {
				action: 'eipsi_get_wave',
				wave_id: waveId,
				nonce: eipsiWavesManagerData.wavesNonce,
			},
			success( response ) {
				if ( response.success && response.data ) {
					populateWaveForm( response.data );
					$( '#wave-modal-title' ).text( 'Editar Onda' );
					$( '#eipsi-wave-modal' ).fadeIn( 200 );
				} else {
					alert(
						response.data ||
							'Error al cargar los datos de la onda'
					);
				}
			},
			error() {
				alert( 'Error de conexión al cargar los datos' );
			},
			complete() {
				$btn.text( originalText ).prop( 'disabled', false );
			},
		} );
	}

	function populateWaveForm( waveData ) {
		$( '#wave_id' ).val( waveData.id );
		$( '#wave_name' ).val( waveData.name );
		$( '#wave_index' ).val( waveData.wave_index );
		$( '#form_id' ).val( waveData.form_id );
		$( '#due_date' ).val( waveData.due_date ? waveData.due_date.slice( 0, 16 ) : '' );
		$( '#wave_description' ).val( waveData.description || '' );
		$( 'input[name="is_mandatory"]' ).prop( 'checked', parseInt( waveData.is_mandatory ) === 1 );
	}

	function resetWaveForm() {
		$( '#eipsi-wave-form' )[ 0 ].reset();
		$( '#wave_id' ).val( '' );
		$( '#wave_index' ).val( 1 );
		$( 'input[name="is_mandatory"]' ).prop( 'checked', true );
	}

	function saveWave() {
		const $btn = $( '#save-wave-btn' );
		const originalText = $btn.text();
		$btn
			.text( eipsiWavesManagerData.strings.saving || 'Guardando...' )
			.prop( 'disabled', true );

		const formData = $( '#eipsi-wave-form' ).serialize();

		$.ajax( {
			url:
				eipsiWavesManagerData.ajaxUrl ||
				( typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php' ),
			type: 'POST',
			data: {
				action: 'eipsi_save_wave',
				nonce: eipsiWavesManagerData.wavesNonce,
				wave_id: $( '#wave_id' ).val(),
				study_id: $( 'input[name="study_id"]' ).val(),
				name: $( '#wave_name' ).val(),
				wave_index: $( '#wave_index' ).val(),
				form_id: $( '#form_id' ).val(),
				due_date: $( '#due_date' ).val(),
				description: $( '#wave_description' ).val(),
				is_mandatory: $( 'input[name="is_mandatory"]:checked' ).val() || 0,
			},
			success( response ) {
				if ( response.success ) {
					showNotification(
						eipsiWavesManagerData.strings.waveSaved ||
							'Onda guardada exitosamente',
						'success'
					);
					$( '#eipsi-wave-modal' ).fadeOut( 200 );
					// Reload page after short delay
					setTimeout( function () {
						window.location.reload();
					}, 500 );
				} else {
					showNotification(
						response.data || 'Error al guardar la onda',
						'error'
					);
				}
			},
			error() {
				showNotification( 'Error de conexión', 'error' );
			},
			complete() {
				$btn.text( originalText ).prop( 'disabled', false );
			},
		} );
	}

	function deleteWave( waveId ) {
		const $btn = $( '.eipsi-delete-wave-btn[data-wave-id="' + waveId + '"]');
		const originalText = $btn.text();
		$btn.text( 'Eliminando...' ).prop( 'disabled', true );

		$.ajax( {
			url:
				eipsiWavesManagerData.ajaxUrl ||
				( typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php' ),
			type: 'POST',
			data: {
				action: 'eipsi_delete_wave',
				nonce: eipsiWavesManagerData.wavesNonce,
				wave_id: waveId,
			},
			success( response ) {
				if ( response.success ) {
					showNotification(
						eipsiWavesManagerData.strings.waveDeleted ||
							'Onda eliminada',
						'success'
					);
					// Reload page after short delay
					setTimeout( function () {
						window.location.reload();
					}, 500 );
				} else {
					showNotification(
						response.data || 'Error al eliminar la onda',
						'error'
					);
					$btn.text( originalText ).prop( 'disabled', false );
				}
			},
			error() {
				showNotification( 'Error de conexión', 'error' );
				$btn.text( originalText ).prop( 'disabled', false );
			},
		} );
	}

	// ===========================
	// PARTICIPANT ASSIGNMENT
	// ===========================

	function loadAvailableParticipants( waveId ) {
		const $tbody = $( '#available-participants-tbody' );
		$tbody.html(
			'<tr><td colspan="4" style="text-align:center;padding:20px;"><span class="spinner is-active"></span> Cargando participantes...</td></tr>'
		);

		$.ajax( {
			url:
				eipsiWavesManagerData.ajaxUrl ||
				( typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php' ),
			type: 'GET',
			data: {
				action: 'eipsi_get_available_participants',
				nonce: eipsiWavesManagerData.wavesNonce,
				study_id: eipsiWavesManagerData.studyId,
				wave_id: waveId,
			},
			success( response ) {
				if ( response.success && response.data ) {
					renderParticipantsList( response.data );
				} else {
					$tbody.html(
						'<tr><td colspan="4" style="text-align:center;padding:20px;color:#666;">' +
							( eipsiWavesManagerData.strings.noParticipants ||
								'No hay participantes disponibles' ) +
							'</td></tr>'
					);
				}
			},
			error() {
				$tbody.html(
					'<tr><td colspan="4" style="text-align:center;padding:20px;color:#d63638;">Error al cargar participantes</td></tr>'
				);
			},
		} );
	}

	function renderParticipantsList( participants ) {
		const $tbody = $( '#available-participants-tbody' );

		if ( participants.length === 0 ) {
			$tbody.html(
				'<tr><td colspan="4" style="text-align:center;padding:20px;color:#666;">' +
					( eipsiWavesManagerData.strings.noParticipants ||
						'No hay participantes disponibles' ) +
					'</td></tr>'
			);
			return;
		}

		let html = '';
		participants.forEach( function ( p ) {
			html +=
				'<tr>' +
				'<td class="check-column"><input type="checkbox" class="participant-checkbox" value="' +
				p.id +
				'"></td>' +
				'<td>' +
					escapeHtml( p.full_name || p.first_name + ' ' + p.last_name ) +
					'</td>' +
				'<td>' +
					escapeHtml( p.email ) +
					'</td>' +
				'<td><code>' +
					escapeHtml( p.participant_id ) +
					'</code></td>' +
				'</tr>';
		} );
		$tbody.html( html );
	}

	function assignParticipants( waveId, participantIds ) {
		const $btn = $( '#confirm-assign-btn' );
		const originalText = $btn.text();
		$btn.text( 'Asignando...' ).prop( 'disabled', true );

		$.ajax( {
			url:
				eipsiWavesManagerData.ajaxUrl ||
				( typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php' ),
			type: 'POST',
			data: {
				action: 'eipsi_assign_participants',
				nonce: eipsiWavesManagerData.wavesNonce,
				study_id: eipsiWavesManagerData.studyId,
				wave_id: waveId,
				participant_ids: participantIds,
			},
			success( response ) {
				if ( response.success ) {
					const msg =
						response.data.assigned_count +
						' ' +
						( eipsiWavesManagerData.strings.participantsAssigned ||
							'participantes asignados' );
					showNotification( msg, 'success' );
					$( '#eipsi-assign-modal' ).fadeOut( 200 );
					// Reload page after short delay
					setTimeout( function () {
						window.location.reload();
					}, 500 );
				} else {
					showNotification(
						response.data || 'Error al asignar participantes',
						'error'
					);
				}
			},
			error() {
				showNotification( 'Error de conexión', 'error' );
			},
			complete() {
				$btn.text( originalText ).prop( 'disabled', false );
			},
		} );
	}

	// ===========================
	// DEADLINE EXTENSION
	// ===========================

	function extendDeadline( waveId, newDeadline ) {
		const $btn = $( '.eipsi-extend-deadline-btn[data-wave-id="' + waveId + '"]');
		const originalText = $btn.text();
		$btn.text( 'Extendiendo...' ).prop( 'disabled', true );

		$.ajax( {
			url:
				eipsiWavesManagerData.ajaxUrl ||
				( typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php' ),
			type: 'POST',
			data: {
				action: 'eipsi_extend_deadline',
				nonce: eipsiWavesManagerData.wavesNonce,
				wave_id: waveId,
				new_deadline: newDeadline,
			},
			success( response ) {
				if ( response.success ) {
					showNotification(
						eipsiWavesManagerData.strings.deadlineExtended ||
							'Plazo extendido',
						'success'
					);
					// Reload page after short delay
					setTimeout( function () {
						window.location.reload();
					}, 500 );
				} else {
					showNotification(
						response.data || 'Error al extender plazo',
						'error'
					);
				}
			},
			error() {
				showNotification( 'Error de conexión', 'error' );
			},
			complete() {
				$btn.text( originalText ).prop( 'disabled', false );
			},
		} );
	}

	// ===========================
	// SEND REMINDERS
	// ===========================

	function sendReminders( waveId ) {
		const $btn = $( '.eipsi-send-reminder-btn[data-wave-id="' + waveId + '"]');
		const originalText = $btn.text();
		$btn
			.text( eipsiWavesManagerData.strings.sending || 'Enviando...' )
			.prop( 'disabled', true );

		$.ajax( {
			url:
				eipsiWavesManagerData.ajaxUrl ||
				( typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php' ),
			type: 'POST',
			data: {
				action: 'eipsi_send_reminder',
				nonce: eipsiWavesManagerData.wavesNonce,
				wave_id: waveId,
			},
			success( response ) {
				if ( response.success ) {
					const msg =
						response.data.sent +
						' ' +
						( eipsiWavesManagerData.strings.remindersSent ||
							'recordatorios enviados' );
					showNotification( msg, 'success' );
				} else {
					showNotification(
						response.data || 'Error al enviar recordatorios',
						'error'
					);
				}
			},
			error() {
				showNotification( 'Error de conexión', 'error' );
			},
			complete() {
				$btn.text( originalText ).prop( 'disabled', false );
			},
		} );
	}

	// ===========================
	// NOTIFICATION HELPER
	// ===========================

	function showNotification( message, type ) {
		// Remove existing notifications
		$( '.eipsi-notification' ).remove();

		const cssClass = type === 'success' ? 'notice-success' : 'notice-error';
		const icon = type === 'success' ? '✓' : '✗';

		const $notification = $(
			'<div class="eipsi-notification ' +
				cssClass +
				'" style="position:fixed;top:50px;right:20px;z-index:99999;padding:12px 20px;border-radius:4px;box-shadow:0 2px 8px rgba(0,0,0,0.15);">' +
				'<strong>' +
				icon +
				'</strong> ' +
				message +
				'</div>'
		);

		$( 'body' ).append( $notification );

		// Auto-remove after 4 seconds
		setTimeout( function () {
			$notification.fadeOut( function () {
				$( this ).remove();
			} );
		}, 4000 );
	}

	// ===========================
	// ANONYMIZE MODAL (EXISTING)
	// ===========================

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
	// ANONYMIZE MODAL VIEW UPDATE
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
	// ANONYMIZE VALIDATIONS
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
	// ANONYMIZE SUBMISSION
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
	// ANONYMIZE MODAL MANAGEMENT
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
		if ( ! unsafe ) {
			return '';
		}
		return unsafe
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			.replace( /"/g, '&quot;' )
			.replace( /'/g, '&#039;' );
	}
} )( window.jQuery );
