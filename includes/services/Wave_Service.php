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

        // v2.5.0 - Cancelar eventos programados y jobs en cola para este assignment
        if ($result > 0) {
            // Obtener el assignment_id
            $assignment = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}survey_assignments 
                 WHERE participant_id = %d AND study_id = %d AND wave_id = %d",
                $participant_id,
                $study_id,
                $wave_id
            ));
            
            if ($assignment) {
                // Cancelar eventos programados
                if (class_exists('EIPSI_Nudge_Event_Scheduler')) {
                    EIPSI_Nudge_Event_Scheduler::cancel_scheduled_nudges($assignment->id);
                }
                
                // Cancelar jobs pendientes en la cola
                if (class_exists('EIPSI_Nudge_Job_Queue')) {
                    EIPSI_Nudge_Job_Queue::cancel_jobs_for_assignment($assignment->id);
                }
                
                // Invalidar cache
                if (class_exists('EIPSI_Nudge_Cache')) {
                    EIPSI_Nudge_Cache::invalidate_assignment_cache($assignment->id);
                }
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log(sprintf(
                        '[Wave_Service] Cancelados eventos y jobs para assignment_id=%d',
                        $assignment->id
                    ));
                }
            }
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

        // T1-Anchor System: Use offset_minutes (absolute time from T1)
        $offset_minutes = (int) ($next_wave->offset_minutes ?? 0);

        // Get T1 submission time (first wave completion)
        $t1_assignment = $wpdb->get_row($wpdb->prepare(
            "SELECT submitted_at FROM {$wpdb->prefix}survey_assignments 
             WHERE participant_id = %d AND study_id = %d AND wave_id = (
                 SELECT id FROM {$wpdb->prefix}survey_waves 
                 WHERE study_id = %d AND wave_index = 1 LIMIT 1
             ) AND status = 'submitted'
             ORDER BY submitted_at DESC LIMIT 1",
            $participant_id,
            $study_id,
            $study_id
        ));

        if (!$t1_assignment || !$t1_assignment->submitted_at) {
            error_log("[Wave_Service] Could not find T1 submission time for participant {$participant_id}");
            return;
        }

        // Calculate when the next wave becomes available (offset from T1)
        $t1_submitted_at = strtotime($t1_assignment->submitted_at);
        $available_at = $t1_submitted_at + ($offset_minutes * 60);
        $now = current_time('timestamp');

        // Log the calculation for debugging
        error_log(sprintf(
            '[Wave_Service] Next wave availability check (T1-Anchor): t1_submitted_at=%s, offset_minutes=%d, available_at=%s, now=%s',
            date('Y-m-d H:i:s', $t1_submitted_at),
            $offset_minutes,
            date('Y-m-d H:i:s', $available_at),
            date('Y-m-d H:i:s', $now)
        ));

        // Get or create assignment for the next wave
        $next_assignment = $wpdb->get_row($wpdb->prepare(
            "SELECT id, available_at FROM {$wpdb->prefix}survey_assignments 
             WHERE participant_id = %d AND study_id = %d AND wave_id = %d",
            $participant_id,
            $study_id,
            $next_wave->id
        ));

        // Persist available_at if not already set
        $available_at_formatted = date('Y-m-d H:i:s', $available_at);
        if (!$next_assignment) {
            // Create assignment with available_at
            $wpdb->insert(
                $wpdb->prefix . 'survey_assignments',
                array(
                    'study_id' => $study_id,
                    'wave_id' => $next_wave->id,
                    'participant_id' => $participant_id,
                    'status' => 'pending',
                    'available_at' => $available_at_formatted,
                ),
                array('%d', '%d', '%d', '%s', '%s')
            );
            error_log("[Wave_Service] Created assignment for next wave {$next_wave->id} with available_at: {$available_at_formatted}");
        } elseif (empty($next_assignment->available_at)) {
            // Update existing assignment with available_at
            $wpdb->update(
                $wpdb->prefix . 'survey_assignments',
                array('available_at' => $available_at_formatted),
                array('id' => $next_assignment->id),
                array('%s'),
                array('%d')
            );
            error_log("[Wave_Service] Persisted available_at for assignment {$next_assignment->id}: {$available_at_formatted}");
            
            // v2.5.0 - Trigger event-driven scheduling for follow-up nudges
            do_action('eipsi_wave_available', $next_assignment->id);
        } else {
            error_log("[Wave_Service] Using existing available_at for assignment {$next_assignment->id}: {$next_assignment->available_at}");
        }

        // Only send if the wave is actually available now
        if ($now < $available_at) {
            $wait_hours = ceil(($available_at - $now) / 3600);
            error_log("[Wave_Service] Next wave not available yet. Waiting ~{$wait_hours} hours. Cron will handle it.");
            return;
        }

        // Wave is NOW available - trigger event-driven nudge system
        error_log("[Wave_Service] Next wave is NOW available - triggering event-driven nudge sequence");
        do_action('eipsi_wave_available', $next_assignment->id);
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
