/**
 * Editor UI para Bloque Pool de Estudios
 *
 * Filosofía: Backend hace TODO el trabajo, el editor solo guarda datos
 * - Textarea para IDs de estudios (uno por línea o separados por coma)
 * - Backend valida y detecta estudios longitudinales
 * - Inputs numéricos simples para probabilidades
 * - Barra de progreso visual del total
 * - Un botón para guardar, un shortcode para copiar
 *
 * @since 2.5.3
 */

import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
    PanelBody,
    TextareaControl,
    TextControl,
    Button,
    Notice,
    Card,
    CardBody,
    CardHeader,
    SelectControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

export default function Edit({ attributes, setAttributes }) {
    const { poolId, poolDescription, poolIncentive, redirectMode, studies, studiesInput, generatedShortcode } = attributes;

    const [isLoading, setIsLoading] = useState(false);
    const [isDetecting, setIsDetecting] = useState(false);
    const [copiedShortcode, setCopiedShortcode] = useState(false);
    const [errorMessage, setErrorMessage] = useState('');
    const [probabilityTotal, setProbabilityTotal] = useState(0);

    // Calcular total de probabilidades cuando cambian los estudios
    useEffect(() => {
        if (studies && studies.length > 0) {
            const total = studies.reduce((sum, study) => sum + (parseFloat(study.probability) || 0), 0);
            setProbabilityTotal(total);
        } else {
            setProbabilityTotal(0);
        }
    }, [studies]);

    /**
     * Detectar estudios desde el textarea
     * El backend valida los IDs y devuelve información de los estudios
     */
    const handleDetectarEstudios = async () => {
        setIsDetecting(true);
        setErrorMessage('');

        if (!studiesInput || studiesInput.trim() === '') {
            setErrorMessage('Ingresá al menos un ID de estudio.');
            setIsDetecting(false);
            return;
        }

        try {
            // Parsear IDs de estudios (soporta separados por coma o líneas)
            const studyIds = studiesInput
                .split(/[\n,]+/)
                .map(id => id.trim())
                .filter(id => id !== '');

            if (studyIds.length === 0) {
                setErrorMessage('Ingresá al menos un ID de estudio válido.');
                setIsDetecting(false);
                return;
            }

            const response = await apiFetch({
                path: `/eipsi/v1/pool-detect?study_ids=${studyIds.join(',')}`,
                method: 'GET',
            });

            if (response.valid && response.valid.length > 0) {
                // Distribuir probabilidades equitativamente
                const numStudies = response.valid.length;
                const basePercentage = Math.floor(100 / numStudies);
                const remainder = 100 % numStudies;

                // Ordenar por ID para distribución determinística
                const sortedStudies = [...response.valid].sort((a, b) => a.id - b.id);

                const studiesWithProb = sortedStudies.map((study, index) => ({
                    id: study.id,
                    name: study.name,
                    code: study.code,
                    probability: basePercentage + (index < remainder ? 1 : 0),
                    active_participants: study.active_participants,
                    waves: study.waves,
                }));

                setAttributes({
                    studies: studiesWithProb,
                });
            } else {
                setErrorMessage('No se encontraron estudios válidos. Verificá los IDs.');
            }

            if (response.invalid && response.invalid.length > 0) {
                setErrorMessage(
                    (prev) => prev + ` IDs inválidos: ${response.invalid.join(', ')}`
                );
            }
        } catch (error) {
            // eslint-disable-next-line no-console
            console.error('Error detectando estudios:', error);
            setErrorMessage('Error detectando estudios. Verificá la consola.');
        } finally {
            setIsDetecting(false);
        }
    };

    /**
     * Cambiar probabilidad de un estudio
     */
    const handleProbabilidadChange = (studyId, newValue) => {
        const updatedStudies = studies.map(study =>
            study.id === studyId
                ? { ...study, probability: Math.max(0, Math.min(100, parseFloat(newValue) || 0)) }
                : study
        );

        setAttributes({
            studies: updatedStudies,
        });
    };

    /**
     * Distribuir probabilidades equitativamente
     */
    const handleDistribuirEquitativamente = () => {
        if (!studies || studies.length === 0) {
            return;
        }

        const numStudies = studies.length;
        const basePercentage = Math.floor(100 / numStudies);
        const remainder = 100 % numStudies;

        const updatedStudies = studies.map((study, index) => ({
            ...study,
            probability: basePercentage + (index < remainder ? 1 : 0),
        }));

        setAttributes({
            studies: updatedStudies,
        });
    };

    /**
     * Guardar configuración en backend
     */
    const handleGuardarConfiguracion = async () => {
        setIsLoading(true);
        setErrorMessage('');

        try {
            // Validaciones básicas
            if (!studies || studies.length < 1) {
                setErrorMessage('Necesitás al menos 1 estudio.');
                setIsLoading(false);
                return;
            }

            const total = studies.reduce((sum, study) => sum + (study.probability || 0), 0);
            if (total < 99.99 || total > 100.01) {
                setErrorMessage(
                    `Las probabilidades deben sumar 100%. Total actual: ${total.toFixed(2)}%`
                );
                setIsLoading(false);
                return;
            }

            const response = await apiFetch({
                path: '/eipsi/v1/pool-config',
                method: 'POST',
                data: {
                    pool_id: poolId || 0,
                    studies: studies.map(s => ({ id: s.id, probability: s.probability })),
                    method: method,
                    name: `Pool ${new Date().toISOString().split('T')[0]}`,
                },
            });

            if (response.success) {
                setAttributes({
                    poolId: String(response.pool_id),
                    generatedShortcode: `[eipsi_pool pool_id="${response.pool_id}"]`,
                });
            } else {
                setErrorMessage(response.message || 'Error guardando configuración.');
            }
        } catch (error) {
            // eslint-disable-next-line no-console
            console.error('Error guardando configuración:', error);
            setErrorMessage('Error guardando configuración.');
        } finally {
            setIsLoading(false);
        }
    };

    /**
     * Copiar shortcode al portapapeles
     */
    const handleCopyShortcode = () => {
        if (!generatedShortcode) {
            return;
        }

        navigator.clipboard
            .writeText(generatedShortcode)
            .then(() => {
                setCopiedShortcode(true);
                setTimeout(() => setCopiedShortcode(false), 2000);
            })
            .catch(() => {
                setErrorMessage('Error copiando al portapapeles.');
            });
    };

    const blockProps = useBlockProps({
        className: 'eipsi-pool-block',
    });

    // Calcular color de la barra de progreso
    const getProgressColor = () => {
        if (probabilityTotal >= 99.99 && probabilityTotal <= 100.01) return '#22c55e'; // verde
        if (probabilityTotal > 100.01) return '#ef4444'; // rojo - excedido
        return '#f59e0b'; // amarillo - incompleto
    };

    return (
        <div {...blockProps}>
            <InspectorControls>
                <PanelBody
                    title={__('📋 Mensajes para Participantes', 'eipsi-forms')}
                    initialOpen={true}
                >
                    <TextareaControl
                        label={__('Descripción del Pool', 'eipsi-forms')}
                        value={poolDescription}
                        onChange={(value) => setAttributes({ poolDescription: value })}
                        placeholder={__('Ej: Estamos comparando diferentes técnicas de intervención para ansiedad. Tu participación nos ayuda a entender cuál funciona mejor.', 'eipsi-forms')}
                        help={__('Este texto se mostrará a los participantes en la página de acceso al pool. Obligatorio.', 'eipsi-forms')}
                        rows={4}
                    />

                    <TextareaControl
                        label={__('Mensaje de incentivo (opcional)', 'eipsi-forms')}
                        value={poolIncentive}
                        onChange={(value) => setAttributes({ poolIncentive: value })}
                        placeholder={__('Ej: Sorteo de 5 gift cards de $50 entre todos los participantes que completen el estudio.', 'eipsi-forms')}
                        help={__('Si hay algún incentivo por participar, describilo aquí. Se mostrará destacado en la página de acceso.', 'eipsi-forms')}
                        rows={3}
                    />
                </PanelBody>

                <PanelBody
                    title={__('⚙️ Configuración Técnica', 'eipsi-forms')}
                    initialOpen={false}
                >
                    <SelectControl
                        label={__('Modo de acceso', 'eipsi-forms')}
                        value={redirectMode}
                        options={[
                            { 
                                label: __('Transición (por defecto) - Página de confirmación', 'eipsi-forms'), 
                                value: 'transition' 
                            },
                            { 
                                label: __('Mínimo (1 click) - Acceso inmediato', 'eipsi-forms'), 
                                value: 'minimal' 
                            },
                        ]}
                        onChange={(value) => setAttributes({ redirectMode: value })}
                        help={redirectMode === 'transition'
                            ? __('Los participantes verán una página de "Asignación exitosa" antes de acceder a su estudio.', 'eipsi-forms')
                            : __('Los participantes accederán inmediatamente a su estudio asignado con un solo click.', 'eipsi-forms')
                        }
                    />

                    <Notice status="info" isDismissible={false} style={{ marginBottom: '1rem', marginTop: '1rem' }}>
                        {__('La asignación es siempre aleatoria simple equiprobable (25% cada estudio si son 4).', 'eipsi-forms')}
                    </Notice>
                </PanelBody>
            </InspectorControls>

            <Card>
                <CardHeader>
                    <h2
                        style={{
                            fontWeight: 'bold',
                            fontSize: '1.25rem',
                        }}
                    >
                        🎯 Pool de Estudios Longitudinales
                    </h2>
                </CardHeader>
                <CardBody>
                    {errorMessage && (
                        <Notice status="error" isDismissible={false} style={{ marginBottom: '1rem' }}>
                            {errorMessage}
                        </Notice>
                    )}

                    {copiedShortcode && (
                        <Notice status="success" isDismissible={false} style={{ marginBottom: '1rem' }}>
                            {__('¡Shortcode copiado al portapapeles!', 'eipsi-forms')}
                        </Notice>
                    )}

                    {/* SECCIÓN 1: Input de IDs de Estudios */}
                    <div style={{ marginBottom: '2rem' }}>
                        <h3>{__('IDs de Estudios Longitudinales', 'eipsi-forms')}</h3>
                        <p style={{ marginBottom: '0.5rem', color: '#666' }}>
                            {__('Ingresá los IDs de los estudios separados por coma o líneas:', 'eipsi-forms')}
                        </p>
                        <TextareaControl
                            value={studiesInput || ''}
                            onChange={(value) => setAttributes({ studiesInput: value })}
                            placeholder={__('1, 2, 3', 'eipsi-forms')}
                            rows={4}
                            style={{ width: '100%', fontFamily: 'monospace' }}
                        />
                        <Button
                            variant="secondary"
                            onClick={handleDetectarEstudios}
                            disabled={!studiesInput || isDetecting}
                            style={{ marginTop: '1rem' }}
                        >
                            {isDetecting
                                ? __('Detectando…', 'eipsi-forms')
                                : __('🔍 Detectar Estudios', 'eipsi-forms')
                            }
                        </Button>
                    </div>

                    {/* SECCIÓN 2: Estudios Detectados */}
                    {studies && studies.length > 0 && (
                        <div style={{ marginBottom: '2rem' }}>
                            <h3>{__('Estudios Detectados', 'eipsi-forms')}</h3>

                            {/* Barra de progreso visual */}
                            <div style={{ marginBottom: '1rem' }}>
                                <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '0.5rem' }}>
                                    <span>{__('Distribución de probabilidades:', 'eipsi-forms')}</span>
                                    <span style={{
                                        fontWeight: 'bold',
                                        color: getProgressColor()
                                    }}>
                                        {probabilityTotal.toFixed(1)}% / 100%
                                    </span>
                                </div>
                                <div style={{
                                    width: '100%',
                                    height: '8px',
                                    backgroundColor: '#e5e7eb',
                                    borderRadius: '4px',
                                    overflow: 'hidden'
                                }}>
                                    <div style={{
                                        width: `${Math.min(probabilityTotal, 100)}%`,
                                        height: '100%',
                                        backgroundColor: getProgressColor(),
                                        transition: 'all 0.3s ease'
                                    }} />
                                </div>
                                {probabilityTotal > 100.01 && (
                                    <p style={{ color: '#ef4444', fontSize: '0.875rem', marginTop: '0.25rem' }}>
                                        {__('⚠️ El total excede 100%', 'eipsi-forms')}
                                    </p>
                                )}
                                {probabilityTotal < 99.99 && (
                                    <p style={{ color: '#f59e0b', fontSize: '0.875rem', marginTop: '0.25rem' }}>
                                        {__('⚠️ El total no suma 100%', 'eipsi-forms')}
                                    </p>
                                )}
                            </div>

                            {studies.map((study) => (
                                <div
                                    key={study.id}
                                    style={{
                                        padding: '0.75rem',
                                        marginBottom: '0.5rem',
                                        border: '1px solid #ddd',
                                        borderRadius: '4px',
                                        backgroundColor: '#f8fafc',
                                        display: 'flex',
                                        justifyContent: 'space-between',
                                        alignItems: 'center',
                                    }}
                                >
                                    <div style={{ flex: 1 }}>
                                        <strong>{study.name}</strong>
                                        <div style={{ fontSize: '0.85rem', color: '#666' }}>
                                            ID: {study.id} | Código: {study.code} | 👥 {study.active_participants} | 🌊 {study.waves} waves
                                        </div>
                                    </div>
                                    <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                                        <TextControl
                                            type="number"
                                            value={study.probability}
                                            onChange={(value) => handleProbabilidadChange(study.id, value)}
                                            min={0}
                                            max={100}
                                            step={0.01}
                                            style={{ width: '80px' }}
                                        />
                                        <span>%</span>
                                    </div>
                                </div>
                            ))}

                            <Button
                                variant="tertiary"
                                onClick={handleDistribuirEquitativamente}
                                style={{ marginTop: '0.5rem' }}
                            >
                                {__('⚖️ Distribuir equitativamente', 'eipsi-forms')}
                            </Button>
                        </div>
                    )}

                    {/* SECCIÓN 3: Guardar Configuración */}
                    {studies && studies.length > 0 && (
                        <div style={{ marginBottom: '2rem' }}>
                            <Button
                                variant="primary"
                                onClick={handleGuardarConfiguracion}
                                disabled={isLoading || probabilityTotal < 99.99 || probabilityTotal > 100.01}
                                style={{ marginRight: '1rem' }}
                            >
                                {isLoading
                                    ? __('Guardando…', 'eipsi-forms')
                                    : __('💾 Guardar Configuración', 'eipsi-forms')
                                }
                            </Button>
                        </div>
                    )}

                    {/* SECCIÓN 4: Shortcode Generado */}
                    {generatedShortcode && (
                        <div style={{
                            padding: '1rem',
                            backgroundColor: '#f0fdf4',
                            border: '1px solid #86efac',
                            borderRadius: '4px',
                        }}>
                            <h4 style={{ marginTop: 0 }}>{__('Shortcode Generado', 'eipsi-forms')}</h4>
                            <code style={{
                                display: 'block',
                                padding: '0.5rem',
                                backgroundColor: '#fff',
                                borderRadius: '4px',
                                fontFamily: 'monospace',
                                marginBottom: '0.5rem'
                            }}>
                                {generatedShortcode}
                            </code>
                            <Button
                                variant="secondary"
                                onClick={handleCopyShortcode}
                            >
                                {__('📋 Copiar Shortcode', 'eipsi-forms')}
                            </Button>
                        </div>
                    )}
                </CardBody>
            </Card>
        </div>
    );
}
