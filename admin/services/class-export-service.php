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
        require_once EIPSI_FORMS_PLUGIN_DIR . 'lib/SimpleXLSXGen.php';

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
     * @param array $waves List of wave objects.
     * @return string[]
     */
    private function build_participant_headers($waves) {
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

        foreach ($waves as $wave) {
            $label     = 'T' . $wave->wave_index . ' - ' . $wave->name;
            $headers[] = $label . ' (Estado)';
            $headers[] = $label . ' (Completado)';
        }

        return $headers;
    }

    /**
     * Build a flat data row for one participant.
     *
     * @param array $row    Participant row from fetch_participants_data().
     * @param array $waves  Wave list.
     * @return array
     */
    private function build_participant_row($row, $waves) {
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

        foreach ($waves as $wave) {
            $wi          = $wave->wave_index;
            $wave_info   = isset($row['wave_statuses'][$wi]) ? $row['wave_statuses'][$wi] : null;
            $status_map  = array(
                'submitted'   => 'Completado',
                'in_progress' => 'En progreso',
                'pending'     => 'Pendiente',
                'expired'     => 'Expirado',
            );

            if ($wave_info) {
                $data[] = isset($status_map[$wave_info['status']]) ? $status_map[$wave_info['status']] : $wave_info['status'];
                $data[] = $wave_info['submitted_at'] ? date('Y-m-d H:i', strtotime($wave_info['submitted_at'])) : '';
            } else {
                $data[] = 'No asignado';
                $data[] = '';
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
        require_once EIPSI_FORMS_PLUGIN_DIR . 'lib/SimpleXLSXGen.php';

        $result = $this->fetch_participants_data($study_id, $filters);
        $rows   = isset($result['rows'])  ? $result['rows']  : array();
        $waves  = isset($result['waves']) ? $result['waves'] : array();

        $headers    = $this->build_participant_headers($waves);
        $xlsx_data  = array($headers);

        foreach ($rows as $row) {
            $xlsx_data[] = $this->build_participant_row($row, $waves);
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

        fputcsv($output, $this->build_participant_headers($waves));

        foreach ($rows as $row) {
            fputcsv($output, $this->build_participant_row($row, $waves));
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
        $result = $this->fetch_participants_data($study_id, $filters);
        $rows   = isset($result['rows'])  ? $result['rows']  : array();
        $waves  = isset($result['waves']) ? $result['waves'] : array();

        $headers      = $this->build_participant_headers($waves);
        $preview_rows = array();

        foreach (array_slice($rows, 0, $limit) as $row) {
            $preview_rows[] = $this->build_participant_row($row, $waves);
        }

        return array(
            'headers' => $headers,
            'rows'    => $preview_rows,
            'total'   => count($rows),
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
}
