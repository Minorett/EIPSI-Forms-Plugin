import { useBlockProps, RichText } from '@wordpress/block-editor';

export default function Save( { attributes } ) {
	const { consentText, consentLabel, isRequired } = attributes;

	const blockProps = useBlockProps.save( {
		className: 'eipsi-consent-block form-group',
		'data-consent-block': 'true',
		'data-required': isRequired ? 'true' : 'false',
	} );

	return (
		<div { ...blockProps }>
			<div className="eipsi-consent-text">
				<RichText.Content value={ consentText } />
			</div>
			<div className="eipsi-consent-control">
				<div className="eipsi-checkbox-wrapper">
					<input
						type="checkbox"
						id="eipsi-consent-checkbox"
						name="eipsi_consent_accepted"
						className="eipsi-consent-checkbox"
						required={ isRequired }
						data-required={ isRequired ? 'true' : 'false' }
						data-testid="input-eipsi_consent_accepted"
					/>
					<label htmlFor="eipsi-consent-checkbox">
						{ consentLabel }
						{ isRequired && (
							<span className="eipsi-required-mark">*</span>
						) }
					</label>
				</div>
				<div className="form-error" style={ { display: 'none' } }></div>
			</div>
		</div>
	);
}
