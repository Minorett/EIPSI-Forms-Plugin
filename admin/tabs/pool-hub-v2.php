<?php
/**
 * Pool Hub v2.0 - Redesigned UI
 * Sub-tabs: Overview, Pools, Analytics
 * 
 * @package EIPSI_Forms
 * @since 2.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render the Pool Hub v2 content
 */
function eipsi_render_pool_hub_v2() {
    if (!function_exists('eipsi_user_can_manage_longitudinal') || !eipsi_user_can_manage_longitudinal()) {
        wp_die(__('Unauthorized', 'eipsi-forms'));
    }

    wp_enqueue_script('jquery');
    wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js', array(), '4.4.1', true);

    $nonce = wp_create_nonce('eipsi_pool_hub');
    $ajax_url = admin_url('admin-ajax.php');
    
    // Load available studies for pool creation
    global $wpdb;
    $studies_table = $wpdb->prefix . 'survey_studies';
    $available_studies = $wpdb->get_results( "SELECT id, study_name, study_code, status FROM {$studies_table} ORDER BY study_name ASC" );
    
    $message = isset($_GET['message']) ? sanitize_key($_GET['message']) : '';
    ?>

    <div class="wrap eipsi-pool-hub-v3">
        <?php if ($message) : ?>
            <div class="notice notice-success is-dismissible">
                <p><?php 
                    if ($message === 'pool_deleted') {
                        _e('Pool eliminado correctamente.', 'eipsi-forms');
                    } elseif ($message === 'pool_created') {
                        _e('Pool creado correctamente.', 'eipsi-forms');
                        $page_url = isset($_GET['page_url']) ? esc_url($_GET['page_url']) : '';
                        if ($page_url) {
                            echo '<br><strong>' . __('URL pública:', 'eipsi-forms') . '</strong> <a href="' . $page_url . '" target="_blank">' . $page_url . '</a>';
                        }
                    } elseif ($message === 'pool_updated') {
                        _e('Pool actualizado correctamente.', 'eipsi-forms');
                    }
                ?></p>
            </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="eipsi-pool-header">
            <div class="eipsi-pool-header-left">
                <h1><span class="dashicons dashicons-groups"></span> <?php _e('Pool Hub', 'eipsi-forms'); ?></h1>
                <p class="eipsi-pool-subtitle"><?php _e('Distribuye participantes entre estudios longitudinales', 'eipsi-forms'); ?></p>
            </div>
            <div class="eipsi-pool-header-actions">
                <button type="button" class="button button-primary" onclick="openPoolModalV3()">
                    <span class="dashicons dashicons-plus"></span> <?php _e('Nuevo pool', 'eipsi-forms'); ?>
                </button>
            </div>
        </div>

        <!-- Sub-tabs -->
        <div class="eipsi-sub-tabs">
            <a href="#" class="nav-tab nav-tab-active" data-subtab="pools" onclick="switchPoolSubtab('pools'); return false;">
                <span class="dashicons dashicons-list-view"></span> <?php _e('Pools', 'eipsi-forms'); ?>
            </a>
            <a href="#" class="nav-tab" data-subtab="analytics" onclick="switchPoolSubtab('analytics'); return false;">
                <span class="dashicons dashicons-chart-bar"></span> <?php _e('Analytics', 'eipsi-forms'); ?>
            </a>
        </div>

        <!-- Sub-tab: Pools -->
        <div class="eipsi-subtab-content" id="subtab-pools">
            <div class="eipsi-skeleton-table" id="eipsi-pools-skeleton">
                <div class="eipsi-skeleton" style="height: 40px; width: 100%; margin-bottom: 8px;"></div>
                <?php for ($i = 0; $i < 5; $i++) : ?>
                <div class="eipsi-skeleton" style="height: 50px; width: 100%; margin-bottom: 4px;"></div>
                <?php endfor; ?>
            </div>
            <div id="eipsi-pools-table" style="display: none;"></div>
        </div>

        <!-- Sub-tab: Analytics -->
        <div class="eipsi-subtab-content" id="subtab-analytics" style="display: none;">
            <div class="eipsi-analytics-header">
                <select id="eipsi-analytics-pool-select" onchange="loadPoolAnalytics(this.value)">
                    <option value=""><?php _e('Seleccionar pool...', 'eipsi-forms'); ?></option>
                </select>
                <button type="button" class="button" onclick="exportPoolCSV()" id="eipsi-export-csv-btn" disabled>
                    <span class="dashicons dashicons-download"></span> <?php _e('Exportar CSV', 'eipsi-forms'); ?>
                </button>
            </div>
            <div id="eipsi-analytics-empty" class="eipsi-empty-state">
                <div class="eipsi-empty-icon">📊</div>
                <h3><?php _e('Seleccioná un pool para ver analytics', 'eipsi-forms'); ?></h3>
            </div>
            <div id="eipsi-analytics-content" style="display: none;">
                <div class="eipsi-metrics-grid" id="eipsi-analytics-metrics"></div>
                <div class="eipsi-chart-container">
                    <canvas id="eipsi-pool-chart"></canvas>
                </div>
                <div class="eipsi-table-container" id="eipsi-analytics-table"></div>
                <div class="eipsi-activity-section" id="eipsi-analytics-activity"></div>
            </div>
        </div>
    </div>

    <!-- Modal: Create/Edit Pool -->
    <div class="eipsi-modal-overlay" id="eipsi-pool-modal-v3" style="display: none;">
        <div class="eipsi-modal-pool">
            <div class="eipsi-modal-header">
                <h2 id="eipsi-pool-modal-title"><?php _e('Crear nuevo pool', 'eipsi-forms'); ?></h2>
                <button class="eipsi-modal-close" onclick="closePoolModalV3()">&times;</button>
            </div>
            <div class="eipsi-modal-body">
                <form id="eipsi-pool-form-v3" method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                    <input type="hidden" name="action" value="eipsi_save_pool">
                    <input type="hidden" name="pool_id" id="eipsi-pool-id-v3" value="0">
                    <?php wp_nonce_field('eipsi_save_pool_nonce', 'pool_nonce'); ?>

                    <div class="eipsi-form-section">
                        <h3 class="eipsi-section-title"><?php _e('Información básica', 'eipsi-forms'); ?></h3>
                        <div class="eipsi-form-field">
                            <label for="eipsi-pool-name-v3"><?php _e('Nombre del pool', 'eipsi-forms'); ?> *</label>
                            <input type="text" name="pool_name" id="eipsi-pool-name-v3" required>
                        </div>
                        <div class="eipsi-form-field">
                            <label for="eipsi-pool-description-v3"><?php _e('Descripción para participantes', 'eipsi-forms'); ?> *</label>
                            <textarea name="pool_description" id="eipsi-pool-description-v3" rows="3" required placeholder="<?php esc_attr_e('Ej: Estamos comparando diferentes técnicas de intervención para ansiedad...', 'eipsi-forms'); ?>"></textarea>
                            <p class="description"><?php _e('Este texto se mostrará a los participantes en la página de acceso al pool.', 'eipsi-forms'); ?></p>
                        </div>
                        <div class="eipsi-form-field">
                            <label for="eipsi-pool-incentive-v3"><?php _e('Mensaje de incentivo (opcional)', 'eipsi-forms'); ?></label>
                            <textarea name="pool_incentive" id="eipsi-pool-incentive-v3" rows="2" placeholder="<?php esc_attr_e('Ej: Sorteo de 5 gift cards de $50 entre todos los participantes que completen el estudio', 'eipsi-forms'); ?>"></textarea>
                            <p class="description"><?php _e('Si hay algún incentivo por participar, describilo aquí. Se mostrará en la página de acceso.', 'eipsi-forms'); ?></p>
                        </div>
                        <div class="eipsi-form-field">
                            <label><?php _e('Método de asignación', 'eipsi-forms'); ?></label>
                            <div class="eipsi-radio-group">
                                <label class="eipsi-radio-label">
                                    <input type="radio" name="method" value="seeded" checked>
                                    <span class="eipsi-radio-text">
                                        <strong>🎲 Seeded</strong>
                                        <span class="eipsi-radio-desc"><?php _e('El participante siempre va al mismo estudio', 'eipsi-forms'); ?></span>
                                    </span>
                                </label>
                                <label class="eipsi-radio-label">
                                    <input type="radio" name="method" value="pure-random">
                                    <span class="eipsi-radio-text">
                                        <strong>🎰 Pure-random</strong>
                                        <span class="eipsi-radio-desc"><?php _e('Distribución aleatoria en cada acceso', 'eipsi-forms'); ?></span>
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="eipsi-form-section">
                        <h3 class="eipsi-section-title"><?php _e('Estudios y probabilidades', 'eipsi-forms'); ?></h3>
                        <div id="eipsi-pool-studies-rows-v3"></div>
                        <div class="eipsi-pool-actions">
                            <button type="button" class="eipsi-btn-secondary" onclick="addStudyRowV3()">
                                + <?php _e('Agregar estudio', 'eipsi-forms'); ?>
                            </button>
                            <button type="button" class="eipsi-btn-secondary" onclick="distributeProbabilitiesV3()">
                                🔀 <?php _e('Distribuir equitativamente', 'eipsi-forms'); ?>
                            </button>
                        </div>
                        <div class="eipsi-probability-total">
                            <div class="eipsi-progress-bar">
                                <div class="eipsi-progress-fill invalid" id="eipsi-progress-fill-v3" style="width: 0%"></div>
                            </div>
                            <div class="eipsi-progress-text invalid" id="eipsi-progress-text-v3">
                                <span id="eipsi-progress-percentage">0% / 100%</span>
                                <span id="eipsi-progress-status-v3">❌ <?php _e('Incompleto', 'eipsi-forms'); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="eipsi-form-section">
                        <details class="eipsi-collapsible">
                            <summary><?php _e('Configuración avanzada', 'eipsi-forms'); ?></summary>
                            <div class="eipsi-collapsible-content">
                                <div class="eipsi-form-field checkbox-field">
                                    <label>
                                        <input type="checkbox" name="allow_reassignment" value="1">
                                        <?php _e('Permitir reasignación si completa el estudio', 'eipsi-forms'); ?>
                                    </label>
                                </div>
                                <div class="eipsi-form-field checkbox-field">
                                    <label>
                                        <input type="checkbox" name="notify_on_completion" value="1">
                                        <?php _e('Notificarme por email cuando un participante complete', 'eipsi-forms'); ?>
                                    </label>
                                </div>
                                <div class="eipsi-form-field">
                                    <label><?php _e('Mensaje cuando el pool está pausado', 'eipsi-forms'); ?></label>
                                    <input type="text" name="paused_message" placeholder="<?php _e('Este pool está temporalmente pausado.', 'eipsi-forms'); ?>">
                                </div>
                            </div>
                        </details>
                    </div>

                    <input type="hidden" name="pool_studies_data" id="eipsi-pool-studies-data-v3" value="">
                </form>
            </div>
            <div class="eipsi-modal-footer">
                <button type="button" class="eipsi-btn-ghost" onclick="closePoolModalV3()"><?php _e('Cancelar', 'eipsi-forms'); ?></button>
                <button type="button" class="eipsi-btn-primary" id="eipsi-save-pool-btn-v3" disabled onclick="savePoolV3()">
                    <?php _e('Guardar pool', 'eipsi-forms'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Modal: Delete Confirmation -->
    <div class="eipsi-modal-overlay" id="eipsi-delete-modal-v3" style="display: none;">
        <div class="eipsi-modal-small">
            <div class="eipsi-modal-header">
                <h3>🗑️ <?php _e('¿Eliminar pool?', 'eipsi-forms'); ?></h3>
                <button class="eipsi-modal-close" onclick="closeDeleteModalV3()">&times;</button>
            </div>
            <div class="eipsi-modal-body">
                <p><?php _e('Estás por eliminar el pool:', 'eipsi-forms'); ?> <strong id="eipsi-delete-pool-name-v3"></strong></p>
                <div class="eipsi-notice-warning">
                    <p>⚠️ <?php _e('Los participantes ya asignados perderán su referencia. Esta acción no se puede deshacer.', 'eipsi-forms'); ?></p>
                </div>
            </div>
            <div class="eipsi-modal-footer">
                <button type="button" class="eipsi-btn-ghost" onclick="closeDeleteModalV3()"><?php _e('Cancelar', 'eipsi-forms'); ?></button>
                <a href="#" class="button button-link-delete" id="eipsi-confirm-delete-v3" onclick="executeDeletePoolV3(); return false;">
                    <?php _e('Eliminar permanentemente', 'eipsi-forms'); ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Modal: Pool Email Logs (v2.5.4) -->
    <div class="eipsi-modal-overlay" id="eipsi-pool-email-logs-modal" style="display: none;">
        <div class="eipsi-modal-pool">
            <div class="eipsi-modal-header">
                <h2>📧 <?php _e('Logs de Invitaciones por Email', 'eipsi-forms'); ?></h2>
                <button class="eipsi-modal-close" onclick="closeEmailLogsModal()">&times;</button>
            </div>
            <div class="eipsi-modal-body">
                <div id="eipsi-email-logs-table-container">
                    <p class="description"><?php _e('Cargando logs...', 'eipsi-forms'); ?></p>
                </div>
            </div>
            <div class="eipsi-modal-footer">
                <button type="button" class="eipsi-btn-ghost" onclick="closeEmailLogsModal()"><?php _e('Cerrar', 'eipsi-forms'); ?></button>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="eipsi-toast-container"></div>


    <style>
        /* ==========================================================================
           POOL HUB V3 - DASHBOARD STYLES (Fase 5)
           ========================================================================== */

        .eipsi-pool-hub-v3 {
            max-width: 1400px;
        }

        /* Header */
        .eipsi-pool-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e2e8f0;
        }

        .eipsi-pool-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .eipsi-pool-header h1 .dashicons {
            font-size: 28px;
            width: 28px;
            height: 28px;
            color: #3b82f6;
        }

        .eipsi-pool-subtitle {
            margin: 4px 0 0 0;
            color: #64748b;
            font-size: 14px;
        }

        .eipsi-pool-header-actions {
            display: flex;
            gap: 10px;
        }

        /* Sub-tabs */
        .eipsi-sub-tabs {
            margin-bottom: 20px;
            border-bottom: 1px solid #c3c4c7;
        }

        .eipsi-sub-tabs .nav-tab {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            font-size: 14px;
        }

        .eipsi-sub-tabs .nav-tab .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
        }

        /* Skeleton Loaders */
        .eipsi-skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: eipsi-pulse 1.5s infinite;
            border-radius: 4px;
        }

        @keyframes eipsi-pulse {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        .eipsi-skeleton-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px 0;
        }

        .eipsi-skeleton-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
        }

        .eipsi-skeleton-table {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
        }

        /* Overview Cards */
        .eipsi-overview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
            padding: 20px 0;
        }

        .eipsi-pool-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            transition: all 0.2s ease;
        }

        .eipsi-pool-card:hover {
            border-color: #3b82f6;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
        }

        .eipsi-pool-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .eipsi-pool-card-title {
            font-size: 16px;
            font-weight: 600;
            color: #1e293b;
            margin: 0;
        }

        .eipsi-status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .eipsi-status-active {
            background: #dcfce7;
            color: #166534;
        }

        .eipsi-status-paused {
            background: #fef3c7;
            color: #92400e;
        }

        .eipsi-pool-card-meta {
            display: flex;
            gap: 15px;
            font-size: 13px;
            color: #64748b;
        }

        .eipsi-balance-bar {
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
            display: flex;
        }

        .eipsi-balance-bar-fill {
            height: 100%;
            transition: width 0.3s ease;
        }

        .eipsi-balance-bar.warning {
            box-shadow: 0 0 0 2px #f59e0b;
        }

        .eipsi-completion-rate {
            font-size: 24px;
            font-weight: 700;
            color: #3b82f6;
        }

        .eipsi-pool-card-actions {
            display: flex;
            gap: 8px;
            margin-top: auto;
            padding-top: 12px;
            border-top: 1px solid #e2e8f0;
        }

        /* Empty State */
        .eipsi-empty-state {
            text-align: center;
            padding: 60px 20px;
            background: #f8fafc;
            border-radius: 8px;
            border: 2px dashed #e2e8f0;
        }

        .eipsi-empty-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }

        .eipsi-empty-state h3 {
            font-size: 18px;
            color: #1e293b;
            margin: 0 0 8px 0;
        }

        .eipsi-empty-state p {
            color: #64748b;
            margin: 0 0 20px 0;
        }

        /* Pools Table */
        .eipsi-pools-table {
            width: 100%;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
        }

        .eipsi-pools-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .eipsi-pools-table th {
            background: #f8fafc;
            padding: 12px 16px;
            text-align: left;
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            border-bottom: 1px solid #e2e8f0;
        }

        .eipsi-pools-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
        }

        .eipsi-pools-table tr:hover {
            background: #f8fafc;
        }

        /* Mini Balance Bar (Table) */
        .eipsi-mini-balance {
            height: 30px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
            display: flex;
            width: 150px;
        }

        .eipsi-mini-balance-segment {
            height: 100%;
            transition: width 0.3s ease;
        }

        /* Toggle Switch */
        .eipsi-toggle {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 24px;
        }

        .eipsi-toggle input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .eipsi-toggle-slider {
            position: absolute;
            cursor: pointer;
            inset: 0;
            background: #cbd5e1;
            border-radius: 24px;
            transition: 0.3s;
        }

        .eipsi-toggle input:checked + .eipsi-toggle-slider {
            background: #22c55e;
        }

        .eipsi-toggle-slider:before {
            content: '';
            position: absolute;
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background: white;
            border-radius: 50%;
            transition: 0.3s;
        }

        .eipsi-toggle input:checked + .eipsi-toggle-slider:before {
            transform: translateX(20px);
        }

        /* Analytics Header */
        .eipsi-analytics-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 16px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
        }

        .eipsi-analytics-header select {
            min-width: 300px;
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 14px;
        }

        /* Metrics Grid */
        .eipsi-metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .eipsi-metric-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }

        .eipsi-metric-value {
            font-size: 32px;
            font-weight: 700;
            color: #3b82f6;
            margin-bottom: 4px;
        }

        .eipsi-metric-label {
            font-size: 12px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 600;
        }

        /* Chart Container */
        .eipsi-chart-container {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 24px;
            height: 400px;
        }

        /* Table Container */
        .eipsi-table-container {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 24px;
            overflow-x: auto;
        }

        .eipsi-data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .eipsi-data-table th {
            background: #f8fafc;
            padding: 12px;
            text-align: left;
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            border-bottom: 1px solid #e2e8f0;
        }

        .eipsi-data-table td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
        }

        .eipsi-delta-good { color: #16a34a; font-weight: 600; }
        .eipsi-delta-warning { color: #f59e0b; font-weight: 600; }
        .eipsi-delta-bad { color: #dc2626; font-weight: 600; }

        /* Activity Section */
        .eipsi-activity-section {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
        }

        .eipsi-activity-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .eipsi-activity-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .eipsi-activity-item:last-child {
            border-bottom: none;
        }

        /* Modal Styles */
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

        .eipsi-modal-pool {
            background: #ffffff;
            border-radius: 12px;
            width: 100%;
            max-width: 680px;
            max-height: calc(100vh - 80px);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .eipsi-modal-small {
            background: #ffffff;
            border-radius: 12px;
            width: 100%;
            max-width: 480px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .eipsi-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 24px;
            border-bottom: 1px solid #e5e7eb;
            background: #f9fafb;
        }

        .eipsi-modal-header h2, .eipsi-modal-header h3 {
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
            width: 32px;
            height: 32px;
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
            padding: 24px;
            overflow-y: auto;
            max-height: 60vh;
        }

        .eipsi-modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            padding: 16px 24px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
        }

        /* Form Sections */
        .eipsi-form-section {
            margin-bottom: 24px;
        }

        .eipsi-form-section:last-child {
            margin-bottom: 0;
        }

        .eipsi-section-title {
            font-size: 14px;
            font-weight: 600;
            color: #1e293b;
            margin: 0 0 12px 0;
        }

        .eipsi-section-desc {
            font-size: 13px;
            color: #6b7280;
            margin: -8px 0 16px 0;
        }

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
        .eipsi-form-field input[type="number"],
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

        .eipsi-form-field.checkbox-field label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-weight: normal;
        }

        .eipsi-form-field.checkbox-field input {
            width: auto;
        }

        /* Radio Group */
        .eipsi-radio-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .eipsi-radio-label {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .eipsi-radio-label:hover {
            border-color: #3b82f6;
            background: #f8fafc;
        }

        .eipsi-radio-label input {
            margin-top: 2px;
        }

        .eipsi-radio-text {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .eipsi-radio-text strong {
            font-size: 14px;
            color: #1e293b;
        }

        .eipsi-radio-desc {
            font-size: 12px;
            color: #6b7280;
        }

        /* Form Rows (Studies) */
        .eipsi-form-row-v3 {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            margin-bottom: 8px;
        }

        .eipsi-form-row-v3 select {
            flex: 1;
            min-width: 200px;
            padding: 6px 8px;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            font-size: 13px;
        }

        .eipsi-form-row-v3 input[type="number"] {
            width: 80px;
            text-align: right;
            padding: 6px 8px;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            font-size: 13px;
        }

        .eipsi-remove-row-v3 {
            color: #dc2626;
            cursor: pointer;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            font-size: 18px;
            background: none;
            border: none;
        }

        .eipsi-remove-row-v3:hover {
            background: #fef2f2;
        }

        /* Collapsible Section */
        .eipsi-collapsible {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 16px;
        }

        .eipsi-collapsible summary {
            font-weight: 500;
            color: #374151;
            cursor: pointer;
            user-select: none;
        }

        .eipsi-collapsible-content {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #e2e8f0;
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

        /* Buttons */
        .eipsi-btn-primary {
            padding: 8px 16px;
            font-size: 14px;
            font-weight: 500;
            border: none;
            border-radius: 6px;
            background: #3b82f6;
            color: #ffffff;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .eipsi-btn-primary:hover:not(:disabled) {
            background: #2563eb;
        }

        .eipsi-btn-primary:disabled {
            background: #93c5fd;
            cursor: not-allowed;
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

        .eipsi-pool-actions {
            display: flex;
            gap: 8px;
            margin: 16px 0;
        }

        /* Notice */
        .eipsi-notice-warning {
            background: #fef3c7;
            border: 1px solid #fbbf24;
            border-radius: 6px;
            padding: 12px;
            margin-top: 12px;
        }

        .eipsi-notice-warning p {
            margin: 0;
            color: #92400e;
            font-size: 13px;
        }

        /* Pool Share Section */
        .eipsi-pool-share {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .eipsi-shortcode {
            display: inline-block;
            background: #f1f5f9;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-family: monospace;
            color: #475569;
            cursor: pointer;
            border: 1px dashed #cbd5e1;
            transition: all 0.2s;
        }

        .eipsi-shortcode:hover {
            background: #e2e8f0;
            border-color: #94a3b8;
        }

        .eipsi-page-link {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 12px;
            color: #3b82f6;
            text-decoration: none;
        }

        .eipsi-page-link:hover {
            color: #2563eb;
            text-decoration: underline;
        }

        .eipsi-no-page {
            font-size: 11px;
            color: #94a3b8;
            font-style: italic;
        }

        /* Email Badge */
        .eipsi-email-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: #f1f5f9;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            color: #475569;
            margin-left: 4px;
        }

        .eipsi-btn-email {
            display: inline-flex !important;
            align-items: center;
            gap: 4px;
        }

        .eipsi-email-logs-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .eipsi-email-logs-table th {
            text-align: left;
            padding: 10px;
            border-bottom: 2px solid #e2e8f0;
            font-size: 12px;
            text-transform: uppercase;
            color: #64748b;
        }

        .eipsi-email-logs-table td {
            padding: 10px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 14px;
        }

        .eipsi-status-tag {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
        }

        .eipsi-status-tag.status-confirmed { background: #dcfce7; color: #166534; }
        .eipsi-status-tag.status-pending { background: #fef3c7; color: #92400e; }
        .eipsi-status-tag.status-expired { background: #f1f5f9; color: #64748b; }

        .spin {
            animation: eipsi-spin 2s linear infinite;
            display: inline-block;
        }

        @keyframes eipsi-spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>


    <script>
        // Pool Hub V3 JavaScript
        const POOL_HUB_V3 = {
            ajaxUrl: '<?php echo $ajax_url; ?>',
            nonce: '<?php echo $nonce; ?>',
            restUrl: '<?php echo rest_url('eipsi/v1/'); ?>',
            pools: [],
            currentPoolId: null,
            chart: null,
            studiesData: <?php echo json_encode($available_studies ?: []); ?>,
            deletePoolId: null
        };

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadAllPoolsData();
        });

        // Switch Sub-tabs
        function switchPoolSubtab(tabName) {
            document.querySelectorAll('.eipsi-sub-tabs .nav-tab').forEach(tab => {
                tab.classList.remove('nav-tab-active');
                if (tab.dataset.subtab === tabName) {
                    tab.classList.add('nav-tab-active');
                }
            });

            // Hide all subtab contents
            document.querySelectorAll('.eipsi-subtab-content').forEach(content => {
                content.style.display = 'none';
            });

            // Show selected
            const selectedContent = document.getElementById('subtab-' + tabName);
            if (selectedContent) {
                selectedContent.style.display = 'block';
            }

            // Load data if needed
            if (tabName === 'analytics') {
                if (POOL_HUB_V3.pools.length > 0) {
                    loadPoolAnalytics(POOL_HUB_V3.pools[0].id);
                }
            }
        }

        // Load all pools data
        function loadAllPoolsData() {
            console.log('[EIPSI-JS-DEBUG] === loadAllPoolsData START ===');
            console.log('[EIPSI-JS-DEBUG] ajaxUrl:', POOL_HUB_V3.ajaxUrl);
            console.log('[EIPSI-JS-DEBUG] nonce:', POOL_HUB_V3.nonce ? 'SET (length: ' + POOL_HUB_V3.nonce.length + ')' : 'MISSING');
            
            jQuery.ajax({
                url: POOL_HUB_V3.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'eipsi_get_all_pools_summary',
                    nonce: POOL_HUB_V3.nonce
                },
                success: function(response) {
                    console.log('[EIPSI-JS-DEBUG] AJAX SUCCESS - response:', response);
                    if (response.success) {
                        POOL_HUB_V3.pools = response.data.pools || [];
                        console.log('[EIPSI-POOL-LOAD] Loaded ' + POOL_HUB_V3.pools.length + ' pools');
                        POOL_HUB_V3.pools.forEach(function(p) {
                            console.log('[EIPSI-POOL-LOAD] Pool #' + p.id + ': ' + p.name + ' (' + p.status + ')');
                        });
                        renderPoolsTable();
                        updatePoolSelector();
                    } else {
                        console.error('[EIPSI-JS-DEBUG] response.success is FALSE');
                        console.error('[EIPSI-JS-DEBUG] Error message:', response.data?.message);
                    }
                    console.log('[EIPSI-JS-DEBUG] === loadAllPoolsData END ===');
                },
                error: function(xhr, status, error) {
                    console.error('[EIPSI-JS-DEBUG] AJAX ERROR - status:', status);
                    console.error('[EIPSI-JS-DEBUG] AJAX ERROR - error:', error);
                    console.error('[EIPSI-JS-DEBUG] AJAX ERROR - responseText:', xhr.responseText);
                    console.error('[EIPSI-JS-DEBUG] AJAX ERROR - statusCode:', xhr.status);
                },
                complete: function() {
                    console.log('[EIPSI-JS-DEBUG] AJAX request completed');
                }
            });
        }

        // Render Pools Table
        function renderPoolsTable() {
            const container = document.getElementById('eipsi-pools-table');
            const skeleton = document.getElementById('eipsi-pools-skeleton');

            if (POOL_HUB_V3.pools.length === 0) {
                container.innerHTML = `
                    <div class="eipsi-empty-state">
                        <div class="eipsi-empty-icon">🏊</div>
                        <h3><?php _e('No hay pools creados', 'eipsi-forms'); ?></h3>
                    </div>
                `;
                skeleton.style.display = 'none';
                container.style.display = 'block';
                return;
            }

            const colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'];

            let html = `
                <div class="eipsi-pools-table">
                    <table>
                        <thead>
                            <tr>
                                <th><?php _e('Nombre', 'eipsi-forms'); ?></th>
                                <th><?php _e('Estudios', 'eipsi-forms'); ?></th>
                                <th><?php _e('Asignados', 'eipsi-forms'); ?></th>
                                <th><?php _e('Balance', 'eipsi-forms'); ?></th>
                                <th><?php _e('Completion', 'eipsi-forms'); ?></th>
                                <th><?php _e('Estado', 'eipsi-forms'); ?></th>
                                <th><?php _e('Compartir', 'eipsi-forms'); ?></th>
                                <th><?php _e('Acciones', 'eipsi-forms'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            POOL_HUB_V3.pools.forEach(function(pool) {
                const isChecked = pool.status === 'active' ? 'checked' : '';
                
                let balanceHtml = '<div class="eipsi-mini-balance">';
                if (pool.distribution && pool.distribution.length > 0) {
                    const total = pool.distribution.reduce((sum, d) => sum + (d.count || 0), 0);
                    pool.distribution.forEach(function(item, index) {
                        const pct = total > 0 ? ((item.count || 0) / total) * 100 : 0;
                        const color = colors[index % colors.length];
                        balanceHtml += `<div class="eipsi-mini-balance-segment" style="width: ${pct}%; background: ${color};"></div>`;
                    });
                }
                balanceHtml += '</div>';

                html += `
                    <tr data-pool-id="${pool.id}">
                        <td><strong>${escapeHtml(pool.name)}</strong></td>
                        <td>${pool.studies_count || 0}</td>
                        <td>${pool.total_assignments || 0}</td>
                        <td>${balanceHtml}</td>
                        <td>${pool.completion_rate || 0}%</td>
                        <td>
                            <label class="eipsi-toggle">
                                <input type="checkbox" ${isChecked} onchange="togglePoolStatusV3(${pool.id}, this.checked)">
                                <span class="eipsi-toggle-slider"></span>
                            </label>
                        </td>
                        <td>
                            <div class="eipsi-pool-share">
                                <code class="eipsi-shortcode" title="Click para copiar" onclick="copyToClipboardV3('[eipsi_pool pool_id=${pool.id}]')">[eipsi_pool pool_id=${pool.id}]</code>
                                ${pool.page_url ? `<code class="eipsi-shortcode" title="Click para copiar URL" onclick="copyToClipboardV3('${pool.page_url}')"><span class="dashicons dashicons-admin-page"></span> URL</code>` : '<span class="eipsi-no-page">Sin página</span>'}
                            </div>
                        </td>
                        <td>
                            <button type="button" class="button button-small" onclick="editPoolV3(${pool.id})" title="<?php esc_attr_e('Editar pool', 'eipsi-forms'); ?>">
                                <span class="dashicons dashicons-edit"></span>
                            </button>
                            <button type="button" class="button button-small eipsi-btn-email" onclick="openPoolEmailLogs(${pool.id})" title="<?php esc_attr_e('Ver logs de email', 'eipsi-forms'); ?>">
                                <span class="dashicons dashicons-email-alt"></span>
                                <span class="eipsi-email-badge">${pool.emails_confirmed || 0}/${pool.emails_sent || 0}</span>
                            </button>
                            <button type="button" class="button button-small" onclick="switchPoolSubtab('analytics'); loadPoolAnalytics(${pool.id});" title="<?php esc_attr_e('Ver analíticas', 'eipsi-forms'); ?>">
                                <span class="dashicons dashicons-chart-area"></span>
                            </button>
                            <button type="button" class="button button-small button-link-delete" onclick="confirmDeletePoolV3(${pool.id}, '${escapeHtml(pool.name)}')" title="<?php esc_attr_e('Eliminar pool', 'eipsi-forms'); ?>">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </td>
                    </tr>
                `;
            });

            html += '</tbody></table></div>';
            container.innerHTML = html;
            skeleton.style.display = 'none';
            container.style.display = 'block';
        }

        // Toggle Pool Status
        function togglePoolStatusV3(poolId, isActive) {
            jQuery.ajax({
                url: POOL_HUB_V3.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'eipsi_toggle_pool_status',
                    pool_id: poolId,
                    status: isActive ? 'active' : 'paused',
                    nonce: POOL_HUB_V3.nonce
                },
                success: function(response) {
                    if (response.success) {
                        loadAllPoolsData();
                    }
                }
            });
        }

        // Update Pool Selector for Analytics
        function updatePoolSelector() {
            const select = document.getElementById('eipsi-analytics-pool-select');
            select.innerHTML = '<option value=""><?php _e('Seleccionar pool...', 'eipsi-forms'); ?></option>';
            
            POOL_HUB_V3.pools.forEach(function(pool) {
                select.innerHTML += `<option value="${pool.id}">${escapeHtml(pool.name)}</option>`;
            });
        }

        // Load Pool Analytics
        function loadPoolAnalytics(poolId) {
            if (!poolId) return;
            
            POOL_HUB_V3.currentPoolId = poolId;
            document.getElementById('eipsi-analytics-empty').style.display = 'none';
            document.getElementById('eipsi-analytics-content').style.display = 'block';
            document.getElementById('eipsi-export-csv-btn').disabled = false;
            document.getElementById('eipsi-analytics-pool-select').value = poolId;

            jQuery.ajax({
                url: POOL_HUB_V3.ajaxUrl,
                type: 'GET',
                data: {
                    action: 'eipsi_get_pool_analytics',
                    pool_id: poolId,
                    nonce: POOL_HUB_V3.nonce
                },
                success: function(response) {
                    if (response.success) {
                        renderAnalytics(response.data);
                    }
                }
            });
        }

        // Render Analytics
        function renderAnalytics(data) {
            // Metrics
            const metrics = data.metrics;
            document.getElementById('eipsi-analytics-metrics').innerHTML = `
                <div class="eipsi-metric-card">
                    <div class="eipsi-metric-value">${metrics.total_assignments}</div>
                    <div class="eipsi-metric-label"><?php _e('Total Asignados', 'eipsi-forms'); ?></div>
                </div>
                <div class="eipsi-metric-card">
                    <div class="eipsi-metric-value">${metrics.completion_rate}%</div>
                    <div class="eipsi-metric-label"><?php _e('Completion Rate', 'eipsi-forms'); ?></div>
                </div>
                <div class="eipsi-metric-card">
                    <div class="eipsi-metric-value">${metrics.balance_score}</div>
                    <div class="eipsi-metric-label"><?php _e('Balance Score', 'eipsi-forms'); ?></div>
                </div>
                <div class="eipsi-metric-card">
                    <div class="eipsi-metric-value">${metrics.dropout_rate}%</div>
                    <div class="eipsi-metric-label"><?php _e('Dropout Rate', 'eipsi-forms'); ?></div>
                </div>
            `;

            // Chart
            renderChart(data.study_breakdown);

            // Table
            let tableHtml = `
                <table class="eipsi-data-table">
                    <thead>
                        <tr>
                            <th><?php _e('Estudio', 'eipsi-forms'); ?></th>
                            <th><?php _e('Asignados', 'eipsi-forms'); ?></th>
                            <th><?php _e('% Real', 'eipsi-forms'); ?></th>
                            <th><?php _e('% Esperado', 'eipsi-forms'); ?></th>
                            <th>Δ</th>
                            <th><?php _e('Completados', 'eipsi-forms'); ?></th>
                            <th><?php _e('En progreso', 'eipsi-forms'); ?></th>
                            <th><?php _e('Completion', 'eipsi-forms'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            data.study_breakdown.forEach(function(study) {
                const deltaClass = Math.abs(study.delta) < 5 ? 'eipsi-delta-good' : (Math.abs(study.delta) < 15 ? 'eipsi-delta-warning' : 'eipsi-delta-bad');
                tableHtml += `
                    <tr>
                        <td>${escapeHtml(study.study_name)}</td>
                        <td>${study.assigned}</td>
                        <td>${study.actual_pct}%</td>
                        <td>${study.expected_pct}%</td>
                        <td class="${deltaClass}">${study.delta > 0 ? '+' : ''}${study.delta}%</td>
                        <td>${study.completed}</td>
                        <td>${study.in_progress}</td>
                        <td>${study.completion_rate}%</td>
                    </tr>
                `;
            });

            tableHtml += '</tbody></table>';
            document.getElementById('eipsi-analytics-table').innerHTML = tableHtml;

            // Activity
            let activityHtml = '<h4><?php _e('Actividad reciente', 'eipsi-forms'); ?></h4><ul class="eipsi-activity-list">';
            data.recent_activity.forEach(function(item) {
                const statusBadge = item.status === 'completado' 
                    ? '<span style="color: #16a34a; font-weight: 600;">✓ <?php _e('Completado', 'eipsi-forms'); ?></span>'
                    : '<span style="color: #64748b;"><?php _e('Asignado', 'eipsi-forms'); ?></span>';
                
                activityHtml += `
                    <li class="eipsi-activity-item">
                        <div>
                            <strong>${escapeHtml(item.participant_email)}</strong>
                            <div style="font-size: 12px; color: #64748b;">${escapeHtml(item.study_name)}</div>
                        </div>
                        <div class="eipsi-activity-meta">
                            ${item.assigned_at} · ${statusBadge}
                        </div>
                    </li>
                `;
            });
            activityHtml += '</ul>';
            document.getElementById('eipsi-analytics-activity').innerHTML = activityHtml;
        }

        // Render Chart
        function renderChart(studyBreakdown) {
            const ctx = document.getElementById('eipsi-pool-chart').getContext('2d');
            
            if (POOL_HUB_V3.chart) {
                POOL_HUB_V3.chart.destroy();
            }

            const labels = studyBreakdown.map(s => s.study_name);
            const expected = studyBreakdown.map(s => s.expected_pct);
            const actual = studyBreakdown.map(s => s.actual_pct);

            POOL_HUB_V3.chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: '<?php _e('% Esperado', 'eipsi-forms'); ?>',
                            data: expected,
                            backgroundColor: '#3b82f6',
                            borderRadius: 4
                        },
                        {
                            label: '<?php _e('% Real', 'eipsi-forms'); ?>',
                            data: actual,
                            backgroundColor: '#10b981',
                            borderRadius: 4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });
        }

        // Export CSV
        function exportPoolCSV() {
            if (!POOL_HUB_V3.currentPoolId) return;
            window.location.href = POOL_HUB_V3.ajaxUrl + '?action=eipsi_export_pool_assignments&pool_id=' + POOL_HUB_V3.currentPoolId + '&nonce=' + POOL_HUB_V3.nonce;
        }

        // Modal Functions
        function openPoolModalV3(poolId = null) {
            const modal = document.getElementById('eipsi-pool-modal-v3');
            const title = document.getElementById('eipsi-pool-modal-title');
            
            if (poolId) {
                title.textContent = '<?php _e('Editar pool', 'eipsi-forms'); ?>';
                const pool = POOL_HUB_V3.pools.find(p => p.id === poolId);
                if (pool) {
                    document.getElementById('eipsi-pool-id-v3').value = pool.id;
                    document.getElementById('eipsi-pool-name-v3').value = pool.name || '';
                    document.getElementById('eipsi-pool-description-v3').value = pool.description || '';
                    
                    // Load studies
                    document.getElementById('eipsi-pool-studies-rows-v3').innerHTML = '';
                    if (pool.config && pool.config.studies) {
                        pool.config.studies.forEach(function(study) {
                            addStudyRowV3(study.study_id, study.probability);
                        });
                    }
                }
            } else {
                title.textContent = '<?php _e('Crear nuevo pool', 'eipsi-forms'); ?>';
                document.getElementById('eipsi-pool-form-v3').reset();
                document.getElementById('eipsi-pool-id-v3').value = '0';
                document.getElementById('eipsi-pool-studies-rows-v3').innerHTML = '';
                // Add two empty study rows for new pool
                addStudyRowV3('', '');
                addStudyRowV3('', '');
            }

            modal.style.display = 'flex';
            updateProbabilityTotalV3();
        }

        function closePoolModalV3() {
            document.getElementById('eipsi-pool-modal-v3').style.display = 'none';
        }

        function closeDeleteModalV3() {
            document.getElementById('eipsi-delete-modal-v3').style.display = 'none';
        }

        // Email Logs Functions (v2.5.4)
        function openPoolEmailLogs(poolId) {
            const modal = document.getElementById('eipsi-pool-email-logs-modal');
            const container = document.getElementById('eipsi-email-logs-table-container');
            
            modal.style.display = 'flex';
            container.innerHTML = '<p class="description"><?php _e('Cargando logs...', 'eipsi-forms'); ?></p>';

            jQuery.ajax({
                url: POOL_HUB_V3.ajaxUrl,
                type: 'GET',
                data: {
                    action: 'eipsi_get_pool_email_logs',
                    pool_id: poolId,
                    nonce: POOL_HUB_V3.nonce
                },
                success: function(response) {
                    if (response.success && response.data.logs) {
                        renderEmailLogs(poolId, response.data.logs);
                    } else {
                        container.innerHTML = '<p class="description"><?php _e('No hay logs registrados para este pool.', 'eipsi-forms'); ?></p>';
                    }
                },
                error: function() {
                    container.innerHTML = '<p class="description" style="color:red;"><?php _e('Error al cargar los logs.', 'eipsi-forms'); ?></p>';
                }
            });
        }

        function renderEmailLogs(poolId, logs) {
            const container = document.getElementById('eipsi-email-logs-table-container');
            
            if (logs.length === 0) {
                container.innerHTML = '<p class="description"><?php _e('No hay envíos registrados.', 'eipsi-forms'); ?></p>';
                return;
            }

            let html = `
                <table class="eipsi-email-logs-table">
                    <thead>
                        <tr>
                            <th><?php _e('Email', 'eipsi-forms'); ?></th>
                            <th><?php _e('Estado', 'eipsi-forms'); ?></th>
                            <th><?php _e('Acción', 'eipsi-forms'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            logs.forEach(function(log) {
                let actionBtn = '';
                if (log.status === 'pending' || log.status === 'expired') {
                    actionBtn = `
                        <button type="button" class="button button-small" onclick="resendPoolEmail(${log.participant_id}, '${log.email}', ${poolId}, this)" title="<?php esc_attr_e('Reenviar email', 'eipsi-forms'); ?>">
                            <span class="dashicons dashicons-email-alt"></span>
                        </button>
                    `;
                }

                html += `
                    <tr>
                        <td>${escapeHtml(log.email)}</td>
                        <td><span class="eipsi-status-tag status-${log.status}">${log.status_label}</span></td>
                        <td>${actionBtn}</td>
                    </tr>
                `;
            });

            html += '</tbody></table>';
            container.innerHTML = html;
        }

        function closeEmailLogsModal() {
            document.getElementById('eipsi-pool-email-logs-modal').style.display = 'none';
        }

        function resendPoolEmail(participantId, email, poolId, btn) {
            if (!confirm('<?php _e('¿Estás seguro de que quieres reenviar el email de confirmación?', 'eipsi-forms'); ?>')) {
                return;
            }

            const originalHtml = btn ? btn.innerHTML : '';
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="dashicons dashicons-update spin"></span>';
            }

            jQuery.ajax({
                url: POOL_HUB_V3.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'eipsi_resend_pool_confirmation',
                    pool_id: poolId,
                    email: email,
                    participant_id: participantId,
                    nonce: POOL_HUB_V3.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showToastV3('📧 ' + response.data.message);
                        openPoolEmailLogs(poolId); // Refresh modal table
                        loadAllPoolsData(); // Update main table counts
                    } else {
                        showToastV3('❌ ' + response.data.message);
                        if (btn) {
                            btn.disabled = false;
                            btn.innerHTML = originalHtml;
                        }
                    }
                },
                error: function(xhr) {
                    let msg = '<?php _e('Error de conexión.', 'eipsi-forms'); ?>';
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        msg = xhr.responseJSON.data.message;
                    }
                    showToastV3('❌ ' + msg);
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = originalHtml;
                    }
                }
            });
        }

        // Load Studies for Select via REST
        function loadStudiesForSelect() {
            jQuery.ajax({
                url: POOL_HUB_V3.restUrl + 'pool-detect',
                type: 'GET',
                beforeSend: function(xhr) {
                    if (window.wpApiSettings && window.wpApiSettings.nonce) {
                        xhr.setRequestHeader('X-WP-Nonce', window.wpApiSettings.nonce);
                    }
                },
                success: function(response) {
                    if (response.valid) {
                        POOL_HUB_V3.studiesData = response.valid;
                    }
                }
            });
        }

        // Add Study Row
        function addStudyRowV3(studyId = '', probability = '') {
            const container = document.getElementById('eipsi-pool-studies-rows-v3');
            const rowCount = container.children.length;

            let optionsHtml = '<option value=""><?php _e('Seleccionar estudio...', 'eipsi-forms'); ?></option>';
            
            if (POOL_HUB_V3.studiesData.length === 0) {
                optionsHtml = '<option value=""><?php _e('No hay estudios disponibles', 'eipsi-forms'); ?></option>';
            } else {
                POOL_HUB_V3.studiesData.forEach(function(study) {
                    const selected = study.id == studyId ? 'selected' : '';
                    optionsHtml += `<option value="${study.id}" ${selected}>${study.study_name} (${study.study_code})</option>`;
                });
            }

            const row = document.createElement('div');
            row.className = 'eipsi-form-row-v3';
            row.innerHTML = `
                <select name="study_id[]" required>${optionsHtml}</select>
                <input type="number" name="probability[]" value="${probability}" min="0" max="100" step="0.01" required onchange="updateProbabilityTotalV3()">%
                <button type="button" class="eipsi-remove-row-v3" onclick="this.parentElement.remove(); updateProbabilityTotalV3();">&times;</button>
            `;
            container.appendChild(row);

            // Auto-distribute if first row
            if (rowCount === 0 && !probability) {
                row.querySelector('input').value = '100';
                updateProbabilityTotalV3();
            } else if (rowCount === 1 && !probability) {
                distributeProbabilitiesV3();
            } else if (!probability) {
                distributeProbabilitiesV3();
            }
        }

        // Distribute Probabilities Equally
        function distributeProbabilitiesV3() {
            const rows = document.querySelectorAll('#eipsi-pool-studies-rows-v3 .eipsi-form-row-v3');
            const count = rows.length;
            if (count === 0) return;

            const baseValue = Math.floor((100 / count) * 100) / 100;
            const remainder = 100 - (baseValue * count);

            rows.forEach(function(row, index) {
                const input = row.querySelector('input[name="probability[]"]');
                input.value = index === 0 ? (baseValue + remainder).toFixed(2) : baseValue.toFixed(2);
            });

            updateProbabilityTotalV3();
        }

        // Update Probability Total
        function updateProbabilityTotalV3() {
            const inputs = document.querySelectorAll('#eipsi-pool-studies-rows-v3 input[name="probability[]"]');
            let total = 0;
            inputs.forEach(function(input) {
                total += parseFloat(input.value) || 0;
            });

            const fill = document.getElementById('eipsi-progress-fill-v3');
            const text = document.getElementById('eipsi-progress-text-v3');
            const status = document.getElementById('eipsi-progress-status-v3');
            const saveBtn = document.getElementById('eipsi-save-pool-btn-v3');

            fill.style.width = Math.min(total, 100) + '%';

            // Allow ±0.1% tolerance for floating point precision (e.g., 33.33+33.33+33.33=99.99)
            if (total >= 99.9 && total <= 100.1) {
                fill.classList.remove('invalid');
                fill.classList.add('valid');
                text.classList.remove('invalid');
                text.classList.add('valid');
                status.textContent = '✅ <?php _e('Completo', 'eipsi-forms'); ?>';
                saveBtn.disabled = false;
            } else {
                fill.classList.remove('valid');
                fill.classList.add('invalid');
                text.classList.remove('valid');
                text.classList.add('invalid');
                status.textContent = total < 100 ? '❌ <?php _e('Incompleto', 'eipsi-forms'); ?>' : '❌ <?php _e('Excedido', 'eipsi-forms'); ?>';
                saveBtn.disabled = true;
            }

            document.getElementById('eipsi-progress-percentage').textContent = total.toFixed(2) + '% / 100%';
        }

        // Save Pool
        function savePoolV3() {
            const form = document.getElementById('eipsi-pool-form-v3');
            const rows = document.querySelectorAll('#eipsi-pool-studies-rows-v3 .eipsi-form-row-v3');

            const studiesData = [];
            rows.forEach(function(row) {
                const studyId = row.querySelector('select').value;
                const probability = row.querySelector('input').value;
                if (studyId) {
                    studiesData.push({
                        study_id: parseInt(studyId),
                        probability: parseFloat(probability)
                    });
                }
            });

            document.getElementById('eipsi-pool-studies-data-v3').value = JSON.stringify(studiesData);
            form.submit();
        }

        // Edit Pool
        function editPoolV3(poolId) {
            openPoolModalV3(poolId);
        }

        // Delete Pool
        function confirmDeletePoolV3(poolId, poolName) {
            POOL_HUB_V3.deletePoolId = poolId;
            document.getElementById('eipsi-delete-pool-name-v3').textContent = poolName || 'Pool #' + poolId;
            document.getElementById('eipsi-delete-modal-v3').style.display = 'flex';
        }

        function executeDeletePoolV3() {
            if (!POOL_HUB_V3.deletePoolId) {
                console.log('[EIPSI-POOL-DELETE] No pool ID set for deletion');
                return;
            }
            
            // Use generic delete nonce
            const nonce = '<?php echo wp_create_nonce('eipsi_delete_pool'); ?>';
            const url = '<?php echo admin_url('admin.php?page=eipsi-longitudinal-study&tab=pool-hub&action=delete&pool_id='); ?>' + POOL_HUB_V3.deletePoolId + '&_wpnonce=' + nonce;
            
            console.log('[EIPSI-POOL-DELETE] Attempting to delete pool ID:', POOL_HUB_V3.deletePoolId);
            console.log('[EIPSI-POOL-DELETE] URL:', url);
            
            window.location.href = url;
        }

        // Utility
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

        // Copy to clipboard
        function copyToClipboardV3(text) {
            navigator.clipboard.writeText(text).then(function() {
                showToastV3('✅ Copiado al portapapeles');
            }).catch(function(err) {
                // Fallback for older browsers
                const textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                textarea.select();
                try {
                    document.execCommand('copy');
                    showToastV3('✅ Copiado al portapapeles');
                } catch (e) {
                    showToastV3('❌ Error al copiar');
                }
                document.body.removeChild(textarea);
            });
        }

        // Show toast notification
        function showToastV3(message) {
            const container = document.getElementById('eipsi-toast-container');
            const toast = document.createElement('div');
            toast.style.cssText = 'background:#1e293b;color:#fff;padding:12px 20px;border-radius:8px;margin-bottom:8px;font-size:14px;animation:fadeIn 0.3s;box-shadow:0 4px 12px rgba(0,0,0,0.15);';
            toast.textContent = message;
            container.appendChild(toast);
            setTimeout(function() {
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 0.3s';
                setTimeout(function() { toast.remove(); }, 300);
            }, 2000);
        }
    </script>

    </div><!-- End wrap -->

<?php } // End function eipsi_render_pool_hub_v2

/**
 * Get pool statistics
 */
function eipsi_get_pool_stats($pool_id) {
    global $wpdb;
    $assignments_table = $wpdb->prefix . 'eipsi_pool_assignments';
    
    $total = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$assignments_table} WHERE pool_id = %d",
        $pool_id
    ));
    
    $completed = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$assignments_table} WHERE pool_id = %d AND status = 'completed'",
        $pool_id
    ));
    
    $rate = $total > 0 ? round(($completed / $total) * 100, 1) : 0;
    
    return array(
        'total' => (int) $total,
        'completed' => (int) $completed,
        'rate' => $rate
    );
}

/**
 * Get the pool page URL
 *
 * @param int $pool_id Pool ID
 * @return string|null Page URL or null if no page exists
 * @since 2.5.0
 */
function eipsi_get_pool_page_url($pool_id) {
    $pages = get_posts(array(
        'post_type' => 'page',
        'meta_key' => 'eipsi_pool_id',
        'meta_value' => $pool_id,
        'posts_per_page' => 1
    ));
    
    if (!empty($pages)) {
        return get_permalink($pages[0]->ID);
    }
    
    return null;
}
