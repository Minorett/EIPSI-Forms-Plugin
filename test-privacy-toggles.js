#!/usr/bin/env node
/**
 * Privacy Toggles Validation Test
 *
 * Verifies that Browser, OS, Screen Width, and IP Address
 * privacy toggles are correctly implemented
 */

const fs = require( 'fs' );
const path = require( 'path' );

const COLORS = {
	GREEN: '\x1b[32m',
	RED: '\x1b[31m',
	YELLOW: '\x1b[33m',
	BLUE: '\x1b[36m',
	RESET: '\x1b[0m',
	BOLD: '\x1b[1m',
};

let passed = 0;
let failed = 0;

function testSection( name ) {
	console.log(
		`\n${ COLORS.BOLD }${ COLORS.BLUE }━━━ ${ name } ━━━${ COLORS.RESET }\n`
	);
}

function test( description, condition, details = '' ) {
	if ( condition ) {
		console.log( `${ COLORS.GREEN }✓${ COLORS.RESET } ${ description }` );
		passed++;
	} else {
		console.log( `${ COLORS.RED }✗${ COLORS.RESET } ${ description }` );
		if ( details ) {
			console.log(
				`  ${ COLORS.YELLOW }→ ${ details }${ COLORS.RESET }`
			);
		}
		failed++;
	}
}

function readFile( filePath ) {
	try {
		return fs.readFileSync( path.join( __dirname, filePath ), 'utf8' );
	} catch ( err ) {
		return null;
	}
}

console.log(
	`${ COLORS.BOLD }${ COLORS.BLUE }╔═══════════════════════════════════════════════════════╗${ COLORS.RESET }`
);
console.log(
	`${ COLORS.BOLD }${ COLORS.BLUE }║   PRIVACY TOGGLES VALIDATION                          ║${ COLORS.RESET }`
);
console.log(
	`${ COLORS.BOLD }${ COLORS.BLUE }║   Browser/OS/Screen Width/IP Privacy Config           ║${ COLORS.RESET }`
);
console.log(
	`${ COLORS.BOLD }${ COLORS.BLUE }╚═══════════════════════════════════════════════════════╝${ COLORS.RESET }`
);

// ═══════════════════════════════════════════════════════════════════
// TEST 1: PRIVACY CONFIG DEFAULTS
// ═══════════════════════════════════════════════════════════════════
testSection( '1. Privacy Config Defaults' );

const privacyConfig = readFile( 'admin/privacy-config.php' );

test(
	'Browser is OFF by default',
	privacyConfig && privacyConfig.includes( "'browser' => false" ),
	'Expected: browser => false in get_privacy_defaults()'
);

test(
	'OS is OFF by default',
	privacyConfig && privacyConfig.includes( "'os' => false" ),
	'Expected: os => false in get_privacy_defaults()'
);

test(
	'Screen Width is OFF by default',
	privacyConfig && privacyConfig.includes( "'screen_width' => false" ),
	'Expected: screen_width => false in get_privacy_defaults()'
);

test(
	'IP Address is ON by default',
	privacyConfig && privacyConfig.includes( "'ip_address' => true" ),
	'Expected: ip_address => true in get_privacy_defaults()'
);

test(
	'Device Type is ON by default (existing behavior)',
	privacyConfig && privacyConfig.includes( "'device_type' => true" ),
	'Expected: device_type => true in get_privacy_defaults()'
);

// ═══════════════════════════════════════════════════════════════════
// TEST 2: ALLOWED TOGGLES
// ═══════════════════════════════════════════════════════════════════
testSection( '2. Allowed Toggles in save_privacy_config()' );

test(
	'Browser is in allowed_toggles array',
	privacyConfig &&
		privacyConfig.match( /\$allowed_toggles[\s\S]*?'browser'/ ),
	'Expected: browser in allowed_toggles'
);

test(
	'OS is in allowed_toggles array',
	privacyConfig && privacyConfig.match( /\$allowed_toggles[\s\S]*?'os'/ ),
	'Expected: os in allowed_toggles'
);

test(
	'Screen Width is in allowed_toggles array',
	privacyConfig &&
		privacyConfig.match( /\$allowed_toggles[\s\S]*?'screen_width'/ ),
	'Expected: screen_width in allowed_toggles'
);

test(
	'IP Address is in allowed_toggles array (now configurable)',
	privacyConfig &&
		privacyConfig.match( /\$allowed_toggles[\s\S]*?'ip_address'/ ),
	'Expected: ip_address in allowed_toggles'
);

test(
	'IP Address is NOT forced to true in get_privacy_config()',
	privacyConfig &&
		! privacyConfig.includes( "$config['ip_address'] = true;" ),
	'Expected: No forced ip_address = true in get_privacy_config()'
);

test(
	'IP Address is NOT forced to true in save_privacy_config()',
	privacyConfig &&
		! privacyConfig.includes( "$sanitized['ip_address'] = true;" ),
	'Expected: No forced ip_address = true in save_privacy_config()'
);

// ═══════════════════════════════════════════════════════════════════
// TEST 3: PRIVACY DASHBOARD UI
// ═══════════════════════════════════════════════════════════════════
testSection( '3. Privacy Dashboard UI' );

const privacyDashboard = readFile( 'admin/privacy-dashboard.php' );

test(
	'Browser toggle exists in UI',
	privacyDashboard && privacyDashboard.includes( 'name="browser"' ),
	'Expected: <input name="browser"> in privacy dashboard'
);

test(
	'OS toggle exists in UI',
	privacyDashboard && privacyDashboard.includes( 'name="os"' ),
	'Expected: <input name="os"> in privacy dashboard'
);

test(
	'Screen Width toggle exists in UI',
	privacyDashboard && privacyDashboard.includes( 'name="screen_width"' ),
	'Expected: <input name="screen_width"> in privacy dashboard'
);

test(
	'IP Address toggle exists in UI (no longer disabled)',
	privacyDashboard && privacyDashboard.includes( 'name="ip_address"' ),
	'Expected: <input name="ip_address"> in privacy dashboard'
);

test(
	'IP Address is NOT disabled/readonly',
	privacyDashboard &&
		! privacyDashboard.match( /name="ip_address"[^>]*disabled/ ),
	'Expected: IP Address checkbox not disabled'
);

test(
	'Browser defaults to unchecked (false)',
	privacyDashboard &&
		privacyDashboard.match(
			/name="browser".*checked\(\$privacy_config\['browser'\] \?\? false\)/
		),
	'Expected: Browser defaults to false'
);

test(
	'OS defaults to unchecked (false)',
	privacyDashboard &&
		privacyDashboard.match(
			/name="os".*checked\(\$privacy_config\['os'\] \?\? false\)/
		),
	'Expected: OS defaults to false'
);

test(
	'Screen Width defaults to unchecked (false)',
	privacyDashboard &&
		privacyDashboard.match(
			/name="screen_width".*checked\(\$privacy_config\['screen_width'\] \?\? false\)/
		),
	'Expected: Screen Width defaults to false'
);

test(
	'IP Address defaults to checked (true)',
	privacyDashboard &&
		privacyDashboard.match(
			/name="ip_address".*checked\(\$privacy_config\['ip_address'\] \?\? true\)/
		),
	'Expected: IP Address defaults to true'
);

test(
	'Device Info section exists with "Opcional" label',
	privacyDashboard &&
		privacyDashboard.includes( '🖥️ Información de Dispositivo' ) &&
		privacyDashboard.includes( 'eipsi-optional' ),
	'Expected: Device Info section with optional styling'
);

test(
	'Section description warning exists',
	privacyDashboard &&
		privacyDashboard.includes( 'eipsi-section-description' ) &&
		privacyDashboard.includes( 'desactivados por defecto' ),
	'Expected: Warning about defaults in section description'
);

test(
	'CSS for .eipsi-optional exists',
	privacyDashboard && privacyDashboard.includes( '.eipsi-optional' ),
	'Expected: CSS styling for optional label'
);

test(
	'CSS for .eipsi-section-description exists',
	privacyDashboard &&
		privacyDashboard.includes( '.eipsi-section-description' ),
	'Expected: CSS styling for section description'
);

test(
	'Updated info box exists',
	privacyDashboard &&
		privacyDashboard.includes( 'Por defecto ON - Auditoría clínica' ) &&
		privacyDashboard.includes( 'Por defecto OFF - Solo para debugging' ),
	'Expected: Updated info box with correct defaults'
);

// ═══════════════════════════════════════════════════════════════════
// TEST 4: AJAX HANDLERS PRIVACY LOGIC
// ═══════════════════════════════════════════════════════════════════
testSection( '4. AJAX Handlers Privacy Logic' );

const ajaxHandlers = readFile( 'admin/ajax-handlers.php' );

test(
	'Browser_raw is captured from POST',
	ajaxHandlers &&
		ajaxHandlers.includes( '$browser_raw' ) &&
		ajaxHandlers.includes( "$_POST['browser']" ),
	'Expected: $browser_raw captured from $_POST'
);

test(
	'OS_raw is captured from POST',
	ajaxHandlers &&
		ajaxHandlers.includes( '$os_raw' ) &&
		ajaxHandlers.includes( "$_POST['os']" ),
	'Expected: $os_raw captured from $_POST'
);

test(
	'Screen Width_raw is captured from POST',
	ajaxHandlers &&
		ajaxHandlers.includes( '$screen_width_raw' ) &&
		ajaxHandlers.includes( "$_POST['screen_width']" ),
	'Expected: $screen_width_raw captured from $_POST'
);

test(
	'IP Address_raw is captured from SERVER',
	ajaxHandlers &&
		ajaxHandlers.includes( '$ip_address_raw' ) &&
		ajaxHandlers.includes( "$_SERVER['REMOTE_ADDR']" ),
	'Expected: $ip_address_raw captured from $_SERVER'
);

test(
	'Browser respects privacy config',
	ajaxHandlers &&
		ajaxHandlers.match(
			/\$browser\s*=\s*\(\$privacy_config\['browser'\].*\?\s*\$browser_raw\s*:\s*null/
		),
	'Expected: $browser set to null if privacy config is false'
);

test(
	'OS respects privacy config',
	ajaxHandlers &&
		ajaxHandlers.match(
			/\$os\s*=\s*\(\$privacy_config\['os'\].*\?\s*\$os_raw\s*:\s*null/
		),
	'Expected: $os set to null if privacy config is false'
);

test(
	'Screen Width respects privacy config',
	ajaxHandlers &&
		ajaxHandlers.match(
			/\$screen_width\s*=\s*\(\$privacy_config\['screen_width'\].*\?\s*\$screen_width_raw\s*:\s*null/
		),
	'Expected: $screen_width set to null if privacy config is false'
);

test(
	'IP Address respects privacy config',
	ajaxHandlers &&
		ajaxHandlers.match(
			/\$ip_address\s*=\s*\(\$privacy_config\['ip_address'\].*\?\s*\$ip_address_raw\s*:\s*null/
		),
	'Expected: $ip_address set to null if privacy config is false'
);

test(
	'Browser is added to device_info metadata',
	ajaxHandlers &&
		ajaxHandlers.includes( 'if ($browser !== null)' ) &&
		ajaxHandlers.includes( "$device_info['browser']" ),
	'Expected: Browser conditionally added to metadata'
);

test(
	'OS is added to device_info metadata',
	ajaxHandlers &&
		ajaxHandlers.includes( 'if ($os !== null)' ) &&
		ajaxHandlers.includes( "$device_info['os']" ),
	'Expected: OS conditionally added to metadata'
);

test(
	'Screen Width is added to device_info metadata',
	ajaxHandlers &&
		ajaxHandlers.includes( 'if ($screen_width !== null)' ) &&
		ajaxHandlers.includes( "$device_info['screen_width']" ),
	'Expected: Screen Width conditionally added to metadata'
);

test(
	'IP Address is conditionally added to network_info',
	ajaxHandlers &&
		ajaxHandlers.includes( 'if ($ip_address !== null)' ) &&
		ajaxHandlers.includes( "$metadata['network_info']" ),
	'Expected: IP Address conditionally added to metadata'
);

// ═══════════════════════════════════════════════════════════════════
// TEST 5: DATABASE SCHEMA (ALREADY NULL-COMPATIBLE)
// ═══════════════════════════════════════════════════════════════════
testSection( '5. Database Schema NULL Support' );

const mainPlugin = readFile( 'vas-dinamico-forms.php' );

test(
	'Browser column allows NULL',
	mainPlugin && mainPlugin.includes( 'browser varchar(100) DEFAULT NULL' ),
	'Expected: browser varchar(100) DEFAULT NULL'
);

test(
	'OS column allows NULL',
	mainPlugin && mainPlugin.includes( 'os varchar(100) DEFAULT NULL' ),
	'Expected: os varchar(100) DEFAULT NULL'
);

test(
	'Screen Width column allows NULL',
	mainPlugin && mainPlugin.includes( 'screen_width int(11) DEFAULT NULL' ),
	'Expected: screen_width int(11) DEFAULT NULL'
);

test(
	'IP Address column allows NULL',
	mainPlugin && mainPlugin.includes( 'ip_address varchar(45) DEFAULT NULL' ),
	'Expected: ip_address varchar(45) DEFAULT NULL'
);

// ═══════════════════════════════════════════════════════════════════
// SUMMARY
// ═══════════════════════════════════════════════════════════════════
console.log(
	`\n${ COLORS.BOLD }${ COLORS.BLUE }━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${ COLORS.RESET }`
);
console.log( `${ COLORS.BOLD }SUMMARY${ COLORS.RESET }` );
console.log(
	`${ COLORS.BOLD }${ COLORS.BLUE }━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${ COLORS.RESET }\n`
);

const total = passed + failed;
const percentage = total > 0 ? Math.round( ( passed / total ) * 100 ) : 0;

if ( failed === 0 ) {
	console.log(
		`${ COLORS.GREEN }${ COLORS.BOLD }✓ ALL TESTS PASSED (${ passed }/${ total })${ COLORS.RESET }`
	);
	console.log(
		`${ COLORS.GREEN }${ COLORS.BOLD }✓ Privacy toggles implementation complete!${ COLORS.RESET }\n`
	);
	process.exit( 0 );
} else {
	console.log(
		`${ COLORS.RED }${ COLORS.BOLD }✗ SOME TESTS FAILED${ COLORS.RESET }`
	);
	console.log( `  ${ COLORS.GREEN }Passed: ${ passed }${ COLORS.RESET }` );
	console.log( `  ${ COLORS.RED }Failed: ${ failed }${ COLORS.RESET }` );
	console.log(
		`  ${ COLORS.YELLOW }Success Rate: ${ percentage }%${ COLORS.RESET }\n`
	);
	process.exit( 1 );
}
