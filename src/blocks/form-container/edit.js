import {
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
	InnerBlocks,
} from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	TextareaControl,
	ToggleControl,
	Button,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';
import {
	migrateToStyleConfig,
	serializeToCSSVariables,
} from '../../utils/styleTokens';
import FormStylePanel from '../../components/FormStylePanel';
import ConditionalLogicMap from '../../components/ConditionalLogicMap';

export default function Edit( { attributes, setAttributes, clientId } ) {
	const {
		formId,
		submitButtonLabel,
		description,
		styleConfig,
		presetName,
		allowBackwardsNav,
		showProgressBar,
	} = attributes;

	const allowBackwardsNavEnabled =
		typeof allowBackwardsNav === 'boolean' ? allowBackwardsNav : true;

	const showProgressBarEnabled =
		typeof showProgressBar === 'boolean' ? showProgressBar : true;

	const [ isMapOpen, setIsMapOpen ] = useState( false );

	// Migration: Convert legacy attributes to styleConfig on mount
	useEffect( () => {
		const updates = {};

		if ( ! styleConfig ) {
			updates.styleConfig = migrateToStyleConfig( attributes );
		}

		if ( ! presetName ) {
			updates.presetName = 'Clinical Blue';
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
		'vas-dinamico/form-page',
		'vas-dinamico/campo-texto',
		'vas-dinamico/campo-textarea',
		'vas-dinamico/campo-descripcion',
		'vas-dinamico/campo-select',
		'vas-dinamico/campo-radio',
		'vas-dinamico/campo-multiple',
		'vas-dinamico/campo-likert',
		'vas-dinamico/vas-slider',
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

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Form Settings', 'vas-dinamico-forms' ) }
					initialOpen={ true }
				>
					<TextControl
						label={ __( 'Form ID/Slug', 'vas-dinamico-forms' ) }
						value={ formId }
						onChange={ ( value ) =>
							setAttributes( { formId: value } )
						}
						help={ __(
							'Enter a unique identifier for the form (e.g., contact-form)',
							'vas-dinamico-forms'
						) }
					/>
					<TextControl
						label={ __(
							'Submit Button Label',
							'vas-dinamico-forms'
						) }
						value={ submitButtonLabel }
						onChange={ ( value ) =>
							setAttributes( { submitButtonLabel: value } )
						}
					/>
					<TextareaControl
						label={ __(
							'Description (Optional)',
							'vas-dinamico-forms'
						) }
						value={ description }
						onChange={ ( value ) =>
							setAttributes( { description: value } )
						}
						help={ __(
							'Optional description text shown above the form',
							'vas-dinamico-forms'
						) }
					/>
				</PanelBody>

				<PanelBody
					title={ __( 'Navigation Settings', 'vas-dinamico-forms' ) }
					initialOpen={ false }
				>
					<ToggleControl
						label={ __(
							'Mostrar botón "Anterior"',
							'vas-dinamico-forms'
						) }
						checked={ allowBackwardsNavEnabled }
						onChange={ ( value ) =>
							setAttributes( { allowBackwardsNav: !! value } )
						}
						help={ __(
							'Permite al paciente volver a la página anterior. Si está desactivado, el botón "Anterior" no aparecerá nunca.',
							'vas-dinamico-forms'
						) }
					/>
					<ToggleControl
						label={ __(
							'Mostrar barra de progreso',
							'vas-dinamico-forms'
						) }
						checked={ showProgressBarEnabled }
						onChange={ ( value ) =>
							setAttributes( { showProgressBar: !! value } )
						}
						help={ __(
							'Muestra "Página X de Y" en la parte inferior del formulario. Útil en formularios con múltiples páginas.',
							'vas-dinamico-forms'
						) }
					/>
				</PanelBody>

				<FormStylePanel
					styleConfig={ currentConfig }
					setStyleConfig={ updateStyleConfig }
					presetName={ presetName || 'Clinical Blue' }
					setPresetName={ ( name ) =>
						setAttributes( { presetName: name } )
					}
				/>

				<PanelBody
					title={ __(
						'Mapa de lógica condicional',
						'vas-dinamico-forms'
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
							'vas-dinamico-forms'
						) }
					</p>
					<Button
						variant="secondary"
						onClick={ () => setIsMapOpen( true ) }
					>
						{ __(
							'Ver mapa de condiciones',
							'vas-dinamico-forms'
						) }
					</Button>
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
										'vas-dinamico-forms'
									) }
								</div>
								<div className="components-placeholder__instructions">
									{ __(
										'Please enter a Form ID in the block settings to get started.',
										'vas-dinamico-forms'
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
										__( 'Submit', 'vas-dinamico-forms' ) }
								</button>
							</div>
						</div>
					) }
				</div>
			</div>
		</>
	);
}
