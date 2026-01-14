/**
 * EIPSI Randomization Block - KISS (Keep It Simple, Stupid)
 *
 * Filosofía: Backend hace TODO el trabajo, el bloque es minimalista
 * - Atributos simples: shortcodesInput, savedConfig, generatedShortcode
 * - Bloque dinámico: render_callback procesa el shortcode
 * - Sin estados complejos, sin validación en frontend
 *
 * @since 1.3.5
 */

import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import metadata from './block.json';

registerBlockType( metadata, {
	edit: Edit,
	save: () => null, // Bloque dinámico - save retorna null
} );
