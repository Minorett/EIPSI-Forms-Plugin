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
        inputBorder: '#e2e8f0',
        inputBorderFocus: '#005a87',
        buttonBg: '#005a87',
        buttonText: '#ffffff',
        buttonHoverBg: '#003d5b',
        error: '#d32f2f',
        success: '#198754',
        warning: '#b35900',
        border: '#e2e8f0',
        borderDark: '#cbd5e0',
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
 * Migrate legacy attributes to styleConfig format
 * Ensures backward compatibility with forms created before the token system
 *
 * @param {Object} attributes - Block attributes
 * @return {Object} Normalized styleConfig object
 */
export function migrateToStyleConfig( attributes ) {
    // If styleConfig already exists and is valid, return it
    if (
        attributes.styleConfig &&
        typeof attributes.styleConfig === 'object'
    ) {
        return attributes.styleConfig;
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
    const config = styleConfig || DEFAULT_STYLE_CONFIG;

    return {
        // Colors
        '--eipsi-color-primary': config.colors.primary,
        '--eipsi-color-primary-hover': config.colors.primaryHover,
        '--eipsi-color-secondary': config.colors.secondary,
        '--eipsi-color-background': config.colors.background,
        '--eipsi-color-background-subtle': config.colors.backgroundSubtle,
        '--eipsi-color-text': config.colors.text,
        '--eipsi-color-text-muted': config.colors.textMuted,
        '--eipsi-color-input-bg': config.colors.inputBg,
        '--eipsi-color-input-text': config.colors.inputText,
        '--eipsi-color-input-border': config.colors.inputBorder,
        '--eipsi-color-input-border-focus': config.colors.inputBorderFocus,
        '--eipsi-color-button-bg': config.colors.buttonBg,
        '--eipsi-color-button-text': config.colors.buttonText,
        '--eipsi-color-button-hover-bg': config.colors.buttonHoverBg,
        '--eipsi-color-error': config.colors.error,
        '--eipsi-color-success': config.colors.success,
        '--eipsi-color-warning': config.colors.warning,
        '--eipsi-color-border': config.colors.border,
        '--eipsi-color-border-dark': config.colors.borderDark,

        // Typography
        '--eipsi-font-family-heading': config.typography.fontFamilyHeading,
        '--eipsi-font-family-body': config.typography.fontFamilyBody,
        '--eipsi-font-size-base': config.typography.fontSizeBase,
        '--eipsi-font-size-h1': config.typography.fontSizeH1,
        '--eipsi-font-size-h2': config.typography.fontSizeH2,
        '--eipsi-font-size-h3': config.typography.fontSizeH3,
        '--eipsi-font-size-small': config.typography.fontSizeSmall,
        '--eipsi-font-weight-normal': config.typography.fontWeightNormal,
        '--eipsi-font-weight-medium': config.typography.fontWeightMedium,
        '--eipsi-font-weight-bold': config.typography.fontWeightBold,
        '--eipsi-line-height-base': config.typography.lineHeightBase,
        '--eipsi-line-height-heading': config.typography.lineHeightHeading,

        // Spacing
        '--eipsi-spacing-xs': config.spacing.xs,
        '--eipsi-spacing-sm': config.spacing.sm,
        '--eipsi-spacing-md': config.spacing.md,
        '--eipsi-spacing-lg': config.spacing.lg,
        '--eipsi-spacing-xl': config.spacing.xl,
        '--eipsi-spacing-container-padding': config.spacing.containerPadding,
        '--eipsi-spacing-field-gap': config.spacing.fieldGap,
        '--eipsi-spacing-section-gap': config.spacing.sectionGap,

        // Borders
        '--eipsi-border-radius-sm': config.borders.radiusSm,
        '--eipsi-border-radius-md': config.borders.radiusMd,
        '--eipsi-border-radius-lg': config.borders.radiusLg,
        '--eipsi-border-width': config.borders.width,
        '--eipsi-border-width-focus': config.borders.widthFocus,
        '--eipsi-border-style': config.borders.style,

        // Shadows
        '--eipsi-shadow-sm': config.shadows.sm,
        '--eipsi-shadow-md': config.shadows.md,
        '--eipsi-shadow-lg': config.shadows.lg,
        '--eipsi-shadow-focus': config.shadows.focus,

        // Interactivity
        '--eipsi-transition-duration': config.interactivity.transitionDuration,
        '--eipsi-transition-timing': config.interactivity.transitionTiming,
        '--eipsi-hover-scale': config.interactivity.hoverScale,
        '--eipsi-focus-outline-width': config.interactivity.focusOutlineWidth,
        '--eipsi-focus-outline-offset': config.interactivity.focusOutlineOffset,
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
