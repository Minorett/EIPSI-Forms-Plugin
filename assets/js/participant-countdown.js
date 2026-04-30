/**
 * EIPSI Participant Countdown Timer
 * Cuenta regresiva visual en tiempo real para el dashboard del participante
 * NO envía emails ni cambia estados - eso lo hace el cron del servidor
 *
 * Fase 4: Migrado a data-target-timestamp + data-countdown-type
 *   - data-countdown-type="until-available" → cuenta hasta apertura (wave pending)
 *   - data-countdown-type="until-expires"   → cuenta hasta expiración (wave available)
 *
 * @package EIPSI_Forms
 * @since 2.5.0
 */

(function() {
    'use strict';

    // Buscar elementos con data-target-timestamp (nuevo atributo único, Fase 4)
    const countdownElements = document.querySelectorAll('[data-target-timestamp]');

    if (!countdownElements.length) return;

    countdownElements.forEach(function(element) {
        const targetTimestamp = parseInt(element.dataset.targetTimestamp, 10);
        const countdownType   = element.dataset.countdownType || 'until-available';

        // Saltar si no hay timestamp válido o es 0 (wave sin fecha de expiración)
        if (!targetTimestamp || isNaN(targetTimestamp) || targetTimestamp === 0) return;

        // Detectar si el timer vive dentro de un .countdown-timer con unidades separadas
        // (hero-card.php usa .countdown-value[data-unit] para días/horas/minutos)
        const unitElements = element.querySelectorAll('.countdown-value[data-unit]');
        const hasUnitDisplay = unitElements.length > 0;

        // Fallback: elemento de texto simple (modo legacy)
        const textElement = !hasUnitDisplay ? (element.querySelector('.countdown-value') || element) : null;

        function pad(n) {
            return String(n).padStart(2, '0');
        }

        function updateCountdown() {
            const now = Math.floor(Date.now() / 1000);
            const remaining = targetTimestamp - now;

            if (remaining <= 0) {
                onExpired();
                return;
            }

            const days    = Math.floor(remaining / 86400);
            const hours   = Math.floor((remaining % 86400) / 3600);
            const minutes = Math.floor((remaining % 3600) / 60);
            const seconds = remaining % 60;

            if (hasUnitDisplay) {
                // Actualizar cada unidad por separado (hero-card)
                unitElements.forEach(function(el) {
                    switch (el.dataset.unit) {
                        case 'days':    el.textContent = days;       break;
                        case 'hours':   el.textContent = pad(hours); break;
                        case 'minutes': el.textContent = pad(minutes); break;
                        case 'seconds': el.textContent = pad(seconds); break;
                    }
                });
            } else if (textElement) {
                // Modo texto compacto (fallback / legacy)
                let text = '';
                if (days > 0) {
                    text = days + 'd ' + hours + 'h ' + minutes + 'm';
                } else if (hours > 0) {
                    text = hours + 'h ' + minutes + 'm ' + seconds + 's';
                } else {
                    text = minutes + 'm ' + seconds + 's';
                }
                textElement.textContent = text;
            }
        }

        function onExpired() {
            if (countdownType === 'until-available') {
                // La wave se acaba de abrir: recargar para que PHP actualice el estado
                if (hasUnitDisplay) {
                    element.classList.add('countdown--ready');
                } else if (textElement) {
                    textElement.textContent = '¡Disponible ahora!';
                }
                setTimeout(function() {
                    window.location.reload();
                }, 3000);
            } else {
                // until-expires: la wave cerró
                if (hasUnitDisplay) {
                    element.classList.add('countdown--expired');
                    unitElements.forEach(function(el) { el.textContent = '00'; });
                } else if (textElement) {
                    textElement.textContent = 'Tiempo agotado';
                }
                setTimeout(function() {
                    window.location.reload();
                }, 3000);
            }
        }

        // Actualizar cada segundo
        updateCountdown();
        setInterval(updateCountdown, 1000);
    });

    console.log('[EIPSI] Countdown timer initialized (Fase 4 - data-target-timestamp)');
})();
