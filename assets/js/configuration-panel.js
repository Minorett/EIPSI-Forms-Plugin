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
            $( '#eipsi-check-table-status' ).on(
                'click',
                this.checkTableStatus.bind( this )
            );
            $( '#eipsi-delete-all-data' ).on(
                'click',
                this.deleteAllData.bind( this )
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

        checkTableStatus( e ) {
            e.preventDefault();

            const $button = $( '#eipsi-check-table-status' );
            const $resultsContainer = $( '#eipsi-table-status-results' );

            // Show loading state
            $button.prop( 'disabled', true ).addClass( 'eipsi-loading' );
            $resultsContainer.hide();

            // Make AJAX request
            $.ajax( {
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'eipsi_check_table_status',
                    nonce: $( '#eipsi_db_config_nonce' ).val(),
                },
                success( response ) {
                    if ( response.success ) {
                        EIPSIConfig.displayTableStatus( response.data );
                    } else {
                        $resultsContainer
                            .html(
                                '<div class="eipsi-table-status-error">' +
                                    '<span class="dashicons dashicons-warning" style="color: #d32f2f;"></span>' +
                                    '<span>' +
                                    ( response.data.message ||
                                        'Failed to check table status' ) +
                                    '</span>' +
                                    '</div>'
                            )
                            .show();
                    }
                },
                error() {
                    $resultsContainer
                        .html(
                            '<div class="eipsi-table-status-error">' +
                                '<span class="dashicons dashicons-warning" style="color: #d32f2f;"></span>' +
                                '<span>Failed to check table status. Please try again.</span>' +
                                '</div>'
                        )
                        .show();
                },
                complete() {
                    $button
                        .prop( 'disabled', false )
                        .removeClass( 'eipsi-loading' );
                },
            } );
        },

        displayTableStatus( data ) {
            const $resultsContainer = $( '#eipsi-table-status-results' );
            let html = '';

            // Overall status indicator
            if ( data.all_tables_exist && data.all_columns_ok ) {
                html +=
                    '<div class="eipsi-table-status-success">' +
                    '<span class="dashicons dashicons-yes-alt" style="color: #198754;"></span>' +
                    '<strong>' +
                    data.message +
                    '</strong>' +
                    '</div>';
            } else {
                html +=
                    '<div class="eipsi-table-status-warning">' +
                    '<span class="dashicons dashicons-warning" style="color: #b35900;"></span>' +
                    '<strong>' +
                    data.message +
                    '</strong>' +
                    '</div>';
            }

            // Results table details
            if ( data.results_table ) {
                html +=
                    '<div class="eipsi-table-detail">' +
                    '<h4>' +
                    '<span class="dashicons dashicons-database"></span>' +
                    ' Results Table: ' +
                    data.results_table.table_name +
                    '</h4>';

                if ( data.results_table.exists ) {
                    html +=
                        '<div class="eipsi-table-exists">' +
                        '<span class="dashicons dashicons-yes-alt" style="color: #198754;"></span>' +
                        ' Table exists' +
                        '</div>';
                    html +=
                        '<div class="eipsi-table-info">' +
                        '<span class="detail-label">Records:</span> ' +
                        '<span class="detail-value">' +
                        data.results_table.row_count.toLocaleString() +
                        '</span>' +
                        '</div>';

                    if ( data.results_table.columns_ok ) {
                        html +=
                            '<div class="eipsi-table-info">' +
                            '<span class="detail-label">Schema:</span> ' +
                            '<span class="detail-value" style="color: #198754;">✓ All columns present</span>' +
                            '</div>';
                    } else {
                        html +=
                            '<div class="eipsi-table-warning">' +
                            '<span class="dashicons dashicons-warning" style="color: #b35900;"></span>' +
                            ' Missing columns: ' +
                            data.results_table.missing_columns.join( ', ' ) +
                            '</div>';
                    }
                } else {
                    html +=
                        '<div class="eipsi-table-missing">' +
                        '<span class="dashicons dashicons-dismiss" style="color: #d32f2f;"></span>' +
                        ' Table does not exist' +
                        '</div>';
                }

                html += '</div>';
            }

            // Events table details
            if ( data.events_table ) {
                html +=
                    '<div class="eipsi-table-detail">' +
                    '<h4>' +
                    '<span class="dashicons dashicons-database"></span>' +
                    ' Events Table: ' +
                    data.events_table.table_name +
                    '</h4>';

                if ( data.events_table.exists ) {
                    html +=
                        '<div class="eipsi-table-exists">' +
                        '<span class="dashicons dashicons-yes-alt" style="color: #198754;"></span>' +
                        ' Table exists' +
                        '</div>';
                    html +=
                        '<div class="eipsi-table-info">' +
                        '<span class="detail-label">Records:</span> ' +
                        '<span class="detail-value">' +
                        data.events_table.row_count.toLocaleString() +
                        '</span>' +
                        '</div>';

                    if ( data.events_table.columns_ok ) {
                        html +=
                            '<div class="eipsi-table-info">' +
                            '<span class="detail-label">Schema:</span> ' +
                            '<span class="detail-value" style="color: #198754;">✓ All columns present</span>' +
                            '</div>';
                    } else {
                        html +=
                            '<div class="eipsi-table-warning">' +
                            '<span class="dashicons dashicons-warning" style="color: #b35900;"></span>' +
                            ' Missing columns: ' +
                            data.events_table.missing_columns.join( ', ' ) +
                            '</div>';
                    }
                } else {
                    html +=
                        '<div class="eipsi-table-missing">' +
                        '<span class="dashicons dashicons-dismiss" style="color: #d32f2f;"></span>' +
                        ' Table does not exist' +
                        '</div>';
                }

                html += '</div>';
            }

            // Show guidance if tables are missing
            if ( ! data.all_tables_exist || ! data.all_columns_ok ) {
                html +=
                    '<div class="eipsi-table-guidance">' +
                    '<h4>' +
                    '<span class="dashicons dashicons-info"></span>' +
                    ' What to do next' +
                    '</h4>' +
                    '<p>The plugin should automatically create required tables when you save the database configuration or submit a form.</p>' +
                    '<p><strong>To manually create or repair tables:</strong></p>' +
                    '<ol>' +
                    '<li>Click the <strong>"Verify & Repair Schema"</strong> button above</li>' +
                    '<li>This will create missing tables and add any missing columns</li>' +
                    '<li>Then click <strong>"Check Table Status"</strong> again to verify</li>' +
                    '</ol>' +
                    '<p style="margin-top: 10px;"><strong>Why this might happen:</strong></p>' +
                    '<ul>' +
                    '<li>First time connecting to this database</li>' +
                    '<li>Database user lacks CREATE TABLE permissions</li>' +
                    '<li>Manual database migration without schema sync</li>' +
                    '</ul>' +
                    '</div>';
            }

            $resultsContainer.html( html ).show();
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

        deleteAllData( e ) {
            e.preventDefault();

            // Custom styled confirmation dialog
            const confirmationHTML = `
                <div id="eipsi-delete-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 999999;">
                    <div style="position: relative; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 8px; max-width: 500px; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
                        <h2 style="color: #d32f2f; margin-top: 0; font-size: 20px;">
                            <span class="dashicons dashicons-warning" style="font-size: 24px; vertical-align: middle;"></span>
                            ${ eipsiConfigL10n.confirmDeleteTitle }
                        </h2>
                        <p style="white-space: pre-line; line-height: 1.6; font-size: 15px; color: #333;">
                            ${ eipsiConfigL10n.confirmDeleteMessage }
                        </p>
                        <div style="margin-top: 25px; text-align: right;">
                            <button id="eipsi-delete-cancel" class="button" style="margin-right: 10px;">
                                ${ eipsiConfigL10n.confirmDeleteNo }
                            </button>
                            <button id="eipsi-delete-confirm" class="button" style="background: #d32f2f; color: white; border-color: #b71c1c; padding: 6px 20px; font-weight: 600;">
                                ${ eipsiConfigL10n.confirmDeleteYes }
                            </button>
                        </div>
                    </div>
                </div>
            `;

            // Add modal to page
            $( 'body' ).append( confirmationHTML );
            $( '#eipsi-delete-modal' ).fadeIn( 200 );

            // Cancel button
            $( '#eipsi-delete-cancel' ).on( 'click', function () {
                $( '#eipsi-delete-modal' ).fadeOut( 200, function () {
                    $( this ).remove();
                } );
            } );

            // Confirm button
            $( '#eipsi-delete-confirm' ).on( 'click', function () {
                $( '#eipsi-delete-modal' ).fadeOut( 200, function () {
                    $( this ).remove();
                } );
                EIPSIConfig.executeDeleteAllData();
            } );

            // Close on background click
            $( '#eipsi-delete-modal' ).on( 'click', function ( e ) {
                if ( e.target === this ) {
                    $( this ).fadeOut( 200, function () {
                        $( this ).remove();
                    } );
                }
            } );
        },

        executeDeleteAllData() {
            const $button = $( '#eipsi-delete-all-data' );

            // Show loading state
            $button.prop( 'disabled', true ).addClass( 'eipsi-loading' );
            this.hideMessage();

            // Make AJAX request
            $.ajax( {
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'eipsi_delete_all_data',
                    nonce: $( '#eipsi-delete-data-nonce' ).val(),
                },
                success( response ) {
                    if ( response.success ) {
                        EIPSIConfig.showMessage(
                            'success',
                            response.data.message
                        );

                        // Reload page after 2 seconds to refresh all stats
                        setTimeout( function () {
                            window.location.reload();
                        }, 2000 );
                    } else {
                        EIPSIConfig.showMessage(
                            'error',
                            response.data.message ||
                                eipsiConfigL10n.deleteError
                        );
                    }
                },
                error() {
                    EIPSIConfig.showMessage(
                        'error',
                        eipsiConfigL10n.deleteError
                    );
                },
                complete() {
                    $button
                        .prop( 'disabled', false )
                        .removeClass( 'eipsi-loading' );
                },
            } );
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
