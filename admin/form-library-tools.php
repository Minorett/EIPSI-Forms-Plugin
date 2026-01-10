<?php
/**
 * Form Library Tools - Export, Import, Duplicate
 * 
 * Herramientas para gestionar formularios en la Form Library:
 * - Exportar formularios como JSON
 * - Importar formularios desde JSON
 * - Duplicar formularios con 1 click
 * 
 * @package EIPSI_Forms
 * @since 1.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Define schema version for JSON export/import
 */
define('EIPSI_FORM_SCHEMA_VERSION', '1.0.0');

/**
 * Export a form template as structured JSON
 * 
 * @param int $template_id Post ID of the form template
 * @param string $mode Export mode: 'full' (default) or 'lite'
 * @return array|WP_Error Structured data or error
 */
function eipsi_export_form_as_json($template_id, $mode = 'full') {
    $template = get_post($template_id);
    
    if (!$template || $template->post_type !== 'eipsi_form_template') {
        return new WP_Error('invalid_form', __('El formulario no existe o no es válido.', 'eipsi-forms'));
    }
    
    // Parse blocks from post_content
    $blocks = parse_blocks($template->post_content);
    
    // Extract form_name from form-container block
    $form_name = '';
    $form_container_attrs = array();
    
    foreach ($blocks as $block) {
        if ($block['blockName'] === 'eipsi/form-container') {
            $form_name = isset($block['attrs']['formId']) ? $block['attrs']['formId'] : '';
            $form_container_attrs = $block['attrs'];
            break;
        }
    }
    
    // Build base export structure
    $export_data = array(
        'schemaVersion' => EIPSI_FORM_SCHEMA_VERSION,
        'meta' => array(
            'exportedAt' => current_time('c'),
            'exportedBy' => wp_get_current_user()->display_name,
            'pluginVersion' => EIPSI_FORMS_VERSION,
            'formTitle' => $template->post_title,
            'formName' => $form_name,
        ),
        'form' => array(
            'title' => $template->post_title,
            'formId' => $form_name,
        ),
        'metadata' => array(
            '_eipsi_form_name' => get_post_meta($template_id, '_eipsi_form_name', true),
        ),
    );
    
    // Modo 'lite': solo blocks simplificados (sin innerHTML/innerContent)
    // Ideal para edición manual y demos clínicos
    if ($mode === 'lite') {
        $export_data['form']['blocks'] = eipsi_simplify_blocks_for_export($blocks);
    } else {
        // Modo 'full': incluye todo (compatible con export anterior)
        $export_data['form']['blocks'] = $blocks;
        $export_data['form']['postContent'] = $template->post_content;
        $export_data['form']['formContainerAttrs'] = $form_container_attrs;
    }
    
    return $export_data;
}

/**
 * Simplify blocks for lite export mode
 * Removes innerHTML, innerContent, and renders only structure + attrs
 * 
 * @param array $blocks Parsed blocks from parse_blocks()
 * @return array Simplified blocks
 */
function eipsi_simplify_blocks_for_export($blocks) {
    $simplified = array();
    
    foreach ($blocks as $block) {
        $simple_block = array(
            'blockName' => $block['blockName'],
            'attrs' => isset($block['attrs']) ? $block['attrs'] : array(),
        );
        
        // Recursively simplify innerBlocks
        if (isset($block['innerBlocks']) && is_array($block['innerBlocks']) && count($block['innerBlocks']) > 0) {
            $simple_block['innerBlocks'] = eipsi_simplify_blocks_for_export($block['innerBlocks']);
        } else {
            $simple_block['innerBlocks'] = array();
        }
        
        $simplified[] = $simple_block;
    }
    
    return $simplified;
}

/**
 * Import a form from JSON structure
 * Supports two formats:
 * - 'full': includes postContent, innerHTML, innerContent (backward compatible)
 * - 'lite': only blockName, attrs, innerBlocks (editable by hand, for demos)
 * 
 * @param array $json_data Decoded JSON data
 * @return int|WP_Error New template ID or error
 */
function eipsi_import_form_from_json($json_data) {
    // Validate schema version
    if (!isset($json_data['schemaVersion'])) {
        return new WP_Error('invalid_schema', __('El archivo JSON no tiene un esquema válido.', 'eipsi-forms'));
    }
    
    $schema_version = $json_data['schemaVersion'];
    
    // Check if we support this schema version
    if (version_compare($schema_version, EIPSI_FORM_SCHEMA_VERSION, '>')) {
        return new WP_Error(
            'unsupported_schema',
            sprintf(
                __('Este JSON usa una versión de esquema más nueva (%s). Actualizá el plugin EIPSI Forms.', 'eipsi-forms'),
                $schema_version
            )
        );
    }
    
    // Validate required fields
    if (!isset($json_data['form']) || !isset($json_data['form']['title'])) {
        return new WP_Error('invalid_structure', __('El archivo JSON no tiene la estructura requerida (falta form.title).', 'eipsi-forms'));
    }
    
    $form_data = $json_data['form'];
    
    // Validate that blocks exist
    if (!isset($form_data['blocks']) || !is_array($form_data['blocks'])) {
        return new WP_Error('invalid_structure', __('El archivo JSON no tiene bloques válidos (falta form.blocks).', 'eipsi-forms'));
    }
    
    // Prepare post data
    $post_title = sanitize_text_field($form_data['title']);
    
    // Check if we should add "imported" suffix
    $existing = get_page_by_title($post_title, OBJECT, 'eipsi_form_template');
    if ($existing) {
        $post_title .= ' (importado)';
    }
    
    // Detect format: 'full' (has postContent) vs 'lite' (blocks only)
    $is_lite_format = !isset($form_data['postContent']) || empty($form_data['postContent']);
    
    if ($is_lite_format) {
        // Lite format: rebuild postContent from blocks
        $blocks = $form_data['blocks'];
        
        // Enrich blocks with missing keys for serialize_blocks()
        $enriched_blocks = eipsi_enrich_blocks_for_serialization($blocks);
        
        // Serialize blocks to Gutenberg HTML
        $post_content = serialize_blocks($enriched_blocks);
    } else {
        // Full format: use postContent as-is
        $post_content = $form_data['postContent'];
    }
    
    // Create new form template post
    $new_post_id = wp_insert_post(array(
        'post_title' => $post_title,
        'post_content' => $post_content,
        'post_status' => 'publish',
        'post_type' => 'eipsi_form_template',
        'post_author' => get_current_user_id(),
    ));
    
    if (is_wp_error($new_post_id)) {
        return $new_post_id;
    }
    
    // Restore metadata
    if (isset($json_data['metadata'])) {
        foreach ($json_data['metadata'] as $meta_key => $meta_value) {
            update_post_meta($new_post_id, $meta_key, $meta_value);
        }
    }
    
    // If metadata doesn't include _eipsi_form_name, extract it
    if (!isset($json_data['metadata']['_eipsi_form_name']) && !empty($form_data['formId'])) {
        update_post_meta($new_post_id, '_eipsi_form_name', sanitize_text_field($form_data['formId']));
    }
    
    return $new_post_id;
}

/**
 * Sanitize JSON string to properly preserve escape sequences
 * Especially important for newlines (\n) and special characters
 *
 * @param string $json_string Raw JSON string from POST
 * @return string Sanitized JSON string
 */
function eipsi_sanitize_json_string($json_string) {
    // Remove WordPress slashes (wp_unslash can corrupt escaped newlines)
    // We need to carefully handle escaped sequences
    if (function_exists('wp_unslash')) {
        // First, detect if we have double-escaped sequences
        $has_double_escapes = (strpos($json_string, '\\\\n') !== false ||
                               strpos($json_string, '\\\\t') !== false ||
                               strpos($json_string, '\\\\r') !== false);

        if ($has_double_escapes) {
            // Replace \\ with \ (but not \\n which should stay as \n)
            $json_string = preg_replace('/\\\\\\\\(?=[ntr])/', '\\\\', $json_string);
        }

        // Now remove single backslashes that aren't part of escape sequences
        // This is tricky - we want to keep \n \t \r but remove stray \
        $json_string = preg_replace('/(?<!\\\\)\\\\+(?!["ntrbf\\/\\\\])/', '', $json_string);
    }

    return $json_string;
}

/**
 * Validate and restore RichText attribute escape sequences
 * Ensures \n, \t, etc. are properly preserved during JSON processing
 *
 * @param mixed $value Attribute value to validate/restore
 * @param string $attr_name Attribute name for debugging
 * @return mixed Sanitized value
 */
function eipsi_validate_richtext_attribute($value, $attr_name = '') {
    if (!is_string($value) || empty($value)) {
        return $value;
    }

    // Common RichText attributes that should preserve newlines
    $richtext_attrs = array(
        'contenido', 'label', 'helperText', 'placeholder',
        'textoComplementario', 'etiquetaCheckbox', 'description',
        'errorMessage', 'successMessage', 'instructions',
    );

    // Check if this attribute is likely RichText content
    $is_richtext = false;
    foreach ($richtext_attrs as $pattern) {
        if (strpos($attr_name, $pattern) !== false) {
            $is_richtext = true;
            break;
        }
    }

    if (!$is_richtext) {
        return $value;
    }

    // Restore escaped newlines that might have been corrupted
    // Common corruption: "muynbien" from "\n" being interpreted as "n"
    if (preg_match('/nyb|ybn|nbie|ybien/', $value)) {
        // Try to detect corrupted newlines
        $value = preg_replace('/(?<=[a-zA-Z])n(?=[a-zA-Z])/', "\n", $value);
    }

    // Ensure proper escaping for JSON serialization
    // This will be handled by json_encode later, but we ensure consistency
    return $value;
}

/**
 * Enrich simplified blocks for serialize_blocks()
 * Adds missing innerHTML, innerContent keys required by WordPress
 * PRESERVES RichText escape sequences and special characters
 *
 * @param array $blocks Simplified blocks from lite JSON
 * @return array Enriched blocks ready for serialize_blocks()
 */
function eipsi_enrich_blocks_for_serialization($blocks) {
    $enriched = array();

    foreach ($blocks as $block) {
        // Get existing attributes
        $attrs = isset($block['attrs']) ? $block['attrs'] : array();

        // Validate and restore RichText attributes to preserve newlines
        foreach ($attrs as $attr_name => $attr_value) {
            $attrs[$attr_name] = eipsi_validate_richtext_attribute($attr_value, $attr_name);
        }

        // Generate missing fieldKey for input blocks if needed
        $blockName = isset($block['blockName']) ? $block['blockName'] : '';

        // Enrich attributes based on block type
        $enriched_attrs = eipsi_enrich_block_attributes($blockName, $attrs);

        $enriched_block = array(
            'blockName' => $blockName,
            'attrs' => $enriched_attrs,
            'innerHTML' => '',
            'innerContent' => array(),
        );

        // Recursively enrich innerBlocks
        if (isset($block['innerBlocks']) && is_array($block['innerBlocks']) && count($block['innerBlocks']) > 0) {
            $enriched_block['innerBlocks'] = eipsi_enrich_blocks_for_serialization($block['innerBlocks']);
            // Add placeholder for inner content
            $enriched_block['innerContent'] = array_fill(0, count($enriched_block['innerBlocks']), null);
        } else {
            $enriched_block['innerBlocks'] = array();
        }

        $enriched[] = $enriched_block;
    }

    return $enriched;
}

/**
 * Enrich block attributes with missing defaults
 * Validates and adds required attributes for each block type
 *
 * @param string $block_name Block type name
 * @param array $attrs Existing block attributes
 * @return array Enriched attributes
 */
function eipsi_enrich_block_attributes($block_name, $attrs) {
    // Default values for all blocks
    $enriched = $attrs;

    // Ensure fieldKey exists for input blocks
    $input_blocks = array(
        'eipsi/campo-texto',
        'eipsi/campo-numerico',
        'eipsi/campo-select',
        'eipsi/campo-checkbox',
        'eipsi/campo-radio',
        'eipsi/campo-fecha',
        'eipsi/campo-email',
        'eipsi/campo-telefono',
        'eipsi/campo-textarea',
    );

    if (in_array($block_name, $input_blocks)) {
        if (empty($enriched['fieldKey'])) {
            // Generate unique fieldKey from label or block name
            $label = isset($enriched['label']) ? $enriched['label'] : $block_name;
            $timestamp = isset($enriched['timestamp']) ? $enriched['timestamp'] : time();
            $enriched['fieldKey'] = 'field_' . hash('sha256', $label . $timestamp);
        }
    }

    // Likert blocks need labels and fieldKey
    if ($block_name === 'eipsi/campo-likert') {
        if (empty($enriched['fieldKey'])) {
            $label = isset($enriched['label']) ? $enriched['label'] : 'likert';
            $timestamp = isset($enriched['timestamp']) ? $enriched['timestamp'] : time();
            $enriched['fieldKey'] = 'field_' . hash('sha256', $label . $timestamp);
        }
        if (!isset($enriched['required'])) {
            $enriched['required'] = true;
        }
    }

    // VAS Slider blocks need labels
    if ($block_name === 'eipsi/vas-slider') {
        if (!isset($enriched['showCurrentValue'])) {
            $enriched['showCurrentValue'] = false;
        }
    }

    // Info blocks (description, consent) need content validated
    $info_blocks = array(
        'eipsi/campo-descripcion',
        'eipsi/consent-block',
    );

    if (in_array($block_name, $info_blocks)) {
        // Ensure contenido exists and is not empty
        if (empty($enriched['contenido'])) {
            $enriched['contenido'] = '<p>Contenido del bloque</p>';
        }
    }

    return $enriched;
}

/**
 * Duplicate a form template
 * 
 * @param int $template_id Original template ID
 * @return int|WP_Error New template ID or error
 */
function eipsi_duplicate_form($template_id) {
    $original = get_post($template_id);
    
    if (!$original || $original->post_type !== 'eipsi_form_template') {
        return new WP_Error('invalid_form', __('El formulario no existe o no es válido.', 'eipsi-forms'));
    }
    
    // Create duplicate post
    $new_title = sprintf(__('Copia de %s', 'eipsi-forms'), $original->post_title);
    
    $new_post_id = wp_insert_post(array(
        'post_title' => $new_title,
        'post_content' => $original->post_content,
        'post_status' => 'publish',
        'post_type' => 'eipsi_form_template',
        'post_author' => get_current_user_id(),
    ));
    
    if (is_wp_error($new_post_id)) {
        return $new_post_id;
    }
    
    // Copy all post meta
    $meta_keys = get_post_meta($template_id);
    
    foreach ($meta_keys as $meta_key => $meta_values) {
        // Skip internal WordPress meta
        if (substr($meta_key, 0, 1) === '_' && in_array($meta_key, array('_edit_lock', '_edit_last'))) {
            continue;
        }
        
        foreach ($meta_values as $meta_value) {
            add_post_meta($new_post_id, $meta_key, maybe_unserialize($meta_value));
        }
    }
    
    return $new_post_id;
}

/**
 * AJAX handler: Export form as JSON (download)
 */
function eipsi_ajax_export_form() {
    check_ajax_referer('eipsi_form_tools_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('No tenés permisos para exportar formularios.', 'eipsi-forms')));
    }
    
    $template_id = isset($_POST['template_id']) ? absint($_POST['template_id']) : 0;
    $mode = isset($_POST['mode']) ? sanitize_text_field($_POST['mode']) : 'full';
    
    if (!$template_id) {
        wp_send_json_error(array('message' => __('ID de formulario inválido.', 'eipsi-forms')));
    }
    
    // Validate mode
    if (!in_array($mode, array('full', 'lite'), true)) {
        $mode = 'full';
    }
    
    $export_data = eipsi_export_form_as_json($template_id, $mode);
    
    if (is_wp_error($export_data)) {
        wp_send_json_error(array('message' => $export_data->get_error_message()));
    }
    
    // Generate filename with mode suffix
    $template = get_post($template_id);
    $mode_suffix = ($mode === 'lite') ? '-lite' : '';
    $filename = sanitize_title($template->post_title) . $mode_suffix . '-' . date('Y-m-d') . '.json';
    
    wp_send_json_success(array(
        'data' => $export_data,
        'filename' => $filename,
        'mode' => $mode,
    ));
}
add_action('wp_ajax_eipsi_export_form', 'eipsi_ajax_export_form');

/**
 * AJAX handler: Import form from JSON
 */
function eipsi_ajax_import_form() {
    check_ajax_referer('eipsi_form_tools_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('No tenés permisos para importar formularios.', 'eipsi-forms')));
    }
    
    if (!isset($_POST['json_data'])) {
        wp_send_json_error(array('message' => __('No se recibió el archivo JSON.', 'eipsi-forms')));
    }

    $json_string = wp_unslash($_POST['json_data']);

    // Sanitize JSON string to preserve escape sequences (newlines, etc.)
    $json_string = eipsi_sanitize_json_string($json_string);

    $json_data = json_decode($json_string, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        wp_send_json_error(array(
            'message' => sprintf(
                __('Error al leer el JSON: %s', 'eipsi-forms'),
                json_last_error_msg()
            ),
        ));
    }
    
    $new_template_id = eipsi_import_form_from_json($json_data);
    
    if (is_wp_error($new_template_id)) {
        wp_send_json_error(array('message' => $new_template_id->get_error_message()));
    }
    
    $template = get_post($new_template_id);
    
    wp_send_json_success(array(
        'message' => sprintf(
            __('✅ Formulario "%s" importado correctamente.', 'eipsi-forms'),
            $template->post_title
        ),
        'template_id' => $new_template_id,
        'edit_url' => get_edit_post_link($new_template_id, 'raw'),
    ));
}
add_action('wp_ajax_eipsi_import_form', 'eipsi_ajax_import_form');

/**
 * AJAX handler: Duplicate form
 */
function eipsi_ajax_duplicate_form() {
    check_ajax_referer('eipsi_form_tools_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('No tenés permisos para duplicar formularios.', 'eipsi-forms')));
    }
    
    $template_id = isset($_POST['template_id']) ? absint($_POST['template_id']) : 0;
    
    if (!$template_id) {
        wp_send_json_error(array('message' => __('ID de formulario inválido.', 'eipsi-forms')));
    }
    
    $new_template_id = eipsi_duplicate_form($template_id);
    
    if (is_wp_error($new_template_id)) {
        wp_send_json_error(array('message' => $new_template_id->get_error_message()));
    }
    
    $template = get_post($new_template_id);
    
    wp_send_json_success(array(
        'message' => sprintf(
            __('✅ Formulario duplicado: "%s"', 'eipsi-forms'),
            $template->post_title
        ),
        'template_id' => $new_template_id,
        'edit_url' => get_edit_post_link($new_template_id, 'raw'),
    ));
}
add_action('wp_ajax_eipsi_duplicate_form', 'eipsi_ajax_duplicate_form');

/**
 * Add row actions to Form Library list table
 */
function eipsi_form_library_row_actions($actions, $post) {
    if ($post->post_type !== 'eipsi_form_template') {
        return $actions;
    }
    
    // Add Export action
    $actions['export'] = sprintf(
        '<a href="#" class="eipsi-export-form" data-template-id="%d" data-template-name="%s">%s</a>',
        $post->ID,
        esc_attr($post->post_title),
        __('Exportar JSON', 'eipsi-forms')
    );
    
    // Add Duplicate action
    $actions['duplicate'] = sprintf(
        '<a href="#" class="eipsi-duplicate-form" data-template-id="%d" data-template-name="%s">%s</a>',
        $post->ID,
        esc_attr($post->post_title),
        __('Duplicar', 'eipsi-forms')
    );
    
    return $actions;
}
add_filter('post_row_actions', 'eipsi_form_library_row_actions', 10, 2);

/**
 * Add "Import Form" button to Form Library page
 */
function eipsi_form_library_import_button() {
    $screen = get_current_screen();
    
    if (!$screen || $screen->post_type !== 'eipsi_form_template' || $screen->base !== 'edit') {
        return;
    }
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Add Import button next to "Add New"
        $('.page-title-action').first().after(
            '<a href="#" class="page-title-action eipsi-import-form-btn" style="margin-left: 8px;">⬆ <?php echo esc_js(__('Importar formulario', 'eipsi-forms')); ?></a>'
        );
    });
    </script>
    <?php
}
add_action('admin_footer', 'eipsi_form_library_import_button');

/**
 * Enqueue admin scripts for Form Library tools
 */
function eipsi_form_library_tools_scripts() {
    $screen = get_current_screen();
    
    if (!$screen || $screen->post_type !== 'eipsi_form_template') {
        return;
    }
    
    wp_enqueue_script(
        'eipsi-form-library-tools',
        EIPSI_FORMS_PLUGIN_URL . 'assets/js/form-library-tools.js',
        array('jquery', 'wp-blocks'),
        EIPSI_FORMS_VERSION,
        true
    );

    // Ensure custom blocks (and their save() implementations) are available to serialize lite JSON
    wp_enqueue_script('eipsi-blocks-editor');
    
    wp_localize_script('eipsi-form-library-tools', 'eipsiFormTools', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('eipsi_form_tools_nonce'),
        'clinicalTemplatesNonce' => wp_create_nonce('eipsi_clinical_templates_nonce'),
        'templatesUrl' => EIPSI_FORMS_PLUGIN_URL . 'templates/',
        'strings' => array(
            'exportSuccess' => __('Formulario exportado correctamente', 'eipsi-forms'),
            'exportError' => __('Error al exportar el formulario', 'eipsi-forms'),
            'exportModalTitle' => __('Exportar formulario', 'eipsi-forms'),
            'exportModalSubtitle' => __('Seleccioná el tipo de JSON que necesitás:', 'eipsi-forms'),
            'exportLiteTitle' => __('✨ Formato simplificado (recomendado)', 'eipsi-forms'),
            'exportLiteDescription' => __('JSON limpio, editable a mano. Ideal para demos clínicas y control de versiones.', 'eipsi-forms'),
            'exportFullTitle' => __('Formato completo', 'eipsi-forms'),
            'exportFullDescription' => __('Incluye HTML Gutenberg, innerHTML y metadatos internos. Útil para backups exactos.', 'eipsi-forms'),
            'exportModeConfirm' => __('Exportar JSON', 'eipsi-forms'),
            'exportModeCancel' => __('Cancelar', 'eipsi-forms'),
            'duplicateConfirm' => __('¿Duplicar este formulario?', 'eipsi-forms'),
            'duplicateSuccess' => __('Formulario duplicado correctamente', 'eipsi-forms'),
            'duplicateError' => __('Error al duplicar el formulario', 'eipsi-forms'),
            'importTitle' => __('Importar formulario desde JSON', 'eipsi-forms'),
            'importInstructions' => __('Seleccioná un archivo .json exportado desde EIPSI Forms:', 'eipsi-forms'),
            'importTutorial' => __('Ver tutorial de creación', 'eipsi-forms'),
            'importButton' => __('Importar', 'eipsi-forms'),
            'importCancel' => __('Cancelar', 'eipsi-forms'),
            'importSuccess' => __('Formulario importado correctamente', 'eipsi-forms'),
            'importError' => __('Error al importar el formulario', 'eipsi-forms'),
            'invalidFile' => __('Por favor, seleccioná un archivo JSON válido.', 'eipsi-forms'),
            'importParseError' => __('El archivo JSON está incompleto o corrupto.', 'eipsi-forms'),
            'importLiteError' => __('No pudimos convertir el JSON simplificado. Revisá que todos los bloques tengan "blockName" y "attrs".', 'eipsi-forms'),
            'importLiteEngineError' => __('WordPress todavía no cargó el motor de bloques. Recargá la página e intentá nuevamente.', 'eipsi-forms'),
            'clinicalTemplateConfirm' => __('¿Crear un formulario nuevo basado en %s? Vas a poder editarlo antes de usarlo con pacientes.', 'eipsi-forms'),
            'clinicalTemplateCreating' => __('Creando...', 'eipsi-forms'),
            'clinicalTemplateError' => __('No pudimos crear la plantilla. Reintentá en unos segundos.', 'eipsi-forms'),
        ),
    ));
}
add_action('admin_enqueue_scripts', 'eipsi_form_library_tools_scripts');
