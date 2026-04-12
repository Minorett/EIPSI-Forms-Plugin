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

// v2.2.0 - Load new robust wave availability email service
require_once plugin_dir_path(__FILE__) . 'services/class-wave-availability-email-service.php';

/**
 * Send wave reminders - Hourly cron job
 *
 * @since 1.4.2
 */
function eipsi_send_wave_reminders_hourly($specific_study_id = null) {
    error_log('[EIPSI Cron] Starting hourly wave reminders' . ($specific_study_id ? " for study {$specific_study_id}" : ' for all studies'));

    global $wpdb;

    // Get studies to process
    if ($specific_study_id) {
        // Process only specific study (from manual cron)
        $studies = $wpdb->get_results($wpdb->prepare(
            "SELECT id, study_name, config FROM {$wpdb->prefix}survey_studies WHERE id = %d AND status = 'active'",
            $specific_study_id
        ));
    } else {
        // Get all active studies with reminders enabled (automated cron)
        $studies = $wpdb->get_results(
            "SELECT id, study_name, config FROM {$wpdb->prefix}survey_studies WHERE status = 'active'"
        );
    }

    if (empty($studies)) {
        error_log('[EIPSI Cron] No active studies found');
        return array(
            'processed_studies' => 0,
            'total_emails_sent' => 0
        );
    }

    $total_emails_sent = 0;

    foreach ($studies as $study) {
        // Guard against null config (json_decode(null) is deprecated in PHP 8.1+)
        $config = !empty($study->config) ? json_decode($study->config, true) : array();
        if (!is_array($config)) {
            $config = array();
        }

        // Nudge 0 (available) always sends regardless of reminders_enabled setting
        // Nudges 1-4 depend on reminders_enabled configuration
        $reminders_enabled = !empty($config['reminders_enabled']);

        $today = current_time('Y-m-d');
        $max_emails = isset($config['max_reminder_emails']) ? intval($config['max_reminder_emails']) : 100;

        // Get pending assignments that need reminders
        // New logic: available_date = (last_submission_date OR participant_created_at) + interval (days/minutes)
        $emails_sent = 0;

        // FIXED: Get pending assignments where the wave is now available
        // Available date = last submission date + interval (with correct time_unit)
        $now = current_time('Y-m-d H:i:s');
        $now_date = current_time('Y-m-d');

        error_log("[EIPSI Cron] Processing study {$study->id} - Now: {$now}");

        // DEBUG: First check all pending assignments for this study
        $all_pending = $wpdb->get_results($wpdb->prepare(
            "SELECT a.id, a.wave_id, a.participant_id, a.status, a.reminder_count,
                    w.time_unit, w.interval_days,
                    p.created_at as participant_created
             FROM {$wpdb->prefix}survey_assignments a
             JOIN {$wpdb->prefix}survey_waves w ON a.wave_id = w.id
             JOIN {$wpdb->prefix}survey_participants p ON a.participant_id = p.id
             WHERE a.study_id = %d
             AND a.status = 'pending'
             AND p.is_active = 1",
            $study->id
        ));
        error_log("[EIPSI Cron] Total pending assignments: " . count($all_pending));
        foreach ($all_pending as $pend) {
            error_log("[EIPSI Cron] Pending: assignment_id={$pend->id}, wave_id={$pend->wave_id}, time_unit={$pend->time_unit}, interval={$pend->interval_days}, reminder_count={$pend->reminder_count}");
        }

        // v2.2.0: Get pending assignments for follow-up nudges (stages 1-4)
        // This cron NOW ALSO handles Nudge 0 when wave becomes available after interval
        // Nudge 0: reminder_count = 0 AND wave is now available (based on interval)
        // Nudges 1-4: reminder_count >= 1 AND < 5

        // FIRST: Handle Nudge 0 - Initial availability emails
        // v2.3.0: Nudge 0 is ALWAYS enabled (immediate availability email)
        // Get pending assignments where reminder_count = 0 AND wave is now available
        $nudge_zero_assignments = $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, w.name as wave_name, w.wave_index, w.due_date, w.follow_up_reminders_enabled,
             w.time_unit, w.interval_days, w.nudge_config,
             p.email, p.first_name, p.last_name, p.id as participant_id,
             (SELECT submitted_at FROM {$wpdb->prefix}survey_assignments a2
              WHERE a2.participant_id = a.participant_id AND a2.wave_id = w.id - 1 AND a2.status = 'submitted'
              ORDER BY a2.submitted_at DESC LIMIT 1) as last_submission_date
             FROM {$wpdb->prefix}survey_assignments a
             JOIN {$wpdb->prefix}survey_waves w ON a.wave_id = w.id
             JOIN {$wpdb->prefix}survey_participants p ON a.participant_id = p.id
             WHERE a.study_id = %d
             AND a.status = 'pending'
             AND p.is_active = 1
             AND p.email IS NOT NULL
             AND a.reminder_count = 0
             HAVING last_submission_date IS NOT NULL
             ORDER BY a.id DESC
             LIMIT %d",
            $study->id,
            $max_emails
        ));

        // Filter Nudge 0 assignments to only those where wave is NOW available
        error_log("[EIPSI Cron] NUDGE 0 DIAGNOSTIC: Found " . count($nudge_zero_assignments) . " pending assignments with reminder_count=0");
        $available_now_assignments = array();
        $now = current_time('timestamp');
        error_log("[EIPSI Cron] NUDGE 0 FILTER: Current time (timestamp)={$now}, formatted=" . date('Y-m-d H:i:s', $now));
        foreach ($nudge_zero_assignments as $assignment) {
            error_log("[EIPSI Cron] NUDGE 0 CHECK: assignment_id={$assignment->id}, participant_id={$assignment->participant_id}, wave_id={$assignment->wave_id}, wave_name={$assignment->wave_name}, last_submission_date={$assignment->last_submission_date}, interval_days={$assignment->interval_days}, time_unit={$assignment->time_unit}");
            if (!empty($assignment->last_submission_date)) {
                // time_unit: 0 = minutes, 1 = days (from database)
                $time_unit_str = (intval($assignment->time_unit) === 0) ? 'minutes' : 'days';
                $available_at = strtotime("+{$assignment->interval_days} {$time_unit_str}", strtotime($assignment->last_submission_date));
                error_log("[EIPSI Cron] NUDGE 0 CALC: assignment_id={$assignment->id}, available_at_timestamp={$available_at}, available_at_formatted=" . ($available_at ? date('Y-m-d H:i:s', $available_at) : 'INVALID') . ", now={$now}, condition_met=" . ($available_at && $now >= $available_at ? 'YES' : 'NO'));
                if ($available_at && $now >= $available_at) {
                    $available_now_assignments[] = $assignment;
                    error_log("[EIPSI Cron] Nudge 0 READY: participant={$assignment->participant_id}, wave={$assignment->wave_name}, available_at=" . date('Y-m-d H:i:s', $available_at));
                } else {
                    error_log("[EIPSI Cron] NUDGE 0 BLOCKED: assignment_id={$assignment->id}, reason=NOT_YET_AVAILABLE, available_at=" . ($available_at ? date('Y-m-d H:i:s', $available_at) : 'N/A') . ", now=" . date('Y-m-d H:i:s', $now));
                }
            } else {
                error_log("[EIPSI Cron] NUDGE 0 BLOCKED: assignment_id={$assignment->id}, reason=NO_LAST_SUBMISSION_DATE");
            }
        }

        error_log("[EIPSI Cron] Nudge 0 assignments ready for email: " . count($available_now_assignments));

        // SECOND: Handle Nudges 1-4 - Follow-up reminders
        $pending_assignments = $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, w.name as wave_name, w.wave_index, w.due_date, w.follow_up_reminders_enabled,
             p.email, p.first_name, p.last_name, p.id as participant_id,
             (SELECT submitted_at FROM {$wpdb->prefix}survey_assignments a2 
              WHERE a2.participant_id = a.participant_id AND a2.wave_id = w.id - 1 AND a2.status = 'submitted' 
              ORDER BY a2.submitted_at DESC LIMIT 1) as last_submission_date
             FROM {$wpdb->prefix}survey_assignments a
             JOIN {$wpdb->prefix}survey_waves w ON a.wave_id = w.id
             JOIN {$wpdb->prefix}survey_participants p ON a.participant_id = p.id
             WHERE a.study_id = %d
             AND a.status = 'pending'
             AND p.is_active = 1
             AND p.email IS NOT NULL
             AND a.reminder_count >= 1
             AND a.reminder_count < 5
             AND w.follow_up_reminders_enabled = 1
             ORDER BY a.id DESC
             LIMIT %d",
            $study->id,
            $max_emails
        ));

        error_log("[EIPSI Cron] Assignments ready for email: " . count($pending_assignments));

        // Combine Nudge 0 (initial availability) with Nudges 1-4 (follow-up reminders)
        $all_assignments_to_process = array_merge($available_now_assignments, $pending_assignments);

        if (empty($all_assignments_to_process)) {
            error_log("[EIPSI Cron] No pending assignments ready for email");
            continue;
        }

        // Load required services
        if (!class_exists('EIPSI_Email_Service')) {
            require_once plugin_dir_path(__FILE__) . 'services/class-email-service.php';
        }
        if (!class_exists('EIPSI_Assignment_Service')) {
            require_once plugin_dir_path(__FILE__) . 'services/class-assignment-service.php';
        }
        if (!class_exists('EIPSI_Nudge_Service')) {
            require_once plugin_dir_path(__FILE__) . '../includes/services/class-nudge-service.php';
        }

        foreach ($all_assignments_to_process as $assignment) {
            $current_stage = (int) $assignment->reminder_count;
            $has_due_date = !empty($assignment->due_date);
            
            error_log("[EIPSI Cron] Processing assignment {$assignment->id}, participant {$assignment->participant_id}, wave {$assignment->wave_id}, stage {$current_stage}");
            
            // v2.3.0 - Load nudge config from wave's nudge_config JSON column
            $custom_config = null;
            if (!empty($assignment->nudge_config)) {
                $custom_config = json_decode($assignment->nudge_config, true);
            }
            
            // Check if stage is enabled in custom config
            $nudge_key = "nudge_{$current_stage}";
            if ($custom_config && isset($custom_config[$nudge_key])) {
                if (empty($custom_config[$nudge_key]['enabled'])) {
                    error_log("[EIPSI Cron] SKIPPED: Nudge {$current_stage} disabled in wave config for participant {$assignment->participant_id}");
                    continue;
                }
            }
            
            // Check if we should send this nudge now
            $should_send = EIPSI_Nudge_Service::should_send_nudge($assignment, (object) $assignment, $current_stage, $custom_config);
            error_log("[EIPSI Cron] should_send_nudge result for stage {$current_stage}: " . ($should_send ? 'YES' : 'NO'));
            if (!$should_send) {
                error_log("[EIPSI Cron] SKIPPED: Nudge {$current_stage} not yet due for participant {$assignment->participant_id}");
                continue;
            }
            
            // v2.2.0 - For Nudge 0, use robust Wave Availability Email Service
            if ($current_stage === 0) {
                error_log("[EIPSI Cron] Using EIPSI_Wave_Availability_Email_Service for Nudge 0");
                
                $result = EIPSI_Wave_Availability_Email_Service::ensure_wave_availability_email_sent(
                    $assignment,
                    (object) $assignment,
                    (object) $assignment,
                    $study->id
                );
                
                error_log("[EIPSI Cron] Wave Availability Email Service result: " . wp_json_encode($result));
                
                if ($result['success'] && $result['sent']) {
                    $total_emails_sent++;
                    // Set rate limit to prevent duplicate in next cron run
                    $rate_limit_key = "eipsi_reminder_{$assignment->participant_id}_{$assignment->wave_id}_0";
                    set_transient($rate_limit_key, true, 24 * HOUR_IN_SECONDS);
                    // v2.2.1 - Delay SMTP para evitar rate limiting (2 segundos entre emails)
                    sleep(2);
                } elseif ($result['reason'] === 'max_retries_reached') {
                    // Increment reminder_count to stop trying
                    EIPSI_Assignment_Service::increment_reminder_count($assignment->id);
                    error_log("[EIPSI Cron] Max retries reached, marking as attempted");
                } else {
                    // Log why Nudge 0 was not sent
                    $reason = isset($result['reason']) ? $result['reason'] : 'unknown';
                    error_log("[EIPSI Cron] Nudge 0 NOT SENT for participant={$assignment->participant_id}, wave={$assignment->wave_id}, reason={$reason}");
                }
                
                continue; // Skip old logic for Nudge 0
            }

            // Nudges 1-4 only send if reminders_enabled is set
            if (!$reminders_enabled) {
                error_log("[EIPSI Cron] SKIPPED: Nudge {$current_stage} - reminders disabled for study {$study->id}");
                continue;
            }
            
            // Check rate limiting - max 1 email per participant per wave per 24 hours
            $rate_limit_key = "eipsi_reminder_{$assignment->participant_id}_{$assignment->wave_id}_{$current_stage}";
            $is_rate_limited = get_transient($rate_limit_key);
            error_log("[EIPSI Cron] Rate limit check: key={$rate_limit_key}, limited=" . ($is_rate_limited ? 'YES' : 'NO'));
            if ($is_rate_limited) {
                error_log("[EIPSI Cron] SKIPPED: Rate limited for participant {$assignment->participant_id}, wave {$assignment->wave_id}, nudge {$current_stage}");
                continue;
            }

            // Get nudge config
            $nudge_config = EIPSI_Nudge_Service::get_nudge_config($current_stage, $has_due_date);
            if (!$nudge_config) {
                error_log("[EIPSI Cron] ERROR: Invalid nudge stage {$current_stage}");
                continue;
            }

            // Send reminder email
            $wave = array(
                'id' => $assignment->wave_id,
                'name' => $assignment->wave_name,
                'wave_index' => $assignment->wave_index,
                'due_date' => $assignment->due_date
            );

            error_log("[EIPSI Cron] Sending Nudge {$current_stage} ({$nudge_config['label']}) to participant {$assignment->participant_id} ({$assignment->email}) for wave {$assignment->wave_name}");

            $result = EIPSI_Email_Service::send_wave_reminder_email(
                $study->id,
                $assignment->participant_id,
                (object) $wave,
                $current_stage // Pass stage for template selection
            );

            error_log("[EIPSI Cron] Email send result: " . ($result ? 'SUCCESS' : 'FAILED'));

            if ($result) {
                $emails_sent++;
                // Increment reminder count (stage) for next nudge
                EIPSI_Assignment_Service::increment_reminder_count($assignment->wave_id, $assignment->participant_id);
                // Set rate limit - 24 hours per stage
                set_transient($rate_limit_key, true, DAY_IN_SECONDS);
                error_log("[EIPSI Cron] Nudge {$current_stage} sent and rate limit set for participant {$assignment->participant_id}");
            } else {
                error_log("[EIPSI Cron] NUDGE {$current_stage} FAILED for participant {$assignment->participant_id}");
            }
        }

        error_log("[EIPSI Cron] Study {$study->study_name}: {$emails_sent} reminder emails sent");
        
        // Accumulate total emails sent
        $total_emails_sent += $emails_sent;

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

    error_log("[EIPSI Cron] ========== RESUMEN NUDGE 0 ==========");
    error_log("[EIPSI Cron] Estudios procesados: " . count($studies));
    error_log("[EIPSI Cron] Total emails enviados (Nudge 0 + otros): {$total_emails_sent}");
    error_log("[EIPSI Cron] Verificar en base de datos: SELECT * FROM wp_survey_assignments WHERE reminder_count = 0 AND status = 'pending'");
    error_log("[EIPSI Cron] ==================================");
    error_log('[EIPSI Cron] Completed hourly wave reminders' . ($specific_study_id ? " for study {$specific_study_id}" : ''));
    
    // Return summary for manual cron
    return array(
        'processed_studies' => count($studies),
        'total_emails_sent' => $total_emails_sent
    );
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
        // Guard against null config (json_decode(null) is deprecated in PHP 8.1+)
        $config = !empty($study->config) ? json_decode($study->config, true) : array();
        if (!is_array($config) || empty($config['dropout_recovery_enabled'])) {
            continue;
        }

        $dropout_days = isset($config['dropout_recovery_days']) ? intval($config['dropout_recovery_days']) : 7;
        $max_emails = isset($config['max_recovery_emails']) ? intval($config['max_recovery_emails']) : 50;
        $today = current_time('Y-m-d');
        $emails_sent = 0;

        $overdue_assignments = $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, w.name as wave_name, w.wave_index, w.due_date, p.email, p.first_name, p.last_name, p.id as participant_id
             FROM {$wpdb->prefix}survey_assignments a
             JOIN {$wpdb->prefix}survey_waves w ON a.wave_id = w.id
             JOIN {$wpdb->prefix}survey_participants p ON a.participant_id = p.id
             WHERE a.study_id = %d
             AND a.status = 'pending'
             AND p.is_active = 1
             AND p.email IS NOT NULL
             AND a.reminder_count < 3
             AND DATE_ADD(DATE(COALESCE(
                 (SELECT MAX(a2.submitted_at) FROM {$wpdb->prefix}survey_assignments a2 
                  WHERE a2.participant_id = p.id AND a2.study_id = a.study_id AND a2.status = 'submitted'),
                 p.created_at
             )), INTERVAL (w.interval_days + %d) DAY) <= %s
             ORDER BY a.id DESC
             LIMIT %d",
            $study->id,
            $dropout_days,
            $today,
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

/**
 * AJAX handler for running reminders cron manually
 * Useful for testing minute-based intervals
 *
 * @since 1.6.0
 */
add_action('wp_ajax_eipsi_run_reminders_cron', 'eipsi_run_reminders_cron_handler');

function eipsi_run_reminders_cron_handler() {
    check_ajax_referer('eipsi_cron_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Permission denied', 'eipsi-forms')));
    }

    // Get selected study ID from request
    $study_id = isset($_POST['study_id']) ? intval($_POST['study_id']) : 0;
    
    error_log('[EIPSI] Manual cron execution triggered by user' . ($study_id ? " for study {$study_id}" : ''));

    global $wpdb;
    
    // Get study info for test email
    $study_info = null;
    $investigator_email = null;
    if ($study_id) {
        $study_info = $wpdb->get_row($wpdb->prepare(
            "SELECT study_name, study_code, config 
             FROM {$wpdb->prefix}survey_studies 
             WHERE id = %d",
            $study_id
        ));
        if ($study_info) {
            $config = !empty($study_info->config) ? json_decode($study_info->config, true) : array();
            $investigator_email = !empty($config['investigator_alert_email']) 
                ? $config['investigator_alert_email'] 
                : get_option('admin_email');
        }
    }

    // Run the reminders function for specific study (or all if no study selected)
    $result = eipsi_send_wave_reminders_hourly($study_id > 0 ? $study_id : null);

    // Send test email to investigator using EIPSI_Email_Service (with SMTP)
    $test_email_sent = false;
    if ($study_info && $investigator_email) {
        // Ensure email service is loaded
        if (!class_exists('EIPSI_Email_Service')) {
            require_once plugin_dir_path(__FILE__) . 'services/class-email-service.php';
        }
        
        $test_subject = "[EIPSI Forms] Prueba de Recordatorio - {$study_info->study_name}";
        $test_message = sprintf(
            "Este es un email de prueba del sistema EIPSI Forms.\n\n" .
            "Estudio: %s (%s)\n" .
            "ID de Estudio: %d\n" .
            "Fecha/Hora: %s\n" .
            "Ejecutado por: %s\n\n" .
            "Resumen de ejecución:\n" .
            "- Estudios procesados: %d\n" .
            "- Emails enviados a participantes: %d\n\n" .
            "Si recibiste este email, el sistema de recordatorios está funcionando correctamente.",
            $study_info->study_name,
            $study_info->study_code,
            $study_id,
            date('Y-m-d H:i:s'),
            wp_get_current_user()->display_name,
            $result['processed_studies'] ?? 0,
            $result['total_emails_sent'] ?? 0
        );
        
        // v2.1.3: Use wp_mail directly for test email (send_email is private)
        // Note: Test emails to investigators don't need SMTP logging
        $headers = array('Content-Type: text/html; charset=UTF-8');
        $test_email_sent = wp_mail($investigator_email, $test_subject, nl2br($test_message), $headers);

        error_log("[EIPSI] Test email sent to investigator: {$investigator_email} - Result: " . ($test_email_sent ? 'SUCCESS' : 'FAILED'));
    }

    // Get last cron logs
    $logs = $wpdb->get_results(
        "SELECT id, survey_id, participant_id, email_type, status, sent_at, error_message
         FROM {$wpdb->prefix}survey_email_log
         WHERE email_type = 'reminder'
         AND sent_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
         ORDER BY sent_at DESC
         LIMIT 10"
    );

    // Build response message
    if ($study_info) {
        $message = sprintf('Cron ejecutado para estudio "%s".', $study_info->study_name);
    } else {
        $message = 'Cron ejecutado para todos los estudios activos.';
    }
    
    if (!empty($logs)) {
        $count = count($logs);
        $message .= " Enviados {$count} recordatorios a participantes.";
    }
    
    if ($test_email_sent) {
        $message .= " Email de prueba enviado a {$investigator_email}.";
    }

    wp_send_json_success(array(
        'message' => $message,
        'logs' => $logs,
        'study_id' => $study_id,
        'test_email_sent' => $test_email_sent,
        'investigator_email' => $investigator_email
    ));
}

/**
 * AJAX handler for clearing rate limit transients
 * Useful for testing minute-based intervals repeatedly
 *
 * @since 1.6.0
 */
add_action('wp_ajax_eipsi_clear_rate_limits', 'eipsi_clear_rate_limits_handler');

function eipsi_clear_rate_limits_handler() {
    check_ajax_referer('eipsi_cron_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Permission denied', 'eipsi-forms')));
    }

    global $wpdb;

    // Delete all eipsi_reminder transients
    $deleted = $wpdb->query(
        "DELETE FROM {$wpdb->options}
         WHERE option_name LIKE '_transient_eipsi_reminder_%'
         OR option_name LIKE '_transient_timeout_eipsi_reminder_%'"
    );

    error_log('[EIPSI] Rate limits cleared by user. Deleted: ' . ($deleted !== false ? $deleted : 0));

    wp_send_json_success(array(
        'message' => __('Rate limits cleared. You can now test again.', 'eipsi-forms'),
        'deleted' => $deleted !== false ? $deleted : 0
    ));
}
