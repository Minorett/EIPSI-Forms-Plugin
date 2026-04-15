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

    // URL para volver al listado de Cross-sectional Study (mantiene el tab randomization)
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
                    <span class="dashicons dashicons-randomize"></span> <?php esc_html_e('Randomization Dashboard', 'eipsi-forms'); ?>
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
                    <span class="dashicons dashicons-update"></span> <?php esc_html_e('Actualizar', 'eipsi-forms'); ?>
                </button>
                <button type="button" id="create-rct-btn" class="button button-primary" onclick="openCreateRCTModal()">
                    <span class="dashicons dashicons-plus"></span> <?php esc_html_e('Crear aleatorización', 'eipsi-forms'); ?>
                </button>
                <span class="last-updated">
                    <?php esc_html_e('Última actualización:', 'eipsi-forms'); ?> 
                    <span id="last-updated-time"><?php echo esc_html(date_i18n('H:i:s')); ?></span>
                </span>
            </div>
        </div>

        <!-- Mensajes -->
        <div id="rct-message-container"></div>

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

        <!-- Modal para crear/editar aleatorización - REDESIGN v2.5 -->
        <div class="eipsi-modal-overlay" id="rct-create-modal" style="display: none;">
            <div class="eipsi-modal-wide">
                <div class="eipsi-modal-header">
                    <h2 id="rct-create-title"><?php esc_html_e('Crear nueva aleatorización', 'eipsi-forms'); ?></h2>
                    <button class="eipsi-modal-close" type="button" onclick="closeCreateRCTModal()">&times;</button>
                </div>
                <div class="eipsi-modal-body">
                    <form id="rct-form" method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                        <input type="hidden" name="action" value="eipsi_save_randomization">
                        <input type="hidden" name="rct_id" id="rct-id" value="0">
                        <?php wp_nonce_field('eipsi_save_randomization_nonce', 'rct_nonce'); ?>

                        <!-- Nombre -->
                        <div class="eipsi-form-field">
                            <label for="rct-name"><?php _e('Nombre de la aleatorización', 'eipsi-forms'); ?></label>
                            <input type="text" name="rct_name" id="rct-name" required>
                        </div>

                        <!-- Descripción -->
                        <div class="eipsi-form-field">
                            <label for="rct-description"><?php _e('Descripción', 'eipsi-forms'); ?></label>
                            <textarea name="rct_description" id="rct-description" rows="2"></textarea>
                        </div>

                        <!-- Método -->
                        <div class="eipsi-form-field">
                            <label for="rct-method"><?php _e('Método', 'eipsi-forms'); ?></label>
                            <select name="method" id="rct-method">
                                <option value="seeded">🎲 <?php _e('SEEDED - Mismo participante = misma asignación', 'eipsi-forms'); ?></option>
                                <option value="pure-random">🎰 <?php _e('PURE-RANDOM - Cada acceso es nuevo', 'eipsi-forms'); ?></option>
                            </select>
                            <p class="description">
                                <?php _e('SEEDED: El participante siempre va al mismo formulario. PURE-RANDOM: Distribución completamente aleatoria.', 'eipsi-forms'); ?>
                            </p>
                        </div>

                        <!-- Formularios y probabilidades -->
                        <h3 class="eipsi-section-title"><?php _e('Formularios y probabilidades', 'eipsi-forms'); ?></h3>
                        <p class="eipsi-section-desc">
                            <?php _e('Agregá los formularios y asigná probabilidades. La suma debe ser exactamente 100%.', 'eipsi-forms'); ?>
                        </p>

                        <div id="rct-forms-rows">
                            <!-- Dynamic rows added via JS -->
                        </div>

                        <div class="eipsi-pool-actions">
                            <button type="button" class="eipsi-btn-secondary" id="rct-add-form-row">
                                + <?php _e('Agregar formulario', 'eipsi-forms'); ?>
                            </button>
                            <button type="button" class="eipsi-btn-secondary" id="rct-distribute-probabilities">
                                🔀 <?php _e('Distribuir equitativamente', 'eipsi-forms'); ?>
                            </button>
                        </div>

                        <!-- Barra de progreso -->
                        <div class="eipsi-probability-total" id="rct-probability-total-display">
                            <div class="eipsi-progress-bar">
                                <div class="eipsi-progress-fill invalid" id="rct-progress-fill" style="width: 0%"></div>
                            </div>
                            <div class="eipsi-progress-text invalid" id="rct-progress-text">
                                <span>0% / 100%</span>
                                <span id="rct-progress-status">❌ Incompleto</span>
                            </div>
                        </div>

                        <!-- Info de página creada (mostrado después de guardar) -->
                        <div id="rct-page-info" style="display: none; margin-top: 20px; padding: 16px; background: #f0f6fc; border-radius: 8px;">
                            <h4 style="margin: 0 0 12px 0; font-size: 14px; color: #1e293b;"><?php _e('Página de aleatorización creada', 'eipsi-forms'); ?></h4>
                            <div style="margin-bottom: 12px;">
                                <label style="display: block; font-size: 12px; color: #64748b; margin-bottom: 4px;"><?php _e('Shortcode:', 'eipsi-forms'); ?></label>
                                <code id="rct-shortcode-display" style="display: block; padding: 8px 12px; background: #1d2327; color: #a5f3fc; border-radius: 4px; font-family: monospace; font-size: 13px;">[eipsi_randomization config_id=""]</code>
                            </div>
                            <div>
                                <label style="display: block; font-size: 12px; color: #64748b; margin-bottom: 4px;"><?php _e('URL de la página:', 'eipsi-forms'); ?></label>
                                <a id="rct-page-url-display" href="#" target="_blank" style="color: #3b82f6; font-size: 13px; text-decoration: none;"></a>
                            </div>
                        </div>

                        <input type="hidden" name="rct_forms_data" id="rct-forms-data" value="">
                    </form>
                </div>
                <div class="eipsi-modal-footer">
                    <button type="button" class="eipsi-btn-ghost" onclick="closeCreateRCTModal()"><?php _e('Cancelar', 'eipsi-forms'); ?></button>
                    <button type="button" class="eipsi-btn-primary" id="rct-save-btn" disabled><?php _e('Guardar aleatorización', 'eipsi-forms'); ?></button>
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

        /* MODAL CREAR ALEATORIZACIÓN - CSS v2.5 */

        /* Modal Overlay */
        .eipsi-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.55);
            backdrop-filter: blur(2px);
            z-index: 100000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        /* Modal Container */
        .eipsi-modal-wide {
            background: #ffffff;
            border-radius: 12px;
            width: 100%;
            max-width: 640px;
            max-height: calc(100vh - 80px);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        /* Form Layout */
        .eipsi-form-field {
            margin-bottom: 16px;
        }

        .eipsi-form-field label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 6px;
        }

        .eipsi-form-field input[type="text"],
        .eipsi-form-field textarea,
        .eipsi-form-field select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 14px;
            color: #1e293b;
            background: #ffffff;
        }

        .eipsi-form-field .description {
            font-size: 12px;
            color: #6b7280;
            margin-top: 4px;
        }

        /* Section Title */
        .eipsi-section-title {
            font-size: 14px;
            font-weight: 600;
            color: #1e293b;
            margin: 24px 0 8px 0;
        }

        .eipsi-section-desc {
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 16px;
        }

        /* Form Rows */
        .eipsi-form-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            margin-bottom: 8px;
        }

        .eipsi-form-row select {
            flex: 1;
            min-width: 200px;
            padding: 6px 8px;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            font-size: 13px;
        }

        .eipsi-form-row input[type="number"] {
            width: 80px;
            text-align: right;
            padding: 6px 8px;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            font-size: 13px;
        }

        .eipsi-remove-row {
            color: #dc2626;
            cursor: pointer;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            font-size: 18px;
        }

        .eipsi-remove-row:hover {
            background: #fef2f2;
        }

        /* Progress Bar */
        .eipsi-probability-total {
            margin-top: 16px;
            padding: 12px 16px;
            background: #f8fafc;
            border-radius: 8px;
        }

        .eipsi-progress-bar {
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 8px;
        }

        .eipsi-progress-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.3s ease, background-color 0.3s;
        }

        .eipsi-progress-fill.valid {
            background: #10b981;
        }

        .eipsi-progress-fill.invalid {
            background: #ef4444;
        }

        .eipsi-progress-text {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            font-weight: 500;
        }

        .eipsi-progress-text.valid {
            color: #059669;
        }

        .eipsi-progress-text.invalid {
            color: #dc2626;
        }

        /* Action Buttons */
        .eipsi-pool-actions {
            display: flex;
            gap: 8px;
            margin-top: 8px;
        }

        .eipsi-btn-secondary {
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 500;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            background: #ffffff;
            color: #374151;
            cursor: pointer;
        }

        .eipsi-btn-secondary:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
        }

        /* Modal Footer */
        .eipsi-modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            padding: 16px 24px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
        }

        .eipsi-btn-ghost {
            padding: 8px 16px;
            font-size: 14px;
            font-weight: 500;
            border: none;
            border-radius: 6px;
            background: transparent;
            color: #64748b;
            cursor: pointer;
        }

        .eipsi-btn-ghost:hover {
            background: #f1f5f9;
            color: #374151;
        }

        .eipsi-btn-primary {
            padding: 8px 16px;
            font-size: 14px;
            font-weight: 500;
            border: none;
            border-radius: 6px;
            background: #3b82f6;
            color: #ffffff;
            cursor: pointer;
        }

        .eipsi-btn-primary:hover:not(:disabled) {
            background: #2563eb;
        }

        .eipsi-btn-primary:disabled {
            background: #cbd5e1;
            color: #94a3b8;
            cursor: not-allowed;
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
                loadRCTData(true); // Force refresh when user clicks the button
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
            function loadRCTData(forceRefresh = false) {
                showLoading();
                
                $.ajax({
                    url: RCT_ANALYTICS.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'eipsi_get_randomizations',
                        nonce: RCT_ANALYTICS.nonce,
                        force_refresh: forceRefresh ? '1' : '0',
                        timestamp: Date.now() // Cache busting parameter
                    },
                    success: function(response) {
                        if (response.success) {
                            renderRCTDashboard(response.data);
                            updateLastUpdatedTime();
                            if (forceRefresh) {
                                showMessage('Dashboard sincronizado correctamente', 'success');
                            }
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
                const statusText = rct.is_active ? 'Activa' : 'Inactiva';
                const statusIcon = rct.is_active ? '<span class="dashicons dashicons-yes-alt" style="color: #22c55e; font-size: 14px; vertical-align: middle;"></span>' : '<span class="dashicons dashicons-no-alt" style="color: #ef4444; font-size: 14px; vertical-align: middle;"></span>';
                
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
                                    <span><span class="dashicons dashicons-calendar-alt" style="font-size: 14px; vertical-align: middle;"></span> ${escapeHtml(rct.created_formatted)}</span>
                                    <span><span class="dashicons dashicons-randomize" style="font-size: 14px; vertical-align: middle;"></span> ${escapeHtml(rct.method.toUpperCase())}</span>
                                    <span class="status-badge ${statusClass}">${statusIcon} ${statusText}</span>
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
                                <span class="dashicons dashicons-visibility"></span> Ver Detalles
                            </button>
                            <button type="button" class="rct-button rct-button-export" onclick="downloadAssignmentsCSV('${escapeHtml(rct.randomization_id)}')">
                                <span class="dashicons dashicons-download"></span> CSV
                            </button>
                            <button type="button" class="rct-button rct-button-export" onclick="downloadAssignmentsExcel('${escapeHtml(rct.randomization_id)}')">
                                <span class="dashicons dashicons-media-spreadsheet"></span> Excel
                            </button>
                            <button type="button" class="rct-button" onclick="copyRCTId('${escapeHtml(rct.randomization_id)}')">
                                <span class="dashicons dashicons-admin-page"></span> Copiar ID
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
                        <div class="empty-state-icon"><span class="dashicons dashicons-randomize" style="font-size: 48px; color: #94a3b8;"></span></div>
                        <h3 class="empty-state-title">No hay aleatorizaciones aún</h3>
                        <button type="button" class="rct-button rct-button-primary" onclick="createRCTPage()">
                            <span class="dashicons dashicons-plus"></span> Crear Aleatorización
                        </button>
                    </div>
                `;
            }

            function createRCTPage() {
                showMessage('Creando página con bloque de aleatorización...', 'success');
                
                $.ajax({
                    url: RCT_ANALYTICS.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'eipsi_create_rct_page',
                        nonce: RCT_ANALYTICS.nonce
                    },
                    success: function(response) {
                        if (response.success && response.data.edit_url) {
                            window.open(response.data.edit_url, '_blank');
                            showMessage('Página creada. Configura el bloque y guarda como borrador.', 'success');
                            $('#rct-details-modal').hide();
                        } else {
                            showMessage('Error al crear la página: ' + (response.data || 'Error desconocido'), 'error');
                        }
                    },
                    error: function() {
                        showMessage('Error de conexión al crear la página', 'error');
                    }
                });
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

            // ==========================================================================
            // MODAL CREAR ALEATORIZACIÓN - JavaScript
            // ==========================================================================
            
            window.openCreateRCTModal = function() {
                document.getElementById('rct-create-modal').style.display = 'flex';
                resetRCTForm();
            };
            
            window.closeCreateRCTModal = function() {
                document.getElementById('rct-create-modal').style.display = 'none';
            };
            
            function resetRCTForm() {
                document.getElementById('rct-id').value = '0';
                document.getElementById('rct-name').value = '';
                document.getElementById('rct-description').value = '';
                document.getElementById('rct-method').value = 'seeded';
                document.getElementById('rct-forms-rows').innerHTML = '';
                document.getElementById('rct-page-info').style.display = 'none';
                updateRCTProbabilityTotal();
            }
            
            // Add form row
            document.getElementById('rct-add-form-row')?.addEventListener('click', function() {
                addRCTFormRow();
            });
            
            function addRCTFormRow(formId = '', probability = '') {
                const container = document.getElementById('rct-forms-rows');
                const rowCount = container.querySelectorAll('.eipsi-form-row').length;
                
                const row = document.createElement('div');
                row.className = 'eipsi-form-row';
                row.innerHTML = `
                    <select name="form_select[]" required style="flex:1;min-width:200px;padding:6px 8px;border:1px solid #e2e8f0;border-radius:4px;font-size:13px;">
                        <option value=""><?php echo esc_js(__('Seleccionar formulario...', 'eipsi-forms')); ?></option>
                        <?php 
                        // v2.5.1 - Obtener formularios de la tabla de resultados (igual que submissions-tab)
                        global $wpdb;
                        $results_table = $wpdb->prefix . 'vas_form_results';
                        $form_ids = $wpdb->get_col("SELECT DISTINCT form_id FROM {$results_table} WHERE form_id IS NOT NULL AND form_id != '' ORDER BY form_id");
                        foreach ($form_ids as $form_id) : ?>
                            <option value="<?php echo esc_js($form_id); ?>" ${formId == '<?php echo esc_js($form_id); ?>' ? 'selected' : ''}>
                                <?php echo esc_js($form_id); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" name="form_probability[]" value="${probability}" min="0" max="100" step="0.01" placeholder="%" required style="width:80px;text-align:right;padding:6px 8px;border:1px solid #e2e8f0;border-radius:4px;font-size:13px;">
                    <span class="eipsi-remove-row" title="<?php echo esc_js(__('Eliminar', 'eipsi-forms')); ?>">&times;</span>
                `;
                
                row.querySelector('.eipsi-remove-row').addEventListener('click', function() {
                    row.remove();
                    updateRCTProbabilityTotal();
                });
                
                row.querySelectorAll('input, select').forEach(input => {
                    input.addEventListener('change', updateRCTProbabilityTotal);
                });
                
                container.appendChild(row);
                updateRCTProbabilityTotal();
            }
            
            // Distribute probabilities
            document.getElementById('rct-distribute-probabilities')?.addEventListener('click', function() {
                const rows = document.querySelectorAll('#rct-forms-rows .eipsi-form-row');
                if (rows.length === 0) {
                    alert('<?php echo esc_js(__('Primero agregá al menos un formulario.', 'eipsi-forms')); ?>');
                    return;
                }
                const equalProb = (100 / rows.length).toFixed(2);
                rows.forEach(row => {
                    row.querySelector('input[type="number"]').value = equalProb;
                });
                updateRCTProbabilityTotal();
            });
            
            // Update probability total
            function updateRCTProbabilityTotal() {
                const rows = document.querySelectorAll('#rct-forms-rows .eipsi-form-row');
                let total = 0;
                rows.forEach(row => {
                    const input = row.querySelector('input[type="number"]');
                    total += parseFloat(input.value) || 0;
                });
                total = Math.round(total * 100) / 100;
                
                const progressFill = document.getElementById('rct-progress-fill');
                const progressText = document.getElementById('rct-progress-text');
                const progressStatus = document.getElementById('rct-progress-status');
                
                const displayWidth = Math.min(Math.max(total, 0), 100);
                progressFill.style.width = displayWidth + '%';
                
                const progressLabel = progressText.querySelector('span:first-child');
                progressLabel.textContent = total.toFixed(2) + '% / 100%';
                
                if (total === 100) {
                    progressFill.classList.remove('invalid');
                    progressFill.classList.add('valid');
                    progressText.classList.remove('invalid');
                    progressText.classList.add('valid');
                    progressStatus.textContent = '✅ Completo';
                } else {
                    progressFill.classList.remove('valid');
                    progressFill.classList.add('invalid');
                    progressText.classList.remove('valid');
                    progressText.classList.add('invalid');
                    progressStatus.textContent = total < 100 ? '❌ Incompleto' : '❌ Excedido';
                }
                
                updateRCTSaveButtonState();
            }
            
            // Update save button state
            function updateRCTSaveButtonState() {
                const rows = document.querySelectorAll('#rct-forms-rows .eipsi-form-row');
                let total = 0;
                rows.forEach(row => {
                    const input = row.querySelector('input[type="number"]');
                    total += parseFloat(input.value) || 0;
                });
                
                const isValid = Math.round(total * 100) / 100 === 100 && rows.length > 0;
                document.getElementById('rct-save-btn').disabled = !isValid;
            }
            
            // Save randomization
            document.getElementById('rct-save-btn')?.addEventListener('click', function() {
                if (this.disabled) {
                    showMessage('<?php echo esc_js(__('La suma de probabilidades debe ser exactamente 100%', 'eipsi-forms')); ?>', 'error');
                    return;
                }
                
                const rows = document.querySelectorAll('#rct-forms-rows .eipsi-form-row');
                const formsData = [];
                
                rows.forEach(row => {
                    const select = row.querySelector('select');
                    const input = row.querySelector('input[type="number"]');
                    if (select.value) {
                        formsData.push({
                            form_id: select.value,
                            probability: parseFloat(input.value) || 0
                        });
                    }
                });
                
                if (formsData.length === 0) {
                    showMessage('<?php echo esc_js(__('Agregá al menos un formulario', 'eipsi-forms')); ?>', 'error');
                    return;
                }
                
                document.getElementById('rct-forms-data').value = JSON.stringify(formsData);
                document.getElementById('rct-form').submit();
            });
            
            // Close modal on outside click
            document.getElementById('rct-create-modal')?.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeCreateRCTModal();
                }
            });
        });
    </script>
    <?php
}

/**
 * AJAX Handler: Crear página con bloque de randomization
 * Crea una página en borrador con el bloque EIPSI Randomization insertado
 */
add_action('wp_ajax_eipsi_create_rct_page', 'eipsi_create_rct_page_handler');

function eipsi_create_rct_page_handler() {
    check_ajax_referer('eipsi_randomization_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Unauthorized', 'eipsi-forms'));
    }

    // Crear la página en borrador
    $page_data = array(
        'post_title'   => sprintf(__('Aleatorización %s', 'eipsi-forms'), wp_date('Y-m-d H:i')),
        'post_content' => '<!-- wp:eipsi/randomization {"randomizationId":""} /-->',
        'post_status'  => 'draft',
        'post_type'    => 'page',
        'post_author'  => get_current_user_id(),
    );

    $page_id = wp_insert_post($page_data, true);

    if (is_wp_error($page_id)) {
        wp_send_json_error($page_id->get_error_message());
    }

    // Generar URL de edición
    $edit_url = get_edit_post_link($page_id, 'raw');

    wp_send_json_success(array(
        'page_id'  => $page_id,
        'edit_url' => $edit_url,
        'message'  => __('Página creada exitosamente', 'eipsi-forms')
    ));
}

/**
 * Create WordPress page for randomization with shortcode
 *
 * @param string $config_id Randomization config ID
 * @param string $name Randomization name
 * @return int|false Page ID or false on failure
 * @since 2.5.0
 */
function eipsi_create_randomization_page($config_id, $name) {
    $page_slug = 'randomization-' . sanitize_title($name);
    
    // Check if page already exists
    $existing_page = get_page_by_path($page_slug);
    
    if (!$existing_page) {
        $existing_pages = get_posts(array(
            'post_type' => 'page',
            'meta_key' => 'eipsi_randomization_config_id',
            'meta_value' => $config_id,
            'posts_per_page' => 1
        ));
        
        if (!empty($existing_pages)) {
            $existing_page = $existing_pages[0];
        }
    }
    
    if ($existing_page) {
        update_post_meta($existing_page->ID, 'eipsi_randomization_config_id', $config_id);
        return $existing_page->ID;
    }
    
    // Create new page
    $page_title = sprintf(__('Aleatorización: %s', 'eipsi-forms'), $name);
    $page_content = '[eipsi_randomization config_id="' . esc_attr($config_id) . '"]';
    
    $page_id = wp_insert_post(array(
        'post_title' => $page_title,
        'post_name' => $page_slug,
        'post_content' => $page_content,
        'post_status' => 'publish',
        'post_type' => 'page',
        'meta_input' => array(
            'eipsi_randomization_config_id' => $config_id
        )
    ));
    
    if (is_wp_error($page_id)) {
        error_log('[EIPSI] Failed to create randomization page: ' . $page_id->get_error_message());
        return false;
    }
    
    return $page_id;
}

/**
 * Get the randomization page URL
 *
 * @param string $config_id Config ID
 * @return string|null Page URL or null if no page exists
 * @since 2.5.0
 */
function eipsi_get_randomization_page_url($config_id) {
    $pages = get_posts(array(
        'post_type' => 'page',
        'meta_key' => 'eipsi_randomization_config_id',
        'meta_value' => $config_id,
        'posts_per_page' => 1
    ));
    
    if (!empty($pages)) {
        return get_permalink($pages[0]->ID);
    }
    
    return null;
}

/**
 * Handle randomization save action (admin-post.php)
 * Creates/updates randomization and auto-creates WordPress page
 *
 * @since 2.5.0
 */
add_action('admin_post_eipsi_save_randomization', 'eipsi_handle_save_randomization');

function eipsi_handle_save_randomization() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['rct_nonce'] ?? '', 'eipsi_save_randomization_nonce')) {
        wp_die(__('Error de seguridad. Por favor, recargá la página.', 'eipsi-forms'));
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_die(__('No tenés permisos para realizar esta acción.', 'eipsi-forms'));
    }
    
    // Get form data
    $rct_id = sanitize_text_field($_POST['rct_id'] ?? '');
    $rct_name = sanitize_text_field($_POST['rct_name'] ?? '');
    $rct_description = sanitize_textarea_field($_POST['rct_description'] ?? '');
    $method = sanitize_key($_POST['method'] ?? 'seeded');
    $forms_data = json_decode(stripslashes($_POST['rct_forms_data'] ?? '[]'), true);
    
    if (empty($rct_name)) {
        wp_die(__('El nombre de la aleatorización es obligatorio.', 'eipsi-forms'));
    }
    
    // Generate unique config ID
    $config_id = empty($rct_id) ? 'rct_' . wp_generate_password(8, false) . '_' . time() : $rct_id;
    
    // Validate total is 100%
    $total_prob = 0;
    foreach ($forms_data as $item) {
        $total_prob += floatval($item['probability'] ?? 0);
    }
    
    if (abs($total_prob - 100) > 0.01) {
        wp_die(__('La suma de probabilidades debe ser exactamente 100%.', 'eipsi-forms'));
    }
    
    // Prepare config data
    $config = array(
        'id' => $config_id,
        'name' => $rct_name,
        'description' => $rct_description,
        'method' => $method,
        'forms' => array(),
        'probabilities' => array(),
        'created_at' => current_time('mysql'),
        'updated_at' => current_time('mysql'),
        'is_active' => true
    );
    
    foreach ($forms_data as $item) {
        $config['forms'][] = intval($item['form_id']);
        $config['probabilities'][] = floatval($item['probability']);
    }
    
    // Save to database (using options for now - can be migrated to custom table later)
    $saved_configs = get_option('eipsi_randomization_configs', array());
    $saved_configs[$config_id] = $config;
    update_option('eipsi_randomization_configs', $saved_configs);
    
    // Auto-create WordPress page for this randomization
    $page_id = eipsi_create_randomization_page($config_id, $rct_name);
    $page_url = $page_id ? get_permalink($page_id) : null;
    
    // Redirect back with success message
    $redirect_url = admin_url('admin.php?page=eipsi-results-experience&tab=randomization&message=rct_' . (empty($rct_id) ? 'created' : 'updated'));
    if ($page_url) {
        $redirect_url .= '&page_url=' . urlencode($page_url);
    }
    
    wp_redirect($redirect_url);
    exit;
}
?>