<?php
if (!defined('ABSPATH')) {
    exit;
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
            
            if ( ! $results_sync['success'] || ! $events_sync['success'] || 
                 ! $rct_configs_sync['success'] || ! $rct_assignments_sync['success'] ||
                 ! $studies_sync['success'] ||
                 ! $participants_sync['success'] || ! $sessions_sync['success'] ||
                 ! $waves_sync['success'] || ! $assignments_sync['success'] || 
                 ! $magic_links_sync['success'] || ! $email_log_sync['success'] ||
                 ! $audit_log_sync['success'] ) {
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
                    $results['errors'][] = $audit_log_sync['error'];
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
            'page_number' => "ALTER TABLE `{$table_name}` ADD COLUMN page_number int(11) DEFAULT NULL AFTER event_type",
            'metadata' => "ALTER TABLE `{$table_name}` ADD COLUMN metadata text DEFAULT NULL AFTER page_number",
            'user_agent' => "ALTER TABLE `{$table_name}` ADD COLUMN user_agent text DEFAULT NULL AFTER metadata",
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
        
        // Events table usually has all required columns from activation
        // But we can add verification here if needed in the future
        
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
    public static function repair_local_schema() {
        global $wpdb;
        
        $results_table = $wpdb->prefix . 'vas_form_results';
        $events_table = $wpdb->prefix . 'vas_form_events';
        $rct_configs_table = $wpdb->prefix . 'eipsi_randomization_configs';
        $rct_assignments_table = $wpdb->prefix . 'eipsi_randomization_assignments';
        
        $repair_log = array(
            'success' => true,
            'results_table' => array(
                'exists' => false,
                'columns_added' => array(),
            ),
            'events_table' => array(
                'exists' => false,
                'columns_added' => array(),
            ),
            'randomization_configs_table' => array(
                'exists' => false,
                'columns_added' => array(),
            ),
            'randomization_assignments_table' => array(
                'exists' => false,
                'columns_added' => array(),
            ),
        );
        
        // Check if tables exist
        $tables_exist = self::local_table_exists( $results_table ) && self::local_table_exists( $events_table );
        $rct_tables_exist = self::local_table_exists( $rct_configs_table ) && self::local_table_exists( $rct_assignments_table );
        
        if ( ! $tables_exist || ! $rct_tables_exist ) {
            // Tables missing - recreate via activation hook
            eipsi_forms_activate();
            
            // Re-check tables
            $repair_log['results_table']['exists'] = self::local_table_exists( $results_table );
            $repair_log['events_table']['exists'] = self::local_table_exists( $events_table );
            $repair_log['randomization_configs_table']['exists'] = self::local_table_exists( $rct_configs_table );
            $repair_log['randomization_assignments_table']['exists'] = self::local_table_exists( $rct_assignments_table );
            
            error_log( '[EIPSI Forms] Schema repair: All tables recreated' );
            return $repair_log;
        }
        
        $repair_log['results_table']['exists'] = true;
        $repair_log['events_table']['exists'] = true;
        $repair_log['randomization_configs_table']['exists'] = true;
        $repair_log['randomization_assignments_table']['exists'] = true;
        
        // Repair results table
        $results_repair = self::repair_local_results_table( $results_table );
        $repair_log['results_table']['columns_added'] = $results_repair;
        
        // Repair events table
        $events_repair = self::repair_local_events_table( $events_table );
        $repair_log['events_table']['columns_added'] = $events_repair;
        
        // Repair randomization configs table
        $rct_configs_repair = self::repair_local_randomization_configs_table( $rct_configs_table );
        $repair_log['randomization_configs_table']['columns_added'] = $rct_configs_repair;
        
        // Repair randomization assignments table
        $rct_assignments_repair = self::repair_local_randomization_assignments_table( $rct_assignments_table );
        $repair_log['randomization_assignments_table']['columns_added'] = $rct_assignments_repair;
        
        // Update version and timestamp
        update_option( 'eipsi_db_schema_version', '1.3.7' );
        update_option( 'eipsi_schema_last_verified', current_time( 'mysql' ) );
        
        $columns_added_total = count( $results_repair ) + count( $events_repair ) + 
                             count( $rct_configs_repair ) + count( $rct_assignments_repair );
        
        if ( $columns_added_total > 0 ) {
            error_log( '[EIPSI Forms] Schema repair completed - Columns added: ' . wp_json_encode( $repair_log ) );
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
            'session_id' => "varchar(255) DEFAULT NULL AFTER wave_index",
            'form_name' => "varchar(255) NOT NULL AFTER session_id",
            'form_responses' => "longtext DEFAULT NULL",
            'metadata' => "LONGTEXT DEFAULT NULL AFTER ip_address",
            'browser' => "varchar(100) DEFAULT NULL AFTER device",
            'os' => "varchar(100) DEFAULT NULL AFTER browser",
            'screen_width' => "int(11) DEFAULT NULL AFTER os",
            'duration_seconds' => "decimal(8,3) DEFAULT NULL AFTER duration"
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

        // v1.4.0 - Composite index for faster lookups
        $wpdb->query( "ALTER TABLE {$table_name} ADD KEY IF NOT EXISTS participant_survey_wave (participant_id, survey_id, wave_index)" );
        
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

        // v1.4.0 - Composite index for faster lookups
        $wpdb->query( "ALTER TABLE {$table_name} ADD KEY IF NOT EXISTS participant_survey_wave (participant_id, survey_id, wave_index)" );
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
        
        $indexes = $wpdb->get_results( "SHOW INDEX FROM {$table} WHERE Column_name = '{$column}'" );
        if ( empty( $indexes ) ) {
            $wpdb->query( "ALTER TABLE {$table} ADD KEY {$column} ({$column})" );
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
                id INT(11) NOT NULL AUTO_INCREMENT,
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
                KEY idx_survey_email (survey_id, email),
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
     * Sync wp_survey_waves table in local DB
     *
     * @since 1.4.0
     * @return array Result with success status and details
     */
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

            status ENUM('draft', 'active', 'completed', 'paused') DEFAULT 'draft',
            is_mandatory TINYINT(1) DEFAULT 1,

            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

            PRIMARY KEY (id),
            KEY idx_study_id (study_id),
            KEY idx_wave_index (study_id, wave_index),
            KEY idx_status (status),
            KEY idx_due_date (due_date),
            UNIQUE KEY uk_study_index (study_id, wave_index)
        ) ENGINE=InnoDB {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
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

            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

            PRIMARY KEY (id),
            KEY idx_study_id (study_id),
            KEY idx_wave_id (wave_id),
            KEY idx_participant_id (participant_id),
            KEY idx_status (status),
            KEY idx_submitted_at (submitted_at),
            UNIQUE KEY uk_wave_participant (wave_id, participant_id)
        ) ENGINE=InnoDB {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
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
                INDEX idx_survey_participant (survey_id, participant_id),
                INDEX idx_token_hash (token_hash),
                INDEX idx_expires_used (expires_at, used_at),
                UNIQUE KEY uk_token_hash (token_hash)
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
                    INDEX idx_survey_participant (survey_id, participant_id),
                    INDEX idx_token_hash (token_hash),
                    INDEX idx_expires_used (expires_at, used_at),
                    UNIQUE KEY uk_token_hash (token_hash)
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
                email_type ENUM('reminder', 'welcome', 'confirmation', 'custom') DEFAULT 'custom',
                wave_id BIGINT(20) UNSIGNED,
                sent_at DATETIME NOT NULL,
                status ENUM('sent', 'failed', 'bounced') DEFAULT 'sent',
                error_message TEXT,
                metadata JSON,
                PRIMARY KEY (id),
                KEY participant_id (participant_id),
                KEY sent_at (sent_at),
                KEY status (status)
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
            'email_type' => "ALTER TABLE {$table_name} ADD COLUMN email_type ENUM('reminder', 'welcome', 'confirmation', 'custom') DEFAULT 'custom' AFTER survey_id",
            'wave_id' => "ALTER TABLE {$table_name} ADD COLUMN wave_id BIGINT(20) UNSIGNED AFTER email_type",
            'sent_at' => "ALTER TABLE {$table_name} ADD COLUMN sent_at DATETIME NOT NULL AFTER wave_id",
            'status' => "ALTER TABLE {$table_name} ADD COLUMN status ENUM('sent', 'failed', 'bounced') DEFAULT 'sent' AFTER sent_at",
            'error_message' => "ALTER TABLE {$table_name} ADD COLUMN error_message TEXT AFTER status",
            'metadata' => "ALTER TABLE {$table_name} ADD COLUMN metadata JSON AFTER error_message",
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
}

// =================================================================
// LONGITUDINAL TABLES (v2.1.0 / Task 2.1)
// Global sync functions + hooks
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
function eipsi_longitudinal_ensure_foreign_key($table_name, $constraint_name, $alter_sql) {
    global $wpdb;

    if (eipsi_longitudinal_fk_exists($table_name, $constraint_name)) {
        return true;
    }

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
    $result = $wpdb->query($alter_sql);

    if ($result === false) {
        error_log('[EIPSI] Failed to add FK ' . $constraint_name . ' on ' . $table_name . ': ' . $wpdb->last_error);
        return false;
    }

    return true;
}

/**
 * Sincronizar tabla wp_survey_waves
 * Crear si no existe, actualizar si es necesario
 */
function eipsi_sync_survey_waves_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'survey_waves';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$table_name} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        study_id BIGINT(20) UNSIGNED NOT NULL,

        wave_index INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        form_id BIGINT(20) UNSIGNED NOT NULL,

        start_date DATETIME NULL,
        due_date DATETIME NULL,

        reminder_days INT DEFAULT 3,
        retry_enabled BOOLEAN DEFAULT 1,
        retry_days INT DEFAULT 7,
        max_retries INT DEFAULT 3,

        status ENUM('draft', 'active', 'completed', 'paused') DEFAULT 'draft',
        is_mandatory BOOLEAN DEFAULT 1,

        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

        PRIMARY KEY (id),
        INDEX idx_study_id (study_id),
        INDEX idx_wave_index (study_id, wave_index),
        INDEX idx_status (status),
        INDEX idx_due_date (due_date),
        UNIQUE KEY uk_study_index (study_id, wave_index)
    ) ENGINE=InnoDB {$charset_collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    // Foreign keys (best effort)
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

    error_log('[EIPSI] Synced survey_waves table');
}

/**
 * Sincronizar tabla wp_survey_assignments
 */
function eipsi_sync_survey_assignments_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'survey_assignments';
    $charset_collate = $wpdb->get_charset_collate();

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

        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

        PRIMARY KEY (id),
        INDEX idx_study_id (study_id),
        INDEX idx_wave_id (wave_id),
        INDEX idx_participant_id (participant_id),
        INDEX idx_status (status),
        INDEX idx_submitted_at (submitted_at),
        UNIQUE KEY uk_wave_participant (wave_id, participant_id)
    ) ENGINE=InnoDB {$charset_collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    // Foreign keys (best effort)
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

    error_log('[EIPSI] Synced survey_assignments table');
}

/**
 * (Compat) Sincronizar tabla wp_survey_participants
 *
 * En esta codebase la creación/repair completa vive dentro del Schema Manager.
 * Lo dejamos como wrapper para que Task 2.1 pueda llamar el mismo entrypoint.
 */
function eipsi_sync_survey_participants_table() {
    if (class_exists('EIPSI_Database_Schema_Manager') && method_exists('EIPSI_Database_Schema_Manager', 'verify_and_sync_schema')) {
        EIPSI_Database_Schema_Manager::verify_and_sync_schema();
    }
}

/**
 * Versioned table creation / migrations
 */
function eipsi_maybe_create_tables() {
    $db_version = get_option('eipsi_longitudinal_db_version', '0');

    if (defined('EIPSI_LONGITUDINAL_DB_VERSION') && version_compare($db_version, EIPSI_LONGITUDINAL_DB_VERSION, '<')) {
        // Crear tablas participantes (si aplica)
        eipsi_sync_survey_participants_table();

        // Crear tablas NUEVAS
        eipsi_sync_survey_waves_table();
        eipsi_sync_survey_assignments_table();
        eipsi_sync_survey_magic_links_table(); // v1.4.1
        eipsi_sync_survey_email_log_table();   // v1.4.1
        eipsi_sync_survey_audit_log_table();   // v1.4.2 TASK 5.1

        // Actualizar versión
        update_option('eipsi_longitudinal_db_version', EIPSI_LONGITUDINAL_DB_VERSION);
        error_log('[EIPSI] Database schema updated to v' . EIPSI_LONGITUDINAL_DB_VERSION);
    }
}

/**
 * Sincronizar tabla wp_survey_magic_links
 * Magic links para Save & Continue Later
 */
function eipsi_sync_survey_magic_links_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'survey_magic_links';
    $charset_collate = $wpdb->get_charset_collate();

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
        INDEX idx_survey_participant (survey_id, participant_id),
        INDEX idx_token_hash (token_hash),
        INDEX idx_expires_used (expires_at, used_at),
        UNIQUE KEY uk_token_hash (token_hash)
    ) ENGINE=InnoDB {$charset_collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    // Foreign keys (best effort)
    eipsi_longitudinal_ensure_foreign_key(
        $table_name,
        'fk_magic_links_survey',
        "ALTER TABLE {$table_name} ADD CONSTRAINT fk_magic_links_survey FOREIGN KEY (survey_id) REFERENCES {$wpdb->posts}(ID) ON DELETE CASCADE"
    );

    eipsi_longitudinal_ensure_foreign_key(
        $table_name,
        'fk_magic_links_participant',
        "ALTER TABLE {$table_name} ADD CONSTRAINT fk_magic_links_participant FOREIGN KEY (participant_id) REFERENCES {$wpdb->prefix}survey_participants(id) ON DELETE CASCADE"
    );

    error_log('[EIPSI] Synced survey_magic_links table');
}

/**
 * Sincronizar tabla wp_survey_email_log
 * Log de emails enviados
 */
function eipsi_sync_survey_email_log_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'survey_email_log';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$table_name} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        survey_id BIGINT(20) UNSIGNED NULL,
        participant_id BIGINT(20) UNSIGNED NOT NULL,
        email_type VARCHAR(50) NOT NULL,
        recipient_email VARCHAR(255) NOT NULL,
        subject VARCHAR(500) NOT NULL,
        content LONGTEXT,
        status ENUM('sent', 'failed', 'bounced') DEFAULT 'sent',
        sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        error_message TEXT NULL,
        magic_link_used BOOLEAN DEFAULT 0,
        metadata JSON NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

        PRIMARY KEY (id),
        INDEX idx_survey_id (survey_id),
        INDEX idx_participant_id (participant_id),
        INDEX idx_survey_participant (survey_id, participant_id),
        INDEX idx_email_type (email_type),
        INDEX idx_status (status),
        INDEX idx_sent_at (sent_at)
    ) ENGINE=InnoDB {$charset_collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    // Foreign key (best effort)
    eipsi_longitudinal_ensure_foreign_key(
        $table_name,
        'fk_email_log_participant',
        "ALTER TABLE {$table_name} ADD CONSTRAINT fk_email_log_participant FOREIGN KEY (participant_id) REFERENCES {$wpdb->prefix}survey_participants(id) ON DELETE CASCADE"
    );

    // Add survey_id FK if possible
    eipsi_longitudinal_ensure_foreign_key(
        $table_name,
        'fk_email_log_survey',
        "ALTER TABLE {$table_name} ADD CONSTRAINT fk_email_log_survey FOREIGN KEY (survey_id) REFERENCES {$wpdb->posts}(ID) ON DELETE SET NULL"
    );

    error_log('[EIPSI] Synced survey_email_log table');
}

/**
 * Sincronizar tabla wp_survey_audit_log
 * Log de auditoría para acciones sensibles (v1.4.2 TASK 5.1)
 */
function eipsi_sync_survey_audit_log_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'survey_audit_log';
    $charset_collate = $wpdb->get_charset_collate();

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
    ) ENGINE=InnoDB {$charset_collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    // Foreign keys (best effort)
    eipsi_longitudinal_ensure_foreign_key(
        $table_name,
        'fk_audit_survey',
        "ALTER TABLE {$table_name} ADD CONSTRAINT fk_audit_survey FOREIGN KEY (survey_id) REFERENCES {$wpdb->posts}(ID) ON DELETE CASCADE"
    );

    eipsi_longitudinal_ensure_foreign_key(
        $table_name,
        'fk_audit_participant',
        "ALTER TABLE {$table_name} ADD CONSTRAINT fk_audit_participant FOREIGN KEY (participant_id) REFERENCES {$wpdb->prefix}survey_participants(id) ON DELETE CASCADE"
    );

    error_log('[EIPSI] Synced survey_audit_log table');
}


/**
 * Hook para sincronizar tablas longitudinales
 */
add_action('eipsi_sync_longitudinal_tables', function() {
    eipsi_maybe_create_tables();
});
