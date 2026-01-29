/**
 * EIPSI Forms - Waves Manager JS
 * 
 * @since 1.4.0
 */

jQuery(document).ready(function($) {
    const api = eipsiWavesManager;
    const $wrap = $('.eipsi-waves-manager-wrap');
    const studyId = $wrap.data('study-id');

    // Modal elements
    const $waveModal = $('#eipsi-wave-modal');
    const $assignModal = $('#eipsi-assign-modal');
    const $waveForm = $('#eipsi-wave-form');

    /**
     * Helper to show messages
     */
    function showMessage(msg, isError = false) {
        const $container = $('#eipsi-message-container');
        const type = isError ? 'error' : 'success';
        $container.html(`<div class="notice notice-${type} is-dismissible"><p>${msg}</p></div>`);
        
        // Auto-dismiss after 5s
        setTimeout(() => $container.empty(), 5000);
    }

    /**
     * Open Wave Modal (Create/Edit)
     */
    $('#eipsi-create-wave-btn').on('click', function() {
        const nextIndex = $(this).data('next-index');
        $waveForm[0].reset();
        $('#wave_id').val('');
        $('#wave_index').val(nextIndex);
        $('#wave-modal-title').text('Crear Nueva Onda');
        $waveModal.css('display', 'flex').fadeIn();
    });

    $('.eipsi-edit-wave-btn').on('click', function() {
        const wave = $(this).data('wave');
        $waveForm[0].reset();
        
        $('#wave_id').val(wave.id);
        $('#wave_name').val(wave.name);
        $('#wave_index').val(wave.wave_index);
        $('#form_id').val(wave.form_id);
        $('#wave_description').val(wave.description);
        
        if (wave.due_date) {
            // Convert to datetime-local format: YYYY-MM-DDTHH:mm
            const date = new Date(wave.due_date);
            const formattedDate = date.toISOString().slice(0, 16);
            $('#due_date').val(formattedDate);
        }

        $('input[name="is_mandatory"]').prop('checked', parseInt(wave.is_mandatory) === 1);
        
        $('#wave-modal-title').text('Editar Onda: ' + wave.name);
        $waveModal.css('display', 'flex').fadeIn();
    });

    /**
     * Save Wave
     */
    $waveForm.on('submit', function(e) {
        e.preventDefault();
        const $btn = $('#save-wave-btn');
        const originalText = $btn.text();
        
        $btn.text(api.strings.loading).prop('disabled', true);

        const formData = new FormData(this);
        formData.append('action', 'eipsi_save_wave');
        formData.append('nonce', api.nonce);

        $.ajax({
            url: api.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showMessage(api.strings.success);
                    location.reload();
                } else {
                    showMessage(response.data || api.strings.error, true);
                    $btn.text(originalText).prop('disabled', false);
                }
            },
            error: function() {
                showMessage(api.strings.error, true);
                $btn.text(originalText).prop('disabled', false);
            }
        });
    });

    /**
     * Delete Wave
     */
    $('.eipsi-delete-wave-btn').on('click', function() {
        if (!confirm(api.strings.confirmDelete)) return;

        const $card = $(this).closest('.eipsi-wave-card');
        const waveId = $card.data('wave-id');

        $.post(api.ajaxUrl, {
            action: 'eipsi_delete_wave',
            wave_id: waveId,
            nonce: api.nonce
        }, function(response) {
            if (response.success) {
                showMessage(api.strings.success);
                $card.fadeOut(() => $card.remove());
            } else {
                showMessage(response.data || api.strings.error, true);
            }
        });
    });

    /**
     * Assign Participants Modal
     */
    let activeWaveId = null;

    $('.eipsi-assign-participants-btn').on('click', function() {
        const $card = $(this).closest('.eipsi-wave-card');
        activeWaveId = $card.data('wave-id');
        const waveName = $card.find('.wave-name').text();

        $('#assign-wave-name').text('(' + waveName + ')');
        $('#available-participants-tbody').html('<tr><td colspan="4" style="text-align:center;">' + api.strings.loading + '</td></tr>');
        $assignModal.css('display', 'flex').fadeIn();

        // Load available participants
        $.get(api.ajaxUrl, {
            action: 'eipsi_get_available_participants',
            study_id: studyId,
            wave_id: activeWaveId,
            nonce: api.nonce
        }, function(response) {
            if (response.success) {
                renderParticipants(response.data);
            } else {
                $('#available-participants-tbody').html('<tr><td colspan="4" style="text-align:center;color:red;">' + (response.data || api.strings.error) + '</td></tr>');
            }
        });
    });

    function renderParticipants(participants) {
        const $tbody = $('#available-participants-tbody');
        $tbody.empty();

        if (participants.length === 0) {
            $tbody.append('<tr><td colspan="4" style="text-align:center;">Todos los participantes activos ya están asignados a esta onda.</td></tr>');
            return;
        }

        participants.forEach(p => {
            $tbody.append(`
                <tr>
                    <td class="check-column"><input type="checkbox" name="participant_ids[]" value="${p.id}"></td>
                    <td>${p.full_name}</td>
                    <td>${p.email}</td>
                    <td><code>${p.participant_id}</code></td>
                </tr>
            `);
        });
    }

    // Master checkbox
    $('#master-participant-check').on('change', function() {
        $('#available-participants-tbody input[type="checkbox"]').prop('checked', this.checked);
    });

    $('#select-all-participants').on('click', () => {
        $('#available-participants-tbody input[type="checkbox"]').prop('checked', true);
        $('#master-participant-check').prop('checked', true);
    });

    $('#deselect-all-participants').on('click', () => {
        $('#available-participants-tbody input[type="checkbox"]').prop('checked', false);
        $('#master-participant-check').prop('checked', false);
    });

    /**
     * Confirm Assignment
     */
    $('#confirm-assign-btn').on('click', function() {
        const selectedIds = $('#available-participants-tbody input[name="participant_ids[]"]:checked').map(function() {
            return this.value;
        }).get();

        if (selectedIds.length === 0) {
            alert(api.strings.noParticipants);
            return;
        }

        if (!confirm(api.strings.confirmAssign)) return;

        const $btn = $(this);
        $btn.text(api.strings.loading).prop('disabled', true);

        $.post(api.ajaxUrl, {
            action: 'eipsi_assign_participants',
            study_id: studyId,
            wave_id: activeWaveId,
            participant_ids: selectedIds,
            nonce: api.nonce
        }, function(response) {
            if (response.success) {
                showMessage(response.data.message || api.strings.success);
                $assignModal.fadeOut();
                location.reload(); // Reload to update stats on cards
            } else {
                showMessage(response.data || api.strings.error, true);
                $btn.text('Asignar Seleccionados').prop('disabled', false);
            }
        });
    });

    /**
     * Send Reminders
     */
    $('.eipsi-send-reminder-btn').on('click', function() {
        const $card = $(this).closest('.eipsi-wave-card');
        const waveId = $card.data('wave-id');

        if (!confirm('¿Enviar recordatorios manuales a todos los participantes pendientes de esta onda?')) return;

        $(this).text('⏳...').prop('disabled', true);

        $.post(api.ajaxUrl, {
            action: 'eipsi_send_reminder',
            wave_id: waveId,
            nonce: api.nonce
        }, (response) => {
            if (response.success) {
                showMessage(response.data.message || api.strings.success);
            } else {
                showMessage(response.data || api.strings.error, true);
            }
            $(this).text('Recordatorio').prop('disabled', false);
        });
    });

    /**
     * Extend Deadline
     */
    $('.eipsi-extend-deadline-btn').on('click', function() {
        const $card = $(this).closest('.eipsi-wave-card');
        const waveId = $card.data('wave-id');
        const newDeadline = prompt('Nueva fecha de vencimiento (YYYY-MM-DD HH:MM):');

        if (!newDeadline) return;

        $.post(api.ajaxUrl, {
            action: 'eipsi_extend_deadline',
            wave_id: waveId,
            new_deadline: newDeadline,
            nonce: api.nonce
        }, (response) => {
            if (response.success) {
                showMessage(api.strings.success);
                location.reload();
            } else {
                showMessage(response.data || api.strings.error, true);
            }
        });
    });

    /**
     * Close Modals
     */
    $('.eipsi-close-modal, .eipsi-close-modal-btn').on('click', function() {
        $(this).closest('.eipsi-modal').fadeOut();
    });

    $(window).on('click', function(e) {
        if ($(e.target).hasClass('eipsi-modal')) {
            $('.eipsi-modal').fadeOut();
        }
    });

    // ESC key support
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            $('.eipsi-modal').fadeOut();
        }
    });
});
