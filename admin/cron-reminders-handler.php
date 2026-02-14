<?php
/**
 * AJAX Handler: Save Cron Reminders Configuration (FIXED)
 *
 * Saves cron reminders configuration to wp_survey_studies table
 *
 * @since 1.5.3
 */

add_action('wp_ajax_eipsi_save_cron_reminders_config', 'eipsi_ajax_save_cron_reminders_config_v2');

function eipsi_ajax_save_cron_reminders_config_v2() {
    check_ajax_referer('eipsi_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Permisos insuficientes', 'eipsi-forms'));
    }

    global $wpdb;

    // Get study ID (changed from survey_id to study_id)
    $study_id = isset($_POST['study_id']) ? intval($_POST['study_id']) : 0;

    if (!$study_id) {
        wp_send_json_error(__('ID de estudio inválido', 'eipsi-forms'));
    }

    // Verify study exists in wp_survey_studies table
    $study = $wpdb->get_row($wpdb->prepare(
        "SELECT id, study_name, config FROM {$wpdb->prefix}survey_studies WHERE id = %d",
        $study_id
    ));

    if (!$study) {
        wp_send_json_error(__('Estudio no encontrado', 'eipsi-forms'));
    }

    // Get and sanitize configuration
    $reminders_enabled = isset($_POST['reminders_enabled']) ? (bool) $_POST['reminders_enabled'] : false;
    $reminder_days_before = isset($_POST['reminder_days_before']) ? intval($_POST['reminder_days_before']) : 3;
    $max_reminder_emails = isset($_POST['max_reminder_emails']) ? intval($_POST['max_reminder_emails']) : 100;
    $dropout_recovery_enabled = isset($_POST['dropout_recovery_enabled']) ? (bool) $_POST['dropout_recovery_enabled'] : false;
    $dropout_recovery_days = isset($_POST['dropout_recovery_days']) ? intval($_POST['dropout_recovery_days']) : 7;
    $max_recovery_emails = isset($_POST['max_recovery_emails']) ? intval($_POST['max_recovery_emails']) : 50;
    $investigator_alert_enabled = isset($_POST['investigator_alert_enabled']) ? (bool) $_POST['investigator_alert_enabled'] : false;
    $investigator_alert_email = isset($_POST['investigator_alert_email']) ? sanitize_email($_POST['investigator_alert_email']) : '';

    // Validate values
    if ($reminder_days_before < 1 || $reminder_days_before > 30) {
        wp_send_json_error(__('Días de recordatorio deben estar entre 1 y 30', 'eipsi-forms'));
    }

    if ($max_reminder_emails < 1 || $max_reminder_emails > 500) {
        wp_send_json_error(__('Máximo de emails debe estar entre 1 y 500', 'eipsi-forms'));
    }

    if ($dropout_recovery_days < 1 || $dropout_recovery_days > 90) {
        wp_send_json_error(__('Días de recovery deben estar entre 1 y 90', 'eipsi-forms'));
    }

    if ($max_recovery_emails < 1 || $max_recovery_emails > 500) {
        wp_send_json_error(__('Máximo de emails recovery debe estar entre 1 y 500', 'eipsi-forms'));
    }

    if ($investigator_alert_enabled && !is_email($investigator_alert_email)) {
        wp_send_json_error(__('Email del investigador inválido', 'eipsi-forms'));
    }

    // Get existing config
    $existing_config = json_decode($study->config, true);
    if (!is_array($existing_config)) {
        $existing_config = array();
    }

    // Update cron reminders configuration
    $existing_config['reminders_enabled'] = $reminders_enabled;
    $existing_config['reminder_days_before'] = $reminder_days_before;
    $existing_config['max_reminder_emails'] = $max_reminder_emails;
    $existing_config['dropout_recovery_enabled'] = $dropout_recovery_enabled;
    $existing_config['dropout_recovery_days'] = $dropout_recovery_days;
    $existing_config['max_recovery_emails'] = $max_recovery_emails;
    $existing_config['investigator_alert_enabled'] = $investigator_alert_enabled;
    $existing_config['investigator_alert_email'] = $investigator_alert_email;

    // Save to config JSON
    $updated = $wpdb->update(
        $wpdb->prefix . 'survey_studies',
        array(
            'config' => json_encode($existing_config),
            'updated_at' => current_time('mysql')
        ),
        array('id' => $study_id),
        array('%s', '%s'),
        array('%d')
    );

    if ($updated === false) {
        wp_send_json_error(__('Error al guardar la configuración', 'eipsi-forms'));
    }

    // Log
    error_log(sprintf(
        '[EIPSI Forms] Cron reminders config saved for study %d: reminders=%s, recovery=%s',
        $study_id,
        $reminders_enabled ? 'enabled' : 'disabled',
        $dropout_recovery_enabled ? 'enabled' : 'disabled'
    ));

    wp_send_json_success(__('Configuración guardada correctamente', 'eipsi-forms'));
}
