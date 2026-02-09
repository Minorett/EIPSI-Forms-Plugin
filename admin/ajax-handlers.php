<?php
if (!defined('ABSPATH')) {
    exit;
}

function generate_stable_form_id($form_name) {
    global $wpdb;
    
    $initials = get_form_initials($form_name);
    
    if (strlen($initials) < 2) {
        $slug = sanitize_title($form_name);
        $initials = strtoupper(substr($slug, 0, 3));
    }
    
    $slug = sanitize_title($form_name);
    $hash = substr(md5($slug), 0, 6);
    $form_id = "{$initials}-{$hash}";
    
    $table_name = $wpdb->prefix . 'vas_form_results';
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE form_id = %s",
        $form_id
    ));
    
    if ($exists > 0) {
        return $form_id;
    }
    
    return $form_id;
}

function get_form_initials($form_name) {
    $words = explode(' ', trim($form_name));
    $initials = '';
    
    foreach ($words as $word) {
        // Limpiar caracteres especiales
        $clean_word = preg_replace('/[^a-zA-Z0-9]/', '', $word);
        
        if (!empty($clean_word)) {
            if (strlen($clean_word) >= 3) {
                $initials .= strtoupper(substr($clean_word, 0, 3));
            } else {
                $initials .= strtoupper($clean_word); // Palabra completa si < 3
            }
            
            if (strlen($initials) >= 3) break; // Máximo 3 caracteres total
        }
    }
    
    return !empty($initials) ? $initials : 'UNK'; // Fallback
}

/**
 * Study status resolver (ethics guard)
 * Finds template by _eipsi_form_name (formId slug) and returns open|closed.
 *
 * @param string $form_name
 * @return string
 */
function eipsi_get_study_status_for_form_name($form_name) {
    $form_name = sanitize_text_field($form_name);

    if (empty($form_name)) {
        return 'open';
    }

    $templates = get_posts(array(
        'post_type' => 'eipsi_form_template',
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'meta_query' => array(
            array(
                'key' => '_eipsi_form_name',
                'value' => $form_name,
                'compare' => '=',
            )
        ),
    ));

    if (empty($templates)) {
        return 'open';
    }

    $template_id = (int) $templates[0];
    $status = get_post_meta($template_id, '_eipsi_study_status', true);

    return ($status === 'closed') ? 'closed' : 'open';
}

function generateStableFingerprint($user_data) {
    $components = array(
        'email' => strtolower(trim($user_data['email'] ?? '')),
        'name' => normalizeName($user_data['name'] ?? ''),
    );
    
    $fingerprint_string = implode('|', array_filter($components));
    
    if ($fingerprint_string) {
        $hash = substr(hash('sha256', $fingerprint_string), 0, 8);
        return "FP-{$hash}";
    } else {
        $session_id = session_id();
        if (empty($session_id)) {
            session_start();
            $session_id = session_id();
        }
        $remote_addr = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $session_hash = substr(md5($session_id . $remote_addr), 0, 6);
        return "FP-SESS-{$session_hash}";
    }
}

function normalizeName($name) {
    return strtoupper(trim($name));
}

add_action('wp_ajax_nopriv_eipsi_forms_submit_form', 'eipsi_forms_submit_form_handler');
add_action('wp_ajax_eipsi_forms_submit_form', 'eipsi_forms_submit_form_handler');

add_action('wp_ajax_nopriv_eipsi_get_response_details', 'eipsi_ajax_get_response_details');
add_action('wp_ajax_eipsi_get_response_details', 'eipsi_ajax_get_response_details');

add_action('wp_ajax_nopriv_eipsi_track_event', 'eipsi_track_event_handler');
add_action('wp_ajax_eipsi_track_event', 'eipsi_track_event_handler');

add_action('wp_ajax_eipsi_test_db_connection', 'eipsi_test_db_connection_handler');
add_action('wp_ajax_eipsi_save_db_config', 'eipsi_save_db_config_handler');
add_action('wp_ajax_eipsi_disable_external_db', 'eipsi_disable_external_db_handler');
add_action('wp_ajax_eipsi_get_db_status', 'eipsi_get_db_status_handler');
add_action('wp_ajax_eipsi_check_external_db', 'eipsi_check_external_db_handler');
add_action('wp_ajax_nopriv_eipsi_check_external_db', 'eipsi_check_external_db_handler');

add_action('wp_ajax_eipsi_save_privacy_config', 'eipsi_save_privacy_config_handler');
add_action('wp_ajax_eipsi_save_global_privacy_config', 'eipsi_save_global_privacy_config_handler');
add_action('wp_ajax_eipsi_verify_schema', 'eipsi_verify_schema_handler');
add_action('wp_ajax_eipsi_verify_local_schema', 'eipsi_verify_local_schema_handler');
add_action('wp_ajax_eipsi_check_table_status', 'eipsi_check_table_status_handler');
add_action('wp_ajax_eipsi_check_local_table_status', 'eipsi_check_local_table_status_handler');
add_action('wp_ajax_eipsi_delete_all_data', 'eipsi_delete_all_data_handler');

// Thank-you page handlers
add_action('wp_ajax_nopriv_eipsi_get_completion_config', 'eipsi_get_completion_config_handler');
add_action('wp_ajax_eipsi_get_completion_config', 'eipsi_get_completion_config_handler');
add_action('wp_ajax_nopriv_eipsi_get_site_logo', 'eipsi_get_site_logo_handler');
add_action('wp_ajax_eipsi_get_site_logo', 'eipsi_get_site_logo_handler');

// Save & Continue handlers
add_action('wp_ajax_nopriv_eipsi_save_partial_response', 'eipsi_save_partial_response_handler');
add_action('wp_ajax_eipsi_save_partial_response', 'eipsi_save_partial_response_handler');
add_action('wp_ajax_nopriv_eipsi_load_partial_response', 'eipsi_load_partial_response_handler');
add_action('wp_ajax_eipsi_load_partial_response', 'eipsi_load_partial_response_handler');
add_action('wp_ajax_nopriv_eipsi_discard_partial_response', 'eipsi_discard_partial_response_handler');
add_action('wp_ajax_eipsi_discard_partial_response', 'eipsi_discard_partial_response_handler');

// === Handlers de Aleatorización (Fase 1) ===
add_action('wp_ajax_eipsi_random_assign', 'eipsi_random_assign_handler');
add_action('wp_ajax_nopriv_eipsi_random_assign', 'eipsi_random_assign_handler');
add_action('wp_ajax_eipsi_get_forms_list', 'eipsi_get_forms_list_handler');
add_action('wp_ajax_eipsi_get_demo_templates', 'eipsi_get_demo_templates_handler');

// === Handlers de Aleatorización Fase 2 (Longitudinal + Reminders) ===
add_action('wp_ajax_eipsi_validate_reminder_token', 'eipsi_validate_reminder_token_handler');
add_action('wp_ajax_nopriv_eipsi_validate_reminder_token', 'eipsi_validate_reminder_token_handler');
add_action('wp_ajax_eipsi_send_reminder_manual', 'eipsi_send_reminder_manual_handler');
add_action('wp_ajax_eipsi_unsubscribe_reminders', 'eipsi_unsubscribe_reminders_handler');

// Monitoring dashboard handlers
add_action('wp_ajax_eipsi_get_monitoring_data', 'eipsi_get_monitoring_data_handler');
add_action('wp_ajax_eipsi_get_audit_log', 'eipsi_get_audit_log_handler');
add_action('wp_ajax_eipsi_export_monitoring_report', 'eipsi_export_monitoring_report_handler');

// === Close Randomization Session (persistent_mode=OFF) ===
add_action( 'wp_ajax_nopriv_eipsi_close_randomization_session', 'eipsi_close_randomization_session_handler' );
add_action( 'wp_ajax_eipsi_close_randomization_session', 'eipsi_close_randomization_session_handler' );

/**
 * AJAX Handler: Monitoring data
 */
function eipsi_get_monitoring_data_handler() {
    check_ajax_referer('eipsi_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized'));
    }

    require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/monitoring.php';

    $data = array(
        'email' => EIPSI_Monitoring::get_email_stats(),
        'cron' => EIPSI_Monitoring::get_cron_status(),
        'sessions' => EIPSI_Monitoring::get_session_stats(),
        'database' => EIPSI_Monitoring::get_db_health(),
        'timestamp' => current_time('mysql'),
    );

    wp_send_json_success($data);
}

/**
 * AJAX Handler: Audit log entries
 */
function eipsi_get_audit_log_handler() {
    check_ajax_referer('eipsi_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized'));
    }

    require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/monitoring.php';

    $limit = isset($_POST['limit']) ? absint($_POST['limit']) : 10;
    $entries = EIPSI_Monitoring::get_audit_log_entries($limit);

    wp_send_json_success($entries);
}

/**
 * AJAX Handler: Export monitoring report
 */
function eipsi_export_monitoring_report_handler() {
    check_ajax_referer('eipsi_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized'));
    }

    require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/monitoring.php';

    $data = array(
        'email' => EIPSI_Monitoring::get_email_stats(),
        'cron' => EIPSI_Monitoring::get_cron_status(),
        'sessions' => EIPSI_Monitoring::get_session_stats(),
        'database' => EIPSI_Monitoring::get_db_health(),
        'audit_log' => EIPSI_Monitoring::get_audit_log_entries(50),
    );

    $filename = 'monitoring_report_' . gmdate('Y-m-d_H-i-s') . '.json';
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo wp_json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

/**
 * AJAX Handler: Get list of available forms for randomization dropdown
 *
 * - CPT can vary depending on installation/migrations.
 *   Prefer eipsi_form (Form Library actual), but also support eipsi_form_template
 *   to avoid breaking older sites.
 * - Includes publish + private.
 *
 * Returns: array of {id, name, label, status, postType}
 * Frontend expects: data.data = [{id, name, ...}]
 *
 * @since 1.3.0
 */
function eipsi_get_forms_list_handler() {
    // Verificar nonce (aceptar GET o POST para robustez)
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

    // Determinar CPT disponible.
    // Preferimos eipsi_form (instalaciones nuevas / librería real), y si no existe,
    // caemos a eipsi_form_template (compatibilidad instalaciones viejas).
    $post_type = null;

    if (post_type_exists('eipsi_form')) {
        $post_type = 'eipsi_form';
    } elseif (post_type_exists('eipsi_form_template')) {
        $post_type = 'eipsi_form_template';
    }

    if (!$post_type) {
        wp_send_json_success(array());
        return;
    }

    $args = array(
        'post_type' => $post_type,
        'post_status' => array('publish', 'private'),
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
    );

    $forms = get_posts($args);

    if (empty($forms)) {
        wp_send_json_success(array());
        return;
    }

    $forms_list = array_map(function($form) {
        $title = $form->post_title ? $form->post_title : __('(Sin título)', 'eipsi-forms');

        return array(
            'id' => intval($form->ID),
            // Compat: el editor actualmente usa .name
            'name' => esc_html($title),
            // Compat: si algún frontend usa .label
            'label' => esc_html($title),
            'status' => $form->post_status,
            'postType' => $form->post_type,
        );
    }, $forms);

    wp_send_json_success($forms_list);
}

/**
 * AJAX Handler: Get demo templates list
 *
 * Returns templates from eipsi_get_demo_templates() in demo-templates.php
 * Returns: array of {id, name, description, icon}
 * Frontend expects: data.data = [{id, name, description, icon}, ...]
 *
 * @since 1.3.0
 */
function eipsi_get_demo_templates_handler() {
    // Verificar nonce (aceptar GET o POST para robustez)
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

    // Cargar la función de templates demo
    require_once plugin_dir_path(__FILE__) . 'demo-templates.php';
    
    // Obtener templates disponibles
    $templates = eipsi_get_demo_templates();
    
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
        );
    }, $templates);

    wp_send_json_success($templates_list);
}

/**
 * Handler principal de aleatorización - Fisher-Yates weighted
 * 
 * @since 1.3.0
 */
function eipsi_random_assign_handler() {
    // Validar nonce
    check_ajax_referer('eipsi_random_nonce', 'nonce');
    
    // Sanitizar input
    $main_form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    $participant_id = isset($_POST['participant_id']) ? sanitize_text_field($_POST['participant_id']) : '';
    $is_manual = isset($_POST['is_manual']) && $_POST['is_manual'] === 'true';
    
    // Validar permisos (cualquier editor puede configurar random)
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => __('Sin permisos para realizar esta acción.', 'eipsi-forms')));
    }
    
    // Validar que el formulario principal existe
    if (!$main_form_id || get_post_type($main_form_id) !== 'eipsi_form') {
        wp_send_json_error(array('message' => __('Formulario principal inválido.', 'eipsi-forms')));
    }
    
    // Identificador: priorizar email, si no participant_id
    if (!empty($email) && !is_email($email)) {
        wp_send_json_error(array('message' => __('Email inválido.', 'eipsi-forms')));
    }
    
    if (empty($email) && empty($participant_id)) {
        wp_send_json_error(array('message' => __('Se requiere email o participant_id.', 'eipsi-forms')));
    }
    
    $identifier = !empty($email) ? $email : $participant_id;
    
    // Si es asignación manual directa (desde el panel de admin)
    if ($is_manual && isset($_POST['assigned_form_id'])) {
        $assigned_form_id = intval($_POST['assigned_form_id']);
        
        // Validar que el formulario asignado existe
        if (!$assigned_form_id || get_post_type($assigned_form_id) !== 'eipsi_form') {
            wp_send_json_error(array('message' => __('Formulario asignado inválido.', 'eipsi-forms')));
        }
        
        $seed = wp_generate_uuid4();
        $type = 'manual';
        
        // Guardar asignación
        eipsi_save_assignment($main_form_id, $identifier, $assigned_form_id, $seed, $type);
        
        wp_send_json_success(array(
            'form_id' => $assigned_form_id,
            'seed' => $seed,
            'type' => $type,
        ));
    }
    
    // BUSCAR ASIGNACIÓN PREVIA (persistencia)
    $existing_assignment = eipsi_get_assignment($main_form_id, $identifier);
    
    if ($existing_assignment) {
        // El participante YA tiene asignado un formulario → devolver el mismo
        wp_send_json_success(array(
            'form_id' => intval($existing_assignment['form_id']),
            'seed' => $existing_assignment['seed'],
            'type' => 'persistent',
            'message' => __('Usando asignación anterior', 'eipsi-forms')
        ));
    }
    
    // Leer configuración de aleatorización
    $config = get_post_meta($main_form_id, '_eipsi_random_config', true);
    
    if (empty($config) || !isset($config['forms']) || count($config['forms']) < 2) {
        wp_send_json_error(array('message' => __('Aleatorización no configurada o incompleta (mínimo 2 formularios requeridos).', 'eipsi-forms')));
    }
    
    // Verificar override manual (el identifier coincide con una asignación manual)
    // Si el participante usó email, verificar si hay override manual
    if (!empty($email)) {
        $manual_assigns = $config['manualAssigns'] ?? array();
        foreach ($manual_assigns as $assign) {
            if (strtolower($assign['email']) === strtolower($email)) {
                // Manual override encontrado - retornar esa asignación
                $seed = wp_generate_uuid4();
                $type = 'manual_override';
                
                // Guardar la asignación para futuras visitas
                eipsi_save_assignment($main_form_id, $identifier, intval($assign['formId']), $seed, $type);
                
                wp_send_json_success(array(
                    'form_id' => intval($assign['formId']),
                    'seed' => $seed,
                    'type' => $type,
                ));
            }
        }
    }
    
    // Fisher-Yates weighted shuffle - NUEVA ASIGNACIÓN
    $forms = $config['forms'];
    $probabilities = $config['probabilities'];
    $method = $config['method'] ?? 'seeded';
    
    // Generar seed para reproducibilidad
    $seed = ($method === 'seeded') ? wp_generate_uuid4() : null;
    if ($seed) {
        // Usar crc32 del UUID para seed reproducible en mt_rand
        mt_srand(crc32($seed));
    }
    
    $assigned_form_id = eipsi_weighted_random($forms, $probabilities);
    $type = 'random';
    
    // Guardar asignación en postmeta para futuras visitas
    eipsi_save_assignment($main_form_id, $identifier, $assigned_form_id, $seed, $type);
    
    wp_send_json_success(array(
        'form_id' => intval($assigned_form_id),
        'seed' => $seed,
        'type' => $type,
    ));
}

/**
 * Guarda la asignación de formulario en postmeta temporal
 * 
 * @param int $main_form_id Formulario principal (el que tiene la config de random)
 * @param string $email Email del participante
 * @param int $assigned_form_id Formulario asignado
 * @param string|null $seed Seed para reproducibilidad
 * @param string $type Tipo de asignación: 'random' | 'manual' | 'manual_override'
 * @since 1.3.0
 */
function eipsi_save_assignment($main_form_id, $email, $assigned_form_id, $seed, $type) {
    $meta_key = '_eipsi_assign_' . md5(strtolower($email));
    
    $assignment = array(
        'form_id' => intval($assigned_form_id),
        'seed' => $seed,
        'type' => $type,
        'timestamp' => current_time('mysql'),
        'main_form_id' => intval($main_form_id),
    );
    
    update_post_meta($main_form_id, $meta_key, $assignment);
}

/**
 * Fisher-Yates weighted shuffle para selección según probabilidades
 * Implementa selección ponderada con distribución uniforme
 * 
 * @param array $forms Array de post IDs
 * @param array $probabilities { formId: percentage }
 * @return int Post ID seleccionado
 * @since 1.3.0
 */
function eipsi_weighted_random($forms, $probabilities) {
    // Crear array ponderado para selección
    // Cada formulario aparece N veces según su peso (simplificado)
    $weighted = array();
    $total_weight = 0;
    
    foreach ($forms as $form_id) {
        $weight = isset($probabilities[$form_id]) ? intval($probabilities[$form_id]) : 1;
        // Usar el porcentaje directamente como peso (0-100)
        // Para distribuciones más precisas con pesos pequeños, usaríamos multiplicador
        for ($i = 0; $i < $weight; $i++) {
            $weighted[] = $form_id;
        }
        $total_weight += $weight;
    }
    
    // Si no hay pesos válidos, retornar primero
    if (empty($weighted)) {
        return intval($forms[0]);
    }
    
    // Fisher-Yates shuffle del array ponderado
    $n = count($weighted);
    for ($i = $n - 1; $i > 0; $i--) {
        $j = mt_rand(0, $i);
        $temp = $weighted[$i];
        $weighted[$i] = $weighted[$j];
        $weighted[$j] = $temp;
    }
    
    // Seleccionar primer elemento después del shuffle
    // Esto da distribución proporcional a los pesos originales
    return intval($weighted[0]);
}

/**
 * Obtiene la asignación de un participante
 * 
 * @param int $main_form_id Formulario principal
 * @param string $email Email del participante
 * @return array|null Datos de asignación o null si no existe
 * @since 1.3.0
 */
function eipsi_get_assignment($main_form_id, $email) {
    $meta_key = '_eipsi_assign_' . md5(strtolower($email));
    return get_post_meta($main_form_id, $meta_key, true);
}

/**
 * Valida un token de recordatorio (Fase 2 - Longitudinal)
 * 
 * @since 1.3.0
 */
function eipsi_validate_reminder_token_handler() {
    // Permitir validación sin nonce para links de email (nopriv)
    // Validaremos el token mismo como seguridad
    
    $token = isset($_POST['token']) ? sanitize_text_field($_POST['token']) : '';
    
    if (empty($token)) {
        wp_send_json_error(array('message' => __('Token inválido.', 'eipsi-forms')));
    }
    
    global $wpdb;
    
    // Buscar token en postmeta de todos los formularios
    $meta_table = $wpdb->postmeta;
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT post_id, meta_value 
        FROM {$meta_table} 
        WHERE meta_key LIKE %s 
        AND meta_value LIKE %s 
        LIMIT 10",
        $wpdb->esc_like('_eipsi_reminder_token_') . '%',
        '%' . $wpdb->esc_like($token) . '%'
    ));
    
    if (empty($results)) {
        wp_send_json_error(array('message' => __('Token no encontrado o expirado.', 'eipsi-forms')));
    }
    
    // Validar cada match (puede haber colisiones parciales)
    foreach ($results as $row) {
        $data = maybe_unserialize($row->meta_value);
        
        if (!is_array($data) || !isset($data['token'])) {
            continue;
        }
        
        // Match exacto del token
        if ($data['token'] !== $token) {
            continue;
        }
        
        // Verificar expiración
        if (isset($data['expires'])) {
            $expires = strtotime($data['expires']);
            $now = current_time('timestamp');
            
            if ($now > $expires) {
                wp_send_json_error(array('message' => __('Este link ha expirado. Solicita uno nuevo.', 'eipsi-forms')));
            }
        }
        
        // Token válido - retornar datos
        wp_send_json_success(array(
            'valid' => true,
            'email' => $data['email'] ?? '',
            'form_id' => intval($data['form_id'] ?? 0),
            'take' => intval($data['take'] ?? 1),
            'seed' => $data['seed'] ?? '',
        ));
    }
    
    wp_send_json_error(array('message' => __('Token inválido.', 'eipsi-forms')));
}

/**
 * Envía un recordatorio manual desde el panel de Submissions
 * 
 * @since 1.3.0
 */
function eipsi_send_reminder_manual_handler() {
    check_ajax_referer('eipsi_admin_nonce', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => __('Sin permisos.', 'eipsi-forms')));
    }
    
    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
    $take_num = isset($_POST['take']) ? intval($_POST['take']) : 1;
    $frequency = isset($_POST['frequency']) ? sanitize_text_field($_POST['frequency']) : 'now';
    
    if (empty($email) || !is_email($email) || !$form_id) {
        wp_send_json_error(array('message' => __('Datos inválidos.', 'eipsi-forms')));
    }
    
    // Verificar que existe una toma pendiente para este email
    $take_meta_key = "_eipsi_toma_{$take_num}_assign";
    $take_data = get_post_meta($form_id, $take_meta_key, true);
    
    if (empty($take_data) || !is_array($take_data)) {
        wp_send_json_error(array('message' => __('No se encontró toma pendiente para este participante.', 'eipsi-forms')));
    }
    
    // Generar token
    $token = wp_generate_uuid4();
    $assigned_form_id = $take_data['form_id'] ?? $form_id;
    $seed = $take_data['seed'] ?? '';
    
    // Guardar token
    $token_data = array(
        'token' => $token,
        'email' => $email,
        'form_id' => $assigned_form_id,
        'take' => $take_num,
        'seed' => $seed,
        'created' => current_time('mysql'),
        'expires' => gmdate('Y-m-d H:i:s', strtotime('+48 hours')),
        'manual' => true,
    );
    
    update_post_meta($form_id, '_eipsi_reminder_token_' . md5($email . $take_num), $token_data);
    
    // Construir link de recordatorio
    $reminder_link = add_query_arg(array(
        'eipsi_token' => $token,
        'form_id' => $assigned_form_id,
        'take' => $take_num,
    ), home_url('/formulario/'));
    
    if ($frequency === 'now') {
        // Enviar inmediatamente
        $headers = array('Content-Type: text/html; charset=UTF-8');
        $form_name = get_the_title($form_id);
        $subject = sprintf(__('Recordatorio: Tu Toma %d está lista - %s', 'eipsi-forms'), $take_num, $form_name);
        
        // Construir link de unsubscribe
        $unsubscribe_link = add_query_arg(array(
            'eipsi_unsubscribe' => '1',
            'email' => urlencode($email),
            'form_id' => $form_id,
            'token' => $token,
        ), home_url('/'));
        
        // Cargar template
        ob_start();
        include EIPSI_FORMS_PLUGIN_DIR . 'includes/emails/reminder-take.php';
        $email_body = ob_get_clean();
        
        $sent = wp_mail($email, $subject, $email_body, $headers);
        
        if ($sent) {
            wp_send_json_success(array('message' => __('Recordatorio enviado exitosamente.', 'eipsi-forms')));
        } else {
            wp_send_json_error(array('message' => __('Error al enviar el email.', 'eipsi-forms')));
        }
    } elseif ($frequency === 'tomorrow') {
        // Programar para mañana 10 AM
        $tomorrow = strtotime('tomorrow 10:00 AM');
        wp_schedule_single_event($tomorrow, 'eipsi_send_manual_reminder', array($email, $reminder_link));
        
        wp_send_json_success(array('message' => __('Recordatorio programado para mañana.', 'eipsi-forms')));
    } elseif ($frequency === 'weekly') {
        // Programar para 1 semana desde ahora
        $next_week = strtotime('+1 week');
        wp_schedule_single_event($next_week, 'eipsi_send_manual_reminder', array($email, $reminder_link));
        
        wp_send_json_success(array('message' => __('Recordatorio programado para la próxima semana.', 'eipsi-forms')));
    }
    
    wp_send_json_error(array('message' => __('Frecuencia inválida.', 'eipsi-forms')));
}

/**
 * Desuscribirse de recordatorios (unsubscribe)
 * 
 * @since 1.3.0
 */
function eipsi_unsubscribe_reminders_handler() {
    // No requiere nonce (viene de link de email)
    
    $email = isset($_GET['email']) ? sanitize_email($_GET['email']) : '';
    $form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;
    $token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';
    
    if (empty($email) || !is_email($email) || !$form_id || empty($token)) {
        wp_die(__('Solicitud inválida.', 'eipsi-forms'));
    }
    
    // Validar que el token existe (seguridad básica)
    global $wpdb;
    $meta_table = $wpdb->postmeta;
    $token_exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) 
        FROM {$meta_table} 
        WHERE post_id = %d 
        AND meta_key LIKE %s 
        AND meta_value LIKE %s",
        $form_id,
        $wpdb->esc_like('_eipsi_reminder_token_') . '%',
        '%' . $wpdb->esc_like($token) . '%'
    ));
    
    if (!$token_exists) {
        wp_die(__('Token inválido.', 'eipsi-forms'));
    }
    
    // Guardar flag de unsubscribe
    update_post_meta($form_id, '_eipsi_unsubscribed_' . md5($email), array(
        'timestamp' => current_time('mysql'),
        'reason' => 'user_request',
    ));
    
    // Mostrar mensaje de confirmación
    wp_die(
        sprintf(
            '<div style="max-width: 600px; margin: 50px auto; padding: 30px; text-align: center; font-family: -apple-system, BlinkMacSystemFont, sans-serif;">
                <h2 style="color: #00a32a; margin-bottom: 20px;">✓ %s</h2>
                <p style="font-size: 16px; color: #666; margin-bottom: 30px;">%s</p>
                <p style="font-size: 14px; color: #999;">%s</p>
            </div>',
            esc_html__('Desuscrito exitosamente', 'eipsi-forms'),
            esc_html__('Ya no recibirás más recordatorios de este estudio.', 'eipsi-forms'),
            esc_html__('Si necesitas volver a participar, contacta al equipo de investigación.', 'eipsi-forms')
        ),
        __('Desuscrito', 'eipsi-forms')
    );
}

/**
 * NOTE: Quality Flags y Patrones de Evitación fueron removidos en v1.0
 * RAZÓN CLÍNICA: 
 * - Quality Flags = ruido sin valor (investigador ve todo en Submissions)
 * - Patrones de Evitación = indetectable con Save & Continue, falsos positivos altos
 * RESPONSABILIDAD: Solo investigador decide qué datos usar, no algoritmos
 */

/**
 * Handler para guardar configuración de privacidad
 */
function eipsi_save_privacy_config_handler() {
    check_ajax_referer('eipsi_privacy_nonce', 'eipsi_privacy_nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('No tienes permisos para realizar esta acción.', 'eipsi-forms')));
    }
    
    $form_id = isset($_POST['form_id']) ? sanitize_text_field($_POST['form_id']) : '';
    
    if (empty($form_id)) {
        wp_send_json_error(array('message' => __('Form ID is required.', 'eipsi-forms')));
    }
    
    require_once dirname(__FILE__) . '/privacy-config.php';
    
    $config = array(
        'device_type' => isset($_POST['device_type']),
        'browser' => isset($_POST['browser']),
        'os' => isset($_POST['os']),
        'screen_width' => isset($_POST['screen_width']),
        'ip_address' => isset($_POST['ip_address'])
    );
    
    $result = save_privacy_config($form_id, $config);
    
    if ($result) {
        wp_send_json_success(array('message' => __('✅ Configuración guardada correctamente.', 'eipsi-forms')));
    } else {
        wp_send_json_error(array('message' => __('Error al guardar la configuración.', 'eipsi-forms')));
    }
}

function eipsi_save_global_privacy_config_handler() {
    check_ajax_referer('eipsi_global_privacy_nonce', 'eipsi_global_privacy_nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('No tienes permisos para realizar esta acción.', 'eipsi-forms')));
    }
    
    require_once dirname(__FILE__) . '/privacy-config.php';
    
    $config = array(
        'device_type' => isset($_POST['device_type']),
        'browser' => isset($_POST['browser']),
        'os' => isset($_POST['os']),
        'screen_width' => isset($_POST['screen_width']),
        'ip_address' => isset($_POST['ip_address'])
    );
    
    $result = save_global_privacy_defaults($config);
    
    if ($result) {
        wp_send_json_success(array('message' => __('✅ Configuración global guardada correctamente.', 'eipsi-forms')));
    } else {
        wp_send_json_error(array('message' => __('Error al guardar la configuración global.', 'eipsi-forms')));
    }
}

function eipsi_forms_submit_form_handler() {
    if (!session_id()) {
        session_start();
    }
    check_ajax_referer('eipsi_forms_nonce', 'nonce');
    
    
    // ✅ v1.4.3 - VALIDACIÓN CONTEXTUAL DE CONSENTIMIENTO
    // La validación de consentimiento se hace en el frontend (eipsi-forms.js líneas 88-127)
    // Solo valida si existe el bloque consent-block en el formulario
    // Esto permite usar bloques individuales sin consentimiento obligatorio
    
    global $wpdb;
    
    $form_name = isset($_POST['form_id']) ? sanitize_text_field($_POST['form_id']) : 'default';

    // Ética clínica: si el estudio está cerrado, no aceptamos nuevos envíos
    if (eipsi_get_study_status_for_form_name($form_name) === 'closed') {
        wp_send_json_error(array(
            'message' => __('Este estudio está cerrado y no acepta más respuestas. Contacta al investigador si tienes dudas.', 'eipsi-forms')
        ), 403);
    }
    
    $device = isset($_POST['device']) ? sanitize_text_field($_POST['device']) : '';
    
    // Capturar otros campos del frontend (siempre los recibimos)
    $browser_raw = isset($_POST['browser']) ? sanitize_text_field($_POST['browser']) : '';
    $os_raw = isset($_POST['os']) ? sanitize_text_field($_POST['os']) : '';
    // Screen puede venir como "1920" o "1920x1080"
    $screen_width_raw = isset($_POST['screen_width']) ? sanitize_text_field($_POST['screen_width']) : '';
    
    // Capturar IP del participante con detección de proxy
    $ip_address_raw = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // Si está detrás de proxy/CDN (Cloudflare, Load Balancer, etc.)
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $ip_address_raw = $_SERVER['HTTP_CF_CONNECTING_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip_address_raw = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    }
    
    // Validar IP
    $ip_address_raw = filter_var($ip_address_raw, FILTER_VALIDATE_IP) ?: 'invalid';
    $start_time = isset($_POST['form_start_time']) ? sanitize_text_field($_POST['form_start_time']) : '';
    $end_time = isset($_POST['form_end_time']) ? sanitize_text_field($_POST['form_end_time']) : '';
    
    // ✅ v1.4.0 - Capturar user fingerprint desde POST
    $user_fingerprint = isset($_POST['eipsi_user_fingerprint']) ? sanitize_text_field($_POST['eipsi_user_fingerprint']) : '';
    
    // Obtener IDs universales del frontend
    $frontend_participant_id = isset($_POST['participant_id']) ? sanitize_text_field($_POST['participant_id']) : '';
    $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';
    
    // Capturar metadata del frontend incluyendo page_transitions
    $frontend_metadata = isset($_POST['metadata']) ? wp_unslash($_POST['metadata']) : '';
    $metadata_array = null;
    
    if (!empty($frontend_metadata)) {
        $metadata_decoded = json_decode($frontend_metadata, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $metadata_array = $metadata_decoded;
        }
    }
    
    $form_responses = array();
    $exclude_fields = array('form_id', 'form_action', 'ip_address', 'device', 'browser', 'os', 'screen_width', 'form_start_time', 'form_end_time', 'current_page', 'nonce', 'action', 'participant_id', 'session_id', 'metadata', 'end_timestamp_ms');
    
    $user_data = array(
        'email' => '',
        'name' => ''
    );
    
    foreach ($_POST as $key => $value) {
        if (!in_array($key, $exclude_fields) && is_string($value)) {
            $form_responses[$key] = sanitize_text_field($value);
            
            if (strtolower($key) === 'email' || strpos(strtolower($key), 'correo') !== false) {
                $user_data['email'] = sanitize_email($value);
            }
            if (strtolower($key) === 'name' || strtolower($key) === 'nombre') {
                $user_data['name'] = sanitize_text_field($value);
            }
        }
    }
    
    $start_timestamp_ms = null;
    $end_timestamp_ms = null;
    $duration = 0;
    $duration_seconds = 0.0;
    
    if (!empty($start_time)) {
        $start_timestamp_ms = intval($start_time);

        // === FIJO: Usar end_timestamp_ms del frontend si existe ===
        // Esto evita el error de ~0.6s por delay de red
        $frontend_end_timestamp_ms = isset($_POST['end_timestamp_ms']) ? intval($_POST['end_timestamp_ms']) : null;

        if (!empty($frontend_end_timestamp_ms)) {
            // Usar timestamp del frontend (preciso, sin delay de red)
            $end_timestamp_ms = $frontend_end_timestamp_ms;
            $duration_ms = max(0, $end_timestamp_ms - $start_timestamp_ms);
            $duration = intval($duration_ms / 1000);
            $duration_seconds = round($duration_ms / 1000, 3);
        } elseif (!empty($end_time)) {
            // Fallback: usar form_end_time si no hay end_timestamp_ms separado
            $end_timestamp_ms = intval($end_time);
            $duration_ms = max(0, $end_timestamp_ms - $start_timestamp_ms);
            $duration = intval($duration_ms / 1000);
            $duration_seconds = round($duration_ms / 1000, 3);
        } else {
            // Último fallback: recapturar en backend (legacy)
            $current_timestamp_ms = round(microtime(true) * 1000);
            $end_timestamp_ms = $current_timestamp_ms;
            $duration_ms = max(0, $end_timestamp_ms - $start_timestamp_ms);
            $duration = intval($duration_ms / 1000);
            $duration_seconds = round($duration_ms / 1000, 3);
        }
    }

    $stable_form_id = generate_stable_form_id($form_name);

    // Usar Participant ID universal del frontend si está disponible, sino fallback al viejo sistema
    $participant_id = !empty($frontend_participant_id) ? $frontend_participant_id : generateStableFingerprint($user_data);
    
    // Capture longitudinal context (v1.4.0)
    $survey_id = EIPSI_Auth_Service::get_current_survey();
    $wave_index = null;

    // Try to get wave context from session if available
    if (isset($_SESSION['eipsi_wave_id'])) {
        $wave_id = absint($_SESSION['eipsi_wave_id']);
        $wave_index_val = $wpdb->get_var($wpdb->prepare(
            "SELECT wave_index FROM {$wpdb->prefix}survey_waves WHERE id = %d",
            $wave_id
        ));
        if ($wave_index_val !== null) {
            $wave_index = (int) $wave_index_val;
        }
    }

    $submitted_at = current_time('mysql');
    
    // Obtener configuración de privacidad
    require_once dirname(__FILE__) . '/privacy-config.php';
    $privacy_config = get_privacy_config($stable_form_id);
    
    // Aplicar privacy config a los campos capturados
    $browser = ($privacy_config['browser'] ?? false) ? $browser_raw : null;
    $os = ($privacy_config['os'] ?? false) ? $os_raw : null;
    $screen_width = ($privacy_config['screen_width'] ?? false) ? $screen_width_raw : null;
    $ip_address = ($privacy_config['ip_address'] ?? true) ? $ip_address_raw : null;
    
    // Construir metadatos según configuración de privacidad
    // Primero, si tenemos metadata del frontend, lo usamos como base
    $metadata = array();
    
    // Si tenemos metadata del frontend (incluyendo page_transitions), lo preservamos
    if ($metadata_array && is_array($metadata_array)) {
        // Mantener los datos del frontend (page_transitions, form_start_time, device_type, etc.)
        $metadata = $metadata_array;
    } else {
        // Fallback a la estructura original si no hay metadata del frontend
        $metadata = array();
    }
    
    // Asegurar que siempre tengamos los campos base
    if (!isset($metadata['form_id'])) {
        $metadata['form_id'] = $stable_form_id;
    }
    if (!isset($metadata['participant_id'])) {
        $metadata['participant_id'] = $participant_id;
    }
    if (!isset($metadata['session_id'])) {
        $metadata['session_id'] = $session_id;
    }
    
    // TIMESTAMPS (SIEMPRE)
    $metadata['timestamps'] = array(
        'start' => $start_timestamp_ms,
        'end' => $end_timestamp_ms,
        'duration_seconds' => $duration_seconds
    );
    
    // DEVICE INFO (según privacy config)
    $device_info = array();
    if ($privacy_config['device_type']) {
        $device_info['device_type'] = $device;
    }
    if ($browser !== null) {
        $device_info['browser'] = $browser;
    }
    if ($os !== null) {
        $device_info['os'] = $os;
    }
    if ($screen_width !== null) {
        $device_info['screen_width'] = $screen_width;
    }
    if (!empty($device_info)) {
        $metadata['device_info'] = $device_info;
    }
    
    // NETWORK INFO (según privacy config)
    if ($ip_address !== null) {
        $metadata['network_info'] = array(
            'ip_address' => $ip_address,
            'ip_storage_type' => ($privacy_config['ip_address'] ?? true) ? 'full' : 'anonymized'
        );
    }
    
    // Removed in v1.0: Quality Flags and Avoidance Patterns deprecated
    // Clinical metadata is now strictly objective (Timing and Completion)
    $metadata['quality_metrics'] = array(
        'completion_rate' => 1.0
    );

    // CONSENT INFO
    if (isset($_POST['eipsi_consent_accepted']) && $_POST['eipsi_consent_accepted'] === 'on') {
        $metadata['consent_given'] = true;
        $metadata['consent_timestamp'] = current_time('Y-m-d\TH:i:s\Z');
        $metadata['consent_ip'] = ($privacy_config['ip_address'] ?? true) ? $ip_address_raw : 'anonymized';
        $metadata['consent_user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    }
    
    // RANDOMIZATION INFO - Guardar datos si existen
    $random_assignment = array(
        'form_id' => isset($_POST['assignment_form_id']) ? sanitize_text_field($_POST['assignment_form_id']) : '-',
        'seed' => isset($_POST['assignment_seed']) ? sanitize_text_field($_POST['assignment_seed']) : '-',
        'type' => isset($_POST['assignment_type']) ? sanitize_text_field($_POST['assignment_type']) : '-'
    );
    
    // Solo guardar en metadata si hay datos reales (no placeholder)
    if ($random_assignment['form_id'] !== '-' || $random_assignment['seed'] !== '-' || $random_assignment['type'] !== '-') {
        $metadata['random_assignment'] = $random_assignment;
    }
    
    // Prepare data for insertion
    $data = array(
        'form_id' => $stable_form_id,
        'participant_id' => $participant_id,
        'survey_id' => $survey_id,
        'wave_index' => $wave_index,
        'session_id' => $session_id,
        'user_fingerprint' => $user_fingerprint,  // ✅ v1.4.0 - Guardar fingerprint
        'form_name' => $form_name,
        'created_at' => current_time('mysql'),
        'submitted_at' => $submitted_at,
        'ip_address' => $ip_address,
        'device' => $device,
        'browser' => $browser,
        'os' => $os,
        'screen_width' => $screen_width,
        'duration' => $duration,
        'duration_seconds' => $duration_seconds,
        'start_timestamp_ms' => $start_timestamp_ms,
        'end_timestamp_ms' => $end_timestamp_ms,
        'metadata' => wp_json_encode($metadata),
        'status' => 'submitted',
        'form_responses' => wp_json_encode($form_responses)
    );
    
    // Check if external database is configured
    require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/database.php';
    $db_helper = new EIPSI_External_Database();
    
    $external_db_enabled = $db_helper->is_enabled();
    $used_fallback = false;
    $error_info = null;
    
    if ($external_db_enabled) {
        // Try external database first
        $result = $db_helper->insert_form_submission($data);
        
        if ($result['success']) {
            // External DB insert succeeded
            EIPSI_Partial_Responses::mark_completed($form_name, $participant_id, $session_id);
            wp_send_json_success(array(
                'message' => __('Form submitted successfully!', 'eipsi-forms'),
                'external_db' => true,
                'insert_id' => $result['insert_id']
            ));
        } else {
            // External DB failed, record error and fall back to WordPress DB
            $error_info = array(
                'error' => $result['error'],
                'error_code' => $result['error_code'],
                'mysql_errno' => isset($result['mysql_errno']) ? $result['mysql_errno'] : null
            );
            
            // Record error for admin diagnostics
            $db_helper->record_error($result['error'], $result['error_code']);
            
            // Log the fallback
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('EIPSI Forms: External DB insert failed, falling back to WordPress DB - ' . $result['error']);
            }
            
            $used_fallback = true;
        }
    }
    
    // Use WordPress database (either as default or as fallback)
    if (!$external_db_enabled || $used_fallback) {
        $table_name = $wpdb->prefix . 'vas_form_results';
        
        $wpdb_result = $wpdb->insert(
            $table_name,
            $data,
            array('%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%f', '%d', '%d', '%s', '%s', '%s')
        );
        
        if ($wpdb_result === false) {
            // Check if it's an "Unknown column" error (schema issue)
            $wpdb_error = $wpdb->last_error;
            
            if (strpos($wpdb_error, 'Unknown column') !== false || strpos($wpdb_error, "doesn't exist") !== false) {
                // Emergency schema repair
                error_log('[EIPSI Forms] Detected schema error, triggering auto-repair: ' . $wpdb_error);
                
                EIPSI_Database_Schema_Manager::repair_local_schema();
                
                // Retry insert once after repair
                $wpdb_result = $wpdb->insert(
                    $table_name,
                    $data,
                    array('%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%f', '%d', '%d', '%s', '%s', '%s')
                );
                
                if ($wpdb_result !== false) {
                    // Success after repair!
                    error_log('[EIPSI Forms] Auto-repaired schema and recovered data insertion');
                    $insert_id = $wpdb->insert_id;
                    EIPSI_Partial_Responses::mark_completed($form_name, $participant_id, $session_id);

                    wp_send_json_success(array(
                        'message' => __('Form submitted successfully!', 'eipsi-forms'),
                        'external_db' => false,
                        'schema_repaired' => true,
                        'insert_id' => $insert_id
                    ));
                } else {
                    // Still failed after repair
                    error_log('[EIPSI Forms CRITICAL] Schema repair failed: ' . $wpdb->last_error);
                    wp_send_json_error(array(
                        'message' => __('Database error (recovery attempted)', 'eipsi-forms'),
                        'external_db_error' => $error_info,
                        'wordpress_db_error' => $wpdb->last_error
                    ));
                }
            } else {
                // Other database error (not schema-related)
                error_log('EIPSI Forms: WordPress DB insert failed - ' . $wpdb_error);
                
                wp_send_json_error(array(
                    'message' => __('Failed to submit form. Please try again.', 'eipsi-forms'),
                    'external_db_error' => $error_info,
                    'wordpress_db_error' => $wpdb_error
                ));
            }
            return;
        }
        
        $insert_id = $wpdb->insert_id;
        
        // Mark partial response as completed
        EIPSI_Partial_Responses::mark_completed($form_name, $participant_id, $session_id);
        
        // === Task 2.4B: Marcar assignment como submitted y obtener próxima toma ===
        $next_wave_data = null;
        $has_next_wave = false;
        
        // Si hay contexto longitudinal (wave_id en sesión), actualizar assignment
        if (isset($_SESSION['eipsi_wave_id']) && $survey_id) {
            $wave_id = absint($_SESSION['eipsi_wave_id']);
            
            // Cargar Wave_Service
            require_once EIPSI_FORMS_PLUGIN_DIR . 'includes/services/Wave_Service.php';
            
            // Marcar assignment como submitted
            $marked = Wave_Service::mark_assignment_submitted($participant_id, $survey_id, $wave_id);
            
            if (!$marked && defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf(
                    '[EIPSI] Warning: No se pudo marcar assignment como submitted (participant_id=%d, survey_id=%d, wave_id=%d)',
                    $participant_id,
                    $survey_id,
                    $wave_id
                ));
            }
            
            // Obtener próxima toma pendiente
            $next_wave = Wave_Service::get_next_pending_wave($participant_id, $survey_id);
            
            if ($next_wave) {
                $has_next_wave = true;
                $next_wave_data = array(
                    'wave_index' => $next_wave['wave_index'],
                    'due_at' => $next_wave['due_at'],
                    'wave_name' => $next_wave['wave_name']
                );
            }
        }
        
        // Preparar respuesta de éxito con información de próximas tomas
        $success_response = array(
            'message' => __('¡GRACIAS! Tu respuesta ha sido guardada exitosamente', 'eipsi-forms'),
            'external_db' => false,
            'insert_id' => $insert_id,
            'has_next' => $has_next_wave,
            'next_wave' => $next_wave_data
        );
        
        // Si no hay próxima toma, agregar mensaje de completado
        if (!$has_next_wave && $survey_id) {
            $success_response['completion_message'] = __('Todas las tomas completadas ✅', 'eipsi-forms');
        }
        
        if ($used_fallback) {
            // Fallback succeeded - inform user with warning
            $success_response['fallback_used'] = true;
            $success_response['warning'] = __('Form was saved to local database (external database temporarily unavailable).', 'eipsi-forms');
            $success_response['error_code'] = $error_info['error_code'];
        }
        
        wp_send_json_success($success_response);
    }
}

// =============================================================================
// FUNCIONES AUXILIARES PARA INVESTIGACIÓN EN PSICOTERAPIA - EIPSI
// =============================================================================

function eipsi_get_research_context($device, $duration) {
    if ($device === 'mobile') {
        return '📱 Posible contexto informal';
    } else {
        return '💻 Posible contexto formal';
    }
}

function eipsi_get_time_context($datetime) {
    $hour = date('H', strtotime($datetime));
    
    if ($hour >= 6 && $hour < 12) return '🌅 Mañana';
    if ($hour >= 12 && $hour < 18) return '🌞 Tarde'; 
    if ($hour >= 18 && $hour < 22) return '🌆 Noche';
    return '🌙 Madrugada';
}

function eipsi_get_platform_type($device, $screen_width) {
    if ($device === 'mobile') {
        if ($screen_width < 400) return '📱 Teléfono pequeño';
        if ($screen_width < 768) return '📱 Teléfono estándar';
        return '📱 Teléfono grande/Tablet pequeña';
    } else {
        if ($screen_width < 1200) return '💻 Laptop';
        return '🖥️ Desktop grande';
    }
}

function eipsi_ajax_get_response_details() {
    check_ajax_referer('eipsi_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Unauthorized', 'eipsi-forms'));
    }
    
    global $wpdb;
    
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    if (empty($id)) {
        wp_send_json_error(__('Invalid ID', 'eipsi-forms'));
    }
    
    $table_name = $wpdb->prefix . 'vas_form_results';
    
    // INTENTO 1: Buscar en BD Externa si está habilitada
    require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/database.php';
    $external_db = new EIPSI_External_Database();
    $response = null;

    if ($external_db->is_enabled()) {
        $mysqli = $external_db->get_connection();
        if ($mysqli) {
             // Determinar nombre de tabla (con o sin prefijo)
             // Esto es crítico porque algunos servidores externos no usan el prefijo WP
             $ext_table_name = $table_name;
             $check = $mysqli->query("SHOW TABLES LIKE '{$ext_table_name}'");
             if (!$check || $check->num_rows === 0) {
                $ext_table_name = 'vas_form_results';
             }

             $stmt = $mysqli->prepare("SELECT * FROM `{$ext_table_name}` WHERE id = ?");
             if ($stmt) {
                 $stmt->bind_param("i", $id);
                 $stmt->execute();
                 $result = $stmt->get_result();
                 if ($result && $row = $result->fetch_object()) {
                     $response = $row;
                 }
                 $stmt->close();
             }
             $mysqli->close();
        }
    }
    
    // INTENTO 2: Fallback a BD Local
    if (!$response) {
        $response = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $id
        ));
    }
    
    if (!$response) {
        wp_send_json_error(__('Response not found', 'eipsi-forms'));
    }
    
    $form_responses = json_decode($response->form_responses, true);

    $html = '';

    // =============================================================================
    // ANÁLISIS DE TIEMPOS POR PÁGINA
    // =============================================================================
    $html .= '<div style="margin-bottom: 20px;">';
        $html .= '<button type="button" id="toggle-timing-analysis" class="button" style="background: #135e96; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;">';
        $html .= '⏱️ Mostrar Análisis de Tiempos';
        $html .= '</button>';
        $html .= '</div>';

        $html .= '<div id="timing-analysis-section" style="display: none; margin: 15px 0; padding: 15px; background: #f0f8ff; border-radius: 5px; border-left: 4px solid #135e96;">';

        // Parse metadata para obtener timing data
        $timing_metadata = null;
        if (!empty($response->metadata)) {
            $metadata_obj = json_decode($response->metadata, true);
            if (isset($metadata_obj['page_timings'])) {
                $timing_metadata = $metadata_obj['page_timings'];
            }
        }

        if ($timing_metadata) {
            // PAGE TIMINGS
            $html .= '<h4>⏱️ Tiempos por Página</h4>';

            if (isset($timing_metadata['total_duration'])) {
                $total_seconds = $timing_metadata['total_duration'];
                $minutes = floor($total_seconds / 60);
                $seconds = intval(round($total_seconds % 60));

                if ($minutes > 0) {
                    $html .= '<p><strong>⏰ Tiempo total:</strong> ' . sprintf('%d min %d sec', $minutes, $seconds) . '</p>';
                } else {
                    $html .= '<p><strong>⏰ Tiempo total:</strong> ' . sprintf('%d sec', $seconds) . '</p>';
                }
                }

                // Page breakdown
            $html .= '<div style="margin-top: 10px; max-height: 200px; overflow-y: auto;">';
            $html .= '<table style="width: 100%; border-collapse: collapse; font-size: 12px;">';
            $html .= '<thead><tr style="background: #e9ecef;">';
            $html .= '<th style="padding: 8px; text-align: left; border-bottom: 2px solid #dee2e6;">Página</th>';
            $html .= '<th style="padding: 8px; text-align: right; border-bottom: 2px solid #dee2e6;">Duración</th>';
            $html .= '<th style="padding: 8px; text-align: left; border-bottom: 2px solid #dee2e6;">Timestamp</th>';
            $html .= '</tr></thead>';
            $html .= '<tbody>';

            $page_number = 0;
            foreach ($timing_metadata as $key => $page_data) {
                if ($key === 'total_duration') continue;

                $page_number++;
                $duration = isset($page_data['duration']) ? floatval($page_data['duration']) : 0;
                $timestamp = isset($page_data['timestamp']) ? $page_data['timestamp'] : '';

                // Format timestamp
                $formatted_time = '';
                if (!empty($timestamp)) {
                    try {
                        $dt = new DateTime($timestamp);
                        $formatted_time = $dt->format('H:i:s');
                    } catch (Exception $e) {
                        $formatted_time = '';
                    }
                }

                $html .= '<tr>';
                $html .= '<td style="padding: 12px 8px; border-bottom: 1px solid #eee;"><strong>Página ' . $page_number . '</strong></td>';
                $html .= '<td style="padding: 12px 8px; border-bottom: 1px solid #eee;">' . number_format($duration, 1) . ' s</td>';
                $html .= '<td style="padding: 12px 8px; border-bottom: 1px solid #eee;">' . esc_html($formatted_time) . '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody></table>';
            $html .= '</div>';

            // FIELD TIMINGS (si están disponibles)
            if (isset($metadata_obj['field_timings']) && !empty($metadata_obj['field_timings'])) {
                $html .= '<div style="margin-top: 20px; max-height: 250px; overflow-y: auto;">';
                $html .= '<h4>🎯 Tiempos por Campo</h4>';
                $html .= '<table style="width: 100%; border-collapse: collapse; font-size: 12px;">';
                $html .= '<thead><tr style="background: #e9ecef;">';
                $html .= '<th style="padding: 8px; text-align: left; border-bottom: 2px solid #dee2e6;">Campo</th>';
                $html .= '<th style="padding: 8px; text-align: right; border-bottom: 2px solid #dee2e6;">Tiempo (s)</th>';
                $html .= '<th style="padding: 8px; text-align: center; border-bottom: 2px solid #dee2e6;">Interacciones</th>';
                $html .= '<th style="padding: 8px; text-align: center; border-bottom: 2px solid #dee2e6;">Foco</th>';
                $html .= '</tr></thead>';
                $html .= '<tbody>';

                $field_count = 0;
                foreach ($metadata_obj['field_timings'] as $field_name => $field_data) {
                    $field_count++;
                    $time_focused = isset($field_data['time_focused']) ? floatval($field_data['time_focused']) : 0;
                    $interactions = isset($field_data['interaction_count']) ? intval($field_data['interaction_count']) : 0;
                    $focus_count = isset($field_data['focus_count']) ? intval($field_data['focus_count']) : 0;

                    $row_color = $field_count % 2 === 0 ? '#ffffff' : '#f8f9fa';
                    $html .= '<tr style="background: ' . $row_color . ';">';
                    $html .= '<td style="padding: 8px; border-bottom: 1px solid #e9ecef;"><code>' . esc_html($field_name) . '</code></td>';
                    $html .= '<td style="padding: 8px; border-bottom: 1px solid #e9ecef; text-align: right;">' . number_format($time_focused, 1) . '</td>';
                    $html .= '<td style="padding: 8px; border-bottom: 1px solid #e9ecef; text-align: center;">' . $interactions . '</td>';
                    $html .= '<td style="padding: 8px; border-bottom: 1px solid #e9ecef; text-align: center;">' . $focus_count . '</td>';
                    $html .= '</tr>';
                }

                $html .= '</tbody></table>';
                $html .= '</div>';
            }

            // ACTIVITY METRICS (si están disponibles)
            if (isset($metadata_obj['activity_metrics'])) {
                $activity = $metadata_obj['activity_metrics'];
                $active_time = isset($activity['active_time']) ? floatval($activity['active_time']) : 0;
                $inactive_time = isset($activity['inactive_time']) ? floatval($activity['inactive_time']) : 0;
                $activity_ratio = isset($activity['activity_ratio']) ? floatval($activity['activity_ratio']) : 0;

                $html .= '<div style="margin-top: 20px;">';
                $html .= '<h4>💤 Métricas de Actividad</h4>';

                $minutes_active = floor($active_time / 60);
                $seconds_active = intval(round($active_time % 60));
                $minutes_inactive = floor($inactive_time / 60);
                $seconds_inactive = intval(round($inactive_time % 60));

                $html .= '<p><strong>⏱️ Tiempo activo:</strong> ' . sprintf('%d min %d sec', $minutes_active, $seconds_active) . '</p>';
                $html .= '<p><strong>💤 Tiempo inactivo:</strong> ' . sprintf('%d min %d sec', $minutes_inactive, $seconds_inactive) . '</p>';
                $html .= '<p><strong>📊 Ratio de actividad:</strong> ' . number_format($activity_ratio * 100, 1) . '%</p>';

                // Activity bar visualization
                $html .= '<div style="margin-top: 10px; width: 100%; height: 24px; background: #e9ecef; border-radius: 4px; overflow: hidden;">';
                $html .= '<div style="width: ' . ($activity_ratio * 100) . '%; height: 100%; background: #10b981;"></div>';
                $html .= '</div>';
                $html .= '</div>';
            }
        } else {
            $html .= '<p><em style="color: #666;">No hay datos de tiempos disponibles para esta respuesta.</em></p>';
        }

        $html .= '</div>';

        // =============================================================================
        // METADATOS TÉCNICOS (SIEMPRE VISIBLES)
        // =============================================================================
        $html .= '<div style="margin-bottom: 20px;">';
        $html .= '<h4>📊 Metadatos Técnicos</h4>';

        // SOLO CAMBIA ESTA LÍNEA:
        $timezone = get_option('timezone_string') ?: 'UTC';
        $timezone_offset = get_option('gmt_offset');
        if ($timezone_offset && empty($timezone)) {
            $timezone_display = 'UTC' . ($timezone_offset > 0 ? '+' : '') . $timezone_offset;
        } else {
            $timezone_display = $timezone;
        }
        $html .= '<p><strong>📅 Fecha y hora:</strong> ' . esc_html($response->created_at) . ' <em style="color: #666; font-size: 0.9em;">(' . esc_html($timezone_display) . ')</em></p>';
    
    // Display timestamps if available
    if (!empty($response->start_timestamp_ms) || !empty($response->end_timestamp_ms)) {
        $html .= '<div style="margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 4px;">';
        
        if (!empty($response->start_timestamp_ms)) {
            $start_time_formatted = gmdate('Y-m-d H:i:s.v', intval($response->start_timestamp_ms / 1000));
            $html .= '<p style="margin: 5px 0;"><strong>🕐 Inicio:</strong> ' . esc_html($start_time_formatted) . ' UTC</p>';
        }
        
        if (!empty($response->end_timestamp_ms)) {
            $end_time_formatted = gmdate('Y-m-d H:i:s.v', intval($response->end_timestamp_ms / 1000));
            $html .= '<p style="margin: 5px 0;"><strong>🕑 Fin:</strong> ' . esc_html($end_time_formatted) . ' UTC</p>';
        }
        
        if (!empty($response->start_timestamp_ms) && !empty($response->end_timestamp_ms)) {
            $calculated_duration_ms = intval($response->end_timestamp_ms) - intval($response->start_timestamp_ms);
            $calculated_duration_seconds = round($calculated_duration_ms / 1000, 3);
            $html .= '<p style="margin: 5px 0;"><strong>⏱️ Duración calculada:</strong> ' . number_format($calculated_duration_seconds, 3) . ' segundos</p>';
        }
        
        $html .= '</div>';
    }
    
    // ESTAS LÍNEAS QUEDAN IGUAL:
    $html .= '<p><strong>⏱️ Duración registrada:</strong> ' . (!empty($response->duration_seconds) ? number_format($response->duration_seconds, 3) : intval($response->duration)) . ' segundos</p>';
    $html .= '<p><strong>📍 Dispositivo:</strong> ' . esc_html($response->device) . '</p>';
    $html .= '</div>';
    
    // =============================================================================
    // DETALLES TÉCNICOS DEL DISPOSITIVO (COLAPSABLE)
    // =============================================================================
    $has_device_info = !empty($response->browser) || !empty($response->os) || !empty($response->screen_width) || !empty($response->ip_address);
    
    if ($has_device_info) {
        $html .= '<div style="margin-bottom: 20px;">';
        $html .= '<button type="button" id="toggle-device-info" class="button" style="background: #6c757d; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; margin-bottom: 10px;">';
        $html .= '🖥️ Mostrar Detalles Técnicos del Dispositivo';
        $html .= '</button>';
        
        $html .= '<div id="device-info-section" style="display: none; padding: 15px; background: #f8f9fa; border-radius: 5px; border-left: 4px solid #6c757d;">';
        $html .= '<h4 style="margin-top: 0;">🖥️ Fingerprint Liviano (Dispositivo)</h4>';
        $html .= '<p style="color: #666; font-size: 0.9em; margin-bottom: 10px;">Ayuda a distinguir envíos desde la misma IP (ej. wifi de clínica). Solo se captura si los toggles están ON en Privacy & Metadata.</p>';
        
        if (!empty($response->ip_address)) {
            $html .= '<p><strong>🌐 IP Address:</strong> ' . esc_html($response->ip_address) . '</p>';
        } else {
            $html .= '<p><strong>🌐 IP Address:</strong> <em style="color: #999;">No disponible (toggle OFF)</em></p>';
        }
        
        if (!empty($response->browser)) {
            $html .= '<p><strong>🌍 Navegador:</strong> ' . esc_html($response->browser) . '</p>';
        } else {
            $html .= '<p><strong>🌍 Navegador:</strong> <em style="color: #999;">No disponible (toggle OFF)</em></p>';
        }
        
        if (!empty($response->os)) {
            $html .= '<p><strong>💻 Sistema Operativo:</strong> ' . esc_html($response->os) . '</p>';
        } else {
            $html .= '<p><strong>💻 Sistema Operativo:</strong> <em style="color: #999;">No disponible (toggle OFF)</em></p>';
        }
        
        if (!empty($response->screen_width)) {
            $html .= '<p><strong>📐 Tamaño de Pantalla:</strong> ' . esc_html($response->screen_width) . '</p>';
        } else {
            $html .= '<p><strong>📐 Tamaño de Pantalla:</strong> <em style="color: #999;">No disponible (toggle OFF)</em></p>';
        }
        
        if (!empty($response->session_id)) {
            $html .= '<p><strong>🔑 Session ID:</strong> <code style="background: #e9ecef; padding: 2px 6px; border-radius: 3px; font-family: monospace; font-size: 0.85em;">' . esc_html($response->session_id) . '</code></p>';
        }
        
        $html .= '</div>';
        $html .= '</div>';
    }
    
    // =============================================================================
    // SESSION IDENTIFIERS
    // =============================================================================
    $html .= '<div style="margin-bottom: 20px;">';
    $html .= '<h4>🔑 Session Identifiers</h4>';
    $html .= '<p><strong>Form ID:</strong> ' . (!empty($response->form_id) ? esc_html($response->form_id) : '<em>Not available</em>') . '</p>';
    $html .= '<p><strong>Participant ID:</strong> ' . (!empty($response->participant_id) ? esc_html($response->participant_id) : '<em>Not available</em>') . '</p>';
    $html .= '<p><strong>Form Name:</strong> ' . esc_html($response->form_name) . '</p>';
    $html .= '</div>';
    
    // =============================================================================
    // DATA EXPORT NOTICE
    // =============================================================================
    $html .= '<div style="margin: 20px 0; padding: 15px; background: #f0f6fc; border-left: 4px solid #2271b1; border-radius: 4px;">';
    $html .= '<h4 style="margin-top: 0;">📊 Access Complete Response Data</h4>';
    $html .= '<p style="margin-bottom: 10px;">For privacy and data protection, questionnaire responses are not displayed in the dashboard.</p>';
    $html .= '<p style="margin-bottom: 10px;"><strong>To view complete responses:</strong></p>';
    $html .= '<ol style="margin-left: 20px; line-height: 1.6;">';
    $html .= '<li>Use the <strong>CSV Export</strong> button for statistical analysis software (SPSS, R, etc.)</li>';
    $html .= '<li>Use the <strong>Excel Export</strong> button for spreadsheet analysis</li>';
    $html .= '</ol>';
    $html .= '<p style="margin-bottom: 0; font-size: 0.9em; color: #666;">Number of questions answered: <strong>' . (!empty($form_responses) ? count($form_responses) : 0) . '</strong></p>';
    $html .= '</div>';
    
    wp_send_json_success($html);
}

function eipsi_track_event_handler() {
    // Verify nonce for security
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'eipsi_tracking_nonce')) {
        wp_send_json_error(array(
            'message' => __('Invalid security token.', 'eipsi-forms')
        ), 403);
        return;
    }
    
    // Define allowed event types
    $allowed_events = array('view', 'start', 'page_change', 'submit', 'abandon', 'branch_jump');
    
    // Sanitize and validate POST data
    $event_type = isset($_POST['event_type']) ? sanitize_text_field($_POST['event_type']) : '';
    
    // Validate event type
    if (empty($event_type) || !in_array($event_type, $allowed_events, true)) {
        wp_send_json_error(array(
            'message' => __('Invalid event type.', 'eipsi-forms')
        ), 400);
        return;
    }
    
    // Sanitize other required fields
    $form_id = isset($_POST['form_id']) ? sanitize_text_field($_POST['form_id']) : '';
    $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';
    
    // Validate required fields
    if (empty($session_id)) {
        wp_send_json_error(array(
            'message' => __('Missing required field: session_id.', 'eipsi-forms')
        ), 400);
        return;
    }
    
    // Sanitize optional fields
    $page_number = isset($_POST['page_number']) ? intval($_POST['page_number']) : null;
    $user_agent = isset($_POST['user_agent']) ? sanitize_text_field($_POST['user_agent']) : '';
    
    // Collect metadata for branch_jump events
    $metadata = null;
    if ($event_type === 'branch_jump') {
        $metadata = array();
        if (isset($_POST['from_page'])) {
            $metadata['from_page'] = intval($_POST['from_page']);
        }
        if (isset($_POST['to_page'])) {
            $metadata['to_page'] = intval($_POST['to_page']);
        }
        if (isset($_POST['field_id'])) {
            $metadata['field_id'] = sanitize_text_field($_POST['field_id']);
        }
        if (isset($_POST['matched_value'])) {
            $metadata['matched_value'] = sanitize_text_field($_POST['matched_value']);
        }
        $metadata = !empty($metadata) ? wp_json_encode($metadata) : null;
    }
    
    // Prepare data for database insertion
    global $wpdb;
    
    $insert_data = array(
        'form_id' => $form_id,
        'session_id' => $session_id,
        'event_type' => $event_type,
        'page_number' => $page_number,
        'metadata' => $metadata,
        'user_agent' => $user_agent,
        'created_at' => current_time('mysql')
    );
    
    // Check if external database is configured
    require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/database.php';
    $db_helper = new EIPSI_External_Database();
    $external_db_enabled = $db_helper->is_enabled();
    $used_fallback = false;
    
    if ($external_db_enabled) {
        // Try external database first
        $result = $db_helper->insert_form_event($insert_data);
        
        if ($result['success']) {
            // External DB insert succeeded
            wp_send_json_success(array(
                'message' => __('Event tracked successfully.', 'eipsi-forms'),
                'event_id' => $result['insert_id'],
                'tracked' => true,
                'external_db' => true
            ));
            return;
        } else {
            // External DB failed, fall back to WordPress DB
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('EIPSI Tracking: External DB insert failed, falling back to WordPress DB - ' . $result['error']);
            }
            $used_fallback = true;
        }
    }
    
    // Use WordPress database (either as default or as fallback)
    $table_name = $wpdb->prefix . 'vas_form_events';
    $insert_formats = array('%s', '%s', '%s', '%d', '%s', '%s', '%s');
    
    $wpdb_result = $wpdb->insert($table_name, $insert_data, $insert_formats);
    
    // Check for database errors
    if ($wpdb_result === false) {
        // Log error but don't crash tracking
        error_log('EIPSI Tracking: Failed to insert event - ' . $wpdb->last_error);
        
        // Still return success to keep tracking JS resilient
        wp_send_json_success(array(
            'message' => __('Event logged.', 'eipsi-forms'),
            'event_id' => null,
            'logged' => true
        ));
        return;
    }
    
    // Return success with event ID
    wp_send_json_success(array(
        'message' => __('Event tracked successfully.', 'eipsi-forms'),
        'event_id' => $wpdb->insert_id,
        'tracked' => true,
        'external_db' => false,
        'fallback_used' => $used_fallback
    ));
}

// =============================================================================
// EXTERNAL DATABASE CONFIGURATION HANDLERS
// =============================================================================

function eipsi_test_db_connection_handler() {
    check_ajax_referer('eipsi_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array(
            'message' => __('Unauthorized', 'eipsi-forms')
        ));
    }
    
    require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/database.php';
    $db_helper = new EIPSI_External_Database();
    
    $host = isset($_POST['host']) ? sanitize_text_field($_POST['host']) : '';
    $user = isset($_POST['user']) ? sanitize_text_field($_POST['user']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $db_name = isset($_POST['db_name']) ? sanitize_text_field($_POST['db_name']) : '';
    
    if (empty($host) || empty($user) || empty($db_name)) {
        wp_send_json_error(array(
            'message' => __('Please fill in all required fields.', 'eipsi-forms')
        ));
    }
    
    $result = $db_helper->test_connection($host, $user, $password, $db_name);
    
    if ($result['success']) {
        wp_send_json_success($result);
    } else {
        wp_send_json_error($result);
    }
}

function eipsi_save_db_config_handler() {
    check_ajax_referer('eipsi_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array(
            'message' => __('Unauthorized', 'eipsi-forms')
        ));
    }
    
    require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/database.php';
    $db_helper = new EIPSI_External_Database();
    
    $host = isset($_POST['host']) ? sanitize_text_field($_POST['host']) : '';
    $user = isset($_POST['user']) ? sanitize_text_field($_POST['user']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $db_name = isset($_POST['db_name']) ? sanitize_text_field($_POST['db_name']) : '';
    
    // If password is empty, get existing password
    if (empty($password)) {
        $existing_credentials = $db_helper->get_credentials();
        if ($existing_credentials && isset($existing_credentials['password'])) {
            $password = $existing_credentials['password'];
        }
    }
    
    if (empty($host) || empty($user) || empty($password) || empty($db_name)) {
        wp_send_json_error(array(
            'message' => __('Please fill in all required fields.', 'eipsi-forms')
        ));
    }
    
    // Test connection before saving
    $test_result = $db_helper->test_connection($host, $user, $password, $db_name);
    
    if (!$test_result['success']) {
        wp_send_json_error(array(
            'message' => __('Connection test failed. Please verify your credentials.', 'eipsi-forms') . ' ' . $test_result['message']
        ));
    }
    
    // Save credentials
    $success = $db_helper->save_credentials($host, $user, $password, $db_name);
    
    if ($success) {
        // Trigger schema verification and synchronization
        require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/database-schema-manager.php';
        $schema_result = EIPSI_Database_Schema_Manager::on_credentials_changed();
        
        $status = $db_helper->get_status();
        
        // Include schema verification results in response
        $response_data = array(
            'message' => sprintf(
                __('Configuration saved successfully! Data will now be stored in: %s', 'eipsi-forms'),
                $db_name
            ),
            'status' => $status,
            'schema_verified' => $schema_result['success'],
            'tables_created' => array(
                'results' => $schema_result['results_table']['created'],
                'events' => $schema_result['events_table']['created']
            ),
            'columns_added' => array(
                'results' => count($schema_result['results_table']['columns_added']),
                'events' => count($schema_result['events_table']['columns_added'])
            )
        );
        
        if (!$schema_result['success']) {
            $response_data['schema_warnings'] = $schema_result['errors'];
        }
        
        wp_send_json_success($response_data);
    } else {
        wp_send_json_error(array(
            'message' => __('Failed to save configuration.', 'eipsi-forms')
        ));
    }
}

function eipsi_disable_external_db_handler() {
    check_ajax_referer('eipsi_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array(
            'message' => __('Unauthorized', 'eipsi-forms')
        ));
    }
    
    require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/database.php';
    $db_helper = new EIPSI_External_Database();
    
    $db_helper->disable();
    
    wp_send_json_success(array(
        'message' => __('External database disabled. Form submissions will now be stored in the WordPress database.', 'eipsi-forms')
    ));
}

function eipsi_get_db_status_handler() {
    check_ajax_referer('eipsi_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array(
            'message' => __('Unauthorized', 'eipsi-forms')
        ));
    }
    
    require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/database.php';
    $db_helper = new EIPSI_External_Database();
    
    $status = $db_helper->get_status();
    
    wp_send_json_success($status);
}

function eipsi_check_external_db_handler() {
    require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/database.php';
    $db_helper = new EIPSI_External_Database();
    
    wp_send_json_success(array(
        'enabled' => $db_helper->is_enabled()
    ));
}

function eipsi_verify_schema_handler() {
    check_ajax_referer('eipsi_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array(
            'message' => __('Unauthorized', 'eipsi-forms')
        ));
    }
    
    require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/database.php';
    require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/database-schema-manager.php';
    
    $db_helper = new EIPSI_External_Database();
    
    if (!$db_helper->is_enabled()) {
        wp_send_json_error(array(
            'message' => __('External database is not enabled', 'eipsi-forms')
        ));
    }
    
    $mysqli = $db_helper->get_connection();
    
    if (!$mysqli) {
        wp_send_json_error(array(
            'message' => __('Failed to connect to external database', 'eipsi-forms')
        ));
    }
    
    $result = EIPSI_Database_Schema_Manager::verify_and_sync_schema($mysqli);
    $mysqli->close();
    
    if ($result['success']) {
        wp_send_json_success(array(
            'message' => __('Schema verification completed successfully', 'eipsi-forms'),
            'results' => $result
        ));
    } else {
        wp_send_json_error(array(
            'message' => __('Schema verification failed', 'eipsi-forms'),
            'errors' => $result['errors']
        ));
    }
}

function eipsi_verify_local_schema_handler() {
    check_ajax_referer('eipsi_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array(
            'message' => __('Unauthorized', 'eipsi-forms')
        ));
    }

    require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/database.php';
    require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/database-schema-manager.php';

    $db_helper = new EIPSI_External_Database();
    $before_status = $db_helper->get_local_table_status();

    $repair_log = EIPSI_Database_Schema_Manager::repair_local_schema();
    $after_status = $db_helper->get_local_table_status();

    if (empty($repair_log) || (isset($repair_log['success']) && $repair_log['success'] === false)) {
        wp_send_json_error(array(
            'message' => __('No se pudo verificar el esquema local.', 'eipsi-forms')
        ));
    }

    $tables_created = array();
    $table_keys = array(
        'results_table',
        'events_table',
        'randomization_configs_table',
        'randomization_assignments_table',
    );

    foreach ($table_keys as $key) {
        $before_exists = isset($before_status[$key]['exists']) ? (bool) $before_status[$key]['exists'] : false;
        $after_exists = isset($after_status[$key]['exists']) ? (bool) $after_status[$key]['exists'] : false;
        if (!$before_exists && $after_exists && !empty($after_status[$key]['table_name'])) {
            $tables_created[] = $after_status[$key]['table_name'];
        }
    }

    if (!empty($before_status['longitudinal_tables']) && !empty($after_status['longitudinal_tables'])) {
        foreach ($after_status['longitudinal_tables'] as $key => $table_info) {
            $before_exists = isset($before_status['longitudinal_tables'][$key]['exists']) ? (bool) $before_status['longitudinal_tables'][$key]['exists'] : false;
            $after_exists = isset($table_info['exists']) ? (bool) $table_info['exists'] : false;
            if (!$before_exists && $after_exists && !empty($table_info['table_name'])) {
                $tables_created[] = $table_info['table_name'];
            }
        }
    }

    $columns_added_total = 0;
    foreach ($repair_log as $table_info) {
        if (is_array($table_info) && isset($table_info['columns_added']) && is_array($table_info['columns_added'])) {
            $columns_added_total += count($table_info['columns_added']);
        }
    }

    $last_verified = $after_status['last_verified'] ?? get_option('eipsi_schema_last_verified', '');
    if (!empty($last_verified)) {
        $last_verified = mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $last_verified);
    }

    wp_send_json_success(array(
        'message' => __('Esquema local verificado y reparado correctamente.', 'eipsi-forms'),
        'tables_created' => $tables_created,
        'columns_added' => $columns_added_total,
        'last_verified' => $last_verified,
        'repair_log' => $repair_log
    ));
}

function eipsi_check_local_table_status_handler() {
    check_ajax_referer('eipsi_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array(
            'message' => __('Unauthorized', 'eipsi-forms')
        ));
    }

    require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/database.php';
    $db_helper = new EIPSI_External_Database();

    $result = $db_helper->get_local_table_status();

    if (!empty($result['success'])) {
        wp_send_json_success($result);
    }

    wp_send_json_error($result);
}

function eipsi_check_table_status_handler() {
    check_ajax_referer('eipsi_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array(
            'message' => __('Unauthorized', 'eipsi-forms')
        ));
    }

    require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/database.php';
    $db_helper = new EIPSI_External_Database();

    $result = $db_helper->check_table_status();

    if ($result['success']) {
        wp_send_json_success($result);
    } else {
        wp_send_json_error($result);
    }
}

function eipsi_delete_all_data_handler() {
    check_ajax_referer('eipsi_delete_all_data', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array(
            'message' => __('Unauthorized', 'eipsi-forms')
        ), 403);
    }

    require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/database.php';
    $db_helper = new EIPSI_External_Database();

    $result = $db_helper->delete_all_data();

    if ($result['success']) {
        wp_send_json_success(array(
            'message' => $result['message'],
            'database' => isset($result['database']) ? $result['database'] : 'wordpress'
        ));
    } else {
        wp_send_json_error(array(
            'message' => isset($result['message']) ? $result['message'] : __('Failed to delete clinical data.', 'eipsi-forms'),
            'error_code' => isset($result['error_code']) ? $result['error_code'] : 'UNKNOWN'
        ));
    }
}

/**
 * AJAX Handler: Save completion message configuration
 */
function eipsi_save_completion_message_handler() {
    check_ajax_referer('eipsi_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Unauthorized', 'eipsi-forms')), 403);
    }
    
    $config = array(
        'title'            => isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '',
        'message'          => isset($_POST['message']) ? wp_kses_post($_POST['message']) : '',
        'show_logo'        => isset($_POST['show_logo']),
        'show_home_button' => isset($_POST['show_home_button']),
        'button_text'      => isset($_POST['button_text']) ? sanitize_text_field($_POST['button_text']) : '',
        'button_action'    => isset($_POST['button_action']) ? sanitize_text_field($_POST['button_action']) : 'reload',
        'show_animation'   => isset($_POST['show_animation']),
    );
    
    require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/completion-message-backend.php';
    
    if (EIPSI_Completion_Message::save_config($config)) {
        wp_send_json_success(array(
            'message' => __('Completion message saved successfully', 'eipsi-forms'),
            'config'  => EIPSI_Completion_Message::get_config(),
        ));
    } else {
        wp_send_json_error(array('message' => __('Failed to save configuration', 'eipsi-forms')));
    }
}
add_action('wp_ajax_eipsi_save_completion_message', 'eipsi_save_completion_message_handler');

/**
 * AJAX Handler: Get completion message configuration for frontend
 */
function eipsi_get_completion_config_handler() {
    require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/completion-message-backend.php';
    
    $config = EIPSI_Completion_Message::get_config();
    
    wp_send_json_success(array(
        'config' => $config,
    ));
}

/**
 * Save & Continue: Save partial response
 */
function eipsi_save_partial_response_handler() {
    // No check_ajax_referer for save operations (need to work even during connection issues)
    
    $form_id = isset($_POST['form_id']) ? sanitize_text_field($_POST['form_id']) : '';
    $participant_id = isset($_POST['participant_id']) ? sanitize_text_field($_POST['participant_id']) : '';
    $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';
    $page_index = isset($_POST['page_index']) ? intval($_POST['page_index']) : 1;
    $responses = isset($_POST['responses']) ? $_POST['responses'] : array();
    
    if (empty($form_id) || empty($participant_id) || empty($session_id)) {
        wp_send_json_error(array(
            'message' => __('Missing required parameters', 'eipsi-forms')
        ));
    }
    
    $result = EIPSI_Partial_Responses::save($form_id, $participant_id, $session_id, $page_index, $responses);
    
    if ($result['success']) {
        wp_send_json_success(array(
            'message' => __('Partial response saved', 'eipsi-forms'),
            'action' => $result['action'],
            'id' => $result['id']
        ));
    } else {
        wp_send_json_error(array(
            'message' => __('Failed to save partial response', 'eipsi-forms'),
            'error' => $result['error']
        ));
    }
}

/**
 * Save & Continue: Load partial response
 */
function eipsi_load_partial_response_handler() {
    $form_id = isset($_POST['form_id']) ? sanitize_text_field($_POST['form_id']) : '';
    $participant_id = isset($_POST['participant_id']) ? sanitize_text_field($_POST['participant_id']) : '';
    $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';
    
    if (empty($form_id) || empty($participant_id) || empty($session_id)) {
        wp_send_json_error(array(
            'message' => __('Missing required parameters', 'eipsi-forms')
        ));
    }
    
    $partial = EIPSI_Partial_Responses::load($form_id, $participant_id, $session_id);
    
    if ($partial) {
        wp_send_json_success(array(
            'found' => true,
            'partial' => $partial
        ));
    } else {
        wp_send_json_success(array(
            'found' => false,
            'partial' => null
        ));
    }
}

/**
 * Save & Continue: Discard partial response
 */
function eipsi_discard_partial_response_handler() {
    $form_id = isset($_POST['form_id']) ? sanitize_text_field($_POST['form_id']) : '';
    $participant_id = isset($_POST['participant_id']) ? sanitize_text_field($_POST['participant_id']) : '';
    $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';
    
    if (empty($form_id) || empty($participant_id) || empty($session_id)) {
        wp_send_json_error(array(
            'message' => __('Missing required parameters', 'eipsi-forms')
        ));
    }
    
    $success = EIPSI_Partial_Responses::discard($form_id, $participant_id, $session_id);
    
    if ($success) {
        wp_send_json_success(array(
            'message' => __('Partial response discarded', 'eipsi-forms')
        ));
    } else {
        wp_send_json_error(array(
            'message' => __('Failed to discard partial response', 'eipsi-forms')
        ));
    }
}

/**
 * Submissions: Sync form list with database
 */
function eipsi_sync_submissions_handler() {
    // Security check
    if (!current_user_can('manage_options') || !check_ajax_referer('eipsi_admin_nonce', 'nonce', false)) {
        wp_send_json_error(array(
            'message' => __('Permission denied or invalid security token.', 'eipsi-forms')
        ));
    }
    
    global $wpdb;
    
    // Query para obtener formularios únicos con respuestas
    $table_name = $wpdb->prefix . 'vas_form_results';
    $forms = array();
    
    // Instanciar clase de BD externa
    $external_db = new EIPSI_External_Database();
    
    if (!$external_db->is_enabled()) {
        // Fallback a BD local si BD externa no está habilitada
        $forms = $wpdb->get_col("SELECT DISTINCT form_id FROM $table_name WHERE form_id IS NOT NULL AND form_id != '' ORDER BY form_id");
        
        // Log para debugging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('EIPSI Sync Submissions: Found ' . count($forms) . ' unique forms in local database');
        }
        
        wp_send_json_success(array(
            'forms_found' => count($forms),
            'count' => count($forms),
            'forms' => $forms,
            'message' => __('Submissions synchronized with database.', 'eipsi-forms'),
            'source' => 'local'
        ));
        return;
    }
    
    // Conectarse a BD externa
    $mysqli = $external_db->get_connection();
    if (!$mysqli) {
        // Si conexión externa falla, fallback a BD local
        $forms = $wpdb->get_col("SELECT DISTINCT form_id FROM $table_name WHERE form_id IS NOT NULL AND form_id != '' ORDER BY form_id");
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('EIPSI Sync Submissions: Could not connect to external database, using local fallback. Found ' . count($forms) . ' forms');
        }
        
        wp_send_json_success(array(
            'forms_found' => count($forms),
            'count' => count($forms),
            'forms' => $forms,
            'message' => __('Submissions synchronized with local database (external connection unavailable).', 'eipsi-forms'),
            'source' => 'local_fallback'
        ));
        return;
    }
    
    // Ejecutar query en BD externa
    $result = $mysqli->query("SELECT DISTINCT form_id FROM `{$table_name}` WHERE form_id IS NOT NULL AND form_id != '' ORDER BY form_id");
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $forms[] = $row['form_id'];
        }
    }
    
    $mysqli->close();
    
    // Log para debugging
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('EIPSI Sync Submissions: Found ' . count($forms) . ' unique forms in external database');
    }
    
    // Retornar éxito - el frontend se encarga del refresh
    wp_send_json_success(array(
        'forms_found' => count($forms),
        'count' => count($forms),
        'forms' => $forms,
        'message' => __('Submissions synchronized with database.', 'eipsi-forms'),
        'source' => 'external'
    ));
}

add_action('wp_ajax_eipsi_sync_submissions', 'eipsi_sync_submissions_handler');

/**
 * AJAX Handler: Get site logo URL
 * Returns the logo URL from WordPress customizer
 * Guaranteed to work on any page context (Elementor, custom headers, etc.)
 */
function eipsi_get_site_logo_handler() {
    // Try to get logo from theme customizer
    $logo_url = '';

    // Method 1: WordPress 5.8+ - get_theme_mod('custom_logo')
    if (function_exists('get_theme_mod')) {
        $logo_id = get_theme_mod('custom_logo');
        if ($logo_id) {
            $logo_src = wp_get_attachment_image_src($logo_id, 'full');
            if ($logo_src) {
                $logo_url = $logo_src[0]; // Return URL
            }
        }
    }

    // Fallback: Try site_icon (browser tab icon)
    if (empty($logo_url)) {
        $site_icon_id = get_option('site_icon');
        if ($site_icon_id) {
            $site_icon_src = wp_get_attachment_image_src($site_icon_id, 'full');
            if ($site_icon_src) {
                $logo_url = $site_icon_src[0];
            }
        }
    }

    wp_send_json_success(array(
        'logo_url' => $logo_url
    ));
}

// =============================================================================
// === Handlers de Aleatorización Pública (Fase 3) ===
add_action('wp_ajax_eipsi_get_randomization_config', 'eipsi_get_randomization_config');
add_action('wp_ajax_nopriv_eipsi_get_randomization_config', 'eipsi_get_randomization_config');
add_action('wp_ajax_eipsi_check_manual_assignment', 'eipsi_check_manual_assignment');
add_action('wp_ajax_nopriv_eipsi_check_manual_assignment', 'eipsi_check_manual_assignment');
add_action('wp_ajax_eipsi_persist_assignment', 'eipsi_persist_assignment');
add_action('wp_ajax_nopriv_eipsi_persist_assignment', 'eipsi_persist_assignment');
add_action('wp_ajax_eipsi_get_randomization_pages', 'eipsi_get_randomization_pages_handler');
add_action('wp_ajax_nopriv_eipsi_get_randomization_pages', 'eipsi_get_randomization_pages_handler');

/**
 * AJAX Handler: Obtener configuración de aleatorización
 * 
 * @since 1.3.0
 */
function eipsi_get_randomization_config() {
    // Verificar nonce
    $nonce = '';
    if (isset($_POST['nonce'])) {
        $nonce = sanitize_text_field(wp_unslash($_POST['nonce']));
    } elseif (isset($_GET['nonce'])) {
        $nonce = sanitize_text_field(wp_unslash($_GET['nonce']));
    }

    if (empty($nonce) || !wp_verify_nonce($nonce, 'eipsi_admin_nonce')) {
        wp_send_json_error(array(
            'message' => __('Token de seguridad inválido', 'eipsi-forms')
        ), 403);
        return;
    }

    $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
    if (!$form_id) {
        wp_send_json_error('Form ID requerido');
        return;
    }

    // Verificar que el formulario existe
    $form_post = get_post($form_id);
    if (!$form_post || get_post_type($form_post) !== 'eipsi_form') {
        wp_send_json_error('Formulario no encontrado');
        return;
    }

    // Obtener config de postmeta
    $random_config = get_post_meta($form_id, '_eipsi_random_config', true);
    
    if (!$random_config || !is_array($random_config)) {
        wp_send_json_error('No se encontró configuración de aleatorización');
        return;
    }

    // Preparar respuesta con formularios válidos
    $forms_list = array();
    if (!empty($random_config['forms'])) {
        foreach ($random_config['forms'] as $form_id) {
            $form_post = get_post($form_id);
            if ($form_post && get_post_type($form_post) === 'eipsi_form') {
                $forms_list[] = array(
                    'id' => intval($form_id),
                    'title' => $form_post->post_title ?: 'Formulario sin título'
                );
            }
        }
    }

    wp_send_json_success(array(
        'enabled' => !empty($random_config['enabled']),
        'forms' => $forms_list,
        'method' => $random_config['method'] ?? 'simple',
        'seed_base' => $random_config['seed_base'] ?? null,
        'has_manual_assignments' => !empty($random_config['manualAssigns'])
    ));
}

/**
 * Verificar asignación manual.
 *
 * Este nombre se usa en dos contextos:
 * 1) Modo helper (llamada interna desde PHP): si se pasa $config (attrs del bloque)
 *    y $participant_identifier, devuelve (int|null) el formId asignado manualmente.
 * 2) Modo AJAX (legacy): si se llama sin parámetros (WordPress AJAX action), responde
 *    JSON leyendo la config desde post_meta.
 *
 * @since 1.3.0
 *
 * @param array|null  $config Config de bloque/legacy.
 * @param string|null $participant_identifier Identificador (email o ip_xxx).
 * @return int|null|void
 */
function eipsi_check_manual_assignment($config = null, $participant_identifier = null) {
    // === Helper mode: usado por el shortcode público [eipsi_randomization] ===
    if (is_array($config) && !empty($participant_identifier)) {
        $manual_assignments = array();

        // Nuevo bloque standalone: manualAssignments
        if (isset($config['manualAssignments']) && is_array($config['manualAssignments'])) {
            $manual_assignments = $config['manualAssignments'];
        } elseif (isset($config['manualAssigns']) && is_array($config['manualAssigns'])) {
            // Backwards compat / naming legacy
            $manual_assignments = $config['manualAssigns'];
        }

        if (empty($manual_assignments)) {
            return null;
        }

        // Limpiar identificador (remover "ip_" prefix si existe)
        $clean_identifier = str_replace('ip_', '', strtolower(trim((string) $participant_identifier)));

        foreach ($manual_assignments as $assignment) {
            $assignment_identifier = '';

            // Standalone block: { email, formId, ... }
            if (isset($assignment['email'])) {
                $assignment_identifier = $assignment['email'];
            } elseif (isset($assignment['participant_id'])) {
                $assignment_identifier = $assignment['participant_id'];
            } elseif (isset($assignment['participantId'])) {
                $assignment_identifier = $assignment['participantId'];
            }

            $assignment_identifier = strtolower(trim((string) $assignment_identifier));

            if ($assignment_identifier === $clean_identifier) {
                $form_id = null;

                if (isset($assignment['formId'])) {
                    $form_id = $assignment['formId'];
                } elseif (isset($assignment['assigned_form_id'])) {
                    $form_id = $assignment['assigned_form_id'];
                } elseif (isset($assignment['form_id'])) {
                    $form_id = $assignment['form_id'];
                }

                return $form_id !== null ? intval($form_id) : null;
            }
        }

        return null;
    }

    // === AJAX mode (legacy) ===

    // Verificar nonce
    $nonce = '';
    if (isset($_POST['nonce'])) {
        $nonce = sanitize_text_field(wp_unslash($_POST['nonce']));
    } elseif (isset($_GET['nonce'])) {
        $nonce = sanitize_text_field(wp_unslash($_GET['nonce']));
    }

    if (empty($nonce) || !wp_verify_nonce($nonce, 'eipsi_admin_nonce')) {
        wp_send_json_error(array(
            'message' => __('Token de seguridad inválido', 'eipsi-forms')
        ), 403);
        return;
    }

    $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
    $participant_id = isset($_POST['participant_id']) ? sanitize_text_field($_POST['participant_id']) : '';

    if (!$form_id || empty($participant_id)) {
        wp_send_json_error('Parámetros faltantes');
        return;
    }

    // Obtener asignaciones manuales desde config
    $random_config = get_post_meta($form_id, '_eipsi_random_config', true);
    $manual_assignments = $random_config['manualAssigns'] ?? array();

    // Buscar coincidencia por participant_id (puede ser email o ID)
    foreach ($manual_assignments as $assignment) {
        if (strtolower($assignment['participant_id'] ?? '') === strtolower($participant_id)) {
            wp_send_json_success(array(
                'assigned_form_id' => intval($assignment['formId']),
                'seed' => $assignment['seed'] ?? '',
                'is_manual' => true,
                'timestamp' => $assignment['timestamp'] ?? current_time('mysql')
            ));
            return;
        }
    }

    // Verificar también en metadata de asignaciones persistidas
    $persisted_assignments = get_post_meta($form_id, '_eipsi_assignments', true) ?: array();
    if (isset($persisted_assignments[$participant_id])) {
        $assignment = $persisted_assignments[$participant_id];
        wp_send_json_success(array(
            'assigned_form_id' => intval($assignment['assigned_form_id']),
            'seed' => $assignment['seed'] ?? '',
            'is_manual' => !empty($assignment['is_manual']),
            'timestamp' => $assignment['timestamp'] ?? current_time('mysql')
        ));
        return;
    }

    // No hay override manual
    wp_send_json_success(null);
}

/**
 * AJAX Handler: Persistir asignación en metadata
 * 
 * @since 1.3.0
 */
function eipsi_persist_assignment() {
    // Verificar nonce
    $nonce = '';
    if (isset($_POST['nonce'])) {
        $nonce = sanitize_text_field(wp_unslash($_POST['nonce']));
    } elseif (isset($_GET['nonce'])) {
        $nonce = sanitize_text_field(wp_unslash($_GET['nonce']));
    }

    if (empty($nonce) || !wp_verify_nonce($nonce, 'eipsi_admin_nonce')) {
        wp_send_json_error(array(
            'message' => __('Token de seguridad inválido', 'eipsi-forms')
        ), 403);
        return;
    }

    $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
    $assigned_form_id = isset($_POST['assigned_form_id']) ? intval($_POST['assigned_form_id']) : 0;
    $participant_id = isset($_POST['participant_id']) ? sanitize_text_field($_POST['participant_id']) : '';
    $seed = isset($_POST['seed']) ? sanitize_text_field($_POST['seed']) : '';
    $is_manual = isset($_POST['is_manual']) && $_POST['is_manual'] === '1';

    if (!$form_id || !$assigned_form_id || empty($participant_id)) {
        wp_send_json_error('Parámetros faltantes');
        return;
    }

    // Verificar que los formularios existen
    if (!get_post($form_id) || get_post_type($form_id) !== 'eipsi_form') {
        wp_send_json_error('Formulario principal inválido');
        return;
    }

    if (!get_post($assigned_form_id) || get_post_type($assigned_form_id) !== 'eipsi_form') {
        wp_send_json_error('Formulario asignado inválido');
        return;
    }

    // Obtener asignaciones existentes
    $assignments = get_post_meta($form_id, '_eipsi_assignments', true);
    if (!is_array($assignments)) {
        $assignments = array();
    }

    // Actualizar o crear asignación
    $assignments[$participant_id] = array(
        'assigned_form_id' => $assigned_form_id,
        'seed' => $seed,
        'timestamp' => current_time('mysql'),
        'is_manual' => $is_manual,
        'study_id' => $form_id
    );

    // Guardar en metadata
    $result = update_post_meta($form_id, '_eipsi_assignments', $assignments);

    if ($result !== false) {
        wp_send_json_success(array(
            'message' => 'Asignación persistida correctamente',
            'participant_id' => $participant_id,
            'assigned_form_id' => $assigned_form_id
        ));
    } else {
        wp_send_json_error('Error al persistir asignación');
    }
}

// =============================================================================
// END RANDOMIZATION SYSTEM HANDLERS
// =============================================================================

/**
 * AJAX Handler: Obtener páginas que contienen el shortcode de aleatorización
 * 
 * @since 1.3.0
 */
function eipsi_get_randomization_pages_handler() {
    // Verificar nonce
    $nonce = '';
    if (isset($_POST['nonce'])) {
        $nonce = sanitize_text_field(wp_unslash($_POST['nonce']));
    } elseif (isset($_GET['nonce'])) {
        $nonce = sanitize_text_field(wp_unslash($_GET['nonce']));
    }

    if (empty($nonce) || !wp_verify_nonce($nonce, 'eipsi_admin_nonce')) {
        wp_send_json_error(array(
            'message' => __('Token de seguridad inválido', 'eipsi-forms')
        ), 403);
        return;
    }

    // Buscar páginas que contengan el shortcode [eipsi_randomized_form]
    $pages = get_posts(array(
        'post_type' => 'page',
        'post_status' => array('publish', 'private'),
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => '_wp_page_template',
                'value' => 'default',
                'compare' => '!='
            )
        )
    ));

    $matching_pages = array();

    foreach ($pages as $page) {
        // Verificar si el contenido de la página contiene el shortcode
        if (has_shortcode($page->post_content, 'eipsi_randomized_form')) {
            $matching_pages[] = array(
                'id' => intval($page->ID),
                'title' => $page->post_title ?: 'Página sin título',
                'link' => get_permalink($page->ID)
            );
        }
    }

    // Si no hay páginas con shortcode, incluir páginas públicas generales como fallback
    if (empty($matching_pages)) {
        $public_pages = get_posts(array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'posts_per_page' => 5,
            'orderby' => 'title',
            'order' => 'ASC'
        ));

        foreach ($public_pages as $page) {
            $matching_pages[] = array(
                'id' => intval($page->ID),
                'title' => $page->post_title ?: 'Página sin título',
                'link' => get_permalink($page->ID),
                'note' => 'Nota: Esta página no contiene el shortcode [eipsi_randomized_form]'
            );
        }
    }

    wp_send_json_success($matching_pages);
}

// =================================================================
// PARTICIPANT AUTHENTICATION ENDPOINTS (v1.4.0+)
// =================================================================

/**
 * Get error message based on error code
 * 
 * @param string $error_code
 * @return string
 */
function eipsi_get_error_message($error_code) {
    $messages = array(
        'invalid_email' => __('Email inválido.', 'eipsi-forms'),
        'short_password' => __('La contraseña debe tener al menos 8 caracteres.', 'eipsi-forms'),
        'email_exists' => __('Este email ya está registrado en este estudio.', 'eipsi-forms'),
        'db_error' => __('Error de base de datos. Intenta nuevamente.', 'eipsi-forms'),
        'user_not_found' => __('Usuario no encontrado.', 'eipsi-forms'),
        'user_inactive' => __('Usuario inactivo. Contacta al administrador.', 'eipsi-forms'),
        'invalid_credentials' => __('Email o contraseña incorrectos.', 'eipsi-forms'),
        'missing_fields' => __('Campos requeridos faltantes.', 'eipsi-forms'),
        'rate_limited' => __('Demasiados intentos fallidos. Intenta en 15 minutos.', 'eipsi-forms'),
        'not_authenticated' => __('No estás autenticado.', 'eipsi-forms'),
        'participant_not_found' => __('Participante no encontrado.', 'eipsi-forms')
    );
    
    return isset($messages[$error_code]) ? $messages[$error_code] : __('Error desconocido.', 'eipsi-forms');
}

/**
 * Rate limit check: máximo 5 intentos fallidos en 15 minutos
 * 
 * @param string $email
 * @param int $survey_id
 * @return bool
 */
function eipsi_check_login_rate_limit($email, $survey_id) {
    $key = 'eipsi_login_attempts_' . md5($email . $survey_id);
    $attempts = get_transient($key);
    
    return !($attempts && $attempts >= 5);
}

/**
 * Record failed login attempt
 * 
 * @param string $email
 * @param int $survey_id
 */
function eipsi_record_failed_login($email, $survey_id) {
    $key = 'eipsi_login_attempts_' . md5($email . $survey_id);
    $attempts = (int) get_transient($key);
    set_transient($key, $attempts + 1, 15 * MINUTE_IN_SECONDS);
}

/**
 * Clear login rate limit on successful login
 * 
 * @param string $email
 * @param int $survey_id
 */
function eipsi_clear_login_rate_limit($email, $survey_id) {
    $key = 'eipsi_login_attempts_' . md5($email . $survey_id);
    delete_transient($key);
}

/**
 * AJAX Handler: Participant Registration
 * 
 * Endpoint: eipsi_participant_register
 * Hooks: wp_ajax_nopriv_eipsi_participant_register, wp_ajax_eipsi_participant_register
 */
add_action('wp_ajax_nopriv_eipsi_participant_register', 'eipsi_participant_register_handler');
add_action('wp_ajax_eipsi_participant_register', 'eipsi_participant_register_handler');

function eipsi_participant_register_handler() {
    // Verificar nonce
    check_ajax_referer('eipsi_participant_nonce', 'nonce', true);
    
    // Obtener datos
    $survey_id = isset($_POST['survey_id']) ? absint($_POST['survey_id']) : 0;
    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : ''; // NO sanitizar
    $first_name = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
    $last_name = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';
    
    // Validar
    if (!$survey_id || !$email || !$password) {
        wp_send_json_error([
            'error' => 'missing_fields',
            'message' => eipsi_get_error_message('missing_fields')
        ]);
    }
    
    // Llamar servicio
    $result = EIPSI_Participant_Service::create_participant(
        $survey_id,
        $email,
        $password,
        ['first_name' => $first_name, 'last_name' => $last_name]
    );
    
    if ($result['success']) {
        // Opcionalmente, crear sesión automáticamente (login after register)
        $auth_result = EIPSI_Auth_Service::authenticate($survey_id, $email, $password);
        if ($auth_result['success']) {
            EIPSI_Auth_Service::create_session(
                $auth_result['participant_id'],
                $survey_id,
                defined('EIPSI_SESSION_TTL_HOURS') ? EIPSI_SESSION_TTL_HOURS : 168
            );
        }
        wp_send_json_success([
            'participant_id' => $result['participant_id'],
            'message' => __('Registro exitoso. Bienvenido!', 'eipsi-forms')
        ]);
    } else {
        wp_send_json_error([
            'error' => $result['error'],
            'message' => eipsi_get_error_message($result['error'])
        ]);
    }
}

/**
 * AJAX Handler: Participant Login
 * 
 * Endpoint: eipsi_participant_login
 * Hooks: wp_ajax_nopriv_eipsi_participant_login, wp_ajax_eipsi_participant_login
 */
add_action('wp_ajax_nopriv_eipsi_participant_login', 'eipsi_participant_login_handler');
add_action('wp_ajax_eipsi_participant_login', 'eipsi_participant_login_handler');

function eipsi_participant_login_handler() {
    check_ajax_referer('eipsi_participant_nonce', 'nonce', true);
    
    $survey_id = absint($_POST['survey_id'] ?? 0);
    $email = sanitize_email($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (!$survey_id || !$email || !$password) {
        wp_send_json_error([
            'error' => 'missing_fields',
            'message' => eipsi_get_error_message('missing_fields')
        ]);
    }
    
    // Rate limit check
    if (!eipsi_check_login_rate_limit($email, $survey_id)) {
        wp_send_json_error([
            'error' => 'rate_limited',
            'message' => eipsi_get_error_message('rate_limited')
        ]);
    }
    
    // Autenticar
    $auth_result = EIPSI_Auth_Service::authenticate($survey_id, $email, $password);
    
    if ($auth_result['success']) {
        // Crear sesión
        EIPSI_Auth_Service::create_session(
            $auth_result['participant_id'],
            $survey_id,
            defined('EIPSI_SESSION_TTL_HOURS') ? EIPSI_SESSION_TTL_HOURS : 168
        );
        
        // Limpiar rate limit
        eipsi_clear_login_rate_limit($email, $survey_id);
        
        wp_send_json_success([
            'participant_id' => $auth_result['participant_id'],
            'message' => __('Login exitoso!', 'eipsi-forms'),
            'redirect' => add_query_arg('loggedin', 1, home_url())
        ]);
    } else {
        // Registrar intento fallido para rate limit
        eipsi_record_failed_login($email, $survey_id);
        
        wp_send_json_error([
            'error' => $auth_result['error'],
            'message' => eipsi_get_error_message($auth_result['error'])
        ]);
    }
}

/**
 * AJAX Handler: Participant Logout
 * 
 * Endpoint: eipsi_participant_logout
 * Hooks: wp_ajax_nopriv_eipsi_participant_logout, wp_ajax_eipsi_participant_logout
 */
add_action('wp_ajax_nopriv_eipsi_participant_logout', 'eipsi_participant_logout_handler');
add_action('wp_ajax_eipsi_participant_logout', 'eipsi_participant_logout_handler');

function eipsi_participant_logout_handler() {
    check_ajax_referer('eipsi_participant_nonce', 'nonce', true);
    
    EIPSI_Auth_Service::destroy_session();
    
    wp_send_json_success([
        'message' => __('Logout exitoso.', 'eipsi-forms'),
        'redirect' => home_url()
    ]);
}

/**
 * AJAX Handler: Get Current Participant Info
 * 
 * Endpoint: eipsi_participant_info
 * Hooks: wp_ajax_nopriv_eipsi_participant_info, wp_ajax_eipsi_participant_info
 */
add_action('wp_ajax_nopriv_eipsi_participant_info', 'eipsi_participant_info_handler');
add_action('wp_ajax_eipsi_participant_info', 'eipsi_participant_info_handler');

function eipsi_participant_info_handler() {
    check_ajax_referer('eipsi_participant_nonce', 'nonce', true);
    
    $participant_id = EIPSI_Auth_Service::get_current_participant();
    
    if (!$participant_id) {
        wp_send_json_error([
            'error' => 'not_authenticated',
            'message' => eipsi_get_error_message('not_authenticated')
        ]);
    }
    
    $participant = EIPSI_Participant_Service::get_by_id($participant_id);
    
    if (!$participant) {
        wp_send_json_error([
            'error' => 'participant_not_found',
            'message' => eipsi_get_error_message('participant_not_found')
        ]);
    }
    
    // NUNCA retornar password_hash
    wp_send_json_success([
        'participant_id' => $participant->id,
        'email' => $participant->email,
        'first_name' => $participant->first_name,
        'last_name' => $participant->last_name,
        'survey_id' => $participant->survey_id,
        'created_at' => $participant->created_at,
        'last_login_at' => $participant->last_login_at
    ]);
}
/**
 * AJAX Handler: Close randomization session (persistent_mode=OFF)
 * 
 * Elimina la asignación del usuario y borra la cookie de rotación.
 * Esto permite que en el próximo F5/reload se asigne un nuevo formulario (rotación cíclica).
 * 
 * @since 1.3.20
 */
function eipsi_close_randomization_session_handler() {
    // Validar nonce (aceptar POST)
    $nonce = '';
    if ( isset( $_POST['nonce'] ) ) {
        $nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ) );
    }

    // Validar parámetros requeridos
    $randomization_id = isset( $_POST['randomization_id'] ) ? sanitize_text_field( wp_unslash( $_POST['randomization_id'] ) ) : '';
    $user_fingerprint = isset( $_POST['user_fingerprint'] ) ? sanitize_text_field( wp_unslash( $_POST['user_fingerprint'] ) ) : '';

    if ( empty( $randomization_id ) || empty( $user_fingerprint ) ) {
        wp_send_json_error( array(
            'message' => __( 'Parámetros incompletos (randomization_id o user_fingerprint faltantes)', 'eipsi-forms' )
        ), 400 );
        return;
    }

    // Llamar a la función de cierre de sesión (requiere randomization-shortcode-handler.php)
    if ( ! function_exists( 'eipsi_close_randomization_session' ) ) {
        wp_send_json_error( array(
            'message' => __( 'Función eipsi_close_randomization_session no disponible', 'eipsi-forms' )
        ), 500 );
        return;
    }

    $success = eipsi_close_randomization_session( $randomization_id, $user_fingerprint );

    if ( $success ) {
        wp_send_json_success( array(
            'message' => __( 'Sesión de aleatorización cerrada correctamente', 'eipsi-forms' )
        ) );
    } else {
        wp_send_json_error( array(
            'message' => __( 'Error al cerrar sesión de aleatorización', 'eipsi-forms' )
        ), 500 );
    }
}

/**
 * AJAX Handler: Save Form Authentication Config
 * 
 * Saves the _eipsi_require_login post meta for form templates
 * 
 * @since 1.4.0
 */
add_action('wp_ajax_eipsi_save_form_auth_config', 'eipsi_ajax_save_form_auth_config');

function eipsi_ajax_save_form_auth_config() {
    check_ajax_referer('eipsi_admin_nonce', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    $template_id = absint($_POST['template_id'] ?? 0);
    $require_login = (bool) ($_POST['require_login'] ?? false);
    
    if (!$template_id) {
        wp_send_json_error('Template ID missing');
    }
    
    update_post_meta($template_id, '_eipsi_require_login', $require_login ? 1 : 0);
    
    wp_send_json_success(array(
        'message' => 'Configuration saved',
        'require_login' => $require_login
    ));
}

/**
 * EIPSI Setup Wizard AJAX Handlers
 * 
 * Handles AJAX requests for the setup wizard functionality.
 *
 * @since 1.5.1
 */

// Save wizard step
add_action('wp_ajax_eipsi_save_wizard_step', 'eipsi_ajax_save_wizard_step');

function eipsi_ajax_save_wizard_step() {
    check_ajax_referer('eipsi_wizard_action', 'eipsi_wizard_nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    $step_number = intval($_POST['current_step'] ?? 0);
    $step_data = $_POST;
    
    // Validate step number
    if ($step_number < 1 || $step_number > 5) {
        wp_send_json_error('Invalid step number');
    }
    
    // Load validators
    require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/wizard-validators.php';
    
    // Validate step data
    $validation_result = eipsi_validate_step_data($step_number, $step_data);
    
    if (!$validation_result['valid']) {
        wp_send_json_error($validation_result['errors']);
    }
    
    // Sanitize step data
    $sanitized_data = eipsi_sanitize_step_data($step_number, $step_data);
    
    // Save to transient
    $result = eipsi_save_wizard_step($step_number, $sanitized_data);
    
    if ($result) {
        wp_send_json_success(array(
            'message' => 'Step saved successfully',
            'step' => $step_number
        ));
    } else {
        wp_send_json_error('Failed to save step');
    }
}

// Auto-save wizard step
add_action('wp_ajax_eipsi_auto_save_wizard_step', 'eipsi_ajax_auto_save_wizard_step');

function eipsi_ajax_auto_save_wizard_step() {
    check_ajax_referer('eipsi_wizard_action', 'eipsi_wizard_nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    $step_number = intval($_POST['current_step'] ?? 0);
    $step_data = $_POST;
    
    // Validate step number
    if ($step_number < 1 || $step_number > 5) {
        wp_send_json_error('Invalid step number');
    }
    
    // Load validators
    require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/wizard-validators.php';
    
    // Sanitize step data (less strict validation for auto-save)
    $sanitized_data = eipsi_sanitize_step_data($step_number, $step_data);
    
    // Save to transient
    $result = eipsi_save_wizard_step($step_number, $sanitized_data);
    
    if ($result) {
        wp_send_json_success(array(
            'message' => 'Auto-save completed',
            'step' => $step_number
        ));
    } else {
        wp_send_json_error('Failed to auto-save step');
    }
}

// Activate study
add_action('wp_ajax_eipsi_activate_study', 'eipsi_ajax_activate_study');

function eipsi_ajax_activate_study() {
    check_ajax_referer('eipsi_wizard_action', 'eipsi_wizard_nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    // Load required files
    require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/wizard-validators.php';
    require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/setup-wizard.php';
    
    // Get wizard data
    $wizard_data = eipsi_get_wizard_data();
    
    // Validate all steps are complete
    for ($i = 1; $i <= 4; $i++) {
        if (empty($wizard_data['step_' . $i])) {
            wp_send_json_error('Debes completar todos los pasos antes de activar el estudio.');
        }
    }
    
    // Validate activation confirmation
    if (!isset($_POST['activation_confirmed']) || $_POST['activation_confirmed'] !== '1') {
        wp_send_json_error('Debes confirmar la activación del estudio.');
    }
    
    // Create the study
    $study_id = eipsi_create_study_from_wizard($wizard_data);
    
    if (!$study_id) {
        wp_send_json_error('Error al crear el estudio. Por favor, intenta nuevamente.');
    }
    
    // Clear wizard transient
    $transient_key = eipsi_get_wizard_transient_key();
    delete_transient($transient_key);
    
    // Redirect to study dashboard
    $redirect_url = admin_url('admin.php?page=eipsi-results&study_id=' . $study_id);
    
    wp_send_json_success(array(
        'message' => 'Estudio creado exitosamente.',
        'study_id' => $study_id,
        'redirect_url' => $redirect_url
    ));
}
// DASHBOARD-EIPSI-MARKER

/**
 * Save cron reminders configuration (Task 4.2)
 *
 * @since 1.4.2
 */
add_action('wp_ajax_eipsi_save_cron_reminders_config', 'eipsi_ajax_save_cron_reminders_config');

function eipsi_ajax_save_cron_reminders_config() {
    check_ajax_referer('eipsi_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Permisos insuficientes', 'eipsi-forms'));
    }

    // Get survey ID
    $survey_id = isset($_POST['survey_id']) ? intval($_POST['survey_id']) : 0;

    if (!$survey_id) {
        wp_send_json_error(__('ID de estudio inválido', 'eipsi-forms'));
    }

    // Verify survey exists
    $survey = get_post($survey_id);
    if (!$survey || $survey->post_type !== 'eipsi_form') {
        wp_send_json_error(__('Estudio no encontrado', 'eipsi-forms'));
    }

    // Get and sanitize configuration
    $reminders_enabled = isset($_POST['reminders_enabled']) ? (bool) $_POST['reminders_enabled'] : false;
    $reminder_days_before = isset($_POST['reminder_days_before']) ? intval($_POST['reminder_days_before']) : 3;
    $max_reminder_emails = isset($_POST['max_reminder_emails']) ? intval($_POST['max_reminder_emails']) : 100;
    $dropout_recovery_enabled = isset($_POST['dropout_recovery_enabled']) ? (bool) $_POST['dropout_recovery_enabled'] : false;
    $dropout_recovery_days = isset($_POST['dropout_recovery_days']) ? intval($_POST['dropout_recovery_days']) : 7;
    $max_recovery_emails = isset($_POST['max_recovery_emails']) ? intval($_POST['max_recovery_emails']) : 50;
    $investigator_alert_enabled = isset($_POST['investigator_alert_enabled']) ? (bool) $_POST['investigator_alert_enabled'] : false;
    $investigator_alert_email = isset($_POST['investigator_alert_email']) ? sanitize_email($_POST['investigator_alert_email']) : '';

    // Validate values
    if ($reminder_days_before < 1 || $reminder_days_before > 30) {
        wp_send_json_error(__('Días de recordatorio deben estar entre 1 y 30', 'eipsi-forms'));
    }

    if ($max_reminder_emails < 1 || $max_reminder_emails > 500) {
        wp_send_json_error(__('Máximo de emails debe estar entre 1 y 500', 'eipsi-forms'));
    }

    if ($dropout_recovery_days < 1 || $dropout_recovery_days > 90) {
        wp_send_json_error(__('Días de recovery deben estar entre 1 y 90', 'eipsi-forms'));
    }

    if ($max_recovery_emails < 1 || $max_recovery_emails > 500) {
        wp_send_json_error(__('Máximo de emails recovery debe estar entre 1 y 500', 'eipsi-forms'));
    }

    if ($investigator_alert_enabled && !is_email($investigator_alert_email)) {
        wp_send_json_error(__('Email del investigador inválido', 'eipsi-forms'));
    }

    // Save configuration
    update_post_meta($survey_id, '_eipsi_reminders_enabled', $reminders_enabled);
    update_post_meta($survey_id, '_eipsi_reminder_days_before', $reminder_days_before);
    update_post_meta($survey_id, '_eipsi_max_reminder_emails_per_run', $max_reminder_emails);
    update_post_meta($survey_id, '_eipsi_dropout_recovery_enabled', $dropout_recovery_enabled);
    update_post_meta($survey_id, '_eipsi_dropout_recovery_days_overdue', $dropout_recovery_days);
    update_post_meta($survey_id, '_eipsi_max_recovery_emails_per_run', $max_recovery_emails);
    update_post_meta($survey_id, '_eipsi_investigator_alert_enabled', $investigator_alert_enabled);
    update_post_meta($survey_id, '_eipsi_investigator_alert_email', $investigator_alert_email);

    // Log
    error_log(sprintf(
        '[EIPSI Forms] Cron reminders config saved for survey %d: reminders=%s, recovery=%s',
        $survey_id,
        $reminders_enabled ? 'enabled' : 'disabled',
        $dropout_recovery_enabled ? 'enabled' : 'disabled'
    ));

    wp_send_json_success(__('Configuración guardada correctamente', 'eipsi-forms'));
}

/**
 * AJAX Handler: Anonimizar survey
 * 
 * POST action=eipsi_anonymize_survey
 * Parámetros:
 *   - survey_id: ID del survey
 *   - nonce: wp_nonce_field value
 *   - close_reason: razón de cierre
 *   - close_notes: notas (opcional)
 */
add_action('wp_ajax_eipsi_anonymize_survey', function() {
    // 1. Validar nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'eipsi_anonymize_survey_nonce')) {
        wp_send_json_error(array('message' => 'Nonce inválido'), 403);
    }
    
    // 2. Validar permisos
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'No tienes permisos para anonimizar'), 403);
    }
    
    // 3. Obtener survey_id
    $survey_id = intval($_POST['survey_id'] ?? 0);
    if ($survey_id <= 0) {
        wp_send_json_error(array('message' => 'Survey ID inválido'));
    }
    
    // 4. Verificar que survey existe
    $survey = get_post($survey_id);
    if (!$survey || $survey->post_type !== 'eipsi_form') {
        wp_send_json_error(array('message' => 'Survey no encontrado'));
    }
    
    // 5. Verificar que se puede anonimizar
    if (!class_exists('EIPSI_Anonymize_Service')) {
        wp_send_json_error(array('message' => 'EIPSI_Anonymize_Service no disponible'));
    }
    
    $can_anon = EIPSI_Anonymize_Service::can_anonymize_survey($survey_id);
    if (!$can_anon['can_anonymize']) {
        wp_send_json_error(array('message' => $can_anon['reason']));
    }
    
    // 6. Construir audit_reason
    $close_reason = sanitize_text_field($_POST['close_reason'] ?? '');
    $close_notes = sanitize_textarea_field($_POST['close_notes'] ?? '');
    
    $audit_reason = $close_reason;
    if ($close_notes) {
        $audit_reason .= ' | ' . $close_notes;
    }
    
    // 7. Ejecutar anonimización
    $result = EIPSI_Anonymize_Service::anonymize_survey($survey_id, $audit_reason);
    
    if (!$result['success']) {
        wp_send_json_error(array('message' => $result['error'] ?? 'Error desconocido al anonimizar'));
    }
    
    // 8. Retornar success
    wp_send_json_success(array(
        'message' => 'Estudio anonimizado exitosamente',
        'anonymized_count' => intval($result['anonymized_count']),
        'survey_title' => sanitize_text_field($survey->post_title),
    ));
});


// AJAX: Get export statistics
add_action("wp_ajax_eipsi_get_export_stats", "eipsi_get_export_stats_handler");

function eipsi_get_export_stats_handler() {
    check_ajax_referer("eipsi_admin_nonce", "nonce");
    
    if (!current_user_can("manage_options")) {
        wp_send_json_error(array("message" => "Unauthorized"));
    }
    
    $survey_id = isset($_POST["survey_id"]) ? absint($_POST["survey_id"]) : 0;
    if (!$survey_id) {
        wp_send_json_error(array("message" => "Invalid survey ID"));
    }
    
    require_once EIPSI_FORMS_PLUGIN_DIR . "admin/services/class-export-service.php";
    $export_service = new EIPSI_Export_Service();
    $stats = $export_service->get_export_statistics($survey_id);
    
    wp_send_json_success($stats);
}

// AJAX: Export to Excel
add_action("wp_ajax_eipsi_export_to_excel", "eipsi_export_to_excel_handler");

function eipsi_export_to_excel_handler() {
    check_ajax_referer("eipsi_admin_nonce", "nonce");
    
    if (!current_user_can("manage_options")) {
        wp_send_json_error(array("message" => "Unauthorized"));
    }
    
    $survey_id = isset($_POST["survey_id"]) ? absint($_POST["survey_id"]) : 0;
    $filters = isset($_POST["filters"]) ? array_map("sanitize_text_field", $_POST["filters"]) : array();
    
    if (!$survey_id) {
        wp_send_json_error(array("message" => "Invalid survey ID"));
    }
    
    require_once EIPSI_FORMS_PLUGIN_DIR . "admin/services/class-export-service.php";
    $export_service = new EIPSI_Export_Service();
    $data = $export_service->export_longitudinal_data($survey_id, $filters);
    $filename = $export_service->export_to_excel($data, $survey_id);
    
    wp_send_json_success(array("filename" => $filename));
}

// AJAX: Export to CSV
add_action("wp_ajax_eipsi_export_to_csv", "eipsi_export_to_csv_handler");

function eipsi_export_to_csv_handler() {
    check_ajax_referer("eipsi_admin_nonce", "nonce");
    
    if (!current_user_can("manage_options")) {
        wp_send_json_error(array("message" => "Unauthorized"));
    }
    
    $survey_id = isset($_POST["survey_id"]) ? absint($_POST["survey_id"]) : 0;
    $filters = isset($_POST["filters"]) ? array_map("sanitize_text_field", $_POST["filters"]) : array();
    
    if (!$survey_id) {
        wp_send_json_error(array("message" => "Invalid survey ID"));
    }
    
    require_once EIPSI_FORMS_PLUGIN_DIR . "admin/services/class-export-service.php";
    $export_service = new EIPSI_Export_Service();
    $data = $export_service->export_longitudinal_data($survey_id, $filters);
    $filename = $export_service->export_to_csv($data, $survey_id);
    
    wp_send_json_success(array("filename" => $filename));
}


/**
 * AJAX Handler: Check Local Table Status (when no external DB configured)
 * 
 * @since 1.4.3
 */
add_action('wp_ajax_eipsi_check_local_table_status', 'eipsi_check_local_table_status_handler');

function eipsi_check_local_table_status_handler() {
    check_ajax_referer('eipsi_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array(
            'message' => __('Unauthorized', 'eipsi-forms')
        ));
    }

    require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/database.php';
    $db_helper = new EIPSI_External_Database();

    // This will return local table status when no external DB is configured
    $result = $db_helper->check_table_status();

    if ($result['success']) {
        wp_send_json_success($result);
    } else {
        wp_send_json_error($result);
    }
}
