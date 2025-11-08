/**
 * EIPSI Forms Frontend JavaScript
 * Handles form validation, submission, and user interactions
 * Works with both shortcode and block-rendered forms
 */

( function () {
	'use strict';

	/* global navigator */

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

				default:
					return '';
			}
		}

		findMatchingRule( rules, fieldValue ) {
			if ( ! Array.isArray( rules ) ) {
				return null;
			}

			if ( Array.isArray( fieldValue ) ) {
				for ( const value of fieldValue ) {
					const rule = rules.find(
						( r ) => r.matchValue === value || r.value === value
					);
					if ( rule ) {
						return rule;
					}
				}
			} else {
				return rules.find(
					( rule ) =>
						rule.matchValue === fieldValue ||
						rule.value === fieldValue
				);
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
					'input[type="radio"], input[type="checkbox"], select'
				);

				inputs.forEach( ( input ) => {
					input.addEventListener( 'change', () => {
						const navigator = this.getNavigator( form );
						if ( navigator ) {
							const currentPage = this.getCurrentPage( form );
							const nextPageResult =
								navigator.getNextPage( currentPage );

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

			if ( window.console && window.console.log ) {
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
			const browserField = form.querySelector(
				'.eipsi-browser-placeholder'
			);
			const osField = form.querySelector( '.eipsi-os-placeholder' );
			const screenField = form.querySelector(
				'.eipsi-screen-placeholder'
			);
			const startTimeField = form.querySelector( '.eipsi-start-time' );

			if ( deviceField ) {
				deviceField.value = this.getDeviceType();
			}

			if ( browserField ) {
				browserField.value = this.getBrowser();
			}

			if ( osField ) {
				osField.value = this.getOS();
			}

			if ( screenField ) {
				screenField.value = window.screen.width || '';
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

		getBrowser() {
			const ua =
				typeof navigator !== 'undefined' ? navigator.userAgent : '';
			let browser = 'Unknown';

			if ( ua.indexOf( 'Firefox' ) > -1 ) {
				browser = 'Firefox';
			} else if ( ua.indexOf( 'SamsungBrowser' ) > -1 ) {
				browser = 'Samsung Browser';
			} else if (
				ua.indexOf( 'Opera' ) > -1 ||
				ua.indexOf( 'OPR' ) > -1
			) {
				browser = 'Opera';
			} else if ( ua.indexOf( 'Trident' ) > -1 ) {
				browser = 'Internet Explorer';
			} else if ( ua.indexOf( 'Edge' ) > -1 ) {
				browser = 'Edge';
			} else if ( ua.indexOf( 'Edg' ) > -1 ) {
				browser = 'Edge Chromium';
			} else if ( ua.indexOf( 'Chrome' ) > -1 ) {
				browser = 'Chrome';
			} else if ( ua.indexOf( 'Safari' ) > -1 ) {
				browser = 'Safari';
			}

			return browser;
		},

		getOS() {
			const ua =
				typeof navigator !== 'undefined' ? navigator.userAgent : '';
			let os = 'Unknown';

			if ( ua.indexOf( 'Win' ) > -1 ) {
				os = 'Windows';
			} else if ( ua.indexOf( 'Mac' ) > -1 ) {
				os = 'MacOS';
			} else if ( ua.indexOf( 'X11' ) > -1 ) {
				os = 'UNIX';
			} else if ( ua.indexOf( 'Linux' ) > -1 ) {
				os = 'Linux';
			} else if ( /Android/.test( ua ) ) {
				os = 'Android';
			} else if ( /iPhone|iPad|iPod/.test( ua ) ) {
				os = 'iOS';
			}

			return os;
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

			if ( prevButton ) {
				prevButton.addEventListener( 'click', ( e ) => {
					e.preventDefault();
					this.handlePagination( form, 'prev' );
				} );
			}

			if ( nextButton ) {
				nextButton.addEventListener( 'click', ( e ) => {
					e.preventDefault();
					this.handlePagination( form, 'next' );
				} );
			}
		},

		initVasSliders( form ) {
			const sliders = form.querySelectorAll( '.vas-slider' );

			sliders.forEach( ( slider ) => {
				const showValue = slider.dataset.showValue === 'true';

				if ( showValue ) {
					const valueDisplay = document.getElementById(
						slider.getAttribute( 'aria-labelledby' )
					);

					if ( valueDisplay ) {
						slider.addEventListener( 'input', ( e ) => {
							const value = e.target.value;
							valueDisplay.textContent = value;
							slider.setAttribute( 'aria-valuenow', value );
						} );
					}
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
					// Add click handler for toggle behavior
					radio.addEventListener( 'click', () => {
						const wasChecked = radio.checked;

						// If clicking an already checked radio, uncheck it
						if ( wasChecked ) {
							// Use setTimeout to allow the browser to complete the default click behavior first
							setTimeout( () => {
								radio.checked = false;
								this.validateField( radio );
							}, 0 );
						} else {
							// Validate the field when a new option is selected
							setTimeout( () => {
								this.validateField( radio );
							}, 0 );
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

			if ( window.console && window.console.log ) {
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

			const hasHistory = navigator && navigator.history.length > 1;

			if ( prevButton ) {
				prevButton.style.display =
					hasHistory || currentPage > 1 ? '' : 'none';
			}

			const shouldShowNext = navigator
				? ! navigator.shouldSubmit( currentPage ) &&
				  currentPage < totalPages
				: currentPage < totalPages;

			if ( nextButton ) {
				nextButton.style.display = shouldShowNext ? '' : 'none';
			}

			const shouldShowSubmit = navigator
				? navigator.shouldSubmit( currentPage ) ||
				  currentPage === totalPages
				: currentPage === totalPages;

			if ( submitButton ) {
				submitButton.style.display = shouldShowSubmit ? '' : 'none';
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

			if ( isSelect ) {
				if ( isRequired && ( ! field.value || field.value === '' ) ) {
					isValid = false;
					errorMessage = 'Este campo es obligatorio.';
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
					errorMessage = 'Este campo es obligatorio.';
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
					errorMessage = 'Este campo es obligatorio.';
				}
			} else if ( isRequired && ! field.value.trim() ) {
				isValid = false;
				errorMessage = 'Este campo es obligatorio.';
			} else if ( field.type === 'email' && field.value ) {
				const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
				if ( ! emailPattern.test( field.value ) ) {
					isValid = false;
					errorMessage =
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

			formData.append( 'action', 'vas_dinamico_submit_form' );
			formData.append( 'nonce', this.config.nonce );

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

						form.reset();
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
			messageElement.className = `form-message ${ type }`;
			messageElement.textContent = message;

			form.appendChild( messageElement );

			if ( this.config.settings?.enableAutoScroll ) {
				this.scrollToElement( messageElement );
			}

			if ( type === 'success' ) {
				setTimeout( () => {
					messageElement.remove();
				}, 5000 );
			}
		},

		clearMessages( form ) {
			const messages = form.querySelectorAll( '.form-message' );
			messages.forEach( ( msg ) => msg.remove() );
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
} )();
