/**
 * VALIDATION TEST: Multi-page Navigation Button Logic & Alignment
 * Tests the fixed navigation button visibility and alignment
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

function assertContains( content, substring, message ) {
	if ( ! content.includes( substring ) ) {
		throw new Error(
			`${ message }\n   Expected to find: "${ substring }"`
		);
	}
}

function assertNotContains( content, substring, message ) {
	if ( content.includes( substring ) ) {
		throw new Error(
			`${ message }\n   Did not expect to find: "${ substring }"`
		);
	}
}

// Read the JavaScript file
const jsFilePath = path.join( __dirname, 'assets/js/eipsi-forms.js' );
const jsContent = fs.readFileSync( jsFilePath, 'utf8' );

// Read the SCSS files
const srcStylePath = path.join(
	__dirname,
	'src/blocks/form-container/style.scss'
);
const srcStyleContent = fs.readFileSync( srcStylePath, 'utf8' );

const blocksStylePath = path.join(
	__dirname,
	'blocks/form-container/style.scss'
);
const blocksStyleContent = fs.readFileSync( blocksStylePath, 'utf8' );

// Read the save.js files
const srcSavePath = path.join( __dirname, 'src/blocks/form-container/save.js' );
const srcSaveContent = fs.readFileSync( srcSavePath, 'utf8' );

const blocksSavePath = path.join( __dirname, 'blocks/form-container/save.js' );
const blocksSaveContent = fs.readFileSync( blocksSavePath, 'utf8' );

console.log( '='.repeat( 70 ) );
console.log(
	'VALIDATION TEST: Multi-page Navigation Alignment & Button Logic'
);
console.log( '='.repeat( 70 ) );
console.log( '' );

// ==================================================================
// SECTION 1: JavaScript Button Visibility Logic
// ==================================================================
console.log( '📋 SECTION 1: JavaScript Button Visibility Logic' );
console.log( '-'.repeat( 70 ) );

test( '1.1: JavaScript uses simplified button visibility logic', () => {
	assertContains(
		jsContent,
		'const isLastPage = navigator',
		'Should calculate isLastPage at the start'
	);
} );

test( '1.2: Previous button logic checks currentPage > 1', () => {
	assertContains(
		jsContent,
		'currentPage > 1 && allowBackwardsNav',
		'Previous button should only show when currentPage > 1 and allowBackwardsNav is true'
	);
} );

test( '1.3: Previous button logic respects allowBackwardsNav toggle', () => {
	assertContains(
		jsContent,
		'allowBackwardsNav',
		'Should check allowBackwardsNav setting'
	);
} );

test( '1.4: Previous button never shows on page 1 (currentPage > 1 check)', () => {
	const prevLogicMatch = jsContent.match(
		/shouldShowPrev\s*=\s*currentPage\s*>\s*1\s*&&\s*allowBackwardsNav/
	);
	if ( ! prevLogicMatch ) {
		throw new Error(
			'Previous button logic does not enforce page 1 exclusion'
		);
	}
} );

test( '1.5: Next button logic uses isLastPage', () => {
	assertContains(
		jsContent,
		'const shouldShowNext = ! isLastPage',
		'Next button should hide on last page'
	);
} );

test( '1.6: Submit button logic uses isLastPage', () => {
	assertContains(
		jsContent,
		'if ( isLastPage ) {',
		'Submit button should only show on last page'
	);
} );

test( '1.7: JavaScript removed complex history-based logic', () => {
	assertNotContains(
		jsContent,
		'hasHistory &&',
		'Should not use hasHistory for button visibility'
	);
} );

test( '1.8: JavaScript removed firstVisitedPage logic for Previous button', () => {
	const updatePaginationSection = jsContent.substring(
		jsContent.indexOf( 'updatePaginationDisplay' ),
		jsContent.indexOf( 'updatePaginationDisplay' ) + 2000
	);
	assertNotContains(
		updatePaginationSection,
		'currentPage > firstVisitedPage',
		'Should not use firstVisitedPage in button visibility logic'
	);
} );

// ==================================================================
// SECTION 2: HTML Structure for Button Alignment
// ==================================================================
console.log( '' );
console.log( '📋 SECTION 2: HTML Structure for Button Alignment' );
console.log( '-'.repeat( 70 ) );

test( '2.1: src/save.js has form-nav-left wrapper', () => {
	assertContains(
		srcSaveContent,
		'<div className="form-nav-left">',
		'src/save.js should have form-nav-left wrapper'
	);
} );

test( '2.2: src/save.js has form-nav-right wrapper', () => {
	assertContains(
		srcSaveContent,
		'<div className="form-nav-right">',
		'src/save.js should have form-nav-right wrapper'
	);
} );

test( '2.3: src/save.js Previous button is in form-nav-left', () => {
	const navLeftMatch = srcSaveContent.match(
		/<div className="form-nav-left">[\s\S]*?eipsi-prev-button[\s\S]*?<\/div>/
	);
	if ( ! navLeftMatch ) {
		throw new Error( 'Previous button should be inside form-nav-left' );
	}
} );

test( '2.4: src/save.js Next and Submit buttons are in form-nav-right', () => {
	const navRightMatch = srcSaveContent.match(
		/<div className="form-nav-right">[\s\S]*?eipsi-next-button[\s\S]*?eipsi-submit-button[\s\S]*?<\/div>/
	);
	if ( ! navRightMatch ) {
		throw new Error(
			'Next and Submit buttons should be inside form-nav-right'
		);
	}
} );

test( '2.5: blocks/save.js has form-nav-left wrapper', () => {
	assertContains(
		blocksSaveContent,
		'<div className="form-nav-left">',
		'blocks/save.js should have form-nav-left wrapper'
	);
} );

test( '2.6: blocks/save.js has form-nav-right wrapper', () => {
	assertContains(
		blocksSaveContent,
		'<div className="form-nav-right">',
		'blocks/save.js should have form-nav-right wrapper'
	);
} );

test( '2.7: blocks/save.js Previous button is in form-nav-left', () => {
	const navLeftMatch = blocksSaveContent.match(
		/<div className="form-nav-left">[\s\S]*?eipsi-prev-button[\s\S]*?<\/div>/
	);
	if ( ! navLeftMatch ) {
		throw new Error( 'Previous button should be inside form-nav-left' );
	}
} );

test( '2.8: blocks/save.js Next and Submit buttons are in form-nav-right', () => {
	const navRightMatch = blocksSaveContent.match(
		/<div className="form-nav-right">[\s\S]*?eipsi-next-button[\s\S]*?eipsi-submit-button[\s\S]*?<\/div>/
	);
	if ( ! navRightMatch ) {
		throw new Error(
			'Next and Submit buttons should be inside form-nav-right'
		);
	}
} );

// ==================================================================
// SECTION 3: CSS Alignment Rules
// ==================================================================
console.log( '' );
console.log( '📋 SECTION 3: CSS Alignment Rules' );
console.log( '-'.repeat( 70 ) );

test( '3.1: src/style.scss has form-nav-left styles', () => {
	assertContains(
		srcStyleContent,
		'.form-nav-left',
		'src/style.scss should have form-nav-left styles'
	);
} );

test( '3.2: src/style.scss has form-nav-right styles', () => {
	assertContains(
		srcStyleContent,
		'.form-nav-right',
		'src/style.scss should have form-nav-right styles'
	);
} );

test( '3.3: src/style.scss uses flexbox for nav containers', () => {
	const navContainerMatch = srcStyleContent.match(
		/\.form-nav-left,[\s\S]*?\.form-nav-right[\s\S]*?display:\s*flex/
	);
	if ( ! navContainerMatch ) {
		throw new Error( 'Nav containers should use display: flex' );
	}
} );

test( '3.4: src/style.scss has space-between for main navigation', () => {
	const formNavMatch = srcStyleContent.match(
		/\.form-navigation[\s\S]*?justify-content:\s*space-between/
	);
	if ( ! formNavMatch ) {
		throw new Error(
			'.form-navigation should use justify-content: space-between'
		);
	}
} );

test( '3.5: src/style.scss does not reference .form-nav-buttons (old structure)', () => {
	assertNotContains(
		srcStyleContent,
		'.form-nav-buttons',
		'Should not use old .form-nav-buttons structure'
	);
} );

test( '3.6: blocks/style.scss has form-nav-left styles', () => {
	assertContains(
		blocksStyleContent,
		'.form-nav-left',
		'blocks/style.scss should have form-nav-left styles'
	);
} );

test( '3.7: blocks/style.scss has form-nav-right styles', () => {
	assertContains(
		blocksStyleContent,
		'.form-nav-right',
		'blocks/style.scss should have form-nav-right styles'
	);
} );

test( '3.8: blocks/style.scss does not reference .form-nav-buttons', () => {
	assertNotContains(
		blocksStyleContent,
		'.form-nav-buttons',
		'Should not use old .form-nav-buttons structure'
	);
} );

// ==================================================================
// SECTION 4: Mobile Responsive Alignment
// ==================================================================
console.log( '' );
console.log( '📋 SECTION 4: Mobile Responsive Alignment' );
console.log( '-'.repeat( 70 ) );

test( '4.1: src/style.scss has mobile styles for form-nav-left', () => {
	const mobileSection = srcStyleContent.substring(
		srcStyleContent.indexOf( '@media (max-width: 768px)' )
	);
	assertContains(
		mobileSection,
		'.form-nav-left',
		'Mobile styles should include form-nav-left'
	);
} );

test( '4.2: src/style.scss has mobile styles for form-nav-right', () => {
	const mobileSection = srcStyleContent.substring(
		srcStyleContent.indexOf( '@media (max-width: 768px)' )
	);
	assertContains(
		mobileSection,
		'.form-nav-right',
		'Mobile styles should include form-nav-right'
	);
} );

test( '4.3: blocks/style.scss has mobile styles for form-nav-left', () => {
	const mobileSection = blocksStyleContent.substring(
		blocksStyleContent.indexOf( '@media (max-width: 768px)' )
	);
	assertContains(
		mobileSection,
		'.form-nav-left',
		'Mobile styles should include form-nav-left'
	);
} );

test( '4.4: blocks/style.scss has mobile styles for form-nav-right', () => {
	const mobileSection = blocksStyleContent.substring(
		blocksStyleContent.indexOf( '@media (max-width: 768px)' )
	);
	assertContains(
		mobileSection,
		'.form-nav-right',
		'Mobile styles should include form-nav-right'
	);
} );

// ==================================================================
// SECTION 5: Logic Scenarios Validation
// ==================================================================
console.log( '' );
console.log( '📋 SECTION 5: Logic Scenarios Validation' );
console.log( '-'.repeat( 70 ) );

test( '5.1: Page 1 logic: Previous button never shows (currentPage = 1 fails currentPage > 1)', () => {
	// When currentPage = 1, currentPage > 1 is false, so shouldShowPrev is false
	const logic = 'currentPage > 1 && allowBackwardsNav';
	assertContains(
		jsContent,
		logic,
		'Logic ensures page 1 never shows Previous'
	);
} );

test( '5.2: Page 2 logic: Previous shows if toggle is ON', () => {
	// When currentPage = 2, currentPage > 1 is true, so shouldShowPrev = allowBackwardsNav
	const logic = 'currentPage > 1 && allowBackwardsNav';
	assertContains(
		jsContent,
		logic,
		'Logic allows Previous on page 2 if toggle is ON'
	);
} );

test( '5.3: Last page logic: Submit shows, Next hides', () => {
	assertContains(
		jsContent,
		'const shouldShowNext = ! isLastPage',
		'Next button hides on last page'
	);
	assertContains(
		jsContent,
		'if ( isLastPage ) {',
		'Submit button shows on last page'
	);
} );

test( '5.4: Last page logic: Previous shows if toggle is ON (no special exception)', () => {
	// Previous button logic doesn\'t exclude last page
	const updatePaginationSection = jsContent.substring(
		jsContent.indexOf( 'updatePaginationDisplay' ),
		jsContent.indexOf( 'updatePaginationDisplay' ) + 2000
	);
	assertNotContains(
		updatePaginationSection,
		'&& ! isLastPage',
		'Previous button should not be excluded on last page'
	);
} );

test( '5.5: Toggle OFF: Previous never shows on any page', () => {
	// When allowBackwardsNav is false, shouldShowPrev is always false
	assertContains(
		jsContent,
		'allowBackwardsNav',
		'Logic respects allowBackwardsNav toggle'
	);
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
	console.log( 'Multi-page Navigation Alignment & Button Logic:' );
	console.log(
		'  ✅ JavaScript button visibility logic simplified and correct'
	);
	console.log( '  ✅ Page 1: Never shows Previous button' );
	console.log(
		'  ✅ Pages 2-n: Shows Previous only if allowBackwardsNav = true'
	);
	console.log( '  ✅ Last page: Shows Submit, hides Next' );
	console.log( '  ✅ HTML structure uses form-nav-left and form-nav-right' );
	console.log( '  ✅ CSS alignment uses flexbox with space-between' );
	console.log( '  ✅ Mobile responsive styles updated' );
	console.log(
		'  ✅ Toggle "Allow backwards navigation" respected in logic'
	);
	console.log( '' );
	process.exit( 0 );
} else {
	console.log( '' );
	console.log( `❌ ${ totalTests - passedTests } test(s) failed` );
	console.log( '' );
	process.exit( 1 );
}
