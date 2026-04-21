<?php
/**
 * Template: Pool Access
 *
 * Interfaz minimalista de acceso al Pool de Estudios
 * - Sin lista de estudios
 * - Sin duraciones estimadas
 * - Solo email + descripción + incentivo + consentimiento
 *
 * @package EIPSI_Forms
 * @since 2.5.4
 */

if (!defined('ABSPATH')) {
    exit;
}

// Variables esperadas desde pool-helpers.php:
// $pool_code, $pool_data

$pool_code = $pool_code ?? '';
$pool_data = $pool_data ?? array();

$description = $pool_data['description'] ?? '';
$incentive_message = $pool_data['incentive_message'] ?? '';
$title = $pool_data['title'] ?? sprintf(__('Pool: %s', 'eipsi-forms'), $pool_code);

// URL para procesar el acceso
$submit_url = home_url('/pool/' . sanitize_title($pool_code) . '/');

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($title); ?> | <?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .eipsi-pool-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 480px;
            width: 100%;
            padding: 40px 32px;
        }
        
        .eipsi-pool-header {
            text-align: center;
            margin-bottom: 32px;
        }
        
        .eipsi-pool-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }
        
        .eipsi-pool-title {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }
        
        .eipsi-pool-code {
            font-size: 13px;
            color: #64748b;
            font-family: monospace;
            background: #f1f5f9;
            padding: 4px 12px;
            border-radius: 20px;
            display: inline-block;
        }
        
        .eipsi-pool-section {
            margin-bottom: 24px;
        }
        
        .eipsi-pool-section-title {
            font-size: 14px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .eipsi-pool-description {
            font-size: 15px;
            line-height: 1.6;
            color: #334155;
            background: #f8fafc;
            padding: 16px;
            border-radius: 8px;
            border-left: 4px solid #3b82f6;
        }
        
        .eipsi-pool-incentive {
            font-size: 15px;
            line-height: 1.6;
            color: #059669;
            background: #ecfdf5;
            padding: 16px;
            border-radius: 8px;
            border-left: 4px solid #10b981;
        }
        
        .eipsi-pool-divider {
            height: 1px;
            background: #e2e8f0;
            margin: 28px 0;
        }
        
        .eipsi-pool-form-group {
            margin-bottom: 20px;
        }
        
        .eipsi-pool-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }
        
        .eipsi-pool-input {
            width: 100%;
            padding: 14px 16px;
            font-size: 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            transition: all 0.2s ease;
        }
        
        .eipsi-pool-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .eipsi-pool-checkbox-group {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin: 20px 0;
        }
        
        .eipsi-pool-checkbox {
            width: 20px;
            height: 20px;
            margin-top: 2px;
            cursor: pointer;
            accent-color: #3b82f6;
        }
        
        .eipsi-pool-checkbox-label {
            font-size: 14px;
            line-height: 1.5;
            color: #475569;
            cursor: pointer;
        }
        
        .eipsi-pool-submit-btn {
            width: 100%;
            padding: 16px 24px;
            font-size: 16px;
            font-weight: 600;
            color: white;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .eipsi-pool-submit-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4);
        }
        
        .eipsi-pool-submit-btn:disabled {
            background: #94a3b8;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .eipsi-pool-footer {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 14px;
            color: #64748b;
        }
        
        .eipsi-pool-footer a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
        }
        
        .eipsi-pool-footer a:hover {
            text-decoration: underline;
        }
        
        .eipsi-pool-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
        }
        
        /* Responsive */
        @media (max-width: 480px) {
            body {
                padding: 16px;
            }
            
            .eipsi-pool-container {
                padding: 28px 20px;
            }
            
            .eipsi-pool-title {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="eipsi-pool-container">
        <!-- Header -->
        <div class="eipsi-pool-header">
            <div class="eipsi-pool-icon">🎲</div>
            <h1 class="eipsi-pool-title"><?php echo esc_html($title); ?></h1>
            <span class="eipsi-pool-code"><?php echo esc_html($pool_code); ?></span>
        </div>
        
        <!-- Descripción -->
        <?php if (!empty($description)) : ?>
        <div class="eipsi-pool-section">
            <div class="eipsi-pool-section-title">
                <span>📋</span> <?php _e('Sobre este pool', 'eipsi-forms'); ?>
            </div>
            <div class="eipsi-pool-description">
                <?php echo wp_kses_post(nl2br(esc_html($description))); ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Incentivo -->
        <?php if (!empty($incentive_message)) : ?>
        <div class="eipsi-pool-section">
            <div class="eipsi-pool-section-title">
                <span>🎁</span> <?php _e('Por participar', 'eipsi-forms'); ?>
            </div>
            <div class="eipsi-pool-incentive">
                <?php echo wp_kses_post(nl2br(esc_html($incentive_message))); ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="eipsi-pool-divider"></div>
        
        <!-- Formulario -->
        <form method="post" action="<?php echo esc_url($submit_url); ?>" id="eipsi-pool-form">
            <?php wp_nonce_field('eipsi_pool_access', 'pool_nonce'); ?>
            
            <!-- Email -->
            <div class="eipsi-pool-form-group">
                <label class="eipsi-pool-label" for="participant_email">
                    📧 <?php _e('Tu Email', 'eipsi-forms'); ?>
                </label>
                <input 
                    type="email" 
                    name="participant_email" 
                    id="participant_email" 
                    class="eipsi-pool-input" 
                    placeholder="<?php esc_attr_e('tu@email.com', 'eipsi-forms'); ?>"
                    required
                    autocomplete="email"
                >
            </div>
            
            <?php 
            // Obtener modo de redirección (default: transition)
            $redirect_mode = isset($pool_data['redirect_mode']) ? $pool_data['redirect_mode'] : 'transition';
            ?>
            
            <?php if ($redirect_mode === 'transition') : ?>
            <!-- Modo Transición: Checkbox + Botón separados -->
            <div class="eipsi-pool-checkbox-group">
                <input 
                    type="checkbox" 
                    name="pool_consent" 
                    id="pool_consent" 
                    class="eipsi-pool-checkbox" 
                    required
                >
                <label for="pool_consent" class="eipsi-pool-checkbox-label">
                    <?php _e('Entiendo que seré asignado aleatoriamente a una intervención y no puedo elegir cuál.', 'eipsi-forms'); ?>
                </label>
            </div>
            
            <!-- Botón submit -->
            <button type="submit" class="eipsi-pool-submit-btn" id="eipsi-pool-submit" data-mode="transition">
                <span>🚀</span> <?php _e('Participar en el Pool', 'eipsi-forms'); ?>
            </button>
            <?php else : ?>
            <!-- Modo Mínimo: Botón integrado (1 click) -->
            <input type="hidden" name="pool_consent" value="1">
            <button type="submit" class="eipsi-pool-submit-btn eipsi-pool-submit-btn-integrated" id="eipsi-pool-submit" data-mode="minimal">
                <span>🚀</span> <?php _e('Participar - Entiendo que seré asignado aleatoriamente', 'eipsi-forms'); ?>
            </button>
            <p class="eipsi-pool-consent-note" style="font-size: 12px; color: #64748b; margin-top: 8px; text-align: center;">
                <?php _e('Al hacer click, aceptás participar y ser asignado aleatoriamente a un estudio.', 'eipsi-forms'); ?>
            </p>
            <?php endif; ?>
        </form>
        
        <!-- Footer -->
        <div class="eipsi-pool-footer">
            <?php _e('¿Ya tenés cuenta?', 'eipsi-forms'); ?> 
            <a href="<?php echo esc_url(wp_login_url($submit_url)); ?>">
                <?php _e('Ingresar aquí', 'eipsi-forms'); ?>
            </a>
        </div>
    </div>
    
    <?php wp_footer(); ?>
    
    <script>
        // Validación básica del formulario
        document.getElementById('eipsi-pool-form').addEventListener('submit', function(e) {
            const email = document.getElementById('participant_email').value;
            const submitBtn = document.getElementById('eipsi-pool-submit');
            const mode = submitBtn.getAttribute('data-mode');
            
            // Validar email siempre
            if (!email) {
                e.preventDefault();
                alert('<?php echo esc_js(__('Por favor completá tu email.', 'eipsi-forms')); ?>');
                return false;
            }
            
            // En modo transition, validar checkbox
            if (mode === 'transition') {
                const consent = document.getElementById('pool_consent').checked;
                if (!consent) {
                    e.preventDefault();
                    alert('<?php echo esc_js(__('Por favor aceptá el consentimiento para continuar.', 'eipsi-forms')); ?>');
                    return false;
                }
            }
            // En modo minimal, el consentimiento está implícito en el click
            
            // Deshabilitar botón para prevenir doble submit
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span>⏳</span> <?php echo esc_js(__('Procesando...', 'eipsi-forms')); ?>';
        });
    </script>
</body>
</html>
