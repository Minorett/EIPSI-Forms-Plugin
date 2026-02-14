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

            // Action buttons (placeholders for now)
            $('#action-edit-study').on('click', function() {
                window.location.href = `?page=eipsi-longitudinal-study&tab=create-study&study_id=${self.currentStudyId}`;
            });

            $('#action-close-study').on('click', function() {
                if (confirm(eipsiStudyDash.strings.confirmClose)) {
                    // Redirect to close handler/anonymize
                    window.location.href = `admin.php?action=eipsi_close_study&study_id=${self.currentStudyId}&nonce=${eipsiStudyDash.nonce}`;
                }
            });
        },

        openDashboard: function(studyId) {
            this.currentStudyId = studyId;
            $('#eipsi-study-dashboard-modal').fadeIn();
            this.loadStudyData(studyId);
            this.startAutoRefresh();
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

            // General Info
            $('#study-modal-title').text(`${general.study_id}: ${general.study_name}`);
            $('#study-id-display').text(general.study_id);
            $('#study-created-at').text(general.created_at);
            $('#study-estimated-end').text(general.estimated_end_date || 'N/A');
            $('#study-status-badge').text(general.status).attr('class', `eipsi-badge badge-${general.status}`);

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
                            <small>Vence: ${wave.deadline}</small>
                        </div>
                        <div class="wave-actions">
                            <button class="button button-small send-reminder" data-wave-id="${wave.id}">📧</button>
                            <button class="button button-small extend-deadline" data-wave-id="${wave.id}" data-deadline="${wave.deadline}">📅</button>
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
        }
    };

    $(document).ready(function() {
        StudyDashboard.init();
    });

})(jQuery);
