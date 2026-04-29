<?php
/**
 * EIPSI Forms - Migration Script: Add Offset Columns for T1-Anchor System
 *
 * Phase 1 of the Longitudinal Timeline Roadmap.
 * Adds offset_minutes to survey_waves and study_end_offset_minutes to survey_studies.
 *
 * Run via: wp eval-file scripts/migration-add-offset-columns.php
 * Or via AJAX in admin panel.
 *
 * @package EIPSI_Forms
 * @since 2.6.0
 */

if (!defined('ABSPATH')) {
    // Allow CLI execution
    if (php_sapi_name() !== 'cli') {
        exit('Direct access not allowed');
    }
    
    // Bootstrap WordPress for CLI
    $wp_load_path = dirname(__DIR__, 4) . '/wp-load.php';
    if (file_exists($wp_load_path)) {
        require_once $wp_load_path;
    } else {
        exit("Could not locate wp-load.php\n");
    }
}

/**
 * Run the migration to add offset columns.
 *
 * @return array Migration result with success status and details.
 */
function eipsi_run_offset_migration() {
    global $wpdb;

    $results = array(
        'success' => true,
        'changes' => array(),
        'errors' => array(),
        'warnings' => array(),
    );

    // =========================================================================
    // 1. Add offset_minutes to survey_waves
    // =========================================================================
    $waves_table = $wpdb->prefix . 'survey_waves';

    // Check if column already exists
    $offset_exists = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = %s
             AND TABLE_NAME = %s
             AND COLUMN_NAME = 'offset_minutes'",
            DB_NAME,
            $waves_table
        )
    );

    if ($offset_exists == 0) {
        // Add offset_minutes column (INT, default 0)
        // For T1 (wave_index = 1), offset_minutes = 0
        // For subsequent waves, offset_minutes is minutes after T1 completion
        $result = $wpdb->query(
            "ALTER TABLE `{$waves_table}`
             ADD COLUMN `offset_minutes` INT(11) DEFAULT 0
             COMMENT 'Minutes after T1 completion when this wave becomes available'
             AFTER `interval_days`"
        );

        if ($result === false) {
            $results['errors'][] = "Failed to add offset_minutes to {$waves_table}: " . $wpdb->last_error;
            $results['success'] = false;
        } else {
            $results['changes'][] = "Added column 'offset_minutes' to {$waves_table}";
        }
    } else {
        $results['warnings'][] = "Column 'offset_minutes' already exists in {$waves_table}";
    }

    // =========================================================================
    // 2. Add window_minutes to survey_waves (optional window for completion)
    // =========================================================================
    $window_exists = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = %s
             AND TABLE_NAME = %s
             AND COLUMN_NAME = 'window_minutes'",
            DB_NAME,
            $waves_table
        )
    );

    if ($window_exists == 0) {
        // Add window_minutes column
        // This determines how long the wave stays open after becoming available
        // If NULL, defaults to next wave's offset - this wave's offset
        $result = $wpdb->query(
            "ALTER TABLE `{$waves_table}`
             ADD COLUMN `window_minutes` INT(11) DEFAULT NULL
             COMMENT 'Minutes the wave stays open after available_at. NULL = until next wave or study_end'
             AFTER `offset_minutes`"
        );

        if ($result === false) {
            $results['errors'][] = "Failed to add window_minutes to {$waves_table}: " . $wpdb->last_error;
            $results['success'] = false;
        } else {
            $results['changes'][] = "Added column 'window_minutes' to {$waves_table}";
        }
    } else {
        $results['warnings'][] = "Column 'window_minutes' already exists in {$waves_table}";
    }

    // =========================================================================
    // 3. Add study_end_offset_minutes to survey_studies
    // =========================================================================
    $studies_table = $wpdb->prefix . 'survey_studies';

    $end_offset_exists = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = %s
             AND TABLE_NAME = %s
             AND COLUMN_NAME = 'study_end_offset_minutes'",
            DB_NAME,
            $studies_table
        )
    );

    if ($end_offset_exists == 0) {
        // Add study_end_offset_minutes column
        // This is the final deadline: all waves must be completed before T1 + this offset
        $result = $wpdb->query(
            "ALTER TABLE `{$studies_table}`
             ADD COLUMN `study_end_offset_minutes` INT(11) DEFAULT NULL
             COMMENT 'Minutes after T1 completion when study closes for participant. NULL = no deadline'
             AFTER `config`"
        );

        if ($result === false) {
            $results['errors'][] = "Failed to add study_end_offset_minutes to {$studies_table}: " . $wpdb->last_error;
            $results['success'] = false;
        } else {
            $results['changes'][] = "Added column 'study_end_offset_minutes' to {$studies_table}";
        }
    } else {
        $results['warnings'][] = "Column 'study_end_offset_minutes' already exists in {$studies_table}";
    }

    // =========================================================================
    // 4. Add t1_completed_at to survey_participants (anchor timestamp)
    // =========================================================================
    $participants_table = $wpdb->prefix . 'survey_participants';

    $t1_exists = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = %s
             AND TABLE_NAME = %s
             AND COLUMN_NAME = 't1_completed_at'",
            DB_NAME,
            $participants_table
        )
    );

    if ($t1_exists == 0) {
        // Add t1_completed_at column
        // This is THE anchor timestamp: when participant submitted T1
        $result = $wpdb->query(
            "ALTER TABLE `{$participants_table}`
             ADD COLUMN `t1_completed_at` DATETIME DEFAULT NULL
             COMMENT 'Anchor timestamp: when participant completed T1 (wave_index=1)'
             AFTER `consent_blocked_survey_id`"
        );

        if ($result === false) {
            $results['errors'][] = "Failed to add t1_completed_at to {$participants_table}: " . $wpdb->last_error;
            $results['success'] = false;
        } else {
            $results['changes'][] = "Added column 't1_completed_at' to {$participants_table}";
        }
    } else {
        $results['warnings'][] = "Column 't1_completed_at' already exists in {$participants_table}";
    }

    // =========================================================================
    // 5. Ensure available_at and due_at exist in survey_assignments
    // =========================================================================
    $assignments_table = $wpdb->prefix . 'survey_assignments';

    // Check available_at
    $available_exists = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = %s
             AND TABLE_NAME = %s
             AND COLUMN_NAME = 'available_at'",
            DB_NAME,
            $assignments_table
        )
    );

    if ($available_exists == 0) {
        $result = $wpdb->query(
            "ALTER TABLE `{$assignments_table}`
             ADD COLUMN `available_at` DATETIME DEFAULT NULL
             COMMENT 'When this assignment becomes available to the participant'
             AFTER `retry_count`"
        );

        if ($result === false) {
            $results['errors'][] = "Failed to add available_at to {$assignments_table}: " . $wpdb->last_error;
            $results['success'] = false;
        } else {
            $results['changes'][] = "Added column 'available_at' to {$assignments_table}";
        }
    }

    // Check due_at
    $due_exists = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = %s
             AND TABLE_NAME = %s
             AND COLUMN_NAME = 'due_at'",
            DB_NAME,
            $assignments_table
        )
    );

    if ($due_exists == 0) {
        $result = $wpdb->query(
            "ALTER TABLE `{$assignments_table}`
             ADD COLUMN `due_at` DATETIME DEFAULT NULL
             COMMENT 'Deadline for this assignment'
             AFTER `available_at`"
        );

        if ($result === false) {
            $results['errors'][] = "Failed to add due_at to {$assignments_table}: " . $wpdb->last_error;
            $results['success'] = false;
        } else {
            $results['changes'][] = "Added column 'due_at' to {$assignments_table}";
        }
    }

    // =========================================================================
    // 6. Add index for available_at + status (for cron queries)
    // =========================================================================
    $index_exists = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
             WHERE TABLE_SCHEMA = %s
             AND TABLE_NAME = %s
             AND INDEX_NAME = 'idx_available_status'",
            DB_NAME,
            $assignments_table
        )
    );

    if ($index_exists == 0) {
        $result = $wpdb->query(
            "ALTER TABLE `{$assignments_table}`
             ADD INDEX `idx_available_status` (`available_at`, `status`)"
        );

        if ($result === false) {
            $results['warnings'][] = "Could not add index idx_available_status: " . $wpdb->last_error;
        } else {
            $results['changes'][] = "Added index 'idx_available_status' to {$assignments_table}";
        }
    }

    // =========================================================================
    // 7. Log migration to audit table
    // =========================================================================
    $audit_table = $wpdb->prefix . 'survey_audit_log';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$audit_table}'");

    if ($table_exists) {
        $wpdb->insert(
            $audit_table,
            array(
                'survey_id' => 0,
                'action' => 'migration_offset_columns',
                'actor_type' => 'system',
                'metadata' => wp_json_encode($results),
                'created_at' => current_time('mysql'),
            ),
            array('%d', '%s', '%s', '%s', '%s')
        );
    }

    return $results;
}

// Execute if run directly (CLI or AJAX)
if (defined('WP_CLI') || (defined('DOING_AJAX') && DOING_AJAX) || php_sapi_name() === 'cli') {
    $result = eipsi_run_offset_migration();

    if (php_sapi_name() === 'cli') {
        echo "\n=== EIPSI Forms Migration: Offset Columns ===\n\n";

        if (!empty($result['changes'])) {
            echo "Changes made:\n";
            foreach ($result['changes'] as $change) {
                echo "  ✓ {$change}\n";
            }
        }

        if (!empty($result['warnings'])) {
            echo "\nWarnings:\n";
            foreach ($result['warnings'] as $warning) {
                echo "  ⚠ {$warning}\n";
            }
        }

        if (!empty($result['errors'])) {
            echo "\nErrors:\n";
            foreach ($result['errors'] as $error) {
                echo "  ✗ {$error}\n";
            }
        }

        echo "\nMigration " . ($result['success'] ? 'completed successfully' : 'completed with errors') . ".\n";
    }
}
