<?php
/**
 * Cron Reminders Tab
 * Configure automatic reminders for longitudinal studies
 *
 * @package EIPSI_Forms
 * @since 1.4.2
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get available studies from wp_survey_studies table
global $wpdb;
$studies = $wpdb->get_results(
    "SELECT id, study_name, study_code, status
    FROM {$wpdb->prefix}survey_studies
    WHERE status IN ('active', 'paused', 'completed')
    ORDER BY created_at DESC"
);

// Get selected study from URL
$selected_study_id = isset($_GET['study_id']) ? intval($_GET['study_id']) : 0;

// Get configuration for selected study from study config JSON
$config = array(
    'reminders_enabled' => false,
    'reminder_days_before' => 3,
    'max_reminder_emails' => 100,
    'dropout_recovery_enabled' => false,
    'dropout_recovery_days' => 7,
    'max_recovery_emails' => 50,
    'investigator_alert_enabled' => false,
    'investigator_alert_email' => get_option('admin_email'),
);

if ($selected_study_id) {
    $study_config = $wpdb->get_var($wpdb->prepare(
        "SELECT config FROM {$wpdb->prefix}survey_studies WHERE id = %d",
        $selected_study_id
    ));

    if ($study_config) {
        $config_data = json_decode($study_config, true);
        if (is_array($config_data)) {
            // Merge with defaults, ensuring all keys exist
            $config = array_merge($config, array_intersect_key($config_data, $config));
        }
    }
}

$investigator_email = $config['investigator_alert_email'] ?? '';
if (empty($investigator_email)) {
    $investigator_email = get_option('admin_email');
}
$config['investigator_alert_email'] = $investigator_email;
?>

<div class="eipsi-cron-reminders-tab">

    <!-- Info Box -->
    <div class="notice notice-info inline" style="margin: 0 0 20px 0; padding: 15px 20px; border-left: 4px solid #3B6CAA;">
        <p style="margin: 0; font-size: 14px; line-height: 1.5;">
            <strong style="color: #3B6CAA; display: block; margin-bottom: 8px;">
                ⏰ <?php _e('Configuración de Recordatorios Automáticos', 'eipsi-forms'); ?>
            </strong>
            <?php _e('Configura el envío automático de recordatorios para waves pendientes y recuperación de participantes inactivos. Los cron jobs se ejecutan cada hora.', 'eipsi-forms'); ?>
        </p>
    </div>

    <!-- Survey Selector -->
    <?php if (empty($studies)): ?>
        <div class="notice notice-warning inline" style="padding: 15px 20px; border-left: 4px solid #ffc107;">
            <p style="margin: 0;">
                <strong>⚠️ <?php _e('No hay estudios disponibles', 'eipsi-forms'); ?></strong><br>
                <?php _e('Primero crea un estudio longitudinal para configurar los recordatorios.', 'eipsi-forms'); ?>
            </p>
        </div>
    <?php else: ?>
        <div class="eipsi-field-group" style="margin: 25px 0; padding: 20px; background: #ffffff; border: 1px solid #e0e0e0; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <label for="study_selector" style="display: block; margin-bottom: 10px; font-weight: 600; color: #3B6CAA; font-size: 14px;">
                📊 <?php _e('Seleccionar Estudio Longitudinal', 'eipsi-forms'); ?>
            </label>
            <select id="study_selector" aria-describedby="study_selector_help" style="width: 100%; max-width: 600px; padding: 10px 12px; border: 2px solid #e0e0e0; border-radius: 6px; font-size: 14px; transition: border-color 0.2s;">
                <option value=""><?php _e('-- Seleccionar un estudio --', 'eipsi-forms'); ?></option>
                <?php foreach ($studies as $study): ?>
                    <option value="<?php echo esc_attr($study->id); ?>" <?php selected($selected_study_id, $study->id); ?>>
                        <?php echo esc_html($study->study_name); ?> (<?php echo esc_html($study->study_code); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <p id="study_selector_help" style="margin: 8px 0 0 0; font-size: 13px; color: #666;">
                <?php _e('Selecciona un estudio para ver y configurar sus recordatorios automáticos.', 'eipsi-forms'); ?>
            </p>
        </div>

        <!-- Configuration Form (hidden until study is selected) -->
        <div id="cron_config_form_wrapper" style="<?php echo $selected_study_id ? '' : 'display: none;'; ?>">
            <form id="eipsi_cron_reminders_form" method="post">
                <?php wp_nonce_field('eipsi_admin_nonce', 'eipsi_admin_nonce'); ?>
                <input type="hidden" id="selected_study_id" name="study_id" value="<?php echo esc_attr($selected_study_id); ?>">

                <!-- Section: Wave Reminders -->
                <div class="eipsi-config-section" style="margin: 30px 0; padding: 25px; background: #ffffff; border: 2px solid #3B6CAA; border-radius: 8px; box-shadow: 0 2px 8px rgba(59, 108, 170, 0.1);">
                    <h3 style="margin-top: 0; margin-bottom: 20px; color: #3B6CAA; font-size: 18px; display: flex; align-items: center; gap: 10px;">
                        ⏰ <?php _e('Recordatorios de Waves Pendientes', 'eipsi-forms'); ?>
                    </h3>

                    <label class="eipsi-toggle-label" style="display: flex; align-items: flex-start; gap: 12px; margin-bottom: 20px; cursor: pointer; padding: 12px; background: #f0f7fc; border-radius: 6px; transition: background 0.2s;">
                        <input type="checkbox" id="reminders_enabled" name="reminders_enabled" <?php checked(!empty($config['reminders_enabled'])); ?> style="margin-top: 3px; width: 20px; height: 20px; cursor: pointer;">
                        <div>
                            <strong style="display: block; margin-bottom: 4px; color: #2c3e50;">
                                <?php _e('Enviar recordatorios automáticos de waves pendientes', 'eipsi-forms'); ?>
                            </strong>
                            <span style="color: #666; font-size: 13px; display: block;">
                                <?php _e('Los participantes recibirán correos recordando completar sus waves pendientes cuando falten X días para el vencimiento.', 'eipsi-forms'); ?>
                                <br>
                                <em style="color: #3B6CAA;"><?php _e('Ejecución: cada hora', 'eipsi-forms'); ?></em>
                            </span>
                        </div>
                    </label>

                    <div class="eipsi-input-group" style="margin: 15px 0; padding: 18px; background: #fafbfc; border-left: 3px solid #3B6CAA; border-radius: 0 4px 4px 0;">
                        <label for="reminder_days_before" style="display: block; margin-bottom: 10px; font-weight: 600; color: #2c3e50; font-size: 14px;">
                            📅 <?php _e('Días antes de vencimiento para enviar recordatorios', 'eipsi-forms'); ?>
                        </label>
                        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                            <input type="number"
                                   id="reminder_days_before"
                                   name="reminder_days_before"
                                   value="<?php echo esc_attr(intval($config['reminder_days_before'])); ?>"
                                   min="1"
                                   max="30"
                                   aria-describedby="reminder_days_before_help"
                                   style="width: 120px; padding: 10px 12px; border: 2px solid #e0e0e0; border-radius: 6px; font-size: 14px; font-weight: 500;">
                            <span style="color: #666; font-size: 13px; background: #e8ecef; padding: 4px 10px; border-radius: 12px;">
                                <?php _e('Rango: 1-30 días', 'eipsi-forms'); ?>
                            </span>
                        </div>
                        <p id="reminder_days_before_help" style="margin: 8px 0 0 0; font-size: 12px; color: #666;">
                            <?php _e('¿Con cuánta anticipación quieres que se envíen los recordatorios?', 'eipsi-forms'); ?>
                        </p>
                    </div>

                    <div class="eipsi-input-group" style="margin: 15px 0; padding: 18px; background: #fafbfc; border-left: 3px solid #3B6CAA; border-radius: 0 4px 4px 0;">
                        <label for="max_reminder_emails" style="display: block; margin-bottom: 10px; font-weight: 600; color: #2c3e50; font-size: 14px;">
                            📧 <?php _e('Máximo de emails de recordatorio por ejecución cron', 'eipsi-forms'); ?>
                        </label>
                        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                            <input type="number"
                                   id="max_reminder_emails"
                                   name="max_reminder_emails"
                                   value="<?php echo esc_attr(intval($config['max_reminder_emails'])); ?>"
                                   min="1"
                                   max="500"
                                   aria-describedby="max_reminder_emails_help"
                                   style="width: 120px; padding: 10px 12px; border: 2px solid #e0e0e0; border-radius: 6px; font-size: 14px; font-weight: 500;">
                            <span style="color: #666; font-size: 13px; background: #e8ecef; padding: 4px 10px; border-radius: 12px;">
                                <?php _e('Rango: 1-500 emails', 'eipsi-forms'); ?>
                            </span>
                        </div>
                        <p id="max_reminder_emails_help" style="margin: 8px 0 0 0; font-size: 12px; color: #666;">
                            <?php _e('Limita la carga del servidor evitando enviar demasiados emails en una sola ejecución.', 'eipsi-forms'); ?>
                        </p>
                    </div>
                </div>

                <!-- Section: Dropout Recovery -->
                <div class="eipsi-config-section" style="margin: 30px 0; padding: 25px; background: #fffdf5; border: 2px solid #f0ad4e; border-radius: 8px; box-shadow: 0 2px 8px rgba(240, 173, 78, 0.15);">
                    <h3 style="margin-top: 0; margin-bottom: 20px; color: #856404; font-size: 18px; display: flex; align-items: center; gap: 10px;">
                        💔 <?php _e('Recuperación de Participantes Inactivos (Dropouts)', 'eipsi-forms'); ?>
                    </h3>

                    <label class="eipsi-toggle-label" style="display: flex; align-items: flex-start; gap: 12px; margin-bottom: 20px; cursor: pointer; padding: 12px; background: #fff8e1; border-radius: 6px; transition: background 0.2s;">
                        <input type="checkbox" id="dropout_recovery_enabled" name="dropout_recovery_enabled" <?php checked(!empty($config['dropout_recovery_enabled'])); ?> style="margin-top: 3px; width: 20px; height: 20px; cursor: pointer;">
                        <div>
                            <strong style="display: block; margin-bottom: 4px; color: #856404;">
                                <?php _e('Activar recuperación de participantes inactivos', 'eipsi-forms'); ?>
                            </strong>
                            <span style="color: #666; font-size: 13px; display: block;">
                                <?php _e('Envía un mensaje "Te extrañamos" a participantes que no han completado waves vencidas después de X días.', 'eipsi-forms'); ?>
                                <br>
                                <em style="color: #f0ad4e;">💌 <?php _e('Template: Mensaje de recuperación personalizado', 'eipsi-forms'); ?></em>
                            </span>
                        </div>
                    </label>

                    <div class="eipsi-input-group" style="margin: 15px 0; padding: 18px; background: #ffffff; border-left: 3px solid #f0ad4e; border-radius: 0 4px 4px 0;">
                        <label for="dropout_recovery_days" style="display: block; margin-bottom: 10px; font-weight: 600; color: #2c3e50; font-size: 14px;">
                            📆 <?php _e('Días después de vencimiento para considerar dropout', 'eipsi-forms'); ?>
                        </label>
                        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                            <input type="number"
                                   id="dropout_recovery_days"
                                   name="dropout_recovery_days"
                                   value="<?php echo esc_attr(intval($config['dropout_recovery_days'])); ?>"
                                   min="1"
                                   max="90"
                                   aria-describedby="dropout_recovery_days_help"
                                   style="width: 120px; padding: 10px 12px; border: 2px solid #e0e0e0; border-radius: 6px; font-size: 14px; font-weight: 500;">
                            <span style="color: #666; font-size: 13px; background: #fff3cd; padding: 4px 10px; border-radius: 12px;">
                                <?php _e('Rango: 1-90 días', 'eipsi-forms'); ?>
                            </span>
                        </div>
                        <p id="dropout_recovery_days_help" style="margin: 8px 0 0 0; font-size: 12px; color: #666;">
                            <?php _e('¿Cuántos días después del vencimiento quieres que se envíe el mensaje de recuperación?', 'eipsi-forms'); ?>
                        </p>
                    </div>

                    <div class="eipsi-input-group" style="margin: 15px 0; padding: 18px; background: #ffffff; border-left: 3px solid #f0ad4e; border-radius: 0 4px 4px 0;">
                        <label for="max_recovery_emails" style="display: block; margin-bottom: 10px; font-weight: 600; color: #2c3e50; font-size: 14px;">
                            📩 <?php _e('Máximo de emails de recuperación por ejecución cron', 'eipsi-forms'); ?>
                        </label>
                        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                            <input type="number"
                                   id="max_recovery_emails"
                                   name="max_recovery_emails"
                                   value="<?php echo esc_attr(intval($config['max_recovery_emails'])); ?>"
                                   min="1"
                                   max="500"
                                   aria-describedby="max_recovery_emails_help"
                                   style="width: 120px; padding: 10px 12px; border: 2px solid #e0e0e0; border-radius: 6px; font-size: 14px; font-weight: 500;">
                            <span style="color: #666; font-size: 13px; background: #fff3cd; padding: 4px 10px; border-radius: 12px;">
                                <?php _e('Rango: 1-500 emails', 'eipsi-forms'); ?>
                            </span>
                        </div>
                        <p id="max_recovery_emails_help" style="margin: 8px 0 0 0; font-size: 12px; color: #666;">
                            <?php _e('Limita cuántos emails de recuperación se envían en una ejecución para evitar sobrecarga.', 'eipsi-forms'); ?>
                        </p>
                    </div>
                </div>

                <!-- Section: Investigator Alerts -->
                <div class="eipsi-config-section" style="margin: 30px 0; padding: 25px; background: #f0fff4; border: 2px solid #28a745; border-radius: 8px; box-shadow: 0 2px 8px rgba(40, 167, 69, 0.1);">
                    <h3 style="margin-top: 0; margin-bottom: 20px; color: #155724; font-size: 18px; display: flex; align-items: center; gap: 10px;">
                        📧 <?php _e('Alertas al Investigador', 'eipsi-forms'); ?>
                    </h3>

                    <label class="eipsi-toggle-label" style="display: flex; align-items: flex-start; gap: 12px; margin-bottom: 20px; cursor: pointer; padding: 12px; background: #e8f5e9; border-radius: 6px; transition: background 0.2s;">
                        <input type="checkbox" id="investigator_alert_enabled" name="investigator_alert_enabled" <?php checked(!empty($config['investigator_alert_enabled'])); ?> style="margin-top: 3px; width: 20px; height: 20px; cursor: pointer;">
                        <div>
                            <strong style="display: block; margin-bottom: 4px; color: #155724;">
                                <?php _e('Recibir resúmenes de actividad del cron', 'eipsi-forms'); ?>
                            </strong>
                            <span style="color: #666; font-size: 13px; display: block;">
                                <?php _e('Recibirás un resumen por email después de cada ejecución del cron que envíe correos.', 'eipsi-forms'); ?>
                                <br>
                                <em style="color: #28a745;">📊 <?php _e('Incluye: emails enviados, participantes notificados, errores', 'eipsi-forms'); ?></em>
                            </span>
                        </div>
                    </label>

                    <div class="eipsi-input-group" style="margin: 15px 0; padding: 18px; background: #ffffff; border-left: 3px solid #28a745; border-radius: 0 4px 4px 0;">
                        <label for="investigator_alert_email" style="display: block; margin-bottom: 10px; font-weight: 600; color: #2c3e50; font-size: 14px;">
                            📮 <?php _e('Email del investigador para alertas', 'eipsi-forms'); ?>
                        </label>
                        <input type="email"
                               id="investigator_alert_email"
                               name="investigator_alert_email"
                               value="<?php echo esc_attr($config['investigator_alert_email']); ?>"
                               placeholder="investigador@ejemplo.com"
                               aria-describedby="investigator_alert_email_help"
                               style="width: 100%; max-width: 450px; padding: 10px 12px; border: 2px solid #e0e0e0; border-radius: 6px; font-size: 14px;">
                        <div style="display: flex; align-items: center; gap: 10px; margin-top: 8px; flex-wrap: wrap;">
                            <span style="color: #666; font-size: 13px; background: #e8f5e9; padding: 4px 10px; border-radius: 12px;">
                                <?php _e('Formato: email@ejemplo.com', 'eipsi-forms'); ?>
                            </span>
                            <span style="color: #666; font-size: 13px; background: #e8f5e9; padding: 4px 10px; border-radius: 12px;">
                                <?php _e('Default: ' . esc_html(get_option('admin_email')), 'eipsi-forms'); ?>
                            </span>
                        </div>
                        <p id="investigator_alert_email_help" style="margin: 8px 0 0 0; font-size: 12px; color: #666;">
                            <?php _e('Email donde recibirás los resúmenes de actividad del cron job.', 'eipsi-forms'); ?>
                        </p>
                    </div>
                </div>

                <!-- Save Button -->
                <div style="margin-top: 35px; padding-top: 25px; border-top: 2px solid #e0e0e0;">
                    <button type="submit" class="button button-primary" id="eipsi_save_cron_config" style="padding: 12px 24px; font-size: 15px; font-weight: 600; height: auto; box-shadow: 0 2px 4px rgba(59, 108, 170, 0.2); transition: all 0.2s;">
                        💾 <?php _e('Guardar Configuración', 'eipsi-forms'); ?>
                    </button>
                    <span id="eipsi_cron_spinner" class="spinner" style="display: none; margin-left: 15px; float: none; vertical-align: middle;"></span>
                    <span id="eipsi_cron_status" style="margin-left: 15px; font-weight: 600; vertical-align: middle;"></span>
                </div>
            </form>

            <!-- Info Box -->
            <div style="margin: 40px 0; padding: 25px; background: #f8f9fa; border-left: 5px solid #6c757d; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                <h3 style="margin-top: 0; margin-bottom: 15px; color: #495057; font-size: 17px; display: flex; align-items: center; gap: 10px;">
                    💡 <?php _e('¿Cómo funciona el sistema de recordatorios?', 'eipsi-forms'); ?>
                </h3>
                <ul style="margin: 0 0 20px 0; padding-left: 25px; color: #495057; font-size: 14px; line-height: 1.7;">
                    <li style="margin-bottom: 12px;">
                        <strong style="color: #3B6CAA;">⏰ Recordatorios de waves:</strong>
                        <?php _e('Se envían automáticamente a participantes con waves pendientes cuando faltan X días para el vencimiento.', 'eipsi-forms'); ?>
                    </li>
                    <li style="margin-bottom: 12px;">
                        <strong style="color: #f0ad4e;">💔 Recuperación de dropouts:</strong>
                        <?php _e('Se envía un mensaje "Te extrañamos" a participantes que no han completado waves vencidas después de X días.', 'eipsi-forms'); ?>
                    </li>
                    <li style="margin-bottom: 12px;">
                        <strong style="color: #17a2b8;">🛡️ Rate limiting:</strong>
                        <?php _e('Cada participante recibe máximo 1 email por cada wave en 24 horas (usando transients de WordPress).', 'eipsi-forms'); ?>
                    </li>
                    <li style="margin-bottom: 12px;">
                        <strong style="color: #dc3545;">📊 Max emails por ejecución:</strong>
                        <?php _e('Limita la carga del servidor evitando enviar demasiados emails en una sola ejecución.', 'eipsi-forms'); ?>
                    </li>
                    <li style="margin-bottom: 12px;">
                        <strong style="color: #28a745;">📧 Alertas al investigador:</strong>
                        <?php _e('Recibes un resumen por email después de cada ejecución de cron que envió emails.', 'eipsi-forms'); ?>
                    </li>
                </ul>
                <div style="padding: 15px; background: #ffffff; border: 1px solid #dee2e6; border-radius: 6px;">
                    <p style="margin: 0; font-size: 13px; color: #495057; line-height: 1.6;">
                        <strong>⚠️ <?php _e('Importante:', 'eipsi-forms'); ?></strong>
                        <?php _e('Los cron jobs de WP-Cron se ejecutan cada hora. Asegúrate de que tu sitio tenga visitas frecuentes para que WP-Cron funcione correctamente, o configura un cron job real en tu servidor.', 'eipsi-forms'); ?>
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>

<style>
/* Toggle Labels Hover Effect */
.eipsi-toggle-label:hover {
    background: #e3f2fd !important;
}

/* Input Groups Focus Effect */
.eipsi-input-group input:focus {
    border-color: #3B6CAA !important;
    outline: none;
    box-shadow: 0 0 0 3px rgba(59, 108, 170, 0.1);
}

/* Button Hover Effect */
#eipsi_save_cron_config:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(59, 108, 170, 0.3) !important;
}

#eipsi_save_cron_config:active {
    transform: translateY(0);
}

/* Section hover effect */
.eipsi-config-section {
    transition: transform 0.2s, box-shadow 0.2s;
}

.eipsi-config-section:hover {
    transform: translateY(-2px);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .eipsi-config-section {
        padding: 20px !important;
    }

    .eipsi-input-group input,
    .eipsi-input-group .button {
        width: 100% !important;
        max-width: none !important;
    }
}
</style>

<script>
(function($) {
    'use strict';

    // Study selector change handler
    const studySelector = document.getElementById('study_selector');
    if (studySelector) {
        studySelector.addEventListener('change', function() {
            const studyId = this.value;
            if (studyId) {
                // Redirect with selected study
                window.location.href = '?page=eipsi-longitudinal-study&tab=reminders&study_id=' + studyId;
            } else {
                // Clear selection
                window.location.href = '?page=eipsi-longitudinal-study&tab=reminders';
            }
        });
    }

    // Save configuration form handler
    const form = document.getElementById('eipsi_cron_reminders_form');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const spinner = document.getElementById('eipsi_cron_spinner');
            const status = document.getElementById('eipsi_cron_status');
            const saveButton = document.getElementById('eipsi_save_cron_config');

            // Validate required fields
            const investigatorEmail = document.getElementById('investigator_alert_email').value;
            const investigatorAlertEnabled = document.getElementById('investigator_alert_enabled').checked;

            if (investigatorAlertEnabled && investigatorEmail && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(investigatorEmail)) {
                status.textContent = '❌ <?php echo esc_js(__('Email del investigador inválido', 'eipsi-forms')); ?>';
                status.style.color = '#dc3545';
                // Shake animation for error
                saveButton.style.animation = 'shake 0.5s';
                setTimeout(() => { saveButton.style.animation = ''; }, 500);
                return;
            }

            // Show loading state
            spinner.style.display = 'inline-block';
            saveButton.disabled = true;
            saveButton.style.opacity = '0.7';
            status.textContent = '<?php echo esc_js(__('Guardando...', 'eipsi-forms')); ?>';
            status.style.color = '#3B6CAA';

            const formData = new FormData(form);
            formData.append('action', 'eipsi_save_cron_reminders_config');
            // Rename nonce field to match what AJAX handler expects
            formData.set('nonce', formData.get('eipsi_admin_nonce'));
            formData.delete('eipsi_admin_nonce');

            fetch(ajaxurl, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                spinner.style.display = 'none';
                saveButton.disabled = false;
                saveButton.style.opacity = '1';

                if (data.success) {
                    status.textContent = '✅ ' + data.data.message;
                    status.style.color = '#28a745';
                    // Success animation
                    saveButton.style.background = '#28a745';
                    setTimeout(() => {
                        saveButton.style.background = '';
                    }, 2000);
                } else {
                    status.textContent = '❌ <?php echo esc_js(__('Error:', 'eipsi-forms')); ?> ' + data.data.message;
                    status.style.color = '#dc3545';
                    // Shake animation for error
                    saveButton.style.animation = 'shake 0.5s';
                    setTimeout(() => { saveButton.style.animation = ''; }, 500);
                }

                setTimeout(() => { status.textContent = ''; }, 5000);
            })
            .catch(err => {
                spinner.style.display = 'none';
                saveButton.disabled = false;
                saveButton.style.opacity = '1';
                status.textContent = '❌ <?php echo esc_js(__('Error de conexión. Inténtalo de nuevo.', 'eipsi-forms')); ?>';
                status.style.color = '#dc3545';
                console.error('AJAX Error:', err);
            });
        });
    }

    // Add shake animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
    `;
    document.head.appendChild(style);

    // Initialize tooltips or additional UI enhancements here
    console.log('EIPSI Reminders Tab Initialized');
})(jQuery);
</script>
