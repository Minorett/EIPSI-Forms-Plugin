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

            // Close modal buttons
            $('.eipsi-modal-close').on('click', function() {
                $(this).closest('.eipsi-modal').fadeOut();
                if ($(this).closest('#eipsi-study-dashboard-modal').length) {
                    self.stopAutoRefresh();
                }
            });

            // Close modal on ESC key
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    const openModal = $('.eipsi-modal:visible');
                    if (openModal.length) {
                        openModal.fadeOut();
                        if (openModal.is('#eipsi-study-dashboard-modal')) {
                            self.stopAutoRefresh();
                        }
                    }
                }
            });

            // Close modal when clicking outside (on overlay)
            $(document).on('click', '.eipsi-modal', function(e) {
                if (e.target === this) {
                    $(this).fadeOut();
                    if ($(this).is('#eipsi-study-dashboard-modal')) {
                        self.stopAutoRefresh();
                    }
                }
            });

            // Refresh button
            $('#refresh-dashboard').off('click').on('click', function() {
                console.log('[BUTTON] refresh-dashboard clicked, studyId:', self.currentStudyId);
                self.loadStudyData(self.currentStudyId);
            });

            // Email logs
            $('#view-email-logs').off('click').on('click', function() {
                console.log('[BUTTON] view-email-logs clicked');
                self.openEmailLogs();
            });

            // Send global reminder
            $('#send-global-reminder').off('click').on('click', function() {
                console.log('[BUTTON] send-global-reminder clicked');
                self.sendGlobalReminder();
            });

            // Import CSV
            $('#action-import-csv').off('click').on('click', function() {
                console.log('[BUTTON] action-import-csv clicked');
                $('#eipsi-csv-import-modal').fadeIn(200);
            });

            // Download data
            $('#action-download-data').off('click').on('click', function() {
                console.log('[BUTTON] action-download-data clicked, studyId:', self.currentStudyId);
                if (self.currentStudyId) {
                    window.location.href = 'admin.php?page=eipsi-longitudinal-study&tab=export&study_id=' + self.currentStudyId;
                }
            });

            // Copy shortcode
            $('#copy-shortcode').off('click').on('click', function() {
                const text = $('#shortcode-display').text();
                console.log('[BUTTON] copy-shortcode clicked, text:', text);
                navigator.clipboard.writeText(text).then(function() {
                    alert('Shortcode copiado al portapapeles');
                });
            });

            // Copy page URL
            $('#copy-page-url').off('click').on('click', function() {
                const text = $('#study-page-url').val();
                console.log('[BUTTON] copy-page-url clicked, text:', text);
                navigator.clipboard.writeText(text).then(function() {
                    alert('URL copiada al portapapeles');
                });
            });

            // View study page
            $('#view-study-page').off('click').on('click', function() {
                const url = $('#study-page-url').val();
                console.log('[BUTTON] view-study-page clicked, url:', url);
                if (url) window.open(url, '_blank');
            });

            // Edit study page
            $('#edit-study-page').off('click').on('click', function() {
                console.log('[BUTTON] edit-study-page clicked');
                alert('Función editar página no implementada aún');
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

            // Add participant button - .off() prevents duplicate handlers
            $('#action-add-participant').off('click').on('click', function() {
                console.log('[BUTTON] action-add-participant clicked');
                self.openAddParticipant();
            });

            // Delete study button - .off() prevents duplicate handlers
            $('#action-delete-study').off('click').on('click', function() {
                console.log('[BUTTON] action-delete-study clicked, studyId:', self.currentStudyId);
                if (!self.currentStudyId) {
                    console.error('[ERROR] No currentStudyId defined');
                    return;
                }
                if (confirm('⚠️ ESTA ACCIÓN ES IRREVERSIBLE ⚠️\n\n¿Estás seguro de ELIMINAR este estudio?\n\n• Se eliminarán TODOS los participantes\n• Se eliminarán TODAS las respuestas\n• Se eliminarán TODOS los waves\n• Se eliminarán TODOS los emails\n\nEsta acción NO se puede deshacer.\n\nPresiona OK para confirmar.')) {
                    if (confirm('⚠️ ÚLTIMA ADVERTENCIA\n\nEl estudio será eliminado PERMANENTEMENTE.\n\n¿Estás COMPLETAMENTE SEGURO?')) {
                        self.deleteStudy(self.currentStudyId);
                    }
                }
            });

            // Add participant form submit
            $('#add-participant-form').on('submit', function(e) {
                e.preventDefault();
                self.addParticipant();
            });

            // View participants
            $('#action-view-participants').off('click').on('click', function() {
                console.log('[BUTTON] action-view-participants clicked');
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
                console.log('[BUTTON] toggle-participant-status clicked, participantId:', participantId, 'isActive:', isActive);
                self.toggleParticipantStatus(participantId, isActive);
            });

            // Delete participant
            $(document).on('click', '.delete-participant-btn', function() {
                const participantId = $(this).data('participant-id');
                console.log('[BUTTON] delete-participant-btn clicked, participantId:', participantId);
                self.deleteParticipant(participantId);
            });

            // Magic Link button click
            $(document).on('click', '.resend-magic-link-btn', function(e) {
                e.stopPropagation();
                const participantId = $(this).data('participant-id');
                const participantEmail = $(this).data('participant-email');
                console.log('[BUTTON] Magic Link clicked, participantId:', participantId, 'email:', participantEmail);
                self.resendEmail(participantId, 'magic_link', participantEmail, null);
            });

            // Reminder button click
            $(document).on('click', '.resend-reminder-btn', function(e) {
                e.stopPropagation();
                const participantId = $(this).data('participant-id');
                const participantEmail = $(this).data('participant-email');
                console.log('[BUTTON] Reminder clicked, participantId:', participantId, 'email:', participantEmail);
                self.resendEmail(participantId, 'reminder', participantEmail, null);
            });
        },

        /**
         * Open dashboard modal and load study data
         */
        openDashboard: function(studyId) {
            console.log('[FUNC] openDashboard called, studyId:', studyId);
            this.currentStudyId = studyId;
            $('#eipsi-study-dashboard-modal').fadeIn(200);
            this.loadStudyData(studyId);
            this.startAutoRefresh();
        },

        /**
         * Load study data via AJAX
         */
        loadStudyData: function(studyId) {
            console.log('[FUNC] loadStudyData called, studyId:', studyId);
            const self = this;

            if (!studyId) {
                console.error('[ERROR] loadStudyData: studyId is null or undefined');
                return;
            }

            $.ajax({
                url: eipsiStudyDash.ajaxUrl,
                type: 'GET',
                data: {
                    action: 'eipsi_get_study_overview',
                    study_id: studyId,
                    nonce: eipsiStudyDash.nonce
                },
                success: function(response) {
                    console.log('[LOAD] AJAX success, response:', response);
                    if (response.success) {
                        self.renderDashboard(response.data);
                    } else {
                        console.error('[LOAD] Server error:', response.data);
                        self.showToast('Error al cargar datos del estudio', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('[LOAD] AJAX error:', status, error);
                    self.showToast('Error de conexión al cargar datos', 'error');
                }
            });
        },

        /**
         * Render dashboard with study data
         */
        renderDashboard: function(data) {
            console.log('[FUNC] renderDashboard called, data:', data);
            const general = data.general;
            const participants = data.participants;
            const waves = data.waves;
            const emails = data.emails;
            const page = data.page;

            // Header info
            const studyDisplayId = general.study_code || general.id;
            $('#study-name-display').text(general.study_name || 'Estudio sin nombre');
            $('#study-status-pill').text(general.status === 'active' ? 'Activo' : general.status === 'paused' ? 'Pausado' : 'Cerrado');
            $('#study-status-pill').attr('class', 'pill pill-' + (general.status === 'active' ? 'active' : general.status === 'paused' ? 'paused' : 'closed'));

            const created = new Date(general.created_at);
            const months = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
            $('#study-meta-display').text(
                'Creado ' + created.getDate() + ' ' + months[created.getMonth()] + ' ' + created.getFullYear() +
                ' · ID: ' + general.id +
                ' · ' + waves.length + ' tomas' +
                ' · ' + participants.total + ' participantes'
            );

            // Shortcode
            const shortcode = page && page.shortcode ? page.shortcode : '[eipsi_longitudinal_study study_code="' + studyDisplayId + '"]';
            $('#shortcode-display').text(shortcode);

            // Page URL
            if (page && page.url) {
                $('#study-page-url').val(page.url);
                $('#view-study-page, #edit-study-page').show();
            } else {
                $('#study-page-url').val('');
                $('#view-study-page, #edit-study-page').hide();
            }

            // KPIs
            $('#kpi-total').text(participants.total || 0);
            $('#kpi-active').text(participants.active || 0);
            $('#kpi-completed').text(participants.completed || 0);
            $('#kpi-emails').text(emails.sent_today || 0);

            // Control buttons visibility
            if (general.status === 'active') {
                $('#btn-pause-study').show();
                $('#btn-resume-study').hide();
            } else if (general.status === 'paused') {
                $('#btn-pause-study').hide();
                $('#btn-resume-study').show();
            } else {
                $('#btn-pause-study, #btn-resume-study').hide();
            }

            // Emails stats
            $('#emails-today').text(emails.sent_today || 0);
            $('#emails-failed').text(emails.failed || 0);
            $('#emails-pending').text(emails.pending || 0);

            // Render waves
            this.renderWaves(waves);
        },

        /**
         * Render waves cards
         */
        renderWaves: function(waves) {
            console.log('[FUNC] renderWaves called, waves count:', waves.length);
            const container = $('#waves-container');
            container.empty();

            if (!waves || waves.length === 0) {
                container.html('<p style="color:#666;padding:20px;">No hay tomas configuradas</p>');
                return;
            }

            waves.forEach(function(wave, index) {
                const waveNum = index + 1;
                const progress = wave.progress || 0;
                const completed = wave.completed || 0;
                const total = wave.total || 0;

                let deadlineText = 'Sin fecha límite';
                if (wave.has_due_date && wave.deadline) {
                    const d = new Date(wave.deadline);
                    deadlineText = 'Vence: ' + d.getDate() + '/' + (d.getMonth()+1) + '/' + d.getFullYear();
                }

                const html = `
                    <div class="wave-card" data-wave-id="${wave.id}">
                        <div class="wave-header">
                            <h4>${wave.name || 'Toma ' + waveNum}</h4>
                            <span class="wave-status status-${wave.status || 'active'}">${wave.status === 'active' ? 'Activa' : 'Pausada'}</span>
                        </div>
                        <div class="wave-progress">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: ${progress}%"></div>
                            </div>
                            <span class="progress-text">${completed}/${total} (${progress}%)</span>
                        </div>
                        <div class="wave-meta">
                            <span>${deadlineText}</span>
                            ${wave.has_due_date ? `<button class="button button-small extend-deadline" data-wave-id="${wave.id}" data-deadline="${wave.deadline}">Extender</button>` : ''}
                        </div>
                        <div class="wave-actions">
                            <button class="button button-small send-reminder" data-wave-id="${wave.id}">Enviar recordatorio</button>
                        </div>
                    </div>
                `;
                container.append(html);
            });
        },

        /**
         * Start auto-refresh interval
         */
        startAutoRefresh: function() {
            console.log('[FUNC] startAutoRefresh called');
            const self = this;
            this.stopAutoRefresh();
            this.autoRefreshInterval = setInterval(function() {
                if (self.currentStudyId) {
                    console.log('[AUTO] Refreshing study data...');
                    self.loadStudyData(self.currentStudyId);
                }
            }, 30000); // 30 seconds
        },

        /**
         * Stop auto-refresh interval
         */
        stopAutoRefresh: function() {
            console.log('[FUNC] stopAutoRefresh called');
            if (this.autoRefreshInterval) {
                clearInterval(this.autoRefreshInterval);
                this.autoRefreshInterval = null;
            }
        },

        /**
         * Generate Magic Link (show in UI, don't send email)
         */
        generateMagicLink: function() {
            const self = this;
            const email = $('#magic-link-email').val().trim();
            const $output = $('#magic-link-output');
            const $error = $('#magic-link-error');
            const $success = $('#magic-link-success');
            const $urlInput = $('#magic-link-url');

            // Hide previous messages
            $error.hide();
            $success.hide();
            $output.hide();

            // Validate email
            if (!email || !this.isValidEmail(email)) {
                $error.text('Por favor, ingresa un email válido').show();
                return;
            }

            // Get study ID from current context
            const studyId = this.currentStudyId;
            if (!studyId) {
                $error.text('No se pudo identificar el estudio').show();
                return;
            }

            $.ajax({
                url: eipsiStudyDash.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'eipsi_generate_magic_link',
                    study_id: studyId,
                    email: email,
                    nonce: eipsiStudyDash.nonce
                },
                beforeSend: function() {
                    $('#generate-magic-link').prop('disabled', true).text('Generando...');
                },
                success: function(response) {
                    $('#generate-magic-link').prop('disabled', false).text('🔐 Generar enlace');

                    if (response.success) {
                        // Show the magic link in the UI
                        $urlInput.val(response.data.magic_link || '');
                        $output.show();
                        
                        $success.text(response.data.message || 'Magic Link generado correctamente').show();
                    } else {
                        $error.text(response.data.message || 'Error al generar el Magic Link').show();
                    }
                },
                error: function() {
                    $('#generate-magic-link').prop('disabled', false).text('🔐 Generar enlace');
                    $error.text('Error de conexión').show();
                }
            });
        },

        /**
         * Send Magic Link via Email
         */
        sendMagicLinkEmail: function() {
            const self = this;
            const email = $('#magic-link-email').val().trim();
            const $error = $('#magic-link-error');
            const $success = $('#magic-link-success');
            const $output = $('#magic-link-output');

            // Hide previous messages
            $error.hide();
            $success.hide();
            $output.hide();

            // Validate email
            if (!email || !this.isValidEmail(email)) {
                $error.text('Por favor, ingresa un email válido').show();
                return;
            }

            // Get study ID from current context
            const studyId = this.currentStudyId;
            if (!studyId) {
                $error.text('No se pudo identificar el estudio').show();
                return;
            }

            $.ajax({
                url: eipsiStudyDash.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'eipsi_generate_magic_link',
                    study_id: studyId,
                    email: email,
                    nonce: eipsiStudyDash.nonce
                },
                beforeSend: function() {
                    $('#send-magic-link').prop('disabled', true).text('Enviando...');
                },
                success: function(response) {
                    $('#send-magic-link').prop('disabled', false).text('📨 Enviar por email');

                    if (response.success) {
                        // Show success message
                        $success.text(response.data.message || 'Magic Link enviado exitosamente').show();
                        
                        // Also show the link in case they want to copy it
                        if (response.data.magic_link) {
                            $('#magic-link-url').val(response.data.magic_link);
                            $output.show();
                        }
                        
                        // Clear the email input for next use
                        $('#magic-link-email').val('');
                    } else {
                        $error.text(response.data.message || 'Error al enviar el Magic Link').show();
                    }
                },
                error: function() {
                    $('#send-magic-link').prop('disabled', false).text('📨 Enviar por email');
                    $error.text('Error de conexión').show();
                }
            });
        },

        /**
         * Copy Magic Link to clipboard
         */
        copyMagicLink: function() {
            const $urlInput = $('#magic-link-url');
            const link = $urlInput.val();

            if (!link) {
                return;
            }

            // Use Clipboard API
            navigator.clipboard.writeText(link).then(function() {
                $('#copy-magic-link').text('✓ ¡Copiado!').prop('disabled', true);
                setTimeout(function() {
                    $('#copy-magic-link').text('📋 Copiar').prop('disabled', false);
                }, 2000);
            }).catch(function(err) {
                // Fallback for older browsers
                $urlInput.select();
                document.execCommand('copy');
                $('#copy-magic-link').text('✓ ¡Copiado!').prop('disabled', true);
                setTimeout(function() {
                    $('#copy-magic-link').text('📋 Copiar').prop('disabled', false);
                }, 2000);
            });
        },

        /**
         * Validate email format
         */
        isValidEmail: function(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        },

        /**
         * Pause Study
         */
        pauseStudy: function() {
            const self = this;
            if (!confirm('¿Estás seguro de que querés pausar este estudio?\n\nAl pausar:\n• Se detendrán los recordatorios automáticos\n• Los participantes no podrán enviar nuevas respuestas\n• El estudio puede reanudarse en cualquier momento')) {
                return;
            }

            $.ajax({
                url: eipsiStudyDash.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'eipsi_pause_study',
                    study_id: this.currentStudyId,
                    nonce: eipsiStudyDash.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.showToast('✅ Estudio pausado correctamente', 'success');
                        self.loadStudyData(self.currentStudyId);
                    } else {
                        self.showToast('❌ Error: ' + (response.data || 'No se pudo pausar el estudio'), 'error');
                    }
                },
                error: function() {
                    self.showToast('❌ Error de conexión', 'error');
                }
            });
        },

        /**
         * Resume Study
         */
        resumeStudy: function() {
            const self = this;
            if (!confirm('¿Reanudar este estudio?\n\nAl reanudar:\n• Los recordatorios automáticos se reactivarán\n• Los participantes podrán continuar con sus evaluaciones')) {
                return;
            }

            $.ajax({
                url: eipsiStudyDash.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'eipsi_resume_study',
                    study_id: this.currentStudyId,
                    nonce: eipsiStudyDash.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.showToast('✅ Estudio reanudado correctamente', 'success');
                        self.loadStudyData(self.currentStudyId);
                    } else {
                        self.showToast('❌ Error: ' + (response.data || 'No se pudo reanudar el estudio'), 'error');
                    }
                },
                error: function() {
                    self.showToast('❌ Error de conexión', 'error');
                }
            });
        },

        /**
         * Close Study permanently
         */
        closeStudy: function() {
            const self = this;
            if (!confirm('⚠️ ¿Cerrar permanentemente este estudio?\n\nEsta acción:\n• Detendrá todos los recordatorios\n• Marcará el estudio como completado\n• No se podrán agregar nuevos participantes\n• NO se pueden deshacer los cambios')) {
                return;
            }

            // Double confirmation
            if (!confirm('🔒 CONFIRMACIÓN FINAL\n\nUna vez cerrado, el estudio no podrá reactivarse.\n¿Continuar?')) {
                return;
            }

            $.ajax({
                url: eipsiStudyDash.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'eipsi_close_study',
                    study_id: this.currentStudyId,
                    nonce: eipsiStudyDash.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.showToast('✅ Estudio cerrado permanentemente', 'success');
                        self.loadStudyData(self.currentStudyId);
                    } else {
                        self.showToast('❌ Error: ' + (response.data || 'No se pudo cerrar el estudio'), 'error');
                    }
                },
                error: function() {
                    self.showToast('❌ Error de conexión', 'error');
                }
            });
        },

        /**
         * Delete Study permanently
         */
        deleteStudy: function(studyId) {
            const self = this;

            $.ajax({
                url: eipsiStudyDash.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'eipsi_delete_study',
                    study_id: studyId,
                    nonce: eipsiStudyDash.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.showToast('✅ Estudio eliminado permanentemente', 'success');
                        // Close modal and reload page to refresh study list
                        $('#eipsi-study-dashboard-modal').fadeOut();
                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);
                    } else {
                        self.showToast('❌ Error: ' + (response.data || 'No se pudo eliminar el estudio'), 'error');
                    }
                },
                error: function() {
                    self.showToast('❌ Error de conexión', 'error');
                }
            });
        },

        /**
         * Open participants list modal
         */
        openParticipantsList: function() {
            console.log('[FUNC] openParticipantsList called, studyId:', this.currentStudyId);
            $('#eipsi-participants-modal').fadeIn(200);
            this.loadParticipantsList(1);
        },

        /**
         * Load participants list via AJAX
         */
        loadParticipantsList: function(page) {
            console.log('[FUNC] loadParticipantsList called, page:', page, 'studyId:', this.currentStudyId);
            const self = this;
            const status = $('#participant-status-filter').val();
            const search = $('#participant-search').val();

            $.ajax({
                url: eipsiStudyDash.ajaxUrl,
                type: 'GET',
                data: {
                    action: 'eipsi_get_participants',
                    study_id: this.currentStudyId,
                    page: page,
                    status: status,
                    search: search,
                    nonce: eipsiStudyDash.nonce
                },
                success: function(response) {
                    console.log('[LOAD] Participants response:', response);
                    if (response.success) {
                        self.renderParticipantsList(response.data);
                    } else {
                        console.error('[LOAD] Error loading participants:', response.data);
                        $('#participants-tbody').html('<tr><td colspan="3" style="text-align:center;padding:20px;">Error al cargar participantes</td></tr>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('[LOAD] AJAX error:', status, error);
                    $('#participants-tbody').html('<tr><td colspan="3" style="text-align:center;padding:20px;">Error de conexión</td></tr>');
                }
            });
        },

        /**
         * Render participants table
         */
        renderParticipantsList: function(data) {
            console.log('[FUNC] renderParticipantsList called, total:', data.total, 'participants:', data.participants?.length);
            const self = this;
            const $tbody = $('#participants-tbody');
            $tbody.empty();

            $('#participants-count').text(`${data.total} participantes`);

            if (data.participants.length === 0) {
                $tbody.append('<tr><td colspan="3" style="text-align:center;padding:20px;">No se encontraron participantes</td></tr>');
                return;
            }

            data.participants.forEach(function(p) {
                console.log('[RENDER] Participant:', p.id, 'email:', p.email, 'is_active:', p.is_active);
                const statusBadge = p.is_active
                    ? '<span class="eipsi-badge badge-active">Activo</span>'
                    : '<span class="eipsi-badge badge-inactive">Inactivo</span>';

                // Toggle button: Activo = 🔒 (cerrado, clic para desactivar), Inactivo = 🔓 (abierto, clic para activar)
                const toggleIcon = p.is_active ? '🔒' : '🔓';
                const toggleText = p.is_active ? 'Desactivar' : 'Activar';
                const toggleClass = p.is_active ? 'deactivate' : 'activate';

                const row = `
                    <tr data-participant-id="${p.id}">
                        <td><code>${p.email}</code></td>
                        <td>${statusBadge}</td>
                        <td>
                            <div class="participants-actions-row" style="display:flex;gap:4px;align-items:center;">
                                <button type="button"
                                        class="button button-small toggle-participant-status ${toggleClass}"
                                        data-participant-id="${p.id}"
                                        data-is-active="${p.is_active ? '1' : '0'}"
                                        title="${toggleText}">
                                    ${toggleIcon}
                                </button>
                                <button type="button" class="button button-small delete-participant-btn" data-participant-id="${p.id}" title="Eliminar">
                                    🗑️
                                </button>
                                <button type="button" class="button button-small resend-magic-link-btn" data-participant-id="${p.id}" data-participant-email="${p.email}" title="Enviar Magic Link">
                                    ✨
                                </button>
                                <button type="button" class="button button-small resend-reminder-btn" data-participant-id="${p.id}" data-participant-email="${p.email}" title="Enviar Recordatorio">
                                    ⏰
                                </button>
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
            console.log('[FUNC] renderParticipantsPagination called, page:', currentPage, 'totalPages:', totalPages);
            const self = this;
            const $pagination = $('#participants-pagination');
            $pagination.empty();

            // Fallback if totalPages is not provided
            const total = totalPages || 1;
            if (total <= 1) return;

            let html = '<div class="pagination-links">';

            if (currentPage > 1) {
                html += `<button class="button button-small" data-page="${currentPage - 1}">← Anterior</button>`;
            }

            html += `<span style="margin: 0 10px;">Página ${currentPage} de ${total}</span>`;

            if (currentPage < total) {
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
            console.log('[FUNC] toggleParticipantStatus called, participantId:', participantId, 'isActive:', isActive);
            const self = this;
            const newStatus = isActive ? 0 : 1;
            const actionText = isActive ? 'desactivar' : 'activar';

            if (!confirm(`¿Estás seguro que querés ${actionText} este participante?`)) {
                console.log('[TOGGLE] Cancelled by user');
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
                    console.log('[TOGGLE] AJAX success, response:', response);
                    if (response.success) {
                        self.showToast(response.data.message || 'Estado actualizado correctamente', 'success');
                        self.loadParticipantsList(1);
                    } else {
                        console.error('[TOGGLE] Server error:', response.data);
                        self.showToast(response.data.message || 'No se pudo actualizar el estado', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('[TOGGLE] AJAX error:', status, error);
                    self.showToast('Error de conexión al actualizar el estado', 'error');
                }
            });
        },

        /**
         * Delete participant
         */
        deleteParticipant: function(participantId) {
            console.log('[FUNC] deleteParticipant called, participantId:', participantId);
            const self = this;
            const $row = $(`tr[data-participant-id="${participantId}"]`);
            console.log('[DELETE] Row found:', $row.length > 0);

            if (!confirm('¿Querés eliminar este participante? Se borrarán sus asignaciones, sesiones y magic links. Esta acción no se puede deshacer.')) {
                console.log('[DELETE] Cancelled by user');
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
                    console.log('[DELETE] AJAX success, response:', response);
                    if (response.success) {
                        self.showToast(response.data.message || 'Participante eliminado correctamente', 'success');
                        self.loadParticipantsList(1);
                    } else {
                        console.error('[DELETE] Server returned error:', response.data);
                        self.showToast(response.data.message || 'No se pudo eliminar el participante', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    $row.removeClass('loading');
                    console.error('[DELETE] AJAX error:', status, error, xhr.responseText);
                    self.showToast('Error de conexión al eliminar el participante', 'error');
                }
            });
        },

        /**
         * Resend email to participant (with debounce)
         */
        resendEmail: function(participantId, emailType, participantEmail, waveId) {
            console.log('[FUNC] resendEmail called, participantId:', participantId, 'type:', emailType, 'email:', participantEmail, 'waveId:', waveId);
            const self = this;

            // Debounce: prevent multiple rapid clicks
            const debounceKey = `${participantId}-${emailType}`;
            if (this.resendDebounceMap.has(debounceKey)) {
                console.log('[RESEND] Debounced, skipping');
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
                console.log('[RESEND] Cancelled by user');
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
                    console.log('[RESEND] AJAX success, response:', response);

                    if (response.success) {
                        self.showToast(response.data.message || 'Email enviado correctamente', 'success');
                    } else {
                        // Show detailed error message from backend
                        const errorMsg = response.data.message || 'Error al enviar email';
                        const errorCode = response.data.error || '';
                        console.error('[RESEND] Server returned error:', errorCode, errorMsg);

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
                    console.error('[RESEND] AJAX error:', status, error, 'response:', xhr.responseText?.substring(0, 500));

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
                            errorMessage = 'Error del servidor. Por favor intentá de nuevo.';
                        }
                    } catch (e) {
                        // Not JSON - use default message
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
