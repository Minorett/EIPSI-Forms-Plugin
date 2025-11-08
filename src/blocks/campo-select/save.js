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
		placeholder,
		helperText,
		options,
		conditionalLogic,
	} = attributes;

	const normalizedFieldName =
		fieldName && fieldName.trim() !== '' ? fieldName.trim() : undefined;

	const blockProps = useBlockProps.save( {
		className: 'form-group eipsi-field eipsi-select-field',
		'data-field-name': normalizedFieldName,
		'data-required': required ? 'true' : 'false',
		'data-field-type': 'select',
		'data-conditional-logic': conditionalLogic
			? JSON.stringify( conditionalLogic )
			: undefined,
	} );

	const inputId = getFieldId( normalizedFieldName );
	const optionsArray = parseOptions( options );

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
			<select
				name={ normalizedFieldName }
				id={ inputId }
				required={ required }
				data-required={ required ? 'true' : 'false' }
				data-field-type="select"
			>
				{ placeholder && placeholder.trim() !== '' && (
					<option value="" disabled selected>
						{ placeholder }
					</option>
				) }
				{ optionsArray.map( ( option, index ) => (
					<option key={ index } value={ option }>
						{ option }
					</option>
				) ) }
			</select>
			{ renderHelperText( helperText ) }
			<div className="form-error" aria-live="polite" />
		</div>
	);
}
