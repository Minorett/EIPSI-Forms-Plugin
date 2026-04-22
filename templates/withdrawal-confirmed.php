<?php
/**
 * Template: Confirmación de Abandono - B1 (Abandono Estándar)
 * 
 * Pantalla de confirmación cuando el participante abandona el estudio
 * pero conserva sus datos para el análisis.
 * 
 * @since 2.5.0
 * @package EIPSI_Forms
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get site info
$site_name = get_bloginfo('name');
$home_url = home_url();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php _e('Abandono Confirmado', 'eipsi-forms'); ?> | <?php echo esc_html($site_name); ?></title>
    <?php wp_head(); ?>
    <style>
        /* ============================================
           FASE 3 - v2.5: Pantalla Confirmación B1
           ============================================ */
        .eipsi-withdrawal-confirmed {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 24px;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
        
        .eipsi-confirmation-card {
            background: #ffffff;
            border-radius: 24px;
            padding: 48px;
            max-width: 560px;
            width: 100%;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
        }
        
        .eipsi-confirmation-icon {
            width: 96px;
            height: 96px;
            background: #fef3c7;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            margin: 0 auto 24px;
        }
        
        .eipsi-confirmation-title {
            font-size: 28px;
            font-weight: 700;
            color: #1f2937;
            margin: 0 0 16px 0;
        }
        
        .eipsi-confirmation-subtitle {
            font-size: 18px;
            color: #6b7280;
            margin: 0 0 32px 0;
        }
        
        .eipsi-confirmation-details {
            background: #f9fafb;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 32px;
            text-align: left;
        }
        
        .eipsi-confirmation-details h3 {
            font-size: 16px;
            font-weight: 600;
            color: #374151;
            margin: 0 0 16px 0;
        }
        
        .eipsi-confirmation-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .eipsi-confirmation-list li {
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
            color: #4b5563;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .eipsi-confirmation-list li:last-child {
            border-bottom: none;
        }
        
        .eipsi-confirmation-list .icon {
            font-size: 20px;
            flex-shrink: 0;
        }
        
        .eipsi-confirmation-list .icon.check {
            color: #10b981;
        }
        
        .eipsi-confirmation-list .icon.info {
            color: #3b82f6;
        }
        
        .eipsi-confirmation-footer {
            margin-top: 32px;
        }
        
        .eipsi-confirmation-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 14px 28px;
            background: #3b6caa;
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.2s ease;
        }
        
        .eipsi-confirmation-btn:hover {
            background: #2d5a8e;
            transform: translateY(-2px);
        }
        
        .eipsi-confirmation-contact {
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid #e5e7eb;
        }
        
        .eipsi-confirmation-contact p {
            font-size: 13px;
            color: #6b7280;
            margin: 0 0 8px 0;
        }
        
        .eipsi-confirmation-contact a {
            color: #3b6caa;
            text-decoration: none;
            font-weight: 500;
        }
        
        .eipsi-confirmation-contact a:hover {
            text-decoration: underline;
        }
        
        /* Dark mode */
        @media (prefers-color-scheme: dark) {
            .eipsi-withdrawal-confirmed {
                background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
            }
            
            .eipsi-confirmation-card {
                background: #1f2937;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            }
            
            .eipsi-confirmation-title {
                color: #f9fafb;
            }
            
            .eipsi-confirmation-subtitle {
                color: #9ca3af;
            }
            
            .eipsi-confirmation-details {
                background: #374151;
            }
            
            .eipsi-confirmation-details h3 {
                color: #e5e7eb;
            }
            
            .eipsi-confirmation-list li {
                color: #d1d5db;
                border-color: #4b5563;
            }
            
            .eipsi-confirmation-contact {
                border-color: #4b5563;
            }
            
            .eipsi-confirmation-contact p {
                color: #9ca3af;
            }
        }
        
        /* Responsive */
        @media (max-width: 640px) {
            .eipsi-confirmation-card {
                padding: 32px 24px;
            }
            
            .eipsi-confirmation-title {
                font-size: 24px;
            }
            
            .eipsi-confirmation-subtitle {
                font-size: 16px;
            }
            
            .eipsi-confirmation-icon {
                width: 72px;
                height: 72px;
                font-size: 36px;
            }
        }
    </style>
</head>
<body>
    <div class="eipsi-withdrawal-confirmed">
        <div class="eipsi-confirmation-card">
            <div class="eipsi-confirmation-icon">👋</div>
            
            <h1 class="eipsi-confirmation-title">
                <?php _e('Abandono Confirmado', 'eipsi-forms'); ?>
            </h1>
            
            <p class="eipsi-confirmation-subtitle">
                <?php _e('Has salido del estudio exitosamente. Gracias por tu participación hasta ahora.', 'eipsi-forms'); ?>
            </p>
            
            <div class="eipsi-confirmation-details">
                <h3><?php _e('¿Qué significa esto?', 'eipsi-forms'); ?></h3>
                <ul class="eipsi-confirmation-list">
                    <li>
                        <span class="icon check">✓</span>
                        <span><?php _e('No te contactaremos para futuras olas del estudio', 'eipsi-forms'); ?></span>
                    </li>
                    <li>
                        <span class="icon check">✓</span>
                        <span><?php _e('Tus respuestas enviadas hasta ahora se conservan para el análisis', 'eipsi-forms'); ?></span>
                    </li>
                    <li>
                        <span class="icon check">✓</span>
                        <span><?php _e('Ya no tienes acceso al panel de participante', 'eipsi-forms'); ?></span>
                    </li>
                    <li>
                        <span class="icon info">ℹ</span>
                        <span><?php _e('Si cambias de opinión, contactá al investigador', 'eipsi-forms'); ?></span>
                    </li>
                </ul>
            </div>
            
            <div class="eipsi-confirmation-footer">
                <a href="<?php echo esc_url($home_url); ?>" class="eipsi-confirmation-btn">
                    <span>🏠</span>
                    <?php _e('Volver al inicio', 'eipsi-forms'); ?>
                </a>
            </div>
            
            <div class="eipsi-confirmation-contact">
                <p>
                    <?php _e('¿Tenés preguntas sobre tu participación?', 'eipsi-forms'); ?>
                </p>
                <a href="mailto:<?php echo esc_attr(get_option('admin_email')); ?>">
                    <?php _e('Contactar al investigador', 'eipsi-forms'); ?> →
                </a>
            </div>
        </div>
    </div>
    
    <?php wp_footer(); ?>
</body>
</html>
