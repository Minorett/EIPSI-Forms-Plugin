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
import { useState } from '@wordpress/element';

export default function Edit( { attributes, setAttributes } ) {
	const { consentText, consentLabel, isRequired, showTimestamp } = attributes;
	const [ previewExpanded, setPreviewExpanded ] = useState( true );

	const blockProps = useBlockProps( {
		className: 'eipsi-consent-block-editor',
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Configuración', 'eipsi-forms' ) }>
					<ToggleControl
						label={ __( 'Campo Obligatorio', 'eipsi-forms' ) }
						help={ __(
							'Si está activado, el participante DEBE marcar el checkbox para continuar. Recomendado para consentimiento informado.',
							'eipsi-forms'
						) }
						checked={ isRequired }
						onChange={ ( value ) =>
							setAttributes( { isRequired: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Mostrar Marca de Tiempo', 'eipsi-forms' ) }
						help={ __(
							'Registra la fecha y hora de aceptación en metadata para auditoría clínica.',
							'eipsi-forms'
						) }
						checked={ showTimestamp }
						onChange={ ( value ) =>
							setAttributes( { showTimestamp: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<div className="eipsi-consent-editor-content">
					<h3 className="eipsi-consent-editor-title">
						{ __(
							'Contenido del Consentimiento Informado',
							'eipsi-forms'
						) }
					</h3>

					<div className="eipsi-consent-text-editor">
						<div className="eipsi-consent-field-label">
							{ __(
								'Descripción del Consentimiento',
								'eipsi-forms'
							) }
						</div>
						<div className="eipsi-consent-tooltip">
							<small>
								{ __(
									'Personaliza el consentimiento para cumplir ANMAT/APA. Incluye: voluntariedad, anonimato, fines clínicos, derechos del participante.',
									'eipsi-forms'
								) }
							</small>
						</div>
						<RichText
							tagName="div"
							multiline="p"
							value={ consentText }
							onChange={ ( value ) =>
								setAttributes( { consentText: value } )
							}
							placeholder={ __(
								'Escriba aquí el texto completo del consentimiento informado. Por ejemplo: "Acepto participar voluntariamente en este estudio de investigación. He leído y comprendido la información proporcionada…"',
								'eipsi-forms'
							) }
						/>
						{ ! consentText?.trim() && (
							<div className="eipsi-consent-validation-error">
								⚠️{ ' ' }
								{ __(
									'El consentimiento debe tener una descripción ética. No puedes dejar este campo vacío.',
									'eipsi-forms'
								) }
							</div>
						) }
					</div>

					<div className="eipsi-consent-label-editor">
						<TextareaControl
							label={ __(
								'Etiqueta del Checkbox (texto junto al checkbox)',
								'eipsi-forms'
							) }
							help={ __(
								'Texto breve junto al checkbox (ej: "He leído y acepto participar voluntariamente en este estudio").',
								'eipsi-forms'
							) }
							value={ consentLabel }
							onChange={ ( value ) =>
								setAttributes( { consentLabel: value } )
							}
							rows={ 4 }
							placeholder={ __(
								'He leído y acepto los términos del consentimiento informado',
								'eipsi-forms'
							) }
						/>
					</div>
				</div>

				<div className="eipsi-consent-preview">
					<div className="eipsi-consent-preview-header">
						<button
							className="eipsi-consent-preview-toggle"
							onClick={ () =>
								setPreviewExpanded( ! previewExpanded )
							}
						>
							{ previewExpanded ? '▼' : '▶' }{ ' ' }
							{ __( 'Vista Previa', 'eipsi-forms' ) }
						</button>
					</div>
					{ previewExpanded && (
						<div className="eipsi-consent-preview-content">
							<div className="eipsi-consent-text-preview">
								<RichText.Content value={ consentText } />
							</div>
							<div className="eipsi-consent-checkbox-preview">
								<input
									type="checkbox"
									id="consent-preview-checkbox"
									disabled
									checked={ false }
								/>
								<label htmlFor="consent-preview-checkbox">
									{ consentLabel }
								</label>
								{ isRequired && (
									<span className="required-asterisk">*</span>
								) }
							</div>
						</div>
					) }
				</div>
			</div>
		</>
	);
}
