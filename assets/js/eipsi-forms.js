/**
 * EIPSI Forms Frontend JavaScript
 * Handles form validation, submission, and user interactions
 * Works with both shortcode and block-rendered forms
 */

( function () {
	'use strict';

	/* global navigator, localStorage */

	/**
	 * Obtiene o genera un Participant ID único y persistente
	 * - Mismo ID para todos los formularios en la sesión
	 * - Persiste en localStorage (sobrevive recargas)
	 * - Completamente anónimo (UUID v4)
	 *
	 * @return {string} "p-a1b2c3d4e5f6" (p- + 12 caracteres)
	 */
	function getUniversalParticipantId() {
		const STORAGE_KEY = 'eipsi_participant_id';

		let pid = localStorage.getItem( STORAGE_KEY );
		if ( ! pid ) {
			// Generar UUID v4 truncado a 12 caracteres
			pid =
				'p-' +
				crypto.randomUUID().replace( /-/g, '' ).substring( 0, 12 );
			localStorage.setItem( STORAGE_KEY, pid );
		}

		return pid;
	}

	/**
	 * Genera Session ID único para cada sesión/envío
	 * @return {string} "sess-[timestamp]-[random]"
	 */
	function getSessionId() {
		const timestamp = Date.now();
		const random = Math.random().toString( 36 ).substring( 2, 8 );
		return 'sess-' + timestamp + '-' + random;
	}

	class ConditionalNavigator {
		constructor( form ) {
			this.form = form;
			this.fieldCache = new Map();
			this.history = [];
			this.visitedPages = new Set();
			this.skippedPages = new Set();
		}

		parseConditionalLogic( jsonString ) {
			if ( ! jsonString || jsonString === 'true' ) {
				return null;
			}

			try {
				return JSON.parse( jsonString );
			} catch ( error ) {
				if ( window.console && window.console.warn ) {
					window.console.warn(
						'[EIPSI Forms] Invalid conditional logic JSON:',
						jsonString,
						error
					);
				}
				return null;
			}
		}

		normalizeConditionalLogic( logic ) {
			if ( ! logic ) {
				return null;
			}

			if ( Array.isArray( logic ) ) {
				return {
					enabled: logic.length > 0,
					rules: logic.map( ( rule ) => ( {
						id: rule.id || `rule-${ Date.now() }`,
						matchValue: rule.value || rule.matchValue || '',
						action: rule.action || 'nextPage',
						targetPage: rule.targetPage || null,
					} ) ),
					defaultAction: 'nextPage',
					defaultTargetPage: null,
				};
			}

			if ( typeof logic === 'object' && logic.enabled !== undefined ) {
				return logic;
			}

			return null;
		}

		getFieldValue( field ) {
			const fieldType = field.dataset.fieldType;

			switch ( fieldType ) {
				case 'select':
					const select = field.querySelector( 'select' );
					return select ? select.value : '';

				case 'radio':
					const checkedRadio = field.querySelector(
						'input[type="radio"]:checked'
					);
					return checkedRadio ? checkedRadio.value : '';

				case 'checkbox':
					const checkedBoxes = field.querySelectorAll(
						'input[type="checkbox"]:checked'
					);
					return Array.from( checkedBoxes ).map( ( cb ) => cb.value );

				case 'vas-slider':
					const slider = field.querySelector( 'input[type="range"]' );
					if ( slider ) {
						const value = parseFloat( slider.value );
						return ! Number.isNaN( value ) ? value : null;
					}
					return null;

				default:
					return '';
			}
		}

		findMatchingRule( rules, fieldValue ) {
			if ( ! Array.isArray( rules ) ) {
				return null;
			}

			for ( const rule of rules ) {
				if ( rule.operator && rule.threshold !== undefined ) {
					if ( typeof fieldValue === 'number' ) {
						const threshold = parseFloat( rule.threshold );

						if ( Number.isNaN( threshold ) ) {
							continue;
						}

						let matches = false;
						switch ( rule.operator ) {
							case '>=':
								matches = fieldValue >= threshold;
								break;
							case '<=':
								matches = fieldValue <= threshold;
								break;
							case '>':
								matches = fieldValue > threshold;
								break;
							case '<':
								matches = fieldValue < threshold;
								break;
							case '==':
								matches = fieldValue === threshold;
								break;
						}

						if ( matches ) {
							return rule;
						}
					}
				} else if (
					rule.matchValue !== undefined ||
					rule.value !== undefined
				) {
					if ( Array.isArray( fieldValue ) ) {
						for ( const value of fieldValue ) {
							if (
								rule.matchValue === value ||
								rule.value === value
							) {
								return rule;
							}
						}
					} else if (
						rule.matchValue === fieldValue ||
						rule.value === fieldValue
					) {
						return rule;
					}
				}
			}

			return null;
		}

		getNextPage( currentPage ) {
			const currentPageElement = EIPSIForms.getPageElement(
				this.form,
				currentPage
			);

			if ( ! currentPageElement ) {
				return { action: 'nextPage', targetPage: currentPage + 1 };
			}

			const conditionalFields = currentPageElement.querySelectorAll(
				'[data-conditional-logic]'
			);

			for ( const field of conditionalFields ) {
				const jsonString = field.dataset.conditionalLogic;
				const parsedLogic = this.parseConditionalLogic( jsonString );
				const conditionalLogic =
					this.normalizeConditionalLogic( parsedLogic );

				if ( ! conditionalLogic || ! conditionalLogic.enabled ) {
					continue;
				}

				const fieldValue = this.getFieldValue( field );

				if (
					! fieldValue ||
					( Array.isArray( fieldValue ) && fieldValue.length === 0 )
				) {
					continue;
				}

				const matchingRule = this.findMatchingRule(
					conditionalLogic.rules,
					fieldValue
				);

				if ( matchingRule ) {
					if ( matchingRule.action === 'submit' ) {
						return { action: 'submit' };
					}

					if (
						matchingRule.action === 'goToPage' &&
						matchingRule.targetPage
					) {
						const targetPage = parseInt(
							matchingRule.targetPage,
							10
						);

						if ( ! Number.isNaN( targetPage ) ) {
							const totalPages = EIPSIForms.getTotalPages(
								this.form
							);
							const boundedTarget = Math.min(
								Math.max( targetPage, 1 ),
								totalPages
							);
							return {
								action: 'goToPage',
								targetPage: boundedTarget,
								fieldId: field.id || field.dataset.fieldName,
								matchedValue: Array.isArray( fieldValue )
									? fieldValue[ 0 ]
									: fieldValue,
							};
						}
					}

					if ( matchingRule.action === 'nextPage' ) {
						return {
							action: 'nextPage',
							targetPage: currentPage + 1,
						};
					}
				}

				if ( conditionalLogic.defaultAction ) {
					if ( conditionalLogic.defaultAction === 'submit' ) {
						return { action: 'submit' };
					}

					if (
						conditionalLogic.defaultAction === 'goToPage' &&
						conditionalLogic.defaultTargetPage
					) {
						const targetPage = parseInt(
							conditionalLogic.defaultTargetPage,
							10
						);

						if ( ! Number.isNaN( targetPage ) ) {
							const totalPages = EIPSIForms.getTotalPages(
								this.form
							);
							const boundedTarget = Math.min(
								Math.max( targetPage, 1 ),
								totalPages
							);
							return {
								action: 'goToPage',
								targetPage: boundedTarget,
								isDefault: true,
							};
						}
					}
				}
			}

			return { action: 'nextPage', targetPage: currentPage + 1 };
		}

		shouldSubmit( currentPage ) {
			const result = this.getNextPage( currentPage );
			return result.action === 'submit';
		}

		pushHistory( pageNumber ) {
			if (
				this.history.length === 0 ||
				this.history[ this.history.length - 1 ] !== pageNumber
			) {
				this.history.push( pageNumber );
				this.visitedPages.add( pageNumber );
			}
		}

		popHistory() {
			if ( this.history.length > 1 ) {
				this.history.pop();
				return this.history[ this.history.length - 1 ];
			}
			return null;
		}

		markSkippedPages( fromPage, toPage ) {
			if ( fromPage === toPage || Math.abs( toPage - fromPage ) === 1 ) {
				return;
			}

			const start = Math.min( fromPage, toPage );
			const end = Math.max( fromPage, toPage );

			for ( let i = start + 1; i < end; i++ ) {
				if ( ! this.visitedPages.has( i ) ) {
					this.skippedPages.add( i );
				}
			}
		}

		getActivePath() {
			return Array.from( this.visitedPages ).sort( ( a, b ) => a - b );
		}

		isPageSkipped( pageNumber ) {
			return this.skippedPages.has( pageNumber );
		}

		reset() {
			this.history = [];
			this.visitedPages.clear();
			this.skippedPages.clear();
		}
	}

	const EIPSIForms = {
		forms: [],
		navigators: new Map(),
		config: window.eipsiFormsConfig || {},

		init() {
			document.addEventListener( 'DOMContentLoaded', () => {
				this.initForms();
			} );
		},

		initForms() {
			const forms = document.querySelectorAll(
				'.vas-dinamico-form form, .eipsi-form form'
			);

			forms.forEach( ( form ) => {
				this.initForm( form );
			} );
		},

		initForm( form ) {
			if ( form.dataset.initialized ) {
				return;
			}

			form.dataset.initialized = 'true';
			this.forms.push( form );

			const formId = this.getFormId( form );
			const navigator = new ConditionalNavigator( form );
			this.navigators.set( formId || form, navigator );

			const initialPage = this.getCurrentPage( form );
			navigator.pushHistory( initialPage );

			this.applyTestSelectors( form );

			this.populateDeviceInfo( form );
			this.initPagination( form );
			this.initVasSliders( form );
			this.initLikertFields( form );
			this.initRadioFields( form );
			this.initConditionalFieldListeners( form );
			this.attachTracking( form );

			form.addEventListener( 'submit', ( e ) =>
				this.handleSubmit( e, form )
			);

			if ( this.config.settings?.validateOnBlur ) {
				this.setupFieldValidation( form );
			}
		},

		getFormId( form ) {
			if ( ! form ) {
				return '';
			}

			if ( form.dataset.formId ) {
				return form.dataset.formId;
			}

			const hiddenField = form.querySelector( 'input[name="form_id"]' );
			return hiddenField ? hiddenField.value : '';
		},

		getTrackingFormId( form ) {
			if ( ! form ) {
				return '';
			}

			return form.dataset.trackingFormId || this.getFormId( form );
		},

		getNavigator( form ) {
			if ( ! form ) {
				return null;
			}

			const formId = this.getFormId( form );
			return this.navigators.get( formId || form );
		},

		initConditionalFieldListeners( form ) {
			const conditionalFields = form.querySelectorAll(
				'[data-conditional-logic]'
			);

			conditionalFields.forEach( ( field ) => {
				const inputs = field.querySelectorAll(
					'input[type="radio"], input[type="checkbox"], input[type="range"], select'
				);

				inputs.forEach( ( input ) => {
					input.addEventListener( 'change', () => {
						const navigator = this.getNavigator( form );
						if ( navigator ) {
							const currentPage = this.getCurrentPage( form );
							const nextPageResult =
								navigator.getNextPage( currentPage );

							const totalPages = this.getTotalPages( form );
							this.updatePaginationDisplay(
								form,
								currentPage,
								totalPages
							);

							if ( window.EIPSITracking ) {
								const trackingFormId =
									this.getTrackingFormId( form );
								if (
									trackingFormId &&
									nextPageResult.action === 'goToPage' &&
									nextPageResult.targetPage !==
										currentPage + 1
								) {
									this.recordBranchingPreview(
										trackingFormId,
										currentPage,
										nextPageResult
									);
								}
							}
						}
					} );
				} );
			} );
		},

		recordBranchingPreview( formId, currentPage, nextPageResult ) {
			if ( ! window.EIPSITracking || ! window.EIPSITracking.trackEvent ) {
				return;
			}

			if (
				this.config.settings?.debug &&
				window.console &&
				window.console.log
			) {
				window.console.log(
					'[EIPSI Forms] Branching route updated:',
					`Page ${ currentPage } → Page ${ nextPageResult.targetPage }`,
					nextPageResult
				);
			}
		},

		applyTestSelectors( form ) {
			if ( ! form ) {
				return;
			}

			const formId = this.getFormId( form ) || 'default';

			if ( ! form.dataset.testid ) {
				form.setAttribute( 'data-testid', `eipsi-form-${ formId }` );
			}

			const navigation = form.querySelector( '.form-navigation' );
			if ( navigation && ! navigation.dataset.testid ) {
				navigation.setAttribute(
					'data-testid',
					`form-navigation-${ formId }`
				);
			}

			const progress = form.querySelector( '.form-progress' );
			if ( progress && ! progress.dataset.testid ) {
				progress.setAttribute(
					'data-testid',
					`form-progress-${ formId }`
				);
			}

			const prevButton = form.querySelector( '.eipsi-prev-button' );
			if ( prevButton && ! prevButton.dataset.testid ) {
				prevButton.setAttribute( 'data-testid', 'prev-button' );
			}

			const nextButton = form.querySelector( '.eipsi-next-button' );
			if ( nextButton && ! nextButton.dataset.testid ) {
				nextButton.setAttribute( 'data-testid', 'next-button' );
			}

			const submitButton = form.querySelector( '.eipsi-submit-button' );
			if ( submitButton && ! submitButton.dataset.testid ) {
				submitButton.setAttribute( 'data-testid', 'submit-button' );
			}

			const pages = form.querySelectorAll( '.eipsi-page' );
			pages.forEach( ( page, index ) => {
				if ( ! page.dataset.testid ) {
					page.setAttribute(
						'data-testid',
						`form-page-${ index + 1 }`
					);
				}
			} );

			const groups = form.querySelectorAll( '.form-group' );
			groups.forEach( ( group ) => {
				const fieldName = group.dataset.fieldName || group.id || '';
				if ( fieldName && ! group.dataset.testid ) {
					const sanitized = fieldName.replace(
						/[^a-zA-Z0-9_-]/g,
						'-'
					);
					group.setAttribute( 'data-testid', `field-${ sanitized }` );
				}

				const inputs = group.querySelectorAll(
					'input, textarea, select'
				);
				inputs.forEach( ( input ) => {
					if ( ! input.dataset.testid ) {
						let key =
							input.name || input.id || fieldName || 'input';
						key = key.replace( /[^a-zA-Z0-9_-]/g, '-' );
						input.setAttribute( 'data-testid', `input-${ key }` );
					}
				} );
			} );
		},

		attachTracking( form ) {
			if ( ! window.EIPSITracking || ! form ) {
				return;
			}

			const formId = this.getFormId( form ) || 'default';

			window.EIPSITracking.registerForm( form, formId );
			form.dataset.trackingFormId = formId;

			const totalPages =
				parseInt( form.dataset.totalPages || '1', 10 ) || 1;
			const currentPageField = form.querySelector(
				'.eipsi-current-page'
			);
			const currentPage =
				parseInt(
					currentPageField?.value || form.dataset.currentPage || '1',
					10
				) || 1;

			window.EIPSITracking.setTotalPages( formId, totalPages );
			window.EIPSITracking.setCurrentPage( formId, currentPage, {
				trackChange: false,
			} );
		},

		populateDeviceInfo( form ) {
			const deviceField = form.querySelector(
				'.eipsi-device-placeholder'
			);
			const startTimeField = form.querySelector( '.eipsi-start-time' );

			if ( deviceField ) {
				deviceField.value = this.getDeviceType();
			}

			if ( startTimeField ) {
				startTimeField.value = Date.now();
			}
		},

		getDeviceType() {
			const ua =
				typeof navigator !== 'undefined' ? navigator.userAgent : '';
			if (
				/(tablet|ipad|playbook|silk)|(android(?!.*mobi))/i.test( ua )
			) {
				return 'tablet';
			}
			if (
				/Mobile|Android|iP(hone|od)|IEMobile|BlackBerry|Kindle|Silk-Accelerated|(hpw|web)OS|Opera M(obi|ini)/.test(
					ua
				)
			) {
				return 'mobile';
			}
			return 'desktop';
		},

		initPagination( form ) {
			const pages = form.querySelectorAll( '.eipsi-page' );

			if ( pages.length > 0 ) {
				pages.forEach( ( page, index ) => {
					if ( ! page.dataset.page ) {
						page.dataset.page = String( index + 1 );
					}
				} );

				const totalPages = pages.length;
				form.dataset.totalPages = totalPages;

				const totalPagesField =
					form.querySelector( '.form-progress .total-pages' ) ||
					form.querySelector( '.total-pages' );
				if ( totalPagesField ) {
					totalPagesField.textContent = totalPages;
				}

				const progressContainer =
					form.querySelector( '.form-progress' );
				if ( progressContainer ) {
					progressContainer.style.display =
						totalPages > 1 ? '' : 'none';
				}

				const normalizedPage = this.getCurrentPage( form );
				this.setCurrentPage( form, normalizedPage, {
					trackChange: false,
				} );
			}

			const prevButton = form.querySelector( '.eipsi-prev-button' );
			const nextButton = form.querySelector( '.eipsi-next-button' );
			const submitButton = form.querySelector( '.eipsi-submit-button' );

			if ( prevButton ) {
				prevButton.removeAttribute( 'disabled' );
				prevButton.addEventListener( 'click', ( e ) => {
					e.preventDefault();
					e.stopPropagation();
					if (
						form.dataset.submitting === 'true' ||
						prevButton.disabled
					) {
						return;
					}
					this.handlePagination( form, 'prev' );
				} );
			}

			if ( nextButton ) {
				nextButton.removeAttribute( 'disabled' );
				nextButton.addEventListener( 'click', ( e ) => {
					e.preventDefault();
					e.stopPropagation();
					if (
						form.dataset.submitting === 'true' ||
						nextButton.disabled
					) {
						return;
					}
					this.handlePagination( form, 'next' );
				} );
			}

			if ( submitButton ) {
				submitButton.removeAttribute( 'disabled' );
			}
		},

		initVasSliders( form ) {
			const sliders = form.querySelectorAll( '.vas-slider' );

			sliders.forEach( ( slider ) => {
				if ( ! slider.hasAttribute( 'data-touched' ) ) {
					slider.setAttribute( 'data-touched', 'false' );
				}

				const showValue = slider.dataset.showValue === 'true';
				let updateTimer = null;
				let rafId = null;

				const markAsTouched = () => {
					if ( slider.dataset.touched === 'false' ) {
						slider.dataset.touched = 'true';
						this.validateField( slider );
					}
				};

				const throttledUpdate = ( value ) => {
					if ( rafId ) {
						return;
					}

					rafId = window.requestAnimationFrame( () => {
						const valueDisplay = document.getElementById(
							slider.getAttribute( 'aria-labelledby' )
						);

						if ( valueDisplay ) {
							valueDisplay.textContent = value;
						}

						slider.setAttribute( 'aria-valuenow', value );
						rafId = null;
					} );
				};

				slider.addEventListener( 'pointerdown', markAsTouched, {
					once: true,
				} );

				slider.addEventListener( 'keydown', ( e ) => {
					if (
						e.key === 'ArrowLeft' ||
						e.key === 'ArrowRight' ||
						e.key === 'ArrowUp' ||
						e.key === 'ArrowDown' ||
						e.key === 'Home' ||
						e.key === 'End'
					) {
						markAsTouched();
					}
				} );

				if ( showValue ) {
					slider.addEventListener( 'input', ( e ) => {
						const value = e.target.value;

						if ( updateTimer ) {
							clearTimeout( updateTimer );
						}

						updateTimer = setTimeout( () => {
							throttledUpdate( value );
						}, 80 );
					} );
				} else {
					slider.addEventListener( 'input', ( e ) => {
						if ( updateTimer ) {
							clearTimeout( updateTimer );
						}

						updateTimer = setTimeout( () => {
							slider.setAttribute(
								'aria-valuenow',
								e.target.value
							);
						}, 80 );
					} );
				}
			} );
		},

		initLikertFields( form ) {
			const likertFields = form.querySelectorAll( '.eipsi-likert-field' );

			likertFields.forEach( ( field ) => {
				const radioInputs = field.querySelectorAll(
					'input[type="radio"]'
				);

				radioInputs.forEach( ( radio ) => {
					// Validate when radio selection changes
					radio.addEventListener( 'change', () => {
						this.validateField( radio );
					} );
				} );
			} );
		},

		initRadioFields( form ) {
			const radioFields = form.querySelectorAll( '.eipsi-radio-field' );

			radioFields.forEach( ( field ) => {
				const radioInputs = field.querySelectorAll(
					'input[type="radio"]'
				);

				let lastSelected = null;

				radioInputs.forEach( ( radio ) => {
					radio.addEventListener( 'change', () => {
						this.validateField( radio );
						lastSelected = radio.value;
					} );

					radio.addEventListener( 'click', () => {
						if ( lastSelected === radio.value && radio.checked ) {
							radio.checked = false;
							lastSelected = null;
							this.validateField( radio );
							radio.dispatchEvent(
								new Event( 'change', { bubbles: true } )
							);
						}
					} );
				} );
			} );
		},

		getTotalPages( form ) {
			if ( ! form ) {
				return 1;
			}

			const datasetValue = parseInt( form.dataset.totalPages || '', 10 );

			if ( ! Number.isNaN( datasetValue ) && datasetValue > 0 ) {
				return datasetValue;
			}

			const pages = form.querySelectorAll( '.eipsi-page' );
			const totalPages = pages.length || 1;
			form.dataset.totalPages = totalPages;

			return totalPages;
		},

		getCurrentPage( form ) {
			if ( ! form ) {
				return 1;
			}

			const currentPageField = form.querySelector(
				'.eipsi-current-page'
			);
			const fieldValue = currentPageField
				? parseInt( currentPageField.value || '', 10 )
				: NaN;
			const datasetValue = parseInt( form.dataset.currentPage || '', 10 );
			const totalPages = this.getTotalPages( form );

			let currentPage = ! Number.isNaN( fieldValue )
				? fieldValue
				: datasetValue;

			if ( Number.isNaN( currentPage ) ) {
				currentPage = 1;
			}

			if ( currentPage < 1 ) {
				currentPage = 1;
			} else if ( currentPage > totalPages ) {
				currentPage = totalPages;
			}

			if (
				currentPageField &&
				currentPageField.value !== `${ currentPage }`
			) {
				currentPageField.value = currentPage;
			}

			form.dataset.currentPage = currentPage;

			return currentPage;
		},

		setCurrentPage( form, pageNumber, options = {} ) {
			if ( ! form ) {
				return;
			}

			const { trackChange = true } = options;
			const totalPages = this.getTotalPages( form );
			const previousPage = this.getCurrentPage( form );
			let sanitizedPage = parseInt( pageNumber, 10 );

			if ( Number.isNaN( sanitizedPage ) ) {
				sanitizedPage = 1;
			}

			if ( sanitizedPage < 1 ) {
				sanitizedPage = 1;
			} else if ( sanitizedPage > totalPages ) {
				sanitizedPage = totalPages;
			}

			const currentPageField = form.querySelector(
				'.eipsi-current-page'
			);

			if ( currentPageField ) {
				currentPageField.value = sanitizedPage;
			}

			form.dataset.currentPage = sanitizedPage;

			this.updatePaginationDisplay( form, sanitizedPage, totalPages );

			if (
				trackChange &&
				sanitizedPage !== previousPage &&
				window.EIPSITracking
			) {
				const trackingFormId = this.getTrackingFormId( form );
				if ( trackingFormId ) {
					window.EIPSITracking.recordPageChange(
						trackingFormId,
						sanitizedPage
					);
				}
			}
		},

		handlePagination( form, direction ) {
			if ( ! form ) {
				return;
			}

			const currentPage = this.getCurrentPage( form );
			let targetPage = currentPage;
			let isBranchJump = false;
			let branchDetails = null;

			if ( direction === 'next' ) {
				if ( ! this.validateCurrentPage( form ) ) {
					return;
				}

				const navigator = this.getNavigator( form );
				if ( navigator ) {
					const result = navigator.getNextPage( currentPage );

					if ( result.action === 'submit' ) {
						this.handleSubmit( { preventDefault: () => {} }, form );
						return;
					}

					if ( result.action === 'goToPage' && result.targetPage ) {
						targetPage = result.targetPage;
						isBranchJump = targetPage !== currentPage + 1;
						branchDetails = result;
					} else {
						const totalPages = this.getTotalPages( form );
						if ( currentPage < totalPages ) {
							targetPage = currentPage + 1;
						}
					}

					if ( isBranchJump ) {
						navigator.markSkippedPages( currentPage, targetPage );
					}

					navigator.pushHistory( targetPage );
				} else {
					const conditionalTarget = this.handleConditionalNavigation(
						form,
						currentPage
					);

					if ( conditionalTarget === 'submit' ) {
						this.handleSubmit( { preventDefault: () => {} }, form );
						return;
					}

					if (
						typeof conditionalTarget === 'number' &&
						! Number.isNaN( conditionalTarget )
					) {
						targetPage = conditionalTarget;
					} else {
						const totalPages = this.getTotalPages( form );
						if ( currentPage < totalPages ) {
							targetPage = currentPage + 1;
						}
					}
				}
			} else if ( direction === 'prev' ) {
				const navigator = this.getNavigator( form );
				if ( navigator ) {
					const previousPage = navigator.popHistory();
					if ( previousPage !== null ) {
						targetPage = previousPage;
					} else if ( currentPage > 1 ) {
						targetPage = currentPage - 1;
					}
				} else if ( currentPage > 1 ) {
					targetPage = currentPage - 1;
				}
			}

			if ( targetPage !== currentPage ) {
				this.setCurrentPage( form, targetPage );

				if ( isBranchJump && branchDetails && window.EIPSITracking ) {
					this.recordBranchJump(
						form,
						currentPage,
						targetPage,
						branchDetails
					);
				}
			}
		},

		recordBranchJump( form, fromPage, toPage, details ) {
			const trackingFormId = this.getTrackingFormId( form );
			if ( ! trackingFormId ) {
				return;
			}

			if (
				this.config.settings?.debug &&
				window.console &&
				window.console.log
			) {
				window.console.log(
					'[EIPSI Forms] Branch jump executed:',
					`Page ${ fromPage } → Page ${ toPage }`,
					{
						fieldId: details.fieldId,
						matchedValue: details.matchedValue,
						isDefault: details.isDefault,
					}
				);
			}

			if (
				window.EIPSITracking &&
				typeof window.EIPSITracking.trackEvent === 'function'
			) {
				window.EIPSITracking.trackEvent(
					'branch_jump',
					trackingFormId,
					{
						from_page: fromPage,
						to_page: toPage,
						field_id: details.fieldId,
						matched_value: details.matchedValue,
					}
				);
			}
		},

		updatePaginationDisplay( form, currentPage, totalPages ) {
			const prevButton = form.querySelector( '.eipsi-prev-button' );
			const nextButton = form.querySelector( '.eipsi-next-button' );
			const submitButton = form.querySelector( '.eipsi-submit-button' );
			const progressText = form.querySelector(
				'.form-progress .current-page'
			);
			const totalPagesText = form.querySelector(
				'.form-progress .total-pages'
			);
			const navigator = this.getNavigator( form );

			const rawAllowBackwards = form.dataset.allowBackwardsNav;
			const allowBackwardsNav =
				rawAllowBackwards !== 'false' &&
				rawAllowBackwards !== '0' &&
				rawAllowBackwards !== '';

			const firstVisitedPage =
				navigator && navigator.history.length > 0
					? navigator.history[ 0 ]
					: 1;
			const hasHistory = navigator && navigator.history.length > 1;

			if ( prevButton ) {
				const shouldShowPrev =
					allowBackwardsNav &&
					hasHistory &&
					currentPage > firstVisitedPage;
				if ( shouldShowPrev ) {
					prevButton.style.display = '';
					prevButton.removeAttribute( 'disabled' );
				} else {
					prevButton.style.display = 'none';
				}
			}

			const shouldShowNext = navigator
				? ! navigator.shouldSubmit( currentPage ) &&
				  currentPage < totalPages
				: currentPage < totalPages;

			if ( nextButton ) {
				if ( shouldShowNext ) {
					nextButton.style.display = '';
					nextButton.removeAttribute( 'disabled' );
				} else {
					nextButton.style.display = 'none';
				}
			}

			const shouldShowSubmit = navigator
				? navigator.shouldSubmit( currentPage ) ||
				  currentPage === totalPages
				: currentPage === totalPages;

			if ( submitButton ) {
				if ( shouldShowSubmit ) {
					submitButton.style.display = '';
					submitButton.removeAttribute( 'disabled' );
				} else {
					submitButton.style.display = 'none';
				}

				const strings = this.config.strings || {};
				if ( shouldShowSubmit && strings.submit ) {
					submitButton.textContent = strings.submit;
				}
			}

			if ( progressText ) {
				progressText.textContent = currentPage;
			}

			if (
				totalPagesText &&
				navigator &&
				navigator.visitedPages.size > 0
			) {
				const activePath = navigator.getActivePath();
				const currentIndex = activePath.indexOf( currentPage );

				if ( currentIndex !== -1 ) {
					const remainingPages =
						totalPages - activePath[ activePath.length - 1 ];
					const estimatedTotal =
						activePath.length + Math.max( 0, remainingPages );

					if ( estimatedTotal !== totalPages ) {
						totalPagesText.textContent = `${ estimatedTotal }*`;
						totalPagesText.title =
							'Estimado basado en tu ruta actual';
					} else {
						totalPagesText.textContent = totalPages;
						totalPagesText.title = '';
					}
				}
			}

			this.updatePageVisibility( form, currentPage );
			this.updatePageAriaAttributes( form, currentPage );

			if ( window.EIPSITracking ) {
				const trackingFormId = this.getTrackingFormId( form );
				if ( trackingFormId ) {
					window.EIPSITracking.setCurrentPage(
						trackingFormId,
						currentPage,
						{ trackChange: false }
					);
				}
			}
		},

		updatePageAriaAttributes( form, currentPage ) {
			const pages = form.querySelectorAll( '.eipsi-page' );

			pages.forEach( ( page, index ) => {
				const pageNumber = parseInt( page.dataset.page || index + 1 );

				if ( pageNumber === currentPage ) {
					page.setAttribute( 'aria-hidden', 'false' );
					page.removeAttribute( 'inert' );
				} else {
					page.setAttribute( 'aria-hidden', 'true' );
					if ( 'inert' in page ) {
						page.inert = true;
					}
				}
			} );
		},

		updatePageVisibility( form, currentPage ) {
			const pages = form.querySelectorAll( '.eipsi-page' );

			pages.forEach( ( page, index ) => {
				const pageNumber = parseInt( page.dataset.page || index + 1 );

				if ( pageNumber === currentPage ) {
					page.style.display = '';
				} else {
					page.style.display = 'none';
				}
			} );

			if ( this.config.settings?.enableAutoScroll ) {
				const formContainer = form.closest(
					'.vas-dinamico-form, .eipsi-form'
				);
				if ( formContainer ) {
					this.scrollToElement( formContainer );
				}
			}
		},

		setupFieldValidation( form ) {
			const fields = form.querySelectorAll( 'input, textarea, select' );

			fields.forEach( ( field ) => {
				field.addEventListener( 'blur', () => {
					this.validateField( field );
				} );

				field.addEventListener( 'input', () => {
					if ( field.classList.contains( 'error' ) ) {
						this.validateField( field );
					}
				} );
			} );
		},

		clearFieldError( formGroup, field, options = {} ) {
			if ( formGroup ) {
				formGroup.classList.remove( 'has-error' );
				const errorElement = formGroup.querySelector( '.form-error' );
				if ( errorElement ) {
					errorElement.style.display = 'none';
					errorElement.textContent = '';
				}
			}

			if ( field ) {
				field.classList.remove( 'error' );
				field.removeAttribute( 'aria-invalid' );
			}

			const { groupSelector } = options;

			if ( groupSelector && formGroup ) {
				const groupedInputs =
					formGroup.querySelectorAll( groupSelector );
				groupedInputs.forEach( ( input ) => {
					input.classList.remove( 'error' );
					input.removeAttribute( 'aria-invalid' );
				} );
			}
		},

		validateField( field ) {
			if ( ! field ) {
				return true;
			}

			const formGroup = field.closest( '.form-group' );
			if ( ! formGroup ) {
				return true;
			}

			if ( field.disabled ) {
				this.clearFieldError( formGroup, field );
				return true;
			}

			const isRadio = field.type === 'radio';
			const isCheckbox = field.type === 'checkbox';
			const isSelect = field.tagName === 'SELECT';
			const isRange = field.type === 'range';
			const groupSelector =
				isRadio || isCheckbox
					? `input[type="${ field.type }"][name="${ field.name }"]`
					: null;
			const isRequired =
				formGroup.dataset.required === 'true' ||
				field.hasAttribute( 'required' );
			let isValid = true;
			let errorMessage = '';

			const pageElement = field.closest( '.eipsi-page' );
			if (
				( isSelect || isRadio ) &&
				pageElement &&
				! this.isElementVisible( pageElement )
			) {
				this.clearFieldError( formGroup, field, {
					groupSelector,
				} );
				return true;
			}

			const errorElement = formGroup.querySelector( '.form-error' );
			const strings = this.config.strings || {};

			if ( isRange ) {
				if ( isRequired && field.dataset.touched === 'false' ) {
					isValid = false;
					errorMessage =
						strings.sliderRequired ||
						'Por favor, interactúe con la escala para continuar.';
				}
			} else if ( isSelect ) {
				if ( isRequired && ( ! field.value || field.value === '' ) ) {
					isValid = false;
					errorMessage =
						strings.requiredField || 'Este campo es obligatorio.';
				}
			} else if ( isRadio ) {
				const radioGroup = formGroup.querySelectorAll(
					`input[type="radio"][name="${ field.name }"]`
				);
				const isChecked = Array.from( radioGroup ).some(
					( radio ) => radio.checked
				);

				if ( isRequired && ! isChecked ) {
					isValid = false;
					errorMessage =
						strings.requiredField || 'Este campo es obligatorio.';
				}
			} else if ( isCheckbox ) {
				const checkboxGroup = formGroup.querySelectorAll(
					`input[type="checkbox"][name="${ field.name }"]`
				);
				const isChecked = Array.from( checkboxGroup ).some(
					( checkbox ) => checkbox.checked
				);

				if ( isRequired && ! isChecked ) {
					isValid = false;
					errorMessage =
						strings.requiredField || 'Este campo es obligatorio.';
				}
			} else if ( isRequired && ! field.value.trim() ) {
				isValid = false;
				errorMessage =
					strings.requiredField || 'Este campo es obligatorio.';
			} else if ( field.type === 'email' && field.value ) {
				const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
				if ( ! emailPattern.test( field.value ) ) {
					isValid = false;
					errorMessage =
						strings.invalidEmail ||
						'Por favor, introduzca una dirección de correo electrónico válida.';
				}
			}

			if ( isValid ) {
				this.clearFieldError( formGroup, field, {
					groupSelector,
				} );
			} else {
				formGroup.classList.add( 'has-error' );

				if ( errorElement ) {
					errorElement.style.display = 'block';
					errorElement.textContent = errorMessage;
				}

				if ( isRadio || isCheckbox ) {
					const groupedInputs = groupSelector
						? formGroup.querySelectorAll( groupSelector )
						: [];
					groupedInputs.forEach( ( input ) => {
						input.classList.add( 'error' );
						input.setAttribute( 'aria-invalid', 'true' );
					} );
				} else {
					field.classList.add( 'error' );
					field.setAttribute( 'aria-invalid', 'true' );
				}
			}

			return isValid;
		},

		validateCurrentPage( form ) {
			if ( ! form ) {
				return true;
			}

			const currentPage = this.getCurrentPage( form );
			const pageElement = this.getPageElement( form, currentPage );

			if ( ! pageElement ) {
				return true;
			}

			const fields = pageElement.querySelectorAll(
				'input, textarea, select'
			);
			let isValid = true;
			const validatedGroups = new Set();

			fields.forEach( ( field ) => {
				const formGroup = field.closest( '.form-group' );
				const groupKey = formGroup
					? formGroup.dataset.fieldName || formGroup.id || ''
					: '';

				if (
					( field.type === 'radio' || field.type === 'checkbox' ) &&
					groupKey
				) {
					if ( validatedGroups.has( groupKey ) ) {
						return;
					}
					validatedGroups.add( groupKey );
				}

				if ( ! this.validateField( field ) ) {
					isValid = false;
				}
			} );

			if ( ! isValid ) {
				this.focusFirstInvalidField( form, pageElement );
			}

			return isValid;
		},

		resetValidationState( form ) {
			if ( ! form ) {
				return;
			}

			form.querySelectorAll( '.form-group.has-error' ).forEach(
				( el ) => {
					el.classList.remove( 'has-error' );
				}
			);

			form.querySelectorAll(
				'input.error, textarea.error, select.error'
			).forEach( ( field ) => {
				field.classList.remove( 'error' );
			} );

			form.querySelectorAll( '[aria-invalid="true"]' ).forEach(
				( el ) => {
					el.removeAttribute( 'aria-invalid' );
				}
			);

			form.querySelectorAll( '.form-error' ).forEach( ( el ) => {
				el.style.display = 'none';
				el.textContent = '';
			} );
		},

		validateForm( form ) {
			if ( ! form ) {
				return true;
			}

			const navigator = this.getNavigator( form );
			let fieldsToValidate;

			if ( navigator && navigator.visitedPages.size > 0 ) {
				fieldsToValidate = [];
				const visitedPageNumbers = Array.from( navigator.visitedPages );

				visitedPageNumbers.forEach( ( pageNum ) => {
					const pageElement = this.getPageElement( form, pageNum );
					if ( pageElement ) {
						const pageFields = pageElement.querySelectorAll(
							'input, textarea, select'
						);
						fieldsToValidate.push( ...Array.from( pageFields ) );
					}
				} );
			} else {
				fieldsToValidate = Array.from(
					form.querySelectorAll( 'input, textarea, select' )
				);
			}

			let isValid = true;

			this.resetValidationState( form );

			const validatedGroups = new Set();

			fieldsToValidate.forEach( ( field ) => {
				const formGroup = field.closest( '.form-group' );
				const groupKey = formGroup
					? formGroup.dataset.fieldName || formGroup.id || ''
					: '';

				if (
					( field.type === 'radio' || field.type === 'checkbox' ) &&
					groupKey
				) {
					if ( validatedGroups.has( groupKey ) ) {
						return;
					}
					validatedGroups.add( groupKey );
				}

				if ( ! this.validateField( field ) ) {
					isValid = false;
				}
			} );

			if ( ! isValid ) {
				this.focusFirstInvalidField( form );
			}

			return isValid;
		},

		handleSubmit( e, form ) {
			e.preventDefault();

			this.getCurrentPage( form );

			if ( ! this.validateForm( form ) ) {
				this.showMessage(
					form,
					'error',
					'Por favor, completa todos los campos requeridos.'
				);
				this.focusFirstInvalidField( form );
				return;
			}

			this.submitForm( form );
		},

		submitForm( form ) {
			const submitButton = form.querySelector( 'button[type="submit"]' );
			const formData = new FormData( form );

			// Obtener IDs antes de enviar
			const participantId = getUniversalParticipantId();
			const sessionId = getSessionId();
			const formId = this.getFormId( form ) || '';

			formData.append( 'action', 'vas_dinamico_submit_form' );
			formData.append( 'nonce', this.config.nonce );
			formData.append( 'form_end_time', Date.now() );
			formData.append( 'participant_id', participantId );
			formData.append( 'session_id', sessionId );

			// Registrar en console para debugging
			if ( window.console && window.console.log ) {
				window.console.log( '📊 Form Submission:', {
					formId,
					participantId,
					sessionId,
					timestamp: new Date().toISOString(),
				} );
			}

			form.dataset.submitting = 'true';
			this.setFormLoading( form, true );

			if ( submitButton ) {
				submitButton.disabled = true;
				submitButton.dataset.originalText = submitButton.textContent;
				submitButton.textContent = 'Enviando...';
			}

			fetch( this.config.ajaxUrl, {
				method: 'POST',
				body: formData,
			} )
				.then( ( response ) => response.json() )
				.then( ( data ) => {
					if ( data.success ) {
						this.showMessage(
							form,
							'success',
							'¡Formulario enviado correctamente!'
						);

						if ( window.EIPSITracking ) {
							const trackingFormId =
								this.getTrackingFormId( form );
							if ( trackingFormId ) {
								window.EIPSITracking.recordSubmit(
									trackingFormId
								);
							}
						}

						setTimeout( () => {
							form.reset();

							const navigator = this.getNavigator( form );
							if ( navigator ) {
								navigator.reset();
							}

							this.setCurrentPage( form, 1, {
								trackChange: false,
							} );

							if ( navigator ) {
								navigator.pushHistory( 1 );
							}

							const sliders =
								form.querySelectorAll( '.vas-slider' );
							sliders.forEach( ( slider ) => {
								slider.dataset.touched = 'false';
								const valueDisplay = document.getElementById(
									slider.getAttribute( 'aria-labelledby' )
								);
								if ( valueDisplay ) {
									valueDisplay.textContent = slider.value;
								}
							} );
						}, 3000 );
					} else {
						this.showMessage(
							form,
							'error',
							'Ocurrió un error. Por favor, inténtelo de nuevo.'
						);
					}
				} )
				.catch( () => {
					this.showMessage(
						form,
						'error',
						'Ocurrió un error. Por favor, inténtelo de nuevo.'
					);
				} )
				.finally( () => {
					this.setFormLoading( form, false );
					delete form.dataset.submitting;

					if ( submitButton ) {
						submitButton.disabled = false;
						submitButton.textContent =
							submitButton.dataset.originalText || 'Enviar';
					}
				} );
		},

		setFormLoading( form, isLoading ) {
			const formContainer = form.closest(
				'.vas-dinamico-form, .eipsi-form'
			);
			if ( formContainer ) {
				if ( isLoading ) {
					formContainer.classList.add( 'form-loading' );
				} else {
					formContainer.classList.remove( 'form-loading' );
				}
			}
		},

		showMessage( form, type, message ) {
			this.clearMessages( form );

			const messageElement = document.createElement( 'div' );
			messageElement.className = `form-message form-message--${ type }`;
			messageElement.setAttribute(
				'role',
				type === 'error' ? 'alert' : 'status'
			);
			messageElement.setAttribute( 'aria-live', 'polite' );
			messageElement.dataset.messageState = 'visible';

			const prefersReducedMotion =
				window.matchMedia &&
				window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

			if ( prefersReducedMotion ) {
				messageElement.classList.add( 'no-motion' );
			}

			if ( type === 'success' ) {
				messageElement.innerHTML = `
                    <div class="form-message__icon">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <circle cx="12" cy="12" r="10" fill="currentColor" opacity="0.15"/>
                            <path d="M7 12L10.5 15.5L17 9" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="form-message__content">
                        <div class="form-message__title">${ message }</div>
                        <div class="form-message__subtitle">Gracias por completar el formulario</div>
                        <div class="form-message__note">Su respuesta ha sido registrada exitosamente</div>
                    </div>
                    <div class="form-message__confetti" aria-hidden="true"></div>
                `;

				if ( ! prefersReducedMotion ) {
					this.createConfetti( messageElement );
				}

				const submitButton = form.querySelector(
					'button[type="submit"]'
				);
				if ( submitButton ) {
					submitButton.disabled = true;
					setTimeout( () => {
						submitButton.disabled = false;
					}, 4000 );
				}

				setTimeout( () => {
					if ( messageElement.parentNode ) {
						messageElement.classList.add( 'form-message--fadeout' );
						messageElement.dataset.messageState = 'fading';
						setTimeout( () => {
							if ( messageElement.parentNode ) {
								messageElement.dataset.messageState = 'removed';
							}
						}, 500 );
					}
				}, 8000 );
			} else if ( type === 'error' ) {
				messageElement.innerHTML = `
                    <div class="form-message__icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <circle cx="12" cy="12" r="10" fill="currentColor" opacity="0.2"/>
                            <path d="M12 8V12M12 16H12.01" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div class="form-message__content">
                        <div class="form-message__title">${ message }</div>
                    </div>
                `;
			} else {
				messageElement.textContent = message;
			}

			const formContainer = form.closest(
				'.vas-dinamico-form, .eipsi-form'
			);
			if ( formContainer ) {
				formContainer.insertBefore( messageElement, form );
			} else {
				form.parentNode.insertBefore( messageElement, form );
			}

			if ( this.config.settings?.enableAutoScroll ) {
				this.scrollToElement( messageElement );
			}
		},

		createConfetti( messageElement ) {
			const confettiContainer = messageElement.querySelector(
				'.form-message__confetti'
			);

			if ( ! confettiContainer ) {
				return;
			}

			const colors = [
				'rgba(0, 90, 135, 0.8)',
				'rgba(25, 135, 84, 0.8)',
				'rgba(227, 242, 253, 0.9)',
				'rgba(255, 255, 255, 0.9)',
			];

			const confettiCount = 20;

			for ( let i = 0; i < confettiCount; i++ ) {
				const confetti = document.createElement( 'div' );
				confetti.className = 'confetti-particle';
				confetti.style.setProperty(
					'--confetti-color',
					colors[ Math.floor( Math.random() * colors.length ) ]
				);
				confetti.style.setProperty(
					'--confetti-x',
					Math.random() * 100 + '%'
				);
				confetti.style.setProperty(
					'--confetti-delay',
					Math.random() * 0.5 + 's'
				);
				confetti.style.setProperty(
					'--confetti-duration',
					Math.random() * 1 + 2 + 's'
				);
				confetti.style.setProperty(
					'--confetti-rotation',
					Math.random() * 360 + 'deg'
				);
				confetti.style.setProperty(
					'--confetti-scale',
					Math.random() * 0.5 + 0.5
				);

				confettiContainer.appendChild( confetti );
			}
		},

		clearMessages( form ) {
			const formContainer = form.closest(
				'.vas-dinamico-form, .eipsi-form'
			);
			if ( formContainer ) {
				const messages =
					formContainer.querySelectorAll( '.form-message' );
				messages.forEach( ( msg ) => msg.remove() );
			} else {
				const messages = form.querySelectorAll( '.form-message' );
				messages.forEach( ( msg ) => msg.remove() );
			}
		},

		focusFirstInvalidField( form, scope ) {
			if ( ! form ) {
				return;
			}

			let searchRoot = scope;

			if ( ! searchRoot ) {
				const currentPageElement = this.getPageElement(
					form,
					this.getCurrentPage( form )
				);
				searchRoot = currentPageElement || form;
			}

			let invalidGroups = Array.from(
				searchRoot.querySelectorAll( '.has-error' )
			).filter( ( group ) => this.isElementVisible( group ) );

			if ( invalidGroups.length === 0 && searchRoot !== form ) {
				invalidGroups = Array.from(
					form.querySelectorAll( '.has-error' )
				).filter( ( group ) => this.isElementVisible( group ) );
			}

			const targetGroup = invalidGroups[ 0 ];
			if ( ! targetGroup ) {
				return;
			}

			const focusableSelectors =
				'input:not([type="hidden"]), select, textarea, button, [tabindex]';
			const candidates = Array.from(
				targetGroup.querySelectorAll( focusableSelectors )
			).filter(
				( element ) =>
					! element.disabled &&
					this.isElementVisible( element ) &&
					element.getAttribute( 'tabindex' ) !== '-1'
			);

			const focusTarget = candidates[ 0 ] || null;
			const scrollTarget = focusTarget || targetGroup;

			if (
				this.config.settings?.enableAutoScroll &&
				this.isElementVisible( scrollTarget )
			) {
				this.scrollToElement( scrollTarget );
			}

			if ( focusTarget && typeof focusTarget.focus === 'function' ) {
				try {
					focusTarget.focus( { preventScroll: true } );
				} catch ( error ) {
					focusTarget.focus();
				}
			}
		},

		scrollToElement( element ) {
			if ( ! this.isElementVisible( element ) ) {
				return;
			}

			const offset = this.config.settings?.scrollOffset || 20;
			const elementRect = element.getBoundingClientRect();
			const elementPosition = elementRect.top + window.pageYOffset;
			const offsetPosition = elementPosition - offset;

			if ( this.config.settings?.smoothScroll ) {
				window.scrollTo( {
					top: offsetPosition,
					behavior: 'smooth',
				} );
			} else {
				window.scrollTo( 0, offsetPosition );
			}
		},

		isElementVisible( element ) {
			if ( ! element ) {
				return false;
			}

			if ( element.hidden ) {
				return false;
			}

			if ( element.offsetParent !== null ) {
				return true;
			}

			const style = window.getComputedStyle( element );

			return (
				style.position === 'fixed' &&
				style.visibility !== 'hidden' &&
				style.display !== 'none' &&
				parseFloat( style.opacity || '1' ) > 0
			);
		},

		handleConditionalNavigation( form, currentPage ) {
			const currentPageElement = this.getPageElement( form, currentPage );
			if ( ! currentPageElement ) {
				return null;
			}

			const conditionalFields = currentPageElement.querySelectorAll(
				'[data-conditional-logic]'
			);

			for ( const field of conditionalFields ) {
				let conditionalLogic = {};

				try {
					conditionalLogic = JSON.parse(
						field.dataset.conditionalLogic || '{}'
					);
				} catch ( error ) {
					continue;
				}

				if (
					! conditionalLogic.enabled ||
					! Array.isArray( conditionalLogic.rules )
				) {
					continue;
				}

				const fieldValue = this.getFieldValue( field );
				const matchingRule = this.findMatchingRule(
					conditionalLogic.rules,
					fieldValue
				);

				if ( ! matchingRule ) {
					continue;
				}

				if ( matchingRule.action === 'submit' ) {
					return 'submit';
				}

				if (
					matchingRule.action === 'goToPage' &&
					matchingRule.targetPage
				) {
					const parsedTarget = parseInt(
						matchingRule.targetPage,
						10
					);

					if ( Number.isNaN( parsedTarget ) ) {
						continue;
					}

					const totalPages = this.getTotalPages( form );
					const boundedTarget = Math.min(
						Math.max( parsedTarget, 1 ),
						totalPages
					);

					return boundedTarget;
				}
			}

			return null;
		},

		findMatchingRule( rules, fieldValue ) {
			if ( ! Array.isArray( rules ) ) {
				return null;
			}

			// For checkboxes, fieldValue is an array
			if ( Array.isArray( fieldValue ) ) {
				// Check if any selected value matches a rule
				for ( const value of fieldValue ) {
					const rule = rules.find( ( r ) => r.value === value );
					if ( rule ) {
						return rule;
					}
				}
			} else {
				// For select and radio
				return rules.find( ( rule ) => rule.value === fieldValue );
			}

			return null;
		},

		getFieldValue( field ) {
			const fieldType = field.dataset.fieldType;

			switch ( fieldType ) {
				case 'select':
					const select = field.querySelector( 'select' );
					return select ? select.value : '';

				case 'radio':
					const checkedRadio = field.querySelector(
						'input[type="radio"]:checked'
					);
					return checkedRadio ? checkedRadio.value : '';

				case 'checkbox':
					const checkedBoxes = field.querySelectorAll(
						'input[type="checkbox"]:checked'
					);
					return Array.from( checkedBoxes ).map( ( cb ) => cb.value );

				default:
					return '';
			}
		},

		getPageElement( form, pageNumber ) {
			const pages = form.querySelectorAll( '.eipsi-page' );

			for ( let index = 0; index < pages.length; index++ ) {
				const page = pages[ index ];
				const pageNum =
					parseInt( page.dataset.page || '', 10 ) || index + 1;

				if ( pageNum === pageNumber ) {
					return page;
				}
			}

			return null;
		},

		goToPage( form, pageNumber ) {
			if ( ! form ) {
				return;
			}

			if ( pageNumber === 'submit' ) {
				this.handleSubmit( { preventDefault: () => {} }, form );
				return;
			}

			const targetPage = parseInt( pageNumber, 10 );

			if ( Number.isNaN( targetPage ) ) {
				return;
			}

			this.setCurrentPage( form, targetPage );
		},
	};

	EIPSIForms.init();

	window.EIPSIForms = EIPSIForms;
	window.EIPSIForms.conditionalNavigators = EIPSIForms.navigators;
} )();
