<?php
/**
 * EIPSI_Failed_Email_Alerts_Service
 *
 * Gestiona alertas de emails fallidos y proporciona funcionalidad de reintento.
 * Se integra con la pestaña de monitoreo.
 *
 * @package EIPSI_Forms
 * @subpackage Services
 * @version 2.1.0
 * @since Phase 3 - Task 3B.5
 */

if (!defined('ABSPATH')) {
    exit;
}

class EIPSI_Failed_Email_Alerts_Service {

    /**
     * Get failed email alerts with participant info.
     *
     * @param int $limit Number of alerts to retrieve
     * @param int $study_id Optional study filter
     * @return array Failed email alerts
     */
    public static function get_failed_email_alerts($limit = 50, $study_id = 0) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'survey_email_log';
        $participants_table = $wpdb->prefix . 'survey_participants';

        $where = array('el.status = %s');
        $params = array('failed');

        if ($study_id > 0) {
            $where[] = 'el.survey_id = %d';
            $params[] = $study_id;
        }

        $where_clause = implode(' AND ', $where);

        $query = "SELECT
                    el.id as log_id,
                    el.survey_id,
                    el.participant_id,
                    el.email_type,
                    el.recipient_email,
                    el.subject,
                    el.error_message,
                    el.sent_at as failed_at,
                    el.metadata,
                    p.first_name,
                    p.last_name,
                    p.email as participant_email,
                    p.is_active
                  FROM {$table_name} el
                  LEFT JOIN {$participants_table} p ON el.participant_id = p.id
                  WHERE {$where_clause}
                  ORDER BY el.sent_at DESC
                  LIMIT %d";

        $params[] = $limit;
        $alerts = $wpdb->get_results($wpdb->prepare($query, $params));

        return array_map(function($alert) {
            return array(
                'log_id' => (int) $alert->log_id,
                'survey_id' => (int) $alert->survey_id,
                'participant_id' => (int) $alert->participant_id,
                'email_type' => $alert->email_type,
                'recipient_email' => $alert->recipient_email,
                'subject' => $alert->subject,
                'error_message' => $alert->error_message,
                'failed_at' => $alert->failed_at,
                'participant_name' => trim($alert->first_name . ' ' . $alert->last_name) ?: 'Unknown',
                'participant_email' => $alert->participant_email,
                'is_active' => (bool) $alert->is_active,
                'can_retry' => self::can_retry_email($alert->email_type, $alert->participant_id),
                'failure_category' => self::categorize_failure($alert->error_message)
            );
        }, $alerts);
    }

    /**
     * Categorize email failure for better understanding.
     */
    private static function categorize_failure($error_message) {
        if (empty($error_message)) {
            return array('type' => 'unknown', 'label' => 'Desconocido');
        }

        $error_lower = strtolower($error_message);

        // Bounce categories
        if (strpos($error_lower, 'bounce') !== false || strpos($error_lower, 'rejected') !== false) {
            return array('type' => 'bounce', 'label' => 'Rechazado (Bounce)');
        }

        // SMTP connection errors
        if (strpos($error_lower, 'smtp') !== false || strpos($error_lower, 'connection') !== false) {
            return array('type' => 'smtp', 'label' => 'Error SMTP');
        }

        // Authentication errors
        if (strpos($error_lower, 'auth') !== false || strpos($error_lower, 'login') !== false || strpos($error_lower, 'credential') !== false) {
            return array('type' => 'auth', 'label' => 'Error de autenticación');
        }

        // Timeout errors
        if (strpos($error_lower, 'timeout') !== false || strpos($error_lower, 'timed out') !== false) {
            return array('type' => 'timeout', 'label' => 'Timeout');
        }

        // DNS/MX errors
        if (strpos($error_lower, 'mx') !== false || strpos($error_lower, 'dns') !== false || strpos($error_lower, 'domain') !== false) {
            return array('type' => 'dns', 'label' => 'Error de dominio/DNS');
        }

        // Mailbox full
        if (strpos($error_lower, 'full') !== false || strpos($error_lower, 'quota') !== false || strpos($error_lower, 'space') !== false) {
            return array('type' => 'mailbox_full', 'label' => 'Buzón lleno');
        }

        // Invalid email
        if (strpos($error_lower, 'invalid') !== false || strpos($error_lower, 'not exist') !== false) {
            return array('type' => 'invalid_email', 'label' => 'Email inválido');
        }

        return array('type' => 'other', 'label' => 'Otro error');
    }

    /**
     * Check if an email type can be retried.
     */
    private static function can_retry_email($email_type, $participant_id) {
        if (!$participant_id) {
            return false;
        }

        // These email types can be retried
        $retryable_types = array(
            'welcome',
            'reminder',
            'confirmation',
            'recovery',
            'magic_link',
            'manual_reminder'
        );

        return in_array($email_type, $retryable_types, true);
    }

    /**
     * Retry a failed email.
     *
     * @param int $log_id The email log ID
     * @return array Result of retry attempt
     */
    public static function retry_failed_email($log_id) {
        global $wpdb;

        $log_id = absint($log_id);
        if (!$log_id) {
            return array(
                'success' => false,
                'message' => 'ID de log inválido'
            );
        }

        // Get the failed email log
        $log = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}survey_email_log WHERE id = %d",
            $log_id
        ));

        if (!$log) {
            return array(
                'success' => false,
                'message' => 'Log no encontrado'
            );
        }

        if ($log->status !== 'failed') {
            return array(
                'success' => false,
                'message' => 'Este email no está marcado como fallido'
            );
        }

        // Check if we can retry this type
        if (!self::can_retry_email($log->email_type, $log->participant_id)) {
            return array(
                'success' => false,
                'message' => 'Este tipo de email no se puede reintentar'
            );
        }

        // Use the email service to resend
        if (!class_exists('EIPSI_Email_Service')) {
            require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/services/class-email-service.php';
        }

        // Get wave info from metadata if available
        $wave_id = null;
        if (!empty($log->metadata)) {
            $metadata = json_decode($log->metadata, true);
            if (is_array($metadata) && isset($metadata['wave_id'])) {
                $wave_id = (int) $metadata['wave_id'];
            }
        }

        // Try to resend based on email type
        $result = EIPSI_Email_Service::resend_participant_email(
            $log->participant_id,
            $log->email_type,
            $log->survey_id,
            $wave_id
        );

        if ($result['success']) {
            // Update the original log to mark it as "retried"
            $wpdb->update(
                $wpdb->prefix . 'survey_email_log',
                array(
                    'metadata' => wp_json_encode(array(
                        'retried' => true,
                        'retried_at' => current_time('mysql'),
                        'retry_success' => true,
                        'wave_id' => $wave_id
                    ))
                ),
                array('id' => $log_id),
                array('%s'),
                array('%d')
            );

            return array(
                'success' => true,
                'message' => 'Email reenviado exitosamente',
                'new_log_id' => $wpdb->insert_id
            );
        } else {
            return array(
                'success' => false,
                'message' => $result['message'] ?? 'Error al reenviar el email'
            );
        }
    }

    /**
     * Get summary statistics of failed emails.
     *
     * @param int $days Number of days to look back
     * @return array Summary statistics
     */
    public static function get_failure_summary($days = 7) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'survey_email_log';
        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Total failed in period
        $total_failed = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name}
             WHERE status = 'failed' AND sent_at >= %s",
            $since
        ));

        // Failed by type
        $by_type = $wpdb->get_results($wpdb->prepare(
            "SELECT email_type, COUNT(*) as count
             FROM {$table_name}
             WHERE status = 'failed' AND sent_at >= %s
             GROUP BY email_type",
            $since
        ));

        // Failed by day
        $by_day = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(sent_at) as date, COUNT(*) as count
             FROM {$table_name}
             WHERE status = 'failed' AND sent_at >= %s
             GROUP BY DATE(sent_at)
             ORDER BY date DESC",
            $since
        ));

        // Recent failures requiring attention (last 24h)
        $recent_count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name}
             WHERE status = 'failed' AND sent_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        ));

        return array(
            'total_failed' => $total_failed,
            'period_days' => $days,
            'by_type' => $by_type,
            'by_day' => $by_day,
            'recent_count' => $recent_count,
            'needs_attention' => $recent_count > 5
        );
    }

    /**
     * Get retry history for a participant.
     *
     * @param int $participant_id
     * @return array Retry history
     */
    public static function get_participant_retry_history($participant_id) {
        global $wpdb;

        $participant_id = absint($participant_id);
        if (!$participant_id) {
            return array();
        }

        $logs = $wpdb->get_results($wpdb->prepare(
            "SELECT
                id,
                email_type,
                status,
                error_message,
                sent_at,
                metadata
             FROM {$wpdb->prefix}survey_email_log
             WHERE participant_id = %d
             ORDER BY sent_at DESC
             LIMIT 50",
            $participant_id
        ));

        return array_map(function($log) {
            $metadata = !empty($log->metadata) ? json_decode($log->metadata, true) : array();

            return array(
                'log_id' => (int) $log->id,
                'email_type' => $log->email_type,
                'status' => $log->status,
                'error_message' => $log->error_message,
                'sent_at' => $log->sent_at,
                'was_retried' => !empty($metadata['retried']),
                'retry_success' => !empty($metadata['retry_success']),
                'retried_at' => $metadata['retried_at'] ?? null
            );
        }, $logs);
    }

    /**
     * Mark failed emails for bulk retry.
     *
     * @param array $log_ids Array of log IDs to retry
     * @return array Results of bulk retry
     */
    public static function bulk_retry($log_ids) {
        $results = array(
            'success' => true,
            'total' => count($log_ids),
            'successful' => 0,
            'failed' => 0,
            'errors' => array()
        );

        foreach ($log_ids as $log_id) {
            $result = self::retry_failed_email($log_id);

            if ($result['success']) {
                $results['successful']++;
            } else {
                $results['failed']++;
                $results['errors'][] = array(
                    'log_id' => $log_id,
                    'message' => $result['message']
                );
            }
        }

        if ($results['failed'] > 0 && $results['successful'] === 0) {
            $results['success'] = false;
        }

        return $results;
    }

    /**
     * Get dashboard widget data for failed emails.
     *
     * @return array Widget data
     */
    public static function get_dashboard_widget_data() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'survey_email_log';

        // Failed in last 24 hours
        $failed_24h = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table_name}
             WHERE status = 'failed' AND sent_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        );

        // Failed in last 7 days
        $failed_7d = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table_name}
             WHERE status = 'failed' AND sent_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
        );

        // Recent failures (need retry)
        $recent_failures = $wpdb->get_results(
            "SELECT
                el.id,
                el.recipient_email,
                el.email_type,
                el.error_message,
                el.sent_at,
                CONCAT(p.first_name, ' ', p.last_name) as participant_name
             FROM {$table_name} el
             LEFT JOIN {$wpdb->prefix}survey_participants p ON el.participant_id = p.id
             WHERE el.status = 'failed'
             AND el.sent_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
             ORDER BY el.sent_at DESC
             LIMIT 5"
        );

        return array(
            'failed_24h' => $failed_24h,
            'failed_7d' => $failed_7d,
            'recent_failures' => $recent_failures,
            'needs_attention' => $failed_24h > 0
        );
    }
}
