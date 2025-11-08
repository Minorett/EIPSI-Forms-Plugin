import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, TextControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

import './editor.scss';
import './style.scss';

const Edit = ( { attributes, setAttributes } ) => {
	const { formId, showTitle } = attributes;
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody
					title={ __( 'Form Settings', 'vas-dinamico-forms' ) }
				>
					<TextControl
						label={ __( 'Form ID', 'vas-dinamico-forms' ) }
						value={ formId }
						onChange={ ( value ) =>
							setAttributes( { formId: value } )
						}
						help={ __(
							'Enter the unique identifier for the form',
							'vas-dinamico-forms'
						) }
					/>
					<ToggleControl
						label={ __( 'Show Title', 'vas-dinamico-forms' ) }
						checked={ showTitle }
						onChange={ ( value ) =>
							setAttributes( { showTitle: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			{ formId ? (
				<ServerSideRender
					block="vas-dinamico/form-block"
					attributes={ attributes }
				/>
			) : (
				<div className="vas-dinamico-form-placeholder">
					<div className="components-placeholder">
						<div className="components-placeholder__label">
							{ __( 'VAS Dinámico Form', 'vas-dinamico-forms' ) }
						</div>
						<div className="components-placeholder__instructions">
							{ __(
								'Please enter a Form ID in the block settings.',
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
