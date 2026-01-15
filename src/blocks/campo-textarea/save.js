import { useBlockProps } from '@wordpress/block-editor';

const renderHelperText = ( text ) => {
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

const getFieldId = ( fieldName ) => {
	if ( ! fieldName || fieldName.trim() === '' ) {
		return undefined;
	}

	const normalized = fieldName.trim().replace( /\s+/g, '-' );
	const sanitized = normalized.replace( /[^a-zA-Z0-9_-]/g, '-' );

	return `field-${ sanitized }`;
};

export default function Save( { attributes } ) {
	const { fieldName, label, required, placeholder, helperText, rows } =
		attributes;

	const normalizedFieldName =
		fieldName && typeof fieldName === 'string' && fieldName.trim() !== ''
			? fieldName.trim()
			: undefined;

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
