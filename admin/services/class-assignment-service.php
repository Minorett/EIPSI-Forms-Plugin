<?php
/**
 * Assignment Service
 *
 * Gestión de asignaciones participante → wave dentro de estudios longitudinales.
 *
 * @package EIPSI_Forms
 * @since 1.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class EIPSI_Assignment_Service {

    /**
     * Crear asignación
     *
     * Crea (o asegura) la asignación de un participante a una wave.
     *
     * Nota: es idempotente por UNIQUE(wave_id, participant_id).
     *
     * @param int $study_id ID del estudio
     * @param int $wave_id ID de la wave
     * @param int $participant_id ID del participante
     * @return int|array|WP_Error Insert ID (int) si se creó, asignación (array) si ya existía, o WP_Error
     */
    public static function create_assignment($study_id, $wave_id, $participant_id) {
        global $wpdb;

        $study_id = absint($study_id);
        $wave_id = absint($wave_id);
        $participant_id = absint($participant_id);

        if (!$study_id || !$wave_id || !$participant_id) {
            return new WP_Error('invalid_params', 'study_id, wave_id and participant_id are required');
        }

        // Validar que la wave exista (y pertenezca al estudio)
        $wave_row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, study_id FROM {$wpdb->prefix}survey_waves WHERE id = %d",
                $wave_id
            ),
            ARRAY_A
        );

        if (empty($wave_row)) {
            return new WP_Error('wave_not_found', 'Wave not found');
        }

        if (!empty($wave_row['study_id']) && (int) $wave_row['study_id'] !== $study_id) {
            return new WP_Error('study_mismatch', 'Wave does not belong to the provided study_id');
        }

        // Validar que el participante exista
        $participant_exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}survey_participants WHERE id = %d",
                $participant_id
            )
        );

        if (!$participant_exists) {
            return new WP_Error('participant_not_found', 'Participant not found');
        }

        // Insertar asignación
        $result = $wpdb->insert(
            $wpdb->prefix . 'survey_assignments',
            array(
                'study_id' => $study_id,
                'wave_id' => $wave_id,
                'participant_id' => $participant_id,
                'status' => 'pending',
            ),
            array('%d', '%d', '%d', '%s')
        );

        if ($result === false) {
            // Si es duplicate, ok (idempotente)
            if (strpos($wpdb->last_error, 'Duplicate entry') !== false) {
                $existing = self::get_assignment($wave_id, $participant_id);
                if ($existing) {
                    return $existing;
                }
            }

            return new WP_Error('db_error', 'Failed to create assignment: ' . $wpdb->last_error);
        }

        return (int) $wpdb->insert_id;
    }

    /**
     * Obtener asignación
     *
     * @param int $wave_id ID de la wave
     * @param int $participant_id ID del participante
     * @return array|null
     */
    public static function get_assignment($wave_id, $participant_id) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}survey_assignments WHERE wave_id = %d AND participant_id = %d",
                absint($wave_id),
                absint($participant_id)
            ),
            ARRAY_A
        );
    }

    /**
     * Obtener asignaciones de un participante
     *
     * @param int $participant_id ID del participante
     * @param int|null $study_id Opcional: filtrar por estudio
     * @return array
     */
    public static function get_participant_assignments($participant_id, $study_id = null) {
        global $wpdb;

        $query = "SELECT a.*, w.name, w.due_date, w.wave_index
                  FROM {$wpdb->prefix}survey_assignments a
                  JOIN {$wpdb->prefix}survey_waves w ON a.wave_id = w.id
                  WHERE a.participant_id = %d";

        $params = array(absint($participant_id));

        if (!is_null($study_id)) {
            $query .= ' AND a.study_id = %d';
            $params[] = absint($study_id);
        }

        $query .= ' ORDER BY w.wave_index ASC';

        return $wpdb->get_results(
            $wpdb->prepare($query, $params),
            ARRAY_A
        );
    }

    /**
     * Actualizar estado de asignación
     *
     * - Si pasa a "submitted", setea submitted_at.
     * - Si pasa a "in_progress" y no había first_viewed_at, setea first_viewed_at.
     *
     * @param int $wave_id ID de la wave
     * @param int $participant_id ID del participante
     * @param string $status Nuevo estado
     * @return bool|WP_Error
     */
    public static function update_assignment_status($wave_id, $participant_id, $status) {
        global $wpdb;

        $allowed_statuses = array('pending', 'in_progress', 'submitted', 'skipped', 'expired');

        $status = sanitize_text_field($status);
        if (!in_array($status, $allowed_statuses, true)) {
            return new WP_Error('invalid_status', 'Invalid status');
        }

        $wave_id = absint($wave_id);
        $participant_id = absint($participant_id);

        $data = array('status' => $status);
        $formats = array('%s');

        if ($status === 'submitted') {
            $data['submitted_at'] = current_time('mysql');
            $formats[] = '%s';
        }

        if ($status === 'in_progress') {
            $first_viewed_at = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT first_viewed_at FROM {$wpdb->prefix}survey_assignments WHERE wave_id = %d AND participant_id = %d",
                    $wave_id,
                    $participant_id
                )
            );

            if (empty($first_viewed_at)) {
                $data['first_viewed_at'] = current_time('mysql');
                $formats[] = '%s';
            }
        }

        $updated = $wpdb->update(
            $wpdb->prefix . 'survey_assignments',
            $data,
            array(
                'wave_id' => $wave_id,
                'participant_id' => $participant_id,
            ),
            $formats,
            array('%d', '%d')
        );

        if ($updated === false) {
            return new WP_Error('db_error', 'Failed to update assignment: ' . $wpdb->last_error);
        }

        return true;
    }

    /**
     * Incrementar contador de recordatorios
     *
     * @param int $wave_id ID de la wave
     * @param int $participant_id ID del participante
     * @return bool
     */
    public static function increment_reminder_count($wave_id, $participant_id) {
        global $wpdb;

        $updated = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$wpdb->prefix}survey_assignments
                 SET reminder_count = reminder_count + 1,
                     last_reminder_sent = %s
                 WHERE wave_id = %d AND participant_id = %d",
                current_time('mysql'),
                absint($wave_id),
                absint($participant_id)
            )
        );

        return $updated !== false;
    }

    /**
     * Obtener participantes en riesgo (para Dropout Management)
     *
     * @param int $study_id ID del estudio
     * @param int $days_overdue Días de retraso para considerar en riesgo (default: 7)
     * @return array Lista de participantes con info de wave y retraso
     */
    public static function get_at_risk_participants($study_id = 0, $days_overdue = 7) {
        global $wpdb;

        $assignments_table = $wpdb->prefix . 'survey_assignments';
        $participants_table = $wpdb->prefix . 'survey_participants';
        $waves_table = $wpdb->prefix . 'survey_waves';

        $where_clause = "WHERE a.status = 'pending' AND a.due_at < DATE_SUB(NOW(), INTERVAL %d DAY)";
        $params = array((int) $days_overdue);

        if ($study_id > 0) {
            $where_clause .= " AND a.study_id = %d";
            $params[] = (int) $study_id;
        }

        // Query: participante + wave + assignment info
        $query = "SELECT a.id as assignment_id,
                         a.wave_id,
                         a.participant_id,
                         a.due_at,
                         p.first_name,
                         p.last_name,
                         p.email,
                         p.is_active,
                         w.name as wave_name,
                         w.wave_index,
                         COALESCE(MAX(s.created_at), a.due_at) as last_activity_at
                  FROM {$assignments_table} a
                  JOIN {$participants_table} p ON a.participant_id = p.id
                  JOIN {$waves_table} w ON a.wave_id = w.id
                  LEFT JOIN {$wpdb->prefix}survey_sessions s ON s.participant_id = p.id
                  {$where_clause}
                  GROUP BY a.id, a.wave_id, a.participant_id, a.due_at, p.first_name, p.last_name, p.email, p.is_active, w.name, w.wave_index
                  ORDER BY a.due_at ASC";

        return $wpdb->get_results($wpdb->prepare($query, $params));
    }

    /**
     * Obtener estadísticas de dropout
     *
     * @param int $study_id ID del estudio
     * @param int $days_overdue Días de retraso
     * @return array {at_risk, pending, reminders_today}
     */
    public static function get_dropout_stats($study_id = 0, $days_overdue = 7) {
        global $wpdb;

        $assignments_table = $wpdb->prefix . 'survey_assignments';
        $where = $study_id > 0 ? "WHERE study_id = %d" : "";
        $params = $study_id > 0 ? array((int) $study_id) : array();

        // At risk (pending + overdue)
        $at_risk_where = $where ? $where . " AND " : "WHERE ";
        $at_risk_where .= "status = 'pending' AND due_at < DATE_SUB(NOW(), INTERVAL %d DAY)";
        $params = array_merge($params, array((int) $days_overdue));

        $at_risk = (int) $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM {$assignments_table} {$at_risk_where}", $params)
        );

        // Pending total
        $pending_where = $where ? $where . " AND " : "WHERE ";
        $pending_where .= "status = 'pending'";
        $pending_params = $study_id > 0 ? array((int) $study_id) : array();

        $pending = (int) $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM {$assignments_table} {$pending_where}", $pending_params)
        );

        // Reminders sent today
        $email_log_table = $wpdb->prefix . 'survey_email_log';
        $email_where = $where ? str_replace('study_id', 'survey_id', $where) . " AND " : "WHERE ";
        $email_where .= "DATE(sent_at) = CURDATE() AND email_type IN ('reminder', 'recovery')";
        $email_params = $study_id > 0 ? array((int) $study_id) : array();

        $reminders_today = (int) $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM {$email_log_table} {$email_where}", $email_params)
        );

        return array(
            'at_risk' => $at_risk,
            'pending' => $pending,
            'reminders_today' => $reminders_today
        );
    }

    /**
     * Extender vencimiento de asignación
     *
     * @param int $assignment_id ID de la asignación
     * @param int $days Días a extender
     * @return bool|WP_Error
     */
    public static function extend_wave_deadline($assignment_id, $days = 7) {
        global $wpdb;

        $assignment_id = absint($assignment_id);
        $days = absint($days);

        if (!$assignment_id || $days < 1) {
            return new WP_Error('invalid_params', 'Invalid assignment_id or days');
        }

        // Get current due_at
        $current_due = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT due_at FROM {$wpdb->prefix}survey_assignments WHERE id = %d",
                $assignment_id
            )
        );

        if (!$current_due) {
            return new WP_Error('assignment_not_found', 'Assignment not found');
        }

        // Calculate new due date
        $new_due_date = date('Y-m-d H:i:s', strtotime($current_due . " +{$days} days"));

        // Update
        $updated = $wpdb->update(
            $wpdb->prefix . 'survey_assignments',
            array('due_at' => $new_due_date),
            array('id' => $assignment_id),
            array('%s'),
            array('%d')
        );

        if ($updated === false) {
            return new WP_Error('db_error', 'Failed to extend deadline: ' . $wpdb->last_error);
        }

        return true;
    }

    /**
     * Marcar wave como completada (manual override)
     *
     * @param int $assignment_id ID de la asignación
     * @return bool|WP_Error
     */
    public static function mark_wave_completed($assignment_id) {
        global $wpdb;

        $assignment_id = absint($assignment_id);

        if (!$assignment_id) {
            return new WP_Error('invalid_params', 'Invalid assignment_id');
        }

        // Get wave_id and participant_id first
        $assignment = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT wave_id, participant_id FROM {$wpdb->prefix}survey_assignments WHERE id = %d",
                $assignment_id
            ),
            ARRAY_A
        );

        if (!$assignment) {
            return new WP_Error('assignment_not_found', 'Assignment not found');
        }

        // Update to submitted
        $updated = $wpdb->update(
            $wpdb->prefix . 'survey_assignments',
            array(
                'status' => 'submitted',
                'submitted_at' => current_time('mysql')
            ),
            array('id' => $assignment_id),
            array('%s', '%s'),
            array('%d')
        );

        if ($updated === false) {
            return new WP_Error('db_error', 'Failed to mark completed: ' . $wpdb->last_error);
        }

        return true;
    }

    /**
     * Desactivar participante
     *
     * @param int $participant_id ID del participante
     * @return bool|WP_Error
     */
    public static function deactivate_participant($participant_id) {
        global $wpdb;

        $participant_id = absint($participant_id);

        if (!$participant_id) {
            return new WP_Error('invalid_params', 'Invalid participant_id');
        }

        $updated = $wpdb->update(
            $wpdb->prefix . 'survey_participants',
            array('is_active' => 0),
            array('id' => $participant_id),
            array('%d'),
            array('%d')
        );

        if ($updated === false) {
            return new WP_Error('db_error', 'Failed to deactivate participant: ' . $wpdb->last_error);
        }

        return true;
    }

    /**
     * Obtener asignación por ID
     *
     * @param int $assignment_id
     * @return array|null
     */
    public static function get_assignment_by_id($assignment_id) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}survey_assignments WHERE id = %d",
                absint($assignment_id)
            ),
            ARRAY_A
        );
    }
}
