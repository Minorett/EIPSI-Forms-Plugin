<?php
/**
 * Database Schema Verification Script
 * 
 * This script checks if the wp_vas_form_results table has all required columns.
 * Run this via WP-CLI or include it in a WordPress context.
 * 
 * Usage (WP-CLI):
 * wp eval-file check-database-schema.php
 * 
 * Usage (Browser - requires admin):
 * Place in WordPress root and access via: yoursite.com/check-database-schema.php
 */

// Load WordPress if not already loaded
if (!defined('ABSPATH')) {
    require_once __DIR__ . '/wp-load.php';
}

// Require admin privileges
if (!current_user_can('manage_options')) {
    die('Error: This script requires administrator privileges.');
}

global $wpdb;

$table_name = $wpdb->prefix . 'vas_form_results';

echo "===========================================\n";
echo "EIPSI Forms - Database Schema Verification\n";
echo "===========================================\n\n";

// Check if table exists
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");

if (!$table_exists) {
    echo "❌ ERROR: Table '{$table_name}' does not exist!\n";
    echo "   → Plugin may not be activated.\n";
    echo "   → Try activating the EIPSI Forms plugin.\n";
    exit(1);
}

echo "✓ Table '{$table_name}' exists\n\n";

// Define required columns
$required_columns = array(
    'id' => array('type' => 'bigint(20) unsigned', 'required' => true),
    'form_id' => array('type' => 'varchar(20)', 'required' => false),
    'participant_id' => array('type' => 'varchar(20)', 'required' => false),
    'participant' => array('type' => 'varchar(255)', 'required' => false),
    'interaction' => array('type' => 'varchar(255)', 'required' => false),
    'form_name' => array('type' => 'varchar(255)', 'required' => true),
    'created_at' => array('type' => 'datetime', 'required' => true),
    'submitted_at' => array('type' => 'datetime', 'required' => false),
    'device' => array('type' => 'varchar(100)', 'required' => false),
    'browser' => array('type' => 'varchar(100)', 'required' => false),
    'os' => array('type' => 'varchar(100)', 'required' => false),
    'screen_width' => array('type' => 'int(11)', 'required' => false),
    'duration' => array('type' => 'int(11)', 'required' => false),
    'duration_seconds' => array('type' => 'decimal(8,3)', 'required' => false, 'critical' => true),
    'start_timestamp_ms' => array('type' => 'bigint(20)', 'required' => false, 'critical' => true),
    'end_timestamp_ms' => array('type' => 'bigint(20)', 'required' => false, 'critical' => true),
    'ip_address' => array('type' => 'varchar(45)', 'required' => false),
    'form_responses' => array('type' => 'longtext', 'required' => false)
);

// Get actual columns from database
$columns_result = $wpdb->get_results("DESCRIBE {$table_name}");
$actual_columns = array();

foreach ($columns_result as $column) {
    $actual_columns[$column->Field] = array(
        'type' => $column->Type,
        'null' => $column->Null,
        'key' => $column->Key,
        'default' => $column->Default
    );
}

// Check each required column
$missing_columns = array();
$mismatched_types = array();
$all_ok = true;

echo "Column Verification:\n";
echo "--------------------\n";

foreach ($required_columns as $column_name => $column_spec) {
    $is_critical = isset($column_spec['critical']) && $column_spec['critical'];
    $prefix = $is_critical ? '⭐' : '  ';
    
    if (!isset($actual_columns[$column_name])) {
        $missing_columns[] = $column_name;
        $all_ok = false;
        echo "{$prefix} ❌ MISSING: {$column_name} ({$column_spec['type']})\n";
        if ($is_critical) {
            echo "     → CRITICAL COLUMN - Required for form submissions!\n";
        }
    } else {
        $actual_type = $actual_columns[$column_name]['type'];
        // Normalize types for comparison (handle variations like int vs int(11))
        $expected_type_normalized = strtolower(str_replace(' unsigned', '', $column_spec['type']));
        $actual_type_normalized = strtolower(str_replace(' unsigned', '', $actual_type));
        
        if (strpos($expected_type_normalized, $actual_type_normalized) === false && 
            strpos($actual_type_normalized, $expected_type_normalized) === false) {
            $mismatched_types[] = array(
                'column' => $column_name,
                'expected' => $column_spec['type'],
                'actual' => $actual_type
            );
            $all_ok = false;
            echo "{$prefix} ⚠️  TYPE MISMATCH: {$column_name}\n";
            echo "     → Expected: {$column_spec['type']}\n";
            echo "     → Actual: {$actual_type}\n";
        } else {
            echo "{$prefix} ✓ {$column_name} ({$actual_type})\n";
        }
    }
}

// Check indexes
echo "\nIndex Verification:\n";
echo "-------------------\n";

$indexes_result = $wpdb->get_results("SHOW INDEX FROM {$table_name}");
$actual_indexes = array();

foreach ($indexes_result as $index) {
    if (!isset($actual_indexes[$index->Key_name])) {
        $actual_indexes[$index->Key_name] = array();
    }
    $actual_indexes[$index->Key_name][] = $index->Column_name;
}

$required_indexes = array('PRIMARY', 'form_name', 'created_at', 'form_id', 'participant_id', 'submitted_at', 'form_participant');

foreach ($required_indexes as $index_name) {
    if (isset($actual_indexes[$index_name])) {
        $columns = implode(', ', $actual_indexes[$index_name]);
        echo "  ✓ {$index_name} ({$columns})\n";
    } else {
        echo "  ⚠️  MISSING INDEX: {$index_name}\n";
        $all_ok = false;
    }
}

// Check database version
echo "\nDatabase Version:\n";
echo "-----------------\n";

$db_version = get_option('vas_dinamico_db_version', 'not set');
echo "  Current: {$db_version}\n";
echo "  Required: 1.4\n";

if ($db_version !== '1.4') {
    echo "  ⚠️  Database version mismatch!\n";
    echo "     → Visit any WordPress admin page to trigger migration.\n";
    $all_ok = false;
} else {
    echo "  ✓ Database version is current\n";
}

// Check record count
echo "\nTable Statistics:\n";
echo "-----------------\n";

$record_count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
echo "  Total Records: {$record_count}\n";

if ($record_count > 0) {
    $recent_record = $wpdb->get_row("SELECT * FROM {$table_name} ORDER BY id DESC LIMIT 1");
    echo "  Most Recent Submission:\n";
    echo "    - ID: {$recent_record->id}\n";
    echo "    - Form: {$recent_record->form_name}\n";
    echo "    - form_id: " . ($recent_record->form_id ?: 'NULL') . "\n";
    echo "    - Duration: {$recent_record->duration} seconds\n";
    if (isset($recent_record->duration_seconds)) {
        echo "    - Duration (precise): {$recent_record->duration_seconds} seconds\n";
    }
    if (isset($recent_record->start_timestamp_ms)) {
        echo "    - Start timestamp: {$recent_record->start_timestamp_ms}\n";
    }
    if (isset($recent_record->end_timestamp_ms)) {
        echo "    - End timestamp: {$recent_record->end_timestamp_ms}\n";
    }
}

// Summary
echo "\n===========================================\n";
echo "Summary:\n";
echo "===========================================\n";

if ($all_ok) {
    echo "✅ ALL CHECKS PASSED\n";
    echo "   Database schema is complete and up to date.\n";
    echo "   Form submissions should work correctly.\n";
} else {
    echo "❌ ISSUES DETECTED\n\n";
    
    if (!empty($missing_columns)) {
        echo "Missing Columns (" . count($missing_columns) . "):\n";
        foreach ($missing_columns as $col) {
            echo "  - {$col}\n";
        }
        echo "\n";
    }
    
    if (!empty($mismatched_types)) {
        echo "Type Mismatches (" . count($mismatched_types) . "):\n";
        foreach ($mismatched_types as $mismatch) {
            echo "  - {$mismatch['column']}: expected {$mismatch['expected']}, got {$mismatch['actual']}\n";
        }
        echo "\n";
    }
    
    echo "Recommended Actions:\n";
    echo "  1. Visit any WordPress admin page to trigger automatic migration\n";
    echo "  2. Check debug.log for migration errors (enable WP_DEBUG if needed)\n";
    echo "  3. If issues persist, deactivate and reactivate the plugin\n";
    echo "  4. See DATABASE_SCHEMA_FIX_SUMMARY.md for detailed troubleshooting\n";
}

echo "\n";
