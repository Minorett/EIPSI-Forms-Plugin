<?php
/**
 * EIPSI Email Service
 * 
 * Gestiona envío de emails automáticos para participantes:
 * - Bienvenida al estudio
 * - Recordatorios de waves pendientes
 * - Magic links para acceso directo
 *
 * @package EIPSI_Forms
 * @since 1.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class EIPSI_Email_Service {
    
    /**
     * Generar magic link para acceso directo a una wave
     * 
     * El magic link es una URL con un token que permite al participante
     * acceder directamente a una wave sin login manual.
     * 
     * @param int $participant_id ID del participante
     * @param int $wave_id ID de la wave
     * @return string (full URL with token)
     */
    public static function generate_magic_link($participant_id, $wave_id) {
        // TODO: Implementar lógica en Fase 2
        // - Generar token único (wp_generate_password(64, true, true))
        // - Hashear token para almacenar en DB (wp_hash_password())
        // - Insertar en wp_survey_magic_links (token_hash, participant_id, wave_id, expires_at)
        // - Construir URL: site_url() . "?eipsi_magic={$token}"
        return '';
    }
    
    /**
     * Enviar recordatorio de wave pendiente
     * 
     * Envía un email al participante recordándole que tiene una wave pendiente.
     * Incluye magic link para acceso directo.
     * 
     * @param int $participant_id ID del participante
     * @param int $wave_id ID de la wave
     * @param string $custom_message Mensaje personalizado opcional
     * @return bool
     */
    public static function send_wave_reminder($participant_id, $wave_id, $custom_message = '') {
        // TODO: Implementar lógica en Fase 2
        // - Obtener datos del participante (email, nombre) con Participant_Service
        // - Generar magic link con generate_magic_link()
        // - Construir email HTML con plantilla (welcome/reminder)
        // - Enviar con wp_mail()
        // - Registrar envío con log_email_sent()
        return false;
    }
    
    /**
     * Enviar email de bienvenida a nuevo participante
     * 
     * @param int $participant_id ID del participante
     * @param int $survey_id ID del survey
     * @param string $temp_password Contraseña temporal (si se generó)
     * @return bool
     */
    public static function send_welcome_email($participant_id, $survey_id, $temp_password = '') {
        // TODO: Implementar lógica en Fase 2
        // - Obtener datos del participante
        // - Generar magic link para primera wave
        // - Enviar email de bienvenida con instrucciones
        // - Registrar envío con log_email_sent()
        return false;
    }
    
    /**
     * Registrar envío en log
     * 
     * Guarda el registro del email enviado para auditoría y evitar spam.
     * 
     * @param int $participant_id ID del participante
     * @param string $type Tipo de email ('reminder', 'welcome', 'confirmation', 'custom')
     * @param int $wave_id ID de la wave (opcional)
     * @param string $status Estado del envío ('sent', 'failed', 'bounced')
     * @param string $error_message Mensaje de error (si falló)
     * @param array $metadata Metadatos adicionales (JSON)
     * @return bool
     */
    public static function log_email_sent($participant_id, $type, $wave_id = null, $status = 'sent', $error_message = '', $metadata = array()) {
        // TODO: Implementar lógica en Fase 2
        // - Insertar en wp_survey_email_log
        // - Campos: participant_id, survey_id, email_type, wave_id, sent_at, status, error_message, metadata
        return false;
    }
    
    /**
     * Obtener historial de emails enviados
     * 
     * @param int $participant_id ID del participante
     * @param int $survey_id ID del survey
     * @param int $limit Cantidad máxima de registros
     * @return array
     */
    public static function get_email_history($participant_id, $survey_id, $limit = 50) {
        // TODO: Implementar lógica en Fase 2
        // - SELECT * FROM wp_survey_email_log
        // - WHERE participant_id = %d AND survey_id = %d
        // - ORDER BY sent_at DESC
        // - LIMIT %d
        return array();
    }
    
    /**
     * Obtener plantilla de email
     * 
     * Retorna la plantilla HTML para un tipo de email específico.
     * Las plantillas pueden personalizarse en configuración del plugin.
     * 
     * @param string $type Tipo de email ('reminder', 'welcome', 'confirmation')
     * @return string HTML de la plantilla
     */
    public static function get_email_template($type) {
        // TODO: Implementar lógica en Fase 2
        // - Leer desde opciones de WordPress o archivo de plantilla
        // - Retornar HTML con placeholders: {participant_name}, {magic_link}, {survey_title}
        return '';
    }
}
