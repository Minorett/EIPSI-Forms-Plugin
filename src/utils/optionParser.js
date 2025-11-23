/**
 * EIPSI Forms – Option Parser Utility
 *
 * Centralizes option parsing logic for all choice-based blocks:
 * - campo-radio
 * - campo-multiple
 * - campo-select
 *
 * Ensures zero data loss for rich options containing:
 * - Commas: "Sí, a veces"
 * - Periods: "Nunca."
 * - Quotes: "Dijo \"no\""
 * - Spanish punctuation: "¿Alguna vez?"
 *
 * Canonical storage format: newline-delimited string
 * Legacy support: comma-separated with CSV-style quote escaping
 */

/**
 * Normalizes line endings to Unix-style (\n)
 *
 * @param {string} str Input string with potential CRLF line endings
 * @return {string} Normalized string with only LF line endings
 */
export function normalizeLineEndings( str ) {
	if ( ! str ) {
		return '';
	}
	return str.replace( /\r\n/g, '\n' ).replace( /\r/g, '\n' );
}

/**
 * Parses legacy comma-separated options with CSV-style quoting
 *
 * Handles:
 * - Quoted values: "Sí, a veces"
 * - Escaped quotes: "Dijo ""no"""
 * - Mixed quoted/unquoted: Nunca, "Sí, a veces", A veces
 *
 * @param {string} str Comma-separated string
 * @return {string[]} Array of trimmed, non-empty option strings
 */
export function parseCommaSeparated( str ) {
	const options = [];
	let current = '';
	let inQuotes = false;
	let i = 0;

	while ( i < str.length ) {
		const char = str[ i ];
		const nextChar = str[ i + 1 ];

		if ( char === '"' ) {
			if ( inQuotes && nextChar === '"' ) {
				// Escaped quote: "" → "
				current += '"';
				i += 2;
				continue;
			}
			// Toggle quote state
			inQuotes = ! inQuotes;
			i++;
			continue;
		}

		if ( char === ',' && ! inQuotes ) {
			// End of option
			const trimmed = current.trim();
			if ( trimmed !== '' ) {
				options.push( trimmed );
			}
			current = '';
			i++;
			continue;
		}

		current += char;
		i++;
	}

	// Don't forget the last option
	const trimmed = current.trim();
	if ( trimmed !== '' ) {
		options.push( trimmed );
	}

	return options;
}

/**
 * Parses options from a string (newline or legacy comma-separated)
 *
 * @param {string} optionsString Raw options string from block attributes
 * @return {string[]} Array of trimmed, non-empty option strings
 */
export function parseOptions( optionsString ) {
	if ( ! optionsString || optionsString.trim() === '' ) {
		return [];
	}

	const normalized = normalizeLineEndings( optionsString );

	// Canonical format: newline-delimited
	if ( normalized.includes( '\n' ) ) {
		return normalized
			.split( '\n' )
			.map( ( option ) => option.trim() )
			.filter( ( option ) => option !== '' );
	}

	// Legacy format: comma-separated with CSV-style quoting
	return parseCommaSeparated( normalized );
}

/**
 * Converts an array of options back to newline-delimited string
 * (canonical storage format)
 *
 * @param {string[]} options Array of option strings
 * @return {string} Newline-delimited string
 */
export function stringifyOptions( options ) {
	if ( ! Array.isArray( options ) ) {
		return '';
	}

	return options
		.map( ( opt ) => ( opt || '' ).trim() )
		.filter( ( opt ) => opt !== '' )
		.join( '\n' );
}

/**
 * Normalizes options input from TextareaControl onChange
 *
 * @param {string} value Raw value from textarea
 * @return {string} Normalized newline-delimited string
 */
export function normalizeOptionsInput( value ) {
	if ( ! value || value.trim() === '' ) {
		return '';
	}

	const normalized = normalizeLineEndings( value );
	const options = normalized
		.split( '\n' )
		.map( ( opt ) => opt.trim() )
		.filter( ( opt ) => opt !== '' );

	return stringifyOptions( options );
}
