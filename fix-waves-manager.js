#!/usr/bin/env node
/**
 * Fix corrupted section in admin/js/waves-manager.js
 * The Email Preview Modal and Validation Modal sections had their line prefixes stripped.
 */
const fs = require( 'fs' );

const content = fs.readFileSync( 'admin/js/waves-manager.js', 'utf8' );
const lines = content.split( '\n' );

// Find where the corruption starts (line 1984 = index 1983) and ends (last line before "} )( window.jQuery )" )
// Strategy: replace lines 1983 to (end-2) with correct version

const jQueryClose = '} )( window.jQuery );';
const jQueryIdx = lines.findIndex( ( l ) => l.trim() === jQueryClose );
console.log( 'jQuery close at line:', jQueryIdx + 1 );

// Lines 0-1982: good (keep)
// Lines jQueryIdx to end: good (keep)
// Lines 1983 to jQueryIdx-1: corrupted (replace)

const correctSection = `function initEmailPreviewModal() {
\t$( document ).on( 'click', '.eipsi-send-reminder-btn', function () {
\t\tconst waveId = $( this ).data( 'wave-id' );
\t\topenEmailPreviewModal( waveId, 'reminder' );
\t} );

\t$( document ).on( 'click', '.eipsi-send-manual-reminder-btn', function () {
\t\tconst waveId = $( this ).data( 'wave-id' );
\t\topenEmailPreviewModal( waveId, 'manual' );
\t} );

\t$( document ).on( 'change', '#email-preview-type, #email-preview-participant', function () {
\t\tloadEmailPreview();
\t} );

\t$( document ).on( 'click', '#email-preview-refresh-btn', function () {
\t\tloadEmailPreview();
\t} );
}

function openEmailPreviewModal( waveId, emailType ) {
\temailPreviewWaveId = waveId;
\t$( '#email-preview-wave-id' ).val( waveId );
\t$( '#email-preview-type' ).val( emailType || 'reminder' );
\t$( '#email-preview-participant' ).val( '' );
\t$( '#email-preview-subject' ).text( '' );
\t$( '#email-preview-body' ).html( '<div style="text-align: center; padding: 40px; color: #666;"><span class="spinner is-active"></span><p>Cargando vista previa...</p></div>' );
\tloadParticipantsForPreview();
\t$( '#eipsi-email-preview-modal' ).fadeIn( 200 );
}

function loadParticipantsForPreview() {
\t$.ajax( {
\t\turl: eipsiWavesManagerData.ajaxUrl || ajaxurl,
\t\ttype: 'GET',
\t\tdata: {
\t\t\taction: 'eipsi_get_pending_participants',
\t\t\tnonce: eipsiWavesManagerData.wavesNonce,
\t\t\tstudy_id: eipsiWavesManagerData.studyId,
\t\t\twave_id: emailPreviewWaveId,
\t\t},
\t\tsuccess( response ) {
\t\t\tif ( response.success && response.data ) {
\t\t\t\tconst $select = $( '#email-preview-participant' );
\t\t\t\t$select.find( 'option:not(:first)' ).remove();
\t\t\t\tresponse.data.forEach( function ( p ) {
\t\t\t\t\tconst fullName = ( p.first_name || '' ) + ' ' + ( p.last_name || '' );
\t\t\t\t\t$select.append( '<option value="' + p.id + '">' + escapeHtml( fullName.trim() || p.email ) + ' (' + escapeHtml( p.email ) + ')</option>' );
\t\t\t\t} );
\t\t\t}
\t\t},
\t} );
}

function loadEmailPreview() {
\tconst waveId = emailPreviewWaveId;
\tconst emailType = $( '#email-preview-type' ).val();
\tconst participantId = $( '#email-preview-participant' ).val();

\t$( '#email-preview-body' ).html( '<div style="text-align: center; padding: 40px; color: #666;"><span class="spinner is-active"></span><p>Cargando vista previa...</p></div>' );

\t$.ajax( {
\t\turl: eipsiWavesManagerData.ajaxUrl || ajaxurl,
\t\ttype: 'GET',
\t\tdata: {
\t\t\taction: 'eipsi_get_wave_email_preview',
\t\t\tnonce: eipsiWavesManagerData.wavesNonce,
\t\t\twave_id: waveId,
\t\t\ttype: emailType,
\t\t\tparticipant_id: participantId,
\t\t},
\t\tsuccess( response ) {
\t\t\tif ( response.success && response.data ) {
\t\t\t\tconst data = response.data;
\t\t\t\t$( '#email-preview-subject' ).text( data.subject || '' );
\t\t\t\t$( '#email-preview-body' ).html( data.content || '' );
\t\t\t\tif ( data.is_sample ) {
\t\t\t\t\t$( '#email-preview-body' ).prepend( '<div style="background: #e8f4f8; padding: 8px 12px; margin-bottom: 15px; border-radius: 4px; font-size: 0.85em; color: #0066cc;"><strong>Vista previa de muestra</strong> - Los datos del participante se sustituiran al enviar.</div>' );
\t\t\t\t}
\t\t\t} else {
\t\t\t\t$( '#email-preview-body' ).html( '<div style="text-align: center; padding: 40px; color: #d63638;">Error al cargar la vista previa</div>' );
\t\t\t}
\t\t},
\t\terror() {
\t\t\t$( '#email-preview-body' ).html( '<div style="text-align: center; padding: 40px; color: #d63638;">Error de conexion</div>' );
\t\t},
\t} );
}

// ===========================
// VALIDATION MODAL
// ===========================

function initValidationModal() {
\t$( document ).on( 'click', '#validation-confirm-save-btn', function () {
\t\t$( '#eipsi-validation-warning-modal' ).fadeOut( 200 );
\t\tpendingValidationWarnings = null;
\t\t$( '#eipsi-wave-form' ).data( 'skip-validation', true );
\t\t$( '#eipsi-wave-form' ).submit();
\t} );
}

function validateWaveDatesOnChange() {
\tconst studyId = eipsiWavesManagerData.studyId;
\tconst waveId = $( '#wave_id' ).val();
\tconst waveIndex = $( '#wave_index' ).val();
\tconst startDate = $( '#start_date' ).val();
\tconst dueDate = $( '#due_date' ).val();

\tif ( ! studyId ) {
\t\treturn;
\t}
\t$.ajax( {
\t\turl: eipsiWavesManagerData.ajaxUrl || ajaxurl,
\t\ttype: 'POST',
\t\tdata: {
\t\t\taction: 'eipsi_validate_wave_dates',
\t\t\tnonce: eipsiWavesManagerData.wavesNonce,
\t\t\tstudy_id: studyId,
\t\t\twave_id: waveId,
\t\t\twave_index: waveIndex,
\t\t\tstart_date: startDate,
\t\t\tdue_date: dueDate,
\t\t},
\t\tsuccess( response ) {
\t\t\tif ( response.success && response.data ) {
\t\t\t\tconst validation = response.data;
\t\t\t\tif ( validation.errors && validation.errors.length > 0 ) {
\t\t\t\t\tshowNotification( validation.errors[ 0 ], 'error' );
\t\t\t\t}
\t\t\t\tif ( validation.warnings && validation.warnings.length > 0 ) {
\t\t\t\t\tpendingValidationWarnings = validation.warnings;
\t\t\t\t} else {
\t\t\t\t\tpendingValidationWarnings = null;
\t\t\t\t}
\t\t\t}
\t\t},
\t} );
}

function checkAndShowValidationWarnings() {
\tif ( pendingValidationWarnings && pendingValidationWarnings.length > 0 ) {
\t\tshowValidationWarningModal( pendingValidationWarnings );
\t\treturn true;
\t}
\treturn false;
}

function showValidationWarningModal( warnings ) {
\tconst $list = $( '#validation-warnings-list' );
\t$list.html( '<div class="notice notice-warning" style="margin: 0;"><p><strong>Se detectaron las siguientes advertencias:</strong></p><ul style="margin: 10px 0;">' + warnings.map( function ( w ) { return '<li>' + escapeHtml( w ) + '</li>'; } ).join( '' ) + '</ul><p>Deseas guardar de todos modos o revisar las fechas?</p></div>' );
\t$( '#eipsi-validation-warning-modal' ).fadeIn( 200 );
}
`;

// The corruption starts at line index 1983 (1-based: 1984)
const CORRUPT_START = 1983; // 0-indexed
const goodStart = lines.slice( 0, CORRUPT_START );
const goodEnd = lines.slice( jQueryIdx );

const newLines = [ ...goodStart, ...correctSection.split( '\n' ), ...goodEnd ];
const newContent = newLines.join( '\n' );

fs.writeFileSync( 'admin/js/waves-manager.js', newContent, 'utf8' );
console.log(
	`Done. Lines: ${ newLines.length }, size: ${ newContent.length }`
);
