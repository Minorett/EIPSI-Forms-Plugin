<?php
/**
 * EIPSI Email Service
 * 
 * Gestiona envío de emails automáticos para participantes:
 * - Bienvenida al estudio
 * - Recordatorios de waves pendientes
 * - Magic links para acceso directo
 * - Confirmaciones de envío
 * - Recuperación de dropouts
 *
 * @package EIPSI_Forms
 * @since 1.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class EIPSI_Email_Service {
    
    /**
     * Generar URL completa de magic link
     * 
     * @param int $survey_id
     * @param int $participant_id
     * @return string|false Full URL or false on failure
     */
    public static function generate_magic_link_url($survey_id, $participant_id) {
        if (!class_exists('EIPSI_MagicLinksService')) {
            require_once plugin_dir_path(__FILE__) . 'class-magic-links-service.php';
        }

        $token = EIPSI_MagicLinksService::generate_magic_link($survey_id, $participant_id);
        
        if (!$token) {
            return false;
        }

        // URL structure: site_url/?eipsi_magic=TOKEN
        return add_query_arg('eipsi_magic', $token, site_url('/'));
    }
    
    /**
     * Enviar email de bienvenida a nuevo participante
     * 
     * @param int $survey_id
     * @param int $participant_id
     * @return bool
     */
    public static function send_welcome_email($survey_id, $participant_id) {
        $participant = self::get_participant($participant_id);
        if (!$participant) return false;

        $survey_name = get_the_title($survey_id);
        $magic_link = self::generate_magic_link_url($survey_id, $participant_id);
        
        if (!$magic_link) {
            self::log_email($survey_id, $participant_id, 'welcome', 'failed', 'Could not generate magic link');
            return false;
        }

        $placeholders = array(
            'first_name' => $participant->first_name,
            'last_name' => $participant->last_name,
            'survey_name' => $survey_name,
            'magic_link' => $magic_link,
            'investigator_name' => get_option('eipsi_investigator_name', 'Equipo de Investigación'),
            'investigator_email' => get_option('eipsi_investigator_email', get_option('admin_email')),
        );

        $subject = "Bienvenido a {$survey_name}";
        $content = self::render_template('welcome', $placeholders);

        return self::send_email($survey_id, $participant_id, $participant->email, 'welcome', $subject, $content);
    }

    /**
     * Enviar recordatorio de wave pendiente
     * 
     * @param int $survey_id
     * @param int $participant_id
     * @param int|object $wave Wave object or ID
     * @return bool
     */
    public static function send_wave_reminder_email($survey_id, $participant_id, $wave) {
        $participant = self::get_participant($participant_id);
        if (!$participant) return false;

        // Ensure we have the wave object
        if (is_numeric($wave)) {
            if (class_exists('EIPSI_Wave_Service')) {
                $wave = EIPSI_Wave_Service::get_wave($wave);
            } else {
                // Fallback direct DB
                global $wpdb;
                $wave = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}survey_waves WHERE id = %d", $wave));
            }
        }

        if (!$wave) {
            self::log_email($survey_id, $participant_id, 'reminder', 'failed', 'Invalid wave ID');
            return false;
        }

        $survey_name = get_the_title($survey_id);
        $magic_link = self::generate_magic_link_url($survey_id, $participant_id);

        if (!$magic_link) {
            self::log_email($survey_id, $participant_id, 'reminder', 'failed', 'Could not generate magic link');
            return false;
        }

        // Calculate due date formatted
        $due_date = !empty($wave->due_date) ? date_i18n(get_option('date_format'), strtotime($wave->due_date)) : 'Pronto';

        $placeholders = array(
            'first_name' => $participant->first_name,
            'survey_name' => $survey_name,
            'wave_index' => isset($wave->wave_index) ? "Toma " . $wave->wave_index : $wave->name,
            'due_at' => $due_date,
            'magic_link' => $magic_link,
            'estimated_time' => isset($wave->estimated_time) ? $wave->estimated_time : '10-15',
            'investigator_name' => get_option('eipsi_investigator_name', 'Equipo de Investigación'),
            'investigator_email' => get_option('eipsi_investigator_email', get_option('admin_email')),
        );

        $subject = "Recordatorio: Tu próxima toma en {$survey_name}";
        $content = self::render_template('wave-reminder', $placeholders);

        return self::send_email($survey_id, $participant_id, $participant->email, 'reminder', $subject, $content);
    }

    /**
     * Enviar confirmación de recepción
     * 
     * @param int $survey_id
     * @param int $participant_id
     * @param int|object $wave Wave that was just completed
     * @param int|object|null $next_wave Next wave object (optional)
     * @return bool
     */
    public static function send_wave_confirmation_email($survey_id, $participant_id, $wave, $next_wave = null) {
        $participant = self::get_participant($participant_id);
        if (!$participant) return false;

        $survey_name = get_the_title($survey_id);
        
        // Handle Wave Object/ID
        if (is_numeric($wave) && class_exists('EIPSI_Wave_Service')) {
            $wave = EIPSI_Wave_Service::get_wave($wave);
        } else if (is_numeric($wave)) {
            global $wpdb;
            $wave = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}survey_waves WHERE id = %d", $wave));
        }

        $wave_name = $wave ? (isset($wave->wave_index) ? "Toma " . $wave->wave_index : $wave->name) : 'Toma reciente';

        // Next wave info
        $next_wave_text = "Te avisaremos pronto";
        $next_due_text = "Por definir";

        if ($next_wave) {
             if (is_numeric($next_wave) && class_exists('EIPSI_Wave_Service')) {
                $next_wave = EIPSI_Wave_Service::get_wave($next_wave);
            }
            if ($next_wave) {
                $next_wave_text = isset($next_wave->wave_index) ? "Toma " . $next_wave->wave_index : $next_wave->name;
                $next_due_text = !empty($next_wave->start_date) ? date_i18n(get_option('date_format'), strtotime($next_wave->start_date)) : 'Pronto';
            }
        }

        $placeholders = array(
            'first_name' => $participant->first_name,
            'survey_name' => $survey_name,
            'wave_index' => $wave_name,
            'submitted_at' => date_i18n(get_option('date_format') . ' ' . get_option('time_format')),
            'next_wave_index' => $next_wave_text,
            'next_due_at' => $next_due_text,
            'investigator_name' => get_option('eipsi_investigator_name', 'Equipo de Investigación'),
            'investigator_email' => get_option('eipsi_investigator_email', get_option('admin_email')),
        );

        $subject = "Recibimos tu respuesta";
        $content = self::render_template('wave-confirmation', $placeholders);

        return self::send_email($survey_id, $participant_id, $participant->email, 'confirmation', $subject, $content);
    }

    /**
     * Enviar recuperación de dropout (te extrañamos)
     * 
     * @param int $survey_id
     * @param int $participant_id
     * @param int|object $wave Missed wave
     * @return bool
     */
    public static function send_dropout_recovery_email($survey_id, $participant_id, $wave) {
        $participant = self::get_participant($participant_id);
        if (!$participant) return false;

        $survey_name = get_the_title($survey_id);
        
        if (is_numeric($wave) && class_exists('EIPSI_Wave_Service')) {
            $wave = EIPSI_Wave_Service::get_wave($wave);
        } else if (is_numeric($wave)) {
            global $wpdb;
            $wave = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}survey_waves WHERE id = %d", $wave));
        }

        if (!$wave) {
            self::log_email($survey_id, $participant_id, 'recovery', 'failed', 'Invalid wave ID');
            return false;
        }

        $magic_link = self::generate_magic_link_url($survey_id, $participant_id);

        if (!$magic_link) {
            self::log_email($survey_id, $participant_id, 'recovery', 'failed', 'Could not generate magic link');
            return false;
        }

        $placeholders = array(
            'first_name' => $participant->first_name,
            'survey_name' => $survey_name,
            'wave_index' => isset($wave->wave_index) ? "Toma " . $wave->wave_index : $wave->name,
            'magic_link' => $magic_link,
            'investigator_name' => get_option('eipsi_investigator_name', 'Equipo de Investigación'),
            'investigator_email' => get_option('eipsi_investigator_email', get_option('admin_email')),
        );

        $subject = "Te extrañamos - {$survey_name}";
        $content = self::render_template('dropout-recovery', $placeholders);

        return self::send_email($survey_id, $participant_id, $participant->email, 'recovery', $subject, $content);
    }

    /**
     * Helper: Obtener participante por ID
     */
    private static function get_participant($participant_id) {
        global $wpdb;
        $participant = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}survey_participants WHERE id = %d",
            $participant_id
        ));

        if (!$participant) {
            error_log("[EIPSI Email] Participant not found: $participant_id");
            return null;
        }

        return $participant;
    }

    /**
     * Helper: Renderizar plantilla HTML
     */
    private static function render_template($template_name, $placeholders = array()) {
        $file_path = plugin_dir_path(dirname(dirname(__FILE__))) . 'includes/emails/' . $template_name . '.php';
        
        if (!file_exists($file_path)) {
            error_log("[EIPSI Email] Template not found: $file_path");
            return "<p>Error: Email template missing.</p>";
        }
        
        ob_start();
        include $file_path;
        $content = ob_get_clean();

        $final_content = $content;
        foreach ($placeholders as $key => $value) {
            if ($key === 'magic_link') {
                $safe_value = $value; // URL is safe (generated by us)
            } else {
                $safe_value = esc_html($value);
            }
            $final_content = str_replace('{{' . $key . '}}', $safe_value, $final_content);
        }

        return $final_content;
    }

    /**
     * Helper: Enviar email y registrar log
     */
    private static function send_email($survey_id, $participant_id, $to, $type, $subject, $content) {
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        try {
            $sent = wp_mail($to, $subject, $content, $headers);
            
            if ($sent) {
                self::log_email($survey_id, $participant_id, $type, 'sent', null);
                return true;
            } else {
                self::log_email($survey_id, $participant_id, $type, 'failed', 'wp_mail returned false');
                return false;
            }
        } catch (Exception $e) {
            self::log_email($survey_id, $participant_id, $type, 'failed', $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registrar envío en log
     */
    public static function log_email($survey_id, $participant_id, $type, $status, $error_message = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'survey_email_log';
        
        $wpdb->insert(
            $table_name,
            array(
                'survey_id' => $survey_id,
                'participant_id' => $participant_id,
                'email_type' => $type,
                'recipient_email' => self::get_participant_email($participant_id),
                'subject' => '', // Optional to fill if passed
                'status' => $status,
                'error_message' => $error_message,
                'sent_at' => current_time('mysql'),
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Helper to get email quickly
     */
    private static function get_participant_email($participant_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare("SELECT email FROM {$wpdb->prefix}survey_participants WHERE id = %d", $participant_id));
    }

    /**
     * Obtener historial de emails enviados
     */
    public static function get_email_history($survey_id, $limit = 100) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'survey_email_log';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE survey_id = %d ORDER BY sent_at DESC LIMIT %d",
            $survey_id,
            $limit
        ));
    }

    /**
     * Obtener logs de emails con filtros (para dashboard)
     * 
     * @param int $survey_id ID del estudio (opcional)
     * @param array $filters Filtros {type, status, date_from, date_to}
     * @param int $limit Límite de resultados
     * @param int $offset Offset para paginación
     * @return array {logs, total}
     */
    public static function get_email_log_entries($survey_id = 0, $filters = array(), $limit = 20, $offset = 0) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'survey_email_log';
        $participants_table = $wpdb->prefix . 'survey_participants';
        
        $where = array();
        $params = array();
        
        // Survey filter
        if ($survey_id > 0) {
            $where[] = 'el.survey_id = %d';
            $params[] = $survey_id;
        }
        
        // Type filter
        if (!empty($filters['type'])) {
            $where[] = 'el.email_type = %s';
            $params[] = sanitize_text_field($filters['type']);
        }
        
        // Status filter
        if (!empty($filters['status'])) {
            $where[] = 'el.status = %s';
            $params[] = sanitize_text_field($filters['status']);
        }
        
        // Date range filters
        if (!empty($filters['date_from'])) {
            $where[] = 'DATE(el.sent_at) >= %s';
            $params[] = sanitize_text_field($filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = 'DATE(el.sent_at) <= %s';
            $params[] = sanitize_text_field($filters['date_to']);
        }
        
        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // Get total count
        $count_query = "SELECT COUNT(*) FROM {$table_name} el {$where_clause}";
        $total = $wpdb->get_var($wpdb->prepare($count_query, $params));
        
        // Get logs with participant names
        $query = "SELECT el.*, 
                         CONCAT(p.first_name, ' ', p.last_name) as participant_name
                  FROM {$table_name} el
                  LEFT JOIN {$participants_table} p ON el.participant_id = p.id
                  {$where_clause}
                  ORDER BY el.sent_at DESC
                  LIMIT %d OFFSET %d";
        
        $params[] = (int) $limit;
        $params[] = (int) $offset;
        
        $logs = $wpdb->get_results($wpdb->prepare($query, $params));
        
        return array(
            'logs' => $logs,
            'total' => (int) $total
        );
    }

    /**
     * Obtener detalles de un email log específico
     * 
     * @param int $email_log_id ID del log
     * @return object|null
     */
    public static function get_email_details($email_log_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'survey_email_log';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT el.*, 
                    CONCAT(p.first_name, ' ', p.last_name) as participant_name
             FROM {$table_name} el
             LEFT JOIN {$wpdb->prefix}survey_participants p ON el.participant_id = p.id
             WHERE el.id = %d",
            (int) $email_log_id
        ));
    }

    /**
     * Reenviar email fallido
     * 
     * @param int $email_log_id ID del log original
     * @return array {success, message, new_log_id}
     */
    public static function resend_email($email_log_id) {
        global $wpdb;
        
        // Get original email log
        $original_log = self::get_email_details($email_log_id);
        
        if (!$original_log) {
            return array(
                'success' => false,
                'message' => 'Email log not found'
            );
        }
        
        if ($original_log->status === 'sent') {
            return array(
                'success' => false,
                'message' => 'Email already sent successfully'
            );
        }
        
        // Get participant and wave info if needed
        $participant = self::get_participant($original_log->participant_id);
        if (!$participant) {
            return array(
                'success' => false,
                'message' => 'Participant not found'
            );
        }
        
        // Get wave if needed (for reminder/recovery types)
        $wave = null;
        if (in_array($original_log->email_type, array('reminder', 'recovery'))) {
            $wave = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}survey_waves WHERE id = %d",
                $original_log->metadata ? json_decode($original_log->metadata, true)['wave_id'] ?? 0 : 0
            ));
        }
        
        // Resend based on type
        $result = false;
        switch ($original_log->email_type) {
            case 'welcome':
                $result = self::send_welcome_email($original_log->survey_id, $original_log->participant_id);
                break;
            case 'reminder':
                $result = $wave ? self::send_wave_reminder_email($original_log->survey_id, $original_log->participant_id, $wave) : false;
                break;
            case 'confirmation':
                $result = self::send_wave_confirmation_email($original_log->survey_id, $original_log->participant_id, $wave);
                break;
            case 'recovery':
                $result = $wave ? self::send_dropout_recovery_email($original_log->survey_id, $original_log->participant_id, $wave) : false;
                break;
        }
        
        if ($result) {
            return array(
                'success' => true,
                'message' => 'Email resent successfully',
                'new_log_id' => $wpdb->insert_id
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Failed to resend email'
            );
        }
    }

    /**
     * Obtener estadísticas de entregabilidad
     * 
     * @param int $survey_id ID del estudio
     * @return array {sent, failed, total, success_rate}
     */
    public static function get_email_deliverability_stats($survey_id = 0) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'survey_email_log';
        
        $where = $survey_id > 0 ? $wpdb->prepare("WHERE survey_id = %d", $survey_id) : '';
        
        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} {$where}");
        $sent = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} {$where}" . ($where ? ' AND ' : 'WHERE ') . "status = 'sent'");
        $failed = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} {$where}" . ($where ? ' AND ' : 'WHERE ') . "status = 'failed'");
        
        return array(
            'total' => $total,
            'sent' => $sent,
            'failed' => $failed,
            'success_rate' => $total > 0 ? round(($sent / $total) * 100, 1) : 0
        );
    }
}
