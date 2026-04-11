<?php
/**
 * Wave Service - Gestión de tomas longitudinales
 * 
 * Maneja lógica de negocio relacionada con waves (tomas) en estudios longitudinales
 * 
 * @package EIPSI_Forms
 * @since 1.4.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Ensure email service is available for reminder functions
if (!class_exists('EIPSI_Email_Service')) {
    require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/services/class-email-service.php';
}

class Wave_Service {
    
    /**
     * Obtener próxima toma pendiente para un participante
     * 
     * @param int $participant_id ID del participante
     * @param int $study_id ID del estudio
     * @return array|null Datos de la próxima wave o null si no hay más
     */
    public static function get_next_pending_wave($participant_id, $study_id) {
        global $wpdb;
        
        if (!$participant_id || !$study_id) {
            return null;
        }
        
        // Query para obtener la próxima toma pendiente (status='pending')
        $table = $wpdb->prefix . 'survey_assignments';
        
        $next_wave = $wpdb->get_row($wpdb->prepare(
            "SELECT a.*, w.wave_index, w.due_date, w.name as wave_name
             FROM {$table} a
             INNER JOIN {$wpdb->prefix}survey_waves w ON a.wave_id = w.id
             WHERE a.participant_id = %d 
             AND a.study_id = %d 
             AND a.status = 'pending'
             ORDER BY w.wave_index ASC
             LIMIT 1",
            $participant_id,
            $study_id
        ), ARRAY_A);
        
        if (!$next_wave) {
            return null;
        }
        
        return array(
            'wave_id' => (int) $next_wave['wave_id'],
            'wave_index' => (int) $next_wave['wave_index'],
            'due_date' => $next_wave['due_date'],
            'wave_name' => $next_wave['wave_name'] ?? sprintf('Toma %d', $next_wave['wave_index']),
            'study_id' => (int) $study_id
        );
    }
    
    /**
     * Obtener todas las tomas de un participante
     * 
     * @param int $participant_id
     * @param int $study_id
     * @return array
     */
    public static function get_participant_waves($participant_id, $study_id) {
        global $wpdb;
        
        if (!$participant_id || !$study_id) {
            return array();
        }
        
        $table = $wpdb->prefix . 'survey_assignments';
        
        $waves = $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, w.wave_index, w.due_date, w.name as wave_name
             FROM {$table} a
             INNER JOIN {$wpdb->prefix}survey_waves w ON a.wave_id = w.id
             WHERE a.participant_id = %d 
             AND a.study_id = %d
             ORDER BY w.wave_index ASC",
            $participant_id,
            $study_id
        ), ARRAY_A);
        
        return $waves;
    }
    
    /**
     * Marcar assignment como completado
     * 
     * @param int $participant_id
     * @param int $study_id
     * @param int $wave_id
     * @return bool
     */
    public static function mark_assignment_submitted($participant_id, $study_id, $wave_id) {
        global $wpdb;
        
        if (!$participant_id || !$study_id || !$wave_id) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[Wave_Service] mark_assignment_submitted: Parámetros inválidos');
            }
            return false;
        }
        
        $table = $wpdb->prefix . 'survey_assignments';
        
        $result = $wpdb->update(
            $table,
            array(
                'status'       => 'submitted',
                'submitted_at' => current_time( 'mysql' ),
                'updated_at'   => current_time( 'mysql' )
            ),
            array(
                'participant_id' => $participant_id,
                'study_id'       => $study_id,
                'wave_id'        => $wave_id
            ),
            array( '%s', '%s', '%s' ), // format for values
            array( '%d', '%d', '%d' )  // format for where
        );
        
        if ($result === false) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf(
                    '[Wave_Service] Error al actualizar assignment: participant_id=%d, study_id=%d, wave_id=%d - Error: %s',
                    $participant_id,
                    $study_id,
                    $wave_id,
                    $wpdb->last_error
                ));
            }
            return false;
        }
        
        // Si result = 0, puede significar que no existe o ya estaba en 'submitted'
        // Log informativo
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                '[Wave_Service] Assignment marcado como submitted: participant_id=%d, study_id=%d, wave_id=%d (affected rows: %d)',
                $participant_id,
                $study_id,
                $wave_id,
                $result
            ));
        }

        // v2.1.3: Trigger immediate email for minute-based intervals
        // If next wave has minutes interval and is immediately available, send email now
        if ($result !== false) {
            self::maybe_send_immediate_wave_reminder($participant_id, $study_id, $wave_id);
        }

        return true; // Devolvemos true aunque result sea 0 (ya estaba submitted)
    }

    /**
     * Send immediate reminder when next wave becomes available
     * Works for any time unit (minutes, hours, days) - sends email immediately when wave is ready
     *
     * @param int $participant_id
     * @param int $study_id
     * @param int $completed_wave_id
     */
    private static function maybe_send_immediate_wave_reminder($participant_id, $study_id, $completed_wave_id) {
        global $wpdb;

        // Get the completed wave info
        $completed_wave = $wpdb->get_row($wpdb->prepare(
            "SELECT wave_index FROM {$wpdb->prefix}survey_waves WHERE id = %d",
            $completed_wave_id
        ));

        if (!$completed_wave) {
            return;
        }

        // Find next wave
        $next_wave = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}survey_waves
             WHERE study_id = %d AND wave_index = %d
             ORDER BY wave_index ASC LIMIT 1",
            $study_id,
            $completed_wave->wave_index + 1
        ));

        if (!$next_wave) {
            error_log('[Wave_Service] No next wave found after wave ' . $completed_wave->wave_index);
            return;
        }

        // Get time unit and interval
        $time_unit = self::normalize_time_unit($next_wave->time_unit);
        $interval_value = (int) ($next_wave->interval_days ?? 0);

        // Get the submission time of the completed wave
        $completed_assignment = $wpdb->get_row($wpdb->prepare(
            "SELECT submitted_at FROM {$wpdb->prefix}survey_assignments 
             WHERE participant_id = %d AND study_id = %d AND wave_id = %d AND status = 'submitted'
             ORDER BY submitted_at DESC LIMIT 1",
            $participant_id,
            $study_id,
            $completed_wave_id
        ));

        if (!$completed_assignment || !$completed_assignment->submitted_at) {
            error_log("[Wave_Service] Could not find submission time for completed wave {$completed_wave_id}");
            return;
        }

        // Calculate when the next wave becomes available based on time unit
        $submitted_at = strtotime($completed_assignment->submitted_at);
        
        // Convert interval to seconds based on time unit
        $interval_seconds = match($time_unit) {
            'minutes' => $interval_value * 60,
            'hours' => $interval_value * 3600,
            'days' => $interval_value * 86400,
            default => $interval_value * 86400, // default to days
        };
        
        $available_at = $submitted_at + $interval_seconds;
        $now = current_time('timestamp');

        // Log the calculation for debugging
        error_log(sprintf(
            '[Wave_Service] Next wave availability check: submitted_at=%s, interval=%d %s, available_at=%s, now=%s',
            date('Y-m-d H:i:s', $submitted_at),
            $interval_value,
            $time_unit,
            date('Y-m-d H:i:s', $available_at),
            date('Y-m-d H:i:s', $now)
        ));

        // Only send if the wave is actually available now
        if ($now < $available_at) {
            $wait_hours = ceil(($available_at - $now) / 3600);
            error_log("[Wave_Service] Next wave not available yet. Waiting ~{$wait_hours} hours. Cron will handle it.");
            return;
        }

        // Wave is NOW available - send email immediately (Nudge 0)
        error_log("[Wave_Service] Next wave is NOW available - triggering immediate Nudge 0 email");

        // Check if EIPSI_Email_Service is available
        if (!class_exists('EIPSI_Email_Service')) {
            require_once EIPSI_FORMS_PLUGIN_DIR . 'includes/services/class-email-service.php';
        }

        if (!method_exists('EIPSI_Email_Service', 'send_wave_reminder_email')) {
            error_log('[Wave_Service] EIPSI_Email_Service::send_wave_reminder_email not available');
            return;
        }

        // Send the reminder email
        $result = EIPSI_Email_Service::send_wave_reminder_email(
            $study_id,
            $participant_id,
            $next_wave
        );

        if ($result) {
            error_log("[Wave_Service] ✅ Immediate reminder email sent to participant {$participant_id} for wave {$next_wave->id}");

            // Increment reminder count to prevent cron from sending duplicate
            $wpdb->update(
                $wpdb->prefix . 'survey_assignments',
                array('reminder_count' => 1),
                array(
                    'participant_id' => $participant_id,
                    'study_id' => $study_id,
                    'wave_id' => $next_wave->id
                ),
                array('%d'),
                array('%d', '%d', '%d')
            );
        } else {
            error_log("[Wave_Service] ❌ Failed to send immediate reminder to participant {$participant_id}");
        }
    }
    
    /**
     * Verificar si existe un assignment
     * 
     * @param int $participant_id
     * @param int $study_id
     * @param int $wave_id
     * @return bool
     */
    public static function assignment_exists($participant_id, $study_id, $wave_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'survey_assignments';
        
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} 
             WHERE participant_id = %d 
             AND study_id = %d 
             AND wave_id = %d",
            $participant_id,
            $study_id,
            $wave_id
        ));
        
        return (bool) $exists;
    }
    
    /**
     * Obtener status actual de un assignment
     * 
     * @param int $participant_id
     * @param int $study_id
     * @param int $wave_id
     * @return string|null 'pending', 'submitted', 'expired', etc.
     */
    public static function get_assignment_status($participant_id, $study_id, $wave_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'survey_assignments';
        
        $status = $wpdb->get_var($wpdb->prepare(
            "SELECT status FROM {$table} 
             WHERE participant_id = %d 
             AND study_id = %d 
             AND wave_id = %d",
            $participant_id,
            $study_id,
            $wave_id
        ));
        
        return $status;
    }

    /**
     * NORMALIZAR time_unit de forma segura
     * 
     * CRITICAL: Esta función evita el bug de empty(0) === true
     * que causaba que '0' (minutos) se tratara como vacío.
     * 
     * Acepta: '0', '1', '2', 0, 1, 2, 'minutes', 'hours', 'days'
     * Retorna siempre: 'minutes', 'hours', o 'days'
     * 
     * @param mixed $raw_value Valor crudo de time_unit (puede ser int, string, null)
     * @return string 'minutes', 'hours', o 'days' (default: 'days')
     */
    public static function normalize_time_unit($raw_value) {
        // Mapeo de valores numéricos a strings
        $numeric_map = array(
            '0' => 'minutes',
            '1' => 'hours',
            '2' => 'days',
            0   => 'minutes',
            1   => 'hours',
            2   => 'days'
        );
        
        // Si es null o empty string (pero NO 0), usar default
        if ($raw_value === null || $raw_value === '') {
            return 'days';
        }
        
        // Si es numérico (0, 1, 2, '0', '1', '2')
        if (isset($numeric_map[$raw_value])) {
            return $numeric_map[$raw_value];
        }
        
        // Si ya es string válido
        $valid_strings = array('minutes', 'hours', 'days');
        if (in_array($raw_value, $valid_strings, true)) {
            return $raw_value;
        }
        
        // Default fallback
        error_log("[EIPSI WARNING] Unrecognized time_unit value: " . var_export($raw_value, true) . ", using 'days'");
        return 'days';
    }

    /**
     * VALIDAR time_unit al guardar una wave
     * 
     * Usar esta función antes de insertar/actualizar waves para
     * asegurar que time_unit siempre se almacene como string válido.
     * 
     * @param array $wave_data Datos de la wave
     * @return array Datos con time_unit normalizado
     */
    public static function validate_wave_time_unit($wave_data) {
        if (isset($wave_data['time_unit'])) {
            $wave_data['time_unit'] = self::normalize_time_unit($wave_data['time_unit']);
        } else {
            $wave_data['time_unit'] = 'days'; // default
        }
        return $wave_data;
    }
}
