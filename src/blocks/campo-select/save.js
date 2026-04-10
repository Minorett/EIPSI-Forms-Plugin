import { useBlockProps } from '@wordpress/block-editor';
import { parseOptions } from '../../utils/optionParser';
import { renderHelperText, getFieldId } from '../../utils/field-helpers';

export default function Save( { attributes } ) {
	const {
		fieldName,
		label,
		required,
		placeholder,
		helperText,
		options,
		conditionalLogic,
		fieldKey,
	} = attributes;

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
		className: 'form-group eipsi-field eipsi-select-field',
		'data-field-name': normalizedFieldName,
		'data-required': required ? 'true' : 'false',
		'data-field-type': 'select',
		'data-conditional-logic': conditionalLogic
			? JSON.stringify( conditionalLogic )
			: undefined,
	} );

	const inputId = getFieldId( normalizedFieldName );
	const optionsArray = parseOptions( options );

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
			<select
				name={ normalizedFieldName }
				id={ inputId }
				required={ required }
				data-required={ required ? 'true' : 'false' }
				data-field-type="select"
			>
				{ placeholder &&
					typeof placeholder === 'string' &&
					placeholder.trim() !== '' && (
						<option value="" disabled selected>
							{ placeholder }
						</option>
					) }
				{ optionsArray.map( ( option, index ) => (
					<option key={ index } value={ option }>
						{ option }
					</option>
				) ) }
			</select>
			{ renderHelperText( helperText ) }
			<div className="form-error" aria-live="polite" />
		</div>
	);
}
