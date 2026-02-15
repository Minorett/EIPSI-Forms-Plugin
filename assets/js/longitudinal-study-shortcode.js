/**
 * EIPSI Forms - Longitudinal Study Shortcode JavaScript
 * 
 * Handles copy functionality for shortcodes and shareable links
 * 
 * @package EIPSI_Forms
 * @since 1.5.0
 */

(function($) {
    'use strict';

    // Wait for DOM ready
    $(document).ready(function() {
        initLongitudinalStudyShortcode();
    });

    /**
     * Initialize the longitudinal study shortcode functionality
     */
    function initLongitudinalStudyShortcode() {
        // Bind copy buttons
        bindCopyButtons();
        
        // Handle shareable URL parameter for auto-navigation
        handleUrlParameters();
        
        // Initialize tooltips if available
        initTooltips();
    }

    /**
     * Bind click events to copy buttons
     */
    function bindCopyButtons() {
        $('.eipsi-longitudinal-study').on('click', '.eipsi-copy-btn', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var textToCopy = $btn.data('copy');
            var isShortcode = $btn.closest('.eipsi-share-option').find('code').length > 0;
            
            // Attempt to copy to clipboard
            copyToClipboard(textToCopy).then(function() {
                // Show success feedback
                showCopyFeedback(isShortcode ? 
                    eipsiLongitudinalStudyL10n.strings.copied : 
                    eipsiLongitudinalStudyL10n.strings.linkCopied
                );
                
                // Update button state temporarily
                var originalText = $btn.find('.copy-text').text();
                $btn.addClass('copied');
                $btn.find('.copy-text').text('✓');
                $btn.find('.dashicons').removeClass('dashicons-clipboard').addClass('dashicons-yes');
                
                // Revert button state after 2 seconds
                setTimeout(function() {
                    $btn.removeClass('copied');
                    $btn.find('.copy-text').text(originalText);
                    $btn.find('.dashicons').removeClass('dashicons-yes').addClass('dashicons-clipboard');
                }, 2000);
                
                // Track copy event (optional analytics)
                trackCopyEvent(isShortcode ? 'shortcode' : 'url');
                
            }).catch(function(err) {
                console.error('Failed to copy:', err);
                showCopyFeedback(eipsiLongitudinalStudyL10n.strings.copyError, 'error');
            });
        });
    }

    /**
     * Copy text to clipboard using modern API with fallback
     * 
     * @param {string} text Text to copy
     * @returns {Promise} Promise that resolves on success
     */
    function copyToClipboard(text) {
        // Try modern Clipboard API first
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(text);
        }
        
        // Fallback for older browsers
        return new Promise(function(resolve, reject) {
            var $tempInput = $('<textarea>');
            $tempInput.css({
                position: 'absolute',
                left: '-9999px',
                top: '0'
            });
            $tempInput.val(text);
            $('body').append($tempInput);
            
            try {
                $tempInput.select();
                $tempInput[0].setSelectionRange(0, 99999); // For mobile devices
                
                var success = document.execCommand('copy');
                $tempInput.remove();
                
                if (success) {
                    resolve();
                } else {
                    reject(new Error('execCommand failed'));
                }
            } catch (err) {
                $tempInput.remove();
                reject(err);
            }
        });
    }

    /**
     * Show copy feedback toast notification
     * 
     * @param {string} message Message to display
     * @param {string} type Type of feedback: 'success' or 'error'
     */
    function showCopyFeedback(message, type) {
        type = type || 'success';
        
        var $feedback = $('.eipsi-copy-feedback');
        
        // Update message
        $feedback.find('.feedback-text').text(message);
        
        // Update styling based on type
        if (type === 'error') {
            $feedback.css('background', '#dc3232');
            $feedback.find('.dashicons').removeClass('dashicons-yes').addClass('dashicons-no');
        } else {
            $feedback.css('background', '#00a32a');
            $feedback.find('.dashicons').removeClass('dashicons-no').addClass('dashicons-yes');
        }
        
        // Show feedback
        $feedback.stop(true, true).fadeIn(200);
        
        // Hide after 3 seconds
        setTimeout(function() {
            $feedback.fadeOut(300);
        }, 3000);
    }

    /**
     * Handle URL parameters for auto-navigation to specific wave
     */
    function handleUrlParameters() {
        var urlParams = new URLSearchParams(window.location.search);
        var studyId = urlParams.get('eipsi_study');
        var waveIndex = urlParams.get('wave');
        
        if (studyId && waveIndex) {
            // Find the target wave card
            var $targetWave = $('.eipsi-wave-card').filter(function() {
                var cardWaveIndex = $(this).find('.wave-index').text().replace('T', '');
                return cardWaveIndex === waveIndex;
            });
            
            if ($targetWave.length) {
                // Smooth scroll to wave
                $('html, body').animate({
                    scrollTop: $targetWave.offset().top - 100
                }, 500);
                
                // Highlight the wave temporarily
                $targetWave.addClass('eipsi-wave-highlight');
                setTimeout(function() {
                    $targetWave.removeClass('eipsi-wave-highlight');
                }, 3000);
            }
        }
    }

    /**
     * Initialize tooltips (if jQuery UI or similar is available)
     */
    function initTooltips() {
        // Check if jQuery UI tooltip is available
        if ($.fn.tooltip) {
            $('.eipsi-copy-btn').tooltip({
                position: { my: 'center bottom', at: 'center top-10' },
                show: { duration: 200 },
                hide: { duration: 100 }
            });
        }
    }

    /**
     * Track copy event for analytics (optional)
     * 
     * @param {string} type Type of copy event
     */
    function trackCopyEvent(type) {
        // If analytics is available, track the event
        if (typeof gtag !== 'undefined') {
            gtag('event', 'copy_longitudinal_study', {
                'event_category': 'engagement',
                'event_label': type,
                'value': 1
            });
        }
        
        // WordPress hook for custom tracking
        if (typeof wp !== 'undefined' && wp.hooks) {
            wp.hooks.doAction('eipsi.longitudinalStudyCopied', type);
        }
    }

    /**
     * Public API for external scripts
     */
    window.EIPSILongitudinalStudy = {
        /**
         * Copy shortcode to clipboard
         * 
         * @param {number} studyId Study ID
         * @param {number} waveIndex Optional wave index
         * @param {number} timeLimit Optional time limit
         */
        copyShortcode: function(studyId, waveIndex, timeLimit) {
            var shortcode = '[eipsi_longitudinal_study id="' + studyId + '"';
            if (waveIndex) {
                shortcode += ' wave="' + waveIndex + '"';
            }
            if (timeLimit) {
                shortcode += ' time_limit="' + timeLimit + '"';
            }
            shortcode += ']';
            
            copyToClipboard(shortcode).then(function() {
                showCopyFeedback(eipsiLongitudinalStudyL10n.strings.copied);
            });
        },
        
        /**
         * Generate and copy shareable URL
         * 
         * @param {number} studyId Study ID
         * @param {string} baseUrl Base URL
         * @param {number} waveIndex Optional wave index
         */
        copyShareableUrl: function(studyId, baseUrl, waveIndex) {
            var url = baseUrl || window.location.href;
            var params = new URLSearchParams();
            params.append('eipsi_study', studyId);
            if (waveIndex) {
                params.append('wave', waveIndex);
            }
            
            var shareableUrl = url + (url.indexOf('?') > -1 ? '&' : '?') + params.toString();
            
            copyToClipboard(shareableUrl).then(function() {
                showCopyFeedback(eipsiLongitudinalStudyL10n.strings.linkCopied);
            });
        },
        
        /**
         * Scroll to specific wave
         * 
         * @param {number} waveIndex Wave index to scroll to
         */
        scrollToWave: function(waveIndex) {
            var $targetWave = $('.eipsi-wave-card').filter(function() {
                var cardWaveIndex = $(this).find('.wave-index').text().replace('T', '');
                return parseInt(cardWaveIndex) === parseInt(waveIndex);
            });
            
            if ($targetWave.length) {
                $('html, body').animate({
                    scrollTop: $targetWave.offset().top - 100
                }, 500);
            }
        }
    };

})(jQuery);

// Add CSS for wave highlight animation
(function() {
    var style = document.createElement('style');
    style.textContent = `
        @keyframes eipsi-wave-pulse {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(59, 108, 170, 0.4);
                border-color: #3B6CAA;
            }
            50% {
                box-shadow: 0 0 0 10px rgba(59, 108, 170, 0);
                border-color: #2d5a8e;
            }
        }
        
        .eipsi-wave-highlight {
            animation: eipsi-wave-pulse 1.5s ease-in-out 2;
        }
    `;
    document.head.appendChild(style);
})();