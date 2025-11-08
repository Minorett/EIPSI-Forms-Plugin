import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	PanelBody,
	TextareaControl,
	ToggleControl,
} from '@wordpress/components';
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

const getFieldId = ( fieldName, suffix = '' ) => {
	if ( ! fieldName || fieldName.trim() === '' ) {
		return undefined;
	}

	const normalized = fieldName.trim().replace( /\s+/g, '-' );
	const sanitized = normalized.replace( /[^a-zA-Z0-9_-]/g, '-' );

	return suffix ? `field-${ sanitized }-${ suffix }` : `field-${ sanitized }`;
};

const parseOptions = ( optionsString ) => {
	if ( ! optionsString || optionsString.trim() === '' ) {
		return [];
	}

	return optionsString
		.split( ',' )
		.map( ( option ) => option.trim() )
		.filter( ( option ) => option !== '' );
};

export default function Edit( { attributes, setAttributes } ) {
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

	const blockProps = useBlockProps( {
		className: 'form-group eipsi-field eipsi-checkbox-field',
		'data-field-name': normalizedFieldName,
		'data-required': required ? 'true' : 'false',
		'data-field-type': 'checkbox',
	} );

	const displayLabel =
		label && label.trim() !== ''
			? label
			: __( 'Campo de selección múltiple', 'vas-dinamico-forms' );
	const optionsArray = parseOptions( options );

	// This will be handled by the ConditionalLogicControl component
	const getTotalPages = () => {
		return 0; // Placeholder, actual logic in ConditionalLogicControl
	};

	return (
		<>
			<InspectorControls>
				<FieldSettings
					attributes={ attributes }
					setAttributes={ setAttributes }
					showPlaceholder={ false }
				/>

				<PanelBody
					title={ __( 'Lógica Condicional', 'vas-dinamico-forms' ) }
					initialOpen={ false }
				>
					<ToggleControl
						label={ __(
							'Habilitar lógica condicional',
							'vas-dinamico-forms'
						) }
						checked={ conditionalLogic?.enabled || false }
						onChange={ ( enabled ) => {
							if ( enabled ) {
								setAttributes( {
									conditionalLogic: {
										enabled: true,
										rules: [],
									},
								} );
							} else {
								setAttributes( {
									conditionalLogic: undefined,
								} );
							}
						} }
						help={ __(
							'Permite redirigir a diferentes páginas según las opciones seleccionadas.',
							'vas-dinamico-forms'
						) }
					/>
				</PanelBody>

				<ConditionalLogicControl
					attributes={ attributes }
					setAttributes={ setAttributes }
					options={ optionsArray }
					totalPages={ getTotalPages() }
				/>

				<PanelBody
					title={ __( 'Checkbox Options', 'vas-dinamico-forms' ) }
					initialOpen={ true }
				>
					<TextareaControl
						label={ __(
							'Options (comma-separated)',
							'vas-dinamico-forms'
						) }
						value={ options || '' }
						onChange={ ( value ) =>
							setAttributes( { options: value } )
						}
						help={ __(
							'Enter options separated by commas (e.g., Option 1, Option 2, Option 3)',
							'vas-dinamico-forms'
						) }
						rows={ 5 }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<label className={ required ? 'required' : undefined }>
					{ displayLabel }
				</label>
				<ul className="checkbox-list">
					{ optionsArray.length > 0 ? (
						optionsArray.map( ( option, index ) => {
							const checkboxId = getFieldId(
								normalizedFieldName,
								index.toString()
							);
							return (
								<li key={ index }>
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
									<label htmlFor={ checkboxId }>
										{ option }
									</label>
								</li>
							);
						} )
					) : (
						<li className="empty-state">
							{ __(
								'Add options in the sidebar',
								'vas-dinamico-forms'
							) }
						</li>
					) }
				</ul>
				{ renderHelperText( helperText ) }
				<div className="form-error" aria-live="polite" />
			</div>
		</>
	);
}
