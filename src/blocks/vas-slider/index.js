import { registerBlockType } from '@wordpress/blocks';
import './style.scss';
import './editor.scss';
import Edit from './edit';
import save from './save';

registerBlockType( 'vas-dinamico/vas-slider', {
	edit: Edit,
	save,
} );
