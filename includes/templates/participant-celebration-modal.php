<?php
/**
 * Template: Participant Celebration Modal (v1.7.0)
 *
 * Modal de celebración que aparece cuando el participante completa
 * todas las preguntas de un formulario. Proporciona retroalimentación
 * positiva y muestra el próximo paso.
 *
 * @package EIPSI_Forms
 * @since 1.7.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$survey_id = isset($atts['survey_id']) ? absint($atts['survey_id']) : 0;
$wave_index = isset($atts['wave_index']) ? absint($atts['wave_index']) : 0;
$next_action_url = isset($atts['next_action_url']) ? esc_url($atts['next_action_url']) : '';
$next_action_text = isset($atts['next_action_text']) ? esc_html($atts['next_action_text']) : esc_html__('Volver al inicio', 'eipsi-forms');

// Obtener información del estudio
$study_name = '';
$total_waves = 0;
$completed_waves = 0;

if ($survey_id) {
    global $wpdb;

    $study = $wpdb->get_row($wpdb->prepare(
        "SELECT study_name FROM {$wpdb->prefix}survey_studies WHERE id = %d",
        $survey_id
    ));

    if ($study) {
        $study_name = $study->study_name;
    }

    // Obtener wave stats
    $waves = $wpdb->get_results($wpdb->prepare(
        "SELECT wave_index FROM {$wpdb->prefix}survey_waves WHERE survey_id = %d",
        $survey_id
    ));

    $total_waves = count($waves);

    // Obtener waves completadas por el participante actual
    $current_user_id = get_current_user_id();
    if ($current_user_id) {
        $participant = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}survey_participants WHERE survey_id = %d AND user_id = %d",
            $survey_id,
            $current_user_id
        ));

        if ($participant) {
            $completed = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}survey_wave_assignments
                 WHERE participant_id = %d AND status = %s",
                $participant->id,
                'submitted'
            ));
            $completed_waves = intval($completed);
        }
    }
}

// Determinar si es la última wave
$is_last_wave = ($wave_index > 0 && $completed_waves >= $total_waves);

// Determinar mensaje de celebración
if ($is_last_wave) {
    $celebration_title = __('¡Felicitaciones! Completaste todo el estudio', 'eipsi-forms');
    $celebration_message = __(
        'Gracias por tu participación en todas las tomas. Tu compromiso nos ayuda a avanzar en la investigación clínica y mejorar tratamientos futuros.',
        'eipsi-forms'
    );
    $celebration_emoji = '🏆';
    $next_action_text = __('Ver mis resultados', 'eipsi-forms');
} else {
    $celebration_title = __('¡Excelente trabajo! Completaste esta toma', 'eipsi-forms');
    $celebration_message = sprintf(
        __('Gracias por tu tiempo y honestidad. Ya completaste %d de %d tomas del estudio.', 'eipsi-forms'),
        $completed_waves,
        $total_waves
    );
    $celebration_emoji = '🌟';
    if (!$next_action_url) {
        $next_action_text = __('Volver al dashboard', 'eipsi-forms');
    }
}
?>

<!-- Celebration Modal -->
<div class="eipsi-celebration-modal" id="eipsi-celebration-modal" style="display: none;">
    <div class="eipsi-celebration-modal__overlay"></div>
    <div class="eipsi-celebration-modal__content">
        <div class="eipsi-celebration-modal__emoji">
            <?php echo esc_html($celebration_emoji); ?>
        </div>
        <h2 class="eipsi-celebration-modal__title">
            <?php echo esc_html($celebration_title); ?>
        </h2>
        <p class="eipsi-celebration-modal__message">
            <?php echo esc_html($celebration_message); ?>
        </p>

        <?php if ($study_name): ?>
        <div class="eipsi-celebration-modal__study">
            <strong><?php esc_html_e('Estudio:', 'eipsi-forms'); ?></strong>
            <?php echo esc_html($study_name); ?>
        </div>
        <?php endif; ?>

        <?php if (!$is_last_wave): ?>
        <div class="eipsi-celebration-modal__next">
            <?php
            $remaining = $total_waves - $completed_waves;
            echo sprintf(
                esc_html__('Te quedan %d tomas por completar.', 'eipsi-forms'),
                absint($remaining)
            );
            ?>
        </div>
        <?php endif; ?>

        <div class="eipsi-celebration-modal__actions">
            <?php if ($next_action_url): ?>
            <a href="<?php echo esc_url($next_action_url); ?>" class="eipsi-celebration-modal__button eipsi-celebration-modal__button--primary">
                <?php echo esc_html($next_action_text); ?>
            </a>
            <?php else: ?>
            <button type="button" class="eipsi-celebration-modal__button eipsi-celebration-modal__button--primary" id="eipsi-celebration-close">
                <?php echo esc_html($next_action_text); ?>
            </button>
            <?php endif; ?>
        </div>

        <div class="eipsi-celebration-modal__footer">
            <span class="eipsi-celebration-modal__info">
                💚 <?php esc_html_e('Tus respuestas ayudan a construir conocimiento clínico', 'eipsi-forms'); ?>
            </span>
        </div>
    </div>
</div>

<script type="text/javascript">
(function($) {
    'use strict';

    $(document).ready(function() {
        // Show modal after brief delay
        setTimeout(function() {
            $('#eipsi-celebration-modal').fadeIn(400);
            triggerConfetti();
        }, 1000);

        // Close button handler
        $('#eipsi-celebration-close').on('click', function() {
            $('#eipsi-celebration-modal').fadeOut(400, function() {
                if ('<?php echo esc_js($next_action_url); ?>') {
                    window.location.href = '<?php echo esc_js($next_action_url); ?>';
                } else {
                    window.location.reload();
                }
            });
        });

        // Close on overlay click
        $('.eipsi-celebration-modal__overlay').on('click', function() {
            $('#eipsi-celebration-modal').fadeOut(400);
        });

        // Close on ESC key
        $(document).on('keydown', function(e) {
            if (e.keyCode === 27) { // ESC
                $('#eipsi-celebration-modal').fadeOut(400);
            }
        });
    });

    // Simple confetti effect
    function triggerConfetti() {
        const colors = ['#2271b1', '#00a32a', '#f0ad4e', '#d63638', '#9c27b0'];
        const container = $('.eipsi-celebration-modal__content');

        for (let i = 0; i < 50; i++) {
            const confetti = $('<div class="eipsi-confetti"></div>');
            const color = colors[Math.floor(Math.random() * colors.length)];
            const left = Math.random() * 100;
            const delay = Math.random() * 2;
            const duration = 2 + Math.random() * 2;

            confetti.css({
                background: color,
                left: left + '%',
                animationDelay: delay + 's',
                animationDuration: duration + 's'
            });

            container.append(confetti);

            setTimeout(function() {
                confetti.remove();
            }, (delay + duration) * 1000);
        }
    }
})(jQuery);
</script>

<style type="text/css">
.eipsi-celebration-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.eipsi-celebration-modal__overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
}

.eipsi-celebration-modal__content {
    position: relative;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #ffffff;
    padding: 40px;
    border-radius: 20px;
    max-width: 500px;
    width: 100%;
    text-align: center;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: modalSlideIn 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: scale(0.8) translateY(50px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.eipsi-celebration-modal__emoji {
    font-size: 64px;
    margin-bottom: 20px;
    animation: emojiBounce 1s ease-in-out infinite;
}

@keyframes emojiBounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.eipsi-celebration-modal__title {
    font-size: 24px;
    font-weight: 700;
    margin: 0 0 16px 0;
    line-height: 1.3;
}

.eipsi-celebration-modal__message {
    font-size: 16px;
    line-height: 1.6;
    margin: 0 0 20px 0;
    opacity: 0.95;
}

.eipsi-celebration-modal__study {
    background: rgba(255, 255, 255, 0.15);
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 16px;
    font-size: 14px;
}

.eipsi-celebration-modal__next {
    margin-bottom: 24px;
    font-size: 14px;
    opacity: 0.9;
}

.eipsi-celebration-modal__actions {
    display: flex;
    gap: 12px;
    justify-content: center;
    flex-wrap: wrap;
}

.eipsi-celebration-modal__button {
    padding: 12px 32px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.eipsi-celebration-modal__button--primary {
    background: #ffffff;
    color: #764ba2;
}

.eipsi-celebration-modal__button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.eipsi-celebration-modal__footer {
    margin-top: 24px;
    padding-top: 16px;
    border-top: 1px solid rgba(255, 255, 255, 0.2);
    font-size: 13px;
    opacity: 0.8;
}

.eipsi-confetti {
    position: absolute;
    width: 10px;
    height: 10px;
    top: -10px;
    border-radius: 2px;
    animation: confettiFall linear forwards;
}

@keyframes confettiFall {
    0% {
        transform: translateY(0) rotate(0deg);
        opacity: 1;
    }
    100% {
        transform: translateY(600px) rotate(720deg);
        opacity: 0;
    }
}

@media (max-width: 640px) {
    .eipsi-celebration-modal {
        padding: 16px;
    }

    .eipsi-celebration-modal__content {
        padding: 32px 24px;
    }

    .eipsi-celebration-modal__emoji {
        font-size: 48px;
    }

    .eipsi-celebration-modal__title {
        font-size: 20px;
    }

    .eipsi-celebration-modal__message {
        font-size: 14px;
    }

    .eipsi-celebration-modal__button {
        width: 100%;
    }
}
</style>
