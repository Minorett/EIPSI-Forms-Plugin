<?php
/**
 * EIPSI Randomization Config Handler - FLUJO MANUAL
 * 
 * Maneja el guardado de configuración de aleatorización y generación de shortcode único
 * para el flujo manual basado en shortcodes de formularios.
 * 
 * @package EIPSI_Forms
 * @since 1.3.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Endpoint AJAX para guardar configuración de aleatorización
 * 
 * @since 1.3.4
 */
function eipsi_save_randomization_config() {
    // Verificar nonce de seguridad
    if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'eipsi_randomization_config' ) ) {
        wp_send_json_error( array( 'message' => 'Token de seguridad inválido' ) );
    }

    // Verificar permisos del usuario
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( array( 'message' => 'No tienes permisos para realizar esta acción' ) );
    }

    // Obtener y sanitizar datos
    $post_id = intval( $_POST['post_id'] ?? 0 );
    $shortcodes = isset( $_POST['shortcodes'] ) ? array_map( 'sanitize_text_field', $_POST['shortcodes'] ) : array();
    $formularios = isset( $_POST['formularios'] ) ? $_POST['formularios'] : array();
    $probabilidades = isset( $_POST['probabilidades'] ) ? array_map( 'intval', $_POST['probabilidades'] ) : array();
    $metodo = sanitize_text_field( $_POST['metodo'] ?? 'pure-random' );
    $seed = sanitize_text_field( $_POST['seed'] ?? '' );
    $permitirOverride = isset( $_POST['permitirOverride'] ) ? (bool) $_POST['permitirOverride'] : true;
    $registrarAsignaciones = isset( $_POST['registrarAsignaciones'] ) ? (bool) $_POST['registrarAsignaciones'] : true;

    // Validaciones
    if ( ! $post_id ) {
        wp_send_json_error( array( 'message' => 'ID del template requerido' ) );
    }

    if ( ! is_array( $formularios ) || empty( $formularios ) ) {
        wp_send_json_error( array( 'message' => 'Se requiere al menos un formulario' ) );
    }

    if ( count( $formularios ) < 1 ) {
        wp_send_json_error( array( 'message' => 'La aleatorización requiere al menos 1 formulario configurado' ) );
    }

    // Validar que probabilidades sumen 100%
    $totalProbabilidades = array_sum( $probabilidades );
    if ( $totalProbabilidades !== 100 ) {
        wp_send_json_error( array( 
            'message' => sprintf( 'Las probabilidades deben sumar 100%%. Total actual: %d%%', $totalProbabilidades )
        ) );
    }

    // Validar que todos los formularios existan
    foreach ( $formularios as $formulario ) {
        if ( ! isset( $formulario['exists'] ) || ! $formulario['exists'] ) {
            wp_send_json_error( array( 'message' => 'Algunos formularios no existen. Verificá los IDs ingresados.' ) );
        }
    }

    // Generar config_id único
    $config_id = 'config_' . $post_id . '_' . time() . '_' . wp_generate_password( 8, false );

    // Preparar configuración
    $config = array(
        'config_id' => $config_id,
        'post_id' => $post_id,
        'shortcodes' => $shortcodes,
        'formularios' => $formularios,
        'probabilidades' => $probabilidades,
        'metodo' => $metodo,
        'seed' => $seed,
        'permitirOverride' => $permitirOverride,
        'registrarAsignaciones' => $registrarAsignaciones,
        'created_at' => current_time( 'mysql' ),
        'created_by' => get_current_user_id(),
        'version' => '1.3.4'
    );

    // Guardar como post meta del template
    $meta_key = '_randomization_config_' . $config_id;
    $result = update_post_meta( $post_id, $meta_key, $config );

    if ( ! $result ) {
        wp_send_json_error( array( 'message' => 'Error guardando configuración en la base de datos' ) );
    }

    // Generar shortcode único para el template
    $shortcode = sprintf( '[eipsi_randomization template="%d" config="%s"]', $post_id, $config_id );

    // Respuesta exitosa
    wp_send_json_success( array(
        'config_id' => $config_id,
        'shortcode' => $shortcode,
        'message' => 'Configuración guardada exitosamente'
    ) );
}

// Registrar endpoint AJAX para usuarios logueados
add_action( 'wp_ajax_eipsi_save_randomization_config', 'eipsi_save_randomization_config' );

/**
 * Registrar endpoint REST para compatibilidad con el bloque Gutenberg
 * 
 * @since 1.3.4
 */
function eipsi_register_randomization_config_rest() {
    register_rest_route( 'eipsi/v1', '/randomization-config', array(
        'methods' => 'POST',
        'callback' => 'eipsi_randomization_config_rest_handler',
        'permission_callback' => function() {
            return current_user_can( 'edit_posts' );
        },
        'args' => array(
            'post_id' => array(
                'required' => true,
                'type' => 'integer',
            ),
            'shortcodes' => array(
                'required' => false,
                'type' => 'array',
            ),
            'formularios' => array(
                'required' => true,
                'type' => 'array',
            ),
            'probabilidades' => array(
                'required' => true,
                'type' => 'object',
            ),
            'metodo' => array(
                'required' => false,
                'type' => 'string',
                'default' => 'pure-random',
            ),
            'seed' => array(
                'required' => false,
                'type' => 'string',
                'default' => '',
            ),
            'permitirOverride' => array(
                'required' => false,
                'type' => 'boolean',
                'default' => true,
            ),
            'registrarAsignaciones' => array(
                'required' => false,
                'type' => 'boolean',
                'default' => true,
            ),
        ),
    ) );
}
add_action( 'rest_api_init', 'eipsi_register_randomization_config_rest' );

/**
 * Handler para endpoint REST
 * 
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function eipsi_randomization_config_rest_handler( $request ) {
    $post_id = $request->get_param( 'post_id' );
    $shortcodes = $request->get_param( 'shortcodes' ) ?: array();
    $formularios = $request->get_param( 'formularios' ) ?: array();
    $probabilidades = $request->get_param( 'probabilidades' ) ?: array();
    $metodo = $request->get_param( 'metodo' ) ?: 'pure-random';
    $seed = $request->get_param( 'seed' ) ?: '';
    $permitirOverride = $request->get_param( 'permitirOverride' ) ?: true;
    $registrarAsignaciones = $request->get_param( 'registrarAsignaciones' ) ?: true;

    // Validaciones
    if ( ! $post_id ) {
        return new WP_REST_Response( array(
            'success' => false,
            'message' => 'ID del template requerido'
        ), 400 );
    }

    if ( empty( $formularios ) ) {
        return new WP_REST_Response( array(
            'success' => false,
            'message' => 'Se requiere al menos un formulario'
        ), 400 );
    }

    if ( count( $formularios ) < 1 ) {
        return new WP_REST_Response( array(
            'success' => false,
            'message' => 'La aleatorización requiere al menos 1 formulario configurado'
        ), 400 );
    }

    // Validar que probabilidades sumen 100%
    $totalProbabilidades = array_sum( $probabilidades );
    if ( $totalProbabilidades !== 100 ) {
        return new WP_REST_Response( array(
            'success' => false,
            'message' => sprintf( 'Las probabilidades deben sumar 100%%. Total actual: %d%%', $totalProbabilidades )
        ), 400 );
    }

    // Validar que todos los formularios existan
    foreach ( $formularios as $formulario ) {
        if ( ! isset( $formulario['exists'] ) || ! $formulario['exists'] ) {
            return new WP_REST_Response( array(
                'success' => false,
                'message' => 'Algunos formularios no existen. Verificá los IDs ingresados.'
            ), 400 );
        }
    }

    // Generar config_id único
    $config_id = 'config_' . $post_id . '_' . time() . '_' . wp_generate_password( 8, false );

    // Preparar configuración
    $config = array(
        'config_id' => $config_id,
        'post_id' => $post_id,
        'shortcodes' => $shortcodes,
        'formularios' => $formularios,
        'probabilidades' => $probabilidades,
        'metodo' => $metodo,
        'seed' => $seed,
        'permitirOverride' => $permitirOverride,
        'registrarAsignaciones' => $registrarAsignaciones,
        'created_at' => current_time( 'mysql' ),
        'created_by' => get_current_user_id(),
        'version' => '1.3.4'
    );

    // Guardar como post meta del template
    $meta_key = '_randomization_config_' . $config_id;
    $result = update_post_meta( $post_id, $meta_key, $config );

    if ( ! $result ) {
        return new WP_REST_Response( array(
            'success' => false,
            'message' => 'Error guardando configuración en la base de datos'
        ), 500 );
    }

    // Generar shortcode único para el template
    $shortcode = sprintf( '[eipsi_randomization template="%d" config="%s"]', $post_id, $config_id );

    // Respuesta exitosa
    return new WP_REST_Response( array(
        'success' => true,
        'config_id' => $config_id,
        'shortcode' => $shortcode,
        'message' => 'Configuración guardada exitosamente'
    ), 200 );
}

/**
 * Función para obtener configuración de aleatorización desde post meta
 * 
 * @param int $post_id Template ID
 * @param string $config_id Config ID
 * @return array|null
 */
function eipsi_get_randomization_config_from_post_meta( $post_id, $config_id ) {
    $meta_key = '_randomization_config_' . $config_id;
    $config = get_post_meta( $post_id, $meta_key, true );
    
    if ( ! $config || empty( $config ) ) {
        return null;
    }

    return $config;
}