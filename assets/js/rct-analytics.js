/**
 * EIPSI Forms - RCT Analytics Dashboard JavaScript
 *
 * Maneja toda la funcionalidad interactiva del dashboard RCT
 *
 * @package EIPSI_Forms
 * @since 1.3.2
 */

( function ( $ ) {
    'use strict';

    // Configuración global
    const RCT_ANALYTICS_CONFIG = {
        ajaxUrl: eipsiAdmin?.ajaxUrl || '/wp-admin/admin-ajax.php',
        nonce: eipsiAdmin?.nonce || '',
        refreshInterval: 60000, // 60 segundos
        autoRefresh: true,
        maxRetries: 3,
    };

    // Estado global
    const RCT_STATE = {
        data: [],
        isLoading: false,
        autoRefreshTimer: null,
        retryCount: 0,
    };

    /**
     * Inicializar el dashboard
     */
    function initRCTDashboard() {
        // Verificar que jQuery esté disponible
        if ( typeof $ === 'undefined' ) {
            console.error( 'EIPSI RCT Analytics: jQuery no disponible' );
            return;
        }

        // Configurar eventos
        setupEventHandlers();

        // Cargar datos iniciales
        loadRCTData();

        // Configurar auto-refresh
        if ( RCT_ANALYTICS_CONFIG.autoRefresh ) {
            startAutoRefresh();
        }

        console.log( 'EIPSI RCT Analytics Dashboard inicializado' );
    }

    /**
     * Configurar manejadores de eventos
     */
    function setupEventHandlers() {
        // Botón de actualizar
        $( document ).on( 'click', '#refresh-rct-data', function ( e ) {
            e.preventDefault();
            loadRCTData();
        } );

        // Ver detalles
        $( document ).on( 'click', '.rct-view-details', function ( e ) {
            e.preventDefault();
            const randomizationId = $( this ).data( 'randomization-id' );
            if ( randomizationId ) {
                showRCTDetails( randomizationId );
            }
        } );

        // Copiar ID
        $( document ).on( 'click', '[data-copy-id]', function ( e ) {
            e.preventDefault();
            const id = $( this ).data( 'copy-id' );
            copyToClipboard( id );
        } );

        // Cerrar modal
        $( document ).on(
            'click',
            '.eipsi-modal-close, .eipsi-modal',
            function ( e ) {
                if ( e.target === this ) {
                    hideModal();
                }
            }
        );

        // Tecla ESC para cerrar modal
        $( document ).on( 'keydown', function ( e ) {
            if ( e.key === 'Escape' ) {
                hideModal();
            }
        } );

        // Toggle auto-refresh
        $( document ).on( 'change', '#auto-refresh-toggle', function () {
            const enabled = $( this ).is( ':checked' );
            toggleAutoRefresh( enabled );
        } );
    }

    /**
     * Cargar datos de aleatorizaciones
     */
    function loadRCTData() {
        if ( RCT_STATE.isLoading ) {
            return;
        }

        RCT_STATE.isLoading = true;
        showLoading();

        $.ajax( {
            url: RCT_ANALYTICS_CONFIG.ajaxUrl,
            type: 'POST',
            data: {
                action: 'eipsi_get_randomizations',
                nonce: RCT_ANALYTICS_CONFIG.nonce,
            },
            timeout: 15000,
            success: function ( response ) {
                RCT_STATE.retryCount = 0; // Reset retry count on success

                if ( response && response.success ) {
                    RCT_STATE.data = response.data.randomizations || [];
                    renderRCTDashboard( RCT_STATE.data );
                    updateLastUpdatedTime();
                    showSuccess( 'Datos actualizados correctamente' );
                } else {
                    throw new Error(
                        response?.data || 'Respuesta inválida del servidor'
                    );
                }
            },
            error: function ( xhr, status, error ) {
                console.error( 'EIPSI RCT Analytics: Error al cargar datos', {
                    xhr,
                    status,
                    error,
                } );

                let errorMessage = 'Error de conexión';

                if ( status === 'timeout' ) {
                    errorMessage = 'Tiempo de espera agotado';
                } else if ( xhr.status === 403 ) {
                    errorMessage = 'Sin permisos para acceder';
                } else if ( xhr.status === 500 ) {
                    errorMessage = 'Error interno del servidor';
                }

                showError( errorMessage );

                // Retry logic
                if ( RCT_STATE.retryCount < RCT_ANALYTICS_CONFIG.maxRetries ) {
                    RCT_STATE.retryCount++;
                    console.log(
                        `Reintentando... (${ RCT_STATE.retryCount }/${ RCT_ANALYTICS_CONFIG.maxRetries })`
                    );
                    setTimeout( loadRCTData, 2000 * RCT_STATE.retryCount );
                }
            },
            complete: function () {
                RCT_STATE.isLoading = false;
                hideLoading();
            },
        } );
    }

    /**
     * Renderizar el dashboard principal
     */
    function renderRCTDashboard( data ) {
        const container = $( '#rct-dashboard' );

        if ( ! data || data.length === 0 ) {
            container.html( getEmptyState() );
            return;
        }

        let html = '';
        data.forEach( function ( rct ) {
            html += renderRCtCard( rct );
        } );

        container.html( html );
    }

    /**
     * Renderizar una card de RCT individual
     */
    function renderRCtCard( rct ) {
        const statusClass = rct.is_active ? 'status-active' : 'status-inactive';
        const statusText = rct.is_active ? '🟢 Activa' : '🔴 Inactiva';

        let distributionHtml = '';
        if ( rct.distribution && rct.distribution.length > 0 ) {
            rct.distribution.forEach( function ( dist ) {
                const percentage =
                    dist.count > 0
                        ? Math.round(
                                ( dist.count / rct.total_assigned ) * 100
                          )
                        : 0;
                distributionHtml += `
                    <div class="distribution-item">
                        <div class="distribution-label">${ escapeHtml(
                            dist.form_title
                        ) }</div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${ percentage }%"></div>
                        </div>
                        <div class="distribution-value">${
                            dist.count
                        } (${ percentage }%)</div>
                    </div>
                `;
            } );
        }

        const completionRate =
            rct.total_assigned > 0
                ? Math.round(
                        ( rct.completed_count / rct.total_assigned ) * 100
                  )
                : 0;

        return `
            <div class="rct-card" data-rct-id="${ escapeHtml(
                rct.randomization_id
            ) }">
                <div class="rct-card-header">
                    <div>
                        <h3 class="rct-card-title">${ escapeHtml(
                            rct.randomization_id
                        ) }</h3>
                        <div class="rct-card-meta">
                            <span>📅 ${ escapeHtml(
                                rct.created_formatted
                            ) }</span>
                            <span>🎯 ${ escapeHtml(
                                rct.method.toUpperCase()
                            ) }</span>
                            <span class="status-badge ${ statusClass }">${ statusText }</span>
                        </div>
                    </div>
                </div>

                <div class="distribution-section">
                    <div class="distribution-title">Distribución de Asignaciones</div>
                    ${
                        distributionHtml ||
                        '<p style="color: #64748b; font-style: italic;">Sin asignaciones aún</p>'
                    }
                    <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                        <strong>Total Asignados: ${
                            rct.total_assigned
                        }</strong>
                        <span style="color: #64748b;">Última asignación: ${ escapeHtml(
                            rct.last_assignment_formatted
                        ) }</span>
                    </div>
                </div>

                <div class="metrics-grid">
                    <div class="metric-card">
                        <div class="metric-value">${ rct.completed_count }</div>
                        <div class="metric-label">Completados</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value">${ completionRate }%</div>
                        <div class="metric-label">Tasa Completado</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value">${
                            rct.avg_access_count || 0
                        }</div>
                        <div class="metric-label">Promedio Accesos</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value">${ rct.avg_days || 0 }</div>
                        <div class="metric-label">Promedio Días</div>
                    </div>
                </div>

                <div class="rct-actions-buttons">
                    <button type="button" class="rct-button rct-button-primary rct-view-details" data-randomization-id="${ escapeHtml(
                        rct.randomization_id
                    ) }">
                        👁️ Ver Detalles
                    </button>
                    <button type="button" class="rct-button rct-button-analysis" onclick="showDistributionAnalysis('${ escapeHtml(
                        rct.randomization_id
                    ) }')">
                        📊 Análisis Distribución
                    </button>
                    <button type="button" class="rct-button rct-button-download" onclick="downloadAssignmentsCSV('${ escapeHtml(
                        rct.randomization_id
                    ) }')">
                        📥 Descargar CSV
                    </button>
                    <button type="button" class="rct-button" data-copy-id="${ escapeHtml(
                        rct.randomization_id
                    ) }">
                        📋 Copiar ID
                    </button>
                </div>
            </div>
        `;
    }

    /**
     * Mostrar detalles de una aleatorización
     */
    function showRCTDetails( randomizationId ) {
        const modal = $( '#rct-details-modal' );
        const modalBody = $( '#modal-body' );
        const modalTitle = $( '#modal-title' );

        // Mostrar loading
        modalBody.html( `
            <div class="loading-indicator">
                <div class="spinner"></div>
                <p>Cargando detalles...</p>
            </div>
        ` );

        modalTitle.text( `Detalles: ${ randomizationId }` );
        showModal();

        $.ajax( {
            url: RCT_ANALYTICS_CONFIG.ajaxUrl,
            type: 'POST',
            data: {
                action: 'eipsi_get_randomization_details',
                randomization_id: randomizationId,
                nonce: RCT_ANALYTICS_CONFIG.nonce,
            },
            timeout: 15000,
            success: function ( response ) {
                if ( response && response.success ) {
                    modalBody.html( renderDetailsView( response.data ) );
                } else {
                    throw new Error(
                        response?.data || 'Error al cargar detalles'
                    );
                }
            },
            error: function ( xhr, status, error ) {
                console.error( 'Error al cargar detalles', {
                    xhr,
                    status,
                    error,
                } );

                let errorMessage = 'Error al cargar detalles';
                if ( xhr.status === 404 ) {
                    errorMessage = 'Aleatorización no encontrada';
                }

                modalBody.html(
                    `<p style="color: #dc2626;">${ escapeHtml(
                        errorMessage
                    ) }</p>`
                );
            },
        } );
    }

    /**
     * Renderizar vista de detalles
     */
    function renderDetailsView( data ) {
        const completionRate = data.completion_rate || 0;
        const dropoutRate = data.dropout_rate || 0;

        // Distribución detallada
        let distributionHtml = '';
        if ( data.distribution && data.distribution.length > 0 ) {
            distributionHtml = data.distribution
                .map(
                    ( dist ) => `
                <div class="detail-item">
                    <div class="detail-label">${ escapeHtml(
                        dist.form_title
                    ) }</div>
                    <div class="detail-value">
                        Asignados: ${ dist.total_assigned } | 
                        Completados: ${ dist.completed_count } (${
                            dist.completion_rate
                        }%) | 
                        Dropout: ${ dist.dropout_count }
                    </div>
                </div>
            `
                )
                .join( '' );
        } else {
            distributionHtml =
                '<p style="color: #64748b; font-style: italic;">Sin asignaciones</p>';
        }

        return `
            <div class="details-section">
                <h4 class="details-title">Información General</h4>
                <div class="details-grid">
                    <div class="detail-item">
                        <div class="detail-label">ID de Aleatorización</div>
                        <div class="detail-value">${ escapeHtml(
                            data.randomization_id
                        ) }</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Fecha de Creación</div>
                        <div class="detail-value">${ escapeHtml(
                            data.created_formatted
                        ) }</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Método</div>
                        <div class="detail-value">${ escapeHtml(
                            data.method.toUpperCase()
                        ) }</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Estado</div>
                        <div class="detail-value">
                            <span class="status-badge ${
                                data.is_active
                                    ? 'status-active'
                                    : 'status-inactive'
                            }">
                                ${
                                    data.is_active ? '🟢 Activa' : '🔴 Inactiva'
                                }
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="details-section">
                <h4 class="details-title">Estadísticas Resumidas</h4>
                <div class="details-grid">
                    <div class="detail-item">
                        <div class="detail-label">Total Asignados</div>
                        <div class="detail-value">${ data.total_assigned }</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Completados</div>
                        <div class="detail-value">${
                            data.completed_count
                        }</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Tasa Completado</div>
                        <div class="detail-value">${ completionRate }%</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Tasa Dropout</div>
                        <div class="detail-value">${ dropoutRate }%</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Promedio Accesos</div>
                        <div class="detail-value">${
                            data.avg_access_count || 0
                        }</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Promedio Días</div>
                        <div class="detail-value">${ data.avg_days || 0 }</div>
                    </div>
                </div>
            </div>

            <div class="details-section">
                <h4 class="details-title">Distribución por Formulario</h4>
                ${ distributionHtml }
            </div>

            <div class="details-section">
                <h4 class="details-title">Timeline</h4>
                <div class="details-grid">
                    <div class="detail-item">
                        <div class="detail-label">Primera Asignación</div>
                        <div class="detail-value">${ escapeHtml(
                            data.first_assignment_formatted || 'N/A'
                        ) }</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Última Asignación</div>
                        <div class="detail-value">${ escapeHtml(
                            data.last_assignment_formatted
                        ) }</div>
                    </div>
                </div>
            </div>

            <div class="details-section">
                <div class="rct-actions-buttons">
                    <button type="button" class="rct-button rct-button-primary" onclick="showRCTUsers('${ escapeHtml(
                        data.randomization_id
                    ) }')">
                        👥 Ver Lista de Usuarios
                    </button>
                    <button type="button" class="rct-button" data-copy-id="${ escapeHtml(
                        data.randomization_id
                    ) }">
                        📋 Copiar ID
                    </button>
                </div>
            </div>
        `;
    }

    /**
     * Descargar CSV de asignaciones
     */
    function downloadAssignmentsCSV( randomizationId, formId = null ) {
        const nonce = eipsiAdmin?.nonce || RCT_ANALYTICS_CONFIG.nonce;

        if ( ! nonce ) {
            showError( 'Error de seguridad: nonce faltante' );
            return;
        }

        // Crear formulario temporal para POST
        const form = document.createElement( 'form' );
        form.method = 'POST';
        form.action = RCT_ANALYTICS_CONFIG.ajaxUrl;
        form.style.display = 'none';

        const fields = {
            action: 'eipsi_download_assignments_csv',
            randomization_id: randomizationId,
            nonce: nonce,
        };

        if ( formId ) {
            fields[ 'form_id' ] = formId;
        }

        Object.keys( fields ).forEach( ( key ) => {
            const input = document.createElement( 'input' );
            input.type = 'hidden';
            input.name = key;
            input.value = fields[ key ];
            form.appendChild( input );
        } );

        document.body.appendChild( form );
        form.submit();

        // Limpiar después de descarga
        setTimeout( () => {
            document.body.removeChild( form );
        }, 100 );

        showSuccess( 'Descargando CSV...' );
    }

    // Exponer función globalmente para uso desde HTML onclick
    window.downloadAssignmentsCSV = downloadAssignmentsCSV;

    /**
     * Auto-refresh
     */
    function startAutoRefresh() {
        if ( RCT_STATE.autoRefreshTimer ) {
            clearInterval( RCT_STATE.autoRefreshTimer );
        }

        RCT_STATE.autoRefreshTimer = setInterval( function () {
            if ( ! RCT_STATE.isLoading ) {
                loadRCTData();
            }
        }, RCT_ANALYTICS_CONFIG.refreshInterval );
    }

    function stopAutoRefresh() {
        if ( RCT_STATE.autoRefreshTimer ) {
            clearInterval( RCT_STATE.autoRefreshTimer );
            RCT_STATE.autoRefreshTimer = null;
        }
    }

    function toggleAutoRefresh( enabled ) {
        if ( enabled ) {
            startAutoRefresh();
            showSuccess( 'Auto-refresh activado' );
        } else {
            stopAutoRefresh();
            showSuccess( 'Auto-refresh desactivado' );
        }
    }

    /**
     * Funciones de utilidad UI
     */
    function showLoading() {
        $( '#rct-dashboard .loading-indicator' ).show();
    }

    function hideLoading() {
        $( '#rct-dashboard .loading-indicator' ).hide();
    }

    function showModal() {
        $( '#rct-details-modal' ).addClass( 'show' ).show();
    }

    function hideModal() {
        $( '#rct-details-modal' ).removeClass( 'show' ).hide();
    }

    function updateLastUpdatedTime() {
        const now = new Date();
        const timeString = now.toLocaleTimeString( 'es-ES', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
        } );
        $( '#last-updated-time' ).text( timeString );
    }

    function getEmptyState() {
        return `
            <div class="empty-state">
                <div class="empty-state-icon">🎲</div>
                <h3 class="empty-state-title">No hay aleatorizaciones aún</h3>
                <p class="empty-state-description">
                    Cuando crees estudios RCT, aparecerán aquí para monitoreo en tiempo real.
                </p>
                <button type="button" class="rct-button rct-button-primary" onclick="window.open('${
                    eipsiAdmin?.adminUrl || '/wp-admin/'
                }post-new.php?post_type=page', '_blank')">
                    ➕ Crear Aleatorización
                </button>
            </div>
        `;
    }

    function escapeHtml( text ) {
        if ( typeof text !== 'string' ) return text;
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;',
        };
        return text.replace( /[&<>"']/g, function ( m ) {
            return map[ m ];
        } );
    }

    function copyToClipboard( text ) {
        if ( navigator.clipboard && navigator.clipboard.writeText ) {
            navigator.clipboard
                .writeText( text )
                .then( function () {
                    showSuccess( 'ID copiado al portapapeles' );
                } )
                .catch( function ( err ) {
                    console.error( 'Error al copiar:', err );
                    fallbackCopyToClipboard( text );
                } );
        } else {
            fallbackCopyToClipboard( text );
        }
    }

    function fallbackCopyToClipboard( text ) {
        const textArea = document.createElement( 'textarea' );
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild( textArea );
        textArea.focus();
        textArea.select();

        try {
            document.execCommand( 'copy' );
            showSuccess( 'ID copiado al portapapeles' );
        } catch ( err ) {
            console.error( 'Error con fallback de copia:', err );
            showError( 'No se pudo copiar el ID' );
        }

        document.body.removeChild( textArea );
    }

    function showMessage( message, type ) {
        const className =
            type === 'success'
                ? 'notice-success'
                : type === 'error'
                ? 'notice-error'
                : 'notice-info';

        $( '#rct-message-container' ).html(
            `<div class="notice ${ className }"><p>${ escapeHtml(
                message
            ) }</p></div>`
        );

        setTimeout( function () {
            $( '#rct-message-container' ).empty();
        }, 5000 );
    }

    function showSuccess( message ) {
        showMessage( message, 'success' );
    }

    function showError( message ) {
        showMessage( message, 'error' );
    }

    /**
     * Funciones globales para uso desde HTML
     */
    window.copyRCTId = function ( id ) {
        copyToClipboard( id );
    };

    /**
     * ========================================
     * PILAR 3: DISTRIBUTION STATS - Real vs Theory
     * ========================================
     */

    /**
     * Cargar stats de distribución para configuración específica
     */
    async function loadDistributionStats( randomizationId ) {
        try {
            const response = await fetch( RCT_ANALYTICS_CONFIG.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams( {
                    action: 'eipsi_get_distribution_stats',
                    nonce: RCT_ANALYTICS_CONFIG.nonce,
                    randomization_id: randomizationId,
                } ),
            } );

            const data = await response.json();

            if ( data.success ) {
                renderDistributionWidget( data.data );
                setupDistributionChart( data.data );
                renderHealthScore( data.data.summary.health_score );
            } else {
                throw new Error( data.data || 'Error al cargar estadísticas de distribución' );
            }
        } catch ( error ) {
            console.error( 'Error loading distribution stats:', error );
            showError( 'Error cargando análisis de distribución: ' + error.message );
        }
    }

    /**
     * Renderizar tabla comparativa (Teórico vs Real)
     */
    function renderDistributionWidget( data ) {
        const html = `
        <div class="rct-distribution-widget">
            <div class="distribution-header">
                <h3>📊 Distribución: Teórica vs Real</h3>
                <span class="overall-status ${ data.summary.overall_status }">
                    ${ getStatusEmoji( data.summary.overall_status ) } 
                    ${ data.summary.overall_status.toUpperCase() }
                </span>
            </div>
            
            <table class="distribution-table">
                <thead>
                    <tr>
                        <th>Formulario</th>
                        <th>Teórico %</th>
                        <th>Real (n)</th>
                        <th>Real %</th>
                        <th>Drift</th>
                        <th>Completado</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    ${ data.formularios.map( ( form ) => `
                        <tr class="row-${ form.drift_status }">
                            <td><strong>${ escapeHtml( form.form_title ) }</strong></td>
                            <td>${ form.probability_theoretical }%</td>
                            <td>${ form.assigned_count }</td>
                            <td>${ form.assigned_percentage }%</td>
                            <td>
                                <span class="drift ${ form.drift_status }">
                                    ${ form.drift_percentage > 0 ? '+' : '' }${ form.drift_percentage }%
                                </span>
                            </td>
                            <td>${ form.completion_rate }% (${ form.completed_count })</td>
                            <td>${ form.status_indicator }</td>
                        </tr>
                    ` ).join( '' ) }
                </tbody>
            </table>
            
            <div class="distribution-summary">
                <p><strong>Recomendación:</strong> ${ escapeHtml( data.summary.recommendation ) }</p>
                <p style="font-size: 0.85em; color: #666;">
                    Sample size: ${ data.total_assigned } (±${ calculateMarginError( data.total_assigned ).toFixed( 1 ) }% error margin)
                </p>
            </div>
        </div>
    `;

        // Buscar contenedor o crearlo si no existe
        let container = document.getElementById( 'distribution-stats-container' );
        if ( ! container ) {
            container = document.createElement( 'div' );
            container.id = 'distribution-stats-container';
            document.body.appendChild( container );
        }
        container.innerHTML = html;
    }

    /**
     * Gráfico comparativo (Chart.js)
     */
    function setupDistributionChart( data ) {
        const canvas = document.getElementById( 'distributionChart' );
        if ( ! canvas ) {
            // Crear canvas si no existe
            const canvasContainer = document.getElementById( 'distribution-chart-container' ) || 
                document.getElementById( 'distribution-stats-container' );
            
            if ( canvasContainer ) {
                const newCanvas = document.createElement( 'canvas' );
                newCanvas.id = 'distributionChart';
                newCanvas.height = '200';
                canvasContainer.appendChild( newCanvas );
            }
        }

        const ctx = canvas.getContext( '2d' );

        // Verificar si Chart.js está disponible
        if ( typeof Chart === 'undefined' ) {
            console.warn( 'Chart.js no disponible. Usando visualización alternativa.' );
            renderAlternativeChart( data );
            return;
        }

        // Destruir gráfico anterior si existe
        if ( window.distributionChartInstance ) {
            window.distributionChartInstance.destroy();
        }

        window.distributionChartInstance = new Chart( ctx, {
            type: 'bar',
            data: {
                labels: data.formularios.map( ( f ) => f.form_title ),
                datasets: [
                    {
                        label: 'Teórico %',
                        data: data.formularios.map( ( f ) => f.probability_theoretical ),
                        backgroundColor: 'rgba( 100, 150, 200, 0.6 )',
                        borderColor: 'rgba( 100, 150, 200, 1 )',
                        borderWidth: 2,
                    },
                    {
                        label: 'Real %',
                        data: data.formularios.map( ( f ) => f.assigned_percentage ),
                        backgroundColor: 'rgba( 76, 175, 80, 0.6 )',
                        borderColor: 'rgba( 76, 175, 80, 1 )',
                        borderWidth: 2,
                    },
                ],
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Distribución: Esperado vs Actual',
                        font: {
                            size: 16,
                            weight: 'bold',
                        },
                    },
                    legend: {
                        position: 'top',
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Porcentaje (%)',
                        },
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Formularios',
                        },
                    },
                },
            },
        } );
    }

    /**
     * Gráfico alternativo sin Chart.js
     */
    function renderAlternativeChart( data ) {
        const container = document.getElementById( 'distribution-chart-container' ) ||
            document.getElementById( 'distribution-stats-container' );

        if ( ! container ) return;

        let html = `
        <div class="distribution-chart-fallback">
            <h4>Gráfico de Distribución (Versión Simplificada)</h4>
            <div class="chart-bars">
    `;

        data.formularios.forEach( ( form ) => {
            const maxValue = Math.max( ...data.formularios.map( ( f ) => Math.max( f.probability_theoretical, f.assigned_percentage ) ) );
            const theoreticalWidth = ( form.probability_theoretical / maxValue ) * 100;
            const realWidth = ( form.assigned_percentage / maxValue ) * 100;

            html += `
            <div class="chart-row">
                <div class="chart-label">${ escapeHtml( form.form_title ) }</div>
                <div class="chart-bars-container">
                    <div class="bar theoretical" style="width: ${ theoreticalWidth }%"></div>
                    <div class="bar real" style="width: ${ realWidth }%"></div>
                </div>
                <div class="chart-values">
                    <span class="value theoretical">T: ${ form.probability_theoretical }%</span>
                    <span class="value real">R: ${ form.assigned_percentage }%</span>
                </div>
            </div>
        `;
        } );

        html += `
            </div>
            <div class="chart-legend">
                <span class="legend-item"><span class="legend-color theoretical"></span> Teórico</span>
                <span class="legend-item"><span class="legend-color real"></span> Real</span>
            </div>
        </div>
    `;

        container.innerHTML += html;
    }

    /**
     * Mostrar Health Score
     */
    function renderHealthScore( score ) {
        const color = score >= 80 ? 'green' : score >= 60 ? 'orange' : 'red';
        const html = `
        <div class="health-score ${ color }">
            <div class="score-value">${ score }</div>
            <div class="score-label">Salud RCT</div>
        </div>
    `;

        // Buscar contenedor o crearlo
        let container = document.getElementById( 'health-score-container' );
        if ( ! container ) {
            container = document.createElement( 'div' );
            container.id = 'health-score-container';
            document.body.appendChild( container );
        }
        container.innerHTML = html;
    }

    /**
     * Obtener emoji según status
     */
    function getStatusEmoji( status ) {
        switch ( status ) {
            case 'ok':
                return '✅';
            case 'warning':
                return '⚠️';
            case 'alert':
                return '🔴';
            default:
                return '❓';
        }
    }

    /**
     * Calcular margen de error (95% CI)
     */
    function calculateMarginError( n ) {
        if ( n <= 0 ) return 100;
        // Fórmula: 1.96 * sqrt(p(1-p)/n) * 100
        // Assuming p=0.5 (worst case)
        return 1.96 * Math.sqrt( 0.5 * 0.5 / n ) * 100;
    }

    /**
     * Mostrar modal de análisis de distribución
     */
    function showDistributionAnalysis( randomizationId ) {
        const modal = document.getElementById( 'rct-distribution-modal' );
        const modalBody = document.getElementById( 'distribution-stats-container' );

        if ( ! modal || ! modalBody ) {
            // Crear modal si no existe
            createDistributionModal();
        }

        // Limpiar contenido anterior
        document.getElementById( 'distribution-stats-container' ).innerHTML = `
        <div class="loading-indicator">
            <div class="spinner"></div>
            <p>Analizando distribución...</p>
        </div>
    `;

        // Limpiar chart anterior
        if ( window.distributionChartInstance ) {
            window.distributionChartInstance.destroy();
        }

        // Mostrar modal
        document.getElementById( 'rct-distribution-modal' ).style.display = 'block';

        // Cargar datos
        loadDistributionStats( randomizationId );
    }

    /**
     * Crear modal de análisis de distribución
     */
    function createDistributionModal() {
        const modalHtml = `
        <div id="rct-distribution-modal" class="eipsi-modal" style="display: none;">
            <div class="eipsi-modal-content">
                <div class="eipsi-modal-header">
                    <h3>📊 Análisis de Distribución</h3>
                    <button type="button" class="eipsi-modal-close">&times;</button>
                </div>
                <div class="eipsi-modal-body">
                    <div id="health-score-container"></div>
                    <div id="distribution-chart-container"></div>
                    <div id="distribution-stats-container"></div>
                </div>
            </div>
        </div>
    `;

        document.body.insertAdjacentHTML( 'beforeend', modalHtml );

        // Configurar eventos del modal
        document.addEventListener( 'click', function ( e ) {
            if ( e.target.id === 'rct-distribution-modal' || e.target.classList.contains( 'eipsi-modal-close' ) ) {
                document.getElementById( 'rct-distribution-modal' ).style.display = 'none';
            }
        } );

        document.addEventListener( 'keydown', function ( e ) {
            if ( e.key === 'Escape' ) {
                document.getElementById( 'rct-distribution-modal' ).style.display = 'none';
            }
        } );
    }

    // Exponer función globalmente para uso desde HTML
    window.showDistributionAnalysis = showDistributionAnalysis;

    /**
     * Inicialización cuando el DOM esté listo
     */
    $( document ).ready( function () {
        // Solo inicializar si estamos en la página correcta
        if ( $( '#rct-dashboard' ).length > 0 ) {
            initRCTDashboard();
        }
    } );
} )( jQuery );
