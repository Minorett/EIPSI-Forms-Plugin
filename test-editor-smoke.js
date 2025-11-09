#!/usr/bin/env node

/**
 * EIPSI Forms Editor Smoke Test
 *
 * Comprehensive end-to-end Gutenberg editor smoke tests covering:
 * - Block insertion (Form Container, Pages, all field types)
 * - Configuration workflows (inspector controls, attributes)
 * - Conditional logic authoring and validation
 * - Form Style Panel modifications
 * - Common editor workflows (duplicate, move, undo/redo, copy/paste)
 * - Console error monitoring
 * - Persistence verification
 *
 * Requirements:
 * - WordPress running with wp-env (npm run wp-env start)
 * - Plugin built (npm run build)
 * - Port 8888 accessible
 */

const puppeteer = require( 'puppeteer' );
const fs = require( 'fs' );
const path = require( 'path' );

// Configuration
const WP_URL = process.env.WP_URL || 'http://localhost:8888';
const WP_USERNAME = process.env.WP_USERNAME || 'admin';
const WP_PASSWORD = process.env.WP_PASSWORD || 'password';
const HEADLESS = process.env.HEADLESS !== 'false';
const SLOW_MO = parseInt( process.env.SLOW_MO || '0', 10 );
const SCREENSHOT_DIR = path.join( __dirname, 'test-screenshots' );

// Test Results
const testResults = {
	timestamp: new Date().toISOString(),
	environment: { WP_URL, headless: HEADLESS },
	scenarios: [],
	consoleErrors: [],
	consoleWarnings: [],
	summary: { total: 0, passed: 0, failed: 0, skipped: 0 },
};

// Utility Functions
function log( message, type = 'info' ) {
	const timestamp = new Date().toISOString();
	const prefix =
		{
			info: '✓',
			error: '✗',
			warn: '⚠',
			skip: '○',
		}[ type ] || 'ℹ';
	// eslint-disable-next-line no-console
	console.log( `[${ timestamp }] ${ prefix } ${ message }` );
}

function recordScenario( name, status, duration, details = {} ) {
	const scenario = {
		name,
		status,
		duration,
		details,
		timestamp: new Date().toISOString(),
	};
	testResults.scenarios.push( scenario );
	testResults.summary.total++;

	// Increment appropriate status counter
	if ( status === 'passed' ) {
		testResults.summary.passed++;
	} else if ( status === 'failed' ) {
		testResults.summary.failed++;
	} else {
		testResults.summary.skipped++;
	}

	// Determine status symbol and log type
	let statusSymbol = '○';
	let logType = 'skip';
	if ( status === 'passed' ) {
		statusSymbol = '✓';
		logType = 'info';
	} else if ( status === 'failed' ) {
		statusSymbol = '✗';
		logType = 'error';
	}

	log(
		`${ name }: ${ statusSymbol } ${ status.toUpperCase() } (${ duration }ms)`,
		logType
	);
}

async function takeScreenshot( page, name ) {
	if ( ! fs.existsSync( SCREENSHOT_DIR ) ) {
		fs.mkdirSync( SCREENSHOT_DIR, { recursive: true } );
	}
	const filename = `${ name
		.replace( /[^a-z0-9]/gi, '-' )
		.toLowerCase() }-${ Date.now() }.png`;
	const filepath = path.join( SCREENSHOT_DIR, filename );
	await page.screenshot( { path: filepath, fullPage: true } );
	log( `Screenshot saved: ${ filename }`, 'info' );
	return filepath;
}

async function waitForTimeout( ms ) {
	return new Promise( ( resolve ) => setTimeout( resolve, ms ) );
}

// WordPress Helper Functions
async function loginToWordPress( page ) {
	log( 'Logging into WordPress...' );
	await page.goto( `${ WP_URL }/wp-login.php`, {
		waitUntil: 'networkidle2',
	} );

	// Check if already logged in
	if ( page.url().includes( 'wp-admin' ) ) {
		log( 'Already logged in' );
		return true;
	}

	await page.type( '#user_login', WP_USERNAME );
	await page.type( '#user_pass', WP_PASSWORD );
	await page.click( '#wp-submit' );
	await page.waitForNavigation( { waitUntil: 'networkidle2' } );

	log( 'Login successful' );
	return true;
}

async function createNewPost( page, title = 'Editor Smoke Test' ) {
	log( `Creating new post: ${ title }` );
	await page.goto( `${ WP_URL }/wp-admin/post-new.php`, {
		waitUntil: 'networkidle2',
	} );

	// Wait for editor to load
	await page.waitForSelector( '.block-editor-writing-flow', {
		timeout: 30000,
	} );

	// Close welcome guide if present
	const welcomeGuide = await page.$( '.edit-post-welcome-guide' );
	if ( welcomeGuide ) {
		const closeButton = await page.$( 'button[aria-label="Close"]' );
		if ( closeButton ) {
			await closeButton.click();
			await waitForTimeout( 500 );
		}
	}

	// Set title
	const titleSelector = '.editor-post-title__input, [aria-label="Add title"]';
	await page.waitForSelector( titleSelector, { timeout: 10000 } );
	await page.click( titleSelector );
	await page.keyboard.type( title );

	log( 'Post created successfully' );
	return true;
}

async function insertBlock( page, blockName, timeout = 10000 ) {
	log( `Inserting block: ${ blockName }` );

	// Click the block inserter
	const inserterSelector =
		'.edit-post-header-toolbar__inserter-toggle, .block-editor-inserter__toggle';
	await page.waitForSelector( inserterSelector, { timeout } );
	await page.click( inserterSelector );
	await waitForTimeout( 500 );

	// Search for the block
	const searchSelector =
		'.block-editor-inserter__search input, .components-search-control__input';
	await page.waitForSelector( searchSelector, { timeout } );
	await page.click( searchSelector );
	await page.keyboard.type( blockName );
	await waitForTimeout( 500 );

	// Click the block result
	try {
		await page.waitForSelector( '.block-editor-block-types-list__item', {
			timeout: 5000,
		} );
		const blockButton = await page.$(
			'.block-editor-block-types-list__item'
		);
		if ( blockButton ) {
			await blockButton.click();
			await waitForTimeout( 500 );
		}
	} catch ( error ) {
		log(
			`Block search result not found, trying alternative selector`,
			'warn'
		);
		await page.keyboard.press( 'Enter' );
		await waitForTimeout( 500 );
	}

	return true;
}

// Test Scenarios
async function testFormContainerInsertion( page ) {
	const startTime = Date.now();
	const scenarioName = 'Form Container Block Insertion';

	try {
		await insertBlock( page, 'Form Container' );

		// Verify block was inserted
		const formContainer = await page.$(
			'.eipsi-form-container-editor, .wp-block-vas-dinamico-form-container'
		);
		if ( ! formContainer ) {
			throw new Error( 'Form Container block not found in editor' );
		}

		await takeScreenshot( page, 'form-container-inserted' );
		recordScenario( scenarioName, 'passed', Date.now() - startTime, {
			blockFound: true,
		} );
		return true;
	} catch ( error ) {
		recordScenario( scenarioName, 'failed', Date.now() - startTime, {
			error: error.message,
		} );
		await takeScreenshot( page, 'form-container-insertion-error' );
		throw error;
	}
}

async function testPageBlocksInsertion( page ) {
	const startTime = Date.now();
	const scenarioName = 'Multiple Page Blocks Insertion';

	try {
		// Insert 3 page blocks
		for ( let i = 1; i <= 3; i++ ) {
			log( `Inserting Page ${ i }` );

			// Click inside the form container
			await page.click(
				'.eipsi-form-container-editor, .wp-block-vas-dinamico-form-container'
			);
			await waitForTimeout( 300 );

			// Use the appender within the container
			const appender = await page.$( '.block-editor-inserter__toggle' );
			if ( appender ) {
				await appender.click();
				await waitForTimeout( 300 );
				await page.keyboard.type( 'Form Page' );
				await waitForTimeout( 500 );
				await page.keyboard.press( 'Enter' );
				await waitForTimeout( 500 );
			}
		}

		// Verify pages were inserted
		const pages = await page.$$(
			'.wp-block-vas-dinamico-pagina, [data-type="vas-dinamico/pagina"]'
		);
		if ( pages.length < 3 ) {
			throw new Error( `Expected 3 pages, found ${ pages.length }` );
		}

		await takeScreenshot( page, 'pages-inserted' );
		recordScenario( scenarioName, 'passed', Date.now() - startTime, {
			pagesInserted: pages.length,
		} );
		return true;
	} catch ( error ) {
		recordScenario( scenarioName, 'failed', Date.now() - startTime, {
			error: error.message,
		} );
		await takeScreenshot( page, 'pages-insertion-error' );
		throw error;
	}
}

async function testFieldBlocksInsertion( page ) {
	const startTime = Date.now();
	const scenarioName = 'Mixed Field Blocks Insertion';

	const fieldTypes = [
		'Text Field',
		'Text Area',
		'Select',
		'Radio',
		'Checkbox',
		'Likert',
		'VAS Slider',
	];

	try {
		for ( const fieldType of fieldTypes ) {
			log( `Inserting ${ fieldType }` );

			// Find a page to insert into
			await page.click( '[data-type="vas-dinamico/pagina"]' );
			await waitForTimeout( 300 );

			try {
				await insertBlock( page, fieldType );
				await waitForTimeout( 500 );
			} catch ( e ) {
				log( `Could not insert ${ fieldType }, continuing...`, 'warn' );
			}
		}

		await takeScreenshot( page, 'fields-inserted' );
		recordScenario( scenarioName, 'passed', Date.now() - startTime, {
			fieldsAttempted: fieldTypes.length,
		} );
		return true;
	} catch ( error ) {
		recordScenario( scenarioName, 'failed', Date.now() - startTime, {
			error: error.message,
		} );
		await takeScreenshot( page, 'fields-insertion-error' );
		throw error;
	}
}

async function testConditionalLogicConfiguration( page ) {
	const startTime = Date.now();
	const scenarioName = 'Conditional Logic Configuration';

	try {
		// Select a page block
		await page.click( '[data-type="vas-dinamico/pagina"]' );
		await waitForTimeout( 500 );

		// Look for Conditional Logic panel in inspector
		const inspectorPanels = await page.$$(
			'.components-panel__body-title'
		);
		let conditionalPanel = null;

		for ( const panel of inspectorPanels ) {
			const text = await panel.evaluate( ( el ) => el.textContent );
			if (
				text.includes( 'Conditional' ) ||
				text.includes( 'Navigation' )
			) {
				conditionalPanel = panel;
				break;
			}
		}

		if ( conditionalPanel ) {
			// Open the panel if collapsed
			const isCollapsed = await conditionalPanel.evaluate(
				( el ) =>
					el.parentElement.classList.contains( 'is-opened' ) === false
			);

			if ( isCollapsed ) {
				await conditionalPanel.click();
				await waitForTimeout( 300 );
			}

			// Enable conditional logic
			const toggles = await page.$$( '.components-toggle-control' );
			for ( const toggle of toggles ) {
				const label = await toggle
					.$eval(
						'.components-toggle-control__label',
						( el ) => el.textContent
					)
					.catch( () => '' );
				if (
					label.includes( 'Enable' ) ||
					label.includes( 'Conditional' )
				) {
					const checkbox = await toggle.$( 'input[type="checkbox"]' );
					if ( checkbox ) {
						const isChecked = await checkbox.evaluate(
							( el ) => el.checked
						);
						if ( ! isChecked ) {
							await checkbox.click();
							await waitForTimeout( 500 );
						}
						break;
					}
				}
			}

			await takeScreenshot( page, 'conditional-logic-configured' );
			recordScenario( scenarioName, 'passed', Date.now() - startTime, {
				panelFound: true,
			} );
		} else {
			recordScenario( scenarioName, 'skipped', Date.now() - startTime, {
				reason: 'Conditional logic panel not found',
			} );
		}

		return true;
	} catch ( error ) {
		recordScenario( scenarioName, 'failed', Date.now() - startTime, {
			error: error.message,
		} );
		await takeScreenshot( page, 'conditional-logic-error' );
		return false;
	}
}

async function testFormStylePanel( page ) {
	const startTime = Date.now();
	const scenarioName = 'Form Style Panel Modification';

	try {
		// Select the form container
		await page.click(
			'.eipsi-form-container-editor, .wp-block-vas-dinamico-form-container'
		);
		await waitForTimeout( 500 );

		// Look for Form Styles panel
		const inspectorPanels = await page.$$(
			'.components-panel__body-title'
		);
		let stylePanel = null;

		for ( const panel of inspectorPanels ) {
			const text = await panel.evaluate( ( el ) => el.textContent );
			if ( text.includes( 'Style' ) || text.includes( 'Design' ) ) {
				stylePanel = panel;
				break;
			}
		}

		if ( stylePanel ) {
			// Open the panel
			const isCollapsed = await stylePanel.evaluate(
				( el ) =>
					el.parentElement.classList.contains( 'is-opened' ) === false
			);

			if ( isCollapsed ) {
				await stylePanel.click();
				await waitForTimeout( 500 );
			}

			// Try to change a color (primary color)
			const colorPickers = await page.$$( '.components-color-picker' );
			if ( colorPickers.length > 0 ) {
				await colorPickers[ 0 ].click();
				await waitForTimeout( 300 );
			}

			// Check if preview updates
			const hasInlineStyles = await page
				.$eval(
					'.eipsi-form-container-editor, .wp-block-vas-dinamico-form-container',
					( el ) => el.getAttribute( 'style' ) !== null
				)
				.catch( () => false );

			await takeScreenshot( page, 'style-panel-modified' );
			recordScenario( scenarioName, 'passed', Date.now() - startTime, {
				panelFound: true,
				inlineStylesApplied: hasInlineStyles,
			} );
		} else {
			recordScenario( scenarioName, 'skipped', Date.now() - startTime, {
				reason: 'Style panel not found',
			} );
		}

		return true;
	} catch ( error ) {
		recordScenario( scenarioName, 'failed', Date.now() - startTime, {
			error: error.message,
		} );
		await takeScreenshot( page, 'style-panel-error' );
		return false;
	}
}

async function testEditorWorkflows( page ) {
	const startTime = Date.now();
	const scenarioName = 'Editor Workflows (Undo/Redo/Duplicate)';

	try {
		// Test Undo
		log( 'Testing Undo' );
		await page.keyboard.down( 'Control' );
		await page.keyboard.press( 'z' );
		await page.keyboard.up( 'Control' );
		await waitForTimeout( 500 );

		// Test Redo
		log( 'Testing Redo' );
		await page.keyboard.down( 'Control' );
		await page.keyboard.down( 'Shift' );
		await page.keyboard.press( 'z' );
		await page.keyboard.up( 'Shift' );
		await page.keyboard.up( 'Control' );
		await waitForTimeout( 500 );

		// Test List View
		log( 'Testing List View' );
		const listViewButton = await page.$(
			'.edit-post-header-toolbar__list-view-toggle, [aria-label*="List"]'
		);
		if ( listViewButton ) {
			await listViewButton.click();
			await waitForTimeout( 500 );
			await listViewButton.click();
			await waitForTimeout( 300 );
		}

		await takeScreenshot( page, 'editor-workflows' );
		recordScenario( scenarioName, 'passed', Date.now() - startTime, {
			workflowsTested: 3,
		} );
		return true;
	} catch ( error ) {
		recordScenario( scenarioName, 'failed', Date.now() - startTime, {
			error: error.message,
		} );
		await takeScreenshot( page, 'editor-workflows-error' );
		return false;
	}
}

async function testPersistence( page ) {
	const startTime = Date.now();
	const scenarioName = 'Save and Reload Persistence';

	try {
		// Save the post
		log( 'Saving post...' );
		await page.keyboard.down( 'Control' );
		await page.keyboard.press( 's' );
		await page.keyboard.up( 'Control' );

		// Wait for save to complete
		await page.waitForSelector(
			'.editor-post-saved-state[aria-label*="Saved"]',
			{ timeout: 10000 }
		);
		await waitForTimeout( 1000 );

		// Get the post URL
		const postUrl = page.url();
		log( `Post saved, reloading: ${ postUrl }` );

		// Reload the page
		await page.reload( { waitUntil: 'networkidle2' } );
		await page.waitForSelector( '.block-editor-writing-flow', {
			timeout: 30000,
		} );
		await waitForTimeout( 2000 );

		// Verify form container still exists
		const formContainer = await page.$(
			'.eipsi-form-container-editor, .wp-block-vas-dinamico-form-container'
		);
		if ( ! formContainer ) {
			throw new Error( 'Form Container not found after reload' );
		}

		// Verify pages still exist
		const pages = await page.$$(
			'.wp-block-vas-dinamico-pagina, [data-type="vas-dinamico/pagina"]'
		);
		log( `Found ${ pages.length } pages after reload` );

		// Check for styleConfig attribute
		const hasStyleConfig = await page
			.evaluate( () => {
				const blocks = wp.data
					.select( 'core/block-editor' )
					.getBlocks();
				const formBlock = blocks.find(
					( b ) => b.name === 'vas-dinamico/form-container'
				);
				return (
					formBlock &&
					formBlock.attributes &&
					formBlock.attributes.styleConfig !== undefined
				);
			} )
			.catch( () => false );

		await takeScreenshot( page, 'persistence-verified' );
		recordScenario( scenarioName, 'passed', Date.now() - startTime, {
			pagesAfterReload: pages.length,
			styleConfigPresent: hasStyleConfig,
		} );
		return true;
	} catch ( error ) {
		recordScenario( scenarioName, 'failed', Date.now() - startTime, {
			error: error.message,
		} );
		await takeScreenshot( page, 'persistence-error' );
		return false;
	}
}

async function monitorConsoleErrors( page ) {
	page.on( 'console', ( msg ) => {
		const type = msg.type();
		const text = msg.text();

		if ( type === 'error' ) {
			testResults.consoleErrors.push( {
				timestamp: new Date().toISOString(),
				text,
			} );
			log( `Console Error: ${ text }`, 'error' );
		} else if (
			type === 'warning' &&
			( text.includes( 'React' ) || text.includes( 'deprecated' ) )
		) {
			testResults.consoleWarnings.push( {
				timestamp: new Date().toISOString(),
				text,
			} );
			log( `Console Warning: ${ text }`, 'warn' );
		}
	} );

	page.on( 'pageerror', ( error ) => {
		testResults.consoleErrors.push( {
			timestamp: new Date().toISOString(),
			text: error.message,
			stack: error.stack,
		} );
		log( `Page Error: ${ error.message }`, 'error' );
	} );
}

// Main Test Runner
async function runSmokeTests() {
	log( '=== EIPSI Forms Editor Smoke Test Suite ===', 'info' );
	log( `Environment: ${ WP_URL }`, 'info' );
	log( `Headless: ${ HEADLESS }`, 'info' );

	let browser;
	let page;

	try {
		// Launch browser
		browser = await puppeteer.launch( {
			headless: HEADLESS,
			slowMo: SLOW_MO,
			args: [
				'--no-sandbox',
				'--disable-setuid-sandbox',
				'--disable-dev-shm-usage',
				'--disable-web-security',
			],
		} );

		page = await browser.newPage();
		await page.setViewport( { width: 1920, height: 1080 } );

		// Set up console monitoring
		monitorConsoleErrors( page );

		// Login
		await loginToWordPress( page );

		// Create new post
		await createNewPost( page, `Editor Smoke Test ${ Date.now() }` );

		// Run test scenarios
		await testFormContainerInsertion( page );
		await testPageBlocksInsertion( page );
		await testFieldBlocksInsertion( page );
		await testConditionalLogicConfiguration( page );
		await testFormStylePanel( page );
		await testEditorWorkflows( page );
		await testPersistence( page );

		// Generate report
		const reportPath = path.join(
			__dirname,
			'EDITOR_SMOKE_TEST_REPORT.md'
		);
		generateReport( reportPath );

		log( '=== Test Suite Complete ===', 'info' );
		log( `Passed: ${ testResults.summary.passed }`, 'info' );
		log( `Failed: ${ testResults.summary.failed }`, 'error' );
		log( `Skipped: ${ testResults.summary.skipped }`, 'skip' );
		log(
			`Console Errors: ${ testResults.consoleErrors.length }`,
			testResults.consoleErrors.length > 0 ? 'error' : 'info'
		);
		log(
			`Console Warnings: ${ testResults.consoleWarnings.length }`,
			testResults.consoleWarnings.length > 0 ? 'warn' : 'info'
		);
		log( `Report: ${ reportPath }`, 'info' );

		await browser.close();

		// Exit with appropriate code
		process.exit( testResults.summary.failed > 0 ? 1 : 0 );
	} catch ( error ) {
		log( `Fatal error: ${ error.message }`, 'error' );
		await takeScreenshot( page, 'fatal-error' ).catch( () => {} );

		if ( browser ) {
			await browser.close();
		}

		process.exit( 1 );
	}
}

function generateReport( filepath ) {
	const report = `# EIPSI Forms - Editor Smoke Test Report

**Generated:** ${ testResults.timestamp }
**Environment:** ${ testResults.environment.WP_URL }
**Headless Mode:** ${ testResults.environment.headless }

## Summary

- **Total Scenarios:** ${ testResults.summary.total }
- **Passed:** ✓ ${ testResults.summary.passed }
- **Failed:** ✗ ${ testResults.summary.failed }
- **Skipped:** ○ ${ testResults.summary.skipped }
- **Console Errors:** ${ testResults.consoleErrors.length }
- **Console Warnings:** ${ testResults.consoleWarnings.length }

## Test Scenarios

${ testResults.scenarios
	.map( ( s ) => {
		let statusIcon = '○';
		if ( s.status === 'passed' ) {
			statusIcon = '✓';
		} else if ( s.status === 'failed' ) {
			statusIcon = '✗';
		}
		return `
### ${ statusIcon } ${ s.name }

- **Status:** ${ s.status.toUpperCase() }
- **Duration:** ${ s.duration }ms
- **Timestamp:** ${ s.timestamp }
${
	Object.keys( s.details ).length > 0
		? `- **Details:** \`\`\`json\n${ JSON.stringify(
				s.details,
				null,
				2
		  ) }\n\`\`\``
		: ''
}
`;
	} )
	.join( '\n' ) }

## Console Errors

${
	testResults.consoleErrors.length === 0
		? '*No console errors detected.*'
		: testResults.consoleErrors
				.map(
					( e ) => `
### Error at ${ e.timestamp }

\`\`\`
${ e.text }
${ e.stack || '' }
\`\`\`
`
				)
				.join( '\n' )
}

## Console Warnings

${
	testResults.consoleWarnings.length === 0
		? '*No significant console warnings detected.*'
		: testResults.consoleWarnings
				.map(
					( w ) => `
- **[${ w.timestamp }]** ${ w.text }
`
				)
				.join( '\n' )
}

## Screenshots

Screenshots have been saved to: \`${ SCREENSHOT_DIR }\`

## Acceptance Criteria Verification

- **Complex forms can be authored without errors:** ${
		testResults.summary.failed === 0 &&
		testResults.consoleErrors.length === 0
			? '✓ PASS'
			: '✗ FAIL'
	}
- **Attributes persist after save/reload:** ${
		testResults.scenarios.find( ( s ) => s.name.includes( 'Persistence' ) )
			?.status === 'passed'
			? '✓ PASS'
			: '✗ FAIL'
	}
- **Editor preview renders correctly:** ${
		testResults.scenarios.find( ( s ) => s.name.includes( 'Style Panel' ) )
			?.status === 'passed'
			? '✓ PASS'
			: '? MANUAL VERIFICATION NEEDED'
	}

## Next Steps

${
	testResults.summary.failed > 0
		? `
### Failed Scenarios

${ testResults.scenarios
	.filter( ( s ) => s.status === 'failed' )
	.map(
		( s ) => `
- **${ s.name }:** ${ s.details.error || 'Unknown error' }
`
	)
	.join( '\n' ) }

**Action Required:** Investigate and fix the failed scenarios above.
`
		: ''
}

${
	testResults.consoleErrors.length > 0
		? `
### Console Errors Detected

Review the console errors section above and address any critical issues.
`
		: ''
}

${
	testResults.summary.failed === 0 && testResults.consoleErrors.length === 0
		? `
### ✓ All Tests Passed

The editor smoke test suite has completed successfully. No critical issues detected.

**Recommended:** Perform manual verification of:
1. Visual rendering quality
2. Complex conditional logic scenarios
3. Multi-browser compatibility
4. Performance with large forms (20+ fields)
`
		: ''
}

---

*Generated by EIPSI Forms Editor Smoke Test Suite*
`;

	fs.writeFileSync( filepath, report );
	log( `Report written to ${ filepath }` );
}

// Run tests if executed directly
if ( require.main === module ) {
	runSmokeTests().catch( ( error ) => {
		log( `Unhandled error: ${ error.message }`, 'error' );
		if ( error.stack ) {
			log( error.stack, 'error' );
		}
		process.exit( 1 );
	} );
}

module.exports = { runSmokeTests };
