import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import Save from './save';

import './editor.scss';
import './style.scss';

registerBlockType( 'eipsi/consent-block', {
	edit: Edit,
	save: Save,
	attributes: {
		titulo: {
			type: 'string',
			default: '',
		},
		contenido: {
			type: 'string',
			default:
				'Acepto participar voluntariamente en este estudio de investigación. He sido informado sobre los objetivos, procedimientos, riesgos y beneficios. Entiendo que mi participación es completamente voluntaria y puedo retirarme en cualquier momento sin penalización. Mis datos serán tratados de forma anónima y confidencial según las normativas ANMAT y APA.',
		},
		textoComplementario: {
			type: 'string',
			default: '',
		},
		mostrarCheckbox: {
			type: 'boolean',
			default: true,
		},
		etiquetaCheckbox: {
			type: 'string',
			default: 'He leído y acepto participar voluntariamente',
		},
		isRequired: {
			type: 'boolean',
			default: true,
		},
		showTimestamp: {
			type: 'boolean',
			default: false,
		},
	},
} );
