<?php
/**
 * EIPSI_Email_Service
 *
 * Servicio de emails transaccionales para estudios longitudinales:
 * - Templates HTML
 * - Magic links
 * - Logging
 * - Rate limiting
 *
 * @package EIPSI_Forms
 * @subpackage Services
 * @version 1.4.1
 * @since 1.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class EIPSI_Email_Service {
    
    /**
     * Generate magic link URL.
     *
     * @param int $survey_id Survey ID.
     * @param int $participant_id Participant ID.
     * @return string|false Full URL or false on failure.
     * @since 1.4.1
     * @access public
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
     * Send welcome email con magic link.
     *
     * Template: includes/emails/welcome.php
     *
     * @param int $survey_id Survey ID.
     * @param int $participant_id Participant ID.
     * @return bool True si enviado, false si error.
     * @since 1.4.1
     * @access public
     */
    public static function send_welcome_email($survey_id, $participant_id) {
        $participant = self::get_participant($participant_id);
        if (!$participant) return false;

        // Verificar que el participante esté activo
        if (!$participant->is_active) {
            error_log("[EIPSI Email] Cannot send welcome email to inactive participant: $participant_id");
            return false;
        }

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
     * Send wave reminder email.
     *
     * Template: includes/emails/wave-reminder.php
     *
     * @param int        $survey_id Survey ID.
     * @param int        $participant_id Participant ID.
     * @param int|object $wave Wave object or ID.
     * @return bool True si enviado, false si error.
     * @since 1.4.1
     * @access public
     */
    public static function send_wave_reminder_email($survey_id, $participant_id, $wave) {
        $participant = self::get_participant($participant_id);
        if (!$participant) return false;

        // Verificar que el participante esté activo
        if (!$participant->is_active) {
            error_log("[EIPSI Email] Cannot send reminder email to inactive participant: $participant_id");
            return false;
        }

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
     * Send wave confirmation email.
     *
     * Template: includes/emails/wave-confirmation.php
     *
     * @param int             $survey_id Survey ID.
     * @param int             $participant_id Participant ID.
     * @param int|object      $wave Wave completed.
     * @param int|object|null $next_wave Next wave object (optional).
     * @return bool True si enviado, false si error.
     * @since 1.4.1
     * @access public
     */
    public static function send_wave_confirmation_email($survey_id, $participant_id, $wave, $next_wave = null) {
        $participant = self::get_participant($participant_id);
        if (!$participant) return false;

        // Verificar que el participante esté activo
        if (!$participant->is_active) {
            error_log("[EIPSI Email] Cannot send confirmation email to inactive participant: $participant_id");
            return false;
        }

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
     * Send dropout recovery email.
     *
     * Template: includes/emails/dropout-recovery.php
     *
     * @param int        $survey_id Survey ID.
     * @param int        $participant_id Participant ID.
     * @param int|object $wave Missed wave.
     * @return bool True si enviado, false si error.
     * @since 1.4.1
     * @access public
     */
    public static function send_dropout_recovery_email($survey_id, $participant_id, $wave) {
        $participant = self::get_participant($participant_id);
        if (!$participant) return false;

        // Verificar que el participante esté activo
        if (!$participant->is_active) {
            error_log("[EIPSI Email] Cannot send recovery email to inactive participant: $participant_id");
            return false;
        }

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
     * Get participant by ID (helper).
     *
     * @param int $participant_id Participant ID.
     * @return object|null Participant object.
     * @since 1.4.1
     * @access private
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
     * Render email template HTML.
     *
     * @param string $template_name Template base name.
     * @param array  $placeholders Placeholders to replace.
     * @return string Rendered HTML.
     * @since 1.4.1
     * @access private
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
     * Send email and log result (helper).
     *
     * @param int    $survey_id Survey ID.
     * @param int    $participant_id Participant ID.
     * @param string $to Email recipient.
     * @param string $type Email type.
     * @param string $subject Email subject.
     * @param string $content Email HTML content.
     * @return bool True si enviado.
     * @since 1.4.1
     * @access private
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
     * Log email sent/failed.
     *
     * @param int    $survey_id Survey ID.
     * @param int    $participant_id Participant ID.
     * @param string $type Type (welcome, reminder, confirmation, recovery).
     * @param string $status Status (sent, failed, pending).
     * @param string|null $error_message Optional error message.
     * @return void
     * @since 1.4.1
     * @access public
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
     * Get participant email (helper).
     *
     * @param int $participant_id Participant ID.
     * @return string|null Email address or null.
     * @since 1.4.1
     * @access private
     */
    private static function get_participant_email($participant_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare("SELECT email FROM {$wpdb->prefix}survey_participants WHERE id = %d", $participant_id));
    }

    /**
     * Get email history for survey.
     *
     * @param int $survey_id Survey ID.
     * @param int $limit Límite de registros.
     * @return array Email logs.
     * @since 1.4.1
     * @access public
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
     * Get email log entries with filters (dashboard).
     *
     * @param int   $survey_id ID del estudio (opcional).
     * @param array $filters Filtros {type, status, date_from, date_to}.
     * @param int   $limit Límite de resultados.
     * @param int   $offset Offset para paginación.
     * @return array {logs, total}
     * @since 1.4.1
     * @access public
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
     * Get email log details.
     *
     * @param int $email_log_id ID del log.
     * @return object|null Email log details.
     * @since 1.4.1
     * @access public
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
     * Resend failed email.
     *
     * @param int $email_log_id ID del log original.
     * @return array {success, message, new_log_id}
     * @since 1.4.1
     * @access public
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
            $wave_id = $original_log->metadata ? json_decode($original_log->metadata, true)['wave_id'] ?? 0 : 0;
            $wave = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}survey_waves WHERE id = %d",
                $wave_id
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
     * Send manual reminders to participants.
     *
     * @param int   $survey_id Survey ID.
     * @param array $participant_ids Array of participant IDs.
     * @param int   $wave_id Wave ID (optional).
     * @param string $custom_message Custom message (optional).
     * @return array {sent_count, failed_count, total_count, errors}
     * @since 1.4.4
     * @access public
     */
    public static function send_manual_reminders($survey_id, $participant_ids, $wave_id = null, $custom_message = null) {
        global $wpdb;
        
        if (empty($participant_ids)) {
            return array(
                'sent_count' => 0,
                'failed_count' => 0,
                'total_count' => 0,
                'errors' => array('No participants specified')
            );
        }
        
        $survey_id = absint($survey_id);
        $wave_obj = null;
        $errors = array();
        $sent_count = 0;
        $failed_count = 0;
        
        // Get wave info if provided
        if ($wave_id) {
            if (class_exists('EIPSI_Wave_Service')) {
                $wave_obj = EIPSI_Wave_Service::get_wave($wave_id);
            } else {
                $wave_obj = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}survey_waves WHERE id = %d",
                    $wave_id
                ));
            }
        }
        
        foreach ($participant_ids as $participant_id) {
            $participant_id = absint($participant_id);
            $result = self::send_manual_reminder_email($survey_id, $participant_id, $wave_obj, $custom_message);
            
            if ($result) {
                $sent_count++;
            } else {
                $failed_count++;
                $errors[] = "Failed to send reminder to participant ID: $participant_id";
            }
        }
        
        return array(
            'sent_count' => $sent_count,
            'failed_count' => $failed_count,
            'total_count' => count($participant_ids),
            'errors' => $errors
        );
    }

    /**
     * Send single manual reminder email.
     *
     * @param int        $survey_id Survey ID.
     * @param int        $participant_id Participant ID.
     * @param object|null $wave Wave object.
     * @param string|null $custom_message Custom message.
     * @return bool True if sent.
     * @since 1.4.4
     * @access private
     */
    private static function send_manual_reminder_email($survey_id, $participant_id, $wave = null, $custom_message = null) {
        $participant = self::get_participant($participant_id);
        if (!$participant) {
            self::log_email($survey_id, $participant_id, 'manual_reminder', 'failed', 'Participant not found');
            return false;
        }

        $survey_name = get_the_title($survey_id);
        $magic_link = self::generate_magic_link_url($survey_id, $participant_id);

        if (!$magic_link) {
            self::log_email($survey_id, $participant_id, 'manual_reminder', 'failed', 'Could not generate magic link');
            return false;
        }

        $placeholders = array(
            'first_name' => $participant->first_name,
            'survey_name' => $survey_name,
            'wave_index' => $wave ? (isset($wave->wave_index) ? "Toma " . $wave->wave_index : $wave->name) : 'Tu próxima toma',
            'due_date' => $wave && !empty($wave->due_date) ? date_i18n(get_option('date_format'), strtotime($wave->due_date)) : 'Pronto',
            'custom_message' => !empty($custom_message) ? $custom_message : '',
            'magic_link' => $magic_link,
            'investigator_name' => get_option('eipsi_investigator_name', 'Equipo de Investigación'),
            'investigator_email' => get_option('eipsi_investigator_email', get_option('admin_email')),
        );

        $subject = $wave ? "Recordatorio: Toma {$wave->wave_index} en {$survey_name}" : "Recordatorio: {$survey_name}";
        $content = self::render_template('manual-reminder', $placeholders);

        return self::send_email($survey_id, $participant_id, $participant->email, 'manual_reminder', $subject, $content);
    }

    /**
     * Get participants who haven't completed a wave.
     *
     * @param int $survey_id Survey ID.
     * @param int $wave_id Wave ID.
     * @return array Participant IDs who haven't submitted.
     * @since 1.4.4
     * @access public
     */
    public static function get_pending_participants($survey_id, $wave_id) {
        global $wpdb;
        
        $participant_table = $wpdb->prefix . 'survey_participants';
        $wave_assignments_table = $wpdb->prefix . 'survey_wave_assignments';
        $submissions_table = $wpdb->prefix . 'survey_submissions';
        
        // Get participants assigned to wave but haven't submitted
        $query = "SELECT DISTINCT p.id, p.first_name, p.last_name, p.email
                  FROM {$participant_table} p
                  INNER JOIN {$wave_assignments_table} wa ON p.id = wa.participant_id
                  LEFT JOIN {$submissions_table} s ON p.id = s.participant_id AND wa.wave_id = s.wave_id
                  WHERE wa.wave_id = %d 
                    AND p.is_active = 1 
                    AND (s.id IS NULL OR s.status != 'completed')
                  ORDER BY p.last_name, p.first_name";
        
        return $wpdb->get_results($wpdb->prepare($query, $wave_id));
    }

    /**
     * Get email deliverability stats.
     *
     * @param int $survey_id ID del estudio.
     * @return array {sent, failed, total, success_rate}
     * @since 1.4.1
     * @access public
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
