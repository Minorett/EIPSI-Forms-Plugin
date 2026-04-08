<?php
/**
 * Wave Service - Gestión de tomas longitudinales
 * 
 * Maneja lógica de negocio relacionada con waves (tomas) en estudios longitudinales
 * 
 * @package EIPSI_Forms
 * @since 1.4.0
 */

if (!defined('ABSPATH')) {
    exit;
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
        
        return true; // Devolvemos true aunque result sea 0 (ya estaba submitted)
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
