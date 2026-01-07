import {
	InspectorControls,
	useBlockProps,
	RichText,
} from '@wordpress/block-editor';
import { PanelBody, TextControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function Edit( { attributes, setAttributes } ) {
	const { consentText, consentLabel, isRequired, showTimestamp } = attributes;

	const blockProps = useBlockProps( {
		className: 'eipsi-consent-block-editor',
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Consent Settings', 'vas-dinamico-forms' ) }
				>
					<TextControl
						label={ __( 'Checkbox Label', 'vas-dinamico-forms' ) }
						value={ consentLabel }
						onChange={ ( value ) =>
							setAttributes( { consentLabel: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Is Required', 'vas-dinamico-forms' ) }
						checked={ isRequired }
						onChange={ ( value ) =>
							setAttributes( { isRequired: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Show Timestamp', 'vas-dinamico-forms' ) }
						checked={ showTimestamp }
						onChange={ ( value ) =>
							setAttributes( { showTimestamp: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<div className="eipsi-consent-preview">
					<div className="eipsi-consent-text-editor">
						<RichText
							tagName="div"
							multiline="p"
							value={ consentText }
							onChange={ ( value ) =>
								setAttributes( { consentText: value } )
							}
							placeholder={ __(
								'Escribe aquí el texto del consentimiento…',
								'vas-dinamico-forms'
							) }
						/>
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
		</>
	);
}
