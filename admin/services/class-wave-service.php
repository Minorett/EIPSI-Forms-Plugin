<?php
/**
 * EIPSI_Wave_Service
 *
 * Gestiona waves (oleadas) en estudios longitudinales:
 * - CRUD operations
 * - Due date calculation
 * - Completion tracking
 * - Stats
 *
 * @package EIPSI_Forms
 * @subpackage Services
 * @version 1.4.2
 * @since 1.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class EIPSI_Wave_Service {

    /**
     * Create wave for survey.
     *
     * @param int   $study_id ID del estudio.
     * @param array $wave_data Datos de la wave.
     * @return int|WP_Error Wave ID insertado o WP_Error.
     * @since 1.4.0
     * @access public
     */
    public static function create_wave($study_id, $wave_data) {
        global $wpdb;

        $study_id = absint($study_id);
        if (!$study_id) {
            return new WP_Error('invalid_study_id', 'Invalid study_id');
        }

        $name = isset($wave_data['name']) ? sanitize_text_field($wave_data['name']) : '';
        $form_id = isset($wave_data['form_id']) ? absint($wave_data['form_id']) : 0;

        if (empty($name) || empty($form_id)) {
            return new WP_Error('missing_required_fields', 'Name and form_id required');
        }

        $wave_index = isset($wave_data['wave_index']) ? absint($wave_data['wave_index']) : 1;
        $reminder_days = isset($wave_data['reminder_days']) ? absint($wave_data['reminder_days']) : 3;
        $retry_enabled = isset($wave_data['retry_enabled']) ? (int) (bool) $wave_data['retry_enabled'] : 1;
        $retry_days = isset($wave_data['retry_days']) ? absint($wave_data['retry_days']) : 7;
        $max_retries = isset($wave_data['max_retries']) ? absint($wave_data['max_retries']) : 3;
        $is_mandatory = isset($wave_data['is_mandatory']) ? (int) (bool) $wave_data['is_mandatory'] : 1;

        $allowed_statuses = array('draft', 'active', 'completed', 'paused');
        $status = isset($wave_data['status']) ? sanitize_text_field($wave_data['status']) : 'draft';
        if (!in_array($status, $allowed_statuses, true)) {
            $status = 'draft';
        }

        $data = array(
            'study_id' => $study_id,
            'wave_index' => $wave_index,
            'name' => $name,
            'form_id' => $form_id,
            'reminder_days' => $reminder_days,
            'retry_enabled' => $retry_enabled,
            'retry_days' => $retry_days,
            'max_retries' => $max_retries,
            'status' => $status,
            'is_mandatory' => $is_mandatory,
        );

        $formats = array('%d', '%d', '%s', '%d', '%d', '%d', '%d', '%d', '%s', '%d');

        if (!empty($wave_data['start_date'])) {
            $data['start_date'] = sanitize_text_field($wave_data['start_date']);
            $formats[] = '%s';
        }

        if (!empty($wave_data['due_date'])) {
            $data['due_date'] = sanitize_text_field($wave_data['due_date']);
            $formats[] = '%s';
        }

        $result = $wpdb->insert(
            $wpdb->prefix . 'survey_waves',
            $data,
            $formats
        );

        if ($result === false) {
            return new WP_Error('db_error', 'Failed to create wave: ' . $wpdb->last_error);
        }

        return (int) $wpdb->insert_id;
    }

    /**
     * Get wave by ID.
     *
     * @param int $wave_id Wave ID.
     * @return array|null Wave data.
     * @since 1.4.0
     * @access public
     */
    public static function get_wave($wave_id) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}survey_waves WHERE id = %d",
                absint($wave_id)
            ),
            ARRAY_A
        );
    }

    /**
     * Get study waves.
     *
     * @param int         $study_id Study ID.
     * @param string|null $status Status filter.
     * @return array Waves list.
     * @since 1.4.0
     * @access public
     */
    public static function get_study_waves($study_id, $status = null) {
        global $wpdb;

        $query = "SELECT * FROM {$wpdb->prefix}survey_waves WHERE study_id = %d";
        $params = array(absint($study_id));

        if (!empty($status)) {
            $query .= ' AND status = %s';
            $params[] = sanitize_text_field($status);
        }

        $query .= ' ORDER BY wave_index ASC';

        return $wpdb->get_results(
            $wpdb->prepare($query, $params),
            ARRAY_A
        );
    }

    /**
     * Update wave.
     *
     * @param int   $wave_id Wave ID.
     * @param array $wave_data Datos a actualizar.
     * @return bool|WP_Error True si actualiza o WP_Error si falla.
     * @since 1.4.0
     * @access public
     */
    public static function update_wave($wave_id, $wave_data) {
        global $wpdb;

        $wave_id = absint($wave_id);
        if (!$wave_id) {
            return new WP_Error('invalid_wave_id', 'Invalid wave_id');
        }

        $allowed_fields = array(
            'name',
            'form_id',
            'start_date',
            'due_date',
            'reminder_days',
            'retry_enabled',
            'retry_days',
            'max_retries',
            'status',
            'is_mandatory',
            'has_time_limit',
            'completion_time_limit',
        );

        $data = array();
        $formats = array();

        foreach ((array) $wave_data as $key => $value) {
            if (!in_array($key, $allowed_fields, true)) {
                continue;
            }

            switch ($key) {
                case 'name':
                    $data[$key] = sanitize_text_field($value);
                    $formats[] = '%s';
                    break;
                case 'form_id':
                case 'reminder_days':
                case 'retry_days':
                case 'max_retries':
                    $data[$key] = absint($value);
                    $formats[] = '%d';
                    break;
                case 'retry_enabled':
                case 'is_mandatory':
                case 'has_time_limit':
                    $data[$key] = (int) (bool) $value;
                    $formats[] = '%d';
                    break;
                case 'completion_time_limit':
                    $data[$key] = $value === null ? null : absint($value);
                    $formats[] = $value === null ? null : '%d';
                    break;
                case 'status':
                    $allowed_statuses = array('draft', 'active', 'completed', 'paused');
                    $value = sanitize_text_field($value);
                    if (!in_array($value, $allowed_statuses, true)) {
                        return new WP_Error('invalid_status', 'Invalid status');
                    }
                    $data[$key] = $value;
                    $formats[] = '%s';
                    break;
                case 'start_date':
                case 'due_date':
                    // Permitimos NULL (si viene vacío, no actualizamos)
                    if ($value === null || $value === '') {
                        continue 2;
                    }
                    $data[$key] = sanitize_text_field($value);
                    $formats[] = '%s';
                    break;
            }
        }

        if (empty($data)) {
            return false;
        }

        $updated = $wpdb->update(
            $wpdb->prefix . 'survey_waves',
            $data,
            array('id' => $wave_id),
            $formats,
            array('%d')
        );

        if ($updated === false) {
            return new WP_Error('db_error', 'Failed to update wave: ' . $wpdb->last_error);
        }

        return true;
    }

    /**
     * Get wave completion stats.
     *
     * @param int $wave_id Wave ID.
     * @return array Stats array.
     * @since 1.4.2
     * @access public
     */
    public static function get_wave_stats($wave_id) {
        global $wpdb;

        $stats = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) as submitted,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired
                FROM {$wpdb->prefix}survey_assignments
                WHERE wave_id = %d",
                absint($wave_id)
            ),
            ARRAY_A
        );

        if (!is_array($stats)) {
            return array(
                'total' => 0,
                'submitted' => 0,
                'pending' => 0,
                'in_progress' => 0,
                'expired' => 0,
            );
        }

        foreach ($stats as $k => $v) {
            $stats[$k] = (int) $v;
        }

        return $stats;
    }

    /**
     * Delete wave with validation.
     *
     * No permite borrar una wave con asignaciones ya submitted.
     *
     * @param int $wave_id Wave ID.
     * @return bool|WP_Error True si elimina o WP_Error si falla.
     * @since 1.4.0
     * @access public
     */
    public static function delete_wave($wave_id) {
        global $wpdb;

        $wave_id = absint($wave_id);
        if (!$wave_id) {
            return new WP_Error('invalid_wave_id', 'Invalid wave_id');
        }

        $has_responses = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}survey_assignments
                 WHERE wave_id = %d AND status = 'submitted'",
                $wave_id
            )
        );

        if ($has_responses > 0) {
            return new WP_Error('has_responses', 'Cannot delete wave with responses');
        }

        $deleted = $wpdb->delete(
            $wpdb->prefix . 'survey_waves',
            array('id' => $wave_id),
            array('%d')
        );

        if ($deleted === false) {
            return new WP_Error('db_error', 'Failed to delete wave: ' . $wpdb->last_error);
        }

        return true;
    }

    /**
     * Calculate wave status based on dates.
     *
     * @param int|array $wave Wave ID or wave array.
     * @return string Status: upcoming, active, closed, overdue.
     * @since 1.7.1
     * @access public
     */
    public static function calculate_wave_status($wave) {
        global $wpdb;

        // Get wave object if ID passed
        if (is_numeric($wave)) {
            $wave = self::get_wave($wave);
        }

        if (!$wave) {
            return 'unknown';
        }

        $now = current_time('mysql');
        $start_date = !empty($wave->start_date) ? $wave->start_date : $wave->due_date;
        $due_date = !empty($wave->due_date) ? $wave->due_date : null;

        // If no dates set, default to active
        if (empty($start_date) && empty($due_date)) {
            return 'active';
        }

        // upcoming: start_date > today (or no start_date but due_date > today)
        if (!empty($start_date) && strtotime($start_date) > strtotime($now)) {
            return 'upcoming';
        }

        // If we have due_date
        if (!empty($due_date)) {
            // active: start_date <= today <= due_date
            $start_ts = !empty($start_date) ? strtotime($start_date) : 0;
            $due_ts = strtotime($due_date);
            $now_ts = strtotime($now);

            if ($start_ts <= $now_ts && $now_ts <= $due_ts) {
                return 'active';
            }

            // overdue: due_date < today (participant started but didn't complete)
            // This requires checking if there are assignments in progress
            if ($now_ts > $due_ts) {
                $has_in_progress = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}survey_assignments
                     WHERE wave_id = %d AND status = 'in_progress'",
                    $wave->id
                ));

                if ($has_in_progress > 0) {
                    return 'overdue';
                }

                return 'closed';
            }
        }

        // Default if only start_date exists and we're past it
        if (!empty($start_date) && strtotime($start_date) <= strtotime($now)) {
            // Check if there are pending assignments
            $has_pending = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}survey_assignments
                 WHERE wave_id = %d AND status = 'pending'",
                $wave->id
            ));

            if ($has_pending > 0) {
                return 'active';
            }
        }

        return 'active';
    }

    /**
     * Validate wave dates before saving.
     *
     * @param array $wave_data Wave data to validate.
     * @param int   $study_id Study ID.
     * @param int   $exclude_wave_id Wave ID to exclude (for updates).
     * @return array {valid: bool, warnings: array, errors: array}
     * @since 1.7.1
     * @access public
     */
    public static function validate_wave_dates($wave_data, $study_id, $exclude_wave_id = 0) {
        global $wpdb;
        
        $warnings = array();
        $errors = array();

        $start_date = isset($wave_data['start_date']) ? $wave_data['start_date'] : null;
        $due_date = isset($wave_data['due_date']) ? $wave_data['due_date'] : null;
        $wave_index = isset($wave_data['wave_index']) ? absint($wave_data['wave_index']) : 1;
        $is_new = empty($exclude_wave_id);

        // Validate: Due date > start date
        if (!empty($start_date) && !empty($due_date)) {
            if (strtotime($due_date) <= strtotime($start_date)) {
                $errors[] = __('La fecha de vencimiento debe ser posterior a la fecha de inicio.', 'eipsi-forms');
            }
        }

        // Validate: Start date not in past for new waves
        if ($is_new && !empty($start_date)) {
            $now = current_time('mysql');
            if (strtotime($start_date) < strtotime($now . ' -1 day')) {
                $warnings[] = __('La fecha de inicio está en el pasado. Los participantes no podrán acceder hasta esa fecha.', 'eipsi-forms');
            }
        }

        // Validate: Wave N+1 start date > Wave N end date
        if (!empty($wave_index)) {
            // Get previous wave
            $previous_wave = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}survey_waves
                 WHERE study_id = %d AND wave_index < %d
                 ORDER BY wave_index DESC LIMIT 1",
                $study_id,
                $wave_index
            ));

            // Get next wave
            $next_wave = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}survey_waves
                 WHERE study_id = %d AND wave_index > %d AND id != %d
                 ORDER BY wave_index ASC LIMIT 1",
                $study_id,
                $wave_index,
                $exclude_wave_id
            ));

            // Check: This wave start should be > previous wave end (due_date)
            if ($previous_wave && !empty($previous_wave->due_date)) {
                if (!empty($start_date) && strtotime($start_date) <= strtotime($previous_wave->due_date)) {
                    $warnings[] = sprintf(
                        __('La fecha de inicio de la Onda T%d debería ser posterior a la fecha de vencimiento de la Onda T%d (%s).', 'eipsi-forms'),
                        $wave_index,
                        $previous_wave->wave_index,
                        date_i18n(get_option('date_format'), strtotime($previous_wave->due_date))
                    );
                }
            }

            // Check: Next wave start should be > this wave end (due_date)
            if ($next_wave && !empty($next_wave->start_date)) {
                if (!empty($due_date) && strtotime($next_wave->start_date) <= strtotime($due_date)) {
                    $warnings[] = sprintf(
                        __('La fecha de inicio de la Onda T%d (%s) debería ser posterior a la fecha de vencimiento de esta onda (%s).', 'eipsi-forms'),
                        $next_wave->wave_index,
                        date_i18n(get_option('date_format'), strtotime($next_wave->start_date)),
                        !empty($due_date) ? date_i18n(get_option('date_format'), strtotime($due_date)) : 'no establecida'
                    );
                }
            }
        }

        return array(
            'valid' => empty($errors),
            'warnings' => $warnings,
            'errors' => $errors
        );
    }

    /**
     * Update wave status based on current dates.
     *
     * @param int $wave_id Wave ID.
     * @return bool True if updated.
     * @since 1.7.1
     * @access public
     */
    public static function update_wave_status($wave_id) {
        global $wpdb;

        $wave = self::get_wave($wave_id);
        if (!$wave) {
            return false;
        }

        $status = self::calculate_wave_status($wave);

        // Map our status to DB status
        $db_status = $status;
        if ($status === 'upcoming') {
            $db_status = 'draft';
        } elseif ($status === 'overdue') {
            $db_status = 'active';
        }

        $result = $wpdb->update(
            $wpdb->prefix . 'survey_waves',
            array('status' => $db_status),
            array('id' => $wave_id),
            array('%s'),
            array('%d')
        );

        return $result !== false;
    }

    /**
     * Update all wave statuses for a study via cron.
     *
     * @param int $study_id Study ID. If 0, update all.
     * @return array Results.
     * @since 1.7.1
     * @access public
     */
    public static function update_all_wave_statuses($study_id = 0) {
        global $wpdb;

        $query = "SELECT id FROM {$wpdb->prefix}survey_waves";
        $params = array();

        if ($study_id > 0) {
            $query .= " WHERE study_id = %d";
            $params[] = $study_id;
        }

        $waves = $wpdb->get_results($wpdb->prepare($query, $params));

        $updated = 0;
        $failed = 0;

        foreach ($waves as $wave) {
            if (self::update_wave_status($wave->id)) {
                $updated++;
            } else {
                $failed++;
            }
        }

        return array(
            'updated' => $updated,
            'failed' => $failed,
            'total' => count($waves)
        );
    }
}
