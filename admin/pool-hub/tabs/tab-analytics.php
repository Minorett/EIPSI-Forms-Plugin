<?php
/**
 * Pool Hub - Tab: Analytics
 * 
 * Fase 2: Migrar contenido de longitudinal-pool-dashboard.php
 * - Gráficos de distribución
 * - Métricas de completitud
 * - Activity feed
 * - Exportar CSV
 * 
 * @package EIPSI_Forms
 * @since 2.2.0
 */

// Load analytics service
$analytics = array();
if ( class_exists( 'EIPSI_Pool_Dashboard_Service' ) && $pool_id ) {
    $service = new EIPSI_Pool_Dashboard_Service();
    $analytics = $service->get_pool_analytics( $pool_id );
}

?>
<div class="eipsi-tab-analytics">
    <div class="notice notice-info">
        <p>
            <strong><?php esc_html_e( '🚧 Fase 2: Analytics', 'eipsi-forms' ); ?></strong><br>
            <?php esc_html_e( 'Aquí se migrará el contenido actual de Pool Analytics: gráficos Chart.js, métricas de asignación, activity feed, y exportación de datos.', 'eipsi-forms' ); ?>
        </p>
    </div>

    <?php if ( ! empty( $analytics ) ) : ?>
        <div class="eipsi-analytics-grid">
            <div class="eipsi-analytics-card">
                <h4><?php esc_html_e( 'Total Asignaciones', 'eipsi-forms' ); ?></h4>
                <span class="eipsi-analytics-number">
                    <?php echo esc_html( $analytics['summary']['total_assignments'] ?? 0 ); ?>
                </span>
            </div>
            
            <div class="eipsi-analytics-card">
                <h4><?php esc_html_e( 'Completitud Promedio', 'eipsi-forms' ); ?></h4>
                <span class="eipsi-analytics-number">
                    <?php 
                    $rates = $analytics['completion_rates'] ?? array();
                    $avg = ! empty( $rates ) ? round( array_sum( $rates ) / count( $rates ), 1 ) : 0;
                    echo esc_html( $avg . '%' );
                    ?>
                </span>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
    .eipsi-analytics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }

    .eipsi-analytics-card {
        background: #f8fafc;
        padding: 20px;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
    }

    .eipsi-analytics-card h4 {
        margin: 0 0 12px;
        font-size: 13px;
        font-weight: 500;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }

    .eipsi-analytics-number {
        font-size: 32px;
        font-weight: 600;
        color: #3B6CAA;
    }
</style>
