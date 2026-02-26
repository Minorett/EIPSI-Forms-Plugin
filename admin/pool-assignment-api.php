<?php
/**
 * EIPSI Forms - Pool Assignment AJAX API
 *
 * Handlers para que participantes se unan a pools de asignación aleatoria.
 * Funciona tanto para usuarios logueados como anónimos (nopriv).
 *
 * @package EIPSI_Forms
 * @since 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handler AJAX: unirse a un pool (usuarios logueados y anónimos).
 *
 * Inputs POST esperados:
 *   - eipsi_pool_join_nonce : nonce de seguridad
 *   - pool_id               : int
 *   - email                 : string (email del participante)
 *   - name                  : string (opcional)
 *
 * Respuesta JSON:
 *   { success: bool, data: { magic_link_url, study_name, pool_name, is_new_assignment } }
 *   { success: false, data: { message: string } }
 */
function eipsi_ajax_join_pool() {
    // -----------------------------------------------------------------
    // 1. Verificar nonce
    // -----------------------------------------------------------------
    $nonce = isset( $_POST['eipsi_pool_join_nonce'] )
        ? sanitize_text_field( wp_unslash( $_POST['eipsi_pool_join_nonce'] ) )
        : '';

    if ( ! wp_verify_nonce( $nonce, 'eipsi_pool_join' ) ) {
        wp_send_json_error(
            array( 'message' => __( 'Token de seguridad inválido. Recargá la página e intentá de nuevo.', 'eipsi-forms' ) ),
            403
        );
    }

    // -----------------------------------------------------------------
    // 2. Sanitizar inputs
    // -----------------------------------------------------------------
    $pool_id = isset( $_POST['pool_id'] ) ? absint( $_POST['pool_id'] ) : 0;
    $email   = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
    $name    = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : null;

    if ( ! $pool_id ) {
        wp_send_json_error(
            array( 'message' => __( 'ID de pool inválido.', 'eipsi-forms' ) ),
            400
        );
    }

    if ( ! is_email( $email ) ) {
        wp_send_json_error(
            array( 'message' => __( 'Por favor ingresá un email válido.', 'eipsi-forms' ) ),
            400
        );
    }

    // -----------------------------------------------------------------
    // 3. Llamar al servicio de asignación
    // -----------------------------------------------------------------
    if ( ! class_exists( 'EIPSI_Pool_Assignment_Service' ) ) {
        require_once plugin_dir_path( __FILE__ ) . 'services/class-pool-assignment-service.php';
    }

    $service = new EIPSI_Pool_Assignment_Service();
    $result  = $service->assign_participant_to_pool( $pool_id, $email, $name );

    if ( is_wp_error( $result ) ) {
        wp_send_json_error(
            array( 'message' => $result->get_error_message() ),
            400
        );
    }

    // -----------------------------------------------------------------
    // 4. Respuesta de éxito
    // -----------------------------------------------------------------
    wp_send_json_success(
        array(
            'magic_link_url'    => esc_url( $result['magic_link_url'] ),
            'study_name'        => esc_html( $result['study_name'] ),
            'pool_name'         => esc_html( $result['pool_name'] ),
            'is_new_assignment' => (bool) $result['is_new_assignment'],
            'message'           => $result['is_new_assignment']
                ? __( '¡Listo! Te asignamos a tu estudio. Serás redirigido en un momento...', 'eipsi-forms' )
                : __( 'Ya tenés una asignación en este pool. Preparando tu acceso...', 'eipsi-forms' ),
        )
    );
}

add_action( 'wp_ajax_eipsi_join_pool', 'eipsi_ajax_join_pool' );
add_action( 'wp_ajax_nopriv_eipsi_join_pool', 'eipsi_ajax_join_pool' );

/**
 * Handler AJAX: obtener estadísticas de un pool (solo admins).
 *
 * Inputs POST esperados:
 *   - eipsi_pool_stats_nonce : nonce de seguridad
 *   - pool_id                : int
 *
 * Respuesta JSON:
 *   { success: bool, data: { by_study: {...}, total: int } }
 */
function eipsi_ajax_get_pool_stats() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => __( 'Sin permisos.', 'eipsi-forms' ) ), 403 );
    }

    $nonce = isset( $_POST['eipsi_pool_stats_nonce'] )
        ? sanitize_text_field( wp_unslash( $_POST['eipsi_pool_stats_nonce'] ) )
        : '';

    if ( ! wp_verify_nonce( $nonce, 'eipsi_pool_stats' ) ) {
        wp_send_json_error( array( 'message' => __( 'Token inválido.', 'eipsi-forms' ) ), 403 );
    }

    $pool_id = isset( $_POST['pool_id'] ) ? absint( $_POST['pool_id'] ) : 0;

    if ( ! $pool_id ) {
        wp_send_json_error( array( 'message' => __( 'ID de pool inválido.', 'eipsi-forms' ) ), 400 );
    }

    if ( ! class_exists( 'EIPSI_Pool_Assignment_Service' ) ) {
        require_once plugin_dir_path( __FILE__ ) . 'services/class-pool-assignment-service.php';
    }

    $service = new EIPSI_Pool_Assignment_Service();
    $stats   = $service->get_pool_stats( $pool_id );

    wp_send_json_success( $stats );
}

add_action( 'wp_ajax_eipsi_get_pool_stats', 'eipsi_ajax_get_pool_stats' );
