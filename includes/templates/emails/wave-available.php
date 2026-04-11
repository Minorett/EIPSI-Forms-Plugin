<?php
/**
 * Template de email: Wave Disponible (Nudge 0)
 * 
 * Variables disponibles:
 * - $participant: Objeto participante
 * - $wave: Objeto wave
 * - $magic_link: Link mágico para acceder
 * - $study_id: ID del estudio
 */

$participant_name = trim($participant->first_name . ' ' . $participant->last_name);
$wave_name = $wave->name;
$wave_index = $wave->wave_index;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($subject); ?></title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #2563eb;
            font-size: 24px;
            margin: 0;
        }
        .content {
            margin-bottom: 30px;
        }
        .wave-badge {
            background: #dbeafe;
            color: #1e40af;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            display: inline-block;
            margin: 10px 0;
        }
        .button {
            display: inline-block;
            background: #2563eb;
            color: white;
            padding: 14px 32px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            margin: 20px 0;
        }
        .button:hover {
            background: #1d4ed8;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            color: #666;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
        .help-box {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎯 ¡Tu siguiente evaluación está disponible!</h1>
        </div>
        
        <div class="content">
            <p>Hola <strong><?php echo esc_html($participant_name); ?></strong>,</p>
            
            <p>¡Buenas noticias! Ya podés acceder a tu siguiente toma del estudio:</p>
            
            <div style="text-align: center;">
                <span class="wave-badge"><?php echo esc_html($wave_name); ?> (T<?php echo intval($wave_index); ?>)</span>
            </div>
            
            <p style="text-align: center;">
                <a href="<?php echo esc_url($magic_link); ?>" class="button">
                    Comenzar Evaluación →
                </a>
            </p>
            
            <p style="font-size: 14px; color: #666; text-align: center;">
                O copiá este link en tu navegador:<br>
                <code style="word-break: break-all;"><?php echo esc_url($magic_link); ?></code>
            </p>
            
            <div class="help-box">
                <strong>¿Necesitás ayuda?</strong><br>
                Si tenés problemas para acceder, respondé a este email o contactá al investigador.
            </div>
        </div>
        
        <div class="footer">
            <p>Este es un email automático del sistema EIPSI Forms.<br>
            Por favor no respondas directamente a este mensaje.</p>
        </div>
    </div>
</body>
</html>
