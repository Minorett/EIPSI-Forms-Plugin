<?php
/**
 * EIPSI_Participant_Timeline_Service
 *
 * Genera vistas de timeline detalladas por participante.
 * Muestra progreso visual: invited → registered → wave 1 complete → wave 2 pending
 *
 * @package EIPSI_Forms
 * @subpackage Services
 * @version 2.1.0
 * @since Phase 3 - Task 3B.4
 */

if (!defined('ABSPATH')) {
    exit;
}

class EIPSI_Participant_Timeline_Service {

    /**
     * Get complete timeline for a participant.
     *
     * @param int $participant_id
     * @return array Timeline data with events
     */
    public static function get_participant_timeline($participant_id) {
        global $wpdb;

        $participant_id = absint($participant_id);
        if (!$participant_id) {
            return array('success' => false, 'error' => 'Invalid participant ID');
        }

        // Get participant basic info
        $participant = $wpdb->get_row($wpdb->prepare(
            "SELECT p.*, s.study_name, s.id as study_id
             FROM {$wpdb->prefix}survey_participants p
             LEFT JOIN {$wpdb->prefix}survey_studies s ON p.survey_id = s.id
             WHERE p.id = %d",
            $participant_id
        ));

        if (!$participant) {
            return array('success' => false, 'error' => 'Participant not found');
        }

        $timeline = array(
            'success' => true,
            'participant' => array(
                'id' => $participant->id,
                'email' => $participant->email,
                'first_name' => $participant->first_name,
                'last_name' => $participant->last_name,
                'full_name' => trim($participant->first_name . ' ' . $participant->last_name),
                'is_active' => (bool) $participant->is_active,
                'created_at' => $participant->created_at,
                'last_login_at' => $participant->last_login_at,
                'study_id' => $participant->study_id,
                'study_name' => $participant->study_name
            ),
            'events' => array(),
            'waves_progress' => array(),
            'overall_progress' => array()
        );

        // Build timeline events
        $events = array();

        // 1. Registration event
        $events[] = array(
            'type' => 'registration',
            'status' => 'completed',
            'date' => $participant->created_at,
            'title' => 'Registro completado',
            'description' => 'El participante se registró en el estudio',
            'icon' => '👤',
            'completed' => true
        );

        // 2. Invitation sent (from email log)
        $invitation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}survey_email_log
             WHERE participant_id = %d AND email_type = 'welcome'
             AND status = 'sent'
             ORDER BY sent_at ASC LIMIT 1",
            $participant_id
        ));

        if ($invitation) {
            $events[] = array(
                'type' => 'invitation',
                'status' => 'completed',
                'date' => $invitation->sent_at,
                'title' => 'Invitación enviada',
                'description' => 'Email de bienvenida enviado',
                'icon' => '📧',
                'completed' => true
            );
        }

        // 3. First login
        $first_login = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}survey_participant_access_log
             WHERE participant_id = %d AND action_type = 'login'
             ORDER BY created_at ASC LIMIT 1",
            $participant_id
        ));

        if ($first_login) {
            $events[] = array(
                'type' => 'first_login',
                'status' => 'completed',
                'date' => $first_login->created_at,
                'title' => 'Primer acceso',
                'description' => 'El participante inició sesión por primera vez',
                'icon' => '🔑',
                'completed' => true
            );
        }

        // 4. Wave progress
        $waves = $wpdb->get_results($wpdb->prepare(
            "SELECT
                w.id as wave_id,
                w.wave_index,
                w.name as wave_name,
                w.due_date,
                a.status,
                a.assigned_at,
                a.first_viewed_at,
                a.submitted_at,
                a.reminder_count,
                a.last_reminder_sent
             FROM {$wpdb->prefix}survey_waves w
             JOIN {$wpdb->prefix}survey_assignments a ON w.id = a.wave_id
             WHERE a.participant_id = %d AND w.study_id = %d
             ORDER BY w.wave_index ASC",
            $participant_id,
            $participant->study_id
        ));

        $total_waves = count($waves);
        $completed_waves = 0;

        foreach ($waves as $wave) {
            $wave_progress = array(
                'wave_id' => $wave->wave_id,
                'wave_index' => $wave->wave_index,
                'wave_name' => $wave->wave_name,
                'status' => $wave->status,
                'assigned_at' => $wave->assigned_at,
                'first_viewed_at' => $wave->first_viewed_at,
                'submitted_at' => $wave->submitted_at,
                'due_date' => $wave->due_date,
                'is_overdue' => false,
                'reminders_sent' => (int) $wave->reminder_count
            );

            // Check if overdue
            if ($wave->status !== 'submitted' && !empty($wave->due_date)) {
                $wave_progress['is_overdue'] = strtotime($wave->due_date) < current_time('timestamp');
            }

            if ($wave->status === 'submitted') {
                $completed_waves++;
            }

            $timeline['waves_progress'][] = $wave_progress;

            // Add wave events
            if ($wave->first_viewed_at) {
                $events[] = array(
                    'type' => 'wave_started',
                    'status' => 'completed',
                    'date' => $wave->first_viewed_at,
                    'title' => "Toma {$wave->wave_index} iniciada",
                    'description' => $wave->wave_name,
                    'icon' => '📝',
                    'completed' => true,
                    'wave_id' => $wave->wave_id
                );
            }

            if ($wave->submitted_at) {
                // Calculate completion time
                $completion_time = '';
                if ($wave->first_viewed_at) {
                    $seconds = strtotime($wave->submitted_at) - strtotime($wave->first_viewed_at);
                    $minutes = floor($seconds / 60);
                    if ($minutes < 60) {
                        $completion_time = $minutes . ' min';
                    } else {
                        $hours = floor($minutes / 60);
                        $remaining_min = $minutes % 60;
                        $completion_time = $hours . 'h ' . $remaining_min . 'min';
                    }
                }

                $events[] = array(
                    'type' => 'wave_completed',
                    'status' => 'completed',
                    'date' => $wave->submitted_at,
                    'title' => "Toma {$wave->wave_index} completada",
                    'description' => $completion_time ? "Completada en {$completion_time}" : $wave->wave_name,
                    'icon' => '✅',
                    'completed' => true,
                    'wave_id' => $wave->wave_id
                );
            } elseif ($wave->status === 'pending' || $wave->status === 'in_progress') {
                $events[] = array(
                    'type' => 'wave_pending',
                    'status' => 'pending',
                    'date' => null,
                    'title' => "Toma {$wave->wave_index} pendiente",
                    'description' => $wave->wave_name . ($wave_progress['is_overdue'] ? ' (Vencida)' : ''),
                    'icon' => $wave_progress['is_overdue'] ? '⚠️' : '⏳',
                    'completed' => false,
                    'wave_id' => $wave->wave_id,
                    'is_overdue' => $wave_progress['is_overdue']
                );
            }
        }

        // Sort events by date
        usort($events, function($a, $b) {
            if (empty($a['date']) && empty($b['date'])) return 0;
            if (empty($a['date'])) return 1;
            if (empty($b['date'])) return -1;
            return strtotime($a['date']) - strtotime($b['date']);
        });

        $timeline['events'] = $events;

        // Calculate overall progress
        $progress_percentage = $total_waves > 0 ? round(($completed_waves / $total_waves) * 100) : 0;

        $timeline['overall_progress'] = array(
            'total_waves' => $total_waves,
            'completed_waves' => $completed_waves,
            'percentage' => $progress_percentage,
            'status' => $progress_percentage === 100 ? 'completed' : ($progress_percentage > 0 ? 'in_progress' : 'not_started'),
            'next_action' => self::get_next_action($waves, $participant)
        );

        return $timeline;
    }

    /**
     * Determine the next recommended action for a participant.
     */
    private static function get_next_action($waves, $participant) {
        // Check if inactive
        if (!$participant->is_active) {
            return array(
                'action' => 'reactivate',
                'label' => 'Reactivar participante',
                'description' => 'El participante está inactivo'
            );
        }

        // Find next pending wave
        foreach ($waves as $wave) {
            if ($wave->status === 'pending' || $wave->status === 'in_progress') {
                if (!empty($wave->due_date) && strtotime($wave->due_date) < current_time('timestamp')) {
                    return array(
                        'action' => 'send_reminder',
                        'label' => 'Enviar recordatorio urgente',
                        'description' => "Toma {$wave->wave_index} está vencida"
                    );
                }

                return array(
                    'action' => 'send_reminder',
                    'label' => 'Enviar recordatorio',
                    'description' => "Pendiente: Toma {$wave->wave_index}"
                );
            }
        }

        // All waves completed
        if (count($waves) > 0) {
            $all_completed = true;
            foreach ($waves as $wave) {
                if ($wave->status !== 'submitted') {
                    $all_completed = false;
                    break;
                }
            }

            if ($all_completed) {
                return array(
                    'action' => 'completed',
                    'label' => 'Estudio completado',
                    'description' => 'Todas las tomas han sido completadas'
                );
            }
        }

        return array(
            'action' => 'none',
            'label' => 'Sin acción requerida',
            'description' => 'Esperando siguiente onda'
        );
    }

    /**
     * Get timeline for all participants in a study (summary view).
     *
     * @param int $study_id
     * @param array $filters Optional filters
     * @return array Participants with progress summary
     */
    public static function get_study_participants_timeline($study_id, $filters = array()) {
        global $wpdb;

        $study_id = absint($study_id);
        if (!$study_id) {
            return array('success' => false, 'error' => 'Invalid study ID');
        }

        $where = array('p.survey_id = %d');
        $params = array($study_id);

        // Status filter
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'active') {
                $where[] = 'p.is_active = 1';
            } elseif ($filters['status'] === 'inactive') {
                $where[] = 'p.is_active = 0';
            }
        }

        // Search filter
        if (!empty($filters['search'])) {
            $search = '%' . $wpdb->esc_like($filters['search']) . '%';
            $where[] = '(p.email LIKE %s OR p.first_name LIKE %s OR p.last_name LIKE %s)';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        $where_clause = implode(' AND ', $where);

        $participants = $wpdb->get_results($wpdb->prepare(
            "SELECT
                p.id,
                p.email,
                p.first_name,
                p.last_name,
                p.is_active,
                p.created_at,
                p.last_login_at
             FROM {$wpdb->prefix}survey_participants p
             WHERE {$where_clause}
             ORDER BY p.created_at DESC",
            $params
        ));

        if (empty($participants)) {
            return array('success' => true, 'participants' => array(), 'total' => 0);
        }

        // Get waves for progress calculation
        $waves = $wpdb->get_results($wpdb->prepare(
            "SELECT id, wave_index FROM {$wpdb->prefix}survey_waves WHERE study_id = %d ORDER BY wave_index",
            $study_id
        ));

        $total_waves = count($waves);
        $wave_ids = array_column($waves, 'id');

        // Get all assignments in one query
        $participant_ids = array_column($participants, 'id');
        $ids_placeholder = implode(',', array_fill(0, count($participant_ids), '%d'));

        $assignments = $wpdb->get_results($wpdb->prepare(
            "SELECT participant_id, wave_id, status, submitted_at
             FROM {$wpdb->prefix}survey_assignments
             WHERE participant_id IN ({$ids_placeholder})
             ORDER BY wave_id",
            $participant_ids
        ));

        // Group assignments by participant
        $assignments_by_participant = array();
        foreach ($assignments as $a) {
            if (!isset($assignments_by_participant[$a->participant_id])) {
                $assignments_by_participant[$a->participant_id] = array();
            }
            $assignments_by_participant[$a->participant_id][] = $a;
        }

        // Build participant summaries
        $participant_summaries = array();
        foreach ($participants as $p) {
            $p_assignments = $assignments_by_participant[$p->id] ?? array();
            $completed = 0;
            $in_progress = 0;
            $pending = 0;

            foreach ($p_assignments as $a) {
                if ($a->status === 'submitted') {
                    $completed++;
                } elseif ($a->status === 'in_progress') {
                    $in_progress++;
                } elseif ($a->status === 'pending') {
                    $pending++;
                }
            }

            $progress = $total_waves > 0 ? round(($completed / $total_waves) * 100) : 0;

            $participant_summaries[] = array(
                'id' => $p->id,
                'email' => $p->email,
                'first_name' => $p->first_name,
                'last_name' => $p->last_name,
                'full_name' => trim($p->first_name . ' ' . $p->last_name),
                'is_active' => (bool) $p->is_active,
                'registered_at' => $p->created_at,
                'last_login_at' => $p->last_login_at,
                'progress' => array(
                    'completed' => $completed,
                    'in_progress' => $in_progress,
                    'pending' => $pending,
                    'total' => $total_waves,
                    'percentage' => $progress
                ),
                'status' => $progress === 100 ? 'completed' : ($progress > 0 ? 'in_progress' : 'not_started')
            );
        }

        return array(
            'success' => true,
            'participants' => $participant_summaries,
            'total' => count($participant_summaries),
            'study_id' => $study_id,
            'total_waves' => $total_waves
        );
    }

    /**
     * Get participant progress percentage for display.
     */
    public static function get_progress_visual($percentage) {
        $bars = 10;
        $filled = round(($percentage / 100) * $bars);
        $empty = $bars - $filled;

        $visual = str_repeat('█', $filled) . str_repeat('░', $empty);

        return array(
            'visual' => $visual,
            'percentage' => $percentage,
            'color' => $percentage >= 80 ? 'green' : ($percentage >= 50 ? 'yellow' : ($percentage > 0 ? 'orange' : 'red'))
        );
    }
}
