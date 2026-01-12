import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import ConsentSettings from '../../components/ConsentSettings';
import {
	parseConsentMarkdown,
	validateConsentMarkdown,
} from '../../../assets/js/consent-markdown-parser';

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

export default function Edit( { attributes, setAttributes } ) {
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

	// Validación en tiempo real
	useEffect( () => {
		const validation = validateConsentMarkdown( contenido || '' );
		setValidationError( validation.valid ? null : validation.error );
	}, [ contenido ] );

	const blockProps = useBlockProps( {
		className: 'form-group eipsi-field eipsi-consent-field',
		'data-field-type': 'consent',
		'data-consent-block': 'true',
		'data-required': mostrarCheckbox && isRequired ? 'true' : 'false',
	} );

	const displayLabel =
		titulo || __( 'Consentimiento Informado', 'eipsi-forms' );

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
