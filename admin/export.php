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

function vas_export_to_excel() {
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
    $internal_fields = array('action', 'eipsi_nonce', 'start_time', 'end_time', 'form_start_time', 'form_end_time', 'nonce', 'form_action', 'ip_address', 'device', 'browser', 'os', 'screen_width', 'current_page', 'form_id');
    $all_questions = [];
    foreach ($results as $row) {
        $form_data = $row->form_responses ? json_decode($row->form_responses, true) : [];
        foreach ($form_data as $question => $answer) {
            if (!in_array($question, $all_questions) && !in_array($question, $internal_fields)) {
                $all_questions[] = $question;
            }
        }
    }
    
    $data = array();
    // Encabezados: nuevo formato con IDs + metadatos + timestamps + preguntas dinámicas
    // ONLY include metadata columns if privacy config allows
    $headers = array('Form ID', 'Participant ID', 'Form Name', 'Date', 'Time', 'Duration(s)', 'Start Time (UTC)', 'End Time (UTC)');
    
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
        
        $row_data = array(
            $form_id,
            $participant_id,
            $row->form_name,
            $date,
            $time,
            $duration,
            $start_time_utc,
            $end_time_utc
        );
        
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

function vas_export_to_csv() {
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
    $internal_fields = array('action', 'eipsi_nonce', 'start_time', 'end_time', 'form_start_time', 'form_end_time', 'nonce', 'form_action', 'ip_address', 'device', 'browser', 'os', 'screen_width', 'current_page', 'form_id');
    $all_questions = [];
    foreach ($results as $row) {
        $form_data = $row->form_responses ? json_decode($row->form_responses, true) : [];
        foreach ($form_data as $question => $answer) {
            if (!in_array($question, $all_questions) && !in_array($question, $internal_fields)) {
                $all_questions[] = $question;
            }
        }
    }
    
    header('Content-Type: text/csv; charset=utf-8');
    $form_suffix = isset($_GET['form_name']) ? '-' . sanitize_title($_GET['form_name']) : '';
    header('Content-Disposition: attachment; filename=form-responses' . $form_suffix . '-' . date('Y-m-d-H-i-s') . '.csv');
    
    $output = fopen('php://output', 'w');
    
    // Encabezados: nuevo formato con IDs + metadatos + timestamps + preguntas dinámicas
    // ONLY include metadata columns if privacy config allows
    $headers = array('Form ID', 'Participant ID', 'Form Name', 'Date', 'Time', 'Duration(s)', 'Start Time (UTC)', 'End Time (UTC)');
    
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
        
        $row_data = array(
            $form_id,
            $participant_id,
            $row->form_name,
            $date,
            $time,
            $duration,
            $start_time_utc,
            $end_time_utc
        );
        
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
            vas_export_to_excel();
        } elseif (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
            vas_export_to_csv();
        }
    }
});