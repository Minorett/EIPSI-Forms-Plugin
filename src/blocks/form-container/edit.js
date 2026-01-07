import {
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
	InnerBlocks,
	MediaUpload,
	MediaUploadCheck,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	TextareaControl,
	ToggleControl,
	Button,
	SelectControl,
	Notice,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';
import { parse } from '@wordpress/blocks';
import { useDispatch, useSelect } from '@wordpress/data';
import {
	migrateToStyleConfig,
	serializeToCSSVariables,
} from '../../utils/styleTokens';
import FormStylePanel from '../../components/FormStylePanel';
import ConditionalLogicMap from '../../components/ConditionalLogicMap';

const COMPLETION_DEFAULTS = {
	title: '¡Gracias por completar el cuestionario!',
	message: 'Sus respuestas han sido registradas correctamente.',
	buttonLabel: 'Comenzar de nuevo',
};

export default function Edit( { attributes, setAttributes, clientId } ) {
	const {
		formId,
		submitButtonLabel,
		description,
		styleConfig,
		presetName,
		allowBackwardsNav,
		showProgressBar,
		studyStatus,
		useCustomCompletion,
		completionTitle,
		completionMessage,
		completionLogoId,
		completionLogoUrl,
		completionButtonLabel,
	} = attributes;

	const allowBackwardsNavEnabled =
		typeof allowBackwardsNav === 'boolean' ? allowBackwardsNav : true;

	const showProgressBarEnabled =
		typeof showProgressBar === 'boolean' ? showProgressBar : true;

	const normalizedStudyStatus = studyStatus === 'closed' ? 'closed' : 'open';
	const isStudyClosed = normalizedStudyStatus === 'closed';

	const customCompletionEnabled =
		typeof useCustomCompletion === 'boolean' ? useCustomCompletion : false;

	const [ isMapOpen, setIsMapOpen ] = useState( false );
	const [ selectedTemplate, setSelectedTemplate ] = useState( '' );
	const [ applyingTemplate, setApplyingTemplate ] = useState( false );

	const { replaceInnerBlocks } = useDispatch( blockEditorStore );

	// Migration: Convert legacy attributes to styleConfig on mount
	useEffect( () => {
		const updates = {};

		if ( ! styleConfig ) {
			updates.styleConfig = migrateToStyleConfig( attributes );
		}

		if ( ! presetName ) {
			updates.presetName = 'Clinical Blue';
		}

		if ( typeof useCustomCompletion !== 'boolean' ) {
			const hasCustomCompletionOverride =
				( completionTitle &&
					completionTitle !== COMPLETION_DEFAULTS.title ) ||
				( completionMessage &&
					completionMessage !== COMPLETION_DEFAULTS.message ) ||
				( completionButtonLabel &&
					completionButtonLabel !==
						COMPLETION_DEFAULTS.buttonLabel ) ||
				!! completionLogoUrl;

			updates.useCustomCompletion = hasCustomCompletionOverride;
		}

		if ( Object.keys( updates ).length ) {
			setAttributes( updates );
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps -- Migration runs only once on mount
	}, [] );

	// Get current style config (with fallback)
	const currentConfig = styleConfig || migrateToStyleConfig( attributes );

	// Generate CSS variables for editor preview
	const cssVars = serializeToCSSVariables( currentConfig );

	const blockProps = useBlockProps( {
		className: 'eipsi-form-container-editor',
		style: cssVars,
	} );

	const ALLOWED_BLOCKS = [
		'eipsi/form-page',
		'eipsi/consent-block',
		'eipsi/campo-texto',
		'eipsi/campo-textarea',
		'eipsi/campo-descripcion',
		'eipsi/campo-select',
		'eipsi/campo-radio',
		'eipsi/campo-multiple',
		'eipsi/campo-likert',
		'eipsi/vas-slider',
	];

	const innerBlocksProps = useInnerBlocksProps(
		{
			className: 'eipsi-form-inner-blocks',
		},
		{
			allowedBlocks: ALLOWED_BLOCKS,
			templateLock: false,
			renderAppender: InnerBlocks.ButtonBlockAppender,
		}
	);

	// Helper to update full styleConfig
	const updateStyleConfig = ( newConfig ) => {
		setAttributes( { styleConfig: newConfig } );
	};

	// Get inner blocks count
	const innerBlocks = useSelect(
		( select ) => {
			return select( blockEditorStore ).getBlocks( clientId );
		},
		[ clientId ]
	);

	const hasContent = innerBlocks && innerBlocks.length > 0;

	const templateData = window?.EIPSIDemoTemplates || {};
	const templates = templateData.templates || [];
	const strings = templateData.strings || {};

	const [ templateNotice, setTemplateNotice ] = useState( null );

	// Template options for SelectControl
	const templateOptions = [
		{
			label:
				strings.selectPlaceholder ||
				__( 'Elegí una plantilla', 'eipsi-forms' ),
			value: '',
		},
		...templates.map( ( t ) => ( {
			label: `${ t.icon || '' } ${ t.name }`.trim(),
			value: t.id,
		} ) ),
	];

	const selectedTemplateData = templates.find(
		( template ) => template.id === selectedTemplate
	);

	// Handler: Apply selected template
	const handleApplyTemplate = () => {
		if ( ! selectedTemplate ) {
			return;
		}

		// Confirm if container already has content
		if ( hasContent ) {
			// eslint-disable-next-line no-alert
			const confirmed = window.confirm(
				strings.confirmReplace ||
					__(
						'Esto reemplazará el contenido actual del formulario. ¿Continuar?',
						'eipsi-forms'
					)
			);

			if ( ! confirmed ) {
				return;
			}
		}

		setApplyingTemplate( true );
		setTemplateNotice( null );

		const template = selectedTemplateData;

		if ( ! template || ! template.content ) {
			setApplyingTemplate( false );
			setTemplateNotice( {
				status: 'error',
				message: __(
					'No pudimos cargar la plantilla seleccionada.',
					'eipsi-forms'
				),
			} );
			return;
		}

		try {
			// Parse the template content
			const parsedBlocks = parse( template.content );

			if (
				! parsedBlocks ||
				parsedBlocks.length === 0 ||
				parsedBlocks[ 0 ].name !== 'eipsi/form-container'
			) {
				throw new Error( 'Invalid template structure' );
			}

			const containerBlock = parsedBlocks[ 0 ];
			const newInnerBlocks = containerBlock.innerBlocks || [];
			const containerAttrs = containerBlock.attributes || {};

			const allowedAttrKeys = [
				'formId',
				'submitButtonLabel',
				'description',
				'presetName',
				'allowBackwardsNav',
				'showProgressBar',
				'studyStatus',
				'useCustomCompletion',
				'completionTitle',
				'completionMessage',
				'completionButtonLabel',
				'styleConfig',
			];

			const updatedAttrs = {};

			allowedAttrKeys.forEach( ( key ) => {
				if (
					Object.prototype.hasOwnProperty.call( containerAttrs, key )
				) {
					updatedAttrs[ key ] = containerAttrs[ key ];
				}
			} );

			if ( Object.keys( updatedAttrs ).length > 0 ) {
				setAttributes( updatedAttrs );
			}

			// Replace inner blocks
			replaceInnerBlocks( clientId, newInnerBlocks, false );

			// Reset selection
			setSelectedTemplate( '' );
			setTemplateNotice( {
				status: 'success',
				message:
					strings.success ||
					__(
						'Plantilla aplicada correctamente.',
						'eipsi-forms'
					),
			} );
		} catch ( error ) {
			// eslint-disable-next-line no-console
			console.error( 'Error applying template:', error );
			setTemplateNotice( {
				status: 'error',
				message: __(
					'No pudimos aplicar la plantilla. Intentá nuevamente.',
					'eipsi-forms'
				),
			} );
		} finally {
			setApplyingTemplate( false );
		}
	};

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Plantillas EIPSI', 'eipsi-forms' ) }
					initialOpen={ false }
				>
					{ templateNotice && (
						<Notice
							status={ templateNotice.status }
							onRemove={ () => setTemplateNotice( null ) }
							isDismissible={ true }
							style={ { marginBottom: '16px' } }
						>
							{ templateNotice.message }
						</Notice>
					) }

					{ templates.length === 0 && (
						<Notice status="info" isDismissible={ false }>
							{ strings.empty ||
								__(
									'Próximamente agregaremos más demos pensados para tu consultorio.',
									'eipsi-forms'
								) }
						</Notice>
					) }

					{ templates.length > 0 && (
						<>
							<p
								style={ {
									fontSize: '13px',
									color: '#475467',
									marginBottom: '12px',
								} }
							>
								{ __(
									'Estas plantillas son demos genéricos con bloques EIPSI reales. No son escalas clínicas oficiales.',
									'eipsi-forms'
								) }
							</p>

							<SelectControl
								label={
									strings.selectLabel ||
									__(
										'Plantillas EIPSI (demo)',
										'eipsi-forms'
									)
								}
								value={ selectedTemplate }
								options={ templateOptions }
								onChange={ ( value ) =>
									setSelectedTemplate( value )
								}
							/>

							{ selectedTemplateData && (
								<div
									style={ {
										marginTop: '8px',
										padding: '8px',
										background: '#f8f9fb',
										borderRadius: '4px',
										fontSize: '12px',
										color: '#646970',
									} }
								>
									{ selectedTemplateData.description }
								</div>
							) }

							{ selectedTemplate && (
								<Button
									variant="primary"
									onClick={ handleApplyTemplate }
									isBusy={ applyingTemplate }
									disabled={ applyingTemplate }
									style={ {
										marginTop: '12px',
										width: '100%',
									} }
								>
									{ strings.apply ||
										__(
											'Aplicar plantilla',
											'eipsi-forms'
										) }
								</Button>
							) }
						</>
					) }
				</PanelBody>

				<PanelBody
					title={ __( 'Form Settings', 'eipsi-forms' ) }
					initialOpen={ true }
				>
					<TextControl
						label={ __( 'Form ID/Slug', 'eipsi-forms' ) }
						value={ formId }
						onChange={ ( value ) =>
							setAttributes( { formId: value } )
						}
						help={ __(
							'Enter a unique identifier for the form (e.g., contact-form)',
							'eipsi-forms'
						) }
					/>
					<TextControl
						label={ __(
							'Submit Button Label',
							'eipsi-forms'
						) }
						value={ submitButtonLabel }
						onChange={ ( value ) =>
							setAttributes( { submitButtonLabel: value } )
						}
					/>
					<TextareaControl
						label={ __(
							'Description (Optional)',
							'eipsi-forms'
						) }
						value={ description }
						onChange={ ( value ) =>
							setAttributes( { description: value } )
						}
						help={ __(
							'Optional description text shown above the form',
							'eipsi-forms'
						) }
					/>

					<Notice
						status={ isStudyClosed ? 'warning' : 'success' }
						isDismissible={ false }
						style={ { marginTop: '12px' } }
					>
						{ isStudyClosed
							? __(
									'🔴 Cerrado: este estudio no acepta nuevas respuestas.',
									'eipsi-forms'
							  )
							: __(
									'🟢 Abierto: este estudio acepta respuestas normalmente.',
									'eipsi-forms'
							  ) }
					</Notice>

					<ToggleControl
						label={ __(
							'Estado del estudio',
							'eipsi-forms'
						) }
						checked={ isStudyClosed }
						onChange={ ( value ) =>
							setAttributes( {
								studyStatus: value ? 'closed' : 'open',
							} )
						}
						help={
							isStudyClosed
								? __(
										'Para volver a recolectar datos, cambialo a Abierto.',
										'eipsi-forms'
								  )
								: __(
										'Si lo cerrás, el formulario se oculta y muestra un aviso en el frontend.',
										'eipsi-forms'
								  )
						}
					/>
				</PanelBody>

				<PanelBody
					title={ __( 'Navigation Settings', 'eipsi-forms' ) }
					initialOpen={ false }
				>
					<ToggleControl
						label={ __(
							'Mostrar botón "Anterior"',
							'eipsi-forms'
						) }
						checked={ allowBackwardsNavEnabled }
						onChange={ ( value ) =>
							setAttributes( { allowBackwardsNav: !! value } )
						}
						help={ __(
							'Permite al paciente volver a la página anterior. Si está desactivado, el botón "Anterior" no aparecerá nunca.',
							'eipsi-forms'
						) }
					/>
					<ToggleControl
						label={ __(
							'Mostrar barra de progreso',
							'eipsi-forms'
						) }
						checked={ showProgressBarEnabled }
						onChange={ ( value ) =>
							setAttributes( { showProgressBar: !! value } )
						}
						help={ __(
							'Muestra "Página X de Y" en la parte inferior del formulario. Útil en formularios con múltiples páginas.',
							'eipsi-forms'
						) }
					/>
				</PanelBody>

				<PanelBody
					title={ __( 'Completion Page', 'eipsi-forms' ) }
					initialOpen={ false }
				>
					<ToggleControl
						label={ __(
							'Personalizar página de finalización',
							'eipsi-forms'
						) }
						checked={ customCompletionEnabled }
						onChange={ ( value ) =>
							setAttributes( { useCustomCompletion: !! value } )
						}
						help={ __(
							'Si está desactivado, se usará la configuración global de Finalización (Results & Experience → Finalización). Si está activado, podrás personalizar el mensaje de finalización solo para este formulario.',
							'eipsi-forms'
						) }
					/>

					{ ! customCompletionEnabled && (
						<p
							style={ {
								marginTop: '12px',
								fontSize: '13px',
								color: '#475467',
								background: '#f8f9fb',
								padding: '12px',
								borderRadius: '6px',
							} }
						>
							{ __(
								'Este formulario usará el mensaje global configurado en Results & Experience → Finalización.',
								'eipsi-forms'
							) }
						</p>
					) }

					{ customCompletionEnabled && (
						<>
							<TextControl
								label={ __(
									'Título de finalización',
									'eipsi-forms'
								) }
								value={ completionTitle }
								onChange={ ( value ) =>
									setAttributes( { completionTitle: value } )
								}
								help={ __(
									'Título que se muestra al completar el formulario',
									'eipsi-forms'
								) }
							/>
							<TextareaControl
								label={ __(
									'Mensaje de finalización',
									'eipsi-forms'
								) }
								value={ completionMessage }
								onChange={ ( value ) =>
									setAttributes( {
										completionMessage: value,
									} )
								}
								help={ __(
									'Mensaje que se muestra al completar el formulario',
									'eipsi-forms'
								) }
								rows={ 4 }
							/>
							<TextControl
								label={ __(
									'Texto del botón',
									'eipsi-forms'
								) }
								value={ completionButtonLabel }
								onChange={ ( value ) =>
									setAttributes( {
										completionButtonLabel: value,
									} )
								}
								help={ __(
									'Por ejemplo: "Comenzar de nuevo" o "Volver a empezar"',
									'eipsi-forms'
								) }
							/>
							<MediaUploadCheck>
								<div style={ { marginTop: '16px' } }>
									<p
										style={ {
											marginBottom: '8px',
											fontWeight: '500',
										} }
									>
										{ __(
											'Logo o imagen (opcional)',
											'eipsi-forms'
										) }
									</p>
									{ completionLogoUrl && (
										<div style={ { marginBottom: '12px' } }>
											<img
												src={ completionLogoUrl }
												alt={ __(
													'Logo del consultorio',
													'eipsi-forms'
												) }
												style={ {
													maxWidth: '200px',
													height: 'auto',
													borderRadius: '8px',
												} }
											/>
										</div>
									) }
									<MediaUpload
										onSelect={ ( media ) =>
											setAttributes( {
												completionLogoId: media.id,
												completionLogoUrl: media.url,
											} )
										}
										allowedTypes={ [ 'image' ] }
										value={ completionLogoId }
										render={ ( { open } ) => (
											<div>
												<Button
													variant="secondary"
													onClick={ open }
												>
													{ completionLogoUrl
														? __(
																'Cambiar imagen',
																'eipsi-forms'
														  )
														: __(
																'Seleccionar imagen',
																'eipsi-forms'
														  ) }
												</Button>
												{ completionLogoUrl && (
													<Button
														variant="tertiary"
														isDestructive
														onClick={ () =>
															setAttributes( {
																completionLogoId: 0,
																completionLogoUrl:
																	'',
															} )
														}
														style={ {
															marginLeft: '8px',
														} }
													>
														{ __(
															'Quitar',
															'eipsi-forms'
														) }
													</Button>
												) }
											</div>
										) }
									/>
									<p
										style={ {
											fontSize: '12px',
											color: '#757575',
											marginTop: '8px',
										} }
									>
										{ __(
											'Se mostrará en la parte superior de la página de finalización',
											'eipsi-forms'
										) }
									</p>
								</div>
							</MediaUploadCheck>
						</>
					) }
				</PanelBody>

				<PanelBody
					title={ __(
						'Mapa de lógica condicional',
						'eipsi-forms'
					) }
					initialOpen={ false }
				>
					<p
						style={ {
							fontSize: '13px',
							color: '#475467',
							marginBottom: '12px',
						} }
					>
						{ __(
							'Visualizá todas las reglas del formulario agrupadas por página. Solo lectura para revisar y explicar flujos clínicos.',
							'eipsi-forms'
						) }
					</p>
					<Button
						variant="secondary"
						onClick={ () => setIsMapOpen( true ) }
					>
						{ __(
							'Ver mapa de condiciones',
							'eipsi-forms'
						) }
					</Button>
				</PanelBody>

				<PanelBody
					title={ __(
						'Apariencia del formulario',
						'eipsi-forms'
					) }
					initialOpen={ false }
				>
					<FormStylePanel
						styleConfig={ currentConfig }
						setStyleConfig={ updateStyleConfig }
						presetName={ presetName || 'Clinical Blue' }
						setPresetName={ ( name ) =>
							setAttributes( { presetName: name } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			{ isMapOpen && (
				<ConditionalLogicMap
					isOpen={ isMapOpen }
					onClose={ () => setIsMapOpen( false ) }
					containerClientId={ clientId }
				/>
			) }

			<div { ...blockProps }>
				<div className="eipsi-form-container-preview">
					{ ! formId && (
						<div className="eipsi-form-placeholder">
							<div className="components-placeholder">
								<div className="components-placeholder__label">
									{ __(
										'EIPSI Form Container',
										'eipsi-forms'
									) }
								</div>
								<div className="components-placeholder__instructions">
									{ __(
										'Please enter a Form ID in the block settings to get started.',
										'eipsi-forms'
									) }
								</div>
							</div>
						</div>
					) }
					{ formId && (
						<div className="eipsi-form-preview-wrapper">
							<div className="form-header">
								<h3 className="form-title">
									{ formId
										.split( '-' )
										.map(
											( word ) =>
												word.charAt( 0 ).toUpperCase() +
												word.slice( 1 )
										)
										.join( ' ' ) }
								</h3>

								<div
									className="eipsi-study-status-indicator"
									style={ {
										marginTop: '6px',
										fontSize: '12px',
										fontWeight: 600,
										color: isStudyClosed
											? 'var(--eipsi-color-error, #d32f2f)'
											: 'var(--eipsi-color-success, #198754)',
									} }
								>
									{ isStudyClosed
										? `🔴 ${ __(
												'Cerrado',
												'eipsi-forms'
										  ) }`
										: `🟢 ${ __(
												'Abierto',
												'eipsi-forms'
										  ) }` }
								</div>
								{ description && (
									<p className="form-description">
										{ description }
									</p>
								) }
							</div>
							<div { ...innerBlocksProps } />
							<div className="form-footer">
								<button
									type="button"
									className="eipsi-submit-button"
									disabled
								>
									{ submitButtonLabel ||
										__( 'Submit', 'eipsi-forms' ) }
								</button>
							</div>
						</div>
					) }
				</div>
			</div>
		</>
	);
}
