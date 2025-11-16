import {
	PanelBody,
	ToggleControl,
	SelectControl,
	TextControl,
	Button,
	Dashicon,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';
import './ConditionalLogicControl.css';

const ConditionalLogicControl = ( {
	attributes,
	setAttributes,
	options = [],
	clientId,
	mode = 'discrete',
} ) => {
	const { conditionalLogic } = attributes;
	const [ validationErrors, setValidationErrors ] = useState( {} );

	const normalizedLogic = normalizeConditionalLogic( conditionalLogic );
	const isNumericMode = mode === 'numeric';

	const { pages, hasPages } = useSelect(
		( select ) => {
			const { getSelectedBlock, getBlockParentsByBlockName, getBlock } =
				select( 'core/block-editor' );

			try {
				const selectedBlock = clientId
					? getBlock( clientId )
					: getSelectedBlock();

				if ( ! selectedBlock ) {
					return { pages: [], hasPages: false };
				}

				const parentIds = getBlockParentsByBlockName(
					selectedBlock.clientId,
					'vas-dinamico/form-container'
				);

				if ( parentIds.length === 0 ) {
					return { pages: [], hasPages: false };
				}

				const formContainerId = parentIds[ parentIds.length - 1 ];
				const formContainer = getBlock( formContainerId );

				if ( ! formContainer || ! formContainer.innerBlocks ) {
					return { pages: [], hasPages: false };
				}

				const pageBlocks = formContainer.innerBlocks.filter(
					( block ) => block.name === 'vas-dinamico/form-page'
				);

				const pagesData = pageBlocks.map( ( page, index ) => ( {
					index: index + 1,
					title: page.attributes.title || '',
					clientId: page.clientId,
				} ) );

				return {
					pages: pagesData,
					hasPages: pagesData.length > 0,
				};
			} catch ( error ) {
				return { pages: [], hasPages: false };
			}
		},
		[ clientId ]
	);

	useEffect( () => {
		if ( normalizedLogic.enabled && normalizedLogic.rules.length > 0 ) {
			validateRules( normalizedLogic.rules );
		}
		// validateRules is a stable function that doesn't need to be in dependencies
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ normalizedLogic.enabled, normalizedLogic.rules, options, pages ] );

	const validateRules = ( rules ) => {
		const errors = {};
		const usedValues = new Set();

		rules.forEach( ( rule, index ) => {
			if ( isNumericMode ) {
				if ( ! rule.operator ) {
					errors[ index ] = __(
						'Selecciona un operador',
						'vas-dinamico-forms'
					);
				}

				if (
					rule.threshold === undefined ||
					rule.threshold === null ||
					rule.threshold === ''
				) {
					errors[ index ] = __(
						'Introduce un valor numérico',
						'vas-dinamico-forms'
					);
				}
			} else {
				if ( usedValues.has( rule.matchValue ) ) {
					errors[ index ] = __(
						'Este valor ya está siendo usado en otra regla',
						'vas-dinamico-forms'
					);
				} else if ( rule.matchValue ) {
					usedValues.add( rule.matchValue );
				}

				if ( ! rule.matchValue ) {
					errors[ index ] = __(
						'Selecciona un valor para esta regla',
						'vas-dinamico-forms'
					);
				}
			}

			if (
				rule.action === 'goToPage' &&
				( ! rule.targetPage || rule.targetPage < 1 )
			) {
				errors[ index ] = __(
					'Selecciona una página válida',
					'vas-dinamico-forms'
				);
			}
		} );

		setValidationErrors( errors );
	};

	const toggleConditionalLogic = ( enabled ) => {
		if ( enabled ) {
			setAttributes( {
				conditionalLogic: {
					enabled: true,
					rules: [],
					defaultAction: 'nextPage',
				},
			} );
		} else {
			setAttributes( { conditionalLogic: null } );
			setValidationErrors( {} );
		}
	};

	const addRule = () => {
		const newRule = isNumericMode
			? {
					id: `rule-${ Date.now() }`,
					operator: '>=',
					threshold: 0,
					action: 'goToPage',
					targetPage: pages.length > 0 ? pages[ 0 ].index : 1,
			  }
			: {
					id: `rule-${ Date.now() }`,
					matchValue: '',
					action: 'goToPage',
					targetPage: pages.length > 0 ? pages[ 0 ].index : 1,
			  };

		const newRules = [ ...normalizedLogic.rules, newRule ];

		setAttributes( {
			conditionalLogic: {
				...normalizedLogic,
				rules: newRules,
			},
		} );
	};

	const updateRule = ( index, field, value ) => {
		const newRules = [ ...normalizedLogic.rules ];
		newRules[ index ] = {
			...newRules[ index ],
			[ field ]: value,
		};

		setAttributes( {
			conditionalLogic: {
				...normalizedLogic,
				rules: newRules,
			},
		} );
	};

	const removeRule = ( index ) => {
		const newRules = normalizedLogic.rules.filter(
			( _, i ) => i !== index
		);

		if ( newRules.length === 0 ) {
			setAttributes( { conditionalLogic: null } );
			setValidationErrors( {} );
		} else {
			setAttributes( {
				conditionalLogic: {
					...normalizedLogic,
					rules: newRules,
				},
			} );
		}
	};

	const updateDefaultAction = ( action, targetPage = null ) => {
		const updatedLogic = {
			...normalizedLogic,
			defaultAction: action,
		};

		if ( action === 'goToPage' && targetPage ) {
			updatedLogic.defaultTargetPage = parseInt( targetPage );
		} else {
			delete updatedLogic.defaultTargetPage;
		}

		setAttributes( {
			conditionalLogic: updatedLogic,
		} );
	};

	const getPageOptions = () => {
		const pageOptions = [];

		if ( pages.length === 0 ) {
			return [
				{
					label: __(
						'No hay páginas disponibles',
						'vas-dinamico-forms'
					),
					value: '',
					disabled: true,
				},
			];
		}

		pages.forEach( ( page ) => {
			const label = page.title
				? `${ __( 'Página', 'vas-dinamico-forms' ) } ${
						page.index
				  } – ${ page.title }`
				: `${ __( 'Página', 'vas-dinamico-forms' ) } ${ page.index }`;

			pageOptions.push( {
				label,
				value: page.index.toString(),
			} );
		} );

		return pageOptions;
	};

	const getActionOptions = () => {
		const actionOptions = [
			{
				label: __( 'Siguiente página', 'vas-dinamico-forms' ),
				value: 'nextPage',
			},
			{
				label: __( 'Finalizar formulario', 'vas-dinamico-forms' ),
				value: 'submit',
			},
		];

		if ( pages.length > 0 ) {
			actionOptions.splice( 1, 0, {
				label: __( 'Ir a página específica…', 'vas-dinamico-forms' ),
				value: 'goToPage',
			} );
		}

		return actionOptions;
	};

	const getOptionOptions = () => {
		if ( options.length === 0 ) {
			return [
				{
					label: __(
						'No hay opciones disponibles',
						'vas-dinamico-forms'
					),
					value: '',
					disabled: true,
				},
			];
		}

		return [
			{
				label: __( 'Selecciona un valor…', 'vas-dinamico-forms' ),
				value: '',
			},
			...options.map( ( option ) => ( {
				label: option,
				value: option,
			} ) ),
		];
	};

	const getOperatorOptions = () => {
		return [
			{
				label: __( 'Mayor o igual (≥)', 'vas-dinamico-forms' ),
				value: '>=',
			},
			{
				label: __( 'Menor o igual (≤)', 'vas-dinamico-forms' ),
				value: '<=',
			},
			{
				label: __( 'Mayor que (>)', 'vas-dinamico-forms' ),
				value: '>',
			},
			{
				label: __( 'Menor que (<)', 'vas-dinamico-forms' ),
				value: '<',
			},
			{
				label: __( 'Igual a (=)', 'vas-dinamico-forms' ),
				value: '==',
			},
		];
	};

	const hasRequiredData = isNumericMode
		? hasPages
		: options.length > 0 && hasPages;

	return (
		<PanelBody
			title={ __( 'Lógica Condicional', 'vas-dinamico-forms' ) }
			initialOpen={ false }
		>
			<div className="conditional-logic-toggle-wrapper">
				<ToggleControl
					label={ __(
						'Habilitar lógica condicional',
						'vas-dinamico-forms'
					) }
					checked={ normalizedLogic.enabled || false }
					onChange={ toggleConditionalLogic }
					help={ __(
						'Permite configurar el comportamiento del formulario según las respuestas del participante.',
						'vas-dinamico-forms'
					) }
				/>
			</div>

			{ normalizedLogic.enabled && (
				<div className="conditional-logic-panel">
					{ ! hasPages && (
						<div className="conditional-logic-warning">
							<p>
								{ __(
									'Para configurar la lógica condicional, primero debes agregar páginas al formulario.',
									'vas-dinamico-forms'
								) }
							</p>
						</div>
					) }

					{ ! isNumericMode && options.length === 0 && (
						<div className="conditional-logic-warning">
							<p>
								{ __(
									'Para configurar la lógica condicional, primero debes agregar opciones a este campo.',
									'vas-dinamico-forms'
								) }
							</p>
						</div>
					) }

					{ hasRequiredData && normalizedLogic.rules.length === 0 && (
						<div className="conditional-logic-empty-state">
							<Dashicon icon="randomize" />
							<p>
								{ __(
									'No hay reglas configuradas. Las reglas permiten redirigir al participante a diferentes páginas según su respuesta.',
									'vas-dinamico-forms'
								) }
							</p>
						</div>
					) }

					{ normalizedLogic.rules.map( ( rule, index ) => (
						<div
							key={ rule.id || index }
							className="conditional-logic-rule"
						>
							<div className="conditional-logic-rule-header">
								<h4>
									{ __( 'Regla', 'vas-dinamico-forms' ) }{ ' ' }
									{ index + 1 }
								</h4>
							</div>

							{ isNumericMode ? (
								<>
									<SelectControl
										label={ __(
											'Cuando el valor del slider sea',
											'vas-dinamico-forms'
										) }
										value={ rule.operator || '>=' }
										options={ getOperatorOptions() }
										onChange={ ( value ) =>
											updateRule(
												index,
												'operator',
												value
											)
										}
									/>

									<TextControl
										label={ __(
											'Valor umbral',
											'vas-dinamico-forms'
										) }
										type="number"
										value={
											rule.threshold !== undefined
												? rule.threshold
												: ''
										}
										onChange={ ( value ) =>
											updateRule(
												index,
												'threshold',
												value !== ''
													? parseFloat( value )
													: ''
											)
										}
										help={ __(
											'El valor con el que se comparará la respuesta del slider',
											'vas-dinamico-forms'
										) }
									/>
								</>
							) : (
								<SelectControl
									label={ __(
										'Cuando el participante seleccione',
										'vas-dinamico-forms'
									) }
									value={ rule.matchValue || '' }
									options={ getOptionOptions() }
									onChange={ ( value ) =>
										updateRule( index, 'matchValue', value )
									}
								/>
							) }

							<SelectControl
								label={ __( 'Entonces', 'vas-dinamico-forms' ) }
								value={
									rule.action === 'goToPage'
										? 'goToPage'
										: rule.action
								}
								options={ getActionOptions() }
								onChange={ ( action ) => {
									if ( action === 'nextPage' ) {
										updateRule(
											index,
											'action',
											'nextPage'
										);
										updateRule( index, 'targetPage', null );
									} else if ( action === 'submit' ) {
										updateRule( index, 'action', 'submit' );
										updateRule( index, 'targetPage', null );
									} else if ( action === 'goToPage' ) {
										updateRule(
											index,
											'action',
											'goToPage'
										);
										updateRule(
											index,
											'targetPage',
											pages[ 0 ]?.index || 1
										);
									}
								} }
							/>

							{ rule.action === 'goToPage' && (
								<SelectControl
									label={ __(
										'Ir a la página',
										'vas-dinamico-forms'
									) }
									value={
										rule.targetPage
											? rule.targetPage.toString()
											: ''
									}
									options={ getPageOptions() }
									onChange={ ( value ) =>
										updateRule(
											index,
											'targetPage',
											parseInt( value )
										)
									}
								/>
							) }

							{ validationErrors[ index ] && (
								<div className="conditional-logic-validation-error">
									<Dashicon icon="warning" />
									<span>{ validationErrors[ index ] }</span>
								</div>
							) }

							<div className="conditional-logic-rule-actions">
								<Button
									isDestructive
									isSmall
									onClick={ () => removeRule( index ) }
								>
									{ __(
										'Eliminar regla',
										'vas-dinamico-forms'
									) }
								</Button>
							</div>
						</div>
					) ) }

					{ hasRequiredData && normalizedLogic.rules.length > 0 && (
						<div className="conditional-logic-default-action">
							<h4>
								{ __(
									'Acción predeterminada',
									'vas-dinamico-forms'
								) }
							</h4>
							<p
								style={ {
									fontSize: '12px',
									color: '#64748b',
									marginBottom: '12px',
								} }
							>
								{ __(
									'Define qué sucede cuando el participante selecciona un valor sin regla configurada.',
									'vas-dinamico-forms'
								) }
							</p>
							<SelectControl
								label={ __(
									'Para otros valores',
									'vas-dinamico-forms'
								) }
								value={
									normalizedLogic.defaultAction === 'goToPage'
										? 'goToPage'
										: normalizedLogic.defaultAction ||
										  'nextPage'
								}
								options={ getActionOptions() }
								onChange={ ( action ) => {
									if ( action === 'goToPage' ) {
										updateDefaultAction(
											action,
											pages[ 0 ]?.index || 1
										);
									} else {
										updateDefaultAction( action );
									}
								} }
							/>

							{ normalizedLogic.defaultAction === 'goToPage' && (
								<SelectControl
									label={ __(
										'Ir a la página',
										'vas-dinamico-forms'
									) }
									value={
										normalizedLogic.defaultTargetPage
											? normalizedLogic.defaultTargetPage.toString()
											: pages[ 0 ]?.index.toString() ||
											  '1'
									}
									options={ getPageOptions() }
									onChange={ ( value ) =>
										updateDefaultAction( 'goToPage', value )
									}
								/>
							) }
						</div>
					) }

					<Button
						isPrimary
						isSmall
						onClick={ addRule }
						disabled={ ! hasRequiredData }
						className="conditional-logic-add-rule-button"
					>
						{ __( '+ Agregar regla', 'vas-dinamico-forms' ) }
					</Button>

					{ hasRequiredData && (
						<div
							className="conditional-logic-info"
							style={ { marginTop: '16px' } }
						>
							<p>
								{ __(
									'Las reglas se evalúan en orden. La primera regla que coincida determinará la navegación del formulario.',
									'vas-dinamico-forms'
								) }
							</p>
						</div>
					) }
				</div>
			) }
		</PanelBody>
	);
};

function normalizeConditionalLogic( conditionalLogic ) {
	if ( ! conditionalLogic ) {
		return { enabled: false, rules: [], defaultAction: 'nextPage' };
	}

	if ( Array.isArray( conditionalLogic ) ) {
		return {
			enabled: true,
			rules: conditionalLogic.map( ( rule, index ) => ( {
				id: rule.id || `rule-legacy-${ index }`,
				matchValue: rule.value || rule.matchValue || '',
				action: rule.action || 'goToPage',
				targetPage: rule.targetPage || null,
			} ) ),
			defaultAction: 'nextPage',
		};
	}

	if ( typeof conditionalLogic === 'object' ) {
		return {
			enabled: conditionalLogic.enabled !== false,
			rules: ( conditionalLogic.rules || [] ).map( ( rule, index ) => ( {
				id: rule.id || `rule-${ index }`,
				matchValue: rule.value || rule.matchValue || '',
				action: rule.action || 'goToPage',
				targetPage: rule.targetPage || null,
			} ) ),
			defaultAction: conditionalLogic.defaultAction || 'nextPage',
			defaultTargetPage: conditionalLogic.defaultTargetPage || null,
		};
	}

	return { enabled: false, rules: [], defaultAction: 'nextPage' };
}

export default ConditionalLogicControl;
