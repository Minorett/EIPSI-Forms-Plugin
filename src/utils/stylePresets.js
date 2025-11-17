/**
 * Style Presets for EIPSI Forms
 * Provides pre-configured themes optimized for clinical research
 *
 * @package
 */

import { DEFAULT_STYLE_CONFIG } from './styleTokens';

/**
 * Clinical Blue Theme (Default)
 * Professional, trustworthy, aligned with EIPSI branding
 * Visual Identity: EIPSI blue, balanced spacing, subtle shadows, modern sans-serif
 */
const CLINICAL_BLUE = {
	name: 'Clinical Blue',
	description:
		'Professional medical research with balanced design and EIPSI blue branding',
	config: { ...DEFAULT_STYLE_CONFIG },
};

/**
 * Minimal White Theme
 * Ultra-clean, distraction-free for sensitive assessments
 * Visual Identity: Sharp corners, no shadows, generous white space, muted slate accent
 */
const MINIMAL_WHITE = {
	name: 'Minimal White',
	description:
		'Ultra-clean minimalist design with sharp lines and abundant white space',
	config: {
		colors: {
			primary: '#475569',
			primaryHover: '#1e293b',
			secondary: '#f8fafc',
			background: '#ffffff',
			backgroundSubtle: '#fafbfc',
			text: '#0f172a',
			textMuted: '#556677',
			inputBg: '#ffffff',
			inputText: '#0f172a',
			inputBorder: '#64748b',
			inputBorderFocus: '#475569',
			inputErrorBg: '#fef2f2',
			inputIcon: '#475569',
			buttonBg: '#475569',
			buttonText: '#ffffff',
			buttonHoverBg: '#1e293b',
			error: '#c53030',
			success: '#28744c',
			warning: '#b35900',
			border: '#64748b',
			borderDark: '#475569',
		},
		typography: {
			fontFamilyHeading:
				'-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
			fontFamilyBody:
				'-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
			fontSizeBase: '16px',
			fontSizeH1: '1.875rem',
			fontSizeH2: '1.5rem',
			fontSizeH3: '1.25rem',
			fontSizeSmall: '0.875rem',
			fontWeightNormal: '400',
			fontWeightMedium: '500',
			fontWeightBold: '600',
			lineHeightBase: '1.7',
			lineHeightHeading: '1.25',
		},
		spacing: {
			xs: '0.75rem',
			sm: '1.25rem',
			md: '2rem',
			lg: '2.75rem',
			xl: '4rem',
			containerPadding: '3.5rem',
			fieldGap: '2rem',
			sectionGap: '3rem',
		},
		borders: {
			radiusSm: '4px',
			radiusMd: '6px',
			radiusLg: '8px',
			width: '1px',
			widthFocus: '2px',
			style: 'solid',
		},
		shadows: {
			sm: 'none',
			md: 'none',
			lg: 'none',
			focus: '0 0 0 3px rgba(71, 85, 105, 0.15)',
			error: '0 0 0 3px rgba(197, 48, 48, 0.15)',
		},
		interactivity: {
			transitionDuration: '0.15s',
			transitionTiming: 'ease',
			hoverScale: '1',
			focusOutlineWidth: '2px',
			focusOutlineOffset: '2px',
		},
	},
};

/**
 * Warm Neutral Theme
 * Comfortable, approachable for psychotherapy contexts
 * Visual Identity: Rounded corners, warm tones, serif headings, gentle shadows
 */
const WARM_NEUTRAL = {
	name: 'Warm Neutral',
	description:
		'Warm and approachable with rounded corners and inviting serif typography',
	config: {
		colors: {
			primary: '#8b6f47',
			primaryHover: '#6b5437',
			secondary: '#f5f1eb',
			background: '#fdfcfa',
			backgroundSubtle: '#f7f4ef',
			text: '#3d3935',
			textMuted: '#6b6560',
			inputBg: '#ffffff',
			inputText: '#3d3935',
			inputBorder: '#8b7a65',
			inputBorderFocus: '#8b6f47',
			inputErrorBg: '#fff5f5',
			inputIcon: '#8b6f47',
			buttonBg: '#8b6f47',
			buttonText: '#ffffff',
			buttonHoverBg: '#6b5437',
			error: '#c53030',
			success: '#2a7850',
			warning: '#b04d1f',
			border: '#8b7a65',
			borderDark: '#6b5437',
		},
		typography: {
			fontFamilyHeading: 'Georgia, "Times New Roman", serif',
			fontFamilyBody:
				'-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
			fontSizeBase: '16px',
			fontSizeH1: '2rem',
			fontSizeH2: '1.75rem',
			fontSizeH3: '1.5rem',
			fontSizeSmall: '0.875rem',
			fontWeightNormal: '400',
			fontWeightMedium: '500',
			fontWeightBold: '700',
			lineHeightBase: '1.7',
			lineHeightHeading: '1.35',
		},
		spacing: {
			xs: '0.5rem',
			sm: '1rem',
			md: '1.5rem',
			lg: '2rem',
			xl: '2.5rem',
			containerPadding: '2.5rem',
			fieldGap: '1.75rem',
			sectionGap: '2.25rem',
		},
		borders: {
			radiusSm: '10px',
			radiusMd: '14px',
			radiusLg: '20px',
			width: '1px',
			widthFocus: '2px',
			style: 'solid',
		},
		shadows: {
			sm: '0 2px 8px rgba(139, 111, 71, 0.08)',
			md: '0 4px 12px rgba(139, 111, 71, 0.12)',
			lg: '0 8px 25px rgba(139, 111, 71, 0.15)',
			focus: '0 0 0 3px rgba(139, 111, 71, 0.15)',
			error: '0 0 0 3px rgba(197, 48, 48, 0.15)',
		},
		interactivity: {
			transitionDuration: '0.25s',
			transitionTiming: 'ease-out',
			hoverScale: '1.01',
			focusOutlineWidth: '2px',
			focusOutlineOffset: '2px',
		},
	},
};

/**
 * Serene Teal Theme
 * Calming, therapeutic for stress-reduction studies
 * Visual Identity: Soft teal palette, balanced curves, gentle shadows, modern sans-serif
 */
const SERENE_TEAL = {
	name: 'Serene Teal',
	description:
		'Calming teal tones with balanced design for therapeutic assessments',
	config: {
		colors: {
			primary: '#0e7490',
			primaryHover: '#155e75',
			secondary: '#e0f2fe',
			background: '#ffffff',
			backgroundSubtle: '#f0f9ff',
			text: '#0c4a6e',
			textMuted: '#475569',
			inputBg: '#ffffff',
			inputText: '#0c4a6e',
			inputBorder: '#0891b2',
			inputBorderFocus: '#0e7490',
			inputErrorBg: '#fef2f2',
			inputIcon: '#0e7490',
			buttonBg: '#0e7490',
			buttonText: '#ffffff',
			buttonHoverBg: '#155e75',
			error: '#dc2626',
			success: '#047857',
			warning: '#b35900',
			border: '#0891b2',
			borderDark: '#0e7490',
		},
		typography: {
			fontFamilyHeading:
				'-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
			fontFamilyBody:
				'-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
			fontSizeBase: '16px',
			fontSizeH1: '2rem',
			fontSizeH2: '1.625rem',
			fontSizeH3: '1.375rem',
			fontSizeSmall: '0.875rem',
			fontWeightNormal: '400',
			fontWeightMedium: '500',
			fontWeightBold: '600',
			lineHeightBase: '1.65',
			lineHeightHeading: '1.3',
		},
		spacing: {
			xs: '0.5rem',
			sm: '1rem',
			md: '1.75rem',
			lg: '2.25rem',
			xl: '3rem',
			containerPadding: '2.75rem',
			fieldGap: '1.75rem',
			sectionGap: '2.5rem',
		},
		borders: {
			radiusSm: '10px',
			radiusMd: '16px',
			radiusLg: '24px',
			width: '1px',
			widthFocus: '2px',
			style: 'solid',
		},
		shadows: {
			sm: '0 2px 8px rgba(8, 145, 178, 0.08)',
			md: '0 4px 12px rgba(8, 145, 178, 0.1)',
			lg: '0 8px 24px rgba(8, 145, 178, 0.12)',
			focus: '0 0 0 3px rgba(8, 145, 178, 0.2)',
			error: '0 0 0 3px rgba(220, 38, 38, 0.15)',
		},
		interactivity: {
			transitionDuration: '0.25s',
			transitionTiming: 'ease-out',
			hoverScale: '1.015',
			focusOutlineWidth: '2px',
			focusOutlineOffset: '2px',
		},
	},
};

/**
 * Dark EIPSI Theme
 * High-contrast dark mode with EIPSI blue background
 * Visual Identity: Dark blue background, light text, reduced eye strain, professional dark mode
 */
const DARK_EIPSI = {
	name: 'Dark EIPSI',
	description:
		'Professional dark mode with EIPSI blue background and high-contrast light text',
	config: {
		colors: {
			primary: '#22d3ee',
			primaryHover: '#06b6d4',
			secondary: '#0c4a6e',
			background: '#005a87',
			backgroundSubtle: '#003d5b',
			text: '#ffffff',
			textMuted: '#94a3b8',
			inputBg: '#f8f9fa',
			inputText: '#1e293b',
			inputBorder: '#64748b',
			inputBorderFocus: '#22d3ee',
			inputErrorBg: '#fff5f5',
			inputIcon: '#1e293b',
			buttonBg: '#0e7490',
			buttonText: '#ffffff',
			buttonHoverBg: '#155e75',
			error: '#fecaca',
			success: '#6ee7b7',
			warning: '#fcd34d',
			border: '#cbd5e1',
			borderDark: '#e2e8f0',
		},
		typography: {
			fontFamilyHeading:
				'-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
			fontFamilyBody:
				'-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
			fontSizeBase: '16px',
			fontSizeH1: '2rem',
			fontSizeH2: '1.75rem',
			fontSizeH3: '1.5rem',
			fontSizeSmall: '0.875rem',
			fontWeightNormal: '400',
			fontWeightMedium: '500',
			fontWeightBold: '600',
			lineHeightBase: '1.65',
			lineHeightHeading: '1.3',
		},
		spacing: {
			xs: '0.5rem',
			sm: '1rem',
			md: '1.75rem',
			lg: '2.25rem',
			xl: '3rem',
			containerPadding: '2.5rem',
			fieldGap: '1.75rem',
			sectionGap: '2.5rem',
		},
		borders: {
			radiusSm: '8px',
			radiusMd: '12px',
			radiusLg: '16px',
			width: '1px',
			widthFocus: '2px',
			style: 'solid',
		},
		shadows: {
			sm: '0 2px 8px rgba(0, 0, 0, 0.25)',
			md: '0 4px 12px rgba(0, 0, 0, 0.3)',
			lg: '0 8px 25px rgba(0, 0, 0, 0.35)',
			focus: '0 0 0 3px rgba(34, 211, 238, 0.4)',
			error: '0 0 0 3px rgba(252, 165, 165, 0.3)',
		},
		interactivity: {
			transitionDuration: '0.2s',
			transitionTiming: 'ease',
			hoverScale: '1.01',
			focusOutlineWidth: '2px',
			focusOutlineOffset: '2px',
		},
	},
};

/**
 * All available presets
 */
export const STYLE_PRESETS = [
	CLINICAL_BLUE,
	MINIMAL_WHITE,
	WARM_NEUTRAL,
	SERENE_TEAL,
	DARK_EIPSI,
];

/**
 * Get preset by name
 *
 * @param {string} name - Preset name
 * @return {Object|null} Preset object or null if not found
 */
export function getPresetByName( name ) {
	return STYLE_PRESETS.find( ( preset ) => preset.name === name ) || null;
}

/**
 * Get preset preview data (for thumbnail)
 * Includes colors, typography hints, and visual characteristics
 *
 * @param {Object} preset - Preset object
 * @return {Object} Preview data with colors and styling hints
 */
export function getPresetPreview( preset ) {
	return {
		primary: preset.config.colors.primary,
		background: preset.config.colors.background,
		backgroundSubtle: preset.config.colors.backgroundSubtle,
		text: preset.config.colors.text,
		border: preset.config.colors.border,
		buttonBg: preset.config.colors.buttonBg,
		buttonText: preset.config.colors.buttonText,
		borderRadius: preset.config.borders.radiusMd,
		shadow: preset.config.shadows.md,
		fontFamily: preset.config.typography.fontFamilyHeading,
	};
}
