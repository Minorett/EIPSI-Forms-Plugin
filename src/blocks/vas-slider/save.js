import { useBlockProps } from '@wordpress/block-editor';

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
	} = attributes;

	const normalizedFieldName =
		fieldName && fieldName.trim() !== '' ? fieldName.trim() : undefined;

	const blockProps = useBlockProps.save( {
		className: 'form-group eipsi-field eipsi-vas-slider-field',
		'data-field-name': normalizedFieldName,
		'data-required': required ? 'true' : 'false',
		'data-field-type': 'vas-slider',
	} );

	const inputId = getFieldId( normalizedFieldName );
	const currentValue =
		initialValue >= minValue && initialValue <= maxValue
			? initialValue
			: Math.floor( ( minValue + maxValue ) / 2 );

	// Procesar etiquetas personalizadas
	const labelArray =
		labels && labels.trim() !== ''
			? labels
					.split( ',' )
					.map( ( l ) => l.trim() )
					.filter( ( l ) => l !== '' )
			: [ 'Nada', 'Leve', 'Moderado', 'Fuerte', 'Muy fuerte' ]; // Valores por defecto

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

			<div className="vas-section">
				<div className="vas-slider-container">
					{ /* ===== ETIQUETAS PERSONALIZABLES ===== */ }
					{ labelArray.length > 0 && (
						<div className="vas-labels">
							{ labelArray.map( ( labelText, index ) => (
								<span key={ index } className="vas-label">
									{ labelText }
								</span>
							) ) }
						</div>
					) }

					{ /* ===== SLIDER PRINCIPAL ===== */ }
					<div className="vas-slider-wrapper">
						<input
							type="range"
							name={ normalizedFieldName }
							id={ inputId }
							className="vas-slider"
							min={ minValue }
							max={ maxValue }
							step={ step }
							defaultValue={ currentValue }
							required={ required }
							data-required={ required ? 'true' : 'false' }
							data-show-value={ showValue ? 'true' : 'false' }
							aria-valuemin={ minValue }
							aria-valuemax={ maxValue }
							aria-valuenow={ currentValue }
							aria-labelledby={ `${ inputId }-value` }
						/>
					</div>

					{ /* ===== VALOR NUMÉRICO CENTRAL ===== */ }
					{ showValue && (
						<div className="vas-value-display">
							<span
								className="vas-value-number"
								id={ `${ inputId }-value-display` }
							>
								{ currentValue }
							</span>
						</div>
					) }
				</div>
			</div>

			{ renderHelperText( helperText ) }
			<div className="form-error" aria-live="polite" />
		</div>
	);
}
