/**
 * EIPSI Forms – Option Parser Utility
 *
 * Centralizes option parsing logic for all choice-based blocks:
 * - campo-radio
 * - campo-multiple
 * - campo-select
 * - campo-likert (labels)
 * - vas-slider (labels)
 *
 * Ensures zero data loss for rich options containing:
 * - Commas: "Sí, a veces"
 * - Periods: "Nunca."
 * - Quotes: "Dijo \"no\""
 * - Spanish punctuation: "¿Alguna ver?"
 *
 * Format priority (for backwards compatibility):
 * 1. Semicolon-separated (;) – NEW STANDARD (v1.3+)
 * 2. Newline-delimited (\n) – Current format (v1.2)
 * 3. Comma-separated with CSV quoting (,) – Legacy format
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
 * Parses options from a string OR array with intelligent format detection
 *
 * Priority (for backwards compatibility):
 * 1. Semicolon-separated (;) – NEW STANDARD
 * 2. Newline-delimited (\n) – Current format
 * 3. Comma-separated with CSV quoting (,) – Legacy format
 *
 * @param {string | Array} optionsInput Raw options (string OR array) from block attributes
 * @return {string[]} Array of trimmed, non-empty option strings
 */
export function parseOptions( optionsInput ) {
	// Handle undefined, null, empty string, empty array
	if ( ! optionsInput ) {
		return [];
	}

	// If already an array, validate and return (legacy data support)
	if ( Array.isArray( optionsInput ) ) {
		return optionsInput
			.map( ( option ) => {
				// Handle objects like {label: "...", value: "..."}
				if ( typeof option === 'object' && option !== null ) {
					return String( option.label || option.value || '' );
				}
				// Handle primitives (string, number, etc)
				return String( option || '' );
			} )
			.map( ( opt ) => opt.trim() )
			.filter( ( opt ) => opt !== '' );
	}

	// If not a string at this point, convert to string
	if ( typeof optionsInput !== 'string' ) {
		const stringified = String( optionsInput );
		if ( ! stringified || stringified.trim() === '' ) {
			return [];
		}
		return [ stringified.trim() ];
	}

	// String parsing logic (original behavior)
	if ( optionsInput.trim() === '' ) {
		return [];
	}

	const normalized = normalizeLineEndings( optionsInput );

	// Priority 1: Semicolon-separated (NEW STANDARD)
	if ( normalized.includes( ';' ) ) {
		return normalized
			.split( ';' )
			.map( ( option ) => option.trim() )
			.filter( ( option ) => option !== '' );
	}

	// Priority 2: Newline-delimited (v1.2 format)
	if ( normalized.includes( '\n' ) ) {
		return normalized
			.split( '\n' )
			.map( ( option ) => option.trim() )
			.filter( ( option ) => option !== '' );
	}

	// Priority 3: Legacy comma-separated with CSV-style quoting
	return parseCommaSeparated( normalized );
}

/**
 * Converts an array of options back to semicolon-separated string
 * (NEW canonical storage format as of v1.3)
 *
 * @param {string[]} options Array of option strings
 * @return {string} Semicolon-separated string
 */
export function stringifyOptions( options ) {
	if ( ! Array.isArray( options ) ) {
		return '';
	}

	return options
		.map( ( opt ) => ( opt || '' ).trim() )
		.filter( ( opt ) => opt !== '' )
		.join( '; ' );
}

/**
 * Normalizes options input from TextareaControl onChange
 *
 * Converts any mix of separators (semicolon, newline, comma) into the
 * canonical semicolon-separated format without blank options.
 *
 * @param {string} value Raw value from textarea
 * @return {string} Normalized semicolon-delimited string
 */
export function normalizeOptionsInput( value ) {
	if ( ! value || value.trim() === '' ) {
		return '';
	}

	const normalized = normalizeLineEndings( value );

	const options = parseOptions( normalized );

	return stringifyOptions( options );
}

/**
 * Encodes newlines for safe storage in HTML attributes
 *
 * HTML attributes cannot contain literal newlines. This function encodes
 * them as HTML entities (&#10;) for safe attribute storage.
 *
 * @param {string} str String that may contain newlines
 * @return {string} String with newlines encoded as &#10;
 */
export function encodeNewlinesForAttribute( str ) {
	if ( ! str || typeof str !== 'string' ) {
		return '';
	}
	return str.replace( /\n/g, '&#10;' );
}

/**
 * Decodes HTML entities back to newlines
 *
 * Complement to encodeNewlinesForAttribute. Handles both &#10; and &#x0A;
 * as well as literal newlines for backward compatibility.
 *
 * @param {string} str String that may contain encoded newlines
 * @return {string} String with encoded newlines decoded back to \n
 */
export function decodeNewlinesFromAttribute( str ) {
	if ( ! str || typeof str !== 'string' ) {
		return '';
	}
	return str.replace( /&#10;|&#x0A;/gi, '\n' );
}
