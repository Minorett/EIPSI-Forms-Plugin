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
            'session_id' => "varchar(255) DEFAULT NULL AFTER participant_id",
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
}
