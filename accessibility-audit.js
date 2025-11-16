/**
 * EIPSI Forms - WCAG 2.1 AA Accessibility Audit Script
 *
 * This script performs automated accessibility checks on the EIPSI Forms plugin.
 * It validates keyboard navigation, ARIA attributes, focus management, and more.
 *
 * Usage: node accessibility-audit.js
 *
 * @version 1.0.0
 * @package
 */

/* eslint-disable no-console, jsdoc/require-param-type, no-nested-ternary */

const fs = require( 'fs' );
const path = require( 'path' );

// ANSI color codes for console output
const colors = {
	reset: '\x1b[0m',
	bright: '\x1b[1m',
	red: '\x1b[31m',
	green: '\x1b[32m',
	yellow: '\x1b[33m',
	blue: '\x1b[34m',
	cyan: '\x1b[36m',
};

// Test results tracking
const results = {
	total: 0,
	passed: 0,
	failed: 0,
	warnings: 0,
	details: [],
};

/**
 * Log test result
 * @param category
 * @param test
 * @param status
 * @param message
 * @param severity
 */
function logTest( category, test, status, message, severity = 'error' ) {
	results.total++;

	const statusSymbol =
		status === 'pass' ? '✓' : status === 'warning' ? '⚠' : '✗';
	const statusColor =
		status === 'pass'
			? colors.green
			: status === 'warning'
			? colors.yellow
			: colors.red;

	if ( status === 'pass' ) {
		results.passed++;
	} else if ( status === 'warning' ) {
		results.warnings++;
	} else {
		results.failed++;
	}

	console.log(
		`${ statusColor }${ statusSymbol }${ colors.reset } ${ category } - ${ test }`
	);
	if ( message ) {
		console.log( `  ${ colors.cyan }→${ colors.reset } ${ message }` );
	}

	results.details.push( {
		category,
		test,
		status,
		message,
		severity,
	} );
}

/**
 * Check if file contains pattern
 * @param filePath
 * @param pattern
 */
function fileContains( filePath, pattern ) {
	try {
		const content = fs.readFileSync( filePath, 'utf8' );
		const regex = new RegExp( pattern, 'gi' );
		const matches = content.match( regex );
		return {
			found: matches !== null,
			count: matches ? matches.length : 0,
			content,
		};
	} catch ( error ) {
		return { found: false, count: 0, error: error.message };
	}
}

/**
 * Check ARIA attributes in block save.js files
 */
function checkBlockARIA() {
	console.log(
		`\n${ colors.bright }=== ARIA Attributes in Block Markup ===${ colors.reset }\n`
	);

	const blockFiles = [
		'src/blocks/vas-slider/save.js',
		'src/blocks/campo-likert/save.js',
		'src/blocks/campo-radio/save.js',
		'src/blocks/campo-multiple/save.js',
		'src/blocks/campo-select/save.js',
		'src/blocks/campo-texto/save.js',
		'src/blocks/campo-textarea/save.js',
	];

	blockFiles.forEach( ( file ) => {
		const fullPath = path.join( __dirname, file );
		const blockName = path.basename( path.dirname( file ) );

		// Check for aria-live on error messages
		const ariaLive = fileContains(
			fullPath,
			'aria-live=["\'](polite|assertive)["\']'
		);
		if ( ariaLive.found ) {
			logTest(
				'ARIA',
				`${ blockName }: Error message has aria-live`,
				'pass',
				`Found ${ ariaLive.count } instance(s)`
			);
		} else {
			logTest(
				'ARIA',
				`${ blockName }: Error message has aria-live`,
				'fail',
				'aria-live attribute missing on error containers',
				'critical'
			);
		}

		// Check for proper label associations
		const htmlFor = fileContains( fullPath, 'htmlFor=' );
		if ( htmlFor.found ) {
			logTest(
				'ARIA',
				`${ blockName }: Labels use htmlFor`,
				'pass',
				`Found ${ htmlFor.count } label association(s)`
			);
		} else {
			logTest(
				'ARIA',
				`${ blockName }: Labels use htmlFor`,
				'warning',
				'No explicit label associations found',
				'moderate'
			);
		}

		// Check for required attribute
		const required = fileContains( fullPath, 'required=\\{' );
		if ( required.found ) {
			logTest(
				'ARIA',
				`${ blockName }: Required attribute implemented`,
				'pass',
				'Dynamic required handling found'
			);
		} else {
			logTest(
				'ARIA',
				`${ blockName }: Required attribute implemented`,
				'warning',
				'No required attribute found',
				'low'
			);
		}
	} );

	// Special checks for VAS slider
	const vasPath = path.join( __dirname, 'src/blocks/vas-slider/save.js' );
	const vasARIA = fileContains( vasPath, 'aria-value(min|max|now)' );
	if ( vasARIA.found ) {
		logTest(
			'ARIA',
			'VAS Slider: ARIA value attributes',
			'pass',
			`Found ${ vasARIA.count } ARIA value attribute(s)`
		);
	} else {
		logTest(
			'ARIA',
			'VAS Slider: ARIA value attributes',
			'fail',
			'Missing aria-valuemin/max/now on range input',
			'critical'
		);
	}

	// Check for aria-labelledby
	const vasLabelledBy = fileContains( vasPath, 'aria-labelledby' );
	if ( vasLabelledBy.found ) {
		logTest(
			'ARIA',
			'VAS Slider: aria-labelledby',
			'pass',
			'Slider properly labeled'
		);
	} else {
		logTest(
			'ARIA',
			'VAS Slider: aria-labelledby',
			'warning',
			'Consider adding aria-labelledby for better SR support',
			'moderate'
		);
	}

	// Check for fieldset/legend in radio fields
	const radioPath = path.join( __dirname, 'src/blocks/campo-radio/save.js' );
	const fieldset = fileContains( radioPath, '<fieldset>' );
	if ( fieldset.found ) {
		logTest(
			'ARIA',
			'Radio Field: Fieldset/Legend structure',
			'pass',
			'Proper semantic grouping with fieldset'
		);
	} else {
		logTest(
			'ARIA',
			'Radio Field: Fieldset/Legend structure',
			'fail',
			'Radio buttons should use fieldset/legend',
			'critical'
		);
	}
}

/**
 * Check keyboard handling in JavaScript
 */
function checkKeyboardHandling() {
	console.log(
		`\n${ colors.bright }=== Keyboard Handling ===${ colors.reset }\n`
	);

	const jsPath = path.join( __dirname, 'assets/js/eipsi-forms.js' );

	// Check for keyboard event listeners
	const keydown = fileContains(
		jsPath,
		'addEventListener\\s*\\(\\s*["\']keydown["\']'
	);
	if ( keydown.found ) {
		logTest(
			'Keyboard',
			'Keydown event listeners',
			'pass',
			`Found ${ keydown.count } keydown handler(s)`
		);
	} else {
		logTest(
			'Keyboard',
			'Keydown event listeners',
			'fail',
			'No keydown handlers found',
			'critical'
		);
	}

	// Check for arrow key handling
	const arrowKeys = fileContains(
		jsPath,
		'ArrowLeft|ArrowRight|ArrowUp|ArrowDown'
	);
	if ( arrowKeys.found ) {
		logTest(
			'Keyboard',
			'Arrow key support',
			'pass',
			'Arrow key navigation implemented'
		);
	} else {
		logTest(
			'Keyboard',
			'Arrow key support',
			'fail',
			'No arrow key handling found',
			'high'
		);
	}

	// Check for Home/End key support
	const homeEnd = fileContains( jsPath, 'Home|End' );
	if ( homeEnd.found ) {
		logTest(
			'Keyboard',
			'Home/End key support',
			'pass',
			'Home/End keys supported'
		);
	} else {
		logTest(
			'Keyboard',
			'Home/End key support',
			'warning',
			'Consider adding Home/End key support',
			'low'
		);
	}

	// Check for Enter/Space handling
	const enterSpace = fileContains(
		jsPath,
		'Enter|Space|\\s+13\\s+|\\s+32\\s+'
	);
	if ( enterSpace.found ) {
		logTest(
			'Keyboard',
			'Enter/Space key support',
			'pass',
			'Enter/Space handling found'
		);
	} else {
		logTest(
			'Keyboard',
			'Enter/Space key support',
			'warning',
			'Verify Enter/Space work with native controls',
			'low'
		);
	}

	// Check for Escape key handling
	const escape = fileContains( jsPath, 'Escape|Esc|\\s+27\\s+' );
	if ( escape.found ) {
		logTest(
			'Keyboard',
			'Escape key support',
			'pass',
			'Escape key handling found'
		);
	} else {
		logTest(
			'Keyboard',
			'Escape key support',
			'warning',
			'Consider Escape key for dismissing messages',
			'low'
		);
	}

	// Check for focus management
	const focus = fileContains( jsPath, '\\.focus\\(\\)' );
	if ( focus.found ) {
		logTest(
			'Keyboard',
			'Programmatic focus management',
			'pass',
			`Found ${ focus.count } focus() call(s)`
		);
	} else {
		logTest(
			'Keyboard',
			'Programmatic focus management',
			'warning',
			'No explicit focus management detected',
			'moderate'
		);
	}

	// Check for aria-hidden updates
	const ariaHidden = fileContains( jsPath, 'aria-hidden' );
	if ( ariaHidden.found ) {
		logTest(
			'Keyboard',
			'ARIA-hidden management',
			'pass',
			'Dynamic aria-hidden updates found'
		);
	} else {
		logTest(
			'Keyboard',
			'ARIA-hidden management',
			'fail',
			'Hidden pages should have aria-hidden="true"',
			'high'
		);
	}

	// Check for inert attribute
	const inert = fileContains( jsPath, 'inert' );
	if ( inert.found ) {
		logTest(
			'Keyboard',
			'Inert attribute for hidden pages',
			'pass',
			'Inert attribute used for hidden pages'
		);
	} else {
		logTest(
			'Keyboard',
			'Inert attribute for hidden pages',
			'warning',
			'Consider using inert for better focus management',
			'low'
		);
	}
}

/**
 * Check CSS focus indicators
 */
function checkFocusIndicators() {
	console.log(
		`\n${ colors.bright }=== Focus Indicators ===${ colors.reset }\n`
	);

	const cssPath = path.join( __dirname, 'assets/css/eipsi-forms.css' );

	// Check for :focus-visible
	const focusVisible = fileContains( cssPath, ':focus-visible' );
	if ( focusVisible.found ) {
		logTest(
			'Focus',
			':focus-visible pseudo-class',
			'pass',
			`Found ${ focusVisible.count } instance(s)`
		);
	} else {
		logTest(
			'Focus',
			':focus-visible pseudo-class',
			'fail',
			':focus-visible missing - use instead of :focus',
			'critical'
		);
	}

	// Check for outline styles
	const outline = fileContains( cssPath, 'outline:\\s*\\d+px\\s+solid' );
	if ( outline.found ) {
		logTest(
			'Focus',
			'Outline styles defined',
			'pass',
			'Explicit outline styles found'
		);
	} else {
		logTest(
			'Focus',
			'Outline styles defined',
			'warning',
			'Verify outline visibility',
			'moderate'
		);
	}

	// Check for outline-offset
	const outlineOffset = fileContains( cssPath, 'outline-offset' );
	if ( outlineOffset.found ) {
		logTest(
			'Focus',
			'Outline offset for spacing',
			'pass',
			'outline-offset improves visibility'
		);
	} else {
		logTest(
			'Focus',
			'Outline offset for spacing',
			'warning',
			'Consider outline-offset for better visibility',
			'low'
		);
	}

	// Check for mobile-enhanced focus
	const mobileFocus = fileContains(
		cssPath,
		'@media\\s*\\(max-width:\\s*768px\\)[^}]*:focus-visible'
	);
	if ( mobileFocus.found ) {
		logTest(
			'Focus',
			'Enhanced mobile focus indicators',
			'pass',
			'Mobile devices get enhanced focus styles'
		);
	} else {
		logTest(
			'Focus',
			'Enhanced mobile focus indicators',
			'warning',
			'Consider thicker focus outlines on mobile',
			'moderate'
		);
	}

	// Check that outline is never set to none without replacement
	const outlineNone = fileContains( cssPath, 'outline:\\s*none' );
	if ( outlineNone.found ) {
		logTest(
			'Focus',
			'Outline:none usage',
			'warning',
			`Found ${ outlineNone.count } outline:none - verify custom focus styles`,
			'high'
		);
	} else {
		logTest(
			'Focus',
			'Outline:none usage',
			'pass',
			'No outline:none found'
		);
	}
}

/**
 * Check reduced motion support
 */
function checkReducedMotion() {
	console.log(
		`\n${ colors.bright }=== Reduced Motion Support ===${ colors.reset }\n`
	);

	const cssPath = path.join( __dirname, 'assets/css/eipsi-forms.css' );
	const jsPath = path.join( __dirname, 'assets/js/eipsi-forms.js' );

	// CSS reduced motion query
	const cssReducedMotion = fileContains(
		cssPath,
		'@media\\s*\\(prefers-reduced-motion:\\s*reduce\\)'
	);
	if ( cssReducedMotion.found ) {
		logTest(
			'Reduced Motion',
			'CSS @media query',
			'pass',
			'prefers-reduced-motion media query found'
		);
	} else {
		logTest(
			'Reduced Motion',
			'CSS @media query',
			'fail',
			'@media (prefers-reduced-motion: reduce) missing',
			'high'
		);
	}

	// JS reduced motion detection
	const jsReducedMotion = fileContains( jsPath, 'prefers-reduced-motion' );
	if ( jsReducedMotion.found ) {
		logTest(
			'Reduced Motion',
			'JavaScript detection',
			'pass',
			'JS detects prefers-reduced-motion'
		);
	} else {
		logTest(
			'Reduced Motion',
			'JavaScript detection',
			'fail',
			'JS should detect prefers-reduced-motion',
			'high'
		);
	}

	// Check animation-duration handling
	const animationDuration = fileContains(
		cssPath,
		'animation-duration:\\s*0\\.01ms'
	);
	if ( animationDuration.found ) {
		logTest(
			'Reduced Motion',
			'Animation duration override',
			'pass',
			'Animations shortened for reduced motion'
		);
	} else {
		logTest(
			'Reduced Motion',
			'Animation duration override',
			'warning',
			'Verify animations respect reduced motion',
			'moderate'
		);
	}

	// Check transition-duration handling
	const transitionDuration = fileContains(
		cssPath,
		'transition-duration:\\s*0\\.01ms'
	);
	if ( transitionDuration.found ) {
		logTest(
			'Reduced Motion',
			'Transition duration override',
			'pass',
			'Transitions shortened for reduced motion'
		);
	} else {
		logTest(
			'Reduced Motion',
			'Transition duration override',
			'warning',
			'Verify transitions respect reduced motion',
			'moderate'
		);
	}

	// Check for confetti conditional
	const confetti = fileContains(
		jsPath,
		'prefersReducedMotion.*confetti|confetti.*prefersReducedMotion'
	);
	if ( confetti.found ) {
		logTest(
			'Reduced Motion',
			'Confetti animation conditional',
			'pass',
			'Confetti respects reduced motion'
		);
	} else {
		logTest(
			'Reduced Motion',
			'Confetti animation conditional',
			'warning',
			'Verify decorative animations are conditional',
			'moderate'
		);
	}
}

/**
 * Check high contrast mode support
 */
function checkHighContrast() {
	console.log(
		`\n${ colors.bright }=== High Contrast Mode Support ===${ colors.reset }\n`
	);

	const cssPath = path.join( __dirname, 'assets/css/eipsi-forms.css' );

	// Check for prefers-contrast media query
	const prefersContrast = fileContains(
		cssPath,
		'@media\\s*\\(prefers-contrast:\\s*high\\)'
	);
	if ( prefersContrast.found ) {
		logTest(
			'High Contrast',
			'prefers-contrast:high media query',
			'pass',
			'High contrast mode detected in CSS'
		);
	} else {
		logTest(
			'High Contrast',
			'prefers-contrast:high media query',
			'warning',
			'Consider @media (prefers-contrast: high)',
			'moderate'
		);
	}

	// Check for forced-colors media query (Windows High Contrast Mode)
	const forcedColors = fileContains(
		cssPath,
		'@media\\s*\\(forced-colors:\\s*active\\)'
	);
	if ( forcedColors.found ) {
		logTest(
			'High Contrast',
			'forced-colors media query',
			'pass',
			'Windows HCM supported'
		);
	} else {
		logTest(
			'High Contrast',
			'forced-colors media query',
			'warning',
			'Add @media (forced-colors: active) for Windows HCM',
			'moderate'
		);
	}

	// Check for border enhancements in high contrast
	const highContrastBorders = fileContains(
		cssPath,
		'prefers-contrast:[^}]*border-width'
	);
	if ( highContrastBorders.found ) {
		logTest(
			'High Contrast',
			'Border width enhancements',
			'pass',
			'Borders enhanced in high contrast mode'
		);
	} else {
		logTest(
			'High Contrast',
			'Border width enhancements',
			'warning',
			'Consider increasing border width in high contrast',
			'low'
		);
	}
}

/**
 * Check semantic HTML structure
 */
function checkSemanticHTML() {
	console.log(
		`\n${ colors.bright }=== Semantic HTML Structure ===${ colors.reset }\n`
	);

	const blockFiles = [
		'src/blocks/campo-radio/save.js',
		'src/blocks/campo-multiple/save.js',
	];

	// Check for fieldset/legend
	blockFiles.forEach( ( file ) => {
		const fullPath = path.join( __dirname, file );
		const blockName = path.basename( path.dirname( file ) );

		const fieldset = fileContains( fullPath, '<fieldset>' );
		const legend = fileContains( fullPath, '<legend' );

		if ( fieldset.found && legend.found ) {
			logTest(
				'Semantic HTML',
				`${ blockName }: Fieldset + Legend`,
				'pass',
				'Proper semantic grouping'
			);
		} else if ( fieldset.found ) {
			logTest(
				'Semantic HTML',
				`${ blockName }: Fieldset + Legend`,
				'warning',
				'Fieldset without legend',
				'moderate'
			);
		} else {
			logTest(
				'Semantic HTML',
				`${ blockName }: Fieldset + Legend`,
				'fail',
				'Radio/checkbox groups need fieldset/legend',
				'high'
			);
		}
	} );

	// Check for proper label elements
	const allBlocks = [
		'src/blocks/vas-slider/save.js',
		'src/blocks/campo-likert/save.js',
		'src/blocks/campo-texto/save.js',
		'src/blocks/campo-textarea/save.js',
		'src/blocks/campo-select/save.js',
	];

	allBlocks.forEach( ( file ) => {
		const fullPath = path.join( __dirname, file );
		const blockName = path.basename( path.dirname( file ) );

		const label = fileContains( fullPath, '<label' );
		if ( label.found ) {
			logTest(
				'Semantic HTML',
				`${ blockName }: Label element`,
				'pass',
				'Label element present'
			);
		} else {
			logTest(
				'Semantic HTML',
				`${ blockName }: Label element`,
				'warning',
				'Verify label element exists',
				'moderate'
			);
		}
	} );

	// Check for role attributes where needed
	const formContainerPath = path.join(
		__dirname,
		'src/blocks/form-container/save.js'
	);
	const formRole = fileContains(
		formContainerPath,
		'role=["\'](form|region)["\']'
	);
	if ( formRole.found ) {
		logTest(
			'Semantic HTML',
			'Form container: role attribute',
			'pass',
			'Explicit role defined'
		);
	} else {
		logTest(
			'Semantic HTML',
			'Form container: role attribute',
			'warning',
			'Consider role="region" for form sections',
			'low'
		);
	}
}

/**
 * Check screen reader support
 */
function checkScreenReaderSupport() {
	console.log(
		`\n${ colors.bright }=== Screen Reader Support ===${ colors.reset }\n`
	);

	const cssPath = path.join( __dirname, 'assets/css/eipsi-forms.css' );

	// Check for .sr-only or .visually-hidden
	const srOnly = fileContains( cssPath, '\\.sr-only|\\.visually-hidden' );
	if ( srOnly.found ) {
		logTest(
			'Screen Reader',
			'SR-only text utility class',
			'pass',
			'Screen reader-only text class available'
		);
	} else {
		logTest(
			'Screen Reader',
			'SR-only text utility class',
			'warning',
			'Add .sr-only class for SR-only content',
			'moderate'
		);
	}

	// Check for skip links
	const skipLink = fileContains( cssPath, '\\.skip-link' );
	if ( skipLink.found ) {
		logTest(
			'Screen Reader',
			'Skip link styles',
			'pass',
			'Skip link functionality present'
		);
	} else {
		logTest(
			'Screen Reader',
			'Skip link styles',
			'warning',
			'Consider adding skip links for long forms',
			'low'
		);
	}

	// Check for aria-live regions
	const jsPath = path.join( __dirname, 'assets/js/eipsi-forms.js' );
	const ariaLive = fileContains( jsPath, 'aria-live' );
	if ( ariaLive.found ) {
		logTest(
			'Screen Reader',
			'ARIA live regions',
			'pass',
			`Found ${ ariaLive.count } aria-live implementation(s)`
		);
	} else {
		logTest(
			'Screen Reader',
			'ARIA live regions',
			'fail',
			'Dynamic content changes need aria-live',
			'high'
		);
	}

	// Check for role="status" or role="alert"
	const roleStatus = fileContains(
		jsPath,
		'role["\'],\\s*["\'](?:status|alert)["\']'
	);
	if ( roleStatus.found ) {
		logTest(
			'Screen Reader',
			'Status/Alert roles',
			'pass',
			'Status/alert roles used appropriately'
		);
	} else {
		logTest(
			'Screen Reader',
			'Status/Alert roles',
			'warning',
			'Consider role="status" for announcements',
			'moderate'
		);
	}

	// Check for aria-describedby
	const ariaDescribedby = fileContains( jsPath, 'aria-describedby' );
	if ( ariaDescribedby.found ) {
		logTest(
			'Screen Reader',
			'aria-describedby for errors',
			'pass',
			'Error messages linked with aria-describedby'
		);
	} else {
		logTest(
			'Screen Reader',
			'aria-describedby for errors',
			'warning',
			'Link error messages to inputs with aria-describedby',
			'high'
		);
	}

	// Check for page change announcements
	const pageChange = fileContains(
		jsPath,
		'updatePageAriaAttributes|aria-hidden.*page'
	);
	if ( pageChange.found ) {
		logTest(
			'Screen Reader',
			'Page navigation announcements',
			'pass',
			'Page changes update ARIA attributes'
		);
	} else {
		logTest(
			'Screen Reader',
			'Page navigation announcements',
			'warning',
			'Verify page changes are announced',
			'moderate'
		);
	}
}

/**
 * Check touch target sizes
 */
function checkTouchTargets() {
	console.log(
		`\n${ colors.bright }=== Touch Target Sizes (WCAG 2.5.5) ===${ colors.reset }\n`
	);

	const cssPath = path.join( __dirname, 'assets/css/eipsi-forms.css' );
	const content = fs.readFileSync( cssPath, 'utf8' );

	// Check button padding (should result in ≥44x44px)
	const buttonPadding = content.match(
		/\.eipsi-.*-button[^{]*\{[^}]*padding:\s*([^;]+)/
	);
	if ( buttonPadding ) {
		logTest(
			'Touch Targets',
			'Button padding',
			'pass',
			`Padding: ${ buttonPadding[ 1 ] }`
		);
	} else {
		logTest(
			'Touch Targets',
			'Button padding',
			'warning',
			'Verify button min-height ≥44px',
			'high'
		);
	}

	// Check radio/checkbox dimensions
	const radioSize = content.match(
		/input\[type=["']radio["']\][^{]*\{[^}]*width:\s*(\d+)px[^}]*height:\s*(\d+)px/s
	);
	if ( radioSize ) {
		const width = parseInt( radioSize[ 1 ] );
		const height = parseInt( radioSize[ 2 ] );
		if ( width >= 20 && height >= 20 ) {
			logTest(
				'Touch Targets',
				'Radio button size',
				'pass',
				`${ width }x${ height }px - clickable area includes padding`
			);
		} else {
			logTest(
				'Touch Targets',
				'Radio button size',
				'fail',
				`${ width }x${ height }px - too small without clickable parent`,
				'high'
			);
		}
	} else {
		logTest(
			'Touch Targets',
			'Radio button size',
			'warning',
			'Verify radio buttons have adequate touch targets',
			'high'
		);
	}

	// Check if radio/checkbox list items provide clickable area
	const listItemPadding = content.match(
		/\.radio-list\s+li[^{]*\{[^}]*padding:\s*([^;]+)/
	);
	if ( listItemPadding ) {
		logTest(
			'Touch Targets',
			'Radio list item padding',
			'pass',
			`Parent padding: ${ listItemPadding[ 1 ] } - increases touch target`
		);
	} else {
		logTest(
			'Touch Targets',
			'Radio list item padding',
			'warning',
			'Verify list items provide adequate touch targets',
			'moderate'
		);
	}

	// Check Likert scale touch targets
	const likertPadding = content.match(
		/\.likert-item[^{]*\{[^}]*padding:\s*([^;]+)/
	);
	if ( likertPadding ) {
		logTest(
			'Touch Targets',
			'Likert scale item padding',
			'pass',
			`Padding: ${ likertPadding[ 1 ] }`
		);
	} else {
		logTest(
			'Touch Targets',
			'Likert scale item padding',
			'warning',
			'Verify Likert items ≥44x44px',
			'high'
		);
	}

	// Check mobile touch target enhancements
	const mobileTouchTargets = content.match(
		/@media\s*\(max-width:\s*768px\)[^}]*\.(radio-list|checkbox-list|likert-item)[^}]*padding/s
	);
	if ( mobileTouchTargets ) {
		logTest(
			'Touch Targets',
			'Mobile touch target enhancements',
			'pass',
			'Mobile devices get optimized touch targets'
		);
	} else {
		logTest(
			'Touch Targets',
			'Mobile touch target enhancements',
			'warning',
			'Consider larger touch targets on mobile',
			'moderate'
		);
	}
}

/**
 * Check responsive design
 */
function checkResponsiveDesign() {
	console.log(
		`\n${ colors.bright }=== Responsive Design ===${ colors.reset }\n`
	);

	const cssPath = path.join( __dirname, 'assets/css/eipsi-forms.css' );

	// Check for mobile breakpoints
	const mobile = fileContains(
		cssPath,
		'@media\\s*\\(max-width:\\s*(320|375|480|768)px\\)'
	);
	if ( mobile.found && mobile.count >= 4 ) {
		logTest(
			'Responsive',
			'Mobile breakpoints',
			'pass',
			`${ mobile.count } mobile breakpoints found`
		);
	} else {
		logTest(
			'Responsive',
			'Mobile breakpoints',
			'warning',
			'Add more mobile breakpoints (320px, 375px, 480px, 768px)',
			'moderate'
		);
	}

	// Check for viewport meta tag (usually in PHP)
	logTest(
		'Responsive',
		'Viewport meta tag',
		'warning',
		'Manually verify <meta name="viewport"> in theme',
		'low'
	);

	// Check for text size on mobile (should be ≥16px to prevent zoom)
	const mobileTextSize = fileContains(
		cssPath,
		'max-width:[^}]*font-size:\\s*(?:16px|1rem|1em|\\d+\\.\\d+rem)'
	);
	if ( mobileTextSize.found ) {
		logTest(
			'Responsive',
			'Mobile text size ≥16px',
			'pass',
			'Text meets minimum size to prevent zoom'
		);
	} else {
		logTest(
			'Responsive',
			'Mobile text size ≥16px',
			'warning',
			'Verify input text ≥16px on mobile',
			'high'
		);
	}

	// Check for horizontal scroll prevention
	const maxWidth = fileContains( cssPath, 'max-width:\\s*100%' );
	if ( maxWidth.found ) {
		logTest(
			'Responsive',
			'Max-width constraints',
			'pass',
			'Elements constrained to prevent horizontal scroll'
		);
	} else {
		logTest(
			'Responsive',
			'Max-width constraints',
			'warning',
			'Verify no horizontal scroll on small screens',
			'moderate'
		);
	}
}

/**
 * Check color contrast (manual verification needed)
 */
function checkColorContrast() {
	console.log(
		`\n${ colors.bright }=== Color Contrast (Manual Verification) ===${ colors.reset }\n`
	);

	logTest(
		'Color Contrast',
		'Primary color contrast',
		'warning',
		'Run wcag-contrast-validation.js for automated testing',
		'info'
	);
	logTest(
		'Color Contrast',
		'Text on backgrounds',
		'warning',
		'Verify all text meets WCAG AA (4.5:1 minimum)',
		'info'
	);
	logTest(
		'Color Contrast',
		'UI component contrast',
		'warning',
		'Verify borders/icons meet WCAG AA (3:1 minimum)',
		'info'
	);
	logTest(
		'Color Contrast',
		'Focus indicator contrast',
		'warning',
		'Verify focus outlines meet 3:1 contrast',
		'info'
	);
	logTest(
		'Color Contrast',
		'Error message contrast',
		'warning',
		'Verify error colors meet accessibility standards',
		'info'
	);
}

/**
 * Generate summary report
 */
function generateSummary() {
	console.log( `\n${ colors.bright }${ '='.repeat( 60 ) }${ colors.reset }` );
	console.log(
		`${ colors.bright }ACCESSIBILITY AUDIT SUMMARY${ colors.reset }`
	);
	console.log( `${ colors.bright }${ '='.repeat( 60 ) }${ colors.reset }\n` );

	const passRate = ( ( results.passed / results.total ) * 100 ).toFixed( 1 );
	const passColor =
		passRate >= 90
			? colors.green
			: passRate >= 70
			? colors.yellow
			: colors.red;

	console.log( `Total Tests:     ${ results.total }` );
	console.log(
		`${ colors.green }Passed:${ colors.reset }          ${ results.passed } (${ passColor }${ passRate }%${ colors.reset })`
	);
	console.log(
		`${ colors.red }Failed:${ colors.reset }          ${ results.failed }`
	);
	console.log(
		`${ colors.yellow }Warnings:${ colors.reset }        ${ results.warnings }`
	);

	// Categorize failures by severity
	const critical = results.details.filter(
		( d ) => d.status === 'fail' && d.severity === 'critical'
	);
	const high = results.details.filter(
		( d ) => d.status === 'fail' && d.severity === 'high'
	);
	const moderate = results.details.filter(
		( d ) =>
			( d.status === 'fail' || d.status === 'warning' ) &&
			d.severity === 'moderate'
	);

	if ( critical.length > 0 ) {
		console.log(
			`\n${ colors.red }${ colors.bright }CRITICAL ISSUES (${ critical.length }):${ colors.reset }`
		);
		critical.forEach( ( item ) => {
			console.log(
				`  ${ colors.red }✗${ colors.reset } ${ item.category }: ${ item.test }`
			);
		} );
	}

	if ( high.length > 0 ) {
		console.log(
			`\n${ colors.red }HIGH PRIORITY (${ high.length }):${ colors.reset }`
		);
		high.forEach( ( item ) => {
			console.log(
				`  ${ colors.red }✗${ colors.reset } ${ item.category }: ${ item.test }`
			);
		} );
	}

	if ( moderate.length > 0 ) {
		console.log(
			`\n${ colors.yellow }MODERATE PRIORITY (${ moderate.length }):${ colors.reset }`
		);
		moderate.slice( 0, 5 ).forEach( ( item ) => {
			console.log(
				`  ${ colors.yellow }⚠${ colors.reset } ${ item.category }: ${ item.test }`
			);
		} );
		if ( moderate.length > 5 ) {
			console.log( `  ... and ${ moderate.length - 5 } more` );
		}
	}

	console.log(
		`\n${ colors.bright }${ '='.repeat( 60 ) }${ colors.reset }\n`
	);

	// Overall recommendation
	if ( results.failed === 0 && results.warnings <= 5 ) {
		console.log(
			`${ colors.green }✓ EXCELLENT${ colors.reset } - Strong accessibility foundation`
		);
	} else if ( results.failed <= 3 && results.warnings <= 10 ) {
		console.log(
			`${ colors.yellow }⚠ GOOD${ colors.reset } - Minor improvements recommended`
		);
	} else if ( results.failed <= 8 ) {
		console.log(
			`${ colors.yellow }⚠ NEEDS WORK${ colors.reset } - Address critical and high priority issues`
		);
	} else {
		console.log(
			`${ colors.red }✗ ACTION REQUIRED${ colors.reset } - Significant accessibility barriers present`
		);
	}

	console.log( `\n${ colors.cyan }Next Steps:${ colors.reset }` );
	console.log(
		`1. Review failed tests and address critical/high priority issues`
	);
	console.log(
		`2. Run manual tests with screen readers (NVDA, VoiceOver, TalkBack)`
	);
	console.log(
		`3. Test with browser DevTools accessibility audits (Lighthouse, axe)`
	);
	console.log(
		`4. Validate with real users who rely on assistive technology`
	);
	console.log(
		`5. Document remaining issues in docs/qa/QA_PHASE5_RESULTS.md\n`
	);
}

/**
 * Main execution
 */
function runAudit() {
	console.log( `${ colors.bright }${ colors.blue }` );
	console.log(
		'╔═══════════════════════════════════════════════════════════╗'
	);
	console.log(
		'║   EIPSI FORMS - WCAG 2.1 AA ACCESSIBILITY AUDIT          ║'
	);
	console.log(
		'║   Automated Static Analysis                               ║'
	);
	console.log(
		'╚═══════════════════════════════════════════════════════════╝'
	);
	console.log( colors.reset );

	checkBlockARIA();
	checkKeyboardHandling();
	checkFocusIndicators();
	checkReducedMotion();
	checkHighContrast();
	checkSemanticHTML();
	checkScreenReaderSupport();
	checkTouchTargets();
	checkResponsiveDesign();
	checkColorContrast();

	generateSummary();

	// Save results to file
	const reportPath = path.join(
		__dirname,
		'docs/qa/accessibility-audit-results.json'
	);
	const reportDir = path.dirname( reportPath );

	if ( ! fs.existsSync( reportDir ) ) {
		fs.mkdirSync( reportDir, { recursive: true } );
	}

	fs.writeFileSync( reportPath, JSON.stringify( results, null, 2 ) );
	console.log(
		`${ colors.cyan }Detailed results saved to:${ colors.reset } ${ reportPath }\n`
	);
}

// Run the audit
runAudit();
