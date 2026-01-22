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
        
        // Instanciar clase de BD externa
        require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/database.php';
        $external_db = new EIPSI_External_Database();
        $is_external_db = $external_db->is_enabled();

        // Eliminar el registro de la base de datos correcta
        $result = false;
        $error_message = '';

        if ($is_external_db) {
            // Usar BD externa
            $mysqli = $external_db->get_connection();
            if ($mysqli) {
                // Verificar que el registro existe antes de eliminar
                $table_name = 'vas_form_results'; // Tabla en BD externa
                $escaped_id = intval($id);
                
                // Primero verificar que existe
                $check_query = "SELECT COUNT(*) as count FROM `{$table_name}` WHERE id = {$escaped_id}";
                $check_result = $mysqli->query($check_query);
                
                if ($check_result) {
                    $row = $check_result->fetch_assoc();
                    if ($row['count'] > 0) {
                        // El registro existe, proceder a eliminar
                        $delete_query = "DELETE FROM `{$table_name}` WHERE id = {$escaped_id}";
                        $delete_result = $mysqli->query($delete_query);
                        
                        if ($delete_result) {
                            $result = $mysqli->affected_rows;
                        } else {
                            $error_message = 'Database query error: ' . $mysqli->error;
                        }
                    } else {
                        $error_message = 'Record not found in external database';
                    }
                } else {
                    $error_message = 'Failed to verify record existence: ' . $mysqli->error;
                }
                
                $mysqli->close();
            } else {
                // Fallback a BD local si conexión externa falla
                global $wpdb;
                $table_name = $wpdb->prefix . 'vas_form_results';
                $result = $wpdb->delete($table_name, array('id' => $id), array('%d'));
                
                if ($result === false || $result === 0) {
                    $error_message = 'External DB connection failed and local DB record not found';
                }
            }
        } else {
            // Usar BD local (comportamiento original)
            global $wpdb;
            $table_name = $wpdb->prefix . 'vas_form_results';
            
            // Verificar que el registro existe antes de eliminar
            $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE id = %d", $id));
            
            if ($count > 0) {
                $result = $wpdb->delete($table_name, array('id' => $id), array('%d'));
            } else {
                $error_message = 'Record not found in local database';
            }
        }

        // Redirigir con mensaje de éxito o error
        if ($result !== false && $result > 0) {
            // Log de éxito
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf('[EIPSI Forms] Response deleted successfully - ID: %d - Database: %s', 
                    $id, 
                    $is_external_db ? 'external' : 'local'
                ));
            }
            
            $redirect_url = add_query_arg(
                array('page' => 'eipsi-results', 'deleted' => '1'),
                admin_url('admin.php')
            );
        } else {
            // Log de error
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf('[EIPSI Forms] Failed to delete response - ID: %d - Database: %s - Error: %s', 
                    $id, 
                    $is_external_db ? 'external' : 'local',
                    $error_message
                ));
            }
            
            $redirect_url = add_query_arg(
                array('page' => 'eipsi-results', 'error' => 'delete'),
                admin_url('admin.php')
            );
        }
        
        wp_safe_redirect($redirect_url);
        exit;
    }
});