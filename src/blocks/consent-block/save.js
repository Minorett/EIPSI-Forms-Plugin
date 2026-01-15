import { useBlockProps, RichText } from '@wordpress/block-editor';
import { parseConsentMarkdown } from '../utils/markdownParser';
import { renderConsentBody } from '../../utils/field-helpers';

function getPlainTextFromHtml( html ) {
	return ( html || '' )
		.replace( /<[^>]*>/g, '' )
		.replace( /&nbsp;/g, ' ' )
		.trim();
}

export default function Save( { attributes } ) {
	const {
		titulo,
		contenido,
		textoComplementario,
		mostrarCheckbox,
		etiquetaCheckbox,
		isRequired,
	} = attributes;

	const hasValidContenido = getPlainTextFromHtml( contenido ).length > 0;

	if ( ! hasValidContenido ) {
		return null;
	}

	const blockProps = useBlockProps.save( {
		className:
			'wp-block-eipsi-consent-block eipsi-consent-block form-group',
		'data-consent-block': 'true',
		'data-required': mostrarCheckbox && isRequired ? 'true' : 'false',
	} );

	return (
		<div { ...blockProps }>
			{ titulo && (
				<h3 className="consent-title">
					<RichText.Content value={ titulo } />
				</h3>
			) }

			{ renderConsentBody( contenido, parseConsentMarkdown ) }

			{ textoComplementario && (
				<p className="consent-note">
					<RichText.Content value={ textoComplementario } />
				</p>
			) }

			{ mostrarCheckbox && (
				<div className="consent-control">
					<div className="eipsi-consent-checkbox-wrapper">
						<input
							type="checkbox"
							id="eipsi-consent-checkbox"
							name="eipsi_consent_accepted"
							className="eipsi-consent-checkbox"
							required={ isRequired }
							data-required={ isRequired ? 'true' : 'false' }
							data-testid="input-eipsi_consent_accepted"
						/>
						<label htmlFor="eipsi-consent-checkbox">
							{ etiquetaCheckbox }
							{ isRequired && (
								<span className="eipsi-required-mark">*</span>
							) }
						</label>
					</div>
					<div
						className="form-error"
						style={ { display: 'none' } }
					></div>
				</div>
			) }
		</div>
	);
}
