import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import ConsentSettings from '../../components/ConsentSettings';
import {
	parseConsentMarkdown,
	validateConsentMarkdown,
} from '../../../assets/js/consent-markdown-parser';
import {
	serializeToCSSVariables,
	DEFAULT_STYLE_CONFIG,
} from '../../utils/styleTokens';

const renderConsentBody = ( text ) => {
	if ( ! text || text.trim() === '' ) {
		return (
			<p className="eipsi-preview-placeholder">
				{ __(
					'Escriba el contenido del consentimiento aquí…',
					'eipsi-forms'
				) }
			</p>
		);
	}

	const lines = text.split( '\n' );

	return (
		<div className="eipsi-consent-body">
			{ lines.map( ( line, index ) => {
				// Parsear markdown en cada línea
				const parsedLine = parseConsentMarkdown( line );
				return (
					<p
						key={ `${ line }-${ index }` }
						dangerouslySetInnerHTML={ { __html: parsedLine } }
					/>
				);
			} ) }
		</div>
	);
};

export default function Edit( { attributes, setAttributes, clientId } ) {
	const {
		titulo,
		contenido,
		textoComplementario,
		mostrarCheckbox,
		etiquetaCheckbox,
		isRequired,
	} = attributes;

	// Estado para validación de markdown
	const [ validationError, setValidationError ] = useState( null );

	// Obtener styleConfig del Form Container parent
	const styleConfig = useSelect(
		( select ) => {
			const { getBlock, getBlockParents, getBlockRootClientId } =
				select( 'core/block-editor' );

			// Buscar el Form Container parent subiendo en el árbol
			const parentIds = getBlockParents( clientId );
			for ( const parentId of parentIds ) {
				const block = getBlock( parentId );
				if ( block && block.name === 'eipsi/form-container' ) {
					return (
						block.attributes?.styleConfig || DEFAULT_STYLE_CONFIG
					);
				}
			}

			// Si no se encuentra, buscar el root block
			try {
				const rootClientId = getBlockRootClientId( clientId );
				const rootBlock = getBlock( rootClientId );
				if ( rootBlock && rootBlock.name === 'eipsi/form-container' ) {
					return (
						rootBlock.attributes?.styleConfig ||
						DEFAULT_STYLE_CONFIG
					);
				}
			} catch ( e ) {
				// Ignore errors
			}

			return DEFAULT_STYLE_CONFIG;
		},
		[ clientId ]
	);

	// Validación en tiempo real
	useEffect( () => {
		const validation = validateConsentMarkdown( contenido || '' );
		setValidationError( validation.valid ? null : validation.error );
	}, [ contenido ] );

	// Serializar styleConfig a CSS variables
	const cssVars = serializeToCSSVariables( styleConfig );

	const blockProps = useBlockProps( {
		className: 'form-group eipsi-field eipsi-consent-field',
		style: cssVars,
		'data-field-type': 'consent',
		'data-consent-block': 'true',
		'data-required': mostrarCheckbox && isRequired ? 'true' : 'false',
	} );

	// Título opcional - solo se muestra si existe (sin fallback)
	const displayLabel = titulo;

	return (
		<>
			<InspectorControls>
				<ConsentSettings
					attributes={ attributes }
					setAttributes={ setAttributes }
				/>
			</InspectorControls>

			<div { ...blockProps }>
				{ displayLabel && (
					<h3 className="consent-title">{ displayLabel }</h3>
				) }

				{ /* Validación visual */ }
				{ validationError && (
					<div
						style={ {
							marginBottom: '10px',
							padding: '10px',
							backgroundColor: '#fff3cd',
							border: '1px solid #ffc107',
							borderRadius: '4px',
							color: '#856404',
							fontSize: '12px',
						} }
					>
						⚠️ { validationError }
					</div>
				) }

				{ /* Preview dinámico con markdown parseado */ }
				{ renderConsentBody( contenido ) }

				{ textoComplementario && (
					<p className="consent-note">{ textoComplementario }</p>
				) }

				{ mostrarCheckbox && (
					<div className="eipsi-consent-checkbox-wrapper">
						<input
							type="checkbox"
							id="consent-preview-checkbox"
							disabled
							checked={ false }
						/>
						<label htmlFor="consent-preview-checkbox">
							{ etiquetaCheckbox }
							{ isRequired && (
								<span className="eipsi-required-mark">*</span>
							) }
						</label>
					</div>
				) }
			</div>
		</>
	);
}
