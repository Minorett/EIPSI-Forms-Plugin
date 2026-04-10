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
 * Handles automatic table creation and schema synchronization for external databases
 * 
 * @package EIPSI_Forms
 * @since 1.2.1
 */
class EIPSI_Database_Schema_Manager {
    
    /**
     * Verify and synchronize schema for both local and external databases
     * Creates missing tables and adds missing columns automatically
     * 
     * @param mysqli|null $mysqli Optional external database connection
     * @return array Result with success status and details
     */
    public static function verify_and_sync_schema( $mysqli = null ) {
        $results = array(
            'success' => true,
            'results_table' => array(
                'exists' => false,
                'created' => false,
                'columns_added' => array(),
                'columns_missing' => array(),
            ),
            'events_table' => array(
                'exists' => false,
                'created' => false,
                'columns_added' => array(),
                'columns_missing' => array(),
            ),
            'randomization_configs_table' => array(
                'exists' => false,
                'created' => false,
                'columns_added' => array(),
                'columns_missing' => array(),
            ),
            'randomization_assignments_table' => array(
                'exists' => false,
                'created' => false,
                'columns_added' => array(),
                'columns_missing' => array(),
            ),
            'errors' => array(),
        );
        
        if ( $mysqli ) {
            // External database sync
            $results_sync = self::sync_results_table( $mysqli );
            $events_sync = self::sync_events_table( $mysqli );
            $rct_configs_sync = self::sync_randomization_configs_table( $mysqli );
            $rct_assignments_sync = self::sync_randomization_assignments_table( $mysqli );
            
            $results['results_table'] = $results_sync;
            $results['events_table'] = $events_sync;
            $results['randomization_configs_table'] = $rct_configs_sync;
            $results['randomization_assignments_table'] = $rct_assignments_sync;
            
            if ( ! $results_sync['success'] || ! $events_sync['success'] || 
                 ! $rct_configs_sync['success'] || ! $rct_assignments_sync['success'] ) {
                $results['success'] = false;
                if ( ! $results_sync['success'] ) {
                    $results['errors'][] = $results_sync['error'];
                }
                if ( ! $events_sync['success'] ) {
                    $results['errors'][] = $events_sync['error'];
                }
                if ( ! $rct_configs_sync['success'] ) {
                    $results['errors'][] = $rct_configs_sync['error'];
                }
                if ( ! $rct_assignments_sync['success'] ) {
                    $results['errors'][] = $rct_assignments_sync['error'];
                }
            }
        } else {
            // Local WordPress database sync
            global $wpdb;
            $results_sync = self::sync_local_results_table();
            $events_sync = self::sync_local_events_table();
            $rct_configs_sync = self::sync_local_randomization_configs_table();
            $rct_assignments_sync = self::sync_local_randomization_assignments_table();
            
            // Longitudinal tables (v1.4.0+)
            $studies_sync = self::sync_local_survey_studies_table();
            $participants_sync = self::sync_local_survey_participants_table();
            $sessions_sync = self::sync_local_survey_sessions_table(); // Nueva tabla de sesiones
            $waves_sync = self::sync_local_survey_waves_table();
            $assignments_sync = self::sync_local_survey_assignments_table();
            $magic_links_sync = self::sync_local_survey_magic_links_table();
            $email_log_sync = self::sync_local_survey_email_log_table();
            $audit_log_sync = self::sync_local_survey_audit_log_table();
            $email_confirmations_sync = self::sync_local_survey_email_confirmations_table();
            $longitudinal_pools_sync = self::sync_local_longitudinal_pools_table();
            $longitudinal_pool_assignments_sync = self::sync_local_longitudinal_pool_assignments_table();
            // Phase 2 - Participant Access Log (v2.0.0)
            $participant_access_log_sync = self::sync_local_survey_participant_access_log_table();
            
            // Device Data RAW (v2.1.0) - replaces fingerprint hash with raw device data
            $device_data_sync = self::sync_local_device_data_table();
            
            // Emergency Submissions table (v1.5.0+) - for data safety backup
            $emergency_sync = self::sync_local_emergency_submissions_table();
            
            $results['results_table'] = $results_sync;
            $results['events_table'] = $events_sync;
            $results['randomization_configs_table'] = $rct_configs_sync;
            $results['randomization_assignments_table'] = $rct_assignments_sync;
            
            // Add longitudinal tables to results
            $results['survey_studies_table'] = $studies_sync;
            $results['survey_participants_table'] = $participants_sync;
            $results['survey_sessions_table'] = $sessions_sync; // Nueva tabla de sesiones
            $results['survey_waves_table'] = $waves_sync;
            $results['survey_assignments_table'] = $assignments_sync;
            $results['survey_magic_links_table'] = $magic_links_sync;
            $results['survey_email_log_table'] = $email_log_sync;
            $results['survey_audit_log_table'] = $audit_log_sync;
            $results['survey_email_confirmations_table'] = $email_confirmations_sync;
            $results['longitudinal_pools_table'] = $longitudinal_pools_sync;
            $results['longitudinal_pool_assignments_table'] = $longitudinal_pool_assignments_sync;
            $results['survey_participant_access_log_table'] = $participant_access_log_sync;
            $results['device_data_table'] = $device_data_sync;
            $results['emergency_submissions_table'] = $emergency_sync;
            
            if ( ! $results_sync['success'] || ! $events_sync['success'] || 
                 ! $rct_configs_sync['success'] || ! $rct_assignments_sync['success'] ||
                 ! $studies_sync['success'] ||
                 ! $participants_sync['success'] || ! $sessions_sync['success'] ||
                 ! $waves_sync['success'] || ! $assignments_sync['success'] || 
                 ! $magic_links_sync['success'] || ! $email_log_sync['success'] ||
                 ! $audit_log_sync['success'] || 
                 ! $longitudinal_pools_sync['success'] || ! $longitudinal_pool_assignments_sync['success'] ||
                 ! $participant_access_log_sync['success'] ||
                 ! $device_data_sync['success'] ||
                 ! $emergency_sync['success'] ) {
                $results['success'] = false;
                if ( ! $results_sync['success'] ) {
                    $results['errors'][] = $results_sync['error'];
                }
                if ( ! $events_sync['success'] ) {
                    $results['errors'][] = $events_sync['error'];
                }
                if ( ! $rct_configs_sync['success'] ) {
                    $results['errors'][] = $rct_configs_sync['error'];
                }
                if ( ! $rct_assignments_sync['success'] ) {
                    $results['errors'][] = $rct_assignments_sync['error'];
                }
                if ( ! $studies_sync['success'] ) {
                    $results['errors'][] = $studies_sync['error'];
                }
                if ( ! $participants_sync['success'] ) {
                    $results['errors'][] = $participants_sync['error'];
                }
                if ( ! $sessions_sync['success'] ) {
                    $results['errors'][] = $sessions_sync['error'];
                }
                if ( ! $waves_sync['success'] ) {
                    $results['errors'][] = $waves_sync['error'];
                }
                if ( ! $assignments_sync['success'] ) {
                    $results['errors'][] = $assignments_sync['error'];
                }
                if ( ! $magic_links_sync['success'] ) {
                    $results['errors'][] = $magic_links_sync['error'];
                }
                if ( ! $email_log_sync['success'] ) {
                    $results['errors'][] = $email_log_sync['error'];
                }
                if ( ! $audit_log_sync['success'] ) {
                    $results['errors'][] = $email_confirmations_sync['error'];
                }
                if ( ! $email_confirmations_sync['success'] ) {
                    $results['errors'][] = $email_confirmations_sync['error'];
                }
                if ( ! $longitudinal_pools_sync['success'] ) {
                    $results['errors'][] = $longitudinal_pools_sync['error'];
                }
                if ( ! $longitudinal_pool_assignments_sync['success'] ) {
                    $results['errors'][] = $longitudinal_pool_assignments_sync['error'];
                }
                if ( ! $participant_access_log_sync['success'] ||
                 ! $device_data_sync['success'] ) {
                    $results['errors'][] = $participant_access_log_sync['error'];
                }
                if ( ! $emergency_sync['success'] ) {
                    $results['errors'][] = $emergency_sync['error'];
                }
            }
        }
        
        // Update last verification timestamp
        update_option( 'eipsi_schema_last_verified', current_time( 'mysql' ) );
        
        return $results;
    }
    
    /**
     * Sync vas_form_results table in external database
     */
    private static function sync_results_table( $mysqli ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vas_form_results';
        $charset = $mysqli->character_set_name();
        
        $result = array(
            'success' => true,
            'exists' => false,
            'created' => false,
            'columns_added' => array(),
            'columns_missing' => array(),
            'error' => null,
        );
        
        // Check if table exists
        $check = $mysqli->query( "SHOW TABLES LIKE '{$table_name}'" );
        $result['exists'] = $check && $check->num_rows > 0;
        
        if ( ! $result['exists'] ) {
            // Create table
            $sql = "CREATE TABLE IF NOT EXISTS `{$table_name}` (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                form_id varchar(15) DEFAULT NULL,
                participant_id varchar(255) DEFAULT NULL,
                session_id varchar(255) DEFAULT NULL,
                user_fingerprint varchar(255) DEFAULT NULL,
                participant varchar(255) DEFAULT NULL,
                interaction varchar(255) DEFAULT NULL,
                form_name varchar(255) NOT NULL,
                created_at datetime NOT NULL,
                device varchar(100) DEFAULT NULL,
                browser varchar(100) DEFAULT NULL,
                os varchar(100) DEFAULT NULL,
                screen_width int(11) DEFAULT NULL,
                duration int(11) DEFAULT NULL,
                duration_seconds decimal(8,3) DEFAULT NULL,
                start_timestamp_ms bigint(20) DEFAULT NULL,
                end_timestamp_ms bigint(20) DEFAULT NULL,
                ip_address varchar(45) DEFAULT NULL,
                metadata LONGTEXT DEFAULT NULL,
                status enum('pending','submitted','error') DEFAULT 'submitted',
                form_responses longtext DEFAULT NULL,
                PRIMARY KEY (id),
                KEY form_name (form_name),
                KEY created_at (created_at),
                KEY form_id (form_id),
                KEY participant_id (participant_id),
                KEY session_id (session_id),
                KEY ip_address (ip_address),
                KEY form_participant (form_id, participant_id)
            ) ENGINE=InnoDB DEFAULT CHARSET={$charset}";
            
            if ( ! $mysqli->query( $sql ) ) {
                $result['success'] = false;
                $result['error'] = 'Failed to create results table: ' . $mysqli->error;
                return $result;
            }
            
            $result['created'] = true;
            $result['exists'] = true;
        }
        
        // Ensure required columns exist
        $required_columns = array(
            'form_id' => "ALTER TABLE `{$table_name}` ADD COLUMN form_id varchar(15) DEFAULT NULL AFTER id",
            'participant_id' => "ALTER TABLE `{$table_name}` ADD COLUMN participant_id varchar(255) DEFAULT NULL AFTER form_id",
            'session_id' => "ALTER TABLE `{$table_name}` ADD COLUMN session_id varchar(255) DEFAULT NULL AFTER participant_id",
            'user_fingerprint' => "ALTER TABLE `{$table_name}` ADD COLUMN user_fingerprint varchar(255) DEFAULT NULL AFTER session_id",
            'browser' => "ALTER TABLE `{$table_name}` ADD COLUMN browser varchar(100) DEFAULT NULL AFTER device",
            'os' => "ALTER TABLE `{$table_name}` ADD COLUMN os varchar(100) DEFAULT NULL AFTER browser",
            'screen_width' => "ALTER TABLE `{$table_name}` ADD COLUMN screen_width int(11) DEFAULT NULL AFTER os",
            'duration_seconds' => "ALTER TABLE `{$table_name}` ADD COLUMN duration_seconds decimal(8,3) DEFAULT NULL AFTER duration",
            'start_timestamp_ms' => "ALTER TABLE `{$table_name}` ADD COLUMN start_timestamp_ms bigint(20) DEFAULT NULL AFTER duration_seconds",
            'end_timestamp_ms' => "ALTER TABLE `{$table_name}` ADD COLUMN end_timestamp_ms bigint(20) DEFAULT NULL AFTER start_timestamp_ms",
            'metadata' => "ALTER TABLE `{$table_name}` ADD COLUMN metadata LONGTEXT DEFAULT NULL AFTER ip_address",
            'status' => "ALTER TABLE `{$table_name}` ADD COLUMN status enum('pending','submitted','error') DEFAULT 'submitted' AFTER metadata",
        );
        
        foreach ( $required_columns as $column => $alter_sql ) {
            $check = $mysqli->query( "SHOW COLUMNS FROM `{$table_name}` LIKE '{$column}'" );
            
            if ( ! $check || $check->num_rows === 0 ) {
                if ( $mysqli->query( $alter_sql ) ) {
                    $result['columns_added'][] = $column;
                } else {
                    $result['columns_missing'][] = $column;
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                        error_log( "EIPSI Schema Manager: Failed to add column {$column} - " . $mysqli->error );
                    }
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Sync vas_form_events table in external database
     */
    private static function sync_events_table( $mysqli ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vas_form_events';
        $charset = $mysqli->character_set_name();
        
        $result = array(
            'success' => true,
            'exists' => false,
            'created' => false,
            'columns_added' => array(),
            'columns_missing' => array(),
            'error' => null,
        );
        
        // Check if table exists
        $check = $mysqli->query( "SHOW TABLES LIKE '{$table_name}'" );
        $result['exists'] = $check && $check->num_rows > 0;
        
        if ( ! $result['exists'] ) {
            // Create table
            $sql = "CREATE TABLE IF NOT EXISTS `{$table_name}` (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                form_id varchar(255) NOT NULL DEFAULT '',
                session_id varchar(255) NOT NULL,
                event_type varchar(50) NOT NULL,
                page_number int(11) DEFAULT NULL,
                metadata text DEFAULT NULL,
                user_agent text DEFAULT NULL,
                created_at datetime NOT NULL,
                PRIMARY KEY (id),
                KEY form_id (form_id),
                KEY session_id (session_id),
                KEY event_type (event_type),
                KEY created_at (created_at),
                KEY form_session (form_id, session_id)
            ) ENGINE=InnoDB DEFAULT CHARSET={$charset}";
            
            if ( ! $mysqli->query( $sql ) ) {
                $result['success'] = false;
                $result['error'] = 'Failed to create events table: ' . $mysqli->error;
                return $result;
            }
            
            $result['created'] = true;
            $result['exists'] = true;
        }
        
        // Ensure required columns exist
        $required_columns = array(
            'form_id' => "ALTER TABLE `{$table_name}` ADD COLUMN form_id varchar(255) NOT NULL DEFAULT '' AFTER id",
            'session_id' => "ALTER TABLE `{$table_name}` ADD COLUMN session_id varchar(255) NOT NULL DEFAULT '' AFTER form_id",
            'event_type' => "ALTER TABLE `{$table_name}` ADD COLUMN event_type varchar(50) NOT NULL DEFAULT '' AFTER session_id",
            'page_number' => "ALTER TABLE `{$table_name}` ADD COLUMN page_number int(11) DEFAULT NULL AFTER event_type",
            'metadata' => "ALTER TABLE `{$table_name}` ADD COLUMN metadata text DEFAULT NULL AFTER page_number",
            'user_agent' => "ALTER TABLE `{$table_name}` ADD COLUMN user_agent text DEFAULT NULL AFTER metadata",
            'created_at' => "ALTER TABLE `{$table_name}` ADD COLUMN created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER user_agent",
        );
        
        foreach ( $required_columns as $column => $alter_sql ) {
            $check = $mysqli->query( "SHOW COLUMNS FROM `{$table_name}` LIKE '{$column}'" );
            
            if ( ! $check || $check->num_rows === 0 ) {
                if ( $mysqli->query( $alter_sql ) ) {
                    $result['columns_added'][] = $column;
                } else {
                    $result['columns_missing'][] = $column;
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                        error_log( "EIPSI Schema Manager: Failed to add column {$column} - " . $mysqli->error );
                    }
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Sync vas_form_results table in local WordPress database
     */
    private static function sync_local_results_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vas_form_results';
        
        $result = array(
            'success' => true,
            'exists' => false,
            'created' => false,
            'columns_added' => array(),
            'columns_missing' => array(),
            'error' => null,
        );
        
        // Check if table exists
        $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" );
        $result['exists'] = ! empty( $table_exists );
        
        if ( ! $result['exists'] ) {
            // Table should be created by activation hook, skip here
            $result['success'] = false;
            $result['error'] = 'Table does not exist and should be created by activation hook';
            return $result;
        }
        
        // Ensure required columns exist
        $required_columns = array(
            'form_id' => "ALTER TABLE {$table_name} ADD COLUMN form_id varchar(20) DEFAULT NULL AFTER id",
            'participant_id' => "ALTER TABLE {$table_name} ADD COLUMN participant_id varchar(20) DEFAULT NULL AFTER form_id",
            'survey_id' => "ALTER TABLE {$table_name} ADD COLUMN survey_id INT(11) DEFAULT NULL AFTER participant_id",
            'wave_index' => "ALTER TABLE {$table_name} ADD COLUMN wave_index INT(11) DEFAULT NULL AFTER survey_id",
            'session_id' => "ALTER TABLE {$table_name} ADD COLUMN session_id varchar(255) DEFAULT NULL AFTER participant_id",
            'user_fingerprint' => "ALTER TABLE {$table_name} ADD COLUMN user_fingerprint varchar(255) DEFAULT NULL AFTER session_id",
            'browser' => "ALTER TABLE {$table_name} ADD COLUMN browser varchar(100) DEFAULT NULL AFTER device",
            'os' => "ALTER TABLE {$table_name} ADD COLUMN os varchar(100) DEFAULT NULL AFTER browser",
            'screen_width' => "ALTER TABLE {$table_name} ADD COLUMN screen_width int(11) DEFAULT NULL AFTER os",
            'duration_seconds' => "ALTER TABLE {$table_name} ADD COLUMN duration_seconds decimal(8,3) DEFAULT NULL AFTER duration",
            'submitted_at' => "ALTER TABLE {$table_name} ADD COLUMN submitted_at datetime DEFAULT NULL AFTER created_at",
            'start_timestamp_ms' => "ALTER TABLE {$table_name} ADD COLUMN start_timestamp_ms bigint(20) DEFAULT NULL AFTER duration_seconds",
            'end_timestamp_ms' => "ALTER TABLE {$table_name} ADD COLUMN end_timestamp_ms bigint(20) DEFAULT NULL AFTER start_timestamp_ms",
            'metadata' => "ALTER TABLE {$table_name} ADD COLUMN metadata LONGTEXT DEFAULT NULL AFTER ip_address",
            'status' => "ALTER TABLE {$table_name} ADD COLUMN status enum('pending','submitted','error') DEFAULT 'submitted' AFTER metadata",
        );
        
        foreach ( $required_columns as $column => $alter_sql ) {
            $column_exists = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
                    DB_NAME,
                    $table_name,
                    $column
                )
            );
            
            if ( empty( $column_exists ) ) {
                if ( false !== $wpdb->query( $alter_sql ) ) {
                    $result['columns_added'][] = $column;
                } else {
                    $result['columns_missing'][] = $column;
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                        error_log( 'EIPSI Schema Manager: Failed to add column ' . $column . ' - ' . $wpdb->last_error );
                    }
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Sync vas_form_events table in local WordPress database
     */
    private static function sync_local_events_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vas_form_events';
        
        $result = array(
            'success' => true,
            'exists' => false,
            'created' => false,
            'columns_added' => array(),
            'columns_missing' => array(),
            'error' => null,
        );
        
        // Check if table exists
        $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" );
        $result['exists'] = ! empty( $table_exists );
        
        if ( ! $result['exists'] ) {
            // Table should be created by activation hook, skip here
            $result['success'] = false;
            $result['error'] = 'Table does not exist and should be created by activation hook';
            return $result;
        }
        
        // Ensure required columns exist
        $required_columns = array(
            'form_id' => "ALTER TABLE {$table_name} ADD COLUMN form_id varchar(255) NOT NULL DEFAULT '' AFTER id",
            'session_id' => "ALTER TABLE {$table_name} ADD COLUMN session_id varchar(255) NOT NULL DEFAULT '' AFTER form_id",
            'event_type' => "ALTER TABLE {$table_name} ADD COLUMN event_type varchar(50) NOT NULL DEFAULT '' AFTER session_id",
            'page_number' => "ALTER TABLE {$table_name} ADD COLUMN page_number int(11) DEFAULT NULL AFTER event_type",
            'metadata' => "ALTER TABLE {$table_name} ADD COLUMN metadata text DEFAULT NULL AFTER page_number",
            'user_agent' => "ALTER TABLE {$table_name} ADD COLUMN user_agent text DEFAULT NULL AFTER metadata",
            'created_at' => "ALTER TABLE {$table_name} ADD COLUMN created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER user_agent",
        );

        foreach ( $required_columns as $column => $alter_sql ) {
            $column_exists = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
                    DB_NAME,
                    $table_name,
                    $column
                )
            );

            if ( empty( $column_exists ) ) {
                if ( false !== $wpdb->query( $alter_sql ) ) {
                    $result['columns_added'][] = $column;
                } else {
                    $result['columns_missing'][] = $column;
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                        error_log( 'EIPSI Schema Manager: Failed to add column ' . $column . ' - ' . $wpdb->last_error );
                    }
                }
            }
        }

        return $result;
    }
    
    /**
     * Sync wp_eipsi_randomization_configs table in external database
     */
    private static function sync_randomization_configs_table( $mysqli ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'eipsi_randomization_configs';
        $charset = $mysqli->character_set_name();
        
        $result = array(
            'success' => true,
            'exists' => false,
            'created' => false,
            'columns_added' => array(),
            'columns_missing' => array(),
            'error' => null,
        );
        
        // Check if table exists
        $check = $mysqli->query( "SHOW TABLES LIKE '{$table_name}'" );
        $result['exists'] = $check && $check->num_rows > 0;
        
        if ( ! $result['exists'] ) {
            // Create table
            $sql = "CREATE TABLE IF NOT EXISTS `{$table_name}` (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                randomization_id varchar(255) NOT NULL,
                formularios LONGTEXT NOT NULL,
                probabilidades LONGTEXT,
                method varchar(20) DEFAULT 'seeded',
                manual_assignments LONGTEXT,
                show_instructions tinyint(1) DEFAULT 0,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY randomization_id (randomization_id),
                KEY method (method),
                KEY created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET={$charset}";
            
            if ( ! $mysqli->query( $sql ) ) {
                $result['success'] = false;
                $result['error'] = 'Failed to create randomization_configs table: ' . $mysqli->error;
                return $result;
            }
            
            $result['created'] = true;
            $result['exists'] = true;
        }
        
        // Ensure required columns exist
        $required_columns = array(
            'formularios' => "ALTER TABLE `{$table_name}` ADD COLUMN formularios LONGTEXT NOT NULL AFTER randomization_id",
            'probabilidades' => "ALTER TABLE `{$table_name}` ADD COLUMN probabilidades LONGTEXT AFTER formularios",
            'method' => "ALTER TABLE `{$table_name}` ADD COLUMN method varchar(20) DEFAULT 'seeded' AFTER probabilidades",
            'manual_assignments' => "ALTER TABLE `{$table_name}` ADD COLUMN manual_assignments LONGTEXT AFTER method",
            'show_instructions' => "ALTER TABLE `{$table_name}` ADD COLUMN show_instructions tinyint(1) DEFAULT 0 AFTER manual_assignments",
            'created_at' => "ALTER TABLE `{$table_name}` ADD COLUMN created_at datetime DEFAULT CURRENT_TIMESTAMP AFTER show_instructions",
            'updated_at' => "ALTER TABLE `{$table_name}` ADD COLUMN updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at",
        );
        
        foreach ( $required_columns as $column => $alter_sql ) {
            $check = $mysqli->query( "SHOW COLUMNS FROM `{$table_name}` LIKE '{$column}'" );
            
            if ( ! $check || $check->num_rows === 0 ) {
                if ( $mysqli->query( $alter_sql ) ) {
                    $result['columns_added'][] = $column;
                } else {
                    $result['columns_missing'][] = $column;
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                        error_log( "EIPSI Schema Manager: Failed to add column {$column} - " . $mysqli->error );
                    }
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Sync wp_eipsi_randomization_assignments table in external database
     */
    private static function sync_randomization_assignments_table( $mysqli ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'eipsi_randomization_assignments';
        $charset = $mysqli->character_set_name();
        
        $result = array(
            'success' => true,
            'exists' => false,
            'created' => false,
            'columns_added' => array(),
            'columns_missing' => array(),
            'error' => null,
        );
        
        // Check if table exists
        $check = $mysqli->query( "SHOW TABLES LIKE '{$table_name}'" );
        $result['exists'] = $check && $check->num_rows > 0;
        
        if ( ! $result['exists'] ) {
            // Create table
            $sql = "CREATE TABLE IF NOT EXISTS `{$table_name}` (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                randomization_id varchar(255) NOT NULL,
                config_id varchar(255) NOT NULL,
                user_fingerprint varchar(255) NOT NULL,
                assigned_form_id bigint(20) unsigned NOT NULL,
                assigned_at datetime DEFAULT CURRENT_TIMESTAMP,
                last_access datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                access_count int(11) DEFAULT 1,
                PRIMARY KEY (id),
                UNIQUE KEY unique_assignment (randomization_id, config_id, user_fingerprint),
                KEY randomization_id (randomization_id),
                KEY config_id (config_id),
                KEY user_fingerprint (user_fingerprint),
                KEY assigned_form_id (assigned_form_id),
                KEY assigned_at (assigned_at)
            ) ENGINE=InnoDB DEFAULT CHARSET={$charset}";
            
            if ( ! $mysqli->query( $sql ) ) {
                $result['success'] = false;
                $result['error'] = 'Failed to create randomization_assignments table: ' . $mysqli->error;
                return $result;
            }
            
            $result['created'] = true;
            $result['exists'] = true;
        }
        
        // Ensure required columns exist (CRITICAL: config_id is essential)
        $required_columns = array(
            'randomization_id' => "ALTER TABLE `{$table_name}` ADD COLUMN randomization_id varchar(255) NOT NULL AFTER id",
            'config_id' => "ALTER TABLE `{$table_name}` ADD COLUMN config_id varchar(255) NOT NULL AFTER randomization_id",
            'user_fingerprint' => "ALTER TABLE `{$table_name}` ADD COLUMN user_fingerprint varchar(255) NOT NULL AFTER config_id",
            'assigned_form_id' => "ALTER TABLE `{$table_name}` ADD COLUMN assigned_form_id bigint(20) unsigned NOT NULL AFTER user_fingerprint",
            'assigned_at' => "ALTER TABLE `{$table_name}` ADD COLUMN assigned_at datetime DEFAULT CURRENT_TIMESTAMP AFTER assigned_form_id",
            'last_access' => "ALTER TABLE `{$table_name}` ADD COLUMN last_access datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER assigned_at",
            'access_count' => "ALTER TABLE `{$table_name}` ADD COLUMN access_count int(11) DEFAULT 1 AFTER last_access",
        );
        
        foreach ( $required_columns as $column => $alter_sql ) {
            $check = $mysqli->query( "SHOW COLUMNS FROM `{$table_name}` LIKE '{$column}'" );
            
            if ( ! $check || $check->num_rows === 0 ) {
                if ( $mysqli->query( $alter_sql ) ) {
                    $result['columns_added'][] = $column;
                } else {
                    $result['columns_missing'][] = $column;
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                        error_log( "EIPSI Schema Manager: Failed to add column {$column} - " . $mysqli->error );
                    }
                }
            }
        }
        
        // Ensure unique constraint exists
        $check_constraint = $mysqli->query( "SHOW INDEX FROM `{$table_name}` WHERE Key_name = 'unique_assignment'" );
        if ( ! $check_constraint || $check_constraint->num_rows === 0 ) {
            $constraint_sql = "ALTER TABLE `{$table_name}` ADD CONSTRAINT unique_assignment UNIQUE (randomization_id, config_id, user_fingerprint)";
            if ( $mysqli->query( $constraint_sql ) ) {
                $result['columns_added'][] = 'unique_constraint_unique_assignment';
            } else {
                $result['columns_missing'][] = 'unique_constraint_unique_assignment';
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    error_log( "EIPSI Schema Manager: Failed to add unique constraint - " . $mysqli->error );
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Sync wp_eipsi_randomization_configs table in local WordPress database
     */
    private static function sync_local_randomization_configs_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'eipsi_randomization_configs';
        
        $result = array(
            'success' => true,
            'exists' => false,
            'created' => false,
            'columns_added' => array(),
            'columns_missing' => array(),
            'error' => null,
        );
        
        // Check if table exists
        $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" );
        $result['exists'] = ! empty( $table_exists );
        
        if ( ! $result['exists'] ) {
            // Table should be created by activation hook, skip here
            $result['success'] = false;
            $result['error'] = 'Table does not exist and should be created by activation hook';
            return $result;
        }
        
        // Ensure required columns exist
        $required_columns = array(
            'formularios' => "ALTER TABLE {$table_name} ADD COLUMN formularios LONGTEXT NOT NULL AFTER randomization_id",
            'probabilidades' => "ALTER TABLE {$table_name} ADD COLUMN probabilidades LONGTEXT AFTER formularios",
            'method' => "ALTER TABLE {$table_name} ADD COLUMN method varchar(20) DEFAULT 'seeded' AFTER probabilidades",
            'manual_assignments' => "ALTER TABLE {$table_name} ADD COLUMN manual_assignments LONGTEXT AFTER method",
            'show_instructions' => "ALTER TABLE {$table_name} ADD COLUMN show_instructions tinyint(1) DEFAULT 0 AFTER manual_assignments",
            'created_at' => "ALTER TABLE {$table_name} ADD COLUMN created_at datetime DEFAULT CURRENT_TIMESTAMP AFTER show_instructions",
            'updated_at' => "ALTER TABLE {$table_name} ADD COLUMN updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at",
        );
        
        foreach ( $required_columns as $column => $alter_sql ) {
            $column_exists = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
                    DB_NAME,
                    $table_name,
                    $column
                )
            );
            
            if ( empty( $column_exists ) ) {
                if ( false !== $wpdb->query( $alter_sql ) ) {
                    $result['columns_added'][] = $column;
                } else {
                    $result['columns_missing'][] = $column;
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                        error_log( 'EIPSI Schema Manager: Failed to add column ' . $column . ' - ' . $wpdb->last_error );
                    }
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Sync wp_eipsi_randomization_assignments table in local WordPress database
     */
    private static function sync_local_randomization_assignments_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'eipsi_randomization_assignments';
        
        $result = array(
            'success' => true,
            'exists' => false,
            'created' => false,
            'columns_added' => array(),
            'columns_missing' => array(),
            'error' => null,
        );
        
        // Check if table exists
        $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" );
        $result['exists'] = ! empty( $table_exists );
        
        if ( ! $result['exists'] ) {
            // Table should be created by activation hook, skip here
            $result['success'] = false;
            $result['error'] = 'Table does not exist and should be created by activation hook';
            return $result;
        }
        
        // Ensure required columns exist (CRITICAL: config_id is essential)
        $required_columns = array(
            'randomization_id' => "ALTER TABLE {$table_name} ADD COLUMN randomization_id varchar(255) NOT NULL AFTER id",
            'config_id' => "ALTER TABLE {$table_name} ADD COLUMN config_id varchar(255) NOT NULL AFTER randomization_id",
            'user_fingerprint' => "ALTER TABLE {$table_name} ADD COLUMN user_fingerprint varchar(255) NOT NULL AFTER config_id",
            'assigned_form_id' => "ALTER TABLE {$table_name} ADD COLUMN assigned_form_id bigint(20) unsigned NOT NULL AFTER user_fingerprint",
            'assigned_at' => "ALTER TABLE {$table_name} ADD COLUMN assigned_at datetime DEFAULT CURRENT_TIMESTAMP AFTER assigned_form_id",
            'last_access' => "ALTER TABLE {$table_name} ADD COLUMN last_access datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER assigned_at",
            'access_count' => "ALTER TABLE {$table_name} ADD COLUMN access_count int(11) DEFAULT 1 AFTER last_access",
        );
        
        foreach ( $required_columns as $column => $alter_sql ) {
            $column_exists = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
                    DB_NAME,
                    $table_name,
                    $column
                )
            );
            
            if ( empty( $column_exists ) ) {
                if ( false !== $wpdb->query( $alter_sql ) ) {
                    $result['columns_added'][] = $column;
                } else {
                    $result['columns_missing'][] = $column;
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                        error_log( 'EIPSI Schema Manager: Failed to add column ' . $column . ' - ' . $wpdb->last_error );
                    }
                }
            }
        }
        
        // Ensure unique constraint exists
        $constraint_exists = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DISTINCT CONSTRAINT_NAME 
                FROM information_schema.table_constraints 
                WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND CONSTRAINT_NAME = %s",
                DB_NAME,
                $table_name,
                'unique_assignment'
            )
        );
        
        if ( empty( $constraint_exists ) ) {
            $constraint_sql = "ALTER TABLE {$table_name} ADD CONSTRAINT unique_assignment UNIQUE (randomization_id, config_id, user_fingerprint)";
            if ( false !== $wpdb->query( $constraint_sql ) ) {
                $result['columns_added'][] = 'unique_constraint_unique_assignment';
            } else {
                $result['columns_missing'][] = 'unique_constraint_unique_assignment';
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    error_log( 'EIPSI Schema Manager: Failed to add unique constraint - ' . $wpdb->last_error );
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Hook: Called when database credentials are changed
     */
    public static function on_credentials_changed() {
        // Clear cached verification timestamp
        delete_option( 'eipsi_schema_last_verified' );
        
        // Try to get connection and verify schema
        require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/database.php';
        $db_helper = new EIPSI_External_Database();
        $mysqli = $db_helper->get_connection();
        
        if ( $mysqli ) {
            $result = self::verify_and_sync_schema( $mysqli );
            $mysqli->close();
            
            // Store result for admin display
            update_option( 'eipsi_schema_last_sync_result', $result );
            
            return $result;
        }
        
        return array(
            'success' => false,
            'error' => 'Could not connect to database',
        );
    }
    
    /**
     * Hook: Periodic verification (every 24 hours)
     * Checks both local WordPress DB and external DB (if enabled)
     */
    public static function periodic_verification() {
        $last_verified = get_option( 'eipsi_schema_last_verified', 0 );
        $current_time = current_time( 'timestamp' );
        
        // If more than 24 hours have passed, verify schema
        if ( ( $current_time - strtotime( $last_verified ) ) > 86400 ) {
            // First, verify LOCAL WordPress database
            self::repair_local_schema();
            
            // Then check if external DB is enabled
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
    
    /**
     * Repair LOCAL WordPress database schema
     * Auto-detects and adds missing columns
     * Returns: array with repair details
     */
    /**
     * Repair LOCAL WordPress database schema
     * Auto-detects and adds missing columns
     * Creates all required tables if missing
     * Returns: array with repair details
     */
    public static function repair_local_schema() {
        global $wpdb;

        $results_table = $wpdb->prefix . 'vas_form_results';
        $events_table = $wpdb->prefix . 'vas_form_events';
        $rct_configs_table = $wpdb->prefix . 'eipsi_randomization_configs';
        $rct_assignments_table = $wpdb->prefix . 'eipsi_randomization_assignments';

        // Longitudinal tables (v1.4.0+)
        $survey_studies_table = $wpdb->prefix . 'survey_studies';
        $survey_participants_table = $wpdb->prefix . 'survey_participants';
        $survey_sessions_table = $wpdb->prefix . 'survey_sessions';
        $survey_waves_table = $wpdb->prefix . 'survey_waves';
        $survey_assignments_table = $wpdb->prefix . 'survey_assignments';
        $survey_magic_links_table = $wpdb->prefix . 'survey_magic_links';
        $survey_email_log_table = $wpdb->prefix . 'survey_email_log';
        $survey_audit_log_table = $wpdb->prefix . 'survey_audit_log';
        $longitudinal_pools_table = $wpdb->prefix . 'eipsi_longitudinal_pools';
        $longitudinal_pool_assignments_table = $wpdb->prefix . 'eipsi_longitudinal_pool_assignments';

        $repair_log = array(
            'success' => true,
            'results_table' => array(
                'exists' => false,
                'created' => false,
                'columns_added' => array(),
            ),
            'events_table' => array(
                'exists' => false,
                'created' => false,
                'columns_added' => array(),
            ),
            'randomization_configs_table' => array(
                'exists' => false,
                'created' => false,
                'columns_added' => array(),
            ),
            'randomization_assignments_table' => array(
                'exists' => false,
                'created' => false,
                'columns_added' => array(),
            ),
            'survey_studies_table' => array(
                'exists' => false,
                'created' => false,
                'columns_added' => array(),
            ),
            'survey_participants_table' => array(
                'exists' => false,
                'created' => false,
                'columns_added' => array(),
            ),
            'survey_sessions_table' => array(
                'exists' => false,
                'created' => false,
                'columns_added' => array(),
            ),
            'survey_waves_table' => array(
                'exists' => false,
                'created' => false,
                'columns_added' => array(),
            ),
            'survey_assignments_table' => array(
                'exists' => false,
                'created' => false,
                'columns_added' => array(),
            ),
            'survey_magic_links_table' => array(
                'exists' => false,
                'created' => false,
                'columns_added' => array(),
            ),
            'survey_email_log_table' => array(
                'exists' => false,
                'created' => false,
                'columns_added' => array(),
            ),
            'survey_audit_log_table' => array(
                'exists' => false,
                'created' => false,
                'columns_added' => array(),
            ),
            'longitudinal_pools_table' => array(
                'exists' => false,
                'created' => false,
                'columns_added' => array(),
            ),
            'longitudinal_pool_assignments_table' => array(
                'exists' => false,
                'created' => false,
                'columns_added' => array(),
            ),
        );

        // Check if core tables exist
        $core_tables_exist = self::local_table_exists( $results_table ) && self::local_table_exists( $events_table );
        $rct_tables_exist = self::local_table_exists( $rct_configs_table ) && self::local_table_exists( $rct_assignments_table );

        // Create core tables if missing
        if ( ! $core_tables_exist || ! $rct_tables_exist ) {
            // Tables missing - create via activation hook
            eipsi_forms_activate();

            // Create randomization tables if they still don't exist
            if ( ! self::local_table_exists( $rct_configs_table ) ) {
                $sql = "CREATE TABLE {$rct_configs_table} (
                    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                    randomization_id varchar(255) NOT NULL,
                    formularios LONGTEXT NOT NULL,
                    probabilidades LONGTEXT,
                    method varchar(20) DEFAULT 'seeded',
                    manual_assignments LONGTEXT,
                    show_instructions tinyint(1) DEFAULT 0,
                    created_at datetime DEFAULT CURRENT_TIMESTAMP,
                    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    UNIQUE KEY randomization_id (randomization_id),
                    KEY method (method),
                    KEY created_at (created_at)
                ) " . $wpdb->get_charset_collate() . ";";

                require_once ABSPATH . 'wp-admin/includes/upgrade.php';
                dbDelta( $sql );
                $repair_log['randomization_configs_table']['created'] = true;
                error_log( '[EIPSI Forms] Created randomization_configs table' );
            }

            if ( ! self::local_table_exists( $rct_assignments_table ) ) {
                $sql = "CREATE TABLE {$rct_assignments_table} (
                    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                    randomization_id varchar(255) NOT NULL,
                    config_id varchar(255) NOT NULL,
                    user_fingerprint varchar(255) NOT NULL,
                    assigned_form_id bigint(20) unsigned NOT NULL,
                    assigned_at datetime DEFAULT CURRENT_TIMESTAMP,
                    last_access datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    access_count int(11) DEFAULT 1,
                    PRIMARY KEY (id),
                    UNIQUE KEY unique_assignment (randomization_id, config_id, user_fingerprint),
                    KEY randomization_id (randomization_id),
                    KEY config_id (config_id),
                    KEY user_fingerprint (user_fingerprint),
                    KEY assigned_form_id (assigned_form_id),
                    KEY assigned_at (assigned_at)
                ) " . $wpdb->get_charset_collate() . ";";

                dbDelta( $sql );
                $repair_log['randomization_assignments_table']['created'] = true;
                error_log( '[EIPSI Forms] Created randomization_assignments table' );
            }

            // Re-check core tables
            $repair_log['results_table']['exists'] = self::local_table_exists( $results_table );
            $repair_log['events_table']['exists'] = self::local_table_exists( $events_table );
            $repair_log['randomization_configs_table']['exists'] = self::local_table_exists( $rct_configs_table );
            $repair_log['randomization_assignments_table']['exists'] = self::local_table_exists( $rct_assignments_table );

            if ( ! $core_tables_exist ) {
                $repair_log['results_table']['created'] = true;
                $repair_log['events_table']['created'] = true;
                error_log( '[EIPSI Forms] Schema repair: Core tables created' );
            }
        } else {
            $repair_log['results_table']['exists'] = true;
            $repair_log['events_table']['exists'] = true;
            $repair_log['randomization_configs_table']['exists'] = true;
            $repair_log['randomization_assignments_table']['exists'] = true;
        }

        // Create/sync longitudinal tables (always run to ensure they exist)
        $studies_sync = self::sync_local_survey_studies_table();
        $repair_log['survey_studies_table']['exists'] = $studies_sync['exists'];
        $repair_log['survey_studies_table']['created'] = $studies_sync['created'];
        $repair_log['survey_studies_table']['columns_added'] = $studies_sync['columns_added'];

        $participants_sync = self::sync_local_survey_participants_table();
        $repair_log['survey_participants_table']['exists'] = $participants_sync['exists'];
        $repair_log['survey_participants_table']['created'] = $participants_sync['created'];
        $repair_log['survey_participants_table']['columns_added'] = $participants_sync['columns_added'];

        $sessions_sync = self::sync_local_survey_sessions_table();
        $repair_log['survey_sessions_table']['exists'] = $sessions_sync['exists'];
        $repair_log['survey_sessions_table']['created'] = $sessions_sync['created'];
        $repair_log['survey_sessions_table']['columns_added'] = $sessions_sync['columns_added'];

        $waves_sync = self::sync_local_survey_waves_table();
        $repair_log['survey_waves_table']['exists'] = $waves_sync['exists'];
        $repair_log['survey_waves_table']['created'] = $waves_sync['created'];
        $repair_log['survey_waves_table']['columns_added'] = $waves_sync['columns_added'];

        $assignments_sync = self::sync_local_survey_assignments_table();
        $repair_log['survey_assignments_table']['exists'] = $assignments_sync['exists'];
        $repair_log['survey_assignments_table']['created'] = $assignments_sync['created'];
        $repair_log['survey_assignments_table']['columns_added'] = $assignments_sync['columns_added'];

        $magic_links_sync = self::sync_local_survey_magic_links_table();
        $repair_log['survey_magic_links_table']['exists'] = $magic_links_sync['exists'];
        $repair_log['survey_magic_links_table']['created'] = $magic_links_sync['created'];
        $repair_log['survey_magic_links_table']['columns_added'] = $magic_links_sync['columns_added'];

        $email_log_sync = self::sync_local_survey_email_log_table();
        $repair_log['survey_email_log_table']['exists'] = $email_log_sync['exists'];
        $repair_log['survey_email_log_table']['created'] = $email_log_sync['created'];
        $repair_log['survey_email_log_table']['columns_added'] = $email_log_sync['columns_added'];

        $audit_log_sync = self::sync_local_survey_audit_log_table();
            $email_confirmations_sync = self::sync_local_survey_email_confirmations_table();
        $repair_log['survey_audit_log_table']['exists'] = $audit_log_sync['exists'];
        $repair_log['survey_audit_log_table']['created'] = $audit_log_sync['created'];
        $repair_log['survey_audit_log_table']['columns_added'] = $audit_log_sync['columns_added'];

        $longitudinal_pools_sync = self::sync_local_longitudinal_pools_table();
        $repair_log['longitudinal_pools_table']['exists'] = $longitudinal_pools_sync['exists'];
        $repair_log['longitudinal_pools_table']['created'] = $longitudinal_pools_sync['created'];
        $repair_log['longitudinal_pools_table']['columns_added'] = $longitudinal_pools_sync['columns_added'];

        $longitudinal_pool_assignments_sync = self::sync_local_longitudinal_pool_assignments_table();
        $repair_log['longitudinal_pool_assignments_table']['exists'] = $longitudinal_pool_assignments_sync['exists'];
        $repair_log['longitudinal_pool_assignments_table']['created'] = $longitudinal_pool_assignments_sync['created'];
        $repair_log['longitudinal_pool_assignments_table']['columns_added'] = $longitudinal_pool_assignments_sync['columns_added'];

        // Phase 2 tables: participant access log + device data (v2.0.0+)
        // These were missing from repair_local_schema() — now included for production safety.
        $participant_access_log_sync = self::sync_local_survey_participant_access_log_table();
        $repair_log['survey_participant_access_log_table'] = array(
            'exists'        => $participant_access_log_sync['exists'],
            'created'       => $participant_access_log_sync['created'],
            'columns_added' => $participant_access_log_sync['columns_added'],
        );
        if ( ! $participant_access_log_sync['success'] ) {
            $repair_log['success'] = false;
        }

        $device_data_sync = self::sync_local_device_data_table();
        $repair_log['eipsi_device_data_table'] = array(
            'exists'        => $device_data_sync['exists'],
            'created'       => $device_data_sync['created'],
            'columns_added' => $device_data_sync['columns_added'],
        );
        if ( ! $device_data_sync['success'] ) {
            $repair_log['success'] = false;
        }

        // Repair results table
        if ( $repair_log['results_table']['exists'] ) {
            $results_repair = self::repair_local_results_table( $results_table );
            $repair_log['results_table']['columns_added'] = $results_repair;
        }

        // Repair events table
        if ( $repair_log['events_table']['exists'] ) {
            $events_repair = self::repair_local_events_table( $events_table );
            $repair_log['events_table']['columns_added'] = $events_repair;
        }

        // Repair randomization configs table
        if ( $repair_log['randomization_configs_table']['exists'] ) {
            $rct_configs_repair = self::repair_local_randomization_configs_table( $rct_configs_table );
            $repair_log['randomization_configs_table']['columns_added'] = $rct_configs_repair;
        }

        // Repair randomization assignments table
        if ( $repair_log['randomization_assignments_table']['exists'] ) {
            $rct_assignments_repair = self::repair_local_randomization_assignments_table( $rct_assignments_table );
            $repair_log['randomization_assignments_table']['columns_added'] = $rct_assignments_repair;
        }

        // Update version and timestamp
        update_option( 'eipsi_db_schema_version', '1.4.3' );
        update_option( 'eipsi_schema_last_verified', current_time( 'mysql' ) );

        // Calculate total columns added
        $columns_added_total = 0;
        foreach ( $repair_log as $table_info ) {
            if ( isset( $table_info['columns_added'] ) && is_array( $table_info['columns_added'] ) ) {
                $columns_added_total += count( $table_info['columns_added'] );
            }
        }

        if ( $columns_added_total > 0 ) {
            error_log( '[EIPSI Forms] Schema repair completed - Columns added: ' . wp_json_encode( $repair_log ) );
        }

        // Check for any errors
        $has_errors = false;
        foreach ( $repair_log as $table_info ) {
            if ( isset( $table_info['success'] ) && $table_info['success'] === false ) {
                $has_errors = true;
                break;
            }
        }

        if ( $has_errors ) {
            $repair_log['success'] = false;
        }

        return $repair_log;
    }
    /**
     * Check if table exists in local database
     */
    private static function local_table_exists( $table_name ) {
        global $wpdb;
        return $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) === $table_name;
    }
    
    /**
     * Repair local results table - add missing columns
     */
    private static function repair_local_results_table( $table_name ) {
        global $wpdb;
        
    $required_columns = array(
        'form_id' => "varchar(20) DEFAULT NULL AFTER id",
        'participant_id' => "varchar(20) DEFAULT NULL AFTER form_id",
        'survey_id' => "INT(11) DEFAULT NULL AFTER participant_id",
        'wave_index' => "INT(11) DEFAULT NULL AFTER survey_id",
        'longitudinal_participant_id' => "INT(11) DEFAULT NULL AFTER wave_index",
        'session_id' => "varchar(255) DEFAULT NULL AFTER longitudinal_participant_id",
        'user_fingerprint' => "varchar(255) DEFAULT NULL AFTER session_id",  // ← NUEVO
        'form_name' => "varchar(255) NOT NULL AFTER user_fingerprint",  // ← Cambiado: AFTER user_fingerprint
        'form_responses' => "longtext DEFAULT NULL AFTER form_name",  // ← Agregado AFTER
        'created_at' => "datetime DEFAULT CURRENT_TIMESTAMP AFTER form_responses",  // ← NUEVO
        'submitted_at' => "datetime DEFAULT NULL AFTER created_at",  // ← NUEVO
        'ip_address' => "varchar(100) DEFAULT NULL AFTER submitted_at",  // ← NUEVO (antes no estaba)
        'device' => "varchar(50) DEFAULT NULL AFTER ip_address",  // ← NUEVO (antes no estaba)
        'browser' => "varchar(100) DEFAULT NULL AFTER device",  // ← Cambiado: AFTER device
        'os' => "varchar(100) DEFAULT NULL AFTER browser",
        'screen_width' => "int(11) DEFAULT NULL AFTER os",
        'duration' => "int(11) DEFAULT NULL AFTER screen_width",  // ← NUEVO (antes no estaba)
        'duration_seconds' => "decimal(8,3) DEFAULT NULL AFTER duration",  // ← Correcto: AFTER duration
        'start_timestamp_ms' => "bigint(20) DEFAULT NULL AFTER duration_seconds",  // ← NUEVO
        'end_timestamp_ms' => "bigint(20) DEFAULT NULL AFTER start_timestamp_ms",  // ← NUEVO
        'metadata' => "LONGTEXT DEFAULT NULL AFTER end_timestamp_ms",  // ← Cambiado: AFTER end_timestamp_ms
        'status' => "varchar(20) DEFAULT 'submitted' AFTER metadata",  // ← NUEVO
        // v1.5.5 - RCT at submission time
        'rct_assigned_variant' => "varchar(100) DEFAULT NULL AFTER status",
        'rct_randomization_id' => "varchar(100) DEFAULT NULL AFTER rct_assigned_variant"
    );
        
        $columns_added = array();
        
        foreach ( $required_columns as $col => $definition ) {
            if ( ! self::local_column_exists( $table_name, $col ) ) {
                $result = $wpdb->query( "ALTER TABLE {$table_name} ADD COLUMN {$col} {$definition}" );
                if ( false !== $result ) {
                    $columns_added[] = $col;
                    error_log( "[EIPSI Forms] Added missing column '{$col}' to {$table_name}" );
                }
            }
        }
        
        // Ensure indices exist
        self::ensure_local_index( $table_name, 'form_id' );
        self::ensure_local_index( $table_name, 'participant_id' );
        self::ensure_local_index( $table_name, 'session_id' );
        self::ensure_local_index( $table_name, 'rct_assigned_variant' );
        self::ensure_local_index( $table_name, 'rct_randomization_id' );

        // v1.4.0 - Composite index for faster lookups
        // Check if index exists first (IF NOT EXISTS is not valid for ADD KEY in MySQL/MariaDB)
        $existing_indices = $wpdb->get_results( "SHOW INDEX FROM {$table_name}" );
        $index_names = array_column( $existing_indices, 'Key_name' );
        if ( ! in_array( 'participant_survey_wave', $index_names, true ) ) {
            $wpdb->query( "ALTER TABLE {$table_name} ADD KEY participant_survey_wave (participant_id, survey_id, wave_index)" );
        }
        
        return $columns_added;
    }
    
    /**
     * Repair local events table - add missing columns
     */
    private static function repair_local_events_table( $table_name ) {
        global $wpdb;
        
        $required_columns = array(
            'form_id' => "varchar(255) NOT NULL DEFAULT '' AFTER id",
            'session_id' => "varchar(255) NOT NULL AFTER form_id",
            'event_type' => "varchar(50) NOT NULL AFTER session_id",
            'page_number' => "int(11) DEFAULT NULL AFTER event_type",
            'metadata' => "text DEFAULT NULL AFTER page_number",
            'user_agent' => "text DEFAULT NULL AFTER metadata",
        );
        
        $columns_added = array();
        
        foreach ( $required_columns as $col => $definition ) {
            if ( ! self::local_column_exists( $table_name, $col ) ) {
                $result = $wpdb->query( "ALTER TABLE {$table_name} ADD COLUMN {$col} {$definition}" );
                if ( false !== $result ) {
                    $columns_added[] = $col;
                    error_log( "[EIPSI Forms] Added missing column '{$col}' to {$table_name}" );
                }
            }
        }
        
        // Ensure indices
        self::ensure_local_index( $table_name, 'form_id' );
        self::ensure_local_index( $table_name, 'session_id' );
        self::ensure_local_index( $table_name, 'rct_assigned_variant' );
        self::ensure_local_index( $table_name, 'rct_randomization_id' );

        self::ensure_local_index( $table_name, 'event_type' );
        
        return $columns_added;
    }
    
    /**
     * Repair local randomization configs table - add missing columns
     */
    private static function repair_local_randomization_configs_table( $table_name ) {
        global $wpdb;
        
        $required_columns = array(
            'randomization_id' => "varchar(255) NOT NULL AFTER id",
            'formularios' => "LONGTEXT NOT NULL AFTER randomization_id",
            'probabilidades' => "LONGTEXT AFTER formularios",
            'method' => "varchar(20) DEFAULT 'seeded' AFTER probabilidades",
            'manual_assignments' => "LONGTEXT AFTER method",
            'show_instructions' => "tinyint(1) DEFAULT 0 AFTER manual_assignments",
            'created_at' => "datetime DEFAULT CURRENT_TIMESTAMP AFTER show_instructions",
            'updated_at' => "datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at",
        );
        
        $columns_added = array();
        
        foreach ( $required_columns as $col => $definition ) {
            if ( ! self::local_column_exists( $table_name, $col ) ) {
                $result = $wpdb->query( "ALTER TABLE {$table_name} ADD COLUMN {$col} {$definition}" );
                if ( false !== $result ) {
                    $columns_added[] = $col;
                    error_log( "[EIPSI Forms] Added missing column '{$col}' to {$table_name}" );
                }
            }
        }
        
        // Ensure indices
        self::ensure_local_index( $table_name, 'randomization_id' );
        self::ensure_local_index( $table_name, 'method' );
        self::ensure_local_index( $table_name, 'created_at' );
        
        return $columns_added;
    }
    
    /**
     * Repair local randomization assignments table - add missing columns
     */
    private static function repair_local_randomization_assignments_table( $table_name ) {
        global $wpdb;
        
        $required_columns = array(
            'randomization_id' => "varchar(255) NOT NULL AFTER id",
            'config_id' => "varchar(255) NOT NULL AFTER randomization_id",
            'user_fingerprint' => "varchar(255) NOT NULL AFTER config_id",
            'assigned_form_id' => "bigint(20) unsigned NOT NULL AFTER user_fingerprint",
            'assigned_at' => "datetime DEFAULT CURRENT_TIMESTAMP AFTER assigned_form_id",
            'last_access' => "datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER assigned_at",
            'access_count' => "int(11) DEFAULT 1 AFTER last_access",
        );
        
        $columns_added = array();
        
        foreach ( $required_columns as $col => $definition ) {
            if ( ! self::local_column_exists( $table_name, $col ) ) {
                $result = $wpdb->query( "ALTER TABLE {$table_name} ADD COLUMN {$col} {$definition}" );
                if ( false !== $result ) {
                    $columns_added[] = $col;
                    error_log( "[EIPSI Forms] Added missing column '{$col}' to {$table_name}" );
                }
            }
        }
        
        // Ensure indices
        self::ensure_local_index( $table_name, 'randomization_id' );
        self::ensure_local_index( $table_name, 'config_id' );
        self::ensure_local_index( $table_name, 'user_fingerprint' );
        self::ensure_local_index( $table_name, 'assigned_form_id' );
        self::ensure_local_index( $table_name, 'assigned_at' );
        
        // Ensure unique constraint
        $constraint_exists = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DISTINCT CONSTRAINT_NAME 
                FROM information_schema.table_constraints 
                WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND CONSTRAINT_NAME = %s",
                DB_NAME,
                $table_name,
                'unique_assignment'
            )
        );
        
        if ( empty( $constraint_exists ) ) {
            $constraint_sql = "ALTER TABLE {$table_name} ADD CONSTRAINT unique_assignment UNIQUE (randomization_id, config_id, user_fingerprint)";
            if ( false !== $wpdb->query( $constraint_sql ) ) {
                $columns_added[] = 'unique_constraint_unique_assignment';
                error_log( "[EIPSI Forms] Added unique constraint to {$table_name}" );
            }
        }
        
        return $columns_added;
    }
    
    /**
     * Repair local survey_participants table - add missing columns
     * 
     * @since 1.4.0
     * @param string $table_name Full table name with prefix
     * @return array Columns added
     */
    private static function repair_local_survey_participants_table( $table_name ) {
        global $wpdb;
        
        $required_columns = array(
            'survey_id' => "INT(11) AFTER id",
            'email' => "VARCHAR(255) NOT NULL AFTER survey_id",
            'password_hash' => "VARCHAR(255) AFTER email",
            'first_name' => "VARCHAR(100) AFTER password_hash",
            'last_name' => "VARCHAR(100) AFTER first_name",
            'created_at' => "DATETIME NOT NULL AFTER last_name",
            'last_login_at' => "DATETIME AFTER created_at",
            'is_active' => "TINYINT(1) DEFAULT 1 AFTER last_login_at",
        );
        
        $columns_added = array();
        
        foreach ( $required_columns as $col => $definition ) {
            if ( ! self::local_column_exists( $table_name, $col ) ) {
                $result = $wpdb->query( "ALTER TABLE {$table_name} ADD COLUMN {$col} {$definition}" );
                if ( false !== $result ) {
                    $columns_added[] = $col;
                    error_log( "[EIPSI Forms] Added missing column '{$col}' to {$table_name}" );
                }
            }
        }
        
        // Ensure security indices (VULN 10 FIX)
        self::ensure_local_index( $table_name, 'email' );
        self::ensure_local_index( $table_name, 'created_at' );
        self::ensure_local_index( $table_name, 'is_active' );
        
        // Composite indices for performance
        $existing_indices = $wpdb->get_results( "SHOW INDEX FROM {$table_name}" );
        $index_names = array_column( $existing_indices, 'Key_name' );
        
        if ( ! in_array( 'idx_survey_email', $index_names, true ) ) {
            $wpdb->query( "ALTER TABLE {$table_name} ADD KEY idx_survey_email (survey_id, email)" );
            $columns_added[] = 'idx_survey_email';
        }
        
        if ( ! in_array( 'idx_participant_active', $index_names, true ) ) {
            $wpdb->query( "ALTER TABLE {$table_name} ADD KEY idx_participant_active (is_active)" );
            $columns_added[] = 'idx_participant_active';
        }
        
        return $columns_added;
    }
    
    /**
     * Check if column exists in local table
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
     * Ensure index exists on local table
     */
    private static function ensure_local_index( $table, $column ) {
        global $wpdb;

        // Guard: skip if table or column name is empty to avoid malformed SQL.
        if ( empty( $table ) || empty( $column ) ) {
            return;
        }

        // Guard: skip if the table does not exist yet (prevents SHOW INDEX on missing table).
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
        if ( empty( $table_exists ) ) {
            return;
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $indexes = $wpdb->get_results( "SHOW INDEX FROM `{$table}` WHERE Column_name = '{$column}'" );
        if ( empty( $indexes ) ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $wpdb->query( "ALTER TABLE `{$table}` ADD KEY `{$column}` (`{$column}`)" );
        }
    }
    
    /**
     * Hook: Fallback verification on insert error
     */
    public static function fallback_verification() {
        require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/database.php';
        $db_helper = new EIPSI_External_Database();
        
        if ( $db_helper->is_enabled() ) {
            $mysqli = $db_helper->get_connection();
            
            if ( $mysqli ) {
                $result = self::verify_and_sync_schema( $mysqli );
                $mysqli->close();
                return $result;
            }
        }
        
        return array(
            'success' => false,
            'error' => 'External database not available',
        );
    }
    
    /**
     * Get schema verification status for display
     */
    public static function get_verification_status() {
        $last_verified = get_option( 'eipsi_schema_last_verified', null );
        $last_sync_result = get_option( 'eipsi_schema_last_sync_result', null );
        
        return array(
            'last_verified' => $last_verified,
            'last_sync_result' => $last_sync_result,
            'needs_verification' => empty( $last_verified ) || ( current_time( 'timestamp' ) - strtotime( $last_verified ) ) > 86400,
        );
    }
    
    // =================================================================
    // LONGITUDINAL TABLES SYNC (v1.4.0+)
    // =================================================================
    
    /**
     * Sync wp_survey_participants table in local DB
     * 
     * @since 1.4.0
     * @return array Result with success status and details
     */
    private static function sync_local_survey_studies_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'survey_studies';
        $charset_collate = $wpdb->get_charset_collate();
        
        $result = array(
            'success' => true,
            'exists' => false,
            'created' => false,
            'columns_added' => array(),
            'columns_missing' => array(),
            'error' => null,
        );
        
        // Check if table exists
        $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" );
        $result['exists'] = ! empty( $table_exists );
        
        if ( ! $result['exists'] ) {
            // Create table
            $sql = "CREATE TABLE {$table_name} (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                study_code VARCHAR(50) NOT NULL,
                study_name VARCHAR(255) NOT NULL,
                description TEXT,
                principal_investigator_id BIGINT(20) UNSIGNED,
                status ENUM('active', 'completed', 'paused', 'archived') DEFAULT 'active',
                config JSON,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY unique_study_code (study_code),
                KEY status (status),
                KEY principal_investigator_id (principal_investigator_id),
                KEY created_at (created_at)
            ) {$charset_collate};";
            
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta( $sql );
            
            $result['created'] = true;
            $result['exists'] = true;
            
            error_log( '[EIPSI Forms] Created table: ' . $table_name );
        }
        
        return $result;
    }

    private static function sync_local_survey_participants_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'survey_participants';
        $charset_collate = $wpdb->get_charset_collate();
        
        $result = array(
            'success' => true,
            'exists' => false,
            'created' => false,
            'columns_added' => array(),
            'columns_missing' => array(),
            'error' => null,
        );
        
        // Check if table exists
        $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" );
        $result['exists'] = ! empty( $table_exists );
        
        if ( ! $result['exists'] ) {
            // Create table with security indices
            $sql = "CREATE TABLE {$table_name} (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                survey_id INT(11),
                email VARCHAR(255) NOT NULL,
                password_hash VARCHAR(255),
                first_name VARCHAR(100),
                last_name VARCHAR(100),
                created_at DATETIME NOT NULL,
                last_login_at DATETIME,
                is_active TINYINT(1) DEFAULT 1,
                PRIMARY KEY (id),
                UNIQUE KEY unique_survey_email (survey_id, email),
                KEY survey_id (survey_id),
                KEY is_active (is_active),
                KEY idx_participant_active (is_active),
                KEY idx_email (email),
                KEY idx_created_at (created_at)
            ) {$charset_collate};";
            
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta( $sql );
            
            $result['created'] = true;
            $result['exists'] = true;
            
            error_log( '[EIPSI Forms] Created table: ' . $table_name );
        }
        
        // Ensure required columns exist (for future migrations)
        $required_columns = array(
            'survey_id' => "ALTER TABLE {$table_name} ADD COLUMN survey_id INT(11) AFTER id",
            'email' => "ALTER TABLE {$table_name} ADD COLUMN email VARCHAR(255) NOT NULL AFTER survey_id",
            'password_hash' => "ALTER TABLE {$table_name} ADD COLUMN password_hash VARCHAR(255) AFTER email",
            'first_name' => "ALTER TABLE {$table_name} ADD COLUMN first_name VARCHAR(100) AFTER password_hash",
            'last_name' => "ALTER TABLE {$table_name} ADD COLUMN last_name VARCHAR(100) AFTER first_name",
            'created_at' => "ALTER TABLE {$table_name} ADD COLUMN created_at DATETIME NOT NULL AFTER last_name",
            'last_login_at' => "ALTER TABLE {$table_name} ADD COLUMN last_login_at DATETIME AFTER created_at",
            'is_active' => "ALTER TABLE {$table_name} ADD COLUMN is_active TINYINT(1) DEFAULT 1 AFTER last_login_at",
            'updated_at' => "ALTER TABLE {$table_name} ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER is_active",
        );
        
        foreach ( $required_columns as $column => $alter_sql ) {
            $column_exists = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
                    DB_NAME,
                    $table_name,
                    $column
                )
            );
            
            if ( empty( $column_exists ) ) {
                if ( false !== $wpdb->query( $alter_sql ) ) {
                    $result['columns_added'][] = $column;
                    error_log( "[EIPSI Forms] Added missing column '{$column}' to {$table_name}" );
                } else {
                    $result['columns_missing'][] = $column;
                    $result['success'] = false;
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                        error_log( 'EIPSI Schema Manager: Failed to add column ' . $column . ' - ' . $wpdb->last_error );
                    }
                }
            }
        }
        
        // VULN 10 FIX: Ensure security indices exist for wp_survey_participants
        self::ensure_local_index( $table_name, 'email' );
        self::ensure_local_index( $table_name, 'created_at' );
        
        // Composite indices for performance and security
        $existing_indices = $wpdb->get_results( "SHOW INDEX FROM {$table_name}" );
        $index_names = array_column( $existing_indices, 'Key_name' );
        
        if ( ! in_array( 'idx_survey_email', $index_names, true ) ) {
            $wpdb->query( "ALTER TABLE {$table_name} ADD KEY idx_survey_email (survey_id, email)" );
        }
        
        if ( ! in_array( 'idx_participant_active', $index_names, true ) ) {
            $wpdb->query( "ALTER TABLE {$table_name} ADD KEY idx_participant_active (is_active)" );
        }
        
        return $result;
    }
    
    /**
     * Sync wp_survey_sessions table in local DB
     * 
     * @since 1.4.0
     * @return array Result with success status and details
     */
    private static function sync_local_survey_sessions_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'survey_sessions';
        $charset_collate = $wpdb->get_charset_collate();
        
        $result = array(
            'success' => true,
            'exists' => false,
            'created' => false,
            'columns_added' => array(),
            'columns_missing' => array(),
            'error' => null,
        );
        
        // Check if table exists
        $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" );
        $result['exists'] = ! empty( $table_exists );
        
        if ( ! $result['exists'] ) {
            // Create table
            $sql = "CREATE TABLE {$table_name} (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                token VARCHAR(255) NOT NULL,
                participant_id BIGINT(20) UNSIGNED NOT NULL,
                survey_id INT(11),
                ip_address VARCHAR(45),
                user_agent VARCHAR(500),
                expires_at DATETIME NOT NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY unique_token (token),
                KEY participant_id (participant_id),
                KEY expires_at (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
            
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta( $sql );
            
            $result['created'] = true;
            $result['exists'] = true;
            
            error_log( '[EIPSI Forms] Created table: ' . $table_name );
        }
        
        // Ensure required columns exist (for future migrations)
        $required_columns = array(
            'token' => "ALTER TABLE {$table_name} ADD COLUMN token VARCHAR(255) NOT NULL AFTER id",
            'participant_id' => "ALTER TABLE {$table_name} ADD COLUMN participant_id BIGINT(20) UNSIGNED NOT NULL AFTER token",
            'survey_id' => "ALTER TABLE {$table_name} ADD COLUMN survey_id INT(11) AFTER participant_id",
            'ip_address' => "ALTER TABLE {$table_name} ADD COLUMN ip_address VARCHAR(45) AFTER survey_id",
            'user_agent' => "ALTER TABLE {$table_name} ADD COLUMN user_agent VARCHAR(500) AFTER ip_address",
            'expires_at' => "ALTER TABLE {$table_name} ADD COLUMN expires_at DATETIME NOT NULL AFTER user_agent",
            'created_at' => "ALTER TABLE {$table_name} ADD COLUMN created_at DATETIME NOT NULL AFTER expires_at",
        );
        
        foreach ( $required_columns as $column => $alter_sql ) {
            $column_exists = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
                    DB_NAME,
                    $table_name,
                    $column
                )
            );
            
            if ( empty( $column_exists ) ) {
                if ( false !== $wpdb->query( $alter_sql ) ) {
                    $result['columns_added'][] = $column;
                    error_log( "[EIPSI Forms] Added missing column '{$column}' to {$table_name}" );
                } else {
                    $result['columns_missing'][] = $column;
                    $result['success'] = false;
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                        error_log( 'EIPSI Schema Manager: Failed to add column ' . $column . ' - ' . $wpdb->last_error );
                    }
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Build normalized index definitions for dbDelta CREATE TABLE statements.
     * Prevents empty or malformed index names from ever reaching dbDelta.
     *
     * @param array $indexes Array of index definitions.
     * @return string
     */
    private static function build_dbdelta_index_sql( $indexes ) {
        $lines = array();

        foreach ( $indexes as $index ) {
            if ( empty( $index['type'] ) || empty( $index['name'] ) || empty( $index['columns'] ) || ! is_array( $index['columns'] ) ) {
                continue;
            }

            $type = strtoupper( trim( (string) $index['type'] ) );
            $name = trim( (string) $index['name'] );
            $columns = array_filter(
                array_map(
                    static function( $column ) {
                        $column = trim( (string) $column );
                        return '' !== $column ? $column : null;
                    },
                    $index['columns']
                )
            );

            if ( '' === $name || empty( $columns ) ) {
                continue;
            }

            $escaped_columns = array_map(
                static function( $column ) {
                    return "`{$column}`";
                },
                $columns
            );

            if ( 'UNIQUE' === $type ) {
                $lines[] = sprintf( 'UNIQUE KEY `%1$s` (%2$s)', $name, implode( ', ', $escaped_columns ) );
                continue;
            }

            if ( 'KEY' === $type ) {
                $lines[] = sprintf( 'KEY `%1$s` (%2$s)', $name, implode( ', ', $escaped_columns ) );
            }
        }

        return implode( ",\n            ", $lines );
    }

    /**
     * Validate generated SQL before passing it to dbDelta.
     *
     * @param string $sql SQL statement.
     * @param string $table_name Table name for error context.
     * @return true|WP_Error
     */
    private static function validate_sql_for_dbdelta( $sql, $table_name ) {
        $sql_lines = explode( "\n", $sql );

        foreach ( $sql_lines as $line_num => $line ) {
            $trimmed = trim( $line );

            if ( empty( $trimmed ) ) {
                continue;
            }

            if ( 0 === strpos( $trimmed, '--' ) ) {
                continue;
            }

            if ( preg_match( '/^\s*KEY\s+/i', $trimmed ) || preg_match( '/^\s*UNIQUE\s+KEY\s+/i', $trimmed ) ) {
                if ( false !== strpos( $trimmed, '``' ) || preg_match( '/KEY\s+``\s*\(/i', $trimmed ) ) {
                    $error_msg = sprintf( 'Malformed index definition in %1$s (line %2$d): %3$s', $table_name, $line_num, $trimmed );
                    error_log( 'EIPSI Forms: ' . $error_msg );
                    return new WP_Error( 'dbdelta_malformed_index', $error_msg, array( 'line' => $trimmed ) );
                }

                if ( false !== strpos( $trimmed, '()' ) ) {
                    $error_msg = sprintf( 'Empty index columns in %1$s (line %2$d): %3$s', $table_name, $line_num, $trimmed );
                    error_log( 'EIPSI Forms: ' . $error_msg );
                    return new WP_Error( 'dbdelta_empty_index_columns', $error_msg, array( 'line' => $trimmed ) );
                }
            }
        }

        return true;
    }

    private static function sync_local_survey_waves_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'survey_waves';
        $charset_collate = $wpdb->get_charset_collate();

        $result = array(
            'success' => true,
            'exists' => false,
            'created' => false,
            'columns_added' => array(),
            'columns_missing' => array(),
            'error' => null,
        );

        $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" );
        $result['exists'] = ! empty( $table_exists );
        $already_existed = $result['exists'];

        $index_sql = self::build_dbdelta_index_sql(
            array(
                array(
                    'type'    => 'KEY',
                    'name'    => 'idx_study_id',
                    'columns' => array( 'study_id' ),
                ),
                array(
                    'type'    => 'KEY',
                    'name'    => 'idx_status',
                    'columns' => array( 'status' ),
                ),
                array(
                    'type'    => 'KEY',
                    'name'    => 'idx_due_date',
                    'columns' => array( 'due_date' ),
                ),
                array(
                    'type'    => 'UNIQUE',
                    'name'    => 'uk_study_index',
                    'columns' => array( 'study_id', 'wave_index' ),
                ),
            )
        );

        // Create / update table via dbDelta
        $sql = "CREATE TABLE {$table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            study_id BIGINT(20) UNSIGNED NOT NULL,
            wave_index INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            form_id BIGINT(20) UNSIGNED NOT NULL,
            start_date DATETIME NULL,
            due_date DATETIME NULL,
            reminder_days INT DEFAULT 3,
            retry_enabled TINYINT(1) DEFAULT 1,
            retry_days INT DEFAULT 7,
            max_retries INT DEFAULT 3,
            has_time_limit TINYINT(1) DEFAULT 0,
            completion_time_limit INT DEFAULT NULL,
            status ENUM('draft', 'active', 'completed', 'paused') DEFAULT 'draft',
            is_mandatory TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            {$index_sql}
        ) ENGINE=InnoDB {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $validation = self::validate_sql_for_dbdelta( $sql, $table_name );
        if ( is_wp_error( $validation ) ) {
            $result['success'] = false;
            $result['error']   = $validation->get_error_message();
            return $result;
        }

        dbDelta( $sql );

        if ( ! $already_existed ) {
            $result['created'] = true;
        }

        $result['exists'] = true;

        // Attempt safe data migration from old v1.4.0 schema if present
        // survey_id -> study_id, form_template_id -> form_id, due_at -> due_date, description -> name
        if ( self::local_column_exists( $table_name, 'survey_id' ) && self::local_column_exists( $table_name, 'study_id' ) ) {
            $wpdb->query( "UPDATE {$table_name} SET study_id = survey_id WHERE (study_id IS NULL OR study_id = 0) AND survey_id IS NOT NULL" );
        }

        if ( self::local_column_exists( $table_name, 'form_template_id' ) && self::local_column_exists( $table_name, 'form_id' ) ) {
            $wpdb->query( "UPDATE {$table_name} SET form_id = form_template_id WHERE (form_id IS NULL OR form_id = 0) AND form_template_id IS NOT NULL" );
        }

        if ( self::local_column_exists( $table_name, 'due_at' ) && self::local_column_exists( $table_name, 'due_date' ) ) {
            $wpdb->query( "UPDATE {$table_name} SET due_date = due_at WHERE due_date IS NULL AND due_at IS NOT NULL" );
        }

        if ( self::local_column_exists( $table_name, 'description' ) && self::local_column_exists( $table_name, 'name' ) ) {
            $wpdb->query( "UPDATE {$table_name} SET name = COALESCE(NULLIF(description, ''), CONCAT('Wave ', wave_index)) WHERE name = '' OR name IS NULL" );
        }

        // Ensure foreign keys (best effort)
        if ( function_exists( 'eipsi_longitudinal_ensure_foreign_key' ) ) {
            eipsi_longitudinal_ensure_foreign_key(
                $table_name,
                'fk_waves_study',
                "ALTER TABLE {$table_name} ADD CONSTRAINT fk_waves_study FOREIGN KEY (study_id) REFERENCES {$wpdb->prefix}survey_studies(id) ON DELETE CASCADE"
            );

            eipsi_longitudinal_ensure_foreign_key(
                $table_name,
                'fk_waves_form',
                "ALTER TABLE {$table_name} ADD CONSTRAINT fk_waves_form FOREIGN KEY (form_id) REFERENCES {$wpdb->posts}(ID) ON DELETE RESTRICT"
            );
        }

        // Ensure required columns exist (v1.5.7+)
        $required_columns = array(
            'interval_days' => "ALTER TABLE {$table_name} ADD COLUMN interval_days INT(11) DEFAULT 7 AFTER due_date",
            'time_unit' => "ALTER TABLE {$table_name} ADD COLUMN time_unit VARCHAR(10) DEFAULT 'days' AFTER interval_days",
        );

        foreach ( $required_columns as $column => $alter_sql ) {
            $column_exists = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
                    DB_NAME,
                    $table_name,
                    $column
                )
            );

            if ( empty( $column_exists ) ) {
                if ( false !== $wpdb->query( $alter_sql ) ) {
                    $result['columns_added'][] = $column;
                    error_log( "[EIPSI Forms] Added missing column '{$column}' to {$table_name}" );
                } else {
                    $result['columns_missing'][] = $column;
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                        error_log( 'EIPSI Schema Manager: Failed to add column ' . $column . ' - ' . $wpdb->last_error );
                    }
                }
            }
        }

        return $result;
    }
    
    /**
     * Sync wp_survey_assignments table in local DB
     *
     * @since 1.4.0
     * @return array Result with success status and details
     */
    private static function sync_local_survey_assignments_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'survey_assignments';
        $charset_collate = $wpdb->get_charset_collate();

        $result = array(
            'success' => true,
            'exists' => false,
            'created' => false,
            'columns_added' => array(),
            'columns_missing' => array(),
            'error' => null,
        );

        $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" );
        $result['exists'] = ! empty( $table_exists );
        $already_existed = $result['exists'];

        $index_sql = self::build_dbdelta_index_sql(
            array(
                array(
                    'type'    => 'KEY',
                    'name'    => 'idx_study_id',
                    'columns' => array( 'study_id' ),
                ),
                array(
                    'type'    => 'KEY',
                    'name'    => 'idx_wave_id',
                    'columns' => array( 'wave_id' ),
                ),
                array(
                    'type'    => 'KEY',
                    'name'    => 'idx_participant_id',
                    'columns' => array( 'participant_id' ),
                ),
                array(
                    'type'    => 'KEY',
                    'name'    => 'idx_status',
                    'columns' => array( 'status' ),
                ),
                array(
                    'type'    => 'KEY',
                    'name'    => 'idx_submitted_at',
                    'columns' => array( 'submitted_at' ),
                ),
                array(
                    'type'    => 'KEY',
                    'name'    => 'idx_due_at',
                    'columns' => array( 'due_at' ),
                ),
                array(
                    'type'    => 'UNIQUE',
                    'name'    => 'uk_wave_participant',
                    'columns' => array( 'wave_id', 'participant_id' ),
                ),
            )
        );

        // Create / update table via dbDelta
        $sql = "CREATE TABLE {$table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            study_id BIGINT(20) UNSIGNED NOT NULL,
            wave_id BIGINT(20) UNSIGNED NOT NULL,
            participant_id BIGINT(20) UNSIGNED NOT NULL,
            status ENUM('pending', 'in_progress', 'submitted', 'skipped', 'expired') DEFAULT 'pending',
            assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            first_viewed_at DATETIME NULL,
            submitted_at DATETIME NULL,
            reminder_count INT DEFAULT 0,
            last_reminder_sent DATETIME NULL,
            retry_count INT DEFAULT 0,
            last_retry_sent DATETIME NULL,
            due_at DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            {$index_sql}
        ) ENGINE=InnoDB {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $validation = self::validate_sql_for_dbdelta( $sql, $table_name );
        if ( is_wp_error( $validation ) ) {
            $result['success'] = false;
            $result['error']   = $validation->get_error_message();
            return $result;
        }

        dbDelta( $sql );

        if ( ! $already_existed ) {
            $result['created'] = true;
        }

        $result['exists'] = true;

        // Attempt safe data migration from old v1.4.0 schema if present
        // survey_id -> study_id
        if ( self::local_column_exists( $table_name, 'survey_id' ) && self::local_column_exists( $table_name, 'study_id' ) ) {
            $wpdb->query( "UPDATE {$table_name} SET study_id = survey_id WHERE (study_id IS NULL OR study_id = 0) AND survey_id IS NOT NULL" );
        }

        // created_at -> assigned_at
        if ( self::local_column_exists( $table_name, 'created_at' ) && self::local_column_exists( $table_name, 'assigned_at' ) ) {
            $wpdb->query( "UPDATE {$table_name} SET assigned_at = created_at WHERE assigned_at IS NULL AND created_at IS NOT NULL" );
        }

        // Ensure foreign keys (best effort)
        if ( function_exists( 'eipsi_longitudinal_ensure_foreign_key' ) ) {
            eipsi_longitudinal_ensure_foreign_key(
                $table_name,
                'fk_assignments_study',
                "ALTER TABLE {$table_name} ADD CONSTRAINT fk_assignments_study FOREIGN KEY (study_id) REFERENCES {$wpdb->prefix}survey_studies(id) ON DELETE CASCADE"
            );

            eipsi_longitudinal_ensure_foreign_key(
                $table_name,
                'fk_assignments_wave',
                "ALTER TABLE {$table_name} ADD CONSTRAINT fk_assignments_wave FOREIGN KEY (wave_id) REFERENCES {$wpdb->prefix}survey_waves(id) ON DELETE CASCADE"
            );

            eipsi_longitudinal_ensure_foreign_key(
                $table_name,
                'fk_assignments_participant',
                "ALTER TABLE {$table_name} ADD CONSTRAINT fk_assignments_participant FOREIGN KEY (participant_id) REFERENCES {$wpdb->prefix}survey_participants(id) ON DELETE CASCADE"
            );
        }

        return $result;
    }
    
    /**
     * Sync wp_survey_magic_links table in local DB
     * Magic links for Save & Continue Later feature
     * 
     * @since 1.4.1
     * @return array Result with success status and details
     */
    private static function sync_local_survey_magic_links_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'survey_magic_links';
        $charset_collate = $wpdb->get_charset_collate();
        
        $result = array(
            'success' => true,
            'exists' => false,
            'created' => false,
            'columns_added' => array(),
            'columns_missing' => array(),
            'error' => null,
        );
        
        // Check if table exists
        $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" );
        $result['exists'] = ! empty( $table_exists );
        
        if ( ! $result['exists'] ) {
            // Create table with v1.4.1 schema
            $sql = "CREATE TABLE {$table_name} (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                survey_id BIGINT(20) UNSIGNED NOT NULL,
                participant_id BIGINT(20) UNSIGNED NOT NULL,
                token_hash VARCHAR(255) NOT NULL,
                token_plain VARCHAR(36),
                expires_at DATETIME NOT NULL,
                used_at DATETIME NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY `idx_survey_participant` (`survey_id`, `participant_id`),
                KEY `idx_token_hash` (`token_hash`),
                KEY `idx_expires_used` (`expires_at`, `used_at`),
                UNIQUE KEY `uk_token_hash` (`token_hash`)
            ) {$charset_collate};";
            
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta( $sql );
            
            $result['created'] = true;
            $result['exists'] = true;
            
            error_log( '[EIPSI Forms] Created table: ' . $table_name );
        } else {
            // Table exists, check for v1.4.1 updates (remove old columns, add new ones)
            
            // Check if we need to migrate from old schema (wave_id, max_uses, use_count)
            $old_columns = array('wave_id', 'max_uses', 'use_count');
            $has_old_schema = false;
            
            foreach ($old_columns as $old_col) {
                $column_exists = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                        WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
                        DB_NAME,
                        $table_name,
                        $old_col
                    )
                );
                
                if ($column_exists > 0) {
                    $has_old_schema = true;
                    break;
                }
            }
            
            if ($has_old_schema) {
                // Migrate data to temp table and recreate
                error_log('[EIPSI Forms] Migrating wp_survey_magic_links to v1.4.1 schema');
                
                // Drop and recreate with new schema
                $wpdb->query("DROP TABLE IF EXISTS {$table_name}");
                
                $sql = "CREATE TABLE {$table_name} (
                    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    survey_id BIGINT(20) UNSIGNED NOT NULL,
                    participant_id BIGINT(20) UNSIGNED NOT NULL,
                    token_hash VARCHAR(255) NOT NULL,
                    token_plain VARCHAR(36),
                    expires_at DATETIME NOT NULL,
                    used_at DATETIME NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    KEY `idx_survey_participant` (`survey_id`, `participant_id`),
                    KEY `idx_token_hash` (`token_hash`),
                    KEY `idx_expires_used` (`expires_at`, `used_at`),
                    UNIQUE KEY `uk_token_hash` (`token_hash`)
                ) {$charset_collate};";
                
                dbDelta( $sql );
                
                error_log('[EIPSI Forms] Migrated wp_survey_magic_links to v1.4.1 schema');
            }
        }
        
        // Ensure required v1.4.1 columns exist
        $required_columns = array(
            'survey_id' => "ALTER TABLE {$table_name} ADD COLUMN survey_id BIGINT(20) UNSIGNED NOT NULL AFTER id",
            'participant_id' => "ALTER TABLE {$table_name} ADD COLUMN participant_id BIGINT(20) UNSIGNED NOT NULL AFTER survey_id",
            'token_hash' => "ALTER TABLE {$table_name} ADD COLUMN token_hash VARCHAR(255) NOT NULL AFTER participant_id",
            'token_plain' => "ALTER TABLE {$table_name} ADD COLUMN token_plain VARCHAR(36) AFTER token_hash",
            'expires_at' => "ALTER TABLE {$table_name} ADD COLUMN expires_at DATETIME NOT NULL AFTER token_plain",
            'used_at' => "ALTER TABLE {$table_name} ADD COLUMN used_at DATETIME NULL AFTER expires_at",
            'created_at' => "ALTER TABLE {$table_name} ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP AFTER used_at",
        );
        
        foreach ( $required_columns as $column => $alter_sql ) {
            $column_exists = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
                    DB_NAME,
                    $table_name,
                    $column
                )
            );
            
            if ( empty( $column_exists ) ) {
                if ( false !== $wpdb->query( $alter_sql ) ) {
                    $result['columns_added'][] = $column;
                    error_log( "[EIPSI Forms] Added missing column '{$column}' to {$table_name}" );
                } else {
                    $result['columns_missing'][] = $column;
                    $result['success'] = false;
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                        error_log( 'EIPSI Schema Manager: Failed to add column ' . $column . ' - ' . $wpdb->last_error );
                    }
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Sync wp_survey_email_log table in local DB
     * 
     * @since 1.4.0
     * @return array Result with success status and details
     */
    private static function sync_local_survey_email_log_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'survey_email_log';
        $charset_collate = $wpdb->get_charset_collate();
        
        $result = array(
            'success' => true,
            'exists' => false,
            'created' => false,
            'columns_added' => array(),
            'columns_missing' => array(),
            'error' => null,
        );
        
        // Check if table exists
        $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" );
        $result['exists'] = ! empty( $table_exists );
        
        if ( ! $result['exists'] ) {
            // Create table
            $sql = "CREATE TABLE {$table_name} (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                participant_id BIGINT(20) UNSIGNED NOT NULL,
                survey_id INT(11),
                email_type ENUM('reminder', 'welcome', 'confirmation', 'magic_link', 'recovery', 'custom', 'audit_log') DEFAULT 'custom',
                wave_id BIGINT(20) UNSIGNED,
                sent_at DATETIME NOT NULL,
                status ENUM('sent', 'failed', 'bounced', 'audit') DEFAULT 'sent',
                error_message TEXT,
                metadata JSON,
                PRIMARY KEY (id),
                KEY `participant_id` (`participant_id`),
                KEY `sent_at` (`sent_at`),
                KEY `status` (`status`)
            ) {$charset_collate};";
            
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta( $sql );
            
            $result['created'] = true;
            $result['exists'] = true;
            
            error_log( '[EIPSI Forms] Created table: ' . $table_name );
        }
        
        // Ensure required columns exist
        $required_columns = array(
            'participant_id' => "ALTER TABLE {$table_name} ADD COLUMN participant_id BIGINT(20) UNSIGNED NOT NULL AFTER id",
            'survey_id' => "ALTER TABLE {$table_name} ADD COLUMN survey_id INT(11) AFTER participant_id",
            'email_type' => "ALTER TABLE {$table_name} ADD COLUMN email_type ENUM('reminder', 'welcome', 'confirmation', 'magic_link', 'recovery', 'custom', 'audit_log') DEFAULT 'custom' AFTER survey_id",
            'wave_id' => "ALTER TABLE {$table_name} ADD COLUMN wave_id BIGINT(20) UNSIGNED AFTER email_type",
            'recipient_email' => "ALTER TABLE {$table_name} ADD COLUMN recipient_email VARCHAR(255) AFTER wave_id",
            'subject' => "ALTER TABLE {$table_name} ADD COLUMN subject VARCHAR(500) AFTER recipient_email",
            'content' => "ALTER TABLE {$table_name} ADD COLUMN content TEXT AFTER subject",
            'sent_at' => "ALTER TABLE {$table_name} ADD COLUMN sent_at DATETIME NOT NULL AFTER content",
            'status' => "ALTER TABLE {$table_name} ADD COLUMN status ENUM('sent', 'failed', 'bounced', 'audit') DEFAULT 'sent' AFTER sent_at",
            'error_message' => "ALTER TABLE {$table_name} ADD COLUMN error_message TEXT AFTER status",
            'magic_link_used' => "ALTER TABLE {$table_name} ADD COLUMN magic_link_used TINYINT(1) DEFAULT 0 AFTER error_message",
            'metadata' => "ALTER TABLE {$table_name} ADD COLUMN metadata JSON AFTER magic_link_used",
            'created_at' => "ALTER TABLE {$table_name} ADD COLUMN created_at DATETIME AFTER metadata",
        );
        
        foreach ( $required_columns as $column => $alter_sql ) {
            $column_exists = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
                    DB_NAME,
                    $table_name,
                    $column
                )
            );
            
            if ( empty( $column_exists ) ) {
                if ( false !== $wpdb->query( $alter_sql ) ) {
                    $result['columns_added'][] = $column;
                    error_log( "[EIPSI Forms] Added missing column '{$column}' to {$table_name}" );
                } else {
                    $result['columns_missing'][] = $column;
                    $result['success'] = false;
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                        error_log( 'EIPSI Schema Manager: Failed to add column ' . $column . ' - ' . $wpdb->last_error );
                    }
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Sync wp_survey_audit_log table in local DB
     *
     * @since 1.4.0
     * @return array Result with success status and details
     */
    private static function sync_local_survey_audit_log_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'survey_audit_log';
        $charset_collate = $wpdb->get_charset_collate();

        $result = array(
            'success' => true,
            'exists' => false,
            'created' => false,
            'columns_added' => array(),
            'columns_missing' => array(),
            'error' => null,
        );

        // Check if table exists
        $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" );
        $result['exists'] = ! empty( $table_exists );

        if ( ! $result['exists'] ) {
            // Create table with v1.4.2 schema (TASK 5.1)
            $sql = "CREATE TABLE {$table_name} (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                survey_id BIGINT(20) UNSIGNED NOT NULL,
                participant_id BIGINT(20) UNSIGNED NULL,
                action VARCHAR(100) NOT NULL,
                actor_type ENUM('admin', 'system') DEFAULT 'system',
                actor_id BIGINT(20) UNSIGNED NULL,
                actor_username VARCHAR(255) NULL,
                ip_address VARCHAR(45) NULL,
                metadata JSON NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                INDEX idx_survey_action (survey_id, action),
                INDEX idx_survey_created (survey_id, created_at),
                INDEX idx_action_created (action, created_at),
                INDEX idx_participant_id (participant_id)
            ) {$charset_collate};";

            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta( $sql );

            $result['created'] = true;
            $result['exists'] = true;

            error_log( '[EIPSI Forms] Created table: ' . $table_name );
        }

        // Ensure required columns exist (v1.4.2 update)
        $required_columns = array(
            'survey_id' => "ALTER TABLE {$table_name} ADD COLUMN survey_id BIGINT(20) UNSIGNED NOT NULL AFTER id",
            'participant_id' => "ALTER TABLE {$table_name} ADD COLUMN participant_id BIGINT(20) UNSIGNED NULL AFTER survey_id",
            'action' => "ALTER TABLE {$table_name} ADD COLUMN action VARCHAR(100) NOT NULL AFTER participant_id",
            'actor_type' => "ALTER TABLE {$table_name} ADD COLUMN actor_type ENUM('admin', 'system') DEFAULT 'system' AFTER action",
            'actor_id' => "ALTER TABLE {$table_name} ADD COLUMN actor_id BIGINT(20) UNSIGNED NULL AFTER actor_type",
            'actor_username' => "ALTER TABLE {$table_name} ADD COLUMN actor_username VARCHAR(255) NULL AFTER actor_id",
            'ip_address' => "ALTER TABLE {$table_name} ADD COLUMN ip_address VARCHAR(45) NULL AFTER actor_username",
            'metadata' => "ALTER TABLE {$table_name} ADD COLUMN metadata JSON NULL AFTER ip_address",
            'created_at' => "ALTER TABLE {$table_name} ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP AFTER metadata",
        );

        foreach ( $required_columns as $column => $alter_sql ) {
            $column_exists = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
                    DB_NAME,
                    $table_name,
                    $column
                )
            );

            if ( empty( $column_exists ) ) {
                if ( false !== $wpdb->query( $alter_sql ) ) {
                    $result['columns_added'][] = $column;
                    error_log( "[EIPSI Forms] Added missing column '{$column}' to {$table_name}" );
                } else {
                    $result['columns_missing'][] = $column;
                    $result['success'] = false;
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                        error_log( 'EIPSI Schema Manager: Failed to add column ' . $column . ' - ' . $wpdb->last_error );
                    }
                }
            }
        }

        // Ensure proper indices exist (v1.4.2 update)
        self::ensure_local_index( $table_name, 'survey_id' );
        self::ensure_local_index( $table_name, 'action' );
        self::ensure_local_index( $table_name, 'created_at' );
        self::ensure_local_index( $table_name, 'participant_id' );

        // Composite indices for performance
        $existing_indices = $wpdb->get_results( "SHOW INDEX FROM {$table_name}" );
        $index_names = array_column( $existing_indices, 'Key_name' );

        if ( ! in_array( 'idx_survey_action', $index_names, true ) ) {
            $wpdb->query( "ALTER TABLE {$table_name} ADD KEY idx_survey_action (survey_id, action)" );
        }

        if ( ! in_array( 'idx_survey_created', $index_names, true ) ) {
            $wpdb->query( "ALTER TABLE {$table_name} ADD KEY idx_survey_created (survey_id, created_at)" );
        }

        if ( ! in_array( 'idx_action_created', $index_names, true ) ) {
            $wpdb->query( "ALTER TABLE {$table_name} ADD KEY idx_action_created (action, created_at)" );
        }

        return $result;
    }

    /**
     * Sync wp_survey_email_confirmations table in local DB
     * Stores email confirmation tokens for double opt-in
     * 
     * @since 1.5.0
     * @return array Result with success status and details
     */
    private static function sync_local_survey_email_confirmations_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'survey_email_confirmations';
        $charset_collate = $wpdb->get_charset_collate();
        
        $result = array(
            'success' => true,
            'exists' => false,
            'created' => false,
            'columns_added' => array(),
            'columns_missing' => array(),
            'error' => null,
        );
        
        // Check if table exists
        $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" );
        $result['exists'] = ! empty( $table_exists );
        
        if ( ! $result['exists'] ) {
            // Create table for double opt-in (v1.5.0)
            $sql = "CREATE TABLE {$table_name} (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                survey_id INT(11) NOT NULL,
                participant_id BIGINT(20) UNSIGNED NOT NULL,
                email VARCHAR(255) NOT NULL,
                token_hash VARCHAR(64) NOT NULL,
                token_plain VARCHAR(64) NOT NULL,
                expires_at DATETIME NOT NULL,
                confirmed_at DATETIME NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_participant (participant_id),
                KEY idx_email (email),
                KEY idx_token_hash (token_hash),
                KEY idx_expires_at (expires_at),
                KEY idx_confirmed_at (confirmed_at),
                UNIQUE KEY idx_participant_email (participant_id, email)
            ) {$charset_collate};";
            
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta( $sql );
            
            $result['created'] = true;
            $result['exists'] = true;
            
            error_log( '[EIPSI Forms] Created table: ' . $table_name );
        }
        
        // Ensure required columns exist (for future migrations)
        $required_columns = array(
            'survey_id' => "ALTER TABLE {$table_name} ADD COLUMN survey_id INT(11) NOT NULL AFTER id",
            'participant_id' => "ALTER TABLE {$table_name} ADD COLUMN participant_id BIGINT(20) UNSIGNED NOT NULL AFTER survey_id",
            'email' => "ALTER TABLE {$table_name} ADD COLUMN email VARCHAR(255) NOT NULL AFTER participant_id",
            'token_hash' => "ALTER TABLE {$table_name} ADD COLUMN token_hash VARCHAR(64) NOT NULL AFTER email",
            'token_plain' => "ALTER TABLE {$table_name} ADD COLUMN token_plain VARCHAR(64) NOT NULL AFTER token_hash",
            'expires_at' => "ALTER TABLE {$table_name} ADD COLUMN expires_at DATETIME NOT NULL AFTER token_plain",
            'confirmed_at' => "ALTER TABLE {$table_name} ADD COLUMN confirmed_at DATETIME NULL AFTER expires_at",
            'created_at' => "ALTER TABLE {$table_name} ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP AFTER confirmed_at",
        );
        
        foreach ( $required_columns as $column => $alter_sql ) {
            $column_exists = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
                    DB_NAME,
                    $table_name,
                    $column
                )
            );
            
            if ( empty( $column_exists ) ) {
                if ( false !== $wpdb->query( $alter_sql ) ) {
                    $result['columns_added'][] = $column;
                    error_log( "[EIPSI Forms] Added missing column '{$column}' to {$table_name}" );
                } else {
                    $result['columns_missing'][] = $column;
                    $result['success'] = false;
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                        error_log( 'EIPSI Schema Manager: Failed to add column ' . $column . ' - ' . $wpdb->last_error );
                    }
                }
            }
        }
        
        // Ensure security indices
        self::ensure_local_index( $table_name, 'token_hash' );
        self::ensure_local_index( $table_name, 'expires_at' );
        
        return $result;
    }
    /**
     * Sync wp_eipsi_longitudinal_pools table in local DB
     *
     * @since 2.1.0
     * @return array Result with success status and details
     */
    private static function sync_local_longitudinal_pools_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'eipsi_longitudinal_pools';
        $charset_collate = $wpdb->get_charset_collate();

        $result = array(
            'success' => true,
            'exists' => false,
            'created' => false,
            'columns_added' => array(),
            'columns_missing' => array(),
            'error' => null,
        );

        // Check if table exists
        $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" );
        $result['exists'] = ! empty( $table_exists );

        if ( ! $result['exists'] ) {
            $sql = "CREATE TABLE {$table_name} (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                pool_name VARCHAR(255) NOT NULL,
                pool_description TEXT,
                studies JSON,
                probabilities JSON,
                method ENUM('seeded', 'pure-random') DEFAULT 'seeded',
                status ENUM('active', 'inactive') DEFAULT 'active',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_status (status),
                KEY idx_method (method),
                KEY idx_created_at (created_at)
            ) {$charset_collate};";

            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta( $sql );

            $result['created'] = true;
            $result['exists'] = true;

            error_log( '[EIPSI Forms] Created table: ' . $table_name );
        }

        $required_columns = array(
            'pool_name' => "ALTER TABLE {$table_name} ADD COLUMN pool_name VARCHAR(255) NOT NULL AFTER id",
            'pool_description' => "ALTER TABLE {$table_name} ADD COLUMN pool_description TEXT AFTER pool_name",
            'studies' => "ALTER TABLE {$table_name} ADD COLUMN studies JSON AFTER pool_description",
            'probabilities' => "ALTER TABLE {$table_name} ADD COLUMN probabilities JSON AFTER studies",
            'method' => "ALTER TABLE {$table_name} ADD COLUMN method ENUM('seeded', 'pure-random') DEFAULT 'seeded' AFTER probabilities",
            'status' => "ALTER TABLE {$table_name} ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active' AFTER method",
            'created_at' => "ALTER TABLE {$table_name} ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP AFTER status",
            'updated_at' => "ALTER TABLE {$table_name} ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at",
        );

        foreach ( $required_columns as $column => $alter_sql ) {
            $column_exists = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
                    DB_NAME,
                    $table_name,
                    $column
                )
            );

            if ( empty( $column_exists ) ) {
                if ( false !== $wpdb->query( $alter_sql ) ) {
                    $result['columns_added'][] = $column;
                    error_log( "[EIPSI Forms] Added missing column '{$column}' to {$table_name}" );
                } else {
                    $result['columns_missing'][] = $column;
                    $result['success'] = false;
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                        error_log( 'EIPSI Schema Manager: Failed to add column ' . $column . ' - ' . $wpdb->last_error );
                    }
                }
            }
        }

        self::ensure_local_index( $table_name, 'status' );
        self::ensure_local_index( $table_name, 'method' );
        self::ensure_local_index( $table_name, 'created_at' );

        return $result;
    }

    /**
     * Sync wp_eipsi_longitudinal_pool_assignments table in local DB
     *
     * @since 2.1.0
     * @return array Result with success status and details
     */
    private static function sync_local_longitudinal_pool_assignments_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'eipsi_longitudinal_pool_assignments';
        $charset_collate = $wpdb->get_charset_collate();

        $result = array(
            'success' => true,
            'exists' => false,
            'created' => false,
            'columns_added' => array(),
            'columns_missing' => array(),
            'error' => null,
        );

        // Check if table exists
        $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" );
        $result['exists'] = ! empty( $table_exists );

        if ( ! $result['exists'] ) {
            $sql = "CREATE TABLE {$table_name} (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                pool_id BIGINT(20) UNSIGNED NOT NULL,
                participant_id BIGINT(20) UNSIGNED NOT NULL,
                assigned_study_id BIGINT(20) UNSIGNED NOT NULL,
                assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                status ENUM('assigned', 'completed', 'dropped') DEFAULT 'assigned',
                PRIMARY KEY (id),
                KEY idx_pool_id (pool_id),
                KEY idx_participant_id (participant_id),
                KEY idx_assigned_study_id (assigned_study_id),
                KEY idx_status (status),
                KEY idx_assigned_at (assigned_at)
            ) {$charset_collate};";

            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta( $sql );

            $result['created'] = true;
            $result['exists'] = true;

            error_log( '[EIPSI Forms] Created table: ' . $table_name );
        }

        $required_columns = array(
            'pool_id' => "ALTER TABLE {$table_name} ADD COLUMN pool_id BIGINT(20) UNSIGNED NOT NULL AFTER id",
            'participant_id' => "ALTER TABLE {$table_name} ADD COLUMN participant_id BIGINT(20) UNSIGNED NOT NULL AFTER pool_id",
            'assigned_study_id' => "ALTER TABLE {$table_name} ADD COLUMN assigned_study_id BIGINT(20) UNSIGNED NOT NULL AFTER participant_id",
            'assigned_at' => "ALTER TABLE {$table_name} ADD COLUMN assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP AFTER assigned_study_id",
            'status' => "ALTER TABLE {$table_name} ADD COLUMN status ENUM('assigned', 'completed', 'dropped') DEFAULT 'assigned' AFTER assigned_at",
        );

        foreach ( $required_columns as $column => $alter_sql ) {
            $column_exists = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
                    DB_NAME,
                    $table_name,
                    $column
                )
            );

            if ( empty( $column_exists ) ) {
                if ( false !== $wpdb->query( $alter_sql ) ) {
                    $result['columns_added'][] = $column;
                    error_log( "[EIPSI Forms] Added missing column '{$column}' to {$table_name}" );
                } else {
                    $result['columns_missing'][] = $column;
                    $result['success'] = false;
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                        error_log( 'EIPSI Schema Manager: Failed to add column ' . $column . ' - ' . $wpdb->last_error );
                    }
                }
            }
        }

        self::ensure_local_index( $table_name, 'pool_id' );
        self::ensure_local_index( $table_name, 'participant_id' );
        self::ensure_local_index( $table_name, 'assigned_study_id' );
        self::ensure_local_index( $table_name, 'status' );
        self::ensure_local_index( $table_name, 'assigned_at' );

        if ( function_exists( 'eipsi_longitudinal_ensure_foreign_key' ) ) {
            eipsi_longitudinal_ensure_foreign_key(
                $table_name,
                'fk_pool_assignments_pool',
                "ALTER TABLE {$table_name} ADD CONSTRAINT fk_pool_assignments_pool FOREIGN KEY (pool_id) REFERENCES {$wpdb->prefix}eipsi_longitudinal_pools(id) ON DELETE CASCADE"
            );

            eipsi_longitudinal_ensure_foreign_key(
                $table_name,
                'fk_pool_assignments_participant',
                "ALTER TABLE {$table_name} ADD CONSTRAINT fk_pool_assignments_participant FOREIGN KEY (participant_id) REFERENCES {$wpdb->prefix}survey_participants(id) ON DELETE CASCADE"
            );

            eipsi_longitudinal_ensure_foreign_key(
                $table_name,
                'fk_pool_assignments_study',
                "ALTER TABLE {$table_name} ADD CONSTRAINT fk_pool_assignments_study FOREIGN KEY (assigned_study_id) REFERENCES {$wpdb->prefix}survey_studies(id) ON DELETE CASCADE"
            );
        }

        return $result;
    }
    
    /**
     * Sync wp_survey_participant_access_log table in local DB
     * Phase 2 - Access Logging for GDPR Compliance
     *
     * @since 2.0.0
     * @return array Result with success status and details
     */
    private static function sync_local_survey_participant_access_log_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'survey_participant_access_log';
        $charset_collate = $wpdb->get_charset_collate();

        $result = array(
            'success' => true,
            'exists' => false,
            'created' => false,
            'columns_added' => array(),
            'columns_missing' => array(),
            'error' => null,
        );

        // Check if table exists
        $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" );
        $result['exists'] = ! empty( $table_exists );

        if ( ! $result['exists'] ) {
            // Create table
            $sql = "CREATE TABLE {$table_name} (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                participant_id BIGINT(20) UNSIGNED NOT NULL,
                study_id INT(11) NOT NULL,
                action_type ENUM('registration', 'login', 'login_failed', 'magic_link_clicked', 'magic_link_sent', 'wave_started', 'wave_completed', 'logout', 'session_expired', 'password_reset_requested', 'password_reset_completed') NOT NULL,
                ip_address VARCHAR(45) NOT NULL,
                user_agent VARCHAR(500),
                metadata JSON,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                INDEX idx_participant_id (participant_id),
                INDEX idx_study_id (study_id),
                INDEX idx_action_type (action_type),
                INDEX idx_created_at (created_at),
                INDEX idx_participant_action (participant_id, action_type),
                INDEX idx_study_created (study_id, created_at)
            ) {$charset_collate};";

            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta( $sql );

            $result['created'] = true;
            $result['exists'] = true;

            error_log( '[EIPSI Forms] Created table: ' . $table_name );
        }

        // Ensure required columns exist
        $required_columns = array(
            'participant_id' => "ALTER TABLE {$table_name} ADD COLUMN participant_id BIGINT(20) UNSIGNED NOT NULL AFTER id",
            'study_id' => "ALTER TABLE {$table_name} ADD COLUMN study_id INT(11) NOT NULL AFTER participant_id",
            'action_type' => "ALTER TABLE {$table_name} ADD COLUMN action_type ENUM('registration', 'login', 'login_failed', 'magic_link_clicked', 'magic_link_sent', 'wave_started', 'wave_completed', 'logout', 'session_expired', 'password_reset_requested', 'password_reset_completed') NOT NULL AFTER study_id",
            'ip_address' => "ALTER TABLE {$table_name} ADD COLUMN ip_address VARCHAR(45) NOT NULL AFTER action_type",
            'user_agent' => "ALTER TABLE {$table_name} ADD COLUMN user_agent VARCHAR(500) AFTER ip_address",
            'metadata' => "ALTER TABLE {$table_name} ADD COLUMN metadata JSON AFTER user_agent",
            'created_at' => "ALTER TABLE {$table_name} ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP AFTER metadata",
        );

        foreach ( $required_columns as $column => $alter_sql ) {
            $column_exists = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
                    DB_NAME,
                    $table_name,
                    $column
                )
            );

            if ( empty( $column_exists ) ) {
                if ( false !== $wpdb->query( $alter_sql ) ) {
                    $result['columns_added'][] = $column;
                    error_log( "[EIPSI Forms] Added missing column '{$column}' to {$table_name}" );
                } else {
                    $result['columns_missing'][] = $column;
                    $result['success'] = false;
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                        error_log( 'EIPSI Schema Manager: Failed to add column ' . $column . ' - ' . $wpdb->last_error );
                    }
                }
            }
        }

        // Ensure indices exist
        self::ensure_local_index( $table_name, 'participant_id' );
        self::ensure_local_index( $table_name, 'study_id' );
        self::ensure_local_index( $table_name, 'action_type' );
        self::ensure_local_index( $table_name, 'created_at' );

        // Composite indices for performance
        $existing_indices = $wpdb->get_results( "SHOW INDEX FROM {$table_name}" );
        $index_names = array_column( $existing_indices, 'Key_name' );

        if ( ! in_array( 'idx_participant_action', $index_names, true ) ) {
            $wpdb->query( "ALTER TABLE {$table_name} ADD KEY idx_participant_action (participant_id, action_type)" );
        }

        if ( ! in_array( 'idx_study_created', $index_names, true ) ) {
            $wpdb->query( "ALTER TABLE {$table_name} ADD KEY idx_study_created (study_id, created_at)" );
        }

        return $result;
    }
    
    /**
     * Sync wp_eipsi_device_data table in local DB
     * RAW device data storage (replaces fingerprint hash)
     *
     * @since 2.1.0
     * @return array Result with success status and details
     */
    private static function sync_local_device_data_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'eipsi_device_data';
        $charset_collate = $wpdb->get_charset_collate();

        $result = array(
            'success' => true,
            'exists' => false,
            'created' => false,
            'columns_added' => array(),
            'columns_missing' => array(),
            'error' => null,
        );

        // Check if table exists
        $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" );
        $result['exists'] = ! empty( $table_exists );

        if ( ! $result['exists'] ) {
            // Create table with RAW device data columns
            $sql = "CREATE TABLE {$table_name} (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                submission_id BIGINT(20) UNSIGNED NULL,
                participant_id BIGINT(20) UNSIGNED NULL,
                
                -- Canvas fingerprint (truncated data URL)
                canvas_fingerprint VARCHAR(255) NULL,
                
                -- WebGL renderer info
                webgl_renderer VARCHAR(255) NULL,
                
                -- Screen data
                screen_resolution VARCHAR(50) NULL,
                screen_depth INT NULL,
                pixel_ratio DECIMAL(4,2) NULL,
                
                -- Timezone data
                timezone VARCHAR(100) NULL,
                timezone_offset INT NULL,
                
                -- Language data
                language VARCHAR(50) NULL,
                languages VARCHAR(255) NULL,
                
                -- Hardware data
                cpu_cores INT NULL,
                ram INT NULL,
                
                -- Privacy settings
                do_not_track VARCHAR(20) NULL,
                cookies_enabled VARCHAR(10) NULL,
                
                -- Plugins
                plugins TEXT NULL,
                
                -- User agent data
                user_agent TEXT NULL,
                platform VARCHAR(100) NULL,
                
                -- Touch support
                touch_support VARCHAR(10) NULL,
                max_touch_points INT NULL,
                
                -- Timestamps
                captured_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                
                PRIMARY KEY (id),
                INDEX idx_submission_id (submission_id),
                INDEX idx_participant_id (participant_id),
                INDEX idx_captured_at (captured_at)
            ) {$charset_collate};";

            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta( $sql );

            $result['created'] = true;
            $result['exists'] = true;

            error_log( '[EIPSI Forms] Created table: ' . $table_name );
        }

        // Ensure required columns exist (for future migrations)
        $required_columns = array(
            'submission_id' => "ALTER TABLE {$table_name} ADD COLUMN submission_id BIGINT(20) UNSIGNED NULL AFTER id",
            'participant_id' => "ALTER TABLE {$table_name} ADD COLUMN participant_id BIGINT(20) UNSIGNED NULL AFTER submission_id",
            'canvas_fingerprint' => "ALTER TABLE {$table_name} ADD COLUMN canvas_fingerprint VARCHAR(255) NULL AFTER participant_id",
            'webgl_renderer' => "ALTER TABLE {$table_name} ADD COLUMN webgl_renderer VARCHAR(255) NULL AFTER canvas_fingerprint",
            'screen_resolution' => "ALTER TABLE {$table_name} ADD COLUMN screen_resolution VARCHAR(50) NULL AFTER webgl_renderer",
            'screen_depth' => "ALTER TABLE {$table_name} ADD COLUMN screen_depth INT NULL AFTER screen_resolution",
            'pixel_ratio' => "ALTER TABLE {$table_name} ADD COLUMN pixel_ratio DECIMAL(4,2) NULL AFTER screen_depth",
            'timezone' => "ALTER TABLE {$table_name} ADD COLUMN timezone VARCHAR(100) NULL AFTER pixel_ratio",
            'timezone_offset' => "ALTER TABLE {$table_name} ADD COLUMN timezone_offset INT NULL AFTER timezone",
            'language' => "ALTER TABLE {$table_name} ADD COLUMN language VARCHAR(50) NULL AFTER timezone_offset",
            'languages' => "ALTER TABLE {$table_name} ADD COLUMN languages VARCHAR(255) NULL AFTER language",
            'cpu_cores' => "ALTER TABLE {$table_name} ADD COLUMN cpu_cores INT NULL AFTER languages",
            'ram' => "ALTER TABLE {$table_name} ADD COLUMN ram INT NULL AFTER cpu_cores",
            'do_not_track' => "ALTER TABLE {$table_name} ADD COLUMN do_not_track VARCHAR(20) NULL AFTER ram",
            'cookies_enabled' => "ALTER TABLE {$table_name} ADD COLUMN cookies_enabled VARCHAR(10) NULL AFTER do_not_track",
            'plugins' => "ALTER TABLE {$table_name} ADD COLUMN plugins TEXT NULL AFTER cookies_enabled",
            'user_agent' => "ALTER TABLE {$table_name} ADD COLUMN user_agent TEXT NULL AFTER plugins",
            'platform' => "ALTER TABLE {$table_name} ADD COLUMN platform VARCHAR(100) NULL AFTER user_agent",
            'touch_support' => "ALTER TABLE {$table_name} ADD COLUMN touch_support VARCHAR(10) NULL AFTER platform",
            'max_touch_points' => "ALTER TABLE {$table_name} ADD COLUMN max_touch_points INT NULL AFTER touch_support",
            'captured_at' => "ALTER TABLE {$table_name} ADD COLUMN captured_at DATETIME DEFAULT CURRENT_TIMESTAMP AFTER max_touch_points",
        );

        foreach ( $required_columns as $column => $alter_sql ) {
            $column_exists = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
                    DB_NAME,
                    $table_name,
                    $column
                )
            );

            if ( empty( $column_exists ) ) {
                if ( false !== $wpdb->query( $alter_sql ) ) {
                    $result['columns_added'][] = $column;
                    error_log( "[EIPSI Forms] Added missing column '{$column}' to {$table_name}" );
                } else {
                    $result['columns_missing'][] = $column;
                    $result['success'] = false;
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                        error_log( 'EIPSI Schema Manager: Failed to add column ' . $column . ' - ' . $wpdb->last_error );
                    }
                }
            }
        }

        // Ensure indices exist
        self::ensure_local_index( $table_name, 'submission_id' );
        self::ensure_local_index( $table_name, 'participant_id' );
        self::ensure_local_index( $table_name, 'captured_at' );

        return $result;
    }


// =============================================================================
// SCHEMA STATUS MONITORING METHODS (v1.6.0+)
// =============================================================================

/**
 * Get detailed status for a single table
 * 
 * @since 1.6.0
 * @param string $table_name Table name (without prefix)
 * @return array Detailed table status
 */
public static function get_detailed_table_status($table_name) {
    global $wpdb;
    $full_table_name = $wpdb->prefix . $table_name;
    
    $result = array(
        'table_name' => $table_name,
        'full_table_name' => $full_table_name,
        'exists' => false,
        'row_count' => 0,
        'columns' => array(),
        'required_columns' => array(),
        'missing_columns' => array(),
        'indexes' => array(),
        'size_mb' => 0,
        'status' => 'error', // ok, warning, error
        'issues' => array()
    );
    
    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$full_table_name}'");
    $result['exists'] = !empty($table_exists);
    
    if (!$result['exists']) {
        $result['issues'][] = 'Tabla no existe';
        return $result;
    }
    
    // Get row count
    $result['row_count'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$full_table_name}");
    
    // Get columns
    $columns = $wpdb->get_results("SHOW COLUMNS FROM {$full_table_name}", ARRAY_A);
    $result['columns'] = array_column($columns, 'Field');
    
    // Get required columns based on table type
    $result['required_columns'] = self::get_required_columns_for_table($table_name);
    $result['missing_columns'] = array_diff($result['required_columns'], $result['columns']);
    
    if (!empty($result['missing_columns'])) {
        $result['issues'][] = 'Columnas faltantes: ' . implode(', ', $result['missing_columns']);
    }
    
    // Get indexes
    $indexes = $wpdb->get_results("SHOW INDEX FROM {$full_table_name}", ARRAY_A);
    $result['indexes'] = array_unique(array_column($indexes, 'Key_name'));
    
    // Get table size
    $size_result = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb 
            FROM information_schema.TABLES 
            WHERE table_schema = %s AND table_name = %s",
            DB_NAME,
            $full_table_name
        )
    );
    $result['size_mb'] = $size_result ? floatval($size_result->size_mb) : 0;
    
    // Determine status
    if (empty($result['missing_columns'])) {
        $result['status'] = 'ok';
    } else {
        // Check if missing columns are critical
        $critical_missing = array_intersect($result['missing_columns'], self::get_critical_columns_for_table($table_name));
        if (!empty($critical_missing)) {
            $result['status'] = 'error';
        } else {
            $result['status'] = 'warning';
        }
    }
    
    return $result;
}

/**
 * Get schema map for repair helpers
 *
 * @since 1.6.0
 * @return array
 */
private static function get_schema_map() {
    return array(
        'vas_form_results' => array(
            'form_id' => 'varchar(20) DEFAULT NULL',
            'participant_id' => 'varchar(20) DEFAULT NULL',
            'survey_id' => 'INT(11) DEFAULT NULL',
            'wave_index' => 'INT(11) DEFAULT NULL',
            'session_id' => 'varchar(255) DEFAULT NULL',
            'user_fingerprint' => 'varchar(255) DEFAULT NULL',
            'form_name' => 'varchar(255) NOT NULL',
            'form_responses' => 'longtext DEFAULT NULL',
            'created_at' => 'datetime DEFAULT CURRENT_TIMESTAMP',
            'submitted_at' => 'datetime DEFAULT NULL',
            'ip_address' => 'varchar(100) DEFAULT NULL',
            'device' => 'varchar(50) DEFAULT NULL',
            'browser' => 'varchar(100) DEFAULT NULL',
            'os' => 'varchar(100) DEFAULT NULL',
            'screen_width' => 'int(11) DEFAULT NULL',
            'duration' => 'int(11) DEFAULT NULL',
            'duration_seconds' => 'decimal(8,3) DEFAULT NULL',
            'start_timestamp_ms' => 'bigint(20) DEFAULT NULL',
            'end_timestamp_ms' => 'bigint(20) DEFAULT NULL',
            'metadata' => 'LONGTEXT DEFAULT NULL',
            "status" => "varchar(20) DEFAULT 'submitted'",
            'rct_assigned_variant' => 'varchar(100) DEFAULT NULL',
            'rct_randomization_id' => 'varchar(100) DEFAULT NULL'
        ),
        'vas_form_events' => array(
            'form_id' => 'varchar(255) NOT NULL DEFAULT \'\'',
            'session_id' => 'varchar(255) NOT NULL',
            'event_type' => 'varchar(50) NOT NULL',
            'page_number' => 'int(11) DEFAULT NULL',
            'metadata' => 'text DEFAULT NULL',
            'user_agent' => 'text DEFAULT NULL',
            'created_at' => 'datetime NOT NULL DEFAULT CURRENT_TIMESTAMP'
        ),
        'eipsi_randomization_configs' => array(
            'randomization_id' => 'varchar(255) NOT NULL',
            'formularios' => 'LONGTEXT NOT NULL',
            'probabilidades' => 'LONGTEXT',
            "method" => "varchar(20) DEFAULT 'seeded'",
            'manual_assignments' => 'LONGTEXT',
            'show_instructions' => 'tinyint(1) DEFAULT 0',
            'created_at' => 'datetime DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        ),
        'eipsi_randomization_assignments' => array(
            'randomization_id' => 'varchar(255) NOT NULL',
            'config_id' => 'varchar(255) NOT NULL',
            'user_fingerprint' => 'varchar(255) NOT NULL',
            'assigned_form_id' => 'bigint(20) unsigned NOT NULL',
            'assigned_at' => 'datetime DEFAULT CURRENT_TIMESTAMP',
            'last_access' => 'datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'access_count' => 'int(11) DEFAULT 1'
        ),
        'survey_studies' => array(
            'study_code' => 'VARCHAR(50) NOT NULL',
            'study_name' => 'VARCHAR(255) NOT NULL',
            'description' => 'TEXT',
            'principal_investigator_id' => 'BIGINT(20) UNSIGNED',
            "status" => "ENUM('active', 'completed', 'paused', 'archived') DEFAULT 'active'",
            'config' => 'JSON',
            'created_at' => 'DATETIME NOT NULL',
            'updated_at' => 'DATETIME NOT NULL'
        ),
        'survey_participants' => array(
            'survey_id' => 'INT(11)',
            'email' => 'VARCHAR(255) NOT NULL',
            'password_hash' => 'VARCHAR(255)',
            'first_name' => 'VARCHAR(100)',
            'last_name' => 'VARCHAR(100)',
            'created_at' => 'DATETIME NOT NULL',
            'last_login_at' => 'DATETIME',
            'is_active' => 'TINYINT(1) DEFAULT 1',
            'updated_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        ),
        'survey_sessions' => array(
            'token' => 'VARCHAR(255) NOT NULL',
            'participant_id' => 'BIGINT(20) UNSIGNED NOT NULL',
            'survey_id' => 'INT(11)',
            'ip_address' => 'VARCHAR(45)',
            'user_agent' => 'VARCHAR(500)',
            'expires_at' => 'DATETIME NOT NULL',
            'created_at' => 'DATETIME NOT NULL'
        ),
        'survey_waves' => array(
            'study_id' => 'BIGINT(20) UNSIGNED NOT NULL',
            'wave_index' => 'INT NOT NULL',
            'name' => 'VARCHAR(255) NOT NULL',
            'form_id' => 'BIGINT(20) UNSIGNED NOT NULL',
            'start_date' => 'DATETIME NULL',
            'due_date' => 'DATETIME NULL',
            'reminder_days' => 'INT DEFAULT 3',
            'retry_enabled' => 'TINYINT(1) DEFAULT 1',
            'retry_days' => 'INT DEFAULT 7',
            'max_retries' => 'INT DEFAULT 3',
            'has_time_limit' => 'TINYINT(1) DEFAULT 0',
            'completion_time_limit' => 'INT DEFAULT NULL',
            "status" => "ENUM('draft', 'active', 'completed', 'paused') DEFAULT 'draft'",
            'is_mandatory' => 'TINYINT(1) DEFAULT 1',
            'created_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        ),
        'survey_assignments' => array(
            'study_id' => 'BIGINT(20) UNSIGNED NOT NULL',
            'wave_id' => 'BIGINT(20) UNSIGNED NOT NULL',
            'participant_id' => 'BIGINT(20) UNSIGNED NOT NULL',
            "status" => "ENUM('pending', 'in_progress', 'submitted', 'skipped', 'expired') DEFAULT 'pending'",
            'assigned_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
            'first_viewed_at' => 'DATETIME NULL',
            'submitted_at' => 'DATETIME NULL',
            'reminder_count' => 'INT DEFAULT 0',
            'last_reminder_sent' => 'DATETIME NULL',
            'retry_count' => 'INT DEFAULT 0',
            'last_retry_sent' => 'DATETIME NULL',
            'due_at' => 'DATETIME NULL',
            'created_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        ),
        'survey_magic_links' => array(
            'survey_id' => 'BIGINT(20) UNSIGNED NOT NULL',
            'participant_id' => 'BIGINT(20) UNSIGNED NOT NULL',
            'token_hash' => 'VARCHAR(255) NOT NULL',
            'token_plain' => 'VARCHAR(36)',
            'expires_at' => 'DATETIME NOT NULL',
            'used_at' => 'DATETIME NULL',
            'created_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP'
        ),
        'survey_email_log' => array(
            'participant_id' => 'BIGINT(20) UNSIGNED NOT NULL',
            'survey_id' => 'INT(11)',
            "email_type" => "ENUM('reminder', 'welcome', 'confirmation', 'magic_link', 'recovery', 'custom', 'audit_log') DEFAULT 'custom'",
            'wave_id' => 'BIGINT(20) UNSIGNED',
            'recipient_email' => 'VARCHAR(255)',
            'subject' => 'VARCHAR(500)',
            'content' => 'TEXT',
            'sent_at' => 'DATETIME NOT NULL',
            "status" => "ENUM('sent', 'failed', 'bounced', 'audit') DEFAULT 'sent'",
            'error_message' => 'TEXT',
            'magic_link_used' => 'TINYINT(1) DEFAULT 0',
            'metadata' => 'JSON',
            'created_at' => 'DATETIME'
        ),
        'survey_audit_log' => array(
            'survey_id' => 'BIGINT(20) UNSIGNED NOT NULL',
            'participant_id' => 'BIGINT(20) UNSIGNED NULL',
            'action' => 'VARCHAR(100) NOT NULL',
            "actor_type" => "ENUM('admin', 'system') DEFAULT 'system'",
            'actor_id' => 'BIGINT(20) UNSIGNED NULL',
            'actor_username' => 'VARCHAR(255) NULL',
            'ip_address' => 'VARCHAR(45) NULL',
            'metadata' => 'JSON NULL',
            'created_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP'
        ),
        'survey_email_confirmations' => array(
            'survey_id' => 'INT(11) NOT NULL',
            'participant_id' => 'BIGINT(20) UNSIGNED NOT NULL',
            'email' => 'VARCHAR(255) NOT NULL',
            'token_hash' => 'VARCHAR(64) NOT NULL',
            'token_plain' => 'VARCHAR(64) NOT NULL',
            'expires_at' => 'DATETIME NOT NULL',
            'confirmed_at' => 'DATETIME NULL',
            'created_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP'
        ),
        'eipsi_longitudinal_pools' => array(
            'pool_name' => 'VARCHAR(255) NOT NULL',
            'pool_description' => 'TEXT',
            'studies' => 'JSON',
            'probabilities' => 'JSON',
            "method" => "ENUM('seeded', 'pure-random') DEFAULT 'seeded'",
            "status" => "ENUM('active', 'inactive') DEFAULT 'active'",
            'created_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        ),
        'eipsi_longitudinal_pool_assignments' => array(
            'pool_id' => 'BIGINT(20) UNSIGNED NOT NULL',
            'participant_id' => 'BIGINT(20) UNSIGNED NOT NULL',
            'assigned_study_id' => 'BIGINT(20) UNSIGNED NOT NULL',
            'assigned_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
            "status" => "ENUM('assigned', 'completed', 'dropped') DEFAULT 'assigned'"
        ),
        'survey_participant_access_log' => array(
            'participant_id' => 'BIGINT(20) UNSIGNED NOT NULL',
            'study_id' => 'INT(11) NOT NULL',
            "action_type" => "ENUM('registration', 'login', 'login_failed', 'magic_link_clicked', 'magic_link_sent', 'wave_started', 'wave_completed', 'logout', 'session_expired', 'password_reset_requested', 'password_reset_completed') NOT NULL",
            'ip_address' => 'VARCHAR(45) NOT NULL',
            'user_agent' => 'VARCHAR(500)',
            'metadata' => 'JSON',
            'created_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP'
        ),
        'eipsi_device_data' => array(
            'submission_id' => 'BIGINT(20) UNSIGNED NULL',
            'participant_id' => 'BIGINT(20) UNSIGNED NULL',
            'canvas_fingerprint' => 'VARCHAR(255) NULL',
            'webgl_renderer' => 'VARCHAR(255) NULL',
            'screen_resolution' => 'VARCHAR(50) NULL',
            'screen_depth' => 'INT NULL',
            'pixel_ratio' => 'DECIMAL(4,2) NULL',
            'timezone' => 'VARCHAR(100) NULL',
            'timezone_offset' => 'INT NULL',
            'language' => 'VARCHAR(50) NULL',
            'languages' => 'VARCHAR(255) NULL',
            'cpu_cores' => 'INT NULL',
            'ram' => 'INT NULL',
            'do_not_track' => 'VARCHAR(20) NULL',
            'cookies_enabled' => 'VARCHAR(10) NULL',
            'plugins' => 'TEXT NULL',
            'user_agent' => 'TEXT NULL',
            'platform' => 'VARCHAR(100) NULL',
            'touch_support' => 'VARCHAR(10) NULL',
            'max_touch_points' => 'INT NULL',
            'captured_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP'
        )
    );
}

/**
 * Get required columns for each table
 * 
 * @since 1.6.0
 * @param string $table_name
 * @return array Required columns
 */
private static function get_required_columns_for_table($table_name) {
    $definitions = self::get_schema_map();

    return isset($definitions[$table_name]) ? array_keys($definitions[$table_name]) : array();
}


/**
 * Get critical columns that if missing should trigger error status
 * 
 * @since 1.6.0
 * @param string $table_name
 * @return array Critical columns
 */
private static function get_critical_columns_for_table($table_name) {
    $critical = array(
        'vas_form_results' => array('id', 'form_name', 'created_at', 'form_responses'),
        'vas_form_events' => array('id', 'form_id', 'event_type', 'created_at'),
        'survey_studies' => array('id', 'study_code', 'study_name', 'status'),
        'survey_participants' => array('id', 'survey_id', 'email'),
        'survey_waves' => array('id', 'study_id', 'form_id'),
        'survey_assignments' => array('id', 'study_id', 'wave_id', 'participant_id')
    );
    
    return isset($critical[$table_name]) ? $critical[$table_name] : array();
}

/**
 * Get status for all monitored tables
 * 
 * @since 1.6.0
 * @return array All tables status
 */
public static function get_all_tables_status() {
    $tables = array(
        'vas_form_results',
        'vas_form_events',
        'eipsi_randomization_configs',
        'eipsi_randomization_assignments',
        'survey_studies',
        'survey_participants',
        'survey_sessions',
        'survey_waves',
        'survey_assignments',
        'survey_magic_links',
        'survey_email_log',
        'survey_audit_log',
        'survey_email_confirmations',
        'eipsi_longitudinal_pools',
        'eipsi_longitudinal_pool_assignments',
        'survey_participant_access_log',
        'eipsi_device_data'
    );
    
    $status = array();
    foreach ($tables as $table) {
        $status[$table] = self::get_detailed_table_status($table);
    }
    
    return $status;
}

/**
 * Repair a single table (create or add missing columns)
 * 
 * @since 1.6.0
 * @param string $table_name Table name (without prefix)
 * @return array Repair result
 */
public static function repair_single_table($table_name) {
    global $wpdb;
    $full_table_name = $wpdb->prefix . $table_name;
    
    $result = array(
        'success' => false,
        'table_name' => $table_name,
        'created' => false,
        'columns_added' => array(),
        'columns_missing' => array(),
        'error' => null,
        'message' => ''
    );
    
    // Get current status
    $status = self::get_detailed_table_status($table_name);
    
    if (!$status['exists']) {
        // Table doesn't exist - we need to call the appropriate sync function
        $sync_method = 'sync_local_' . $table_name . '_table';
        
        // Map table names to sync methods
        $sync_map = array(
            'vas_form_results' => 'sync_local_results_table',
            'vas_form_events' => 'sync_local_events_table',
            'eipsi_randomization_configs' => 'sync_local_randomization_configs_table',
            'eipsi_randomization_assignments' => 'sync_local_randomization_assignments_table',
            'survey_studies' => 'sync_local_survey_studies_table',
            'survey_participants' => 'sync_local_survey_participants_table',
            'survey_sessions' => 'sync_local_survey_sessions_table',
            'survey_waves' => 'sync_local_survey_waves_table',
            'survey_assignments' => 'sync_local_survey_assignments_table',
            'survey_magic_links' => 'sync_local_survey_magic_links_table',
            'survey_email_log' => 'sync_local_survey_email_log_table',
            'survey_audit_log' => 'sync_local_survey_audit_log_table',
            'survey_email_confirmations' => 'sync_local_survey_email_confirmations_table',
            'eipsi_longitudinal_pools' => 'sync_local_longitudinal_pools_table',
            'eipsi_longitudinal_pool_assignments' => 'sync_local_longitudinal_pool_assignments_table',
            'survey_participant_access_log' => 'sync_local_survey_participant_access_log_table',
            'eipsi_device_data' => 'sync_local_device_data_table'
        );
        
        if (isset($sync_map[$table_name]) && method_exists('EIPSI_Database_Schema_Manager', $sync_map[$table_name])) {
            $sync_result = call_user_func(array('EIPSI_Database_Schema_Manager', $sync_map[$table_name]));
            $result['created'] = $sync_result['created'] ?? false;
            $result['columns_added'] = $sync_result['columns_added'] ?? array();
            $result['success'] = $sync_result['success'] ?? false;
            $result['error'] = $sync_result['error'] ?? null;
            $result['message'] = $result['created'] ? 'Tabla creada exitosamente' : 'Error al crear tabla';
            
            error_log("[EIPSI Schema Repair] Table {$table_name}: created=" . ($result['created'] ? 'yes' : 'no') .
                      ", columns_added=" . count($result['columns_added']) .
                      ", error=" . ($result['error'] ?? 'none'));
        } else {
            $result['error'] = 'Método de sincronización no encontrado para: ' . $table_name;
            $result['message'] = $result['error'];
        }
    } else {
        // Table exists but has missing columns - add them
        $required_columns = self::get_required_columns_for_table($table_name);
        $missing_columns = array_diff($required_columns, $status['columns']);
        
        if (!empty($missing_columns)) {
            // Get column definitions from the appropriate sync function
            $column_definitions = self::get_column_definitions_for_table($table_name);

            if (empty($column_definitions)) {
                $result['error'] = 'Definiciones de columnas no disponibles para: ' . $table_name;
                $result['message'] = $result['error'];
                return $result;
            }
            
            foreach ($missing_columns as $column) {
                if (isset($column_definitions[$column])) {
                    $alter_sql = "ALTER TABLE {$full_table_name} ADD COLUMN {$column} {$column_definitions[$column]}";
                    
                    if (false !== $wpdb->query($alter_sql)) {
                        $result['columns_added'][] = $column;
                        error_log("[EIPSI Schema Repair] Added column {$column} to {$table_name}");
                    } else {
                        $result['columns_missing'][] = $column;
                        error_log("[EIPSI Schema Repair] Failed to add column {$column} to {$table_name}: " . $wpdb->last_error);
                    }
                } else {
                    $result['columns_missing'][] = $column;
                    error_log("[EIPSI Schema Repair] Missing definition for column {$column} in {$table_name}");
                }
            }
        }
        
        $result['success'] = empty($result['columns_missing']);
        $result['message'] = empty($result['columns_added'])
            ? 'No se requirieron cambios'
            : 'Columnas agregadas: ' . implode(', ', $result['columns_added']);
    }
    
    // Update last verification timestamp
    update_option('eipsi_schema_last_verified', current_time('mysql'));
    
    // Fix collations automatically
    $collation_fix = self::fix_collations();
    if ($collation_fix['total_fixed'] > 0) {
        error_log('[EIPSI] Fixed ' . $collation_fix['total_fixed'] . ' table collations during schema repair');
    }
    
    return $result;
}

/**
 * Get column definitions for a table
 * 
 * @since 1.6.0
 * @param string $table_name
 * @return array Column definitions
 */
private static function get_column_definitions_for_table($table_name) {
    $definitions = self::get_schema_map();

    return isset($definitions[$table_name]) ? $definitions[$table_name] : array();
}


/**
 * Get schema health summary
 * 
 * @since 1.6.0
 * @return array Health summary
 */
public static function get_schema_health_summary() {
    $all_tables = self::get_all_tables_status();
    
    $summary = array(
        'total_tables' => count($all_tables),
        'healthy_tables' => 0,
        'warning_tables' => 0,
        'error_tables' => 0,
        'total_size_mb' => 0,
        'total_rows' => 0,
        'last_verified' => get_option('eipsi_schema_last_verified', null),
        'issues' => array()
    );
    
    foreach ($all_tables as $table) {
        if ($table['status'] === 'ok') {
            $summary['healthy_tables']++;
        } elseif ($table['status'] === 'warning') {
            $summary['warning_tables']++;
        } else {
            $summary['error_tables']++;
        }
        
        $summary['total_size_mb'] += $table['size_mb'];
        $summary['total_rows'] += $table['row_count'];
        
        if (!empty($table['issues'])) {
            $summary['issues'][$table['table_name']] = $table['issues'];
        }
    }
    
    // Calculate health score (0-100)
    if ($summary['total_tables'] > 0) {
        $summary['health_score'] = round(
            ($summary['healthy_tables'] * 100 + $summary['warning_tables'] * 50) / $summary['total_tables']
        );
    } else {
        $summary['health_score'] = 0;
    }
    
    return $summary;
}

    // =================================================================
    // TASK 6: Safe Table Creation Order + FK Guards
    // =================================================================
    
    /**
     * TASK 6: Get table creation order respecting FK dependencies
     * 
     * Order:
     * - Level 0: Root tables (survey_studies, survey_participants, eipsi_longitudinal_pools)
     * - Level 1: Tables depending on root (survey_sessions, survey_waves, etc.)
     * - Level 2: Tables depending on level 1 (survey_assignments, pool_assignments)
     * 
     * @since 1.6.0
     * @return array Ordered table list with dependencies
     */
    private static function get_table_creation_order() {
        return array(
            // Level 0: Root tables (no FK dependencies)
            'survey_studies' => array(
                'level' => 0,
                'dependencies' => array(),
                'sync_method' => 'sync_local_survey_studies_table',
            ),
            'survey_participants' => array(
                'level' => 0,
                'dependencies' => array(),
                'sync_method' => 'sync_local_survey_participants_table',
            ),
            'eipsi_longitudinal_pools' => array(
                'level' => 0,
                'dependencies' => array(),
                'sync_method' => 'sync_local_longitudinal_pools_table',
            ),
            // Independent tables (no FK constraints or optional FKs)
            'survey_audit_log' => array(
                'level' => 0,
                'dependencies' => array(),
                'sync_method' => 'sync_local_survey_audit_log_table',
            ),
            'eipsi_device_data' => array(
                'level' => 0,
                'dependencies' => array(),
                'sync_method' => 'sync_local_device_data_table',
            ),
            // Level 1: Tables depending on root tables
            'survey_sessions' => array(
                'level' => 1,
                'dependencies' => array('survey_participants'),
                'sync_method' => 'sync_local_survey_sessions_table',
            ),
            'survey_waves' => array(
                'level' => 1,
                'dependencies' => array('survey_studies'),
                'sync_method' => 'sync_local_survey_waves_table',
            ),
            'survey_magic_links' => array(
                'level' => 1,
                'dependencies' => array('survey_participants'),
                'sync_method' => 'sync_local_survey_magic_links_table',
            ),
            'survey_email_log' => array(
                'level' => 1,
                'dependencies' => array('survey_participants'),
                'sync_method' => 'sync_local_survey_email_log_table',
            ),
            'survey_email_confirmations' => array(
                'level' => 1,
                'dependencies' => array('survey_participants'),
                'sync_method' => 'sync_local_survey_email_confirmations_table',
            ),
            'survey_participant_access_log' => array(
                'level' => 1,
                'dependencies' => array('survey_participants'),
                'sync_method' => 'sync_local_survey_participant_access_log_table',
            ),
            // Level 2: Tables depending on level 1 tables
            'survey_assignments' => array(
                'level' => 2,
                'dependencies' => array('survey_studies', 'survey_waves', 'survey_participants'),
                'sync_method' => 'sync_local_survey_assignments_table',
            ),
            'eipsi_longitudinal_pool_assignments' => array(
                'level' => 2,
                'dependencies' => array('eipsi_longitudinal_pools', 'survey_participants', 'survey_studies'),
                'sync_method' => 'sync_local_longitudinal_pool_assignments_table',
            ),
        );
    }
    
    /**
     * TASK 6: Ensure parent tables exist before creating child table
     * 
     * @since 1.6.0
     * @param string $table_name Table to check dependencies for
     * @param array $dependencies Array of required parent tables
     * @return array Result with success status and missing tables
     */
    private static function ensure_parent_tables_exist( $table_name, $dependencies ) {
        global $wpdb;
        
        $result = array(
            'success' => true,
            'missing_tables' => array(),
            'created_tables' => array(),
        );
        
        if ( empty( $dependencies ) ) {
            return $result;
        }
        
        $table_order = self::get_table_creation_order();
        
        foreach ( $dependencies as $dep_table ) {
            $full_table_name = $wpdb->prefix . $dep_table;
            
            // Check if parent table exists
            $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$full_table_name}'" );
            
            if ( empty( $table_exists ) ) {
                // Parent doesn't exist - try to create it first
                error_log( "[EIPSI Task 6] Parent table {$dep_table} missing, attempting to create before {$table_name}" );
                
                if ( isset( $table_order[ $dep_table ] ) ) {
                    $sync_method = $table_order[ $dep_table ]['sync_method'];
                    
                    if ( method_exists( 'EIPSI_Database_Schema_Manager', $sync_method ) ) {
                        $sync_result = call_user_func( array( 'EIPSI_Database_Schema_Manager', $sync_method ) );
                        
                        if ( ! empty( $sync_result['created'] ) ) {
                            $result['created_tables'][] = $dep_table;
                            error_log( "[EIPSI Task 6] Successfully created parent table {$dep_table}" );
                        } elseif ( ! empty( $sync_result['exists'] ) ) {
                            // Table was created by another process
                            error_log( "[EIPSI Task 6] Parent table {$dep_table} now exists" );
                        } else {
                            $result['missing_tables'][] = $dep_table;
                            $result['success'] = false;
                            error_log( "[EIPSI Task 6] FAILED to create parent table {$dep_table}: " . ( $sync_result['error'] ?? 'Unknown error' ) );
                        }
                    } else {
                        $result['missing_tables'][] = $dep_table;
                        $result['success'] = false;
                        error_log( "[EIPSI Task 6] Sync method not found for parent table {$dep_table}" );
                    }
                } else {
                    $result['missing_tables'][] = $dep_table;
                    $result['success'] = false;
                    error_log( "[EIPSI Task 6] Unknown parent table dependency: {$dep_table}" );
                }
            }
        }
        
        return $result;
    }
    
    /**
     * TASK 6: Add foreign keys in phase 2 (after all tables exist)
     * 
     * @since 1.6.0
     * @return array Results for each FK attempt
     */
    private static function add_foreign_keys_phase2() {
        global $wpdb;
        
        $results = array(
            'success' => true,
            'added' => array(),
            'failed' => array(),
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
            'eipsi_longitudinal_pool_assignments' => array(
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
                    'sql' => "ALTER TABLE {table} ADD CONSTRAINT fk_pool_assignments_study FOREIGN KEY (assigned_study_id) REFERENCES {prefix}survey_studies(id) ON DELETE CASCADE",
                ),
            ),
        );
        
        foreach ( $fk_definitions as $table_name => $fks ) {
            $full_table_name = $wpdb->prefix . $table_name;
            
            // Check if table exists
            $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$full_table_name}'" );
            if ( empty( $table_exists ) ) {
                foreach ( $fks as $fk_name => $fk_info ) {
                    $results['skipped'][] = "{$table_name}.{$fk_name}";
                    error_log( "[EIPSI Task 6] Skipping FK {$fk_name}: table {$table_name} doesn't exist" );
                }
                continue;
            }
            
            foreach ( $fks as $fk_name => $fk_info ) {
                // Check if referenced table exists
                $ref_table_name = $wpdb->prefix . $fk_info['referenced_table'];
                $ref_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$ref_table_name}'" );
                
                if ( empty( $ref_exists ) ) {
                    $results['skipped'][] = "{$table_name}.{$fk_name}";
                    error_log( "[EIPSI Task 6] Skipping FK {$fk_name}: referenced table {$fk_info['referenced_table']} doesn't exist" );
                    continue;
                }
                
                // Check if FK already exists
                if ( function_exists( 'eipsi_longitudinal_fk_exists' ) && eipsi_longitudinal_fk_exists( $full_table_name, $fk_name ) ) {
                    $results['skipped'][] = "{$table_name}.{$fk_name}";
                    continue;
                }
                
                // Prepare SQL
                $sql = str_replace(
                    array( '{table}', '{prefix}' ),
                    array( $full_table_name, $wpdb->prefix ),
                    $fk_info['sql']
                );
                
                // Try to add FK
                if ( function_exists( 'eipsi_longitudinal_ensure_foreign_key' ) ) {
                    $fk_result = eipsi_longitudinal_ensure_foreign_key( $full_table_name, $fk_name, $sql );
                    
                    if ( $fk_result ) {
                        $results['added'][] = "{$table_name}.{$fk_name}";
                    } else {
                        $results['failed'][] = "{$table_name}.{$fk_name}";
                        // Don't mark success as false - FKs are optional
                    }
                } else {
                    // Direct attempt
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
                    $result = @$wpdb->query( $sql );
                    
                    if ( $result === false ) {
                        $results['failed'][] = "{$table_name}.{$fk_name}";
                        error_log( "[EIPSI Task 6] Failed to add FK {$fk_name}: " . $wpdb->last_error );
                    } else {
                        $results['added'][] = "{$table_name}.{$fk_name}";
                        error_log( "[EIPSI Task 6] Added FK {$fk_name}" );
                    }
                }
            }
        }
        
        return $results;
    }
    
    /**
     * TASK 6: Sync longitudinal tables in safe order
     * 
     * @since 1.6.0
     * @return array Sync results for all tables
     */
    public static function sync_longitudinal_tables_safe() {
        $results = array(
            'success' => true,
            'tables_created' => array(),
            'tables_existing' => array(),
            'tables_failed' => array(),
            'fk_phase2' => null,
            'errors' => array(),
        );
        
        $table_order = self::get_table_creation_order();
        
        // Sort by level (0, 1, 2) to ensure proper creation order
        uasort( $table_order, function( $a, $b ) {
            return $a['level'] - $b['level'];
        });
        
        // Phase 1: Create tables in order
        foreach ( $table_order as $table_name => $table_info ) {
            // TASK 6: Ensure parent tables exist before creating child
            if ( ! empty( $table_info['dependencies'] ) ) {
                $parent_check = self::ensure_parent_tables_exist( $table_name, $table_info['dependencies'] );
                
                if ( ! $parent_check['success'] ) {
                    $results['tables_failed'][] = $table_name;
                    $results['errors'][] = "Cannot create {$table_name}: missing parent tables " . implode( ', ', $parent_check['missing_tables'] );
                    error_log( "[EIPSI Task 6] Skipping {$table_name}: missing parents " . implode( ', ', $parent_check['missing_tables'] ) );
                    continue;
                }
            }
            
            // Sync the table
            $sync_method = $table_info['sync_method'];
            
            if ( method_exists( 'EIPSI_Database_Schema_Manager', $sync_method ) ) {
                $sync_result = call_user_func( array( 'EIPSI_Database_Schema_Manager', $sync_method ) );
                
                if ( ! empty( $sync_result['created'] ) ) {
                    $results['tables_created'][] = $table_name;
                    error_log( "[EIPSI Task 6] Created table: {$table_name}" );
                } elseif ( ! empty( $sync_result['exists'] ) ) {
                    $results['tables_existing'][] = $table_name;
                } elseif ( ! $sync_result['success'] ) {
                    $results['tables_failed'][] = $table_name;
                    $results['errors'][] = "Failed to sync {$table_name}: " . ( $sync_result['error'] ?? 'Unknown error' );
                    $results['success'] = false;
                }
            }
        }
        
        // Phase 2: Add foreign keys after all tables exist
        $results['fk_phase2'] = self::add_foreign_keys_phase2();
        
        // Log summary
        error_log( sprintf(
            '[EIPSI Task 6] Sync complete: %d created, %d existing, %d failed, %d FKs added',
            count( $results['tables_created'] ),
            count( $results['tables_existing'] ),
            count( $results['tables_failed'] ),
            count( $results['fk_phase2']['added'] )
        ) );
        
        return $results;
    }

    /**
     * Fix collations for all plugin tables
     * Ensures all tables use utf8mb4_unicode_ci collation
     * 
     * @return array Results with fixed tables list
     */
    public static function fix_collations() {
        global $wpdb;
        
        $plugin_tables = array(
            'vas_form_results',
            'survey_sessions', 
            'survey_participants',
            'survey_waves',
            'survey_assignments',
            'survey_studies',
            'survey_magic_links',
            'survey_email_log',
            'survey_audit_log'
        );
        
        $fixed_tables = array();
        $target_collation = 'utf8mb4_unicode_ci';
        
        foreach ($plugin_tables as $table) {
            $full_table_name = $wpdb->prefix . $table;
            
            // Get current table status
            $status = $wpdb->get_row(
                $wpdb->prepare(
                    "SHOW TABLE STATUS LIKE %s",
                    $full_table_name
                ),
                ARRAY_A
            );
            
            if ($status && $status['Collation'] !== $target_collation) {
                // Fix collation
                $alter_sql = $wpdb->prepare(
                    "ALTER TABLE %i CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
                    $full_table_name
                );
                
                $result = $wpdb->query($alter_sql);
                
                if ($result !== false) {
                    $fixed_tables[] = array(
                        'table' => $full_table_name,
                        'old_collation' => $status['Collation'],
                        'new_collation' => $target_collation,
                        'success' => true
                    );
                    
                    error_log("[EIPSI] Fixed collation for table {$full_table_name}: {$status['Collation']} -> {$target_collation}");
                } else {
                    $fixed_tables[] = array(
                        'table' => $full_table_name,
                        'old_collation' => $status['Collation'],
                        'new_collation' => $target_collation,
                        'success' => false,
                        'error' => $wpdb->last_error
                    );
                    
                    error_log("[EIPSI] Failed to fix collation for table {$full_table_name}: {$wpdb->last_error}");
                }
            }
        }
        
        return array(
            'success' => true,
            'fixed_tables' => $fixed_tables,
            'total_fixed' => count($fixed_tables)
        );
    }

    /**
     * Check if any tables need collation fix
     * Detects tables that don't use utf8mb4_unicode_ci collation
     * 
     * @return array Array of tables needing collation fix with their current collations
     * @since 1.6.1
     */
    public static function check_collation_issues() {
        global $wpdb;
        
        $plugin_tables = array(
            'vas_form_results',
            'vas_form_events',
            'eipsi_randomization_configs',
            'eipsi_randomization_assignments',
            'survey_studies',
            'survey_participants',
            'survey_sessions',
            'survey_waves',
            'survey_assignments',
            'survey_magic_links',
            'survey_email_log',
            'survey_audit_log',
            'survey_email_confirmations',
            'eipsi_longitudinal_pools',
            'eipsi_longitudinal_pool_assignments',
            'survey_participant_access_log',
            'eipsi_device_data'
        );
        
        $issues = array();
        $target_collation = 'utf8mb4_unicode_ci';
        
        foreach ($plugin_tables as $table) {
            $full_table_name = $wpdb->prefix . $table;
            
            // Check if table exists
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$full_table_name}'");
            if (empty($table_exists)) {
                continue;
            }
            
            // Get current table status
            $status = $wpdb->get_row(
                $wpdb->prepare(
                    "SHOW TABLE STATUS LIKE %s",
                    $full_table_name
                ),
                ARRAY_A
            );
            
            if ($status && $status['Collation'] !== $target_collation) {
                $issues[] = array(
                    'table' => $table,
                    'full_table_name' => $full_table_name,
                    'current_collation' => $status['Collation'],
                    'target_collation' => $target_collation
                );
            }
        }
        
        return array(
            'needs_fix' => !empty($issues),
            'issues_count' => count($issues),
            'issues' => $issues
        );
    }

    /**
     * Execute maintenance SQL statements
     * Allows running safe maintenance queries on the database
     * 
     * @param array $sql_statements Array of SQL statements to execute
     * @return array Results for each statement
     * @since 1.6.1
     */
    public static function execute_maintenance_sql($sql_statements) {
        global $wpdb;
        
        $results = array();
        $wpdb->suppress_errors(true);
        
        foreach ($sql_statements as $index => $sql) {
            $sql = trim($sql);
            if (empty($sql)) {
                continue;
            }
            
            // Only allow safe maintenance statements
            $allowed_prefixes = array(
                'UPDATE wp_survey_',
                'UPDATE wp_vas_form_',
                'UPDATE wp_eipsi_',
                'ALTER TABLE wp_survey_',
                'ALTER TABLE wp_vas_form_',
                'ALTER TABLE wp_eipsi_',
                'DELETE FROM wp_survey_',
                'DELETE FROM wp_vas_form_',
                'DELETE FROM wp_eipsi_',
                'SELECT * FROM wp_survey_',
                'SELECT * FROM wp_vas_form_',
                'SELECT * FROM wp_eipsi_',
                'SELECT COUNT',
                'SELECT id FROM',
            );
            
            $is_allowed = false;
            $upper_sql = strtoupper(substr($sql, 0, 100));
            
            foreach ($allowed_prefixes as $prefix) {
                $upper_prefix = strtoupper($prefix);
                if (strpos($upper_sql, $upper_prefix) !== false) {
                    $is_allowed = true;
                    break;
                }
            }
            
            // Additional safety check: must be related to plugin tables
            $is_plugin_table = (
                strpos($upper_sql, 'WP_SURVEY_') !== false ||
                strpos($upper_sql, 'WP_VAS_FORM_') !== false ||
                strpos($upper_sql, 'WP_EIPSI_') !== false
            );
            
            if (!$is_allowed || !$is_plugin_table) {
                $results[] = array(
                    'index' => $index,
                    'sql_preview' => substr($sql, 0, 60) . '...',
                    'success' => false,
                    'error' => 'Statement not allowed for security reasons. Only plugin table operations permitted.',
                    'affected_rows' => 0
                );
                continue;
            }
            
            // Execute the statement
            $result = $wpdb->query($sql);
            
            if ($result !== false) {
                $results[] = array(
                    'index' => $index,
                    'sql_preview' => substr($sql, 0, 60) . '...',
                    'success' => true,
                    'affected_rows' => $wpdb->rows_affected ?? 0,
                    'last_error' => null
                );
            } else {
                $results[] = array(
                    'index' => $index,
                    'sql_preview' => substr($sql, 0, 60) . '...',
                    'success' => false,
                    'error' => $wpdb->last_error,
                    'affected_rows' => 0
                );
            }
        }
        
        $wpdb->suppress_errors(false);
        
        return array(
            'success' => true,
            'total_statements' => count($sql_statements),
            'executed' => count($results),
            'results' => $results
        );
    }

    /**
     * Sync emergency_submissions table in local WordPress database
     * Stores backup submissions when main table fails
     */
    private static function sync_local_emergency_submissions_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'eipsi_emergency_submissions';
        $charset_collate = $wpdb->get_charset_collate();

        $result = array(
            'success' => true,
            'exists' => false,
            'created' => false,
            'columns_added' => array(),
            'columns_missing' => array(),
            'error' => null,
        );

        // Check if table exists
        $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" );
        $result['exists'] = ! empty( $table_exists );

        if ( ! $result['exists'] ) {
            // Create table
            $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                form_id varchar(50) DEFAULT NULL,
                participant_id varchar(255) DEFAULT NULL,
                session_id varchar(255) DEFAULT NULL,
                survey_id bigint(20) DEFAULT NULL,
                wave_id bigint(20) DEFAULT NULL,
                form_responses longtext DEFAULT NULL,
                form_data longtext DEFAULT NULL,
                metadata longtext DEFAULT NULL,
                device varchar(100) DEFAULT NULL,
                browser varchar(100) DEFAULT NULL,
                os varchar(100) DEFAULT NULL,
                screen_width int(11) DEFAULT NULL,
                duration int(11) DEFAULT NULL,
                ip_address varchar(45) DEFAULT NULL,
                user_agent text DEFAULT NULL,
                error_message text DEFAULT NULL,
                error_code varchar(50) DEFAULT NULL,
                db_type varchar(20) DEFAULT 'wordpress',
                resolved tinyint(1) DEFAULT 0,
                resolved_at datetime DEFAULT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY form_id (form_id),
                KEY participant_id (participant_id),
                KEY session_id (session_id),
                KEY survey_id (survey_id),
                KEY resolved (resolved),
                KEY created_at (created_at)
            ) {$charset_collate};";

            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );

            // Verify table was created
            $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" );
            if ( ! empty( $table_exists ) ) {
                $result['created'] = true;
                $result['exists'] = true;
            } else {
                $result['success'] = false;
                $result['error'] = 'Failed to create emergency submissions table';
            }
        }

        return $result;
    }

}

// =================================================================
// LONGITUDINAL TABLES (v2.1.0 / Task 2.1)
// Utilidades + hook de sync unificado
// =================================================================

/**
 * Verifica si existe una FK por nombre.
 *
 * @param string $table_name Nombre real de la tabla (con prefix)
 * @param string $constraint_name Nombre del constraint
 * @return bool
 */
function eipsi_longitudinal_fk_exists($table_name, $constraint_name) {
    global $wpdb;

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $exists = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT CONSTRAINT_NAME
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = %s
              AND TABLE_NAME = %s
              AND CONSTRAINT_NAME = %s
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
            DB_NAME,
            $table_name,
            $constraint_name
        )
    );

    return !empty($exists);
}

/**
 * Intenta agregar una FK (best effort, sin romper el sitio si falla).
 *
 * @param string $table_name Nombre real de la tabla (con prefix)
 * @param string $constraint_name Nombre del constraint
 * @param string $alter_sql SQL ALTER TABLE ... ADD CONSTRAINT ...
 * @return bool
 */
/**
 * Intenta agregar una FK (best effort, sin romper el sitio si falla).
 *
 * @param string $table_name Nombre real de la tabla (con prefix)
 * @param string $constraint_name Nombre del constraint
 * @param string $alter_sql SQL ALTER TABLE ... ADD CONSTRAINT ...
 * @return bool
 */
function eipsi_longitudinal_ensure_foreign_key($table_name, $constraint_name, $alter_sql) {
    global $wpdb;

    // Check if constraint already exists
    if (eipsi_longitudinal_fk_exists($table_name, $constraint_name)) {
        return true;
    }

    // Suppress errors to prevent site breakage on FK failures
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
    $result = @$wpdb->query($alter_sql);

    if ($result === false) {
        // Log detailed error for debugging
        $error_msg = $wpdb->last_error;
        error_log('[EIPSI] Failed to add FK ' . $constraint_name . ' on ' . $table_name . ': ' . $error_msg);

        // Common error patterns and their meanings:
        // - "Can't create table" or "errno: 150": Referenced table doesn't exist or column type mismatch
        // - "Duplicate key name": Constraint already exists (should have been caught above)
        // - "Cannot add foreign key constraint": Referenced column is not indexed

        if (strpos($error_msg, 'errno: 150') !== false || strpos($error_msg, 'Cannot add foreign key') !== false) {
            error_log('[EIPSI] FK ' . $constraint_name . ' failed: Referenced table may not exist or column types mismatch');
        }

        return false;
    }

    error_log('[EIPSI] Successfully added FK ' . $constraint_name . ' on ' . $table_name);
    return true;
}

/**
 * Verifica si una tabla existe en la base de datos.
 *
 * @param string $table_name Nombre de la tabla (con prefijo)
 * @return bool
 */
function eipsi_table_exists($table_name) {
    global $wpdb;

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $exists = $wpdb->get_var(
        $wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table_name
        )
    );

    return !empty($exists);
}

/**
 * Verifica si una columna existe en una tabla.
 *
 * @param string $table_name Nombre de la tabla
 * @param string $column_name Nombre de la columna
 * @return bool
 */
function eipsi_column_exists_db($table_name, $column_name) {
    global $wpdb;

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $exists = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
            DB_NAME,
            $table_name,
            $column_name
        )
    );

    return !empty($exists);
}

/**
 * Obtiene informacion sobre el tipo de datos de una columna.
 *
 * @param string $table_name Nombre de la tabla
 * @param string $column_name Nombre de la columna
 * @return array|null Informacion de la columna o null si no existe
 */
function eipsi_get_column_info($table_name, $column_name) {
    global $wpdb;

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $info = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT COLUMN_NAME, DATA_TYPE, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_KEY
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
            DB_NAME,
            $table_name,
            $column_name
        ),
        ARRAY_A
    );

    return $info ?: null;
}


/**
 * Sincronización longitudinal unificada.
 * El Schema Manager es el único entrypoint de creación/repair.
 */
add_action('eipsi_sync_longitudinal_tables', function() {
    if (class_exists('EIPSI_Database_Schema_Manager')) {
        EIPSI_Database_Schema_Manager::verify_and_sync_schema();
    }
});
