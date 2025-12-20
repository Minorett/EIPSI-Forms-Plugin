/**
 * VAS Slider – Label positioning helpers
 *
 * Objetivo clínico:
 * - Labels SIEMPRE equidistantes (0–100%)
 * - Control "Label Alignment" comprime/expande TODA la distribución
 * - Misma lógica compartida entre edit.js y save.js (WYSIWYG real)
 */

export const VAS_ALIGNMENT_INTERNAL_MAX = 80;

/**
 * Normaliza el valor almacenado en el atributo a un rango interno 0–80.
 *
 * Backward compatibility:
 * - Versiones previas podían guardar 0–100 (display) → lo convertimos a 0–80.
 *
 * @param {number} value    Valor crudo desde atributos
 * @param {number} fallback Valor por defecto (interno)
 * @return {number} Valor interno 0–80
 */
export function sanitizeAlignmentInternal( value, fallback = 40 ) {
	if ( typeof value !== 'number' || Number.isNaN( value ) ) {
		return fallback;
	}

	let nextValue = value;

	// Si viene de formatos antiguos (0–100), lo convertimos al rango interno 0–80.
	if ( nextValue > VAS_ALIGNMENT_INTERNAL_MAX ) {
		nextValue = ( nextValue / 100 ) * VAS_ALIGNMENT_INTERNAL_MAX;
	}

	return Math.min( Math.max( nextValue, 0 ), VAS_ALIGNMENT_INTERNAL_MAX );
}

/**
 * Convierte el valor interno 0–80 a display 0–100 (lo que ve la persona en el editor).
 *
 * @param {number} internal Valor interno 0–80
 * @return {number} Valor display 0–100
 */
export function alignmentInternalToDisplay( internal ) {
	const safeInternal = sanitizeAlignmentInternal( internal );
	return Math.round( ( safeInternal / VAS_ALIGNMENT_INTERNAL_MAX ) * 100 );
}

/**
 * Convierte el valor display 0–100 a interno 0–80.
 *
 * @param {number} display Valor display 0–100
 * @return {number} Valor interno 0–80
 */
export function alignmentDisplayToInternal( display ) {
	if ( typeof display !== 'number' || Number.isNaN( display ) ) {
		return 40;
	}
	const normalizedDisplay = Math.min( Math.max( display, 0 ), 100 );
	return Math.round(
		( normalizedDisplay / 100 ) * VAS_ALIGNMENT_INTERNAL_MAX
	);
}

/**
 * Obtiene ratio 0–1 a partir del valor interno.
 *
 * @param {number} internal Valor interno 0–80
 * @return {number} ratio 0–1
 */
export function getAlignmentRatio( internal ) {
	const safeInternal = sanitizeAlignmentInternal( internal );
	return safeInternal / VAS_ALIGNMENT_INTERNAL_MAX;
}

/**
 * Calcula la posición porcentual (0–100) para cada label dado un alignment ratio.
 *
 * Distribución base: i/(N-1) * 100
 * Compresión: escala hacia el centro (50%) según ratio.
 *
 * @param {number} index Índice del label
 * @param {number} total Cantidad total de labels
 * @param {number} ratio 0–1 (0 = súper compacto, 1 = full spread)
 * @return {number} Posición en % (0–100)
 */
export function calculateLabelPositionPercent( index, total, ratio ) {
	if ( total <= 1 ) {
		return 50;
	}

	const safeRatio = Math.min( Math.max( ratio, 0 ), 1 );
	const base = ( index / ( total - 1 ) ) * 100;

	// Escala desde el centro: 0 → todo al 50%, 1 → distribución original
	return 50 + ( base - 50 ) * safeRatio;
}

/**
 * Calcula el style inline para cada label.
 *
 * Importante:
 * - Incluye transform inline para evitar que :hover del CSS rompa el posicionamiento.
 * - First/Last se anclan fuera del track para evitar solapamiento con labels internos.
 *
 * @param {Object} params
 * @param {number} params.index
 * @param {number} params.totalLabels
 * @param {number} params.alignmentInternal 0–80
 * @return {Object} style inline
 */
export function calculateLabelPositionStyle( {
	index,
	totalLabels,
	alignmentInternal,
} ) {
	const ratio = getAlignmentRatio( alignmentInternal );
	const positionPercent = calculateLabelPositionPercent(
		index,
		totalLabels,
		ratio
	);

	const isFirst = index === 0;
	const isLast = totalLabels > 0 && index === totalLabels - 1;

	let transform = 'translateX(-50%)';
	let textAlign = 'center';

	// Refinamiento UX (2025): espejo en los extremos
	// - First label: text-align left
	// - Last label: text-align right
	if ( isFirst ) {
		transform = 'translateX(-100%)';
		textAlign = 'left';
	} else if ( isLast ) {
		transform = 'translateX(50%)';
		textAlign = 'right';
	}

	return {
		left: `${ positionPercent }%`,
		transform,
		textAlign,
	};
}

/**
 * Legacy: calculateLabelSpacing
 *
 * Se mantiene exportado para backward compatibility interna.
 * Actualmente NO se usa en el VAS (usamos posicionamiento absoluto),
 * pero lo dejamos para evitar roturas si alguien lo importó en forks.
 *
 * @param {number} value      Valor 0–100 (display histórico)
 * @param {number} labelCount Cantidad de labels
 * @return {Object}           { gap, paddingLeft, paddingRight, distribution }
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
