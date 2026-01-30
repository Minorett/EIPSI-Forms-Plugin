<?php
/**
 * System Monitoring Dashboard
 * Recopila métricas de email, cron, sesiones, DB en tiempo real
 */

defined('ABSPATH') || exit;

class EIPSI_Monitoring {

    /**
     * Get email statistics
     */
    public static function get_email_stats() {
        global $wpdb;

        $stats = array(
            'sent_today' => 0,
            'failed_today' => 0,
            'bounce_rate' => 0,
            'last_sent' => null,
            'pending_count' => 0,
        );

        $table = $wpdb->prefix . 'survey_email_log';

        if (!self::table_exists($table)) {
            return $stats;
        }

        // Enviados hoy
        $stats['sent_today'] = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE status = %s AND DATE(sent_at) = CURDATE()",
            'sent'
        ));

        // Fallidos hoy
        $stats['failed_today'] = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE status = %s AND DATE(sent_at) = CURDATE()",
            'failed'
        ));

        // Bounce rate
        $total_sent = $stats['sent_today'] + $stats['failed_today'];
        $stats['bounce_rate'] = ($total_sent > 0) ? round(($stats['failed_today'] / $total_sent) * 100, 2) : 0;

        // Último envío
        $last = $wpdb->get_row($wpdb->prepare(
            "SELECT sent_at FROM $table WHERE status = %s ORDER BY sent_at DESC LIMIT 1",
            'sent'
        ));
        $stats['last_sent'] = $last ? $last->sent_at : null;

        // Pendientes en cola
        $stats['pending_count'] = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE status = %s",
            'pending'
        ));

        return $stats;
    }

    /**
     * Get cron job status
     */
    public static function get_cron_status() {
        $status = array(
            'wave_reminders' => array('status' => 'unknown', 'last_run' => null),
            'session_cleanup' => array('status' => 'unknown', 'last_run' => null),
            'email_retry' => array('status' => 'unknown', 'last_run' => null),
            'dropout_recovery' => array('status' => 'unknown', 'last_run' => null),
        );

        $now = current_time('timestamp');

        // Leer transients para último ejecutado
        foreach (array_keys($status) as $job) {
            $last_run = get_transient("eipsi_cron_last_run_{$job}");

            if ($last_run) {
                $status[$job]['last_run'] = $last_run;
                $last_run_time = strtotime($last_run);

                if ($last_run_time) {
                    $minutes_ago = round(($now - $last_run_time) / 60);

                    // Si ejecutó en últimos 5 minutos → OK
                    if ($minutes_ago < 5) {
                        $status[$job]['status'] = 'ok';
                    } elseif ($minutes_ago < 60) {
                        $status[$job]['status'] = 'warning';
                    } else {
                        $status[$job]['status'] = 'error';
                    }
                }
            }
        }

        return $status;
    }

    /**
     * Get session statistics
     */
    public static function get_session_stats() {
        global $wpdb;

        $stats = array(
            'active_sessions' => 0,
            'expired_today' => 0,
            'unused_sessions' => 0,
        );

        $table = $wpdb->prefix . 'survey_sessions';

        if (!self::table_exists($table)) {
            return $stats;
        }

        // Sesiones activas
        $stats['active_sessions'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $table WHERE expires_at > NOW()"
        );

        // Expiradas hoy
        $stats['expired_today'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $table WHERE expires_at <= NOW() AND DATE(expires_at) = CURDATE()"
        );

        // Sin usar (últimas 24h sin actividad)
        $stats['unused_sessions'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $table 
             WHERE expires_at > NOW() 
             AND last_activity < DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        );

        return $stats;
    }

    /**
     * Get database health
     */
    public static function get_db_health() {
        global $wpdb;

        $health = array(
            'queries_per_minute' => 0,
            'slow_queries' => 0,
            'table_size_mb' => 0,
            'connection_status' => 'ok',
        );

        // Database size
        $size = $wpdb->get_var($wpdb->prepare(
            "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size
             FROM information_schema.tables
             WHERE table_schema = %s",
            DB_NAME
        ));
        $health['table_size_mb'] = $size ? (float) $size : 0;

        // Connection test
        $wpdb->query('SELECT 1');
        if (!empty($wpdb->last_error)) {
            $health['connection_status'] = 'error';
        }

        return $health;
    }

    /**
     * Get audit log recent entries
     */
    public static function get_audit_log_entries($limit = 10) {
        global $wpdb;

        $table = $wpdb->prefix . 'survey_audit_log';

        if (!self::table_exists($table)) {
            return array();
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT action, user_id, details, created_at 
             FROM $table 
             ORDER BY created_at DESC 
             LIMIT %d",
            $limit
        ));
    }

    private static function table_exists($table) {
        global $wpdb;

        $table_name = $wpdb->get_var(
            $wpdb->prepare('SHOW TABLES LIKE %s', $table)
        );

        return $table_name === $table;
    }
}

// Hook para actualizar transients de cron
add_action('eipsi_cron_wave_reminders', function() {
    set_transient('eipsi_cron_last_run_wave_reminders', current_time('mysql'), HOUR_IN_SECONDS);
});

add_action('eipsi_cron_session_cleanup', function() {
    set_transient('eipsi_cron_last_run_session_cleanup', current_time('mysql'), HOUR_IN_SECONDS);
});

add_action('eipsi_cron_email_retry', function() {
    set_transient('eipsi_cron_last_run_email_retry', current_time('mysql'), HOUR_IN_SECONDS);
});

add_action('eipsi_cron_dropout_recovery', function() {
    set_transient('eipsi_cron_last_run_dropout_recovery', current_time('mysql'), HOUR_IN_SECONDS);
});
