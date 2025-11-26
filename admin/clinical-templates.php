<?php
/**
 * Clinical Templates - Official EIPSI Templates
 *
 * Plantillas oficiales de escalas clínicas validadas en español.
 * Proveen estructuras listas para usar en la Form Library.
 *
 * @package VAS_Dinamico_Forms
 * @since 1.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Return clinical templates metadata for the UI
 *
 * @return array
 */
function eipsi_get_clinical_templates() {
    return array(
        'phq9' => array(
            'id' => 'phq9',
            'name' => 'PHQ-9',
            'full_name' => 'Patient Health Questionnaire-9',
            'description' => 'Cuestionario de salud del paciente – síntomas depresivos en las últimas 2 semanas.',
            'icon' => '🩺',
            'author' => 'Kroenke, Spitzer & Williams (2001)',
            'validated_version' => 'Adaptación hispanohablante / Argentina 2023',
        ),
        'gad7' => array(
            'id' => 'gad7',
            'name' => 'GAD-7',
            'full_name' => 'Generalized Anxiety Disorder-7',
            'description' => 'Escala breve de ansiedad generalizada – 7 ítems (últimas 2 semanas).',
            'icon' => '😰',
            'author' => 'Spitzer et al. (2006)',
            'validated_version' => 'Versión en español validada',
        ),
        'pcl5' => array(
            'id' => 'pcl5',
            'name' => 'PCL-5',
            'full_name' => 'PTSD Checklist for DSM-5',
            'description' => 'Checklist de TEPT DSM-5 – síntomas ocurridos durante el último mes.',
            'icon' => '🛡️',
            'author' => 'Weathers et al. (2013)',
            'validated_version' => 'Versión latina autorizada',
        ),
        'audit' => array(
            'id' => 'audit',
            'name' => 'AUDIT',
            'full_name' => 'Alcohol Use Disorders Identification Test',
            'description' => 'Tamizaje de consumo riesgoso de alcohol – 10 preguntas estándar OMS.',
            'icon' => '🍷',
            'author' => 'Organización Mundial de la Salud (1992)',
            'validated_version' => 'Traducción oficial OMS en español',
        ),
        'dass21' => array(
            'id' => 'dass21',
            'name' => 'DASS-21',
            'full_name' => 'Depression, Anxiety and Stress Scale - 21',
            'description' => 'Escala de Depresión, Ansiedad y Estrés – 21 ítems en 3 subescalas.',
            'icon' => '📈',
            'author' => 'Lovibond & Lovibond (1995)',
            'validated_version' => 'Versión en español (Bados, 2010)',
        ),
    );
}

/**
 * Generate a unique formId using a prefix
 *
 * @param string $prefix
 * @return string
 */
function eipsi_generate_form_id_with_prefix($prefix) {
    return sanitize_title($prefix . '-' . uniqid());
}

/**
 * Normalize field IDs to alphanumeric/underscore/hyphen
 *
 * @param string $field_id
 * @return string
 */
function eipsi_normalize_field_id($field_id) {
    $clean = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', $field_id));
    return $clean ? $clean : uniqid('field_');
}

/**
 * Helper: render radio field block markup
 *
 * @param string $field_id
 * @param string $question
 * @param array  $options
 * @param array  $extra_attrs
 * @return string
 */
function eipsi_render_radio_field_block($field_id, $question, $options, $extra_attrs = array()) {
    $attrs = array_merge(
        array(
            'fieldId' => eipsi_normalize_field_id($field_id),
            'question' => $question,
            'options' => $options,
            'required' => true,
        ),
        $extra_attrs
    );

    return '<!-- wp:vas-dinamico/campo-radio ' . wp_json_encode($attrs, JSON_UNESCAPED_UNICODE) . " /-->\n\n";
}

/**
 * Generate PHQ-9 template content
 *
 * @return string
 */
function eipsi_generate_phq9_template() {
    $form_id = eipsi_generate_form_id_with_prefix('phq9');

    $questions = array(
        'Poco interés o placer en hacer las cosas.',
        'Se ha sentido decaído/a, deprimido/a o sin esperanzas.',
        'Ha tenido dificultad para quedarse dormido/a, permanecer dormido/a o ha dormido demasiado.',
        'Se ha sentido cansado/a o con poca energía.',
        'Ha tenido poco apetito o ha comido en exceso.',
        'Se ha sentido mal consigo mismo/a; que es un fracaso o que ha defraudado a su familia.',
        'Ha tenido dificultad para concentrarse en ciertas actividades, como leer el periódico o ver la televisión.',
        'Se ha movido o hablado tan lento que otras personas podrían haberlo notado, o lo opuesto: tan inquieto/a o agitado/a que se ha estado moviendo más de lo normal.',
        'Ha tenido pensamientos de que estaría mejor muerto/a o de lastimarse de alguna manera.',
    );

    $options = array(
        array('label' => 'Nunca', 'value' => '0'),
        array('label' => 'Varios días', 'value' => '1'),
        array('label' => 'Más de la mitad de los días', 'value' => '2'),
        array('label' => 'Casi todos los días', 'value' => '3'),
    );

    $fields_markup = '';
    foreach ($questions as $index => $question) {
        $fields_markup .= eipsi_render_radio_field_block('phq9_q' . ($index + 1), $question, $options);
    }

    $content = sprintf(
        '<!-- wp:vas-dinamico/form-container {"formId":"%1$s","submitButtonLabel":"Enviar","presetName":"Clinical Blue","allowBackwardsNav":false} -->
<div class="wp-block-vas-dinamico-form-container">
<!-- wp:vas-dinamico/pagina {"pageNumber":1} -->
<div class="wp-block-vas-dinamico-pagina">

<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">PHQ-9 - Cuestionario de Salud del Paciente</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><strong>Durante las últimas 2 semanas, ¿con qué frecuencia le han molestado los siguientes problemas?</strong></p>
<!-- /wp:paragraph -->

%2$s

</div>
<!-- /wp:vas-dinamico/pagina -->
</div>
<!-- /wp:vas-dinamico/form-container -->',
        esc_attr($form_id),
        $fields_markup
    );

    return $content;
}

/**
 * Generate GAD-7 template content
 *
 * @return string
 */
function eipsi_generate_gad7_template() {
    $form_id = eipsi_generate_form_id_with_prefix('gad7');

    $questions = array(
        'Se ha sentido nervioso/a, ansioso/a o muy alterado/a.',
        'No ha podido dejar de preocuparse o controlar su preocupación.',
        'Se ha preocupado demasiado por diferentes cosas.',
        'Ha tenido dificultad para relajarse.',
        'Ha estado tan inquieto/a que le ha sido difícil permanecer sentado/a quieto/a.',
        'Se ha molestado o irritado con facilidad.',
        'Ha sentido miedo como si algo terrible fuera a suceder.',
    );

    $options = array(
        array('label' => 'Nunca', 'value' => '0'),
        array('label' => 'Varios días', 'value' => '1'),
        array('label' => 'Más de la mitad de los días', 'value' => '2'),
        array('label' => 'Casi todos los días', 'value' => '3'),
    );

    $fields_markup = '';
    foreach ($questions as $index => $question) {
        $fields_markup .= eipsi_render_radio_field_block('gad7_q' . ($index + 1), $question, $options);
    }

    $content = sprintf(
        '<!-- wp:vas-dinamico/form-container {"formId":"%1$s","submitButtonLabel":"Enviar","presetName":"Clinical Blue","allowBackwardsNav":false} -->
<div class="wp-block-vas-dinamico-form-container">
<!-- wp:vas-dinamico/pagina {"pageNumber":1} -->
<div class="wp-block-vas-dinamico-pagina">

<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">GAD-7 - Escala de Ansiedad Generalizada</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><strong>Durante las últimas 2 semanas, ¿con qué frecuencia le han molestado los siguientes problemas?</strong></p>
<!-- /wp:paragraph -->

%2$s

</div>
<!-- /wp:vas-dinamico/pagina -->
</div>
<!-- /wp:vas-dinamico/form-container -->',
        esc_attr($form_id),
        $fields_markup
    );

    return $content;
}

/**
 * Generate PCL-5 template content
 *
 * @return string
 */
function eipsi_generate_pcl5_template() {
    $form_id = eipsi_generate_form_id_with_prefix('pcl5');

    $questions = array(
        'Recuerdos repetitivos, perturbadores y no deseados de la experiencia estresante.',
        'Sueños repetitivos y perturbadores de la experiencia estresante.',
        'Como si la experiencia estresante estuviera ocurriendo de nuevo (reviviscencias).',
        'Sentirse muy molesto/a cuando algo le recordó la experiencia estresante.',
        'Reacciones físicas fuertes cuando algo le recordó la experiencia estresante (ej.: latidos acelerados, dificultad para respirar, sudoración).',
        'Evitar recuerdos, pensamientos o sentimientos relacionados con la experiencia estresante.',
        'Evitar recordatorios externos de la experiencia estresante (personas, lugares, conversaciones, actividades, objetos).',
        'Dificultad para recordar partes importantes de la experiencia estresante.',
        'Creencias negativas fuertes sobre usted mismo/a, otras personas o el mundo.',
        'Culparse a sí mismo/a o culpar a otros por la experiencia estresante o lo que sucedió después.',
        'Sentimientos negativos fuertes (miedo, horror, enojo, culpa o vergüenza).',
        'Pérdida de interés en actividades que antes disfrutaba.',
        'Sentirse distante o aislado/a de otras personas.',
        'Dificultad para experimentar sentimientos positivos.',
        'Comportamiento irritable, arrebatos de enojo o actuar agresivamente.',
        'Tomar demasiados riesgos o hacer cosas que podrían causarle daño.',
        'Estar “superalerta”, vigilante o en guardia.',
        'Sentirse nervioso/a o asustarse con facilidad.',
        'Dificultad para concentrarse.',
        'Dificultad para conciliar el sueño o permanecer dormido/a.',
    );

    $options = array(
        array('label' => 'Nada', 'value' => '0'),
        array('label' => 'Un poco', 'value' => '1'),
        array('label' => 'Moderadamente', 'value' => '2'),
        array('label' => 'Bastante', 'value' => '3'),
        array('label' => 'Extremadamente', 'value' => '4'),
    );

    $page1_markup = '';
    $page2_markup = '';

    foreach ($questions as $index => $question) {
        $field_markup = eipsi_render_radio_field_block('pcl5_q' . ($index + 1), $question, $options);
        if ($index < 10) {
            $page1_markup .= $field_markup;
        } else {
            $page2_markup .= $field_markup;
        }
    }

    $content = sprintf(
        '<!-- wp:vas-dinamico/form-container {"formId":"%1$s","submitButtonLabel":"Enviar","presetName":"Clinical Blue","allowBackwardsNav":true} -->
<div class="wp-block-vas-dinamico-form-container">

<!-- wp:vas-dinamico/pagina {"pageNumber":1} -->
<div class="wp-block-vas-dinamico-pagina">

<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">PCL-5 - Lista de Chequeo de TEPT</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><strong>En el último mes, ¿cuánto le han molestado los siguientes problemas?</strong></p>
<!-- /wp:paragraph -->

%2$s

</div>
<!-- /wp:vas-dinamico/pagina -->

<!-- wp:vas-dinamico/pagina {"pageNumber":2} -->
<div class="wp-block-vas-dinamico-pagina">

<!-- wp:paragraph -->
<p><strong>Continuá respondiendo según lo que viviste el último mes:</strong></p>
<!-- /wp:paragraph -->

%3$s

</div>
<!-- /wp:vas-dinamico/pagina -->

</div>
<!-- /wp:vas-dinamico/form-container -->',
        esc_attr($form_id),
        $page1_markup,
        $page2_markup
    );

    return $content;
}

/**
 * Generate AUDIT template content
 *
 * @return string
 */
function eipsi_generate_audit_template() {
    $form_id = eipsi_generate_form_id_with_prefix('audit');

    $questions_and_options = array(
        array(
            'question' => '¿Con qué frecuencia consume alguna bebida alcohólica?',
            'options' => array(
                array('label' => 'Nunca', 'value' => '0'),
                array('label' => 'Una vez al mes o menos', 'value' => '1'),
                array('label' => 'De 2 a 4 veces al mes', 'value' => '2'),
                array('label' => 'De 2 a 3 veces por semana', 'value' => '3'),
                array('label' => '4 o más veces por semana', 'value' => '4'),
            ),
        ),
        array(
            'question' => 'En un día de consumo típico, ¿cuántas bebidas alcohólicas toma?',
            'options' => array(
                array('label' => '1 o 2', 'value' => '0'),
                array('label' => '3 o 4', 'value' => '1'),
                array('label' => '5 o 6', 'value' => '2'),
                array('label' => '7, 8 o 9', 'value' => '3'),
                array('label' => '10 o más', 'value' => '4'),
            ),
        ),
        array(
            'question' => '¿Con qué frecuencia toma 6 o más bebidas alcohólicas en una sola ocasión?',
            'options' => array(
                array('label' => 'Nunca', 'value' => '0'),
                array('label' => 'Menos de una vez al mes', 'value' => '1'),
                array('label' => 'Mensualmente', 'value' => '2'),
                array('label' => 'Semanalmente', 'value' => '3'),
                array('label' => 'A diario o casi a diario', 'value' => '4'),
            ),
        ),
        array(
            'question' => 'En el último año, ¿con qué frecuencia fue incapaz de parar de beber una vez que había empezado?',
            'options' => array(
                array('label' => 'Nunca', 'value' => '0'),
                array('label' => 'Menos de una vez al mes', 'value' => '1'),
                array('label' => 'Mensualmente', 'value' => '2'),
                array('label' => 'Semanalmente', 'value' => '3'),
                array('label' => 'A diario o casi a diario', 'value' => '4'),
            ),
        ),
        array(
            'question' => 'En el último año, ¿con qué frecuencia no pudo hacer lo que se esperaba de usted porque había bebido?',
            'options' => array(
                array('label' => 'Nunca', 'value' => '0'),
                array('label' => 'Menos de una vez al mes', 'value' => '1'),
                array('label' => 'Mensualmente', 'value' => '2'),
                array('label' => 'Semanalmente', 'value' => '3'),
                array('label' => 'A diario o casi a diario', 'value' => '4'),
            ),
        ),
        array(
            'question' => 'En el último año, ¿con qué frecuencia necesitó beber en ayunas para recuperarse después de haber bebido mucho el día anterior?',
            'options' => array(
                array('label' => 'Nunca', 'value' => '0'),
                array('label' => 'Menos de una vez al mes', 'value' => '1'),
                array('label' => 'Mensualmente', 'value' => '2'),
                array('label' => 'Semanalmente', 'value' => '3'),
                array('label' => 'A diario o casi a diario', 'value' => '4'),
            ),
        ),
        array(
            'question' => 'En el último año, ¿con qué frecuencia tuvo remordimientos o sentimientos de culpa después de beber?',
            'options' => array(
                array('label' => 'Nunca', 'value' => '0'),
                array('label' => 'Menos de una vez al mes', 'value' => '1'),
                array('label' => 'Mensualmente', 'value' => '2'),
                array('label' => 'Semanalmente', 'value' => '3'),
                array('label' => 'A diario o casi a diario', 'value' => '4'),
            ),
        ),
        array(
            'question' => 'En el último año, ¿con qué frecuencia no pudo recordar lo que sucedió la noche anterior porque había estado bebiendo?',
            'options' => array(
                array('label' => 'Nunca', 'value' => '0'),
                array('label' => 'Menos de una vez al mes', 'value' => '1'),
                array('label' => 'Mensualmente', 'value' => '2'),
                array('label' => 'Semanalmente', 'value' => '3'),
                array('label' => 'A diario o casi a diario', 'value' => '4'),
            ),
        ),
        array(
            'question' => '¿Usted o alguna otra persona han resultado heridos porque usted había bebido?',
            'options' => array(
                array('label' => 'No', 'value' => '0'),
                array('label' => 'Sí, pero no en el último año', 'value' => '2'),
                array('label' => 'Sí, en el último año', 'value' => '4'),
            ),
        ),
        array(
            'question' => '¿Algún familiar, amigo/a o profesional de la salud le ha sugerido dejar de beber o ha mostrado preocupación por su consumo?',
            'options' => array(
                array('label' => 'No', 'value' => '0'),
                array('label' => 'Sí, pero no en el último año', 'value' => '2'),
                array('label' => 'Sí, en el último año', 'value' => '4'),
            ),
        ),
    );

    $fields_markup = '';
    foreach ($questions_and_options as $index => $item) {
        $fields_markup .= eipsi_render_radio_field_block('audit_q' . ($index + 1), $item['question'], $item['options']);
    }

    $content = sprintf(
        '<!-- wp:vas-dinamico/form-container {"formId":"%1$s","submitButtonLabel":"Enviar","presetName":"Clinical Blue","allowBackwardsNav":false} -->
<div class="wp-block-vas-dinamico-form-container">
<!-- wp:vas-dinamico/pagina {"pageNumber":1} -->
<div class="wp-block-vas-dinamico-pagina">

<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">AUDIT - Test de Identificación de Trastornos por Consumo de Alcohol</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><strong>Pensá en tu consumo habitual. Respondé cada pregunta marcando la opción que mejor te representa.</strong></p>
<!-- /wp:paragraph -->

%2$s

</div>
<!-- /wp:vas-dinamico/pagina -->
</div>
<!-- /wp:vas-dinamico/form-container -->',
        esc_attr($form_id),
        $fields_markup
    );

    return $content;
}

/**
 * Generate DASS-21 template content
 *
 * @return string
 */
function eipsi_generate_dass21_template() {
    $form_id = eipsi_generate_form_id_with_prefix('dass21');

    $questions = array(
        'Me costó mucho relajarme.',
        'Noté que tenía la boca seca.',
        'No pude sentir ningún sentimiento positivo.',
        'Se me hizo difícil respirar (por ejemplo, jadeos, falta de aire sin haber hecho esfuerzo físico).',
        'Se me hizo difícil tomar la iniciativa para hacer cosas.',
        'Reaccioné exageradamente en ciertas situaciones.',
        'Sentí que mis manos temblaban.',
        'Me sentí muy nervioso/a.',
        'Me preocupaba que pudiera tener un pánico o hacer el ridículo.',
        'Sentí que no tenía nada por qué vivir.',
        'Noté que me agitaba.',
        'Se me hizo difícil relajarme.',
        'Me sentí triste y deprimido/a.',
        'No toleré nada que interrumpiera lo que estaba haciendo.',
        'Sentí que estaba al punto de entrar en pánico.',
        'No pude entusiasmarme por nada.',
        'Sentí que valía muy poco como persona.',
        'Sentí que estaba muy irritable.',
        'Sentí los latidos de mi corazón aunque no hubiera hecho esfuerzo físico.',
        'Tuve miedo sin razón.',
        'Sentí que la vida no tenía ningún sentido.',
    );

    $options = array(
        array('label' => 'No me aplicó en absoluto', 'value' => '0'),
        array('label' => 'Me aplicó un poco, o durante parte del tiempo', 'value' => '1'),
        array('label' => 'Me aplicó bastante, o durante buena parte del tiempo', 'value' => '2'),
        array('label' => 'Me aplicó mucho, o la mayor parte del tiempo', 'value' => '3'),
    );

    $page1_markup = '';
    $page2_markup = '';

    foreach ($questions as $index => $question) {
        $field_markup = eipsi_render_radio_field_block('dass21_q' . ($index + 1), $question, $options);
        if ($index < 11) {
            $page1_markup .= $field_markup;
        } else {
            $page2_markup .= $field_markup;
        }
    }

    $content = sprintf(
        '<!-- wp:vas-dinamico/form-container {"formId":"%1$s","submitButtonLabel":"Enviar","presetName":"Clinical Blue","allowBackwardsNav":true} -->
<div class="wp-block-vas-dinamico-form-container">

<!-- wp:vas-dinamico/pagina {"pageNumber":1} -->
<div class="wp-block-vas-dinamico-pagina">

<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">DASS-21 - Escala de Depresión, Ansiedad y Estrés</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><strong>Leé cada afirmación y marcá cuánto se aplicó a vos durante la última semana. No hay respuestas correctas o incorrectas.</strong></p>
<!-- /wp:paragraph -->

%2$s

</div>
<!-- /wp:vas-dinamico/pagina -->

<!-- wp:vas-dinamico/pagina {"pageNumber":2} -->
<div class="wp-block-vas-dinamico-pagina">

<!-- wp:paragraph -->
<p><strong>Continuá respondiendo según tu última semana:</strong></p>
<!-- /wp:paragraph -->

%3$s

</div>
<!-- /wp:vas-dinamico/pagina -->

</div>
<!-- /wp:vas-dinamico/form-container -->',
        esc_attr($form_id),
        $page1_markup,
        $page2_markup
    );

    return $content;
}

/**
 * Create form template from a clinical scale
 *
 * @param string $template_id
 * @return int|WP_Error
 */
function eipsi_create_form_from_clinical_template($template_id) {
    $templates = eipsi_get_clinical_templates();

    if (!isset($templates[$template_id])) {
        return new WP_Error('invalid_template', __('Plantilla clínica no válida.', 'vas-dinamico-forms'));
    }

    switch ($template_id) {
        case 'phq9':
            $content = eipsi_generate_phq9_template();
            break;
        case 'gad7':
            $content = eipsi_generate_gad7_template();
            break;
        case 'pcl5':
            $content = eipsi_generate_pcl5_template();
            break;
        case 'audit':
            $content = eipsi_generate_audit_template();
            break;
        case 'dass21':
            $content = eipsi_generate_dass21_template();
            break;
        default:
            return new WP_Error('invalid_template', __('Plantilla clínica no válida.', 'vas-dinamico-forms'));
    }

    $template_info = $templates[$template_id];
    $post_title = sprintf(__('%s (nuevo)', 'vas-dinamico-forms'), $template_info['name']);

    $new_post_id = wp_insert_post(array(
        'post_title' => $post_title,
        'post_content' => $content,
        'post_status' => 'publish',
        'post_type' => 'eipsi_form_template',
        'post_author' => get_current_user_id(),
    ));

    if (is_wp_error($new_post_id)) {
        return $new_post_id;
    }

    $blocks = parse_blocks($content);
    $form_name = '';

    foreach ($blocks as $block) {
        if ($block['blockName'] === 'vas-dinamico/form-container') {
            $form_name = isset($block['attrs']['formId']) ? $block['attrs']['formId'] : '';
            break;
        }
    }

    if ($form_name) {
        update_post_meta($new_post_id, '_eipsi_form_name', sanitize_text_field($form_name));
    }

    update_post_meta($new_post_id, '_eipsi_clinical_template', $template_id);

    return $new_post_id;
}

/**
 * AJAX handler: Create form from clinical template
 */
function eipsi_ajax_create_from_clinical_template() {
    check_ajax_referer('eipsi_clinical_templates_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('No tenés permisos para crear formularios.', 'vas-dinamico-forms')));
    }

    $template_id = isset($_POST['template_id']) ? sanitize_text_field(wp_unslash($_POST['template_id'])) : '';

    if (!$template_id) {
        wp_send_json_error(array('message' => __('ID de plantilla inválido.', 'vas-dinamico-forms')));
    }

    $new_template_id = eipsi_create_form_from_clinical_template($template_id);

    if (is_wp_error($new_template_id)) {
        wp_send_json_error(array('message' => $new_template_id->get_error_message()));
    }

    $template = get_post($new_template_id);

    wp_send_json_success(array(
        'message' => sprintf(__('✅ Formulario "%s" creado correctamente.', 'vas-dinamico-forms'), $template->post_title),
        'template_id' => $new_template_id,
        'edit_url' => get_edit_post_link($new_template_id, 'raw'),
    ));
}
add_action('wp_ajax_eipsi_create_from_clinical_template', 'eipsi_ajax_create_from_clinical_template');
