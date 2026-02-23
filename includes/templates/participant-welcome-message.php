<?php
/**
 * Template: Participant Welcome Message (Empathetic v1.7.0)
 *
 * Mensaje de bienvenida emocional que explica el propósito del estudio
 * en términos humanos y reduce la ansiedad inicial del participante.
 *
 * @package EIPSI_Forms
 * @since 1.7.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$survey_id = isset($atts['survey_id']) ? absint($atts['survey_id']) : 0;

// Obtener información del estudio
$study_name = '';
$study_description = '';
$estimated_time = '';
$total_questions = 0;

if ($survey_id) {
    global $wpdb;

    // Obtener datos del estudio
    $study = $wpdb->get_row($wpdb->prepare(
        "SELECT study_name, description FROM {$wpdb->prefix}survey_studies WHERE id = %d",
        $survey_id
    ));

    if ($study) {
        $study_name = $study->study_name;
        $study_description = $study->description;
    }

    // Obtener form del estudio para calcular estadísticas
    $form_id = $wpdb->get_var($wpdb->prepare(
        "SELECT form_id FROM {$wpdb->prefix}survey_waves WHERE survey_id = %d AND wave_index = 1 LIMIT 1",
        $survey_id
    ));

    if ($form_id) {
        // Obtener preguntas del formulario
        $questions = $wpdb->get_col($wpdb->prepare(
            "SELECT question_text FROM {$wpdb->prefix}vas_questions WHERE form_id = %d",
            $form_id
        ));
        $total_questions = count($questions);

        // Estimar tiempo (aproximadamente 30 segundos por pregunta)
        $estimated_time = ceil($total_questions * 0.5); // en minutos
    }
}
?>

<!-- Welcome Message Emocional -->
<div class="eipsi-welcome-message" id="eipsi-welcome-message-<?php echo esc_attr($survey_id); ?>">
    <span class="eipsi-welcome-message__emoji">🌱</span>
    <h3 class="eipsi-welcome-message__title">
        <?php
        if ($study_name) {
            echo sprintf(
                esc_html__('Gracias por tu interés en %s', 'eipsi-forms'),
                '<span class="eipsi-welcome-message__highlight">' . esc_html($study_name) . '</span>'
            );
        } else {
            esc_html_e('Gracias por tu interés en este estudio', 'eipsi-forms');
        }
        ?>
    </h3>
    <p class="eipsi-welcome-message__text">
        <?php
        if ($study_description) {
            echo esc_html($study_description);
        } else {
            esc_html_e(
                'Tu participación nos ayuda a entender mejor cómo funcionan las emociones y a mejorar tratamientos futuros. Todo lo que compartes es completamente confidencial.',
                'eipsi-forms'
            );
        }
        ?>
    </p>
</div>

<!-- Time Estimate -->
<?php if ($estimated_time > 0): ?>
<div class="eipsi-time-estimate">
    <span class="eipsi-time-estimate__icon">⏱️</span>
    <div class="eipsi-time-estimate__content">
        <div class="eipsi-time-estimate__primary">
            <?php
            echo sprintf(
                esc_html__('Tiempo estimado: %d-%d minutos', 'eipsi-forms'),
                $estimated_time,
                $estimated_time + 5
            );
            ?>
        </div>
        <div class="eipsi-time-estimate__secondary">
            <?php
            echo sprintf(
                esc_html__('%d preguntas en este formulario', 'eipsi-forms'),
                $total_questions
            );
            ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Gentle Reminder si hay próximas waves -->
<?php
if ($survey_id) {
    $next_wave = $wpdb->get_row($wpdb->prepare(
        "SELECT w.name, w.due_date, w.wave_index
         FROM {$wpdb->prefix}survey_waves w
         WHERE w.survey_id = %d
         AND w.wave_index > 1
         ORDER BY w.wave_index ASC
         LIMIT 1",
        $survey_id
    ));

    if ($next_wave) {
        $due_date = $next_wave->due_date;
        $days_until = $due_date ? ceil((strtotime($due_date) - current_time('timestamp')) / DAY_IN_SECONDS) : 0;

        if ($days_until >= 0 && $days_until <= 7) {
            $urgency_class = 'urgent';
            if ($days_until <= 3) {
                $urgency_class = 'today';
            } else {
                $urgency_class = 'soon';
            }
            ?>
            <div class="eipsi-gentle-reminder">
                <div class="eipsi-gentle-reminder__header">
                    <span class="eipsi-gentle-reminder__emoji">🗓️</span>
                    <span class="eipsi-gentle-reminder__title">
                        <?php esc_html_e('Próxima toma pronto', 'eipsi-forms'); ?>
                    </span>
                </div>
                <p class="eipsi-gentle-reminder__message">
                    <?php
                    if ($days_until === 0) {
                        echo sprintf(
                            esc_html__('Tienes una toma pendiente para hoy: %s', 'eipsi-forms'),
                            esc_html($next_wave->name)
                        );
                    } elseif ($days_until === 1) {
                        echo sprintf(
                            esc_html__('Tienes una toma pendiente para mañana: %s', 'eipsi-forms'),
                            esc_html($next_wave->name)
                        );
                    } else {
                        echo sprintf(
                            esc_html__('Tienes una toma pendiente en %d días: %s', 'eipsi-forms'),
                            absint($days_until),
                            esc_html($next_wave->name)
                        );
                    }
                    ?>
                </p>
            </div>
            <?php
        }
    }
}
?>
