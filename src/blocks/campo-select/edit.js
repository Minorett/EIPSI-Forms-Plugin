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

const getFieldId = ( fieldName ) => {
	if ( ! fieldName || fieldName.trim() === '' ) {
		return undefined;
	}

	const normalized = fieldName.trim().replace( /\s+/g, '-' );
	const sanitized = normalized.replace( /[^a-zA-Z0-9_-]/g, '-' );

	return `field-${ sanitized }`;
};

export default function Edit( { attributes, setAttributes, clientId } ) {
	const {
		fieldName,
		label,
		required,
		placeholder,
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
		className: 'form-group eipsi-field eipsi-select-field',
		'data-field-name': normalizedFieldName,
		'data-required': required ? 'true' : 'false',
		'data-field-type': 'select',
		'data-conditional-logic': hasConditionalLogic ? 'true' : undefined,
	} );

	const inputId = getFieldId( normalizedFieldName );
	const displayLabel =
		label && label.trim() !== ''
			? label
			: __( 'Campo de selección', 'vas-dinamico-forms' );
	const optionsArray = parseOptions( options );

	return (
		<>
			<InspectorControls>
				<FieldSettings
					attributes={ attributes }
					setAttributes={ setAttributes }
				/>

				<ConditionalLogicControl
					attributes={ attributes }
					setAttributes={ setAttributes }
					options={ optionsArray }
					clientId={ clientId }
				/>

				<PanelBody
					title={ __( 'Select Options', 'vas-dinamico-forms' ) }
					initialOpen={ true }
				>
					<TextareaControl
						label={ __(
							'Options (separated by semicolon)',
							'vas-dinamico-forms'
						) }
						value={ options || '' }
						onChange={ ( value ) => {
							setAttributes( {
								options: normalizeLineEndings( value ),
							} );
						} }
						help={ __(
							'Separá las opciones con punto y coma (;). Las comas dentro de cada opción son totalmente seguras. Formatos anteriores (líneas o comas) siguen funcionando.',
							'vas-dinamico-forms'
						) }
						placeholder={
							'Sí, absolutamente; Sí, pero no tan frecuente; No, no ocurre a menudo; Nunca'
						}
						rows={ 8 }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<label
					className={ required ? 'required' : undefined }
					htmlFor={ inputId }
				>
					{ displayLabel }
				</label>
				<select
					name={ normalizedFieldName }
					id={ inputId }
					required={ required }
					data-required={ required ? 'true' : 'false' }
					data-field-type="select"
					disabled
				>
					{ placeholder && placeholder.trim() !== '' && (
						<option value="">{ placeholder }</option>
					) }
					{ optionsArray.length > 0 ? (
						optionsArray.map( ( option, index ) => (
							<option key={ index } value={ option }>
								{ option }
							</option>
						) )
					) : (
						<option value="">
							{ __(
								'Add options in the sidebar',
								'vas-dinamico-forms'
							) }
						</option>
					) }
				</select>
				{ renderHelperText( helperText ) }
				<div className="form-error" aria-live="polite" />
			</div>
		</>
	);
}
