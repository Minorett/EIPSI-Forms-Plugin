/**
 * Editor UI para Bloque de Aleatorización - KISS (Keep It Simple, Stupid)
 *
 * Filosofía: Backend hace TODO el trabajo, el editor solo guarda datos
 * - Textarea para shortcodes (uno por línea)
 * - Backend parsea, valida y detecta formularios
 * - Inputs numéricos simples para probabilidades
 * - Un botón para guardar, un shortcode para copiar
 *
 * @since 1.3.5
 */

/* global navigator */

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
	ToggleControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

export default function Edit( { attributes, setAttributes } ) {
	const { shortcodesInput, savedConfig, generatedShortcode } = attributes;

	const [ isLoading, setIsLoading ] = useState( false );
	const [ isDetecting, setIsDetecting ] = useState( false );
	const [ copiedShortcode, setCopiedShortcode ] = useState( false );
	const [ errorMessage, setErrorMessage ] = useState( '' );

	/**
	 * Detectar formularios desde el textarea
	 * El backend parsea y valida los shortcodes
	 */
	const handleDetectarFormularios = async () => {
		setIsDetecting( true );
		setErrorMessage( '' );

		try {
			const postId = wp.data.select( 'core/editor' ).getCurrentPostId();

			const response = await apiFetch( {
				path: '/eipsi/v1/randomization-detect',
				method: 'POST',
				data: {
					post_id: postId,
					shortcodes_input: shortcodesInput,
				},
			} );

			if ( response.success ) {
				// Distribuir probabilidades equitativamente
				const numForms = response.formularios.length;
				const basePercentage = Math.floor( 100 / numForms );
				const remainder = 100 % numForms;

				const probabilidades = {};

				// Orden FIJO: Ordenar por ID para distribución determinística
				// Esto asegura que las probabilidades no dependan del orden de entrada
				const sortedForms = [ ...response.formularios ].sort(
					( a, b ) => a.id - b.id
				);

				// Distribuir probabilidades de forma determinística basada en form_id
				sortedForms.forEach( ( form, index ) => {
					// El +1% va a los últimos (por ID más alto), no a los primeros
					probabilidades[ form.id ] =
						basePercentage + ( index < remainder ? 1 : 0 );
				} );

				setAttributes( {
					savedConfig: {
						...response,
						probabilidades,
					},
				} );
			} else {
				setErrorMessage(
					response.message || 'Error detectando formularios.'
				);
			}
		} catch ( error ) {
			// eslint-disable-next-line no-console
			console.error( 'Error detectando formularios:', error );
			setErrorMessage(
				'Error detectando formularios. Verificá la consola.'
			);
		} finally {
			setIsDetecting( false );
		}
	};

	/**
	 * Cambiar probabilidad de un formulario
	 *
	 * @param {string} formId   ID del formulario
	 * @param {string} newValue Nuevo valor de probabilidad
	 */
	const handleProbabilidadChange = ( formId, newValue ) => {
		const updatedProbabilidades = {
			...savedConfig.probabilidades,
			[ formId ]: Math.max(
				0,
				Math.min( 100, parseInt( newValue ) || 0 )
			),
		};

		setAttributes( {
			savedConfig: {
				...savedConfig,
				probabilidades: updatedProbabilidades,
			},
		} );
	};

	/**
	 * Distribuir probabilidades equitativamente
	 */
	const handleDistribuirEquitativamente = () => {
		if (
			! savedConfig.formularios ||
			savedConfig.formularios.length === 0
		) {
			return;
		}

		const numForms = savedConfig.formularios.length;
		const basePercentage = Math.floor( 100 / numForms );
		const remainder = 100 % numForms;

		const probabilidades = {};
		savedConfig.formularios.forEach( ( form, index ) => {
			probabilidades[ form.id ] =
				basePercentage + ( index < remainder ? 1 : 0 );
		} );

		setAttributes( {
			savedConfig: {
				...savedConfig,
				probabilidades,
			},
		} );
	};

	/**
	 * Guardar configuración en backend
	 */
	const handleGuardarConfiguracion = async () => {
		setIsLoading( true );
		setErrorMessage( '' );

		try {
			const postId = wp.data.select( 'core/editor' ).getCurrentPostId();

			// Validaciones básicas
			if (
				! savedConfig.formularios ||
				savedConfig.formularios.length < 1
			) {
				setErrorMessage( 'Necesitás al menos 1 formulario.' );
				setIsLoading( false );
				return;
			}

			const total = Object.values( savedConfig.probabilidades ).reduce(
				( sum, val ) => sum + ( val || 0 ),
				0
			);
			if ( total !== 100 ) {
				setErrorMessage(
					`Las probabilidades deben sumar 100%. Total actual: ${ total }%`
				);
				setIsLoading( false );
				return;
			}

			const response = await apiFetch( {
				path: '/eipsi/v1/randomization-config',
				method: 'POST',
				data: {
					post_id: postId,
					formularios: savedConfig.formularios,
					probabilidades: savedConfig.probabilidades,
					metodo: savedConfig.metodo || 'pure-random',
					seed: savedConfig.seed || '',
					persistent_mode: savedConfig.persistentMode !== false,
				},
			} );

			if ( response.success ) {
				setAttributes( {
					generatedShortcode: response.shortcode,
				} );
			} else {
				setErrorMessage(
					response.message || 'Error guardando configuración.'
				);
			}
		} catch ( error ) {
			// eslint-disable-next-line no-console
			console.error( 'Error guardando configuración:', error );
			setErrorMessage( 'Error guardando configuración.' );
		} finally {
			setIsLoading( false );
		}
	};

	/**
	 * Copiar shortcode al portapapeles
	 */
	const handleCopyShortcode = () => {
		if ( ! generatedShortcode ) {
			return;
		}

		navigator.clipboard
			.writeText( generatedShortcode )
			.then( () => {
				setCopiedShortcode( true );
				setTimeout( () => setCopiedShortcode( false ), 2000 );
			} )
			.catch( () => {
				setErrorMessage( 'Error copiando al portapapeles.' );
			} );
	};

	const blockProps = useBlockProps( {
		className: 'eipsi-randomization-block',
	} );

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody
					title={ __(
						'🎲 Aleatorización de Formularios',
						'eipsi-forms'
					) }
					initialOpen={ true }
				>
					<p>
						{ __(
							'Configurá la aleatorización de formularios. El backend se encarga de validar y asignar.',
							'eipsi-forms'
						) }
					</p>
				</PanelBody>

				{ savedConfig?.formularios &&
					savedConfig.formularios.length > 0 && (
						<PanelBody
							title={ __(
								'⚙️ Configuración Avanzada',
								'eipsi-forms'
							) }
							initialOpen={ false }
						>
							<ToggleControl
								label={ __(
									'Modo Persistente (Recomendado)',
									'eipsi-forms'
								) }
								help={
									savedConfig.persistentMode !== false
										? __(
												'Los participantes mantienen la misma asignación en futuras visitas',
												'eipsi-forms'
										  )
										: __(
												'⚠️ MODO TEST: Los participantes reciben una nueva asignación en cada visita',
												'eipsi-forms'
										  )
								}
								checked={ savedConfig.persistentMode !== false }
								onChange={ ( value ) => {
									const updatedConfig = {
										...savedConfig,
										persistentMode: value,
									};
									setAttributes( {
										savedConfig: updatedConfig,
									} );
								} }
							/>
							{ savedConfig.persistentMode === false && (
								<Notice
									status="warning"
									isDismissible={ false }
								>
									{ __(
										'⚠️ Estás en MODO TEST. La aleatorización se reevalúa en cada carga de página. No usar en producción para estudios clínicos.',
										'eipsi-forms'
									) }
								</Notice>
							) }
						</PanelBody>
					) }
			</InspectorControls>

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
							{ __( 'Shortcodes de Formularios', 'eipsi-forms' ) }
						</h3>
						<p style={ { marginBottom: '0.5rem' } }>
							{ __(
								'Ingresá un shortcode por línea:',
								'eipsi-forms'
							) }
						</p>
						<TextareaControl
							value={ shortcodesInput || '' }
							onChange={ ( value ) =>
								setAttributes( { shortcodesInput: value } )
							}
							placeholder={
								'[eipsi_form id="2424"] [eipsi_form id="2417"]'
							}
							rows={ 6 }
							style={ { width: '100%' } }
						/>
						<Button
							variant="secondary"
							onClick={ handleDetectarFormularios }
							disabled={ ! shortcodesInput || isDetecting }
							style={ { marginTop: '1rem' } }
						>
							{ isDetecting
								? __( 'Detectando…', 'eipsi-forms' )
								: __(
										'🔍 Detectar Formularios',
										'eipsi-forms'
								  ) }
						</Button>
					</div>

					{ /* SECCIÓN 2: Formularios Detectados */ }
					{ savedConfig?.formularios &&
						savedConfig.formularios.length > 0 && (
							<div style={ { marginBottom: '2rem' } }>
								<h3>
									{ __(
										'Formularios Detectados',
										'eipsi-forms'
									) }
								</h3>
								{ savedConfig.formularios.map(
									( formulario ) => (
										<div
											key={ formulario.id }
											style={ {
												padding: '0.75rem',
												marginBottom: '0.5rem',
												border: '1px solid #ddd',
												borderRadius: '4px',
												backgroundColor: '#f0f8ff',
											} }
										>
											<strong>{ formulario.name }</strong>
											<div
												style={ {
													fontSize: '0.9rem',
													color: '#666',
												} }
											>
												ID: { formulario.id }
											</div>
										</div>
									)
								) }
							</div>
						) }

					{ /* SECCIÓN 3: Configurar Probabilidades */ }
					{ savedConfig?.formularios &&
						savedConfig.formularios.length > 0 && (
							<div style={ { marginBottom: '2rem' } }>
								<h3>
									{ __(
										'Configurar Probabilidades',
										'eipsi-forms'
									) }
								</h3>
								{ savedConfig.formularios.map(
									( formulario ) => {
										const porcentaje =
											savedConfig.probabilidades?.[
												formulario.id
											] || 0;
										return (
											<div
												key={ formulario.id }
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
										);
									}
								) }
								<div
									style={ {
										display: 'flex',
										justifyContent: 'space-between',
										alignItems: 'center',
										marginTop: '1rem',
										padding: '0.75rem',
										backgroundColor:
											Object.values(
												savedConfig.probabilidades || {}
											).reduce(
												( sum, val ) =>
													sum + ( val || 0 ),
												0
											) === 100
												? '#e8f5e8'
												: '#fff5f5',
										borderRadius: '4px',
									} }
								>
									<strong>
										Total:{ ' ' }
										{ Object.values(
											savedConfig.probabilidades || {}
										).reduce(
											( sum, val ) => sum + ( val || 0 ),
											0
										) }
										%
										{ Object.values(
											savedConfig.probabilidades || {}
										).reduce(
											( sum, val ) => sum + ( val || 0 ),
											0
										) === 100
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

					{ /* SECCIÓN 4: Guardar y Generar Shortcode */ }
					<div
						style={ {
							borderTop: '1px solid #ddd',
							paddingTop: '1rem',
						} }
					>
						<Button
							variant="primary"
							onClick={ handleGuardarConfiguracion }
							disabled={
								! savedConfig?.formularios ||
								savedConfig.formularios.length === 0 ||
								isLoading
							}
							style={ {
								width: '100%',
								marginBottom: '1rem',
							} }
						>
							{ isLoading
								? __( 'Guardando…', 'eipsi-forms' )
								: __(
										'💾 Guardar Configuración',
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
											? __( '✅ Copiado', 'eipsi-forms' )
											: __( '📋 Copiar', 'eipsi-forms' ) }
									</Button>

									<Button
										variant="secondary"
										href={ `/wp-admin/admin.php?page=eipsi-results&tab=rct-analytics&config=${ encodeURIComponent(
											savedConfig?.config_id || ''
										) }` }
										target="_blank"
										rel="noopener noreferrer"
										disabled={ ! savedConfig?.config_id }
										className="analytics-button"
										style={ {
											background:
												'linear-gradient(135deg, #3b82f6, #2563eb)',
											color: 'white',
											border: 'none',
										} }
									>
										📊{ ' ' }
										{ __(
											'Ver Analytics en Vivo',
											'eipsi-forms'
										) }
									</Button>
								</div>
								<Notice
									status="success"
									isDismissible={ false }
								>
									{ __(
										'✅ Configuración guardada. Copiá este shortcode para usarlo en cualquier página.',
										'eipsi-forms'
									) }
								</Notice>
							</div>
						) }
					</div>
				</CardBody>
			</Card>
		</div>
	);
}
