/**
 * EIPSI Forms - Database Configuration Panel JavaScript
 * Handles connection testing, saving, and status updates
 */

(function($) {
	'use strict';

	// Configuration object
	const EIPSIConfig = {
		connectionTested: false,

		init: function() {
			this.bindEvents();
		},

		bindEvents: function() {
			$('#eipsi-test-connection').on('click', this.testConnection.bind(this));
			$('#eipsi-db-config-form').on('submit', this.saveConfiguration.bind(this));
			$('#eipsi-disable-external-db').on('click', this.disableExternalDB.bind(this));

			// Enable save button after successful test
			$('#eipsi-db-config-form input').on('input', function() {
				EIPSIConfig.connectionTested = false;
				$('#eipsi-save-config').prop('disabled', true);
			});
		},

		testConnection: function(e) {
			e.preventDefault();

			const $button = $('#eipsi-test-connection');
			const $form = $('#eipsi-db-config-form');
			const $statusBox = $('#eipsi-status-box');

			// Get form values
			const data = {
				action: 'eipsi_test_db_connection',
				nonce: $('#eipsi_db_config_nonce').val(),
				host: $('#db_host').val(),
				user: $('#db_user').val(),
				password: $('#db_password').val(),
				db_name: $('#db_name').val()
			};

			// Validate fields
			if (!data.host || !data.user || !data.db_name) {
				this.showMessage('error', eipsiConfigL10n.fillAllFields);
				return;
			}

			// Show loading state
			$button.prop('disabled', true).addClass('eipsi-loading');
			this.hideMessage();

			// Make AJAX request
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: data,
				success: function(response) {
					if (response.success) {
						EIPSIConfig.showMessage('success', response.data.message);
						EIPSIConfig.updateStatusBox(true, response.data);
						EIPSIConfig.connectionTested = true;
						$('#eipsi-save-config').prop('disabled', false);
					} else {
						EIPSIConfig.showMessage('error', response.data.message);
						EIPSIConfig.updateStatusBox(false, {});
					}
				},
				error: function(xhr, status, error) {
					EIPSIConfig.showMessage('error', eipsiConfigL10n.connectionError);
					EIPSIConfig.updateStatusBox(false, {});
				},
				complete: function() {
					$button.prop('disabled', false).removeClass('eipsi-loading');
				}
			});
		},

		saveConfiguration: function(e) {
			e.preventDefault();

			if (!this.connectionTested) {
				this.showMessage('warning', eipsiConfigL10n.testFirst);
				return;
			}

			const $button = $('#eipsi-save-config');
			const data = {
				action: 'eipsi_save_db_config',
				nonce: $('#eipsi_db_config_nonce').val(),
				host: $('#db_host').val(),
				user: $('#db_user').val(),
				password: $('#db_password').val(),
				db_name: $('#db_name').val()
			};

			// Show loading state
			$button.prop('disabled', true).addClass('eipsi-loading');
			this.hideMessage();

			// Make AJAX request
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: data,
				success: function(response) {
					if (response.success) {
						EIPSIConfig.showMessage('success', response.data.message);
						EIPSIConfig.updateStatusBox(true, response.data.status);
						
						// Clear password field after successful save
						$('#db_password').val('');
						
						// Show disable button if not already visible
						if ($('#eipsi-disable-external-db').length === 0) {
							$('.eipsi-form-actions').append(
								'<button type="button" id="eipsi-disable-external-db" class="button button-link-delete">' +
								eipsiConfigL10n.disableExternal +
								'</button>'
							);
							$('#eipsi-disable-external-db').on('click', EIPSIConfig.disableExternalDB.bind(EIPSIConfig));
						}
					} else {
						EIPSIConfig.showMessage('error', response.data.message);
					}
				},
				error: function(xhr, status, error) {
					EIPSIConfig.showMessage('error', eipsiConfigL10n.saveError);
				},
				complete: function() {
					$button.prop('disabled', false).removeClass('eipsi-loading');
				}
			});
		},

		disableExternalDB: function(e) {
			e.preventDefault();

			if (!confirm(eipsiConfigL10n.confirmDisable)) {
				return;
			}

			const $button = $(e.currentTarget);

			// Show loading state
			$button.prop('disabled', true);

			// Make AJAX request
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'eipsi_disable_external_db',
					nonce: $('#eipsi_db_config_nonce').val()
				},
				success: function(response) {
					if (response.success) {
						EIPSIConfig.showMessage('success', response.data.message);
						EIPSIConfig.updateStatusBox(false, {});
						$button.remove();
						EIPSIConfig.connectionTested = false;
						$('#eipsi-save-config').prop('disabled', true);
					} else {
						EIPSIConfig.showMessage('error', response.data.message);
					}
				},
				error: function(xhr, status, error) {
					EIPSIConfig.showMessage('error', eipsiConfigL10n.disableError);
				},
				complete: function() {
					$button.prop('disabled', false);
				}
			});
		},

		showMessage: function(type, message) {
			const $container = $('#eipsi-message-container');
			$container
				.removeClass('success error warning')
				.addClass(type)
				.text(message)
				.show();

			// Auto-hide success messages after 5 seconds
			if (type === 'success') {
				setTimeout(function() {
					$container.fadeOut();
				}, 5000);
			}
		},

		hideMessage: function() {
			$('#eipsi-message-container').hide();
		},

		updateStatusBox: function(connected, data) {
			const $statusBox = $('#eipsi-status-box');

			if (connected) {
				$statusBox.html(
					'<div class="eipsi-status-indicator">' +
						'<span class="status-icon status-connected"></span>' +
						'<span class="status-text">' + eipsiConfigL10n.connected + '</span>' +
					'</div>' +
					'<div class="eipsi-status-details">' +
						'<div class="status-detail-row">' +
							'<span class="detail-label">' + eipsiConfigL10n.currentDatabase + '</span>' +
							'<span class="detail-value">' + (data.db_name || '') + '</span>' +
						'</div>' +
						'<div class="status-detail-row">' +
							'<span class="detail-label">' + eipsiConfigL10n.records + '</span>' +
							'<span class="detail-value">' + (data.record_count || 0) + '</span>' +
						'</div>' +
					'</div>'
				);
			} else {
				$statusBox.html(
					'<div class="eipsi-status-indicator">' +
						'<span class="status-icon status-disconnected"></span>' +
						'<span class="status-text">' + eipsiConfigL10n.disconnected + '</span>' +
					'</div>' +
					'<div class="eipsi-status-message">' +
						'<p>' + eipsiConfigL10n.noExternalDB + '</p>' +
					'</div>'
				);
			}
		}
	};

	// Initialize on document ready
	$(document).ready(function() {
		if ($('#eipsi-db-config-form').length) {
			EIPSIConfig.init();
		}
	});

})(jQuery);
