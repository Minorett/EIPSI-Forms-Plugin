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
        
        // Verificación consolidada en un solo log
        $is_available = self::is_wave_available($assignment, $wave);
        $already_sent = self::was_nudge_zero_already_sent($participant_id, $wave_id);
        $retry_status = self::get_retry_status($participant_id, $wave_id);
        
        error_log(sprintf(
            '[EIPSI WaveEmail] Verificando p=%d w=%d | disponible=%s | enviado=%s | reintentos=%d/%d',
            $participant_id,
            $wave_id,
            $is_available ? 'SÍ' : 'NO',
            $already_sent ? 'SÍ' : 'NO',
            $retry_status['attempts'],
            self::MAX_RETRY_ATTEMPTS
        ));

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
        
        if ($already_sent) {
            return array(
                'success' => true,
                'sent' => false,
                'reason' => 'already_sent',
                'message' => 'Nudge 0 ya enviado anteriormente, no se duplica',
                'participant_id' => $participant_id,
                'wave_id' => $wave_id
            );
        }

        if ($retry_status['attempts'] >= self::MAX_RETRY_ATTEMPTS) {
            error_log("[EIPSI WaveEmail] ✗ Máximo reintentos alcanzado ({$retry_status['attempts']}/" . self::MAX_RETRY_ATTEMPTS . ")");
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

        // Intentar enviar
        $attempt = $retry_status['attempts'] + 1;
        $send_result = self::send_wave_availability_email_with_retry(
            $assignment, 
            $wave, 
            $participant, 
            $study_id,
            $attempt
        );

        if ($send_result['success']) {
            self::mark_nudge_zero_sent($participant_id, $wave_id, $send_result['log_id']);
            error_log("[EIPSI WaveEmail] ✓ Enviado p={$participant_id} w={$wave_id} (log_id={$send_result['log_id']})");
            
            return array(
                'success' => true,
                'sent' => true,
                'reason' => 'sent_successfully',
                'message' => 'Email enviado correctamente',
                'participant_id' => $participant_id,
                'wave_id' => $wave_id,
                'log_id' => $send_result['log_id'],
                'attempts' => $attempt
            );
        } else {
            self::record_failed_attempt($participant_id, $wave_id, $send_result['error']);
            
            // Log detallado solo para errores críticos
            if ($send_result['error'] !== 'retry_cooldown') {
                error_log("[EIPSI WaveEmail] ✗ Fallo p={$participant_id} w={$wave_id}: {$send_result['error']} (intento {$attempt})");
            }
            
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
        // v2.5.3 - Fix: buscar tanto 'reminder' (legacy) como 'wave_availability' (nuevo tipo)
        $logs = $wpdb->get_results($wpdb->prepare(
            "SELECT id, status, metadata, email_type
             FROM {$wpdb->prefix}survey_email_log 
             WHERE participant_id = %d 
             AND email_type IN ('reminder', 'wave_availability')
             AND status IN ('sent', 'pending')
             ORDER BY sent_at DESC 
             LIMIT 5",
            $participant_id
        ));

        foreach ($logs as $log) {
            $metadata = !empty($log->metadata) ? json_decode($log->metadata, true) : array();
            if (isset($metadata['wave_id']) && $metadata['wave_id'] == $wave_id) {
                if (isset($metadata['nudge_stage']) && $metadata['nudge_stage'] === 0) {
                    // Log solo en debug - no es un error, es prevención de duplicado
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log("[EIPSI WaveEmail] Nudge 0 ya enviado (log_id={$log->id})");
                    }
                    return true;
                }
            }
        }

        // MÉTODO 2 (BACKUP): Verificar si el usuario ya interactuó con la wave
        // Esto previene duplicados incluso si el email_log se borra o el metadata falla
        // Usamos vas_form_results que guarda las submissions de formularios
        $already_accessed = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vas_form_results 
             WHERE participant_id = %d AND form_id = %d",
            $participant_id, 
            $wave_id
        ));

        if ($already_accessed > 0) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[EIPSI WaveEmail] Usuario ya accedió a wave {$wave_id} - no necesita Nudge 0");
            }
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
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[EIPSI WaveEmail] Assignment en '{$assignment_status}' - no necesita Nudge 0");
            }
            return true;
        }

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
        
        // Solo loguear errores críticos, no cooldowns
        if ($error !== 'retry_cooldown' && (defined('WP_DEBUG') && WP_DEBUG)) {
            error_log("[EIPSI WaveEmail] Intento fallido registrado: {$status['attempts']}/" . self::MAX_RETRY_ATTEMPTS);
        }
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
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[EIPSI WaveEmail] Nudge 0 marcado como enviado. Log ID: {$log_id}");
        }
    }

    /**
     * Enviar email de disponibilidad con reintentos internos
     */
    private static function send_wave_availability_email_with_retry($assignment, $wave, $participant, $study_id, $attempt_number) {
        // Log simplificado solo en debug
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[EIPSI WaveEmail] Enviando email (intento #{$attempt_number})");
        }
        global $wpdb;
        
        if (!class_exists('EIPSI_Email_Service')) {
            require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/services/class-email-service.php';
        }

        // Get study info for correct URL
        $study = $wpdb->get_row($wpdb->prepare(
            "SELECT study_code, study_name FROM {$wpdb->prefix}survey_studies WHERE id = %d",
            $study_id
        ));

        if (!$study) {
            error_log("[EIPSI WaveEmail] ERROR: Study {$study_id} no encontrado");
            return array(
                'success' => false,
                'error' => 'study_not_found',
                'message' => 'Estudio no encontrado'
            );
        }

        // Build study page URL
        $study_slug = 'estudio-' . sanitize_title($study->study_code);
        $study_page = get_page_by_path($study_slug);
        
        if (!$study_page) {
            // Fallback: try to find by meta
            $study_pages = get_posts([
                'post_type' => 'page',
                'meta_key' => 'eipsi_study_id',
                'meta_value' => $study_id,
                'posts_per_page' => 1
            ]);
            $study_page = !empty($study_pages) ? $study_pages[0] : null;
        }
        
        if (!$study_page) {
            error_log("[EIPSI WaveEmail] ERROR: Página no encontrada para study: {$study_slug}");
            return array(
                'success' => false,
                'error' => 'study_page_not_found',
                'message' => 'Página del estudio no encontrada'
            );
        }

        $study_url = get_permalink($study_page->ID);
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[EIPSI WaveEmail] Study URL: {$study_url}");
        }

        // Generate magic token
        if (!class_exists('EIPSI_MagicLinksService')) {
            require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/services/class-magic-links-service.php';
        }
        
        $token = EIPSI_MagicLinksService::generate_magic_link($study_id, $participant->id);
        if (!$token) {
            // El error ya fue logueado por MagicLinksService con diagnóstico
            return array(
                'success' => false,
                'error' => 'token_generation_failed',
                'message' => 'No se pudo generar el token de acceso'
            );
        }

        // Build magic link with email pre-filled
        $magic_link = add_query_arg([
            'eipsi_magic' => $token,
            'email_pre' => urlencode($participant->email)
        ], $study_url);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[EIPSI WaveEmail] Magic link generado para p={$participant->id}");
        }

        // Prepare email content
        $wave_name = $wave->name ?: 'Toma ' . $wave->order_index;
        $subject = "Tu siguiente evaluación está disponible - " . $study->study_name;
        
        $message = sprintf(
            '<p>¡Hola %s!</p>
            <p>¡Buenas noticias! Ya podés acceder a tu siguiente toma del estudio:</p>
            <h2>%s (%s)</h2>
            <p style="text-align: center; margin: 30px 0;">
                <a href="%s" style="background: #4F46E5; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block;">
                    Comenzar Evaluación →
                </a>
            </p>
            <p>O copiá este link en tu navegador:<br>
            <code style="background: #f3f4f6; padding: 8px; word-break: break-all;">%s</code></p>
            <hr>
            <p style="color: #666; font-size: 14px;">
                <strong>¿Necesitás ayuda?</strong><br>
                Si tenés problemas para acceder, respondé a este email o contactá al investigador.
            </p>
            <p style="color: #999; font-size: 12px;">
                Este es un email automático del sistema EIPSI Forms.<br>
                Por favor no respondas directamente a este mensaje.
            </p>',
            esc_html($participant->first_name),
            esc_html($study->study_name),
            esc_html($wave_name),
            esc_url($magic_link),
            esc_html($magic_link)
        );

        // Send email - EIPSI_Email_Service ya inserta el log automáticamente
        $sent = EIPSI_Email_Service::send_email(
            $study_id,
            $participant->id,
            $participant->email,
            'wave_availability',
            $subject,
            $message
        );
        
        if ($sent) {
            // v2.5.4 - NO llamar a log_email_success porque send_email ya logueó
            return array(
                'success' => true,
                'log_id' => null, // El log_id se obtiene del insert de send_email
                'message' => 'Email enviado correctamente'
            );
        } else {
            error_log("[EIPSI WaveEmail] ERROR: Fallo SMTP al enviar a p={$participant->id}");
            return array(
                'success' => false,
                'error' => 'smtp_error',
                'message' => 'Fallo al enviar email via SMTP'
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
                'email_type' => 'wave_availability',  // v2.5.3 - Nudge 0 específico
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
