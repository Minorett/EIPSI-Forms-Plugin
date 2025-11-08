import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
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

export default function Edit( { attributes, setAttributes } ) {
	const { fieldName, label, required, placeholder, helperText } = attributes;

	const normalizedFieldName =
		fieldName && fieldName.trim() !== '' ? fieldName.trim() : undefined;

	const blockProps = useBlockProps( {
		className: 'form-group eipsi-field eipsi-description-field',
		'data-field-name': normalizedFieldName,
		'data-required': required ? 'true' : 'false',
		'data-field-type': 'description',
	} );

	const displayLabel =
		label || __( 'Campo descripción', 'vas-dinamico-forms' );

	return (
		<>
			<InspectorControls>
				<FieldSettings
					attributes={ attributes }
					setAttributes={ setAttributes }
				/>
			</InspectorControls>

			<div { ...blockProps }>
				{ displayLabel && (
					<label className={ required ? 'required' : undefined }>
						{ displayLabel }
					</label>
				) }
				{ placeholder && (
					<p className="description-placeholder">{ placeholder }</p>
				) }
				{ renderHelperText( helperText ) }
				<div className="form-error" aria-live="polite" />
			</div>
		</>
	);
}
