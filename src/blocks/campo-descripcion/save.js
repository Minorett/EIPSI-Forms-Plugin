import { useBlockProps } from '@wordpress/block-editor';

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

export default function Save( { attributes } ) {
	const { label, placeholder, helperText } = attributes;

	const blockProps = useBlockProps.save( {
		className: 'form-group eipsi-field eipsi-description-field',
		'data-field-type': 'description',
		'data-label': label || '',
		'data-helper-text': helperText || '',
		'data-placeholder': placeholder || '',
	} );

	return (
		<div { ...blockProps }>
			{ label && <h3 className="description-title">{ label }</h3> }
			{ renderDescriptionBody( helperText ) }
			{ placeholder && (
				<p className="description-note">{ placeholder }</p>
			) }
		</div>
	);
}
