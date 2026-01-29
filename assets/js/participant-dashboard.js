/**
 * EIPSI Forms - Participant Dashboard JavaScript
 *
 * @package EIPSI_Forms
 * @since 1.6.0
 */

(function($) {
    'use strict';

    /**
     * Participant Dashboard Handler
     */
    window.EIPSI_Participant_Dashboard = {
        
        /**
         * Initialize dashboard functionality
         */
        init: function() {
            this.bindEvents();
            this.initTooltips();
            console.log('EIPSI Participant Dashboard initialized');
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            var self = this;

            // Logout button handler
            $('#eipsi-logout-button').on('click', function(e) {
                e.preventDefault();
                self.handleLogout($(this));
            });

            // Respond now button handler (optional - for tracking)
            $('.eipsi-respond-now').on('click', function(e) {
                self.handleRespondNow($(this));
            });

            // Contact link handler
            $('.eipsi-link-contact').on('click', function(e) {
                self.handleContact($(this));
            });

            // Status badge hover effects
            $('.eipsi-status-badge').on('mouseenter', function() {
                self.showStatusTooltip($(this));
            }).on('mouseleave', function() {
                self.hideTooltip();
            });
        },

        /**
         * Handle logout functionality
         * @param {jQuery} $button The logout button
         */
        handleLogout: function($button) {
            var self = this;
            var nonce = $button.data('nonce') || '';
            var confirmMessage = eipsiParticipantDashboardL10n?.confirm_logout || '¿Estás seguro de que quieres cerrar sesión?';

            // Confirm logout
            if (!confirm(confirmMessage)) {
                return;
            }

            // Show loading state
            var originalText = $button.text();
            var loadingText = eipsiParticipantDashboardL10n?.logging_out || 'Cerrando sesión...';
            $button.prop('disabled', true).text(loadingText);

            // Make AJAX request
            $.ajax({
                url: eipsiParticipantDashboardL10n?.ajaxUrl || ajaxurl || '/wp-admin/admin-ajax.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'eipsi_participant_logout',
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        self.showMessage(
                            eipsiParticipantDashboardL10n?.logout_success || 'Sesión cerrada correctamente',
                            'success'
                        );

                        // Redirect after short delay
                        setTimeout(function() {
                            var redirectUrl = eipsiParticipantDashboardL10n?.loginPageUrl || window.location.href;
                            window.location.href = redirectUrl;
                        }, 1000);
                    } else {
                        // Show error message
                        self.showMessage(
                            response.data?.message || eipsiParticipantDashboardL10n?.logout_error || 'Error al cerrar sesión',
                            'error'
                        );
                        
                        // Reset button state
                        $button.prop('disabled', false).text(originalText);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Logout error:', error);
                    self.showMessage(
                        eipsiParticipantDashboardL10n?.logout_error || 'Error al cerrar sesión',
                        'error'
                    );
                    
                    // Reset button state
                    $button.prop('disabled', false).text(originalText);
                }
            });
        },

        /**
         * Handle respond now button click
         * @param {jQuery} $button The respond button
         */
        handleRespondNow: function($button) {
            // Add loading state
            $button.addClass('eipsi-loading');
            
            // Track the click event
            if (typeof eipsiTracking !== 'undefined' && eipsiTracking.trackEvent) {
                eipsiTracking.trackEvent('dashboard_respond_now_clicked', {
                    wave_id: $button.attr('href').match(/wave_id=(\d+)/)?.[1] || 'unknown',
                    survey_id: $button.attr('href').match(/survey_id=(\d+)/)?.[1] || 'unknown'
                });
            }
        },

        /**
         * Handle contact link click
         * @param {jQuery} $link The contact link
         */
        handleContact: function($link) {
            // Track the contact attempt
            if (typeof eipsiTracking !== 'undefined' && eipsiTracking.trackEvent) {
                eipsiTracking.trackEvent('dashboard_contact_clicked');
            }
        },

        /**
         * Show status tooltip
         * @param {jQuery} $badge The status badge
         */
        showStatusTooltip: function($badge) {
            var status = $badge.hasClass('status-completed') ? 'completed' :
                        $badge.hasClass('status-pending') ? 'pending' : 'not-started';
            
            var messages = {
                'completed': eipsiParticipantDashboardL10n?.status_completed_tooltip || 'Esta toma fue completada exitosamente',
                'pending': eipsiParticipantDashboardL10n?.status_pending_tooltip || 'Esta toma está pendiente o en progreso',
                'not-started': eipsiParticipantDashboardL10n?.status_not_started_tooltip || 'Esta toma aún no ha sido iniciada'
            };

            this.showTooltip(messages[status], $badge);
        },

        /**
         * Show tooltip
         * @param {string} message The tooltip message
         * @param {jQuery} $element The element to attach tooltip to
         */
        showTooltip: function(message, $element) {
            var $tooltip = $('<div class="eipsi-tooltip">' + message + '</div>');
            $('body').append($tooltip);

            var offset = $element.offset();
            var width = $element.outerWidth();
            var height = $element.outerHeight();
            var tooltipHeight = $tooltip.outerHeight();

            $tooltip.css({
                left: offset.left + (width / 2) - ($tooltip.outerWidth() / 2),
                top: offset.top - tooltipHeight - 10,
                position: 'absolute',
                zIndex: 1000
            });

            this.currentTooltip = $tooltip;
        },

        /**
         * Hide tooltip
         */
        hideTooltip: function() {
            if (this.currentTooltip) {
                this.currentTooltip.remove();
                this.currentTooltip = null;
            }
        },

        /**
         * Initialize tooltips
         */
        initTooltips: function() {
            // Create tooltip CSS if not exists
            if ($('#eipsi-tooltip-css').length === 0) {
                var tooltipCSS = `
                    <style id="eipsi-tooltip-css">
                        .eipsi-tooltip {
                            background: var(--eipsi-dashboard-text);
                            color: var(--eipsi-dashboard-bg);
                            padding: 0.5rem 0.75rem;
                            border-radius: 4px;
                            font-size: 0.85rem;
                            white-space: nowrap;
                            pointer-events: none;
                        }
                        .eipsi-tooltip::after {
                            content: '';
                            position: absolute;
                            top: 100%;
                            left: 50%;
                            margin-left: -5px;
                            border: 5px solid transparent;
                            border-top-color: var(--eipsi-dashboard-text);
                        }
                    </style>
                `;
                $('head').append(tooltipCSS);
            }
        },

        /**
         * Show message to user
         * @param {string} message The message to show
         * @param {string} type Message type (success, error, warning)
         */
        showMessage: function(message, type) {
            var $message = $('<div class="eipsi-dashboard-message eipsi-message-' + type + '">' + message + '</div>');
            
            // Add message CSS if not exists
            if ($('#eipsi-message-css').length === 0) {
                var messageCSS = `
                    <style id="eipsi-message-css">
                        .eipsi-dashboard-message {
                            position: fixed;
                            top: 20px;
                            right: 20px;
                            padding: 1rem 1.5rem;
                            border-radius: var(--eipsi-border-radius);
                            font-weight: 600;
                            z-index: 10000;
                            animation: eipsi-slide-in 0.3s ease;
                            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                        }
                        .eipsi-message-success {
                            background: var(--eipsi-success-color);
                            color: white;
                        }
                        .eipsi-message-error {
                            background: var(--eipsi-danger-color);
                            color: white;
                        }
                        .eipsi-message-warning {
                            background: var(--eipsi-warning-color);
                            color: white;
                        }
                        @keyframes eipsi-slide-in {
                            from { transform: translateX(100%); opacity: 0; }
                            to { transform: translateX(0); opacity: 1; }
                        }
                    </style>
                `;
                $('head').append(messageCSS);
            }

            $('body').append($message);

            // Auto remove after 3 seconds
            setTimeout(function() {
                $message.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
        }
    };

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        window.EIPSI_Participant_Dashboard.init();
    });

})(jQuery);