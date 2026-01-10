/**
 * LikertPresets.js
 * Presets predefinidos para escalas Likert con diferentes números de puntos y tipos de medición
 */

/**
 * Definiciones de presets para escalas Likert
 * Incluye 5-point (más común), 7-point (mayor especificidad), 4-point (fuerza decisión) y 9-point (máxima especificidad)
 */
export const LIKERT_PRESETS = {
	// 5-point scales (most common - includes neutral point)
	'likert5-agreement': {
		name: 'Escala de Acuerdo (5 puntos)',
		minValue: 1,
		maxValue: 5,
		labels: 'Totalmente en desacuerdo; En desacuerdo; Neutral; De acuerdo; Totalmente de acuerdo',
		description: 'Evaluación de concordancia con afirmaciones',
		type: 'agreement',
		icon: '🤝',
		color: '#3b82f6', // blue
	},
	'likert5-satisfaction': {
		name: 'Escala de Satisfacción (5 puntos)',
		minValue: 1,
		maxValue: 5,
		labels: 'Muy insatisfecho; Insatisfecho; Neutral; Satisfecho; Muy satisfecho',
		description: 'Medición de satisfacción con servicios o productos',
		type: 'satisfaction',
		icon: '😊',
		color: '#10b981', // green
	},
	'likert5-frequency': {
		name: 'Escala de Frecuencia (5 puntos)',
		minValue: 1,
		maxValue: 5,
		labels: 'Nunca; Raramente; A veces; Frecuentemente; Siempre',
		description: 'Evaluación de frecuencia de comportamientos/síntomas',
		type: 'frequency',
		icon: '📊',
		color: '#f59e0b', // orange
	},

	// 7-point scale (greater specificity while maintaining neutral)
	'likert7-agreement': {
		name: 'Escala de Acuerdo (7 puntos)',
		minValue: 1,
		maxValue: 7,
		labels: 'Totalmente en desacuerdo; Muy en desacuerdo; En desacuerdo; Neutral; De acuerdo; Muy de acuerdo; Totalmente de acuerdo',
		description: 'Mayor especificidad en evaluación de acuerdos',
		type: 'agreement',
		icon: '🤝',
		color: '#3b82f6', // blue
	},
	'likert7-satisfaction': {
		name: 'Escala de Satisfacción (7 puntos)',
		minValue: 1,
		maxValue: 7,
		labels: 'Muy insatisfecho; Bastante insatisfecho; Algo insatisfecho; Neutral; Algo satisfecho; Bastante satisfecho; Muy satisfecho',
		description: 'Mayor granularidad en medición de satisfacción',
		type: 'satisfaction',
		icon: '😊',
		color: '#10b981', // green
	},

	// 4-point scale (forces decision - no neutral point)
	'likert4-agreement': {
		name: 'Escala de Acuerdo (4 puntos)',
		minValue: 1,
		maxValue: 4,
		labels: 'Muy en desacuerdo; En desacuerdo; De acuerdo; Muy de acuerdo',
		description: 'Fuerza decisión sin punto neutral',
		type: 'agreement',
		icon: '⚖️',
		color: '#3b82f6', // blue
	},

	// 9-point scale (maximum specificity)
	'likert9-scale': {
		name: 'Escala de 9 puntos',
		minValue: 1,
		maxValue: 9,
		labels: '1; 2; 3; 4 (Desacuerdo); 5 (Neutral); 6; 7; 8; 9 (Acuerdo)',
		description:
			'Máxima especificidad - típicamente usada en investigación avanzada',
		type: 'general',
		icon: '🎯',
		color: '#8b5cf6', // purple
	},

	// Custom option
	custom: {
		name: 'Personalizado',
		minValue: null,
		maxValue: null,
		labels: null,
		description: 'Define tu propia escala',
		type: 'custom',
		icon: '⚙️',
		color: '#6b7280', // gray
	},
};

/**
 * Obtiene un preset específico por su clave
 * @param {string} key - Clave del preset
 * @return {Object|null} - Objeto del preset o null si no existe
 */
export const getPresetByKey = ( key ) => {
	return LIKERT_PRESETS[ key ] || null;
};

/**
 * Aplica un preset a los atributos del bloque
 * @param {Object} preset - Objeto del preset
 * @return {Object} - Atributos actualizados
 */
export const applyPreset = ( preset ) => {
	if ( ! preset || preset.key === 'custom' ) {
		return {};
	}

	return {
		minValue: preset.minValue,
		maxValue: preset.maxValue,
		labels: preset.labels,
	};
};

/**
 * Valida si las etiquetas coinciden con el número de puntos de la escala
 * @param {string} labels   - Etiquetas separadas por punto y coma
 * @param {number} minValue - Valor mínimo de la escala
 * @param {number} maxValue - Valor máximo de la escala
 * @return {Object} - Resultado de validación
 */
export const validateLabels = ( labels, minValue, maxValue ) => {
	if ( ! labels || ! labels.trim() ) {
		return { isValid: true, message: '' };
	}

	const labelCount = labels
		.split( ';' )
		.filter( ( label ) => label.trim() ).length;
	const scaleCount = maxValue - minValue + 1;

	if ( labelCount !== scaleCount ) {
		return {
			isValid: false,
			message: `Tienes ${ labelCount } etiquetas pero ${ scaleCount } puntos en la escala. Las etiquetas serán ignoradas si el número no coincide.`,
		};
	}

	return { isValid: true, message: '' };
};

/**
 * Obtiene los presets agrupados por tipo
 * @return {Object} - Presets agrupados
 */
export const getGroupedPresets = () => {
	return {
		'5-puntos': {
			name: 'Escalas de 5 puntos (más comunes)',
			presets: [
				LIKERT_PRESETS[ 'likert5-agreement' ],
				LIKERT_PRESETS[ 'likert5-satisfaction' ],
				LIKERT_PRESETS[ 'likert5-frequency' ],
			],
		},
		'7-puntos': {
			name: 'Escalas de 7 puntos (mayor especificidad)',
			presets: [
				LIKERT_PRESETS[ 'likert7-agreement' ],
				LIKERT_PRESETS[ 'likert7-satisfaction' ],
			],
		},
		'4-puntos': {
			name: 'Escala de 4 puntos (fuerza decisión)',
			presets: [ LIKERT_PRESETS[ 'likert4-agreement' ] ],
		},
		'9-puntos': {
			name: 'Escala de 9 puntos (máxima especificidad)',
			presets: [ LIKERT_PRESETS[ 'likert9-scale' ] ],
		},
		custom: {
			name: 'Opción personalizada',
			presets: [ LIKERT_PRESETS.custom ],
		},
	};
};
