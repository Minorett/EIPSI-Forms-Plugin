/**
 * Study Dashboard JS
 * 
 * @since 1.5.2
 */

(function($) {
    'use strict';

    const StudyDashboard = {
        currentStudyId: null,
        autoRefreshInterval: null,
        resendDebounceMap: new Map(),

        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            const self = this;

            // Open dashboard modal
            $('.eipsi-view-study').on('click', function() {
                const studyId = $(this).data('study-id');
                self.openDashboard(studyId);
            });

            // Close modals
            $('.eipsi-modal-close').on('click', function() {
                $(this).closest('.eipsi-modal').fadeOut();
                if ($(this).closest('#eipsi-study-dashboard-modal').length) {
                    self.stopAutoRefresh();
                }
            });

            // Refresh button
            $('#refresh-dashboard').on('click', function() {
                self.loadStudyData(self.currentStudyId);
            });

            // Email logs
            $('#view-email-logs').on('click', function() {
                self.openEmailLogs();
            });

            // Manual reminder
            $(document).on('click', '.send-reminder', function() {
                const waveId = $(this).data('wave-id');
                self.sendReminder(waveId);
            });

            // Extend deadline
            $(document).on('click', '.extend-deadline', function() {
                const waveId = $(this).data('wave-id');
                const currentDeadline = $(this).data('deadline');
                self.openExtendDeadline(waveId, currentDeadline);
            });

            // Save extended deadline
            $('#extend-deadline-form').on('submit', function(e) {
                e.preventDefault();
                self.saveExtendedDeadline();
            });

            // Add participant button
            $('#action-add-participant').on('click', function() {
                self.openAddParticipant();
            });

            // Add participant form submit
            $('#add-participant-form').on('submit', function(e) {
                e.preventDefault();
                self.addParticipant();
            });

            // Delete study
            $('#action-delete-study').on('click', function() {
                self.deleteStudy();
            });

            // View participants
            $('#action-view-participants').on('click', function() {
                self.openParticipantsList();
            });

            // Participant status filter
            $('#participant-status-filter').on('change', function() {
                self.loadParticipantsList(1);
            });

            // Participant search
            let searchTimeout;
            $('#participant-search').on('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    self.loadParticipantsList(1);
                }, 300);
            });

            // Toggle participant status
            $(document).on('click', '.toggle-participant-status', function() {
                const participantId = $(this).data('participant-id');
                const isActive = $(this).data('is-active');
                self.toggleParticipantStatus(participantId, isActive);
            });

            // Delete participant
            $(document).on('click', '.delete-participant-btn', function() {
                const participantId = $(this).data('participant-id');
                self.deleteParticipant(participantId);
            });

            // Resend email dropdown toggle
            $(document).on('click', '.resend-email-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const $dropdown = $(this).siblings('.resend-email-dropdown');
                $('.resend-email-dropdown').not($dropdown).hide();
                $dropdown.toggle();
            });

            // Close dropdowns on click outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.resend-email-wrapper').length) {
                    $('.resend-email-dropdown').hide();
                }
            });

            // Resend email type selection
            $(document).on('click', '.resend-email-option', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const participantId = $(this).data('participant-id');
                const emailType = $(this).data('email-type');
                const participantEmail = $(this).data('participant-email');
                const waveId = $(this).data('wave-id') || null;
                
                $(this).closest('.resend-email-dropdown').hide();
                
                self.resendEmail(participantId, emailType, participantEmail, waveId);
            });
        },

        openDashboard: function(studyId) {
            this.currentStudyId = studyId;
            $('#eipsi-study-dashboard-modal').fadeIn();
            this.scrollToModal('#eipsi-study-dashboard-modal');
            this.loadStudyData(studyId);
            this.startAutoRefresh();
        },

        scrollToModal: function(modalSelector) {
            // Scroll modal into view with smooth animation
            $('html, body').animate({
                scrollTop: $(modalSelector).offset().top - 50
            }, 300);
        },

        deleteStudy: function() {
            const self = this;
            
            if (!confirm('¿Estás seguro de que querés eliminar este estudio?\n\n⚠️ Esta acción eliminará TODOS los datos relacionados:\n• Participantes\n• Waves\n• Asignaciones\n• Emails enviados\n• Sesiones\n\nEsta acción NO SE PUEDE DESHACER.')) {
                return;
            }

            // Double confirmation for safety
            if (!confirm('⚠️ ÚLTIMA ADVERTENCIA ⚠️\n\nEl estudio será eliminado permanentemente.\n¿Continuar?')) {
                return;
            }

            $.ajax({
                url: eipsiStudyDash.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'eipsi_delete_study',
                    study_id: this.currentStudyId,
                    nonce: eipsiStudyDash.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Close modal
                        $('#eipsi-study-dashboard-modal').fadeOut();
                        self.stopAutoRefresh();
                        
                        // Show success message
                        self.showToast(response.data.message || 'Estudio eliminado correctamente', 'success');
                        
                        // Reload page after short delay
                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);
                    } else {
                        self.showToast('Error: ' + (response.data || 'No se pudo eliminar el estudio'), 'error');
                    }
                },
                error: function() {
                    self.showToast('Error de conexión al intentar eliminar el estudio', 'error');
                }
            });
        },

        loadStudyData: function(studyId) {
            const self = this;
            $('#eipsi-dashboard-loading').show();
            $('#eipsi-dashboard-content').hide();

            $.ajax({
                url: eipsiStudyDash.ajaxUrl,
                type: 'GET',
                data: {
                    action: 'eipsi_get_study_overview',
                    study_id: studyId,
                    nonce: eipsiStudyDash.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.renderDashboard(response.data);
                        $('#eipsi-dashboard-loading').hide();
                        $('#eipsi-dashboard-content').fadeIn();
                    } else {
                        alert(response.data || 'Error loading study data');
                    }
                },
                error: function() {
                    alert('Server error');
                }
            });
        },

        renderDashboard: function(data) {
            const general = data.general;
            const participants = data.participants;
            const waves = data.waves;
            const emails = data.emails;

            // General Info - usar study_code si existe, sino id
            const studyDisplayId = general.study_code || general.id;
            $('#study-modal-title').text(`${studyDisplayId}: ${general.study_name}`);
            $('#study-id-display').text(general.id);
            $('#study-created-at').text(general.created_at);
            $('#study-estimated-end').text(general.estimated_end_date || 'Sin fecha definida');
            $('#study-status-badge').text(general.status).attr('class', `eipsi-badge badge-${general.status}`);

            // Shortcode - generar con study_code o id
            const shortcode = `[eipsi_longitudinal_study study_code="${studyDisplayId}"]`;
            $('#study-shortcode-display').text(shortcode);

            // Participant Stats
            $('#total-participants').text(participants.total);
            const compPct = participants.total > 0 ? Math.round((participants.completed / participants.total) * 100) : 0;
            const progPct = participants.total > 0 ? Math.round((participants.in_progress / participants.total) * 100) : 0;
            const inactPct = participants.total > 0 ? Math.round((participants.inactive / participants.total) * 100) : 0;

            $('#percent-completed').text(`${compPct}%`);
            $('#bar-completed').css('width', `${compPct}%`);
            $('#percent-in-progress').text(`${progPct}%`);
            $('#bar-in-progress').css('width', `${progPct}%`);
            $('#percent-inactive').text(`${inactPct}%`);
            $('#bar-inactive').css('width', `${inactPct}%`);

            // Waves
            const $container = $('#waves-container');
            $container.empty();

            waves.forEach(wave => {
                const deadlineText = wave.deadline || 'Sin fecha límite';
                const waveHtml = `
                    <div class="wave-item-card">
                        <div class="wave-header">
                            <span class="wave-name">${wave.wave_name}</span>
                            <span class="eipsi-badge badge-${wave.status}">${wave.status}</span>
                        </div>
                        <div class="wave-stats">
                            <small>${wave.form_id}</small><br>
                            <strong>${wave.completed}/${wave.total}</strong> completados (${wave.progress}%)
                        </div>
                        <div class="progress-bar-bg" style="margin: 8px 0;">
                            <div class="progress-bar-fill blue" style="width: ${wave.progress}%"></div>
                        </div>
                        <div class="wave-footer">
                            <small>Vence: ${deadlineText}</small>
                        </div>
                        <div class="wave-actions">
                            <button class="button button-small send-reminder" data-wave-id="${wave.id}">📧</button>
                            <button class="button button-small extend-deadline" data-wave-id="${wave.id}" data-deadline="${wave.deadline || ''}">📅</button>
                        </div>
                    </div>
                `;
                $container.append(waveHtml);
            });

            // Emails
            $('#emails-sent-today').text(emails.sent_today);
            $('#emails-failed').text(emails.failed);
            $('#emails-last-sent').text(emails.last_sent ? this.formatRelativeTime(emails.last_sent) : 'Nunca');
        },

        openEmailLogs: function() {
            const self = this;
            $.ajax({
                url: eipsiStudyDash.ajaxUrl,
                type: 'GET',
                data: {
                    action: 'eipsi_get_study_email_logs',
                    study_id: this.currentStudyId,
                    nonce: eipsiStudyDash.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const $tbody = $('#email-logs-tbody');
                        $tbody.empty();
                        response.data.forEach(log => {
                            $tbody.append(`
                                <tr>
                                    <td>${log.sent_at}</td>
                                    <td>${log.recipient_email}</td>
                                    <td>${log.subject}</td>
                                    <td><span class="eipsi-badge badge-${log.status}">${log.status}</span></td>
                                </tr>
                            `);
                        });
                        $('#eipsi-email-logs-modal').fadeIn();
                        self.scrollToModal('#eipsi-email-logs-modal');
                    }
                }
            });
        },

        sendReminder: function(waveId) {
            if (!confirm(eipsiStudyDash.strings.confirmReminder)) return;

            $.ajax({
                url: eipsiStudyDash.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'eipsi_send_wave_reminder_manual',
                    wave_id: waveId,
                    nonce: eipsiStudyDash.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                    } else {
                        alert('Error: ' + response.data);
                    }
                }
            });
        },

        openExtendDeadline: function(waveId, currentDeadline) {
            $('#extend-wave-id').val(waveId);
            $('#new-deadline-date').val(currentDeadline ? currentDeadline.split(' ')[0] : '');
            $('#eipsi-extend-deadline-modal').fadeIn();
            this.scrollToModal('#eipsi-extend-deadline-modal');
        },

        saveExtendedDeadline: function() {
            const self = this;
            const waveId = $('#extend-wave-id').val();
            const newDate = $('#new-deadline-date').val();

            $.ajax({
                url: eipsiStudyDash.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'eipsi_extend_wave_deadline',
                    wave_id: waveId,
                    new_deadline: newDate,
                    nonce: eipsiStudyDash.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#eipsi-extend-deadline-modal').fadeOut();
                        self.loadStudyData(self.currentStudyId);
                    } else {
                        alert('Error: ' + response.data);
                    }
                }
            });
        },

        openAddParticipant: function() {
            // Reset form
            $('#add-participant-form')[0].reset();
            $('#add-participant-study-id').val(this.currentStudyId);
            $('#add-participant-error').hide();
            $('#add-participant-success').hide();
            
            // Open modal
            $('#eipsi-add-participant-modal').fadeIn();
            this.scrollToModal('#eipsi-add-participant-modal');
        },

        addParticipant: function() {
            const self = this;
            const $form = $('#add-participant-form');
            const $submitButton = $('#submit-add-participant');
            const $error = $('#add-participant-error');
            const $success = $('#add-participant-success');

            // Reset messages
            $error.hide();
            $success.hide();

            // Disable submit button
            $submitButton.prop('disabled', true).text('⏳ Procesando...');

            // Collect form data
            const formData = {
                action: 'eipsi_add_participant',
                study_id: $('#add-participant-study-id').val(),
                email: $('#participant-email').val(),
                first_name: $('#participant-first-name').val(),
                last_name: $('#participant-last-name').val(),
                password: $('#participant-password').val(),
                nonce: eipsiStudyDash.nonce
            };

            // Validate email format
            if (!formData.email || !formData.email.includes('@')) {
                $error.text('Por favor ingrese un email válido').show();
                $submitButton.prop('disabled', false).text('✉️ Crear y Enviar Invitación');
                return;
            }

            // Send AJAX request
            $.ajax({
                url: eipsiStudyDash.ajaxUrl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        $success.html(
                            '<strong>✅ ¡Éxito!</strong><br>' + 
                            (response.data.email_sent ? 
                                'Participante creado e invitación enviada por email.' : 
                                'Participante creado, pero hubo un problema enviando el email.')
                        ).show();

                        // Close modal after 3 seconds and refresh
                        setTimeout(function() {
                            $('#eipsi-add-participant-modal').fadeOut();
                            self.loadStudyData(self.currentStudyId);
                        }, 3000);
                    } else {
                        // Show error
                        $error.text('Error: ' + (response.data || 'Ocurrió un error inesperado')).show();
                    }
                },
                error: function(xhr, status, error) {
                    $error.text('Error de conexión: ' + error).show();
                },
                complete: function() {
                    // Re-enable submit button
                    $submitButton.prop('disabled', false).text('✉️ Crear y Enviar Invitación');
                }
            });
        },

        formatRelativeTime: function(dateString) {
            const now = new Date();
            const past = new Date(dateString);
            const diffMs = now - past;
            const diffSec = Math.floor(diffMs / 1000);
            const diffMin = Math.floor(diffSec / 60);
            const diffHour = Math.floor(diffMin / 60);
            const diffDay = Math.floor(diffHour / 24);

            if (diffSec < 60) return 'Hace instantes';
            if (diffMin < 60) return `Hace ${diffMin} min`;
            if (diffHour < 24) return `Hace ${diffHour} horas`;
            if (diffDay < 30) return `Hace ${diffDay} días`;
            return dateString;
        },

        startAutoRefresh: function() {
            const self = this;
            this.stopAutoRefresh();
            this.autoRefreshInterval = setInterval(() => {
                self.loadStudyData(self.currentStudyId);
            }, 60000); // 60 seconds
        },

        stopAutoRefresh: function() {
            if (this.autoRefreshInterval) {
                clearInterval(this.autoRefreshInterval);
                this.autoRefreshInterval = null;
            }
        },

        /**
         * Open participants list modal
         */
        openParticipantsList: function() {
            $('#eipsi-participants-list-modal').fadeIn();
            this.scrollToModal('#eipsi-participants-list-modal');
            this.loadParticipantsList(1);
        },

        /**
         * Load participants list with pagination
         */
        loadParticipantsList: function(page) {
            const self = this;
            const $loading = $('#participants-loading');
            const $content = $('#participants-content');

            $loading.show();
            $content.hide();

            const filters = {
                status: $('#participant-status-filter').val(),
                search: $('#participant-search').val()
            };

            $.ajax({
                url: eipsiStudyDash.ajaxUrl,
                type: 'GET',
                data: {
                    action: 'eipsi_get_participants_list',
                    study_id: this.currentStudyId,
                    page: page,
                    per_page: 20,
                    nonce: eipsiStudyDash.nonce,
                    ...filters
                },
                success: function(response) {
                    if (response.success) {
                        self.renderParticipantsList(response.data);
                        $loading.hide();
                        $content.show();
                    } else {
                        alert('Error: ' + (response.data || 'No se pudieron cargar los participantes'));
                    }
                },
                error: function() {
                    alert('Error de conexión');
                }
            });
        },

        /**
         * Render participants table
         */
        renderParticipantsList: function(data) {
            const self = this;
            const $tbody = $('#participants-tbody');
            $tbody.empty();

            $('#participants-count').text(`${data.total} participantes`);

            if (data.participants.length === 0) {
                $tbody.append('<tr><td colspan="4" style="text-align:center;">No se encontraron participantes</td></tr>');
                return;
            }

            data.participants.forEach(function(p) {
                const statusBadge = p.is_active 
                    ? '<span class="eipsi-badge badge-active">Activo</span>'
                    : '<span class="eipsi-badge badge-inactive">Inactivo</span>';

                const toggleText = p.is_active ? 'Desactivar' : 'Activar';
                const toggleClass = p.is_active ? 'deactivate' : 'activate';

                const row = `
                    <tr data-participant-id="${p.id}">
                        <td><code>${p.email}</code></td>
                        <td>${p.first_name || ''} ${p.last_name || ''}</td>
                        <td>${statusBadge}</td>
                        <td>
                            <div class="participants-actions-row">
                                <button type="button" 
                                        class="button button-small toggle-participant-status ${toggleClass}" 
                                        data-participant-id="${p.id}" 
                                        data-is-active="${p.is_active ? '1' : '0'}"
                                        title="${toggleText}">
                                    ${p.is_active ? '🔒' : '🔓'}
                                </button>
                                <button type="button" class="button button-small delete-participant-btn" data-participant-id="${p.id}" title="Eliminar">
                                    🗑️
                                </button>
                                <div class="resend-email-wrapper" style="position: relative; display: inline-block;">
                                    <button type="button" class="button button-small resend-email-btn" title="Reenviar email">
                                        📧
                                    </button>
                                    <div class="resend-email-dropdown" style="display:none; position:absolute; right:0; top:100%; background:#fff; border:1px solid #ccc; border-radius:4px; box-shadow:0 2px 5px rgba(0,0,0,0.15); z-index:1000; min-width:180px;">
                                        <a href="#" class="resend-email-option" data-participant-id="${p.id}" data-email-type="magic_link" data-participant-email="${p.email}" style="display:block; padding:8px 12px; text-decoration:none; color:#333;">
                                            ✨ Enviar Magic Link
                                        </a>
                                        <a href="#" class="resend-email-option" data-participant-id="${p.id}" data-email-type="welcome" data-participant-email="${p.email}" style="display:block; padding:8px 12px; text-decoration:none; color:#333;">
                                            👋 Email de Bienvenida
                                        </a>
                                        <a href="#" class="resend-email-option" data-participant-id="${p.id}" data-email-type="reminder" data-participant-email="${p.email}" style="display:block; padding:8px 12px; text-decoration:none; color:#333;">
                                            🔔 Recordatorio de Onda
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                `;
                $tbody.append(row);
            });

            // Render pagination
            this.renderParticipantsPagination(data.page, data.total_pages);
        },

        /**
         * Render pagination for participants
         */
        renderParticipantsPagination: function(currentPage, totalPages) {
            const self = this;
            const $pagination = $('#participants-pagination');
            $pagination.empty();

            if (totalPages <= 1) return;

            let html = '<div class="pagination-links">';

            if (currentPage > 1) {
                html += `<button class="button button-small" data-page="${currentPage - 1}">← Anterior</button>`;
            }

            html += `<span style="margin: 0 10px;">Página ${currentPage} de ${totalPages}</span>`;

            if (currentPage < totalPages) {
                html += `<button class="button button-small" data-page="${currentPage + 1}">Siguiente →</button>`;
            }

            html += '</div>';

            $pagination.html(html);

            // Bind pagination clicks
            $pagination.find('button').on('click', function() {
                self.loadParticipantsList($(this).data('page'));
            });
        },

        /**
         * Toggle participant active status
         */
        toggleParticipantStatus: function(participantId, isActive) {
            const self = this;
            const newStatus = isActive ? 0 : 1;
            const actionText = isActive ? 'desactivar' : 'activar';

            if (!confirm(`¿Estás seguro que querés ${actionText} este participante?`)) {
                return;
            }

            $.ajax({
                url: eipsiStudyDash.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'eipsi_toggle_participant_status',
                    participant_id: participantId,
                    is_active: newStatus,
                    nonce: eipsiStudyDash.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Reload the list
                        self.loadParticipantsList(1);
                    } else {
                        alert('Error: ' + (response.data || 'No se pudo cambiar el estado'));
                    }
                },
                error: function() {
                    alert('Error de conexión');
                }
            });
        },

        /**
         * Delete participant
         */
        deleteParticipant: function(participantId) {
            const self = this;
            const $row = $(`tr[data-participant-id="${participantId}"]`);

            if (!confirm('¿Querés eliminar este participante? Se borrarán sus asignaciones, sesiones y magic links. Esta acción no se puede deshacer.')) {
                return;
            }

            $row.addClass('loading');

            $.ajax({
                url: eipsiStudyDash.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'eipsi_delete_participant',
                    participant_id: participantId,
                    study_id: this.currentStudyId,
                    nonce: eipsiStudyDash.nonce
                },
                success: function(response) {
                    $row.removeClass('loading');
                    if (response.success) {
                        self.showToast(response.data.message || 'Participante eliminado correctamente', 'success');
                        self.loadParticipantsList(1);
                    } else {
                        self.showToast(response.data.message || 'No se pudo eliminar el participante', 'error');
                    }
                },
                error: function() {
                    $row.removeClass('loading');
                    self.showToast('Error de conexión al eliminar el participante', 'error');
                }
            });
        },

        /**
         * Resend email to participant (with debounce)
         */
        resendEmail: function(participantId, emailType, participantEmail, waveId) {
            const self = this;

            // Debounce: prevent multiple rapid clicks
            const debounceKey = `${participantId}-${emailType}`;
            if (this.resendDebounceMap.has(debounceKey)) {
                return;
            }
            this.resendDebounceMap.set(debounceKey, true);
            setTimeout(() => this.resendDebounceMap.delete(debounceKey), 3000);

            const typeLabels = {
                'welcome': 'Email de bienvenida',
                'magic_link': 'Magic Link',
                'reminder': 'Recordatorio de onda',
                'confirmation': 'Email de confirmación',
                'recovery': 'Email de recuperación'
            };

            if (!confirm(`¿Enviar ${typeLabels[emailType] || 'email'} a ${participantEmail}?`)) {
                return;
            }

            // Show loading state
            const $row = $(`tr[data-participant-id="${participantId}"]`);
            $row.addClass('loading');

            $.ajax({
                url: eipsiStudyDash.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'eipsi_resend_participant_email',
                    participant_id: participantId,
                    email_type: emailType,
                    study_id: this.currentStudyId,
                    wave_id: waveId,
                    nonce: eipsiStudyDash.nonce
                },
                success: function(response) {
                    $row.removeClass('loading');
                    
                    if (response.success) {
                        self.showToast(response.data.message || 'Email enviado correctamente', 'success');
                    } else {
                        // Show detailed error message from backend
                        const errorMsg = response.data.message || 'Error al enviar email';
                        const errorCode = response.data.error || '';
                        
                        // Provide helpful guidance based on error type
                        let helpfulMsg = errorMsg;
                        if (errorCode === 'invalid_survey_id' || errorCode === 'survey_not_found') {
                            helpfulMsg = errorMsg + '. Este participante tiene un estudio asociado inválido.';
                        }
                        
                        self.showToast(helpfulMsg, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    $row.removeClass('loading');
                    
                    // Try to parse error response
                    let errorMessage = 'Error de conexión: ' + error;
                    
                    try {
                        // Check if response is JSON
                        const contentType = xhr.getResponseHeader('content-type');
                        if (contentType && contentType.indexOf('application/json') !== -1) {
                            const response = JSON.parse(xhr.responseText);
                            if (response.data && response.data.message) {
                                errorMessage = response.data.message;
                                
                                // Add extra context for specific errors
                                if (response.data.error === 'invalid_survey_id') {
                                    errorMessage += ' (ID: ' + (response.data.participant_survey_id || '?') + ')';
                                }
                            }
                        } else {
                            // HTML response - likely a PHP error or page
                            errorMessage = 'Error del servidor. Por favor, revisa los logs.';
                        }
                    } catch (e) {
                        // If parsing fails, use generic message
                        errorMessage = 'Error de conexión. Verifica tu conexión a internet.';
                    }
                    
                    self.showToast(errorMessage, 'error');
                }
            });
        },

        /**
         * Show toast notification
         */
        showToast: function(message, type) {
            const $toast = $(`
                <div class="eipsi-toast eipsi-toast-${type}" style="
                    position: fixed;
                    bottom: 20px;
                    right: 20px;
                    padding: 12px 20px;
                    background: ${type === 'success' ? '#46b450' : '#dc3232'};
                    color: #fff;
                    border-radius: 4px;
                    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
                    z-index: 100000;
                    font-size: 14px;
                    max-width: 400px;
                ">
                    ${message}
                </div>
            `);

            $('body').append($toast);

            setTimeout(function() {
                $toast.fadeOut(function() {
                    $toast.remove();
                });
            }, 4000);
        }
    };

    $(document).ready(function() {
        StudyDashboard.init();
    });

})(jQuery);
