import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import DescriptionSettings from '../../components/DescriptionSettings';

const renderDescriptionBody = ( text ) => {
	if ( ! text || text.trim() === '' ) {
		return null;
	}

	const lines = text.split( '\n' );

	return (
		<div className="description-body">
			{ lines.map( ( line, index ) => (
				<p key={ `${ line }-${ index }` }>{ line }</p>
			) ) }
		</div>
	);
};

export default function Edit( { attributes, setAttributes } ) {
	const { label, placeholder, helperText } = attributes;

	const blockProps = useBlockProps( {
		className: 'form-group eipsi-field eipsi-description-field',
		'data-field-type': 'description',
	} );

	const displayLabel = label || __( 'Campo descripción', 'eipsi-forms' );

	return (
		<>
			<InspectorControls>
				<DescriptionSettings
					attributes={ attributes }
					setAttributes={ setAttributes }
				/>
			</InspectorControls>

			<div { ...blockProps }>
				{ displayLabel && (
					<h3 className="description-title">{ displayLabel }</h3>
				) }
				{ renderDescriptionBody( helperText ) }
				{ placeholder && (
					<p className="description-note">{ placeholder }</p>
				) }
			</div>
		</>
	);
}
