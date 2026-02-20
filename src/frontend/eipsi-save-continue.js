/**
 * EIPSI Forms - Save & Continue v1
 * Guarda respuestas parciales (server + IndexedDB) y permite reanudar sesiones
 *
 * @since 1.3.0
 */

( function () {
    'use strict';

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
            popup.setAttribute( 'data-modal-id', Date.now() );
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

            const continueButton = popup.querySelector(
                '[data-action="continue"]'
            );
            const restartButton = popup.querySelector(
                '[data-action="restart"]'
            );

            if ( continueButton ) {
                continueButton.addEventListener( 'click', async () => {
                    // Deshabilitar botón para evitar múltiples clics
                    continueButton.disabled = true;
                    continueButton.textContent = 'Cargando...';

                    try {
                        // Paso 1: Esperar a que el formulario esté listo
                        await this.waitForFormReady();

                        // Paso 2: Restaurar datos (async)
                        await this.restorePartial( partial );

                        // Paso 3: Asegurar que el formulario sea visible
                        await this.ensureFormVisible();

                        // Paso 4: Esperar un frame extra para que todo se renderice
                        await new Promise( ( resolve ) => {
                            requestAnimationFrame( () => {
                                requestAnimationFrame( resolve );
                            } );
                        } );

                        // Paso 5: Remover el modal completamente
                        this.closeRecoveryPopup( popup );
                    } catch ( error ) {
                        // Si algo falla, mostrar error en consola pero cerrar modal de todas formas
                        if (
                            this.config?.settings?.debug &&
                            window.console?.error
                        ) {
                            window.console.error(
                                '[EIPSI Save & Continue] Error al restaurar sesión:',
                                error
                            );
                        }

                        // Forzar visibilidad del formulario
                        this.forceFormVisible();

                        // Cerrar modal de todas formas
                        this.closeRecoveryPopup( popup );
                    }
                } );
            }

            if ( restartButton ) {
                restartButton.addEventListener( 'click', async () => {
                    restartButton.disabled = true;
                    restartButton.textContent = 'Borrando...';

                    try {
                        // PASO 1: Cerrar sesión de aleatorización (si existe)
                        await this.closeRandomizationSession();

                        // PASO 2: Descartar respuestas parciales
                        await this.discardPartial();

                        // PASO 3: Forzar visibilidad del formulario
                        this.forceFormVisible();

                        // PASO 4: Cerrar modal de recuperación
                        this.closeRecoveryPopup( popup );
                    } catch ( error ) {
                        if (
                            this.config?.settings?.debug &&
                            window.console?.error
                        ) {
                            window.console.error(
                                '[EIPSI Save & Continue] Error al descartar sesión:',
                                error
                            );
                        }
                        // Cerrar modal de todas formas
                        this.closeRecoveryPopup( popup );
                    }
                } );
            }
        }

        closeRecoveryPopup( popup ) {
            if ( ! popup || ! popup.parentNode ) {
                return;
            }

            // Remover overlay primero para evitar capturar clics
            const overlay = popup.querySelector( '.eipsi-recovery-overlay' );
            if ( overlay ) {
                overlay.style.pointerEvents = 'none';
                overlay.style.opacity = '0';
            }

            // Remover modal con transición suave
            const modal = popup.querySelector( '.eipsi-recovery-modal' );
            if ( modal ) {
                modal.style.opacity = '0';
            }

            // Remover del DOM después de la transición
            setTimeout( () => {
                if ( popup.parentNode ) {
                    popup.parentNode.removeChild( popup );
                }
            }, 200 );
        }

        waitForFormReady() {
            const TIMEOUT_MS = 10000;
            const CHECK_INTERVAL_MS = 100;

            return new Promise( ( resolve ) => {
                const startTime = Date.now();

                const checkReady = () => {
                    if ( Date.now() - startTime > TIMEOUT_MS ) {
                        // Timeout: resolver de todas formas (mejor que quedar colgado)
                        if (
                            this.config?.settings?.debug &&
                            window.console?.warn
                        ) {
                            window.console.warn(
                                '[EIPSI Save & Continue] Timeout esperando formulario, continuando...'
                            );
                        }
                        resolve();
                        return;
                    }

                    // Verificar que el formulario existe
                    if ( ! this.form || ! this.form.parentNode ) {
                        setTimeout( checkReady, CHECK_INTERVAL_MS );
                        return;
                    }

                    // Verificar que el formulario sea visible
                    const hasDimensions =
                        this.form.offsetHeight > 0 && this.form.offsetWidth > 0;
                    const isVisible =
                        this.form.style.display !== 'none' &&
                        this.form.style.visibility !== 'hidden';

                    if ( hasDimensions || isVisible ) {
                        resolve();
                    } else {
                        setTimeout( checkReady, CHECK_INTERVAL_MS );
                    }
                };

                checkReady();
            } );
        }

        ensureFormVisible() {
            return new Promise( ( resolve ) => {
                if ( ! this.form ) {
                    resolve();
                    return;
                }

                // Remover clases de ocultamiento
                this.form.classList.remove(
                    'hidden',
                    'eipsi-hidden',
                    'eipsi-form-hidden'
                );

                // Aplicar estilos inline para asegurar visibilidad
                this.form.style.display = 'block';
                this.form.style.visibility = 'visible';
                this.form.style.opacity = '1';

                // También asegurarse de que el contenedor padre sea visible
                const formContainer = this.form.closest( '.eipsi-form' );
                if ( formContainer ) {
                    formContainer.classList.remove(
                        'hidden',
                        'eipsi-hidden',
                        'eipsi-form-hidden'
                    );
                    formContainer.style.display = 'block';
                    formContainer.style.visibility = 'visible';
                    formContainer.style.opacity = '1';
                }

                // Esperar a que los estilos se apliquen usando requestAnimationFrame
                requestAnimationFrame( () => {
                    // Segundo frame para asegurar que el navegador procesó los cambios
                    requestAnimationFrame( () => {
                        resolve();
                    } );
                } );
            } );
        }

        forceFormVisible() {
            if ( ! this.form ) {
                return;
            }

            // Forzar visibilidad sin importar el estado actual
            this.form.style.setProperty( 'display', 'block', 'important' );
            this.form.style.setProperty( 'visibility', 'visible', 'important' );
            this.form.style.setProperty( 'opacity', '1', 'important' );
            this.form.style.setProperty(
                'pointer-events',
                'auto',
                'important'
            );

            const formContainer = this.form.closest( '.eipsi-form' );
            if ( formContainer ) {
                formContainer.style.setProperty(
                    'display',
                    'block',
                    'important'
                );
                formContainer.style.setProperty(
                    'visibility',
                    'visible',
                    'important'
                );
                formContainer.style.setProperty( 'opacity', '1', 'important' );
                formContainer.style.setProperty(
                    'pointer-events',
                    'auto',
                    'important'
                );
            }
        }

        async restorePartial( partial ) {
            const responses = partial.responses || {};
            const pageIndex = partial.page_index || 1;

            // Restaurar los valores de los campos
            Object.keys( responses ).forEach( ( fieldName ) => {
                this.setFieldValue( fieldName, responses[ fieldName ] );
            } );

            this.hasResponses = Object.keys( responses ).length > 0;

            // Esperar un frame para que los campos se actualicen en el DOM
            await new Promise( ( resolve ) => {
                requestAnimationFrame( () => {
                    requestAnimationFrame( resolve );
                } );
            } );

            // Configurar la página actual si EIPSIForms está disponible
            if (
                window.EIPSIForms &&
                typeof window.EIPSIForms.setCurrentPage === 'function'
            ) {
                try {
                    window.EIPSIForms.setCurrentPage( this.form, pageIndex, {
                        trackChange: false,
                    } );

                    // Esperar a que la página se renderice
                    await new Promise( ( resolve ) => {
                        requestAnimationFrame( () => {
                            requestAnimationFrame( resolve );
                        } );
                    } );

                    // Actualizar el navegador de historial
                    const navigator = window.EIPSIForms.getNavigator(
                        this.form
                    );
                    if ( navigator && navigator.reset ) {
                        navigator.reset();
                        navigator.pushHistory( pageIndex );
                    }
                } catch ( error ) {
                    // Si setCurrentPage falla, continuar sin error crítico
                    if (
                        this.config?.settings?.debug &&
                        window.console?.warn
                    ) {
                        window.console.warn(
                            '[EIPSI Save & Continue] Error al establecer página:',
                            error
                        );
                    }
                }
            }
        }

        /**
         * Obtener fingerprint del usuario desde localStorage/cookie
         *
         * @return {string|null} Fingerprint del usuario o null
         */
        getUserFingerprint() {
            // OPCIÓN 1: Intentar desde localStorage (usado por fingerprinting)
            try {
                const fpData = window.localStorage.getItem(
                    'eipsi_user_fingerprint'
                );
                if ( fpData ) {
                    const parsed = JSON.parse( fpData );
                    if ( parsed && parsed.fingerprint ) {
                        return parsed.fingerprint;
                    }
                }
            } catch ( error ) {
                // Ignore localStorage errors
            }

            // OPCIÓN 2: Intentar desde cookie (fallback para navegadores con localStorage deshabilitado)
            if ( document.cookie ) {
                const cookies = document.cookie.split( ';' );
                for ( let i = 0; i < cookies.length; i++ ) {
                    const cookie = cookies[ i ].trim();
                    if ( cookie.startsWith( 'eipsi_fingerprint=' ) ) {
                        return cookie.substring( 'eipsi_fingerprint='.length );
                    }
                }
            }

            // OPCIÓN 3: Intentar desde sessionStorage (usado en algunos flujos)
            try {
                const sessionFp =
                    window.sessionStorage.getItem( 'eipsi_fingerprint' );
                if ( sessionFp ) {
                    return sessionFp;
                }
            } catch ( error ) {
                // Ignore sessionStorage errors
            }

            return null;
        }

        /**
         * Obtener randomization_id desde el contenedor de aleatorización
         *
         * @return {string|null} Randomization ID o null
         */
        getRandomizationId() {
            // Buscar el contenedor de aleatorización en el árbol del formulario
            const container = this.form.closest(
                '.eipsi-randomization-container'
            );
            if ( container ) {
                return container.getAttribute( 'data-randomization-id' );
            }
            return null;
        }

        /**
         * Cerrar sesión de aleatorización (persistent_mode=OFF)
         *
         * Elimina la asignación del usuario de la DB y borra la cookie de rotación.
         * Si no hay randomization_id o fingerprint, no hace nada (sin errores).
         *
         * @return {Promise<boolean>} True si se cerró correctamente o no hay aleatorización
         */
        async closeRandomizationSession() {
            const randomizationId = this.getRandomizationId();
            const userFingerprint = this.getUserFingerprint();

            // Si no hay aleatorización activa, no hacer nada (no es un error)
            if ( ! randomizationId || ! userFingerprint ) {
                if ( this.config?.settings?.debug && window.console?.debug ) {
                    window.console.debug(
                        '[EIPSI Save & Continue] No hay sesión de aleatorización activa'
                    );
                }
                return true;
            }

            // Si no hay ajaxUrl, no se puede hacer la petición
            if ( ! this.config.ajaxUrl ) {
                if ( this.config?.settings?.debug && window.console?.warn ) {
                    window.console.warn(
                        '[EIPSI Save & Continue] No hay ajaxUrl para cerrar sesión de aleatorización'
                    );
                }
                return false;
            }

            try {
                const formData = new URLSearchParams();
                formData.append(
                    'action',
                    'eipsi_close_randomization_session'
                );
                formData.append( 'randomization_id', randomizationId );
                formData.append( 'user_fingerprint', userFingerprint );

                const response = await fetch( this.config.ajaxUrl, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    credentials: 'same-origin',
                } );

                if ( ! response.ok ) {
                    if (
                        this.config?.settings?.debug &&
                        window.console?.warn
                    ) {
                        window.console.warn(
                            '[EIPSI Save & Continue] Error al cerrar sesión de aleatorización:',
                            response.status
                        );
                    }
                    return false;
                }

                const data = await response.json();

                if ( this.config?.settings?.debug && window.console?.debug ) {
                    window.console.debug(
                        '[EIPSI Save & Continue] Sesión de aleatorización cerrada:',
                        data
                    );
                }

                return data.success || false;
            } catch ( error ) {
                if ( this.config?.settings?.debug && window.console?.error ) {
                    window.console.error(
                        '[EIPSI Save & Continue] Error al cerrar sesión de aleatorización:',
                        error
                    );
                }
                // No lanzar error (permitir que el flujo continúe)
                return false;
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

            // Limpiar almacenamiento local/sesión
            try {
                const sessionKey = `eipsi_session_${
                    this.formId || 'default'
                }`;
                window.sessionStorage.removeItem( sessionKey );

                const storageKey = `eipsi_form_responses_${
                    this.formId || 'default'
                }`;
                window.localStorage.removeItem( storageKey );
            } catch ( error ) {
                // Ignore
            }

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
            this.discardFromServer();
            this.removeBeforeUnload();

            // Limpiar almacenamiento local/sesión
            try {
                const sessionKey = `eipsi_session_${
                    this.formId || 'default'
                }`;
                window.sessionStorage.removeItem( sessionKey );

                // Limpiar cualquier respaldo en localStorage si existiera (por compatibilidad)
                const storageKey = `eipsi_form_responses_${
                    this.formId || 'default'
                }`;
                window.localStorage.removeItem( storageKey );
            } catch ( error ) {
                // Ignore storage errors
            }

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

    /**
     * Handler global para botones "Comenzar de nuevo" en thank-you page
     *
     * Detecta si el formulario está dentro de un contenedor de aleatorización
     * y cierra la sesión antes de recargar la página.
     */
    document.addEventListener( 'DOMContentLoaded', () => {
        const restartButtons = document.querySelectorAll(
            '.eipsi-randomization-container [data-action="restart"]'
        );

        restartButtons.forEach( ( button ) => {
            button.addEventListener( 'click', async ( event ) => {
                event.preventDefault();
                button.disabled = true;
                button.textContent = 'Reiniciando...';

                try {
                    // Obtener randomization_id desde el contenedor
                    const container = button.closest(
                        '.eipsi-randomization-container'
                    );
                    const randomizationId = container
                        ? container.getAttribute( 'data-randomization-id' )
                        : null;

                    // Obtener fingerprint del usuario
                    let userFingerprint = null;

                    // OPCIÓN 1: Desde localStorage (usado por fingerprinting)
                    try {
                        const fpData = window.localStorage.getItem(
                            'eipsi_user_fingerprint'
                        );
                        if ( fpData ) {
                            const parsed = JSON.parse( fpData );
                            if ( parsed && parsed.fingerprint ) {
                                userFingerprint = parsed.fingerprint;
                            }
                        }
                    } catch ( error ) {
                        // Ignore
                    }

                    // OPCIÓN 2: Desde cookie
                    if ( ! userFingerprint && document.cookie ) {
                        const cookies = document.cookie.split( ';' );
                        for ( let i = 0; i < cookies.length; i++ ) {
                            const cookie = cookies[ i ].trim();
                            if ( cookie.startsWith( 'eipsi_fingerprint=' ) ) {
                                userFingerprint = cookie.substring(
                                    'eipsi_fingerprint='.length
                                );
                                break;
                            }
                        }
                    }

                    // OPCIÓN 3: Desde sessionStorage
                    if ( ! userFingerprint ) {
                        try {
                            userFingerprint =
                                window.sessionStorage.getItem(
                                    'eipsi_fingerprint'
                                );
                        } catch ( error ) {
                            // Ignore
                        }
                    }

                    // Si hay randomization_id y fingerprint, cerrar sesión
                    if (
                        randomizationId &&
                        userFingerprint &&
                        window.eipsiFormsConfig &&
                        window.eipsiFormsConfig.ajaxUrl
                    ) {
                        const formData = new URLSearchParams();
                        formData.append(
                            'action',
                            'eipsi_close_randomization_session'
                        );
                        formData.append( 'randomization_id', randomizationId );
                        formData.append( 'user_fingerprint', userFingerprint );

                        await fetch( window.eipsiFormsConfig.ajaxUrl, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'Content-Type':
                                    'application/x-www-form-urlencoded',
                            },
                            credentials: 'same-origin',
                        } );
                    }
                } catch ( error ) {
                    // Ignorar errores (continuar de todas formas)
                    if ( window.console && window.console.warn ) {
                        window.console.warn(
                            '[EIPSI Save & Continue] Error al cerrar sesión de aleatorización:',
                            error
                        );
                    }
                }

                // Recargar la página (esto asignará un nuevo formulario en rotación)
                window.location.reload();
            } );
        } );
    } );
} )();
