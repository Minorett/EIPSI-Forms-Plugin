/**
 * EIPSI Randomization Block
 *
 * Bloque independiente para configurar aleatorización de formularios.
 * Genera automáticamente shortcode y link para uso público.
 *
 * @since 1.3.0
 */

import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import Save from './save';

import './editor.scss';
import './style.scss';

registerBlockType( 'eipsi/randomization', {
	edit: Edit,
	save: Save,
} );
