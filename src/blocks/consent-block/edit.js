import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { useState, useEffect } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import ConsentSettings from '../../components/ConsentSettings';
import {
    parseConsentMarkdown,
    validateConsentMarkdown,
} from '../utils/markdownParser';
import {
    serializeToCSSVariables,
    DEFAULT_STYLE_CONFIG,
} from '../../utils/styleTokens';
import { renderConsentBody } from '../../utils/field-helpers';

export default function Edit( { attributes, setAttributes, clientId } ) {
    const {
        titulo,
        contenido,
        textoComplementario,
        mostrarCheckbox,
        etiquetaCheckbox,
        isRequired,
        etiquetaConfirmacionLectura,
        textoBotonRechazar,
        textoBotonAceptar,
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
                { renderConsentBody( contenido, parseConsentMarkdown, true ) }

                { textoComplementario && (
                    <p className="consent-note">{ textoComplementario }</p>
                ) }

                { mostrarCheckbox && (
                    <div className="eipsi-consent-control-preview">
                        {/* v2.5: Preview de botones de decisión */}
                        <div className="eipsi-consent-buttons-preview">
                            <button
                                type="button"
                                disabled
                                style={ {
                                    backgroundColor: 'transparent',
                                    color: '#dc2626',
                                    border: '2px solid #dc2626',
                                    padding: '8px 16px',
                                    borderRadius: '6px',
                                    marginRight: '12px',
                                    opacity: 0.7,
                                    cursor: 'not-allowed',
                                } }
                            >
                                { textoBotonRechazar }
                            </button>
                            <button
                                type="button"
                                disabled
                                style={ {
                                    backgroundColor: '#16a34a',
                                    color: 'white',
                                    border: '2px solid #16a34a',
                                    padding: '8px 16px',
                                    borderRadius: '6px',
                                    opacity: 0.8,
                                    cursor: 'not-allowed',
                                } }
                            >
                                { textoBotonAceptar }
                            </button>
                        </div>

                        {/* Nota informativa para el editor */}
                        <p
                            style={ {
                                fontSize: '11px',
                                color: '#6b7280',
                                marginTop: '8px',
                                fontStyle: 'italic',
                            } }
                        >
                            💡 Los participantes deberán elegir una opción para continuar.
                        </p>
                    </div>
                ) }
            </div>
        </>
    );
}
