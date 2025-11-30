<?php
/**
 * Demo Templates - Generic EIPSI Templates
 *
 * Generic form templates built entirely with real EIPSI Gutenberg blocks.
 * These are NOT clinical scales - they're demonstration templates that show
 * how to use conditional logic, multi-page forms, and various field types.
 *
 * @package VAS_Dinamico_Forms
 * @since 1.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get available demo templates
 *
 * @return array
 */
function eipsi_get_demo_templates() {
    return array(
        'blank' => array(
            'id' => 'blank',
            'name' => __('Formulario en blanco', 'vas-dinamico-forms'),
            'description' => __('Empezar con un contenedor vacío', 'vas-dinamico-forms'),
            'icon' => '📄',
        ),
        'anxiety_intake_demo' => array(
            'id' => 'anxiety_intake_demo',
            'name' => __('Ingreso ansiedad breve (demo)', 'vas-dinamico-forms'),
            'description' => __('Formulario demo de 2 páginas con VAS, radio y lógica condicional', 'vas-dinamico-forms'),
            'icon' => '🧠',
        ),
        'session_satisfaction_demo' => array(
            'id' => 'session_satisfaction_demo',
            'name' => __('Satisfacción de sesión (demo)', 'vas-dinamico-forms'),
            'description' => __('Formulario demo de página única con Likert y campo condicional', 'vas-dinamico-forms'),
            'icon' => '⭐',
        ),
    );
}

/**
 * Generate anxiety intake demo template (2 pages, with conditional logic)
 *
 * @return string Block markup
 */
function eipsi_generate_anxiety_intake_demo() {
    $form_id = 'ingreso-ansiedad-' . substr(uniqid(), -6);

    $content = <<<GUTENBERG
<!-- wp:vas-dinamico/form-container {"formId":"{$form_id}","submitButtonLabel":"Enviar formulario","presetName":"Clinical Blue","allowBackwardsNav":true,"description":"Este es un formulario demo que muestra cómo usar campos VAS, Radio y lógica condicional."} -->
<div class="wp-block-vas-dinamico-form-container">

<!-- wp:vas-dinamico/form-page {"pageNumber":1} -->
<div class="wp-block-vas-dinamico-pagina">

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Página 1: Estado general</h3>
<!-- /wp:heading -->

<!-- wp:vas-dinamico/vas-slider {"fieldId":"nivel_ansiedad_actual","question":"¿Cómo calificarías tu nivel de ansiedad en este momento?","minLabel":"Nada ansioso/a","maxLabel":"Extremadamente ansioso/a","required":true} /-->

<!-- wp:vas-dinamico/vas-slider {"fieldId":"dificultad_dormir","question":"¿Qué tan difícil te resulta dormir últimamente?","minLabel":"Ninguna dificultad","maxLabel":"Muy difícil","required":true} /-->

<!-- wp:vas-dinamico/campo-radio {"fieldId":"ataques_panico_semana","question":"¿Tuviste ataques de pánico esta semana?","options":[{"label":"Sí","value":"si"},{"label":"No","value":"no"}],"required":true} /-->

</div>
<!-- /wp:vas-dinamico/form-page -->

<!-- wp:vas-dinamico/form-page {"pageNumber":2,"conditionalLogic":{"enabled":true,"rules":[{"fieldId":"nivel_ansiedad_actual","operator":">=","value":"70"}],"action":"show"}} -->
<div class="wp-block-vas-dinamico-pagina">

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Página 2: Información adicional</h3>
<!-- /wp:heading -->

<!-- wp:vas-dinamico/campo-descripcion {"content":"Esta página solo aparece si tu nivel de ansiedad es mayor o igual a 70."} /-->

<!-- wp:vas-dinamico/campo-textarea {"fieldId":"crisis_descripcion","question":"¿Podrías contarnos brevemente qué está pasando?","placeholder":"Escribí acá con tus propias palabras...","required":false} /-->

<!-- wp:vas-dinamico/campo-radio {"fieldId":"ayuda_profesional","question":"¿Estás recibiendo ayuda profesional actualmente?","options":[{"label":"Sí, con psicólogo/a","value":"psicologo"},{"label":"Sí, con psiquiatra","value":"psiquiatra"},{"label":"Ambos","value":"ambos"},{"label":"No, todavía no","value":"no"}],"required":true} /-->

</div>
<!-- /wp:vas-dinamico/form-page -->

<!-- wp:vas-dinamico/form-page {"pageNumber":3,"conditionalLogic":{"enabled":true,"rules":[{"fieldId":"ataques_panico_semana","operator":"==","value":"si"}],"action":"show"}} -->
<div class="wp-block-vas-dinamico-pagina">

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Página 3: Sobre los ataques de pánico</h3>
<!-- /wp:heading -->

<!-- wp:vas-dinamico/campo-descripcion {"content":"Esta página solo aparece si respondiste que sí tuviste ataques de pánico."} /-->

<!-- wp:vas-dinamico/campo-select {"fieldId":"frecuencia_ataques","question":"¿Con qué frecuencia tuviste ataques de pánico esta semana?","options":[{"label":"Una vez","value":"1"},{"label":"2-3 veces","value":"2-3"},{"label":"4-6 veces","value":"4-6"},{"label":"Todos los días","value":"diario"}],"required":true} /-->

<!-- wp:vas-dinamico/campo-textarea {"fieldId":"algo_mas_agregar","question":"¿Hay algo más que quieras contarnos?","placeholder":"Opcional","required":false} /-->

</div>
<!-- /wp:vas-dinamico/form-page -->

</div>
<!-- /wp:vas-dinamico/form-container -->
GUTENBERG;

    return $content;
}

/**
 * Generate session satisfaction demo template (1 page, with conditional logic)
 *
 * @return string Block markup
 */
function eipsi_generate_session_satisfaction_demo() {
    $form_id = 'satisfaccion-sesion-' . substr(uniqid(), -6);

    $content = <<<GUTENBERG
<!-- wp:vas-dinamico/form-container {"formId":"{$form_id}","submitButtonLabel":"Enviar respuesta","presetName":"Clinical Blue","allowBackwardsNav":false,"description":"Contanos qué te pareció la sesión de hoy."} -->
<div class="wp-block-vas-dinamico-form-container">

<!-- wp:vas-dinamico/form-page {"pageNumber":1} -->
<div class="wp-block-vas-dinamico-pagina">

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Tu opinión nos ayuda a mejorar</h3>
<!-- /wp:heading -->

<!-- wp:vas-dinamico/campo-likert {"fieldId":"utilidad_sesion","question":"¿Qué tan útil te resultó esta sesión?","minLabel":"Nada útil","maxLabel":"Muy útil","scaleType":"1-5","required":true} /-->

<!-- wp:vas-dinamico/campo-textarea {"fieldId":"que_ayudo_mas","question":"¿Qué fue lo que más te ayudó hoy?","placeholder":"Compartí con confianza...","required":false} /-->

<!-- wp:vas-dinamico/campo-textarea {"fieldId":"que_mejorar","question":"¿Qué podríamos mejorar para vos?","placeholder":"Cualquier sugerencia es bienvenida","required":false,"conditionalLogic":{"enabled":true,"rules":[{"fieldId":"utilidad_sesion","operator":"<=","value":"2"}],"action":"show"}} /-->

<!-- wp:vas-dinamico/campo-multiple {"fieldId":"temas_trabajar","question":"¿Qué temas te gustaría seguir trabajando? (podés elegir varios)","options":[{"label":"Ansiedad","value":"ansiedad"},{"label":"Estado de ánimo","value":"animo"},{"label":"Relaciones interpersonales","value":"relaciones"},{"label":"Autoestima","value":"autoestima"},{"label":"Otro (especificá abajo)","value":"otro"}],"required":false} /-->

<!-- wp:vas-dinamico/campo-texto {"fieldId":"otro_tema","question":"Si elegiste 'Otro', especificá:","placeholder":"Escribí el tema que te interesa","required":false,"conditionalLogic":{"enabled":true,"rules":[{"fieldId":"temas_trabajar","operator":"contains","value":"otro"}],"action":"show"}} /-->

</div>
<!-- /wp:vas-dinamico/form-page -->

</div>
<!-- /wp:vas-dinamico/form-container -->
GUTENBERG;

    return $content;
}

/**
 * Generate demo template content by ID
 *
 * @param string $template_id
 * @return string|WP_Error Block markup or error
 */
function eipsi_get_demo_template_content($template_id) {
    switch ($template_id) {
        case 'blank':
            // For blank, we return an empty form container with a single page
            $form_id = 'formulario-' . substr(uniqid(), -6);
            return sprintf(
                '<!-- wp:vas-dinamico/form-container {"formId":"%s","submitButtonLabel":"Enviar","presetName":"Clinical Blue","allowBackwardsNav":true,"description":""} -->
<div class="wp-block-vas-dinamico-form-container">

<!-- wp:vas-dinamico/form-page {"pageNumber":1} -->
<div class="wp-block-vas-dinamico-pagina">

<!-- wp:paragraph -->
<p>%s</p>
<!-- /wp:paragraph -->

</div>
<!-- /wp:vas-dinamico/form-page -->

</div>
<!-- /wp:vas-dinamico/form-container -->',
                esc_attr($form_id),
                esc_html__('Agregá bloques EIPSI (campos, páginas, etc.) para empezar a armar tu formulario.', 'vas-dinamico-forms')
            );

        case 'anxiety_intake_demo':
            return eipsi_generate_anxiety_intake_demo();

        case 'session_satisfaction_demo':
            return eipsi_generate_session_satisfaction_demo();

        default:
            return new WP_Error('invalid_template', __('Plantilla demo no encontrada.', 'vas-dinamico-forms'));
    }
}

/**
 * Prepare demo templates payload for the block editor
 *
 * @return array
 */
function eipsi_prepare_demo_templates_payload() {
    $templates = eipsi_get_demo_templates();
    $payload = array();

    foreach ($templates as $template_id => $template_meta) {
        $content = eipsi_get_demo_template_content($template_id);

        if (is_wp_error($content)) {
            continue;
        }

        $payload[] = array(
            'id' => $template_id,
            'name' => $template_meta['name'],
            'description' => $template_meta['description'],
            'icon' => $template_meta['icon'],
            'content' => $content,
        );
    }

    return $payload;
}

/**
 * Localize demo templates for editor JavaScript
 */
function eipsi_localize_demo_templates() {
    $payload = eipsi_prepare_demo_templates_payload();

    wp_localize_script(
        'vas-dinamico-blocks-editor',
        'EIPSIDemoTemplates',
        array(
            'templates' => $payload,
            'strings' => array(
                'selectLabel' => __('Plantillas EIPSI (demo)', 'vas-dinamico-forms'),
                'selectPlaceholder' => __('Elegí una plantilla', 'vas-dinamico-forms'),
                'apply' => __('Aplicar plantilla', 'vas-dinamico-forms'),
                'confirmReplace' => __('Esto reemplazará el contenido actual del formulario. ¿Continuar?', 'vas-dinamico-forms'),
                'success' => __('Plantilla aplicada correctamente.', 'vas-dinamico-forms'),
                'empty' => __('Próximamente agregaremos más demos pensados para tu consultorio.', 'vas-dinamico-forms'),
            ),
        )
    );
}
add_action('enqueue_block_editor_assets', 'eipsi_localize_demo_templates');
