/**
 * Phase 18 Validation: Remove semantic redundancy from inline success message
 * Ensures the inline success message is technical and doesn't duplicate "Gracias"
 */

const fs = require( 'fs' );
const path = require( 'path' );

let passed = 0;
let failed = 0;

/**
 * Test helper
 * @param description
 * @param fn
 */
function test( description, fn ) {
	try {
		fn();
		passed++;
		console.log( `✅ ${ description }` );
	} catch ( error ) {
		failed++;
		console.error( `❌ ${ description }` );
		console.error( `   ${ error.message }` );
	}
}

/**
 * Assert helper
 * @param condition
 * @param message
 */
function assert( condition, message ) {
	if ( ! condition ) {
		throw new Error( message );
	}
}

console.log( '🧪 Phase 18: Inline Success Message Validation\n' );

const formsJsPath = path.join( __dirname, 'assets/js/eipsi-forms.js' );
const formsJsContent = fs.readFileSync( formsJsPath, 'utf-8' );

// Test 1: Verify success message text is updated
test( 'Success message uses new text "✓ Respuesta guardada correctamente"', () => {
	assert(
		formsJsContent.includes( "'✓ Respuesta guardada correctamente'" ),
		'Should contain new success message text'
	);
} );

// Test 2: Verify old message text is removed
test( 'Old message "¡Formulario enviado correctamente! Redirigiendo..." is removed', () => {
	assert(
		! formsJsContent.includes(
			'¡Formulario enviado correctamente! Redirigiendo...'
		),
		'Should NOT contain old message text'
	);
} );

// Test 3: Verify subtitle is updated
test( 'Subtitle changed to "Redirigiendo a la página de confirmación..."', () => {
	assert(
		formsJsContent.includes(
			'Redirigiendo a la página de confirmación...'
		),
		'Should contain new subtitle text'
	);
} );

// Test 4: Verify "Gracias por completar el formulario" is removed from inline message
test( '"Gracias por completar el formulario" is removed from inline message', () => {
	// Find the success message block
	const successBlockMatch = formsJsContent.match(
		/if \( type === 'success' \) \{[\s\S]*?messageElement\.innerHTML = `[\s\S]*?`;/
	);
	assert( successBlockMatch, 'Should find success message block' );

	const successBlock = successBlockMatch[ 0 ];
	assert(
		! successBlock.includes( 'Gracias por completar el formulario' ),
		'Success message block should NOT contain "Gracias por completar el formulario"'
	);
} );

// Test 5: Verify "Su respuesta ha sido registrada exitosamente" is removed
test( '"Su respuesta ha sido registrada exitosamente" is removed from inline message', () => {
	// Find the success message block
	const successBlockMatch = formsJsContent.match(
		/if \( type === 'success' \) \{[\s\S]*?messageElement\.innerHTML = `[\s\S]*?`;/
	);
	assert( successBlockMatch, 'Should find success message block' );

	const successBlock = successBlockMatch[ 0 ];
	assert(
		! successBlock.includes(
			'Su respuesta ha sido registrada exitosamente'
		),
		'Success message block should NOT contain "Su respuesta ha sido registrada exitosamente"'
	);
} );

// Test 6: Verify confetti is still present
test( 'Confetti animation is still present', () => {
	assert(
		formsJsContent.includes( 'form-message__confetti' ),
		'Should still contain confetti container'
	);
	assert(
		formsJsContent.includes( 'this.createConfetti( messageElement )' ),
		'Should still call createConfetti function'
	);
} );

// Test 7: Verify SVG icon is still present
test( 'Success icon SVG is still present', () => {
	const successBlockMatch = formsJsContent.match(
		/if \( type === 'success' \) \{[\s\S]*?messageElement\.innerHTML = `[\s\S]*?`;/
	);
	assert( successBlockMatch, 'Should find success message block' );

	const successBlock = successBlockMatch[ 0 ];
	assert(
		successBlock.includes( 'form-message__icon' ),
		'Should contain icon container'
	);
	assert( successBlock.includes( '<svg' ), 'Should contain SVG element' );
} );

// Test 8: Verify message structure is correct
test( 'Success message has correct structure (icon + content + confetti)', () => {
	const successBlockMatch = formsJsContent.match(
		/if \( type === 'success' \) \{[\s\S]*?messageElement\.innerHTML = `[\s\S]*?`;/
	);
	assert( successBlockMatch, 'Should find success message block' );

	const successBlock = successBlockMatch[ 0 ];
	assert(
		successBlock.includes( 'form-message__icon' ),
		'Should have icon container'
	);
	assert(
		successBlock.includes( 'form-message__content' ),
		'Should have content container'
	);
	assert(
		successBlock.includes( 'form-message__confetti' ),
		'Should have confetti container'
	);
} );

// Test 9: Verify title uses the message variable
test( 'Title uses the ${message} variable', () => {
	const successBlockMatch = formsJsContent.match(
		/if \( type === 'success' \) \{[\s\S]*?messageElement\.innerHTML = `[\s\S]*?`;/
	);
	assert( successBlockMatch, 'Should find success message block' );

	const successBlock = successBlockMatch[ 0 ];
	assert(
		successBlock.includes( 'form-message__title' ),
		'Should have title element'
	);
	assert(
		successBlock.includes( '${ message }' ),
		'Title should use message variable'
	);
} );

// Test 10: Verify subtitle is hardcoded (not using variable)
test( 'Subtitle is hardcoded (not dynamic)', () => {
	const successBlockMatch = formsJsContent.match(
		/if \( type === 'success' \) \{[\s\S]*?messageElement\.innerHTML = `[\s\S]*?`;/
	);
	assert( successBlockMatch, 'Should find success message block' );

	const successBlock = successBlockMatch[ 0 ];
	assert(
		successBlock.includes( 'form-message__subtitle' ),
		'Should have subtitle element'
	);
	// Verify it contains the exact text, not a variable
	assert(
		/form-message__subtitle[^>]*>Redirigiendo a la página de confirmación\.\.\.</i.test(
			successBlock
		),
		'Subtitle should contain the exact hardcoded text'
	);
} );

// Test 11: Verify note element is completely removed
test( 'form-message__note element is completely removed', () => {
	const successBlockMatch = formsJsContent.match(
		/if \( type === 'success' \) \{[\s\S]*?messageElement\.innerHTML = `[\s\S]*?`;/
	);
	assert( successBlockMatch, 'Should find success message block' );

	const successBlock = successBlockMatch[ 0 ];
	assert(
		! successBlock.includes( 'form-message__note' ),
		'Should NOT contain form-message__note class'
	);
} );

// Test 12: Verify redirect timeout is still 1500ms
test( 'Redirect timeout remains at 1500ms', () => {
	// Find the setTimeout block after showMessage
	assert(
		formsJsContent.includes( 'setTimeout( () => {' ),
		'Should have setTimeout'
	);
	assert(
		formsJsContent.includes( '}, 1500 )' ),
		'Should have 1500ms timeout for redirect'
	);
} );

// Summary
console.log( '\n📊 Test Summary:' );
console.log( `✅ Passed: ${ passed }` );
console.log( `❌ Failed: ${ failed }` );
console.log( `📈 Total: ${ passed + failed }` );

if ( failed > 0 ) {
	console.log( '\n❌ Some tests failed. Please review the changes.' );
	process.exit( 1 );
} else {
	console.log( '\n✅ All tests passed! Phase 18 implementation is correct.' );
	process.exit( 0 );
}
