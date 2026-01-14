import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import Save from './save';
import metadata from './block.json';

import './editor.scss';
import './style.scss';

registerBlockType( metadata, {
	edit: Edit,
	save: Save,
} );
