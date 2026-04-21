<?php
/**
 * Template: Pool Assigned (Página de Transición)
 *
 * Muestra confirmación de asignación exitosa con botón para ir al estudio.
 * Esta página se muestra en modo "transición" antes de redirigir al estudio.
 *
 * @package EIPSI_Forms
 * @since 2.5.4
 */

if (!defined('ABSPATH')) {
    exit;
}

// Variables esperadas desde pool-helpers.php:
// $pool_code, $participant_id, $assignment, $study_id, $study_url

$pool_code = $pool_code ?? '';
$study_id = $study_id ?? '';
$study_url = $study_url ?? '';

// Obtener info del estudio si existe
$study_name = $study_id;
$study_post = get_page_by_path($study_id, OBJECT, 'eipsi_study');
if ($study_post) {
    $study_name = $study_post->post_title;
}

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php _e('¡Asignación Exitosa!', 'eipsi-forms'); ?> | <?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .eipsi-assigned-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 480px;
            width: 100%;
            padding: 40px 32px;
            text-align: center;
        }
        
        .eipsi-assigned-header {
            margin-bottom: 32px;
        }
        
        .eipsi-assigned-icon {
            font-size: 64px;
            margin-bottom: 16px;
            animation: scaleIn 0.5s ease;
        }
        
        @keyframes scaleIn {
            0% { transform: scale(0); opacity: 0; }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); opacity: 1; }
        }
        
        .eipsi-assigned-title {
            font-size: 28px;
            font-weight: 700;
            color: #059669;
            margin-bottom: 8px;
        }
        
        .eipsi-assigned-subtitle {
            font-size: 16px;
            color: #64748b;
        }
        
        .eipsi-study-card {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border: 2px solid #86efac;
            border-radius: 12px;
            padding: 24px;
            margin: 24px 0;
            text-align: center;
        }
        
        .eipsi-study-label {
            font-size: 12px;
            font-weight: 600;
            color: #059669;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        
        .eipsi-study-name {
            font-size: 20px;
            font-weight: 700;
            color: #065f46;
        }
        
        .eipsi-study-code {
            font-size: 13px;
            color: #6b7280;
            font-family: monospace;
            margin-top: 8px;
        }
        
        .eipsi-info-box {
            background: #f8fafc;
            border-left: 4px solid #3b82f6;
            padding: 16px;
            border-radius: 8px;
            margin: 24px 0;
            text-align: left;
        }
        
        .eipsi-info-box h3 {
            font-size: 14px;
            font-weight: 600;
            color: #1e40af;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .eipsi-info-box ul {
            font-size: 14px;
            color: #475569;
            margin: 0;
            padding-left: 20px;
        }
        
        .eipsi-info-box li {
            margin-bottom: 6px;
        }
        
        .eipsi-assigned-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 18px 32px;
            font-size: 18px;
            font-weight: 600;
            color: white;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            border: none;
            border-radius: 12px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
            margin-top: 8px;
        }
        
        .eipsi-assigned-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(59, 130, 246, 0.4);
        }
        
        .eipsi-assigned-btn:active {
            transform: translateY(0);
        }
        
        .eipsi-assigned-footer {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            font-size: 13px;
            color: #94a3b8;
        }
        
        .eipsi-assigned-footer strong {
            color: #64748b;
        }
        
        /* Responsive */
        @media (max-width: 480px) {
            body {
                padding: 16px;
            }
            
            .eipsi-assigned-container {
                padding: 28px 20px;
            }
            
            .eipsi-assigned-title {
                font-size: 24px;
            }
            
            .eipsi-study-name {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <div class="eipsi-assigned-container">
        <!-- Header -->
        <div class="eipsi-assigned-header">
            <div class="eipsi-assigned-icon">✅</div>
            <h1 class="eipsi-assigned-title"><?php _e('¡Asignación Exitosa!', 'eipsi-forms'); ?></h1>
            <p class="eipsi-assigned-subtitle"><?php _e('Has sido asignado a un estudio', 'eipsi-forms'); ?></p>
        </div>
        
        <!-- Info del estudio asignado -->
        <div class="eipsi-study-card">
            <div class="eipsi-study-label"><?php _e('Estudio Asignado', 'eipsi-forms'); ?></div>
            <div class="eipsi-study-name"><?php echo esc_html($study_name); ?></div>
            <div class="eipsi-study-code"><?php echo esc_html($study_id); ?></div>
        </div>
        
        <!-- Información importante -->
        <div class="eipsi-info-box">
            <h3><span>ℹ️</span> <?php _e('Información importante', 'eipsi-forms'); ?></h3>
            <ul>
                <li><?php _e('Tu asignación fue completamente aleatoria y no puede modificarse.', 'eipsi-forms'); ?></li>
                <li><?php _e('A partir de ahora, participás en este estudio individualmente.', 'eipsi-forms'); ?></li>
                <li><?php _e('Podés completarlo en tus tiempos.', 'eipsi-forms'); ?></li>
            </ul>
        </div>
        
        <!-- Botón para ir al estudio -->
        <a href="<?php echo esc_url($study_url); ?>" class="eipsi-assigned-btn">
            <span>📋</span> <?php _e('Comenzar mi estudio', 'eipsi-forms'); ?>
        </a>
        
        <!-- Footer -->
        <div class="eipsi-assigned-footer">
            <p><?php _e('Pool:', 'eipsi-forms'); ?> <strong><?php echo esc_html($pool_code); ?></strong></p>
            <p style="margin-top: 8px;"><?php _e('Si tenés problemas, contactá al investigador.', 'eipsi-forms'); ?></p>
        </div>
    </div>
    
    <?php wp_footer(); ?>
</body>
</html>
