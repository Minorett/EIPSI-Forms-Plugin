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
		const mainForm = document.querySelector( '.vas-dinamico-form' );
		if ( ! mainForm ) {
			return;
		}

		const mainFormId = mainForm.dataset.formId || getFormIdFromUrl();
		if ( ! mainFormId ) {
			return;
		}

		// Obtener email del participante
		const email = await getParticipantEmail();
		if ( ! email ) {
			return;
		}

		// Llamar al handler AJAX
		try {
			const response = await fetch( window.ajaxurl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: new URLSearchParams( {
					action: 'eipsi_random_assign',
					form_id: mainFormId,
					email,
					nonce: window.eipsiNonce || window.eipsiRandomNonce || '',
				} ),
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
		const form = document.querySelector( '.vas-dinamico-form' );
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
