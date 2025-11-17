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

add_action('wp_ajax_eipsi_save_privacy_config', 'eipsi_save_privacy_config_handler');

/**
 * Calcula engagement score basado en tiempo y cambios
 */
function eipsi_calculate_engagement_score($responses, $duration_seconds) {
    if (!$responses || $duration_seconds == 0) {
        return 0;
    }
    
    $field_count = count($responses);
    $avg_time_per_field = $duration_seconds / max($field_count, 1);
    
    // Score entre 0 y 1
    // Más tiempo = más engagement (min 5s por campo, max 60s)
    $score = min(max($avg_time_per_field / 60, 0), 1);
    
    return round($score, 2);
}

/**
 * Calcula consistency score (coherencia de respuestas)
 */
function eipsi_calculate_consistency_score($responses) {
    // TODO: Implementar lógica de coherencia
    // Por ahora retorna 1.0 (perfecta coherencia)
    return 1.0;
}

/**
 * Detecta patrones de evitación (saltos, retrocesos)
 */
function eipsi_detect_avoidance_patterns($responses) {
    // TODO: Implementar detección
    // Por ahora retorna array vacío
    return array();
}

/**
 * Calcula quality flag basado en múltiples factores
 */
function eipsi_calculate_quality_flag($responses, $duration_seconds) {
    $engagement = eipsi_calculate_engagement_score($responses, $duration_seconds);
    $consistency = eipsi_calculate_consistency_score($responses);
    
    $avg_score = ($engagement + $consistency) / 2;
    
    if ($avg_score >= 0.8) {
        return 'HIGH';
    } elseif ($avg_score >= 0.5) {
        return 'NORMAL';
    } else {
        return 'LOW';
    }
}

/**
 * Handler para guardar configuración de privacidad
 */
function eipsi_save_privacy_config_handler() {
    check_ajax_referer('eipsi_privacy_nonce', 'eipsi_privacy_nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('No tienes permisos para realizar esta acción.', 'vas-dinamico-forms')));
    }
    
    $form_id = isset($_POST['form_id']) ? sanitize_text_field($_POST['form_id']) : '';
    
    if (empty($form_id)) {
        wp_send_json_error(array('message' => __('Form ID is required.', 'vas-dinamico-forms')));
    }
    
    require_once dirname(__FILE__) . '/privacy-config.php';
    
    $config = array(
        'therapeutic_engagement' => isset($_POST['therapeutic_engagement']),
        'clinical_consistency' => isset($_POST['clinical_consistency']),
        'avoidance_patterns' => isset($_POST['avoidance_patterns']),
        'device_type' => isset($_POST['device_type'])
    );
    
    $result = save_privacy_config($form_id, $config);
    
    if ($result) {
        wp_send_json_success(array('message' => __('✅ Configuración guardada correctamente.', 'vas-dinamico-forms')));
    } else {
        wp_send_json_error(array('message' => __('Error al guardar la configuración.', 'vas-dinamico-forms')));
    }
}

function vas_dinamico_submit_form_handler() {
    check_ajax_referer('eipsi_forms_nonce', 'nonce');
    
    global $wpdb;
    
    $form_name = isset($_POST['form_id']) ? sanitize_text_field($_POST['form_id']) : 'default';
    
    // Capturar IP del participante (REQUERIDO) con detección de proxy
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // Si está detrás de proxy/CDN (Cloudflare, Load Balancer, etc.)
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $ip_address = $_SERVER['HTTP_CF_CONNECTING_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip_address = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    }
    
    // Validar IP
    $ip_address = filter_var($ip_address, FILTER_VALIDATE_IP) ?: 'invalid';
    
    $device = isset($_POST['device']) ? sanitize_text_field($_POST['device']) : '';
    $browser = isset($_POST['browser']) ? sanitize_text_field($_POST['browser']) : '';
    $os = isset($_POST['os']) ? sanitize_text_field($_POST['os']) : '';
    $screen_width = isset($_POST['screen_width']) ? intval($_POST['screen_width']) : 0;
    $start_time = isset($_POST['form_start_time']) ? sanitize_text_field($_POST['form_start_time']) : '';
    $end_time = isset($_POST['form_end_time']) ? sanitize_text_field($_POST['form_end_time']) : '';
    
    // Obtener IDs universales del frontend
    $frontend_participant_id = isset($_POST['participant_id']) ? sanitize_text_field($_POST['participant_id']) : '';
    $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';
    
    $form_responses = array();
    $exclude_fields = array('form_id', 'form_action', 'ip_address', 'device', 'browser', 'os', 'screen_width', 'form_start_time', 'form_end_time', 'current_page', 'nonce', 'action', 'participant_id', 'session_id');
    
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
        
        if (!empty($end_time)) {
            $end_timestamp_ms = intval($end_time);
            $duration_ms = max(0, $end_timestamp_ms - $start_timestamp_ms);
            $duration = intval($duration_ms / 1000);
            $duration_seconds = round($duration_ms / 1000, 3);
        } else {
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
    
    // Construir metadatos según configuración de privacidad
    $metadata = array(
        'form_id' => $stable_form_id,
        'participant_id' => $participant_id,
        'session_id' => $session_id
    );
    
    // TIMESTAMPS (SIEMPRE)
    $metadata['timestamps'] = array(
        'start' => $start_timestamp_ms,
        'end' => $end_timestamp_ms,
        'duration_seconds' => $duration_seconds
    );
    
    // DEVICE (si está habilitado)
    if ($privacy_config['device_type']) {
        $metadata['device_info'] = array(
            'device_type' => $device
        );
    }
    
    // NETWORK INFO (IP SIEMPRE - REQUERIDO)
    $metadata['network_info'] = array(
        'ip_address' => $ip_address,
        'ip_storage_type' => $privacy_config['ip_storage']
    );
    
    // CLINICAL INSIGHTS
    $metadata['clinical_insights'] = array();
    
    if ($privacy_config['therapeutic_engagement']) {
        $metadata['clinical_insights']['therapeutic_engagement'] = eipsi_calculate_engagement_score($form_responses, $duration_seconds);
    }
    
    if ($privacy_config['clinical_consistency']) {
        $metadata['clinical_insights']['clinical_consistency'] = eipsi_calculate_consistency_score($form_responses);
    }
    
    if ($privacy_config['avoidance_patterns']) {
        $metadata['clinical_insights']['avoidance_patterns'] = eipsi_detect_avoidance_patterns($form_responses);
    }
    
    // QUALITY METRICS (SIEMPRE)
    $quality_flag = eipsi_calculate_quality_flag($form_responses, $duration_seconds);
    $metadata['quality_metrics'] = array(
        'quality_flag' => $quality_flag,
        'completion_rate' => 1.0
    );
    
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
        'quality_flag' => $quality_flag,
        'status' => 'submitted',
        'form_responses' => wp_json_encode($form_responses)
    );
    
    // Check if external database is configured
    require_once VAS_DINAMICO_PLUGIN_DIR . 'admin/database.php';
    $db_helper = new EIPSI_External_Database();
    
    $external_db_enabled = $db_helper->is_enabled();
    $used_fallback = false;
    $error_info = null;
    
    if ($external_db_enabled) {
        // Try external database first
        $result = $db_helper->insert_form_submission($data);
        
        if ($result['success']) {
            // External DB insert succeeded
            wp_send_json_success(array(
                'message' => __('Form submitted successfully!', 'vas-dinamico-forms'),
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
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%f', '%d', '%d', '%s', '%s', '%s', '%s')
        );
        
        if ($wpdb_result === false) {
            // WordPress DB insert also failed - critical error
            $wpdb_error = $wpdb->last_error;
            error_log('EIPSI Forms: WordPress DB insert failed - ' . $wpdb_error);
            
            wp_send_json_error(array(
                'message' => __('Failed to submit form. Please try again.', 'vas-dinamico-forms'),
                'external_db_error' => $error_info,
                'wordpress_db_error' => $wpdb_error
            ));
        }
        
        $insert_id = $wpdb->insert_id;
        
        if ($used_fallback) {
            // Fallback succeeded - inform user with warning
            wp_send_json_success(array(
                'message' => __('Form submitted successfully!', 'vas-dinamico-forms'),
                'external_db' => false,
                'fallback_used' => true,
                'warning' => __('Form was saved to local database (external database temporarily unavailable).', 'vas-dinamico-forms'),
                'insert_id' => $insert_id,
                'error_code' => $error_info['error_code']
            ));
        } else {
            // Normal WordPress DB submission
            wp_send_json_success(array(
                'message' => __('Form submitted successfully!', 'vas-dinamico-forms'),
                'external_db' => false,
                'insert_id' => $insert_id
            ));
        }
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
    $html .= '<p><strong>📍 Dispositivo:</strong> ' . esc_html($response->device) . ' (' . esc_html($response->browser) . ' on ' . esc_html($response->os) . ')</p>';
    $html .= '<p><strong>🖥️ Ancho pantalla:</strong> ' . ($response->screen_width ? esc_html($response->screen_width) . 'px' : 'N/A') . '</p>';
    $html .= '</div>';
    
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