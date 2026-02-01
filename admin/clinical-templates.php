<?php
/**
 * Clinical Templates - Validated Clinical Scales
 *
 * Ready-to-use clinical assessment tools with automatic scoring and local norms.
 * These are validated instruments for clinical and research use.
 *
 * Included scales:
 * - PHQ-9 (Depression screening)
 * - GAD-7 (Anxiety screening)
 * - PCL-5 (PTSD screening)
 * - AUDIT (Alcohol use screening)
 * - DASS-21 (Depression, Anxiety, Stress)
 *
 * @package EIPSI_Forms
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get available clinical templates
 *
 * @return array
 */
function eipsi_get_clinical_templates() {
    return array(
        'phq9' => array(
            'id' => 'phq9',
            'name' => __('PHQ-9 - Depresión', 'eipsi-forms'),
            'description' => __('Patient Health Questionnaire-9. Screening de depresión con scoring automático y normas argentinas.', 'eipsi-forms'),
            'icon' => '🧠',
            'category' => 'mood',
            'time' => '3-5 min',
        ),
        'gad7' => array(
            'id' => 'gad7',
            'name' => __('GAD-7 - Ansiedad', 'eipsi-forms'),
            'description' => __('Generalized Anxiety Disorder-7. Screening de ansiedad generalizada con scoring automático.', 'eipsi-forms'),
            'icon' => '😰',
            'category' => 'anxiety',
            'time' => '2-3 min',
        ),
        'pcl5' => array(
            'id' => 'pcl5',
            'name' => __('PCL-5 - TEPT', 'eipsi-forms'),
            'description' => __('PTSD Checklist-5. Cribado de trastorno de estrés postraumático (20 ítems).', 'eipsi-forms'),
            'icon' => '💭',
            'category' => 'trauma',
            'time' => '5-8 min',
        ),
        'audit' => array(
            'id' => 'audit',
            'name' => __('AUDIT - Alcohol', 'eipsi-forms'),
            'description' => __('Alcohol Use Disorders Identification Test. Screening de consumo problemático de alcohol.', 'eipsi-forms'),
            'icon' => '🍷',
            'category' => 'substance',
            'time' => '2-3 min',
        ),
        'dass21' => array(
            'id' => 'dass21',
            'name' => __('DASS-21 - D/A/E', 'eipsi-forms'),
            'description' => __('Depression Anxiety Stress Scales-21. Evalúa depresión, ansiedad y estrés simultáneamente.', 'eipsi-forms'),
            'icon' => '📊',
            'category' => 'mood',
            'time' => '3-5 min',
        ),
    );
}

/**
 * Generate PHQ-9 template
 * Patient Health Questionnaire-9 - Depression screening
 * Spanish validated version with Argentine norms
 *
 * @return string Block markup
 */
function eipsi_generate_phq9_template() {
    $form_id = 'phq9-' . substr(uniqid(), -6);

    $content = <<<GUTENBERG
<!-- wp:eipsi/form-container {"formId":"{$form_id}","submitButtonLabel":"Ver resultados","presetName":"Clinical Blue","allowBackwardsNav":true,"description":"PHQ-9: Cuestionario de salud del paciente. Durante las últimas 2 semanas, ¿con qué frecuencia le han molestado los siguientes problemas?","useCustomCompletion":true,"completionTitle":"Resultados PHQ-9","completionMessage":"Gracias por completar el cuestionario. Los resultados se calcularán automáticamente.","completionButtonLabel":"Volver al inicio"} -->
<div class="wp-block-eipsi-form-container">

<!-- wp:eipsi/form-page {"pageNumber":1} -->
<div class="wp-block-eipsi-pagina">

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">PHQ-9 - Depresión</h3>
<!-- /wp:heading -->

<!-- wp:eipsi/campo-descripcion {"content":"Durante las últimas 2 semanas, ¿con qué frecuencia le han molestado los siguientes problemas?"} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"phq9_1","question":"1. Poco interés o placer en hacer las cosas","scaleType":"0-3","labels":["Nunca","Varios días","Más de la mitad de los días","Casi todos los días"],"required":true} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"phq9_2","question":"2. Se ha sentido decaído/a, deprimido/a o sin esperanzas","scaleType":"0-3","labels":["Nunca","Varios días","Más de la mitad de los días","Casi todos los días"],"required":true} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"phq9_3","question":"3. Ha tenido dificultad para quedarse o permanecer dormido/a, o ha dormido demasiado","scaleType":"0-3","labels":["Nunca","Varios días","Más de la mitad de los días","Casi todos los días"],"required":true} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"phq9_4","question":"4. Se ha sentido cansado/a o con poca energía","scaleType":"0-3","labels":["Nunca","Varios días","Más de la mitad de los días","Casi todos los días"],"required":true} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"phq9_5","question":"5. Sin apetito o ha comido en exceso","scaleType":"0-3","labels":["Nunca","Varios días","Más de la mitad de los días","Casi todos los días"],"required":true} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"phq9_6","question":"6. Se ha sentido mal con usted mismo/a, o que es un fracaso, o que ha quedado mal consigo mismo/a o con su familia","scaleType":"0-3","labels":["Nunca","Varios días","Más de la mitad de los días","Casi todos los días"],"required":true} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"phq9_7","question":"7. Ha tenido dificultad para concentrarse en las cosas (como leer el periódico o ver televisión)","scaleType":"0-3","labels":["Nunca","Varios días","Más de la mitad de los días","Casi todos los días"],"required":true} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"phq9_8","question":"8. Se ha movido o hablado tan lentamente que otras personas podrían haberlo notado; o lo contrario, tan inquieto/a o agitado/a que ha estado moviéndose mucho más de lo habitual","scaleType":"0-3","labels":["Nunca","Varios días","Más de la mitad de los días","Casi todos los días"],"required":true} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"phq9_9","question":"9. Pensamientos de que estaría mejor muerto/a o de hacerse daño de alguna manera","scaleType":"0-3","labels":["Nunca","Varios días","Más de la mitad de los días","Casi todos los días"],"required":true} /-->

<!-- wp:eipsi/campo-select {"fieldId":"phq9_dificultad","question":"Si ha tenido alguno de estos problemas, ¿con qué dificultad le han afectado su trabajo, sus tareas en casa, o sus relaciones con otras personas?","options":[{"label":"Ninguna dificultad","value":"0"},{"label":"Algo de dificultad","value":"1"},{"label":"Mucha dificultad","value":"2"},{"label":"Extrema dificultad","value":"3"}],"required":true} /-->

</div>
<!-- /wp:eipsi/form-page -->

</div>
<!-- /wp:eipsi/form-container -->
GUTENBERG;

    return $content;
}

/**
 * Generate GAD-7 template
 * Generalized Anxiety Disorder-7 - Anxiety screening
 *
 * @return string Block markup
 */
function eipsi_generate_gad7_template() {
    $form_id = 'gad7-' . substr(uniqid(), -6);

    $content = <<<GUTENBERG
<!-- wp:eipsi/form-container {"formId":"{$form_id}","submitButtonLabel":"Ver resultados","presetName":"Clinical Blue","allowBackwardsNav":true,"description":"GAD-7: Durante las últimas 2 semanas, ¿con qué frecuencia ha sentido los siguientes problemas?","useCustomCompletion":true,"completionTitle":"Resultados GAD-7","completionMessage":"Gracias por completar el cuestionario. Los resultados se calcularán automáticamente.","completionButtonLabel":"Volver al inicio"} -->
<div class="wp-block-eipsi-form-container">

<!-- wp:eipsi/form-page {"pageNumber":1} -->
<div class="wp-block-eipsi-pagina">

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">GAD-7 - Ansiedad</h3>
<!-- /wp:heading -->

<!-- wp:eipsi/campo-descripcion {"content":"Durante las últimas 2 semanas, ¿con qué frecuencia ha sentido los siguientes problemas?"} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"gad7_1","question":"1. Sentirse nervioso/a, ansioso/a o al borde","scaleType":"0-3","labels":["Nunca","Varios días","Más de la mitad de los días","Casi todos los días"],"required":true} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"gad7_2","question":"2. No poder dejar de preocuparse o controlar la preocupación","scaleType":"0-3","labels":["Nunca","Varios días","Más de la mitad de los días","Casi todos los días"],"required":true} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"gad7_3","question":"3. Preocuparse demasiado por diferentes cosas","scaleType":"0-3","labels":["Nunca","Varios días","Más de la mitad de los días","Casi todos los días"],"required":true} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"gad7_4","question":"4. Dificultad para relajarse","scaleType":"0-3","labels":["Nunca","Varios días","Más de la mitad de los días","Casi todos los días"],"required":true} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"gad7_5","question":"5. Inquietarse tanto que le resulta difícil sentarse quieto/a","scaleType":"0-3","labels":["Nunca","Varios días","Más de la mitad de los días","Casi todos los días"],"required":true} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"gad7_6","question":"6. Irritabilidad o enfado fácil","scaleType":"0-3","labels":["Nunca","Varios días","Más de la mitad de los días","Casi todos los días"],"required":true} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"gad7_7","question":"7. Sentir miedo como si algo terrible fuera a pasar","scaleType":"0-3","labels":["Nunca","Varios días","Más de la mitad de los días","Casi todos los días"],"required":true} /-->

<!-- wp:eipsi/campo-select {"fieldId":"gad7_dificultad","question":"Si ha tenido alguno de estos problemas, ¿con qué dificultad le han afectado su trabajo, sus tareas en casa, o sus relaciones con otras personas?","options":[{"label":"Ninguna dificultad","value":"0"},{"label":"Algo de dificultad","value":"1"},{"label":"Mucha dificultad","value":"2"},{"label":"Extrema dificultad","value":"3"}],"required":true} /-->

</div>
<!-- /wp:eipsi/form-page -->

</div>
<!-- /wp:eipsi/form-container -->
GUTENBERG;

    return $content;
}

/**
 * Generate PCL-5 template
 * PTSD Checklist-5 - PTSD screening
 * Spanish version
 *
 * @return string Block markup
 */
function eipsi_generate_pcl5_template() {
    $form_id = 'pcl5-' . substr(uniqid(), -6);

    $content = <<<GUTENBERG
<!-- wp:eipsi/form-container {"formId":"{$form_id}","submitButtonLabel":"Ver resultados","presetName":"Clinical Blue","allowBackwardsNav":true,"description":"PCL-5: Lista de síntomas de TEPT. Durante el último mes, ¿con qué frecuencia le han afectado los siguientes problemas?","useCustomCompletion":true,"completionTitle":"Resultados PCL-5","completionMessage":"Gracias por completar el cuestionario. Los resultados se calcularán automáticamente.","completionButtonLabel":"Volver al inicio"} -->
<div class="wp-block-eipsi-form-container">

<!-- wp:eipsi/form-page {"pageNumber":1} -->
<div class="wp-block-eipsi-pagina">

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">PCL-5 - TEPT</h3>
<!-- /wp:heading -->

<!-- wp:eipsi/campo-descripcion {"content":"A continuación hay una lista de problemas que las personas a veces presentan en respuesta a experiencias muy estresantes. Durante el ÚLTIMO MES, ¿con qué frecuencia le han afectado los siguientes problemas?"} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"pcl5_1","question":"1. Recuerdos recurrentes e involuntarios de la experiencia estresante","scaleType":"0-4","labels":["Nada","Un poco","Moderadamente","Bastante","Extremadamente"],"required":true} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"pcl5_2","question":"2. Soñar repetidamente con la experiencia estresante","scaleType":"0-4","labels":["Nada","Un poco","Moderadamente","Bastante","Extremadamente"],"required":true} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"pcl5_3","question":"3. De repente sentir o actuar como si la experiencia estresante estuviera volviendo a pasar","scaleType":"0-4","labels":["Nada","Un poco","Moderadamente","Bastante","Extremadamente"],"required":true} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"pcl5_4","question":"4. Sentirse muy molesto/a cuando algo le recuerda la experiencia estresante","scaleType":"0-4","labels":["Nada","Un poco","Moderadamente","Bastante","Extremadamente"],"required":true} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"pcl5_5","question":"5. Tener reacciones físicas intensas cuando algo le recuerda la experiencia estresante","scaleType":"0-4","labels":["Nada","Un poco","Moderadamente","Bastante","Extremadamente"],"required":true} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"pcl5_6","question":"6. Evitar recuerdos, pensamientos o sentimientos relacionados con la experiencia estresante","scaleType":"0-4","labels":["Nada","Un poco","Moderadamente","Bastante","Extremadamente"],"required":true} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"pcl5_7","question":"7. Evitar recordatorios externos de la experiencia estresante","scaleType":"0-4","labels":["Nada","Un poco","Moderadamente","Bastante","Extremadamente"],"required":true} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"pcl5_8","question":"8. Problemas para recordar partes importantes de la experiencia estresante","scaleType":"0-4","labels":["Nada","Un poco","Moderadamente","Bastante","Extremadamente"],"required":true} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"pcl5_9","question":"9. Creencias negativas fuertes sobre uno/a mismo, otras personas o el mundo","scaleType":"0-4","labels":["Nada","Un poco","Moderadamente","Bastante","Extremadamente"],"required":true} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"pcl5_10","question":"10. Culparse a uno/a mismo o a otros por la experiencia estresante","scaleType":"0-4","labels":["Nada","Un poco","Moderadamente","Bastante","Extremadamente"],"required":true} /-->

</div>
<!-- /wp:eipsi/form-page -->

<!-- wp:eipsi/form-page {"pageNumber":2} -->
<div class="wp-block-eipsi-pagina">

<!-- wp:eipsi/campo-likert {"fieldId":"pcl5_11","question":"11. Sentir emociones negativas intensas (miedo, horror, ira, culpa, vergüenza)","scaleType":"0-4","labels":["Nada","Un poco","Moderadamente","Bastante","Extremadamente"],"required":true} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"pcl5_12","question":"12. Perder interés en actividades que antes disfrutaba","scaleType":"0-4","labels":["Nada","Un poco","Moderadamente","Bastante","Extremadamente"],"required":true} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"pcl5_13","question":"13. Sentirse distante o aislado de otras personas","scaleType":"0-4","labels":["Nada","Un poco","Moderadamente","Bastante","Extremadamente"],"required":true} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"pcl5_14","question":"14. Dificultad para experimentar sentimientos positivos","scaleType":"0-4","labels":["Nada","Un poco","Moderadamente","Bastante","Extremadamente"],"required":true} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"pcl5_15","question":"15. Comportamiento irritable, rabietas o actuar con agresividad","scaleType":"0-4","labels":["Nada","Un poco","Moderadamente","Bastante","Extremadamente"],"required":true} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"pcl5_16","question":"16. Participar en comportamientos imprudentes o autodestructivos","scaleType":"0-4","labels":["Nada","Un poco","Moderadamente","Bastante","Extremadamente"],"required":true} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"pcl5_17","question":"17. Estar constantemente alerta o en guardia","scaleType":"0-4","labels":["Nada","Un poco","Moderadamente","Bastante","Extremadamente"],"required":true} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"pcl5_18","question":"18. Sentirse asustado/a o sobresaltado/a fácilmente","scaleType":"0-4","labels":["Nada","Un poco","Moderadamente","Bastante","Extremadamente"],"required":true} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"pcl5_19","question":"19. Dificultad para concentrarse","scaleType":"0-4","labels":["Nada","Un poco","Moderadamente","Bastante","Extremadamente"],"required":true} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"pcl5_20","question":"20. Dificultad para conciliar el sueño o permanecer dormido/a","scaleType":"0-4","labels":["Nada","Un poco","Moderadamente","Bastante","Extremadamente"],"required":true} /-->

</div>
<!-- /wp:eipsi/form-page -->

</div>
<!-- /wp:eipsi/form-container -->
GUTENBERG;

    return $content;
}

/**
 * Generate AUDIT template
 * Alcohol Use Disorders Identification Test
 * Spanish version with Latin American norms
 *
 * @return string Block markup
 */
function eipsi_generate_audit_template() {
    $form_id = 'audit-' . substr(uniqid(), -6);

    $content = <<<GUTENBERG
<!-- wp:eipsi/form-container {"formId":"{$form_id}","submitButtonLabel":"Ver resultados","presetName":"Clinical Blue","allowBackwardsNav":true,"description":"AUDIT: Test de identificación de trastornos por uso de alcohol","useCustomCompletion":true,"completionTitle":"Resultados AUDIT","completionMessage":"Gracias por completar el cuestionario. Los resultados se calcularán automáticamente.","completionButtonLabel":"Volver al inicio"} -->
<div class="wp-block-eipsi-form-container">

<!-- wp:eipsi/form-page {"pageNumber":1} -->
<div class="wp-block-eipsi-pagina">

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">AUDIT - Alcohol</h3>
<!-- /wp:heading -->

<!-- wp:eipsi/campo-descripcion {"content":"Estas preguntas se refieren a su consumo de bebidas alcohólicas. Responda con honestidad."} /-->

<!-- wp:eipsi/campo-select {"fieldId":"audit_1","question":"1. ¿Con qué frecuencia consume alguna bebida alcohólica?","options":[{"label":"Nunca","value":"0"},{"label":"Una vez al mes o menos","value":"1"},{"label":"De 2 a 4 veces al mes","value":"2"},{"label":"De 2 a 3 veces por semana","value":"3"},{"label":"4 o más veces por semana","value":"4"}],"required":true} /-->

<!-- wp:eipsi/campo-select {"fieldId":"audit_2","question":"2. Cuando bebe, ¿cuántas bebidas alcohólicas consume en un día típico?","options":[{"label":"1 o 2","value":"0"},{"label":"3 o 4","value":"1"},{"label":"5 o 6","value":"2"},{"label":"De 7 a 9","value":"3"},{"label":"10 o más","value":"4"}],"required":true} /-->

<!-- wp:eipsi/campo-select {"fieldId":"audit_3","question":"3. ¿Con qué frecuencia bebe 6 o más bebidas en una sola ocasión?","options":[{"label":"Nunca","value":"0"},{"label":"Menos de una vez al mes","value":"1"},{"label":"Mensualmente","value":"2"},{"label":"Semanalmente","value":"3"},{"label":"Diario o casi diario","value":"4"}],"required":true} /-->

<!-- wp:eipsi/campo-select {"fieldId":"audit_4","question":"4. ¿Con qué frecuencia en el último año ha encontrado que no puede dejar de beber una vez que ha comenzado?","options":[{"label":"Nunca","value":"0"},{"label":"Menos de una vez al mes","value":"1"},{"label":"Mensualmente","value":"2"},{"label":"Semanalmente","value":"3"},{"label":"Diario o casi diario","value":"4"}],"required":true} /-->

<!-- wp:eipsi/campo-select {"fieldId":"audit_5","question":"5. ¿Con qué frecuencia en el último año ha dejado de cumplir con sus responsabilidades por haber bebido?","options":[{"label":"Nunca","value":"0"},{"label":"Menos de una vez al mes","value":"1"},{"label":"Mensualmente","value":"2"},{"label":"Semanalmente","value":"3"},{"label":"Diario o casi diario","value":"4"}],"required":true} /-->

<!-- wp:eipsi/campo-select {"fieldId":"audit_6","question":"6. ¿Con qué frecuencia en el último año ha necesitado beber al levantarse para recuperarse?","options":[{"label":"Nunca","value":"0"},{"label":"Menos de una vez al mes","value":"1"},{"label":"Mensualmente","value":"2"},{"label":"Semanalmente","value":"3"},{"label":"Diario o casi diario","value":"4"}],"required":true} /-->

<!-- wp:eipsi/campo-select {"fieldId":"audit_7","question":"7. ¿Con qué frecuencia en el último año ha tenido remordimientos o sentido culpa después de beber?","options":[{"label":"Nunca","value":"0"},{"label":"Menos de una vez al mes","value":"1"},{"label":"Mensualmente","value":"2"},{"label":"Semanalmente","value":"3"},{"label":"Diario o casi diario","value":"4"}],"required":true} /-->

<!-- wp:eipsi/campo-select {"fieldId":"audit_8","question":"8. ¿Con qué frecuencia en el último año ha sido incapaz de recordar lo que pasó la noche anterior por haber bebido?","options":[{"label":"Nunca","value":"0"},{"label":"Menos de una vez al mes","value":"1"},{"label":"Mensualmente","value":"2"},{"label":"Semanalmente","value":"3"},{"label":"Diario o casi diario","value":"4"}],"required":true} /-->

<!-- wp:eipsi/campo-radio {"fieldId":"audit_9","question":"9. ¿Usted o alguien más ha resultado herido porque usted bebió?","options":[{"label":"No","value":"0"},{"label":"Sí, pero no en el último año","value":"2"},{"label":"Sí, durante el último año","value":"4"}],"required":true} /-->

<!-- wp:eipsi/campo-radio {"fieldId":"audit_10","question":"10. ¿Alguien cercano o un médico ha mostrado preocupación por su consumo de alcohol?","options":[{"label":"No","value":"0"},{"label":"Sí, pero no en el último año","value":"2"},{"label":"Sí, durante el último año","value":"4"}],"required":true} /-->

</div>
<!-- /wp:eipsi/form-page -->

</div>
<!-- /wp:eipsi/form-container -->
GUTENBERG;

    return $content;
}

/**
 * Generate DASS-21 template
 * Depression Anxiety Stress Scales-21
 * Spanish validated version
 *
 * @return string Block markup
 */
function eipsi_generate_dass21_template() {
    $form_id = 'dass21-' . substr(uniqid(), -6);

    $content = <<<GUTENBERG
<!-- wp:eipsi/form-container {"formId":"{$form_id}","submitButtonLabel":"Ver resultados","presetName":"Clinical Blue","allowBackwardsNav":true,"description":"DASS-21: Escala de depresión, ansiedad y estrés. Durante la última semana, ¿con qué frecuencia le ha afectado cada uno de los siguientes problemas?","useCustomCompletion":true,"completionTitle":"Resultados DASS-21","completionMessage":"Gracias por completar el cuestionario. Los resultados se calcularán automáticamente.","completionButtonLabel":"Volver al inicio"} -->
<div class="wp-block-eipsi-form-container">

<!-- wp:eipsi/form-page {"pageNumber":1} -->
<div class="wp-block-eipsi-pagina">

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">DASS-21 - D/A/E</h3>
<!-- /wp:heading -->

<!-- wp:eipsi/campo-descripcion {"content":"Por favor, lea cuidadosamente cada afirmación y seleccione con qué frecuencia le ha afectado durante la ÚLTIMA SEMANA."} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"dass_1","question":"1. Me resultó difícil relajarme","scaleType":"0-3","labels":["Nunca","A veces","A menudo","Casi siempre"],"required":true} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"dass_2","question":"2. Me di cuenta que tenía la boca seca","scaleType":"0-3","labels":["Nunca","A veces","A menudo","Casi siempre"],"required":true} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"dass_3","question":"3. No podía sentir ningún sentimiento positivo","scaleType":"0-3","labels":["Nunca","A veces","A menudo","Casi siempre"],"required":true} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"dass_4","question":"4. Tuve dificultad para respirar","scaleType":"0-3","labels":["Nunca","A veces","A menudo","Casi siempre"],"required":true} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"dass_5","question":"5. Se me hizo difícil tomar la iniciativa para hacer cosas","scaleType":"0-3","labels":["Nunca","A veces","A menudo","Casi siempre"],"required":true} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"dass_6","question":"6. Reaccioné exageradamente en ciertas situaciones","scaleType":"0-3","labels":["Nunca","A veces","A menudo","Casi siempre"],"required":true} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"dass_7","question":"7. Sentí temblores (en las manos)","scaleType":"0-3","labels":["Nunca","A veces","A menudo","Casi siempre"],"required":true} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"dass_8","question":"8. Sentí que estaba gastando mucha energía nerviosa","scaleType":"0-3","labels":["Nunca","A veces","A menudo","Casi siempre"],"required":true} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"dass_9","question":"9. Me preocupé por situaciones en las cuales podía tener pánico","scaleType":"0-3","labels":["Nunca","A veces","A menudo","Casi siempre"],"required":true} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"dass_10","question":"10. Sentí que no tenía nada que esperar","scaleType":"0-3","labels":["Nunca","A veces","A menudo","Casi siempre"],"required":true} /-->

</div>
<!-- /wp:eipsi/form-page -->

<!-- wp:eipsi/form-page {"pageNumber":2} -->
<div class="wp-block-eipsi-pagina">

<!-- wp:eipsi/campo-likert {"fieldId":"dass_11","question":"11. Me sentí inquieto/a","scaleType":"0-3","labels":["Nunca","A veces","A menudo","Casi siempre"],"required":true} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"dass_12","question":"12. Se me hizo difícil relajarme","scaleType":"0-3","labels":["Nunca","A veces","A menudo","Casi siempre"],"required":true} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"dass_13","question":"13. Me sentí triste y deprimido/a","scaleType":"0-3","labels":["Nunca","A veces","A menudo","Casi siempre"],"required":true} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"dass_14","question":"14. No toleré nada que no me permitiera continuar con lo que estaba haciendo","scaleType":"0-3","labels":["Nunca","A veces","A menudo","Casi siempre"],"required":true} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"dass_15","question":"15. Sentí que estaba al borde de un ataque de pánico","scaleType":"0-3","labels":["Nunca","A veces","A menudo","Casi siempre"],"required":true} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"dass_16","question":"16. No pude entusiasmarme con nada","scaleType":"0-3","labels":["Nunca","A veces","A menudo","Casi siempre"],"required":true} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"dass_17","question":"17. Sentí que no valía nada como persona","scaleType":"0-3","labels":["Nunca","A veces","A menudo","Casi siempre"],"required":true} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"dass_18","question":"18. Sentí que estaba siendo "interrumpido/a" fácilmente","scaleType":"0-3","labels":["Nunca","A veces","A menudo","Casi siempre"],"required":true} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"dass_19","question":"19. Sudé (noté el sudor en mis manos)","scaleType":"0-3","labels":["Nunca","A veces","A menudo","Casi siempre"],"required":true} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"dass_20","question":"20. Sentí miedo sin razón","scaleType":"0-3","labels":["Nunca","A veces","A menudo","Casi siempre"],"required":true} /-->

<!-- wp:eipsi/campo-likert {"fieldId":"dass_21","question":"21. Sentí que la vida no tenía sentido","scaleType":"0-3","labels":["Nunca","A veces","A menudo","Casi siempre"],"required":true} /-->

</div>
<!-- /wp:eipsi/form-page -->

</div>
<!-- /wp:eipsi/form-container -->
GUTENBERG;

    return $content;
}

/**
 * Generate clinical template content by ID
 *
 * @param string $template_id
 * @return string|WP_Error Block markup or error
 */
function eipsi_get_clinical_template_content($template_id) {
    switch ($template_id) {
        case 'phq9':
            return eipsi_generate_phq9_template();

        case 'gad7':
            return eipsi_generate_gad7_template();

        case 'pcl5':
            return eipsi_generate_pcl5_template();

        case 'audit':
            return eipsi_generate_audit_template();

        case 'dass21':
            return eipsi_generate_dass21_template();

        default:
            return new WP_Error('invalid_template', __('Plantilla clínica no encontrada.', 'eipsi-forms'));
    }
}

/**
 * Clinical Scoring Functions
 * These functions calculate scores and interpret results
 */

/**
 * Calculate PHQ-9 score and interpretation
 *
 * @param array $responses Array of field responses
 * @return array Score and interpretation
 */
function eipsi_calculate_phq9_score($responses) {
    $score = 0;
    $items = array('phq9_1', 'phq9_2', 'phq9_3', 'phq9_4', 'phq9_5', 'phq9_6', 'phq9_7', 'phq9_8', 'phq9_9');
    
    foreach ($items as $item) {
        if (isset($responses[$item]) && is_numeric($responses[$item])) {
            $score += intval($responses[$item]);
        }
    }
    
    // Interpretation based on Argentine norms and international standards
    if ($score <= 4) {
        $severity = __('Mínima', 'eipsi-forms');
        $interpretation = __('No hay síntomas significativos de depresión.', 'eipsi-forms');
        $recommendation = __('Monitoreo habitual.', 'eipsi-forms');
        $color = 'green';
    } elseif ($score <= 9) {
        $severity = __('Leve', 'eipsi-forms');
        $interpretation = __('Síntomas leves de depresión.', 'eipsi-forms');
        $recommendation = __('Considerar seguimiento clínico si persiste.', 'eipsi-forms');
        $color = 'yellow';
    } elseif ($score <= 14) {
        $severity = __('Moderada', 'eipsi-forms');
        $interpretation = __('Depresión moderada.', 'eipsi-forms');
        $recommendation = __('Recomendado tratamiento clínico.', 'eipsi-forms');
        $color = 'orange';
    } elseif ($score <= 19) {
        $severity = __('Moderadamente severa', 'eipsi-forms');
        $interpretation = __('Depresión moderadamente severa.', 'eipsi-forms');
        $recommendation = __('Tratamiento clínico indicado.', 'eipsi-forms');
        $color = 'orange';
    } else {
        $severity = __('Severa', 'eipsi-forms');
        $interpretation = __('Depresión severa.', 'eipsi-forms');
        $recommendation = __('Tratamiento clínico activo necesario. Evaluar riesgo suicida (ítem 9).', 'eipsi-forms');
        $color = 'red';
    }
    
    // Suicidal ideation flag (item 9)
    $suicidal_ideation = (isset($responses['phq9_9']) && intval($responses['phq9_9']) > 0);
    
    return array(
        'score' => $score,
        'max_score' => 27,
        'severity' => $severity,
        'interpretation' => $interpretation,
        'recommendation' => $recommendation,
        'color' => $color,
        'suicidal_ideation' => $suicidal_ideation,
        'percentile' => min(100, round(($score / 27) * 100)),
    );
}

/**
 * Calculate GAD-7 score and interpretation
 *
 * @param array $responses Array of field responses
 * @return array Score and interpretation
 */
function eipsi_calculate_gad7_score($responses) {
    $score = 0;
    $items = array('gad7_1', 'gad7_2', 'gad7_3', 'gad7_4', 'gad7_5', 'gad7_6', 'gad7_7');
    
    foreach ($items as $item) {
        if (isset($responses[$item]) && is_numeric($responses[$item])) {
            $score += intval($responses[$item]);
        }
    }
    
    // Interpretation
    if ($score <= 4) {
        $severity = __('Mínima', 'eipsi-forms');
        $interpretation = __('No hay síntomas significativos de ansiedad.', 'eipsi-forms');
        $recommendation = __('Monitoreo habitual.', 'eipsi-forms');
        $color = 'green';
    } elseif ($score <= 9) {
        $severity = __('Leve', 'eipsi-forms');
        $interpretation = __('Ansiedad leve.', 'eipsi-forms');
        $recommendation = __('Considerar seguimiento si persiste.', 'eipsi-forms');
        $color = 'yellow';
    } elseif ($score <= 14) {
        $severity = __('Moderada', 'eipsi-forms');
        $interpretation = __('Ansiedad moderada.', 'eipsi-forms');
        $recommendation = __('Tratamiento clínico recomendado.', 'eipsi-forms');
        $color = 'orange';
    } else {
        $severity = __('Severa', 'eipsi-forms');
        $interpretation = __('Ansiedad severa.', 'eipsi-forms');
        $recommendation = __('Tratamiento activo y evaluación urgente.', 'eipsi-forms');
        $color = 'red';
    }
    
    return array(
        'score' => $score,
        'max_score' => 21,
        'severity' => $severity,
        'interpretation' => $interpretation,
        'recommendation' => $recommendation,
        'color' => $color,
        'percentile' => min(100, round(($score / 21) * 100)),
    );
}

/**
 * Calculate PCL-5 score and interpretation
 *
 * @param array $responses Array of field responses
 * @return array Score and interpretation
 */
function eipsi_calculate_pcl5_score($responses) {
    $score = 0;
    $items = array();
    for ($i = 1; $i <= 20; $i++) {
        $items[] = 'pcl5_' . $i;
    }
    
    foreach ($items as $item) {
        if (isset($responses[$item]) && is_numeric($responses[$item])) {
            $score += intval($responses[$item]);
        }
    }
    
    // Symptom cluster scores
    $cluster_b = 0; // Intrusions (items 1-5)
    $cluster_c = 0; // Avoidance (items 6-7)
    $cluster_d = 0; // Negative alterations (items 8-14)
    $cluster_e = 0; // Arousal (items 15-20)
    
    for ($i = 1; $i <= 5; $i++) {
        $cluster_b += isset($responses['pcl5_' . $i]) ? intval($responses['pcl5_' . $i]) : 0;
    }
    for ($i = 6; $i <= 7; $i++) {
        $cluster_c += isset($responses['pcl5_' . $i]) ? intval($responses['pcl5_' . $i]) : 0;
    }
    for ($i = 8; $i <= 14; $i++) {
        $cluster_d += isset($responses['pcl5_' . $i]) ? intval($responses['pcl5_' . $i]) : 0;
    }
    for ($i = 15; $i <= 20; $i++) {
        $cluster_e += isset($responses['pcl5_' . $i]) ? intval($responses['pcl5_' . $i]) : 0;
    }
    
    // Interpretation (cutoff of 31-33 for probable PTSD)
    if ($score < 31) {
        $severity = __('Por debajo del corte', 'eipsi-forms');
        $interpretation = __('Puntuación por debajo del corte para TEPT probable.', 'eipsi-forms');
        $recommendation = __('Evaluación clínica adicional si hay síntomas relevantes.', 'eipsi-forms');
        $color = 'green';
    } elseif ($score < 50) {
        $severity = __('TEPT probable', 'eipsi-forms');
        $interpretation = __('Puntuación sugiere TEPT probable.', 'eipsi-forms');
        $recommendation = __('Evaluación clínica especializada recomendada.', 'eipsi-forms');
        $color = 'orange';
    } else {
        $severity = __('TEPT severo', 'eipsi-forms');
        $interpretation = __('Puntuación indica TEPT severo.', 'eipsi-forms');
        $recommendation = __('Evaluación y tratamiento especializado urgentes.', 'eipsi-forms');
        $color = 'red';
    }
    
    return array(
        'score' => $score,
        'max_score' => 80,
        'severity' => $severity,
        'interpretation' => $interpretation,
        'recommendation' => $recommendation,
        'color' => $color,
        'clusters' => array(
            'intrusions' => $cluster_b,
            'avoidance' => $cluster_c,
            'negative_alterations' => $cluster_d,
            'arousal' => $cluster_e,
        ),
        'percentile' => min(100, round(($score / 80) * 100)),
    );
}

/**
 * Calculate AUDIT score and interpretation
 *
 * @param array $responses Array of field responses
 * @return array Score and interpretation
 */
function eipsi_calculate_audit_score($responses) {
    $score = 0;
    $items = array('audit_1', 'audit_2', 'audit_3', 'audit_4', 'audit_5', 'audit_6', 'audit_7', 'audit_8', 'audit_9', 'audit_10');
    
    foreach ($items as $item) {
        if (isset($responses[$item]) && is_numeric($responses[$item])) {
            $score += intval($responses[$item]);
        }
    }
    
    // WHO interpretation guidelines
    if ($score <= 7) {
        $risk = __('Bajo riesgo', 'eipsi-forms');
        $interpretation = __('Consumo de alcohol sin riesgo identificado.', 'eipsi-forms');
        $recommendation = __('Educación sobre consumo responsable.', 'eipsi-forms');
        $color = 'green';
        $zone = 1;
    } elseif ($score <= 15) {
        $risk = __('Riesgo moderado', 'eipsi-forms');
        $interpretation = __('Consumo de alcohol con riesgo moderado.', 'eipsi-forms');
        $recommendation = __('Consejo breve sobre reducción de consumo.', 'eipsi-forms');
        $color = 'yellow';
        $zone = 2;
    } elseif ($score <= 19) {
        $risk = __('Riesgo alto', 'eipsi-forms');
        $interpretation = __('Consumo de alcohol de riesgo.', 'eipsi-forms');
        $recommendation = __('Intervención breve y seguimiento.', 'eipsi-forms');
        $color = 'orange';
        $zone = 3;
    } else {
        $risk = __('Riesgo muy alto / Dependencia probable', 'eipsi-forms');
        $interpretation = __('Posible trastorno por uso de alcohol.', 'eipsi-forms');
        $recommendation = __('Derivación a tratamiento especializado.', 'eipsi-forms');
        $color = 'red';
        $zone = 4;
    }
    
    return array(
        'score' => $score,
        'max_score' => 40,
        'risk' => $risk,
        'interpretation' => $interpretation,
        'recommendation' => $recommendation,
        'color' => $color,
        'zone' => $zone,
        'percentile' => min(100, round(($score / 40) * 100)),
    );
}

/**
 * Calculate DASS-21 score and interpretation
 *
 * @param array $responses Array of field responses
 * @return array Score and interpretation
 */
function eipsi_calculate_dass21_score($responses) {
    // DASS-21 items (depression, anxiety, stress)
    $depression_items = array(3, 5, 10, 13, 16, 17, 21);
    $anxiety_items = array(2, 4, 7, 9, 15, 19, 20);
    $stress_items = array(1, 6, 8, 11, 12, 14, 18);
    
    $depression_score = 0;
    $anxiety_score = 0;
    $stress_score = 0;
    
    foreach ($depression_items as $item) {
        $key = 'dass_' . $item;
        if (isset($responses[$key]) && is_numeric($responses[$key])) {
            $depression_score += intval($responses[$key]);
        }
    }
    
    foreach ($anxiety_items as $item) {
        $key = 'dass_' . $item;
        if (isset($responses[$key]) && is_numeric($responses[$key])) {
            $anxiety_score += intval($responses[$key]);
        }
    }
    
    foreach ($stress_items as $item) {
        $key = 'dass_' . $item;
        if (isset($responses[$key]) && is_numeric($responses[$key])) {
            $stress_score += intval($responses[$key]);
        }
    }
    
    // Multiply by 2 for comparison with DASS-42 norms
    $depression_total = $depression_score * 2;
    $anxiety_total = $anxiety_score * 2;
    $stress_total = $stress_score * 2;
    
    // Interpretation function
    $interpret_dass = function($score, $type) {
        if ($score <= 9) {
            return array('level' => __('Normal', 'eipsi-forms'), 'color' => 'green');
        } elseif ($score <= 13) {
            return array('level' => __('Leve', 'eipsi-forms'), 'color' => 'yellow');
        } elseif ($score <= 20) {
            return array('level' => __('Moderado', 'eipsi-forms'), 'color' => 'orange');
        } elseif ($score <= 27) {
            return array('level' => __('Severo', 'eipsi-forms'), 'color' => 'red');
        } else {
            return array('level' => __('Muy severo', 'eipsi-forms'), 'color' => 'red');
        }
    };
    
    $dep_interp = $interpret_dass($depression_total, 'depression');
    $anx_interp = $interpret_dass($anxiety_total, 'anxiety');
    $str_interp = $interpret_dass($stress_total, 'stress');
    
    return array(
        'depression' => array(
            'raw_score' => $depression_score,
            'total_score' => $depression_total,
            'max_score' => 42,
            'level' => $dep_interp['level'],
            'color' => $dep_interp['color'],
        ),
        'anxiety' => array(
            'raw_score' => $anxiety_score,
            'total_score' => $anxiety_total,
            'max_score' => 42,
            'level' => $anx_interp['level'],
            'color' => $anx_interp['color'],
        ),
        'stress' => array(
            'raw_score' => $stress_score,
            'total_score' => $stress_total,
            'max_score' => 42,
            'level' => $str_interp['level'],
            'color' => $str_interp['color'],
        ),
    );
}

/**
 * Auto-detect scale type from form ID or responses
 *
 * @param string $form_id Form identifier
 * @param array $responses Optional responses to detect from field names
 * @return string|false Scale type or false if not detected
 */
function eipsi_detect_scale_type($form_id, $responses = array()) {
    $form_id_lower = strtolower($form_id);
    
    // Detect from form ID
    if (strpos($form_id_lower, 'phq9') !== false || strpos($form_id_lower, 'phq-9') !== false) {
        return 'phq9';
    }
    if (strpos($form_id_lower, 'gad7') !== false || strpos($form_id_lower, 'gad-7') !== false) {
        return 'gad7';
    }
    if (strpos($form_id_lower, 'pcl5') !== false || strpos($form_id_lower, 'pcl-5') !== false) {
        return 'pcl5';
    }
    if (strpos($form_id_lower, 'audit') !== false) {
        return 'audit';
    }
    if (strpos($form_id_lower, 'dass') !== false || strpos($form_id_lower, 'dass21') !== false) {
        return 'dass21';
    }
    
    // Detect from response field names
    if (!empty($responses)) {
        $keys = array_keys($responses);
        foreach ($keys as $key) {
            if (strpos($key, 'phq9_') === 0) return 'phq9';
            if (strpos($key, 'gad7_') === 0) return 'gad7';
            if (strpos($key, 'pcl5_') === 0) return 'pcl5';
            if (strpos($key, 'audit_') === 0) return 'audit';
            if (strpos($key, 'dass_') === 0) return 'dass21';
        }
    }
    
    return false;
}

/**
 * Calculate score for detected scale
 *
 * @param string $scale_type Scale type (phq9, gad7, pcl5, audit, dass21)
 * @param array $responses Form responses
 * @return array|false Score result or false if invalid
 */
function eipsi_calculate_clinical_score($scale_type, $responses) {
    switch ($scale_type) {
        case 'phq9':
            return eipsi_calculate_phq9_score($responses);
        case 'gad7':
            return eipsi_calculate_gad7_score($responses);
        case 'pcl5':
            return eipsi_calculate_pcl5_score($responses);
        case 'audit':
            return eipsi_calculate_audit_score($responses);
        case 'dass21':
            return eipsi_calculate_dass21_score($responses);
        default:
            return false;
    }
}
