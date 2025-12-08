import { useBlockProps } from '@wordpress/block-editor';
import { parseOptions } from '../../utils/optionParser';

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

export default function Save( { attributes } ) {
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
		labelAlignmentPercent,
		labelSpacing,
		labelFontSize,
		valueFontSize,
		showLabelContainers,
		boldLabels,
		showCurrentValue,
		valuePosition,
		conditionalLogic,
	} = attributes;

	const normalizedFieldName =
		fieldName && fieldName.trim() !== '' ? fieldName.trim() : undefined;

	const blockPropsData = {
		className: `form-group eipsi-field eipsi-vas-slider-field${
			valuePosition === 'below' ? ' vas-value-below' : ''
		}`,
		'data-field-name': normalizedFieldName,
		'data-required': required ? 'true' : 'false',
		'data-field-type': 'vas-slider',
		'data-value-position': valuePosition || 'above',
	};

	if (
		conditionalLogic &&
		conditionalLogic.enabled &&
		conditionalLogic.rules &&
		conditionalLogic.rules.length > 0
	) {
		blockPropsData[ 'data-conditional-logic' ] =
			JSON.stringify( conditionalLogic );
	}

	const blockProps = useBlockProps.save( blockPropsData );

	const inputId = getFieldId( normalizedFieldName );

	const safeStep = step && step > 0 ? step : 1;
	const sliderMin =
		typeof minValue === 'number' && ! Number.isNaN( minValue )
			? minValue
			: 0;
	const sliderMaxCandidate =
		typeof maxValue === 'number' && ! Number.isNaN( maxValue )
			? maxValue
			: sliderMin + safeStep;
	const sliderMax =
		sliderMaxCandidate > sliderMin
			? sliderMaxCandidate
			: sliderMin + safeStep;

	const safeInitialValue =
		typeof initialValue === 'number' &&
		! Number.isNaN( initialValue ) &&
		initialValue >= sliderMin &&
		initialValue <= sliderMax
			? initialValue
			: Math.floor( ( sliderMin + sliderMax ) / 2 );

	const parsedLabels = labels ? parseOptions( labels ) : [];
	const resolvedLabels =
		parsedLabels.length > 0
			? parsedLabels
			: [ `${ sliderMin }`, `${ sliderMax }` ];

	const shouldShowValue =
		showCurrentValue !== undefined ? showCurrentValue : showValue !== false;
	const valueElementId =
		shouldShowValue && inputId ? `${ inputId }-value` : undefined;

	let alignmentPercentValue = 50;
	if (
		typeof labelAlignmentPercent === 'number' &&
		! Number.isNaN( labelAlignmentPercent )
	) {
		alignmentPercentValue = labelAlignmentPercent;
	} else if (
		typeof labelSpacing === 'number' &&
		! Number.isNaN( labelSpacing )
	) {
		alignmentPercentValue = labelSpacing;
	}
	// Allow extended alignment up to 200 for clinical flexibility
	const clampedAlignmentPercent = Math.min(
		Math.max( alignmentPercentValue, 0 ),
		200
	);
	// Extended ratio: 0→0, 100→1, 200→2 (allowing more extreme separation)
	const alignmentRatio = clampedAlignmentPercent / 100;
	const compactnessRatio = Math.max( 0, 1 - alignmentRatio );

	return (
		<div { ...blockProps }>
			{ label && (
				<label
					className={ required ? 'required' : undefined }
					htmlFor={ inputId }
				>
					{ label }
				</label>
			) }
			<div
				className={ `vas-slider-container${
					showLabelContainers ? ' vas-show-label-containers' : ''
				}${ boldLabels !== false ? ' vas-bold-labels' : '' }${
					valuePosition === 'below' ? ' vas-value-below' : ''
				}` }
				data-scale={ `${ sliderMin }-${ sliderMax }` }
				style={ {
					// STATIC designer setting. Never touches patient’s slider value.
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
					{ resolvedLabels.map( ( labelText, index ) => (
						<span
							key={ `${ labelText }-${ index }` }
							className="vas-multi-label"
						>
							{ labelText }
						</span>
					) ) }
				</div>

				{ shouldShowValue && (
					<div
						className="vas-current-value-solo"
						id={ valueElementId }
					>
						{ safeInitialValue }
					</div>
				) }

				<input
					type="range"
					name={ normalizedFieldName }
					id={ inputId }
					className="vas-slider"
					min={ sliderMin }
					max={ sliderMax }
					step={ safeStep }
					defaultValue={ safeInitialValue }
					required={ required }
					data-required={ required ? 'true' : 'false' }
					data-show-value={ shouldShowValue ? 'true' : 'false' }
					data-touched="false"
					aria-valuemin={ sliderMin }
					aria-valuemax={ sliderMax }
					aria-valuenow={ safeInitialValue }
					aria-labelledby={ valueElementId }
				/>
			</div>
			{ renderHelperText( helperText ) }
			<div className="form-error" aria-live="polite" />
		</div>
	);
}
