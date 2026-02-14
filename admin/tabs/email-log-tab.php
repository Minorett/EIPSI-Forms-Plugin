<?php
/**
 * Tab: Email Log & Dropout Management
 * 
 * Dashboard de auditoría de emails + gestión de participantes en riesgo
 * 
 * @package EIPSI_Forms
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Obtener survey_id actual (si está en contexto de un estudio específico)
$survey_id = isset($_GET['survey_id']) ? absint($_GET['survey_id']) : 0;

// Stats resumen iniciales
if ($survey_id > 0) {
    // Con filtro de survey_id
    $sent_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}survey_email_log WHERE status = %s AND survey_id = %d",
        'sent',
        $survey_id
    ));
    $failed_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}survey_email_log WHERE status = %s AND survey_id = %d",
        'failed',
        $survey_id
    ));
    $total_emails = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}survey_email_log WHERE survey_id = %d",
        $survey_id
    ));
} else {
    // Sin filtro de survey_id
    $sent_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}survey_email_log WHERE status = %s",
        'sent'
    ));
    $failed_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}survey_email_log WHERE status = %s",
        'failed'
    ));
    $total_emails = $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->prefix}survey_email_log"
    );
}

$success_rate = $total_emails > 0 ? round(($sent_count / $total_emails) * 100, 1) : 0;

// Enqueue high contrast styles
wp_enqueue_style('eipsi-email-log', EIPSI_FORMS_PLUGIN_URL . 'admin/css/email-log.css', array(), EIPSI_FORMS_VERSION);
wp_enqueue_style('eipsi-high-contrast', EIPSI_FORMS_PLUGIN_URL . 'assets/css/eipsi-high-contrast.css', array('eipsi-email-log'), EIPSI_FORMS_VERSION);
?>

<div class="eipsi-email-log-wrap">
    
    <!-- Header con título y descripción -->
    <div class="eipsi-tab-header">
        <h1>📧 <?php esc_html_e('Email Log & Dropout Management', 'eipsi-forms'); ?></h1>
        <p class="description">
            <?php esc_html_e('Auditoría de emails enviados y gestión de participantes en riesgo de abandono.', 'eipsi-forms'); ?>
        </p>
    </div>

    <!-- Cards de estadísticas -->
    <div class="eipsi-email-stats-cards">
        <div class="eipsi-stat-card eipsi-stat-success">
            <span class="stat-label">✅ <?php esc_html_e('Enviados Exitosamente', 'eipsi-forms'); ?></span>
            <span class="stat-value"><?php echo (int)$sent_count; ?></span>
            <span class="stat-sublabel"><?php printf(__('%s%% tasa de entrega', 'eipsi-forms'), $success_rate); ?></span>
        </div>
        <div class="eipsi-stat-card eipsi-stat-error">
            <span class="stat-label">❌ <?php esc_html_e('Fallidos', 'eipsi-forms'); ?></span>
            <span class="stat-value"><?php echo (int)$failed_count; ?></span>
        </div>
        <div class="eipsi-stat-card eipsi-stat-total">
            <span class="stat-label">📊 <?php esc_html_e('Total Emails', 'eipsi-forms'); ?></span>
            <span class="stat-value"><?php echo (int)$total_emails; ?></span>
        </div>
    </div>

    <!-- Tabs para Email Log y Dropout Management -->
    <div class="eipsi-sub-tabs">
        <button class="eipsi-sub-tab-button active" data-tab="email-log">
            📧 <?php esc_html_e('Email Log', 'eipsi-forms'); ?>
        </button>
        <button class="eipsi-sub-tab-button" data-tab="dropout-management">
            🚨 <?php esc_html_e('Dropout Management', 'eipsi-forms'); ?>
        </button>
    </div>

    <!-- Sección: Email Log -->
    <div id="eipsi-email-log-section" class="eipsi-tab-content active">
        
        <!-- Filtros -->
        <div class="eipsi-filters-toolbar">
            <div class="eipsi-filter-group">
                <label><?php esc_html_e('Tipo:', 'eipsi-forms'); ?></label>
                <select id="filter-email-type" class="eipsi-filter-select">
                    <option value=""><?php esc_html_e('Todos', 'eipsi-forms'); ?></option>
                    <option value="welcome"><?php esc_html_e('Bienvenida', 'eipsi-forms'); ?></option>
                    <option value="reminder"><?php esc_html_e('Recordatorio', 'eipsi-forms'); ?></option>
                    <option value="confirmation"><?php esc_html_e('Confirmación', 'eipsi-forms'); ?></option>
                    <option value="recovery"><?php esc_html_e('Recuperación', 'eipsi-forms'); ?></option>
                </select>
            </div>
            <div class="eipsi-filter-group">
                <label><?php esc_html_e('Estado:', 'eipsi-forms'); ?></label>
                <select id="filter-email-status" class="eipsi-filter-select">
                    <option value=""><?php esc_html_e('Todos', 'eipsi-forms'); ?></option>
                    <option value="sent"><?php esc_html_e('Enviados', 'eipsi-forms'); ?></option>
                    <option value="failed"><?php esc_html_e('Fallidos', 'eipsi-forms'); ?></option>
                </select>
            </div>
            <div class="eipsi-filter-group">
                <label><?php esc_html_e('Fecha Desde:', 'eipsi-forms'); ?></label>
                <input type="date" id="filter-date-from" class="eipsi-filter-input">
            </div>
            <div class="eipsi-filter-group">
                <label><?php esc_html_e('Fecha Hasta:', 'eipsi-forms'); ?></label>
                <input type="date" id="filter-date-to" class="eipsi-filter-input">
            </div>
            <button id="apply-email-filters" class="button button-primary">
                🔍 <?php esc_html_e('Filtrar', 'eipsi-forms'); ?>
            </button>
            <button id="reset-email-filters" class="button button-secondary">
                🔄 <?php esc_html_e('Limpiar', 'eipsi-forms'); ?>
            </button>
            <button id="export-email-logs" class="button button-secondary">
                📥 <?php esc_html_e('Exportar CSV', 'eipsi-forms'); ?>
            </button>
        </div>

        <!-- Tabla de Email Log -->
        <div class="eipsi-email-log-table-wrapper">
            <table class="wp-list-table widefat fixed striped eipsi-email-log-table">
                <thead>
                    <tr>
                        <th style="width: 15%;"><?php esc_html_e('Fecha', 'eipsi-forms'); ?></th>
                        <th style="width: 15%;"><?php esc_html_e('Tipo', 'eipsi-forms'); ?></th>
                        <th style="width: 20%;"><?php esc_html_e('Participante', 'eipsi-forms'); ?></th>
                        <th style="width: 15%;"><?php esc_html_e('Email', 'eipsi-forms'); ?></th>
                        <th style="width: 10%;"><?php esc_html_e('Estado', 'eipsi-forms'); ?></th>
                        <th style="width: 25%;"><?php esc_html_e('Acciones', 'eipsi-forms'); ?></th>
                    </tr>
                </thead>
                <tbody id="eipsi-email-log-body">
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px;">
                            <span class="spinner is-active" style="display: inline-block; vertical-align: middle;"></span>
                            <span style="margin-left: 10px;"><?php esc_html_e('Cargando emails...', 'eipsi-forms'); ?></span>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <!-- Paginación -->
            <div class="eipsi-pagination-wrapper">
                <button id="prev-email-page" class="button button-small" disabled>
                    ← <?php esc_html_e('Anterior', 'eipsi-forms'); ?>
                </button>
                <span id="email-page-info" style="margin: 0 15px;">Página 1</span>
                <button id="next-email-page" class="button button-small">
                    <?php esc_html_e('Siguiente', 'eipsi-forms'); ?> →
                </button>
            </div>
        </div>
    </div>

    <!-- Sección: Dropout Management -->
    <div id="eipsi-dropout-section" class="eipsi-tab-content">
        
        <!-- Filtros de riesgo -->
        <div class="eipsi-filters-toolbar">
            <div class="eipsi-filter-group">
                <label><?php esc_html_e('Días de retraso:', 'eipsi-forms'); ?></label>
                <select id="filter-risk-days" class="eipsi-filter-select">
                    <option value="3">> 3 días</option>
                    <option value="7" selected>> 7 días</option>
                    <option value="14">> 14 días</option>
                    <option value="30">> 30 días</option>
                </select>
            </div>
            <button id="apply-dropout-filters" class="button button-primary">
                🔍 <?php esc_html_e('Buscar en Riesgo', 'eipsi-forms'); ?>
            </button>
        </div>

        <!-- Stats de Dropout -->
        <div class="eipsi-dropout-stats">
            <div class="eipsi-dropout-stat">
                <span class="stat-label">🚨 <?php esc_html_e('En Riesgo', 'eipsi-forms'); ?></span>
                <span class="stat-value" id="at-risk-count">-</span>
            </div>
            <div class="eipsi-dropout-stat">
                <span class="stat-label">⏳ <?php esc_html_e('Pendientes', 'eipsi-forms'); ?></span>
                <span class="stat-value" id="pending-count">-</span>
            </div>
            <div class="eipsi-dropout-stat">
                <span class="stat-label">📧 <?php esc_html_e('Recordatorios Hoy', 'eipsi-forms'); ?></span>
                <span class="stat-value" id="reminders-today">-</span>
            </div>
        </div>

        <!-- Tabla de Participantes en Riesgo -->
        <div class="eipsi-dropout-table-wrapper">
            <table class="wp-list-table widefat fixed striped eipsi-dropout-table">
                <thead>
                    <tr>
                        <th style="width: 3%;">
                            <input type="checkbox" id="select-all-at-risk">
                        </th>
                        <th style="width: 20%;"><?php esc_html_e('Participante', 'eipsi-forms'); ?></th>
                        <th style="width: 15%;"><?php esc_html_e('Toma Pendiente', 'eipsi-forms'); ?></th>
                        <th style="width: 12%;"><?php esc_html_e('Vencimiento', 'eipsi-forms'); ?></th>
                        <th style="width: 12%;"><?php esc_html_e('Última Actividad', 'eipsi-forms'); ?></th>
                        <th style="width: 8%;"><?php esc_html_e('Estado', 'eipsi-forms'); ?></th>
                        <th style="width: 30%;"><?php esc_html_e('Acciones', 'eipsi-forms'); ?></th>
                    </tr>
                </thead>
                <tbody id="eipsi-dropout-body">
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px;">
                            <span class="spinner is-active" style="display: inline-block; vertical-align: middle;"></span>
                            <span style="margin-left: 10px;"><?php esc_html_e('Cargando participantes en riesgo...', 'eipsi-forms'); ?></span>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <!-- Acciones Bulk -->
            <div class="eipsi-bulk-actions-wrapper">
                <label><?php esc_html_e('Acciones en lote:', 'eipsi-forms'); ?></label>
                <select id="bulk-action-select" class="eipsi-filter-select" disabled>
                    <option value=""><?php esc_html_e('Selecciona acción...', 'eipsi-forms'); ?></option>
                    <option value="send_reminder"><?php esc_html_e('Enviar recordatorio', 'eipsi-forms'); ?></option>
                    <option value="extend_7"><?php esc_html_e('Extender 7 días', 'eipsi-forms'); ?></option>
                    <option value="extend_14"><?php esc_html_e('Extender 14 días', 'eipsi-forms'); ?></option>
                    <option value="extend_30"><?php esc_html_e('Extender 30 días', 'eipsi-forms'); ?></option>
                </select>
                <button id="apply-bulk-action" class="button button-primary" disabled>
                    <?php esc_html_e('Aplicar', 'eipsi-forms'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Ver detalles de email -->
<div id="eipsi-email-details-modal" class="eipsi-modal" style="display: none;">
    <div class="eipsi-modal-content">
        <span class="eipsi-modal-close">&times;</span>
        <h3><?php esc_html_e('Detalles del Email', 'eipsi-forms'); ?></h3>
        <div id="eipsi-email-details-body"></div>
    </div>
</div>

<!-- Modal: Extender vencimiento -->
<div id="eipsi-extend-modal" class="eipsi-modal" style="display: none;">
    <div class="eipsi-modal-content eipsi-modal-small">
        <span class="eipsi-modal-close">&times;</span>
        <h3><?php esc_html_e('Extender Vencimiento', 'eipsi-forms'); ?></h3>
        <p><?php esc_html_e('¿Cuántos días deseas extender el vencimiento?', 'eipsi-forms'); ?></p>
        <input type="number" id="extend-days" class="regular-text" value="7" min="1" max="365">
        <input type="hidden" id="extend-assignment-id">
        <div style="margin-top: 20px; text-align: right;">
            <button id="confirm-extend" class="button button-primary">
                <?php esc_html_e('Extender', 'eipsi-forms'); ?>
            </button>
            <button class="button button-secondary eipsi-modal-cancel">
                <?php esc_html_e('Cancelar', 'eipsi-forms'); ?>
            </button>
        </div>
    </div>
</div>
