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
		isRequired,
		etiquetaConfirmacionLectura,
		textoBotonRechazar,
		textoBotonAceptar,
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
					{/* v2.5: Checkbox de confirmación de lectura (gate) */}
					<div className="eipsi-consent-reading-confirmation">
						<input
							type="checkbox"
							id="eipsi-consent-confirm-reading"
							name="eipsi_consent_confirm_reading"
							className="eipsi-consent-checkbox eipsi-consent-reading-gate"
							data-required="true"
							data-testid="input-eipsi_consent_confirm_reading"
						/>
						<label htmlFor="eipsi-consent-confirm-reading">
							{ etiquetaConfirmacionLectura }
							<span className="eipsi-required-mark">*</span>
						</label>
					</div>

					{/* v2.5: Botones explícitos de decisión */}
					<div className="eipsi-consent-decision-buttons">
						<button
							type="button"
							className="eipsi-btn eipsi-btn-reject"
							data-action="reject"
							data-testid="btn-consent-reject"
						>
							{ textoBotonRechazar }
						</button>
						<button
							type="button"
							className="eipsi-btn eipsi-btn-accept"
							data-action="accept"
							data-testid="btn-consent-accept"
							disabled={ true }
							aria-disabled="true"
						>
							{ textoBotonAceptar }
						</button>
					</div>

					{/* Campo oculto para enviar el valor del consentimiento */}
					<input
						type="hidden"
						name="eipsi_consent_decision"
						id="eipsi-consent-decision"
						value=""
						data-testid="input-eipsi_consent_decision"
					/>

					<div
						className="eipsi-consent-error form-error"
						style={ { display: 'none' } }
						role="alert"
					>
						Debes confirmar que leíste el consentimiento informado para continuar.
					</div>
				</div>
			) }
		</div>
	);
}
