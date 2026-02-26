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
	 * Inicializar todos los formularios de pool join presentes en la página.
	 */
	function init() {
		$( document ).on( 'submit', '.eipsi-pool-join-form', handleSubmit );
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

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Activar o desactivar estado de carga en el botón.
	 *
	 * @param {jQuery}  $btn    Botón.
	 * @param {boolean} loading True = cargando.
	 */
	function setLoading( $btn, loading ) {
		if ( loading ) {
			$btn.attr( 'disabled', true ).text(
				$btn.data( 'label-loading' ) ||
					getI18n( 'loading', 'Asignando...' )
			);
		} else {
			$btn.removeAttr( 'disabled' ).text(
				$btn.data( 'label-default' ) || 'Comenzar'
			);
		}
	}

	/**
	 * Mostrar mensaje de error o éxito inline.
	 *
	 * @param {jQuery} $el  Contenedor del mensaje.
	 * @param {string} msg  Texto a mostrar.
	 * @param {string} type 'error' | 'success'
	 */
	function showMessage( $el, msg, type ) {
		$el.removeClass( 'eipsi-pool-msg-error eipsi-pool-msg-success' )
			.addClass( 'eipsi-pool-msg-' + ( type || 'error' ) )
			.text( msg )
			.slideDown( 200 );
	}

	/**
	 * Ocultar mensaje inline.
	 *
	 * @param {jQuery} $el Contenedor del mensaje.
	 */
	function hideMessage( $el ) {
		$el.hide().text( '' );
	}

	/**
	 * Validar formato de email de forma básica.
	 *
	 * @param {string} email Email a validar.
	 * @return {boolean} True si el email tiene formato válido.
	 */
	function isValidEmail( email ) {
		return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( email );
	}

	/**
	 * Obtener string i18n con fallback.
	 *
	 * @param {string} key      Clave en eipsiPoolJoin.i18n.
	 * @param {string} fallback Texto de fallback si la clave no existe.
	 * @return {string} Texto traducido o fallback.
	 */
	function getI18n( key, fallback ) {
		return eipsiPoolJoin && eipsiPoolJoin.i18n && eipsiPoolJoin.i18n[ key ]
			? eipsiPoolJoin.i18n[ key ]
			: fallback;
	}

	// Arrancar cuando el DOM esté listo
	$( init );
} )( jQuery );
