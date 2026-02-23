<?php
/**
 * Template: Contextual Help Component (v1.7.0)
 *
 * Componente reutilizable para explicar el contexto y propósito
 * de preguntas específicas en los formularios clínicos.
 *
 * Uso:
 * <?php echo eipsi_render_contextual_help($question_text, $explanation, $is_expanded = false); ?>
 *
 * @package EIPSI_Forms
 * @since 1.7.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render contextual help component
 *
 * @param string $question_text Texto de la pregunta
 * @param string $explanation Explicación del por qué
 * @param bool $is_expanded Si debe estar expandido por defecto
 * @return string HTML del componente
 */
function eipsi_render_contextual_help($question_text, $explanation, $is_expanded = false) {
    $unique_id = 'eipsi-help-' . uniqid();
    $expanded_class = $is_expanded ? 'eipsi-contextual-help__content--expanded' : '';
    $expanded_attr = $is_expanded ? 'aria-expanded="true"' : 'aria-expanded="false"';
    $toggle_class = $is_expanded ? 'eipsi-contextual-help__toggle--expanded' : '';

    ob_start();
    ?>
    <div class="eipsi-contextual-help" data-question="<?php echo esc_attr($question_text); ?>">
        <div class="eipsi-contextual-help__header" <?php echo $expanded_attr; ?> role="button" tabindex="0">
            <span class="eipsi-contextual-help__icon">❓</span>
            <span class="eipsi-contextual-help__title">
                <?php esc_html_e('¿Por qué preguntamos esto?', 'eipsi-forms'); ?>
            </span>
            <span class="eipsi-contextual-help__toggle <?php echo esc_attr($toggle_class); ?>">
                ▼
            </span>
        </div>
        <div class="eipsi-contextual-help__content <?php echo esc_attr($expanded_class); ?>" id="<?php echo esc_attr($unique_id); ?>">
            <p class="eipsi-contextual-help__text">
                <?php echo esc_html($explanation); ?>
            </p>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render contextual help with predefined explanations
 *
 * @param string $question_type Tipo de pregunta (mood, anxiety, sleep, etc.)
 * @param string $custom_explanation Explicación personalizada (opcional)
 * @return string HTML del componente
 */
function eipsi_render_contextual_help_predefined($question_type, $custom_explanation = '') {
    $explanations = array(
        'mood' => __(
            'El estado de ánimo es un indicador importante de bienestar emocional. Entender tus fluctuaciones diarias nos ayuda a identificar patrones y momentos donde podrías necesitar más apoyo.',
            'eipsi-forms'
        ),
        'anxiety' => __(
            'La ansiedad es una respuesta natural del cuerpo al estrés. Medirla nos permite entender cómo afecta tu vida diaria y qué estrategias podrían ser más efectivas para manejarla.',
            'eipsi-forms'
        ),
        'sleep' => __(
            'El sueño es fundamental para la salud mental. Dormir bien afecta tu energía, humor y capacidad de concentración. Nos ayuda a ver si hay conexiones entre tus patrones de sueño y tu bienestar.',
            'eipsi-forms'
        ),
        'stress' => __(
            'El estrés es inevitable en la vida moderna, pero entender qué situaciones lo generan y cómo lo manejas es el primer paso para construir resiliencia.',
            'eipsi-forms'
        ),
        'relationships' => __(
            'Las relaciones sociales son un pilar fundamental del bienestar. Entender cómo te sientes en tus conexiones con otros nos ayuda a ver cómo afectan tu salud mental.',
            'eipsi-forms'
        ),
        'physical_activity' => __(
            'La actividad física no solo es buena para el cuerpo, también es poderosa para la mente. Nos ayuda a ver cómo tu nivel de movimiento se relaciona con tu estado emocional.',
            'eipsi-forms'
        ),
        'medication' => __(
            'Si estás tomando medicación, es importante que sepamos cuál y cómo la usas. Esto nos ayuda a entender mejor tu situación y a no confundir efectos de medicación con otros síntomas.',
            'eipsi-forms'
        ),
        'therapy' => __(
            'La terapia es una herramienta valiosa. Saber si estás en terapia nos ayuda a entender tu nivel de apoyo y cómo complementar el trabajo que ya estás haciendo.',
            'eipsi-forms'
        ),
        'general' => __(
            'Esta información nos ayuda a construir un cuadro más completo de tu experiencia. No hay respuestas correctas o incorrectas, solo queremos conocer tu perspectiva honesta.',
            'eipsi-forms'
        )
    );

    $explanation = $custom_explanation ?: ($explanations[$question_type] ?? $explanations['general']);

    return eipsi_render_contextual_help($question_type, $explanation);
}
?>

<!-- Ejemplo de uso en un formulario -->
<!--
<div class="vas-question-block">
    <label class="vas-label">¿Cómo te has sentido hoy?</label>

    <?php echo eipsi_render_contextual_help_predefined('mood'); ?>

    <div class="vas-response-options">
        <input type="radio" name="mood_today" value="1">
        <label>Muy mal</label>
        ...
    </div>
</div>
-->

<!-- Ejemplo con explicación personalizada -->
<!--
<div class="vas-question-block">
    <label class="vas-label">¿Qué tan difícil te ha sido concentrarte esta semana?</label>

    <?php
    echo eipsi_render_contextual_help(
        'concentration',
        'La concentración es clave para tu bienestar y productividad. Dificultades para concentrarte pueden indicar estrés, ansiedad u otros factores que podemos abordar juntos.',
        true // expandido por defecto
    );
    ?>

    <div class="vas-response-options">
        <input type="radio" name="concentration" value="1">
        <label>Muy difícil</label>
        ...
    </div>
</div>
-->
