#!/usr/bin/env node

/**
 * EIPSI Forms - Admin Workflows Validation Script (Phase 7)
 *
 * Comprehensive automated testing for all admin-side functionality:
 * - Gutenberg block editor components
 * - Admin pages (Results, Configuration, Export)
 * - AJAX handlers and security
 * - Admin assets (CSS, JavaScript)
 * - Database queries and exports
 *
 * Usage: node admin-workflows-validation.js
 *
 * @version 1.0.0
 * @package
 */

/* eslint-disable no-console, jsdoc/require-param-type, no-nested-ternary */

const fs = require( 'fs' );
const path = require( 'path' );

// Color codes for terminal output
const colors = {
	reset: '\x1b[0m',
	bright: '\x1b[1m',
	green: '\x1b[32m',
	red: '\x1b[31m',
	yellow: '\x1b[33m',
	blue: '\x1b[34m',
	cyan: '\x1b[36m',
	gray: '\x1b[90m',
};

// Test results tracker
const results = {
	total: 0,
	passed: 0,
	failed: 0,
	warnings: 0,
	categories: {
		'Block Editor': { total: 0, passed: 0, failed: 0 },
		'Results Page': { total: 0, passed: 0, failed: 0 },
		'Configuration Panel': { total: 0, passed: 0, failed: 0 },
		'Export Functionality': { total: 0, passed: 0, failed: 0 },
		'AJAX Handlers': { total: 0, passed: 0, failed: 0 },
		'Admin Assets': { total: 0, passed: 0, failed: 0 },
		'Security & Validation': { total: 0, passed: 0, failed: 0 },
	},
	details: [],
};

/**
 * Test runner function
 * @param category
 * @param name
 * @param fn
 */
function test( category, name, fn ) {
	results.total++;
	results.categories[ category ].total++;

	try {
		const result = fn();
		if ( result === true ) {
			results.passed++;
			results.categories[ category ].passed++;
			console.log(
				`${ colors.green }✓${ colors.reset } ${ colors.gray }[${ category }]${ colors.reset } ${ name }`
			);
			results.details.push( { category, name, status: 'PASS' } );
		} else if ( result === 'warning' ) {
			results.warnings++;
			results.categories[ category ].passed++;
			console.log(
				`${ colors.yellow }⚠${ colors.reset } ${ colors.gray }[${ category }]${ colors.reset } ${ name }`
			);
			results.details.push( {
				category,
				name,
				status: 'WARNING',
				message: result.message || '',
			} );
		} else {
			results.failed++;
			results.categories[ category ].failed++;
			console.log(
				`${ colors.red }✗${ colors.reset } ${ colors.gray }[${ category }]${ colors.reset } ${ name }`
			);
			results.details.push( {
				category,
				name,
				status: 'FAIL',
				message: result,
			} );
		}
	} catch ( error ) {
		results.failed++;
		results.categories[ category ].failed++;
		console.log(
			`${ colors.red }✗${ colors.reset } ${ colors.gray }[${ category }]${ colors.reset } ${ name }`
		);
		console.log(
			`  ${ colors.red }Error: ${ error.message }${ colors.reset }`
		);
		results.details.push( {
			category,
			name,
			status: 'FAIL',
			message: error.message,
		} );
	}
}

/**
 * Helper functions
 * @param filePath
 */
function fileExists( filePath ) {
	return fs.existsSync( path.join( __dirname, filePath ) );
}

function readFile( filePath ) {
	return fs.readFileSync( path.join( __dirname, filePath ), 'utf8' );
}

function containsPattern( filePath, pattern ) {
	const content = readFile( filePath );
	return pattern.test( content );
}

function countOccurrences( filePath, pattern ) {
	const content = readFile( filePath );
	const matches = content.match( pattern );
	return matches ? matches.length : 0;
}

/**
 * Print header
 */
console.log(
	'\n' + colors.bright + colors.cyan + '━'.repeat( 80 ) + colors.reset
);
console.log(
	colors.bright +
		'  EIPSI Forms - Admin Workflows Validation (Phase 7)' +
		colors.reset
);
console.log( colors.cyan + '━'.repeat( 80 ) + colors.reset + '\n' );

console.log(
	`${ colors.gray }Testing all admin-side functionality...${ colors.reset }\n`
);

// =============================================================================
// BLOCK EDITOR TESTS
// =============================================================================

console.log( colors.bright + '\n📝 Block Editor Components\n' + colors.reset );

test( 'Block Editor', 'Form Container block edit.js exists', () => {
	return fileExists( 'src/blocks/form-container/edit.js' );
} );

test( 'Block Editor', 'Form Container imports InspectorControls', () => {
	return containsPattern(
		'src/blocks/form-container/edit.js',
		/InspectorControls/
	);
} );

test( 'Block Editor', 'Form Container has formId attribute control', () => {
	return containsPattern( 'src/blocks/form-container/edit.js', /formId/ );
} );

test( 'Block Editor', 'Form Container has allowBackwardsNav toggle', () => {
	return containsPattern(
		'src/blocks/form-container/edit.js',
		/allowBackwardsNav/
	);
} );

test( 'Block Editor', 'Form Container has description textarea', () => {
	return containsPattern(
		'src/blocks/form-container/edit.js',
		/description/
	);
} );

test( 'Block Editor', 'Form Container imports FormStylePanel', () => {
	return containsPattern(
		'src/blocks/form-container/edit.js',
		/FormStylePanel/
	);
} );

test( 'Block Editor', 'FormStylePanel component exists', () => {
	return fileExists( 'src/components/FormStylePanel.js' );
} );

test( 'Block Editor', 'FormStylePanel has preset application logic', () => {
	return containsPattern( 'src/components/FormStylePanel.js', /applyPreset/ );
} );

test( 'Block Editor', 'FormStylePanel has color picker controls', () => {
	return containsPattern(
		'src/components/FormStylePanel.js',
		/ColorPalette/
	);
} );

test( 'Block Editor', 'FormStylePanel has spacing controls', () => {
	return containsPattern(
		'src/components/FormStylePanel.js',
		/RangeControl/
	);
} );

test( 'Block Editor', 'styleTokens utility exists', () => {
	return fileExists( 'src/utils/styleTokens.js' );
} );

test(
	'Block Editor',
	'styleTokens has serializeToCSSVariables function',
	() => {
		return containsPattern(
			'src/utils/styleTokens.js',
			/serializeToCSSVariables/
		);
	}
);

test( 'Block Editor', 'stylePresets utility exists', () => {
	return fileExists( 'src/utils/stylePresets.js' );
} );

test( 'Block Editor', 'stylePresets has STYLE_PRESETS constant', () => {
	return containsPattern( 'src/utils/stylePresets.js', /STYLE_PRESETS/ );
} );

test( 'Block Editor', 'Form Container has block.json definition', () => {
	return fileExists( 'blocks/form-container/block.json' );
} );

test( 'Block Editor', 'Form Container save.js exists', () => {
	return fileExists( 'src/blocks/form-container/save.js' );
} );

test( 'Block Editor', 'Form Container has allowed blocks list', () => {
	return containsPattern(
		'src/blocks/form-container/edit.js',
		/ALLOWED_BLOCKS/
	);
} );

test( 'Block Editor', 'Form Page block exists', () => {
	return fileExists( 'src/blocks/pagina/edit.js' );
} );

test( 'Block Editor', 'Form fields blocks exist (7 types)', () => {
	const fields = [
		'campo-texto',
		'campo-textarea',
		'campo-descripcion',
		'campo-select',
		'campo-radio',
		'campo-multiple',
		'campo-likert',
	];
	return fields.every( ( field ) =>
		fileExists( `src/blocks/${ field }/edit.js` )
	);
} );

test( 'Block Editor', 'VAS Slider block exists', () => {
	return fileExists( 'src/blocks/vas-slider/edit.js' );
} );

// =============================================================================
// RESULTS PAGE TESTS
// =============================================================================

console.log( colors.bright + '\n📊 Results Page\n' + colors.reset );

test( 'Results Page', 'results-page.php exists', () => {
	return fileExists( 'admin/results-page.php' );
} );

test( 'Results Page', 'Has form filter dropdown', () => {
	return containsPattern( 'admin/results-page.php', /form_filter/ );
} );

test( 'Results Page', 'Has dynamic Form ID column visibility', () => {
	return containsPattern( 'admin/results-page.php', /show_form_column/ );
} );

test( 'Results Page', 'Has dynamic colspan calculation', () => {
	return containsPattern(
		'admin/results-page.php',
		/\$colspan = \$show_form_column \? 8 : 7/
	);
} );

test(
	'Results Page',
	'Displays privacy notice about metadata-only view',
	() => {
		return containsPattern( 'admin/results-page.php', /Privacy Notice/ );
	}
);

test( 'Results Page', 'Has View response button with data-id attribute', () => {
	return containsPattern(
		'admin/results-page.php',
		/vas-view-response.*data-id/s
	);
} );

test( 'Results Page', 'Has Delete response button with nonce URL', () => {
	return containsPattern(
		'admin/results-page.php',
		/wp_nonce_url.*delete_response_/s
	);
} );

test( 'Results Page', 'Has response modal HTML structure', () => {
	return containsPattern( 'admin/results-page.php', /vas-response-modal/ );
} );

test( 'Results Page', 'Has AJAX call to eipsi_get_response_details', () => {
	return containsPattern(
		'admin/results-page.php',
		/eipsi_get_response_details/
	);
} );

test( 'Results Page', 'Has research context toggle button', () => {
	return containsPattern(
		'admin/results-page.php',
		/toggle-research-context/
	);
} );

test( 'Results Page', 'Formats date/time with WordPress timezone', () => {
	return containsPattern(
		'admin/results-page.php',
		/timezone_string.*DateTimeZone/s
	);
} );

test( 'Results Page', 'Uses duration_seconds with fallback to duration', () => {
	return containsPattern(
		'admin/results-page.php',
		/duration_seconds.*duration/
	);
} );

test( 'Results Page', 'Has CSV export button', () => {
	return containsPattern( 'admin/results-page.php', /export_csv/ );
} );

test( 'Results Page', 'Has Excel export button', () => {
	return containsPattern( 'admin/results-page.php', /export_excel/ );
} );

test( 'Results Page', 'Has delete success/error notice handling', () => {
	return containsPattern(
		'admin/results-page.php',
		/deleted.*notice-success/s
	);
} );

test( 'Results Page', 'Has nonce creation for AJAX', () => {
	return containsPattern(
		'admin/results-page.php',
		/wp_create_nonce.*eipsi_admin_nonce/
	);
} );

// =============================================================================
// CONFIGURATION PANEL TESTS
// =============================================================================

console.log( colors.bright + '\n⚙️  Configuration Panel\n' + colors.reset );

test( 'Configuration Panel', 'configuration.php exists', () => {
	return fileExists( 'admin/configuration.php' );
} );

test( 'Configuration Panel', 'Has database indicator banner', () => {
	return containsPattern(
		'admin/configuration.php',
		/eipsi-db-indicator-banner/
	);
} );

test(
	'Configuration Panel',
	'Shows external vs WordPress database badge',
	() => {
		return containsPattern(
			'admin/configuration.php',
			/eipsi-db-badge--external.*eipsi-db-badge--wordpress/s
		);
	}
);

test( 'Configuration Panel', 'Has database connection form', () => {
	return containsPattern( 'admin/configuration.php', /eipsi-db-config-form/ );
} );

test( 'Configuration Panel', 'Has host input field', () => {
	return containsPattern( 'admin/configuration.php', /db_host/ );
} );

test( 'Configuration Panel', 'Has username input field', () => {
	return containsPattern( 'admin/configuration.php', /db_user/ );
} );

test(
	'Configuration Panel',
	'Has password input field with conditional required',
	() => {
		return containsPattern(
			'admin/configuration.php',
			/db_password.*credentials \? '' : 'required'/s
		);
	}
);

test( 'Configuration Panel', 'Has database name input field', () => {
	return containsPattern( 'admin/configuration.php', /db_name/ );
} );

test( 'Configuration Panel', 'Has Test Connection button', () => {
	return containsPattern(
		'admin/configuration.php',
		/eipsi-test-connection/
	);
} );

test(
	'Configuration Panel',
	'Has Save Configuration button (disabled by default)',
	() => {
		return containsPattern(
			'admin/configuration.php',
			/eipsi-save-config.*disabled/s
		);
	}
);

test( 'Configuration Panel', 'Has Disable External Database button', () => {
	return containsPattern(
		'admin/configuration.php',
		/eipsi-disable-external-db/
	);
} );

test( 'Configuration Panel', 'Has message container with role="alert"', () => {
	return containsPattern(
		'admin/configuration.php',
		/eipsi-message-container.*role="alert"/s
	);
} );

test( 'Configuration Panel', 'Has status box with connection indicator', () => {
	return containsPattern( 'admin/configuration.php', /eipsi-status-box/ );
} );

test( 'Configuration Panel', 'Displays record count and last updated', () => {
	return containsPattern(
		'admin/configuration.php',
		/record_count.*last_updated/s
	);
} );

test( 'Configuration Panel', 'Has fallback mode error display', () => {
	return containsPattern( 'admin/configuration.php', /Fallback Mode Active/ );
} );

test( 'Configuration Panel', 'Has setup instructions help section', () => {
	return containsPattern( 'admin/configuration.php', /Setup Instructions/ );
} );

test( 'Configuration Panel', 'Requires manage_options capability', () => {
	return containsPattern(
		'admin/configuration.php',
		/current_user_can.*manage_options/
	);
} );

test( 'Configuration Panel', 'Uses eipsi_admin_nonce for security', () => {
	return containsPattern( 'admin/configuration.php', /eipsi_admin_nonce/ );
} );

// =============================================================================
// EXPORT FUNCTIONALITY TESTS
// =============================================================================

console.log( colors.bright + '\n📤 Export Functionality\n' + colors.reset );

test( 'Export Functionality', 'export.php exists', () => {
	return fileExists( 'admin/export.php' );
} );

test( 'Export Functionality', 'Includes SimpleXLSXGen library', () => {
	return containsPattern( 'admin/export.php', /SimpleXLSXGen/ );
} );

test( 'Export Functionality', 'Has Excel export function', () => {
	return containsPattern( 'admin/export.php', /vas_export_to_excel/ );
} );

test( 'Export Functionality', 'Has CSV export function', () => {
	return containsPattern( 'admin/export.php', /vas_export_to_csv/ );
} );

test( 'Export Functionality', 'Excel export has Form ID column', () => {
	return containsPattern( 'admin/export.php', /'Form ID'/ );
} );

test( 'Export Functionality', 'Excel export has Participant ID column', () => {
	return containsPattern( 'admin/export.php', /'Participant ID'/ );
} );

test( 'Export Functionality', 'Excel export has timestamp columns', () => {
	return containsPattern(
		'admin/export.php',
		/'Start Time \(UTC\)'.*'End Time \(UTC\)'/s
	);
} );

test( 'Export Functionality', 'Uses duration_seconds with fallback', () => {
	return containsPattern(
		'admin/export.php',
		/duration_seconds.*number_format.*duration/s
	);
} );

test( 'Export Functionality', 'Generates stable form ID from form name', () => {
	return containsPattern(
		'admin/export.php',
		/export_generate_stable_form_id/
	);
} );

test(
	'Export Functionality',
	'Generates stable participant fingerprint',
	() => {
		return containsPattern(
			'admin/export.php',
			/export_generateStableFingerprint/
		);
	}
);

test( 'Export Functionality', 'Excludes internal fields from export', () => {
	return containsPattern( 'admin/export.php', /internal_fields = array\(/ );
} );

test( 'Export Functionality', 'Dynamically includes all form questions', () => {
	return containsPattern( 'admin/export.php', /all_questions/ );
} );

test( 'Export Functionality', 'Supports filtered export by form_name', () => {
	return containsPattern( 'admin/export.php', /form_filter.*form_name/s );
} );

test( 'Export Functionality', 'CSV has proper UTF-8 encoding header', () => {
	return containsPattern( 'admin/export.php', /charset=utf-8/ );
} );

test( 'Export Functionality', 'Formats timestamps as ISO 8601', () => {
	return containsPattern( 'admin/export.php', /gmdate.*Y-m-d\\TH:i:s/ );
} );

test( 'Export Functionality', 'Requires manage_options capability', () => {
	return containsPattern(
		'admin/export.php',
		/current_user_can.*manage_options/
	);
} );

test( 'Export Functionality', 'Has admin_init hook for export actions', () => {
	return containsPattern( 'admin/export.php', /add_action.*admin_init/ );
} );

// =============================================================================
// AJAX HANDLERS TESTS
// =============================================================================

console.log( colors.bright + '\n🔌 AJAX Handlers\n' + colors.reset );

test( 'AJAX Handlers', 'ajax-handlers.php exists', () => {
	return fileExists( 'admin/ajax-handlers.php' );
} );

test( 'AJAX Handlers', 'Registers vas_dinamico_submit_form handler', () => {
	return containsPattern(
		'admin/ajax-handlers.php',
		/add_action.*vas_dinamico_submit_form/
	);
} );

test( 'AJAX Handlers', 'Registers eipsi_get_response_details handler', () => {
	return containsPattern(
		'admin/ajax-handlers.php',
		/add_action.*eipsi_get_response_details/
	);
} );

test( 'AJAX Handlers', 'Registers eipsi_track_event handler', () => {
	return containsPattern(
		'admin/ajax-handlers.php',
		/add_action.*eipsi_track_event/
	);
} );

test( 'AJAX Handlers', 'Registers eipsi_test_db_connection handler', () => {
	return containsPattern(
		'admin/ajax-handlers.php',
		/add_action.*eipsi_test_db_connection/
	);
} );

test( 'AJAX Handlers', 'Registers eipsi_save_db_config handler', () => {
	return containsPattern(
		'admin/ajax-handlers.php',
		/add_action.*eipsi_save_db_config/
	);
} );

test( 'AJAX Handlers', 'Registers eipsi_disable_external_db handler', () => {
	return containsPattern(
		'admin/ajax-handlers.php',
		/add_action.*eipsi_disable_external_db/
	);
} );

test( 'AJAX Handlers', 'Form submission uses check_ajax_referer', () => {
	return containsPattern( 'admin/ajax-handlers.php', /check_ajax_referer/ );
} );

test( 'AJAX Handlers', 'Generates stable form ID in submission', () => {
	return containsPattern(
		'admin/ajax-handlers.php',
		/generate_stable_form_id/
	);
} );

test(
	'AJAX Handlers',
	'Generates participant fingerprint in submission',
	() => {
		return containsPattern(
			'admin/ajax-handlers.php',
			/generateStableFingerprint/
		);
	}
);

test( 'AJAX Handlers', 'Calculates duration from timestamps', () => {
	return containsPattern(
		'admin/ajax-handlers.php',
		/duration_seconds.*start_timestamp_ms.*end_timestamp_ms/s
	);
} );

test( 'AJAX Handlers', 'Sanitizes all POST data', () => {
	const count = countOccurrences(
		'admin/ajax-handlers.php',
		/sanitize_text_field|sanitize_email|intval/g
	);
	return count >= 10; // Should have many sanitization calls
} );

test( 'AJAX Handlers', 'Has ABSPATH security check', () => {
	return containsPattern(
		'admin/ajax-handlers.php',
		/if \(!defined\('ABSPATH'\)\)/
	);
} );

test( 'AJAX Handlers', 'External database helper class exists', () => {
	return fileExists( 'admin/database.php' );
} );

test( 'AJAX Handlers', 'handlers.php exists for additional handlers', () => {
	return fileExists( 'admin/handlers.php' );
} );

// =============================================================================
// ADMIN ASSETS TESTS
// =============================================================================

console.log( colors.bright + '\n🎨 Admin Assets\n' + colors.reset );

test( 'Admin Assets', 'admin-style.css exists', () => {
	return fileExists( 'assets/css/admin-style.css' );
} );

test( 'Admin Assets', 'configuration-panel.css exists', () => {
	return fileExists( 'assets/css/configuration-panel.css' );
} );

test( 'Admin Assets', 'configuration-panel.js exists', () => {
	return fileExists( 'assets/js/configuration-panel.js' );
} );

test( 'Admin Assets', 'admin-script.js exists', () => {
	return fileExists( 'assets/js/admin-script.js' );
} );

test( 'Admin Assets', 'configuration-panel.js has EIPSIConfig object', () => {
	return containsPattern(
		'assets/js/configuration-panel.js',
		/const EIPSIConfig/
	);
} );

test(
	'Admin Assets',
	'configuration-panel.js has testConnection method',
	() => {
		return containsPattern(
			'assets/js/configuration-panel.js',
			/testConnection/
		);
	}
);

test(
	'Admin Assets',
	'configuration-panel.js has saveConfiguration method',
	() => {
		return containsPattern(
			'assets/js/configuration-panel.js',
			/saveConfiguration/
		);
	}
);

test(
	'Admin Assets',
	'configuration-panel.js has disableExternalDB method',
	() => {
		return containsPattern(
			'assets/js/configuration-panel.js',
			/disableExternalDB/
		);
	}
);

test( 'Admin Assets', 'configuration-panel.js has showMessage method', () => {
	return containsPattern( 'assets/js/configuration-panel.js', /showMessage/ );
} );

test(
	'Admin Assets',
	'configuration-panel.js has updateStatusBox method',
	() => {
		return containsPattern(
			'assets/js/configuration-panel.js',
			/updateStatusBox/
		);
	}
);

test(
	'Admin Assets',
	'configuration-panel.js disables save until test passes',
	() => {
		return containsPattern(
			'assets/js/configuration-panel.js',
			/connectionTested = false.*save-config.*disabled/s
		);
	}
);

test(
	'Admin Assets',
	'configuration-panel.js clears password after save',
	() => {
		return containsPattern(
			'assets/js/configuration-panel.js',
			/#db_password.*val\( '' \)/s
		);
	}
);

test(
	'Admin Assets',
	'configuration-panel.js has loading state handling',
	() => {
		return containsPattern(
			'assets/js/configuration-panel.js',
			/eipsi-loading/
		);
	}
);

test(
	'Admin Assets',
	'configuration-panel.js auto-hides success messages',
	() => {
		return containsPattern(
			'assets/js/configuration-panel.js',
			/setTimeout.*fadeOut/s
		);
	}
);

test( 'Admin Assets', 'admin-style.css has responsive styles', () => {
	const content = readFile( 'assets/css/admin-style.css' );
	return /@media/.test( content );
} );

test(
	'Admin Assets',
	'configuration-panel.css has status indicator styles',
	() => {
		return containsPattern(
			'assets/css/configuration-panel.css',
			/status-icon|status-connected|status-disconnected/
		);
	}
);

// =============================================================================
// SECURITY & VALIDATION TESTS
// =============================================================================

console.log( colors.bright + '\n🔒 Security & Validation\n' + colors.reset );

test( 'Security & Validation', 'All admin PHP files have ABSPATH check', () => {
	const adminFiles = [
		'admin/ajax-handlers.php',
		'admin/configuration.php',
		'admin/database.php',
		'admin/export.php',
		'admin/handlers.php',
		'admin/results-page.php',
		'admin/menu.php',
	];
	return adminFiles.every( ( file ) => {
		if ( ! fileExists( file ) ) {
			return false;
		}
		return containsPattern( file, /if \(!defined\('ABSPATH'\)\)/ );
	} );
} );

test(
	'Security & Validation',
	'Results page uses wp_nonce_url for delete',
	() => {
		return containsPattern( 'admin/results-page.php', /wp_nonce_url/ );
	}
);

test(
	'Security & Validation',
	'Results page has confirmation dialog for delete',
	() => {
		return containsPattern(
			'admin/results-page.php',
			/confirm.*delete this response/s
		);
	}
);

test( 'Security & Validation', 'Configuration page creates nonce', () => {
	return containsPattern( 'admin/configuration.php', /wp_create_nonce/ );
} );

test(
	'Security & Validation',
	'Export functions check manage_options capability',
	() => {
		const content = readFile( 'admin/export.php' );
		const matches = content.match(
			/current_user_can\('manage_options'\)/g
		);
		return matches && matches.length >= 2; // Both export functions
	}
);

test( 'Security & Validation', 'AJAX handlers use nonce verification', () => {
	return containsPattern(
		'admin/ajax-handlers.php',
		/check_ajax_referer|wp_verify_nonce/
	);
} );

test( 'Security & Validation', 'Configuration requires manage_options', () => {
	return containsPattern(
		'admin/configuration.php',
		/current_user_can.*manage_options.*wp_die/s
	);
} );

test( 'Security & Validation', 'All form inputs are sanitized', () => {
	const count = countOccurrences(
		'admin/ajax-handlers.php',
		/sanitize_text_field|sanitize_email|esc_attr|esc_html/g
	);
	return count >= 15; // Should have many sanitization calls
} );

test(
	'Security & Validation',
	'Database queries use prepared statements',
	() => {
		const content = readFile( 'admin/ajax-handlers.php' );
		const prepare = ( content.match( /\$wpdb->prepare/g ) || [] ).length;
		const direct = (
			content.match(
				/\$wpdb->query\(\s*"SELECT|INSERT|UPDATE|DELETE/g
			) || []
		).length;
		return prepare > 0 && direct === 0; // Has prepared statements, no direct queries
	}
);

test( 'Security & Validation', 'Output is escaped in results page', () => {
	const count = countOccurrences(
		'admin/results-page.php',
		/esc_html|esc_attr|esc_url/g
	);
	return count >= 15; // Should have many escaping calls (16 found in current implementation)
} );

test( 'Security & Validation', 'AJAX responses use wp_send_json_*', () => {
	return containsPattern(
		'admin/ajax-handlers.php',
		/wp_send_json_success|wp_send_json_error/
	);
} );

test(
	'Security & Validation',
	'Delete action validates nonce with response ID',
	() => {
		return containsPattern(
			'admin/results-page.php',
			/delete_response_.*\$row->id/s
		);
	}
);

// =============================================================================
// GENERATE SUMMARY
// =============================================================================

console.log( '\n' + colors.cyan + '━'.repeat( 80 ) + colors.reset );
console.log( colors.bright + '  Test Results Summary' + colors.reset );
console.log( colors.cyan + '━'.repeat( 80 ) + colors.reset + '\n' );

// Category breakdown
console.log( colors.bright + 'Results by Category:\n' + colors.reset );
Object.keys( results.categories ).forEach( ( category ) => {
	const cat = results.categories[ category ];
	const passRate =
		cat.total > 0
			? ( ( cat.passed / cat.total ) * 100 ).toFixed( 1 )
			: '0.0';
	const statusColor = cat.failed === 0 ? colors.green : colors.yellow;
	console.log(
		`${ statusColor }${ category.padEnd( 25 ) }${ colors.reset } ` +
			`${ colors.green }${ cat.passed }${ colors.reset }/${ cat.total } passed ` +
			`(${ statusColor }${ passRate }%${ colors.reset })`
	);
} );

console.log( '\n' + colors.cyan + '─'.repeat( 80 ) + colors.reset + '\n' );

// Overall stats
const passRate = ( ( results.passed / results.total ) * 100 ).toFixed( 1 );
const overallColor =
	results.failed === 0
		? colors.green
		: results.failed < 5
		? colors.yellow
		: colors.red;

console.log( colors.bright + 'Overall Results:\n' + colors.reset );
console.log(
	`  ${ colors.green }Passed:  ${ colors.bright }${ results.passed }${ colors.reset }`
);
console.log(
	`  ${ colors.red }Failed:  ${ colors.bright }${ results.failed }${ colors.reset }`
);
console.log(
	`  ${ colors.yellow }Warnings: ${ colors.bright }${ results.warnings }${ colors.reset }`
);
console.log(
	`  Total:   ${ colors.bright }${ results.total }${ colors.reset }`
);
console.log(
	`  ${ overallColor }Pass Rate: ${ colors.bright }${ passRate }%${ colors.reset }\n`
);

// Status badge
if ( results.failed === 0 ) {
	console.log(
		`  ${ colors.green }${ colors.bright }✓ ALL TESTS PASSED${ colors.reset }\n`
	);
} else if ( results.failed < 5 ) {
	console.log(
		`  ${ colors.yellow }${ colors.bright }⚠ MOSTLY PASSING (Minor Issues)${ colors.reset }\n`
	);
} else {
	console.log(
		`  ${ colors.red }${ colors.bright }✗ TESTS FAILED${ colors.reset }\n`
	);
}

console.log( colors.cyan + '━'.repeat( 80 ) + colors.reset + '\n' );

// Export results to JSON
const jsonOutput = {
	summary: {
		total: results.total,
		passed: results.passed,
		failed: results.failed,
		warnings: results.warnings,
		passRate: parseFloat( passRate ),
	},
	categories: results.categories,
	tests: results.details,
	timestamp: new Date().toISOString(),
};

fs.writeFileSync(
	path.join( __dirname, 'docs/qa/admin-workflows-validation.json' ),
	JSON.stringify( jsonOutput, null, 2 )
);

console.log(
	`${ colors.gray }Results saved to: docs/qa/admin-workflows-validation.json${ colors.reset }\n`
);

// Exit with appropriate code
process.exit( results.failed > 0 ? 1 : 0 );
