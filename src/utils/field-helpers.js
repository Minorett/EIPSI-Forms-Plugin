/**
 * Field Helpers - Centralized utility functions for form fields
 *
 * This file consolidates commonly used helper functions that were
 * previously duplicated across multiple block files.
 */
import { __ } from '@wordpress/i18n';

/**
 * Render helper text with support for line breaks.
 *
 * @param {string} text - The helper text to render.
 * @return {JSX.Element|null} The rendered helper text or null if empty.
 */
export const renderHelperText = ( text ) => {
	if ( ! text || text.trim() === '' ) {
		return null;
	}

	const lines = text.split( '\n' );

	return (
		<p className="field-helper">
			{ lines.map( ( line, index ) => (
				<span key={ index }>
					{ line }
					{ index < lines.length - 1 && <br /> }
				</span>
			) ) }
		</p>
	);
};

/**
 * Generate a field ID from a field name.
 * Normalizes spaces to hyphens and sanitizes special characters.
 *
 * @param {string} fieldName - The field name to convert to an ID.
 * @param {string} suffix    - Optional suffix to append to the ID.
 * @return {string|undefined} The generated field ID or undefined if invalid.
 */
export const getFieldId = ( fieldName, suffix = '' ) => {
	if ( ! fieldName || fieldName.trim() === '' ) {
		return undefined;
	}

	const normalized = fieldName.trim().replace( /\s+/g, '-' );
	const sanitized = normalized.replace( /[^a-zA-Z0-9_-]/g, '-' );

	return suffix ? `field-${ sanitized }-${ suffix }` : `field-${ sanitized }`;
};

/**
 * Calculate the maximum value for a Likert scale.
 * Formula: maxValue = minValue + (labelCount - 1)
 *
 * @param {string} labelsString    - Labels separated by semicolons.
 * @param {number} currentMinValue - The current minimum value.
 * @return {number} The calculated maximum value.
 */
export const calculateMaxValue = ( labelsString, currentMinValue ) => {
	if ( ! labelsString || labelsString.trim() === '' ) {
		return currentMinValue; // If no labels, max = min
	}
	const labelArray = labelsString
		.split( ';' )
		.map( ( labelText ) => labelText.trim() )
		.filter( ( labelText ) => labelText !== '' );
	const labelCount = labelArray.length > 0 ? labelArray.length : 1;
	return currentMinValue + ( labelCount - 1 );
};

/**
 * Render consent body with support for markdown parsing.
 *
 * @param {string}   text            - The consent text to render.
 * @param {Function} parseMarkdown   - Function to parse markdown (e.g., parseConsentMarkdown).
 * @param {boolean}  showPlaceholder - Whether to show placeholder when text is empty.
 * @param {string}   placeholderText - Custom placeholder text.
 * @return {JSX.Element|null} The rendered consent body or null if empty.
 */
export const renderConsentBody = (
	text,
	parseMarkdown,
	showPlaceholder = false,
	placeholderText = null
) => {
	if ( ! text || text.trim() === '' ) {
		return showPlaceholder ? (
			<p className="eipsi-preview-placeholder">
				{ placeholderText ||
					__(
						'Escriba el contenido del consentimiento aquí…',
						'eipsi-forms'
					) }
			</p>
		) : null;
	}

	const lines = text.split( '\n' );

	return (
		<div className="eipsi-consent-body">
			{ lines.map( ( line, index ) => {
				const parsedLine = parseMarkdown ? parseMarkdown( line ) : line;
				return (
					<p
						key={ `${ line }-${ index }` }
						dangerouslySetInnerHTML={ { __html: parsedLine } }
					/>
				);
			} ) }
		</div>
	);
};
