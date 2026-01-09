<?php
/**
 * Email Template: Recordatorio de Toma Pendiente
 * 
 * Variables disponibles:
 * - $email: Email del participante
 * - $take_num: Número de toma
 * - $form_name: Nombre del formulario
 * - $reminder_link: Link de acceso con token
 * - $unsubscribe_link: Link para desuscribirse
 * 
 * @package EIPSI_Forms
 * @since 1.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$site_name = get_bloginfo('name');
$logo_url = get_site_icon_url(120);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .email-header {
            background: #2271b1;
            color: #ffffff;
            padding: 30px;
            text-align: center;
        }
        .email-header img {
            max-width: 80px;
            margin-bottom: 15px;
        }
        .email-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .email-body {
            padding: 40px 30px;
        }
        .email-body p {
            margin: 0 0 15px;
            font-size: 16px;
        }
        .cta-button {
            display: inline-block;
            background: #2271b1;
            color: #ffffff;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 5px;
            font-weight: 600;
            font-size: 16px;
            margin: 20px 0;
            text-align: center;
        }
        .cta-button:hover {
            background: #135e96;
        }
        .info-box {
            background: #f0f6fc;
            border-left: 4px solid #2271b1;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-box p {
            margin: 0;
            font-size: 14px;
            color: #1d2327;
        }
        .email-footer {
            background: #f5f5f5;
            padding: 20px 30px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #e0e0e0;
        }
        .email-footer a {
            color: #2271b1;
            text-decoration: none;
        }
        .email-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <?php if ($logo_url): ?>
                <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($site_name); ?>">
            <?php endif; ?>
            <h1><?php _e('Recordatorio de Toma Pendiente', 'eipsi-forms'); ?></h1>
        </div>
        
        <div class="email-body">
            <p><?php printf(__('Hola,', 'eipsi-forms')); ?></p>
            
            <p><?php printf(
                __('Te recordamos que tu <strong>Toma %d</strong> del estudio <strong>%s</strong> está lista para ser completada.', 'eipsi-forms'),
                $take_num,
                $form_name
            ); ?></p>
            
            <div class="info-box">
                <p><strong>⏱️ <?php _e('Importante:', 'eipsi-forms'); ?></strong> <?php _e('Este enlace es válido por 48 horas.', 'eipsi-forms'); ?></p>
            </div>
            
            <center>
                <a href="<?php echo esc_url($reminder_link); ?>" class="cta-button">
                    <?php _e('Responder ahora', 'eipsi-forms'); ?> →
                </a>
            </center>
            
            <p style="margin-top: 30px; font-size: 14px; color: #666;">
                <?php _e('Si tienes alguna pregunta, contacta con el equipo de investigación.', 'eipsi-forms'); ?>
            </p>
        </div>
        
        <div class="email-footer">
            <p>
                <?php printf(__('Este es un recordatorio automático de %s', 'eipsi-forms'), esc_html($site_name)); ?>
            </p>
            <p style="margin-top: 10px;">
                <a href="<?php echo esc_url($unsubscribe_link); ?>">
                    <?php _e('No quiero recibir más recordatorios', 'eipsi-forms'); ?>
                </a>
            </p>
        </div>
    </div>
</body>
</html>
