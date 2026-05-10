/**
 * Participant Dashboard Auto-Refresh System
 * 
 * This script implements intelligent auto-refresh for the participant dashboard:
 * 1. Auto-refresh when countdown reaches 0
 * 2. Periodic polling to detect backend state changes
 * 3. User notification before refresh
 */

(function($) {
    'use strict';
    
    const AutoRefresh = {
        config: {
            pollInterval: 30000, // Check every 30 seconds
            countdownCheckInterval: 1000, // Check countdown every second
            autoRefreshDelay: 3000, // Wait 3 seconds before auto-refresh
            showNotification: true,
        },
        
        state: {
            currentWaveId: null,
            currentWaveStatus: null,
            countdownTarget: null,
            pollTimer: null,
            countdownTimer: null,
            isRefreshing: false,
        },
        
        /**
         * Initialize auto-refresh system
         */
        init: function() {
            console.log('[EIPSI Auto-Refresh] Initializing...');
            
            // Extract current state from page
            this.extractCurrentState();
            
            // Start polling for state changes
            if (this.state.currentWaveId) {
                this.startPolling();
            }
            
            // Start countdown monitoring
            if (this.state.countdownTarget) {
                this.startCountdownMonitoring();
            }
            
            console.log('[EIPSI Auto-Refresh] Initialized', this.state);
        },
        
        /**
         * Extract current wave state from the page
         */
        extractCurrentState: function() {
            // Get current wave ID from data attribute or hidden field
            const $nextWave = $('.eipsi-wave-card.next-wave, .eipsi-wave-item.active');
            if ($nextWave.length) {
                this.state.currentWaveId = $nextWave.data('wave-id');
                this.state.currentWaveStatus = $nextWave.data('wave-status') || 'pending';
            }
            
            // Get countdown target timestamp
            const $countdown = $('.eipsi-countdown[data-target-timestamp]');
            if ($countdown.length) {
                this.state.countdownTarget = parseInt($countdown.data('target-timestamp'));
                console.log('[EIPSI Auto-Refresh] Countdown target:', new Date(this.state.countdownTarget * 1000));
            }
        },
        
        /**
         * Start polling for backend state changes
         */
        startPolling: function() {
            console.log('[EIPSI Auto-Refresh] Starting polling (every ' + (this.config.pollInterval / 1000) + 's)');
            
            // Clear existing timer
            if (this.state.pollTimer) {
                clearInterval(this.state.pollTimer);
            }
            
            // Poll immediately, then at intervals
            this.checkBackendState();
            
            this.state.pollTimer = setInterval(() => {
                this.checkBackendState();
            }, this.config.pollInterval);
        },
        
        /**
         * Check backend state via AJAX
         */
        checkBackendState: function() {
            if (this.state.isRefreshing) {
                return;
            }
            
            console.log('[EIPSI Auto-Refresh] Checking backend state...');
            
            $.ajax({
                url: eipsiAutoRefresh.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'eipsi_check_wave_state',
                    wave_id: this.state.currentWaveId,
                    nonce: eipsiAutoRefresh.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.handleStateResponse(response.data);
                    }
                },
                error: (xhr, status, error) => {
                    console.error('[EIPSI Auto-Refresh] Poll error:', error);
                }
            });
        },
        
        /**
         * Handle state response from backend
         */
        handleStateResponse: function(data) {
            console.log('[EIPSI Auto-Refresh] Backend state:', data);
            
            // Check if wave status changed
            if (data.status !== this.state.currentWaveStatus) {
                console.log('[EIPSI Auto-Refresh] Status changed:', this.state.currentWaveStatus, '→', data.status);
                this.triggerRefresh('Estado de la ola cambió: ' + data.status);
                return;
            }
            
            // Check if wave became available
            if (data.is_locked === false && this.state.countdownTarget) {
                console.log('[EIPSI Auto-Refresh] Wave unlocked!');
                this.triggerRefresh('¡La ola ya está disponible!');
                return;
            }
            
            // Check if wave was skipped
            if (data.was_skipped) {
                console.log('[EIPSI Auto-Refresh] Wave was skipped');
                this.triggerRefresh('Esta ola fue omitida. Mostrando siguiente ola...');
                return;
            }
            
            // Check if next wave changed
            if (data.next_wave_id && data.next_wave_id !== this.state.currentWaveId) {
                console.log('[EIPSI Auto-Refresh] Next wave changed:', this.state.currentWaveId, '→', data.next_wave_id);
                this.triggerRefresh('Siguiente ola disponible');
                return;
            }
        },
        
        /**
         * Start countdown monitoring
         */
        startCountdownMonitoring: function() {
            console.log('[EIPSI Auto-Refresh] Starting countdown monitoring');
            
            if (this.state.countdownTimer) {
                clearInterval(this.state.countdownTimer);
            }
            
            this.state.countdownTimer = setInterval(() => {
                this.checkCountdown();
            }, this.config.countdownCheckInterval);
        },
        
        /**
         * Check if countdown reached 0
         */
        checkCountdown: function() {
            if (!this.state.countdownTarget || this.state.isRefreshing) {
                return;
            }
            
            const now = Math.floor(Date.now() / 1000);
            const remaining = this.state.countdownTarget - now;
            
            // If countdown reached 0 or passed
            if (remaining <= 0) {
                console.log('[EIPSI Auto-Refresh] Countdown reached 0!');
                clearInterval(this.state.countdownTimer);
                this.triggerRefresh('¡El tiempo de espera terminó!', true);
            }
        },
        
        /**
         * Trigger page refresh with notification
         */
        triggerRefresh: function(message, immediate = false) {
            if (this.state.isRefreshing) {
                return;
            }
            
            this.state.isRefreshing = true;
            
            // Stop polling
            if (this.state.pollTimer) {
                clearInterval(this.state.pollTimer);
            }
            if (this.state.countdownTimer) {
                clearInterval(this.state.countdownTimer);
            }
            
            console.log('[EIPSI Auto-Refresh] Triggering refresh:', message);
            
            if (this.config.showNotification) {
                this.showRefreshNotification(message, immediate);
            } else {
                this.performRefresh();
            }
        },
        
        /**
         * Show refresh notification to user
         */
        showRefreshNotification: function(message, immediate = false) {
            const delay = immediate ? 0 : this.config.autoRefreshDelay;
            const seconds = Math.ceil(delay / 1000);
            
            // Create notification element
            const $notification = $('<div>', {
                class: 'eipsi-auto-refresh-notification',
                html: `
                    <div class="eipsi-notification-content">
                        <div class="eipsi-notification-icon">🔄</div>
                        <div class="eipsi-notification-message">
                            <strong>${message}</strong>
                            <p>${immediate ? 'Actualizando página...' : 'Actualizando en ' + seconds + ' segundos...'}</p>
                        </div>
                        ${!immediate ? '<button class="eipsi-refresh-now">Actualizar ahora</button>' : ''}
                    </div>
                `
            });
            
            // Add styles
            if (!$('#eipsi-auto-refresh-styles').length) {
                $('<style id="eipsi-auto-refresh-styles">').text(`
                    .eipsi-auto-refresh-notification {
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        background: white;
                        border-radius: 8px;
                        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                        padding: 20px;
                        z-index: 9999;
                        max-width: 400px;
                        animation: slideIn 0.3s ease-out;
                    }
                    .eipsi-notification-content {
                        display: flex;
                        align-items: center;
                        gap: 16px;
                    }
                    .eipsi-notification-icon {
                        font-size: 32px;
                        animation: spin 2s linear infinite;
                    }
                    .eipsi-notification-message strong {
                        display: block;
                        margin-bottom: 4px;
                        color: #333;
                    }
                    .eipsi-notification-message p {
                        margin: 0;
                        color: #666;
                        font-size: 14px;
                    }
                    .eipsi-refresh-now {
                        margin-top: 12px;
                        padding: 8px 16px;
                        background: #2271b1;
                        color: white;
                        border: none;
                        border-radius: 4px;
                        cursor: pointer;
                        font-size: 14px;
                        white-space: nowrap;
                    }
                    .eipsi-refresh-now:hover {
                        background: #135e96;
                    }
                    @keyframes slideIn {
                        from {
                            transform: translateX(400px);
                            opacity: 0;
                        }
                        to {
                            transform: translateX(0);
                            opacity: 1;
                        }
                    }
                    @keyframes spin {
                        from { transform: rotate(0deg); }
                        to { transform: rotate(360deg); }
                    }
                `).appendTo('head');
            }
            
            // Add to page
            $('body').append($notification);
            
            // Handle manual refresh button
            $notification.find('.eipsi-refresh-now').on('click', () => {
                this.performRefresh();
            });
            
            // Auto-refresh after delay
            if (delay > 0) {
                setTimeout(() => {
                    this.performRefresh();
                }, delay);
            } else {
                this.performRefresh();
            }
        },
        
        /**
         * Perform actual page refresh
         */
        performRefresh: function() {
            console.log('[EIPSI Auto-Refresh] Refreshing page...');
            window.location.reload();
        },
        
        /**
         * Stop all timers (for cleanup)
         */
        stop: function() {
            if (this.state.pollTimer) {
                clearInterval(this.state.pollTimer);
            }
            if (this.state.countdownTimer) {
                clearInterval(this.state.countdownTimer);
            }
            console.log('[EIPSI Auto-Refresh] Stopped');
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        // Only run on participant dashboard pages
        if ($('.eipsi-longitudinal-study').length || $('.eipsi-participant-dashboard').length) {
            AutoRefresh.init();
        }
    });
    
    // Expose to window for debugging
    window.EIPSIAutoRefresh = AutoRefresh;
    
})(jQuery);
