/**
 * EIPSI Forms - Pool Join Frontend
 *
 * Maneja el envío del formulario [eipsi_pool_join] vía AJAX.
 * Muestra estado de carga, redirige al magic link o muestra enlace.
 *
 * @package
 * @since 2.1.0
 */

/* global eipsiPoolJoin */

( function ( $ ) {
	'use strict';

	/**
	 * Inicializar todos los formularios de pool presentes en la página.
	 */
	function init() {
		$( document ).on( 'submit', '.eipsi-pool-join-form', handleSubmit );
		$( document ).on( 'submit', '.eipsi-pool-auth-form', handlePoolAuthSubmit );
		$( document ).on( 'click', '.eipsi-pool-tab', switchTab );
		$( document ).on( 'click', '.eipsi-pool-switch-tab', switchTabLink );
		$( document ).on( 'click', '.eipsi-pool-request-assignment-btn', handleRequestAssignment );
	}

	/**
	 * Handler para clic en tabs.
	 *
	 * @param {Event} e Evento click.
	 */
	function switchTab( e ) {
		const $tab = $( this );
		const target = $tab.data( 'tab' );
		const $container = $tab.closest( '.eipsi-pool-login-box' );

		// Activar tab
		$container.find( '.eipsi-pool-tab' ).removeClass( 'active' );
		$tab.addClass( 'active' );

		// Mostrar pane correspondiente
		$container.find( '.eipsi-pool-tab-content' ).removeClass( 'active' );
		$container.find( '[data-pane="' + target + '"]' ).addClass( 'active' );
	}

	/**
	 * Handler para links de cambio de tab.
	 *
	 * @param {Event} e Evento click.
	 */
	function switchTabLink( e ) {
		e.preventDefault();
		const target = $( this ).data( 'target' );
		const $container = $( this ).closest( '.eipsi-pool-login-box' );

		// Activar tab
		$container.find( '.eipsi-pool-tab' ).removeClass( 'active' );
		$container.find( '[data-tab="' + target + '"]' ).addClass( 'active' );

		// Mostrar pane correspondiente
		$container.find( '.eipsi-pool-tab-content' ).removeClass( 'active' );
		$container.find( '[data-pane="' + target + '"]' ).addClass( 'active' );
	}

	/**
	 * Handler para login/register en el bloque pool.
	 *
	 * @param {Event} e Evento submit.
	 */
	function handlePoolAuthSubmit( e ) {
		e.preventDefault();

		const $form = $( this );
		const action = $form.data( 'action' );
		const poolId = $form.data( 'pool-id' );
		const $container = $form.closest( '.eipsi-pool-login-box' );
		const $btn = $form.find( '.eipsi-pool-submit-btn' );
		const $message = $container.find( '.eipsi-pool-login-message' );
		const $successState = $container.find( '.eipsi-pool-success-state' );
		const email = $form.find( 'input[name="email"]' ).val().trim();

		// Validación
		if ( ! email || ! isValidEmail( email ) ) {
			showMessage(
				$message,
				getI18n( 'error_email_invalid', 'Por favor ingresá un email válido.' ),
				'error'
			);
			$form.find( 'input[name="email"]' ).focus();
			return;
		}

		// Para registro, verificar términos
		if ( action === 'register' ) {
			if ( ! $form.find( 'input[name="accept_terms"]' ).is( ':checked' ) ) {
				showMessage(
					$message,
					getI18n( 'error_terms_required', 'Debés aceptar los términos y condiciones.' ),
					'error'
				);
				return;
			}
		}

		setLoading( $btn, true );
		hideMessage( $message );

		const data = {
			action: 'eipsi_pool_auth',
			nonce: window.eipsiPoolJoin && eipsiPoolJoin.nonce ? eipsiPoolJoin.nonce : '',
			pool_id: poolId,
			email: email,
			auth_action: action,
		};

		$.ajax( {
			url: eipsiPoolJoin.ajaxUrl,
			method: 'POST',
			dataType: 'json',
			data,
		} )
			.done( function ( response ) {
				if ( response.success ) {
					if ( action === 'login' && response.data.redirect_url ) {
						// Login exitoso con estudio asignado -> redirigir
						window.location.href = response.data.redirect_url;
					} else if ( action === 'login' && response.data.magic_link_url ) {
						// Login exitoso sin estudio asignado -> mostrar enlace
						$container.find( '.eipsi-pool-tab-content' ).hide();
						$successState.find( '.eipsi-pool-success-text' ).text(
							getI18n( 'login_success', '¡Listo! Te enviamos un email con el enlace de acceso.' )
						);
						if ( response.data.magic_link_url ) {
							$successState.find( '.eipsi-pool-redirect-link' )
								.attr( 'href', response.data.magic_link_url )
								.show();
						}
						$successState.show();
					} else if ( action === 'register' ) {
						// Registro exitoso -> mostrar mensaje de confirmación
						$container.find( '.eipsi-pool-tab-content' ).hide();
						$successState.find( '.eipsi-pool-success-text' ).text(
							getI18n( 'register_success', '¡Listo! Te enviamos un email de confirmación. Revisá tu bandeja de entrada (y spam).' )
						);
						$successState.find( '.eipsi-pool-redirect-link' ).hide();
						$successState.show();
					}
				} else {
					const msg = response.data && response.data.message
						? response.data.message
						: getI18n( 'error_generic', 'Ocurrió un error. Por favor, intentá de nuevo.' );
					showMessage( $message, msg, 'error' );
					setLoading( $btn, false );
				}
			} )
			.fail( function () {
				showMessage(
					$message,
					getI18n( 'error_generic', 'No se pudo conectar con el servidor. Revisá tu conexión.' ),
					'error'
				);
				setLoading( $btn, false );
			} );
	}

	/**
	 * Handler principal del submit del formulario.
	 *
	 * @param {Event} e Evento submit.
	 */
	function handleSubmit( e ) {
		e.preventDefault();

		const $form = $( this );
		const $wrap = $form.closest( '.eipsi-pool-join-wrap' );
		const $btn = $form.find( '.eipsi-pool-submit-btn' );
		const $message = $form.find( '.eipsi-pool-message' );
		const $success = $wrap.find( '[id$="-success"]' );
		const poolId = $form.data( 'pool-id' );
		const doRedirect = String( $form.data( 'redirect' ) ) === '1';
		const email = $form.find( 'input[name="email"]' ).val().trim();
		const name = $form.find( 'input[name="name"]' ).val().trim();

		// Validación básica en cliente
		if ( ! email || ! isValidEmail( email ) ) {
			showMessage(
				$message,
				getI18n(
					'error_generic',
					'Por favor ingresá un email válido.'
				),
				'error'
			);
			$form.find( 'input[name="email"]' ).focus();
			return;
		}

		// Estado de carga
		setLoading( $btn, true );
		hideMessage( $message );

		const data = {
			action: 'eipsi_join_pool',
			eipsi_pool_join_nonce: eipsiPoolJoin.nonce,
			pool_id: poolId,
			email,
		};

		if ( name ) {
			data.name = name;
		}

		$.ajax( {
			url: eipsiPoolJoin.ajaxUrl,
			method: 'POST',
			dataType: 'json',
			data,
		} )
			.done( function ( response ) {
				if ( response.success ) {
					handleSuccess( $form, $success, response.data, doRedirect );
				} else {
					const msg =
						response.data && response.data.message
							? response.data.message
							: getI18n(
									'error_generic',
									'Ocurrió un error. Por favor, intentá de nuevo.'
							  );
					showMessage( $message, msg, 'error' );
					setLoading( $btn, false );
				}
			} )
			.fail( function () {
				showMessage(
					$message,
					getI18n(
						'error_generic',
						'No se pudo conectar con el servidor. Revisá tu conexión e intentá de nuevo.'
					),
					'error'
				);
				setLoading( $btn, false );
			} );
	}

	/**
	 * Handler del formulario de login simplificado del pool (block shortcode).
	 *
	 * @param {Event} e Evento submit.
	 */
	function handleLoginSubmit( e ) {
		e.preventDefault();

		const $form = $( this );
		const $btn = $form.find( '.eipsi-pool-submit-btn' );
		const $message = $form.closest( '.eipsi-pool-login-form-wrapper' ).find( '.eipsi-pool-login-message' );
		const poolId = $form.data( 'pool-id' );
		const email = $form.find( 'input[name="email"]' ).val().trim();

		// Validación básica en cliente
		if ( ! email || ! isValidEmail( email ) ) {
			showMessage(
				$message,
				getI18n(
					'error_email_invalid',
					'Por favor ingresá un email válido.'
				),
				'error'
			);
			$form.find( 'input[name="email"]' ).focus();
			return;
		}

		// Verificar términos
		if ( ! $form.find( 'input[name="accept_terms"]' ).is( ':checked' ) ) {
			showMessage(
				$message,
				getI18n(
					'error_terms_required',
					'Debés aceptar los términos y condiciones para continuar.'
				),
				'error'
			);
			return;
		}

		// Estado de carga
		setLoading( $btn, true );
		hideMessage( $message );

		const data = {
			action: 'eipsi_join_pool',
			nonce: eipsiPoolJoin.nonce,
			pool_id: poolId,
			email: email,
		};

		$.ajax( {
			url: eipsiPoolJoin.ajaxUrl,
			method: 'POST',
			dataType: 'json',
			data,
		} )
			.done( function ( response ) {
				if ( response.success ) {
					// Mostrar mensaje de éxito: revisá tu email
					showMessage(
						$message,
						getI18n(
							'login_success',
							'¡Listo! Te enviamos un email con un enlace para acceder. Revisá tu bandeja de entrada (y spam).'
						),
						'success'
					);
					$form.hide();
				} else {
					const msg =
						response.data && response.data.message
							? response.data.message
							: getI18n(
									'error_generic',
									'Ocurrió un error. Por favor, intentá de nuevo.'
							  );
					showMessage( $message, msg, 'error' );
					setLoading( $btn, false );
				}
			} )
			.fail( function () {
				showMessage(
					$message,
					getI18n(
						'error_generic',
						'No se pudo conectar con el servidor. Revisá tu conexión e intentá de nuevo.'
					),
					'error'
				);
				setLoading( $btn, false );
			} );
	}

	/**
	 * Handler para solicitar asignación desde el dashboard del pool.
	 *
	 * @param {Event} e Evento click.
	 */
	function handleRequestAssignment( e ) {
		e.preventDefault();

		const $btn = $( this );
		const poolId = $btn.data( 'pool-id' );
		const participantId = $btn.data( 'participant-id' );
		const $message = $btn.closest( '.eipsi-pool-dashboard-content' ).find( '.eipsi-pool-assignment-message' );

		// Estado de carga
		setLoading( $btn, true );
		$message.hide().removeClass( 'success error' );

		const data = {
			action: 'eipsi_request_pool_assignment',
			nonce: window.eipsiPoolJoin && eipsiPoolJoin.nonce ? eipsiPoolJoin.nonce : '',
			pool_id: poolId,
			participant_id: participantId,
		};

		$.ajax( {
			url: eipsiPoolJoin.ajaxUrl,
			method: 'POST',
			dataType: 'json',
			data,
		} )
			.done( function ( response ) {
				if ( response.success ) {
					// Asignación exitosa - mostrar mensaje y redirigir
					$message
						.html( response.data.message + '<br>Preparando tu acceso al estudio...' )
						.addClass( 'success' )
						.show();

					// Redirigir al estudio después de un breve delay
					setTimeout( function () {
						if ( response.data.redirect_url ) {
							window.location.href = response.data.redirect_url;
						}
					}, 1500 );
				} else {
					const msg = response.data && response.data.message
						? response.data.message
						: getI18n( 'error_generic', 'Ocurrió un error. Por favor, intentá de nuevo.' );
					$message
						.html( msg )
						.addClass( 'error' )
						.show();
					setLoading( $btn, false );
				}
			} )
			.fail( function () {
				$message
					.html( getI18n( 'error_generic', 'No se pudo conectar con el servidor. Revisá tu conexión.' ) )
					.addClass( 'error' )
					.show();
				setLoading( $btn, false );
			} );
	}

	/**
	 * Manejar respuesta exitosa.
	 *
	 * @param {jQuery}  $form      Formulario.
	 * @param {jQuery}  $success   Panel de éxito.
	 * @param {Object}  data       Datos de respuesta del servidor.
	 * @param {boolean} doRedirect Redirigir automáticamente.
	 */
	function handleSuccess( $form, $success, data, doRedirect ) {
		const magicLink = data.magic_link_url || '';
		const message = data.message || getI18n( 'success_title', '¡Listo!' );

		if ( doRedirect && magicLink ) {
			// Mostrar mensaje de redirección breve antes de navegar
			const $btn = $form.find( '.eipsi-pool-submit-btn' );
			$btn.text(
				getI18n( 'redirecting', 'Redirigiendo a tu estudio...' )
			);

			setTimeout( function () {
				window.location.href = magicLink;
			}, 1200 );

			return;
		}

		// Sin redirección: mostrar panel de éxito con enlace
		$form.hide();

		$success.find( '.eipsi-pool-success-msg' ).text( message );

		if ( magicLink ) {
			$success
				.find( '.eipsi-pool-success-link' )
				.attr( 'href', magicLink )
				.show();
		}

		$success.slideDown( 300 );
		$success.trigger( 'focus' );
	}

	$success.slideDown( 300 );
	$success.trigger( 'focus' );
}

// -------------------------------------------------------------------------
// Helpers
// -------------------------------------------------------------------------

/**
 * Alternar estado de carga del botón.
 *
 * @param {jQuery} $btn     Botón.
 * @param {boolean} loading Estado de carga.
 */
function setLoading( $btn, loading ) {
	const $text = $btn.find( '.btn-text' );
	const $spinner = $btn.find( '.btn-spinner' );
	const defaultText = $btn.data( 'label-default' ) || $btn.text();

	if ( loading ) {
		$btn.prop( 'disabled', true );
		$text.hide();
		$spinner.show();
	} else {
		$btn.prop( 'disabled', false );
		$text.show().text( defaultText );
		$spinner.hide();
	}
}

/**
 * Validar formato de email.
 *
 * @param {string} email Email a validar.
 * @return {boolean} True si es válido.
 */
function isValidEmail( email ) {
	return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( email );
}

/**
 * Mostrar mensaje en el contenedor.
 *
 * @param {jQuery} $container Contenedor del mensaje.
 * @param {string} message    Mensaje a mostrar.
 * @param {string} type       Tipo: 'success' o 'error'.
 */
function showMessage( $container, message, type ) {
	$container
		.removeClass( 'success error' )
		.addClass( type )
		.html( message )
		.show();
}

/**
 * Ocultar mensaje.
 *
 * @param {jQuery} $container Contenedor del mensaje.
 */
function hideMessage( $container ) {
	$container.hide().removeClass( 'success error' );
}

/**
 * Alternar estado de carga del botón.
 *
 * @param {jQuery} $btn     Botón.
 * @param {boolean} loading Estado de carga.
 */
function setLoading( $btn, loading ) {
	const $text = $btn.find( '.btn-text' );
	const $spinner = $btn.find( '.btn-spinner' );
	const defaultText = $btn.data( 'label-default' ) || $btn.text();

	if ( loading ) {
		$btn.prop( 'disabled', true );
		$text.hide();
		$spinner.show();
	} else {
		$btn.prop( 'disabled', false );
		$text.show().text( defaultText );
		$spinner.hide();
	}
}

/**
 * Obtener string i18n con fallback.
 *
 * @param {string} key      Clave en eipsiPoolJoin.i18n.
 * @param {string} fallback Texto de fallback si la clave no existe.
 * @return {string} Texto traducido o fallback.
 */
function getI18n( key, fallback ) {
	return window.eipsiPoolJoin && eipsiPoolJoin.i18n && eipsiPoolJoin.i18n[ key ]
		? eipsiPoolJoin.i18n[ key ]
		: fallback;
}

// Arrancar cuando el DOM esté listo
jQuery( init );
} )( jQuery );
