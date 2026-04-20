/**
 * EIPSI Participant Countdown Timer
 * Cuenta regresiva visual en tiempo real para el dashboard del participante
 * NO envía emails ni cambia estados - eso lo hace el cron del servidor
 *
 * @package EIPSI_Forms
 * @since 2.2.0
 */

(function() {
    'use strict';

    // Buscar elementos con data-available-timestamp
    const countdownElements = document.querySelectorAll('[data-eipsi-countdown]');

    if (!countdownElements.length) return;

    countdownElements.forEach(function(element) {
        const availableTimestamp = parseInt(element.dataset.availableTimestamp, 10);
        if (!availableTimestamp || isNaN(availableTimestamp)) return;

        // Buscar el elemento donde mostrar el valor (puede ser el mismo o un hijo .countdown-value)
        const valueElement = element.querySelector('.countdown-value') || element;

        function updateCountdown() {
            const now = Math.floor(Date.now() / 1000);
            const remaining = availableTimestamp - now;

            if (remaining <= 0) {
                // Si hay label y value separados, actualizar ambos
                if (valueElement !== element) {
                    element.innerHTML = '<span class="countdown-ready">✅ ¡Disponible ahora! Recarga la página</span>';
                } else {
                    valueElement.textContent = '✅ ¡Disponible ahora!';
                }
                // Recargar página después de 5 segundos para que PHP actualice el estado
                setTimeout(function() {
                    window.location.reload();
                }, 5000);
                return;
            }

            // Calcular días, horas, minutos, segundos
            const days = Math.floor(remaining / 86400);
            const hours = Math.floor((remaining % 86400) / 3600);
            const minutes = Math.floor((remaining % 3600) / 60);
            const seconds = remaining % 60;

            let text = '';
            if (days > 0) {
                text = days + 'd ' + hours + 'h ' + minutes + 'm';
            } else if (hours > 0) {
                text = hours + 'h ' + minutes + 'm ' + seconds + 's';
            } else {
                text = minutes + 'm ' + seconds + 's';
            }

            valueElement.textContent = text;
        }

        // Actualizar cada segundo
        updateCountdown();
        setInterval(updateCountdown, 1000);
    });

    console.log('[EIPSI] Countdown timer initialized');
})();
