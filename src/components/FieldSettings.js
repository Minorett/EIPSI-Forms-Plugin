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
			<TextControl
				label={ __( 'Label', 'vas-dinamico-forms' ) }
				value={ label || '' }
				onChange={ ( value ) => setAttributes( { label: value } ) }
			/>
			<ToggleControl
				label={ __( 'Required field', 'vas-dinamico-forms' ) }
				checked={ !! required }
				onChange={ ( value ) =>
					setAttributes( { required: !! value } )
				}
			/>
			{ showPlaceholder && (
				<TextControl
					label={ __( 'Placeholder', 'vas-dinamico-forms' ) }
					value={ placeholder || '' }
					onChange={ ( value ) =>
						setAttributes( { placeholder: value } )
					}
				/>
			) }
			<TextareaControl
				label={ __( 'Helper text', 'vas-dinamico-forms' ) }
				value={ helperText || '' }
				onChange={ ( value ) => setAttributes( { helperText: value } ) }
				help={ __(
					'Displayed below the field to provide additional guidance.',
					'vas-dinamico-forms'
				) }
			/>
		</PanelBody>
	);
};

export default FieldSettings;
