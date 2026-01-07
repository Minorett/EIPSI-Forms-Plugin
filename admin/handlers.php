<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_init', function() {
    // Verificar si es una solicitud de eliminación
    if (isset($_GET['page']) && $_GET['page'] === 'eipsi-results' && 
        isset($_GET['action']) && $_GET['action'] === 'delete') {
        
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            $redirect_url = add_query_arg(
                array('page' => 'eipsi-results', 'error' => 'permission'),
                admin_url('admin.php')
            );
            wp_safe_redirect($redirect_url);
            exit;
        }
        
        // Validar ID y nonce
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if (!$id || !isset($_GET['_wpnonce'])) {
            $redirect_url = add_query_arg(
                array('page' => 'eipsi-results', 'error' => 'invalid'),
                admin_url('admin.php')
            );
            wp_safe_redirect($redirect_url);
            exit;
        }
        
        // Verificar nonce de seguridad - ALIGNED with results-page.php
        if (!wp_verify_nonce($_GET['_wpnonce'], 'delete_response_' . $id)) {
            $redirect_url = add_query_arg(
                array('page' => 'eipsi-results', 'error' => 'nonce'),
                admin_url('admin.php')
            );
            wp_safe_redirect($redirect_url);
            exit;
        }
        
        // Eliminar el registro de la base de datos
        global $wpdb;
        $table_name = $wpdb->prefix . 'vas_form_results';
        
        $result = $wpdb->delete(
            $table_name, 
            array('id' => $id), 
            array('%d')
        );
        
        // Redirigir con mensaje de éxito o error
        if ($result !== false && $result > 0) {
            $redirect_url = add_query_arg(
                array('page' => 'eipsi-results', 'deleted' => '1'),
                admin_url('admin.php')
            );
        } else {
            $redirect_url = add_query_arg(
                array('page' => 'eipsi-results', 'error' => 'delete'),
                admin_url('admin.php')
            );
        }
        
        wp_safe_redirect($redirect_url);
        exit;
    }
});