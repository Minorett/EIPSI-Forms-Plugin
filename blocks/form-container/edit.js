import {
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
	InnerBlocks,
} from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	TextareaControl,
	ColorPalette,
	RangeControl,
	ToggleControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function Edit( { attributes, setAttributes } ) {
	const {
		formId,
		submitButtonLabel,
		description,
		// NUEVOS atributos
		backgroundColor,
		textColor,
		borderRadius,
		padding,
		// Analytics & Timing
		capturePageTiming,
		captureFieldTiming,
		captureInactivityTime,
	} = attributes;

	const blockProps = useBlockProps( {
		className: 'eipsi-form-container-editor',
	} );

	const ALLOWED_BLOCKS = [
		'vas-dinamico/form-page',
		'vas-dinamico/campo-texto',
		'vas-dinamico/campo-textarea',
		'vas-dinamico/campo-descripcion',
		'vas-dinamico/campo-select',
		'vas-dinamico/campo-radio',
		'vas-dinamico/campo-multiple',
		'vas-dinamico/campo-likert',
		'vas-dinamico/vas-slider',
	];

	const innerBlocksProps = useInnerBlocksProps(
		{
			className: 'eipsi-form-inner-blocks',
		},
		{
			allowedBlocks: ALLOWED_BLOCKS,
			templateLock: false,
			renderAppender: InnerBlocks.ButtonBlockAppender,
		}
	);

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Form Settings', 'vas-dinamico-forms' ) }
					initialOpen={ true }
				>
					<TextControl
						label={ __( 'Form ID/Slug', 'vas-dinamico-forms' ) }
						value={ formId }
						onChange={ ( value ) =>
							setAttributes( { formId: value } )
						}
						help={ __(
							'Enter a unique identifier for the form (e.g., contact-form)',
							'vas-dinamico-forms'
						) }
					/>
					<TextControl
						label={ __(
							'Submit Button Label',
							'vas-dinamico-forms'
						) }
						value={ submitButtonLabel }
						onChange={ ( value ) =>
							setAttributes( { submitButtonLabel: value } )
						}
					/>
					<TextareaControl
						label={ __(
							'Description (Optional)',
							'vas-dinamico-forms'
						) }
						value={ description }
						onChange={ ( value ) =>
							setAttributes( { description: value } )
						}
						help={ __(
							'Optional description text shown above the form',
							'vas-dinamico-forms'
						) }
					/>
				</PanelBody>
				{ /* NUEVO: Panel de personalización - Sin TabPanel */ }
				<PanelBody
					title={ __( 'Style Customization', 'vas-dinamico-forms' ) }
					initialOpen={ false }
				>
					<div style={ { marginBottom: '1em' } }>
						<label htmlFor="background-color">
							{ __( 'Background Color', 'vas-dinamico-forms' ) }
						</label>
						<ColorPalette
							id="background-color"
							colors={ [
								{ name: 'Dark', color: '#23210f' },
								{ name: 'White', color: '#ffffff' },
								{ name: 'Gray', color: '#f0f0f0' },
							] }
							value={ backgroundColor }
							onChange={ ( color ) =>
								setAttributes( { backgroundColor: color } )
							}
						/>
					</div>
					<div style={ { marginBottom: '1em' } }>
						<label htmlFor="text-color">
							{ __( 'Text Color', 'vas-dinamico-forms' ) }
						</label>
						<ColorPalette
							id="text-color"
							value={ textColor }
							onChange={ ( color ) =>
								setAttributes( { textColor: color } )
							}
						/>
					</div>
					<RangeControl
						label={ __( 'Padding', 'vas-dinamico-forms' ) }
						value={ padding }
						onChange={ ( value ) =>
							setAttributes( { padding: value } )
						}
						min={ 0 }
						max={ 60 }
						step={ 4 }
					/>
					<RangeControl
						label={ __( 'Border Radius', 'vas-dinamico-forms' ) }
						value={ borderRadius }
						onChange={ ( value ) =>
							setAttributes( { borderRadius: value } )
						}
						min={ 0 }
						max={ 30 }
						step={ 2 }
					/>
				</PanelBody>

				<PanelBody
					title={ __( 'Analytics & Timing', 'vas-dinamico-forms' ) }
					initialOpen={ false }
				>
					<ToggleControl
						label={ __(
							'Capturar tiempo por página',
							'vas-dinamico-forms'
						) }
						checked={ capturePageTiming }
						onChange={ ( value ) =>
							setAttributes( { capturePageTiming: value } )
						}
						help={ __(
							'Registra cuánto tiempo pasa el participante en cada página. Útil para detectar patrones de fatiga, evasión o procesamiento cognitivo.',
							'vas-dinamico-forms'
						) }
					/>
					<ToggleControl
						label={ __(
							'Capturar tiempo por campo individual',
							'vas-dinamico-forms'
						) }
						checked={ captureFieldTiming }
						onChange={ ( value ) =>
							setAttributes( { captureFieldTiming: value } )
						}
						help={ __(
							'Registra el tiempo que el participante enfoca cada campo individual. Útil para estudios de psicología experimental. ⚠️ Experimental',
							'vas-dinamico-forms'
						) }
					/>
					<ToggleControl
						label={ __(
							'Registrar tiempo de inactividad',
							'vas-dinamico-forms'
						) }
						checked={ captureInactivityTime }
						onChange={ ( value ) =>
							setAttributes( { captureInactivityTime: value } )
						}
						help={ __(
							'Detecta períodos donde el usuario no interactúa con el formulario (30 segundos sin actividad). Útil para estimar tiempo real vs tiempo total. ⚠️ Usa con cautela - no es 100% fiable.',
							'vas-dinamico-forms'
						) }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<div className="eipsi-form-container-preview">
					{ ! formId && (
						<div className="eipsi-form-placeholder">
							<div className="components-placeholder">
								<div className="components-placeholder__label">
									{ __(
										'EIPSI Form Container',
										'vas-dinamico-forms'
									) }
								</div>
								<div className="components-placeholder__instructions">
									{ __(
										'Please enter a Form ID in the block settings to get started.',
										'vas-dinamico-forms'
									) }
								</div>
							</div>
						</div>
					) }
					{ formId && (
						<div className="eipsi-form-preview-wrapper">
							<div className="form-header">
								<h3 className="form-title">
									{ formId
										.split( '-' )
										.map(
											( word ) =>
												word.charAt( 0 ).toUpperCase() +
												word.slice( 1 )
										)
										.join( ' ' ) }
								</h3>
								{ description && (
									<p className="form-description">
										{ description }
									</p>
								) }
							</div>
							<div { ...innerBlocksProps } />
							<div className="form-footer">
								<button
									type="button"
									className="eipsi-submit-button"
									disabled
								>
									{ submitButtonLabel ||
										__( 'Submit', 'vas-dinamico-forms' ) }
								</button>
							</div>
						</div>
					) }
				</div>
			</div>
		</>
	);
}
