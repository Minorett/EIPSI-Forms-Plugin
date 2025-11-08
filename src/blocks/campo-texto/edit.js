import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import FieldSettings from '../../components/FieldSettings';

const FIELD_TYPE_OPTIONS = [
	{ label: __( 'Text', 'vas-dinamico-forms' ), value: 'text' },
	{ label: __( 'Email', 'vas-dinamico-forms' ), value: 'email' },
	{ label: __( 'Number', 'vas-dinamico-forms' ), value: 'number' },
	{ label: __( 'Telephone', 'vas-dinamico-forms' ), value: 'tel' },
	{ label: __( 'URL', 'vas-dinamico-forms' ), value: 'url' },
	{ label: __( 'Date', 'vas-dinamico-forms' ), value: 'date' },
];

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
	const { fieldName, label, required, placeholder, helperText, fieldType } =
		attributes;

	const normalizedFieldName =
		fieldName && fieldName.trim() !== '' ? fieldName.trim() : undefined;

	const blockProps = useBlockProps( {
		className: 'form-group eipsi-field eipsi-text-field',
		'data-field-name': normalizedFieldName,
		'data-required': required ? 'true' : 'false',
		'data-field-type': fieldType || 'text',
	} );

	const inputId = getFieldId( normalizedFieldName );
	const displayLabel =
		label && label.trim() !== ''
			? label
			: __( 'Campo de texto', 'vas-dinamico-forms' );

	return (
		<>
			<InspectorControls>
				<FieldSettings
					attributes={ attributes }
					setAttributes={ setAttributes }
				/>
				<PanelBody
					title={ __( 'Text Field Options', 'vas-dinamico-forms' ) }
					initialOpen={ false }
				>
					<SelectControl
						label={ __( 'Input type', 'vas-dinamico-forms' ) }
						value={ fieldType }
						options={ FIELD_TYPE_OPTIONS }
						onChange={ ( value ) =>
							setAttributes( { fieldType: value } )
						}
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
				<input
					type={ fieldType || 'text' }
					name={ normalizedFieldName }
					id={ inputId }
					placeholder={ placeholder || '' }
					required={ required }
					data-required={ required ? 'true' : 'false' }
					data-field-type={ fieldType || 'text' }
					disabled
				/>
				{ renderHelperText( helperText ) }
				<div className="form-error" aria-live="polite" />
			</div>
		</>
	);
}
