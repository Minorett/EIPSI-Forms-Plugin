/**
 * EIPSI Forms - Participant Dashboard JavaScript (Enhanced v1.6.0)
 * 
 * Features:
 * - Logout functionality
 * - Real-time updates
 * - Wave status interactions
 * - Loading states
 * 
 * @package EIPSI_Forms
 * @since 1.6.0
 */

(function($) {
    'use strict';

    // Configuration
    const config = {
        ajaxUrl: window.eipsiParticipantDashboardL10n?.ajaxUrl || '/wp-admin/admin-ajax.php',
        nonce: window.eipsiParticipantDashboardL10n?.nonce || '',
        strings: window.eipsiParticipantDashboardL10n?.strings || {
            confirm_logout: '¿Estás seguro de que quieres cerrar sesión?',
            logging_out: 'Cerrando sesión...',
            logout_success: 'Sesión cerrada correctamente',
            logout_error: 'Error al cerrar sesión'
        }
    };

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        initLogout();
        initWaveInteractions();
        initProgressAnimation();
        initCountdown();
    });

    /**
     * Initialize logout functionality
     */
    function initLogout() {
        $(document).on('click', '#eipsi-logout-button', function(e) {
            e.preventDefault();
            
            const $btn = $(this);
            const originalText = $btn.html();
            
            // Confirm logout
            if (!confirm(config.strings.confirm_logout)) {
                return;
            }
            
            // Show loading state
            $btn.prop('disabled', true).html('<span class="btn-icon">⏳</span> ' + config.strings.logging_out);
            
            $.ajax({
                url: config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'eipsi_participant_logout',
                    nonce: $btn.data('nonce') || config.nonce
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        showNotification(config.strings.logout_success, 'success');
                        
                        // Redirect after a brief delay
                        setTimeout(function() {
                            if (response.data.redirect) {
                                window.location.href = response.data.redirect;
                            } else {
                                window.location.reload();
                            }
                        }, 500);
                    } else {
                        showNotification(response.data?.message || config.strings.logout_error, 'error');
                        $btn.prop('disabled', false).html(originalText);
                    }
                },
                error: function() {
                    showNotification(config.strings.logout_error, 'error');
                    $btn.prop('disabled', false).html(originalText);
                }
            });
        });
    }

    /**
     * Initialize wave interactions
     */
    function initWaveInteractions() {
        // Highlight current/next wave row
        $('.eipsi-waves-table tbody tr').each(function() {
            const $row = $(this);
            if ($row.hasClass('status-pending') || $row.hasClass('status-in-progress')) {
                $row.addClass('is-next-wave');
            }
        });
        
        // Add click handler for wave rows (optional navigation)
        $(document).on('click', '.eipsi-waves-table tbody tr', function() {
            const $row = $(this);
            const $link = $row.find('a');
            
            if ($link.length) {
                window.location.href = $link.attr('href');
            }
        });
    }

    /**
     * Initialize progress bar animation
     */
    function initProgressAnimation() {
        const $progressFill = $('.eipsi-progress-fill');
        
        if ($progressFill.length) {
            // Animate progress bar on load
            const targetWidth = $progressFill.css('width');
            $progressFill.css('width', '0');
            
            setTimeout(function() {
                $progressFill.css('width', targetWidth);
            }, 300);
        }
        
        // Animate step indicators
        $('.eipsi-step.completed').each(function(index) {
            const $step = $(this);
            setTimeout(function() {
                $step.addClass('animate');
            }, index * 200);
        });
    }

    /**
     * Initialize countdown timers for locked waves
     */
    function initCountdown() {
        document.querySelectorAll('.eipsi-countdown').forEach(renderCountdown);
    }

    /**
     * Render countdown for a single element
     */
    function renderCountdown(el) {
        var ts = parseInt(el.dataset.availableTimestamp, 10) * 1000;
        var unit = el.dataset.unit;
        var numEl = el.querySelector('.eipsi-countdown-num');
        var unitEl = el.querySelector('.eipsi-countdown-unit');

        function update() {
            var diff = ts - Date.now();
            if (diff <= 0) {
                numEl.textContent = window.eipsiCountdownStrings?.available || 'Ya disponible';
                unitEl.textContent = '';
                el.closest('.eipsi-cta--locked').classList.add('eipsi-cta--ready');
                return;
            }

            var totalSecs = Math.floor(diff / 1000);
            var totalMins = Math.floor(diff / 60000);
            var totalHours = Math.floor(diff / 3600000);
            var totalDays = Math.floor(diff / 86400000);

            if (totalMins < 60) {
                // Timer en vivo: MM:SS
                var m = Math.floor(totalSecs / 60);
                var s = totalSecs % 60;
                numEl.textContent = m + ':' + (s < 10 ? '0' : '') + s;
                unitEl.textContent = window.eipsiCountdownStrings?.mins || 'min : seg';
                el.closest('.eipsi-dash-cta').style.setProperty('--cta-color', '#856404');
                el.closest('.eipsi-dash-cta').style.setProperty('--cta-bg', '#fff8e5');
                el.closest('.eipsi-dash-cta').style.setProperty('--cta-border', '#ffc107');
                setTimeout(update, 1000);
            } else if (totalHours < 48) {
                // Estático: Xh Ym
                var h = totalHours;
                var mins = Math.floor((diff % 3600000) / 60000);
                numEl.textContent = h + 'h ' + mins + 'm';
                unitEl.textContent = window.eipsiCountdownStrings?.hours || 'horas';
                el.closest('.eipsi-dash-cta').style.setProperty('--cta-color', '#856404');
                el.closest('.eipsi-dash-cta').style.setProperty('--cta-bg', '#fff8e5');
                el.closest('.eipsi-dash-cta').style.setProperty('--cta-border', '#ffc107');
            } else {
                // Días + fecha aproximada
                var fecha = new Date(ts);
                var meses = window.eipsiCountdownStrings?.months || ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
                var fechaStr = '~' + fecha.getDate() + ' ' + meses[fecha.getMonth()] + ' ' + fecha.getFullYear();
                numEl.textContent = totalDays + ' días';
                unitEl.textContent = fechaStr;
                // Color neutro cuando no hay urgencia
                el.closest('.eipsi-dash-cta').style.setProperty('--cta-color', '#64748b');
                el.closest('.eipsi-dash-cta').style.setProperty('--cta-bg', '#f8f9fa');
                el.closest('.eipsi-dash-cta').style.setProperty('--cta-border', '#e2e8f0');
            }
        }

        update();
    }

    /**
     * Show notification
     */
    function showNotification(message, type) {
        // Remove existing notifications
        $('.eipsi-notification').remove();
        
        const $notification = $('<div class="eipsi-notification eipsi-notification--' + type + '">' + message + '</div>');
        
        $('body').append($notification);
        
        // Animate in
        $notification.css({
            transform: 'translateY(-100%)',
            opacity: 0
        }).animate({
            transform: 'translateY(0)',
            opacity: 1
        }, 300);
        
        // Auto-remove
        setTimeout(function() {
            $notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }

    /**
     * Refresh dashboard data (can be called periodically)
     */
    function refreshDashboard() {
        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'eipsi_get_participant_dashboard',
                nonce: config.nonce
            },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data) {
                    updateDashboardUI(response.data);
                }
            }
        });
    }

    /**
     * Update dashboard UI with new data
     */
    function updateDashboardUI(data) {
        // Update progress bar
        if (data.progress_percentage !== undefined) {
            $('.eipsi-progress-fill').css('width', data.progress_percentage + '%');
            $('.eipsi-progress-percentage').text(data.progress_percentage + '%');
        }
        
        // Update stats
        if (data.completed_waves !== undefined) {
            $('.stat-completed').text('✅ ' + data.completed_waves + ' completadas');
        }
        if (data.pending_waves !== undefined) {
            $('.stat-pending').text('⏳ ' + data.pending_waves + ' pendientes');
        }
    }

    // Expose public API
    window.EIPSIDashboard = {
        refresh: refreshDashboard,
        showNotification: showNotification
    };

})(jQuery);
