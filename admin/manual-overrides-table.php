<?php
/**
 * EIPSI Forms - Manual Overrides Table Setup
 *
 * Crea la tabla wp_eipsi_manual_overrides para asignaciones manuales de randomización
 *
 * @package EIPSI_Forms
 * @since 1.4.5
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Crear tabla wp_eipsi_manual_overrides
 */
function eipsi_create_manual_overrides_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'eipsi_manual_overrides';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$table_name} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        randomization_id VARCHAR(255) NOT NULL,
        user_fingerprint VARCHAR(255) NOT NULL,
        assigned_form_id BIGINT(20) UNSIGNED NOT NULL,
        reason TEXT,
        created_by BIGINT(20) UNSIGNED NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        status ENUM('active', 'revoked', 'expired') DEFAULT 'active',
        expires_at DATETIME NULL,
        PRIMARY KEY (id),
        UNIQUE KEY unique_override (randomization_id, user_fingerprint),
        KEY randomization_id (randomization_id),
        KEY user_fingerprint (user_fingerprint),
        KEY status (status),
        KEY expires_at (expires_at),
        KEY created_by (created_by)
    ) {$charset_collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    error_log('[EIPSI Forms] Created/updated table: ' . $table_name);
}

// Hook para ejecutar creación de tabla al activar el plugin
add_action('admin_init', 'eipsi_ensure_manual_overrides_table');

function eipsi_ensure_manual_overrides_table() {
    // Verificar si la tabla existe
    global $wpdb;
    $table_name = $wpdb->prefix . 'eipsi_manual_overrides';

    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");

    if (!$table_exists) {
        eipsi_create_manual_overrides_table();
    }
}
