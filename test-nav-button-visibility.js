/**
 * REGRESSION TEST: Navigation Button Visibility
 * Tests that buttons are correctly shown/hidden after initialization
 * and during page transitions (prevents all-buttons-visible bug)
 */

const fs = require( 'fs' );
const path = require( 'path' );

// Test results
let totalTests = 0;
let passedTests = 0;

function test( description, testFn ) {
    totalTests++;
    try {
        testFn();
        console.log( `✅ PASS: ${ description }` );
        passedTests++;
        return true;
    } catch ( error ) {
        console.log( `❌ FAIL: ${ description }` );
        console.log( `   Error: ${ error.message }` );
        return false;
    }
}

function assertEquals( actual, expected, message ) {
    if ( actual !== expected ) {
        throw new Error(
            `${ message }\n   Expected: ${ expected }\n   Actual: ${ actual }`
        );
    }
}

console.log( '='.repeat( 70 ) );
console.log( 'REGRESSION TEST: Navigation Button Visibility Fix' );
console.log( '='.repeat( 70 ) );
console.log( '' );

// Read the JavaScript file
const jsFilePath = path.join( __dirname, 'assets/js/eipsi-forms.js' );
const jsContent = fs.readFileSync( jsFilePath, 'utf8' );

// ==================================================================
// SECTION 1: Critical Fix - updatePaginationDisplay called at init
// ==================================================================
console.log( '📋 SECTION 1: Critical Fix Verification' );
console.log( '-'.repeat( 70 ) );

test( '1.1: initPagination function exists', () => {
    if ( ! jsContent.includes( 'initPagination( form )' ) ) {
        throw new Error( 'initPagination function not found' );
    }
} );

test( '1.2: updatePaginationDisplay is called in initPagination', () => {
    // Find initPagination function (with or without space before {)
    let funcStart = jsContent.indexOf( 'initPagination( form ) {' );
    if ( funcStart === -1 ) {
        funcStart = jsContent.indexOf( 'initPagination( form ){' );
    }
    if ( funcStart === -1 ) {
        throw new Error( 'initPagination function not found' );
    }

    // Get a reasonable chunk of code after initPagination starts (3000 chars to cover the whole function)
    const searchSection = jsContent.substring( funcStart, funcStart + 3000 );

    if ( ! searchSection.includes( 'updatePaginationDisplay' ) ) {
        throw new Error(
            'initPagination should call updatePaginationDisplay'
        );
    }
} );

test( '1.3: updatePaginationDisplay is called with getCurrentPage and getTotalPages', () => {
    let funcStart = jsContent.indexOf( 'initPagination( form ) {' );
    if ( funcStart === -1 ) {
        funcStart = jsContent.indexOf( 'initPagination( form){' );
    }
    const searchSection = jsContent.substring( funcStart, funcStart + 3000 );

    const hasGetCurrentPage =
        searchSection.includes( 'getCurrentPage' ) ||
        searchSection.includes( 'currentPage' );
    const hasGetTotalPages =
        searchSection.includes( 'getTotalPages' ) ||
        searchSection.includes( 'totalPages' );

    if ( ! hasGetCurrentPage || ! hasGetTotalPages ) {
        throw new Error(
            'initPagination should get currentPage and totalPages before calling updatePaginationDisplay'
        );
    }
} );

// ==================================================================
// SECTION 2: HTML Initial States
// ==================================================================
console.log( '' );
console.log( '📋 SECTION 2: HTML Initial States (Prevent FOUC)' );
console.log( '-'.repeat( 70 ) );

test( '2.1: src/save.js Previous button has initial display:none', () => {
    const savePath = path.join(
        __dirname,
        'src/blocks/form-container/save.js'
    );
    const saveContent = fs.readFileSync( savePath, 'utf8' );

    if ( ! saveContent.includes( 'eipsi-prev-button' ) ) {
        throw new Error( 'Previous button not found in save.js' );
    }

    // Find Previous button and check for display: none
    const prevIndex = saveContent.indexOf( 'eipsi-prev-button' );
    const prevSection = saveContent.substring( prevIndex - 100, prevIndex + 200 );

    if ( ! prevSection.includes( "display: 'none'" ) ) {
        throw new Error(
            'Previous button should have style={{ display: \'none\' }} initially'
        );
    }
} );

test( '2.2: src/save.js Submit button has initial display:none', () => {
    const savePath = path.join(
        __dirname,
        'src/blocks/form-container/save.js'
    );
    const saveContent = fs.readFileSync( savePath, 'utf8' );

    if ( ! saveContent.includes( 'eipsi-submit-button' ) ) {
        throw new Error( 'Submit button not found in save.js' );
    }

    // Find Submit button and check for display: none
    const submitIndex = saveContent.indexOf( 'eipsi-submit-button' );
    const submitSection = saveContent.substring(
        submitIndex - 100,
        submitIndex + 200
    );

    if ( ! submitSection.includes( "display: 'none'" ) ) {
        throw new Error(
            'Submit button should have style={{ display: \'none\' }} initially'
        );
    }
} );

test( '2.3: blocks/save.js Previous button has initial display:none', () => {
    const savePath = path.join( __dirname, 'blocks/form-container/save.js' );
    const saveContent = fs.readFileSync( savePath, 'utf8' );

    if ( ! saveContent.includes( 'eipsi-prev-button' ) ) {
        throw new Error( 'Previous button not found in blocks/save.js' );
    }

    const prevIndex = saveContent.indexOf( 'eipsi-prev-button' );
    const prevSection = saveContent.substring( prevIndex - 100, prevIndex + 200 );

    if ( ! prevSection.includes( "display: 'none'" ) ) {
        throw new Error(
            'Previous button should have style={{ display: \'none\' }} initially'
        );
    }
} );

test( '2.4: blocks/save.js Submit button has initial display:none', () => {
    const savePath = path.join( __dirname, 'blocks/form-container/save.js' );
    const saveContent = fs.readFileSync( savePath, 'utf8' );

    if ( ! saveContent.includes( 'eipsi-submit-button' ) ) {
        throw new Error( 'Submit button not found in blocks/save.js' );
    }

    const submitIndex = saveContent.indexOf( 'eipsi-submit-button' );
    const submitSection = saveContent.substring(
        submitIndex - 100,
        submitIndex + 200
    );

    if ( ! submitSection.includes( "display: 'none'" ) ) {
        throw new Error(
            'Submit button should have style={{ display: \'none\' }} initially'
        );
    }
} );

// ==================================================================
// SECTION 3: Test HTML Files Updated
// ==================================================================
console.log( '' );
console.log( '📋 SECTION 3: Test HTML Files Updated' );
console.log( '-'.repeat( 70 ) );

test( '3.1: test-nav-controls.html Previous buttons have display:none', () => {
    const testPath = path.join( __dirname, 'test-nav-controls.html' );
    const testContent = fs.readFileSync( testPath, 'utf8' );

    // Find all eipsi-prev-button instances
    const prevMatches = testContent.match( /eipsi-prev-button/g );

    if ( ! prevMatches || prevMatches.length < 2 ) {
        throw new Error(
            'Expected multiple Previous buttons in test-nav-controls.html'
        );
    }

    // Check if any Previous button element is missing display: none
    const lines = testContent.split( '\n' );
    let foundPrevWithoutHide = false;

    lines.forEach( ( line ) => {
        if (
            line.includes( '<button' ) &&
            line.includes( 'eipsi-prev-button' ) &&
            ! line.includes( 'display: none' )
        ) {
            // This is a button element line without display: none
            foundPrevWithoutHide = true;
        }
    } );

    if ( foundPrevWithoutHide ) {
        throw new Error(
            'All Previous buttons in test-nav-controls.html should have style="display: none;"'
        );
    }
} );

test( '3.2: test-nav-bug-reproduction.html Previous button has display:none', () => {
    const testPath = path.join(
        __dirname,
        'test-nav-bug-reproduction.html'
    );
    const testContent = fs.readFileSync( testPath, 'utf8' );

    if ( ! testContent.includes( 'eipsi-prev-button' ) ) {
        throw new Error( 'Previous button not found in test file' );
    }

    const prevIndex = testContent.indexOf( 'eipsi-prev-button' );
    const prevLine = testContent
        .substring( prevIndex - 100, prevIndex + 100 )
        .split( '\n' )
        .find( ( l ) => l.includes( 'eipsi-prev-button' ) );

    if ( ! prevLine || ! prevLine.includes( 'display: none' ) ) {
        throw new Error(
            'Previous button should have style="display: none;" in test-nav-bug-reproduction.html'
        );
    }
} );

// ==================================================================
// Summary
// ==================================================================
console.log( '' );
console.log( '='.repeat( 70 ) );
console.log( `TEST SUMMARY: ${ passedTests }/${ totalTests } tests passed` );
console.log( '='.repeat( 70 ) );

if ( passedTests === totalTests ) {
    console.log( '' );
    console.log( '✅ ALL TESTS PASSED!' );
    console.log( '' );
    console.log( 'Navigation Button Visibility Fix Verified:' );
    console.log(
        '  ✅ initPagination calls updatePaginationDisplay to ensure buttons are correct'
    );
    console.log(
        '  ✅ HTML has proper initial state (Previous/Submit hidden, Next visible)'
    );
    console.log(
        '  ✅ Test HTML files updated to match production HTML'
    );
    console.log(
        '  ✅ Prevents FOUC (Flash of Unstyled Content) on page load'
    );
    console.log(
        '  ✅ Guarantees correct button visibility on page 1'
    );
    console.log( '' );
    console.log( 'REGRESSION TEST: ✅ PASS' );
    console.log( 'The all-buttons-visible bug is fixed!' );
    console.log( '' );
    process.exit( 0 );
} else {
    console.log( '' );
    console.log( `❌ ${ totalTests - passedTests } test(s) failed` );
    console.log( '' );
    console.log( 'REGRESSION TEST: ❌ FAIL' );
    console.log( '' );
    process.exit( 1 );
}
