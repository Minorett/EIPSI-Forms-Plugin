import { Modal, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import './ConditionalLogicMap.css';

/**
 * ConditionalLogicMap Component
 * Displays a read-only map of all conditional logic rules in the form,
 * grouped by page and block.
 *
 * @param {Object}   props                   - Component props
 * @param {boolean}  props.isOpen            - Whether the modal is open
 * @param {Function} props.onClose           - Callback to close the modal
 * @param {string}   props.containerClientId - Client ID of the form container
 */
const ConditionalLogicMap = ( { isOpen, onClose, containerClientId } ) => {
	// Helper to normalize legacy formats
	const normalizeLogic = ( logic ) => {
		if ( ! logic ) {
			return null;
		}

		if ( Array.isArray( logic ) ) {
			return {
				enabled: logic.length > 0,
				rules: logic,
				defaultAction: 'nextPage',
			};
		}

		if ( typeof logic === 'object' && logic.enabled !== undefined ) {
			return logic;
		}

		return null;
	};

	const { mapData } = useSelect(
		( select ) => {
			if ( ! containerClientId ) {
				return { mapData: [] };
			}

			const { getBlock } = select( 'core/block-editor' );

			const container = getBlock( containerClientId );

			if ( ! container || ! container.innerBlocks ) {
				return { mapData: [] };
			}

			// Extract pages and their fields
			const pages = container.innerBlocks.filter(
				( block ) => block.name === 'vas-dinamico/form-page'
			);

			const pagesData = pages.map( ( page, pageIndex ) => {
				const pageNumber = pageIndex + 1;
				const pageTitle =
					page.attributes.title ||
					`${ __( 'Página', 'vas-dinamico-forms' ) } ${ pageNumber }`;

				// Find all fields with conditional logic in this page
				const fieldsWithLogic = [];

				const extractFieldsRecursive = ( blocks ) => {
					blocks.forEach( ( block ) => {
						const { attributes, name } = block;

						// Check if this block has conditional logic
						if ( attributes.conditionalLogic ) {
							const logic = normalizeLogic(
								attributes.conditionalLogic
							);

							if (
								logic &&
								logic.enabled &&
								logic.rules.length > 0
							) {
								fieldsWithLogic.push( {
									blockName: name,
									label:
										attributes.label ||
										attributes.fieldName ||
										__(
											'Campo sin nombre',
											'vas-dinamico-forms'
										),
									logic,
									clientId: block.clientId,
								} );
							}
						}

						// Recurse into inner blocks
						if (
							block.innerBlocks &&
							block.innerBlocks.length > 0
						) {
							extractFieldsRecursive( block.innerBlocks );
						}
					} );
				};

				extractFieldsRecursive( page.innerBlocks || [] );

				return {
					pageNumber,
					pageTitle,
					fields: fieldsWithLogic,
				};
			} );

			// Filter out pages with no conditional logic
			return {
				mapData: pagesData.filter( ( page ) => page.fields.length > 0 ),
			};
		},
		[ containerClientId ]
	);

	if ( ! isOpen ) {
		return null;
	}

	const formatConditionText = ( rule ) => {
		// Advanced rule with multiple conditions (AND/OR)
		if ( rule.conditions && Array.isArray( rule.conditions ) ) {
			const parts = rule.conditions.map( ( cond, index ) => {
				const operator = cond.operator === '==' ? '=' : cond.operator;
				const prefix =
					index > 0 ? ` ${ cond.logicalOperator || 'Y' } ` : '';
				return `${ prefix }[Bloque ${ cond.blockId }] ${ operator } "${ cond.value }"`;
			} );
			return parts.join( '' );
		}

		// Simple rule (legacy or single condition)
		if ( rule.operator && rule.threshold !== undefined ) {
			const op = rule.operator === '==' ? '=' : rule.operator;
			return `${ op } ${ rule.threshold }`;
		}

		if ( rule.matchValue !== undefined ) {
			return `= "${ rule.matchValue }"`;
		}

		return __( 'Condición no definida', 'vas-dinamico-forms' );
	};

	const formatActionText = ( rule ) => {
		switch ( rule.action ) {
			case 'nextPage':
				return __( 'Siguiente página', 'vas-dinamico-forms' );
			case 'submit':
				return __( 'Finalizar formulario', 'vas-dinamico-forms' );
			case 'goToPage':
				return `${ __( 'Ir a página', 'vas-dinamico-forms' ) } ${
					rule.targetPage
				}`;
			default:
				return __( 'Acción desconocida', 'vas-dinamico-forms' );
		}
	};

	if ( ! isOpen ) {
		return null;
	}

	return (
		<Modal
			title={ __( 'Mapa de lógica condicional', 'vas-dinamico-forms' ) }
			onRequestClose={ onClose }
			className="conditional-logic-map-modal"
			style={ { maxWidth: '800px', width: '90vw' } }
		>
			<div className="conditional-logic-map-content">
				{ mapData.length === 0 ? (
					<div className="conditional-logic-map-empty">
						<p>
							{ __(
								'No hay reglas de lógica condicional configuradas en este formulario.',
								'vas-dinamico-forms'
							) }
						</p>
					</div>
				) : (
					mapData.map( ( page ) => (
						<div key={ page.pageNumber } className="logic-map-page">
							<h3 className="logic-map-page-title">
								{ page.pageTitle }
							</h3>
							{ page.fields.map( ( field, fieldIndex ) => (
								<div
									key={ fieldIndex }
									className="logic-map-field"
								>
									<h4 className="logic-map-field-label">
										{ field.label }
									</h4>
									<ul className="logic-map-rules">
										{ field.logic.rules.map(
											( rule, ruleIndex ) => (
												<li
													key={ ruleIndex }
													className="logic-map-rule"
												>
													<span className="logic-map-rule-condition">
														<strong>
															{ __(
																'SI',
																'vas-dinamico-forms'
															) }
														</strong>{ ' ' }
														{ formatConditionText(
															rule
														) }
													</span>{ ' ' }
													<span className="logic-map-rule-arrow">
														→
													</span>{ ' ' }
													<span className="logic-map-rule-action">
														{ formatActionText(
															rule
														) }
													</span>
												</li>
											)
										) }
									</ul>
									{ field.logic.defaultAction &&
										field.logic.defaultAction !==
											'nextPage' && (
											<div className="logic-map-default-action">
												<span className="logic-map-rule-condition">
													<strong>
														{ __(
															'Para otros valores',
															'vas-dinamico-forms'
														) }
													</strong>
												</span>{ ' ' }
												<span className="logic-map-rule-arrow">
													→
												</span>{ ' ' }
												<span className="logic-map-rule-action">
													{ formatActionText( {
														action: field.logic
															.defaultAction,
														targetPage:
															field.logic
																.defaultTargetPage,
													} ) }
												</span>
											</div>
										) }
								</div>
							) ) }
						</div>
					) )
				) }
			</div>
			<div className="conditional-logic-map-footer">
				<Button isPrimary onClick={ onClose }>
					{ __( 'Cerrar', 'vas-dinamico-forms' ) }
				</Button>
			</div>
		</Modal>
	);
};

export default ConditionalLogicMap;
