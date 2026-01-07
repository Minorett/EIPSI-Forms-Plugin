import {
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
import { useSelect } from '@wordpress/data';

export default function Edit( { attributes, setAttributes, clientId } ) {
	const { title, pageIndex } = attributes;

	const computedPageIndex = useSelect(
		( select ) => {
			const { getBlockRootClientId, getBlockOrder, getBlock } =
				select( 'core/block-editor' );
			const parentClientId = getBlockRootClientId( clientId );
			const siblingClientIds =
				( parentClientId
					? getBlockOrder( parentClientId )
					: getBlockOrder() ) || [];

			const pageClientIds = siblingClientIds.filter( ( siblingId ) => {
				const block = getBlock( siblingId );
				return block?.name === 'eipsi/form-page';
			} );

			const index = pageClientIds.indexOf( clientId );
			return index === -1 ? null : index + 1;
		},
		[ clientId ]
	);

	useEffect( () => {
		if ( computedPageIndex && computedPageIndex !== pageIndex ) {
			setAttributes( { pageIndex: computedPageIndex } );
		}
	}, [ computedPageIndex, pageIndex, setAttributes ] );

	const currentPageIndex = computedPageIndex || pageIndex || 1;

	const blockProps = useBlockProps( {
		className: 'eipsi-page-editor',
	} );

	const ALLOWED_BLOCKS = [
		'core/paragraph',
		'core/heading',
		'core/html',
		'core/spacer',
		'core/separator',
		'core/group',
		'core/columns',
		'core/column',
		'core/list',
		'core/list-item',
		'core/image',
		'core/buttons',
		'core/button',
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
			className: 'eipsi-page-content-editor',
		},
		{
			allowedBlocks: ALLOWED_BLOCKS,
			templateLock: false,
			renderAppender: undefined,
		}
	);

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Page Settings', 'eipsi-forms' ) }>
					<TextControl
						label={ __( 'Page Title (Optional)', 'eipsi-forms' ) }
						value={ title }
						onChange={ ( value ) =>
							setAttributes( { title: value } )
						}
						help={ __(
							'Enter an optional title for this page (e.g., Personal Information)',
							'eipsi-forms'
						) }
					/>
					<TextControl
						label={ __( 'Page Number', 'eipsi-forms' ) }
						type="number"
						value={ currentPageIndex }
						help={ __(
							'This page number updates automatically based on block order.',
							'eipsi-forms'
						) }
						disabled
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<div className="eipsi-page-preview">
					<div className="page-header">
						<span className="page-badge">
							{ __( 'Page', 'eipsi-forms' ) } { currentPageIndex }
						</span>
						{ title && (
							<h3 className="eipsi-page-title">{ title }</h3>
						) }
						{ ! title && (
							<p className="page-placeholder-text">
								{ __(
									'Add a page title in the block settings (optional)',
									'eipsi-forms'
								) }
							</p>
						) }
					</div>
					<div { ...innerBlocksProps } />
				</div>
			</div>
		</>
	);
}
