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
add_action('wp_ajax_eipsi_validate_csv_participants', 'wp_ajax_eipsi_validate_csv_participants_handler');
add_action('wp_ajax_eipsi_import_csv_participants', 'wp_ajax_eipsi_import_csv_participants_handler');
add_action('wp_ajax_eipsi_get_participants_list', 'wp_ajax_eipsi_get_participants_list_handler');
add_action('wp_ajax_eipsi_toggle_participant_status', 'wp_ajax_eipsi_toggle_participant_status_handler');
add_action('wp_ajax_eipsi_save_study_cron_config', 'wp_ajax_eipsi_save_study_cron_config_handler');
add_action('wp_ajax_eipsi_get_study_cron_config', 'wp_ajax_eipsi_get_study_cron_config_handler');
add_action('wp_ajax_eipsi_save_study_settings', 'wp_ajax_eipsi_save_study_settings_handler');
add_action('wp_ajax_eipsi_close_study', 'wp_ajax_eipsi_close_study_handler');

/**
 * GET consolidated study data
 */
function wp_ajax_eipsi_get_study_overview_handler() {
    check_ajax_referer('eipsi_study_dashboard_nonce', 'nonce');

    if (!eipsi_user_can_manage_longitudinal()) {
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
 * POST close study
 */
function wp_ajax_eipsi_close_study_handler() {
    check_ajax_referer('eipsi_study_dashboard_nonce', 'nonce');

    if (!eipsi_user_can_manage_longitudinal()) {
        wp_send_json_error(array('message' => __('Unauthorized', 'eipsi-forms')));
    }

    $study_id = isset($_POST['study_id']) ? intval($_POST['study_id']) : 0;
    if (empty($study_id)) {
        wp_send_json_error(array('message' => __('Missing study ID', 'eipsi-forms')));
    }

    global $wpdb;

    $study = $wpdb->get_row($wpdb->prepare(
        "SELECT id, study_name, status FROM {$wpdb->prefix}survey_studies WHERE id = %d",
        $study_id
    ));

    if (!$study) {
        wp_send_json_error(array('message' => __('Study not found', 'eipsi-forms')));
    }

    $updated = $wpdb->update(
        "{$wpdb->prefix}survey_studies",
        array(
            'status' => 'completed',
            'updated_at' => current_time('mysql')
        ),
        array('id' => $study_id),
        array('%s', '%s'),
        array('%d')
    );

    if ($updated === false) {
        wp_send_json_error(array('message' => __('No se pudo cerrar el estudio.', 'eipsi-forms')));
    }

    wp_send_json_success(array(
        'message' => sprintf(__('Estudio "%s" cerrado correctamente.', 'eipsi-forms'), $study->study_name),
        'status' => 'completed'
    ));
}

/**
 * GET specific wave details
 */
function wp_ajax_eipsi_get_wave_details_handler() {
    check_ajax_referer('eipsi_study_dashboard_nonce', 'nonce');

    if (!eipsi_user_can_manage_longitudinal()) {
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

    if (!eipsi_user_can_manage_longitudinal()) {
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

    if (!eipsi_user_can_manage_longitudinal()) {
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

    if (!eipsi_user_can_manage_longitudinal()) {
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
 * This handler accepts both 'eipsi_study_dashboard_nonce' and 'eipsi_waves_nonce' for compatibility
 */
function wp_ajax_eipsi_add_participant_handler() {
    // Check nonce - accept both nonces for compatibility with different contexts
    $nonce_valid = false;

    // Try study dashboard nonce first
    if (isset($_POST['nonce'])) {
        $nonce_valid = wp_verify_nonce($_POST['nonce'], 'eipsi_study_dashboard_nonce') ||
                      wp_verify_nonce($_POST['nonce'], 'eipsi_waves_nonce');
    }

    if (!$nonce_valid) {
        wp_send_json_error('Invalid nonce');
    }

    if (!eipsi_user_can_manage_longitudinal()) {
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
            'email_sent' => true,
            'temporary_password' => $password // Include for backward compatibility
        ));
    } else {
        wp_send_json_success(array(
            'message' => 'Participante creado exitosamente, pero hubo un problema enviando el email',
            'participant_id' => $participant_id,
            'email_sent' => false,
            'temporary_password' => $password // Include for backward compatibility
        ));
    }
}

/**
 * POST validate CSV participants data
 */
function wp_ajax_eipsi_validate_csv_participants_handler() {
    check_ajax_referer('eipsi_study_dashboard_nonce', 'nonce');

    if (!eipsi_user_can_manage_longitudinal()) {
        wp_send_json_error('Unauthorized');
    }

    $study_id = isset($_POST['study_id']) ? intval($_POST['study_id']) : 0;
    $csv_data = isset($_POST['csv_data']) ? $_POST['csv_data'] : '';

    if (empty($study_id)) {
        wp_send_json_error('Missing study ID');
    }

    if (empty($csv_data)) {
        wp_send_json_error('No se proporcionaron datos CSV');
    }

    // Parsear CSV
    $participants = eipsi_parse_csv_data($csv_data);

    if (empty($participants)) {
        wp_send_json_error('No se encontraron participantes válidos en el CSV');
    }

    // Límite máximo de participantes
    if (count($participants) > 500) {
        wp_send_json_error('El archivo CSV contiene más de 500 participantes. Por favor, divide el archivo en partes más pequeñas.');
    }

    global $wpdb;

    // Validar cada participante
    $validation_results = array();
    $valid_count = 0;
    $invalid_count = 0;
    $existing_count = 0;

    foreach ($participants as $index => $participant) {
        $result = array(
            'row' => $index + 1,
            'email' => $participant['email'],
            'first_name' => $participant['first_name'],
            'last_name' => $participant['last_name'],
            'is_valid' => true,
            'errors' => array(),
            'status' => 'valid' // valid, invalid, existing
        );

        // Validar email
        if (empty($participant['email'])) {
            $result['is_valid'] = false;
            $result['errors'][] = 'Email vacío';
        } elseif (!is_email($participant['email'])) {
            $result['is_valid'] = false;
            $result['errors'][] = 'Formato de email inválido';
        } else {
            // Verificar si ya existe en el estudio
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}survey_participants WHERE survey_id = %d AND email = %s",
                $study_id,
                sanitize_email($participant['email'])
            ));

            if ($existing) {
                $result['status'] = 'existing';
                $existing_count++;
            }
        }

        // Sanitizar nombres
        $result['first_name'] = sanitize_text_field($participant['first_name']);
        $result['last_name'] = sanitize_text_field($participant['last_name']);

        if (!$result['is_valid']) {
            $result['status'] = 'invalid';
            $invalid_count++;
        } elseif ($result['status'] === 'valid') {
            $valid_count++;
        }

        $validation_results[] = $result;
    }

    wp_send_json_success(array(
        'participants' => $validation_results,
        'summary' => array(
            'total' => count($participants),
            'valid' => $valid_count,
            'invalid' => $invalid_count,
            'existing' => $existing_count
        )
    ));
}

/**
 * POST import CSV participants and send invitations
 */
function wp_ajax_eipsi_import_csv_participants_handler() {
    check_ajax_referer('eipsi_study_dashboard_nonce', 'nonce');

    if (!eipsi_user_can_manage_longitudinal()) {
        wp_send_json_error('Unauthorized');
    }

    $study_id = isset($_POST['study_id']) ? intval($_POST['study_id']) : 0;
    $participants = isset($_POST['participants']) ? $_POST['participants'] : array();

    if (empty($study_id)) {
        wp_send_json_error('Missing study ID');
    }

    if (empty($participants)) {
        wp_send_json_error('No hay participantes para importar');
    }

    // Cargar servicios necesarios
    if (!class_exists('EIPSI_Participant_Service')) {
        require_once plugin_dir_path(__FILE__) . 'services/class-participant-service.php';
    }

    if (!class_exists('EIPSI_Email_Service')) {
        require_once plugin_dir_path(__FILE__) . 'services/class-email-service.php';
    }

    $results = array(
        'imported' => 0,
        'failed' => 0,
        'emails_sent' => 0,
        'emails_failed' => 0,
        'errors' => array()
    );

    foreach ($participants as $participant) {
        // Solo importar participantes válidos que no existan
        if ($participant['status'] !== 'valid') {
            continue;
        }

        $email = sanitize_email($participant['email']);
        $first_name = sanitize_text_field($participant['first_name']);
        $last_name = sanitize_text_field($participant['last_name']);

        // Generar contraseña automática
        $password = wp_generate_password(12, false);

        $metadata = array();
        if (!empty($first_name)) {
            $metadata['first_name'] = $first_name;
        }
        if (!empty($last_name)) {
            $metadata['last_name'] = $last_name;
        }

        // Crear participante
        $participant_result = EIPSI_Participant_Service::create_participant($study_id, $email, $password, $metadata);

        if (!$participant_result['success']) {
            $results['failed']++;
            $results['errors'][] = array(
                'email' => $email,
                'error' => $participant_result['error']
            );
            continue;
        }

        $results['imported']++;
        $participant_id = $participant_result['participant_id'];

        // Enviar invitación por email
        $email_sent = EIPSI_Email_Service::send_welcome_email($study_id, $participant_id);

        if ($email_sent) {
            $results['emails_sent']++;
        } else {
            $results['emails_failed']++;
        }
    }

    wp_send_json_success(array(
        'message' => sprintf(
            'Importación completada: %d participantes importados, %d emails enviados',
            $results['imported'],
            $results['emails_sent']
        ),
        'results' => $results
    ));
}

/**
 * Parse CSV data string into array
 *
 * @param string $csv_data CSV content
 * @return array Parsed participants
 */
function eipsi_parse_csv_data($csv_data) {
    $participants = array();

    // Normalizar saltos de línea
    $csv_data = str_replace("\r\n", "\n", $csv_data);
    $csv_data = str_replace("\r", "\n", $csv_data);

    $lines = explode("\n", $csv_data);

    $is_first_line = true;
    foreach ($lines as $line) {
        $line = trim($line);

        // Saltar líneas vacías
        if (empty($line)) {
            continue;
        }

        // Parsear CSV respetando comillas
        $row = eipsi_parse_csv_line($line);

        // Saltar encabezados si es la primera línea
        if ($is_first_line) {
            $is_first_line = false;
            // Detectar si es encabezado (contiene 'email' o similar)
            $first_col = strtolower(trim($row[0] ?? ''));
            if (strpos($first_col, 'email') !== false) {
                continue;
            }
        }

        // Extraer datos
        $participant = array(
            'email' => trim($row[0] ?? ''),
            'first_name' => trim($row[1] ?? ''),
            'last_name' => trim($row[2] ?? '')
        );

        // Solo agregar si hay email
        if (!empty($participant['email'])) {
            $participants[] = $participant;
        }
    }

    return $participants;
}

/**
 * Parse a single CSV line respecting quotes
 *
 * @param string $line CSV line
 * @return array Fields
 */
function eipsi_parse_csv_line($line) {
    $fields = array();
    $field = '';
    $in_quotes = false;
    $length = strlen($line);

    for ($i = 0; $i < $length; $i++) {
        $char = $line[$i];

        if ($char === '"') {
            if ($in_quotes && $i + 1 < $length && $line[$i + 1] === '"') {
                // Comilla escapada
                $field .= '"';
                $i++;
            } else {
                $in_quotes = !$in_quotes;
            }
        } elseif ($char === ',' && !$in_quotes) {
            $fields[] = $field;
            $field = '';
        } else {
            $field .= $char;
        }
    }

    $fields[] = $field;

    return $fields;
}

/**
 * GET participants list with pagination and filters
 */
function wp_ajax_eipsi_get_participants_list_handler() {
    check_ajax_referer('eipsi_study_dashboard_nonce', 'nonce');

    if (!eipsi_user_can_manage_longitudinal()) {
        wp_send_json_error('Unauthorized');
    }

    $study_id = isset($_GET['study_id']) ? intval($_GET['study_id']) : 0;
    if (empty($study_id)) {
        wp_send_json_error('Missing study ID');
    }

    // Parámetros de paginación
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $per_page = isset($_GET['per_page']) ? max(1, min(100, intval($_GET['per_page']))) : 20;

    // Filtros
    $filters = array();
    if (isset($_GET['status']) && in_array($_GET['status'], array('active', 'inactive'))) {
        $filters['status'] = $_GET['status'];
    }
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $filters['search'] = sanitize_text_field($_GET['search']);
    }

    // Cargar servicio de participantes
    if (!class_exists('EIPSI_Participant_Service')) {
        require_once plugin_dir_path(__FILE__) . 'services/class-participant-service.php';
    }

    $result = EIPSI_Participant_Service::list_participants($study_id, $page, $per_page, $filters);

    wp_send_json_success($result);
}

/**
 * POST toggle participant active status
 */
function wp_ajax_eipsi_toggle_participant_status_handler() {
    check_ajax_referer('eipsi_study_dashboard_nonce', 'nonce');

    if (!eipsi_user_can_manage_longitudinal()) {
        wp_send_json_error('Unauthorized');
    }

    $participant_id = isset($_POST['participant_id']) ? intval($_POST['participant_id']) : 0;
    $is_active = isset($_POST['is_active']) ? filter_var($_POST['is_active'], FILTER_VALIDATE_BOOLEAN) : true;

    if (empty($participant_id)) {
        wp_send_json_error('Missing participant ID');
    }

    // Cargar servicio de participantes
    if (!class_exists('EIPSI_Participant_Service')) {
        require_once plugin_dir_path(__FILE__) . 'services/class-participant-service.php';
    }

    $success = EIPSI_Participant_Service::set_active($participant_id, $is_active);

    if ($success) {
        $status_text = $is_active ? 'activado' : 'desactivado';
        wp_send_json_success(array(
            'message' => sprintf('Participante %s exitosamente', $status_text),
            'is_active' => $is_active
        ));
    } else {
        wp_send_json_error('Error al cambiar el estado del participante');
    }
}

/**
 * POST save study cron configuration
 */
function wp_ajax_eipsi_save_study_cron_config_handler() {
    check_ajax_referer('eipsi_study_cron_nonce', 'nonce');

    if (!eipsi_user_can_manage_longitudinal()) {
        wp_send_json_error('Unauthorized');
    }

    $study_id = isset($_POST['study_id']) ? intval($_POST['study_id']) : 0;
    if (empty($study_id)) {
        wp_send_json_error('Missing study ID');
    }

    // Validar y sanitizar datos
    $cron_enabled = isset($_POST['cron_enabled']) ? filter_var($_POST['cron_enabled'], FILTER_VALIDATE_BOOLEAN) : false;
    $cron_frequency = isset($_POST['cron_frequency']) ? sanitize_text_field($_POST['cron_frequency']) : '';
    $cron_actions = isset($_POST['cron_actions']) ? array_map('sanitize_text_field', (array)$_POST['cron_actions']) : array();

    // Validaciones
    $errors = array();

    if ($cron_enabled) {
        if (empty($cron_frequency)) {
            $errors[] = 'La frecuencia es requerida cuando los cron jobs están activados.';
        } elseif (!in_array($cron_frequency, array('daily', 'weekly', 'monthly'))) {
            $errors[] = 'Frecuencia inválida.';
        }

        if (empty($cron_actions)) {
            $errors[] = 'Debes seleccionar al menos una acción.';
        }
    }

    if (!empty($errors)) {
        wp_send_json_error(array(
            'message' => implode(' ', $errors)
        ));
    }

    // Guardar configuración
    update_post_meta($study_id, '_eipsi_study_cron_enabled', $cron_enabled);
    update_post_meta($study_id, '_eipsi_study_cron_frequency', $cron_frequency);
    update_post_meta($study_id, '_eipsi_study_cron_actions', $cron_actions);

    // Programar cron job si está activado
    if ($cron_enabled) {
        // Desprogramar cualquier cron job existente
        wp_clear_scheduled_hook('eipsi_study_cron_job', array($study_id));

        // Programar nuevo cron job según la frecuencia
        $timestamp = current_time('timestamp');
        
        switch ($cron_frequency) {
            case 'daily':
                $next_run = strtotime('tomorrow', $timestamp);
                break;
            case 'weekly':
                $next_run = strtotime('next monday', $timestamp);
                break;
            case 'monthly':
                $next_run = strtotime('first day of next month', $timestamp);
                break;
            default:
                $next_run = strtotime('tomorrow', $timestamp);
        }

        // Programar el evento
        wp_schedule_event($next_run, 'eipsi_' . $cron_frequency, 'eipsi_study_cron_job', array($study_id));

        // Guardar información de ejecución
        update_post_meta($study_id, '_eipsi_study_cron_next_run', date('Y-m-d H:i:s', $next_run));
    } else {
        // Desprogramar cron job si se desactiva
        wp_clear_scheduled_hook('eipsi_study_cron_job', array($study_id));
        delete_post_meta($study_id, '_eipsi_study_cron_next_run');
    }

    // Obtener información actualizada
    $last_run = get_post_meta($study_id, '_eipsi_study_cron_last_run', true);
    $next_run = get_post_meta($study_id, '_eipsi_study_cron_next_run', true);

    wp_send_json_success(array(
        'message' => 'Configuración de cron jobs guardada exitosamente.',
        'last_run' => $last_run ? date('Y-m-d H:i:s', strtotime($last_run)) : 'Nunca',
        'next_run' => $next_run ? date('Y-m-d H:i:s', strtotime($next_run)) : 'No programada'
    ));
}

/**
 * GET study cron configuration HTML
 */
function wp_ajax_eipsi_get_study_cron_config_handler() {
    check_ajax_referer('eipsi_study_dashboard_nonce', 'nonce');

    if (!eipsi_user_can_manage_longitudinal()) {
        wp_send_json_error('Unauthorized');
    }

    $study_id = isset($_GET['study_id']) ? intval($_GET['study_id']) : 0;
    if (empty($study_id)) {
        wp_send_json_error('Missing study ID');
    }

    // Load the cron jobs tab content
    ob_start();
    include plugin_dir_path(__FILE__) . 'tabs/study-cron-jobs-tab.php';
    $html = ob_get_clean();

    wp_send_json_success(array(
        'html' => $html
    ));
}

/**
 * POST save study settings
 */
function wp_ajax_eipsi_save_study_settings_handler() {
    check_ajax_referer('eipsi_study_dashboard_nonce', 'nonce');

    if (!eipsi_user_can_manage_longitudinal()) {
        wp_send_json_error('Unauthorized');
    }

    $study_id = isset($_POST['study_id']) ? intval($_POST['study_id']) : 0;
    if (empty($study_id)) {
        wp_send_json_error('Missing study ID');
    }

    global $wpdb;

    // Verify study exists and is in draft status
    $study = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}survey_studies WHERE id = %d",
        $study_id
    ));

    if (!$study) {
        wp_send_json_error('Study not found');
    }

    if ($study->status !== 'draft') {
        wp_send_json_error('Only draft studies can be edited');
    }

    // Sanitize and validate input
    $study_name = isset($_POST['study_name']) ? sanitize_text_field($_POST['study_name']) : '';
    $study_description = isset($_POST['study_description']) ? sanitize_textarea_field($_POST['study_description']) : '';
    $time_config = isset($_POST['time_config']) ? sanitize_text_field($_POST['time_config']) : 'limited';
    $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';
    $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : '';

    // Validations
    $errors = array();

    if (empty($study_name)) {
        $errors[] = 'El nombre del estudio es requerido.';
    }

    if ($time_config === 'limited') {
        if (empty($start_date)) {
            $errors[] = 'La fecha de inicio es requerida cuando el tiempo es limitado.';
        }

        if (empty($end_date)) {
            $errors[] = 'La fecha de finalización es requerida cuando el tiempo es limitado.';
        }

        if (!empty($start_date) && !empty($end_date) && strtotime($end_date) <= strtotime($start_date)) {
            $errors[] = 'La fecha de finalización debe ser posterior a la fecha de inicio.';
        }
    }

    if (!empty($errors)) {
        wp_send_json_error(array(
            'message' => implode(' ', $errors)
        ));
    }

    // Prepare update data
    $update_data = array(
        'name' => $study_name,
        'description' => $study_description,
        'status' => 'draft'
    );

    if ($time_config === 'limited') {
        $update_data['start_date'] = $start_date;
        $update_data['end_date'] = $end_date;
    } else {
        $update_data['start_date'] = null;
        $update_data['end_date'] = null;
    }

    // Update study in database
    $updated = $wpdb->update(
        "{$wpdb->prefix}survey_studies",
        $update_data,
        array('id' => $study_id),
        array('%s', '%s', '%s'),
        array('%d')
    );

    if ($updated === false) {
        wp_send_json_error('Failed to update study settings');
    }

    wp_send_json_success(array(
        'message' => 'Configuración del estudio guardada exitosamente.',
        'study_id' => $study_id
    ));
}
