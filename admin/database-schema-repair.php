<?php
/**
 * Database Schema Repair Utility
 * 
 * Fixes corrupt or invalid database indexes that cause dbDelta errors
 * Specifically addresses empty index names that generate SQL errors like:
 * "ALTER TABLE wp_survey_waves ADD `` ()"
 * 
 * @package EIPSI_Forms
 * @since 2.0.1
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Fix corrupt indexes with empty names in database
 * 
 * This function searches for indexes with empty names in the specified tables
 * and drops them before dbDelta runs, preventing SQL errors.
 * 
 * @param array $tables Array of table names (with prefix) to check
 * @return array Results with dropped indexes count
 */
function eipsi_fix_corrupt_indexes($tables = array()) {
    global $wpdb;
    
    $results = array(
        'success' => true,
        'tables_checked' => 0,
        'indexes_dropped' => 0,
        'errors' => array()
    );
    
    // Default tables to check if none specified
    if (empty($tables)) {
        $tables = array(
            $wpdb->prefix . 'survey_waves',
            $wpdb->prefix . 'survey_assignments',
            $wpdb->prefix . 'survey_studies',
            $wpdb->prefix . 'survey_participants',
            $wpdb->prefix . 'survey_sessions',
            $wpdb->prefix . 'survey_magic_links',
            $wpdb->prefix . 'survey_email_log',
            $wpdb->prefix . 'survey_audit_log',
            $wpdb->prefix . 'survey_email_confirmations',
            $wpdb->prefix . 'vas_form_results',
            $wpdb->prefix . 'vas_form_events',
            $wpdb->prefix . 'eipsi_longitudinal_pools',
            $wpdb->prefix . 'eipsi_pool_assignments',
            $wpdb->prefix . 'survey_participant_access_log',
            $wpdb->prefix . 'eipsi_device_data'
        );
    }
    
    foreach ($tables as $table) {
        // Check if table exists first
        $table_exists = $wpdb->get_var(
            $wpdb->prepare("SHOW TABLES LIKE %s", $table)
        );
        
        if (empty($table_exists)) {
            continue;
        }
        
        $results['tables_checked']++;
        
        // Get all indexes for this table
        // Use INFORMATION_SCHEMA for more reliable results
        $indexes = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT INDEX_NAME, COLUMN_NAME 
                 FROM INFORMATION_SCHEMA.STATISTICS 
                 WHERE TABLE_SCHEMA = %s 
                   AND TABLE_NAME = %s
                 ORDER BY INDEX_NAME, SEQ_IN_INDEX",
                DB_NAME,
                str_replace($wpdb->prefix, '', $table) // Remove prefix for INFORMATION_SCHEMA
            ),
            ARRAY_A
        );
        
        if (empty($indexes)) {
            continue;
        }
        
        // Group indexes by name
        $index_groups = array();
        foreach ($indexes as $index) {
            $index_name = $index['INDEX_NAME'];
            
            // Skip PRIMARY index (should never be dropped)
            if ($index_name === 'PRIMARY') {
                continue;
            }
            
            // Check for empty index name (the bug we're fixing)
            if (empty($index_name) || trim($index_name) === '') {
                error_log("[EIPSI Schema Repair] Found corrupt empty index on table {$table}");
                
                // Try to drop it - we need to reconstruct the index name from column names
                $columns_in_index = array();
                foreach ($indexes as $idx) {
                    if (empty($idx['INDEX_NAME']) || trim($idx['INDEX_NAME']) === '') {
                        $columns_in_index[] = $idx['COLUMN_NAME'];
                    }
                }
                
                if (!empty($columns_in_index)) {
                    $column_list = implode('`, `', $columns_in_index);
                    $drop_sql = "ALTER TABLE `{$table}` DROP INDEX (`{$column_list}`)";
                    
                    $drop_result = $wpdb->query($drop_sql);
                    
                    if ($drop_result !== false) {
                        $results['indexes_dropped']++;
                        error_log("[EIPSI Schema Repair] Dropped corrupt index on {$table} with columns: {$column_list}");
                    } else {
                        $error = $wpdb->last_error;
                        $results['errors'][] = "Failed to drop corrupt index on {$table}: {$error}";
                        error_log("[EIPSI Schema Repair] ERROR: {$error}");
                    }
                }
            }
        }
        
        // Also check for indexes with names starting with empty backticks
        $raw_indexes = $wpdb->get_results("SHOW INDEX FROM `{$table}`", ARRAY_A);
        foreach ($raw_indexes as $raw_index) {
            $key_name = $raw_index['Key_name'];
            
            // Skip PRIMARY
            if ($key_name === 'PRIMARY') {
                continue;
            }
            
            // Check for suspicious index names (empty strings, only whitespace, etc.)
            if (trim($key_name) === '' || strpos($key_name, '``') !== false) {
                error_log("[EIPSI Schema Repair] Found malformed index '{$key_name}' on table {$table}");
                
                // Try to drop by reconstructing
                $drop_sql = "ALTER TABLE `{$table}` DROP INDEX `{$key_name}`";
                $drop_result = @$wpdb->query($drop_sql);
                
                if ($drop_result !== false) {
                    $results['indexes_dropped']++;
                    error_log("[EIPSI Schema Repair] Dropped malformed index '{$key_name}' on {$table}");
                }
            }
        }
    }
    
    return $results;
}

/**
 * Comprehensive database repair function
 * 
 * This function should be called before any dbDelta operation
 * to ensure no corrupt indexes interfere with schema updates.
 * 
 * @return array Repair results
 */
function eipsi_repair_database_schema() {
    $results = array(
        'corrupt_indexes' => null,
        'total_fixed' => 0,
        'success' => true,
        'errors' => array()
    );
    
    // Step 1: Fix corrupt indexes
    $index_repair = eipsi_fix_corrupt_indexes();
    $results['corrupt_indexes'] = $index_repair;
    $results['total_fixed'] += $index_repair['indexes_dropped'];
    
    if (!empty($index_repair['errors'])) {
        $results['success'] = false;
        $results['errors'] = array_merge($results['errors'], $index_repair['errors']);
    }
    
    // Log repair summary
    error_log(sprintf(
        '[EIPSI Schema Repair] Completed: %d tables checked, %d indexes dropped',
        $index_repair['tables_checked'],
        $index_repair['indexes_dropped']
    ));
    
    return $results;
}

/**
 * Hook to run schema repair before dbDelta operations
 */
add_action('eipsi_before_dbdelta', 'eipsi_repair_database_schema');

/**
 * Manual trigger for schema repair via admin
 * 
 * To use: add ?eipsi_repair_schema=1 to any admin URL
 * Only available to users with manage_options capability
 */
function eipsi_manual_schema_repair() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    if (!isset($_GET['eipsi_repair_schema']) || $_GET['eipsi_repair_schema'] !== '1') {
        return;
    }
    
    // Verify nonce for security
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'eipsi_repair_schema')) {
        wp_die('Security check failed');
    }
    
    // Run repair
    $results = eipsi_repair_database_schema();
    
    // Display results
    echo '<div class="wrap">';
    echo '<h1>EIPSI Forms - Database Schema Repair</h1>';
    
    if ($results['success']) {
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p><strong>Repair completed successfully!</strong></p>';
        echo '<ul>';
        echo "<li>Tables checked: {$results['corrupt_indexes']['tables_checked']}</li>";
        echo "<li>Indexes dropped: {$results['corrupt_indexes']['indexes_dropped']}</li>";
        echo '</ul>';
        echo '</div>';
    } else {
        echo '<div class="notice notice-error is-dismissible">';
        echo '<p><strong>Repair completed with errors:</strong></p>';
        echo '<ul>';
        foreach ($results['errors'] as $error) {
            echo "<li>" . esc_html($error) . "</li>";
        }
        echo '</ul>';
        echo '</div>';
    }
    
    echo '<p><a href="' . admin_url() . '" class="button">Back to Dashboard</a></p>';
    echo '</div>';
    
    exit;
}
add_action('admin_init', 'eipsi_manual_schema_repair');
