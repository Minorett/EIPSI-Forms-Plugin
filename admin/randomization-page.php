<?php
/**
 * EIPSI Forms - Randomization Dashboard
 * Dashboard principal para monitorear estudios de aleatorización controlada
 * 
 * @package EIPSI_Forms
 * @since 1.3.2
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renderizar página principal del Randomization Dashboard
 */
function eipsi_display_randomization() {
    // Verificar permisos
    if (!current_user_can('manage_options')) {
        wp_die(__('Unauthorized', 'eipsi-forms'));
    }

    // Verificar nonce para AJAX
    $nonce = wp_create_nonce('eipsi_randomization_nonce');
    $ajax_url = admin_url('admin-ajax.php');

    // Auto-load config desde URL (?config=...)
    $requested_config_id = isset($_GET['config']) ? sanitize_text_field(wp_unslash($_GET['config'])) : '';
    $auto_load_config = '';
    if (!empty($requested_config_id) && function_exists('eipsi_check_config_exists')) {
        if (eipsi_check_config_exists($requested_config_id)) {
            $auto_load_config = $requested_config_id;
        }
    }

    // URL para volver al listado de Results & Experience (mantiene el tab randomization)
    $back_to_results_url = admin_url('admin.php?page=eipsi-results-experience&tab=randomization');
    ?>
    
    <div class="wrap eipsi-randomization">
        <div class="rct-header">
            <div class="rct-header-left">
                <?php if (!empty($auto_load_config)) : ?>
                    <div class="rct-breadcrumb">
                        <a href="<?php echo esc_url($back_to_results_url); ?>">
                            ← <?php esc_html_e('Volver a Editar', 'eipsi-forms'); ?>
                        </a>
                        <span class="separator">/</span>
                        <span class="current"><?php esc_html_e('Monitoreo en Vivo', 'eipsi-forms'); ?></span>
                    </div>
                <?php endif; ?>

                <h1>
                    🎲 <?php esc_html_e('Randomization Dashboard', 'eipsi-forms'); ?>
                    <?php if (!empty($auto_load_config)) : ?>
                        <span class="config-id-badge">
                            <?php esc_html_e('Config:', 'eipsi-forms'); ?>
                            <code
                                title="<?php echo esc_attr($auto_load_config); ?>"
                                data-copy-id="<?php echo esc_attr($auto_load_config); ?>"
                            >
                                <?php echo esc_html(substr($auto_load_config, 0, 8)); ?>...
                            </code>
                        </span>
                    <?php endif; ?>
                </h1>
            </div>
            <div class="rct-actions">
                <button type="button" id="refresh-rct-data" class="button button-secondary">
                    🔄 <?php esc_html_e('Actualizar', 'eipsi-forms'); ?>
                </button>
                <span class="last-updated">
                    <?php esc_html_e('Última actualización:', 'eipsi-forms'); ?> 
                    <span id="last-updated-time"><?php echo esc_html(date_i18n('H:i:s')); ?></span>
                </span>
            </div>
        </div>

        <!-- Mensajes -->
        <div id="rct-message-container"></div>

        <!-- Modal para detalles -->
        <div id="rct-details-modal" class="eipsi-modal" style="display: none;">
            <div class="eipsi-modal-content">
                <div class="eipsi-modal-header">
                    <h3 id="modal-title">Detalles</h3>
                    <button type="button" class="eipsi-modal-close">&times;</button>
                </div>
                <div class="eipsi-modal-body">
                    <div id="modal-body"></div>
                </div>
            </div>
        </div>

        <!-- Dashboard Container -->
        <div id="rct-dashboard" class="rct-dashboard">
            <div class="loading-indicator">
                <div class="spinner"></div>
                <p><?php esc_html_e('Cargando datos de aleatorizaciones...', 'eipsi-forms'); ?></p>
            </div>
        </div>

        <!-- Modal para detalles -->
        <div id="rct-details-modal" class="eipsi-modal" style="display: none;">
            <div class="eipsi-modal-content">
                <div class="eipsi-modal-header">
                    <h3 id="modal-title"><?php esc_html_e('Detalles de Aleatorización', 'eipsi-forms'); ?></h3>
                    <button type="button" class="eipsi-modal-close">&times;</button>
                </div>
                <div class="eipsi-modal-body" id="modal-body">
                    <div class="loading-indicator">
                        <div class="spinner"></div>
                        <p><?php esc_html_e('Cargando detalles...', 'eipsi-forms'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal para Asignaciones Manuales -->
        <div id="manual-overrides-modal" class="eipsi-modal" style="display: none;">
            <div class="eipsi-modal-content modal-medium">
                <div class="eipsi-modal-header">
                    <h3>✏️ Asignaciones Manuales</h3>
                    <button type="button" class="eipsi-modal-close manual-overrides-close">&times;</button>
                </div>
                <div class="eipsi-modal-body">
                    <input type="hidden" id="manual-overrides-randomization-id" value="">

                    <div class="manual-overrides-header">
                        <h4>Lista de Asignaciones</h4>
                        <button type="button" class="rct-button rct-button-primary" onclick="openAddOverrideModal()">
                            ➕ Agregar Asignación
                        </button>
                    </div>

                    <table class="manual-overrides-table">
                        <thead>
                            <tr>
                                <th>Estado</th>
                                <th>Fingerprint</th>
                                <th>Formulario</th>
                                <th>Razón</th>
                                <th>Por</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="manual-overrides-table-body">
                            <tr>
                                <td colspan="7" style="text-align: center; color: #64748b;">
                                    Selecciona una configuración para ver sus asignaciones manuales
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal para agregar asignación manual -->
        <div id="add-override-modal" class="eipsi-modal" style="display: none;">
            <div class="eipsi-modal-content modal-medium">
                <div class="eipsi-modal-header">
                    <h3>➕ Nueva Asignación Manual</h3>
                    <button type="button" class="eipsi-modal-close add-override-close">&times;</button>
                </div>
                <div class="eipsi-modal-body">
                    <form id="add-override-form">
                        <div class="form-group">
                            <label for="override-fingerprint">Fingerprint del Usuario *</label>
                            <input
                                type="text"
                                id="override-fingerprint"
                                class="form-control"
                                placeholder="fp_xxxxxx... o email_xxxxxx..."
                                required
                            >
                            <small class="form-text">
                                Copia el fingerprint desde la pestaña "Lista de Usuarios" en los detalles.
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="override-form-id">Formulario Asignado *</label>
                            <select id="override-form-id" class="form-control" required>
                                <option value="">Cargando formularios...</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="override-reason">Razón (opcional)</label>
                            <textarea
                                id="override-reason"
                                class="form-control"
                                rows="3"
                                placeholder="Ej: Participante del grupo control necesita intervención específica..."
                            ></textarea>
                        </div>

                        <div class="form-group">
                            <label for="override-expires-days">Expira en (días)</label>
                            <select id="override-expires-days" class="form-control">
                                <option value="0">Nunca expira</option>
                                <option value="7">7 días</option>
                                <option value="30">30 días</option>
                                <option value="90">90 días</option>
                                <option value="365">1 año</option>
                            </select>
                            <small class="form-text">
                                Después de este período, el usuario volverá a la aleatorización normal.
                            </small>
                        </div>

                        <div class="form-actions">
                            <button type="button" id="save-override-btn" class="rct-button rct-button-primary">
                                💾 Guardar Asignación
                            </button>
                            <button type="button" class="rct-button add-override-close">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>
        .eipsi-randomization {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .rct-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e1e5e9;
        }

        .rct-header h1 {
            margin: 0;
            color: #1e293b;
            font-size: 24px;
            font-weight: 600;
        }

        .rct-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .last-updated {
            color: #64748b;
            font-size: 14px;
        }

        .rct-dashboard {
            min-height: 400px;
        }

        .loading-indicator {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 200px;
            color: #64748b;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 15px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Cards de aleatorización */
        .rct-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .rct-card:hover {
            border-color: #3b82f6;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .rct-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .rct-card-title {
            font-size: 16px;
            font-weight: 600;
            color: #1e293b;
            margin: 0 0 5px 0;
        }

        .rct-card-meta {
            display: flex;
            gap: 15px;
            font-size: 13px;
            color: #64748b;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-active {
            background: #dcfce7;
            color: #166534;
        }

        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Distribución */
        .distribution-section {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 15px;
            margin: 15px 0;
        }

        .distribution-title {
            font-weight: 600;
            margin-bottom: 10px;
            color: #374151;
        }

        .distribution-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .distribution-label {
            font-size: 14px;
            color: #4b5563;
            min-width: 120px;
        }

        .progress-bar {
            flex: 1;
            height: 20px;
            background: #f1f5f9;
            border-radius: 10px;
            margin: 0 15px;
            overflow: hidden;
            position: relative;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #3b82f6, #1d4ed8);
            border-radius: 10px;
            transition: width 0.5s ease;
        }

        .distribution-value {
            font-size: 14px;
            font-weight: 600;
            color: #1e293b;
            min-width: 60px;
            text-align: right;
        }

        /* Métricas */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .metric-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 15px;
            text-align: center;
        }

        .metric-value {
            font-size: 24px;
            font-weight: 700;
            color: #3b82f6;
            margin-bottom: 5px;
        }

        .metric-label {
            font-size: 13px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 600;
        }

        /* Botones de acción */
        .rct-actions-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }

        .rct-button {
            padding: 8px 16px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            background: white;
            color: #374151;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .rct-button:hover {
            background: #f9fafb;
            border-color: #9ca3af;
        }

        .rct-button-primary {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }

        .rct-button-primary:hover {
            background: #1d4ed8;
        }

        .rct-button-analysis {
            background: #8b5cf6;
            color: white;
            border-color: #8b5cf6;
        }

        .rct-button-analysis:hover {
            background: #7c3aed;
            color: white;
            border-color: #7c3aed;
        }

        .rct-button-export {
            background: #059669;
            color: white;
            border-color: #059669;
        }

        .rct-button-export:hover {
            background: #047857;
        }

        /* Estado vacío */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #64748b;
        }

        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .empty-state-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .empty-state-description {
            font-size: 14px;
            margin-bottom: 20px;
        }

        /* Modal */
        .eipsi-modal {
            position: fixed;
            z-index: 100000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .eipsi-modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 8px;
            width: 90%;
            max-width: 800px;
            max-height: 80vh;
            overflow: hidden;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .eipsi-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #e5e7eb;
            background: #f9fafb;
        }

        .eipsi-modal-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
        }

        .eipsi-modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #6b7280;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s ease;
        }

        .eipsi-modal-close:hover {
            background: #e5e7eb;
            color: #374151;
        }

        .eipsi-modal-body {
            padding: 20px;
            max-height: 60vh;
            overflow-y: auto;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .rct-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .rct-actions {
                width: 100%;
                justify-content: space-between;
            }

            .metrics-grid {
                grid-template-columns: 1fr;
            }

            .distribution-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .progress-bar {
                margin: 0;
                width: 100%;
            }

            .eipsi-modal-content {
                width: 95%;
                margin: 2% auto;
            }
        }

        /* ========================================
           ASIGNACIONES MANUALES (Estilos)
           ======================================== */

        /* Modal medium para overrides */
        .modal-medium .eipsi-modal-content {
            max-width: 700px;
        }

        /* Panel de overrides */
        .manual-overrides-panel {
            background: white;
        }

        .manual-overrides-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e5e7eb;
        }

        .manual-overrides-header h4 {
            margin: 0;
            color: #1f2937;
            font-size: 16px;
            font-weight: 600;
        }

        /* Tabla de overrides */
        .manual-overrides-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background: white;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .manual-overrides-table th,
        .manual-overrides-table td {
            padding: 12px 10px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
            font-size: 13px;
        }

        .manual-overrides-table th {
            background: #f9fafb;
            font-weight: 600;
            color: #374151;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
        }

        .manual-overrides-table tbody tr:hover {
            background: #f8fafc;
        }

        .manual-overrides-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Filas con colores por estado */
        .row-active {
            background: #f0fdf4a !important;
        }

        .row-active:hover {
            background: #dcfce7 !important;
        }

        .row-revoked {
            background: #fef2f2 !important;
        }

        .row-revoked:hover {
            background: #fee2e2 !important;
        }

        .row-expired {
            background: #fef9c3 !important;
            opacity: 0.7;
        }

        /* Botones de acción en tabla */
        .btn-revoke,
        .btn-delete {
            padding: 6px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .btn-revoke {
            background: #fef3c7;
            color: #92400e;
        }

        .btn-revoke:hover {
            background: #fde68a;
        }

        .btn-delete {
            background: #fee2e2;
            color: #991b1b;
        }

        .btn-delete:hover {
            background: #fecaca;
        }

        /* Formulario de override */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.2s ease;
            box-sizing: border-box;
        }

        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-text {
            display: block;
            margin-top: 6px;
            color: #64748b;
            font-size: 12px;
            line-height: 1.4;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }

        /* Responsive para overrides */
        @media (max-width: 600px) {
            .manual-overrides-table {
                font-size: 11px;
            }

            .manual-overrides-table th,
            .manual-overrides-table td {
                padding: 8px 6px;
            }

            .manual-overrides-table th:nth-child(n+4),
            .manual-overrides-table td:nth-child(n+4) {
                display: none;
            }

            .form-actions {
                flex-direction: column;
            }

            .form-actions .rct-button {
                width: 100%;
            }
        }
    </style>

    <script>
        jQuery(document).ready(function($) {
            // Configuración global
            const RCT_ANALYTICS = {
                ajaxUrl: '<?php echo esc_js($ajax_url); ?>',
                nonce: '<?php echo esc_js($nonce); ?>',
                refreshInterval: 60000, // 60 segundos
                autoRefresh: true
            };

            // Inicializar dashboard
            loadRCTData();
            
            // Auto-refresh cada minuto
            if (RCT_ANALYTICS.autoRefresh) {
                setInterval(loadRCTData, RCT_ANALYTICS.refreshInterval);
            }

            // Eventos
            $(document).on('click', '#refresh-rct-data', function(e) {
                e.preventDefault();
                loadRCTData();
            });

            $(document).on('click', '.rct-view-details', function(e) {
                e.preventDefault();
                const randomizationId = $(this).data('randomization-id');
                showRCTDetails(randomizationId);
            });

            $(document).on('click', '.eipsi-modal-close, .eipsi-modal', function(e) {
                if (e.target === this) {
                    $('#rct-details-modal').hide();
                }
            });

            // Funciones principales
            function loadRCTData() {
                showLoading();
                
                $.ajax({
                    url: RCT_ANALYTICS.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'eipsi_get_randomizations',
                        nonce: RCT_ANALYTICS.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            renderRCTDashboard(response.data);
                            updateLastUpdatedTime();
                        } else {
                            showError('Error al cargar datos: ' + (response.data || 'Error desconocido'));
                        }
                    },
                    error: function() {
                        showError('Error de conexión al cargar datos');
                    },
                    complete: function() {
                        hideLoading();
                    }
                });
            }

            function renderRCTDashboard(data) {
                const container = $('#rct-dashboard');
                
                if (!data.randomizations || data.randomizations.length === 0) {
                    container.html(getEmptyState());
                    return;
                }

                let html = '';
                data.randomizations.forEach(function(rct) {
                    html += renderRCtCard(rct);
                });

                container.html(html);
            }

            function renderRCtCard(rct) {
                const statusClass = rct.is_active ? 'status-active' : 'status-inactive';
                const statusText = rct.is_active ? '🟢 Activa' : '🔴 Inactiva';
                
                let distributionHtml = '';
                if (rct.distribution && rct.distribution.length > 0) {
                    rct.distribution.forEach(function(dist) {
                        const percentage = dist.count > 0 ? Math.round((dist.count / rct.total_assigned) * 100) : 0;
                        distributionHtml += `
                            <div class="distribution-item">
                                <div class="distribution-label">${escapeHtml(dist.form_title)}</div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: ${percentage}%"></div>
                                </div>
                                <div class="distribution-value">${dist.count} (${percentage}%)</div>
                            </div>
                        `;
                    });
                }

                const completionRate = rct.total_assigned > 0 ? Math.round((rct.completed_count / rct.total_assigned) * 100) : 0;

                return `
                    <div class="rct-card">
                        <div class="rct-card-header">
                            <div>
                                <h3 class="rct-card-title">${escapeHtml(rct.randomization_id)}</h3>
                                <div class="rct-card-meta">
                                    <span>📅 ${escapeHtml(rct.created_formatted)}</span>
                                    <span>🎯 ${escapeHtml(rct.method.toUpperCase())}</span>
                                    <span class="status-badge ${statusClass}">${statusText}</span>
                                </div>
                            </div>
                        </div>

                        <div class="distribution-section">
                            <div class="distribution-title">Distribución de Asignaciones</div>
                            ${distributionHtml || '<p style="color: #64748b; font-style: italic;">Sin asignaciones aún</p>'}
                            <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                                <strong>Total Asignados: ${rct.total_assigned}</strong>
                                <span style="color: #64748b;">Última asignación: ${escapeHtml(rct.last_assignment_formatted)}</span>
                            </div>
                        </div>

                        <div class="metrics-grid">
                            <div class="metric-card">
                                <div class="metric-value">${rct.completed_count}</div>
                                <div class="metric-label">Completados</div>
                            </div>
                            <div class="metric-card">
                                <div class="metric-value">${completionRate}%</div>
                                <div class="metric-label">Tasa Completado</div>
                            </div>
                            <div class="metric-card">
                                <div class="metric-value">${rct.avg_access_count || 0}</div>
                                <div class="metric-label">Promedio Accesos</div>
                            </div>
                            <div class="metric-card">
                                <div class="metric-value">${rct.avg_days || 0}</div>
                                <div class="metric-label">Promedio Días</div>
                            </div>
                        </div>

                        <div class="rct-actions-buttons">
                            <button type="button" class="rct-button rct-button-primary rct-view-details" data-randomization-id="${escapeHtml(rct.randomization_id)}">
                                👁️ Ver Detalles
                            </button>
                            <button type="button" class="rct-button rct-button-analysis" onclick="showDistributionAnalysis('${escapeHtml(rct.randomization_id)}')">
                                📊 Análisis Distribución
                            </button>
                            <button type="button" class="rct-button" onclick="showManualOverrides('${escapeHtml(rct.randomization_id)}')">
                                ✏️ Asignaciones Manuales
                            </button>
                            <button type="button" class="rct-button rct-button-export" onclick="downloadAssignmentsCSV('${escapeHtml(rct.randomization_id)}')">
                                📥 Exportar CSV
                            </button>
                            <button type="button" class="rct-button rct-button-export" onclick="downloadAssignmentsExcel('${escapeHtml(rct.randomization_id)}')">
                                📊 Exportar Excel
                            </button>
                            <button type="button" class="rct-button" onclick="copyRCTId('${escapeHtml(rct.randomization_id)}')">
                                📋 Copiar ID
                            </button>
                        </div>
                    </div>
                `;
            }

            function showRCTDetails(randomizationId) {
                const modal = $('#rct-details-modal');
                const modalBody = $('#modal-body');
                
                modalBody.html('<div class="loading-indicator"><div class="spinner"></div><p>Cargando detalles...</p></div>');
                modal.show();

                $.ajax({
                    url: RCT_ANALYTICS.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'eipsi_get_randomization_details',
                        randomization_id: randomizationId,
                        nonce: RCT_ANALYTICS.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            modalBody.html(renderDetailsView(response.data));
                        } else {
                            modalBody.html('<p style="color: #dc2626;">Error al cargar detalles: ' + (response.data || 'Error desconocido') + '</p>');
                        }
                    },
                    error: function() {
                        modalBody.html('<p style="color: #dc2626;">Error de conexión al cargar detalles</p>');
                    }
                });
            }

            function renderDetailsView(data) {
                // Implementación básica de la vista de detalles
                return `
                    <div>
                        <h4>Detalles de: ${escapeHtml(data.randomization_id)}</h4>
                        <p><strong>Total Asignados:</strong> ${data.total_assigned}</p>
                        <p><strong>Completados:</strong> ${data.completed_count}</p>
                        <p><strong>Tasa de Completado:</strong> ${data.completion_rate}%</p>
                        
                        ${data.distribution && data.distribution.length > 0 ? `
                            <h5>Distribución por Formulario:</h5>
                            ${data.distribution.map(dist => `
                                <div style="margin: 10px 0; padding: 10px; background: #f8fafc; border-radius: 6px;">
                                    <strong>${escapeHtml(dist.form_title)}</strong><br>
                                    Asignados: ${dist.count} | Completados: ${dist.completed_count}
                                </div>
                            `).join('')}
                        ` : '<p>No hay distribución disponible</p>'}
                    </div>
                `;
            }

            // Funciones de utilidad
            function showLoading() {
                $('#rct-dashboard .loading-indicator').show();
            }

            function hideLoading() {
                $('#rct-dashboard .loading-indicator').hide();
            }

            function showError(message) {
                $('#rct-message-container').html('<div class="notice notice-error"><p>' + escapeHtml(message) + '</p></div>');
                setTimeout(function() {
                    $('#rct-message-container').empty();
                }, 5000);
            }

            function updateLastUpdatedTime() {
                const now = new Date();
                const timeString = now.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                $('#last-updated-time').text(timeString);
            }

            function getEmptyState() {
                return `
                    <div class="empty-state">
                        <div class="empty-state-icon">🎲</div>
                        <h3 class="empty-state-title">No hay aleatorizaciones aún</h3>
                        <p class="empty-state-description">Cuando crees estudios RCT, aparecerán aquí para monitoreo.</p>
                        <button type="button" class="rct-button rct-button-primary" onclick="window.open('<?php echo admin_url('post-new.php?post_type=page'); ?>', '_blank')">
                            ➕ Crear Aleatorización
                        </button>
                    </div>
                `;
            }

            function escapeHtml(text) {
                if (typeof text !== 'string') return text;
                const map = {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                };
                return text.replace(/[&<>"']/g, function(m) { return map[m]; });
            }

            // Función global para copiar ID
            window.copyRCTId = function(id) {
                navigator.clipboard.writeText(id).then(function() {
                    showMessage('ID copiado al portapapeles', 'success');
                }).catch(function() {
                    // Fallback para navegadores más antiguos
                    const textArea = document.createElement('textarea');
                    textArea.value = id;
                    document.body.appendChild(textArea);
                    textArea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textArea);
                    showMessage('ID copiado al portapapeles', 'success');
                });
            };

            // Función para descargar asignaciones en CSV
            window.downloadAssignmentsCSV = function(randomizationId) {
                if (!confirm('¿Descargar todas las asignaciones en formato CSV?')) {
                    return;
                }

                const formData = new FormData();
                formData.append('action', 'eipsi_download_assignments_csv');
                formData.append('randomization_id', randomizationId);
                formData.append('nonce', RCT_ANALYTICS.nonce);

                // Crear form invisible para submit
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = RCT_ANALYTICS.ajaxUrl;
                form.style.display = 'none';

                for (const [key, value] of formData.entries()) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = value;
                    form.appendChild(input);
                }

                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);

                showMessage('Descargando archivo CSV...', 'success');
            };

            // Función para descargar asignaciones en Excel
            window.downloadAssignmentsExcel = function(randomizationId) {
                if (!confirm('¿Descargar todas las asignaciones en formato Excel?')) {
                    return;
                }

                const formData = new FormData();
                formData.append('action', 'eipsi_download_assignments_excel');
                formData.append('randomization_id', randomizationId);
                formData.append('nonce', RCT_ANALYTICS.nonce);

                // Crear form invisible para submit
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = RCT_ANALYTICS.ajaxUrl;
                form.style.display = 'none';

                for (const [key, value] of formData.entries()) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = value;
                    form.appendChild(input);
                }

                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);

                showMessage('Descargando archivo Excel...', 'success');
            };

            // Función para mostrar análisis de distribución
            window.showDistributionAnalysis = function(randomizationId) {
                showMessage('Función de análisis de distribución próximamente disponible', 'success');
            };

            function showMessage(message, type) {
                const className = type === 'success' ? 'notice-success' : 'notice-error';
                $('#rct-message-container').html('<div class="notice ' + className + '"><p>' + escapeHtml(message) + '</p></div>');
                setTimeout(function() {
                    $('#rct-message-container').empty();
                }, 3000);
            }

            // ========================================
            // ASIGNACIONES MANUALES (OVERRIDES)
            // ========================================

            // Función global para mostrar modal de asignaciones manuales
            window.showManualOverrides = function(randomizationId) {
                $('#manual-overrides-randomization-id').val(randomizationId);
                loadManualOverrides(randomizationId);
                $('#manual-overrides-modal').show();
            };

            function loadManualOverrides(randomizationId) {
                const container = $('#manual-overrides-table-body');
                container.html('<tr><td colspan="7" style="text-align: center;"><div class="spinner" style="margin: 20px auto;"></div></td></tr>');

                $.ajax({
                    url: RCT_ANALYTICS.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'eipsi_get_manual_overrides',
                        randomization_id: randomizationId,
                        nonce: RCT_ANALYTICS.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            renderManualOverridesTable(response.data.overrides);
                        } else {
                            container.html('<tr><td colspan="7" style="text-align: center; color: #dc2626;">Error al cargar asignaciones: ' + (response.data || 'Error desconocido') + '</td></tr>');
                        }
                    },
                    error: function() {
                        container.html('<tr><td colspan="7" style="text-align: center; color: #dc2626;">Error de conexión</td></tr>');
                    }
                });
            }

            function renderManualOverridesTable(overrides) {
                const container = $('#manual-overrides-table-body');

                if (!overrides || overrides.length === 0) {
                    container.html('<tr><td colspan="7" style="text-align: center; color: #64748b;">No hay asignaciones manuales configuradas</td></tr>');
                    return;
                }

                let html = '';
                overrides.forEach(function(override) {
                    const statusIcon = override.status === 'active' && !override.is_expired ? '✅' :
                                  override.status === 'revoked' ? '❌' :
                                  override.is_expired ? '⏰' : '❓';

                    const rowClass = override.status === 'active' && !override.is_expired ? 'row-active' :
                                   override.status === 'revoked' ? 'row-revoked' : 'row-expired';

                    html += `
                        <tr class="${rowClass}">
                            <td>${statusIcon} ${override.status}</td>
                            <td><code style="font-size: 11px; background: #f1f5f9; padding: 2px 6px; border-radius: 3px;">${escapeHtml(override.fingerprint)}</code></td>
                            <td><strong>${escapeHtml(override.form_title)}</strong></td>
                            <td>${escapeHtml(override.reason || '-')}</td>
                            <td>${escapeHtml(override.creator_name)}</td>
                            <td>${escapeHtml(override.created_formatted)}</td>
                            <td>
                                ${override.status === 'active' && !override.is_expired ? `
                                    <button type="button" class="btn-revoke" onclick="revokeManualOverride(${override.id})" title="Revocar asignación">
                                        ↩️
                                    </button>
                                ` : `
                                    <button type="button" class="btn-delete" onclick="deleteManualOverride(${override.id})" title="Eliminar permanentemente">
                                        🗑️
                                    </button>
                                `}
                            </td>
                        </tr>
                    `;
                });

                container.html(html);
            }

            function openAddOverrideModal() {
                $('#add-override-modal').show();
                loadFormsList();
            }

            function loadFormsList() {
                // Usar los formularios de la configuración actual
                const randomizationId = $('#manual-overrides-randomization-id').val();

                // Obtener los formularios desde la data actual del dashboard
                const currentConfigData = RCT_STATE.data.find(function(rct) {
                    return rct.randomization_id === randomizationId;
                });

                const select = $('#override-form-id');
                select.empty();

                if (currentConfigData && currentConfigData.formularios) {
                    currentConfigData.formularios.forEach(function(form) {
                        select.append(`<option value="${form.id}">${escapeHtml(form.name || 'Formulario ' + form.id)}</option>`);
                    });
                } else {
                    select.append('<option value="">No hay formularios disponibles</option>');
                }
            }

            function saveOverride() {
                const randomizationId = $('#manual-overrides-randomization-id').val();
                const fingerprint = $('#override-fingerprint').val().trim();
                const formId = $('#override-form-id').val();
                const reason = $('#override-reason').val().trim();
                const expiresDays = $('#override-expires-days').val();

                // Validaciones
                if (!fingerprint) {
                    alert('El fingerprint es requerido');
                    return;
                }
                if (!formId) {
                    alert('Debes seleccionar un formulario');
                    return;
                }

                // Deshabilitar botón
                const btn = $('#save-override-btn');
                btn.prop('disabled', true).text('Guardando...');

                $.ajax({
                    url: RCT_ANALYTICS.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'eipsi_create_manual_override',
                        randomization_id: randomizationId,
                        user_fingerprint: fingerprint,
                        assigned_form_id: formId,
                        reason: reason,
                        expires_days: expiresDays,
                        nonce: RCT_ANALYTICS.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            showMessage('Asignación manual guardada correctamente', 'success');
                            $('#add-override-modal').hide();
                            // Limpiar formulario
                            $('#override-fingerprint').val('');
                            $('#override-reason').val('');
                            // Recargar tabla
                            loadManualOverrides(randomizationId);
                        } else {
                            alert('Error: ' + (response.data || 'Error desconocido'));
                        }
                    },
                    error: function() {
                        alert('Error de conexión al guardar asignación');
                    },
                    complete: function() {
                        btn.prop('disabled', false).text('Guardar Asignación');
                    }
                });
            }

            function revokeManualOverride(overrideId) {
                if (!confirm('¿Estás seguro de revocar esta asignación manual?\n\nEl usuario volverá a la aleatorización normal.')) {
                    return;
                }

                $.ajax({
                    url: RCT_ANALYTICS.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'eipsi_revoke_manual_override',
                        override_id: overrideId,
                        nonce: RCT_ANALYTICS.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            showMessage('Asignación revocada correctamente', 'success');
                            // Recargar tabla
                            const randomizationId = $('#manual-overrides-randomization-id').val();
                            loadManualOverrides(randomizationId);
                        } else {
                            alert('Error: ' + (response.data || 'Error desconocido'));
                        }
                    },
                    error: function() {
                        alert('Error de conexión al revocar asignación');
                    }
                });
            }

            function deleteManualOverride(overrideId) {
                if (!confirm('¿Estás seguro de ELIMINAR permanentemente esta asignación?\n\nEsta acción no se puede deshacer.')) {
                    return;
                }

                $.ajax({
                    url: RCT_ANALYTICS.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'eipsi_delete_manual_override',
                        override_id: overrideId,
                        nonce: RCT_ANALYTICS.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            showMessage('Asignación eliminada correctamente', 'success');
                            // Recargar tabla
                            const randomizationId = $('#manual-overrides-randomization-id').val();
                            loadManualOverrides(randomizationId);
                        } else {
                            alert('Error: ' + (response.data || 'Error desconocido'));
                        }
                    },
                    error: function() {
                        alert('Error de conexión al eliminar asignación');
                    }
                });
            }

            // Event listeners para modales
            $(document).on('click', '.manual-overrides-close, #manual-overrides-modal', function(e) {
                if (e.target === this) {
                    $('#manual-overrides-modal').hide();
                }
            });

            $(document).on('click', '.add-override-close, #add-override-modal', function(e) {
                if (e.target === this) {
                    $('#add-override-modal').hide();
                }
            });

            // Event listener para guardar override
            $(document).on('click', '#save-override-btn', function(e) {
                e.preventDefault();
                saveOverride();
            });
        });
    </script>
    <?php
}
?>