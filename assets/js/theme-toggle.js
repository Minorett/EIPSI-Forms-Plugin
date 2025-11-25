/**
 * DARK MODE TEMPORARILY DISABLED - Feb 2025
 *
 * Reason:
 * - VAS slider no adaptado a dark mode (colores de light → ilegibles)
 * - Mensajes de éxito con gradientes mezclando light/dark
 * - Presets (Clinical Blue, Warm Neutral, etc.) pierden identidad en dark
 *
 * Auditoría completa: DARK_MODE_AUDIT.md
 *
 * PARA REACTIVAR:
 * 1. Descomentar el toggle en form-container/save.js (JSX del botón)
 * 2. Restaurar el código ejecutable de este archivo (ver historial git)
 * 3. Completar las variables faltantes en _theme-toggle.scss (ver anexo del audit)
 * 4. Ejecutar el testing checklist de DARK_MODE_AUDIT.md
 *
 * @package EIPSI Forms
 * @version 4.0.0 (DISABLED)
 */

/* global localStorage */

console.log( '[EIPSI Forms] Dark mode temporarily disabled. See DARK_MODE_AUDIT.md for details.' );

// ============================================================================
// CÓDIGO ORIGINAL DESACTIVADO (mantener para referencia futura)
// ============================================================================
/*
( function () {
    'use strict';

    const STORAGE_KEY = 'eipsi-theme';
    const THEME_LIGHT = 'light';
    const THEME_DARK = 'dark';
    const DEFAULT_PRESET = 'Clinical Blue';

    const initThemeToggle = () => {
        const forms = document.querySelectorAll( '.vas-dinamico-form' );
        const toggles = document.querySelectorAll( '.eipsi-toggle' );

        if ( ! toggles.length || ! forms.length ) {
            return;
        }

        const setTheme = ( theme ) => {
            forms.forEach( ( form ) => {
                form.dataset.theme = theme;
            } );

            localStorage.setItem( STORAGE_KEY, theme );

            const label = theme === THEME_DARK ? '☀️ Diurno' : '🌙 Nocturno';

            toggles.forEach( ( toggle ) => {
                toggle.textContent = label;

                toggle.setAttribute(
                    'aria-label',
                    theme === THEME_DARK
                        ? 'Switch to light mode'
                        : 'Switch to dark mode'
                );
            } );
        };

        const initTheme = () => {
            const saved = localStorage.getItem( STORAGE_KEY );
            const prefersDark = window.matchMedia(
                '(prefers-color-scheme: dark)'
            ).matches;
            const theme = saved || ( prefersDark ? THEME_DARK : THEME_LIGHT );
            setTheme( theme );

            forms.forEach( ( form ) => {
                if ( ! form.dataset.preset ) {
                    form.dataset.preset = DEFAULT_PRESET;
                }
            } );
        };

        const toggleTheme = async ( button ) => {
            button.classList.add( 'eipsi-toggle--loading' );

            await new Promise( ( resolve ) => setTimeout( resolve, 150 ) );

            const currentTheme = forms[ 0 ]?.dataset.theme || THEME_LIGHT;
            const newTheme =
                currentTheme === THEME_DARK ? THEME_LIGHT : THEME_DARK;
            setTheme( newTheme );

            button.classList.remove( 'eipsi-toggle--loading' );

            button.focus();
        };

        const handleSystemPreferenceChange = ( e ) => {
            if ( ! localStorage.getItem( STORAGE_KEY ) ) {
                setTheme( e.matches ? THEME_DARK : THEME_LIGHT );
            }
        };

        initTheme();

        toggles.forEach( ( button ) => {
            button.addEventListener( 'click', () => toggleTheme( button ) );
        } );

        window
            .matchMedia( '(prefers-color-scheme: dark)' )
            .addEventListener( 'change', handleSystemPreferenceChange );

        document.addEventListener( 'keydown', ( e ) => {
            if (
                ( e.ctrlKey || e.metaKey ) &&
                e.shiftKey &&
                e.key.toLowerCase() === 'd'
            ) {
                e.preventDefault();
                if ( toggles[ 0 ] ) {
                    toggleTheme( toggles[ 0 ] );
                }
            }
        } );

        window.eipsiTheme = {
            getTheme: () => forms[ 0 ]?.dataset.theme || THEME_LIGHT,
            setTheme,
            toggle: () => {
                const currentTheme = forms[ 0 ]?.dataset.theme || THEME_LIGHT;
                const newTheme =
                    currentTheme === THEME_DARK ? THEME_LIGHT : THEME_DARK;
                setTheme( newTheme );
            },
        };
    };

    if ( document.readyState === 'loading' ) {
        document.addEventListener( 'DOMContentLoaded', initThemeToggle );
    } else {
        initThemeToggle();
    }
} )();
*/
