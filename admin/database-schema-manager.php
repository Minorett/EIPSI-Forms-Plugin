<?php
if (!defined('ABSPATH')) {
    exit;
}

// Load database repair utilities for fixing corrupt indexes
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/database-schema-repair.php';

if ( ! class_exists( 'WP_Error' ) ) {
    require_once ABSPATH . 'wp-includes/class-wp-error.php';
}

/**
 * EIPSI Forms Database Schema Manager
 * Handles automatic table creation and schema synchronization for local and external databases.
 * Unified source of truth for all plugin tables.
 * 
 * @package EIPSI_Forms
 * @since 1.2.1
 * @version 2.6.0
 */
class EIPSI_Database_Schema_Manager {

    /**
     * Verify and synchronize schema for both local and external databases.
     * Consolidates creation and updates into a single managed process.
     * 
     * @param mysqli|null $mysqli Optional external database connection
     * @return array Result with success status and details
     */
    public static function verify_and_sync_schema( $mysqli = null ) {
        // Step 0: Fix corrupt indexes before any schema operation (Hotfix v1.2.2/v2.0.1 dogma)
        if ( function_exists( 'eipsi_repair_database_schema' ) ) {
            eipsi_repair_database_schema();
        }

        $results = array(
            'success' => true,
            'tables'  => array(),
            'errors'  => array(),
        );

        $table_order = self::get_table_creation_order();
        
        // Sort tables by level (dependencies first)
        uasort( $table_order, function( $a, $b ) {
            return $a['level'] - $b['level'];
        });

        foreach ( $table_order as $table_slug => $info ) {
            if ( $mysqli ) {
                $sync_res = self::sync_external_table( $table_slug, $mysqli );
            } else {
                $sync_res = self::sync_local_table( $table_slug );
            }
            
            $results['tables'][$table_slug] = $sync_res;
            
            if ( ! $sync_res['success'] ) {
                $results['success'] = false;
                $results['errors'][] = "Table {$table_slug}: " . ( $sync_res['error'] ?? 'Unknown error' );
            }
        }

        // Phase 2: Foreign Keys (Local only)
        if ( ! $mysqli ) {
            $fk_res = self::add_foreign_keys_phase2();
            $results['fk_phase2'] = $fk_res;
        }

        // Final local maintenance
        if ( ! $mysqli ) {
            self::fix_collations();
        }

        update_option( 'eipsi_schema_last_verified', current_time( 'mysql' ) );
        update_option( 'eipsi_schema_last_sync_result', $results );

        return $results;
    }

    /**
     * Table creation order respecting Foreign Key dependencies.
     * 
     * @return array
     */
    private static function get_table_creation_order() {
        return array(
            // Level 0: Root tables (no dependencies)
            'vas_form_results'                => array( 'level' => 0 ),
            'vas_form_events'                 => array( 'level' => 0 ),
            'eipsi_randomization_configs'     => array( 'level' => 0 ),
            'survey_studies'                  => array( 'level' => 0 ),
            'survey_participants'             => array( 'level' => 0 ),
            'eipsi_longitudinal_pools'        => array( 'level' => 0 ),
            'survey_audit_log'                => array( 'level' => 0 ),
            'eipsi_device_data'               => array( 'level' => 0 ),
            'eipsi_emergency_submissions'     => array( 'level' => 0 ),
            'survey_nudge_jobs'               => array( 'level' => 0 ),
            'eipsi_partial_responses'         => array( 'level' => 0 ),
            'survey_cron_log'                 => array( 'level' => 0 ),
            'survey_data_requests'            => array( 'level' => 0 ),
            'eipsi_manual_overrides'          => array( 'level' => 0 ),

            // Level 1: Dependencies on level 0
            'survey_sessions'                 => array( 'level' => 1 ),
            'survey_waves'                    => array( 'level' => 1 ),
            'eipsi_pool_email_log'            => array( 'level' => 1 ),
            'survey_magic_links'              => array( 'level' => 1 ),
            'survey_email_log'                => array( 'level' => 1 ),
            'survey_email_confirmations'      => array( 'level' => 1 ),
            'survey_participant_access_log'   => array( 'level' => 1 ),
            'eipsi_randomization_assignments' => array( 'level' => 1 ),

            // Level 2: Dependencies on level 1
            'survey_assignments'              => array( 'level' => 2 ),
            'eipsi_pool_assignments'          => array( 'level' => 2 ),
            'eipsi_pool_analytics'            => array( 'level' => 2 ),
        );
    }

    /**
     * Unified source of truth for all plugin table definitions.
     * 
     * @return array
     */
    private static function get_schema_map() {
        return array(
            'vas_form_results' => array(
                'columns' => array(
                    'id'                 => 'bigint(20) unsigned NOT NULL AUTO_INCREMENT',
                    'form_id'            => 'varchar(20) DEFAULT NULL',
                    'participant_id'     => 'varchar(20) DEFAULT NULL',
                    'survey_id'          => 'INT(11) DEFAULT NULL',
                    'wave_index'         => 'INT(11) DEFAULT NULL',
                    'session_id'         => 'varchar(255) DEFAULT NULL',
                    'user_fingerprint'   => 'varchar(255) DEFAULT NULL',
                    'participant'        => 'varchar(255) DEFAULT NULL',
                    'interaction'        => 'varchar(255) DEFAULT NULL',
                    'form_name'          => 'varchar(255) NOT NULL',
                    'created_at'         => 'datetime NOT NULL',
                    'submitted_at'       => 'datetime DEFAULT NULL',
                    'device'             => 'varchar(100) DEFAULT NULL',
                    'browser'            => 'varchar(100) DEFAULT NULL',
                    'os'                  => 'varchar(100) DEFAULT NULL',
                    'screen_width'       => 'int(11) DEFAULT NULL',
                    'duration'           => 'int(11) DEFAULT NULL',
                    'duration_seconds'   => 'decimal(8,3) DEFAULT NULL',
                    'start_timestamp_ms' => 'bigint(20) DEFAULT NULL',
                    'end_timestamp_ms'   => 'bigint(20) DEFAULT NULL',
                    'ip_address'         => 'varchar(45) DEFAULT NULL',
                    'metadata'           => 'LONGTEXT DEFAULT NULL',
                    'status'             => "enum('pending','submitted','error') DEFAULT 'submitted'",
                    'form_responses'     => 'longtext DEFAULT NULL',
                    'rct_assigned_variant' => 'varchar(100) DEFAULT NULL',
                    'rct_randomization_id' => 'varchar(100) DEFAULT NULL',
                ),
                'indexes' => array(
                    'PRIMARY KEY'                => '(id)',
                    'KEY  form_name'             => '(form_name)',
                    'KEY  created_at'            => '(created_at)',
                    'KEY  form_id'               => '(form_id)',
                    'KEY  participant_id'        => '(participant_id)',
                    'KEY  session_id'            => '(session_id)',
                    'KEY  ip_address'            => '(ip_address)',
                    'KEY  submitted_at'          => '(submitted_at)',
                    'KEY  participant_survey_wave' => '(participant_id, survey_id, wave_index)',
                    'KEY  form_participant'      => '(form_id, participant_id)',
                ),
            ),
            'vas_form_events' => array(
                'columns' => array(
                    'id'          => 'bigint(20) unsigned NOT NULL AUTO_INCREMENT',
                    'form_id'     => 'varchar(255) NOT NULL DEFAULT \'\'',
                    'session_id'  => 'varchar(255) NOT NULL',
                    'event_type'  => 'varchar(50) NOT NULL',
                    'page_number' => 'int(11) DEFAULT NULL',
                    'metadata'    => 'text DEFAULT NULL',
                    'user_agent'  => 'text DEFAULT NULL',
                    'created_at'  => 'datetime NOT NULL',
                ),
                'indexes' => array(
                    'PRIMARY KEY'        => '(id)',
                    'KEY  form_id'       => '(form_id)',
                    'KEY  session_id'    => '(session_id)',
                    'KEY  event_type'    => '(event_type)',
                    'KEY  created_at'    => '(created_at)',
                    'KEY  form_session'  => '(form_id, session_id)',
                ),
            ),
            'eipsi_randomization_configs' => array(
                'columns' => array(
                    'id'                 => 'bigint(20) unsigned NOT NULL AUTO_INCREMENT',
                    'randomization_id'   => 'varchar(255) NOT NULL',
                    'formularios'        => 'LONGTEXT NOT NULL',
                    'probabilidades'     => 'LONGTEXT',
                    'method'             => "varchar(20) DEFAULT 'seeded'",
                    'manual_assignments' => 'LONGTEXT',
                    'show_instructions'  => 'tinyint(1) DEFAULT 0',
                    'created_at'         => 'datetime DEFAULT CURRENT_TIMESTAMP',
                    'updated_at'         => 'datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
                ),
                'indexes' => array(
                    'PRIMARY KEY'                => '(id)',
                    'UNIQUE KEY  randomization_id' => '(randomization_id)',
                    'KEY  method'                => '(method)',
                    'KEY  created_at'            => '(created_at)',
                ),
            ),
            'eipsi_randomization_assignments' => array(
                'columns' => array(
                    'id'                => 'bigint(20) unsigned NOT NULL AUTO_INCREMENT',
                    'randomization_id'  => 'varchar(255) NOT NULL',
                    'config_id'         => 'varchar(255) NOT NULL',
                    'user_fingerprint'  => 'varchar(255) NOT NULL',
                    'assigned_form_id'  => 'bigint(20) unsigned NOT NULL',
                    'assigned_at'       => 'datetime DEFAULT CURRENT_TIMESTAMP',
                    'last_access'       => 'datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
                    'access_count'      => 'int(11) DEFAULT 1',
                ),
                'indexes' => array(
                    'PRIMARY KEY'             => '(id)',
                    'UNIQUE KEY  unique_assignment' => '(randomization_id, config_id, user_fingerprint)',
                    'KEY  randomization_id'   => '(randomization_id)',
                    'KEY  config_id'          => '(config_id)',
                    'KEY  user_fingerprint'   => '(user_fingerprint)',
                    'KEY  assigned_form_id'   => '(assigned_form_id)',
                    'KEY  assigned_at'        => '(assigned_at)',
                ),
            ),
            'survey_studies' => array(
                'columns' => array(
                    'id'                        => 'BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT',
                    'study_code'                => 'VARCHAR(50) NOT NULL',
                    'study_name'                => 'VARCHAR(255) NOT NULL',
                    'description'               => 'TEXT',
                    'principal_investigator_id' => 'BIGINT(20) UNSIGNED',
                    'status'                    => "ENUM('active', 'completed', 'paused', 'archived') DEFAULT 'active'",
                    'config'                    => 'JSON',
                    'created_at'                => 'DATETIME NOT NULL',
                    'updated_at'                => 'DATETIME NOT NULL',
                ),
                'indexes' => array(
                    'PRIMARY KEY'                => '(id)',
                    'UNIQUE KEY  unique_study_code' => '(study_code)',
                    'KEY  status'                => '(status)',
                    'KEY  principal_investigator_id' => '(principal_investigator_id)',
                    'KEY  created_at'            => '(created_at)',
                ),
            ),
            'survey_participants' => array(
                'columns' => array(
                    'id'            => 'BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT',
                    'survey_id'     => 'INT(11)',
                    'email'         => 'VARCHAR(255) NOT NULL',
                    'password_hash' => 'VARCHAR(255)',
                    'first_name'    => 'VARCHAR(100)',
                    'last_name'     => 'VARCHAR(100)',
                    'created_at'    => 'DATETIME NOT NULL',
                    'last_login_at' => 'DATETIME',
                    'is_active'     => 'TINYINT(1) DEFAULT 1',
                    'updated_at'    => 'DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
                ),
                'indexes' => array(
                    'PRIMARY KEY'                => '(id)',
                    'UNIQUE KEY  unique_survey_email' => '(survey_id, email)',
                    'KEY  survey_id'             => '(survey_id)',
                    'KEY  idx_participant_active' => '(is_active)',
                    'KEY  idx_email'             => '(email)',
                    'KEY  idx_created_at'        => '(created_at)',
                    'KEY  idx_survey_email'      => '(survey_id, email)',
                ),
            ),
            'survey_sessions' => array(
                'columns' => array(
                    'id'             => 'BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT',
                    'token'          => 'VARCHAR(255) NOT NULL',
                    'participant_id' => 'BIGINT(20) UNSIGNED NOT NULL',
                    'survey_id'      => 'INT(11)',
                    'ip_address'     => 'VARCHAR(45)',
                    'user_agent'     => 'VARCHAR(500)',
                    'expires_at'     => 'DATETIME NOT NULL',
                    'created_at'     => 'DATETIME NOT NULL',
                ),
                'indexes' => array(
                    'PRIMARY KEY'          => '(id)',
                    'UNIQUE KEY  unique_token' => '(token)',
                    'KEY  participant_id'  => '(participant_id)',
                    'KEY  expires_at'      => '(expires_at)',
                ),
            ),
            'survey_waves' => array(
                'columns' => array(
                    'id'                          => 'BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT',
                    'study_id'                    => 'BIGINT(20) UNSIGNED NOT NULL',
                    'wave_index'                  => 'INT NOT NULL',
                    'name'                        => 'VARCHAR(255) NOT NULL',
                    'form_id'                     => 'BIGINT(20) UNSIGNED NOT NULL',
                    'start_date'                  => 'DATETIME NULL',
                    'due_date'                    => 'DATETIME NULL',
                    'interval_days'               => 'INT(11) DEFAULT 7',
                    'time_unit'                   => "VARCHAR(10) DEFAULT 'days'",
                    'reminder_days'               => 'INT DEFAULT 3',
                    'retry_enabled'               => 'TINYINT(1) DEFAULT 1',
                    'retry_days'                  => 'INT DEFAULT 7',
                    'max_retries'                 => 'INT DEFAULT 3',
                    'has_time_limit'              => 'TINYINT(1) DEFAULT 0',
                    'completion_time_limit'       => 'INT DEFAULT NULL',
                    'status'                      => "ENUM('draft', 'active', 'completed', 'paused') DEFAULT 'draft'",
                    'is_mandatory'                => 'TINYINT(1) DEFAULT 1',
                    'follow_up_reminders_enabled' => 'TINYINT(1) DEFAULT 1',
                    'nudge_config'                => 'TEXT DEFAULT NULL',
                    'created_at'                  => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
                    'updated_at'                  => 'DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
                ),
                'indexes' => array(
                    'PRIMARY KEY'           => '(id)',
                    'KEY  idx_study_id'     => '(study_id)',
                    'KEY  idx_status'       => '(status)',
                    'KEY  idx_due_date'     => '(due_date)',
                    'UNIQUE KEY  uk_study_index' => '(study_id, wave_index)',
                ),
            ),
            'survey_assignments' => array(
                'columns' => array(
                    'id'                 => 'BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT',
                    'study_id'           => 'BIGINT(20) UNSIGNED NOT NULL',
                    'wave_id'            => 'BIGINT(20) UNSIGNED NOT NULL',
                    'participant_id'     => 'BIGINT(20) UNSIGNED NOT NULL',
                    'status'             => "ENUM('pending', 'in_progress', 'submitted', 'skipped', 'expired') DEFAULT 'pending'",
                    'assigned_at'        => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
                    'first_viewed_at'    => 'DATETIME NULL',
                    'submitted_at'       => 'DATETIME NULL',
                    'reminder_count'     => 'INT DEFAULT 0',
                    'last_nudge_sent_at' => "DATETIME NULL COMMENT 'Timestamp real del último nudge enviado exitosamente'",
                    'last_reminder_sent' => 'DATETIME NULL',
                    'retry_count'        => 'INT DEFAULT 0',
                    'last_retry_sent'    => 'DATETIME NULL',
                    'due_at'             => 'DATETIME NULL',
                    'available_at'       => 'DATETIME NULL',
                    'created_at'         => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
                    'updated_at'         => 'DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
                ),
                'indexes' => array(
                    'PRIMARY KEY'             => '(id)',
                    'KEY  idx_study_id'       => '(study_id)',
                    'KEY  idx_wave_id'        => '(wave_id)',
                    'KEY  idx_participant_id' => '(participant_id)',
                    'KEY  idx_status'         => '(status)',
                    'KEY  idx_submitted_at'   => '(submitted_at)',
                    'KEY  idx_due_at'         => '(due_at)',
                    'KEY  idx_available_at'   => '(available_at)',
                    'UNIQUE KEY  uk_wave_participant' => '(wave_id, participant_id)',
                ),
            ),
            'survey_magic_links' => array(
                'columns' => array(
                    'id'             => 'BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT',
                    'survey_id'      => 'BIGINT(20) UNSIGNED NOT NULL',
                    'participant_id' => 'BIGINT(20) UNSIGNED NOT NULL',
                    'token_hash'     => 'VARCHAR(255) NOT NULL',
                    'token_plain'    => 'VARCHAR(36)',
                    'expires_at'     => 'DATETIME NOT NULL',
                    'used_at'        => 'DATETIME NULL',
                    'created_at'     => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
                ),
                'indexes' => array(
                    'PRIMARY KEY'               => '(id)',
                    'KEY  idx_survey_participant' => '(survey_id, participant_id)',
                    'KEY  idx_token_hash'       => '(token_hash)',
                    'KEY  idx_expires_used'     => '(expires_at, used_at)',
                    'UNIQUE KEY  uk_token_hash' => '(token_hash)',
                ),
            ),
            'survey_email_log' => array(
                'columns' => array(
                    'id'              => 'BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT',
                    'participant_id'  => 'BIGINT(20) UNSIGNED NOT NULL',
                    'survey_id'       => 'INT(11)',
                    'email_type'      => "ENUM('reminder', 'wave_availability', 'nudge_1', 'nudge_2', 'nudge_3', 'nudge_4', 'welcome', 'confirmation', 'magic_link', 'recovery', 'custom', 'audit_log') DEFAULT 'custom'",
                    'wave_id'         => 'BIGINT(20) UNSIGNED',
                    'recipient_email' => 'VARCHAR(255)',
                    'subject'         => 'VARCHAR(500)',
                    'content'         => 'TEXT',
                    'sent_at'         => 'DATETIME NOT NULL',
                    'status'          => "ENUM('sent', 'failed', 'bounced', 'audit') DEFAULT 'sent'",
                    'error_message'   => 'TEXT',
                    'magic_link_used' => 'TINYINT(1) DEFAULT 0',
                    'metadata'        => 'JSON',
                    'created_at'      => 'DATETIME',
                ),
                'indexes' => array(
                    'PRIMARY KEY'        => '(id)',
                    'KEY  participant_id' => '(participant_id)',
                    'KEY  sent_at'        => '(sent_at)',
                    'KEY  status'         => '(status)',
                ),
            ),
            'survey_audit_log' => array(
                'columns' => array(
                    'id'             => 'BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT',
                    'survey_id'      => 'BIGINT(20) UNSIGNED NOT NULL',
                    'participant_id' => 'BIGINT(20) UNSIGNED NULL',
                    'action'         => 'VARCHAR(100) NOT NULL',
                    'actor_type'     => "ENUM('admin', 'system') DEFAULT 'system'",
                    'actor_id'       => 'BIGINT(20) UNSIGNED NULL',
                    'actor_username' => 'VARCHAR(255) NULL',
                    'ip_address'     => 'VARCHAR(45) NULL',
                    'metadata'       => 'JSON NULL',
                    'created_at'     => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
                ),
                'indexes' => array(
                    'PRIMARY KEY'            => '(id)',
                    'KEY  idx_survey_action'  => '(survey_id, action)',
                    'KEY  idx_survey_created' => '(survey_id, created_at)',
                    'KEY  idx_action_created' => '(action, created_at)',
                    'KEY  idx_participant_id' => '(participant_id)',
                ),
            ),
            'survey_email_confirmations' => array(
                'columns' => array(
                    'id'             => 'BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT',
                    'survey_id'      => 'INT(11) NOT NULL',
                    'participant_id' => 'BIGINT(20) UNSIGNED NOT NULL',
                    'email'          => 'VARCHAR(255) NOT NULL',
                    'token_hash'     => 'VARCHAR(64) NOT NULL',
                    'token_plain'    => 'VARCHAR(64) NOT NULL',
                    'expires_at'     => 'DATETIME NOT NULL',
                    'confirmed_at'   => 'DATETIME NULL',
                    'created_at'     => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
                ),
                'indexes' => array(
                    'PRIMARY KEY'               => '(id)',
                    'KEY  idx_participant'      => '(participant_id)',
                    'KEY  idx_email'            => '(email)',
                    'KEY  idx_token_hash'       => '(token_hash)',
                    'KEY  idx_expires_at'       => '(expires_at)',
                    'KEY  idx_confirmed_at'     => '(confirmed_at)',
                    'UNIQUE KEY  idx_participant_email' => '(participant_id, email)',
                ),
            ),
            'eipsi_longitudinal_pools' => array(
                'columns' => array(
                    'id'               => 'BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT',
                    'pool_name'        => 'VARCHAR(255) NOT NULL',
                    'pool_description' => 'TEXT',
                    'studies'          => 'JSON',
                    'probabilities'    => 'JSON',
                    'method'           => "ENUM('seeded', 'pure-random') DEFAULT 'seeded'",
                    'status'           => "ENUM('active', 'inactive') DEFAULT 'active'",
                    'config'           => 'JSON',
                    'page_id'          => 'BIGINT(20) UNSIGNED DEFAULT NULL',
                    'created_at'       => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
                    'updated_at'       => 'DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
                ),
                'indexes' => array(
                    'PRIMARY KEY'      => '(id)',
                    'KEY  idx_status'  => '(status)',
                    'KEY  idx_method'  => '(method)',
                    'KEY  idx_created_at' => '(created_at)',
                ),
            ),
            'eipsi_pool_assignments' => array(
                'columns' => array(
                    'id'                 => 'BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT',
                    'pool_id'            => 'BIGINT(20) UNSIGNED NOT NULL',
                    'participant_id'     => 'VARCHAR(255) NOT NULL',
                    'study_id'           => 'BIGINT(20) UNSIGNED NOT NULL',
                    'assigned_at'        => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
                    'first_access'       => 'DATETIME DEFAULT NULL',
                    'last_access'        => 'DATETIME DEFAULT NULL',
                    'access_count'       => 'INT(11) DEFAULT 0',
                    'completed'          => 'TINYINT(1) DEFAULT 0',
                    'completed_at'       => 'DATETIME DEFAULT NULL',
                    'completion_form_id' => 'VARCHAR(20) DEFAULT NULL',
                ),
                'indexes' => array(
                    'PRIMARY KEY'               => '(id)',
                    'KEY  idx_pool_id'          => '(pool_id)',
                    'KEY  idx_participant_id'   => '(participant_id)',
                    'KEY  idx_study_id'         => '(study_id)',
                    'UNIQUE KEY  unique_pool_participant' => '(pool_id, participant_id)',
                ),
            ),
            'eipsi_pool_analytics' => array(
                'columns' => array(
                    'id'                    => 'BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT',
                    'pool_id'               => 'BIGINT(20) UNSIGNED NOT NULL',
                    'date'                  => 'DATE NOT NULL',
                    'study_id'              => 'BIGINT(20) UNSIGNED NOT NULL',
                    'assignments'           => 'INT(11) DEFAULT 0',
                    'completions'           => 'INT(11) DEFAULT 0',
                    'cumulative_assignments' => 'INT(11) DEFAULT 0',
                ),
                'indexes' => array(
                    'PRIMARY KEY'      => '(id)',
                    'KEY  idx_pool_date' => '(pool_id, date)',
                    'KEY  idx_study_id'  => '(study_id)',
                ),
            ),
            'survey_participant_access_log' => array(
                'columns' => array(
                    'id'             => 'BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT',
                    'participant_id' => 'BIGINT(20) UNSIGNED NOT NULL',
                    'study_id'       => 'INT(11) NOT NULL',
                    'action_type'    => "ENUM('registration', 'login', 'login_failed', 'magic_link_clicked', 'magic_link_sent', 'wave_started', 'wave_completed', 'logout', 'session_expired', 'password_reset_requested', 'password_reset_completed') NOT NULL",
                    'ip_address'     => 'VARCHAR(45) NOT NULL',
                    'user_agent'     => 'VARCHAR(500)',
                    'metadata'       => 'JSON',
                    'created_at'     => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
                ),
                'indexes' => array(
                    'PRIMARY KEY'              => '(id)',
                    'KEY  idx_participant_id'  => '(participant_id)',
                    'KEY  idx_study_id'        => '(study_id)',
                    'KEY  idx_action_type'     => '(action_type)',
                    'KEY  idx_created_at'      => '(created_at)',
                    'KEY  idx_participant_action' => '(participant_id, action_type)',
                    'KEY  idx_study_created'   => '(study_id, created_at)',
                ),
            ),
            'eipsi_device_data' => array(
                'columns' => array(
                    'id'                 => 'BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT',
                    'submission_id'      => 'BIGINT(20) UNSIGNED NULL',
                    'participant_id'     => 'BIGINT(20) UNSIGNED NULL',
                    'canvas_fingerprint' => 'VARCHAR(255) NULL',
                    'webgl_renderer'     => 'VARCHAR(255) NULL',
                    'screen_resolution'  => 'VARCHAR(50) NULL',
                    'screen_depth'       => 'INT NULL',
                    'pixel_ratio'        => 'DECIMAL(4,2) NULL',
                    'timezone'           => 'VARCHAR(100) NULL',
                    'timezone_offset'    => 'INT NULL',
                    'language'           => 'VARCHAR(50) NULL',
                    'languages'          => 'VARCHAR(255) NULL',
                    'cpu_cores'          => 'INT NULL',
                    'ram'                => 'INT NULL',
                    'do_not_track'       => 'VARCHAR(20) NULL',
                    'cookies_enabled'    => 'VARCHAR(10) NULL',
                    'plugins'            => 'TEXT NULL',
                    'user_agent'         => 'TEXT NULL',
                    'platform'           => 'VARCHAR(100) NULL',
                    'touch_support'      => 'VARCHAR(10) NULL',
                    'max_touch_points'   => 'INT NULL',
                    'captured_at'        => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
                ),
                'indexes' => array(
                    'PRIMARY KEY'          => '(id)',
                    'KEY  idx_submission_id' => '(submission_id)',
                    'KEY  idx_participant_id' => '(participant_id)',
                    'KEY  idx_captured_at' => '(captured_at)',
                ),
            ),
            'eipsi_pool_email_log' => array(
                'columns' => array(
                    'id'             => 'BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT',
                    'pool_id'        => 'INT NOT NULL',
                    'participant_id' => 'BIGINT UNSIGNED NOT NULL',
                    'email'          => 'VARCHAR(255) NOT NULL',
                    'action'         => "ENUM('sent', 'confirmed', 'resent') NOT NULL",
                    'created_at'     => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
                ),
                'indexes' => array(
                    'PRIMARY KEY'        => '(id)',
                    'KEY  idx_pool_id'    => '(pool_id)',
                    'KEY  idx_participant_id' => '(participant_id)',
                    'KEY  idx_email'      => '(email)',
                    'KEY  idx_action'     => '(action)',
                    'KEY  idx_created_at' => '(created_at)',
                ),
            ),
            'eipsi_emergency_submissions' => array(
                'columns' => array(
                    'id'             => 'bigint(20) unsigned NOT NULL AUTO_INCREMENT',
                    'form_id'        => 'varchar(50) DEFAULT NULL',
                    'participant_id' => 'varchar(255) DEFAULT NULL',
                    'session_id'     => 'varchar(255) DEFAULT NULL',
                    'survey_id'      => 'bigint(20) DEFAULT NULL',
                    'wave_id'        => 'bigint(20) DEFAULT NULL',
                    'form_responses' => 'longtext DEFAULT NULL',
                    'form_data'      => 'longtext DEFAULT NULL',
                    'metadata'       => 'longtext DEFAULT NULL',
                    'device'         => 'varchar(100) DEFAULT NULL',
                    'browser'        => 'varchar(100) DEFAULT NULL',
                    'os'             => 'varchar(100) DEFAULT NULL',
                    'screen_width'   => 'int(11) DEFAULT NULL',
                    'duration'       => 'int(11) DEFAULT NULL',
                    'ip_address'     => 'varchar(45) DEFAULT NULL',
                    'user_agent'     => 'text DEFAULT NULL',
                    'error_message'  => 'text DEFAULT NULL',
                    'error_code'     => 'varchar(50) DEFAULT NULL',
                    'db_type'        => "varchar(20) DEFAULT 'wordpress'",
                    'resolved'       => 'tinyint(1) DEFAULT 0',
                    'resolved_at'    => 'datetime DEFAULT NULL',
                    'created_at'     => 'datetime DEFAULT CURRENT_TIMESTAMP',
                ),
                'indexes' => array(
                    'PRIMARY KEY' => '(id)',
                    'KEY  form_id' => '(form_id)',
                    'KEY  participant_id' => '(participant_id)',
                    'KEY  session_id' => '(session_id)',
                    'KEY  resolved' => '(resolved)',
                    'KEY  created_at' => '(created_at)',
                ),
            ),
            'survey_nudge_jobs' => array(
                'columns' => array(
                    'id'           => 'BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT',
                    'job_type'     => 'VARCHAR(50) NOT NULL',
                    'payload'      => 'LONGTEXT',
                    'priority'     => 'INT(11) DEFAULT 10',
                    'status'       => "VARCHAR(20) DEFAULT 'pending'",
                    'retries'      => 'INT(11) DEFAULT 0',
                    'error'        => 'TEXT',
                    'result'       => 'TEXT',
                    'scheduled_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
                    'processed_at' => 'DATETIME NULL',
                    'completed_at' => 'DATETIME NULL',
                    'failed_at'    => 'DATETIME NULL',
                    'created_at'   => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
                    'updated_at'   => 'DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
                ),
                'indexes' => array(
                    'PRIMARY KEY'         => '(id)',
                    'KEY  status_scheduled' => '(status, scheduled_at)',
                    'KEY  priority_created' => '(priority, created_at)',
                    'KEY  job_type'       => '(job_type)',
                ),
            ),
            'eipsi_partial_responses' => array(
                'columns' => array(
                    'id'             => 'bigint(20) unsigned NOT NULL AUTO_INCREMENT',
                    'form_id'        => 'varchar(64) NOT NULL',
                    'participant_id' => 'varchar(255) NOT NULL',
                    'session_id'     => 'varchar(255) NOT NULL',
                    'page_index'     => 'int(11) DEFAULT 1',
                    'responses_json' => 'longtext DEFAULT NULL',
                    'completed'      => 'tinyint(1) DEFAULT 0',
                    'created_at'     => 'datetime NOT NULL',
                    'updated_at'     => 'datetime NOT NULL',
                ),
                'indexes' => array(
                    'PRIMARY KEY'           => '(id)',
                    'UNIQUE KEY  unique_session' => '(form_id, participant_id, session_id)',
                    'KEY  updated_at'       => '(updated_at)',
                    'KEY  completed'        => '(completed)',
                    'KEY  idx_participant_completed' => '(participant_id, completed)',
                    'KEY  idx_updated_completed'     => '(updated_at, completed)',
                    'KEY  idx_form_participant'      => '(form_id, participant_id)',
                ),
            ),
            'survey_cron_log' => array(
                'columns' => array(
                    'id'          => 'BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT',
                    'cron_hook'   => 'VARCHAR(100) NOT NULL',
                    'executed_at' => 'DATETIME NOT NULL',
                    'metadata'    => 'TEXT',
                ),
                'indexes' => array(
                    'PRIMARY KEY'     => '(id)',
                    'KEY  cron_hook'   => '(cron_hook)',
                    'KEY  executed_at' => '(executed_at)',
                ),
            ),
            'survey_data_requests' => array(
                'columns' => array(
                    'id'             => 'BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT',
                    'participant_id' => 'BIGINT(20) UNSIGNED NOT NULL',
                    'survey_id'      => 'BIGINT(20) UNSIGNED NOT NULL',
                    'request_type'   => 'VARCHAR(20) NOT NULL',
                    'reason'         => 'TEXT',
                    'status'         => "VARCHAR(20) NOT NULL DEFAULT 'pending'",
                    'admin_id'       => 'BIGINT(20) UNSIGNED',
                    'admin_notes'    => 'TEXT',
                    'result_data'    => 'TEXT',
                    'created_at'     => 'DATETIME NOT NULL',
                    'started_processing_at' => 'DATETIME',
                    'processed_at'   => 'DATETIME',
                ),
                'indexes' => array(
                    'PRIMARY KEY'     => '(id)',
                    'KEY  participant_id' => '(participant_id)',
                    'KEY  survey_id'      => '(survey_id)',
                    'KEY  status'         => '(status)',
                    'KEY  created_at'     => '(created_at)',
                ),
            ),
            'eipsi_manual_overrides' => array(
                'columns' => array(
                    'id'                => 'BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT',
                    'randomization_id'  => 'VARCHAR(255) NOT NULL',
                    'user_fingerprint'  => 'VARCHAR(255) NOT NULL',
                    'assigned_form_id'  => 'BIGINT(20) UNSIGNED NOT NULL',
                    'reason'            => 'TEXT',
                    'created_by'        => 'BIGINT(20) UNSIGNED NOT NULL',
                    'created_at'        => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
                    'updated_at'        => 'DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
                    'status'            => "ENUM('active', 'revoked', 'expired') DEFAULT 'active'",
                    'expires_at'        => 'DATETIME NULL',
                ),
                'indexes' => array(
                    'PRIMARY KEY'               => '(id)',
                    'UNIQUE KEY  unique_override' => '(randomization_id, user_fingerprint)',
                    'KEY  randomization_id'     => '(randomization_id)',
                    'KEY  user_fingerprint'     => '(user_fingerprint)',
                    'KEY  status'               => '(status)',
                    'KEY  expires_at'           => '(expires_at)',
                    'KEY  created_by'           => '(created_by)',
                ),
            ),
        );
    }

    /**
     * Generate correctly formatted CREATE TABLE SQL for dbDelta.
     * 
     * @param string $slug Table slug
     * @param string $charset_collate
     * @return string
     */
    private static function get_create_table_sql( $slug, $charset_collate = '' ) {
        global $wpdb;
        $map = self::get_schema_map();
        if ( ! isset( $map[$slug] ) ) {
            return '';
        }

        $config = $map[$slug];
        $full_table_name = $wpdb->prefix . $slug;
        
        $sql = "CREATE TABLE $full_table_name (\n";
        
        foreach ( $config['columns'] as $col => $def ) {
            $sql .= "  $col $def,\n";
        }
        
        foreach ( $config['indexes'] as $key => $def ) {
            // Fix spacing for dbDelta to avoid empty index names
            if ( $key === 'PRIMARY KEY' ) {
                $sql .= "  PRIMARY KEY  $def,\n";
            } else {
                $sql .= "  $key  $def,\n";
            }
        }
        
        $sql = rtrim( $sql, ",\n" );
        $sql .= "\n) $charset_collate;";
        
        return $sql;
    }

    /**
     * Sync table in local WordPress database.
     */
    private static function sync_local_table( $slug ) {
        global $wpdb;
        $table_name = $wpdb->prefix . $slug;
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = self::get_create_table_sql( $slug, $charset_collate );
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        
        // Suppress errors during dbDelta to handle legacy columns gracefully
        $wpdb->suppress_errors(true);
        dbDelta( $sql );
        $wpdb->suppress_errors(false);
        
        $exists = self::local_table_exists( $table_name );
        
        return array(
            'success' => $exists && empty( $wpdb->last_error ),
            'exists'  => $exists,
            'created' => $exists, // Simplified for dbDelta
            'error'   => $wpdb->last_error,
            'columns_added' => array(), // dbDelta doesn't easily return this
        );
    }

    /**
     * Sync table in external database.
     */
    private static function sync_external_table( $slug, $mysqli ) {
        global $wpdb;
        $table_name = $wpdb->prefix . $slug;
        $charset = $mysqli->character_set_name();
        
        $result = array(
            'success' => true,
            'exists'  => false,
            'created' => false,
            'columns_added' => array(),
            'error'   => null,
        );

        $map = self::get_schema_map();
        if ( ! isset( $map[$slug] ) ) {
            $result['success'] = false;
            $result['error'] = "Unknown table slug: $slug";
            return $result;
        }

        $config = $map[$slug];

        // Check if table exists
        $check = $mysqli->query( "SHOW TABLES LIKE '{$table_name}'" );
        $result['exists'] = $check && $check->num_rows > 0;

        if ( ! $result['exists'] ) {
            // Create table using same logic but adjust SQL for mysqli
            $sql = self::get_create_table_sql( $slug, "DEFAULT CHARSET=$charset" );
            if ( ! $mysqli->query( $sql ) ) {
                $result['success'] = false;
                $result['error'] = 'Failed to create table: ' . $mysqli->error;
                return $result;
            }
            $result['created'] = true;
            $result['exists'] = true;
        }

        // Check for missing columns and add them
        foreach ( $config['columns'] as $col => $def ) {
            $col_check = $mysqli->query( "SHOW COLUMNS FROM `{$table_name}` LIKE '{$col}'" );
            if ( ! $col_check || $col_check->num_rows === 0 ) {
                $alter_sql = "ALTER TABLE `{$table_name}` ADD COLUMN $col $def";
                if ( $mysqli->query( $alter_sql ) ) {
                    $result['columns_added'][] = $col;
                } else {
                    $result['success'] = false;
                    $result['error'] = "Failed to add column $col: " . $mysqli->error;
                }
            }
        }

        return $result;
    }

    /**
     * Check if table exists in local database.
     */
    private static function local_table_exists( $table_name ) {
        global $wpdb;
        return $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) === $table_name;
    }

    /**
     * Check if column exists in local table.
     */
    private static function local_column_exists( $table, $column ) {
        global $wpdb;
        $result = $wpdb->get_results(
            $wpdb->prepare(
                "SHOW COLUMNS FROM {$table} LIKE %s",
                $column
            )
        );
        return ! empty( $result );
    }

    /**
     * Ensure index exists on local table.
     */
    private static function ensure_local_index( $table, $column ) {
        global $wpdb;
        if ( empty( $table ) || empty( $column ) ) {
            return;
        }
        if ( ! self::local_table_exists( $table ) ) {
            return;
        }
        $indexes = $wpdb->get_results( "SHOW INDEX FROM `{$table}` WHERE Column_name = '{$column}'" );
        if ( empty( $indexes ) ) {
            $wpdb->query( "ALTER TABLE `{$table}` ADD KEY `{$column}` (`{$column}`)" );
        }
    }

    /**
     * Add foreign keys in phase 2 (after all tables exist).
     * 
     * @return array Results for each FK attempt
     */
    private static function add_foreign_keys_phase2() {
        global $wpdb;
        
        $results = array(
            'success' => true,
            'added'   => array(),
            'failed'  => array(),
            'skipped' => array(),
        );
        
        $fk_definitions = array(
            'survey_waves' => array(
                'fk_waves_study' => array(
                    'referenced_table' => 'survey_studies',
                    'sql' => "ALTER TABLE {table} ADD CONSTRAINT fk_waves_study FOREIGN KEY (study_id) REFERENCES {prefix}survey_studies(id) ON DELETE CASCADE",
                ),
            ),
            'survey_assignments' => array(
                'fk_assignments_study' => array(
                    'referenced_table' => 'survey_studies',
                    'sql' => "ALTER TABLE {table} ADD CONSTRAINT fk_assignments_study FOREIGN KEY (study_id) REFERENCES {prefix}survey_studies(id) ON DELETE CASCADE",
                ),
                'fk_assignments_wave' => array(
                    'referenced_table' => 'survey_waves',
                    'sql' => "ALTER TABLE {table} ADD CONSTRAINT fk_assignments_wave FOREIGN KEY (wave_id) REFERENCES {prefix}survey_waves(id) ON DELETE CASCADE",
                ),
                'fk_assignments_participant' => array(
                    'referenced_table' => 'survey_participants',
                    'sql' => "ALTER TABLE {table} ADD CONSTRAINT fk_assignments_participant FOREIGN KEY (participant_id) REFERENCES {prefix}survey_participants(id) ON DELETE CASCADE",
                ),
            ),
            'eipsi_pool_assignments' => array(
                'fk_pool_assignments_pool' => array(
                    'referenced_table' => 'eipsi_longitudinal_pools',
                    'sql' => "ALTER TABLE {table} ADD CONSTRAINT fk_pool_assignments_pool FOREIGN KEY (pool_id) REFERENCES {prefix}eipsi_longitudinal_pools(id) ON DELETE CASCADE",
                ),
                'fk_pool_assignments_participant' => array(
                    'referenced_table' => 'survey_participants',
                    'sql' => "ALTER TABLE {table} ADD CONSTRAINT fk_pool_assignments_participant FOREIGN KEY (participant_id) REFERENCES {prefix}survey_participants(id) ON DELETE CASCADE",
                ),
                'fk_pool_assignments_study' => array(
                    'referenced_table' => 'survey_studies',
                    'sql' => "ALTER TABLE {table} ADD CONSTRAINT fk_pool_assignments_study FOREIGN KEY (study_id) REFERENCES {prefix}survey_studies(id) ON DELETE CASCADE",
                ),
            ),
        );
        
        foreach ( $fk_definitions as $table_slug => $fks ) {
            $full_table_name = $wpdb->prefix . $table_slug;
            
            if ( ! self::local_table_exists( $full_table_name ) ) {
                foreach ( $fks as $fk_name => $fk_info ) {
                    $results['skipped'][] = "{$table_slug}.{$fk_name}";
                }
                continue;
            }
            
            foreach ( $fks as $fk_name => $fk_info ) {
                $ref_table_name = $wpdb->prefix . $fk_info['referenced_table'];
                
                if ( ! self::local_table_exists( $ref_table_name ) ) {
                    $results['skipped'][] = "{$table_slug}.{$fk_name}";
                    continue;
                }
                
                if ( function_exists( 'eipsi_longitudinal_fk_exists' ) && eipsi_longitudinal_fk_exists( $full_table_name, $fk_name ) ) {
                    $results['skipped'][] = "{$table_slug}.{$fk_name}";
                    continue;
                }
                
                $sql = str_replace(
                    array( '{table}', '{prefix}' ),
                    array( $full_table_name, $wpdb->prefix ),
                    $fk_info['sql']
                );
                
                if ( function_exists( 'eipsi_longitudinal_ensure_foreign_key' ) ) {
                    $fk_result = eipsi_longitudinal_ensure_foreign_key( $full_table_name, $fk_name, $sql );
                    if ( $fk_result ) {
                        $results['added'][] = "{$table_slug}.{$fk_name}";
                    } else {
                        $results['failed'][] = "{$table_slug}.{$fk_name}";
                    }
                } else {
                    $res = @$wpdb->query( $sql );
                    if ( $res === false ) {
                        $results['failed'][] = "{$table_slug}.{$fk_name}";
                    } else {
                        $results['added'][] = "{$table_slug}.{$fk_name}";
                    }
                }
            }
        }
        
        return $results;
    }

    /**
     * Fix collations for all plugin tables.
     */
    public static function fix_collations() {
        global $wpdb;
        $map = self::get_schema_map();
        $fixed_tables = array();
        $target_collation = 'utf8mb4_unicode_ci';
        
        foreach ( array_keys($map) as $slug ) {
            $full_table_name = $wpdb->prefix . $slug;
            $status = $wpdb->get_row( $wpdb->prepare( "SHOW TABLE STATUS LIKE %s", $full_table_name ), ARRAY_A );
            
            if ( $status && $status['Collation'] !== $target_collation ) {
                $alter_sql = "ALTER TABLE $full_table_name CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
                if ( false !== $wpdb->query( $alter_sql ) ) {
                    $fixed_tables[] = $full_table_name;
                }
            }
        }
        
        return array( 'success' => true, 'total_fixed' => count($fixed_tables), 'fixed' => $fixed_tables );
    }

    /**
     * Repair LOCAL WordPress database schema (Alias for backward compatibility).
     * 
     * @return array
     */
    public static function repair_local_schema() {
        return self::verify_and_sync_schema();
    }

    /**
     * Get detailed status for a single table.
     */
    public static function get_detailed_table_status( $table_slug ) {
        global $wpdb;
        $full_table_name = $wpdb->prefix . $table_slug;
        $map = self::get_schema_map();
        
        $result = array(
            'table_name'      => $table_slug,
            'full_table_name' => $full_table_name,
            'exists'          => false,
            'row_count'       => 0,
            'columns'         => array(),
            'missing_columns' => array(),
            'status'          => 'error',
            'issues'          => array()
        );
        
        $table_exists = self::local_table_exists( $full_table_name );
        $result['exists'] = $table_exists;
        
        if ( ! $table_exists ) {
            $result['issues'][] = 'Table does not exist';
            return $result;
        }
        
        $result['row_count'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $full_table_name" );
        $columns = $wpdb->get_results( "SHOW COLUMNS FROM $full_table_name", ARRAY_A );
        $result['columns'] = array_column( $columns, 'Field' );
        
        if ( isset( $map[$table_slug] ) ) {
            $required = array_keys( $map[$table_slug]['columns'] );
            $result['missing_columns'] = array_diff( $required, $result['columns'] );
            
            if ( ! empty( $result['missing_columns'] ) ) {
                $result['issues'][] = 'Missing columns: ' . implode( ', ', $result['missing_columns'] );
                $result['status'] = 'warning';
            } else {
                $result['status'] = 'ok';
            }
        }
        
        return $result;
    }

    /**
     * Repair a single table.
     */
    public static function repair_single_table( $table_slug ) {
        return self::sync_local_table( $table_slug );
    }

    /**
     * Get status for all monitored tables.
     */
    public static function get_all_tables_status() {
        $map = self::get_schema_map();
        $status = array();
        foreach ( array_keys($map) as $slug ) {
            $status[$slug] = self::get_detailed_table_status( $slug );
        }
        return $status;
    }

    /**
     * Get schema health summary.
     */
    public static function get_schema_health_summary() {
        $all_status = self::get_all_tables_status();
        $summary = array(
            'total_tables'   => count($all_status),
            'healthy_tables' => 0,
            'warning_tables' => 0,
            'error_tables'   => 0,
            'total_rows'     => 0,
            'last_verified'  => get_option( 'eipsi_schema_last_verified', null ),
            'issues'         => array()
        );
        
        foreach ( $all_status as $slug => $s ) {
            if ( $s['status'] === 'ok' ) $summary['healthy_tables']++;
            elseif ( $s['status'] === 'warning' ) $summary['warning_tables']++;
            else $summary['error_tables']++;
            
            $summary['total_rows'] += $s['row_count'];
            if ( ! empty($s['issues']) ) $summary['issues'][$slug] = $s['issues'];
        }
        
        return $summary;
    }

    /**
     * Execute maintenance SQL statements.
     */
    public static function execute_maintenance_sql( $sql_statements ) {
        global $wpdb;
        $results = array();
        
        foreach ( $sql_statements as $sql ) {
            $sql = trim($sql);
            if ( empty($sql) ) continue;
            
            // Basic safety: only allow operations on plugin tables
            $is_allowed = (
                strpos(strtoupper($sql), 'WP_SURVEY_') !== false ||
                strpos(strtoupper($sql), 'WP_VAS_FORM_') !== false ||
                strpos(strtoupper($sql), 'WP_EIPSI_') !== false
            );
            
            if ( ! $is_allowed ) {
                $results[] = array( 'success' => false, 'error' => 'Table not allowed' );
                continue;
            }
            
            $res = $wpdb->query( $sql );
            $results[] = array( 'success' => $res !== false, 'affected' => $res, 'error' => $wpdb->last_error );
        }
        
        return $results;
    }

    /**
     * Hook: Called when database credentials are changed
     */
    public static function on_credentials_changed() {
        delete_option( 'eipsi_schema_last_verified' );
        require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/database.php';
        $db_helper = new EIPSI_External_Database();
        $mysqli = $db_helper->get_connection();
        
        if ( $mysqli ) {
            $result = self::verify_and_sync_schema( $mysqli );
            $mysqli->close();
            return $result;
        }
        return array( 'success' => false, 'error' => 'Could not connect' );
    }

    /**
     * Periodic verification (every 24 hours)
     */
    public static function periodic_verification() {
        $last = get_option( 'eipsi_schema_last_verified', 0 );
        if ( ( current_time( 'timestamp' ) - strtotime( $last ) ) > 86400 ) {
            self::verify_and_sync_schema();
            
            require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/database.php';
            $db_helper = new EIPSI_External_Database();
            if ( $db_helper->is_enabled() ) {
                $mysqli = $db_helper->get_connection();
                if ( $mysqli ) {
                    self::verify_and_sync_schema( $mysqli );
                    $mysqli->close();
                }
            }
        }
    }
}

// =================================================================
// LONGITUDINAL UTILITIES (Best effort FK logic)
// =================================================================

function eipsi_longitudinal_fk_exists($table_name, $constraint_name) {
    global $wpdb;
    return !empty($wpdb->get_var($wpdb->prepare(
        "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = %s AND TABLE_NAME = %s AND CONSTRAINT_NAME = %s AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
        DB_NAME, $table_name, $constraint_name
    )));
}

function eipsi_longitudinal_ensure_foreign_key($table_name, $constraint_name, $alter_sql) {
    global $wpdb;
    if (eipsi_longitudinal_fk_exists($table_name, $constraint_name)) return true;
    $result = @$wpdb->query($alter_sql);
    if ($result === false) {
        error_log('[EIPSI] Failed to add FK ' . $constraint_name . ' on ' . $table_name . ': ' . $wpdb->last_error);
        return false;
    }
    return true;
}

/**
 * Unified entrypoint for schema synchronization.
 */
add_action('eipsi_sync_longitudinal_tables', function() {
    if (class_exists('EIPSI_Database_Schema_Manager')) {
        EIPSI_Database_Schema_Manager::verify_and_sync_schema();
    }
});

// Alias for backwards compatibility
add_action('eipsi_sync_rct_tables', function() {
    do_action('eipsi_sync_longitudinal_tables');
});
