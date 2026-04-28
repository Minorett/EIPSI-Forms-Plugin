<?php
/**
 * Database Schema Migration - v2.0.1
 * 
 * Fixes corrupt indexes that cause dbDelta errors
 * This migration should run automatically on plugin update
 * 
 * @package EIPSI_Forms
 * @since 2.0.1
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Migration function to fix corrupt indexes and perform schema repairs for v2.5.5
 */
function eipsi_migrate_v2_5_5_repair() {
    global $wpdb;
    $migration_version = '2.5.5';
    $completed_version = get_option('eipsi_db_schema_migration_version', '0.0.0');
    
    if (version_compare($completed_version, $migration_version, '>=')) {
        return array(
            'success' => true,
            'message' => 'Migration 2.5.5 already completed',
            'skipped' => true
        );
    }

    $results = array(
        'success' => true,
        'errors' => array(),
        'steps' => array()
    );

    $participants_table = $wpdb->prefix . 'survey_participants';

    // Step A: Deep schema repair - manually adding 'status' column if it doesn't exist
    $column_exists = $wpdb->get_results($wpdb->prepare(
        "SHOW COLUMNS FROM $participants_table LIKE %s",
        'status'
    ));

    if (empty($column_exists)) {
        $wpdb->query("ALTER TABLE $participants_table ADD COLUMN status VARCHAR(30) DEFAULT 'active' AFTER is_active");
        $results['steps'][] = 'Added status column to survey_participants';
    }

    // Step B: Data Cleanup - update 'default' values to numeric survey_id
    $wpdb->query("UPDATE $participants_table SET consent_blocked_survey_id = survey_id WHERE consent_blocked_survey_id = 'default'");
    $results['steps'][] = 'Cleaned up legacy default values in consent_blocked_survey_id';

    // Step C: Type Alteration - change consent_blocked_survey_id to BIGINT
    $wpdb->query("ALTER TABLE $participants_table MODIFY COLUMN consent_blocked_survey_id BIGINT(20) UNSIGNED NULL");
    $results['steps'][] = 'Altered consent_blocked_survey_id to BIGINT';

    // Mark migration as completed
    update_option('eipsi_db_schema_migration_version', $migration_version);
    update_option('eipsi_db_schema_migration_date', current_time('mysql'));
    
    error_log(sprintf('[EIPSI Migration] v%s completed successfully.', $migration_version));

    return $results;
}

/**
 * Migration function to fix corrupt indexes
 * 
 * This function runs during plugin activation and updates
 * to clean up any corrupt or malformed indexes in the database.
 * 
 * @return array Migration results
 */
function eipsi_migrate_fix_corrupt_indexes() {
    $migration_version = '2.0.1';
    $completed_version = get_option('eipsi_db_schema_migration_version', '0.0.0');
    
    // Skip if migration already run
    if (version_compare($completed_version, $migration_version, '>=')) {
        return array(
            'success' => true,
            'message' => 'Migration already completed',
            'skipped' => true
        );
    }
    
    // Load repair utilities
    if (!function_exists('eipsi_repair_database_schema')) {
        require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/database-schema-repair.php';
    }
    
    // Run repair
    $results = eipsi_repair_database_schema();
    
    // Mark migration as completed
    if ($results['success']) {
        update_option('eipsi_db_schema_migration_version', $migration_version);
        update_option('eipsi_db_schema_migration_date', current_time('mysql'));
        
        error_log(sprintf(
            '[EIPSI Migration] v%s completed successfully. Dropped %d corrupt indexes.',
            $migration_version,
            $results['total_fixed']
        ));
    } else {
        error_log(sprintf(
            '[EIPSI Migration] v%s completed with errors: %s',
            $migration_version,
            implode(', ', $results['errors'])
        ));
    }
    
    return $results;
}

/**
 * Register migration hook on plugin activation
 */
add_action('eipsi_forms_activated', function() {
    eipsi_migrate_fix_corrupt_indexes();
    eipsi_migrate_v2_5_5_repair();
});

/**
 * Register migration hook on plugin update
 * 
 * This ensures the migration runs even if the user doesn't deactivate/reactivate
 */
add_action('admin_init', function() {
    $migration_version = '2.5.5';
    $completed_version = get_option('eipsi_db_schema_migration_version', '0.0.0');
    
    // Only run migration if needed and in admin context
    if (version_compare($completed_version, $migration_version, '<') && is_admin()) {
        // Check for nonce to prevent running on every admin load
        if (isset($_GET['eipsi_run_migration'])) {
            check_admin_referer('eipsi_run_migration');
            
            eipsi_migrate_fix_corrupt_indexes();
            $results = eipsi_migrate_v2_5_5_repair();
            
            // Display results
            add_action('admin_notices', function() use ($results) {
                $class = $results['success'] ? 'notice-success' : 'notice-error';
                $message = $results['success'] 
                    ? 'Database schema migration v2.5.5 completed successfully!' 
                    : 'Database schema migration encountered errors. Check error logs for details.';
                
                echo '<div class="notice ' . $class . ' is-dismissible">';
                echo '<p><strong>EIPSI Forms Database Migration:</strong> ' . $message . '</p>';
                
                if (!empty($results['steps'])) {
                    echo '<p>Steps completed:</p><ul>';
                    foreach ($results['steps'] as $step) {
                        echo '<li>' . esc_html($step) . '</li>';
                    }
                    echo '</ul>';
                }
                
                if (!$results['success'] && !empty($results['errors'])) {
                    echo '<p>Errors:</p><ul>';
                    foreach ($results['errors'] as $error) {
                        echo '<li>' . esc_html($error) . '</li>';
                    }
                    echo '</ul>';
                }
                
                echo '</div>';
            });
        }
    }
});

/**
 * Add migration link to admin dashboard if needed
 */
add_action('admin_menu', function() {
    $migration_version = '2.5.5';
    $completed_version = get_option('eipsi_db_schema_migration_version', '0.0.0');
    
    if (version_compare($completed_version, $migration_version, '<')) {
        add_action('admin_notices', function() {
            $run_url = wp_nonce_url(
                admin_url('?eipsi_run_migration=1'),
                'eipsi_run_migration'
            );
            
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>EIPSI Forms:</strong> A database schema update (v2.5.5) is required to fix Consent and Participants logic.</p>';
            echo '<p><a href="' . esc_url($run_url) . '" class="button button-primary">Run Database Migration</a></p>';
            echo '<p><small>This will repair the participants table schema and clean up legacy consent data. The process is safe and will not affect your responses.</small></p>';
            echo '</div>';
        });
    }
});
