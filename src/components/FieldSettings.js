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
			title={ __( 'Field Settings', 'vas-dinamico-forms' ) }
			initialOpen={ true }
		>
			<TextControl
				label={ __( 'Field Name / Slug', 'vas-dinamico-forms' ) }
				value={ fieldName || '' }
				onChange={ ( value ) => setAttributes( { fieldName: value } ) }
				help={ __(
					'Used as the input name attribute (e.g., full_name)',
					'vas-dinamico-forms'
				) }
			/>

			<div style={ { marginTop: '16px', marginBottom: '8px' } }>
				<strong>
					{ __( 'Texto que ve el paciente', 'vas-dinamico-forms' ) }
				</strong>
			</div>

			<TextControl
				label={ __( 'Label (título del campo)', 'vas-dinamico-forms' ) }
				value={ label || '' }
				onChange={ ( value ) => setAttributes( { label: value } ) }
				help={ __(
					'Aparece en negrita sobre el campo',
					'vas-dinamico-forms'
				) }
			/>

			<TextareaControl
				label={ __(
					'Descripción / Helper text',
					'vas-dinamico-forms'
				) }
				value={ helperText || '' }
				onChange={ ( value ) => setAttributes( { helperText: value } ) }
				rows={ 4 }
				help={ __(
					'Texto de ayuda permanente que se muestra debajo del campo. Ideal para instrucciones clínicas.',
					'vas-dinamico-forms'
				) }
			/>

			{ showPlaceholder && (
				<>
					<div style={ { marginTop: '16px', marginBottom: '8px' } }>
						<strong>
							{ __(
								'Placeholder (opcional)',
								'vas-dinamico-forms'
							) }
						</strong>
					</div>
					<TextControl
						label={ __(
							'Texto fantasma (desaparece al escribir)',
							'vas-dinamico-forms'
						) }
						value={ placeholder || '' }
						onChange={ ( value ) =>
							setAttributes( { placeholder: value } )
						}
						help={ __(
							'Ejemplo: "Escribe tu respuesta aquí…"',
							'vas-dinamico-forms'
						) }
					/>
				</>
			) }

			<div style={ { marginTop: '16px', marginBottom: '8px' } }>
				<strong>{ __( 'Validación', 'vas-dinamico-forms' ) }</strong>
			</div>

			<ToggleControl
				label={ __( 'Campo obligatorio', 'vas-dinamico-forms' ) }
				checked={ !! required }
				onChange={ ( value ) =>
					setAttributes( { required: !! value } )
				}
				help={ __(
					'Si está activo, el paciente debe completar este campo',
					'vas-dinamico-forms'
				) }
			/>
		</PanelBody>
	);
};

export default FieldSettings;
