import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, TextareaControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import FieldSettings from '../../components/FieldSettings';
import ConditionalLogicControl from '../../components/ConditionalLogicControl';
import { parseOptions, normalizeLineEndings } from '../../utils/optionParser';

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

const getFieldId = ( fieldName, suffix = '' ) => {
	if ( ! fieldName || fieldName.trim() === '' ) {
		return undefined;
	}

	const normalized = fieldName.trim().replace( /\s+/g, '-' );
	const sanitized = normalized.replace( /[^a-zA-Z0-9_-]/g, '-' );

	return suffix ? `field-${ sanitized }-${ suffix }` : `field-${ sanitized }`;
};

export default function Edit( { attributes, setAttributes, clientId } ) {
	const {
		fieldName,
		label,
		required,
		helperText,
		options,
		conditionalLogic,
	} = attributes;

	const normalizedFieldName =
		fieldName && fieldName.trim() !== '' ? fieldName.trim() : undefined;

	const hasConditionalLogic =
		conditionalLogic &&
		( Array.isArray( conditionalLogic )
			? conditionalLogic.length > 0
			: conditionalLogic.enabled &&
			  conditionalLogic.rules &&
			  conditionalLogic.rules.length > 0 );

	const blockProps = useBlockProps( {
		className: 'form-group eipsi-field eipsi-checkbox-field',
		'data-field-name': normalizedFieldName,
		'data-required': required ? 'true' : 'false',
		'data-field-type': 'checkbox',
		'data-conditional-logic': hasConditionalLogic ? 'true' : undefined,
	} );

	const displayLabel =
		label && label.trim() !== ''
			? label
			: __( 'Campo de selección múltiple', 'eipsi-forms' );
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
					title={ __( 'Checkbox Options', 'eipsi-forms' ) }
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
					<ul className="checkbox-list">
						{ optionsArray.length > 0 ? (
							optionsArray.map( ( option, index ) => {
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
												disabled
											/>
											<span className="checkbox-label-text">
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
