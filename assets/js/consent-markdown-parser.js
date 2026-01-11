/**
 * EIPSI Forms - Consent Markdown Parser
 * Version: 1.2.3
 *
 * Parsea markdown-lite del bloque de consentimiento informado
 * Sintaxis soportada:
 * - *texto* para <strong>negrita</strong>
 * - _texto_ para <em>itálica</em>
 * - *_texto_* para <strong><em>negrita + itálica</em></strong>
 *
 * Diseñado para UX de investigadores clínicos: simple, intuitivo, sin brackets.
 */

/**
 * Escapa caracteres HTML para prevenir XSS
 *
 * @param {string} text - Texto a escapar
 * @return {string} - Texto con caracteres HTML escapados
 */
function escapeHtml( text ) {
	if ( ! text || typeof text !== 'string' ) {
		return text;
	}

	const map = {
		'&': '&amp;',
		'<': '&lt;',
		'>': '&gt;',
		'"': '&quot;',
		"'": '&#039;',
	};

	return text.replace( /[&<>"']/g, ( char ) => map[ char ] );
}

/**
 * Parsea markdown-lite del bloque de consentimiento
 * Soporta: *texto* para bold, _texto_ para itálica
 *
 * @param {string} text - Texto con markdown
 * @return {string} - HTML renderizado
 */
function parseConsentMarkdown( text ) {
	if ( ! text || typeof text !== 'string' ) {
		return text || '';
	}

	// Escapar caracteres HTML peligrosos PRIMERO (seguridad)
	text = escapeHtml( text );

	// 1. BOLD + ITÁLICA: *_texto_*
	text = text.replace(
		/\*_([^*_]+?)_\*/g,
		'<strong><em>$1</em></strong>'
	);

	// 2. ITÁLICA + BOLD: _*texto*_
	text = text.replace(
		/_\*([^*_]+?)\*_/g,
		'<em><strong>$1</strong></em>'
	);

	// 3. SOLO BOLD: *texto* (evitar ** doble asterisco)
	text = text.replace( /(?<!\*)\*([^*]+?)\*(?!\*)/g, '<strong>$1</strong>' );

	// 4. SOLO ITÁLICA: _texto_ (evitar __ doble guion bajo)
	text = text.replace( /(?<!_)_([^_]+?)_(?!_)/g, '<em>$1</em>' );

	return text;
}

/**
 * Valida que no haya asteriscos o guiones solos (sin cerrar)
 *
 * @param {string} text - Texto a validar
 * @return {Object} - { valid: boolean, error: string|null }
 */
function validateConsentMarkdown( text ) {
	if ( ! text || typeof text !== 'string' ) {
		return { valid: true };
	}

	// Contar asteriscos y guiones (deben ser pares)
	const asterisks = ( text.match( /\*/g ) || [] ).length;
	const underscores = ( text.match( /_/g ) || [] ).length;

	const issues = [];

	if ( asterisks % 2 !== 0 ) {
		issues.push( `Asteriscos desparejados: ${ asterisks } total` );
	}

	if ( underscores % 2 !== 0 ) {
		issues.push( `Guiones bajos desparejados: ${ underscores } total` );
	}

	if ( issues.length > 0 ) {
		return {
			valid: false,
			error: issues.join( ', ' ),
		};
	}

	return { valid: true };
}

// Exportar para uso en módulos ES6
export { parseConsentMarkdown, escapeHtml, validateConsentMarkdown };

// Exportar para CommonJS (si es necesario)
if ( typeof module !== 'undefined' && module.exports ) {
	module.exports = {
		parseConsentMarkdown,
		escapeHtml,
		validateConsentMarkdown,
	};
}
