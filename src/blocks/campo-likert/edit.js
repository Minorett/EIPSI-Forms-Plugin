import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { useEffect, useState } from '@wordpress/element';
import {
	PanelBody,
	TextControl,
	TextareaControl,
	ToggleControl,
	SelectControl,
	BaseControl,
	Button,
	Notice,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ConditionalLogicControl from '../../components/ConditionalLogicControl';
import { parseOptions, normalizeLineEndings } from '../../utils/optionParser';
import {
	getPresetByKey,
	applyPreset,
	validateLabels,
} from '../../components/LikertPresets';

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
		fieldKey,
		fieldName,
		label,
		required,
		helperText,
		minValue = 0,
		reversed = false,
		labels,
		conditionalLogic,
		scaleVariation = 'custom',
	} = attributes;

	// Calcular el máximo basado en la fórmula: maxValue = minValue + (labelCount - 1)
	const calculateMaxValue = ( labelsString, currentMinValue ) => {
		if ( ! labelsString || labelsString.trim() === '' ) {
			return currentMinValue; // Si no hay labels, max = min
		}
		const labelArray = labelsString
			.split( ';' )
			.map( ( labelText ) => labelText.trim() )
			.filter( ( labelText ) => labelText !== '' );
		const labelCount = labelArray.length > 0 ? labelArray.length : 1;
		return currentMinValue + ( labelCount - 1 );
	};

	// Calcular el máximo actual
	const maxValue = calculateMaxValue( labels, minValue );

	// State for preset management
	const [ selectedPreset, setSelectedPreset ] = useState( scaleVariation );
	const [ isUsingPreset, setIsUsingPreset ] = useState(
		scaleVariation !== 'custom'
	);

	// Generate fieldKey from clientId if not set
	useEffect( () => {
		if ( ! fieldKey ) {
			const generatedKey = `likert-${ clientId.replace(
				/[^a-zA-Z0-9]/g,
				''
			) }`;
			setAttributes( { fieldKey: generatedKey } );
		}
	}, [ fieldKey, clientId, setAttributes ] );

	// Handle preset selection
	const handlePresetChange = ( newPresetKey ) => {
		setSelectedPreset( newPresetKey );
		const isCustom = newPresetKey === 'custom';
		setIsUsingPreset( ! isCustom );

		// Update scaleVariation attribute
		setAttributes( { scaleVariation: newPresetKey } );

		// If not custom, apply the preset
		if ( ! isCustom ) {
			const preset = getPresetByKey( newPresetKey );
			if ( preset ) {
				const presetAttributes = applyPreset( preset );
				setAttributes( presetAttributes );
			}
		}
	};

	// Handle custom mode toggle
	const handleCustomModeToggle = ( useCustom ) => {
		setIsUsingPreset( ! useCustom );
		if ( useCustom ) {
			setAttributes( { scaleVariation: 'custom' } );
		} else if ( selectedPreset !== 'custom' ) {
			// Reapply current preset
			const preset = getPresetByKey( selectedPreset );
			if ( preset ) {
				const presetAttributes = applyPreset( preset );
				setAttributes( presetAttributes );
			}
		}
	};

	const effectiveFieldName =
		fieldName && fieldName.trim() !== '' ? fieldName.trim() : fieldKey;

	const hasConditionalLogic =
		conditionalLogic &&
		( Array.isArray( conditionalLogic )
			? conditionalLogic.length > 0
			: conditionalLogic.enabled &&
			  conditionalLogic.rules &&
			  conditionalLogic.rules.length > 0 );

	const blockProps = useBlockProps( {
		className: `form-group eipsi-field eipsi-likert-field ${
			isUsingPreset ? 'using-preset' : 'custom-mode'
		}`,
		'data-field-name': effectiveFieldName,
		'data-required': required ? 'true' : 'false',
		'data-field-type': 'likert',
		'data-min': minValue,
		'data-max': maxValue,
		'data-scale-variation': scaleVariation,
		'data-conditional-logic': hasConditionalLogic ? 'true' : undefined,
	} );

	const displayLabel =
		label && label.trim() !== ''
			? label
			: __( 'Escala Likert', 'eipsi-forms' );

	const labelArray = labels ? parseOptions( labels ) : [];

	const scale = [];
	for ( let i = minValue; i <= maxValue; i++ ) {
		scale.push( i );
	}

	// Validate labels with preset context
	const validationResult = validateLabels( labels, minValue, maxValue );

	const likertOptions = scale.map( ( value, index ) => {
		const baseValue = value.toString();
		const optionLabel = labelArray[ index ]
			? `${ baseValue } – ${ labelArray[ index ] }`
			: baseValue;
		return {
			label: optionLabel,
			value: baseValue,
		};
	} );

	// Get current preset info
	const currentPreset = getPresetByKey( scaleVariation );

	// Preset options for SelectControl
	const presetOptions = [
		{ label: '🔧 Personalizado', value: 'custom' },
		{
			label: '🤝 Escala de Acuerdo (5 puntos)',
			value: 'likert5-agreement',
		},
		{
			label: '😊 Escala de Satisfacción (5 puntos)',
			value: 'likert5-satisfaction',
		},
		{
			label: '📊 Escala de Frecuencia (5 puntos)',
			value: 'likert5-frequency',
		},
		{
			label: '🤝 Escala de Acuerdo (7 puntos)',
			value: 'likert7-agreement',
		},
		{
			label: '😊 Escala de Satisfacción (7 puntos)',
			value: 'likert7-satisfaction',
		},
		{
			label: '⚖️ Escala de Acuerdo (4 puntos)',
			value: 'likert4-agreement',
		},
	];

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Field Settings', 'eipsi-forms' ) }
					initialOpen={ true }
				>
					<TextControl
						label={ __( 'Field Name / Slug', 'eipsi-forms' ) }
						value={ fieldName || '' }
						onChange={ ( value ) =>
							setAttributes( { fieldName: value } )
						}
						help={ __(
							'Used as the input name attribute (e.g., satisfaction)',
							'eipsi-forms'
						) }
					/>
					<TextControl
						label={ __( 'Label', 'eipsi-forms' ) }
						value={ label || '' }
						onChange={ ( value ) =>
							setAttributes( { label: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Required field', 'eipsi-forms' ) }
						checked={ !! required }
						onChange={ ( value ) =>
							setAttributes( { required: !! value } )
						}
					/>
					<TextareaControl
						label={ __( 'Helper text', 'eipsi-forms' ) }
						value={ helperText || '' }
						onChange={ ( value ) =>
							setAttributes( { helperText: value } )
						}
						help={ __(
							'Displayed below the field to provide additional guidance.',
							'eipsi-forms'
						) }
					/>
				</PanelBody>

				<PanelBody
					title={ __(
						'📊 Variación de Escala Likert',
						'eipsi-forms'
					) }
					initialOpen={ true }
				>
					<SelectControl
						label={ __(
							'Seleccionar Escala Predefinida',
							'eipsi-forms'
						) }
						value={ selectedPreset }
						options={ presetOptions }
						onChange={ handlePresetChange }
						help={ __(
							'Las escalas predefinidas configuran automáticamente min/max/etiquetas. Selecciona "Personalizado" para configuración manual.',
							'eipsi-forms'
						) }
					/>

					{ currentPreset &&
						currentPreset.name !== 'Personalizado' && (
							<div
								className="preset-description"
								style={ {
									padding: '12px',
									backgroundColor: `${ currentPreset.color }15`,
									borderLeft: `4px solid ${ currentPreset.color }`,
									borderRadius: '4px',
									margin: '12px 0',
								} }
							>
								<div
									style={ {
										display: 'flex',
										alignItems: 'center',
										marginBottom: '8px',
									} }
								>
									<span
										style={ {
											fontSize: '18px',
											marginRight: '8px',
										} }
									>
										{ currentPreset.icon }
									</span>
									<strong>{ currentPreset.name }</strong>
								</div>
								<p
									style={ {
										margin: '0',
										fontSize: '13px',
										color: '#555',
										lineHeight: '1.4',
									} }
								>
									{ currentPreset.description }
								</p>
								<div
									style={ {
										marginTop: '8px',
										fontSize: '12px',
										color: '#666',
										display: 'flex',
										justifyContent: 'space-between',
									} }
								>
									<span>
										📏{ ' ' }
										{ currentPreset.maxValue -
											currentPreset.minValue +
											1 }{ ' ' }
										puntos
									</span>
									<span
										style={ {
											padding: '2px 6px',
											backgroundColor:
												currentPreset.color,
											color: 'white',
											borderRadius: '3px',
											fontSize: '10px',
										} }
									>
										{ currentPreset.type.toUpperCase() }
									</span>
								</div>
							</div>
						) }

					<ToggleControl
						label={ __(
							'Usar valores personalizados',
							'eipsi-forms'
						) }
						checked={ ! isUsingPreset }
						onChange={ handleCustomModeToggle }
						help={ __(
							'Desactiva para usar la escala predefinida. Actívalo para configurar manualmente min/max/etiquetas.',
							'eipsi-forms'
						) }
					/>
				</PanelBody>

				<PanelBody
					title={ __( '⚙️ Configuración de Escala', 'eipsi-forms' ) }
					initialOpen={ ! isUsingPreset }
				>
					<TextareaControl
						label={ __(
							'Etiquetas (separadas por punto y coma)',
							'eipsi-forms'
						) }
						value={ labels || '' }
						onChange={ ( value ) =>
							setAttributes( {
								labels: normalizeLineEndings( value ),
							} )
						}
						disabled={ isUsingPreset }
						help={ __(
							'Etiquetas opcionales para cada punto de la escala (deben coincidir con la cantidad de puntos). Separá con punto y coma (;). Formatos anteriores (líneas o comas) siguen funcionando.',
							'eipsi-forms'
						) }
						placeholder={ 'Nada; Poco; Moderado; Bastante; Mucho' }
					/>

					{ labels && labels.trim() !== '' ? (
						<BaseControl
							id="eipsi-scale-config"
							label={ __(
								'⚙️ Configuración de Escala',
								'eipsi-forms'
							) }
							help={ __(
								'El valor máximo se calcula automáticamente',
								'eipsi-forms'
							) }
						>
							<div
								style={ {
									display: 'flex',
									gap: '8px',
									alignItems: 'center',
									flexWrap: 'wrap',
								} }
							>
								<TextControl
									type="number"
									label={ __( 'Desde', 'eipsi-forms' ) }
									value={ minValue }
									onChange={ ( val ) =>
										setAttributes( {
											minValue: parseInt( val ) || 0,
										} )
									}
									style={ { width: '80px' } }
									disabled={ isUsingPreset }
								/>
								<span style={ { fontWeight: 'bold' } }>a</span>
								<div
									style={ {
										padding: '8px 12px',
										background: '#f5f5f5',
										borderRadius: '4px',
										fontWeight: '600',
										minWidth: '40px',
										textAlign: 'center',
									} }
								>
									{ maxValue }
								</div>
								<Button
									variant={
										reversed ? 'primary' : 'secondary'
									}
									icon="shuffle"
									onClick={ () =>
										setAttributes( {
											reversed: ! reversed,
										} )
									}
									title={ __(
										'Invertir orden (de mayor a menor)',
										'eipsi-forms'
									) }
									disabled={ isUsingPreset }
									style={ {
										transform: reversed
											? 'scaleX(-1)'
											: 'none',
										transition: 'transform 0.2s ease',
									} }
								/>
							</div>
							{ reversed && (
								<div
									style={ {
										marginTop: '8px',
										padding: '8px 12px',
										backgroundColor: '#e6f4ff',
										border: '1px solid #b3d7ff',
										borderRadius: '4px',
										fontSize: '12px',
										color: '#0052cc',
										display: 'flex',
										alignItems: 'center',
										gap: '6px',
									} }
								>
									<span>🔄</span>
									<span>
										Escala invertida: mostrando de{ ' ' }
										<strong>{ maxValue }</strong> a{ ' ' }
										<strong>{ minValue }</strong>
									</span>
								</div>
							) }
						</BaseControl>
					) : (
						<Notice status="info" isDismissible={ false }>
							{ __(
								'Agregá etiquetas para calcular automáticamente el rango.',
								'eipsi-forms'
							) }
						</Notice>
					) }

					{ validationResult && ! validationResult.isValid && (
						<Notice status="warning" isDismissible={ false }>
							{ validationResult.message }
						</Notice>
					) }

					{ isUsingPreset && (
						<Notice status="info" isDismissible={ false }>
							<strong>Modo Predefinido Activo:</strong> La
							configuración de la escala está bloqueada porque
							estás usando &quot;{ currentPreset?.name }&quot;.
							Desactiva &quot;Usar valores personalizados&quot;
							para editar manualmente.
						</Notice>
					) }
				</PanelBody>

				<ConditionalLogicControl
					attributes={ attributes }
					setAttributes={ setAttributes }
					options={ likertOptions }
					clientId={ clientId }
				/>
			</InspectorControls>

			<div { ...blockProps }>
				{ label && (
					<label
						htmlFor={
							effectiveFieldName
								? `${ getFieldId( effectiveFieldName ) }-label`
								: undefined
						}
						className={ required ? 'required' : undefined }
					>
						{ displayLabel }
					</label>
				) }
				<div
					className={ `likert-scale likert-preview${
						reversed ? ' reversed' : ''
					}` }
					data-scale={ `${ minValue }-${ maxValue }` }
					data-reversed={ reversed ? 'true' : 'false' }
				>
					<ul className="likert-list">
						{ scale.map( ( value, index ) => {
							const optionId = `${ getFieldId(
								effectiveFieldName
							) }-${ value }`;
							const optionLabel =
								labelArray[ index ] || value.toString();

							return (
								<li key={ value } className="likert-item">
									<label
										htmlFor={ optionId }
										className="likert-label-wrapper"
									>
										<input
											type="radio"
											name={ effectiveFieldName }
											id={ optionId }
											value={ value }
											required={ required }
											data-required={
												required ? 'true' : 'false'
											}
											disabled
										/>
										<span className="likert-label-text">
											{ optionLabel }
										</span>
									</label>
								</li>
							);
						} ) }
					</ul>
				</div>
				{ renderHelperText( helperText ) }
				<div className="form-error" aria-live="polite" />
			</div>
		</>
	);
}
