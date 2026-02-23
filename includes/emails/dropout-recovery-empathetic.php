<?php
/**
 * Email Template: Dropout Recovery (Empathetic v1.7.0)
 *
 * Email de recuperación con tono empático que muestra entendimiento
 * y mantiene la puerta abierta para dropouts, sin presión.
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
// $wave_name - Nombre de la wave/toma perdida
// $due_date - Fecha límite original (formato Y-m-d)
// $days_overdue - Días desde el deadline
// $magic_link - Link mágico para acceso directo
// $researcher_name - Nombre del investigador (opcional)
// $researcher_email - Email del investigador (opcional)

$participant_first_name = !empty($participant_name) ? explode(' ', trim($participant_name))[0] : '';
$greeting = !empty($participant_first_name) ? "Hola {$participant_first_name}," : "Hola,";

// Determinar mensaje basado en tiempo vencido
if ($days_overdue <= 3) {
    $recovery_message = __('Te extrañamos en el estudio. Si todavía tienes interés en participar, ¡estamos aquí para ti!', 'eipsi-forms');
    $recovery_emoji = '💭';
} elseif ($days_overdue <= 7) {
    $recovery_message = __('Ha pasado un tiempo desde que completaste la última toma. Queríamos saber cómo estás.', 'eipsi-forms');
    $recovery_emoji = '🤗';
} else {
    $recovery_message = __('Te extrañamos mucho. Si quieres volver a participar, la puerta está abierta.', 'eipsi-forms');
    $recovery_emoji = '💚';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo sprintf(esc_html__('Te extrañamos: %s', 'eipsi-forms'), esc_html($study_name)); ?></title>
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
        .empathy-box {
            background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
            border-left: 4px solid #f57c00;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 24px;
        }
        .empathy-box-title {
            font-weight: 600;
            margin-bottom: 8px;
            color: #e65100;
            font-size: 18px;
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
        .cta-primary {
            display: inline-block;
            background: linear-gradient(135deg, #2271b1 0%, #00a32a 100%);
            color: #ffffff;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            text-align: center;
            margin-bottom: 16px;
        }
        .cta-primary:hover {
            background: linear-gradient(135deg, #1e5a8f 0%, #008224 100%);
        }
        .cta-secondary {
            display: inline-block;
            background: #e0e0e0;
            color: #333;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            text-align: center;
        }
        .cta-secondary:hover {
            background: #d0d0d0;
        }
        .understanding-box {
            background: #f3e5f5;
            border-left: 4px solid #9c27b0;
            padding: 16px;
            border-radius: 6px;
            margin-bottom: 24px;
        }
        .understanding-box-title {
            font-weight: 600;
            margin-bottom: 8px;
            color: #7b1fa2;
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
            .cta-primary,
            .cta-secondary {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo"><?php echo esc_html($recovery_emoji); ?></div>
        </div>

        <!-- Greeting -->
        <p class="greeting"><?php echo esc_html($greeting); ?></p>

        <!-- Main Message -->
        <div class="message">
            <p><?php echo esc_html($recovery_message); ?></p>
            <p style="margin-top: 16px;">
                <?php esc_html_e('Entendemos que la vida a veces se complica y que surgen imprevistos. Quererte de vuelta no significa que debas justificar nada, solo que valoramos tu participación.', 'eipsi-forms'); ?>
            </p>
        </div>

        <!-- Empathy Box -->
        <div class="empathy-box">
            <div class="empathy-box-title">
                <?php esc_html_e('Te extrañamos en el estudio', 'eipsi-forms'); ?>
            </div>
            <p style="margin: 0;">
                <?php
                echo sprintf(
                    esc_html__('La toma "%s" del estudio %s estaba programada para el %s, pero eso es pasado. Lo importante es que si todavía quieres participar, ¡estamos aquí para apoyarte!', 'eipsi-forms'),
                    esc_html($wave_name),
                    esc_html($study_name),
                    esc_html(date('d/m/Y', strtotime($due_date)))
                );
                ?>
            </p>
        </div>

        <!-- Details Grid -->
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label"><?php esc_html_e('Toma pendiente:', 'eipsi-forms'); ?></div>
                <div class="info-value"><?php echo esc_html($wave_name); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label"><?php esc_html_e('Días desde deadline:', 'eipsi-forms'); ?></div>
                <div class="info-value"><?php echo absint($days_overdue); ?> días</div>
            </div>
        </div>

        <!-- CTAs -->
        <div style="text-align: center; margin-bottom: 24px;">
            <a href="<?php echo esc_url($magic_link); ?>" class="cta-primary">
                <?php esc_html_e('Volver al estudio', 'eipsi-forms'); ?> 💚
            </a>
            <div style="margin-top: 16px;">
                <a href="#" class="cta-secondary" id="eipsi-optout">
                    <?php esc_html_e('Prefiero no continuar', 'eipsi-forms'); ?>
                </a>
            </div>
        </div>

        <!-- Understanding Box -->
        <div class="understanding-box">
            <div class="understanding-box-title">
                <?php esc_html_e('Sin presiones, solo empatía', 'eipsi-forms'); ?> 💜
            </div>
            <p style="margin: 0;">
                <?php esc_html_e('Si decidiste no continuar, entendemos y respetamos tu decisión. Solo queríamos saber que la puerta está abierta si cambias de opinión.', 'eipsi-forms'); ?>
            </p>
        </div>

        <!-- Support -->
        <?php if (!empty($researcher_email)): ?>
        <div style="text-align: center; margin-bottom: 24px;">
            <p style="font-size: 14px; color: #666;">
                <?php
                echo sprintf(
                    esc_html__('Si tienes preguntas o necesitas hablar con alguien, escríbenos a %s', 'eipsi-forms'),
                    '<a href="mailto:' . esc_attr($researcher_email) . '">' . esc_html($researcher_email) . '</a>'
                );
                ?>
            </p>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="footer">
            <p><?php esc_html_e('Gracias por tu tiempo y consideración.', 'eipsi-forms'); ?></p>
            <p style="margin-top: 8px;">
                <?php
                if (!empty($researcher_name)) {
                    echo sprintf(
                        esc_html__('Con cariño y comprensión, %s y el equipo de investigación', 'eipsi-forms'),
                        esc_html($researcher_name)
                    );
                } else {
                    esc_html_e('Con cariño y comprensión, el equipo de investigación', 'eipsi-forms');
                }
                ?>
            </p>
        </div>
    </div>

    <!-- Optout tracking -->
    <script>
        document.getElementById('eipsi-optout').addEventListener('click', function(e) {
            e.preventDefault();
            // En la práctica, esto debería redirigir a una página de optout
            // o enviar un request AJAX para marcar el optout
            if (confirm('<?php esc_html_e('¿Estás seguro de que prefieres no continuar en el estudio? Podrás cambiar de opinión más tarde.', 'eipsi-forms'); ?>')) {
                window.location.href = '<?php echo esc_url(home_url('/')); ?>';
            }
        });
    </script>
</body>
</html>
