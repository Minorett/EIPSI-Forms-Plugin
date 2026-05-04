<?php
/**
 * Email Template: Weekly T1 Reminder (Phase 5 T1-Anchor)
 * 
 * Sent weekly to participants who haven't completed T1 after all nudges.
 * 
 * @package EIPSI_Forms
 * @since 2.6.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Generate weekly T1 reminder email
 * 
 * @param array $data Email data
 * @return array {subject, body}
 */
function eipsi_email_weekly_t1_reminder($data) {
    $participant_name = $data['participant_name'] ?? 'Participante';
    $study_name = $data['study_name'] ?? 'el estudio';
    $magic_link = $data['magic_link'] ?? '#';
    $reminder_number = $data['reminder_number'] ?? 1;
    
    $subject = "Recordatorio semanal: {$study_name}";
    
    $body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <p>Hola {$participant_name},</p>
            
            <p>Te recordamos que aún no has completado la primera evaluación del estudio <strong>{$study_name}</strong>.</p>
            
            <p>Este es tu recordatorio semanal #{$reminder_number}. Tu participación es muy importante para nosotros.</p>
            
            <div style='text-align: center; margin: 30px 0;'>
                <a href='{$magic_link}' style='background-color: #0073aa; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block; font-weight: bold;'>
                    Completar evaluación
                </a>
            </div>
            
            <p style='color: #666; font-size: 14px;'>
                Si tenés alguna duda o problema para acceder, no dudes en contactarnos respondiendo este email.
            </p>
            
            <p>Gracias por tu participación,<br>
            <strong>Equipo de {$study_name}</strong></p>
        </div>
    ";
    
    return array(
        'subject' => $subject,
        'body' => $body
    );
}
