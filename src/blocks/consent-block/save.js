import { useBlockProps, RichText } from '@wordpress/block-editor';

export default function Save( { attributes } ) {
	const {
		titulo,
		contenido,
		textoComplementario,
		mostrarCheckbox,
		etiquetaCheckbox,
		isRequired,
	} = attributes;

	const blockProps = useBlockProps.save( {
		className:
			'wp-block-eipsi-consent-block eipsi-consent-block form-group',
		'data-consent-block': 'true',
		'data-required': mostrarCheckbox && isRequired ? 'true' : 'false',
	} );

	return (
		<div { ...blockProps }>
			{ /* Título si existe */ }
			{ titulo && (
				<h3 className="eipsi-consent-titulo">
					<RichText.Content value={ titulo } />
				</h3>
			) }

			{ /* Contenido principal */ }
			<div className="eipsi-consent-contenido">
				<RichText.Content value={ contenido } />
			</div>

			{ /* Texto complementario si existe */ }
			{ textoComplementario && (
				<div className="eipsi-consent-complementario">
					<RichText.Content value={ textoComplementario } />
				</div>
			) }

			{ /* Checkbox si toggle ON */ }
			{ mostrarCheckbox && (
				<div className="eipsi-consent-control">
					<div className="eipsi-checkbox-wrapper">
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
