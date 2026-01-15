import { useBlockProps } from '@wordpress/block-editor';
import { renderHelperText, getFieldId } from '../../utils/field-helpers';

export default function Save( { attributes } ) {
	const { fieldName, label, required, placeholder, helperText, fieldType } =
		attributes;

	const normalizedFieldName =
		fieldName && typeof fieldName === 'string' && fieldName.trim() !== ''
			? fieldName.trim()
			: undefined;

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
