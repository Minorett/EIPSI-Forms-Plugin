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
 */
const CLINICAL_BLUE = {
	name: 'Clinical Blue',
	description: 'Professional medical research aesthetic with EIPSI blue',
	config: { ...DEFAULT_STYLE_CONFIG },
};

/**
 * Minimal White Theme
 * Clean, distraction-free for sensitive assessments
 */
const MINIMAL_WHITE = {
	name: 'Minimal White',
	description: 'Clean and minimal for distraction-free assessments',
	config: {
		colors: {
			primary: '#2c5aa0',
			primaryHover: '#1e3a70',
			secondary: '#f0f4f8',
			background: '#ffffff',
			backgroundSubtle: '#fafbfc',
			text: '#1a202c',
			textMuted: '#718096',
			inputBg: '#ffffff',
			inputText: '#1a202c',
			inputBorder: '#e2e8f0',
			inputBorderFocus: '#2c5aa0',
			buttonBg: '#2c5aa0',
			buttonText: '#ffffff',
			buttonHoverBg: '#1e3a70',
			error: '#e53e3e',
			success: '#38a169',
			warning: '#d69e2e',
			border: '#e2e8f0',
			borderDark: '#cbd5e0',
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
			lineHeightBase: '1.625',
			lineHeightHeading: '1.3',
		},
		spacing: {
			xs: '0.5rem',
			sm: '1rem',
			md: '1.5rem',
			lg: '2rem',
			xl: '3rem',
			containerPadding: '3rem',
			fieldGap: '1.5rem',
			sectionGap: '2rem',
		},
		borders: {
			radiusSm: '6px',
			radiusMd: '8px',
			radiusLg: '12px',
			width: '1px',
			widthFocus: '2px',
			style: 'solid',
		},
		shadows: {
			sm: '0 1px 3px rgba(0, 0, 0, 0.08)',
			md: '0 4px 6px rgba(0, 0, 0, 0.1)',
			lg: '0 10px 20px rgba(0, 0, 0, 0.12)',
			focus: '0 0 0 3px rgba(44, 90, 160, 0.15)',
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
 */
const WARM_NEUTRAL = {
	name: 'Warm Neutral',
	description: 'Warm and approachable tones for participant comfort',
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
			inputBorder: '#e5ded4',
			inputBorderFocus: '#8b6f47',
			buttonBg: '#8b6f47',
			buttonText: '#ffffff',
			buttonHoverBg: '#6b5437',
			error: '#c53030',
			success: '#2f855a',
			warning: '#c05621',
			border: '#e5ded4',
			borderDark: '#d4c9bb',
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
 * High Contrast Theme
 * Maximum readability for accessibility
 */
const HIGH_CONTRAST = {
	name: 'High Contrast',
	description: 'Maximum readability for visually impaired participants',
	config: {
		colors: {
			primary: '#0050d8',
			primaryHover: '#003da6',
			secondary: '#f0f0f0',
			background: '#ffffff',
			backgroundSubtle: '#f8f8f8',
			text: '#000000',
			textMuted: '#3d3d3d',
			inputBg: '#ffffff',
			inputText: '#000000',
			inputBorder: '#000000',
			inputBorderFocus: '#0050d8',
			buttonBg: '#0050d8',
			buttonText: '#ffffff',
			buttonHoverBg: '#003da6',
			error: '#d30000',
			success: '#006600',
			warning: '#b35900',
			border: '#000000',
			borderDark: '#000000',
		},
		typography: {
			fontFamilyHeading: 'Arial, sans-serif',
			fontFamilyBody: 'Arial, sans-serif',
			fontSizeBase: '18px',
			fontSizeH1: '2.25rem',
			fontSizeH2: '1.875rem',
			fontSizeH3: '1.5rem',
			fontSizeSmall: '1rem',
			fontWeightNormal: '400',
			fontWeightMedium: '600',
			fontWeightBold: '700',
			lineHeightBase: '1.8',
			lineHeightHeading: '1.4',
		},
		spacing: {
			xs: '0.75rem',
			sm: '1.25rem',
			md: '1.75rem',
			lg: '2.25rem',
			xl: '3rem',
			containerPadding: '2rem',
			fieldGap: '1.75rem',
			sectionGap: '2.5rem',
		},
		borders: {
			radiusSm: '4px',
			radiusMd: '6px',
			radiusLg: '8px',
			width: '2px',
			widthFocus: '3px',
			style: 'solid',
		},
		shadows: {
			sm: 'none',
			md: 'none',
			lg: 'none',
			focus: '0 0 0 4px rgba(0, 80, 216, 0.3)',
		},
		interactivity: {
			transitionDuration: '0.1s',
			transitionTiming: 'linear',
			hoverScale: '1',
			focusOutlineWidth: '3px',
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
	HIGH_CONTRAST,
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
 * Get preset preview colors (for thumbnail)
 *
 * @param {Object} preset - Preset object
 * @return {Object} Preview colors
 */
export function getPresetPreview( preset ) {
	return {
		primary: preset.config.colors.primary,
		background: preset.config.colors.background,
		text: preset.config.colors.text,
		border: preset.config.colors.border,
	};
}
