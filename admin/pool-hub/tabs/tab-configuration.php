<?php
/**
 * Pool Hub - Tab: Configuración
 * 
 * Fase 2: Migrar contenido de randomization-pools-page.php
 * - Formulario de edición de pool
 * - Selector de estudios y probabilidades
 * - Preview de shortcode
 * 
 * @package EIPSI_Forms
 * @since 2.2.0
 */

?>
<div class="eipsi-tab-configuration">
    <div class="notice notice-info">
        <p>
            <strong><?php esc_html_e( '🚧 Fase 2: Configuración', 'eipsi-forms' ); ?></strong><br>
            <?php esc_html_e( 'Aquí se migrará el contenido actual de Longitudinal Pools: gestión de estudios, probabilidades, método de asignación, y preview del shortcode.', 'eipsi-forms' ); ?>
        </p>
    </div>

    <h3><?php esc_html_e( 'Resumen del Pool', 'eipsi-forms' ); ?></h3>
    
    <?php if ( $active_pool ) : ?>
        <table class="widefat striped">
            <tr>
                <th><?php esc_html_e( 'Nombre', 'eipsi-forms' ); ?></th>
                <td><?php echo esc_html( $active_pool['pool_name'] ); ?></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Descripción', 'eipsi-forms' ); ?></th>
                <td><?php echo esc_html( $active_pool['pool_description'] ?? '-' ); ?></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Método', 'eipsi-forms' ); ?></th>
                <td><?php echo esc_html( $active_pool['method'] ); ?></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Estado', 'eipsi-forms' ); ?></th>
                <td><?php echo esc_html( ucfirst( $active_pool['status'] ) ); ?></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Creado', 'eipsi-forms' ); ?></th>
                <td><?php echo esc_html( $active_pool['created_at'] ); ?></td>
            </tr>
        </table>

        <h4><?php esc_html_e( 'Shortcode', 'eipsi-forms' ); ?></h4>
        <code class="eipsi-shortcode-preview">
            [eipsi_pool_join pool_id="<?php echo esc_attr( $pool_id ); ?>"]
        </code>
    <?php endif; ?>
</div>
