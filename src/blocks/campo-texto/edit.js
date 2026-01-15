import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import FieldSettings from '../../components/FieldSettings';
import { renderHelperText, getFieldId } from '../../utils/field-helpers';

const FIELD_TYPE_OPTIONS = [
	{ label: __( 'Text', 'eipsi-forms' ), value: 'text' },
	{ label: __( 'Email', 'eipsi-forms' ), value: 'email' },
	{ label: __( 'Number', 'eipsi-forms' ), value: 'number' },
	{ label: __( 'Telephone', 'eipsi-forms' ), value: 'tel' },
	{ label: __( 'URL', 'eipsi-forms' ), value: 'url' },
	{ label: __( 'Date', 'eipsi-forms' ), value: 'date' },
];

export default function Edit( { attributes, setAttributes } ) {
	const { fieldName, label, required, placeholder, helperText, fieldType } =
		attributes;

	const normalizedFieldName =
		fieldName && typeof fieldName === 'string' && fieldName.trim() !== ''
			? fieldName.trim()
			: undefined;

	const blockProps = useBlockProps( {
		className: 'form-group eipsi-field eipsi-text-field',
		'data-field-name': normalizedFieldName,
		'data-required': required ? 'true' : 'false',
		'data-field-type': fieldType || 'text',
	} );

	const inputId = getFieldId( normalizedFieldName );
	const displayLabel =
		label && typeof label === 'string' && label.trim() !== ''
			? label
			: __( 'Campo de texto', 'eipsi-forms' );

	return (
		<>
			<InspectorControls>
				<FieldSettings
					attributes={ attributes }
					setAttributes={ setAttributes }
				/>
				<PanelBody
					title={ __( 'Text Field Options', 'eipsi-forms' ) }
					initialOpen={ false }
				>
					<SelectControl
						label={ __( 'Input type', 'eipsi-forms' ) }
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
