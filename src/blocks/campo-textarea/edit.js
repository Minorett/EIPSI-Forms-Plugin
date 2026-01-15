import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, RangeControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import FieldSettings from '../../components/FieldSettings';

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

export default function Edit( { attributes, setAttributes } ) {
	const { fieldName, label, required, placeholder, helperText, rows } =
		attributes;

	const normalizedFieldName =
		fieldName && typeof fieldName === 'string' && fieldName.trim() !== ''
			? fieldName.trim()
			: undefined;

	const blockProps = useBlockProps( {
		className: 'form-group eipsi-field eipsi-textarea-field',
		'data-field-name': normalizedFieldName,
		'data-required': required ? 'true' : 'false',
		'data-field-type': 'textarea',
	} );

	const inputId = getFieldId( normalizedFieldName );
	const displayLabel = label || __( 'Campo textarea', 'eipsi-forms' );
	const rowsValue = Number( rows ) || 4;

	return (
		<>
			<InspectorControls>
				<FieldSettings
					attributes={ attributes }
					setAttributes={ setAttributes }
				/>
				<PanelBody
					title={ __( 'Textarea Options', 'eipsi-forms' ) }
					initialOpen={ false }
				>
					<RangeControl
						label={ __( 'Rows', 'eipsi-forms' ) }
						value={ rowsValue }
						onChange={ ( value ) =>
							setAttributes( { rows: Number( value ) || 4 } )
						}
						min={ 2 }
						max={ 12 }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<label
					className={ required ? 'required' : undefined }
					htmlFor={ inputId }
				>
					{ displayLabel }
				</label>
				<textarea
					name={ normalizedFieldName }
					id={ inputId }
					placeholder={ placeholder || '' }
					required={ required }
					rows={ rowsValue }
					data-required={ required ? 'true' : 'false' }
					data-field-type="textarea"
					disabled
				/>
				{ renderHelperText( helperText ) }
				<div className="form-error" aria-live="polite" />
			</div>
		</>
	);
}
