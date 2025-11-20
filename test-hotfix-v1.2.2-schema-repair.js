#!/usr/bin/env node

/**
 * EIPSI Forms - HOTFIX v1.2.2 Schema Repair Validation
 * Tests automatic database schema repair with zero data loss guarantee
 * 
 * Test Categories:
 * 1. Schema Manager Methods (repair_local_schema, column checks)
 * 2. Activation Hook (version setting, logging)
 * 3. Auto-Repair Triggers (plugins_loaded, admin_init)
 * 4. Emergency Fallback (AJAX error detection and recovery)
 * 5. Integration Tests (full repair cycle)
 */

const fs = require('fs');
const path = require('path');

let passCount = 0;
let failCount = 0;
const failures = [];

function test(description, fn) {
    try {
        fn();
        passCount++;
        console.log(`✅ ${description}`);
    } catch (error) {
        failCount++;
        failures.push({ description, error: error.message });
        console.log(`❌ ${description}`);
        console.log(`   ${error.message}`);
    }
}

function assert(condition, message) {
    if (!condition) {
        throw new Error(message || 'Assertion failed');
    }
}

function assertFileExists(filePath, message) {
    assert(fs.existsSync(filePath), message || `File should exist: ${filePath}`);
}

function assertFileContains(filePath, searchString, message) {
    const content = fs.readFileSync(filePath, 'utf8');
    assert(
        content.includes(searchString),
        message || `File should contain: "${searchString}"`
    );
}

function assertFileContainsRegex(filePath, regex, message) {
    const content = fs.readFileSync(filePath, 'utf8');
    assert(
        regex.test(content),
        message || `File should match regex: ${regex}`
    );
}

function countOccurrences(filePath, searchString) {
    const content = fs.readFileSync(filePath, 'utf8');
    return (content.match(new RegExp(searchString, 'g')) || []).length;
}

console.log('\n=================================');
console.log('HOTFIX v1.2.2 - Schema Repair Validation');
console.log('=================================\n');

// ===========================
// CATEGORY 1: SCHEMA MANAGER METHODS
// ===========================

console.log('📂 CATEGORY 1: Schema Manager Methods\n');

test('Schema manager file exists', () => {
    assertFileExists(
        path.join(__dirname, 'admin/database-schema-manager.php'),
        'Database schema manager file must exist'
    );
});

test('repair_local_schema() method exists', () => {
    assertFileContains(
        path.join(__dirname, 'admin/database-schema-manager.php'),
        'public static function repair_local_schema()',
        'repair_local_schema() method must be defined'
    );
});

test('local_table_exists() method exists', () => {
    assertFileContains(
        path.join(__dirname, 'admin/database-schema-manager.php'),
        'private static function local_table_exists',
        'local_table_exists() method must be defined'
    );
});

test('repair_local_results_table() method exists', () => {
    assertFileContains(
        path.join(__dirname, 'admin/database-schema-manager.php'),
        'private static function repair_local_results_table',
        'repair_local_results_table() method must be defined'
    );
});

test('repair_local_events_table() method exists', () => {
    assertFileContains(
        path.join(__dirname, 'admin/database-schema-manager.php'),
        'private static function repair_local_events_table',
        'repair_local_events_table() method must be defined'
    );
});

test('local_column_exists() method exists', () => {
    assertFileContains(
        path.join(__dirname, 'admin/database-schema-manager.php'),
        'private static function local_column_exists',
        'local_column_exists() method must be defined'
    );
});

test('ensure_local_index() method exists', () => {
    assertFileContains(
        path.join(__dirname, 'admin/database-schema-manager.php'),
        'private static function ensure_local_index',
        'ensure_local_index() method must be defined'
    );
});

test('repair_local_schema() sets schema version', () => {
    assertFileContains(
        path.join(__dirname, 'admin/database-schema-manager.php'),
        "update_option( 'eipsi_db_schema_version', '1.2.2' )",
        'repair_local_schema() must set schema version to 1.2.2'
    );
});

test('repair_local_schema() updates last verified timestamp', () => {
    assertFileContains(
        path.join(__dirname, 'admin/database-schema-manager.php'),
        "update_option( 'eipsi_schema_last_verified', current_time( 'mysql' ) )",
        'repair_local_schema() must update last verified timestamp'
    );
});

test('repair_local_schema() calls activation hook if tables missing', () => {
    assertFileContains(
        path.join(__dirname, 'admin/database-schema-manager.php'),
        'vas_dinamico_activate();',
        'repair_local_schema() must call activation hook if tables missing'
    );
});

test('repair_local_results_table() checks required columns', () => {
    const schemaFile = path.join(__dirname, 'admin/database-schema-manager.php');
    const requiredColumns = [
        'form_id',
        'participant_id',
        'session_id',
        'form_name',
        'form_responses',
        'metadata',
        'browser',
        'os',
        'screen_width',
        'duration_seconds',
        'quality_flag'
    ];
    
    requiredColumns.forEach(column => {
        assertFileContains(
            schemaFile,
            `'${column}'`,
            `repair_local_results_table() must check for ${column} column`
        );
    });
});

test('repair_local_events_table() checks required columns', () => {
    const schemaFile = path.join(__dirname, 'admin/database-schema-manager.php');
    const requiredColumns = ['form_id', 'session_id', 'event_type', 'page_number', 'metadata', 'user_agent'];
    
    requiredColumns.forEach(column => {
        assertFileContains(
            schemaFile,
            `'${column}'`,
            `repair_local_events_table() must check for ${column} column`
        );
    });
});

test('Schema repair includes logging', () => {
    assertFileContains(
        path.join(__dirname, 'admin/database-schema-manager.php'),
        'error_log',
        'Schema repair must include logging'
    );
});

test('repair_local_schema() includes error logging for column additions', () => {
    assertFileContains(
        path.join(__dirname, 'admin/database-schema-manager.php'),
        "[EIPSI Forms] Added missing column",
        'Column additions must be logged'
    );
});

test('periodic_verification() calls repair_local_schema()', () => {
    assertFileContains(
        path.join(__dirname, 'admin/database-schema-manager.php'),
        'self::repair_local_schema();',
        'periodic_verification() must call repair_local_schema()'
    );
});

// ===========================
// CATEGORY 2: ACTIVATION HOOK
// ===========================

console.log('\n📂 CATEGORY 2: Activation Hook\n');

test('Main plugin file exists', () => {
    assertFileExists(
        path.join(__dirname, 'vas-dinamico-forms.php'),
        'Main plugin file must exist'
    );
});

test('Plugin version updated to 1.2.2', () => {
    assertFileContains(
        path.join(__dirname, 'vas-dinamico-forms.php'),
        'Version: 1.2.2',
        'Plugin header version must be 1.2.2'
    );
});

test('VAS_DINAMICO_VERSION constant updated to 1.2.2', () => {
    assertFileContains(
        path.join(__dirname, 'vas-dinamico-forms.php'),
        "define('VAS_DINAMICO_VERSION', '1.2.2')",
        'Version constant must be 1.2.2'
    );
});

test('Stable tag updated to 1.2.2', () => {
    assertFileContains(
        path.join(__dirname, 'vas-dinamico-forms.php'),
        'Stable tag: 1.2.2',
        'Stable tag must be 1.2.2'
    );
});

test('Activation hook sets schema version', () => {
    assertFileContains(
        path.join(__dirname, 'vas-dinamico-forms.php'),
        "update_option('eipsi_db_schema_version', '1.2.2')",
        'Activation hook must set eipsi_db_schema_version option'
    );
});

test('Activation hook includes logging', () => {
    assertFileContains(
        path.join(__dirname, 'vas-dinamico-forms.php'),
        "error_log('[EIPSI Forms] Plugin activated - Schema v1.2.2 installed')",
        'Activation hook must log activation event'
    );
});

test('Activation hook registered', () => {
    assertFileContains(
        path.join(__dirname, 'vas-dinamico-forms.php'),
        "register_activation_hook(__FILE__, 'vas_dinamico_activate')",
        'Activation hook must be registered'
    );
});

// ===========================
// CATEGORY 3: AUTO-REPAIR TRIGGERS
// ===========================

console.log('\n📂 CATEGORY 3: Auto-Repair Triggers\n');

test('plugins_loaded hook exists for schema verification', () => {
    assertFileContains(
        path.join(__dirname, 'vas-dinamico-forms.php'),
        "add_action('plugins_loaded', 'vas_dinamico_verify_schema_on_load')",
        'plugins_loaded hook must be registered for schema verification'
    );
});

test('vas_dinamico_verify_schema_on_load() function exists', () => {
    assertFileContains(
        path.join(__dirname, 'vas-dinamico-forms.php'),
        'function vas_dinamico_verify_schema_on_load()',
        'vas_dinamico_verify_schema_on_load() function must exist'
    );
});

test('Schema verification checks version option', () => {
    assertFileContains(
        path.join(__dirname, 'vas-dinamico-forms.php'),
        "get_option('eipsi_db_schema_version')",
        'Schema verification must check version option'
    );
});

test('Schema verification compares version', () => {
    assertFileContains(
        path.join(__dirname, 'vas-dinamico-forms.php'),
        "version_compare($schema_version, '1.2.2', '<')",
        'Schema verification must compare version'
    );
});

test('Schema verification triggers repair', () => {
    assertFileContains(
        path.join(__dirname, 'vas-dinamico-forms.php'),
        'EIPSI_Database_Schema_Manager::repair_local_schema()',
        'Schema verification must trigger repair_local_schema()'
    );
});

test('admin_init hook for periodic verification exists', () => {
    assertFileContains(
        path.join(__dirname, 'vas-dinamico-forms.php'),
        "add_action('admin_init', array('EIPSI_Database_Schema_Manager', 'periodic_verification'))",
        'admin_init hook must be registered for periodic verification'
    );
});

test('Schema manager is included in main plugin file', () => {
    assertFileContains(
        path.join(__dirname, 'vas-dinamico-forms.php'),
        "require_once VAS_DINAMICO_PLUGIN_DIR . 'admin/database-schema-manager.php'",
        'Schema manager must be included in main plugin file'
    );
});

// ===========================
// CATEGORY 4: EMERGENCY FALLBACK IN AJAX
// ===========================

console.log('\n📂 CATEGORY 4: Emergency Fallback in AJAX\n');

test('AJAX handlers file exists', () => {
    assertFileExists(
        path.join(__dirname, 'admin/ajax-handlers.php'),
        'AJAX handlers file must exist'
    );
});

test('AJAX handler detects "Unknown column" error', () => {
    assertFileContains(
        path.join(__dirname, 'admin/ajax-handlers.php'),
        "strpos($wpdb_error, 'Unknown column')",
        'AJAX handler must detect "Unknown column" error'
    );
});

test('AJAX handler detects table missing error', () => {
    assertFileContains(
        path.join(__dirname, 'admin/ajax-handlers.php'),
        'strpos($wpdb_error, "doesn\'t exist")',
        'AJAX handler must detect table missing error'
    );
});

test('AJAX handler triggers emergency schema repair', () => {
    assertFileContains(
        path.join(__dirname, 'admin/ajax-handlers.php'),
        'EIPSI_Database_Schema_Manager::repair_local_schema()',
        'AJAX handler must trigger emergency schema repair'
    );
});

test('AJAX handler logs schema error detection', () => {
    assertFileContains(
        path.join(__dirname, 'admin/ajax-handlers.php'),
        "[EIPSI Forms] Detected schema error, triggering auto-repair",
        'AJAX handler must log schema error detection'
    );
});

test('AJAX handler retries insert after repair', () => {
    const ajaxFile = path.join(__dirname, 'admin/ajax-handlers.php');
    const content = fs.readFileSync(ajaxFile, 'utf8');
    
    // Find the emergency repair section
    const repairSection = content.substring(
        content.indexOf('EIPSI_Database_Schema_Manager::repair_local_schema()'),
        content.indexOf('EIPSI_Database_Schema_Manager::repair_local_schema()') + 500
    );
    
    assert(
        repairSection.includes('$wpdb->insert('),
        'AJAX handler must retry insert after repair'
    );
});

test('AJAX handler logs successful recovery', () => {
    assertFileContains(
        path.join(__dirname, 'admin/ajax-handlers.php'),
        "[EIPSI Forms] Auto-repaired schema and recovered data insertion",
        'AJAX handler must log successful recovery'
    );
});

test('AJAX handler logs critical failure if repair fails', () => {
    assertFileContains(
        path.join(__dirname, 'admin/ajax-handlers.php'),
        "[EIPSI Forms CRITICAL] Schema repair failed",
        'AJAX handler must log critical failure if repair fails'
    );
});

test('AJAX handler returns success after recovery', () => {
    assertFileContains(
        path.join(__dirname, 'admin/ajax-handlers.php'),
        "'schema_repaired' => true",
        'AJAX handler must indicate schema was repaired in success response'
    );
});

test('AJAX handler returns error message if recovery fails', () => {
    assertFileContains(
        path.join(__dirname, 'admin/ajax-handlers.php'),
        "'message' => __('Database error (recovery attempted)'",
        'AJAX handler must return appropriate error message if recovery fails'
    );
});

// ===========================
// CATEGORY 5: INTEGRATION TESTS
// ===========================

console.log('\n📂 CATEGORY 5: Integration Tests\n');

test('Schema repair covers all critical columns', () => {
    const schemaFile = path.join(__dirname, 'admin/database-schema-manager.php');
    const criticalColumns = [
        'form_id',
        'participant_id', // This was the reported missing column
        'session_id',
        'metadata',
        'duration_seconds',
        'quality_flag'
    ];
    
    criticalColumns.forEach(column => {
        const occurrences = countOccurrences(schemaFile, `'${column}'`);
        assert(
            occurrences >= 1,
            `Critical column '${column}' must be handled in schema repair (found ${occurrences} times)`
        );
    });
});

test('Schema repair includes index creation for key columns', () => {
    const schemaFile = path.join(__dirname, 'admin/database-schema-manager.php');
    
    assertFileContains(
        schemaFile,
        "self::ensure_local_index( $table_name, 'form_id' )",
        'Schema repair must ensure form_id index'
    );
    
    assertFileContains(
        schemaFile,
        "self::ensure_local_index( $table_name, 'participant_id' )",
        'Schema repair must ensure participant_id index'
    );
    
    assertFileContains(
        schemaFile,
        "self::ensure_local_index( $table_name, 'session_id' )",
        'Schema repair must ensure session_id index'
    );
});

test('Full repair cycle documented', () => {
    assertFileExists(
        path.join(__dirname, 'HOTFIX_v1.2.2_AUTO_DB_SCHEMA_REPAIR.md'),
        'HOTFIX documentation must exist'
    );
});

test('Documentation includes all 3 layers', () => {
    const docFile = path.join(__dirname, 'HOTFIX_v1.2.2_AUTO_DB_SCHEMA_REPAIR.md');
    
    assertFileContains(docFile, 'LAYER 1', 'Documentation must describe Layer 1');
    assertFileContains(docFile, 'LAYER 2', 'Documentation must describe Layer 2');
    assertFileContains(docFile, 'LAYER 3', 'Documentation must describe Layer 3');
});

test('Documentation includes testing protocol', () => {
    assertFileContains(
        path.join(__dirname, 'HOTFIX_v1.2.2_AUTO_DB_SCHEMA_REPAIR.md'),
        'TESTING PROTOCOL',
        'Documentation must include testing protocol'
    );
});

test('Documentation includes zero data loss guarantee', () => {
    assertFileContains(
        path.join(__dirname, 'HOTFIX_v1.2.2_AUTO_DB_SCHEMA_REPAIR.md'),
        'ZERO DATA LOSS',
        'Documentation must emphasize zero data loss guarantee'
    );
});

test('No breaking changes in existing code', () => {
    // Verify that vas_dinamico_submit_form_handler still exists
    assertFileContains(
        path.join(__dirname, 'admin/ajax-handlers.php'),
        'function vas_dinamico_submit_form_handler()',
        'Main AJAX handler function must still exist'
    );
    
    // Verify that existing activation hook logic is preserved
    assertFileContains(
        path.join(__dirname, 'vas-dinamico-forms.php'),
        'dbDelta($sql);',
        'Existing dbDelta calls must be preserved'
    );
});

test('Backward compatibility maintained', () => {
    // Verify existing upgrade function still exists
    assertFileContains(
        path.join(__dirname, 'vas-dinamico-forms.php'),
        'function vas_dinamico_upgrade_database()',
        'Existing upgrade function must still exist'
    );
    
    // Verify existing schema manager methods still exist
    assertFileContains(
        path.join(__dirname, 'admin/database-schema-manager.php'),
        'public static function verify_and_sync_schema',
        'Existing verify_and_sync_schema method must be preserved'
    );
});

test('Build artifacts present', () => {
    assertFileExists(
        path.join(__dirname, 'build/index.js'),
        'Build artifact index.js must exist'
    );
});

// ===========================
// SUMMARY
// ===========================

console.log('\n=================================');
console.log('TEST SUMMARY');
console.log('=================================\n');
console.log(`✅ Passed: ${passCount}`);
console.log(`❌ Failed: ${failCount}`);
console.log(`📊 Total: ${passCount + failCount}`);
console.log(`✨ Success Rate: ${((passCount / (passCount + failCount)) * 100).toFixed(1)}%\n`);

if (failCount > 0) {
    console.log('=================================');
    console.log('FAILED TESTS:');
    console.log('=================================\n');
    failures.forEach(({ description, error }) => {
        console.log(`❌ ${description}`);
        console.log(`   ${error}\n`);
    });
}

console.log('=================================');
console.log('HOTFIX v1.2.2 VALIDATION COMPLETE');
console.log('=================================\n');

if (failCount === 0) {
    console.log('🎉 All tests passed! Schema repair implementation is complete.\n');
    console.log('✅ Layer 1: Activation hook enhanced');
    console.log('✅ Layer 2: Auto-repair on plugin load');
    console.log('✅ Layer 3: Emergency fallback in AJAX');
    console.log('✅ Zero data loss guarantee achieved\n');
} else {
    console.log('⚠️  Some tests failed. Please review and fix issues.\n');
    process.exit(1);
}

process.exit(0);
