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
define('EIPSI_FORM_SCHEMA_VERSION_V2', '2.0.0');

/**
 * Enable legacy v1 export format
 * 
 * By default, exports are TRUE Lite v2.0 (structure format).
 * Define this as true in wp-config.php to enable v1 format exports:
 * define('EIPSI_FORMS_ENABLE_LEGACY_EXPORT', true);
 */
if (!defined('EIPSI_FORMS_ENABLE_LEGACY_EXPORT')) {
    define('EIPSI_FORMS_ENABLE_LEGACY_EXPORT', false);
}

/**
 * Available themes for form styling (replaces full styleConfig)
 */
function eipsi_get_available_themes() {
    return array(
        'clinical-blue' => array(
            'name' => 'Clinical Blue',
            'description' => 'Azul profesional, ideal para entornos clínicos',
            'colors' => array(
                'primary' => '#3B6CAA',
                'primaryHover' => '#1E3A5F',
                'secondary' => '#e3f2fd',
                'background' => '#ffffff',
                'backgroundSubtle' => '#f8f9fa',
                'text' => '#2c3e50',
                'textMuted' => '#64748b',
                'inputBg' => '#ffffff',
                'inputText' => '#2c3e50',
                'inputBorder' => '#64748b',
                'inputBorderFocus' => '#3B6CAA',
                'inputErrorBg' => '#fff5f5',
                'inputIcon' => '#3B6CAA',
                'buttonBg' => '#3B6CAA',
                'buttonText' => '#ffffff',
                'buttonHoverBg' => '#1E3A5F',
                'error' => '#d32f2f',
                'success' => '#198754',
                'warning' => '#b35900',
                'border' => '#64748b',
                'borderDark' => '#475569',
            ),
            'typography' => array(
                'fontFamilyHeading' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
                'fontFamilyBody' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
                'fontSizeBase' => '16px',
            ),
        ),
        'clinical-green' => array(
            'name' => 'Clinical Green',
            'description' => 'Verde calmante, ideal para bienestar',
            'colors' => array(
                'primary' => '#2E7D32',
                'primaryHover' => '#1B5E20',
                'secondary' => '#e8f5e9',
                'background' => '#ffffff',
                'backgroundSubtle' => '#f8faf8',
                'text' => '#1b5e20',
                'textMuted' => '#4a6b4a',
                'inputBorder' => '#4caf50',
                'inputBorderFocus' => '#2E7D32',
                'buttonBg' => '#2E7D32',
                'buttonHoverBg' => '#1B5E20',
            ),
            'typography' => array(
                'fontFamilyHeading' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
                'fontFamilyBody' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
                'fontSizeBase' => '16px',
            ),
        ),
        'minimal' => array(
            'name' => 'Minimal',
            'description' => 'Diseño limpio y simple, sin distracciones',
            'colors' => array(
                'primary' => '#333333',
                'primaryHover' => '#000000',
                'secondary' => '#f5f5f5',
                'background' => '#ffffff',
                'backgroundSubtle' => '#fafafa',
                'text' => '#333333',
                'textMuted' => '#666666',
                'inputBorder' => '#cccccc',
                'inputBorderFocus' => '#333333',
                'buttonBg' => '#333333',
                'buttonHoverBg' => '#000000',
            ),
            'typography' => array(
                'fontFamilyHeading' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
                'fontFamilyBody' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
                'fontSizeBase' => '16px',
            ),
        ),
        'high-contrast' => array(
            'name' => 'High Contrast',
            'description' => 'Alto contraste para accesibilidad',
            'colors' => array(
                'primary' => '#000000',
                'primaryHover' => '#000000',
                'secondary' => '#ffffff',
                'background' => '#ffffff',
                'backgroundSubtle' => '#ffffff',
                'text' => '#000000',
                'textMuted' => '#333333',
                'inputBg' => '#ffffff',
                'inputText' => '#000000',
                'inputBorder' => '#000000',
                'inputBorderFocus' => '#000000',
                'buttonBg' => '#000000',
                'buttonText' => '#ffffff',
                'buttonHoverBg' => '#333333',
            ),
            'typography' => array(
                'fontFamilyHeading' => 'Arial, sans-serif',
                'fontFamilyBody' => 'Arial, sans-serif',
                'fontSizeBase' => '18px',
            ),
        ),
    );
}

/**
 * Convert Gutenberg blocks to v2.0 structure format
 * TRUE Lite format: human-readable, editable, theme-based
 * 
 * @param array $blocks Parsed blocks from parse_blocks()
 * @param array $form_container_attrs Attributes from form-container block
 * @return array Structure format
 */
function eipsi_convert_blocks_to_structure($blocks, $form_container_attrs = array()) {
    $structure = array(
        'version' => '2.0',
        'title' => '',
        'formId' => '',
        'theme' => 'clinical-blue', // default
        'settings' => array(),
        'pages' => array(),
    );
    
    // Extract form settings from container
    if (!empty($form_container_attrs)) {
        $structure['formId'] = isset($form_container_attrs['formId']) ? $form_container_attrs['formId'] : '';
        $structure['theme'] = isset($form_container_attrs['preset']) ? $form_container_attrs['preset'] : 'clinical-blue';
        
        // Extract boolean settings
        $settings_map = array(
            'randomization' => 'useRandomization',
            'requireLogin' => 'requireLogin',
            'pages' => 'showProgressBar',
            'customCompletion' => 'useCustomCompletion',
        );
        
        foreach ($settings_map as $key => $attr) {
            if (isset($form_container_attrs[$attr])) {
                $structure['settings'][$key] = (bool) $form_container_attrs[$attr];
            }
        }
        
        // Completion settings
        if (!empty($form_container_attrs['completionTitle'])) {
            $structure['settings']['completionTitle'] = $form_container_attrs['completionTitle'];
        }
        if (!empty($form_container_attrs['completionMessage'])) {
            $structure['settings']['completionMessage'] = $form_container_attrs['completionMessage'];
        }
    }
    
    // Find form-container and process inner blocks (pages)
    foreach ($blocks as $block) {
        if ($block['blockName'] === 'eipsi/form-container' && !empty($block['innerBlocks'])) {
            foreach ($block['innerBlocks'] as $inner_block) {
                // Process each form-page
                if ($inner_block['blockName'] === 'eipsi/form-page') {
                    $page = eipsi_convert_page_to_structure($inner_block);
                    if ($page) {
                        $structure['pages'][] = $page;
                    }
                }
            }
            break;
        }
    }
    
    return $structure;
}

/**
 * Convert a form-page block to v2.0 page structure
 */
function eipsi_convert_page_to_structure($page_block) {
    $attrs = isset($page_block['attrs']) ? $page_block['attrs'] : array();
    
    $page = array(
        'title' => isset($attrs['title']) ? $attrs['title'] : 'Página',
        'hidden' => isset($attrs['isHidden']) ? (bool) $attrs['isHidden'] : false,
        'fields' => array(),
    );
    
    // Process fields in page
    if (!empty($page_block['innerBlocks'])) {
        foreach ($page_block['innerBlocks'] as $field_block) {
            $field = eipsi_convert_field_to_structure($field_block);
            if ($field) {
                $page['fields'][] = $field;
            }
        }
    }
    
    return $page;
}

/**
 * Convert a field block to v2.0 field structure
 */
function eipsi_convert_field_to_structure($field_block) {
    $block_name = isset($field_block['blockName']) ? $field_block['blockName'] : '';
    $attrs = isset($field_block['attrs']) ? $field_block['attrs'] : array();
    
    // Skip non-field blocks
    if (empty($block_name) || strpos($block_name, 'eipsi/') !== 0) {
        return null;
    }
    
    $field = array();
    
    // Map block types to field types
    $type_map = array(
        'eipsi/campo-texto' => 'text',
        'eipsi/campo-numerico' => 'number',
        'eipsi/campo-email' => 'email',
        'eipsi/campo-telefono' => 'tel',
        'eipsi/campo-fecha' => 'date',
        'eipsi/campo-textarea' => 'textarea',
        'eipsi/campo-select' => 'select',
        'eipsi/campo-radio' => 'radio',
        'eipsi/campo-checkbox' => 'checkbox',
        'eipsi/campo-multiple' => 'checkbox',
        'eipsi/campo-likert' => 'likert',
        'eipsi/vas-slider' => 'vas',
        'eipsi/campo-descripcion' => 'description',
        'eipsi/consent-block' => 'consent',
    );
    
    $field['type'] = isset($type_map[$block_name]) ? $type_map[$block_name] : 'text';
    
    // Extract common attributes
    if (!empty($attrs['fieldName'])) {
        $field['name'] = $attrs['fieldName'];
    } elseif (!empty($attrs['fieldKey'])) {
        $field['name'] = $attrs['fieldKey'];
    }
    
    if (!empty($attrs['label'])) {
        $field['label'] = $attrs['label'];
    }
    
    if (isset($attrs['required'])) {
        $field['required'] = (bool) $attrs['required'];
    }
    
    if (!empty($attrs['placeholder'])) {
        $field['placeholder'] = $attrs['placeholder'];
    }
    
    if (!empty($attrs['helperText'])) {
        $field['helperText'] = $attrs['helperText'];
    }
    
    // Field-specific attributes
    switch ($field['type']) {
        case 'text':
            if (!empty($attrs['fieldType'])) {
                $field['fieldType'] = $attrs['fieldType']; // email, number, etc.
            }
            break;
            
        case 'select':
        case 'radio':
        case 'checkbox':
            if (!empty($attrs['options'])) {
                // Parse semicolon-separated options
                $field['options'] = array_map('trim', explode(';', $attrs['options']));
            }
            break;
            
        case 'likert':
            if (!empty($attrs['labels'])) {
                $field['scale'] = array_map('trim', explode(';', $attrs['labels']));
            }
            break;
            
        case 'vas':
            if (!empty($attrs['labels'])) {
                $field['labels'] = array_map('trim', explode(';', $attrs['labels']));
            }
            if (isset($attrs['showCurrentValue'])) {
                $field['showValue'] = (bool) $attrs['showCurrentValue'];
            }
            break;
            
        case 'description':
            if (!empty($attrs['label'])) {
                $field['title'] = $attrs['label'];
            }
            if (!empty($attrs['helperText'])) {
                $field['text'] = $attrs['helperText'];
            }
            break;
            
        case 'consent':
            // Extract consent text from innerHTML or attrs
            if (!empty($attrs['consentText'])) {
                $field['text'] = $attrs['consentText'];
            }
            if (!empty($attrs['checkboxLabel'])) {
                $field['checkboxLabel'] = $attrs['checkboxLabel'];
            }
            break;
    }
    
    return $field;
}

/**
 * Export a form template as structured JSON
 * 
 * @param int $template_id Post ID of the form template
 * @param string $mode Export mode: 'full' (v1), 'lite' (v2 structure), 'v1lite' (legacy)
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
    
    // TRUE Lite v2.0: Structure format - human readable, theme-based
    if ($mode === 'lite') {
        $structure = eipsi_convert_blocks_to_structure($blocks, $form_container_attrs);
        $structure['title'] = $template->post_title;
        $structure['formId'] = $form_name;
        
        return array(
            'version' => '2.0',
            'meta' => array(
                'exportedAt' => current_time('c'),
                'exportedBy' => wp_get_current_user()->display_name,
                'pluginVersion' => EIPSI_FORMS_VERSION,
                'formTitle' => $template->post_title,
                'formName' => $form_name,
            ),
            'structure' => $structure,
        );
    }
    
    // Legacy formats (v1)
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
    
    // Modo 'v1lite': solo blocks simplificados (sin innerHTML/innerContent)
    if ($mode === 'v1lite') {
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
 * Convert v2.0 structure format to Gutenberg blocks
 * TRUE Lite import: structure -> blocks -> postContent
 * 
 * @param array $structure Structure format data
 * @return array Gutenberg blocks ready for serialize_blocks()
 */
function eipsi_convert_structure_to_blocks($structure) {
    $theme = isset($structure['theme']) ? $structure['theme'] : 'clinical-blue';
    $form_id = isset($structure['formId']) ? $structure['formId'] : 'form_' . time();
    $settings = isset($structure['settings']) ? $structure['settings'] : array();
    $pages = isset($structure['pages']) ? $structure['pages'] : array();
    
    // Get theme data
    $themes = eipsi_get_available_themes();
    $theme_data = isset($themes[$theme]) ? $themes[$theme] : $themes['clinical-blue'];
    
    // Build form container attributes
    $container_attrs = array(
        'formId' => $form_id,
        'preset' => $theme,
        'studyStatus' => 'open',
    );
    
    // Map settings to container attributes
    $settings_map = array(
        'randomization' => 'useRandomization',
        'requireLogin' => 'requireLogin',
        'pages' => 'showProgressBar',
        'customCompletion' => 'useCustomCompletion',
    );
    
    foreach ($settings_map as $key => $attr) {
        if (isset($settings[$key])) {
            $container_attrs[$attr] = (bool) $settings[$key];
        }
    }
    
    // Completion settings
    if (!empty($settings['completionTitle'])) {
        $container_attrs['completionTitle'] = $settings['completionTitle'];
    } else {
        $container_attrs['completionTitle'] = 'Formulario Completado';
    }
    
    if (!empty($settings['completionMessage'])) {
        $container_attrs['completionMessage'] = $settings['completionMessage'];
    } else {
        $container_attrs['completionMessage'] = 'Gracias por completar el formulario.';
    }
    
    // Add theme colors to styleConfig
    $container_attrs['styleConfig'] = array(
        'colors' => $theme_data['colors'],
        'typography' => $theme_data['typography'],
    );
    
    // Build inner blocks (pages)
    $inner_blocks = array();
    $page_num = 1;
    
    foreach ($pages as $page_data) {
        $page_block = eipsi_convert_page_structure_to_block($page_data, $page_num);
        if ($page_block) {
            $inner_blocks[] = $page_block;
            $page_num++;
        }
    }
    
    // Build form container block
    $container_block = array(
        'blockName' => 'eipsi/form-container',
        'attrs' => $container_attrs,
        'innerBlocks' => $inner_blocks,
        'innerHTML' => '',
        'innerContent' => array_fill(0, count($inner_blocks), null),
    );
    
    return array($container_block);
}

/**
 * Convert v2.0 page structure to form-page block
 */
function eipsi_convert_page_structure_to_block($page_data, $page_num) {
    $title = isset($page_data['title']) ? $page_data['title'] : 'Página ' . $page_num;
    $hidden = isset($page_data['hidden']) ? (bool) $page_data['hidden'] : false;
    $fields = isset($page_data['fields']) ? $page_data['fields'] : array();
    
    // Build page attributes
    $page_attrs = array(
        'title' => $title,
        'page' => $page_num,
    );
    
    if ($hidden) {
        $page_attrs['isHidden'] = true;
    }
    
    // Build inner blocks (fields)
    $field_blocks = array();
    
    foreach ($fields as $field_data) {
        $field_block = eipsi_convert_field_structure_to_block($field_data);
        if ($field_block) {
            $field_blocks[] = $field_block;
        }
    }
    
    // Build page block
    return array(
        'blockName' => 'eipsi/form-page',
        'attrs' => $page_attrs,
        'innerBlocks' => $field_blocks,
        'innerHTML' => '',
        'innerContent' => array_fill(0, count($field_blocks), null),
    );
}

/**
 * Convert v2.0 field structure to EIPSI block
 */
function eipsi_convert_field_structure_to_block($field_data) {
    $type = isset($field_data['type']) ? $field_data['type'] : 'text';
    
    // Map field types to block names
    $block_map = array(
        'text' => 'eipsi/campo-texto',
        'number' => 'eipsi/campo-numerico',
        'email' => 'eipsi/campo-email',
        'tel' => 'eipsi/campo-telefono',
        'date' => 'eipsi/campo-fecha',
        'textarea' => 'eipsi/campo-textarea',
        'select' => 'eipsi/campo-select',
        'radio' => 'eipsi/campo-radio',
        'checkbox' => 'eipsi/campo-multiple',
        'likert' => 'eipsi/campo-likert',
        'vas' => 'eipsi/vas-slider',
        'description' => 'eipsi/campo-descripcion',
        'consent' => 'eipsi/consent-block',
    );
    
    $block_name = isset($block_map[$type]) ? $block_map[$type] : 'eipsi/campo-texto';
    
    // Build attributes
    $attrs = array();
    
    // Common attributes
    if (!empty($field_data['name'])) {
        if ($type === 'likert') {
            $attrs['fieldKey'] = $field_data['name'];
        } else {
            $attrs['fieldName'] = $field_data['name'];
        }
    }
    
    if (!empty($field_data['label'])) {
        $attrs['label'] = $field_data['label'];
    }
    
    if (isset($field_data['required'])) {
        $attrs['required'] = (bool) $field_data['required'];
    }
    
    if (!empty($field_data['placeholder'])) {
        $attrs['placeholder'] = $field_data['placeholder'];
    }
    
    if (!empty($field_data['helperText'])) {
        $attrs['helperText'] = $field_data['helperText'];
    }
    
    // Field-specific attributes
    switch ($type) {
        case 'text':
            if (!empty($field_data['fieldType'])) {
                $attrs['fieldType'] = $field_data['fieldType'];
            }
            break;
            
        case 'select':
        case 'radio':
        case 'checkbox':
            if (!empty($field_data['options'])) {
                // Convert array to semicolon-separated string
                $attrs['options'] = is_array($field_data['options']) 
                    ? implode(';', $field_data['options']) 
                    : $field_data['options'];
            }
            break;
            
        case 'likert':
            if (!empty($field_data['scale'])) {
                $attrs['labels'] = is_array($field_data['scale']) 
                    ? implode(';', $field_data['scale']) 
                    : $field_data['scale'];
            }
            break;
            
        case 'vas':
            if (!empty($field_data['labels'])) {
                $attrs['labels'] = is_array($field_data['labels']) 
                    ? implode(';', $field_data['labels']) 
                    : $field_data['labels'];
            }
            if (isset($field_data['showValue'])) {
                $attrs['showCurrentValue'] = (bool) $field_data['showValue'];
            }
            break;
            
        case 'description':
            if (!empty($field_data['title'])) {
                $attrs['label'] = $field_data['title'];
            }
            if (!empty($field_data['text'])) {
                $attrs['helperText'] = $field_data['text'];
            }
            break;
            
        case 'consent':
            if (!empty($field_data['text'])) {
                $attrs['consentText'] = $field_data['text'];
            }
            if (!empty($field_data['checkboxLabel'])) {
                $attrs['checkboxLabel'] = $field_data['checkboxLabel'];
            }
            break;
    }
    
    return array(
        'blockName' => $block_name,
        'attrs' => $attrs,
        'innerBlocks' => array(),
        'innerHTML' => '',
        'innerContent' => array(),
    );
}

/**
 * Validate v2.0 structure format and return detailed errors
 * 
 * @param array $structure The structure data
 * @return array Validation result with 'valid' and 'errors'
 */
function eipsi_validate_v2_structure($structure) {
    $errors = array();
    
    // Check required top-level fields
    if (!isset($structure['title']) || empty($structure['title'])) {
        $errors[] = __('Falta "title" del formulario', 'eipsi-forms');
    }
    
    if (!isset($structure['pages']) || !is_array($structure['pages'])) {
        $errors[] = __('Falta "pages" (debe ser un array)', 'eipsi-forms');
    } elseif (empty($structure['pages'])) {
        $errors[] = __('El formulario debe tener al menos una página', 'eipsi-forms');
    }
    
    // Validate each page
    if (isset($structure['pages']) && is_array($structure['pages'])) {
        foreach ($structure['pages'] as $page_index => $page) {
            $page_num = $page_index + 1;
            
            if (!isset($page['title']) || empty($page['title'])) {
                $errors[] = sprintf(__('Página %d: falta título', 'eipsi-forms'), $page_num);
            }
            
            if (!isset($page['fields']) || !is_array($page['fields'])) {
                $errors[] = sprintf(__('Página %d: falta "fields" (debe ser un array)', 'eipsi-forms'), $page_num);
            } else {
                // Validate each field
                foreach ($page['fields'] as $field_index => $field) {
                    $field_num = $field_index + 1;
                    
                    if (!isset($field['type']) || empty($field['type'])) {
                        $errors[] = sprintf(__('Página %d, campo %d: falta "type"', 'eipsi-forms'), $page_num, $field_num);
                    }
                    
                    // Check for name in field types that require it
                    $named_types = array('text', 'number', 'email', 'tel', 'date', 'textarea', 'select', 'radio', 'checkbox', 'likert', 'vas');
                    if (isset($field['type']) && in_array($field['type'], $named_types) && empty($field['name'])) {
                        $errors[] = sprintf(__('Página %d, campo %d (%s): falta "name"', 'eipsi-forms'), $page_num, $field_num, $field['type']);
                    }
                }
            }
        }
    }
    
    // Validate theme if provided
    if (isset($structure['theme'])) {
        $themes = eipsi_get_available_themes();
        if (!isset($themes[$structure['theme']])) {
            $available = implode(', ', array_keys($themes));
            $errors[] = sprintf(__('Theme "%s" no válido. Disponibles: %s', 'eipsi-forms'), $structure['theme'], $available);
        }
    }
    
    return array(
        'valid' => empty($errors),
        'errors' => $errors,
    );
}

/**
 * Detect JSON format version and type
 * 
 * @param array $json_data Decoded JSON data
 * @return array Format info with 'version', 'type', and 'description'
 */
function eipsi_detect_json_format($json_data) {
    // v2.0 TRUE Lite format
    if (isset($json_data['version']) && $json_data['version'] === '2.0' && isset($json_data['structure'])) {
        return array(
            'version' => '2.0',
            'type' => 'structure',
            'description' => 'TRUE Lite v2.0 (recomendado)',
            'editable' => true,
            'theme_based' => true,
        );
    }
    
    // v1.x formats
    if (isset($json_data['schemaVersion'])) {
        $schema = $json_data['schemaVersion'];
        
        // v1 full
        if (isset($json_data['form']['postContent'])) {
            return array(
                'version' => $schema,
                'type' => 'v1-full',
                'description' => 'Formato completo v1 (legacy)',
                'editable' => false,
                'theme_based' => false,
            );
        }
        
        // v1 lite
        if (isset($json_data['form']['blocks'])) {
            return array(
                'version' => $schema,
                'type' => 'v1-lite',
                'description' => 'Formato simplificado v1 (legacy)',
                'editable' => false,
                'theme_based' => false,
            );
        }
    }
    
    // Unknown/invalid
    return array(
        'version' => 'unknown',
        'type' => 'unknown',
        'description' => 'Formato no reconocido',
        'editable' => false,
        'theme_based' => false,
    );
}

/**
 * Get format description for user messages
 */
function eipsi_get_format_friendly_name($format_info) {
    if ($format_info['version'] === '2.0') {
        return '✨ TRUE Lite v2.0 - Editable, theme-based';
    }
    if ($format_info['type'] === 'v1-full') {
        return '📦 Legacy v1 Full - Backup exacto';
    }
    if ($format_info['type'] === 'v1-lite') {
        return '📄 Legacy v1 Lite - Bloques simplificados';
    }
    return '❓ Formato desconocido';
}

/**
 * Import a form from JSON structure
 * Supports three formats:
 * - 'v2': TRUE Lite structure format (recommended)
 * - 'full': v1 includes postContent, innerHTML, innerContent
 * - 'v1lite': v1 simplified blocks only
 * 
 * @param array $json_data Decoded JSON data
 * @return int|WP_Error New template ID or error
 */
function eipsi_import_form_from_json($json_data) {
    // Detect format
    $format_info = eipsi_detect_json_format($json_data);
    $is_v2_format = $format_info['version'] === '2.0';
    $is_v1_full = $format_info['type'] === 'v1-full';
    $is_v1_lite = $format_info['type'] === 'v1-lite';
    
    // TRUE Lite v2.0 format (recommended)
    if ($is_v2_format) {
        $structure = $json_data['structure'];
        
        // Validate v2.0 structure
        $validation = eipsi_validate_v2_structure($structure);
        if (!$validation['valid']) {
            $error_msg = __('Errores de validación en el JSON v2.0:', 'eipsi-forms') . "\n";
            $error_msg .= '• ' . implode("\n• ", $validation['errors']);
            return new WP_Error('invalid_structure', $error_msg);
        }
        
        $post_title = sanitize_text_field($structure['title']);
        
        // Check if we should add "imported" suffix
        $existing = get_page_by_title($post_title, OBJECT, 'eipsi_form_template');
        if ($existing) {
            $post_title .= ' (importado)';
        }
        
        // Convert structure to blocks
        $blocks = eipsi_convert_structure_to_blocks($structure);
        
        // Enrich blocks with required keys
        $enriched_blocks = eipsi_enrich_blocks_for_serialization($blocks);
        
        // Serialize to post content
        $post_content = serialize_blocks($enriched_blocks);
        
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
        if (!empty($structure['formId'])) {
            update_post_meta($new_post_id, '_eipsi_form_name', sanitize_text_field($structure['formId']));
        }
        
        return $new_post_id;
    }
    
    // Legacy v1 format validation
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
    
    // v1 Full format: use postContent as-is
    if ($is_v1_full) {
        $post_content = $form_data['postContent'];
    } else {
        // v1 Lite format: rebuild postContent from blocks
        $blocks = $form_data['blocks'];
        
        // Enrich blocks with missing keys for serialize_blocks()
        $enriched_blocks = eipsi_enrich_blocks_for_serialization($blocks);
        
        // Serialize blocks to Gutenberg HTML
        $post_content = serialize_blocks($enriched_blocks);
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
    
    // Detect format for user feedback
    $format_info = eipsi_detect_json_format($json_data);
    
    // Show warning for legacy formats
    $is_legacy = strpos($format_info['type'], 'v1-') === 0;
    
    $new_template_id = eipsi_import_form_from_json($json_data);
    
    if (is_wp_error($new_template_id)) {
        wp_send_json_error(array('message' => $new_template_id->get_error_message()));
    }
    
    $template = get_post($new_template_id);
    
    // Build success message with format info
    $format_label = eipsi_get_format_friendly_name($format_info);
    $message = sprintf(
        __('✅ Formulario "%s" importado correctamente (%s).', 'eipsi-forms'),
        $template->post_title,
        $format_label
    );
    
    wp_send_json_success(array(
        'message' => $message,
        'template_id' => $new_template_id,
        'edit_url' => get_edit_post_link($new_template_id, 'raw'),
        'format' => $format_info,
        'is_legacy' => $is_legacy,
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
            'exportLiteTitle' => __('✨ TRUE Lite v2.0 (recomendado)', 'eipsi-forms'),
            'exportLiteDescription' => __('JSON editable a mano, basado en themes. Ideal para clínicos y control de versiones.', 'eipsi-forms'),
            'exportFullTitle' => __('Formato v1 Legacy', 'eipsi-forms'),
            'exportFullDescription' => __('Formato anterior con HTML completo. Solo para compatibilidad backward.', 'eipsi-forms'),
            'exportModeConfirm' => __('Exportar JSON', 'eipsi-forms'),
            'exportModeCancel' => __('Cancelar', 'eipsi-forms'),
            'duplicateConfirm' => __('¿Duplicar este formulario?', 'eipsi-forms'),
            'duplicateSuccess' => __('Formulario duplicado correctamente', 'eipsi-forms'),
            'duplicateError' => __('Error al duplicar el formulario', 'eipsi-forms'),
            'importTitle' => __('Importar formulario desde JSON', 'eipsi-forms'),
            'importInstructions' => __('Soporta formatos: TRUE Lite v2.0 (recomendado), v1 Full, v1 Lite. El archivo debe ser .json exportado desde EIPSI Forms.', 'eipsi-forms'),
            'importTutorial' => __('Ver documentación', 'eipsi-forms'),
            'importButton' => __('Importar', 'eipsi-forms'),
            'importCancel' => __('Cancelar', 'eipsi-forms'),
            'importSuccess' => __('Formulario importado correctamente', 'eipsi-forms'),
            'importError' => __('Error al importar el formulario', 'eipsi-forms'),
            'invalidFile' => __('Por favor, seleccioná un archivo JSON válido.', 'eipsi-forms'),
            'importParseError' => __('El archivo JSON está incompleto o corrupto.', 'eipsi-forms'),
            'importLiteError' => __('No pudimos convertir el JSON. Revisá que tenga la estructura correcta.', 'eipsi-forms'),
            'importLiteEngineError' => __('WordPress todavía no cargó el motor de bloques. Recargá la página e intentá nuevamente.', 'eipsi-forms'),
            'clinicalTemplateConfirm' => __('¿Crear un formulario nuevo basado en %s? Vas a poder editarlo antes de usarlo con pacientes.', 'eipsi-forms'),
            'clinicalTemplateCreating' => __('Creando...', 'eipsi-forms'),
            'clinicalTemplateError' => __('No pudimos crear la plantilla. Reintentá en unos segundos.', 'eipsi-forms'),
        ),
    ));
}
add_action('admin_enqueue_scripts', 'eipsi_form_library_tools_scripts');
