import {
	PanelBody,
	ToggleControl,
	SelectControl,
	Button,
	Dashicon,
	TextControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { useCallback, useEffect, useMemo, useState } from '@wordpress/element';
import { parseOptions } from '../utils/optionParser';
import './ConditionalLogicControl.css';

const MAX_CONDITIONS = 3;

const LOGICAL_OPERATOR_OPTIONS = [
	{ label: __( 'Y (AND)', 'vas-dinamico-forms' ), value: 'AND' },
	{ label: __( 'O (OR)', 'vas-dinamico-forms' ), value: 'OR' },
];

const NUMERIC_OPERATOR_OPTIONS = [
	{ label: __( 'Mayor o igual (≥)', 'vas-dinamico-forms' ), value: '>=' },
	{ label: __( 'Menor o igual (≤)', 'vas-dinamico-forms' ), value: '<=' },
	{ label: __( 'Mayor que (>)', 'vas-dinamico-forms' ), value: '>' },
	{ label: __( 'Menor que (<)', 'vas-dinamico-forms' ), value: '<' },
	{ label: __( 'Igual a (=)', 'vas-dinamico-forms' ), value: '==' },
];

const SUPPORTED_FIELD_BLOCKS = {
	'vas-dinamico/campo-radio': {
		type: 'discrete',
		getOptions: ( attributes ) => parseOptions( attributes.options ),
	},
	'vas-dinamico/campo-select': {
		type: 'discrete',
		getOptions: ( attributes ) => parseOptions( attributes.options ),
	},
	'vas-dinamico/campo-multiple': {
		type: 'discrete',
		getOptions: ( attributes ) => parseOptions( attributes.options ),
	},
	'vas-dinamico/campo-likert': {
		type: 'discrete',
		getOptions: ( attributes ) => buildLikertOptions( attributes ),
	},
	'vas-dinamico/vas-slider': {
		type: 'numeric',
		getRange: ( attributes ) => buildSliderRange( attributes ),
	},
};

const ConditionalLogicControl = ( {
	attributes,
	setAttributes,
	clientId,
	mode = 'discrete',
} ) => {
	const { conditionalLogic } = attributes;
	const [ validationErrors, setValidationErrors ] = useState( {} );

	const defaultFieldId = getFieldIdentifier( attributes );
	const defaultFieldType = mode === 'numeric' ? 'numeric' : 'discrete';

	const normalizedLogic = useMemo( () => {
		return normalizeConditionalLogic(
			conditionalLogic,
			defaultFieldId,
			defaultFieldType
		);
	}, [ conditionalLogic, defaultFieldId, defaultFieldType ] );

	const { pages, hasPages, availableFields } = useSelect(
		( select ) => {
			const { getSelectedBlock, getBlockParentsByBlockName, getBlock } =
				select( 'core/block-editor' );

			const selectedBlock = clientId
				? getBlock( clientId )
				: getSelectedBlock();

			if ( ! selectedBlock ) {
				return {
					pages: [],
					hasPages: false,
					availableFields: [],
				};
			}

			const formParentIds = getBlockParentsByBlockName(
				selectedBlock.clientId,
				'vas-dinamico/form-container'
			);

			if ( formParentIds.length === 0 ) {
				return {
					pages: [],
					hasPages: false,
					availableFields: [],
				};
			}

			const formContainerId = formParentIds[ formParentIds.length - 1 ];
			const formContainer = getBlock( formContainerId );

			if ( ! formContainer || ! formContainer.innerBlocks ) {
				return {
					pages: [],
					hasPages: false,
					availableFields: [],
				};
			}

			const pageBlocks = formContainer.innerBlocks.filter(
				( block ) => block.name === 'vas-dinamico/form-page'
			);

			const pagesData = pageBlocks.map( ( page, index ) => ( {
				index: index + 1,
				title: page.attributes.title || '',
				clientId: page.clientId,
			} ) );

			const pageParents = getBlockParentsByBlockName(
				selectedBlock.clientId,
				'vas-dinamico/form-page'
			);
			const currentPageClientId =
				pageParents.length > 0
					? pageParents[ pageParents.length - 1 ]
					: null;
			const currentPageBlock = currentPageClientId
				? getBlock( currentPageClientId )
				: null;

			return {
				pages: pagesData,
				hasPages: pagesData.length > 0,
				availableFields: collectConditionableFields( currentPageBlock ),
			};
		},
		[ clientId ]
	);

	const fieldMap = useMemo( () => {
		const map = {};
		availableFields.forEach( ( field ) => {
			if ( field.fieldId ) {
				map[ field.fieldId ] = field;
			}
		} );
		return map;
	}, [ availableFields ] );

	const fieldOptions = useMemo( () => {
		const choices = [
			{
				label: __( 'Selecciona un campo…', 'vas-dinamico-forms' ),
				value: '',
			},
		];

		availableFields.forEach( ( field ) => {
			choices.push( {
				label: field.label,
				value: field.fieldId,
			} );
		} );

		return choices;
	}, [ availableFields ] );

	const hasRequiredData = hasPages && availableFields.length > 0;

	const validateRules = useCallback(
		( rules ) => {
			const errors = {};

			rules.forEach( ( rule, ruleIndex ) => {
				if (
					! Array.isArray( rule.conditions ) ||
					rule.conditions.length === 0
				) {
					errors[ ruleIndex ] = __(
						'Cada regla necesita al menos una condición',
						'vas-dinamico-forms'
					);
					return;
				}

				for ( let i = 0; i < rule.conditions.length; i++ ) {
					const condition = rule.conditions[ i ];
					const fieldMeta = condition.fieldId
						? fieldMap[ condition.fieldId ]
						: null;

					if ( ! fieldMeta ) {
						errors[ ruleIndex ] = __(
							'Selecciona un campo válido para esta condición',
							'vas-dinamico-forms'
						);
						break;
					}

					if ( fieldMeta.type === 'numeric' ) {
						if ( ! condition.operator ) {
							errors[ ruleIndex ] = __(
								'Eligí un operador numérico',
								'vas-dinamico-forms'
							);
							break;
						}

						const numValue = parseFloat( condition.threshold );
						if ( Number.isNaN( numValue ) ) {
							errors[ ruleIndex ] = __(
								'Ingresá un valor numérico válido',
								'vas-dinamico-forms'
							);
							break;
						}
					} else if ( ! condition.value ) {
						errors[ ruleIndex ] = __(
							'Selecciona el valor que dispara la condición',
							'vas-dinamico-forms'
						);
						break;
					}
				}

				if (
					rule.action === 'goToPage' &&
					( ! rule.targetPage || rule.targetPage < 1 )
				) {
					errors[ ruleIndex ] = __(
						'Elegí una página de destino válida',
						'vas-dinamico-forms'
					);
				}
			} );

			setValidationErrors( errors );
		},
		[ fieldMap ]
	);

	useEffect( () => {
		if ( normalizedLogic.enabled && normalizedLogic.rules.length > 0 ) {
			validateRules( normalizedLogic.rules );
		} else {
			setValidationErrors( {} );
		}
	}, [
		normalizedLogic.enabled,
		normalizedLogic.rules,
		availableFields,
		pages,
		validateRules,
	] );

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
		const defaultField =
			fieldMap[ defaultFieldId ] || availableFields[ 0 ] || null;
		const newRule = {
			id: `rule-${ Date.now() }`,
			conditions: [ createCondition( defaultField, defaultFieldType ) ],
			action: 'nextPage',
			targetPage: null,
		};

		const newRules = [ ...normalizedLogic.rules, newRule ];
		setAttributes( {
			conditionalLogic: {
				...normalizedLogic,
				rules: newRules,
			},
		} );
	};

	const updateRule = ( index, updates ) => {
		const newRules = normalizedLogic.rules.map( ( rule, ruleIndex ) => {
			if ( ruleIndex !== index ) {
				return rule;
			}
			return {
				...rule,
				...updates,
			};
		} );

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
			return;
		}

		setAttributes( {
			conditionalLogic: {
				...normalizedLogic,
				rules: newRules,
			},
		} );
	};

	const addConditionToRule = ( ruleIndex ) => {
		const defaultField =
			fieldMap[ defaultFieldId ] || availableFields[ 0 ] || null;

		const newRules = normalizedLogic.rules.map( ( rule, index ) => {
			if ( index !== ruleIndex ) {
				return rule;
			}

			const newCondition = createCondition(
				defaultField,
				defaultFieldType
			);

			return {
				...rule,
				conditions: [ ...rule.conditions, newCondition ].slice(
					0,
					MAX_CONDITIONS
				),
			};
		} );

		setAttributes( {
			conditionalLogic: {
				...normalizedLogic,
				rules: newRules,
			},
		} );
	};

	const removeConditionFromRule = ( ruleIndex, conditionIndex ) => {
		const newRules = normalizedLogic.rules.map( ( rule, index ) => {
			if ( index !== ruleIndex ) {
				return rule;
			}

			const remaining = rule.conditions.filter(
				( _, i ) => i !== conditionIndex
			);

			return {
				...rule,
				conditions:
					remaining.length === 0
						? [
								createCondition(
									fieldMap[ defaultFieldId ],
									defaultFieldType
								),
						  ]
						: remaining,
			};
		} );

		setAttributes( {
			conditionalLogic: {
				...normalizedLogic,
				rules: newRules,
			},
		} );
	};

	const updateCondition = ( ruleIndex, conditionIndex, updates ) => {
		const newRules = normalizedLogic.rules.map( ( rule, rIndex ) => {
			if ( rIndex !== ruleIndex ) {
				return rule;
			}

			const newConditions = rule.conditions.map(
				( condition, cIndex ) => {
					if ( cIndex !== conditionIndex ) {
						return condition;
					}

					return {
						...condition,
						...updates,
					};
				}
			);

			return {
				...rule,
				conditions: newConditions,
			};
		} );

		setAttributes( {
			conditionalLogic: {
				...normalizedLogic,
				rules: newRules,
			},
		} );
	};

	const handleConditionFieldChange = (
		ruleIndex,
		conditionIndex,
		fieldId
	) => {
		const fieldMeta = fieldMap[ fieldId ] || null;
		const nextCondition = createCondition( fieldMeta, defaultFieldType );

		updateCondition( ruleIndex, conditionIndex, {
			...nextCondition,
			logicalOperator:
				normalizedLogic.rules[ ruleIndex ].conditions[ conditionIndex ]
					?.logicalOperator || 'AND',
			fieldId,
		} );
	};

	const updateDefaultAction = ( action, targetPage = null ) => {
		const updatedLogic = {
			...normalizedLogic,
			defaultAction: action,
		};

		if ( action === 'goToPage' && targetPage ) {
			updatedLogic.defaultTargetPage = parseInt( targetPage, 10 );
		} else {
			delete updatedLogic.defaultTargetPage;
		}

		setAttributes( { conditionalLogic: updatedLogic } );
	};

	const getPageOptions = () => {
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

		return pages.map( ( page ) => {
			const label = page.title
				? `${ __( 'Página', 'vas-dinamico-forms' ) } ${
						page.index
				  } – ${ page.title }`
				: `${ __( 'Página', 'vas-dinamico-forms' ) } ${ page.index }`;

			return {
				label,
				value: page.index.toString(),
			};
		} );
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

	const renderCondition = ( ruleIndex, condition, conditionIndex ) => {
		const fieldMeta = condition.fieldId
			? fieldMap[ condition.fieldId ]
			: null;
		const discreteOptions = fieldMeta?.options || [];
		const logicalLabel =
			conditionIndex === 0
				? __( 'Si se cumple', 'vas-dinamico-forms' )
				: __( 'Unir con', 'vas-dinamico-forms' );
		const currentRule = normalizedLogic.rules[ ruleIndex ];

		return (
			<div
				className="conditional-logic-condition"
				key={ condition.id || `${ ruleIndex }-${ conditionIndex }` }
			>
				{ conditionIndex > 0 && (
					<SelectControl
						label={ __( 'Operador lógico', 'vas-dinamico-forms' ) }
						value={ condition.logicalOperator || 'AND' }
						options={ LOGICAL_OPERATOR_OPTIONS }
						onChange={ ( value ) =>
							updateCondition( ruleIndex, conditionIndex, {
								logicalOperator: value,
							} )
						}
					/>
				) }

				<SelectControl
					label={ logicalLabel }
					value={ condition.fieldId || '' }
					options={ fieldOptions }
					onChange={ ( value ) =>
						handleConditionFieldChange(
							ruleIndex,
							conditionIndex,
							value
						)
					}
				/>

				{ fieldMeta && fieldMeta.type === 'discrete' && (
					<SelectControl
						label={ __(
							'Cuando el valor sea',
							'vas-dinamico-forms'
						) }
						value={ condition.value || '' }
						options={ [
							{
								label: __(
									'Selecciona un valor…',
									'vas-dinamico-forms'
								),
								value: '',
							},
							...discreteOptions,
						] }
						onChange={ ( value ) =>
							updateCondition( ruleIndex, conditionIndex, {
								value,
								fieldType: 'discrete',
							} )
						}
					/>
				) }

				{ fieldMeta && fieldMeta.type === 'numeric' && (
					<>
						<SelectControl
							label={ __(
								'Comparar cuando el valor sea',
								'vas-dinamico-forms'
							) }
							value={ condition.operator || '>=' }
							options={ NUMERIC_OPERATOR_OPTIONS }
							onChange={ ( value ) =>
								updateCondition( ruleIndex, conditionIndex, {
									operator: value,
									fieldType: 'numeric',
								} )
							}
						/>
						<TextControl
							label={ __( 'Valor umbral', 'vas-dinamico-forms' ) }
							type="number"
							value={
								condition.threshold !== undefined &&
								condition.threshold !== null
									? String( condition.threshold )
									: ''
							}
							onChange={ ( value ) => {
								const numericValue =
									value === '' || value === null
										? ''
										: parseFloat( value );
								if (
									value === '' ||
									Number.isNaN( numericValue )
								) {
									updateCondition(
										ruleIndex,
										conditionIndex,
										{
											threshold: '',
										}
									);
									return;
								}

								let boundedValue = numericValue;
								if ( fieldMeta.min !== undefined ) {
									boundedValue = Math.max(
										fieldMeta.min,
										boundedValue
									);
								}
								if ( fieldMeta.max !== undefined ) {
									boundedValue = Math.min(
										fieldMeta.max,
										boundedValue
									);
								}

								updateCondition( ruleIndex, conditionIndex, {
									threshold: boundedValue,
									fieldType: 'numeric',
								} );
							} }
							min={ fieldMeta.min }
							max={ fieldMeta.max }
							step="any"
						/>
					</>
				) }

				{ ! fieldMeta && (
					<div className="conditional-logic-warning">
						<p>
							{ __(
								'Este campo ya no está disponible en la página. Seleccioná otro campo para continuar.',
								'vas-dinamico-forms'
							) }
						</p>
					</div>
				) }

				{ currentRule && currentRule.conditions.length > 1 && (
					<Button
						isLink
						isDestructive
						onClick={ () =>
							removeConditionFromRule( ruleIndex, conditionIndex )
						}
					>
						{ __( 'Eliminar condición', 'vas-dinamico-forms' ) }
					</Button>
				) }
			</div>
		);
	};

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
						'Configura saltos y visibilidad según las respuestas del paciente.',
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
									'Para configurar la lógica condicional, primero agregá páginas al formulario.',
									'vas-dinamico-forms'
								) }
							</p>
						</div>
					) }

					{ hasPages && availableFields.length === 0 && (
						<div className="conditional-logic-warning">
							<p>
								{ __(
									'Agregá campos compatibles en esta página (select, radio, múltiple, likert o VAS) para crear reglas.',
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
									'Creá reglas para saltar páginas, ocultar bloques o finalizar según combinaciones de respuestas en esta página.',
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

							{ rule.conditions.map(
								( condition, conditionIndex ) =>
									renderCondition(
										index,
										condition,
										conditionIndex
									)
							) }

							{ rule.conditions.length < MAX_CONDITIONS && (
								<Button
									variant="secondary"
									onClick={ () =>
										addConditionToRule( index )
									}
									className="conditional-logic-add-condition-button"
								>
									{ __(
										'+ Combinar (Y/O)',
										'vas-dinamico-forms'
									) }
								</Button>
							) }

							<SelectControl
								label={ __( 'Entonces', 'vas-dinamico-forms' ) }
								value={ rule.action || 'nextPage' }
								options={ getActionOptions() }
								onChange={ ( action ) => {
									if ( action === 'nextPage' ) {
										updateRule( index, {
											action: 'nextPage',
											targetPage: null,
										} );
									} else if ( action === 'submit' ) {
										updateRule( index, {
											action: 'submit',
											targetPage: null,
										} );
									} else if ( action === 'goToPage' ) {
										updateRule( index, {
											action: 'goToPage',
											targetPage:
												rule.targetPage ||
												pages[ 0 ]?.index ||
												1,
										} );
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
										updateRule( index, {
											targetPage: parseInt( value, 10 ),
										} )
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
							<p>
								{ __(
									'Qué ocurre cuando ninguna condición coincide.',
									'vas-dinamico-forms'
								) }
							</p>
							<SelectControl
								label={ __(
									'Para otros casos',
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
						<div className="conditional-logic-info">
							<p>
								{ __(
									'Las reglas se evalúan en orden. La primera coincidencia define el camino del participante.',
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

function getFieldIdentifier( attributes = {} ) {
	const { fieldName, fieldKey } = attributes;

	if ( fieldName && fieldName.trim() !== '' ) {
		return fieldName.trim();
	}

	if ( fieldKey && fieldKey.trim() !== '' ) {
		return fieldKey.trim();
	}

	return '';
}

function buildLikertOptions( attributes ) {
	const { minValue = 1, maxValue = 5, labels = '' } = attributes;
	const parsedLabels = labels
		? labels
				.split( ',' )
				.map( ( label ) => label.trim() )
				.filter( Boolean )
		: [];

	const options = [];
	for ( let value = minValue; value <= maxValue; value++ ) {
		const label = parsedLabels[ value - minValue ]
			? `${ value } – ${ parsedLabels[ value - minValue ] }`
			: value.toString();
		options.push( label );
	}

	return options;
}

function buildSliderRange( attributes ) {
	const min =
		typeof attributes.minValue === 'number' ? attributes.minValue : 0;
	const maxCandidate =
		typeof attributes.maxValue === 'number'
			? attributes.maxValue
			: min + 10;
	const max = maxCandidate > min ? maxCandidate : min + 10;

	return { min, max };
}

function collectConditionableFields( pageBlock ) {
	if ( ! pageBlock || ! Array.isArray( pageBlock.innerBlocks ) ) {
		return [];
	}

	const fields = [];

	const traverse = ( blocks ) => {
		blocks.forEach( ( block ) => {
			const config = SUPPORTED_FIELD_BLOCKS[ block.name ];

			if ( config ) {
				const fieldId = getFieldIdentifier( block.attributes );
				if ( fieldId ) {
					const label =
						block.attributes.label ||
						block.attributes.fieldName ||
						__( 'Campo sin nombre', 'vas-dinamico-forms' );

					const meta = {
						fieldId,
						label,
						type: config.type,
					};

					if ( config.type === 'discrete' ) {
						const optionValues = config.getOptions(
							block.attributes
						);
						meta.options = optionValues.map( ( value ) => ( {
							label: value,
							value,
						} ) );
					} else if ( config.type === 'numeric' ) {
						const range = config.getRange( block.attributes );
						meta.min = range.min;
						meta.max = range.max;
					}

					fields.push( meta );
				}
			}

			if ( block.innerBlocks && block.innerBlocks.length > 0 ) {
				traverse( block.innerBlocks );
			}
		} );
	};

	traverse( pageBlock.innerBlocks );
	return fields;
}

function createCondition( fieldMeta, fallbackType = 'discrete' ) {
	const isNumeric = fieldMeta?.type
		? fieldMeta.type === 'numeric'
		: fallbackType === 'numeric';

	return {
		id: `cond-${ Date.now() }-${ Math.floor( Math.random() * 1000 ) }`,
		fieldId: fieldMeta?.fieldId || '',
		fieldLabel: fieldMeta?.label || '',
		fieldType: isNumeric ? 'numeric' : 'discrete',
		operator: isNumeric ? '>=' : '==',
		value: isNumeric ? undefined : '',
		threshold: isNumeric ? fieldMeta?.min ?? 0 : undefined,
		logicalOperator: 'AND',
	};
}

function normalizeConditionalLogic(
	conditionalLogic,
	defaultFieldId,
	defaultFieldType
) {
	if ( ! conditionalLogic ) {
		return { enabled: false, rules: [], defaultAction: 'nextPage' };
	}

	const fallbackCondition = () => {
		return createCondition( null, defaultFieldType );
	};

	const normalizeConditionShape = ( condition = {}, index = 0 ) => {
		const isNumeric =
			condition.fieldType === 'numeric' ||
			condition.operator === '>=' ||
			condition.operator === '<=' ||
			condition.operator === '>' ||
			condition.operator === '<';

		return {
			id: condition.id || `cond-${ index }`,
			fieldId: condition.fieldId || defaultFieldId,
			fieldLabel: condition.fieldLabel || '',
			fieldType: isNumeric ? 'numeric' : 'discrete',
			operator: condition.operator || ( isNumeric ? '>=' : '==' ),
			value: isNumeric
				? undefined
				: condition.value ?? condition.matchValue ?? '',
			threshold: isNumeric
				? condition.threshold ?? condition.value ?? null
				: undefined,
			logicalOperator: condition.logicalOperator || 'AND',
		};
	};

	const normalizeRuleShape = ( rule = {}, index = 0 ) => {
		const baseRule = {
			id: rule.id || `rule-${ index }`,
			action: rule.action || 'nextPage',
			targetPage: rule.targetPage || null,
		};

		if ( Array.isArray( rule.conditions ) && rule.conditions.length > 0 ) {
			return {
				...baseRule,
				conditions: rule.conditions.map(
					( condition, conditionIndex ) =>
						normalizeConditionShape(
							condition,
							`${ index }-${ conditionIndex }`
						)
				),
			};
		}

		if ( rule.operator && rule.threshold !== undefined ) {
			return {
				...baseRule,
				conditions: [
					normalizeConditionShape(
						{
							fieldId: rule.fieldId || defaultFieldId,
							fieldType: 'numeric',
							operator: rule.operator,
							threshold:
								rule.threshold ?? rule.valueThreshold ?? null,
						},
						`${ index }-0`
					),
				],
			};
		}

		if ( rule.matchValue !== undefined || rule.value !== undefined ) {
			return {
				...baseRule,
				conditions: [
					normalizeConditionShape(
						{
							fieldId: rule.fieldId || defaultFieldId,
							fieldType: 'discrete',
							operator: '==',
							value: rule.matchValue ?? rule.value ?? '',
						},
						`${ index }-0`
					),
				],
			};
		}

		return {
			...baseRule,
			conditions: [ fallbackCondition() ],
		};
	};

	if ( Array.isArray( conditionalLogic ) ) {
		return {
			enabled: conditionalLogic.length > 0,
			rules: conditionalLogic.map( ( rule, index ) =>
				normalizeRuleShape( rule, index )
			),
			defaultAction: 'nextPage',
		};
	}

	if ( typeof conditionalLogic === 'object' ) {
		const rawRules = Array.isArray( conditionalLogic.rules )
			? conditionalLogic.rules
			: [];

		return {
			enabled: conditionalLogic.enabled !== false,
			rules: rawRules.map( ( rule, index ) =>
				normalizeRuleShape( rule, index )
			),
			defaultAction: conditionalLogic.defaultAction || 'nextPage',
			defaultTargetPage: conditionalLogic.defaultTargetPage || null,
		};
	}

	return { enabled: false, rules: [], defaultAction: 'nextPage' };
}

export default ConditionalLogicControl;
