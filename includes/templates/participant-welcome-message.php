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
