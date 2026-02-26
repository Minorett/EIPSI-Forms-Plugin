/**
 * EIPSI Forms - Device Data Capture (RAW)
 *
 * Captura datos RAW del dispositivo/navegador sin procesamiento ni hashes.
 * El investigador decide qué datos usar al exportar.
 *
 * Features:
 * - Canvas fingerprint (data URL, no hash)
 * - WebGL renderer info
 * - Screen resolution + color depth + pixel ratio
 * - Timezone + timezone offset
 * - Language + languages array
 * - Hardware (CPU cores, RAM if available)
 * - Do Not Track + Cookies enabled
 * - Plugins list
 * - User Agent + Platform
 *
 * @package EIPSI_Forms
 * @since 1.5.4
 * @updated 2.0.0 - RAW data only, no hash generation
 */

/* global navigator, window, document, Intl, crypto, TextEncoder */

( function () {
    'use strict';

    /**
     * Canvas Fingerprinting
     * Genera data URL del canvas (no hash)
     *
     * @return {string} Canvas data URL or error indicator
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

            // Obtener data URL (truncado para no sobrecargar)
            const dataUrl = canvas.toDataURL();
            // Solo guardar primeros 100 caracteres como firma
            return dataUrl.substring( 0, 100 );
        } catch ( e ) {
            return 'canvas-error';
        }
    }

    /**
     * WebGL Renderer Info
     * Información de la GPU/renderer del usuario
     *
     * @return {string} WebGL vendor + renderer
     */
    function getWebGLRenderer() {
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
                return vendor + ' | ' + renderer;
            }

            return (
                gl.getParameter( gl.VENDOR ) +
                ' | ' +
                gl.getParameter( gl.RENDERER )
            );
        } catch ( e ) {
            return 'webgl-error';
        }
    }

    /**
     * Get browser plugins list
     *
     * @return {string} Pipe-separated plugin names
     */
    function getPluginList() {
        try {
            if ( navigator.plugins && navigator.plugins.length > 0 ) {
                const plugins = [];
                const maxPlugins = Math.min( navigator.plugins.length, 10 );
                for ( let i = 0; i < maxPlugins; i++ ) {
                    plugins.push( navigator.plugins[ i ].name );
                }
                return plugins.join( ' | ' );
            }
            return 'none';
        } catch ( e ) {
            return 'error';
        }
    }

    /**
     * Capturar todos los datos RAW del dispositivo
     *
     * @return {Object} Objeto con todos los datos crudos
     */
    function captureDeviceData() {
        const data = {};

        // Screen resolution
        data.screen_resolution = window.screen.width + 'x' + window.screen.height;

        // Color depth
        data.screen_depth = window.screen.colorDepth || null;

        // Pixel ratio
        data.pixel_ratio = window.devicePixelRatio || 1;

        // Timezone
        try {
            data.timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
        } catch ( e ) {
            data.timezone = 'unknown';
        }

        // Timezone offset in minutes
        data.timezone_offset = new Date().getTimezoneOffset();

        // Language
        data.language = navigator.language || 'unknown';

        // Languages array
        if ( navigator.languages && navigator.languages.length > 0 ) {
            data.languages = navigator.languages.slice( 0, 5 ).join( ', ' );
        } else {
            data.languages = data.language;
        }

        // Platform
        data.platform = navigator.platform || 'unknown';

        // User agent
        data.user_agent = navigator.userAgent || 'unknown';

        // Hardware concurrency (CPU cores)
        data.cpu_cores = navigator.hardwareConcurrency || null;

        // Device memory (GB) - only available in some browsers
        data.ram = navigator.deviceMemory || null;

        // Do Not Track
        data.do_not_track = navigator.doNotTrack || 'unspecified';

        // Cookies enabled
        data.cookies_enabled = navigator.cookieEnabled ? 'true' : 'false';

        // Canvas fingerprint (truncated data URL)
        data.canvas_fingerprint = getCanvasFingerprint();

        // WebGL renderer info
        data.webgl_renderer = getWebGLRenderer();

        // Plugins list
        data.plugins = getPluginList();

        // Touch support
        data.touch_support = (
            'ontouchstart' in window ||
            navigator.maxTouchPoints > 0 ||
            window.DocumentTouch
        ) ? 'true' : 'false';

        // Max touch points
        data.max_touch_points = navigator.maxTouchPoints || 0;

        return data;
    }

    /**
     * Obtener o generar datos del dispositivo (con caché en sessionStorage)
     *
     * @return {Object} Objeto con todos los datos crudos
     */
    function getDeviceData() {
        // Intentar obtener de sessionStorage primero (dura la sesión del navegador)
        try {
            const cached = sessionStorage.getItem( 'eipsi_device_data' );
            if ( cached ) {
                return JSON.parse( cached );
            }
        } catch ( e ) {
            // sessionStorage no disponible (privado/incognito)
        }

        // Capturar nuevos datos
        const data = captureDeviceData();

        // Guardar en sessionStorage para esta sesión
        try {
            sessionStorage.setItem( 'eipsi_device_data', JSON.stringify( data ) );
        } catch ( e ) {
            // Ignorar si falla
        }

        return data;
    }

    /**
     * Exponer globalmente para uso en shortcodes
     */
    window.eipsiGetDeviceData = getDeviceData;

    /**
     * Auto-capturar datos al cargar la página
     * y guardarlos en inputs hidden si existe el formulario
     */
    document.addEventListener( 'DOMContentLoaded', function () {
        try {
            const deviceData = getDeviceData();

            // Buscar todos los formularios de EIPSI
            const containers = document.querySelectorAll(
                '.eipsi-randomization-container, .eipsi-form-container, [data-eipsi-form]'
            );

            containers.forEach( function ( container ) {
                // Crear input hidden con todos los datos RAW
                let deviceDataInput = container.querySelector(
                    'input[name="eipsi_device_data"]'
                );

                if ( ! deviceDataInput ) {
                    deviceDataInput = document.createElement( 'input' );
                    deviceDataInput.type = 'hidden';
                    deviceDataInput.name = 'eipsi_device_data';
                    container.appendChild( deviceDataInput );
                }

                deviceDataInput.value = JSON.stringify( deviceData );

                // Agregar data-attribute para debugging
                container.setAttribute(
                    'data-device-captured',
                    'true'
                );
            } );

            // También buscar formularios EIPSI por clase
            const eipsiForms = document.querySelectorAll(
                'form.eipsi-survey-form, form[data-eipsi-survey]'
            );

            eipsiForms.forEach( function ( form ) {
                let deviceDataInput = form.querySelector(
                    'input[name="eipsi_device_data"]'
                );

                if ( ! deviceDataInput ) {
                    deviceDataInput = document.createElement( 'input' );
                    deviceDataInput.type = 'hidden';
                    deviceDataInput.name = 'eipsi_device_data';
                    form.appendChild( deviceDataInput );
                }

                deviceDataInput.value = JSON.stringify( deviceData );
            } );

            // eslint-disable-next-line no-console
            console.log(
                '[EIPSI Forms] Device data captured:',
                deviceData
            );
        } catch ( e ) {
            // eslint-disable-next-line no-console
            console.error( '[EIPSI Forms] Device data capture error:', e );
        }
    } );

    // ============================================================================
    // BACKWARD COMPATIBILITY - Mantener funciones anteriores
    // ============================================================================

    /**
     * @deprecated Use eipsiGetDeviceData() instead
     * Mantenido por compatibilidad con código existente
     */
    window.eipsiGetFingerprint = function() {
        // eslint-disable-next-line no-console
        console.warn( '[EIPSI Forms] eipsiGetFingerprint() is deprecated. Use eipsiGetDeviceData() instead.' );
        const data = getDeviceData();
        return {
            fingerprint: 'raw-data-available',
            rawDetails: data
        };
    };
} )();
