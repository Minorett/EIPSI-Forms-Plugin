import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import Save from './save';

import './editor.scss';
import './style.scss';

registerBlockType( 'eipsi/form-container', {
	edit: Edit,
	save: Save,
} );
