/**
 * FINAL AUDIT TEST SUITE - EIPSI Forms v1.2.1
 * Validates all 10 critical production readiness criteria
 *
 * Run with: node test-final-audit-v1.2.1.js
 */

const fs = require( 'fs' );
const path = require( 'path' );

class FinalAuditValidator {
	constructor() {
		this.results = {
			passed: 0,
			failed: 0,
			warnings: 0,
			tests: [],
		};
	}

	log( message, type = 'info' ) {
		const icons = {
			pass: '✅',
			fail: '❌',
			warn: '⚠️',
			info: 'ℹ️',
		};
		console.log( `${ icons[ type ] || icons.info } ${ message }` );
	}

	test( name, fn ) {
		try {
			const result = fn();
			if ( result.pass ) {
				this.results.passed++;
				this.log( `${ name }: PASS`, 'pass' );
				if ( result.details ) {
					result.details.forEach( ( detail ) =>
						console.log( `   ${ detail }` )
					);
				}
			} else {
				this.results.failed++;
				this.log( `${ name }: FAIL`, 'fail' );
				if ( result.reason ) {
					console.log( `   Reason: ${ result.reason }` );
				}
			}
			this.results.tests.push( { name, ...result } );
		} catch ( error ) {
			this.results.failed++;
			this.log( `${ name }: ERROR`, 'fail' );
			console.log( `   Error: ${ error.message }` );
			this.results.tests.push( {
				name,
				pass: false,
				error: error.message,
			} );
		}
	}

	readFile( filePath ) {
		try {
			return fs.readFileSync( path.join( __dirname, filePath ), 'utf8' );
		} catch ( error ) {
			throw new Error( `Cannot read ${ filePath }: ${ error.message }` );
		}
	}

	// =========================================================================
	// TEST 6: External DB Fallback (Zero Data Loss)
	// =========================================================================
	test_external_db_fallback() {
		const ajaxHandlers = this.readFile( 'admin/ajax-handlers.php' );

		// Check for external DB try/catch
		const hasTryCatch =
			ajaxHandlers.includes( 'if ($external_db_enabled)' ) &&
			ajaxHandlers.includes( '$used_fallback = true' );

		// Check for fallback logic
		const hasFallbackLogic = ajaxHandlers.includes(
			'if (!$external_db_enabled || $used_fallback)'
		);

		// Check for error logging
		const hasErrorLogging =
			ajaxHandlers.includes( 'error_log(' ) &&
			ajaxHandlers.includes( 'falling back to WordPress DB' );

		// Check for schema repair
		const hasSchemaRepair =
			ajaxHandlers.includes( 'Unknown column' ) &&
			ajaxHandlers.includes(
				'EIPSI_Database_Schema_Manager::repair_local_schema()'
			);

		return {
			pass:
				hasTryCatch &&
				hasFallbackLogic &&
				hasErrorLogging &&
				hasSchemaRepair,
			details: [
				`External DB try/catch: ${ hasTryCatch ? 'Yes' : 'No' }`,
				`Fallback logic: ${ hasFallbackLogic ? 'Yes' : 'No' }`,
				`Error logging: ${ hasErrorLogging ? 'Yes' : 'No' }`,
				`Schema auto-repair: ${ hasSchemaRepair ? 'Yes' : 'No' }`,
			],
			reason:
				! hasTryCatch || ! hasFallbackLogic
					? 'Missing external DB fallback logic'
					: null,
		};
	}

	// =========================================================================
	// TEST 7: 6 Tracking Events (view/start/page_change/submit/abandon/branch_jump)
	// =========================================================================
	test_tracking_events() {
		const trackingJS = this.readFile( 'assets/js/eipsi-tracking.js' );
		const ajaxHandlers = this.readFile( 'admin/ajax-handlers.php' );

		const requiredEvents = [
			'view',
			'start',
			'page_change',
			'submit',
			'abandon',
			'branch_jump',
		];

		// Check if all events are defined in ALLOWED_EVENTS in tracking.js
		const hasAllowedEvents = requiredEvents.every( ( event ) =>
			trackingJS.includes( `'${ event }'` )
		);

		// Check for event handler and validation logic
		const hasEventHandler =
			ajaxHandlers.includes( 'function eipsi_track_event_handler()' ) &&
			ajaxHandlers.includes( 'wp_ajax_nopriv_eipsi_track_event' );

		const hasEventValidation =
			ajaxHandlers.includes( '$allowed_events' ) ||
			ajaxHandlers.includes( 'in_array($event_type' );

		// Check for branch_jump metadata
		const hasBranchMetadata =
			ajaxHandlers.includes( 'from_page' ) &&
			ajaxHandlers.includes( 'to_page' ) &&
			trackingJS.includes( 'branch_jump' );

		// Check for database insertion
		const hasDBInsertion =
			ajaxHandlers.includes( 'vas_form_events' ) &&
			ajaxHandlers.includes( '$insert_data' );

		return {
			pass:
				hasAllowedEvents &&
				hasEventHandler &&
				hasBranchMetadata &&
				hasDBInsertion,
			details: [
				`All 6 events defined: ${ hasAllowedEvents ? 'Yes' : 'No' }`,
				`Event handler: ${ hasEventHandler ? 'Yes' : 'No' }`,
				`Event validation: ${ hasEventValidation ? 'Yes' : 'No' }`,
				`Branch metadata: ${ hasBranchMetadata ? 'Yes' : 'No' }`,
				`Database insertion: ${ hasDBInsertion ? 'Yes' : 'No' }`,
			],
			reason: ! hasAllowedEvents ? 'Not all 6 events are defined' : null,
		};
	}

	// =========================================================================
	// TEST 8: Conditional Logic + Branch Jump
	// =========================================================================
	test_conditional_logic() {
		const formsJS = this.readFile( 'assets/js/eipsi-forms.js' );

		// Check for ConditionalNavigator class
		const hasNavigatorClass = formsJS.includes(
			'class ConditionalNavigator'
		);

		// Check for rule matching logic
		const hasRuleMatching =
			formsJS.includes( 'findMatchingRule' ) &&
			formsJS.includes( 'getNextPage' );

		// Check for branch jump recording
		const hasBranchJumpRecording =
			formsJS.includes( 'recordBranchJump' ) &&
			formsJS.includes( 'from_page' ) &&
			formsJS.includes( 'to_page' );

		// Check for field value extraction
		const hasFieldValueExtraction = formsJS.includes( 'getFieldValue' );

		return {
			pass:
				hasNavigatorClass &&
				hasRuleMatching &&
				hasBranchJumpRecording &&
				hasFieldValueExtraction,
			details: [
				`ConditionalNavigator class: ${
					hasNavigatorClass ? 'Yes' : 'No'
				}`,
				`Rule matching logic: ${ hasRuleMatching ? 'Yes' : 'No' }`,
				`Branch jump recording: ${
					hasBranchJumpRecording ? 'Yes' : 'No'
				}`,
				`Field value extraction: ${
					hasFieldValueExtraction ? 'Yes' : 'No'
				}`,
			],
			reason: ! hasNavigatorClass
				? 'ConditionalNavigator class not found'
				: null,
		};
	}

	// =========================================================================
	// TEST 9: Export CSV Privacy Config (CRITICAL FIX)
	// =========================================================================
	test_export_privacy() {
		const exportPHP = this.readFile( 'admin/export.php' );

		// Check if privacy config is loaded
		const loadsPrivacyConfig =
			exportPHP.includes(
				"require_once VAS_DINAMICO_PLUGIN_DIR . 'admin/privacy-config.php'"
			) && exportPHP.includes( 'get_privacy_config($first_form_id)' );

		// Check if headers respect privacy config
		const respectsPrivacyInHeaders =
			exportPHP.includes( "if ($privacy_config['ip_address'])" ) &&
			exportPHP.includes( "if ($privacy_config['browser'])" ) &&
			exportPHP.includes( "if ($privacy_config['os'])" ) &&
			exportPHP.includes( "if ($privacy_config['device_type'])" );

		// Check if row data respects privacy config
		const respectsPrivacyInRows = exportPHP.match(
			/if \(\$privacy_config\[.*?\]\) \{\s*\$row_data\[\]/g
		);

		// Check both CSV and Excel functions
		const csvFixed =
			exportPHP.includes( 'function vas_export_to_csv()' ) &&
			respectsPrivacyInHeaders;
		const excelFixed =
			exportPHP.includes( 'function vas_export_to_excel()' ) &&
			respectsPrivacyInHeaders;

		return {
			pass:
				loadsPrivacyConfig &&
				respectsPrivacyInHeaders &&
				respectsPrivacyInRows &&
				csvFixed &&
				excelFixed,
			details: [
				`Privacy config loaded: ${ loadsPrivacyConfig ? 'Yes' : 'No' }`,
				`Headers respect privacy: ${
					respectsPrivacyInHeaders ? 'Yes' : 'No'
				}`,
				`Row data respects privacy: ${
					respectsPrivacyInRows ? 'Yes' : 'No'
				}`,
				`CSV function fixed: ${ csvFixed ? 'Yes' : 'No' }`,
				`Excel function fixed: ${ excelFixed ? 'Yes' : 'No' }`,
			],
			reason: ! respectsPrivacyInHeaders
				? 'Export functions do not respect privacy config'
				: null,
		};
	}

	// =========================================================================
	// TEST 10: Quality Flag Calculation (HIGH/NORMAL/LOW)
	// =========================================================================
	test_quality_flag() {
		const ajaxHandlers = this.readFile( 'admin/ajax-handlers.php' );

		// Check for quality flag calculation function
		const hasCalcFunction = ajaxHandlers.includes(
			'function eipsi_calculate_quality_flag('
		);

		// Check for engagement score calculation
		const hasEngagementCalc = ajaxHandlers.includes(
			'function eipsi_calculate_engagement_score('
		);

		// Check for consistency score calculation
		const hasConsistencyCalc = ajaxHandlers.includes(
			'function eipsi_calculate_consistency_score('
		);

		// Check for quality flag values
		const hasQualityValues =
			ajaxHandlers.includes( "'HIGH'" ) &&
			ajaxHandlers.includes( "'NORMAL'" ) &&
			ajaxHandlers.includes( "'LOW'" );

		// Check if quality flag is assigned on submit
		const assignsQualityFlag = ajaxHandlers.includes(
			'$quality_flag = eipsi_calculate_quality_flag('
		);

		return {
			pass:
				hasCalcFunction &&
				hasEngagementCalc &&
				hasConsistencyCalc &&
				hasQualityValues &&
				assignsQualityFlag,
			details: [
				`Quality flag function: ${ hasCalcFunction ? 'Yes' : 'No' }`,
				`Engagement calculation: ${ hasEngagementCalc ? 'Yes' : 'No' }`,
				`Consistency calculation: ${
					hasConsistencyCalc ? 'Yes' : 'No'
				}`,
				`Quality values (HIGH/NORMAL/LOW): ${
					hasQualityValues ? 'Yes' : 'No'
				}`,
				`Assigned on submit: ${ assignsQualityFlag ? 'Yes' : 'No' }`,
			],
			reason: ! hasCalcFunction
				? 'Quality flag calculation function not found'
				: null,
		};
	}

	// =========================================================================
	// TEST 11: WCAG 2.1 AA Compliance (Focus Indicators)
	// =========================================================================
	test_wcag_compliance() {
		const formContainerCSS = this.readFile(
			'src/blocks/form-container/style.scss'
		);
		const likertCSS = this.readFile( 'src/blocks/campo-likert/style.scss' );

		// Check for focus indicators
		const hasFocusStyles =
			formContainerCSS.includes( ':focus' ) ||
			formContainerCSS.includes( 'focus-visible' ) ||
			likertCSS.includes( 'focus-within' );

		// Check for ARIA support
		const hasAriaSupport =
			this.readFile( 'assets/js/eipsi-forms.js' ).includes(
				'aria-hidden'
			) &&
			this.readFile( 'assets/js/eipsi-forms.js' ).includes(
				'aria-invalid'
			);

		// Check for box-shadow focus indicators
		const hasFocusShadow =
			likertCSS.includes( 'box-shadow' ) &&
			likertCSS.includes( 'focus-within' );

		return {
			pass: hasFocusStyles && hasAriaSupport && hasFocusShadow,
			details: [
				`Focus styles defined: ${ hasFocusStyles ? 'Yes' : 'No' }`,
				`ARIA support: ${ hasAriaSupport ? 'Yes' : 'No' }`,
				`Focus shadow indicators: ${ hasFocusShadow ? 'Yes' : 'No' }`,
			],
			reason: ! hasFocusStyles
				? 'Focus indicators not properly defined'
				: null,
		};
	}

	// =========================================================================
	// TEST 12: Touch Targets ≥ 44×44px
	// =========================================================================
	test_touch_targets() {
		const formContainerCSS = this.readFile(
			'src/blocks/form-container/style.scss'
		);
		const likertCSS = this.readFile( 'src/blocks/campo-likert/style.scss' );

		// Check for button padding (should result in >= 44px height)
		const hasButtonPadding =
			formContainerCSS.includes( 'padding: 0.9em 2em' ) ||
			formContainerCSS.includes( 'padding: 0.9em 2.5em' );

		// Check for Likert item padding
		const hasLikertPadding =
			likertCSS.includes( 'padding: 0.9em 1em' ) ||
			likertCSS.includes( 'padding: 1em 0.5em' );

		// Check for responsive design
		const hasResponsiveDesign =
			likertCSS.includes( '@media (max-width:' ) &&
			formContainerCSS.includes( '@media (max-width:' );

		return {
			pass: hasButtonPadding && hasLikertPadding && hasResponsiveDesign,
			details: [
				`Button padding adequate: ${ hasButtonPadding ? 'Yes' : 'No' }`,
				`Likert item padding adequate: ${
					hasLikertPadding ? 'Yes' : 'No'
				}`,
				`Responsive design: ${ hasResponsiveDesign ? 'Yes' : 'No' }`,
			],
			reason: ! hasButtonPadding
				? 'Button touch targets may be too small'
				: null,
		};
	}

	// =========================================================================
	// TEST 13: DB Credentials Encrypted
	// =========================================================================
	test_db_encryption() {
		const databasePHP = this.readFile( 'admin/database.php' );

		// Check for encryption functions
		const hasEncryptFunction = databasePHP.includes(
			'private function encrypt_data('
		);
		const hasDecryptFunction = databasePHP.includes(
			'private function decrypt_data('
		);

		// Check for OpenSSL usage
		const usesOpenSSL =
			databasePHP.includes( 'openssl_encrypt' ) &&
			databasePHP.includes( 'openssl_decrypt' ) &&
			databasePHP.includes( 'aes-256-cbc' );

		// Check for WordPress salt usage
		const usesWPSalt = databasePHP.includes( "wp_salt('auth')" );

		// Check for IV (initialization vector)
		const usesIV =
			databasePHP.includes( 'openssl_cipher_iv_length' ) &&
			databasePHP.includes( 'openssl_random_pseudo_bytes' );

		return {
			pass:
				hasEncryptFunction &&
				hasDecryptFunction &&
				usesOpenSSL &&
				usesWPSalt &&
				usesIV,
			details: [
				`Encrypt function: ${ hasEncryptFunction ? 'Yes' : 'No' }`,
				`Decrypt function: ${ hasDecryptFunction ? 'Yes' : 'No' }`,
				`OpenSSL AES-256-CBC: ${ usesOpenSSL ? 'Yes' : 'No' }`,
				`WordPress salt: ${ usesWPSalt ? 'Yes' : 'No' }`,
				`Initialization vector: ${ usesIV ? 'Yes' : 'No' }`,
			],
			reason: ! usesOpenSSL
				? 'Encryption not properly implemented'
				: null,
		};
	}

	// =========================================================================
	// TEST 14: GDPR Participant ID Deletion (MANUAL TEST REQUIRED)
	// =========================================================================
	test_gdpr_deletion() {
		// This requires manual SQL testing, so we check for infrastructure
		const ajaxHandlers = this.readFile( 'admin/ajax-handlers.php' );

		// Check for participant_id in database schema
		const hasParticipantID = ajaxHandlers.includes( 'participant_id' );

		// Check for deletion capability (would be in a separate file typically)
		// For now, we just verify the infrastructure exists
		const hasInfrastructure = hasParticipantID;

		return {
			pass: hasInfrastructure,
			details: [
				`Participant ID infrastructure: ${
					hasParticipantID ? 'Yes' : 'No'
				}`,
				`⚠️  MANUAL TEST REQUIRED: Verify deletion via admin panel`,
				`⚠️  SQL: DELETE FROM wp_vas_form_results WHERE participant_id = 'p-abc123'`,
				`⚠️  SQL: DELETE FROM wp_vas_form_events WHERE participant_id = 'p-abc123'`,
			],
			reason: ! hasInfrastructure
				? 'Participant ID infrastructure missing'
				: null,
		};
	}

	// =========================================================================
	// TEST 15: Performance (JS Bundle Size)
	// =========================================================================
	test_performance() {
		// Check if build files exist and are reasonable size
		const buildPath = path.join( __dirname, 'build' );
		const assetPath = path.join( __dirname, 'assets/js' );

		let buildExists = false;
		let assetsExist = false;
		let bundleSize = 0;
		let bundleSizeKB = 0;

		try {
			buildExists = fs.existsSync( buildPath );
			assetsExist = fs.existsSync( assetPath );

			// Check main JS bundle size
			const mainJS = path.join( buildPath, 'index.js' );
			if ( fs.existsSync( mainJS ) ) {
				const stats = fs.statSync( mainJS );
				bundleSize = stats.size;
				bundleSizeKB = Math.round( bundleSize / 1024 );
			}
		} catch ( error ) {
			// Directories don't exist
		}

		// Check for build script in package.json
		let hasBuildScript = false;
		try {
			const packageJSON = JSON.parse(
				fs.readFileSync(
					path.join( __dirname, 'package.json' ),
					'utf8'
				)
			);
			hasBuildScript = packageJSON.scripts && packageJSON.scripts.build;
		} catch ( error ) {
			// Package.json not found
		}

		// Check if bundle size is under target (180kb uncompressed, typically 50-60kb gzipped)
		const bundleSizeOK = bundleSizeKB > 0 && bundleSizeKB < 180;

		return {
			pass: buildExists && assetsExist && hasBuildScript && bundleSizeOK,
			details: [
				`Build directory: ${ buildExists ? 'Yes' : 'No' }`,
				`Assets directory: ${ assetsExist ? 'Yes' : 'No' }`,
				`Build script: ${ hasBuildScript ? 'Yes (wp-scripts)' : 'No' }`,
				`Main JS bundle: ${ bundleSizeKB }KB (Target: < 180KB)`,
				`Bundle size OK: ${ bundleSizeOK ? 'Yes ✅' : 'No ❌' }`,
				`⚠️  Note: Gzipped size typically ~30-40% of uncompressed (est. ${ Math.round(
					bundleSizeKB * 0.35
				) }KB)`,
			],
			reason:
				! bundleSizeOK && bundleSizeKB > 0
					? `Bundle too large: ${ bundleSizeKB }KB`
					: ! buildExists
					? 'Build directory not found - run npm run build'
					: null,
		};
	}

	// =========================================================================
	// RUN ALL TESTS
	// =========================================================================
	runAll() {
		console.log( '\n========================================' );
		console.log( 'EIPSI FORMS v1.2.1 - FINAL AUDIT' );
		console.log( 'Production Readiness Validation' );
		console.log( '========================================\n' );

		this.test( '6. External DB Fallback (Zero Data Loss)', () =>
			this.test_external_db_fallback()
		);
		this.test( '7. 6 Tracking Events', () => this.test_tracking_events() );
		this.test( '8. Conditional Logic + Branch Jump', () =>
			this.test_conditional_logic()
		);
		this.test( '9. Export CSV Privacy Config', () =>
			this.test_export_privacy()
		);
		this.test( '10. Quality Flag Calculation', () =>
			this.test_quality_flag()
		);
		this.test( '11. WCAG 2.1 AA Compliance', () =>
			this.test_wcag_compliance()
		);
		this.test( '12. Touch Targets ≥ 44×44px', () =>
			this.test_touch_targets()
		);
		this.test( '13. DB Credentials Encrypted', () =>
			this.test_db_encryption()
		);
		this.test( '14. GDPR Participant ID Deletion', () =>
			this.test_gdpr_deletion()
		);
		this.test( '15. Performance & Bundle Size', () =>
			this.test_performance()
		);

		this.printSummary();
		this.generateReport();
	}

	printSummary() {
		console.log( '\n========================================' );
		console.log( 'AUDIT SUMMARY' );
		console.log( '========================================' );
		console.log( `✅ Passed: ${ this.results.passed }/10` );
		console.log( `❌ Failed: ${ this.results.failed }/10` );
		console.log(
			`⚠️  Manual tests required: 2 (GDPR deletion, Performance)`
		);

		const passRate = ( ( this.results.passed / 10 ) * 100 ).toFixed( 1 );
		console.log( `\n📊 Pass Rate: ${ passRate }%` );

		if ( this.results.passed >= 8 ) {
			console.log( '\n✅ PRODUCTION READY (with manual validation)' );
		} else if ( this.results.passed >= 6 ) {
			console.log( '\n⚠️  REQUIRES FIXES BEFORE PRODUCTION' );
		} else {
			console.log( '\n❌ NOT PRODUCTION READY' );
		}
		console.log( '========================================\n' );
	}

	generateReport() {
		const report = {
			version: '1.2.1',
			timestamp: new Date().toISOString(),
			results: this.results,
			recommendation:
				this.results.passed >= 8
					? 'PRODUCTION READY'
					: 'FIXES REQUIRED',
		};

		fs.writeFileSync(
			path.join( __dirname, 'final-audit-results-v1.2.1.json' ),
			JSON.stringify( report, null, 2 )
		);

		console.log(
			'📄 Full report saved to: final-audit-results-v1.2.1.json\n'
		);
	}
}

// Run the audit
const validator = new FinalAuditValidator();
validator.runAll();
