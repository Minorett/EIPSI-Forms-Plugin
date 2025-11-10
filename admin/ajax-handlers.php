<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_nopriv_vas_dinamico_submit_form', 'vas_dinamico_submit_form_handler');
add_action('wp_ajax_vas_dinamico_submit_form', 'vas_dinamico_submit_form_handler');

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

function vas_dinamico_submit_form_handler() {
    check_ajax_referer('eipsi_forms_nonce', 'nonce');
    
    global $wpdb;
    
    $form_id = isset($_POST['form_id']) ? sanitize_text_field($_POST['form_id']) : 'default';
    $ip_address = isset($_POST['ip_address']) ? sanitize_text_field($_POST['ip_address']) : '';
    $device = isset($_POST['device']) ? sanitize_text_field($_POST['device']) : '';
    $browser = isset($_POST['browser']) ? sanitize_text_field($_POST['browser']) : '';
    $os = isset($_POST['os']) ? sanitize_text_field($_POST['os']) : '';
    $screen_width = isset($_POST['screen_width']) ? intval($_POST['screen_width']) : 0;
    $start_time = isset($_POST['form_start_time']) ? sanitize_text_field($_POST['form_start_time']) : '';
    
    $form_responses = array();
    $exclude_fields = array('form_id', 'form_action', 'ip_address', 'device', 'browser', 'os', 'screen_width', 'form_start_time', 'current_page', 'nonce');
    
    foreach ($_POST as $key => $value) {
        if (!in_array($key, $exclude_fields) && is_string($value)) {
            $form_responses[$key] = sanitize_text_field($value);
        }
    }
    
    $duration = 0;
    if (!empty($start_time)) {
        $start_timestamp = intval($start_time);
        $current_timestamp = current_time('timestamp', true) * 1000;
        $duration = max(0, intval(($current_timestamp - $start_timestamp) / 1000));
    }
    
    // Prepare data for insertion
    $data = array(
        'form_name' => $form_id,
        'created_at' => current_time('mysql'),
        'ip_address' => $ip_address,
        'device' => $device,
        'browser' => $browser,
        'os' => $os,
        'screen_width' => $screen_width,
        'duration' => $duration,
        'form_responses' => wp_json_encode($form_responses)
    );
    
    // Check if external database is configured
    require_once VAS_DINAMICO_PLUGIN_DIR . 'admin/database.php';
    $db_helper = new EIPSI_External_Database();
    
    if ($db_helper->is_enabled()) {
        // Use external database
        $result = $db_helper->insert_form_submission($data);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Form submitted successfully!', 'vas-dinamico-forms'),
                'external_db' => true
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to submit form to external database.', 'vas-dinamico-forms')
            ));
        }
    } else {
        // Use WordPress database (default behavior)
        $table_name = $wpdb->prefix . 'vas_form_results';
        
        $wpdb->insert(
            $table_name,
            $data,
            array('%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s')
        );
        
        wp_send_json_success(array(
            'message' => __('Form submitted successfully!', 'vas-dinamico-forms'),
            'external_db' => false
        ));
    }
}

// =============================================================================
// FUNCIONES AUXILIARES PARA INVESTIGACIÓN EN PSICOTERAPIA - EIPSI
// =============================================================================

function vas_get_research_context($device, $duration) {
    if ($device === 'mobile') {
        return '📱 Posible contexto informal';
    } else {
        return '💻 Posible contexto formal';
    }
}

function vas_get_time_context($datetime) {
    $hour = date('H', strtotime($datetime));
    
    if ($hour >= 6 && $hour < 12) return '🌅 Mañana';
    if ($hour >= 12 && $hour < 18) return '🌞 Tarde'; 
    if ($hour >= 18 && $hour < 22) return '🌆 Noche';
    return '🌙 Madrugada';
}

function vas_get_platform_type($device, $screen_width) {
    if ($device === 'mobile') {
        if ($screen_width < 400) return '📱 Teléfono pequeño';
        if ($screen_width < 768) return '📱 Teléfono estándar';
        return '📱 Teléfono grande/Tablet pequeña';
    } else {
        if ($screen_width < 1200) return '💻 Laptop';
        return '🖥️ Desktop grande';
    }
}

function vas_get_data_quality($duration, $responses) {
    if (empty($responses)) return '❌ Sin datos';
    
    $empty_fields = count(array_filter($responses, function($value) {
        return empty($value) || $value === '' || $value === '0';
    }));
    
    $total_fields = count($responses);
    $completion_rate = (($total_fields - $empty_fields) / $total_fields) * 100;
    
    if ($completion_rate < 50) return '❌ Baja calidad';
    if ($completion_rate < 80) return '⚠️  Calidad media';
    if ($duration < 5) return '⚠️  Respuestas muy rápidas';
    return '✅ Buena calidad';
}

function vas_get_response_speed($duration, $form_name) {
    // Basado en tipo de formulario psicológico
    if (strpos($form_name, 'emocional') !== false || strpos($form_name, 'ansiedad') !== false) {
        if ($duration < 30) return '⚡ Muy rápido (posible falta de reflexión)';
        if ($duration > 300) return '🐢 Muy lento (posible dificultad emocional)';
        return '✅ Tiempo adecuado';
    }
    
    // Para formularios generales
    if ($duration < 10) return '⚡ Respuesta rápida';
    if ($duration > 120) return '🐢 Respuesta muy reflexiva';
    return '✅ Tiempo normal';
}

function eipsi_ajax_get_response_details() {
    check_ajax_referer('eipsi_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Unauthorized', 'vas-dinamico-forms'));
    }
    
    global $wpdb;
    
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    if (empty($id)) {
        wp_send_json_error(__('Invalid ID', 'vas-dinamico-forms'));
    }
    
    $table_name = $wpdb->prefix . 'vas_form_results';
    
    $response = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d",
        $id
    ));
    
    if (!$response) {
        wp_send_json_error(__('Response not found', 'vas-dinamico-forms'));
    }
    
    $form_responses = json_decode($response->form_responses, true);
    
    $html = '';
    
    // =============================================================================
    // BOTÓN TOGGLE PARA CONTEXTO DE INVESTIGACIÓN
    // =============================================================================
    $html .= '<div style="margin-bottom: 15px;">';
    $html .= '<button type="button" id="toggle-research-context" class="button" style="background: #2271b1; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;">';
    $html .= '🧠 Mostrar Contexto de Investigación';
    $html .= '</button>';
    $html .= '</div>';
    
    // =============================================================================
    // CONTEXTO DE INVESTIGACIÓN (OCULTO INICIALMENTE)
    // =============================================================================
    $html .= '<div id="research-context-section" style="display: none; margin: 15px 0; padding: 15px; background: #f0f8ff; border-radius: 5px; border-left: 4px solid #2271b1;">';
    $html .= '<h4>🧠 Contexto de Investigación</h4>';
    
    $html .= '<p><strong>🏥 Contexto administración:</strong> ' . vas_get_research_context($response->device, $response->duration) . '</p>';
    $html .= '<p><strong>⏰ Momento del día:</strong> ' . vas_get_time_context($response->created_at) . '</p>';
    $html .= '<p><strong>📱 Plataforma:</strong> ' . vas_get_platform_type($response->device, $response->screen_width) . '</p>';
    $html .= '<p><strong>📈 Calidad de datos:</strong> ' . vas_get_data_quality($response->duration, $form_responses) . '</p>';
    $html .= '<p><strong>⚡ Velocidad respuesta:</strong> ' . vas_get_response_speed($response->duration, $response->form_name) . '</p>';
    
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
    
    // ESTAS LÍNEAS QUEDAN IGUAL:
    $html .= '<p><strong>⏱️ Duración total:</strong> ' . intval($response->duration) . ' segundos</p>';
    $html .= '<p><strong>📍 Dispositivo:</strong> ' . esc_html($response->device) . ' (' . esc_html($response->browser) . ' on ' . esc_html($response->os) . ')</p>';
    $html .= '<p><strong>🖥️ Ancho pantalla:</strong> ' . ($response->screen_width ? esc_html($response->screen_width) . 'px' : 'N/A') . '</p>';
    $html .= '<p><strong>🌐 IP:</strong> ' . esc_html($response->ip_address) . '</p>';
    $html .= '</div>';
    
    // =============================================================================
    // DATOS DEL FORMULARIO
    // =============================================================================
    $html .= '<div>';
    $html .= '<h4>📋 Datos del Formulario</h4>';
    if (!empty($form_responses)) {
        $html .= '<div style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px;">';
        $html .= '<table style="width: 100%; border-collapse: collapse;">';
        foreach ($form_responses as $key => $value) {
            $html .= '<tr style="border-bottom: 1px solid #eee;">';
            $html .= '<td style="padding: 12px; font-weight: bold; background: #f9f9f9; width: 30%;">' . esc_html($key) . '</td>';
            $html .= '<td style="padding: 12px; background: white;">' . esc_html(is_array($value) ? json_encode($value) : $value) . '</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';
        $html .= '</div>';
    } else {
        $html .= '<p>' . esc_html__('No form data available.', 'vas-dinamico-forms') . '</p>';
    }
    $html .= '</div>';
    
    wp_send_json_success($html);
}

function eipsi_track_event_handler() {
    // Verify nonce for security
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'eipsi_tracking_nonce')) {
        wp_send_json_error(array(
            'message' => __('Invalid security token.', 'vas-dinamico-forms')
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
            'message' => __('Invalid event type.', 'vas-dinamico-forms')
        ), 400);
        return;
    }
    
    // Sanitize other required fields
    $form_id = isset($_POST['form_id']) ? sanitize_text_field($_POST['form_id']) : '';
    $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';
    
    // Validate required fields
    if (empty($session_id)) {
        wp_send_json_error(array(
            'message' => __('Missing required field: session_id.', 'vas-dinamico-forms')
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
    $table_name = $wpdb->prefix . 'vas_form_events';
    
    $insert_data = array(
        'form_id' => $form_id,
        'session_id' => $session_id,
        'event_type' => $event_type,
        'page_number' => $page_number,
        'metadata' => $metadata,
        'user_agent' => $user_agent,
        'created_at' => current_time('mysql')
    );
    
    $insert_formats = array('%s', '%s', '%s', '%d', '%s', '%s', '%s');
    
    // Insert event into database
    $result = $wpdb->insert($table_name, $insert_data, $insert_formats);
    
    // Check for database errors
    if ($result === false) {
        // Log error but don't crash tracking
        error_log('EIPSI Tracking: Failed to insert event - ' . $wpdb->last_error);
        
        // Still return success to keep tracking JS resilient
        wp_send_json_success(array(
            'message' => __('Event logged.', 'vas-dinamico-forms'),
            'event_id' => null,
            'logged' => true
        ));
        return;
    }
    
    // Return success with event ID
    wp_send_json_success(array(
        'message' => __('Event tracked successfully.', 'vas-dinamico-forms'),
        'event_id' => $wpdb->insert_id,
        'tracked' => true
    ));
}

// =============================================================================
// EXTERNAL DATABASE CONFIGURATION HANDLERS
// =============================================================================

function eipsi_test_db_connection_handler() {
    check_ajax_referer('eipsi_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array(
            'message' => __('Unauthorized', 'vas-dinamico-forms')
        ));
    }
    
    require_once VAS_DINAMICO_PLUGIN_DIR . 'admin/database.php';
    $db_helper = new EIPSI_External_Database();
    
    $host = isset($_POST['host']) ? sanitize_text_field($_POST['host']) : '';
    $user = isset($_POST['user']) ? sanitize_text_field($_POST['user']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $db_name = isset($_POST['db_name']) ? sanitize_text_field($_POST['db_name']) : '';
    
    if (empty($host) || empty($user) || empty($db_name)) {
        wp_send_json_error(array(
            'message' => __('Please fill in all required fields.', 'vas-dinamico-forms')
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
            'message' => __('Unauthorized', 'vas-dinamico-forms')
        ));
    }
    
    require_once VAS_DINAMICO_PLUGIN_DIR . 'admin/database.php';
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
            'message' => __('Please fill in all required fields.', 'vas-dinamico-forms')
        ));
    }
    
    // Test connection before saving
    $test_result = $db_helper->test_connection($host, $user, $password, $db_name);
    
    if (!$test_result['success']) {
        wp_send_json_error(array(
            'message' => __('Connection test failed. Please verify your credentials.', 'vas-dinamico-forms') . ' ' . $test_result['message']
        ));
    }
    
    // Save credentials
    $success = $db_helper->save_credentials($host, $user, $password, $db_name);
    
    if ($success) {
        $status = $db_helper->get_status();
        wp_send_json_success(array(
            'message' => sprintf(
                __('Configuration saved successfully! Data will now be stored in: %s', 'vas-dinamico-forms'),
                $db_name
            ),
            'status' => $status
        ));
    } else {
        wp_send_json_error(array(
            'message' => __('Failed to save configuration.', 'vas-dinamico-forms')
        ));
    }
}

function eipsi_disable_external_db_handler() {
    check_ajax_referer('eipsi_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array(
            'message' => __('Unauthorized', 'vas-dinamico-forms')
        ));
    }
    
    require_once VAS_DINAMICO_PLUGIN_DIR . 'admin/database.php';
    $db_helper = new EIPSI_External_Database();
    
    $db_helper->disable();
    
    wp_send_json_success(array(
        'message' => __('External database disabled. Form submissions will now be stored in the WordPress database.', 'vas-dinamico-forms')
    ));
}

function eipsi_get_db_status_handler() {
    check_ajax_referer('eipsi_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array(
            'message' => __('Unauthorized', 'vas-dinamico-forms')
        ));
    }
    
    require_once VAS_DINAMICO_PLUGIN_DIR . 'admin/database.php';
    $db_helper = new EIPSI_External_Database();
    
    $status = $db_helper->get_status();
    
    wp_send_json_success($status);
}

function eipsi_check_external_db_handler() {
    require_once VAS_DINAMICO_PLUGIN_DIR . 'admin/database.php';
    $db_helper = new EIPSI_External_Database();
    
    wp_send_json_success(array(
        'enabled' => $db_helper->is_enabled()
    ));
}
?>