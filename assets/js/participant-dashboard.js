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
