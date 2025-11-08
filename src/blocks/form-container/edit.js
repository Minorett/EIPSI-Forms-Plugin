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
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
import {
	migrateToStyleConfig,
	serializeToCSSVariables,
	generateInlineStyle,
} from '../../utils/styleTokens';

export default function Edit( { attributes, setAttributes } ) {
	const { formId, submitButtonLabel, description, styleConfig } = attributes;

	// Migration: Convert legacy attributes to styleConfig on mount
	useEffect( () => {
		if ( ! styleConfig ) {
			const migratedConfig = migrateToStyleConfig( attributes );
			setAttributes( { styleConfig: migratedConfig } );
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	// Get current style config (with fallback)
	const currentConfig = styleConfig || migrateToStyleConfig( attributes );

	// Generate CSS variables for editor preview
	const cssVars = serializeToCSSVariables( currentConfig );
	const inlineStyle = generateInlineStyle( cssVars );

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

	// Helper to update styleConfig colors
	const updateStyleColor = ( colorKey, newValue ) => {
		const updatedConfig = {
			...currentConfig,
			colors: {
				...currentConfig.colors,
				[ colorKey ]: newValue,
			},
		};
		setAttributes( { styleConfig: updatedConfig } );
	};

	// Helper to update styleConfig spacing
	const updateStyleSpacing = ( spacingKey, newValue ) => {
		const updatedConfig = {
			...currentConfig,
			spacing: {
				...currentConfig.spacing,
				[ spacingKey ]: `${ newValue }px`,
			},
		};
		setAttributes( { styleConfig: updatedConfig } );
	};

	// Helper to update styleConfig borders
	const updateStyleBorder = ( borderKey, newValue ) => {
		const updatedConfig = {
			...currentConfig,
			borders: {
				...currentConfig.borders,
				[ borderKey ]: `${ newValue }px`,
			},
		};
		setAttributes( { styleConfig: updatedConfig } );
	};

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
				<PanelBody
					title={ __( 'Style Customization', 'vas-dinamico-forms' ) }
					initialOpen={ false }
				>
					<p
						style={ {
							fontSize: '0.9em',
							color: '#666',
							marginTop: 0,
						} }
					>
						{ __(
							'Customize form appearance using design tokens. Changes apply to all form elements.',
							'vas-dinamico-forms'
						) }
					</p>

					<div style={ { marginBottom: '1.5em' } }>
						<h4
							style={ {
								fontSize: '0.95em',
								marginBottom: '0.5em',
							} }
						>
							{ __( 'Colors', 'vas-dinamico-forms' ) }
						</h4>

						<div style={ { marginBottom: '1em' } }>
							<label htmlFor="primary-color">
								{ __( 'Primary Color', 'vas-dinamico-forms' ) }
							</label>
							<ColorPalette
								id="primary-color"
								colors={ [
									{ name: 'EIPSI Blue', color: '#005a87' },
									{ name: 'Default Blue', color: '#0073aa' },
									{ name: 'Navy', color: '#003d5b' },
								] }
								value={ currentConfig.colors.primary }
								onChange={ ( color ) =>
									updateStyleColor( 'primary', color )
								}
							/>
						</div>

						<div style={ { marginBottom: '1em' } }>
							<label htmlFor="background-color">
								{ __(
									'Background Color',
									'vas-dinamico-forms'
								) }
							</label>
							<ColorPalette
								id="background-color"
								colors={ [
									{ name: 'White', color: '#ffffff' },
									{ name: 'Light Gray', color: '#f8f9fa' },
									{ name: 'Dark', color: '#23210f' },
								] }
								value={ currentConfig.colors.background }
								onChange={ ( color ) =>
									updateStyleColor( 'background', color )
								}
							/>
						</div>

						<div style={ { marginBottom: '1em' } }>
							<label htmlFor="text-color">
								{ __( 'Text Color', 'vas-dinamico-forms' ) }
							</label>
							<ColorPalette
								id="text-color"
								colors={ [
									{ name: 'Dark', color: '#2c3e50' },
									{ name: 'Black', color: '#1d2327' },
									{ name: 'White', color: '#ffffff' },
								] }
								value={ currentConfig.colors.text }
								onChange={ ( color ) =>
									updateStyleColor( 'text', color )
								}
							/>
						</div>
					</div>

					<div style={ { marginBottom: '1.5em' } }>
						<h4
							style={ {
								fontSize: '0.95em',
								marginBottom: '0.5em',
							} }
						>
							{ __( 'Spacing & Layout', 'vas-dinamico-forms' ) }
						</h4>

						<RangeControl
							label={ __(
								'Container Padding',
								'vas-dinamico-forms'
							) }
							value={ parseInt(
								currentConfig.spacing.containerPadding
							) }
							onChange={ ( value ) =>
								updateStyleSpacing( 'containerPadding', value )
							}
							min={ 0 }
							max={ 80 }
							step={ 4 }
						/>

						<RangeControl
							label={ __(
								'Border Radius',
								'vas-dinamico-forms'
							) }
							value={ parseInt( currentConfig.borders.radiusMd ) }
							onChange={ ( value ) =>
								updateStyleBorder( 'radiusMd', value )
							}
							min={ 0 }
							max={ 40 }
							step={ 2 }
						/>
					</div>
				</PanelBody>
			</InspectorControls>

			<div
				{ ...blockProps }
				style={ { '--eipsi-editor-style': inlineStyle } }
			>
				<div className="eipsi-form-container-preview" style={ cssVars }>
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
