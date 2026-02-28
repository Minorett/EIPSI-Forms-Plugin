#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Fix the corrupted section in waves-manager.js"""

content = open('admin/js/waves-manager.js', 'r', encoding='utf-8').read()

correct_section = r'''function initEmailPreviewModal() {
	$( document ).on( 'click', '.eipsi-send-reminder-btn', function () {
		const waveId = $( this ).data( 'wave-id' );
		openEmailPreviewModal( waveId, 'reminder' );
	} );

	$( document ).on( 'click', '.eipsi-send-manual-reminder-btn', function () {
		const waveId = $( this ).data( 'wave-id' );
		openEmailPreviewModal( waveId, 'manual' );
	} );

	$( document ).on( 'change', '#email-preview-type, #email-preview-participant', function () {
		loadEmailPreview();
	} );

	$( document ).on( 'click', '#email-preview-refresh-btn', function () {
		loadEmailPreview();
	} );
}

function openEmailPreviewModal( waveId, emailType ) {
	emailPreviewWaveId = waveId;
	$( '#email-preview-wave-id' ).val( waveId );
	$( '#email-preview-type' ).val( emailType || 'reminder' );
	$( '#email-preview-participant' ).val( '' );
	$( '#email-preview-subject' ).text( '' );
	$( '#email-preview-body' ).html( '<div style="text-align: center; padding: 40px; color: #666;"><span class="spinner is-active"></span><p>Cargando vista previa...</p></div>' );
	loadParticipantsForPreview();
	$( '#eipsi-email-preview-modal' ).fadeIn( 200 );
}

function loadParticipantsForPreview() {
	$.ajax( {
		url: eipsiWavesManagerData.ajaxUrl || ajaxurl,
		type: 'GET',
		data: {
			action: 'eipsi_get_pending_participants',
			nonce: eipsiWavesManagerData.wavesNonce,
			study_id: eipsiWavesManagerData.studyId,
			wave_id: emailPreviewWaveId,
		},
		success( response ) {
			if ( response.success && response.data ) {
				const $select = $( '#email-preview-participant' );
				$select.find( 'option:not(:first)' ).remove();
				response.data.forEach( function ( p ) {
					const fullName = ( p.first_name || '' ) + ' ' + ( p.last_name || '' );
					$select.append( '<option value="' + p.id + '">' + escapeHtml( fullName.trim() || p.email ) + ' (' + escapeHtml( p.email ) + ')</option>' );
				} );
			}
		},
	} );
}

function loadEmailPreview() {
	const waveId = emailPreviewWaveId;
	const emailType = $( '#email-preview-type' ).val();
	const participantId = $( '#email-preview-participant' ).val();

	$( '#email-preview-body' ).html( '<div style="text-align: center; padding: 40px; color: #666;"><span class="spinner is-active"></span><p>Cargando vista previa...</p></div>' );

	$.ajax( {
		url: eipsiWavesManagerData.ajaxUrl || ajaxurl,
		type: 'GET',
		data: {
			action: 'eipsi_get_wave_email_preview',
			nonce: eipsiWavesManagerData.wavesNonce,
			wave_id: waveId,
			type: emailType,
			participant_id: participantId,
		},
		success( response ) {
			if ( response.success && response.data ) {
				const data = response.data;
				$( '#email-preview-subject' ).text( data.subject || '' );
				$( '#email-preview-body' ).html( data.content || '' );
				if ( data.is_sample ) {
					$( '#email-preview-body' ).prepend( '<div style="background: #e8f4f8; padding: 8px 12px; margin-bottom: 15px; border-radius: 4px; font-size: 0.85em; color: #0066cc;"><strong>Vista previa de muestra</strong> - Los datos del participante se sustituiran al enviar.</div>' );
				}
			} else {
				$( '#email-preview-body' ).html( '<div style="text-align: center; padding: 40px; color: #d63638;">Error al cargar la vista previa</div>' );
			}
		},
		error() {
			$( '#email-preview-body' ).html( '<div style="text-align: center; padding: 40px; color: #d63638;">Error de conexion</div>' );
		},
	} );
}

// ===========================
// VALIDATION MODAL
// ===========================

function initValidationModal() {
	$( document ).on( 'click', '#validation-confirm-save-btn', function () {
		$( '#eipsi-validation-warning-modal' ).fadeOut( 200 );
		pendingValidationWarnings = null;
		$( '#eipsi-wave-form' ).data( 'skip-validation', true );
		$( '#eipsi-wave-form' ).submit();
	} );
}

function validateWaveDatesOnChange() {
	const studyId = eipsiWavesManagerData.studyId;
	const waveId = $( '#wave_id' ).val();
	const waveIndex = $( '#wave_index' ).val();
	const startDate = $( '#start_date' ).val();
	const dueDate = $( '#due_date' ).val();

	if ( ! studyId ) {
		return;
	}
	$.ajax( {
		url: eipsiWavesManagerData.ajaxUrl || ajaxurl,
		type: 'POST',
		data: {
			action: 'eipsi_validate_wave_dates',
			nonce: eipsiWavesManagerData.wavesNonce,
			study_id: studyId,
			wave_id: waveId,
			wave_index: waveIndex,
			start_date: startDate,
			due_date: dueDate,
		},
		success( response ) {
			if ( response.success && response.data ) {
				const validation = response.data;
				if ( validation.errors && validation.errors.length > 0 ) {
					showNotification( validation.errors[ 0 ], 'error' );
				}
				if ( validation.warnings && validation.warnings.length > 0 ) {
					pendingValidationWarnings = validation.warnings;
				} else {
					pendingValidationWarnings = null;
				}
			}
		},
	} );
}

function checkAndShowValidationWarnings() {
	if ( pendingValidationWarnings && pendingValidationWarnings.length > 0 ) {
		showValidationWarningModal( pendingValidationWarnings );
		return true;
	}
	return false;
}

function showValidationWarningModal( warnings ) {
	const $list = $( '#validation-warnings-list' );
	$list.html( '<div class="notice notice-warning" style="margin: 0;"><p><strong>Se detectaron las siguientes advertencias:</strong></p><ul style="margin: 10px 0;">' + warnings.map( function ( w ) { return '<li>' + escapeHtml( w ) + '</li>'; } ).join( '' ) + '</ul><p>Deseas guardar de todos modos o revisar las fechas?</p></div>' );
	$( '#eipsi-validation-warning-modal' ).fadeIn( 200 );
}'''

start_marker = 'function initEmailPreviewModal() {'
end_marker = '} )( window.jQuery );'

start_idx = content.find(start_marker)
end_idx = content.find(end_marker)

if start_idx == -1 or end_idx == -1:
    print(f'Markers not found: start={start_idx}, end={end_idx}')
else:
    new_content = content[:start_idx] + correct_section + '\n' + content[end_idx:]
    open('admin/js/waves-manager.js', 'w', encoding='utf-8').write(new_content)
    print(f'Replaced. New file size: {len(new_content)} chars')
