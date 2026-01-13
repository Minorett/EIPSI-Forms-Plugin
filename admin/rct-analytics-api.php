<?php
/**
 * EIPSI Forms - RCT Analytics API
 * 
 * Maneja los endpoints AJAX para el RCT Analytics Dashboard
 * 
 * @package EIPSI_Forms
 * @since 1.3.2
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registrar los endpoints AJAX
 */
function eipsi_register_rct_analytics_endpoints() {
    // Para usuarios logueados
    add_action('wp_ajax_eipsi_get_randomizations', 'eipsi_get_randomizations');
    add_action('wp_ajax_eipsi_get_randomization_details', 'eipsi_get_randomization_details');
    add_action('wp_ajax_eipsi_get_randomization_users', 'eipsi_get_randomization_users');
}
add_action('init', 'eipsi_register_rct_analytics_endpoints');

/**
 * Obtener lista de aleatorizaciones para el dashboard
 */
function eipsi_get_randomizations() {
    // Verificar nonce y permisos
    if (!wp_verify_nonce($_POST['nonce'], 'eipsi_rct_analytics_nonce') || !current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized', 403);
        return;
    }

    global $wpdb;

    try {
        // Query principal: listar aleatorizaciones con estadísticas
        $query = "
            SELECT 
                rc.randomization_id,
                rc.formularios,
                rc.probabilidades,
                rc.method,
                rc.created_at,
                rc.updated_at,
                rc.show_instructions,
                COUNT(DISTINCT ra.user_fingerprint) as total_assigned,
                COUNT(CASE WHEN ra.last_access IS NOT NULL THEN 1 END) as completed_count,
                MAX(ra.assigned_at) as last_assignment,
                AVG(ra.access_count) as avg_access_count,
                AVG(DATEDIFF(CURDATE(), DATE(ra.assigned_at))) as avg_days
            FROM {$wpdb->prefix}eipsi_randomization_configs rc
            LEFT JOIN {$wpdb->prefix}eipsi_randomization_assignments ra 
                ON rc.randomization_id = ra.randomization_id
            GROUP BY rc.randomization_id
            ORDER BY rc.created_at DESC
        ";

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $results = $wpdb->get_results($query);

        if ($results === false) {
            wp_send_json_error('Error en consulta SQL');
            return;
        }

        $randomizations = array();

        foreach ($results as $row) {
            // Decodificar JSON de formularios
            $formularios = json_decode($row->formularios, true);
            if (!$formularios) $formularios = array();

            // Decodificar JSON de probabilidades
            $probabilidades = json_decode($row->probabilidades, true);
            if (!$probabilidades) $probabilidades = array();

            // Obtener distribución por formulario
            $distribution_query = "
                SELECT 
                    ra.assigned_form_id,
                    COUNT(*) as count,
                    COUNT(CASE WHEN ra.last_access IS NOT NULL THEN 1 END) as completed_count,
                    AVG(ra.access_count) as avg_access_count,
                    AVG(DATEDIFF(CURDATE(), DATE(ra.assigned_at))) as avg_days
                FROM {$wpdb->prefix}eipsi_randomization_assignments ra
                WHERE ra.randomization_id = %s
                GROUP BY ra.assigned_form_id
            ";

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $distribution = $wpdb->get_results($wpdb->prepare($distribution_query, $row->randomization_id));

            $formatted_distribution = array();
            foreach ($distribution as $dist) {
                // Obtener título del formulario
                $form_title = get_the_title($dist->assigned_form_id);
                if (!$form_title) {
                    $form_title = "Formulario ID: {$dist->assigned_form_id}";
                }

                $formatted_distribution[] = array(
                    'form_id' => $dist->assigned_form_id,
                    'form_title' => $form_title,
                    'count' => intval($dist->count),
                    'completed_count' => intval($dist->completed_count),
                    'avg_access_count' => round($dist->avg_access_count, 1),
                    'avg_days' => round($dist->avg_days, 1)
                );
            }

            // Determinar si está activa (tiene asignaciones recientes)
            $is_active = false;
            if ($row->last_assignment) {
                $days_since_last = (time() - strtotime($row->last_assignment)) / (60 * 60 * 24);
                $is_active = $days_since_last <= 30; // Activa si hay actividad en los últimos 30 días
            }

            // Formatear fechas
            $created_formatted = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($row->created_at));
            $last_assignment_formatted = $row->last_assignment ? 
                human_time_diff(strtotime($row->last_assignment)) . ' ago' : 
                'Nunca';

            $randomizations[] = array(
                'randomization_id' => $row->randomization_id,
                'method' => $row->method,
                'is_active' => $is_active,
                'created_at' => $row->created_at,
                'created_formatted' => $created_formatted,
                'last_assignment' => $row->last_assignment,
                'last_assignment_formatted' => $last_assignment_formatted,
                'total_assigned' => intval($row->total_assigned),
                'completed_count' => intval($row->completed_count),
                'avg_access_count' => round($row->avg_access_count, 1),
                'avg_days' => round($row->avg_days, 1),
                'distribution' => $formatted_distribution,
                'formularios' => $formularios,
                'probabilidades' => $probabilidades
            );
        }

        wp_send_json_success(array(
            'randomizations' => $randomizations,
            'total_count' => count($randomizations)
        ));

    } catch (Exception $e) {
        error_log('[EIPSI RCT Analytics] Error: ' . $e->getMessage());
        wp_send_json_error('Error interno del servidor');
    }
}

/**
 * Obtener detalles específicos de una aleatorización
 */
function eipsi_get_randomization_details() {
    // Verificar nonce y permisos
    if (!wp_verify_nonce($_POST['nonce'], 'eipsi_rct_analytics_nonce') || !current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized', 403);
        return;
    }

    $randomization_id = sanitize_text_field($_POST['randomization_id'] ?? '');
    if (empty($randomization_id)) {
        wp_send_json_error('ID de aleatorización requerido');
        return;
    }

    global $wpdb;

    try {
        // Obtener configuración básica
        $config_query = "
            SELECT 
                rc.*,
                COUNT(DISTINCT ra.user_fingerprint) as total_assigned,
                COUNT(CASE WHEN ra.last_access IS NOT NULL THEN 1 END) as completed_count,
                MAX(ra.assigned_at) as last_assignment,
                MIN(ra.assigned_at) as first_assignment,
                AVG(ra.access_count) as avg_access_count,
                AVG(DATEDIFF(CURDATE(), DATE(ra.assigned_at))) as avg_days
            FROM {$wpdb->prefix}eipsi_randomization_configs rc
            LEFT JOIN {$wpdb->prefix}eipsi_randomization_assignments ra 
                ON rc.randomization_id = ra.randomization_id
            WHERE rc.randomization_id = %s
            GROUP BY rc.id
        ";

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $config = $wpdb->get_row($wpdb->prepare($config_query, $randomization_id));

        if (!$config) {
            wp_send_json_error('Aleatorización no encontrada');
            return;
        }

        // Decodificar configuración
        $formularios = json_decode($config->formularios, true) ?: array();
        $probabilidades = json_decode($config->probabilidades, true) ?: array();

        // Obtener distribución detallada por formulario
        $distribution_query = "
            SELECT 
                ra.assigned_form_id,
                COUNT(*) as total_assigned,
                COUNT(CASE WHEN ra.last_access IS NOT NULL THEN 1 END) as completed_count,
                COUNT(CASE WHEN ra.last_access IS NULL THEN 1 END) as dropout_count,
                AVG(ra.access_count) as avg_access_count,
                AVG(DATEDIFF(CURDATE(), DATE(ra.assigned_at))) as avg_days,
                MIN(ra.assigned_at) as first_assignment,
                MAX(ra.assigned_at) as last_assignment
            FROM {$wpdb->prefix}eipsi_randomization_assignments ra
            WHERE ra.randomization_id = %s
            GROUP BY ra.assigned_form_id
        ";

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $distribution_raw = $wpdb->get_results($wpdb->prepare($distribution_query, $randomization_id));

        $distribution = array();
        foreach ($distribution_raw as $dist) {
            $form_title = get_the_title($dist->assigned_form_id) ?: "Formulario ID: {$dist->assigned_form_id}";
            
            $completion_rate = $dist->total_assigned > 0 ? 
                round(($dist->completed_count / $dist->total_assigned) * 100, 1) : 0;

            $distribution[] = array(
                'form_id' => $dist->assigned_form_id,
                'form_title' => $form_title,
                'total_assigned' => intval($dist->total_assigned),
                'completed_count' => intval($dist->completed_count),
                'dropout_count' => intval($dist->dropout_count),
                'completion_rate' => $completion_rate,
                'avg_access_count' => round($dist->avg_access_count, 1),
                'avg_days' => round($dist->avg_days, 1),
                'first_assignment' => $dist->first_assignment,
                'last_assignment' => $dist->last_assignment
            );
        }

        // Calcular estadísticas generales
        $total_assigned = intval($config->total_assigned);
        $completed_count = intval($config->completed_count);
        $completion_rate = $total_assigned > 0 ? round(($completed_count / $total_assigned) * 100, 1) : 0;
        $dropout_count = $total_assigned - $completed_count;
        $dropout_rate = $total_assigned > 0 ? round(($dropout_count / $total_assigned) * 100, 1) : 0;

        // Formatear fechas
        $created_formatted = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($config->created_at));
        $first_assignment_formatted = $config->first_assignment ? 
            date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($config->first_assignment)) : 
            'N/A';
        $last_assignment_formatted = $config->last_assignment ? 
            human_time_diff(strtotime($config->last_assignment)) . ' ago' : 
            'Nunca';

        // Determinar estado
        $is_active = false;
        if ($config->last_assignment) {
            $days_since_last = (time() - strtotime($config->last_assignment)) / (60 * 60 * 24);
            $is_active = $days_since_last <= 30;
        }

        wp_send_json_success(array(
            'randomization_id' => $randomization_id,
            'created_at' => $config->created_at,
            'created_formatted' => $created_formatted,
            'method' => $config->method,
            'show_instructions' => (bool)$config->show_instructions,
            'is_active' => $is_active,
            'formularios' => $formularios,
            'probabilidades' => $probabilidades,
            
            // Estadísticas generales
            'total_assigned' => $total_assigned,
            'completed_count' => $completed_count,
            'dropout_count' => $dropout_count,
            'completion_rate' => $completion_rate,
            'dropout_rate' => $dropout_rate,
            'avg_access_count' => round($config->avg_access_count, 1),
            'avg_days' => round($config->avg_days, 1),
            
            // Distribución por formulario
            'distribution' => $distribution,
            
            // Timeline
            'first_assignment' => $config->first_assignment,
            'first_assignment_formatted' => $first_assignment_formatted,
            'last_assignment' => $config->last_assignment,
            'last_assignment_formatted' => $last_assignment_formatted
        ));

    } catch (Exception $e) {
        error_log('[EIPSI RCT Analytics] Error en detalles: ' . $e->getMessage());
        wp_send_json_error('Error interno del servidor');
    }
}

/**
 * Obtener lista de usuarios de una aleatorización
 */
function eipsi_get_randomization_users() {
    // Verificar nonce y permisos
    if (!wp_verify_nonce($_POST['nonce'], 'eipsi_rct_analytics_nonce') || !current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized', 403);
        return;
    }

    $randomization_id = sanitize_text_field($_POST['randomization_id'] ?? '');
    $page = intval($_POST['page'] ?? 1);
    $limit = intval($_POST['limit'] ?? 50);
    $offset = ($page - 1) * $limit;

    if (empty($randomization_id)) {
        wp_send_json_error('ID de aleatorización requerido');
        return;
    }

    global $wpdb;

    try {
        // Query principal
        $query = "
            SELECT 
                ra.user_fingerprint,
                ra.assigned_form_id,
                ra.assigned_at,
                ra.last_access,
                ra.access_count,
                CASE WHEN ra.last_access IS NOT NULL THEN 'Sí' ELSE 'No' END as completado,
                DATEDIFF(CURDATE(), DATE(ra.assigned_at)) as dias_transcurridos
            FROM {$wpdb->prefix}eipsi_randomization_assignments ra
            WHERE ra.randomization_id = %s
            ORDER BY ra.assigned_at DESC
            LIMIT %d OFFSET %d
        ";

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $results = $wpdb->get_results($wpdb->prepare($query, $randomization_id, $limit, $offset));

        // Query para total
        $count_query = "
            SELECT COUNT(*) as total
            FROM {$wpdb->prefix}eipsi_randomization_assignments
            WHERE randomization_id = %s
        ";

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $total = $wpdb->get_var($wpdb->prepare($count_query, $randomization_id));

        $users = array();
        foreach ($results as $row) {
            $form_title = get_the_title($row->assigned_form_id) ?: "Formulario ID: {$row->assigned_form_id}";
            
            // Anonimizar fingerprint
            $fingerprint_short = substr($row->user_fingerprint, 0, 8) . '...' . substr($row->user_fingerprint, -6);

            // Formatear fecha de asignación
            $assigned_formatted = date_i18n('j M, H:i', strtotime($row->assigned_at));

            // Formatear último acceso
            $last_access_formatted = $row->last_access ? 
                human_time_diff(strtotime($row->last_access)) . ' ago' : 
                'Nunca';

            $users[] = array(
                'fingerprint' => $fingerprint_short,
                'fingerprint_full' => $row->user_fingerprint,
                'form_id' => $row->assigned_form_id,
                'form_title' => $form_title,
                'assigned_at' => $row->assigned_at,
                'assigned_formatted' => $assigned_formatted,
                'last_access' => $row->last_access,
                'last_access_formatted' => $last_access_formatted,
                'access_count' => intval($row->access_count),
                'completado' => $row->completado,
                'dias_transcurridos' => intval($row->dias_transcurridos)
            );
        }

        wp_send_json_success(array(
            'users' => $users,
            'total' => intval($total),
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit)
        ));

    } catch (Exception $e) {
        error_log('[EIPSI RCT Analytics] Error en usuarios: ' . $e->getMessage());
        wp_send_json_error('Error interno del servidor');
    }
}
?>