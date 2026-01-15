import { useBlockProps } from '@wordpress/block-editor';
import { renderHelperText, getFieldId } from '../../utils/field-helpers';

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
