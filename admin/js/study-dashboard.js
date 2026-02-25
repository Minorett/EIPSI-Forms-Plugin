/**
 * EIPSI Forms - Study Dashboard
 * Handles Study Overview Modal, Wave Details, and Email Logs
 *
 * @param {jQuery} $ - jQuery instance.
 * @package
 * @since 1.4.3
 */

( function ( $ ) {
	'use strict';

	// ===========================
	// STATE
	// ===========================

	let currentStudyId = 0;
	let isLoading = false;
	let currentPage = 1;
	const participantsPerPage = 20;
	let currentMagicLinkParticipantId = 0;
	let currentMagicLinkParticipantEmail = '';

	// ===========================
	// INITIALIZATION
	// ===========================

	$( document ).ready( function () {
		initStudyDashboard();
	} );

	function initStudyDashboard() {
		// Open Dashboard Modal
		$( document ).on( 'click', '.eipsi-view-study', function () {
			const studyId = $( this ).data( 'study-id' );
			if ( ! studyId ) {
				return;
			}

			currentStudyId = studyId;
			openDashboardModal();
			loadStudyOverview( studyId );
		} );

		// Close Modal
		$( document ).on(
			'click',
			'.eipsi-modal-close, #eipsi-study-dashboard-modal .eipsi-modal-close',
			function () {
				closeDashboardModal();
			}
		);

		// Close on overlay click
		$( document ).on( 'click', '.eipsi-modal', function ( e ) {
			if ( e.target === this ) {
				$( this ).fadeOut( 200 );
			}
		} );

		// Refresh Dashboard
		$( document ).on( 'click', '#refresh-dashboard', function () {
			if ( currentStudyId && ! isLoading ) {
				loadStudyOverview( currentStudyId );
			}
		} );

		// Copy Study Shortcode
		$( document ).on( 'click', '#copy-study-shortcode', function () {
			copyStudyShortcode();
		} );

		// Magic Links
		$( document ).on( 'click', '#generate-magic-link', function () {
			generateMagicLink();
		} );

		$( document ).on( 'submit', '#magic-link-form', function ( e ) {
			e.preventDefault();
			sendMagicLink();
		} );

		$( document ).on( 'click', '#copy-magic-link', function () {
			copyMagicLink();
		} );

		$( document ).on( 'input', '#magic-link-email', function () {
			clearMagicLinkMessages();
		} );

		// View Email Logs
		$( document ).on( 'click', '#view-email-logs', function () {
			if ( currentStudyId ) {
				loadEmailLogs( currentStudyId );
				$( '#eipsi-email-logs-modal' ).fadeIn( 200 );
			}
		} );

		// Close Email Logs Modal
		$( document ).on(
			'click',
			'#eipsi-email-logs-modal .eipsi-modal-close',
			function () {
				$( '#eipsi-email-logs-modal' ).fadeOut( 200 );
			}
		);

		// Close Extend Deadline Modal
		$( document ).on(
			'click',
			'#eipsi-extend-deadline-modal .eipsi-modal-close',
			function () {
				$( '#eipsi-extend-deadline-modal' ).fadeOut( 200 );
			}
		);

		// Extend Deadline Form Submit
		$( document ).on( 'submit', '#extend-deadline-form', function ( e ) {
			e.preventDefault();
			const waveId = $( '#extend-wave-id' ).val();
			const newDeadline = $( '#new-deadline-date' ).val();
			if ( waveId && newDeadline ) {
				extendWaveDeadline( waveId, newDeadline );
			}
		} );

		// Close Participants Modal
		$( document ).on(
			'click',
			'#eipsi-participants-list-modal .eipsi-modal-close',
			function () {
				$( '#eipsi-participants-list-modal' ).fadeOut( 200 );
			}
		);

		// Filter Participants
		$( document ).on( 'change', '#participant-status-filter', function () {
			loadParticipantsList( 1 );
		} );

		$( document ).on( 'input', '#participant-search', function () {
			// Debounce search
			clearTimeout( window.participantSearchTimeout );
			window.participantSearchTimeout = setTimeout( function () {
				loadParticipantsList( 1 );
			}, 500 );
		} );

		// Toggle Participant Status
		$( document ).on( 'click', '.toggle-participant-status', function () {
			const participantId = $( this ).data( 'participant-id' );
			const currentStatus = $( this ).data( 'is-active' );
			const newStatus = ! currentStatus;
			const participantEmail = $( this ).data( 'participant-email' );

			if ( ! newStatus ) {
				// Desactivar - requiere confirmación
				showConfirmationDialog( {
					title: 'Desactivar participante',
					message:
						'¿Estás seguro de desactivar a este participante?\n\n' +
						'• No recibirá más emails de recordatorio\n' +
						'• No podrá acceder al estudio\n\n' +
						'Email: ' +
						participantEmail,
					confirmText: 'Sí, desactivar',
					onConfirm() {
						toggleParticipantStatus( participantId, newStatus );
					},
				} );
				return;
			}

			showConfirmationDialog( {
				title: 'Reactivar participante',
				message:
					'¿Reactivar a este participante? Volverá a recibir emails de recordatorio.',
				confirmText: 'Sí, reactivar',
				onConfirm() {
					toggleParticipantStatus( participantId, newStatus );
				},
			} );
		} );

		// Magic Link Actions
		$( document ).on( 'click', '.eipsi-magic-link-resend', function () {
			const participantId = $( this ).data( 'participant-id' );
			const participantEmail = $( this ).data( 'participant-email' );
			openResendMagicLinkModal( participantId, participantEmail );
		} );

		$( document ).on( 'click', '.eipsi-magic-link-generate', function () {
			const participantId = $( this ).data( 'participant-id' );
			const participantEmail = $( this ).data( 'participant-email' );
			openManualMagicLinkModal( participantId, participantEmail );
		} );

		$( document ).on( 'click', '.magic-link-extend', function () {
			const participantId = $( this ).data( 'participant-id' );
			const participantEmail = $( this ).data( 'participant-email' );
			confirmExtendMagicLink( participantId, participantEmail );
		} );

		$( document ).on( 'click', '#confirm-resend-magic-link', function () {
			resendMagicLinkFromModal();
		} );

		$( document ).on( 'click', '#manual-generate-magic-link', function () {
			generateManualMagicLink();
		} );

		$( document ).on( 'click', '#manual-copy-magic-link', function () {
			copyTextToClipboard(
				$( '#manual-magic-link-url' ).val(),
				'Magic Link copiado al portapapeles.'
			);
		} );

		$( document ).on( 'click', '#copy-resend-magic-link', function () {
			copyTextToClipboard(
				$( '#resend-magic-link-link' ).val(),
				'Magic Link copiado al portapapeles.'
			);
		} );

		$( document ).on( 'input', '#manual-magic-link-email', function () {
			clearManualMagicLinkMessages();
		} );

		$( document ).on(
			'click',
			'#eipsi-magic-link-resend-modal .eipsi-modal-close',
			function () {
				$( '#eipsi-magic-link-resend-modal' ).fadeOut( 200 );
			}
		);

		$( document ).on(
			'click',
			'#eipsi-magic-link-manual-modal .eipsi-modal-close',
			function () {
				$( '#eipsi-magic-link-manual-modal' ).fadeOut( 200 );
			}
		);

		// Pagination
		$( document ).on(
			'click',
			'.participants-pagination button',
			function () {
				const page = $( this ).data( 'page' );
				if ( page && page !== currentPage ) {
					loadParticipantsList( page );
				}
			}
		);

		// Quick Action: Edit Study
		$( document ).on( 'click', '#action-edit-study', function () {
			if ( currentStudyId ) {
				// Check if study is in draft status
				const statusBadge = $( '#study-status-badge' ).text();
				if ( statusBadge.includes( 'Borrador' ) ) {
					// Open edit modal
					openEditStudyModal( currentStudyId );
				} else {
					// Redirect to waves manager for non-draft studies
					window.location.href =
						'?page=eipsi-results&tab=waves-manager&study_id=' +
						currentStudyId;
				}
			}
		} );

		// Quick Action: Download Data
		$( document ).on( 'click', '#action-download-data', function () {
			if ( currentStudyId ) {
				window.location.href =
					'?page=eipsi-results&tab=export&study_id=' + currentStudyId;
			}
		} );

		// Quick Action: View Participants
		$( document ).on( 'click', '#action-view-participants', function () {
			if ( currentStudyId ) {
				openParticipantsModal();
			}
		} );

		// Quick Action: Add Participant
		$( document ).on( 'click', '#action-add-participant', function () {
			if ( currentStudyId ) {
				openAddParticipantModal();
			}
		} );

		// Add Participant Modal Close
		$( document ).on(
			'click',
			'#eipsi-add-participant-modal .eipsi-modal-close',
			function () {
				closeAddParticipantModal();
			}
		);

		// Add Participant Form Submit
		$( document ).on( 'submit', '#add-participant-form', function ( e ) {
			e.preventDefault();
			submitAddParticipant();
		} );

		// Quick Action: Close Study
		$( document ).on( 'click', '#action-close-study', function () {
			if ( ! currentStudyId ) {
				return;
			}

			showConfirmationDialog( {
				title: 'Cerrar estudio',
				message:
					'¿Estás seguro de cerrar este estudio?\n\n' +
					'• Se bloquearán nuevas respuestas\n' +
					'• El shortcode seguirá disponible para consulta\n\n' +
					'Esta acción no se puede deshacer.',
				confirmText: 'Sí, cerrar',
				onConfirm() {
					closeStudy( currentStudyId );
				},
			} );
		} );

		// Quick Action: Delete Study
		$( document ).on( 'click', '#action-delete-study', function () {
			if ( ! currentStudyId ) {
				return;
			}

			showConfirmationDialog( {
				title: 'Eliminar estudio',
				message:
					'⚠️ ESTA ACCIÓN ES IRREVERSIBLE ⚠️\n\n' +
					'¿Estás seguro de ELIMINAR este estudio?\n\n' +
					'• Se eliminarán TODOS los participantes\n' +
					'• Se eliminarán TODAS las respuestas\n' +
					'• Se eliminarán TODOS los waves\n' +
					'• Se eliminarán TODOS los emails\n\n' +
					'Esta acción NO se puede deshacer.',
				confirmText: 'Sí, eliminar',
				onConfirm() {
					deleteStudy( currentStudyId );
				},
			} );
		} );

		// Quick Action: Import CSV
		$( document ).on( 'click', '#action-import-csv', function () {
			if ( currentStudyId ) {
				openCsvImportModal();
			}
		} );

		// Quick Action: Cron Jobs
		$( document ).on( 'click', '#action-cron-jobs', function () {
			if ( currentStudyId ) {
				loadCronJobsConfig( currentStudyId );
				$( '#eipsi-cron-jobs-modal' ).fadeIn( 200 );
			}
		} );

		// Close Cron Jobs Modal
		$( document ).on(
			'click',
			'#eipsi-cron-jobs-modal .eipsi-modal-close',
			function () {
				$( '#eipsi-cron-jobs-modal' ).fadeOut( 200 );
			}
		);

		// Close CSV Import Modal
		$( document ).on(
			'click',
			'#eipsi-import-csv-modal .eipsi-modal-close, #csv-cancel-btn',
			function () {
				closeCsvImportModal();
			}
		);

		// CSV Upload area click
		$( document ).on( 'click', '#csv-upload-area', function () {
			$( '#csv-file-input' ).trigger( 'click' );
		} );

		// CSV File input change
		$( document ).on( 'change', '#csv-file-input', function ( e ) {
			const file = e.target.files[ 0 ];
			if ( file ) {
				handleCsvFile( file );
			}
		} );

		// CSV Drag and drop
		$( document ).on( 'dragover', '#csv-upload-area', function ( e ) {
			e.preventDefault();
			$( this ).addClass( 'dragover' );
		} );

		$( document ).on( 'dragleave', '#csv-upload-area', function ( e ) {
			e.preventDefault();
			$( this ).removeClass( 'dragover' );
		} );

		$( document ).on( 'drop', '#csv-upload-area', function ( e ) {
			e.preventDefault();
			$( this ).removeClass( 'dragover' );
			const file = e.originalEvent.dataTransfer.files[ 0 ];
			if (
				file &&
				( file.name.endsWith( '.csv' ) || file.name.endsWith( '.txt' ) )
			) {
				handleCsvFile( file );
			} else {
				showCsvError( 'Por favor, sube un archivo CSV válido' );
			}
		} );

		// Download CSV Template
		$( document ).on( 'click', '#download-csv-template', function ( e ) {
			e.preventDefault();
			downloadCsvTemplate();
		} );

		// Validate CSV Data
		$( document ).on( 'click', '#csv-validate-btn', function () {
			validateCsvData();
		} );

		// Import CSV Participants
		$( document ).on( 'click', '#csv-import-btn', function () {
			importCsvParticipants();
		} );

		// Done button
		$( document ).on( 'click', '#csv-done-btn', function () {
			closeCsvImportModal();
			// Refresh dashboard to show updated participant count
			if ( currentStudyId ) {
				loadStudyOverview( currentStudyId );
			}
		} );
	}

	// ===========================
	// MODAL MANAGEMENT
	// ===========================

	function openDashboardModal() {
		$( '#eipsi-study-dashboard-modal' ).fadeIn( 200 );
		$( '#eipsi-dashboard-loading' ).show();
		$( '#eipsi-dashboard-content' ).hide();
		resetMagicLinkForm();
	}

	function closeDashboardModal() {
		$( '#eipsi-study-dashboard-modal' ).fadeOut( 200 );
		currentStudyId = 0;
	}

	function closeStudy( studyId ) {
		const ajaxUrl =
			eipsiStudyDash.ajaxUrl ||
			( typeof ajaxurl !== 'undefined'
				? ajaxurl
				: '/wp-admin/admin-ajax.php' );

		$.ajax( {
			url: ajaxUrl,
			type: 'POST',
			data: {
				action: 'eipsi_close_study',
				nonce: eipsiStudyDash.nonce,
				study_id: studyId,
			},
			success( response ) {
				if ( response.success ) {
					showNotification( response.data.message, 'success' );
					loadStudyOverview( studyId );
				} else {
					showNotification(
						response.data || 'Error al cerrar el estudio',
						'error'
					);
				}
			},
			error() {
				showNotification( 'Error de conexión', 'error' );
			},
		} );
	}

	function deleteStudy( studyId ) {
		const ajaxUrl =
			eipsiStudyDash.ajaxUrl ||
			( typeof ajaxurl !== 'undefined'
				? ajaxurl
				: '/wp-admin/admin-ajax.php' );

		$.ajax( {
			url: ajaxUrl,
			type: 'POST',
			data: {
				action: 'eipsi_delete_study',
				nonce: eipsiStudyDash.nonce,
				study_id: studyId,
			},
			success( response ) {
				if ( response.success ) {
					showNotification( response.data.message, 'success' );
					// Redirect to studies list after 2 seconds
					setTimeout( function () {
						window.location.href =
							'?page=eipsi-longitudinal-study&tab=dashboard-study';
					}, 2000 );
				} else {
					showNotification(
						response.data || 'Error al eliminar el estudio',
						'error'
					);
				}
			},
			error() {
				showNotification( 'Error de conexión', 'error' );
			},
		} );
	}

	function openAddParticipantModal() {
		resetAddParticipantForm();
		$( '#add-participant-study-id' ).val( currentStudyId );
		$( '#eipsi-add-participant-modal' ).fadeIn( 200 );
		$( '#participant-email' ).trigger( 'focus' );
	}

	function closeAddParticipantModal() {
		$( '#eipsi-add-participant-modal' ).fadeOut( 200 );
	}

	function resetAddParticipantForm() {
		const form = $( '#add-participant-form' )[ 0 ];
		if ( form ) {
			form.reset();
		}
		$( '#add-participant-error' ).hide().empty();
		$( '#add-participant-success' ).hide().empty();
	}

	function submitAddParticipant() {
		const $btn = $( '#submit-add-participant' );
		const originalText = $btn.text();
		$btn.text( 'Enviando...' ).prop( 'disabled', true );

		$( '#add-participant-error' ).hide().empty();
		$( '#add-participant-success' ).hide().empty();

		const ajaxUrl =
			eipsiStudyDash.ajaxUrl ||
			( typeof ajaxurl !== 'undefined'
				? ajaxurl
				: '/wp-admin/admin-ajax.php' );

		$.ajax( {
			url: ajaxUrl,
			type: 'POST',
			data: {
				action: 'eipsi_add_participant',
				nonce: eipsiStudyDash.nonce,
				study_id: currentStudyId,
				email: $( '#participant-email' ).val(),
				first_name: $( '#participant-first-name' ).val(),
				last_name: $( '#participant-last-name' ).val(),
				password: $( '#participant-password' ).val(),
			},
			success( response ) {
				if ( response.success ) {
					let message =
						response.data && response.data.message
							? response.data.message
							: 'Participante creado e invitación enviada.';

					if ( response.data && response.data.email_sent === false ) {
						message =
							'Participante creado, pero la invitación no pudo enviarse.';
					}

					if (
						response.data &&
						response.data.temporary_password &&
						response.data.email_sent === false
					) {
						message +=
							'<br><strong>Contraseña temporal:</strong> ' +
							escapeHtml( response.data.temporary_password ) +
							'<br><small>Guárdala ahora; solo se mostrará una vez.</small>';
					}

					$( '#add-participant-success' )
						.html( '<p>' + message + '</p>' )
						.show();
					$( '#participant-password' ).val( '' );

					if ( currentStudyId ) {
						loadStudyOverview( currentStudyId );
					}

					if (
						$( '#eipsi-participants-list-modal' ).is( ':visible' )
					) {
						loadParticipantsList( currentPage );
					}
				} else {
					showAddParticipantError(
						response.data || 'Error al crear participante'
					);
				}
			},
			error() {
				showAddParticipantError( 'Error de conexión' );
			},
			complete() {
				$btn.text( originalText ).prop( 'disabled', false );
			},
		} );
	}

	function showAddParticipantError( message ) {
		$( '#add-participant-error' )
			.html( '<p>' + escapeHtml( message ) + '</p>' )
			.show();
	}

	// ===========================
	// MAGIC LINKS
	// ===========================

	function resetMagicLinkForm() {
		const form = $( '#magic-link-form' )[ 0 ];
		if ( form ) {
			form.reset();
		}
		$( '#magic-link-output' ).hide();
		$( '#magic-link-url' ).val( '' );
		clearMagicLinkMessages();
	}

	function clearMagicLinkMessages() {
		$( '#magic-link-error' ).hide().empty();
		$( '#magic-link-success' ).hide().empty();
	}

	function getMagicLinkEmail() {
		return $( '#magic-link-email' ).val().trim();
	}

	function setMagicLinkOutput( magicLink ) {
		if ( ! magicLink ) {
			return;
		}
		$( '#magic-link-url' ).val( magicLink );
		$( '#magic-link-output' ).show();
	}

	function showMagicLinkError( message ) {
		$( '#magic-link-error' )
			.html( '<p>' + escapeHtml( message ) + '</p>' )
			.show();
		$( '#magic-link-success' ).hide().empty();
	}

	function showMagicLinkSuccess( message ) {
		$( '#magic-link-success' )
			.html( '<p>' + escapeHtml( message ) + '</p>' )
			.show();
		$( '#magic-link-error' ).hide().empty();
	}

	function generateMagicLink() {
		if ( ! currentStudyId ) {
			showMagicLinkError( 'No hay estudio seleccionado.' );
			return;
		}

		const email = getMagicLinkEmail();
		if ( ! email ) {
			showMagicLinkError(
				'Ingresá el email del participante para generar el enlace.'
			);
			return;
		}

		const $btn = $( '#generate-magic-link' );
		const originalText = $btn.text();
		$btn.text( 'Generando...' ).prop( 'disabled', true );
		clearMagicLinkMessages();

		const ajaxUrl =
			eipsiStudyDash.ajaxUrl ||
			( typeof ajaxurl !== 'undefined'
				? ajaxurl
				: '/wp-admin/admin-ajax.php' );

		$.ajax( {
			url: ajaxUrl,
			type: 'POST',
			data: {
				action: 'eipsi_generate_magic_link',
				nonce: eipsiStudyDash.nonce,
				study_id: currentStudyId,
				email,
			},
			success( response ) {
				if ( response.success && response.data ) {
					setMagicLinkOutput( response.data.magic_link );
					showMagicLinkSuccess(
						response.data.message ||
							'Magic Link generado correctamente.'
					);
				} else {
					showMagicLinkError(
						response.data || 'No pudimos generar el Magic Link.'
					);
				}
			},
			error() {
				showMagicLinkError(
					'Error de conexión al generar el Magic Link.'
				);
			},
			complete() {
				$btn.text( originalText ).prop( 'disabled', false );
			},
		} );
	}

	function sendMagicLink() {
		if ( ! currentStudyId ) {
			showMagicLinkError( 'No hay estudio seleccionado.' );
			return;
		}

		const email = getMagicLinkEmail();
		if ( ! email ) {
			showMagicLinkError(
				'Ingresá el email del participante para enviar el enlace.'
			);
			return;
		}

		const $btn = $( '#send-magic-link' );
		const originalText = $btn.text();
		$btn.text( 'Enviando...' ).prop( 'disabled', true );
		clearMagicLinkMessages();

		const ajaxUrl =
			eipsiStudyDash.ajaxUrl ||
			( typeof ajaxurl !== 'undefined'
				? ajaxurl
				: '/wp-admin/admin-ajax.php' );

		$.ajax( {
			url: ajaxUrl,
			type: 'POST',
			data: {
				action: 'eipsi_send_magic_link',
				nonce: eipsiStudyDash.nonce,
				study_id: currentStudyId,
				email,
			},
			success( response ) {
				if ( response.success && response.data ) {
					setMagicLinkOutput( response.data.magic_link );
					showMagicLinkSuccess(
						response.data.message ||
							'Magic Link enviado correctamente.'
					);
				} else {
					showMagicLinkError(
						response.data || 'No pudimos enviar el Magic Link.'
					);
				}
			},
			error() {
				showMagicLinkError(
					'Error de conexión al enviar el Magic Link.'
				);
			},
			complete() {
				$btn.text( originalText ).prop( 'disabled', false );
			},
		} );
	}

	function copyMagicLink() {
		copyTextToClipboard(
			$( '#magic-link-url' ).val(),
			'Magic Link copiado al portapapeles.'
		);
	}

	function copyTextToClipboard( text, successMessage ) {
		if ( ! text ) {
			showNotification( 'No hay Magic Link para copiar.', 'error' );
			return;
		}

		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard
				.writeText( text )
				.then( function () {
					showNotification( successMessage, 'success' );
				} )
				.catch( function () {
					fallbackCopyText( text, successMessage );
				} );
			return;
		}

		fallbackCopyText( text, successMessage );
	}

	function fallbackCopyText( text, successMessage ) {
		const textarea = document.createElement( 'textarea' );
		textarea.value = text;
		textarea.setAttribute( 'readonly', '' );
		textarea.style.position = 'absolute';
		textarea.style.left = '-9999px';
		document.body.appendChild( textarea );
		textarea.select();
		document.execCommand( 'copy' );
		document.body.removeChild( textarea );
		showNotification( successMessage, 'success' );
	}

	function openResendMagicLinkModal( participantId, participantEmail ) {
		if ( ! participantId ) {
			return;
		}

		currentMagicLinkParticipantId = participantId;
		currentMagicLinkParticipantEmail = participantEmail || '';

		$( '#resend-magic-link-email' ).text( participantEmail || '' );
		$( '#resend-magic-link-subject' ).text( '' );
		$( '#resend-magic-link-preview' ).html(
			'<p>Cargando vista previa...</p>'
		);
		$( '#resend-magic-link-link' ).val( '' );
		$( '#resend-magic-link-link-wrap' ).hide();
		clearResendMagicLinkMessages();

		$( '#eipsi-magic-link-resend-modal' ).fadeIn( 200 );
		loadMagicLinkPreview( participantId );
	}

	function loadMagicLinkPreview( participantId ) {
		const ajaxUrl =
			eipsiStudyDash.ajaxUrl ||
			( typeof ajaxurl !== 'undefined'
				? ajaxurl
				: '/wp-admin/admin-ajax.php' );

		$.ajax( {
			url: ajaxUrl,
			type: 'GET',
			data: {
				action: 'eipsi_get_magic_link_preview',
				nonce: eipsiStudyDash.nonce,
				study_id: currentStudyId,
				participant_id: participantId,
			},
			success( response ) {
				if ( response.success && response.data ) {
					$( '#resend-magic-link-email' ).text(
						response.data.email || currentMagicLinkParticipantEmail
					);
					$( '#resend-magic-link-subject' ).text(
						response.data.subject || ''
					);
					$( '#resend-magic-link-preview' ).html(
						response.data.content || ''
					);

					if ( response.data.magic_link ) {
						$( '#resend-magic-link-link' ).val(
							response.data.magic_link
						);
						$( '#resend-magic-link-link-wrap' ).show();
					}
				} else {
					showResendMagicLinkError(
						response.data || 'No pudimos cargar la vista previa.'
					);
				}
			},
			error() {
				showResendMagicLinkError(
					'Error de conexión al cargar la vista previa.'
				);
			},
		} );
	}

	function resendMagicLinkFromModal() {
		if ( ! currentStudyId || ! currentMagicLinkParticipantId ) {
			showResendMagicLinkError(
				'No hay participante seleccionado para reenviar.'
			);
			return;
		}

		const $btn = $( '#confirm-resend-magic-link' );
		const originalText = $btn.text();
		$btn.text( 'Enviando...' ).prop( 'disabled', true );
		clearResendMagicLinkMessages();

		const ajaxUrl =
			eipsiStudyDash.ajaxUrl ||
			( typeof ajaxurl !== 'undefined'
				? ajaxurl
				: '/wp-admin/admin-ajax.php' );

		$.ajax( {
			url: ajaxUrl,
			type: 'POST',
			data: {
				action: 'eipsi_resend_magic_link',
				nonce: eipsiStudyDash.nonce,
				study_id: currentStudyId,
				participant_id: currentMagicLinkParticipantId,
			},
			success( response ) {
				if ( response.success && response.data ) {
					showResendMagicLinkSuccess(
						response.data.message || 'Magic Link reenviado.'
					);

					if ( response.data.magic_link ) {
						$( '#resend-magic-link-link' ).val(
							response.data.magic_link
						);
						$( '#resend-magic-link-link-wrap' ).show();
					}

					loadParticipantsList( currentPage );
				} else {
					showResendMagicLinkError(
						response.data || 'No pudimos reenviar el Magic Link.'
					);
				}
			},
			error() {
				showResendMagicLinkError(
					'Error de conexión al reenviar el Magic Link.'
				);
			},
			complete() {
				$btn.text( originalText ).prop( 'disabled', false );
			},
		} );
	}

	function clearResendMagicLinkMessages() {
		$( '#resend-magic-link-error' ).hide().empty();
		$( '#resend-magic-link-success' ).hide().empty();
	}

	function showResendMagicLinkError( message ) {
		$( '#resend-magic-link-error' )
			.html( '<p>' + escapeHtml( message ) + '</p>' )
			.show();
		$( '#resend-magic-link-success' ).hide().empty();
	}

	function showResendMagicLinkSuccess( message ) {
		$( '#resend-magic-link-success' )
			.html( '<p>' + escapeHtml( message ) + '</p>' )
			.show();
		$( '#resend-magic-link-error' ).hide().empty();
	}

	function openManualMagicLinkModal( participantId, participantEmail ) {
		currentMagicLinkParticipantId = participantId || 0;
		currentMagicLinkParticipantEmail = participantEmail || '';

		$( '#manual-magic-link-email' ).val( participantEmail || '' );
		$( '#manual-magic-link-output' ).hide();
		$( '#manual-magic-link-url' ).val( '' );
		clearManualMagicLinkMessages();

		$( '#eipsi-magic-link-manual-modal' ).fadeIn( 200 );
		$( '#manual-magic-link-email' ).trigger( 'focus' );
	}

	function clearManualMagicLinkMessages() {
		$( '#manual-magic-link-error' ).hide().empty();
		$( '#manual-magic-link-success' ).hide().empty();
	}

	function showManualMagicLinkError( message ) {
		$( '#manual-magic-link-error' )
			.html( '<p>' + escapeHtml( message ) + '</p>' )
			.show();
		$( '#manual-magic-link-success' ).hide().empty();
	}

	function showManualMagicLinkSuccess( message ) {
		$( '#manual-magic-link-success' )
			.html( '<p>' + escapeHtml( message ) + '</p>' )
			.show();
		$( '#manual-magic-link-error' ).hide().empty();
	}

	function generateManualMagicLink() {
		if ( ! currentStudyId ) {
			showManualMagicLinkError( 'No hay estudio seleccionado.' );
			return;
		}

		const email = $( '#manual-magic-link-email' ).val().trim();
		if ( ! email ) {
			showManualMagicLinkError( 'Ingresá un email válido.' );
			return;
		}

		const $btn = $( '#manual-generate-magic-link' );
		const originalText = $btn.text();
		$btn.text( 'Generando...' ).prop( 'disabled', true );
		clearManualMagicLinkMessages();

		const ajaxUrl =
			eipsiStudyDash.ajaxUrl ||
			( typeof ajaxurl !== 'undefined'
				? ajaxurl
				: '/wp-admin/admin-ajax.php' );

		$.ajax( {
			url: ajaxUrl,
			type: 'POST',
			data: {
				action: 'eipsi_generate_magic_link',
				nonce: eipsiStudyDash.nonce,
				study_id: currentStudyId,
				email,
			},
			success( response ) {
				if ( response.success && response.data ) {
					$( '#manual-magic-link-url' ).val(
						response.data.magic_link
					);
					$( '#manual-magic-link-output' ).show();
					showManualMagicLinkSuccess(
						response.data.message ||
							'Magic Link generado correctamente.'
					);
					loadParticipantsList( currentPage );
				} else {
					showManualMagicLinkError(
						response.data || 'No pudimos generar el Magic Link.'
					);
				}
			},
			error() {
				showManualMagicLinkError(
					'Error de conexión al generar el Magic Link.'
				);
			},
			complete() {
				$btn.text( originalText ).prop( 'disabled', false );
			},
		} );
	}

	function confirmExtendMagicLink( participantId, participantEmail ) {
		if ( ! participantId ) {
			return;
		}

		showConfirmationDialog( {
			title: 'Extender Magic Link',
			message:
				'¿Extender 48 horas el Magic Link de ' +
				( participantEmail || 'este participante' ) +
				'? Esta acción no invalida el enlace actual.',
			confirmText: 'Sí, extender',
			onConfirm() {
				extendMagicLink( participantId );
			},
		} );
	}

	function extendMagicLink( participantId ) {
		const ajaxUrl =
			eipsiStudyDash.ajaxUrl ||
			( typeof ajaxurl !== 'undefined'
				? ajaxurl
				: '/wp-admin/admin-ajax.php' );

		$.ajax( {
			url: ajaxUrl,
			type: 'POST',
			data: {
				action: 'eipsi_extend_magic_link',
				nonce: eipsiStudyDash.nonce,
				study_id: currentStudyId,
				participant_id: participantId,
			},
			success( response ) {
				if ( response.success ) {
					showNotification(
						response.data.message || 'Magic Link extendido.',
						'success'
					);
					loadParticipantsList( currentPage );
				} else {
					showNotification(
						response.data || 'No pudimos extender el Magic Link.',
						'error'
					);
				}
			},
			error() {
				showNotification(
					'Error de conexión al extender el Magic Link.',
					'error'
				);
			},
		} );
	}

	// ===========================
	// PARTICIPANTS LIST MODAL
	// ===========================

	function openParticipantsModal() {
		$( '#eipsi-participants-list-modal' ).fadeIn( 200 );
		$( '#participants-loading' ).show();
		$( '#participants-content' ).hide();
		loadParticipantsList( 1 );
	}

	function loadParticipantsList( page ) {
		if ( ! currentStudyId ) {
			return;
		}

		const statusFilter = $( '#participant-status-filter' ).val();
		const searchTerm = $( '#participant-search' ).val();

		$( '#participants-loading' ).show();
		$( '#participants-content' ).hide();

		const ajaxUrl =
			eipsiStudyDash.ajaxUrl ||
			( typeof ajaxurl !== 'undefined'
				? ajaxurl
				: '/wp-admin/admin-ajax.php' );

		$.ajax( {
			url: ajaxUrl,
			type: 'GET',
			data: {
				action: 'eipsi_get_participants_list',
				nonce: eipsiStudyDash.nonce,
				study_id: currentStudyId,
				page,
				per_page: participantsPerPage,
				status: statusFilter,
				search: searchTerm,
			},
			success( response ) {
				if ( response.success && response.data ) {
					renderParticipantsList( response.data );
				} else {
					showErrorParticipants(
						response.data || 'Error al cargar participantes'
					);
				}
			},
			error() {
				showErrorParticipants( 'Error de conexión' );
			},
			complete() {
				$( '#participants-loading' ).hide();
				$( '#participants-content' ).fadeIn( 200 );
			},
		} );
	}

	function renderParticipantsList( data ) {
		const participants = data.participants || [];
		const total = data.total || 0;
		const page = data.page || 1;
		const pages = data.pages || 1;

		currentPage = page;

		// Update count badge
		$( '#participants-count' ).text( total + ' participantes' );

		const $tbody = $( '#participants-tbody' );

		if ( participants.length === 0 ) {
			$tbody.html(
				'<tr><td colspan="7" style="text-align:center;padding:30px;color:#666;">No se encontraron participantes</td></tr>'
			);
			$( '#participants-pagination' ).empty();
			return;
		}

		// Render rows
		let html = '';
		participants.forEach( function ( p ) {
			const statusBadge = p.is_active
				? '<span class="status-badge active">● Activo</span>'
				: '<span class="status-badge inactive">● Inactivo</span>';

			const toggleButton = p.is_active
				? '<button class="button button-small toggle-participant-status" data-participant-id="' +
				  p.id +
				  '" data-is-active="true" data-participant-email="' +
				  escapeHtml( p.email ) +
				  '" title="Desactivar participante">🔴 Desactivar</button>'
				: '<button class="button button-small button-primary toggle-participant-status" data-participant-id="' +
				  p.id +
				  '" data-is-active="false" data-participant-email="' +
				  escapeHtml( p.email ) +
				  '" title="Reactivar participante">🟢 Reactivar</button>';

			const magicLinkStatus = getMagicLinkStatusHtml( p );
			const resendDisabled = p.is_active ? '' : ' disabled';
			const resendButton =
				'<button class="button button-small eipsi-magic-link-resend" data-participant-id="' +
				p.id +
				'" data-participant-email="' +
				escapeHtml( p.email ) +
				'"' +
				resendDisabled +
				'>🔁 Reenviar</button>';
			const generateButton =
				'<button class="button button-small button-secondary eipsi-magic-link-generate" data-participant-id="' +
				p.id +
				'" data-participant-email="' +
				escapeHtml( p.email ) +
				'"' +
				resendDisabled +
				'>🔗 Generar Link</button>';
			const viewDetailButton =
				'<button class="button button-small button-link view-participant-detail" data-participant-id="' +
				p.id +
				'" title="Ver detalles">👁️ Ver Detalles</button>';
			const actionsHtml =
				'<div class="participant-actions">' +
				viewDetailButton +
				toggleButton +
				resendButton +
				generateButton +
				'</div>';

			html += '<tr class="participant-row">';
			html += '<td><strong>' + escapeHtml( p.email ) + '</strong></td>';
			html +=
				'<td>' +
				escapeHtml( p.first_name ) +
				' ' +
				escapeHtml( p.last_name ) +
				'</td>';
			html += '<td>' + statusBadge + '</td>';
			html += '<td>' + magicLinkStatus + '</td>';
			html += '<td>' + formatDate( p.created_at ) + '</td>';
			html +=
				'<td>' +
				( p.last_login_at
					? formatDateTime( p.last_login_at )
					: 'Nunca' ) +
				'</td>';
			html += '<td>' + actionsHtml + '</td>';
			html += '</tr>';
		} );

		$tbody.html( html );

		// Render pagination
		renderParticipantsPagination( page, pages );
	}

	function getMagicLinkStatusMeta( status ) {
		const map = {
			sent: { label: 'Enviado', icon: '✉️', className: 'status-sent' },
			delivered: {
				label: 'Entregado',
				icon: '📬',
				className: 'status-delivered',
			},
			clicked: {
				label: 'Click',
				icon: '✅',
				className: 'status-clicked',
			},
			expired: {
				label: 'Vencido',
				icon: '⌛',
				className: 'status-expired',
			},
			failed: {
				label: 'Fallido',
				icon: '⚠️',
				className: 'status-failed',
			},
			none: { label: 'Sin envío', icon: '—', className: 'status-none' },
		};
		return map[ status ] || map.none;
	}

	function getMagicLinkStatusHtml( participant ) {
		const meta = getMagicLinkStatusMeta( participant.magic_link_status );
		let html =
			'<div class="magic-link-status ' +
			meta.className +
			'"><span class="status-icon">' +
			meta.icon +
			'</span><span>' +
			meta.label +
			'</span></div>';

		if ( participant.magic_link_expires_at ) {
			html +=
				'<div class="magic-link-expiry">Expira: ' +
				formatDateTime( participant.magic_link_expires_at ) +
				'</div>';
		}

		if ( participant.magic_link_sent_at ) {
			html +=
				'<div class="magic-link-sent-at">Último envío: ' +
				formatDateTime( participant.magic_link_sent_at ) +
				'</div>';
		}

		if ( participant.magic_link_can_extend ) {
			html +=
				'<button class="button button-small button-secondary magic-link-extend" data-participant-id="' +
				participant.id +
				'" data-participant-email="' +
				escapeHtml( participant.email ) +
				'">⏳ Extender 48 h</button>';
		}

		return html;
	}

	function renderParticipantsPagination( pageNum, totalPages ) {
		if ( totalPages <= 1 ) {
			$( '#participants-pagination' ).empty();
			return;
		}

		let html = '<div class="tablenav-pages">';

		if ( pageNum > 1 ) {
			html +=
				'<button class="button button-small" data-page="' +
				( pageNum - 1 ) +
				'">« Anterior</button>';
		}

		html += '<span class="paging-input">';
		html += pageNum + ' de ' + totalPages;
		html += '</span>';

		if ( pageNum < totalPages ) {
			html +=
				'<button class="button button-small" data-page="' +
				( pageNum + 1 ) +
				'">Siguiente »</button>';
		}

		html += '</div>';

		$( '#participants-pagination' ).html( html );
	}
	// ===========================
	// PARTICIPANT DETAIL
	// ===========================

	let currentDetailParticipantId = 0;

	function openParticipantDetailModal( participantId ) {
		currentDetailParticipantId = participantId;
		$( '#participant-detail-loading' ).show();
		$( '#participant-detail-content' ).hide();
		$( '#eipsi-participant-detail-modal' ).fadeIn( 200 );
		loadParticipantDetail( participantId );
	}

	function loadParticipantDetail( participantId ) {
		const ajaxUrl =
			eipsiStudyDash.ajaxUrl ||
			( typeof ajaxurl !== 'undefined'
				? ajaxurl
				: '/wp-admin/admin-ajax.php' );

		$.ajax( {
			url: ajaxUrl,
			type: 'GET',
			data: {
				action: 'eipsi_get_participant_detail',
				nonce: eipsiStudyDash.nonce,
				participant_id: participantId,
			},
			success( response ) {
				if ( response.success ) {
					renderParticipantDetail( response.data );
				} else {
					alert(
						response.data || 'Error loading participant detail'
					);
				}
			},
			error() {
				alert( 'Error loading participant detail' );
			},
		} );
	}

	function renderParticipantDetail( data ) {
		const p = data.participant;
		const study = data.study;

		// Participant info
		$( '#detail-participant-email' ).text( p.email );
		$( '#detail-participant-name' ).text(
			( p.first_name || '' ) + ' ' + ( p.last_name || '' )
		);

		// Status badge
		const statusBadge = $( '#detail-participant-status' );
		if ( p.is_active ) {
			statusBadge
				.text( 'Activo' )
				.removeClass()
				.addClass( 'eipsi-badge badge-active' );
		} else {
			statusBadge
				.text( 'Inactivo' )
				.removeClass()
				.addClass( 'eipsi-badge badge-inactive' );
		}

		// Session status
		const sessionEl = $( '#detail-participant-session' );
		if ( data.has_active_session ) {
			sessionEl.html( '<span style="color: #27ae60;">✅ Sí</span>' );
		} else {
			sessionEl.html( '<span style="color: #95a5a6;">❌ No</span>' );
		}

		// Dates
		$( '#detail-participant-created' ).text( formatDate( p.created_at ) );
		$( '#detail-participant-last-login' ).text(
			p.last_login_at ? formatDate( p.last_login_at ) : '—'
		);

		// Timeline
		renderParticipantTimeline( data.timeline );

		// Magic Link History
		renderMagicLinkHistory( data.magic_link_history );

		// Show content
		$( '#participant-detail-loading' ).hide();
		$( '#participant-detail-content' ).show();
	}

	function renderParticipantTimeline( timeline ) {
		const container = $( '#participant-timeline' );
		let html = '';

		if ( ! timeline || timeline.length === 0 ) {
			html =
				'<p style="color: #666; font-style: italic;">No hay eventos en la línea de tiempo.</p>';
		} else {
			timeline.forEach( function ( event ) {
				const dateStr = event.date ? formatDate( event.date ) : '—';
				html +=
					'<div class="timeline-event ' +
					event.status +
					'">' +
					'<div class="event-label">' +
					event.icon +
					' ' +
					event.label +
					'</div>' +
					'<div class="event-date">' +
					dateStr +
					'</div>';

				if ( event.form_title ) {
					html +=
						'<div class="event-form">' +
						event.form_title +
						'</div>';
				}

				html += '</div>';
			} );
		}

		container.html( html );
	}

	function renderMagicLinkHistory( history ) {
		const tbody = $( '#magic-link-history-tbody' );
		let html = '';

		if ( ! history || history.length === 0 ) {
			html =
				'<tr><td colspan="4" style="text-align: center; color: #666;">No hay magic links enviados.</td></tr>';
		} else {
			history.forEach( function ( link ) {
				const createdAt = formatDate( link.created_at );
				const expiresAt = formatDate( link.expires_at );
				const usedAt = link.used_at ? formatDate( link.used_at ) : '—';

				let statusHtml =
					'<span style="color: #95a5a6;">Sin usar</span>';
				if ( link.used_at ) {
					statusHtml = '<span style="color: #27ae60;">Usado</span>';
				} else if ( new Date( link.expires_at ) < new Date() ) {
					statusHtml =
						'<span style="color: #e74c3c;">Expirado</span>';
				} else {
					statusHtml = '<span style="color: #3498db;">Activo</span>';
				}

				html +=
					'<tr>' +
					'<td>' +
					createdAt +
					'</td>' +
					'<td>' +
					expiresAt +
					'</td>' +
					'<td>' +
					statusHtml +
					'</td>' +
					'<td>' +
					usedAt +
					'</td>' +
					'</tr>';
			} );
		}

		tbody.html( html );
	}

	function formatDate( dateStr ) {
		if ( ! dateStr ) {
			return '—';
		}
		const date = new Date( dateStr );
		return date.toLocaleString( 'es-AR', {
			year: 'numeric',
			month: 'short',
			day: 'numeric',
			hour: '2-digit',
			minute: '2-digit',
		} );
	}

	// ===========================
	// REMOVE PARTICIPANT
	// ===========================

	function openRemoveParticipantModal() {
		$( '#remove-participant-error' ).hide();
		$( '#remove-participant-success' ).hide();
		$( '#remove-participant-reason' ).val( '' );
		$( '#eipsi-remove-participant-modal' ).fadeIn( 200 );
	}

	function closeRemoveParticipantModal() {
		$( '#eipsi-remove-participant-modal' ).fadeOut( 200 );
	}

	function deactivateParticipant() {
		const reason = $( '#remove-participant-reason' ).val();
		const participantId = currentDetailParticipantId;

		$( '#remove-participant-error' ).hide();
		$( '#btn-confirm-deactivate' )
			.prop( 'disabled', true )
			.text( 'Procesando...' );

		const ajaxUrl =
			eipsiStudyDash.ajaxUrl ||
			( typeof ajaxurl !== 'undefined'
				? ajaxurl
				: '/wp-admin/admin-ajax.php' );

		$.ajax( {
			url: ajaxUrl,
			type: 'POST',
			data: {
				action: 'eipsi_remove_participant',
				nonce: eipsiStudyDash.nonce,
				participant_id: participantId,
				reason,
			},
			success( response ) {
				if ( response.success ) {
					$( '#remove-participant-success' )
						.text( response.data.message )
						.show();
					setTimeout( function () {
						closeRemoveParticipantModal();
						$( '#eipsi-participant-detail-modal' ).fadeOut( 200 );
						// Refresh participants list
						loadParticipantsList( 1 );
					}, 1500 );
				} else {
					$( '#remove-participant-error' )
						.text( response.data.message || 'Error' )
						.show();
				}
			},
			error() {
				$( '#remove-participant-error' )
					.text( 'Error de conexión' )
					.show();
			},
			complete() {
				$( '#btn-confirm-deactivate' )
					.prop( 'disabled', false )
					.text( 'Desactivar' );
			},
		} );
	}

	function deleteParticipantHard() {
		if (
			! confirm(
				'¿Estás completamente seguro? Esta acción eliminará TODOS los datos del participante incluyendo sus respuestas. NO SE PUEDE DESHACER.'
			)
		) {
			return;
		}

		const reason = $( '#remove-participant-reason' ).val();
		const participantId = currentDetailParticipantId;

		$( '#remove-participant-error' ).hide();
		$( '#btn-confirm-delete' )
			.prop( 'disabled', true )
			.text( 'Eliminando...' );

		const ajaxUrl =
			eipsiStudyDash.ajaxUrl ||
			( typeof ajaxurl !== 'undefined'
				? ajaxurl
				: '/wp-admin/admin-ajax.php' );

		$.ajax( {
			url: ajaxUrl,
			type: 'POST',
			data: {
				action: 'eipsi_delete_participant',
				nonce: eipsiStudyDash.nonce,
				participant_id: participantId,
				reason,
			},
			success( response ) {
				if ( response.success ) {
					$( '#remove-participant-success' )
						.text( response.data.message )
						.show();
					setTimeout( function () {
						closeRemoveParticipantModal();
						$( '#eipsi-participant-detail-modal' ).fadeOut( 200 );
						// Refresh participants list
						loadParticipantsList( 1 );
					}, 1500 );
				} else {
					$( '#remove-participant-error' )
						.text( response.data.message || 'Error' )
						.show();
				}
			},
			error() {
				$( '#remove-participant-error' )
					.text( 'Error de conexión' )
					.show();
			},
			complete() {
				$( '#btn-confirm-delete' )
					.prop( 'disabled', false )
					.text( 'Eliminar Permanentemente' );
			},
		} );
	}

	function toggleParticipantStatus( participantId, isActive ) {
		const $btn = $(
			'.toggle-participant-status[data-participant-id="' +
				participantId +
				'"]'
		);
		const originalText = $btn.text();
		$btn.text( 'Procesando...' ).prop( 'disabled', true );

		const ajaxUrl =
			eipsiStudyDash.ajaxUrl ||
			( typeof ajaxurl !== 'undefined'
				? ajaxurl
				: '/wp-admin/admin-ajax.php' );

		$.ajax( {
			url: ajaxUrl,
			type: 'POST',
			data: {
				action: 'eipsi_toggle_participant_status',
				nonce: eipsiStudyDash.nonce,
				participant_id: participantId,
				is_active: isActive ? 1 : 0,
			},
			success( response ) {
				if ( response.success ) {
					showNotification( response.data.message, 'success' );
					// Refresh list
					loadParticipantsList( currentPage );
				} else {
					showNotification(
						response.data || 'Error al cambiar el estado',
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

	function showErrorParticipants( message ) {
		const $tbody = $( '#participants-tbody' );
		$tbody.html(
			'<tr><td colspan="7" style="text-align:center;color:#d63638;padding:30px;"><strong>Error:</strong> ' +
				message +
				'</td></tr>'
		);
		$( '#participants-content' ).show();
	}

	// ===========================
	// CSV IMPORT MODAL
	// ===========================

	let csvRawData = '';
	let csvValidationResults = null;

	function openCsvImportModal() {
		resetCsvModal();
		$( '#eipsi-import-csv-modal' ).fadeIn( 200 );
	}

	function closeCsvImportModal() {
		$( '#eipsi-import-csv-modal' ).fadeOut( 200 );
		resetCsvModal();
	}

	function resetCsvModal() {
		csvRawData = '';
		csvValidationResults = null;

		// Reset steps
		$( '.csv-import-step' ).hide();
		$( '#csv-step-1' ).show();

		// Reset buttons
		$( '#csv-cancel-btn' ).show();
		$( '#csv-validate-btn' ).hide();
		$( '#csv-import-btn' ).hide().prop( 'disabled', true );
		$( '#csv-done-btn' ).hide();

		// Reset file input
		$( '#csv-file-input' ).val( '' );

		// Reset messages
		$( '#csv-import-error, #csv-import-success' ).hide();

		// Reset preview
		$( '#csv-preview-tbody' ).empty();
		$( '#csv-validation-summary' ).empty();
	}

	function showCsvStep( stepNumber ) {
		$( '.csv-import-step' ).hide();
		$( '#csv-step-' + stepNumber ).show();
	}

	function showCsvError( message ) {
		$( '#csv-import-error' ).text( message ).show();
		$( '#csv-import-success' ).hide();
	}

	function showCsvSuccess( message ) {
		$( '#csv-import-success' ).text( message ).show();
		$( '#csv-import-error' ).hide();
	}

	function handleCsvFile( file ) {
		// Validar tamaño (máximo 1MB)
		if ( file.size > 1024 * 1024 ) {
			showCsvError( 'El archivo es demasiado grande. Máximo 1MB.' );
			return;
		}

		const reader = new FileReader();

		reader.onload = function ( e ) {
			csvRawData = e.target.result;

			// Contar líneas aproximadas
			const lines = csvRawData
				.split( /\r\n|\n|\r/ )
				.filter( function ( line ) {
					return line.trim() !== '';
				} );

			if ( lines.length === 0 ) {
				showCsvError( 'El archivo CSV está vacío' );
				return;
			}

			if ( lines.length > 501 ) {
				showCsvError(
					'El archivo contiene más de 500 participantes. Por favor, divide el archivo.'
				);
				return;
			}

			// Mostrar botón de validar
			$( '#csv-validate-btn' ).show();
			showCsvSuccess(
				'Archivo cargado: ' +
					( lines.length - 1 ) +
					' participantes detectados (aprox)'
			);
		};

		reader.onerror = function () {
			showCsvError( 'Error al leer el archivo' );
		};

		reader.readAsText( file );
	}

	function downloadCsvTemplate() {
		const template =
			'email,first_name,last_name\n' +
			'juan.perez@email.com,Juan,Pérez\n' +
			'maria.garcia@email.com,María,García\n' +
			'carlos.lopez@email.com,Carlos,López';

		const blob = new Blob( [ template ], {
			type: 'text/csv;charset=utf-8;',
		} );
		const link = document.createElement( 'a' );
		link.href = URL.createObjectURL( blob );
		link.download = 'plantilla_participantes_eipsi.csv';
		link.click();
	}

	function validateCsvData() {
		if ( ! csvRawData || ! currentStudyId ) {
			showCsvError( 'No hay datos para validar' );
			return;
		}

		const $btn = $( '#csv-validate-btn' );
		const originalText = $btn.text();
		$btn.text( 'Validando...' ).prop( 'disabled', true );

		const ajaxUrl =
			eipsiStudyDash.ajaxUrl ||
			( typeof ajaxurl !== 'undefined'
				? ajaxurl
				: '/wp-admin/admin-ajax.php' );

		$.ajax( {
			url: ajaxUrl,
			type: 'POST',
			data: {
				action: 'eipsi_validate_csv_participants',
				nonce: eipsiStudyDash.nonce,
				study_id: currentStudyId,
				csv_data: csvRawData,
			},
			success( response ) {
				if ( response.success && response.data ) {
					csvValidationResults = response.data;
					renderCsvPreview( response.data );
					showCsvStep( 2 );

					// Mostrar/ocultar botones según validación
					$( '#csv-validate-btn' ).hide();
					if ( response.data.summary.valid > 0 ) {
						$( '#csv-import-btn' ).show().prop( 'disabled', false );
					}
				} else {
					showCsvError( response.data || 'Error al validar el CSV' );
				}
			},
			error() {
				showCsvError( 'Error de conexión al validar' );
			},
			complete() {
				$btn.text( originalText ).prop( 'disabled', false );
			},
		} );
	}

	function renderCsvPreview( data ) {
		const summary = data.summary;
		const participants = data.participants;

		// Actualizar contador
		$( '#csv-preview-count' ).text( summary.total + ' filas' );

		// Resumen de validación
		let summaryHtml = '<div class="validation-summary-inner">';
		summaryHtml +=
			'<span class="summary-item valid">✓ ' +
			summary.valid +
			' válidos</span>';
		if ( summary.invalid > 0 ) {
			summaryHtml +=
				'<span class="summary-item invalid">✗ ' +
				summary.invalid +
				' inválidos</span>';
		}
		if ( summary.existing > 0 ) {
			summaryHtml +=
				'<span class="summary-item existing">⚠ ' +
				summary.existing +
				' existentes</span>';
		}
		summaryHtml += '</div>';
		$( '#csv-validation-summary' ).html( summaryHtml );

		// Tabla de preview (mostrar máximo 50)
		let html = '';
		const previewLimit = Math.min( participants.length, 50 );

		for ( let i = 0; i < previewLimit; i++ ) {
			const p = participants[ i ];
			let statusBadge = '';

			if ( p.status === 'valid' ) {
				statusBadge =
					'<span class="status-badge valid">✓ Válido</span>';
			} else if ( p.status === 'invalid' ) {
				statusBadge =
					'<span class="status-badge invalid" title="' +
					escapeHtml( p.errors.join( ', ' ) ) +
					'">✗ Inválido</span>';
			} else if ( p.status === 'existing' ) {
				statusBadge =
					'<span class="status-badge existing">⚠ Existente</span>';
			}

			html += '<tr class="status-' + p.status + '">';
			html += '<td>' + p.row + '</td>';
			html += '<td>' + escapeHtml( p.email ) + '</td>';
			html += '<td>' + escapeHtml( p.first_name ) + '</td>';
			html += '<td>' + escapeHtml( p.last_name ) + '</td>';
			html += '<td>' + statusBadge + '</td>';
			html += '</tr>';
		}

		if ( participants.length > 50 ) {
			html +=
				'<tr><td colspan="5" class="preview-more">... y ' +
				( participants.length - 50 ) +
				' más</td></tr>';
		}

		$( '#csv-preview-tbody' ).html( html );
	}

	function importCsvParticipants() {
		if (
			! csvValidationResults ||
			! csvValidationResults.participants ||
			! currentStudyId
		) {
			showCsvError( 'No hay participantes para importar' );
			return;
		}

		// Filtrar solo participantes válidos
		const validParticipants = csvValidationResults.participants.filter(
			function ( p ) {
				return p.status === 'valid';
			}
		);

		if ( validParticipants.length === 0 ) {
			showCsvError( 'No hay participantes válidos para importar' );
			return;
		}

		showConfirmationDialog( {
			title: 'Importar participantes',
			message:
				'¿Importar ' +
				validParticipants.length +
				' participantes y enviar invitaciones por email?',
			confirmText: 'Sí, importar',
			onConfirm() {
				startCsvImportProcess( validParticipants );
			},
		} );
	}

	function startCsvImportProcess( validParticipants ) {
		showCsvStep( 3 );
		$( '#csv-cancel-btn, #csv-import-btn' ).hide();

		const total = validParticipants.length;
		let processed = 0;
		let imported = 0;
		let failed = 0;
		let emailsSent = 0;

		// Procesar en lotes de 10 para mostrar progreso
		const batchSize = 10;
		const batches = [];

		for ( let i = 0; i < validParticipants.length; i += batchSize ) {
			batches.push( validParticipants.slice( i, i + batchSize ) );
		}

		function processBatch( batchIndex ) {
			if ( batchIndex >= batches.length ) {
				// Finalizar
				showImportResults( imported, failed, emailsSent );
				return;
			}

			const batch = batches[ batchIndex ];

			const ajaxUrl =
				eipsiStudyDash.ajaxUrl ||
				( typeof ajaxurl !== 'undefined'
					? ajaxurl
					: '/wp-admin/admin-ajax.php' );

			$.ajax( {
				url: ajaxUrl,
				type: 'POST',
				data: {
					action: 'eipsi_import_csv_participants',
					nonce: eipsiStudyDash.nonce,
					study_id: currentStudyId,
					participants: batch,
				},
				success( response ) {
					if ( response.success && response.data ) {
						imported += response.data.results.imported;
						failed += response.data.results.failed;
						emailsSent += response.data.results.emails_sent;
					} else {
						failed += batch.length;
					}
				},
				error() {
					failed += batch.length;
				},
				complete() {
					processed += batch.length;
					updateProgress(
						processed,
						total,
						imported,
						failed,
						emailsSent
					);
					processBatch( batchIndex + 1 );
				},
			} );
		}

		function updateProgress(
			current,
			totalCount,
			importCount,
			failCount,
			sentCount
		) {
			const pct = Math.round( ( current / totalCount ) * 100 );
			$( '#csv-import-progress-bar' ).css( 'width', pct + '%' );
			$( '#csv-import-counter' ).text( current + ' / ' + totalCount );
			$( '#csv-progress-details' ).html(
				'Importados: ' +
					importCount +
					' | Emails enviados: ' +
					sentCount +
					' | Fallidos: ' +
					failCount
			);
		}

		function showImportResults( importCount, failCount, sentCount ) {
			showCsvStep( 4 );

			let resultsHtml = '<div class="csv-results-inner">';

			if ( importCount > 0 ) {
				resultsHtml += '<div class="result-item success">';
				resultsHtml += '<span class="result-icon">✓</span>';
				resultsHtml +=
					'<span class="result-text">' +
					importCount +
					' participantes importados exitosamente</span>';
				resultsHtml += '</div>';
			}

			if ( sentCount > 0 ) {
				resultsHtml += '<div class="result-item success">';
				resultsHtml += '<span class="result-icon">✉️</span>';
				resultsHtml +=
					'<span class="result-text">' +
					sentCount +
					' invitaciones enviadas</span>';
				resultsHtml += '</div>';
			}

			if ( failCount > 0 ) {
				resultsHtml += '<div class="result-item error">';
				resultsHtml += '<span class="result-icon">✗</span>';
				resultsHtml +=
					'<span class="result-text">' +
					failCount +
					' participantes no pudieron ser importados</span>';
				resultsHtml += '</div>';
			}

			resultsHtml += '</div>';

			$( '#csv-results' ).html( resultsHtml );
			$( '#csv-done-btn' ).show();
		}

		// Iniciar procesamiento
		processBatch( 0 );
	}

	// ===========================
	// CRON JOBS MODAL
	// ===========================

	function loadCronJobsConfig( studyId ) {
		$( '#cron-jobs-loading' ).show();
		$( '#cron-jobs-content' ).hide();

		// Load the cron jobs tab content via AJAX
		const ajaxUrl =
			eipsiStudyDash.ajaxUrl ||
			( typeof ajaxurl !== 'undefined'
				? ajaxurl
				: '/wp-admin/admin-ajax.php' );

		$.ajax( {
			url: ajaxUrl,
			type: 'GET',
			data: {
				action: 'eipsi_get_study_cron_config',
				nonce: eipsiStudyDash.nonce,
				study_id: studyId,
			},
			success( response ) {
				if ( response.success && response.data ) {
					$( '#cron-jobs-content' ).html( response.data.html );
					$( '#cron-jobs-content' ).fadeIn( 200 );
				} else {
					$( '#cron-jobs-content' ).html(
						'<div class="notice notice-error"><p>Error al cargar la configuración de cron jobs</p></div>'
					);
					$( '#cron-jobs-content' ).show();
				}
			},
			error() {
				$( '#cron-jobs-content' ).html(
					'<div class="notice notice-error"><p>Error de conexión al cargar la configuración</p></div>'
				);
				$( '#cron-jobs-content' ).show();
			},
			complete() {
				$( '#cron-jobs-loading' ).hide();
			},
		} );
	}

	// ===========================
	// DATA LOADING
	// ===========================

	function loadStudyOverview( studyId ) {
		if ( isLoading ) {
			return;
		}
		isLoading = true;

		$( '#eipsi-dashboard-loading' ).show();
		$( '#eipsi-dashboard-content' ).hide();

		const ajaxUrl =
			eipsiStudyDash.ajaxUrl ||
			( typeof ajaxurl !== 'undefined'
				? ajaxurl
				: '/wp-admin/admin-ajax.php' );

		$.ajax( {
			url: ajaxUrl,
			type: 'GET',
			data: {
				action: 'eipsi_get_study_overview',
				nonce: eipsiStudyDash.nonce,
				study_id: studyId,
			},
			success( response ) {
				if ( response.success && response.data ) {
					renderDashboard( response.data );
				} else {
					showError(
						response.data || 'Error al cargar los datos del estudio'
					);
				}
			},
			error() {
				showError( 'Error de conexión al cargar los datos' );
			},
			complete() {
				isLoading = false;
				$( '#eipsi-dashboard-loading' ).hide();
			},
		} );
	}

	function renderDashboard( data ) {
		// Update Modal Title
		if ( data.general && data.general.study_name ) {
			$( '#study-modal-title' ).text(
				'Detalles: ' + escapeHtml( data.general.study_name )
			);
		}

		// General Status Card
		if ( data.general ) {
			const statusBadge = getStatusBadge( data.general.status );
			$( '#study-status-badge' ).html( statusBadge );
			$( '#study-created-at' ).text(
				formatDate( data.general.created_at )
			);
			$( '#study-estimated-end' ).text(
				data.general.estimated_end_date
					? formatDate( data.general.estimated_end_date )
					: 'No definida'
			);
			$( '#study-id-display' ).text( data.general.study_code );

			const shortcode = buildStudyShortcode(
				data.general.id || currentStudyId,
				data.general.study_code // Use study_code for security
			);
			$( '#study-shortcode-display' ).text( shortcode );

			const isCompleted = data.general.status === 'completed';
			const $closeBtn = $( '#action-close-study' );
			const originalLabel = $closeBtn.data( 'label' ) || $closeBtn.text();
			$closeBtn.data( 'label', originalLabel );
			$closeBtn.prop( 'disabled', isCompleted );
			$closeBtn.text(
				isCompleted ? '🔒 Estudio cerrado' : originalLabel
			);
		}

		// Participants Card
		if ( data.participants ) {
			const total = data.participants.total || 0;
			const completed = data.participants.completed || 0;
			const inProgress = data.participants.in_progress || 0;
			const inactive = data.participants.inactive || 0;

			$( '#total-participants' ).text( total );

			// Progress bars
			const completedPct =
				total > 0 ? Math.round( ( completed / total ) * 100 ) : 0;
			const inProgressPct =
				total > 0 ? Math.round( ( inProgress / total ) * 100 ) : 0;
			const inactivePct =
				total > 0 ? Math.round( ( inactive / total ) * 100 ) : 0;

			$( '#bar-completed' ).css( 'width', completedPct + '%' );
			$( '#percent-completed' ).text( completedPct + '%' );
			$( '#bar-in-progress' ).css( 'width', inProgressPct + '%' );
			$( '#percent-in-progress' ).text( inProgressPct + '%' );
			$( '#bar-inactive' ).css( 'width', inactivePct + '%' );
			$( '#percent-inactive' ).text( inactivePct + '%' );
		}

		// Waves Card
		if ( data.waves && data.waves.length > 0 ) {
			renderWaves( data.waves );
		} else {
			$( '#waves-container' ).html(
				'<p style="text-align:center;color:#666;padding:20px;">No hay tomas configuradas</p>'
			);
		}

		// Emails Card
		if ( data.emails ) {
			$( '#emails-sent-today' ).text( data.emails.sent_today || 0 );
			$( '#emails-failed' ).text( data.emails.failed || 0 );
			$( '#emails-last-sent' ).text(
				data.emails.last_sent
					? formatDateTime( data.emails.last_sent )
					: 'Nunca'
			);
		}

		// Show content
		$( '#eipsi-dashboard-content' ).fadeIn( 200 );
	}

	function renderWaves( waves ) {
		let html = '';

		waves.forEach( function ( wave ) {
			const statusBadge = getStatusBadge( wave.status );
			let progressColor = 'orange';
			if ( wave.progress >= 75 ) {
				progressColor = 'green';
			} else if ( wave.progress >= 50 ) {
				progressColor = 'blue';
			}

			html +=
				'<div class="wave-summary-card" data-wave-id="' +
				wave.id +
				'">' +
				'<div class="wave-header">' +
				'<h4>' +
				escapeHtml( wave.wave_name ) +
				'</h4>' +
				statusBadge +
				'</div>' +
				'<div class="wave-progress-bar">' +
				'<div class="progress-fill ' +
				progressColor +
				'" style="width:' +
				wave.progress +
				'%">' +
				'</div>' +
				'<span class="progress-text">' +
				wave.progress +
				'%</span>' +
				'</div>' +
				'<div class="wave-stats-row">' +
				'<span><strong>' +
				wave.completed +
				'</strong>/' +
				wave.total +
				' completados</span>' +
				'<span class="deadline">Vence: ' +
				( wave.deadline ? formatDate( wave.deadline ) : 'Sin fecha' ) +
				'</span>' +
				'</div>' +
				'<div class="wave-actions-row">' +
				'<button class="button button-small extend-deadline" data-wave-id="' +
				wave.id +
				'">📅 Extender</button>' +
				'<button class="button button-small send-reminder" data-wave-id="' +
				wave.id +
				'">📧 Recordatorio</button>' +
				'</div>' +
				'</div>';
		} );

		$( '#waves-container' ).html( html );

		// Bind wave action buttons
		$( '.extend-deadline' ).on( 'click', function () {
			const waveId = $( this ).data( 'wave-id' );
			$( '#extend-wave-id' ).val( waveId );
			$( '#eipsi-extend-deadline-modal' ).fadeIn( 200 );
		} );

		$( '.send-reminder' ).on( 'click', function () {
			const waveId = $( this ).data( 'wave-id' );
			sendWaveReminder( waveId );
		} );
	}

	function loadEmailLogs( studyId ) {
		const $tbody = $( '#email-logs-tbody' );
		$tbody.html(
			'<tr><td colspan="4" style="text-align:center;padding:20px;"><span class="spinner is-active"></span> Cargando...</td></tr>'
		);

		const ajaxUrl =
			eipsiStudyDash.ajaxUrl ||
			( typeof ajaxurl !== 'undefined'
				? ajaxurl
				: '/wp-admin/admin-ajax.php' );

		$.ajax( {
			url: ajaxUrl,
			type: 'GET',
			data: {
				action: 'eipsi_get_study_email_logs',
				nonce: eipsiStudyDash.nonce,
				study_id: studyId,
			},
			success( response ) {
				if ( response.success && response.data ) {
					renderEmailLogs( response.data );
				} else {
					$tbody.html(
						'<tr><td colspan="4" style="text-align:center;color:#666;">No hay logs de emails</td></tr>'
					);
				}
			},
			error() {
				$tbody.html(
					'<tr><td colspan="4" style="text-align:center;color:#d63638;">Error al cargar logs</td></tr>'
				);
			},
		} );
	}

	function renderEmailLogs( logs ) {
		const $tbody = $( '#email-logs-tbody' );

		if ( logs.length === 0 ) {
			$tbody.html(
				'<tr><td colspan="4" style="text-align:center;color:#666;">No hay emails registrados</td></tr>'
			);
			return;
		}

		let html = '';
		logs.forEach( function ( log ) {
			const statusBadge =
				log.status === 'sent'
					? '<span style="color:#27ae60;">✓ Enviado</span>'
					: '<span style="color:#d63638;">✗ Fallido</span>';

			html +=
				'<tr>' +
				'<td>' +
				formatDateTime( log.sent_at ) +
				'</td>' +
				'<td>' +
				escapeHtml( log.recipient_email ) +
				'</td>' +
				'<td>' +
				escapeHtml( log.subject || 'Sin asunto' ) +
				'</td>' +
				'<td>' +
				statusBadge +
				'</td>' +
				'</tr>';
		} );

		$tbody.html( html );
	}

	// ===========================
	// ACTIONS
	// ===========================

	function extendWaveDeadline( waveId, newDeadline ) {
		const $btn = $( '#extend-deadline-form button[type="submit"]' );
		const originalText = $btn.text();
		$btn.text( 'Guardando...' ).prop( 'disabled', true );

		const ajaxUrl =
			eipsiStudyDash.ajaxUrl ||
			( typeof ajaxurl !== 'undefined'
				? ajaxurl
				: '/wp-admin/admin-ajax.php' );

		$.ajax( {
			url: ajaxUrl,
			type: 'POST',
			data: {
				action: 'eipsi_extend_wave_deadline',
				nonce: eipsiStudyDash.nonce,
				wave_id: waveId,
				new_deadline: newDeadline + ' 23:59:59', // Add time to date
			},
			success( response ) {
				if ( response.success ) {
					showNotification(
						'Plazo extendido exitosamente',
						'success'
					);
					$( '#eipsi-extend-deadline-modal' ).fadeOut( 200 );
					// Refresh dashboard
					loadStudyOverview( currentStudyId );
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

	function sendWaveReminder( waveId ) {
		showConfirmationDialog( {
			title: 'Enviar recordatorios',
			message:
				'¿Enviar recordatorios a todos los participantes pendientes de esta toma?',
			confirmText: 'Sí, enviar',
			onConfirm() {
				executeWaveReminderSend( waveId );
			},
		} );
	}

	function executeWaveReminderSend( waveId ) {
		const $btn = $( '.send-reminder[data-wave-id="' + waveId + '"]' );
		const originalText = $btn.text();
		$btn.text( 'Enviando...' ).prop( 'disabled', true );

		const ajaxUrl =
			eipsiStudyDash.ajaxUrl ||
			( typeof ajaxurl !== 'undefined'
				? ajaxurl
				: '/wp-admin/admin-ajax.php' );

		$.ajax( {
			url: ajaxUrl,
			type: 'POST',
			data: {
				action: 'eipsi_send_wave_reminder_manual',
				nonce: eipsiStudyDash.nonce,
				wave_id: waveId,
			},
			success( response ) {
				if ( response.success ) {
					showNotification(
						response.data.message || 'Recordatorios enviados',
						'success'
					);
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
	// EDIT STUDY MODAL
	// ===========================

	function openEditStudyModal( studyId ) {
		// Load study data
		loadStudyDataForEditing( studyId );

		// Show modal
		$( '#eipsi-edit-study-modal' ).fadeIn( 200 );
	}

	function closeEditStudyModal() {
		$( '#eipsi-edit-study-modal' ).fadeOut( 200 );
	}

	function loadStudyDataForEditing( studyId ) {
		// Show loading state
		$( '#edit-study-error, #edit-study-success' ).hide();

		// Get study data from dashboard
		const studyName = $( '#study-modal-title' )
			.text()
			.replace( 'Detalles: ', '' );
		const createdAt = $( '#study-created-at' ).text();
		const estimatedEnd = $( '#study-estimated-end' ).text();

		// Set form values
		$( '#edit-study-id' ).val( studyId );
		$( '#edit-study-name' ).val( studyName );

		// Check if time is unlimited
		if ( estimatedEnd === 'No definida' ) {
			$( '#edit-study-time-config' ).val( 'unlimited' );
			$( '#edit-study-dates-container' ).hide();
		} else {
			$( '#edit-study-time-config' ).val( 'limited' );
			$( '#edit-study-dates-container' ).show();
		}

		// Set dates if available
		if ( createdAt && createdAt !== 'N/A' ) {
			const startDate = new Date( createdAt );
			$( '#edit-study-start-date' ).val(
				formatDateForInput( startDate )
			);
		}

		if ( estimatedEnd && estimatedEnd !== 'No definida' ) {
			const endDate = new Date( estimatedEnd );
			$( '#edit-study-end-date' ).val( formatDateForInput( endDate ) );
		}
	}

	function formatDateForInput( date ) {
		const year = date.getFullYear();
		const month = String( date.getMonth() + 1 ).padStart( 2, '0' );
		const day = String( date.getDate() ).padStart( 2, '0' );
		return year + '-' + month + '-' + day;
	}

	// Handle time config change
	$( document ).on( 'change', '#edit-study-time-config', function () {
		const config = $( this ).val();
		if ( config === 'unlimited' ) {
			$( '#edit-study-dates-container' ).hide();
		} else {
			$( '#edit-study-dates-container' ).show();
		}
	} );

	// Handle form submission
	$( document ).on( 'submit', '#edit-study-form', function ( e ) {
		e.preventDefault();

		const studyId = $( '#edit-study-id' ).val();
		const studyName = $( '#edit-study-name' ).val();
		const studyDescription = $( '#edit-study-description' ).val();
		const timeConfig = $( '#edit-study-time-config' ).val();
		const startDate = $( '#edit-study-start-date' ).val();
		const endDate = $( '#edit-study-end-date' ).val();

		const $btn = $( '#edit-study-form button[type="submit"]' );
		const originalText = $btn.text();
		$btn.text( 'Guardando...' ).prop( 'disabled', true );

		const ajaxUrl =
			eipsiStudyDash.ajaxUrl ||
			( typeof ajaxurl !== 'undefined'
				? ajaxurl
				: '/wp-admin/admin-ajax.php' );

		$.ajax( {
			url: ajaxUrl,
			type: 'POST',
			data: {
				action: 'eipsi_save_study_settings',
				nonce: eipsiStudyDash.nonce,
				study_id: studyId,
				study_name: studyName,
				study_description: studyDescription,
				time_config: timeConfig,
				start_date: startDate,
				end_date: endDate,
			},
			success( response ) {
				if ( response.success ) {
					$( '#edit-study-success' )
						.text( response.data.message )
						.show();
					$( '#edit-study-error' ).hide();

					// Refresh dashboard
					loadStudyOverview( currentStudyId );

					// Close modal after 2 seconds
					setTimeout( function () {
						closeEditStudyModal();
					}, 2000 );
				} else {
					$( '#edit-study-error' )
						.text(
							response.data
								? response.data.message
								: 'Error al guardar cambios'
						)
						.show();
					$( '#edit-study-success' ).hide();
				}
			},
			error() {
				$( '#edit-study-error' )
					.text( 'Error de conexión al guardar cambios' )
					.show();
				$( '#edit-study-success' ).hide();
			},
			complete() {
				$btn.text( originalText ).prop( 'disabled', false );
			},
		} );
	} );

	// Close edit study modal
	$( document ).on(
		'click',
		'#eipsi-edit-study-modal .eipsi-modal-close',
		function () {
			closeEditStudyModal();
		}
	);

	// ===========================
	// HELPERS
	// ===========================

	function showConfirmationDialog( options ) {
		const settings = $.extend(
			{
				title: 'Confirmar acción',
				message: '',
				confirmText: 'Confirmar',
				cancelText: 'Cancelar',
				onConfirm: null,
				onCancel: null,
			},
			options
		);

		createDialog( settings );
	}

	function createDialog( settings ) {
		$( '.eipsi-dialog-overlay' ).remove();

		const dialogId = 'eipsi-dialog-' + Date.now();
		const safeTitle = escapeHtml( settings.title );
		const safeMessage = formatDialogMessage( settings.message );

		const $overlay = $(
			'<div class="eipsi-dialog-overlay" style="position:fixed;inset:0;background:rgba(0,0,0,0.55);display:flex;align-items:center;justify-content:center;z-index:1000000;"></div>'
		);

		const $dialog = $(
			'<div class="eipsi-dialog" role="dialog" aria-modal="true" aria-labelledby="' +
				dialogId +
				'-title" style="background:#fff;border-radius:8px;max-width:480px;width:90%;box-shadow:0 10px 30px rgba(0,0,0,0.25);padding:20px;">' +
				'<h3 id="' +
				dialogId +
				'-title" style="margin:0 0 12px;font-size:18px;">' +
				safeTitle +
				'</h3>' +
				'<div class="eipsi-dialog-message" style="margin-bottom:20px;color:#333;line-height:1.5;">' +
				safeMessage +
				'</div>' +
				'<div class="eipsi-dialog-actions" style="display:flex;gap:10px;justify-content:flex-end;">' +
				'<button type="button" class="button eipsi-dialog-cancel">' +
				escapeHtml( settings.cancelText ) +
				'</button>' +
				'<button type="button" class="button button-primary eipsi-dialog-confirm">' +
				escapeHtml( settings.confirmText ) +
				'</button>' +
				'</div>' +
				'</div>'
		);

		$overlay.append( $dialog );
		$( 'body' ).append( $overlay );

		const $confirmButton = $dialog.find( '.eipsi-dialog-confirm' );
		const $cancelButton = $dialog.find( '.eipsi-dialog-cancel' );

		function closeDialog() {
			$overlay.remove();
			$( document ).off( 'keydown', handleKeydown );
		}

		function handleConfirm() {
			if ( typeof settings.onConfirm === 'function' ) {
				settings.onConfirm();
			}
			closeDialog();
		}

		function handleCancel() {
			if ( typeof settings.onCancel === 'function' ) {
				settings.onCancel();
			}
			closeDialog();
		}

		function handleKeydown( event ) {
			if ( event.key === 'Escape' ) {
				handleCancel();
			} else if ( event.key === 'Enter' ) {
				handleConfirm();
			}
		}

		$confirmButton.on( 'click', handleConfirm );
		$cancelButton.on( 'click', handleCancel );
		$overlay.on( 'click', function ( event ) {
			if ( event.target === this ) {
				handleCancel();
			}
		} );

		$( document ).on( 'keydown', handleKeydown );
		$confirmButton.trigger( 'focus' );
	}

	function formatDialogMessage( message ) {
		const safeMessage = escapeHtml( message || '' );
		return safeMessage.replace( /\n/g, '<br>' );
	}

	function buildStudyShortcode( studyId, studyCode ) {
		if ( ! studyId && ! studyCode ) {
			return '';
		}

		// PREFER study_code for security (v1.6.0+)
		if ( studyCode ) {
			return '[eipsi_longitudinal_study study_code="' + studyCode + '"]';
		}

		// BACKWARD COMPATIBILITY: Use numeric ID (less secure)
		return '[eipsi_longitudinal_study id="' + studyId + '"]';
	}

	function copyStudyShortcode() {
		const shortcode = $( '#study-shortcode-display' ).text();
		if ( ! shortcode ) {
			showNotification( 'No hay shortcode disponible', 'error' );
			return;
		}

		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard
				.writeText( shortcode )
				.then( function () {
					showNotification(
						'Shortcode copiado al portapapeles',
						'success'
					);
				} )
				.catch( function () {
					fallbackCopyShortcode( shortcode );
				} );
			return;
		}

		fallbackCopyShortcode( shortcode );
	}

	function fallbackCopyShortcode( shortcode ) {
		const textarea = document.createElement( 'textarea' );
		textarea.value = shortcode;
		textarea.setAttribute( 'readonly', '' );
		textarea.style.position = 'absolute';
		textarea.style.left = '-9999px';
		document.body.appendChild( textarea );
		textarea.select();
		document.execCommand( 'copy' );
		document.body.removeChild( textarea );
		showNotification( 'Shortcode copiado al portapapeles', 'success' );
	}

	function getStatusBadge( status ) {
		const badges = {
			active: '<span class="eipsi-badge badge-active">Activo</span>',
			completed:
				'<span class="eipsi-badge badge-completed">Completado</span>',
			paused: '<span class="eipsi-badge badge-paused">En Pausa</span>',
			draft: '<span class="eipsi-badge badge-draft">Borrador</span>',
		};
		return (
			badges[ status ] ||
			'<span class="eipsi-badge">' + status + '</span>'
		);
	}

	function formatDate( dateStr ) {
		if ( ! dateStr ) {
			return 'N/A';
		}
		const date = new Date( dateStr );
		return date.toLocaleDateString( 'es-ES', {
			year: 'numeric',
			month: 'short',
			day: 'numeric',
		} );
	}

	function formatDateTime( dateStr ) {
		if ( ! dateStr ) {
			return 'N/A';
		}
		const date = new Date( dateStr );
		return date.toLocaleDateString( 'es-ES', {
			year: 'numeric',
			month: 'short',
			day: 'numeric',
			hour: '2-digit',
			minute: '2-digit',
		} );
	}

	function showNotification( message, type ) {
		$( '.eipsi-notification' ).remove();

		const cssClass = type === 'success' ? 'notice-success' : 'notice-error';
		const icon = type === 'success' ? '✓' : '✗';

		const $notification = $(
			'<div class="eipsi-notification ' +
				cssClass +
				'" style="position:fixed;top:50px;right:20px;z-index:999999;padding:12px 20px;border-radius:4px;box-shadow:0 2px 8px rgba(0,0,0,0.15);">' +
				'<strong>' +
				icon +
				'</strong> ' +
				message +
				'</div>'
		);

		$( 'body' ).append( $notification );

		setTimeout( function () {
			$notification.fadeOut( function () {
				$( this ).remove();
			} );
		}, 4000 );
	}

	function showError( message ) {
		$( '#waves-container' ).html(
			'<div style="color:#d63638;padding:20px;text-align:center;"><strong>Error:</strong> ' +
				message +
				'</div>'
		);
		$( '#eipsi-dashboard-content' ).show();
	}

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
