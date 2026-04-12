<?php
/**
 * EIPSI_Wave_Availability_Email_Service
 *
 * Sistema robusto de verificación y envío de emails de disponibilidad de wave (Nudge 0).
 * Verifica si la toma está disponible, si el email ya se envió, y reintenta si es necesario.
 *
 * @package EIPSI_Forms
 * @subpackage Services
 * @version 2.2.0
 * @since 2.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class EIPSI_Wave_Availability_Email_Service {

    const MAX_RETRY_ATTEMPTS = 3;
    const RETRY_DELAY_MINUTES = 5;
    const EMAIL_TYPE = 'wave_available'; // Nudge 0

    /**
     * Verificación completa del sistema:
     * 1. ¿La toma está disponible?
     * 2. ¿Se envió mail de inmediato (Nudge 0)?
     * 3. Si no se envió → Enviar con reintentos
     * 4. Si ya se envió → No duplicar, esperar siguientes nudges
     *
     * @param object $assignment Assignment data
     * @param object $wave Wave data  
     * @param object $participant Participant data
     * @param int $study_id Study ID
     * @return array Resultado detallado
     */
    public static function ensure_wave_availability_email_sent($assignment, $wave, $participant, $study_id) {
        $participant_id = $assignment->participant_id;
        $wave_id = $assignment->wave_id;
        
        error_log("[EIPSI WaveEmail] === INICIO VERIFICACIÓN === participant={$participant_id}, wave={$wave_id}");

        // Paso 1: ¿La toma está disponible?
        $is_available = self::is_wave_available($assignment, $wave);
        error_log("[EIPSI WaveEmail] Paso 1 - ¿Toma disponible? " . ($is_available ? 'SÍ' : 'NO'));
        
        if (!$is_available) {
            return array(
                'success' => false,
                'sent' => false,
                'reason' => 'wave_not_available',
                'message' => 'La toma aún no está disponible',
                'participant_id' => $participant_id,
                'wave_id' => $wave_id
            );
        }

        // Paso 2: ¿Se envió mail de inmediato (Nudge 0)?
        $already_sent = self::was_nudge_zero_already_sent($participant_id, $wave_id);
        error_log("[EIPSI WaveEmail] Paso 2 - ¿Nudge 0 ya enviado? " . ($already_sent ? 'SÍ' : 'NO'));
        
        if ($already_sent) {
            error_log("[EIPSI WaveEmail] ✓ TODO CORRECTO: Nudge 0 ya enviado anteriormente. No se envía duplicado.");
            return array(
                'success' => true,
                'sent' => false,
                'reason' => 'already_sent',
                'message' => 'Nudge 0 ya enviado anteriormente, no se duplica',
                'participant_id' => $participant_id,
                'wave_id' => $wave_id
            );
        }

        // Paso 3: Verificar si hay reintentos pendientes
        $retry_status = self::get_retry_status($participant_id, $wave_id);
        error_log("[EIPSI WaveEmail] Paso 3 - Estado de reintentos: intentos={$retry_status['attempts']}, último_intento={$retry_status['last_attempt']}");

        if ($retry_status['attempts'] >= self::MAX_RETRY_ATTEMPTS) {
            error_log("[EIPSI WaveEmail] ✗ MÁXIMO DE REINTENTOS ALCANZADO ({$retry_status['attempts']}/" . self::MAX_RETRY_ATTEMPTS . ")");
            return array(
                'success' => false,
                'sent' => false,
                'reason' => 'max_retries_reached',
                'message' => "Se alcanzó el máximo de reintentos (" . self::MAX_RETRY_ATTEMPTS . ")",
                'participant_id' => $participant_id,
                'wave_id' => $wave_id,
                'retry_history' => $retry_status
            );
        }

        // Verificar cooldown entre reintentos
        if ($retry_status['last_attempt'] > 0) {
            $minutes_since_last = (time() - $retry_status['last_attempt']) / 60;
            if ($minutes_since_last < self::RETRY_DELAY_MINUTES) {
                $wait = round(self::RETRY_DELAY_MINUTES - $minutes_since_last);
                error_log("[EIPSI WaveEmail] ⏳ COOLDOWN ACTIVO: Esperar {$wait} minutos antes de reintentar");
                return array(
                    'success' => false,
                    'sent' => false,
                    'reason' => 'retry_cooldown',
                    'message' => "Cooldown activo, esperar {$wait} minutos",
                    'participant_id' => $participant_id,
                    'wave_id' => $wave_id,
                    'wait_minutes' => $wait
                );
            }
        }

        // Paso 4: Intentar enviar el email con sistema de reintento
        error_log("[EIPSI WaveEmail] Paso 4 - Intentando enviar Nudge 0 (intento #" . ($retry_status['attempts'] + 1) . ")");
        
        $send_result = self::send_wave_availability_email_with_retry(
            $assignment, 
            $wave, 
            $participant, 
            $study_id,
            $retry_status['attempts'] + 1
        );

        // Paso 5: Verificar resultado y actualizar estado
        if ($send_result['success']) {
            // Éxito: Marcar como enviado
            self::mark_nudge_zero_sent($participant_id, $wave_id, $send_result['log_id']);
            
            error_log("[EIPSI WaveEmail] ✓ ÉXITO: Email enviado correctamente. Log ID: {$send_result['log_id']}");
            
            return array(
                'success' => true,
                'sent' => true,
                'reason' => 'sent_successfully',
                'message' => 'Email enviado correctamente',
                'participant_id' => $participant_id,
                'wave_id' => $wave_id,
                'log_id' => $send_result['log_id'],
                'attempts' => $retry_status['attempts'] + 1
            );
        } else {
            // Fallo: Registrar intento fallido
            self::record_failed_attempt($participant_id, $wave_id, $send_result['error']);
            
            error_log("[EIPSI WaveEmail] ✗ FALLO: {$send_result['error']}");
            
            return array(
                'success' => false,
                'sent' => false,
                'reason' => 'send_failed',
                'message' => $send_result['error'],
                'participant_id' => $participant_id,
                'wave_id' => $wave_id,
                'attempts' => $retry_status['attempts'] + 1,
                'error' => $send_result['error']
            );
        }
    }

    /**
     * Verificar si la wave está disponible
     */
    private static function is_wave_available($assignment, $wave) {
        // Si no tiene last_submission_date, es la primera wave (T1) - siempre disponible
        if (empty($assignment->last_submission_date)) {
            return true;
        }

        // time_unit: 0 = minutes, 1 = days (from database)
        $time_unit_str = (intval($wave->time_unit) === 0) ? 'minutes' : 'days';
        $available_at = strtotime("+{$wave->interval_days} {$time_unit_str}", strtotime($assignment->last_submission_date));
        $now = current_time('timestamp');

        return $now >= $available_at;
    }

    /**
     * Verificar si Nudge 0 ya fue enviado
     * Usa defensa en profundidad: múltiples métodos de verificación
     */
    private static function was_nudge_zero_already_sent($participant_id, $wave_id) {
        global $wpdb;
        
        // MÉTODO 1: Buscar en email_log emails tipo 'reminder' con wave_id en metadata
        // O buscar en la tabla de nudges enviados
        $logs = $wpdb->get_results($wpdb->prepare(
            "SELECT id, status, metadata 
             FROM {$wpdb->prefix}survey_email_log 
             WHERE participant_id = %d 
             AND email_type = 'reminder'
             AND status IN ('sent', 'pending')
             ORDER BY sent_at DESC 
             LIMIT 5",
            $participant_id
        ));

        foreach ($logs as $log) {
            $metadata = !empty($log->metadata) ? json_decode($log->metadata, true) : array();
            if (isset($metadata['wave_id']) && $metadata['wave_id'] == $wave_id) {
                // También verificar que sea Nudge 0 (no un follow-up)
                if (isset($metadata['nudge_stage']) && $metadata['nudge_stage'] === 0) {
                    error_log("[EIPSI WaveEmail] Verificación 1 (email_log): Nudge 0 encontrado - log_id={$log->id}");
                    return true;
                }
            }
        }

        // MÉTODO 2 (BACKUP): Verificar si el usuario ya interactuó con la wave
        // Esto previene duplicados incluso si el email_log se borra o el metadata falla
        $already_accessed = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}survey_submissions 
             WHERE participant_id = %d AND wave_id = %d",
            $participant_id, 
            $wave_id
        ));

        if ($already_accessed > 0) {
            error_log("[EIPSI WaveEmail] Verificación 2 (submissions): Usuario ya accedió a wave {$wave_id} - no necesita Nudge 0");
            return true;
        }

        // MÉTODO 3 (BACKUP): Verificar si hay assignment con status 'submitted' o 'in_progress'
        $assignment_status = $wpdb->get_var($wpdb->prepare(
            "SELECT status FROM {$wpdb->prefix}survey_assignments 
             WHERE participant_id = %d AND wave_id = %d",
            $participant_id, 
            $wave_id
        ));

        if (in_array($assignment_status, array('submitted', 'in_progress'))) {
            error_log("[EIPSI WaveEmail] Verificación 3 (assignment): Assignment en estado '{$assignment_status}' - no necesita Nudge 0");
            return true;
        }

        error_log("[EIPSI WaveEmail] Todas las verificaciones pasaron: Nudge 0 NO enviado aún");
        return false;
    }

    /**
     * Obtener estado de reintentos
     */
    private static function get_retry_status($participant_id, $wave_id) {
        $transient_key = "eipsi_wave_email_retries_{$participant_id}_{$wave_id}";
        $status = get_transient($transient_key);
        
        if (!$status) {
            return array(
                'attempts' => 0,
                'last_attempt' => 0,
                'errors' => array()
            );
        }
        
        return $status;
    }

    /**
     * Registrar intento fallido
     */
    private static function record_failed_attempt($participant_id, $wave_id, $error) {
        $transient_key = "eipsi_wave_email_retries_{$participant_id}_{$wave_id}";
        $status = self::get_retry_status($participant_id, $wave_id);
        
        $status['attempts']++;
        $status['last_attempt'] = time();
        $status['errors'][] = array(
            'time' => current_time('mysql'),
            'error' => $error
        );
        
        // Guardar por 24 horas
        set_transient($transient_key, $status, 24 * HOUR_IN_SECONDS);
        
        error_log("[EIPSI WaveEmail] Intento fallido registrado: {$status['attempts']}/" . self::MAX_RETRY_ATTEMPTS);
    }

    /**
     * Marcar Nudge 0 como enviado
     */
    private static function mark_nudge_zero_sent($participant_id, $wave_id, $log_id) {
        $transient_key = "eipsi_wave_email_retries_{$participant_id}_{$wave_id}";
        
        $status = array(
            'attempts' => 0, // Resetear intentos
            'last_attempt' => 0,
            'sent' => true,
            'sent_at' => current_time('mysql'),
            'log_id' => $log_id,
            'errors' => array()
        );
        
        // Guardar por 7 días para evitar duplicados
        set_transient($transient_key, $status, 7 * DAY_IN_SECONDS);
        
        error_log("[EIPSI WaveEmail] Nudge 0 marcado como enviado. Log ID: {$log_id}");
    }

    /**
     * Enviar email de disponibilidad con reintentos internos
     */
    private static function send_wave_availability_email_with_retry($assignment, $wave, $participant, $study_id, $attempt_number) {
        error_log("[EIPSI WaveEmail] Enviando email (intento #{$attempt_number})...");
        
        if (!class_exists('EIPSI_Email_Service')) {
            require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/services/class-email-service.php';
        }

        // Preparar datos del email
        $to = $participant->email;
        $subject = sprintf(__('Tu siguiente evaluación está disponible - %s', 'eipsi-forms'), $assignment->wave_name);
        
        // Generar magic link
        $magic_link = '';
        if (class_exists('EIPSI_MagicLinksService')) {
            $token = EIPSI_MagicLinksService::generate_magic_link($study_id, $assignment->participant_id);
            if ($token !== false) {
                // Construir el magic link URL
                $magic_link = add_query_arg(array(
                    'eipsi_token' => $token,
                    'survey_id' => $study_id
                ), home_url('/eipsi-survey/'));
            }
        }

        // Preparar template
        ob_start();
        include EIPSI_FORMS_PLUGIN_DIR . 'includes/templates/emails/wave-available.php';
        $message = ob_get_clean();

        // Headers
        $headers = array('Content-Type: text/html; charset=UTF-8');

        // Intentar enviar
        $sent = wp_mail($to, $subject, $message, $headers);
        
        if ($sent) {
            // Log exitoso
            $log_id = self::log_email_success($study_id, $assignment->participant_id, $wave->id, $to);
            
            return array(
                'success' => true,
                'log_id' => $log_id,
                'message' => 'Email enviado correctamente'
            );
        } else {
            // Log fallido
            global $phpmailer;
            $error = isset($phpmailer) ? $phpmailer->ErrorInfo : 'Error desconocido en wp_mail';
            
            return array(
                'success' => false,
                'error' => $error,
                'message' => 'Fallo al enviar email: ' . $error
            );
        }
    }

    /**
     * Log de email exitoso
     */
    private static function log_email_success($survey_id, $participant_id, $wave_id, $recipient_email = '') {
        global $wpdb;
        
        // Usar el email pasado como parámetro, o buscarlo si no se proporcionó
        if (empty($recipient_email)) {
            $participant = $wpdb->get_row($wpdb->prepare(
                "SELECT email FROM {$wpdb->prefix}survey_participants WHERE id = %d",
                $participant_id
            ));
            $recipient_email = $participant ? $participant->email : '';
        }
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'survey_email_log',
            array(
                'survey_id' => $survey_id,
                'participant_id' => $participant_id,
                'email_type' => 'reminder',
                'recipient_email' => $recipient_email,
                'subject' => 'Tu siguiente evaluación está disponible',
                'status' => 'sent',
                'sent_at' => current_time('mysql'),
                'created_at' => current_time('mysql'),
                'metadata' => wp_json_encode(array(
                    'wave_id' => $wave_id,
                    'nudge_stage' => 0,
                    'email_variant' => 'wave_available'
                ))
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        return $result ? $wpdb->insert_id : 0;
    }

    /**
     * Forzar envío de Nudge 0 (para uso manual/admin)
     */
    public static function force_send_nudge_zero($assignment, $wave, $participant, $study_id) {
        error_log("[EIPSI WaveEmail] FORZANDO envío de Nudge 0 (bypass de verificaciones)");
        
        // Limpiar estado de reintentos
        $transient_key = "eipsi_wave_email_retries_{$assignment->participant_id}_{$wave->id}";
        delete_transient($transient_key);
        
        // Forzar envío
        return self::send_wave_availability_email_with_retry(
            $assignment, 
            $wave, 
            $participant, 
            $study_id,
            1
        );
    }
}
