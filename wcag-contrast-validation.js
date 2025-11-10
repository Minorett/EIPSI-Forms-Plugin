/**
 * WCAG Contrast Validation Test Suite
 * Validates contrast ratios for all default tokens and presets
 *
 * Usage: node wcag-contrast-validation.js
 */

// Import contrast checker functions
function hexToRgb( hex ) {
	const cleanHex = hex.replace( /^#/, '' );
	let r, g, b;

	if ( cleanHex.length === 3 ) {
		r = parseInt( cleanHex[ 0 ] + cleanHex[ 0 ], 16 );
		g = parseInt( cleanHex[ 1 ] + cleanHex[ 1 ], 16 );
		b = parseInt( cleanHex[ 2 ] + cleanHex[ 2 ], 16 );
	} else if ( cleanHex.length === 6 ) {
		r = parseInt( cleanHex.substring( 0, 2 ), 16 );
		g = parseInt( cleanHex.substring( 2, 4 ), 16 );
		b = parseInt( cleanHex.substring( 4, 6 ), 16 );
	} else {
		return null;
	}

	return { r, g, b };
}

function parseColor( color ) {
	if ( ! color || typeof color !== 'string' ) {
		return null;
	}

	const trimmed = color.trim();

	if ( trimmed.startsWith( '#' ) ) {
		return hexToRgb( trimmed );
	}

	const rgbMatch = trimmed.match( /rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/ );
	if ( rgbMatch ) {
		return {
			r: parseInt( rgbMatch[ 1 ], 10 ),
			g: parseInt( rgbMatch[ 2 ], 10 ),
			b: parseInt( rgbMatch[ 3 ], 10 ),
		};
	}

	return null;
}

function getLuminance( rgb ) {
	if ( ! rgb ) {
		return 0;
	}

	const rsRGB = rgb.r / 255;
	const gsRGB = rgb.g / 255;
	const bsRGB = rgb.b / 255;

	const r =
		rsRGB <= 0.03928
			? rsRGB / 12.92
			: Math.pow( ( rsRGB + 0.055 ) / 1.055, 2.4 );
	const g =
		gsRGB <= 0.03928
			? gsRGB / 12.92
			: Math.pow( ( gsRGB + 0.055 ) / 1.055, 2.4 );
	const b =
		bsRGB <= 0.03928
			? bsRGB / 12.92
			: Math.pow( ( bsRGB + 0.055 ) / 1.055, 2.4 );

	return 0.2126 * r + 0.7152 * g + 0.0722 * b;
}

function getContrastRatio( color1, color2 ) {
	const rgb1 = parseColor( color1 );
	const rgb2 = parseColor( color2 );

	if ( ! rgb1 || ! rgb2 ) {
		return 0;
	}

	const lum1 = getLuminance( rgb1 );
	const lum2 = getLuminance( rgb2 );

	const lighter = Math.max( lum1, lum2 );
	const darker = Math.min( lum1, lum2 );

	return ( lighter + 0.05 ) / ( darker + 0.05 );
}

function getContrastRating( textColor, bgColor ) {
	const ratio = getContrastRatio( textColor, bgColor );

	if ( ratio >= 7 ) {
		return {
			passes: true,
			level: 'AAA',
			ratio: ratio.toFixed( 2 ),
			message: 'Excellent contrast (WCAG AAA)',
		};
	}

	if ( ratio >= 4.5 ) {
		return {
			passes: true,
			level: 'AA',
			ratio: ratio.toFixed( 2 ),
			message: 'Good contrast (WCAG AA)',
		};
	}

	return {
		passes: false,
		level: 'Fail',
		ratio: ratio.toFixed( 2 ),
		message: `Insufficient contrast (${ ratio.toFixed(
			2
		) }:1). Minimum 4.5:1 required for accessibility.`,
	};
}

// Define presets (from stylePresets.js)
const PRESETS = {
	'Clinical Blue': {
		colors: {
			primary: '#005a87',
			primaryHover: '#003d5b',
			secondary: '#e3f2fd',
			background: '#ffffff',
			backgroundSubtle: '#f8f9fa',
			text: '#2c3e50',
			textMuted: '#64748b',
			inputBg: '#ffffff',
			inputText: '#2c3e50',
			inputBorder: '#e2e8f0',
			inputBorderFocus: '#005a87',
			buttonBg: '#005a87',
			buttonText: '#ffffff',
			buttonHoverBg: '#003d5b',
			error: '#d32f2f',
			success: '#198754',
			warning: '#b35900',
			border: '#e2e8f0',
			borderDark: '#cbd5e0',
		},
	},
	'Minimal White': {
		colors: {
			primary: '#2c5aa0',
			primaryHover: '#1e3a70',
			secondary: '#f0f4f8',
			background: '#ffffff',
			backgroundSubtle: '#fafbfc',
			text: '#1a202c',
			textMuted: '#556677',
			inputBg: '#ffffff',
			inputText: '#1a202c',
			inputBorder: '#e2e8f0',
			inputBorderFocus: '#2c5aa0',
			buttonBg: '#2c5aa0',
			buttonText: '#ffffff',
			buttonHoverBg: '#1e3a70',
			error: '#c53030',
			success: '#28744c',
			warning: '#b35900',
			border: '#e2e8f0',
			borderDark: '#cbd5e0',
		},
	},
	'Warm Neutral': {
		colors: {
			primary: '#8b6f47',
			primaryHover: '#6b5437',
			secondary: '#f5f1eb',
			background: '#fdfcfa',
			backgroundSubtle: '#f7f4ef',
			text: '#3d3935',
			textMuted: '#6b6560',
			inputBg: '#ffffff',
			inputText: '#3d3935',
			inputBorder: '#e5ded4',
			inputBorderFocus: '#8b6f47',
			buttonBg: '#8b6f47',
			buttonText: '#ffffff',
			buttonHoverBg: '#6b5437',
			error: '#c53030',
			success: '#2a7850',
			warning: '#b04d1f',
			border: '#e5ded4',
			borderDark: '#d4c9bb',
		},
	},
	'High Contrast': {
		colors: {
			primary: '#0050d8',
			primaryHover: '#003da6',
			secondary: '#f0f0f0',
			background: '#ffffff',
			backgroundSubtle: '#f8f8f8',
			text: '#000000',
			textMuted: '#3d3d3d',
			inputBg: '#ffffff',
			inputText: '#000000',
			inputBorder: '#000000',
			inputBorderFocus: '#0050d8',
			buttonBg: '#0050d8',
			buttonText: '#ffffff',
			buttonHoverBg: '#003da6',
			error: '#d30000',
			success: '#006600',
			warning: '#b35900',
			border: '#000000',
			borderDark: '#000000',
		},
	},
};

// Hardcoded colors to test (from CSS - now fixed)
const HARDCODED_COLORS = {
	placeholder: '#64748b', // Fixed from #adb5bd
	helperText: '#64748b',
};

// Test definitions
const TESTS = [
	{
		name: 'Text vs Background',
		fg: 'text',
		bg: 'background',
		critical: true,
	},
	{
		name: 'Text Muted vs Background Subtle',
		fg: 'textMuted',
		bg: 'backgroundSubtle',
		critical: true,
	},
	{
		name: 'Text Muted vs Background',
		fg: 'textMuted',
		bg: 'background',
		critical: false,
	},
	{
		name: 'Button Text vs Button Background',
		fg: 'buttonText',
		bg: 'buttonBg',
		critical: true,
	},
	{
		name: 'Button Text vs Button Hover',
		fg: 'buttonText',
		bg: 'buttonHoverBg',
		critical: true,
	},
	{
		name: 'Input Text vs Input Background',
		fg: 'inputText',
		bg: 'inputBg',
		critical: true,
	},
	{
		name: 'Error vs Background',
		fg: 'error',
		bg: 'background',
		critical: true,
	},
	{
		name: 'Error vs Background Subtle',
		fg: 'error',
		bg: 'backgroundSubtle',
		critical: false,
	},
	{
		name: 'Success vs Background',
		fg: 'success',
		bg: 'background',
		critical: true,
	},
	{
		name: 'Success vs Background Subtle',
		fg: 'success',
		bg: 'backgroundSubtle',
		critical: false,
	},
	{
		name: 'Warning vs Background',
		fg: 'warning',
		bg: 'background',
		critical: true,
	},
	{
		name: 'Warning vs Background Subtle',
		fg: 'warning',
		bg: 'backgroundSubtle',
		critical: false,
	},
	{
		name: 'Primary vs Background',
		fg: 'primary',
		bg: 'background',
		critical: false,
	},
	{
		name: 'Input Border Focus vs Input Background',
		fg: 'inputBorderFocus',
		bg: 'inputBg',
		critical: false,
	},
];

// Console formatting
const COLORS = {
	reset: '\x1b[0m',
	bright: '\x1b[1m',
	green: '\x1b[32m',
	yellow: '\x1b[33m',
	red: '\x1b[31m',
	cyan: '\x1b[36m',
	gray: '\x1b[90m',
};

function formatStatus( rating ) {
	if ( rating.level === 'AAA' ) {
		return `${ COLORS.green }✓ AAA${ COLORS.reset }`;
	}
	if ( rating.level === 'AA' ) {
		return `${ COLORS.green }✓ AA${ COLORS.reset }`;
	}
	return `${ COLORS.red }✗ FAIL${ COLORS.reset }`;
}

// Main validation function
function validatePreset( presetName, preset ) {
	console.log(
		`\n${ COLORS.bright }${ COLORS.cyan }━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${ COLORS.reset }`
	);
	console.log(
		`${ COLORS.bright }${ COLORS.cyan }  ${ presetName }${ COLORS.reset }`
	);
	console.log(
		`${ COLORS.cyan }━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${ COLORS.reset }\n`
	);

	const results = [];
	let criticalFailures = 0;
	let nonCriticalFailures = 0;

	TESTS.forEach( ( test ) => {
		const fg = preset.colors[ test.fg ];
		const bg = preset.colors[ test.bg ];
		const rating = getContrastRating( fg, bg );

		results.push( {
			test,
			rating,
			fg,
			bg,
		} );

		if ( ! rating.passes ) {
			if ( test.critical ) {
				criticalFailures++;
			} else {
				nonCriticalFailures++;
			}
		}

		const criticalFlag = test.critical ? '🔴' : '⚪';
		const status = formatStatus( rating );
		const ratioColor = rating.passes ? COLORS.gray : COLORS.red;

		console.log(
			`${ criticalFlag } ${ test.name.padEnd(
				40
			) } ${ status }  ${ ratioColor }${ rating.ratio }:1${
				COLORS.reset
			}`
		);
		console.log( `   ${ COLORS.gray }${ fg } on ${ bg }${ COLORS.reset }` );
	} );

	// Test hardcoded colors
	console.log(
		`\n${ COLORS.bright }Hardcoded Colors (from CSS audit):${ COLORS.reset }`
	);

	const placeholderRating = getContrastRating(
		HARDCODED_COLORS.placeholder,
		preset.colors.background
	);
	console.log(
		`🔴 Placeholder vs Background               ${ formatStatus(
			placeholderRating
		) }  ${ placeholderRating.passes ? COLORS.gray : COLORS.red }${
			placeholderRating.ratio
		}:1${ COLORS.reset }`
	);
	console.log(
		`   ${ COLORS.gray }${ HARDCODED_COLORS.placeholder } on ${ preset.colors.background }${ COLORS.reset }`
	);
	if ( ! placeholderRating.passes ) {
		criticalFailures++;
	}

	const helperRating = getContrastRating(
		HARDCODED_COLORS.helperText,
		preset.colors.backgroundSubtle
	);
	console.log(
		`⚪ Helper Text vs Background Subtle        ${ formatStatus(
			helperRating
		) }  ${ helperRating.passes ? COLORS.gray : COLORS.red }${
			helperRating.ratio
		}:1${ COLORS.reset }`
	);
	console.log(
		`   ${ COLORS.gray }${ HARDCODED_COLORS.helperText } on ${ preset.colors.backgroundSubtle } (informational)${ COLORS.reset }`
	);
	if ( ! helperRating.passes ) {
		nonCriticalFailures++;
	}

	// Summary
	console.log( `\n${ COLORS.bright }Summary:${ COLORS.reset }` );
	const totalTests = TESTS.length + 2; // +2 for hardcoded
	const passing = totalTests - criticalFailures - nonCriticalFailures;

	if ( criticalFailures === 0 ) {
		console.log(
			`${ COLORS.green }✓ All critical pairs pass WCAG AA (4.5:1)${ COLORS.reset }`
		);
	} else {
		console.log(
			`${ COLORS.red }✗ ${ criticalFailures } critical failure(s)${ COLORS.reset }`
		);
	}

	if ( nonCriticalFailures > 0 ) {
		console.log(
			`${ COLORS.yellow }⚠ ${ nonCriticalFailures } non-critical failure(s)${ COLORS.reset }`
		);
	}

	console.log( `   ${ passing }/${ totalTests } tests passed` );

	return {
		presetName,
		criticalFailures,
		nonCriticalFailures,
		results,
		placeholderRating,
		helperRating,
	};
}

// Run all validations
console.log( `${ COLORS.bright }${ COLORS.cyan }` );
console.log(
	'╔══════════════════════════════════════════════════════════════╗'
);
console.log(
	'║       WCAG CONTRAST VALIDATION - EIPSI FORMS PLUGIN         ║'
);
console.log(
	'║                                                              ║'
);
console.log(
	'║  Standard: WCAG 2.1 Level AA                                ║'
);
console.log(
	'║  Minimum Ratio: 4.5:1 (normal text)                         ║'
);
console.log(
	'║  Target Ratio: 7:1 (AAA - enhanced)                         ║'
);
console.log(
	'╚══════════════════════════════════════════════════════════════╝'
);
console.log( `${ COLORS.reset }` );

console.log(
	`${ COLORS.gray }Legend: 🔴 = Critical (must pass)  ⚪ = Informational${ COLORS.reset }\n`
);

const allResults = [];
let totalCriticalFailures = 0;

Object.keys( PRESETS ).forEach( ( presetName ) => {
	const result = validatePreset( presetName, PRESETS[ presetName ] );
	allResults.push( result );
	totalCriticalFailures += result.criticalFailures;
} );

// Final summary
console.log(
	`\n${ COLORS.bright }${ COLORS.cyan }━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${ COLORS.reset }`
);
console.log(
	`${ COLORS.bright }${ COLORS.cyan }  FINAL SUMMARY${ COLORS.reset }`
);
console.log(
	`${ COLORS.cyan }━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${ COLORS.reset }\n`
);

allResults.forEach( ( result ) => {
	const status =
		result.criticalFailures === 0
			? `${ COLORS.green }PASS${ COLORS.reset }`
			: `${ COLORS.red }FAIL${ COLORS.reset }`;
	console.log(
		`${ result.presetName.padEnd( 20 ) } ${ status }  (${
			result.criticalFailures
		} critical failures)`
	);
} );

console.log();

if ( totalCriticalFailures === 0 ) {
	console.log(
		`${ COLORS.green }${ COLORS.bright }✓ ALL PRESETS PASS WCAG AA REQUIREMENTS${ COLORS.reset }`
	);
	console.log(
		`${ COLORS.green }All default theme tokens and panel presets meet accessibility standards.${ COLORS.reset }`
	);
	process.exit( 0 );
} else {
	console.log(
		`${ COLORS.red }${ COLORS.bright }✗ ACCESSIBILITY ISSUES DETECTED${ COLORS.reset }`
	);
	console.log(
		`${ COLORS.red }${ totalCriticalFailures } critical contrast failure(s) require attention.${ COLORS.reset }`
	);
	console.log(
		`${ COLORS.yellow }Review the detailed report above for specific color pairs.${ COLORS.reset }`
	);
	process.exit( 1 );
}
