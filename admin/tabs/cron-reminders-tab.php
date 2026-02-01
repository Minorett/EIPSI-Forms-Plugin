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

// Get available surveys
$surveys = get_posts(array(
    'post_type' => 'eipsi_form',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC',
));

// Get selected survey from URL
$selected_survey_id = isset($_GET['survey_id']) ? intval($_GET['survey_id']) : 0;

// Get configuration for selected survey
$config = array();
if ($selected_survey_id) {
    $config = array(
        'reminders_enabled' => get_post_meta($selected_survey_id, '_eipsi_reminders_enabled', true),
        'reminder_days_before' => get_post_meta($selected_survey_id, '_eipsi_reminder_days_before', true),
        'max_reminder_emails' => get_post_meta($selected_survey_id, '_eipsi_max_reminder_emails_per_run', true),
        'dropout_recovery_enabled' => get_post_meta($selected_survey_id, '_eipsi_dropout_recovery_enabled', true),
        'dropout_recovery_days' => get_post_meta($selected_survey_id, '_eipsi_dropout_recovery_days_overdue', true),
        'max_recovery_emails' => get_post_meta($selected_survey_id, '_eipsi_max_recovery_emails_per_run', true),
        'investigator_alert_enabled' => get_post_meta($selected_survey_id, '_eipsi_investigator_alert_enabled', true),
        'investigator_alert_email' => get_post_meta($selected_survey_id, '_eipsi_investigator_alert_email', true),
    );
}
?>

<div class="eipsi-cron-reminders-tab">

    <!-- Info Box -->
    <div class="notice notice-info inline" style="margin: 0 0 20px 0;">
        <p>
            <strong><?php _e('Configuración de Recordatorios Automáticos', 'eipsi-forms'); ?></strong><br>
            <?php _e('Configura el envío automático de recordatorios para waves pendientes y recuperación de participantes inactivos. Los cron jobs se ejecutan cada hora.', 'eipsi-forms'); ?>
        </p>
    </div>

    <!-- Survey Selector -->
    <?php if (empty($surveys)): ?>
        <div class="notice notice-warning inline">
            <p><?php _e('No hay estudios (surveys) disponibles. Primero crea un estudio longitudinal.', 'eipsi-forms'); ?></p>
        </div>
    <?php else: ?>
        <div style="margin: 20px 0;">
            <label for="survey_selector" style="display: block; margin-bottom: 8px; font-weight: 600;">
                <?php _e('Seleccionar Estudio', 'eipsi-forms'); ?>
            </label>
            <select id="survey_selector" style="width: 100%; max-width: 600px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                <option value=""><?php _e('-- Seleccionar --', 'eipsi-forms'); ?></option>
                <?php foreach ($surveys as $survey): ?>
                    <option value="<?php echo esc_attr($survey->ID); ?>" <?php selected($selected_survey_id, $survey->ID); ?>>
                        <?php echo esc_html($survey->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Configuration Form (hidden until survey is selected) -->
        <div id="cron_config_form_wrapper" style="<?php echo $selected_survey_id ? '' : 'display: none;'; ?>">
            <form id="eipsi_cron_reminders_form" method="post">
                <?php wp_nonce_field('eipsi_admin_nonce', 'eipsi_admin_nonce'); ?>
                <input type="hidden" id="selected_survey_id" name="survey_id" value="<?php echo esc_attr($selected_survey_id); ?>">

                <!-- Section: Wave Reminders -->
                <div style="margin: 30px 0; padding: 20px; background: #f8f9fa; border: 1px solid #ddd; border-radius: 6px;">
                    <h3 style="margin-top: 0; color: #3B6CAA;">
                        ⏰ <?php _e('Recordatorios de Waves Pendientes', 'eipsi-forms'); ?>
                    </h3>

                    <label style="display: block; margin-bottom: 12px;">
                        <input type="checkbox" id="reminders_enabled" name="reminders_enabled" <?php checked(!empty($config['reminders_enabled'])); ?>>
                        <strong><?php _e('Enviar recordatorios automáticos de waves pendientes', 'eipsi-forms'); ?></strong>
                        <span style="color: #666; font-size: 0.9em; margin-left: 8px;">
                            (<?php _e('Ejecución: cada hora', 'eipsi-forms'); ?>)
                        </span>
                    </label>

                    <div style="margin: 15px 0; padding: 15px; background: white; border: 1px solid #e0e0e0; border-radius: 4px;">
                        <label for="reminder_days_before" style="display: block; margin-bottom: 8px; font-weight: 600;">
                            <?php _e('Días antes de vencimiento para enviar recordatorios', 'eipsi-forms'); ?>
                        </label>
                        <input type="number"
                               id="reminder_days_before"
                               name="reminder_days_before"
                               value="<?php echo esc_attr(intval($config['reminder_days_before']) ?: 3); ?>"
                               min="1"
                               max="30"
                               style="width: 100px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <span style="color: #666; font-size: 0.9em; margin-left: 8px;">
                            (<?php _e('Default: 3 días', 'eipsi-forms'); ?>)
                        </span>
                    </div>

                    <div style="margin: 15px 0; padding: 15px; background: white; border: 1px solid #e0e0e0; border-radius: 4px;">
                        <label for="max_reminder_emails" style="display: block; margin-bottom: 8px; font-weight: 600;">
                            <?php _e('Máximo de emails de recordatorio por ejecución cron', 'eipsi-forms'); ?>
                        </label>
                        <input type="number"
                               id="max_reminder_emails"
                               name="max_reminder_emails"
                               value="<?php echo esc_attr(intval($config['max_reminder_emails']) ?: 100); ?>"
                               min="1"
                               max="500"
                               style="width: 100px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <span style="color: #666; font-size: 0.9em; margin-left: 8px;">
                            (<?php _e('Default: 100 emails', 'eipsi-forms'); ?>)
                        </span>
                    </div>
                </div>

                <!-- Section: Dropout Recovery -->
                <div style="margin: 30px 0; padding: 20px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 6px;">
                    <h3 style="margin-top: 0; color: #856404;">
                        💔 <?php _e('Recuperación de Participantes Inactivos (Dropouts)', 'eipsi-forms'); ?>
                    </h3>

                    <label style="display: block; margin-bottom: 12px;">
                        <input type="checkbox" id="dropout_recovery_enabled" name="dropout_recovery_enabled" <?php checked(!empty($config['dropout_recovery_enabled'])); ?>>
                        <strong><?php _e('Activar recuperación de participantes inactivos', 'eipsi-forms'); ?></strong>
                        <span style="color: #666; font-size: 0.9em; margin-left: 8px;">
                            (<?php _e('Mensaje: "Te extrañamos"', 'eipsi-forms'); ?>)
                        </span>
                    </label>

                    <div style="margin: 15px 0; padding: 15px; background: white; border: 1px solid #e0e0e0; border-radius: 4px;">
                        <label for="dropout_recovery_days" style="display: block; margin-bottom: 8px; font-weight: 600;">
                            <?php _e('Días después de vencimiento para considerar dropout', 'eipsi-forms'); ?>
                        </label>
                        <input type="number"
                               id="dropout_recovery_days"
                               name="dropout_recovery_days"
                               value="<?php echo esc_attr(intval($config['dropout_recovery_days']) ?: 7); ?>"
                               min="1"
                               max="90"
                               style="width: 100px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <span style="color: #666; font-size: 0.9em; margin-left: 8px;">
                            (<?php _e('Default: 7 días', 'eipsi-forms'); ?>)
                        </span>
                    </div>

                    <div style="margin: 15px 0; padding: 15px; background: white; border: 1px solid #e0e0e0; border-radius: 4px;">
                        <label for="max_recovery_emails" style="display: block; margin-bottom: 8px; font-weight: 600;">
                            <?php _e('Máximo de emails de recuperación por ejecución cron', 'eipsi-forms'); ?>
                        </label>
                        <input type="number"
                               id="max_recovery_emails"
                               name="max_recovery_emails"
                               value="<?php echo esc_attr(intval($config['max_recovery_emails']) ?: 50); ?>"
                               min="1"
                               max="500"
                               style="width: 100px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <span style="color: #666; font-size: 0.9em; margin-left: 8px;">
                            (<?php _e('Default: 50 emails', 'eipsi-forms'); ?>)
                        </span>
                    </div>
                </div>

                <!-- Section: Investigator Alerts -->
                <div style="margin: 30px 0; padding: 20px; background: #d4edda; border: 1px solid #28a745; border-radius: 6px;">
                    <h3 style="margin-top: 0; color: #155724;">
                        📧 <?php _e('Alertas al Investigador', 'eipsi-forms'); ?>
                    </h3>

                    <label style="display: block; margin-bottom: 12px;">
                        <input type="checkbox" id="investigator_alert_enabled" name="investigator_alert_enabled" <?php checked(!empty($config['investigator_alert_enabled'])); ?>>
                        <strong><?php _e('Alertar al investigador sobre actividad de cron', 'eipsi-forms'); ?></strong>
                        <span style="color: #666; font-size: 0.9em; margin-left: 8px;">
                            (<?php _e('Resumen enviado después de cada ejecución', 'eipsi-forms'); ?>)
                        </span>
                    </label>

                    <div style="margin: 15px 0; padding: 15px; background: white; border: 1px solid #e0e0e0; border-radius: 4px;">
                        <label for="investigator_alert_email" style="display: block; margin-bottom: 8px; font-weight: 600;">
                            <?php _e('Email del investigador para alertas', 'eipsi-forms'); ?>
                        </label>
                        <input type="email"
                               id="investigator_alert_email"
                               name="investigator_alert_email"
                               value="<?php echo esc_attr($config['investigator_alert_email'] ?: get_option('admin_email')); ?>"
                               placeholder="investigador@ejemplo.com"
                               style="width: 100%; max-width: 400px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <small style="display: block; margin-top: 6px; color: #666;">
                            <?php _e('Email donde recibirás los resúmenes de actividad', 'eipsi-forms'); ?>
                        </small>
                    </div>
                </div>

                <!-- Save Button -->
                <button type="submit" class="button button-primary" id="eipsi_save_cron_config">
                    <?php _e('💾 Guardar Configuración', 'eipsi-forms'); ?>
                </button>
                <span id="eipsi_cron_spinner" class="spinner" style="display: none; margin-left: 10px;"></span>
                <span id="eipsi_cron_status" style="margin-left: 10px; font-weight: 600;"></span>
            </form>

            <!-- Info Box -->
            <div style="margin: 30px 0; padding: 20px; background: #e2e3e5; border-left: 4px solid #6c757d; border-radius: 6px;">
                <h3 style="margin-top: 0;"><?php _e('💡 Cómo funciona', 'eipsi-forms'); ?></h3>
                <ul style="margin: 10px 0;">
                    <li><?php _e('<strong>Recordatorios de waves:</strong> Se envían automáticamente a participantes con waves pendientes cuando faltan X días para el vencimiento.', 'eipsi-forms'); ?></li>
                    <li><?php _e('<strong>Recuperación de dropouts:</strong> Se envía un mensaje "Te extrañamos" a participantes que no han completado waves vencidas después de X días.', 'eipsi-forms'); ?></li>
                    <li><?php _e('<strong>Rate limiting:</strong> Cada participante recibe máximo 1 email por cada wave en 24 horas (usando transients).', 'eipsi-forms'); ?></li>
                    <li><?php _e('<strong>Max emails por ejecución:</strong> Limita la carga del servidor evitando enviar demasiados emails en una sola ejecución.', 'eipsi-forms'); ?></li>
                    <li><?php _e('<strong>Alertas al investigador:</strong> Recibes un resumen por email después de cada ejecución de cron que envió emails.', 'eipsi-forms'); ?></li>
                </ul>
                <p style="margin-top: 15px;">
                    <small><?php _e('Nota: Los cron jobs de WP-Cron se ejecutan cada hora. Asegúrate de que tu sitio tenga visitas frecuentes para que WP-Cron funcione correctamente.', 'eipsi-forms'); ?></small>
                </p>
            </div>
        </div>
    <?php endif; ?>

</div>

<script>
// Survey selector change handler
document.getElementById('survey_selector').addEventListener('change', function() {
    const surveyId = this.value;
    if (surveyId) {
        // Redirect with selected survey
        window.location.href = '?page=eipsi-results&tab=cron-reminders&survey_id=' + surveyId;
    } else {
        // Clear selection
        window.location.href = '?page=eipsi-results&tab=cron-reminders';
    }
});

// Save configuration form handler
document.getElementById('eipsi_cron_reminders_form').addEventListener('submit', function(e) {
    e.preventDefault();

    const form = this;
    const spinner = document.getElementById('eipsi_cron_spinner');
    const status = document.getElementById('eipsi_cron_status');

    // Validate required fields
    const investigatorEmail = document.getElementById('investigator_alert_email').value;
    const investigatorAlertEnabled = document.getElementById('investigator_alert_enabled').checked;

    if (investigatorAlertEnabled && investigatorEmail && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(investigatorEmail)) {
        status.textContent = '❌ <?php _e('Email del investigador inválido', 'eipsi-forms'); ?>';
        status.style.color = '#dc3545';
        return;
    }

    spinner.style.display = 'inline-block';
    status.textContent = '';
    status.style.color = '';

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

        if (data.success) {
            status.textContent = '✅ ' + data.data.message;
            status.style.color = '#28a745';
        } else {
            status.textContent = '❌ <?php _e('Error:', 'eipsi-forms'); ?> ' + data.data.message;
            status.style.color = '#dc3545';
        }

        setTimeout(() => { status.textContent = ''; }, 5000);
    })
    .catch(err => {
        spinner.style.display = 'none';
        status.textContent = '❌ <?php _e('Error de conexión. Inténtalo de nuevo.', 'eipsi-forms'); ?>';
        status.style.color = '#dc3545';
        console.error('AJAX Error:', err);
    });
});
</script>
