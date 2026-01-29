<?php
/**
 * AJAX API Handlers for Waves Manager
 * 
 * @since 1.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register AJAX handlers
 */
add_action('wp_ajax_eipsi_save_wave', 'wp_ajax_eipsi_save_wave_handler');
add_action('wp_ajax_eipsi_delete_wave', 'wp_ajax_eipsi_delete_wave_handler');
add_action('wp_ajax_eipsi_get_available_participants', 'wp_ajax_eipsi_get_available_participants_handler');
add_action('wp_ajax_eipsi_assign_participants', 'wp_ajax_eipsi_assign_participants_handler');
add_action('wp_ajax_eipsi_extend_deadline', 'wp_ajax_eipsi_extend_deadline_handler');
add_action('wp_ajax_eipsi_send_reminder', 'wp_ajax_eipsi_send_reminder_handler');

/**
 * Create or Update Wave
 */
function wp_ajax_eipsi_save_wave_handler() {
    check_ajax_referer('eipsi_waves_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $wave_id = isset($_POST['wave_id']) ? absint($_POST['wave_id']) : 0;
    $study_id = isset($_POST['study_id']) ? absint($_POST['study_id']) : 0;
    
    if (!$study_id) {
        wp_send_json_error('Missing study_id');
    }

    $wave_data = array(
        'name' => sanitize_text_field($_POST['name'] ?? ''),
        'wave_index' => absint($_POST['wave_index'] ?? 1),
        'form_id' => absint($_POST['form_id'] ?? 0),
        'due_date' => sanitize_text_field($_POST['due_date'] ?? ''),
        'description' => sanitize_textarea_field($_POST['description'] ?? ''),
        'is_mandatory' => isset($_POST['is_mandatory']) ? 1 : 0,
        'status' => 'active' // Default to active for now
    );

    if (empty($wave_data['name']) || !$wave_data['form_id']) {
        wp_send_json_error('Missing required fields');
    }

    if ($wave_id) {
        $result = EIPSI_Wave_Service::update_wave($wave_id, $wave_data);
    } else {
        $result = EIPSI_Wave_Service::create_wave($study_id, $wave_data);
    }

    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }

    wp_send_json_success(array(
        'message' => $wave_id ? 'Onda actualizada' : 'Onda creada',
        'wave_id' => $wave_id ? $wave_id : $result
    ));
}

/**
 * Delete Wave
 */
function wp_ajax_eipsi_delete_wave_handler() {
    check_ajax_referer('eipsi_waves_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $wave_id = isset($_POST['wave_id']) ? absint($_POST['wave_id']) : 0;
    if (!$wave_id) {
        wp_send_json_error('Missing wave_id');
    }

    $result = EIPSI_Wave_Service::delete_wave($wave_id);

    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }

    wp_send_json_success('Onda eliminada');
}

/**
 * Get participants of a study that are NOT assigned to a specific wave
 */
function wp_ajax_eipsi_get_available_participants_handler() {
    check_ajax_referer('eipsi_waves_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $study_id = isset($_GET['study_id']) ? absint($_GET['study_id']) : 0;
    $wave_id = isset($_GET['wave_id']) ? absint($_GET['wave_id']) : 0;

    if (!$study_id || !$wave_id) {
        wp_send_json_error('Missing parameters');
    }

    global $wpdb;

    // Obtener study_code del estudio (porque survey_participants usa study_id como string/code en algunas partes)
    // Pero en el esquema nuevo debería ser el numeric ID o el code.
    // Veamos survey_participants...
    $study_code = $wpdb->get_var($wpdb->prepare("SELECT study_id FROM {$wpdb->prefix}survey_studies WHERE id = %d", $study_id));

    $participants = $wpdb->get_results($wpdb->prepare(
        "SELECT p.id, p.participant_id, p.full_name, p.email 
         FROM {$wpdb->prefix}survey_participants p
         WHERE (p.study_id = %d OR p.study_id = %s)
         AND p.id NOT IN (
             SELECT participant_id FROM {$wpdb->prefix}survey_assignments WHERE wave_id = %d
         )
         AND p.status = 'active'
         ORDER BY p.full_name ASC",
        $study_id, $study_code, $wave_id
    ));

    wp_send_json_success($participants);
}

/**
 * Assign multiple participants to a wave
 */
function wp_ajax_eipsi_assign_participants_handler() {
    check_ajax_referer('eipsi_waves_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $study_id = isset($_POST['study_id']) ? absint($_POST['study_id']) : 0;
    $wave_id = isset($_POST['wave_id']) ? absint($_POST['wave_id']) : 0;
    $participant_ids = isset($_POST['participant_ids']) ? array_map('absint', $_POST['participant_ids']) : array();

    if (!$study_id || !$wave_id || empty($participant_ids)) {
        wp_send_json_error('Missing parameters');
    }

    $count = 0;
    foreach ($participant_ids as $p_id) {
        $result = EIPSI_Assignment_Service::create_assignment($study_id, $wave_id, $p_id);
        if (!is_wp_error($result)) {
            $count++;
        }
    }

    wp_send_json_success(array(
        'message' => sprintf('%d participantes asignados exitosamente.', $count),
        'assigned_count' => $count
    ));
}

/**
 * Extend Wave Deadline
 */
function wp_ajax_eipsi_extend_deadline_handler() {
    check_ajax_referer('eipsi_waves_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $wave_id = isset($_POST['wave_id']) ? absint($_POST['wave_id']) : 0;
    $new_deadline = isset($_POST['new_deadline']) ? sanitize_text_field($_POST['new_deadline']) : '';

    if (!$wave_id || empty($new_deadline)) {
        wp_send_json_error('Missing parameters');
    }

    $result = EIPSI_Wave_Service::update_wave($wave_id, array('due_date' => $new_deadline));

    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }

    wp_send_json_success('Fecha de vencimiento extendida');
}

/**
 * Send Manual Reminders for Wave
 */
function wp_ajax_eipsi_send_reminder_handler() {
    check_ajax_referer('eipsi_waves_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $wave_id = isset($_POST['wave_id']) ? absint($_POST['wave_id']) : 0;
    if (!$wave_id) {
        wp_send_json_error('Missing wave_id');
    }

    $sent_count = 0;
    if (class_exists('EIPSI_Email_Service') && method_exists('EIPSI_Email_Service', 'send_manual_reminders')) {
        $sent_count = EIPSI_Email_Service::send_manual_reminders($wave_id);
    }

    wp_send_json_success(array(
        'message' => sprintf('Se han enviado %d recordatorios.', $sent_count),
        'sent' => $sent_count
    ));
}
