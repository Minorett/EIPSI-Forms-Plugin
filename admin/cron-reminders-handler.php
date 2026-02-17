<?php
/**
 * Cron Reminders Handler
 *
 * Handles cron jobs for sending reminders and recovery emails
 * for longitudinal studies.
 *
 * @package EIPSI_Forms
 * @since 1.4.2
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Send wave reminders - Hourly cron job
 *
 * @since 1.4.2
 */
function eipsi_send_wave_reminders_hourly() {
    error_log('[EIPSI Cron] Starting hourly wave reminders');

    global $wpdb;

    // Get all active studies with reminders enabled
    $studies = $wpdb->get_results(
        "SELECT id, study_name, config FROM {$wpdb->prefix}survey_studies WHERE status = 'active'"
    );

    if (empty($studies)) {
        error_log('[EIPSI Cron] No active studies found');
        return;
    }

    foreach ($studies as $study) {
        $config = json_decode($study->config, true);
        if (!is_array($config) || empty($config['reminders_enabled'])) {
            continue;
        }

        $reminder_days = isset($config['reminder_days_before']) ? intval($config['reminder_days_before']) : 3;
        $max_emails = isset($config['max_reminder_emails']) ? intval($config['max_reminder_emails']) : 100;

        // Get pending assignments that need reminders
        $reminder_date = date('Y-m-d H:i:s', strtotime("+{$reminder_days} days"));
        $emails_sent = 0;

        $pending_assignments = $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, w.name as wave_name, w.wave_index, p.email, p.first_name, p.last_name, p.id as participant_id
             FROM {$wpdb->prefix}survey_assignments a
             JOIN {$wpdb->prefix}survey_waves w ON a.wave_id = w.id
             JOIN {$wpdb->prefix}survey_participants p ON a.participant_id = p.id
             WHERE a.study_id = %d
             AND a.status = 'pending'
             AND w.due_date <= %s
             AND w.due_date > NOW()
             AND p.status = 'active'
             AND p.email IS NOT NULL
             ORDER BY w.due_date ASC
             LIMIT %d",
            $study->id,
            $reminder_date,
            $max_emails
        ));

        if (empty($pending_assignments)) {
            continue;
        }

        // Load email service
        if (!class_exists('EIPSI_Email_Service')) {
            require_once plugin_dir_path(__FILE__) . 'services/class-email-service.php';
        }

        foreach ($pending_assignments as $assignment) {
            // Check rate limiting - max 1 email per participant per wave per 24 hours
            $rate_limit_key = "eipsi_reminder_{$assignment->participant_id}_{$assignment->wave_id}";
            if (get_transient($rate_limit_key)) {
                continue;
            }

            // Send reminder email
            $wave = array(
                'id' => $assignment->wave_id,
                'name' => $assignment->wave_name,
                'wave_index' => $assignment->wave_index,
                'due_date' => $assignment->due_date
            );

            $result = EIPSI_Email_Service::send_wave_reminder_email(
                $study->id,
                $assignment->participant_id,
                (object) $wave
            );

            if ($result) {
                $emails_sent++;
                // Set rate limit - 24 hours
                set_transient($rate_limit_key, true, DAY_IN_SECONDS);
            }
        }

        error_log("[EIPSI Cron] Study {$study->study_name}: {$emails_sent} reminder emails sent");

        // Send investigator alert if enabled
        if (!empty($config['investigator_alert_enabled']) && $emails_sent > 0) {
            $investigator_email = !empty($config['investigator_alert_email']) 
                ? $config['investigator_alert_email'] 
                : get_option('admin_email');

            $subject = "[EIPSI] Resumen de Recordatorios - {$study->study_name}";
            $message = sprintf(
                "Se enviaron %d recordatorios para el estudio '%s'.\n\nFecha: %s",
                $emails_sent,
                $study->study_name,
                date('Y-m-d H:i:s')
            );

            wp_mail($investigator_email, $subject, $message);
        }
    }

    error_log('[EIPSI Cron] Completed hourly wave reminders');
}
add_action('eipsi_send_wave_reminders_hourly', 'eipsi_send_wave_reminders_hourly');

/**
 * Send dropout recovery emails - Hourly cron job
 *
 * @since 1.4.2
 */
function eipsi_send_dropout_recovery_hourly() {
    error_log('[EIPSI Cron] Starting hourly dropout recovery');

    global $wpdb;

    // Get all active studies with dropout recovery enabled
    $studies = $wpdb->get_results(
        "SELECT id, study_name, config FROM {$wpdb->prefix}survey_studies WHERE status = 'active'"
    );

    if (empty($studies)) {
        error_log('[EIPSI Cron] No active studies found');
        return;
    }

    foreach ($studies as $study) {
        $config = json_decode($study->config, true);
        if (!is_array($config) || empty($config['dropout_recovery_enabled'])) {
            continue;
        }

        $dropout_days = isset($config['dropout_recovery_days']) ? intval($config['dropout_recovery_days']) : 7;
        $max_emails = isset($config['max_recovery_emails']) ? intval($config['max_recovery_emails']) : 50;

        // Get overdue assignments (dropouts)
        $overdue_date = date('Y-m-d H:i:s', strtotime("-{$dropout_days} days"));
        $emails_sent = 0;

        $overdue_assignments = $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, w.name as wave_name, w.wave_index, p.email, p.first_name, p.last_name, p.id as participant_id
             FROM {$wpdb->prefix}survey_assignments a
             JOIN {$wpdb->prefix}survey_waves w ON a.wave_id = w.id
             JOIN {$wpdb->prefix}survey_participants p ON a.participant_id = p.id
             WHERE a.study_id = %d
             AND a.status = 'pending'
             AND w.due_date < %s
             AND p.status = 'active'
             AND p.email IS NOT NULL
             AND a.reminder_count < 3
             ORDER BY w.due_date ASC
             LIMIT %d",
            $study->id,
            $overdue_date,
            $max_emails
        ));

        if (empty($overdue_assignments)) {
            continue;
        }

        // Load email service
        if (!class_exists('EIPSI_Email_Service')) {
            require_once plugin_dir_path(__FILE__) . 'services/class-email-service.php';
        }

        foreach ($overdue_assignments as $assignment) {
            // Check rate limiting - max 1 recovery email per participant per week
            $rate_limit_key = "eipsi_recovery_{$assignment->participant_id}_{$assignment->wave_id}";
            if (get_transient($rate_limit_key)) {
                continue;
            }

            // Send recovery email
            $wave = array(
                'id' => $assignment->wave_id,
                'name' => $assignment->wave_name,
                'wave_index' => $assignment->wave_index,
                'due_date' => $assignment->due_date
            );

            $result = EIPSI_Email_Service::send_dropout_recovery_email(
                $study->id,
                $assignment->participant_id,
                (object) $wave
            );

            if ($result) {
                $emails_sent++;
                // Increment reminder count
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$wpdb->prefix}survey_assignments SET reminder_count = reminder_count + 1 WHERE id = %d",
                    $assignment->id
                ));
                // Set rate limit - 7 days
                set_transient($rate_limit_key, true, 7 * DAY_IN_SECONDS);
            }
        }

        error_log("[EIPSI Cron] Study {$study->study_name}: {$emails_sent} recovery emails sent");
    }

    error_log('[EIPSI Cron] Completed hourly dropout recovery');
}
add_action('eipsi_send_dropout_recovery_hourly', 'eipsi_send_dropout_recovery_hourly');

/**
 * Legacy: Send daily reminders
 *
 * @since 1.0.0
 * @deprecated 1.4.2 Use eipsi_send_wave_reminders_hourly instead
 */
function eipsi_send_take_reminders_daily() {
    error_log('[EIPSI Cron] Legacy daily reminders triggered (deprecated)');
}
add_action('eipsi_send_take_reminders_daily', 'eipsi_send_take_reminders_daily');

/**
 * Legacy: Send weekly reminders
 *
 * @since 1.0.0
 * @deprecated 1.4.2 Use eipsi_send_wave_reminders_hourly instead
 */
function eipsi_send_take_reminders_weekly() {
    error_log('[EIPSI Cron] Legacy weekly reminders triggered (deprecated)');
}
add_action('eipsi_send_take_reminders_weekly', 'eipsi_send_take_reminders_weekly');

/**
 * Handle unsubscribe requests
 *
 * @since 1.4.2
 */
function eipsi_unsubscribe_reminders_handler() {
    if (!isset($_GET['eipsi_unsubscribe']) || $_GET['eipsi_unsubscribe'] !== '1') {
        return;
    }

    $participant_id = isset($_GET['participant_id']) ? sanitize_text_field($_GET['participant_id']) : '';
    $email = isset($_GET['email']) ? sanitize_email($_GET['email']) : '';

    if (empty($participant_id) && empty($email)) {
        wp_die(__('Invalid unsubscribe request.', 'eipsi-forms'));
    }

    global $wpdb;

    // Update participant status to unsubscribed
    if (!empty($participant_id)) {
        $wpdb->update(
            $wpdb->prefix . 'survey_participants',
            array('status' => 'unsubscribed'),
            array('participant_id' => $participant_id)
        );
    } elseif (!empty($email)) {
        $wpdb->update(
            $wpdb->prefix . 'survey_participants',
            array('status' => 'unsubscribed'),
            array('email' => $email)
        );
    }

    // Show success message
    wp_die(
        '<h2>' . __('Unsubscribed Successfully', 'eipsi-forms') . '</h2>' .
        '<p>' . __('You will no longer receive reminder emails from this study.', 'eipsi-forms') . '</p>' .
        '<p><a href="' . home_url() . '">' . __('Return to Homepage', 'eipsi-forms') . '</a></p>',
        __('Unsubscribed', 'eipsi-forms'),
        array('response' => 200)
    );
}

/**
 * AJAX handler to save cron reminders configuration
 *
 * @since 1.4.2
 */
add_action('wp_ajax_eipsi_save_cron_reminders_config', 'eipsi_ajax_save_cron_reminders_config');

function eipsi_ajax_save_cron_reminders_config() {
    check_ajax_referer('eipsi_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Unauthorized', 'eipsi-forms')));
    }

    $study_id = isset($_POST['study_id']) ? intval($_POST['study_id']) : 0;
    if (!$study_id) {
        wp_send_json_error(array('message' => __('Missing study ID', 'eipsi-forms')));
    }

    // Validate and sanitize inputs
    $config = array(
        'reminders_enabled' => isset($_POST['reminders_enabled']),
        'reminder_days_before' => isset($_POST['reminder_days_before']) ? max(1, min(30, intval($_POST['reminder_days_before']))) : 3,
        'max_reminder_emails' => isset($_POST['max_reminder_emails']) ? max(1, min(500, intval($_POST['max_reminder_emails']))) : 100,
        'dropout_recovery_enabled' => isset($_POST['dropout_recovery_enabled']),
        'dropout_recovery_days' => isset($_POST['dropout_recovery_days']) ? max(1, min(90, intval($_POST['dropout_recovery_days']))) : 7,
        'max_recovery_emails' => isset($_POST['max_recovery_emails']) ? max(1, min(500, intval($_POST['max_recovery_emails']))) : 50,
        'investigator_alert_enabled' => isset($_POST['investigator_alert_enabled']),
        'investigator_alert_email' => isset($_POST['investigator_alert_email']) ? sanitize_email($_POST['investigator_alert_email']) : get_option('admin_email'),
    );

    // Get existing config
    global $wpdb;
    $existing_config = $wpdb->get_var($wpdb->prepare(
        "SELECT config FROM {$wpdb->prefix}survey_studies WHERE id = %d",
        $study_id
    ));

    $existing_data = array();
    if ($existing_config) {
        $existing_data = json_decode($existing_config, true);
        if (!is_array($existing_data)) {
            $existing_data = array();
        }
    }

    // Merge configs
    $merged_config = array_merge($existing_data, $config);

    // Update database
    $result = $wpdb->update(
        $wpdb->prefix . 'survey_studies',
        array('config' => wp_json_encode($merged_config)),
        array('id' => $study_id),
        array('%s'),
        array('%d')
    );

    if ($result === false) {
        wp_send_json_error(array('message' => __('Database error: ', 'eipsi-forms') . $wpdb->last_error));
    }

    wp_send_json_success(array('message' => __('Configuration saved successfully', 'eipsi-forms')));
}
