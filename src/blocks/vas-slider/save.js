import { useBlockProps } from '@wordpress/block-editor';
import { parseOptions } from '../../utils/optionParser';
import {
	calculateLabelStyle,
	alignmentInternalToDisplay,
} from './calculateLabelSpacing';
import { renderHelperText, getFieldId } from '../../utils/field-helpers';

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
		labelFontSize,
		valueFontSize,
		showLabelContainers,
		boldLabels,
		showCurrentValue,
		valuePosition,
		conditionalLogic,
		labelAlignment,
		useGradient,
		gradientType,
		gradientColorStart,
		gradientColorEnd,
	} = attributes;

	const normalizedFieldName =
		fieldName && typeof fieldName === 'string' && fieldName.trim() !== ''
			? fieldName.trim()
			: undefined;

	// ✅ FIX v1.3.19: Move variable declarations BEFORE usage to avoid TDZ error
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

	const blockPropsData = {
		className: `form-group eipsi-field eipsi-vas-slider-field${
			valuePosition === 'below' ? ' vas-value-below' : ''
		}`,
		'data-field-name': normalizedFieldName,
		'data-required': required ? 'true' : 'false',
		'data-field-type': 'vas-slider',
		'data-value-position': valuePosition || 'above',
		'data-labels': labels || '',
		'data-min-value': sliderMin,
		'data-max-value': sliderMax,
		'data-step': safeStep,
		'data-initial-value': safeInitialValue,
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

	const parsedLabels = labels ? parseOptions( labels ) : [];
	const resolvedLabels =
		parsedLabels.length > 0
			? parsedLabels
			: [ `${ sliderMin }`, `${ sliderMax }` ];

	const shouldShowValue =
		showCurrentValue !== undefined ? showCurrentValue : showValue !== false;
	const valueElementId =
		shouldShowValue && inputId ? `${ inputId }-value` : undefined;

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
						const hasManualBreaks = safeLabelText.includes( '\n' );

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
						{ safeInitialValue }
					</div>
				) }

				<input
					type="range"
					name={ normalizedFieldName }
					id={ inputId }
					className={ `vas-slider${
						useGradient
							? ` gradient-${
									gradientType || 'improvement-right'
							  }`
							: ''
					}` }
					style={
						useGradient && gradientType === 'custom'
							? {
									'--vas-gradient-color-start':
										gradientColorStart || '#f44336',
									'--vas-gradient-color-end':
										gradientColorEnd || '#4caf50',
							  }
							: {}
					}
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
