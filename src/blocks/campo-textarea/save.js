import { useBlockProps } from '@wordpress/block-editor';
import { renderHelperText, getFieldId } from '../../utils/field-helpers';

export default function Save( { attributes } ) {
	const { fieldName, label, required, placeholder, helperText, rows, fieldKey } =
		attributes;

	// Generate automatic fieldName if not provided
	const generateFieldName = () => {
		if ( fieldName && typeof fieldName === 'string' && fieldName.trim() !== '' ) {
			return fieldName.trim();
		}
		if ( fieldKey && typeof fieldKey === 'string' && fieldKey.trim() !== '' ) {
			return fieldKey.trim();
		}
		if ( label && typeof label === 'string' && label.trim() !== '' ) {
			const sanitized = label.trim()
				.toLowerCase()
				.replace( /[^a-z0-9]/g, '_' )
				.replace( /_+/g, '_' )
				.replace( /^_|_$/g, '' )
				.substring( 0, 30 );
			return sanitized || 'field_' + Date.now();
		}
		return 'field_' + Date.now();
	};

	const normalizedFieldName = generateFieldName();

	const blockProps = useBlockProps.save( {
		className: 'form-group eipsi-field eipsi-textarea-field',
		'data-field-name': normalizedFieldName,
		'data-required': required ? 'true' : 'false',
		'data-field-type': 'textarea',
	} );

	const inputId = getFieldId( normalizedFieldName );
	const rowsValue = Number( rows ) || 4;

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
			<textarea
				name={ normalizedFieldName }
				id={ inputId }
				placeholder={ placeholder || '' }
				required={ required }
				rows={ rowsValue }
				data-required={ required ? 'true' : 'false' }
				data-field-type="textarea"
			/>
			{ renderHelperText( helperText ) }
			<div className="form-error" aria-live="polite" />
		</div>
	);
}
