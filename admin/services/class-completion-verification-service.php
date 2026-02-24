<?php
/**
 * EIPSI_Completion_Verification_Service
 *
 * Verifica la precisión de las tasas de finalización en exportaciones.
 * Detecta discrepancias entre conteos y proporciona explicaciones.
 *
 * @package EIPSI_Forms
 * @subpackage Services
 * @version 2.1.0
 * @since Phase 3 - Task 3A.2
 */

if (!defined('ABSPATH')) {
    exit;
}

class EIPSI_Completion_Verification_Service {

    /**
     * Verify completion rates for a study.
     *
     * @param int $study_id Study ID to verify
     * @return array Verification results with counts, rates, and any mismatches
     */
    public static function verify_completion_rates($study_id) {
        global $wpdb;

        $study_id = absint($study_id);
        if (!$study_id) {
            return array(
                'success' => false,
                'error' => 'Invalid study ID'
            );
        }

        $results = array(
            'study_id' => $study_id,
            'verified_at' => current_time('mysql'),
            'total_participants' => 0,
            'participants_by_status' => array(),
            'waves' => array(),
            'completion_rates' => array(),
            'discrepancies' => array(),
            'is_accurate' => true,
            'explanation' => ''
        );

        // Get study info
        $study = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}survey_studies WHERE id = %d",
            $study_id
        ));

        if (!$study) {
            return array(
                'success' => false,
                'error' => 'Study not found'
            );
        }

        $results['study_name'] = $study->study_name;

        // Count total participants
        $results['total_participants'] = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}survey_participants WHERE survey_id = %d",
            $study_id
        ));

        // Count participants by status
        $results['participants_by_status'] = array(
            'active' => (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}survey_participants WHERE survey_id = %d AND is_active = 1",
                $study_id
            )),
            'inactive' => (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}survey_participants WHERE survey_id = %d AND is_active = 0",
                $study_id
            ))
        );

        // Get all waves for this study
        $waves = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}survey_waves WHERE study_id = %d ORDER BY wave_index ASC",
            $study_id
        ));

        $total_waves = count($waves);

        foreach ($waves as $wave) {
            $wave_data = array(
                'wave_id' => $wave->id,
                'wave_index' => $wave->wave_index,
                'wave_name' => $wave->name,
                'total_assignments' => 0,
                'by_status' => array(),
                'completion_rate' => 0,
                'expected_completions' => 0
            );

            // Count assignments for this wave
            $assignments = $wpdb->get_results($wpdb->prepare(
                "SELECT status, COUNT(*) as count
                 FROM {$wpdb->prefix}survey_assignments
                 WHERE wave_id = %d
                 GROUP BY status",
                $wave->id
            ));

            $status_counts = array(
                'pending' => 0,
                'in_progress' => 0,
                'submitted' => 0,
                'skipped' => 0,
                'expired' => 0
            );

            foreach ($assignments as $row) {
                $status_counts[$row->status] = (int) $row->count;
            }

            $wave_data['by_status'] = $status_counts;
            $wave_data['total_assignments'] = array_sum($status_counts);

            // Calculate completion rate
            if ($wave_data['total_assignments'] > 0) {
                $wave_data['completion_rate'] = round(
                    ($status_counts['submitted'] / $wave_data['total_assignments']) * 100,
                    2
                );
            }

            // Calculate expected completions (based on participants at wave start)
            $wave_start = $wpdb->get_var($wpdb->prepare(
                "SELECT MIN(assigned_at) FROM {$wpdb->prefix}survey_assignments WHERE wave_id = %d",
                $wave->id
            ));

            if ($wave_start) {
                $active_at_start = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}survey_participants
                     WHERE survey_id = %d AND created_at <= %s",
                    $study_id,
                    $wave_start
                ));
                $wave_data['expected_completions'] = (int) $active_at_start;
            }

            $results['waves'][] = $wave_data;
        }

        // Overall completion stats
        if ($total_waves > 0) {
            $completed_all_waves = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT p.id)
                 FROM {$wpdb->prefix}survey_participants p
                 WHERE p.survey_id = %d
                 AND (
                     SELECT COUNT(DISTINCT a.wave_id)
                     FROM {$wpdb->prefix}survey_assignments a
                     WHERE a.participant_id = p.id AND a.status = 'submitted'
                 ) = %d",
                $study_id,
                $total_waves
            ));

            $results['completion_rates']['completed_all_waves'] = $completed_all_waves;
            $results['completion_rates']['completion_percentage'] = $results['total_participants'] > 0
                ? round(($completed_all_waves / $results['total_participants']) * 100, 2)
                : 0;
        }

        // Check for discrepancies
        $results = self::check_discrepancies($results);

        return array(
            'success' => true,
            'data' => $results
        );
    }

    /**
     * Check for discrepancies in the data.
     */
    private static function check_discrepancies($results) {
        $discrepancies = array();

        // Check 1: Wave assignments vs total participants
        foreach ($results['waves'] as $wave) {
            if ($wave['total_assignments'] !== $results['total_participants']) {
                $diff = $wave['total_assignments'] - $results['total_participants'];
                $discrepancies[] = array(
                    'type' => 'assignment_mismatch',
                    'wave_id' => $wave['wave_id'],
                    'wave_name' => $wave['wave_name'],
                    'message' => sprintf(
                        'La onda "%s" tiene %d asignaciones pero el estudio tiene %d participantes (diferencia: %d)',
                        $wave['wave_name'],
                        $wave['total_assignments'],
                        $results['total_participants'],
                        $diff
                    ),
                    'severity' => abs($diff) > 5 ? 'high' : 'low',
                    'explanation' => $diff > 0
                        ? 'Algunos participantes pueden tener múltiples asignaciones o hay asignaciones huérfanas.'
                        : 'Algunos participantes pueden no tener asignación en esta onda.'
                );
            }
        }

        // Check 2: Completed more waves than exist
        $completed_all = $results['completion_rates']['completed_all_waves'] ?? 0;
        if ($completed_all > $results['total_participants']) {
            $discrepancies[] = array(
                'type' => 'completion_overflow',
                'message' => sprintf(
                    'Hay %d participantes que completaron todas las ondas, pero solo hay %d participantes totales',
                    $completed_all,
                    $results['total_participants']
                ),
                'severity' => 'high',
                'explanation' => 'Posible duplicación de registros o error en el conteo de ondas completadas.'
            );
        }

        // Check 3: Zero submissions but participants exist
        if ($results['total_participants'] > 0) {
            $total_submitted = 0;
            foreach ($results['waves'] as $wave) {
                $total_submitted += $wave['by_status']['submitted'];
            }

            if ($total_submitted === 0) {
                $discrepancies[] = array(
                    'type' => 'no_submissions',
                    'message' => 'No hay envíos registrados aunque hay participantes en el estudio',
                    'severity' => 'info',
                    'explanation' => 'El estudio puede estar recién iniciado o los participantes aún no han completado ninguna onda.'
                );
            }
        }

        // Check 4: Inconsistent submission counts across waves
        if (count($results['waves']) > 1) {
            $submission_counts = array();
            foreach ($results['waves'] as $wave) {
                $submission_counts[] = $wave['by_status']['submitted'];
            }

            $max_submissions = max($submission_counts);
            $min_submissions = min($submission_counts);

            if ($max_submissions > 0) {
                $dropoff_rate = 1 - ($min_submissions / $max_submissions);
                if ($dropoff_rate > 0.5) {
                    $discrepancies[] = array(
                        'type' => 'high_dropout',
                        'message' => sprintf(
                            'Alta tasa de abandono: de %d envíos en la primera onda a %d en la última (%.1f%% menos)',
                            $max_submissions,
                            $min_submissions,
                            $dropoff_rate * 100
                        ),
                        'severity' => 'info',
                        'explanation' => 'Esto es normal en estudios longitudinales. Considere enviar recordatorios a participantes pendientes.'
                    );
                }
            }
        }

        $results['discrepancies'] = $discrepancies;
        $results['is_accurate'] = empty(array_filter($discrepancies, function($d) {
            return $d['severity'] === 'high';
        }));

        // Generate overall explanation
        if ($results['is_accurate'] && empty($discrepancies)) {
            $results['explanation'] = 'Los datos de finalización son consistentes y no se detectaron discrepancias.';
        } elseif ($results['is_accurate']) {
            $results['explanation'] = 'Los datos son generalmente consistentes. Las discrepancias detectadas son menores y esperadas.';
        } else {
            $high_priority = array_filter($discrepancies, function($d) {
                return $d['severity'] === 'high';
            });
            $results['explanation'] = sprintf(
                'Se detectaron %d problemas de alta prioridad que pueden indicar inconsistencias en los datos. Revise las discrepancias detalladas.',
                count($high_priority)
            );
        }

        return $results;
    }

    /**
     * Get export verification summary.
     *
     * @param int $study_id
     * @return array Summary for display in export interface
     */
    public static function get_export_verification_summary($study_id) {
        $verification = self::verify_completion_rates($study_id);

        if (!$verification['success']) {
            return array(
                'verified' => false,
                'message' => $verification['error']
            );
        }

        $data = $verification['data'];

        return array(
            'verified' => true,
            'verified_at' => $data['verified_at'],
            'is_accurate' => $data['is_accurate'],
            'total_participants' => $data['total_participants'],
            'total_waves' => count($data['waves']),
            'overall_completion_rate' => $data['completion_rates']['completion_percentage'] ?? 0,
            'discrepancy_count' => count($data['discrepancies']),
            'high_priority_issues' => count(array_filter($data['discrepancies'], function($d) {
                return $d['severity'] === 'high';
            })),
            'explanation' => $data['explanation'],
            'can_export' => true // Always allow export, just warn if needed
        );
    }

    /**
     * Get wave-level timestamps for export.
     *
     * @param int $study_id
     * @return array Wave timestamps data
     */
    public static function get_wave_timestamps($study_id) {
        global $wpdb;

        $study_id = absint($study_id);
        if (!$study_id) {
            return array();
        }

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT
                p.id as participant_id,
                p.email as participant_email,
                CONCAT(COALESCE(p.first_name, ''), ' ', COALESCE(p.last_name, '')) as participant_name,
                w.wave_index,
                w.name as wave_name,
                a.assigned_at as wave_started_at,
                a.first_viewed_at,
                a.submitted_at as wave_completed_at,
                a.status,
                CASE
                    WHEN a.submitted_at IS NOT NULL AND a.first_viewed_at IS NOT NULL
                    THEN TIMESTAMPDIFF(SECOND, a.first_viewed_at, a.submitted_at)
                    WHEN a.submitted_at IS NOT NULL AND a.first_viewed_at IS NULL AND a.assigned_at IS NOT NULL
                    THEN TIMESTAMPDIFF(SECOND, a.assigned_at, a.submitted_at)
                    ELSE NULL
                END as time_to_complete_seconds
             FROM {$wpdb->prefix}survey_participants p
             JOIN {$wpdb->prefix}survey_assignments a ON p.id = a.participant_id
             JOIN {$wpdb->prefix}survey_waves w ON a.wave_id = w.id
             WHERE p.survey_id = %d
             ORDER BY p.id, w.wave_index",
            $study_id
        ));

        return $results;
    }

    /**
     * Add wave timestamp columns to export data.
     *
     * @param array $export_data Existing export data
     * @param int $study_id
     * @return array Enhanced export data with wave timestamps
     */
    public static function enhance_export_with_wave_timestamps($export_data, $study_id) {
        $timestamps = self::get_wave_timestamps($study_id);

        if (empty($timestamps)) {
            return $export_data;
        }

        // Group by participant
        $participant_waves = array();
        foreach ($timestamps as $row) {
            $participant_id = $row->participant_id;
            if (!isset($participant_waves[$participant_id])) {
                $participant_waves[$participant_id] = array();
            }
            $participant_waves[$participant_id][] = $row;
        }

        // Add to export data
        // This assumes $export_data has participant_id column
        $headers = $export_data[0];
        $participant_id_index = array_search('Participant ID', $headers);

        if ($participant_id_index === false) {
            // Can't match participants, return as-is
            return $export_data;
        }

        // Add new headers for wave timestamps
        // Find max waves
        $max_waves = 0;
        foreach ($participant_waves as $waves) {
            $max_waves = max($max_waves, count($waves));
        }

        for ($i = 1; $i <= $max_waves; $i++) {
            $headers[] = "T{$i}_started_at";
            $headers[] = "T{$i}_completed_at";
            $headers[] = "T{$i}_time_seconds";
            $headers[] = "T{$i}_status";
        }

        $new_data = array($headers);

        // Process each row
        for ($i = 1; $i < count($export_data); $i++) {
            $row = $export_data[$i];
            $participant_id = $row[$participant_id_index];

            if (isset($participant_waves[$participant_id])) {
                foreach ($participant_waves[$participant_id] as $wave) {
                    $wave_index = $wave->wave_index;
                    $row[] = $wave->wave_started_at;
                    $row[] = $wave->wave_completed_at;
                    $row[] = $wave->time_to_complete_seconds;
                    $row[] = $wave->status;
                }
            }

            $new_data[] = $row;
        }

        return $new_data;
    }
}
