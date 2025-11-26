import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, SelectControl, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { decodeEntities } from '@wordpress/html-entities';
import ServerSideRender from '@wordpress/server-side-render';
import { useMemo } from '@wordpress/element';

import './editor.scss';
import './style.scss';

const Edit = ( { attributes, setAttributes } ) => {
	const { templateId } = attributes;
	const blockProps = useBlockProps();

	const query = useMemo(
		() => ( {
			per_page: -1,
			status: 'publish',
			orderby: 'title',
			order: 'asc',
		} ),
		[]
	);

	// Fetch available form templates from the Form Library
	const { formTemplates, isLoading } = useSelect(
		( select ) => {
			const coreStore = select( 'core' );
			const dataStore = select( 'core/data' );

			return {
				formTemplates:
					coreStore?.getEntityRecords(
						'postType',
						'eipsi_form_template',
						query
					) || [],
				isLoading: dataStore?.isResolving( 'core', 'getEntityRecords', [
					'postType',
					'eipsi_form_template',
					query,
				] ),
			};
		},
		[ query ]
	);

	// Build options for SelectControl
	const formOptions = useMemo(
		() => [
			{
				label: __(
					'— Seleccioná un formulario —',
					'vas-dinamico-forms'
				),
				value: '',
			},
			...formTemplates.map( ( template ) => ( {
				label:
					decodeEntities( template.title.rendered ) ||
					__( '(Sin título)', 'vas-dinamico-forms' ),
				value: String( template.id ),
			} ) ),
		],
		[ formTemplates ]
	);

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody
					title={ __(
						'Configuración del formulario',
						'vas-dinamico-forms'
					) }
					initialOpen={ true }
				>
					{ isLoading && (
						<div style={ { textAlign: 'center', padding: '20px' } }>
							<Spinner />
							<p style={ { marginTop: '10px', color: '#666' } }>
								{ __(
									'Cargando formularios…',
									'vas-dinamico-forms'
								) }
							</p>
						</div>
					) }
					{ ! isLoading && formTemplates.length === 0 && (
						<div
							style={ {
								padding: '12px',
								background: '#f0f0f1',
								borderRadius: '4px',
							} }
						>
							<p
								style={ {
									margin: 0,
									fontSize: '13px',
									color: '#666',
								} }
							>
								{ __(
									'No hay formularios creados aún.',
									'vas-dinamico-forms'
								) }
							</p>
							<p
								style={ {
									margin: '8px 0 0',
									fontSize: '13px',
								} }
							>
								<a
									href="/wp-admin/post-new.php?post_type=eipsi_form_template"
									target="_blank"
								>
									{ __(
										'+ Crear tu primer formulario',
										'vas-dinamico-forms'
									) }
								</a>
							</p>
						</div>
					) }
					{ ! isLoading && formTemplates.length > 0 && (
						<SelectControl
							label={ __(
								'Formulario a mostrar',
								'vas-dinamico-forms'
							) }
							value={ templateId ? String( templateId ) : '' }
							options={ formOptions }
							onChange={ ( value ) =>
								setAttributes( {
									templateId: value
										? parseInt( value, 10 )
										: 0,
								} )
							}
							help={ __(
								'Elegí un formulario de la librería para mostrarlo en esta página.',
								'vas-dinamico-forms'
							) }
						/>
					) }
				</PanelBody>
			</InspectorControls>

			{ templateId ? (
				<ServerSideRender
					block="vas-dinamico/form-block"
					attributes={ attributes }
				/>
			) : (
				<div className="vas-dinamico-form-placeholder">
					<div className="components-placeholder">
						<div className="components-placeholder__label">
							{ __( 'Formulario EIPSI', 'vas-dinamico-forms' ) }
						</div>
						<div className="components-placeholder__instructions">
							{ __(
								'Seleccioná un formulario de la librería en la configuración del bloque →',
								'vas-dinamico-forms'
							) }
						</div>
					</div>
				</div>
			) }
		</div>
	);
};

registerBlockType( 'vas-dinamico/form-block', {
	edit: Edit,
	save: () => {
		return null;
	},
} );
