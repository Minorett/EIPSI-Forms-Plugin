import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import {
	migrateToStyleConfig,
	serializeToCSSVariables,
} from '../../utils/styleTokens';

export default function Save( { attributes } ) {
	const {
		formId,
		submitButtonLabel,
		description,
		className,
		styleConfig,
		allowBackwardsNav,
	} = attributes;

	// Get style configuration (migrate if needed)
	const currentConfig = styleConfig || migrateToStyleConfig( attributes );
	const cssVars = serializeToCSSVariables( currentConfig );

	const blockProps = useBlockProps.save( {
		className: 'vas-dinamico-form eipsi-form ' + ( className || '' ),
		style: cssVars,
	} );

	const innerBlocksProps = useInnerBlocksProps.save( {
		className: 'eipsi-form eipsi-form-content',
	} );

	return (
		<div { ...blockProps }>
			{ /* HEADER CON DARK MODE TOGGLE */ }
			<header className="eipsi-header">
				<h2>{ description || 'Formulario' }</h2>
				<button
					type="button"
					className="eipsi-toggle"
					id="eipsi-theme-toggle"
					aria-label="Toggle dark mode"
				>
					🌙 Nocturno
				</button>
			</header>

			<form
				className="vas-form eipsi-form-element"
				data-form-id={ formId }
				data-allow-backwards-nav={
					allowBackwardsNav ? 'true' : 'false'
				}
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
					className="eipsi-start-time"
					name="form_start_time"
				/>
				<input
					type="hidden"
					className="eipsi-end-time"
					name="form_end_time"
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
					<button
						type="button"
						className="eipsi-prev-button"
						style={ { display: 'none' } }
						data-testid="prev-button"
					>
						Anterior
					</button>
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

				{ /* INDICADOR DE PROGRESO - EL JS ACTUALIZARÁ LOS NÚMEROS */ }
				<div className="form-progress">
					Página <span className="current-page">1</span> de{ ' ' }
					<span className="total-pages">?</span>
				</div>

				{ /* FOOTER CON DARK MODE TOGGLE */ }
				<div className="eipsi-theme-toggle">
					<button
						type="button"
						className="eipsi-toggle"
						aria-label="Toggle dark mode"
					>
						🌙 Nocturno
					</button>
				</div>
			</form>

			{ /* MOBILE FIXED TOGGLE */ }
			<div className="eipsi-toggle-mobile">
				<button
					type="button"
					className="eipsi-toggle"
					aria-label="Toggle dark mode"
				>
					🌙
				</button>
			</div>

			{ /* NOSCRIPT FALLBACK */ }
			<noscript>
				<style>
					{ `.eipsi-theme-toggle, .eipsi-toggle-mobile { display: none !important; }` }
				</style>
			</noscript>
		</div>
	);
}
