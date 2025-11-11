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
        
        // Test if the required table exists
        $table_exists = $this->check_table_exists($mysqli);
        
        $record_count = 0;
        if ($table_exists) {
            $record_count = $this->get_record_count_from_connection($mysqli);
        }
        
        $mysqli->close();
        
        return array(
            'success' => true,
            'message' => __('Connection successful!', 'vas-dinamico-forms'),
            'db_name' => $db_name,
            'record_count' => $record_count,
            'table_exists' => $table_exists
        );
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
        global $wpdb;
        $table_name = $wpdb->prefix . 'vas_form_results';
        
        $result = $mysqli->query("SELECT COUNT(*) as count FROM `{$table_name}`");
        
        if (!$result) {
            return 0;
        }
        
        $row = $result->fetch_assoc();
        return isset($row['count']) ? intval($row['count']) : 0;
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
     * @return bool|int Insert ID on success, false on failure
     */
    public function insert_form_submission($data) {
        $mysqli = $this->get_connection();
        
        if (!$mysqli) {
            return false;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'vas_form_results';
        
        // Prepare statement with new fields
        $stmt = $mysqli->prepare(
            "INSERT INTO `{$table_name}` 
            (form_id, participant_id, form_name, created_at, submitted_at, ip_address, device, browser, os, screen_width, duration, duration_seconds, form_responses) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        
        if (!$stmt) {
            error_log('EIPSI Forms: Failed to prepare statement - ' . $mysqli->error);
            $mysqli->close();
            return false;
        }
        
        $stmt->bind_param(
            'ssssssssssiis',
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
            $data['form_responses']
        );
        
        $success = $stmt->execute();
        $insert_id = $mysqli->insert_id;
        
        $stmt->close();
        $mysqli->close();
        
        return $success ? $insert_id : false;
    }
    
    /**
     * Disable external database
     */
    public function disable() {
        update_option($this->option_prefix . 'enabled', false);
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
        
        if (!$credentials) {
            return array(
                'connected' => false,
                'db_name' => '',
                'record_count' => 0,
                'last_updated' => ''
            );
        }
        
        $test_result = $this->test_connection(
            $credentials['host'],
            $credentials['user'],
            $credentials['password'],
            $credentials['name']
        );
        
        return array(
            'connected' => $test_result['success'],
            'db_name' => $credentials['name'],
            'record_count' => $test_result['success'] ? $test_result['record_count'] : 0,
            'last_updated' => get_option($this->option_prefix . 'last_updated', ''),
            'message' => $test_result['message']
        );
    }
}
