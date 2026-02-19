/**
 * Email Log & Dropout Management JavaScript
 *
 * @param $
 * @package
 * @since 1.5.0
 */

/* global eipsi, ajaxurl */
/* eslint-disable no-alert */

( function ( $ ) {
    'use strict';

    // === State ===
    const state = {
        currentPage: 1,
        itemsPerPage: 20,
        totalItems: 0,
        filters: {
            type: '',
            status: '',
            dateFrom: '',
            dateTo: '',
        },
        surveyId: 0,
    };

    // === DOM Elements ===
    const $emailLogBody = $( '#eipsi-email-log-body' );
    const $dropoutBody = $( '#eipsi-dropout-body' );
    const $prevPageBtn = $( '#prev-email-page' );
    const $nextPageBtn = $( '#next-email-page' );
    const $pageInfo = $( '#email-page-info' );

    // === Initialization ===
    $( document ).ready( function () {
        // Get survey_id from URL
        const urlParams = new URLSearchParams( window.location.search );
        state.surveyId = parseInt( urlParams.get( 'survey_id' ) ) || 0;

        // Load initial data
        loadEmailLogs();
        loadAtRiskParticipants();

        // Setup event listeners
        setupEventListeners();
    } );

    // === Event Listeners ===
    function setupEventListeners() {
        // Sub tabs
        $( '.eipsi-sub-tab-button' ).on( 'click', function () {
            const tab = $( this ).data( 'tab' );

            $( '.eipsi-sub-tab-button' ).removeClass( 'active' );
            $( this ).addClass( 'active' );

            $( '.eipsi-tab-content' ).removeClass( 'active' );
            $( '#eipsi-' + tab + '-section' ).addClass( 'active' );
        } );

        // Email log filters
        $( '#apply-email-filters' ).on( 'click', function () {
            state.filters.type = $( '#filter-email-type' ).val();
            state.filters.status = $( '#filter-email-status' ).val();
            state.filters.dateFrom = $( '#filter-date-from' ).val();
            state.filters.dateTo = $( '#filter-date-to' ).val();
            state.currentPage = 1;
            loadEmailLogs();
        } );

        $( '#reset-email-filters' ).on( 'click', function () {
            $( '#filter-email-type' ).val( '' );
            $( '#filter-email-status' ).val( '' );
            $( '#filter-date-from' ).val( '' );
            $( '#filter-date-to' ).val( '' );
            state.filters = { type: '', status: '', dateFrom: '', dateTo: '' };
            state.currentPage = 1;
            loadEmailLogs();
        } );

        // Export CSV
        $( '#export-email-logs' ).on( 'click', function () {
            exportEmailLogsCSV();
        } );

        // Pagination
        $prevPageBtn.on( 'click', function () {
            if ( state.currentPage > 1 ) {
                state.currentPage--;
                loadEmailLogs();
            }
        } );

        $nextPageBtn.on( 'click', function () {
            const maxPage = Math.ceil( state.totalItems / state.itemsPerPage );
            if ( state.currentPage < maxPage ) {
                state.currentPage++;
                loadEmailLogs();
            }
        } );

        // Dropout filters
        $( '#apply-dropout-filters' ).on( 'click', function () {
            loadAtRiskParticipants();
        } );

        // Select all at risk
        $( '#select-all-at-risk' ).on( 'change', function () {
            $( '.eipsi-at-risk-checkbox' ).prop(
                'checked',
                $( this ).prop( 'checked' )
            );
            updateBulkActionState();
        } );

        // Individual checkboxes
        $( document ).on( 'change', '.eipsi-at-risk-checkbox', function () {
            updateBulkActionState();
        } );

        // Bulk action apply
        $( '#apply-bulk-action' ).on( 'click', function () {
            const action = $( '#bulk-action-select' ).val();
            if ( ! action ) {
                return;
            }

            const selectedParticipants = [];
            $( '.eipsi-at-risk-checkbox:checked' ).each( function () {
                selectedParticipants.push( $( this ).data( 'participant-id' ) );
            } );

            if ( selectedParticipants.length === 0 ) {
                alert(
                    eipsi.i18n.selectParticipants ||
                        'Por favor selecciona al menos un participante'
                );
                return;
            }

            executeBulkAction( action, selectedParticipants );
        } );

        // Modal close
        $( document ).on(
            'click',
            '.eipsi-modal-close, .eipsi-modal-cancel',
            function () {
                $( '.eipsi-modal' ).fadeOut();
            }
        );

        $( document ).on( 'click', '.eipsi-modal', function ( e ) {
            if ( e.target === this ) {
                $( this ).fadeOut();
            }
        } );

        // Extend deadline confirm
        $( '#confirm-extend' ).on( 'click', function () {
            const assignmentId = $( '#extend-assignment-id' ).val();
            const days = parseInt( $( '#extend-days' ).val() ) || 7;
            extendWaveDeadline( assignmentId, days );
        } );
    }

    // === Email Log Functions ===

    function loadEmailLogs() {
        $emailLogBody.html( `
            <tr>
                <td colspan="6" style="text-align: center; padding: 40px;">
                    <span class="spinner is-active" style="display: inline-block; vertical-align: middle;"></span>
                    <span style="margin-left: 10px;">${
                        eipsi.i18n.loading || 'Cargando...'
                    }</span>
                </td>
            </tr>
        ` );

        const offset = ( state.currentPage - 1 ) * state.itemsPerPage;

        $.ajax( {
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'eipsi_get_email_logs',
                nonce: eipsi.nonce,
                survey_id: state.surveyId,
                filters: state.filters,
                limit: state.itemsPerPage,
                offset,
            },
            success( response ) {
                if ( response.success ) {
                    renderEmailLogs( response.data.logs, response.data.total );
                } else {
                    showError( response.data || eipsi.i18n.errorLoading );
                }
            },
            error() {
                showError( eipsi.i18n.connectionError || 'Error de conexión' );
            },
        } );
    }

    function renderEmailLogs( logs, total ) {
        state.totalItems = total;

        if ( logs.length === 0 ) {
            $emailLogBody.html( `
                <tr>
                    <td colspan="6" style="text-align: center; padding: 40px;">
                        <p style="color: #646970;">${
                            eipsi.i18n.noEmails || 'No se encontraron emails'
                        }</p>
                    </td>
                </tr>
            ` );
            updatePagination( 0 );
            return;
        }

        let html = '';
        logs.forEach( ( log ) => {
            const dateObj = new Date( log.sent_at );
            const formattedDate = dateObj.toLocaleDateString( 'es-ES', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
            } );
            const formattedTime = dateObj.toLocaleTimeString( 'es-ES', {
                hour: '2-digit',
                minute: '2-digit',
            } );

            const typeLabel = getTypeLabel( log.email_type );
            const statusBadge = getStatusBadge( log.status );

            html += `
                <tr>
                    <td>
                        <div style="font-weight: 500;">${ formattedDate }</div>
                        <div style="font-size: 12px; color: #646970;">${ formattedTime }</div>
                    </td>
                    <td>
                        <span class="eipsi-email-type ${
                            log.email_type
                        }">${ typeLabel }</span>
                    </td>
                    <td>
                        <strong>${ escapeHtml(
                            log.participant_name || 'N/A'
                        ) }</strong>
                    </td>
                    <td>
                        <span style="font-size: 13px;">${ escapeHtml(
                            log.recipient_email
                        ) }</span>
                    </td>
                    <td>${ statusBadge }</td>
                    <td>
                        <div class="eipsi-action-buttons">
                            <button class="button button-small" onclick="viewEmailDetails(${
                                log.id
                            })">
                                👁️ ${ eipsi.i18n.view || 'Ver' }
                            </button>
                            ${
                                log.status !== 'sent'
                                    ? `
                                <button class="button button-small button-primary" onclick="resendEmail(${
                                    log.id
                                })">
                                    📤 ${ eipsi.i18n.resend || 'Reenviar' }
                                </button>
                            `
                                    : ''
                            }
                        </div>
                    </td>
                </tr>
            `;
        } );

        $emailLogBody.html( html );
        updatePagination( total );
    }

    function updatePagination( total ) {
        const maxPage = Math.ceil( total / state.itemsPerPage );

        $pageInfo.text(
            `${ eipsi.i18n.page || 'Página' } ${ state.currentPage } ${
                eipsi.i18n.of || 'de'
            } ${ maxPage || 1 }`
        );

        $prevPageBtn.prop( 'disabled', state.currentPage <= 1 );
        $nextPageBtn.prop(
            'disabled',
            state.currentPage >= maxPage || maxPage === 0
        );
    }

    function getTypeLabel( type ) {
        const labels = {
            welcome: 'Bienvenida',
            reminder: 'Recordatorio',
            confirmation: 'Confirmación',
            recovery: 'Recuperación',
        };
        return labels[ type ] || type;
    }

    function getStatusBadge( status ) {
        if ( status === 'sent' ) {
            return '<span class="eipsi-email-status sent">✅ Enviado</span>';
        } else if ( status === 'failed' ) {
            return '<span class="eipsi-email-status failed">❌ Fallido</span>';
        }
        return `<span class="eipsi-email-status">${ status }</span>`;
    }

    // === Email Detail View ===

    window.viewEmailDetails = function ( emailLogId ) {
        $.ajax( {
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'eipsi_get_email_details',
                nonce: eipsi.nonce,
                email_log_id: emailLogId,
            },
            success( response ) {
                if ( response.success ) {
                    showEmailDetailsModal( response.data );
                } else {
                    alert( response.data || eipsi.i18n.errorLoading );
                }
            },
            error() {
                alert( eipsi.i18n.connectionError || 'Error de conexión' );
            },
        } );
    };

    function showEmailDetailsModal( email ) {
        const typeLabel = getTypeLabel( email.email_type );
        const statusBadge = getStatusBadge( email.status );

        const html = `
            <div class="eipsi-email-detail-row">
                <span class="eipsi-email-detail-label">${
                    eipsi.i18n.type || 'Tipo'
                }:</span>
                <span class="eipsi-email-detail-value"><span class="eipsi-email-type ${
                    email.email_type
                }">${ typeLabel }</span></span>
            </div>
            <div class="eipsi-email-detail-row">
                <span class="eipsi-email-detail-label">${
                    eipsi.i18n.to || 'Para'
                }:</span>
                <span class="eipsi-email-detail-value">${ escapeHtml(
                    email.recipient_email
                ) }</span>
            </div>
            <div class="eipsi-email-detail-row">
                <span class="eipsi-email-detail-label">${
                    eipsi.i18n.status || 'Estado'
                }:</span>
                <span class="eipsi-email-detail-value">${ statusBadge }</span>
            </div>
            <div class="eipsi-email-detail-row">
                <span class="eipsi-email-detail-label">${
                    eipsi.i18n.sentAt || 'Enviado'
                }:</span>
                <span class="eipsi-email-detail-value">${ escapeHtml(
                    email.sent_at
                ) }</span>
            </div>
            ${
                email.error_message
                    ? `
                <div class="eipsi-email-detail-row">
                    <span class="eipsi-email-detail-label">${
                        eipsi.i18n.error || 'Error'
                    }:</span>
                    <span class="eipsi-email-detail-value" style="color: #d63638;">${ escapeHtml(
                        email.error_message
                    ) }</span>
                </div>
            `
                    : ''
            }
            ${
                email.subject
                    ? `
                <div class="eipsi-email-detail-row">
                    <span class="eipsi-email-detail-label">${
                        eipsi.i18n.subject || 'Asunto'
                    }:</span>
                    <span class="eipsi-email-detail-value">${ escapeHtml(
                        email.subject
                    ) }</span>
                </div>
            `
                    : ''
            }
            ${
                email.content
                    ? `
                <div class="eipsi-email-detail-row">
                    <span class="eipsi-email-detail-label">${
                        eipsi.i18n.content || 'Contenido'
                    }:</span>
                    <div class="eipsi-email-content-preview">${
                        email.content
                    }</div>
                </div>
            `
                    : ''
            }
        `;

        $( '#eipsi-email-details-body' ).html( html );
        $( '#eipsi-email-details-modal' ).fadeIn();
    }

    // === Resend Email ===

    window.resendEmail = function ( emailLogId ) {
        if (
            ! confirm(
                eipsi.i18n.confirmResend || '¿Deseas reenviar este email?'
            )
        ) {
            return;
        }

        const $btn = $( `button[onclick="resendEmail(${ emailLogId })"]` );
        const originalText = $btn.html();
        $btn.html( '⏳...' ).prop( 'disabled', true );

        $.ajax( {
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'eipsi_resend_email',
                nonce: eipsi.nonce,
                email_log_id: emailLogId,
            },
            success( response ) {
                if ( response.success ) {
                    alert(
                        eipsi.i18n.emailSent || 'Email enviado exitosamente'
                    );
                    loadEmailLogs(); // Refresh
                } else {
                    alert( response.data || eipsi.i18n.errorSending );
                }
            },
            error() {
                alert( eipsi.i18n.connectionError || 'Error de conexión' );
            },
            complete() {
                $btn.html( originalText ).prop( 'disabled', false );
            },
        } );
    };

    // === Export CSV ===

    function exportEmailLogsCSV() {
        const url = new URL( ajaxurl );
        url.searchParams.append( 'action', 'eipsi_email_log_export' );
        url.searchParams.append( 'nonce', eipsi.nonce );
        url.searchParams.append( 'survey_id', state.surveyId );
        url.searchParams.append( 'filters', JSON.stringify( state.filters ) );

        window.open( url.toString(), '_blank' );
    }

    // === Dropout Management Functions ===

    function loadAtRiskParticipants() {
        $dropoutBody.html( `
            <tr>
                <td colspan="7" style="text-align: center; padding: 40px;">
                    <span class="spinner is-active" style="display: inline-block; vertical-align: middle;"></span>
                    <span style="margin-left: 10px;">${
                        eipsi.i18n.loading || 'Cargando...'
                    }</span>
                </td>
            </tr>
        ` );

        const riskDays = parseInt( $( '#filter-risk-days' ).val() ) || 7;

        $.ajax( {
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'eipsi_get_at_risk_participants',
                nonce: eipsi.nonce,
                survey_id: state.surveyId,
                days_overdue: riskDays,
            },
            success( response ) {
                if ( response.success ) {
                    renderAtRiskParticipants(
                        response.data.participants,
                        response.data.stats
                    );
                } else {
                    showError( response.data || eipsi.i18n.errorLoading );
                }
            },
            error() {
                showError( eipsi.i18n.connectionError || 'Error de conexión' );
            },
        } );
    }

    function renderAtRiskParticipants( participants, stats ) {
        // Update stats
        $( '#at-risk-count' ).text( stats.at_risk || 0 );
        $( '#pending-count' ).text( stats.pending || 0 );
        $( '#reminders-today' ).text( stats.reminders_today || 0 );

        if ( participants.length === 0 ) {
            $dropoutBody.html( `
                <tr>
                    <td colspan="7" style="text-align: center; padding: 40px;">
                        <p style="color: #646970;">${
                            eipsi.i18n.noAtRisk ||
                            'No hay participantes en riesgo'
                        }</p>
                    </td>
                </tr>
            ` );
            return;
        }

        let html = '';
        participants.forEach( ( p ) => {
            const dueDate = new Date( p.due_at );
            const lastActivity = new Date( p.last_activity_at );

            const daysOverdue = Math.floor(
                ( new Date() - dueDate ) / ( 1000 * 60 * 60 * 24 )
            );

            html += `
                <tr>
                    <td>
                        <input type="checkbox" class="eipsi-at-risk-checkbox" 
                               data-participant-id="${ p.participant_id }" 
                               data-assignment-id="${ p.assignment_id }">
                    </td>
                    <td>
                        <strong>${ escapeHtml( p.participant_name ) }</strong>
                        <div style="font-size: 12px; color: #646970;">${ escapeHtml(
                            p.email
                        ) }</div>
                    </td>
                    <td>
                        <span class="eipsi-email-type reminder">${ escapeHtml(
                            p.wave_name
                        ) }</span>
                    </td>
                    <td>
                        <div style="font-weight: 500;">${ dueDate.toLocaleDateString(
                            'es-ES'
                        ) }</div>
                        <div style="font-size: 12px; color: #d63638;">+${ daysOverdue } días</div>
                    </td>
                    <td>
                        <div>${ lastActivity.toLocaleDateString(
                            'es-ES'
                        ) }</div>
                        <div style="font-size: 12px; color: #646970;">${ Math.floor(
                            ( new Date() - lastActivity ) /
                                ( 1000 * 60 * 60 * 24 )
                        ) } días</div>
                    </td>
                    <td>
                        <span class="eipsi-at-risk-badge">🚨 ${
                            eipsi.i18n.atRisk || 'EN RIESGO'
                        }</span>
                    </td>
                    <td>
                        <div class="eipsi-action-buttons">
                            <button class="button button-small button-primary" 
                                    onclick="sendDropoutReminder(${
                                        p.participant_id
                                    }, ${ p.wave_id })">
                                📧 ${ eipsi.i18n.reminder || 'Recordatorio' }
                            </button>
                            <button class="button button-small" 
                                    onclick="showExtendModal(${
                                        p.assignment_id
                                    })">
                                📅 ${ eipsi.i18n.extend || 'Extender' }
                            </button>
                            <button class="button button-small" 
                                    onclick="markWaveCompleted(${
                                        p.assignment_id
                                    })">
                                ✅ ${ eipsi.i18n.complete || 'Completada' }
                            </button>
                            <button class="button button-small" 
                                    onclick="deactivateParticipant(${
                                        p.participant_id
                                    })"
                                    style="background: #d63638; color: white; border: none;">
                                🚫 ${ eipsi.i18n.deactivate || 'Desactivar' }
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        } );

        $dropoutBody.html( html );
    }

    function updateBulkActionState() {
        const checkedCount = $( '.eipsi-at-risk-checkbox:checked' ).length;
        $( '#bulk-action-select' ).prop( 'disabled', checkedCount === 0 );
        $( '#apply-bulk-action' ).prop( 'disabled', checkedCount === 0 );
    }

    // === Dropout Actions ===

    window.sendDropoutReminder = function ( participantId, waveId ) {
        if (
            ! confirm(
                eipsi.i18n.confirmReminder ||
                    '¿Deseas enviar un recordatorio a este participante?'
            )
        ) {
            return;
        }

        $.ajax( {
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'eipsi_send_dropout_reminder',
                nonce: eipsi.nonce,
                participant_id: participantId,
                wave_id: waveId,
            },
            success( response ) {
                if ( response.success ) {
                    alert(
                        eipsi.i18n.reminderSent ||
                            'Recordatorio enviado exitosamente'
                    );
                    loadAtRiskParticipants(); // Refresh
                } else {
                    alert( response.data || eipsi.i18n.errorSending );
                }
            },
            error() {
                alert( eipsi.i18n.connectionError || 'Error de conexión' );
            },
        } );
    };

    window.showExtendModal = function ( assignmentId ) {
        $( '#extend-assignment-id' ).val( assignmentId );
        $( '#extend-days' ).val( 7 );
        $( '#eipsi-extend-modal' ).fadeIn();
    };

    function extendWaveDeadline( assignmentId, days ) {
        $.ajax( {
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'eipsi_extend_wave_deadline',
                nonce: eipsi.nonce,
                assignment_id: assignmentId,
                days,
            },
            success( response ) {
                if ( response.success ) {
                    $( '#eipsi-extend-modal' ).fadeOut();
                    alert(
                        `${
                            eipsi.i18n.extended || 'Vencimiento extendido'
                        } ${ days } ${ eipsi.i18n.days || 'días' }`
                    );
                    loadAtRiskParticipants(); // Refresh
                } else {
                    alert( response.data || eipsi.i18n.error );
                }
            },
            error() {
                alert( eipsi.i18n.connectionError || 'Error de conexión' );
            },
        } );
    }

    window.markWaveCompleted = function ( assignmentId ) {
        if (
            ! confirm(
                eipsi.i18n.confirmComplete ||
                    '¿Deseas marcar esta toma como completada?'
            )
        ) {
            return;
        }

        $.ajax( {
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'eipsi_mark_wave_completed',
                nonce: eipsi.nonce,
                assignment_id: assignmentId,
            },
            success( response ) {
                if ( response.success ) {
                    alert(
                        eipsi.i18n.markedComplete ||
                            'Toma marcada como completada'
                    );
                    loadAtRiskParticipants(); // Refresh
                } else {
                    alert( response.data || eipsi.i18n.error );
                }
            },
            error() {
                alert( eipsi.i18n.connectionError || 'Error de conexión' );
            },
        } );
    };

    window.deactivateParticipant = function ( participantId ) {
        if (
            ! confirm(
                eipsi.i18n.confirmDeactivate ||
                    '¿Deseas desactivar este participante?'
            )
        ) {
            return;
        }

        $.ajax( {
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'eipsi_deactivate_participant',
                nonce: eipsi.nonce,
                participant_id: participantId,
            },
            success( response ) {
                if ( response.success ) {
                    alert(
                        eipsi.i18n.deactivated || 'Participante desactivado'
                    );
                    loadAtRiskParticipants(); // Refresh
                } else {
                    alert( response.data || eipsi.i18n.error );
                }
            },
            error() {
                alert( eipsi.i18n.connectionError || 'Error de conexión' );
            },
        } );
    };

    function executeBulkAction( action, participantIds ) {
        let confirmMsg = '';
        switch ( action ) {
            case 'send_reminder':
                confirmMsg = `¿Enviar recordatorio a ${ participantIds.length } participantes?`;
                break;
            case 'extend_7':
            case 'extend_14':
            case 'extend_30':
                const days = action.replace( 'extend_', '' );
                confirmMsg = `¿Extender vencimiento ${ days } días a ${ participantIds.length } participantes?`;
                break;
        }

        if ( ! confirm( confirmMsg ) ) {
            return;
        }

        $.ajax( {
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'eipsi_execute_bulk_action',
                nonce: eipsi.nonce,
                bulk_action: action,
                participant_ids: participantIds,
            },
            success( response ) {
                if ( response.success ) {
                    alert(
                        eipsi.i18n.actionComplete ||
                            'Acción completada exitosamente'
                    );
                    $( '#select-all-at-risk' ).prop( 'checked', false );
                    loadAtRiskParticipants(); // Refresh
                } else {
                    alert( response.data || eipsi.i18n.error );
                }
            },
            error() {
                alert( eipsi.i18n.connectionError || 'Error de conexión' );
            },
        } );
    }

    // === Utilities ===

    function escapeHtml( str ) {
        if ( ! str ) {
            return '';
        }
        const div = document.createElement( 'div' );
        div.textContent = str;
        return div.innerHTML;
    }

    function showError( message ) {
        $emailLogBody.html( `
            <tr>
                <td colspan="6" style="text-align: center; padding: 40px;">
                    <div style="color: #d63638;">
                        <strong>${ eipsi.i18n.error || 'Error' }</strong>
                        <p>${ message }</p>
                    </div>
                </td>
            </tr>
        ` );
    }
} )( jQuery );
