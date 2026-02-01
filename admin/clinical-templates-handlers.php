<?php
/**
 * Clinical Templates AJAX Handlers
 *
 * @package EIPSI_Forms
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Include the clinical templates functions
require_once plugin_dir_path(__FILE__) . 'clinical-templates.php';

/**
 * AJAX Handler: Get clinical templates list
 *
 * @since 1.5.0
 */
function eipsi_get_clinical_templates_handler() {
    // Verificar nonce
    $nonce = '';
    if (isset($_GET['nonce'])) {
        $nonce = sanitize_text_field(wp_unslash($_GET['nonce']));
    } elseif (isset($_POST['nonce'])) {
        $nonce = sanitize_text_field(wp_unslash($_POST['nonce']));
    }

    if (empty($nonce) || !wp_verify_nonce($nonce, 'eipsi_admin_nonce')) {
        wp_send_json_error(array(
            'message' => __('Invalid security token', 'eipsi-forms')
        ), 403);
        return;
    }

    // Obtener templates disponibles
    $templates = eipsi_get_clinical_templates();
    
    if (empty($templates)) {
        wp_send_json_success(array());
        return;
    }

    $templates_list = array_map(function($template) {
        return array(
            'id' => sanitize_text_field($template['id']),
            'name' => esc_html($template['name']),
            'description' => esc_html($template['description']),
            'icon' => esc_html($template['icon']),
            'category' => sanitize_text_field($template['category']),
            'time' => sanitize_text_field($template['time']),
        );
    }, $templates);

    wp_send_json_success($templates_list);
}

/**
 * AJAX Handler: Calculate clinical score
 * Used by frontend to display results after form completion
 *
 * @since 1.5.0
 */
function eipsi_calculate_clinical_score_handler() {
    // Verificar nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'eipsi_forms_nonce')) {
        wp_send_json_error(array(
            'message' => __('Invalid security token', 'eipsi-forms')
        ), 403);
        return;
    }

    $form_id = isset($_POST['form_id']) ? sanitize_text_field($_POST['form_id']) : '';
    $responses = isset($_POST['responses']) ? $_POST['responses'] : array();

    if (empty($form_id) || empty($responses)) {
        wp_send_json_error(array(
            'message' => __('Missing required data', 'eipsi-forms')
        ), 400);
        return;
    }

    // Sanitize responses
    $sanitized_responses = array();
    foreach ($responses as $key => $value) {
        $sanitized_responses[sanitize_text_field($key)] = sanitize_text_field($value);
    }

    // Detect scale type
    $scale_type = eipsi_detect_scale_type($form_id, $sanitized_responses);

    if (!$scale_type) {
        wp_send_json_error(array(
            'message' => __('Could not detect scale type', 'eipsi-forms')
        ), 400);
        return;
    }

    // Calculate score
    $score_result = eipsi_calculate_clinical_score($scale_type, $sanitized_responses);

    if (!$score_result) {
        wp_send_json_error(array(
            'message' => __('Error calculating score', 'eipsi-forms')
        ), 500);
        return;
    }

    // Add scale type to result
    $score_result['scale_type'] = $scale_type;
    $score_result['scale_name'] = eipsi_get_clinical_templates()[$scale_type]['name'] ?? $scale_type;

    wp_send_json_success(array(
        'score' => $score_result,
        'scale_type' => $scale_type,
    ));
}

/**
 * Generate clinical template content by ID
 * This function is called via AJAX when applying a template
 *
 * @since 1.5.0
 */
function eipsi_get_clinical_template_content_handler() {
    // Verificar nonce
    $nonce = isset($_GET['nonce']) ? sanitize_text_field(wp_unslash($_GET['nonce'])) : '';
    
    if (empty($nonce) || !wp_verify_nonce($nonce, 'eipsi_admin_nonce')) {
        wp_send_json_error(array(
            'message' => __('Invalid security token', 'eipsi-forms')
        ), 403);
        return;
    }

    $template_id = isset($_GET['template_id']) ? sanitize_text_field(wp_unslash($_GET['template_id'])) : '';

    if (empty($template_id)) {
        wp_send_json_error(array(
            'message' => __('Template ID required', 'eipsi-forms')
        ), 400);
        return;
    }

    $content = eipsi_get_clinical_template_content($template_id);

    if (is_wp_error($content)) {
        wp_send_json_error(array(
            'message' => $content->get_error_message()
        ), 404);
        return;
    }

    wp_send_json_success(array(
        'content' => $content,
        'template_id' => $template_id,
    ));
}

// Register AJAX handlers
add_action('wp_ajax_eipsi_get_clinical_templates', 'eipsi_get_clinical_templates_handler');
add_action('wp_ajax_eipsi_get_clinical_template_content', 'eipsi_get_clinical_template_content_handler');
