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
	BaseControl,
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
import TimingTable from '../../components/TimingTable';

const COMPLETION_DEFAULTS = {
	title: '¡Gracias por completar el cuestionario!',
	message: 'Sus respuestas han sido registradas correctamente.',
	buttonLabel: 'Comenzar de nuevo',
};

const RANDOMIZATION_DEFAULT_CONFIG = {
	enabled: false,
	method: 'seeded',
	forms: [],
	probabilities: {},
	manualAssigns: [],
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
		// Aleatorización
		useRandomization,
		randomConfig: randomConfigAttr,
		// Analytics & Timing
		capturePageTiming,
		captureFieldTiming,
		showTimingAnalysis,
	} = attributes;

	// Blindaje: si por cualquier motivo randomConfig llega undefined/null (bloques viejos, etc.),
	// evitamos que el editor explote.
	const randomConfig =
		randomConfigAttr && typeof randomConfigAttr === 'object'
			? {
					...RANDOMIZATION_DEFAULT_CONFIG,
					...randomConfigAttr,
					forms: Array.isArray( randomConfigAttr.forms )
						? randomConfigAttr.forms
						: [],
					probabilities:
						randomConfigAttr.probabilities &&
						typeof randomConfigAttr.probabilities === 'object'
							? randomConfigAttr.probabilities
							: {},
					manualAssigns: Array.isArray(
						randomConfigAttr.manualAssigns
					)
						? randomConfigAttr.manualAssigns
						: [],
			  }
			: { ...RANDOMIZATION_DEFAULT_CONFIG };

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

	// === Estado para Aleatorización ===
	const [ availableForms, setAvailableForms ] = useState( [] );
	const [ loadingForms, setLoadingForms ] = useState( false );
	const [ linkCopied, setLinkCopied ] = useState( false );
	const [ manualEmail, setManualEmail ] = useState( '' );
	const [ manualFormId, setManualFormId ] = useState( '' );

	// Cargar formularios disponibles del CPT eipsi_form
	useEffect( () => {
		if ( useRandomization && availableForms.length === 0 ) {
			loadAvailableForms();
		}
	}, [ useRandomization, availableForms.length ] );

	const loadAvailableForms = async () => {
		setLoadingForms( true );
		try {
			const ajaxUrl =
				window?.eipsiEditorData?.ajaxurl || '/wp-admin/admin-ajax.php';
			const nonce =
				window?.eipsiEditorData?.nonce || window?.eipsiAdminNonce || '';

			const response = await fetch(
				ajaxUrl +
					'?action=eipsi_get_forms_list&nonce=' +
					encodeURIComponent( nonce ),
				{ credentials: 'same-origin' }
			);

			if ( ! response.ok ) {
				throw new Error( `HTTP ${ response.status }` );
			}

			const data = await response.json();
			if ( data?.success ) {
				setAvailableForms(
					Array.isArray( data.data ) ? data.data : []
				);
			}
		} catch ( error ) {
			// eslint-disable-next-line no-console
			console.error( 'Error cargando formularios:', error );
		}
		setLoadingForms( false );
	};

	// === Funciones de Aleatorización ===

	// Actualizar configuración de aleatorización
	const updateRandomConfig = ( key, value ) => {
		const newConfig = {
			...randomConfig,
			[ key ]: value,
		};
		setAttributes( { randomConfig: newConfig } );
	};

	// Añadir formulario a la lista
	const addFormToRandom = ( formIdToAdd ) => {
		if (
			! formIdToAdd ||
			randomConfig.forms.includes( parseInt( formIdToAdd ) )
		) {
			return;
		}
		const newForms = [ ...randomConfig.forms, parseInt( formIdToAdd ) ];
		const newProbs = { ...randomConfig.probabilities };

		// Asignar probabilidad inicial equitativa
		const initialProb = Math.floor( 100 / newForms.length );
		newForms.forEach( ( id, index ) => {
			newProbs[ id ] =
				index === newForms.length - 1
					? 100 - initialProb * ( newForms.length - 1 )
					: initialProb;
		} );

		setAttributes( {
			randomConfig: {
				...randomConfig,
				forms: newForms,
				probabilities: newProbs,
			},
		} );
	};

	// Eliminar formulario de la lista
	const removeFormFromRandom = ( formIdToRemove ) => {
		const newForms = randomConfig.forms.filter(
			( id ) => id !== formIdToRemove
		);
		const newProbs = { ...randomConfig.probabilities };
		delete newProbs[ formIdToRemove ];

		// Redistribuir probabilidades
		if ( newForms.length > 0 ) {
			const initialProb = Math.floor( 100 / newForms.length );
			newForms.forEach( ( id, index ) => {
				newProbs[ id ] =
					index === newForms.length - 1
						? 100 - initialProb * ( newForms.length - 1 )
						: initialProb;
			} );
		}

		setAttributes( {
			randomConfig: {
				...randomConfig,
				forms: newForms,
				probabilities: newProbs,
			},
		} );
	};

	// Actualizar probabilidad de un formulario
	const updateProbability = ( formIdToUpdate, newValue ) => {
		const value = parseInt( newValue );
		const newProbs = { ...randomConfig.probabilities };
		newProbs[ formIdToUpdate ] = value;

		// Ajustar otros formularios para mantener suma = 100
		const otherForms = randomConfig.forms.filter(
			( id ) => id !== formIdToUpdate
		);
		if ( otherForms.length > 0 ) {
			const remaining = 100 - value;
			const otherSum = otherForms.reduce(
				( sum, id ) => sum + ( newProbs[ id ] || 0 ),
				0
			);

			if ( otherSum > 0 ) {
				const adjustment = remaining / otherForms.length;
				otherForms.forEach( ( id, index ) => {
					newProbs[ id ] =
						index === otherForms.length - 1
							? remaining - adjustment * ( otherForms.length - 1 )
							: adjustment;
				} );
			} else {
				// Repartir equitativamente
				const each = remaining / otherForms.length;
				otherForms.forEach( ( id, index ) => {
					newProbs[ id ] =
						index === otherForms.length - 1
							? remaining - each * ( otherForms.length - 1 )
							: each;
				} );
			}
		}

		setAttributes( {
			randomConfig: {
				...randomConfig,
				probabilities: newProbs,
			},
		} );
	};

	// Añadir asignación manual
	const addManualAssign = () => {
		if ( ! manualEmail || ! manualFormId ) {
			return;
		}
		const newAssigns = [
			...randomConfig.manualAssigns,
			{
				email: manualEmail.toLowerCase().trim(),
				formId: parseInt( manualFormId ),
				timestamp: new Date().toISOString(),
			},
		];
		updateRandomConfig( 'manualAssigns', newAssigns );
		setManualEmail( '' );
		setManualFormId( '' );
	};

	// Eliminar asignación manual
	const removeManualAssign = ( index ) => {
		const newAssigns = randomConfig.manualAssigns.filter(
			( _, i ) => i !== index
		);
		updateRandomConfig( 'manualAssigns', newAssigns );
	};

	// Generar link con random - mejorada para shortcode público
	const generateRandomLink = async () => {
		// Verificar que hay configuración de aleatorización
		if ( ! randomConfig.enabled || randomConfig.forms.length < 2 ) {
			// eslint-disable-next-line no-alert
			window.alert(
				__(
					'Necesitás configurar al menos 2 formularios para generar el link de aleatorización.',
					'eipsi-forms'
				)
			);
			return;
		}

		try {
			const ajaxUrl =
				window?.eipsiEditorData?.ajaxurl || '/wp-admin/admin-ajax.php';
			const nonce =
				window?.eipsiEditorData?.nonce || window?.eipsiAdminNonce || '';

			// Obtener páginas disponibles con el shortcode
			const response = await fetch(
				`${ ajaxUrl }?action=eipsi_get_randomization_pages&nonce=${ encodeURIComponent(
					nonce
				) }`,
				{ credentials: 'same-origin' }
			);

			const data = await response.json();

			if ( data.success && data.data.length > 0 ) {
				// Usar la primera página disponible con el shortcode
				const selectedPage = data.data[ 0 ];
				const link = `${ selectedPage.link }?eipsi_random=true&study_id=${ formId }`;

				// eslint-disable-next-line no-undef
				navigator.clipboard.writeText( link ).then( () => {
					setLinkCopied( true );
					setTimeout( () => setLinkCopied( false ), 2000 );
				} );

				// eslint-disable-next-line no-alert
				window.alert(
					`${ __( 'Link generado:', 'eipsi-forms' ) }\n${ link }`
				);
			} else {
				// Fallback: generar link con URL actual
				const currentUrl = window.location.href.split( '?' )[ 0 ];
				const fallbackLink = `${ currentUrl }?eipsi_random=true&study_id=${ formId }`;

				// eslint-disable-next-line no-undef
				navigator.clipboard.writeText( fallbackLink ).then( () => {
					setLinkCopied( true );
					setTimeout( () => setLinkCopied( false ), 2000 );
				} );

				// eslint-disable-next-line no-alert
				window.alert(
					`${ __(
						'Link generado (página actual):',
						'eipsi-forms'
					) }\n${ fallbackLink }\n\n${ __(
						'⚠️ Recomendación: creá una página con el shortcode [eipsi_randomized_form] para un mejor funcionamiento.',
						'eipsi-forms'
					) }`
				);
			}
		} catch ( error ) {
			// Fallback en caso de error
			const currentUrl = window.location.href.split( '?' )[ 0 ];
			const fallbackLink = `${ currentUrl }?eipsi_random=true&study_id=${ formId }`;

			// eslint-disable-next-line no-undef
			navigator.clipboard.writeText( fallbackLink ).then( () => {
				setLinkCopied( true );
				setTimeout( () => setLinkCopied( false ), 2000 );
			} );

			// eslint-disable-next-line no-alert
			window.alert(
				`${ __(
					'Link generado (fallback):',
					'eipsi-forms'
				) }\n${ fallbackLink }`
			);
		}
	};

	// Obtener nombre de formulario por ID
	const getFormName = ( id ) => {
		const form = availableForms.find( ( f ) => f.id === parseInt( id ) );
		return form ? form.name || form.label : `Formulario ${ id }`;
	};

	// Calcular total de probabilidades
	const totalProbability = Object.values(
		randomConfig.probabilities || {}
	).reduce( ( sum, val ) => sum + ( parseInt( val ) || 0 ), 0 );

	// Formularios no seleccionados para dropdown
	const availableForSelect = availableForms.filter(
		( f ) => ! randomConfig.forms.includes( f.id )
	);

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
					__( 'Plantilla aplicada correctamente.', 'eipsi-forms' ),
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
						label={ __( 'Submit Button Label', 'eipsi-forms' ) }
						value={ submitButtonLabel }
						onChange={ ( value ) =>
							setAttributes( { submitButtonLabel: value } )
						}
					/>
					<TextareaControl
						label={ __( 'Description (Optional)', 'eipsi-forms' ) }
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
						label={ __( 'Estado del estudio', 'eipsi-forms' ) }
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
								label={ __( 'Texto del botón', 'eipsi-forms' ) }
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
					title={ __( 'Mapa de lógica condicional', 'eipsi-forms' ) }
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
						{ __( 'Ver mapa de condiciones', 'eipsi-forms' ) }
					</Button>
				</PanelBody>

				<PanelBody
					title={ __( 'Apariencia del formulario', 'eipsi-forms' ) }
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

				<PanelBody
					title={ __( 'Analytics & Timing', 'eipsi-forms' ) }
					initialOpen={ false }
				>
					<ToggleControl
						label={ __(
							'Capturar tiempos por página',
							'eipsi-forms'
						) }
						checked={ capturePageTiming }
						onChange={ ( value ) =>
							setAttributes( { capturePageTiming: value } )
						}
					/>
					<ToggleControl
						label={ __(
							'Capturar tiempos por campo',
							'eipsi-forms'
						) }
						checked={ captureFieldTiming }
						onChange={ ( value ) =>
							setAttributes( { captureFieldTiming: value } )
						}
					/>
					<hr />
					<ToggleControl
						label={ __( '⏱️ Hide Timing Analysis', 'eipsi-forms' ) }
						checked={ ! showTimingAnalysis }
						onChange={ ( value ) =>
							setAttributes( { showTimingAnalysis: ! value } )
						}
						help={ __(
							'Muestra u oculta la tabla de análisis de tiempos en esta vista previa.',
							'eipsi-forms'
						) }
					/>
				</PanelBody>

				{ /* === Panel de Aleatorización (Fase 1) === */ }
				<PanelBody
					title={ __( '🎲 Aleatorización', 'eipsi-forms' ) }
					initialOpen={ false }
				>
					<ToggleControl
						label={ __(
							'Activar aleatorización de asignación',
							'eipsi-forms'
						) }
						checked={ !! useRandomization }
						onChange={ ( value ) =>
							setAttributes( { useRandomization: !! value } )
						}
						help={ __(
							'Asigna formularios aleatoriamente a los participantes según las probabilidades configuradas.',
							'eipsi-forms'
						) }
					/>

					{ useRandomization && (
						<div className="eipsi-randomization-panel">
							{ /* Selector de formularios */ }
							<BaseControl
								id="eipsi-random-forms-select"
								label={ __(
									'Formularios para aleatorizar',
									'eipsi-forms'
								) }
							>
								<div
									style={ {
										display: 'flex',
										gap: '8px',
										marginBottom: '12px',
									} }
								>
									<SelectControl
										value=""
										options={ [
											{
												label: __(
													'Seleccionar formulario…',
													'eipsi-forms'
												),
												value: '',
											},
											...availableForSelect.map(
												( f ) => ( {
													label: f.name || f.label,
													value: String( f.id ),
												} )
											),
										] }
										onChange={ ( val ) => {
											if ( val ) {
												addFormToRandom( val );
											}
										} }
										disabled={
											availableForSelect.length === 0
										}
									/>
									<Button
										variant="secondary"
										onClick={ loadAvailableForms }
										isBusy={ loadingForms }
									>
										🔄
									</Button>
								</div>

								{ availableForms.length === 0 &&
									! loadingForms && (
										<Notice
											status="info"
											isDismissible={ false }
											style={ { marginBottom: '12px' } }
										>
											{ __(
												'No hay formularios disponibles en la Form Library. Creá al menos 2 formularios para usar la aleatorización.',
												'eipsi-forms'
											) }
										</Notice>
									) }

								{ randomConfig.forms.length < 2 &&
									availableForms.length > 0 && (
										<Notice
											status="warning"
											isDismissible={ false }
											style={ { marginBottom: '12px' } }
										>
											{ __(
												'Añadí al menos 2 formularios para activar la aleatorización.',
												'eipsi-forms'
											) }
										</Notice>
									) }

								{ /* Lista de formularios seleccionados con sliders */ }
								{ randomConfig.forms.length > 0 && (
									<div className="eipsi-forms-list">
										{ randomConfig.forms.map(
											( selectedFormId ) => (
												<div
													key={ selectedFormId }
													className="eipsi-form-row"
													style={ {
														marginBottom: '16px',
														padding: '12px',
														background: '#fff',
														borderRadius: '6px',
														border: '1px solid #e2e8f0',
													} }
												>
													<div
														style={ {
															display: 'flex',
															justifyContent:
																'space-between',
															alignItems:
																'center',
															marginBottom: '8px',
														} }
													>
														<strong>
															{ getFormName(
																selectedFormId
															) }
														</strong>
														<Button
															variant="tertiary"
															isDestructive
															onClick={ () =>
																removeFormFromRandom(
																	selectedFormId
																)
															}
															icon="no-alt"
														/>
													</div>
													<div
														style={ {
															display: 'flex',
															alignItems:
																'center',
															gap: '12px',
														} }
													>
														<input
															type="range"
															min="0"
															max="100"
															value={
																randomConfig
																	.probabilities[
																	selectedFormId
																] || 0
															}
															onChange={ ( e ) =>
																updateProbability(
																	selectedFormId,
																	e.target
																		.value
																)
															}
															style={ {
																flex: 1,
															} }
														/>
														<span
															style={ {
																minWidth:
																	'50px',
																textAlign:
																	'right',
																fontWeight: 600,
																color:
																	totalProbability !==
																	100
																		? '#d32f2f'
																		: '#198754',
															} }
														>
															{ randomConfig
																.probabilities[
																selectedFormId
															] || 0 }
															%
														</span>
													</div>
												</div>
											)
										) }

										{ /* Total de probabilidades */ }
										<div
											style={ {
												textAlign: 'center',
												padding: '8px',
												background:
													totalProbability === 100
														? '#dcfce7'
														: '#fef3c7',
												borderRadius: '6px',
												fontWeight: 600,
												color:
													totalProbability === 100
														? '#166534'
														: '#92400e',
											} }
										>
											{ __( 'Total:', 'eipsi-forms' ) }{ ' ' }
											{ totalProbability }%
										</div>
									</div>
								) }
							</BaseControl>

							{ /* Método de aleatorización */ }
							<SelectControl
								label={ __(
									'Método de aleatorización',
									'eipsi-forms'
								) }
								value={ randomConfig.method || 'seeded' }
								options={ [
									{
										label: __(
											'Simple (uniforme)',
											'eipsi-forms'
										),
										value: 'simple',
									},
									{
										label: __(
											'Con seed reproducible',
											'eipsi-forms'
										),
										value: 'seeded',
									},
								] }
								onChange={ ( value ) =>
									updateRandomConfig( 'method', value )
								}
								help={ __(
									'Simple: random puro. Seed: asigna UUID único para replicar en análisis.',
									'eipsi-forms'
								) }
							/>

							{ /* Asignaciones manuales */ }
							<BaseControl
								id="eipsi-random-manual-assigns"
								label={ __(
									'Asignaciones manuales (override ético)',
									'eipsi-forms'
								) }
								help={ __(
									'Estas asignaciones tienen prioridad sobre la aleatorización automática.',
									'eipsi-forms'
								) }
							>
								<div
									style={ {
										display: 'grid',
										gap: '8px',
										marginBottom: '12px',
									} }
								>
									<TextControl
										placeholder={ __(
											'Email del participante',
											'eipsi-forms'
										) }
										value={ manualEmail }
										onChange={ setManualEmail }
										type="email"
									/>
									<div
										style={ {
											display: 'flex',
											gap: '8px',
										} }
									>
										<SelectControl
											value={ manualFormId }
											options={ [
												{
													label: __(
														'Seleccionar formulario…',
														'eipsi-forms'
													),
													value: '',
												},
												...availableForms.map(
													( f ) => ( {
														label: f.name,
														value: String( f.id ),
													} )
												),
											] }
											onChange={ setManualFormId }
											style={ { flex: 1 } }
										/>
										<Button
											variant="primary"
											onClick={ addManualAssign }
											disabled={
												! manualEmail || ! manualFormId
											}
										>
											{ __( 'Añadir', 'eipsi-forms' ) }
										</Button>
									</div>
								</div>

								{ /* Tabla de asignaciones manuales */ }
								{ randomConfig.manualAssigns.length > 0 && (
									<table
										className="eipsi-manual-assigns-table"
										style={ {
											width: '100%',
											borderCollapse: 'collapse',
											fontSize: '13px',
										} }
									>
										<thead>
											<tr>
												<th
													style={ {
														padding: '8px',
														background: '#f1f5f9',
														textAlign: 'left',
													} }
												>
													{ __(
														'Email',
														'eipsi-forms'
													) }
												</th>
												<th
													style={ {
														padding: '8px',
														background: '#f1f5f9',
														textAlign: 'left',
													} }
												>
													{ __(
														'Formulario',
														'eipsi-forms'
													) }
												</th>
												<th
													style={ {
														padding: '8px',
														background: '#f1f5f9',
														width: '40px',
													} }
												>
													{  }
												</th>
											</tr>
										</thead>
										<tbody>
											{ randomConfig.manualAssigns.map(
												( assign, index ) => (
													<tr key={ index }>
														<td
															style={ {
																padding: '8px',
															} }
														>
															{ assign.email }
														</td>
														<td
															style={ {
																padding: '8px',
															} }
														>
															{ getFormName(
																assign.formId
															) }
														</td>
														<td
															style={ {
																padding: '8px',
															} }
														>
															<Button
																variant="tertiary"
																isDestructive
																onClick={ () =>
																	removeManualAssign(
																		index
																	)
																}
																icon="no-alt"
															/>
														</td>
													</tr>
												)
											) }
										</tbody>
									</table>
								) }
							</BaseControl>

							{ /* Botón generar link */ }
							<Button
								variant="secondary"
								onClick={ generateRandomLink }
								style={ { width: '100%', marginTop: '16px' } }
							>
								{ linkCopied
									? '✓ ' + __( 'Link copiado', 'eipsi-forms' )
									: '🔗 ' +
									  __(
											'Generar link con random',
											'eipsi-forms'
									  ) }
							</Button>

							{ /* Vista previa de configuración */ }
							{ randomConfig.forms.length >= 2 && (
								<div
									className="eipsi-random-preview"
									style={ {
										marginTop: '16px',
										padding: '12px',
										background: '#f8f9fb',
										borderRadius: '6px',
										fontSize: '12px',
									} }
								>
									<strong>
										{ __( 'Vista previa:', 'eipsi-forms' ) }
									</strong>
									<br />
									{ __(
										'Aleatorización activa:',
										'eipsi-forms'
									) }{ ' ' }
									{ randomConfig.forms
										.map(
											( id ) =>
												`${ getFormName( id ) } (${
													randomConfig.probabilities[
														id
													] || 0
												}%)`
										)
										.join( ' | ' ) }
									<br />
									{ __( 'Método:', 'eipsi-forms' ) }{ ' ' }
									{ randomConfig.method === 'seeded'
										? __(
												'Con seed reproducible',
												'eipsi-forms'
										  )
										: __(
												'Simple (uniforme)',
												'eipsi-forms'
										  ) }
									<br />
									{ __(
										'Overrides manuales:',
										'eipsi-forms'
									) }{ ' ' }
									{ randomConfig.manualAssigns.length }
								</div>
							) }
						</div>
					) }
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

				<TimingTable
					showTimingAnalysis={ showTimingAnalysis }
					totalTime="12.5"
					pages={ [
						{
							name: 'Página 1',
							duration: '5.2 s',
							timestamp: '14:20:05',
						},
						{
							name: 'Página 2',
							duration: '7.3 s',
							timestamp: '14:20:12',
						},
					] }
				/>
			</div>
		</>
	);
}
