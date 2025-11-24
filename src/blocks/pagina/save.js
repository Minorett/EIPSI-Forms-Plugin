import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

export default function Save( { attributes } ) {
	const {
		title,
		pageIndex,
		className,
		pageType,
		enableRestartButton,
		restartButtonLabel,
	} = attributes;

	const isThankYouPage = pageType === 'thank_you';

	const blockProps = useBlockProps.save( {
		className: `eipsi-page ${
			isThankYouPage ? 'eipsi-thank-you-page-block' : ''
		} ${ className || '' }`.trim(),
		'data-page': isThankYouPage ? 'thank-you' : pageIndex,
		'data-page-type': isThankYouPage ? 'thank_you' : 'standard',
		style: {
			display: isThankYouPage || pageIndex !== 1 ? 'none' : undefined,
		},
	} );

	const innerBlocksProps = useInnerBlocksProps.save( {
		className: 'eipsi-page-content',
	} );

	return (
		<div { ...blockProps }>
			{ title && <h3 className="eipsi-page-title">{ title }</h3> }
			<div { ...innerBlocksProps } />
			{ isThankYouPage && enableRestartButton && (
				<div className="eipsi-thank-you-restart">
					<button
						type="button"
						className="eipsi-restart-button"
						data-action="restart"
						data-testid="thank-you-restart-button"
					>
						{ restartButtonLabel ||
							__( 'Comenzar de nuevo', 'vas-dinamico-forms' ) }
					</button>
				</div>
			) }
		</div>
	);
}
