import { useBlockProps } from '@wordpress/block-editor';
import { parseOptions } from '../../utils/optionParser';
import {
	renderHelperText,
	getFieldId,
	calculateMaxValue,
} from '../../utils/field-helpers';

export default function Save( { attributes } ) {
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
		useGradient,
		gradientType,
		gradientColorStart,
		gradientColorEnd,
	} = attributes;

	// Calcular el máximo actual
	const maxValue = calculateMaxValue( labels, minValue );

	const effectiveFieldName =
		fieldName && typeof fieldName === 'string' && fieldName.trim() !== ''
			? fieldName.trim()
			: fieldKey;

	const blockPropsData = {
		className: `form-group eipsi-field eipsi-likert-field${
			reversed ? ' reversed' : ''
		}`,
		'data-field-name': effectiveFieldName,
		'data-required': required ? 'true' : 'false',
		'data-field-type': 'likert',
		'data-min': minValue,
		'data-max': maxValue,
		'data-scale-variation': scaleVariation,
		'data-reversed': reversed ? 'true' : 'false',
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

	const labelArray = labels ? parseOptions( labels ) : [];

	const scale = [];
	for ( let i = minValue; i <= maxValue; i++ ) {
		scale.push( i );
	}

	return (
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
					{ label }
				</label>
			) }
			<div
				className={ `likert-scale${ reversed ? ' reversed' : '' }${
					useGradient
						? ` gradient-${ gradientType || 'improvement-right' }`
						: ''
				}` }
				style={
					useGradient && gradientType === 'custom'
						? {
								'--likert-gradient-color-start':
									gradientColorStart || '#f44336',
								'--likert-gradient-color-end':
									gradientColorEnd || '#4caf50',
						  }
						: {}
				}
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
	);
}
