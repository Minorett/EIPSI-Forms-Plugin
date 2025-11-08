import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

export default function Save( { attributes } ) {
	const { title, pageIndex, className } = attributes;

	const blockProps = useBlockProps.save( {
		className: `eipsi-page ${ className || '' }`.trim(),
		'data-page': pageIndex,
		style: {
			display: pageIndex === 1 ? undefined : 'none',
		},
	} );

	const innerBlocksProps = useInnerBlocksProps.save( {
		className: 'eipsi-page-content',
	} );

	return (
		<div { ...blockProps }>
			{ title && <h3 className="eipsi-page-title">{ title }</h3> }
			<div { ...innerBlocksProps } />
		</div>
	);
}
