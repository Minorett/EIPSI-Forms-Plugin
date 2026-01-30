<?php
/**
 * EIPSI Forms Magic Links Service
 * Handles magic link token generation, validation, and management
 *
 * @package EIPSI_Forms
 * @since 1.4.1
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Magic Links Service Class
 * Provides secure token generation and validation for survey access
 */
class EIPSI_MagicLinksService {

    /**
     * Generate a magic link token for survey access
     *
     * @param int $survey_id Survey post ID
     * @param int $participant_id Participant ID from wp_survey_participants
     * @return string|false Token plain text (UUID4) or false on failure
     */
    public static function generate_magic_link($survey_id, $participant_id) {
        global $wpdb;

        // Validate inputs
        $survey_id = intval($survey_id);
        $participant_id = intval($participant_id);

        if ($survey_id <= 0 || $participant_id <= 0) {
            error_log('[EIPSI MagicLinksService] Invalid survey_id or participant_id');
            return false;
        }

        // Check if participant exists
        $participant_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}survey_participants WHERE id = %d",
            $participant_id
        ));

        if (!$participant_exists) {
            error_log('[EIPSI MagicLinksService] Participant not found: ' . $participant_id);
            return false;
        }

        // Delete any existing unused tokens for this participant in this survey
        $deleted = $wpdb->delete(
            "{$wpdb->prefix}survey_magic_links",
            array(
                'survey_id' => $survey_id,
                'participant_id' => $participant_id,
                'used_at' => null
            ),
            array('%d', '%d', '%s')
        );

        if ($deleted !== false && $deleted > 0) {
            error_log('[EIPSI MagicLinksService] Deleted ' . $deleted . ' old unused tokens for participant ' . $participant_id);
        }

        // Generate UUID4 token
        $token_plain = wp_generate_uuid4();
        $token_hash = hash('sha256', $token_plain);

        // Calculate expiration (48 hours from now)
        $expires_at = current_time('mysql', 1); // GMT
        $expires_at = date('Y-m-d H:i:s', strtotime($expires_at . ' +48 hours'));

        // Insert into database
        $inserted = $wpdb->insert(
            "{$wpdb->prefix}survey_magic_links",
            array(
                'survey_id' => $survey_id,
                'participant_id' => $participant_id,
                'token_hash' => $token_hash,
                'token_plain' => $token_plain, // For debugging, will be removed in production
                'expires_at' => $expires_at,
                'used_at' => null,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s', '%s')
        );

        if ($inserted === false) {
            error_log('[EIPSI MagicLinksService] Failed to insert magic link: ' . $wpdb->last_error);
            return false;
        }

        $ml_id = $wpdb->insert_id;
        error_log('[EIPSI MagicLinksService] Generated magic link ID ' . $ml_id . ' for participant ' . $participant_id);

        return $token_plain;
    }

    /**
     * Validate a magic link token
     *
     * @param string $token_plain The plain token from URL
     * @return array Validation result with status and data
     */
    public static function validate_magic_link($token_plain) {
        global $wpdb;

        if (empty($token_plain)) {
            return array('valid' => false, 'reason' => 'empty_token');
        }

        // Hash the token for lookup
        $token_hash = hash('sha256', $token_plain);

        // Query the database
        $magic_link = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}survey_magic_links WHERE token_hash = %s",
            $token_hash
        ));

        if (!$magic_link) {
            return array('valid' => false, 'reason' => 'not_found');
        }

        // Check if already used
        if ($magic_link->used_at !== null) {
            return array(
                'valid' => false,
                'reason' => 'already_used',
                'used_at' => $magic_link->used_at
            );
        }

        // Check expiration
        $now = current_time('mysql', 1); // GMT
        if ($magic_link->expires_at < $now) {
            return array(
                'valid' => false,
                'reason' => 'expired',
                'expired_at' => $magic_link->expires_at
            );
        }

        // Valid token
        return array(
            'valid' => true,
            'ml_id' => $magic_link->id,
            'survey_id' => $magic_link->survey_id,
            'participant_id' => $magic_link->participant_id,
            'expires_at' => $magic_link->expires_at
        );
    }

    /**
     * Mark a magic link as used
     *
     * @param int $ml_id Magic link ID
     * @return bool Success status
     */
    public static function mark_magic_link_used($ml_id) {
        global $wpdb;

        $ml_id = intval($ml_id);

        if ($ml_id <= 0) {
            error_log('[EIPSI MagicLinksService] Invalid ml_id: ' . $ml_id);
            return false;
        }

        $updated = $wpdb->update(
            "{$wpdb->prefix}survey_magic_links",
            array('used_at' => current_time('mysql')),
            array('id' => $ml_id, 'used_at' => null),
            array('%s'),
            array('%d', '%s')
        );

        if ($updated === false) {
            error_log('[EIPSI MagicLinksService] Failed to mark magic link as used: ' . $wpdb->last_error);
            return false;
        }

        if ($updated === 0) {
            error_log('[EIPSI MagicLinksService] Magic link already used or not found: ' . $ml_id);
            return false;
        }

        error_log('[EIPSI MagicLinksService] Marked magic link ' . $ml_id . ' as used');
        return true;
    }

    /**
     * Get magic link record by token hash
     *
     * @param string $token_hash SHA256 hash of the token
     * @return object|null Magic link record or null
     */
    public static function get_magic_link_by_token($token_hash) {
        global $wpdb;

        if (empty($token_hash)) {
            return null;
        }

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}survey_magic_links WHERE token_hash = %s",
            $token_hash
        ));
    }

    /**
     * Clean up expired magic links (optional maintenance function)
     *
     * @return int Number of deleted rows
     */
    public static function cleanup_expired_magic_links() {
        global $wpdb;

        $now = current_time('mysql', 1); // GMT

        $deleted = $wpdb->delete(
            "{$wpdb->prefix}survey_magic_links",
            array(
                'expires_at' => $now,
                'used_at' => null
            ),
            array('%s', '%s')
        );

        if ($deleted > 0) {
            error_log('[EIPSI MagicLinksService] Cleaned up ' . $deleted . ' expired magic links');
        }

        return $deleted;
    }
}
