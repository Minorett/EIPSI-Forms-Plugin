<?php
/**
 * EIPSI Forms - Cron Handlers for Longitudinal Reminders
 * 
 * Gestiona envío automático de recordatorios para tomas pendientes.
 * 
 * @package EIPSI_Forms
 * @since 1.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Registrar hooks de cron
add_action('eipsi_send_take_reminders_daily', 'eipsi_process_daily_reminders');
add_action('eipsi_send_take_reminders_weekly', 'eipsi_process_weekly_reminders');
add_action('eipsi_send_manual_reminder', 'eipsi_send_manual_reminder_handler', 10, 2);

/**
 * Procesa recordatorios diarios
 * 
 * @since 1.3.0
 */
function eipsi_process_daily_reminders() {
    eipsi_process_reminders('daily');
}

/**
 * Procesa recordatorios semanales
 * 
 * @since 1.3.0
 */
function eipsi_process_weekly_reminders() {
    eipsi_process_reminders('weekly');
}

/**
 * Lógica principal de procesamiento de recordatorios
 * 
 * @param string $frequency 'daily' | 'weekly'
 * @since 1.3.0
 */
function eipsi_process_reminders($frequency) {
    global $wpdb;
    
    // Log inicio
    error_log("[EIPSI Forms] Processing {$frequency} reminders - " . current_time('mysql'));
    
    // Obtener formularios con aleatorización activa
    $forms = get_posts(array(
        'post_type' => 'eipsi_form',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'meta_query' => array(
            array(
                'key' => '_eipsi_random_config',
                'compare' => 'EXISTS',
            ),
        ),
    ));
    
    if (empty($forms)) {
        error_log("[EIPSI Forms] No forms with randomization found.");
        return;
    }
    
    $reminders_sent = 0;
    $rate_limit = apply_filters('eipsi_cron_reminders_rate_limit', 100);
    
    foreach ($forms as $form_id) {
        if ($reminders_sent >= $rate_limit) {
            error_log("[EIPSI Forms] Rate limit reached: {$rate_limit} emails. Stopping.");
            break;
        }
        
        // Verificar si este formulario tiene recordatorios habilitados con esta frecuencia
        $config = get_post_meta($form_id, '_eipsi_random_config', true);
        
        if (empty($config) || !isset($config['reminderFrequency'])) {
            continue;
        }
        
        // Solo procesar si la frecuencia coincide
        if ($config['reminderFrequency'] !== $frequency) {
            continue;
        }
        
        // Obtener tomas pendientes para este formulario
        $pending_takes = eipsi_get_pending_takes($form_id);
        
        foreach ($pending_takes as $take) {
            if ($reminders_sent >= $rate_limit) {
                break;
            }
            
            // Verificar que no se haya enviado ya hoy
            if (eipsi_reminder_sent_today($form_id, $take['email'])) {
                continue;
            }
            
            // Verificar que no esté unsubscribed
            if (eipsi_is_unsubscribed($form_id, $take['email'])) {
                continue;
            }
            
            // Verificar intentos fallidos (max 3)
            $failed_attempts = get_post_meta($form_id, "_eipsi_reminder_fails_{$take['email']}", true);
            if ($failed_attempts && intval($failed_attempts) >= 3) {
                error_log("[EIPSI Forms] Max failed attempts reached for {$take['email']}");
                continue;
            }
            
            // Generar token y enviar
            $sent = eipsi_send_take_reminder($form_id, $take);
            
            if ($sent) {
                $reminders_sent++;
            }
        }
    }
    
    error_log("[EIPSI Forms] {$frequency} reminders process completed. Sent: {$reminders_sent}");
}

/**
 * Obtiene tomas pendientes para un formulario
 * 
 * @param int $form_id Form ID
 * @return array Array de tomas pendientes { email, take_num, form_id, ... }
 * @since 1.3.0
 */
function eipsi_get_pending_takes($form_id) {
    global $wpdb;
    
    $pending = array();
    
    // Buscar todos los postmeta con pattern _eipsi_toma_*_assign
    $meta_table = $wpdb->postmeta;
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT meta_key, meta_value 
        FROM {$meta_table} 
        WHERE post_id = %d 
        AND meta_key LIKE %s",
        $form_id,
        $wpdb->esc_like('_eipsi_toma_') . '%' . $wpdb->esc_like('_assign')
    ));
    
    foreach ($results as $row) {
        $data = maybe_unserialize($row->meta_value);
        
        if (!is_array($data)) {
            continue;
        }
        
        // Solo tomas pendientes
        if (isset($data['status']) && $data['status'] === 'pending') {
            // Verificar si está vencida (>7 días sin responder)
            if (isset($data['timestamp'])) {
                $created = strtotime($data['timestamp']);
                $now = current_time('timestamp');
                $days_elapsed = floor(($now - $created) / DAY_IN_SECONDS);
                
                // Solo enviar recordatorios después de 7 días
                if ($days_elapsed < 7) {
                    continue;
                }
            }
            
            // Extraer take_num del meta_key (ej. _eipsi_toma_2_assign → 2)
            preg_match('/_eipsi_toma_(\d+)_assign/', $row->meta_key, $matches);
            $take_num = isset($matches[1]) ? intval($matches[1]) : 1;
            
            $pending[] = array(
                'email' => $data['email'] ?? '',
                'take_num' => $take_num,
                'form_id' => $data['form_id'] ?? $form_id,
                'seed' => $data['seed'] ?? '',
                'timestamp' => $data['timestamp'] ?? current_time('mysql'),
            );
        }
    }
    
    return $pending;
}

/**
 * Envía un recordatorio de toma pendiente
 * 
 * @param int $form_id Form ID
 * @param array $take Datos de la toma { email, take_num, form_id, seed }
 * @return bool True si se envió exitosamente
 * @since 1.3.0
 */
function eipsi_send_take_reminder($form_id, $take) {
    $email = $take['email'];
    $take_num = $take['take_num'];
    $assigned_form_id = $take['form_id'];
    $seed = $take['seed'];
    
    if (empty($email) || !is_email($email)) {
        return false;
    }
    
    // Generar token único
    $token = wp_generate_uuid4();
    
    // Guardar token en postmeta temporal (expira en 48h)
    $token_data = array(
        'token' => $token,
        'email' => $email,
        'form_id' => $assigned_form_id,
        'take' => $take_num,
        'seed' => $seed,
        'created' => current_time('mysql'),
        'expires' => gmdate('Y-m-d H:i:s', strtotime('+48 hours')),
        'manual' => false,
    );
    
    update_post_meta($form_id, '_eipsi_reminder_token_' . md5($email . $take_num), $token_data);
    
    // Construir link de recordatorio
    $reminder_link = add_query_arg(array(
        'eipsi_token' => $token,
        'form_id' => $assigned_form_id,
        'take' => $take_num,
    ), home_url('/formulario/'));
    
    // Construir link de unsubscribe
    $unsubscribe_link = add_query_arg(array(
        'eipsi_unsubscribe' => '1',
        'email' => urlencode($email),
        'form_id' => $form_id,
        'token' => $token,
    ), home_url('/'));
    
    // Cargar template de email
    ob_start();
    include EIPSI_FORMS_PLUGIN_DIR . 'includes/emails/reminder-take.php';
    $email_body = ob_get_clean();
    
    // Headers
    $headers = array('Content-Type: text/html; charset=UTF-8');
    
    // Subject
    $form_name = get_the_title($form_id);
    $subject = sprintf(__('Recordatorio: Tu Toma %d está lista - %s', 'eipsi-forms'), $take_num, $form_name);
    
    // Enviar email
    $sent = wp_mail($email, $subject, $email_body, $headers);
    
    if ($sent) {
        // Log envío exitoso
        update_post_meta($form_id, '_eipsi_reminder_sent_' . md5($email . $take_num), array(
            'timestamp' => current_time('mysql'),
            'token' => $token,
            'take' => $take_num,
        ));
        
        // Reset failed attempts counter
        delete_post_meta($form_id, "_eipsi_reminder_fails_{$email}");
        
        error_log("[EIPSI Forms] Reminder sent to {$email} for take {$take_num}");
    } else {
        // Incrementar contador de fallos
        $fails = get_post_meta($form_id, "_eipsi_reminder_fails_{$email}", true);
        $fails = $fails ? intval($fails) + 1 : 1;
        update_post_meta($form_id, "_eipsi_reminder_fails_{$email}", $fails);
        
        error_log("[EIPSI Forms] Failed to send reminder to {$email}. Attempt {$fails}/3");
    }
    
    return $sent;
}

/**
 * Verifica si ya se envió un recordatorio hoy para este email
 * 
 * @param int $form_id Form ID
 * @param string $email Email del participante
 * @return bool True si ya se envió hoy
 * @since 1.3.0
 */
function eipsi_reminder_sent_today($form_id, $email) {
    global $wpdb;
    
    $meta_table = $wpdb->postmeta;
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT meta_value 
        FROM {$meta_table} 
        WHERE post_id = %d 
        AND meta_key LIKE %s",
        $form_id,
        $wpdb->esc_like('_eipsi_reminder_sent_') . '%'
    ));
    
    $today = current_time('Y-m-d');
    
    foreach ($results as $row) {
        $data = maybe_unserialize($row->meta_value);
        
        if (is_array($data) && isset($data['timestamp'])) {
            $sent_date = gmdate('Y-m-d', strtotime($data['timestamp']));
            if ($sent_date === $today) {
                return true;
            }
        }
    }
    
    return false;
}

/**
 * Verifica si un email está unsubscribed
 * 
 * @param int $form_id Form ID
 * @param string $email Email del participante
 * @return bool True si está unsubscribed
 * @since 1.3.0
 */
function eipsi_is_unsubscribed($form_id, $email) {
    $unsubscribed = get_post_meta($form_id, '_eipsi_unsubscribed_' . md5($email), true);
    return !empty($unsubscribed);
}

/**
 * Handler para envío manual de recordatorio (programado)
 * 
 * @param string $email Email del participante
 * @param string $reminder_link Link de recordatorio
 * @since 1.3.0
 */
function eipsi_send_manual_reminder_handler($email, $reminder_link) {
    if (empty($email) || !is_email($email)) {
        return;
    }
    
    $headers = array('Content-Type: text/html; charset=UTF-8');
    $subject = __('Recordatorio: Toma pendiente', 'eipsi-forms');
    
    $body = sprintf(
        '<p>%s</p><p><a href="%s">%s</a></p>',
        __('Hola, tu toma está lista para ser completada.', 'eipsi-forms'),
        esc_url($reminder_link),
        __('Responder ahora', 'eipsi-forms')
    );
    
    wp_mail($email, $subject, $body, $headers);
    
    error_log("[EIPSI Forms] Manual reminder sent to {$email}");
}
