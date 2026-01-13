/**
 * EIPSI Forms - Robust User Fingerprinting
 * 
 * Genera un fingerprint único del dispositivo/navegador para persistencia
 * de asignaciones en RCT sin depender de cookies o localStorage.
 * 
 * Features:
 * - Canvas fingerprinting (GPU/renderer)
 * - Screen + timezone + language
 * - User agent + platform
 * - WebGL fingerprinting
 * - Hash SHA-256
 * 
 * @package EIPSI_Forms
 * @since 1.3.1
 */

/* global crypto, TextEncoder */

( function() {
	'use strict';

	/**
	 * Generar hash SHA-256 de un string
	 * 
	 * @param {string} message - Texto a hashear
	 * @return {Promise<string>} Hash hexadecimal
	 */
	async function sha256( message ) {
		const msgBuffer = new TextEncoder().encode( message );
		const hashBuffer = await crypto.subtle.digest( 'SHA-256', msgBuffer );
		const hashArray = Array.from( new Uint8Array( hashBuffer ) );
		const hashHex = hashArray.map( b => b.toString( 16 ).padStart( 2, '0' ) ).join( '' );
		return hashHex;
	}

	/**
	 * Canvas Fingerprinting
	 * Genera hash único basado en cómo el navegador renderiza canvas
	 * 
	 * @return {string} Canvas fingerprint
	 */
	function getCanvasFingerprint() {
		try {
			const canvas = document.createElement( 'canvas' );
			canvas.width = 200;
			canvas.height = 50;
			const ctx = canvas.getContext( '2d' );
			
			if ( ! ctx ) {
				return 'no-canvas';
			}

			// Dibujar texto con diferentes estilos
			ctx.textBaseline = 'top';
			ctx.font = '14px "Arial"';
			ctx.textBaseline = 'alphabetic';
			ctx.fillStyle = '#f60';
			ctx.fillRect( 125, 1, 62, 20 );
			ctx.fillStyle = '#069';
			ctx.fillText( 'EIPSI RCT', 2, 15 );
			ctx.fillStyle = 'rgba(102, 204, 0, 0.7)';
			ctx.fillText( 'EIPSI RCT', 4, 17 );

			// Agregar formas geométricas
			ctx.globalCompositeOperation = 'multiply';
			ctx.fillStyle = 'rgb(255,0,255)';
			ctx.beginPath();
			ctx.arc( 50, 25, 20, 0, Math.PI * 2, true );
			ctx.closePath();
			ctx.fill();

			// Obtener data URL
			return canvas.toDataURL();
		} catch ( e ) {
			return 'canvas-error';
		}
	}

	/**
	 * WebGL Fingerprinting
	 * Información de la GPU/renderer del usuario
	 * 
	 * @return {string} WebGL info
	 */
	function getWebGLFingerprint() {
		try {
			const canvas = document.createElement( 'canvas' );
			const gl = canvas.getContext( 'webgl' ) || canvas.getContext( 'experimental-webgl' );
			
			if ( ! gl ) {
				return 'no-webgl';
			}

			const debugInfo = gl.getExtension( 'WEBGL_debug_renderer_info' );
			if ( debugInfo ) {
				const vendor = gl.getParameter( debugInfo.UNMASKED_VENDOR_WEBGL );
				const renderer = gl.getParameter( debugInfo.UNMASKED_RENDERER_WEBGL );
				return vendor + '|' + renderer;
			}

			return gl.getParameter( gl.VENDOR ) + '|' + gl.getParameter( gl.RENDERER );
		} catch ( e ) {
			return 'webgl-error';
		}
	}

	/**
	 * Recolectar información del dispositivo/navegador
	 * 
	 * @return {string} String combinado de características
	 */
	function collectDeviceInfo() {
		const info = [];

		// Screen resolution
		info.push( 'screen:' + window.screen.width + 'x' + window.screen.height );
		
		// Color depth
		info.push( 'depth:' + window.screen.colorDepth );
		
		// Pixel ratio
		info.push( 'ratio:' + window.devicePixelRatio );

		// Timezone
		try {
			info.push( 'tz:' + Intl.DateTimeFormat().resolvedOptions().timeZone );
		} catch ( e ) {
			info.push( 'tz:unknown' );
		}

		// Timezone offset
		info.push( 'offset:' + new Date().getTimezoneOffset() );

		// Language
		info.push( 'lang:' + ( navigator.language || 'unknown' ) );

		// Languages array
		if ( navigator.languages ) {
			info.push( 'langs:' + navigator.languages.join( ',' ) );
		}

		// Platform
		info.push( 'platform:' + ( navigator.platform || 'unknown' ) );

		// User agent
		info.push( 'ua:' + navigator.userAgent );

		// Hardware concurrency (CPU cores)
		if ( navigator.hardwareConcurrency ) {
			info.push( 'cores:' + navigator.hardwareConcurrency );
		}

		// Device memory (GB)
		if ( navigator.deviceMemory ) {
			info.push( 'memory:' + navigator.deviceMemory );
		}

		// Do Not Track
		info.push( 'dnt:' + ( navigator.doNotTrack || 'unknown' ) );

		// Cookies enabled
		info.push( 'cookies:' + navigator.cookieEnabled );

		// Canvas fingerprint
		info.push( 'canvas:' + getCanvasFingerprint() );

		// WebGL fingerprint
		info.push( 'webgl:' + getWebGLFingerprint() );

		// Plugins (legacy, pero útil)
		if ( navigator.plugins && navigator.plugins.length > 0 ) {
			const plugins = [];
			for ( let i = 0; i < navigator.plugins.length; i++ ) {
				plugins.push( navigator.plugins[ i ].name );
			}
			info.push( 'plugins:' + plugins.join( '|' ) );
		}

		return info.join( '||' );
	}

	/**
	 * Generar fingerprint único del usuario
	 * 
	 * @return {Promise<string>} Fingerprint hasheado
	 */
	async function generateFingerprint() {
		const deviceInfo = collectDeviceInfo();
		const hash = await sha256( deviceInfo );
		return 'fp_' + hash.substring( 0, 32 ); // 32 caracteres
	}

	/**
	 * Obtener o generar fingerprint (con caché en sessionStorage)
	 * 
	 * @return {Promise<string>} Fingerprint
	 */
	async function getFingerprint() {
		// Intentar obtener de sessionStorage primero (dura la sesión del navegador)
		try {
			const cached = sessionStorage.getItem( 'eipsi_fingerprint' );
			if ( cached ) {
				return cached;
			}
		} catch ( e ) {
			// sessionStorage no disponible (privado/incognito)
		}

		// Generar nuevo fingerprint
		const fingerprint = await generateFingerprint();

		// Guardar en sessionStorage para esta sesión
		try {
			sessionStorage.setItem( 'eipsi_fingerprint', fingerprint );
		} catch ( e ) {
			// Ignorar si falla
		}

		return fingerprint;
	}

	/**
	 * Exponer globalmente para uso en shortcodes
	 */
	window.eipsiGetFingerprint = getFingerprint;

	/**
	 * Auto-generar fingerprint al cargar la página
	 * y guardarlo en un input hidden si existe el formulario
	 */
	document.addEventListener( 'DOMContentLoaded', async function() {
		try {
			const fingerprint = await getFingerprint();

			// Buscar todos los formularios de EIPSI con aleatorización
			const containers = document.querySelectorAll( '.eipsi-randomization-container' );
			
			containers.forEach( function( container ) {
				// Crear input hidden con el fingerprint
				let fingerprintInput = container.querySelector( 'input[name="eipsi_user_fingerprint"]' );
				
				if ( ! fingerprintInput ) {
					fingerprintInput = document.createElement( 'input' );
					fingerprintInput.type = 'hidden';
					fingerprintInput.name = 'eipsi_user_fingerprint';
					container.appendChild( fingerprintInput );
				}

				fingerprintInput.value = fingerprint;

				// Agregar data-attribute para debugging
				container.setAttribute( 'data-fingerprint', fingerprint.substring( 0, 16 ) + '...' );
			} );

			// eslint-disable-next-line no-console
			console.log( '[EIPSI Fingerprint] Generated:', fingerprint.substring( 0, 16 ) + '...' );
		} catch ( e ) {
			// eslint-disable-next-line no-console
			console.error( '[EIPSI Fingerprint] Error:', e );
		}
	} );
}() );
