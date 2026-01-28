/**
 * AuthOptionsPanel.js
 * Panel de opciones de autenticación para el editor de Gutenberg
 * Permite configurar si un formulario requiere login para ser completado
 *
 * @package
 */

import { __ } from '@wordpress/i18n';
import {
	PanelBody,
	ToggleControl,
	BaseControl,
	Notice,
} from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

const AuthOptionsPanel = ( { attributes, setAttributes } ) => {
	const { requireLogin } = attributes;
	const [ isSaving, setIsSaving ] = useState( false );
	const [ saveMessage, setSaveMessage ] = useState( '' );
	const [ saveError, setSaveError ] = useState( '' );

	// Auto-save cuando cambia requireLogin
	useEffect( () => {
		if ( typeof requireLogin !== 'undefined' ) {
			saveAuthConfig();
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ requireLogin ] );

	const saveAuthConfig = async () => {
		// Si no hay template_id disponible, no guardar
		if ( ! window.eipsiTemplateId ) {
			// Only log in debug mode
			if (
				typeof window !== 'undefined' &&
				window.console &&
				window.eipsiEditorData?.debug
			) {
				window.console.log(
					'[EIPSI] No template ID available for saving auth config'
				);
			}
			return;
		}

		setIsSaving( true );
		setSaveError( '' );

		try {
			const response = await apiFetch( {
				path: '/wp-admin/admin-ajax.php',
				method: 'POST',
				data: {
					action: 'eipsi_save_form_auth_config',
					nonce: window.eipsiAdminNonce,
					template_id: window.eipsiTemplateId,
					require_login: requireLogin,
				},
			} );

			if ( response.success ) {
				setSaveMessage(
					__(
						'Configuración de autenticación guardada',
						'eipsi-forms'
					)
				);
				setTimeout( () => setSaveMessage( '' ), 3000 );
			} else {
				setSaveError(
					response.data ||
						__( 'Error al guardar configuración', 'eipsi-forms' )
				);
			}
		} catch ( error ) {
			// Only log in debug mode
			if (
				typeof window !== 'undefined' &&
				window.console &&
				window.eipsiEditorData?.debug
			) {
				window.console.error(
					'[EIPSI] Error saving auth config:',
					error
				);
			}
			setSaveError( __( 'Error de conexión al guardar', 'eipsi-forms' ) );
		} finally {
			setIsSaving( false );
		}
	};

	return (
		<PanelBody
			title={ __( 'Opciones de Acceso', 'eipsi-forms' ) }
			initialOpen={ false }
			icon="lock"
		>
			<BaseControl>
				<ToggleControl
					label={ __(
						'Requerir login para responder',
						'eipsi-forms'
					) }
					help={ __(
						'Si está activado, solo participantes registrados podrán acceder a este formulario',
						'eipsi-forms'
					) }
					checked={ requireLogin }
					onChange={ ( value ) =>
						setAttributes( { requireLogin: value } )
					}
					disabled={ isSaving }
				/>
			</BaseControl>

			{ requireLogin && (
				<div className="eipsi-auth-options__notice">
					<Notice
						status="info"
						isDismissible={ false }
						style={ { margin: '12px 0 0' } }
					>
						<strong>
							{ __( '⚠️ Importante:', 'eipsi-forms' ) }
						</strong>
						<br />
						{ __(
							'Si está activado, mostrará un login gate en lugar del formulario para usuarios no autenticados.',
							'eipsi-forms'
						) }
					</Notice>
				</div>
			) }

			{ saveMessage && (
				<Notice
					status="success"
					isDismissible={ true }
					onRemove={ () => setSaveMessage( '' ) }
					style={ { margin: '12px 0 0' } }
				>
					{ saveMessage }
				</Notice>
			) }

			{ saveError && (
				<Notice
					status="error"
					isDismissible={ true }
					onRemove={ () => setSaveError( '' ) }
					style={ { margin: '12px 0 0' } }
				>
					{ saveError }
				</Notice>
			) }
		</PanelBody>
	);
};

export default AuthOptionsPanel;
