<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * EIPSI Forms - Partial Responses Manager
 * Handles save & continue functionality
 * 
 * @since 1.3.0
 */
class EIPSI_Partial_Responses {
    
    /**
     * Create partial responses table
     */
    public static function create_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'eipsi_partial_responses';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            form_id varchar(64) NOT NULL,
            participant_id varchar(255) NOT NULL,
            session_id varchar(255) NOT NULL,
            page_index int(11) DEFAULT 1,
            responses_json longtext DEFAULT NULL,
            completed tinyint(1) DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_session (form_id, participant_id, session_id),
            KEY updated_at (updated_at),
            KEY completed (completed)
        ) $charset_collate;";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
        
        return $wpdb->last_error === '';
    }
    
    /**
     * Save partial response
     * 
     * @param string $form_id Form identifier
     * @param string $participant_id Participant identifier
     * @param string $session_id Session identifier
     * @param int $page_index Current page index
     * @param array $responses Form responses
     * @return array Result with success status
     */
    public static function save($form_id, $participant_id, $session_id, $page_index, $responses) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'eipsi_partial_responses';
        
        // Sanitize inputs
        $form_id = sanitize_text_field($form_id);
        $participant_id = sanitize_text_field($participant_id);
        $session_id = sanitize_text_field($session_id);
        $page_index = intval($page_index);
        
        // Validate
        if (empty($form_id) || empty($participant_id) || empty($session_id)) {
            return array(
                'success' => false,
                'error' => 'Missing required fields'
            );
        }
        
        $responses_json = wp_json_encode($responses);
        
        // Check if record exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $table_name 
            WHERE form_id = %s AND participant_id = %s AND session_id = %s AND completed = 0",
            $form_id,
            $participant_id,
            $session_id
        ));
        
        $now = current_time('mysql');
        
        if ($existing) {
            // Update existing record
            $result = $wpdb->update(
                $table_name,
                array(
                    'page_index' => $page_index,
                    'responses_json' => $responses_json,
                    'updated_at' => $now
                ),
                array(
                    'id' => $existing->id
                ),
                array('%d', '%s', '%s'),
                array('%d')
            );
            
            if ($result === false) {
                return array(
                    'success' => false,
                    'error' => $wpdb->last_error
                );
            }
            
            return array(
                'success' => true,
                'action' => 'updated',
                'id' => $existing->id
            );
        } else {
            // Insert new record
            $result = $wpdb->insert(
                $table_name,
                array(
                    'form_id' => $form_id,
                    'participant_id' => $participant_id,
                    'session_id' => $session_id,
                    'page_index' => $page_index,
                    'responses_json' => $responses_json,
                    'completed' => 0,
                    'created_at' => $now,
                    'updated_at' => $now
                ),
                array('%s', '%s', '%s', '%d', '%s', '%d', '%s', '%s')
            );
            
            if ($result === false) {
                return array(
                    'success' => false,
                    'error' => $wpdb->last_error
                );
            }
            
            return array(
                'success' => true,
                'action' => 'created',
                'id' => $wpdb->insert_id
            );
        }
    }
    
    /**
     * Load partial response
     * 
     * @param string $form_id Form identifier
     * @param string $participant_id Participant identifier
     * @param string $session_id Session identifier
     * @return array|null Partial response data or null
     */
    public static function load($form_id, $participant_id, $session_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'eipsi_partial_responses';
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name 
            WHERE form_id = %s AND participant_id = %s AND session_id = %s AND completed = 0
            ORDER BY updated_at DESC
            LIMIT 1",
            sanitize_text_field($form_id),
            sanitize_text_field($participant_id),
            sanitize_text_field($session_id)
        ));
        
        if (!$result) {
            return null;
        }
        
        return array(
            'id' => $result->id,
            'form_id' => $result->form_id,
            'participant_id' => $result->participant_id,
            'session_id' => $result->session_id,
            'page_index' => intval($result->page_index),
            'responses' => json_decode($result->responses_json, true),
            'created_at' => $result->created_at,
            'updated_at' => $result->updated_at
        );
    }
    
    /**
     * Mark session as completed
     * 
     * @param string $form_id Form identifier
     * @param string $participant_id Participant identifier
     * @param string $session_id Session identifier
     * @return bool Success status
     */
    public static function mark_completed($form_id, $participant_id, $session_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'eipsi_partial_responses';
        
        $result = $wpdb->update(
            $table_name,
            array(
                'completed' => 1,
                'updated_at' => current_time('mysql')
            ),
            array(
                'form_id' => sanitize_text_field($form_id),
                'participant_id' => sanitize_text_field($participant_id),
                'session_id' => sanitize_text_field($session_id)
            ),
            array('%d', '%s'),
            array('%s', '%s', '%s')
        );
        
        return $result !== false;
    }
    
    /**
     * Discard partial response
     * 
     * @param string $form_id Form identifier
     * @param string $participant_id Participant identifier
     * @param string $session_id Session identifier
     * @return bool Success status
     */
    public static function discard($form_id, $participant_id, $session_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'eipsi_partial_responses';
        
        $result = $wpdb->delete(
            $table_name,
            array(
                'form_id' => sanitize_text_field($form_id),
                'participant_id' => sanitize_text_field($participant_id),
                'session_id' => sanitize_text_field($session_id)
            ),
            array('%s', '%s', '%s')
        );
        
        return $result !== false;
    }
    
    /**
     * Clean up old partial responses (> 30 days)
     * 
     * @return int Number of deleted records
     */
    public static function cleanup_old_responses() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'eipsi_partial_responses';
        
        $result = $wpdb->query("
            DELETE FROM $table_name 
            WHERE updated_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        return intval($result);
    }
}
