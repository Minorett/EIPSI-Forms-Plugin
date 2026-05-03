<?php
/**
 * Wave Eligibility Service
 * 
 * Determina si un participante es elegible para una wave específica
 * basándose en si completó la wave anterior (para waves > 1)
 * 
 * @since 2.3.0
 * @package EIPSI Forms
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class EIPSI_Wave_Eligibility_Service
 */
class EIPSI_Wave_Eligibility_Service {
    
    /**
     * Verificar si un participante es elegible para una wave
     * 
     * @param int $participant_id ID del participante
     * @param int $wave_id ID de la wave
     * @param int $study_id ID del estudio
     * @return bool True si es elegible
     */
    public static function is_eligible_for_wave($participant_id, $wave_id, $study_id) {
        global $wpdb;
        
        // Obtener wave_index de la wave actual
        $current_wave = $wpdb->get_row($wpdb->prepare(
            "SELECT wave_index FROM {$wpdb->prefix}survey_waves 
             WHERE id = %d AND study_id = %d",
            $wave_id, 
            $study_id
        ));
        
        if (!$current_wave) {
            error_log("[EIPSI Eligibility] Wave {$wave_id} no encontrada para estudio {$study_id}");
            return false;
        }
        
        // Wave 1 (o índice 0/1) no tiene prerequisitos
        if ($current_wave->wave_index <= 1) {
            return true;
        }
        
        // Verificar si completó la wave anterior
        $previous_completed = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}survey_assignments a
             JOIN {$wpdb->prefix}survey_waves w ON a.wave_id = w.id
             WHERE a.participant_id = %d
             AND w.study_id = %d
             AND w.wave_index = %d
             AND a.status = 'submitted'",
            $participant_id,
            $study_id,
            $current_wave->wave_index - 1
        ));
        
        $is_eligible = (bool) $previous_completed;
        
        error_log("[EIPSI Eligibility] Participant {$participant_id} for wave {$wave_id}: " . ($is_eligible ? 'ELIGIBLE' : 'NOT ELIGIBLE'));
        
        return $is_eligible;
    }
    
    /**
     * Obtener IDs de participantes elegibles para una wave
     * 
     * @param int $wave_id ID de la wave
     * @param int $study_id ID del estudio
     * @param array $candidate_ids Opcional: filtrar solo estos IDs
     * @return array Array de participant_ids elegibles
     */
    public static function get_eligible_participants($wave_id, $study_id, $candidate_ids = null) {
        global $wpdb;
        
        // Obtener wave_index
        $current_wave = $wpdb->get_row($wpdb->prepare(
            "SELECT wave_index FROM {$wpdb->prefix}survey_waves 
             WHERE id = %d AND study_id = %d",
            $wave_id, 
            $study_id
        ));
        
        if (!$current_wave) {
            error_log("[EIPSI Eligibility] Wave {$wave_id} no encontrada");
            return array();
        }
        
        // Wave 1: todos los candidatos son elegibles
        if ($current_wave->wave_index <= 1) {
            error_log("[EIPSI Eligibility] Wave {$wave_id} es wave_index={$current_wave->wave_index}, todos elegibles");
            return $candidate_ids ? $candidate_ids : array();
        }
        
        // Construir query para obtener quienes completaron la wave anterior
        $previous_wave_index = $current_wave->wave_index - 1;
        
        if ($candidate_ids && !empty($candidate_ids)) {
            // Filtrar solo entre candidatos específicos
            $placeholders = implode(',', array_fill(0, count($candidate_ids), '%d'));
            $sql = $wpdb->prepare(
                "SELECT DISTINCT a.participant_id 
                 FROM {$wpdb->prefix}survey_assignments a
                 JOIN {$wpdb->prefix}survey_waves w ON a.wave_id = w.id
                 WHERE w.study_id = %d
                 AND w.wave_index = %d
                 AND a.status = 'submitted'
                 AND a.participant_id IN ($placeholders)",
                array_merge(
                    array($study_id, $previous_wave_index),
                    $candidate_ids
                )
            );
        } else {
            // Buscar entre todos los participantes
            $sql = $wpdb->prepare(
                "SELECT DISTINCT a.participant_id 
                 FROM {$wpdb->prefix}survey_assignments a
                 JOIN {$wpdb->prefix}survey_waves w ON a.wave_id = w.id
                 WHERE w.study_id = %d
                 AND w.wave_index = %d
                 AND a.status = 'submitted'",
                $study_id,
                $previous_wave_index
            );
        }
        
        $eligible_ids = $wpdb->get_col($sql);
        
        error_log("[EIPSI Eligibility] Wave {$wave_id} (index {$current_wave->wave_index}): " . 
                  ($candidate_ids ? count($candidate_ids) : 'todos') . " candidatos, " . 
                  count($eligible_ids) . " elegibles (completaron wave {$previous_wave_index})");
        
        return $eligible_ids;
    }
    
    /**
     * Filtrar candidatos pendientes por elegibilidad
     * 
     * @param int $wave_id ID de la wave
     * @param int $study_id ID del estudio
     * @param array $pending_ids IDs de participantes pendientes
     * @return array IDs filtrados (solo elegibles)
     */
    public static function filter_pending_by_eligibility($wave_id, $study_id, $pending_ids) {
        if (empty($pending_ids)) {
            return array();
        }
        
        $eligible_ids = self::get_eligible_participants($wave_id, $study_id, $pending_ids);
        
        // Log de quiénes fueron filtrados
        $filtered_out = array_diff($pending_ids, $eligible_ids);
        if (!empty($filtered_out)) {
            error_log("[EIPSI Eligibility] Filtrados (no elegibles): " . implode(',', $filtered_out));
        }
        
        return $eligible_ids;
    }
}
