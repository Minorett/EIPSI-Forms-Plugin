/**
 * EIPSI Forms - Conditional Flow Automated Tests
 *
 * This script provides automated testing for the ConditionalNavigator state machine.
 * Load this script in browser console on a page with an EIPSI form to run tests.
 *
 * Usage:
 *   1. Load form page in browser
 *   2. Open DevTools Console
 *   3. Copy and paste this entire script
 *   4. Run: EIPSIConditionalTests.runAll()
 *
 * Or load automatically with: ?autotest query parameter
 */

/* eslint-disable no-console, jsdoc/require-param-type, @wordpress/no-unused-vars-before-return */

( function () {
	'use strict';

	const EIPSIConditionalTests = {
		results: [],
		form: null,
		navigator: null,

		/**
		 * Log test result
		 * @param message
		 * @param status
		 */
		log( message, status = 'info' ) {
			const emoji = {
				info: 'ℹ️',
				success: '✅',
				error: '❌',
				warning: '⚠️',
			};
			const timestamp = new Date().toLocaleTimeString();
			const logMessage = `[${ timestamp }] ${ emoji[ status ] } ${ message }`;
			console.log( logMessage );
			this.results.push( { message, status, timestamp } );
		},

		/**
		 * Initialize test environment
		 */
		init() {
			this.log( 'Initializing test environment...', 'info' );

			// Find form
			this.form = document.querySelector( '.vas-dinamico-form' );
			if ( ! this.form ) {
				this.log( '❌ CRITICAL: Form not found on page', 'error' );
				return false;
			}
			this.log( 'Form found', 'success' );

			// Check for EIPSIForms global
			if ( ! window.EIPSIForms ) {
				this.log( '❌ CRITICAL: EIPSIForms not loaded', 'error' );
				return false;
			}
			this.log( 'EIPSIForms global loaded', 'success' );

			// Get navigator instance
			if ( window.EIPSIForms.conditionalNavigators ) {
				this.navigator = window.EIPSIForms.conditionalNavigators.get(
					this.form
				);
			}

			if ( ! this.navigator ) {
				this.log(
					'⚠️ WARNING: ConditionalNavigator not initialized (might be normal for simple forms)',
					'warning'
				);
			} else {
				this.log( 'ConditionalNavigator initialized', 'success' );
			}

			return true;
		},

		/**
		 * Test 1: Navigator State Properties
		 */
		testNavigatorState() {
			this.log(
				'\n📋 Test 1: ConditionalNavigator State Properties',
				'info'
			);

			if ( ! this.navigator ) {
				this.log( 'Skipping - no navigator (linear form?)', 'warning' );
				return;
			}

			// Check required properties
			const requiredProps = [
				'form',
				'history',
				'visitedPages',
				'skippedPages',
				'fieldCache',
			];

			requiredProps.forEach( ( prop ) => {
				if ( this.navigator[ prop ] !== undefined ) {
					this.log( `Property '${ prop }' exists`, 'success' );
				} else {
					this.log( `Property '${ prop }' missing`, 'error' );
				}
			} );

			// Check data types
			if ( Array.isArray( this.navigator.history ) ) {
				this.log( 'history is Array', 'success' );
			} else {
				this.log( 'history should be Array', 'error' );
			}

			if ( this.navigator.visitedPages instanceof Set ) {
				this.log( 'visitedPages is Set', 'success' );
			} else {
				this.log( 'visitedPages should be Set', 'error' );
			}

			if ( this.navigator.skippedPages instanceof Set ) {
				this.log( 'skippedPages is Set', 'success' );
			} else {
				this.log( 'skippedPages should be Set', 'error' );
			}

			// Log current state
			console.log( '📊 Current Navigator State:', {
				history: [ ...this.navigator.history ],
				visited: [ ...this.navigator.visitedPages ],
				skipped: [ ...this.navigator.skippedPages ],
				fieldCacheSize: this.navigator.fieldCache.size,
			} );
		},

		/**
		 * Test 2: Conditional Logic Serialization
		 */
		testConditionalLogicSerialization() {
			this.log( '\n📋 Test 2: Conditional Logic Serialization', 'info' );

			const fields = this.form.querySelectorAll(
				'[data-conditional-logic]'
			);

			if ( fields.length === 0 ) {
				this.log(
					'No conditional fields found (linear form)',
					'warning'
				);
				return;
			}

			this.log( `Found ${ fields.length } conditional field(s)`, 'info' );

			fields.forEach( ( field, index ) => {
				const fieldName =
					field.dataset.fieldName || `Field ${ index + 1 }`;
				this.log( `\nAnalyzing: ${ fieldName }`, 'info' );

				const jsonString = field.dataset.conditionalLogic;

				// Check if JSON is valid
				try {
					const logic = JSON.parse( jsonString );

					// Check schema
					if (
						typeof logic === 'object' &&
						! Array.isArray( logic )
					) {
						// New format
						if ( logic.enabled === undefined ) {
							this.log( 'Missing "enabled" property', 'warning' );
						}

						if ( ! logic.rules ) {
							this.log( 'Missing "rules" array', 'error' );
						} else if ( Array.isArray( logic.rules ) ) {
							this.log(
								`${ logic.rules.length } rule(s) configured`,
								'success'
							);

							// Check rule structure
							logic.rules.forEach( ( rule, ruleIndex ) => {
								const ruleNum = ruleIndex + 1;

								if ( ! rule.id ) {
									this.log(
										`Rule ${ ruleNum }: Missing id`,
										'warning'
									);
								}

								if ( ! rule.matchValue && ! rule.value ) {
									this.log(
										`Rule ${ ruleNum }: Missing matchValue/value`,
										'error'
									);
								}

								if ( ! rule.action ) {
									this.log(
										`Rule ${ ruleNum }: Missing action`,
										'error'
									);
								} else {
									const validActions = [
										'goToPage',
										'nextPage',
										'submit',
									];
									if (
										! validActions.includes( rule.action )
									) {
										this.log(
											`Rule ${ ruleNum }: Invalid action '${ rule.action }'`,
											'error'
										);
									}

									if (
										rule.action === 'goToPage' &&
										! rule.targetPage
									) {
										this.log(
											`Rule ${ ruleNum }: goToPage missing targetPage`,
											'error'
										);
									}
								}

								// Log rule details
								console.log(
									`  Rule ${ ruleNum }:`,
									`"${ rule.matchValue || rule.value }" →`,
									rule.action === 'goToPage'
										? `Page ${ rule.targetPage }`
										: rule.action
								);
							} );
						}

						// Check default action
						if ( logic.defaultAction ) {
							this.log(
								`Default action: ${ logic.defaultAction }`,
								'info'
							);
						}
					} else if ( Array.isArray( logic ) ) {
						// Legacy format
						this.log(
							`Legacy array format (${ logic.length } rules)`,
							'warning'
						);
						this.log( 'Should auto-migrate to new format', 'info' );
					} else {
						this.log( 'Unknown logic format', 'error' );
					}
				} catch ( error ) {
					this.log( `Invalid JSON: ${ error.message }`, 'error' );
					console.error( 'JSON Parse Error:', error );
					console.log( 'Raw JSON:', jsonString );
				}
			} );
		},

		/**
		 * Test 3: Current Page State
		 */
		testCurrentPageState() {
			this.log( '\n📋 Test 3: Current Page State', 'info' );

			const currentPageAttr =
				this.form.getAttribute( 'data-current-page' );
			const currentPage = parseInt( currentPageAttr, 10 );

			if ( isNaN( currentPage ) ) {
				this.log( 'Invalid data-current-page attribute', 'error' );
			} else {
				this.log( `Current page: ${ currentPage }`, 'info' );

				const totalPages = window.EIPSIForms.getTotalPages( this.form );
				this.log( `Total pages: ${ totalPages }`, 'info' );

				if ( currentPage < 1 || currentPage > totalPages ) {
					this.log( 'Current page out of bounds', 'error' );
				} else {
					this.log( 'Current page within bounds', 'success' );
				}

				// Check if current page element exists
				const pageElement = this.form.querySelector(
					`.form-page[data-page="${ currentPage }"]`
				);
				if ( ! pageElement ) {
					this.log( 'Current page element not found', 'error' );
				} else {
					this.log( 'Current page element found', 'success' );

					// Check visibility
					const isHidden =
						pageElement.style.display === 'none' ||
						pageElement.classList.contains( 'hidden' );
					if ( isHidden ) {
						this.log(
							'Current page is hidden (should be visible)',
							'error'
						);
					} else {
						this.log( 'Current page is visible', 'success' );
					}
				}
			}
		},

		/**
		 * Test 4: Navigation Buttons
		 */
		testNavigationButtons() {
			this.log( '\n📋 Test 4: Navigation Buttons', 'info' );

			const currentPageAttr =
				this.form.getAttribute( 'data-current-page' );
			const currentPage = parseInt( currentPageAttr, 10 );
			const totalPages = window.EIPSIForms.getTotalPages( this.form );

			// Find buttons
			const prevBtn = this.form.querySelector( '.prev-button' );
			const nextBtn = this.form.querySelector( '.next-button' );
			const submitBtn = this.form.querySelector( '.submit-button' );

			// Check prev button
			if ( currentPage === 1 ) {
				if (
					prevBtn &&
					! prevBtn.disabled &&
					prevBtn.offsetParent !== null
				) {
					this.log(
						'Prev button should be hidden on page 1',
						'warning'
					);
				} else {
					this.log(
						'Prev button correctly hidden on page 1',
						'success'
					);
				}
			} else if ( ! prevBtn ) {
				this.log( 'Prev button not found', 'error' );
			} else if ( prevBtn.disabled ) {
				this.log( 'Prev button should not be disabled', 'error' );
			} else {
				this.log( 'Prev button available', 'success' );
			}

			// Check next/submit buttons
			if ( currentPage === totalPages ) {
				if ( submitBtn ) {
					this.log( 'Submit button found on last page', 'success' );
				} else {
					this.log(
						'Submit button not found on last page',
						'warning'
					);
				}

				if ( nextBtn && nextBtn.offsetParent !== null ) {
					this.log(
						'Next button should be hidden on last page',
						'warning'
					);
				}
			} else if ( ! nextBtn ) {
				this.log( 'Next button not found', 'error' );
			} else {
				this.log( 'Next button available', 'success' );
			}
		},

		/**
		 * Test 5: Field Validation
		 */
		testFieldValidation() {
			this.log( '\n📋 Test 5: Field Validation', 'info' );

			const currentPageAttr =
				this.form.getAttribute( 'data-current-page' );
			const currentPage = parseInt( currentPageAttr, 10 );

			const pageElement = this.form.querySelector(
				`.form-page[data-page="${ currentPage }"]`
			);

			if ( ! pageElement ) {
				this.log( 'Cannot test - page element not found', 'error' );
				return;
			}

			const requiredFields = pageElement.querySelectorAll(
				'[data-required="true"]'
			);

			this.log(
				`Found ${ requiredFields.length } required field(s) on current page`,
				'info'
			);

			requiredFields.forEach( ( field ) => {
				const fieldName = field.dataset.fieldName || 'Unknown';
				const fieldType = field.dataset.fieldType;

				this.log(
					`  Checking: ${ fieldName } (${ fieldType })`,
					'info'
				);

				// Check if field has validation
				const errorContainer = field.querySelector( '.form-error' );
				if ( ! errorContainer ) {
					this.log( '    Missing .form-error container', 'warning' );
				}

				// Check for aria-required
				let hasAriaRequired = false;
				if ( fieldType === 'text' || fieldType === 'email' ) {
					const input = field.querySelector( 'input' );
					hasAriaRequired =
						input &&
						input.getAttribute( 'aria-required' ) === 'true';
				} else if (
					fieldType === 'radio' ||
					fieldType === 'checkbox'
				) {
					const inputs = field.querySelectorAll( 'input' );
					hasAriaRequired =
						inputs.length > 0 &&
						inputs[ 0 ].getAttribute( 'aria-required' ) === 'true';
				}

				if ( ! hasAriaRequired ) {
					this.log(
						'    Missing aria-required attribute',
						'warning'
					);
				}
			} );
		},

		/**
		 * Test 6: History Stack Integrity
		 */
		testHistoryStack() {
			this.log( '\n📋 Test 6: History Stack Integrity', 'info' );

			if ( ! this.navigator ) {
				this.log( 'Skipping - no navigator', 'warning' );
				return;
			}

			const history = this.navigator.history;
			const visited = this.navigator.visitedPages;
			const skipped = this.navigator.skippedPages;

			// Check history is not empty
			if ( history.length === 0 ) {
				this.log(
					'History is empty (should have at least page 1)',
					'error'
				);
			} else {
				this.log(
					`History has ${ history.length } entry/entries`,
					'success'
				);
			}

			// Check all history pages are in visited set
			let historyInSync = true;
			history.forEach( ( page ) => {
				if ( ! visited.has( page ) ) {
					this.log(
						`Page ${ page } in history but not in visitedPages`,
						'error'
					);
					historyInSync = false;
				}
			} );

			if ( historyInSync && history.length > 0 ) {
				this.log( 'History and visitedPages in sync', 'success' );
			}

			// Check no overlap between visited and skipped
			const overlap = [ ...visited ].filter( ( page ) =>
				skipped.has( page )
			);
			if ( overlap.length > 0 ) {
				this.log(
					`Pages ${ overlap.join(
						', '
					) } are both visited AND skipped`,
					'error'
				);
			} else if ( visited.size > 0 && skipped.size > 0 ) {
				this.log( 'No overlap between visited and skipped', 'success' );
			}

			// Check current page is last in history
			const currentPageAttr =
				this.form.getAttribute( 'data-current-page' );
			const currentPage = parseInt( currentPageAttr, 10 );
			const lastHistoryPage = history[ history.length - 1 ];

			if ( currentPage !== lastHistoryPage ) {
				this.log(
					`Current page (${ currentPage }) doesn't match last history entry (${ lastHistoryPage })`,
					'error'
				);
			} else {
				this.log( 'Current page matches history', 'success' );
			}
		},

		/**
		 * Test 7: Simulate Navigation (if possible)
		 */
		async testSimulateNavigation() {
			this.log( '\n📋 Test 7: Simulate Navigation', 'info' );

			const nextBtn = this.form.querySelector( '.next-button' );
			const prevBtn = this.form.querySelector( '.prev-button' );

			if ( ! nextBtn || nextBtn.disabled ) {
				this.log(
					'Cannot test - Next button not available',
					'warning'
				);
				return;
			}

			this.log( 'Attempting to click Next button...', 'info' );

			const initialPage = parseInt(
				this.form.getAttribute( 'data-current-page' ),
				10
			);

			// Store initial state
			const initialHistory = this.navigator
				? [ ...this.navigator.history ]
				: null;

			// Click next
			nextBtn.click();

			// Wait for page change
			await new Promise( ( resolve ) => setTimeout( resolve, 500 ) );

			const newPage = parseInt(
				this.form.getAttribute( 'data-current-page' ),
				10
			);

			if ( newPage !== initialPage ) {
				this.log(
					`Navigation successful: ${ initialPage } → ${ newPage }`,
					'success'
				);

				if ( this.navigator ) {
					const newHistory = [ ...this.navigator.history ];
					if ( newHistory.length > initialHistory.length ) {
						this.log( 'History updated correctly', 'success' );
					} else {
						this.log( 'History not updated', 'error' );
					}
				}
			} else {
				this.log(
					'Page did not change (validation error or last page?)',
					'warning'
				);
			}

			// Try to go back if we moved forward
			if ( newPage !== initialPage && prevBtn && ! prevBtn.disabled ) {
				this.log( 'Attempting to click Prev button...', 'info' );

				prevBtn.click();
				await new Promise( ( resolve ) => setTimeout( resolve, 500 ) );

				const backPage = parseInt(
					this.form.getAttribute( 'data-current-page' ),
					10
				);

				if ( backPage === initialPage ) {
					this.log( 'Backward navigation successful', 'success' );
				} else {
					this.log(
						`Expected to return to page ${ initialPage }, now on page ${ backPage }`,
						'error'
					);
				}
			}
		},

		/**
		 * Generate summary report
		 */
		generateSummary() {
			console.log( '\n' + '='.repeat( 60 ) );
			this.log( '\n📊 Test Summary Report', 'info' );
			console.log( '='.repeat( 60 ) );

			const total = this.results.length;
			const passed = this.results.filter(
				( r ) => r.status === 'success'
			).length;
			const failed = this.results.filter(
				( r ) => r.status === 'error'
			).length;
			const warnings = this.results.filter(
				( r ) => r.status === 'warning'
			).length;

			console.log( `Total checks: ${ total }` );
			console.log( `✅ Passed: ${ passed }` );
			console.log( `❌ Failed: ${ failed }` );
			console.log( `⚠️ Warnings: ${ warnings }` );

			const passRate =
				total > 0 ? ( ( passed / total ) * 100 ).toFixed( 1 ) : 0;
			console.log( `\nPass rate: ${ passRate }%` );

			if ( failed === 0 && warnings === 0 ) {
				console.log( '\n🎉 All tests passed!' );
			} else if ( failed === 0 ) {
				console.log( '\n✅ All tests passed (with warnings)' );
			} else {
				console.log( '\n⚠️ Some tests failed - review results above' );
			}

			console.log( '='.repeat( 60 ) );

			return {
				total,
				passed,
				failed,
				warnings,
				passRate: parseFloat( passRate ),
				results: this.results,
			};
		},

		/**
		 * Run all tests
		 */
		async runAll() {
			console.clear();
			this.results = [];

			console.log( '🧪 EIPSI Forms - Conditional Flow Tests' );
			console.log( '='.repeat( 60 ) );

			// Initialize
			if ( ! this.init() ) {
				console.log( '\n❌ Initialization failed - cannot continue' );
				return;
			}

			console.log( '\n🚀 Starting test suite...\n' );

			// Run tests
			try {
				this.testNavigatorState();
				this.testConditionalLogicSerialization();
				this.testCurrentPageState();
				this.testNavigationButtons();
				this.testFieldValidation();
				this.testHistoryStack();
				await this.testSimulateNavigation();
			} catch ( error ) {
				this.log( `Test execution error: ${ error.message }`, 'error' );
				console.error( 'Test Error:', error );
			}

			// Generate summary
			const summary = this.generateSummary();

			// Return results for programmatic access
			return summary;
		},

		/**
		 * Run specific test
		 * @param testName
		 */
		async run( testName ) {
			this.results = [];

			if ( ! this.init() ) {
				console.log( '❌ Initialization failed' );
				return;
			}

			const tests = {
				navigator: () => this.testNavigatorState(),
				serialization: () => this.testConditionalLogicSerialization(),
				page: () => this.testCurrentPageState(),
				buttons: () => this.testNavigationButtons(),
				validation: () => this.testFieldValidation(),
				history: () => this.testHistoryStack(),
				navigation: () => this.testSimulateNavigation(),
			};

			if ( tests[ testName ] ) {
				await tests[ testName ]();
				return this.generateSummary();
			}
			console.log( `❌ Unknown test: ${ testName }` );
			console.log(
				'Available tests:',
				Object.keys( tests ).join( ', ' )
			);
		},
	};

	// Auto-run if URL contains ?autotest
	if (
		window.location.search.includes( 'autotest' ) ||
		window.location.search.includes( 'test-conditional' )
	) {
		if ( document.readyState === 'loading' ) {
			document.addEventListener( 'DOMContentLoaded', () => {
				setTimeout( () => EIPSIConditionalTests.runAll(), 1000 );
			} );
		} else {
			setTimeout( () => EIPSIConditionalTests.runAll(), 1000 );
		}
	}

	// Expose to global scope
	window.EIPSIConditionalTests = EIPSIConditionalTests;

	console.log( '✅ Test suite loaded successfully!' );
	console.log( '📝 Run: EIPSIConditionalTests.runAll()' );
	console.log(
		'📝 Or run specific test: EIPSIConditionalTests.run("navigator")'
	);
	console.log(
		'📝 Available tests: navigator, serialization, page, buttons, validation, history, navigation'
	);
} )();
