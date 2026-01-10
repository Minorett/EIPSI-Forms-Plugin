import {
	InspectorControls,
	useBlockProps,
	RichText,
} from '@wordpress/block-editor';
import {
	PanelBody,
	TextareaControl,
	ToggleControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

function getPlainTextFromHtml( html ) {
	return ( html || '' )
		.replace( /<[^>]*>/g, '' )
		.replace( /&nbsp;/g, ' ' )
		.trim();
}

export default function Edit( { attributes, setAttributes } ) {
	const {
		titulo,
		contenido,
		textoComplementario,
		mostrarCheckbox,
		etiquetaCheckbox,
		isRequired,
		showTimestamp,
	} = attributes;

	const blockProps = useBlockProps( {
		className: 'eipsi-consent-block-editor',
	} );

	const hasValidContenido = getPlainTextFromHtml( contenido ).length > 0;

	return (
		<>
			{ /* SIDEBAR: Estructura estándar EIPSI */ }
			<InspectorControls>
				<PanelBody
					title={ __( 'Consentimiento Informado', 'eipsi-forms' ) }
				>
					{ /* Título (opcional) */ }
					<div className="eipsi-sidebar-control">
						<div className="eipsi-control-title">
							{ __( 'Título (opcional)', 'eipsi-forms' ) }
						</div>
						<RichText
							tagName="div"
							value={ titulo }
							onChange={ ( value ) =>
								setAttributes( { titulo: value } )
							}
							placeholder={ __(
								'Ej: "Consentimiento Informado"',
								'eipsi-forms'
							) }
							aria-label={ __(
								'Título del consentimiento',
								'eipsi-forms'
							) }
							className="eipsi-richtext-field"
						/>
						<small className="eipsi-control-help">
							{ __(
								'Aparece en negrita. Dejar vacío para omitir.',
								'eipsi-forms'
							) }
						</small>
					</div>

					{ /* Contenido (REQUERIDO) */ }
					<div className="eipsi-sidebar-control">
						<div className="eipsi-control-title required">
							{ __( 'Contenido', 'eipsi-forms' ) }
						</div>
						<RichText
							tagName="div"
							multiline="p"
							value={ contenido }
							onChange={ ( value ) =>
								setAttributes( { contenido: value } )
							}
							placeholder={ __(
								'Escriba el texto completo del consentimiento informado. Incluya: voluntariedad, anonimato, fines clínicos, derechos del participante.',
								'eipsi-forms'
							) }
							aria-label={ __(
								'Contenido del consentimiento',
								'eipsi-forms'
							) }
							className="eipsi-richtext-field"
						/>
						<small className="eipsi-control-help">
							{ __(
								'Personaliza para cumplir ANMAT/APA. Este es el texto principal que verá el paciente.',
								'eipsi-forms'
							) }
						</small>

						{ ! hasValidContenido && (
							<div className="eipsi-validation-warning">
								⚠️{ ' ' }
								{ __(
									'El contenido es obligatorio. El consentimiento debe tener una descripción ética clara.',
									'eipsi-forms'
								) }
							</div>
						) }
					</div>

					{ /* Texto Complementario (opcional) */ }
					<div className="eipsi-sidebar-control">
						<div className="eipsi-control-title">
							{ __(
								'Texto Complementario (opcional)',
								'eipsi-forms'
							) }
						</div>
						<RichText
							tagName="div"
							value={ textoComplementario }
							onChange={ ( value ) =>
								setAttributes( { textoComplementario: value } )
							}
							placeholder={ __(
								'Ej: "Si deseas participar en futuras fases, completa los siguientes datos."',
								'eipsi-forms'
							) }
							aria-label={ __(
								'Texto complementario',
								'eipsi-forms'
							) }
							className="eipsi-richtext-field"
						/>
						<small className="eipsi-control-help">
							{ __(
								'Aparece debajo del contenido. Dejar vacío para omitir.',
								'eipsi-forms'
							) }
						</small>
					</div>

					{ /* Checkbox Toggle */ }
					<div className="eipsi-sidebar-control">
						<ToggleControl
							label={ __(
								'Incluir Checkbox de Aceptación',
								'eipsi-forms'
							) }
							checked={ mostrarCheckbox }
							onChange={ ( value ) =>
								setAttributes( { mostrarCheckbox: value } )
							}
							help={ __(
								'Si activado, muestra un checkbox para que el participante acepte el consentimiento.',
								'eipsi-forms'
							) }
						/>
					</div>

					{ /* Etiqueta del Checkbox (condicional) */ }
					{ mostrarCheckbox && (
						<TextareaControl
							label={ __(
								'Etiqueta del Checkbox',
								'eipsi-forms'
							) }
							value={ etiquetaCheckbox }
							onChange={ ( value ) =>
								setAttributes( { etiquetaCheckbox: value } )
							}
							rows={ 3 }
							placeholder={ __(
								'Ej: "He leído y acepto participar voluntariamente en este estudio."',
								'eipsi-forms'
							) }
							help={ __(
								'Texto que aparece junto al checkbox.',
								'eipsi-forms'
							) }
						/>
					) }

					{ /* Toggles adicionales (condicional) */ }
					{ mostrarCheckbox && (
						<>
							<div className="eipsi-sidebar-control">
								<ToggleControl
									label={ __(
										'Campo Obligatorio',
										'eipsi-forms'
									) }
									checked={ isRequired }
									onChange={ ( value ) =>
										setAttributes( { isRequired: value } )
									}
									help={ __(
										'Si activado, el participante DEBE marcar el checkbox para continuar.',
										'eipsi-forms'
									) }
								/>
							</div>

							<div className="eipsi-sidebar-control">
								<ToggleControl
									label={ __(
										'Mostrar Marca de Tiempo',
										'eipsi-forms'
									) }
									checked={ showTimestamp }
									onChange={ ( value ) =>
										setAttributes( {
											showTimestamp: value,
										} )
									}
									help={ __(
										'Registra fecha y hora de aceptación en metadata para auditoría.',
										'eipsi-forms'
									) }
								/>
							</div>
						</>
					) }
				</PanelBody>
			</InspectorControls>

			{ /* CANVAS: Vista previa en vivo */ }
			<div { ...blockProps }>
				<div className="eipsi-consent-preview-container">
					<h4 className="eipsi-preview-title">
						{ __( '👁️ Vista Previa', 'eipsi-forms' ) }
					</h4>

					<div className="eipsi-consent-preview-content">
						{ /* Título si existe */ }
						{ titulo && (
							<h3 className="eipsi-consent-titulo">
								<RichText.Content value={ titulo } />
							</h3>
						) }

						{ /* Contenido principal */ }
						<div className="eipsi-consent-contenido">
							{ hasValidContenido ? (
								<RichText.Content value={ contenido } />
							) : (
								<p className="eipsi-preview-placeholder">
									{ __(
										'Escriba el contenido del consentimiento aquí…',
										'eipsi-forms'
									) }
								</p>
							) }
						</div>

						{ /* Texto complementario si existe */ }
						{ textoComplementario && (
							<div className="eipsi-consent-complementario">
								<RichText.Content
									value={ textoComplementario }
								/>
							</div>
						) }

						{ /* Checkbox si toggle ON */ }
						{ mostrarCheckbox && (
							<div className="eipsi-consent-checkbox-wrapper">
								<input
									type="checkbox"
									id="consent-preview-checkbox"
									disabled
									checked={ false }
								/>
								<label htmlFor="consent-preview-checkbox">
									{ etiquetaCheckbox }
									{ isRequired && (
										<span className="required-asterisk">
											*
										</span>
									) }
								</label>
							</div>
						) }
					</div>
				</div>
			</div>
		</>
	);
}
