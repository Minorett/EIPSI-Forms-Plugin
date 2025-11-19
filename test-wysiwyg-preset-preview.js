/**
 * WYSIWYG Preset Preview Validation Test
 *
 * Validates that instant preset preview works correctly in the form editor.
 * Tests CSS variable application across all form blocks and presets.
 *
 * @package
 * @version 1.0.0
 */

const fs = require( 'fs' );
const path = require( 'path' );

// Validation results
let totalTests = 0;
let passedTests = 0;
let failedTests = 0;
const results = [];

/**
 * Test result helper
 * @param description
 * @param condition
 */
function test( description, condition ) {
	totalTests++;
	const passed = condition;

	if ( passed ) {
		passedTests++;
		results.push( `✅ PASS: ${ description }` );
	} else {
		failedTests++;
		results.push( `❌ FAIL: ${ description }` );
	}

	return passed;
}

/**
 * Read file content
 * @param filePath
 */
function readFile( filePath ) {
	try {
		return fs.readFileSync( path.join( __dirname, filePath ), 'utf8' );
	} catch ( error ) {
		return null;
	}
}

/**
 * Count occurrences of a pattern in text
 * @param text
 * @param pattern
 */
function countOccurrences( text, pattern ) {
	const matches = text.match( pattern );
	return matches ? matches.length : 0;
}

console.log( '='.repeat( 70 ) );
console.log( 'WYSIWYG PRESET PREVIEW VALIDATION TEST' );
console.log( 'Testing instant preset preview in form editor' );
console.log( '='.repeat( 70 ) );
console.log( '' );

// =============================================================================
// 1. CSS VARIABLES IN EDITOR SCSS FILES
// =============================================================================

console.log( '📋 Test Group 1: CSS Variables in Editor Styles' );
console.log( '-'.repeat( 70 ) );

const editorFiles = [
	'src/blocks/form-container/editor.scss',
	'src/blocks/campo-texto/editor.scss',
	'src/blocks/campo-textarea/editor.scss',
	'src/blocks/campo-select/editor.scss',
	'src/blocks/campo-radio/editor.scss',
	'src/blocks/campo-multiple/editor.scss',
	'src/blocks/campo-likert/editor.scss',
	'src/blocks/campo-descripcion/editor.scss',
	'src/blocks/vas-slider/editor.scss',
	'src/blocks/pagina/editor.scss',
];

editorFiles.forEach( ( filePath ) => {
	const content = readFile( filePath );
	const fileName = path.basename( filePath );

	if ( ! content ) {
		test( `${ fileName }: File exists`, false );
		return;
	}

	test( `${ fileName }: File exists`, true );

	// Check for CSS variable usage
	const cssVarCount = countOccurrences( content, /var\(--eipsi-[a-z-]+/g );
	test(
		`${ fileName }: Uses CSS variables (found ${ cssVarCount })`,
		cssVarCount > 0
	);

	// Check for primary color variable
	test(
		`${ fileName }: Uses --eipsi-color-primary`,
		content.includes( '--eipsi-color-primary' )
	);

	// Check for background color variable
	test(
		`${ fileName }: Uses --eipsi-color-background`,
		content.includes( '--eipsi-color-background' ) ||
			content.includes( '--eipsi-color-background-subtle' )
	);

	// Check for typography variables
	test(
		`${ fileName }: Uses typography variables`,
		content.includes( '--eipsi-font-' ) ||
			content.includes( 'font-family: var(' )
	);

	// Check for spacing variables
	test(
		`${ fileName }: Uses spacing variables`,
		content.includes( '--eipsi-spacing-' )
	);

	// Check for border radius variables
	test(
		`${ fileName }: Uses border radius variables`,
		content.includes( '--eipsi-border-radius-' )
	);

	// Check for shadow variables
	test(
		`${ fileName }: Uses shadow variables`,
		content.includes( '--eipsi-shadow-' )
	);

	// Check for transition variables
	test(
		`${ fileName }: Uses transition variables`,
		content.includes( '--eipsi-transition-' )
	);

	// Check for hardcoded WordPress blue (should not exist)
	test(
		`${ fileName }: No hardcoded #0073aa (WordPress blue)`,
		! content.includes( '#0073aa' )
	);

	// Check for hardcoded #005177 (WordPress dark blue)
	test(
		`${ fileName }: No hardcoded #005177 (WordPress dark blue)`,
		! content.includes( '#005177' )
	);

	// Check for hardcoded #f8fbff (old light blue background)
	test(
		`${ fileName }: No hardcoded #f8fbff (old background)`,
		! content.includes( '#f8fbff' )
	);

	// Check for hardcoded #e6f3ff (old hover background)
	test(
		`${ fileName }: No hardcoded #e6f3ff (old hover background)`,
		! content.includes( '#e6f3ff' )
	);
} );

console.log( '' );

// =============================================================================
// 2. JAVASCRIPT CSS VARIABLE APPLICATION
// =============================================================================

console.log( '📋 Test Group 2: JavaScript CSS Variable Application' );
console.log( '-'.repeat( 70 ) );

const editJsPath = 'src/blocks/form-container/edit.js';
const editJsContent = readFile( editJsPath );

if ( editJsContent ) {
	test( 'edit.js: File exists', true );

	// Check for serializeToCSSVariables import
	test(
		'edit.js: Imports serializeToCSSVariables',
		editJsContent.includes( 'serializeToCSSVariables' )
	);

	// Check for CSS variables generation
	test(
		'edit.js: Generates CSS variables (cssVars)',
		editJsContent.includes( 'serializeToCSSVariables' ) &&
			editJsContent.includes( 'cssVars' )
	);

	// Check for CSS variables application to blockProps
	test(
		'edit.js: Applies CSS variables to blockProps style',
		editJsContent.includes( 'style: cssVars' ) ||
			editJsContent.includes( 'style: { ...cssVars' )
	);

	// Check for styleConfig attribute usage
	test(
		'edit.js: Uses styleConfig attribute',
		editJsContent.includes( 'styleConfig' )
	);
} else {
	test( 'edit.js: File exists', false );
}

console.log( '' );

// =============================================================================
// 3. STYLE TOKENS SYSTEM
// =============================================================================

console.log( '📋 Test Group 3: Style Tokens System' );
console.log( '-'.repeat( 70 ) );

const styleTokensPath = 'src/utils/styleTokens.js';
const styleTokensContent = readFile( styleTokensPath );

if ( styleTokensContent ) {
	test( 'styleTokens.js: File exists', true );

	// Check for serializeToCSSVariables function
	test(
		'styleTokens.js: Exports serializeToCSSVariables',
		styleTokensContent.includes( 'export function serializeToCSSVariables' )
	);

	// Check for DEFAULT_STYLE_CONFIG
	test(
		'styleTokens.js: Exports DEFAULT_STYLE_CONFIG',
		styleTokensContent.includes( 'export const DEFAULT_STYLE_CONFIG' )
	);

	// Check for comprehensive CSS variable generation
	const cssVarMatches = styleTokensContent.match( /'--eipsi-[a-z-]+'/g );
	const uniqueCssVars = new Set( cssVarMatches || [] );
	test(
		`styleTokens.js: Generates comprehensive CSS variables (${ uniqueCssVars.size } unique vars)`,
		uniqueCssVars.size >= 40
	);

	// Check for color variables
	test(
		'styleTokens.js: Generates color CSS variables',
		styleTokensContent.includes( '--eipsi-color-primary' ) &&
			styleTokensContent.includes( '--eipsi-color-background' )
	);

	// Check for typography variables
	test(
		'styleTokens.js: Generates typography CSS variables',
		styleTokensContent.includes( '--eipsi-font-family-heading' ) &&
			styleTokensContent.includes( '--eipsi-font-size-base' )
	);

	// Check for spacing variables
	test(
		'styleTokens.js: Generates spacing CSS variables',
		styleTokensContent.includes( '--eipsi-spacing-' ) &&
			styleTokensContent.includes( '--eipsi-spacing-container-padding' )
	);

	// Check for border variables
	test(
		'styleTokens.js: Generates border CSS variables',
		styleTokensContent.includes( '--eipsi-border-radius-' ) &&
			styleTokensContent.includes( '--eipsi-border-width' )
	);

	// Check for shadow variables
	test(
		'styleTokens.js: Generates shadow CSS variables',
		styleTokensContent.includes( '--eipsi-shadow-sm' ) &&
			styleTokensContent.includes( '--eipsi-shadow-focus' )
	);

	// Check for interactivity variables
	test(
		'styleTokens.js: Generates interactivity CSS variables',
		styleTokensContent.includes( '--eipsi-transition-duration' ) &&
			styleTokensContent.includes( '--eipsi-hover-scale' )
	);
} else {
	test( 'styleTokens.js: File exists', false );
}

console.log( '' );

// =============================================================================
// 4. PRESET SYSTEM
// =============================================================================

console.log( '📋 Test Group 4: Preset System' );
console.log( '-'.repeat( 70 ) );

const stylePresetsPath = 'src/utils/stylePresets.js';
const stylePresetsContent = readFile( stylePresetsPath );

if ( stylePresetsContent ) {
	test( 'stylePresets.js: File exists', true );

	// Check for all 5 presets
	const presets = [
		'CLINICAL_BLUE',
		'MINIMAL_WHITE',
		'WARM_NEUTRAL',
		'SERENE_TEAL',
		'DARK_EIPSI',
	];

	presets.forEach( ( preset ) => {
		test(
			`stylePresets.js: Defines ${ preset } preset`,
			stylePresetsContent.includes( `const ${ preset }` )
		);
	} );

	// Check STYLE_PRESETS export
	test(
		'stylePresets.js: Exports STYLE_PRESETS array',
		stylePresetsContent.includes( 'export const STYLE_PRESETS' )
	);

	// Verify all presets are in the array
	test(
		'stylePresets.js: STYLE_PRESETS includes all 5 presets',
		presets.every(
			( preset ) =>
				stylePresetsContent.includes( preset ) &&
				stylePresetsContent.match(
					new RegExp( `STYLE_PRESETS[^]*${ preset }` )
				)
		)
	);

	// Check preset structure
	test(
		'stylePresets.js: Presets have name property',
		stylePresetsContent.includes( "name: 'Clinical Blue'" )
	);

	test(
		'stylePresets.js: Presets have description property',
		stylePresetsContent.includes( 'description:' )
	);

	test(
		'stylePresets.js: Presets have config property with colors',
		stylePresetsContent.includes( 'config:' ) &&
			stylePresetsContent.includes( 'colors:' )
	);
} else {
	test( 'stylePresets.js: File exists', false );
}

console.log( '' );

// =============================================================================
// 5. FORM STYLE PANEL
// =============================================================================

console.log( '📋 Test Group 5: Form Style Panel' );
console.log( '-'.repeat( 70 ) );

const formStylePanelPath = 'src/components/FormStylePanel.js';
const formStylePanelContent = readFile( formStylePanelPath );

if ( formStylePanelContent ) {
	test( 'FormStylePanel.js: File exists', true );

	// Check for STYLE_PRESETS import
	test(
		'FormStylePanel.js: Imports STYLE_PRESETS',
		formStylePanelContent.includes( 'STYLE_PRESETS' )
	);

	// Check for applyPreset function
	test(
		'FormStylePanel.js: Has applyPreset function',
		formStylePanelContent.includes( 'applyPreset' )
	);

	// Check for preset rendering
	test(
		'FormStylePanel.js: Renders preset buttons',
		formStylePanelContent.includes( 'STYLE_PRESETS.map' ) &&
			formStylePanelContent.includes( 'eipsi-preset-button' )
	);

	// Check for preset preview
	test(
		'FormStylePanel.js: Renders preset preview',
		formStylePanelContent.includes( 'getPresetPreview' ) &&
			formStylePanelContent.includes( 'eipsi-preset-preview' )
	);

	// Check for active state tracking
	test(
		'FormStylePanel.js: Tracks active preset',
		formStylePanelContent.includes( 'activePreset' ) &&
			formStylePanelContent.includes( 'setActivePreset' )
	);
} else {
	test( 'FormStylePanel.js: File exists', false );
}

console.log( '' );

// =============================================================================
// 6. COMPILED OUTPUT VERIFICATION
// =============================================================================

console.log( '📋 Test Group 6: Compiled Output' );
console.log( '-'.repeat( 70 ) );

// Check for build output
const buildPath = 'build';
const buildExists = fs.existsSync( path.join( __dirname, buildPath ) );
test( 'build/: Output directory exists', buildExists );

if ( buildExists ) {
	// Check for compiled JS files
	const buildFiles = fs.readdirSync( path.join( __dirname, buildPath ) );
	const hasJsFiles = buildFiles.some( ( file ) => file.endsWith( '.js' ) );
	const hasCssFiles = buildFiles.some( ( file ) => file.endsWith( '.css' ) );

	test( 'build/: Contains compiled JS files', hasJsFiles );
	test( 'build/: Contains compiled CSS files', hasCssFiles );

	// Check for specific editor assets
	const hasEditorAssets = buildFiles.some(
		( file ) =>
			file.includes( 'editor' ) || file.includes( 'form-container' )
	);
	test( 'build/: Contains editor assets', hasEditorAssets );
}

console.log( '' );

// =============================================================================
// 7. DOCUMENTATION
// =============================================================================

console.log( '📋 Test Group 7: Documentation' );
console.log( '-'.repeat( 70 ) );

const readmePath = 'README.md';
const readmeContent = readFile( readmePath );

if ( readmeContent ) {
	test( 'README.md: File exists', true );

	// Check for WYSIWYG mentions
	test(
		'README.md: Documents WYSIWYG editor behavior',
		readmeContent.toLowerCase().includes( 'wysiwyg' ) ||
			readmeContent
				.toLowerCase()
				.includes( 'what you see is what you get' ) ||
			readmeContent.toLowerCase().includes( 'instant preview' )
	);

	// Check for preset documentation
	test(
		'README.md: Documents presets',
		readmeContent.includes( 'Clinical Blue' ) &&
			readmeContent.includes( 'Minimal White' ) &&
			readmeContent.includes( 'Warm Neutral' ) &&
			readmeContent.includes( 'Serene Teal' ) &&
			readmeContent.includes( 'Dark EIPSI' )
	);
} else {
	test( 'README.md: File exists', false );
}

console.log( '' );

// =============================================================================
// FINAL REPORT
// =============================================================================

console.log( '='.repeat( 70 ) );
console.log( 'TEST RESULTS SUMMARY' );
console.log( '='.repeat( 70 ) );
console.log( '' );

// Group results by status
const passed = results.filter( ( r ) => r.startsWith( '✅' ) );
const failed = results.filter( ( r ) => r.startsWith( '❌' ) );

if ( failed.length > 0 ) {
	console.log( '❌ FAILED TESTS:' );
	console.log( '-'.repeat( 70 ) );
	failed.forEach( ( result ) => console.log( result ) );
	console.log( '' );
}

if ( passed.length > 0 ) {
	console.log( '✅ PASSED TESTS:' );
	console.log( '-'.repeat( 70 ) );
	passed.forEach( ( result ) => console.log( result ) );
	console.log( '' );
}

console.log( '='.repeat( 70 ) );
console.log( `Total Tests: ${ totalTests }` );
console.log(
	`Passed: ${ passedTests } (${ (
		( passedTests / totalTests ) *
		100
	).toFixed( 1 ) }%)`
);
console.log(
	`Failed: ${ failedTests } (${ (
		( failedTests / totalTests ) *
		100
	).toFixed( 1 ) }%)`
);
console.log( '='.repeat( 70 ) );

// Exit with appropriate code
process.exit( failedTests > 0 ? 1 : 0 );
