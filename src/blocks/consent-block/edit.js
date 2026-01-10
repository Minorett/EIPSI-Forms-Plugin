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
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

function getPlainTextFromHtml( html ) {
	return ( html || '' )
		.replace( /<[^>]*>/g, '' )
		.replace( /&nbsp;/g, ' ' )
		.trim();
}

export default function Edit( { attributes, setAttributes } ) {
	const { consentText, consentLabel, isRequired, showTimestamp } = attributes;
	const [ previewMobileMode, setPreviewMobileMode ] = useState( false );

	const blockProps = useBlockProps( {
		className: 'eipsi-consent-block-editor-refactored',
	} );

	const hasValidConsentText = getPlainTextFromHtml( consentText ).length > 0;

	return (
		<>
			{ /* SIDEBAR: todo editable aquí */ }
			<InspectorControls>
				<PanelBody
					title={ __( 'Consentimiento Informado', 'eipsi-forms' ) }
					initialOpen={ true }
				>
					{ /* RichText GRANDE para consentText */ }
					<div className="eipsi-sidebar-control">
						<div className="eipsi-sidebar-label">
							{ __(
								'Descripción del Consentimiento',
								'eipsi-forms'
							) }
						</div>
						<div className="eipsi-sidebar-tooltip">
							<small>
								{ __(
									'Personaliza el consentimiento para cumplir ANMAT/APA. Incluye: voluntariedad, anonimato, fines clínicos, derechos del participante.',
									'eipsi-forms'
								) }
							</small>
						</div>

						<div className="eipsi-sidebar-richtext">
							<RichText
								tagName="div"
								multiline="p"
								value={ consentText }
								onChange={ ( value ) =>
									setAttributes( {
										consentText: value,
									} )
								}
								placeholder={ __(
									'Escriba aquí el texto completo del consentimiento informado. Ej: “Acepto participar voluntariamente…”',
									'eipsi-forms'
								) }
								aria-label={ __(
									'Descripción del consentimiento informado',
									'eipsi-forms'
								) }
								className="eipsi-sidebar-richtext__field"
							/>
						</div>

						{ ! hasValidConsentText && (
							<div className="eipsi-validation-warning">
								⚠️{ ' ' }
								{ __(
									'El consentimiento debe tener una descripción ética. No puedes dejar este campo vacío.',
									'eipsi-forms'
								) }
							</div>
						) }
					</div>

					{ /* TextareaControl para consentLabel */ }
					<div className="eipsi-sidebar-control">
						<TextareaControl
							label={ __(
								'Etiqueta del Checkbox',
								'eipsi-forms'
							) }
							value={ consentLabel }
							onChange={ ( value ) =>
								setAttributes( {
									consentLabel: value,
								} )
							}
							rows={ 3 }
							placeholder={ __(
								'He leído y acepto participar voluntariamente en este estudio',
								'eipsi-forms'
							) }
							help={ __(
								'Texto breve junto al checkbox. Ej: “He leído y acepto los términos”.',
								'eipsi-forms'
							) }
						/>
					</div>

					{ /* Toggles */ }
					<div className="eipsi-sidebar-control">
						<ToggleControl
							label={ __( 'Campo Obligatorio', 'eipsi-forms' ) }
							checked={ isRequired }
							onChange={ ( value ) =>
								setAttributes( { isRequired: value } )
							}
							help={ __(
								'Si está activado, el participante DEBE marcar el checkbox para continuar. Recomendado para consentimiento informado.',
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
								'Registra la fecha y hora de aceptación en metadata para auditoría clínica.',
								'eipsi-forms'
							) }
						/>
					</div>

					<div className="eipsi-sidebar-control">
						<ToggleControl
							label={ __( 'Vista Mobile', 'eipsi-forms' ) }
							checked={ previewMobileMode }
							onChange={ ( value ) =>
								setPreviewMobileMode( value )
							}
							help={ __(
								'Simula cómo se ve en pantalla de teléfono (375px).',
								'eipsi-forms'
							) }
						/>
					</div>
				</PanelBody>
			</InspectorControls>

			{ /* CANVAS: solo preview en vivo */ }
			<div { ...blockProps }>
				<div
					className={ `eipsi-consent-preview-container ${
						previewMobileMode ? 'mobile-mode' : ''
					}` }
				>
					<h4 className="eipsi-preview-title">
						👁️ { __( 'Vista Previa en Vivo', 'eipsi-forms' ) }
					</h4>

					<div className="eipsi-consent-preview-content">
						<div className="eipsi-consent-text-preview">
							{ hasValidConsentText ? (
								<RichText.Content value={ consentText } />
							) : (
								<p className="eipsi-preview-placeholder">
									{ __(
										'El texto del consentimiento aparecerá aquí…',
										'eipsi-forms'
									) }
								</p>
							) }
						</div>

						<div className="eipsi-consent-checkbox-preview">
							<input
								type="checkbox"
								id="consent-preview-checkbox"
								disabled
								checked={ false }
							/>
							<label htmlFor="consent-preview-checkbox">
								{ consentLabel ||
									__(
										'He leído y acepto los términos',
										'eipsi-forms'
									) }
								{ isRequired && (
									<span className="required-asterisk">*</span>
								) }
							</label>
						</div>
					</div>

					{ previewMobileMode && (
						<div className="eipsi-mobile-indicator">
							📱 { __( 'Vista Mobile (375px)', 'eipsi-forms' ) }
						</div>
					) }
				</div>
			</div>
		</>
	);
}
