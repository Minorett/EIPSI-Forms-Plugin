<?php
/**
 * EIPSI Forms - Migración de Schema RCT (v1.3.7)
 * 
 * Script para actualizar la tabla wp_eipsi_randomization_assignments
 * de template_id a randomization_id
 * 
 * CRITICAL FIX: Resuelve errores "Unknown column 'template_id'" en sistema RCT
 * 
 * @package EIPSI_Forms
 * @since 1.3.7
 * 
 * CHANGELOG v1.3.7:
 * - Agregada función eipsi_autofix_randomization_assignments_schema()
 * - Detecta y crea columnas faltantes: config_id, persistent_mode, access_count
 * - Hook admin_init para ejecución automática
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Ejecutar migración de schema
 * 
 * Este script:
 * 1. Verifica si la tabla tiene columna 'template_id' (schema antiguo)
 * 2. Renombra 'template_id' → 'randomization_id'
 * 3. Actualiza índices y claves únicas
 * 4. Preserva todos los datos existentes
 * 
 * @return bool True si la migración fue exitosa
 */
function eipsi_migrate_randomization_schema() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'eipsi_randomization_assignments';

    // PASO 1: Verificar si la tabla existe
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" );
    
    if ( $table_exists !== $table_name ) {
        error_log( '[EIPSI Forms] Migración cancelada: tabla no existe. Se creará con schema correcto.' );
        return false;
    }

    // PASO 2: Verificar si la columna 'template_id' existe (schema antiguo)
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $column_check = $wpdb->get_results( "SHOW COLUMNS FROM {$table_name} LIKE 'template_id'" );
    
    if ( empty( $column_check ) ) {
        error_log( '[EIPSI Forms] Migración no requerida: schema ya está actualizado (randomization_id existe).' );
        return true; // Ya está actualizado
    }

    error_log( '[EIPSI Forms] Iniciando migración de schema RCT...' );

    // PASO 3: Eliminar índices y claves únicas del schema antiguo
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
    $wpdb->query( "ALTER TABLE {$table_name} DROP INDEX IF EXISTS unique_assignment" );
    
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
    $wpdb->query( "ALTER TABLE {$table_name} DROP INDEX IF EXISTS template_id" );

    // PASO 4: Renombrar columna template_id → randomization_id
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
    $result = $wpdb->query(
        "ALTER TABLE {$table_name} 
        CHANGE COLUMN template_id randomization_id VARCHAR(255) NOT NULL"
    );

    if ( $result === false ) {
        error_log( "[EIPSI Forms] ERROR en migración: {$wpdb->last_error}" );
        return false;
    }

    // PASO 5: Recrear índices y claves únicas con el nuevo schema
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
    $wpdb->query(
        "ALTER TABLE {$table_name} 
        ADD UNIQUE KEY unique_assignment (randomization_id, config_id, user_fingerprint)"
    );

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
    $wpdb->query(
        "ALTER TABLE {$table_name} 
        ADD KEY randomization_id (randomization_id)"
    );

    error_log( '[EIPSI Forms] ✅ Migración de schema RCT completada exitosamente.' );
    update_option( 'eipsi_randomization_schema_version', '1.3.7' );

    return true;
}

/**
 * AUTOFIX: Verificar y crear columnas faltantes en assignments table
 * 
 * Detecta automáticamente si faltan columnas y las crea:
 * - config_id (CRÍTICA)
 * - persistent_mode (NUEVA)
 * - access_count (si falta)
 * 
 * Funciona TANTO en WordPress DB como en External DB
 * 
 * @global wpdb $wpdb
 * @return bool True si autofix fue exitoso o no fue necesario
 */
function eipsi_autofix_randomization_assignments_schema() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'eipsi_randomization_assignments';

    // PASO 1: Verificar si la tabla existe
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" );
    
    if ( $table_exists !== $table_name ) {
        error_log( '[EIPSI RCT AUTOFIX] Tabla no existe. Será creada con schema correcto en próxima inicialización.' );
        return true; // No es un error - se creará con init hook
    }

    error_log( '[EIPSI RCT AUTOFIX] Escaneando tabla para columnas faltantes...' );

    // PASO 2: Obtener listado de columnas actuales
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $current_columns = $wpdb->get_results(
        "SHOW COLUMNS FROM {$table_name}",
        OBJECT_K
    );

    if ( ! $current_columns ) {
        error_log( '[EIPSI RCT AUTOFIX] ERROR: No se pudieron leer las columnas.' );
        return false;
    }

    $columns_to_add = array();

    // PASO 3: Verificar columnas CRÍTICAS que faltan
    
    // ❌ COLUMNA CRÍTICA: config_id (fue la causa principal del error)
    if ( ! isset( $current_columns['config_id'] ) ) {
        error_log( '[EIPSI RCT AUTOFIX] ⚠️ COLUMNA CRÍTICA FALTANTE: config_id' );
        $columns_to_add['config_id'] = "ALTER TABLE {$table_name} 
            ADD COLUMN config_id VARCHAR(255) NOT NULL AFTER randomization_id";
    }

    // 🆕 COLUMNA NUEVA: persistent_mode (para el toggle feature)
    if ( ! isset( $current_columns['persistent_mode'] ) ) {
        error_log( '[EIPSI RCT AUTOFIX] ⚠️ COLUMNA NUEVA FALTANTE: persistent_mode' );
        $columns_to_add['persistent_mode'] = "ALTER TABLE {$table_name} 
            ADD COLUMN persistent_mode TINYINT(1) DEFAULT 1 AFTER assigned_form_id";
    }

    // ✅ COLUMNA ESTÁNDAR: access_count (por si acaso)
    if ( ! isset( $current_columns['access_count'] ) ) {
        error_log( '[EIPSI RCT AUTOFIX] ⚠️ COLUMNA FALTANTE: access_count' );
        $columns_to_add['access_count'] = "ALTER TABLE {$table_name} 
            ADD COLUMN access_count INT(11) DEFAULT 1 AFTER last_access";
    }

    // PASO 4: Si no hay columnas faltantes, retornar true
    if ( empty( $columns_to_add ) ) {
        error_log( '[EIPSI RCT AUTOFIX] ✅ Schema está completo. No hay columnas faltantes.' );
        update_option( 'eipsi_autofix_schema_version', '1.3.7' );
        return true;
    }

    // PASO 5: EJECUTAR ALTERACIONES
    error_log( '[EIPSI RCT AUTOFIX] Creando ' . count( $columns_to_add ) . ' columnas faltantes...' );

    foreach ( $columns_to_add as $col_name => $alter_sql ) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
        $result = $wpdb->query( $alter_sql );

        if ( $result === false ) {
            error_log( "[EIPSI RCT AUTOFIX] ❌ ERROR al crear columna '{$col_name}': {$wpdb->last_error}" );
            return false;
        }

        error_log( "[EIPSI RCT AUTOFIX] ✅ Columna '{$col_name}' creada exitosamente" );
    }

    // PASO 6: Recrear índices si fue necesario
    // (Si se agregó config_id, necesitamos actualizar la UNIQUE KEY)
    if ( isset( $columns_to_add['config_id'] ) ) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
        $wpdb->query( "ALTER TABLE {$table_name} DROP INDEX IF EXISTS unique_assignment" );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
        $wpdb->query(
            "ALTER TABLE {$table_name} 
            ADD UNIQUE KEY unique_assignment (randomization_id, config_id, user_fingerprint)"
        );

        error_log( '[EIPSI RCT AUTOFIX] ✅ Índice unique_assignment recreado con config_id' );
    }

    error_log( '[EIPSI RCT AUTOFIX] ✅ AUTOFIX COMPLETADO - Todas las columnas creadas' );
    update_option( 'eipsi_autofix_schema_version', '1.3.7' );
    return true;
}

/**
 * Hook para ejecutar AUTOFIX automáticamente en cada admin_init
 * 
 * Esto asegura que incluso si la DB está desactualizada,
 * el autofix se dispare y corrija el schema automáticamente
 */
add_action( 'admin_init', function() {
    $current_version = get_option( 'eipsi_autofix_schema_version', '0' );
    
    if ( version_compare( $current_version, '1.3.7', '<' ) ) {
        error_log( '[EIPSI RCT AUTOFIX] Iniciando verificación de schema...' );
        eipsi_autofix_randomization_assignments_schema();
    }
} );

/**
 * Hook para ejecutar migración en activación del plugin
 */
function eipsi_check_and_migrate_schema() {
    $current_version = get_option( 'eipsi_randomization_schema_version', '0' );
    
    if ( version_compare( $current_version, '1.3.7', '<' ) ) {
        eipsi_migrate_randomization_schema();
    }
}

add_action( 'admin_init', 'eipsi_check_and_migrate_schema' );

/**
 * Endpoint AJAX para migración manual (por si falla la automática)
 */
function eipsi_ajax_migrate_schema() {
    // Verificar permisos
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'No autorizado' );
    }

    // Verificar nonce
    if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'eipsi_migrate_schema' ) ) {
        wp_send_json_error( 'Token de seguridad inválido' );
    }

    $result = eipsi_migrate_randomization_schema();

    if ( $result ) {
        wp_send_json_success( 'Migración completada exitosamente' );
    } else {
        wp_send_json_error( 'Error en migración. Revisar logs de error.' );
    }
}

add_action( 'wp_ajax_eipsi_migrate_schema', 'eipsi_ajax_migrate_schema' );
