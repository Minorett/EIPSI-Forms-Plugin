import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, TextareaControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import FieldSettings from '../../components/FieldSettings';
import ConditionalLogicControl from '../../components/ConditionalLogicControl';

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

const parseOptions = ( optionsString ) => {
	if ( ! optionsString || optionsString.trim() === '' ) {
		return [];
	}

	// Detectar formato: newline (estándar) o comma (legacy)
	// Si contiene \n, usar newline; si no, usar comma (backward compatibility)
	const separator = optionsString.includes( '\n' ) ? '\n' : ',';

	return optionsString
		.split( separator )
		.map( ( option ) => option.trim() )
		.filter( ( option ) => option !== '' );
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
							'Options (one per line)',
							'vas-dinamico-forms'
						) }
						value={
							options ? parseOptions( options ).join( '\n' ) : ''
						}
						onChange={ ( value ) => {
							// Dividir por newline, limpiar y filtrar
							const cleanedOptions = value
								.split( '\n' )
								.map( ( opt ) => opt.trim() )
								.filter( ( opt ) => opt !== '' );
							setAttributes( {
								options: cleanedOptions.join( '\n' ),
							} );
						} }
						help={ __(
							'Enter one option per line. Options can contain commas, periods, quotes, etc.',
							'vas-dinamico-forms'
						) }
						placeholder={
							'Sí, absolutamente\nSí, pero no tan frecuente\nNo, no ocurre a menudo\nNunca'
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
