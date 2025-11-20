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
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
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

export default function Edit( { attributes, setAttributes, clientId } ) {
	const {
		fieldName,
		label,
		required,
		helperText,
		leftLabel,
		rightLabel,
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
		showValueContainer,
		boldLabels,
		showCurrentValue,
		valuePosition,
	} = attributes;

	useEffect( () => {
		if (
			labelAlignmentPercent === undefined &&
			( labelStyle !== undefined || labelAlignment !== undefined )
		) {
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
		// Only run once on mount to migrate legacy attributes
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	const normalizedFieldName =
		fieldName && fieldName.trim() !== '' ? fieldName.trim() : undefined;

	const blockProps = useBlockProps( {
		className: 'form-group eipsi-field eipsi-vas-slider-field',
		'data-field-name': normalizedFieldName,
		'data-required': required ? 'true' : 'false',
		'data-field-type': 'vas-slider',
	} );

	const displayLabel =
		label && label.trim() !== ''
			? label
			: __( 'VAS Slider', 'vas-dinamico-forms' );
	const inputId = getFieldId( normalizedFieldName );

	const currentValue =
		initialValue >= minValue && initialValue <= maxValue
			? initialValue
			: Math.floor( ( minValue + maxValue ) / 2 );
	const [ previewValue, setPreviewValue ] = useState( currentValue );

	const labelArray =
		labels && labels.trim() !== ''
			? labels
					.split( ',' )
					.map( ( l ) => l.trim() )
					.filter( ( l ) => l !== '' )
			: [];
	const hasMultiLabels = labelArray.length > 0;

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
							'Labels (comma-separated)',
							'vas-dinamico-forms'
						) }
						value={ labels || '' }
						onChange={ ( value ) =>
							setAttributes( { labels: value } )
						}
						help={ __(
							'Optional: Multiple labels for scale points (e.g., "Muy mal,Mal,Neutral,Bien,Muy bien")',
							'vas-dinamico-forms'
						) }
					/>
					<TextControl
						label={ __( 'Left Label', 'vas-dinamico-forms' ) }
						value={ leftLabel || '' }
						onChange={ ( value ) =>
							setAttributes( { leftLabel: value } )
						}
						help={ __(
							'Label for the minimum value (used when multi-labels are not set)',
							'vas-dinamico-forms'
						) }
					/>
					<TextControl
						label={ __( 'Right Label', 'vas-dinamico-forms' ) }
						value={ rightLabel || '' }
						onChange={ ( value ) =>
							setAttributes( { rightLabel: value } )
						}
						help={ __(
							'Label for the maximum value (used when multi-labels are not set)',
							'vas-dinamico-forms'
						) }
					/>
					<RangeControl
						label={ __( 'Minimum Value', 'vas-dinamico-forms' ) }
						value={ minValue }
						onChange={ ( value ) => {
							setAttributes( { minValue: value } );
							if ( maxValue <= value ) {
								setAttributes( { maxValue: value + 1 } );
							}
							if ( initialValue < value ) {
								const newInitial = Math.floor(
									( value + maxValue ) / 2
								);
								setAttributes( { initialValue: newInitial } );
								setPreviewValue( newInitial );
							}
						} }
						min={ 0 }
						max={ 100 }
					/>
					<RangeControl
						label={ __( 'Maximum Value', 'vas-dinamico-forms' ) }
						value={ maxValue }
						onChange={ ( value ) => {
							setAttributes( { maxValue: value } );
							if ( minValue >= value ) {
								setAttributes( { minValue: value - 1 } );
							}
							if ( initialValue > value ) {
								const newInitial = Math.floor(
									( minValue + value ) / 2
								);
								setAttributes( { initialValue: newInitial } );
								setPreviewValue( newInitial );
							}
						} }
						min={ 1 }
						max={ 1000 }
					/>
					<RangeControl
						label={ __( 'Step', 'vas-dinamico-forms' ) }
						value={ step }
						onChange={ ( value ) =>
							setAttributes( { step: value } )
						}
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
						value={ initialValue }
						onChange={ ( value ) => {
							setAttributes( { initialValue: value } );
							setPreviewValue( value );
						} }
						min={ minValue }
						max={ maxValue }
						step={ step }
						help={ __(
							'Default slider position',
							'vas-dinamico-forms'
						) }
					/>
					<ToggleControl
						label={ __(
							'Show current value',
							'vas-dinamico-forms'
						) }
						checked={ !! showValue }
						onChange={ ( value ) =>
							setAttributes( { showValue: !! value } )
						}
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
							checked={
								boldLabels !== undefined ? !! boldLabels : true
							}
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
								const numValue = parseInt( value ) || 16;
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
								'Label spacing',
								'vas-dinamico-forms'
							) }
							value={
								labelSpacing !== undefined ? labelSpacing : 100
							}
							onChange={ ( value ) =>
								setAttributes( { labelSpacing: value } )
							}
							min={ 0 }
							max={ 100 }
							step={ 1 }
							help={ __(
								'0 = tight spacing (edge-to-edge), 100 = wide spacing (centered with gaps)',
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
							{ __( 'Value Display', 'vas-dinamico-forms' ) }
						</h3>

						<ToggleControl
							label={ __(
								'Show current value',
								'vas-dinamico-forms'
							) }
							checked={
								showCurrentValue !== undefined
									? !! showCurrentValue
									: showValue !== false
							}
							onChange={ ( value ) => {
								setAttributes( {
									showCurrentValue: !! value,
									showValue: !! value,
								} );
							} }
							help={ __(
								'Display the current slider value',
								'vas-dinamico-forms'
							) }
						/>

						<ToggleControl
							label={ __(
								'Show value container',
								'vas-dinamico-forms'
							) }
							checked={ !! showValueContainer }
							onChange={ ( value ) =>
								setAttributes( {
									showValueContainer: !! value,
								} )
							}
							help={ __(
								'Display background box around the value number',
								'vas-dinamico-forms'
							) }
						/>

						<UnitControl
							label={ __( 'Value size', 'vas-dinamico-forms' ) }
							value={ `${ valueFontSize || 36 }px` }
							onChange={ ( value ) => {
								const numValue = parseInt( value ) || 36;
								setAttributes( { valueFontSize: numValue } );
							} }
							min={ 20 }
							max={ 80 }
							step={ 1 }
							units={ [
								{ value: 'px', label: 'px', default: 36 },
							] }
							isUnitSelectTabbable={ false }
						/>

						<SelectControl
							label={ __(
								'Value position',
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
								setAttributes( { valuePosition: value } )
							}
						/>
					</div>
				</PanelBody>

				<ConditionalLogicControl
					attributes={ attributes }
					setAttributes={ setAttributes }
					clientId={ clientId }
					mode="numeric"
				/>
			</InspectorControls>

			<div { ...blockProps }>
				<label
					className={ required ? 'required' : undefined }
					htmlFor={ inputId }
				>
					{ displayLabel }
				</label>
				<div
					className={ `vas-slider-container vas-slider-preview ${
						showLabelContainers ? 'vas-show-label-containers' : ''
					} ${
						showValueContainer ? 'vas-show-value-container' : ''
					} ${ boldLabels !== false ? 'vas-bold-labels' : '' } ${
						valuePosition === 'below' ? 'vas-value-below' : ''
					}` }
					data-scale={ `${ minValue }-${ maxValue }` }
					style={ {
						'--vas-label-alignment':
							( labelAlignmentPercent !== undefined
								? labelAlignmentPercent
								: labelSpacing || 50 ) / 100,
						'--vas-label-size': `${ labelFontSize || 16 }px`,
						'--vas-value-size': `${ valueFontSize || 36 }px`,
						'--vas-label-spacing': `${
							labelSpacing !== undefined ? labelSpacing : 100
						}%`,
					} }
				>
					{ ! hasMultiLabels && (
						<div className="vas-slider-labels">
							{ leftLabel && (
								<span className="vas-label-left">
									{ leftLabel }
								</span>
							) }
							{ ( showCurrentValue !== undefined
								? showCurrentValue
								: showValue !== false ) && (
								<span
									className="vas-current-value"
									id={ `${ inputId }-value` }
								>
									{ previewValue }
								</span>
							) }
							{ rightLabel && (
								<span className="vas-label-right">
									{ rightLabel }
								</span>
							) }
						</div>
					) }
					{ hasMultiLabels && (
						<div className="vas-multi-labels">
							{ labelArray.map( ( labelText, index ) => (
								<span key={ index } className="vas-multi-label">
									{ labelText }
								</span>
							) ) }
						</div>
					) }
					{ hasMultiLabels &&
						( showCurrentValue !== undefined
							? showCurrentValue
							: showValue !== false ) && (
							<div
								className="vas-current-value-solo"
								id={ `${ inputId }-value` }
							>
								{ previewValue }
							</div>
						) }
					<input
						type="range"
						name={ normalizedFieldName }
						id={ inputId }
						className="vas-slider"
						min={ minValue }
						max={ maxValue }
						step={ step }
						value={ previewValue }
						onChange={ ( e ) =>
							setPreviewValue( parseFloat( e.target.value ) )
						}
						required={ required }
						data-required={ required ? 'true' : 'false' }
						data-show-value={
							(
								showCurrentValue !== undefined
									? showCurrentValue
									: showValue !== false
							)
								? 'true'
								: 'false'
						}
						aria-valuemin={ minValue }
						aria-valuemax={ maxValue }
						aria-valuenow={ previewValue }
						aria-labelledby={ `${ inputId }-value` }
					/>
				</div>
				{ renderHelperText( helperText ) }
				<div className="form-error" aria-live="polite" />
			</div>
		</>
	);
}
