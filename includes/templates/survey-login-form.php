<?php
/**
 * Template: Survey Login/Registration Form (Enhanced v1.6.0)
 * 
 * Mejoras:
 * - Progress indicators durante operaciones
 * - Magic link como alternativa de acceso
 * - Mejor UX con instrucciones claras
 * - Estados de carga visuales
 * - Validación en tiempo real mejorada
 * 
 * @package EIPSI_Forms
 * @since 1.6.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$survey_id = isset($atts['survey_id']) ? absint($atts['survey_id']) : 0;
$redirect_url = isset($atts['redirect_url']) ? esc_url($atts['redirect_url']) : '';

// Obtener información del estudio si existe
$study_name = '';
if ($survey_id) {
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

<div class="eipsi-survey-login-container" id="eipsi-survey-login-<?php echo esc_attr($survey_id); ?>" 
     data-survey-id="<?php echo esc_attr($survey_id); ?>" 
     data-redirect="<?php echo esc_attr($redirect_url); ?>">
    
    <!-- Header con información del estudio -->
    <div class="eipsi-login-header">
        <div class="eipsi-login-logo">🔬</div>
        <h2 class="eipsi-login-title">
            <?php echo $study_name ? esc_html($study_name) : esc_html__('Acceso al Estudio', 'eipsi-forms'); ?>
        </h2>
        <p class="eipsi-login-subtitle">
            <?php esc_html_e('Ingresá tus datos para participar', 'eipsi-forms'); ?>
        </p>
    </div>
    
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
    
    <!-- Tabs -->
    <div class="eipsi-survey-login-tabs">
        <button type="button" class="eipsi-survey-login-tab active" data-tab="login">
            <span class="tab-icon">🔑</span>
            <?php esc_html_e('Ingresar', 'eipsi-forms'); ?>
        </button>
        <button type="button" class="eipsi-survey-login-tab" data-tab="register">
            <span class="tab-icon">✨</span>
            <?php esc_html_e('Crear cuenta', 'eipsi-forms'); ?>
        </button>
        <button type="button" class="eipsi-survey-login-tab" data-tab="magic">
            <span class="tab-icon">✉️</span>
            <?php esc_html_e('Link mágico', 'eipsi-forms'); ?>
        </button>
    </div>

    <div class="eipsi-survey-login-content">
        <!-- Login Tab -->
        <div class="eipsi-survey-login-pane active" id="eipsi-login-pane">
            <div class="eipsi-pane-header">
                <p class="eipsi-pane-description">
                    <?php esc_html_e('Ingresá con tu email y contraseña para continuar.', 'eipsi-forms'); ?>
                </p>
            </div>
            
            <form id="eipsi-participant-login-form" class="eipsi-participant-login-form eipsi-survey-login-form">
                <?php wp_nonce_field('eipsi_participant_auth', 'eipsi_auth_nonce'); ?>
                <input type="hidden" name="survey_id" value="<?php echo esc_attr($survey_id); ?>">
                
                <div class="eipsi-form-group">
                    <label for="login-email">
                        <?php esc_html_e('Email', 'eipsi-forms'); ?>
                        <span class="required">*</span>
                    </label>
                    <div class="eipsi-input-wrapper">
                        <span class="input-icon">📧</span>
                        <input type="email" 
                               id="login-email" 
                               name="email" 
                               required 
                               placeholder="ejemplo@email.com" 
                               aria-label="<?php esc_attr_e('Email', 'eipsi-forms'); ?>"
                               autocomplete="email">
                        <span class="eipsi-valid-icon">✓</span>
                    </div>
                </div>

                <div class="eipsi-form-group">
                    <label for="login-password">
                        <?php esc_html_e('Contraseña', 'eipsi-forms'); ?>
                        <span class="required">*</span>
                    </label>
                    <div class="eipsi-input-wrapper">
                        <span class="input-icon">🔒</span>
                        <input type="password" 
                               id="login-password" 
                               name="password" 
                               required 
                               aria-label="<?php esc_attr_e('Contraseña', 'eipsi-forms'); ?>"
                               autocomplete="current-password">
                        <button type="button" class="toggle-password" aria-label="<?php esc_attr_e('Mostrar contraseña', 'eipsi-forms'); ?>">
                            <span class="dashicons dashicons-visibility"></span>
                        </button>
                    </div>
                </div>

                <div class="eipsi-form-options">
                    <label class="eipsi-checkbox-label">
                        <input type="checkbox" id="show-password-login" class="toggle-password-checkbox">
                        <?php esc_html_e('Mostrar contraseña', 'eipsi-forms'); ?>
                    </label>
                    <a href="#" class="forgot-password-link" data-tab="magic">
                        <?php esc_html_e('¿Olvidaste tu contraseña?', 'eipsi-forms'); ?>
                    </a>
                </div>

                <button type="submit" class="eipsi-button-primary">
                    <span class="button-text"><?php esc_html_e('Ingresar al estudio', 'eipsi-forms'); ?></span>
                    <span class="eipsi-spinner" style="display: none;"></span>
                </button>

                <div class="eipsi-form-footer">
                    <p><?php esc_html_e('¿No tenés cuenta?', 'eipsi-forms'); ?> 
                        <a href="#" class="switch-to-register"><?php esc_html_e('Creá una nueva', 'eipsi-forms'); ?></a>
                    </p>
                </div>
            </form>
        </div>

        <!-- Register Tab -->
        <div class="eipsi-survey-login-pane" id="eipsi-register-pane">
            <div class="eipsi-pane-header">
                <p class="eipsi-pane-description">
                    <?php esc_html_e('Completá tus datos para participar en el estudio.', 'eipsi-forms'); ?>
                </p>
            </div>
            
            <form id="eipsi-participant-register-form" class="eipsi-participant-register-form eipsi-survey-login-form">
                <?php wp_nonce_field('eipsi_participant_auth', 'eipsi_auth_nonce'); ?>
                <input type="hidden" name="survey_id" value="<?php echo esc_attr($survey_id); ?>">
                
                <?php if (empty($survey_id)): ?>
                <div class="eipsi-form-group">
                    <label for="register-study-code">
                        <?php esc_html_e('Código del Estudio', 'eipsi-forms'); ?>
                        <span class="required">*</span>
                    </label>
                    <div class="eipsi-input-wrapper">
                        <span class="input-icon">🔬</span>
                        <input type="text" 
                               id="register-study-code" 
                               name="study_code" 
                               required 
                               placeholder="Ejemplo: ESTUDIO_2025" 
                               aria-label="<?php esc_attr_e('Código del Estudio', 'eipsi-forms'); ?>">
                    </div>
                    <small class="field-hint"><?php esc_html_e('Ingresá el código que te proporcionó el investigador.', 'eipsi-forms'); ?></small>
                </div>
                <?php endif; ?>
                
                <div class="eipsi-form-group">
                    <label for="register-email">
                        <?php esc_html_e('Email', 'eipsi-forms'); ?>
                        <span class="required">*</span>
                    </label>
                    <div class="eipsi-input-wrapper">
                        <span class="input-icon">📧</span>
                        <input type="email" 
                               id="register-email" 
                               name="email" 
                               required 
                               placeholder="ejemplo@email.com" 
                               aria-label="<?php esc_attr_e('Email', 'eipsi-forms'); ?>"
                               autocomplete="email">
                        <span class="eipsi-valid-icon">✓</span>
                    </div>
                    <small class="field-hint"><?php esc_html_e('Usaremos este email para enviarte los recordatorios.', 'eipsi-forms'); ?></small>
                </div>

                <div class="eipsi-row">
                    <div class="eipsi-form-group">
                        <label for="register-first-name">
                            <?php esc_html_e('Nombre', 'eipsi-forms'); ?>
                            <span class="required">*</span>
                        </label>
                        <div class="eipsi-input-wrapper">
                            <input type="text" 
                                   id="register-first-name" 
                                   name="first_name" 
                                   required 
                                   aria-label="<?php esc_attr_e('Nombre', 'eipsi-forms'); ?>"
                                   autocomplete="given-name">
                        </div>
                    </div>
                    <div class="eipsi-form-group">
                        <label for="register-last-name">
                            <?php esc_html_e('Apellido', 'eipsi-forms'); ?>
                            <span class="required">*</span>
                        </label>
                        <div class="eipsi-input-wrapper">
                            <input type="text" 
                                   id="register-last-name" 
                                   name="last_name" 
                                   required 
                                   aria-label="<?php esc_attr_e('Apellido', 'eipsi-forms'); ?>"
                                   autocomplete="family-name">
                        </div>
                    </div>
                </div>

                <div class="eipsi-form-group">
                    <label for="register-password">
                        <?php esc_html_e('Contraseña', 'eipsi-forms'); ?>
                        <span class="required">*</span>
                    </label>
                    <div class="eipsi-input-wrapper">
                        <span class="input-icon">🔒</span>
                        <input type="password" 
                               id="register-password" 
                               name="password" 
                               required 
                               minlength="8" 
                               aria-label="<?php esc_attr_e('Contraseña', 'eipsi-forms'); ?>"
                               aria-describedby="password-hint"
                               autocomplete="new-password">
                        <button type="button" class="toggle-password" aria-label="<?php esc_attr_e('Mostrar contraseña', 'eipsi-forms'); ?>">
                            <span class="dashicons dashicons-visibility"></span>
                        </button>
                    </div>
                    <div class="password-strength-meter">
                        <div class="strength-bar"></div>
                        <span class="strength-text"></span>
                    </div>
                    <small id="password-hint"><?php esc_html_e('Mínimo 8 caracteres. Usá letras, números y símbolos.', 'eipsi-forms'); ?></small>
                </div>

                <div class="eipsi-form-group">
                    <label for="register-confirm-password">
                        <?php esc_html_e('Confirmar Contraseña', 'eipsi-forms'); ?>
                        <span class="required">*</span>
                    </label>
                    <div class="eipsi-input-wrapper">
                        <span class="input-icon">🔒</span>
                        <input type="password" 
                               id="register-confirm-password" 
                               name="confirm_password" 
                               required 
                               aria-label="<?php esc_attr_e('Confirmar Contraseña', 'eipsi-forms'); ?>"
                               autocomplete="new-password">
                    </div>
                </div>

                <div class="eipsi-form-options">
                    <label class="eipsi-checkbox-label">
                        <input type="checkbox" name="accept_terms" required>
                        <span><?php 
                            printf(
                                __('Acepto los %1$stérminos y condiciones%2$s y la %3$spolítica de privacidad%4$s', 'eipsi-forms'),
                                '<a href="#" target="_blank">',
                                '</a>',
                                '<a href="#" target="_blank">',
                                '</a>'
                            ); 
                        ?></span>
                    </label>
                </div>

                <button type="submit" class="eipsi-button-primary">
                    <span class="button-text"><?php esc_html_e('Crear cuenta y participar', 'eipsi-forms'); ?></span>
                    <span class="eipsi-spinner" style="display: none;"></span>
                </button>

                <div class="eipsi-form-footer">
                    <p><?php esc_html_e('¿Ya tenés cuenta?', 'eipsi-forms'); ?> 
                        <a href="#" class="switch-to-login"><?php esc_html_e('Ingresá aquí', 'eipsi-forms'); ?></a>
                    </p>
                </div>
            </form>
        </div>
        
        <!-- Magic Link Tab -->
        <div class="eipsi-survey-login-pane" id="eipsi-magic-pane">
            <div class="eipsi-pane-header">
                <p class="eipsi-pane-description">
                    <?php esc_html_e('Recibí un link mágico en tu email para acceder sin contraseña.', 'eipsi-forms'); ?>
                </p>
            </div>
            
            <form id="eipsi-magic-link-form" class="eipsi-magic-link-form eipsi-survey-login-form">
                <?php wp_nonce_field('eipsi_participant_auth', 'eipsi_auth_nonce'); ?>
                <input type="hidden" name="survey_id" value="<?php echo esc_attr($survey_id); ?>">
                
                <div class="magic-link-icon">✉️</div>
                
                <div class="eipsi-form-group">
                    <label for="magic-email">
                        <?php esc_html_e('Tu email registrado', 'eipsi-forms'); ?>
                        <span class="required">*</span>
                    </label>
                    <div class="eipsi-input-wrapper">
                        <span class="input-icon">📧</span>
                        <input type="email" 
                               id="magic-email" 
                               name="email" 
                               required 
                               placeholder="ejemplo@email.com" 
                               aria-label="<?php esc_attr_e('Email', 'eipsi-forms'); ?>"
                               autocomplete="email">
                        <span class="eipsi-valid-icon">✓</span>
                    </div>
                </div>

                <div class="magic-link-info">
                    <div class="info-item">
                        <span class="info-icon">⚡</span>
                        <span><?php esc_html_e('Acceso inmediato', 'eipsi-forms'); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-icon">🔒</span>
                        <span><?php esc_html_e('Válido por 48 horas', 'eipsi-forms'); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-icon">🔄</span>
                        <span><?php esc_html_e('Un solo uso', 'eipsi-forms'); ?></span>
                    </div>
                </div>

                <button type="submit" class="eipsi-button-primary eipsi-button-magic">
                    <span class="button-text"><?php esc_html_e('Enviar link mágico', 'eipsi-forms'); ?></span>
                    <span class="eipsi-spinner" style="display: none;"></span>
                </button>

                <div class="eipsi-form-footer">
                    <p><?php esc_html_e('¿Preferís usar contraseña?', 'eipsi-forms'); ?> 
                        <a href="#" class="switch-to-login"><?php esc_html_e('Ingresá con tu cuenta', 'eipsi-forms'); ?></a>
                    </p>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Security Notice -->
    <div class="eipsi-security-notice">
        <span class="security-icon">🔒</span>
        <span><?php esc_html_e('Tus datos están protegidos y encriptados.', 'eipsi-forms'); ?></span>
    </div>
</div>
