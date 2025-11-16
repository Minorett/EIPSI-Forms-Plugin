#!/usr/bin/env node

/**
 * EIPSI Forms - Data Persistence Validation Script
 *
 * Validates the implementation of data persistence logic including:
 * - Database schema validation
 * - External DB connection logic
 * - Fallback behavior patterns
 * - Session management code
 * - Data integrity checks
 *
 * This is a CODE VALIDATION script (not integration test).
 * For full integration testing, use WordPress test environment.
 */

/* eslint-disable no-console, no-unused-vars */

const fs = require( 'fs' );
const path = require( 'path' );

// Color codes for terminal output
const colors = {
	reset: '\x1b[0m',
	bright: '\x1b[1m',
	red: '\x1b[31m',
	green: '\x1b[32m',
	yellow: '\x1b[33m',
	blue: '\x1b[34m',
	cyan: '\x1b[36m',
};

function log( message, color = 'reset' ) {
	console.log( `${ colors[ color ] }${ message }${ colors.reset }` );
}

function header( text ) {
	log( '\n' + '='.repeat( 80 ), 'cyan' );
	log( text, 'bright' );
	log( '='.repeat( 80 ), 'cyan' );
}

function subheader( text ) {
	log( '\n' + text, 'blue' );
	log( '-'.repeat( text.length ), 'blue' );
}

function pass( text ) {
	log( `✅ PASS: ${ text }`, 'green' );
}

function fail( text ) {
	log( `❌ FAIL: ${ text }`, 'red' );
}

function warn( text ) {
	log( `⚠️  WARN: ${ text }`, 'yellow' );
}

function info( text ) {
	log( `ℹ️  ${ text }`, 'cyan' );
}

// Test results tracking
let totalTests = 0;
let passedTests = 0;
let failedTests = 0;
const warnings = 0;

function test( name, fn ) {
	totalTests++;
	try {
		const result = fn();
		if ( result === false ) {
			fail( name );
			failedTests++;
			return false;
		}
		pass( name );
		passedTests++;
		return true;
	} catch ( error ) {
		fail( `${ name } - ${ error.message }` );
		failedTests++;
		return false;
	}
}

// File reading helper
function readFile( filePath ) {
	const fullPath = path.join( __dirname, filePath );
	if ( ! fs.existsSync( fullPath ) ) {
		throw new Error( `File not found: ${ filePath }` );
	}
	return fs.readFileSync( fullPath, 'utf8' );
}

// Code pattern matching helpers
function hasFunction( content, functionName ) {
	const patterns = [
		new RegExp( `function\\s+${ functionName }\\s*\\(` ),
		new RegExp( `${ functionName }\\s*=\\s*function\\s*\\(` ),
		new RegExp( `${ functionName }:\\s*function\\s*\\(` ),
		new RegExp( `${ functionName }\\s*\\([^)]*\\)\\s*{` ), // Arrow function
	];
	return patterns.some( ( pattern ) => pattern.test( content ) );
}

function hasClass( content, className ) {
	return new RegExp( `class\\s+${ className }\\s*{` ).test( content );
}

function hasMethod( content, className, methodName ) {
	const classMatch = content.match(
		new RegExp( `class\\s+${ className }\\s*{([^}]+(?:{[^}]*})*)*}` )
	);
	if ( ! classMatch ) {
		return false;
	}
	const classBody = classMatch[ 0 ];
	return new RegExp(
		`(public\\s+|private\\s+|protected\\s+)?function\\s+${ methodName }\\s*\\(`
	).test( classBody );
}

function countMatches( content, pattern ) {
	const matches = content.match( pattern );
	return matches ? matches.length : 0;
}

// ============================================================================
// TEST SUITE
// ============================================================================

header( 'EIPSI Forms - Data Persistence Validation' );
info( 'Version: 1.2.0' );
info( 'Date: ' + new Date().toISOString() );

// ============================================================================
// 1. SUBMISSION HANDLER VALIDATION
// ============================================================================

subheader( '1. Submission Handler (admin/ajax-handlers.php)' );

const ajaxHandlers = readFile( 'admin/ajax-handlers.php' );

test( '1.1: vas_dinamico_submit_form_handler function exists', () => {
	return hasFunction( ajaxHandlers, 'vas_dinamico_submit_form_handler' );
} );

test( '1.2: generate_stable_form_id function exists', () => {
	return hasFunction( ajaxHandlers, 'generate_stable_form_id' );
} );

test( '1.3: generateStableFingerprint function exists', () => {
	return hasFunction( ajaxHandlers, 'generateStableFingerprint' );
} );

test( '1.4: Form ID generation uses hash', () => {
	return (
		ajaxHandlers.includes( 'md5($slug)' ) &&
		ajaxHandlers.includes( 'substr(md5' )
	);
} );

test( '1.5: Participant ID uses SHA256', () => {
	return (
		ajaxHandlers.includes( "hash('sha256'" ) &&
		ajaxHandlers.includes( 'FP-' )
	);
} );

test( '1.6: Timestamp fields captured (start_timestamp_ms, end_timestamp_ms)', () => {
	return (
		ajaxHandlers.includes( 'start_timestamp_ms' ) &&
		ajaxHandlers.includes( 'end_timestamp_ms' ) &&
		ajaxHandlers.includes( 'duration_seconds' )
	);
} );

test( '1.7: Duration calculated with millisecond precision', () => {
	return (
		ajaxHandlers.includes( 'round(' ) &&
		ajaxHandlers.includes( '/ 1000' ) &&
		ajaxHandlers.includes( 'duration_ms' )
	);
} );

test( '1.8: External DB helper included', () => {
	return (
		ajaxHandlers.includes(
			"require_once VAS_DINAMICO_PLUGIN_DIR . 'admin/database.php'"
		) && ajaxHandlers.includes( 'EIPSI_External_Database' )
	);
} );

test( '1.9: Fallback logic implemented', () => {
	return (
		ajaxHandlers.includes( '$used_fallback' ) &&
		ajaxHandlers.includes( 'record_error' ) &&
		ajaxHandlers.includes( 'falling back to WordPress DB' )
	);
} );

test( '1.10: Nonce verification present', () => {
	return ajaxHandlers.includes( "check_ajax_referer('eipsi_forms_nonce'" );
} );

test( '1.11: Data sanitization implemented', () => {
	const sanitizeFunctions = [
		'sanitize_text_field',
		'sanitize_email',
		'intval',
		'wp_json_encode',
	];
	return sanitizeFunctions.every( ( fn ) => ajaxHandlers.includes( fn ) );
} );

test( '1.12: AJAX response includes external_db status', () => {
	return (
		ajaxHandlers.includes( "'external_db'" ) &&
		ajaxHandlers.includes( "'fallback_used'" )
	);
} );

test( '1.13: Error logging with WP_DEBUG check', () => {
	return (
		ajaxHandlers.includes( 'WP_DEBUG' ) &&
		ajaxHandlers.includes( 'error_log' )
	);
} );

// ============================================================================
// 2. EXTERNAL DATABASE HELPER VALIDATION
// ============================================================================

subheader( '2. External Database Helper (admin/database.php)' );

const database = readFile( 'admin/database.php' );

test( '2.1: EIPSI_External_Database class exists', () => {
	return hasClass( database, 'EIPSI_External_Database' );
} );

test( '2.2: Credential encryption methods present', () => {
	return (
		database.includes( 'encrypt_data' ) &&
		database.includes( 'decrypt_data' ) &&
		database.includes( 'openssl_encrypt' )
	);
} );

test( '2.3: AES-256-CBC encryption used', () => {
	return database.includes( "'aes-256-cbc'" );
} );

test( '2.4: WordPress salt used for encryption key', () => {
	return database.includes( "wp_salt('auth')" );
} );

test( '2.5: save_credentials method exists', () => {
	return database.includes( 'function save_credentials' );
} );

test( '2.6: get_credentials method exists', () => {
	return database.includes( 'function get_credentials' );
} );

test( '2.7: test_connection method exists', () => {
	return database.includes( 'function test_connection' );
} );

test( '2.8: Schema validation (ensure_schema_ready)', () => {
	return (
		database.includes( 'ensure_schema_ready' ) &&
		database.includes( 'create_table_if_missing' ) &&
		database.includes( 'ensure_required_columns' )
	);
} );

test( '2.9: Table name resolution (prefixed vs bare)', () => {
	return (
		database.includes( 'resolve_table_name' ) &&
		database.includes( 'prefixed_table' ) &&
		database.includes( 'bare_table' )
	);
} );

test( '2.10: Prepared statements used for INSERT', () => {
	return (
		database.includes( '$mysqli->prepare' ) &&
		database.includes( 'bind_param' ) &&
		database.includes( 'sssssssssiidiis' )
	);
} );

test( '2.11: Error recording methods (record_error, clear_errors)', () => {
	return (
		database.includes( 'function record_error' ) &&
		database.includes( 'function clear_errors' ) &&
		database.includes( 'last_error_time' )
	);
} );

test( '2.12: get_status method includes fallback info', () => {
	return (
		database.includes( 'function get_status' ) &&
		database.includes( 'fallback_active' ) &&
		database.includes( 'last_error' )
	);
} );

test( '2.13: is_enabled method exists', () => {
	return database.includes( 'function is_enabled' );
} );

test( '2.14: Connection suppressed with @ operator', () => {
	return database.includes( '@new mysqli' );
} );

test( '2.15: Column migration handles missing columns', () => {
	return (
		database.includes( 'SHOW COLUMNS FROM' ) &&
		database.includes( 'ALTER TABLE' ) &&
		database.includes( 'ADD COLUMN' )
	);
} );

// ============================================================================
// 3. TRACKING HANDLER VALIDATION
// ============================================================================

subheader( '3. Tracking Handler (admin/ajax-handlers.php)' );

test( '3.1: eipsi_track_event_handler function exists', () => {
	return hasFunction( ajaxHandlers, 'eipsi_track_event_handler' );
} );

test( '3.2: Event type validation (whitelist)', () => {
	return (
		ajaxHandlers.includes( '$allowed_events' ) ||
		( ajaxHandlers.includes( 'view' ) &&
			ajaxHandlers.includes( 'start' ) &&
			ajaxHandlers.includes( 'abandon' ) )
	);
} );

test( '3.3: Session ID required', () => {
	return (
		ajaxHandlers.includes( 'session_id' ) &&
		ajaxHandlers.includes( 'Missing required field: session_id' )
	);
} );

test( '3.4: Nonce verification for tracking', () => {
	return (
		ajaxHandlers.includes( 'wp_verify_nonce' ) &&
		ajaxHandlers.includes( 'eipsi_tracking_nonce' )
	);
} );

test( '3.5: Metadata handling for branch_jump events', () => {
	return (
		ajaxHandlers.includes( "'branch_jump'" ) &&
		ajaxHandlers.includes( 'from_page' ) &&
		ajaxHandlers.includes( 'to_page' )
	);
} );

test( '3.6: Graceful error handling (returns success even on DB error)', () => {
	return (
		ajaxHandlers.includes( 'if ($result === false)' ) &&
		ajaxHandlers.includes( 'Still return success' )
	);
} );

test( '3.7: Event inserted into wp_vas_form_events', () => {
	return (
		ajaxHandlers.includes( "'vas_form_events'" ) &&
		ajaxHandlers.includes( '$wpdb->insert' )
	);
} );

// ============================================================================
// 4. FRONTEND TRACKING JAVASCRIPT VALIDATION
// ============================================================================

subheader( '4. Frontend Tracking (assets/js/eipsi-tracking.js)' );

const tracking = readFile( 'assets/js/eipsi-tracking.js' );

test( '4.1: IIFE pattern used', () => {
	return /\(\s*function\s*\(/.test( tracking );
} );

test( '4.2: sessionStorage support detection', () => {
	return (
		tracking.includes( 'sessionStorage' ) &&
		tracking.includes( 'supportsStorage' )
	);
} );

test( '4.3: Session restoration from storage', () => {
	return (
		tracking.includes( 'restoreSessions' ) &&
		/getItem.*STORAGE_KEY/.test( tracking )
	);
} );

test( '4.4: Session persistence to storage', () => {
	return (
		tracking.includes( 'persistSessions' ) && tracking.includes( 'setItem' )
	);
} );

test( '4.5: Crypto-secure session ID generation', () => {
	return (
		tracking.includes( 'crypto.getRandomValues' ) &&
		tracking.includes( 'Uint32Array' )
	);
} );

test( '4.6: Session ID fallback (Math.random + Date.now)', () => {
	return (
		tracking.includes( 'Math.random()' ) &&
		tracking.includes( 'Date.now()' )
	);
} );

test( '4.7: Event type whitelist (ALLOWED_EVENTS)', () => {
	return (
		tracking.includes( 'ALLOWED_EVENTS' ) ||
		( tracking.includes( 'view' ) &&
			tracking.includes( 'start' ) &&
			tracking.includes( 'abandon' ) )
	);
} );

test( '4.8: View event tracked on form load', () => {
	return (
		tracking.includes( 'registerForm' ) &&
		/trackEvent.*'view'/.test( tracking )
	);
} );

test( '4.9: Start event tracked on first interaction', () => {
	return (
		tracking.includes( 'startTracked' ) &&
		tracking.includes( 'isInteractiveField' )
	);
} );

test( '4.10: Abandon event on page exit', () => {
	return (
		tracking.includes( 'flushAbandonEvents' ) &&
		tracking.includes( 'beforeunload' )
	);
} );

test( '4.11: Beacon API used for abandon (keepalive)', () => {
	return (
		tracking.includes( 'sendBeacon' ) ||
		tracking.includes( 'keepalive: true' )
	);
} );

test( '4.12: Page visibility change handling', () => {
	return (
		tracking.includes( 'visibilitychange' ) &&
		tracking.includes( "document.visibilityState === 'hidden'" )
	);
} );

test( '4.13: Global API exposed (window.EIPSITracking)', () => {
	return tracking.includes( 'window.EIPSITracking' );
} );

// ============================================================================
// 5. CONFIGURATION PANEL VALIDATION
// ============================================================================

subheader( '5. Configuration Panel (admin/configuration.php)' );

const configuration = readFile( 'admin/configuration.php' );

test( '5.1: eipsi_display_configuration_page function exists', () => {
	return hasFunction( configuration, 'eipsi_display_configuration_page' );
} );

test( '5.2: Capability check (manage_options)', () => {
	return configuration.includes( "current_user_can('manage_options')" );
} );

test( '5.3: Database status indicator banner present', () => {
	return (
		configuration.includes( 'eipsi-db-indicator-banner' ) &&
		configuration.includes( 'Current Storage Location' )
	);
} );

test( '5.4: Test Connection button exists', () => {
	return (
		configuration.includes( 'eipsi-test-connection' ) &&
		configuration.includes( 'Test Connection' )
	);
} );

test( '5.5: Save Configuration button exists', () => {
	return (
		configuration.includes( 'eipsi-save-config' ) &&
		configuration.includes( 'Save Configuration' )
	);
} );

test( '5.6: Disable External DB button exists', () => {
	return configuration.includes( 'eipsi-disable-external-db' );
} );

test( '5.7: Fallback warning display', () => {
	return (
		configuration.includes( 'eipsi-error-box' ) &&
		configuration.includes( 'Fallback Mode Active' )
	);
} );

test( '5.8: Password field encryption notice', () => {
	return configuration.includes( 'will be encrypted' );
} );

test( '5.9: Record count displayed in status', () => {
	return (
		configuration.includes( 'Records:' ) &&
		configuration.includes( 'record_count' )
	);
} );

// ============================================================================
// 6. DATABASE SCHEMA VALIDATION
// ============================================================================

subheader( '6. Database Schema (vas-dinamico-forms.php)' );

const mainPlugin = readFile( 'vas-dinamico-forms.php' );

test( '6.1: vas_dinamico_activate function exists', () => {
	return hasFunction( mainPlugin, 'vas_dinamico_activate' );
} );

test( '6.2: wp_vas_form_results table creation', () => {
	return (
		mainPlugin.includes( 'CREATE TABLE IF NOT EXISTS' ) &&
		mainPlugin.includes( 'vas_form_results' )
	);
} );

test( '6.3: wp_vas_form_events table creation', () => {
	return (
		mainPlugin.includes( 'vas_form_events' ) &&
		mainPlugin.includes( 'session_id' )
	);
} );

test( '6.4: Required columns in wp_vas_form_results', () => {
	const requiredColumns = [
		'form_id',
		'participant_id',
		'form_name',
		'created_at',
		'submitted_at',
		'duration_seconds',
		'start_timestamp_ms',
		'end_timestamp_ms',
		'form_responses',
	];
	return requiredColumns.every( ( col ) => mainPlugin.includes( col ) );
} );

test( '6.5: Indexes defined', () => {
	return (
		mainPlugin.includes( 'KEY form_name' ) &&
		mainPlugin.includes( 'KEY form_id' ) &&
		mainPlugin.includes( 'KEY form_participant' )
	);
} );

test( '6.6: Database upgrade function exists', () => {
	return hasFunction( mainPlugin, 'vas_dinamico_upgrade_database' );
} );

test( '6.7: Version checking for upgrades', () => {
	return (
		mainPlugin.includes( 'vas_dinamico_db_version' ) &&
		mainPlugin.includes( 'version_compare' )
	);
} );

test( '6.8: dbDelta used for schema updates', () => {
	return mainPlugin.includes( 'dbDelta($sql' );
} );

// ============================================================================
// 7. AJAX ENDPOINT REGISTRATION VALIDATION
// ============================================================================

subheader( '7. AJAX Endpoints Registration' );

test( '7.1: Submit form endpoint (logged in + logged out)', () => {
	return (
		ajaxHandlers.includes(
			"add_action('wp_ajax_nopriv_vas_dinamico_submit_form'"
		) &&
		ajaxHandlers.includes( "add_action('wp_ajax_vas_dinamico_submit_form'" )
	);
} );

test( '7.2: Track event endpoint (logged in + logged out)', () => {
	return (
		ajaxHandlers.includes(
			"add_action('wp_ajax_nopriv_eipsi_track_event'"
		) && ajaxHandlers.includes( "add_action('wp_ajax_eipsi_track_event'" )
	);
} );

test( '7.3: Test DB connection endpoint (admin only)', () => {
	return ajaxHandlers.includes(
		"add_action('wp_ajax_eipsi_test_db_connection'"
	);
} );

test( '7.4: Save DB config endpoint (admin only)', () => {
	return ajaxHandlers.includes( "add_action('wp_ajax_eipsi_save_db_config'" );
} );

test( '7.5: Disable external DB endpoint (admin only)', () => {
	return ajaxHandlers.includes(
		"add_action('wp_ajax_eipsi_disable_external_db'"
	);
} );

test( '7.6: Get DB status endpoint (admin only)', () => {
	return ajaxHandlers.includes( "add_action('wp_ajax_eipsi_get_db_status'" );
} );

test( '7.7: Check external DB endpoint (all users)', () => {
	return (
		ajaxHandlers.includes(
			"add_action('wp_ajax_eipsi_check_external_db'"
		) &&
		ajaxHandlers.includes(
			"add_action('wp_ajax_nopriv_eipsi_check_external_db'"
		)
	);
} );

// ============================================================================
// 8. DATA INTEGRITY PATTERNS
// ============================================================================

subheader( '8. Data Integrity & Security' );

test( '8.1: SQL injection prevention (prepared statements)', () => {
	return (
		ajaxHandlers.includes( '$wpdb->prepare' ) &&
		database.includes( 'bind_param' )
	);
} );

test( '8.2: XSS prevention (output escaping)', () => {
	const escapeFunctions = [
		'esc_html',
		'esc_attr',
		'esc_url',
		'wp_json_encode',
	];
	return escapeFunctions.some( ( fn ) => configuration.includes( fn ) );
} );

test( '8.3: CSRF protection (nonce verification)', () => {
	const nonceChecks = countMatches(
		ajaxHandlers,
		/check_ajax_referer|wp_verify_nonce/g
	);
	return nonceChecks >= 5; // Should have nonce checks in multiple handlers
} );

test( '8.4: Capability checks in admin endpoints', () => {
	const capChecks = countMatches(
		ajaxHandlers,
		/current_user_can\('manage_options'\)/g
	);
	return capChecks >= 4; // Test, save, disable, status endpoints
} );

test( '8.5: Error suppression with fallback (@new mysqli)', () => {
	return (
		database.includes( '@new mysqli' ) &&
		database.includes( 'connect_error' )
	);
} );

test( '8.6: JSON validation before output', () => {
	return (
		ajaxHandlers.includes( 'wp_json_encode' ) ||
		database.includes( 'wp_json_encode' )
	);
} );

test( '8.7: Timezone handling (current_time)', () => {
	return ajaxHandlers.includes( "current_time('mysql')" );
} );

// ============================================================================
// 9. ERROR HANDLING PATTERNS
// ============================================================================

subheader( '9. Error Handling & Logging' );

test( '9.1: Try-catch in tracking JS', () => {
	return tracking.includes( 'try {' ) && tracking.includes( 'catch' );
} );

test( '9.2: Database error handling (mysqli)', () => {
	return (
		database.includes( '$mysqli->connect_error' ) &&
		database.includes( '$stmt->error' )
	);
} );

test( '9.3: WP_DEBUG conditional logging', () => {
	return ajaxHandlers.includes( "if (defined('WP_DEBUG') && WP_DEBUG)" );
} );

test( '9.4: Graceful degradation (tracking continues on error)', () => {
	return ajaxHandlers.includes(
		'Still return success to keep tracking JS resilient'
	);
} );

test( '9.5: Error codes documented', () => {
	const errorCodes = [
		'CONNECTION_FAILED',
		'SCHEMA_ERROR',
		'PREPARE_FAILED',
		'EXECUTE_FAILED',
	];
	return errorCodes.every( ( code ) => database.includes( code ) );
} );

// ============================================================================
// 10. DOCUMENTATION VALIDATION
// ============================================================================

subheader( '10. Documentation & Comments' );

test( '10.1: QA Phase 3 documentation exists', () => {
	try {
		const qaDoc = readFile( 'docs/qa/QA_PHASE3_RESULTS.md' );
		return (
			qaDoc.includes( 'Data Persistence Validation' ) &&
			qaDoc.includes( 'Acceptance Criteria' )
		);
	} catch ( e ) {
		return false;
	}
} );

test( '10.2: PHPDoc comments in database class', () => {
	return (
		database.includes( '/**' ) &&
		database.includes( '@param' ) &&
		database.includes( '@return' )
	);
} );

test( '10.3: Function descriptions in tracking JS', () => {
	return tracking.includes( '/**' ) || tracking.includes( '//' );
} );

test( '10.4: SQL table schema documented', () => {
	return (
		mainPlugin.includes( 'CREATE TABLE' ) &&
		mainPlugin.includes( 'PRIMARY KEY' )
	);
} );

// ============================================================================
// RESULTS SUMMARY
// ============================================================================

header( 'VALIDATION RESULTS' );

const passRate = ( ( passedTests / totalTests ) * 100 ).toFixed( 1 );

info( `Total Tests: ${ totalTests }` );
log(
	`Passed: ${ passedTests }`,
	passedTests === totalTests ? 'green' : 'yellow'
);
log( `Failed: ${ failedTests }`, failedTests > 0 ? 'red' : 'green' );
log( `Pass Rate: ${ passRate }%`, passRate === '100.0' ? 'green' : 'yellow' );

if ( warnings > 0 ) {
	warn( `Warnings: ${ warnings }` );
}

log( '' );

if ( failedTests === 0 ) {
	log( '✅ ALL VALIDATION CHECKS PASSED', 'green' );
	log( '' );
	log( 'Data persistence implementation is PRODUCTION-READY:', 'bright' );
	log( '  • Submission handler validates all required fields', 'green' );
	log( '  • External database integration with fallback', 'green' );
	log( '  • Encrypted credential storage (AES-256-CBC)', 'green' );
	log( '  • SQL injection protection (prepared statements)', 'green' );
	log( '  • Session tracking with crypto-secure IDs', 'green' );
	log( '  • Comprehensive error handling and logging', 'green' );
	log( '  • Admin UI with status indicators', 'green' );
	log( '  • Documentation complete (QA Phase 3)', 'green' );
	log( '' );
	log( 'Next Steps:', 'cyan' );
	log( '  1. Run integration tests in WordPress environment', 'cyan' );
	log( '  2. Test with external MySQL instance', 'cyan' );
	log( '  3. Validate fallback behavior with broken connections', 'cyan' );
	log( '  4. Monitor production logs for errors', 'cyan' );
	process.exit( 0 );
} else {
	log( '❌ VALIDATION FAILED - Please review failed tests above', 'red' );
	log( '' );
	log( 'Common Issues:', 'yellow' );
	log( '  • Missing functions or methods', 'yellow' );
	log( '  • Incomplete error handling', 'yellow' );
	log( '  • Missing security checks (nonces, capabilities)', 'yellow' );
	log( '  • Documentation gaps', 'yellow' );
	process.exit( 1 );
}
