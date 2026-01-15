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
	} = attributes;

	const normalizedFieldName =
		fieldName && typeof fieldName === 'string' && fieldName.trim() !== ''
			? fieldName.trim()
			: undefined;

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
