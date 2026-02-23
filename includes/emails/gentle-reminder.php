<?php
/**
 * Email Template: Gentle Reminder (Empathetic v1.7.0)
 *
 * Recordatorio amable y empático que reduce la sensación de presión
 * y aumenta la tasa de respuesta sin causar ansiedad.
 *
 * @package EIPSI_Forms
 * @since 1.7.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Variables disponibles:
// $participant_name - Nombre del participante (o vacío)
// $study_name - Nombre del estudio
// $wave_name - Nombre de la wave/toma
// $due_date - Fecha límite (formato Y-m-d)
// $magic_link - Link mágico para acceso directo
// $researcher_name - Nombre del investigador (opcional)
// $researcher_email - Email del investigador (opcional)

$participant_first_name = !empty($participant_name) ? explode(' ', trim($participant_name))[0] : '';
$greeting = !empty($participant_first_name) ? "Hola {$participant_first_name}," : "Hola,";

// Calcular días hasta el deadline
$days_until = $due_date ? ceil((strtotime($due_date) - current_time('timestamp')) / DAY_IN_SECONDS) : 0;

// Determinar tono basado en urgencia
if ($days_until <= 1) {
    $urgency_message = __('Este es un recordatorio amable de que tienes una toma pendiente para hoy o mañana.', 'eipsi-forms');
    $urgency_emoji = '⏰';
} elseif ($days_until <= 3) {
    $urgency_message = __('Este es un recordatorio amable de que tienes una toma pendiente pronto.', 'eipsi-forms');
    $urgency_emoji = '📅';
} else {
    $urgency_message = __('Este es un recordatorio amable de que tienes una toma pendiente próximamente.', 'eipsi-forms');
    $urgency_emoji = '🗓️';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo sprintf(esc_html__('Recordatorio: %s', 'eipsi-forms'), esc_html($study_name)); ?></title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: #ffffff;
            border-radius: 12px;
            padding: 32px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 24px;
        }
        .logo {
            font-size: 48px;
            margin-bottom: 8px;
        }
        .greeting {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 16px;
            color: #2271b1;
        }
        .message {
            font-size: 16px;
            margin-bottom: 24px;
        }
        .highlight-box {
            background: #f0f7ff;
            border-left: 4px solid #2271b1;
            padding: 16px;
            border-radius: 6px;
            margin-bottom: 24px;
        }
        .highlight-box-title {
            font-weight: 600;
            margin-bottom: 8px;
            color: #2271b1;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        .info-item {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 6px;
        }
        .info-label {
            font-size: 13px;
            color: #666;
            margin-bottom: 4px;
        }
        .info-value {
            font-size: 15px;
            font-weight: 600;
            color: #333;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #2271b1 0%, #00a32a 100%);
            color: #ffffff;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            text-align: center;
            margin-bottom: 24px;
        }
        .cta-button:hover {
            background: linear-gradient(135deg, #1e5a8f 0%, #008224 100%);
        }
        .no-pressure {
            background: #fff9e6;
            border-left: 4px solid #ffecb3;
            padding: 16px;
            border-radius: 6px;
            margin-bottom: 24px;
        }
        .no-pressure-title {
            font-weight: 600;
            margin-bottom: 8px;
            color: #f57c00;
        }
        .footer {
            text-align: center;
            padding-top: 24px;
            border-top: 1px solid #e0e0e0;
            font-size: 14px;
            color: #666;
        }
        .footer a {
            color: #2271b1;
            text-decoration: none;
        }
        .footer a:hover {
            text-decoration: underline;
        }
        @media (max-width: 480px) {
            .container {
                padding: 24px;
            }
            .info-grid {
                grid-template-columns: 1fr;
            }
            .cta-button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo">🌱</div>
        </div>

        <!-- Greeting -->
        <p class="greeting"><?php echo esc_html($greeting); ?></p>

        <!-- Main Message -->
        <div class="message">
            <p><?php esc_html_e('Te escribo desde el equipo de investigación para recordarte que tienes una toma pendiente en el estudio:', 'eipsi-forms'); ?></p>
        </div>

        <!-- Study Info -->
        <div class="highlight-box">
            <div class="highlight-box-title">
                📊 <?php echo esc_html($study_name); ?>
            </div>
            <div style="margin-top: 8px;">
                <?php echo esc_html($urgency_message); ?>
            </div>
        </div>

        <!-- Details Grid -->
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label"><?php esc_html_e('Toma pendiente:', 'eipsi-forms'); ?></div>
                <div class="info-value"><?php echo esc_html($wave_name); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label"><?php esc_html_e('Fecha límite:', 'eipsi-forms'); ?></div>
                <div class="info-value"><?php echo esc_html(date('d/m/Y', strtotime($due_date))); ?></div>
            </div>
        </div>

        <!-- CTA -->
        <div style="text-align: center;">
            <a href="<?php echo esc_url($magic_link); ?>" class="cta-button">
                <?php esc_html_e('Completar toma ahora', 'eipsi-forms'); ?>
            </a>
        </div>

        <!-- No Pressure Message -->
        <div class="no-pressure">
            <div class="no-pressure-title">
                <?php esc_html_e('Sin presión, solo recordatorio', 'eipsi-forms'); ?> 💚
            </div>
            <p style="margin: 0;">
                <?php esc_html_e('Entendemos que la vida a veces se complica. Toma tu tiempo, no hay prisa. Cuando estés listo/a, el link estará disponible.', 'eipsi-forms'); ?>
            </p>
        </div>

        <!-- Support -->
        <?php if (!empty($researcher_email)): ?>
        <div style="text-align: center; margin-bottom: 24px;">
            <p style="font-size: 14px; color: #666;">
                <?php
                echo sprintf(
                    esc_html__('Si tienes preguntas, escríbenos a %s', 'eipsi-forms'),
                    '<a href="mailto:' . esc_attr($researcher_email) . '">' . esc_html($researcher_email) . '</a>'
                );
                ?>
            </p>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="footer">
            <p><?php esc_html_e('Gracias por ser parte de este proyecto de investigación.', 'eipsi-forms'); ?></p>
            <p style="margin-top: 8px;">
                <?php
                if (!empty($researcher_name)) {
                    echo sprintf(
                        esc_html__('Con cariño, %s y el equipo de investigación', 'eipsi-forms'),
                        esc_html($researcher_name)
                    );
                } else {
                    esc_html_e('Con cariño, el equipo de investigación', 'eipsi-forms');
                }
                ?>
            </p>
        </div>
    </div>
</body>
</html>
