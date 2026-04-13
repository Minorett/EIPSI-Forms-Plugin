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

            // Resend email dropdown toggle
            $(document).on('click', '.resend-email-btn', function(e) {
                console.log('[BUTTON] resend-email-btn clicked');
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
                console.log('[BUTTON] resend-email-option clicked, participantId:', participantId, 'emailType:', emailType);
                
                $(this).closest('.resend-email-dropdown').hide();
                
                self.resendEmail(participantId, emailType, participantEmail, waveId);
            });

            // Magic Link Form - Generate link button
            $('#generate-magic-link').off('click').on('click', function(e) {
                console.log('[BUTTON] generate-magic-link clicked');
                e.preventDefault();
                self.generateMagicLink();
            });

            // Magic Link Form - Send email button
            $('#send-magic-link').off('click').on('click', function(e) {
                console.log('[BUTTON] send-magic-link clicked');
                e.preventDefault();
                self.sendMagicLinkEmail();
            });

            // Magic Link Form - Copy link button
            $('#copy-magic-link').off('click').on('click', function() {
                console.log('[BUTTON] copy-magic-link clicked');
                self.copyMagicLink();
            });

            // Magic Link Form - Prevent default form submission
            $('#magic-link-form').on('submit', function(e) {
                e.preventDefault();
                self.sendMagicLinkEmail();
            });

            // Study Control Buttons - .off() prevents duplicate handlers
            $('#btn-pause-study').off('click').on('click', function() {
                console.log('[BUTTON] btn-pause-study clicked');
                self.pauseStudy();
            });

            $('#btn-resume-study').off('click').on('click', function() {
                console.log('[BUTTON] btn-resume-study clicked');
                self.resumeStudy();
            });

            $('#btn-close-study').off('click').on('click', function() {
                console.log('[BUTTON] btn-close-study clicked');
                self.closeStudy();
            });
        },

        openDashboard: function(studyId) {
            this.currentStudyId = studyId;
            window.currentStudyId = studyId; // Expose globally for inline scripts
            console.log('[FUNC] openDashboard, studyId:', studyId);
            $('#eipsi-study-dashboard-modal').fadeIn();
            this.scrollToModal('#eipsi-study-dashboard-modal');
            this.loadStudyData(studyId);
            this.startAutoRefresh();
        },

        scrollToModal: function(modalSelector) {
            // Scroll modal into view with smooth animation
            const $modal = $(modalSelector);
            if ($modal.length && $modal.offset()) {
                $('html, body').animate({
                    scrollTop: $modal.offset().top - 50
                }, 300);
            }
        },

        /**
         * Get wave interval text description
         */
        getWaveIntervalText: function(wave) {
            if (!wave.interval_value || !wave.interval_unit) {
                return 'Sin intervalo definido';
            }
            const unitLabels = {
                'minutes': 'minutos',
                'hours': 'horas',
                'days': 'días',
                'weeks': 'semanas',
                'months': 'meses'
            };
            const unit = unitLabels[wave.interval_unit] || wave.interval_unit;
            return `${wave.interval_value} ${unit} después de la toma anterior`;
        },

        loadStudyData: function(studyId) {
            console.log('[FUNC] loadStudyData called, studyId:', studyId);
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
            console.log('[FUNC] renderDashboard called', data);
            const self = this;
            const general = data.general;
            const participants = data.participants;
            const waves = data.waves;
            const emails = data.emails;
            const page = data.page;

            // General Info - usar study_code si existe, sino id
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

            // Shortcode - generar con study_code o id
            const shortcode = page && page.shortcode ? page.shortcode : `[eipsi_longitudinal_study study_code="${studyDisplayId}"]`;
            $('#shortcode-display').text(shortcode);

            // Study Page - show URL and actions
            this.renderStudyPage(page);

            // KPIs
            $('#kpi-total').text(participants.total);
            $('#kpi-active').text(participants.active || 0);
            $('#kpi-completed').text(participants.completed || 0);
            $('#kpi-emails').text(emails.sent_today || 0);

            // Study Control Buttons - Show/hide based on status
            const studyStatus = general.status;
            if (studyStatus === 'active') {
                $('#btn-pause-study').show();
                $('#btn-resume-study').hide();
                $('#btn-close-study').show().prop('disabled', false);
            } else if (studyStatus === 'paused') {
                $('#btn-pause-study').hide();
                $('#btn-resume-study').show();
                $('#btn-close-study').show().prop('disabled', false);
            } else if (studyStatus === 'completed' || studyStatus === 'closed') {
                $('#btn-pause-study').hide();
                $('#btn-resume-study').hide();
                $('#btn-close-study').show().prop('disabled', true).text('🔒 Estudio Cerrado');
            }

            // Waves - EIPSI redesigned cards
            const $container = $('#waves-container');
            $container.empty();

            waves.forEach((wave, index) => {
                const waveNum = index + 1;
                const progress = wave.progress || 0;
                const completed = wave.completed || 0;
                const total = wave.total || 0;
                const hasDeadline = wave.has_due_date && wave.deadline;
                const deadlineFormatted = wave.deadline_formatted || 'sin fecha límite';

                // Nudge config
                const nudgeConfig = wave.nudge_config || {};
                const nudgesEnabled = nudgeConfig.nudge_1?.enabled || nudgeConfig.nudge_2?.enabled || false;
                const nudgeCount = [nudgeConfig.nudge_1, nudgeConfig.nudge_2, nudgeConfig.nudge_3, nudgeConfig.nudge_4]
                    .filter(n => n && n.enabled).length;

                const waveHtml = `
                    <div class="wave-card" data-wave-id="${wave.id}">
                        <div class="wave-card-head">
                            <div class="wave-left">
                                <span class="wave-idx">T${waveNum}</span>
                                <div>
                                    <div class="wave-name">${wave.wave_name || 'Toma ' + waveNum}</div>
                                    <div class="wave-sub">${self.getWaveIntervalText(wave)}</div>
                                </div>
                            </div>
                            <div class="wave-right">
                                <span class="pill ${wave.status === 'active' ? 'pill-active' : ''}">${wave.status === 'active' ? 'Activo' : 'Inactivo'}</span>
                            </div>
                        </div>
                        <div class="wave-body">
                            <div class="prog-row">
                                <div class="prog-track"><div class="prog-fill ${progress === 100 ? 'fill-green' : progress > 0 ? 'fill-blue' : 'fill-gray'}" style="width:${progress}%"></div></div>
                                <span class="prog-lbl" style="color:${progress === 100 ? '#006666' : '#2c3e50'}">${completed}/${total} · ${progress}%</span>
                            </div>
                            <div class="deadline-row">
                                <span>Plazo:</span>
                                <span class="deadline-val ${hasDeadline ? '' : 'none'}">${deadlineFormatted}</span>
                                <button class="btn-link" onclick="toggleDeadlineEditor('de${wave.id}', this)">${hasDeadline ? 'Cambiar' : 'Asignar plazo'}</button>
                                ${hasDeadline ? `<button class="btn-link btn-link-red" onclick="removeDeadline(${wave.id})">Quitar</button>` : ''}
                            </div>
                            <div class="deadline-editor" id="de${wave.id}">
                                <div class="de-label">Fecha límite para completar esta toma</div>
                                <div class="de-row">
                                    <input type="date" id="de${wave.id}-date" value="${wave.deadline || ''}">
                                </div>
                                <div class="de-footer">
                                    <button class="btn-sm" onclick="toggleDeadlineEditor('de${wave.id}', null)">Cancelar</button>
                                    <button class="btn-sm btn-primary" onclick="saveDeadline(${wave.id}, 'de${wave.id}')">Guardar</button>
                                </div>
                            </div>
                        </div>
                        <div class="nudge-section">
                            <div class="nudge-toggle-row" onclick="toggleNudgePanel('n${wave.id}')">
                                <span class="nudge-lbl ${nudgesEnabled ? 'on' : ''}" id="nl${wave.id}">
                                    ${nudgesEnabled ? `Recordatorios activados · ${nudgeCount} nudges` : 'Recordatorios desactivados'}
                                </span>
                                <label class="toggle" onclick="event.stopPropagation()">
                                    <input type="checkbox" ${nudgesEnabled ? 'checked' : ''} onchange="toggleNudgePanel('n${wave.id}')">
                                    <span class="tslider"></span>
                                </label>
                            </div>
                            <div class="nudge-panel ${nudgesEnabled ? 'open' : ''}" id="n${wave.id}">
                                ${!hasDeadline ? '<div class="info-note">Asigná un plazo arriba para habilitar el modo "antes de vencimiento".</div>' : ''}
                                <div class="nudge-ref-row">
                                    Basado en: momento de disponibilidad
                                </div>
                                <div class="nudge-rows">
                                    ${self.renderNudgeRows(nudgeConfig, wave.id)}
                                </div>
                                <div class="nudge-footer">
                                    <button class="btn-sm" onclick="toggleNudgePanel('n${wave.id}')">Cancelar</button>
                                    <button class="btn-sm btn-primary" onclick="saveNudgeConfig(${wave.id})">Guardar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                $container.append(waveHtml);
            });

            // Emails - usar IDs que coinciden con el HTML
            $('#emails-today').text(emails.sent_today || 0);
            $('#emails-failed').text(emails.failed || 0);
            $('#emails-pending').text(emails.pending || 0);
            if (emails.last_sent) {
                const last = new Date(emails.last_sent);
                const months = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
                $('#emails-last-label').text('· último envío: ' + last.getDate() + ' ' + months[last.getMonth()]);
            } else {
                $('#emails-last-label').text('· último envío: -');
            }
        },

        /**
         * Render nudge rows for a wave
         */
        renderNudgeRows: function(config, waveId) {
            const defaults = [
                { value: 24, unit: 'hours' },
                { value: 72, unit: 'hours' },
                { value: 168, unit: 'hours' }
            ];

            let html = '';
            for (let i = 1; i <= 4; i++) {
                const nudge = config['nudge_' + i] || defaults[i-1] || { value: 24, unit: 'hours' };
                html += `
                    <div class="nudge-row">
                        <span class="nudge-num">${i}</span>
                        <input type="number" value="${nudge.value}" id="nudge-${waveId}-${i}-val" min="1">
                        <select id="nudge-${waveId}-${i}-unit">
                            <option value="hours" ${nudge.unit === 'hours' ? 'selected' : ''}>horas</option>
                            <option value="days" ${nudge.unit === 'days' ? 'selected' : ''}>días</option>
                        </select>
                        <span>después de disponible</span>
                    </div>
                `;
            }
            return html;
        },

        /**
         * Render study page section
         */
        renderStudyPage: function(page) {
            const self = this;
            const $loading = $('#study-page-loading');
            const $exists = $('#study-page-exists');
            const $notExists = $('#study-page-not-exists');

            $loading.hide();

            if (page && page.url) {
                // Page exists
                $exists.show();
                $notExists.hide();

                $('#study-page-url').val(page.url);
                $('#study-page-view-link').attr('href', page.url);

                if (page.edit_url) {
                    $('#study-page-edit-link').attr('href', page.edit_url).show();
                } else {
                    $('#study-page-edit-link').hide();
                }

                // Bind copy button
                $('#copy-study-page-url').off('click').on('click', function() {
                    self.copyToClipboard(page.url, this);
                });
            } else {
                // Page doesn't exist
                $exists.hide();
                $notExists.show();

                // Bind create page button
                $('#create-study-page').off('click').on('click', function() {
                    self.createStudyPage();
                });
            }
        },

        /**
         * Create study page via AJAX
         */
        createStudyPage: function() {
            const self = this;
            const $btn = $('#create-study-page');

            $btn.prop('disabled', true).text('Creando...');

            $.ajax({
                url: eipsiStudyDash.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'eipsi_create_study_page',
                    study_id: this.currentStudyId,
                    nonce: eipsiStudyDash.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.showToast('Página del estudio creada correctamente', 'success');
                        // Reload study data to get the new page info
                        self.loadStudyData(self.currentStudyId);
                    } else {
                        self.showToast('Error: ' + (response.data || 'No se pudo crear la página'), 'error');
                        $btn.prop('disabled', false).text('➕ Crear página del estudio');
                    }
                },
                error: function() {
                    self.showToast('Error de conexión', 'error');
                    $btn.prop('disabled', false).text('➕ Crear página del estudio');
                }
            });
        },

        /**
         * Copy text to clipboard
         */
        copyToClipboard: function(text, button) {
            const $btn = $(button);
            const originalText = $btn.text();

            navigator.clipboard.writeText(text).then(function() {
                $btn.text('✓ Copiado').prop('disabled', true);
                setTimeout(function() {
                    $btn.text(originalText).prop('disabled', false);
                }, 2000);
            }).catch(function() {
                // Fallback
                const $temp = $('<input>');
                $('body').append($temp);
                $temp.val(text).select();
                document.execCommand('copy');
                $temp.remove();

                $btn.text('✓ Copiado').prop('disabled', true);
                setTimeout(function() {
                    $btn.text(originalText).prop('disabled', false);
                }, 2000);
            });
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
            console.log('[FUNC] sendReminder called, waveId:', waveId);
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

        sendGlobalReminder: function() {
            console.log('[FUNC] sendGlobalReminder called, studyId:', this.currentStudyId);
            if (!this.currentStudyId) {
                console.error('[ERROR] No currentStudyId for sendGlobalReminder');
                alert('Error: No se ha seleccionado un estudio');
                return;
            }
            
            if (!confirm('¿Enviar recordatorio global a todos los participantes activos?')) {
                return;
            }

            const self = this;
            $.ajax({
                url: eipsiStudyDash.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'eipsi_send_global_reminder',
                    study_id: this.currentStudyId,
                    nonce: eipsiStudyDash.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message || 'Recordatorios enviados correctamente');
                        self.loadStudyData(self.currentStudyId);
                    } else {
                        alert('Error: ' + (response.data || 'No se pudieron enviar los recordatorios'));
                    }
                },
                error: function() {
                    alert('Error de conexión al enviar recordatorios');
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
            console.log('[FUNC] openAddParticipant called, studyId:', this.currentStudyId);
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
            $submitButton.prop('disabled', true).text('⏳ Agregando...');

            // Collect form data - ONLY email required
            const formData = {
                action: 'eipsi_add_participant',
                study_id: $('#add-participant-study-id').val(),
                email: $('#participant-email').val(),
                nonce: eipsiStudyDash.nonce
            };

            // Validate email format
            if (!formData.email || !formData.email.includes('@')) {
                $error.text('Por favor ingrese un email válido').show();
                $submitButton.prop('disabled', false).text('✉️ Agregar y Enviar Invitación');
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
                            'Participante agregado e invitación enviada por email.'
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
                    $submitButton.prop('disabled', false).text('✉️ Agregar y Enviar Invitación');
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
            console.log('[FUNC] openParticipantsList called');
            $('#eipsi-participants-modal').fadeIn();
            this.scrollToModal('#eipsi-participants-modal');
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
                $tbody.append('<tr><td colspan="3" style="text-align:center;padding:20px;">No se encontraron participantes</td></tr>');
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
                        <td>${statusBadge}</td>
                        <td>
                            <div class="participants-actions-row">
                                <button type="button"
                                        class="button button-small toggle-participant-status ${toggleClass}"
                                        data-participant-id="${p.id}"
                                        data-is-active="${p.is_active ? '1' : '0'}"
                                        title="${toggleText}">
                                    ${p.is_active ? '�' : '�'}
                                </button>
                                <button type="button" class="button button-small delete-participant-btn" data-participant-id="${p.id}" title="Eliminar">
                                    🗑️
                                </button>
                                <div class="resend-email-wrapper" style="position: relative; display: inline-block;">
                                    <button type="button" class="button button-small resend-email-btn" title="Reenviar email">
                                        ✉️
                                    </button>
                                    <div class="resend-email-dropdown" style="display:none; position:absolute; right:0; top:100%; background:#fff; border:1px solid #ccc; border-radius:4px; box-shadow:0 2px 5px rgba(0,0,0,0.15); z-index:1000; min-width:180px;">
                                        <a href="#" class="resend-email-option" data-participant-id="${p.id}" data-email-type="magic_link" data-participant-email="${p.email}" style="display:block; padding:8px 12px; text-decoration:none; color:#333;">
                                            🔗 Magic Link
                                        </a>
                                        <a href="#" class="resend-email-option" data-participant-id="${p.id}" data-email-type="welcome" data-participant-email="${p.email}" style="display:block; padding:8px 12px; text-decoration:none; color:#333;">
                                            👋 Bienvenida
                                        </a>
                                        <a href="#" class="resend-email-option" data-participant-id="${p.id}" data-email-type="reminder" data-participant-email="${p.email}" style="display:block; padding:8px 12px; text-decoration:none; color:#333;">
                                            ⏰ Recordatorio
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
