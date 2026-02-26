import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import Save from './save';
import metadata from './block.json';

import './editor.scss';
import './style.scss';

registerBlockType( metadata, {
	edit: Edit,
	save: Save,
	transforms: {
		from: [
			{
				type: 'block',
				blocks: [ 'eipsi/campo-multiple' ],
				transform: ( attributes ) => {
					return {
						fieldName: attributes.fieldName || '',
						label: attributes.label || '',
						required: attributes.required || false,
						placeholder: 'Seleccioná una opción',
						helperText: attributes.helperText || '',
						options: attributes.options || '',
						conditionalLogic: attributes.conditionalLogic || null,
						className: attributes.className || '',
					};
				},
			},
			{
				type: 'block',
				blocks: [ 'eipsi/campo-radio' ],
				transform: ( attributes ) => {
					return {
						fieldName: attributes.fieldName || '',
						label: attributes.label || '',
						required: attributes.required || false,
						placeholder: 'Seleccioná una opción',
						helperText: attributes.helperText || '',
						options: attributes.options || '',
						conditionalLogic: attributes.conditionalLogic || null,
						className: attributes.className || '',
					};
				},
			},
		],
		to: [
			{
				type: 'block',
				blocks: [ 'eipsi/campo-multiple' ],
				transform: ( attributes ) => {
					return {
						fieldKey: '',
						fieldName: attributes.fieldName || '',
						label: attributes.label || '',
						required: attributes.required || false,
						helperText: attributes.helperText || '',
						options: attributes.options || '',
						layout: 'vertical',
						conditionalLogic: attributes.conditionalLogic || null,
						className: attributes.className || '',
						minSelections: 1,
						maxSelections: 0,
					};
				},
			},
			{
				type: 'block',
				blocks: [ 'eipsi/campo-radio' ],
				transform: ( attributes ) => {
					return {
						fieldKey: '',
						fieldName: attributes.fieldName || '',
						label: attributes.label || '',
						required: attributes.required || false,
						helperText: attributes.helperText || '',
						options: attributes.options || '',
						layout: 'vertical',
						conditionalLogic: attributes.conditionalLogic || null,
						className: attributes.className || '',
					};
				},
			},
		],
	},
} );
