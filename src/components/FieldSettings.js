import {
	PanelBody,
	TextControl,
	ToggleControl,
	TextareaControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const FieldSettings = ( {
	attributes,
	setAttributes,
	showPlaceholder = true,
} ) => {
	const { fieldName, label, required, placeholder, helperText } = attributes;

	return (
		<PanelBody
			title={ __( 'Field Settings', 'eipsi-forms' ) }
			initialOpen={ true }
		>
			<TextControl
				label={ __( 'Field Name / Slug', 'eipsi-forms' ) }
				value={ fieldName || '' }
				onChange={ ( value ) => setAttributes( { fieldName: value } ) }
				help={ __(
					'Used as the input name attribute (e.g., full_name)',
					'eipsi-forms'
				) }
			/>

			<div style={ { marginTop: '16px', marginBottom: '8px' } }>
				<strong>
					{ __( 'Texto que ve el paciente', 'eipsi-forms' ) }
				</strong>
			</div>

			<TextControl
				label={ __( 'Label (título del campo)', 'eipsi-forms' ) }
				value={ label || '' }
				onChange={ ( value ) => setAttributes( { label: value } ) }
				help={ __(
					'Aparece en negrita sobre el campo',
					'eipsi-forms'
				) }
			/>

			<TextareaControl
				label={ __(
					'Descripción / Helper text',
					'eipsi-forms'
				) }
				value={ helperText || '' }
				onChange={ ( value ) => setAttributes( { helperText: value } ) }
				rows={ 4 }
				help={ __(
					'Texto de ayuda permanente que se muestra debajo del campo. Ideal para instrucciones clínicas.',
					'eipsi-forms'
				) }
			/>

			{ showPlaceholder && (
				<>
					<div style={ { marginTop: '16px', marginBottom: '8px' } }>
						<strong>
							{ __(
								'Placeholder (opcional)',
								'eipsi-forms'
							) }
						</strong>
					</div>
					<TextControl
						label={ __(
							'Texto fantasma (desaparece al escribir)',
							'eipsi-forms'
						) }
						value={ placeholder || '' }
						onChange={ ( value ) =>
							setAttributes( { placeholder: value } )
						}
						help={ __(
							'Ejemplo: "Escribe tu respuesta aquí…"',
							'eipsi-forms'
						) }
					/>
				</>
			) }

			<div style={ { marginTop: '16px', marginBottom: '8px' } }>
				<strong>{ __( 'Validación', 'eipsi-forms' ) }</strong>
			</div>

			<ToggleControl
				label={ __( 'Campo obligatorio', 'eipsi-forms' ) }
				checked={ !! required }
				onChange={ ( value ) =>
					setAttributes( { required: !! value } )
				}
				help={ __(
					'Si está activo, el paciente debe completar este campo',
					'eipsi-forms'
				) }
			/>
		</PanelBody>
	);
};

export default FieldSettings;
