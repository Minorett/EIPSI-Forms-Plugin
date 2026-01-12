import { useBlockProps, RichText } from '@wordpress/block-editor';
import { parseConsentMarkdown } from '../../../assets/js/consent-markdown-parser';

function getPlainTextFromHtml( html ) {
	return ( html || '' )
		.replace( /<[^>]*>/g, '' )
		.replace( /&nbsp;/g, ' ' )
		.trim();
}

const renderConsentBody = ( text ) => {
	if ( ! text || text.trim() === '' ) {
		return null;
	}

	const lines = text.split( '\n' );

	return (
		<div className="eipsi-consent-body">
			{ lines.map( ( line, index ) => {
				// Parsear markdown en cada línea para frontend
				const parsedLine = parseConsentMarkdown( line );
				return (
					<p
						key={ `${ line }-${ index }` }
						dangerouslySetInnerHTML={ { __html: parsedLine } }
					/>
				);
			} ) }
		</div>
	);
};

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

			{ renderConsentBody( contenido ) }

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
