import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import Save from './save';

import './editor.scss';
import './style.scss';

registerBlockType( 'vas-dinamico/form-page', {
	edit: Edit,
	save: Save,
} );
