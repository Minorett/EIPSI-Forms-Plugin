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

    // Ensure jQuery is loaded
    wp_enqueue_script('jquery');

    global $wpdb;

    $pools_table = $wpdb->prefix . 'eipsi_longitudinal_pools';
    $assignments_table = $wpdb->prefix . 'eipsi_longitudinal_pool_assignments';

    // Get all pools
    $pools = $wpdb->get_results("SELECT * FROM {$pools_table} ORDER BY created_at DESC", ARRAY_A);

    // Get global stats
    $total_pools = count($pools);
    $active_pools = array_filter($pools, function($p) { return $p['status'] === 'active'; });
    $total_assignments = $wpdb->get_var("SELECT COUNT(*) FROM {$assignments_table}");
    $total_completed = $wpdb->get_var("SELECT COUNT(*) FROM {$assignments_table} WHERE status = 'completed'");
    $completion_rate = $total_assignments > 0 ? round(($total_completed / $total_assignments) * 100, 1) : 0;

    // Get recent activity
    $recent_activity = $wpdb->get_results(
        "SELECT a.*, p.pool_name 
         FROM {$assignments_table} a 
         LEFT JOIN {$pools_table} p ON a.pool_id = p.id 
         ORDER BY a.assigned_at DESC 
         LIMIT 10",
        ARRAY_A
    );

    // Check for messages
    $message = isset($_GET['message']) ? sanitize_key($_GET['message']) : '';
    
    // v2.5.2 - Cargar estudios disponibles para el modal de crear/editar pool
    $all_studies = $wpdb->get_results(
        "SELECT id, study_name, study_code FROM {$wpdb->prefix}survey_studies ORDER BY study_name",
        ARRAY_A
    );
    
    // Convertir estudios a formato JSON para JavaScript
    $studies_json = json_encode($all_studies);
    ?>
    
    <!-- Pasar estudios a JavaScript -->
    <script>
        window.eipsiPoolStudies = <?php echo $studies_json; ?>;
        console.log('[POOL-HUB-INIT] Estudios cargados:', window.eipsiPoolStudies.length);
    </script>

    <div class="wrap eipsi-pool-hub-v2">
        
        <?php if ($message === 'pool_deleted') : ?>
            <div class="notice notice-success is-dismissible">
                <p><?php _e('Pool eliminado correctamente.', 'eipsi-forms'); ?></p>
            </div>
        <?php elseif ($message === 'pool_created') : ?>
            <div class="notice notice-success is-dismissible">
                <p><?php _e('Pool creado correctamente.', 'eipsi-forms'); ?></p>
            </div>
        <?php elseif ($message === 'pool_updated') : ?>
            <div class="notice notice-success is-dismissible">
                <p><?php _e('Pool actualizado correctamente.', 'eipsi-forms'); ?></p>
            </div>
        <?php endif; ?>

        <?php if (empty($pools)) : ?>
            <!-- Empty State - No pools created yet (v2.2.3: EIPSI Design System) -->
            <div class="eipsi-pool-hub-page" style="padding: var(--eipsi-space-xl);">

                <!-- Page header -->
                <div style="margin-bottom: var(--eipsi-space-xl);">
                    <h1 style="font-size: 22px; font-weight: 500; color: var(--eipsi-text); margin: 0 0 4px 0;">Pool Hub</h1>
                    <p style="font-size: 14px; color: var(--eipsi-text-muted); margin: 0;"><?php _e('Distribuye participantes entre estudios longitudinales', 'eipsi-forms'); ?></p>
                </div>

                <!-- Empty state -->
                <div style="
                    background: var(--eipsi-bg-subtle);
                    border: 1.5px dashed var(--eipsi-border);
                    border-radius: var(--eipsi-radius-lg);
                    padding: var(--eipsi-space-xl) var(--eipsi-space-xl);
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    text-align: center;
                    min-height: 340px;
                ">

                    <!-- Icon container -->
                    <div style="
                        width: 56px;
                        height: 56px;
                        background: var(--eipsi-bg-muted);
                        border: 1px solid var(--eipsi-border);
                        border-radius: var(--eipsi-radius-lg);
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        margin-bottom: var(--eipsi-space-lg);
                    ">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--eipsi-primary)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="8" x2="12" y2="16"/>
                            <line x1="8" y1="12" x2="16" y2="12"/>
                        </svg>
                    </div>

                    <!-- Title -->
                    <h2 style="
                        font-size: 18px;
                        font-weight: 500;
                        color: var(--eipsi-text);
                        margin: 0 0 var(--eipsi-space-sm) 0;
                    "><?php _e('Ningún pool creado todavía', 'eipsi-forms'); ?></h2>

                    <!-- Description -->
                    <p style="
                        font-size: 14px;
                        color: var(--eipsi-text-muted);
                        max-width: 380px;
                        line-height: 1.6;
                        margin: 0 0 var(--eipsi-space-lg) 0;
                    "><?php _e('Los pools distribuyen participantes entre estudios longitudinales según probabilidades configurables.', 'eipsi-forms'); ?></p>

                    <!-- CTA button -->
                    <button
                        class="button button-primary eipsi-create-pool-btn"
                        data-open-modal="create"
                        onclick="openEipsiPoolModal();"
                        style="
                            background: var(--eipsi-primary);
                            color: #ffffff;
                            border: none;
                            border-radius: var(--eipsi-radius);
                            padding: 10px 24px;
                            font-size: 14px;
                            font-weight: 500;
                            cursor: pointer;
                            box-shadow: var(--eipsi-shadow-sm);
                            transition: background 0.15s ease;
                        "
                        onmouseover="this.style.background='var(--eipsi-primary-hover)'"
                        onmouseout="this.style.background='var(--eipsi-primary)'"
                    >
                        + <?php _e('Crear primer pool', 'eipsi-forms'); ?>
                    </button>

                </div>
            </div>
        <?php else : ?>
            <h1>🏊 Pool Hub</h1>
            
            <!-- Sub-tabs -->
            <h2 class="nav-tab-wrapper eipsi-sub-tabs">
                <a href="#overview" class="nav-tab nav-tab-active" data-subtab="overview">
                    📊 <?php _e('Overview', 'eipsi-forms'); ?>
                </a>
                <a href="#pools" class="nav-tab" data-subtab="pools">
                    🏊 <?php _e('Pools', 'eipsi-forms'); ?> 
                    <span class="eipsi-badge"><?php echo $total_pools; ?></span>
                </a>
                <a href="#analytics" class="nav-tab" data-subtab="analytics">
                    📈 <?php _e('Analytics', 'eipsi-forms'); ?>
                </a>
            </h2>

        <!-- Overview Tab -->
        <div class="eipsi-subtab-content" id="subtab-overview">
            <div class="eipsi-kpi-grid">
                <div class="eipsi-kpi-card">
                    <div class="eipsi-kpi-icon">🏊</div>
                    <div class="eipsi-kpi-content">
                        <span class="eipsi-kpi-value"><?php echo $total_pools; ?></span>
                        <span class="eipsi-kpi-label"><?php _e('Pools totales', 'eipsi-forms'); ?></span>
                        <span class="eipsi-kpi-detail"><?php echo count($active_pools); ?> activos</span>
                    </div>
                </div>
                <div class="eipsi-kpi-card">
                    <div class="eipsi-kpi-icon">👥</div>
                    <div class="eipsi-kpi-content">
                        <span class="eipsi-kpi-value"><?php echo $total_assignments; ?></span>
                        <span class="eipsi-kpi-label"><?php _e('Asignaciones', 'eipsi-forms'); ?></span>
                        <span class="eipsi-kpi-detail"><?php echo $total_completed; ?> completadas</span>
                    </div>
                </div>
                <div class="eipsi-kpi-card">
                    <div class="eipsi-kpi-icon">✅</div>
                    <div class="eipsi-kpi-content">
                        <span class="eipsi-kpi-value"><?php echo $completion_rate; ?>%</span>
                        <span class="eipsi-kpi-label"><?php _e('Completion Rate', 'eipsi-forms'); ?></span>
                        <span class="eipsi-kpi-detail">Promedio global</span>
                    </div>
                </div>
                <div class="eipsi-kpi-card">
                    <div class="eipsi-kpi-icon">⚡</div>
                    <div class="eipsi-kpi-content">
                        <span class="eipsi-kpi-value"><?php echo count($active_pools); ?></span>
                        <span class="eipsi-kpi-label"><?php _e('Pools activos', 'eipsi-forms'); ?></span>
                        <span class="eipsi-kpi-detail">Aceptando participantes</span>
                    </div>
                </div>
            </div>

            <!-- Pool Summary Cards -->
            <div class="eipsi-overview-header">
                <h2><?php _e('Resumen de Pools', 'eipsi-forms'); ?></h2>
                <button class="button button-primary eipsi-create-pool-btn" data-open-modal="create" onclick="openEipsiPoolModal();">
                    + <?php _e('Nuevo pool', 'eipsi-forms'); ?>
                </button>
            </div>
            
            <?php if (empty($pools)) : ?>
                <div class="eipsi-empty-state">
                    <div class="eipsi-empty-icon">🏊</div>
                    <h3><?php _e('No hay pools aún', 'eipsi-forms'); ?></h3>
                    <p><?php _e('Crea tu primer pool para comenzar a asignar participantes.', 'eipsi-forms'); ?></p>
                    <button class="button button-primary eipsi-create-pool-btn" data-open-modal="create" onclick="openEipsiPoolModal();">
                        <?php _e('Crear primer pool', 'eipsi-forms'); ?>
                    </button>
                </div>
            <?php else : ?>
                <div class="eipsi-pool-summary-grid">
                    <?php foreach ($pools as $pool) : 
                        $pool_stats = eipsi_get_pool_stats($pool['id']);
                        $studies = json_decode($pool['studies'], true);
                        $probabilities = json_decode($pool['probabilities'], true);
                        $study_count = is_array($studies) ? count($studies) : 0;
                        $is_active = $pool['status'] === 'active';
                        
                        // Calculate expected vs actual balance
                        $balance_diff = 0;
                        if ($study_count > 0 && $pool_stats['total'] > 0 && is_array($probabilities)) {
                            // Simple balance calculation - deviation from perfect distribution
                            $balance_diff = min(100, abs(100 - ($pool_stats['completed'] / max(1, $pool_stats['total']) * 100)));
                        }
                    ?>
                        <div class="eipsi-pool-summary-card <?php echo $is_active ? 'is-active' : 'is-paused'; ?>">
                            <div class="eipsi-pool-summary-header">
                                <h3><?php echo esc_html($pool['pool_name']); ?></h3>
                                <span class="eipsi-status-badge status-<?php echo esc_attr($pool['status']); ?>">
                                    <?php echo $is_active ? __('Activo', 'eipsi-forms') : __('Pausado', 'eipsi-forms'); ?>
                                </span>
                            </div>
                            <div class="eipsi-pool-summary-body">
                                <div class="eipsi-pool-summary-metric">
                                    <span class="eipsi-metric-value"><?php echo $study_count; ?></span>
                                    <span class="eipsi-metric-label"><?php _e('Estudios', 'eipsi-forms'); ?></span>
                                </div>
                                <div class="eipsi-pool-summary-metric">
                                    <span class="eipsi-metric-value"><?php echo $pool_stats['total']; ?></span>
                                    <span class="eipsi-metric-label"><?php _e('Asignaciones', 'eipsi-forms'); ?></span>
                                </div>
                                <div class="eipsi-pool-summary-metric">
                                    <span class="eipsi-metric-value"><?php echo $pool_stats['rate']; ?>%</span>
                                    <span class="eipsi-metric-label"><?php _e('Completados', 'eipsi-forms'); ?></span>
                                </div>
                            </div>
                            <div class="eipsi-pool-summary-balance">
                                <div class="eipsi-balance-bar-container">
                                    <div class="eipsi-balance-bar" style="width: <?php echo min(100, max(0, 100 - $balance_diff)); ?>%"></div>
                                </div>
                                <span class="eipsi-balance-label"><?php _e('Balance', 'eipsi-forms'); ?></span>
                            </div>
                            <div class="eipsi-pool-summary-actions">
                                <button class="button button-small eipsi-view-analytics" data-pool-id="<?php echo $pool['id']; ?>">
                                    📊 <?php _e('Analytics', 'eipsi-forms'); ?>
                                </button>
                                <button class="button button-small eipsi-edit-pool" data-pool-id="<?php echo $pool['id']; ?>">
                                    ✏️ <?php _e('Editar', 'eipsi-forms'); ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pools Tab -->
        <div class="eipsi-subtab-content" id="subtab-pools" style="display: none;">
            <div class="eipsi-pools-header">
                <h2><?php _e('Gestión de Pools', 'eipsi-forms'); ?></h2>
                <button class="button button-primary eipsi-create-pool-btn" data-open-modal="create" onclick="openEipsiPoolModal();">
                    + <?php _e('Nuevo Pool', 'eipsi-forms'); ?>
                </button>
            </div>

            <?php if (empty($pools)) : ?>
                <div class="eipsi-empty-state">
                    <div class="eipsi-empty-icon">🏊</div>
                    <h3><?php _e('No hay pools creados', 'eipsi-forms'); ?></h3>
                    <p><?php _e('Los pools te permiten distribuir participantes entre múltiples estudios con probabilidades configurables.', 'eipsi-forms'); ?></p>
                    <button class="button button-primary eipsi-create-pool-btn" data-open-modal="create" onclick="openEipsiPoolModal();">
                        <?php _e('Crear mi primer pool', 'eipsi-forms'); ?>
                    </button>
                </div>
            <?php else : ?>
                <div class="eipsi-pools-table-container">
                    <table class="eipsi-pools-table wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Pool', 'eipsi-forms'); ?></th>
                                <th><?php _e('Estudios', 'eipsi-forms'); ?></th>
                                <th><?php _e('Asignaciones', 'eipsi-forms'); ?></th>
                                <th><?php _e('Balance', 'eipsi-forms'); ?></th>
                                <th><?php _e('Estado', 'eipsi-forms'); ?></th>
                                <th class="eipsi-actions-col"><?php _e('Acciones', 'eipsi-forms'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pools as $pool) : 
                                $pool_stats = eipsi_get_pool_stats($pool['id']);
                                $studies = json_decode($pool['studies'], true);
                                $probabilities = json_decode($pool['probabilities'], true);
                                $study_count = is_array($studies) ? count($studies) : 0;
                                $is_active = $pool['status'] === 'active';
                                
                                // Calculate balance
                                $balance_diff = 0;
                                if ($study_count > 0 && $pool_stats['total'] > 0) {
                                    $balance_diff = min(100, abs(100 - ($pool_stats['completed'] / max(1, $pool_stats['total']) * 100)));
                                }
                                $balance_pct = min(100, max(0, 100 - $balance_diff));
                            ?>
                                <tr class="eipsi-pool-row <?php echo $is_active ? 'is-active' : 'is-paused'; ?>">
                                    <td class="eipsi-pool-name-col">
                                        <strong><?php echo esc_html($pool['pool_name']); ?></strong>
                                        <div class="eipsi-row-meta">
                                            <?php echo $pool['method'] === 'seeded' ? '🎲 ' . __('Seeded', 'eipsi-forms') : '🎰 ' . __('Random', 'eipsi-forms'); ?>
                                        </div>
                                    </td>
                                    <td class="eipsi-pool-studies-col">
                                        <?php echo $study_count; ?> estudios
                                    </td>
                                    <td class="eipsi-pool-assignments-col">
                                        <strong><?php echo $pool_stats['total']; ?></strong>
                                        <div class="eipsi-row-meta">
                                            <?php echo $pool_stats['completed']; ?> completados (<?php echo $pool_stats['rate']; ?>%)
                                        </div>
                                    </td>
                                    <td class="eipsi-pool-balance-col">
                                        <div class="eipsi-balance-mini-bar">
                                            <div class="eipsi-balance-mini-fill" style="width: <?php echo $balance_pct; ?>%"></div>
                                        </div>
                                        <span class="eipsi-balance-mini-text"><?php echo round($balance_pct); ?>%</span>
                                    </td>
                                    <td class="eipsi-pool-status-col">
                                        <label class="eipsi-toggle-switch">
                                            <input type="checkbox" class="eipsi-toggle-pool-status" 
                                                   data-pool-id="<?php echo $pool['id']; ?>" 
                                                   <?php echo $is_active ? 'checked' : ''; ?>>
                                            <span class="eipsi-toggle-slider"></span>
                                        </label>
                                        <span class="eipsi-status-text"><?php echo $is_active ? __('Activo', 'eipsi-forms') : __('Pausado', 'eipsi-forms'); ?></span>
                                    </td>
                                    <td class="eipsi-pool-actions-col">
                                        <div class="eipsi-action-buttons">
                                            <button class="button button-small eipsi-view-analytics" data-pool-id="<?php echo $pool['id']; ?>" title="<?php _e('Analytics', 'eipsi-forms'); ?>">
                                                📊
                                            </button>
                                            <button class="button button-small eipsi-edit-pool" data-pool-id="<?php echo $pool['id']; ?>" title="<?php _e('Editar', 'eipsi-forms'); ?>">
                                                ✏️
                                            </button>
                                            <button class="button button-small eipsi-get-shortcode" data-pool-id="<?php echo $pool['id']; ?>" title="<?php _e('Shortcode', 'eipsi-forms'); ?>">
                                                📋
                                            </button>
                                            <button class="button button-small eipsi-delete-pool-trigger" data-pool-id="<?php echo $pool['id']; ?>" data-pool-name="<?php echo esc_attr($pool['pool_name']); ?>" title="<?php _e('Eliminar', 'eipsi-forms'); ?>">
                                                🗑️
                                            </button>
                                        </div>
                                        <!-- Inline confirmation -->
                                        <div class="eipsi-delete-confirm-inline" id="delete-confirm-<?php echo $pool['id']; ?>" style="display: none;">
                                            <span><?php _e('¿Eliminar?', 'eipsi-forms'); ?></span>
                                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=eipsi-longitudinal-study&tab=pool-hub&action=delete&pool_id=' . $pool['id']), 'eipsi_delete_longitudinal_pool_' . $pool['id']); ?>" class="eipsi-delete-yes"><?php _e('Sí', 'eipsi-forms'); ?></a>
                                            <a href="#" class="eipsi-delete-no" data-pool-id="<?php echo $pool['id']; ?>"><?php _e('No', 'eipsi-forms'); ?></a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Analytics Tab -->
        <div class="eipsi-subtab-content" id="subtab-analytics" style="display: none;">
            <div class="eipsi-analytics-header">
                <div class="eipsi-analytics-selector">
                    <label for="eipsi-analytics-pool-select"><?php _e('Seleccionar pool:', 'eipsi-forms'); ?></label>
                    <select id="eipsi-analytics-pool-select" class="regular-text">
                        <option value=""><?php _e('— Todos los pools —', 'eipsi-forms'); ?></option>
                        <?php foreach ($pools as $pool) : ?>
                            <option value="<?php echo $pool['id']; ?>"><?php echo esc_html($pool['pool_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="button button-secondary" id="eipsi-export-analytics-csv">
                    📥 <?php _e('Exportar CSV', 'eipsi-forms'); ?>
                </button>
            </div>

            <div class="eipsi-analytics-content" id="eipsi-analytics-container">
                <div class="eipsi-empty-state-small">
                    <p><?php _e('Seleccioná un pool para ver analytics detallados.', 'eipsi-forms'); ?></p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; // End if (empty($pools)) - Cerrando el else del if principal ?>

    <!-- Shortcode Modal -->
    <div class="eipsi-modal-overlay" id="eipsi-shortcode-modal" style="display: none;">
        <div class="eipsi-modal eipsi-modal-small">
            <div class="eipsi-modal-header">
                <h2><?php _e('Shortcode para compartir', 'eipsi-forms'); ?></h2>
                <button class="eipsi-modal-close" type="button">&times;</button>
            </div>
            <div class="eipsi-modal-body">
                <p><?php _e('Copiá este shortcode y pegalo en cualquier página o entrada:', 'eipsi-forms'); ?></p>
                <div class="eipsi-shortcode-box">
                    <code id="eipsi-shortcode-display">[eipsi_pool_join pool_id="1"]</code>
                    <button type="button" class="button" id="eipsi-copy-shortcode">
                        📋 <?php _e('Copiar', 'eipsi-forms'); ?>
                    </button>
                </div>
                <p class="description"><?php _e('Variante con campo de nombre visible:', 'eipsi-forms'); ?></p>
                <div class="eipsi-shortcode-box">
                    <code id="eipsi-shortcode-display-name">[eipsi_pool_join pool_id="1" show_name="1"]</code>
                    <button type="button" class="button" id="eipsi-copy-shortcode-name">
                        📋 <?php _e('Copiar', 'eipsi-forms'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="eipsi-modal-overlay" id="eipsi-delete-modal" style="display: none;">
        <div class="eipsi-modal eipsi-modal-small">
            <div class="eipsi-modal-header">
                <h2>🗑️ <?php _e('¿Eliminar pool?', 'eipsi-forms'); ?></h2>
                <button class="eipsi-modal-close" type="button">&times;</button>
            </div>
            <div class="eipsi-modal-body">
                <p><?php _e('Estás por eliminar el pool:', 'eipsi-forms'); ?> <strong id="eipsi-delete-pool-name"></strong></p>
                <div class="eipsi-notice-warning">
                    <p>⚠️ <?php _e('Esta acción no se puede deshacer. Se eliminarán todas las asignaciones asociadas.', 'eipsi-forms'); ?></p>
                </div>
            </div>
            <div class="eipsi-modal-footer">
                <button type="button" class="button button-secondary eipsi-modal-cancel"><?php _e('Cancelar', 'eipsi-forms'); ?></button>
                <a href="#" class="button button-link-delete" id="eipsi-confirm-delete">
                    <?php _e('Eliminar permanentemente', 'eipsi-forms'); ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="eipsi-toast-container"></div>

    <style>
        /* Pool Hub v2 Styles */
        .eipsi-pool-hub-v2 {
            max-width: 1400px;
        }

        .eipsi-sub-tabs {
            margin-top: 20px;
            margin-bottom: 20px;
            border-bottom: 1px solid #c3c4c7;
        }

        .eipsi-sub-tabs .nav-tab {
            position: relative;
        }

        .eipsi-badge {
            display: inline-block;
            background: #3B6CAA;
            color: white;
            border-radius: 12px;
            padding: 2px 8px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 5px;
        }

        /* Empty State Full */
        .eipsi-empty-state-full {
            text-align: center;
            padding: 80px 20px;
            background: #f8f9fa;
            border-radius: 12px;
            border: 2px dashed #d6edff;
            margin-top: 30px;
        }

        .eipsi-empty-state-full .eipsi-empty-icon {
            font-size: 80px;
            margin-bottom: 25px;
        }

        .eipsi-empty-state-full h2 {
            margin: 0 0 15px 0;
            color: #2c3e50;
            font-size: 24px;
        }

        .eipsi-empty-state-full p {
            color: #64748b;
            margin-bottom: 30px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
            font-size: 15px;
        }

        /* KPI Cards */
        .eipsi-kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        @media (max-width: 1200px) {
            .eipsi-kpi-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .eipsi-kpi-grid {
                grid-template-columns: 1fr;
            }
        }

        .eipsi-kpi-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .eipsi-kpi-icon {
            font-size: 32px;
            width: 60px;
            height: 60px;
            background: #f0f6fc;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .eipsi-kpi-content {
            flex: 1;
        }

        .eipsi-kpi-value {
            display: block;
            font-size: 28px;
            font-weight: 700;
            color: #1d2327;
            line-height: 1;
        }

        .eipsi-kpi-label {
            display: block;
            font-size: 14px;
            color: #646970;
            margin-top: 4px;
        }

        .eipsi-kpi-detail {
            display: block;
            font-size: 12px;
            color: #8c8f94;
            margin-top: 2px;
        }

        /* Overview Grid */
        .eipsi-overview-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }

        @media (max-width: 900px) {
            .eipsi-overview-grid {
                grid-template-columns: 1fr;
            }
        }

        .eipsi-overview-main,
        .eipsi-overview-sidebar {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .eipsi-overview-main h2,
        .eipsi-overview-sidebar h2 {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 18px;
            color: #1d2327;
        }

        /* Activity List */
        .eipsi-activity-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .eipsi-activity-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f1;
        }

        .eipsi-activity-item:last-child {
            border-bottom: none;
        }

        .eipsi-activity-icon {
            font-size: 16px;
            width: 28px;
            height: 28px;
            background: #f0f6fc;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .eipsi-activity-content {
            flex: 1;
        }

        .eipsi-activity-text {
            display: block;
            font-size: 13px;
            color: #1d2327;
        }

        .eipsi-activity-time {
            display: block;
            font-size: 12px;
            color: #8c8f94;
            margin-top: 2px;
        }

        /* Pools Header */
        .eipsi-pools-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .eipsi-pools-header h2 {
            margin: 0;
        }

        /* Pool Cards Grid */
        .eipsi-pools-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }

        .eipsi-pool-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .eipsi-pool-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .eipsi-pool-card.is-inactive {
            opacity: 0.7;
        }

        .eipsi-pool-card-header {
            padding: 16px;
            border-bottom: 1px solid #f0f0f1;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .eipsi-pool-info h3 {
            margin: 0 0 8px 0;
            font-size: 16px;
            color: #1d2327;
        }

        .eipsi-status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .eipsi-status-badge.status-active {
            background: #d1f7d1;
            color: #0a6e0a;
        }

        .eipsi-status-badge.status-inactive {
            background: #f0f0f1;
            color: #646970;
        }

        /* Pool Menu */
        .eipsi-pool-menu {
            position: relative;
        }

        .eipsi-pool-menu-toggle {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            padding: 5px 10px;
            color: #646970;
            border-radius: 4px;
        }

        .eipsi-pool-menu-toggle:hover {
            background: #f0f0f1;
            color: #1d2327;
        }

        .eipsi-pool-menu-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            min-width: 150px;
            z-index: 100;
            display: none;
        }

        .eipsi-pool-menu-dropdown.is-open {
            display: block;
        }

        .eipsi-pool-menu-dropdown a {
            display: block;
            padding: 10px 15px;
            text-decoration: none;
            color: #1d2327;
            font-size: 13px;
        }

        .eipsi-pool-menu-dropdown a:hover {
            background: #f0f6fc;
        }

        .eipsi-pool-menu-dropdown hr {
            margin: 8px 0;
            border: none;
            border-top: 1px solid #f0f0f1;
        }

        /* Inline Delete Confirmation */
        .eipsi-delete-confirm {
            display: none;
            align-items: center;
            gap: 8px;
            padding: 10px 15px;
            background: #fff5f5;
            border: 1px solid #800000;
            border-radius: 4px;
            font-size: 12px;
            margin-top: 10px;
        }

        .eipsi-delete-confirm-text {
            color: #800000;
            font-weight: 500;
        }

        .eipsi-delete-yes {
            color: #800000;
            font-weight: 600;
            text-decoration: none;
        }

        .eipsi-delete-yes:hover {
            text-decoration: underline;
        }

        .eipsi-delete-no {
            color: #64748b;
            text-decoration: none;
        }

        .eipsi-delete-no:hover {
            color: #2c3e50;
            text-decoration: underline;
        }

        .eipsi-delete-separator {
            color: #c3c4c7;
        }

        .eipsi-pool-card-body {
            padding: 16px;
        }

        .eipsi-pool-description {
            margin: 0 0 12px 0;
            font-size: 13px;
            color: #646970;
            font-style: italic;
        }

        .eipsi-pool-meta {
            display: flex;
            gap: 15px;
            font-size: 12px;
            color: #8c8f94;
        }

        .eipsi-pool-card-stats {
            display: flex;
            background: #f6f7f7;
            padding: 12px 16px;
        }

        .eipsi-stat {
            flex: 1;
            text-align: center;
        }

        .eipsi-stat:not(:last-child) {
            border-right: 1px solid #dcdcde;
        }

        .eipsi-stat-value {
            display: block;
            font-size: 20px;
            font-weight: 700;
            color: #1d2327;
        }

        .eipsi-stat-label {
            display: block;
            font-size: 11px;
            color: #646970;
            text-transform: uppercase;
        }

        .eipsi-pool-card-actions {
            display: flex;
            gap: 8px;
            padding: 12px 16px;
            border-top: 1px solid #f0f0f1;
        }

        .eipsi-pool-card-actions .button {
            flex: 1;
            text-align: center;
        }

        /* Analytics */
        .eipsi-analytics-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .eipsi-analytics-selector {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .eipsi-analytics-selector label {
            font-weight: 600;
        }

        /* ==========================================================================
           MODAL POOL - REDESIGN v2.5
           ========================================================================== */

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
            display: none; /* Hidden by default, shown via JS */
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }
        
        .eipsi-modal-overlay.active {
            display: flex;
        }

        /* Modal Container */
        .eipsi-modal {
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

        /* Modal Header */
        .eipsi-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 24px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }

        .eipsi-modal-header h2 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
        }

        .eipsi-modal-close {
            background: none;
            border: none;
            font-size: 24px;
            color: #64748b;
            cursor: pointer;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: all 0.2s;
        }

        .eipsi-modal-close:hover {
            background: #fef2f2;
            color: #dc2626;
        }

        /* Modal Body */
        .eipsi-modal-body {
            padding: 24px;
            overflow-y: auto;
        }

        /* Form Layout - Labels arriba */
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
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .eipsi-form-field input:focus,
        .eipsi-form-field textarea:focus,
        .eipsi-form-field select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
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

        /* Pool Studies Rows - Redesigned */
        #eipsi-pool-studies-rows {
            margin: 0 0 12px 0;
        }

        .eipsi-pool-study-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            margin-bottom: 8px;
        }

        .eipsi-pool-study-row select {
            flex: 1;
            min-width: 200px;
            padding: 6px 8px;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            font-size: 13px;
        }

        .eipsi-pool-study-row input[type="number"] {
            width: 80px;
            text-align: right;
            padding: 6px 8px;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            font-size: 13px;
        }

        .eipsi-remove-study {
            color: #dc2626;
            cursor: pointer;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            font-size: 18px;
            transition: background 0.2s;
        }

        .eipsi-remove-study:hover {
            background: #fef2f2;
        }

        /* Progress Bar - Reemplaza "Total: X%" */
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

        .eipsi-pool-actions button,
        .eipsi-btn-secondary {
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 500;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            background: #ffffff;
            color: #374151;
            cursor: pointer;
            transition: all 0.2s;
        }

        .eipsi-pool-actions button:hover,
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
            transition: all 0.2s;
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
            transition: all 0.2s;
        }

        .eipsi-btn-primary:hover:not(:disabled) {
            background: #2563eb;
        }

        .eipsi-btn-primary:disabled {
            background: #cbd5e1;
            color: #94a3b8;
            cursor: not-allowed;
        }

        /* Force light mode - solo elementos internos */
        .eipsi-force-light-mode select,
        .eipsi-force-light-mode input,
        .eipsi-force-light-mode textarea {
            background: #ffffff;
            color: #1e293b;
        }

        /* Shortcode Box */
        .eipsi-shortcode-box {
            display: flex;
            gap: 10px;
            align-items: center;
            margin: 15px 0;
            padding: 12px;
            background: #f0f6fc;
            border-radius: 6px;
        }

        .eipsi-shortcode-box code {
            flex: 1;
            background: #1d2327;
            color: #a5f3fc;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 13px;
        }

        /* Empty States */
        .eipsi-empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .eipsi-empty-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .eipsi-empty-state h3 {
            margin: 0 0 10px 0;
            color: #1d2327;
        }

        .eipsi-empty-state p {
            color: #646970;
            margin-bottom: 25px;
        }

        .eipsi-empty-state-small {
            text-align: center;
            padding: 40px 20px;
            color: #646970;
        }

        /* Notice */
        .eipsi-notice-warning {
            background: #fcf9e8;
            border-left: 4px solid #dba617;
            padding: 12px;
            margin: 15px 0;
        }

        .eipsi-notice-warning p {
            margin: 0;
        }

        /* Toast */
        #eipsi-toast-container {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 100001;
        }

        .eipsi-toast {
            background: #1d2327;
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            margin-top: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            animation: eipsi-toast-in 0.3s ease;
        }

        .eipsi-toast.success {
            background: #008080;
        }

        .eipsi-toast.error {
            background: #800000;
        }

        @keyframes eipsi-toast-in {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* v2.5.2 - Overview Header */
        .eipsi-overview-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--eipsi-border);
        }

        .eipsi-overview-header h2 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
        }

        /* v2.5.2 - Pool Summary Grid */
        .eipsi-pool-summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .eipsi-pool-summary-card {
            background: white;
            border: 1px solid var(--eipsi-border);
            border-radius: var(--eipsi-radius-lg);
            padding: 20px;
            transition: all 0.2s ease;
        }

        .eipsi-pool-summary-card.is-paused {
            opacity: 0.7;
            background: var(--eipsi-bg-subtle);
        }

        .eipsi-pool-summary-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .eipsi-pool-summary-header h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
        }

        .eipsi-pool-summary-body {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 16px;
        }

        .eipsi-pool-summary-metric {
            text-align: center;
        }

        .eipsi-metric-value {
            display: block;
            font-size: 24px;
            font-weight: 600;
            color: var(--eipsi-text);
        }

        .eipsi-metric-label {
            display: block;
            font-size: 12px;
            color: var(--eipsi-text-muted);
            margin-top: 4px;
        }

        .eipsi-pool-summary-balance {
            margin-bottom: 16px;
        }

        .eipsi-balance-bar-container {
            height: 8px;
            background: var(--eipsi-bg-muted);
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 4px;
        }

        .eipsi-balance-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--eipsi-primary), #3b82f6);
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .eipsi-balance-label {
            font-size: 11px;
            color: var(--eipsi-text-muted);
        }

        .eipsi-pool-summary-actions {
            display: flex;
            gap: 8px;
        }

        /* v2.5.2 - Pools Table */
        .eipsi-pools-table-container {
            margin-top: 20px;
        }

        .eipsi-pools-table {
            width: 100%;
        }

        .eipsi-pools-table th {
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .eipsi-pool-row.is-paused {
            background: var(--eipsi-bg-subtle);
        }

        .eipsi-pool-name-col strong {
            font-size: 14px;
        }

        .eipsi-row-meta {
            font-size: 11px;
            color: var(--eipsi-text-muted);
            margin-top: 4px;
        }

        .eipsi-pool-balance-col {
            min-width: 100px;
        }

        .eipsi-balance-mini-bar {
            height: 6px;
            background: var(--eipsi-bg-muted);
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 4px;
        }

        .eipsi-balance-mini-fill {
            height: 100%;
            background: var(--eipsi-primary);
            border-radius: 3px;
        }

        .eipsi-balance-mini-text {
            font-size: 11px;
            color: var(--eipsi-text-muted);
        }

        .eipsi-action-buttons {
            display: flex;
            gap: 4px;
        }

        .eipsi-action-buttons .button {
            padding: 0 6px;
            min-height: 28px;
            line-height: 26px;
        }

        .eipsi-delete-confirm-inline {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            margin-top: 4px;
        }

        /* v2.5.2 - Toggle Switch */
        .eipsi-toggle-switch {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 24px;
            vertical-align: middle;
            margin-right: 8px;
        }

        .eipsi-toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .eipsi-toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .3s;
            border-radius: 24px;
        }

        .eipsi-toggle-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .3s;
            border-radius: 50%;
        }

        .eipsi-toggle-switch input:checked + .eipsi-toggle-slider {
            background-color: var(--eipsi-primary);
        }

        .eipsi-toggle-switch input:checked + .eipsi-toggle-slider:before {
            transform: translateX(20px);
        }

        .eipsi-status-text {
            font-size: 12px;
            color: var(--eipsi-text-muted);
        }

        /* v2.5.2 - Analytics Cards */
        .eipsi-analytics-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .eipsi-analytics-card {
            background: white;
            border: 1px solid var(--eipsi-border);
            border-radius: var(--eipsi-radius-lg);
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .eipsi-analytics-card-icon {
            font-size: 32px;
            width: 56px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--eipsi-bg-subtle);
            border-radius: var(--eipsi-radius);
        }

        .eipsi-analytics-card-content {
            flex: 1;
        }

        .eipsi-analytics-card-value {
            display: block;
            font-size: 28px;
            font-weight: 700;
            color: var(--eipsi-text);
            line-height: 1;
        }

        .eipsi-analytics-card-label {
            display: block;
            font-size: 13px;
            color: var(--eipsi-text-muted);
            margin-top: 4px;
        }

        .eipsi-analytics-section-title {
            font-size: 16px;
            font-weight: 600;
            margin: 24px 0 16px 0;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--eipsi-border);
        }

        .eipsi-analytics-empty {
            text-align: center;
            padding: 60px 20px;
            background: var(--eipsi-bg-subtle);
            border-radius: var(--eipsi-radius-lg);
        }

        .eipsi-analytics-empty .eipsi-empty-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }

        .eipsi-analytics-empty h3 {
            margin: 0 0 8px 0;
            font-size: 16px;
            font-weight: 600;
        }

        .eipsi-analytics-empty p {
            margin: 0;
            color: var(--eipsi-text-muted);
        }

        /* v2.5.2 - Modal Content */
        .eipsi-modal-content {
            background: white;
            border-radius: var(--eipsi-radius-lg);
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
        }
    </style>

    <script data-version="<?php echo time(); ?>">
    document.addEventListener('DOMContentLoaded', function() {
        console.log('[POOL-HUB-INIT] DOMContentLoaded - Script Pool Hub iniciando...');

        const subTabs = document.querySelectorAll('.eipsi-sub-tabs .nav-tab');
        const subContents = document.querySelectorAll('.eipsi-subtab-content');
        console.log('[POOL-HUB-INIT] Sub-tabs encontrados:', subTabs.length);
        console.log('[POOL-HUB-INIT] Sub-contents encontrados:', subContents.length);

        // Sub-tab switching
        subTabs.forEach(tab => {
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                const subtab = this.dataset.subtab;
                console.log('[POOL-HUB] Sub-tab clicked:', subtab);

                subTabs.forEach(t => t.classList.remove('nav-tab-active'));
                this.classList.add('nav-tab-active');

                subContents.forEach(c => c.style.display = 'none');
                document.getElementById('subtab-' + subtab).style.display = 'block';
                console.log('[POOL-HUB] Sub-tab content visible:', 'subtab-' + subtab);

                // Update URL hash
                window.location.hash = subtab;
            });
        });

        // Handle hash on load
        if (window.location.hash) {
            const hash = window.location.hash.substring(1);
            const tab = document.querySelector('.eipsi-sub-tabs [data-subtab="' + hash + '"]');
            if (tab) {
                tab.click();
            }
        }

        // Pool menu dropdowns
        document.querySelectorAll('.eipsi-pool-menu-toggle').forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                e.stopPropagation();
                const dropdown = this.nextElementSibling;
                const isOpen = dropdown.classList.contains('is-open');
                console.log('[POOL-HUB] Menu toggle clicked, isOpen:', isOpen);

                // Close all dropdowns
                document.querySelectorAll('.eipsi-pool-menu-dropdown').forEach(d => {
                    d.classList.remove('is-open');
                });

                if (!isOpen) {
                    dropdown.classList.add('is-open');
                    console.log('[POOL-HUB] Dropdown opened');
                }
            });
        });

        // Dropdown cleanup on any click
        document.addEventListener('click', function() {
            document.querySelectorAll('.eipsi-pool-menu-dropdown').forEach(d => {
                d.classList.remove('is-open');
            });
        });

        // Modal handling
        const poolModal = document.getElementById('eipsi-pool-modal');
        const shortcodeModal = document.getElementById('eipsi-shortcode-modal');
        const deleteModal = document.getElementById('eipsi-delete-modal');

        // Verificación inicial de elementos
        console.log('[POOL-HUB-INIT] Verificando elementos DOM:');
        console.log('  - eipsi-pool-modal:', poolModal ? 'EXISTS' : 'NOT FOUND');
        console.log('  - eipsi-shortcode-modal:', shortcodeModal ? 'EXISTS' : 'NOT FOUND');
        console.log('  - eipsi-delete-modal:', deleteModal ? 'EXISTS' : 'NOT FOUND');

        function openModal(modal) {
            console.log('[POOL-HUB] openModal llamado, modal:', modal ? modal.id || 'unnamed' : 'null');
            if (modal) {
                modal.classList.add('active');
                modal.style.display = 'flex'; // Fallback for older browsers
                console.log('[POOL-HUB] Modal abierto:', modal.id || 'unnamed');
            } else {
                console.error('[POOL-HUB] openModal: modal es null o undefined');
            }
        }

        function closeModal(modal) {
            console.log('[POOL-HUB] closeModal llamado, modal:', modal ? modal.id || 'unnamed' : 'null');
            if (modal) {
                modal.classList.remove('active');
                modal.style.display = 'none';
                console.log('[POOL-HUB] Modal cerrado:', modal.id || 'unnamed');
            } else {
                console.error('[POOL-HUB] closeModal: modal es null o undefined');
            }
        }

        // v2.2.4 - Funciones específicas para handlers inline (compatibilidad con randomization modal style)
        // v2.5.3 - Definidas en window para estar disponibles antes de DOMContentLoaded
        window.openEipsiPoolModal = function() {
            console.log('[POOL-HUB] openEipsiPoolModal() llamado');
            if (poolModal) {
                updateProbabilityTotal();
                openModal(poolModal);
            } else {
                console.error('[POOL-HUB] poolModal no encontrado');
            }
        };

        function closeEipsiPoolModal() {
            console.log('[POOL-HUB] closeEipsiPoolModal() llamado');
            closeModal(poolModal);
            // Reset form to create mode
            resetPoolForm();
        }

        function resetPoolForm() {
            console.log('[POOL-HUB] resetPoolForm() llamado');
            const form = document.getElementById('eipsi-pool-form');
            if (form) {
                form.reset();
                document.getElementById('eipsi-pool-id').value = '0';
                document.getElementById('eipsi-modal-title').textContent = '<?php echo esc_js(__('Crear nuevo pool', 'eipsi-forms')); ?>';
                document.getElementById('eipsi-save-pool-btn').textContent = '<?php echo esc_js(__('Guardar pool', 'eipsi-forms')); ?>';
            }
            // Clear study rows
            const rowsContainer = document.getElementById('eipsi-pool-studies-rows');
            if (rowsContainer) {
                rowsContainer.innerHTML = '';
            }
            // Hide page info
            const pageInfo = document.getElementById('eipsi-pool-page-info');
            if (pageInfo) {
                pageInfo.style.display = 'none';
            }
            updateProbabilityTotal();
        }

        document.querySelectorAll('.eipsi-modal-close, .eipsi-modal-cancel').forEach(btn => {
            btn.addEventListener('click', function() {
                console.log('[POOL-HUB] Modal close/cancel button clicked');
                closeModal(this.closest('.eipsi-modal-overlay'));
            });
        });

        document.querySelectorAll('.eipsi-modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', function(e) {
                if (e.target === this) {
                    console.log('[POOL-HUB] Modal overlay clicked (outside content)');
                    closeModal(this);
                }
            });
        });

        // Create pool buttons - v2.2.3: Fixed to work with empty state
        // Using jQuery delegation for dynamically added buttons
        $(document).on('click', '.eipsi-create-pool-btn', function(e) {
            console.log('[POOL-HUB-JQ] Handler jQuery activado para .eipsi-create-pool-btn');
            e.preventDefault();
            e.stopPropagation();

            const modalType = $(this).data('open-modal');
            console.log('[POOL-HUB-JQ] modalType:', modalType, '| poolModal existe:', !!poolModal);

            if (modalType === 'create' && poolModal) {
                console.log('[POOL-HUB-JQ] Abriendo modal de creación...');
                updateProbabilityTotal();
                openModal(poolModal);
                console.log('[POOL-HUB-JQ] Modal abierto vía jQuery');
            } else {
                console.error('[POOL-HUB-JQ] No se pudo abrir modal:', {modalType, poolModalExists: !!poolModal});
            }
        });

        // Edit pool
        document.querySelectorAll('.eipsi-edit-pool').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const poolId = this.dataset.poolId;
                console.log('[POOL-HUB] Edit pool clicked, poolId:', poolId);
                // Load pool data via AJAX and open modal
                loadPoolData(poolId);
            });
        });

        // View analytics - sync with analytics tab
        document.querySelectorAll('.eipsi-view-analytics').forEach(btn => {
            btn.addEventListener('click', function() {
                const poolId = this.dataset.poolId;
                console.log('[POOL-HUB] View analytics clicked, poolId:', poolId);
                const poolSelect = document.getElementById('eipsi-analytics-pool-select');
                
                // Switch to analytics tab
                const analyticsTab = document.querySelector('[data-subtab="analytics"]');
                if (analyticsTab) analyticsTab.click();
                
                // Select the pool
                if (poolSelect) {
                    poolSelect.value = poolId;
                    poolSelect.dispatchEvent(new Event('change'));
                    console.log('[POOL-HUB] Pool selected in analytics:', poolId);
                }
            });
        });

        // Get shortcode
        document.querySelectorAll('.eipsi-get-shortcode').forEach(btn => {
            btn.addEventListener('click', function() {
                const poolId = this.dataset.poolId;
                console.log('[POOL-HUB] Get shortcode clicked, poolId:', poolId);
                document.getElementById('eipsi-shortcode-display').textContent = '[eipsi_pool_join pool_id="' + poolId + '"]';
                document.getElementById('eipsi-shortcode-display-name').textContent = '[eipsi_pool_join pool_id="' + poolId + '" show_name="1"]';
                openModal(shortcodeModal);
            });
        });

        // Copy shortcode
        function copyToClipboard(text, btn) {
            console.log('[POOL-HUB] copyToClipboard called, text length:', text.length);
            navigator.clipboard.writeText(text).then(function() {
                console.log('[POOL-HUB] Text copied to clipboard');
                const originalText = btn.textContent;
                btn.textContent = '✅ Copiado!';
                setTimeout(() => {
                    btn.textContent = originalText;
                }, 2000);
            }).catch(function(err) {
                console.error('[POOL-HUB] Failed to copy:', err);
            });
        }

        document.getElementById('eipsi-copy-shortcode').addEventListener('click', function() {
            console.log('[POOL-HUB] Copy shortcode clicked');
            copyToClipboard(document.getElementById('eipsi-shortcode-display').textContent, this);
        });

        document.getElementById('eipsi-copy-shortcode-name').addEventListener('click', function() {
            console.log('[POOL-HUB] Copy shortcode (with name) clicked');
            copyToClipboard(document.getElementById('eipsi-shortcode-display-name').textContent, this);
        });

        // Delete pool - inline confirmation (no timeout, closes on 'No' or click outside)
        let currentConfirmBox = null;
        
        document.querySelectorAll('.eipsi-delete-pool-trigger').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const poolId = this.dataset.poolId;
                console.log('[POOL-HUB] Delete pool trigger clicked, poolId:', poolId);
                const confirmBox = document.getElementById('delete-confirm-' + poolId);
                
                // Hide all confirm boxes
                document.querySelectorAll('.eipsi-delete-confirm').forEach(box => {
                    box.style.display = 'none';
                });
                
                // Show this confirm box
                confirmBox.style.display = 'flex';
                currentConfirmBox = confirmBox;
                console.log('[POOL-HUB] Delete confirmation box shown for pool:', poolId);
            });
        });
        
        // Cancel delete
        document.querySelectorAll('.eipsi-delete-no').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const poolId = this.dataset.poolId;
                console.log('[POOL-HUB] Delete cancelled for pool:', poolId);
                document.getElementById('delete-confirm-' + poolId).style.display = 'none';
                currentConfirmBox = null;
            });
        });
        
        // Close confirm box when clicking outside any pool card
        document.addEventListener('click', function(e) {
            if (currentConfirmBox && !e.target.closest('.eipsi-pool-card')) {
                document.querySelectorAll('.eipsi-delete-confirm').forEach(box => {
                    box.style.display = 'none';
                });
                currentConfirmBox = null;
            }
        });

        // Toggle pool status (from dropdown menu)
        document.querySelectorAll('.eipsi-toggle-pool').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const poolId = this.dataset.poolId;
                const currentStatus = this.dataset.status;
                console.log('[POOL-HUB] Toggle pool status clicked, poolId:', poolId, 'currentStatus:', currentStatus);
                togglePoolStatus(poolId, currentStatus);
            });
        });

        // v2.5.2 - Toggle pool status from table switch
        document.querySelectorAll('.eipsi-toggle-pool-status').forEach(toggle => {
            toggle.addEventListener('change', function() {
                const poolId = this.dataset.poolId;
                const newStatus = this.checked ? 'active' : 'paused';
                console.log('[POOL-HUB] Toggle switch changed, poolId:', poolId, 'newStatus:', newStatus);
                togglePoolStatus(poolId, this.checked ? 'paused' : 'active');
                
                // Update status text
                const row = this.closest('.eipsi-pool-row');
                const statusText = row?.querySelector('.eipsi-status-text');
                if (statusText) {
                    statusText.textContent = this.checked ? '<?php _e('Activo', 'eipsi-forms'); ?>' : '<?php _e('Pausado', 'eipsi-forms'); ?>';
                }
                if (row) {
                    row.classList.toggle('is-active', this.checked);
                    row.classList.toggle('is-paused', !this.checked);
                }
            });
        });

        // Toast notification
        function showToast(message, type = 'success') {
            console.log('[POOL-HUB] showToast:', message, 'type:', type);
            const container = document.getElementById('eipsi-toast-container');
            const toast = document.createElement('div');
            toast.className = 'eipsi-toast ' + type;
            toast.textContent = message;
            container.appendChild(toast);
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }

        // Load pool data function
        function loadPoolData(poolId) {
            console.log('[POOL-HUB] loadPoolData called, poolId:', poolId);
            const data = new FormData();
            data.append('action', 'eipsi_get_pool_data');
            data.append('pool_id', poolId);
            data.append('nonce', '<?php echo wp_create_nonce("eipsi_forms_nonce"); ?>');

            console.log('[POOL-HUB] Fetching pool data via AJAX...');
            fetch(ajaxurl, {
                method: 'POST',
                body: data
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('eipsi-modal-title').textContent = '<?php _e("Editar pool", "eipsi-forms"); ?>';
                    document.getElementById('eipsi-pool-id').value = data.data.pool.id;
                    document.getElementById('eipsi-pool-name').value = data.data.pool.pool_name;
                    document.getElementById('eipsi-pool-description').value = data.data.pool.description;
                    document.getElementById('eipsi-pool-method').value = data.data.pool.method;
                    
                    // Populate studies
                    const container = document.getElementById('eipsi-pool-studies-rows');
                    container.innerHTML = '';
                    if (data.data.studies) {
                        data.data.studies.forEach(study => {
                            addStudyRow(study.id, study.probability);
                        });
                    }
                    updateProbabilityTotal();
                    openModal(poolModal);
                } else {
                    showToast(data.data.message || '<?php _e("Error al cargar pool", "eipsi-forms"); ?>', 'error');
                }
            });
        }

        // Toggle pool status function
        function togglePoolStatus(poolId, currentStatus) {
            console.log('[POOL-HUB] togglePoolStatus called, poolId:', poolId, 'currentStatus:', currentStatus);
            const data = new FormData();
            data.append('action', 'eipsi_toggle_pool_status');
            data.append('pool_id', poolId);
            data.append('nonce', '<?php echo wp_create_nonce("eipsi_forms_nonce"); ?>');

            console.log('[POOL-HUB] Sending toggle pool status request...');
            fetch(ajaxurl, {
                method: 'POST',
                body: data
            })
            .then(response => response.json())
            .then(data => {
                console.log('[POOL-HUB] Toggle status response:', data.success ? 'SUCCESS' : 'ERROR');
                if (data.success) {
                    showToast(data.data.message);
                    location.reload();
                } else {
                    showToast(data.data.message || '<?php _e("Error al cambiar estado", "eipsi-forms"); ?>', 'error');
                }
            })
            .catch(error => {
                console.error('[POOL-HUB] Toggle status error:', error);
                showToast('<?php _e("Error de conexión", "eipsi-forms"); ?>', 'error');
            });
        }

        // v2.5.2 - Add study row usando estudios cargados dinámicamente
        function addStudyRow(studyId = '', probability = '') {
            console.log('[POOL-HUB] addStudyRow called, studyId:', studyId, 'probability:', probability);
            const container = document.getElementById('eipsi-pool-studies-rows');
            if (!container) {
                console.error('[POOL-HUB] Container eipsi-pool-studies-rows no encontrado');
                return;
            }
            
            const rowCount = container.querySelectorAll('.eipsi-pool-study-row').length;
            console.log('[POOL-HUB] Current study rows:', rowCount);
            
            // v2.5.2 - Usar estudios cargados desde window.eipsiPoolStudies
            const studies = window.eipsiPoolStudies || [];
            console.log('[POOL-HUB] Estudios disponibles:', studies.length);
            
            if (studies.length === 0) {
                alert('<?php _e("No hay estudios disponibles. Primero creá un estudio longitudinal.", "eipsi-forms"); ?>');
                return;
            }
            
            // Generar opciones del select dinámicamente
            let optionsHtml = '<option value=""><?php _e("Seleccionar estudio...", "eipsi-forms"); ?></option>';
            studies.forEach(function(study) {
                const selected = (studyId == study.id) ? 'selected' : '';
                optionsHtml += '<option value="' + study.id + '" ' + selected + '>' + 
                    study.study_name + ' (' + study.study_code + ')</option>';
            });
            
            const row = document.createElement('div');
            row.className = 'eipsi-pool-study-row';
            row.innerHTML = `
                <select name="study_select[]" required style="flex:1;min-width:200px;padding:6px 8px;border:1px solid #e2e8f0;border-radius:4px;font-size:13px;">
                    ${optionsHtml}
                </select>
                <input type="number" name="study_probability[]" value="${probability}" min="0" max="100" step="0.01" placeholder="%" required style="width:80px;text-align:right;padding:6px 8px;border:1px solid #e2e8f0;border-radius:4px;font-size:13px;">
                <span class="eipsi-remove-study" title="<?php _e('Eliminar', 'eipsi-forms'); ?>">&times;</span>
            `;
            
            row.querySelector('.eipsi-remove-study').addEventListener('click', function() {
                console.log('[POOL-HUB] Remove study row clicked');
                row.remove();
                updateProbabilityTotal();
            });
            
            row.querySelectorAll('input, select').forEach(input => {
                input.addEventListener('change', function() {
                    console.log('[POOL-HUB] Study row input changed:', this.name, 'value:', this.value);
                    updateProbabilityTotal();
                });
            });
            
            container.appendChild(row);
            console.log('[POOL-HUB] Study row added, new count:', rowCount + 1);
            
            // v2.5.2 - Auto-distribute probabilities after adding
            autoDistributeProbabilities();
        }

        // v2.5.2 - Auto-distribute function
        function autoDistributeProbabilities() {
            const rows = document.querySelectorAll('#eipsi-pool-studies-rows .eipsi-pool-study-row');
            if (rows.length === 0) return;
            
            const equalProb = (100 / rows.length).toFixed(2);
            rows.forEach((row, index) => {
                const input = row.querySelector('input[type="number"]');
                // Only set if empty or default value
                if (!input.value || input.value === '0' || input.value === '') {
                    input.value = equalProb;
                }
            });
            updateProbabilityTotal();
            console.log('[POOL-HUB] Auto-distributed probabilities:', equalProb + '% each');
        }

        // Add study button
        document.getElementById('eipsi-add-study-row').addEventListener('click', function() {
            console.log('[POOL-HUB] Add study button clicked');
            addStudyRow();
        });

        // Distribute probabilities
        document.getElementById('eipsi-distribute-probabilities').addEventListener('click', function() {
            console.log('[POOL-HUB] Distribute probabilities button clicked');
            const rows = document.querySelectorAll('#eipsi-pool-studies-rows .eipsi-pool-study-row');
            console.log('[POOL-HUB] Number of study rows to distribute:', rows.length);
            if (rows.length === 0) {
                alert('<?php _e("Primero agregá al menos un estudio.", "eipsi-forms"); ?>');
                return;
            }
            const equalProb = (100 / rows.length).toFixed(2);
            console.log('[POOL-HUB] Equal probability calculated:', equalProb);
            rows.forEach((row, index) => {
                row.querySelector('input[type="number"]').value = equalProb;
                console.log('[POOL-HUB] Row', index, 'set to:', equalProb);
            });
            updateProbabilityTotal();
        });

        // Update probability total - with progress bar
        function updateProbabilityTotal() {
            const rows = document.querySelectorAll('#eipsi-pool-studies-rows .eipsi-pool-study-row');
            let total = 0;
            rows.forEach(row => {
                const input = row.querySelector('input[type="number"]');
                total += parseFloat(input.value) || 0;
            });
            total = Math.round(total * 100) / 100;
            console.log('[POOL-HUB] updateProbabilityTotal calculated:', total);
            
            // Update progress bar
            const progressFill = document.getElementById('eipsi-progress-fill');
            const progressText = document.getElementById('eipsi-progress-text');
            const progressStatus = document.getElementById('eipsi-progress-status');
            
            // Clamp width between 0 and 100 for display
            const displayWidth = Math.min(Math.max(total, 0), 100);
            progressFill.style.width = displayWidth + '%';
            
            // Update progress bar color and text
            const progressLabel = progressText.querySelector('span:first-child');
            progressLabel.textContent = total.toFixed(2) + '% / 100%';
            
            // Allow ±1% tolerance for rounding (99% to 101% is valid)
            const isValid = total >= 99 && total <= 101;
            
            if (isValid) {
                progressFill.classList.remove('invalid');
                progressFill.classList.add('valid');
                progressText.classList.remove('invalid');
                progressText.classList.add('valid');
                progressStatus.textContent = '✅ Completo';
                console.log('[POOL-HUB] Probability total is valid:', total + '%');
            } else {
                progressFill.classList.remove('valid');
                progressFill.classList.add('invalid');
                progressText.classList.remove('valid');
                progressText.classList.add('invalid');
                progressStatus.textContent = total < 99 ? '❌ Incompleto' : '❌ Excedido';
                console.log('[POOL-HUB] Probability total is INVALID:', total);
            }
            
            // Update save button state
            updateSaveButtonState();
        }

        // Save pool
        const savePoolBtn = document.getElementById('eipsi-save-pool-btn');
        const poolForm = document.getElementById('eipsi-pool-form');
        
        function updateSaveButtonState() {
            const rows = document.querySelectorAll('#eipsi-pool-studies-rows .eipsi-pool-study-row');
            let total = 0;
            
            rows.forEach(row => {
                const input = row.querySelector('input[type="number"]');
                total += parseFloat(input.value) || 0;
            });
            
            // Allow ±1% tolerance (99% to 101% is valid)
            const roundedTotal = Math.round(total * 100) / 100;
            const isValid = roundedTotal >= 99 && roundedTotal <= 101 && rows.length > 0;
            savePoolBtn.disabled = !isValid;
            console.log('[POOL-HUB] updateSaveButtonState - isValid:', isValid, 'total:', roundedTotal + '%');
        }
        
        // Initial state
        updateProbabilityTotal();
        updateSaveButtonState();
        
        savePoolBtn.addEventListener('click', function() {
            console.log('[POOL-HUB] Save pool button clicked');
            if (this.disabled) {
                console.log('[POOL-HUB] Save blocked - button disabled');
                showToast('<?php _e("La suma de probabilidades debe ser exactamente 100%", "eipsi-forms"); ?>', 'error');
                return;
            }
            
            // Collect studies data
            const rows = document.querySelectorAll('#eipsi-pool-studies-rows .eipsi-pool-study-row');
            console.log('[POOL-HUB] Collecting pool data, rows:', rows.length);
            
            const studiesData = [];
            let total = 0;
            
            rows.forEach((row, index) => {
                const select = row.querySelector('select');
                const input = row.querySelector('input[type="number"]');
                const studyId = select.value;
                const probability = parseFloat(input.value) || 0;
                
                if (studyId) {
                    studiesData.push({
                        study_id: studyId,
                        probability: probability
                    });
                    total += probability;
                    console.log('[POOL-HUB] Row', index, '- Study:', studyId, 'Prob:', probability);
                }
            });
            
            console.log('[POOL-HUB] Total probability:', total);
            
            // Allow ±1% tolerance for rounding errors
            if (total < 99 || total > 101) {
                showToast('<?php _e("La suma de probabilidades debe ser 100% (±1% tolerancia)", "eipsi-forms"); ?>', 'error');
                return;
            }
            
            if (studiesData.length === 0) {
                showToast('<?php _e("Agregá al menos un estudio al pool", "eipsi-forms"); ?>', 'error');
                return;
            }
            
            // Set hidden field with studies data
            document.getElementById('eipsi-pool-studies-data').value = JSON.stringify(studiesData);
            console.log('[POOL-HUB] Studies data JSON:', document.getElementById('eipsi-pool-studies-data').value);
            
            showToast('<?php _e("Guardando pool y creando página...", "eipsi-forms"); ?>', 'info');
            
            // Submit form via traditional POST to admin-post.php
            console.log('[POOL-HUB] Submitting form to:', poolForm.action);
            poolForm.submit();
        });

        // Analytics pool selector
        const analyticsSelect = document.getElementById('eipsi-analytics-pool-select');
        if (analyticsSelect) analyticsSelect.addEventListener('change', function() {
            const poolId = this.value;
            console.log('[POOL-HUB] Analytics pool selector changed, poolId:', poolId);
            if (poolId) {
                loadPoolAnalytics(poolId);
            } else {
                document.getElementById('eipsi-analytics-container').innerHTML = '<div class="eipsi-empty-state-small"><p><?php _e("Seleccioná un pool para ver analytics.", "eipsi-forms"); ?></p></div>';
            }
        });

        function loadPoolAnalytics(poolId) {
            console.log('[POOL-HUB] loadPoolAnalytics called, poolId:', poolId);
            const container = document.getElementById('eipsi-analytics-container');
            container.innerHTML = '<div class="eipsi-empty-state-small"><p><?php _e("Cargando...", "eipsi-forms"); ?></p></div>';
            
            const data = new FormData();
            data.append('action', 'eipsi_get_pool_analytics');
            data.append('pool_id', poolId);
            data.append('nonce', '<?php echo wp_create_nonce("eipsi_forms_nonce"); ?>');

            console.log('[POOL-HUB] Fetching pool analytics...');
            fetch(ajaxurl, {
                method: 'POST',
                body: data
            })
            .then(response => response.json())
            .then(data => {
                console.log('[POOL-HUB] Pool analytics response:', data.success ? 'SUCCESS' : 'ERROR');
                if (data.success) {
                    renderPoolAnalytics(data.data);
                } else {
                    container.innerHTML = '<div class="eipsi-empty-state-small"><p>' + (data.data.message || '<?php _e("Error al cargar analytics.", "eipsi-forms"); ?>') + '</p></div>';
                }
            })
            .catch(error => {
                console.error('[POOL-HUB] Analytics fetch error:', error);
                container.innerHTML = '<div class="eipsi-empty-state-small"><p>Error de conexión</p></div>';
            });
        }

        // v2.5.2 - Render pool analytics con cards y tabla breakdown
        function renderPoolAnalytics(data) {
            console.log('[POOL-HUB] renderPoolAnalytics called, data:', data);
            const container = document.getElementById('eipsi-analytics-container');
            
            // Si no hay asignaciones
            if (!data.assignments || data.assignments.length === 0) {
                container.innerHTML = `
                    <div class="eipsi-analytics-empty">
                        <div class="eipsi-empty-icon">📊</div>
                        <h3><?php _e('Este pool todavía no tiene participantes asignados', 'eipsi-forms'); ?></h3>
                        <p><?php _e('Compartí el shortcode para comenzar a recibir participantes.', 'eipsi-forms'); ?></p>
                    </div>
                `;
                return;
            }
            
            // Calcular métricas
            const total = data.total_assignments || 0;
            const completed = data.completed || 0;
            const inProgress = data.in_progress || 0;
            const dropouts = data.dropouts || 0;
            const completionRate = total > 0 ? Math.round((completed / total) * 100) : 0;
            const dropoutRate = total > 0 ? Math.round((dropouts / total) * 100) : 0;
            
            // HTML de cards resumen
            let html = `
                <div class="eipsi-analytics-cards">
                    <div class="eipsi-analytics-card">
                        <div class="eipsi-analytics-card-icon">👥</div>
                        <div class="eipsi-analytics-card-content">
                            <span class="eipsi-analytics-card-value">${total}</span>
                            <span class="eipsi-analytics-card-label"><?php _e('Total asignados', 'eipsi-forms'); ?></span>
                        </div>
                    </div>
                    <div class="eipsi-analytics-card">
                        <div class="eipsi-analytics-card-icon">✅</div>
                        <div class="eipsi-analytics-card-content">
                            <span class="eipsi-analytics-card-value">${completionRate}%</span>
                            <span class="eipsi-analytics-card-label"><?php _e('Completion rate', 'eipsi-forms'); ?></span>
                        </div>
                    </div>
                    <div class="eipsi-analytics-card">
                        <div class="eipsi-analytics-card-icon">⚠️</div>
                        <div class="eipsi-analytics-card-content">
                            <span class="eipsi-analytics-card-value">${dropoutRate}%</span>
                            <span class="eipsi-analytics-card-label"><?php _e('Dropout rate', 'eipsi-forms'); ?></span>
                        </div>
                    </div>
                </div>
                
                <h3 class="eipsi-analytics-section-title"><?php _e('Breakdown por estudio', 'eipsi-forms'); ?></h3>
                <div class="eipsi-analytics-table-container">
                    <table class="eipsi-analytics-table wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Estudio', 'eipsi-forms'); ?></th>
                                <th><?php _e('Asignaciones', 'eipsi-forms'); ?></th>
                                <th><?php _e('% Real', 'eipsi-forms'); ?></th>
                                <th><?php _e('% Esperado', 'eipsi-forms'); ?></th>
                                <th><?php _e('Completados', 'eipsi-forms'); ?></th>
                                <th><?php _e('En progreso', 'eipsi-forms'); ?></th>
                                <th><?php _e('Dropouts', 'eipsi-forms'); ?></th>
                                <th><?php _e('Completion Rate', 'eipsi-forms'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            // Agregar filas de estudios
            if (data.studies && data.studies.length > 0) {
                data.studies.forEach(study => {
                    const realPct = total > 0 ? ((study.assignments / total) * 100).toFixed(1) : 0;
                    const studyCompletionRate = study.assignments > 0 ? Math.round((study.completed / study.assignments) * 100) : 0;
                    
                    html += `
                        <tr>
                            <td><strong>${study.name}</strong></td>
                            <td>${study.assignments}</td>
                            <td>${realPct}%</td>
                            <td>${study.expected_pct}%</td>
                            <td>${study.completed}</td>
                            <td>${study.in_progress}</td>
                            <td>${study.dropouts}</td>
                            <td>${studyCompletionRate}%</td>
                        </tr>
                    `;
                });
            }
            
            html += `
                        </tbody>
                    </table>
                </div>
            `;
            
            container.innerHTML = html;
            console.log('[POOL-HUB] Analytics rendered with cards and table');
        }

        // Export CSV
        const exportBtn = document.getElementById('eipsi-export-analytics-csv');
        if (exportBtn) exportBtn.addEventListener('click', function() {
            const poolId = document.getElementById('eipsi-analytics-pool-select').value;
            console.log('[POOL-HUB] Export CSV clicked, poolId:', poolId);
            if (!poolId) {
                showToast('<?php _e("Seleccioná un pool primero.", "eipsi-forms"); ?>', 'error');
                return;
            }
            const exportUrl = ajaxurl + '?action=eipsi_export_pool_csv&pool_id=' + poolId + '&nonce=<?php echo wp_create_nonce("eipsi_forms_nonce"); ?>';
            console.log('[POOL-HUB] Export URL:', exportUrl);
            window.location.href = exportUrl;
        });

        // Log form input changes for debugging
        document.getElementById('eipsi-pool-name')?.addEventListener('input', function() {
            console.log('[POOL-HUB] Pool name changed:', this.value);
        });
        
        document.getElementById('eipsi-pool-description')?.addEventListener('input', function() {
            console.log('[POOL-HUB] Pool description changed:', this.value);
        });
        
        document.getElementById('eipsi-pool-method')?.addEventListener('change', function() {
            console.log('[POOL-HUB] Pool method changed:', this.value);
        });

        console.log('[POOL-HUB-INIT] Script Pool Hub cargado completamente');
    }); // End DOMContentLoaded
    </script>

    <!-- v2.5.3 - Script ahora disponible para ambos estados (con/sin pools) -->
    <script>
    if (typeof window.openEipsiPoolModal !== 'function') {
        window.openEipsiPoolModal = function() {
            const modal = document.getElementById('eipsi-pool-modal');
            if (modal) {
                modal.classList.add('active');
                modal.style.display = 'flex';
            } else {
                console.error('[POOL-HUB-FALLBACK] Modal no encontrado');
            }
        };
        window.closeEipsiPoolModal = function() {
            const modal = document.getElementById('eipsi-pool-modal');
            if (modal) {
                modal.classList.remove('active');
                modal.style.display = 'none';
            }
        };
        console.log('[POOL-HUB-FALLBACK] Funciones definidas en modo fallback');
    }
    </script>

    <!-- Create/Edit Pool Modal - v2.5.2 max-width 680px -->
    <div class="eipsi-modal-overlay" id="eipsi-pool-modal" style="display: none;">
        <div class="eipsi-modal-content" style="max-width: 680px;">
            <div class="eipsi-modal-header">
                <h2 id="eipsi-modal-title"><?php _e('Crear nuevo pool', 'eipsi-forms'); ?></h2>
                <button class="eipsi-modal-close" type="button" onclick="closeEipsiPoolModal()">&times;</button>
            </div>
            <div class="eipsi-modal-body">
                <form id="eipsi-pool-form" method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                    <input type="hidden" name="action" value="eipsi_save_pool">
                    <input type="hidden" name="pool_id" id="eipsi-pool-id" value="0">
                    <?php wp_nonce_field('eipsi_save_pool_nonce', 'pool_nonce'); ?>

                    <!-- Form Fields -->
                    <div class="eipsi-form-field">
                        <label for="eipsi-pool-name"><?php _e('Nombre del pool', 'eipsi-forms'); ?></label>
                        <input type="text" name="pool_name" id="eipsi-pool-name" required>
                    </div>

                    <div class="eipsi-form-field">
                        <label for="eipsi-pool-description"><?php _e('Descripción', 'eipsi-forms'); ?></label>
                        <textarea name="pool_description" id="eipsi-pool-description" rows="3"></textarea>
                    </div>

                    <div class="eipsi-form-field">
                        <label for="eipsi-pool-method"><?php _e('Método', 'eipsi-forms'); ?></label>
                        <select name="method" id="eipsi-pool-method">
                            <option value="seeded">🎲 <?php _e('Seeded (mismo participante = misma asignación)', 'eipsi-forms'); ?></option>
                            <option value="pure-random">🎰 <?php _e('Pure-random (cada acceso es nuevo)', 'eipsi-forms'); ?></option>
                        </select>
                        <p class="description">
                            <?php _e('Seeded: El participante siempre va al mismo estudio. Random: Distribución completamente aleatoria.', 'eipsi-forms'); ?>
                        </p>
                    </div>

                    <!-- Studies Section -->
                    <h3 class="eipsi-section-title"><?php _e('Estudios y probabilidades', 'eipsi-forms'); ?></h3>
                    <p class="eipsi-section-desc">
                        <?php _e('Agregá los estudios al pool y asigná probabilidades. La suma debe ser 100% (±1% tolerancia permitida).', 'eipsi-forms'); ?>
                    </p>

                    <div id="eipsi-pool-studies-rows">
                        <!-- Dynamic rows added via JS -->
                    </div>

                    <div class="eipsi-pool-actions">
                        <button type="button" class="eipsi-btn-secondary" id="eipsi-add-study-row">
                            + <?php _e('Agregar estudio', 'eipsi-forms'); ?>
                        </button>
                        <button type="button" class="eipsi-btn-secondary" id="eipsi-distribute-probabilities">
                            🔀 <?php _e('Distribuir equitativamente', 'eipsi-forms'); ?>
                        </button>
                    </div>

                    <!-- Progress Bar Total -->
                    <div class="eipsi-probability-total" id="eipsi-probability-total-display">
                        <div class="eipsi-progress-bar">
                            <div class="eipsi-progress-fill invalid" id="eipsi-progress-fill" style="width: 0%"></div>
                        </div>
                        <div class="eipsi-progress-text invalid" id="eipsi-progress-text">
                            <span>0% / 100%</span>
                            <span id="eipsi-progress-status">❌ Incompleto</span>
                        </div>
                    </div>

                    <!-- Shortcode y URL de la página (mostrado después de guardar) -->
                    <div id="eipsi-pool-page-info" style="display: none; margin-top: 20px; padding: 16px; background: #f0f6fc; border-radius: 8px;">
                        <h4 style="margin: 0 0 12px 0; font-size: 14px; color: #1e293b;"><?php _e('Página del pool creada', 'eipsi-forms'); ?></h4>
                        
                        <div style="margin-bottom: 12px;">
                            <label style="display: block; font-size: 12px; color: #64748b; margin-bottom: 4px;"><?php _e('Shortcode:', 'eipsi-forms'); ?></label>
                            <code id="eipsi-pool-shortcode" style="display: block; padding: 8px 12px; background: #1d2327; color: #a5f3fc; border-radius: 4px; font-family: monospace; font-size: 13px;">[eipsi_pool_join pool_id="0"]</code>
                        </div>
                        
                        <div>
                            <label style="display: block; font-size: 12px; color: #64748b; margin-bottom: 4px;"><?php _e('URL de la página:', 'eipsi-forms'); ?></label>
                            <a id="eipsi-pool-page-url" href="#" target="_blank" style="color: #3b82f6; font-size: 13px; text-decoration: none;"></a>
                        </div>
                    </div>

                    <input type="hidden" name="pool_studies_data" id="eipsi-pool-studies-data" value="">
                </form>
            </div>
            <div class="eipsi-modal-footer">
                <button type="button" class="eipsi-btn-ghost" onclick="closeEipsiPoolModal()"><?php _e('Cancelar', 'eipsi-forms'); ?></button>
                <button type="button" class="eipsi-btn-primary" id="eipsi-save-pool-btn" disabled title="<?php _e('La suma debe ser 100%', 'eipsi-forms'); ?>">
                    <?php _e('Guardar pool', 'eipsi-forms'); ?>
                </button>
            </div>
        </div>
    </div>

    </div><!-- End wrap -->

    <?php
}

/**
 * Get pool statistics
 */
function eipsi_get_pool_stats($pool_id) {
    global $wpdb;
    $assignments_table = $wpdb->prefix . 'eipsi_longitudinal_pool_assignments';
    
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
 * Create WordPress page for pool with shortcode
 *
 * @param int    $pool_id   Pool ID
 * @param string $pool_name Pool name
 * @return int|false Page ID or false on failure
 * @since 2.5.0
 */
function eipsi_create_pool_page($pool_id, $pool_name) {
    $pool_slug = 'pool-' . sanitize_title($pool_name);
    
    // Check if page already exists
    $existing_page = get_page_by_path($pool_slug);
    
    if (!$existing_page) {
        $existing_pages = get_posts(array(
            'post_type' => 'page',
            'meta_key' => 'eipsi_pool_id',
            'meta_value' => $pool_id,
            'posts_per_page' => 1
        ));
        
        if (!empty($existing_pages)) {
            $existing_page = $existing_pages[0];
        }
    }
    
    if ($existing_page) {
        update_post_meta($existing_page->ID, 'eipsi_pool_id', $pool_id);
        return $existing_page->ID;
    }
    
    // Create new page
    $page_title = sprintf(__('Pool: %s', 'eipsi-forms'), $pool_name);
    $page_content = '[eipsi_pool_join pool_id="' . esc_attr($pool_id) . '"]';
    
    $page_id = wp_insert_post(array(
        'post_title' => $page_title,
        'post_name' => $pool_slug,
        'post_content' => $page_content,
        'post_status' => 'publish',
        'post_type' => 'page',
        'meta_input' => array(
            'eipsi_pool_id' => $pool_id
        )
    ));
    
    if (is_wp_error($page_id)) {
        error_log('[EIPSI] Failed to create pool page: ' . $page_id->get_error_message());
        return false;
    }
    
    return $page_id;
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

/**
 * Handle pool save action (admin-post.php)
 * Creates/updates pool and auto-creates WordPress page
 *
 * @since 2.5.0
 */
add_action('admin_post_eipsi_save_pool', 'eipsi_handle_save_pool');

function eipsi_handle_save_pool() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['pool_nonce'] ?? '', 'eipsi_save_pool_nonce')) {
        wp_die(__('Error de seguridad. Por favor, recargá la página.', 'eipsi-forms'));
    }
    
    // Check permissions
    if (!function_exists('eipsi_user_can_manage_longitudinal') || !eipsi_user_can_manage_longitudinal()) {
        wp_die(__('No tenés permisos para realizar esta acción.', 'eipsi-forms'));
    }
    
    global $wpdb;
    
    $pools_table = $wpdb->prefix . 'eipsi_longitudinal_pools';
    
    // Get form data
    $pool_id = intval($_POST['pool_id'] ?? 0);
    $pool_name = sanitize_text_field($_POST['pool_name'] ?? '');
    $pool_description = sanitize_textarea_field($_POST['pool_description'] ?? '');
    $method = sanitize_key($_POST['method'] ?? 'seeded');
    $studies_data = json_decode(stripslashes($_POST['pool_studies_data'] ?? '[]'), true);
    
    if (empty($pool_name)) {
        wp_die(__('El nombre del pool es obligatorio.', 'eipsi-forms'));
    }
    
    // Extract study IDs and probabilities
    $study_ids = array();
    $probabilities = array();
    $total_prob = 0;
    
    foreach ($studies_data as $item) {
        if (!empty($item['study_id']) && isset($item['probability'])) {
            $study_ids[] = intval($item['study_id']);
            $prob = floatval($item['probability']);
            $probabilities[] = $prob;
            $total_prob += $prob;
        }
    }
    
    // Validate total is 100%
    if (abs($total_prob - 100) > 0.01) {
        wp_die(__('La suma de probabilidades debe ser exactamente 100%.', 'eipsi-forms'));
    }
    
    $now = current_time('mysql');
    
    if ($pool_id > 0) {
        // Update existing pool
        $wpdb->update(
            $pools_table,
            array(
                'pool_name' => $pool_name,
                'pool_description' => $pool_description,
                'studies' => json_encode($study_ids),
                'probabilities' => json_encode($probabilities),
                'method' => $method,
                'updated_at' => $now
            ),
            array('id' => $pool_id),
            array('%s', '%s', '%s', '%s', '%s', '%s'),
            array('%d')
        );
    } else {
        // Create new pool
        $wpdb->insert(
            $pools_table,
            array(
                'pool_name' => $pool_name,
                'pool_description' => $pool_description,
                'studies' => json_encode($study_ids),
                'probabilities' => json_encode($probabilities),
                'method' => $method,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        $pool_id = $wpdb->insert_id;
    }
    
    // Auto-create WordPress page for this pool
    $page_id = eipsi_create_pool_page($pool_id, $pool_name);
    $page_url = $page_id ? get_permalink($page_id) : null;
    
    // Redirect back with success message
    $redirect_url = admin_url('admin.php?page=eipsi-longitudinal-study&tab=pool-hub&message=pool_' . ($pool_id > 0 ? 'updated' : 'created'));
    
    wp_redirect($redirect_url);
    exit;
}
