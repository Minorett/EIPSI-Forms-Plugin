<?php
/**
 * AJAX API Handlers for Waves Manager
 *
 * @since 1.4.0
 * @updated 1.5.2 - Added participant management and unlimited time support
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register AJAX handlers
 */
add_action('wp_ajax_eipsi_save_wave', 'wp_ajax_eipsi_save_wave_handler');
add_action('wp_ajax_eipsi_delete_wave', 'wp_ajax_eipsi_delete_wave_handler');
add_action('wp_ajax_eipsi_get_wave', 'wp_ajax_eipsi_get_wave_handler');
add_action('wp_ajax_eipsi_get_available_participants', 'wp_ajax_eipsi_get_available_participants_handler');
add_action('wp_ajax_eipsi_get_study_participants', 'wp_ajax_eipsi_get_study_participants_handler');
add_action('wp_ajax_eipsi_assign_participants', 'wp_ajax_eipsi_assign_participants_handler');
add_action('wp_ajax_eipsi_extend_deadline', 'wp_ajax_eipsi_extend_deadline_handler');
add_action('wp_ajax_eipsi_send_reminder', 'wp_ajax_eipsi_send_reminder_handler');
add_action('wp_ajax_eipsi_get_pending_participants', 'wp_ajax_eipsi_get_pending_participants_handler');

// Participant Management Handlers
// Note: wp_ajax_eipsi_add_participant is defined in study-dashboard-api.php to avoid duplication
add_action('wp_ajax_eipsi_edit_participant', 'wp_ajax_eipsi_edit_participant_handler');
add_action('wp_ajax_eipsi_delete_participant', 'wp_ajax_eipsi_delete_participant_handler');
add_action('wp_ajax_eipsi_get_participant', 'wp_ajax_eipsi_get_participant_handler');

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

    // Handle unlimited time option
    $has_time_limit = isset($_POST['has_time_limit']) ? 1 : 0;
    $completion_time_limit = isset($_POST['completion_time_limit']) ? absint($_POST['completion_time_limit']) : 0;

    $wave_data = array(
        'name' => sanitize_text_field($_POST['name'] ?? ''),
        'wave_index' => absint($_POST['wave_index'] ?? 1),
        'form_id' => absint($_POST['form_id'] ?? 0),
        'due_date' => sanitize_text_field($_POST['due_date'] ?? ''),
        'description' => sanitize_textarea_field($_POST['description'] ?? ''),
        'is_mandatory' => isset($_POST['is_mandatory']) ? 1 : 0,
        'has_time_limit' => $has_time_limit,
        'completion_time_limit' => $has_time_limit ? $completion_time_limit : null,
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
 * Get Single Wave
 */
function wp_ajax_eipsi_get_wave_handler() {
    check_ajax_referer('eipsi_waves_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $wave_id = isset($_GET['wave_id']) ? absint($_GET['wave_id']) : 0;
    if (!$wave_id) {
        wp_send_json_error('Missing wave_id');
    }

    $wave = EIPSI_Wave_Service::get_wave($wave_id);

    if (!$wave) {
        wp_send_json_error('Wave not found');
    }

    wp_send_json_success($wave);
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
    $participant_ids = isset($_POST['participant_ids']) ? array_map('absint', $_POST['participant_ids']) : array();
    $custom_message = isset($_POST['custom_message']) ? sanitize_textarea_field($_POST['custom_message']) : null;
    $study_id = isset($_POST['study_id']) ? absint($_POST['study_id']) : 0;

    if (!$wave_id) {
        wp_send_json_error('Missing wave_id');
    }

    // If no participants specified, get pending participants for this wave
    if (empty($participant_ids)) {
        if (!$study_id) {
            // Get study_id from wave
            global $wpdb;
            $wave = $wpdb->get_row($wpdb->prepare(
                "SELECT study_id FROM {$wpdb->prefix}survey_waves WHERE id = %d",
                $wave_id
            ));
            $study_id = $wave ? absint($wave->study_id) : 0;
        }

        if ($study_id && class_exists('EIPSI_Email_Service')) {
            $pending = EIPSI_Email_Service::get_pending_participants($study_id, $wave_id);
            $participant_ids = array_map(function($p) { return $p->id; }, $pending);
        }
    }

    if (empty($participant_ids)) {
        wp_send_json_error('No participants to send reminders to');
    }

    $result = array(
        'sent_count' => 0,
        'failed_count' => 0,
        'total_count' => 0,
        'errors' => array()
    );

    if (class_exists('EIPSI_Email_Service') && method_exists('EIPSI_Email_Service', 'send_manual_reminders')) {
        $result = EIPSI_Email_Service::send_manual_reminders($study_id, $participant_ids, $wave_id, $custom_message);
    }

    $message = '';
    if ($result['sent_count'] > 0) {
        $message = sprintf('✅ Se enviaron %d de %d recordatorios.', $result['sent_count'], $result['total_count']);
    } else {
        $message = 'No se pudieron enviar los recordatorios.';
    }

    if (!empty($result['errors'])) {
        $message .= ' Errores: ' . implode(', ', $result['errors']);
    }

    wp_send_json_success(array(
        'message' => $message,
        'sent' => $result['sent_count'],
        'failed' => $result['failed_count'],
        'total' => $result['total_count']
    ));
}

/**
 * Get pending participants for a wave (AJAX)
 */
function wp_ajax_eipsi_get_pending_participants_handler() {
    check_ajax_referer('eipsi_waves_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $wave_id = isset($_GET['wave_id']) ? absint($_GET['wave_id']) : 0;
    $study_id = isset($_GET['study_id']) ? absint($_GET['study_id']) : 0;

    if (!$wave_id) {
        wp_send_json_error('Missing wave_id');
    }

    if (!$study_id) {
        global $wpdb;
        $wave = $wpdb->get_row($wpdb->prepare(
            "SELECT study_id FROM {$wpdb->prefix}survey_waves WHERE id = %d",
            $wave_id
        ));
        $study_id = $wave ? absint($wave->study_id) : 0;
    }

    $participants = array();
    if ($study_id && class_exists('EIPSI_Email_Service') && method_exists('EIPSI_Email_Service', 'get_pending_participants')) {
        $participants = EIPSI_Email_Service::get_pending_participants($study_id, $wave_id);
    }

    wp_send_json_success($participants);
}

/**
 * Get all participants for a study
 */
function wp_ajax_eipsi_get_study_participants_handler() {
    check_ajax_referer('eipsi_waves_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $study_id = isset($_GET['study_id']) ? absint($_GET['study_id']) : 0;
    if (!$study_id) {
        wp_send_json_error('Missing study_id');
    }

    global $wpdb;

    $participants = $wpdb->get_results($wpdb->prepare(
        "SELECT p.id, p.email, p.first_name, p.last_name, p.created_at, p.is_active,
                CONCAT(p.first_name, ' ', p.last_name) as full_name
         FROM {$wpdb->prefix}survey_participants p
         WHERE p.survey_id = %d
         ORDER BY p.created_at DESC",
        $study_id
    ));

    wp_send_json_success($participants);
}

/**
 * Get single participant details
 */
function wp_ajax_eipsi_get_participant_handler() {
    check_ajax_referer('eipsi_waves_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $participant_id = isset($_GET['participant_id']) ? absint($_GET['participant_id']) : 0;
    if (!$participant_id) {
        wp_send_json_error('Missing participant_id');
    }

    $participant = EIPSI_Participant_Service::get_by_id($participant_id);

    if (!$participant) {
        wp_send_json_error('Participant not found');
    }

    // Remove sensitive data
    unset($participant->password_hash);

    wp_send_json_success($participant);
}

/**
 * Edit participant
 */
function wp_ajax_eipsi_edit_participant_handler() {
    check_ajax_referer('eipsi_waves_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $participant_id = isset($_POST['participant_id']) ? absint($_POST['participant_id']) : 0;
    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    $first_name = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
    $last_name = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (!$participant_id) {
        wp_send_json_error('Missing participant_id');
    }

    if (!empty($email) && !is_email($email)) {
        wp_send_json_error('Invalid email address');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'survey_participants';

    $data = array();
    $formats = array();

    if (!empty($email)) {
        // Check for duplicate email
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table_name} WHERE email = %s AND id != %d",
            $email,
            $participant_id
        ));

        if ($existing) {
            wp_send_json_error('This email is already registered to another participant');
        }

        $data['email'] = $email;
        $formats[] = '%s';
    }

    $data['first_name'] = $first_name;
    $formats[] = '%s';

    $data['last_name'] = $last_name;
    $formats[] = '%s';

    $data['is_active'] = $is_active;
    $formats[] = '%d';

    $result = $wpdb->update(
        $table_name,
        $data,
        array('id' => $participant_id),
        $formats,
        array('%d')
    );

    if ($result === false) {
        wp_send_json_error('Failed to update participant');
    }

    wp_send_json_success(array(
        'message' => 'Participant updated successfully'
    ));
}

/**
 * Delete participant
 */
function wp_ajax_eipsi_delete_participant_handler() {
    check_ajax_referer('eipsi_waves_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $participant_id = isset($_POST['participant_id']) ? absint($_POST['participant_id']) : 0;
    $delete_data = isset($_POST['delete_data']) ? true : false;

    if (!$participant_id) {
        wp_send_json_error('Missing participant_id');
    }

    global $wpdb;

    // Check if participant has submissions
    $has_submissions = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}survey_assignments WHERE participant_id = %d AND status = 'submitted'",
        $participant_id
    ));

    if ($has_submissions > 0 && !$delete_data) {
        // Just deactivate instead of delete
        $result = EIPSI_Participant_Service::set_active($participant_id, false);
        if ($result) {
            wp_send_json_success(array(
                'message' => 'Participant has submissions and was deactivated instead of deleted',
                'deactivated' => true
            ));
        } else {
            wp_send_json_error('Failed to deactivate participant');
        }
    }

    // Delete participant's assignments first
    $wpdb->delete(
        $wpdb->prefix . 'survey_assignments',
        array('participant_id' => $participant_id),
        array('%d')
    );

    // Delete participant's magic links
    $wpdb->delete(
        $wpdb->prefix . 'survey_magic_links',
        array('participant_id' => $participant_id),
        array('%d')
    );

    // Delete participant's sessions
    $wpdb->delete(
        $wpdb->prefix . 'survey_sessions',
        array('participant_id' => $participant_id),
        array('%d')
    );

    // Finally delete participant
    $result = $wpdb->delete(
        $wpdb->prefix . 'survey_participants',
        array('id' => $participant_id),
        array('%d')
    );

    if ($result === false) {
        wp_send_json_error('Failed to delete participant');
    }

    wp_send_json_success(array(
        'message' => 'Participant deleted successfully'
    ));
}
