/**
 * EIPSI Forms - Participant UX Enhanced JavaScript (v1.7.0)
 *
 * Mejoras de UX centradas en empatía y experiencia humana:
 * - Real-time progress tracking
 * - Celebration message system
 * - Contextual help toggles
 * - Smooth animations
 * - Mobile optimizations
 *
 * @package EIPSI_Forms
 * since 1.7.0
 */

(function($) {
    'use strict';

    // Configuration
    const config = {
        animationDuration: 300,
        celebrationDelay: 500,
        progressUpdateInterval: 500,
        strings: {
            progress: 'Progreso',
            questionsAnswered: 'preguntas respondidas',
            of: 'de',
            celebrate: '¡Genial!',
            complete: '¡Felicidades! Completaste todas las preguntas',
            sectionComplete: '¡Excelente! Completaste esta sección',
            helpTitle: '¿Por qué preguntamos esto?',
            expandHelp: 'Ver más contexto',
            collapseHelp: 'Mostrar menos'
        }
    };

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        initProgressTracking();
        initCelebrationSystem();
        initContextualHelp();
        initMobileOptimizations();
        initWelcomeAnimations();
    });

    /**
     * Initialize real-time progress tracking
     */
    function initProgressTracking() {
        // Find progress container
        const $progressContainer = $('.eipsi-form-progress-container');
        if (!$progressContainer.length) {
            return;
        }

        // Get total questions from data attribute
        const totalQuestions = parseInt($progressContainer.data('total-questions')) || 0;
        if (!totalQuestions) {
            return;
        }

        // Track form inputs
        trackFormInputs($progressContainer, totalQuestions);
        updateProgressUI($progressContainer, 0, totalQuestions);
    }

    /**
     * Track form inputs for progress
     */
    function trackFormInputs($progressContainer, totalQuestions) {
        const formSelector = $progressContainer.data('form-selector') || '.vas-form';
        const $form = $(formSelector);

        if (!$form.length) {
            return;
        }

        // Listen to input changes
        $form.on('input change', 'input, select, textarea', debounce(function() {
            const answeredCount = countAnsweredQuestions($form);
            updateProgressUI($progressContainer, answeredCount, totalQuestions);
            checkCelebrationTrigger(answeredCount, totalQuestions);
        }, config.progressUpdateInterval));

        // Initial count
        setTimeout(function() {
            const answeredCount = countAnsweredQuestions($form);
            updateProgressUI($progressContainer, answeredCount, totalQuestions);
        }, 100);
    }

    /**
     * Count answered questions
     */
    function countAnsweredQuestions($form) {
        let count = 0;

        // Radio buttons
        $form.find('input[type="radio"]:checked').each(function() {
            const name = $(this).attr('name');
            if (name && !$form.find(`input[name="${name}"]:checked`).first().is($(this))) {
                return; // Skip duplicates
            }
            count++;
        });

        // Checkboxes (count each separately)
        $form.find('input[type="checkbox"]:checked').each(function() {
            count++;
        });

        // Text inputs, selects, textareas
        $form.find('input[type="text"], input[type="email"], input[type="number"], select, textarea').each(function() {
            const value = $(this).val().trim();
            if (value !== '') {
                count++;
            }
        });

        return count;
    }

    /**
     * Update progress UI
     */
    function updateProgressUI($progressContainer, answered, total) {
        const percentage = Math.round((answered / total) * 100);

        // Update progress bar
        const $fill = $progressContainer.find('.eipsi-form-progress__fill');
        $fill.css('width', percentage + '%');

        // Update percentage display
        const $percentage = $progressContainer.find('.eipsi-form-progress__percentage');
        $percentage.text(percentage + '%');

        // Update questions display
        const $questions = $progressContainer.find('.eipsi-form-progress__questions');
        $questions.text(`${answered} ${config.strings.questionsAnswered} ${config.strings.of} ${total}`);

        // Store in localStorage for persistence
        const formId = $progressContainer.data('form-id');
        if (formId) {
            try {
                localStorage.setItem(`eipsi_progress_${formId}`, JSON.stringify({
                    answered: answered,
                    total: total,
                    percentage: percentage,
                    timestamp: Date.now()
                }));
            } catch (e) {
                // Silent fail if localStorage unavailable
            }
        }
    }

    /**
     * Initialize celebration system
     */
    function initCelebrationSystem() {
        // Check for completion celebration
        const $celebration = $('.eipsi-celebration--initial');
        if ($celebration.length) {
            setTimeout(function() {
                $celebration.fadeIn(config.animationDuration);
            }, config.celebrationDelay);
        }
    }

    /**
     * Check if celebration should trigger
     */
    function checkCelebrationTrigger(answered, total) {
        const percentage = (answered / total) * 100;

        // Trigger at 25%, 50%, 75%, 100%
        const milestones = [25, 50, 75, 100];
        milestones.forEach(function(milestone) {
            if (percentage >= milestone && percentage < milestone + 5) {
                showCelebrationMessage(milestone, percentage);
            }
        });

        // Complete celebration
        if (percentage === 100) {
            showCompletionCelebration();
        }
    }

    /**
     * Show celebration message
     */
    function showCelebrationMessage(milestone, percentage) {
        const key = `eipsi_celebration_${milestone}`;
        if (localStorage.getItem(key)) {
            return; // Already shown
        }

        localStorage.setItem(key, 'true');

        let message = '';
        let emoji = '';

        switch (milestone) {
            case 25:
                message = '¡Buen comienzo! Ya respondiste el 25% de las preguntas.';
                emoji = '🌱';
                break;
            case 50:
                message = '¡Genial! Ya completaste la mitad del formulario.';
                emoji = '💪';
                break;
            case 75:
                message = '¡Casi ahí! Solo un poco más para completar.';
                emoji = '🎯';
                break;
            case 100:
                message = '¡Felicidades! Completaste todas las preguntas.';
                emoji = '🌟';
                break;
        }

        if (message) {
            createToastMessage(message, emoji, 'celebration');
        }
    }

    /**
     * Show completion celebration
     */
    function showCompletionCelebration() {
        // Create modal
        const modalHtml = `
            <div class="eipsi-celebration-modal">
                <div class="eipsi-celebration-modal__content">
                    <div class="eipsi-celebration-modal__emoji">🎉</div>
                    <h2 class="eipsi-celebration-modal__title">${config.strings.complete}</h2>
                    <p class="eipsi-celebration-modal__message">
                        Gracias por tu tiempo y honestidad. Tus respuestas nos ayudan
                        a mejorar y a entender mejor cómo funcionan las emociones.
                    </p>
                    <button class="eipsi-celebration-modal__close">
                        Continuar
                    </button>
                </div>
            </div>
        `;

        $('body').append(modalHtml);

        // Animate in
        setTimeout(function() {
            $('.eipsi-celebration-modal').fadeIn(400);
        }, 100);

        // Handle close
        $('.eipsi-celebration-modal__close').on('click', function() {
            $('.eipsi-celebration-modal').fadeOut(400, function() {
                $(this).remove();
            });
        });

        // Auto-close after 5 seconds
        setTimeout(function() {
            $('.eipsi-celebration-modal').fadeOut(400, function() {
                $(this).remove();
            });
        }, 5000);
    }

    /**
     * Initialize contextual help
     */
    function initContextualHelp() {
        $(document).on('click', '.eipsi-contextual-help__header', function() {
            const $header = $(this);
            const $container = $header.closest('.eipsi-contextual-help');
            const $content = $container.find('.eipsi-contextual-help__content');
            const $toggle = $header.find('.eipsi-contextual-help__toggle');

            const isExpanded = $content.hasClass('eipsi-contextual-help__content--expanded');

            if (isExpanded) {
                $content.removeClass('eipsi-contextual-help__content--expanded');
                $toggle.removeClass('eipsi-contextual-help__toggle--expanded');
                $header.attr('aria-expanded', 'false');
            } else {
                $content.addClass('eipsi-contextual-help__content--expanded');
                $toggle.addClass('eipsi-contextual-help__toggle--expanded');
                $header.attr('aria-expanded', 'true');
            }
        });

        // Close on outside click
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.eipsi-contextual-help').length) {
                $('.eipsi-contextual-help__content--expanded')
                    .removeClass('eipsi-contextual-help__content--expanded');
                $('.eipsi-contextual-help__toggle--expanded')
                    .removeClass('eipsi-contextual-help__toggle--expanded');
            }
        });
    }

    /**
     * Initialize mobile optimizations
     */
    function initMobileOptimizations() {
        // Touch-friendly interactions
        if ('ontouchstart' in window) {
            $('body').addClass('eipsi-touch-device');

            // Add ripple effect to buttons
            $('.eipsi-button, .eipsi-gentle-reminder__cta').on('touchstart', function(e) {
                const $btn = $(this);
                const touch = e.originalEvent.touches[0];
                const ripple = $('<span class="eipsi-ripple"></span>');
                const size = Math.max($btn.outerWidth(), $btn.outerHeight());
                const x = touch.clientX - $btn.offset().left - size / 2;
                const y = touch.clientY - $btn.offset().top - size / 2;

                ripple.css({
                    width: size + 'px',
                    height: size + 'px',
                    left: x + 'px',
                    top: y + 'px'
                });

                $btn.append(ripple);

                setTimeout(function() {
                    ripple.remove();
                }, 600);
            });
        }

        // Optimize scroll for sticky progress bar
        const $progressContainer = $('.eipsi-form-progress-container--sticky');
        if ($progressContainer.length) {
            let lastScroll = 0;
            let ticking = false;

            $(window).on('scroll', function() {
                lastScroll = window.pageYOffset;

                if (!ticking) {
                    window.requestAnimationFrame(function() {
                        const threshold = 100;
                        const shouldSticky = lastScroll > threshold;

                        if (shouldSticky) {
                            $progressContainer.addClass('eipsi-sticky');
                        } else {
                            $progressContainer.removeClass('eipsi-sticky');
                        }

                        ticking = false;
                    });

                    ticking = true;
                }
            });
        }
    }

    /**
     * Initialize welcome animations
     */
    function initWelcomeAnimations() {
        const $welcome = $('.eipsi-welcome-message');
        if (!$welcome.length) {
            return;
        }

        // Stagger animation of children
        $welcome.css('opacity', '0');

        setTimeout(function() {
            $welcome.animate({
                opacity: 1
            }, 600);
        }, 200);
    }

    /**
     * Create toast message
     */
    function createToastMessage(message, emoji, type) {
        const toast = $(`
            <div class="eipsi-toast eipsi-toast--${type}">
                <span class="eipsi-toast__emoji">${emoji}</span>
                <span class="eipsi-toast__message">${message}</span>
            </div>
        `);

        $('body').append(toast);

        // Animate in
        toast.css({
            transform: 'translateY(100%)',
            opacity: 0
        }).animate({
            transform: 'translateY(0)',
            opacity: 1
        }, 300);

        // Auto-remove
        setTimeout(function() {
            toast.fadeOut(300, function() {
                $(this).remove();
            });
        }, 4000);
    }

    /**
     * Debounce function
     */
    function debounce(func, wait) {
        let timeout;
        return function() {
            const context = this;
            const args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                func.apply(context, args);
            }, wait);
        };
    }

    /**
     * Check for saved progress
     */
    function loadSavedProgress(formId) {
        try {
            const saved = localStorage.getItem(`eipsi_progress_${formId}`);
            if (saved) {
                const data = JSON.parse(saved);
                const now = Date.now();
                const oneHour = 60 * 60 * 1000;

                // Only restore if less than 1 hour old
                if (now - data.timestamp < oneHour) {
                    return data;
                }
            }
        } catch (e) {
            // Silent fail
        }
        return null;
    }

    /**
     * Clear saved progress
     */
    function clearSavedProgress(formId) {
        try {
            localStorage.removeItem(`eipsi_progress_${formId}`);
        } catch (e) {
            // Silent fail
        }
    }

    // Expose public API
    window.EIPSIParticipantUX = {
        loadSavedProgress: loadSavedProgress,
        clearSavedProgress: clearSavedProgress,
        showCelebration: showCelebrationMessage,
        showToast: createToastMessage
    };

})(jQuery);
