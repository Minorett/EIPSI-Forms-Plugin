( function () {
	'use strict';

	const config = window.eipsiTrackingConfig || {};
	const STORAGE_KEY = 'eipsiAnalyticsSessions';
	const ALLOWED_EVENTS = new Set( [
		'view',
		'start',
		'page_change',
		'submit',
		'abandon',
	] );

	const Tracking = {
		config,
		sessions: new Map(),
		supportsSessionStorage: null,

		init() {
			this.restoreSessions();

			document.addEventListener( 'visibilitychange', () => {
				if ( document.visibilityState === 'hidden' ) {
					this.flushAbandonEvents( true );
				}
			} );

			window.addEventListener( 'beforeunload', () => {
				this.flushAbandonEvents( true );
			} );
		},

		supportsStorage() {
			if ( this.supportsSessionStorage !== null ) {
				return this.supportsSessionStorage;
			}

			try {
				const testKey = '__eipsi_tracking__';
				sessionStorage.setItem( testKey, '1' );
				sessionStorage.removeItem( testKey );
				this.supportsSessionStorage = true;
			} catch ( error ) {
				this.supportsSessionStorage = false;
			}

			return this.supportsSessionStorage;
		},

		restoreSessions() {
			if ( ! this.supportsStorage() ) {
				return;
			}

			try {
				const raw = sessionStorage.getItem( STORAGE_KEY );
				if ( ! raw ) {
					return;
				}

				const parsed = JSON.parse( raw );

				if ( parsed && typeof parsed === 'object' ) {
					Object.keys( parsed ).forEach( ( key ) => {
						if ( parsed[ key ] && parsed[ key ].sessionId ) {
							this.sessions.set( key, parsed[ key ] );
						}
					} );
				}
			} catch ( error ) {
				// Ignore storage errors
			}
		},

		persistSessions() {
			if ( ! this.supportsStorage() ) {
				return;
			}

			const payload = {};
			this.sessions.forEach( ( session, key ) => {
				payload[ key ] = session;
			} );

			try {
				sessionStorage.setItem(
					STORAGE_KEY,
					JSON.stringify( payload )
				);
			} catch ( error ) {
				// Ignore quota errors
			}
		},

		persistSession() {
			this.persistSessions();
		},

		generateSessionId() {
			if ( window.crypto && window.crypto.getRandomValues ) {
				const buffer = new Uint32Array( 4 );
				window.crypto.getRandomValues( buffer );
				return Array.from( buffer )
					.map( ( value ) => value.toString( 16 ).padStart( 8, '0' ) )
					.join( '' );
			}

			return (
				Math.random().toString( 36 ).slice( 2 ) +
				Date.now().toString( 36 )
			);
		},

		createSessionPayload() {
			return {
				sessionId: this.generateSessionId(),
				viewTracked: false,
				startTracked: false,
				submitTracked: false,
				abandonTracked: false,
				currentPage: 1,
				totalPages: 1,
			};
		},

		getOrCreateSession( formId ) {
			const key = formId || 'default';

			if ( this.sessions.has( key ) ) {
				return this.sessions.get( key );
			}

			const session = this.createSessionPayload();
			this.sessions.set( key, session );
			this.persistSession();

			return session;
		},

		registerForm( form, formId ) {
			if ( ! form ) {
				return null;
			}

			const key = formId || 'default';
			const session = this.getOrCreateSession( key );

			if ( ! form.dataset.sessionId ) {
				form.dataset.sessionId = session.sessionId;
			}

			if ( ! session.viewTracked ) {
				this.trackEvent( 'view', key );
				session.viewTracked = true;
				this.persistSession();
			}

			const startHandler = ( event ) => {
				if ( session.startTracked ) {
					return;
				}

				if ( this.isInteractiveField( event.target ) ) {
					this.trackEvent( 'start', key );
					session.startTracked = true;
					this.persistSession();
					form.removeEventListener( 'focusin', startHandler );
					form.removeEventListener( 'input', startHandler );
				}
			};

			form.addEventListener( 'focusin', startHandler, { once: false } );
			form.addEventListener( 'input', startHandler, { once: false } );

			return session;
		},

		setTotalPages( formId, totalPages ) {
			const session = this.getOrCreateSession( formId );
			session.totalPages = totalPages || 1;
			this.persistSession();
		},

		setCurrentPage( formId, pageNumber, options = {} ) {
			const session = this.getOrCreateSession( formId );
			session.currentPage = pageNumber || 1;

			if ( options.trackChange ) {
				this.trackEvent( 'page_change', formId, {
					page_number: session.currentPage,
				} );
			}

			this.persistSession();
		},

		recordPageChange( formId, pageNumber ) {
			const session = this.getOrCreateSession( formId );

			if ( typeof pageNumber === 'number' ) {
				session.currentPage = pageNumber;
			}

			this.trackEvent( 'page_change', formId, {
				page_number: session.currentPage,
			} );
			this.persistSession();
		},

		recordSubmit( formId ) {
			const session = this.getOrCreateSession( formId );

			if ( ! session.submitTracked ) {
				this.trackEvent( 'submit', formId );
				session.submitTracked = true;
				session.abandonTracked = true;
				this.persistSession();
			}
		},

		flushAbandonEvents( force ) {
			let updated = false;

			this.sessions.forEach( ( session, key ) => {
				if (
					session.startTracked &&
					! session.submitTracked &&
					! session.abandonTracked
				) {
					this.trackEvent(
						'abandon',
						key,
						{ page_number: session.currentPage || null },
						{ useBeacon: true, keepalive: true }
					);
					session.abandonTracked = true;
					updated = true;
				}
			} );

			if ( updated || force ) {
				this.persistSession();
			}
		},

		trackEvent( eventType, formId, payload = {}, options = {} ) {
			if ( ! ALLOWED_EVENTS.has( eventType ) ) {
				return;
			}

			if ( ! this.config.ajaxUrl || ! this.config.nonce ) {
				return;
			}

			const session = this.getOrCreateSession( formId || 'default' );
			const params = new URLSearchParams();
			params.append( 'action', 'eipsi_track_event' );
			params.append( 'nonce', this.config.nonce );
			params.append( 'form_id', formId || '' );
			params.append( 'session_id', session.sessionId );
			params.append( 'event_type', eventType );

			if (
				payload &&
				Object.prototype.hasOwnProperty.call(
					payload,
					'page_number'
				) &&
				payload.page_number !== null &&
				payload.page_number !== undefined
			) {
				params.append( 'page_number', String( payload.page_number ) );
			}

			if ( navigator.userAgent ) {
				params.append( 'user_agent', navigator.userAgent );
			}

			if ( options.useBeacon && navigator.sendBeacon ) {
				navigator.sendBeacon( this.config.ajaxUrl, params );
				return;
			}

			const requestOptions = {
				method: 'POST',
				headers: {
					'Content-Type':
						'application/x-www-form-urlencoded; charset=UTF-8',
				},
				body: params.toString(),
			};

			if ( options.keepalive ) {
				requestOptions.keepalive = true;
			}

			fetch( this.config.ajaxUrl, requestOptions ).catch( () => {
				// Silently ignore network errors
			} );
		},

		isInteractiveField( element ) {
			if ( ! element || ! element.tagName ) {
				return false;
			}

			const tag = element.tagName.toLowerCase();
			return tag === 'input' || tag === 'select' || tag === 'textarea';
		},
	};

	Tracking.init();

	window.EIPSITracking = {
		registerForm( form, formId ) {
			return Tracking.registerForm( form, formId );
		},
		setTotalPages( formId, totalPages ) {
			Tracking.setTotalPages( formId, totalPages );
		},
		setCurrentPage( formId, pageNumber, options = {} ) {
			Tracking.setCurrentPage( formId, pageNumber, options );
		},
		recordPageChange( formId, pageNumber ) {
			Tracking.recordPageChange( formId, pageNumber );
		},
		recordSubmit( formId ) {
			Tracking.recordSubmit( formId );
		},
		flushAbandon() {
			Tracking.flushAbandonEvents( true );
		},
	};
} )();
