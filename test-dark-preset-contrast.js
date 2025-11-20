/**
 * Test: Dark EIPSI Preset Text Contrast
 * Validates WCAG AA compliance for Dark EIPSI preset input field colors
 *
 * Requirements from ticket:
 * - Normal state: white background (#ffffff) + dark text (#1f2937) for 7:1 contrast
 * - Hover state: light gray background (#f8f9fa) + dark text (#1f2937) for good contrast
 * - Focus state: white background (#ffffff) + cyan border (#22d3ee)
 * - Placeholder: medium gray (#6b7280) for distinguishability
 */

const fs = require( 'fs' );
const path = require( 'path' );

// WCAG contrast calculation
function getLuminance( hex ) {
	const rgb = parseInt( hex.slice( 1 ), 16 );
	const r = ( rgb >> 16 ) & 0xff;
	const g = ( rgb >> 8 ) & 0xff;
	const b = ( rgb >> 0 ) & 0xff;

	const rsRGB = r / 255;
	const gsRGB = g / 255;
	const bsRGB = b / 255;

	const rLinear =
		rsRGB <= 0.03928
			? rsRGB / 12.92
			: Math.pow( ( rsRGB + 0.055 ) / 1.055, 2.4 );
	const gLinear =
		gsRGB <= 0.03928
			? gsRGB / 12.92
			: Math.pow( ( gsRGB + 0.055 ) / 1.055, 2.4 );
	const bLinear =
		bsRGB <= 0.03928
			? bsRGB / 12.92
			: Math.pow( ( bsRGB + 0.055 ) / 1.055, 2.4 );

	return 0.2126 * rLinear + 0.7152 * gLinear + 0.0722 * bLinear;
}

function getContrastRatio( hex1, hex2 ) {
	const l1 = getLuminance( hex1 );
	const l2 = getLuminance( hex2 );
	const lighter = Math.max( l1, l2 );
	const darker = Math.min( l1, l2 );
	return ( lighter + 0.05 ) / ( darker + 0.05 );
}

// Load the stylePresets.js file
const stylePresetsPath = path.join(
	__dirname,
	'src',
	'utils',
	'stylePresets.js'
);
const stylePresetsContent = fs.readFileSync( stylePresetsPath, 'utf8' );

// Extract Dark EIPSI colors (simple regex parsing)
const darkEipsiMatch = stylePresetsContent.match(
	/const DARK_EIPSI = \{[\s\S]*?config: \{[\s\S]*?colors: \{[\s\S]*?\}/
);
if ( ! darkEipsiMatch ) {
	console.error( '❌ Could not find DARK_EIPSI preset in stylePresets.js' );
	process.exit( 1 );
}

const darkEipsiColors = darkEipsiMatch[ 0 ];

// Extract color values
function extractColor( colorName ) {
	const regex = new RegExp( `${ colorName }:\\s*['"]([^'"]+)['"]` );
	const match = darkEipsiColors.match( regex );
	return match ? match[ 1 ] : null;
}

const colors = {
	inputBg: extractColor( 'inputBg' ),
	inputText: extractColor( 'inputText' ),
	backgroundSubtle: extractColor( 'backgroundSubtle' ),
	textMuted: extractColor( 'textMuted' ),
	inputBorder: extractColor( 'inputBorder' ),
	inputBorderFocus: extractColor( 'inputBorderFocus' ),
};

console.log( '🎨 Dark EIPSI Preset Colors:' );
console.log( '================================' );
console.log( `Input Background (normal): ${ colors.inputBg }` );
console.log( `Input Text: ${ colors.inputText }` );
console.log( `Background Subtle (hover): ${ colors.backgroundSubtle }` );
console.log( `Text Muted (placeholder): ${ colors.textMuted }` );
console.log( `Border Focus: ${ colors.inputBorderFocus }` );
console.log( '' );

// Test results
const results = {
	passed: 0,
	failed: 0,
	tests: [],
};

function test( name, condition, details ) {
	const passed = condition;
	results.tests.push( { name, passed, details } );
	if ( passed ) {
		results.passed++;
		console.log( `✅ ${ name }` );
		console.log( `   ${ details }` );
	} else {
		results.failed++;
		console.error( `❌ ${ name }` );
		console.error( `   ${ details }` );
	}
	console.log( '' );
}

// Test 1: Input background should be white
test(
	'Input background is white',
	colors.inputBg === '#ffffff',
	`Expected: #ffffff, Got: ${ colors.inputBg }`
);

// Test 2: Input text should be dark gray for contrast
test(
	'Input text is dark gray',
	colors.inputText === '#1f2937',
	`Expected: #1f2937, Got: ${ colors.inputText }`
);

// Test 3: Placeholder color should be medium gray
test(
	'Placeholder color is medium gray',
	colors.textMuted === '#6b7280',
	`Expected: #6b7280, Got: ${ colors.textMuted }`
);

// Test 4: Hover background should be light gray (not dark)
test(
	'Hover background is light gray',
	colors.backgroundSubtle === '#f8f9fa',
	`Expected: #f8f9fa, Got: ${ colors.backgroundSubtle }`
);

// Test 5: Normal state contrast ratio (white bg + dark text)
const normalContrast = getContrastRatio( colors.inputBg, colors.inputText );
test(
	'Normal state WCAG AA contrast (≥ 4.5:1)',
	normalContrast >= 4.5,
	`Contrast ratio: ${ normalContrast.toFixed( 2 ) }:1 (${
		colors.inputBg
	} on ${ colors.inputText })`
);

// Test 6: Normal state contrast ratio meets AAA (7:1)
test(
	'Normal state WCAG AAA contrast (≥ 7:1)',
	normalContrast >= 7.0,
	`Contrast ratio: ${ normalContrast.toFixed( 2 ) }:1 - ${
		normalContrast >= 7.0 ? 'AAA level!' : 'below AAA'
	}`
);

// Test 7: Hover state contrast ratio (light gray bg + dark text)
const hoverContrast = getContrastRatio(
	colors.backgroundSubtle,
	colors.inputText
);
test(
	'Hover state WCAG AA contrast (≥ 4.5:1)',
	hoverContrast >= 4.5,
	`Contrast ratio: ${ hoverContrast.toFixed( 2 ) }:1 (${
		colors.backgroundSubtle
	} on ${ colors.inputText })`
);

// Test 8: Placeholder contrast on white background
const placeholderContrast = getContrastRatio(
	colors.inputBg,
	colors.textMuted
);
test(
	'Placeholder WCAG AA contrast for UI (≥ 3:1)',
	placeholderContrast >= 3.0,
	`Contrast ratio: ${ placeholderContrast.toFixed( 2 ) }:1 (${
		colors.inputBg
	} on ${ colors.textMuted })`
);

// Test 9: Placeholder is distinguishable but not too dark
test(
	'Placeholder is medium gray (not too light or dark)',
	placeholderContrast >= 3.0 && placeholderContrast <= 7.0,
	`Contrast ratio: ${ placeholderContrast.toFixed( 2 ) }:1 - ${
		placeholderContrast >= 3.0 && placeholderContrast <= 7.0
			? 'Good balance'
			: 'Out of range'
	}`
);

// Test 10: Border colors are defined
test(
	'Border colors are defined',
	colors.inputBorder && colors.inputBorderFocus,
	`Border: ${ colors.inputBorder }, Focus: ${ colors.inputBorderFocus }`
);

// Summary
console.log( '📊 Test Summary' );
console.log( '================================' );
console.log( `Total Tests: ${ results.tests.length }` );
console.log( `✅ Passed: ${ results.passed }` );
console.log( `❌ Failed: ${ results.failed }` );
console.log( '' );

if ( results.failed === 0 ) {
	console.log(
		'🎉 All tests passed! Dark EIPSI preset has excellent text visibility.'
	);
	console.log( '' );
	console.log( '✅ TICKET VALIDATION:' );
	console.log( '   ✓ Normal state: white background + dark text (readable)' );
	console.log(
		'   ✓ Hover state: light gray background + dark text (maintains contrast)'
	);
	console.log( '   ✓ Placeholder: medium gray (distinguishable)' );
	console.log( '   ✓ WCAG AA compliance: ≥ 4.5:1 contrast ratio' );
	console.log(
		'   ✓ WCAG AAA compliance: ≥ 7:1 contrast ratio (if achieved)'
	);
	process.exit( 0 );
} else {
	console.error(
		'⚠️  Some tests failed. Please review the Dark EIPSI preset configuration.'
	);
	process.exit( 1 );
}
