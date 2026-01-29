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
}
