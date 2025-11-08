/**
 * Contrast Checker Utility
 * Validates WCAG AA compliance for text/background color combinations
 * Used in FormStylePanel to warn users about accessibility issues
 *
 * @package
 */

/**
 * Convert hex color to RGB
 *
 * @param {string} hex - Hex color string
 * @return {Object|null} RGB object or null if invalid
 */
function hexToRgb( hex ) {
	// Remove # if present
	const cleanHex = hex.replace( /^#/, '' );

	// Support both 3-digit and 6-digit hex
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

/**
 * Parse color string to RGB
 * Supports hex, rgb(), and rgba() formats
 *
 * @param {string} color - Color string
 * @return {Object|null} RGB object or null if invalid
 */
function parseColor( color ) {
	if ( ! color || typeof color !== 'string' ) {
		return null;
	}

	const trimmed = color.trim();

	// Hex format
	if ( trimmed.startsWith( '#' ) ) {
		return hexToRgb( trimmed );
	}

	// RGB/RGBA format
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

/**
 * Calculate relative luminance for a color
 * Uses WCAG formula: https://www.w3.org/TR/WCAG20/#relativeluminancedef
 *
 * @param {Object} rgb - RGB color object
 * @return {number} Relative luminance
 */
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

/**
 * Calculate contrast ratio between two colors
 * Uses WCAG formula: https://www.w3.org/TR/WCAG20/#contrast-ratiodef
 *
 * @param {string} color1 - First color (any CSS format)
 * @param {string} color2 - Second color (any CSS format)
 * @return {number} Contrast ratio (1-21)
 */
export function getContrastRatio( color1, color2 ) {
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

/**
 * Check if a color combination passes WCAG AA for normal text
 * Requires minimum 4.5:1 contrast ratio
 *
 * @param {string} textColor - Text color
 * @param {string} bgColor   - Background color
 * @return {boolean} True if passes WCAG AA
 */
export function passesWCAGAA( textColor, bgColor ) {
	const ratio = getContrastRatio( textColor, bgColor );
	return ratio >= 4.5;
}

/**
 * Check if a color combination passes WCAG AAA for normal text
 * Requires minimum 7:1 contrast ratio
 *
 * @param {string} textColor - Text color
 * @param {string} bgColor   - Background color
 * @return {boolean} True if passes WCAG AAA
 */
export function passesWCAGAAA( textColor, bgColor ) {
	const ratio = getContrastRatio( textColor, bgColor );
	return ratio >= 7;
}

/**
 * Get contrast rating and recommendation
 *
 * @param {string} textColor - Text color
 * @param {string} bgColor   - Background color
 * @return {Object} Rating object with passes, ratio, and message
 */
export function getContrastRating( textColor, bgColor ) {
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
