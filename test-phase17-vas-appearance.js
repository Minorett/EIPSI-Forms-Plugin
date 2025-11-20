#!/usr/bin/env node

/**
 * Phase 17: VAS Block Appearance Panel - Validation Test Suite
 *
 * Validates the implementation of UnitControl sliders for appearance customization
 *
 * Test Coverage:
 * - New attributes in block.json
 * - UnitControl import and usage
 * - CSS variable application
 * - Modifier class application
 * - Responsive styles
 * - Backward compatibility
 */

const fs = require( 'fs' );
const path = require( 'path' );

// Test results
const results = {
    passed: 0,
    failed: 0,
    tests: [],
};

/**
 * Test assertion
 *
 * @param {string}  description Test description
 * @param {boolean} condition   Test condition
 */
function test( description, condition ) {
    if ( condition ) {
        results.passed++;
        results.tests.push( { description, status: '✅ PASS' } );
        // eslint-disable-next-line no-console
        console.log( `✅ ${ description }` );
    } else {
        results.failed++;
        results.tests.push( { description, status: '❌ FAIL' } );
        // eslint-disable-next-line no-console
        console.error( `❌ ${ description }` );
    }
}

/**
 * Check if file contains pattern
 *
 * @param {string} filePath File path
 * @param {string} pattern  Pattern to search for
 * @return {boolean} True if pattern found
 */
function fileContains( filePath, pattern ) {
    try {
        const content = fs.readFileSync( filePath, 'utf8' );
        if ( pattern instanceof RegExp ) {
            return pattern.test( content );
        }
        return content.includes( pattern );
    } catch ( error ) {
        return false;
    }
}

/**
 * Count occurrences of pattern in file
 *
 * @param {string} filePath File path
 * @param {string} pattern  Pattern to count
 * @return {number} Number of occurrences
 */
function countOccurrences( filePath, pattern ) {
    try {
        const content = fs.readFileSync( filePath, 'utf8' );
        const matches = content.match( new RegExp( pattern, 'g' ) );
        return matches ? matches.length : 0;
    } catch ( error ) {
        return 0;
    }
}

// eslint-disable-next-line no-console
console.log( '\n' + '='.repeat( 70 ) );
// eslint-disable-next-line no-console
console.log( 'PHASE 17: VAS APPEARANCE PANEL - VALIDATION TEST SUITE' );
// eslint-disable-next-line no-console
console.log( '='.repeat( 70 ) + '\n' );

// ============================================================================
// TEST 1: block.json Attributes
// ============================================================================
// eslint-disable-next-line no-console
console.log( '📋 TEST 1: block.json Attributes\n' );

const blockJsonPath = path.join( __dirname, 'blocks/vas-slider/block.json' );
const blockJson = JSON.parse( fs.readFileSync( blockJsonPath, 'utf8' ) );

test(
    '1.1: labelFontSize attribute exists with correct type and default',
    blockJson.attributes.labelFontSize &&
        blockJson.attributes.labelFontSize.type === 'number' &&
        blockJson.attributes.labelFontSize.default === 16
);

test(
    '1.2: valueFontSize attribute exists with correct type and default',
    blockJson.attributes.valueFontSize &&
        blockJson.attributes.valueFontSize.type === 'number' &&
        blockJson.attributes.valueFontSize.default === 36
);

test(
    '1.3: showLabelContainers attribute exists with correct default (false)',
    blockJson.attributes.showLabelContainers &&
        blockJson.attributes.showLabelContainers.type === 'boolean' &&
        blockJson.attributes.showLabelContainers.default === false
);

test(
    '1.4: showValueContainer attribute exists with correct default (false)',
    blockJson.attributes.showValueContainer &&
        blockJson.attributes.showValueContainer.type === 'boolean' &&
        blockJson.attributes.showValueContainer.default === false
);

test(
    '1.5: boldLabels attribute exists with correct default (true)',
    blockJson.attributes.boldLabels &&
        blockJson.attributes.boldLabels.type === 'boolean' &&
        blockJson.attributes.boldLabels.default === true
);

test(
    '1.6: showCurrentValue attribute exists with correct default (true)',
    blockJson.attributes.showCurrentValue &&
        blockJson.attributes.showCurrentValue.type === 'boolean' &&
        blockJson.attributes.showCurrentValue.default === true
);

test(
    '1.7: valuePosition attribute exists with correct enum',
    blockJson.attributes.valuePosition &&
        blockJson.attributes.valuePosition.type === 'string' &&
        blockJson.attributes.valuePosition.default === 'above' &&
        Array.isArray( blockJson.attributes.valuePosition.enum ) &&
        blockJson.attributes.valuePosition.enum.includes( 'above' ) &&
        blockJson.attributes.valuePosition.enum.includes( 'below' )
);

test(
    '1.8: labelSpacing attribute exists (backward compatibility)',
    blockJson.attributes.labelSpacing &&
        blockJson.attributes.labelSpacing.type === 'number' &&
        blockJson.attributes.labelSpacing.default === 100
);

// ============================================================================
// TEST 2: edit.js - UnitControl Import and Usage
// ============================================================================
// eslint-disable-next-line no-console
console.log( '\n📋 TEST 2: edit.js - UnitControl Import and Usage\n' );

const editPath = path.join( __dirname, 'src/blocks/vas-slider/edit.js' );

test(
    '2.1: UnitControl imported from @wordpress/components',
    fileContains(
        editPath,
        '__experimentalUnitControl as UnitControl'
    )
);

test(
    '2.2: ESLint disable comment for experimental API usage',
    fileContains(
        editPath,
        '@wordpress/no-unsafe-wp-apis -- UnitControl is the standard component'
    )
);

test(
    '2.3: SelectControl imported for value position',
    fileContains( editPath, 'SelectControl' )
);

test(
    '2.4: Appearance panel exists with correct title',
    fileContains( editPath, "title={ __( 'Appearance'" )
);

test(
    '2.5: Label Appearance section exists',
    fileContains( editPath, "{ __( 'Label Appearance'" )
);

test(
    '2.6: Value Display section exists',
    fileContains( editPath, "{ __( 'Value Display'" )
);

test(
    '2.7: Show label containers toggle exists',
    fileContains( editPath, 'Show label containers' ) &&
        fileContains( editPath, 'showLabelContainers' )
);

test(
    '2.8: Bold labels toggle exists',
    fileContains( editPath, 'Bold labels' ) &&
        fileContains( editPath, 'boldLabels' )
);

test(
    '2.9: Label size UnitControl exists with correct range',
    fileContains( editPath, 'Label size' ) &&
        fileContains( editPath, 'min={ 12 }' ) &&
        fileContains( editPath, 'max={ 36 }' ) &&
        fileContains( editPath, 'labelFontSize' )
);

test(
    '2.10: Value size UnitControl exists with correct range',
    fileContains( editPath, 'Value size' ) &&
        fileContains( editPath, 'min={ 20 }' ) &&
        fileContains( editPath, 'max={ 80 }' ) &&
        fileContains( editPath, 'valueFontSize' )
);

test(
    '2.11: Show value container toggle exists',
    fileContains( editPath, 'Show value container' ) &&
        fileContains( editPath, 'showValueContainer' )
);

test(
    '2.12: Value position SelectControl exists',
    fileContains( editPath, 'Value position' ) &&
        fileContains( editPath, 'valuePosition' ) &&
        fileContains( editPath, 'Above slider' ) &&
        fileContains( editPath, 'Below slider' )
);

// ============================================================================
// TEST 3: edit.js - CSS Variables and Classes
// ============================================================================
// eslint-disable-next-line no-console
console.log( '\n📋 TEST 3: edit.js - CSS Variables and Classes\n' );

test(
    '3.1: CSS variable --vas-label-size applied',
    fileContains( editPath, "'--vas-label-size': `${ labelFontSize || 16 }px`" )
);

test(
    '3.2: CSS variable --vas-value-size applied',
    fileContains( editPath, "'--vas-value-size': `${ valueFontSize || 36 }px`" )
);

test(
    '3.3: CSS variable --vas-label-spacing applied',
    fileContains( editPath, '--vas-label-spacing' ) &&
        fileContains( editPath, 'labelSpacing !== undefined ? labelSpacing : 100' )
);

test(
    '3.4: Modifier class vas-show-label-containers applied conditionally',
    fileContains(
        editPath,
        "showLabelContainers ? 'vas-show-label-containers' : ''"
    )
);

test(
    '3.5: Modifier class vas-show-value-container applied conditionally',
    fileContains(
        editPath,
        "showValueContainer ? 'vas-show-value-container' : ''"
    )
);

test(
    '3.6: Modifier class vas-bold-labels applied conditionally',
    fileContains( editPath, "boldLabels !== false ? 'vas-bold-labels' : ''" )
);

test(
    '3.7: Modifier class vas-value-below applied conditionally',
    fileContains(
        editPath,
        "valuePosition === 'below' ? 'vas-value-below' : ''"
    )
);

// ============================================================================
// TEST 4: save.js - Frontend Output
// ============================================================================
// eslint-disable-next-line no-console
console.log( '\n📋 TEST 4: save.js - Frontend Output\n' );

const savePath = path.join( __dirname, 'src/blocks/vas-slider/save.js' );

test(
    '4.1: All new attributes destructured in save function',
    fileContains( savePath, 'labelFontSize,' ) &&
        fileContains( savePath, 'valueFontSize,' ) &&
        fileContains( savePath, 'showLabelContainers,' ) &&
        fileContains( savePath, 'showValueContainer,' ) &&
        fileContains( savePath, 'boldLabels,' ) &&
        fileContains( savePath, 'showCurrentValue,' ) &&
        fileContains( savePath, 'valuePosition,' )
);

test(
    '4.2: CSS variables applied in save function',
    fileContains( savePath, "'--vas-label-size': `${ labelFontSize || 16 }px`" ) &&
        fileContains(
            savePath,
            "'--vas-value-size': `${ valueFontSize || 36 }px`"
        ) &&
        fileContains( savePath, '--vas-label-spacing' )
);

test(
    '4.3: Modifier classes applied in save function',
    fileContains( savePath, 'vas-show-label-containers' ) &&
        fileContains( savePath, 'vas-show-value-container' ) &&
        fileContains( savePath, 'vas-bold-labels' ) &&
        fileContains( savePath, 'vas-value-below' )
);

test(
    '4.4: Backward compatibility with showValue attribute',
    fileContains( savePath, 'showValue' ) &&
        fileContains( savePath, 'showCurrentValue' )
);

// ============================================================================
// TEST 5: style.scss - CSS Implementation
// ============================================================================
// eslint-disable-next-line no-console
console.log( '\n📋 TEST 5: style.scss - CSS Implementation\n' );

const stylePath = path.join( __dirname, 'src/blocks/vas-slider/style.scss' );

test(
    '5.1: CSS variable --vas-label-size declared with default',
    fileContains( stylePath, '--vas-label-size: 16px;' )
);

test(
    '5.2: CSS variable --vas-value-size declared with default',
    fileContains( stylePath, '--vas-value-size: 36px;' )
);

test(
    '5.3: CSS variable --vas-label-spacing declared with default',
    fileContains( stylePath, '--vas-label-spacing: 100%;' )
);

test(
    '5.4: Label font-size uses CSS variable',
    fileContains( stylePath, 'font-size: var(--vas-label-size, 16px);' )
);

test(
    '5.5: Value font-size uses CSS variable',
    fileContains( stylePath, 'font-size: var(--vas-value-size, 36px);' )
);

test(
    '5.6: Bold labels modifier class exists',
    fileContains( stylePath, '&.vas-bold-labels .vas-slider-labels' ) &&
        fileContains( stylePath, 'font-weight: 700;' )
);

test(
    '5.7: Show label containers modifier class exists',
    fileContains( stylePath, '&.vas-show-label-containers .vas-slider-labels' )
);

test(
    '5.8: Show value container modifier class exists',
    fileContains( stylePath, '&.vas-show-value-container' )
);

test(
    '5.9: Value position below modifier class exists',
    fileContains( stylePath, '&.vas-value-below' ) &&
        fileContains( stylePath, 'order: 2;' ) &&
        fileContains( stylePath, 'order: 1;' )
);

test(
    '5.10: Responsive font-size adjustments use CSS variables',
    fileContains(
        stylePath,
        'max(12px, calc(var(--vas-label-size, 16px) * 0.9))'
    ) ||
        fileContains(
            stylePath,
            'max(20px, calc(var(--vas-value-size, 36px) * 0.85))'
        )
);

test(
    '5.11: Bold labels modifier applies to multi-labels',
    fileContains(
        stylePath,
        '&.vas-bold-labels .vas-multi-labels .vas-multi-label'
    )
);

test(
    '5.12: Show label containers modifier applies to multi-labels',
    fileContains(
        stylePath,
        '&.vas-show-label-containers .vas-multi-labels .vas-multi-label'
    )
);

// ============================================================================
// TEST 6: Build Output - Compiled Files
// ============================================================================
// eslint-disable-next-line no-console
console.log( '\n📋 TEST 6: Build Output - Compiled Files\n' );

const buildIndexPath = path.join( __dirname, 'build/index.js' );
const buildStylePath = path.join( __dirname, 'build/style-index.css' );

test( '6.1: Build directory exists', fs.existsSync( buildIndexPath ) );

test(
    '6.2: Compiled JS contains UnitControl',
    fileContains( buildIndexPath, 'UnitControl' ) ||
        fileContains( buildIndexPath, 'unit-control' )
);

test(
    '6.3: Compiled CSS contains VAS modifier classes',
    fileContains( buildStylePath, 'vas-show-label-containers' ) &&
        fileContains( buildStylePath, 'vas-show-value-container' ) &&
        fileContains( buildStylePath, 'vas-bold-labels' ) &&
        fileContains( buildStylePath, 'vas-value-below' )
);

test(
    '6.4: Compiled CSS contains CSS variable usage',
    fileContains( buildStylePath, '--vas-label-size' ) &&
        fileContains( buildStylePath, '--vas-value-size' )
);

// ============================================================================
// TEST 7: Backward Compatibility
// ============================================================================
// eslint-disable-next-line no-console
console.log( '\n📋 TEST 7: Backward Compatibility\n' );

test(
    '7.1: Old showValue attribute still present in block.json',
    blockJson.attributes.showValue !== undefined
);

test(
    '7.2: labelAlignmentPercent attribute still present',
    blockJson.attributes.labelAlignmentPercent !== undefined
);

test(
    '7.3: Edit function handles showValue fallback',
    fileContains( editPath, 'showValue' ) &&
        fileContains( editPath, 'showCurrentValue' )
);

test(
    '7.4: Save function handles showValue fallback',
    fileContains( savePath, 'showValue' ) &&
        fileContains( savePath, 'showCurrentValue' )
);

test(
    '7.5: Edit function handles labelAlignmentPercent fallback',
    fileContains( editPath, 'labelAlignmentPercent' ) &&
        fileContains( editPath, 'labelSpacing' )
);

// ============================================================================
// SUMMARY
// ============================================================================
// eslint-disable-next-line no-console
console.log( '\n' + '='.repeat( 70 ) );
// eslint-disable-next-line no-console
console.log( 'TEST SUMMARY' );
// eslint-disable-next-line no-console
console.log( '='.repeat( 70 ) );
// eslint-disable-next-line no-console
console.log( `Total Tests: ${ results.passed + results.failed }` );
// eslint-disable-next-line no-console
console.log( `Passed: ${ results.passed }` );
// eslint-disable-next-line no-console
console.log( `Failed: ${ results.failed }` );
// eslint-disable-next-line no-console
console.log(
    `Success Rate: ${
        (
            ( results.passed / ( results.passed + results.failed ) ) *
            100
        ).toFixed( 1 )
    }%`
);
// eslint-disable-next-line no-console
console.log( '='.repeat( 70 ) );

if ( results.failed === 0 ) {
    // eslint-disable-next-line no-console
    console.log(
        '\n🎉 All tests passed! Phase 17 implementation is complete and verified.\n'
    );
    process.exit( 0 );
} else {
    // eslint-disable-next-line no-console
    console.log(
        `\n⚠️  ${ results.failed } test(s) failed. Please review the errors above.\n`
    );
    process.exit( 1 );
}
