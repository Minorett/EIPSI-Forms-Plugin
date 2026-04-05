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

        // --- Step 1: Get participants from study ---
        $participants = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, email, is_active, created_at, last_login_at
                 FROM {$wpdb->prefix}survey_participants
                 WHERE survey_id = %d
                 ORDER BY created_at DESC",
                $study_id
            )
        );

        if (empty($participants)) {
            return array('rows' => array(), 'waves' => array());
        }

        // --- Step 2: Get waves for this study ---
        $waves = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, wave_index, name, form_id
                 FROM {$wpdb->prefix}survey_waves
                 WHERE study_id = %d
                 ORDER BY wave_index ASC",
                $study_id
            )
        );

        // Index waves by wave_index and by form_id
        $waves_by_index = array();
        $waves_by_form_id = array();
        foreach ($waves as $w) {
            $waves_by_index[$w->wave_index] = $w;
            $waves_by_form_id[$w->form_id] = $w;
        }

        // --- Step 3: Get longitudinal submissions from vas_form_results ---
        $submissions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT participant_id, wave_index, form_id, form_responses,
                        submitted_at, duration_seconds, user_fingerprint,
                        device, browser, os, screen_width, ip_address
                 FROM {$wpdb->prefix}vas_form_results
                 WHERE survey_id = %d
                 AND wave_index IS NOT NULL
                 ORDER BY submitted_at ASC",
                $study_id
            )
        );

        // Load privacy configs
        if (!function_exists('get_privacy_config')) {
            require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/privacy-config.php';
        }

        // Process submissions and extract emails
        $submissions_by_participant = array();
        $privacy_configs = array();
        
        foreach ($submissions as $sub) {
            $participant_fingerprint = $sub->participant_id;
            $wave_index = $sub->wave_index;
            $form_id = $sub->form_id;

            // Get privacy config for this form
            if (!isset($privacy_configs[$form_id])) {
                $privacy_configs[$form_id] = get_privacy_config($form_id);
            }
            $privacy = $privacy_configs[$form_id];

            // Decode form responses and extract email
            $decoded_responses = json_decode($sub->form_responses, true);
            $email_from_form = null;
            
            if (is_array($decoded_responses)) {
                // Look for email in form responses
                $email_from_form = $decoded_responses['email'] ?? 
                                 $decoded_responses['correo_electronico'] ?? 
                                 $decoded_responses['correo'] ?? null;
            }

            // Build submission data
            $submission_data = array(
                'participant_fingerprint' => $participant_fingerprint,
                'email_from_form' => $email_from_form,
                'form_responses' => is_array($decoded_responses) ? $decoded_responses : array(),
                'submitted_at' => $sub->submitted_at,
                'duration_seconds' => $sub->duration_seconds,
                'user_fingerprint' => $sub->user_fingerprint,
            );

            // Add privacy-controlled fields
            if (!empty($privacy['device_type'])) $submission_data['device'] = $sub->device;
            if (!empty($privacy['browser'])) $submission_data['browser'] = $sub->browser;
            if (!empty($privacy['os'])) $submission_data['os'] = $sub->os;
            if (!empty($privacy['screen_width'])) $submission_data['screen_width'] = $sub->screen_width;
            if (!empty($privacy['ip_address'])) $submission_data['ip_address'] = $sub->ip_address;

            if (!isset($submissions_by_participant[$participant_fingerprint])) {
                $submissions_by_participant[$participant_fingerprint] = array();
            }
            $submissions_by_participant[$participant_fingerprint][$wave_index] = $submission_data;
        }

        // --- Step 4: Match submissions with participants by email ---
        $participant_by_email = array();
        foreach ($participants as $p) {
            $participant_by_email[strtolower($p->email)] = $p;
        }

        // --- Step 5: Get assignments for progress tracking ---
        $participant_ids = array_column($participants, 'id');
        if (!empty($participant_ids)) {
            $ids_placeholder = implode(',', array_fill(0, count($participant_ids), '%d'));
            $assignments = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT participant_id, wave_id, status, submitted_at
                     FROM {$wpdb->prefix}survey_assignments
                     WHERE participant_id IN ({$ids_placeholder})
                     ORDER BY wave_id ASC",
                    $participant_ids
                )
            );
        } else {
            $assignments = array();
        }

        // Group assignments by participant
        $assignments_by_participant = array();
        foreach ($assignments as $a) {
            $assignments_by_participant[$a->participant_id][] = $a;
        }

        // --- Step 6: Build result rows ---
        $rows = array();
        
        foreach ($participants as $participant) {
            $p_id = $participant->id;
            $email = strtolower($participant->email);
            
            // Get assignments for this participant
            $p_assignments = isset($assignments_by_participant[$p_id]) ? $assignments_by_participant[$p_id] : array();
            
            // Build wave status map
            $wave_status_map = array();
            $submitted_count = 0;
            foreach ($p_assignments as $a) {
                if (isset($waves_by_index[$a->wave_id])) {
                    $wi = $waves_by_index[$a->wave_id]->wave_index;
                    $wave_status_map[$wi] = array(
                        'status' => $a->status,
                        'submitted_at' => $a->submitted_at,
                    );
                    if ($a->status === 'submitted') {
                        $submitted_count++;
                    }
                }
            }

            // Apply filters
            if ($filters['status'] === 'active' && !$participant->is_active) continue;
            if ($filters['status'] === 'inactive' && $participant->is_active) continue;
            
            if (!empty($filters['search'])) {
                $search = strtolower($filters['search']);
                if (strpos($email, $search) === false) continue;
            }

            if ($filters['wave_index'] !== 'all') {
                if (!isset($wave_status_map[$filters['wave_index']])) continue;
            }

            $total_waves = count($waves);
            $completion_percent = $total_waves > 0 ? round(($submitted_count / $total_waves) * 100) : 0;

            // Build participant row
            $row = array(
                'id' => (int) $participant->id,
                'email' => $participant->email,
                'is_active' => (bool) $participant->is_active,
                'created_at' => $participant->created_at,
                'last_login_at' => $participant->last_login_at,
                'waves_assigned' => count($p_assignments),
                'waves_submitted' => $submitted_count,
                'waves_total' => $total_waves,
                'completion_percent' => $completion_percent,
                'wave_statuses' => $wave_status_map,
                'submissions' => array(), // Will be filled below
            );

            // Find submissions for this participant by matching emails
            foreach ($submissions_by_participant as $fingerprint => $submissions_by_wave) {
                foreach ($submissions_by_wave as $wave_index => $submission) {
                    $submission_email = strtolower($submission['email_from_form'] ?? '');
                    
                    // Match by email
                    if ($submission_email === $email) {
                        $row['submissions'][$wave_index] = $submission;
                    }
                }
            }

            $rows[] = $row;
        }

        // Also add rows for submissions that couldn't be matched to participants
        foreach ($submissions_by_participant as $fingerprint => $submissions_by_wave) {
            $has_match = false;
            foreach ($submissions_by_wave as $submission) {
                $submission_email = strtolower($submission['email_from_form'] ?? '');
                if (isset($participant_by_email[$submission_email])) {
                    $has_match = true;
                    break;
                }
            }
            
            // If no match found, create a row with fingerprint as ID
            if (!$has_match) {
                // Find any submission to get email
                $first_submission = reset($submissions_by_wave);
                $email = $first_submission['email_from_form'] ?? '';
                
                $row = array(
                    'id' => $fingerprint, // Use fingerprint as ID
                    'email' => $email,
                    'is_active' => null,
                    'created_at' => null,
                    'last_login_at' => null,
                    'waves_assigned' => count($submissions_by_wave),
                    'waves_submitted' => count($submissions_by_wave),
                    'waves_total' => count($waves),
                    'completion_percent' => 100, // All submitted waves are completed
                    'wave_statuses' => array(),
                    'submissions' => $submissions_by_wave,
                );
                
                // Build wave statuses from submissions
                foreach ($submissions_by_wave as $wave_index => $submission) {
                    $row['wave_statuses'][$wave_index] = array(
                        'status' => 'submitted',
                        'submitted_at' => $submission['submitted_at'],
                    );
                }
                
                $rows[] = $row;
            }
        }

        return array(
            'rows' => $rows,
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

        // Get all unique field names from submissions across all waves
        $all_field_names = array();
        foreach ($rows as $row) {
            if (isset($row['submissions'])) {
                foreach ($row['submissions'] as $wave_index => $submission) {
                    if (isset($submission['form_responses']) && is_array($submission['form_responses'])) {
                        $excluded_fields = array(
                            'eipsi_consent_accepted',
                            'wave_id',
                            'form_action',
                            'nonce',
                            'action',
                            'current_page',
                            'form_start_time',
                            'form_end_time',
                            'end_timestamp_ms',
                            'eipsi_user_fingerprint',
                            'eipsi_fingerprint_raw'
                        );
                        
                        foreach ($submission['form_responses'] as $field_name => $value) {
                            if (!in_array($field_name, $excluded_fields)) {
                                $all_field_names[$field_name] = true;
                            }
                        }
                    }
                }
            }
        }
        $unique_field_names = array_keys($all_field_names);

        foreach ($waves as $wave) {
            $prefix = 'T' . $wave->wave_index;
            $headers[] = $prefix . '_submitted_at';
            $headers[] = $prefix . '_duration_seconds';
            $headers[] = $prefix . '_fingerprint_id';
            $headers[] = $prefix . '_device';
            $headers[] = $prefix . '_browser';
            $headers[] = $prefix . '_os';
            $headers[] = $prefix . '_screen_width';
            $headers[] = $prefix . '_ip_address';

            // Add dynamic headers for form_response fields
            foreach ($unique_field_names as $field_name) {
                $headers[] = $prefix . '_' . $field_name;
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
        // Base participant data (without names)
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

        // Get all unique field names (same as headers)
        $all_field_names = array();
        foreach ($waves as $wave) {
            foreach ($row['submissions'] ?? array() as $wave_index => $submission) {
                if (isset($submission['form_responses']) && is_array($submission['form_responses'])) {
                    $excluded_fields = array(
                        'eipsi_consent_accepted',
                        'wave_id',
                        'form_action',
                        'nonce',
                        'action',
                        'current_page',
                        'form_start_time',
                        'form_end_time',
                        'end_timestamp_ms',
                        'eipsi_user_fingerprint',
                        'eipsi_fingerprint_raw'
                    );
                    
                    foreach ($submission['form_responses'] as $field_name => $value) {
                        if (!in_array($field_name, $excluded_fields)) {
                            $all_field_names[$field_name] = true;
                        }
                    }
                }
            }
        }
        $unique_field_names = array_keys($all_field_names);

        // Add wave data for each wave
        foreach ($waves as $wave) {
            $wi = $wave->wave_index;
            $submission = isset($row['submissions'][$wi]) ? $row['submissions'][$wi] : null;

            if ($submission) {
                // Metadata fields
                $data[] = $submission['submitted_at'] ? date('Y-m-d H:i:s', strtotime($submission['submitted_at'])) : '';
                $data[] = $submission['duration_seconds'] ?? '';
                $data[] = $submission['user_fingerprint'] ?? '';
                $data[] = $submission['device'] ?? '';
                $data[] = $submission['browser'] ?? '';
                $data[] = $submission['os'] ?? '';
                $data[] = $submission['screen_width'] ?? '';
                $data[] = $submission['ip_address'] ?? '';

                // Form response fields
                $form_responses = $submission['form_responses'] ?? array();
                foreach ($unique_field_names as $field_name) {
                    $value = '';
                    if (isset($form_responses[$field_name])) {
                        $val = $form_responses[$field_name];
                        if (is_array($val) || is_object($val)) {
                            $value = json_encode($val, JSON_UNESCAPED_UNICODE);
                        } else {
                            $value = $val;
                        }
                    }
                    $data[] = $value;
                }
            } else {
                // Empty submission - add empty columns
                $metadata_columns = 8; // submitted_at, duration_seconds, fingerprint_id, device, browser, os, screen_width, ip_address
                for ($i = 0; $i < $metadata_columns; $i++) {
                    $data[] = '';
                }
                // Empty form response columns
                for ($i = 0; $i < count($unique_field_names); $i++) {
                    $data[] = '';
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

        $headers = $this->build_participants_wide_headers($rows, $waves);
        $xlsx_data = array($headers);

        foreach ($rows as $row) {
            $xlsx_data[] = $this->build_participants_wide_row($row, $waves);
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
    public function stream_participants_wide_csv($study_id, $filters, $output) {
        $result = $this->fetch_participants_data($study_id, $filters);
        $rows   = isset($result['rows'])  ? $result['rows']  : array();
        $waves  = isset($result['waves']) ? $result['waves'] : array();

        fputcsv($output, $this->build_participants_wide_headers($rows, $waves));

        foreach ($rows as $row) {
            fputcsv($output, $this->build_participants_wide_row($row, $waves));
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

        $headers = $this->build_participants_wide_headers($rows, $waves);
        $preview_rows = array();

        foreach (array_slice($rows, 0, $limit) as $row) {
            $preview_rows[] = $this->build_participants_wide_row($row, $waves);
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
                 WHERE r.survey_id = %d
                 AND r.wave_index IS NOT NULL
                 AND r.form_responses IS NOT NULL
                 AND r.form_responses != ''",
                $study_id
            )
        );

        $all_fields = array();
        $excluded_fields = array(
            'eipsi_consent_accepted',
            'wave_id',
            'form_action',
            'nonce',
            'action',
            'current_page',
            'form_start_time',
            'form_end_time',
            'end_timestamp_ms',
            'eipsi_user_fingerprint',
            'eipsi_fingerprint_raw'
        );

        foreach ($results as $json) {
            $decoded = json_decode($json, true);
            if (is_array($decoded)) {
                foreach (array_keys($decoded) as $field_name) {
                    if (!in_array($field_name, $excluded_fields)) {
                        $all_fields[] = $field_name;
                    }
                }
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

        // Base participant data
        $data = array(
            $participant_row['id'],
            $participant_row['email'],
            $participant_row['is_active'] ? 'Activo' : 'Inactivo',
            $participant_row['created_at'] ? date('Y-m-d H:i', strtotime($participant_row['created_at'])) : '',
            $wi, // Toma (wave_index)
            $wave->name, // Wave_name
        );

        // Get submission data for this wave
        $submission = isset($participant_row['submissions'][$wi]) ? $participant_row['submissions'][$wi] : null;

        if ($submission) {
            // Metadata fields
            $data[] = $submission['submitted_at'] ? date('Y-m-d H:i:s', strtotime($submission['submitted_at'])) : '';
            $data[] = $submission['duration_seconds'] ?? '';
            $data[] = $submission['user_fingerprint'] ?? '';
            $data[] = $submission['device'] ?? '';
        } else {
            // Empty submission
            for ($i = 0; $i < 4; $i++) {
                $data[] = '';
            }
        }

        // Form response fields (using all unique field names)
        $form_responses = $submission ? ($submission['form_responses'] ?? array()) : array();
        foreach ($all_unique_field_names as $field_name) {
            $value = '';
            if (isset($form_responses[$field_name])) {
                $val = $form_responses[$field_name];
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

        // Get all unique field names across the study
        $all_unique_field_names = $this->get_all_unique_field_names($study_id);
        $headers = $this->build_participants_long_headers($all_unique_field_names);
        $xlsx_data = array($headers);

        // Generate one row per participant × wave combination
        foreach ($rows as $row) {
            foreach ($waves as $wave) {
                $xlsx_data[] = $this->build_participants_long_row($row, $wave, $all_unique_field_names);
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

        // Get all unique field names across the study
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

        // Get all unique field names across the study
        $all_unique_field_names = $this->get_all_unique_field_names($study_id);

        // Build headers
        $headers = $this->build_participants_long_headers($all_unique_field_names);

        // Build preview rows (one per participant × wave, up to limit)
        $preview_rows = array();
        $count = 0;

        foreach ($rows as $participant_row) {
            foreach ($waves as $wave) {
                if ($count >= $limit) break 2;
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
