<?php
/**
 * Template: Survey Login/Registration Form
 * 
 * @package EIPSI_Forms
 * @since 1.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$survey_id = isset($atts['survey_id']) ? absint($atts['survey_id']) : 0;
$redirect_url = isset($atts['redirect_url']) ? esc_url($atts['redirect_url']) : '';
?>

<div class="eipsi-survey-login-container" id="eipsi-survey-login-<?php echo $survey_id; ?>" data-survey-id="<?php echo $survey_id; ?>" data-redirect="<?php echo esc_attr($redirect_url); ?>">
    
    <div class="eipsi-survey-login-tabs">
        <button type="button" class="eipsi-survey-login-tab active" data-tab="login">
            <?php esc_html_e('Ingresar', 'eipsi-forms'); ?>
        </button>
        <button type="button" class="eipsi-survey-login-tab" data-tab="register">
            <?php esc_html_e('Registro', 'eipsi-forms'); ?>
        </button>
    </div>

    <div class="eipsi-survey-login-content">
        <!-- Login Tab -->
        <div class="eipsi-survey-login-pane active" id="eipsi-login-pane">
            <form id="eipsi-participant-login-form" class="eipsi-participant-login-form eipsi-survey-login-form">
                <input type="hidden" name="survey_id" value="<?php echo $survey_id; ?>">
                
                <div class="eipsi-form-group">
                    <label for="login-email"><?php esc_html_e('Email', 'eipsi-forms'); ?></label>
                    <div class="eipsi-input-wrapper">
                        <input type="email" id="login-email" name="email" required placeholder="ejemplo@email.com" aria-label="<?php esc_attr_e('Email', 'eipsi-forms'); ?>">
                        <span class="eipsi-valid-icon">✓</span>
                    </div>
                </div>

                <div class="eipsi-form-group">
                    <label for="login-password"><?php esc_html_e('Contraseña', 'eipsi-forms'); ?></label>
                    <div class="eipsi-input-wrapper">
                        <input type="password" id="login-password" name="password" required aria-label="<?php esc_attr_e('Contraseña', 'eipsi-forms'); ?>">
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
                </div>

                <button type="submit" class="eipsi-button-primary">
                    <span class="button-text"><?php esc_html_e('Ingresar', 'eipsi-forms'); ?></span>
                    <span class="eipsi-spinner" style="display: none;"></span>
                </button>

                <div class="eipsi-form-footer">
                    <p><?php esc_html_e('¿No tenés cuenta?', 'eipsi-forms'); ?> <a href="#" class="switch-to-register"><?php esc_html_e('Crear una nueva', 'eipsi-forms'); ?></a></p>
                    <p><a href="#" class="forgot-password"><?php esc_html_e('¿Olvidaste tu contraseña?', 'eipsi-forms'); ?></a></p>
                </div>
            </form>
        </div>

        <!-- Register Tab -->
        <div class="eipsi-survey-login-pane" id="eipsi-register-pane">
            <form id="eipsi-participant-register-form" class="eipsi-participant-register-form eipsi-survey-login-form">
                <input type="hidden" name="survey_id" value="<?php echo $survey_id; ?>">
                
                <div class="eipsi-form-group">
                    <label for="register-email"><?php esc_html_e('Email', 'eipsi-forms'); ?></label>
                    <div class="eipsi-input-wrapper">
                        <input type="email" id="register-email" name="email" required placeholder="ejemplo@email.com" aria-label="<?php esc_attr_e('Email', 'eipsi-forms'); ?>">
                        <span class="eipsi-valid-icon">✓</span>
                    </div>
                </div>

                <div class="eipsi-row">
                    <div class="eipsi-form-group">
                        <label for="register-first-name"><?php esc_html_e('Nombre', 'eipsi-forms'); ?></label>
                        <input type="text" id="register-first-name" name="first_name" required aria-label="<?php esc_attr_e('Nombre', 'eipsi-forms'); ?>">
                    </div>
                    <div class="eipsi-form-group">
                        <label for="register-last-name"><?php esc_html_e('Apellido', 'eipsi-forms'); ?></label>
                        <input type="text" id="register-last-name" name="last_name" required aria-label="<?php esc_attr_e('Apellido', 'eipsi-forms'); ?>">
                    </div>
                </div>

                <div class="eipsi-form-group">
                    <label for="register-password"><?php esc_html_e('Contraseña', 'eipsi-forms'); ?></label>
                    <div class="eipsi-input-wrapper">
                        <input type="password" id="register-password" name="password" required minlength="8" aria-label="<?php esc_attr_e('Contraseña', 'eipsi-forms'); ?>" aria-describedby="password-hint">
                        <button type="button" class="toggle-password" aria-label="<?php esc_attr_e('Mostrar contraseña', 'eipsi-forms'); ?>">
                            <span class="dashicons dashicons-visibility"></span>
                        </button>
                    </div>
                    <small id="password-hint"><?php esc_html_e('Min 8 caracteres', 'eipsi-forms'); ?></small>
                </div>

                <div class="eipsi-form-group">
                    <label for="register-confirm-password"><?php esc_html_e('Confirmar Contraseña', 'eipsi-forms'); ?></label>
                    <div class="eipsi-input-wrapper">
                        <input type="password" id="register-confirm-password" name="confirm_password" required aria-label="<?php esc_attr_e('Confirmar Contraseña', 'eipsi-forms'); ?>">
                    </div>
                </div>

                <div class="eipsi-form-options">
                    <label class="eipsi-checkbox-label">
                        <input type="checkbox" name="accept_terms" required>
                        <?php esc_html_e('Acepto términos y condiciones', 'eipsi-forms'); ?>
                    </label>
                </div>

                <button type="submit" class="eipsi-button-primary">
                    <span class="button-text"><?php esc_html_e('Crear cuenta', 'eipsi-forms'); ?></span>
                    <span class="eipsi-spinner" style="display: none;"></span>
                </button>

                <div class="eipsi-form-footer">
                    <p><?php esc_html_e('¿Ya tenés cuenta?', 'eipsi-forms'); ?> <a href="#" class="switch-to-login"><?php esc_html_e('Ingresar aquí', 'eipsi-forms'); ?></a></p>
                </div>
            </form>
        </div>
    </div>
</div>
