/**
 * Parser simple para markdown básico en consent blocks
 * Soporta: *texto* (negrita), _texto_ (itálica), *_texto_* (negrita + itálica)
 */

/**
 * Parsea texto con markdown básico a HTML
 * @param {string} text - Texto con markdown a parsear
 * @return {string} Texto con HTML aplicado
 */
export const parseConsentMarkdown = ( text ) => {
	if ( ! text || typeof text !== 'string' ) {
		return '';
	}

	// Reemplazar negrita + itálica: *_texto_*
	let parsed = text.replace(
		/\*_([^_]+)_\*/g,
		'<strong><em>$1</em></strong>'
	);

	// Reemplazar negrita: *texto*
	parsed = parsed.replace( /\*([^*]+)\*/g, '<strong>$1</strong>' );

	// Reemplazar itálica: _texto_
	parsed = parsed.replace( /_([^_]+)_/g, '<em>$1</em>' );

	return parsed;
};

/**
 * Valida el markdown del consentimiento
 * @param {string} text - Texto a validar
 * @return {Object} Resultado de la validación
 */
export const validateConsentMarkdown = ( text ) => {
	if ( ! text || typeof text !== 'string' ) {
		return { valid: true, error: null };
	}

	// Validaciones básicas
	const lines = text.split( '\n' );
	const errors = [];

	for ( let i = 0; i < lines.length; i++ ) {
		const line = lines[ i ];
		const lineNumber = i + 1;

		// Verificar que los asteriscos estén balanceados
		const asterisks = ( line.match( /\*/g ) || [] ).length;
		if ( asterisks % 2 !== 0 ) {
			errors.push( `Línea ${ lineNumber }: Asteriscos no balanceados` );
		}

		// Verificar que los guiones bajos estén balanceados
		const underscores = ( line.match( /_/g ) || [] ).length;
		if ( underscores % 2 !== 0 ) {
			errors.push(
				`Línea ${ lineNumber }: Guiones bajos no balanceados`
			);
		}
	}

	return {
		valid: errors.length === 0,
		error:
			errors.length > 0
				? `Errores de formato: ${ errors.join( ', ' ) }`
				: null,
	};
};
