<?php
/**
 * EIPSI Auth Service
 * 
 * Maneja autenticación y sesiones del plugin para participantes.
 * Usa cookies propias del plugin, independientes de las sesiones de WordPress.
 *
 * @package EIPSI_Forms
 * @since 1.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class EIPSI_Auth_Service {
    
    /**
     * Autenticar participante (login)
     * 
     * Valida credenciales y crea sesión si son correctas.
     * 
     * @param int $survey_id ID del survey
     * @param string $email Email del participante
     * @param string $password Password en texto plano
     * @return array { success: bool, participant_id: int|null, error: string }
     */
    public static function authenticate($survey_id, $email, $password) {
        // TODO: Implementar lógica en Fase 1
        // - Usar Participant_Service::get_by_email() para buscar participante
        // - Usar Participant_Service::verify_password() para validar
        // - Si válido, llamar a create_session()
        // - Actualizar último login con Participant_Service::update_last_login()
        return array(
            'success' => false,
            'participant_id' => null,
            'error' => 'Not implemented yet (Fase 1)'
        );
    }
    
    /**
     * Crear sesión firmada (cookie propia del plugin)
     * 
     * La sesión se almacena en:
     * 1. Cookie HTTP-only: EIPSI_SESSION_COOKIE_NAME
     * 2. Tabla wp_survey_sessions (para invalidación)
     * 
     * @param int $participant_id ID del participante
     * @param int $survey_id ID del survey
     * @param int $ttl_hours Tiempo de vida en horas (default 168 = 7 días)
     * @return bool
     */
    public static function create_session($participant_id, $survey_id, $ttl_hours = 168) {
        // TODO: Implementar lógica en Fase 1
        // - Generar token único (wp_generate_password(64, true, true))
        // - Calcular expires_at = NOW() + INTERVAL {$ttl_hours} HOUR
        // - Insertar en wp_survey_sessions (token, participant_id, survey_id, ip_address, user_agent, expires_at)
        // - Setear cookie HTTP-only con el token
        // - Token en cookie = base64_encode($participant_id . '|' . $token)
        return false;
    }
    
    /**
     * Obtener participante actual desde sesión
     * 
     * Lee la cookie del plugin y valida contra la tabla wp_survey_sessions.
     * 
     * @return int|null (participant_id)
     */
    public static function get_current_participant() {
        // TODO: Implementar lógica en Fase 1
        // - Leer cookie EIPSI_SESSION_COOKIE_NAME
        // - Decodificar: base64_decode()
        // - Separar participant_id y token
        // - Validar token en wp_survey_sessions (no expirado, no usado)
        // - Retornar participant_id si válido
        return null;
    }
    
    /**
     * Obtener survey actual desde sesión
     * 
     * @return int|null (survey_id)
     */
    public static function get_current_survey() {
        // TODO: Implementar lógica en Fase 1
        // - Similar a get_current_participant()
        // - Retornar survey_id desde la sesión
        return null;
    }
    
    /**
     * Destruir sesión (logout)
     * 
     * Elimina la cookie y marca la sesión como inválida en la DB.
     * 
     * @return bool
     */
    public static function destroy_session() {
        // TODO: Implementar lógica en Fase 1
        // - Leer cookie para obtener token
        // - Eliminar cookie (setear con fecha de expiración en el pasado)
        // - Marcar sesión como expirada en wp_survey_sessions (DELETE o UPDATE expires_at)
        return false;
    }
    
    /**
     * Validar sesión
     * 
     * Verifica si hay una sesión válida activa.
     * 
     * @return bool
     */
    public static function is_authenticated() {
        // TODO: Implementar lógica en Fase 1
        // - Llamar a get_current_participant()
        // - Retornar true si no es null
        return false;
    }
}
