import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import ConsentSettings from '../../components/ConsentSettings';

const renderConsentBody = ( text ) => {
	if ( ! text || text.trim() === '' ) {
		return (
			<p className="eipsi-preview-placeholder">
				{ __(
					'Escriba el contenido del consentimiento aquí…',
					'eipsi-forms'
				) }
			</p>
		);
	}

	const lines = text.split( '\n' );

	return (
		<div className="consent-body">
			{ lines.map( ( line, index ) => (
				<p key={ `${ line }-${ index }` }>{ line }</p>
			) ) }
		</div>
	);
};

export default function Edit( { attributes, setAttributes } ) {
	const {
		titulo,
		contenido,
		textoComplementario,
		mostrarCheckbox,
		etiquetaCheckbox,
		isRequired,
	} = attributes;

	const blockProps = useBlockProps( {
		className: 'form-group eipsi-field eipsi-consent-field',
		'data-field-type': 'consent',
		'data-consent-block': 'true',
		'data-required': mostrarCheckbox && isRequired ? 'true' : 'false',
	} );

	const displayLabel =
		titulo || __( 'Consentimiento Informado', 'eipsi-forms' );

	return (
		<>
			<InspectorControls>
				<ConsentSettings
					attributes={ attributes }
					setAttributes={ setAttributes }
				/>
			</InspectorControls>

			<div { ...blockProps }>
				{ displayLabel && (
					<h3 className="consent-title">{ displayLabel }</h3>
				) }

				{ renderConsentBody( contenido ) }

				{ textoComplementario && (
					<p className="consent-note">{ textoComplementario }</p>
				) }

				{ mostrarCheckbox && (
					<div className="consent-checkbox-wrapper">
						<input
							type="checkbox"
							id="consent-preview-checkbox"
							disabled
							checked={ false }
						/>
						<label htmlFor="consent-preview-checkbox">
							{ etiquetaCheckbox }
							{ isRequired && (
								<span className="required-asterisk">*</span>
							) }
						</label>
					</div>
				) }
			</div>
		</>
	);
}
