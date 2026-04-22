<?php
/**
 * Template: Confirmación de Eliminación - B2 (Eliminación de Datos)
 * 
 * Pantalla de confirmación cuando el participante solicita
 * la eliminación completa de sus datos del estudio.
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
    <title><?php _e('Datos Eliminados', 'eipsi-forms'); ?> | <?php echo esc_html($site_name); ?></title>
    <?php wp_head(); ?>
    <style>
        /* ============================================
           FASE 3 - v2.5: Pantalla Confirmación B2
           ============================================ */
        .eipsi-data-deleted {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 24px;
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
        }
        
        .eipsi-deletion-card {
            background: #ffffff;
            border-radius: 24px;
            padding: 48px;
            max-width: 560px;
            width: 100%;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(220, 38, 38, 0.15);
            border: 1px solid #fecaca;
        }
        
        .eipsi-deletion-icon {
            width: 96px;
            height: 96px;
            background: #fee2e2;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            margin: 0 auto 24px;
        }
        
        .eipsi-deletion-title {
            font-size: 28px;
            font-weight: 700;
            color: #991b1b;
            margin: 0 0 16px 0;
        }
        
        .eipsi-deletion-subtitle {
            font-size: 18px;
            color: #7f1d1d;
            margin: 0 0 32px 0;
            font-weight: 500;
        }
        
        .eipsi-deletion-warning {
            background: #fef2f2;
            border: 2px solid #fca5a5;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 32px;
            text-align: left;
        }
        
        .eipsi-deletion-warning-icon {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }
        
        .eipsi-deletion-warning-icon span {
            font-size: 24px;
        }
        
        .eipsi-deletion-warning-icon strong {
            color: #991b1b;
            font-size: 15px;
        }
        
        .eipsi-deletion-warning p {
            font-size: 14px;
            color: #7f1d1d;
            margin: 0;
            line-height: 1.6;
        }
        
        .eipsi-deletion-details {
            background: #f9fafb;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 32px;
            text-align: left;
        }
        
        .eipsi-deletion-details h3 {
            font-size: 16px;
            font-weight: 600;
            color: #374151;
            margin: 0 0 16px 0;
        }
        
        .eipsi-deletion-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .eipsi-deletion-list li {
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
            color: #4b5563;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .eipsi-deletion-list li:last-child {
            border-bottom: none;
        }
        
        .eipsi-deletion-list .icon {
            font-size: 20px;
            flex-shrink: 0;
        }
        
        .eipsi-deletion-list .icon.cross {
            color: #dc2626;
        }
        
        .eipsi-deletion-list .icon.check {
            color: #10b981;
        }
        
        .eipsi-deletion-footer {
            margin-top: 32px;
        }
        
        .eipsi-deletion-btn {
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
        
        .eipsi-deletion-btn:hover {
            background: #2d5a8e;
            transform: translateY(-2px);
        }
        
        .eipsi-deletion-contact {
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid #e5e7eb;
        }
        
        .eipsi-deletion-contact p {
            font-size: 13px;
            color: #6b7280;
            margin: 0 0 8px 0;
        }
        
        .eipsi-deletion-contact a {
            color: #3b6caa;
            text-decoration: none;
            font-weight: 500;
        }
        
        .eipsi-deletion-contact a:hover {
            text-decoration: underline;
        }
        
        /* Dark mode */
        @media (prefers-color-scheme: dark) {
            .eipsi-data-deleted {
                background: linear-gradient(135deg, #451a1a 0%, #7f1d1d 100%);
            }
            
            .eipsi-deletion-card {
                background: #1f2937;
                border-color: #7f1d1d;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            }
            
            .eipsi-deletion-title {
                color: #fca5a5;
            }
            
            .eipsi-deletion-subtitle {
                color: #fecaca;
            }
            
            .eipsi-deletion-warning {
                background: rgba(220, 38, 38, 0.1);
                border-color: #dc2626;
            }
            
            .eipsi-deletion-warning-icon strong {
                color: #fca5a5;
            }
            
            .eipsi-deletion-warning p {
                color: #fecaca;
            }
            
            .eipsi-deletion-details {
                background: #374151;
            }
            
            .eipsi-deletion-details h3 {
                color: #e5e7eb;
            }
            
            .eipsi-deletion-list li {
                color: #d1d5db;
                border-color: #4b5563;
            }
            
            .eipsi-deletion-contact {
                border-color: #4b5563;
            }
            
            .eipsi-deletion-contact p {
                color: #9ca3af;
            }
        }
        
        /* Responsive */
        @media (max-width: 640px) {
            .eipsi-deletion-card {
                padding: 32px 24px;
            }
            
            .eipsi-deletion-title {
                font-size: 24px;
            }
            
            .eipsi-deletion-subtitle {
                font-size: 16px;
            }
            
            .eipsi-deletion-icon {
                width: 72px;
                height: 72px;
                font-size: 36px;
            }
        }
    </style>
</head>
<body>
    <div class="eipsi-data-deleted">
        <div class="eipsi-deletion-card">
            <div class="eipsi-deletion-icon">🗑️</div>
            
            <h1 class="eipsi-deletion-title">
                <?php _e('Datos Eliminados Permanentemente', 'eipsi-forms'); ?>
            </h1>
            
            <p class="eipsi-deletion-subtitle">
                <?php _e('Tus respuestas han sido eliminadas del estudio y NO formarán parte del análisis.', 'eipsi-forms'); ?>
            </p>
            
            <div class="eipsi-deletion-warning">
                <div class="eipsi-deletion-warning-icon">
                    <span>⚠️</span>
                    <strong><?php _e('Esta acción es irreversible', 'eipsi-forms'); ?></strong>
                </div>
                <p>
                    <?php _e('Una vez eliminados, los datos no pueden ser recuperados. El investigador fue notificado de tu solicitud.', 'eipsi-forms'); ?>
                </p>
            </div>
            
            <div class="eipsi-deletion-details">
                <h3><?php _e('¿Qué se eliminó?', 'eipsi-forms'); ?></h3>
                <ul class="eipsi-deletion-list">
                    <li>
                        <span class="icon cross">✗</span>
                        <span><?php _e('Todas tus respuestas enviadas', 'eipsi-forms'); ?></span>
                    </li>
                    <li>
                        <span class="icon cross">✗</span>
                        <span><?php _e('Respuestas parciales guardadas', 'eipsi-forms'); ?></span>
                    </li>
                    <li>
                        <span class="icon cross">✗</span>
                        <span><?php _e('Tu participación en futuras olas', 'eipsi-forms'); ?></span>
                    </li>
                    <li>
                        <span class="icon check">✓</span>
                        <span><?php _e('Tu derecho a la eliminación fue respetado', 'eipsi-forms'); ?></span>
                    </li>
                </ul>
            </div>
            
            <div class="eipsi-deletion-footer">
                <a href="<?php echo esc_url($home_url); ?>" class="eipsi-deletion-btn">
                    <span>🏠</span>
                    <?php _e('Volver al inicio', 'eipsi-forms'); ?>
                </a>
            </div>
            
            <div class="eipsi-deletion-contact">
                <p>
                    <?php _e('¿Tenés preguntas sobre la eliminación de datos?', 'eipsi-forms'); ?>
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
