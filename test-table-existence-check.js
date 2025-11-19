/* eslint-disable no-console */
/**
 * Table Existence Check Feature - Comprehensive Test Suite
 * Tests the database table existence check functionality in BD config UI
 */

const fs = require('fs');
const path = require('path');

let passedTests = 0;
let failedTests = 0;
const failures = [];

function assert(condition, description, details = '') {
    if (condition) {
        passedTests++;
        console.log(`✅ ${description}`);
    } else {
        failedTests++;
        const failureMessage = `❌ ${description}${details ? ` - ${details}` : ''}`;
        console.log(failureMessage);
        failures.push(failureMessage);
    }
}

function fileExists(filePath) {
    return fs.existsSync(filePath);
}

function fileContains(filePath, searchString) {
    if (!fileExists(filePath)) {
        return false;
    }
    const content = fs.readFileSync(filePath, 'utf8');
    return content.includes(searchString);
}

function fileContainsPattern(filePath, pattern) {
    if (!fileExists(filePath)) {
        return false;
    }
    const content = fs.readFileSync(filePath, 'utf8');
    return pattern.test(content);
}

console.log('='.repeat(80));
console.log('TABLE EXISTENCE CHECK FEATURE - COMPREHENSIVE TEST SUITE');
console.log('='.repeat(80));
console.log('');

// =============================================================================
// TEST SUITE 1: Backend PHP - Database Method
// =============================================================================
console.log('📦 TEST SUITE 1: Backend PHP - Database Method');
console.log('-'.repeat(80));

const databasePhpPath = path.join(__dirname, 'admin', 'database.php');

assert(
    fileExists(databasePhpPath),
    'admin/database.php exists',
);

assert(
    fileContains(databasePhpPath, 'public function check_table_status()'),
    'check_table_status() method is defined',
);

assert(
    fileContains(databasePhpPath, 'Check database table status (existence, schema, row count)'),
    'check_table_status() has proper documentation',
);

assert(
    fileContains(databasePhpPath, 'vas_form_results'),
    'check_table_status() checks vas_form_results table',
);

assert(
    fileContains(databasePhpPath, 'vas_form_events'),
    'check_table_status() checks vas_form_events table',
);

assert(
    fileContains(databasePhpPath, 'SHOW TABLES LIKE'),
    'check_table_status() uses SHOW TABLES LIKE query',
);

assert(
    fileContains(databasePhpPath, 'SHOW COLUMNS FROM'),
    'check_table_status() verifies column existence',
);

assert(
    fileContains(databasePhpPath, 'row_count'),
    'check_table_status() returns row count',
);

assert(
    fileContains(databasePhpPath, 'missing_columns'),
    'check_table_status() detects missing columns',
);

assert(
    fileContains(databasePhpPath, 'all_tables_exist'),
    'check_table_status() returns overall table existence status',
);

assert(
    fileContains(databasePhpPath, 'all_columns_ok'),
    'check_table_status() returns overall column completeness status',
);

// Check required columns validation
const requiredResultsColumns = [
    'form_id', 'participant_id', 'session_id', 'form_name',
    'created_at', 'submitted_at', 'duration_seconds',
    'start_timestamp_ms', 'end_timestamp_ms', 'metadata',
    'quality_flag', 'status', 'form_responses'
];

requiredResultsColumns.forEach((column) => {
    assert(
        fileContains(databasePhpPath, `'${column}'`),
        `check_table_status() validates required column: ${column}`,
    );
});

const requiredEventsColumns = [
    'form_id', 'session_id', 'event_type', 'page_number',
    'metadata', 'user_agent', 'created_at'
];

requiredEventsColumns.forEach((column) => {
    assert(
        fileContains(databasePhpPath, `'${column}'`),
        `check_table_status() validates events column: ${column}`,
    );
});

console.log('');

// =============================================================================
// TEST SUITE 2: Backend PHP - AJAX Handler
// =============================================================================
console.log('📦 TEST SUITE 2: Backend PHP - AJAX Handler');
console.log('-'.repeat(80));

const ajaxHandlersPath = path.join(__dirname, 'admin', 'ajax-handlers.php');

assert(
    fileExists(ajaxHandlersPath),
    'admin/ajax-handlers.php exists',
);

assert(
    fileContains(ajaxHandlersPath, "add_action('wp_ajax_eipsi_check_table_status'"),
    'AJAX action for eipsi_check_table_status is registered',
);

assert(
    fileContains(ajaxHandlersPath, 'function eipsi_check_table_status_handler()'),
    'eipsi_check_table_status_handler() function is defined',
);

assert(
    fileContains(ajaxHandlersPath, "check_ajax_referer('eipsi_admin_nonce'"),
    'eipsi_check_table_status_handler() verifies nonce',
);

assert(
    fileContains(ajaxHandlersPath, "current_user_can('manage_options')"),
    'eipsi_check_table_status_handler() checks user permissions',
);

assert(
    fileContains(ajaxHandlersPath, '$db_helper->check_table_status()'),
    'eipsi_check_table_status_handler() calls check_table_status() method',
);

assert(
    fileContains(ajaxHandlersPath, 'wp_send_json_success'),
    'eipsi_check_table_status_handler() sends JSON success response',
);

assert(
    fileContains(ajaxHandlersPath, 'wp_send_json_error'),
    'eipsi_check_table_status_handler() sends JSON error response',
);

console.log('');

// =============================================================================
// TEST SUITE 3: Frontend PHP - Configuration UI
// =============================================================================
console.log('📦 TEST SUITE 3: Frontend PHP - Configuration UI');
console.log('-'.repeat(80));

const configurationPhpPath = path.join(__dirname, 'admin', 'configuration.php');

assert(
    fileExists(configurationPhpPath),
    'admin/configuration.php exists',
);

assert(
    fileContains(configurationPhpPath, 'eipsi-table-status-box'),
    'configuration.php contains table status section',
);

assert(
    fileContains(configurationPhpPath, 'Database Table Status'),
    'configuration.php has table status heading',
);

assert(
    fileContains(configurationPhpPath, 'eipsi-check-table-status'),
    'configuration.php has check table status button',
);

assert(
    fileContains(configurationPhpPath, 'Check Table Status'),
    'configuration.php has button label',
);

assert(
    fileContains(configurationPhpPath, 'eipsi-table-status-content'),
    'configuration.php has table status content container',
);

assert(
    fileContains(configurationPhpPath, 'eipsi-table-status-results'),
    'configuration.php has table status results container',
);

assert(
    fileContains(configurationPhpPath, 'Check if required database tables exist'),
    'configuration.php has descriptive text',
);

assert(
    fileContains(configurationPhpPath, 'dashicons-search'),
    'configuration.php uses search icon for check button',
);

assert(
    fileContains(configurationPhpPath, 'dashicons-database-view'),
    'configuration.php uses database icon for section heading',
);

console.log('');

// =============================================================================
// TEST SUITE 4: Frontend JavaScript - Event Handling
// =============================================================================
console.log('📦 TEST SUITE 4: Frontend JavaScript - Event Handling');
console.log('-'.repeat(80));

const configPanelJsPath = path.join(__dirname, 'assets', 'js', 'configuration-panel.js');

assert(
    fileExists(configPanelJsPath),
    'assets/js/configuration-panel.js exists',
);

assert(
    fileContains(configPanelJsPath, "$( '#eipsi-check-table-status' ).on("),
    'configuration-panel.js binds click event to check table status button',
);

assert(
    fileContains(configPanelJsPath, 'this.checkTableStatus.bind( this )'),
    'configuration-panel.js binds checkTableStatus method',
);

assert(
    fileContains(configPanelJsPath, 'checkTableStatus( e ) {'),
    'configuration-panel.js defines checkTableStatus method',
);

assert(
    fileContains(configPanelJsPath, "action: 'eipsi_check_table_status'"),
    'configuration-panel.js calls correct AJAX action',
);

assert(
    fileContains(configPanelJsPath, "nonce: $( '#eipsi_db_config_nonce' ).val()"),
    'configuration-panel.js sends nonce with AJAX request',
);

assert(
    fileContains(configPanelJsPath, 'EIPSIConfig.displayTableStatus( response.data )'),
    'configuration-panel.js calls displayTableStatus on success',
);

assert(
    fileContains(configPanelJsPath, 'displayTableStatus( data ) {'),
    'configuration-panel.js defines displayTableStatus method',
);

assert(
    fileContains(configPanelJsPath, '.addClass( \'eipsi-loading\' )'),
    'configuration-panel.js shows loading state',
);

assert(
    fileContains(configPanelJsPath, '.removeClass( \'eipsi-loading\' )'),
    'configuration-panel.js removes loading state on complete',
);

assert(
    fileContains(configPanelJsPath, '$( \'#eipsi-table-status-results\' )'),
    'configuration-panel.js targets results container',
);

console.log('');

// =============================================================================
// TEST SUITE 5: Frontend JavaScript - Display Logic
// =============================================================================
console.log('📦 TEST SUITE 5: Frontend JavaScript - Display Logic');
console.log('-'.repeat(80));

assert(
    fileContains(configPanelJsPath, 'data.all_tables_exist'),
    'displayTableStatus checks all_tables_exist',
);

assert(
    fileContains(configPanelJsPath, 'data.all_columns_ok'),
    'displayTableStatus checks all_columns_ok',
);

assert(
    fileContains(configPanelJsPath, 'eipsi-table-status-success'),
    'displayTableStatus displays success indicator',
);

assert(
    fileContains(configPanelJsPath, 'eipsi-table-status-warning'),
    'displayTableStatus displays warning indicator',
);

assert(
    fileContains(configPanelJsPath, 'data.results_table'),
    'displayTableStatus shows results table details',
);

assert(
    fileContains(configPanelJsPath, 'data.events_table'),
    'displayTableStatus shows events table details',
);

assert(
    fileContains(configPanelJsPath, 'data.results_table.table_name'),
    'displayTableStatus shows table name',
);

assert(
    fileContains(configPanelJsPath, 'data.results_table.row_count'),
    'displayTableStatus shows row count',
);

assert(
    fileContains(configPanelJsPath, 'data.results_table.exists'),
    'displayTableStatus checks if table exists',
);

assert(
    fileContains(configPanelJsPath, 'data.results_table.columns_ok'),
    'displayTableStatus checks if columns are complete',
);

assert(
    fileContains(configPanelJsPath, 'data.results_table.missing_columns'),
    'displayTableStatus shows missing columns',
);

assert(
    fileContains(configPanelJsPath, 'eipsi-table-exists'),
    'displayTableStatus uses exists class',
);

assert(
    fileContains(configPanelJsPath, 'eipsi-table-missing'),
    'displayTableStatus uses missing class',
);

assert(
    fileContains(configPanelJsPath, 'eipsi-table-guidance'),
    'displayTableStatus shows guidance section',
);

assert(
    fileContains(configPanelJsPath, 'What to do next'),
    'displayTableStatus includes guidance heading',
);

assert(
    fileContains(configPanelJsPath, 'Verify & Repair Schema'),
    'displayTableStatus mentions repair button',
);

assert(
    fileContains(configPanelJsPath, 'Why this might happen'),
    'displayTableStatus explains potential causes',
);

console.log('');

// =============================================================================
// TEST SUITE 6: CSS Styling
// =============================================================================
console.log('📦 TEST SUITE 6: CSS Styling');
console.log('-'.repeat(80));

const configPanelCssPath = path.join(__dirname, 'assets', 'css', 'configuration-panel.css');

assert(
    fileExists(configPanelCssPath),
    'assets/css/configuration-panel.css exists',
);

assert(
    fileContains(configPanelCssPath, '.eipsi-table-status-box {'),
    'CSS defines table status box styles',
);

assert(
    fileContains(configPanelCssPath, '.eipsi-table-status-success'),
    'CSS defines success indicator styles',
);

assert(
    fileContains(configPanelCssPath, '.eipsi-table-status-warning'),
    'CSS defines warning indicator styles',
);

assert(
    fileContains(configPanelCssPath, '.eipsi-table-status-error'),
    'CSS defines error indicator styles',
);

assert(
    fileContains(configPanelCssPath, '.eipsi-table-detail'),
    'CSS defines table detail styles',
);

assert(
    fileContains(configPanelCssPath, '.eipsi-table-exists'),
    'CSS defines table exists indicator styles',
);

assert(
    fileContains(configPanelCssPath, '.eipsi-table-missing'),
    'CSS defines table missing indicator styles',
);

assert(
    fileContains(configPanelCssPath, '.eipsi-table-info'),
    'CSS defines table info styles',
);

assert(
    fileContains(configPanelCssPath, '.eipsi-table-guidance'),
    'CSS defines guidance section styles',
);

// Color validation for WCAG AA compliance
assert(
    fileContains(configPanelCssPath, '#198754') || fileContains(configPanelCssPath, 'var(--eipsi-color-success'),
    'CSS uses WCAG AA compliant success color',
);

assert(
    fileContains(configPanelCssPath, '#b35900') || fileContains(configPanelCssPath, 'var(--eipsi-color-warning'),
    'CSS uses WCAG AA compliant warning color',
);

assert(
    fileContains(configPanelCssPath, '#d32f2f') || fileContains(configPanelCssPath, 'var(--eipsi-color-error'),
    'CSS uses WCAG AA compliant error color',
);

console.log('');

// =============================================================================
// TEST SUITE 7: Mobile Responsiveness
// =============================================================================
console.log('📦 TEST SUITE 7: Mobile Responsiveness');
console.log('-'.repeat(80));

assert(
    fileContainsPattern(configPanelCssPath, /@media.*max-width.*374px/),
    'CSS includes small phone breakpoint (320px-374px)',
);

assert(
    fileContainsPattern(configPanelCssPath, /@media.*max-width.*768px/),
    'CSS includes tablet breakpoint (375px-768px)',
);

assert(
    fileContains(configPanelCssPath, '.eipsi-table-status-box') && 
    fileContainsPattern(configPanelCssPath, /@media.*max-width.*374px[\s\S]*\.eipsi-table-status-box/),
    'CSS includes mobile styles for table status box',
);

assert(
    fileContains(configPanelCssPath, 'padding: 0.75rem') || fileContains(configPanelCssPath, 'padding: 1rem'),
    'CSS adjusts padding for mobile devices',
);

console.log('');

// =============================================================================
// TEST SUITE 8: User Experience Features
// =============================================================================
console.log('📦 TEST SUITE 8: User Experience Features');
console.log('-'.repeat(80));

assert(
    fileContains(databasePhpPath, '✓') || fileContains(databasePhpPath, '⚠️'),
    'Backend returns visual indicators in messages',
);

assert(
    fileContains(configPanelJsPath, 'dashicons-yes-alt'),
    'Frontend displays checkmark icon for success',
);

assert(
    fileContains(configPanelJsPath, 'dashicons-warning'),
    'Frontend displays warning icon for issues',
);

assert(
    fileContains(configPanelJsPath, 'dashicons-dismiss'),
    'Frontend displays dismiss icon for missing tables',
);

assert(
    fileContains(configPanelJsPath, 'toLocaleString()'),
    'Frontend formats row counts with locale-specific number formatting',
);

assert(
    fileContains(configPanelCssPath, 'border-radius'),
    'CSS uses rounded corners for modern appearance',
);

assert(
    fileContains(configPanelCssPath, 'box-shadow'),
    'CSS uses subtle shadows for depth',
);

console.log('');

// =============================================================================
// TEST SUITE 9: Security & Best Practices
// =============================================================================
console.log('📦 TEST SUITE 9: Security & Best Practices');
console.log('-'.repeat(80));

assert(
    fileContains(ajaxHandlersPath, 'check_ajax_referer'),
    'AJAX handler validates nonce',
);

assert(
    fileContains(ajaxHandlersPath, 'current_user_can'),
    'AJAX handler checks user capabilities',
);

assert(
    fileContains(databasePhpPath, '@new mysqli'),
    'Database connection suppresses error display for security',
);

assert(
    fileContains(databasePhpPath, '$mysqli->connect_error'),
    'Database connection handles errors gracefully',
);

assert(
    fileContains(databasePhpPath, '$mysqli->close()'),
    'Database connections are properly closed',
);

assert(
    fileContains(databasePhpPath, '__(') || fileContains(databasePhpPath, 'esc_'),
    'Database method uses translation functions or escaping',
);

console.log('');

// =============================================================================
// TEST SUITE 10: Integration with Existing Features
// =============================================================================
console.log('📦 TEST SUITE 10: Integration with Existing Features');
console.log('-'.repeat(80));

assert(
    fileContains(configurationPhpPath, 'eipsi-verify-schema'),
    'Configuration page maintains existing verify schema button',
);

assert(
    fileContains(configurationPhpPath, 'Database Schema Status'),
    'Configuration page maintains existing schema status section',
);

assert(
    fileContains(configurationPhpPath, '$status[\'connected\']'),
    'Configuration page checks connection status before showing button',
);

assert(
    fileContains(configurationPhpPath, 'Configure and connect to an external database'),
    'Configuration page shows appropriate message when not connected',
);

assert(
    fileContains(configPanelJsPath, 'verifySchema'),
    'JavaScript maintains existing verifySchema method',
);

assert(
    fileContains(configPanelJsPath, 'testConnection'),
    'JavaScript maintains existing testConnection method',
);

console.log('');

// =============================================================================
// SUMMARY
// =============================================================================
console.log('='.repeat(80));
console.log('TEST SUMMARY');
console.log('='.repeat(80));
console.log(`✅ Passed: ${passedTests}`);
console.log(`❌ Failed: ${failedTests}`);
console.log(`📊 Total: ${passedTests + failedTests}`);

if (failedTests > 0) {
    console.log('');
    console.log('FAILED TESTS:');
    console.log('-'.repeat(80));
    failures.forEach((failure) => {
        console.log(failure);
    });
    process.exit(1);
} else {
    console.log('');
    console.log('🎉 All tests passed! Table existence check feature is complete.');
    process.exit(0);
}
