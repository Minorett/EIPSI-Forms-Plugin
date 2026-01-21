<?php
/**
 * EIPSI Forms - Migración de Schema RCT (v1.3.6)
 * 
 * Script para actualizar la tabla wp_eipsi_randomization_assignments
 * de template_id a randomization_id
 * 
 * CRITICAL FIX: Resuelve errores "Unknown column 'template_id'" en sistema RCT
 * 
 * @package EIPSI_Forms
 * @since 1.3.6
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
    update_option( 'eipsi_randomization_schema_version', '1.3.6' );

    return true;
}

/**
 * Hook para ejecutar migración en activación del plugin
 */
function eipsi_check_and_migrate_schema() {
    $current_version = get_option( 'eipsi_randomization_schema_version', '0' );
    
    if ( version_compare( $current_version, '1.3.6', '<' ) ) {
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
