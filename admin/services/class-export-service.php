<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * EIPSI_Export_Service
 *
 * Handles data export for both longitudinal (wave-based) studies and
 * participant rosters. Supports CSV (streamed) and Excel (.xlsx) output.
 *
 * @package EIPSI_Forms
 * @since   1.4.0
 * @updated 1.8.0 — Added participant export (CSV + Excel) with filters,
 *                   real preview endpoint, summary stats.
 */
class EIPSI_Export_Service {

    // =========================================================================
    // LONGITUDINAL EXPORT (wave responses)
    // =========================================================================

    /**
     * Main export method — fetches longitudinal (wave) data.
     *
     * @param int   $survey_id Study / survey ID.
     * @param array $filters   Optional filters (wave_index, date_from, date_to, status).
     * @return array Array of stdClass rows.
     */
    public function export_longitudinal_data($survey_id, $filters = array()) {
        $default_filters = array(
            'wave_index'          => null,
            'date_from'           => null,
            'date_to'             => null,
            'status'              => 'all',
            'include_fingerprint' => true,
        );

        $filters = array_merge($default_filters, $filters);
        return $this->fetch_longitudinal_data($survey_id, $filters);
    }

    /** @internal */
    private function fetch_longitudinal_data($survey_id, $filters) {
        global $wpdb;

        $query = "
            SELECT
                CASE
                    WHEN sp.is_anonymized = 1 THEN NULL
                    ELSE sp.id
                END as participant_id,
                sw.wave_index,
                sr.submitted_at,
                TIMESTAMPDIFF(SECOND, sr.created_at, sr.submitted_at) as response_time_seconds,
                sr.response_data,
                sr.user_fingerprint,
                CASE
                    WHEN sw.due_date < sr.submitted_at THEN 'Late'
                    WHEN sr.submitted_at IS NOT NULL THEN 'Completed'
                    ELSE 'Pending'
                END as status
            FROM {$wpdb->prefix}survey_participants sp
            JOIN {$wpdb->prefix}survey_waves sw ON sp.survey_id = sw.study_id
            LEFT JOIN {$wpdb->prefix}survey_responses sr ON sp.id = sr.participant_id AND sw.id = sr.wave_id
            WHERE sp.survey_id = %d
        ";

        $params = array($survey_id);

        if (!empty($filters['wave_index']) && $filters['wave_index'] !== 'all') {
            $query   .= ' AND sw.wave_index = %s';
            $params[] = $filters['wave_index'];
        }

        if (!empty($filters['date_from'])) {
            $query   .= ' AND sr.submitted_at >= %s';
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $query   .= ' AND sr.submitted_at <= %s';
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $status   = ucfirst(sanitize_text_field($filters['status']));
            $query   .= " AND
                CASE
                    WHEN sw.due_date < sr.submitted_at THEN 'Late'
                    WHEN sr.submitted_at IS NOT NULL THEN 'Completed'
                    ELSE 'Pending'
                END = %s";
            $params[] = $status;
        }

        $query .= ' ORDER BY sp.id, sw.wave_index';

        return $wpdb->get_results($wpdb->prepare($query, $params));
    }

    /** Export longitudinal data to .xlsx, returns filename. */
    public function export_to_excel($data, $survey_id) {
        if ( ! class_exists( '\Shuchkin\SimpleXLSXGen' ) ) {     require_once EIPSI_FORMS_PLUGIN_DIR . 'lib/SimpleXLSXGen.php'; }

        $headers = array(
            'Participant ID',
            'Wave',
            'Submitted At',
            'Response Time (min)',
            'Status',
            'User Fingerprint',
        );

        if (!empty($data)) {
            $response_data = json_decode($data[0]->response_data, true);
            if (is_array($response_data)) {
                foreach (array_keys($response_data) as $field) {
                    $headers[] = $field;
                }
            }
        }

        $xlsx_data   = array($headers);

        foreach ($data as $item) {
            $response_data = json_decode($item->response_data, true);
            if (!is_array($response_data)) {
                $response_data = array();
            }

            $row = array(
                $item->participant_id,
                $item->wave_index,
                $item->submitted_at,
                round($item->response_time_seconds / 60, 2),
                $item->status,
                $item->user_fingerprint,
            );

            foreach ($response_data as $value) {
                $row[] = $value;
            }

            $xlsx_data[] = $row;
        }

        $filename   = "longitudinal_export_{$survey_id}_" . date('Y-m-d_H-i-s') . '.xlsx';
        $export_dir = EIPSI_FORMS_PLUGIN_DIR . 'exports';
        if (!file_exists($export_dir)) {
            wp_mkdir_p($export_dir);
        }

        $xlsx = \Shuchkin\SimpleXLSXGen::fromArray($xlsx_data);
        $xlsx->saveAs($export_dir . '/' . $filename);

        return $filename;
    }

    /** Export longitudinal data to .csv, returns filename. */
    public function export_to_csv($data, $survey_id) {
        $filename   = "longitudinal_export_{$survey_id}_" . date('Y-m-d_H-i-s') . '.csv';
        $export_dir = EIPSI_FORMS_PLUGIN_DIR . 'exports';
        if (!file_exists($export_dir)) {
            wp_mkdir_p($export_dir);
        }

        $file_path = $export_dir . '/' . $filename;
        $file      = fopen($file_path, 'w');

        $headers = array(
            'Participant ID',
            'Wave',
            'Submitted At',
            'Response Time (min)',
            'Status',
            'User Fingerprint',
        );

        if (!empty($data)) {
            $response_data = json_decode($data[0]->response_data, true);
            if (is_array($response_data)) {
                foreach (array_keys($response_data) as $field) {
                    $headers[] = $field;
                }
            }
        }

        fputcsv($file, $headers);

        foreach ($data as $item) {
            $response_data = json_decode($item->response_data, true);
            if (!is_array($response_data)) {
                $response_data = array();
            }

            $row = array(
                $item->participant_id,
                $item->wave_index,
                $item->submitted_at,
                round($item->response_time_seconds / 60, 2),
                $item->status,
                $item->user_fingerprint,
            );

            foreach ($response_data as $value) {
                $row[] = $value;
            }

            fputcsv($file, $row);
        }

        fclose($file);
        return $filename;
    }

    // =========================================================================
    // PARTICIPANT EXPORT (roster + wave progress)
    // =========================================================================

    /**
     * Fetch participant rows for export, with optional filters.
     *
     * Returns one row per participant with their wave-completion summary columns.
     *
     * Supported filters:
     *   - status      : 'all' | 'active' | 'inactive'
     *   - wave_index  : 'all' | e.g. '1', '2'  (filters by whether that wave is completed)
     *   - search      : email / name free-text
     *   - date_from   : YYYY-MM-DD (participant created_at)
     *   - date_to     : YYYY-MM-DD
     *
     * @param int   $study_id
     * @param array $filters
     * @return array
     */
    public function fetch_participants_data($study_id, $filters = array()) {
        global $wpdb;

        $defaults = array(
            'status'     => 'all',
            'wave_index' => 'all',
            'search'     => '',
            'date_from'  => null,
            'date_to'    => null,
        );
        $filters = array_merge($defaults, $filters);

        // --- Build WHERE clauses ---
        $where_parts = array('p.survey_id = %d');
        $params      = array($study_id);

        if ($filters['status'] === 'active') {
            $where_parts[] = 'p.is_active = 1';
        } elseif ($filters['status'] === 'inactive') {
            $where_parts[] = 'p.is_active = 0';
        }

        if (!empty($filters['search'])) {
            $like          = '%' . $wpdb->esc_like($filters['search']) . '%';
            $where_parts[] = '(p.email LIKE %s OR p.first_name LIKE %s OR p.last_name LIKE %s)';
            $params[]      = $like;
            $params[]      = $like;
            $params[]      = $like;
        }

        if (!empty($filters['date_from'])) {
            $where_parts[] = 'p.created_at >= %s';
            $params[]      = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $where_parts[] = 'p.created_at <= %s';
            $params[]      = $filters['date_to'] . ' 23:59:59';
        }

        $where = 'WHERE ' . implode(' AND ', $where_parts);

        // --- Participant base query ---
        $participants = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    p.id,
                    p.email,
                    p.first_name,
                    p.last_name,
                    p.is_active,
                    p.created_at,
                    p.last_login_at
                 FROM {$wpdb->prefix}survey_participants p
                 {$where}
                 ORDER BY p.created_at DESC",
                $params
            )
        );

        if (empty($participants)) {
            return array();
        }

        // --- Get all waves for this study ---
        $waves = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, wave_index, name, due_date, status
                 FROM {$wpdb->prefix}survey_waves
                 WHERE study_id = %d
                 ORDER BY wave_index ASC",
                $study_id
            )
        );

        // Index waves by id
        $waves_by_id = array();
        foreach ($waves as $w) {
            $waves_by_id[$w->id] = $w;
        }

        // --- Get all assignments for participants in this study ---
        $participant_ids = array_column($participants, 'id');
        if (empty($participant_ids)) {
            return array();
        }

        $ids_placeholder = implode(',', array_fill(0, count($participant_ids), '%d'));
        $assignments     = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT participant_id, wave_id, status, submitted_at
                 FROM {$wpdb->prefix}survey_assignments
                 WHERE participant_id IN ({$ids_placeholder})
                 ORDER BY wave_id ASC",
                $participant_ids
            )
        );

        // Group assignments by participant
        $assignments_by_p = array();
        foreach ($assignments as $a) {
            $assignments_by_p[ $a->participant_id ][] = $a;
        }

        // --- NEW: Get metadata and responses from vas_form_results ---
        $wave_responses_by_p = array();
        $form_responses_by_p = array();

        if (!function_exists('get_privacy_config')) {
            require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/privacy-config.php';
        }

        $form_metadata = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT 
                    r.form_id, 
                    s.participant_id, 
                    r.form_responses, 
                    r.submitted_at, 
                    r.duration_seconds, 
                    r.user_fingerprint,
                    r.device, 
                    r.browser, 
                    r.os, 
                    r.screen_width, 
                    r.ip_address,
                    r.wave_index
                 FROM {$wpdb->prefix}vas_form_results r
                 LEFT JOIN {$wpdb->prefix}survey_sessions s ON s.token = r.session_id
                 LEFT JOIN {$wpdb->prefix}survey_participants p ON p.id = s.participant_id
                 WHERE p.survey_id = %d",
                $study_id
            )
        );

        $privacy_configs = array();

        foreach ($form_metadata as $res) {
            $p_id = $res->participant_id;
            $wi   = $res->wave_index;
            $fid  = $res->form_id;

            if (!$p_id) continue;

            // Fallback for wave_index if not present in results table
            if ($wi === null) {
                foreach ($waves as $w) {
                    if ($w->form_id == $fid) {
                        $wi = $w->wave_index;
                        break;
                    }
                }
            }

            if ($wi === null) continue;

            if (!isset($privacy_configs[$fid])) {
                $privacy_configs[$fid] = get_privacy_config($fid);
            }
            $privacy = $privacy_configs[$fid];

            $decoded = json_decode($res->form_responses, true);
            $wave_data = array(
                'form_responses'   => is_array($decoded) ? $decoded : array(),
                'submitted_at'     => $res->submitted_at,
                'duration_seconds' => $res->duration_seconds,
                'user_fingerprint' => $res->user_fingerprint,
            );

            // Conditional fields based on privacy config
            if (!empty($privacy['device_type']))  $wave_data['device'] = $res->device;
            if (!empty($privacy['browser']))      $wave_data['browser'] = $res->browser;
            if (!empty($privacy['os']))           $wave_data['os'] = $res->os;
            if (!empty($privacy['screen_width'])) $wave_data['screen_width'] = $res->screen_width;
            if (!empty($privacy['ip_address']))   $wave_data['ip_address'] = $res->ip_address;

            if (!isset($wave_responses_by_p[$p_id])) {
                $wave_responses_by_p[$p_id] = array();
                $form_responses_by_p[$p_id] = array();
            }
            $wave_responses_by_p[$p_id][$wi] = $wave_data;
            $form_responses_by_p[$p_id][$wi] = $wave_data['form_responses'];
        }

        // --- Build result rows ---
        $rows = array();
        foreach ($participants as $p) {
            $p_assignments = isset($assignments_by_p[$p->id]) ? $assignments_by_p[$p->id] : array();

            // Build wave status map: wave_index => status
            $wave_status_map = array();
            $submitted_count = 0;
            foreach ($p_assignments as $a) {
                if (isset($waves_by_id[$a->wave_id])) {
                    $wi = $waves_by_id[$a->wave_id]->wave_index;
                    $wave_status_map[$wi] = array(
                        'status'       => $a->status,
                        'submitted_at' => $a->submitted_at,
                    );
                    if ($a->status === 'submitted') {
                        $submitted_count++;
                    }
                }
            }

            // Filter by wave_index if requested (only keep participant if
            // they have an assignment in that wave).
            if ($filters['wave_index'] !== 'all') {
                $wi = $filters['wave_index'];
                if (!isset($wave_status_map[$wi])) {
                    continue; // not assigned to this wave, skip
                }
            }

            $total_waves        = count($waves);
            $completion_percent = ($total_waves > 0)
                ? round(($submitted_count / $total_waves) * 100)
                : 0;

            $row = array(
                'id'                 => (int) $p->id,
                'email'              => $p->email,
                'first_name'         => $p->first_name,
                'last_name'          => $p->last_name,
                'full_name'          => trim($p->first_name . ' ' . $p->last_name),
                'is_active'          => (bool) $p->is_active,
                'created_at'         => $p->created_at,
                'last_login_at'      => $p->last_login_at,
                'waves_assigned'     => count($p_assignments),
                'waves_submitted'    => $submitted_count,
                'waves_total'        => $total_waves,
                'completion_percent' => $completion_percent,
                'wave_statuses'      => $wave_status_map,
                'form_responses'     => isset($form_responses_by_p[$p->id]) ? $form_responses_by_p[$p->id] : array(),
                'wave_responses'     => isset($wave_responses_by_p[$p->id]) ? $wave_responses_by_p[$p->id] : array(),
            );

            $rows[] = $row;
        }

        return array(
            'rows'  => $rows,
            'waves' => $waves,
        );
    }

    /**
     * Build the flat header array for participant export.
     *
     * @param array $rows  List of participant rows (optional, for extracting form_response keys).
     * @param array $waves List of wave objects.
     * @return string[]
     */
    private function build_participant_headers($rows, $waves) {
        $headers = array(
            'ID',
            'Email',
            'Nombre',
            'Apellido',
            'Estado',
            'Registrado en',
            'Último acceso',
            'Ondas asignadas',
            'Ondas completadas',
            'Progreso (%)',
        );

        // Extract response keys grouped by wave_index
        $response_keys_by_wave = $this->extract_response_keys_by_wave($rows);

        foreach ($waves as $wave) {
            $label = 'T' . $wave->wave_index . ' - ' . $wave->name;
            $headers[] = $label . ' (Estado)';
            $headers[] = $label . ' (Completado)';

            // Add dynamic headers for form_response fields
            $wave_index = $wave->wave_index;
            if (isset($response_keys_by_wave[$wave_index])) {
                foreach ($response_keys_by_wave[$wave_index] as $field_key) {
                    $headers[] = 'T' . $wave_index . ' - ' . $field_key;
                }
            }
        }

        return $headers;
    }

    /**
     * Extract unique form_response keys grouped by wave_index.
     *
     * @param array $rows Participant rows with form_responses.
     * @return array Keys indexed by wave_index.
     */
    private function extract_response_keys_by_wave($rows) {
        $response_keys_by_wave = array();

        foreach ($rows as $row) {
            if (!empty($row['form_responses']) && is_array($row['form_responses'])) {
                foreach ($row['form_responses'] as $wave_index => $responses) {
                    if (is_array($responses)) {
                        if (!isset($response_keys_by_wave[$wave_index])) {
                            $response_keys_by_wave[$wave_index] = array();
                        }
                        $response_keys_by_wave[$wave_index] = array_unique(
                            array_merge($response_keys_by_wave[$wave_index], array_keys($responses))
                        );
                    }
                }
            }
        }

        // Sort keys per wave for consistency
        foreach ($response_keys_by_wave as $wi => $keys) {
            sort($response_keys_by_wave[$wi]);
        }

        return $response_keys_by_wave;
    }

    /**
     * Build a flat data row for one participant.
     *
     * @param array $row                    Participant row from fetch_participants_data().
     * @param array $waves                  Wave list.
     * @param array $response_keys_by_wave  Optional: keys per wave_index (from build_participant_headers).
     * @return array
     */
    private function build_participant_row($row, $waves, $response_keys_by_wave = array()) {
        // =====================================================================
        // 1. Datos base del participante (10 columnas)
        // =====================================================================
        $data = array(
            $row['id'],
            $row['email'],
            $row['first_name'],
            $row['last_name'],
            $row['is_active'] ? 'Activo' : 'Inactivo',
            $row['created_at'] ? date('Y-m-d H:i', strtotime($row['created_at'])) : '',
            $row['last_login_at'] ? date('Y-m-d H:i', strtotime($row['last_login_at'])) : '',
            $row['waves_assigned'],
            $row['waves_submitted'],
            $row['completion_percent'],
        );

        // =====================================================================
        // 2. Datos de waves: estado, completado, y form_responses
        // =====================================================================
        $form_responses = isset($row['form_responses']) ? $row['form_responses'] : array();
        
        // Mapeo de status a etiquetas legibles
        $status_map = array(
            'submitted'   => 'Completado',
            'in_progress' => 'En progreso',
            'pending'     => 'Pendiente',
            'expired'     => 'Expirado',
        );

        foreach ($waves as $wave) {
            $wi = $wave->wave_index;
            $wave_info = isset($row['wave_statuses'][$wi]) ? $row['wave_statuses'][$wi] : null;

            // 2a. Estado del wave
            if ($wave_info) {
                $data[] = isset($status_map[$wave_info['status']]) ? $status_map[$wave_info['status']] : $wave_info['status'];
            } else {
                $data[] = 'No asignado';
            }

            // 2b. Fecha de completado
            $data[] = ($wave_info && $wave_info['submitted_at']) 
                ? date('Y-m-d H:i', strtotime($wave_info['submitted_at'])) 
                : '';

            // 2c. Valores de form_responses para este wave
            // Iterar sobre las claves del JSON en el mismo orden que los headers
            if (!empty($response_keys_by_wave[$wi])) {
                $wave_responses = isset($form_responses[$wi]) ? $form_responses[$wi] : array();
                
                foreach ($response_keys_by_wave[$wi] as $field_key) {
                    $value = '';
                    
                    // $row['form_responses'][wave_index][field_name] tiene los datos
                    if (isset($wave_responses[$field_key])) {
                        $val = $wave_responses[$field_key];
                        
                        // Serializar arrays/objetos para CSV/Excel
                        if (is_array($val) || is_object($val)) {
                            $value = json_encode($val, JSON_UNESCAPED_UNICODE);
                        } else {
                            $value = $val;
                        }
                    }
                    // Si no hay respuesta, $value queda como string vacío
                    
                    $data[] = $value;
                }
            } else {
                // No hay claves definidas para este wave - añadir celdas vacías
                // (El número de celdas depende de los headers, se llena con vacíos)
            }
        }

        return $data;
    }

    /**
     * Export participant roster to Excel, returns filename.
     *
     * @param int   $study_id
     * @param array $filters
     * @return string Filename (in exports/ directory)
     */
    public function export_participants_to_excel($study_id, $filters = array()) {
        if ( ! class_exists( '\Shuchkin\SimpleXLSXGen' ) ) {     require_once EIPSI_FORMS_PLUGIN_DIR . 'lib/SimpleXLSXGen.php'; }

        $result = $this->fetch_participants_data($study_id, $filters);
        $rows   = isset($result['rows'])  ? $result['rows']  : array();
        $waves  = isset($result['waves']) ? $result['waves'] : array();

        $response_keys_by_wave = $this->extract_response_keys_by_wave($rows);
        $headers              = $this->build_participant_headers($rows, $waves);
        $xlsx_data            = array($headers);

        foreach ($rows as $row) {
            $xlsx_data[] = $this->build_participant_row($row, $waves, $response_keys_by_wave);
        }

        // Summary sheet row
        $xlsx_data[] = array();
        $xlsx_data[] = array('Total participantes', count($rows));
        $active      = count(array_filter($rows, function ($r) { return $r['is_active']; }));
        $xlsx_data[] = array('Activos', $active);
        $xlsx_data[] = array('Inactivos', count($rows) - $active);
        $xlsx_data[] = array('Exportado el', date('Y-m-d H:i:s'));

        $filename   = 'participantes-' . $study_id . '-' . date('Y-m-d_H-i-s') . '.xlsx';
        $export_dir = EIPSI_FORMS_PLUGIN_DIR . 'exports';
        if (!file_exists($export_dir)) {
            wp_mkdir_p($export_dir);
        }

        $xlsx = \Shuchkin\SimpleXLSXGen::fromArray($xlsx_data);
        $xlsx->saveAs($export_dir . '/' . $filename);

        return $filename;
    }

    /**
     * Stream participant CSV directly to an open file handle (e.g. php://output).
     *
     * @param int      $study_id
     * @param array    $filters
     * @param resource $output   Open file handle.
     */
    public function stream_participants_csv($study_id, $filters, $output) {
        $result = $this->fetch_participants_data($study_id, $filters);
        $rows   = isset($result['rows'])  ? $result['rows']  : array();
        $waves  = isset($result['waves']) ? $result['waves'] : array();

        $response_keys_by_wave = $this->extract_response_keys_by_wave($rows);
        fputcsv($output, $this->build_participant_headers($rows, $waves));

        foreach ($rows as $row) {
            fputcsv($output, $this->build_participant_row($row, $waves, $response_keys_by_wave));
        }
    }

    /**
     * Return a lightweight preview (first N rows) of the participant export.
     *
     * Used by the AJAX preview endpoint in the UI.
     *
     * @param int   $study_id
     * @param array $filters
     * @param int   $limit    Max rows to return (default 10).
     * @return array { headers: string[], rows: array[], total: int }
     */
    public function get_participants_preview($study_id, $filters = array(), $limit = 10) {
        $format = isset($filters['format']) && in_array($filters['format'], array('wide', 'long')) 
            ? $filters['format'] 
            : 'wide';
        
        $result = $this->fetch_participants_data($study_id, $filters);
        $rows   = isset($result['rows'])  ? $result['rows']  : array();
        $waves  = isset($result['waves']) ? $result['waves'] : array();

        $response_keys_by_wave = $this->extract_response_keys_by_wave($rows);
        
        if ($format === 'wide') {
            $headers     = $this->build_participant_headers($rows, $waves);
            $preview_rows = array();
            foreach (array_slice($rows, 0, $limit) as $row) {
                $preview_rows[] = $this->build_participant_row($row, $waves, $response_keys_by_wave);
            }
        } else {
            // Long format: one row per participant per wave
            $all_unique_field_names = array();
            foreach ($response_keys_by_wave as $wi => $keys) {
                $all_unique_field_names = array_unique(array_merge($all_unique_field_names, $keys));
            }
            sort($all_unique_field_names);
            
            $headers      = $this->build_participants_long_headers($all_unique_field_names);
            $preview_rows = array();
            
            $rows_limited = array_slice($rows, 0, $limit);
            foreach ($rows_limited as $row) {
                foreach ($waves as $wave) {
                    $preview_rows[] = $this->build_participants_long_row($row, $wave, $all_unique_field_names);
                }
            }
        }

        return array(
            'headers' => $headers,
            'rows'    => $preview_rows,
            'total'   => ($format === 'long' && !empty($waves)) ? count($rows) * count($waves) : count($rows),
            'columns' => count($headers),
        );
    }

    // =========================================================================
    // STATISTICS
    // =========================================================================

    /**
     * Get export statistics for a survey.
     *
     * @param int   $survey_id
     * @param array $filters   (unused – kept for BC)
     * @return array
     */
    public function get_export_statistics($survey_id, $filters = array()) {
        global $wpdb;

        $stats = array(
            'total_participants'  => 0,
            'active_participants' => 0,
            'completed_all_waves' => 0,
            'completion_rates'    => array(),
            'avg_response_times'  => array(),
        );

        // Total + active participants
        $stats['total_participants'] = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}survey_participants WHERE survey_id = %d",
            $survey_id
        ));

        $stats['active_participants'] = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}survey_participants WHERE survey_id = %d AND is_active = 1",
            $survey_id
        ));

        // Waves for this study
        $waves = $wpdb->get_results($wpdb->prepare(
            "SELECT id, wave_index, name
             FROM {$wpdb->prefix}survey_waves
             WHERE study_id = %d
             ORDER BY wave_index ASC",
            $survey_id
        ));

        // Completion rates per wave
        foreach ($waves as $wave) {
            $completed = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT participant_id)
                 FROM {$wpdb->prefix}survey_assignments
                 WHERE wave_id = %d AND status = 'submitted'",
                $wave->id
            ));

            $total = $stats['total_participants'];
            $rate  = ($total > 0) ? round(($completed / $total) * 100, 1) : 0;

            $stats['completion_rates']['T' . $wave->wave_index] = array(
                'wave_name' => $wave->name,
                'completed' => $completed,
                'total'     => $total,
                'rate'      => $rate,
            );

            // Average response time (seconds)
            $avg_time = (float) $wpdb->get_var($wpdb->prepare(
                "SELECT AVG(TIMESTAMPDIFF(SECOND, created_at, submitted_at))
                 FROM {$wpdb->prefix}survey_assignments
                 WHERE wave_id = %d AND status = 'submitted' AND submitted_at IS NOT NULL",
                $wave->id
            ));

            $stats['avg_response_times']['T' . $wave->wave_index] = array(
                'seconds' => (int) $avg_time,
                'minutes' => round($avg_time / 60, 1),
            );
        }

        // Completed ALL waves
        $wave_count = count($waves);
        if ($wave_count > 0) {
            $stats['completed_all_waves'] = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT p.id)
                 FROM {$wpdb->prefix}survey_participants p
                 WHERE p.survey_id = %d AND p.is_active = 1
                 AND (
                     SELECT COUNT(DISTINCT a.wave_id)
                     FROM {$wpdb->prefix}survey_assignments a
                     WHERE a.participant_id = p.id AND a.status = 'submitted'
                 ) = %d",
                $survey_id,
                $wave_count
            ));
        }

        return $stats;
    }

    // =========================================================================
    // HELPER — available surveys / waves
    // =========================================================================

    /** Get list of active surveys for dropdown. */
    public function get_available_surveys() {
        global $wpdb;

        return $wpdb->get_results(
            "SELECT id, title, description
             FROM {$wpdb->prefix}survey_surveys
             WHERE is_active = 1
             ORDER BY created_at DESC"
        );
    }

    /** Get wave indices for a survey. */
    public function get_survey_waves($survey_id) {
        global $wpdb;

        $waves = $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT wave_index
             FROM {$wpdb->prefix}survey_waves
             WHERE survey_id = %d
             ORDER BY wave_index",
            $survey_id
        ));

        return array_column($waves, 'wave_index');
    }

    // =========================================================================
    // WIDE FORMAT EXPORT (one row per participant)
    // =========================================================================

    /**
     * Build the wide header array for participant export.
     *
     * Estructura Wide:
     * - Columnas base: ID, Email, Estado, Registrado, Último acceso, Ondas asignadas, Ondas completadas, Progreso (%)
     * - Por cada wave: T{n}_submitted_at, T{n}_duration_seconds, T{n}_fingerprint_id, T{n}_device, T{n}_{field_name}
     * - SIN columnas Nombre ni Apellido
     *
     * @param array $rows  List of participant rows.
     * @param array $waves List of wave objects.
     * @return string[]
     */
    private function build_participants_wide_headers($rows, $waves) {
        $headers = array(
            'ID',
            'Email',
            'Estado',
            'Registrado',
            'Último acceso',
            'Ondas asignadas',
            'Ondas completadas',
            'Progreso (%)',
        );

        // Extract response keys grouped by wave_index
        $response_keys_by_wave = $this->extract_response_keys_by_wave($rows);

        foreach ($waves as $wave) {
            $prefix = 'T' . $wave->wave_index;
            $headers[] = $prefix . '_submitted_at';
            $headers[] = $prefix . '_duration_seconds';
            $headers[] = $prefix . '_fingerprint_id';
            $headers[] = $prefix . '_device';

            // Add dynamic headers for form_response fields
            $wave_index = $wave->wave_index;
            if (isset($response_keys_by_wave[$wave_index])) {
                foreach ($response_keys_by_wave[$wave_index] as $field_key) {
                    $headers[] = $prefix . '_' . $field_key;
                }
            }
        }

        return $headers;
    }

    /**
     * Build a wide data row for one participant.
     *
     * @param array $row                    Participant row from fetch_participants_data().
     * @param array $waves                  Wave list.
     * @param array $response_keys_by_wave  Optional: keys per wave_index.
     * @return array
     */
    private function build_participants_wide_row($row, $waves, $response_keys_by_wave = array()) {
        // =====================================================================
        // 1. Datos base del participante (SIN Nombre ni Apellido)
        // =====================================================================
        $data = array(
            $row['id'],
            $row['email'],
            $row['is_active'] ? 'Activo' : 'Inactivo',
            $row['created_at'] ? date('Y-m-d H:i', strtotime($row['created_at'])) : '',
            $row['last_login_at'] ? date('Y-m-d H:i', strtotime($row['last_login_at'])) : '',
            $row['waves_assigned'],
            $row['waves_submitted'],
            $row['completion_percent'],
        );

        // =====================================================================
        // 2. Datos de waves en formato Wide
        // =====================================================================
        $wave_responses = isset($row['wave_responses']) ? $row['wave_responses'] : array();
        $form_responses = isset($row['form_responses']) ? $row['form_responses'] : array();

        foreach ($waves as $wave) {
            $wi = $wave->wave_index;

            // 2a. submitted_at
            $submitted_at = isset($wave_responses[$wi]['submitted_at'])
                ? date('Y-m-d H:i:s', strtotime($wave_responses[$wi]['submitted_at']))
                : '';
            $data[] = $submitted_at;

            // 2b. duration_seconds
            $duration = isset($wave_responses[$wi]['duration_seconds'])
                ? (int) $wave_responses[$wi]['duration_seconds']
                : '';
            $data[] = $duration;

            // 2c. fingerprint_id
            $fingerprint = isset($wave_responses[$wi]['user_fingerprint'])
                ? $wave_responses[$wi]['user_fingerprint']
                : '';
            $data[] = $fingerprint;

            // 2d. device
            $device = isset($wave_responses[$wi]['device'])
                ? $wave_responses[$wi]['device']
                : '';
            $data[] = $device;

            // 2e. Valores de form_responses para este wave
            if (!empty($response_keys_by_wave[$wi])) {
                $wave_form_responses = isset($form_responses[$wi]) ? $form_responses[$wi] : array();

                foreach ($response_keys_by_wave[$wi] as $field_key) {
                    $value = '';

                    if (isset($wave_form_responses[$field_key])) {
                        $val = $wave_form_responses[$field_key];

                        // Serializar arrays/objetos para CSV/Excel
                        if (is_array($val) || is_object($val)) {
                            $value = json_encode($val, JSON_UNESCAPED_UNICODE);
                        } else {
                            $value = $val;
                        }
                    }

                    $data[] = $value;
                }
            }
        }

        return $data;
    }

    /**
     * Export participant roster to Excel in WIDE format, returns filename.
     *
     * Estructura Wide: una fila por participante con columnas por cada wave.
     *
     * @param int   $study_id
     * @param array $filters
     * @return string Filename (in exports/ directory)
     */
    public function export_participants_wide_excel($study_id, $filters = array()) {
        if ( ! class_exists( '\Shuchkin\SimpleXLSXGen' ) ) {     require_once EIPSI_FORMS_PLUGIN_DIR . 'lib/SimpleXLSXGen.php'; }

        $result = $this->fetch_participants_data($study_id, $filters);
        $rows   = isset($result['rows'])  ? $result['rows']  : array();
        $waves  = isset($result['waves']) ? $result['waves'] : array();

        $response_keys_by_wave = $this->extract_response_keys_by_wave($rows);
        $headers              = $this->build_participants_wide_headers($rows, $waves);
        $xlsx_data            = array($headers);

        foreach ($rows as $row) {
            $xlsx_data[] = $this->build_participants_wide_row($row, $waves, $response_keys_by_wave);
        }

        // Summary sheet row
        $xlsx_data[] = array();
        $xlsx_data[] = array('Total participantes', count($rows));
        $active      = count(array_filter($rows, function ($r) { return $r['is_active']; }));
        $xlsx_data[] = array('Activos', $active);
        $xlsx_data[] = array('Inactivos', count($rows) - $active);
        $xlsx_data[] = array('Formato', 'Wide');
        $xlsx_data[] = array('Exportado el', date('Y-m-d H:i:s'));

        $filename   = 'participantes-wide-' . $study_id . '-' . date('Y-m-d_H-i-s') . '.xlsx';
        $export_dir = EIPSI_FORMS_PLUGIN_DIR . 'exports';
        if (!file_exists($export_dir)) {
            wp_mkdir_p($export_dir);
        }

        $xlsx = \Shuchkin\SimpleXLSXGen::fromArray($xlsx_data);
        $xlsx->saveAs($export_dir . '/' . $filename);

        return $filename;
    }

    /**
     * Export participant roster to CSV in WIDE format.
     *
     * @param int      $study_id
     * @param array    $filters
     * @param resource $output   Open file handle (e.g., php://output).
     */
    public function export_participants_wide_csv($study_id, $filters, $output) {
        $result = $this->fetch_participants_data($study_id, $filters);
        $rows   = isset($result['rows'])  ? $result['rows']  : array();
        $waves  = isset($result['waves']) ? $result['waves'] : array();

        $response_keys_by_wave = $this->extract_response_keys_by_wave($rows);
        fputcsv($output, $this->build_participants_wide_headers($rows, $waves));

        foreach ($rows as $row) {
            fputcsv($output, $this->build_participants_wide_row($row, $waves, $response_keys_by_wave));
        }
    }

    /**
     * Return a lightweight preview (first N rows) of the participant WIDE export.
     *
     * Used by the AJAX preview endpoint in the UI.
     *
     * @param int   $study_id
     * @param array $filters
     * @param int   $limit    Max rows to return (default 10).
     * @return array { headers: string[], rows: array[], total: int }
     */
    public function get_participants_wide_preview($study_id, $filters = array(), $limit = 10) {
        $result = $this->fetch_participants_data($study_id, $filters);
        $rows   = isset($result['rows'])  ? $result['rows']  : array();
        $waves  = isset($result['waves']) ? $result['waves'] : array();

        $response_keys_by_wave = $this->extract_response_keys_by_wave($rows);
        $headers              = $this->build_participants_wide_headers($rows, $waves);
        $preview_rows         = array();

        foreach (array_slice($rows, 0, $limit) as $row) {
            $preview_rows[] = $this->build_participants_wide_row($row, $waves, $response_keys_by_wave);
        }

        return array(
            'headers' => $headers,
            'rows'    => $preview_rows,
            'total'   => count($rows),
            'columns' => count($headers),
            'format'  => 'wide',
        );
    }

    // =========================================================================
    // LONG FORMAT EXPORT (one row per participant × wave)
    // =========================================================================

    /**
     * Build the LONG header array for participant export.
     *
     * Estructura Long: una fila por participante × toma.
     * - Columnas base: ID, Email, Estado, Registrado, Toma (wave_index), Wave_name
     * - Por cada toma: submitted_at, duration_seconds, fingerprint_id, device, y un campo por cada field_name único
     * - Si no completó esa toma, las columnas de respuesta van vacías
     *
     * @param array $all_unique_field_names List of ALL unique field_names across ALL forms in the study.
     * @return string[]
     */
    private function build_participants_long_headers($all_unique_field_names) {
        $headers = array(
            'ID',
            'Email',
            'Estado',
            'Registrado',
            'Toma',
            'Wave_name',
            'submitted_at',
            'duration_seconds',
            'fingerprint_id',
            'device',
        );

        // Add all unique field_name columns
        foreach ($all_unique_field_names as $field_name) {
            $headers[] = $field_name;
        }

        return $headers;
    }

    /**
     * Extract all unique field_names from ALL forms in the study.
     *
     * This makes a FIRST PASS to collect all field_names from vas_form_results
     * across all participants and waves for the study.
     *
     * @param int $study_id
     * @return array List of unique field_name strings.
     */
    private function get_all_unique_field_names($study_id) {
        global $wpdb;

        // Get all form_responses JSON from vas_form_results for this study
        $results = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT r.form_responses
                 FROM {$wpdb->prefix}vas_form_results r
                 LEFT JOIN {$wpdb->prefix}survey_sessions s ON s.token = r.session_id
                 LEFT JOIN {$wpdb->prefix}survey_participants p ON p.id = s.participant_id
                 WHERE p.survey_id = %d
                 AND r.form_responses IS NOT NULL
                 AND r.form_responses != ''",
                $study_id
            )
        );

        $all_fields = array();

        foreach ($results as $json) {
            $decoded = json_decode($json, true);
            if (is_array($decoded)) {
                $all_fields = array_merge($all_fields, array_keys($decoded));
            }
        }

        // Return unique sorted field names
        $unique_fields = array_unique($all_fields);
        sort($unique_fields);

        return $unique_fields;
    }

    /**
     * Build a LONG data row for one participant × wave combination.
     *
     * @param array  $participant_row        Participant row from fetch_participants_data().
     * @param object $wave                   Wave object.
     * @param array  $all_unique_field_names List of all unique field_names across the study.
     * @return array
     */
    private function build_participants_long_row($participant_row, $wave, $all_unique_field_names) {
        $wi = $wave->wave_index;

        // =====================================================================
        // 1. Datos base del participante
        // =====================================================================
        $data = array(
            $participant_row['id'],
            $participant_row['email'],
            $participant_row['is_active'] ? 'Activo' : 'Inactivo',
            $participant_row['created_at'] ? date('Y-m-d H:i', strtotime($participant_row['created_at'])) : '',
            $wi, // Toma (wave_index)
            $wave->name, // Wave_name
        );

        // =====================================================================
        // 2. Datos de la toma específica (submitted_at, duration, fingerprint, device)
        // =====================================================================
        $wave_responses = isset($participant_row['wave_responses'][$wi])
            ? $participant_row['wave_responses'][$wi]
            : array();

        $form_responses = isset($participant_row['form_responses'][$wi])
            ? $participant_row['form_responses'][$wi]
            : array();

        // 2a. submitted_at
        $data[] = isset($wave_responses['submitted_at'])
            ? date('Y-m-d H:i:s', strtotime($wave_responses['submitted_at']))
            : '';

        // 2b. duration_seconds
        $data[] = isset($wave_responses['duration_seconds'])
            ? (int) $wave_responses['duration_seconds']
            : '';

        // 2c. fingerprint_id
        $data[] = isset($wave_responses['user_fingerprint'])
            ? $wave_responses['user_fingerprint']
            : '';

        // 2d. device
        $data[] = isset($wave_responses['device'])
            ? $wave_responses['device']
            : '';

        // =====================================================================
        // 3. Valores de form_responses para esta toma (usando ALL unique field_names)
        // =====================================================================
        // Para cada field_name único en el estudio, buscar si existe en esta toma
        foreach ($all_unique_field_names as $field_name) {
            $value = '';

            if (isset($form_responses[$field_name])) {
                $val = $form_responses[$field_name];

                // Serializar arrays/objetos para CSV/Excel
                if (is_array($val) || is_object($val)) {
                    $value = json_encode($val, JSON_UNESCAPED_UNICODE);
                } else {
                    $value = $val;
                }
            }

            $data[] = $value;
        }

        return $data;
    }

    /**
     * Export participant roster to Excel in LONG format, returns filename.
     *
     * Estructura Long: una fila por participante × toma.
     *
     * @param int   $study_id
     * @param array $filters
     * @return string Filename (in exports/ directory)
     */
    public function export_participants_long_excel($study_id, $filters = array()) {
        if ( ! class_exists( '\Shuchkin\SimpleXLSXGen' ) ) {     require_once EIPSI_FORMS_PLUGIN_DIR . 'lib/SimpleXLSXGen.php'; }

        $result = $this->fetch_participants_data($study_id, $filters);
        $rows   = isset($result['rows'])  ? $result['rows']  : array();
        $waves  = isset($result['waves']) ? $result['waves'] : array();

        // FIRST PASS: Collect all unique field_names from the entire study
        $all_unique_field_names = $this->get_all_unique_field_names($study_id);

        // Build headers
        $headers = $this->build_participants_long_headers($all_unique_field_names);
        $xlsx_data = array($headers);

        // Build rows: one row per participant × wave combination
        foreach ($rows as $participant_row) {
            foreach ($waves as $wave) {
                $xlsx_data[] = $this->build_participants_long_row(
                    $participant_row,
                    $wave,
                    $all_unique_field_names
                );
            }
        }

        // Summary sheet row
        $xlsx_data[] = array();
        $xlsx_data[] = array('Total filas (participantes × tomas)', count($xlsx_data) - 2);
        $xlsx_data[] = array('Total participantes', count($rows));
        $xlsx_data[] = array('Total tomas', count($waves));
        $active = count(array_filter($rows, function ($r) { return $r['is_active']; }));
        $xlsx_data[] = array('Activos', $active);
        $xlsx_data[] = array('Inactivos', count($rows) - $active);
        $xlsx_data[] = array('Formato', 'Long');
        $xlsx_data[] = array('Exportado el', date('Y-m-d H:i:s'));

        $filename   = 'participantes-long-' . $study_id . '-' . date('Y-m-d_H-i-s') . '.xlsx';
        $export_dir = EIPSI_FORMS_PLUGIN_DIR . 'exports';
        if (!file_exists($export_dir)) {
            wp_mkdir_p($export_dir);
        }

        $xlsx = \Shuchkin\SimpleXLSXGen::fromArray($xlsx_data);
        $xlsx->saveAs($export_dir . '/' . $filename);

        return $filename;
    }

    /**
     * Export participant roster to CSV in LONG format.
     *
     * Estructura Long: una fila por participante × toma.
     *
     * @param int      $study_id
     * @param array    $filters
     * @param resource $output   Open file handle (e.g., php://output).
     */
    public function export_participants_long_csv($study_id, $filters, $output) {
        $result = $this->fetch_participants_data($study_id, $filters);
        $rows   = isset($result['rows'])  ? $result['rows']  : array();
        $waves  = isset($result['waves']) ? $result['waves'] : array();

        // FIRST PASS: Collect all unique field_names from the entire study
        $all_unique_field_names = $this->get_all_unique_field_names($study_id);

        // Write headers
        fputcsv($output, $this->build_participants_long_headers($all_unique_field_names));

        // Write rows: one row per participant × wave combination
        foreach ($rows as $participant_row) {
            foreach ($waves as $wave) {
                fputcsv($output, $this->build_participants_long_row(
                    $participant_row,
                    $wave,
                    $all_unique_field_names
                ));
            }
        }
    }

    /**
     * Return a lightweight preview (first N rows) of the participant LONG export.
     *
     * Used by the AJAX preview endpoint in the UI.
     *
     * @param int   $study_id
     * @param array $filters
     * @param int   $limit    Max rows to return (default 10).
     * @return array { headers: string[], rows: array[], total: int }
     */
    public function get_participants_long_preview($study_id, $filters = array(), $limit = 10) {
        $result = $this->fetch_participants_data($study_id, $filters);
        $rows   = isset($result['rows'])  ? $result['rows']  : array();
        $waves  = isset($result['waves']) ? $result['waves'] : array();

        // FIRST PASS: Collect all unique field_names from the entire study
        $all_unique_field_names = $this->get_all_unique_field_names($study_id);

        // Build headers
        $headers = $this->build_participants_long_headers($all_unique_field_names);

        // Build preview rows (one per participant × wave, up to limit)
        $preview_rows = array();
        $count = 0;

        foreach ($rows as $participant_row) {
            if ($count >= $limit) {
                break;
            }

            foreach ($waves as $wave) {
                if ($count >= $limit) {
                    break;
                }

                $preview_rows[] = $this->build_participants_long_row(
                    $participant_row,
                    $wave,
                    $all_unique_field_names
                );

                $count++;
            }
        }

        // Calculate total rows (participants × waves)
        $total_rows = count($rows) * count($waves);

        return array(
            'headers' => $headers,
            'rows'    => $preview_rows,
            'total'   => $total_rows,
            'columns' => count($headers),
            'format'  => 'long',
            'total_participants' => count($rows),
            'total_waves' => count($waves),
        );
    }
}
