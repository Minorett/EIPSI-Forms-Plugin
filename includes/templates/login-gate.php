<?php
/**
 * Login Gate Template
 * Mostrado cuando formulario requiere login y usuario NO está autenticado
 * 
 * @package EIPSI_Forms
 */

if (!defined('ABSPATH')) {
    exit;
}

// Variables disponibles:
// - $survey_id (int, opcional)
// - $form_title (string, opcional)
// - $form_description (string, opcional)
?>

<div class="eipsi-login-gate">
    <div class="eipsi-login-gate__container">
        
        <div class="eipsi-login-gate__header">
            <div class="eipsi-login-gate__icon">🔐</div>
            <h2 class="eipsi-login-gate__title">
                <?php esc_html_e('Formulario Protegido', 'eipsi-forms'); ?>
            </h2>
        </div>
        
        <div class="eipsi-login-gate__content">
            <p class="eipsi-login-gate__description">
                <?php esc_html_e('Para responder este formulario, necesitás iniciar sesión.', 'eipsi-forms'); ?>
            </p>
            
            <div class="eipsi-login-gate__actions">
                <button class="eipsi-login-gate__btn eipsi-login-gate__btn--primary survey-login-tab-trigger" 
                        data-tab="login"
                        data-survey-id="<?php echo esc_attr($survey_id ?? 0); ?>">
                    <?php esc_html_e('Ingresar a mi cuenta', 'eipsi-forms'); ?>
                </button>
                
                <button class="eipsi-login-gate__btn eipsi-login-gate__btn--secondary survey-login-tab-trigger" 
                        data-tab="register"
                        data-survey-id="<?php echo esc_attr($survey_id ?? 0); ?>">
                    <?php esc_html_e('Crear nueva cuenta', 'eipsi-forms'); ?>
                </button>
            </div>
        </div>
        
        <div class="eipsi-login-gate__footer">
            <div class="eipsi-login-gate__info">
                <svg class="eipsi-login-gate__info-icon" viewBox="0 0 24 24" width="20" height="20">
                    <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"/>
                    <line x1="12" y1="16" x2="12" y2="12" stroke="currentColor" stroke-width="2"/>
                    <line x1="12" y1="8" x2="12.01" y2="8" stroke="currentColor" stroke-width="2"/>
                </svg>
                <span class="eipsi-login-gate__info-text">
                    <?php esc_html_e('Tus respuestas se guardarán de forma segura con tu email.', 'eipsi-forms'); ?>
                </span>
            </div>
        </div>
        
    </div>
</div>

<!-- Inyectar login form aquí (será llenado por JavaScript) -->
<div class="eipsi-login-gate__form-container" id="eipsi-login-form-container"></div>