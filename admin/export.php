<?php
if (!defined('ABSPATH')) {
    exit;
}

// Incluir la librería
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/lib/SimpleXLSXGen.php';

// Usar el namespace
use Shuchkin\SimpleXLSXGen;

function export_generate_stable_form_id($form_name) {
    global $wpdb;
    
    $initials = export_get_form_initials($form_name);
    
    if (strlen($initials) < 2) {
        $slug = sanitize_title($form_name);
        $initials = strtoupper(substr($slug, 0, 3));
    }
    
    $slug = sanitize_title($form_name);
    $hash = substr(md5($slug), 0, 6);
    $form_id = "{$initials}-{$hash}";
    
    return $form_id;
}

function export_get_form_initials($form_name) {
    $stop_words = array('de', 'la', 'el', 'y', 'en', 'con', 'para', 'del', 'los', 'las');
    $words = preg_split('/\s+/', strtolower($form_name));
    $words = array_diff($words, $stop_words);
    $initials = '';
    foreach ($words as $word) {
        if (!empty($word)) {
            $initials .= strtoupper(substr($word, 0, 1));
        }
    }
    return $initials;
}

function export_generateStableFingerprint($user_data) {
    $components = array(
        'email' => strtolower(trim($user_data['email'] ?? '')),
        'name' => export_normalizeName($user_data['name'] ?? ''),
    );
    
    $fingerprint_string = implode('|', array_filter($components));
    
    if ($fingerprint_string) {
        $hash = substr(hash('sha256', $fingerprint_string), 0, 8);
        return "FP-{$hash}";
    } else {
        return "FP-SESS-" . substr(md5(uniqid()), 0, 6);
    }
}

function export_normalizeName($name) {
    return strtoupper(trim($name));
}

function eipsi_export_to_excel() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to perform this action.', 'eipsi-forms'));
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'vas_form_results';
    
    // Instanciar clase de BD externa
    $external_db = new EIPSI_External_Database();
    $results = array();
    
    if ($external_db->is_enabled()) {
        // Usar BD externa si está habilitada
        $mysqli = $external_db->get_connection();
        if ($mysqli) {
            // Preparar filtro de forma segura para mysqli
            $where = "WHERE 1=1";
            if (isset($_GET['form_id']) && !empty($_GET['form_id'])) {
                $form_id = $mysqli->real_escape_string($_GET['form_id']);
                $where .= " AND form_id = '{$form_id}'";
            }
            
            $query = "SELECT * FROM `{$table_name}` {$where} ORDER BY created_at DESC";
            $result = $mysqli->query($query);
            
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    // Convertir array asociativo a stdClass para mantener compatibilidad
                    $results[] = (object) $row;
                }
            }
            $mysqli->close();
        } else {
            // Fallback a BD local si conexión externa falla
            $form_filter = isset($_GET['form_id']) ? $wpdb->prepare('AND form_id = %s', $_GET['form_id']) : '';
            $results = $wpdb->get_results("SELECT * FROM $table_name WHERE 1=1 $form_filter ORDER BY created_at DESC");
        }
    } else {
        // Fallback a BD local si no hay BD externa
        $form_filter = isset($_GET['form_id']) ? $wpdb->prepare('AND form_id = %s', $_GET['form_id']) : '';
        $results = $wpdb->get_results("SELECT * FROM $table_name WHERE 1=1 $form_filter ORDER BY created_at DESC");
    }
    
    if (empty($results)) {
        wp_die(__('No data to export.', 'eipsi-forms'));
    }
    
    // Get privacy config for first form (assuming same config per form_name)
    require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/privacy-config.php';
    $first_form_id = !empty($results[0]->form_id) ? $results[0]->form_id : export_generate_stable_form_id($results[0]->form_name);
    $privacy_config = get_privacy_config($first_form_id);

    // Obtener todas las preguntas únicas para crear columnas (excluir campos internos)
    $internal_fields = array('action', 'eipsi_nonce', 'start_time', 'end_time', 'form_start_time', 'form_end_time', 'nonce', 'form_action', 'ip_address', 'device', 'browser', 'os', 'screen_width', 'current_page', 'form_id', 'eipsi_consent_accepted');
    $all_questions = [];
    foreach ($results as $row) {
        $form_data = $row->form_responses ? json_decode($row->form_responses, true) : [];
        foreach ($form_data as $question => $answer) {
            if (!in_array($question, $all_questions) && !in_array($question, $internal_fields)) {
                $all_questions[] = $question;
            }
        }
    }

    // === DETECTAR TIEMPOS POR PÁGINA Y CAMPO (Excel) ===
    $page_timing_keys = [];
    $field_timing_keys = [];
    $has_page_timings = false;
    $has_field_timings = false;

    foreach ($results as $row) {
        $metadata = !empty($row->metadata) ? json_decode($row->metadata, true) : [];

        // Detectar page_timings
        if (isset($metadata['page_timings']) && is_array($metadata['page_timings'])) {
            $has_page_timings = true;
            foreach ($metadata['page_timings'] as $key => $val) {
                // Excluir total_duration, solo procesar page_0, page_1, etc.
                if ($key !== 'total_duration' && strpos($key, 'page_') === 0) {
                    if (!in_array($key, $page_timing_keys)) {
                        $page_timing_keys[] = $key;
                    }
                }
            }
        }

        // Detectar field_timings
        if (isset($metadata['field_timings']) && is_array($metadata['field_timings'])) {
            $has_field_timings = true;
            foreach ($metadata['field_timings'] as $field_name => $field_data) {
                if (!in_array($field_name, $field_timing_keys)) {
                    $field_timing_keys[] = $field_name;
                }
            }
        }
    }

    // Ordenar page_timing_keys numéricamente (page_0, page_1, page_2, ...)
    usort($page_timing_keys, function($a, $b) {
        $num_a = intval(str_replace('page_', '', $a));
        $num_b = intval(str_replace('page_', '', $b));
        return $num_a <=> $num_b;
    });

    // Detectar si hay aleatorización real en los resultados
    $has_randomization = false;
    foreach ($results as $row) {
        $metadata = !empty($row->metadata) ? json_decode($row->metadata, true) : [];
        if (!empty($metadata['random_assignment']['form_id']) && $metadata['random_assignment']['form_id'] !== '-') {
            $has_randomization = true;
            break;
        }
    }

    $data = array();
    // Encabezados: nuevo formato con IDs + metadatos + timestamps + preguntas dinámicas
    // ONLY include metadata columns if privacy config allows
    $headers = array('Form ID', 'Participant ID', 'Form Name', 'Date', 'Time', 'Duration(s)', 'Start Time (UTC)', 'End Time (UTC)');

    // ✅ v1.5.4 - Fingerprint ID (si fingerprint_enabled está activado)
    if ($privacy_config['fingerprint_enabled'] ?? true) {
        $headers[] = 'Fingerprint ID';
    }

    // === Columnas de Aleatorización (Fase 1) - Solo si hay datos reales ===
    if ($has_randomization) {
        $headers[] = 'Assignment Form';
        $headers[] = 'Seed';
        $headers[] = 'Type (Random/Manual)';
    }

    if ($privacy_config['ip_address']) {
        $headers[] = 'IP Address';
    }
    if ($privacy_config['device_type']) {
        $headers[] = 'Device';
    }
    if ($privacy_config['browser']) {
        $headers[] = 'Browser';
    }
    if ($privacy_config['os']) {
        $headers[] = 'OS';
    }

    // === Columnas de Page Timings ===
    foreach ($page_timing_keys as $page_key) {
        $page_num = intval(str_replace('page_', '', $page_key));
        $headers[] = "Page {$page_num} - Duration(s)";
        $headers[] = "Page {$page_num} - Timestamp";
    }

    // === Columnas de Field Timings ===
    foreach ($field_timing_keys as $field_name) {
        $headers[] = "{$field_name} - Time(s)";
        $headers[] = "{$field_name} - Interactions";
        $headers[] = "{$field_name} - Focus Count";
    }

    // === Columna Total Duration ===
    if ($has_page_timings || $has_field_timings) {
        $headers[] = 'Total Duration(s)';
    }

    $headers = array_merge($headers, $all_questions);
    $data[] = $headers;
    
    foreach ($results as $row) {
        $form_data = $row->form_responses ? json_decode($row->form_responses, true) : [];
        
        // Generate IDs if not already present
        $form_id = !empty($row->form_id) ? $row->form_id : export_generate_stable_form_id($row->form_name);
        
        // Extract user data for fingerprint
        $user_data = array(
            'email' => '',
            'name' => ''
        );
        foreach ($form_data as $key => $value) {
            if (strtolower($key) === 'email' || strpos(strtolower($key), 'correo') !== false) {
                $user_data['email'] = $value;
            }
            if (strtolower($key) === 'name' || strtolower($key) === 'nombre') {
                $user_data['name'] = $value;
            }
        }
        
        $participant_id = !empty($row->participant_id) ? $row->participant_id : export_generateStableFingerprint($user_data);
        
        // Format date and time separately
        $datetime = !empty($row->submitted_at) ? $row->submitted_at : $row->created_at;
        $date = date('Y-m-d', strtotime($datetime));
        $time = date('H:i:s', strtotime($datetime));
        
        // Use duration_seconds if available, otherwise duration
        $duration = !empty($row->duration_seconds) ? number_format($row->duration_seconds, 3, '.', '') : $row->duration;
        
        // Format timestamps as ISO 8601 datetime strings
        $start_time_utc = '';
        $end_time_utc = '';
        if (!empty($row->start_timestamp_ms)) {
            $start_time_utc = gmdate('Y-m-d\TH:i:s.v\Z', intval($row->start_timestamp_ms / 1000));
        }
        if (!empty($row->end_timestamp_ms)) {
            $end_time_utc = gmdate('Y-m-d\TH:i:s.v\Z', intval($row->end_timestamp_ms / 1000));
        }

        // ✅ v1.5.4 - Obtener fingerprint_id
        $fingerprint_id = !empty($row->user_fingerprint) ? $row->user_fingerprint : '';

        // === Obtener datos de aleatorización (solo si hay randomización real) ===
        $row_data = array(
            $form_id,
            $participant_id,
            $row->form_name,
            $date,
            $time,
            $duration,
            $start_time_utc,
            $end_time_utc,
        );

        // ✅ v1.5.4 - Agregar fingerprint_id a la fila (si fingerprint_enabled está activado)
        if ($privacy_config['fingerprint_enabled'] ?? true) {
            $row_data[] = $fingerprint_id;
        }

        // Solo agregar datos de aleatorización si hay randomización real
        if ($has_randomization) {
            $metadata = !empty($row->metadata) ? json_decode($row->metadata, true) : [];
            $assignment_form = $metadata['random_assignment']['form_id'] ?? '-';
            $assignment_seed = $metadata['random_assignment']['seed'] ?? '-';
            $assignment_type = $metadata['random_assignment']['type'] ?? '-';
            
            $row_data[] = $assignment_form;
            $row_data[] = $assignment_seed;
            $row_data[] = $assignment_type;
        }
        
        // Add metadata fields only if privacy config allows
        if ($privacy_config['ip_address']) {
            $row_data[] = $row->ip_address;
        }
        if ($privacy_config['device_type']) {
            $row_data[] = $row->device;
        }
        if ($privacy_config['browser']) {
            $row_data[] = $row->browser;
        }
        if ($privacy_config['os']) {
            $row_data[] = $row->os;
        }

        // === Agregar datos de Page Timings ===
        $metadata = !empty($row->metadata) ? json_decode($row->metadata, true) : [];
        foreach ($page_timing_keys as $page_key) {
            $page_data = $metadata['page_timings'][$page_key] ?? null;
            if ($page_data) {
                // Duration: usar rawDuration si existe, sino extraer de duration
                $duration = isset($page_data['rawDuration']) ? $page_data['rawDuration'] : (isset($page_data['duration']) ? floatval($page_data['duration']) : '');
                $row_data[] = $duration;
                // Timestamp
                $row_data[] = $page_data['timestamp'] ?? '';
            } else {
                $row_data[] = '';
                $row_data[] = '';
            }
        }

        // === Agregar datos de Field Timings ===
        foreach ($field_timing_keys as $field_name) {
            $field_data = $metadata['field_timings'][$field_name] ?? null;
            if ($field_data) {
                $row_data[] = $field_data['time_focused'] ?? '';
                $row_data[] = $field_data['interaction_count'] ?? '';
                $row_data[] = $field_data['focus_count'] ?? '';
            } else {
                $row_data[] = '';
                $row_data[] = '';
                $row_data[] = '';
            }
        }

        // === Agregar Total Duration ===
        if ($has_page_timings || $has_field_timings) {
            $total_duration = $metadata['page_timings']['total_duration'] ?? '';
            $row_data[] = $total_duration;
        }

        // Agregar respuestas en el orden de las preguntas (excluir campos internos)
        foreach ($all_questions as $question) {
            $row_data[] = isset($form_data[$question]) ? (is_array($form_data[$question]) ? json_encode($form_data[$question]) : $form_data[$question]) : '';
        }

        $data[] = $row_data;
    }

    $xlsx = SimpleXLSXGen::fromArray($data);
    $form_suffix = isset($_GET['form_name']) ? '-' . sanitize_title($_GET['form_name']) : '';
    $filename = 'form-responses' . $form_suffix . '-' . date('Y-m-d-H-i-s') . '.xlsx';
    $xlsx->downloadAs($filename);
    exit;
}

function eipsi_export_to_csv() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to perform this action.', 'eipsi-forms'));
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'vas_form_results';
    
    // Instanciar clase de BD externa
    $external_db = new EIPSI_External_Database();
    $results = array();
    
    if ($external_db->is_enabled()) {
        // Usar BD externa si está habilitada
        $mysqli = $external_db->get_connection();
        if ($mysqli) {
            // Preparar filtro de forma segura para mysqli
            $where = "WHERE 1=1";
            if (isset($_GET['form_id']) && !empty($_GET['form_id'])) {
                $form_id = $mysqli->real_escape_string($_GET['form_id']);
                $where .= " AND form_id = '{$form_id}'";
            }
            
            $query = "SELECT * FROM `{$table_name}` {$where} ORDER BY created_at DESC";
            $result = $mysqli->query($query);
            
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    // Convertir array asociativo a stdClass para mantener compatibilidad
                    $results[] = (object) $row;
                }
            }
            $mysqli->close();
        } else {
            // Fallback a BD local si conexión externa falla
            $form_filter = isset($_GET['form_id']) ? $wpdb->prepare('AND form_id = %s', $_GET['form_id']) : '';
            $results = $wpdb->get_results("SELECT * FROM $table_name WHERE 1=1 $form_filter ORDER BY created_at DESC");
        }
    } else {
        // Fallback a BD local si no hay BD externa
        $form_filter = isset($_GET['form_id']) ? $wpdb->prepare('AND form_id = %s', $_GET['form_id']) : '';
        $results = $wpdb->get_results("SELECT * FROM $table_name WHERE 1=1 $form_filter ORDER BY created_at DESC");
    }
    
    if (empty($results)) {
        wp_die(__('No data to export.', 'eipsi-forms'));
    }
    
    // Get privacy config for first form (assuming same config per form_name)
    require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/privacy-config.php';
    $first_form_id = !empty($results[0]->form_id) ? $results[0]->form_id : export_generate_stable_form_id($results[0]->form_name);
    $privacy_config = get_privacy_config($first_form_id);
    
    // Obtener todas las preguntas únicas para crear columnas (excluir campos internos)
    $internal_fields = array('action', 'eipsi_nonce', 'start_time', 'end_time', 'form_start_time', 'form_end_time', 'nonce', 'form_action', 'ip_address', 'device', 'browser', 'os', 'screen_width', 'current_page', 'form_id', 'eipsi_consent_accepted');
    $all_questions = [];
    foreach ($results as $row) {
        $form_data = $row->form_responses ? json_decode($row->form_responses, true) : [];
        foreach ($form_data as $question => $answer) {
            if (!in_array($question, $all_questions) && !in_array($question, $internal_fields)) {
                $all_questions[] = $question;
            }
        }
    }

    // === DETECTAR TIEMPOS POR PÁGINA Y CAMPO (CSV) ===
    $page_timing_keys = [];
    $field_timing_keys = [];
    $has_page_timings = false;
    $has_field_timings = false;

    foreach ($results as $row) {
        $metadata = !empty($row->metadata) ? json_decode($row->metadata, true) : [];

        // Detectar page_timings
        if (isset($metadata['page_timings']) && is_array($metadata['page_timings'])) {
            $has_page_timings = true;
            foreach ($metadata['page_timings'] as $key => $val) {
                // Excluir total_duration, solo procesar page_0, page_1, etc.
                if ($key !== 'total_duration' && strpos($key, 'page_') === 0) {
                    if (!in_array($key, $page_timing_keys)) {
                        $page_timing_keys[] = $key;
                    }
                }
            }
        }

        // Detectar field_timings
        if (isset($metadata['field_timings']) && is_array($metadata['field_timings'])) {
            $has_field_timings = true;
            foreach ($metadata['field_timings'] as $field_name => $field_data) {
                if (!in_array($field_name, $field_timing_keys)) {
                    $field_timing_keys[] = $field_name;
                }
            }
        }
    }

    // Ordenar page_timing_keys numéricamente (page_0, page_1, page_2, ...)
    usort($page_timing_keys, function($a, $b) {
        $num_a = intval(str_replace('page_', '', $a));
        $num_b = intval(str_replace('page_', '', $b));
        return $num_a <=> $num_b;
    });

    // Detectar si hay aleatorización real en los resultados
    $has_randomization = false;
    foreach ($results as $row) {
        $metadata = !empty($row->metadata) ? json_decode($row->metadata, true) : [];
        if (!empty($metadata['random_assignment']['form_id']) && $metadata['random_assignment']['form_id'] !== '-') {
            $has_randomization = true;
            break;
        }
    }
    
    header('Content-Type: text/csv; charset=utf-8');
    $form_suffix = isset($_GET['form_name']) ? '-' . sanitize_title($_GET['form_name']) : '';
    header('Content-Disposition: attachment; filename=form-responses' . $form_suffix . '-' . date('Y-m-d-H-i-s') . '.csv');
    
    $output = fopen('php://output', 'w');
    
    // Encabezados: nuevo formato con IDs + metadatos + timestamps + preguntas dinámicas
    // ONLY include metadata columns if privacy config allows
    $headers = array('Form ID', 'Participant ID', 'Form Name', 'Date', 'Time', 'Duration(s)', 'Start Time (UTC)', 'End Time (UTC)');
    
    // === Columnas de Aleatorización (Fase 1) - Solo si hay datos reales ===
    if ($has_randomization) {
        $headers[] = 'Assignment Form';
        $headers[] = 'Seed';
        $headers[] = 'Type (Random/Manual)';
    }
    
    if ($privacy_config['ip_address']) {
        $headers[] = 'IP Address';
    }
    if ($privacy_config['device_type']) {
        $headers[] = 'Device';
    }
    if ($privacy_config['browser']) {
        $headers[] = 'Browser';
    }
    if ($privacy_config['os']) {
        $headers[] = 'OS';
    }

    // === Columnas de Page Timings ===
    foreach ($page_timing_keys as $page_key) {
        $page_num = intval(str_replace('page_', '', $page_key));
        $headers[] = "Page {$page_num} - Duration(s)";
        $headers[] = "Page {$page_num} - Timestamp";
    }

    // === Columnas de Field Timings ===
    foreach ($field_timing_keys as $field_name) {
        $headers[] = "{$field_name} - Time(s)";
        $headers[] = "{$field_name} - Interactions";
        $headers[] = "{$field_name} - Focus Count";
    }

    // === Columna Total Duration ===
    if ($has_page_timings || $has_field_timings) {
        $headers[] = 'Total Duration(s)';
    }

    $headers = array_merge($headers, $all_questions);
    fputcsv($output, $headers);
    
    foreach ($results as $row) {
        $form_data = $row->form_responses ? json_decode($row->form_responses, true) : [];
        
        // Generate IDs if not already present
        $form_id = !empty($row->form_id) ? $row->form_id : export_generate_stable_form_id($row->form_name);
        
        // Extract user data for fingerprint
        $user_data = array(
            'email' => '',
            'name' => ''
        );
        foreach ($form_data as $key => $value) {
            if (strtolower($key) === 'email' || strpos(strtolower($key), 'correo') !== false) {
                $user_data['email'] = $value;
            }
            if (strtolower($key) === 'name' || strtolower($key) === 'nombre') {
                $user_data['name'] = $value;
            }
        }
        
        $participant_id = !empty($row->participant_id) ? $row->participant_id : export_generateStableFingerprint($user_data);
        
        // Format date and time separately
        $datetime = !empty($row->submitted_at) ? $row->submitted_at : $row->created_at;
        $date = date('Y-m-d', strtotime($datetime));
        $time = date('H:i:s', strtotime($datetime));
        
        // Use duration_seconds if available, otherwise duration
        $duration = !empty($row->duration_seconds) ? number_format($row->duration_seconds, 3, '.', '') : $row->duration;
        
        // Format timestamps as ISO 8601 datetime strings
        $start_time_utc = '';
        $end_time_utc = '';
        if (!empty($row->start_timestamp_ms)) {
            $start_time_utc = gmdate('Y-m-d\TH:i:s.v\Z', intval($row->start_timestamp_ms / 1000));
        }
        if (!empty($row->end_timestamp_ms)) {
            $end_time_utc = gmdate('Y-m-d\TH:i:s.v\Z', intval($row->end_timestamp_ms / 1000));
        }

        // ✅ v1.5.4 - Obtener fingerprint_id
        $fingerprint_id = !empty($row->user_fingerprint) ? $row->user_fingerprint : '';

        // === Obtener datos de aleatorización (solo si hay randomización real) ===
        $row_data = array(
            $form_id,
            $participant_id,
            $row->form_name,
            $date,
            $time,
            $duration,
            $start_time_utc,
            $end_time_utc,
        );

        // ✅ v1.5.4 - Agregar fingerprint_id a la fila (si fingerprint_enabled está activado)
        if ($privacy_config['fingerprint_enabled'] ?? true) {
            $row_data[] = $fingerprint_id;
        }

        // Solo agregar datos de aleatorización si hay randomización real
        if ($has_randomization) {
            $metadata = !empty($row->metadata) ? json_decode($row->metadata, true) : [];
            $assignment_form = $metadata['random_assignment']['form_id'] ?? '-';
            $assignment_seed = $metadata['random_assignment']['seed'] ?? '-';
            $assignment_type = $metadata['random_assignment']['type'] ?? '-';
            
            $row_data[] = $assignment_form;
            $row_data[] = $assignment_seed;
            $row_data[] = $assignment_type;
        }
        
        // Add metadata fields only if privacy config allows
        if ($privacy_config['ip_address']) {
            $row_data[] = $row->ip_address;
        }
        if ($privacy_config['device_type']) {
            $row_data[] = $row->device;
        }
        if ($privacy_config['browser']) {
            $row_data[] = $row->browser;
        }
        if ($privacy_config['os']) {
            $row_data[] = $row->os;
        }

        // === Agregar datos de Page Timings ===
        $metadata = !empty($row->metadata) ? json_decode($row->metadata, true) : [];
        foreach ($page_timing_keys as $page_key) {
            $page_data = $metadata['page_timings'][$page_key] ?? null;
            if ($page_data) {
                // Duration: usar rawDuration si existe, sino extraer de duration
                $duration = isset($page_data['rawDuration']) ? $page_data['rawDuration'] : (isset($page_data['duration']) ? floatval($page_data['duration']) : '');
                $row_data[] = $duration;
                // Timestamp
                $row_data[] = $page_data['timestamp'] ?? '';
            } else {
                $row_data[] = '';
                $row_data[] = '';
            }
        }

        // === Agregar datos de Field Timings ===
        foreach ($field_timing_keys as $field_name) {
            $field_data = $metadata['field_timings'][$field_name] ?? null;
            if ($field_data) {
                $row_data[] = $field_data['time_focused'] ?? '';
                $row_data[] = $field_data['interaction_count'] ?? '';
                $row_data[] = $field_data['focus_count'] ?? '';
            } else {
                $row_data[] = '';
                $row_data[] = '';
                $row_data[] = '';
            }
        }

        // === Agregar Total Duration ===
        if ($has_page_timings || $has_field_timings) {
            $total_duration = $metadata['page_timings']['total_duration'] ?? '';
            $row_data[] = $total_duration;
        }

        // Agregar respuestas en el orden de las preguntas (excluir campos internos)
        foreach ($all_questions as $question) {
            $row_data[] = isset($form_data[$question]) ? (is_array($form_data[$question]) ? json_encode($form_data[$question]) : $form_data[$question]) : '';
        }

        fputcsv($output, $row_data);
    }

    fclose($output);
    exit;
}

add_action('admin_init', function() {
    if (isset($_GET['page']) && $_GET['page'] === 'eipsi-results') {
        if (isset($_GET['action']) && $_GET['action'] === 'export_excel') {
            eipsi_export_to_excel();
        } elseif (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
            eipsi_export_to_csv();
        }
    }
    
    // Handle longitudinal export actions
    if (isset($_GET['page']) && $_GET['page'] === 'eipsi-export-longitudinal') {
        if (isset($_GET['action']) && $_GET['action'] === 'export_longitudinal_excel') {
            eipsi_export_longitudinal_to_excel();
        } elseif (isset($_GET['action']) && $_GET['action'] === 'export_longitudinal_csv') {
            eipsi_export_longitudinal_to_csv();
        }
    }
});

function eipsi_export_longitudinal_to_excel() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to perform this action.', 'eipsi-forms'));
    }
    
    $survey_id = isset($_GET['survey_id']) ? absint($_GET['survey_id']) : 0;
    if (!$survey_id) {
        wp_die(__('Invalid survey ID.', 'eipsi-forms'));
    }
    
    require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/services/class-export-service.php';
    $export_service = new EIPSI_Export_Service();
    
    $filters = array(
        'wave_index' => isset($_GET['wave_index']) ? sanitize_text_field($_GET['wave_index']) : 'all',
        'date_from' => isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : null,
        'date_to' => isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : null,
        'status' => isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'all',
    );
    
    $data = $export_service->export_longitudinal_data($survey_id, $filters);
    $filename = $export_service->export_to_excel($data, $survey_id);
    
    // Download the file
    $export_dir = EIPSI_FORMS_PLUGIN_DIR . 'exports';
    $file_path = $export_dir . '/' . $filename;
    
    if (file_exists($file_path)) {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($file_path));
        readfile($file_path);
        exit;
    } else {
        wp_die(__('Export file not found.', 'eipsi-forms'));
    }
}

function eipsi_export_longitudinal_to_csv() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to perform this action.', 'eipsi-forms'));
    }
    
    $survey_id = isset($_GET['survey_id']) ? absint($_GET['survey_id']) : 0;
    if (!$survey_id) {
        wp_die(__('Invalid survey ID.', 'eipsi-forms'));
    }
    
    require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/services/class-export-service.php';
    $export_service = new EIPSI_Export_Service();
    
    $filters = array(
        'wave_index' => isset($_GET['wave_index']) ? sanitize_text_field($_GET['wave_index']) : 'all',
        'date_from' => isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : null,
        'date_to' => isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : null,
        'status' => isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'all',
    );
    
    $data = $export_service->export_longitudinal_data($survey_id, $filters);
    $filename = $export_service->export_to_csv($data, $survey_id);
    
    // Download the file
    $export_dir = EIPSI_FORMS_PLUGIN_DIR . 'exports';
    $file_path = $export_dir . '/' . $filename;
    
    if (file_exists($file_path)) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($file_path));
        readfile($file_path);
        exit;
    } else {
        wp_die(__('Export file not found.', 'eipsi-forms'));
    }
}