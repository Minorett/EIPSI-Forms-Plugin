import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

export default function Save( { attributes } ) {
	const {
		formId,
		submitButtonLabel,
		description,
		className,
		// Timing settings
		capturePageTiming,
		captureFieldTiming,
		captureInactivityTime,
	} = attributes;

	const blockProps = useBlockProps.save( {
		className: 'eipsi-form eipsi-form ' + ( className || '' ),
		'data-capture-page-timing': capturePageTiming ? 'true' : 'false',
		'data-capture-field-timing': captureFieldTiming ? 'true' : 'false',
		'data-capture-inactivity-time': captureInactivityTime
			? 'true'
			: 'false',
	} );

	const innerBlocksProps = useInnerBlocksProps.save( {
		className: 'eipsi-form eipsi-form-content',
	} );

	return (
		<div { ...blockProps }>
			{ description && (
				<div className="form-description">
					<p>{ description }</p>
				</div>
			) }
			<form
				className="vas-form eipsi-form-element"
				data-form-id={ formId }
			>
				<input type="hidden" name="form_id" value={ formId } />
				<input
					type="hidden"
					name="form_action"
					value="vas_dinamico_submit_form"
				/>
				<input type="hidden" name="eipsi_nonce" value="" />
				<input
					type="hidden"
					className="eipsi-device-placeholder"
					name="device"
				/>
				<input
					type="hidden"
					className="eipsi-browser-placeholder"
					name="browser"
				/>
				<input
					type="hidden"
					className="eipsi-os-placeholder"
					name="os"
				/>
				<input
					type="hidden"
					className="eipsi-screen-placeholder"
					name="screen_width"
				/>
				<input
					type="hidden"
					className="eipsi-start-time"
					name="start_time"
				/>
				<input
					type="hidden"
					className="eipsi-current-page"
					name="current_page"
					value="1"
				/>

				{ /* LOS BLOQUES HIJOS SE RENDERIZAN AQUÍ AUTOMÁTICAMENTE */ }
				<div { ...innerBlocksProps } />

				{ /* NAVEGACIÓN - EL JS EXISTENTE MANEJARÁ LA VISIBILIDAD */ }
				<div className="form-navigation">
					<div className="form-nav-left">
						<button
							type="button"
							className="eipsi-prev-button"
							style={ { display: 'none' } }
							data-testid="prev-button"
						>
							Anterior
						</button>
					</div>
					<div className="form-nav-right">
						<button
							type="button"
							className="eipsi-next-button"
							data-testid="next-button"
						>
							Siguiente
						</button>
						<button
							type="submit"
							className="eipsi-submit-button"
							style={ { display: 'none' } }
							data-testid="submit-button"
						>
							{ submitButtonLabel || 'Enviar' }
						</button>
					</div>
				</div>

				{ /* INDICADOR DE PROGRESO - EL JS ACTUALIZARÁ LOS NÚMEROS */ }
				<div className="form-progress">
					Página <span className="current-page">1</span> de{ ' ' }
					<span className="total-pages">?</span>
				</div>
			</form>
		</div>
	);
}
