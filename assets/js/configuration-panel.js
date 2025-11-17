/* global jQuery, ajaxurl, eipsiConfigL10n, confirm */
/**
 * EIPSI Forms - Database Configuration Panel JavaScript
 * Handles connection testing, saving, and status updates
 * @param {Object} $ - jQuery object
 */

( function ( $ ) {
	'use strict';

	// Configuration object
	const EIPSIConfig = {
		connectionTested: false,

		init() {
			this.bindEvents();
		},

		bindEvents() {
			$( '#eipsi-test-connection' ).on(
				'click',
				this.testConnection.bind( this )
			);
			$( '#eipsi-db-config-form' ).on(
				'submit',
				this.saveConfiguration.bind( this )
			);
			$( '#eipsi-disable-external-db' ).on(
				'click',
				this.disableExternalDB.bind( this )
			);
			$( '#eipsi-verify-schema' ).on(
				'click',
				this.verifySchema.bind( this )
			);

			// Enable save button after successful test
			$( '#eipsi-db-config-form input' ).on( 'input', function () {
				EIPSIConfig.connectionTested = false;
				$( '#eipsi-save-config' ).prop( 'disabled', true );
			} );
		},

		testConnection( e ) {
			e.preventDefault();

			// Get form values
			const data = {
				action: 'eipsi_test_db_connection',
				nonce: $( '#eipsi_db_config_nonce' ).val(),
				host: $( '#db_host' ).val(),
				user: $( '#db_user' ).val(),
				password: $( '#db_password' ).val(),
				db_name: $( '#db_name' ).val(),
			};

			// Validate fields
			if ( ! data.host || ! data.user || ! data.db_name ) {
				this.showMessage( 'error', eipsiConfigL10n.fillAllFields );
				return;
			}

			const $button = $( '#eipsi-test-connection' );

			// Show loading state
			$button.prop( 'disabled', true ).addClass( 'eipsi-loading' );
			this.hideMessage();

			// Make AJAX request
			$.ajax( {
				url: ajaxurl,
				type: 'POST',
				data,
				success( response ) {
					if ( response.success ) {
						EIPSIConfig.showMessage(
							'success',
							response.data.message
						);
						EIPSIConfig.updateStatusBox( true, response.data );
						EIPSIConfig.connectionTested = true;
						$( '#eipsi-save-config' ).prop( 'disabled', false );
					} else {
						EIPSIConfig.showMessage(
							'error',
							response.data.message
						);
						EIPSIConfig.updateStatusBox( false, {} );
					}
				},
				error() {
					EIPSIConfig.showMessage(
						'error',
						eipsiConfigL10n.connectionError
					);
					EIPSIConfig.updateStatusBox( false, {} );
				},
				complete() {
					$button
						.prop( 'disabled', false )
						.removeClass( 'eipsi-loading' );
				},
			} );
		},

		saveConfiguration( e ) {
			e.preventDefault();

			if ( ! this.connectionTested ) {
				this.showMessage( 'warning', eipsiConfigL10n.testFirst );
				return;
			}

			const $button = $( '#eipsi-save-config' );
			const data = {
				action: 'eipsi_save_db_config',
				nonce: $( '#eipsi_db_config_nonce' ).val(),
				host: $( '#db_host' ).val(),
				user: $( '#db_user' ).val(),
				password: $( '#db_password' ).val(),
				db_name: $( '#db_name' ).val(),
			};

			// Show loading state
			$button.prop( 'disabled', true ).addClass( 'eipsi-loading' );
			this.hideMessage();

			// Make AJAX request
			$.ajax( {
				url: ajaxurl,
				type: 'POST',
				data,
				success( response ) {
					if ( response.success ) {
						EIPSIConfig.showMessage(
							'success',
							response.data.message
						);
						EIPSIConfig.updateStatusBox(
							true,
							response.data.status
						);

						// Clear password field after successful save
						$( '#db_password' ).val( '' );

						// Show disable button if not already visible
						if ( $( '#eipsi-disable-external-db' ).length === 0 ) {
							$( '.eipsi-form-actions' ).append(
								'<button type="button" id="eipsi-disable-external-db" class="button button-link-delete">' +
									eipsiConfigL10n.disableExternal +
									'</button>'
							);
							$( '#eipsi-disable-external-db' ).on(
								'click',
								EIPSIConfig.disableExternalDB.bind(
									EIPSIConfig
								)
							);
						}
					} else {
						EIPSIConfig.showMessage(
							'error',
							response.data.message
						);
					}
				},
				error() {
					EIPSIConfig.showMessage(
						'error',
						eipsiConfigL10n.saveError
					);
				},
				complete() {
					$button
						.prop( 'disabled', false )
						.removeClass( 'eipsi-loading' );
				},
			} );
		},

		disableExternalDB( e ) {
			e.preventDefault();

			// eslint-disable-next-line no-alert
			if ( ! confirm( eipsiConfigL10n.confirmDisable ) ) {
				return;
			}

			const $button = $( e.currentTarget );

			// Show loading state
			$button.prop( 'disabled', true );

			// Make AJAX request
			$.ajax( {
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'eipsi_disable_external_db',
					nonce: $( '#eipsi_db_config_nonce' ).val(),
				},
				success( response ) {
					if ( response.success ) {
						EIPSIConfig.showMessage(
							'success',
							response.data.message
						);
						EIPSIConfig.updateStatusBox( false, {} );
						$button.remove();
						EIPSIConfig.connectionTested = false;
						$( '#eipsi-save-config' ).prop( 'disabled', true );
					} else {
						EIPSIConfig.showMessage(
							'error',
							response.data.message
						);
					}
				},
				error() {
					EIPSIConfig.showMessage(
						'error',
						eipsiConfigL10n.disableError
					);
				},
				complete() {
					$button.prop( 'disabled', false );
				},
			} );
		},

		verifySchema( e ) {
			e.preventDefault();

			const $button = $( '#eipsi-verify-schema' );

			// Show loading state
			$button.prop( 'disabled', true ).addClass( 'eipsi-loading' );
			this.hideMessage();

			// Make AJAX request
			$.ajax( {
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'eipsi_verify_schema',
					nonce: $( '#eipsi_db_config_nonce' ).val(),
				},
				success( response ) {
					if ( response.success ) {
						const results = response.data.results;
						let message = '✅ Schema verification completed!\n\n';

						if (
							results.results_table.created ||
							results.events_table.created
						) {
							message += 'Tables created:\n';
							if ( results.results_table.created ) {
								message += '  • wp_vas_form_results\n';
							}
							if ( results.events_table.created ) {
								message += '  • wp_vas_form_events\n';
							}
							message += '\n';
						}

						const columnsAdded =
							results.results_table.columns_added.length +
							results.events_table.columns_added.length;
						if ( columnsAdded > 0 ) {
							message += `Columns synchronized: ${ columnsAdded }\n`;
						}

						if (
							! results.results_table.created &&
							! results.events_table.created &&
							columnsAdded === 0
						) {
							message += 'All tables and columns are up to date!';
						}

						EIPSIConfig.showMessage( 'success', message );

						// Reload page to refresh schema status
						setTimeout( function () {
							window.location.reload();
						}, 2000 );
					} else {
						EIPSIConfig.showMessage(
							'error',
							'Schema verification failed: ' +
								response.data.message
						);
					}
				},
				error() {
					EIPSIConfig.showMessage(
						'error',
						'Failed to verify schema'
					);
				},
				complete() {
					$button
						.prop( 'disabled', false )
						.removeClass( 'eipsi-loading' );
				},
			} );
		},

		showMessage( type, message ) {
			const $container = $( '#eipsi-message-container' );
			$container
				.removeClass( 'success error warning' )
				.addClass( type )
				.text( message )
				.show();

			// Auto-hide success messages after 5 seconds
			if ( type === 'success' ) {
				setTimeout( function () {
					$container.fadeOut();
				}, 5000 );
			}
		},

		hideMessage() {
			$( '#eipsi-message-container' ).hide();
		},

		updateStatusBox( connected, data ) {
			const $statusBox = $( '#eipsi-status-box' );

			if ( connected ) {
				$statusBox.html(
					'<div class="eipsi-status-indicator">' +
						'<span class="status-icon status-connected"></span>' +
						'<span class="status-text">' +
						eipsiConfigL10n.connected +
						'</span>' +
						'</div>' +
						'<div class="eipsi-status-details">' +
						'<div class="status-detail-row">' +
						'<span class="detail-label">' +
						eipsiConfigL10n.currentDatabase +
						'</span>' +
						'<span class="detail-value">' +
						( data.db_name || '' ) +
						'</span>' +
						'</div>' +
						'<div class="status-detail-row">' +
						'<span class="detail-label">' +
						eipsiConfigL10n.records +
						'</span>' +
						'<span class="detail-value">' +
						( data.record_count || 0 ) +
						'</span>' +
						'</div>' +
						'</div>'
				);
			} else {
				$statusBox.html(
					'<div class="eipsi-status-indicator">' +
						'<span class="status-icon status-disconnected"></span>' +
						'<span class="status-text">' +
						eipsiConfigL10n.disconnected +
						'</span>' +
						'</div>' +
						'<div class="eipsi-status-message">' +
						'<p>' +
						eipsiConfigL10n.noExternalDB +
						'</p>' +
						'</div>'
				);
			}
		},
	};

	// Initialize on document ready
	$( document ).ready( function () {
		if ( $( '#eipsi-db-config-form' ).length ) {
			EIPSIConfig.init();
		}
	} );
} )( jQuery );
