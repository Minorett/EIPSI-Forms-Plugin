/**
 * EIPSI Forms - Save & Continue v1
 * Guarda respuestas parciales (server + IndexedDB) y permite reanudar sesiones
 *
 * @since 1.3.0
 */

( function () {
	'use strict';

	/* global navigator, CSS */

	if ( typeof window === 'undefined' ) {
		return;
	}

	const AUTOSAVE_INTERVAL = 30000; // 30 segundos
	const INPUT_DEBOUNCE = 800; // ms
	const IDB_NAME = 'eipsi_forms';
	const IDB_VERSION = 1;
	const IDB_STORE = 'partial_responses';
	const EXCLUDED_FIELDS = new Set( [
		'form_id',
		'form_action',
		'ip_address',
		'device',
		'browser',
		'os',
		'screen_width',
		'form_start_time',
		'form_end_time',
		'current_page',
		'nonce',
		'action',
		'participant_id',
		'session_id',
		'eipsi_forms_nonce',
	] );

	class EIPSISaveContinue {
		constructor( form, config ) {
			this.form = form;
			this.config = config || {};
			this.formId = this.getFormId();
			this.participantId = this.getParticipantId();
			this.sessionId = this.getSessionId();
			this.autosaveTimer = null;
			this.db = null;
			this.pendingSync = false;
			this.initialized = false;
			this.completed = false;
			this.hasResponses = false;
			this.beforeUnloadHandler = null;
			this.inputDebounceId = null;

			this.init();
		}

		async init() {
			try {
				this.db = await this.openDB();
				this.initialized = true;

				await this.checkForPartialResponse();
				this.setupAutosave();
				this.setupBeforeUnload();
				this.setupChangeListeners();
			} catch ( error ) {
				if ( window.console && window.console.error ) {
					window.console.error(
						'[EIPSI Save & Continue] Initialization failed:',
						error
					);
				}
			}
		}

		getFormId() {
			return (
				this.form?.dataset?.formId ||
				this.form?.querySelector( 'input[name="form_id"]' )?.value ||
				'default'
			);
		}

		getParticipantId() {
			const STORAGE_KEY = 'eipsi_participant_id';
			let pid = null;

			try {
				pid = window.localStorage.getItem( STORAGE_KEY );
			} catch ( error ) {
				pid = null;
			}

			if ( ! pid ) {
				const randomSource = crypto.randomUUID
					? crypto.randomUUID().replace( /-/g, '' )
					: `${ Math.random()
							.toString( 36 )
							.substring( 2 ) }${ Date.now().toString( 36 ) }`;
				pid = `p-${ randomSource.substring( 0, 12 ) }`;

				try {
					window.localStorage.setItem( STORAGE_KEY, pid );
				} catch ( error ) {
					// Ignore storage errors (Safari private mode, etc.)
				}
			}

			return pid;
		}

		getSessionId() {
			const SESSION_KEY = `eipsi_session_${ this.formId || 'default' }`;
			let sid = null;

			try {
				sid = window.sessionStorage.getItem( SESSION_KEY );
			} catch ( error ) {
				sid = null;
			}

			if ( ! sid ) {
				const timestamp = Date.now();
				const random = Math.random().toString( 36 ).substring( 2, 8 );
				sid = `sess-${ timestamp }-${ random }`;

				try {
					window.sessionStorage.setItem( SESSION_KEY, sid );
				} catch ( error ) {
					// Ignore storage errors
				}
			}

			return sid;
		}

		openDB() {
			if ( ! window.indexedDB ) {
				return Promise.resolve( null );
			}

			return new Promise( ( resolve, reject ) => {
				const request = window.indexedDB.open( IDB_NAME, IDB_VERSION );

				request.onerror = () =>
					reject( new Error( 'IndexedDB unavailable' ) );
				request.onsuccess = () => resolve( request.result );
				request.onupgradeneeded = ( event ) => {
					const db = event.target.result;
					if ( ! db.objectStoreNames.contains( IDB_STORE ) ) {
						db.createObjectStore( IDB_STORE, {
							keyPath: [
								'form_id',
								'participant_id',
								'session_id',
							],
						} );
					}
				};
			} );
		}

		async checkForPartialResponse() {
			const serverPartial = await this.loadFromServer();
			if (
				serverPartial &&
				serverPartial.found &&
				serverPartial.partial
			) {
				this.showRecoveryPopup( serverPartial.partial );
				return;
			}

			const localPartial = await this.loadFromIDB();
			if ( localPartial ) {
				this.showRecoveryPopup( localPartial );
			}
		}

		async loadFromServer() {
			if ( ! this.config.ajaxUrl ) {
				return null;
			}

			try {
				const formData = new FormData();
				formData.append( 'action', 'eipsi_load_partial_response' );
				formData.append( 'form_id', this.formId );
				formData.append( 'participant_id', this.participantId );
				formData.append( 'session_id', this.sessionId );

				const response = await fetch( this.config.ajaxUrl, {
					method: 'POST',
					body: formData,
					credentials: 'same-origin',
				} );

				const data = await response.json();
				return data.success ? data.data : null;
			} catch ( error ) {
				return null;
			}
		}

		async loadFromIDB() {
			if ( ! this.db ) {
				return null;
			}

			return new Promise( ( resolve ) => {
				const transaction = this.db.transaction(
					[ IDB_STORE ],
					'readonly'
				);
				const store = transaction.objectStore( IDB_STORE );
				const key = [ this.formId, this.participantId, this.sessionId ];
				const request = store.get( key );

				request.onsuccess = () => resolve( request.result || null );
				request.onerror = () => resolve( null );
			} );
		}

		showRecoveryPopup( partial ) {
			if ( document.querySelector( '.eipsi-recovery-popup' ) ) {
				return;
			}

			const rawUpdatedAt = partial.updated_at || new Date().toISOString();
			const normalizedDate = new Date(
				typeof rawUpdatedAt === 'string'
					? rawUpdatedAt.replace( ' ', 'T' )
					: rawUpdatedAt
			);
			const dateStr = Number.isNaN( normalizedDate.getTime() )
				? 'tu última sesión'
				: normalizedDate.toLocaleString( 'es', {
						year: 'numeric',
						month: 'long',
						day: 'numeric',
						hour: '2-digit',
						minute: '2-digit',
				  } );

			const popup = document.createElement( 'div' );
			popup.className = 'eipsi-recovery-popup';
			popup.innerHTML = `
                <div class="eipsi-recovery-overlay" aria-hidden="true"></div>
                <div class="eipsi-recovery-modal" role="dialog" aria-live="polite">
                    <h3>Continuar donde quedaste</h3>
                    <p>Tenés respuestas guardadas del <strong>${ dateStr }</strong>.</p>
                    <p>¿Querés continuar donde quedaste?</p>
                    <div class="eipsi-recovery-buttons">
                        <button type="button" class="eipsi-btn eipsi-btn-primary" data-action="continue">
                            Continuar
                        </button>
                        <button type="button" class="eipsi-btn eipsi-btn-secondary" data-action="restart">
                            Empezar de nuevo
                        </button>
                    </div>
                </div>
            `;

			document.body.appendChild( popup );

			popup
				.querySelector( '[data-action="continue"]' )
				?.addEventListener( 'click', () => {
					this.restorePartial( partial );
					popup.remove();
				} );

			popup
				.querySelector( '[data-action="restart"]' )
				?.addEventListener( 'click', async () => {
					await this.discardPartial();
					popup.remove();
				} );
		}

		restorePartial( partial ) {
			const responses = partial.responses || {};
			const pageIndex = partial.page_index || 1;

			Object.keys( responses ).forEach( ( fieldName ) => {
				this.setFieldValue( fieldName, responses[ fieldName ] );
			} );

			this.hasResponses = Object.keys( responses ).length > 0;

			if ( window.EIPSIForms ) {
				window.EIPSIForms.setCurrentPage( this.form, pageIndex, {
					trackChange: false,
				} );

				const navigator = window.EIPSIForms.getNavigator( this.form );
				if ( navigator && navigator.reset ) {
					navigator.reset();
					navigator.pushHistory( pageIndex );
				}
			}
		}

		setFieldValue( fieldName, value ) {
			if ( value === undefined || value === null ) {
				return;
			}

			const fields = this.form.querySelectorAll(
				`[name="${ fieldName }"]`
			);

			if ( fields.length > 1 ) {
				fields.forEach( ( field ) => {
					if ( field.type === 'radio' ) {
						field.checked = field.value === value;
					} else if ( field.type === 'checkbox' ) {
						if ( Array.isArray( value ) ) {
							field.checked = value.includes( field.value );
						} else {
							field.checked =
								value === true ||
								value === 'true' ||
								field.value === value;
						}
					}
				} );
				return;
			}

			const safeFieldName =
				window.CSS && window.CSS.escape
					? CSS.escape( fieldName )
					: fieldName.replace(
							/([ #.;?*+~'"^$\[\]()=>|/@])/g,
							'\\$1'
					  );

			const field =
				fields[ 0 ] ||
				this.form.querySelector( `[id="${ safeFieldName }"]` );

			if ( ! field ) {
				return;
			}

			if ( field.type === 'checkbox' ) {
				if ( Array.isArray( value ) ) {
					field.checked = value.includes( field.value );
				} else {
					field.checked =
						value === true ||
						value === 'true' ||
						field.value === value;
				}
				return;
			}

			if (
				field.tagName === 'SELECT' &&
				field.multiple &&
				Array.isArray( value )
			) {
				Array.from( field.options ).forEach( ( option ) => {
					option.selected = value.includes( option.value );
				} );
				return;
			}

			const normalized = Array.isArray( value ) ? value[ 0 ] : value;
			field.value = normalized;

			if ( field.type === 'range' ) {
				field.dispatchEvent( new Event( 'input', { bubbles: true } ) );
			}
		}

		async discardPartial() {
			await this.clearFromIDB();
			await this.discardFromServer();
			this.hasResponses = false;
			this.completed = false;
		}

		async clearFromIDB() {
			if ( ! this.db ) {
				this.hasResponses = false;
				return false;
			}

			return new Promise( ( resolve ) => {
				const transaction = this.db.transaction(
					[ IDB_STORE ],
					'readwrite'
				);
				const store = transaction.objectStore( IDB_STORE );
				const key = [ this.formId, this.participantId, this.sessionId ];
				const request = store.delete( key );

				request.onsuccess = () => {
					this.hasResponses = false;
					resolve( true );
				};
				request.onerror = () => resolve( false );
			} );
		}

		async discardFromServer() {
			if ( ! this.config.ajaxUrl ) {
				return;
			}

			try {
				const formData = new URLSearchParams();
				formData.append( 'action', 'eipsi_discard_partial_response' );
				formData.append( 'form_id', this.formId );
				formData.append( 'participant_id', this.participantId );
				formData.append( 'session_id', this.sessionId );

				await fetch( this.config.ajaxUrl, {
					method: 'POST',
					body: formData,
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded',
					},
					keepalive: true,
					credentials: 'same-origin',
				} );
			} catch ( error ) {
				// Silencioso: mientras IndexedDB se limpie, el usuario puede continuar
			}
		}

		setupAutosave() {
			if ( this.autosaveTimer ) {
				clearInterval( this.autosaveTimer );
			}

			this.autosaveTimer = window.setInterval( () => {
				this.savePartial( 'auto' );
			}, AUTOSAVE_INTERVAL );
		}

		setupBeforeUnload() {
			if ( this.beforeUnloadHandler ) {
				return;
			}

			this.beforeUnloadHandler = ( event ) => {
				if ( this.completed ) {
					return;
				}

				this.savePartialSync();

				if ( this.hasResponses ) {
					const message =
						'Tus respuestas se están guardando. Podés volver cuando quieras.';
					event.preventDefault();
					event.returnValue = message;
					return message;
				}

				return undefined;
			};

			window.addEventListener( 'beforeunload', this.beforeUnloadHandler );
		}

		removeBeforeUnload() {
			if ( this.beforeUnloadHandler ) {
				window.removeEventListener(
					'beforeunload',
					this.beforeUnloadHandler
				);
				this.beforeUnloadHandler = null;
			}
		}

		setupChangeListeners() {
			const fields = this.form.querySelectorAll(
				'input, textarea, select'
			);

			fields.forEach( ( field ) => {
				field.addEventListener( 'input', () =>
					this.handleFieldInput()
				);
				field.addEventListener( 'change', () =>
					this.savePartial( 'field-change' )
				);
			} );
		}

		handleFieldInput() {
			if ( this.completed ) {
				return;
			}

			if ( this.inputDebounceId ) {
				clearTimeout( this.inputDebounceId );
			}

			this.inputDebounceId = window.setTimeout( () => {
				this.saveToIDB();
			}, INPUT_DEBOUNCE );
		}

		async savePartial( trigger = 'manual' ) {
			if ( this.completed || this.pendingSync ) {
				return;
			}

			this.pendingSync = true;

			try {
				const responses = this.collectResponses();
				const currentPage = this.getCurrentPage();
				this.hasResponses = Object.keys( responses ).length > 0;

				if (
					this.config?.settings?.debug &&
					window.console &&
					typeof window.console.debug === 'function'
				) {
					window.console.debug(
						'[EIPSI Save & Continue] Guardando borrador',
						{
							trigger,
							page: currentPage,
							hasResponses: this.hasResponses,
						}
					);
				}

				await this.saveToIDB( responses, currentPage );
				await this.saveToServer( responses, currentPage );
			} catch ( error ) {
				if ( window.console && window.console.warn ) {
					window.console.warn(
						'[EIPSI Save & Continue] Save failed:',
						error
					);
				}
			} finally {
				this.pendingSync = false;
			}
		}

		savePartialSync() {
			if ( this.completed || ! this.config.ajaxUrl ) {
				return;
			}

			const responses = this.collectResponses();
			const currentPage = this.getCurrentPage();

			const payload = new URLSearchParams();
			payload.append( 'action', 'eipsi_save_partial_response' );
			payload.append( 'form_id', this.formId );
			payload.append( 'participant_id', this.participantId );
			payload.append( 'session_id', this.sessionId );
			payload.append( 'page_index', currentPage );
			payload.append( 'responses', JSON.stringify( responses ) );

			const bodyString = payload.toString();

			if ( navigator.sendBeacon ) {
				const blob = new Blob( [ bodyString ], {
					type: 'application/x-www-form-urlencoded',
				} );
				navigator.sendBeacon( this.config.ajaxUrl, blob );
			} else {
				fetch( this.config.ajaxUrl, {
					method: 'POST',
					body: bodyString,
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded',
					},
					keepalive: true,
					credentials: 'same-origin',
				} ).catch( () => {} );
			}

			this.saveToIDB( responses, currentPage );
		}

		collectResponses() {
			const responses = {};
			const formData = new FormData( this.form );

			formData.forEach( ( value, key ) => {
				if ( EXCLUDED_FIELDS.has( key ) ) {
					return;
				}

				if ( value instanceof File ) {
					return;
				}

				const normalized =
					typeof value === 'string' ? value : `${ value }`;

				if ( responses[ key ] !== undefined ) {
					if ( ! Array.isArray( responses[ key ] ) ) {
						responses[ key ] = [ responses[ key ] ];
					}
					responses[ key ].push( normalized );
				} else {
					responses[ key ] = normalized;
				}
			} );

			return responses;
		}

		getCurrentPage() {
			const pageInput = this.form.querySelector( '.eipsi-current-page' );
			return pageInput ? parseInt( pageInput.value, 10 ) || 1 : 1;
		}

		async saveToIDB( responses = null, pageIndex = null ) {
			if ( ! this.db ) {
				return false;
			}

			const payload = {
				form_id: this.formId,
				participant_id: this.participantId,
				session_id: this.sessionId,
				page_index: pageIndex || this.getCurrentPage(),
				responses: responses || this.collectResponses(),
				updated_at: new Date().toISOString(),
			};

			return new Promise( ( resolve ) => {
				const transaction = this.db.transaction(
					[ IDB_STORE ],
					'readwrite'
				);
				const store = transaction.objectStore( IDB_STORE );
				const request = store.put( payload );

				request.onsuccess = () => resolve( true );
				request.onerror = () => resolve( false );
			} );
		}

		async saveToServer( responses = null, pageIndex = null ) {
			if ( ! this.config.ajaxUrl ) {
				return false;
			}

			try {
				const formData = new FormData();
				formData.append( 'action', 'eipsi_save_partial_response' );
				formData.append( 'form_id', this.formId );
				formData.append( 'participant_id', this.participantId );
				formData.append( 'session_id', this.sessionId );
				formData.append(
					'page_index',
					pageIndex || this.getCurrentPage()
				);
				formData.append(
					'responses',
					JSON.stringify( responses || this.collectResponses() )
				);

				const response = await fetch( this.config.ajaxUrl, {
					method: 'POST',
					body: formData,
					keepalive: true,
					credentials: 'same-origin',
				} );

				const data = await response.json();
				return !! data.success;
			} catch ( error ) {
				return false;
			}
		}

		handleFormCompleted() {
			this.completed = true;
			this.clearFromIDB();
			this.removeBeforeUnload();

			if ( this.autosaveTimer ) {
				clearInterval( this.autosaveTimer );
				this.autosaveTimer = null;
			}

			if ( this.inputDebounceId ) {
				clearTimeout( this.inputDebounceId );
				this.inputDebounceId = null;
			}
		}

		destroy() {
			this.handleFormCompleted();
			if ( this.db ) {
				this.db.close();
				this.db = null;
			}
		}
	}

	document.addEventListener( 'DOMContentLoaded', () => {
		const forms = document.querySelectorAll(
			'.eipsi-form form, .eipsi-form form'
		);

		forms.forEach( ( form ) => {
			if ( ! window.eipsiFormsConfig || form.eipsiSaveContinue ) {
				return;
			}

			const instance = new EIPSISaveContinue(
				form,
				window.eipsiFormsConfig
			);
			form.eipsiSaveContinue = instance;
		} );
	} );

	window.EIPSISaveContinue = EIPSISaveContinue;
} )();
