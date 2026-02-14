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
 * - ✅ v1.5.4 - Exposes raw fingerprint details for export
 *
 * @package EIPSI_Forms
 * @since 1.3.1
 */

/* global crypto, TextEncoder */

( function () {
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
        const hashHex = hashArray
            .map( ( b ) => b.toString( 16 ).padStart( 2, '0' ) )
            .join( '' );
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
            const gl =
                canvas.getContext( 'webgl' ) ||
                canvas.getContext( 'experimental-webgl' );

            if ( ! gl ) {
                return 'no-webgl';
            }

            const debugInfo = gl.getExtension( 'WEBGL_debug_renderer_info' );
            if ( debugInfo ) {
                const vendor = gl.getParameter(
                    debugInfo.UNMASKED_VENDOR_WEBGL
                );
                const renderer = gl.getParameter(
                    debugInfo.UNMASKED_RENDERER_WEBGL
                );
                return vendor + '|' + renderer;
            }

            return (
                gl.getParameter( gl.VENDOR ) +
                '|' +
                gl.getParameter( gl.RENDERER )
            );
        } catch ( e ) {
            return 'webgl-error';
        }
    }

    /**
     * Recolectar información del dispositivo/navegador
     *
     * @return {Object} Objeto con rawDetails y string combinado
     */
    function collectDeviceInfo() {
        const info = [];
        const rawDetails = {};

        // Screen resolution
        rawDetails.screen_resolution = window.screen.width + 'x' + window.screen.height;
        info.push( 'screen:' + rawDetails.screen_resolution );

        // Color depth
        rawDetails.color_depth = window.screen.colorDepth;
        info.push( 'depth:' + rawDetails.color_depth );

        // Pixel ratio
        rawDetails.pixel_ratio = window.devicePixelRatio;
        info.push( 'ratio:' + rawDetails.pixel_ratio );

        // Timezone
        try {
            rawDetails.timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
            info.push( 'tz:' + rawDetails.timezone );
        } catch ( e ) {
            rawDetails.timezone = 'unknown';
            info.push( 'tz:unknown' );
        }

        // Timezone offset
        rawDetails.timezone_offset = new Date().getTimezoneOffset();
        info.push( 'offset:' + rawDetails.timezone_offset );

        // Language
        rawDetails.language = navigator.language || 'unknown';
        info.push( 'lang:' + rawDetails.language );

        // Languages array
        if ( navigator.languages ) {
            rawDetails.languages = navigator.languages.join( ',' );
            info.push( 'langs:' + rawDetails.languages );
        }

        // Platform
        rawDetails.platform = navigator.platform || 'unknown';
        info.push( 'platform:' + rawDetails.platform );

        // User agent
        rawDetails.user_agent = navigator.userAgent;
        info.push( 'ua:' + rawDetails.user_agent );

        // Hardware concurrency (CPU cores)
        if ( navigator.hardwareConcurrency ) {
            rawDetails.hardware_concurrency = navigator.hardwareConcurrency;
            info.push( 'cores:' + rawDetails.hardware_concurrency );
        }

        // Device memory (GB)
        if ( navigator.deviceMemory ) {
            rawDetails.device_memory = navigator.deviceMemory;
            info.push( 'memory:' + rawDetails.device_memory );
        }

        // Do Not Track
        rawDetails.do_not_track = navigator.doNotTrack || 'unknown';
        info.push( 'dnt:' + rawDetails.do_not_track );

        // Cookies enabled
        rawDetails.cookies_enabled = navigator.cookieEnabled;
        info.push( 'cookies:' + rawDetails.cookies_enabled );

        // Canvas fingerprint
        rawDetails.canvas_fingerprint = getCanvasFingerprint();
        info.push( 'canvas:' + rawDetails.canvas_fingerprint );

        // WebGL fingerprint
        rawDetails.webgl_fingerprint = getWebGLFingerprint();
        info.push( 'webgl:' + rawDetails.webgl_fingerprint );

        // Plugins (legacy, pero útil)
        if ( navigator.plugins && navigator.plugins.length > 0 ) {
            const plugins = [];
            for ( let i = 0; i < navigator.plugins.length; i++ ) {
                plugins.push( navigator.plugins[ i ].name );
            }
            rawDetails.plugins = plugins.join( '|' );
            info.push( 'plugins:' + rawDetails.plugins );
        } else {
            rawDetails.plugins = 'none';
        }

        return {
            rawDetails: rawDetails,
            combinedString: info.join( '||' )
        };
    }

    /**
     * Generar fingerprint único del usuario
     *
     * @return {Promise<Object>} Objeto con fingerprint hash y rawDetails
     */
    async function generateFingerprint() {
        const deviceInfo = collectDeviceInfo();
        const hash = await sha256( deviceInfo.combinedString );
        return {
            fingerprint: 'fp_' + hash.substring( 0, 32 ), // 32 caracteres
            rawDetails: deviceInfo.rawDetails
        };
    }

    /**
     * Obtener o generar fingerprint (con caché en sessionStorage)
     *
     * @return {Promise<Object>} Objeto con fingerprint hash y rawDetails
     */
    async function getFingerprint() {
        // Intentar obtener de sessionStorage primero (dura la sesión del navegador)
        try {
            const cached = sessionStorage.getItem( 'eipsi_fingerprint' );
            const cachedRaw = sessionStorage.getItem( 'eipsi_fingerprint_raw' );
            if ( cached && cachedRaw ) {
                return {
                    fingerprint: cached,
                    rawDetails: JSON.parse( cachedRaw )
                };
            }
        } catch ( e ) {
            // sessionStorage no disponible (privado/incognito)
        }

        // Generar nuevo fingerprint
        const result = await generateFingerprint();

        // Guardar en sessionStorage para esta sesión
        try {
            sessionStorage.setItem( 'eipsi_fingerprint', result.fingerprint );
            sessionStorage.setItem( 'eipsi_fingerprint_raw', JSON.stringify( result.rawDetails ) );
        } catch ( e ) {
            // Ignorar si falla
        }

        return result;
    }

    /**
     * Exponer globalmente para uso en shortcodes
     */
    window.eipsiGetFingerprint = getFingerprint;

    /**
     * Auto-generar fingerprint al cargar la página
     * y guardarlo en un input hidden si existe el formulario
     */
    document.addEventListener( 'DOMContentLoaded', async function () {
        try {
            const result = await getFingerprint();
            const fingerprint = result.fingerprint;
            const rawDetails = result.rawDetails;

            // Buscar todos los formularios de EIPSI con aleatorización
            const containers = document.querySelectorAll(
                '.eipsi-randomization-container'
            );

            containers.forEach( function ( container ) {
                // Crear input hidden con el fingerprint hash
                let fingerprintInput = container.querySelector(
                    'input[name="eipsi_user_fingerprint"]'
                );

                if ( ! fingerprintInput ) {
                    fingerprintInput = document.createElement( 'input' );
                    fingerprintInput.type = 'hidden';
                    fingerprintInput.name = 'eipsi_user_fingerprint';
                    container.appendChild( fingerprintInput );
                }

                fingerprintInput.value = fingerprint;

                // ✅ v1.5.4 - Crear input hidden con los detalles crudos
                let rawDetailsInput = container.querySelector(
                    'input[name="eipsi_fingerprint_raw"]'
                );

                if ( ! rawDetailsInput ) {
                    rawDetailsInput = document.createElement( 'input' );
                    rawDetailsInput.type = 'hidden';
                    rawDetailsInput.name = 'eipsi_fingerprint_raw';
                    container.appendChild( rawDetailsInput );
                }

                rawDetailsInput.value = JSON.stringify( rawDetails );

                // Agregar data-attribute para debugging
                container.setAttribute(
                    'data-fingerprint',
                    fingerprint.substring( 0, 16 ) + '...'
                );
            } );

            // eslint-disable-next-line no-console
            console.log(
                '[EIPSI Fingerprint] Generated:',
                fingerprint.substring( 0, 16 ) + '...'
            );
            // eslint-disable-next-line no-console
            console.log(
                '[EIPSI Fingerprint] Raw details:',
                rawDetails
            );
        } catch ( e ) {
            // eslint-disable-next-line no-console
            console.error( '[EIPSI Fingerprint] Error:', e );
        }
    } );
} )();
