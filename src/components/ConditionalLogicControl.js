import {
	PanelBody,
	ToggleControl,
	SelectControl,
	Button,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import './ConditionalLogicControl.css';

const ConditionalLogicControl = ( {
	attributes,
	setAttributes,
	options = [],
	totalPages = 0,
} ) => {
	const { conditionalLogic } = attributes;

	// Better function to get total pages from form container
	const getFormTotalPages = () => {
		try {
			// Try to get the selected block and its parent form container
			const { getSelectedBlock, getBlockParentsByBlockName, getBlocks } =
				wp.data.select( 'core/block-editor' );
			const selectedBlock = getSelectedBlock();

			if ( ! selectedBlock ) {
				return totalPages;
			}

			// Get parent form container blocks
			const parentIds = getBlockParentsByBlockName(
				selectedBlock.clientId,
				'vas-dinamico/form-container'
			);

			if ( parentIds.length > 0 ) {
				// Get the parent form container block
				const formContainerId = parentIds[ parentIds.length - 1 ];
				const formContainer = getBlocks().find(
					( block ) => block.clientId === formContainerId
				);

				if ( formContainer && formContainer.innerBlocks ) {
					// Count form-page blocks
					const pageBlocks = formContainer.innerBlocks.filter(
						( block ) => block.name === 'vas-dinamico/pagina'
					);

					// Also count field blocks that are not inside pages
					const fieldBlocksNotInPages =
						formContainer.innerBlocks.filter(
							( block ) =>
								! block.name.includes( 'form-page' ) &&
								( block.name.includes( 'campo-' ) ||
									block.name.includes( 'vas-slider' ) )
						);

					return Math.max(
						pageBlocks.length,
						fieldBlocksNotInPages.length,
						totalPages
					);
				}
			}

			return totalPages;
		} catch ( error ) {
			// Error getting form total pages, using default
			return totalPages;
		}
	};

	const actualTotalPages = getFormTotalPages();

	const toggleConditionalLogic = ( enabled ) => {
		if ( enabled ) {
			setAttributes( {
				conditionalLogic: {
					enabled: true,
					rules: [],
				},
			} );
		} else {
			setAttributes( { conditionalLogic: undefined } );
		}
	};

	const addRule = () => {
		const newRules = [
			...( conditionalLogic?.rules || [] ),
			{
				value: '',
				action: 'goToPage',
				targetPage: 1,
			},
		];

		setAttributes( {
			conditionalLogic: {
				...conditionalLogic,
				rules: newRules,
			},
		} );
	};

	const updateRule = ( index, field, value ) => {
		const newRules = [ ...conditionalLogic.rules ];
		newRules[ index ] = {
			...newRules[ index ],
			[ field ]: value,
		};

		setAttributes( {
			conditionalLogic: {
				...conditionalLogic,
				rules: newRules,
			},
		} );
	};

	const removeRule = ( index ) => {
		const newRules = conditionalLogic.rules.filter(
			( _, i ) => i !== index
		);

		setAttributes( {
			conditionalLogic: {
				...conditionalLogic,
				rules: newRules,
			},
		} );
	};

	const getPageOptions = () => {
		const pageOptions = [
			{
				label: __( 'Finalizar formulario', 'vas-dinamico-forms' ),
				value: 'submit',
			},
		];

		for ( let i = 1; i <= actualTotalPages; i++ ) {
			pageOptions.push( {
				label: __( 'Ir a Página', 'vas-dinamico-forms' ) + ' #' + i,
				value: i.toString(),
			} );
		}

		return pageOptions;
	};

	const getOptionOptions = () => {
		if ( options.length === 0 ) {
			return [
				{
					label: __(
						'Agrega opciones primero',
						'vas-dinamico-forms'
					),
					value: '',
				},
			];
		}

		return options.map( ( option ) => ( {
			label: option,
			value: option,
		} ) );
	};

	if ( ! conditionalLogic?.enabled ) {
		return null;
	}

	return (
		<PanelBody
			title={ __( 'Lógica Condicional', 'vas-dinamico-forms' ) }
			initialOpen={ false }
		>
			<ToggleControl
				label={ __(
					'Habilitar lógica condicional',
					'vas-dinamico-forms'
				) }
				checked={ conditionalLogic?.enabled || false }
				onChange={ toggleConditionalLogic }
			/>
			{ conditionalLogic?.enabled && (
				<div className="conditional-logic-panel">
					{ actualTotalPages === 0 && (
						<div className="conditional-logic-warning">
							<p>
								{ __(
									'Agrega páginas al formulario para configurar la lógica condicional.',
									'vas-dinamico-forms'
								) }
							</p>
						</div>
					) }

					{ options.length === 0 && (
						<div className="conditional-logic-warning">
							<p>
								{ __(
									'Agrega opciones al campo para configurar la lógica condicional.',
									'vas-dinamico-forms'
								) }
							</p>
						</div>
					) }

					{ conditionalLogic.rules.map( ( rule, index ) => (
						<div key={ index } className="conditional-logic-rule">
							<h4>
								{ __( 'Regla', 'vas-dinamico-forms' ) }{ ' ' }
								{ index + 1 }
							</h4>

							<SelectControl
								label={ __(
									'Si el valor es',
									'vas-dinamico-forms'
								) }
								value={ rule.value }
								options={ getOptionOptions() }
								onChange={ ( value ) =>
									updateRule( index, 'value', value )
								}
							/>

							<SelectControl
								label={ __( 'Entonces', 'vas-dinamico-forms' ) }
								value={
									rule.action === 'goToPage'
										? rule.targetPage.toString()
										: 'submit'
								}
								options={ getPageOptions() }
								onChange={ ( value ) => {
									if ( value === 'submit' ) {
										updateRule( index, 'action', 'submit' );
										updateRule( index, 'targetPage', 0 );
									} else {
										updateRule(
											index,
											'action',
											'goToPage'
										);
										updateRule(
											index,
											'targetPage',
											parseInt( value )
										);
									}
								} }
							/>

							<Button
								isDestructive
								isSmall
								onClick={ () => removeRule( index ) }
							>
								{ __( 'Eliminar regla', 'vas-dinamico-forms' ) }
							</Button>
						</div>
					) ) }

					<Button
						isPrimary
						isSmall
						onClick={ addRule }
						disabled={
							options.length === 0 || actualTotalPages === 0
						}
					>
						{ __( 'Agregar regla', 'vas-dinamico-forms' ) }
					</Button>
				</div>
			) }
		</PanelBody>
	);
};

export default ConditionalLogicControl;
