/**
 * EIPSI Forms - Study Dashboard
 * Handles Study Overview Modal, Wave Details, and Email Logs
 *
 * @package EIPSI_Forms
 * @since 1.4.3
 */

/* global eipsiStudyDashboardData, ajaxurl */

( function ( $ ) {
	'use strict';

	// ===========================
	// STATE
	// ===========================

	let currentStudyId = 0;
	let isLoading = false;

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
			if ( ! studyId ) return;

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

		// View Email Logs
		$( document ).on( 'click', '#view-email-logs', function () {
			if ( currentStudyId ) {
				loadEmailLogs( currentStudyId );
				$( '#eipsi-email-logs-modal' ).fadeIn( 200 );
			}
		} );

		// Close Email Logs Modal
		$( document ).on( 'click', '#eipsi-email-logs-modal .eipsi-modal-close', function () {
			$( '#eipsi-email-logs-modal' ).fadeOut( 200 );
		} );

		// Close Extend Deadline Modal
		$( document ).on( 'click', '#eipsi-extend-deadline-modal .eipsi-modal-close', function () {
			$( '#eipsi-extend-deadline-modal' ).fadeOut( 200 );
		} );

		// Extend Deadline Form Submit
		$( document ).on( 'submit', '#extend-deadline-form', function ( e ) {
			e.preventDefault();
			const waveId = $( '#extend-wave-id' ).val();
			const newDeadline = $( '#new-deadline-date' ).val();
			if ( waveId && newDeadline ) {
				extendWaveDeadline( waveId, newDeadline );
			}
		} );

		// Quick Action: Edit Study
		$( document ).on( 'click', '#action-edit-study', function () {
			if ( currentStudyId ) {
				window.location.href =
					'?page=eipsi-results&tab=waves-manager&study_id=' + currentStudyId;
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
				alert(
					'Funcionalidad de gestión de participantes disponible en la pestaña Waves Manager'
				);
			}
		} );

		// Quick Action: Close Study
		$( document ).on( 'click', '#action-close-study', function () {
			if ( currentStudyId ) {
				if (
					confirm(
						'¿Estás seguro de cerrar este estudio? Esta acción requiere confirmación adicional.'
					)
				) {
					closeStudyModal();
				}
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
	}

	function closeDashboardModal() {
		$( '#eipsi-study-dashboard-modal' ).fadeOut( 200 );
		currentStudyId = 0;
	}

	function closeStudyModal() {
		// Redirect to waves manager where anonymize button is available
		window.location.href =
			'?page=eipsi-results&tab=waves-manager&study_id=' + currentStudyId;
	}

	// ===========================
	// DATA LOADING
	// ===========================

	function loadStudyOverview( studyId ) {
		if ( isLoading ) return;
		isLoading = true;

		$( '#eipsi-dashboard-loading' ).show();
		$( '#eipsi-dashboard-content' ).hide();

		$.ajax( {
			url:
				eipsiStudyDashboardData.ajaxUrl ||
				( typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php' ),
			type: 'GET',
			data: {
				action: 'eipsi_get_study_overview',
				nonce: eipsiStudyDashboardData.nonce,
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
			$( '#study-created-at' ).text( formatDate( data.general.created_at ) );
			$( '#study-estimated-end' ).text(
				data.general.estimated_end_date
					? formatDate( data.general.estimated_end_date )
					: 'No definida'
			);
			$( '#study-id-display' ).text( data.general.study_code );
		}

		// Participants Card
		if ( data.participants ) {
			const total = data.participants.total || 0;
			const completed = data.participants.completed || 0;
			const inProgress = data.participants.in_progress || 0;
			const inactive = data.participants.inactive || 0;

			$( '#total-participants' ).text( total );

			// Progress bars
			const completedPct = total > 0 ? Math.round( ( completed / total ) * 100 ) : 0;
			const inProgressPct = total > 0 ? Math.round( ( inProgress / total ) * 100 ) : 0;
			const inactivePct = total > 0 ? Math.round( ( inactive / total ) * 100 ) : 0;

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
			const progressColor =
				wave.progress >= 75 ? 'green' : wave.progress >= 50 ? 'blue' : 'orange';

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

		$.ajax( {
			url:
				eipsiStudyDashboardData.ajaxUrl ||
				( typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php' ),
			type: 'GET',
			data: {
				action: 'eipsi_get_study_email_logs',
				nonce: eipsiStudyDashboardData.nonce,
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

		$.ajax( {
			url:
				eipsiStudyDashboardData.ajaxUrl ||
				( typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php' ),
			type: 'POST',
			data: {
				action: 'eipsi_extend_wave_deadline',
				nonce: eipsiStudyDashboardData.nonce,
				wave_id: waveId,
				new_deadline: newDeadline + ' 23:59:59', // Add time to date
			},
			success( response ) {
				if ( response.success ) {
					showNotification( 'Plazo extendido exitosamente', 'success' );
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
		if (
			! confirm(
				'¿Enviar recordatorios a todos los participantes pendientes de esta toma?'
			)
		) {
			return;
		}

		const $btn = $( '.send-reminder[data-wave-id="' + waveId + '"]' );
		const originalText = $btn.text();
		$btn.text( 'Enviando...' ).prop( 'disabled', true );

		$.ajax( {
			url:
				eipsiStudyDashboardData.ajaxUrl ||
				( typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php' ),
			type: 'POST',
			data: {
				action: 'eipsi_send_wave_reminder_manual',
				nonce: eipsiStudyDashboardData.nonce,
				wave_id: waveId,
			},
			success( response ) {
				if ( response.success ) {
					showNotification( response.data.message || 'Recordatorios enviados', 'success' );
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
	// HELPERS
	// ===========================

	function getStatusBadge( status ) {
		const badges = {
			active: '<span class="eipsi-badge badge-active">Activo</span>',
			completed: '<span class="eipsi-badge badge-completed">Completado</span>',
			paused: '<span class="eipsi-badge badge-paused">En Pausa</span>',
			draft: '<span class="eipsi-badge badge-draft">Borrador</span>',
		};
		return badges[ status ] || '<span class="eipsi-badge">' + status + '</span>';
	}

	function formatDate( dateStr ) {
		if ( ! dateStr ) return 'N/A';
		const date = new Date( dateStr );
		return date.toLocaleDateString( 'es-ES', {
			year: 'numeric',
			month: 'short',
			day: 'numeric',
		} );
	}

	function formatDateTime( dateStr ) {
		if ( ! dateStr ) return 'N/A';
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
		if ( ! unsafe ) return '';
		return unsafe
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			.replace( /"/g, '&quot;' )
			.replace( /'/g, '&#039;' );
	}
} )( window.jQuery );
