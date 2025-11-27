/**
 * DARK MODE v2.0 - REACTIVATED Feb 2025
 *
 * Auditoría completa: DARK_MODE_AUDIT.md
 * 
 * CAMBIOS IMPLEMENTADOS:
 * ✅ Variables CSS completas para VAS slider, success messages, progress, cards
 * ✅ Presets específicos con dark mode personalizado (Clinical Blue, Warm Neutral, Serene Teal, Minimal White)
 * ✅ Toggle UI reactivado con mejor UX
 * ✅ Persistencia en localStorage + respeto de prefers-color-scheme
 * ✅ Contraste WCAG AA verificado
 *
 * @package EIPSI Forms
 * @version 2.0.0
 */

/* global localStorage */

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
                        ? 'Cambiar a modo diurno'
                        : 'Cambiar a modo nocturno'
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
