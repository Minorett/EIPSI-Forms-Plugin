<?php
/**
 * EIPSI Wave Service
 * 
 * Gestiona tomas/waves longitudinales de los estudios.
 * Las waves representan las evaluaciones sucesivas en el tiempo (baseline, follow-up 1, 2, 3...).
 *
 * @package EIPSI_Forms
 * @since 1.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class EIPSI_Wave_Service {
    
    /**
     * Obtener waves pendientes para un participante
     * 
     * Filtra waves con status = 'pending' o 'in_progress' en wp_survey_assignments.
     * 
     * @param int $participant_id ID del participante
     * @param int $survey_id ID del survey
     * @return array Array de wave objects { id, wave_index, form_template_id, due_at, status }
     */
    public static function get_pending_waves($participant_id, $survey_id) {
        // TODO: Implementar lógica en Fase 1
        // - JOIN wp_survey_waves con wp_survey_assignments
        // - WHERE assignments.participant_id = %d AND assignments.survey_id = %d
        // - WHERE assignments.status IN ('pending', 'in_progress')
        // - ORDER BY wave_index ASC
        return array();
    }
    
    /**
     * Obtener próxima wave pendiente
     * 
     * Retorna la wave con menor wave_index que esté pendiente.
     * 
     * @param int $participant_id ID del participante
     * @param int $survey_id ID del survey
     * @return object|null
     */
    public static function get_next_pending_wave($participant_id, $survey_id) {
        // TODO: Implementar lógica en Fase 1
        // - Llamar a get_pending_waves()
        // - Retornar el primer elemento (wave_index menor)
        return null;
    }
    
    /**
     * Obtener historial de waves respondidas
     * 
     * @param int $participant_id ID del participante
     * @param int $survey_id ID del survey
     * @return array Array de wave objects completadas
     */
    public static function get_completed_waves($participant_id, $survey_id) {
        // TODO: Implementar lógica en Fase 1
        // - JOIN wp_survey_waves con wp_survey_assignments
        // - WHERE assignments.participant_id = %d AND assignments.survey_id = %d
        // - WHERE assignments.status = 'submitted'
        // - ORDER BY wave_index ASC
        return array();
    }
    
    /**
     * Marcar wave como "in_progress"
     * 
     * Se llama cuando el participante inicia el formulario de una wave.
     * 
     * @param int $participant_id ID del participante
     * @param int $survey_id ID del survey
     * @param int $wave_id ID de la wave
     * @return bool
     */
    public static function start_wave($participant_id, $survey_id, $wave_id) {
        // TODO: Implementar lógica en Fase 1
        // - UPDATE wp_survey_assignments SET status = 'in_progress', updated_at = NOW()
        // - WHERE participant_id = %d AND survey_id = %d AND wave_id = %d
        return false;
    }
    
    /**
     * Marcar wave como "submitted"
     * 
     * Se llama cuando el participante envía el formulario de una wave.
     * 
     * @param int $participant_id ID del participante
     * @param int $survey_id ID del survey
     * @param int $wave_id ID de la wave
     * @return bool
     */
    public static function complete_wave($participant_id, $survey_id, $wave_id) {
        // TODO: Implementar lógica en Fase 1
        // - UPDATE wp_survey_assignments SET status = 'submitted', submitted_at = NOW(), updated_at = NOW()
        // - WHERE participant_id = %d AND survey_id = %d AND wave_id = %d
        return false;
    }
    
    /**
     * Crear nueva wave para una encuesta
     * 
     * @param int $survey_id ID del survey
     * @param int $wave_index Índice de la wave (1, 2, 3, ...)
     * @param int $form_template_id ID del post que contiene el formulario (Gutenberg)
     * @param string $due_at Fecha de vencimiento (datetime, formato Y-m-d H:i:s)
     * @param string $description Descripción opcional de la wave
     * @return int|bool (wave_id or false)
     */
    public static function create_wave($survey_id, $wave_index, $form_template_id, $due_at, $description = '') {
        // TODO: Implementar lógica en Fase 1
        // - Insertar en wp_survey_waves (survey_id, wave_index, form_template_id, due_at, description)
        // - Retornar el ID insertado o false en caso de error
        return false;
    }
    
    /**
     * Asignar wave a un participante
     * 
     * Crea el registro en wp_survey_assignments que vincula participante con wave.
     * 
     * @param int $participant_id ID del participante
     * @param int $survey_id ID del survey
     * @param int $wave_id ID de la wave
     * @return bool
     */
    public static function assign_wave_to_participant($participant_id, $survey_id, $wave_id) {
        // TODO: Implementar lógica en Fase 1
        // - Insertar en wp_survey_assignments (participant_id, survey_id, wave_id, status = 'pending')
        // - Verificar que no exista ya (unique constraint)
        return false;
    }
}
