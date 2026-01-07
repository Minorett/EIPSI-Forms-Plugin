import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import Save from './save';

import './editor.scss';
import './style.scss';

registerBlockType( 'eipsi/campo-textarea', {
	edit: Edit,
	save: Save,
} );
