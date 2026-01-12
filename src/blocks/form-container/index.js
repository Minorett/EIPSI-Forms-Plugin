import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import Save from './save';
import { DEFAULT_STYLE_CONFIG } from '../../utils/styleTokens';

import './editor.scss';
import './style.scss';

registerBlockType( 'eipsi/form-container', {
	attributes: {
		formId: {
			type: 'string',
			default: '',
		},
		submitButtonLabel: {
			type: 'string',
			default: 'Enviar',
		},
		description: {
			type: 'string',
			default: '',
		},
		styleConfig: {
			type: 'object',
			default: JSON.parse( JSON.stringify( DEFAULT_STYLE_CONFIG ) ),
		},
		presetName: {
			type: 'string',
			default: 'Clinical Blue',
		},
		allowBackwardsNav: {
			type: 'boolean',
			default: true,
		},
		showProgressBar: {
			type: 'boolean',
			default: true,
		},
		studyStatus: {
			type: 'string',
			default: 'open',
		},
		useCustomCompletion: {
			type: 'boolean',
			default: false,
		},
		completionTitle: {
			type: 'string',
			default: '',
		},
		completionMessage: {
			type: 'string',
			default: '',
		},
		completionLogoId: {
			type: 'number',
			default: 0,
		},
		completionLogoUrl: {
			type: 'string',
			default: '',
		},
		completionButtonLabel: {
			type: 'string',
			default: '',
		},
		// === Atributos de Aleatorización (Fase 1) ===
		useRandomization: {
			type: 'boolean',
			default: false,
		},
		randomConfig: {
			type: 'object',
			default: {
				forms: [], // Array de post IDs
				probabilities: {}, // { formId: percentage }
				method: 'seeded', // 'simple' | 'seeded'
				manualAssigns: [], // [{ email, formId, timestamp }]
			},
		},
		// === Atributos de Analytics & Timing ===
		capturePageTiming: {
			type: 'boolean',
			default: true,
		},
		captureFieldTiming: {
			type: 'boolean',
			default: true,
		},
		showTimingAnalysis: {
			type: 'boolean',
			default: false,
		},
	},
	edit: Edit,
	save: Save,
} );
