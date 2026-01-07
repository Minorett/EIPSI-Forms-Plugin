import { registerBlockType } from '@wordpress/blocks';
import './style.scss';
import './editor.scss';
import Edit from './edit';
import save from './save';

registerBlockType( 'eipsi/vas-slider', {
	edit: Edit,
	save,
} );
