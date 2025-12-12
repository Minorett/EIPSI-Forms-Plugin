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
		labelStyle,
		labelAlignment,
		labelAlignmentPercent,
		labelSpacing,
		labelFontSize,
		valueFontSize,
		showLabelContainers,
		boldLabels,
		showCurrentValue,
		valuePosition,
	} = attributes;

	useEffect( () => {
		if ( labelAlignmentPercent !== undefined ) {
			return;
		}

		if (
			typeof labelSpacing === 'number' &&
			! Number.isNaN( labelSpacing )
		) {
			setAttributes( { labelAlignmentPercent: labelSpacing } );
			return;
		}

		if ( labelStyle !== undefined || labelAlignment !== undefined ) {
			let migratedValue = 50;
			if ( labelAlignment === 'justified' ) {
				migratedValue = 0;
			} else if ( labelAlignment === 'centered' ) {
				migratedValue = 100;
			} else if ( labelStyle === 'simple' ) {
				migratedValue = 30;
			} else if ( labelStyle === 'centered' ) {
				migratedValue = 70;
			}
			setAttributes( { labelAlignmentPercent: migratedValue } );
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps -- Only run once on mount to migrate legacy attributes
	}, [] );

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
			: __( 'VAS Slider', 'vas-dinamico-forms' );
	const inputId = getFieldId( normalizedFieldName );

	const safeStep = step && step > 0 ? step : 1;
	const sliderMin = sanitizeNumber( minValue, 0 );
	const sliderMaxCandidate = sanitizeNumber( maxValue, sliderMin + safeStep );
	const sliderMax =
		sliderMaxCandidate > sliderMin
			? sliderMaxCandidate
			: sliderMin + safeStep;

	let alignmentPercentValue = 50;
	if ( Number.isFinite( labelAlignmentPercent ) ) {
		alignmentPercentValue = labelAlignmentPercent;
	} else if ( Number.isFinite( labelSpacing ) ) {
		alignmentPercentValue = labelSpacing;
	}
	// Allow unlimited alignment values for clinical flexibility
	const safeAlignmentPercent = Math.max( alignmentPercentValue, 0 );
	// Normalize to 0-1 scale: 0→0, 100→1, >1→extended separation
	const alignmentRatio = safeAlignmentPercent / 100;
	const compactnessRatio = Math.max( 0, 1 - alignmentRatio );

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
		'vas-dinamico-forms'
	);
	const maxControlHelp = __(
		'Debe ser mayor que el mínimo. Si lo igualás o bajás demasiado, movemos el mínimo para mantener el slider usable.',
		'vas-dinamico-forms'
	);

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Field Settings', 'vas-dinamico-forms' ) }
					initialOpen={ true }
				>
					<TextControl
						label={ __(
							'Field Name / Slug',
							'vas-dinamico-forms'
						) }
						value={ fieldName || '' }
						onChange={ ( value ) =>
							setAttributes( { fieldName: value } )
						}
						help={ __(
							'Used as the input name attribute (e.g., pain_level)',
							'vas-dinamico-forms'
						) }
					/>
					<TextControl
						label={ __( 'Label', 'vas-dinamico-forms' ) }
						value={ label || '' }
						onChange={ ( value ) =>
							setAttributes( { label: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Required field', 'vas-dinamico-forms' ) }
						checked={ !! required }
						onChange={ ( value ) =>
							setAttributes( { required: !! value } )
						}
					/>
					<TextareaControl
						label={ __( 'Helper text', 'vas-dinamico-forms' ) }
						value={ helperText || '' }
						onChange={ ( value ) =>
							setAttributes( { helperText: value } )
						}
						help={ __(
							'Displayed below the field to provide additional guidance.',
							'vas-dinamico-forms'
						) }
					/>
				</PanelBody>

				<PanelBody
					title={ __( 'VAS Slider Options', 'vas-dinamico-forms' ) }
					initialOpen={ true }
				>
					<TextareaControl
						label={ __(
							'Labels (separated by semicolon)',
							'vas-dinamico-forms'
						) }
						value={ labels || '' }
						onChange={ ( value ) =>
							setAttributes( {
								labels: normalizeLineEndings( value ),
							} )
						}
						help={ __(
							'Usá punto y coma (;) para separar cada etiqueta (ej.: "Nada; Poco; Bastante; Mucho"). Si lo dejás vacío, mostramos solo los extremos numéricos. Formatos anteriores con comas o saltos de línea se mantienen activos.',
							'vas-dinamico-forms'
						) }
					/>
					<NumberControl
						label={ __( 'Minimum Value', 'vas-dinamico-forms' ) }
						value={ minValue }
						onChange={ handleMinValueChange }
						min={ -1000 }
						max={ 1000 }
						step="any"
						help={ minControlHelp }
					/>
					<NumberControl
						label={ __( 'Maximum Value', 'vas-dinamico-forms' ) }
						value={ maxValue }
						onChange={ handleMaxValueChange }
						min={ -1000 }
						max={ 1000 }
						step="any"
						help={ maxControlHelp }
					/>
					<RangeControl
						label={ __( 'Step', 'vas-dinamico-forms' ) }
						value={ step && step > 0 ? step : 1 }
						onChange={ handleStepChange }
						min={ 0.1 }
						max={ 10 }
						step={ 0.1 }
						help={ __(
							'Increment for each slider movement',
							'vas-dinamico-forms'
						) }
					/>
					<RangeControl
						label={ __( 'Initial Value', 'vas-dinamico-forms' ) }
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
							'vas-dinamico-forms'
						) }
					/>
				</PanelBody>

				<PanelBody
					title={ __( 'Appearance', 'vas-dinamico-forms' ) }
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
							{ __( 'Label Appearance', 'vas-dinamico-forms' ) }
						</h3>

						<ToggleControl
							label={ __(
								'Show label containers',
								'vas-dinamico-forms'
							) }
							checked={ !! showLabelContainers }
							onChange={ ( value ) =>
								setAttributes( {
									showLabelContainers: !! value,
								} )
							}
							help={ __(
								'Display background boxes around each label',
								'vas-dinamico-forms'
							) }
						/>

						<ToggleControl
							label={ __( 'Bold labels', 'vas-dinamico-forms' ) }
							checked={ boldLabels !== false }
							onChange={ ( value ) =>
								setAttributes( { boldLabels: !! value } )
							}
							help={ __(
								'Make label text bold',
								'vas-dinamico-forms'
							) }
						/>

						<UnitControl
							label={ __( 'Label size', 'vas-dinamico-forms' ) }
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
								'vas-dinamico-forms'
							) }
							value={ alignmentPercentValue }
							onChange={ ( value ) =>
								setAttributes( {
									labelAlignmentPercent: value,
								} )
							}
							min={ 0 }
							max={ 100 }
							step={ 1 }
							help={ __(
								'0 = compactas | 100 = bien marcadas',
								'vas-dinamico-forms'
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
							{ __( 'Values', 'vas-dinamico-forms' ) }
						</h3>

						<ToggleControl
							label={ __(
								'Show selected value',
								'vas-dinamico-forms'
							) }
							checked={ shouldShowValue }
							onChange={ ( value ) =>
								setAttributes( { showCurrentValue: !! value } )
							}
							help={ __(
								'Show the number selected by the user',
								'vas-dinamico-forms'
							) }
						/>

						{ shouldShowValue && (
							<>
								<SelectControl
									label={ __(
										'Value Position',
										'vas-dinamico-forms'
									) }
									value={ valuePosition || 'above' }
									options={ [
										{
											label: __(
												'Above slider',
												'vas-dinamico-forms'
											),
											value: 'above',
										},
										{
											label: __(
												'Below slider',
												'vas-dinamico-forms'
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
										'vas-dinamico-forms'
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
					title={ __( 'Conditional Logic', 'vas-dinamico-forms' ) }
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
						showLabelContainers ? ' vas-show-label-containers' : ''
					}${ boldLabels !== false ? ' vas-bold-labels' : '' }${
						valuePosition === 'below' ? ' vas-value-below' : ''
					}` }
					style={ {
						'--vas-label-alignment': alignmentRatio,
						'--vas-label-compactness': compactnessRatio,
						'--vas-label-size': `${ labelFontSize || 16 }px`,
						'--vas-value-size': `${ valueFontSize || 36 }px`,
					} }
				>
					<div
						className="vas-multi-labels"
						data-label-count={ resolvedLabels.length }
					>
						{ resolvedLabels.map( ( labelText, index ) => {
							const isFirst = index === 0;
							const isLast = index === resolvedLabels.length - 1;
							const labelClasses = [
								'vas-multi-label',
								isFirst && 'vas-multi-label--first',
								isLast && 'vas-multi-label--last',
							]
								.filter( Boolean )
								.join( ' ' );

							return (
								<span
									key={ `label-${ index }` }
									className={ labelClasses }
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
