/**
 * Style Tokens Utility
 * Centralizes design token defaults, migration logic, and CSS variable serialization
 * for the EIPSI Forms plugin.
 *
 * @package
 */

/**
 * Default style configuration aligned with clinical design guidance
 */
export const DEFAULT_STYLE_CONFIG = {
	colors: {
		primary: '#005a87',
		primaryHover: '#003d5b',
		secondary: '#e3f2fd',
		background: '#ffffff',
		backgroundSubtle: '#f8f9fa',
		text: '#2c3e50',
		textMuted: '#64748b',
		inputBg: '#ffffff',
		inputText: '#2c3e50',
		inputBorder: '#64748b',
		inputBorderFocus: '#005a87',
		inputErrorBg: '#fff5f5',
		inputIcon: '#005a87',
		buttonBg: '#005a87',
		buttonText: '#ffffff',
		buttonHoverBg: '#003d5b',
		error: '#d32f2f',
		success: '#198754',
		warning: '#b35900',
		border: '#64748b',
		borderDark: '#475569',
	},
	typography: {
		fontFamilyHeading:
			'-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
		fontFamilyBody:
			'-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
		fontSizeBase: '16px',
		fontSizeH1: '2rem',
		fontSizeH2: '1.75rem',
		fontSizeH3: '1.5rem',
		fontSizeSmall: '0.875rem',
		fontWeightNormal: '400',
		fontWeightMedium: '500',
		fontWeightBold: '700',
		lineHeightBase: '1.6',
		lineHeightHeading: '1.3',
	},
	spacing: {
		xs: '0.5rem',
		sm: '1rem',
		md: '1.5rem',
		lg: '2rem',
		xl: '2.5rem',
		containerPadding: '2.5rem',
		fieldGap: '1.5rem',
		sectionGap: '2rem',
	},
	borders: {
		radiusSm: '8px',
		radiusMd: '12px',
		radiusLg: '20px',
		width: '1px',
		widthFocus: '2px',
		style: 'solid',
	},
	shadows: {
		sm: '0 2px 8px rgba(0, 90, 135, 0.08)',
		md: '0 4px 12px rgba(0, 90, 135, 0.1)',
		lg: '0 8px 25px rgba(0, 90, 135, 0.1)',
		focus: '0 0 0 3px rgba(0, 90, 135, 0.1)',
		error: '0 0 0 3px rgba(211, 47, 47, 0.15)',
	},
	interactivity: {
		transitionDuration: '0.2s',
		transitionTiming: 'ease',
		hoverScale: '1.02',
		focusOutlineWidth: '2px',
		focusOutlineOffset: '2px',
	},
};

/**
 * Normalize styleConfig
 * Deep-merges any partial config with DEFAULT_STYLE_CONFIG, so the editor never crashes
 * when a saved block contains an incomplete object (e.g., {} or missing colors).
 *
 * @param {Object} styleConfig - Partial style config
 * @return {Object} Normalized styleConfig
 */
export function normalizeStyleConfig( styleConfig ) {
	const config =
		styleConfig && typeof styleConfig === 'object' ? styleConfig : {};

	return {
		...DEFAULT_STYLE_CONFIG,
		...config,
		colors: { ...DEFAULT_STYLE_CONFIG.colors, ...( config.colors || {} ) },
		typography: {
			...DEFAULT_STYLE_CONFIG.typography,
			...( config.typography || {} ),
		},
		spacing: {
			...DEFAULT_STYLE_CONFIG.spacing,
			...( config.spacing || {} ),
		},
		borders: {
			...DEFAULT_STYLE_CONFIG.borders,
			...( config.borders || {} ),
		},
		shadows: {
			...DEFAULT_STYLE_CONFIG.shadows,
			...( config.shadows || {} ),
		},
		interactivity: {
			...DEFAULT_STYLE_CONFIG.interactivity,
			...( config.interactivity || {} ),
		},
	};
}

/**
 * Migrate legacy attributes to styleConfig format
 * Ensures backward compatibility with forms created before the token system
 *
 * @param {Object} attributes - Block attributes
 * @return {Object} Normalized styleConfig object
 */
export function migrateToStyleConfig( attributes ) {
	// Defensive: ensure attributes is valid object
	if ( ! attributes || typeof attributes !== 'object' ) {
		return JSON.parse( JSON.stringify( DEFAULT_STYLE_CONFIG ) );
	}

	// If styleConfig already exists, normalize it (deep-merge with defaults)
	if (
		attributes.styleConfig &&
		typeof attributes.styleConfig === 'object'
	) {
		return normalizeStyleConfig( attributes.styleConfig );
	}

	// Build config from legacy attributes or defaults
	const config = JSON.parse( JSON.stringify( DEFAULT_STYLE_CONFIG ) );

	// Map legacy attributes to new structure
	if ( attributes.backgroundColor ) {
		config.colors.background = attributes.backgroundColor;
	}

	if ( attributes.textColor ) {
		config.colors.text = attributes.textColor;
	}

	if ( attributes.primaryColor ) {
		config.colors.primary = attributes.primaryColor;
		config.colors.buttonBg = attributes.primaryColor;
	}

	if ( attributes.inputBgColor ) {
		config.colors.inputBg = attributes.inputBgColor;
	}

	if ( attributes.inputTextColor ) {
		config.colors.inputText = attributes.inputTextColor;
	}

	if ( attributes.buttonBgColor ) {
		config.colors.buttonBg = attributes.buttonBgColor;
	}

	if ( attributes.buttonTextColor ) {
		config.colors.buttonText = attributes.buttonTextColor;
	}

	if ( attributes.borderRadius !== undefined ) {
		config.borders.radiusMd = `${ attributes.borderRadius }px`;
	}

	if ( attributes.padding !== undefined ) {
		config.spacing.containerPadding = `${ attributes.padding }px`;
	}

	return config;
}

/**
 * Serialize styleConfig to CSS variables object
 * Generates a flat object of CSS custom properties ready for inline styles
 *
 * @param {Object} styleConfig - Style configuration object
 * @return {Object} CSS variables object
 */
export function serializeToCSSVariables( styleConfig ) {
	// Defensive: deep-merge with defaults to prevent undefined access
	const safeConfig = normalizeStyleConfig( styleConfig );

	return {
		// Colors
		'--eipsi-color-primary': safeConfig.colors.primary,
		'--eipsi-color-primary-hover': safeConfig.colors.primaryHover,
		'--eipsi-color-secondary': safeConfig.colors.secondary,
		'--eipsi-color-background': safeConfig.colors.background,
		'--eipsi-color-background-subtle': safeConfig.colors.backgroundSubtle,
		'--eipsi-color-text': safeConfig.colors.text,
		'--eipsi-color-text-muted': safeConfig.colors.textMuted,
		'--eipsi-color-input-bg': safeConfig.colors.inputBg,
		'--eipsi-color-input-text': safeConfig.colors.inputText,
		'--eipsi-color-input-border': safeConfig.colors.inputBorder,
		'--eipsi-color-input-border-focus': safeConfig.colors.inputBorderFocus,
		'--eipsi-color-input-error-bg': safeConfig.colors.inputErrorBg,
		'--eipsi-color-input-icon': safeConfig.colors.inputIcon,
		'--eipsi-color-button-bg': safeConfig.colors.buttonBg,
		'--eipsi-color-button-text': safeConfig.colors.buttonText,
		'--eipsi-color-button-hover-bg': safeConfig.colors.buttonHoverBg,
		'--eipsi-color-error': safeConfig.colors.error,
		'--eipsi-color-success': safeConfig.colors.success,
		'--eipsi-color-warning': safeConfig.colors.warning,
		'--eipsi-color-border': safeConfig.colors.border,
		'--eipsi-color-border-dark': safeConfig.colors.borderDark,

		// Typography
		'--eipsi-font-family-heading': safeConfig.typography.fontFamilyHeading,
		'--eipsi-font-family-body': safeConfig.typography.fontFamilyBody,
		'--eipsi-font-size-base': safeConfig.typography.fontSizeBase,
		'--eipsi-font-size-h1': safeConfig.typography.fontSizeH1,
		'--eipsi-font-size-h2': safeConfig.typography.fontSizeH2,
		'--eipsi-font-size-h3': safeConfig.typography.fontSizeH3,
		'--eipsi-font-size-small': safeConfig.typography.fontSizeSmall,
		'--eipsi-font-weight-normal': safeConfig.typography.fontWeightNormal,
		'--eipsi-font-weight-medium': safeConfig.typography.fontWeightMedium,
		'--eipsi-font-weight-bold': safeConfig.typography.fontWeightBold,
		'--eipsi-line-height-base': safeConfig.typography.lineHeightBase,
		'--eipsi-line-height-heading': safeConfig.typography.lineHeightHeading,

		// Spacing
		'--eipsi-spacing-xs': safeConfig.spacing.xs,
		'--eipsi-spacing-sm': safeConfig.spacing.sm,
		'--eipsi-spacing-md': safeConfig.spacing.md,
		'--eipsi-spacing-lg': safeConfig.spacing.lg,
		'--eipsi-spacing-xl': safeConfig.spacing.xl,
		'--eipsi-spacing-container-padding':
			safeConfig.spacing.containerPadding,
		'--eipsi-spacing-field-gap': safeConfig.spacing.fieldGap,
		'--eipsi-spacing-section-gap': safeConfig.spacing.sectionGap,

		// Borders
		'--eipsi-border-radius-sm': safeConfig.borders.radiusSm,
		'--eipsi-border-radius-md': safeConfig.borders.radiusMd,
		'--eipsi-border-radius-lg': safeConfig.borders.radiusLg,
		'--eipsi-border-width': safeConfig.borders.width,
		'--eipsi-border-width-focus': safeConfig.borders.widthFocus,
		'--eipsi-border-style': safeConfig.borders.style,

		// Shadows
		'--eipsi-shadow-sm': safeConfig.shadows.sm,
		'--eipsi-shadow-md': safeConfig.shadows.md,
		'--eipsi-shadow-lg': safeConfig.shadows.lg,
		'--eipsi-shadow-focus': safeConfig.shadows.focus,
		'--eipsi-shadow-error': safeConfig.shadows.error,

		// Interactivity
		'--eipsi-transition-duration':
			safeConfig.interactivity.transitionDuration,
		'--eipsi-transition-timing': safeConfig.interactivity.transitionTiming,
		'--eipsi-hover-scale': safeConfig.interactivity.hoverScale,
		'--eipsi-focus-outline-width':
			safeConfig.interactivity.focusOutlineWidth,
		'--eipsi-focus-outline-offset':
			safeConfig.interactivity.focusOutlineOffset,
	};
}

/**
 * Generate inline style string from CSS variables object
 *
 * @param {Object} cssVars - CSS variables object
 * @return {string} Inline style string
 */
export function generateInlineStyle( cssVars ) {
	return Object.entries( cssVars )
		.map( ( [ key, value ] ) => `${ key }: ${ value }` )
		.join( '; ' );
}

/**
 * Sanitize style config values
 * Ensures all values are safe for output
 *
 * @param {Object} config - Style configuration object
 * @return {Object} Sanitized configuration
 */
export function sanitizeStyleConfig( config ) {
	const sanitized = JSON.parse( JSON.stringify( config ) );

	// Sanitize color values (allow hex, rgb, rgba, hsl, hsla)
	const colorRegex = /^(#[0-9a-fA-F]{3,8}|rgba?\([^)]+\)|hsla?\([^)]+\))$/;

	Object.keys( sanitized.colors ).forEach( ( key ) => {
		if ( ! colorRegex.test( sanitized.colors[ key ] ) ) {
			sanitized.colors[ key ] = DEFAULT_STYLE_CONFIG.colors[ key ];
		}
	} );

	// Sanitize spacing values (must include unit)
	const spacingRegex = /^[\d.]+(?:px|rem|em|%)$/;

	Object.keys( sanitized.spacing ).forEach( ( key ) => {
		if ( ! spacingRegex.test( sanitized.spacing[ key ] ) ) {
			sanitized.spacing[ key ] = DEFAULT_STYLE_CONFIG.spacing[ key ];
		}
	} );

	// Sanitize border radius values
	Object.keys( sanitized.borders ).forEach( ( key ) => {
		if (
			key.startsWith( 'radius' ) &&
			! spacingRegex.test( sanitized.borders[ key ] )
		) {
			sanitized.borders[ key ] = DEFAULT_STYLE_CONFIG.borders[ key ];
		}
	} );

	return sanitized;
}

/**
 * Get a readable token name for documentation
 *
 * @param {string} cssVar - CSS variable name
 * @return {string} Human-readable token name
 */
export function getTokenDisplayName( cssVar ) {
	return cssVar
		.replace( '--eipsi-', '' )
		.split( '-' )
		.map( ( word ) => word.charAt( 0 ).toUpperCase() + word.slice( 1 ) )
		.join( ' ' );
}
