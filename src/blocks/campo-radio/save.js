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

export default function Save( { attributes } ) {
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

	const blockProps = useBlockProps.save( {
		className: 'form-group eipsi-field eipsi-radio-field',
		'data-field-name': normalizedFieldName,
		'data-required': required ? 'true' : 'false',
		'data-field-type': 'radio',
		'data-conditional-logic': conditionalLogic
			? JSON.stringify( conditionalLogic )
			: undefined,
	} );

	const optionsArray = parseOptions( options );

	return (
		<div { ...blockProps }>
			{ label && (
				<label className={ required ? 'required' : undefined }>
					{ label }
				</label>
			) }
			<ul className="radio-list">
				{ optionsArray.map( ( option, index ) => {
					const radioId = getFieldId(
						normalizedFieldName,
						index.toString()
					);
					return (
						<li key={ index }>
							<input
								type="radio"
								name={ normalizedFieldName }
								id={ radioId }
								value={ option }
								required={ required }
								data-required={ required ? 'true' : 'false' }
								data-field-type="radio"
							/>
							<label htmlFor={ radioId }>{ option }</label>
						</li>
					);
				} ) }
			</ul>
			{ renderHelperText( helperText ) }
			<div className="form-error" aria-live="polite" />
		</div>
	);
}
