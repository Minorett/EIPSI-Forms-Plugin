/**
 * Save para Bloque de Aleatorización
 *
 * En el frontend, renderiza el shortcode que será procesado por el backend.
 * El shortcode [eipsi_randomization] maneja toda la lógica de asignación.
 *
 * @since 1.3.0
 */

import { useBlockProps } from '@wordpress/block-editor';

export default function Save( { attributes } ) {
	const { randomizationId, enabled } = attributes;

	if ( ! enabled || ! randomizationId ) {
		return null;
	}

	const blockProps = useBlockProps.save( {
		className: 'eipsi-randomization-wrapper',
		'data-randomization-id': randomizationId,
	} );

	return (
		<div { ...blockProps }>
			{ `[eipsi_randomization id="${ randomizationId }"]` }
		</div>
	);
}
