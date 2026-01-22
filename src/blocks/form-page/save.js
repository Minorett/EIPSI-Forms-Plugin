import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

export default function Save( { attributes } ) {
	const {
		title,
		page,
		className,
		pageType,
		isHidden,
		enableRestartButton,
		restartButtonLabel,
	} = attributes;

	const isThankYouPage = pageType === 'thank_you';

	const blockProps = useBlockProps.save( {
		className: `eipsi-page ${
			isThankYouPage ? 'eipsi-thank-you-page-block' : ''
		} ${ className || '' }`.trim(),
		'data-page': isThankYouPage ? 'thank-you' : page || 1,
		'data-page-type': pageType || 'standard',
		style: isHidden ? { display: 'none' } : undefined,
	} );

	const innerBlocksProps = useInnerBlocksProps.save( {
		className: 'eipsi-page-content',
	} );

	const pageNumber = isThankYouPage ? null : page || 1;

	return (
		<div { ...blockProps }>
			{ ! isThankYouPage && (
				<div className="page-header">
					<span className={ `page-badge page-${ pageNumber }` }>
						{ __( 'Page', 'eipsi-forms' ) } { pageNumber }
					</span>
					{ title && (
						<div className="page-header-content">
							<h3 className="page-header-title">{ title }</h3>
						</div>
					) }
				</div>
			) }
			{ isThankYouPage && title && (
				<h3 className="eipsi-page-title">{ title }</h3>
			) }
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
							__( 'Comenzar de nuevo', 'eipsi-forms' ) }
					</button>
				</div>
			) }
		</div>
	);
}
