/**
 * FormStylePanel Component
 * Comprehensive customization panel for EIPSI Form styling
 * Provides FormGent-level control over colors, typography, spacing, borders, shadows, and presets
 *
 * @package
 */

/* eslint-disable jsx-a11y/label-has-associated-control -- Labels are properly associated through custom component structure */

import {
	PanelBody,
	ColorPalette,
	ColorIndicator,
	SelectControl,
	RangeControl,
	Button,
	TextControl,
	Notice,
	Flex,
	FlexItem,
	Dashicon,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { DEFAULT_STYLE_CONFIG } from '../utils/styleTokens';
import { getContrastRating } from '../utils/contrastChecker';
import { STYLE_PRESETS, getPresetPreview } from '../utils/stylePresets';
import './FormStylePanel.css';

const FormStylePanel = ( {
	styleConfig,
	setStyleConfig,
	presetName,
	setPresetName,
} ) => {
	const [ activePreset, setActivePreset ] = useState( presetName || null );

	const config = styleConfig || DEFAULT_STYLE_CONFIG;

	// Helper to update any nested config value
	const updateConfig = ( category, key, value ) => {
		const updated = {
			...config,
			[ category ]: {
				...config[ category ],
				[ key ]: value,
			},
		};
		setStyleConfig( updated );
		setActivePreset( null ); // Clear active preset on manual change
	};

	// Apply preset theme
	const applyPreset = ( preset ) => {
		setStyleConfig( JSON.parse( JSON.stringify( preset.config ) ) );
		setActivePreset( preset.name );
		setPresetName( preset.name ); // Save preset name to attributes
	};

	// Reset to defaults
	const resetToDefaults = () => {
		if (
			// eslint-disable-next-line no-alert
			window.confirm(
				__(
					'Reset all customizations to default clinical theme?',
					'eipsi-forms'
				)
			)
		) {
			setStyleConfig(
				JSON.parse( JSON.stringify( DEFAULT_STYLE_CONFIG ) )
			);
			setActivePreset( 'Clinical Blue' );
			setPresetName( 'Clinical Blue' );
		}
	};

	// Contrast checking for key combinations
	const textBgRating = getContrastRating(
		config.colors.text,
		config.colors.background
	);
	const textMutedSubtleRating = getContrastRating(
		config.colors.textMuted,
		config.colors.backgroundSubtle
	);
	const buttonRating = getContrastRating(
		config.colors.buttonText,
		config.colors.buttonBg
	);
	const buttonHoverRating = getContrastRating(
		config.colors.buttonText,
		config.colors.buttonHoverBg
	);
	const inputRating = getContrastRating(
		config.colors.inputText,
		config.colors.inputBg
	);
	const errorBgRating = getContrastRating(
		config.colors.error,
		config.colors.background
	);
	const successBgRating = getContrastRating(
		config.colors.success,
		config.colors.background
	);
	const warningBgRating = getContrastRating(
		config.colors.warning,
		config.colors.background
	);

	// Color presets for pickers
	const colorPresets = [
		{ name: 'EIPSI Blue', color: '#005a87' },
		{ name: 'Dark Blue', color: '#003d5b' },
		{ name: 'Light Blue', color: '#e3f2fd' },
		{ name: 'Navy', color: '#2c5aa0' },
		{ name: 'White', color: '#ffffff' },
		{ name: 'Light Gray', color: '#f8f9fa' },
		{ name: 'Dark Gray', color: '#2c3e50' },
		{ name: 'Medium Gray', color: '#64748b' },
		{ name: 'Border Gray', color: '#e2e8f0' },
		{ name: 'Black', color: '#000000' },
		{ name: 'Error Red (WCAG AA)', color: '#d32f2f' },
		{ name: 'Success Green (WCAG AA)', color: '#198754' },
		{ name: 'Warning Brown (WCAG AA)', color: '#b35900' },
		{ name: 'Warm Brown', color: '#8b6f47' },
	];

	// Font family options
	const fontFamilyOptions = [
		{
			label: 'System Default',
			value: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
		},
		{ label: 'Arial', value: 'Arial, sans-serif' },
		{ label: 'Helvetica', value: 'Helvetica, Arial, sans-serif' },
		{
			label: 'Georgia (Serif)',
			value: 'Georgia, "Times New Roman", serif',
		},
		{ label: 'Times New Roman', value: '"Times New Roman", serif' },
		{ label: 'Courier New (Mono)', value: '"Courier New", monospace' },
		{ label: 'Verdana', value: 'Verdana, sans-serif' },
	];

	// Border style options
	const borderStyleOptions = [
		{ label: 'Solid', value: 'solid' },
		{ label: 'Dashed', value: 'dashed' },
		{ label: 'Dotted', value: 'dotted' },
		{ label: 'None', value: 'none' },
	];

	return (
		<>
			{ /* PRESETS PANEL */ }
			<PanelBody
				title={ __( '🎨 Theme Presets', 'eipsi-forms' ) }
				initialOpen={ true }
			>
				<p className="eipsi-panel-description">
					{ __(
						'Apply professionally designed themes optimized for clinical research.',
						'eipsi-forms'
					) }
				</p>

				<div className="eipsi-preset-grid">
					{ STYLE_PRESETS.map( ( preset ) => {
						const preview = getPresetPreview( preset );
						const isActive = activePreset === preset.name;

						return (
							<button
								key={ preset.name }
								className={ `eipsi-preset-button ${
									isActive ? 'is-active' : ''
								}` }
								onClick={ () => applyPreset( preset ) }
								title={ preset.description }
							>
								<div
									className="eipsi-preset-preview"
									style={ {
										background: preview.backgroundSubtle,
										borderColor: preview.border,
										borderRadius: preview.borderRadius,
										boxShadow: preview.shadow,
									} }
								>
									<div
										className="eipsi-preset-button-sample"
										style={ {
											background: preview.buttonBg,
											color: preview.buttonText,
											borderRadius: preview.borderRadius,
										} }
									>
										Button
									</div>
									<div
										className="eipsi-preset-text"
										style={ {
											color: preview.text,
											fontFamily: preview.fontFamily,
										} }
									>
										Text
									</div>
								</div>
								<span className="eipsi-preset-name">
									{ preset.name }
								</span>
								{ isActive && (
									<Dashicon
										icon="yes-alt"
										className="eipsi-preset-checkmark"
									/>
								) }
							</button>
						);
					} ) }
				</div>

				<Button
					isSecondary
					isSmall
					onClick={ resetToDefaults }
					style={ { marginTop: '1rem', width: '100%' } }
				>
					{ __( 'Reset to Default', 'eipsi-forms' ) }
				</Button>
			</PanelBody>

			{ /* COLORS PANEL */ }
			<PanelBody
				title={ __( '🎨 Colors', 'eipsi-forms' ) }
				initialOpen={ false }
			>
				<p className="eipsi-panel-description">
					{ __(
						'Customize form colors. Maintain 4.5:1 contrast ratio for accessibility.',
						'eipsi-forms'
					) }
				</p>

				{ /* Brand Colors */ }
				<h4 className="eipsi-section-title">
					{ __( 'Brand Colors', 'eipsi-forms' ) }
				</h4>

				<div className="eipsi-color-control">
					<Flex align="flex-start" justify="space-between">
						<FlexItem>
							<label>
								{ __( 'Primary', 'eipsi-forms' ) }
							</label>
						</FlexItem>
						<FlexItem>
							<ColorIndicator
								colorValue={ config.colors.primary }
							/>
						</FlexItem>
					</Flex>
					<ColorPalette
						colors={ colorPresets }
						value={ config.colors.primary }
						onChange={ ( color ) =>
							updateConfig( 'colors', 'primary', color )
						}
						clearable={ false }
					/>
				</div>

				<div className="eipsi-color-control">
					<Flex align="flex-start" justify="space-between">
						<FlexItem>
							<label>
								{ __( 'Primary Hover', 'eipsi-forms' ) }
							</label>
						</FlexItem>
						<FlexItem>
							<ColorIndicator
								colorValue={ config.colors.primaryHover }
							/>
						</FlexItem>
					</Flex>
					<ColorPalette
						colors={ colorPresets }
						value={ config.colors.primaryHover }
						onChange={ ( color ) =>
							updateConfig( 'colors', 'primaryHover', color )
						}
						clearable={ false }
					/>
				</div>

				<div className="eipsi-color-control">
					<Flex align="flex-start" justify="space-between">
						<FlexItem>
							<label>
								{ __( 'Secondary', 'eipsi-forms' ) }
							</label>
						</FlexItem>
						<FlexItem>
							<ColorIndicator
								colorValue={ config.colors.secondary }
							/>
						</FlexItem>
					</Flex>
					<ColorPalette
						colors={ colorPresets }
						value={ config.colors.secondary }
						onChange={ ( color ) =>
							updateConfig( 'colors', 'secondary', color )
						}
						clearable={ false }
					/>
				</div>

				{ /* Background & Text */ }
				<h4 className="eipsi-section-title">
					{ __( 'Background & Text', 'eipsi-forms' ) }
				</h4>

				<div className="eipsi-color-control">
					<Flex align="flex-start" justify="space-between">
						<FlexItem>
							<label>
								{ __( 'Background', 'eipsi-forms' ) }
							</label>
						</FlexItem>
						<FlexItem>
							<ColorIndicator
								colorValue={ config.colors.background }
							/>
						</FlexItem>
					</Flex>
					<ColorPalette
						colors={ colorPresets }
						value={ config.colors.background }
						onChange={ ( color ) =>
							updateConfig( 'colors', 'background', color )
						}
						clearable={ false }
					/>
				</div>

				<div className="eipsi-color-control">
					<Flex align="flex-start" justify="space-between">
						<FlexItem>
							<label>
								{ __( 'Text', 'eipsi-forms' ) }
							</label>
						</FlexItem>
						<FlexItem>
							<ColorIndicator colorValue={ config.colors.text } />
						</FlexItem>
					</Flex>
					<ColorPalette
						colors={ colorPresets }
						value={ config.colors.text }
						onChange={ ( color ) =>
							updateConfig( 'colors', 'text', color )
						}
						clearable={ false }
					/>
				</div>

				{ ! textBgRating.passes && (
					<Notice status="warning" isDismissible={ false }>
						<strong>
							{ __( 'Contrast Warning:', 'eipsi-forms' ) }
						</strong>{ ' ' }
						{ textBgRating.message }
					</Notice>
				) }

				<div className="eipsi-color-control">
					<Flex align="flex-start" justify="space-between">
						<FlexItem>
							<label>
								{ __( 'Text Muted', 'eipsi-forms' ) }
							</label>
						</FlexItem>
						<FlexItem>
							<ColorIndicator
								colorValue={ config.colors.textMuted }
							/>
						</FlexItem>
					</Flex>
					<ColorPalette
						colors={ colorPresets }
						value={ config.colors.textMuted }
						onChange={ ( color ) =>
							updateConfig( 'colors', 'textMuted', color )
						}
						clearable={ false }
					/>
				</div>

				{ ! textMutedSubtleRating.passes && (
					<Notice status="warning" isDismissible={ false }>
						<strong>
							{ __( 'Contrast Warning:', 'eipsi-forms' ) }
						</strong>{ ' ' }
						{ __(
							'Text Muted on Background Subtle:',
							'eipsi-forms'
						) }
						{ textMutedSubtleRating.message }
					</Notice>
				) }

				{ /* Input Colors */ }
				<h4 className="eipsi-section-title">
					{ __( 'Input Fields', 'eipsi-forms' ) }
				</h4>

				<div className="eipsi-color-control">
					<Flex align="flex-start" justify="space-between">
						<FlexItem>
							<label>
								{ __(
									'Input Background',
									'eipsi-forms'
								) }
							</label>
						</FlexItem>
						<FlexItem>
							<ColorIndicator
								colorValue={ config.colors.inputBg }
							/>
						</FlexItem>
					</Flex>
					<ColorPalette
						colors={ colorPresets }
						value={ config.colors.inputBg }
						onChange={ ( color ) =>
							updateConfig( 'colors', 'inputBg', color )
						}
						clearable={ false }
					/>
				</div>

				<div className="eipsi-color-control">
					<Flex align="flex-start" justify="space-between">
						<FlexItem>
							<label>
								{ __( 'Input Text', 'eipsi-forms' ) }
							</label>
						</FlexItem>
						<FlexItem>
							<ColorIndicator
								colorValue={ config.colors.inputText }
							/>
						</FlexItem>
					</Flex>
					<ColorPalette
						colors={ colorPresets }
						value={ config.colors.inputText }
						onChange={ ( color ) =>
							updateConfig( 'colors', 'inputText', color )
						}
						clearable={ false }
					/>
				</div>

				{ ! inputRating.passes && (
					<Notice status="warning" isDismissible={ false }>
						<strong>
							{ __( 'Contrast Warning:', 'eipsi-forms' ) }
						</strong>{ ' ' }
						{ inputRating.message }
					</Notice>
				) }

				<div className="eipsi-color-control">
					<Flex align="flex-start" justify="space-between">
						<FlexItem>
							<label>
								{ __( 'Input Border', 'eipsi-forms' ) }
							</label>
						</FlexItem>
						<FlexItem>
							<ColorIndicator
								colorValue={ config.colors.inputBorder }
							/>
						</FlexItem>
					</Flex>
					<ColorPalette
						colors={ colorPresets }
						value={ config.colors.inputBorder }
						onChange={ ( color ) =>
							updateConfig( 'colors', 'inputBorder', color )
						}
						clearable={ false }
					/>
				</div>

				<div className="eipsi-color-control">
					<Flex align="flex-start" justify="space-between">
						<FlexItem>
							<label>
								{ __(
									'Input Border (Focus)',
									'eipsi-forms'
								) }
							</label>
						</FlexItem>
						<FlexItem>
							<ColorIndicator
								colorValue={ config.colors.inputBorderFocus }
							/>
						</FlexItem>
					</Flex>
					<ColorPalette
						colors={ colorPresets }
						value={ config.colors.inputBorderFocus }
						onChange={ ( color ) =>
							updateConfig( 'colors', 'inputBorderFocus', color )
						}
						clearable={ false }
					/>
				</div>

				{ /* Button Colors */ }
				<h4 className="eipsi-section-title">
					{ __( 'Buttons', 'eipsi-forms' ) }
				</h4>

				<div className="eipsi-color-control">
					<Flex align="flex-start" justify="space-between">
						<FlexItem>
							<label>
								{ __(
									'Button Background',
									'eipsi-forms'
								) }
							</label>
						</FlexItem>
						<FlexItem>
							<ColorIndicator
								colorValue={ config.colors.buttonBg }
							/>
						</FlexItem>
					</Flex>
					<ColorPalette
						colors={ colorPresets }
						value={ config.colors.buttonBg }
						onChange={ ( color ) =>
							updateConfig( 'colors', 'buttonBg', color )
						}
						clearable={ false }
					/>
				</div>

				<div className="eipsi-color-control">
					<Flex align="flex-start" justify="space-between">
						<FlexItem>
							<label>
								{ __( 'Button Text', 'eipsi-forms' ) }
							</label>
						</FlexItem>
						<FlexItem>
							<ColorIndicator
								colorValue={ config.colors.buttonText }
							/>
						</FlexItem>
					</Flex>
					<ColorPalette
						colors={ colorPresets }
						value={ config.colors.buttonText }
						onChange={ ( color ) =>
							updateConfig( 'colors', 'buttonText', color )
						}
						clearable={ false }
					/>
				</div>

				{ ! buttonRating.passes && (
					<Notice status="warning" isDismissible={ false }>
						<strong>
							{ __( 'Contrast Warning:', 'eipsi-forms' ) }
						</strong>{ ' ' }
						{ buttonRating.message }
					</Notice>
				) }

				<div className="eipsi-color-control">
					<Flex align="flex-start" justify="space-between">
						<FlexItem>
							<label>
								{ __( 'Button Hover', 'eipsi-forms' ) }
							</label>
						</FlexItem>
						<FlexItem>
							<ColorIndicator
								colorValue={ config.colors.buttonHoverBg }
							/>
						</FlexItem>
					</Flex>
					<ColorPalette
						colors={ colorPresets }
						value={ config.colors.buttonHoverBg }
						onChange={ ( color ) =>
							updateConfig( 'colors', 'buttonHoverBg', color )
						}
						clearable={ false }
					/>
				</div>

				{ ! buttonHoverRating.passes && (
					<Notice status="warning" isDismissible={ false }>
						<strong>
							{ __( 'Contrast Warning:', 'eipsi-forms' ) }
						</strong>{ ' ' }
						{ __(
							'Button Text on Hover Background:',
							'eipsi-forms'
						) }
						{ buttonHoverRating.message }
					</Notice>
				) }

				{ /* Semantic Colors */ }
				<h4 className="eipsi-section-title">
					{ __( 'Status & Feedback', 'eipsi-forms' ) }
				</h4>

				<div className="eipsi-color-control">
					<Flex align="flex-start" justify="space-between">
						<FlexItem>
							<label>
								{ __( 'Error', 'eipsi-forms' ) }
							</label>
						</FlexItem>
						<FlexItem>
							<ColorIndicator
								colorValue={ config.colors.error }
							/>
						</FlexItem>
					</Flex>
					<ColorPalette
						colors={ colorPresets }
						value={ config.colors.error }
						onChange={ ( color ) =>
							updateConfig( 'colors', 'error', color )
						}
						clearable={ false }
					/>
				</div>

				{ ! errorBgRating.passes && (
					<Notice status="warning" isDismissible={ false }>
						<strong>
							{ __( 'Contrast Warning:', 'eipsi-forms' ) }
						</strong>{ ' ' }
						{ __(
							'Error messages must be readable.',
							'eipsi-forms'
						) }
						{ errorBgRating.message }
					</Notice>
				) }

				<div className="eipsi-color-control">
					<Flex align="flex-start" justify="space-between">
						<FlexItem>
							<label>
								{ __( 'Success', 'eipsi-forms' ) }
							</label>
						</FlexItem>
						<FlexItem>
							<ColorIndicator
								colorValue={ config.colors.success }
							/>
						</FlexItem>
					</Flex>
					<ColorPalette
						colors={ colorPresets }
						value={ config.colors.success }
						onChange={ ( color ) =>
							updateConfig( 'colors', 'success', color )
						}
						clearable={ false }
					/>
				</div>

				{ ! successBgRating.passes && (
					<Notice status="warning" isDismissible={ false }>
						<strong>
							{ __( 'Contrast Warning:', 'eipsi-forms' ) }
						</strong>{ ' ' }
						{ __(
							'Success messages must be readable.',
							'eipsi-forms'
						) }
						{ successBgRating.message }
					</Notice>
				) }

				<div className="eipsi-color-control">
					<Flex align="flex-start" justify="space-between">
						<FlexItem>
							<label>
								{ __( 'Warning', 'eipsi-forms' ) }
							</label>
						</FlexItem>
						<FlexItem>
							<ColorIndicator
								colorValue={ config.colors.warning }
							/>
						</FlexItem>
					</Flex>
					<ColorPalette
						colors={ colorPresets }
						value={ config.colors.warning }
						onChange={ ( color ) =>
							updateConfig( 'colors', 'warning', color )
						}
						clearable={ false }
					/>
				</div>

				{ ! warningBgRating.passes && (
					<Notice status="warning" isDismissible={ false }>
						<strong>
							{ __( 'Contrast Warning:', 'eipsi-forms' ) }
						</strong>{ ' ' }
						{ __(
							'Warning messages must be readable.',
							'eipsi-forms'
						) }
						{ warningBgRating.message }
					</Notice>
				) }

				{ /* Border Colors */ }
				<h4 className="eipsi-section-title">
					{ __( 'Borders', 'eipsi-forms' ) }
				</h4>

				<div className="eipsi-color-control">
					<Flex align="flex-start" justify="space-between">
						<FlexItem>
							<label>
								{ __( 'Border', 'eipsi-forms' ) }
							</label>
						</FlexItem>
						<FlexItem>
							<ColorIndicator
								colorValue={ config.colors.border }
							/>
						</FlexItem>
					</Flex>
					<ColorPalette
						colors={ colorPresets }
						value={ config.colors.border }
						onChange={ ( color ) =>
							updateConfig( 'colors', 'border', color )
						}
						clearable={ false }
					/>
				</div>

				<div className="eipsi-color-control">
					<Flex align="flex-start" justify="space-between">
						<FlexItem>
							<label>
								{ __( 'Border Dark', 'eipsi-forms' ) }
							</label>
						</FlexItem>
						<FlexItem>
							<ColorIndicator
								colorValue={ config.colors.borderDark }
							/>
						</FlexItem>
					</Flex>
					<ColorPalette
						colors={ colorPresets }
						value={ config.colors.borderDark }
						onChange={ ( color ) =>
							updateConfig( 'colors', 'borderDark', color )
						}
						clearable={ false }
					/>
				</div>
			</PanelBody>

			{ /* TYPOGRAPHY PANEL */ }
			<PanelBody
				title={ __( '✍️ Typography', 'eipsi-forms' ) }
				initialOpen={ false }
			>
				<p className="eipsi-panel-description">
					{ __(
						'Configure font families and sizes for optimal readability.',
						'eipsi-forms'
					) }
				</p>

				{ /* Font Families */ }
				<h4 className="eipsi-section-title">
					{ __( 'Font Families', 'eipsi-forms' ) }
				</h4>

				<SelectControl
					label={ __( 'Heading Font', 'eipsi-forms' ) }
					value={ config.typography.fontFamilyHeading }
					options={ fontFamilyOptions }
					onChange={ ( value ) =>
						updateConfig( 'typography', 'fontFamilyHeading', value )
					}
				/>

				<SelectControl
					label={ __( 'Body Font', 'eipsi-forms' ) }
					value={ config.typography.fontFamilyBody }
					options={ fontFamilyOptions }
					onChange={ ( value ) =>
						updateConfig( 'typography', 'fontFamilyBody', value )
					}
				/>

				{ /* Font Sizes */ }
				<h4 className="eipsi-section-title">
					{ __( 'Font Sizes', 'eipsi-forms' ) }
				</h4>

				<TextControl
					label={ __( 'Base Size', 'eipsi-forms' ) }
					value={ config.typography.fontSizeBase }
					onChange={ ( value ) =>
						updateConfig( 'typography', 'fontSizeBase', value )
					}
					help={ __(
						'Recommended: 16px minimum for accessibility',
						'eipsi-forms'
					) }
				/>

				<TextControl
					label={ __( 'Heading 1 Size', 'eipsi-forms' ) }
					value={ config.typography.fontSizeH1 }
					onChange={ ( value ) =>
						updateConfig( 'typography', 'fontSizeH1', value )
					}
				/>

				<TextControl
					label={ __( 'Heading 2 Size', 'eipsi-forms' ) }
					value={ config.typography.fontSizeH2 }
					onChange={ ( value ) =>
						updateConfig( 'typography', 'fontSizeH2', value )
					}
				/>

				<TextControl
					label={ __( 'Heading 3 Size', 'eipsi-forms' ) }
					value={ config.typography.fontSizeH3 }
					onChange={ ( value ) =>
						updateConfig( 'typography', 'fontSizeH3', value )
					}
				/>

				<TextControl
					label={ __( 'Small Text Size', 'eipsi-forms' ) }
					value={ config.typography.fontSizeSmall }
					onChange={ ( value ) =>
						updateConfig( 'typography', 'fontSizeSmall', value )
					}
				/>

				{ /* Font Weights */ }
				<h4 className="eipsi-section-title">
					{ __( 'Font Weights', 'eipsi-forms' ) }
				</h4>

				<RangeControl
					label={ __( 'Normal Weight', 'eipsi-forms' ) }
					value={ parseInt( config.typography.fontWeightNormal ) }
					onChange={ ( value ) =>
						updateConfig(
							'typography',
							'fontWeightNormal',
							String( value )
						)
					}
					min={ 100 }
					max={ 900 }
					step={ 100 }
				/>

				<RangeControl
					label={ __( 'Medium Weight', 'eipsi-forms' ) }
					value={ parseInt( config.typography.fontWeightMedium ) }
					onChange={ ( value ) =>
						updateConfig(
							'typography',
							'fontWeightMedium',
							String( value )
						)
					}
					min={ 100 }
					max={ 900 }
					step={ 100 }
				/>

				<RangeControl
					label={ __( 'Bold Weight', 'eipsi-forms' ) }
					value={ parseInt( config.typography.fontWeightBold ) }
					onChange={ ( value ) =>
						updateConfig(
							'typography',
							'fontWeightBold',
							String( value )
						)
					}
					min={ 100 }
					max={ 900 }
					step={ 100 }
				/>

				{ /* Line Heights */ }
				<h4 className="eipsi-section-title">
					{ __( 'Line Heights', 'eipsi-forms' ) }
				</h4>

				<TextControl
					label={ __( 'Base Line Height', 'eipsi-forms' ) }
					value={ config.typography.lineHeightBase }
					onChange={ ( value ) =>
						updateConfig( 'typography', 'lineHeightBase', value )
					}
					help={ __(
						'Recommended: 1.6–1.8 for comfortable reading',
						'eipsi-forms'
					) }
				/>

				<TextControl
					label={ __( 'Heading Line Height', 'eipsi-forms' ) }
					value={ config.typography.lineHeightHeading }
					onChange={ ( value ) =>
						updateConfig( 'typography', 'lineHeightHeading', value )
					}
				/>
			</PanelBody>

			{ /* SPACING & LAYOUT PANEL */ }
			<PanelBody
				title={ __( '📐 Spacing & Layout', 'eipsi-forms' ) }
				initialOpen={ false }
			>
				<p className="eipsi-panel-description">
					{ __(
						'Control spacing for participant comfort and visual hierarchy.',
						'eipsi-forms'
					) }
				</p>

				<RangeControl
					label={ __( 'Container Padding', 'eipsi-forms' ) }
					value={ parseFloat( config.spacing.containerPadding ) }
					onChange={ ( value ) =>
						updateConfig(
							'spacing',
							'containerPadding',
							`${ value }rem`
						)
					}
					min={ 0 }
					max={ 5 }
					step={ 0.25 }
					help={ __(
						'Breathing room around form content',
						'eipsi-forms'
					) }
				/>

				<RangeControl
					label={ __( 'Field Gap', 'eipsi-forms' ) }
					value={ parseFloat( config.spacing.fieldGap ) }
					onChange={ ( value ) =>
						updateConfig( 'spacing', 'fieldGap', `${ value }rem` )
					}
					min={ 0.5 }
					max={ 4 }
					step={ 0.25 }
					help={ __(
						'Vertical spacing between form fields',
						'eipsi-forms'
					) }
				/>

				<RangeControl
					label={ __( 'Section Gap', 'eipsi-forms' ) }
					value={ parseFloat( config.spacing.sectionGap ) }
					onChange={ ( value ) =>
						updateConfig( 'spacing', 'sectionGap', `${ value }rem` )
					}
					min={ 1 }
					max={ 5 }
					step={ 0.25 }
					help={ __(
						'Spacing between major form sections',
						'eipsi-forms'
					) }
				/>

				<h4 className="eipsi-section-title">
					{ __( 'Spacing Scale', 'eipsi-forms' ) }
				</h4>

				<RangeControl
					label={ __( 'Extra Small', 'eipsi-forms' ) }
					value={ parseFloat( config.spacing.xs ) }
					onChange={ ( value ) =>
						updateConfig( 'spacing', 'xs', `${ value }rem` )
					}
					min={ 0.25 }
					max={ 2 }
					step={ 0.25 }
				/>

				<RangeControl
					label={ __( 'Small', 'eipsi-forms' ) }
					value={ parseFloat( config.spacing.sm ) }
					onChange={ ( value ) =>
						updateConfig( 'spacing', 'sm', `${ value }rem` )
					}
					min={ 0.5 }
					max={ 3 }
					step={ 0.25 }
				/>

				<RangeControl
					label={ __( 'Medium', 'eipsi-forms' ) }
					value={ parseFloat( config.spacing.md ) }
					onChange={ ( value ) =>
						updateConfig( 'spacing', 'md', `${ value }rem` )
					}
					min={ 1 }
					max={ 4 }
					step={ 0.25 }
				/>

				<RangeControl
					label={ __( 'Large', 'eipsi-forms' ) }
					value={ parseFloat( config.spacing.lg ) }
					onChange={ ( value ) =>
						updateConfig( 'spacing', 'lg', `${ value }rem` )
					}
					min={ 1.5 }
					max={ 5 }
					step={ 0.25 }
				/>

				<RangeControl
					label={ __( 'Extra Large', 'eipsi-forms' ) }
					value={ parseFloat( config.spacing.xl ) }
					onChange={ ( value ) =>
						updateConfig( 'spacing', 'xl', `${ value }rem` )
					}
					min={ 2 }
					max={ 6 }
					step={ 0.25 }
				/>
			</PanelBody>

			{ /* BORDERS & RADIUS PANEL */ }
			<PanelBody
				title={ __( '🔲 Borders & Radius', 'eipsi-forms' ) }
				initialOpen={ false }
			>
				<p className="eipsi-panel-description">
					{ __(
						'Configure border styles and corner radius for clinical aesthetics.',
						'eipsi-forms'
					) }
				</p>

				{ /* Border Radius */ }
				<h4 className="eipsi-section-title">
					{ __( 'Border Radius', 'eipsi-forms' ) }
				</h4>

				<RangeControl
					label={ __( 'Small Radius', 'eipsi-forms' ) }
					value={ parseInt( config.borders.radiusSm ) }
					onChange={ ( value ) =>
						updateConfig( 'borders', 'radiusSm', `${ value }px` )
					}
					min={ 0 }
					max={ 20 }
					step={ 1 }
					help={ __(
						'Used for small elements',
						'eipsi-forms'
					) }
				/>

				<RangeControl
					label={ __( 'Medium Radius', 'eipsi-forms' ) }
					value={ parseInt( config.borders.radiusMd ) }
					onChange={ ( value ) =>
						updateConfig( 'borders', 'radiusMd', `${ value }px` )
					}
					min={ 0 }
					max={ 30 }
					step={ 1 }
					help={ __(
						'Used for inputs and buttons',
						'eipsi-forms'
					) }
				/>

				<RangeControl
					label={ __( 'Large Radius', 'eipsi-forms' ) }
					value={ parseInt( config.borders.radiusLg ) }
					onChange={ ( value ) =>
						updateConfig( 'borders', 'radiusLg', `${ value }px` )
					}
					min={ 0 }
					max={ 40 }
					step={ 1 }
					help={ __(
						'Used for containers and sections',
						'eipsi-forms'
					) }
				/>

				{ /* Border Width & Style */ }
				<h4 className="eipsi-section-title">
					{ __( 'Border Width & Style', 'eipsi-forms' ) }
				</h4>

				<RangeControl
					label={ __( 'Border Width', 'eipsi-forms' ) }
					value={ parseInt( config.borders.width ) }
					onChange={ ( value ) =>
						updateConfig( 'borders', 'width', `${ value }px` )
					}
					min={ 0 }
					max={ 10 }
					step={ 1 }
				/>

				<RangeControl
					label={ __( 'Focus Border Width', 'eipsi-forms' ) }
					value={ parseInt( config.borders.widthFocus ) }
					onChange={ ( value ) =>
						updateConfig( 'borders', 'widthFocus', `${ value }px` )
					}
					min={ 0 }
					max={ 10 }
					step={ 1 }
					help={ __(
						'Thicker border for focused elements',
						'eipsi-forms'
					) }
				/>

				<SelectControl
					label={ __( 'Border Style', 'eipsi-forms' ) }
					value={ config.borders.style }
					options={ borderStyleOptions }
					onChange={ ( value ) =>
						updateConfig( 'borders', 'style', value )
					}
				/>
			</PanelBody>

			{ /* SHADOWS & EFFECTS PANEL */ }
			<PanelBody
				title={ __( '✨ Shadows & Effects', 'eipsi-forms' ) }
				initialOpen={ false }
			>
				<p className="eipsi-panel-description">
					{ __(
						'Add depth and visual feedback with shadows.',
						'eipsi-forms'
					) }
				</p>

				<TextControl
					label={ __( 'Small Shadow', 'eipsi-forms' ) }
					value={ config.shadows.sm }
					onChange={ ( value ) =>
						updateConfig( 'shadows', 'sm', value )
					}
					help={ __(
						'Subtle elevation for small elements',
						'eipsi-forms'
					) }
				/>

				<TextControl
					label={ __( 'Medium Shadow', 'eipsi-forms' ) }
					value={ config.shadows.md }
					onChange={ ( value ) =>
						updateConfig( 'shadows', 'md', value )
					}
					help={ __( 'Standard card depth', 'eipsi-forms' ) }
				/>

				<TextControl
					label={ __( 'Large Shadow', 'eipsi-forms' ) }
					value={ config.shadows.lg }
					onChange={ ( value ) =>
						updateConfig( 'shadows', 'lg', value )
					}
					help={ __( 'Prominent elevation', 'eipsi-forms' ) }
				/>

				<TextControl
					label={ __( 'Focus Shadow', 'eipsi-forms' ) }
					value={ config.shadows.focus }
					onChange={ ( value ) =>
						updateConfig( 'shadows', 'focus', value )
					}
					help={ __(
						'Ring effect for focused elements',
						'eipsi-forms'
					) }
				/>
			</PanelBody>

			{ /* HOVER & INTERACTION PANEL */ }
			<PanelBody
				title={ __( '⚡ Hover & Interaction', 'eipsi-forms' ) }
				initialOpen={ false }
			>
				<p className="eipsi-panel-description">
					{ __(
						'Configure animation and interaction feedback.',
						'eipsi-forms'
					) }
				</p>

				<TextControl
					label={ __( 'Transition Duration', 'eipsi-forms' ) }
					value={ config.interactivity.transitionDuration }
					onChange={ ( value ) =>
						updateConfig(
							'interactivity',
							'transitionDuration',
							value
						)
					}
					help={ __( 'E.g., 0.2s or 200ms', 'eipsi-forms' ) }
				/>

				<SelectControl
					label={ __( 'Transition Timing', 'eipsi-forms' ) }
					value={ config.interactivity.transitionTiming }
					options={ [
						{ label: 'Linear', value: 'linear' },
						{ label: 'Ease', value: 'ease' },
						{ label: 'Ease In', value: 'ease-in' },
						{ label: 'Ease Out', value: 'ease-out' },
						{ label: 'Ease In Out', value: 'ease-in-out' },
					] }
					onChange={ ( value ) =>
						updateConfig(
							'interactivity',
							'transitionTiming',
							value
						)
					}
				/>

				<TextControl
					label={ __( 'Hover Scale', 'eipsi-forms' ) }
					value={ config.interactivity.hoverScale }
					onChange={ ( value ) =>
						updateConfig( 'interactivity', 'hoverScale', value )
					}
					help={ __(
						'E.g., 1.02 for slight growth on hover',
						'eipsi-forms'
					) }
				/>

				<RangeControl
					label={ __( 'Focus Outline Width', 'eipsi-forms' ) }
					value={ parseInt( config.interactivity.focusOutlineWidth ) }
					onChange={ ( value ) =>
						updateConfig(
							'interactivity',
							'focusOutlineWidth',
							`${ value }px`
						)
					}
					min={ 0 }
					max={ 10 }
					step={ 1 }
					help={ __(
						'Recommended: 2–3px for accessibility',
						'eipsi-forms'
					) }
				/>

				<RangeControl
					label={ __( 'Focus Outline Offset', 'eipsi-forms' ) }
					value={ parseInt(
						config.interactivity.focusOutlineOffset
					) }
					onChange={ ( value ) =>
						updateConfig(
							'interactivity',
							'focusOutlineOffset',
							`${ value }px`
						)
					}
					min={ 0 }
					max={ 10 }
					step={ 1 }
				/>
			</PanelBody>
		</>
	);
};

export default FormStylePanel;
