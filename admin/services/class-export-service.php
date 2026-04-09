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
    $wave_id_to_index = array(); // ✅ Mapeo wave_id -> wave_index
    foreach ($waves as $w) {
        $waves_by_index[$w->wave_index] = $w;
        $waves_by_form_id[$w->form_id] = $w;
        $wave_id_to_index[$w->id] = $w->wave_index; // Mapear ID de tabla a índice
    }

    // --- Step 3: Get longitudinal submissions from vas_form_results ---
    $submissions = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT r.longitudinal_participant_id, r.wave_index, r.form_id, r.form_responses,
                    r.submitted_at, r.duration_seconds, r.user_fingerprint,
                    r.device, r.browser, r.os, r.screen_width, r.ip_address,
                    r.participant_id as fingerprint_participant_id
             FROM {$wpdb->prefix}vas_form_results r
             WHERE r.survey_id = %d
             AND r.wave_index IS NOT NULL
             ORDER BY r.submitted_at ASC",
            $study_id
        )
    );

    // Load privacy configs
    if (!function_exists('get_privacy_config')) {
        require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/privacy-config.php';
    }

    // Process submissions and organize by participant
    $submissions_by_participant = array();
    $privacy_configs = array();
    
    foreach ($submissions as $sub) {
        $longitudinal_participant_id = $sub->longitudinal_participant_id;
        $wave_index = $sub->wave_index;
        $form_id = $sub->form_id;
        $fingerprint_participant_id = $sub->fingerprint_participant_id;

        // Get privacy config for this form
        if (!isset($privacy_configs[$form_id])) {
            $privacy_configs[$form_id] = get_privacy_config($form_id);
        }
        $privacy = $privacy_configs[$form_id];

        // Decode form responses
        $decoded_responses = json_decode($sub->form_responses, true);
        
        // Build submission data
        $submission_data = array(
            'longitudinal_participant_id' => $longitudinal_participant_id,
            'fingerprint_participant_id' => $fingerprint_participant_id,
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

        // Organize by longitudinal participant ID (integer)
        if ($longitudinal_participant_id) {
            if (!isset($submissions_by_participant[$longitudinal_participant_id])) {
                $submissions_by_participant[$longitudinal_participant_id] = array();
            }
            $submissions_by_participant[$longitudinal_participant_id][$wave_index] = $submission_data;
        }
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
            
            // Get assignments for this participant
            $p_assignments = isset($assignments_by_participant[$p_id]) ? $assignments_by_participant[$p_id] : array();
            
            // Build wave status map
            $wave_status_map = array();
            $submitted_count = 0;
            foreach ($p_assignments as $a) {
                // ✅ v1.5.6 - Usar mapeo wave_id -> wave_index
                if (isset($wave_id_to_index[$a->wave_id])) {
                    $wi = $wave_id_to_index[$a->wave_id];
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
                if (strpos(strtolower($participant->email), $search) === false) continue;
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
                'submissions' => isset($submissions_by_participant[$p_id]) ? $submissions_by_participant[$p_id] : array(),
            );

            $rows[] = $row;
        }

        return array(
            'rows' => $rows,
            'waves' => $waves,
        );
    }

    /**
     * Stream participant CSV directly to an open file handle (e.g. php://output).
     *
     * @param int      $study_id
     * @param array    $filters
     * @param resource $output   Open file handle.
     */
    public function stream_participants_csv($study_id, $filters, $output) {
        $this->stream_participants_wide_csv($study_id, $filters, $output);
    }
    
    public function get_participants_preview($study_id, $filters = array(), $limit = 10) {
        return $this->get_participants_wide_preview($study_id, $filters, $limit);
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
     * Estructura Wide (v2.1.3):
     * - Columnas base: ID, Email, Estado, Registrado, Último acceso, Ondas asignadas, Ondas completadas, Progreso (%)
     * - Por cada wave: T{n}_submitted_at, T{n}_duration_seconds, T{n}_device, T{n}_browser, T{n}_os, T{n}_screen_width, T{n}_ip_address, T{n}_{field_name}
     * - SIN columnas: Nombre, Apellido, fingerprint_id (el investigador lo construye si lo necesita)
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
                            'eipsi_nonce',
                            'nonce',
                            'seguimiento',
                            'wave_id',
                            'form_action',
                            'action',
                            'current_page',
                            'form_start_time',
                            'form_end_time',
                            'end_timestamp_ms',
                            'eipsi_user_fingerprint',
                            'eipsi_fingerprint_raw',
                            'eipsi_consent_accepted',
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
            // v2.1.3: Removed fingerprint_id - researchers should construct this if needed
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
                        'eipsi_nonce',
                        'nonce',
                        'seguimiento',
                        'wave_id',
                        'form_action',
                        'action',
                        'current_page',
                        'form_start_time',
                        'form_end_time',
                        'end_timestamp_ms',
                        'eipsi_user_fingerprint',
                        'eipsi_fingerprint_raw',
                        'eipsi_consent_accepted',
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
                // v2.1.3: Removed fingerprint_id from export
                $data[] = $submission['submitted_at'] ? date('Y-m-d H:i:s', strtotime($submission['submitted_at'])) : '';
                $data[] = $submission['duration_seconds'] ?? '';
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
                // v2.1.3: 7 columns (removed fingerprint_id)
                $metadata_columns = 7; // submitted_at, duration_seconds, device, browser, os, screen_width, ip_address
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
    public function get_participants_wide_preview($study_id, $filters = array(), $limit = 8) {
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
}
