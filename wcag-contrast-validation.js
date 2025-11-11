#!/usr/bin/env node

/**
 * WCAG Contrast Validation Script
 * Tests all theme presets for WCAG 2.1 Level AA compliance
 * 
 * Usage: node wcag-contrast-validation.js
 * 
 * Exit codes:
 * 0 - All presets pass WCAG AA
 * 1 - One or more presets fail WCAG AA
 */

// Manually define preset configurations (since we can't easily import from JSX modules)
// These are copied from styleTokens.js and stylePresets.js
const DEFAULT_STYLE_CONFIG = {
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
};

const STYLE_PRESETS = [
    {
        name: 'Clinical Blue',
        description: 'Professional medical research with balanced design and EIPSI blue branding',
        config: DEFAULT_STYLE_CONFIG,
    },
    {
        name: 'Minimal White',
        description: 'Ultra-clean minimalist design with sharp lines and abundant white space',
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
        },
    },
    {
        name: 'Warm Neutral',
        description: 'Warm and approachable with rounded corners and inviting serif typography',
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
        },
    },
    {
        name: 'High Contrast',
        description: 'Maximum accessibility with bold borders, large text, and no visual distractions',
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
                inputErrorBg: '#ffe0e0',
                inputIcon: '#0050d8',
                buttonBg: '#0050d8',
                buttonText: '#ffffff',
                buttonHoverBg: '#003da6',
                error: '#d30000',
                success: '#006600',
                warning: '#b35900',
                border: '#000000',
                borderDark: '#000000',
            },
        },
    },
    {
        name: 'Serene Teal',
        description: 'Calming teal tones with balanced design for therapeutic assessments',
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
        },
    },
    {
        name: 'Dark EIPSI',
        description: 'Professional dark mode with EIPSI blue background and high-contrast light text',
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
        },
    },
];

/**
 * Convert hex color to RGB
 */
function hexToRgb(hex) {
    const cleanHex = hex.replace(/^#/, '');
    let r, g, b;

    if (cleanHex.length === 3) {
        r = parseInt(cleanHex[0] + cleanHex[0], 16);
        g = parseInt(cleanHex[1] + cleanHex[1], 16);
        b = parseInt(cleanHex[2] + cleanHex[2], 16);
    } else if (cleanHex.length === 6) {
        r = parseInt(cleanHex.substring(0, 2), 16);
        g = parseInt(cleanHex.substring(2, 4), 16);
        b = parseInt(cleanHex.substring(4, 6), 16);
    } else {
        return null;
    }

    return { r, g, b };
}

/**
 * Calculate relative luminance
 */
function getLuminance(rgb) {
    if (!rgb) return 0;

    const rsRGB = rgb.r / 255;
    const gsRGB = rgb.g / 255;
    const bsRGB = rgb.b / 255;

    const r = rsRGB <= 0.03928 ? rsRGB / 12.92 : Math.pow((rsRGB + 0.055) / 1.055, 2.4);
    const g = gsRGB <= 0.03928 ? gsRGB / 12.92 : Math.pow((gsRGB + 0.055) / 1.055, 2.4);
    const b = bsRGB <= 0.03928 ? bsRGB / 12.92 : Math.pow((bsRGB + 0.055) / 1.055, 2.4);

    return 0.2126 * r + 0.7152 * g + 0.0722 * b;
}

/**
 * Calculate contrast ratio between two colors
 */
function getContrastRatio(color1, color2) {
    const rgb1 = hexToRgb(color1);
    const rgb2 = hexToRgb(color2);

    if (!rgb1 || !rgb2) return 0;

    const lum1 = getLuminance(rgb1);
    const lum2 = getLuminance(rgb2);

    const lighter = Math.max(lum1, lum2);
    const darker = Math.min(lum1, lum2);

    return (lighter + 0.05) / (darker + 0.05);
}

/**
 * Test color combination for WCAG AA compliance
 */
function testContrast(label, foreground, background, minRatio = 4.5) {
    const ratio = getContrastRatio(foreground, background);
    const passes = ratio >= minRatio;
    
    return {
        label,
        foreground,
        background,
        ratio: ratio.toFixed(2),
        minRatio,
        passes,
        status: passes ? '✓' : '✗',
    };
}

/**
 * Test a single preset for all critical color combinations
 */
function testPreset(preset) {
    const config = preset.config;
    const tests = [];

    // Critical text/background combinations
    tests.push(testContrast('Text vs Background', config.colors.text, config.colors.background));
    tests.push(testContrast('Text Muted vs Background Subtle', config.colors.textMuted, config.colors.backgroundSubtle));
    tests.push(testContrast('Text vs Background Subtle', config.colors.text, config.colors.backgroundSubtle));
    
    // Button combinations
    tests.push(testContrast('Button Text vs Button Background', config.colors.buttonText, config.colors.buttonBg));
    tests.push(testContrast('Button Text vs Button Hover', config.colors.buttonText, config.colors.buttonHoverBg));
    
    // Input combinations
    tests.push(testContrast('Input Text vs Input Background', config.colors.inputText, config.colors.inputBg));
    tests.push(testContrast('Input Border Focus vs Background', config.colors.inputBorderFocus, config.colors.background, 3.0));
    
    // Semantic colors vs background
    tests.push(testContrast('Error vs Background', config.colors.error, config.colors.background));
    tests.push(testContrast('Success vs Background', config.colors.success, config.colors.background));
    tests.push(testContrast('Warning vs Background', config.colors.warning, config.colors.background));
    
    // Border visibility (UI components need 3:1)
    tests.push(testContrast('Border vs Background', config.colors.border, config.colors.background, 3.0));
    tests.push(testContrast('Input Border vs Input Background', config.colors.inputBorder, config.colors.inputBg, 3.0));

    return tests;
}

/**
 * Main validation function
 */
function validatePresets() {
    console.log('\n╔════════════════════════════════════════════════════════════╗');
    console.log('║   WCAG 2.1 Level AA Contrast Validation for EIPSI Forms   ║');
    console.log('╚════════════════════════════════════════════════════════════╝\n');

    let allPass = true;
    const results = {};

    // Test all presets
    STYLE_PRESETS.forEach(preset => {
        console.log(`\n┌─ ${preset.name} ${'─'.repeat(60 - preset.name.length)}`);
        console.log(`│  ${preset.description || 'Default theme configuration'}\n│`);
        
        const tests = testPreset(preset);
        const failures = tests.filter(t => !t.passes);
        
        tests.forEach(test => {
            const statusSymbol = test.passes ? '✓' : '✗';
            const statusColor = test.passes ? '\x1b[32m' : '\x1b[31m';
            const resetColor = '\x1b[0m';
            
            console.log(
                `│  ${statusColor}${statusSymbol}${resetColor} ` +
                `${test.label.padEnd(40)} ` +
                `${test.ratio}:1 (min: ${test.minRatio}:1)`
            );
        });
        
        const passCount = tests.length - failures.length;
        const totalCount = tests.length;
        
        if (failures.length > 0) {
            allPass = false;
            console.log(`│\n│  ⚠️  ${failures.length} test(s) failed`);
        } else {
            console.log(`│\n│  ✓ All ${totalCount} tests passed`);
        }
        
        console.log(`└${'─'.repeat(62)}`);
        
        results[preset.name] = {
            total: totalCount,
            passed: passCount,
            failed: failures.length,
            tests
        };
    });

    // Summary
    console.log('\n' + '='.repeat(64));
    console.log('SUMMARY');
    console.log('='.repeat(64));
    
    Object.entries(results).forEach(([name, result]) => {
        const status = result.failed === 0 ? '✓ PASS' : '✗ FAIL';
        const statusColor = result.failed === 0 ? '\x1b[32m' : '\x1b[31m';
        const resetColor = '\x1b[0m';
        
        console.log(
            `${statusColor}${status}${resetColor} ` +
            `${name.padEnd(35)} ` +
            `${result.passed}/${result.total} tests passed`
        );
    });
    
    console.log('='.repeat(64));
    
    if (allPass) {
        console.log('\n✓ SUCCESS: All presets meet WCAG 2.1 Level AA requirements\n');
        return 0;
    } else {
        console.log('\n✗ FAILURE: Some presets do not meet WCAG AA requirements');
        console.log('Please adjust colors to meet minimum contrast ratios.\n');
        return 1;
    }
}

// Run validation
const exitCode = validatePresets();
process.exit(exitCode);
