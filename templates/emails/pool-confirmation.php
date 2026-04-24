<?php
/**
 * Template de email: Confirmación de registro en Pool
 * 
 * Variables disponibles:
 * - $pool_name: Nombre del pool
 * - $confirm_url: URL de confirmación
 * - $participant_email: Email del participante
 * - $expiry_hours: Horas hasta expiración (default: 24)
 * 
 * @package EIPSI-Forms
 * @since 2.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$expiry = isset($expiry_hours) ? $expiry_hours : 24;
$site_name = get_bloginfo('name');
$site_url = home_url();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($subject); ?></title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .email-wrapper {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 40px 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e5e7eb;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #3b82f6;
            margin-bottom: 10px;
        }
        .pool-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        h1 {
            color: #1e293b;
            font-size: 22px;
            margin: 0 0 20px 0;
        }
        .content {
            color: #4b5563;
            font-size: 16px;
            margin-bottom: 30px;
        }
        .button-wrapper {
            text-align: center;
            margin: 30px 0;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: #ffffff;
            text-decoration: none;
            padding: 16px 32px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
        }
        .button:hover {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
        }
        .expiry-notice {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 12px 16px;
            margin: 20px 0;
            font-size: 14px;
            color: #92400e;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 13px;
            color: #6b7280;
        }
        .fallback-link {
            word-break: break-all;
            color: #3b82f6;
            font-size: 13px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="header">
            <div class="pool-icon">🏊</div>
            <div class="logo"><?php echo esc_html($site_name); ?></div>
        </div>
        
        <h1>¡Confirmá tu email para participar!</h1>
        
        <div class="content">
            <p>Hola,</p>
            
            <p>Gracias por tu interés en participar en <strong><?php echo esc_html($pool_name); ?></strong>.</p>
            
            <p>Para completar tu registro y acceder al pool de estudios, hacé clic en el siguiente botón:</p>
        </div>
        
        <div class="button-wrapper">
            <a href="<?php echo esc_url($confirm_url); ?>" class="button">
                Confirmar mi email
            </a>
        </div>
        
        <div class="expiry-notice">
            ⏳ Este enlace expira en <?php echo intval($expiry); ?> horas.
        </div>
        
        <div class="content">
            <p style="font-size: 14px; color: #6b7280;">
                Si el botón no funciona, copiá y pegá este enlace en tu navegador:
            </p>
            <p class="fallback-link">
                <?php echo esc_url($confirm_url); ?>
            </p>
        </div>
        
        <div class="footer">
            <p>
                Si no solicitaste este registro, podés ignorar este mensaje.<br>
                Tu cuenta no será activada.
            </p>
            <p style="margin-top: 15px;">
                <strong><?php echo esc_html($site_name); ?></strong><br>
                <a href="<?php echo esc_url($site_url); ?>" style="color: #3b82f6; text-decoration: none;">
                    <?php echo esc_url($site_url); ?>
                </a>
            </p>
        </div>
    </div>
</body>
</html>
