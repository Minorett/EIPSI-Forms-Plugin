import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, TextareaControl } from '@wordpress/components';
import { useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import FieldSettings from '../../components/FieldSettings';
import ConditionalLogicControl from '../../components/ConditionalLogicControl';
import { parseOptions, normalizeLineEndings } from '../../utils/optionParser';
import { renderHelperText, getFieldId } from '../../utils/field-helpers';

export default function Edit( { attributes, setAttributes, clientId } ) {
	const {
		fieldKey,
		fieldName,
		label,
		required,
		helperText,
		options,
		conditionalLogic,
	} = attributes;

	useEffect( () => {
		if ( ! fieldKey ) {
			const generatedKey = `radio-${ clientId.replace(
				/[^a-zA-Z0-9]/g,
				''
			) }`;
			setAttributes( { fieldKey: generatedKey } );
		}
	}, [ fieldKey, clientId, setAttributes ] );

	const effectiveFieldName =
		fieldName && typeof fieldName === 'string' && fieldName.trim() !== ''
			? fieldName.trim()
			: fieldKey;

	const normalizedFieldName = effectiveFieldName;

	const hasConditionalLogic =
		conditionalLogic &&
		( Array.isArray( conditionalLogic )
			? conditionalLogic.length > 0
			: conditionalLogic.enabled &&
			  conditionalLogic.rules &&
			  conditionalLogic.rules.length > 0 );

	const blockProps = useBlockProps( {
		className: 'form-group eipsi-field eipsi-radio-field',
		'data-field-name': normalizedFieldName || undefined,
		'data-required': required ? 'true' : 'false',
		'data-field-type': 'radio',
		'data-conditional-logic': hasConditionalLogic ? 'true' : undefined,
	} );

	const displayLabel =
		label && typeof label === 'string' && label.trim() !== ''
			? label
			: __( 'Campo de opciones', 'eipsi-forms' );
	const optionsArray = parseOptions( options );

	return (
		<>
			<InspectorControls>
				<FieldSettings
					attributes={ attributes }
					setAttributes={ setAttributes }
					showPlaceholder={ false }
				/>

				<ConditionalLogicControl
					attributes={ attributes }
					setAttributes={ setAttributes }
					options={ optionsArray }
					clientId={ clientId }
				/>

				<PanelBody
					title={ __( 'Radio Options', 'eipsi-forms' ) }
					initialOpen={ true }
				>
					<TextareaControl
						label={ __(
							'Options (separated by semicolon)',
							'eipsi-forms'
						) }
						value={ options || '' }
						onChange={ ( value ) => {
							setAttributes( {
								options: normalizeLineEndings( value ),
							} );
						} }
						help={ __(
							'Separá las opciones con punto y coma (;). Las comas dentro de cada opción son totalmente seguras. Formatos anteriores (líneas o comas) siguen funcionando.',
							'eipsi-forms'
						) }
						placeholder={
							'Sí, absolutamente; Sí, pero no tan frecuente; No, no ocurre a menudo; Nunca'
						}
						rows={ 8 }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<fieldset>
					<legend className={ required ? 'required' : undefined }>
						{ displayLabel }
					</legend>
					<ul className="radio-list">
						{ optionsArray.length > 0 ? (
							optionsArray.map( ( option, index ) => {
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
												disabled
											/>
											<span className="radio-label-text">
												{ option }
											</span>
										</label>
									</li>
								);
							} )
						) : (
							<li className="empty-state">
								{ __(
									'Add options in the sidebar',
									'eipsi-forms'
								) }
							</li>
						) }
					</ul>
					{ renderHelperText( helperText ) }
					<div className="form-error" aria-live="polite" />
				</fieldset>
			</div>
		</>
	);
}
