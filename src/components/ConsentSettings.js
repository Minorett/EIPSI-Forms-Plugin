import {
	PanelBody,
	TextControl,
	TextareaControl,
	ToggleControl,
} from '@wordpress/components';
import { RichText } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

/**
 * Settings panel for consent block
 * Follows the same modular pattern as DescriptionSettings
 *
 * @param {Object}   root0               Component props
 * @param {Object}   root0.attributes    Block attributes
 * @param {Function} root0.setAttributes Function to update attributes
 * @return {Element} The settings panel component
 */
const ConsentSettings = ( { attributes, setAttributes } ) => {
	const {
		titulo,
		contenido,
		textoComplementario,
		mostrarCheckbox,
		etiquetaCheckbox,
		isRequired,
		showTimestamp,
	} = attributes;

	return (
		<PanelBody
			title={ __( 'Configuración del Consentimiento', 'eipsi-forms' ) }
			initialOpen={ true }
		>
			<div style={ { marginBottom: '16px' } }>
				<p style={ { fontSize: '13px', color: '#666' } }>
					{ __(
						'Bloque de consentimiento informado para investigaciones clínicas (ANMAT/APA).',
						'eipsi-forms'
					) }
				</p>
			</div>

			<TextControl
				label={ __( 'Título (opcional)', 'eipsi-forms' ) }
				value={ titulo || '' }
				onChange={ ( value ) => setAttributes( { titulo: value } ) }
				help={ __(
					'Aparece en negrita (ej: "Consentimiento Informado")',
					'eipsi-forms'
				) }
			/>

			<div
				className="eipsi-sidebar-control"
				style={ { marginBottom: '16px' } }
			>
				<div
					className="eipsi-control-title required"
					style={ {
						fontWeight: 600,
						marginBottom: '8px',
						color: '#374151',
						fontSize: '13px',
					} }
				>
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
					style={ {
						minHeight: '120px',
						border: '1px solid #d1d5db',
						borderRadius: '4px',
						padding: '0.75rem',
						background: 'white',
					} }
				/>
				<small
					className="eipsi-control-help"
					style={ {
						display: 'block',
						marginTop: '0.5rem',
						color: '#6b7280',
						fontSize: '12px',
						lineHeight: '1.4',
					} }
				>
					{ __(
						'Personaliza para cumplir ANMAT/APA. Este es el texto principal que verá el paciente.',
						'eipsi-forms'
					) }
				</small>
			</div>

			<TextControl
				label={ __( 'Texto complementario (opcional)', 'eipsi-forms' ) }
				value={ textoComplementario || '' }
				onChange={ ( value ) =>
					setAttributes( { textoComplementario: value } )
				}
				help={ __(
					'Aparece debajo del contenido principal en estilo discreto.',
					'eipsi-forms'
				) }
			/>

			<ToggleControl
				label={ __( 'Incluir Checkbox de Aceptación', 'eipsi-forms' ) }
				checked={ mostrarCheckbox }
				onChange={ ( value ) =>
					setAttributes( { mostrarCheckbox: value } )
				}
				help={ __(
					'Si activado, muestra un checkbox para que el participante acepte el consentimiento.',
					'eipsi-forms'
				) }
			/>

			{ mostrarCheckbox && (
				<>
					<TextareaControl
						label={ __( 'Etiqueta del Checkbox', 'eipsi-forms' ) }
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

					<ToggleControl
						label={ __( 'Campo Obligatorio', 'eipsi-forms' ) }
						checked={ isRequired }
						onChange={ ( value ) =>
							setAttributes( { isRequired: value } )
						}
						help={ __(
							'Si activado, el participante DEBE marcar el checkbox para continuar.',
							'eipsi-forms'
						) }
					/>

					<ToggleControl
						label={ __( 'Mostrar Marca de Tiempo', 'eipsi-forms' ) }
						checked={ showTimestamp }
						onChange={ ( value ) =>
							setAttributes( { showTimestamp: value } )
						}
						help={ __(
							'Registra fecha y hora de aceptación en metadata para auditoría.',
							'eipsi-forms'
						) }
					/>
				</>
			) }
		</PanelBody>
	);
};

export default ConsentSettings;
