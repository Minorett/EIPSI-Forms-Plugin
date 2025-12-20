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
