/**
 * EIPSI Forms - Randomization Frontend v1
 * Detecta ?eipsi_random=true y asigna formulario aleatoriamente
 *
 * @since 1.3.0
 */

( function () {
	'use strict';

	/* global sessionStorage */

	if ( typeof window === 'undefined' ) {
		return;
	}

	/**
	 * Inicializa la aleatorización si se detecta el query param
	 */
	async function initRandomization() {
		// === Fase 2: Detectar token de recordatorio ===
		const urlParams = new URLSearchParams( window.location.search );
		const token = urlParams.get( 'eipsi_token' );

		if ( token ) {
			// Validar token con el servidor
			const tokenValid = await validateReminderToken( token );
			if ( tokenValid ) {
				// Token válido, la función ya guardó los datos en sessionStorage
				return;
			}
			// Token inválido, continuar con flujo normal
		}

		// Verificar que ya no tenemos una asignación guardada
		const existingFormId = sessionStorage.getItem( 'eipsi_assigned_form' );
		const existingSeed = sessionStorage.getItem( 'eipsi_seed' );

		if ( existingFormId && existingSeed ) {
			// Ya tenemos asignación, verificar si necesitamos redirigir
			const currentFormId = getCurrentFormId();
			if ( String( existingFormId ) !== String( currentFormId ) ) {
				// Redirigir al formulario asignado
				redirectToForm( existingFormId, existingSeed );
				return;
			}
			return; // Ya estamos en el formulario correcto
		}

		// Obtener formulario principal
		const mainForm = document.querySelector(
			'.vas-form, .vas-dinamico-form'
		);
		if ( ! mainForm ) {
			return;
		}

		const mainFormId = mainForm.dataset.formId || getFormIdFromUrl();
		if ( ! mainFormId ) {
			return;
		}

		// Obtener identificadores del participante
		const email = await getParticipantEmail();
		const participantId = getParticipantIdFromStorage();

		// Necesitamos al menos uno: email o participant_id
		if ( ! email && ! participantId ) {
			return;
		}

		// Llamar al handler AJAX
		try {
			const params = {
				action: 'eipsi_random_assign',
				form_id: mainFormId,
				nonce: window.eipsiNonce || window.eipsiRandomNonce || '',
			};

			// Enviar email si existe, si no participant_id
			if ( email ) {
				params.email = email;
			} else if ( participantId ) {
				params.participant_id = participantId;
			}

			const response = await fetch( window.ajaxurl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: new URLSearchParams( params ),
			} );

			const data = await response.json();

			if ( data.success && data.data ) {
				const { formId, seed, type } = data.data;

				// Guardar en sessionStorage
				sessionStorage.setItem(
					'eipsi_assigned_form',
					String( formId )
				);
				sessionStorage.setItem( 'eipsi_seed', seed || '' );
				sessionStorage.setItem( 'eipsi_random_type', type || 'random' );

				// Si el formulario asignado es diferente, redirigir
				if ( String( formId ) !== String( mainFormId ) ) {
					redirectToForm( formId, seed );
				}
			}
		} catch ( error ) {
			// eslint-disable-next-line no-console
			console.error( '[EIPSI Random] Error:', error );
		}
	}

	/**
	 * Obtiene el ID del formulario actual
	 *
	 * @return {string} El ID del formulario actual
	 */
	function getCurrentFormId() {
		const form = document.querySelector( '.vas-form, .vas-dinamico-form' );
		if ( form && form.dataset.formId ) {
			return form.dataset.formId;
		}

		// Intentar obtener de la URL
		return getFormIdFromUrl();
	}

	/**
	 * Extrae el form_id de la URL
	 *
	 * @return {string} El ID del formulario desde la URL
	 */
	function getFormIdFromUrl() {
		const urlParams = new URLSearchParams( window.location.search );
		return urlParams.get( 'form_id' ) || '';
	}

	/**
	 * Obtiene un participant_id desde URL o storage.
	 *
	 * @return {string|null} participant_id o null si no hay.
	 */
	function getParticipantIdFromStorage() {
		// 1) URL param (prioritario)
		try {
			const urlParams = new URLSearchParams( window.location.search );
			const fromUrl = urlParams.get( 'participant_id' );
			if ( fromUrl ) {
				return fromUrl;
			}
		} catch ( e ) {
			// Ignore URL parsing errors
		}

		// 2) sessionStorage
		try {
			const fromSession = sessionStorage.getItem(
				'eipsi_participant_id'
			);
			if ( fromSession ) {
				return fromSession;
			}
		} catch ( e ) {
			// Ignore storage errors
		}

		// 3) localStorage
		try {
			const fromLocal = window.localStorage.getItem(
				'eipsi_participant_id'
			);
			if ( fromLocal ) {
				return fromLocal;
			}
		} catch ( e ) {
			// Ignore storage errors
		}

		return null;
	}

	/**
	 * Obtiene el email del participante
	 *
	 * @return {Promise<string|null>} El email del participante o null
	 */
	async function getParticipantEmail() {
		// Si ya tenemos email en window, usar ese
		if ( window.eipsiParticipantEmail ) {
			return window.eipsiParticipantEmail;
		}

		// Verificar en localStorage
		try {
			const savedEmail = window.localStorage.getItem(
				'eipsi_participant_email'
			);
			if ( savedEmail ) {
				return savedEmail;
			}
		} catch ( e ) {
			// Ignore storage errors
		}

		// Verificar si hay un campo de email en el formulario
		const emailField = document.querySelector( 'input[type="email"]' );
		if ( emailField && emailField.value ) {
			return emailField.value;
		}

		// No mostrar prompt en contexto de frontend sin usuario
		// El participante debe estar logueado o proporcionar email de otra forma
		return null;
	}

	/**
	 * Redirige al formulario asignado
	 *
	 * @param {number|string} formId - ID del formulario asignado
	 * @param {string}        seed   - Seed de la asignación
	 */
	function redirectToForm( formId, seed ) {
		const baseUrl = window.location.href.split( '?' )[ 0 ];
		const params = new URLSearchParams();

		if ( formId ) {
			params.set( 'form_id', String( formId ) );
		}
		if ( seed ) {
			params.set( 'eipsi_seed', seed );
		}
		params.set( 'eipsi_assigned', '1' );

		window.location.href = baseUrl + '?' + params.toString();
	}

	/**
	 * Detecta si la aleatorización ya fue completada
	 *
	 * @return {boolean} True si ya fue asignado
	 */
	function isAlreadyAssigned() {
		const urlParams = new URLSearchParams( window.location.search );
		return urlParams.get( 'eipsi_assigned' ) === '1';
	}

	/**
	 * Valida un token de recordatorio con el servidor (Fase 2)
	 *
	 * @param {string} token - Token recibido en la URL
	 * @return {Promise<boolean>} True si el token es válido
	 */
	async function validateReminderToken( token ) {
		try {
			const response = await fetch( window.ajaxurl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: new URLSearchParams( {
					action: 'eipsi_validate_reminder_token',
					token,
				} ),
			} );

			const data = await response.json();

			if ( data.success && data.data && data.data.valid ) {
				const { email, form_id: formId, take, seed } = data.data;

				// Guardar datos en sessionStorage
				sessionStorage.setItem(
					'eipsi_assigned_form',
					String( formId )
				);
				sessionStorage.setItem( 'eipsi_seed', seed || '' );
				sessionStorage.setItem( 'eipsi_email', email || '' );
				sessionStorage.setItem( 'eipsi_take', String( take ) );
				sessionStorage.setItem(
					'eipsi_random_type',
					'token_auto_login'
				);

				// eslint-disable-next-line no-console
				console.log( '[EIPSI Random] Token validated successfully' );

				// Si el formulario actual no coincide, redirigir
				const currentFormId = getCurrentFormId();
				if ( String( formId ) !== String( currentFormId ) ) {
					redirectToForm( formId, seed );
				}

				return true;
			}

			// Token inválido o expirado
			// eslint-disable-next-line no-console
			console.warn(
				'[EIPSI Random] Token invalid:',
				data.data ? data.data.message : 'Unknown error'
			);

			// Mostrar mensaje al usuario (solo en consola para evitar alert bloqueante)
			// Los errores se muestran mejor en UI personalizada
			if ( data.data && data.data.message ) {
				// eslint-disable-next-line no-console
				console.error( '[EIPSI Random]', data.data.message );
			}

			return false;
		} catch ( error ) {
			// eslint-disable-next-line no-console
			console.error( '[EIPSI Random] Token validation error:', error );
			return false;
		}
	}

	// Inicializar cuando el DOM esté listo
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			// Solo ejecutar si hay query param o flag de asignación
			const urlParams = new URLSearchParams( window.location.search );
			if ( urlParams.has( 'eipsi_random' ) || isAlreadyAssigned() ) {
				initRandomization();
			}
		} );
	} else {
		// DOM ya cargado
		const urlParams = new URLSearchParams( window.location.search );
		if ( urlParams.has( 'eipsi_random' ) || isAlreadyAssigned() ) {
			initRandomization();
		}
	}

	// Exportar funciones para uso externo
	window.EIPSIRandomization = {
		init: initRandomization,
		/**
		 * Obtiene la asignación actual del participante.
		 * @return {{formId: string|null, seed: string|null, type: string|null}} Un objeto con los datos de asignación actual.
		 */
		getAssignment() {
			return {
				formId: sessionStorage.getItem( 'eipsi_assigned_form' ),
				seed: sessionStorage.getItem( 'eipsi_seed' ),
				type: sessionStorage.getItem( 'eipsi_random_type' ),
			};
		},
		clearAssignment() {
			sessionStorage.removeItem( 'eipsi_assigned_form' );
			sessionStorage.removeItem( 'eipsi_seed' );
			sessionStorage.removeItem( 'eipsi_random_type' );
		},
	};
} )();
