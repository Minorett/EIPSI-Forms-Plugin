( function ( $ ) {
    'use strict';

    const state = {
        charts: {},
    };

    const selectors = {
        poolSelector: '#eipsi-pool-selector',
        exportButton: '#eipsi-export-pool-csv',
        breakdownBody: '#eipsi-pool-breakdown-body',
        activityFeed: '#eipsi-pool-activity-feed',
        poolName: '#eipsi-pool-name',
        poolDescription: '#eipsi-pool-description',
        poolStatus: '#eipsi-pool-status',
        poolMethod: '#eipsi-pool-method',
        poolCreated: '#eipsi-pool-created',
        poolTotal: '#eipsi-pool-total',
        breakdownHelper: '#eipsi-pool-breakdown-helper',
    };

    const strings = ( window.eipsiPoolDashboard && window.eipsiPoolDashboard.strings ) || {};

    // Copy button strings
    const copyStrings = {
        copy: __( 'Copiar', 'eipsi-forms' ),
        copied: __( '¡Copiado!', 'eipsi-forms' ),
        copyError: __( 'Error al copiar', 'eipsi-forms' ),
    };

    $( document ).ready( function () {
        bindEvents();
        bindCopyEvents();

        if ( window.eipsiPoolDashboard && window.eipsiPoolDashboard.poolId ) {
            loadPoolAnalytics( window.eipsiPoolDashboard.poolId );
        }
    } );

    function __( text, domain ) {
        if ( typeof wp !== 'undefined' && wp.i18n ) {
            return wp.i18n.__( text, domain );
        }
        return text;
    }

    function bindCopyEvents() {
        $( document ).on( 'click', '.eipsi-copy-btn', function ( e ) {
            e.preventDefault();
            const button = $( this );
            const shortcode = button.data( 'shortcode' );

            if ( ! shortcode ) {
                return;
            }

            copyToClipboard( shortcode, button );
        } );
    }

    function copyToClipboard( text, button ) {
        // Try modern Clipboard API first
        if ( navigator.clipboard && navigator.clipboard.writeText ) {
            navigator.clipboard.writeText( text )
                .then( function () {
                    showCopySuccess( button );
                } )
                .catch( function () {
                    fallbackCopy( text, button );
                } );
        } else {
            // Fallback for older browsers
            fallbackCopy( text, button );
        }
    }

    function fallbackCopy( text, button ) {
        const textArea = document.createElement( 'textarea' );
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild( textArea );
        textArea.focus();
        textArea.select();

        try {
            const successful = document.execCommand( 'copy' );
            if ( successful ) {
                showCopySuccess( button );
            } else {
                showCopyError( button );
            }
        } catch ( err ) {
            showCopyError( button );
        }

        document.body.removeChild( textArea );
    }

    function showCopySuccess( button ) {
        const originalIcon = button.find( '.eipsi-copy-icon' ).html();
        const originalText = button.find( '.eipsi-copy-text' ).html();

        button.addClass( 'copied' );
        button.find( '.eipsi-copy-icon' ).html( '✓' );
        button.find( '.eipsi-copy-text' ).html( copyStrings.copied );

        setTimeout( function () {
            button.removeClass( 'copied' );
            button.find( '.eipsi-copy-icon' ).html( originalIcon );
            button.find( '.eipsi-copy-text' ).html( originalText );
        }, 2000 );
    }

    function showCopyError( button ) {
        const originalText = button.find( '.eipsi-copy-text' ).html();
        button.find( '.eipsi-copy-text' ).html( copyStrings.copyError );
        button.css( 'background', '#dc2626' );

        setTimeout( function () {
            button.find( '.eipsi-copy-text' ).html( originalText );
            button.css( 'background', '' );
        }, 2000 );
    }

    function bindEvents() {
        $( document ).on( 'change', selectors.poolSelector, function () {
            const poolId = parseInt( $( this ).val(), 10 );
            if ( poolId ) {
                window.location = `${window.location.pathname}?page=eipsi-pool-dashboard&pool_id=${poolId}`;
            }
        } );

        $( document ).on( 'click', selectors.exportButton, function ( event ) {
            event.preventDefault();
            if ( ! window.eipsiPoolDashboard || ! window.eipsiPoolDashboard.poolId ) {
                return;
            }
            exportPoolCSV( window.eipsiPoolDashboard.poolId );
        } );
    }

    function loadPoolAnalytics( poolId ) {
        updateLoadingState();

        $.post( window.eipsiPoolDashboard.ajaxUrl, {
            action: 'eipsi_get_pool_analytics',
            nonce: window.eipsiPoolDashboard.nonce,
            pool_id: poolId,
        } )
            .done( function ( response ) {
                if ( response && response.success ) {
                    renderPoolDashboard( response.data );
                } else {
                    renderEmptyState();
                }
            } )
            .fail( function () {
                renderEmptyState();
            } );
    }

    function updateLoadingState() {
        $( selectors.breakdownBody ).html(
            `<tr><td colspan="7" class="eipsi-placeholder">${
                strings.loading || 'Cargando...'
            }</td></tr>`
        );
        $( selectors.activityFeed ).html(
            `<li class="eipsi-placeholder">${strings.loading || 'Cargando...'}</li>`
        );
    }

    function renderPoolDashboard( data ) {
        if ( ! data || ! data.pool_info ) {
            renderEmptyState();
            return;
        }

        renderHeader( data.pool_info, data.total_assignments );
        renderBreakdown( data.studies_breakdown || [] );
        renderCharts( data.pool_info, data.studies_breakdown || [], data.total_assignments || 0 );
        renderActivityFeed( data.recent_activity || [] );
    }

    function renderHeader( poolInfo, totalAssignments ) {
        const status = poolInfo.status === 'inactive' ? 'inactive' : 'active';
        const statusLabel = status === 'inactive' ? 'Inactivo' : 'Activo';
        const methodLabel =
            poolInfo.method === 'pure-random'
                ? 'Pure-random'
                : 'Seeded';

        $( selectors.poolName ).text( poolInfo.pool_name || 'Pool' );
        $( selectors.poolDescription ).text( poolInfo.pool_description || '' );
        $( selectors.poolStatus )
            .removeClass( 'status-active status-inactive' )
            .addClass( `status-${status}` )
            .text( statusLabel );
        $( selectors.poolMethod ).text( methodLabel );
        $( selectors.poolCreated ).text( formatDate( poolInfo.created_at ) );
        $( selectors.poolTotal ).text( totalAssignments );
    }

    function renderBreakdown( breakdown ) {
        if ( ! breakdown.length ) {
            renderEmptyState();
            return;
        }

        const rows = breakdown
            .map( function ( row ) {
                const rateClass = getCompletionRateClass( row.completion_rate );
                const studyUrl = `admin.php?page=eipsi-longitudinal-study&tab=dashboard-study&study_id=${row.study_id}`;

                return `
                <tr>
                    <td>
                        <a href="${studyUrl}" class="eipsi-study-link">
                            ${escapeHtml( row.study_name )}
                        </a>
                        ${row.study_code ? `<div class="eipsi-study-code">${escapeHtml( row.study_code )}</div>` : ''}
                    </td>
                    <td>${row.assignments}</td>
                    <td>${row.percent_of_pool}%</td>
                    <td>${row.completed}</td>
                    <td>${row.in_progress}</td>
                    <td>${row.dropped}</td>
                    <td><span class="eipsi-rate-pill ${rateClass}">${row.completion_rate}%</span></td>
                </tr>`;
            } )
            .join( '' );

        $( selectors.breakdownBody ).html( rows );
        $( selectors.breakdownHelper ).text( `${breakdown.length} estudios en pool` );
    }

    function renderCharts( poolInfo, breakdown, totalAssignments ) {
        if ( ! breakdown.length ) {
            return;
        }

        const labels = breakdown.map( ( row ) => row.study_name );
        const assignments = breakdown.map( ( row ) => row.assignments );
        const completionRates = breakdown.map( ( row ) => row.completion_rate );
        const expected = labels.map( ( label, index ) => {
            const studyId = breakdown[ index ].study_id;
            const probability = poolInfo.probabilities && poolInfo.probabilities[ studyId ]
                ? parseFloat( poolInfo.probabilities[ studyId ] )
                : 0;
            return totalAssignments ? Math.round( ( probability / 100 ) * totalAssignments ) : 0;
        } );

        renderDistributionChart( labels, assignments, expected );
        renderCompletionChart( labels, completionRates );
    }

    function renderDistributionChart( labels, assignments, expected ) {
        const ctx = document.getElementById( 'eipsi-pool-distribution-chart' );
        if ( ! ctx ) {
            return;
        }

        if ( state.charts.distribution ) {
            state.charts.distribution.destroy();
        }

        state.charts.distribution = new Chart( ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Asignaciones reales',
                        data: assignments,
                        backgroundColor: getPalette( labels.length, 0.85 ),
                        borderWidth: 1,
                    },
                    {
                        label: 'Configuración esperada',
                        data: expected,
                        backgroundColor: getPalette( labels.length, 0.35 ),
                        borderWidth: 1,
                    },
                ],
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                },
                cutout: '45%',
            },
        } );
    }

    function renderCompletionChart( labels, completionRates ) {
        const ctx = document.getElementById( 'eipsi-pool-completion-chart' );
        if ( ! ctx ) {
            return;
        }

        if ( state.charts.completion ) {
            state.charts.completion.destroy();
        }

        state.charts.completion = new Chart( ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Completion rate',
                        data: completionRates,
                        backgroundColor: completionRates.map( ( rate ) => getRateColor( rate ) ),
                        borderRadius: 6,
                    },
                ],
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                scales: {
                    x: {
                        max: 100,
                        ticks: {
                            callback: function ( value ) {
                                return `${value}%`;
                            },
                        },
                    },
                },
                plugins: {
                    legend: {
                        display: false,
                    },
                },
            },
        } );
    }

    function renderActivityFeed( activity ) {
        if ( ! activity.length ) {
            $( selectors.activityFeed ).html(
                `<li class="eipsi-placeholder">${strings.noData || 'Sin actividad reciente.'}</li>`
            );
            return;
        }

        const rows = activity
            .map( function ( item ) {
                const statusClass = `status-${item.status || 'assigned'}`;
                const statusLabel = formatStatusLabel( item.status );
                return `
                <li class="eipsi-activity-item">
                    <div>
                        <strong>${escapeHtml( item.participant_name || '' )}</strong>
                        <span class="eipsi-activity-study">${escapeHtml( item.study_name || '' )}</span>
                        <div class="eipsi-activity-meta">
                            <span class="eipsi-activity-email">${escapeHtml( item.participant_email || '' )}</span>
                            <span class="eipsi-activity-date">${formatDate( item.assigned_at )}</span>
                        </div>
                    </div>
                    <span class="eipsi-status-pill ${statusClass}">${statusLabel}</span>
                </li>`;
            } )
            .join( '' );

        $( selectors.activityFeed ).html( rows );
    }

    function renderEmptyState() {
        $( selectors.breakdownBody ).html(
            `<tr><td colspan="7" class="eipsi-placeholder">${strings.noData || 'Sin datos aún.'}</td></tr>`
        );
        $( selectors.activityFeed ).html(
            `<li class="eipsi-placeholder">${strings.noData || 'Sin actividad reciente.'}</li>`
        );
    }

    function exportPoolCSV( poolId ) {
        const button = $( selectors.exportButton );
        const originalText = button.text();
        button.prop( 'disabled', true ).text( strings.exporting || 'Exportando...' );

        const payload = new URLSearchParams();
        payload.append( 'action', 'eipsi_export_pool_assignments' );
        payload.append( 'nonce', window.eipsiPoolDashboard.nonce );
        payload.append( 'pool_id', poolId );

        fetch( window.eipsiPoolDashboard.ajaxUrl, {
            method: 'POST',
            body: payload,
        } )
            .then( function ( response ) {
                if ( ! response.ok ) {
                    throw new Error( 'Export error' );
                }
                return response.blob();
            } )
            .then( function ( blob ) {
                const url = window.URL.createObjectURL( blob );
                const link = document.createElement( 'a' );
                link.href = url;
                link.download = `pool-${poolId}-assignments.csv`;
                document.body.appendChild( link );
                link.click();
                link.remove();
                window.URL.revokeObjectURL( url );
            } )
            .catch( function () {
                alert( strings.exportError || 'No se pudo exportar el CSV.' );
            } )
            .finally( function () {
                button.prop( 'disabled', false ).text( originalText );
            } );
    }

    function formatDate( value ) {
        if ( ! value ) {
            return '—';
        }
        const date = new Date( value.replace( ' ', 'T' ) );
        if ( Number.isNaN( date.getTime() ) ) {
            return value;
        }
        return date.toLocaleDateString( 'es-AR', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        } );
    }

    function getCompletionRateClass( rate ) {
        if ( rate >= 70 ) {
            return 'rate-high';
        }
        if ( rate >= 50 ) {
            return 'rate-mid';
        }
        return 'rate-low';
    }

    function getRateColor( rate ) {
        if ( rate >= 70 ) {
            return '#16a34a';
        }
        if ( rate >= 50 ) {
            return '#f59e0b';
        }
        return '#ef4444';
    }

    function formatStatusLabel( status ) {
        switch ( status ) {
            case 'completed':
                return 'Completado';
            case 'dropped':
                return 'Dropout';
            default:
                return 'Asignado';
        }
    }

    function getPalette( size, alpha ) {
        const base = [
            '16, 185, 129',
            '59, 130, 246',
            '234, 179, 8',
            '239, 68, 68',
            '139, 92, 246',
            '14, 116, 144',
            '217, 70, 239',
        ];
        return Array.from( { length: size }, ( _, index ) => {
            const color = base[ index % base.length ];
            return `rgba(${color}, ${alpha})`;
        } );
    }

    function escapeHtml( value ) {
        if ( ! value ) {
            return '';
        }
        return String( value )
            .replace( /&/g, '&amp;' )
            .replace( /</g, '&lt;' )
            .replace( />/g, '&gt;' )
            .replace( /"/g, '&quot;' )
            .replace( /'/g, '&#039;' );
    }
} )( jQuery );
