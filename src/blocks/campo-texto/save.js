import { useBlockProps } from '@wordpress/block-editor';
import { renderHelperText, getFieldId } from '../../utils/field-helpers';

export default function Save( { attributes } ) {
	const { fieldName, label, required, placeholder, helperText, fieldType, fieldKey } =
		attributes;

	// Generate automatic fieldName if not provided (using label, fieldKey, or timestamp)
	const generateFieldName = () => {
		if ( fieldName && typeof fieldName === 'string' && fieldName.trim() !== '' ) {
			return fieldName.trim();
		}
		// Try to use fieldKey (used by some blocks like likert)
		if ( fieldKey && typeof fieldKey === 'string' && fieldKey.trim() !== '' ) {
			return fieldKey.trim();
		}
		// Generate from label (sanitize)
		if ( label && typeof label === 'string' && label.trim() !== '' ) {
			const sanitized = label.trim()
				.toLowerCase()
				.replace( /[^a-z0-9]/g, '_' )
				.replace( /_+/g, '_' )
				.replace( /^_|_$/g, '' )
				.substring( 0, 30 );
			return sanitized || 'field_' + Date.now();
		}
		// Fallback to timestamp
		return 'field_' + Date.now();
	};

	const normalizedFieldName = generateFieldName();

	const blockProps = useBlockProps.save( {
		className: 'form-group eipsi-field eipsi-text-field',
		'data-field-name': normalizedFieldName,
		'data-required': required ? 'true' : 'false',
		'data-field-type': fieldType || 'text',
	} );

	const inputId = getFieldId( normalizedFieldName );
	const typeAttribute = fieldType || 'text';

	return (
		<div { ...blockProps }>
			{ label && (
				<label
					className={ required ? 'required' : undefined }
					htmlFor={ inputId }
				>
					{ label }
				</label>
			) }
			<input
				type={ typeAttribute }
				name={ normalizedFieldName }
				id={ inputId }
				placeholder={ placeholder || '' }
				required={ required }
				data-required={ required ? 'true' : 'false' }
				data-field-type={ typeAttribute }
			/>
			{ renderHelperText( helperText ) }
			<div className="form-error" aria-live="polite" />
		</div>
	);
}
