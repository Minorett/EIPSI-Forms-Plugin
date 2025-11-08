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

export default function Save( { attributes } ) {
	const { fieldName, label, required, placeholder, helperText } = attributes;

	const normalizedFieldName =
		fieldName && fieldName.trim() !== '' ? fieldName.trim() : undefined;

	const blockProps = useBlockProps.save( {
		className: 'form-group eipsi-field eipsi-description-field',
		'data-field-name': normalizedFieldName,
		'data-required': required ? 'true' : 'false',
		'data-field-type': 'description',
	} );

	return (
		<div { ...blockProps }>
			{ label && (
				<label className={ required ? 'required' : undefined }>
					{ label }
				</label>
			) }
			{ placeholder && (
				<p className="description-placeholder">{ placeholder }</p>
			) }
			{ renderHelperText( helperText ) }
			<div className="form-error" aria-live="polite" />
		</div>
	);
}
