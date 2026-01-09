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

export default function Edit( { attributes, setAttributes } ) {
	const { consentText, consentLabel, isRequired, showTimestamp } = attributes;

	const blockProps = useBlockProps( {
		className: 'eipsi-consent-block-editor',
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Configuración', 'eipsi-forms' ) }>
					<ToggleControl
						label={ __( 'Campo Obligatorio', 'eipsi-forms' ) }
						checked={ isRequired }
						onChange={ ( value ) =>
							setAttributes( { isRequired: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Mostrar Marca de Tiempo', 'eipsi-forms' ) }
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
					</div>

					<div className="eipsi-consent-label-editor">
						<TextareaControl
							label={ __(
								'Etiqueta del Checkbox (texto junto al checkbox)',
								'eipsi-forms'
							) }
							help={ __(
								'Este texto aparecerá junto al checkbox que debe marcar el participante',
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
					<h4 className="eipsi-consent-preview-title">
						{ __( '👁️ Vista Previa', 'eipsi-forms' ) }
					</h4>
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
				</div>
			</div>
		</>
	);
}
