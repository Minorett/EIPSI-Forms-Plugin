/**
 * Editor UI para Bloque de Aleatorización
 *
 * Features:
 * - Configurar formularios con porcentajes automáticos
 * - Asignaciones manuales (override ético)
 * - Generación automática de shortcode y link
 * - Vista previa en tiempo real
 *
 * @since 1.3.0
 */

/* global alert, navigator */

import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	SelectControl,
	Button,
	ToggleControl,
	Notice,
	Card,
	CardBody,
	CardHeader,
	Flex,
	FlexItem,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

export default function Edit( { attributes, setAttributes } ) {
	const {
		randomizationId,
		enabled,
		formularios,
		method,
		manualAssignments,
		showPreview,
		showInstructions,
	} = attributes;

	const [ availableForms, setAvailableForms ] = useState( [] );
	const [ selectedFormId, setSelectedFormId ] = useState( '' );
	const [ emailInput, setEmailInput ] = useState( '' );
	const [ formForEmail, setFormForEmail ] = useState( '' );
	const [ isLoading, setIsLoading ] = useState( false );
	const [ copiedShortcode, setCopiedShortcode ] = useState( false );
	const [ copiedLink, setCopiedLink ] = useState( false );

	// Cargar formularios disponibles del CPT eipsi_form_template
	useEffect( () => {
		setIsLoading( true );
		apiFetch( {
			path: '/wp/v2/eipsi_form_template?per_page=100&status=publish',
		} )
			.then( ( posts ) => {
				const options = posts.map( ( post ) => ( {
					id: String( post.id ),
					label: post.title.rendered || `Formulario #${ post.id }`,
				} ) );
				setAvailableForms( options );
			} )
			.catch( ( error ) => {
				// eslint-disable-next-line no-console
				console.error(
					'[EIPSI Randomization] Error loading forms:',
					error
				);
			} )
			.finally( () => {
				setIsLoading( false );
			} );
	}, [] );

	// Generar ID único al activar
	const handleToggleEnabled = ( newValue ) => {
		if ( newValue && ! randomizationId ) {
			const id = `rand_${ Date.now() }_${ Math.random()
				.toString( 36 )
				.substr( 2, 9 ) }`;
			setAttributes( { enabled: newValue, randomizationId: id } );
		} else {
			setAttributes( { enabled: newValue } );
		}
	};

	// Agregar formulario con recalculo automático de porcentajes
	const handleAddForm = () => {
		if ( ! selectedFormId ) {
			return;
		}

		const formData = availableForms.find(
			( f ) => f.id === selectedFormId
		);
		if ( ! formData ) {
			return;
		}

		// Verificar duplicados
		const isDuplicate = formularios.some(
			( f ) => f.postId === parseInt( selectedFormId )
		);
		if ( isDuplicate ) {
			// eslint-disable-next-line no-alert
			alert( __( 'Este formulario ya está en la lista', 'eipsi-forms' ) );
			return;
		}

		const newForm = {
			id: `form_${ Date.now() }`,
			postId: parseInt( selectedFormId ),
			nombre: formData.label,
			porcentaje: 0, // Se calcula después
		};

		const updatedForms = [ ...formularios, newForm ];

		// Recalcular porcentajes para que sumen 100
		recalculatePercentages( updatedForms );

		setSelectedFormId( '' );
	};

	// Remover formulario y recalcular porcentajes
	const handleRemoveForm = ( id ) => {
		const updatedForms = formularios.filter( ( f ) => f.id !== id );

		if ( updatedForms.length > 0 ) {
			recalculatePercentages( updatedForms );
		} else {
			setAttributes( { formularios: [] } );
		}
	};

	// Recalcular porcentajes para que sumen exactamente 100
	const recalculatePercentages = ( forms ) => {
		if ( forms.length === 0 ) {
			return;
		}

		const basePercentage = Math.floor( 100 / forms.length );
		const remainder = 100 % forms.length;

		const recalculated = forms.map( ( form, index ) => ( {
			...form,
			porcentaje: basePercentage + ( index < remainder ? 1 : 0 ),
		} ) );

		setAttributes( { formularios: recalculated } );
	};

	// Agregar asignación manual
	const handleAddManualAssignment = () => {
		if ( ! emailInput || ! formForEmail ) {
			// eslint-disable-next-line no-alert
			alert(
				__(
					'Completá el email y seleccioná un formulario',
					'eipsi-forms'
				)
			);
			return;
		}

		const emailNormalized = emailInput.toLowerCase().trim();

		// Validar formato de email
		const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
		if ( ! emailRegex.test( emailNormalized ) ) {
			// eslint-disable-next-line no-alert
			alert( __( 'Email inválido', 'eipsi-forms' ) );
			return;
		}

		// Verificar duplicados
		const isDuplicate = manualAssignments.some(
			( a ) => a.email === emailNormalized
		);
		if ( isDuplicate ) {
			// eslint-disable-next-line no-alert
			alert(
				__( 'Ya existe una asignación para este email', 'eipsi-forms' )
			);
			return;
		}

		const formData = availableForms.find( ( f ) => f.id === formForEmail );
		if ( ! formData ) {
			return;
		}

		const newAssignment = {
			email: emailNormalized,
			formId: formForEmail,
			formName: formData.label,
			timestamp: Date.now(),
		};

		setAttributes( {
			manualAssignments: [ ...manualAssignments, newAssignment ],
		} );

		setEmailInput( '' );
		setFormForEmail( '' );
	};

	// Remover asignación manual
	const handleRemoveManualAssignment = ( email ) => {
		setAttributes( {
			manualAssignments: manualAssignments.filter(
				( a ) => a.email !== email
			),
		} );
	};

	// Copiar shortcode al portapapeles
	const handleCopyShortcode = () => {
		if ( formularios.length < 2 ) {
			// eslint-disable-next-line no-alert
			alert(
				__(
					'Necesitás al menos 2 formularios configurados',
					'eipsi-forms'
				)
			);
			return;
		}

		const shortcode = `[eipsi_randomization id="${ randomizationId }"]`;
		navigator.clipboard.writeText( shortcode ).then( () => {
			setCopiedShortcode( true );
			setTimeout( () => setCopiedShortcode( false ), 2000 );
		} );
	};

	// Copiar link al portapapeles
	const handleCopyLink = () => {
		if ( formularios.length < 2 ) {
			// eslint-disable-next-line no-alert
			alert(
				__(
					'Necesitás al menos 2 formularios configurados',
					'eipsi-forms'
				)
			);
			return;
		}

		const siteUrl = window.location.origin;
		const link = `${ siteUrl }/?eipsi_rand=${ randomizationId }`;
		navigator.clipboard.writeText( link ).then( () => {
			setCopiedLink( true );
			setTimeout( () => setCopiedLink( false ), 2000 );
		} );
	};

	const blockProps = useBlockProps( {
		className: 'eipsi-randomization-block',
	} );

	const totalPercentage = formularios.reduce(
		( sum, f ) => sum + ( f.porcentaje || 0 ),
		0
	);

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody
					title={ __( '⚙️ Configuración', 'eipsi-forms' ) }
					initialOpen={ true }
				>
					<ToggleControl
						label={ __( 'Activar Aleatorización', 'eipsi-forms' ) }
						checked={ enabled }
						onChange={ handleToggleEnabled }
						help={ __(
							'Cuando está activado, los participantes reciben formularios aleatorios',
							'eipsi-forms'
						) }
					/>

					{ enabled && (
						<>
							<SelectControl
								label={ __(
									'Método de Aleatorización',
									'eipsi-forms'
								) }
								value={ method }
								options={ [
									{
										label: __(
											'Con seed reproducible',
											'eipsi-forms'
										),
										value: 'seeded',
									},
									{
										label: __(
											'Random puro',
											'eipsi-forms'
										),
										value: 'pure-random',
									},
								] }
								onChange={ ( value ) =>
									setAttributes( { method: value } )
								}
								help={ __(
									'Con seed: misma asignación para mismo participante. Random puro: puede cambiar en cada acceso.',
									'eipsi-forms'
								) }
							/>

							<ToggleControl
								label={ __(
									'Mostrar Vista Previa',
									'eipsi-forms'
								) }
								checked={ showPreview }
								onChange={ ( value ) =>
									setAttributes( { showPreview: value } )
								}
							/>

							<ToggleControl
								label={ __(
									'Mostrar Instrucciones en Frontend',
									'eipsi-forms'
								) }
								checked={ showInstructions }
								onChange={ ( value ) =>
									setAttributes( {
										showInstructions: value,
									} )
								}
								help={ __(
									'Muestra un disclaimer sobre la aleatorización',
									'eipsi-forms'
								) }
							/>
						</>
					) }
				</PanelBody>
			</InspectorControls>

			<Card>
				<CardHeader>
					<h2>🎲 Configuración de Aleatorización</h2>
				</CardHeader>
				<CardBody>
					{ ! enabled ? (
						<Notice status="info" isDismissible={ false }>
							{ __(
								'La aleatorización está desactivada. Actívala en el panel lateral para empezar.',
								'eipsi-forms'
							) }
						</Notice>
					) : (
						<>
							{ /* Formularios para Aleatorizar */ }
							<h3>
								{ __(
									'Formularios para Aleatorizar',
									'eipsi-forms'
								) }
							</h3>

							<Flex gap={ 2 } style={ { marginBottom: '1rem' } }>
								<FlexItem style={ { flex: 1 } }>
									<SelectControl
										value={ selectedFormId }
										options={ [
											{
												label: __(
													'Seleccionar formulario…',
													'eipsi-forms'
												),
												value: '',
											},
											...availableForms.map( ( f ) => ( {
												label: f.label,
												value: f.id,
											} ) ),
										] }
										onChange={ setSelectedFormId }
										disabled={ isLoading }
									/>
								</FlexItem>
								<FlexItem>
									<Button
										variant="primary"
										onClick={ handleAddForm }
										disabled={
											! selectedFormId || isLoading
										}
									>
										{ __( '➕ Añadir', 'eipsi-forms' ) }
									</Button>
								</FlexItem>
							</Flex>

							{ isLoading && (
								<Notice status="info" isDismissible={ false }>
									{ __(
										'Cargando formularios…',
										'eipsi-forms'
									) }
								</Notice>
							) }

							{ formularios.length > 0 && (
								<div
									style={ {
										marginBottom: '1rem',
										border: '1px solid #ddd',
										borderRadius: '4px',
										overflow: 'hidden',
									} }
								>
									{ formularios.map( ( form ) => (
										<div
											key={ form.id }
											style={ {
												display: 'flex',
												justifyContent: 'space-between',
												alignItems: 'center',
												padding: '0.75rem 1rem',
												borderBottom:
													'1px solid #f0f0f0',
												background: '#fafafa',
											} }
										>
											<div>
												<strong>{ form.nombre }</strong>
												<span
													style={ {
														marginLeft: '1rem',
														color: '#666',
														fontWeight: 'bold',
													} }
												>
													{ form.porcentaje }%
												</span>
											</div>
											<Button
												isDestructive
												isSmall
												onClick={ () =>
													handleRemoveForm( form.id )
												}
											>
												✕
											</Button>
										</div>
									) ) }
								</div>
							) }

							{ formularios.length > 0 && (
								<div
									style={ {
										marginTop: '0.5rem',
										fontSize: '0.9rem',
										fontWeight: 'bold',
										padding: '0.5rem',
										background:
											totalPercentage === 100
												? '#e8f5e9'
												: '#ffebee',
										color:
											totalPercentage === 100
												? '#2e7d32'
												: '#c62828',
										borderRadius: '4px',
									} }
								>
									{ __( 'Total:', 'eipsi-forms' ) }{ ' ' }
									{ totalPercentage }%{ ' ' }
									{ totalPercentage === 100 ? '✓' : '⚠️' }
								</div>
							) }

							{ formularios.length < 2 && (
								<Notice
									status="warning"
									isDismissible={ false }
								>
									{ __(
										'Necesitás al menos 2 formularios para generar shortcode/link',
										'eipsi-forms'
									) }
								</Notice>
							) }

							{ /* Asignaciones Manuales */ }
							<hr
								style={ {
									margin: '2rem 0',
									border: 'none',
									borderTop: '2px solid #e0e0e0',
								} }
							/>

							<h3>
								{ __(
									'Asignaciones Manuales (Override Ético)',
									'eipsi-forms'
								) }
							</h3>
							<p
								style={ {
									fontSize: '0.9rem',
									color: '#666',
									marginBottom: '1rem',
								} }
							>
								{ __(
									'Asigná participantes específicos a formularios concretos. Útil para casos especiales o balanceo manual.',
									'eipsi-forms'
								) }
							</p>

							<Flex gap={ 2 } style={ { marginBottom: '1rem' } }>
								<FlexItem style={ { flex: 1 } }>
									<TextControl
										type="email"
										placeholder={ __(
											'Email del participante',
											'eipsi-forms'
										) }
										value={ emailInput }
										onChange={ setEmailInput }
									/>
								</FlexItem>
								<FlexItem style={ { flex: 1 } }>
									<SelectControl
										value={ formForEmail }
										options={ [
											{
												label: __(
													'Seleccionar formulario…',
													'eipsi-forms'
												),
												value: '',
											},
											...availableForms.map( ( f ) => ( {
												label: f.label,
												value: f.id,
											} ) ),
										] }
										onChange={ setFormForEmail }
									/>
								</FlexItem>
								<FlexItem>
									<Button
										variant="primary"
										onClick={ handleAddManualAssignment }
										disabled={
											! emailInput || ! formForEmail
										}
									>
										{ __( '➕ Añadir', 'eipsi-forms' ) }
									</Button>
								</FlexItem>
							</Flex>

							{ manualAssignments.length > 0 && (
								<div
									style={ {
										marginBottom: '1rem',
										border: '1px solid #ffc107',
										borderRadius: '4px',
										overflow: 'hidden',
									} }
								>
									{ manualAssignments.map( ( assignment ) => (
										<div
											key={ assignment.email }
											style={ {
												display: 'flex',
												justifyContent: 'space-between',
												alignItems: 'center',
												padding: '0.75rem 1rem',
												borderBottom:
													'1px solid #fff8e1',
												background: '#fffbf0',
												borderLeft: '3px solid #ffc107',
											} }
										>
											<div>
												<code
													style={ {
														background: '#fff',
														padding: '2px 6px',
														borderRadius: '3px',
													} }
												>
													{ assignment.email }
												</code>
												<span
													style={ {
														marginLeft: '1rem',
														color: '#666',
													} }
												>
													→ { assignment.formName }
												</span>
											</div>
											<Button
												isDestructive
												isSmall
												onClick={ () =>
													handleRemoveManualAssignment(
														assignment.email
													)
												}
											>
												✕
											</Button>
										</div>
									) ) }
								</div>
							) }

							{ /* Generación de Shortcode/Link */ }
							{ formularios.length >= 2 && (
								<>
									<hr
										style={ {
											margin: '2rem 0',
											border: 'none',
											borderTop: '2px solid #e0e0e0',
										} }
									/>

									<Card
										style={ {
											background: '#f0f7ff',
											border: '2px solid #2196f3',
										} }
									>
										<CardBody>
											<h3
												style={ {
													marginTop: 0,
													color: '#1565c0',
												} }
											>
												{ __(
													'📋 Generación Automática',
													'eipsi-forms'
												) }
											</h3>

											{ showPreview && (
												<div
													style={ {
														padding: '1rem',
														background: 'white',
														border: '1px solid #ddd',
														borderRadius: '4px',
														marginBottom: '1rem',
														fontSize: '0.9rem',
													} }
												>
													<strong>
														{ __(
															'Vista Previa:',
															'eipsi-forms'
														) }
													</strong>
													<div>
														<strong>
															Aleatorización:
														</strong>{ ' ' }
														{ formularios
															.map(
																( f ) =>
																	`${ f.nombre } (${ f.porcentaje }%)`
															)
															.join( ' | ' ) }
													</div>
													<div>
														<strong>Método:</strong>{ ' ' }
														{ method === 'seeded'
															? 'Con seed reproducible'
															: 'Random puro' }
													</div>
													<div>
														<strong>
															Asignaciones
															manuales:
														</strong>{ ' ' }
														{
															manualAssignments.length
														}
													</div>
												</div>
											) }

											{ /* Shortcode */ }
											<div
												style={ {
													marginBottom: '1.5rem',
												} }
											>
												<h4
													style={ {
														marginBottom: '0.5rem',
													} }
												>
													{ __(
														'Shortcode (para insertar en posts/páginas)',
														'eipsi-forms'
													) }
												</h4>
												<code
													style={ {
														display: 'block',
														padding: '0.75rem',
														background: '#f5f5f5',
														borderRadius: '4px',
														marginBottom: '0.5rem',
														wordBreak: 'break-all',
														fontSize: '0.9rem',
														border: '1px solid #ddd',
													} }
												>
													[eipsi_randomization
													id=&quot;{ randomizationId }
													&quot;]
												</code>
												<Button
													variant={
														copiedShortcode
															? 'primary'
															: 'secondary'
													}
													onClick={
														handleCopyShortcode
													}
													icon={
														copiedShortcode
															? 'yes-alt'
															: 'clipboard'
													}
												>
													{ copiedShortcode
														? __(
																'✓ Copiado!',
																'eipsi-forms'
														  )
														: __(
																'📋 Copiar Shortcode',
																'eipsi-forms'
														  ) }
												</Button>
											</div>

											{ /* Link Directo */ }
											<div>
												<h4
													style={ {
														marginBottom: '0.5rem',
													} }
												>
													{ __(
														'Link Directo',
														'eipsi-forms'
													) }
												</h4>
												<code
													style={ {
														display: 'block',
														padding: '0.75rem',
														background: '#f5f5f5',
														borderRadius: '4px',
														marginBottom: '0.5rem',
														wordBreak: 'break-all',
														fontSize: '0.9rem',
														border: '1px solid #ddd',
													} }
												>
													{ window.location.origin }
													/?eipsi_rand=
													{ randomizationId }
												</code>
												<Button
													variant={
														copiedLink
															? 'primary'
															: 'secondary'
													}
													onClick={ handleCopyLink }
													icon={
														copiedLink
															? 'yes-alt'
															: 'admin-links'
													}
												>
													{ copiedLink
														? __(
																'✓ Copiado!',
																'eipsi-forms'
														  )
														: __(
																'🔗 Copiar Link',
																'eipsi-forms'
														  ) }
												</Button>
											</div>
										</CardBody>
									</Card>
								</>
							) }
						</>
					) }
				</CardBody>
			</Card>
		</div>
	);
}
