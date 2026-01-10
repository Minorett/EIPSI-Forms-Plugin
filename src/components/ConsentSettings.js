import {
	PanelBody,
	TextControl,
	TextareaControl,
	ToggleControl,
} from '@wordpress/components';
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

			<TextareaControl
				label={ __( 'Contenido', 'eipsi-forms' ) }
				value={ contenido || '' }
				onChange={ ( value ) => setAttributes( { contenido: value } ) }
				rows={ 6 }
				placeholder={ __(
					'Escriba el texto completo del consentimiento informado. Incluya: voluntariedad, anonimato, fines clínicos, derechos del participante.',
					'eipsi-forms'
				) }
				help={ __(
					'Personaliza para cumplir ANMAT/APA. Este es el texto principal que verá el paciente. Puedes usar saltos de línea.',
					'eipsi-forms'
				) }
			/>

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
