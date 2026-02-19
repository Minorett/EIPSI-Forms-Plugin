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
     * Send magic link email on demand.
     *
     * Template: includes/emails/magic-link.php
     *
     * @param int    $survey_id Survey ID.
     * @param int    $participant_id Participant ID.
     * @param string $custom_message Optional custom message.
     * @return array {success: bool, magic_link: string|null, error: string|null}
     * @since 1.5.3
     * @access public
     */
    public static function send_magic_link_email($survey_id, $participant_id, $custom_message = '') {
        $participant = self::get_participant($participant_id);
        if (!$participant) {
            return array('success' => false, 'magic_link' => null, 'error' => 'Participante no encontrado');
        }

        // Verificar que el participante esté activo
        if (!$participant->is_active) {
            error_log("[EIPSI Email] Cannot send magic link to inactive participant: $participant_id");
            return array('success' => false, 'magic_link' => null, 'error' => 'El participante está inactivo');
        }

        $survey_name = get_the_title($survey_id);
        $magic_link = self::generate_magic_link_url($survey_id, $participant_id);

        if (!$magic_link) {
            self::log_email($survey_id, $participant_id, 'magic_link', 'failed', 'Could not generate magic link');
            return array('success' => false, 'magic_link' => null, 'error' => 'No se pudo generar el Magic Link');
        }

        $placeholders = array(
            'first_name' => $participant->first_name,
            'last_name' => $participant->last_name,
            'survey_name' => $survey_name,
            'magic_link' => $magic_link,
            'custom_message' => $custom_message,
            'investigator_name' => get_option('eipsi_investigator_name', 'Equipo de Investigación'),
            'investigator_email' => get_option('eipsi_investigator_email', get_option('admin_email')),
        );

        $subject = "Acceso rápido a {$survey_name}";
        $content = self::render_template('magic-link', $placeholders);

        $sent = self::send_email($survey_id, $participant_id, $participant->email, 'magic_link', $subject, $content);

        if (!$sent) {
            return array('success' => false, 'magic_link' => $magic_link, 'error' => 'No se pudo enviar el email');
        }

        return array('success' => true, 'magic_link' => $magic_link, 'error' => null);
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
            // Check if SMTP is configured
            $smtp_service = class_exists('EIPSI_SMTP_Service') ? new EIPSI_SMTP_Service() : null;
            $smtp_config = $smtp_service ? $smtp_service->get_config() : null;

            if ($smtp_config) {
                // Use SMTP service
                error_log("[EIPSI Email] Sending via SMTP to: $to");
                $result = $smtp_service->send_message($to, $subject, $content, $smtp_config);

                if (!empty($result['success'])) {
                    self::log_email($survey_id, $participant_id, $type, 'sent', null, $subject);
                    error_log("[EIPSI Email] SMTP send successful to: $to");
                    return true;
                }

                $error = $result['error'] ?? 'SMTP send failed';
                error_log("[EIPSI Email] SMTP send failed: $error");
                self::log_email($survey_id, $participant_id, $type, 'failed', $error, $subject);
                return false;
            }

            // Use wp_mail with enhanced error handling
            error_log("[EIPSI Email] Sending via wp_mail to: $to");
            
            // Set default From name and email if not already set
            add_filter('wp_mail_from_name', function($name) {
                $investigator_name = get_option('eipsi_investigator_name', '');
                return !empty($investigator_name) ? $investigator_name : $name;
            }, 99);
            
            add_filter('wp_mail_from', function($email) {
                $investigator_email = get_option('eipsi_investigator_email', '');
                return !empty($investigator_email) && is_email($investigator_email) 
                    ? $investigator_email 
                    : $email;
            }, 99);

            // Try to send the email
            $sent = wp_mail($to, $subject, $content, $headers);

            if ($sent) {
                self::log_email($survey_id, $participant_id, $type, 'sent', null, $subject);
                error_log("[EIPSI Email] wp_mail successful to: $to");
                return true;
            }

            // Get the last error from WordPress
            global $wp_mail_error;
            $error_msg = 'wp_mail returned false';
            if ($wp_mail_error instanceof WP_Error) {
                $error_msg = $wp_mail_error->get_error_message();
            }
            
            // Log detailed error information
            error_log("[EIPSI Email] wp_mail failed: $error_msg");
            self::log_email($survey_id, $participant_id, $type, 'failed', $error_msg, $subject);
            return false;
            
        } catch (Exception $e) {
            $error_msg = 'Exception: ' . $e->getMessage();
            error_log("[EIPSI Email] Exception during send: $error_msg");
            self::log_email($survey_id, $participant_id, $type, 'failed', $error_msg, $subject);
            return false;
        } catch (Error $e) {
            $error_msg = 'Fatal Error: ' . $e->getMessage();
            error_log("[EIPSI Email] Fatal error during send: $error_msg");
            self::log_email($survey_id, $participant_id, $type, 'failed', $error_msg, $subject);
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
    public static function log_email($survey_id, $participant_id, $type, $status, $error_message = null, $subject = '') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'survey_email_log';
        $subject = sanitize_text_field($subject);
        
        $wpdb->insert(
            $table_name,
            array(
                'survey_id' => $survey_id,
                'participant_id' => $participant_id,
                'email_type' => $type,
                'recipient_email' => self::get_participant_email($participant_id),
                'subject' => $subject,
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
     * Send a test email to verify the email system is working.
     *
     * @param string|null $to Email address to send test to. If null, uses investigator or admin email.
     * @return array {success: bool, message: string, details: string}
     * @since 1.5.5
     * @access public
     */
    public static function send_test_email($to = null) {
        // Sanitize and validate email
        $recipient = sanitize_email($to);
        
        // Use provided email or default to investigator/admin email
        if (empty($recipient)) {
            $recipient = get_option('eipsi_investigator_email', get_option('admin_email', ''));
        }
        
        if (empty($recipient) || !is_email($recipient)) {
            return array(
                'success' => false,
                'message' => __('Email de destino inválido', 'eipsi-forms'),
                'details' => __('Por favor proporciona un email válido para el test o configura el email del investigador/administrador.', 'eipsi-forms')
            );
        }

        $smtp_service = class_exists('EIPSI_SMTP_Service') ? new EIPSI_SMTP_Service() : null;
        $smtp_config = $smtp_service ? $smtp_service->get_config() : null;
        $use_smtp = !empty($smtp_config);

        $subject = sprintf(
            __('🧪 Test de Email - %s', 'eipsi-forms'),
            get_bloginfo('name')
        );

        $site_name = get_bloginfo('name');
        $site_url = home_url();
        $date = date_i18n(get_option('date_format') . ' ' . get_option('time_format'));
        $investigator_name = get_option('eipsi_investigator_name', '');

        // Build email content
        $content = sprintf(
            "<h2>🧪 Test de Sistema de Email - EIPSI Forms</h2>
            <p>Este es un email de prueba generado automáticamente.</p>
            <hr>
            <p><strong>Sitio:</strong> %s</p>
            <p><strong>URL:</strong> %s</p>
            <p><strong>Fecha/Hora:</strong> %s</p>
            <p><strong>Destinatario:</strong> %s</p>
            <p><strong>Configuración SMTP:</strong> %s</p>",
            esc_html($site_name),
            esc_url($site_url),
            esc_html($date),
            esc_html($recipient),
            $use_smtp ? '✅ ' . esc_html($smtp_config['host'] . ':' . $smtp_config['port']) : '❌ ' . __('Inactivo (usando wp_mail)', 'eipsi-forms')
        );

        if ($use_smtp && isset($smtp_config['host'])) {
            $content .= sprintf(
                '<p><strong>Servidor SMTP:</strong> %s</p>',
                esc_html($smtp_config['host'] . ':' . $smtp_config['port'])
            );
        }

        if (!empty($investigator_name)) {
            $content .= sprintf(
                '<p><strong>Investigador:</strong> %s</p>',
                esc_html($investigator_name)
            );
        }

        $content .= '<hr>';
        $content .= sprintf(
            '<p>%s</p>',
            __('✅ Si estás viendo este mensaje, el sistema de email está funcionando correctamente.', 'eipsi-forms')
        );
        $content .= sprintf(
            '<p><small>%s %s</small></p>',
            __('Este email fue enviado usando', 'eipsi-forms'),
            self::get_email_method_label()
        );

        try {
            // Use the send_email method for consistency and logging
            $result = self::send_email(0, 0, $recipient, 'test', $subject, $content);

            if ($result) {
                return array(
                    'success' => true,
                    'message' => __('Email de prueba enviado exitosamente', 'eipsi-forms'),
                    'details' => sprintf(
                        __('Método: %s | Destinatario: %s | Fecha: %s', 'eipsi-forms'),
                        self::get_email_method_label(),
                        $recipient,
                        $date
                    )
                );
            } else {
                return array(
                    'success' => false,
                    'message' => __('Error al enviar el email de prueba', 'eipsi-forms'),
                    'details' => __('El sistema no pudo enviar el email de prueba. Revisa los logs de error.', 'eipsi-forms')
                );
            }
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => __('Excepción durante el envío', 'eipsi-forms'),
                'details' => __('Error: ', 'eipsi-forms') . $e->getMessage()
            );
        }
    }

    /**
     * Diagnóstico básico del sistema de email
     *
     * @return array {status: string, issues: array, recommendations: array}
     * @since 1.5.4
     * @access public
     */
    public static function diagnose_email_system() {
        $issues = array();
        $recommendations = array();
        
        // Check SMTP configuration
        $smtp_service = class_exists('EIPSI_SMTP_Service') ? new EIPSI_SMTP_Service() : null;
        $smtp_config = $smtp_service ? $smtp_service->get_config() : null;
        
        if (empty($smtp_config)) {
            $issues[] = 'SMTP no configurado - usando wp_mail()';
            $recommendations[] = 'Para mejor entregabilidad, configura SMTP en Configuración > SMTP';
        }
        
        // Check investigator email
        $investigator_email = get_option('eipsi_investigator_email', '');
        if (empty($investigator_email)) {
            $issues[] = 'Email del investigador no configurado';
            $recommendations[] = 'Configura el email del investigador en Configuración > SMTP';
        } elseif (!is_email($investigator_email)) {
            $issues[] = 'Email del investigador inválido';
            $recommendations[] = 'Corrige el formato del email del investigador';
        }
        
        // Check admin email
        $admin_email = get_option('admin_email', '');
        if (!is_email($admin_email)) {
            $issues[] = 'Email de administrador inválido';
            $recommendations[] = 'Corrige el email de administrador en WordPress';
        }
        
        // Check WordPress mail settings
        if (!function_exists('wp_mail')) {
            $issues[] = 'wp_mail() no disponible';
            $recommendations[] = 'Verifica que WordPress esté correctamente instalado';
        }
        
        $status = empty($issues) ? 'okay' : 'warning';
        
        return array(
            'status' => $status,
            'issues' => $issues,
            'recommendations' => $recommendations,
            'smtp_configured' => !empty($smtp_config),
            'investigator_email' => $investigator_email,
            'admin_email' => $admin_email
        );
    }

    /**
     * Get the label for the current email method being used.
     *
     * @return string Method label.
     * @since 1.5.5
     * @access private
     */
    private static function get_email_method_label() {
        $smtp_service = class_exists('EIPSI_SMTP_Service') ? new EIPSI_SMTP_Service() : null;
        $smtp_config = $smtp_service ? $smtp_service->get_config() : null;
        
        if ($smtp_config) {
            return sprintf('SMTP (%s:%d)', $smtp_config['host'], $smtp_config['port']);
        }
        
        return 'wp_mail() (WordPress default)';
    }

    /**
     * Get email deliverability statistics.
     *
     * @return array Statistics about email delivery.
     * @since 1.5.5
     * @access public
     */
    public static function get_email_deliverability_stats() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'survey_email_log';

        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
        
        if (!$table_exists) {
            return array(
                'has_data' => false,
                'message' => __('No hay datos de email disponibles aún.', 'eipsi-forms')
            );
        }

        // Overall stats
        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
        $sent = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE status = 'sent'");
        $failed = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE status = 'failed'");
        
        // Last 7 days
        $last_7_days = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE sent_at >= DATE_SUB(NOW(), INTERVAL %d DAY)",
            7
        ));

        // Last 30 days
        $last_30_days = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE sent_at >= DATE_SUB(NOW(), INTERVAL %d DAY)",
            30
        ));

        // Success rate
        $success_rate = $total > 0 ? round(($sent / $total) * 100, 1) : 0;

        // Most common error
        $common_error = $wpdb->get_var(
            "SELECT error_message FROM {$table_name} 
             WHERE status = 'failed' AND error_message IS NOT NULL 
             GROUP BY error_message ORDER BY COUNT(*) DESC LIMIT 1"
        );

        return array(
            'has_data' => $total > 0,
            'total_emails' => (int) $total,
            'sent' => (int) $sent,
            'failed' => (int) $failed,
            'success_rate' => $success_rate,
            'last_7_days' => (int) $last_7_days,
            'last_30_days' => (int) $last_30_days,
            'common_error' => $common_error ?: null,
            'health_status' => $success_rate >= 95 ? 'excellent' : ($success_rate >= 85 ? 'good' : 'needs_attention')
        );
    }
}
