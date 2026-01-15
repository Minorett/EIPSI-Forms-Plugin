import { useBlockProps } from '@wordpress/block-editor';
import { parseOptions } from '../../utils/optionParser';
import { renderHelperText, getFieldId } from '../../utils/field-helpers';

export default function Save( { attributes } ) {
	const {
		fieldKey,
		fieldName,
		label,
		required,
		helperText,
		options,
		conditionalLogic,
	} = attributes;

	const effectiveFieldName =
		fieldName && typeof fieldName === 'string' && fieldName.trim() !== ''
			? fieldName.trim()
			: fieldKey;

	const normalizedFieldName = effectiveFieldName;

	const blockProps = useBlockProps.save( {
		className: 'form-group eipsi-field eipsi-radio-field',
		'data-field-name': normalizedFieldName || undefined,
		'data-required': required ? 'true' : 'false',
		'data-field-type': 'radio',
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
				<ul className="radio-list">
					{ optionsArray.map( ( option, index ) => {
						const radioId = getFieldId(
							normalizedFieldName,
							index.toString()
						);
						return (
							<li key={ index }>
								<label
									htmlFor={ radioId }
									className="radio-label-wrapper"
								>
									<input
										type="radio"
										name={ normalizedFieldName }
										id={ radioId }
										value={ option }
										required={ required }
										data-required={
											required ? 'true' : 'false'
										}
										data-field-type="radio"
									/>
									<span className="radio-label-text">
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
