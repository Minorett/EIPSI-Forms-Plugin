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
		minValue,
		maxValue,
		labels,
	} = attributes;

	const normalizedFieldName =
		fieldName && fieldName.trim() !== '' ? fieldName.trim() : undefined;

	const blockProps = useBlockProps.save( {
		className: 'form-group eipsi-field eipsi-likert-field',
		'data-field-name': normalizedFieldName,
		'data-required': required ? 'true' : 'false',
		'data-field-type': 'likert',
		'data-min': minValue,
		'data-max': maxValue,
	} );

	const labelArray =
		labels && labels.trim() !== ''
			? labels
					.split( ',' )
					.map( ( l ) => l.trim() )
					.filter( ( l ) => l !== '' )
			: [];

	const scale = [];
	for ( let i = minValue; i <= maxValue; i++ ) {
		scale.push( i );
	}

	return (
		<div { ...blockProps }>
			{ label && (
				<label className={ required ? 'required' : undefined }>
					{ label }
				</label>
			) }
			<div
				className="likert-scale"
				data-scale={ `${ minValue }-${ maxValue }` }
			>
				<ul className="likert-list">
					{ scale.map( ( value, index ) => {
						const optionId = `${ getFieldId(
							normalizedFieldName
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
										name={ normalizedFieldName }
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