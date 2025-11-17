<?php
if (!defined('ABSPATH')) {
	exit;
}

/**
 * EIPSI Forms Database Schema Manager
 * Handles automatic table creation and schema synchronization for external databases
 * 
 * @package VAS_Dinamico_Forms
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
			'errors' => array(),
		);
		
		if ( $mysqli ) {
			// External database sync
			$results_sync = self::sync_results_table( $mysqli );
			$events_sync = self::sync_events_table( $mysqli );
			
			$results['results_table'] = $results_sync;
			$results['events_table'] = $events_sync;
			
			if ( ! $results_sync['success'] || ! $events_sync['success'] ) {
				$results['success'] = false;
				if ( ! $results_sync['success'] ) {
					$results['errors'][] = $results_sync['error'];
				}
				if ( ! $events_sync['success'] ) {
					$results['errors'][] = $events_sync['error'];
				}
			}
		} else {
			// Local WordPress database sync
			global $wpdb;
			$results_sync = self::sync_local_results_table();
			$events_sync = self::sync_local_events_table();
			
			$results['results_table'] = $results_sync;
			$results['events_table'] = $events_sync;
			
			if ( ! $results_sync['success'] || ! $events_sync['success'] ) {
				$results['success'] = false;
				if ( ! $results_sync['success'] ) {
					$results['errors'][] = $results_sync['error'];
				}
				if ( ! $events_sync['success'] ) {
					$results['errors'][] = $events_sync['error'];
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
				submitted_at datetime DEFAULT NULL,
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
				quality_flag enum('HIGH','NORMAL','LOW') DEFAULT 'NORMAL',
				status enum('pending','submitted','error') DEFAULT 'submitted',
				form_responses longtext DEFAULT NULL,
				PRIMARY KEY (id),
				KEY form_name (form_name),
				KEY created_at (created_at),
				KEY form_id (form_id),
				KEY participant_id (participant_id),
				KEY session_id (session_id),
				KEY submitted_at (submitted_at),
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
			'submitted_at' => "ALTER TABLE `{$table_name}` ADD COLUMN submitted_at datetime DEFAULT NULL AFTER created_at",
			'start_timestamp_ms' => "ALTER TABLE `{$table_name}` ADD COLUMN start_timestamp_ms bigint(20) DEFAULT NULL AFTER duration_seconds",
			'end_timestamp_ms' => "ALTER TABLE `{$table_name}` ADD COLUMN end_timestamp_ms bigint(20) DEFAULT NULL AFTER start_timestamp_ms",
			'metadata' => "ALTER TABLE `{$table_name}` ADD COLUMN metadata LONGTEXT DEFAULT NULL AFTER ip_address",
			'quality_flag' => "ALTER TABLE `{$table_name}` ADD COLUMN quality_flag enum('HIGH','NORMAL','LOW') DEFAULT 'NORMAL' AFTER metadata",
			'status' => "ALTER TABLE `{$table_name}` ADD COLUMN status enum('pending','submitted','error') DEFAULT 'submitted' AFTER quality_flag",
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
			'quality_flag' => "ALTER TABLE {$table_name} ADD COLUMN quality_flag enum('HIGH','NORMAL','LOW') DEFAULT 'NORMAL' AFTER metadata",
			'status' => "ALTER TABLE {$table_name} ADD COLUMN status enum('pending','submitted','error') DEFAULT 'submitted' AFTER quality_flag",
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
	 * Hook: Called when database credentials are changed
	 */
	public static function on_credentials_changed() {
		// Clear cached verification timestamp
		delete_option( 'eipsi_schema_last_verified' );
		
		// Try to get connection and verify schema
		require_once VAS_DINAMICO_PLUGIN_DIR . 'admin/database.php';
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
	 */
	public static function periodic_verification() {
		$last_verified = get_option( 'eipsi_schema_last_verified', 0 );
		$current_time = current_time( 'timestamp' );
		
		// If more than 24 hours have passed, verify schema
		if ( ( $current_time - strtotime( $last_verified ) ) > 86400 ) {
			// Check if external DB is enabled
			require_once VAS_DINAMICO_PLUGIN_DIR . 'admin/database.php';
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
	 * Hook: Fallback verification on insert error
	 */
	public static function fallback_verification() {
		require_once VAS_DINAMICO_PLUGIN_DIR . 'admin/database.php';
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
