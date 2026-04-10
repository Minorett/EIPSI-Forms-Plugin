import { useBlockProps } from '@wordpress/block-editor';
import { parseOptions } from '../../utils/optionParser';
import { renderHelperText, getFieldId } from '../../utils/field-helpers';

export default function Save( { attributes } ) {
	const {
		fieldName,
		label,
		required,
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
		className: 'form-group eipsi-field eipsi-checkbox-field',
		'data-field-name': normalizedFieldName,
		'data-required': required ? 'true' : 'false',
		'data-field-type': 'checkbox',
		'data-conditional-logic': conditionalLogic
			? JSON.stringify( conditionalLogic )
			: undefined,
	} );

	const optionsArray = parseOptions( options );

	return (
		<div { ...blockProps }>
			<fieldset>
				{ label && (
					<legend className={ required ? 'required' : undefined }>
						{ label }
					</legend>
				) }
				<ul className="checkbox-list">
					{ optionsArray.map( ( option, index ) => {
						const checkboxId = getFieldId(
							normalizedFieldName,
							index.toString()
						);
						return (
							<li key={ index }>
								<label
									htmlFor={ checkboxId }
									className="checkbox-label-wrapper"
								>
									<input
										type="checkbox"
										name={ `${ normalizedFieldName }[]` }
										id={ checkboxId }
										value={ option }
										data-required={
											required ? 'true' : 'false'
										}
										data-field-type="checkbox"
									/>
									<span className="checkbox-label-text">
										{ option }
									</span>
								</label>
							</li>
						);
					} ) }
				</ul>
				{ renderHelperText( helperText ) }
				<div className="form-error" aria-live="polite" />
			</fieldset>
		</div>
	);
}
