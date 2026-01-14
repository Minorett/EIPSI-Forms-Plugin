/**
 * Save para Bloque de Aleatorización - KISS (Keep It Simple, Stupid)
 *
 * Filosofía: Bloque dinámico que guarda solo data attributes
 * El backend renderiza el shortcode basado en config_id
 *
 * @since 1.3.5
 */

import { useBlockProps } from '@wordpress/block-editor';

export default function Save( { attributes } ) {
	const { generatedShortcode } = attributes;

	// Si no hay shortcode generado, no guardar nada
	if ( ! generatedShortcode ) {
		return null;
	}

	// Extraer config_id del shortcode
	const configMatch = generatedShortcode.match( /config="([^"]+)"/ );
	const configId = configMatch ? configMatch[ 1 ] : '';

	const blockProps = useBlockProps.save( {
		className: 'eipsi-randomization-wrapper',
		'data-config-id': configId,
	} );

	// El bloque dinámico renderizará el shortcode
	// Aquí solo guardamos el contenedor con data attributes
	return (
		<div { ...blockProps } data-shortcode={ generatedShortcode }>
			{ generatedShortcode }
		</div>
	);
}
