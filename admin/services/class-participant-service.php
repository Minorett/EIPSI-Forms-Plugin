<?php
/**
 * EIPSI Participant Service
 * 
 * Gestiona participantes y su ciclo de vida en el sistema longitudinal.
 * Los participantes pueden registrarse con email+password y recibir waves.
 *
 * @package EIPSI_Forms
 * @since 1.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class EIPSI_Participant_Service {
    
    /**
     * Crear nuevo participante
     * 
     * @param string $survey_id ID del survey/study
     * @param string $email Email del participante
     * @param string $password Password en texto plano (será hasheado)
     * @param array $additional_data Datos adicionales (first_name, last_name, etc.)
     * @return array { success: bool, participant_id: int|null, error: string }
     */
    public static function create_participant($survey_id, $email, $password, $additional_data = array()) {
        // TODO: Implementar lógica en Fase 1
        // - Validar email único (survey_id + email)
        // - Hashear password con wp_hash_password()
        // - Insertar en wp_survey_participants
        return array(
            'success' => false,
            'participant_id' => null,
            'error' => 'Not implemented yet (Fase 1)'
        );
    }
    
    /**
     * Obtener participante por email + survey
     * 
     * @param int $survey_id ID del survey
     * @param string $email Email del participante
     * @return object|null Fila de wp_survey_participants
     */
    public static function get_by_email($survey_id, $email) {
        // TODO: Implementar en Fase 1
        // - Buscar en wp_survey_participants
        // - WHERE survey_id = %s AND email = %s AND is_active = 1
        return null;
    }
    
    /**
     * Obtener participante por ID
     * 
     * @param int $participant_id ID del participante
     * @return object|null
     */
    public static function get_by_id($participant_id) {
        // TODO: Implementar en Fase 1
        // - Buscar en wp_survey_participants
        // - WHERE id = %d
        return null;
    }
    
    /**
     * Verificar password
     * 
     * @param int $participant_id ID del participante
     * @param string $plain_password Password en texto plano
     * @return bool
     */
    public static function verify_password($participant_id, $plain_password) {
        // TODO: Implementar en Fase 1
        // - Obtener password_hash del participante
        // - Usar wp_check_password()
        return false;
    }
    
    /**
     * Actualizar último login
     * 
     * @param int $participant_id ID del participante
     * @return bool
     */
    public static function update_last_login($participant_id) {
        // TODO: Implementar en Fase 1
        // - UPDATE wp_survey_participants SET last_login_at = NOW()
        return false;
    }
    
    /**
     * Marcar como activo/inactivo (para anonimización)
     * 
     * @param int $participant_id ID del participante
     * @param bool $is_active Estado (true = activo, false = inactivo)
     * @return bool
     */
    public static function set_active($participant_id, $is_active) {
        // TODO: Implementar en Fase 1 (usado en Anonymize_Service)
        // - UPDATE wp_survey_participants SET is_active = %d
        return false;
    }
}
