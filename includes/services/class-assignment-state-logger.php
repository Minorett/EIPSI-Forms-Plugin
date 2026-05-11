<?php
/**
 * Assignment State Logger
 * 
 * Servicio centralizado para logging de cambios de estado de assignments.
 * Muestra solo cuando hay transiciones de estado, no logs constantes.
 * 
 * Formato de log:
 * [EIPSI WAVE STATUS] Participant X | T1: completed | T2: available | T3: pending
 * 
 * @package EIPSI_Forms
 * @since 2.6.2
 */

if (!defined('ABSPATH')) {
    exit;
}

class EIPSI_Assignment_State_Logger {
    
    /**
     * Cache de estados previos por participante
     * Formato: [participant_id => 'T1:completed|T2:available|T3:pending']
     */
    private static $state_cache = array();
    
    /**
     * Log del estado actual de todos los assignments de un participante
     * Solo loguea si hubo cambios desde el último log
     * 
     * @param int $participant_id ID del participante
     * @param int $study_id ID del estudio
     * @param string $trigger Evento que disparó el log (ej: 'submission', 'expiration', 'availability')
     */
    public static function log_participant_state($participant_id, $study_id, $trigger = '') {
        global $wpdb;
        
        // Obtener todos los assignments del participante ordenados por wave_index
        $assignments = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                a.id,
                a.wave_id,
                a.status,
                w.wave_index,
                w.name as wave_name
             FROM {$wpdb->prefix}survey_assignments a
             JOIN {$wpdb->prefix}survey_waves w ON a.wave_id = w.id
             WHERE a.participant_id = %d 
             AND a.study_id = %d
             ORDER BY w.wave_index ASC",
            $participant_id,
            $study_id
        ));
        
        if (empty($assignments)) {
            return;
        }
        
        // Construir string de estado actual
        $state_parts = array();
        foreach ($assignments as $assignment) {
            $state_parts[] = sprintf('T%d:%s', $assignment->wave_index, $assignment->status);
        }
        $current_state = implode('|', $state_parts);
        
        // Verificar si cambió desde el último log
        $cache_key = $participant_id . '_' . $study_id;
        $previous_state = isset(self::$state_cache[$cache_key]) ? self::$state_cache[$cache_key] : null;
        
        if ($current_state === $previous_state) {
            // No hubo cambios, no loguear
            return;
        }
        
        // Actualizar cache
        self::$state_cache[$cache_key] = $current_state;
        
        // Construir mensaje de log legible
        $wave_statuses = array();
        foreach ($assignments as $assignment) {
            $wave_statuses[] = sprintf('T%d: %s', $assignment->wave_index, $assignment->status);
        }
        
        $trigger_msg = $trigger ? " [{$trigger}]" : '';
        
        error_log(sprintf(
            '[EIPSI WAVE STATUS]%s Participant %d | %s',
            $trigger_msg,
            $participant_id,
            implode(' | ', $wave_statuses)
        ));
    }
    
    /**
     * Log cuando un assignment cambia de estado
     * Wrapper conveniente que loguea el estado completo del participante
     * 
     * @param int $assignment_id ID del assignment
     * @param string $old_status Estado anterior
     * @param string $new_status Estado nuevo
     */
    public static function log_assignment_change($assignment_id, $old_status, $new_status) {
        global $wpdb;
        
        // Obtener info del assignment
        $assignment = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                a.participant_id,
                a.study_id,
                w.wave_index
             FROM {$wpdb->prefix}survey_assignments a
             JOIN {$wpdb->prefix}survey_waves w ON a.wave_id = w.id
             WHERE a.id = %d",
            $assignment_id
        ));
        
        if (!$assignment) {
            return;
        }
        
        $trigger = sprintf('T%d: %s→%s', $assignment->wave_index, $old_status, $new_status);
        self::log_participant_state($assignment->participant_id, $assignment->study_id, $trigger);
    }
    
    /**
     * Limpiar cache (útil para testing)
     */
    public static function clear_cache() {
        self::$state_cache = array();
    }
}
