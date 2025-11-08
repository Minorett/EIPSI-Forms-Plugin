/**
 * EIPSI Forms Frontend JavaScript
 * Handles form validation, submission, and user interactions
 * Works with both shortcode and block-rendered forms
 */

( function () {
	'use strict';

	const EIPSIForms = {
		config: window.eipsiFormsConfig || {},
		forms: [],

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

			this.applyTestSelectors( form );

			this.populateDeviceInfo( form );
			this.initPagination( form );
			this.initVasSliders( form );
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
			const ua = navigator.userAgent;
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
			const ua = navigator.userAgent;
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
			const ua = navigator.userAgent;
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
				const totalPages = pages.length;
				form.dataset.totalPages = totalPages;

				const totalPagesField = form.querySelector( '.total-pages' );
				if ( totalPagesField ) {
					totalPagesField.textContent = totalPages;
				}

				const progressContainer =
					form.querySelector( '.form-progress' );
				if ( progressContainer && totalPages > 1 ) {
					progressContainer.style.display = '';
				}

				this.updatePaginationDisplay( form, 1, totalPages );
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

		handlePagination( form, direction ) {
			const currentPageField = form.querySelector(
				'.eipsi-current-page'
			);
			const currentPage = parseInt( currentPageField?.value || '1' );
			const totalPages = parseInt( form.dataset.totalPages || '1' );

			let newPage = currentPage;

			if ( direction === 'next' ) {
				// Check for conditional logic on current page
				const conditionalTarget = this.handleConditionalNavigation(
					form,
					currentPage
				);
				if ( conditionalTarget === 'submit' ) {
					// Submit the form
					this.handleSubmit( { preventDefault: () => {} }, form );
					return;
				} else if ( conditionalTarget !== null ) {
					newPage = conditionalTarget;
				} else if ( currentPage < totalPages ) {
					newPage = currentPage + 1;
				}
			} else if ( direction === 'prev' && currentPage > 1 ) {
				newPage = currentPage - 1;
			}

			if ( newPage !== currentPage ) {
				if ( currentPageField ) {
					currentPageField.value = newPage;
				}
				form.dataset.currentPage = newPage;
				this.updatePaginationDisplay( form, newPage, totalPages );

				if ( window.EIPSITracking ) {
					const trackingFormId = this.getTrackingFormId( form );
					if ( trackingFormId ) {
						window.EIPSITracking.recordPageChange(
							trackingFormId,
							newPage
						);
					}
				}
			}
		},

		updatePaginationDisplay( form, currentPage, totalPages ) {
			const prevButton = form.querySelector( '.eipsi-prev-button' );
			const nextButton = form.querySelector( '.eipsi-next-button' );
			const submitButton = form.querySelector( '.eipsi-submit-button' );
			const progressText = form.querySelector(
				'.form-progress .current-page'
			);

			if ( prevButton ) {
				prevButton.style.display = currentPage > 1 ? '' : 'none';
			}

			if ( nextButton ) {
				nextButton.style.display =
					currentPage < totalPages ? '' : 'none';
			}

			if ( submitButton ) {
				submitButton.style.display =
					currentPage === totalPages ? '' : 'none';
			}

			if ( progressText ) {
				progressText.textContent = currentPage;
			}

			this.updatePageVisibility( form, currentPage, totalPages );

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

		updatePageVisibility( form, currentPage, totalPages ) {
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

		validateField( field ) {
			const formGroup = field.closest( '.form-group' );
			if ( ! formGroup ) {
				return true;
			}

			const errorElement = formGroup.querySelector( '.form-error' );
			let isValid = true;
			let errorMessage = '';

			// Validación para SELECT
			if ( field.tagName === 'SELECT' && field.hasAttribute( 'required' ) ) {
				if ( ! field.value || field.value === '' ) {
					isValid = false;
					errorMessage = 'Este campo es obligatorio.';
				}
			} else if ( field.type === 'radio' ) {
				const radioGroup = formGroup.querySelectorAll(
					`input[type="radio"][name="${ field.name }"]`
				);
				const isChecked = Array.from( radioGroup ).some(
					( radio ) => radio.checked
				);
				const isRequired =
					formGroup.dataset.required === 'true' ||
					field.hasAttribute( 'required' );

				if ( isRequired && ! isChecked ) {
					isValid = false;
					errorMessage = 'Este campo es obligatorio.';
				}
			} else if ( field.type === 'checkbox' ) {
				const checkboxGroup = formGroup.querySelectorAll(
					`input[type="checkbox"][name="${ field.name }"]`
				);
				const isChecked = Array.from( checkboxGroup ).some(
					( checkbox ) => checkbox.checked
				);
				const isRequired =
					formGroup.dataset.required === 'true' ||
					field.hasAttribute( 'required' );

				if ( isRequired && ! isChecked ) {
					isValid = false;
					errorMessage = 'Este campo es obligatorio.';
				}
			} else if (
				field.hasAttribute( 'required' ) &&
				! field.value.trim()
			) {
				isValid = false;
				errorMessage = 'Este campo es obligatorio.';
			} else if ( field.type === 'email' && field.value ) {
				const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
				if ( ! emailPattern.test( field.value ) ) {
					isValid = false;
					errorMessage = 'Por favor, introduzca una dirección de correo electrónico válida.';
				}
			}

			if ( isValid ) {
				formGroup.classList.remove( 'has-error' );
				if ( errorElement ) {
					errorElement.style.display = 'none';
					errorElement.textContent = '';
				}
			} else {
				formGroup.classList.add( 'has-error' );
				if ( errorElement ) {
					errorElement.style.display = 'block';
					errorElement.textContent = errorMessage;
				}
			}

			return isValid;
		},

		validateForm( form ) {
			const fields = form.querySelectorAll( 'input, textarea, select' );
			let isValid = true;
			
			// Limpiar errores previos
			form.querySelectorAll( '.has-error' ).forEach( el => {
				el.classList.remove( 'has-error' );
			});
			form.querySelectorAll( '.form-error' ).forEach( el => {
				el.style.display = 'none';
			});

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

			return isValid;
		},

		handleSubmit( e, form ) {
			e.preventDefault();

			console.log('=== VALIDATING FORM ===');

			if ( ! this.validateForm( form ) ) {
				console.log('=== FORM VALIDATION FAILED ===');
				const firstError = form.querySelector( '.has-error' );
				if ( firstError && this.config.settings?.enableAutoScroll ) {
					this.scrollToElement( firstError );
				}
				
				// Mostrar mensaje general de error
				this.showMessage(
					form,
					'error',
					'Por favor, completa todos los campos requeridos.'
				);
				return;
			}

			console.log('=== FORM VALIDATION PASSED - SUBMITTING ===');
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
					console.log('Form submission response:', data);
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
				.catch( ( error ) => {
					console.error( 'Form submission error:', error );
					console.log('=== FORM SUBMISSION FAILED ===');
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

		scrollToElement( element ) {
			const offset = this.config.settings?.scrollOffset || 20;
			const elementPosition =
				element.getBoundingClientRect().top + window.pageYOffset;
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

		handleConditionalNavigation( form, currentPage ) {
			const currentPageElement = this.getPageElement( form, currentPage );
			if ( ! currentPageElement ) {
				return null;
			}

			// Find all conditional logic fields on current page
			const conditionalFields = currentPageElement.querySelectorAll(
				'[data-conditional-logic]'
			);

			for ( const field of conditionalFields ) {
				const conditionalLogic = JSON.parse(
					field.dataset.conditionalLogic || '{}'
				);
				if ( ! conditionalLogic.enabled || ! conditionalLogic.rules ) {
					continue;
				}

				const fieldValue = this.getFieldValue( field );
				const matchingRule = this.findMatchingRule(
					conditionalLogic.rules,
					fieldValue
				);

				if ( matchingRule ) {
					if ( matchingRule.action === 'submit' ) {
						return 'submit';
					} else if (
						matchingRule.action === 'goToPage' &&
						matchingRule.targetPage
					) {
						return matchingRule.targetPage;
					}
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
			const fieldName = field.dataset.fieldName;

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
			for ( const page of pages ) {
				const pageNum = parseInt( page.dataset.page );
				if ( pageNum === pageNumber ) {
					return page;
				}
			}
			return null;
		},

		goToPage( form, pageNumber ) {
			const currentPageField = form.querySelector(
				'.eipsi-current-page'
			);
			const totalPages = parseInt( form.dataset.totalPages || '1' );

			if ( pageNumber === 'submit' ) {
				// Handle form submission
				this.handleSubmit( { preventDefault: () => {} }, form );
				return;
			}

			if ( pageNumber >= 1 && pageNumber <= totalPages ) {
				if ( currentPageField ) {
					currentPageField.value = pageNumber;
				}
				form.dataset.currentPage = pageNumber;
				this.updatePaginationDisplay( form, pageNumber, totalPages );

				if ( window.EIPSITracking ) {
					const trackingFormId = this.getTrackingFormId( form );
					if ( trackingFormId ) {
						window.EIPSITracking.recordPageChange(
							trackingFormId,
							pageNumber
						);
					}
				}
			}
		},
	};

	EIPSIForms.init();

	window.EIPSIForms = EIPSIForms;
} )();