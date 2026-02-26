<?php
/**
 * Longitudinal Pool Dashboard
 * Monitoring dashboard for longitudinal pools.
 *
 * @package EIPSI_Forms
 * @since 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Render pool dashboard page.
 */
function eipsi_display_pool_dashboard_page() {
    if ( ! function_exists( 'eipsi_user_can_manage_longitudinal' ) || ! eipsi_user_can_manage_longitudinal() ) {
        wp_die( esc_html__( 'Unauthorized', 'eipsi-forms' ) );
    }

    global $wpdb;

    $pools_table = $wpdb->prefix . 'eipsi_longitudinal_pools';
    $pools = $wpdb->get_results( "SELECT id, pool_name, status FROM {$pools_table} ORDER BY created_at DESC", ARRAY_A );

    $pool_id = isset( $_GET['pool_id'] ) ? absint( $_GET['pool_id'] ) : 0;
    if ( ! $pool_id && ! empty( $pools ) ) {
        $pool_id = (int) $pools[0]['id'];
    }

    $active_pool = null;
    if ( $pool_id ) {
        $active_pool = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$pools_table} WHERE id = %d", $pool_id ),
            ARRAY_A
        );
    }

    wp_enqueue_style( 'eipsi-pool-dashboard', EIPSI_FORMS_PLUGIN_URL . 'assets/css/eipsi-pool-dashboard.css', array(), EIPSI_FORMS_VERSION );
    wp_enqueue_script( 'eipsi-pool-dashboard-chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js', array(), '4.4.2', true );
    wp_enqueue_script( 'eipsi-pool-dashboard', EIPSI_FORMS_PLUGIN_URL . 'assets/js/eipsi-pool-dashboard.js', array( 'jquery', 'eipsi-pool-dashboard-chartjs' ), EIPSI_FORMS_VERSION, true );

    wp_localize_script( 'eipsi-pool-dashboard', 'eipsiPoolDashboard', array(
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'eipsi_pool_dashboard_nonce' ),
        'poolId'  => $pool_id,
        'strings' => array(
            'loading' => __( 'Cargando analytics...', 'eipsi-forms' ),
            'noPool' => __( 'Seleccioná un pool para ver analytics.', 'eipsi-forms' ),
            'noData' => __( 'Todavía no hay asignaciones en este pool.', 'eipsi-forms' ),
            'exporting' => __( 'Preparando exportación...', 'eipsi-forms' ),
            'exportError' => __( 'No se pudo exportar el CSV.', 'eipsi-forms' ),
        ),
    ) );

    ?>
    <div class="wrap eipsi-pool-dashboard">
        <div class="eipsi-pool-dashboard-header">
            <div>
                <h1><?php esc_html_e( 'Pool Analytics', 'eipsi-forms' ); ?></h1>
                <p class="description"><?php esc_html_e( 'Seguimiento comparativo de asignaciones, progreso y deserción en estudios longitudinales.', 'eipsi-forms' ); ?></p>
            </div>
            <div class="eipsi-pool-dashboard-actions">
                <label for="eipsi-pool-selector" class="screen-reader-text"><?php esc_html_e( 'Seleccionar pool', 'eipsi-forms' ); ?></label>
                <select id="eipsi-pool-selector" class="regular-text">
                    <?php if ( empty( $pools ) ) : ?>
                        <option value="0"><?php esc_html_e( 'No hay pools disponibles', 'eipsi-forms' ); ?></option>
                    <?php else : ?>
                        <?php foreach ( $pools as $pool ) : ?>
                            <option value="<?php echo esc_attr( $pool['id'] ); ?>" <?php selected( (int) $pool['id'], $pool_id ); ?>>
                                <?php echo esc_html( $pool['pool_name'] ); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <button class="button button-secondary" id="eipsi-export-pool-csv" <?php echo $pool_id ? '' : 'disabled'; ?>>
                    <?php esc_html_e( 'Exportar CSV', 'eipsi-forms' ); ?>
                </button>
            </div>
        </div>

        <?php if ( empty( $pools ) ) : ?>
            <div class="notice notice-warning">
                <p><?php esc_html_e( 'Todavía no hay pools creados. Creá un pool para ver analytics.', 'eipsi-forms' ); ?></p>
            </div>
        <?php endif; ?>

        <div class="eipsi-pool-dashboard-body" data-pool-id="<?php echo esc_attr( $pool_id ); ?>">
            <section class="eipsi-pool-summary-card">
                <div class="eipsi-pool-summary-head">
                    <div>
                        <h2 id="eipsi-pool-name"><?php echo esc_html( $active_pool['pool_name'] ?? '' ); ?></h2>
                        <p id="eipsi-pool-description" class="description"></p>
                    </div>
                    <span id="eipsi-pool-status" class="eipsi-status-pill"></span>
                </div>
                <div class="eipsi-pool-summary-grid">
                    <div class="eipsi-summary-item">
                        <span class="label"><?php esc_html_e( 'Método', 'eipsi-forms' ); ?></span>
                        <span class="value" id="eipsi-pool-method">—</span>
                    </div>
                    <div class="eipsi-summary-item">
                        <span class="label"><?php esc_html_e( 'Fecha de creación', 'eipsi-forms' ); ?></span>
                        <span class="value" id="eipsi-pool-created">—</span>
                    </div>
                    <div class="eipsi-summary-item">
                        <span class="label"><?php esc_html_e( 'Asignaciones totales', 'eipsi-forms' ); ?></span>
                        <span class="value" id="eipsi-pool-total">—</span>
                    </div>
                </div>
            </section>

            <section class="eipsi-pool-dashboard-grid">
                <div class="eipsi-pool-dashboard-panel">
                    <h3><?php esc_html_e( 'Distribución por estudio', 'eipsi-forms' ); ?></h3>
                    <div class="eipsi-chart-wrapper">
                        <canvas id="eipsi-pool-distribution-chart" height="240"></canvas>
                    </div>
                    <p class="description eipsi-chart-note"><?php esc_html_e( 'Comparativo entre distribución configurada (anillo interno) y asignaciones reales (anillo externo).', 'eipsi-forms' ); ?></p>
                </div>
                <div class="eipsi-pool-dashboard-panel">
                    <h3><?php esc_html_e( 'Completion rate por estudio', 'eipsi-forms' ); ?></h3>
                    <div class="eipsi-chart-wrapper">
                        <canvas id="eipsi-pool-completion-chart" height="240"></canvas>
                    </div>
                </div>
            </section>

            <section class="eipsi-pool-dashboard-panel">
                <div class="eipsi-panel-header">
                    <h3><?php esc_html_e( 'Breakdown por estudio', 'eipsi-forms' ); ?></h3>
                    <span class="eipsi-panel-helper" id="eipsi-pool-breakdown-helper"></span>
                </div>
                <div class="eipsi-table-wrapper">
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Estudio', 'eipsi-forms' ); ?></th>
                                <th><?php esc_html_e( 'Asignaciones', 'eipsi-forms' ); ?></th>
                                <th><?php esc_html_e( '% del pool', 'eipsi-forms' ); ?></th>
                                <th><?php esc_html_e( 'Completados', 'eipsi-forms' ); ?></th>
                                <th><?php esc_html_e( 'En progreso', 'eipsi-forms' ); ?></th>
                                <th><?php esc_html_e( 'Dropouts', 'eipsi-forms' ); ?></th>
                                <th><?php esc_html_e( 'Completion rate', 'eipsi-forms' ); ?></th>
                            </tr>
                        </thead>
                        <tbody id="eipsi-pool-breakdown-body">
                            <tr>
                                <td colspan="7" class="eipsi-placeholder">
                                    <?php esc_html_e( 'Cargando datos del pool...', 'eipsi-forms' ); ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="eipsi-pool-dashboard-panel">
                <h3><?php esc_html_e( 'Actividad reciente', 'eipsi-forms' ); ?></h3>
                <ul class="eipsi-activity-feed" id="eipsi-pool-activity-feed">
                    <li class="eipsi-placeholder"><?php esc_html_e( 'Cargando actividad reciente...', 'eipsi-forms' ); ?></li>
                </ul>
            </section>
        </div>
    </div>
    <?php
}
