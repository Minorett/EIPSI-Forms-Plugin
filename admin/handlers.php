<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_init', function() {
    // Verificar si es una solicitud de eliminación
    if (isset($_GET['page']) && $_GET['page'] === 'vas-dinamico-results' && 
        isset($_GET['action']) && $_GET['action'] === 'delete') {
        
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.', 'vas-dinamico-forms'));
        }
        
        // Validar ID y nonce
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if (!$id || !isset($_GET['_wpnonce'])) {
            wp_die(__('Invalid request.', 'vas-dinamico-forms'));
        }
        
        // Verificar nonce de seguridad
        if (!wp_verify_nonce($_GET['_wpnonce'], 'vas_dinamico_delete_' . $id)) {
            wp_die(__('Security check failed.', 'vas-dinamico-forms'));
        }
        
        // Eliminar el registro de la base de datos
        global $wpdb;
        $table_name = $wpdb->prefix . 'vas_form_results';
        
        $result = $wpdb->delete(
            $table_name, 
            array('id' => $id), 
            array('%d')
        );
        
        // Redirigir con mensaje de éxito
        if ($result !== false) {
            wp_redirect(admin_url('admin.php?page=vas-dinamico-results&deleted=1'));
        } else {
            wp_redirect(admin_url('admin.php?page=vas-dinamico-results&error=1'));
        }
        exit;
    }
});