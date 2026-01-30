<?php
if (!defined('ABSPATH')) {
    exit;
}

class EIPSI_Export_Service {
    
    // Main export method
    public function export_longitudinal_data($survey_id, $filters = array()) {
        $default_filters = array(
            'wave_index' => null,    // 'T1', 'T2', 'All'
            'date_from' => null,     // YYYY-MM-DD
            'date_to' => null,       // YYYY-MM-DD
            'status' => 'all',       // 'all', 'completed', 'pending', 'late'
            'include_fingerprint' => true,
        );
        
        $filters = array_merge($default_filters, $filters);
        
        // Build query
        $data = $this->fetch_longitudinal_data($survey_id, $filters);
        
        return $data;
    }
    
    // Fetch data from DB
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
            JOIN {$wpdb->prefix}survey_waves sw ON sp.survey_id = sw.survey_id
            LEFT JOIN {$wpdb->prefix}survey_responses sr ON sp.id = sr.participant_id AND sw.id = sr.wave_id
            WHERE sp.survey_id = %d
        ";
        
        $params = array($survey_id);
        
        // Apply filters
        if (!empty($filters['wave_index']) && $filters['wave_index'] !== 'all') {
            $query .= " AND sw.wave_index = %s";
            $params[] = $filters['wave_index'];
        }
        
        if (!empty($filters['date_from'])) {
            $query .= " AND sr.submitted_at >= %s";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }
        
        if (!empty($filters['date_to'])) {
            $query .= " AND sr.submitted_at <= %s";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }
        
        if ($filters['status'] !== 'all') {
            $status = ucfirst($filters['status']);
            $query .= " AND 
                CASE
                    WHEN sw.due_date < sr.submitted_at THEN 'Late'
                    WHEN sr.submitted_at IS NOT NULL THEN 'Completed'
                    ELSE 'Pending'
                END = %s";
            $params[] = $status;
        }
        
        $query .= " ORDER BY sp.id, sw.wave_index";
        
        $stmt = $wpdb->prepare($query, $params);
        return $wpdb->get_results($stmt);
    }
    
    // Export to Excel
    public function export_to_excel($data, $survey_id) {
        require_once EIPSI_FORMS_PLUGIN_DIR . 'lib/SimpleXLSXGen.php';
        
        $xlsx_data = array();
        
        // Headers
        $headers = array(
            'Participant ID',
            'Wave',
            'Submitted At',
            'Response Time (min)',
            'Status',
            'User Fingerprint',
        );
        
        // Add form fields dynamically
        if (!empty($data)) {
            $first_row = $data[0];
            $response_data = json_decode($first_row->response_data, true);
            if (is_array($response_data)) {
                foreach (array_keys($response_data) as $field) {
                    $headers[] = $field;
                }
            }
        }
        
        $xlsx_data[] = $headers;
        
        // Add data rows
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
        
        // Generate filename
        $filename = "longitudinal_export_{$survey_id}_" . date('Y-m-d_H-i-s') . ".xlsx";
        
        // Ensure exports directory exists
        $export_dir = EIPSI_FORMS_PLUGIN_DIR . 'exports';
        if (!file_exists($export_dir)) {
            wp_mkdir_p($export_dir);
        }
        
        // Use SimpleXLSXGen
        $xlsx = SimpleXLSXGen::fromArray($xlsx_data);
        $xlsx->saveAs($export_dir . '/' . $filename);
        
        return $filename;
    }
    
    // Export to CSV
    public function export_to_csv($data, $survey_id) {
        $filename = "longitudinal_export_{$survey_id}_" . date('Y-m-d_H-i-s') . ".csv";
        
        // Ensure exports directory exists
        $export_dir = EIPSI_FORMS_PLUGIN_DIR . 'exports';
        if (!file_exists($export_dir)) {
            wp_mkdir_p($export_dir);
        }
        
        $file_path = $export_dir . '/' . $filename;
        $file = fopen($file_path, 'w');
        
        // Headers
        $headers = array(
            'Participant ID',
            'Wave',
            'Submitted At',
            'Response Time (min)',
            'Status',
            'User Fingerprint',
        );
        
        if (!empty($data)) {
            $first_row = $data[0];
            $response_data = json_decode($first_row->response_data, true);
            if (is_array($response_data)) {
                foreach (array_keys($response_data) as $field) {
                    $headers[] = $field;
                }
            }
        }
        
        fputcsv($file, $headers);
        
        // Data
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
    
    // Get statistics
    public function get_export_statistics($survey_id, $filters = array()) {
        global $wpdb;
        
        $stats = array(
            'total_participants' => 0,
            'completed_all_waves' => 0,
            'completion_rates' => array(),
            'avg_response_times' => array(),
        );
        
        // Total participants
        $stats['total_participants'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}survey_participants WHERE survey_id = %d AND is_active = 1",
            $survey_id
        ));
        
        // Completion rates per wave
        $waves = $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT wave_index FROM {$wpdb->prefix}survey_waves WHERE survey_id = %d ORDER BY wave_index",
            $survey_id
        ));
        
        foreach ($waves as $wave) {
            $completed = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT sp.id) 
                 FROM {$wpdb->prefix}survey_participants sp
                 JOIN {$wpdb->prefix}survey_waves sw ON sp.survey_id = sw.survey_id
                 JOIN {$wpdb->prefix}survey_responses sr ON sp.id = sr.participant_id AND sw.id = sr.wave_id
                 WHERE sp.survey_id = %d AND sw.wave_index = %s",
                $survey_id,
                $wave->wave_index
            ));
            
            $rate = ($stats['total_participants'] > 0) ? ($completed / $stats['total_participants']) * 100 : 0;
            $stats['completion_rates'][$wave->wave_index] = array(
                'completed' => $completed,
                'total' => $stats['total_participants'],
                'rate' => round($rate, 2),
            );
            
            // Average response time
            $avg_time = $wpdb->get_var($wpdb->prepare(
                "SELECT AVG(TIMESTAMPDIFF(SECOND, sr.created_at, sr.submitted_at)) 
                 FROM {$wpdb->prefix}survey_responses sr
                 JOIN {$wpdb->prefix}survey_waves sw ON sr.wave_id = sw.id
                 WHERE sw.survey_id = %d AND sw.wave_index = %s",
                $survey_id,
                $wave->wave_index
            ));
            
            $stats['avg_response_times'][$wave->wave_index] = array(
                'seconds' => (int)$avg_time,
                'minutes' => round($avg_time / 60, 2),
            );
        }
        
        // Completed all waves
        $all_wave_indices = array_column($waves, 'wave_index');
        if (count($all_wave_indices) > 0) {
            $stats['completed_all_waves'] = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT sp.id)
                 FROM {$wpdb->prefix}survey_participants sp
                 WHERE sp.survey_id = %d AND sp.is_active = 1
                 AND (
                     SELECT COUNT(DISTINCT sw.wave_index)
                     FROM {$wpdb->prefix}survey_waves sw
                     JOIN {$wpdb->prefix}survey_responses sr ON sp.id = sr.participant_id AND sw.id = sr.wave_id
                     WHERE sw.survey_id = sp.survey_id
                 ) = %d",
                $survey_id,
                count($all_wave_indices)
            ));
        }
        
        return $stats;
    }
    
    // Get available surveys for dropdown
    public function get_available_surveys() {
        global $wpdb;
        
        $surveys = $wpdb->get_results(
            "SELECT id, title, description 
             FROM {$wpdb->prefix}survey_surveys 
             WHERE is_active = 1 
             ORDER BY created_at DESC"
        );
        
        return $surveys;
    }
    
    // Get waves for a specific survey
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