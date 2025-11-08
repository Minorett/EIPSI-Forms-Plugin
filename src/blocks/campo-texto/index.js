import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import Save from './save';

import './editor.scss';
import './style.scss';

registerBlockType( 'vas-dinamico/campo-texto', {
	edit: Edit,
	save: Save,
} );
