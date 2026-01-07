import { PanelBody, TextControl, TextareaControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Settings panel for description blocks
 * Unlike regular fields, descriptions don't have a slug/fieldName
 * since they're not meant to capture user responses
 *
 * @param {Object}   root0               Component props
 * @param {Object}   root0.attributes    Block attributes
 * @param {Function} root0.setAttributes Function to update attributes
 * @return {Element} The settings panel component
 */
const DescriptionSettings = ( { attributes, setAttributes } ) => {
	const { label, helperText, placeholder } = attributes;

	return (
		<PanelBody
			title={ __( 'Description Settings', 'eipsi-forms' ) }
			initialOpen={ true }
		>
			<div style={ { marginBottom: '16px' } }>
				<p style={ { fontSize: '13px', color: '#666' } }>
					{ __(
						'Este bloque es solo informativo — no captura respuestas del paciente.',
						'eipsi-forms'
					) }
				</p>
			</div>

			<TextControl
				label={ __( 'Título', 'eipsi-forms' ) }
				value={ label || '' }
				onChange={ ( value ) => setAttributes( { label: value } ) }
				help={ __(
					'Aparece en negrita (ej: "Instrucciones importantes")',
					'eipsi-forms'
				) }
			/>

			<TextareaControl
				label={ __( 'Contenido', 'eipsi-forms' ) }
				value={ helperText || '' }
				onChange={ ( value ) => setAttributes( { helperText: value } ) }
				rows={ 6 }
				help={ __(
					'Texto principal que verá el paciente. Puedes usar saltos de línea.',
					'eipsi-forms'
				) }
			/>

			<TextControl
				label={ __( 'Texto complementario (opcional)', 'eipsi-forms' ) }
				value={ placeholder || '' }
				onChange={ ( value ) =>
					setAttributes( { placeholder: value } )
				}
				help={ __(
					'Aparece debajo del contenido principal en estilo discreto.',
					'eipsi-forms'
				) }
			/>
		</PanelBody>
	);
};

export default DescriptionSettings;
