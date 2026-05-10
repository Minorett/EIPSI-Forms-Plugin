<?php
/**
 * Cron Diagnostic Tab
 * 
 * Shows cron job status and helps diagnose issues
 * 
 * @package EIPSI_Forms
 * @since 2.6.1
 */

if (!defined('ABSPATH')) {
    exit;
}

// Load diagnostic functions
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/cron-diagnostic.php';

$diagnostics = eipsi_get_cron_diagnostic();
$has_issues = !empty($diagnostics['issues']);
?>

<div class="eipsi-cron-diagnostic-container" style="max-width: 1200px; margin: 20px auto; padding: 20px;">
    
    <!-- Header -->
    <div style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h2 style="margin: 0 0 10px 0; display: flex; align-items: center; gap: 10px;">
            🔍 Diagnóstico de Cron Jobs
            <?php if ($has_issues): ?>
                <span style="background: #dc3545; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600;">
                    <?php echo count($diagnostics['issues']); ?> Problemas
                </span>
            <?php else: ?>
                <span style="background: #28a745; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600;">
                    ✓ Todo OK
                </span>
            <?php endif; ?>
        </h2>
        <p style="margin: 0; color: #666;">
            Esta herramienta verifica que los cron jobs se estén ejecutando correctamente.
        </p>
    </div>

    <!-- Issues Alert -->
    <?php if ($has_issues): ?>
    <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 16px; margin-bottom: 20px; border-radius: 4px;">
        <h3 style="margin: 0 0 12px 0; color: #856404;">⚠️ Problemas Detectados</h3>
        <ul style="margin: 0; padding-left: 20px; color: #856404;">
            <?php foreach ($diagnostics['issues'] as $issue): ?>
                <li><?php echo esc_html($issue); ?></li>
            <?php endforeach; ?>
        </ul>
        
        <?php if (!$diagnostics['wp_cron_disabled']): ?>
        <div style="margin-top: 16px; padding: 12px; background: #fff; border-radius: 4px;">
            <strong>🚨 ACCIÓN REQUERIDA:</strong>
            <p style="margin: 8px 0;">
                Necesitás configurar un cron real del sistema. 
                <a href="<?php echo esc_url(plugins_url('SETUP-REAL-CRON.md', dirname(__FILE__, 2))); ?>" target="_blank" style="color: #0284c7; font-weight: 600;">
                    Ver guía de configuración →
                </a>
            </p>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- System Info -->
    <div style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h3 style="margin: 0 0 16px 0;">📊 Información del Sistema</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px;">
            <div>
                <strong>DISABLE_WP_CRON:</strong>
                <span style="color: <?php echo $diagnostics['wp_cron_disabled'] ? '#28a745' : '#dc3545'; ?>; font-weight: 600;">
                    <?php echo $diagnostics['wp_cron_disabled'] ? '✓ Activado' : '✗ Desactivado'; ?>
                </span>
            </div>
            <div>
                <strong>Hora WordPress:</strong>
                <?php echo esc_html($diagnostics['current_time']); ?>
            </div>
            <div>
                <strong>Hora Servidor:</strong>
                <?php echo esc_html($diagnostics['server_time']); ?>
            </div>
            <div>
                <strong>Zona Horaria:</strong>
                <?php echo esc_html($diagnostics['timezone']); ?>
            </div>
        </div>
    </div>

    <!-- Cron Jobs Status -->
    <div style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h3 style="margin: 0 0 16px 0;">⏰ Estado de Cron Jobs</h3>
        <table class="wp-list-table widefat fixed striped" style="margin-top: 16px;">
            <thead>
                <tr>
                    <th style="width: 40%;">Cron Job</th>
                    <th style="width: 15%;">Frecuencia</th>
                    <th style="width: 15%;">Estado</th>
                    <th style="width: 20%;">Próxima Ejecución</th>
                    <th style="width: 10%;">Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($diagnostics['cron_jobs'] as $hook => $job): ?>
                <tr>
                    <td>
                        <code style="font-size: 12px;"><?php echo esc_html($hook); ?></code>
                    </td>
                    <td><?php echo esc_html($job['expected_schedule']); ?></td>
                    <td>
                        <?php if ($job['is_scheduled']): ?>
                            <?php if ($job['is_overdue']): ?>
                                <span style="color: #dc3545; font-weight: 600;">⚠️ Atrasado</span>
                            <?php else: ?>
                                <span style="color: #28a745; font-weight: 600;">✓ Programado</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="color: #dc3545; font-weight: 600;">✗ No programado</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($job['next_run']): ?>
                            <?php echo esc_html($job['next_run']); ?>
                            <br>
                            <small style="color: #666;">(<?php echo esc_html($job['next_run_in']); ?>)</small>
                        <?php else: ?>
                            <em style="color: #999;">-</em>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button type="button" class="button button-small eipsi-force-run-cron" data-hook="<?php echo esc_attr($hook); ?>">
                            ▶️ Ejecutar
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Recent Executions -->
    <?php if (!empty($diagnostics['recent_executions'])): ?>
    <div style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h3 style="margin: 0 0 16px 0;">📝 Ejecuciones Recientes</h3>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Cron Job</th>
                    <th>Última Ejecución</th>
                    <th>Hace</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($diagnostics['recent_executions'] as $transient => $execution): ?>
                <tr>
                    <td><code style="font-size: 12px;"><?php echo esc_html($transient); ?></code></td>
                    <td><?php echo esc_html($execution['last_run']); ?></td>
                    <td><?php echo esc_html($execution['minutes_ago']); ?> minutos</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Actions -->
    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h3 style="margin: 0 0 16px 0;">🔧 Acciones</h3>
        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
            <button type="button" id="eipsi-refresh-diagnostic" class="button button-primary">
                🔄 Actualizar Diagnóstico
            </button>
            <button type="button" id="eipsi-reschedule-all-crons" class="button button-secondary">
                ⏰ Reprogramar Todos los Crons
            </button>
            <a href="<?php echo esc_url(admin_url('tools.php?page=crontrol_admin_manage_page')); ?>" class="button button-secondary" target="_blank">
                🔍 Ver WP Crontrol (si está instalado)
            </a>
        </div>
    </div>

</div>

<script>
jQuery(document).ready(function($) {
    
    // Refresh diagnostic
    $('#eipsi-refresh-diagnostic').on('click', function() {
        location.reload();
    });
    
    // Reschedule all crons
    $('#eipsi-reschedule-all-crons').on('click', function() {
        if (!confirm('¿Estás seguro de que querés reprogramar todos los cron jobs?')) {
            return;
        }
        
        const $btn = $(this);
        $btn.prop('disabled', true).text('⏳ Reprogramando...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'eipsi_reschedule_all_crons',
                nonce: '<?php echo wp_create_nonce('eipsi_admin_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('✓ Todos los cron jobs han sido reprogramados exitosamente.');
                    location.reload();
                } else {
                    alert('✗ Error: ' + (response.data || 'Unknown error'));
                }
            },
            error: function() {
                alert('✗ Error de conexión al reprogramar cron jobs.');
            },
            complete: function() {
                $btn.prop('disabled', false).text('⏰ Reprogramar Todos los Crons');
            }
        });
    });
    
    // Force run individual cron
    $('.eipsi-force-run-cron').on('click', function() {
        const $btn = $(this);
        const hook = $btn.data('hook');
        
        if (!confirm(`¿Ejecutar manualmente el cron job "${hook}"?`)) {
            return;
        }
        
        $btn.prop('disabled', true).text('⏳');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'eipsi_force_run_cron',
                hook: hook,
                nonce: '<?php echo wp_create_nonce('eipsi_admin_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('✓ Cron job ejecutado. Revisá los logs para ver los resultados.');
                    location.reload();
                } else {
                    alert('✗ Error: ' + (response.data || 'Unknown error'));
                }
            },
            error: function() {
                alert('✗ Error de conexión.');
            },
            complete: function() {
                $btn.prop('disabled', false).text('▶️ Ejecutar');
            }
        });
    });
    
});
</script>
