/**
 * Editor UI para Bloque de Aleatorización - FLUJO MANUAL
 *
 * Features:
 * - Input manual de shortcodes [eipsi_form id="XXXX"]
 * - Validación y parsing de shortcodes en tiempo real
 * - Configuración visual de probabilidades
 * - Generación automática de shortcode único para el template
 * - Guardado como post meta del template
 *
 * @since 1.3.4
 */

/* global navigator */

import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
    PanelBody,
    TextControl,
    TextareaControl,
    SelectControl,
    Button,
    ToggleControl,
    Notice,
    Card,
    CardBody,
    CardHeader,
    Collapsible,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

export default function Edit( { attributes, setAttributes } ) {
    const {
        enabled,
        shortcodesInput,
        formulariosDetectados,
        probabilidades,
        metodo,
        seed,
        permitirOverride,
        registrarAsignaciones,
        generatedShortcode,
    } = attributes;

    const [ isLoading, setIsLoading ] = useState( false );
    const [ copiedShortcode, setCopiedShortcode ] = useState( false );
    const [ errorMessage, setErrorMessage ] = useState( '' );
    const [ validatingFormIds, setValidatingFormIds ] = useState( new Set() );

    // Parsear shortcodes del textarea
    const parseShortcodes = ( input ) => {
        const regex = /\[eipsi_form\s+id=["\']?(\d+)["\']?\]/g;
        const formularios = [];
        let match;

        while ( ( match = regex.exec( input ) ) !== null ) {
            formularios.push( {
                id: match[ 1 ],
                shortcode: match[ 0 ],
            } );
        }

        return formularios;
    };

    // Validar que existan los formularios
    const validateFormularios = async ( formulariosData ) => {
        setValidatingFormIds( new Set( formulariosData.map( ( f ) => f.id ) ) );
        const validation = {};

        for ( const formulario of formulariosData ) {
            try {
                const response = await apiFetch( {
                    path: `/wp/v2/eipsi_form_template/${ formulario.id }?status=publish`,
                } );

                if ( response ) {
                    validation[ formulario.id ] = {
                        exists: true,
                        name:
                            response.title?.rendered ||
                            `Formulario #${ formulario.id }`,
                    };
                } else {
                    validation[ formulario.id ] = { exists: false };
                }
            } catch ( error ) {
                validation[ formulario.id ] = { exists: false };
            }
        }

        setValidatingFormIds( new Set() );

        // Actualizar formularios detectados
        const formulariosConNombres = formulariosData.map( ( formulario ) => ( {
            ...formulario,
            name:
                validation[ formulario.id ]?.name ||
                `Formulario #${ formulario.id }`,
            exists: validation[ formulario.id ]?.exists || false,
        } ) );

        setAttributes( { formulariosDetectados: formulariosConNombres } );
    };

    // Manejar cambios en el textarea de shortcodes
    const handleShortcodesChange = ( value ) => {
        setAttributes( { shortcodesInput: value } );

        // Validar formato en tiempo real
        if ( value.trim() ) {
            const detectedForms = parseShortcodes( value );
            if ( detectedForms.length > 0 ) {
                validateFormularios( detectedForms );
            } else {
                setAttributes( { formulariosDetectados: [] } );
            }
        } else {
            setAttributes( { formulariosDetectados: [] } );
        }
    };

    // Manejar cambios en probabilidades
    const handleProbabilidadChange = ( formId, newValue ) => {
        const updatedProbabilidades = {
            ...probabilidades,
            [ formId ]: Math.max(
                0,
                Math.min( 100, parseInt( newValue ) || 0 )
            ),
        };

        setAttributes( { probabilidades: updatedProbabilidades } );
    };

    // Distribuir probabilidades equitativamente
    const handleDistribuirEquitativamente = () => {
        if ( formulariosDetectados.length === 0 ) {
            return;
        }

        const numForms = formulariosDetectados.length;
        const basePercentage = Math.floor( 100 / numForms );
        const remainder = 100 % numForms;

        const updatedProbabilidades = {};
        formulariosDetectados.forEach( ( form, index ) => {
            updatedProbabilidades[ form.id ] =
                basePercentage + ( index < remainder ? 1 : 0 );
        } );

        setAttributes( { probabilidades: updatedProbabilidades } );
    };

    // Eliminar formulario
    const handleRemoveForm = ( formId ) => {
        const updatedForms = formulariosDetectados.filter(
            ( f ) => f.id !== formId
        );
        const updatedProbabilidades = { ...probabilidades };
        delete updatedProbabilidades[ formId ];

        setAttributes( {
            formulariosDetectados: updatedForms,
            probabilidades: updatedProbabilidades,
        } );

        // Redistribuir si quedan formularios
        if ( updatedForms.length > 0 ) {
            handleDistribuirEquitativamente();
        }
    };

    // Guardar configuración y generar shortcode
    const handleGuardarConfiguracion = async () => {
        setErrorMessage( '' );

        // Validaciones
        if ( formulariosDetectados.length < 1 ) {
            setErrorMessage( 'Necesitás ingresar al menos 1 formulario.' );
            return;
        }

        const totalProbabilidades = Object.values( probabilidades ).reduce(
            ( sum, val ) => sum + ( val || 0 ),
            0
        );
        if ( totalProbabilidades !== 100 ) {
            setErrorMessage(
                `Las probabilidades deben sumar 100%. Total actual: ${ totalProbabilidades }%`
            );
            return;
        }

        const formulariosValidos = formulariosDetectados.filter(
            ( f ) => f.exists
        );
        if ( formulariosValidos.length !== formulariosDetectados.length ) {
            setErrorMessage(
                'Algunos formularios no existen. Verificá los IDs ingresados.'
            );
            return;
        }

        setIsLoading( true );

        try {
            // Obtener el ID del post actual
            const postId = wp.data.select( 'core/editor' ).getCurrentPostId();

            const configData = {
                post_id: postId,
                shortcodes: formulariosDetectados.map(
                    ( f ) => `[eipsi_form id="${ f.id }"]`
                ),
                formularios: formulariosDetectados,
                probabilidades,
                metodo,
                seed: seed || '',
                permitirOverride,
                registrarAsignaciones,
            };

            const response = await apiFetch( {
                path: '/eipsi/v1/randomization-config',
                method: 'POST',
                data: configData,
            } );

            if ( response.success ) {
                setAttributes( {
                    generatedShortcode: response.shortcode,
                } );
            } else {
                setErrorMessage(
                    'Error guardando configuración. Intentalo nuevamente.'
                );
            }
        } catch ( error ) {
            // eslint-disable-next-line no-console
            console.error( 'Error guardando configuración:', error );
            setErrorMessage(
                'Error guardando configuración. Verificá la consola para más detalles.'
            );
        } finally {
            setIsLoading( false );
        }
    };

    // Copiar shortcode al portapapeles
    const handleCopyShortcode = () => {
        if ( ! generatedShortcode ) {
            return;
        }

        navigator.clipboard.writeText( generatedShortcode ).then( () => {
            setCopiedShortcode( true );
            setTimeout( () => setCopiedShortcode( false ), 2000 );
        } );
    };

    const blockProps = useBlockProps( {
        className: 'eipsi-randomization-block',
    } );

    const totalProbabilidades = Object.values( probabilidades ).reduce(
        ( sum, val ) => sum + ( val || 0 ),
        0
    );
    const isValidConfig =
        formulariosDetectados.length >= 1 && totalProbabilidades === 100;

    return (
        <div { ...blockProps }>
            <InspectorControls>
                <PanelBody
                    title={ __( '⚙️ Configuración General', 'eipsi-forms' ) }
                    initialOpen={ true }
                >
                    <ToggleControl
                        label={ __( 'Activar Aleatorización', 'eipsi-forms' ) }
                        checked={ enabled }
                        onChange={ ( value ) =>
                            setAttributes( { enabled: value } )
                        }
                        help={ __(
                            'Activar el bloque de aleatorización de formularios',
                            'eipsi-forms'
                        ) }
                    />
                </PanelBody>
            </InspectorControls>

            { ! enabled ? (
                <Card>
                    <CardBody>
                        <Notice status="info" isDismissible={ false }>
                            { __(
                                'La aleatorización está desactivada. Actívala en el panel lateral para empezar.',
                                'eipsi-forms'
                            ) }
                        </Notice>
                    </CardBody>
                </Card>
            ) : (
                <Card>
                    <CardHeader>
                        <h2
                            style={ {
                                fontWeight: 'bold',
                                fontSize: '1.25rem',
                            } }
                        >
                            🎲 Aleatorización de Formularios
                        </h2>
                    </CardHeader>
                    <CardBody>
                        { errorMessage && (
                            <Notice status="error" isDismissible={ false }>
                                { errorMessage }
                            </Notice>
                        ) }

                        { /* SECCIÓN 1: Input de Shortcodes */ }
                        <div style={ { marginBottom: '2rem' } }>
                            <h3>
                                { __(
                                    'Formularios a Aleatorizar',
                                    'eipsi-forms'
                                ) }
                            </h3>
                            <TextareaControl
                                value={ shortcodesInput || '' }
                                onChange={ handleShortcodesChange }
                                placeholder={ __(
                                    'Ingresá los shortcodes de los formularios que deseas aleatorizar.' +
                                        ' Un shortcode por línea. Ejemplo:' +
                                        ' [eipsi_form id="2424"]' +
                                        ' [eipsi_form id="2417"]',
                                    'eipsi-forms'
                                ) }
                                rows={ 6 }
                                style={ { width: '100%' } }
                            />
                        </div>

                        { /* SECCIÓN 2: Formularios Detectados */ }
                        { formulariosDetectados.length > 0 && (
                            <div style={ { marginBottom: '2rem' } }>
                                <h3>
                                    { __(
                                        'Formularios Detectados',
                                        'eipsi-forms'
                                    ) }
                                </h3>
                                { formulariosDetectados.map( ( formulario ) => (
                                    <div
                                        key={ formulario.id }
                                        style={ {
                                            display: 'flex',
                                            alignItems: 'center',
                                            padding: '0.75rem',
                                            marginBottom: '0.5rem',
                                            border: '1px solid #ddd',
                                            borderRadius: '4px',
                                            backgroundColor: formulario.exists
                                                ? '#f0f8ff'
                                                : '#fff5f5',
                                        } }
                                    >
                                        <div style={ { flex: 1 } }>
                                            <div
                                                style={ { fontWeight: 'bold' } }
                                            >
                                                { formulario.name }
                                            </div>
                                            <div
                                                style={ {
                                                    fontSize: '0.9rem',
                                                    color: '#666',
                                                } }
                                            >
                                                ID: { formulario.id } |{ ' ' }
                                                { formulario.shortcode }
                                            </div>
                                        </div>
                                        <div
                                            style={ { marginRight: '0.5rem' } }
                                        >
                                            { (() => {
                                                if (validatingFormIds.has(formulario.id)) {
                                                    return <span>⏳ Validando...</span>;
                                                }
                                                if (formulario.exists) {
                                                    return (
                                                        <span style={{ color: 'green' }}>
                                                            ✅ Existe
                                                        </span>
                                                    );
                                                }
                                                return (
                                                    <span style={{ color: 'red' }}>
                                                        ⚠️ No existe
                                                    </span>
                                                );
                                            })() }
                                        </div>
                                        <Button
                                            variant="link"
                                            onClick={ () =>
                                                handleRemoveForm(
                                                    formulario.id
                                                )
                                            }
                                            style={ { color: 'red' } }
                                        >
                                            ✕
                                        </Button>
                                    </div>
                                ) ) }
                            </div>
                        ) }

                        { formulariosDetectados.length === 0 &&
                            shortcodesInput.trim() && (
                                <Notice
                                    status="warning"
                                    isDismissible={ false }
                                >
                                    { __(
                                        'No se detectaron shortcodes válidos. Verificá el formato.',
                                        'eipsi-forms'
                                    ) }
                                </Notice>
                            ) }

                        { /* SECCIÓN 3: Configurar Probabilidades */ }
                        { formulariosDetectados.length > 0 && (
                            <div style={ { marginBottom: '2rem' } }>
                                <h3>
                                    { __(
                                        'Configurar Probabilidades',
                                        'eipsi-forms'
                                    ) }
                                </h3>
                                { formulariosDetectados.map( ( formulario ) => {
                                    const porcentaje =
                                        probabilidades[ formulario.id ] || 0;
                                    return (
                                        <div
                                            key={ formulario.id }
                                            style={ { marginBottom: '1rem' } }
                                        >
                                            <div
                                                style={ {
                                                    display: 'flex',
                                                    alignItems: 'center',
                                                    marginBottom: '0.5rem',
                                                } }
                                            >
                                                <strong style={ { flex: 1 } }>
                                                    { formulario.name }
                                                </strong>
                                                <TextControl
                                                    type="number"
                                                    value={ porcentaje }
                                                    onChange={ ( value ) =>
                                                        handleProbabilidadChange(
                                                            formulario.id,
                                                            value
                                                        )
                                                    }
                                                    min={ 0 }
                                                    max={ 100 }
                                                    style={ {
                                                        width: '80px',
                                                        marginLeft: '1rem',
                                                    } }
                                                />
                                                <span
                                                    style={ {
                                                        marginLeft: '0.5rem',
                                                    } }
                                                >
                                                    %
                                                </span>
                                            </div>
                                            <div
                                                style={ {
                                                    backgroundColor: '#f0f0f0',
                                                    height: '8px',
                                                    borderRadius: '4px',
                                                    overflow: 'hidden',
                                                } }
                                            >
                                                <div
                                                    style={ {
                                                        backgroundColor: `hsl(${
                                                            formulario.id % 360
                                                        }, 70%, 50%)`,
                                                        height: '100%',
                                                        width: `${ porcentaje }%`,
                                                        transition:
                                                            'width 0.3s ease',
                                                    } }
                                                />
                                            </div>
                                        </div>
                                    );
                                } ) }
                                <div
                                    style={ {
                                        display: 'flex',
                                        justifyContent: 'space-between',
                                        alignItems: 'center',
                                        marginTop: '1rem',
                                        padding: '0.75rem',
                                        backgroundColor:
                                            totalProbabilidades === 100
                                                ? '#e8f5e8'
                                                : '#fff5f5',
                                        borderRadius: '4px',
                                    } }
                                >
                                    <strong>
                                        Total: { totalProbabilidades }%
                                        { totalProbabilidades === 100
                                            ? ' ✅'
                                            : ' ❌' }
                                    </strong>
                                    <Button
                                        variant="secondary"
                                        onClick={
                                            handleDistribuirEquitativamente
                                        }
                                    >
                                        { __(
                                            'Distribuir Equitativamente',
                                            'eipsi-forms'
                                        ) }
                                    </Button>
                                </div>
                            </div>
                        ) }

                        { /* SECCIÓN 4: Configuración Avanzada */ }
                        <Collapsible defaultOpen={ false }>
                            <div style={ { marginBottom: '2rem' } }>
                                <h3>
                                    { __(
                                        'Configuración Avanzada',
                                        'eipsi-forms'
                                    ) }
                                </h3>
                                <SelectControl
                                    label={ __(
                                        'Método de Aleatorización',
                                        'eipsi-forms'
                                    ) }
                                    value={ metodo }
                                    options={ [
                                        {
                                            label: __(
                                                'Aleatorización Pura',
                                                'eipsi-forms'
                                            ),
                                            value: 'pure-random',
                                        },
                                        {
                                            label: __(
                                                'Seeded (Determinista)',
                                                'eipsi-forms'
                                            ),
                                            value: 'seeded',
                                        },
                                    ] }
                                    onChange={ ( value ) =>
                                        setAttributes( { metodo: value } )
                                    }
                                    help={ __(
                                        'Aleatorización pura: cada usuario tiene igual probabilidad. Seeded: reproducible con la misma seed.',
                                        'eipsi-forms'
                                    ) }
                                />

                                { metodo === 'seeded' && (
                                    <TextControl
                                        label={ __(
                                            'Seed Value',
                                            'eipsi-forms'
                                        ) }
                                        value={ seed }
                                        onChange={ ( value ) =>
                                            setAttributes( { seed: value } )
                                        }
                                        help={ __(
                                            'Valor seed para reproducibilidad. Usar la misma seed siempre da la misma distribución.',
                                            'eipsi-forms'
                                        ) }
                                    />
                                ) }

                                <ToggleControl
                                    label={ __(
                                        'Permitir Override Manual',
                                        'eipsi-forms'
                                    ) }
                                    checked={ permitirOverride }
                                    onChange={ ( value ) =>
                                        setAttributes( {
                                            permitirOverride: value,
                                        } )
                                    }
                                    help={ __(
                                        'Permitir forzar un formulario específico vía URL (?form_id=XXX)',
                                        'eipsi-forms'
                                    ) }
                                />

                                <ToggleControl
                                    label={ __(
                                        'Registrar Asignaciones',
                                        'eipsi-forms'
                                    ) }
                                    checked={ registrarAsignaciones }
                                    onChange={ ( value ) =>
                                        setAttributes( {
                                            registrarAsignaciones: value,
                                        } )
                                    }
                                    help={ __(
                                        'Guarda quién recibió qué formulario para análisis posterior',
                                        'eipsi-forms'
                                    ) }
                                />
                            </div>
                        </Collapsible>

                        { /* SECCIÓN 5: Guardar y Generar Shortcode */ }
                        <div
                            style={ {
                                borderTop: '1px solid #ddd',
                                paddingTop: '1rem',
                            } }
                        >
                            <Button
                                variant="primary"
                                onClick={ handleGuardarConfiguracion }
                                disabled={ ! isValidConfig || isLoading }
                                style={ {
                                    width: '100%',
                                    marginBottom: '1rem',
                                } }
                            >
                                { isLoading
                                    ? __( 'Guardando…', 'eipsi-forms' )
                                    : __(
                                            '💾 Guardar Configuración de Aleatorización',
                                            'eipsi-forms'
                                      ) }
                            </Button>

                            { generatedShortcode && (
                                <div>
                                    <h4>
                                        { __(
                                            'Shortcode Generado:',
                                            'eipsi-forms'
                                        ) }
                                    </h4>
                                    <div
                                        style={ {
                                            display: 'flex',
                                            gap: '0.5rem',
                                        } }
                                    >
                                        <TextControl
                                            value={ generatedShortcode }
                                            readOnly
                                            style={ { flex: 1 } }
                                        />
                                        <Button
                                            variant="secondary"
                                            onClick={ handleCopyShortcode }
                                        >
                                            { copiedShortcode
                                                ? __(
                                                        '✅ Copiado',
                                                        'eipsi-forms'
                                                  )
                                                : __(
                                                        '📋 Copiar',
                                                        'eipsi-forms'
                                                  ) }
                                        </Button>
                                    </div>
                                    <Notice
                                        status="success"
                                        isDismissible={ false }
                                    >
                                        { __(
                                            '✅ Configuración guardada. Shortcode generado.',
                                            'eipsi-forms'
                                        ) }
                                    </Notice>
                                </div>
                            ) }
                        </div>
                    </CardBody>
                </Card>
            ) }
        </div>
    );
}
