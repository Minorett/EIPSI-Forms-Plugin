<?php
/**
 * EIPSI Anonymize Service
 * 
 * Maneja anonimización y cierre ético de estudios.
 * 
 * La anonimización es irreversible y debe ejecutarse con cautela:
 * - Borra PII (email, password, nombre) de participantes
 * - Invalida todos los magic links
 * - Mantiene datos de respuestas (sin PII) para análisis
 * - Registra todas las acciones en audit log
 *
 * @package EIPSI_Forms
 * @since 1.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class EIPSI_Anonymize_Service {
    
    /**
     * Anonimizar encuesta completa (irreversible)
     * 
     * Anonimiza TODOS los participantes de un survey, borra PII y magic links.
     * Esta acción es irreversible y debe confirmarse antes de ejecutarse.
     * 
     * @param int $survey_id ID del survey
     * @param string $audit_reason Razón por la que se anonimiza (para auditoría)
     * @return array { success: bool, anonymized_count: int, error: string }
     */
    public static function anonymize_survey($survey_id, $audit_reason = '') {
        // TODO: Implementar lógica en Fase 3
        // - Iniciar transacción de DB
        // - SELECT COUNT(*) FROM wp_survey_participants WHERE survey_id = %d AND is_active = 1
        // - Para cada participante:
        //   - Llamar a delete_pii($participant_id)
        // - Invalidar todos los magic links con invalidate_magic_links($survey_id)
        // - Marcar participantes como is_active = 0
        // - Registrar en audit log con audit_log()
        // - Commit transacción
        return array(
            'success' => false,
            'anonymized_count' => 0,
            'error' => 'Not implemented yet (Fase 3)'
        );
    }
    
    /**
     * Anonimizar un solo participante
     * 
     * @param int $participant_id ID del participante
     * @param string $audit_reason Razón de la anonimización
     * @return array { success: bool, error: string }
     */
    public static function anonymize_participant($participant_id, $audit_reason = '') {
        // TODO: Implementar lógica en Fase 3
        // - Llamar a delete_pii($participant_id)
        // - Invalidar magic links del participante
        // - Marcar como is_active = 0 con Participant_Service::set_active()
        // - Registrar en audit log con audit_log()
        return array(
            'success' => false,
            'error' => 'Not implemented yet (Fase 3)'
        );
    }
    
    /**
     * Borrar PII de un participante
     * 
     * Borra Personal Identifiable Information manteniendo los datos clínicos.
     * 
     * @param int $participant_id ID del participante
     * @return bool
     */
    public static function delete_pii($participant_id) {
        // TODO: Implementar lógica en Fase 3
        // - UPDATE wp_survey_participants
        // - SET email = 'anonymous_{id}@deleted.local', password_hash = NULL, first_name = NULL, last_name = NULL
        // - WHERE id = %d
        return false;
    }
    
    /**
     * Invalidar todos los magic links de un survey
     * 
     * Marca todos los magic links como usados para evitar acceso futuro.
     * 
     * @param int $survey_id ID del survey
     * @return int (count invalidated)
     */
    public static function invalidate_magic_links($survey_id) {
        // TODO: Implementar lógica en Fase 3
        // - UPDATE wp_survey_magic_links
        // - SET used_at = NOW(), expires_at = NOW()
        // - WHERE survey_id = %d AND used_at IS NULL AND expires_at > NOW()
        // - Retornar filas afectadas
        return 0;
    }
    
    /**
     * Invalidar magic links de un participante
     * 
     * @param int $participant_id ID del participante
     * @return int (count invalidated)
     */
    public static function invalidate_participant_magic_links($participant_id) {
        // TODO: Implementar lógica en Fase 3
        // - UPDATE wp_survey_magic_links
        // - SET used_at = NOW(), expires_at = NOW()
        // - WHERE participant_id = %d AND used_at IS NULL AND expires_at > NOW()
        // - Retornar filas afectadas
        return 0;
    }
    
    /**
     * Registrar acción en audit log
     * 
     * Todas las acciones sensibles deben registrarse para auditoría ética.
     * 
     * @param string $action Tipo de acción ('anonymize_survey', 'anonymize_participant', 'invalidate_links', etc.)
     * @param int $survey_id ID del survey
     * @param int $participant_id ID del participante (opcional)
     * @param array $metadata Metadatos adicionales (JSON)
     * @return bool
     */
    public static function audit_log($action, $survey_id, $participant_id = null, $metadata = array()) {
        // TODO: Implementar lógica en Fase 1 (audit log básico)
        // - Obtener actor_type ('admin', 'system')
        // - Obtener actor_id (current_user_id() o 0 si es sistema)
        // - Insertar en wp_survey_audit_log
        // - Campos: action, survey_id, participant_id, actor_type, actor_id, metadata, created_at
        return false;
    }
    
    /**
     * Obtener historial de auditoría de un survey
     * 
     * @param int $survey_id ID del survey
     * @param int $limit Cantidad máxima de registros
     * @return array
     */
    public static function get_survey_audit_log($survey_id, $limit = 100) {
        // TODO: Implementar lógica en Fase 1
        // - SELECT * FROM wp_survey_audit_log
        // - WHERE survey_id = %d
        // - ORDER BY created_at DESC
        // - LIMIT %d
        return array();
    }
    
    /**
     * Verificar si un survey puede anonimizarse
     * 
     * Valida condiciones previas:
     * - No hay waves pendientes
     - - Todos los participantes tienen al menos una wave completada
     * 
     * @param int $survey_id ID del survey
     * @return array { can_anonymize: bool, reason: string }
     */
    public static function can_anonymize_survey($survey_id) {
        // TODO: Implementar lógica en Fase 3
        // - Verificar si hay waves pendientes
        // - Verificar si hay assignments con status = 'pending' o 'in_progress'
        // - Retornar resultado con razón si no se puede
        return array(
            'can_anonymize' => true,
            'reason' => 'Not implemented yet (Fase 3)'
        );
    }
}
