<?php
/**
 * EIPSI Forms - Randomization Database Setup
 * 
 * Crea las tablas necesarias para el sistema de aleatorización RCT:
 * - wp_eipsi_randomization_configs: Configuraciones de estudios
 * - wp_eipsi_randomization_assignments: Asignaciones de usuarios
 * 
 * @package EIPSI_Forms
 * @since 1.3.1
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Crear tabla de configuraciones de aleatorización
 * 
 * Almacena la configuración de cada estudio RCT:
 * - Qué formularios participan
 * - Probabilidades de asignación
 * - Método (seeded vs pure-random)
 * - Asignaciones manuales
 */
function eipsi_create_randomization_configs_table() {
    global $wpdb;

    $table_name      = $wpdb->prefix . 'eipsi_randomization_configs';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        randomization_id VARCHAR(255) NOT NULL,
        formularios LONGTEXT NOT NULL,
        probabilidades LONGTEXT,
        method VARCHAR(20) DEFAULT 'seeded',
        manual_assignments LONGTEXT,
        show_instructions TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY randomization_id (randomization_id),
        KEY method (method),
        KEY created_at (created_at)
    ) {$charset_collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );

    // Verificar si se creó correctamente
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" );
    
    if ( $table_exists === $table_name ) {
        error_log( '[EIPSI Forms] Tabla creada: ' . $table_name );
        return true;
    } else {
        error_log( '[EIPSI Forms] ERROR: No se pudo crear tabla ' . $table_name );
        return false;
    }
}

/**
 * Crear tabla de asignaciones de usuarios
 * 
 * Almacena qué formulario fue asignado a cada usuario (por fingerprint):
 * - randomization_id: A qué estudio pertenece
 * - user_fingerprint: Identificador único del dispositivo/navegador
 * - assigned_form_id: Qué formulario le tocó
 * - Timestamps de acceso
 */
function eipsi_create_randomization_assignments_table() {
    global $wpdb;

    $table_name      = $wpdb->prefix . 'eipsi_randomization_assignments';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        template_id BIGINT(20) UNSIGNED NOT NULL,
        config_id VARCHAR(255) NOT NULL,
        user_fingerprint VARCHAR(255) NOT NULL,
        assigned_form_id BIGINT(20) UNSIGNED NOT NULL,
        assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        last_access DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        access_count INT(11) DEFAULT 1,
        PRIMARY KEY  (id),
        UNIQUE KEY unique_assignment (template_id, config_id, user_fingerprint),
        KEY template_id (template_id),
        KEY config_id (config_id),
        KEY user_fingerprint (user_fingerprint),
        KEY assigned_form_id (assigned_form_id),
        KEY assigned_at (assigned_at)
    ) {$charset_collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );

    // Verificar si se creó correctamente
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" );
    
    if ( $table_exists === $table_name ) {
        error_log( '[EIPSI Forms] Tabla creada: ' . $table_name );
        return true;
    } else {
        error_log( '[EIPSI Forms] ERROR: No se pudo crear tabla ' . $table_name );
        return false;
    }
}

/**
 * Crear ambas tablas en activación del plugin
 */
function eipsi_create_randomization_tables() {
    $configs_created     = eipsi_create_randomization_configs_table();
    $assignments_created = eipsi_create_randomization_assignments_table();

    if ( $configs_created && $assignments_created ) {
        error_log( '[EIPSI Forms] Sistema de aleatorización RCT configurado correctamente ✓' );
        update_option( 'eipsi_randomization_db_version', '1.3.1' );
        return true;
    } else {
        error_log( '[EIPSI Forms] ERROR: Fallo en configuración del sistema RCT' );
        return false;
    }
}

/**
 * Guardar o actualizar configuración de aleatorización
 * 
 * @param string $randomization_id ID único de la aleatorización
 * @param array  $config Configuración completa
 * @return bool True si se guardó correctamente
 */
function eipsi_save_randomization_config( $randomization_id, $config ) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'eipsi_randomization_configs';

    // Preparar datos
    $data = array(
        'randomization_id'   => $randomization_id,
        'formularios'        => wp_json_encode( $config['formularios'] ?? array() ),
        'probabilidades'     => wp_json_encode( $config['probabilidades'] ?? array() ),
        'method'             => $config['method'] ?? 'seeded',
        'manual_assignments' => wp_json_encode( $config['manualAssignments'] ?? array() ),
        'show_instructions'  => ! empty( $config['showInstructions'] ) ? 1 : 0,
        'updated_at'         => current_time( 'mysql' ),
    );

    // Verificar si ya existe
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $existing = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT id FROM {$table_name} WHERE randomization_id = %s",
            $randomization_id
        )
    );

    if ( $existing ) {
        // Actualizar
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $result = $wpdb->update(
            $table_name,
            $data,
            array( 'randomization_id' => $randomization_id ),
            array( '%s', '%s', '%s', '%s', '%s', '%d', '%s' ),
            array( '%s' )
        );

        error_log( "[EIPSI Forms] Config actualizada: {$randomization_id}" );
        return $result !== false;
    } else {
        // Insertar nueva
        $data['created_at'] = current_time( 'mysql' );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $result = $wpdb->insert(
            $table_name,
            $data,
            array( '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
        );

        error_log( "[EIPSI Forms] Config creada: {$randomization_id}" );
        return $result !== false;
    }
}

/**
 * Obtener configuración de aleatorización desde DB
 * 
 * @param string $randomization_id ID único
 * @return array|null Configuración o null si no existe
 */
function eipsi_get_randomization_config_from_db( $randomization_id ) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'eipsi_randomization_configs';

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $row = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE randomization_id = %s",
            $randomization_id
        ),
        ARRAY_A
    );

    if ( ! $row ) {
        return null;
    }

    // Decodificar JSON fields
    return array(
        'randomizationId'    => $row['randomization_id'],
        'formularios'        => json_decode( $row['formularios'], true ) ?? array(),
        'probabilidades'     => json_decode( $row['probabilidades'], true ) ?? array(),
        'method'             => $row['method'],
        'manualAssignments'  => json_decode( $row['manual_assignments'], true ) ?? array(),
        'showInstructions'   => (bool) $row['show_instructions'],
        'created_at'         => $row['created_at'],
        'updated_at'         => $row['updated_at'],
    );
}

/**
 * Obtener todas las asignaciones de un estudio
 * 
 * @param string $randomization_id ID único del estudio
 * @return array Lista de asignaciones
 */
function eipsi_get_study_assignments( $randomization_id ) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'eipsi_randomization_assignments';

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$table_name} 
            WHERE randomization_id = %s 
            ORDER BY assigned_at DESC",
            $randomization_id
        ),
        ARRAY_A
    );

    return $results ?? array();
}

/**
 * Obtener estadísticas de un estudio RCT
 * 
 * @param string $randomization_id ID único del estudio
 * @return array Estadísticas
 */
function eipsi_get_study_stats( $randomization_id ) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'eipsi_randomization_assignments';

    // Total de asignaciones
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $total = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE randomization_id = %s",
            $randomization_id
        )
    );

    // Distribución por formulario
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $distribution = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT assigned_form_id, COUNT(*) as count 
            FROM {$table_name} 
            WHERE randomization_id = %s 
            GROUP BY assigned_form_id",
            $randomization_id
        ),
        ARRAY_A
    );

    // Total de accesos
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $total_accesses = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT SUM(access_count) FROM {$table_name} WHERE randomization_id = %s",
            $randomization_id
        )
    );

    return array(
        'total_participants' => (int) $total,
        'distribution'       => $distribution,
        'total_accesses'     => (int) $total_accesses,
    );
}

/**
 * Verificar si las tablas existen
 * 
 * @return bool True si ambas tablas existen
 */
function eipsi_randomization_tables_exist() {
    global $wpdb;

    $configs_table     = $wpdb->prefix . 'eipsi_randomization_configs';
    $assignments_table = $wpdb->prefix . 'eipsi_randomization_assignments';

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $configs_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$configs_table}'" ) === $configs_table;
    
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $assignments_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$assignments_table}'" ) === $assignments_table;

    return $configs_exists && $assignments_exists;
}

// Hook para activación
add_action( 'eipsi_forms_activation', 'eipsi_create_randomization_tables' );

// Verificar tablas en cada carga de admin (crear si faltan)
add_action( 'admin_init', function() {
    if ( ! eipsi_randomization_tables_exist() ) {
        error_log( '[EIPSI Forms] Tablas de aleatorización faltantes. Creando...' );
        eipsi_create_randomization_tables();
    }
} );

/**
 * Registrar endpoint REST API para guardar configuración desde el bloque
 */
function eipsi_register_randomization_rest_endpoint() {
    register_rest_route(
        'wp/v2',
        '/eipsi_randomization_config',
        array(
            'methods'             => 'POST',
            'callback'            => 'eipsi_rest_save_randomization_config',
            'permission_callback' => function() {
                return current_user_can( 'edit_posts' );
            },
        )
    );
}

add_action( 'rest_api_init', 'eipsi_register_randomization_rest_endpoint' );

/**
 * Callback del endpoint REST: guardar configuración
 * 
 * @param WP_REST_Request $request Request object
 * @return WP_REST_Response
 */
function eipsi_rest_save_randomization_config( $request ) {
    $randomization_id   = $request->get_param( 'randomizationId' );
    $formularios        = $request->get_param( 'formularios' );
    $method             = $request->get_param( 'method' );
    $manual_assignments = $request->get_param( 'manualAssignments' );
    $show_instructions  = $request->get_param( 'showInstructions' );

    if ( empty( $randomization_id ) || empty( $formularios ) ) {
        return new WP_REST_Response(
            array(
                'success' => false,
                'message' => 'Missing required parameters',
            ),
            400
        );
    }

    // Construir array de probabilidades
    $probabilidades = array();
    foreach ( $formularios as $form ) {
        if ( isset( $form['postId'] ) && isset( $form['porcentaje'] ) ) {
            $probabilidades[ $form['postId'] ] = $form['porcentaje'];
        }
    }

    $config = array(
        'formularios'        => $formularios,
        'probabilidades'     => $probabilidades,
        'method'             => $method ?? 'seeded',
        'manualAssignments'  => $manual_assignments ?? array(),
        'showInstructions'   => $show_instructions ?? false,
    );

    $result = eipsi_save_randomization_config( $randomization_id, $config );

    if ( $result ) {
        return new WP_REST_Response(
            array(
                'success' => true,
                'message' => 'Configuration saved successfully',
            ),
            200
        );
    } else {
        return new WP_REST_Response(
            array(
                'success' => false,
                'message' => 'Failed to save configuration',
            ),
            500
        );
    }
}
