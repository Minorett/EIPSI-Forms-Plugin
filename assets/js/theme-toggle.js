/**
 * EIPSI Forms - Dark Mode Toggle
 * Universal semantic dark mode system with localStorage persistence
 *
 * @package
 * @version 3.0.0
 */

/* global localStorage */

( function () {
	'use strict';

	const STORAGE_KEY = 'eipsi-theme';
	const THEME_LIGHT = 'light';
	const THEME_DARK = 'dark';

	/**
	 * Initialize theme toggle on DOM ready
	 */
	const initThemeToggle = () => {
		const html = document.documentElement;
		const toggles = document.querySelectorAll( '.eipsi-toggle' );

		if ( ! toggles.length ) {
			return;
		}

		/**
		 * Set theme and persist to localStorage
		 *
		 * @param {string} theme - Theme name ('light' or 'dark')
		 */
		const setTheme = ( theme ) => {
			html.dataset.theme = theme;
			localStorage.setItem( STORAGE_KEY, theme );

			// Update all toggle button labels
			const label = theme === THEME_DARK ? '☀️ Diurno' : '🌙 Nocturno';
			const mobileLabel = theme === THEME_DARK ? '☀️' : '🌙';

			toggles.forEach( ( toggle ) => {
				// Mobile toggles show only emoji
				if ( toggle.closest( '.eipsi-toggle-mobile' ) ) {
					toggle.textContent = mobileLabel;
				} else {
					toggle.textContent = label;
				}

				// Update aria-label for accessibility
				toggle.setAttribute(
					'aria-label',
					theme === THEME_DARK
						? 'Switch to light mode'
						: 'Switch to dark mode'
				);
			} );
		};

		/**
		 * Initialize theme on page load
		 * Priority: localStorage > system preference > default (light)
		 */
		const initTheme = () => {
			const saved = localStorage.getItem( STORAGE_KEY );
			const prefersDark = window.matchMedia(
				'(prefers-color-scheme: dark)'
			).matches;
			const theme = saved || ( prefersDark ? THEME_DARK : THEME_LIGHT );
			setTheme( theme );
		};

		/**
		 * Toggle between light and dark modes
		 *
		 * @param {HTMLElement} button - The button that was clicked
		 */
		const toggleTheme = async ( button ) => {
			button.classList.add( 'eipsi-toggle--loading' );

			// Simulate loading for visual feedback
			await new Promise( ( resolve ) => setTimeout( resolve, 150 ) );

			const newTheme =
				html.dataset.theme === THEME_DARK ? THEME_LIGHT : THEME_DARK;
			setTheme( newTheme );

			button.classList.remove( 'eipsi-toggle--loading' );

			// Focus management for accessibility
			button.focus();
		};

		/**
		 * Handle system preference changes
		 * Only sync if user hasn't set a manual preference
		 *
		 * @param {MediaQueryListEvent} e - Media query change event
		 */
		const handleSystemPreferenceChange = ( e ) => {
			if ( ! localStorage.getItem( STORAGE_KEY ) ) {
				setTheme( e.matches ? THEME_DARK : THEME_LIGHT );
			}
		};

		// Initialize theme
		initTheme();

		// Add click listeners to all toggles
		toggles.forEach( ( button ) => {
			button.addEventListener( 'click', () => toggleTheme( button ) );
		} );

		// Sync with system preference changes
		window
			.matchMedia( '(prefers-color-scheme: dark)' )
			.addEventListener( 'change', handleSystemPreferenceChange );

		// Keyboard shortcut (Ctrl/Cmd + Shift + D)
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

		// Expose API for programmatic control
		window.eipsiTheme = {
			getTheme: () => html.dataset.theme,
			setTheme,
			toggle: () => {
				const newTheme =
					html.dataset.theme === THEME_DARK
						? THEME_LIGHT
						: THEME_DARK;
				setTheme( newTheme );
			},
		};
	};

	// Initialize when DOM is ready
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initThemeToggle );
	} else {
		initThemeToggle();
	}
} )();
