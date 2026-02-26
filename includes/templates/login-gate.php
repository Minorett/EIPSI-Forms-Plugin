<?php
/**
 * Login Gate Template (Enhanced v1.6.0)
 * 
 * Mejoras:
 * - Better visual design
 * - Progress indicators
 * - Clear instructions
 * - Magic link integration
 * - Responsive design
 * 
 * @package EIPSI_Forms
 * @since 1.6.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Variables disponibles:
// - $survey_id (int, opcional)
// - $form_title (string, opcional)
// - $form_description (string, opcional)

// Obtener información del formulario
$form_title = $form_title ?? __('Formulario Protegido', 'eipsi-forms');
$form_description = $form_description ?? __('Para responder este formulario, necesitás iniciar sesión o crear una cuenta.', 'eipsi-forms');

// Obtener nombre del estudio si existe
$study_name = '';
if (!empty($survey_id)) {
    global $wpdb;
    $study = $wpdb->get_row($wpdb->prepare(
        "SELECT study_name FROM {$wpdb->prefix}survey_studies WHERE id = %d",
        $survey_id
    ));
    if ($study) {
        $study_name = $study->study_name;
    }
}
?>

<div class="eipsi-login-gate">
    <div class="eipsi-login-gate__container">
        
        <!-- Progress Steps -->
        <div class="eipsi-login-steps">
            <div class="eipsi-step active" data-step="1">
                <span class="eipsi-step-number">1</span>
                <span class="eipsi-step-label"><?php esc_html_e('Acceso', 'eipsi-forms'); ?></span>
            </div>
            <div class="eipsi-step-connector"></div>
            <div class="eipsi-step" data-step="2">
                <span class="eipsi-step-number">2</span>
                <span class="eipsi-step-label"><?php esc_html_e('Formulario', 'eipsi-forms'); ?></span>
            </div>
            <div class="eipsi-step-connector"></div>
            <div class="eipsi-step" data-step="3">
                <span class="eipsi-step-number">3</span>
                <span class="eipsi-step-label"><?php esc_html_e('Confirmación', 'eipsi-forms'); ?></span>
            </div>
        </div>
        
        <div class="eipsi-login-gate__header">
            <div class="eipsi-login-gate__icon">🔐</div>
            <h2 class="eipsi-login-gate__title">
                <?php echo esc_html($study_name ?: $form_title); ?>
            </h2>
            <?php if ($study_name): ?>
                <span class="eipsi-login-gate__badge"><?php esc_html_e('Estudio Longitudinal', 'eipsi-forms'); ?></span>
            <?php endif; ?>
        </div>
        
        <div class="eipsi-login-gate__content">
            <p class="eipsi-login-gate__description">
                <?php echo esc_html($form_description); ?>
            </p>
            
            <!-- Benefits -->
            <div class="eipsi-login-gate__benefits">
                <div class="benefit-item">
                    <span class="benefit-icon">🔒</span>
                    <span class="benefit-text"><?php esc_html_e('Tus respuestas están seguras', 'eipsi-forms'); ?></span>
                </div>
                <div class="benefit-item">
                    <span class="benefit-icon">📊</span>
                    <span class="benefit-text"><?php esc_html_e('Seguimiento de tu progreso', 'eipsi-forms'); ?></span>
                </div>
                <div class="benefit-item">
                    <span class="benefit-icon">⏸️</span>
                    <span class="benefit-text"><?php esc_html_e('Continuás donde lo dejaste', 'eipsi-forms'); ?></span>
                </div>
            </div>
            
            <div class="eipsi-login-gate__actions">
                <button class="eipsi-login-gate__btn eipsi-login-gate__btn--primary survey-login-tab-trigger"
                        data-tab="login"
                        data-survey-id="<?php echo esc_attr($survey_id ?? 0); ?>">
                    <span class="btn-icon">🔑</span>
                    <?php esc_html_e('Ingresar a mi cuenta', 'eipsi-forms'); ?>
                </button>

                <button class="eipsi-login-gate__btn eipsi-login-gate__btn--secondary survey-login-tab-trigger"
                        data-tab="register"
                        data-survey-id="<?php echo esc_attr($survey_id ?? 0); ?>">
                    <span class="btn-icon">✨</span>
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
                    <?php esc_html_e('Tus respuestas se guardarán de forma segura y encriptada. Solo el investigador principal tiene acceso a los datos.', 'eipsi-forms'); ?>
                </span>
            </div>
        </div>
        
    </div>
</div>

<!-- Inyectar login form aquí (será llenado por JavaScript) -->
<div class="eipsi-login-gate__form-container" id="eipsi-login-form-container" style="display: none;"></div>
