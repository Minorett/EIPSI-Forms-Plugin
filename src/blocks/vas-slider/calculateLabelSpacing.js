/**
 * VAS Slider – Label positioning (simple + brutal)
 *
 * Objetivo clínico:
 * - Nada de fórmulas “mágicas” ni mediciones.
 * - Distribución equidistante y estable.
 * - Si la persona necesita 2 líneas, lo decide con Shift+Enter (\n).
 *
 * WYSIWYG real:
 * - Esta lógica se comparte entre edit.js y save.js.
 */

export const VAS_LABEL_FIRST_LEFT_PERCENT = 3;
export const VAS_LABEL_LAST_LEFT_PERCENT = 90;
export const VAS_ALIGNMENT_INTERNAL_MAX = 80;

export function sanitizeAlignmentInternal( value, fallback = 40 ) {
	if ( typeof value !== 'number' || Number.isNaN( value ) ) {
		return fallback;
	}
	let nextValue = value;
	if ( nextValue > VAS_ALIGNMENT_INTERNAL_MAX ) {
		nextValue = ( nextValue / 100 ) * VAS_ALIGNMENT_INTERNAL_MAX;
	}
	return Math.min( Math.max( nextValue, 0 ), VAS_ALIGNMENT_INTERNAL_MAX );
}

export function alignmentInternalToDisplay( internal ) {
	const safeInternal = sanitizeAlignmentInternal( internal );
	return Math.round( ( safeInternal / VAS_ALIGNMENT_INTERNAL_MAX ) * 100 );
}

export function alignmentDisplayToInternal( display ) {
	if ( typeof display !== 'number' || Number.isNaN( display ) ) {
		return 40;
	}
	const normalizedDisplay = Math.min( Math.max( display, 0 ), 100 );
	return Math.round(
		( normalizedDisplay / 100 ) * VAS_ALIGNMENT_INTERNAL_MAX
	);
}

export function getAlignmentRatio( internal ) {
	const safeInternal = sanitizeAlignmentInternal( internal );
	return safeInternal / VAS_ALIGNMENT_INTERNAL_MAX;
}

/**
 * Calcula la posición (en %) para cada label.
 *
 * Regla simple:
 * - 1 label: centro (50%)
 * - 2+ labels: distribución lineal entre FIRST y LAST
 *
 * @param {number} index Índice del label
 * @param {number} total Cantidad total de labels
 * @return {number} Posición en %
 */
export function calculateLabelPositionPercent( index, total ) {
	if ( total <= 1 ) {
		return 50;
	}

	const safeTotal = Math.max( 2, total );
	const safeIndex = Math.min( Math.max( index, 0 ), safeTotal - 1 );
	const ratio = safeIndex / ( safeTotal - 1 );

	return (
		VAS_LABEL_FIRST_LEFT_PERCENT +
		ratio * ( VAS_LABEL_LAST_LEFT_PERCENT - VAS_LABEL_FIRST_LEFT_PERCENT )
	);
}

/**
 * Mapeo de posiciones clínicas validadas
 * Cada fila: [alignment0, alignment50, alignment100]
 */
const LABEL_POSITIONS = {
	3: {
		100: [ 5, 50, 87 ],
		50: [ 20, 50, 70 ],
		0: [ 25, 50, 75 ], // Estimado por simetría
	},
	4: {
		100: [ 5, 30, 70, 88 ],
		50: [ 15, 35, 65, 80 ],
		0: [ 25, 37.5, 62.5, 75 ], // Estimado
	},
	5: {
		100: [ 5, 25, 50, 75, 90 ],
		50: [ 15, 28, 50, 70, 80 ],
		0: [ 25, 37.5, 50, 62.5, 75 ], // Estimado
	},
};

/**
 * Calcula left% usando mapeo clínico + interpolación
 * @param {number} index            Índice del label
 * @param {number} totalLabels      Cantidad de labels
 * @param {number} alignmentDisplay Valor del slider 0-100
 * @return {number} left%
 */
export function calculateLabelLeftPercent(
	index,
	totalLabels,
	alignmentDisplay
) {
	// Casos especiales
	if ( totalLabels === 1 ) {
		return 50;
	}
	if ( totalLabels === 2 ) {
		// 2 labels: distribuir según alignment
		const ratio = alignmentDisplay / 100;
		const minMargin = 10 + ( 1 - ratio ) * 15; // 10% a 25%
		const leftPercents = [ minMargin, 100 - minMargin ];
		return leftPercents[ index ];
	}

	// Para 3-5 labels: usar mapeo + interpolación
	if ( totalLabels >= 3 && totalLabels <= 5 ) {
		const positions = LABEL_POSITIONS[ totalLabels ];

		// Encontrar los dos alignments más cercanos para interpolar
		let lower = 0,
			upper = 100;
		let lowerPositions =
			positions[ 0 ] || positions[ Object.keys( positions )[ 0 ] ];
		let upperPositions = positions[ 100 ];

		// Interpolación lineal entre alignment 0/50/100
		if ( alignmentDisplay <= 50 ) {
			lower = 0;
			upper = 50;
			lowerPositions =
				positions[ 0 ] ||
				calculatePositionsForAlignment( totalLabels, 0 );
			upperPositions = positions[ 50 ];
		} else {
			lower = 50;
			upper = 100;
			lowerPositions = positions[ 50 ];
			upperPositions = positions[ 100 ];
		}

		// Interpolar
		const ratio = ( alignmentDisplay - lower ) / ( upper - lower );
		const interpolated =
			lowerPositions[ index ] +
			( upperPositions[ index ] - lowerPositions[ index ] ) * ratio;

		return interpolated;
	}

	// Para 6+ labels: extrapolación simple (no validado clínicamente)
	const minMargin = 5 + ( 1 - alignmentDisplay / 100 ) * 20;
	const maxMargin = 100 - minMargin * 1.5;
	const normalizedIndex = index / ( totalLabels - 1 );
	return minMargin + normalizedIndex * ( maxMargin - minMargin );
}

/**
 * Fallback para generar posiciones si no están mapeadas
 * (Para alignment 0 o labels > 5)
 * @param {number} totalLabels Cantidad de labels
 * @param {number} alignment   Valor del alignment 0-100
 * @return {Array<number>} Array de posiciones en %
 */
function calculatePositionsForAlignment( totalLabels, alignment ) {
	const ratio = alignment / 100;
	const minMargin = 5 + ( 1 - ratio ) * 20;
	const maxMargin = 100 - minMargin * 1.5;

	const positions = [];
	for ( let i = 0; i < totalLabels; i++ ) {
		const normalizedIndex = i / ( totalLabels - 1 );
		positions.push(
			minMargin + normalizedIndex * ( maxMargin - minMargin )
		);
	}
	return positions;
}

/**
 * Calcula transform basado en posición del label
 * @param {number} index       Índice del label
 * @param {number} totalLabels Cantidad total de labels
 * @return {string} transform CSS
 */
export function calculateLabelTransform( index, totalLabels ) {
	const isFirst = index === 0;
	const isLast = totalLabels > 0 && index === totalLabels - 1;

	if ( isFirst ) {
		return 'translateX(-100%)';
	}
	if ( isLast ) {
		return 'translateX(50%)';
	}
	return 'translateX(-50%)';
}

/**
 * Calcula text-align basado en posición del label
 * @param {number} index       Índice del label
 * @param {number} totalLabels Cantidad total de labels
 * @return {string} text-align CSS
 */
export function calculateLabelTextAlign( index, totalLabels ) {
	const isFirst = index === 0;
	const isLast = totalLabels > 0 && index === totalLabels - 1;

	if ( isFirst ) {
		return 'left';
	}
	if ( isLast ) {
		return 'right';
	}
	return 'center';
}

/**
 * Calcula style completo para cada label (NUEVA FUNCIÓN PRINCIPAL)
 * @param {number} index            Índice del label
 * @param {number} totalLabels      Cantidad total de labels
 * @param {number} alignmentDisplay Valor del slider 0-100
 * @return {Object} style CSS object con left, transform, textAlign
 */
export function calculateLabelStyle( index, totalLabels, alignmentDisplay ) {
	const left = calculateLabelLeftPercent(
		index,
		totalLabels,
		alignmentDisplay
	);
	const transform = calculateLabelTransform( index, totalLabels );
	const textAlign = calculateLabelTextAlign( index, totalLabels );

	return {
		left: `${ Math.round( left ) }%`,
		transform,
		textAlign,
	};
}

/**
 * Calcula el style inline para cada label.
 *
 * Posicionamiento fijo:
 * - First: left ~3%, translateX(-100%), text-align left
 * - Last: left ~90%, translateX(50%), text-align right
 * - Intermedios: equidistantes, translateX(-50%), text-align center
 *
 * @param {Object} params
 * @param {number} params.index
 * @param {number} params.totalLabels
 * @return {Object} style inline
 */
export function calculateLabelPositionStyle( { index, totalLabels } ) {
	const safeTotal = Math.max( 1, totalLabels );
	const isFirst = index === 0;
	const isLast = safeTotal > 1 && index === safeTotal - 1;

	let transform = 'translateX(-50%)';
	let textAlign = 'center';

	if ( isFirst ) {
		transform = 'translateX(-100%)';
		textAlign = 'left';
	} else if ( isLast ) {
		transform = 'translateX(50%)';
		textAlign = 'right';
	}

	const positionPercent = calculateLabelPositionPercent( index, safeTotal );

	return {
		left: `${ positionPercent }%`,
		transform,
		textAlign,
	};
}
