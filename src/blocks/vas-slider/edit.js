import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	TextareaControl,
	ToggleControl,
	RangeControl,
	SelectControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis -- UnitControl is the standard component for this use case
	__experimentalUnitControl as UnitControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis -- NumberControl is the standard component for numeric input
	__experimentalNumberControl as NumberControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import ConditionalLogicControl from '../../components/ConditionalLogicControl';
import { parseOptions, normalizeLineEndings } from '../../utils/optionParser';
import {
	alignmentInternalToDisplay,
	alignmentDisplayToInternal,
	calculateLabelStyle,
} from './calculateLabelSpacing';

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

const sanitizeNumber = ( value, fallback = 0 ) => {
	return typeof value === 'number' && ! Number.isNaN( value )
		? value
		: fallback;
};

const clampValueToRange = ( value, min, max ) => {
	if ( typeof value !== 'number' || Number.isNaN( value ) ) {
		return ( min + max ) / 2;
	}

	if ( max <= min ) {
		return min;
	}

	return Math.min( Math.max( value, min ), max );
};

export default function Edit( { attributes, setAttributes, clientId } ) {
	const {
		fieldName,
		label,
		required,
		helperText,
		labels,
		minValue,
		maxValue,
		step,
		initialValue,
		showValue,
		labelFontSize,
		valueFontSize,
		boldLabels,
		showCurrentValue,
		valuePosition,
		labelAlignment,
	} = attributes;

	const normalizedFieldName =
		fieldName && fieldName.trim() !== '' ? fieldName.trim() : undefined;

	const blockProps = useBlockProps( {
		className: `form-group eipsi-field eipsi-vas-slider-field${
			valuePosition === 'below' ? ' vas-value-below' : ''
		}`,
		'data-field-name': normalizedFieldName,
		'data-required': required ? 'true' : 'false',
		'data-field-type': 'vas-slider',
		'data-value-position': valuePosition || 'above',
	} );

	const displayLabel =
		label && label.trim() !== ''
			? label
			: __( 'VAS Slider', 'eipsi-forms' );
	const inputId = getFieldId( normalizedFieldName );

	const safeStep = step && step > 0 ? step : 1;
	const sliderMin = sanitizeNumber( minValue, 0 );
	const sliderMaxCandidate = sanitizeNumber( maxValue, sliderMin + safeStep );
	const sliderMax =
		sliderMaxCandidate > sliderMin
			? sliderMaxCandidate
			: sliderMin + safeStep;

	const [ previewValue, setPreviewValue ] = useState(
		clampValueToRange(
			sanitizeNumber( initialValue, sliderMin ),
			sliderMin,
			sliderMax
		)
	);

	useEffect( () => {
		const safeInitial = sanitizeNumber( initialValue, sliderMin );
		const clampedInitial = clampValueToRange(
			safeInitial,
			sliderMin,
			sliderMax
		);
		if ( clampedInitial !== initialValue ) {
			setAttributes( { initialValue: clampedInitial } );
		}
	}, [ initialValue, sliderMin, sliderMax, setAttributes ] );

	useEffect( () => {
		const safeInitial = sanitizeNumber( initialValue, sliderMin );
		const clampedInitial = clampValueToRange(
			safeInitial,
			sliderMin,
			sliderMax
		);
		if ( clampedInitial !== previewValue ) {
			setPreviewValue( clampedInitial );
		}
	}, [ initialValue, sliderMin, sliderMax, previewValue ] );

	const parsedLabels = labels ? parseOptions( labels ) : [];
	const resolvedLabels =
		parsedLabels.length > 0
			? parsedLabels
			: [ `${ sliderMin }`, `${ sliderMax }` ];

	const shouldShowValue =
		showCurrentValue !== undefined ? showCurrentValue : showValue !== false;
	const valueElementId =
		shouldShowValue && inputId ? `${ inputId }-value` : undefined;

	const handleMinValueChange = ( value ) => {
		const parsedValue = parseFloat( value );
		if ( Number.isNaN( parsedValue ) ) {
			return;
		}

		let nextMax =
			typeof maxValue === 'number' && ! Number.isNaN( maxValue )
				? maxValue
				: parsedValue + safeStep;

		if ( parsedValue >= nextMax ) {
			nextMax = parsedValue + safeStep;
		}

		const nextInitial = clampValueToRange(
			sanitizeNumber( initialValue, parsedValue ),
			parsedValue,
			nextMax
		);
		const nextPreview = clampValueToRange(
			previewValue,
			parsedValue,
			nextMax
		);

		setAttributes( {
			minValue: parsedValue,
			maxValue: nextMax,
			initialValue: nextInitial,
		} );
		setPreviewValue( nextPreview );
	};

	const handleMaxValueChange = ( value ) => {
		const parsedValue = parseFloat( value );
		if ( Number.isNaN( parsedValue ) ) {
			return;
		}

		let nextMin =
			typeof minValue === 'number' && ! Number.isNaN( minValue )
				? minValue
				: parsedValue - safeStep;

		if ( parsedValue <= nextMin ) {
			nextMin = parsedValue - safeStep;
		}

		const nextInitial = clampValueToRange(
			sanitizeNumber( initialValue, nextMin ),
			nextMin,
			parsedValue
		);
		const nextPreview = clampValueToRange(
			previewValue,
			nextMin,
			parsedValue
		);

		setAttributes( {
			minValue: nextMin,
			maxValue: parsedValue,
			initialValue: nextInitial,
		} );
		setPreviewValue( nextPreview );
	};

	const handleStepChange = ( value ) => {
		const parsedValue =
			typeof value === 'number' ? value : parseFloat( value );
		if ( Number.isNaN( parsedValue ) ) {
			return;
		}
		const nextStep = parsedValue > 0 ? parsedValue : 0.1;
		setAttributes( { step: parseFloat( nextStep.toFixed( 2 ) ) } );
	};

	const minControlHelp = __(
		'Debe ser menor que el máximo. Si lo igualás o superás, ajustamos el rango automáticamente.',
		'eipsi-forms'
	);
	const maxControlHelp = __(
		'Debe ser mayor que el mínimo. Si lo igualás o bajás demasiado, movemos el mínimo para mantener el slider usable.',
		'eipsi-forms'
	);

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Field Settings', 'eipsi-forms' ) }
					initialOpen={ true }
				>
					<TextControl
						label={ __(
							'Field Name / Slug',
							'eipsi-forms'
						) }
						value={ fieldName || '' }
						onChange={ ( value ) =>
							setAttributes( { fieldName: value } )
						}
						help={ __(
							'Used as the input name attribute (e.g., pain_level)',
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
					title={ __( 'VAS Slider Options', 'eipsi-forms' ) }
					initialOpen={ true }
				>
					<TextareaControl
						label={ __(
							'Labels (separated by semicolon)',
							'eipsi-forms'
						) }
						value={ labels || '' }
						onChange={ ( value ) =>
							setAttributes( {
								labels: normalizeLineEndings( value ),
							} )
						}
						help={
							<>
								<strong>
									{ __(
										'Ingresá los labels separados por punto y coma (;)',
										'eipsi-forms'
									) }
								</strong>
								<br />
								{ __(
									'Ejemplo: Nada; Poco; Bastante; Mucho; Extremadamente intenso',
									'eipsi-forms'
								) }
								<br />
								<br />
								{ __(
									'¿Querés que un label ocupe dos líneas? Presioná Shift+Enter dentro del label para dividir.',
									'eipsi-forms'
								) }
								<br />
								{ __(
									'Los saltos de línea se respetan exactamente como los escribas.',
									'eipsi-forms'
								) }
							</>
						}
					/>
					<NumberControl
						label={ __( 'Minimum Value', 'eipsi-forms' ) }
						value={ minValue }
						onChange={ handleMinValueChange }
						min={ -1000 }
						max={ 1000 }
						step="any"
						help={ minControlHelp }
					/>
					<NumberControl
						label={ __( 'Maximum Value', 'eipsi-forms' ) }
						value={ maxValue }
						onChange={ handleMaxValueChange }
						min={ -1000 }
						max={ 1000 }
						step="any"
						help={ maxControlHelp }
					/>
					<RangeControl
						label={ __( 'Step', 'eipsi-forms' ) }
						value={ step && step > 0 ? step : 1 }
						onChange={ handleStepChange }
						min={ 0.1 }
						max={ 10 }
						step={ 0.1 }
						help={ __(
							'Increment for each slider movement',
							'eipsi-forms'
						) }
					/>
					<RangeControl
						label={ __( 'Initial Value', 'eipsi-forms' ) }
						value={
							typeof initialValue === 'number'
								? initialValue
								: sliderMin
						}
						onChange={ ( value ) => {
							setAttributes( { initialValue: value } );
							setPreviewValue( value );
						} }
						min={ sliderMin }
						max={ sliderMax }
						step={ safeStep }
						help={ __(
							'Default slider position',
							'eipsi-forms'
						) }
					/>
				</PanelBody>

				<PanelBody
					title={ __( 'Appearance', 'eipsi-forms' ) }
					initialOpen={ true }
				>
					<div className="eipsi-panel-section">
						<h3
							style={ {
								fontSize: '13px',
								fontWeight: 600,
								marginBottom: '12px',
							} }
						>
							{ __( 'Label Appearance', 'eipsi-forms' ) }
						</h3>

						<ToggleControl
							label={ __( 'Bold labels', 'eipsi-forms' ) }
							checked={ boldLabels !== false }
							onChange={ ( value ) =>
								setAttributes( { boldLabels: !! value } )
							}
							help={ __(
								'Make label text bold',
								'eipsi-forms'
							) }
						/>

						<UnitControl
							label={ __( 'Label size', 'eipsi-forms' ) }
							value={ `${ labelFontSize || 16 }px` }
							onChange={ ( value ) => {
								const numValue = parseInt( value, 10 ) || 16;
								setAttributes( { labelFontSize: numValue } );
							} }
							min={ 12 }
							max={ 36 }
							step={ 1 }
							units={ [
								{ value: 'px', label: 'px', default: 16 },
							] }
							isUnitSelectTabbable={ false }
						/>

						<RangeControl
							label={ __(
								'Label Alignment',
								'eipsi-forms'
							) }
							value={ alignmentInternalToDisplay(
								labelAlignment
							) }
							onChange={ ( displayValue ) => {
								const internalValue =
									alignmentDisplayToInternal( displayValue );
								setAttributes( {
									labelAlignment: internalValue,
								} );
							} }
							min={ 0 }
							max={ 100 }
							step={ 1 }
							help={ __(
								'Expand (higher) or compress (lower) label spacing. 0–100 range.',
								'eipsi-forms'
							) }
						/>
					</div>

					<div
						className="eipsi-panel-section"
						style={ {
							marginTop: '20px',
							paddingTop: '20px',
							borderTop: '1px solid #e5e5e5',
						} }
					>
						<h3
							style={ {
								fontSize: '13px',
								fontWeight: 600,
								marginBottom: '12px',
							} }
						>
							{ __( 'Values', 'eipsi-forms' ) }
						</h3>

						<ToggleControl
							label={ __(
								'Show selected value',
								'eipsi-forms'
							) }
							checked={ shouldShowValue }
							onChange={ ( value ) =>
								setAttributes( { showCurrentValue: !! value } )
							}
							help={ __(
								'Show the number selected by the user',
								'eipsi-forms'
							) }
						/>

						{ shouldShowValue && (
							<>
								<SelectControl
									label={ __(
										'Value Position',
										'eipsi-forms'
									) }
									value={ valuePosition || 'above' }
									options={ [
										{
											label: __(
												'Above slider',
												'eipsi-forms'
											),
											value: 'above',
										},
										{
											label: __(
												'Below slider',
												'eipsi-forms'
											),
											value: 'below',
										},
									] }
									onChange={ ( value ) =>
										setAttributes( {
											valuePosition: value,
										} )
									}
								/>

								<UnitControl
									label={ __(
										'Value font size',
										'eipsi-forms'
									) }
									value={ `${ valueFontSize || 36 }px` }
									onChange={ ( value ) => {
										const numValue =
											parseInt( value, 10 ) || 36;
										setAttributes( {
											valueFontSize: numValue,
										} );
									} }
									min={ 16 }
									max={ 72 }
									step={ 1 }
									units={ [
										{
											value: 'px',
											label: 'px',
											default: 36,
										},
									] }
									isUnitSelectTabbable={ false }
								/>
							</>
						) }
					</div>
				</PanelBody>

				<PanelBody
					title={ __( 'Conditional Logic', 'eipsi-forms' ) }
					initialOpen={ false }
				>
					<ConditionalLogicControl
						attributes={ attributes }
						setAttributes={ setAttributes }
						clientId={ clientId }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				{ displayLabel && (
					<label
						className={ required ? 'required' : undefined }
						htmlFor={ inputId }
					>
						{ displayLabel }
					</label>
				) }
				<div
					className={ `vas-slider-container${
						boldLabels !== false ? ' vas-bold-labels' : ''
					}${ valuePosition === 'below' ? ' vas-value-below' : '' }` }
					style={ {
						'--vas-label-size': `${ labelFontSize || 16 }px`,
						'--vas-value-size': `${ valueFontSize || 36 }px`,
					} }
				>
					<div
						className="vas-multi-labels"
						data-label-count={ resolvedLabels.length }
					>
						{ resolvedLabels.map( ( labelText, index ) => {
							const totalLabels = resolvedLabels.length;

							const safeLabelText =
								typeof labelText === 'string' ? labelText : '';
							const hasManualBreaks =
								safeLabelText.includes( '\n' );

							const labelClasses = [
								'vas-multi-label',
								hasManualBreaks && 'has-manual-breaks',
							]
								.filter( Boolean )
								.join( ' ' );

							const displayAlignment =
								alignmentInternalToDisplay( labelAlignment );
							const positionStyle = calculateLabelStyle(
								index,
								totalLabels,
								displayAlignment
							);

							return (
								<span
									key={ `label-${ index }` }
									className={ labelClasses }
									style={ positionStyle }
								>
									{ labelText }
								</span>
							);
						} ) }
					</div>

					{ shouldShowValue && (
						<div
							className="vas-current-value-solo"
							id={ valueElementId }
						>
							{ previewValue }
						</div>
					) }

					<input
						type="range"
						className="vas-slider"
						min={ sliderMin }
						max={ sliderMax }
						step={ safeStep }
						value={ previewValue }
						onChange={ ( e ) => {
							const val = parseFloat( e.target.value );
							if ( ! isNaN( val ) ) {
								setPreviewValue( val );
							}
						} }
					/>
				</div>
				{ renderHelperText( helperText ) }
			</div>
		</>
	);
}
