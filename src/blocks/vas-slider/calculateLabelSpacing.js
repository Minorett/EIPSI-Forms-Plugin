/**
 * Calculate dynamic label spacing for VAS slider based on alignment value
 *
 * Algoritmo de distribución dinámica que proporciona valores coherentes para gap,
 * padding y distribución visual de labels según el porcentaje de alineación.
 */

/**
 * Calcula spacing dinámico de labels basado en alignment value
 *
 * @param {number} value      - 0-100 (alignment percent from editor)
 * @param {number} labelCount - Cantidad de labels
 * @return {Object} { gap, padding, distribution }
 */
export function calculateLabelSpacing( value, labelCount ) {
	// Casos especiales - 1 label
	if ( labelCount === 1 ) {
		return {
			gap: '0em',
			paddingLeft: '0px',
			paddingRight: '0px',
			distribution: 'centered',
		};
	}

	// Normalizar value a 0-1 (puede exceder 1 para valores > 100)
	const alignment = Math.max( 0, value / 100 );

	// Casos especiales - 2 labels
	if ( labelCount === 2 ) {
		// Para 2 labels, el gap es progresivo
		const gapValue = alignment * 2;
		return {
			gap: `${ gapValue.toFixed( 2 ) }em`,
			paddingLeft: '0px',
			paddingRight: '0px',
			distribution: alignment > 0.5 ? 'expanded' : 'normal',
		};
	}

	// Para 3+ labels
	// Cálculo: gap va de 0.2em (compacto) a 2em (expandido)
	const gapMin = 0.2;
	const gapMax = 2;
	const gapValue = gapMin + alignment * ( gapMax - gapMin );

	return {
		gap: `${ gapValue.toFixed( 2 ) }em`,
		paddingLeft: `${ alignment * 10 }px`,
		paddingRight: `${ alignment * 10 }px`,
		distribution: alignment > 0.8 ? 'expanded' : 'normal',
	};
}

/**
 * Test cases para validar la función calculateLabelSpacing
 *
 * Estos tests verifican que el algoritmo funciona correctamente
 * en todos los casos de uso clínico comunes.
 */
export function testCalculateLabelSpacing() {
	if ( typeof window === 'undefined' || ! window.console ) {
		return;
	}

	// eslint-disable-next-line no-console
	console.log( '=== Testing calculateLabelSpacing ===' );

	const tests = [
		{
			name: '3 labels, alignment 0 (compacto)',
			value: 0,
			labelCount: 3,
			expectedGap: '0.20em',
		},
		{
			name: '3 labels, alignment 50 (moderado)',
			value: 50,
			labelCount: 3,
			expectedGap: '1.10em',
		},
		{
			name: '3 labels, alignment 100 (bien marcado)',
			value: 100,
			labelCount: 3,
			expectedGap: '2.00em',
		},
		{
			name: '5 labels, alignment 0 (compacto)',
			value: 0,
			labelCount: 5,
			expectedGap: '0.20em',
		},
		{
			name: '5 labels, alignment 100 (bien marcado)',
			value: 100,
			labelCount: 5,
			expectedGap: '2.00em',
		},
		{
			name: '1 label (centrado)',
			value: 100,
			labelCount: 1,
			expectedDistribution: 'centered',
		},
		{
			name: '2 labels, alignment 100',
			value: 100,
			labelCount: 2,
			expectedDistribution: 'expanded',
		},
	];

	let passCount = 0;
	let failCount = 0;

	tests.forEach( ( test ) => {
		const result = calculateLabelSpacing( test.value, test.labelCount );

		let passed = true;

		if ( test.expectedGap && result.gap !== test.expectedGap ) {
			passed = false;
			// eslint-disable-next-line no-console
			console.warn(
				`❌ ${ test.name }: expected gap ${ test.expectedGap }, got ${ result.gap }`
			);
		}

		if (
			test.expectedDistribution &&
			result.distribution !== test.expectedDistribution
		) {
			passed = false;
			// eslint-disable-next-line no-console
			console.warn(
				`❌ ${ test.name }: expected distribution ${ test.expectedDistribution }, got ${ result.distribution }`
			);
		}

		if ( passed ) {
			// eslint-disable-next-line no-console
			console.log( `✅ ${ test.name }`, result );
			passCount++;
		} else {
			failCount++;
		}
	} );

	// eslint-disable-next-line no-console
	console.log(
		`\n=== Results: ${ passCount } passed, ${ failCount } failed ===\n`
	);

	return failCount === 0;
}
