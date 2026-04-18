/**
 * Save para Bloque Pool de Estudios
 *
 * Filosofía: Bloque dinámico que guarda solo data attributes
 * El backend renderiza el shortcode basado en pool_id
 *
 * @since 2.5.3
 */

import { useBlockProps } from '@wordpress/block-editor';

export default function Save({ attributes }) {
    const { poolId, generatedShortcode } = attributes;

    // Si no hay shortcode generado, no guardar nada
    if (!generatedShortcode || !poolId) {
        return null;
    }

    const blockProps = useBlockProps.save({
        className: 'eipsi-pool-wrapper',
        'data-pool-id': poolId,
    });

    // El bloque dinámico renderizará el shortcode
    // Aquí solo guardamos el contenedor con data attributes
    return (
        <div {...blockProps} data-shortcode={generatedShortcode}>
            {generatedShortcode}
        </div>
    );
}
