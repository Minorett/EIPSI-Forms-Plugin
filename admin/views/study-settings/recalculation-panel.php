<div class="eipsi-recalculation-panel">
    <h3><?php esc_html_e( 'Recalcular tiempos de tomas futuras', 'eipsi-forms' ); ?></h3>

    <div class="notice notice-warning inline">
        <p><?php esc_html_e( 'Esta acción modificará las fechas de disponibilidad y vencimiento de las tomas que aún no han sido completadas ni expiradas.', 'eipsi-forms' ); ?></p>
        <ul>
            <li><?php esc_html_e( 'Las tomas ya completadas no se modificarán.', 'eipsi-forms' ); ?></li>
            <li><?php esc_html_e( 'Las tomas expiradas no se modificarán.', 'eipsi-forms' ); ?></li>
            <li><?php esc_html_e( 'Se generará un registro de auditoría completo con opción de deshacer.', 'eipsi-forms' ); ?></li>
        </ul>
    </div>

    <div class="eipsi-recalc-preview">
        <p><?php echo esc_html( sprintf( _n( '%d participante será afectado.', '%d participantes serán afectados.', $preview['affected_participants'], 'eipsi-forms' ), $preview['affected_participants'] ) ); ?></p>
        <p><?php echo esc_html( sprintf( _n( '%d toma será recalculada.', '%d tomas serán recalculadas.', $preview['waves_to_update'], 'eipsi-forms' ), $preview['waves_to_update'] ) ); ?></p>
    </div>

    <div class="eipsi-form-row" style="margin-top: 16px;">
        <label class="eipsi-checkbox-label">
            <input type="checkbox" id="confirm-recalculation" name="confirm_recalculation">
            <?php esc_html_e( 'Entiendo que esta acción modificará tiempos de participantes activos.', 'eipsi-forms' ); ?>
        </label>
    </div>

    <div style="margin-top: 12px; display: flex; gap: 10px; align-items: center;">
        <button type="button" id="btn-recalculate" class="button button-primary" disabled data-study-id="<?php echo esc_attr( $study_id ); ?>">
            <?php esc_html_e( 'Recalcular tomas futuras', 'eipsi-forms' ); ?>
        </button>
        <?php if ( ! empty( $last_batch_id ) ): ?>
        <button type="button" id="btn-rollback" class="button" data-batch-id="<?php echo esc_attr( $last_batch_id ); ?>">
            <?php esc_html_e( 'Deshacer última recalculación', 'eipsi-forms' ); ?>
        </button>
        <?php endif; ?>
    </div>

    <div id="recalc-result" style="margin-top: 12px;"></div>
</div>

<script>
( function( $ ) {
    $( '#confirm-recalculation' ).on( 'change', function() {
        $( '#btn-recalculate' ).prop( 'disabled', ! this.checked );
    } );
    $( '#btn-recalculate' ).on( 'click', function() {
        const studyId = $( this ).data( 'study-id' );
        $( this ).prop( 'disabled', true ).text( 'Recalculando...' );
        $.post( ajaxurl, { action: 'eipsi_recalculate_waves', study_id: studyId, nonce: eipsiAdmin.nonce }, function( response ) {
            $( '#recalc-result' ).html( '<div class="notice notice-' + ( response.success ? 'success' : 'error' ) + ' inline"><p>' + response.data.message + '</p></div>' );
            $( '#btn-recalculate' ).prop( 'disabled', false ).text( 'Recalcular tomas futuras' );
            $( '#confirm-recalculation' ).prop( 'checked', false );
        } );
    } );
    $( '#btn-rollback' ).on( 'click', function() {
        if ( ! confirm( 'Esto restaurará los tiempos anteriores. ¿Continuar?' ) ) return;
        $.post( ajaxurl, { action: 'eipsi_rollback_recalculation', batch_id: $( this ).data( 'batch-id' ), nonce: eipsiAdmin.nonce }, function( response ) {
            $( '#recalc-result' ).html( '<div class="notice notice-' + ( response.success ? 'success' : 'error' ) + ' inline"><p>' + response.data.message + '</p></div>' );
        } );
    } );
} )( jQuery );
</script>
