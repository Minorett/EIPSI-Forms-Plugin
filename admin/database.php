<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * EIPSI Forms External Database Helper
 * Manages connections and operations for external MySQL databases
 */
class EIPSI_External_Database {
    
    private $option_prefix = 'eipsi_external_db_';
    
    /**
     * Encrypt credentials before storing
     * Uses WordPress salts for encryption
     */
    private function encrypt_data($data) {
        if (empty($data)) {
            return '';
        }
        
        $key = wp_salt('auth');
        $iv_length = openssl_cipher_iv_length('aes-256-cbc');
        $iv = openssl_random_pseudo_bytes($iv_length);
        
        $encrypted = openssl_encrypt(
            $data,
            'aes-256-cbc',
            $key,
            0,
            $iv
        );
        
        return base64_encode($iv . '::' . $encrypted);
    }
    
    /**
     * Decrypt stored credentials
     */
    private function decrypt_data($encrypted_data) {
        if (empty($encrypted_data)) {
            return '';
        }
        
        $key = wp_salt('auth');
        $decoded = base64_decode($encrypted_data);
        
        if (strpos($decoded, '::') === false) {
            return '';
        }
        
        list($iv, $encrypted) = explode('::', $decoded, 2);
        
        return openssl_decrypt(
            $encrypted,
            'aes-256-cbc',
            $key,
            0,
            $iv
        );
    }
    
    /**
     * Save custom database credentials
     * 
     * @param string $host Database host
     * @param string $user Database username
     * @param string $password Database password
     * @param string $db_name Database name
     * @return bool Success or failure
     */
    public function save_credentials($host, $user, $password, $db_name) {
        $host = sanitize_text_field($host);
        $user = sanitize_text_field($user);
        $db_name = sanitize_text_field($db_name);
        
        if (empty($host) || empty($user) || empty($db_name)) {
            return false;
        }
        
        // Encrypt sensitive data
        $encrypted_password = $this->encrypt_data($password);
        
        // Save to wp_options
        update_option($this->option_prefix . 'host', $host);
        update_option($this->option_prefix . 'user', $user);
        update_option($this->option_prefix . 'password', $encrypted_password);
        update_option($this->option_prefix . 'name', $db_name);
        update_option($this->option_prefix . 'enabled', true);
        update_option($this->option_prefix . 'last_updated', current_time('mysql'));
        
        return true;
    }
    
    /**
     * Get stored credentials
     * 
     * @return array|null Array with credentials or null if not configured
     */
    public function get_credentials() {
        $enabled = get_option($this->option_prefix . 'enabled', false);
        
        if (!$enabled) {
            return null;
        }
        
        $host = get_option($this->option_prefix . 'host', '');
        $user = get_option($this->option_prefix . 'user', '');
        $encrypted_password = get_option($this->option_prefix . 'password', '');
        $db_name = get_option($this->option_prefix . 'name', '');
        
        if (empty($host) || empty($user) || empty($db_name)) {
            return null;
        }
        
        $password = $this->decrypt_data($encrypted_password);
        
        return array(
            'host' => $host,
            'user' => $user,
            'password' => $password,
            'name' => $db_name
        );
    }
    
    /**
     * Test database connection
     * 
     * @param string $host Database host
     * @param string $user Database username
     * @param string $password Database password
     * @param string $db_name Database name
     * @return array Array with success status and message
     */
    public function test_connection($host, $user, $password, $db_name) {
        $host = sanitize_text_field($host);
        $user = sanitize_text_field($user);
        $db_name = sanitize_text_field($db_name);
        
        // Suppress errors to handle them gracefully
        $mysqli = @new mysqli($host, $user, $password, $db_name);
        
        if ($mysqli->connect_error) {
            return array(
                'success' => false,
                'message' => sprintf(
                    __('Connection failed: %s', 'vas-dinamico-forms'),
                    $mysqli->connect_error
                ),
                'error_code' => $mysqli->connect_errno
            );
        }
        
        // Ensure schema is ready (create table if missing, add columns if needed)
        $schema_result = $this->ensure_schema_ready($mysqli);
        
        if (!$schema_result['success']) {
            $mysqli->close();
            return array(
                'success' => false,
                'message' => sprintf(
                    __('Schema validation failed: %s', 'vas-dinamico-forms'),
                    $schema_result['error']
                ),
                'error_code' => 'SCHEMA_ERROR'
            );
        }
        
        // Get record count
        $record_count = $this->get_record_count_from_connection($mysqli);
        
        $mysqli->close();
        
        return array(
            'success' => true,
            'message' => __('Connection successful! Schema validated.', 'vas-dinamico-forms'),
            'db_name' => $db_name,
            'record_count' => $record_count,
            'table_exists' => true
        );
    }
    
    /**
     * Resolve table name (with or without WP prefix)
     * Checks both prefixed and bare table names
     */
    private function resolve_table_name($mysqli) {
        global $wpdb;
        $prefixed_table = $wpdb->prefix . 'vas_form_results';
        $bare_table = 'vas_form_results';
        
        // Check prefixed table first
        $result = $mysqli->query("SHOW TABLES LIKE '{$prefixed_table}'");
        if ($result && $result->num_rows > 0) {
            return $prefixed_table;
        }
        
        // Check bare table
        $result = $mysqli->query("SHOW TABLES LIKE '{$bare_table}'");
        if ($result && $result->num_rows > 0) {
            return $bare_table;
        }
        
        // Default to prefixed table for creation
        return $prefixed_table;
    }

    /**
     * Check if vas_form_results table exists
     */
    private function check_table_exists($mysqli) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vas_form_results';
        
        $result = $mysqli->query("SHOW TABLES LIKE '{$table_name}'");
        
        if (!$result) {
            return false;
        }
        
        return $result->num_rows > 0;
    }
    
    /**
     * Get record count from custom database connection
     */
    private function get_record_count_from_connection($mysqli) {
        $table_name = $this->resolve_table_name($mysqli);
        
        $result = $mysqli->query("SELECT COUNT(*) as count FROM `{$table_name}`");
        
        if (!$result) {
            return 0;
        }
        
        $row = $result->fetch_assoc();
        return isset($row['count']) ? intval($row['count']) : 0;
    }
    
    /**
     * Create the vas_form_results table in the external database
     * 
     * @param mysqli $mysqli Active database connection
     * @return bool Success or failure
     */
    private function create_table_if_missing($mysqli) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vas_form_results';
        $charset = $mysqli->character_set_name();
        
        $sql = "CREATE TABLE IF NOT EXISTS `{$table_name}` (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            form_id varchar(20) DEFAULT NULL,
            participant_id varchar(20) DEFAULT NULL,
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
            form_responses longtext DEFAULT NULL,
            PRIMARY KEY (id),
            KEY form_name (form_name),
            KEY created_at (created_at),
            KEY form_id (form_id),
            KEY participant_id (participant_id),
            KEY submitted_at (submitted_at),
            KEY form_participant (form_id, participant_id)
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset}";
        
        $result = $mysqli->query($sql);
        
        if (!$result) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('EIPSI Forms: Failed to create table - ' . $mysqli->error);
            }
            return false;
        }
        
        return true;
    }

    /**
     * Ensure required columns exist in the table
     * 
     * @param mysqli $mysqli Active database connection
     * @param string $table_name Resolved table name
     * @return bool Success or failure
     */
    private function ensure_required_columns($mysqli, $table_name) {
        $required_columns = array(
            'participant_id' => "ALTER TABLE `{$table_name}` ADD COLUMN participant_id varchar(20) DEFAULT NULL AFTER form_id",
            'duration_seconds' => "ALTER TABLE `{$table_name}` ADD COLUMN duration_seconds decimal(8,3) DEFAULT NULL AFTER duration",
            'submitted_at' => "ALTER TABLE `{$table_name}` ADD COLUMN submitted_at datetime DEFAULT NULL AFTER created_at",
            'start_timestamp_ms' => "ALTER TABLE `{$table_name}` ADD COLUMN start_timestamp_ms bigint(20) DEFAULT NULL AFTER duration_seconds",
            'end_timestamp_ms' => "ALTER TABLE `{$table_name}` ADD COLUMN end_timestamp_ms bigint(20) DEFAULT NULL AFTER start_timestamp_ms"
        );
        
        foreach ($required_columns as $column => $alter_sql) {
            // Check if column exists
            $result = $mysqli->query("SHOW COLUMNS FROM `{$table_name}` LIKE '{$column}'");
            
            if (!$result || $result->num_rows === 0) {
                // Column doesn't exist, add it
                if (!$mysqli->query($alter_sql)) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log("EIPSI Forms: Failed to add column {$column} - " . $mysqli->error);
                    }
                    return false;
                }
            }
        }
        
        return true;
    }

    /**
     * Ensure schema is ready (table exists with all required columns)
     * 
     * @param mysqli $mysqli Active database connection
     * @return array Array with success status and table name
     */
    private function ensure_schema_ready($mysqli) {
        // Try to create table if missing
        if (!$this->create_table_if_missing($mysqli)) {
            return array(
                'success' => false,
                'error' => 'Failed to create table',
                'table_name' => null
            );
        }
        
        // Resolve the actual table name
        $table_name = $this->resolve_table_name($mysqli);
        
        // Ensure all required columns exist
        if (!$this->ensure_required_columns($mysqli, $table_name)) {
            return array(
                'success' => false,
                'error' => 'Failed to add required columns',
                'table_name' => $table_name
            );
        }
        
        return array(
            'success' => true,
            'table_name' => $table_name
        );
    }

    /**
     * Get active database connection (custom or WordPress)
     * 
     * @return mysqli|null Custom database connection or null
     */
    public function get_connection() {
        $credentials = $this->get_credentials();
        
        if (!$credentials) {
            return null;
        }
        
        $mysqli = @new mysqli(
            $credentials['host'],
            $credentials['user'],
            $credentials['password'],
            $credentials['name']
        );
        
        if ($mysqli->connect_error) {
            error_log('EIPSI Forms: Failed to connect to external DB - ' . $mysqli->connect_error);
            return null;
        }
        
        return $mysqli;
    }
    
    /**
     * Get current database record count
     * 
     * @return int Record count
     */
    public function get_record_count() {
        $custom_connection = $this->get_connection();
        
        if ($custom_connection) {
            $count = $this->get_record_count_from_connection($custom_connection);
            $custom_connection->close();
            return $count;
        }
        
        // Fallback to WordPress database
        global $wpdb;
        $table_name = $wpdb->prefix . 'vas_form_results';
        
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        
        return intval($count);
    }
    
    /**
     * Insert form submission into custom database
     * 
     * @param array $data Form submission data
     * @return array Result array with success status, insert_id, and error details
     */
    public function insert_form_submission($data) {
        $mysqli = $this->get_connection();
        
        if (!$mysqli) {
            $error_msg = 'Failed to connect to external database';
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('EIPSI Forms External DB: ' . $error_msg);
            }
            return array(
                'success' => false,
                'error' => $error_msg,
                'error_code' => 'CONNECTION_FAILED',
                'insert_id' => null
            );
        }
        
        // Ensure schema is ready before insert
        $schema_result = $this->ensure_schema_ready($mysqli);
        
        if (!$schema_result['success']) {
            $error_msg = 'Schema validation failed: ' . $schema_result['error'];
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('EIPSI Forms External DB: ' . $error_msg);
            }
            $mysqli->close();
            return array(
                'success' => false,
                'error' => $error_msg,
                'error_code' => 'SCHEMA_ERROR',
                'insert_id' => null
            );
        }
        
        $table_name = $schema_result['table_name'];
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('EIPSI Forms External DB: Attempting insert into table ' . $table_name);
        }
        
        // Prepare statement with corrected bind types: s s s s s s s s s i i d i i s
        $stmt = $mysqli->prepare(
            "INSERT INTO `{$table_name}` 
            (form_id, participant_id, form_name, created_at, submitted_at, ip_address, device, browser, os, screen_width, duration, duration_seconds, start_timestamp_ms, end_timestamp_ms, form_responses) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        
        if (!$stmt) {
            $error_msg = 'Failed to prepare statement: ' . $mysqli->error;
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('EIPSI Forms External DB: ' . $error_msg);
                error_log('EIPSI Forms External DB: MySQL Error Code: ' . $mysqli->errno);
            }
            $mysqli->close();
            return array(
                'success' => false,
                'error' => $error_msg,
                'error_code' => 'PREPARE_FAILED',
                'mysql_errno' => $mysqli->errno,
                'insert_id' => null
            );
        }
        
        // Correct bind types: string × 9, int, int, double, bigint, bigint, string
        $stmt->bind_param(
            'sssssssssiidiis',
            $data['form_id'],
            $data['participant_id'],
            $data['form_name'],
            $data['created_at'],
            $data['submitted_at'],
            $data['ip_address'],
            $data['device'],
            $data['browser'],
            $data['os'],
            $data['screen_width'],
            $data['duration'],
            $data['duration_seconds'],
            $data['start_timestamp_ms'],
            $data['end_timestamp_ms'],
            $data['form_responses']
        );
        
        $success = $stmt->execute();
        
        if (!$success) {
            $error_msg = 'Failed to execute insert: ' . $stmt->error;
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('EIPSI Forms External DB: ' . $error_msg);
                error_log('EIPSI Forms External DB: MySQL Error Code: ' . $stmt->errno);
            }
            $stmt->close();
            $mysqli->close();
            return array(
                'success' => false,
                'error' => $error_msg,
                'error_code' => 'EXECUTE_FAILED',
                'mysql_errno' => $stmt->errno,
                'insert_id' => null
            );
        }
        
        $insert_id = $mysqli->insert_id;
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('EIPSI Forms External DB: Successfully inserted record with ID ' . $insert_id);
        }
        
        $stmt->close();
        $mysqli->close();
        
        return array(
            'success' => true,
            'insert_id' => $insert_id,
            'error' => null,
            'error_code' => null
        );
    }
    
    /**
     * Record external database error for admin diagnostics
     * 
     * @param string $error_message Error message
     * @param string $error_code Machine-readable error code
     */
    public function record_error($error_message, $error_code) {
        update_option($this->option_prefix . 'last_error', $error_message);
        update_option($this->option_prefix . 'last_error_code', $error_code);
        update_option($this->option_prefix . 'last_error_time', current_time('mysql'));
    }

    /**
     * Clear recorded errors
     */
    public function clear_errors() {
        delete_option($this->option_prefix . 'last_error');
        delete_option($this->option_prefix . 'last_error_code');
        delete_option($this->option_prefix . 'last_error_time');
    }

    /**
     * Disable external database
     */
    public function disable() {
        update_option($this->option_prefix . 'enabled', false);
        $this->clear_errors();
    }
    
    /**
     * Check if external database is enabled and configured
     * 
     * @return bool
     */
    public function is_enabled() {
        $enabled = get_option($this->option_prefix . 'enabled', false);
        
        if (!$enabled) {
            return false;
        }
        
        $credentials = $this->get_credentials();
        return !empty($credentials);
    }
    
    /**
     * Get connection status information
     * 
     * @return array Status information
     */
    public function get_status() {
        $credentials = $this->get_credentials();
        
        // Get error information
        $last_error = get_option($this->option_prefix . 'last_error', '');
        $last_error_code = get_option($this->option_prefix . 'last_error_code', '');
        $last_error_time = get_option($this->option_prefix . 'last_error_time', '');
        
        if (!$credentials) {
            return array(
                'connected' => false,
                'db_name' => '',
                'record_count' => 0,
                'last_updated' => '',
                'last_error' => $last_error,
                'last_error_code' => $last_error_code,
                'last_error_time' => $last_error_time,
                'fallback_active' => !empty($last_error)
            );
        }
        
        $test_result = $this->test_connection(
            $credentials['host'],
            $credentials['user'],
            $credentials['password'],
            $credentials['name']
        );
        
        // Clear errors if connection is successful
        if ($test_result['success']) {
            $this->clear_errors();
            $last_error = '';
            $last_error_code = '';
            $last_error_time = '';
        }
        
        return array(
            'connected' => $test_result['success'],
            'db_name' => $credentials['name'],
            'record_count' => $test_result['success'] ? $test_result['record_count'] : 0,
            'last_updated' => get_option($this->option_prefix . 'last_updated', ''),
            'message' => $test_result['message'],
            'last_error' => $last_error,
            'last_error_code' => $last_error_code,
            'last_error_time' => $last_error_time,
            'fallback_active' => !empty($last_error) && !$test_result['success']
        );
    }
}
