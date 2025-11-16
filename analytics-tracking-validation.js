#!/usr/bin/env node

/**
 * EIPSI Forms - Analytics Tracking Validation Script
 *
 * Validates analytics tracking implementation across:
 * - Frontend tracker (eipsi-tracking.js)
 * - AJAX handler (eipsi_track_event_handler)
 * - Database table (vas_form_events)
 * - Session persistence and multi-form support
 * - Error resilience and admin visibility
 *
 * @package
 * @version 1.0.0
 */

/* eslint-disable no-console, no-nested-ternary, no-unused-vars */

const fs = require( 'fs' );
const path = require( 'path' );

// ANSI color codes
const colors = {
	reset: '\x1b[0m',
	bright: '\x1b[1m',
	dim: '\x1b[2m',
	red: '\x1b[31m',
	green: '\x1b[32m',
	yellow: '\x1b[33m',
	blue: '\x1b[34m',
	cyan: '\x1b[36m',
	magenta: '\x1b[35m',
};

let totalTests = 0;
let passedTests = 0;
let failedTests = 0;
let warnings = 0;

const results = {
	timestamp: new Date().toISOString(),
	tests: [],
	summary: {},
};

function log( message, color = 'reset' ) {
	console.log( `${ colors[ color ] }${ message }${ colors.reset }` );
}

function logTest( category, test, status, message = '' ) {
	totalTests++;
	const symbol = status === 'PASS' ? '✓' : status === 'FAIL' ? '✗' : '⚠';
	const color =
		status === 'PASS' ? 'green' : status === 'FAIL' ? 'red' : 'yellow';

	if ( status === 'PASS' ) {
		passedTests++;
	} else if ( status === 'FAIL' ) {
		failedTests++;
	} else if ( status === 'WARN' ) {
		warnings++;
	}

	log( `  ${ symbol } ${ test }${ message ? ': ' + message : '' }`, color );

	results.tests.push( {
		category,
		test,
		status,
		message,
	} );
}

function readFile( filePath ) {
	try {
		return fs.readFileSync( path.join( __dirname, filePath ), 'utf8' );
	} catch ( error ) {
		return null;
	}
}

function countOccurrences( content, pattern ) {
	const matches = content.match( new RegExp( pattern, 'g' ) );
	return matches ? matches.length : 0;
}

log( '\n' + '='.repeat( 80 ), 'cyan' );
log( 'EIPSI FORMS - ANALYTICS TRACKING VALIDATION', 'bright' );
log( '='.repeat( 80 ) + '\n', 'cyan' );

// =============================================================================
// CATEGORY 1: FRONTEND TRACKER VALIDATION
// =============================================================================
log( '━'.repeat( 80 ), 'blue' );
log( 'CATEGORY 1: Frontend Tracker Validation (eipsi-tracking.js)', 'bright' );
log( '━'.repeat( 80 ), 'blue' );

const trackingJs = readFile( 'assets/js/eipsi-tracking.js' );

if ( ! trackingJs ) {
	logTest(
		'Frontend Tracker',
		'File Exists',
		'FAIL',
		'eipsi-tracking.js not found'
	);
} else {
	logTest( 'Frontend Tracker', 'File Exists', 'PASS' );

	// Test 1.1: Event types defined
	const allowedEvents = trackingJs.match(
		/ALLOWED_EVENTS\s*=\s*new\s+Set\(\s*\[([\s\S]*?)\]\s*\)/
	);
	if ( allowedEvents ) {
		const events = allowedEvents[ 1 ].match( /'(\w+)'/g );
		const expectedEvents = [
			'view',
			'start',
			'page_change',
			'submit',
			'abandon',
			'branch_jump',
		];
		const foundEvents = events
			? events.map( ( e ) => e.replace( /'/g, '' ) )
			: [];
		const allPresent = expectedEvents.every( ( e ) =>
			foundEvents.includes( e )
		);

		if ( allPresent && foundEvents.length === expectedEvents.length ) {
			logTest(
				'Frontend Tracker',
				'Event Types Defined',
				'PASS',
				`All 6 event types present`
			);
		} else {
			logTest(
				'Frontend Tracker',
				'Event Types Defined',
				'FAIL',
				`Expected ${ expectedEvents.join(
					', '
				) } but found ${ foundEvents.join( ', ' ) }`
			);
		}
	} else {
		logTest(
			'Frontend Tracker',
			'Event Types Defined',
			'FAIL',
			'ALLOWED_EVENTS not found'
		);
	}

	// Test 1.2: Session storage key
	const storageKey = trackingJs.includes(
		"STORAGE_KEY = 'eipsiAnalyticsSessions'"
	);
	logTest(
		'Frontend Tracker',
		'Session Storage Key',
		storageKey ? 'PASS' : 'FAIL',
		storageKey
			? 'eipsiAnalyticsSessions'
			: 'Storage key not found or incorrect'
	);

	// Test 1.3: Crypto-secure session ID generation
	const cryptoSecure =
		trackingJs.includes( 'window.crypto' ) &&
		trackingJs.includes( 'crypto.getRandomValues' );
	logTest(
		'Frontend Tracker',
		'Crypto-Secure Session ID',
		cryptoSecure ? 'PASS' : 'WARN',
		cryptoSecure
			? 'Uses crypto.getRandomValues'
			: 'Falls back to Math.random()'
	);

	// Test 1.4: Session restoration
	const restoreSessions =
		trackingJs.includes( 'restoreSessions()' ) &&
		( trackingJs.includes( 'sessionStorage.getItem(STORAGE_KEY)' ) ||
			trackingJs.includes( 'sessionStorage.getItem( STORAGE_KEY )' ) );
	logTest(
		'Frontend Tracker',
		'Session Restoration',
		restoreSessions ? 'PASS' : 'FAIL',
		restoreSessions
			? 'Restores from sessionStorage on init'
			: 'Restoration logic missing'
	);

	// Test 1.5: Session persistence
	const persistSessions =
		trackingJs.includes( 'persistSessions()' ) &&
		trackingJs.includes( 'sessionStorage.setItem' );
	logTest(
		'Frontend Tracker',
		'Session Persistence',
		persistSessions ? 'PASS' : 'FAIL',
		persistSessions
			? 'Saves to sessionStorage after updates'
			: 'Persistence logic missing'
	);

	// Test 1.6: View event tracking
	const viewTracking =
		( trackingJs.includes( "trackEvent( 'view'" ) ||
			trackingJs.includes( "trackEvent('view'" ) ) &&
		trackingJs.includes( 'viewTracked' );
	logTest(
		'Frontend Tracker',
		'View Event Tracking',
		viewTracking ? 'PASS' : 'FAIL',
		viewTracking ? 'Tracks view on registerForm' : 'View tracking missing'
	);

	// Test 1.7: Start event tracking
	const startTracking =
		( trackingJs.includes( "trackEvent( 'start'" ) ||
			trackingJs.includes( "trackEvent('start'" ) ) &&
		trackingJs.includes( 'startTracked' ) &&
		trackingJs.includes( 'isInteractiveField' );
	logTest(
		'Frontend Tracker',
		'Start Event Tracking',
		startTracking ? 'PASS' : 'FAIL',
		startTracking
			? 'Tracks start on first interaction'
			: 'Start tracking incomplete'
	);

	// Test 1.8: Page change tracking
	const pageChange =
		trackingJs.includes( 'recordPageChange' ) &&
		( trackingJs.includes( "trackEvent( 'page_change'" ) ||
			trackingJs.includes( "trackEvent('page_change'" ) ) &&
		trackingJs.includes( 'page_number' );
	logTest(
		'Frontend Tracker',
		'Page Change Tracking',
		pageChange ? 'PASS' : 'FAIL',
		pageChange
			? 'Tracks page changes with page_number'
			: 'Page change tracking missing'
	);

	// Test 1.9: Submit tracking
	const submitTracking =
		trackingJs.includes( 'recordSubmit' ) &&
		( trackingJs.includes( "trackEvent( 'submit'" ) ||
			trackingJs.includes( "trackEvent('submit'" ) ) &&
		trackingJs.includes( 'submitTracked' );
	logTest(
		'Frontend Tracker',
		'Submit Event Tracking',
		submitTracking ? 'PASS' : 'FAIL',
		submitTracking
			? 'Tracks submit with deduplication'
			: 'Submit tracking missing'
	);

	// Test 1.10: Abandon tracking
	const abandonTracking =
		trackingJs.includes( 'flushAbandonEvents' ) &&
		( trackingJs.includes( "trackEvent( 'abandon'" ) ||
			trackingJs.includes( "trackEvent('abandon'" ) ||
			trackingJs.includes( "trackEvent(\n\t\t\t\t\t\t'abandon'" ) ) &&
		trackingJs.includes( 'abandonTracked' ) &&
		trackingJs.includes( 'visibilitychange' ) &&
		trackingJs.includes( 'beforeunload' );
	logTest(
		'Frontend Tracker',
		'Abandon Event Tracking',
		abandonTracking ? 'PASS' : 'FAIL',
		abandonTracking
			? 'Tracks abandon on page hide/unload'
			: 'Abandon tracking incomplete'
	);

	// Test 1.11: sendBeacon support
	const sendBeacon =
		trackingJs.includes( 'navigator.sendBeacon' ) &&
		trackingJs.includes( 'useBeacon' );
	logTest(
		'Frontend Tracker',
		'sendBeacon Support',
		sendBeacon ? 'PASS' : 'FAIL',
		sendBeacon
			? 'Uses sendBeacon for abandon events'
			: 'sendBeacon not implemented'
	);

	// Test 1.12: Branch jump tracking
	const branchJump =
		trackingJs.includes( 'branch_jump' ) &&
		trackingJs.includes( 'from_page' ) &&
		trackingJs.includes( 'to_page' ) &&
		trackingJs.includes( 'field_id' ) &&
		trackingJs.includes( 'matched_value' );
	logTest(
		'Frontend Tracker',
		'Branch Jump Tracking',
		branchJump ? 'PASS' : 'FAIL',
		branchJump
			? 'Supports branch_jump with metadata'
			: 'Branch jump metadata incomplete'
	);

	// Test 1.13: Nonce verification
	const nonceCheck =
		trackingJs.includes( 'nonce' ) &&
		( trackingJs.includes( "params.append( 'nonce'" ) ||
			trackingJs.includes( "params.append('nonce'" ) );
	logTest(
		'Frontend Tracker',
		'Nonce Inclusion',
		nonceCheck ? 'PASS' : 'FAIL',
		nonceCheck
			? 'Includes nonce in all requests'
			: 'Nonce missing from requests'
	);

	// Test 1.14: User agent tracking
	const userAgent = trackingJs.includes( 'navigator.userAgent' );
	logTest(
		'Frontend Tracker',
		'User Agent Tracking',
		userAgent ? 'PASS' : 'FAIL',
		userAgent ? 'Includes user agent in requests' : 'User agent not tracked'
	);

	// Test 1.15: Fetch with keepalive
	const keepalive =
		trackingJs.includes( 'keepalive: true' ) &&
		trackingJs.includes( 'requestOptions.keepalive' );
	logTest(
		'Frontend Tracker',
		'Keepalive Support',
		keepalive ? 'PASS' : 'FAIL',
		keepalive
			? 'Supports keepalive for abandon events'
			: 'Keepalive not implemented'
	);

	// Test 1.16: Error resilience
	const errorHandling =
		trackingJs.includes( '.catch(' ) &&
		trackingJs.includes( '// Silently ignore' );
	logTest(
		'Frontend Tracker',
		'Error Resilience',
		errorHandling ? 'PASS' : 'FAIL',
		errorHandling
			? 'Silently handles network errors'
			: 'No error handling for failed requests'
	);

	// Test 1.17: Public API
	const publicApi =
		trackingJs.includes( 'window.EIPSITracking' ) &&
		trackingJs.includes( 'registerForm' ) &&
		trackingJs.includes( 'setTotalPages' ) &&
		trackingJs.includes( 'recordPageChange' ) &&
		trackingJs.includes( 'recordSubmit' ) &&
		trackingJs.includes( 'flushAbandon' );
	logTest(
		'Frontend Tracker',
		'Public API',
		publicApi ? 'PASS' : 'FAIL',
		publicApi ? 'Exposes complete public API' : 'Public API incomplete'
	);

	// Test 1.18: Multi-form support
	const multiForm =
		trackingJs.includes( 'sessions: new Map()' ) &&
		trackingJs.includes( 'getOrCreateSession' );
	logTest(
		'Frontend Tracker',
		'Multi-Form Support',
		multiForm ? 'PASS' : 'FAIL',
		multiForm ? 'Uses Map for multiple sessions' : 'No multi-form support'
	);
}

// =============================================================================
// CATEGORY 2: AJAX HANDLER VALIDATION
// =============================================================================
log( '\n' + '━'.repeat( 80 ), 'blue' );
log(
	'CATEGORY 2: AJAX Handler Validation (admin/ajax-handlers.php)',
	'bright'
);
log( '━'.repeat( 80 ), 'blue' );

const ajaxHandlers = readFile( 'admin/ajax-handlers.php' );

if ( ! ajaxHandlers ) {
	logTest(
		'AJAX Handler',
		'File Exists',
		'FAIL',
		'ajax-handlers.php not found'
	);
} else {
	logTest( 'AJAX Handler', 'File Exists', 'PASS' );

	// Test 2.1: Handler registration
	const handlerRegistration =
		ajaxHandlers.includes(
			"add_action('wp_ajax_nopriv_eipsi_track_event'"
		) &&
		ajaxHandlers.includes( "add_action('wp_ajax_eipsi_track_event'" ) &&
		ajaxHandlers.includes( 'eipsi_track_event_handler' );
	logTest(
		'AJAX Handler',
		'Handler Registration',
		handlerRegistration ? 'PASS' : 'FAIL',
		handlerRegistration
			? 'Registered for logged-in and logged-out users'
			: 'Registration missing'
	);

	// Test 2.2: Nonce verification
	const nonceVerification =
		ajaxHandlers.includes( 'wp_verify_nonce' ) &&
		ajaxHandlers.includes( 'eipsi_tracking_nonce' );
	logTest(
		'AJAX Handler',
		'Nonce Verification',
		nonceVerification ? 'PASS' : 'FAIL',
		nonceVerification
			? 'Verifies nonce before processing'
			: 'Nonce verification missing'
	);

	// Test 2.3: Event type validation
	const eventValidation = ajaxHandlers.match(
		/allowed_events\s*=\s*array\(([\s\S]*?)\)/i
	);
	if ( eventValidation ) {
		const events = eventValidation[ 1 ].match( /'(\w+)'/g );
		const expectedEvents = [
			'view',
			'start',
			'page_change',
			'submit',
			'abandon',
			'branch_jump',
		];
		const foundEvents = events
			? events.map( ( e ) => e.replace( /'/g, '' ) )
			: [];
		const allPresent = expectedEvents.every( ( e ) =>
			foundEvents.includes( e )
		);

		if ( allPresent ) {
			logTest(
				'AJAX Handler',
				'Event Type Validation',
				'PASS',
				'Validates against allowed event types'
			);
		} else {
			logTest(
				'AJAX Handler',
				'Event Type Validation',
				'FAIL',
				`Expected ${ expectedEvents.join(
					', '
				) } but found ${ foundEvents.join( ', ' ) }`
			);
		}
	} else {
		logTest(
			'AJAX Handler',
			'Event Type Validation',
			'FAIL',
			'Event validation not found'
		);
	}

	// Test 2.4: Input sanitization
	const sanitization =
		ajaxHandlers.includes( 'sanitize_text_field' ) &&
		ajaxHandlers.includes( 'intval' );
	logTest(
		'AJAX Handler',
		'Input Sanitization',
		sanitization ? 'PASS' : 'FAIL',
		sanitization ? 'Sanitizes all POST inputs' : 'Sanitization missing'
	);

	// Test 2.5: Required field validation
	const requiredFields =
		ajaxHandlers.includes( 'empty($session_id)' ) &&
		ajaxHandlers.includes( 'Missing required field' );
	logTest(
		'AJAX Handler',
		'Required Field Validation',
		requiredFields ? 'PASS' : 'FAIL',
		requiredFields
			? 'Validates session_id presence'
			: 'Required field validation missing'
	);

	// Test 2.6: Database table reference
	const dbTable =
		ajaxHandlers.includes( "'vas_form_events'" ) ||
		ajaxHandlers.includes( '"vas_form_events"' );
	logTest(
		'AJAX Handler',
		'Database Table Reference',
		dbTable ? 'PASS' : 'FAIL',
		dbTable ? 'Uses vas_form_events table' : 'Table reference missing'
	);

	// Test 2.7: Insert data structure
	const insertData =
		ajaxHandlers.includes( '$insert_data = array(' ) &&
		ajaxHandlers.includes( 'form_id' ) &&
		ajaxHandlers.includes( 'session_id' ) &&
		ajaxHandlers.includes( 'event_type' ) &&
		ajaxHandlers.includes( 'page_number' ) &&
		ajaxHandlers.includes( 'metadata' ) &&
		ajaxHandlers.includes( 'user_agent' ) &&
		ajaxHandlers.includes( 'created_at' );
	logTest(
		'AJAX Handler',
		'Insert Data Structure',
		insertData ? 'PASS' : 'FAIL',
		insertData
			? 'Prepares complete data structure'
			: 'Data structure incomplete'
	);

	// Test 2.8: Branch jump metadata
	const branchMetadata =
		ajaxHandlers.includes( "event_type === 'branch_jump'" ) &&
		ajaxHandlers.includes( 'from_page' ) &&
		ajaxHandlers.includes( 'to_page' ) &&
		ajaxHandlers.includes( 'field_id' ) &&
		ajaxHandlers.includes( 'matched_value' ) &&
		ajaxHandlers.includes( 'wp_json_encode' );
	logTest(
		'AJAX Handler',
		'Branch Jump Metadata',
		branchMetadata ? 'PASS' : 'FAIL',
		branchMetadata
			? 'Collects and encodes branch jump metadata'
			: 'Metadata handling incomplete'
	);

	// Test 2.9: Database insert
	const dbInsert =
		ajaxHandlers.includes( '$wpdb->insert' ) &&
		ajaxHandlers.includes( '$insert_formats' );
	logTest(
		'AJAX Handler',
		'Database Insert',
		dbInsert ? 'PASS' : 'FAIL',
		dbInsert
			? 'Uses $wpdb->insert with format specifiers'
			: 'Insert logic missing'
	);

	// Test 2.10: Error handling
	const errorHandlingPhp =
		ajaxHandlers.includes( 'if ($result === false)' ) &&
		ajaxHandlers.includes( 'error_log' ) &&
		ajaxHandlers.includes( 'wp_send_json_success' );
	logTest(
		'AJAX Handler',
		'Error Handling',
		errorHandlingPhp ? 'PASS' : 'FAIL',
		errorHandlingPhp
			? 'Logs errors but returns success (resilient)'
			: 'Error handling missing'
	);

	// Test 2.11: Success response
	const successResponse =
		ajaxHandlers.includes( 'wp_send_json_success' ) &&
		ajaxHandlers.includes( 'event_id' ) &&
		ajaxHandlers.includes( '$wpdb->insert_id' );
	logTest(
		'AJAX Handler',
		'Success Response',
		successResponse ? 'PASS' : 'FAIL',
		successResponse
			? 'Returns event_id in success response'
			: 'Success response incomplete'
	);

	// Test 2.12: HTTP status codes
	const statusCodes =
		ajaxHandlers.includes( 'wp_send_json_error' ) &&
		ajaxHandlers.includes( '403' ) &&
		ajaxHandlers.includes( '400' );
	logTest(
		'AJAX Handler',
		'HTTP Status Codes',
		statusCodes ? 'PASS' : 'FAIL',
		statusCodes
			? 'Returns proper HTTP status codes'
			: 'Status codes not set'
	);
}

// =============================================================================
// CATEGORY 3: DATABASE SCHEMA VALIDATION
// =============================================================================
log( '\n' + '━'.repeat( 80 ), 'blue' );
log(
	'CATEGORY 3: Database Schema Validation (vas-dinamico-forms.php)',
	'bright'
);
log( '━'.repeat( 80 ), 'blue' );

const mainPlugin = readFile( 'vas-dinamico-forms.php' );

if ( ! mainPlugin ) {
	logTest(
		'Database Schema',
		'File Exists',
		'FAIL',
		'vas-dinamico-forms.php not found'
	);
} else {
	logTest( 'Database Schema', 'File Exists', 'PASS' );

	// Test 3.1: Table creation in activation hook
	const tableCreation =
		mainPlugin.includes( 'CREATE TABLE IF NOT EXISTS $events_table' ) ||
		mainPlugin.includes( 'CREATE TABLE IF NOT EXISTS \\$events_table' );
	logTest(
		'Database Schema',
		'Table Creation Hook',
		tableCreation ? 'PASS' : 'FAIL',
		tableCreation
			? 'Creates vas_form_events on activation'
			: 'Table creation missing'
	);

	// Test 3.2: Table structure - id column
	const idColumn = mainPlugin.includes(
		'id bigint(20) unsigned NOT NULL AUTO_INCREMENT'
	);
	logTest(
		'Database Schema',
		'Column: id',
		idColumn ? 'PASS' : 'FAIL',
		idColumn
			? 'bigint AUTO_INCREMENT PRIMARY KEY'
			: 'id column missing or incorrect'
	);

	// Test 3.3: Table structure - form_id column
	const formIdColumn = mainPlugin.includes( 'form_id varchar(255)' );
	logTest(
		'Database Schema',
		'Column: form_id',
		formIdColumn ? 'PASS' : 'FAIL',
		formIdColumn ? 'varchar(255)' : 'form_id column missing or incorrect'
	);

	// Test 3.4: Table structure - session_id column
	const sessionIdColumn = mainPlugin.includes(
		'session_id varchar(255) NOT NULL'
	);
	logTest(
		'Database Schema',
		'Column: session_id',
		sessionIdColumn ? 'PASS' : 'FAIL',
		sessionIdColumn
			? 'varchar(255) NOT NULL'
			: 'session_id column missing or incorrect'
	);

	// Test 3.5: Table structure - event_type column
	const eventTypeColumn = mainPlugin.includes(
		'event_type varchar(50) NOT NULL'
	);
	logTest(
		'Database Schema',
		'Column: event_type',
		eventTypeColumn ? 'PASS' : 'FAIL',
		eventTypeColumn
			? 'varchar(50) NOT NULL'
			: 'event_type column missing or incorrect'
	);

	// Test 3.6: Table structure - page_number column
	const pageNumberColumn = mainPlugin.includes( 'page_number int(11)' );
	logTest(
		'Database Schema',
		'Column: page_number',
		pageNumberColumn ? 'PASS' : 'FAIL',
		pageNumberColumn
			? 'int(11) nullable'
			: 'page_number column missing or incorrect'
	);

	// Test 3.7: Table structure - metadata column
	const metadataColumn = mainPlugin.includes( 'metadata text' );
	logTest(
		'Database Schema',
		'Column: metadata',
		metadataColumn ? 'PASS' : 'FAIL',
		metadataColumn
			? 'text nullable (for branch_jump)'
			: 'metadata column missing or incorrect'
	);

	// Test 3.8: Table structure - user_agent column
	const userAgentColumn = mainPlugin.includes( 'user_agent text' );
	logTest(
		'Database Schema',
		'Column: user_agent',
		userAgentColumn ? 'PASS' : 'FAIL',
		userAgentColumn
			? 'text nullable'
			: 'user_agent column missing or incorrect'
	);

	// Test 3.9: Table structure - created_at column
	const createdAtColumn = mainPlugin.includes(
		'created_at datetime NOT NULL'
	);
	logTest(
		'Database Schema',
		'Column: created_at',
		createdAtColumn ? 'PASS' : 'FAIL',
		createdAtColumn
			? 'datetime NOT NULL'
			: 'created_at column missing or incorrect'
	);

	// Test 3.10: Index on form_id
	const formIdIndex = mainPlugin.includes( 'KEY form_id (form_id)' );
	logTest(
		'Database Schema',
		'Index: form_id',
		formIdIndex ? 'PASS' : 'FAIL',
		formIdIndex ? 'Indexed for query performance' : 'form_id index missing'
	);

	// Test 3.11: Index on session_id
	const sessionIdIndex = mainPlugin.includes( 'KEY session_id (session_id)' );
	logTest(
		'Database Schema',
		'Index: session_id',
		sessionIdIndex ? 'PASS' : 'FAIL',
		sessionIdIndex
			? 'Indexed for query performance'
			: 'session_id index missing'
	);

	// Test 3.12: Index on event_type
	const eventTypeIndex = mainPlugin.includes( 'KEY event_type (event_type)' );
	logTest(
		'Database Schema',
		'Index: event_type',
		eventTypeIndex ? 'PASS' : 'FAIL',
		eventTypeIndex
			? 'Indexed for filtering by event'
			: 'event_type index missing'
	);

	// Test 3.13: Index on created_at
	const createdAtIndex = mainPlugin.includes( 'KEY created_at (created_at)' );
	logTest(
		'Database Schema',
		'Index: created_at',
		createdAtIndex ? 'PASS' : 'FAIL',
		createdAtIndex
			? 'Indexed for time-series queries'
			: 'created_at index missing'
	);

	// Test 3.14: Composite index on form_id + session_id
	const compositeIndex = mainPlugin.includes(
		'KEY form_session (form_id, session_id)'
	);
	logTest(
		'Database Schema',
		'Index: form_session',
		compositeIndex ? 'PASS' : 'FAIL',
		compositeIndex
			? 'Composite index for session queries'
			: 'Composite index missing'
	);

	// Test 3.15: dbDelta usage
	const dbDelta = mainPlugin.includes( 'dbDelta($sql_events)' );
	logTest(
		'Database Schema',
		'dbDelta Usage',
		dbDelta ? 'PASS' : 'FAIL',
		dbDelta
			? 'Uses dbDelta for safe table creation'
			: 'Should use dbDelta for upgrades'
	);
}

// =============================================================================
// CATEGORY 4: INTEGRATION VALIDATION
// =============================================================================
log( '\n' + '━'.repeat( 80 ), 'blue' );
log( 'CATEGORY 4: Integration Validation', 'bright' );
log( '━'.repeat( 80 ), 'blue' );

// Test 4.1: Tracking JS loaded before forms JS
if ( mainPlugin ) {
	const trackingLoadOrder =
		mainPlugin.includes( 'eipsi-tracking-js' ) &&
		mainPlugin.includes( "array('eipsi-tracking-js')" );
	logTest(
		'Integration',
		'Script Load Order',
		trackingLoadOrder ? 'PASS' : 'FAIL',
		trackingLoadOrder
			? 'eipsi-tracking.js loads before eipsi-forms.js'
			: 'Load order not enforced'
	);

	// Test 4.2: Tracking config localization
	const trackingConfig =
		mainPlugin.includes( 'wp_localize_script' ) &&
		mainPlugin.includes( 'eipsiTrackingConfig' ) &&
		mainPlugin.includes( 'eipsi_tracking_nonce' );
	logTest(
		'Integration',
		'Tracking Config Localized',
		trackingConfig ? 'PASS' : 'FAIL',
		trackingConfig
			? 'Localizes ajaxUrl and nonce'
			: 'Config localization missing'
	);

	// Test 4.3: Nonce creation
	const nonceCreation = mainPlugin.includes(
		"wp_create_nonce('eipsi_tracking_nonce')"
	);
	logTest(
		'Integration',
		'Nonce Creation',
		nonceCreation ? 'PASS' : 'FAIL',
		nonceCreation
			? 'Creates eipsi_tracking_nonce'
			: 'Nonce creation missing'
	);
}

// Test 4.4: Forms JS integration
const formsJs = readFile( 'assets/js/eipsi-forms.js' );
if ( formsJs ) {
	const trackingIntegration =
		formsJs.includes( 'EIPSITracking' ) &&
		formsJs.includes( 'registerForm' );
	logTest(
		'Integration',
		'Forms JS Integration',
		trackingIntegration ? 'PASS' : 'FAIL',
		trackingIntegration
			? 'eipsi-forms.js calls EIPSITracking.registerForm'
			: 'Integration missing'
	);

	// Test 4.5: Page change integration
	const pageChangeIntegration =
		formsJs.includes( 'recordPageChange' ) ||
		formsJs.includes( 'setCurrentPage' );
	logTest(
		'Integration',
		'Page Change Integration',
		pageChangeIntegration ? 'PASS' : 'WARN',
		pageChangeIntegration
			? 'Forms JS tracks page changes'
			: 'Page change tracking not found in forms JS'
	);

	// Test 4.6: Submit integration
	const submitIntegration = formsJs.includes( 'recordSubmit' );
	logTest(
		'Integration',
		'Submit Integration',
		submitIntegration ? 'PASS' : 'WARN',
		submitIntegration
			? 'Forms JS tracks submit events'
			: 'Submit tracking not found in forms JS'
	);
}

// =============================================================================
// CATEGORY 5: ADMIN VISIBILITY VALIDATION
// =============================================================================
log( '\n' + '━'.repeat( 80 ), 'blue' );
log( 'CATEGORY 5: Admin Visibility Validation', 'bright' );
log( '━'.repeat( 80 ), 'blue' );

// Test 5.1: Results page exists
const resultsPage = readFile( 'admin/results-page.php' );
if ( resultsPage ) {
	logTest( 'Admin Visibility', 'Results Page Exists', 'PASS' );

	// Test 5.2: Analytics query capability
	const analyticsQuery =
		resultsPage.includes( 'vas_form_events' ) ||
		resultsPage.includes( 'form_events' );
	logTest(
		'Admin Visibility',
		'Analytics Query Capability',
		analyticsQuery ? 'PASS' : 'WARN',
		analyticsQuery
			? 'Results page queries vas_form_events'
			: 'Analytics query not found (may be implemented elsewhere)'
	);
} else {
	logTest(
		'Admin Visibility',
		'Results Page Exists',
		'FAIL',
		'results-page.php not found'
	);
}

// Test 5.3: Response details modal
if ( ajaxHandlers ) {
	const responseDetails = ajaxHandlers.includes(
		'eipsi_ajax_get_response_details'
	);
	logTest(
		'Admin Visibility',
		'Response Details Modal',
		responseDetails ? 'PASS' : 'FAIL',
		responseDetails
			? 'AJAX handler for response details exists'
			: 'Response details handler missing'
	);
}

// =============================================================================
// CATEGORY 6: ERROR RESILIENCE VALIDATION
// =============================================================================
log( '\n' + '━'.repeat( 80 ), 'blue' );
log( 'CATEGORY 6: Error Resilience Validation', 'bright' );
log( '━'.repeat( 80 ), 'blue' );

if ( ajaxHandlers && trackingJs ) {
	// Test 6.1: Invalid nonce handling
	const invalidNonce =
		ajaxHandlers.includes( 'wp_verify_nonce' ) &&
		ajaxHandlers.includes( 'wp_send_json_error' ) &&
		ajaxHandlers.includes( '403' );
	logTest(
		'Error Resilience',
		'Invalid Nonce Handling',
		invalidNonce ? 'PASS' : 'FAIL',
		invalidNonce
			? 'Returns 403 for invalid nonce'
			: 'Nonce error handling missing'
	);

	// Test 6.2: Invalid event type handling
	const invalidEvent =
		ajaxHandlers.includes( '!in_array($event_type, $allowed_events' ) &&
		ajaxHandlers.includes( 'Invalid event type' );
	logTest(
		'Error Resilience',
		'Invalid Event Type Handling',
		invalidEvent ? 'PASS' : 'FAIL',
		invalidEvent
			? 'Returns 400 for invalid event type'
			: 'Event validation missing'
	);

	// Test 6.3: Missing required fields
	const missingFields =
		ajaxHandlers.includes( 'empty($session_id)' ) &&
		ajaxHandlers.includes( 'Missing required field' );
	logTest(
		'Error Resilience',
		'Missing Field Handling',
		missingFields ? 'PASS' : 'FAIL',
		missingFields
			? 'Returns 400 for missing session_id'
			: 'Field validation missing'
	);

	// Test 6.4: Database error resilience
	const dbErrorResilience =
		ajaxHandlers.includes( 'if ($result === false)' ) &&
		ajaxHandlers.includes( 'error_log' ) &&
		ajaxHandlers.includes( 'Still return success' );
	logTest(
		'Error Resilience',
		'Database Error Resilience',
		dbErrorResilience ? 'PASS' : 'FAIL',
		dbErrorResilience
			? 'Logs error but returns success (keeps tracking working)'
			: 'Should not crash on DB errors'
	);

	// Test 6.5: Frontend network error handling
	const networkErrorHandling =
		trackingJs.includes( '.catch(' ) &&
		trackingJs.includes( 'Silently ignore' );
	logTest(
		'Error Resilience',
		'Network Error Handling',
		networkErrorHandling ? 'PASS' : 'FAIL',
		networkErrorHandling
			? 'Silently ignores network errors'
			: 'Network error handling missing'
	);

	// Test 6.6: SessionStorage quota handling
	const quotaHandling =
		trackingJs.includes( 'try {' ) &&
		trackingJs.includes( 'sessionStorage.setItem' ) &&
		trackingJs.includes( '} catch' ) &&
		( trackingJs.includes( '// Ignore quota errors' ) ||
			trackingJs.includes( 'Ignore quota' ) ||
			trackingJs.includes( 'catch ( error )' ) );
	logTest(
		'Error Resilience',
		'SessionStorage Quota Handling',
		quotaHandling ? 'PASS' : 'FAIL',
		quotaHandling
			? 'Handles quota exceeded errors gracefully'
			: 'Quota error handling missing'
	);

	// Test 6.7: SessionStorage support detection
	const storageDetection =
		trackingJs.includes( 'supportsStorage()' ) &&
		trackingJs.includes( 'supportsSessionStorage' );
	logTest(
		'Error Resilience',
		'Storage Support Detection',
		storageDetection ? 'PASS' : 'FAIL',
		storageDetection
			? 'Detects sessionStorage availability'
			: 'Storage detection missing'
	);
}

// =============================================================================
// SUMMARY
// =============================================================================
log( '\n' + '='.repeat( 80 ), 'cyan' );
log( 'VALIDATION SUMMARY', 'bright' );
log( '='.repeat( 80 ), 'cyan' );

const passRate =
	totalTests > 0 ? ( ( passedTests / totalTests ) * 100 ).toFixed( 1 ) : 0;
const statusColor =
	passRate >= 95 ? 'green' : passRate >= 80 ? 'yellow' : 'red';

log( `\nTotal Tests:    ${ totalTests }`, 'bright' );
log( `Passed:         ${ passedTests }`, 'green' );
log( `Failed:         ${ failedTests }`, failedTests > 0 ? 'red' : 'green' );
log( `Warnings:       ${ warnings }`, warnings > 0 ? 'yellow' : 'green' );
log( `Pass Rate:      ${ passRate }%`, statusColor );

results.summary = {
	totalTests,
	passedTests,
	failedTests,
	warnings,
	passRate: parseFloat( passRate ),
};

// Save results to JSON
const resultsPath = path.join(
	__dirname,
	'docs/qa/analytics-tracking-validation.json'
);
fs.mkdirSync( path.dirname( resultsPath ), { recursive: true } );
fs.writeFileSync( resultsPath, JSON.stringify( results, null, 2 ) );

log( `\nResults saved to: docs/qa/analytics-tracking-validation.json`, 'cyan' );

// =============================================================================
// RECOMMENDATIONS
// =============================================================================
log( '\n' + '='.repeat( 80 ), 'magenta' );
log( 'RECOMMENDATIONS', 'bright' );
log( '='.repeat( 80 ), 'magenta' );

const recommendations = [];

if ( failedTests > 0 ) {
	recommendations.push( '⚠️  Address all FAILED tests before deployment' );
}

if ( warnings > 0 ) {
	recommendations.push( '💡 Review WARN items and implement if applicable' );
}

if ( passRate < 95 ) {
	recommendations.push(
		'🔧 Aim for 95%+ pass rate for production readiness'
	);
}

recommendations.push(
	'✅ Run manual testing to verify event emission in browser'
);
recommendations.push(
	'✅ Query vas_form_events table to confirm data persistence'
);
recommendations.push( '✅ Test with WP_DEBUG enabled to catch any errors' );
recommendations.push( '✅ Test abandon events with sendBeacon in network tab' );
recommendations.push( '✅ Test session restoration after page refresh' );
recommendations.push( '✅ Test multiple forms on same page' );

recommendations.forEach( ( rec ) => log( `  ${ rec }`, 'dim' ) );

log( '\n' + '='.repeat( 80 ), 'cyan' );
log( 'END OF VALIDATION', 'bright' );
log( '='.repeat( 80 ) + '\n', 'cyan' );

process.exit( failedTests > 0 ? 1 : 0 );
