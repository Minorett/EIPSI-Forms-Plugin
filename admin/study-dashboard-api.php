<?php
/**
 * AJAX API Handlers for Study Dashboard
 * 
 * @since 1.5.2
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register AJAX handlers
 */
add_action('wp_ajax_eipsi_get_study_overview', 'wp_ajax_eipsi_get_study_overview_handler');
add_action('wp_ajax_eipsi_get_wave_details', 'wp_ajax_eipsi_get_wave_details_handler');
add_action('wp_ajax_eipsi_send_wave_reminder_manual', 'wp_ajax_eipsi_send_wave_reminder_manual_handler');
add_action('wp_ajax_eipsi_extend_wave_deadline', 'wp_ajax_eipsi_extend_wave_deadline_handler');
add_action('wp_ajax_eipsi_get_study_email_logs', 'wp_ajax_eipsi_get_study_email_logs_handler');
add_action('wp_ajax_eipsi_add_participant', 'wp_ajax_eipsi_add_participant_handler');

/**
 * GET consolidated study data
 */
function wp_ajax_eipsi_get_study_overview_handler() {
    check_ajax_referer('eipsi_study_dashboard_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $study_id = isset($_GET['study_id']) ? intval($_GET['study_id']) : 0;
    if (empty($study_id)) {
        wp_send_json_error('Missing study ID');
    }

    global $wpdb;

    // 1. General study info (usar 'id' como PK, no 'study_id')
    $study = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}survey_studies WHERE id = %d",
        $study_id
    ));

    if (!$study) {
        wp_send_json_error('Study not found');
    }

    // 2. Participant stats
    // La tabla participants usa 'survey_id' (que es el ID del estudio), no 'study_id'
    $participants_stats = array(
        'total' => (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}survey_participants WHERE survey_id = %d",
            $study_id
        )),
        'completed' => (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT participant_id) FROM {$wpdb->prefix}survey_assignments 
             WHERE study_id = %d AND status = 'submitted'",
            $study_id
        )),
        'in_progress' => (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT participant_id) FROM {$wpdb->prefix}survey_assignments 
             WHERE study_id = %d AND status = 'in_progress'",
            $study_id
        )),
        'inactive' => (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}survey_participants 
             WHERE survey_id = %d AND is_active = 0",
            $study_id
        )),
    );

    // 3. Waves stats
    $waves = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}survey_waves WHERE study_id = %d ORDER BY wave_index ASC",
        $study_id
    ));

    $waves_stats = array();
    foreach ($waves as $wave) {
        $total_assignments = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}survey_assignments WHERE wave_id = %d",
            $wave->id
        ));
        $completed_assignments = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}survey_assignments WHERE wave_id = %d AND status = 'submitted'",
            $wave->id
        ));
        
        // Usar nombres de columnas correctos según el schema
        $waves_stats[] = array(
            'id' => $wave->id,
            'wave_name' => $wave->name,
            'form_id' => $wave->form_id,
            'deadline' => $wave->due_date,
            'status' => $wave->status,
            'total' => $total_assignments,
            'completed' => $completed_assignments,
            'progress' => ($total_assignments > 0) ? round(($completed_assignments / $total_assignments) * 100) : 0,
            'reminders_sent' => 0 // TODO: Implement reminder tracking
        );
    }

    // 4. Email stats
    // La tabla email_log usa survey_id (INT), no study_id
    $emails_stats = array(
        'sent_today' => (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}survey_email_log 
             WHERE survey_id = %d AND DATE(sent_at) = CURDATE()",
            $study_id
        )),
        'failed' => (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}survey_email_log 
             WHERE survey_id = %d AND status = 'failed'",
            $study_id
        )),
        'last_sent' => $wpdb->get_var($wpdb->prepare(
            "SELECT sent_at FROM {$wpdb->prefix}survey_email_log 
             WHERE survey_id = %d ORDER BY sent_at DESC LIMIT 1",
            $study_id
        )),
    );

    wp_send_json_success(array(
        'general' => $study,
        'participants' => $participants_stats,
        'waves' => $waves_stats,
        'emails' => $emails_stats
    ));
}

/**
 * GET specific wave details
 */
function wp_ajax_eipsi_get_wave_details_handler() {
    check_ajax_referer('eipsi_study_dashboard_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $wave_id = isset($_GET['wave_id']) ? (int) $_GET['wave_id'] : 0;
    if (!$wave_id) {
        wp_send_json_error('Missing wave ID');
    }

    global $wpdb;

    $assignments = $wpdb->get_results($wpdb->prepare(
        "SELECT a.*, p.email, CONCAT(p.first_name, ' ', p.last_name) as full_name 
         FROM {$wpdb->prefix}survey_assignments a
         JOIN {$wpdb->prefix}survey_participants p ON a.participant_id = p.id
         WHERE a.wave_id = %d",
        $wave_id
    ));

    wp_send_json_success($assignments);
}

/**
 * POST send manual reminder
 */
function wp_ajax_eipsi_send_wave_reminder_manual_handler() {
    check_ajax_referer('eipsi_study_dashboard_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $wave_id = isset($_POST['wave_id']) ? (int) $_POST['wave_id'] : 0;
    if (!$wave_id) {
        wp_send_json_error('Missing wave ID');
    }

    // Usar Email Service para enviar recordatorios
    // NOTA: send_manual_reminders será implementado plenamente en Fase 2
    $sent_count = 0;
    if (method_exists('EIPSI_Email_Service', 'send_manual_reminders')) {
        $sent_count = EIPSI_Email_Service::send_manual_reminders($wave_id);
    }

    wp_send_json_success(array(
        'message' => sprintf(__('Se han enviado %d recordatorios.', 'eipsi-forms'), $sent_count),
        'sent' => $sent_count
    ));
}

/**
 * POST extend wave deadline
 */
function wp_ajax_eipsi_extend_wave_deadline_handler() {
    check_ajax_referer('eipsi_study_dashboard_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $wave_id = isset($_POST['wave_id']) ? (int) $_POST['wave_id'] : 0;
    $new_deadline = isset($_POST['new_deadline']) ? sanitize_text_field($_POST['new_deadline']) : '';

    if (!$wave_id || empty($new_deadline)) {
        wp_send_json_error('Missing parameters');
    }

    global $wpdb;
    $updated = $wpdb->update(
        "{$wpdb->prefix}survey_waves",
        array('end_date' => $new_deadline),
        array('id' => $wave_id),
        array('%s'),
        array('%d')
    );

    if ($updated !== false) {
        wp_send_json_success('Deadline extended successfully');
    } else {
        wp_send_json_error('Failed to extend deadline');
    }
}

/**
 * GET email logs
 */
function wp_ajax_eipsi_get_study_email_logs_handler() {
    check_ajax_referer('eipsi_study_dashboard_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $study_id = isset($_GET['study_id']) ? intval($_GET['study_id']) : 0;
    if (empty($study_id)) {
        wp_send_json_error('Missing study ID');
    }

    global $wpdb;
    // La tabla email_log usa survey_id (INT), no study_id
    $logs = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}survey_email_log 
         WHERE survey_id = %d 
         ORDER BY sent_at DESC LIMIT 50",
        $study_id
    ));

    wp_send_json_success($logs);
}

/**
 * POST add participant and send invitation
 */
function wp_ajax_eipsi_add_participant_handler() {
    check_ajax_referer('eipsi_study_dashboard_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $study_id = isset($_POST['study_id']) ? intval($_POST['study_id']) : 0;
    if (empty($study_id)) {
        wp_send_json_error('Missing study ID');
    }

    // Sanitizar y validar datos
    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    $first_name = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
    $last_name = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';
    $password = isset($_POST['password']) ? sanitize_text_field($_POST['password']) : '';

    // Validaciones
    if (empty($email) || !is_email($email)) {
        wp_send_json_error('Email inválido');
    }

    // Generar contraseña automática si no se proporcionó
    if (empty($password)) {
        $password = wp_generate_password(12, false);
    }

    // Validar longitud mínima de contraseña
    if (strlen($password) < 8) {
        wp_send_json_error('La contraseña debe tener al menos 8 caracteres');
    }

    // Cargar servicios necesarios
    if (!class_exists('EIPSI_Participant_Service')) {
        require_once plugin_dir_path(__FILE__) . 'services/class-participant-service.php';
    }

    if (!class_exists('EIPSI_Email_Service')) {
        require_once plugin_dir_path(__FILE__) . 'services/class-email-service.php';
    }

    // Crear participante
    $metadata = array();
    if (!empty($first_name)) {
        $metadata['first_name'] = $first_name;
    }
    if (!empty($last_name)) {
        $metadata['last_name'] = $last_name;
    }

    $participant_result = EIPSI_Participant_Service::create_participant($study_id, $email, $password, $metadata);

    if (!$participant_result['success']) {
        switch ($participant_result['error']) {
            case 'invalid_email':
                wp_send_json_error('Formato de email inválido');
            case 'short_password':
                wp_send_json_error('La contraseña debe tener al menos 8 caracteres');
            case 'email_exists':
                wp_send_json_error('Este email ya existe en el estudio');
            default:
                wp_send_json_error('Error al crear el participante');
        }
    }

    $participant_id = $participant_result['participant_id'];

    // Enviar invitación por email
    $email_sent = EIPSI_Email_Service::send_welcome_email($study_id, $participant_id);

    if ($email_sent) {
        wp_send_json_success(array(
            'message' => 'Participante creado exitosamente e invitación enviada',
            'participant_id' => $participant_id,
            'email_sent' => true
        ));
    } else {
        wp_send_json_success(array(
            'message' => 'Participante creado exitosamente, pero hubo un problema enviando el email',
            'participant_id' => $participant_id,
            'email_sent' => false
        ));
    }
}
