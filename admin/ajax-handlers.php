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
add_action('wp_ajax_eipsi_save_global_privacy_config', 'eipsi_save_global_privacy_config_handler');
add_action('wp_ajax_eipsi_verify_schema', 'eipsi_verify_schema_handler');
add_action('wp_ajax_eipsi_check_table_status', 'eipsi_check_table_status_handler');
add_action('wp_ajax_eipsi_delete_all_data', 'eipsi_delete_all_data_handler');

// Save & Continue handlers
add_action('wp_ajax_nopriv_eipsi_save_partial_response', 'eipsi_save_partial_response_handler');
add_action('wp_ajax_eipsi_save_partial_response', 'eipsi_save_partial_response_handler');
add_action('wp_ajax_nopriv_eipsi_load_partial_response', 'eipsi_load_partial_response_handler');
add_action('wp_ajax_eipsi_load_partial_response', 'eipsi_load_partial_response_handler');
add_action('wp_ajax_nopriv_eipsi_discard_partial_response', 'eipsi_discard_partial_response_handler');
add_action('wp_ajax_eipsi_discard_partial_response', 'eipsi_discard_partial_response_handler');

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
 * v1 mínima: PHQ-9 / GAD-7 inconsistency detection
 */
function eipsi_calculate_consistency_score($responses) {
    $inconsistencies = 0;

    // Regla mínima para escalas tipo PHQ-9 / GAD-7 (ítems 0-3)
    // Buscamos un ítem de riesgo alto (p.ej. ideación suicida) y lo comparamos
    // con el promedio del resto de los ítems de la misma escala.
    $suicidal_item = $responses['phq9_q9'] ?? $responses['gad7_q7'] ?? null;

    if ($suicidal_item !== null) {
        $total_score = 0;
        $count = 0;

        foreach ($responses as $k => $v) {
            // Consideramos preguntas de PHQ-9 o GAD-7
            if (strpos($k, 'phq9_q') === 0 || strpos($k, 'gad7_q') === 0) {
                // Excluimos los ítems de riesgo clave (q9 PHQ, q7 GAD)
                if (strpos($k, '_q9') === false && strpos($k, '_q7') === false) {
                    $total_score += intval($v);
                    $count++;
                }
            }
        }

        $avg_score = $count > 0 ? $total_score / $count : 0;
        $suicidal_val = intval($suicidal_item);

        // Inconsistencias básicas:
        // - Promedio bajo (<1.5) pero ítem de riesgo alto (>=2)
        if ($avg_score < 1.5 && $suicidal_val >= 2) {
            $inconsistencies++;
        }

        // - Promedio alto (>=2.5) pero ítem de riesgo en 0
        if ($avg_score >= 2.5 && $suicidal_val === 0) {
            $inconsistencies++;
        }
    }

    // v1: score binario simple
    return $inconsistencies === 0 ? 1.0 : 0.6;
}

/**
 * Detecta patrones de evitación (saltos, retrocesos)
 */
function eipsi_detect_avoidance_patterns($duration_seconds, $total_pages) {
    $patterns = array();

    // Regla mínima: respuesta demasiado rápida en relación a la cantidad de páginas
    // (umbral aproximado: < 9 segundos por página)
    if ($total_pages > 0 && $duration_seconds > 0 && $duration_seconds < ($total_pages * 9)) {
        $patterns[] = 'respuesta_extremadamente_rápida';
    }

    return $patterns;
}

/**
 * Calcula quality flag basado en múltiples factores
 */
function eipsi_calculate_quality_flag($responses, $duration_seconds, $total_pages = null) {
    // Estimar total_pages si no se proporciona (aproximación: ~5 campos por página)
    if ($total_pages === null) {
        $total_pages = max(1, ceil(count($responses) / 5));
    }
    
    $engagement = eipsi_calculate_engagement_score($responses, $duration_seconds);
    $consistency = eipsi_calculate_consistency_score($responses);
    $avoidance = eipsi_detect_avoidance_patterns($duration_seconds, $total_pages);
    
    $avg_score = ($engagement + $consistency) / 2;
    
    // Opcional: si hay avoidance patterns, penalizar un poco más
    if (!empty($avoidance) && $avg_score > 0.6) {
        $avg_score = 0.6;
    }
    
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
        'avoidance_patterns' => isset($_POST['avoidance_patterns']),
        'device_type' => isset($_POST['device_type']),
        'browser' => isset($_POST['browser']),
        'os' => isset($_POST['os']),
        'screen_width' => isset($_POST['screen_width']),
        'ip_address' => isset($_POST['ip_address']),
        'quality_flag' => isset($_POST['quality_flag'])
    );
    
    $result = save_privacy_config($form_id, $config);
    
    if ($result) {
        wp_send_json_success(array('message' => __('✅ Configuración guardada correctamente.', 'vas-dinamico-forms')));
    } else {
        wp_send_json_error(array('message' => __('Error al guardar la configuración.', 'vas-dinamico-forms')));
    }
}

function eipsi_save_global_privacy_config_handler() {
    check_ajax_referer('eipsi_global_privacy_nonce', 'eipsi_global_privacy_nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('No tienes permisos para realizar esta acción.', 'vas-dinamico-forms')));
    }
    
    require_once dirname(__FILE__) . '/privacy-config.php';
    
    $config = array(
        'therapeutic_engagement' => isset($_POST['therapeutic_engagement']),
        'avoidance_patterns' => isset($_POST['avoidance_patterns']),
        'device_type' => isset($_POST['device_type']),
        'browser' => isset($_POST['browser']),
        'os' => isset($_POST['os']),
        'screen_width' => isset($_POST['screen_width']),
        'ip_address' => isset($_POST['ip_address']),
        'quality_flag' => isset($_POST['quality_flag'])
    );
    
    $result = save_global_privacy_defaults($config);
    
    if ($result) {
        wp_send_json_success(array('message' => __('✅ Configuración global guardada correctamente.', 'vas-dinamico-forms')));
    } else {
        wp_send_json_error(array('message' => __('Error al guardar la configuración global.', 'vas-dinamico-forms')));
    }
}

function vas_dinamico_submit_form_handler() {
    check_ajax_referer('eipsi_forms_nonce', 'nonce');
    
    global $wpdb;
    
    $form_name = isset($_POST['form_id']) ? sanitize_text_field($_POST['form_id']) : 'default';
    
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
    
    // Aplicar privacy config a los campos capturados
    $browser = ($privacy_config['browser'] ?? false) ? $browser_raw : null;
    $os = ($privacy_config['os'] ?? false) ? $os_raw : null;
    $screen_width = ($privacy_config['screen_width'] ?? false) ? $screen_width_raw : null;
    $ip_address = ($privacy_config['ip_address'] ?? true) ? $ip_address_raw : null;
    
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
    
    // CLINICAL INSIGHTS
    $metadata['clinical_insights'] = array();
    
    // Estimar total_pages basado en cantidad de campos (aproximación: ~5 campos por página)
    $estimated_total_pages = max(1, ceil(count($form_responses) / 5));
    
    if ($privacy_config['therapeutic_engagement']) {
        $metadata['clinical_insights']['therapeutic_engagement'] = eipsi_calculate_engagement_score($form_responses, $duration_seconds);
    }
    
    if ($privacy_config['avoidance_patterns']) {
        $metadata['clinical_insights']['avoidance_patterns'] = eipsi_detect_avoidance_patterns($duration_seconds, $estimated_total_pages);
    }
    
    // QUALITY METRICS (según privacidad config)
    $quality_flag = null;
    if ($privacy_config['quality_flag'] ?? true) {
        $quality_flag = eipsi_calculate_quality_flag($form_responses, $duration_seconds, $estimated_total_pages);
        $metadata['quality_metrics'] = array(
            'quality_flag' => $quality_flag,
            'completion_rate' => 1.0
        );
    } else {
        $metadata['quality_metrics'] = array(
            'completion_rate' => 1.0
        );
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
            EIPSI_Partial_Responses::mark_completed($stable_form_id, $participant_id, $session_id);
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
                    array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%f', '%d', '%d', '%s', '%s', '%s', '%s')
                );
                
                if ($wpdb_result !== false) {
                    // Success after repair!
                    error_log('[EIPSI Forms] Auto-repaired schema and recovered data insertion');
                    $insert_id = $wpdb->insert_id;
                    EIPSI_Partial_Responses::mark_completed($stable_form_id, $participant_id, $session_id);
                    
                    wp_send_json_success(array(
                        'message' => __('Form submitted successfully!', 'vas-dinamico-forms'),
                        'external_db' => false,
                        'schema_repaired' => true,
                        'insert_id' => $insert_id
                    ));
                } else {
                    // Still failed after repair
                    error_log('[EIPSI Forms CRITICAL] Schema repair failed: ' . $wpdb->last_error);
                    wp_send_json_error(array(
                        'message' => __('Database error (recovery attempted)', 'vas-dinamico-forms'),
                        'external_db_error' => $error_info,
                        'wordpress_db_error' => $wpdb->last_error
                    ));
                }
            } else {
                // Other database error (not schema-related)
                error_log('EIPSI Forms: WordPress DB insert failed - ' . $wpdb_error);
                
                wp_send_json_error(array(
                    'message' => __('Failed to submit form. Please try again.', 'vas-dinamico-forms'),
                    'external_db_error' => $error_info,
                    'wordpress_db_error' => $wpdb_error
                ));
            }
            return;
        }
        
        $insert_id = $wpdb->insert_id;
        
        // Mark partial response as completed
        EIPSI_Partial_Responses::mark_completed($stable_form_id, $participant_id, $session_id);
        
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
    
    // INTENTO 1: Buscar en BD Externa si está habilitada
    require_once VAS_DINAMICO_PLUGIN_DIR . 'admin/database.php';
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
    require_once VAS_DINAMICO_PLUGIN_DIR . 'admin/database.php';
    $db_helper = new EIPSI_External_Database();
    $external_db_enabled = $db_helper->is_enabled();
    $used_fallback = false;
    
    if ($external_db_enabled) {
        // Try external database first
        $result = $db_helper->insert_form_event($insert_data);
        
        if ($result['success']) {
            // External DB insert succeeded
            wp_send_json_success(array(
                'message' => __('Event tracked successfully.', 'vas-dinamico-forms'),
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
        // Trigger schema verification and synchronization
        require_once VAS_DINAMICO_PLUGIN_DIR . 'admin/database-schema-manager.php';
        $schema_result = EIPSI_Database_Schema_Manager::on_credentials_changed();
        
        $status = $db_helper->get_status();
        
        // Include schema verification results in response
        $response_data = array(
            'message' => sprintf(
                __('Configuration saved successfully! Data will now be stored in: %s', 'vas-dinamico-forms'),
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

function eipsi_verify_schema_handler() {
    check_ajax_referer('eipsi_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array(
            'message' => __('Unauthorized', 'vas-dinamico-forms')
        ));
    }
    
    require_once VAS_DINAMICO_PLUGIN_DIR . 'admin/database.php';
    require_once VAS_DINAMICO_PLUGIN_DIR . 'admin/database-schema-manager.php';
    
    $db_helper = new EIPSI_External_Database();
    
    if (!$db_helper->is_enabled()) {
        wp_send_json_error(array(
            'message' => __('External database is not enabled', 'vas-dinamico-forms')
        ));
    }
    
    $mysqli = $db_helper->get_connection();
    
    if (!$mysqli) {
        wp_send_json_error(array(
            'message' => __('Failed to connect to external database', 'vas-dinamico-forms')
        ));
    }
    
    $result = EIPSI_Database_Schema_Manager::verify_and_sync_schema($mysqli);
    $mysqli->close();
    
    if ($result['success']) {
        wp_send_json_success(array(
            'message' => __('Schema verification completed successfully', 'vas-dinamico-forms'),
            'results' => $result
        ));
    } else {
        wp_send_json_error(array(
            'message' => __('Schema verification failed', 'vas-dinamico-forms'),
            'errors' => $result['errors']
        ));
    }
}

function eipsi_check_table_status_handler() {
    check_ajax_referer('eipsi_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array(
            'message' => __('Unauthorized', 'vas-dinamico-forms')
        ));
    }

    require_once VAS_DINAMICO_PLUGIN_DIR . 'admin/database.php';
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
            'message' => __('Unauthorized', 'vas-dinamico-forms')
        ), 403);
    }

    require_once VAS_DINAMICO_PLUGIN_DIR . 'admin/database.php';
    $db_helper = new EIPSI_External_Database();

    $result = $db_helper->delete_all_data();

    if ($result['success']) {
        wp_send_json_success(array(
            'message' => $result['message'],
            'database' => isset($result['database']) ? $result['database'] : 'wordpress'
        ));
    } else {
        wp_send_json_error(array(
            'message' => isset($result['message']) ? $result['message'] : __('Failed to delete clinical data.', 'vas-dinamico-forms'),
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
        wp_send_json_error(array('message' => __('Unauthorized', 'vas-dinamico-forms')), 403);
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
    
    require_once VAS_DINAMICO_PLUGIN_DIR . 'admin/completion-message-backend.php';
    
    if (EIPSI_Completion_Message::save_config($config)) {
        wp_send_json_success(array(
            'message' => __('Completion message saved successfully', 'vas-dinamico-forms'),
            'config'  => EIPSI_Completion_Message::get_config(),
        ));
    } else {
        wp_send_json_error(array('message' => __('Failed to save configuration', 'vas-dinamico-forms')));
    }
}
add_action('wp_ajax_eipsi_save_completion_message', 'eipsi_save_completion_message_handler');

/**
 * AJAX Handler: Get completion message configuration for frontend
 */
function eipsi_get_completion_config_handler() {
    require_once VAS_DINAMICO_PLUGIN_DIR . 'admin/completion-message-backend.php';
    
    $config = EIPSI_Completion_Message::get_config();
    
    wp_send_json_success(array(
        'config' => $config,
    ));
}
add_action('wp_ajax_nopriv_eipsi_get_completion_config', 'eipsi_get_completion_config_handler');
add_action('wp_ajax_eipsi_get_completion_config', 'eipsi_get_completion_config_handler');

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
            'message' => __('Missing required parameters', 'vas-dinamico-forms')
        ));
    }
    
    $result = EIPSI_Partial_Responses::save($form_id, $participant_id, $session_id, $page_index, $responses);
    
    if ($result['success']) {
        wp_send_json_success(array(
            'message' => __('Partial response saved', 'vas-dinamico-forms'),
            'action' => $result['action'],
            'id' => $result['id']
        ));
    } else {
        wp_send_json_error(array(
            'message' => __('Failed to save partial response', 'vas-dinamico-forms'),
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
            'message' => __('Missing required parameters', 'vas-dinamico-forms')
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
            'message' => __('Missing required parameters', 'vas-dinamico-forms')
        ));
    }
    
    $success = EIPSI_Partial_Responses::discard($form_id, $participant_id, $session_id);
    
    if ($success) {
        wp_send_json_success(array(
            'message' => __('Partial response discarded', 'vas-dinamico-forms')
        ));
    } else {
        wp_send_json_error(array(
            'message' => __('Failed to discard partial response', 'vas-dinamico-forms')
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
            'message' => __('Permission denied or invalid security token.', 'vas-dinamico-forms')
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
            'message' => __('Submissions synchronized with database.', 'vas-dinamico-forms'),
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
            'message' => __('Submissions synchronized with local database (external connection unavailable).', 'vas-dinamico-forms'),
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
        'message' => __('Submissions synchronized with database.', 'vas-dinamico-forms'),
        'source' => 'external'
    ));
}

/**
 * AJAX Handler: Save global privacy configuration
 */
function eipsi_save_global_privacy_config_handler() {
    // Security check
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array(
            'message' => __('Permission denied.', 'vas-dinamico-forms')
        ));
    }

    require_once VAS_DINAMICO_PLUGIN_DIR . 'admin/privacy-config.php';

    // Build config array from POST data
    $config = array();
    $allowed_toggles = array(
        'therapeutic_engagement',
        'avoidance_patterns',
        'device_type',
        'browser',
        'os',
        'screen_width',
        'ip_address',
        'quality_flag'
    );

    foreach ($allowed_toggles as $toggle) {
        $config[$toggle] = isset($_POST[$toggle]) ? (bool) $_POST[$toggle] : false;
    }

    // Save global config
    $result = save_global_privacy_defaults($config);

    if ($result) {
        wp_send_json_success(array(
            'message' => __('Configuración global guardada correctamente.', 'vas-dinamico-forms')
        ));
    } else {
        wp_send_json_error(array(
            'message' => __('Error al guardar la configuración global.', 'vas-dinamico-forms')
        ));
    }
}

/**
 * AJAX Handler: Save per-form privacy configuration
 */
function eipsi_save_privacy_config_handler() {
    // Security check
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array(
            'message' => __('Permission denied.', 'vas-dinamico-forms')
        ));
    }

    require_once VAS_DINAMICO_PLUGIN_DIR . 'admin/privacy-config.php';

    // Get form_id
    $form_id = isset($_POST['form_id']) ? sanitize_text_field($_POST['form_id']) : '';

    if (empty($form_id)) {
        wp_send_json_error(array(
            'message' => __('Form ID is required.', 'vas-dinamico-forms')
        ));
    }

    // Build config array from POST data
    $config = array();
    $allowed_toggles = array(
        'therapeutic_engagement',
        'avoidance_patterns',
        'device_type',
        'browser',
        'os',
        'screen_width',
        'ip_address',
        'quality_flag'
    );

    foreach ($allowed_toggles as $toggle) {
        $config[$toggle] = isset($_POST[$toggle]) ? (bool) $_POST[$toggle] : false;
    }

    // Save per-form config
    $result = save_privacy_config($form_id, $config);

    if ($result) {
        wp_send_json_success(array(
            'message' => __('Configuración guardada correctamente para el formulario.', 'vas-dinamico-forms')
        ));
    } else {
        wp_send_json_error(array(
            'message' => __('Error al guardar la configuración.', 'vas-dinamico-forms')
        ));
    }
}

add_action('wp_ajax_eipsi_sync_submissions', 'eipsi_sync_submissions_handler');
?>