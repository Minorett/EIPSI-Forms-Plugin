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
add_action('wp_ajax_eipsi_check_table_status', 'eipsi_check_table_status_handler');
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

// === Handlers de Aleatorización Fase 2 (Longitudinal + Reminders) ===
add_action('wp_ajax_eipsi_validate_reminder_token', 'eipsi_validate_reminder_token_handler');
add_action('wp_ajax_nopriv_eipsi_validate_reminder_token', 'eipsi_validate_reminder_token_handler');
add_action('wp_ajax_eipsi_send_reminder_manual', 'eipsi_send_reminder_manual_handler');
add_action('wp_ajax_eipsi_unsubscribe_reminders', 'eipsi_unsubscribe_reminders_handler');

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
    $is_manual = isset($_POST['is_manual']) && $_POST['is_manual'] === 'true';
    
    // Validar permisos (cualquier editor puede configurar random)
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => __('Sin permisos para realizar esta acción.', 'eipsi-forms')));
    }
    
    // Validar que el formulario principal existe
    if (!$main_form_id || get_post_type($main_form_id) !== 'eipsi_form') {
        wp_send_json_error(array('message' => __('Formulario principal inválido.', 'eipsi-forms')));
    }
    
    // Validar email
    if (empty($email) || !is_email($email)) {
        wp_send_json_error(array('message' => __('Email inválido.', 'eipsi-forms')));
    }
    
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
        eipsi_save_assignment($main_form_id, $email, $assigned_form_id, $seed, $type);
        
        wp_send_json_success(array(
            'form_id' => $assigned_form_id,
            'seed' => $seed,
            'type' => $type,
        ));
    }
    
    // Leer configuración de aleatorización
    $config = get_post_meta($main_form_id, '_eipsi_random_config', true);
    
    if (empty($config) || !isset($config['forms']) || count($config['forms']) < 2) {
        wp_send_json_error(array('message' => __('Aleatorización no configurada o incompleta (mínimo 2 formularios requeridos).', 'eipsi-forms')));
    }
    
    // Verificar override manual (el email coincide con una asignación manual)
    $manual_assigns = $config['manualAssigns'] ?? array();
    foreach ($manual_assigns as $assign) {
        if (strtolower($assign['email']) === strtolower($email)) {
            // Manual override encontrado - retornar esa asignación
            $seed = wp_generate_uuid4();
            $type = 'manual_override';
            
            wp_send_json_success(array(
                'form_id' => intval($assign['formId']),
                'seed' => $seed,
                'type' => $type,
            ));
        }
    }
    
    // Fisher-Yates weighted shuffle
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
    
    // Guardar asignación en postmeta temporal
    eipsi_save_assignment($main_form_id, $email, $assigned_form_id, $seed, $type);
    
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
    check_ajax_referer('eipsi_forms_nonce', 'nonce');
    
    // 1️⃣ VALIDACIÓN DE CONSENTIMIENTO OBLIGATORIA - PRIMER CHECK
    if (!isset($_POST['eipsi_consent_accepted']) || $_POST['eipsi_consent_accepted'] !== 'on') {
        wp_send_json_error([
            'message' => 'Debes aceptar los términos de consentimiento',
            'error_code' => 'consent_required'
        ], 403);
        return; // ⛔ NO CONTINUAR BAJO NINGUNA CIRCUNSTANCIA
    }
    
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
            'ip_storage_type' => $privacy_config['ip_storage']
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
        'session_id' => $session_id,
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
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%f', '%d', '%d', '%s', '%s', '%s')
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
                    array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%f', '%d', '%d', '%s', '%s', '%s')
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
        
        if ($used_fallback) {
            // Fallback succeeded - inform user with warning
            wp_send_json_success(array(
                'message' => __('Form submitted successfully!', 'eipsi-forms'),
                'external_db' => false,
                'fallback_used' => true,
                'warning' => __('Form was saved to local database (external database temporarily unavailable).', 'eipsi-forms'),
                'insert_id' => $insert_id,
                'error_code' => $error_info['error_code']
            ));
        } else {
            // Normal WordPress DB submission
            wp_send_json_success(array(
                'message' => __('Form submitted successfully!', 'eipsi-forms'),
                'external_db' => false,
                'insert_id' => $insert_id
            ));
        }
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
// RANDOMIZATION SYSTEM HANDLERS
// =============================================================================

/**
 * AJAX Handler: Get Randomization Configuration
 * Returns the randomization config for a study form
 */
function eipsi_get_randomization_config() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'eipsi_admin_nonce')) {
        wp_send_json_error('Security check failed');
    }

    // Verify form_id
    if (!isset($_POST['form_id'])) {
        wp_send_json_error('Missing form_id parameter');
    }

    $form_id = intval($_POST['form_id']);
    
    // Get randomization config from postmeta
    $random_config = get_post_meta($form_id, '_eipsi_random_config', true);
    
    if (!$random_config || !is_array($random_config)) {
        wp_send_json_error('No randomization configuration found');
    }

    // Verify randomization is enabled
    if (empty($random_config['enabled'])) {
        wp_send_json_error('Randomization is not enabled for this form');
    }

    // Verify there are forms to randomize between
    if (empty($random_config['forms']) || count($random_config['forms']) < 2) {
        wp_send_json_error('Insufficient forms configured for randomization');
    }

    // Return sanitized config
    wp_send_json_success(array(
        'enabled' => true,
        'forms' => array_map(function($form) {
            return array(
                'id' => intval($form['id']),
                'title' => sanitize_text_field($form['title'] ?? '')
            );
        }, $random_config['forms']),
        'method' => sanitize_text_field($random_config['method'] ?? 'simple'),
        'seed_base' => sanitize_text_field($random_config['seed_base'] ?? null)
    ));
}
add_action('wp_ajax_eipsi_get_randomization_config', 'eipsi_get_randomization_config');
add_action('wp_ajax_nopriv_eipsi_get_randomization_config', 'eipsi_get_randomization_config');

/**
 * AJAX Handler: Check Manual Assignment
 * Checks if a participant has a manual assignment override
 */
function eipsi_check_manual_assignment() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'eipsi_admin_nonce')) {
        wp_send_json_error('Security check failed');
    }

    // Verify required parameters
    if (!isset($_POST['form_id'], $_POST['participant_id'])) {
        wp_send_json_error('Missing required parameters');
    }

    $form_id = intval($_POST['form_id']);
    $participant_id = sanitize_text_field($_POST['participant_id']);
    
    // Get manual assignments
    $manual_assignments = get_post_meta($form_id, '_eipsi_manual_assignments', true);
    
    if (is_array($manual_assignments) && isset($manual_assignments[$participant_id])) {
        $assignment = $manual_assignments[$participant_id];
        
        // Verify the assigned form still exists and is valid
        $assigned_form = get_post($assignment['form_id']);
        if ($assigned_form && $assigned_form->post_type === 'eipsi_form_template') {
            wp_send_json_success(intval($assignment['form_id']));
        }
    }
    
    // No manual assignment found
    wp_send_json_success(null);
}
add_action('wp_ajax_eipsi_check_manual_assignment', 'eipsi_check_manual_assignment');
add_action('wp_ajax_nopriv_eipsi_check_manual_assignment', 'eipsi_check_manual_assignment');

/**
 * AJAX Handler: Persist Assignment
 * Saves a randomization assignment to the database
 */
function eipsi_persist_assignment() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'eipsi_admin_nonce')) {
        wp_send_json_error('Security check failed');
    }

    // Verify required parameters
    if (!isset($_POST['form_id'], $_POST['assigned_form_id'], $_POST['participant_id'])) {
        wp_send_json_error('Missing required parameters');
    }

    $form_id = intval($_POST['form_id']);
    $assigned_form_id = intval($_POST['assigned_form_id']);
    $participant_id = sanitize_text_field($_POST['participant_id']);
    $seed = sanitize_text_field($_POST['seed'] ?? '');
    $is_manual = !empty($_POST['is_manual']) && $_POST['is_manual'] === '1';

    // Verify both forms exist
    $study_form = get_post($form_id);
    $assigned_form = get_post($assigned_form_id);
    
    if (!$study_form || !$assigned_form || 
        $study_form->post_type !== 'eipsi_form_template' || 
        $assigned_form->post_type !== 'eipsi_form_template') {
        wp_send_json_error('Invalid form IDs');
    }

    // Get existing assignments
    $assignments = get_post_meta($form_id, '_eipsi_assignments', true);
    if (!is_array($assignments)) {
        $assignments = array();
    }

    // Store assignment
    $assignments[$participant_id] = array(
        'assigned_form_id' => $assigned_form_id,
        'seed' => $seed,
        'timestamp' => current_time('mysql'),
        'is_manual' => $is_manual,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    );

    // Save to database
    $result = update_post_meta($form_id, '_eipsi_assignments', $assignments);

    if ($result === false) {
        wp_send_json_error('Failed to persist assignment');
    }

    wp_send_json_success('Assignment persisted successfully');
}
add_action('wp_ajax_eipsi_persist_assignment', 'eipsi_persist_assignment');
add_action('wp_ajax_nopriv_eipsi_persist_assignment', 'eipsi_persist_assignment');

/**
 * AJAX Handler: Load Form
 * Loads a form template for rendering
 */
function eipsi_load_form() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'eipsi_admin_nonce')) {
        wp_send_json_error('Security check failed');
    }

    // Verify form_id
    if (!isset($_POST['form_id'])) {
        wp_send_json_error('Missing form_id parameter');
    }

    $form_id = intval($_POST['form_id']);
    
    // Get form template
    $template = get_post($form_id);
    if (!$template || $template->post_type !== 'eipsi_form_template') {
        wp_send_json_error('Form template not found');
    }

    // Use existing render function
    if (function_exists('eipsi_render_form_block')) {
        $attributes = array(
            'formId' => $form_id,
            'showTitle' => true
        );
        
        // Capture output
        ob_start();
        echo eipsi_render_form_block($attributes);
        $html = ob_get_clean();
        
        wp_send_json_success($html);
    }

    // Fallback: render basic form
    $blocks = parse_blocks($template->post_content);
    $html = '';
    
    foreach ($blocks as $block) {
        $html .= render_block($block);
    }
    
    wp_send_json_success($html);
}
add_action('wp_ajax_eipsi_load_form', 'eipsi_load_form');
add_action('wp_ajax_nopriv_eipsi_load_form', 'eipsi_load_form');

// =============================================================================
// END RANDOMIZATION SYSTEM HANDLERS
// =============================================================================
?>