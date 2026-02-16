/**
 * EIPSI Forms - Study Dashboard
 * Handles Study Overview Modal, Wave Details, and Email Logs
 *
 * @package EIPSI_Forms
 * @since 1.4.3
 */

/* global eipsiStudyDash, ajaxurl */

( function ( $ ) {
    'use strict';

    // ===========================
    // STATE
    // ===========================

    let currentStudyId = 0;
    let isLoading = false;
    let currentPage = 1;
    let participantsPerPage = 20;

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

        // Copy Study Shortcode
        $( document ).on( 'click', '#copy-study-shortcode', function () {
            copyStudyShortcode();
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

        // Close Participants Modal
        $( document ).on( 'click', '#eipsi-participants-list-modal .eipsi-modal-close', function () {
            $( '#eipsi-participants-list-modal' ).fadeOut( 200 );
        } );

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
                if (
                    ! confirm(
                        '¿Estás seguro de desactivar a este participante?\n\n' +
                        '• No recibirá más emails de recordatorio\n' +
                        '• No podrá acceder al estudio\n\n' +
                        'Email: ' + participantEmail
                    )
                ) {
                    return;
                }
            } else {
                // Reactivar - confirmación
                if ( ! confirm( '¿Reactivar a este participante? Volverá a recibir emails de recordatorio.' ) ) {
                    return;
                }
            }

            toggleParticipantStatus( participantId, newStatus );
        } );

        // Pagination
        $( document ).on( 'click', '.participants-pagination button', function () {
            const page = $( this ).data( 'page' );
            if ( page && page !== currentPage ) {
                loadParticipantsList( page );
            }
        } );

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
                        '?page=eipsi-results&tab=waves-manager&study_id=' + currentStudyId;
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
        $( document ).on( 'click', '#eipsi-add-participant-modal .eipsi-modal-close', function () {
            closeAddParticipantModal();
        } );

        // Add Participant Form Submit
        $( document ).on( 'submit', '#add-participant-form', function ( e ) {
            e.preventDefault();
            submitAddParticipant();
        } );

        // Quick Action: Close Study
        $( document ).on( 'click', '#action-close-study', function () {
            if ( currentStudyId ) {
                if (
                    confirm(
                        '¿Estás seguro de cerrar este estudio?\n\n' +
                            '• Se bloquearán nuevas respuestas\n' +
                            '• El shortcode seguirá disponible para consulta\n\n' +
                            'Esta acción no se puede deshacer.'
                    )
                ) {
                    closeStudy( currentStudyId );
                }
            }
        } );

        // Quick Action: Delete Study
        $( document ).on( 'click', '#action-delete-study', function () {
            if ( currentStudyId ) {
                // Double confirmation for delete
                if (
                    confirm(
                        '⚠️ ESTA ACCIÓN ES IRREVERSIBLE ⚠️\n\n' +
                        '¿Estás seguro de ELIMINAR este estudio?\n\n' +
                        '• Se eliminarán TODOS los participantes\n' +
                        '• Se eliminarán TODAS las respuestas\n' +
                        '• Se eliminarán TODOS los waves\n' +
                        '• Se eliminarán TODOS los emails\n\n' +
                        'Esta acción NO se puede deshacer.'
                    )
                ) {
                    deleteStudy( currentStudyId );
                }
            }
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
        $( document ).on( 'click', '#eipsi-cron-jobs-modal .eipsi-modal-close', function () {
            $( '#eipsi-cron-jobs-modal' ).fadeOut( 200 );
        } );

        // Close CSV Import Modal
        $( document ).on( 'click', '#eipsi-import-csv-modal .eipsi-modal-close, #csv-cancel-btn', function () {
            closeCsvImportModal();
        } );

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
            if ( file && ( file.name.endsWith( '.csv' ) || file.name.endsWith( '.txt' ) ) ) {
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
    }

    function closeDashboardModal() {
        $( '#eipsi-study-dashboard-modal' ).fadeOut( 200 );
        currentStudyId = 0;
    }

    function closeStudy( studyId ) {
        $.ajax( {
            url:
                eipsiStudyDash.ajaxUrl ||
                ( typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php' ),
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
        $.ajax( {
            url:
                eipsiStudyDash.ajaxUrl ||
                ( typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php' ),
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
                        window.location.href = '?page=eipsi-longitudinal-study&tab=dashboard-study';
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

        $.ajax( {
            url:
                eipsiStudyDash.ajaxUrl ||
                ( typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php' ),
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
                    let message = response.data && response.data.message
                        ? response.data.message
                        : 'Participante creado e invitación enviada.';

                    if ( response.data && response.data.email_sent === false ) {
                        message = 'Participante creado, pero la invitación no pudo enviarse.';
                    }

                    if ( response.data && response.data.temporary_password && response.data.email_sent === false ) {
                        message +=
                            '<br><strong>Contraseña temporal:</strong> ' +
                            escapeHtml( response.data.temporary_password ) +
                            '<br><small>Guárdala ahora; solo se mostrará una vez.</small>';
                    }

                    $( '#add-participant-success' ).html( '<p>' + message + '</p>' ).show();
                    $( '#participant-password' ).val( '' );

                    if ( currentStudyId ) {
                        loadStudyOverview( currentStudyId );
                    }

                    if ( $( '#eipsi-participants-list-modal' ).is( ':visible' ) ) {
                        loadParticipantsList( currentPage );
                    }
                } else {
                    showAddParticipantError( response.data || 'Error al crear participante' );
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
    // PARTICIPANTS LIST MODAL
    // ===========================

    function openParticipantsModal() {
        $( '#eipsi-participants-list-modal' ).fadeIn( 200 );
        $( '#participants-loading' ).show();
        $( '#participants-content' ).hide();
        loadParticipantsList( 1 );
    }

    function loadParticipantsList( page ) {
        if ( ! currentStudyId ) return;

        const statusFilter = $( '#participant-status-filter' ).val();
        const searchTerm = $( '#participant-search' ).val();

        $( '#participants-loading' ).show();
        $( '#participants-content' ).hide();

        $.ajax( {
            url:
                eipsiStudyDash.ajaxUrl ||
                ( typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php' ),
            type: 'GET',
            data: {
                action: 'eipsi_get_participants_list',
                nonce: eipsiStudyDash.nonce,
                study_id: currentStudyId,
                page: page,
                per_page: participantsPerPage,
                status: statusFilter,
                search: searchTerm,
            },
            success( response ) {
                if ( response.success && response.data ) {
                    renderParticipantsList( response.data );
                } else {
                    showErrorParticipants( response.data || 'Error al cargar participantes' );
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
                '<tr><td colspan="6" style="text-align:center;padding:30px;color:#666;">No se encontraron participantes</td></tr>'
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

            const statusIcon = p.is_active
                ? '<span class="dashicons dashicons-yes-alt" style="color:#27ae60;"></span>'
                : '<span class="dashicons dashicons-no-alt" style="color:#d63638;"></span>';

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

            html += '<tr class="participant-row">';
            html += '<td><strong>' + escapeHtml( p.email ) + '</strong></td>';
            html +=
                '<td>' +
                escapeHtml( p.first_name ) +
                ' ' +
                escapeHtml( p.last_name ) +
                '</td>';
            html += '<td>' + statusBadge + '</td>';
            html += '<td>' + formatDate( p.created_at ) + '</td>';
            html +=
                '<td>' +
                ( p.last_login_at ? formatDateTime( p.last_login_at ) : 'Nunca' ) +
                '</td>';
            html += '<td>' + toggleButton + '</td>';
            html += '</tr>';
        } );

        $tbody.html( html );

        // Render pagination
        renderParticipantsPagination( page, pages );
    }

    function renderParticipantsPagination( currentPage, totalPages ) {
        if ( totalPages <= 1 ) {
            $( '#participants-pagination' ).empty();
            return;
        }

        let html = '<div class="tablenav-pages">';

        if ( currentPage > 1 ) {
            html +=
                '<button class="button button-small" data-page="' +
                ( currentPage - 1 ) +
                '">« Anterior</button>';
        }

        html += '<span class="paging-input">';
        html += currentPage + ' de ' + totalPages;
        html += '</span>';

        if ( currentPage < totalPages ) {
            html +=
                '<button class="button button-small" data-page="' +
                ( currentPage + 1 ) +
                '">Siguiente »</button>';
        }

        html += '</div>';

        $( '#participants-pagination' ).html( html );
    }

    function toggleParticipantStatus( participantId, isActive ) {
        const $btn = $(
            '.toggle-participant-status[data-participant-id="' + participantId + '"]'
        );
        const originalText = $btn.text();
        $btn.text( 'Procesando...' ).prop( 'disabled', true );

        $.ajax( {
            url:
                eipsiStudyDash.ajaxUrl ||
                ( typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php' ),
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
            '<tr><td colspan="6" style="text-align:center;color:#d63638;padding:30px;"><strong>Error:</strong> ' +
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
            const lines = csvRawData.split( /\r\n|\n|\r/ ).filter( function ( line ) {
                return line.trim() !== '';
            } );

            if ( lines.length === 0 ) {
                showCsvError( 'El archivo CSV está vacío' );
                return;
            }

            if ( lines.length > 501 ) {
                showCsvError( 'El archivo contiene más de 500 participantes. Por favor, divide el archivo.' );
                return;
            }

            // Mostrar botón de validar
            $( '#csv-validate-btn' ).show();
            showCsvSuccess( 'Archivo cargado: ' + ( lines.length - 1 ) + ' participantes detectados (aprox)' );
        };

        reader.onerror = function () {
            showCsvError( 'Error al leer el archivo' );
        };

        reader.readAsText( file );
    }

    function downloadCsvTemplate() {
        const template = 'email,first_name,last_name\n' +
            'juan.perez@email.com,Juan,Pérez\n' +
            'maria.garcia@email.com,María,García\n' +
            'carlos.lopez@email.com,Carlos,López';

        const blob = new Blob( [ template ], { type: 'text/csv;charset=utf-8;' } );
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

        $.ajax( {
            url:
                eipsiStudyDash.ajaxUrl ||
                ( typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php' ),
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
        summaryHtml += '<span class="summary-item valid">✓ ' + summary.valid + ' válidos</span>';
        if ( summary.invalid > 0 ) {
            summaryHtml += '<span class="summary-item invalid">✗ ' + summary.invalid + ' inválidos</span>';
        }
        if ( summary.existing > 0 ) {
            summaryHtml += '<span class="summary-item existing">⚠ ' + summary.existing + ' existentes</span>';
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
                statusBadge = '<span class="status-badge valid">✓ Válido</span>';
            } else if ( p.status === 'invalid' ) {
                statusBadge = '<span class="status-badge invalid" title="' + escapeHtml( p.errors.join( ', ' ) ) + '">✗ Inválido</span>';
            } else if ( p.status === 'existing' ) {
                statusBadge = '<span class="status-badge existing">⚠ Existente</span>';
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
            html += '<tr><td colspan="5" class="preview-more">... y ' + ( participants.length - 50 ) + ' más</td></tr>';
        }

        $( '#csv-preview-tbody' ).html( html );
    }

    function importCsvParticipants() {
        if ( ! csvValidationResults || ! csvValidationResults.participants || ! currentStudyId ) {
            showCsvError( 'No hay participantes para importar' );
            return;
        }

        // Filtrar solo participantes válidos
        const validParticipants = csvValidationResults.participants.filter( function ( p ) {
            return p.status === 'valid';
        } );

        if ( validParticipants.length === 0 ) {
            showCsvError( 'No hay participantes válidos para importar' );
            return;
        }

        if ( ! confirm( '¿Importar ' + validParticipants.length + ' participantes y enviar invitaciones por email?' ) ) {
            return;
        }

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

            $.ajax( {
                url:
                    eipsiStudyDash.ajaxUrl ||
                    ( typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php' ),
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
                    updateProgress( processed, total, imported, failed, emailsSent );
                    processBatch( batchIndex + 1 );
                },
            } );
        }

        function updateProgress( current, total, imported, failed, emailsSent ) {
            const pct = Math.round( ( current / total ) * 100 );
            $( '#csv-import-progress-bar' ).css( 'width', pct + '%' );
            $( '#csv-import-counter' ).text( current + ' / ' + total );
            $( '#csv-progress-details' ).html(
                'Importados: ' + imported + ' | Emails enviados: ' + emailsSent + ' | Fallidos: ' + failed
            );
        }

        function showImportResults( imported, failed, emailsSent ) {
            showCsvStep( 4 );

            let resultsHtml = '<div class="csv-results-inner">';

            if ( imported > 0 ) {
                resultsHtml += '<div class="result-item success">';
                resultsHtml += '<span class="result-icon">✓</span>';
                resultsHtml += '<span class="result-text">' + imported + ' participantes importados exitosamente</span>';
                resultsHtml += '</div>';
            }

            if ( emailsSent > 0 ) {
                resultsHtml += '<div class="result-item success">';
                resultsHtml += '<span class="result-icon">✉️</span>';
                resultsHtml += '<span class="result-text">' + emailsSent + ' invitaciones enviadas</span>';
                resultsHtml += '</div>';
            }

            if ( failed > 0 ) {
                resultsHtml += '<div class="result-item error">';
                resultsHtml += '<span class="result-icon">✗</span>';
                resultsHtml += '<span class="result-text">' + failed + ' participantes no pudieron ser importados</span>';
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
        $.ajax( {
            url:
                eipsiStudyDash.ajaxUrl ||
                ( typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php' ),
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
        if ( isLoading ) return;
        isLoading = true;

        $( '#eipsi-dashboard-loading' ).show();
        $( '#eipsi-dashboard-content' ).hide();

        $.ajax( {
            url:
                eipsiStudyDash.ajaxUrl ||
                ( typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php' ),
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
            $( '#study-created-at' ).text( formatDate( data.general.created_at ) );
            $( '#study-estimated-end' ).text(
                data.general.estimated_end_date
                    ? formatDate( data.general.estimated_end_date )
                    : 'No definida'
            );
            $( '#study-id-display' ).text( data.general.study_code );

            const shortcode = buildStudyShortcode( data.general.id || currentStudyId );
            $( '#study-shortcode-display' ).text( shortcode );

            const isCompleted = data.general.status === 'completed';
            const $closeBtn = $( '#action-close-study' );
            const originalLabel = $closeBtn.data( 'label' ) || $closeBtn.text();
            $closeBtn.data( 'label', originalLabel );
            $closeBtn.prop( 'disabled', isCompleted );
            $closeBtn.text( isCompleted ? '🔒 Estudio cerrado' : originalLabel );
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
                eipsiStudyDash.ajaxUrl ||
                ( typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php' ),
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

        $.ajax( {
            url:
                eipsiStudyDash.ajaxUrl ||
                ( typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php' ),
            type: 'POST',
            data: {
                action: 'eipsi_extend_wave_deadline',
                nonce: eipsiStudyDash.nonce,
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
                eipsiStudyDash.ajaxUrl ||
                ( typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php' ),
            type: 'POST',
            data: {
                action: 'eipsi_send_wave_reminder_manual',
                nonce: eipsiStudyDash.nonce,
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
        const studyName = $( '#study-modal-title' ).text().replace( 'Detalles: ', '' );
        const statusBadge = $( '#study-status-badge' ).text();
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
            $( '#edit-study-start-date' ).val( formatDateForInput( startDate ) );
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

        $.ajax( {
            url:
                eipsiStudyDash.ajaxUrl ||
                ( typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php' ),
            type: 'POST',
            data: {
                action: 'eipsi_save_study_settings',
                nonce: eipsiStudyDash.nonce,
                study_id: studyId,
                study_name: studyName,
                study_description: studyDescription,
                time_config: timeConfig,
                start_date: startDate,
                end_date: endDate
            },
            success( response ) {
                if ( response.success ) {
                    $( '#edit-study-success' ).text( response.data.message ).show();
                    $( '#edit-study-error' ).hide();
                    
                    // Refresh dashboard
                    loadStudyOverview( currentStudyId );
                    
                    // Close modal after 2 seconds
                    setTimeout( function () {
                        closeEditStudyModal();
                    }, 2000 );
                } else {
                    $( '#edit-study-error' ).text( response.data ? response.data.message : 'Error al guardar cambios' ).show();
                    $( '#edit-study-success' ).hide();
                }
            },
            error() {
                $( '#edit-study-error' ).text( 'Error de conexión al guardar cambios' ).show();
                $( '#edit-study-success' ).hide();
            },
            complete() {
                $btn.text( originalText ).prop( 'disabled', false );
            },
        } );
    } );

    // Close edit study modal
    $( document ).on( 'click', '#eipsi-edit-study-modal .eipsi-modal-close', function () {
        closeEditStudyModal();
    } );

    // ===========================
    // HELPERS
    // ===========================

    function buildStudyShortcode( studyId ) {
        if ( ! studyId ) return '';
        return '[eipsi_longitudinal_study id="' + studyId + '"]';
    }

    function copyStudyShortcode() {
        const shortcode = $( '#study-shortcode-display' ).text();
        if ( ! shortcode ) {
            showNotification( 'No hay shortcode disponible', 'error' );
            return;
        }

        if ( navigator.clipboard && navigator.clipboard.writeText ) {
            navigator.clipboard.writeText( shortcode ).then( function () {
                showNotification( 'Shortcode copiado al portapapeles', 'success' );
            } ).catch( function () {
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
