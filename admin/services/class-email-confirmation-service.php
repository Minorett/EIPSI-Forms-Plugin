<?php
/**
 * EIPSI_Email_Confirmation_Service
 *
 * Gestiona la confirmación de email (double opt-in):
 * - Generación de tokens de confirmación
 * - Validación de tokens
 * - Limpieza de tokens expirados
 *
 * @package EIPSI_Forms
 * @subpackage Services
 * @version 1.0.0
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class EIPSI_Email_Confirmation_Service {

    /**
     * Generate confirmation token and store it.
     *
     * @param int    $survey_id Survey ID.
     * @param int    $participant_id Participant ID.
     * @param string $email Email address to confirm.
     * @return array {success: bool, token: string|null, error: string|null}
     * @since 1.5.0
     * @access public
     */
    public static function generate_confirmation_token($survey_id, $participant_id, $email) {
        global $wpdb;

        // Load config
        require_once plugin_dir_path(dirname(__FILE__)) . 'config/longitudinal-config.php';

        try {
            // Generate secure token
            $token_plain = wp_generate_password(EIPSI_CONFIRMATION_TOKEN_LENGTH, true, true);
            $token_hash = wp_hash($token_plain);

            // Calculate expiry
            $expires_at = gmdate('Y-m-d H:i:s', current_time('timestamp', true) + (EIPSI_CONFIRMATION_TOKEN_EXPIRY_HOURS * HOUR_IN_SECONDS));

            $table_name = $wpdb->prefix . 'survey_email_confirmations';

            // Delete any existing pending confirmations for this participant/email
            $wpdb->delete(
                $table_name,
                array(
                    'participant_id' => $participant_id,
                    'email' => $email
                ),
                array('%d', '%s')
            );

            // Insert new confirmation record
            $result = $wpdb->insert(
                $table_name,
                array(
                    'survey_id' => $survey_id,
                    'participant_id' => $participant_id,
                    'email' => $email,
                    'token_hash' => $token_hash,
                    'token_plain' => $token_plain,
                    'expires_at' => $expires_at,
                    'created_at' => current_time('mysql')
                ),
                array('%d', '%d', '%s', '%s', '%s', '%s', '%s')
            );

            if ($result === false) {
                error_log('EIPSI Email Confirmation: Failed to insert token: ' . $wpdb->last_error);
                return array(
                    'success' => false,
                    'token' => null,
                    'error' => 'db_error'
                );
            }

            return array(
                'success' => true,
                'token' => $token_plain,
                'expires_at' => $expires_at,
                'error' => null
            );

        } catch (Exception $e) {
            error_log('EIPSI Email Confirmation exception: ' . $e->getMessage());
            return array(
                'success' => false,
                'token' => null,
                'error' => 'exception'
            );
        }
    }

    /**
     * Validate confirmation token.
     *
     * @param string $token Plain token.
     * @param string $email Email to validate against.
     * @return array {success: bool, participant_id: int|null, error: string|null}
     * @since 1.5.0
     * @access public
     */
    public static function validate_confirmation_token($token, $email) {
        global $wpdb;

        if (empty($token) || empty($email)) {
            return array(
                'success' => false,
                'participant_id' => null,
                'error' => 'invalid_parameters'
            );
        }

        $token_hash = wp_hash($token);
        $table_name = $wpdb->prefix . 'survey_email_confirmations';

        // Find confirmation record
        $confirmation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} 
             WHERE token_hash = %s AND email = %s AND confirmed_at IS NULL
             LIMIT 1",
            $token_hash,
            $email
        ));

        if (!$confirmation) {
            return array(
                'success' => false,
                'participant_id' => null,
                'error' => 'invalid_token'
            );
        }

        // Check if expired
        if (strtotime($confirmation->expires_at) < current_time('timestamp', true)) {
            return array(
                'success' => false,
                'participant_id' => null,
                'error' => 'token_expired'
            );
        }

        return array(
            'success' => true,
            'participant_id' => (int) $confirmation->participant_id,
            'survey_id' => (int) $confirmation->survey_id,
            'email' => $confirmation->email,
            'error' => null
        );
    }

    /**
     * Mark confirmation as completed.
     *
     * @param string $token Plain token.
     * @param string $email Email confirmed.
     * @return array {success: bool, error: string|null}
     * @since 1.5.0
     * @access public
     */
    public static function mark_confirmed($token, $email) {
        global $wpdb;

        $token_hash = wp_hash($token);
        $table_name = $wpdb->prefix . 'survey_email_confirmations';

        $result = $wpdb->update(
            $table_name,
            array('confirmed_at' => current_time('mysql')),
            array('token_hash' => $token_hash, 'email' => $email),
            array('%s'),
            array('%s', '%s')
        );

        if ($result === false) {
            error_log('EIPSI Email Confirmation: Failed to mark confirmed: ' . $wpdb->last_error);
            return array(
                'success' => false,
                'error' => 'db_error'
            );
        }

        return array(
            'success' => true,
            'error' => null
        );
    }

    /**
     * Generate confirmation URL.
     *
     * @param string $token Plain token.
     * @param string $email Email to confirm.
     * @return string Confirmation URL.
     * @since 1.5.0
     * @access public
     */
    public static function generate_confirmation_url($token, $email) {
        require_once plugin_dir_path(dirname(__FILE__)) . 'config/longitudinal-config.php';
        
        return add_query_arg(
            array(
                EIPSI_CONFIRMATION_URL_PARAM => $token,
                'email' => urlencode($email)
            ),
            site_url('/')
        );
    }

    /**
     * Get participant confirmation status.
     *
     * @param int $participant_id Participant ID.
     * @return bool True if confirmed.
     * @since 1.5.0
     * @access public
     */
    public static function is_confirmed($participant_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'survey_email_confirmations';

        $confirmation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} 
             WHERE participant_id = %d AND confirmed_at IS NOT NULL
             ORDER BY created_at DESC
             LIMIT 1",
            $participant_id
        ));

        return !empty($confirmation);
    }

    /**
     * Get pending confirmation for participant.
     *
     * @param int $participant_id Participant ID.
     * @return object|null Confirmation record.
     * @since 1.5.0
     * @access public
     */
    public static function get_pending_confirmation($participant_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'survey_email_confirmations';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} 
             WHERE participant_id = %d AND confirmed_at IS NULL
             ORDER BY created_at DESC
             LIMIT 1",
            $participant_id
        ));
    }

    /**
     * Resend confirmation email.
     *
     * @param int $participant_id Participant ID.
     * @return array {success: bool, error: string|null}
     * @since 1.5.0
     * @access public
     */
    public static function resend_confirmation_email($participant_id) {
        global $wpdb;

        // Get participant
        $participant = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}survey_participants WHERE id = %d",
            $participant_id
        ));

        if (!$participant) {
            return array(
                'success' => false,
                'error' => 'participant_not_found'
            );
        }

        // Check if already confirmed
        if (self::is_confirmed($participant_id)) {
            return array(
                'success' => false,
                'error' => 'already_confirmed'
            );
        }

        // Delete existing pending confirmations
        $table_name = $wpdb->prefix . 'survey_email_confirmations';
        $wpdb->delete(
            $table_name,
            array('participant_id' => $participant_id, 'confirmed_at' => null),
            array('%d', '%s')
        );

        // Generate new token
        $result = self::generate_confirmation_token($participant->survey_id, $participant_id, $participant->email);

        if (!$result['success']) {
            return array(
                'success' => false,
                'error' => $result['error']
            );
        }

        // Send confirmation email
        if (!class_exists('EIPSI_Email_Service')) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'services/class-email-service.php';
        }

        $email_result = EIPSI_Email_Service::send_confirmation_email(
            $participant->survey_id,
            $participant_id,
            $result['token']
        );

        if (!$email_result) {
            return array(
                'success' => false,
                'error' => 'email_send_failed'
            );
        }

        return array(
            'success' => true,
            'error' => null
        );
    }

    /**
     * Clean up expired confirmations and unconfirmed participants.
     *
     * This should be called by a cron job.
     *
     * @return array {deleted_confirmations: int, deleted_participants: int}
     * @since 1.5.0
     * @access public
     */
    public static function cleanup_expired_confirmations() {
        global $wpdb;

        require_once plugin_dir_path(dirname(__FILE__)) . 'config/longitudinal-config.php';

        $confirmations_table = $wpdb->prefix . 'survey_email_confirmations';
        $participants_table = $wpdb->prefix . 'survey_participants';

        // Delete expired confirmations
        $expired_confirmations = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$confirmations_table} WHERE expires_at < %s",
            current_time('mysql')
        ));

        // Find unconfirmed participants older than retention period
        $retention_cutoff = gmdate('Y-m-d H:i:s', current_time('timestamp', true) - (EIPSI_UNCONFIRMED_PARTICIPANT_RETENTION_HOURS * HOUR_IN_SECONDS));

        // Get unconfirmed participant IDs (those created before cutoff that have no confirmed record)
        $unconfirmed_participants = $wpdb->get_results($wpdb->prepare(
            "SELECT p.id, p.email 
             FROM {$participants_table} p
             LEFT JOIN {$confirmations_table} c ON p.id = c.participant_id AND c.confirmed_at IS NOT NULL
             WHERE p.created_at < %s 
               AND p.is_active = 0
               AND c.id IS NULL",
            $retention_cutoff
        ));

        $deleted_participants = 0;
        foreach ($unconfirmed_participants as $participant) {
            // Only delete if there's a pending confirmation (unconfirmed)
            $has_pending = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$confirmations_table} 
                 WHERE participant_id = %d AND confirmed_at IS NULL",
                $participant->id
            ));

            if ($has_pending > 0) {
                // Delete confirmation first
                $wpdb->delete(
                    $confirmations_table,
                    array('participant_id' => $participant->id),
                    array('%d')
                );

                // Delete participant
                $wpdb->delete(
                    $participants_table,
                    array('id' => $participant->id),
                    array('%d')
                );

                $deleted_participants++;
            }
        }

        return array(
            'deleted_confirmations' => $expired_confirmations,
            'deleted_participants' => $deleted_participants
        );
    }

    /**
     * Check if double opt-in is enabled.
     *
     * @return bool
     * @since 1.5.0
     * @access public
     */
    public static function is_enabled() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'config/longitudinal-config.php';
        return EIPSI_DOUBLE_OPTIN_ENABLED;
    }
}
